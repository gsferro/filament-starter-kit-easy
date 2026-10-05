{{--
    O bloco do usuário no cabeçalho: nome e, abaixo, o e-mail ou o papel no painel corrente.
    Renderizado por `App\Support\CabecalhoDoPainel::usuario()` no render hook `USER_MENU_BEFORE`,
    que o Filament emite na topbar, à esquerda do avatar e FORA do dropdown — o menu nativo
    continua inteiro (P-09).

    Só classes do kit (`kit-usuario*`, em `resources/css/filament/kit.css`); é lá que o bloco
    some abaixo de 768 px (P-04). Chaves duplas sempre: nome e e-mail são texto de terceiro.
--}}
<span class="kit-usuario">
    <span class="kit-usuario__nome" title="{{ $nome }}">{{ $nome }}</span>
    @if (filled($detalhe))
        <span class="kit-usuario__detalhe" title="{{ $detalhe }}">{{ $detalhe }}</span>
    @endif
</span>
