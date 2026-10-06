<?php

use App\Console\Commands\KitUpdate;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;

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
    // Wiki login-unificado, CT-22: a migration de Settings e a resposta de login chegam a quem atualiza.
    'database/settings/2026_09_05_100000_add_login_unificado_to_kit_settings.php',
    'app/Http/Responses/RespostaDeLogin.php',
    // Issue #148: o override da lock-screen (par claro/escuro de logos) chega a quem atualiza.
    '[CT-01] override da lock-screen' => 'resources/views/vendor/filament-auth-designer/components/partials/media.blade.php',
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
    'database/settings',

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

    /*
     * `tests` entrou depois de a validacao da v0.39.0 medir que `tests/Browser` e
     * `tests/BrowserTenancy` NUNCA chegavam a quem atualiza: viajam no `create-project`
     * pelo `.gitattributes` e nao estavam em `CAMINHOS_DO_KIT`.
     *
     * E a varredura nao pegou pelo mesmo motivo de sempre: ela olhava `app`,
     * `database/*`, `resources/views` e `resources/css/filament`. Um diretorio de TESTE
     * novo era invisivel para ela — exatamente como um diretorio de VIEW novo era antes
     * da v0.23.0, tres linhas acima nesta mesma lista.
     *
     * `tests/Unit` e `tests/Feature` ficam de fora por `NAO_E_DO_KIT`: sao o esqueleto do
     * Laravel, o ponto de extensao de quem instala, e entrega-los sobrescreveria o teste
     * do usuario.
     */
    'tests',

    /*
     * `lang` entrou com a TERCEIRA ocorrencia da mesma classe: `lang/pt_BR.json` traduz as telas
     * de plugin de terceiro e nao estava em `CAMINHOS_DO_KIT`, entao quem atualiza continuava com
     * o /infra em ingles. A varredura nao pegou porque `lang` nao estava aqui — pelo mesmo motivo
     * de `resources/views/svg` (v0.23.0) e de `tests` (v0.39.1), tres e quatro linhas acima.
     *
     * `lang/pt_BR/` sai por `NAO_E_DO_KIT`: sao as traducoes padrao do Laravel.
     */
    'lang',
];

/**
 * Arquivos que moram nesses diretórios e NÃO são do kit.
 *
 * @var list<string>
 */
const NAO_E_DO_KIT = [
    // Do skeleton do Laravel, e ponto de extensão de quem instala.
    'app/Http/Controllers/Controller.php',

    /*
     * O esqueleto do Laravel, e o ponto de extensao de quem instala: entregar estes pelo
     * `kit:update` sobrescreveria o teste do usuario. Ficam fora de proposito.
     */
    'tests/Unit/ExampleTest.php',
    'tests/Feature/ExemploTest.php',

    /*
     * As traducoes PADRAO do Laravel, que quem instala pode ajustar ao proprio dominio.
     * O que e do kit e `lang/pt_BR.json`, esse sim entregue pelo `kit:update`.
     */
    'lang/pt_BR/actions.php',
    'lang/pt_BR/auth.php',
    'lang/pt_BR/http-statuses.php',
    'lang/pt_BR/pagination.php',
    'lang/pt_BR/passwords.php',
    'lang/pt_BR/validation.php',
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

            // `resources/views/vendor` não é decidido por esta varredura de caminho: é decidido
            // por CONTEÚDO, pasta a pasta, pelos casos CT-06/CT-07 deste arquivo — override
            // autoral entra em CAMINHOS_DO_KIT, publish cru idêntico ao pacote fica de fora (#148).
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

it('[CT-10] extrai do fonte desta versão exatamente a lista da constante — a forma textual é contrato', function (): void {
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

    /*
     * O CHANGELOG inteiro, não só a seção do topo.
     *
     * O recorte original só passava enquanto a versão desta feature fosse a mais recente: a
     * primeira seção `[Unreleased]` de qualquer entrega seguinte empurrava a v0.30.1 para baixo e
     * reprovava um caso que nada tem a ver com ela. O que este caso protege é "a entrega da lista
     * do destino está registrada no CHANGELOG" — e isso não expira.
     */
    $changelog = (string) file_get_contents(base_path('CHANGELOG.md'));

    expect($changelog)->toContain('kit:update')->toContain('destino');
})->with([
    'pt' => ['docs/pt/comecar/atualizando-o-projeto.md', 'versão destino'],
    'en' => ['docs/en/comecar/atualizando-o-projeto.md', 'target version'],
])->skip(fn (): bool => ! naArvoreDoKit(), 'O kit:update não entrega o site do kit: o diretório do site é export-ignore e não existe no projeto instalado.')->group('kit');

/*
|--------------------------------------------------------------------------
| resources/views/vendor: override autoral × publish cru (issue #148)
|--------------------------------------------------------------------------
|
| Casos de `wikis/specs/fix/kit-update-views-vendor/views-vendor-no-kit-update/04-casos-de-teste.md`.
| Uma pasta é AUTORAL quando ao menos um arquivo dela (recursivo, qualquer extensão) não é
| idêntico — fim de linha normalizado nos dois lados — a nenhum arquivo de mesmo caminho
| relativo nas views de qualquer pacote do vendor (`resources/views` de cada um); arquivo sem par é autoral, pasta vazia é crua.
| Autoral tem de estar em CAMINHOS_DO_KIT inteira; crua não pode estar coberta por forma nenhuma.
|
*/

/**
 * Todos os arquivos da pasta, recursivo, relativos a ela e sempre com `/`.
 *
 * @return list<string>
 */
function arquivosDaPastaDeViews(string $pasta): array
{
    $arquivos = [];

    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($pasta, FilesystemIterator::SKIP_DOTS)) as $arquivo) {
        if ($arquivo->isFile()) {
            $arquivos[] = str_replace('\\', '/', substr($arquivo->getPathname(), strlen($pasta) + 1));
        }
    }

    sort($arquivos);

    return $arquivos;
}

