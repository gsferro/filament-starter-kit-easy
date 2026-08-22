<?php

/**
 * A esteira de qualidade do kit — e a fronteira entre o que roda sempre e o que roda
 * sob demanda.
 *
 * São quatro ferramentas em quatro eixos:
 *
 *   pint       estilo         corrige   ← gate
 *   phpstan    tipos          reporta   ← gate (level 7)
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
