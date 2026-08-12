<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\note;
use function Laravel\Prompts\select;
use function Laravel\Prompts\table;

/**
 * Pseudo-update do kit: compara o projeto com uma versão nova do starter-kit,
 * mostra o que mudou e aplica APENAS o que você aprovar, arquivo a arquivo.
 *
 * O desenho parte de uma premissa: depois do `create-project` o projeto é seu.
 * Um update que sobrescreve arquivos reescreveria justamente o que você
 * personalizou. Então aqui nada é aplicado sem aprovação, tudo acontece num
 * branch temporário e o vínculo com o repositório do kit é desfeito no fim —
 * o projeto não fica com remote nem tags de terceiros pendurados.
 *
 * Como o resultado é um branch com commits seus, reverter é `git switch` e
 * apagar o branch.
 */
class KitUpdate extends Command
{
    protected $signature = 'kit:update
        {--repo= : URL do repositório do kit (padrão: config kit.repository)}
        {--tag= : Versão do kit a comparar (padrão: a mais recente publicada)}
        {--from= : Versão de origem (padrão: config kit.version)}
        {--branch= : Nome do branch temporário (padrão: kit-update/<tag>)}
        {--no-branch : Trabalha no branch atual, sem criar um temporário}
        {--dry-run : Só mostra o que mudou; não altera nenhum arquivo}
        {--keep-remote : Mantém o remote e as tags do kit ao final}';

    protected $description = 'Compara o projeto com uma versão nova do starter-kit e aplica só o que você aprovar';

    /** Apelido do remote temporário. Namespace das tags: kit-*. */
    private const REMOTE = 'kit';

    /**
     * Caminhos que pertencem ao kit — a "cola" que evolui entre versões.
     *
     * Fora desta lista está o que é seu (models, resources de negócio,
     * migrations do seu domínio) ou o que o Composer já atualiza (vendor).
     *
     * @var list<string>
     */
    private const CAMINHOS_DO_KIT = [
        'app/Ai',
        'app/Console/Commands/KitInstall.php',
        'app/Console/Commands/KitUpdate.php',
        'app/Filament/Concerns',
        'app/Filament/Spotlight',
        'app/Filament/Admin/Widgets',
        'app/Filament/Infra/Widgets',
        'app/Filament/Infra/Pages',
        'app/Providers',
        'app/Traits',
        'config/kit.php',
        'docker',
        'lang/vendor',
        'resources/views/errors',
        'resources/views/filament',
        'resources/views/livewire',
        'routes/console.php',
        '.github',
        'Dockerfile.laravel',
        'docker-compose.yml',
        'phpstan.neon',
        'pint.json',
    ];

    private string $git;

    public function handle(): int
    {
        $this->git = (new ExecutableFinder)->find('git') ?? '';

        if ($this->git === '' || ! $this->preVoo()) {
            return self::FAILURE;
        }

        $repo = $this->option('repo') ?: config('kit.repository');

        $this->components->info('Buscando versões do kit...');
        $this->vincularKit($repo);

        try {
            $tags = $this->tagsDoKit();

            if ($tags === []) {
                $this->components->error("Nenhuma tag encontrada em {$repo}.");

                return self::FAILURE;
            }

            $destino = $this->escolherDestino($tags);
            $origem  = $this->resolverOrigem($tags);

            $arquivos = $this->arquivosAlterados($origem, $destino);

            if ($arquivos === []) {
                $this->components->info('Nada a atualizar: seu projeto já está na altura de '.$destino.'.');

                return self::SUCCESS;
            }

            $this->mostrarResumo($origem, $destino, $arquivos);

            if ($this->option('dry-run')) {
                note('Modo --dry-run: nenhum arquivo foi tocado.');

                return self::SUCCESS;
            }

            /*
             * Aplicar exige aprovação arquivo a arquivo — sem terminal não há
             * a quem perguntar. Em vez de criar um branch vazio e sair, o
             * comando se comporta como relatório.
             */
            if (! $this->input->isInteractive()) {
                note('Sem terminal interativo: nada foi aplicado. Rode `php artisan kit:update` num terminal para revisar e aprovar cada arquivo.');

                return self::SUCCESS;
            }

            if (! $this->prepararBranch($destino)) {
                return self::SUCCESS;
            }

            $aplicados = $this->revisarEAplicar($destino, $arquivos);

            $this->encerrar($destino, $aplicados);
        } finally {
            if (! $this->option('keep-remote')) {
                $this->desvincularKit();
            }
        }

        return self::SUCCESS;
    }

