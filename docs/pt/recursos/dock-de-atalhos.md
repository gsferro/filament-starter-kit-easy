---
title: "Dock de atalhos"
description: "O dock é um botão flutuante com os atalhos favoritos de cada usuário: links para telas da aplicação, de qualquer painel, a um clique de distância…"
sidebar:
  order: 9
---
O dock é um **botão flutuante** com os atalhos favoritos de cada usuário: links para telas da
aplicação, de qualquer painel, a um clique de distância (`hoceineel/filament-quick-action-dock`). Ele
é montado em `App\Filament\Dock\PluginDoDock`.

![Animação do dock de atalhos: o botão flutuante abre o leque de favoritos, o slideover Gerenciar atalhos escolhe e reordena destinos e a opção Adicionar esta página favorita a tela atual](https://raw.githubusercontent.com/gsferro/filament-starter-kit-easy/main/art/dock-de-atalhos.gif)

<details>
<summary>A mesma animação em tema escuro</summary>

![Animação do dock de atalhos em tema escuro: o dock acompanha o tema do painel](https://raw.githubusercontent.com/gsferro/filament-starter-kit-easy/main/art/dock-de-atalhos-escuro.gif)

</details>

## Para quem

Só quem tem a permissão **`Usar:Dock`**, concedida a papéis na tela de papéis. Sem ela o dock não
renderiza. Numa instalação nova, os papéis de administração dos três painéis a recebem e o
`panel_user` não — o administrador a concede pela tela de papéis.

## Onde aparece

Nos painéis marcados em *Painéis onde o dock aparece*, na aba **Kit** de
`/admin/configuracoes-da-aplicacao`. Numa instalação nova **nenhum** painel está marcado: o dock não
aparece em lugar nenhum até alguém ligá-lo. Veja as opções em [Configuração](#configuração).

O dock e o chat do assistente **não aparecem** na tela de bloqueio (`/screen/lock`), nas telas de
login (de cada painel e unificado, inclusive a escolha de painel) nem nas telas que usam o layout do
Auth Designer (recuperar senha, cadastro, registro por convite, dois fatores): numa sessão
bloqueada, eles entregariam os favoritos e os títulos das conversas a quem estivesse diante da tela.
Ao destravar, os dois voltam.

## Usar o dock

1. Clique no botão flutuante: os atalhos abrem **agrupados por painel**, cada grupo com o nome do painel como separador, e na ordem que você escolheu dentro de cada grupo. Com o multi-tenant ligado, os favoritos do painel da aplicação ficam **um grupo por organização**, com o separador "{painel} · {organização}" — mesmo quando há uma organização só —, na ordem em que cada organização apareceu entre os seus favoritos.
2. Passe o mouse sobre um favorito para ver, no tooltip, o caminho para onde ele leva.
3. Clique num favorito para ir à tela dele.

| Layout | Como se apresenta |
|---|---|
| Leque (speed dial) | os atalhos abrem em leque a partir do botão |
| Barra | os atalhos ficam numa barra |

| Tema claro | Tema escuro |
|---|---|
| [![Dock aberto no painel admin em tema claro, com três favoritos de painéis diferentes e o grupo Atalhos](https://raw.githubusercontent.com/gsferro/filament-starter-kit-easy/main/art/thumbs/dock-aberto.png)](https://raw.githubusercontent.com/gsferro/filament-starter-kit-easy/main/art/dock-aberto.png) | [![Dock aberto no painel admin em tema escuro, com superfície e rótulos escuros](https://raw.githubusercontent.com/gsferro/filament-starter-kit-easy/main/art/thumbs/dock-aberto-escuro.png)](https://raw.githubusercontent.com/gsferro/filament-starter-kit-easy/main/art/dock-aberto-escuro.png) |

## Gerenciar atalhos

A opção **Gerenciar atalhos** do dock abre um **slideover**. Nele o usuário:

- escolhe destinos do **catálogo** — as telas que ele pode abrir, geradas sozinhas, sem lista mantida à mão;
- **reordena** os escolhidos: a ordem dele vale dentro do grupo de cada painel no dock;
- remove os que não quer mais;
- troca o **layout** do dock, só dele.

Cada destino traz o **painel** — e, no painel de organização, a **organização** — antes do grupo e
da tela, para distinguir telas de mesmo nome.

| Regra | O que acontece |
|---|---|
| Limite de 10 | com 10 gravados, enviar mais é recusado com aviso do limite e nada muda |
| Sem duplicata | o mesmo destino não entra duas vezes |
| Envio inválido | um envio com chave fora do catálogo é recusado por inteiro, sem gravar nada |
| Só destinos | um favorito é sempre um destino do catálogo — a lista de um Resource ou uma Page avulsa —, nunca uma URL digitada |

| Tema claro | Tema escuro |
|---|---|
| [![Slideover Gerenciar atalhos em tema claro, com os escolhidos rotulados por painel e organização e o catálogo aberto](https://raw.githubusercontent.com/gsferro/filament-starter-kit-easy/main/art/thumbs/dock-gerenciar.png)](https://raw.githubusercontent.com/gsferro/filament-starter-kit-easy/main/art/dock-gerenciar.png) | [![Slideover Gerenciar atalhos em tema escuro](https://raw.githubusercontent.com/gsferro/filament-starter-kit-easy/main/art/thumbs/dock-gerenciar-escuro.png)](https://raw.githubusercontent.com/gsferro/filament-starter-kit-easy/main/art/dock-gerenciar-escuro.png) |

## Adicionar esta página

A outra opção do dock favorita a tela em que o usuário está.

- **Onde aparece:** na lista de um Resource ou numa Page avulsa. Em tela de criar, editar ou ver registro **não** aparece.
- **Onde não aparece:** quando a página já é favorita.
- **Onde entra:** no **fim** da lista (e, no dock, no fim do grupo do painel dela), sem mudar a ordem dos demais.
- **Quando não dá:** se a página já não pode ser adicionada — por exemplo, com 10 gravados —, clicar mostra um **aviso** em vez de não fazer nada.

## Favoritos entre painéis e organizações

- Um favorito de um painel aparece no dock de **qualquer painel** em que o dock esteja ligado, enquanto o usuário puder acessar aquele painel e aquela tela.
- O favorito do painel de organização guarda a organização: **abre nela** e some do dock se o usuário perder o acesso a ela.
- No painel de organização, as telas novas oferecidas no catálogo são só as da organização aberta.
- Em qualquer painel, os favoritos já gravados que o usuário ainda pode abrir aparecem entre os escolhidos do slideover, com o rótulo de painel e organização, e podem ser removidos ou reordenados.

**Acesso perdido.** Se o usuário perde o acesso ao destino (permissão, painel ou vínculo com a
organização), o favorito — **favorito oculto** — some do dock e do slideover, mas continua gravado:
volta se o acesso voltar, é preservado quando o usuário salva o slideover e conta no teto de 10
.

**Organização excluída.** O favorito de uma organização que não existe mais é descartado quando o
usuário salva o slideover, assim como a chave que nunca foi destino.

| Tema claro | Tema escuro |
|---|---|
| [![Dock no layout barra, em tema claro, no rodapé do painel admin](https://raw.githubusercontent.com/gsferro/filament-starter-kit-easy/main/art/thumbs/dock-barra.png)](https://raw.githubusercontent.com/gsferro/filament-starter-kit-easy/main/art/dock-barra.png) | [![Dock no layout barra, em tema escuro](https://raw.githubusercontent.com/gsferro/filament-starter-kit-easy/main/art/thumbs/dock-barra-escuro.png)](https://raw.githubusercontent.com/gsferro/filament-starter-kit-easy/main/art/dock-barra-escuro.png) |

## Layout e estilo

O **layout** e o **estilo** padrão são definidos nas configurações do kit. O usuário troca só o
**layout**, no slideover; o estilo vale para todos.

| Layout (valor) | Rótulo |
|---|---|
| `speed-dial` | Leque (speed dial) |
| `bar` | Barra |

| Estilo (valor) | Rótulo |
|---|---|
| `native` | Nativo |
| `outline` | Contorno |
| `contrast` | Contraste |
| `tinted` | Tingido |

## Junto dos outros botões flutuantes

O canto inferior direito também tem o **"Voltar ao topo"** (em toda tela rolada dos painéis) e, no
painel da aplicação, o **chat do assistente**. Quando o dock fica nesse canto, ou é solto por cima
do lugar desses botões, os três formam uma pilha:

- o dock fica onde o usuário o pôs;
- o chat sobe para logo acima do dock, e o "Voltar ao topo" para acima do chat;
- os três ficam centralizados no mesmo eixo vertical, com **16 px** entre um e outro;
- com o leque do dock **aberto**, o chat e o "Voltar ao topo" somem até o dock fechar;
- no layout **barra**, eles continuam visíveis, empilhados acima da barra.

Com o dock em outro canto, ou longe desses botões, nada muda: cada um fica na posição de sempre.

## Tema escuro

O dock acompanha o tema do painel, sem nenhuma configuração: quando o painel está em modo escuro, a superfície do dock, os itens e o slideover usam as cores escuras, inclusive quando o tema escuro é escolhido no próprio painel e o sistema operacional está em modo claro. As quatro opções de estilo têm variante escura. As imagens desta página mostram os dois temas lado a lado.

## Configuração

Na aba **Kit** de `/admin/configuracoes-da-aplicacao`, seção *Dock de atalhos*:

| Opção | O que define | Nasce |
|---|---|---|
| **Painéis onde o dock aparece** | os painéis (admin, app, infra) em que o dock aparece | **nenhum** |
| **Layout padrão** | `Leque (speed dial)` ou `Barra`; cada usuário pode trocar o dele | `Leque (speed dial)` |
| **Estilo** | `Nativo`, `Contorno`, `Contraste` ou `Tingido`; vale para todos | `Nativo` |

A lista de painéis nasce da variável `KIT_DOCK_PAINEIS` (ids separados por vírgula, em
`config/kit.php` → `dock.paineis`), vazia no `.env.example`. **Vazia significa nenhum painel.** Layout
e estilo não têm variável. Como no restante da aba, **o banco vence o `.env`**. Quem vê o dock
continua sendo definido pela permissão `Usar:Dock`, não por esta seção. Mais detalhes em
[Configurações do kit](../configuracoes-do-kit/).

[![Aba Kit das configurações da aplicação, seção Dock de atalhos, com painéis, layout padrão e estilo](https://raw.githubusercontent.com/gsferro/filament-starter-kit-easy/main/art/thumbs/admin-configuracoes-dock.png)](https://raw.githubusercontent.com/gsferro/filament-starter-kit-easy/main/art/admin-configuracoes-dock.png)

## Custo por requisição

Ler o settings **não acrescenta consulta ao banco por request** para decidir se o dock aparece nem
como ele aparece. Por favorito, a única consulta admitida é a da autorização da própria
tela (`Gate::before` → `User::isMasterGlobal()`), a mesma de cada item da barra lateral.

## Perguntas frequentes

**O dock não aparece.** Confira, nesta ordem: (1) o painel está marcado em *Painéis onde o dock
aparece*? Nenhum vem marcado de fábrica; (2) o seu papel tem a permissão `Usar:Dock`? O
`panel_user` não a recebe de fábrica.

**Sumiu um favorito.** Ele é um favorito oculto: você perdeu o acesso ao painel, à tela ou à
organização dele. Continua gravado e volta se o acesso voltar. Se era de uma organização excluída,
ele é descartado ao salvar o slideover.

**Não consigo adicionar mais.** O limite é 10 favoritos por usuário, contando os ocultos; remova um
no slideover.

**Não vejo "Adicionar esta página".** Só aparece na lista de um Resource ou numa Page avulsa, e não
quando a página já é favorita.

## Fora de escopo

O dock não executa código (formulário, modal, confirmação): ele só abre URLs de destinos. A posição
e o arrastar do dock já são guardados por usuário no navegador pelo pacote.
