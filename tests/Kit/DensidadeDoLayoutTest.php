<?php

use App\Filament\Admin\Pages\ConfiguracoesDoKit as ConfiguracoesDoKitTela;
use App\Settings\ConfiguracoesDoKit;
use App\Support\DensidadeDoLayout;
use Database\Seeders\PapeisSeeder;
use Database\Seeders\ShieldPermissionsSeeder;
use Filament\Facades\Filament;
use Filament\Panel;
use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
use Livewire\Livewire;
use Spatie\LaravelSettings\Models\SettingsProperty;

/**
 * O layout compacto: uma declaração de `--spacing` fora de cascade layer, decidida por request.
 *
 * O que estes casos protegem não é a aparência — é a **cadeia**. A feature atravessa cinco elos
 * (propriedade do Settings → linha do `mapaDeConfiguracao()` → linha semeada na migration →
 * `aplicarNaConfig()` → render hook), e **quatro deles falham em silêncio**: o Select da tela
 * continua aparecendo, continua gravando, e a tela simplesmente não muda. É o defeito que
 * `.ai/rules/settings.md` descreve como *"o campo aparece, grava, e não governa nada"*.
 *
 * Por isso nenhum caso aqui pergunta a `config()` o que a própria `config()` acabou de guardar:
 * a gravação é sempre na TABELA `settings`, e a leitura é sempre do outro lado da cadeia —
 * o HTML servido, ou o retorno do render hook registrado.
 *
 * Ver `wikis/specs/feat/layout-compact/layout-compact/`, ADR-03 (por que `--spacing`), ADR-04
 * (por que níveis) e ADR-06 (por que render hook e não `viteTheme()`).
 */

/**
 * O que o `STYLES_BEFORE` devolve neste request, já renderizado.
 *
 * O hook do kit não é o único registrado nessa chave — `configureOrdemDasCascadeLayers()` emite a
 * ordem das layers ali também —, então a função devolve o bloco inteiro e cada caso afirma sobre
 * o que lhe interessa. Ler daqui, e não do `KitServiceProvider`, é o que faz o caso exercitar a
 * registração de verdade: um método que ninguém chamasse no `boot()` devolveria vazio.
 */
function estilosAntesDoFilament(): string
{
    return (string) FilamentView::renderHook(PanelsRenderHook::STYLES_BEFORE);
}

/** Grava o nível na tabela e alinha a config, como o `boot()` faria num request de verdade. */
function gravarDensidade(string $nivel): void
{
    gravarConfiguracao('densidade_do_layout', $nivel);
    alinharConfiguracoesDoKit();
}

/*
|--------------------------------------------------------------------------
| O nível padrão não emite nada
|--------------------------------------------------------------------------
*/

/**
 * [CT-01] O confortável não emite `<style>` — nem vazio.
 *
 * É o caso que protege toda instalação que nunca vai mexer nisto: o HTML dela continua byte a
 * byte o que já era. Uma implementação que emitisse `:root{--spacing:0.25rem}` no confortável
 * passaria em qualquer asserção visual (o valor é o mesmo) e **mentiria na inspeção**, deixando
 * uma declaração inútil em toda página de todo painel.
 *
 * A asserção é sobre `--spacing` e não sobre a string vazia porque o bloco carrega também a
 * ordem das cascade layers, que precisa continuar lá.
 */
it('[CT-01] nao emite declaracao de spacing no nivel confortavel', function (): void {
    gravarDensidade(DensidadeDoLayout::Confortavel->value);

    expect(estilosAntesDoFilament())
        ->not->toContain('--spacing')
        ->toContain('@layer properties, theme, base, components, utilities;');
});

/**
 * [CT-02] E o valor de fábrica É o confortável.
 *
 * Sem este caso, CT-01 continuaria verde numa entrega que nascesse `denso`: o teste provaria que
 * "confortável não emite", enquanto toda instalação nova mudaria de aparência sozinha ao
 * atualizar o kit. `kitConfigCom($chave, null)` e não `config(...)`: com a variável presente no
 * ambiente, o caso mediria o `.env` do processo em vez do default do ARQUIVO.
 */
