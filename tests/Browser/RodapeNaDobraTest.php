<?php

/**
 * O rodapé das telas de autenticação cabe na dobra — e isto SÓ o navegador prova.
 *
 * ## Por que este arquivo existe
 *
 * O `01-plano-acao.md` declarou, na seção `## Gate de CT-B`, que a entrega era "composição de
 * texto e ordem de render — nada que só o navegador prove". **A própria entrega falseou essa
 * declaração**: o step 6.5 achou um Blocker que nenhum dos 113 casos de HTML via — a
 * assinatura em `y=1122` e o recado em `y=1186` num viewport de 1117px, os dois abaixo da dobra.
 *
 * A correção foi uma regra de CSS (`resources/css/filament/kit.css`), e ela entrou **sem teste**.
 * Duas rules do projeto prescrevem exatamente o oráculo que faltava:
 *
 * - `.ai/rules/testes-browser.md`: *"o oráculo é número, não presença"* — a rule nasceu de um
 *   overlay a 1.833px numa viewport de 1.117px, o mesmo sintoma e o mesmo número;
 * - `.ai/rules/css-filament.md`: folha do kit precisa de guarda que leia o vendor em runtime.
 *
 * ## O que este teste protege, e por que ele não é decorativo
 *
 * A regra de CSS depende de **dois fatos do vendor**:
 *
 * 1. `.fi-auth-layout { min-height: 100vh }` — `caresome/filament-auth-designer`;
 * 2. o hook `FOOTER` ser emitido **depois** do fechamento dessa div.
 *
 * `composer update` move âncoras de vendor em bloco e em silêncio. No dia em que qualquer um dos
 * dois mudar, o Blocker volta com a suíte inteira verde — porque `assertSee` fica verde com o
 * elemento fora da tela. Achado QA-04 do quality gate.
 *
 * ## `assertVisible` NÃO serve aqui
 *
 * O `assertVisible` do Playwright exige bounding box não-vazio; **não** exige estar no viewport.
 * O elemento a 1.122px num viewport de 1.117px é "visível" para ele. Por isso o oráculo é
 * geometria comparada com `innerHeight`, e não presença.
 */
it('[CT-B01] mantem a assinatura e o recado dentro da dobra nas telas de autenticacao', function (string $rota, bool $unificado, bool $esperaRecado): void {
    config(['kit.login.rodape' => 'Fale com o suporte']);
    ligarLoginUnificado($unificado);

    $medida = json_decode((string) visit($rota)->script(<<<'JS'
        (() => {
            const a = document.querySelector('.kit-versao');
            const r = document.querySelector('.fi-login-rodape');
            const caixa = (el) => el ? el.getBoundingClientRect() : null;
            const ca = caixa(a), cr = caixa(r);
            return JSON.stringify({
                temAssinatura: a !== null,
                temRecado: r !== null,
                vh: innerHeight,
                assinaturaTop: ca ? Math.round(ca.top) : null,
                assinaturaBottom: ca ? Math.round(ca.bottom) : null,
                recadoTop: cr ? Math.round(cr.top) : null,
                recadoBottom: cr ? Math.round(cr.bottom) : null,
                alturaDocumento: document.documentElement.scrollHeight,
            });
        })()
    JS), true, flags: JSON_THROW_ON_ERROR);

    /*
     * CONTROLE POSITIVO, e ele vem primeiro: sem o elemento no DOM, toda comparação de geometria
     * abaixo seria feita sobre `null` e o caso ficaria verde sobre nada. É a asserção que prova
     * que estávamos olhando para a tela certa.
     */
    expect($medida['temAssinatura'])->toBeTrue("a assinatura não existe em {$rota} — a geometria abaixo mediria o vazio");
    expect($medida['temRecado'])->toBe($esperaRecado, "o recado em {$rota} não está como o escopo do hook promete");

    // O oráculo: o elemento INTEIRO cabe, não só o topo dele.
    expect($medida['assinaturaBottom'])->toBeLessThanOrEqual(
        $medida['vh'],
        "a assinatura termina em y={$medida['assinaturaBottom']} num viewport de {$medida['vh']}px — abaixo da dobra, como no Blocker do step 6.5",
    );

    if ($esperaRecado) {
        expect($medida['recadoBottom'])->toBeLessThanOrEqual(
            $medida['vh'],
            "o recado termina em y={$medida['recadoBottom']} num viewport de {$medida['vh']}px — e ele era visível DENTRO do cartão antes desta feature",
        );

        // A ordem VISUAL, que é o que o requisito pede e o DOM sozinho não garante: o CSS pode
        // reordenar (`flex-col-reverse`) com a ordem do documento intacta.
        expect($medida['assinaturaTop'])->toBeLessThan(
            $medida['recadoTop'],
            'a assinatura deveria aparecer ACIMA do recado na tela, não só antes dele no documento',
        );
    }
})->with([
    'login do admin'        => ['/admin/login', false, true],
    'login do app'          => ['/app/login', false, true],
    'login do infra'        => ['/infra/login', false, true],
    'página única de login' => ['/login', true, true],
    'recuperação de senha'  => ['/admin/password-reset/request', false, false],
])->group('browser-kit');