    /**
     * Só faz sentido rodar num repositório git limpo: é o que garante que
     * qualquer coisa aplicada aqui possa ser revertida com um comando.
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
                "Versione o projeto antes de atualizar — é o que torna a atualização reversível:\n\n"
                ."  git init\n"
                ."  git add -A\n"
                .'  git commit -m "estado atual antes de atualizar o kit"'
            );

            return false;
        }

        if (trim($this->git(['status', '--porcelain'])) !== '') {
            $this->components->error('Há alterações não commitadas na árvore de trabalho.');
            note(
                "Commite ou guarde o que está em andamento antes de continuar:\n\n"
                ."  git add -A && git commit -m \"wip\"\n"
                .'  # ou:  git stash'
            );

            return false;
        }

        return true;
    }

    /**
     * Vincula o repositório do kit de forma temporária e somente-leitura.
     *
     * O push é apontado para `no_push` porque quem mantém um kit costuma ter
     * permissão de escrita nele: um `git push kit main` distraído mandaria o
     * projeto do usuário para dentro do repositório do kit.
     *
     * As tags entram no namespace `kit-*` (com `--no-tags`, senão o git traz
     * também as tags soltas) para não colidirem com as versões do seu projeto.
     */
    private function vincularKit(string $repo): void
    {
        $this->desvincularKit();

        $this->git(['remote', 'add', self::REMOTE, $repo]);
        $this->git(['remote', 'set-url', '--push', self::REMOTE, 'no_push']);
        $this->git(['fetch', '--no-tags', '--quiet', self::REMOTE, 'refs/tags/*:refs/tags/kit-*']);
    }

    private function desvincularKit(): void
    {
        foreach (explode("\n", trim($this->git(['tag', '-l', 'kit-*']))) as $tag) {
            if (trim($tag) !== '') {
                $this->git(['tag', '-d', trim($tag)]);
            }
        }

        if (str_contains($this->git(['remote']), self::REMOTE)) {
            $this->git(['remote', 'remove', self::REMOTE]);
        }
    }

    /** @return list<string> tags no formato kit-vX.Y.Z, da mais nova para a mais antiga */
    private function tagsDoKit(): array
    {
        $saida = trim($this->git(['tag', '-l', 'kit-*', '--sort=-v:refname']));

        return $saida === '' ? [] : array_values(array_filter(array_map('trim', explode("\n", $saida))));
    }

    /** @param  list<string>  $tags */
    private function escolherDestino(array $tags): string
    {
        if ($alvo = $this->option('tag')) {
            $alvo = str_starts_with((string) $alvo, 'kit-') ? $alvo : 'kit-'.ltrim((string) $alvo, 'v');
            $alvo = in_array($alvo, $tags, true) ? $alvo : 'kit-v'.ltrim((string) $this->option('tag'), 'v');

            if (! in_array($alvo, $tags, true)) {
                $this->components->warn("Tag {$this->option('tag')} não existe no kit. Usando a mais recente.");

                return $tags[0];
            }

            return $alvo;
        }

        if (! $this->input->isInteractive() || count($tags) === 1) {
            return $tags[0];
        }

        return select(
            label: 'Atualizar para qual versão do kit?',
            options: $tags,
            default: $tags[0],
        );
    }

    /**
     * De qual versão o projeto partiu. Sem essa referência o diff mistura o que
     * o kit mudou com o que você mudou — daí a marca em config/kit.php.
     *
     * @param  list<string>  $tags
     */
    private function resolverOrigem(array $tags): ?string
    {
        $origem = $this->option('from') ?: config('kit.version');

        if (! $origem) {
            return null;
        }

        $tag = 'kit-v'.ltrim((string) $origem, 'v');

        if (! in_array($tag, $tags, true)) {
            $this->components->warn("A versão de origem ({$origem}) não existe como tag no kit — comparando com a árvore de trabalho.");

            return null;
        }

        return $tag;
    }

    /**
     * Lista os arquivos do kit que mudaram.
     *
     * Com origem conhecida, compara tag→tag: mostra o que o KIT mudou, sem
     * acusar as suas edições. Sem origem, compara a tag nova com a sua árvore
     * de trabalho — mais ruidoso, porém honesto.
     *
     * @return array<string, string> caminho => rótulo do tipo de mudança
     */
    private function arquivosAlterados(?string $origem, string $destino): array
    {
        $args = $origem !== null
            ? ['diff', '--name-status', $origem, $destino, '--']
            : ['diff', '--name-status', $destino, '--'];

        $saida = trim($this->git([...$args, ...self::CAMINHOS_DO_KIT]));

        if ($saida === '') {
            return [];
        }

        $arquivos = [];

        foreach (explode("\n", $saida) as $linha) {
            [$status, $caminho] = array_pad(preg_split('/\s+/', trim($linha), 2) ?: [], 2, null);

            if ($caminho === null) {
                continue;
            }

            /*
             * Sem origem o diff é "tag → sua árvore", então a leitura inverte:
             * 'D' quer dizer que o arquivo existe no kit e não no seu projeto,
             * e 'A' que o arquivo é seu e o kit não tem — este último se ignora.
             */
            $rotulo = match (true) {
                $status === 'A' && $origem !== null => 'novo no kit',
                $status === 'A'                     => null,
                $status === 'D' && $origem !== null => 'removido do kit',
                $status === 'D'                     => 'novo no kit',
                default                             => 'modificado',
            };

            if ($rotulo !== null) {
                $arquivos[$caminho] = $rotulo;
            }
        }

        ksort($arquivos);

        return $arquivos;
    }

