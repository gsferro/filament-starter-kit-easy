---
title: "Hub de navegação em cartões"
parent: Recursos
grand_parent: Português
nav_order: 7
---

# Hub de navegação em cartões

Cada painel tem uma página **hub**: uma grade de cartões, um por destino do painel, no lugar da
árvore da barra lateral — para quando a pergunta "onde vejo X?" é real. São três:

| Hub | Painel | URL | Nasce |
|---|---|---|---|
| `HubDeInfraestrutura` | `/infra` | `/infra/hub-de-infraestrutura` | **ligado** — dezesseis destinos em quatro grupos, metade com rótulo de plugin não traduzido |
| `HubDeAdministracao` | `/admin` | `/admin/hub-de-administracao` | desligado |
| `HubDoNegocio` | `/app` | `/app{/org}/hub-do-negocio` | desligado |

A flag é **`KIT_HUB`** (`config/kit.php` → `hub`, default `false`). Ela liga os hubs de `/admin` e
`/app`; desligada, as duas páginas somem do menu, da URL e da busca ⌘K. O `/infra` **não depende
dela**: é o único painel onde a grade ganha da árvore no default, e a assimetria é decisão
registrada (ADR-03 da wiki `hub-de-cards-opcional`). Ligar `KIT_HUB=true` não exige mais nada — o
`FilamentCardsPlugin` já está registrado nos três painéis e o CSS dos cartões já é publicado.

Os cartões saem de `App\Filament\Concerns\DescobreCardsDoPainel`, que varre os resources e páginas
do painel e **filtra por `canAccess()` de cada destino** — quem não alcança a tela não vê o cartão.
O hub soma à barra lateral, não a substitui: nenhum item sai da navegação.

O teste de navegador é `tests/Browser/HubDeCardsTest.php`: a grade do `/infra` **pintada**, com a
descrição dentro do cartão — porque o pacote não registra CSS e sem
`resources/css/filament/cards.css` o HTML é o mesmo e a grade vira lista de links soltos.

