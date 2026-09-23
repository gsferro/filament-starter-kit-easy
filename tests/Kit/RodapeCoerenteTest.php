<?php

use App\Filament\Admin\Pages\ConfiguracoesDoKit;
use Database\Seeders\PapeisSeeder;
use Database\Seeders\ShieldPermissionsSeeder;
use Filament\Facades\Filament;
use Illuminate\Support\Carbon;
use Livewire\Livewire;

/*
|--------------------------------------------------------------------------
| Rodapé coerente entre os painéis e a tela de login
|--------------------------------------------------------------------------
| Derivado de `wikis/specs/feat/rodape-coerente/rodape-coerente/04-casos-de-teste.md` — leia o
| `## Setup Global` daquele arquivo antes de mexer aqui. Os IDs `CT-nn` são POR WIKI:
| `tests/Kit/VersaoNoRodapeTest.php` usa `CT-01…CT-47` de outra wiki, e não é reaproveitado.
|
| Quatro regras valem em TODO cenário deste arquivo (Setup Global):
| 1. "o rodapé contém X" == `assinaturaDoRodape()`, nunca `rodapeDe()` (que inclui o snapshot do
|    Livewire, onde `app.name` e o recado aparecem serializados).
| 2. Todo `Dado` fixa as QUATRO chaves via `comIdentidade()` — nome, versão, toggle do kit e
|    recado —, mesmo quando o cenário só discute uma delas.
| 3. Todo cenário que afirma a assinatura congela o relógio com `emJunhoDe2026()` (exceção:
|    `[CT-22]`, que MEDE o relógio e por isso gerencia o tempo por conta própria).
| 4. Todo cenário que abre uma rota de login fixa `kit.login.unificado` com `ligarLoginUnificado()`.
*/

beforeEach(function (): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);
});

/*
|--------------------------------------------------------------------------
| R1 — a assinatura © {ano corrente} {Nome} aparece para todo mundo
|--------------------------------------------------------------------------
*/

/**
 * [CT-01] a assinatura sai em toda superfície, para as duas audiências.
 *
 * A última linha (`/admin/password-reset/request`) é a que impede a guarda de ser escrita por
 * ROTA em vez de por autenticação (M37/M39): é uma superfície pública que NÃO é tela de login, e
 * por decisão de 2026-09-23 (Q2, "só nas telas de login") o recado também não pode aparecer ali —
 * o `Então` daquela linha ganha a checagem extra (M34).
 *
 * `/admin/register` foi CORTADO desta suíte: o painel admin deste kit não tem `registration()`
 * habilitado (nenhuma rota `/admin/register` existe nesta instalação — confirmado por
 * `php artisan route:list`), e o `04` autoriza explicitamente cortar essa linha e manter a de
 * recuperação de senha, que sozinha já mata M37.
 */
it('[CT-01] a assinatura sai em toda superfície, para as duas audiências', function (string $persona, string $rota, bool $recadoNaoDeveAparecer = false): void {
    emJunhoDe2026();
    comIdentidade('Acme', '2.4.0', exibirKit: false, recado: 'Fale com o suporte');
    ligarLoginUnificado($rota === '/login');

    $resposta = $persona === 'visitante'
        ? $this->get($rota)
        : $this->actingAs(usuarioDoKit($persona, "{$persona}@example.com"))->get($rota);

    $resposta->assertOk();

    $html = (string) $resposta->getContent();

    $this->assertStringContainsString('© 2026 Acme', assinaturaDoRodape($html));

    if ($recadoNaoDeveAparecer) {
        expect(rodapeDe($html))->not->toBe('', 'rodapeDe() nao achou o rodape nesta rota — a ausencia abaixo mediria o vazio');
        $this->assertStringNotContainsString('Fale com o suporte', rodapeDe($html));
    }
})->with([
    'admin - painel admin'                        => ['admin', '/admin'],
    'panel_user - painel com tenancy'             => ['panel_user', '/app'],
    'infra - terceiro painel'                     => ['infra', '/infra'],
    'visitante - login de painel'                 => ['visitante', '/admin/login'],
    'visitante - login do painel com tenancy'     => ['visitante', '/app/login'],
    'visitante - página única de login'           => ['visitante', '/login'],
    'visitante - layout simple que NÃO é login'   => ['visitante', '/admin/password-reset/request', true],
])->group('kit');

/**
 * [CT-02] a assinatura sai uma vez só, em toda superfície.
 *
 * Conta no DOCUMENTO INTEIRO, não no recorte — duas emissões podem cair em pontos diferentes da
 * página. O snapshot do Livewire pode serializar `app.name`, então a contagem exclui o valor do
 * atributo `wire:snapshot` (que o Livewire sempre codifica com `&quot;` para as aspas internas,
 * nunca aspas cruas — a regex fecha no primeiro `"` de verdade).
 *
 * A última linha é o mundo que Q3 nomeia: o admin já digitou "© Acme" no recado, e as duas
 * convivem (M48, dedup indevida).
 */