it('[CT-02] nasce confortavel no arquivo de configuracao', function (): void {
    expect(kitConfigCom('KIT_DENSIDADE_DO_LAYOUT', null)['densidade_do_layout'])
        ->toBe(DensidadeDoLayout::Confortavel->value);
});

/*
|--------------------------------------------------------------------------
| O settings governa de verdade — o valor no banco chega ao HTML
|--------------------------------------------------------------------------
*/

/**
 * [CT-03] O nível gravado na tela chega ao HTML SERVIDO, nos três painéis.
 *
 * Este é o caso que o requisito compra, e ele é deliberadamente o mais caro do arquivo: pede uma
 * resposta HTTP de verdade e procura a declaração no corpo dela. Nenhum elo da cadeia fica de
 * fora — propriedade, mapa, alinhamento no `boot()` do request e render hook.
 *
 * As telas de login e não o dashboard, de propósito: elas são servidas pelo layout do
 * `caresome/filament-auth-designer`, que é o layout que **mais** poderia deixar de emitir o
 * `STYLES_BEFORE` por vestir as páginas de autenticação com blade própria. Se a declaração chega
 * lá, chega em todo o resto — todos os layouts passam pelo `base.blade.php`.
 *
 * O `Dado` grava na TABELA e não em `config()`: setar a config e depois lê-la de volta seria
 * tautologia, com o Settings inteiro fora do caminho.
 */
it('[CT-03] leva o nivel gravado na tela ate o HTML dos tres paineis', function (string $rota, string $nivel, string $esperado): void {
    gravarDensidade($nivel);

    $html = $this->get($rota)->assertOk()->getContent();

    expect($html)->toContain($esperado);
})->with([
    'admin compacto' => ['/admin/login', 'compacto', '<style>:root{--spacing:0.2rem}</style>'],
    'admin denso'    => ['/admin/login', 'denso', '<style>:root{--spacing:0.175rem}</style>'],
    'app compacto'   => ['/app/login', 'compacto', '<style>:root{--spacing:0.2rem}</style>'],
    'infra denso'    => ['/infra/login', 'denso', '<style>:root{--spacing:0.175rem}</style>'],
]);

/**
 * [CT-04] E o confortável gravado não põe declaração nenhuma no HTML servido.
 *
 * O contrapositivo de CT-03. Sem ele, CT-03 ficaria verde numa implementação que emitisse a
 * declaração SEMPRE, com o valor certo — o nível governaria o texto e não a existência, e a
 * promessa do CT-01 ("instalação que não mexeu continua com o HTML que já tinha") seria falsa
 * exatamente onde importa, que é a página servida.
 */
it('[CT-04] nao poe declaracao nenhuma no HTML quando o nivel e confortavel', function (): void {
    gravarDensidade(DensidadeDoLayout::Confortavel->value);

    expect($this->get('/admin/login')->assertOk()->getContent())
        ->not->toContain('--spacing:');
});

/**
 * [CT-05] A declaração fica FORA de cascade layer, e é isso que a faz vencer.
 *
 * O mecanismo inteiro depende disto: `--spacing:.25rem` do Filament mora em
 * `@layer theme{:root,:host{…}}`, e só uma declaração **sem** layer ganha dela sem `!important`
 * e sem depender de ordem de folha. Uma implementação que envolvesse a regra em
 * `@layer utilities{…}` — o reflexo de quem leu a correção da `v0.37.1` — sairia no HTML, teria
 * a aparência de estar certa, e **não mudaria um pixel**: nenhuma asserção de conteúdo, status
 * ou console pegaria.
 *
 * O caso recorta a declaração do kit e afirma sobre ela, em vez de sobre o bloco inteiro, porque
 * o bloco contém a declaração de ordem das layers — que TEM `@layer` e é correta.
 */
