{{--
    A assinatura do sistema no rodapé de toda tela dos painéis E das telas de autenticação.

    Registrada UMA vez, em `App\Providers\Concerns\ConfiguraFilamentGlobal`, no render hook
    `FOOTER` e **sem `scopes:`** — vale para os três painéis e para qualquer painel que o projeto
    criar depois. É a mesma técnica do botão "Voltar ao topo", e pelo mesmo motivo.

    ── O que mudou, e por quê ──

    Esta blade se chamava `versao-do-kit.blade.php` e mostrava só versões. O nome já era
    impreciso: ela mostra `config('app.version')`, a versão do SISTEMA, e a do kit só sob toggle.
    Agora mostra também `© {ano} {nome}`, e o nome antigo ficaria ativamente errado (ADR-03).

    A COMPOSIÇÃO saiu daqui para `App\Support\AssinaturaDoRodape`, porque a mesma linha passou a
    ser usada em duas superfícies e regra duplicada diverge. O que ficou aqui é o que só aqui se
    sabe: QUEM ESTÁ OLHANDO.

    ── A guarda se estreitou, e é o ponto de atenção desta blade ──

    Antes, `filament()->auth()->check()` envolvia o bloco inteiro: visitante não via nada.
    Agora ela decide só UMA PARTE — o nome e o `©` valem para todo mundo, a VERSÃO não.

    O motivo de a versão continuar guardada é o mesmo de sempre, e não mudou: mostrá-la na tela de
    login é entregar o mapa de CVEs aplicáveis a quem ainda não autenticou. O que mudou foi o
    alcance da guarda, não a decisão.

    `filament()->auth()->check()` e NÃO `@auth`: o `@auth` consulta o guard DEFAULT, e o kit nasce
    sem `->authGuard()` nos painéis — mas o projeto que nascer dele pode declarar um, e aí o
    `@auth` responderia sobre outro guard que não o do painel. O modo de falhar seria a versão
    aparecendo para visitante, com o diff parecendo correto.

    ── Duas versões, e elas não são a mesma coisa ──

    `config('app.version')` é a versão do PRODUTO que nasceu do kit, editável em
    /admin/configuracoes-da-aplicacao (semeada por `APP_VERSION`). Vazia, não aparece — projeto que
    não versiona não precisa fingir que versiona.

    `config('kit.version')` é a versão do STARTER KIT que originou o projeto: métrica interna,
    consumida pelo `kit:update`. Só aparece com o toggle `kit.exibir_versao` ligado, e ele nasce
    DESLIGADO — quem entrega o produto a um cliente final não tem por que anunciar de qual kit ele
    nasceu.

    ── SAÍDA ESCAPADA, e isto não é detalhe ──

    `{{ }}`, nunca `{!! !!}`. O nome vem de campo editável, e esta blade passou a renderizar em
    tela PÚBLICA e NÃO AUTENTICADA. O recado do login, logo abaixo desta linha, aceita Markdown
    por um motivo próprio e documentado na blade dele — esse motivo NÃO se estende ao nome, que é
    texto puro e não tem por que formatar. Unificar as duas vias por simetria estética abriria a
    superfície do nome (ADR-05).

    ── Sem utilitária Tailwind ──

    O kit não tem `viteTheme()`, então utilitária que esta blade emitisse não existiria na folha
    compilada e o rodapé sairia sem estilo nenhum, com o teste verde (`.ai/rules/css-filament.md`).
    A classe é do kit e mora em `resources/css/filament/kit.css`. Ela não declara COR de propósito:
    herda a do tema e usa `opacity`, então funciona no claro e no escuro sem um par de regras.

    ── `<footer>`, e não `<div>` ──

    O elemento é filho direto de `<body>` e renderiza em tela PÚBLICA. Como `<div>`, o axe-core
    acusava `region` — conteúdo fora de qualquer landmark — nas telas de autenticação, que é onde
    a assinatura passou a aparecer. `<footer>` nessa posição tem `role=contentinfo` implícito e
    resolve sem atributo extra. Achado QA-09 do quality gate.
--}}
@php
    $partes = \App\Support\AssinaturaDoRodape::partes(comVersao: filament()->auth()->check());
@endphp

@if ($partes !== [])
    <footer class="kit-versao">{{ implode(' · ', $partes) }}</footer>
@endif
