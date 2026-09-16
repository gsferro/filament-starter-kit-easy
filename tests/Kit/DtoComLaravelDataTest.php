<?php

use App\Support\GuardaDoPadraoDeDto;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * O guarda do padrão de DTO — `.ai/rules/dto.md` em forma executável.
 *
 * Os IDs de CT são os de
 * `wikis/specs/feat/laravel-data-como-padrao-de-dto/laravel-data-como-padrao-de-dto/04-casos-de-teste.md`.
 *
 * **Nada é escrito em `app/` nem em `routes/`.** As fixtures vivem numa raiz temporária sob
 * `storage/framework/testing/dto/`, e o que amarra o guarda ao mundo real são CT-13 (as raízes
 * padrão são dado) e CT-14 (o kit publicado passa verde). A revisão adversarial mediu quatro
 * riscos na escrita em caminho de produção: visibilidade entre workers no `--parallel`,
 * interrupção deixando classe defeituosa autocarregada, cache de rota e `git status` sujo.
 */
afterEach(function (): void {
    File::deleteDirectory(storage_path('framework/testing/dto'));
});

/**
 * Uma raiz temporária com os arquivos pedidos. A chave é o caminho relativo.
 *
 * @param  array<string, string>  $arquivos
 */
function raizDeFixture(array $arquivos): string
{
    $raiz = storage_path('framework/testing/dto/'.Str::random(8));

    foreach ($arquivos as $caminho => $conteudo) {
        $destino = $raiz.'/'.$caminho;
        File::ensureDirectoryExists(dirname($destino));
        File::put($destino, $conteudo);
    }

    return $raiz;
}

/** Um Data correto, na forma canônica do kit. */
function dataCorreto(string $classe = 'ExemploData'): string
{
    return <<<PHP
    <?php
    namespace Fixture\\Data;
    use Spatie\\LaravelData\\Data;
    final class {$classe} extends Data
    {
        public function __construct(public readonly string \$nome = '') {}
    }
    PHP;
}

/*
 * CT-10 — o Data correto é aprovado, nas DUAS formas válidas.
 *
 * Sem esta partição, uma varredura que reprova tudo passa no conjunto inteiro — e em produção
 * reclama de `extends \Spatie\LaravelData\Data` escrito por extenso e de `readonly` declarado em
 * cada propriedade promovida. Foi o achado I1 da revisão adversarial.
 */
it('[CT-10] aprova o Data correto nas duas formas validas', function (string $conteudo): void {
    $raiz = raizDeFixture(['Data/ExemploData.php' => $conteudo]);

    expect(GuardaDoPadraoDeDto::violacoes($raiz))->toBe([]);
})->with([
    'nome curto, readonly na assinatura'      => [dataCorreto()],
    'nome completo, readonly por propriedade' => [<<<'PHP'
    <?php
    namespace Fixture\Data;
    final class OutroData extends \Spatie\LaravelData\Data
    {
        public function __construct(
            public readonly string $nome = '',
            public readonly ?int $idade = null,
        ) {}
    }
    PHP],
]);

/*
 * CT-11 — propriedade com nome parecido, mas legítima, é aprovada.
 *
 * `tokenDoConvite` é identificador de convite, não credencial. Um guarda que reprove qualquer
 * nome contendo "token" inviabiliza Data legítimo.
 */
it('[CT-11] aprova propriedade legitima com nome parecido', function (): void {
    $raiz = raizDeFixture(['Data/ConviteData.php' => <<<'PHP'
    <?php
    namespace Fixture\Data;
    use Spatie\LaravelData\Data;
    final class ConviteData extends Data
    {
        public function __construct(public readonly string $tokenDoConvite = '') {}
    }
    PHP]);

    expect(GuardaDoPadraoDeDto::violacoes($raiz))->toBe([]);
});

/*
 * CT-04 — sem a herança do pacote reprova, e o motivo é a herança.
 *
 * O `e não cita` é o que mata a mensagem catch-all: um guarda com uma mensagem única listando
 * todos os motivos satisfaz "cita X" em todo cenário e não distingue nada (achado I8).
 */
it('[CT-04] reprova classe sem a heranca do pacote, citando a heranca', function (): void {
    $raiz = raizDeFixture(['Data/SemHerancaData.php' => <<<'PHP'
    <?php
    namespace Fixture\Data;
    final class SemHerancaData
    {
        public function __construct(public readonly string $nome = '') {}
    }
    PHP]);

    $violacoes = GuardaDoPadraoDeDto::violacoes($raiz);

    expect($violacoes)->toHaveCount(1)
        ->and($violacoes[0])->toContain('SemHerancaData')
        ->and($violacoes[0])->toContain('estender')
        ->and($violacoes[0])->not->toContain('final')
        ->and($violacoes[0])->not->toContain('readonly')
        ->and($violacoes[0])->not->toContain('abstract');
});

/*
 * CT-05 — cada defeito de imutabilidade é nomeado pelo SEU motivo.
 */