it('[CT-05] emite a declaracao fora de cascade layer', function (): void {
    gravarDensidade(DensidadeDoLayout::Compacto->value);

    $estilos = estilosAntesDoFilament();

    $posicao = strpos($estilos, '--spacing');

    expect($posicao)->not->toBeFalse('a declaracao de spacing nao foi emitida');

    $tagDoKit = substr($estilos, (int) strrpos(substr($estilos, 0, $posicao), '<style>'));

    expect($tagDoKit)
        ->toStartWith('<style>:root{--spacing:')
        ->not->toContain('@layer')
        ->not->toContain('!important');
});

/**
 * [CT-06] A leitura é POR REQUEST: a resposta muda sem o painel ser remontado.
 *
 * O caso de falsificabilidade da `Closure`, e o único que separa esta entrega da armadilha que
 * `.ai/rules/settings.md` registra. Uma implementação por `viteTheme()` — ou um render hook que
 * recebesse a string já resolvida em vez de uma `Closure` — congelaria a decisão no momento do
 * registro: CT-03 poderia até passar (o processo de teste nasceria com o valor certo), e o
 * toggle em produção gravaria sem fazer efeito até o próximo deploy.
 *
 * Mudar a config **depois** do `boot()` e exigir que a mesma chamada responda diferente é a
 * única forma de provar que a tela governa de verdade.
 */
it('[CT-06] muda de resposta no mesmo processo, sem remontar o painel', function (): void {
    gravarDensidade(DensidadeDoLayout::Confortavel->value);

    $antes = estilosAntesDoFilament();

    gravarDensidade(DensidadeDoLayout::Denso->value);

    expect($antes)->not->toContain('--spacing')
        ->and(estilosAntesDoFilament())->toContain('--spacing:0.175rem');
});

/*
|--------------------------------------------------------------------------
| Os três lugares do Settings
|--------------------------------------------------------------------------
*/

/**
 * [CT-07] Os TRÊS lugares estão ligados: propriedade, mapa e linha semeada.
 *
 * O docblock de `App\Settings\ConfiguracoesDoKit` chama o segundo de defeito silencioso, e com
 * razão: sem a linha no `mapaDeConfiguracao()`, o Select aparece na tela, grava no banco, e
 * `config('kit.densidade_do_layout')` continua para sempre no default do arquivo.
 *
 * A última asserção é o que impede o caso de ficar verde sobre um mapa vazio — ela fecha na
 * chave de configuração de verdade, depois do alinhamento, com um valor que **difere** do de
 * fábrica de propósito (um alinhamento que não fizesse nada devolveria `confortavel`).
 */
it('[CT-07] declara a propriedade, a linha do mapa e a linha semeada', function (): void {
    expect(property_exists(ConfiguracoesDoKit::class, 'densidade_do_layout'))->toBeTrue()
        ->and(ConfiguracoesDoKit::mapaDeConfiguracao())
        ->toHaveKey('densidade_do_layout', 'kit.densidade_do_layout');

    expect(SettingsProperty::query()->where('group', 'kit')->where('name', 'densidade_do_layout')->exists())
        ->toBeTrue('a propriedade densidade_do_layout nao tem linha semeada: o boot vai avisar em todo request');

    gravarDensidade(DensidadeDoLayout::Compacto->value);

    expect(config('kit.densidade_do_layout'))->toBe('compacto');
});

/**
 * [CT-08] A migration semeia com o valor de fábrica que o kit promete — e o `down()` limpa.
 *
 * CT-02 lê o ARQUIVO; quem governa a partir do primeiro boot real é a **linha semeada**, que o
 * alinhamento sobrepõe por cima. Uma migration que semeasse `denso` faria toda instalação nova
 * nascer apertada, contra a decisão explícita, e passaria em CT-01, CT-02 e CT-07.
 *
 * O `Dado` fixa a config antes de refazer a migration porque a terceira asserção seria vácua de
 * outro jeito: semear "o valor da config" e conferir contra a mesma config não distingue
 * implementação nenhuma. Aqui a config diz `denso` e a semente precisa dizer `denso` — o que
 * também mata a implementação que ignorasse a config e escrevesse o literal `confortavel`.
 */
