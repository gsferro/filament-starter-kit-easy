<?php

use App\Filament\Admin\Pages\ConfiguracoesDoKit;
use App\Providers\KitServiceProvider;
use App\Settings\ConfiguracoesDoKit as SettingsDoKit;
use App\Support\TetoDeUpload;
use Database\Seeders\PapeisSeeder;
use Database\Seeders\ShieldPermissionsSeeder;
use Filament\Facades\Filament;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Spatie\LaravelSettings\Models\SettingsProperty;
use Symfony\Component\Finder\Finder;

/**
 * O teto de tamanho e a recusa de SVG nos uploads do kit.
 *
 * IDs de CT em `wikis/specs/feat/upload-limite-e-tipos/upload-limite-e-tipos/04-casos-de-teste.md`.
 *
 * ## A regra do par, em todo caso
 *
 * Barreira só é barreira quando o caso que a atravessa reprova. Todo cenário aqui
 * vem em dupla — dentro do teto passa / acima é recusado, PNG passa / SVG é
 * recusado — porque um caso verde sozinho continua verde com o `->maxSize()` e o
 * `->rule('image')` REMOVIDOS, que é justamente o estado que esta feature corrige.
 *
 * ## Por que o SVG renomeado é provado por `Validator`, e não pela tela
 *
 * O `getMimeType()` de um arquivo falso do Laravel vem do NOME
 * (`vendor/laravel/framework/src/Illuminate/Http/Testing/File.php:132-135`:
 * `$this->mimeTypeToReport ?: MimeType::from($this->name)`), enquanto em produção
 * o `TemporaryUploadedFile` do Livewire lê o DISCO temporário
 * (`vendor/livewire/livewire/src/Features/SupportFileUploads/TemporaryUploadedFile.php:63-90`).
 *
 * Um cenário de componente com `createWithContent('logo.png', $svg)` NÃO provaria
 * nada sobre renomear: ele reprovaria por defeito da própria fixture, e a pressão
 * seria relaxar a asserção até ficar verde. Então o caso do renomeado usa um
 * `Illuminate\Http\UploadedFile` REAL apontando para um arquivo com bytes de SVG e
 * nome `.png` — o mesmo caminho de detecção por conteúdo que a produção usa. Ver
 * ADR-03 da wiki.
 */
beforeEach(function (): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);

    Filament::setCurrentPanel('admin');
});

/**
 * O caminho gravado de um dos campos de arquivo - o oraculo de "entrou ou nao".
 *
 * `assertHasNoFormErrors()` sozinho não distingue aceito de descartado em
 * silêncio: arquivo recusado pelo upload temporário do Livewire chega aqui como
 * "sem erro e sem gravação". Ver CT-04.
 */
function configuracaoDeUploadGravada(string $propriedade): mixed
{
    return json_decode((string) SettingsProperty::query()
        ->where('group', SettingsDoKit::group())
        ->where('name', $propriedade)
        ->value('payload'), associative: true);
}

/** Preenche um dos tres campos de arquivo da tela de configuracoes e tenta salvar. */
function enviarNaTelaDeConfiguracoes(string $campo, UploadedFile $arquivo): Testable
{
    Storage::fake('public');

    // Reaproveita quem já está autenticado: os casos em par chamam este helper
    // duas vezes, e `usuarioDoKit()` fixa o e-mail — criar de novo estoura o
    // UNIQUE de `users.email` e a falha aponta para o banco, não para a barreira.
    test()->actingAs(auth()->user() ?? usuarioDoKit('admin'));

    return Livewire::test(ConfiguracoesDoKit::class)
        ->fillForm([$campo => $arquivo])
        ->call('save');
}

/*
|--------------------------------------------------------------------------
| RQ-05 e RQ-01 — a chave, o default e a fronteira do `.env`
|--------------------------------------------------------------------------
*/

/**
 * CT-01 — o teto de fábrica é 10 MB, e a config o expõe em KILOBYTES.
 *
 * As duas asserções são o par de unidade. Só a de KB deixaria passar uma chave
 * que guardasse `10` (MB) e alimentasse `->maxSize(10)` — um teto de dez
 * kilobytes, que recusaria praticamente todo arquivo com cara de estar correto.
 */
