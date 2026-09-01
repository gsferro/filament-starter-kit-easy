<?php

use App\Models\Convite;
use App\Models\Role;
use App\Support\IdentidadeDoKit;
use Database\Seeders\PapeisSeeder;
use Database\Seeders\ShieldPermissionsSeeder;
use Illuminate\Support\Facades\Storage;

/**
 * Logo, favicon e arte de login: da configuração até o HTML dos painéis.
 *
 * IDs de CT em `wikis/specs/feat/settings-do-kit/settings-do-kit/04-casos-de-teste.md`.
 *
 * CT-26 afirma sobre o RESOLVEDOR; CT-35 e CT-35b afirmam sobre o HTML
 * renderizado, e existem por causa da rodada 2 da revisão adversarial: uma
 * implementação em que `IdentidadeDoKit` está perfeita e **nenhum painel a
 * consome** passava no conjunto inteiro. `brandLogo` ausente e arte literal nos
 * providers, com o resolvedor impecável e testado.
 */
beforeEach(function (): void {
    Storage::fake('public');
});

/**
 * CT-26 — a resolução do arquivo de identidade cobre as três partições.
 *
 * A partição do meio é a que justifica a classe existir: caminho declarado e
 * arquivo AUSENTE no disco. Sem a guarda, o `<link rel="icon">` de toda página
 * pediria um 404 — e acontece de verdade, quando alguém apaga
 * `storage/app/public/kit/` ou clona o repositório sem o `storage/` do colega.
 *
 * **Logo e favicon devolvem `null`; só a arte tem fallback.** A assimetria é
 * deliberada: com `null`, o Filament cai no brand em texto e no ícone dele, o que
 * é um default legítimo. Já `->media()` do Auth Designer recebendo `null` deixaria
 * a tela de autenticação sem imagem, que é regressão visível.
 */
it('resolve a logo para url publica quando o arquivo existe', function (): void {
    Storage::disk('public')->put('kit/logo.png', 'conteúdo');
    config(['kit.identidade.logo' => 'kit/logo.png']);

    expect(IdentidadeDoKit::logo())->toBe(Storage::disk('public')->url('kit/logo.png'));
})->group('kit');

it('devolve nulo para logo e favicon quando o arquivo declarado nao esta no disco', function (string $chave, string $metodo): void {
    config([$chave => 'kit/nao-existe.png']);

    $canal = espiarConfiguracoes();

    expect(IdentidadeDoKit::{$metodo}())->toBeNull();

    $canal->shouldHaveReceived('warning');
})->with([
    'logo'    => ['kit.identidade.logo', 'logo'],
    'favicon' => ['kit.identidade.favicon', 'favicon'],
])->group('kit');

it('devolve nulo para logo e favicon quando nada esta configurado', function (string $metodo): void {
    expect(IdentidadeDoKit::{$metodo}())->toBeNull();
})->with(['logo', 'favicon'])->group('kit');

it('resolve a arte do login para o arquivo enviado quando ele existe', function (): void {
    Storage::disk('public')->put('kit/arte.svg', '<svg/>');
    config(['kit.identidade.arte_do_login' => 'kit/arte.svg']);

    expect(IdentidadeDoKit::arteDoLogin())->toBe(Storage::disk('public')->url('kit/arte.svg'));
})->group('kit');

/**
 * CT-08 — a precedência da arte enviada não muda com o fallback novo.
 *
 * Substitui o `cai na arte padrao do kit quando nao ha arquivo utilizavel` desta
 * mesma suíte, que afirmava `toBe(asset(IdentidadeDoKit::ARTE_PADRAO))`. A
 * constante deixou de existir com a wiki `arte-do-login-com-nome-da-aplicacao`:
 * o fallback passou de arquivo em `public/` para SVG gerado com o nome dentro.
 * As partições são as mesmas; só o valor esperado foi reancorado.
 *
 * Cada linha carrega a metade negativa, e são metades diferentes. Na enviada, o
 * valor NÃO é data URI — é ela que separa "a precedência existe" de "o fallback
 * passou na frente". Na declarada-e-ausente, o valor NÃO é a URL do arquivo
 * declarado — sem isso o caso repete R1 e deixa passar a implementação que
 * devolve URL para arquivo inexistente, que é o 404 no `<head>` que esta classe
 * foi criada para impedir.
 */
