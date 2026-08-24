<?php

use Filament\Facades\Filament;
use Filament\Support\Colors\Color;
use Illuminate\Support\Facades\View;

/**
 * A página de boas-vindas da rota `/`, que substituiu a welcome padrão do Laravel.
 *
 * Ver `wikis/specs/feat/pagina-boas-vindas/pagina-boas-vindas/04-casos-de-teste.md` — os IDs de CT
 * abaixo são os de lá.
 *
 * Nenhum `actingAs()` na maioria dos casos, e isso é o ponto: a rota é anônima, como a welcome que
 * ela substitui. O único caso com usuário é CT-13, que existe para provar que a página **não**
 * revela a identidade dele.
 *
 * O `phpunit.xml` fixa `KIT_COR_PRIMARIA=''`, `KIT_DEMO=false`, `KIT_HUB=false` e os rótulos de
 * organização com `force="true"` — então a partição "configuração de fábrica" não precisa de
 * arranjo. As quatro chaves de prazo (`validade_em_dias`, `limite_do_lote` e as retenções) **não**
 * são fixadas lá, e é por isso que CT-08 afirma o valor efetivo lido antes de asserir a tela.
 */

/**
 * CT-01 — o visitante anônimo recebe a página do kit, e não a welcome do Laravel.
 *
 * O `assertDontSee('Documentation')` é a asserção discriminante: aquele texto é da welcome de
 * fábrica, e uma rota que continuasse em `view('welcome')` responderia 200 com ele.
 *
 * O nome da aplicação vem de `config('app.name')` lido aqui, nunca da string literal: o
 * `phpunit.xml` não fixa `APP_NAME`, e cravar o valor tornaria o caso um teste do `.env` de quem
 * roda a suíte.
 */
it('entrega a pagina de boas-vindas do kit a quem nao esta autenticado', function (): void {
    $this->get('/')
        ->assertOk()
        ->assertSee('Bem-vindo ao Starter Kit Easy')
        ->assertSee((string) config('app.name'))
        ->assertDontSee('Documentation');
});

/** CT-02 — a welcome padrão do Laravel não existe mais no projeto. */
it('nao tem mais a view welcome padrao do laravel', function (): void {
    expect(View::exists('welcome'))->toBeFalse();
});

/**
 * CT-03 — um cartão por painel, apontando para a raiz do painel.
 *
 * A asserção é sobre o `href`, e não sobre o rótulo: os rótulos são premissa desta wiki (o
 * requisito só diz "cards para acessar os paines"), e asserir texto de premissa fixaria uma decisão
 * que o usuário ainda pode mudar.
 *
 * A URL é montada por `url()` aqui, não escrita à mão — o mesmo caminho que a página usa quando o
 * painel não tem domínio próprio.
 */
it('tem um cartao por painel apontando para a raiz do painel', function (string $painel): void {
    $destino = url(Filament::getPanel($painel)->getPath());

    $this->get('/')
        ->assertOk()
        ->assertSee('href="'.$destino.'"', escape: false);
})->with([
    'painel de negócio'        => ['app'],
    'painel de administração'  => ['admin'],
    'painel de infraestrutura' => ['infra'],
]);

/**
 * CT-05 — a grade sai sob o escopo de CSS que o kit mantém para o pacote de cartões.
 *
 * As três asserções juntas, porque cada uma cobre um modo de falhar diferente e todos são
 * silenciosos: sem a classe de escopo o `cards.css` não alcança nada; sem a folha publicada não há
 * o que alcançar; e `lg:grid-cols-3` é a largura de grade que o arquivo cobre — `$columns >= 5`
 * geraria uma classe que ele declara, por escrito, nunca ter.
 *
 * Nenhuma delas prova que a grade está legível. Isso é CT-B01.
 */
it('renderiza a grade sob o escopo de css dos cartoes do kit', function (): void {
    $this->get('/')
        ->assertOk()
        ->assertSee('kit-cards-page')
        ->assertSee('kit-cards.css')
        ->assertSee('lg:grid-cols-3');
});

