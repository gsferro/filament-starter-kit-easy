{{--
    Rodape da tela de login dos tres paineis. Texto configuravel, vindo do ponto unico de
    leitura (App\Support\ConfiguracaoDoLogin) — hoje de config, amanha da tela de Settings.

    Renderiza VAZIO quando nao ha texto, e nao uma faixa vazia: `filled()` trata string de
    espacos como ausente.

    SAIDA POR MARKDOWN, com HTML cru DESCARTADO. O valor vem de campo editavel e esta tela e
    PUBLICA e NAO AUTENTICADA — HTML solto aqui seria XSS armazenado com o pior alcance
    possivel: a tela por onde todo mundo entra. O Markdown (Str::markdown com html_input
    "strip" e allow_unsafe_links false) da negrito, italico e link com esquema seguro, e
    descarta tag e "javascript:". E o campo estruturado que a ADR-09 da wiki
    login-social-google pedia, na forma que o CommonMark ja entrega. Pedido do solicitante na
    validacao real dos provedores (2026-08-26): texto puro nao formatava.

    ponytail: estilo inline, pelo mesmo motivo do botao ao lado. `color:inherit` com opacity
    herda a cor do tema, entao o rodape acompanha claro e escuro sem uma cor fixa.

    -- `<aside>`, e nao `<div>` nem `<footer>` --

    Este elemento e irmao direto de `<body>` numa tela PUBLICA, entao precisa viver num landmark:
    como `<div>` o axe o acusa por `region`. Mas `<footer>` nessa posicao tem `contentinfo`
    implicito, e a assinatura do sistema (`assinatura-do-rodape.blade.php`) ja e um — dois
    `contentinfo` disparam `landmark-no-duplicate-contentinfo` E `landmark-unique`, os dois
    apontando para a ASSINATURA.

    `<aside>` e landmark `complementary`: resolve o `region` sem duplicar nada. As tres formas
    foram MEDIDAS com o axe, e nao deduzidas (achados QA-17 e QA-40 do quality gate):

      <div>     region acusa o recado
      <footer>  region ok, mas 2 regras novas acusam a assinatura
      <aside>   nenhuma das tres acusa nenhum dos dois

    `[CT-B03]` guarda isso, e o ADR-09 de `rodape-coerente` registra a medicao das tres formas.
--}}
@php
    $rodape = \App\Support\ConfiguracaoDoLogin::rodapeDoLogin();
@endphp

@if ($rodape !== null)
    <aside
        class="fi-login-rodape"
        style="margin-top:1.5rem;text-align:center;font-size:.75rem;color:inherit;opacity:.65"
    >
        {!! \Illuminate\Support\Str::markdown($rodape, ['html_input' => 'strip', 'allow_unsafe_links' => false]) !!}
    </aside>
@endif