it('[CT-08] semeia a densidade com o valor de fabrica e a remove no rollback', function (): void {
    $migration = require base_path('database/settings/2026_09_21_100000_add_densidade_do_layout_to_kit_settings.php');

    $migration->down();

    expect(SettingsProperty::query()->where('group', 'kit')->where('name', 'densidade_do_layout')->exists())
        ->toBeFalse('o down() da migration nao removeu a propriedade');

    config()->set('kit.densidade_do_layout', DensidadeDoLayout::Denso->value);

    $migration->up();

    expect(configuracaoGravada('densidade_do_layout'))->toBe(DensidadeDoLayout::Denso->value);
});

/**
 * [CT-09] A densidade não é segredo.
 *
 * Mesma razão do caso irmão em `AlertaDeAlteracoesNaoSalvasTest`: a lista `encrypted()` tem duas
 * caras de falha — nome fora da lista com `addEncrypted()` na migration devolve criptograma na
 * leitura, e nome dentro da lista sem necessidade cifra o que não precisava. A segunda metade lê
 * o `payload` CRU, sem passar pelo decifrador do spatie.
 */
it('[CT-09] nao trata a densidade como segredo', function (): void {
    expect(ConfiguracoesDoKit::encrypted())->not->toContain('densidade_do_layout');

    gravarConfiguracao('densidade_do_layout', DensidadeDoLayout::Denso->value);

    expect(configuracaoGravada('densidade_do_layout'))->toBe('denso');
});

/*
|--------------------------------------------------------------------------
| O vocabulário, e para que lado ele falha
|--------------------------------------------------------------------------
*/

/**
 * [CT-10] Valor fora do vocabulário falha FECHADO, no confortável.
 *
 * A chave é editada à mão no `.env` e gravada numa tabela, e o consumidor dela roda no render
 * hook de **toda tela dos três painéis**. `from()` em vez de `tryFrom()` transformaria
 * `KIT_DENSIDADE_DO_LAYOUT=compact` (em inglês, que é o erro provável) num `ValueError` no
 * layout base — a aplicação inteira fora do ar por um erro de digitação em configuração
 * cosmética.
 *
 * `'compact'` está no conjunto por isso, e `'COMPACTO'` porque o enum é sensível a caixa: o pior
 * resultado de um valor ilegível precisa ser "nada muda", nunca "tela em branco".
 */
it('[CT-10] cai no confortavel para qualquer valor fora do vocabulario', function (mixed $bruto): void {
    expect(DensidadeDoLayout::coagir($bruto))->toBe(DensidadeDoLayout::Confortavel);
})->with([
    'ingles'         => ['compact'],
    'caixa alta'     => ['COMPACTO'],
    'vazio'          => [''],
    'nulo'           => [null],
    'booleano'       => [true],
    'numero'         => [1],
    'texto qualquer' => ['sim'],
]);

/**
 * [CT-11] E um valor ilegível GRAVADO no banco não derruba a página servida.
 *
 * CT-10 mede a função; este mede a consequência, e os dois não se substituem: uma implementação
 * que coagisse corretamente em `config/kit.php` e chamasse `from()` no render hook passaria em
 * CT-10 e derrubaria o painel aqui. É o caminho real do defeito, porque `aplicarNaConfig()`
 * escreve o `payload` do banco DIRETO na config, sem passar pelo arquivo.
 */
it('[CT-11] serve a pagina normalmente com um nivel ilegivel gravado no banco', function (): void {
    gravarDensidade('compact');

    expect($this->get('/admin/login')->assertOk()->getContent())->not->toContain('--spacing:');
});

/*
|--------------------------------------------------------------------------
| Os valores medidos
|--------------------------------------------------------------------------
*/