/**
 * CT-06 — a página pública não traz a barra lateral nem a topbar do painel.
 *
 * O `assertSee('fi-simple-main-ctn')` não é redundante com o `assertDontSee`: sozinho, o
 * `assertDontSee` passaria numa resposta vazia.
 *
 * **A asserção negativa é sobre `id="fi-main-sidebar"`, e não sobre a classe `fi-sidebar`.** A
 * primeira versão deste caso usava a classe e ficou vermelha sem defeito: `fi-sidebar` aparece
 * **11 vezes** na resposta, todas dentro de blocos `<style>` — o CSS do
 * `gsferro/filament-odometer-easy` e o `kit.css` escrevem seletores `.fi-main-sidebar` que existem
 * mesmo em página sem barra lateral. Nome de classe em texto de folha de estilo não é elemento
 * renderizado. O `id`, que só a `livewire/sidebar.blade.php:20` emite, é.
 */
it('nao traz a barra lateral nem a topbar do painel', function (): void {
    $this->get('/')
        ->assertOk()
        ->assertDontSee('id="fi-main-sidebar"', escape: false)
        ->assertDontSee('fi-simple-layout-header')
        ->assertSee('fi-simple-main-ctn');
});

/**
 * CT-07 — cada informação exibida vem da chave de config dela.
 *
 * Os valores plantados não existem em nenhum `.env` do kit: nenhum deles pode ser acertado por
 * acidente. É o caso que mata "os valores estão escritos na tela" e "a chave vizinha foi trocada" —
 * as duas linhas de rótulo de organização plantam valores diferentes de propósito.
 *
 * A linha de idiomas planta DOIS idiomas: com um só, uma implementação que exibisse apenas o
 * primeiro item da lista passaria.
 */
it('exibe o valor lido de cada chave de config', function (string $chave, mixed $plantado, string $exibido): void {
    config([$chave => $plantado]);

    $this->get('/')
        ->assertOk()
        ->assertSee($exibido);
})->with([
    'nome da aplicação'      => ['app.name', 'Projeto Sentinela', 'Projeto Sentinela'],
    'versão do kit'          => ['kit.version', '9.9.9-sentinela', '9.9.9-sentinela'],
    'rótulo singular'        => ['kit.tenancy.label', 'Unidade', 'Unidade'],
    'rótulo plural'          => ['kit.tenancy.label_plural', 'Unidades', 'Unidades'],
    'lista de idiomas'       => ['kit.idiomas', ['pt_BR', 'en'], 'pt_BR, en'],
    'prazo de convite'       => ['kit.convites.validade_em_dias', 21, '21 dias'],
    'limite do lote'         => ['kit.convites.limite_do_lote', 42, '42'],
    'retenção de importação' => ['kit.retencao.importacoes_em_dias', 77, '77 dias'],
]);

/**
 * CT-08 — com a configuração de fábrica, os valores do kit aparecem.
 *
 * Par indivisível com CT-07: uma implementação que escreve os valores literalmente na tela passa
 * aqui e morre lá; uma que lê a chave errada morre lá.
 *
 * As quatro primeiras linhas afirmam o **valor efetivo lido**, não "a configuração de fábrica".
 * Sem elas o caso mediria o `.env` de quem roda a suíte — o `phpunit.xml` fixa cinco chaves com
 * `force="true"`, e nenhuma destas quatro está entre elas.
 */
it('exibe os valores de fabrica do kit quando nada foi personalizado', function (): void {
    expect(config('kit.convites.validade_em_dias'))->toBe(7)
        ->and(config('kit.convites.limite_do_lote'))->toBe(100)
        ->and(config('kit.retencao.excecoes_em_dias'))->toBe(14)
        ->and(config('kit.retencao.importacoes_em_dias'))->toBe(30);

    $this->get('/')
        ->assertOk()
        ->assertSee('7 dias')
        ->assertSee('100')
        ->assertSee('14 dias')
        ->assertSee('30 dias');
});

/**
 * CT-10 — prazo de retenção desligado é exibido como desligado, nunca como o número.
 *
 * A fronteira é o zero, e as três linhas ao redor dela são o ponto do caso. `.ai/rules/config.md`
 * registra que este exato limite, escrito com o comparador errado, apagou a trilha de exceções
 * inteira neste kit — `subDays(0)` é hoje. Aqui a consequência é só de exibição; a fronteira é a
 * mesma.
 *
 * A linha `1` não é redundante com `14`: ela é a única que distingue "1 dia" de "1 dias".
 */
