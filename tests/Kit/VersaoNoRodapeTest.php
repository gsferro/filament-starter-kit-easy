<?php

use App\Filament\Admin\Pages\ConfiguracoesDoKit;
use App\Settings\ConfiguracoesDoKit as SettingsDoKit;
use Database\Seeders\PapeisSeeder;
use Database\Seeders\ShieldPermissionsSeeder;
use Filament\Facades\Filament;
use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
use Illuminate\Support\Facades\File;
use Livewire\Livewire;
use Spatie\LaravelSettings\Models\SettingsProperty;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\Finder\SplFileInfo;

/**
 * A versão no rodapé dos painéis.
 *
 * **Duas versões, e confundi-las foi o erro que o Adendo 2 do requisito corrigiu:**
 *
 *   `config('app.version')` → a versão do PRODUTO que nasceu do kit. É a que o rodapé mostra.
 *   `config('kit.version')` → a versão do STARTER KIT. Métrica interna, só aparece sob toggle.
 *
 * O caso que vale mais neste arquivo é o do visitante: o render hook `FOOTER` é emitido também
 * pelo layout `simple` (`vendor/filament/filament/resources/views/components/layout/simple.blade.php:61`),
 * que é o das telas de login, registro e recuperação de senha — e versão exata de uma instalação
 * é o mapa de CVEs aplicáveis a ela.
 *
 * Ver ADR-04 de `wikis/specs/feat/estudo-de-pacotes-rodada-2/`.
 */
beforeEach(function (): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);
});

/**
 * As duas chaves são lidas por request (`config()` dentro da blade), então `config()->set()` no
 * caso é o arranjo fiel — não há nada congelado no boot do painel a contornar.
 */
function comVersoes(?string $sistema, bool $exibirKit): void
{
    config()->set('app.version', $sistema);
    config()->set('kit.exibir_versao', $exibirKit);
}

/**
 * O RODAPÉ da página, e não a página inteira.
 *
 * É oráculo, não conveniência. `assertSee` sobre o documento todo fica verde com a versão emitida
 * na barra do topo, no menu lateral ou no corpo da tela — e a regra é sobre o rodapé (M5). O
 * recorte é implementation-neutral e vem do próprio vendor: nos DOIS layouts o hook `FOOTER` é
 * emitido depois do fechamento do `</main>`
 * (`vendor/filament/filament/resources/views/components/layout/index.blade.php:126` e
 * `.../simple.blade.php:61`), então o trecho posterior ao último `</main>` contém o rodapé e não
 * contém nem topbar nem conteúdo.
 *
 * O que ele NÃO fecha está declarado em L5 da wiki: a variante "a versão é emitida no fim do
 * documento" também é posterior ao `</main>`, e fechar o recorte por baixo dependeria do markup do
 * vendor — o mesmo critério pelo qual L2 descartou a asserção sobre `<img>`.
 */
function rodapeDe(string $html): string
{
    $fim = strrpos($html, '</main>');

    return $fim === false ? '' : substr($html, $fim);
}

/*
|--------------------------------------------------------------------------
| A fronteira: visitante nunca vê versão
|--------------------------------------------------------------------------
*/

/**
 * CT-05 — nenhuma tela de autenticação expõe a versão.
 *
 * **A asserção de ausência tem destinatário**, e sem isso o caso seria vácuo: o `phpunit.xml`
 * força `APP_VERSION=""`, então "a tela de login não contém a versão" passaria com o guard
 * removido, com o rodapé removido e com a feature inteira revertida. O `Dado` GRAVA a versão, e
 * CT-06 fecha o par mostrando que o mesmo valor aparece para quem autenticou.
 *
 * Sozinho ele ainda não mata M7 ("não existe guard nenhum"): a ausência também seria verdadeira se
 * o rodapé simplesmente não alcançasse o layout das telas de autenticação. Quem fecha esse buraco
 * é CT-37, logo abaixo.
 *
 * As cinco partições foram conferidas contra `php artisan route:list`, não escritas de memória.
 */
it('[CT-05] nao mostra versao nenhuma para quem nao entrou', function (string $rota): void {
    comVersoes('9.9.9-secreta', exibirKit: true);

    /*
     * A chave da página única governa a partição, nos dois sentidos: desligada, `/login`
     * redireciona para o login do painel default; ligada, são os logins dos painéis que passam a
     * redirecionar para `/login`. Nos dois casos a asserção mediria um redirect em vez do guard,
     * então cada linha roda no estado em que a tela dela existe.
     */
    ligarLoginUnificado($rota === '/login');

    $this->get($rota)
        ->assertOk()
        ->assertDontSee('9.9.9-secreta')
        // O NÚMERO, não o rótulo: RQ-20 exige que o rótulo exista, não que seja `kit `.
        // Casar a string acoplaria este caso a uma redação que o requisito deixou livre.
        ->assertDontSee((string) config('kit.version'));
})->with([
    'login do admin'          => ['/admin/login'],
    'login do app'            => ['/app/login'],
    'login do infra'          => ['/infra/login'],
    'página única de login'   => ['/login'],
    'recuperação de senha'    => ['/admin/password-reset/request'],
])->group('kit');

/**
 * CT-06 — o par positivo do guard.
 *
 * O mesmo valor, o mesmo arranjo: o visitante não encontra e a administradora encontra. É ele que
 * mata M8 (o guard invertido, escondendo de todo mundo) — com o guard invertido, CT-05 continua
 * verde e este fica vermelho.
 */
