<?php

use App\Console\Commands\KitUpdate;

/**
 * O `kit:update` compara duas versões do kit restrito a uma lista fechada de
 * caminhos. Arquivo do kit fora dessa lista **não chega a quem já instalou**:
 * a feature existe no repositório e é invisível na prática.
 *
 * Foi exatamente o que aconteceu com a multi-tenancy — três versões inteiras
 * (0.9.1 a 0.9.3) em que o `kit:update` só oferecia `config/kit.php`. Este
 * teste é o que faz a lista envelhecer com barulho em vez de em silêncio.
 */
function caminhosDoKit(): array
{
    $reflexao = new ReflectionClass(KitUpdate::class);

    /** @var list<string> $caminhos */
    $caminhos = $reflexao->getConstant('CAMINHOS_DO_KIT');

    return $caminhos;
}

function estaCoberto(string $arquivo): bool
{
    foreach (caminhosDoKit() as $caminho) {
        if ($arquivo === $caminho || str_starts_with($arquivo, rtrim($caminho, '/').'/')) {
            return true;
        }
    }

    return false;
}

it('cobre os arquivos da fundação na lista de caminhos do kit', function (string $arquivo): void {
    expect(estaCoberto($arquivo))->toBeTrue(
        "`{$arquivo}` é do kit mas não está em KitUpdate::CAMINHOS_DO_KIT — "
        .'quem já instalou o projeto nunca receberá este arquivo.'
    );
})->with([
    // A cola
    'app/Providers/KitServiceProvider.php',
    'app/Providers/Concerns/ConfiguraFilamentGlobal.php',
    'app/Traits/TemUuid.php',
    'app/Traits/AuditsFillables.php',
    'app/Models/User.php',

    // Comandos
    'app/Console/Commands/KitInstall.php',
    'app/Console/Commands/KitUpdate.php',
    'app/Console/Commands/KitTenancy.php',

    // Customizador da instalação
    'app/Support/CustomizadorDaInstalacao.php',
    'app/Support/SubstituicaoEmArquivo.php',
    'app/Support/AtivadorDeTenancy.php',
    'app/Support/CorPrimaria.php',

    // Multi-tenancy
    'app/Models/Tenant.php',
    'app/Traits/BelongsToTenant.php',
    'app/Http/Middleware/DefinirTenantDePermissoes.php',
    'app/Policies/TenantPolicy.php',
    'app/Filament/Admin/Resources/Tenants/TenantResource.php',
    'app/Ai/Support/ResolvedorDeTenant.php',
    'database/migrations/0001_01_01_000020_create_tenants_table.php',
    'database/seeders/TenantsSeeder.php',
    'database/factories/TenantFactory.php',

    // Suítes do kit
    'tests/Pest.php',
    'tests/TestCase.php',
    'tests/TenancyTestCase.php',
    'tests/Kit/FundacaoTest.php',
    'tests/Tenancy/TenancyTest.php',
]);

/**
 * Diretórios de CÓDIGO do kit, varridos arquivo a arquivo.
 *
 * A lista à mão do teste acima documenta o que é crítico, mas não pega o que
 * ninguém pensou em escrever — e foi exatamente o que aconteceu: os resources de
 * `Users`, `AgentesIa` e `AiRuns` ficaram fora do `kit:update` por três versões,
 * e a correção da tela de usuários da 0.9.7 não chegou a nenhum projeto
 * instalado. Aqui a árvore é a fonte da verdade.
 *
 * `config/` fica fora de propósito: é o que cada projeto calibra, e o kit não
 * sobrescreve (só `config/kit.php`, que é a marca de nascença).
 *
 * @var list<string>
 */
const DIRETORIOS_DE_CODIGO = [
    'app',
    'database/factories',
    'database/migrations',
    'database/seeders',

    /*
     * `resources/views` entrou depois de a v0.23.0 quebrar em projeto atualizado:
     * a arte do login virou a view `svg/arte-do-login.blade.php`, o `IdentidadeDoKit`
     * que a consome FOI entregue, e a view não — `resources/views/svg` não estava em
     * `CAMINHOS_DO_KIT`. Quem rodou `kit:update` recebeu
     * "View [svg.arte-do-login] not found" no primeiro `composer dev`.
     *
     * A varredura não pegou porque olhava só `app` e `database/*`. Um diretório de
     * view novo era invisível para ela, e a mesma armadilha já tinha engolido
     * `resources/views/auth` antes desta correção.
     */
    'resources/views',

    /*
     * O CSS que o kit registra por `FilamentAsset`. Entrou com a correção do overlay da busca
     * ⌘K: `spotlight.css` seria o TERCEIRO arquivo do diretório fora de `CAMINHOS_DO_KIT` —
     * `kit.css` e `cards.css` já estavam, e nunca chegaram a projeto atualizado. Só o
     * diretório `filament/`: `resources/css/app.css` é do skeleton (ponto de extensão de
     * quem instala) e `resources/css/vendor/` é o que os pacotes publicam.
     */
    'resources/css/filament',
];

