<?php

/**
 * A esteira de qualidade do kit — e a fronteira entre o que roda sempre e o que roda
 * sob demanda.
 *
 * São quatro ferramentas em quatro eixos:
 *
 *   pint       estilo         corrige   ← gate
 *   phpstan    tipos          reporta   ← gate (level 8)
 *   filacheck  API Filament   reporta   ← gate
 *   rector     reescrita      MUDA      ← sob demanda, NUNCA no gate
 *
 * Este arquivo protege a quarta linha. Ela é a única contraintuitiva, e a única cuja
 * violação passaria despercebida: um Rector no `composer test` deixa o build verde na
 * primeira rodada e começa a brigar com o PHPStan na segunda.
 *
 * Ver ADR-02 em wikis/specs/feature/v1-enriquecimento-kit/rector/.
 */
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;
use Monolog\Handler\NullHandler;

beforeEach(function (): void {
    $this->composer = json_decode((string) file_get_contents(base_path('composer.json')), true);
    $this->rector   = (string) file_get_contents(base_path('rector.php'));
});

it('mantém o rector fora do composer test', function (): void {
    $test = implode(' ', $this->composer['scripts']['test'] ?? []);

    expect($test)->not->toContain('rector')
        ->and($test)->not->toContain('refactor');
})->group('kit');

/**
 * O gate continua sendo exatamente três.
 *
 * O caso é sobre a COMPOSIÇÃO, não sobre cada um: acrescentar uma quarta ferramenta ao
 * `composer test` é decisão de arquitetura, e este caso obriga quem fizer isso a passar
 * por aqui — e, ao passar, a ler o porquê da ausência do Rector.
 */
it('mantém os três gates do composer test', function (): void {
    expect($this->composer['scripts']['test'] ?? [])
        ->toContain('@lint:check')
        ->toContain('@types:check')
        ->toContain('@filament:check');
})->group('kit');

it('expõe o rector como comando sob demanda', function (string $script): void {
    expect($this->composer['scripts'][$script] ?? null)->not->toBeNull();
})->with(['refactor:preview', 'refactor:apply'])->group('kit');

/**
 * O `rector.php` nasce sem set, e é isso que o mantém inofensivo.
 *
 * Com os sets de qualidade ligados ele reescreveria 103 arquivos deste projeto, e um
 * deles — `CarbonToDateFacadeRector` — reintroduziria o TypeError que o PHPStan level 7
 * pegou no `InfraPanelProvider`: `now()` é `Date::now()`, o kit faz
 * `Date::use(CarbonImmutable)`, e o `modelPruneInterval()` exige Carbon mutável.
 *
 * Ligar um set no momento do upgrade é o uso correto — e aí este caso é o lembrete de
 * desligá-lo depois.
 */
it('não liga nenhum set de qualidade no rector.php', function (string $set): void {
    // Só as ocorrências FORA de comentário contam: o arquivo cita os sets no bloco de
    // instruções, de propósito, e citar não é ligar.
    $codigo = preg_replace('~/\*.*?\*/~s', '', $this->rector) ?? '';

    expect($codigo)->not->toContain($set);
})->with([
    'LARAVEL_CODE_QUALITY',
    'LARAVEL_COLLECTION',
    'LARAVEL_IF_HELPERS',
    'LARAVEL_ELOQUENT_MAGIC_METHOD_TO_QUERY_BUILDER',
    'LARAVEL_TYPE_DECLARATIONS',
    'withPhpSets',
])->group('kit');