function conteudoComFimDeLinhaNormalizado(string $arquivo): string
{
    return str_replace("\r\n", "\n", (string) file_get_contents($arquivo));
}

/**
 * Classifica a pasta pelo conteúdo e devolve os arquivos que divergem de todo candidato.
 *
 * @return array{classe: 'autoral'|'cru', divergentes: list<string>}
 */
function classificarPastaDeViews(string $raizDasViews, string $raizDoVendor, string $pasta): array
{
    $divergentes = [];

    foreach (arquivosDaPastaDeViews($raizDasViews.'/'.$pasta) as $relativo) {
        $doKit      = conteudoComFimDeLinhaNormalizado($raizDasViews.'/'.$pasta.'/'.$relativo);
        $candidatos = glob($raizDoVendor.'/*/*/resources/views/'.$relativo) ?: [];

        $temIgual = array_filter(
            $candidatos,
            fn (string $candidato): bool => is_file($candidato) && conteudoComFimDeLinhaNormalizado($candidato) === $doKit,
        ) !== [];

        if (! $temIgual) {
            $divergentes[] = $relativo;
        }
    }

    return ['classe' => $divergentes === [] ? 'cru' : 'autoral', 'divergentes' => $divergentes];
}

/**
 * Confere a lista contra a autoria de cada pasta de views de pacote.
 *
 * @param  list<string>  $lista
 * @return array{classes: array<string, array{classe: 'autoral'|'cru', divergentes: list<string>}>, falhas: array<string, string>}
 */
function varrerViewsDeVendor(string $raizDasViews, string $raizDoVendor, array $lista): array
{
    $classes = [];
    $falhas  = [];

    $pastas = array_map('basename', glob($raizDasViews.'/*', GLOB_ONLYDIR) ?: []);
    sort($pastas);

    foreach ($pastas as $pasta) {
        $caminho         = 'resources/views/vendor/'.$pasta;
        $classificacao   = classificarPastaDeViews($raizDasViews, $raizDoVendor, $pasta);
        $classes[$pasta] = $classificacao;

        $inteira = false;
        $alguma  = false;

        foreach ($lista as $entrada) {
            $entrada = rtrim($entrada, '/');

            if ($caminho === $entrada || str_starts_with($caminho, $entrada.'/')) {
                $inteira = true;
                $alguma  = true;
            } elseif (str_starts_with($entrada, $caminho.'/')) {
                $alguma = true;
            }
        }

        if ($classificacao['classe'] === 'autoral' && ! $inteira) {
            $falhas[$pasta] = "A pasta `{$pasta}` é override AUTORAL (divergem do pacote instalado: "
                .implode(', ', $classificacao['divergentes'])
                .") e não está em CAMINHOS_DO_KIT: quem atualiza nunca a recebe. Liste `{$caminho}` em "
                .'KitUpdate::CAMINHOS_DO_KIT; ou, se for publish cru que o pacote atualizou, republique com '
                .'`php artisan vendor:publish` para voltar a ser idêntica ao pacote.';
        } elseif ($classificacao['classe'] === 'cru' && $alguma) {
            $falhas[$pasta] = "A pasta `{$pasta}` é publish cru, idêntica ao pacote instalado, e está coberta: "
                .'o kit:update sobrescreveria a customização do projeto. Saída: remover de CAMINHOS_DO_KIT '
                ."a entrada que cobre `{$caminho}`.";
        }
    }

    return ['classes' => $classes, 'falhas' => $falhas];
}

