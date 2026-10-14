---
title: "Hub de navegação em cartões"
description: "Cada painel tem uma página hub: uma grade de cartões, um por destino do painel, no lugar da árvore da barra lateral — para quando a pergunta \"onde vejo X?\"…"
sidebar:
  order: 7
---
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


## Dock de atalhos

O dock é um botão flutuante com os **atalhos favoritos de cada usuário**: links para telas da
aplicação (`hoceineel/filament-quick-action-dock`). Ele é montado em
`App\Filament\Dock\PluginDoDock`.

**Quem vê.** Só quem tem a permissão **`Usar:Dock`**, concedida a papéis na tela de papéis. Sem
ela o dock não renderiza. Numa instalação nova, os papéis de administração dos três painéis a
recebem e o `panel_user` não — o administrador a concede pela tela de papéis.

**Onde aparece.** Nos painéis marcados em *Painéis onde o dock aparece*, na aba **Kit** de
`/admin/configuracoes-da-aplicacao`. Numa instalação nova **nenhum** painel está marcado: o dock
não aparece em lugar nenhum até alguém ligá-lo. Veja as opções em
[Configurações do kit](../configuracoes-do-kit/).

**O que é um favorito.** Um destino da aplicação — a lista de um Resource ou uma Page avulsa —
nunca uma URL digitada. O catálogo de destinos é gerado sozinho, a partir das telas que o usuário
pode abrir; não há lista mantida à mão. Cada usuário guarda **até 10** favoritos, de qualquer
painel, e o favorito de um painel aparece no dock de qualquer painel em que o dock esteja ligado.
Com 10 gravados, adicionar outro é recusado com aviso do limite e nada muda; o mesmo destino não
entra duas vezes.

**Gerenciar.** A opção **Gerenciar atalhos** do dock abre um slideover, onde o usuário escolhe
os destinos, remove e **reordena** — a ordem do dock é a que ele escolhe. Cada destino traz o
painel (e, no painel de organização, a organização) antes do grupo e da tela. No painel de
organização, as telas novas oferecidas são só as da organização aberta; os favoritos já
gravados que o usuário ainda pode abrir aparecem entre os escolhidos em qualquer painel, com o
rótulo de painel e organização, e podem ser removidos ou reordenados. Um envio com chave fora do
catálogo é recusado por inteiro, sem gravar nada.

**Adicionar esta página.** A outra opção do dock favorita a tela em que o usuário está, e o
destino entra **no fim** da lista, sem mudar a ordem dos demais. Ela só aparece na lista de um
Resource ou numa Page avulsa — não em tela de criar, editar ou ver registro — e não aparece
quando a página já é favorita.

**Acesso perdido.** O favorito cujo destino o usuário deixou de poder abrir some do dock, mas
continua gravado e volta se o acesso voltar; ele não aparece no slideover, é preservado quando o
usuário salva e conta no teto de 10. O favorito do painel de organização guarda a organização,
abre nela e some do dock se o usuário perder o acesso a ela.

**Layout e estilo.** O layout e o estilo padrão são definidos no settings. O usuário pode trocar
só o **layout**, no próprio slideover; o estilo vale para todos.