/**
 * Arquivos que moram nesses diretórios e NÃO são do kit.
 *
 * @var list<string>
 */
const NAO_E_DO_KIT = [
    // Do skeleton do Laravel, e ponto de extensão de quem instala.
    'app/Http/Controllers/Controller.php',
];

it('cobre todo o código do kit, e não só o que alguém lembrou de listar', function (): void {
    /*
     * Só faz sentido NO kit: em projeto instalado, o model e o resource DO
     * USUÁRIO moram nesses mesmos diretórios e apareceriam como descobertos. O
     * `.github` é `export-ignore`, logo existe aqui e não lá — é o sinal mais
     * confiável de "estou na árvore do kit".
     */
    if (! is_dir(base_path('.github'))) {
        expect(true)->toBeTrue();

        return;
    }

    $descobertos = [];

    foreach (DIRETORIOS_DE_CODIGO as $diretorio) {
        $arquivos = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(base_path($diretorio), FilesystemIterator::SKIP_DOTS)
        );

        foreach ($arquivos as $arquivo) {
            $relativo = str_replace('\\', '/', substr($arquivo->getPathname(), strlen(base_path()) + 1));

            // `resources/views/vendor` é o que os pacotes publicam com `vendor:publish`;
            // não é código do kit e não deve ser entregue pelo `kit:update`.
            if (str_starts_with($relativo, 'resources/views/vendor/')) {
                continue;
            }

            if (in_array($relativo, NAO_E_DO_KIT, true) || estaCoberto($relativo)) {
                continue;
            }

            $descobertos[] = $relativo;
        }
    }

    sort($descobertos);

    expect($descobertos)->toBe([], "Arquivos do kit fora de KitUpdate::CAMINHOS_DO_KIT:\n  "
        .implode("\n  ", $descobertos)
        ."\n\nQuem já instalou o projeto nunca vai receber estes arquivos. "
        .'Some-os à lista, ou a NAO_E_DO_KIT se realmente não forem do kit.');
});

/**
 * O que os agentes de IA leem tem de acompanhar a atualização.
 *
 * As regras de `.ai/rules` são lidas ANTES de editar arquivo, por instrução do
 * `CLAUDE.md`/`AGENTS.md` que o Boost gera, e as wikis são a referência que elas citam.
 * Sem elas na lista, o `kit:update` entregava o código de uma feature e não a armadilha
 * que ela documenta — regra nova chegava só a projeto novo.
 *
 * Varredura, e não lista à mão: foi lista à mão que deixou metade do Filament de fora na
 * v0.9.8. `wikis/specs/` fica fora de propósito — é o histórico de planejamento do kit.
 */
it('cobre as regras de IA e as wikis de referência', function (): void {
    $descobertos = [];

    foreach (['.ai/rules', 'wikis'] as $diretorio) {
        foreach (glob(base_path($diretorio).'/*.md') ?: [] as $arquivo) {
            $relativo = str_replace('\\', '/', substr($arquivo, strlen(base_path()) + 1));

            if (! estaCoberto($relativo)) {
                $descobertos[] = $relativo;
            }
        }
    }

    sort($descobertos);

    expect($descobertos)->toBe([], "Documentação do kit fora de KitUpdate::CAMINHOS_DO_KIT:\n  "
        .implode("\n  ", $descobertos)
        ."\n\nQuem já instalou o projeto nunca vai receber estes arquivos — e são eles que "
        .'ensinam o próximo agente a não repetir armadilha já paga.');
});

it('não entrega o histórico de planejamento do kit', function (): void {
    // `wikis/specs/` são as ADRs das features DO KIT. Entregá-las faria todo projeto
    // instalado carregar o planejamento de outro projeto, que só cresce.
    expect(estaCoberto('wikis/specs/main/convite-de-usuario/01-plano-acao.md'))->toBeFalse();
});