/**
 * CT-B02 — a faixa tem ESTILO, e não só existe.
 *
 * O kit não usa `viteTheme()`. Utilitária Tailwind emitida por blade do kit **não existe** na
 * folha compilada, e a faixa sairia sem estilo nenhum com o HTML byte a byte correto e a suíte
 * inteira verde (`.ai/rules/css-filament.md`). `.kit-versao` é classe do kit, declarada em
 * `resources/css/filament/kit.css` — este caso é o que prova que ela chegou ao navegador.
 *
 * Mata o mutante M35 do `04`, que estava declarado sem matador.
 */
it('[CT-B02] entrega a faixa do rodape com o estilo do kit aplicado', function (): void {
    $estilo = json_decode((string) visit('/admin/login')->script(<<<'JS'
        (() => {
            const a = document.querySelector('.kit-versao');
            const cs = getComputedStyle(a);
            return JSON.stringify({
                fontSize: cs.fontSize,
                textAlign: cs.textAlign,
                opacity: parseFloat(cs.opacity),
                display: cs.display,
            });
        })()
    JS), true, flags: JSON_THROW_ON_ERROR);

    /*
     * Os três valores vêm da regra do kit, e nenhum é default do navegador: `font-size` de body é
     * 16px, `text-align` é `start` e `opacity` é 1. Se a folha não chegar, os três divergem de uma
     * vez — é o que torna o caso sensível à falha que ele existe para pegar.
     */
    expect($estilo['fontSize'])->toBe('12px', 'a faixa saiu com o tamanho padrão do navegador — a folha do kit não chegou');
    expect($estilo['textAlign'])->toBe('center', 'a faixa não está centralizada — a folha do kit não chegou');
    expect($estilo['opacity'])->toBeLessThan(1.0, 'a faixa está em opacidade cheia — a folha do kit não chegou');
})->group('browser-kit');

/**
 * CT-B03 — o rodapé não introduz problema de acessibilidade na tela pública.
 *
 * ## Por que este caso existe, e por que ele é a resposta certa a três achados
 *
 * O ciclo 1 do quality gate (QA-09) apontou que o elemento da assinatura era um `<div>` filho
 * direto de `<body>` — conteúdo fora de qualquer landmark, numa tela pública. Virou `<footer>`,
 * que tem `role=contentinfo` implícito nessa posição.
 *
 * O ciclo 2 (QA-22) apontou que **nenhum caso afirmava a tag**: reverter para `<div>` deixava
 * 113/113 verdes. E o ciclo 3 (QA-35) apontou algo pior — eu justifiquei não fechar a lacuna
 * vizinha com uma consequência do axe que **nunca tinha rodado**.
 *
 * Afirmar a TAG seria o oráculo errado: ele congela a implementação em vez da propriedade. O que
 * importa não é ser `<footer>` — é a tela pública não ganhar problema de acessibilidade por causa
 * do rodapé. É isso que este caso mede, e é o que a rule do projeto pede quando há superfície
 * pública nova.
 *
 * Medido antes de escrever: o axe passa hoje, com a assinatura e o recado presentes.
 */