it('[CT-02] a assinatura sai uma vez só, em toda superfície', function (string $persona, string $rota, ?string $recado = 'Fale com o suporte'): void {
    emJunhoDe2026();
    comIdentidade('Acme', null, exibirKit: false, recado: $recado);
    ligarLoginUnificado($rota === '/login');

    $resposta = $persona === 'visitante'
        ? $this->get($rota)
        : $this->actingAs(usuarioDoKit($persona, "{$persona}@example.com"))->get($rota);

    $resposta->assertOk();

    $semSnapshot = (string) preg_replace('~wire:snapshot="[^"]*"~s', '', (string) $resposta->getContent());

    expect(substr_count($semSnapshot, '© 2026 Acme'))->toBe(1);
})->with([
    'classe mãe — login de painel'         => ['visitante', '/admin/login'],
    'classe filha — página única'          => ['visitante', '/login'],
    'hook global — painel autenticado'     => ['admin', '/admin'],
    'recado contendo © Acme (mundo de Q3)' => ['visitante', '/admin/login', '© Acme'],
])->group('kit');

/**
 * [CT-03] a assinatura reflete o nome gravado pela tela de configurações.
 *
 * O gate de tela de escrita: `fillForm` → `call('save')` → `configuracaoGravada()` →
 * `alinharConfiguracoesDoKit()` → `GET`. As DUAS superfícies são checadas DEPOIS do save — é o
 * que mata M46 (composição memoizada no painel, só o login relê).
 *
 * `/admin/login` é visitado como VISITANTE (depois de `Filament::auth()->logout()`): o Filament
 * redireciona (302) quem já está autenticado para fora da tela de login
 * (`vendor/filament/filament/src/Auth/Pages/Login.php:58-62`), então checar como admin mediria um
 * redirect, não a assinatura.
 */