it('[CT-01] expoe o teto de fabrica em kilobytes e em megabytes', function (): void {
    expect(config('kit.uploads.maximo_em_kb'))->toBe(10 * 1024)
        ->and(TetoDeUpload::emKb())->toBe(10240)
        ->and(TetoDeUpload::emMb())->toBe(10);
})->group('kit');

/**
 * CT-02 — a fronteira do `.env`, e o zero que não desliga a feature.
 *
 * `.ai/rules/config.md` mede este defeito em cinco chaves do kit: o segundo
 * argumento do `env()` só cobre chave AUSENTE, então `KIT_UPLOAD_MAXIMO_MB=`
 * (presente e vazia) devolve string vazia, `(int) ''` é 0, e `->maxSize(0)`
 * recusaria TODO arquivo — a feature desligada por acidente, sem erro nenhum.
 *
 * O `0` escrito à mão cai no default de propósito, e é o que separa esta chave
 * das retenções: teto de upload não tem significado para "zero", prazo de poda
 * tem. É o contraste que `NumeroDoEnv` existe para manter.
 *
 * Texto e negativo caem em **1 MB**, o piso do `positivo()`: o pior caso passa a
 * ser um teto curto e visível, que faz alguém corrigir o `.env`.
 */
it('[CT-02] nunca deixa o .env produzir um teto de zero', function (?string $bruto, int $esperadoEmKb): void {
    expect(kitConfigCom('KIT_UPLOAD_MAXIMO_MB', $bruto)['uploads']['maximo_em_kb'])->toBe($esperadoEmKb);
})->with([
    'ausente'           => [null, 10240],
    'vazia'             => ['', 10240],
    'zero à mão'        => ['0', 10240],
    'negativo'          => ['-5', 1024],
    'texto'             => ['dez', 1024],
    'valor legítimo'    => ['50', 51200],
    'valor mínimo útil' => ['1', 1024],
])->group('kit');

/**
 * CT-03 - o teto do upload TEMPORARIO do Livewire nasce da mesma chave, com folga.
 *
 * Duas asserções, e a segunda é a que a medição obrigou. Sem a linha do provider,
 * o default do pacote é `max:12288`
 * (`vendor/livewire/livewire/src/Features/SupportFileUploads/FileUploadConfiguration.php:116`)
 * — fixo, e mais estreito que o teto do kit no instante em que alguém sobe a
 * chave acima de 12 MB.
 *
 * E a folga de 1 MB não é decoração: com os tetos IGUAIS, o Livewire recusa o
 * arquivo antes de o formulário validar, e a mensagem de erro do campo fica
 * inalcançável. Ver CT-05, que é o caso que morre se a folga desaparecer.
 *
 * O segundo `Quando` mata o mutante "escrever `max:11264` literal": com um número
 * cravado, a chave nova não é respeitada.
 */
it('[CT-03] alinha o upload temporario do Livewire a chave do kit, com folga', function (): void {
    expect(config('livewire.temporary_file_upload.rules'))->toBe(['required', 'file', 'max:11264'])
        ->and(TetoDeUpload::emKbComFolgaDoLivewire())->toBeGreaterThan(TetoDeUpload::emKb());

    config()->set('kit.uploads.maximo_em_kb', 51200);

    // Closure ligada ao provider, e nao visibilidade afrouxada no codigo de
    // producao: o metodo e `protected` como os vizinhos dele, e mudar isso para o
    // teste alcancar seria deixar o teste desenhar a API.
    (function (): void {
        $this->configureTetoDeUpload();
    })->call(new KitServiceProvider(app()));

    expect(config('livewire.temporary_file_upload.rules'))->toBe(['required', 'file', 'max:52224']);
})->group('kit');

/*
|--------------------------------------------------------------------------
| RQ-01 e RQ-02 — o valor limite, nos três valores
|--------------------------------------------------------------------------
*/