/**
 * Monta a árvore de fixture: `views/{pasta}/...` e `vendor/{fornecedor}/{pacote}/resources/views/...`.
 *
 * @param  array<string, array<string, string>>  $pastasDoKit  pasta => [relativo => conteúdo]
 * @param  array<string, array<string, string>>  $pacotes  fornecedor/pacote => [relativo => conteúdo]
 * @return array{0: string, 1: string}
 */
function arvoreDeViewsDeFixture(string $raiz, array $pastasDoKit, array $pacotes): array
{
    $escrever = function (string $arquivo, string $conteudo): void {
        if (! is_dir(dirname($arquivo))) {
            mkdir(dirname($arquivo), 0777, true);
        }

        file_put_contents($arquivo, $conteudo);
    };

    foreach ($pastasDoKit as $pasta => $arquivos) {
        if (! is_dir($raiz.'/views/'.$pasta)) {
            mkdir($raiz.'/views/'.$pasta, 0777, true);
        }

        foreach ($arquivos as $relativo => $conteudo) {
            $escrever($raiz.'/views/'.$pasta.'/'.$relativo, $conteudo);
        }
    }

    if (! is_dir($raiz.'/vendor')) {
        mkdir($raiz.'/vendor', 0777, true);
    }

    foreach ($pacotes as $pacote => $arquivos) {
        foreach ($arquivos as $relativo => $conteudo) {
            $escrever($raiz.'/vendor/'.$pacote.'/resources/views/'.$relativo, $conteudo);
        }
    }

    return [$raiz.'/views', $raiz.'/vendor'];
}

function raizTemporariaDeViews(): string
{
    $raiz = str_replace('\\', '/', sys_get_temp_dir()).'/kit-views-vendor-'.bin2hex(random_bytes(6));
    mkdir($raiz, 0777, true);

    return $raiz;
}

afterEach(function (): void {
    if (isset($this->raizTemporaria) && is_dir($this->raizTemporaria)) {
        File::deleteDirectory($this->raizTemporaria);
    }
});

const VIEW_DO_PACOTE = "<div>\n    {{ \$slot }}\n</div>\n";