it('mantem a precedencia da arte enviada sobre a arte padrao gerada', function (?string $configurado, bool $noDisco, bool $avisa): void {
    if ($noDisco) {
        Storage::disk('public')->put((string) $configurado, '<svg/>');
    }

    config([
        'app.name'                        => 'Prefeitura de Itabira',
        'kit.identidade.arte_do_login'    => $configurado,
    ]);

    $canal = espiarConfiguracoes();

    $arte = IdentidadeDoKit::arteDoLogin();

    if ($noDisco) {
        expect($arte)
            ->toBe(Storage::disk('public')->url((string) $configurado))
            ->not->toStartWith('data:');
    } else {
        expect(textoDoSvg(documentoDoSvg(svgDaArte($arte))))->toBe('Prefeitura de Itabira');

        if ($configurado !== null) {
            expect($arte)->not->toBe(Storage::disk('public')->url($configurado));
        }
    }

    $avisa
        ? $canal->shouldHaveReceived('warning')
        : $canal->shouldNotHaveReceived('warning');
})->with([
    'enviada, com o arquivo no disco'           => ['kit/arte.svg', true, false],
    'declarada, com o arquivo ausente no disco' => ['kit/apagada.svg', false, true],
    'não configurada'                           => [null, false, false],
])->group('kit');

/**
 * CT-35 — o nome, a logo e o favicon gravados aparecem no HTML dos três painéis.
 *
 * O `assertDontSee` do nome do AMBIENTE é a asserção discriminante, e sem ela o
 * caso não vale nada: com a marca literal no provider e o alinhamento funcionando,
 * o HTML conteria as DUAS coisas — a marca velha na topbar e o nome novo no
 * `<title>` —, e um `assertSee` do nome novo passaria com `brandName` congelado.
 * Foi exatamente o que a rodada 2 da revisão apontou.
 */
it('serve o nome, a logo e o favicon gravados nas telas dos tres paineis', function (string $rota): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);

    Storage::disk('public')->put('kit/logo.png', 'conteúdo');
    Storage::disk('public')->put('kit/favicon.png', 'conteúdo');

    $doAmbiente = (string) config('app.name');

    config([
        'app.name'                  => 'Nome Do Banco',
        'kit.identidade.logo'       => 'kit/logo.png',
        'kit.identidade.favicon'    => 'kit/favicon.png',
    ]);

    $this->actingAs(usuarioDoKit('master_global'));

    $this->get($rota)
        ->assertOk()
        ->assertSee('Nome Do Banco')
        ->assertDontSee($doAmbiente)
        ->assertSee(Storage::disk('public')->url('kit/logo.png'), escape: false)
        ->assertSee(Storage::disk('public')->url('kit/favicon.png'), escape: false);
})->with([
    'painel de administração'  => '/admin',
    'painel de negócio'        => '/app',
    'painel de infraestrutura' => '/infra',
])->group('kit');

/**
 * CT-35b — a arte gravada veste as telas de login dos três painéis.
 *
 * O par `assertSee` da arte gravada + `assertDontSee` do caminho padrão é o que
 * distingue "a fiação existe" de "a tela serve as duas". Sem sessão de propósito:
 * a tela de login é anônima, e é justamente por isso que os arquivos de identidade
 * precisam ser públicos.
 */
it('veste as telas de login com a arte gravada', function (string $rota): void {
    Storage::disk('public')->put('kit/arte.svg', '<svg/>');

    config(['kit.identidade.arte_do_login' => 'kit/arte.svg']);

    $this->get($rota)
        ->assertOk()
        ->assertSee(Storage::disk('public')->url('kit/arte.svg'), escape: false)
        // Reancorada: o caminho `images/auth/login.svg` deixou de existir. A metade
        // discriminante passa a ser que a mídia NÃO é a arte gerada — se a
        // precedência quebrasse, o `src` viria como data URI.
        ->assertDontSee('data:image/svg+xml;base64,', escape: false);
})->with([
    'login do /admin' => '/admin/login',
    'login do /app'   => '/app/login',
    'login do /infra' => '/infra/login',
])->group('kit');

