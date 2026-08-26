{{--
    Icone de marca do X (antigo Twitter), SVG inline. Ver ADR-08 da wiki mais-provedores-sociais: Heroicons
    nao tem logo de marca, e o kit nao ganha um pacote de icones para usar quatro.

    A marca e monocromatica, entao o fill e currentColor: o icone acompanha tema claro e escuro
    sozinho.

    O driver e o `x` do Socialite (OAuth 2 com PKCE), nao o `twitter` OAuth 1.0 — aquele nao
    poe o e-mail nem no payload bruto, e a barreira de verificacao nao teria onde encostar.
--}}
<svg data-provedor="x" viewBox="0 0 24 24" width="18" height="18" fill="currentColor" aria-hidden="true" focusable="false">
    <path d="M18.9 1.15h3.68l-8.04 9.19L24 22.85h-7.4l-5.8-7.58-6.63 7.58H.49l8.6-9.83L0 1.15h7.59l5.44 7.19 5.87-7.19zm-1.29 19.5h2.04L6.48 3.24H4.29l13.32 17.41z"/>
</svg>
