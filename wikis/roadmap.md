# Roadmap — o que ainda não entrou no kit, e por quê

> **Este documento é sobre o futuro do KIT, não sobre o do seu projeto.**
>
> Ele viaja junto com o `composer create-project` de propósito, pela mesma razão que os outros
> `wikis/*.md`: é material de trabalho de quem instala. Serve para você saber **o que o kit já
> olhou e decidiu adiar** — e assim não gastar uma tarde reavaliando do zero algo que já tem
> medição registrada aqui.
>
> Nada nesta página é compromisso de data. Depois do `create-project`, o projeto é seu: se um item
> daqui for urgente para você, implementá-lo no seu projeto é legítimo e não conflita com nada.

Companheiro de [`pacotes-candidatos.md`](pacotes-candidatos.md), que responde à mesma pergunta para
**pacotes**. Este responde para **capacidades**.

---

## 1. Preferências de usuário — densidade, fonte, cor e modo do menu

**Estado**: analisado, não implementado · **Origem**: `wikis/specs/feat/layout-compact/`

Hoje a densidade do layout é uma configuração **da instalação**: quem administra escolhe em
`/admin` → Configurações → Kit, e vale para todo mundo. O passo seguinte natural é a escolha ser
**de cada pessoa**, guardada no perfil:

| Preferência | Situação hoje | O que falta |
|---|---|---|
| **Densidade do layout** | existe, por instalação | mover a leitura para o usuário logado, com a da instalação como padrão |
| **Tamanho da fonte** | não existe | mesma técnica da densidade — o Tailwind 4 do Filament deriva tudo de `--text-*` |
| **Cor primária** | existe por instalação e por organização | falta o nível do usuário |
| **Modo de ocultação do menu** (esconder tudo × manter o ícone, que é o padrão hoje) | não existe | é a única das quatro que **não** sai de variável CSS |

**Por que não entrou junto com a densidade.** A densidade custou uma declaração CSS porque já havia
onde guardá-la (o settings do kit) e onde lê-la (um render hook avaliado por request). Preferência
por usuário precisa de **tabela, tela de perfil e leitura por sessão** — é uma feature inteira, não
o mesmo trabalho com outro sujeito.

**A hipótese que vale registrar**: isto é candidato natural a **pacote Filament externo**. Ele
nasceria no kit, mas nada nele depende do kit — e um pacote de "preferências de aparência por
usuário" é útil para qualquer painel Filament, podendo ser evoluído por terceiros. Se for por esse
caminho, o kit passa a consumir o pacote em vez de manter o código.

---

## 2. O tema compacto oficial e pago do Filament

**Estado**: estudado e **recusado para o kit** — mas pode ser a escolha certa para o **seu** projeto

