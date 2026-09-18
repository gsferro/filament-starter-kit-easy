{{--
    A versão do SISTEMA no rodapé de toda tela dos painéis — e, opcionalmente, a do kit ao lado.

    Registrado UMA vez, em `App\Providers\Concerns\ConfiguraFilamentGlobal`, no render hook
    `FOOTER` e **sem `scopes:`** — vale para os três painéis e para qualquer painel que o projeto
    criar depois. É a mesma técnica do botão "Voltar ao topo", e pelo mesmo motivo.

    ── Duas versões, e elas não são a mesma coisa ──

    `config('app.version')` é a versão do PRODUTO que nasceu do kit. É ela que o rodapé mostra, e
    ela vem da tela /admin/configuracoes-da-aplicacao (semeada por `APP_VERSION` no `.env`). Vazia,
    o rodapé não mostra versão nenhuma — projeto que não versiona não precisa fingir que versiona.

    `config('kit.version')` é a versão do STARTER KIT que originou o projeto: métrica interna do
    kit, consumida pelo `kit:update` para saber de onde comparar. Só aparece quando o toggle
    `kit.exibir_versao` está ligado, e ele nasce DESLIGADO — quem entrega o produto a um cliente
    final não tem por que anunciar de qual kit ele nasceu.

    Confundir as duas foi o erro que o Adendo 2 do `00-requisito.md` corrigiu: a primeira versão
    desta blade mostrava a do kit como se fosse a do sistema.

    ── Sem ler `.git` ──

    Nem tag, nem branch `release/<versão>`, e a decisão é explícita. Imagem de produção normalmente
    não tem `.git`, e ler de lá criaria uma segunda fonte de verdade que divergiria do que a tela
    mostra. Quem implanta é quem preenche `APP_VERSION` ou o campo da tela.

    ── Por que não um pacote ──

    Existe `vaslv/filament-app-version`, e ele resolve versão por config, por composer.json, por
    arquivo e pelo SHA curto do git — mas NÃO por tag nem por nome de branch. Somando que ele exige
    `php: ^8.4` contra o `^8.3` declarado do kit, sobrou uma dependência a mais para um `<span>`.
    Ver ADR-04 da wiki `estudo-de-pacotes-rodada-2`.

    ── O guard de visitante não é opcional ──

    Este hook é emitido por DOIS layouts: o do painel
    (`vendor/filament/filament/resources/views/components/layout/index.blade.php:126`) e o
    `simple` (`.../layout/simple.blade.php:61`), que é o das telas de login, registro e
    recuperação de senha. Sem o guard abaixo, a versão exata da instalação apareceria para
    QUALQUER visitante da tela de login — que é entregar o mapa de CVEs aplicáveis a quem ainda
    não autenticou.

    `filament()->auth()->check()` e não `@auth`: o `@auth` consulta o guard DEFAULT, e o kit
    nasce sem `->authGuard()` nos painéis — mas o projeto que nascer dele pode declarar um, e aí
    o `@auth` responderia sobre outro guard que não o do painel. O modo de falhar seria a versão
    aparecendo para visitante, com o diff parecendo correto. A forma do painel não tem esse caso.

    ── Sem utilitária Tailwind ──

    O kit não tem `viteTheme()`, então utilitária que esta blade emitisse não existiria na folha
    compilada e o rodapé sairia sem estilo nenhum, com o teste verde (`.ai/rules/css-filament.md`).
    A classe é do kit e mora em `resources/css/filament/kit.css`. Ela não declara COR de propósito:
    herda a do tema e usa `opacity`, então funciona no claro e no escuro sem um par de regras — e
    sem a armadilha de especificidade do `:where()` que o CHANGELOG da 0.34.2 documenta.
--}}
@php
    $versaoDoSistema = filled(config('app.version')) ? 'v'.config('app.version') : null;
    $versaoDoKit = config('kit.exibir_versao') ? 'kit '.config('kit.version') : null;
    $partes = array_filter([$versaoDoSistema, $versaoDoKit]);
@endphp

@if ($partes !== [] && filament()->auth()->check())
    <div class="kit-versao">{{ implode(' · ', $partes) }}</div>
@endif