/*
|--------------------------------------------------------------------------
| A arte padrão com o nome da aplicação
|--------------------------------------------------------------------------
|
| Wiki `feat/arte-do-login-com-nome-da-aplicacao`. IDs de CT no `04` de lá.
|
| A armadilha que decide o conjunto inteiro: os dez `->media()` já passam
| `alt: config('app.name')`, então `assertSee(config('app.name'))` numa tela de
| login PASSA antes de a feature existir. E `assertDontSee('Laravel 13 · …')`
| também passa, porque em base64 nenhum texto do SVG aparece cru. Por isso
| nenhum caso daqui afirma sobre o HTML direto: todos decodificam o `src` e
| afirmam sobre o DOCUMENTO.
|
*/

/**
 * O SVG por trás do data URI, com o envelope afirmado no caminho (CT-02).
 */
function svgDaArte(string $valor): string
{
    $prefixo = 'data:image/svg+xml;base64,';

    expect($valor)->toStartWith($prefixo);

    $payload = substr($valor, strlen($prefixo));

    expect($payload)->toMatch('/^[A-Za-z0-9+\/]+={0,2}$/');

    $svg = base64_decode($payload, true);

    expect($svg)->toBeString();

    return (string) $svg;
}

/**
 * O SVG como documento, falhando o caso quando o XML é inválido.
 *
 * É metade do oráculo duplo de CT-05: sem escape, um nome com `&` produz um
 * documento que NÃO parseia, e sem esta asserção o caso morreria adiante com
 * uma mensagem que não explica nada.
 */
function documentoDoSvg(string $svg): DOMDocument
{
    $documento = new DOMDocument;

    $anterior = libxml_use_internal_errors(true);
    $valido   = $documento->loadXML($svg);
    libxml_clear_errors();
    libxml_use_internal_errors($anterior);

    expect($valido)->toBeTrue();

    return $documento;
}

/** Os elementos de texto do SVG — o `local-name()` evita o namespace default. */
function nosDeTextoDoSvg(DOMDocument $documento): DOMNodeList
{
    return (new DOMXPath($documento))->query('//*[local-name()="text"]');
}

/**
 * O texto de TODOS os nós de texto, concatenado.
 *
 * `textContent` e não o idiomático `(string) $xml->xpath('//text')[0]`: o
 * segundo devolve só os nós de caractere diretos e IGNORA um `<tspan>` filho.
 * Uma implementação que mantivesse a segunda linha como `<tspan>` dentro do
 * único `<text>` passaria pela contagem e pela comparação.
 */
function textoDoSvg(DOMDocument $documento): string
{
    $texto = '';

    foreach (nosDeTextoDoSvg($documento) as $no) {
        $texto .= $no->textContent;
    }

    return $texto;
}

/** O `fill` do elemento ou do ancestral mais próximo que o declare. */
function preenchimentoEfetivo(DOMElement $no): ?string
{
    for ($atual = $no; $atual instanceof DOMElement; $atual = $atual->parentNode) {
        if ($atual->hasAttribute('fill')) {
            return $atual->getAttribute('fill');
        }
    }

    return null;
}

/**
 * A mídia de autenticação da página, como elemento.
 *
 * Devolve o elemento e não o `src` porque o nome da TAG é oráculo: o Auth
 * Designer escolhe entre `<img>` e `<video>` por extensão de arquivo, e um data
 * URI base64 não tem extensão. Se caísse no ramo de vídeo a arte sumiria da
 * tela, e uma asserção só sobre o `src` não veria.
 */
function midiaDeAutenticacao(string $html): DOMElement
{
    $documento = new DOMDocument;

    $anterior = libxml_use_internal_errors(true);
    $documento->loadHTML('<?xml encoding="UTF-8">'.$html);
    libxml_clear_errors();
    libxml_use_internal_errors($anterior);

    $midias = (new DOMXPath($documento))
        ->query("//*[contains(concat(' ', normalize-space(@class), ' '), ' fi-auth-media ')]");

    expect($midias->length)->toBe(1);

    $midia = $midias->item(0);

    expect($midia)->toBeInstanceOf(DOMElement::class);

    return $midia;
}