it('[CT-06] mostra ao autenticado a mesma versao que o visitante nao ve', function (): void {
    comVersoes('2.4.0', exibirKit: false);

    $this->get('/admin/login')->assertOk()->assertDontSee('2.4.0');

    $resposta = $this->actingAs(usuarioDoKit('admin'))->get('/admin')->assertOk();

    expect(rodapeDe((string) $resposta->getContent()))->toContain('v2.4.0');
})->group('kit');

/**
 * CT-37 — o ponto de extensão do rodapé é renderizado TAMBÉM na tela de login.
 *
 * É o cenário que dá destinatário a CT-05, e sem ele a ausência da versão na tela de login é
 * verdadeira por dois motivos indistinguíveis: *o guard escondeu* ou *o rodapé nunca chega àquele
 * layout*. No segundo caso M7 — "não existe guard nenhum" — atravessa o conjunto inteiro, e o
 * cenário que mais importa em toda a área vira decoração.
 *
 * O marcador é neutro e registrado pelo próprio caso: é o `Http::fake()` desta regra, um mundo em
 * que o efeito PODERIA acontecer. O fato que ele trava está medido no vendor instalado —
 * `layout/simple.blade.php:61` emite o mesmo `FOOTER` que `layout/index.blade.php:126`. Se um
 * upgrade do Filament deixar de emitir, este caso fica vermelho e alguém descobre ANTES de o guard
 * virar código morto.
 *
 * L6 declara o que ele não fecha: para registrar o marcador é preciso NOMEAR o ponto de extensão,
 * e ponto de extensão é mecanismo. Se a implementação usar outro, M53 sobrevive.
 */
it('[CT-37] renderiza o ponto de extensao do rodape tambem na tela de login', function (): void {
    FilamentView::registerRenderHook(
        PanelsRenderHook::FOOTER,
        fn (): string => '<span data-marcador-do-rodape></span>',
    );

    $this->get('/admin/login')
        ->assertOk()
        ->assertSee('data-marcador-do-rodape', escape: false);
})->group('kit');

/*
|--------------------------------------------------------------------------
| A tabela de decisão das duas chaves
|--------------------------------------------------------------------------
*/

/**
 * Quatro combinações, produto cartesiano fechado das duas chaves.
 *
 * O par que mata o mutante mais provável — "mostrar a do kit sempre" — é
 * `só sistema` × `as duas`: o primeiro exige a AUSÊNCIA da versão do kit, o segundo a presença.
 */
it('[CT-01] compoe o rodape conforme as duas chaves', function (?string $sistema, bool $exibirKit, bool $esperaSistema, bool $esperaKit): void {
    comVersoes($sistema, $exibirKit);

    /*
     * A versão do kit é lida AQUI, e não no dataset: dataset é resolvido fora do ciclo de vida da
     * aplicação, e `config()` ali estoura `Target class [config] does not exist`.
     *
     * E é o NÚMERO, não o rótulo. A primeira redação casava `'kit '.config('kit.version')`, o que
     * acoplava este caso a uma redação que RQ-20 deixou livre — renomear o rótulo para `starter `
     * deixaria CT-46/CT-47 verdes e este vermelho, que é o oposto do que deveria acontecer. Quem
     * assere a EXISTÊNCIA do rótulo é CT-46; aqui o oráculo é a presença da versão.
     */
    $versaoDoKit = (string) config('kit.version');

    $resposta = $this->actingAs(usuarioDoKit('admin'))->get('/admin');

    $resposta->assertOk();

    $rodape = rodapeDe((string) $resposta->getContent());

    $esperaSistema
        ? expect($rodape)->toContain('v'.$sistema)
        : expect($rodape)->not->toContain('v'.($sistema ?? '—'));

    $esperaKit
        ? expect($rodape)->toContain($versaoDoKit)
        : expect($rodape)->not->toContain($versaoDoKit);

    /*
     * O INVARIANTE das duas leituras da premissa nº 2: nada no rodapé apresenta a versão do kit
     * como se fosse a do produto. A pergunta nº 9 da wiki dizia que ele só é asserível casando
     * rótulo, e que a pergunta nº 8 proibia casar rótulo — o impasse se desfaz porque o rótulo
     * `kit ` **existe** na implementação e é o que torna a distinção observável. Aqui ele é usado
     * como o discriminante que é: a versão do kit, quando aparece, aparece SEMPRE atrás de `kit `,
     * nunca atrás do `v` que marca a do produto.
     */
    expect($rodape)->not->toContain('v'.config('kit.version'));
})->with([
    'nenhuma das duas' => [null, false, false, false],
    'só o sistema'     => ['2.4.1', false, true, false],
    /*
     * A linha que a wiki marcava `@premissa` (pergunta nº 2), e a premissa foi NEGADA pelo código
     * entregue: com a versão do sistema vazia e o toggle ligado, o rodapé mostra a do kit, com o
     * rótulo que a distingue. O `04` adotara a direção fechada ("não renderiza nada") e previa
     * exatamente esta inversão no `Se negado`. Registrado em `## Reconciliação` do `04`, para o
     * solicitante decidir; o invariante afirmado no corpo do caso vale nas duas leituras.
     */
    'só o kit'         => [null, true, false, true],
    'as duas'          => ['2.4.1', true, true, true],
])->group('kit');

