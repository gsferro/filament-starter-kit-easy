# Decisões Arquiteturais — feat/cabecalho-do-painel

<!-- ADR só quando as três valem: difícil de reverter, surpreendente sem contexto, resultado de
     trade-off real. Falta uma → sem ADR; a decisão vira linha em ## Decisões de Desenho do 01. -->

## ADR-01: A composição É a marca do painel (`brandLogo` como `Htmlable`), não um bloco a mais no topbar

**Status**: Aceita
**Data**: 2026-10-05
**Portões**: difícil de reverter ✅ (o markup da marca passa a ser do kit em dois pontos do layout — barra lateral e topbar — e todo projeto que ligar a opção depende dessa posição; trocar depois por um render hook muda onde a marca aparece para quem já configurou) · surpreendente ✅ (quem lê o provider espera que `brandLogo()` devolva uma URL, e ele passa a devolver HTML com texto, separadores e duas imagens) · trade-off ✅ (dentro da barra lateral aberta a composição tem 16,5–20 rem para caber, contra a alternativa de um hook só no topbar, que duplicaria a marca quando a barra está recolhida)

### Contexto

O pedido é "nome do projeto | nome do painel | logo da marca" no lugar onde hoje está a logo. O
Filament desenha a marca em dois lugares: no cabeçalho da barra lateral enquanto ela está aberta, e
no topbar quando ela está recolhida ou no celular — os três painéis do kit são recolhíveis no
desktop. E o componente de logo do Filament aceita `Htmlable` em `brandLogo()`/`darkModeBrandLogo()`:
com um `Htmlable`, ele envolve o HTML numa `div.fi-logo` com a altura da marca e não acrescenta `<img>`
nenhuma. O projeto-3, referência do pedido, usa exatamente esse caminho.

Há um render hook dedicado (`TOPBAR_LOGO_AFTER`/`TOPBAR_START`) que poria o bloco só no topbar.

### Decisão

A composição é devolvida pela Closure de `brandLogo()` dos três painéis, como `HtmlString`, por uma
classe de apoio única (`CabecalhoDoPainel::marca()`); `darkModeBrandLogo()` devolve `null` com a
composição ativa, porque o par claro/escuro da logo já vai dentro dela com as classes nativas
`fi-logo-light`/`fi-logo-dark`. Com nenhum interruptor ligado, as duas Closures devolvem o que
devolviam (a URL da logo, ou `null`) — o default é byte a byte o de hoje.

A composição fica sempre numa linha, no topbar e na barra lateral (onde o Filament só a desenha em tela
estreita, abaixo de 1024 px, com a barra aberta — medido pelo CT-B03); dentro da barra lateral o CSS do
kit encolhe os textos e corta com reticências *(alterado em 2026-10-05: CR-04 do step 9 — a versão
anterior deixava quebrar linha, e a quebra vazava por baixo do cabeçalho da barra, que tem altura fixa)*.

### Alternativas Consideradas

1. **Render hook `TOPBAR_LOGO_AFTER` / `TOPBAR_START` com o bloco, mantendo o `brandLogo` nativo** — descartada: com a barra recolhida o topbar mostraria a logo nativa **e** a composição (que também tem logo); esconder a nativa por CSS é frágil (classe do vendor) e a barra aberta ficaria sem a composição, trocando a identidade ao recolher.
2. **Override da blade `filament-panels::components.logo`** — descartada: publicar uma view do Filament é manter cópia que quebra a cada upgrade (o kit evita isso em todo lugar; ver `css-filament.md`), e o `Htmlable` nativo já faz o que a override faria.
3. **Composição só no topbar, e logo solta na barra lateral** — descartada pela mesma razão da 1: duas marcas diferentes na mesma página.

### Consequências

- **Positivas**: um ponto de decisão (`marca()`), zero view de vendor publicada, o link para a home do painel e a altura da marca continuam nativos, e o swap claro/escuro reaproveita o CSS do Filament já verificado em `feat/logo-dark-mode`.
- **Negativas**: a composição precisa caber na barra lateral; o CSS do kit assume a responsabilidade por tamanho de fonte e truncamento com reticências ali *(alterado em 2026-10-05: era `flex-wrap`, revogado pelo CR-04)*, e a `div.fi-logo` do vendor impõe `height` ao wrapper — a composição ignora a altura no texto e dá altura absoluta às imagens (que também vale fora do Filament, na página de erro do Sentinel — RD-06).
- **Riscos**: o Filament mudar o tratamento de `Htmlable` em `logo.blade.php` — mitigado por um CT que lê a blade do vendor e confere que o ramo `instanceof Htmlable` existe (mesma técnica do CT-35 do menu do usuário, que sobreviveu ao bump 5.7 → 5.8 sem edição).

### Referências

- `CabecalhoDoPainel` (classe de apoio), `IdentidadeDoKit` (fonte das logos), `Paineis` (rótulo do painel)
- ADR-01 de `wikis/specs/feat/logo-dark-mode/` (classes nativas do swap, zero CSS de troca)
- Projeto-3: `CabecalhoProjTEC` — a referência que o solicitante citou

---

## Superfície Livewire

A feature **não cria** página, widget nem componente Livewire. O que o cliente alcança:

| Ponto de entrada | Alcançável por | Fronteira aplicada pelo projeto | Evidência |
|---|---|---|---|
| `$data['cabecalho_nome_do_projeto']`, `['cabecalho_nome_do_painel']`, `['cabecalho_logo_da_marca']`, `['cabecalho_usuario']` (bool) e `['cabecalho_detalhe_do_usuario']` (string) — chaves novas na propriedade pública `$data` da `SettingsPage` existente | `$wire.set('data.cabecalho_detalhe_do_usuario', '<qualquer string>')` entre requests, e o `save()` da página | `save()` exige `View:ConfiguracoesDoKit` (`ExigePermissaoDaTela`); o select é `->required()` com `options()` fechadas (validação `in` nativa do Filament); o valor gravado passa por `DetalheDoUsuario::coagir()` ao chegar à config, então string fora da lista nunca vira comportamento — vira `perfil` | `vendor/filament/spatie-laravel-settings-plugin/src/Pages/SettingsPage.php:save():62`; `app/Filament/Admin/Pages/ConfiguracoesDoKit.php:abaIdentidade():273` |
| Closures de `brandLogo()`, `darkModeBrandLogo()` e do hook `USER_MENU_BEFORE` | **não** são ações Livewire: rodam no render do layout, sem argumento do cliente | n.a. — só leem config, tenant e usuário autenticado | `vendor/filament/filament/resources/views/components/logo.blade.php:$brandLogo:6`; `vendor/filament/filament/resources/views/components/user-menu.blade.php:USER_MENU_BEFORE:43` |
| `public function` nova em Page/Widget | nenhuma | — | `grep -rn "public function " app/Filament/Admin/Pages/ConfiguracoesDoKit.php` — nenhuma nova neste diff (conferido antes do step 9) |
| `public $` sem `#[Locked]` nova | nenhuma | — | idem, `grep -rn "public \$\|public ?" app/Filament app/Livewire \| grep -v Locked` |
| Arrays de estado do framework consumidos (`$filters`, `$tableFilters`, `$tableSearch`…) | nenhum | — | a feature não tem tabela nem filtro |

**Pacote de terceiro**: a feature não monta sobre nenhum (o `spatie-laravel-settings-plugin` já era a base da tela; nenhum model novo é persistido).
