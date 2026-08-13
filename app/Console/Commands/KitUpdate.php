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
        {--all : Aplica tudo de uma vez (uma confirmação para o conjunto)}
        {--only-new : Aplica de uma vez só os arquivos que ainda não existem no projeto}
        {--dry-run : Só mostra o que mudou; não altera nenhum arquivo}
        {--keep-remote : Mantém o remote e as tags do kit ao final}';

    protected $description = 'Compara o projeto com uma versão nova do starter-kit e aplica só o que você aprovar';

    /** Apelido do remote temporário. Namespace das tags: kit-*. */
    private const REMOTE = 'kit';

    /**
     * Caminhos que pertencem ao kit — a "cola" que evolui entre versões.
     *
     * Fora desta lista está o que é seu (seus models, seus resources de
     * negócio) ou o que o Composer já atualiza (vendor).
     *
     * Diretórios são seguros mesmo quando você tem arquivos seus dentro deles:
     * a comparação é kit-versão-A × kit-versão-B, então um arquivo que só
     * existe no SEU projeto nunca aparece no diff. Por isso `database/seeders`
     * entra inteiro, mas `app/Models` entra arquivo a arquivo — ali a chance de
     * colisão de nome com um model seu é real.
     *
     * MANTER ESTA LISTA EM DIA É PARTE DE ENTREGAR UMA FEATURE. Arquivo do kit
     * fora daqui simplesmente não chega a quem já instalou — a feature existe
     * no repositório e é invisível na prática. `tests/Kit/KitUpdateTest.php`
     * cobra os caminhos críticos.
     *
     * @var list<string>
     */
    private const CAMINHOS_DO_KIT = [
        'app/Ai',
        // Comandos do kit. Comando SEU não aparece: ele não existe na árvore
        // do kit, então nunca entra no diff entre duas versões.
        'app/Console/Commands',
        'app/Filament/Concerns',
        'app/Filament/Spotlight',
        'app/Filament/Admin/Widgets',
        'app/Filament/Admin/Resources/Tenants',
        'app/Filament/App/Resources/Projetos',
        'app/Filament/Infra/Widgets',
        'app/Filament/Infra/Pages',
        'app/Http/Middleware',
        // Models do kit, um a um: `app/Models` inteiro convidaria colisão com
        // os seus.
        'app/Models/User.php',
        'app/Models/Tenant.php',
        'app/Models/Projeto.php',
        'app/Policies/TenantPolicy.php',
        'app/Providers',
        'app/Traits',
        'config/kit.php',
        // Migrations e seeders do kit. Os SEUS não entram no diff, pela mesma
        // razão dos comandos. Migration nova exige rodar `php artisan migrate`
        // depois de aplicar.
        'database/factories/TenantFactory.php',
        'database/migrations',
        'database/seeders',
        'docker',
        'lang/vendor',
        'resources/views/errors',
        'resources/views/filament',
        'resources/views/livewire',
        'routes/console.php',
        // Os testes do kit acompanham a atualização: é com eles que você
        // confere se a fundação continua de pé depois de aplicar.
        'tests/Kit',
        'tests/Tenancy',
        'tests/Pest.php',
        'tests/TestCase.php',
        'tests/TenancyTestCase.php',
        // `.github` NÃO entra: o `.gitattributes` o marca com `export-ignore`,
        // então ele não existe em projeto nascido de `create-project`. O CI é do
        // kit, não do projeto — listá-lo aqui faria o comando oferecer arquivo
        // que o projeto não deveria ter.
        'Dockerfile.laravel',
        'docker-compose.yml',
        'phpstan.neon',
        'phpunit.xml',
        'pint.json',
    ];

    /**
     * Arquivos que o kit evolui mas NUNCA aplica: são do usuário por definição.
     *
     * O `composer.json` carrega as dependências do projeto — sobrescrevê-lo
     * apagaria tudo que foi instalado depois do kit. Em vez de aplicar, o
     * comando relata o que mudou (pacotes e scripts) para você copiar à mão.
     *
     * @var list<string>
     */
    private const CAMINHOS_SO_RELATORIO = [
        'composer.json',
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
                $versao = str_replace('kit-v', '', $destino);

                // Escolher uma versão antiga também cai aqui — e aí dizer "está
                // atualizado" seria mentira: não há mudanças ENTRE as duas.
                $this->components->info($destino === $tags[0]
                    ? "Nada a atualizar: seu projeto já está na versão mais atual do kit ({$versao})."
                    : "Nada a atualizar: não há mudanças do kit entre a sua versão e a {$versao}.");

                return self::SUCCESS;
            }

            $this->mostrarResumo($origem, $destino, $arquivos);

            if ($this->option('dry-run')) {
                note('Modo --dry-run: nenhum arquivo foi tocado.');

                return self::SUCCESS;
            }

            /*
             * Aplicar exige aprovação. Sem terminal não há a quem perguntar —
             * a não ser que a aprovação já tenha vindo na linha de comando,
             * como `--all` ou `--only-new`. Nesse caso o comando é scriptável.
             */
            if (! $this->input->isInteractive() && ! $this->option('all') && ! $this->option('only-new')) {
                note(
                    "Sem terminal interativo: nada foi aplicado.\n"
                    ."Rode `php artisan kit:update` num terminal para revisar arquivo a arquivo,\n"
                    .'ou passe `--only-new` (só os arquivos novos) / `--all` (tudo).'
                );

                return self::SUCCESS;
            }

            if (! $this->prepararBranch($destino)) {
                return self::SUCCESS;
            }

            $aplicados = $this->revisarEAplicar($destino, $arquivos);

            $this->repararPastasDeTeste();
            $this->relatarComposerJson($origem, $destino);

            $this->encerrar($destino, $aplicados);
        } finally {
            if (! $this->option('keep-remote')) {
                $this->desvincularKit();
            }
        }

        return self::SUCCESS;
    }

    /**
     * Aplicar mudanças exige repositório git com a árvore limpa: é o que garante
     * que tudo que este comando fizer possa ser desfeito com um `git checkout`.
     *
     * `--dry-run` não escreve nada, então não exige árvore limpa — cobrar isso
     * de um relatório só atrapalharia quem quer justamente olhar antes de mexer.
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

        if ($this->option('dry-run')) {
            return true;
        }

        $sujos = trim($this->git(['status', '--porcelain']));

        if ($sujos === '') {
            return true;
        }

        $this->components->error('Há alterações não commitadas na árvore de trabalho.');

        $lista = array_slice(explode("\n", $sujos), 0, 10);
        $this->line('  '.implode("\n  ", array_map(trim(...), $lista)));

        if (substr_count($sujos, "\n") >= 10) {
            $this->line('  ...');
        }

        note(
            "Commite ou guarde o que está em andamento — assim dá para distinguir o que é seu\n"
            ."do que o kit trouxe, e reverter só o que quiser:\n\n"
            ."  git add -A && git commit -m \"antes de atualizar o kit\"\n"
            ."  # ou:  git stash\n\n"
            .'Só quer ver o que mudou, sem tocar em nada? Rode com `--dry-run`.'
        );

        return false;
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

        // Sem terminal (rodando com --all/--only-new num script), cria o branch
        // sem perguntar: é o comportamento mais conservador dos dois.
        $criar = ! $this->input->isInteractive() || confirm(
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
     * Revisão com aprovação — arquivo a arquivo ou em lote.
     *
     * O lote existe porque revisar 30 arquivos um a um é hostil, mas ele continua
     * sendo uma aprovação: uma confirmação para o conjunto, com a conta de quantos
     * são novos e quantos são modificados.
     *
     * A distinção importa e é a razão de existir o lote "só os novos": arquivo que
     * não existe no seu projeto não tem o que sobrescrever, então aplicá-lo em massa
     * é seguro. Já um "modificado" pode conter edição sua — e é aí que o diff vale
     * o tempo de olhar.
     *
     * @param  array<string, string>  $arquivos
     * @return list<string>
     */
    private function revisarEAplicar(string $destino, array $arquivos): array
    {
        $aplicados = [];
        $emLote    = null;

        if ($this->option('all')) {
            $emLote = 'todos';
        } elseif ($this->option('only-new')) {
            $emLote = 'novos';
        }

        if ($emLote !== null && ! $this->confirmarLote($emLote, $arquivos)) {
            return [];
        }

        $pendentes = $arquivos;

        foreach ($arquivos as $caminho => $rotulo) {
            unset($pendentes[$caminho]);

            if ($rotulo === 'removido do kit') {
                note("`{$caminho}` foi REMOVIDO do kit. Nada é apagado automaticamente — decida você.");

                continue;
            }

            // Já está em lote: aplica sem perguntar de novo.
            if ($emLote === 'todos' || ($emLote === 'novos' && $rotulo === 'novo no kit')) {
                $aplicados[] = $this->aplicar($destino, $caminho);

                continue;
            }

            if ($emLote === 'novos') {
                continue;
            }

            $escolha = select(
                label: "{$caminho} ({$rotulo})",
                options: $this->opcoesDeRevisao($pendentes),
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

            // Lote escolhido no meio do caminho: vale para este arquivo em diante.
            if (in_array($escolha, ['todos', 'novos'], true)) {
                $restantes = [$caminho => $rotulo] + $pendentes;

                if (! $this->confirmarLote($escolha, $restantes)) {
                    $escolha = 'pular';
                } else {
                    $emLote  = $escolha;
                    $escolha = ($escolha === 'todos' || $rotulo === 'novo no kit') ? 'aplicar' : 'pular';
                }
            }

            if ($escolha === 'aplicar') {
                $aplicados[] = $this->aplicar($destino, $caminho);
            }
        }

        return $aplicados;
    }

    /**
     * @param  array<string, string>  $pendentes
     * @return array<string, string>
     */
    private function opcoesDeRevisao(array $pendentes): array
    {
        $opcoes = [
            'diff'    => 'Ver o diff',
            'aplicar' => 'Aplicar a versão do kit',
            'pular'   => 'Pular',
        ];

        $novos = count(array_filter($pendentes, fn (string $r): bool => $r === 'novo no kit'));
        $total = count(array_filter($pendentes, fn (string $r): bool => $r !== 'removido do kit'));

        if ($novos > 0) {
            $opcoes['novos'] = "Aplicar todos os arquivos NOVOS daqui em diante ({$novos} restantes)";
        }

        if ($total > 0) {
            $opcoes['todos'] = "Aplicar TUDO daqui em diante ({$total} restantes)";
        }

        $opcoes['sair'] = 'Parar por aqui';

        return $opcoes;
    }

    /** @param  array<string, string>  $arquivos */
    private function confirmarLote(string $modo, array $arquivos): bool
    {
        $novos       = count(array_filter($arquivos, fn (string $r): bool => $r === 'novo no kit'));
        $modificados = count(array_filter($arquivos, fn (string $r): bool => $r === 'modificado'));

        if ($modo === 'novos') {
            note("Serão aplicados {$novos} arquivo(s) novo(s). Nenhum arquivo seu é sobrescrito — eles ainda não existem no projeto.");

            return ! $this->input->isInteractive() || confirm(label: 'Aplicar os novos?', default: true);
        }

        note(
            "Serão aplicados {$novos} arquivo(s) novo(s) e {$modificados} modificado(s).\n"
            .'Os modificados SUBSTITUEM o conteúdo atual — se você editou algum deles, a sua versão se perde '
            .'(recuperável com `git checkout -- <arquivo>`, já que nada foi commitado).'
        );

        return ! $this->input->isInteractive() || confirm(
            label: 'Aplicar tudo?',
            default: false,
            hint: 'Prefere não arriscar os modificados? Cancele e escolha "Aplicar todos os arquivos NOVOS".',
        );
    }

    private function aplicar(string $destino, string $caminho): string
    {
        $this->git(['checkout', $destino, '--', $caminho]);
        $this->components->info("aplicado: {$caminho}");

        return $caminho;
    }

    /**
     * Recria pastas de teste declaradas no phpunit.xml que não existem em disco.
     *
     * O PHPUnit aborta com exit 2 quando uma testsuite aponta para um caminho
     * inexistente — e é fácil chegar nesse estado sem perceber: git não versiona
     * diretório vazio, então uma pasta de testes sem arquivos simplesmente não
     * viaja no pacote nem no clone. Acontece com `tests/Feature` em projeto que
     * ainda não escreveu nenhum teste próprio.
     *
     * O `.gitkeep` é o que impede o problema de voltar no próximo clone.
     */
    private function repararPastasDeTeste(): void
    {
        $phpunit = base_path('phpunit.xml');

        if (! is_file($phpunit)) {
            return;
        }

        $xml = @simplexml_load_file($phpunit);

        if ($xml === false) {
            return;
        }

        foreach ($xml->xpath('//testsuite/directory') ?: [] as $diretorio) {
            $caminho = base_path((string) $diretorio);

            if (is_dir($caminho)) {
                continue;
            }

            mkdir($caminho, 0755, recursive: true);
            file_put_contents($caminho.'/.gitkeep', '');

            $this->components->info('Pasta de testes recriada: '.(string) $diretorio);
        }
    }

    /**
     * O que mudou no `composer.json` do kit — como relatório, nunca aplicado.
     *
     * Sobrescrever o composer.json apagaria as dependências que o projeto
     * instalou depois do kit. Mas ignorá-lo em silêncio esconde coisas que o
     * usuário precisa saber: pacote novo do kit e script novo (foi assim que o
     * `composer test:kit` deixou de chegar em quem já tinha o projeto criado).
     */
    private function relatarComposerJson(?string $origem, string $destino): void
    {
        if ($origem === null) {
            return;
        }

        $diff = trim($this->git(['diff', $origem, $destino, '--', ...self::CAMINHOS_SO_RELATORIO]));

        if ($diff === '') {
            return;
        }

        // Só as linhas que interessam: pacotes e scripts entrando ou saindo.
        $relevantes = array_values(array_filter(
            explode("\n", $diff),
            fn (string $linha): bool => (bool) preg_match('/^[+-]\s{8,}"[^"]+"\s*:/', $linha),
        ));

        $this->newLine();
        $this->components->warn('O composer.json do kit mudou — este arquivo NUNCA é aplicado automaticamente.');

        if ($relevantes !== []) {
            $this->line('  '.implode("\n  ", array_map(trim(...), array_slice($relevantes, 0, 20))));
        }

        note(
            "Ele carrega as dependências do SEU projeto: aplicá-lo apagaria tudo que você instalou.\n"
            ."Copie à mão o que fizer sentido (pacote novo, script novo):\n\n"
            ."  git diff {$origem} {$destino} -- composer.json\n\n"
            .'Depois de mexer nas dependências: `composer update`.'
        );
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

        $this->marcarVersao($versao);

        /*
         * O comando é um dos arquivos que ele mesmo atualiza. O PHP já carregou
         * a classe antiga em memória, então tudo nesta execução — inclusive as
         * mensagens acima — vem da versão anterior. Avisar evita a conclusão
         * errada de que a versão nova não funcionou.
         */
        if (in_array('app/Console/Commands/KitUpdate.php', $aplicados, true)) {
            note(
                "O próprio `kit:update` foi atualizado nesta rodada.\n"
                .'O que você viu acima ainda é o comportamento da versão anterior; a nova vale a partir da próxima execução.'
            );
        }

        note(
            "Próximos passos:\n\n"
            ."  git diff --staged        # revise tudo que entrou\n"
            ."  php artisan filament:assets\n"
            ."  composer test:kit        # só os testes do kit — é o que a atualização pode ter quebrado\n"
            ."  composer test            # a suíte inteira, incluindo a do seu negócio\n\n"
            .'Se algo saiu errado: `git checkout -- .` desfaz, ou apague o branch e volte para o seu.'
        );
    }

    /**
     * Grava a versão aplicada em `config/kit.php`.
     *
     * É o ponto de partida da PRÓXIMA comparação, então deixar isso a cargo do
     * usuário significa que uma distração hoje vira um diff errado no próximo
     * update. Só a linha da versão é reescrita — o resto do arquivo (credenciais
     * do seeder, repositório) fica intacto.
     */
    private function marcarVersao(string $versao): void
    {
        $arquivo = config_path('kit.php');

        if (! is_file($arquivo)) {
            return;
        }

        $conteudo = (string) file_get_contents($arquivo);

        $novo = preg_replace(
            "/(['\"]version['\"]\s*=>\s*)['\"][^'\"]*['\"]/",
            "\${1}'{$versao}'",
            $conteudo,
            1,
            $trocas,
        );

        if ($novo === null || $trocas === 0) {
            $this->components->warn(
                "Não encontrei a chave `version` em config/kit.php — adicione `'version' => '{$versao}',` "
                .'para o próximo `kit:update` saber de onde comparar.'
            );

            return;
        }

        file_put_contents($arquivo, $novo);

        $this->components->info("config/kit.php: versão marcada como {$versao}.");
    }

    /** @param  list<string>  $args */
    private function git(array $args): string
    {
        $processo = new Process([$this->git, ...$args], base_path(), timeout: 300);
        $processo->run();

        return $processo->getOutput();
    }
}