it('[CT-02] a classificação de uma pasta de override segue o conteúdo das views', function (string $pasta, array $doKit, array $pacotes, string $classe): void {
    $this->raizTemporaria = raizTemporariaDeViews();

    [$views, $vendor] = arvoreDeViewsDeFixture($this->raizTemporaria, [$pasta => $doKit], $pacotes);

    expect(classificarPastaDeViews($views, $vendor, $pasta)['classe'])->toBe($classe);
})->with([
    'cru'      => ['cru', ['v.blade.php' => VIEW_DO_PACOTE], ['acme/cru' => ['v.blade.php' => VIEW_DO_PACOTE]], 'cru'],
    'crlf'     => ['crlf', ['v.blade.php' => "<div>\r\n    {{ \$slot }}\r\n</div>\r\n"], ['acme/crlf' => ['v.blade.php' => VIEW_DO_PACOTE]], 'cru'],
    'crlf-inv' => ['crlf-inv', ['v.blade.php' => VIEW_DO_PACOTE], ['acme/crlf-inv' => ['v.blade.php' => "<div>\r\n    {{ \$slot }}\r\n</div>\r\n"]], 'cru'],
    'byte'     => ['byte', ['v.blade.php' => "<div>\n    {{  \$slot }}\n</div>\n"], ['acme/byte' => ['v.blade.php' => VIEW_DO_PACOTE]], 'autoral'],
    'borda'    => ['borda', ['v.blade.php' => "<div> \n    {{ \$slot }}\n</div>\n\n"], ['acme/borda' => ['v.blade.php' => VIEW_DO_PACOTE]], 'autoral'],
    'mista'    => ['mista', [
        'a.blade.php' => "<a>\n",
        'b.blade.php' => "<b>editada</b>\n",
        'c.blade.php' => "<c>\n",
    ], ['acme/mista' => ['a.blade.php' => "<a>\n", 'b.blade.php' => "<b>\n", 'c.blade.php' => "<c>\n"]], 'autoral'],
    'extra'    => ['extra', ['a.blade.php' => "<a>\n", 'novo.blade.php' => "<novo>\n"], ['acme/extra' => ['a.blade.php' => "<a>\n"]], 'autoral'],
    'aninhada' => ['aninhada', [
        'x.blade.php'            => "<x>\n",
        'components/y.blade.php' => "<y>editada</y>\n",
    ], ['acme/aninhada' => ['x.blade.php' => "<x>\n", 'components/y.blade.php' => "<y>\n"]], 'autoral'],
    'svg'      => ['svg', ['icone.svg' => "<svg><path d=\"M1\"/></svg>\n"], ['acme/svg' => ['icone.svg' => "<svg><path d=\"M0\"/></svg>\n"]], 'autoral'],
    'outro'    => ['outro', ['v.blade.php' => VIEW_DO_PACOTE], ['acme/outro-nome' => ['v.blade.php' => VIEW_DO_PACOTE]], 'cru'],
    'vazia'    => ['vazia', [], [], 'cru'],
    'sopar'    => ['sopar', ['so-do-kit.blade.php' => "<p>só do kit</p>\n"], [], 'autoral'],
    'painel'   => ['painel', ['p.blade.php' => VIEW_DO_PACOTE], [
        'acme/a-diferente' => ['p.blade.php' => "<section>outra</section>\n"],
        'acme/z-igual'     => ['p.blade.php' => VIEW_DO_PACOTE],
    ], 'cru'],
    'painel2'  => ['painel2', ['p.blade.php' => VIEW_DO_PACOTE], [
        'acme/a-igual'     => ['p.blade.php' => VIEW_DO_PACOTE],
        'acme/z-diferente' => ['p.blade.php' => "<section>outra</section>\n"],
    ], 'cru'],
])->group('kit');

it('[CT-03] publish cru que o pacote atualizou é acusado como autoral fora da lista, com as duas saídas e só os arquivos que divergem', function (): void {
    $this->raizTemporaria = raizTemporariaDeViews();

    [$views, $vendor] = arvoreDeViewsDeFixture($this->raizTemporaria, [
        'drift' => ['painel.blade.php' => "<div>versão antiga</div>\n", 'outro.blade.php' => "<p>igual</p>\n"],
    ], [
        'acme/drift' => ['painel.blade.php' => "<div>versão nova</div>\n", 'outro.blade.php' => "<p>igual</p>\n"],
    ]);

    $falhas = varrerViewsDeVendor($views, $vendor, ['resources/views/outra-coisa'])['falhas'];

    expect($falhas)->toHaveKey('drift');

    $mensagem = $falhas['drift'];

    expect($mensagem)->toContain('drift', 'painel.blade.php', 'CAMINHOS_DO_KIT', 'vendor:publish');
    $this->assertStringNotContainsString('outro.blade.php', $mensagem, 'a mensagem só pode citar os arquivos que divergem');
})->group('kit');

it('[CT-04] publish cru coberto pela lista reprova, por qualquer forma de entrada, com a saída de remover', function (string $entrada): void {
    $this->raizTemporaria = raizTemporariaDeViews();

    [$views, $vendor] = arvoreDeViewsDeFixture($this->raizTemporaria, [
        'crua' => ['painel.blade.php' => VIEW_DO_PACOTE],
    ], [
        'acme/crua' => ['painel.blade.php' => VIEW_DO_PACOTE],
    ]);

    $falhas = varrerViewsDeVendor($views, $vendor, [$entrada])['falhas'];

    expect($falhas)->toHaveKey('crua');

    $mensagem = $falhas['crua'];

    expect($mensagem)->toContain('crua', 'remover de CAMINHOS_DO_KIT');
    $this->assertStringNotContainsStringIgnoringCase('liste', $mensagem, 'a saída do publish cru coberto é remover, não listar');
})->with([
    'exata'     => ['resources/views/vendor/crua'],
    'ancestral' => ['resources/views/vendor'],
    'arquivo'   => ['resources/views/vendor/crua/painel.blade.php'],
])->group('kit');