/**
 * Quando Rector e PHPStan discordam, o PHPStan vence — e o desempate mora no config.
 *
 * `CarbonToDateFacadeRector` troca `Carbon::now()` por `Date::now()`. Neste projeto isso
 * quebra: `now()` É `Date::now()` (helpers.php:623), o kit faz
 * `Date::use(CarbonImmutable)` (KitServiceProvider:57), e o `modelPruneInterval()` do
 * filament-exceptions exige Carbon MUTÁVEL. O PHPStan level 7 já reportou esse TypeError
 * nesta base.
 *
 * O skip vale SEMPRE, inclusive durante um upgrade de major — que é o único momento em
 * que os sets são ligados, e portanto o único em que o conflito apareceria. Medido: com
 * `LARAVEL_CODE_QUALITY` ligado e este skip presente, as ocorrências da regra vão de 7
 * para 0.
 */
it('desliga no rector as regras que conflitam com o phpstan', function (): void {
    expect($this->rector)->toContain('CarbonToDateFacadeRector::class');
})->group('kit');

/**
 * O upgrade de Filament é ferramenta do próprio Filament.
 *
 * Não existe regra de Filament no `driftingly/rector-laravel` — ele cobre Laravel e só.
 * O `filament/upgrade` é o caminho oficial, também baseado em Rector, mantido em lockstep
 * com o framework.
 *
 * Cuidado com a confusão de nomes: `php artisan filament:upgrade` é OUTRA coisa (o comando
 * do próprio Filament que republica assets, já presente no `post-autoload-dump`). O script
 * aqui chama o binário `filament-v5`.
 */
it('expõe o upgrade oficial do filament como comando', function (): void {
    expect($this->composer['scripts']['upgrade:filament'] ?? null)->not->toBeNull()
        ->and($this->composer['require-dev']['filament/upgrade'] ?? null)->not->toBeNull();
})->group('kit');

/**
 * O cache do Rector não pode nascer na raiz.
 *
 * O default dele é `.rector.cache` no diretório do projeto — sujeira num kit distribuído
 * por `create-project`, e a primeira coisa que aparece num `git status` de quem acabou de
 * instalar.
 */
it('guarda o cache do rector fora da raiz', function (): void {
    expect($this->rector)->toContain('withCache')
        ->and($this->rector)->toContain('storage/framework/cache/rector');
})->group('kit');

/**
 * A suíte de browser aquece as views ANTES de rodar.
 *
 * Compilar as ~590 views custa dezenas de segundos, e o primeiro cenário que renderiza
 * um painel pagaria a conta inteira dentro do próprio timeout de 45s — falhando por um
 * motivo que não é o dele.
 *
 * O caso existe porque a falha é enganosa: numa máquina com as views quentes de um
 * `composer test:kit` anterior a suíte passa, e só o CI limpo fica vermelho. O sintoma
 * tem a cara de teste instável, e custou duas execuções completas da suíte para separar
 * uma coisa da outra.
 *
 * Ver .ai/rules/testes-browser.md.
 */
it('aquece as views antes da suíte de browser', function (): void {
    expect($this->composer['scripts']['test:browser'] ?? [])
        ->toContain('@php artisan view:cache');
})->group('kit');

/**
 * A suíte não escreve nos logs de trabalho de quem a roda.
 *
 * Medido antes da correção: `storage/logs/autenticacao-2026-08-14.log` com 4.463 linhas e
 * 1,1 MB, 1.033 delas de `[User@canAccessPanel]` — tudo produzido pelas rodadas do dia.
 *
 * A armadilha é o remédio óbvio: `LOG_CHANNEL=null` no `phpunit.xml` troca apenas o canal
 * **default**, e as 60 chamadas de log do kit são `Log::channel('ai'|'tenancy'|'autenticacao')`
 * nomeadas — passavam por cima dele e continuavam gravando em `daily`. Quem resolve é o
 * `LOG_KIT_DRIVER` no driver dos três canais (`config/logging.php`).
 *
 * O caso assere o **handler resolvido**, não a chave de config: assim ele cobre a corrente
 * inteira (env do `phpunit.xml` → `env()` do config → `LogManager`), e morre se alguém
 * errar o nome da variável, tirar o `env()` de um dos canais ou apagar a linha do
 * `phpunit.xml`.
 *
 * Ver DT-10 em wikis/specs/feature/wiki-regressao-telas/regressao-de-telas/.
 */