/**
 * Versão vazia e toggle desligado: o rodapé não renderiza NADA.
 *
 * Caso separado da tabela acima de propósito: ali a asserção é sobre texto, aqui é sobre o
 * elemento. Um mutante que renderizasse `<div class="kit-versao"></div>` vazio passaria na
 * tabela e falha aqui — e um rodapé vazio ocupando espaço é defeito visual, não detalhe.
 */
it('[CT-01] nao renderiza o elemento quando nao ha o que mostrar', function (): void {
    comVersoes(null, exibirKit: false);

    $this->actingAs(usuarioDoKit('admin'))
        ->get('/admin')
        ->assertOk()
        ->assertDontSee('kit-versao');
})->group('kit');

/**
 * CT-02 — a versão do rodapé é a do PRODUTO, não a do kit.
 *
 * É o erro que o Adendo 2 do requisito corrigiu, e o mutante M1 é literalmente a primeira redação
 * desta feature: `config('kit.version')` no lugar de `config('app.version')`. Os dois valores são
 * diferentes e ambos não vazios de propósito — um rodapé que lesse a chave errada ficaria verde
 * com qualquer valor único.
 *
 * A segunda asserção é sobre a PÁGINA INTEIRA, e aqui isso é correto: com o toggle desligado, a
 * versão do kit não pode aparecer em lugar nenhum do documento, e o recorte do rodapé seria uma
 * asserção mais fraca do que a regra pede.
 */
it('[CT-02] mostra a versao do sistema e nao a do kit', function (): void {
    comVersoes('2.4.0', exibirKit: false);

    config()->set('kit.version', '0.34.2');

    $resposta = $this->actingAs(usuarioDoKit('admin'))->get('/admin')->assertOk();

    expect(rodapeDe((string) $resposta->getContent()))->toContain('v2.4.0');

    $resposta->assertDontSee('0.34.2');
})->group('kit');

/*
|--------------------------------------------------------------------------
| Vale nos três painéis
|--------------------------------------------------------------------------
*/

/**
 * O hook é registrado **sem `scopes:`** em `ConfiguraFilamentGlobal`, o que o faz valer em
 * qualquer painel. Este caso é o que fica vermelho se alguém acrescentar um escopo.
 */
it('[CT-03] mostra a versao do sistema nos tres paineis', function (string $painel, string $papel): void {
    comVersoes('3.1.4', exibirKit: false);

    $resposta = $this->actingAs(usuarioDoKit($papel, "{$papel}@example.com"))
        ->get("/{$painel}")
        ->assertOk();

    expect(rodapeDe((string) $resposta->getContent()))->toContain('v3.1.4');
})->with([
    /*
     * O `app` NÃO pode faltar, e a primeira redação deste caso o omitiu. Ele é o painel com
     * tenancy, o mais provável de resolver o hook de forma diferente — sem ele, acrescentar
     * `scopes: ['admin', 'infra']` ao registro do hook deixaria o caso VERDE e sumiria com o
     * rodapé do /app em silêncio, que é exatamente o mutante que ele diz matar. Achado pelo
     * `/code-review` do diff.
     */
    'app'   => ['app', 'panel_user'],
    'admin' => ['admin', 'admin'],
    'infra' => ['infra', 'infra'],
])->group('kit');

/**
 * CT-04 — a versão gravada com marcação HTML chega ESCAPADA ao rodapé.
 *
 * `versao_do_sistema` é texto livre digitado numa tela e impresso em TODA página dos três painéis:
 * é a superfície de injeção mais larga desta entrega. O mutante M3 é a linha `{!! !!}` ou a
 * concatenação sem escape, e a carga é a que distingue "escapa" de "não escapa" — um valor como
 * `v1.0` não distingue nada.
 *
 * As duas metades importam: o documento não pode conter a tag executável, e o texto visível tem de
 * continuar sendo o que a pessoa digitou (descartar o valor também deixaria a primeira asserção
 * verde, e é errado por outro motivo).
 */
it('[CT-04] escapa a marcacao html da versao gravada', function (): void {
    comVersoes('<script>alert(1)</script>', exibirKit: false);

    $resposta = $this->actingAs(usuarioDoKit('admin'))->get('/admin')->assertOk();

    $rodape = rodapeDe((string) $resposta->getContent());

    expect($rodape)->not->toContain('<script>alert(1)</script>')
        ->and($rodape)->toContain('&lt;script&gt;alert(1)&lt;/script&gt;');
})->group('kit');

/*
|--------------------------------------------------------------------------
| A fonte é a config, não o git
|--------------------------------------------------------------------------
*/

/**
 * A decisão de NÃO ler `.git` em tempo de execução está na ADR-04, e o motivo é operacional:
 * imagem de produção costuma não ter `.git`.
 *
 * Este caso a enforça por varredura: nenhum arquivo do kit pode resolver versão por `shell_exec`,
 * `exec` ou leitura de `.git`. Sem ele, a decisão seria só prosa na ADR.
 */
