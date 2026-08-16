<?php

namespace App\Console\Commands;

use App\Support\CustomizadorDaInstalacao;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;
use Throwable;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\note;

/**
 * Deixa o projeto pronto para uso em um comando.
 *
 * Roda automaticamente no `composer create-project` (post-create-project-cmd)
 * e pode ser reexecutado à mão depois de um `git clone` — todos os passos são
 * idempotentes. Nenhum passo aborta a instalação: o que falhar vira aviso com
 * a instrução de como refazer.
 *
 * Num projeto NASCENDO ele também pergunta: nome, banco, credenciais do admin,
 * cor e multi-organização, no mesmo lugar em que o `laravel new` faz as dele.
 * O Composer repassa o terminal ao script (`EventDispatcher::executeTty`), então
 * as perguntas aparecem dentro do `create-project`. Sem terminal — CI, Docker,
 * `--no-interaction` — nada é perguntado e a instalação é a de sempre.
 */
class KitInstall extends Command
{
    protected $signature = 'kit:install
        {--no-npm : Pula a instalação e o build dos assets front-end}
        {--no-seed : Não popula o banco (papéis, usuário inicial, agentes de IA)}
        {--force : Recria o banco SQLite do zero (apaga os dados existentes) e refaz as perguntas}
        {--no-custom : Pula as perguntas de customização e instala com os padrões}
        {--no-support : Pula o convite para dar uma estrela ao kit no GitHub}';

    protected $description = 'Instala o starter-kit: banco, migrations, seeders, permissões e assets';

    /** Endereço do kit, para o convite da estrela. */
    private const REPOSITORIO = 'https://github.com/gsferro/filament-starter-kit-easy';

    /** @var list<string> */
    protected array $avisos = [];

    /**
     * Linhas do resumo da customização. Vazio = o usuário pulou.
     *
     * @var list<array{0: string, 1: string}>
     */
    private array $resumo = [];

    /** Falso quando o banco escolhido não respondeu: migrar sem conexão só empilha erro. */
    private bool $bancoAcessivel = true;

    public function handle(): int
    {
        $this->components->info('Instalando o starter-kit-easy...');

        /*
         * Antes do prepararEnv(): depois dele o arquivo existe sempre, e o gate
         * "este projeto está nascendo" ficaria sempre falso.
         */
        $envJaExistia = File::exists(base_path('.env'));

        $this->prepararEnv();
        $this->customizar($envJaExistia);
        $this->gerarAppKey();
        $this->prepararBancoSqlite();
        $this->conferirConexao();

        if ($this->bancoAcessivel) {
            $this->migrar();
        }

        if ($this->bancoAcessivel && ! $this->option('no-seed')) {
            $this->semear();
            $this->formatarCodigoGerado();
        }

        $this->publicarAssets();

        if (! $this->option('no-npm')) {
            $this->construirFrontend();
        }

        $this->banner();
        $this->resumoDaCustomizacao();
        $this->oferecerTestes();
        $this->oferecerEstrela();

        return self::SUCCESS;
    }

    /**
     * As perguntas — só num projeto nascendo, ou numa reinstalação explícita.
     *
     * `--force` já significa "recria o banco do zero", então quem o passa está
     * reinstalando de propósito e merece as perguntas de volta. Um `kit:install`
     * seco num projeto em uso não pergunta nada: reescrever o .env de alguém que
     * já configurou o projeto seria destruir trabalho.
     */
    private function customizar(bool $envJaExistia): void
    {
        if ($envJaExistia && ! $this->option('force')) {
            return;
        }

        $customizador = new CustomizadorDaInstalacao;
        $respostas    = $customizador->perguntar($this, $this->input->isInteractive());

        if ($respostas === null) {
            return;
        }

        $this->resumo = $customizador->aplicar($respostas);
    }

    /**
     * Confere o banco escolhido antes de migrar.
     *
     * Postgres e MySQL dependem de um serviço que pode não estar de pé — no caso
     * do Postgres esse é o caso NORMAL logo depois da instalação, porque o
     * container ainda não subiu. Sem esta conferência, `migrate` e `db:seed`
     * falhariam em cascata e o usuário receberia duas stack traces de PDO
     * dizendo a mesma coisa.
     */
    private function conferirConexao(): void
    {
        $driver = (string) config('database.default');

        if ($driver === 'sqlite') {
            return;
        }

        try {
            DB::connection()->getPdo();
        } catch (Throwable $e) {
            $this->bancoAcessivel = false;

            $this->avisos[] = $driver === 'pgsql'
                ? 'O PostgreSQL não respondeu — pulei migrations e seeders. Suba o serviço e rode: docker compose up -d && php artisan migrate --seed'
                : 'O MySQL não respondeu — pulei migrations e seeders. Confira as credenciais no .env, crie o banco `'.config('database.connections.'.$driver.'.database').'` e rode: php artisan migrate --seed';

            Log::warning(
                '[KitInstall@conferirConexao] Banco inacessível, migrations puladas | driver: '.$driver,
                ['driver' => $driver, 'exception' => $e],
            );
        }
    }