it('não escreve log em disco durante a suíte', function (?string $canal): void {
    $handlers = Log::channel($canal)->getLogger()->getHandlers();

    expect($handlers)->toHaveCount(1)
        ->and($handlers[0])->toBeInstanceOf(NullHandler::class);
})->with(['ai', 'tenancy', 'autenticacao', null])->group('kit');

/**
 * ===========================================================================
 * PHPStan no level 8 — wikis/specs/feat/phpstan-nivel-8
 * ===========================================================================
 *
 * Helpers e casos abaixo são usados só por ESTE arquivo (não vão para
 * tests/Pest.php).
 */

/**
 * Roda "phpstan dump-parameters --json" DE VERDADE e devolve a config resolvida
 * (includes já mesclados). NUNCA pula: se o comando falhar ou devolver JSON
 * inválido, a asserção estoura e o teste que chamou este helper fica vermelho —
 * é exatamente isso que o CT-01 protege (M4c).
 *
 * `PAO_DISABLE=1` evita que o laravel/pao interfira na saída do processo.
 *
 * Memoizado numa `static`: o processo do PHPStan é caro (minutos, não milissegundos),
 * e CT-01, CT-04, CT-05 (×3) e CT-06 chamam este helper — sem a `static` cada um
 * dispara a própria execução do binário. Uma execução por processo de teste basta:
 * nenhum caso deste arquivo escreve no `phpstan.neon` no meio da suíte.
 */
function configuracaoEfetivaDoPhpstan(): array
{
    static $configuracao = null;

    if ($configuracao !== null) {
        return $configuracao;
    }

    $resultado = Process::path(base_path())
        ->env(['PAO_DISABLE' => '1'])
        ->timeout(60)
        ->run('php vendor/bin/phpstan dump-parameters --json');

    expect($resultado->exitCode())->toBe(0);

    $json = json_decode($resultado->output(), associative: true);

    expect(json_last_error())->toBe(JSON_ERROR_NONE)
        ->and($json)->toBeArray();

    return $configuracao = $json;
}

/**
 * `dump-parameters` devolve paths absolutos (o SO da máquina que roda o teste).
 * Volta para a forma relativa, com barra normal, para comparar com o texto do
 * `phpstan.neon`.
 */
function relativizarCaminhoDoPhpstan(string $caminho): string
{
    $base         = str_replace('\\', '/', base_path());
    $normalizado  = str_replace('\\', '/', $caminho);

    return ltrim(Str::after($normalizado, $base), '/');
}

/**
 * Acha, na config efetiva, a única entrada de "ignoreErrors" cuja mensagem cita
 * o assunto pedido (CT-05).
 */
function entradaDeIgnoreErrorsPara(array $json, string $assunto): array
{
    $entradas = array_values(array_filter(
        $json['ignoreErrors'],
        static fn (array $entrada): bool => str_contains((string) ($entrada['message'] ?? ''), $assunto)
    ));

    expect($entradas)->toHaveCount(1);

    return $entradas[0];
}

/**
 * Todas as linhas que citam "phpstan" em qualquer workflow do CI, com arquivo e
 * número da linha — usado pela partição "varredura" do CT-02.
 */
function linhasDePhpstanEmWorkflows(): array
{
    $linhas = [];

    foreach (glob(base_path('.github/workflows/*.yml')) ?: [] as $arquivo) {
        $conteudo = (string) file_get_contents($arquivo);

        foreach (explode("\n", $conteudo) as $numero => $linha) {
            if (str_contains($linha, 'phpstan')) {
                $linhas[] = [
                    'arquivo' => basename($arquivo),
                    'numero'  => $numero + 1,
                    'texto'   => trim($linha),
                ];
            }
        }
    }

    return $linhas;
}