/**
 * [CT-12] Os três níveis, com o `--spacing` que a medição no kit fixou.
 *
 * O caso congela o número, e é o que torna uma mudança de valor uma **decisão** em vez de um
 * commit distraído: trocar `0.2rem` por `0.2em` ou por `2px` reprovaria aqui, e não em nenhum
 * outro lugar da suíte. Os números que justificam cada um estão no docblock de cada caso do
 * enum, medidos em `/admin/users`.
 *
 * O `null` do confortável não é detalhe de implementação: é o contrato que impede o `<style>`
 * vazio que CT-01 e CT-04 cobrem do outro lado.
 */
it('[CT-12] fixa o espacamento medido de cada nivel', function (DensidadeDoLayout $nivel, ?string $esperado): void {
    expect($nivel->espacamento())->toBe($esperado);
})->with([
    'confortavel' => [DensidadeDoLayout::Confortavel, null],
    'compacto'    => [DensidadeDoLayout::Compacto, '0.2rem'],
    'denso'       => [DensidadeDoLayout::Denso, '0.175rem'],
]);

/**
 * [CT-14] O Select da tela grava, grava STRING, e o que ele gravou chega ao HTML.
 *
 * Este caso nasceu de um defeito **medido**, e é o mais barato de perder de vista: a primeira
 * versão do campo era `->options(DensidadeDoLayout::class)`, que parece a forma idiomática. O
 * Filament castea o estado de volta para instância do enum, o `fill()` do spatie a atribui à
 * propriedade tipada `string`, e o salvamento da **tela inteira** estoura `TypeError` — os campos
 * de e-mail, login social e anti-robô junto, nenhum deles tocado pelo diff.
 *
 * Nada em CT-01..CT-13 pegava isso: todos gravam direto na tabela, que é o caminho que contorna
 * exatamente o elo quebrado. O `Quando` aqui é a tela de verdade.
 *
 * As três asserções não se substituem: `assertHasNoFormErrors()` mata o campo que recusa o valor
 * válido, o `toBe('compacto')` sobre o `payload` CRU mata a gravação do enum serializado
 * (`{"value":"compacto"}` satisfaria uma comparação frouxa), e o HTML fecha a cadeia até a ponta.
 */
it('[CT-14] grava a densidade pelo Select da tela e a leva ate o HTML', function (): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);

    Filament::setCurrentPanel('admin');
    $this->actingAs(usuarioDoKit('admin'));

    Livewire::test(ConfiguracoesDoKitTela::class)
        ->fillForm(['densidade_do_layout' => DensidadeDoLayout::Compacto->value])
        ->call('save')
        ->assertHasNoFormErrors();

    expect(configuracaoGravada('densidade_do_layout'))->toBe('compacto');

    alinharConfiguracoesDoKit();

    /*
     * O dashboard e não `/admin/login`: aqui há sessão autenticada, e a tela de login responde
     * 302 para quem já entrou. O layout é o mesmo `base.blade.php` dos dois lados.
     */
    expect($this->get('/admin')->assertOk()->getContent())
        ->toContain('<style>:root{--spacing:0.2rem}</style>');
});

/**
 * [CT-13] São três níveis, e é a escala da ADR-04 — não um booleano disfarçado.
 *
 * A decisão do usuário foi por níveis justamente porque a distorção de proporção escala com a
 * intensidade. Um enum reduzido a dois casos cumpriria todos os outros casos deste arquivo e
 * desfaria a decisão em silêncio.
 */
it('[CT-13] expoe exatamente os tres niveis da escala', function (): void {
    expect(array_column(DensidadeDoLayout::cases(), 'value'))
        ->toBe(['confortavel', 'compacto', 'denso']);
});

