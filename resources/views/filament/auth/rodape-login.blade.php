{{--
    Rodape da tela de login dos tres paineis. Texto configuravel, vindo do ponto unico de
    leitura (App\Support\ConfiguracaoDoLogin) — hoje de config, amanha da tela de Settings.

    Renderiza VAZIO quando nao ha texto, e nao uma faixa vazia: `filled()` trata string de
    espacos como ausente.

    SAIDA ESCAPADA, sempre. O valor vem de campo editavel e esta tela e PUBLICA e NAO
    AUTENTICADA — a sintaxe de saida crua do Blade aqui seria XSS armazenado com o pior
    alcance possivel: a tela por onde todo mundo entra. Se um dia for preciso link no rodape,
    a resposta e um campo estruturado (texto + URL, com validacao de esquema), nunca um campo
    de HTML solto. Ver ADR-09 da wiki login-social-google.

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
        {{ $rodape }}
    </div>
@endif