/**
 * O bloco de um step do workflow, do "- name: {$nomeStep}" até o próximo item
 * de lista no mesmo nível (ou o fim do texto). Usado para conferir que o step
 * não declara "continue-on-error" nem "if:" em nenhuma linha do MESMO step.
 */
function blocoDoStep(string $texto, string $nomeStep): string
{
    $inicio = mb_strpos($texto, "- name: {$nomeStep}");

    expect($inicio)->not->toBeFalse();

    $resto = mb_substr($texto, (int) $inicio);
    $fim   = mb_strpos($resto, "\n      - ", 1);

    return $fim === false ? $resto : mb_substr($resto, 0, $fim);
}

/**
 * Os 14 arquivos de app/database com nulidade tratada por esta entrega, na
 * lista literal recebida do orquestrador (o 04 fala em "16 arquivos"; a
 * diferença está reportada como ambiguidade resolvida no retorno deste lote).
 *
 * @return list<string>
 */
function arquivosTocadosPelaEntrega(): array
{
    return [
        'app/Filament/Admin/Resources/Roles/Pages/CreateRole.php',
        'app/Filament/Admin/Resources/Roles/Pages/EditRole.php',
        'app/Filament/Concerns/DescobreCardsDoPainel.php',
        'app/Filament/Pages/Auth/RegistroPorConvite.php',
        'app/Filament/Pages/Auth/TelaBloqueio.php',
        'app/Filament/Pages/Auth/TelaLogin.php',
        'app/Http/Controllers/Auth/LoginSocialController.php',
        'app/Http/Middleware/ExigirEmailVerificado.php',
        'app/Livewire/AssistenteChatWidget.php',
        'app/Livewire/DefinirSenhaPorEmail.php',
        'app/Models/Convite.php',
        'app/Notifications/PrimeiroAcessoSocial.php',
        'app/Support/ImportExport/ImportadorDoKit.php',
        'database/migrations/2026_08_12_164953_harden_onboarding_progress_scope.php',
    ];
}

it('[CT-01] a configuração resolvida do PHPStan declara nível 8 ou superior', function (): void {
    $json = configuracaoEfetivaDoPhpstan();

    $nivel       = $json['level'] ?? null;
    $nivelValido = $nivel === 'max' || (is_numeric($nivel) && (int) $nivel >= 8);

    expect($nivelValido)->toBeTrue()
        ->and($json['checkNullables'] ?? null)->toBeTrue()
        ->and($json['reportMaybes'] ?? null)->toBeTrue()
        ->and($json['checkUnionTypes'] ?? null)->toBeTrue();
})->group('kit');