/**
 * CT-04 - analise de valor limite, tres valores, nos TRES campos de arquivo.
 *
 * `max:10240` é `<=`, então a fronteira tem três pontos e os três importam:
 * 10239 e 10240 gravam, 10241 é recusado. Um `<` em vez de `<=` sobreviveria a
 * qualquer caso que só usasse 1 KB e 50 MB.
 *
 * O dataset varre os três campos porque eles saem do MESMO helper `arquivo()`: um
 * `->maxSize()` colado só no `favicon` deixaria a tela prometendo teto e
 * entregando em um terço dos campos.
 *
 * **O oráculo é o valor GRAVADO, não a ausência de erro.** Foi a correção mais
 * importante desta suíte: arquivo recusado pela camada do Livewire chega ao teste
 * como "nenhum erro e nada gravado", e um caso que só olhasse
 * `assertHasNoFormErrors()` ficaria verde sem nada ter sido salvo. Afirmar o
 * caminho gravado distingue aceito de descartado em silêncio.
 */
it('[CT-04] grava ate o teto e recusa acima dele, em todo campo de arquivo', function (string $campo, int $tamanhoEmKb, bool $recusado): void {
    enviarNaTelaDeConfiguracoes($campo, UploadedFile::fake()->image($campo.'.png')->size($tamanhoEmKb));

    $recusado
        ? expect(configuracaoDeUploadGravada($campo))->toBeNull()
        : expect(configuracaoDeUploadGravada($campo))->toBeString();
})->with([
    'logo, um abaixo do teto'     => ['logo', 10239, false],
    'logo, exatamente no teto'    => ['logo', 10240, false],
    'logo, um acima do teto'      => ['logo', 10241, true],
    'favicon, exatamente no teto' => ['favicon', 10240, false],
    'favicon, um acima do teto'   => ['favicon', 10241, true],
    'arte, exatamente no teto'    => ['arte_do_login', 10240, false],
    'arte, um acima do teto'      => ['arte_do_login', 10241, true],
])->group('kit');

/**
 * CT-05 - a recusa por tamanho fala em MB, no formulario, e nao em kilobytes.
 *
 * Este é o caso que prova que a **folga de 1 MB** no teto do Livewire serve para
 * alguma coisa. Medido: com os dois tetos iguais, um arquivo de 20 MB é recusado
 * pelo upload temporário do Livewire e o componente responde SEM erro nenhum — a
 * mensagem do campo nunca roda. Com a folga, o arquivo pouco acima do teto
 * (11 MB contra 10) chega ao formulário e é o CAMPO que recusa, com a frase que a
 * pessoa entende.
 *
 * A mensagem de fábrica do Laravel diria "não pode ser maior que 10240
 * kilobytes", porque a regra é em KB. Sem esta asserção, remover o
 * `validationMessages()` não reprovaria nada.
 */
it('[CT-05] recusa no formulario, em megabytes, o arquivo pouco acima do teto', function (): void {
    enviarNaTelaDeConfiguracoes('favicon', UploadedFile::fake()->image('grande.png')->size(11264))
        ->assertHasFormErrors(['favicon'])
        ->assertSee('O arquivo passa de 10 MB.');

    expect(configuracaoDeUploadGravada('favicon'))->toBeNull();
})->group('kit');

/**
 * CT-06 - acima da folga, o corte e do Livewire e nada e gravado.
 *
 * O oráculo aqui é deliberadamente mais fraco, porque a realidade é mais fraca: o
 * arquivo é recusado no upload temporário, antes de o formulário existir, e no
 * navegador isso é 422 no XHR com erro genérico do FilePond. O que se pode
 * afirmar — e o que importa — é que ele **não entra**.
 *
 * Sem este caso, alguém "consertaria" a UX afrouxando o teto do Livewire para um
 * número enorme, e nada reprovaria: a barreira de tamanho voltaria a ser só a do
 * formulário, com meio gigabyte de transferência antes da recusa.
 */
it('[CT-06] nao deixa entrar o arquivo muito acima do teto', function (): void {
    enviarNaTelaDeConfiguracoes('favicon', UploadedFile::fake()->image('enorme.png')->size(51200));

    expect(configuracaoDeUploadGravada('favicon'))->toBeNull();
})->group('kit');

/**
 * CT-07 - o par que define a clausula: SVG e recusado, PNG passa.
 *
 * Os dois arquivos têm o MESMO tamanho, bem abaixo do teto, de propósito: se um
 * deles fosse maior, a recusa poderia vir do `max` e o caso ficaria verde com o
 * `->rule('image')` removido.
 *
 * **A asserção é sobre a MENSAGEM, e não sobre o nome da regra**, e isso é um
 * fato do Filament, não preferência: as regras de arquivo rodam num validador
 * ANINHADO e a falha volta como `$fail($validator->errors()->first())`
 * (`vendor/filament/forms/src/Components/BaseFileUpload.php:752-772`), o que
 * descarta o nome da regra no caminho. `assertHasFormErrors(['logo' => 'image'])`
 * reprova mesmo com a recusa funcionando — e reprovaria por um motivo que não é o
 * do caso.
 */