it('[CT-13] nao le o git para resolver a versao', function (): void {
    $blade = (string) file_get_contents(resource_path('views/filament/versao-do-kit.blade.php'));

    /*
     * O comentário da blade EXPLICA a decisão, e portanto cita `.git`. Varrer o arquivo inteiro
     * reprovaria a documentação da própria decisão — o recorte é o código, depois do `--}}`.
     */
    $codigo = substr($blade, (int) strpos($blade, '--}}'));

    expect($codigo)
        ->not->toContain('shell_exec')
        ->not->toContain('exec(')
        ->not->toContain('.git')
        ->toContain("config('app.version')")
        ->toContain("config('kit.version')");

    /*
     * E a varredura larga que o cenário pede: `app/`, `config/` e `resources/views/` inteiros, sem
     * os comentários. Sem ela, a blade é a única coisa provada limpa e um resolvedor que lesse
     * `.git` num provider venceria a tela em silêncio (M18) — duas fontes de verdade divergentes,
     * que é o defeito de verdade.
     *
     * O filtro de comentário é obrigatório e está medido três vezes em `.ai/rules/testes.md`: os
     * arquivos bem comentados do kit CITAM o que proíbem, e é lá que está escrito o porquê. Aqui
     * isso é certo — `config/app.php`, a blade e o provider explicam a decisão citando `.git`.
     */
    $semComentario = static fn (string $arquivo): string => (string) preg_replace(
        ['~/\*.*?\*/~s', '~\{\{--.*?--\}\}~s', '~//[^\n]*~', '~^\s*#.*$~m'],
        '',
        (string) file_get_contents($arquivo),
    );

    $arquivos = collect([app_path(), config_path(), resource_path('views')])
        ->flatMap(fn (string $diretorio): array => File::allFiles($diretorio))
        ->filter(fn (SplFileInfo $arquivo): bool => $arquivo->getExtension() === 'php')
        ->values();

    expect($arquivos)->not->toBeEmpty('a varredura não encontrou arquivo nenhum: seria vácua');

    foreach ($arquivos as $arquivo) {
        $codigoDoArquivo = $semComentario($arquivo->getRealPath());

        foreach (['packed-refs', 'git describe', 'shell_exec(', 'proc_open('] as $proibido) {
            expect($codigoDoArquivo)->not->toContain(
                $proibido,
                "{$arquivo->getRelativePathname()} resolve versão por processo externo ou pelo repositório git",
            );
        }
    }
})->group('kit');

/*
|--------------------------------------------------------------------------
| R3 — a versão do kit é opcional e nasce desligada
|--------------------------------------------------------------------------
*/

/**
 * CT-07 — o valor de fábrica é lido do ARQUIVO, com a variável de ambiente ausente.
 *
 * `kitConfigCom($chave, null)` e não `config('kit.exibir_versao')`: o `phpunit.xml` fixa chaves
 * desta feature, e um caso que lesse a config do processo estaria medindo o `phpunit.xml` — o
 * default errado no arquivo sobreviveria sem nada ficar vermelho.
 *
 * O mutante é M10, e ele não é hipotético: `true` no arquivo faz toda instalação do kit anunciar
 * de qual starter ela nasceu, contra a decisão explícita do Adendo 2, que é assinada pelo
 * solicitante e portanto requisito.
 */
it('[CT-07] nasce com a exibicao da versao do kit desligada no arquivo de configuracao', function (): void {
    expect(kitConfigCom('KIT_EXIBIR_VERSAO', null)['exibir_versao'])->toBeFalse();
})->group('kit');

/**
 * CT-08 — ligada pela tela, a versão do kit ACOMPANHA a do sistema.
 *
 * Dois mutantes numa asserção só, e é por isso que o `Então` exige as DUAS versões: M12 (o toggle
 * grava e não governa, porque falta a linha no mapa de configuração) morre na presença da versão
 * do kit; M13 (ligar o kit SUBSTITUI a do sistema) morre na presença simultânea da do sistema.
 *
 * O `Dado` grava pela tela e alinha como o boot alinharia — o caminho inteiro, propriedade → mapa
 * → config → blade, e não um `config()->set()` que provaria só que `config()` devolve o que
 * `config()` guardou.
 */
it('[CT-08] faz a versao do kit acompanhar a do sistema quando ligada pela tela', function (): void {
    config()->set('kit.version', '0.34.2');

    gravarConfiguracao('versao_do_sistema', '2.4.0');
    gravarConfiguracao('exibir_versao_do_kit', false);
    alinharConfiguracoesDoKit();

    $antes = $this->actingAs(usuarioDoKit('admin'))->get('/admin')->assertOk();

    expect(rodapeDe((string) $antes->getContent()))->not->toContain('0.34.2');

    gravarConfiguracao('exibir_versao_do_kit', true);
    alinharConfiguracoesDoKit();

    $depois = $this->get('/admin')->assertOk();

    /*
     * As duas presentes, e a do kit rotulada — sem casar o TEXTO do rótulo.
     *
     * A redação anterior casava `'kit 0.34.2'`, e isso acoplava o caso a uma string que RQ-20
     * deixou livre: renomear o rótulo para `starter ` — permitido pelo requisito — deixava
     * CT-46/CT-47 verdes e este vermelho, o oposto do que deveria acontecer. Achado do ciclo 2 do
     * quality gate, e ele sobreviveu à primeira correção porque eu só desacoplei dois outros
     * pontos e não varri o arquivo.
     *
     * Quem assere a EXISTÊNCIA do rótulo é CT-46, pelo critério de palavra. Aqui o oráculo é a
     * presença simultânea das duas versões, que é o que o nome do caso promete.
     */
    expect(rodapeDe((string) $depois->getContent()))
        ->toContain('v2.4.0')
        ->toContain('0.34.2')
        ->toMatch('/\p{L}{2,}\s*0\.34\.2/u');
})->group('kit');