it('exibe prazo de retencao desligado como sem poda', function (int $dias, string $exibido): void {
    config(['kit.retencao.excecoes_em_dias' => $dias]);

    $this->get('/')
        ->assertOk()
        ->assertSee($exibido);
})->with([
    'borda menos um' => [-1, 'Sem poda'],
    'borda'          => [0, 'Sem poda'],
    'borda mais um'  => [1, 'exceções 1 dia'],
    'dentro'         => [14, 'exceções 14 dias'],
]);

/**
 * CT-11 — lembretes de convite com lista vazia são exibidos como desligados.
 *
 * A lista de um elemento não exercita o ramo de junção da frase ("3º e 5º dia"), e é por isso que
 * as três partições existem.
 */
it('exibe lembretes de convite conforme a lista configurada', function (array $lista, string $exibido): void {
    config(['kit.convites.lembretes_dias' => $lista]);

    $this->get('/')
        ->assertOk()
        ->assertSee($exibido);
})->with([
    'vazio'       => [[], 'Desligados'],
    'um elemento' => [[3], '3º dia'],
    'dois'        => [[3, 5], '3º e 5º dia'],
]);

/**
 * CT-12 — nenhum segredo aparece na resposta da rota `/`.
 *
 * Uma linha por item da lista negra do ADR-04, e é exaustivo por construção: se alguém acrescentar
 * à página uma entrada que leia uma dessas chaves, a linha correspondente fica vermelha. É a
 * mitigação que o ADR-04 promete.
 *
 * Os sentinelas são distintivos de propósito — `assertDontSee('password')` cru casaria com texto de
 * layout e com script do Filament, e não provaria nada.
 *
 * O `assertOk()` não é enfeite: sem ele, uma resposta 500 passaria em todas as linhas de
 * `assertDontSee`. É a armadilha do "cenário de recusa que não afirma o não-efeito", aplicada a uma
 * asserção de ausência.
 */
it('nao vaza segredo nem dado de infraestrutura na rota publica', function (string $chave, string $sentinela): void {
    config([$chave => $sentinela]);

    $this->get('/')
        ->assertOk()
        ->assertDontSee($sentinela);
})->with([
    'e-mail do administrador' => ['kit.admin.email', 'admin-sentinela@proibido.test'],
    'senha do administrador'  => ['kit.admin.password', 'SenhaSentinela9Z'],
    'nome do administrador'   => ['kit.admin.name', 'NomeAdminSentinela9Z'],
    'repositório do kit'      => ['kit.repository', 'https://git.interno.sentinela/x.git'],
    'host do banco'           => ['database.connections.mysql.host', 'host-sentinela.interno'],
    'usuário do banco'        => ['database.connections.mysql.username', 'usuario-sentinela-db'],
    'ambiente da aplicação'   => ['app.env', 'ambiente-sentinela'],
    'usuário do smtp'         => ['mail.mailers.smtp.username', 'smtp-sentinela'],
]);

/**
 * CT-13 — a página não muda de comportamento para quem está autenticado.
 *
 * A primeira versão deste caso afirmava a AUSÊNCIA do e-mail do próprio usuário, e estava errada
 * como especificação: com sessão ativa o `layout.simple` renderiza a topbar
 * (`components/layout/simple.blade.php:30`) e o kit pendura ali o cabeçalho de identidade
 * (`resources/views/filament/user-menu-header.blade.php`, pelo render hook
 * `USER_MENU_PROFILE_BEFORE`). O e-mail aparece — para o próprio dono da sessão, no menu de usuário
 * padrão do Filament. Isso não é vazamento; é a tela funcionando.
 *
 * O que a rota `/` não pode fazer é mostrar identidade a **quem não está autenticado**, e essa é a
 * asserção de CT-06 (`assertDontSee('fi-simple-layout-header')`), onde não há sessão nenhuma.
 *
 * Sem papel de propósito: `usuario()` em vez de `usuarioDoKit()`. O caso é sobre sessão, não sobre
 * autorização, e pedir um papel obrigaria a semear `PapeisSeeder` e `ShieldPermissionsSeeder` para
 * um cenário que não consulta permissão nenhuma.
 */
it('funciona igual para quem esta autenticado', function (): void {
    $this->actingAs(usuario('identidade-sentinela@example.test'))
        ->get('/')
        ->assertOk()
        ->assertSee('Bem-vindo ao Starter Kit Easy')
        ->assertSee('href="'.url(Filament::getPanel('admin')->getPath()).'"', escape: false);
});