it('[CT-05] reprova o defeito de imutabilidade pelo motivo certo', function (string $conteudo, string $motivo, string $alheio): void {
    $raiz = raizDeFixture(['Data/DefeituosoData.php' => $conteudo]);

    $violacoes = GuardaDoPadraoDeDto::violacoes($raiz);

    expect($violacoes)->toHaveCount(1)
        ->and($violacoes[0])->toContain($motivo)
        ->and($violacoes[0])->not->toContain($alheio);
})->with([
    'sem final' => [<<<'PHP'
    <?php
    namespace Fixture\Data;
    use Spatie\LaravelData\Data;
    class DefeituosoData extends Data
    {
        public function __construct(public readonly string $nome = '') {}
    }
    PHP, 'final', 'abstract'],
    'propriedade mutavel' => [<<<'PHP'
    <?php
    namespace Fixture\Data;
    use Spatie\LaravelData\Data;
    final class DefeituosoData extends Data
    {
        public string $nome = '';
    }
    PHP, 'readonly', 'final'],
    'abstrata' => [<<<'PHP'
    <?php
    namespace Fixture\Data;
    use Spatie\LaravelData\Data;
    abstract class DefeituosoData extends Data
    {
        public function __construct(public readonly string $nome = '') {}
    }
    PHP, 'abstract', 'readonly'],
]);

/*
 * CT-06 — o sufixo `Data` é reservado.
 *
 * O caso que motivou: num projeto real do mesmo autor, um Eloquent Model chamado
 * `MbaReportStudentData` convivia com os DTO, e quem varre `*Data.php` atrás de DTO encontrava
 * o Model junto.
 */
it('[CT-06] reprova sufixo Data fora do diretorio de DTO', function (): void {
    $raiz = raizDeFixture(['Models/RelatorioData.php' => <<<'PHP'
    <?php
    namespace Fixture\Models;
    use Illuminate\Database\Eloquent\Model;
    final class RelatorioData extends Model
    {
    }
    PHP]);

    $violacoes = GuardaDoPadraoDeDto::violacoes($raiz);

    expect($violacoes)->toHaveCount(1)
        ->and($violacoes[0])->toContain('RelatorioData')
        ->and($violacoes[0])->toContain('reservado');
});

/*
 * CT-07 — credencial em Data reprova, nomeando a propriedade.
 */
it('[CT-07] reprova credencial em Data citando a propriedade', function (string $nome): void {
    $raiz = raizDeFixture(['Data/CredencialData.php' => <<<PHP
    <?php
    namespace Fixture\\Data;
    use Spatie\\LaravelData\\Data;
    final class CredencialData extends Data
    {
        public function __construct(public readonly string \${$nome} = '') {}
    }
    PHP]);

    $violacoes = GuardaDoPadraoDeDto::violacoes($raiz);

    $outros = array_values(array_diff(GuardaDoPadraoDeDto::NOMES_DE_CREDENCIAL, [$nome]));

    expect($violacoes)->toHaveCount(1)
        ->and($violacoes[0])->toContain('$'.$nome)
        ->and($violacoes[0])->toContain('credencial');

    foreach ($outros as $outro) {
        expect($violacoes[0])->not->toContain('$'.$outro);
    }
})->with(['senha', 'password', 'token', 'secret', 'api_key']);

/*
 * CT-08 — Data como propriedade pública de componente reprova.
 *
 * É a superfície do pacote que a wiki inventariou: `LivewireDataSynth::hydrate()` reconstrói a
 * propriedade a partir do payload do NAVEGADOR. É a mesma classe de defeito que custou quatro
 * correções na v0.33.0.
 */
it('[CT-08] reprova Data como propriedade publica de componente', function (): void {
    $raiz = raizDeFixture(['Filament/PaginaDeTeste.php' => <<<'PHP'
    <?php
    namespace Fixture\Filament;
    use Fixture\Data\ExemploData;
    use Livewire\Component;
    class PaginaDeTeste extends Component
    {
        public ExemploData $exemplo;
    }
    PHP]);

    $violacoes = GuardaDoPadraoDeDto::violacoes($raiz);

    expect($violacoes)->toHaveCount(1)
        ->and($violacoes[0])->toContain('PaginaDeTeste')
        ->and($violacoes[0])->toContain('navegador');
});

/*
 * CT-09 — instanciar Data com `new` fora da fábrica reprova.
 *
 * Só o pipeline do pacote roda os casts; `new` os pula em silêncio.
 */
it('[CT-09] reprova new de Data fora da fabrica', function (): void {
    $raiz = raizDeFixture(['Servicos/Servico.php' => <<<'PHP'
    <?php
    namespace Fixture\Servicos;
    use Fixture\Data\ExemploData;
    final class Servico
    {
        public function fazer(): ExemploData
        {
            return new ExemploData('cru');
        }
    }
    PHP]);

    $violacoes = GuardaDoPadraoDeDto::violacoes($raiz);

    expect($violacoes)->toHaveCount(1)
        ->and($violacoes[0])->toContain('Servico.php')
        ->and($violacoes[0])->toContain('fábrica');
});

/*
 * CT-12 — a varredura percorre o diretório inteiro, não o primeiro arquivo.
 */