/**
 * CT-43 — com o toggle desligado, a linha de comando continua sendo a saída.
 *
 * O Adendo 2 escreve literalmente que "quem quiser vê-la usa `php artisan kit:info` ou liga o
 * toggle" — e com o toggle nascendo desligado, o comando é o ÚNICO caminho restante. Isso torna a
 * cláusula requisito, não detalhe do plano, e é o que M54 mata: o comando deixar de mostrar a
 * versão do kit fecharia a última porta sem nada ficar vermelho.
 *
 * O `Dado` fixa o toggle desligado de propósito: o caso precisa provar que a saída do comando NÃO
 * depende dele.
 *
 * Fica aqui, e não em `tests/Kit/KitInfoTest.php` como o índice do `04` sugere, porque aquele
 * arquivo carrega os `[CT-nn]` de outra wiki (`kit-info`, que vai até CT-17) e um `[CT-43]` no
 * meio deles seria ambíguo nos dois sentidos. Registrado em `## Reconciliação`.
 */
/*
 * CT-09 ("o vocabulário do .env é respeitado na exibição da versão do kit") **não tem caso**, e o
 * motivo é achado, não preguiça: **M11 está vivo na árvore**.
 *
 * `config/kit.php:250` lê a chave com `(bool) env('KIT_EXIBIR_VERSAO', false)`, e a medição é
 * direta — `KIT_EXIBIR_VERSAO=off`, `=no` e qualquer texto ilegível resolvem para **true**, isto
 * é, LIGAM a exibição da versão do kit em toda instalação que usar essas palavras. O contrato de
 * booleano do kit (`App\Support\BooleanoDoEnv`, que `tests/Kit/BooleanoDoEnvTest.php` trava) as
 * resolveria para o default, que é `false`.
 *
 * O comentário do próprio arquivo justifica o `(bool)` dizendo que "o default é `false`, que é
 * justamente o valor para o qual o defeito da chave presente-e-vazia converge" — e isso é verdade
 * para a chave VAZIA, que é o defeito que `BooleanoDoEnv` nasceu para corrigir. Não é verdade para
 * o vocabulário `off`/`no`, que é outra partição, e é a que CT-09 mede.
 *
 * Escrever o caso aqui deixaria a suíte vermelha por um defeito de produção, e esta reconciliação
 * não tem mandato para mexer em `config/`. Fica registrado em `04-casos-de-teste.md` ›
 * `## Reconciliação`, com destino **implementação**. A chave irmã (`alerta_alteracoes_nao_salvas`)
 * usa o contrato certo e tem o cenário equivalente rodando: CT-17.
 */

it('[CT-43] mostra a versao do kit no comando de informacoes mesmo com o toggle desligado', function (): void {
    config()->set('kit.exibir_versao', false);
    config()->set('kit.version', '0.34.2');

    expect(saidaDoKitInfo())->toContain('0.34.2');
})->group('kit');

/*
|--------------------------------------------------------------------------
| R4 — a versão do sistema vem da tela, semeada pelo ambiente
|--------------------------------------------------------------------------
*/

/**
 * CT-10 — o valor gravado na tela é o valor exibido, sem alteração silenciosa.
 *
 * O gate de tela de escrita (`.ai/rules/testes.md`, *"uma tela aberta não é uma tela que grava"*):
 * o caso preenche e SALVA pelo componente, e só depois visita o painel para ler o rodapé. A linha
 * "2.4.0" é a que mata M14 — a propriedade sem linha no `mapaDeConfiguracao()`, que grava e não
 * governa nada.
 *
 * A partição do campo inteira numa tabela só: SemVer, data, um caractere, espaços nas bordas e a
 * limpeza do campo. A última é a que o `phpunit.xml` esconderia se estivesse sozinha — ver CT-38.
 *
 * A linha "espaços nas bordas" espera o valor COM os espaços, e isso é correção do `04`: a regra
 * se chama *sem alteração silenciosa*, e aparar as bordas é exatamente uma alteração silenciosa.
 * Registrado em `## Reconciliação`.
 */
it('[CT-10] exibe no rodape exatamente o que foi gravado na tela', function (string $digitado, ?string $gravado, ?string $exibido): void {
    $this->actingAs(usuarioDoKit('admin'));

    Filament::setCurrentPanel('admin');

    Livewire::test(ConfiguracoesDoKit::class)
        ->fillForm(['versao_do_sistema' => $digitado])
        ->call('save')
        ->assertHasNoFormErrors();

    expect(configuracaoGravada('versao_do_sistema'))->toBe($gravado);

    alinharConfiguracoesDoKit();

    $rodape = rodapeDe((string) $this->get('/admin')->assertOk()->getContent());

    $exibido === null
        ? expect($rodape)->not->toContain('kit-versao')
        : expect($rodape)->toContain('v'.e($exibido));
})->with([
    'SemVer'             => ['2.4.0', '2.4.0', '2.4.0'],
    'data'               => ['2026-09-18', '2026-09-18', '2026-09-18'],
    'um caractere'       => ['1', '1', '1'],
    'espaços nas bordas' => [' 2.4.0 ', ' 2.4.0 ', ' 2.4.0 '],
    /*
     * Campo esvaziado grava NULO, e não string vazia — é a distinção que o `04` pede no eixo
     * ausente ≠ nulo ≠ vazio, e a que faz `filled()` na blade apagar o rodapé.
     */
    'limpeza do campo'   => ['', null, null],
])->group('kit');

