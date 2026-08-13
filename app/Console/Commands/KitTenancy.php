<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;
use Throwable;

use function Laravel\Prompts\note;

/**
 * Liga o modo multi-tenant do kit.
 *
 * O kit nasce single-tenant e continua assim para quem não rodar este comando.
 * Ele faz três coisas que precisam acontecer JUNTAS, senão config e schema
 * ficam incoerentes:
 *
 *   1. `KIT_TENANCY=true` no .env  → o painel /app vira /app/{tenant}
 *   2. `permission.teams = true`   → papéis passam a valer por tenant
 *   3. `migrate:fresh --seed`      → as tabelas de permissão nascem com a
 *                                    coluna de tenant
 *
 * O passo 3 é o motivo de o comando ser DESTRUTIVO: a migration do spatie cria
 * as colunas de team condicionalmente, lendo a config em tempo de execução
 * (create_permission_tables.php). Ligar a flag depois de migrar deixa a config
 * dizendo "teams" e o banco sem as colunas — falha silenciosa. Refazer
 * aditivamente exigiria recriar índices únicos, o que em SQLite significa
 * recriar a tabela.
 *
 * Por isso a recomendação é rodar no dia 1 do projeto. Quem já tem dados em
 * produção precisa fazer a migração à mão (ver wikis/arquitetura.md).
 */
class KitTenancy extends Command
{
    protected $signature = 'kit:tenancy
        {--demo : Cria também o cenário de demonstração (2 tenants, 3 usuários, projetos)}
        {--force : Confirma a recriação do banco sem perguntar}';

    protected $description = 'Liga o modo multi-tenant: painel /app por tenant, papéis por tenant';

    private string $git = '';

    public function handle(): int
    {
        $this->git = (new ExecutableFinder)->find('git') ?? '';

        if (config('kit.tenancy.enabled')) {
            $this->components->info('O modo multi-tenant já está ligado.');
            note("Para recriar o cenário de demonstração:\n\n  php artisan db:seed --class=Database\\\\Seeders\\\\DemoTenancySeeder");

            return self::SUCCESS;
        }

        if (! $this->preVoo()) {
            return self::FAILURE;
        }

        if (! $this->confirmarDestruicao()) {
            return self::FAILURE;
        }

        try {
            $this->ligarFlagNoEnv();
            $this->ligarPapeisPorTenant();
            $this->recriarBanco();

            if ($this->option('demo')) {
                $this->semearDemo();
            }
        } catch (Throwable $e) {
            $this->components->error('Falha ao ativar o modo multi-tenant: '.$e->getMessage());

            Log::channel('tenancy')->error(
                '[KitTenancy@handle] Falha ao ativar o modo multi-tenant',
                ['exception' => $e, 'demo' => (bool) $this->option('demo')],
            );

            return self::FAILURE;
        }

        Log::channel('tenancy')->info(
            '[KitTenancy@handle] Modo multi-tenant ativado | demo: '.($this->option('demo') ? 'sim' : 'não'),
            ['demo' => (bool) $this->option('demo')],
        );

        $this->banner();

        return self::SUCCESS;
    }

    /**
     * Exige git com árvore limpa — mesma garantia do `kit:update`: o comando
     * reescreve config e recria o banco, e sem um ponto de retorno não há como
     * desfazer.
     */
    private function preVoo(): bool
    {
        if ($this->git === '') {
            $this->components->error('git não encontrado no PATH.');

            return false;
        }

        if (! is_dir(base_path('.git'))) {
            $this->components->error('Este projeto não é um repositório git.');
            note(
                "Versione o projeto antes de ligar a tenancy — é o que torna a mudança reversível:\n\n"
                ."  git init\n"
                ."  git add -A\n"
                .'  git commit -m "estado atual antes de ligar o multi-tenancy"'
            );

            return false;
        }

        $sujos = trim($this->executarGit(['status', '--porcelain']));

        if ($sujos === '') {
            return true;
        }

        $this->components->error('Há alterações não commitadas na árvore de trabalho.');

        $lista = array_slice(explode("\n", $sujos), 0, 10);
        $this->line('  '.implode("\n  ", array_map(trim(...), $lista)));

        note(
            "Commite ou guarde o que está em andamento antes de continuar — este comando\n"
            ."reescreve config e RECRIA O BANCO:\n\n"
            .'  git add -A && git commit -m "antes de ligar o multi-tenancy"'
        );

        return false;
    }

    private function confirmarDestruicao(): bool
    {
        $this->components->warn('Este comando APAGA os dados do banco (migrate:fresh --seed).');
        note(
            "Ligar papéis por tenant exige que as tabelas de permissão nasçam com a\n"
            ."coluna de tenant — e elas só ganham essa coluna se a flag estiver ligada ANTES\n"
            .'do migrate. Por isso o banco é recriado, e não migrado aditivamente.'
        );

        if ($this->option('force')) {
            return true;
        }

        if (! $this->input->isInteractive()) {
            $this->components->error('Sem terminal interativo. Passe --force para confirmar a recriação do banco.');

            return false;
        }

        return $this->confirm('Recriar o banco e ligar o modo multi-tenant?', false);
    }

    private function ligarFlagNoEnv(): void
    {
        $this->substituirNoArquivo(
            base_path('.env'),
            '/^KIT_TENANCY=.*$/m',
            'KIT_TENANCY=true',
            "\nKIT_TENANCY=true\n",
        );

        $this->components->task('KIT_TENANCY=true no .env', fn (): bool => true);
    }