/** A arte servida por uma tela, já decodificada. */
function arteDaTela(string $html): DOMDocument
{
    $midia = midiaDeAutenticacao($html);

    expect($midia->tagName)->toBe('img');

    return documentoDoSvg(svgDaArte($midia->getAttribute('src')));
}

/**
 * CT-01 — o nome configurado está na arte, e o texto fixo do kit não está.
 *
 * O literal `starter-kit-easy` é a metade discriminante: é o que RQ-01 manda
 * sair, e não depende do ambiente.
 */
it('leva o nome da aplicacao para dentro da arte padrao', function (): void {
    config(['app.name' => 'Prefeitura de Itabira']);

    $svg = svgDaArte(IdentidadeDoKit::arteDoLogin());

    expect(textoDoSvg(documentoDoSvg($svg)))->toContain('Prefeitura de Itabira')
        ->and($svg)->not->toContain('starter-kit-easy');
})->group('kit');

/**
 * CT-02 — a arte é entregue como imagem utilizável, e o documento é válido.
 *
 * O envelope é o que distingue "a string tem o nome dentro" de "a tela tem uma
 * imagem": data URI cru sem base64, markup solto sem o prefixo `data:` e
 * payload truncado produzem, todos, uma string com o nome e uma tela sem arte.
 */
it('entrega a arte padrao como data uri de svg valido', function (): void {
    $documento = documentoDoSvg(svgDaArte(IdentidadeDoKit::arteDoLogin()));

    expect($documento->documentElement?->localName)->toBe('svg');
})->group('kit');

/**
 * CT-03 — trocar o nome muda a arte na leitura seguinte.
 *
 * Duas leituras no MESMO processo é o ponto: é assim que memoização estática
 * morre. E a primeira leitura é afirmada no `Então`, não escondida no arranjo —
 * sem afirmá-la, o caso não distingue "releu" de "nunca leu o primeiro valor".
 *
 * `config()->set()` e não `putenv()` de propósito: uma implementação que leia
 * `env('APP_NAME')` sobrevive ao set e morre aqui, na segunda leitura.
 */
it('le o nome da aplicacao a cada resolucao da arte', function (): void {
    config(['app.name' => 'Antes da Troca']);

    $primeira = svgDaArte(IdentidadeDoKit::arteDoLogin());

    config(['app.name' => 'Depois da Troca']);

    $segunda = svgDaArte(IdentidadeDoKit::arteDoLogin());

    expect(textoDoSvg(documentoDoSvg($primeira)))->toBe('Antes da Troca')
        ->and(textoDoSvg(documentoDoSvg($segunda)))->toBe('Depois da Troca')
        ->and($segunda)->not->toContain('Antes da Troca');
})->group('kit');

/**
 * CT-05 — o nome atravessa a arte íntegro, seja qual for o caractere.
 *
 * Oráculo duplo de propósito. "O documento parseia" sozinho não basta:
 * `Obras <Municipais>` SEM escape produz um documento válido — o `<Municipais>`
 * vira elemento aninhado — cujo texto perde os sinais. "O texto é o nome"
 * sozinho também não basta: documento inválido nem chega a ter texto.
 *
 * A linha do `&` é a que mata o escape duplo: `{{ e($nome) }}` produz
 * `&amp;amp;`, que parseia de volta como `&amp;` — texto diferente do nome.
 *
 * A comparação de COMPRIMENTO é o que mata truncamento com teto alto:
 * `Str::limit($nome, 100)` sobrevive a qualquer linha mais curta que o teto, e
 * é por isso que existe a linha de 255.
 */