it('[CT-07] recusa SVG e aceita PNG no mesmo campo e no mesmo tamanho', function (): void {
    enviarNaTelaDeConfiguracoes('logo', UploadedFile::fake()->create('marca.svg', 8, 'image/svg+xml'))
        ->assertHasFormErrors(['logo'])
        ->assertSee('SVG não é aceito.');

    expect(configuracaoDeUploadGravada('logo'))->toBeNull();

    enviarNaTelaDeConfiguracoes('logo', UploadedFile::fake()->image('marca.png')->size(8))
        ->assertHasNoFormErrors();

    expect(configuracaoDeUploadGravada('logo'))->toBeString();
})->group('kit');

/**
 * CT-07b - SVG e recusado nos TRES campos, e a mensagem diz o que enviar.
 *
 * Mesmo motivo do dataset de CT-04: os três campos saem de um helper, e a
 * barreira precisa valer nos três. A asserção da mensagem existe porque
 * "recusado" sem explicação manda a pessoa tentar o mesmo arquivo de novo.
 */
it('[CT-07b] recusa SVG em todo campo de arquivo, dizendo o que enviar', function (string $campo): void {
    enviarNaTelaDeConfiguracoes($campo, UploadedFile::fake()->create('arte.svg', 4, 'image/svg+xml'))
        ->assertHasFormErrors([$campo])
        ->assertSee('SVG não é aceito. Envie JPG, JPEG, PNG, GIF, BMP, WEBP, AVIF, HEIC, HEIF, ICO, TIF, TIFF.');

    expect(configuracaoDeUploadGravada($campo))->toBeNull();
})->with(['logo', 'favicon', 'arte_do_login'])->group('kit');

/**
 * CT-08 — "o restante pode ser qualquer tipo de image": um caso por formato.
 *
 * Não há classe de equivalência entre formatos de imagem — cada um é uma entrada
 * independente na lista da regra `image` do Laravel
 * (`ValidatesAttributes.php:1533`). Uma barreira apertada demais
 * (`acceptedFileTypes(['image/png'])`) atenderia RQ-03 e violaria RQ-04 sem
 * reprovar em nenhum caso que só usasse PNG.
 *
 * `UploadedFile::fake()->image()` gera imagem de verdade pelo GD para os
 * formatos que ele suporta; os demais entram por `create()` com o MIME
 * declarado, que é o que o arquivo falso do Laravel consulta.
 */
it('[CT-08] aceita os demais formatos de imagem', function (UploadedFile $arquivo): void {
    enviarNaTelaDeConfiguracoes('logo', $arquivo)->assertHasNoFormErrors();

    expect(configuracaoDeUploadGravada('logo'))->toBeString();
})->with([
    'png'  => fn (): UploadedFile => UploadedFile::fake()->image('marca.png')->size(16),
    'jpeg' => fn (): UploadedFile => UploadedFile::fake()->image('marca.jpg')->size(16),
    'gif'  => fn (): UploadedFile => UploadedFile::fake()->image('marca.gif')->size(16),
    'webp' => fn (): UploadedFile => UploadedFile::fake()->create('marca.webp', 16, 'image/webp'),
    'bmp'  => fn (): UploadedFile => UploadedFile::fake()->create('marca.bmp', 16, 'image/bmp'),
    'avif' => fn (): UploadedFile => UploadedFile::fake()->create('marca.avif', 16, 'image/avif'),
    'heic' => fn (): UploadedFile => UploadedFile::fake()->create('marca.heic', 16, 'image/heic'),
    'heif' => fn (): UploadedFile => UploadedFile::fake()->create('marca.heif', 16, 'image/heif'),
    'tiff' => fn (): UploadedFile => UploadedFile::fake()->create('marca.tiff', 16, 'image/tiff'),
    'ico'  => fn (): UploadedFile => UploadedFile::fake()->create('marca.ico', 16, 'image/vnd.microsoft.icon'),
])->group('kit');