/**
 * CT-04 (`wikis/specs/fix/spotlight-sem-estilo/`) — o CSS do kit é entregue, fonte e
 * publicado, e o CSS do usuário não.
 *
 * As linhas de `cards.css` e `kit.css` são o que separa "listei o arquivo desta correção" de
 * "listei o diretório": arquivo a arquivo é a granularidade que o comentário de `app/Filament`
 * já condenou, e foi ela que deixou os dois de fora até aqui. Os controles negativos são o que
 * impede a saída oposta — `resources/css` inteiro entregaria o `app.css` por cima do do usuário.
 */
it('entrega o css do kit — fonte e publicado — e não o css do usuário', function (string $arquivo, bool $coberto): void {
    expect(estaCoberto($arquivo))->toBe($coberto);
})->with([
    'spotlight.css (fonte)'      => ['resources/css/filament/spotlight.css', true],
    'cards.css (nunca entregue)' => ['resources/css/filament/cards.css', true],
    'kit.css (nunca entregue)'   => ['resources/css/filament/kit.css', true],
    'spotlight.css (publicado)'  => ['public/css/kit/kit-spotlight.css', true],
    'app.css do skeleton'        => ['resources/css/app.css', false],
    'css publicado por pacote'   => ['resources/css/vendor/filament-onboarding/onboarding.css', false],
]);

it('só lista caminhos que existem de fato', function (): void {
    $ausentes = array_values(array_filter(
        caminhosDoKit(),
        fn (string $caminho): bool => ! file_exists(base_path($caminho)),
    ));

    // Caminho que não existe mais vira ruído no diff e esconde erro de digitação.
    expect($ausentes)->toBe([]);
});

/**
 * O `.gitattributes` marca com `export-ignore` o que fica fora do pacote
 * distribuído — o CI e o changelog são do kit, não do projeto que nasce dele.
 * Caminho assim não existe em projeto instalado por `create-project`: listá-lo
 * aqui faria o `kit:update` oferecer arquivo que o projeto não deveria ter, e
 * derrubaria o teste acima em toda instalação (foi o que aconteceu com
 * `.github`).
 */
