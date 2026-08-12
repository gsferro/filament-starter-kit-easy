<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

use function Laravel\Prompts\note;

/**
 * Deixa o projeto pronto para uso em um comando.
 *
 * Roda automaticamente no `composer create-project` (post-create-project-cmd)
 * e pode ser reexecutado à mão depois de um `git clone` — todos os passos são
 * idempotentes. Nenhum passo aborta a instalação: o que falhar vira aviso com
 * a instrução de como refazer.
 */
class KitInstall extends Command
{
    protected $signature = 'kit:install
        {--no-npm : Pula a instalação e o build dos assets front-end}
        {--no-seed : Não popula o banco (papéis, usuário inicial, agentes de IA)}
        {--force : Recria o banco SQLite do zero (apaga os dados existentes)}';

    protected $description = 'Instala o starter-kit: banco, migrations, seeders, permissões e assets';

    /** @var list<string> */
    protected array $avisos = [];

    public function handle(): int
    {
        $this->components->info('Instalando o starter-kit-easy...');

        $this->prepararEnv();
        $this->gerarAppKey();
        $this->prepararBancoSqlite();
        $this->migrar();

        if (! $this->option('no-seed')) {
            $this->semear();
        }

        $this->publicarAssets();

        if (! $this->option('no-npm')) {
            $this->construirFrontend();
        }

        $this->banner();

        return self::SUCCESS;
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
    }
}