/**
 * CT-23 - o favicon aceita `.ico` de verdade, com os bytes de um icone.
 *
 * CT-08 cobre as partições com arquivo falso, cujo MIME vem do NOME. Este cobre
 * `.ico` com CONTEÚDO, porque foi a partição que expôs o defeito e ela merece a
 * prova forte: a regra `image` do Laravel — a primeira barreira escrita nesta
 * feature — recusava um `.ico` real, e a justificativa da ADR ("favicon moderno é
 * PNG, e é o que o kit já usa") era **falsa**: o kit serve `public/favicon.ico`.
 *
 * Medido antes de trocar a regra: `guessExtension()` de um ICO real é `ico`, e a
 * regra `image` reprova. É o caso que morre se alguém voltar `->rule('image')`
 * achando que a lista do framework basta.
 *
 * O ICO é montado à mão porque nem o GD nem o `fake()` geram um: o formato aceita
 * um PNG embutido desde o Vista, e é essa a forma que os geradores de favicon
 * entregam hoje.
 *
 * Roda no `Validator` e não pela tela pelo mesmo motivo de CT-09, e por um a mais:
 * um `UploadedFile` REAL não atravessa o arnês de upload do Livewire — o
 * `Testable::upload()` acessa `$file->name`, propriedade que só existe no
 * `Illuminate\Http\Testing\File`, e o caso morre em "Undefined property". A
 * partição `.ico` pela tela, com arquivo falso, é CT-08.
 */
it('[CT-23] aceita um .ico de verdade no favicon', function (): void {
    $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8DwHwAFAAH/q842iQAAAABJRU5ErkJggg==', strict: true);
    $ico = pack('vvv', 0, 1, 1).pack('CCCCvvVV', 1, 1, 0, 0, 1, 32, strlen((string) $png), 22).$png;

    $caminho = tempnam(sys_get_temp_dir(), 'kit-ico').'-marca.ico';
    file_put_contents($caminho, $ico);

    $icone = new UploadedFile($caminho, 'marca.ico', 'image/x-icon', null, test: true);
    $lista = 'mimes:jpg,jpeg,png,gif,bmp,webp,avif,heic,heif,ico,tif,tiff';

    expect($icone->guessExtension())->toBe('ico')
        ->and(Validator::make(['f' => $icone], ['f' => 'image'])->fails())->toBeTrue()
        ->and(Validator::make(['f' => $icone], ['f' => $lista])->fails())->toBeFalse();

    unlink($caminho);
})->group('kit');

/**
 * CT-09 — a barreira lê o CONTEÚDO, então renomear não passa.
 *
 * É a pergunta que decide se esta feature entrega segurança ou teatro, e o par
 * responde as duas metades:
 *
 * 1. a regra `image` RECUSA um arquivo com bytes de SVG chamado `logo.png`, que
 *    declara `image/png` no cabeçalho do cliente;
 * 2. a regra `mimetypes:image/*` — que é o que o `->image()` do Filament gera, e
 *    era a única barreira antes desta feature — **aceita** o mesmo arquivo.
 *
 * A segunda asserção é a que dá sentido à primeira: sem ela, ninguém sabe se a
 * troca de regra mudou alguma coisa. Com ela, o caso reprova se alguém voltar a
 * confiar só no `->image()`.
 *
 * Isto roda no `Validator` e não na tela porque o arquivo falso do Laravel
 * responde o MIME pelo NOME (`Illuminate\Http\Testing\File::getMimeType()`), e
 * em produção o `TemporaryUploadedFile` do Livewire lê o disco. Ver ADR-03.
 */
it('[CT-09] recusa SVG renomeado para .png, onde o mimetypes:image/* aceitava', function (): void {
    $svgComScript = '<svg xmlns="http://www.w3.org/2000/svg" width="10" height="10">'
        .'<script>alert(document.cookie)</script><rect width="10" height="10"/></svg>';

    $caminho = tempnam(sys_get_temp_dir(), 'kit-upload').'-logo.png';
    file_put_contents($caminho, $svgComScript);

    $renomeado = new UploadedFile($caminho, 'logo.png', 'image/png', null, test: true);

    expect($renomeado->getMimeType())->toBe('image/svg+xml')
        ->and($renomeado->getClientMimeType())->toBe('image/png')
        ->and(Validator::make(['f' => $renomeado], ['f' => 'mimes:jpg,jpeg,png,gif,bmp,webp,avif,heic,heif,ico,tiff'])->fails())->toBeTrue()
        ->and(Validator::make(['f' => $renomeado], ['f' => 'mimetypes:image/*'])->fails())->toBeFalse();

    unlink($caminho);
})->group('kit');

