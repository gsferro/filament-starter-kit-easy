{{-- Arte padrão das telas de autenticação, embutida como data URI por `IdentidadeDoKit::arteDoLogin()`.

     É uma view, e não um arquivo em `public/`, porque precisa carregar um valor de runtime: o nome da
     aplicação. Quem tem marca própria envia a imagem em `/admin/configuracoes-do-kit` e nunca chega aqui.

     O nome sai por `{{ }}`, que escapa `&`, `<` e `>` — sem isso um `APP_NAME` como "Silva & Cia"
     invalidaria o XML e a tela ficaria sem arte. --}}
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 800 1000" preserveAspectRatio="xMidYMid slice">
  <defs>
    <linearGradient id="bg" x1="0" y1="0" x2="1" y2="1">
      <stop offset="0" stop-color="#0f172a"/>
      <stop offset="0.55" stop-color="#1e3a5f"/>
      <stop offset="1" stop-color="#0e7490"/>
    </linearGradient>
    <radialGradient id="glow" cx="0.7" cy="0.25" r="0.6">
      <stop offset="0" stop-color="#22d3ee" stop-opacity="0.35"/>
      <stop offset="1" stop-color="#22d3ee" stop-opacity="0"/>
    </radialGradient>
  </defs>
  <rect width="800" height="1000" fill="url(#bg)"/>
  <rect width="800" height="1000" fill="url(#glow)"/>
  <g fill="none" stroke="#67e8f9" stroke-opacity="0.25" stroke-width="1.5">
    <circle cx="640" cy="220" r="140"/>
    <circle cx="640" cy="220" r="200"/>
    <circle cx="640" cy="220" r="260"/>
    <circle cx="160" cy="820" r="120"/>
    <circle cx="160" cy="820" r="180"/>
  </g>
  <g fill="#e0f2fe">
    <text x="80" y="512" font-family="ui-sans-serif, system-ui, sans-serif" font-size="44" font-weight="700">{{ $nome }}</text>
  </g>
</svg>