it('[CT-02] nenhum ponto de invocação sobrescreve nível, configuração, escopo ou resultado', function (string $ponto): void {
    // `.github/` é export-ignore: num projeto instalado só a linha do composer tem o que ler.
    if ($ponto !== 'script "types:check" do composer.json' && ! naArvoreDoKit()) {
        $this->markTestSkipped('O kit:update não entrega a pasta de workflows (export-ignore).');
    }

    $composerScripts = json_decode((string) file_get_contents(base_path('composer.json')), true)['scripts'];
    $ciTexto         = naArvoreDoKit() ? (string) file_get_contents(base_path('.github/workflows/ci.yml')) : '';

    preg_match('/Análise estática \(PHPStan\)\s*\n\s*run:\s*(.+)/', $ciTexto, $m);
    $linhaCi = trim($m[1] ?? '');

    $todasAsChamadas = linhasDePhpstanEmWorkflows();
    $outras          = array_values(array_filter(
        $todasAsChamadas,
        static fn (array $l): bool => ! ($l['arquivo'] === 'ci.yml' && $l['texto'] === "run: {$linhaCi}")
    ));

    $linhas = match ($ponto) {
        'script "types:check" do composer.json'                   => [implode(' ', (array) ($composerScripts['types:check'] ?? []))],
        'step do phpstan em .github/workflows/ci.yml'             => [$linhaCi],
        'qualquer outra chamada a phpstan em .github/workflows/*' => array_column($outras, 'texto'),
    };

    foreach ($linhas as $linha) {
        expect($linha)->toContain('phpstan analyse');
        expect($linha)->not->toMatch('/phpstan analyse\s+[^\s-]/');
        expect($linha)->not->toMatch('/(^|\s)(-l\S*|--level(=\S+)?|-c(\s|$)|--configuration\S*|--generate-baseline)/');
        expect(str_ends_with(rtrim($linha), '|| true'))->toBeFalse();
    }

    // A partição "varredura" não tem, hoje, nenhuma OUTRA invocação além das já
    // testadas pelas duas primeiras linhas — e é isso que M3 (uma segunda
    // chamada com "-c phpstan-ci.neon" nível menor, em outro workflow) quebraria.
    if ($ponto === 'qualquer outra chamada a phpstan em .github/workflows/*') {
        expect($outras)->toBeEmpty();
    }

    // O step do CI não desliga o gate por fora da linha de comando, e o workflow
    // dispara em push para main e em pull_request. As duas checagens vivem SÓ
    // aqui: são propriedade do arquivo .github/workflows/ci.yml, que só esta
    // partição lê (RQ-07, Adendo 1). Rodá-las também na partição do composer
    // reprovaria todo projeto instalado, que não tem ".github/" (M4e).
    if ($ponto === 'step do phpstan em .github/workflows/ci.yml') {
        $bloco = blocoDoStep($ciTexto, 'Análise estática (PHPStan)');

        expect($bloco)->not->toContain('continue-on-error')
            ->and($bloco)->not->toContain('if:');

        preg_match('/^on:\n(.*?)\njobs:/ms', $ciTexto, $onBloco);
        $blocoOn = $onBloco[1] ?? '';

        expect($blocoOn)->toContain('push:')
            ->and($blocoOn)->toMatch('/branches:\s*\[main\]/')
            ->and($blocoOn)->toContain('pull_request:');
    }

    // O script local não indireciona para outro script do composer, e a linha
    // não lê nenhum arquivo de ".github/" — é o que a mantém executável e verde
    // num projeto instalado (create-project não entrega ".github/", RQ-07).
    if ($ponto === 'script "types:check" do composer.json') {
        $tiposCheck = (array) ($composerScripts['types:check'] ?? []);
        expect(collect($tiposCheck)->contains(static fn (string $l): bool => str_starts_with(trim($l), '@')))->toBeFalse();

        foreach ($linhas as $linha) {
            expect($linha)->not->toContain('.github');
        }
    }
})->with([
    'script "types:check" do composer.json',
    'step do phpstan em .github/workflows/ci.yml',
    'qualquer outra chamada a phpstan em .github/workflows/*',
])->group('kit');

it('[CT-04] a configuração efetiva não carrega baseline', function (): void {
    $json = configuracaoEfetivaDoPhpstan();
    $neon = (string) file_get_contents(base_path('phpstan.neon'));

    foreach ($json['ignoreErrors'] as $entrada) {
        expect($entrada)->not->toHaveKey('count');
    }

    preg_match('/^includes:\n((?:[ \t]+-[^\n]*\n)+)/m', $neon, $m);
    $blocoIncludes = $m[1] ?? '';

    expect($blocoIncludes)->not->toBe('');
    expect($blocoIncludes)->not->toContain('baseline');

    expect(glob(base_path('phpstan-baseline.*')) ?: [])->toBeEmpty();
})->group('kit');