it('[CT-B03] nao acrescenta elemento sem landmark na tela publica', function (string $rota, bool $unificado): void {
    config(['kit.login.rodape' => 'Fale com o **suporte**']);
    ligarLoginUnificado($unificado);

    /*
     * O ORACULO E DE PERTINENCIA, e nao `assertNoAccessibilityIssues()` puro. Duas medicoes, as
     * duas do achado QA-40 do quality gate:
     *
     * 1. O NIVEL PADRAO E CEGO PARA ESTA CLASSE. `assertNoAccessibilityIssues(int $level = 1)`
     *    mantem so `critical` e `serious`; a regra `region` — a que o <div> disparava e o <footer>
     *    resolve — e `moderate`. A primeira redacao deste caso usava o padrao e era cega ao
     *    defeito que veio guardar: revertendo para <div>, ficava verde.
     *
     * 2. NO NIVEL `moderate` A TELA JA TEM DOIS PROBLEMAS, E NENHUM E DESTA FEATURE. Medido:
     *    `landmark-one-main` no <html> e `region` na `.fi-auth-media-section` e nos campos do
     *    formulario — todos do layout do `filament-auth-designer`, que nao emite <main>. Exigir
     *    zero seria reprovar esta entrega por divida alheia.
     *
     * Entao o que se afirma e: os elementos que ESTA feature acrescenta nao estao entre os
     * acusados. Ver `04-casos-de-teste.md`, regra R8, mutantes M58-M62.
     *
     * AS TRES REGRAS, e nao so `region`. Varrer so `region` deixaria M62 vivo: <footer> no recado
     * limpa o `region` e QUEBRA as outras duas, acusando a assinatura. As tres foram medidas nas
     * tres formas (<div>, <footer>, <aside>) — a tabela esta na regra R8.
     */
    $acusados = json_decode((string) visit($rota)->script(<<<'JS'
        (() => new Promise((resolve) => {
            const regras = ['region', 'landmark-no-duplicate-contentinfo', 'landmark-unique'];
            axe.run(document, { runOnly: { type: 'rule', values: regras } }).then((r) => {
                const nos = r.violations.flatMap((v) => v.nodes).flatMap((n) => n.target).map(String);
                resolve(JSON.stringify({ total: nos.length, alvos: nos }));
            });
        }))()
    JS), true, flags: JSON_THROW_ON_ERROR);

    /*
     * CONTROLE POSITIVO DO DETECTOR, e ele vem primeiro: um axe que nao rodasse devolveria lista
     * vazia, e as duas ausencias abaixo ficariam verdes sobre nada. Esta tela acusa dois
     * elementos hoje, os dois do vendor — se a lista vier vazia, e a varredura que falhou.
     */
    expect($acusados['total'])->toBeGreaterThan(0, "o axe nao acusou nada em {$rota} — a varredura nao rodou, e as ausencias abaixo mediriam o vazio");

    $lista = implode(' ', $acusados['alvos']);

    $this->assertStringNotContainsString('kit-versao', $lista, "a assinatura foi acusada em {$rota}: ou ficou fora de landmark (M58), ou virou o segundo <footer> da tela e duplicou o contentinfo (M62)");
    $this->assertStringNotContainsString('fi-login-rodape', $lista, "o recado foi acusado em {$rota} — ele precisa de um landmark proprio, e <aside> e o unico que nao duplica o da assinatura (M59)");
})->with([
    'login do admin'       => ['/admin/login', false],
    'página única'         => ['/login', true],
    'recuperação de senha' => ['/admin/password-reset/request', false],
])->group('browser-kit');