    protected function prepararEnv(): void
    {
        if (File::exists(base_path('.env'))) {
            return;
        }

        File::copy(base_path('.env.example'), base_path('.env'));
        $this->components->task('Criando .env', fn (): bool => true);
    }

    protected function gerarAppKey(): void
    {
        if (filled(config('app.key'))) {
            return;
        }

        $this->callSilently('key:generate', ['--force' => true]);
        $this->components->task('Gerando APP_KEY', fn (): bool => true);
    }

    /**
     * SQLite é o default do kit justamente para o create-project não depender
     * de serviço externo. Quem usa Postgres/Docker já trocou DB_CONNECTION.
     */
    protected function prepararBancoSqlite(): void
    {
        if (config('database.default') !== 'sqlite') {
            return;
        }

        $caminho = config('database.connections.sqlite.database');

        if ($caminho === ':memory:') {
            return;
        }

        if ($this->option('force') && File::exists($caminho)) {
            File::delete($caminho);
        }

        if (! File::exists($caminho)) {
            File::ensureDirectoryExists(dirname($caminho));
            File::put($caminho, '');
            $this->components->task('Criando banco SQLite', fn (): bool => true);
        }
    }

    protected function migrar(): void
    {
        $this->components->task('Rodando migrations', function (): bool {
            $codigo = $this->callSilently('migrate', [
                '--graceful' => true,
                '--force'    => true,
            ]);

            if ($codigo !== self::SUCCESS) {
                $this->avisos[] = 'As migrations não completaram. Rode: php artisan migrate';
            }

            return $codigo === self::SUCCESS;
        });
    }

    protected function semear(): void
    {
        $this->components->task('Populando papéis, permissões e usuário inicial', function (): bool {
            $codigo = $this->callSilently('db:seed', ['--force' => true]);

            if ($codigo !== self::SUCCESS) {
                $this->avisos[] = 'Os seeders não completaram. Rode: php artisan db:seed';
            }

            return $codigo === self::SUCCESS;
        });
    }

    /**
     * Formata o que os geradores cuspiram.
     *
     * O `shield:generate` (chamado pelo ShieldPermissionsSeeder) escreve as
     * policies com o estilo dele, não com o do projeto — e aí `composer test`
     * falha no Pint logo na primeira execução de um projeto recém-criado.
     *
     * Pint é require-dev: numa instalação `--no-dev` ele não existe, e aí não
     * há o que formatar (nem `composer test` para rodar).
     */
    protected function formatarCodigoGerado(): void
    {
        $pint = base_path('vendor/bin/pint');

        if (! File::exists($pint)) {
            return;
        }

        $this->components->task('Formatando o código gerado', function () use ($pint): bool {
            $processo = new Process([PHP_BINARY, $pint, '--quiet', 'app/Policies'], base_path(), timeout: 300);
            $processo->run();

            return $processo->isSuccessful();
        });
    }

    protected function publicarAssets(): void
    {
        $this->components->task('Publicando assets do Filament', function (): bool {
            $this->callSilently('filament:assets');
            $this->callSilently('storage:link');

            return true;
        });
    }

    /**
     * npm é opcional: quem só vai rodar a API/painel sem tocar em CSS pode
     * instalar sem Node. Sem build, o Filament ainda funciona com os assets
     * publicados acima; só os temas customizados via Vite ficam pendentes.
     */
    protected function construirFrontend(): void
    {
        $npm = (new ExecutableFinder)->find('npm');

        if ($npm === null) {
            $this->avisos[] = 'npm não encontrado — pulei os assets. Depois rode: npm install && npm run build';

            return;
        }

        foreach ([['install'], ['run', 'build']] as $argumentos) {
            $rotulo = 'npm '.implode(' ', $argumentos);

            $this->components->task($rotulo, function () use ($npm, $argumentos, $rotulo): bool {
                $processo = new Process([$npm, ...$argumentos], base_path(), timeout: 900);
                $processo->run();

                if (! $processo->isSuccessful()) {
                    $this->avisos[] = "`{$rotulo}` falhou — rode manualmente para ver o erro.";
                }

                return $processo->isSuccessful();
            });
        }
    }