it('[CT-05] toda exceção de ignoreErrors tem escopo de path e mensagem específica', function (string $assunto): void {
    $json    = configuracaoEfetivaDoPhpstan();
    $entrada = entradaDeIgnoreErrorsPara($json, $assunto);

    expect($entrada)->toHaveKey('message');
    expect(is_string($entrada['message']))->toBeTrue();

    $paths = $entrada['paths'] ?? ($entrada['path'] ?? null);
    $paths = is_array($paths) ? $paths : ($paths === null ? [] : [$paths]);

    expect($paths)->not->toBeEmpty();

    foreach (['identifier', 'identifiers', 'rawMessage', 'messages'] as $chaveProibida) {
        expect($entrada)->not->toHaveKey($chaveProibida);
    }

    $raizesAnalisadas = array_map(
        static fn (string $r): string => relativizarCaminhoDoPhpstan(str_replace('\\', '/', base_path($r))),
        ['app', 'bootstrap/app.php', 'config', 'database', 'routes']
    );

    foreach ($paths as $p) {
        $relativo = relativizarCaminhoDoPhpstan($p);
        expect(in_array($relativo, $raizesAnalisadas, true))->toBeFalse();
    }

    expect((bool) @preg_match($entrada['message'], 'Cannot call method getLoginUrl() on Filament\Panel|null.'))->toBeFalse();
    expect((bool) @preg_match($entrada['message'], 'Parameter #1 $url of class Illuminate\Http\RedirectResponse constructor expects string, string|null given.'))->toBeFalse();
})->with([
    'simpleLightbox',
    'WidgetDinamico',
    'customMyProfilePage',
])->group('kit');

/**
 * Adendo 1 (RQ-09, 2026-09-26): a anotação errada do vendor para `EnsureEmailIsVerified`
 * deixa de ser contornada por exceção no `phpstan.neon` — passa a ser guarda de invariante
 * no código do kit (`ExigirEmailVerificado`). O inventário volta às 3 exceções anteriores
 * à entrega, e nenhuma cita `ExigirEmailVerificado` na mensagem nem no path.
 */
it('[CT-06] o inventário de exceções volta às 3 anteriores e cada uma registra a tentativa', function (): void {
    $json = configuracaoEfetivaDoPhpstan();
    $neon = (string) file_get_contents(base_path('phpstan.neon'));

    expect($json['ignoreErrors'])->toHaveCount(3);

    foreach ($json['ignoreErrors'] as $entrada) {
        $paths = $entrada['paths'] ?? ($entrada['path'] ?? null);
        $paths = is_array($paths) ? $paths : ($paths === null ? [] : [$paths]);

        $this->assertStringNotContainsString('ExigirEmailVerificado', (string) ($entrada['message'] ?? ''));

        foreach ($paths as $p) {
            $this->assertStringNotContainsString('ExigirEmailVerificado', $p);
        }
    }

    $this->assertStringNotContainsString('ExigirEmailVerificado', $neon);

    // As 3 entradas pré-existentes, congeladas com o mesmo message/path(s) da
    // `main` (medido: `git show main:phpstan.neon` é byte-idêntico a estes
    // três blocos na branch corrente).
    $congeladas = [
        [
            'message' => '#Call to an undefined method .*::simpleLightbox\(\)#',
            'paths'   => ['app/Filament/*/Resources/**'],
        ],
        [
            'message' => '#Trait App\\\\Filament\\\\Concerns\\\\WidgetDinamico is used zero times#',
            'paths'   => ['app/Filament/Concerns/WidgetDinamico.php'],
        ],
        [
            'message' => '#customMyProfilePage\(\) expects class-string<Jeffgreco13\\\\FilamentBreezy\\\\Concerns\\\\Plugin\\\\Pages\\\\MyProfilePage>#',
            'paths'   => [
                'app/Providers/Filament/AdminPanelProvider.php',
                'app/Providers/Filament/AppPanelProvider.php',
                'app/Providers/Filament/InfraPanelProvider.php',
            ],
        ],
    ];

    foreach ($congeladas as $indice => $esperada) {
        $atual = $json['ignoreErrors'][$indice];

        $pathsAtuais = $atual['paths'] ?? ($atual['path'] ?? null);
        $pathsAtuais = is_array($pathsAtuais) ? $pathsAtuais : [$pathsAtuais];
        $pathsAtuais = array_map(relativizarCaminhoDoPhpstan(...), $pathsAtuais);

        expect($atual['message'] ?? null)->toBe($esperada['message']);
        expect($pathsAtuais)->toBe($esperada['paths']);
    }

    // Cada uma das 3 entradas registra a tentativa: "Tentado" seguido de ao
    // menos uma alternativa descartada (bullet "#   - ...").
    preg_match_all('/((?:^[ \t]*#[^\n]*\n)+)[ \t]*-\n[ \t]*message: /m', $neon, $matches);
    $comentarios = $matches[1];

    expect($comentarios)->toHaveCount(3);

    foreach ($comentarios as $bloco) {
        expect($bloco)->toContain('Tentado');
        expect((bool) preg_match('/Tentado.*\n(?:[ \t]*#[^\n]*\n)*?[ \t]*#[ \t]*-/s', $bloco))->toBeTrue();
    }

    expect($json['reportUnmatchedIgnoredErrors'] ?? null)->toBeTrue();

    foreach ($json['stubFiles'] ?? [] as $stub) {
        expect(str_contains(str_replace('\\', '/', $stub), '/vendor/'))->toBeTrue();
    }

    expect($neon)->not->toMatch('/^services:/m');
    expect($neon)->not->toMatch('/^conditionalTags:/m');

    preg_match('/^includes:\n((?:[ \t]+-[^\n]*\n)+)/m', $neon, $mi);
    $blocoIncludes = trim($mi[1] ?? '');

    expect($blocoIncludes)->not->toBe('');

    foreach (explode("\n", $blocoIncludes) as $linha) {
        expect(trim($linha))->toStartWith('- vendor/');
    }
})->group('kit');

