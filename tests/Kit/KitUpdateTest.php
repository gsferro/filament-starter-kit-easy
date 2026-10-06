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
 * Os arquivos RASTREADOS pelo git da pasta, recursivo, relativos a ela e sempre com `/`.
 *
 * Na árvore real do kit só o que está no repositório decide a classe (RD-06): arquivo local
 * não rastreado numa pasta crua não vira override autoral. `git ls-files` roda com a pasta
 * como diretório corrente, então devolve caminhos relativos a ela.
 *
 * @return list<string>
 */
function arquivosRastreadosDaPastaDeViews(string $pasta): array
{
    if (! is_dir($pasta)) {
        return [];
    }

    $processo = new Process(['git', '-c', 'core.quotepath=off', 'ls-files', '-z', '--', '.'], $pasta, timeout: 60);
    $processo->mustRun();

    $arquivos = array_values(array_filter(
        explode("\0", $processo->getOutput()),
        fn (string $arquivo): bool => $arquivo !== '',
    ));

    sort($arquivos);

    return $arquivos;
}

/**
 * Todos os arquivos da pasta, recursivo, relativos a ela e sempre com `/`.
 *
 * Com `$rastreados`, só os arquivos que o git rastreia (árvore real); sem, o disco (fixture,
 * que não é repositório git).
 *
 * @return list<string>
 */