/**
 * CT-16 — um nível ilegível gravado não trava a TELA de configurações.
 *
 * ## O par que faltava
 *
 * CT-11 já cobre o valor ilegível pelo lado do RENDER: a página servida não quebra e não emite
 * `--spacing`. Esta é a outra metade, e ela estava aberta — achado do `/code-review`.
 *
 * `Select` acrescenta sozinho um `Rule::in()` das próprias opções
 * (`vendor/filament/forms/src/Components/Select.php:getInValidationRuleValues()`). Com
 * `compact` — que não é nível do kit — gravado na linha de settings, o formulário nasce com esse
 * valor no estado e a validação o recusa. O efeito **não** fica contido no campo: a tela inteira
 * para de salvar, e quem tentar mudar o NOME DA APLICAÇÃO leva erro num campo que nem tocou.
 *
 * É exatamente o defeito que o docblock de `comValorConfigurado()`, neste mesmo arquivo de tela,
 * já descreve para `MAIL_MAILER=ses` — *"nem o nome da aplicação grava"*. O caso do `.env` foi
 * tratado; este nasceu junto com a densidade e não foi.
 *
 * ## Por que `coagir()` e não `comValorConfigurado()`
 *
 * São problemas diferentes com a mesma aparência. `comValorConfigurado()` existe para valor
 * **legítimo porém fora da lista curta** — `ses` é um transporte real do Laravel, e rebaixá-lo ao
 * default seria perda de dado. Nível de densidade tem **vocabulário fechado**: `compact` não é um
 * nível válido em lugar nenhum, é lixo. Oferecê-lo como opção marcada exibiria lixo na tela e o
 * gravaria de volta.
 *
 * `coagir()` é a mesma função que o render hook já usa (CT-10), então a tela e a página passam a
 * responder a mesma coisa para a mesma entrada — que é o invariante que importa.
 *
 * ## O oráculo é o CAMPO ALHEIO
 *
 * Asserir só que a densidade grava deixaria passar uma correção que conserta o campo e mantém a
 * tela travada. O `Então` afirma que `nome_da_aplicacao` — que o caso nem menciona no problema —
 * chega ao banco.
 */
it('[CT-16] um nivel ilegivel gravado nao impede salvar o resto da tela', function (): void {
    // Os demais casos deste arquivo não abrem tela, então o arquivo não semeia papéis.
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);

    gravarDensidade('compact');

    $this->actingAs(usuarioDoKit('admin'));

    Livewire::test(ConfiguracoesDoKitTela::class)
        ->fillForm(['nome_da_aplicacao' => 'Projeto Novo'])
        ->call('save')
        ->assertHasNoFormErrors()
        ->assertNotified();

    expect(configuracaoGravada('nome_da_aplicacao'))->toBe('Projeto Novo');

    expect(configuracaoGravada('densidade_do_layout'))->toBe(
        'confortavel',
        'o lixo tinha de ser coagido para o padrão ao entrar no formulário, e gravado coagido',
    );
})->group('kit');

/**
 * CT-17 — a largura do menu acompanha o nível, nos três painéis.
 *
 * ## Por que este caso existe, e o que ele fecha
 *
 * O quality gate reprovou a entrega por aqui: o menu é uma das **quatro** superfícies do escopo,
 * a altura dos itens encolhia (40 → 32,8 → 31,2 px) e a largura ficava em 320 px nos três níveis.
 * O `00-requisito.md` afirma que **nenhuma superfície fica parcialmente compacta** — *"meia tela
 * compacta é pior que nenhuma"*. Era exatamente isso.
 *
 * A largura não sai de `--spacing`: ela vem de `--sidebar-width`, emitido **inline** a partir de
 * `filament()->getSidebarWidth()` (`base.blade.php:85`). Nenhuma declaração de `--spacing`, em
 * layer nenhuma, a alcança — por isso ela precisou de mecanismo próprio.
 *
 * ## O oráculo é o PAINEL, não o HTML
 *
 * Os demais casos deste arquivo leem o HTML servido, porque o `<style>` do render hook só existe
 * ali. Aqui a leitura é `Filament::getPanel($x)->getSidebarWidth()`, e é o ponto certo: é esse
 * getter que o layout base chama, e ler dele exercita a **avaliação do Closure** — que é a parte
 * que pode quebrar. Um valor fixo passaria num `assertSee` do HTML e falharia aqui, que é o
 * defeito da ADR-06 (grava e só vale no próximo deploy).
 *
 * ## Os três painéis, e não um
 *
 * `sidebarWidth()` é por painel e foi escrito três vezes. Esquecer um é o defeito provável, e ele
 * seria invisível em qualquer caso que olhasse só o `/admin`.
 */