it('[CT-07] o escopo analisado não encolheu para esconder erro', function (): void {
    $json = configuracaoEfetivaDoPhpstan();

    $paths = array_map(relativizarCaminhoDoPhpstan(...), $json['paths']);
    sort($paths);

    $pathsEsperados = ['app', 'bootstrap/app.php', 'config', 'database', 'routes'];
    sort($pathsEsperados);

    expect($paths)->toBe($pathsEsperados);

    $excludePaths = array_map(
        relativizarCaminhoDoPhpstan(...),
        array_merge($json['excludePaths']['analyseAndScan'] ?? [], $json['excludePaths']['analyse'] ?? [])
    );
    sort($excludePaths);

    $excludePathsEsperados = [
        'database/migrations/*_create_health_tables.php',
        'database/migrations/*_create_breezy_sessions_table.php',
        'database/migrations/*_create_pulse_tables.php',
    ];
    sort($excludePathsEsperados);

    expect($excludePaths)->toBe($excludePathsEsperados);

    foreach (arquivosTocadosPelaEntrega() as $arquivo) {
        expect(in_array($arquivo, $excludePaths, true))->toBeFalse();
    }
})->group('kit');

it('[CT-08] nenhum @phpstan-ignore novo nasce, e os pré-existentes ficam congelados', function (): void {
    $roleResource = (string) file_get_contents(base_path('app/Filament/Admin/Resources/Roles/RoleResource.php'));
    $userModel    = (string) file_get_contents(base_path('app/Models/User.php'));

    expect(substr_count($roleResource, '@phpstan-ignore'))->toBe(2);
    expect(substr_count($userModel, '@phpstan-ignore'))->toBe(1);

    foreach (arquivosTocadosPelaEntrega() as $arquivo) {
        $codigo = (string) file_get_contents(base_path($arquivo));

        expect($codigo)->not->toContain('@phpstan-ignore');
        expect($codigo)->not->toContain('@phpstan-assert');
        expect((bool) preg_match('/\bassert\s*\(/', $codigo))->toBeFalse();
    }
})->group('kit');