/**
 * CT-38 — limpar o campo apaga o rodapé MESMO com a versão do ambiente preenchida.
 *
 * É o cenário que o `phpunit.xml` esconderia: a suíte força `APP_VERSION=""`, então a linha
 * "(vazio)" de CT-10 fica verde com um `?: env('APP_VERSION')` por trás — não há o que devolver.
 * Fixando a versão do ambiente em "9.9.9" no `Dado`, o fallback silencioso (M17) aparece.
 *
 * O comportamento que ele fixa é o do kit inteiro, não uma exceção desta propriedade:
 * `aplicarNaConfig()` grava `$novo[$chave] = $this->{$propriedade}` sem guarda de nulo, e há caso
 * próprio fixando isso em `ConfiguracoesDoKitTest`. A consequência prática está documentada: num
 * projeto já instalado com o campo em branco, escrever `APP_VERSION=2.4.1` no `.env` **não** muda
 * o rodapé.
 */
it('[CT-38] apaga o rodape ao limpar o campo, mesmo com a versao do ambiente preenchida', function (): void {
    config()->set('app.version', '9.9.9');

    gravarConfiguracao('versao_do_sistema', '2.4.0');
    alinharConfiguracoesDoKit();

    expect(config('app.version'))->toBe('2.4.0');

    gravarConfiguracao('versao_do_sistema', null);
    alinharConfiguracoesDoKit();

    $rodape = rodapeDe((string) $this->actingAs(usuarioDoKit('admin'))->get('/admin')->assertOk()->getContent());

    expect(config('app.version'))->toBeNull()
        ->and($rodape)->not->toContain('2.4.0')
        ->and($rodape)->not->toContain('9.9.9');
})->group('kit');

/**
 * CT-44 — valor longo é gravado inteiro ou recusado, nunca truncado em silêncio.
 *
 * `@premissa` (pergunta nº 3): o requisito diz "texto livre" e não decide se existe limite. O
 * `Então` é disjuntivo de propósito, e o invariante que vale nas duas leituras é o que importa —
 * truncar é o único dos três resultados que MENTE para quem digitou (M16).
 *
 * O `Dado` fixa a versão anterior em "1.0.0" porque sem ela o ramo da recusa deixaria a
 * propriedade em `""`, que **é** prefixo do que foi digitado, e o caso ficaria vermelho contra uma
 * implementação correta.
 */
it('[CT-44] grava a versao longa inteira ou recusa, nunca truncada', function (): void {
    gravarConfiguracao('versao_do_sistema', '1.0.0');

    $this->actingAs(usuarioDoKit('admin'));

    Filament::setCurrentPanel('admin');

    $longa = str_repeat('a', 1024);

    $tela = Livewire::test(ConfiguracoesDoKit::class)
        ->fillForm(['versao_do_sistema' => $longa])
        ->call('save');

    $gravada = configuracaoGravada('versao_do_sistema');

    $gravada === '1.0.0'
        ? $tela->assertHasFormErrors(['versao_do_sistema'])
        : expect($gravada)->toBe($longa);

    expect($gravada === $longa || $gravada === '1.0.0')->toBeTrue(
        'a versão gravada não é nem a digitada inteira nem a anterior: foi truncada em silêncio',
    );
})->group('kit');

/**
 * CT-11 — quem não tem a permissão de configuração não abre a tela.
 *
 * A persona é a única que discrimina: um `panel_user` não alcança o `/admin` de qualquer jeito, e
 * o 403 viria da fronteira de painel — uma página **sem autorização nenhuma** passaria no caso.
 * Aqui é um administrador que PERDEU a permission, e por isso o 403 só pode vir da tela.
 *
 * A segunda metade é a saída do estado de erro, que o checklist de taxonomia exige e que a versão
 * anterior deste cenário afirmava só na prosa: recusar a tela não pode recusar o painel.
 *
 * O 403 em si também é afirmado, por outras personas, em
 * `tests/Kit/ConfiguracoesDoKitTelaTest.php` (da wiki `settings-do-kit`); o que este caso
 * acrescenta é o par com a segunda metade.
 */
it('[CT-11] recusa a tela de configuracoes a quem perdeu a permissao, sem recusar o painel', function (): void {
    gravarConfiguracao('versao_do_sistema', '2.4.0');

    $admin = usuarioDoKit('admin');

    Role::findByName('admin')->revokePermissionTo('View:ConfiguracoesDoKit');
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $this->actingAs($admin);

    $this->get('/admin/configuracoes-da-aplicacao')->assertForbidden();

    $this->get('/admin')->assertOk();
    $this->get('/admin/users')->assertOk();
})->group('kit');

/**
 * CT-41 — quem não tem a permissão não grava, nem chamando o salvamento.
 *
 * O gate de CAMADA da regra: a autorização que vive só no `mount()` (ou na visibilidade do campo)
 * deixa o 403 de CT-11 verde e aceita a gravação de qualquer persona (M19). CT-11 mede a tela,
 * este mede a AÇÃO — e a pós-condição no banco é o que separa os dois.
 *
 * **Cobertura parcial declarada (L7 / pergunta nº 10 da wiki)**: a tela tem uma barreira só —
 * `canEdit()` devolve `canAccess()` (`app/Filament/Admin/Pages/ConfiguracoesDoKit.php:109`) —,
 * então o `mount` recusa antes de o `save` existir e a variante *"só no mount"* continua
 * indistinguível. O caso é honesto sobre isso: o que ele prova é que nada foi gravado, que é a
 * proposição verificável hoje.
 */