/*
|--------------------------------------------------------------------------
| RQ-05 — o teto é LIDO da config, não cravado no código
|--------------------------------------------------------------------------
*/

/**
 * CT-24 - a fronteira do campo acompanha a config, e nao um numero cravado.
 *
 * CT-04 prova que existe uma fronteira; este prova que ela é **a da config**. Sem
 * ele, `->maxSize(10240)` escrito literalmente passaria em tudo — e RQ-05 ("o
 * tamanho maximo de upload deve ficar na config no kit") ficaria violada com a
 * suíte inteira verde, que é o estado exato de dois dos cinco campos antes desta
 * feature.
 *
 * O teto é baixado para 2 MB no arranjo, e a folga do Livewire é alargada à mão
 * porque o provider já rodou no boot deste processo — o que se está exercitando
 * aqui é o CAMPO lendo a config, não o alinhamento (que é CT-03).
 */
it('[CT-24] move a fronteira do campo quando a config muda', function (int $tamanhoEmKb, bool $recusado): void {
    config()->set('kit.uploads.maximo_em_kb', 2048);
    config()->set('livewire.temporary_file_upload.rules', ['required', 'file', 'max:1048576']);

    $resultado = enviarNaTelaDeConfiguracoes('favicon', UploadedFile::fake()->image('marca.png')->size($tamanhoEmKb));

    if ($recusado) {
        $resultado->assertHasFormErrors(['favicon'])->assertSee('O arquivo passa de 2 MB.');
        expect(configuracaoDeUploadGravada('favicon'))->toBeNull();

        return;
    }

    $resultado->assertHasNoFormErrors();
    expect(configuracaoDeUploadGravada('favicon'))->toBeString();
})->with([
    'no teto novo'                          => [2048, false],
    'acima do teto novo'                    => [2049, true],
    'abaixo do teto antigo e acima do novo' => [5120, true],
])->group('kit');

/**
 * CT-25 - nenhum `maxSize()` do kit recebe numero literal.
 *
 * Varredura, não cenário: CT-24 prova que UM campo lê a config, e o kit tem
 * cinco. A alternativa seria um caso de fronteira por campo com a config mexida,
 * o que multiplica cinco cenários lentos de componente para provar uma
 * propriedade estática do código.
 *
 * ⚠️ A asserção de AUSÊNCIA filtra comentário antes de afirmar, e não é
 * preciosismo: os docblocks desta feature CITAM `->maxSize(1024)` e
 * `->maxSize(10 * 1024)` para explicar o que saiu do código. Sem o filtro, este
 * caso reprovaria pela própria documentação — é a armadilha que
 * `.ai/rules/testes.md` documenta em três ocorrências anteriores deste
 * repositório.
 *
 * `token_get_all()` e não regex de comentário: regex não distingue `//` dentro de
 * string de `//` iniciando comentário, e o kit tem URLs em docblock.
 */
it('[CT-25] nao deixa nenhum maxSize do kit com numero cravado', function (): void {
    $comLiteral = [];

    foreach (Finder::create()->in(app_path())->files()->name('*.php') as $arquivo) {
        $codigo = implode('', array_map(
            static fn (array|string $token): string => is_string($token) ? $token : (
                in_array($token[0], [T_COMMENT, T_DOC_COMMENT], strict: true) ? ' ' : $token[1]
            ),
            token_get_all((string) file_get_contents($arquivo->getRealPath())),
        ));

        if (preg_match('/->maxSize\(\s*\d/', $codigo) === 1) {
            $comLiteral[] = $arquivo->getRelativePathname();
        }
    }

    expect($comLiteral)->toBe([], 'Teto cravado no código em: '.implode(', ', $comLiteral)
        .'. RQ-05 pede que o tamanho máximo viva na config — use App\Support\TetoDeUpload::emKb().');
})->group('kit');