[`filament/compact-theme`](https://filamentphp.com/plugins/filament-compact-theme), US$ 29, da
própria Filament. Medido por diff dos bundles da demo oficial:

| | tema pago | o que o kit faz |
|---|---|---|
| Ganho na altura da tabela | **−22,5%** | −16,8% no nível `compacto`, **−22,9%** no `denso` |
| Como | 548 blocos de regra sobre 370 classes `fi-*` | **uma** declaração de `--spacing` |
| Distorce ícone e fonte? | **não** — não mexe em `--spacing` nem `--text-*` | **sim** — ícones e inputs encolhem junto |
| Liga/desliga em runtime? | **não** — é build-time, exige `viteTheme()` + `npm run build` | **sim** |

**O que o desqualifica para o kit não é o preço, é a licença**: ela é de projeto único. Um starter
kit é distribuído, então embarcá-lo obrigaria **cada instalação** a comprar — a mesma família do
veto que `.ai/rules/general.md` já registra para o `filament/blueprint`.

**Para um projeto único, a conta muda.** Se a distorção de proporção do nível `denso` incomoda e
você quer o ganho sem ela, o tema pago é a melhor compra disponível, e a análise acima existe para
você decidir com número em vez de intuição. Ele exige migrar o painel para `viteTheme()` — ver o
item 4.

---

## 3. Densidade nativa do Filament — o gatilho que aposenta o item

**Estado**: aguardando o upstream · **É desejável que aconteça**

O Filament 5 **não** tem densidade global. Ele tem `compact()` **por componente**
(`Section`, `EmptyState`, `Repeater`), e `Table` não tem nem isso. Foi por isso que o kit resolveu
em CSS.

Se o Filament ganhar densidade de painel — algo como um `Panel::densely()` —, a implementação atual
do kit vira dívida: CSS mantido à mão onde existiria API. **O gatilho de reavaliação é esse**, e a
ação é trocar a declaração de `--spacing` pela API nativa, mantendo os mesmos três níveis na tela
de configurações para não quebrar quem já escolheu.

Vale dizer o lado bom: como o kit **não escreve nenhuma classe `fi-*`**, essa troca é local. Fosse
uma lista congelada de seletores de vendor, seria uma migração.

---

## 4. Armadilha conhecida: migrar para `viteTheme()` desliga o botão em silêncio

**Estado**: não é tarefa, é aviso para quem for mexer

O interruptor de densidade funciona porque é um **render hook**, avaliado a cada request. Se alguém
migrar o kit (ou o seu projeto) para um tema Vite customizado e mover a densidade para lá, o
`viteTheme()` é resolvido no **registro do painel** e **não aceita `Closure`**: a configuração
passa a ser lida uma vez, no boot.

O sintoma é o pior possível — a tela **grava** a escolha, não dá erro, e **nada muda** até o
próximo deploy. É exatamente o modo de falha que `.ai/rules/settings.md` já registra por outro
caso do kit.

Quem migrar para `viteTheme()` precisa reintroduzir a densidade por render hook, ou aceitar que ela
vira decisão de build.

---

## 5. Superfícies que a densidade não alcança

**Estado**: medido, documentado, sem plano

Estes três não encolhem, e não é defeito — é onde o valor não sai de `--spacing`:

| Superfície | Por que não responde | O que exigiria |
|---|---|---|
| **Altura da topbar** (64 px nos três níveis) | altura fixa, não derivada de variável | uma variável nova no layout base do Filament — não há API hoje |
| **Rail do menu colapsado** (`--collapsed-sidebar-width`, 72 px nos três) | **decisão, não limitação**: `collapsedSidebarWidth()` aceita `Closure` e daria para governar igual à largura normal | nada — foi deixado de fora de propósito, ver abaixo |
| **Cartões do Pulse** (`/infra/pulse`, 128 px nos três) | o Pulse carrega CSS própria | escrever regra mirando o Pulse — o tipo de acoplamento a vendor que o kit evita. As **tabelas** do Pulse apertam normalmente |

> **O rail colapsado é o único item desta página que está fora por escolha, e não por limitação.**
> Decidido com o usuário em 2026-09-21: o rail é *icon-only*, então a largura dele é ditada pelo
> **alvo de clique**, não por densidade de conteúdo. Encolher os 72 px espremeria o alvo sem ganhar
> área útil — e o ícone dentro dele **já** encolhe sozinho (24 → 19,2 → 16,8 px), porque sai de
> `--spacing`. Fica registrado com o motivo para não ser reaberto como se fosse esquecimento.
>
> **A largura do menu saiu desta lista.** Ela estava aqui como "candidata mais provável a entrar
> primeiro", e entrou: o quality gate apontou que deixá-la de fora violava o invariante do
> requisito — o menu é uma das quatro superfícies do escopo, e apertar só a altura é a "meia tela
> compacta" que ele proíbe. Hoje a largura acompanha os três níveis (320 → 272 → 264 px), por
> `Panel::sidebarWidth()` com `Closure`, avaliado por request.

---

## Como este documento é mantido

Item entra aqui quando uma decisão **registrada** o empurrou para depois — com o motivo e, quando
houver, o número que sustentou a decisão. Item sai daqui quando é implementado (e vira linha do
`CHANGELOG.md`) ou quando é recusado em definitivo (e vira linha de
[`pacotes-candidatos.md`](pacotes-candidatos.md), se for pacote).

Item sem motivo registrado não é roadmap, é lista de desejos — e lista de desejos envelhece sem
que ninguém perceba.