it('preserva o nome integro dentro da arte, seja qual for o caractere', function (string $nome): void {
    config(['app.name' => $nome]);

    $documento = documentoDoSvg(svgDaArte(IdentidadeDoKit::arteDoLogin()));

    expect(nosDeTextoDoSvg($documento)->length)->toBe(1)
        ->and(textoDoSvg($documento))->toBe($nome)
        ->and(mb_strlen(textoDoSvg($documento)))->toBe(mb_strlen($nome));
})->with([
    'comum (linha de referência)'  => 'Prefeitura de Itabira',
    'E comercial'                  => 'Silva & Cia',
    'menor e maior'                => 'Obras <Municipais> e Urbanismo',
    'aspas e apóstrofo'            => 'Colégio "Dom Pedro" d\'Alcântara',
    'acento e emoji (4 bytes)'     => 'Instituição de Educação e Inovação 🏫',
    '1 caractere (borda inferior)' => 'X',
    'nome longo (ADR-02)'          => 'Secretaria Municipal de Obras, Urbanismo, Habitação e Desenvolvimento Territorial Sustentável',
    'borda superior de um varchar' => str_repeat('a', 255),
    // @premissa: o `00` não diz o que acontece com `APP_NAME=""`. Assumido: a
    // arte sai válida, com o texto vazio — não volta para "starter-kit-easy" e
    // não estoura.
    'vazio (premissa)'             => '',
])->group('kit');

/**
 * CT-06 — a segunda linha de texto não existe mais na arte.
 *
 * A CONTAGEM é o oráculo; a ausência da frase é apoio. `não contém "Laravel 13"`
 * sozinho é falso positivo: em base64 nenhuma frase aparece crua, e a asserção
 * passaria com as duas linhas intactas. Quem falsifica é "exatamente um elemento
 * de texto", que também mata o mutante que ninguém prevê — a segunda linha
 * passar a repetir o nome.
 *
 * A geometria é oráculo, não detalhe: ao virar view é perfeitamente possível
 * perder `x`, `y`, `font-size` ou `fill`. O resultado é documento válido, um nó
 * de texto, texto exato, imagem pintando — e o nome fora do `viewBox` ou na cor
 * do fundo. Tudo verde, e RQ-01 não entregue.
 */
it('exibe somente o nome, com um unico elemento de texto visivel', function (): void {
    config(['app.name' => 'Prefeitura de Itabira']);

    $svg       = svgDaArte(IdentidadeDoKit::arteDoLogin());
    $documento = documentoDoSvg($svg);

    expect(nosDeTextoDoSvg($documento)->length)->toBe(1)
        ->and(textoDoSvg($documento))->toBe('Prefeitura de Itabira')
        ->and($svg)->not->toContain('Laravel 13');

    $texto = $documento->getElementsByTagName('text')->item(0);

    expect($texto)->toBeInstanceOf(DOMElement::class);

    expect((float) $texto->getAttribute('x'))->toBeGreaterThanOrEqual(0.0)->toBeLessThan(800.0)
        ->and((float) $texto->getAttribute('y'))->toBeGreaterThan(0.0)->toBeLessThan(1000.0)
        ->and((float) $texto->getAttribute('font-size'))->toBeGreaterThan(0.0)
        ->and(preenchimentoEfetivo($texto))->not->toBeNull()->not->toBe('url(#bg)');
})->group('kit');

/**
 * CT-07 — a forma da arte permanece: sai o texto, não o desenho. @premissa
 *
 * O `00` declara que "sem outro texto" NÃO inclui a marca d'água visual. Se a
 * premissa for negada, este caso inverte — e é por isso que ele existe escrito.
 *
 * A área de desenho fecha o mutante que o desenho sozinho não pega: quem
 * reescreve o SVG como view pode perder o `viewBox` ou o `preserveAspectRatio`,
 * e a arte sai ESTICADA na coluna lateral, com todos os círculos no lugar.
 */
it('preserva o desenho da arte ao trocar o texto', function (): void {
    $documento = documentoDoSvg(svgDaArte(IdentidadeDoKit::arteDoLogin()));
    $xpath     = new DOMXPath($documento);

    expect($xpath->query('//*[local-name()="circle"]')->length)->toBe(5)
        ->and($xpath->query('//*[local-name()="linearGradient" or local-name()="radialGradient"]')->length)->toBe(2)
        ->and($documento->documentElement?->getAttribute('viewBox'))->toBe('0 0 800 1000')
        ->and($documento->documentElement?->getAttribute('preserveAspectRatio'))->toBe('xMidYMid slice');
})->group('kit');

