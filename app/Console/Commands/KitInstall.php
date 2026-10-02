<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Settings\ConfiguracoesDoKit;
use App\Support\AdministradorDaInstalacao;
use App\Support\BancoSqlite;
use App\Support\CustomizadorDaInstalacao;
use App\Support\HostLocal;
use App\Support\SenhaDoAdministrador;
use App\Support\VinculoDoSnyk;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
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
 * idempotentes, e um passo idempotente que falhar vira aviso com a instrução
 * de como refazer. Uma falha de INFRAESTRUTURA interrompe: com `--force`, um
 * SQLite preso por outro processo faz `BancoSqlite::recriar()` lançar
 * `RuntimeException` sem captura, e a instalação para ali.
 *
 * Num projeto NASCENDO ele também pergunta: nome, banco, credenciais do admin,
 * cor e multi-organização, no mesmo lugar em que o `laravel new` faz as dele.
 *
 * As perguntas dependem de o Composer conseguir repassar o terminal ao script
 * (`ProcessExecutor::executeTty`), e ele nem sempre consegue — sistema, console e
 * forma de invocação mudam o resultado. Sem terminal, nada é perguntado, a
 * instalação é a de sempre, e o comando avisa como refazê-la com as perguntas.
 * `temTerminal()` explica por que a detecção não é `isInteractive()` sozinho.
 */
class KitInstall extends Command
{
    protected $signature = 'kit:install
        {--no-npm : Pula a instalação e o build dos assets front-end}
        {--no-seed : Não popula o banco (papéis, usuário inicial, agentes de IA)}
        {--force : Recria o banco SQLite do zero (APAGA os dados existentes) e refaz as cinco perguntas}
        {--custom : Refaz só o que não toca o banco — nome e cor — e sai. Não apaga nada}
        {--no-custom : Pula as perguntas de customização e instala com os padrões}
        {--no-support : Pula o convite para dar uma estrela ao kit no GitHub}
        {--create-project : Uso interno do post-create-project-cmd: apaga o que so serve ao repositorio do kit}';

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

    /**
     * Verdadeiro só quando `semear()` REALMENTE rodou nesta execução (banco acessível e sem
     * `--no-seed`) — o desfecho de fato, não uma suposição.
     *
     * Fonte única (RD3-01/RD3-03): `corrigirResumoDaSenha()` e `mensagemDoBanner()` decidiam essa
     * mesma pergunta por caminhos diferentes — uma nunca a consultava (sempre assumia que não
     * rodou) e a outra recebia a expressão repetida por parâmetro. Gravado aqui, uma vez, no exato
     * ponto em que `handle()` decide chamar `semear()`.
     */
    private bool $semeado = false;

    /**
     * Verdadeiro quando `garantirSenhaDoAdministrador()` encontrou um administrador JÁ
     * existente antes do `db:seed` (DV-02): nesse caso nenhuma senha é gerada nem impressa —
     * o `UsuarioAdminSeeder` não atualiza credencial de quem já existe, e a senha que o kit
     * geraria não valeria.
     */
    private bool $adminJaExistia = false;