    protected function banner(): void
    {
        $url = rtrim((string) config('app.url'), '/');

        $this->newLine();
        $this->components->info('Pronto! O projeto está instalado.');

        $this->components->bulletList([
            "Negócio:        {$url}/app",
            "Administração:  {$url}/admin",
            "Infraestrutura: {$url}/infra",
        ]);

        note(
            'Login inicial: '.config('kit.admin.email')
            .' / '.config('kit.admin.password')
            ."\nTroque a senha antes de expor o ambiente."
        );

        $this->components->bulletList([
            'Suba o servidor com: composer dev',
            'Serviços opcionais (Postgres, Redis, IA local): docker compose up -d',
        ]);

        foreach ($this->avisos as $aviso) {
            $this->components->warn($aviso);
        }

        $this->newLine();

        Log::info(
            '[KitInstall@banner] Instalação concluída | avisos: '.count($this->avisos),
            ['avisos' => $this->avisos, 'customizado' => $this->resumo !== []],
        );
    }

    /**
     * O que foi customizado — e o que continua manual.
     *
     * A segunda metade é o que fecha a lista de "Personalize seu projeto" do
     * README: sete itens que são código ou dado de tela e não cabem num prompt.
     * Se este resumo encolher, eles deixam de ser descobertos.
     */
    private function resumoDaCustomizacao(): void
    {
        if ($this->resumo === []) {
            return;
        }

        $this->components->info('O que foi customizado nesta instalação:');

        foreach ($this->resumo as [$item, $valor]) {
            $this->components->twoColumnDetail("<fg=gray>{$item}</>", $valor);
        }

        $this->newLine();
        $this->components->info('O que continua sendo ajustado à mão:');
        $this->components->bulletList(CustomizadorDaInstalacao::itensManuais());
        $this->newLine();
    }

    /**
     * Oferece rodar a suíte do kit.
     *
     * Seguro por construção: o `phpunit.xml` fixa `DB_CONNECTION=sqlite` e
     * `DB_DATABASE=:memory:`, então a suíte não toca o banco recém-instalado nem
     * depende do Postgres estar de pé.
     */
    private function oferecerTestes(): void
    {
        if ($this->resumo === [] || ! $this->input->isInteractive()) {
            return;
        }

        if (! confirm('Rodar os testes do kit agora?', default: false)) {
            note('Quando quiser conferir a fundação: composer test:kit');

            return;
        }

        /*
         * Seleção por SUÍTE, e não `--group=kit`.
         *
         * O `pest-plugin-browser` sobe o Playwright na COLETA, ao parsear
         * qualquer arquivo com `visit()` — antes de qualquer filtro de grupo ser
         * consultado (`UsesBrowserTestCaseMethodFilter.php:57-60`). Num projeto
         * recém-instalado os browsers do Playwright não foram baixados, e
         * `--group=kit` morreria em `PlaywrightNotInstalledException` sem rodar um
         * único teste. É a mesma correção que o CI do kit já carrega.
         */
        $processo = new Process(
            [PHP_BINARY, 'artisan', 'test', '--testsuite=Kit,Tenancy'],
            base_path(),
            timeout: 900,
        );

        $processo->run(fn (string $tipo, string $saida) => $this->output->write($saida));
    }

    /**
     * O convite da estrela, no modelo do `Thanks` do Pest.
     *
     * O endereço é impresso SEMPRE, inclusive sem terminal: quem lê o output de
     * uma instalação automatizada também merece saber onde o kit mora. A
     * pergunta é que só existe com terminal, e `--no-support` a desliga.
     */
    private function oferecerEstrela(): void
    {
        $this->components->twoColumnDetail('<fg=gray>Repositório do kit</>', self::REPOSITORIO);

        if ($this->option('no-support') || ! $this->input->isInteractive()) {
            return;
        }

        if (! confirm('Gostou? Dar uma estrela ao starter-kit no GitHub?', default: false)) {
            return;
        }

        // ponytail: `exec` do SO é o mesmo caminho do Pest; falha silenciosa em
        // servidor sem ambiente gráfico é aceitável — o endereço já foi impresso.
        match (PHP_OS_FAMILY) {
            'Darwin'  => exec('open '.self::REPOSITORIO),
            'Windows' => exec('start '.self::REPOSITORIO),
            'Linux'   => exec('xdg-open '.self::REPOSITORIO),
            default   => null,
        };
    }
}