it('[CT-05] lista coerente com a autoria aprova', function (): void {
    $this->raizTemporaria = raizTemporariaDeViews();

    [$views, $vendor] = arvoreDeViewsDeFixture($this->raizTemporaria, [
        'editada' => ['v.blade.php' => "<div>editada pelo kit</div>\n"],
        'crua'    => ['v.blade.php' => VIEW_DO_PACOTE],
    ], [
        'acme/editada' => ['v.blade.php' => VIEW_DO_PACOTE],
        'acme/crua'    => ['v.blade.php' => VIEW_DO_PACOTE],
    ]);

    $varredura = varrerViewsDeVendor($views, $vendor, ['resources/views/vendor/editada']);

    expect($varredura['falhas'])->toBe([])
        ->and($varredura['classes']['editada']['classe'])->toBe('autoral')
        ->and($varredura['classes']['crua']['classe'])->toBe('cru');
})->group('kit');

it('[CT-12] pasta autoral sem pacote instalado e fora da lista reprova', function (): void {
    $this->raizTemporaria = raizTemporariaDeViews();

    [$views, $vendor] = arvoreDeViewsDeFixture($this->raizTemporaria, [
        'sopar' => ['so-do-kit.blade.php' => "<p>só do kit</p>\n"],
    ], []);

    $falhas = varrerViewsDeVendor($views, $vendor, ['resources/views/vendor/outra'])['falhas'];

    expect($falhas)->toHaveKey('sopar')
        ->and($falhas['sopar'])->toContain('sopar', 'CAMINHOS_DO_KIT');
})->group('kit');

it('[CT-06] toda pasta autoral está coberta inteira, inclusive por arquivo que ainda não existe', function (): void {
    $varredura = varrerViewsDeVendor(base_path('resources/views/vendor'), base_path('vendor'), caminhosDoKit());

    $descobertos = [];

    foreach ($varredura['classes'] as $pasta => $classificacao) {
        if ($classificacao['classe'] !== 'autoral') {
            continue;
        }

        $arquivos = [...arquivosDaPastaDeViews(base_path('resources/views/vendor/'.$pasta)), 'novo-arquivo-sonda.blade.php'];

        foreach ($arquivos as $relativo) {
            $caminho = "resources/views/vendor/{$pasta}/{$relativo}";

            if (! estaCoberto($caminho)) {
                $descobertos[] = $caminho;
            }
        }
    }

    expect($descobertos)->toBe([], "Override autoral fora de KitUpdate::CAMINHOS_DO_KIT:\n  "
        .implode("\n  ", $descobertos)."\n\n".implode("\n", $varredura['falhas']));
})->skip(fn (): bool => ! naArvoreDoKit(), 'Projeto instalado: resources/views/vendor tem os publishes do próprio projeto, que não são do kit — a autoria só se decide na árvore do kit.')->group('kit');

it('[CT-07] nenhuma view de pasta publish cru está coberta', function (): void {
    $varredura = varrerViewsDeVendor(base_path('resources/views/vendor'), base_path('vendor'), caminhosDoKit());

    $cobertas = [];

    foreach ($varredura['classes'] as $pasta => $classificacao) {
        if ($classificacao['classe'] !== 'cru') {
            continue;
        }

        foreach (arquivosDaPastaDeViews(base_path('resources/views/vendor/'.$pasta)) as $relativo) {
            $caminho = "resources/views/vendor/{$pasta}/{$relativo}";

            if (estaCoberto($caminho)) {
                $cobertas[] = $caminho;
            }
        }
    }

    expect($cobertas)->toBe([], "Publish cru coberto por KitUpdate::CAMINHOS_DO_KIT:\n  "
        .implode("\n  ", $cobertas)."\n\n".implode("\n", $varredura['falhas']));
})->skip(fn (): bool => ! naArvoreDoKit(), 'Projeto instalado: resources/views/vendor tem os publishes do próprio projeto, que não são do kit — a autoria só se decide na árvore do kit.')->group('kit');