    public function handle(): int
    {
        $this->components->info('Instalando o starter-kit-easy...');

        $this->prepararEnv();

        /*
         * O boot aplicou o banco VELHO à config, e é dele que o `--force` vai se livrar. Sem
         * devolver a config ao `.env` ANTES das perguntas (o customizador realinha em memória o
         * que respondem), a migration de settings semearia o banco novo com o que o banco velho
         * dizia. Ver `ConfiguracoesDoKit::devolverConfigAoEnv()`.
         */
        if ($this->option('force')) {
            ConfiguracoesDoKit::devolverConfigAoEnv();
        }

        /*
         * `--custom` sai daqui, e é o ponto: ele existe para quem JÁ instalou.
         *
         * O `--force` refaz as cinco perguntas, mas apaga o SQLite antes (ver
         * `prepararBancoSqlite()`) — inócuo no minuto seguinte à instalação, destrutivo depois.
         * Este ramo cobre o "depois" sem tocar em banco, seeder nem asset.
         */
        if ($this->option('custom')) {
            return $this->customizarSemBanco();
        }

        $this->customizar();
        $this->gerarAppKey();
        $this->prepararBancoSqlite();
        $this->conferirConexao();

        if ($this->bancoAcessivel) {
            $this->migrar();
        }

        if ($this->bancoAcessivel && ! $this->option('no-seed')) {
            $this->semeado = true;
            $this->semear();
            $this->formatarCodigoGerado();
        }

        /*
         * O desfecho (semeou? gerou?) só é conhecido AGORA — o resumo foi montado antes, em
         * `customizar()` (linha 108), sem saber se `--no-seed` estava ligado ou se o banco ia
         * responder. Corrige a linha da senha antes dela ser impressa (RD2-05/RD3-01/RD3-03).
         */
        $this->corrigirResumoDaSenha();

        $this->publicarAssets();

        if (! $this->option('no-npm')) {
            $this->construirFrontend();
        }

        if ($this->option('create-project')) {
            $this->desvincularDoSnyk();
        }

        $this->oferecerHostLocal();

        $this->banner();
        $this->resumoDaCustomizacao();
        $this->oferecerTestes();
        $this->oferecerEstrela();

        return self::SUCCESS;
    }

    /**
     * Há uma pessoa do outro lado capaz de responder?
     *
     * É a MESMA expressão que o Laravel usa para decidir se os prompts são
     * interativos (`ConfiguresPrompts::configurePrompts()`), e cada termo dela
     * existe por um motivo:
     *
     *   - `isInteractive()` sozinho NÃO basta. No Windows o Symfony não tem
     *     `posix_isatty` para consultar, então ele deixa a entrada como
     *     interativa mesmo quando não há terminal nenhum. Foi o que fez a
     *     instalação sem TTY "responder" as cinco perguntas com os defaults e
     *     reescrever o .env — trocando inclusive o APP_NAME pelo nome da pasta.
     *   - `stream_isatty(STDIN)` sozinho também não: sob `$this->artisan()` o
     *     STDIN não é tty, e o customizador se pularia dentro da própria suíte.
     *   - `runningUnitTests()` é o que reconcilia os dois.
     *
     * A propriedade equivalente do `Laravel\Prompts\Prompt` é `protected`, então
     * a expressão é repetida aqui em vez de lida de lá.
     */
    private function temTerminal(): bool
    {
        return ($this->input->isInteractive() && defined('STDIN') && stream_isatty(STDIN))
            || $this->laravel->runningUnitTests();
    }

    /**
     * O caminho do `--custom`: reescreve nome e cor, e diz o que ele NÃO faz.
     *
     * O aviso final não é rodapé: é o que impede alguém de concluir que "refazer as perguntas"
     * cobre as cinco. As outras três exigem recriar o banco, cada uma por um motivo diferente —
     * e o das credenciais é o menos obvio, porque o `UsuarioAdminSeeder` faz `firstOrCreate` por
     * e-mail: mudar o endereço e semear de novo criaria um SEGUNDO `master_global`, com o
     * primeiro vivo e a senha antiga.
     */
    private function customizarSemBanco(): int
    {
        $customizador = new CustomizadorDaInstalacao;
        $respostas    = $customizador->perguntarSemBanco();

        if ($respostas === null) {
            $this->components->info('Nada alterado.');

            return self::SUCCESS;
        }

        $this->components->twoColumnDetail('<fg=gray>O que mudou</>', '');

        foreach ($customizador->aplicarSemBanco($respostas) as [$rotulo, $valor]) {
            $this->components->twoColumnDetail($rotulo, $valor);
        }

        $this->newLine();
        $this->components->warn('O que este comando NÃO faz, e por onde vai cada um:');
        $this->components->bulletList([
            'Banco de dados — trocar depois do migrate é outra instalação: `php artisan kit:install --force` (APAGA os dados).',
            'Multi-organização — as tabelas de permissão só nascem com a coluna de contexto antes do migrate: rode `php artisan kit:tenancy` (recria o banco).',
            'Credenciais do administrador — `php artisan kit:admin` (ou a tela de perfil do painel). Semear de novo NÃO aplica: o seeder garante que exista administrador, não que ele espelhe o .env.',
        ]);

        $this->components->info('Rode `php artisan config:clear` se estiver com config em cache.');

        return self::SUCCESS;
    }