it('não lista caminho que o pacote distribuído deixa de fora', function (): void {
    $linhas = file(base_path('.gitattributes'), FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    $exportIgnore = [];

    foreach ($linhas as $linha) {
        if (preg_match('/^(\S+)\s+export-ignore\b/', trim($linha), $captura) === 1) {
            $exportIgnore[] = ltrim($captura[1], '/');
        }
    }

    expect($exportIgnore)->not->toBeEmpty('.gitattributes sem export-ignore: o teste perdeu o alvo.')
        ->and(array_values(array_intersect(caminhosDoKit(), $exportIgnore)))->toBe([]);
});

/*
|--------------------------------------------------------------------------
| O piso de exibição do menu de versões
|--------------------------------------------------------------------------
|
| O kit passou de quarenta tags publicadas, e o `select()` de destino listava
| todas. `KitUpdate::PISO_DE_EXIBICAO` corta a lista — mas só a LISTA.
|
| Estes casos existem por causa da assimetria que o corte cria, e que é onde ele
| erra silencioso: filtrar demais não deixa o comando vermelho, deixa o projeto
| antigo sem referência de origem, comparando contra a árvore de trabalho e
| culpando o usuário pelas próprias edições.
|
*/

/**
 * O piso é uma versão real e comparável — senão o filtro reprova tudo ou nada.
 */
/**
 * O aviso da segunda rodada traz o comando pronto, com `--from` e `--no-branch`.
 *
 * Medido numa instalação v0.22.3 atualizada para a 0.24.1: a primeira rodada grava a versão
 * nova em `config/kit.php` (`marcarVersao()`), então a segunda, sem `--from`, lê o destino como
 * origem e responde "Nada a atualizar" — com o CSS do kit ainda faltando. E o branch temporário
 * já existe, então sem `--no-branch` a segunda rodada nem começa. Um aviso que diga só "rode de
 * novo" produz exatamente a atualização pela metade que ele existe para evitar.
 */
it('manda a segunda rodada com --from explícito e --no-branch', function (): void {
    $fonte = (string) file_get_contents(base_path('app/Console/Commands/KitUpdate.php'));
    $aviso = mb_substr($fonte, (int) mb_strpos($fonte, 'O próprio `kit:update` foi atualizado nesta rodada'));
    $aviso = mb_substr($aviso, 0, (int) mb_strpos($aviso, 'Próximos passos'));

    expect($aviso)->toContain('php artisan kit:update{$from} --tag={$versao} --no-branch')
        ->and($fonte)->toContain("' --from='.str_replace('kit-v', '', \$origem)");
});

it('tem um piso de exibição em formato de versão comparável', function (): void {
    $piso = (new ReflectionClassConstant(KitUpdate::class, 'PISO_DE_EXIBICAO'))->getValue();

    expect($piso)->toBeString()
        ->and(preg_match('/^\d+\.\d+\.\d+$/', (string) $piso))->toBe(1)
        ->and(version_compare((string) $piso, '0.0.0', '>'))->toBeTrue();
})->group('kit');

/**
 * A regra do corte, exercida sobre a mesma expressão que o comando usa.
 *
 * O caso da lista vazia é o que impede o piso de virar um menu sem opção: se
 * nenhuma tag alcança o piso, o comando devolve a lista inteira. Um menu longo
 * é ruim; um menu vazio é um comando quebrado.
 */
it('corta do menu as versões abaixo do piso, e nunca devolve menu vazio', function (): void {
    $piso = (string) (new ReflectionClassConstant(KitUpdate::class, 'PISO_DE_EXIBICAO'))->getValue();

    $filtrar = fn (array $tags): array => array_values(array_filter(
        $tags,
        fn (string $tag): bool => version_compare(ltrim(str_replace('kit-', '', $tag), 'v'), $piso, '>='),
    ));

    $tags = ['kit-v0.23.0', 'kit-v0.22.5', 'kit-v0.20.1', 'kit-v0.9.0'];

    expect($filtrar($tags))->toBe(['kit-v0.23.0'])
        ->and($filtrar(['kit-v0.22.5', 'kit-v0.9.0']))->toBe([]);
})->group('kit');

/**
 * A metade que protege quem está atrasado.
 *
 * `resolverOrigem()` procura a tag de onde o projeto partiu na lista COMPLETA.
 * Se alguém "simplificar" aplicando o piso em `tagsDoKit()`, um projeto na v0.20
 * deixa de encontrar a própria origem — e este caso fica vermelho antes de o
 * usuário descobrir sozinho.
 */
it('mantém a lista completa disponível para resolver a origem do projeto', function (): void {
    $fonte = (string) file_get_contents(base_path('app/Console/Commands/KitUpdate.php'));

    $tagsDoKit = mb_substr($fonte, (int) mb_strpos($fonte, 'private function tagsDoKit'));
    $tagsDoKit = mb_substr($tagsDoKit, 0, (int) mb_strpos($tagsDoKit, 'private function escolherDestino'));

    expect($tagsDoKit)->not->toContain('PISO_DE_EXIBICAO');
})->group('kit');

/*
 * A lista que filtra o diff é a UNIÃO da constante desta versão com a que a versão
 * DESTINO declara — lida do fonte dela por `git show`. A classe que roda é a da
 * instalação (a antiga); sem isso, diretório que só a lista nova cobre ficava para a
 * segunda rodada, e na 0.23.0 o projeto ficou sem boot entre as duas.
 *
 * Ver `wikis/specs/fix/kit-update-lista-do-destino/`. Os casos abaixo são o `04` daquela
 * wiki: CT-01…CT-03 (parser), CT-05…CT-07 (união), CT-08 (o fonte), CT-09 (docs).
 */

/** Fonte na forma da v0.22.3: comentário citando caminho, caminho comentado, e uma segunda constante depois. */
const FONTE_ANTIGA_DO_KIT_UPDATE = <<<'PHP'
    private const CAMINHOS_DO_KIT = [
        /*
         * Comentário citando 'app/Comentado' — não é declaração.
         */
        'app/Filament',
        // 'app/Desligado',
        'app/Support',
        'resources/views/errors',
        'config/kit.php',
    ];

    private const CAMINHOS_SO_RELATORIO = [
        'composer.json',
    ];
PHP;

it('extrai do fonte desta versão exatamente a lista da constante — a forma textual é contrato', function (): void {
    $fonte = (string) file_get_contents(base_path('app/Console/Commands/KitUpdate.php'));

    expect(KitUpdate::caminhosDeclaradosEm($fonte))->toBe(caminhosDoKit());
})->group('kit');

it('extrai de um fonte antigo só o que está declarado — comentário não é declaração, e para na primeira constante', function (): void {
    expect(KitUpdate::caminhosDeclaradosEm(FONTE_ANTIGA_DO_KIT_UPDATE))
        ->toBe(['app/Filament', 'app/Support', 'resources/views/errors', 'config/kit.php']);
})->group('kit');

it('devolve lista vazia quando o fonte não tem a constante em forma reconhecível', function (string $fonte): void {
    expect(KitUpdate::caminhosDeclaradosEm($fonte))->toBe([]);
})->with([
    'arquivo de outra classe' => ["<?php\n\nclass Outra\n{\n    private const OUTRA = [\n        'a/b',\n    ];\n}\n"],
    'forma irreconhecível'    => ["    private const CAMINHOS_DO_KIT = array_merge(self::A, self::B);\n"],
    'git show falhou'         => [''],
])->group('kit');

it('caminho que só o destino cobre entra na lista unida, junto com toda a constante', function (): void {
    expect(KitUpdate::caminhosUnidos(['resources/views/kit-prova']))
        ->toBe([...caminhosDoKit(), 'resources/views/kit-prova']);
})->group('kit');

it('caminho que só esta versão cobre não se perde quando o destino declara menos', function (): void {
    expect(KitUpdate::caminhosUnidos(['app/Filament']))->toBe(caminhosDoKit())
        ->and(KitUpdate::caminhosUnidos(['app/Filament']))->toContain('public/css/kit');
})->group('kit');

it('lista do destino vazia devolve exatamente a constante — sem repetição e reindexada', function (): void {
    $unida = KitUpdate::caminhosUnidos([]);

    expect($unida)->toBe(caminhosDoKit())
        ->and(array_keys($unida))->toBe(range(0, count($unida) - 1))
        ->and(array_unique($unida))->toHaveCount(count($unida));
})->group('kit');

/**
 * O que a suíte não consegue provar com git de verdade, prova no fonte: o diff usa a lista
 * unida (e não a constante direta), a lista do destino vem de `git show` do próprio
 * `KitUpdate.php`, e o parágrafo "rode de novo" é condicional — o de "comportamento
 * anterior" não. Ausência com comentários filtrados (`.ai/rules/testes.md`).
 */
it('filtra o diff pela lista unida lida do destino, e só manda rodar de novo quando ela faltou', function (): void {
    $fonte         = (string) file_get_contents(base_path('app/Console/Commands/KitUpdate.php'));
    $semComentario = (string) preg_replace('~^\s*//.*$~m', '', (string) preg_replace('~/\*.*?\*/~s', '', $fonte));

    $trecho = static function (string $texto, string $de, string $ate): string {
        $inicio = (int) mb_strpos($texto, $de);

        return mb_substr($texto, $inicio, (int) mb_strpos($texto, $ate, $inicio) - $inicio);
    };

    $arquivosAlterados = $trecho($semComentario, 'private function arquivosAlterados', 'private function caminhosDoKit');
    $caminhosDoKit     = $trecho($semComentario, 'private function caminhosDoKit', 'public static function caminhosUnidos');
    $encerrar          = $trecho($fonte, 'private function encerrar', 'private function marcarVersao');

    expect($arquivosAlterados)->toContain('$this->caminhosDoKit($destino)')
        ->not->toContain('self::CAMINHOS_DO_KIT')
        ->and($caminhosDoKit)->toContain('\'show\', "{$destino}:app/Console/Commands/KitUpdate.php"')
        ->and((int) mb_strpos($encerrar, 'comportamento da versão anterior'))
        ->toBeLessThan((int) mb_strpos($encerrar, 'if (! $this->listaDoDestinoLida)'))
        ->and((int) mb_strpos($encerrar, 'if (! $this->listaDoDestinoLida)'))
        ->toBeLessThan((int) mb_strpos($encerrar, 'RODE O COMANDO DE NOVO'));
})->group('kit');

it('documenta a lista do destino e o contorno para instalações anteriores, nos dois idiomas e no CHANGELOG', function (string $pagina, string $destino): void {
    $texto = (string) file_get_contents(base_path($pagina));

    expect($texto)->toContain($destino)
        ->toContain('svg.arte-do-login')
        ->toContain('0.22');

    $changelog  = (string) file_get_contents(base_path('CHANGELOG.md'));
    $naoLancado = mb_substr($changelog, (int) mb_strpos($changelog, '## [Unreleased]'));
    $naoLancado = mb_substr($naoLancado, 0, (int) mb_strpos($naoLancado, "\n## [", 1));

    expect($naoLancado)->toContain('kit:update')->toContain('destino');
})->with([
    'pt' => ['docs/pt/comecar/atualizando-o-projeto.md', 'versão destino'],
    'en' => ['docs/en/comecar/atualizando-o-projeto.md', 'target version'],
])->group('kit');
