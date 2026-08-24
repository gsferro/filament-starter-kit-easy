{--
    Icone de marca do LinkedIn, SVG inline. Ver ADR-08 da wiki mais-provedores-sociais: Heroicons
    nao tem logo de marca, e o kit nao ganha um pacote de icones para usar quatro.

    A marca e monocromatica, entao o fill e currentColor: o icone acompanha tema claro e escuro
    sozinho.

    O provedor por tras deste botao e o driver `linkedin-openid` do Socialite, nao o `linkedin`
    legado — so o OpenID devolve `email_verified`. Ver ADR-03.
--}
<svg data-provedor="linkedin-openid" viewBox="0 0 24 24" width="18" height="18" fill="currentColor" aria-hidden="true" focusable="false">
    <path d="M20.45 20.45h-3.55v-5.57c0-1.33-.02-3.04-1.85-3.04-1.85 0-2.13 1.45-2.13 2.94v5.67H9.36V9h3.41v1.56h.05c.47-.9 1.63-1.85 3.36-1.85 3.6 0 4.27 2.37 4.27 5.45v6.29zM5.34 7.43a2.06 2.06 0 1 1 0-4.12 2.06 2.06 0 0 1 0 4.12zm1.78 13.02H3.56V9h3.56v11.45zM22.22 0H1.77C.79 0 0 .77 0 1.73v20.54C0 23.22.79 24 1.77 24h20.45c.98 0 1.78-.78 1.78-1.73V1.73C24 .77 23.2 0 22.22 0z"/>
</svg>