    /**
     * As perguntas — só num projeto nascendo, ou numa reinstalação explícita.
     *
     * A decisão inteira vive em `CustomizadorDaInstalacao::devePerguntar()`, com
     * o porquê de cada sinal. Aqui só se colhe o que é do comando.
     */
    private function customizar(): void
    {
        $customizador = new CustomizadorDaInstalacao;
        $respostas    = $customizador->perguntar($this, $this->temTerminal());

        if ($respostas === null) {
            $this->avisarSePerdeuAsPerguntas();

            return;
        }

        $this->resumo = $customizador->aplicar($respostas);
    }

    /**
     * Projeto novo + sem terminal = as perguntas passaram batido.
     *
     * Acontece de verdade, e não é hipótese: o Composer só repassa o terminal ao
     * script quando ele mesmo consegue (`ProcessExecutor::executeTty`), e em
     * várias combinações de sistema e console isso não acontece — o `artisan`
     * roda com a entrada fechada e todo prompt é pulado. Sem esta mensagem o
     * usuário conclui que a feature não existe; com ela, sabe o comando que
     * refaz a instalação **com** as perguntas.
     */
    private function avisarSePerdeuAsPerguntas(): void
    {
        if ($this->option('no-custom') || $this->temTerminal() || filled(config('app.key'))) {
            return;
        }

        $this->avisos[] = 'Instalado com os padrões: este terminal não aceitou perguntas '
            .'(no Windows o Composer nunca repassa o terminal ao script). Para escolher as cinco '
            .'AGORA, com o banco ainda vazio: php artisan kit:install --force (recria o banco — '
            .'inócuo neste instante, destrutivo depois). Só nome e cor, sem tocar no banco, a '
            .'qualquer momento: php artisan kit:install --custom';
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

            /*
             * (DV-01/DV-06) A MESMA instrução que a nota do banner e o resumo imprimem
             * (`instrucaoBancoNaoPopulado()`), nunca um literal divergente — e o Postgres
             * sobre Docker precisa do `--force-recreate`: `env_file` só é lido na CRIAÇÃO
             * do container, e um `up -d` sobre container já criado não relê o `.env` que
             * o usuário vai editar com a KIT_ADMIN_PASSWORD.
             */
            $this->avisos[] = $driver === 'pgsql'
                ? 'O PostgreSQL não respondeu — pulei migrations e seeders. Suba o serviço relendo o .env (o env_file só vale na criação do container): docker compose up -d --force-recreate. Depois, '.$this->instrucaoBancoNaoPopulado()
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

        /*
         * `BancoSqlite` desconecta antes de apagar e FALHA se o arquivo sobreviver — no Windows,
         * arquivo aberto não se apaga, e o boot do kit já abre o SQLite. Sem isso o `--force` migrava
         * o banco velho e o resumo mentia sobre o login inicial. Ver o docblock da classe.
         */
        if ($this->option('force')) {
            BancoSqlite::recriar($caminho);
            $criado = true;
        } else {
            $criado = BancoSqlite::criarSeFaltar($caminho);
        }

        if ($criado) {
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

    /**
     * A senha gerada nesta execucao, ou `null` quando o `.env` ja trazia uma.
     *
     * Guardada para ser impressa UMA VEZ no fim. Quando o usuario definiu a propria, fica `null`
     * e nada e impresso — imprimir a senha de alguem no terminal e no log de CI, sem que essa
     * pessoa tenha pedido, e vaza-la.
     */
    protected ?string $senhaGerada = null;

    /**
     * Garante uma senha de administrador utilizavel, ANTES de semear.
     *
     * A ordem e requisito, nao estilo: e o `UsuarioAdminSeeder` que grava a senha no usuario, a
     * partir de `config('kit.admin.password')`. Gerar depois dele criaria o administrador com o
     * valor antigo e gravaria no `.env` um valor que nao entra em lugar nenhum — a instalacao
     * imprimiria uma senha que nao funciona.
     */
    protected function garantirSenhaDoAdministrador(): void
    {
        /*
         * (DV-02) Administrador já existente (`kit:install` rodado de novo sobre um banco
         * populado): o `UsuarioAdminSeeder` sai cedo e NÃO toca na senha — gerar uma nova
         * gravaria no `.env` um valor morto e o banner imprimiria credencial que não
         * autentica. Nesse caso nada é gerado nem gravado, e o banner diz a verdade: a
         * senha NÃO mudou.
         */
        $administradores = AdministradorDaInstalacao::todos();

        if ($administradores->isNotEmpty()) {
            $this->adminJaExistia = true;
            $this->senhaGerada    = null;

            /*
             * O resquício anterior à geração de senha (v0.39.1-): administrador semeado com
             * o padrão publicado. O `.env` vazio não é erro — é o estado que sobrou — mas a
             * credencial default ainda ativa merece o aviso explícito.
             */
            if ($administradores->contains(
                fn (User $admin): bool => Hash::check(SenhaDoAdministrador::PADRAO_PUBLICADO, (string) $admin->password),
            )) {
                $this->avisos[] = 'Um administrador já existe com a senha padrão publicada — defina '.SenhaDoAdministrador::CHAVE.' no .env e troque com: php artisan kit:admin';
            }

            return;
        }

        $this->senhaGerada = SenhaDoAdministrador::garantirNoEnv(base_path('.env'));

        if ($this->senhaGerada === null) {
            return;
        }

        $this->components->task('Gerando senha do administrador', fn (): bool => true);
    }

    protected function semear(): void
    {
        $this->garantirSenhaDoAdministrador();

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

    /**
     * Oferece o domínio local — a última pergunta, e de propósito ANTES do banner.
     *
     * "No final" aqui é o final do TRABALHO, não a última linha do `handle()`: o
     * `banner()` imprime as URLs de acesso a partir de `config('app.url')`, e
     * oferecer depois dele deixaria na tela `http://localhost:8000/app` logo
     * abaixo do endereço que a pessoa acabou de escolher — a feature funcionando
     * e parecendo não ter funcionado.
     *
     * Nada aqui aborta a instalação: o que não der certo vira aviso, impresso
     * pelo próprio `banner()` junto com os demais. Por isso a etapa também não
     * depende de flag nenhuma — `--force` apaga o banco, e amarrar o cadastro de
     * um DNS local a ele seria pedir para apagar o banco para ganhar um domínio.
     */
    private function oferecerHostLocal(): void
    {
        $aviso = (new HostLocal(base_path()))->oferecer($this->temTerminal());

        if ($aviso !== null) {
            $this->avisos[] = $aviso;
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

        /*
         * A SENHA SO APARECE QUANDO ESTA EXECUCAO A GEROU.
         *
         * Ate a v0.39.1 esta linha imprimia `config('kit.admin.password')` sempre — inclusive a
         * senha que o usuario tinha escolhido no `.env`, que ninguem pediu para ver no terminal
         * nem no log de CI. Agora: gerada, aparece uma vez (e e a unica chance de anotar);
         * escolhida por quem instala, nao aparece; e se a semeadura nao rodou (RD2-05), nenhuma
         * das duas promessas e feita — ver `mensagemDoBanner()`.
         *
         * `$this->semeado` (RD3-01/RD3-03), nunca a expressão recalculada aqui: é a MESMA
         * propriedade que `corrigirResumoDaSenha()` consulta, gravada uma vez em `handle()` no
         * momento exato em que `semear()` é (ou não) chamado.
         */
        note($this->mensagemDoBanner($this->semeado));

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
     * A nota do banner sobre a senha do administrador (RD2-05).
     *
     * Três desfechos, e só três — o `$semeado` é o que faltava para diferenciar o terceiro do
     * segundo, porque `$this->senhaGerada === null` sozinho não distingue "o `.env` já tinha uma
     * senha utilizável, e o seeder rodou com ela" de "nada rodou, e não existe administrador
     * nenhum para valer essa senha":
     *
     *   1. `$this->senhaGerada` preenchido — a senha foi gerada NESTA execução. Aparece uma vez,
     *      porque é a única chance de anotar (comportamento de hoje, preservado).
     *   2. `$this->senhaGerada === null` e `$semeado === true` — o `.env` já tinha uma senha
     *      utilizável, e o `db:seed` rodou com ela: nomear `KIT_ADMIN_PASSWORD` como a que vale é
     *      verdade (comportamento de hoje, preservado).
     *   3. `$this->senhaGerada === null` e `$semeado === false` — `--no-seed` ou banco
     *      inacessível: `semear()` nunca rodou, então NENHUM administrador foi criado. Dizer "a
     *      senha é a que você definiu" prometeria uma credencial para um usuário que não existe.
     */
    private function mensagemDoBanner(bool $semeado): string
    {
        if ($this->senhaGerada !== null) {
            return 'Login inicial: '.config('kit.admin.email')
                .' / '.$this->senhaGerada
                ."\nEsta senha foi gerada agora e NAO sera mostrada de novo — anote."
                ."\nPara trocar: php artisan kit:admin";
        }

        if (! $semeado) {
            return 'Nenhum usuario foi criado nesta execucao (banco nao populado) — nenhuma senha para mostrar.'
                ."\nPara criar o administrador, ".$this->instrucaoBancoNaoPopulado().'.';
        }

        /*
         * (DV-02) Quarto desfecho: `semear()` rodou mas já existia administrador — o seeder
         * não tocou na credencial, então nem "gerada agora" nem "a que você definiu" são
         * verdades. A senha que continua valendo é a que o administrador já tinha.
         */
        if ($this->adminJaExistia) {
            return 'Login: '.config('kit.admin.email').' — um administrador ja existia; a senha NAO foi alterada nesta execucao.'
                ."\nPara trocar: php artisan kit:admin";
        }

        return 'Login inicial: '.config('kit.admin.email')
            ."\nA senha e a que voce definiu em KIT_ADMIN_PASSWORD.";
    }

    /**
     * A ÚNICA instrução para o caso "banco não populado" (`--no-seed` ou banco inacessível) —
     * banner e resumo (RD3-01) davam conselhos DIFERENTES para a mesma situação, e nenhum
     * funcionava: o banner mandava `php artisan kit:install --force` (apaga o banco à toa, para
     * quem só quer semear) ou `php artisan db:seed` direto — que, com `KIT_ADMIN_PASSWORD` vazio,
     * grava o administrador com `SenhaDoAdministrador::PADRAO_PUBLICADO` (o fallback de
     * `config/kit.php`); o resumo mandava `php artisan kit:admin`, que FALHA sem nenhum
     * administrador para atualizar.
     *
     * Fonte única (RD3-12): `mensagemDoBanner()` e `corrigirResumoDaSenha()` consultam este
     * método, nunca um literal duplicado em cada um.
     */
    private function instrucaoBancoNaoPopulado(): string
    {
        /*
         * `migrate --seed`, nunca `db:seed` puro (DV-01): a situação que dispara esta nota
         * é "banco não populado", e em banco recém-criado o `db:seed` falha com
         * `no such table: roles` — a migration nunca rodou. Com banco já migrado, o
         * `migrate` é no-op e o `--seed` semeia: um comando só serve aos dois casos.
         */
        return 'defina '.SenhaDoAdministrador::CHAVE.' no .env e só então rode: php artisan migrate --seed';
    }

    /**
     * Corrige a linha "Senha do administrador" do resumo quando ela não bate com o desfecho REAL
     * de `semear()` (RD2-05/RD3-01/RD3-03).
     *
     * `CustomizadorDaInstalacao::aplicar()` monta o resumo em `customizar()`, ANTES de o
     * `kit:install` saber se `semear()` vai rodar — a linha promete
     * `CustomizadorDaInstalacao::RESUMO_SENHA_GERADA` sempre que, NAQUELE momento, nem a senha
     * digitada nem a do `.env` de destino eram utilizáveis. Dois desfechos reais podem contradizer
     * essa promessa, e `$this->semeado` (gravado por `handle()`, o ÚNICO que sabe) é o que separa
     * um do outro:
     *
     *   - **`$this->semeado === false`** (RD3-01): `--no-seed` ou banco inacessível — `semear()`
     *     nunca rodou, nenhum administrador existe. Prometer "gerada e impressa" seria falso; a
     *     linha vira a MESMA instrução que `mensagemDoBanner()` imprime (uma fonte só).
     *   - **`$this->semeado === true`** e `$this->senhaGerada === null` (RD3-03): `semear()`
     *     RODOU, mas nada foi gerado porque já havia senha utilizável — só que vinda do AMBIENTE
     *     (`config('kit.admin.password')`, uma variável exportada), não do ARQUIVO que
     *     `CustomizadorDaInstalacao::aplicar()` lia. A linha não pode negar que o banco foi
     *     populado: passa a dizer o mesmo que diria se a origem tivesse sido o arquivo.
     *
     * Chamado depois de `conferirConexao()` e da decisão de `semear()`, antes de
     * `resumoDaCustomizacao()`.
     */
    private function corrigirResumoDaSenha(): void
    {
        if ($this->senhaGerada !== null) {
            return;
        }

        foreach ($this->resumo as $indice => [$item, $valor]) {
            if ($item !== 'Senha do administrador') {
                continue;
            }

            // (DV-02) Com administrador pré-existente TODA promessa é falsa — não só a de
            // "gerada". Sem ele, só a promessa de geração precisa de correção.
            if (! $this->adminJaExistia && ! str_contains($valor, CustomizadorDaInstalacao::RESUMO_SENHA_GERADA)) {
                continue;
            }

            $this->resumo[$indice][1] = match (true) {
                /*
                 * (DV-02) Administrador pré-existente: qualquer promessa do resumo —
                 * "gerada", "a que você digitou", "a que você já definiu" — é falsa, porque
                 * o seeder não tocou na credencial dele.
                 */
                $this->adminJaExistia => 'inalterada — um administrador já existia antes da instalação',
                $this->semeado        => 'a que você já definiu em KIT_ADMIN_PASSWORD',
                default               => 'nenhuma foi definida — o banco não foi populado nesta execução; '.$this->instrucaoBancoNaoPopulado(),
            };
        }
    }

    /**
     * O que foi customizado — e o que continua manual.
     *
     * A segunda metade é o que fecha a lista de "Personalize seu projeto" do
     * README: sete itens que são código ou dado de tela e não cabem num prompt.
     * Se este resumo encolher, eles deixam de ser descobertos.
     */
    /**
     * Apaga o vínculo com o Snyk do kit — que é do kit, e não de quem instala.
     *
     * O porquê de cada arquivo está em `App\Support\VinculoDoSnyk`. O que importa AQUI é
     * quando isto roda, e a resposta é: só quando o `post-create-project-cmd` pede.
     *
     * Este comando roda nos DOIS lados — no `composer create-project` de quem instala e, à
     * mão, dentro do repositório do kit: o job `instalacao` do CI faz exatamente isso, e
     * `composer setup` é o que se roda depois de clonar. Apagar sempre destruiria os arquivos
     * da própria fonte e sujaria a árvore no CI.
     *
     * Quem sabe a diferença é o CHAMADOR, não o comando. Então o `post-create-project-cmd`
     * passa `--create-project`, e mais ninguém passa — nem o `setup`, que é de clone. Sem
     * heurística de `.git` ou de nome de pasta: essas erram, um flag não. Há caso de teste
     * guardando os dois scripts do `composer.json`, porque escrever o flag no `setup` por
     * engano é fácil e o estrago é exatamente o que este desenho evita.
     */
    private function desvincularDoSnyk(): void
    {
        $apagados = VinculoDoSnyk::remover(base_path());

        if ($apagados === []) {
            return;
        }

        $this->components->task(
            'Removendo o vínculo com o Snyk do kit ('.count($apagados).' arquivo(s))',
            fn (): bool => true,
        );
    }

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
        if ($this->resumo === [] || ! $this->temTerminal()) {
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

        if ($this->option('no-support') || ! $this->temTerminal()) {
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