it('[CT-03] a assinatura reflete o nome gravado pela tela de configurações', function (): void {
    emJunhoDe2026();
    comIdentidade('Acme', '2.4.0', exibirKit: false, recado: null);
    ligarLoginUnificado(false);

    $admin = usuarioDoKit('admin');

    $this->actingAs($admin);
    Filament::setCurrentPanel('admin');

    Livewire::test(ConfiguracoesDoKit::class)
        ->fillForm(['nome_da_aplicacao' => 'Marca Nova'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect(configuracaoGravada('nome_da_aplicacao'))->toBe('Marca Nova');

    alinharConfiguracoesDoKit();

    $rodapeAdmin = (string) $this->get('/admin')->assertOk()->getContent();
    $this->assertStringContainsString('© 2026 Marca Nova', assinaturaDoRodape($rodapeAdmin));
    $this->assertStringNotContainsString('© 2026 Acme', assinaturaDoRodape($rodapeAdmin));

    Filament::auth()->logout();

    $rodapeLogin = (string) $this->get('/admin/login')->assertOk()->getContent();
    $this->assertStringContainsString('© 2026 Marca Nova', assinaturaDoRodape($rodapeLogin));
    $this->assertStringNotContainsString('© 2026 Acme', assinaturaDoRodape($rodapeLogin));
})->group('kit');

/**
 * [CT-22] o ano da assinatura acompanha o relógio, e vem do fuso da aplicação.
 *
 * **Não usa `emJunhoDe2026()`** — este é o cenário que MEDE o relógio.
 *
 * `AssinaturaDoRodape::partes()` chama `now()->year` sem argumento de fuso. `now()` == `Date::now(null)`
 * == `Carbon::now(null)`, e quando não há fuso explícito o Carbon resolve o "agora" de teste pelo
 * fuso PADRÃO DO PHP (`date_default_timezone_get()`) — não por `config('app.timezone')` lido em
 * tempo de execução (medido: `vendor/nesbot/carbon/src/Carbon/Traits/Test.php:149`,
 * `$testInstance->setTimezone($timezone ?? date_default_timezone_get())`). Em produção isso não
 * importa porque `LoadConfiguration` chama `date_default_timezone_set(config('app.timezone'))` UMA
 * vez, no boot — mas um `config()->set('app.timezone', ...)` NO CORPO DO TESTE, depois do boot, não
 * refaria essa chamada (é a mesma lacuna que `tests/Kit/PageHeaderTest.php` mede para
 * `FilamentTimezone`). Por isso o `Dado` do fuso usa `date_default_timezone_set()` diretamente — é
 * o que efetivamente controla o que `now()` devolve — e o instante é sempre ancorado em UTC antes
 * do `travelTo()`, para que `setTimezone()` o converta de verdade.
 */
it('[CT-22] o ano da assinatura acompanha o relógio, e vem do fuso da aplicação', function (string $fuso, string $instante, string $ano): void {
    comIdentidade('Acme', null, exibirKit: false, recado: null);
    ligarLoginUnificado(false);

    date_default_timezone_set($fuso);

    try {
        $this->travelTo(Carbon::parse($instante, 'UTC'));

        $html = (string) $this->get('/admin/login')->assertOk()->getContent();

        expect(assinaturaDoRodape($html))->toBe("© {$ano} Acme");
    } finally {
        date_default_timezone_set((string) config('app.timezone', 'UTC'));
    }
})->with([
    'borda−1 — último segundo do ano, UTC'           => ['UTC', '2026-12-31 23:59:59', '2026'],
    'borda — primeiro segundo do ano seguinte, UTC'  => ['UTC', '2027-01-01 00:00:00', '2027'],
    'longe da borda, UTC'                            => ['UTC', '2027-06-15 12:00:00', '2027'],
    'fuso do app diverge do UTC — ainda 31/12 em SP' => ['America/Sao_Paulo', '2027-01-01 02:30:00', '2026'],
])->group('kit');

/*
|--------------------------------------------------------------------------
| R2 — nenhuma versão aparece para quem não autenticou
|--------------------------------------------------------------------------
*/

/**
 * [CT-04] o visitante vê a assinatura e nenhuma versão.
 *
 * `[CT-06]` da derivação original foi FUNDIDO aqui na revisão adversarial (recorte inconsistente:
 * CT-04 media ausência só no rodapé, CT-06 no documento inteiro, para a mesma regra) — o ID fica
 * registrado, não reaproveitado. A asserção de ausência é sobre o DOCUMENTO INTEIRO.
 *
 * A última linha (`/admin/password-reset/request`) é o que impede a guarda de ser escrita por
 * rota (M37); ela também prova que o recado não vaza para superfícies públicas que não são login
 * (M34, Q2).
 *
 * `/admin/register` foi cortado pelo mesmo motivo do CT-01: a rota não existe nesta instalação.
 */
it('[CT-04] o visitante vê a assinatura e nenhuma versão', function (string $rota, bool $exibirKit): void {
    emJunhoDe2026();
    comIdentidade('Acme', '9.9.9-secreta', $exibirKit, recado: 'Fale com o suporte');
    ligarLoginUnificado($rota === '/login');

    $html = (string) $this->get($rota)->assertOk()->getContent();

    $this->assertStringContainsString('© 2026 Acme', assinaturaDoRodape($html));
    $this->assertStringNotContainsString('9.9.9-secreta', $html);
    $this->assertStringNotContainsString((string) config('kit.version'), $html);

    if ($rota === '/admin/password-reset/request') {
        expect(rodapeDe($html))->not->toBe('', 'rodapeDe() nao achou o rodape nesta rota — a ausencia abaixo mediria o vazio');
        $this->assertStringNotContainsString('Fale com o suporte', rodapeDe($html));
    }
})->with([
    'guarda no caminho comum'                => ['/admin/login', false],
    'a versão do KIT também é guardada'      => ['/admin/login', true],
    'outro painel, mesma guarda'             => ['/infra/login', true],
    'página única — não há painel corrente'  => ['/login', true],
    'superfície pública que NÃO é login'     => ['/admin/password-reset/request', true],
])->group('kit');

/**
 * [CT-05] quem autenticou vê, nos três painéis, a versão que o visitante não vê.
 *
 * O par positivo do estreitamento: fica vermelho se a guarda for invertida ou se passar a
 * esconder a versão de todo mundo. Exige o valor de `kit.version` no segmento (não só um rótulo
 * qualquer) e a AUSÊNCIA de rótulo no segmento da versão do sistema, discriminando as duas.
 *
 * **Ambiguidade resolvida**: `temRotulo()` — herdado de `VersaoNoRodapeTest.php` e não alterável
 * aqui (contrato de outra wiki) — detecta "palavra de 2+ letras" no SEGMENTO INTEIRO. O `04` manda
 * usar o literal "9.9.9-secreta" (o mesmo de CT-04, por continuidade narrativa), mas essa string
 * contém a palavra "secreta": aplicar `temRotulo()` direto no segmento `v9.9.9-secreta` dá `true`
 * por causa do PRÓPRIO conteúdo da versão, não de um rótulo real — falso-positivo medido. A
 * checagem abaixo remove o valor da versão do segmento ANTES de perguntar por rótulo, isolando o
 * que sobra ao redor dela (o "v" sozinho não tem rótulo; "kit " sim).
 */
it('[CT-05] quem autenticou vê, nos três painéis, a versão que o visitante não vê', function (string $persona, string $rota): void {
    emJunhoDe2026();
    comIdentidade('Acme', '9.9.9-secreta', exibirKit: true, recado: null);

    $html   = (string) $this->actingAs(usuarioDoKit($persona, "{$persona}@example.com"))->get($rota)->assertOk()->getContent();
    $rodape = rodapeDe($html);

    $this->assertStringContainsString('v9.9.9-secreta', $rodape);

    $segmentoDoKit     = segmentoDaVersao($rodape, (string) config('kit.version'));
    $segmentoDoSistema = segmentoDaVersao($rodape, '9.9.9-secreta');

    $this->assertStringContainsString((string) config('kit.version'), $segmentoDoKit);

    $arredorDoKit     = trim(str_replace((string) config('kit.version'), '', $segmentoDoKit));
    $arredorDoSistema = trim(str_replace('9.9.9-secreta', '', $segmentoDoSistema));

    expect(temRotulo($arredorDoKit))->toBeTrue()
        ->and(temRotulo($arredorDoSistema))->toBeFalse();
})->with([
    'painel admin'      => ['admin', '/admin'],
    'painel c/ tenancy' => ['panel_user', '/app'],
    'terceiro painel'   => ['infra', '/infra'],
])->group('kit');

/*
|--------------------------------------------------------------------------
| R3 — a linha automática é composta num só ponto
|--------------------------------------------------------------------------
*/

/**
 * [CT-07] a assinatura é a mesma string literal em toda superfície.
 *
 * Compara CADA superfície com o LITERAL esperado, não as superfícies entre si — igualdade
 * cruzada não discrimina duas composições igualmente erradas. Cobre CINCO superfícies de
 * propósito: uma composição duplicada que divirja só em `/app` ou `/login` não seria vista por um
 * par.
 */
it('[CT-07] a assinatura é a mesma string literal em toda superfície', function (string $persona, string $rota, string $esperado): void {
    emJunhoDe2026();
    comIdentidade('Acme & Filhos', '2.4.0', exibirKit: false, recado: null);
    ligarLoginUnificado($rota === '/login');

    $resposta = $persona === 'visitante'
        ? $this->get($rota)
        : $this->actingAs(usuarioDoKit($persona, "{$persona}@example.com"))->get($rota);

    $resposta->assertOk();

    expect(assinaturaDoRodape((string) $resposta->getContent()))->toBe($esperado);
})->with([
    'painel admin'      => ['admin', '/admin', '© 2026 Acme & Filhos · v2.4.0'],
    'outro painel'      => ['infra', '/infra', '© 2026 Acme & Filhos · v2.4.0'],
    'painel c/ tenancy' => ['panel_user', '/app', '© 2026 Acme & Filhos · v2.4.0'],
    'login de painel'   => ['visitante', '/admin/login', '© 2026 Acme & Filhos'],
    'página única'      => ['visitante', '/login', '© 2026 Acme & Filhos'],
])->group('kit');

/**
 * [CT-08] o recado preenchido não substitui a assinatura.
 */
it('[CT-08] o recado preenchido não substitui a assinatura', function (): void {
    emJunhoDe2026();
    comIdentidade('Acme', null, exibirKit: false, recado: 'Fale com o suporte');
    ligarLoginUnificado(false);

    $html   = (string) $this->get('/admin/login')->assertOk()->getContent();
    $rodape = rodapeDe($html);

    $this->assertStringContainsString('© 2026 Acme', assinaturaDoRodape($html));

    // O RECORTE do elemento, e nao a cauda: `rodapeDe()` inclui o `wire:snapshot`, onde o recado
    // pode aparecer serializado — presenca satisfeita com a faixa inexistente (QA-07).
    $this->assertStringContainsString('Fale com o suporte', recadoDoRodape($html));
})->group('kit');

/**
 * [CT-09] o recado sem conteúdo não apaga a assinatura.
 *
 * A linha `"   "` é a discriminante: separa `filled()` de `! empty()`. O elemento do recado
 * (`fi-login-rodape`) é condicional; a assinatura não é.
 */
it('[CT-09] o recado sem conteúdo não apaga a assinatura', function (?string $recado): void {
    emJunhoDe2026();
    comIdentidade('Acme', null, exibirKit: false, recado: $recado);
    ligarLoginUnificado(false);

    $html   = (string) $this->get('/admin/login')->assertOk()->getContent();
    $rodape = rodapeDe($html);

    $this->assertStringContainsString('© 2026 Acme', assinaturaDoRodape($html));
    expect($rodape)->not->toBe('', 'rodapeDe() nao achou o rodape nesta rota — a ausencia abaixo mediria o vazio');
    $this->assertStringNotContainsString('fi-login-rodape', $rodape);
})->with([
    'ausente'     => [null],
    'vazio'       => [''],
    'só espaços'  => ['   '],
])->group('kit');

/*
|--------------------------------------------------------------------------
| R4 — o recado é linha adicional, abaixo, e só nas telas de login
|--------------------------------------------------------------------------
*/

/**
 * [CT-10] a assinatura vem antes do recado, em toda tela de login.
 *
 * A armadilha do oráculo: `strpos` devolve `false` para agulha ausente, e `false < N` é
 * verdadeiro em PHP — comparar posições SEM antes provar que as duas existem deixaria passar a
 * implementação que apaga a assinatura. Por isso a presença é afirmada primeiro, com as duas
 * asserções INTEIRAS (não por posição), e só então as posições são comparadas.
 *
 * A assinatura e o recado são checados como DOIS BLOCOS IRMÃOS, cada um com conteúdo exato — não
 * concatenados no mesmo nó (M45): se estivessem, a checagem de posição abaixo passaria igual, e é
 * por isso que o conteúdo do elemento da assinatura precisa ser EXATAMENTE a assinatura, sem o
 * recado dentro.
 */
it('[CT-10] a assinatura vem antes do recado, em toda tela de login', function (string $rota): void {
    emJunhoDe2026();
    comIdentidade('Acme', null, exibirKit: false, recado: 'Fale com o suporte');
    ligarLoginUnificado($rota === '/login');

    $html = (string) $this->get($rota)->assertOk()->getContent();

    // Bloco 1: a assinatura, sozinha no seu elemento.
    expect(assinaturaDoRodape($html))->toBe('© 2026 Acme');

    // Bloco 2: o recado, num elemento IRMÃO — não dentro do mesmo nó da assinatura. `[^>]*` (e não
    // um espaço fixo) entre `<div` e `class=`: o blade quebra os atributos em linhas separadas.
    preg_match('~<div[^>]*class="fi-login-rodape"[^>]*>(.*?)</div>~s', $html, $recadoMatch);
    expect($recadoMatch[1] ?? null)->not->toBeNull('o elemento do recado não foi encontrado');
    $this->assertStringContainsString('Fale com o suporte', $recadoMatch[1]);

    // As duas presenças já provadas — agora, e só agora, a ordem.
    $posicaoDaAssinatura = strpos($html, 'kit-versao');
    $posicaoDoRecado     = strpos($html, 'fi-login-rodape');

    expect($posicaoDaAssinatura)->not->toBeFalse()
        ->and($posicaoDoRecado)->not->toBeFalse()
        ->and($posicaoDaAssinatura)->toBeLessThan($posicaoDoRecado);
})->with([
    'login de painel — a classe mãe'                            => ['/admin/login'],
    'página única — a classe filha, registro por outro caminho' => ['/login'],
])->group('kit');

/**
 * [CT-11] o recado aparece em todas as telas de login.
 *
 * A linha `/login` é a que mata "escopar só na mãe" (V6/V7: `TelaLoginUnificada` ESTENDE
 * `TelaLogin`, e `getRenderHookScopes()` devolve a classe CONCRETA). `/infra/login` mata o escopo
 * enumerado painel a painel que esquece o terceiro painel.
 */
it('[CT-11] o recado aparece em todas as telas de login', function (string $rota): void {
    emJunhoDe2026();
    comIdentidade('Acme', null, exibirKit: false, recado: 'Fale com o suporte');
    ligarLoginUnificado($rota === '/login');

    $html = (string) $this->get($rota)->assertOk()->getContent();

    // O RECORTE do elemento, e nao a cauda (QA-07). Este caso e o UNICO matador de M16/M17/M43
    // — o escopo do hook —, entao e justamente aqui que o oraculo nao pode ser o da cauda.
    $this->assertStringContainsString('Fale com o suporte', recadoDoRodape($html));
    $this->assertStringContainsString('© 2026 Acme', assinaturaDoRodape($html));
})->with([
    'login de painel (a classe mãe)'          => ['/admin/login'],
    'login de outro painel, mesma classe mãe' => ['/app/login'],
    'terceiro painel'                         => ['/infra/login'],
    'página única de login (a classe FILHA)'  => ['/login'],
])->group('kit');

/**
 * [CT-12] o recado aparece na tela de login e não na tela autenticada.
 *
 * A linha do visitante é o CONTROLE POSITIVO: sem ela, "o recado não aparece em /admin" ficaria
 * verde com o recado nunca renderizado em lugar nenhum.
 */
it('[CT-12] o recado aparece na tela de login e não na tela autenticada', function (string $persona, string $rota, bool $espera): void {
    emJunhoDe2026();
    comIdentidade('Acme', null, exibirKit: false, recado: 'Fale com o suporte');
    ligarLoginUnificado(false);

    $resposta = $persona === 'visitante'
        ? $this->get($rota)
        : $this->actingAs(usuarioDoKit($persona, "{$persona}@example.com"))->get($rota);

    $html   = (string) $resposta->assertOk()->getContent();
    $rodape = rodapeDe($html);

    // Presenca pelo RECORTE (QA-07); ausencia pela cauda, que e o recorte largo e correto para
    // provar que o recado nao esta em lugar nenhum daquele pedaco.
    expect($rodape)->not->toBe('', 'rodapeDe() nao achou o rodape nesta rota — a ausencia abaixo mediria o vazio');

    $espera
        ? $this->assertStringContainsString('Fale com o suporte', recadoDoRodape($html))
        : $this->assertStringNotContainsString('Fale com o suporte', $rodape);

    $this->assertStringContainsString('© 2026 Acme', assinaturaDoRodape($html));
})->with([
    'o destinatário existe'     => ['visitante', '/admin/login', true],
    'a fronteira, painel admin' => ['admin', '/admin', false],
    'painel c/ tenancy'         => ['panel_user', '/app', false],
    'terceiro painel'           => ['infra', '/infra', false],
])->group('kit');

/*
|--------------------------------------------------------------------------
| R5 — o prefixo v é de exibição
|--------------------------------------------------------------------------
*/

/**
 * [CT-13] o campo Versão do sistema exibe o afixo nativo "v" sem contaminar o estado.
 *
 * `getPrefixLabel()`/`getSuffixLabel()` são o oráculo de RQ-04, alcançados como
 * `tests/Tenancy/AdminDaOrganizacaoTest.php:157` já alcança componentes:
 * `->instance()->getSchemaComponent('form.versao_do_sistema')`.
 *
 * "Nenhum outro campo do formulário tem prefixo 'v'": o afixo do Filament sai como
 * `<span class="fi-input-wrp-label">v</span>` (`vendor/filament/support/.../input/wrapper.blade.php:134-137`),
 * e a MESMA classe serve para SUFIXO também — contar a classe sozinha contaria afixos de outros
 * campos. O oráculo conta quantos desses rótulos têm conteúdo EXATAMENTE "v".
 */
it('[CT-13] o campo Versão do sistema exibe o afixo nativo "v" sem contaminar o estado', function (): void {
    gravarConfiguracao('versao_do_sistema', '2.4.0');

    $this->actingAs(usuarioDoKit('admin'));
    Filament::setCurrentPanel('admin');

    $tela = Livewire::test(ConfiguracoesDoKit::class);

    $campo = $tela->instance()->getSchemaComponent('form.versao_do_sistema');

    expect($campo->getPrefixLabel())->toBe('v')
        ->and($campo->getSuffixLabel())->toBeNull()
        ->and($campo->getState())->toBe('2.4.0');

    $html = $tela->html();

    $this->assertStringContainsString('fi-input-wrp-label', $html);

    preg_match_all('~<span class="fi-input-wrp-label">\s*(.*?)\s*</span>~s', $html, $matches);

    $rotulosV = array_filter($matches[1], static fn (string $rotulo): bool => trim($rotulo) === 'v');

    expect($rotulosV)->toHaveCount(1, 'algum outro campo do formulário também tem afixo "v"');
})->group('kit');

/**
 * [CT-14] o que se digita é o que se grava, e o rodapé compõe o "v".
 *
 * A linha `v2-beta` é a discriminante de M52 (adversarial, rodada 3): um "ajudante" que remove um
 * `v` inicial no salvamento mutila quem digita uma versão que legitimamente começa por `v`. A
 * asserção `não contém "vv2.4.0"` na linha comum é o detector de M22 (o prefixo entra no estado).
 */
it('[CT-14] o que se digita é o que se grava, e o rodapé compõe o "v"', function (string $digitado, string $gravado, string $exibido): void {
    emJunhoDe2026();
    gravarConfiguracao('versao_do_sistema', '1.0.0');
    comIdentidade('Acme', null, exibirKit: false, recado: null);

    $this->actingAs(usuarioDoKit('admin'));
    Filament::setCurrentPanel('admin');

    Livewire::test(ConfiguracoesDoKit::class)
        ->fillForm(['versao_do_sistema' => $digitado])
        ->call('save')
        ->assertHasNoFormErrors();

    expect(configuracaoGravada('versao_do_sistema'))->toBe($gravado);

    alinharConfiguracoesDoKit();

    $rodape = rodapeDe((string) $this->get('/admin')->assertOk()->getContent());

    $this->assertStringContainsString($exibido, $rodape);

    if ($exibido === 'v2.4.0') {
        $this->assertStringNotContainsString('vv2.4.0', $rodape);
    }
})->with([
    'caminho comum — e o rodapé NÃO contém "vv"' => ['2.4.0', '2.4.0', 'v2.4.0'],
    'versão que legitimamente começa por "v"'    => ['v2-beta', 'v2-beta', 'vv2-beta'],
])->group('kit');

/**
 * [CT-15] a versão já gravada com "v" não é migrada nem normalizada.
 *
 * `Quando` grava OUTRO campo de propósito: é o único jeito de provar que a versão não é tocada
 * por um `save` que passa por ela. `vv1.2.3` não é defeito aqui — é o oráculo.
 */
it('[CT-15] a versão já gravada com "v" não é migrada nem normalizada', function (): void {
    emJunhoDe2026();
    gravarConfiguracao('versao_do_sistema', 'v1.2.3');
    comIdentidade('Acme', null, exibirKit: false, recado: null);
    alinharConfiguracoesDoKit();

    $this->actingAs(usuarioDoKit('admin'));
    Filament::setCurrentPanel('admin');

    $tela = Livewire::test(ConfiguracoesDoKit::class);

    $campo = $tela->instance()->getSchemaComponent('form.versao_do_sistema');

    expect($campo->getState())->toBe('v1.2.3')
        ->and($campo->getPrefixLabel())->toBe('v');

    $tela->fillForm(['nome_da_aplicacao' => 'Marca Nova'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect(configuracaoGravada('versao_do_sistema'))->toBe('v1.2.3');

    alinharConfiguracoesDoKit();

    $rodape = rodapeDe((string) $this->get('/admin')->assertOk()->getContent());

    $this->assertStringContainsString('vv1.2.3', $rodape);
})->group('kit');

/**
 * [CT-21] apagar a versão pela tela esvazia o rodapé, sem o campo virar obrigatório.
 *
 * M49 (adversarial, rodada 2): o eixo de GRAVAÇÃO de vazio não existia — todas as partições de
 * vazio entravam por `config()->set()`. Este é o único cenário que esvazia a versão PELO
 * FORMULÁRIO.
 */
it('[CT-21] apagar a versão pela tela esvazia o rodapé, sem o campo virar obrigatório', function (): void {
    emJunhoDe2026();
    // `alinharConfiguracoesDoKit()` relê TODAS as chaves do banco e sobrescreve `config()`; sem
    // gravar o nome também na tabela, ele voltaria ao default da settings (não "Acme") depois do
    // alinhamento — medido.
    gravarConfiguracao('nome_da_aplicacao', 'Acme');
    gravarConfiguracao('versao_do_sistema', '2.4.0');
    comIdentidade('Acme', '2.4.0', exibirKit: false, recado: null);

    $this->actingAs(usuarioDoKit('admin'));
    Filament::setCurrentPanel('admin');

    Livewire::test(ConfiguracoesDoKit::class)
        ->fillForm(['versao_do_sistema' => ''])
        ->call('save')
        ->assertHasNoFormErrors(['versao_do_sistema']);

    expect(configuracaoGravada('versao_do_sistema'))->toBeNull();

    alinharConfiguracoesDoKit();

    $html = (string) $this->get('/admin')->assertOk()->getContent();

    expect(assinaturaDoRodape($html))->toBe('© 2026 Acme');
})->group('kit');

/*
|--------------------------------------------------------------------------
| R6 — valor vazio não deixa sobra
|--------------------------------------------------------------------------
*/

/**
 * [CT-16] a composição exata, com a versão do kit desligada.
 *
 * Oráculo de IGUALDADE EXATA — o único jeito de matar `Acme ©`, `© Acme ·` e `· v2.4.0` de uma
 * vez. `"0"` e `"   "` são as duas fronteiras de PHP que separam `filled()` de `! empty()`
 * (`empty("0") === true`, `! empty("   ") === true`). A linha `(elemento ausente)` afirma sobre o
 * MARCADOR no HTML, nunca sobre a string normalizada — `assinaturaDoRodape()` devolve `""` tanto
 * para ausente quanto para presente-e-vazio.
 */
it('[CT-16] a composição exata, com a versão do kit desligada', function (?string $nome, ?string $versao, ?string $esperado): void {
    emJunhoDe2026();
    comIdentidade($nome, $versao, exibirKit: false, recado: null);

    $html = (string) $this->actingAs(usuarioDoKit('admin'))->get('/admin')->assertOk()->getContent();

    if ($esperado === null) {
        /*
         * CONTROLE POSITIVO NA MESMA ROTA, e ele vem antes.
         *
         * `rodapeDe()` devolve '' em silencio quando nao acha o ancora, e string vazia satisfaz
         * qualquer `assertStringNotContainsString`. Sem esta linha a ausencia abaixo ficaria
         * verde sobre nada — e o docblock do proprio helper exige o controle NA MESMA ROTA, nao
         * noutra. Achado QA-06 do quality gate, que eu tinha declarado como lacuna irredutivel
         * quando custava uma linha.
         */
        expect(rodapeDe($html))->not->toBe('', 'rodapeDe() nao achou o rodape nesta rota — a ausencia abaixo mediria o vazio');

        $this->assertStringNotContainsString('kit-versao', rodapeDe($html));

        return;
    }

    expect(assinaturaDoRodape($html))->toBe($esperado);
})->with([
    'tudo preenchido'                      => ['Acme', '2.4.0', '© 2026 Acme · v2.4.0'],
    'sem versão — sem separador solto'     => ['Acme', '', '© 2026 Acme'],
    'nulo ≠ vazio, mesmo Então'            => ['Acme', null, '© 2026 Acme'],
    'branco — separa filled() de !empty()' => ['Acme', '   ', '© 2026 Acme'],
    '"0" — empty("0") é true em PHP'       => ['Acme', '0', '© 2026 Acme · v0'],
    'sem nome — sem © órfão'               => ['', '2.4.0', 'v2.4.0'],
    'nada a mostrar — elemento ausente'    => ['', '', null],
    'nome de 120 caracteres, sem truncar'  => [str_repeat('N', 120), '2.4.0', '© 2026 '.str_repeat('N', 120).' · v2.4.0'],
])->group('kit');

/**
 * [CT-17] com a versão do kit ligada, nenhum segmento nasce vazio.
 *
 * Ganhou controle positivo (o valor de `kit.version` tem de estar presente) e contagem EXATA de
 * segmentos — sem eles as três cláusulas eram vacuamente verdadeiras com o rodapé ausente. A
 * ordem checada é assinatura → versão do sistema → versão do kit (M50).
 */
it('[CT-17] com a versão do kit ligada, nenhum segmento nasce vazio', function (string $nome, string $versao, int $segmentos): void {
    emJunhoDe2026();
    comIdentidade($nome, $versao, exibirKit: true, recado: null);

    $html     = (string) $this->actingAs(usuarioDoKit('admin'))->get('/admin')->assertOk()->getContent();
    $conteudo = assinaturaDoRodape($html);

    $this->assertStringContainsString((string) config('kit.version'), $conteudo);

    $partes = explode(' · ', $conteudo);

    expect($partes)->toHaveCount($segmentos);

    foreach ($partes as $parte) {
        expect(trim($parte))->not->toBe('');
    }

    expect($conteudo)->not->toStartWith(' · ')
        ->and($conteudo)->not->toEndWith(' · ');

    $indice = 0;

    if ($nome !== '') {
        $this->assertStringStartsWith('© ', trim($partes[$indice]));
        $indice++;
    }

    if ($versao !== '') {
        $this->assertStringContainsString($versao, $partes[$indice]);
        $indice++;
    }

    $this->assertStringContainsString((string) config('kit.version'), $partes[$indice]);
})->with([
    'assinatura + versão + kit'    => ['Acme', '2.4.0', 3],
    'o buraco no meio'             => ['Acme', '', 2],
    'o buraco na frente'           => ['', '2.4.0', 2],
    'só a do kit — segmento único' => ['', '', 1],
])->group('kit');

/**
 * [CT-18] para o visitante, a assinatura é a linha inteira.
 *
 * Roda em `/admin/login`, onde o `Dado` grava uma versão que EXISTE e não pode sair: fecha a
 * partição de vazios e a guarda, no mesmo mundo.
 */
it('[CT-18] para o visitante, a assinatura é a linha inteira', function (string $nome, bool $exibirKit, ?string $esperado): void {
    emJunhoDe2026();
    comIdentidade($nome, '9.9.9-secreta', $exibirKit, recado: null);
    ligarLoginUnificado(false);

    $html = (string) $this->get('/admin/login')->assertOk()->getContent();

    if ($esperado === null) {
        /*
         * CONTROLE POSITIVO NA MESMA ROTA, e ele vem antes.
         *
         * `rodapeDe()` devolve '' em silencio quando nao acha o ancora, e string vazia satisfaz
         * qualquer `assertStringNotContainsString`. Sem esta linha a ausencia abaixo ficaria
         * verde sobre nada — e o docblock do proprio helper exige o controle NA MESMA ROTA, nao
         * noutra. Achado QA-06 do quality gate, que eu tinha declarado como lacuna irredutivel
         * quando custava uma linha.
         */
        expect(rodapeDe($html))->not->toBe('', 'rodapeDe() nao achou o rodape nesta rota — a ausencia abaixo mediria o vazio');

        $this->assertStringNotContainsString('kit-versao', rodapeDe($html));

        return;
    }

    expect(assinaturaDoRodape($html))->toBe($esperado);
    $this->assertStringNotContainsString('9.9.9-secreta', $html);
    $this->assertStringNotContainsString((string) config('kit.version'), $html);
})->with([
    'caminho comum'                     => ['Acme', false, '© 2026 Acme'],
    'o toggle não abre nada ao anônimo' => ['Acme', true, '© 2026 Acme'],
    'sem nome e sem versão visível'     => ['', false, null],
    'idem, com o toggle ligado'         => ['', true, null],
])->group('kit');

/*
|--------------------------------------------------------------------------
| R7 — o nome da aplicação sai escapado na superfície pública
|--------------------------------------------------------------------------
*/

/**
 * [CT-19] a marcação gravada no nome sai escapada, não crua nem descartada.
 *
 * Três saídas possíveis para o nome, e só uma é correta: escapado (`&lt;script&gt;...`, ✅), cru
 * (`<script>...`, ❌ pela negativa) ou pelo Markdown do recado — que DESCARTA HTML (`html_input:
 * strip`) e devolveria só "Acme", mutilado (❌ pela positiva). Um oráculo só-negativo daria verde
 * nas duas últimas — é por isso que a forma escapada literal é afirmada, não só a ausência da
 * crua. Esta regra NÃO usa `assinaturaDoRodape()`: o helper faz `strip_tags` +
 * `html_entity_decode`, que colapsam a forma escapada e a crua na mesma string — aqui a asserção é
 * sobre o HTML BRUTO.
 */
it('[CT-19] a marcação gravada no nome sai escapada, não crua nem descartada', function (string $persona, string $rota): void {
    emJunhoDe2026();
    comIdentidade('<script>alert(1)</script>Acme', '2.4.0', exibirKit: false, recado: null);
    ligarLoginUnificado(false);

    $resposta = $persona === 'visitante'
        ? $this->get($rota)
        : $this->actingAs(usuarioDoKit('admin'))->get($rota);

    $html = (string) $resposta->assertOk()->getContent();

    $this->assertStringContainsString('&lt;script&gt;alert(1)&lt;/script&gt;Acme', $html);
    $this->assertStringNotContainsString('<script>alert(1)', $html);
})->with([
    'superfície pública e anônima' => ['visitante', '/admin/login'],
    'superfície autenticada'       => ['admin', '/admin'],
])->group('kit');

/**
 * [CT-20] a assinatura não passa pelo renderizador de Markdown do recado.
 *
 * Exige a assinatura E o recado na MESMA resposta: só a negativa deixaria verde a implementação
 * que apaga a assinatura no caminho do recado. A ênfase aplicada (`<strong>`) prova que o Markdown
 * do recado CONTINUA funcionando ao lado da assinatura escapada — sem essa segunda metade, a
 * "correção" preguiçosa de escapar tudo (quebrando o recado) passaria.
 */
it('[CT-20] a assinatura não passa pelo renderizador de Markdown do recado', function (): void {
    emJunhoDe2026();
    comIdentidade('<script>alert(1)</script>Acme', null, exibirKit: false, recado: '**Fale com o suporte**');
    ligarLoginUnificado(false);

    $html = (string) $this->get('/admin/login')->assertOk()->getContent();

    $this->assertStringContainsString('&lt;script&gt;alert(1)&lt;/script&gt;Acme', $html);
    $this->assertStringContainsString('<strong>Fale com o suporte</strong>', $html);
    $this->assertStringNotContainsString('<script>alert(1)', $html);
})->group('kit');