it('[CT-12] percorre o diretorio inteiro', function (): void {
    $raiz = raizDeFixture([
        'Data/Um/PrimeiroData.php' => <<<'PHP'
        <?php
        namespace Fixture\Data\Um;
        final class PrimeiroData
        {
        }
        PHP,
        'Data/Dois/SegundoData.php' => <<<'PHP'
        <?php
        namespace Fixture\Data\Dois;
        final class SegundoData
        {
        }
        PHP,
    ]);

    $violacoes = GuardaDoPadraoDeDto::violacoes($raiz);

    expect($violacoes)->toHaveCount(2)
        ->and(implode(' ', $violacoes))->toContain('PrimeiroData')
        ->and(implode(' ', $violacoes))->toContain('SegundoData');
});

/*
 * CT-13 — as raízes padrão são DADO, verificável.
 *
 * Sem isto, as raízes podem encolher em silêncio para o diretório que o teste entrega, e o guarda
 * deixa de ver produção sem que nada fique vermelho (achado I2/I7).
 */
it('[CT-13] expoe as raizes padrao como dado', function (): void {
    expect(GuardaDoPadraoDeDto::RAIZES_PADRAO)
        ->toBe(['app', 'routes/api.php', 'app/Http/Resources']);
});

/*
 * CT-14 — o kit publicado passa pelo guarda sem reprovação.
 *
 * É o par de CT-13: junto com ele, prova que o guarda roda sobre o código real e fica verde nele.
 * Um guarda que reprova tudo quando chamado sem raiz morre aqui.
 */
it('[CT-14] nao reprova nada no kit publicado', function (): void {
    expect(GuardaDoPadraoDeDto::violacoes())->toBe([]);
});

/*
 * CT-15 — a árvore de produção imitada é percorrida inteira.
 */
it('[CT-15] percorre a arvore de producao imitada', function (): void {
    $raiz = raizDeFixture([
        'Data/RuimData.php' => <<<'PHP'
        <?php
        namespace Fixture\Data;
        final class RuimData
        {
        }
        PHP,
        'Models/RelatorioData.php' => <<<'PHP'
        <?php
        namespace Fixture\Models;
        final class RelatorioData
        {
        }
        PHP,
        'Filament/Pagina.php' => <<<'PHP'
        <?php
        namespace Fixture\Filament;
        use Fixture\Data\RuimData;
        class Pagina
        {
            public RuimData $dados;
        }
        PHP,
    ]);

    $violacoes = GuardaDoPadraoDeDto::violacoes($raiz);

    expect($violacoes)->toHaveCount(3)
        ->and(implode(' ', $violacoes))->toContain('RuimData')
        ->and(implode(' ', $violacoes))->toContain('RelatorioData')
        ->and(implode(' ', $violacoes))->toContain('Pagina');
});

/*
 * CT-20 — hoje o kit não tem superfície de API, e o caso AFIRMA isso.
 *
 * Ele não substitui CT-21: sozinho, prova vacuidade do mundo, não que o guarda funciona. O par
 * dos dois é o que faz RQ-04 valer no dia em que a primeira API nascer.
 */
it('[CT-20] o kit publicado nao tem superficie de api', function (): void {
    expect(file_exists(base_path('routes/api.php')))->toBeFalse()
        ->and(is_dir(app_path('Http/Resources')))->toBeFalse();

    $comJson = collect(File::allFiles(app_path('Http/Controllers')))
        ->filter(fn ($arquivo): bool => str_contains($arquivo->getContents(), 'response()->json('))
        ->map(fn ($arquivo): string => $arquivo->getFilename())
        ->values()
        ->all();

    expect($comJson)->toBe([]);
});

/*
 * CT-21 — cada uma das TRÊS superfícies de API sem Data reprova.
 */
it('[CT-21] reprova cada superficie de api sem Data', function (array $arquivos, string $esperado): void {
    $violacoes = GuardaDoPadraoDeDto::violacoes(raizDeFixture($arquivos));

    expect($violacoes)->toHaveCount(1)
        ->and($violacoes[0])->toContain($esperado);
})->with([
    'controller' => [[
        'Http/Controllers/RelatorioController.php' => <<<'PHP'
        <?php
        namespace Fixture\Http\Controllers;
        final class RelatorioController
        {
            public function index()
            {
                return response()->json(['total' => 1]);
            }
        }
        PHP,
    ], 'RelatorioController.php'],
    'resource' => [[
        'app/Http/Resources/UsuarioResource.php' => <<<'PHP'
        <?php
        namespace Fixture\Http\Resources;
        use Illuminate\Http\Resources\Json\JsonResource;
        final class UsuarioResource extends JsonResource
        {
            public function toArray($request): array
            {
                return ['id' => $this->id];
            }
        }
        PHP,
    ], 'UsuarioResource.php'],
    'rota' => [[
        'routes/api.php' => <<<'PHP'
        <?php
        use Illuminate\Support\Facades\Route;
        Route::get('/usuarios', fn (): array => ['total' => 1]);
        PHP,
    ], 'routes/api.php'],
]);