/**
 * CT-14 — a resposta traz a folha do Filament, o CSS do kit e o script de tema do painel.
 *
 * As quatro asserções são as quatro coisas que o `layout.base` do painel emite e que uma Blade
 * solta com `@filamentStyles` NÃO emitiria — medido antes de escolher o desenho (ADR-01).
 *
 * O `assertSee('loadDarkMode')` prova que o script está lá; não prova que o tema é aplicado. Isso é
 * CT-B01, e a distinção está em `.ai/rules/testes-browser.md`.
 */
it('herda a folha do filament, o css do kit e o script de tema', function (): void {
    $this->get('/')
        ->assertOk()
        ->assertSee('css/filament/filament/app.css')
        ->assertSee('kit-correcoes.css')
        ->assertSee('--primary-500')
        ->assertSee('loadDarkMode');
});

/**
 * CT-15 — a cor primária escolhida pelo projeto chega até a página.
 *
 * **É o caso discriminante de toda esta wiki.** Medido antes de escrever o plano:
 * `FilamentAsset::renderStyles()` sem painel corrente, com `KIT_COR_PRIMARIA=Violet` na env, emite
 * o tom 500 do ÂMBAR — a paleta do projeto só entra por `Panel::boot()`, que chama
 * `FilamentColor::register($this->getColors())` (`vendor/filament/filament/src/Panel.php:95`). Uma
 * página que emitisse só `@filamentStyles` passaria em CT-14 e morreria aqui.
 *
 * A cor é `Violet` e não a de fábrica porque o `phpunit.xml` fixa `KIT_COR_PRIMARIA=''` — e com a
 * chave vazia a cor correta É o âmbar do default, então as duas implementações produziriam o mesmo
 * byte. Valor redondo não discrimina.
 *
 * O tom é lido de `Color::` e nunca escrito como literal `oklch(...)`: um upgrade de paleta do
 * Filament quebraria o caso sem defeito nenhum no kit.
 *
 * **A asserção é sobre o par `--primary-500:{tom}`, e não sobre o tom solto.** A primeira versão
 * deste caso afirmava também `assertDontSee(Color::Amber[500])` e ficou vermelha sem defeito: o
 * âmbar É a paleta padrão de `--warning-*` no Filament, então o tom 500 do âmbar aparece na
 * resposta de qualquer jeito. Afirmar o par prende a cor à variável certa, que é o que o caso
 * quer dizer.
 */
it('aplica a cor primaria escolhida pelo projeto', function (): void {
    config(['kit.cor_primaria' => 'Violet']);

    $this->get('/')
        ->assertOk()
        ->assertSee('--primary-500:'.Color::Violet[500], escape: false);
});

/**
 * CT-16 — a cor primária distingue ausente, vazia e escolhida.
 *
 * `CorPrimaria::paleta()` trata `null` e `''` como o mesmo caso, e uma implementação que só testasse
 * `=== null` mostraria uma linha em branco na string vazia — que é o que sobra quando alguém apaga
 * o valor do `.env` e esquece o `=`.
 */
it('distingue cor primaria ausente, vazia e escolhida', function (?string $valor, string $exibido): void {
    config(['kit.cor_primaria' => $valor]);

    $this->get('/')
        ->assertOk()
        ->assertSee($exibido);
})->with([
    'ausente'    => [null, 'Âmbar (padrão do Filament)'],
    'vazia'      => ['', 'Âmbar (padrão do Filament)'],
    'preenchida' => ['Violet', 'Violet'],
]);

/**
 * CT-17 — o nome da aplicação é escapado antes de ir para a tela.
 *
 * `APP_NAME` vem do `.env`, escrito pelo `kit:install` a partir do que a pessoa digitou. É entrada
 * de usuário numa página pública.
 *
 * O `assertSee('Ação')` existe para o caso não passar com a página quebrada: o `assertDontSee`
 * sozinho passaria numa resposta 500 — e o `assertOk()` acima cobre o mesmo eixo por outro lado.
 */
it('escapa o nome da aplicacao antes de exibir', function (): void {
    config(['app.name' => '<script>alert(1)</script> Ação']);

    $this->get('/')
        ->assertOk()
        ->assertDontSee('<script>alert(1)</script>', escape: false)
        ->assertSee('Ação');
});
