{{--
    Botoes de login social, abaixo do formulario de login dos tres paineis.

    Chega aqui pelo render hook AUTH_LOGIN_FORM_AFTER, registrado uma unica vez em
    App\Providers\KitServiceProvider::configureTelaDeLogin() e SEM escopo de painel — uma
    registracao cobre os tres. Ver ADR-05 da wiki login-social-google.

    A lista vem de App\Support\ConfiguracaoDoLogin::disponiveis(), que devolve so os provedores
    com o interruptor ligado E as tres credenciais preenchidas. Nenhum provedor disponivel
    renderiza VAZIO — nem o divisor "ou" — e nao um bloco escondido: o botao e a unica porta do
    login social, e presenca no HTML com display desligado seria uma affordance para um caminho
    que responde 404.

    Na tela de REGISTRO (hook do formulario de registro, registrado no mesmo metodo do provider)
    o link carrega o `org` e o `token` da query corrente ate o `redirect`, que os guarda na sessao
    para a volta do OAuth — e assim o cadastro pelo provedor cai na organizacao certa e aceita o
    convite. No login e na tela de bloqueio nao ha query, e o link sai limpo. Ver a wiki
    cadastro-social-por-convite-e-organizacao.

    Este arquivo substituiu o botao-google.blade.php. Um blade especifico ao lado de um generico
    seria a segunda fonte da verdade que o kit ja pagou uma vez — ver .ai/rules/config.md, "uma
    pergunta, uma dona". Ver ADR-08 da wiki mais-provedores-sociais.

    Os icones sao SVG inline, trazidos por inclusao de resources/views/filament/auth/icones/.
    Heroicons nao tem logo de marca, e o kit nao ganha um pacote de ~3.000 icones para usar
    quatro. Ver ADR-04 da wiki login-social-google e ADR-08 desta.

    ATENCAO ao editar: comentario de blade NAO protege diretiva. Nunca escreva o nome de uma
    diretiva com arroba aqui dentro — o compilador a processa antes de remover o comentario, e a
    mencao vira codigo no arquivo compilado, derrubando as tres telas de login com ParseError.
    No comentario, "por inclusao". Ver .ai/rules/views.md.

    ponytail: estilo inline em vez de uma regra nova em resources/css/filament/kit.css. Poucas
    propriedades nao justificam um arquivo de CSS mais um `php artisan filament:assets` no fluxo
    de quem instala o kit. currentColor com opacity segue o tema claro/escuro sozinho, sem uma
    variavel de cor escrita a mao. Teto conhecido: se o bloco crescer, ele vira uma regra
    escopada em .fi-login-social naquele arquivo.
--}}
@php
    /*
        O PAINEL CORRENTE decide quais provedores aparecem, e ele viaja na URL do botao ate o
        controller. Provedor liberado so no /admin nao renderiza na tela de login do /app.

        `getCurrentPanel()?->getId()` e nao uma string fixa: este blade e o mesmo nos tres
        paineis, injetado pelo render hook do KitServiceProvider. Nulo (fora de painel) cai no
        comportamento anterior a esta feature -- lista vazia significa todos.

        O `painel` entra por ULTIMO na query, depois de `org` e `token`. Nao e estilo: o caso
        `it carrega org e token da tela de registro no link do botao` afirma o prefixo
        `auth/google/redirect?org=acme`, e por o painel na frente quebraria uma assercao que ja
        existia sem que nada no comportamento tivesse mudado.

        Ver wikis/specs/feat/login-social-por-painel/login-social-por-painel/.
    */
    $painelCorrente = \Filament\Facades\Filament::getCurrentPanel()?->getId();
    $provedores     = \App\Support\ConfiguracaoDoLogin::disponiveis($painelCorrente);
@endphp

@if ($provedores !== [])
    <div class="fi-login-social" style="margin-top:1.5rem">
        {{-- Divisor com rotulo, UMA vez. A linha usa currentColor para acompanhar o tema. --}}
        <div style="display:flex;align-items:center;gap:.75rem;margin-bottom:1rem" aria-hidden="true">
            <span style="flex:1;border-top:1px solid currentColor;opacity:.15"></span>
            <span style="font-size:.75rem;opacity:.6">ou</span>
            <span style="flex:1;border-top:1px solid currentColor;opacity:.15"></span>
        </div>

        <div style="display:flex;flex-direction:column;gap:.5rem">
            @foreach ($provedores as $provedor)
                <x-filament::button
                    tag="a"
                    :href="route('auth.social.redirect', array_filter(['provedor' => $provedor->value, 'org' => request()->query('org'), 'token' => request()->query('token'), 'painel' => $painelCorrente], 'is_string'))"
                    color="gray"
                    size="lg"
                    outlined
                    :aria-label="'Entrar com ' . $provedor->rotulo()"
                    style="width:100%"
                >
                    @include('filament.auth.icones.' . $provedor->icone())
                    Entrar com {{ $provedor->rotulo() }}
                </x-filament::button>
            @endforeach
        </div>
    </div>
@endif