it('[CT-17] a largura do menu acompanha o nivel nos tres paineis', function (string $nivel, string $esperado): void {
    gravarDensidade($nivel);

    foreach (['app', 'admin', 'infra'] as $painel) {
        expect(Filament::getPanel($painel)->getSidebarWidth())->toBe(
            $esperado,
            "o painel `{$painel}` não acompanhou a densidade `{$nivel}`",
        );
    }
})->with([
    // `20rem` é o default do vendor (`HasSidebar.php:11`) — o confortável não muda nada.
    'confortavel' => ['confortavel', '20rem'],
    'compacto'    => ['compacto', '17rem'],
    'denso'       => ['denso', '16.5rem'],
    // Vocabulário ilegível cai no confortável, como em CT-10/CT-11.
    'ilegivel'    => ['compact', '20rem'],
])->group('kit');

/**
 * CT-18 — o nível confortável devolve **exatamente o default do Filament**.
 *
 * ## O caso existe porque a ADR afirmava uma guarda que não existia
 *
 * A ADR-03 diz que o confortável é **indistinguível do kit sem a feature** — é o contrato do
 * nível de fábrica, e a razão de `espacamento()` devolver `null` em vez de `'0.25rem'`. Quando a
 * largura do menu entrou, a ADR passou a afirmar que *"CT-17 fica vermelho"* se o Filament mudar
 * o próprio default.
 *
 * **Medido no ciclo 3 do quality gate: não fica.** Os dois lados da asserção de CT-17 são
 * literais do kit — `larguraDaSidebar()` de um lado, o dataset do outro —, então o default do
 * vendor **nunca entra na comparação**. Trocando `'20rem'` por `'18rem'` em `HasSidebar.php:11`,
 * CT-17 passa. E como os três painéis chamam `->sidebarWidth()`, o default fica inalcançável em
 * runtime: nenhum outro caso o veria mudar.
 *
 * O contrato não tinha guarda nenhuma, e a ADR dizia que tinha. Este caso é a guarda.
 *
 * ## Por que reflexão sobre a PROPRIEDADE, e não `getSidebarWidth()` de um painel
 *
 * Perguntar ao painel devolveria o valor que o **kit** configurou — a própria coisa que este caso
 * quer confrontar. A pergunta é sobre o vendor **antes** de o kit falar, e o único lugar onde ela
 * existe é a declaração da propriedade no trait.
 *
 * Vermelho aqui **não** quer dizer que o kit quebrou: quer dizer que o Filament mudou o default e
 * que a decisão precisa ser retomada — acompanhar o novo valor, ou declarar que o confortável
 * deixou de ser idêntico. As duas são escolha, e nenhuma pode acontecer em silêncio.
 */
it('[CT-18] o confortavel devolve exatamente o default do Filament', function (): void {
    $padraoDoVendor = (new ReflectionClass(Panel::class))
        ->getDefaultProperties()['sidebarWidth'] ?? null;

    expect($padraoDoVendor)->toBeString(
        'a propriedade `sidebarWidth` sumiu do `Panel` — o contrato do nível confortável perdeu '
        .'a referência contra a qual ele é afirmado',
    );

    expect(DensidadeDoLayout::Confortavel->larguraDaSidebar())->toBe(
        $padraoDoVendor,
        'o Filament mudou o default de `--sidebar-width`, e o nível confortável do kit deixou de '
        .'ser indistinguível do kit SEM a feature — que é o contrato da ADR-03. Acompanhar o novo '
        .'valor ou declarar que o contrato mudou: as duas são decisão, nenhuma é silêncio',
    );
})->group('kit');