it('[CT-41] nao grava a versao do sistema para quem perdeu a permissao', function (): void {
    gravarConfiguracao('versao_do_sistema', '2.4.0');

    $admin = usuarioDoKit('admin');

    Role::findByName('admin')->revokePermissionTo('View:ConfiguracoesDoKit');
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $this->actingAs($admin);

    Filament::setCurrentPanel('admin');

    /*
     * `->call('save')` encadeado não chega a existir: o `mount()` aborta e o Livewire devolve um
     * snapshot inválido para a chamada seguinte. Isso É o achado — a barreira única recusa antes
     * do salvamento —, e o caso o afirma nas duas metades que sobram: a recusa do componente e a
     * barreira de gravação da própria tela respondendo `false`, que é onde o `save` consultaria.
     */
    Livewire::test(ConfiguracoesDoKit::class)->assertForbidden();

    expect((new ConfiguracoesDoKit)->canEdit())->toBeFalse()
        ->and(configuracaoGravada('versao_do_sistema'))->toBe('2.4.0');
})->group('kit');

/**
 * CT-12 — sem linha no banco, a versão do ambiente continua valendo.
 *
 * A partição *ausente*, que não é a mesma coisa que *vazia* (CT-38) nem que *nula*: a propriedade
 * não tem linha na tabela. O mutante M15 é o alinhamento que sobrepõe a config com `null` quando a
 * propriedade não existe, apagando o `APP_VERSION` de quem nunca abriu a tela — isto é, de toda
 * instalação recém-migrada.
 *
 * O `Então` fecha no RODAPÉ e não em `config()`, e isso é M55: o alinhamento pode acontecer
 * certinho e o rodapé continuar lendo outra fonte, sem ninguém perceber.
 */
it('[CT-12] mantem a versao do ambiente quando a propriedade nao tem linha no banco', function (): void {
    config()->set('app.version', '9.9.9');

    SettingsProperty::query()
        ->where('group', 'kit')
        ->where('name', 'versao_do_sistema')
        ->delete();

    app()->forgetInstance(SettingsDoKit::class);

    alinharConfiguracoesDoKit();

    expect(config('app.version'))->toBe('9.9.9');

    $rodape = rodapeDe((string) $this->actingAs(usuarioDoKit('admin'))->get('/admin')->assertOk()->getContent());

    expect($rodape)->toContain('v9.9.9');
})->group('kit');

/**
 * [CT-09] o vocabulário do `.env` desliga a exibição da versão do kit.
 *
 * ## O defeito que este caso expõe
 *
 * `config/kit.php` usava `(bool) env('KIT_EXIBIR_VERSAO', false)`, e o comentário do arquivo
 * justificava o `(bool)` dizendo que, com default `false`, o defeito da chave presente-e-vazia
 * converge — o que é verdade e **responde a pergunta errada**. A partição que importa aqui não é
 * "vazia × ausente", é o **vocabulário**: `off`, `no`, `nao` e qualquer texto ilegível são formas
 * correntes de escrever "não" num `.env`, e `(bool) 'off'` é `true` em PHP.
 *
 * Medido antes da correção: `KIT_EXIBIR_VERSAO=off` **ligava** a exibição da versão do kit. A
 * chave irmã (`KIT_ALERTA_ALTERACOES_NAO_SALVAS`) já usava `BooleanoDoEnv` e não tinha o problema
 * — era a única das duas com a guarda certa, e a assimetria passou despercebida porque as duas
 * têm defaults opostos.
 *
 * Achado pela reconciliação do step 7 (divergência D2, mutante M11). O CT ficou deliberadamente
 * **sem teste** enquanto o defeito existia, em vez de ser escrito já verde contra o comportamento
 * errado — que é a inversão que a `feature-test-design` proíbe.
 *
 * ## Por que a asserção é sobre a config, e não sobre o rodapé
 *
 * O rodapé já tem cobertura própria em CT-01 e CT-07. O defeito mora na **leitura da env**, e um
 * caso que passasse pelo HTTP mediria a composição inteira — ficaria verde se alguém "consertasse"
 * o rodapé para ignorar a chave, que é outro defeito.
 */
it('[CT-09] desliga a exibicao da versao do kit em todo vocabulario de negativa do env', function (?string $bruto): void {
    expect(kitConfigCom('KIT_EXIBIR_VERSAO', $bruto)['exibir_versao'])->toBeFalse(
        'O valor `'.($bruto ?? '(ausente)').'` no .env precisa DESLIGAR a exibição da versão do kit.',
    );
})->with([
    'false'           => ['false'],
    'FALSE maiúsculo' => ['FALSE'],
    'off'             => ['off'],
    'no'              => ['no'],
    'zero'            => ['0'],
    'vazia'           => [''],
    'texto ilegível'  => ['talvez'],
    'ausente'         => [null],
])->group('kit');

/**
 * O par positivo: o vocabulário de afirmativa continua ligando.
 *
 * Sem ele, o caso acima ficaria verde numa implementação que devolvesse `false` sempre — e aí a
 * feature estaria desligada para todo mundo, o que também é defeito.
 */
it('[CT-09] liga a exibicao da versao do kit no vocabulario de afirmativa', function (string $bruto): void {
    expect(kitConfigCom('KIT_EXIBIR_VERSAO', $bruto)['exibir_versao'])->toBeTrue();
})->with(['true' => ['true'], 'TRUE maiúsculo' => ['TRUE'], 'on' => ['on'], 'um' => ['1'], 'yes' => ['yes']])->group('kit');

/*
|--------------------------------------------------------------------------
| RQ-20 (Adendo 3) — a versão do kit é distinguível da do sistema
|--------------------------------------------------------------------------
*/