function arquivosDaPastaDeViews(string $pasta, bool $rastreados = false): array
{
    if ($rastreados) {
        return arquivosRastreadosDaPastaDeViews($pasta);
    }

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
 * `comPacote` diz se ao menos um arquivo da pasta tem par em algum pacote instalado: sem
 * nenhum, a pasta é órfã e não há o que republicar com `vendor:publish`.
 *
 * @return array{classe: 'autoral'|'cru', divergentes: list<string>, comPacote: bool}
 */
function classificarPastaDeViews(string $raizDasViews, string $raizDoVendor, string $pasta, bool $rastreados = false): array
{
    $divergentes = [];
    $comPacote   = false;

    foreach (arquivosDaPastaDeViews($raizDasViews.'/'.$pasta, $rastreados) as $relativo) {
        $doKit      = conteudoComFimDeLinhaNormalizado($raizDasViews.'/'.$pasta.'/'.$relativo);
        $candidatos = array_values(array_filter(glob($raizDoVendor.'/*/*/resources/views/'.$relativo) ?: [], 'is_file'));

        if ($candidatos !== []) {
            $comPacote = true;
        }

        $temIgual = array_filter(
            $candidatos,
            fn (string $candidato): bool => conteudoComFimDeLinhaNormalizado($candidato) === $doKit,
        ) !== [];

        if (! $temIgual) {
            $divergentes[] = $relativo;
        }
    }

    return ['classe' => $divergentes === [] ? 'cru' : 'autoral', 'divergentes' => $divergentes, 'comPacote' => $comPacote];
}

/**
 * As pastas de views de pacote a examinar: no disco (fixture) ou as que têm arquivo rastreado
 * (árvore real).
 *
 * @return list<string>
 */
function pastasDeViewsDeVendor(string $raizDasViews, bool $rastreados): array
{
    if ($rastreados) {
        $pastas = array_values(array_unique(array_map(
            fn (string $arquivo): string => explode('/', $arquivo)[0],
            array_filter(arquivosRastreadosDaPastaDeViews($raizDasViews), fn (string $arquivo): bool => str_contains($arquivo, '/')),
        )));
    } else {
        $pastas = array_map('basename', glob($raizDasViews.'/*', GLOB_ONLYDIR) ?: []);
    }

    sort($pastas);

    return $pastas;
}

/**
 * Confere a lista contra a autoria de cada pasta de views de pacote.
 *
 * Com `$rastreados`, as pastas e os arquivos vêm de `git ls-files` (árvore real); sem, do disco.
 *
 * A saída da reprovação depende da célula: autoral fora da lista com pacote instalado oferece
 * listar ou republicar; sem pacote, listar ou apagar a órfã; publish cru coberto pela própria
 * entrada (exata ou de arquivo) manda remover, e coberto por entrada ANCESTRAL manda estreitá-la —
 * remover a ancestral levaria junto toda pasta autoral (RD-05).
 *
 * @param  list<string>  $lista
 * @return array{classes: array<string, array{classe: 'autoral'|'cru', divergentes: list<string>, comPacote: bool}>, falhas: array<string, string>}
 */
function varrerViewsDeVendor(string $raizDasViews, string $raizDoVendor, array $lista, bool $rastreados = false): array
{
    $classes = [];
    $falhas  = [];

    foreach (pastasDeViewsDeVendor($raizDasViews, $rastreados) as $pasta) {
        $caminho         = 'resources/views/vendor/'.$pasta;
        $classificacao   = classificarPastaDeViews($raizDasViews, $raizDoVendor, $pasta, $rastreados);
        $classes[$pasta] = $classificacao;

        $inteira   = false;
        $alguma    = false;
        $ancestral = null;

        foreach ($lista as $entrada) {
            $entrada = rtrim($entrada, '/');

            if ($caminho === $entrada) {
                $inteira = true;
                $alguma  = true;
            } elseif (str_starts_with($caminho, $entrada.'/')) {
                $inteira   = true;
                $alguma    = true;
                $ancestral = $entrada;
            } elseif (str_starts_with($entrada, $caminho.'/')) {
                $alguma = true;
            }
        }

        if ($classificacao['classe'] === 'autoral' && ! $inteira) {
            $mensagem = "A pasta `{$pasta}` é override AUTORAL (divergem do pacote instalado: "
                .implode(', ', $classificacao['divergentes'])
                .") e não está em CAMINHOS_DO_KIT: quem atualiza nunca a recebe. Liste `{$caminho}` em "
                .'KitUpdate::CAMINHOS_DO_KIT; ';

            $falhas[$pasta] = $mensagem.($classificacao['comPacote']
                ? 'ou, se for publish cru que o pacote atualizou, republique com `php artisan vendor:publish` '
                    .'para voltar a ser idêntica ao pacote.'
                : 'ou apague a pasta órfã, se o pacote saiu do composer.json.');
        } elseif ($classificacao['classe'] === 'cru' && $alguma) {
            $falhas[$pasta] = "A pasta `{$pasta}` é publish cru, idêntica ao pacote instalado, e está coberta: "
                .'o kit:update sobrescreveria a customização do projeto. Saída: '
                .($ancestral !== null
                    ? "estreite a entrada ancestral para as pastas autorais (`{$ancestral}` cobre `{$caminho}`)."
                    : "remover de CAMINHOS_DO_KIT a entrada que cobre `{$caminho}`.");
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

it('[CT-04] publish cru coberto pela lista reprova, por qualquer forma de entrada, com a saída certa para a forma', function (string $entrada, string $saida): void {
    $this->raizTemporaria = raizTemporariaDeViews();

    [$views, $vendor] = arvoreDeViewsDeFixture($this->raizTemporaria, [
        'crua' => ['painel.blade.php' => VIEW_DO_PACOTE],
    ], [
        'acme/crua' => ['painel.blade.php' => VIEW_DO_PACOTE],
    ]);

    $falhas = varrerViewsDeVendor($views, $vendor, [$entrada])['falhas'];

    expect($falhas)->toHaveKey('crua');

    $mensagem = $falhas['crua'];

    expect($mensagem)->toContain('crua', $saida);
    $this->assertStringNotContainsStringIgnoringCase('liste', $mensagem, 'a saída do publish cru coberto não é listar');
})->with([
    'exata'     => ['resources/views/vendor/crua', 'remover de CAMINHOS_DO_KIT'],
    'ancestral' => ['resources/views/vendor', 'estreite a entrada ancestral para as pastas autorais'],
    'arquivo'   => ['resources/views/vendor/crua/painel.blade.php', 'remover de CAMINHOS_DO_KIT'],
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

it('[CT-12] pasta autoral sem pacote instalado e fora da lista reprova, oferecendo listar ou apagar a órfã', function (): void {
    $this->raizTemporaria = raizTemporariaDeViews();

    [$views, $vendor] = arvoreDeViewsDeFixture($this->raizTemporaria, [
        'sopar' => ['so-do-kit.blade.php' => "<p>só do kit</p>\n"],
    ], []);

    $falhas = varrerViewsDeVendor($views, $vendor, ['resources/views/vendor/outra'])['falhas'];

    expect($falhas)->toHaveKey('sopar')
        ->and($falhas['sopar'])->toContain('sopar', 'CAMINHOS_DO_KIT', 'apague a pasta órfã, se o pacote saiu do composer.json');
    $this->assertStringNotContainsString('vendor:publish', $falhas['sopar'], 'sem pacote instalado não há o que republicar');
})->group('kit');

it('[CT-06] toda pasta autoral está coberta inteira, inclusive por arquivo que ainda não existe', function (): void {
    $varredura = varrerViewsDeVendor(base_path('resources/views/vendor'), base_path('vendor'), caminhosDoKit(), rastreados: true);

    $descobertos = [];

    foreach ($varredura['classes'] as $pasta => $classificacao) {
        if ($classificacao['classe'] !== 'autoral') {
            continue;
        }

        $arquivos = [...arquivosDaPastaDeViews(base_path('resources/views/vendor/'.$pasta), rastreados: true), 'novo-arquivo-sonda.blade.php'];

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
    $varredura = varrerViewsDeVendor(base_path('resources/views/vendor'), base_path('vendor'), caminhosDoKit(), rastreados: true);

    $cobertas = [];

    foreach ($varredura['classes'] as $pasta => $classificacao) {
        if ($classificacao['classe'] !== 'cru') {
            continue;
        }

        foreach (arquivosDaPastaDeViews(base_path('resources/views/vendor/'.$pasta), rastreados: true) as $relativo) {
            $caminho = "resources/views/vendor/{$pasta}/{$relativo}";

            if (estaCoberto($caminho)) {
                $cobertas[] = $caminho;
            }
        }
    }

    expect($cobertas)->toBe([], "Publish cru coberto por KitUpdate::CAMINHOS_DO_KIT:\n  "
        .implode("\n  ", $cobertas)."\n\n".implode("\n", $varredura['falhas']));
})->skip(fn (): bool => ! naArvoreDoKit(), 'Projeto instalado: resources/views/vendor tem os publishes do próprio projeto, que não são do kit — a autoria só se decide na árvore do kit.')->group('kit');

it('[CT-08] a varredura examina todas as pastas da árvore real e classifica a da lock-screen como autoral', function (): void {
    /*
     * Referência independente da varredura: `scandir` dos diretórios, e cada um conta se o git
     * rastreia ao menos um arquivo dentro dele. Nunca o mesmo glob/enumerador da varredura,
     * senão os dois concordariam com o mesmo erro (M37).
     */
    $raiz       = base_path('resources/views/vendor');
    $diretorios = array_values(array_filter(
        scandir($raiz) ?: [],
        fn (string $nome): bool => $nome !== '.' && $nome !== '..' && is_dir($raiz.'/'.$nome),
    ));

    $comArquivoRastreado = array_values(array_filter($diretorios, function (string $nome): bool {
        $processo = new Process(['git', 'ls-files', '--', 'resources/views/vendor/'.$nome], base_path(), timeout: 60);
        $processo->mustRun();

        return trim($processo->getOutput()) !== '';
    }));

    $varredura = varrerViewsDeVendor(base_path('resources/views/vendor'), base_path('vendor'), caminhosDoKit(), rastreados: true);

    expect(count($comArquivoRastreado))->toBeGreaterThan(0)
        ->and(count($varredura['classes']))->toBe(count($comArquivoRastreado))
        ->and($varredura['classes'])->toHaveKey('filament-auth-designer')
        ->and($varredura['classes']['filament-auth-designer']['classe'] ?? null)->toBe('autoral');
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

it('[CT-14] a entrada-pasta extrai o arquivo aninhado', function (): void {
    $this->raizTemporaria = raizTemporariaDeViews();

    $alvo = 'resources/views/vendor/filament-auth-designer/components/partials/media.blade.php';

    expect(file_exists($this->raizTemporaria.'/resources/views/vendor/filament-auth-designer'))->toBeFalse();

    $entradas = array_values(array_filter(
        caminhosDoKit(),
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

/*
|--------------------------------------------------------------------------
| R8 — a entrada nova na lista é comparada tag de destino × árvore do projeto
|--------------------------------------------------------------------------
|
| CT-15/CT-16 do mesmo `04`. As duas peças puras do `kit:update` chamadas direto, sem git
| nem árvore; o comando inteiro é o procedimento CT-17, evidência do `03`.
|
*/

it('[CT-15] entrada nova é a que está na lista do destino e não na da origem', function (array $destino, array $origem, array $novas): void {
    expect(KitUpdate::caminhosNovosNaLista($destino, $origem))->toBe($novas);
})->with([
    'entradas a mais, não contíguas' => [
        ['app', 'resources/views/vendor/fad', 'config/kit.php', 'lang/x'],
        ['app', 'config/kit.php'],
        [0 => 'resources/views/vendor/fad', 1 => 'lang/x'],
    ],
    'listas iguais' => [
        ['app', 'config/kit.php'],
        ['app', 'config/kit.php'],
        [],
    ],
    'origem não lida não vira "tudo é novo"' => [
        ['app', 'resources/views/vendor/fad'],
        [],
        [],
    ],
    'outra ordem e caminho só na origem' => [
        ['app', 'config/kit.php'],
        ['config/kit.php', 'app', 'routes'],
        [],
    ],
])->group('kit');

/*
 * A coluna `existeNoProjeto` é a resposta do callable (P-08), ou `null` para chamar sem ele.
 * Vai como bool, e não como closure: o Pest resolve closure de dataset antes de passar.
 */
it('[CT-16] a saída do git diff --name-status vira rótulo conforme haja origem', function (string $saida, bool $comOrigem, array $rotulos, ?bool $existeNoProjeto = null): void {
    $rotulado = $existeNoProjeto === null
        ? KitUpdate::rotularDiff($saida, $comOrigem)
        : KitUpdate::rotularDiff($saida, $comOrigem, fn (string $caminho): bool => $existeNoProjeto);

    expect($rotulado)->toBe($rotulos);
})->with([
    'com origem: A, M, D, fora de ordem' => [
        "M\tp/b.php\nA\tp/a.php\nD\tp/c.php\n",
        true,
        ['p/a.php' => 'novo no kit', 'p/b.php' => 'modificado', 'p/c.php' => 'removido do kit'],
    ],
    'sem origem: D, M, A ignorado' => [
        "D\tp/falta.php\nM\tp/dif.php\nA\tp/so-projeto.php\n",
        false,
        ['p/dif.php' => 'modificado', 'p/falta.php' => 'novo no kit'],
    ],
    'outra letra e linha em branco final' => [
        "T\tp/link.php\n\n",
        false,
        ['p/link.php' => 'modificado'],
    ],
    'saída vazia' => [
        '',
        true,
        [],
    ],
    'P-08: M com origem e arquivo ausente no projeto' => [
        "M\tresources/views/vendor/x/a.blade.php\n",
        true,
        ['resources/views/vendor/x/a.blade.php' => 'novo no kit'],
        false,
    ],
    'P-08: M com origem e arquivo presente no projeto' => [
        "M\tresources/views/vendor/x/a.blade.php\n",
        true,
        ['resources/views/vendor/x/a.blade.php' => 'modificado'],
        true,
    ],
    'P-08: D com origem e arquivo ausente segue removido do kit' => [
        "D\tresources/views/vendor/x/a.blade.php\n",
        true,
        ['resources/views/vendor/x/a.blade.php' => 'removido do kit'],
        false,
    ],
])->group('kit');

/*
| R9 — CT-18, só a parte automatizável: o diff da tag anterior até HEAD traz as dez views
| autorais e nenhuma de pasta crua. O `kit:update --dry-run` com a classe antiga continua
| procedimento da sessão (evidência do `03`).
*/

/** @var list<string> */
const VIEWS_AUTORAIS_DESTA_RELEASE = [
    'resources/views/vendor/asmit-resized-column/sticky-panel.blade.php',
    'resources/views/vendor/command-center/components/output.blade.php',
    'resources/views/vendor/command-center/pages/commands.blade.php',
    'resources/views/vendor/command-center/pages/run.blade.php',
    'resources/views/vendor/filament-auth-designer/components/partials/media.blade.php',
    'resources/views/vendor/filament-captcha/drivers/hcaptcha.blade.php',
    'resources/views/vendor/filament-captcha/drivers/recaptcha-v2.blade.php',
    'resources/views/vendor/filament-captcha/drivers/recaptcha-v3.blade.php',
    'resources/views/vendor/filament-captcha/drivers/turnstile.blade.php',
    'resources/views/vendor/filament-clear-cache/livewire/clear-cache-button.blade.php',
];

/** O checkout raso do CI (`actions/checkout` sem tags) não traz a tag anterior (QA-02). */
function tagAnteriorNoCheckout(): bool
{
    $processo = new Process(['git', 'rev-parse', '--verify', '--quiet', 'v0.45.0^{commit}'], base_path(), timeout: 60);
    $processo->run();

    return $processo->isSuccessful();
}

it('[CT-18] cada view autoral difere da tag anterior, e nenhuma de pasta crua', function (): void {
    $processo = new Process(['git', '-c', 'core.quotepath=off', 'diff', '--name-only', 'v0.45.0', 'HEAD', '--', 'resources/views/vendor'], base_path(), timeout: 120);
    $processo->run();

    expect($processo->isSuccessful())->toBeTrue('git diff falhou: '.$processo->getErrorOutput());

    $alterados = array_values(array_filter(
        array_map('trim', explode("\n", str_replace("\r\n", "\n", $processo->getOutput()))),
        fn (string $linha): bool => $linha !== '',
    ));

    expect(array_values(array_diff(VIEWS_AUTORAIS_DESTA_RELEASE, $alterados)))
        ->toBe([], 'views autorais que não diferem de v0.45.0 — a classe antiga do kit:update não as entrega');

    $raizDasViews = base_path('resources/views/vendor');
    $classes      = [];
    $deCrua       = [];

    foreach ($alterados as $caminho) {
        $pasta = explode('/', substr($caminho, strlen('resources/views/vendor/')))[0];

        $classes[$pasta] ??= is_dir($raizDasViews.'/'.$pasta)
            ? classificarPastaDeViews($raizDasViews, base_path('vendor'), $pasta, rastreados: true)['classe']
            : null;

        if ($classes[$pasta] === 'cru') {
            $deCrua[] = $caminho;
        }
    }

    expect($deCrua)->toBe([], 'o diff da release toca views de pasta publish cru');
})
    ->skip(fn (): bool => ! naArvoreDoKit(), 'a tag anterior só existe no git do kit')
    ->skip(fn (): bool => ! tagAnteriorNoCheckout(), 'a tag anterior v0.45.0 não está neste checkout (checkout raso do CI)')
    ->group('kit');