    /** @param  array<string, string>  $arquivos */
    private function mostrarResumo(?string $origem, string $destino, array $arquivos): void
    {
        $de = $origem ?? 'sua árvore de trabalho';

        $this->newLine();
        $this->components->info("Mudanças do kit entre {$de} e {$destino}");

        table(
            headers: ['Arquivo', 'Mudança'],
            rows: array_map(fn (string $c, string $r): array => [$c, $r], array_keys($arquivos), $arquivos),
        );

        note(
            'Estes são apenas os caminhos que pertencem ao kit. '
            ."Seu código de negócio (models, resources, migrations do seu domínio) não entra na comparação.\n"
            .'Dependências como Filament e plugins não aparecem aqui: elas vêm por `composer update`.'
        );
    }

    /** @return bool false quando o usuário desiste */
    private function prepararBranch(string $destino): bool
    {
        if ($this->option('no-branch')) {
            return true;
        }

        $branch = $this->option('branch') ?: 'kit-update/'.str_replace('kit-', '', $destino);
        $atual  = trim($this->git(['rev-parse', '--abbrev-ref', 'HEAD']));

        if ($atual === $branch) {
            return true;
        }

        $criar = confirm(
            label: "Criar o branch temporário `{$branch}` para aplicar as mudanças?",
            default: true,
            hint: "Você está em `{$atual}`. Trabalhar num branch mantém o seu limpo e torna o descarte trivial.",
        );

        if (! $criar) {
            return confirm(
                label: "Aplicar direto em `{$atual}` mesmo assim?",
                default: false,
            );
        }

        $this->git(['switch', '-c', $branch]);
        $this->components->info("Branch `{$branch}` criado.");

        return true;
    }

    /**
     * Aprovação arquivo a arquivo. Nada é sobrescrito sem o usuário ver o diff
     * e dizer sim.
     *
     * @param  array<string, string>  $arquivos
     * @return list<string>
     */
    private function revisarEAplicar(string $destino, array $arquivos): array
    {
        $aplicados = [];

        foreach ($arquivos as $caminho => $rotulo) {
            if ($rotulo === 'removido do kit') {
                note("`{$caminho}` foi REMOVIDO do kit. Nada é apagado automaticamente — decida você.");

                continue;
            }

            $escolha = select(
                label: "{$caminho} ({$rotulo})",
                options: [
                    'diff'    => 'Ver o diff',
                    'aplicar' => 'Aplicar a versão do kit',
                    'pular'   => 'Pular',
                    'sair'    => 'Parar por aqui',
                ],
                default: 'diff',
            );

            if ($escolha === 'diff') {
                $this->newLine();
                $this->line($this->git(['diff', $destino, '--', $caminho]));
                $this->newLine();

                $escolha = confirm(label: "Aplicar a versão do kit em `{$caminho}`?", default: false)
                    ? 'aplicar'
                    : 'pular';
            }

            if ($escolha === 'sair') {
                break;
            }

            if ($escolha === 'aplicar') {
                $this->git(['checkout', $destino, '--', $caminho]);
                $aplicados[] = $caminho;
                $this->components->info("aplicado: {$caminho}");
            }
        }

        return $aplicados;
    }

    /** @param  list<string>  $aplicados */
    private function encerrar(string $destino, array $aplicados): void
    {
        $versao = str_replace('kit-v', '', $destino);

        $this->newLine();

        if ($aplicados === []) {
            $this->components->info('Nenhum arquivo foi alterado.');

            return;
        }

        $this->components->info(count($aplicados).' arquivo(s) atualizado(s) — nada foi commitado ainda.');

        note(
            "Próximos passos:\n\n"
            ."  git diff --staged        # revise tudo que entrou\n"
            ."  composer update          # traz as dependências novas (Filament, plugins)\n"
            ."  php artisan filament:assets\n"
            ."  composer test            # pint + phpstan + testes\n\n"
            ."Atualize a marca de versão em config/kit.php para '{$versao}'.\n\n"
            .'Se algo saiu errado: `git checkout -- .` desfaz, ou apague o branch e volte para o seu.'
        );
    }

    /** @param  list<string>  $args */
    private function git(array $args): string
    {
        $processo = new Process([$this->git, ...$args], base_path(), timeout: 300);
        $processo->run();

        return $processo->getOutput();
    }
}