    /**
     * `permission.teams` liga o recorte por tenant no spatie E no Shield: o
     * `Utils::isTenancyEnabled()` do Shield lê exatamente esta chave.
     */
    private function ligarPapeisPorTenant(): void
    {
        $this->substituirNoArquivo(
            config_path('permission.php'),
            "/'teams'\s*=>\s*false/",
            "'teams' => true",
        );

        $this->substituirNoArquivo(
            config_path('filament-shield.php'),
            "/'tenant_model'\s*=>\s*null/",
            "'tenant_model' => \\App\\Models\\Tenant::class",
        );

        $this->components->task('papéis por tenant (permission.teams + Shield)', fn (): bool => true);
    }

    /**
     * Alinha a config JÁ CARREGADA com o que acabou de ser escrito em disco.
     *
     * Sem isto o comando falha de um jeito traiçoeiro: `config:clear` apaga o
     * arquivo de cache, mas NÃO recarrega a config em memória. O
     * `migrate:fresh` roda neste mesmo processo, lê `permission.teams` ainda
     * como `false` e cria as tabelas de permissão SEM as colunas de team. A
     * requisição seguinte — processo novo, config nova — consulta
     * `model_has_roles.team_id` e recebe "no such column".
     *
     * O `PermissionRegistrar` é singleton e lê `permission.teams` no
     * construtor, então precisa ser descartado para renascer sabendo de teams.
     * E o contexto global de papéis precisa ser fixado à mão, porque o
     * `KitServiceProvider::configureTenancy()` já rodou no boot, quando a flag
     * ainda estava desligada — sem ele, os seeders atribuem papel com
     * `team_id` nulo e estouram a constraint NOT NULL.
     */
    private function alinharConfigEmMemoria(): void
    {
        config([
            'kit.tenancy.enabled'          => true,
            'permission.teams'             => true,
            'filament-shield.tenant_model' => Tenant::class,
        ]);

        $this->laravel->forgetInstance(PermissionRegistrar::class);
        app(PermissionRegistrar::class)->setPermissionsTeamId(Tenant::CONTEXTO_GLOBAL);
    }

    private function recriarBanco(): void
    {
        // Limpa o cache em disco (para os próximos processos) e alinha a config
        // desta execução (para o migrate:fresh logo abaixo).
        $this->callSilently('config:clear');
        $this->alinharConfigEmMemoria();

        $this->components->info('Recriando o banco com as tabelas de permissão por tenant...');
        $this->call('migrate:fresh', ['--seed' => true, '--force' => true]);

        $this->conferirSchema();
    }

    /**
     * Confere o que o passo anterior prometeu. A ausência desta coluna é
     * justamente a falha silenciosa que o comando existe para evitar: o banco
     * fica de pé, o comando termina com sucesso, e o erro só aparece no
     * primeiro login.
     */
    private function conferirSchema(): void
    {
        $tabela = config('permission.table_names.model_has_roles', 'model_has_roles');
        $coluna = config('permission.column_names.team_foreign_key', 'team_id');

        if (Schema::hasColumn($tabela, $coluna)) {
            return;
        }

        throw new RuntimeException(
            "As tabelas de permissão nasceram sem a coluna `{$tabela}.{$coluna}`. "
            .'A config de teams não chegou à migration — rode `php artisan migrate:fresh --seed` '
            .'num processo novo para refazer.'
        );
    }

    private function semearDemo(): void
    {
        $this->call('db:seed', [
            '--class' => 'Database\\Seeders\\DemoTenancySeeder',
            '--force' => true,
        ]);
    }

    /**
     * Substituição pontual num arquivo — mesma abordagem do `kit:update` ao
     * marcar a versão: mexe só na linha alvo, preservando comentários e
     * qualquer chave que o usuário tenha acrescentado.
     */
    private function substituirNoArquivo(string $caminho, string $padrao, string $novo, ?string $fallback = null): void
    {
        if (! File::exists($caminho)) {
            return;
        }

        $conteudo = File::get($caminho);

        if (preg_match($padrao, $conteudo) === 1) {
            File::put($caminho, preg_replace($padrao, $novo, $conteudo, 1));

            return;
        }

        if ($fallback !== null) {
            File::append($caminho, $fallback);
        }
    }

    /** @param  list<string>  $args */
    private function executarGit(array $args): string
    {
        $processo = new Process([$this->git, ...$args], base_path());
        $processo->run();

        return $processo->getOutput();
    }

    private function banner(): void
    {
        $tenants = Tenant::query()->orderBy('id')->get(['nome', 'slug']);
        $plural  = mb_strtolower((string) config('kit.tenancy.label_plural', 'Organizações'));
        $slug    = (string) config('kit.tenancy.slug', 'organizacoes');

        $this->newLine();
        $this->components->info('Modo multi-tenant ligado.');

        $this->components->twoColumnDetail('<fg=gray>Painel de negócio</>', '/app/{slug}');
        $this->components->twoColumnDetail("<fg=gray>Cadastro de {$plural}</>", "/admin/{$slug}");

        foreach ($tenants as $tenant) {
            $this->components->twoColumnDetail("<fg=gray>{$tenant->nome}</>", "/app/{$tenant->slug}");
        }

        note(
            "Toda model do negócio que pertence a um tenant deve usar\n"
            ."`App\\Traits\\BelongsToTenant` — é ela que recorta as queries fora dos\n"
            ."resources. E no form, `->scopedUnique()` no lugar de `->unique()`.\n"
            .'Ver wikis/convencoes.md.'
        );

        if ($this->option('demo')) {
            note(
                "A demo é descartável. Para removê-la, apague:\n\n"
                ."  app/Models/Projeto.php\n"
                ."  app/Filament/App/Resources/Projetos/\n"
                ."  database/migrations/*_create_projetos_table.php\n"
                .'  database/seeders/DemoTenancySeeder.php'
            );
        }
    }
}