it('[CT-08] a varredura examina todas as pastas da árvore real', function (): void {
    $diretorios = glob(base_path('resources/views/vendor').'/*', GLOB_ONLYDIR) ?: [];

    $varredura = varrerViewsDeVendor(base_path('resources/views/vendor'), base_path('vendor'), caminhosDoKit());

    expect(count($diretorios))->toBeGreaterThan(0)
        ->and(count($varredura['classes']))->toBe(count($diretorios));
})->skip(fn (): bool => ! naArvoreDoKit(), 'Projeto instalado: resources/views/vendor tem os publishes do próprio projeto, que não são do kit — a autoria só se decide na árvore do kit.')->group('kit');

it('[CT-11] o CHANGELOG registra a entrega de resources/views/vendor e o issue #148', function (): void {
    $changelog = (string) file_get_contents(base_path('CHANGELOG.md'));

    expect($changelog)->toContain('#148', 'resources/views/vendor');
})->skip(fn (): bool => ! naArvoreDoKit(), 'CHANGELOG.md é export-ignore (.gitattributes) e não existe no projeto instalado.')->group('kit');

it('[CT-13] a guarda da árvore está declarada no fonte dos casos da árvore real', function (string $id): void {
    $fonte = (string) file_get_contents(__FILE__);

    $inicio = mb_strpos($fonte, "it('[".$id.']');

    expect($inicio)->not->toBeFalse("a declaração de {$id} não foi encontrada no fonte");

    $fim        = mb_strpos($fonte, "\nit(", (int) $inicio + 1);
    $declaracao = mb_substr($fonte, (int) $inicio, $fim === false ? null : $fim - (int) $inicio);
    $semEspacos = (string) preg_replace('/\s+/', '', $declaracao);

    expect(preg_match("/->skip\\(fn \\(\\): bool => ! naArvoreDoKit\\(\\), '[^']+'\\)/", $declaracao))
        ->toBe(1, "{$id} precisa pular com `! naArvoreDoKit()` e motivo não vazio")
        ->and(str_contains($semEspacos, 'expect(true)->toBeTrue();return;'))
        ->toBeFalse("{$id} não pode usar `expect(true)->toBeTrue(); return;` como guarda");
})->with(['CT-06', 'CT-07', 'CT-08'])->group('kit');

it('[CT-14] a entrada-pasta entrega o arquivo aninhado a uma árvore que não o tinha', function (): void {
    $this->raizTemporaria = raizTemporariaDeViews();

    $alvo = 'resources/views/vendor/filament-auth-designer/components/partials/media.blade.php';

    expect(file_exists($this->raizTemporaria.'/resources/views/vendor/filament-auth-designer'))->toBeFalse();

    $listaAntiga = array_values(array_filter(
        caminhosDoKit(),
        fn (string $caminho): bool => ! str_starts_with($caminho, 'resources/views/vendor/'),
    ));

    $entradas = array_values(array_filter(
        KitUpdate::caminhosUnidos($listaAntiga),
        fn (string $caminho): bool => str_starts_with($caminho, 'resources/views/vendor/'),
    ));

    expect($entradas)->not->toBeEmpty();

    $comando = 'git archive HEAD -- '.implode(' ', array_map('escapeshellarg', $entradas))
        .' | tar -x -f - -C '.escapeshellarg($this->raizTemporaria);

    $processo = Process::fromShellCommandline($comando, base_path(), timeout: 120);
    $processo->run();

    expect($processo->isSuccessful())->toBeTrue('git archive | tar falhou: '.$processo->getErrorOutput());

    $extraido = $this->raizTemporaria.'/'.$alvo;

    expect(is_file($extraido))->toBeTrue("{$alvo} não chegou à árvore temporária")
        ->and(conteudoComFimDeLinhaNormalizado($extraido))->toBe(conteudoComFimDeLinhaNormalizado(base_path($alvo)));
})->skip(fn (): bool => ! naArvoreDoKit(), 'Precisa do git do kit: o projeto instalado não tem o histórico do kit para o git archive.')->group('kit');