/**
 * [CT-46] as duas versões aparecem distinguíveis uma da outra.
 *
 * Até o Adendo 3 isto era **prosa**. A premissa nº 2 do `00` afirmava *"nada no rodapé apresenta a
 * versão do kit como se fosse a do produto"*, e a pergunta nº 8 declarava que nenhum `Então` casa
 * rótulo — o invariante não tinha como ser verificado. A rodada 2 da revisão adversarial registrou
 * a contradição e escreveu *"não há terceira saída"*; a saída foi o solicitante transformar o
 * rótulo em requisito (RQ-20).
 *
 * ## O que o caso NÃO assere, de propósito
 *
 * Ele não fixa a palavra `kit`. O Adendo 3 diz que o texto do rótulo não é requisito — o que é
 * exigido é que exista distinção legível. Casar a string faria o teste escolher a redação da
 * interface, que é o erro que a pergunta nº 8 evita. O oráculo é: a versão do kit vem acompanhada
 * de algo que a versão do sistema **não** tem.
 */
it('[CT-46] distingue a versao do kit da versao do sistema no rodape', function (): void {
    comVersoes('2.4.1', exibirKit: true);

    $html = (string) $this->actingAs(usuarioDoKit('admin'))->get('/admin')->assertOk()->getContent();

    preg_match('~<div class="kit-versao">(.*?)</div>~s', $html, $m);
    $rodape = trim($m[1] ?? '');

    expect($rodape)->not->toBe('', 'o rodapé da versão não renderizou')
        ->and($rodape)->toContain('2.4.1')
        ->and($rodape)->toContain(config('kit.version'));

    /*
     * O trecho ENTRE a versão do sistema e a do kit precisa ter ao menos uma LETRA.
     *
     * A primeira redação deste caso exigia só "não vazio", e isso não matava o mutante que ele diz
     * matar: sem rótulo o rodapé sairia `v2.4.1 · 0.34.2`, o trecho entre as duas seria `·`, e a
     * asserção passaria. Separador não é rótulo — o que distingue é palavra.
     */
    $entreAsDuas = (string) mb_substr(
        $rodape,
        (int) (mb_strpos($rodape, '2.4.1') + mb_strlen('2.4.1')),
        (int) mb_strpos($rodape, (string) config('kit.version')) - (int) (mb_strpos($rodape, '2.4.1') + mb_strlen('2.4.1')),
    );

    /*
     * PALAVRA, não letra solta. A versão do sistema sai como `v2.4.1`, e o `v` é marcador de
     * versão — uma letra. Exigir "ao menos uma letra" não discrimina os dois casos; exigir duas
     * ou mais separa rótulo (`kit`, `starter`, o que for) de marcador (`v`).
     */
    expect($entreAsDuas)->toMatch('/\p{L}{2,}/u', 'a versão do kit não vem acompanhada de rótulo: entre as duas há só pontuação');

    /*
     * E o rótulo NÃO acompanha a versão do sistema — é isso que o torna discriminante.
     *
     * A primeira redação afirmava `toStartWith('v2.4.1')`, e isso era um oráculo **posicional**:
     * media onde a versão está, não que ela esteja sem rótulo. Renomear o rótulo de `kit ` para
     * qualquer outra coisa — que RQ-20 explicitamente permite, porque não fixa o texto — deixaria
     * este caso verde e quebraria outros. O oráculo certo é simétrico ao de cima: antes da versão
     * do sistema não pode haver letra nenhuma.
     */
    $antesDoSistema = (string) mb_substr($rodape, 0, (int) mb_strpos($rodape, '2.4.1'));

    expect($antesDoSistema)->not->toMatch(
        '/\p{L}{2,}/u',
        'a versão do sistema também veio rotulada, e aí o rótulo deixa de discriminar as duas',
    );
})->group('kit');

/**
 * [CT-47] com a versão do sistema vazia, a do kit continua rotulada.
 *
 * É a linha 3 de CT-01 — a que a premissa nº 2 decidia às cegas. Um rodapé que emitisse `0.34.2`
 * sozinho seria lido como a versão do produto, e é exatamente esse o dano que o invariante
 * previne. Mata M65: rotular só quando há duas versões.
 */
it('[CT-47] mantem o rotulo na versao do kit quando a do sistema nao foi informada', function (): void {
    comVersoes(null, exibirKit: true);

    $html = (string) $this->actingAs(usuarioDoKit('admin'))->get('/admin')->assertOk()->getContent();

    preg_match('~<div class="kit-versao">(.*?)</div>~s', $html, $m);
    $rodape = trim($m[1] ?? '');

    /*
     * O oráculo é o MESMO de CT-46 — presença de letra —, e isso não é repetição preguiçosa.
     *
     * A primeira redação usava `not->toBe(kit.version)` mais `not->toStartWith('v')`, e o ciclo 2
     * do quality gate mostrou que `(0.34.2)` passa nas duas sem conter letra nenhuma: a fraqueza
     * que CT-46 tinha acabado de perder havia migrado para o vizinho. Exigir letra fecha os dois
     * pelo mesmo critério, que é o que RQ-20 pede — distinção legível, não uma string fixa.
     */
    expect($rodape)->toContain(config('kit.version'))
        ->and($rodape)->toMatch(
            '/\p{L}{2,}/u',
            'a versão do kit saiu sem rótulo — sozinha no rodapé, seria lida como a do produto',
        );
})->group('kit');