/**
 * CT-09 — cada tela pública de autenticação serve a arte com o nome.
 *
 * Vale um caso sobre as TELAS, e não só sobre o método: o `04` da feature
 * ancestral registra que uma implementação com `IdentidadeDoKit` perfeita e
 * NENHUM painel consumindo passava no conjunto inteiro. Aqui o risco é maior —
 * a forma do valor muda de URL para data URI, e um painel deixado com o literal
 * `asset('images/auth/login.svg')` continua compilando, continua respondendo 200
 * e serve imagem para um arquivo que esta feature removeu.
 *
 * `assertDontSee($doAmbiente)` é a metade que mata o congelamento no boot.
 */
it('serve a arte com o nome em cada tela publica de autenticacao', function (string $rota): void {
    $doAmbiente = (string) config('app.name');

    config(['app.name' => 'Prefeitura de Itabira']);

    $resposta = $this->get($rota)->assertOk();

    expect(textoDoSvg(arteDaTela($resposta->getContent())))->toContain('Prefeitura de Itabira');

    $resposta
        ->assertDontSee('images/auth/login.svg', escape: false)
        ->assertDontSee($doAmbiente);
})->with([
    'login do /admin'                => '/admin/login',
    'login do /app'                  => '/app/login',
    'login do /infra'                => '/infra/login',
    'recuperação de senha do /admin' => '/admin/password-reset/request',
    'recuperação de senha do /app'   => '/app/password-reset/request',
    'recuperação de senha do /infra' => '/infra/password-reset/request',
])->group('kit');

/**
 * CT-10 — as telas de autenticação com pré-condição servem a mesma arte.
 *
 * As duas últimas linhas são o achado da revisão adversarial do `04`, e são o
 * motivo de este caso existir separado de CT-09: a tela de bloqueio e o desafio
 * de 2FA NÃO têm `->media()` próprio. Cada uma declara uma chave (`login` e
 * `password-reset`) e HERDA a configuração da tela correspondente. Isso as torna
 * consumidoras da arte sem aparecer em nenhuma das dez chamadas — e uma matriz
 * fechada por "rota de autenticação" as deixaria de fora, com a arte quebrada e
 * o conjunto verde.
 *
 * Os arranjos são inline de propósito: `exigenciaDeEmail()` e
 * `usuarioSemEmailValidado()` são helpers LOCAIS de `VerificacaoDeEmailTest`, e
 * usá-los daqui exigiria movê-los para `tests/Pest.php` sem ganho nenhum.
 */
it('serve a arte com o nome nas telas de autenticacao com pre-condicao', function (string $tela): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);

    $doAmbiente = (string) config('app.name');

    config(['app.name' => 'Prefeitura de Itabira']);

    $resposta = match ($tela) {
        // chave `registration`
        'aceite de convite' => $this->get('/app/register?token='.Convite::factory()->create([
            'email'   => 'convidado@example.com',
            'role_id' => Role::findByName('panel_user')->getKey(),
        ])->enviar()),

        // chave `email-verification`
        'verificação de e-mail' => (function () {
            config(['kit.registro.verificar_email' => true]);

            $usuario = usuarioDoKit('panel_user');
            $usuario->forceFill(['email_verified_at' => null])->save();

            return $this->actingAs($usuario)->followingRedirects()->get('/app');
        })(),

        // chave `password-reset`, HERDADA — a tela não tem `media()` própria
        'desafio de 2FA' => $this->actingAs(usuarioDoKit('master_global'))
            ->get('/admin/two-factor-authentication'),

        // chave `login`, HERDADA — a tela não tem `media()` própria
        'bloqueio de sessão' => (function () {
            $this->actingAs(usuarioDoKit('master_global'));
            $this->post(route('lockscreen.admin.lock-session'));

            return $this->get('/admin/screen/lock');
        })(),
    };

    $resposta->assertSuccessful();

    expect(textoDoSvg(arteDaTela($resposta->getContent())))->toContain('Prefeitura de Itabira');

    $resposta
        ->assertDontSee('images/auth/login.svg', escape: false)
        ->assertDontSee($doAmbiente);
})->with([
    'aceite de convite no /app'     => 'aceite de convite',
    'verificação de e-mail do /app' => 'verificação de e-mail',
    'desafio de 2FA do /admin'      => 'desafio de 2FA',
    'bloqueio de sessão do /admin'  => 'bloqueio de sessão',
])->group('kit');
