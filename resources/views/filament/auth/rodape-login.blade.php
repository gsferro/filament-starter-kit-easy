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
--}}
@php
    $rodape = \App\Support\ConfiguracaoDoLogin::rodapeDoLogin();
@endphp

@if ($rodape !== null)
    <div
        class="fi-login-rodape"
        style="margin-top:1.5rem;text-align:center;font-size:.75rem;color:inherit;opacity:.65"
    >
        {!! \Illuminate\Support\Str::markdown($rodape, ['html_input' => 'strip', 'allow_unsafe_links' => false]) !!}
    </div>
@endif
