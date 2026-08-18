# Decisões Arquiteturais — Enriquecimento do kit para a versão 1.0

## ADR-01: `USER_MENU_PROFILE_BEFORE` como ponto de injeção

**Status**: Aceita
**Data**: 2026-08-18

### Contexto

O kit já tem uma decisão registrada e testada sobre render hook de user menu: o gatilho ⌘K da busca
usa `PanelsRenderHook::GLOBAL_SEARCH_BEFORE` **porque `USER_MENU_BEFORE` foi rejeitado** — ele
renderiza *dentro* do dropdown, e o gatilho precisava ficar na topbar (documentado em
`wikis/convencoes.md:166`, `README.md:699` e `CHANGELOG.md:1185`).

Agora a exigência é a oposta: o cabeçalho de identidade **precisa** ficar dentro do dropdown. Existe o
risco real de alguém ler a nota antiga e "corrigir" o hook novo achando que é o mesmo erro.

### Decisão

Usar `PanelsRenderHook::USER_MENU_PROFILE_BEFORE`, que emite exatamente antes do item "Meu perfil",
dentro do dropdown. É o mesmo hook que o `mini-pff` usa nos quatro painéis dele.

E deixar, no comentário acima do hook em cada PanelProvider, a nota de que renderizar dentro do
dropdown — motivo da rejeição do outro hook — é aqui a razão de adotar este.

### Alternativas Consideradas

1. **`USER_MENU_BEFORE`** — descartada: renderiza antes do *botão* do menu, na topbar, não dentro do
   dropdown. Foi exatamente o que o kit já mediu e rejeitou para o gatilho ⌘K, pelo motivo inverso.
2. **Publicar a view de vendor do user menu** (`resources/views/vendor/filament/components/dropdown/...`)
   — descartada: o kit já tem 10 pacotes com views publicadas, e cada uma é superfície de manutenção
   num upgrade de major. Render hook é contrato público do Filament; view publicada é cópia congelada
   de código de terceiro.
3. **`userMenuItems()` com um item não-clicável** — descartada: item de menu é `Action`, tem estado de
   hover, foco e navegação por teclado. Um cabeçalho informativo que se comporta como botão é armadilha
   de acessibilidade, não economia.

### Consequências

- **Positivas**: zero view de vendor publicada; o mesmo hook serve os três painéis sem ramificação;
  o upgrade do Filament não quebra a feature enquanto o hook existir.
- **Negativas**: o conteúdo só é visível com o dropdown aberto — logo o cenário "está visível ao
  clique" exige navegador (ver ADR-06).
- **Riscos**: se o Filament remover o hook num major, a feature some silenciosamente (sem erro).
  Mitigação: CT-01 a CT-03 verificam a presença do cabeçalho no HTML de cada painel — a remoção do
  hook derruba os três.

### Referências

- `app/Providers/Filament/AdminPanelProvider.php:199-212` (o bloco de comentário do hook irmão)
- `wikis/convencoes.md:166`
- Origem: `mini-pff` — `app/Providers/Filament/AdminPanelProvider.php` (4 painéis, mesmo hook)

---

## ADR-02: `papelDoPainel()` no model, não na view

**Status**: Aceita
**Data**: 2026-08-18

### Contexto

O badge precisa responder "com que papel este usuário está aqui". A resposta depende de três coisas:
`master_global` vence antes de tudo, o papel é lido pela coluna `roles.painel`, e a consulta tem de
ignorar o filtro de `team_id` que o spatie injeta quando `permission.teams` está ligado.

O `mini-pff` resolve isso **dentro do Blade**, com um `@php` que monta o enum de perfil a partir do
tenant. Funciona lá porque o `PerfilGerencial` é um enum de domínio com `label()` e `icon()` próprios.

### Decisão

Colocar a regra em `App\Models\User::papelDoPainel(string $painel): ?string`, ao lado de
`temPapelDoPainel()`, e deixar a view com uma chamada só.

A regra da rule de projeto `.ai/rules/filament.md` §2 já diz isto para outro caso: *"asserção de
identidade vive no model, não na query da tela — policy não cobre job/comando"*. O mesmo argumento
vale aqui pelo lado do teste: regra em Blade só se testa renderizando página; regra em model se testa
direto.

### Alternativas Consideradas

1. **`@php` no Blade, como no `mini-pff`** — descartada: a lógica ficaria fora do alcance de PHPStan e
   de teste unitário, e o kit não tem o enum de domínio que torna a versão de lá curta.
2. **View Composer** — descartada: indireção sem ganho. Um composer para uma view que já pode
   consultar o próprio model é camada por camada.
3. **Accessor `getPapelAtualAttribute()`** — descartada: accessor sem argumento não sabe de qual painel
   se fala, e o kit tem três. Precisaria ler `Filament::getCurrentPanel()` de dentro do model, que é
   acoplamento na direção errada.

### Consequências

- **Positivas**: testável sem renderizar tela; um único entendimento de "papel do usuário" no código;
  reaproveitável por qualquer outra tela futura (por exemplo, uma coluna na tabela de usuários).
- **Negativas**: mais um método público em `User`, que já é o model mais denso do kit.
- **Riscos**: alguém pode passar a usá-lo como se fosse autorização. Mitigação: o docblock diz, em uma
  frase, que ele é **exibição** — quem autoriza é `canAccessPanel()`.

### Referências

- `app/Models/User.php:106-116` (`temPapelDoPainel()`, o irmão)
- `.ai/rules/filament.md` §2
- Refine: ADR-03

---

## ADR-03: `papeisEmQualquerContexto()` e não `roles()`

**Status**: Aceita
**Data**: 2026-08-18

### Contexto

Com `permission.teams` ligado (o que o `kit:tenancy` faz), a relação `roles()` do spatie acrescenta
`wherePivot(team_id, getPermissionsTeamId())`. Isso é correto para permissão, e errado para esta
pergunta: no `/admin` e no `/infra` não há tenant na rota, e o `team_id` corrente pode ser o global,
o de uma organização qualquer ou nada — dependendo do que o middleware definiu.

O `User` já enfrentou este problema em `canAccessPanel()` e criou `papeisEmQualquerContexto()`
justamente para isso, com o comentário explicando que "pergunta de ACESSO A PAINEL não é pergunta de
organização".

### Decisão

`papelDoPainel()` usa `papeisEmQualquerContexto()`, a mesma relação de `canAccessPanel()`.

O acoplamento é intencional e é a coisa certa: **o badge tem de dizer o papel que abriu a porta**. Se
ele consultasse outra relação, existiria um caminho em que o usuário entra no painel por um papel e o
badge mostra outro — ou nenhum.

### Alternativas Consideradas

1. **`roles()` do spatie** — descartada: no `/app` devolveria só os papéis do tenant corrente, e no
   `/admin` devolveria conforme o `team_id` que estivesse setado. Badge sumindo em painel sem tenant é
   o defeito mais provável desta feature, e é exatamente este.
2. **Trocar o `PermissionRegistrar` no container antes da consulta** — descartada: é o padrão que o
   `User` já **abandonou** (o antigo `temPapelGlobal()`), porque descarregava a relação duas vezes por
   chamada.

### Consequências

- **Positivas**: badge e acesso ao painel derivam do mesmo fato; um bug em um aparece no outro, em vez
  de os dois divergirem em silêncio.
- **Negativas**: usuário com dois papéis do mesmo painel em organizações diferentes vê o primeiro por
  ordem de chave. Aceito: o caso é raro, e mostrar "um dos papéis" é melhor que mostrar nenhum.
- **Riscos**: se `papeisEmQualquerContexto()` mudar de semântica, muda o badge junto. É o
  comportamento desejado.

### Referências

- `app/Models/User.php:151-176`
- Refine: ADR-02

---

## ADR-04: sem kill-switch de configuração

**Status**: Aceita
**Data**: 2026-08-18

### Contexto

Várias features do kit têm kill-switch (`lockscreen.enabled`, `command-center.enabled`,
`filament-onboarding.enabled`, `kit.tenancy.enabled`). O padrão parece ser "toda feature nova ganha o
seu".

### Decisão

Esta não ganha. A feature é 35 linhas de Blade sem estado, sem query cara, sem integração externa e
sem superfície de risco. Quem não quiser, remove três linhas dos providers.

### Alternativas Consideradas

1. **`config('kit.user_menu_header.enabled')`** — descartada: chave de config é contrato público do
   kit. Cada uma precisa de documentação, de valor no `.env.example`, de teste do caminho desligado e
   de manutenção no `KitUpdate`. Custo permanente para desligar 35 linhas de HTML.

### Consequências

- **Positivas**: `config/kit.php` continua sendo a superfície de customização **do que importa**, e
  não uma lista de interruptores cosméticos.
- **Negativas**: quem quiser desligar edita código do projeto — o que, num starter kit, é o uso
  esperado.

### Referências

- `config/kit.php` (179 linhas; toda chave lá tem consumidor real)

---

## ADR-05: nenhum log nesta feature

**Status**: Aceita
**Data**: 2026-08-18

### Contexto

A skill `feature-wiki` exige channel de log por feature e log em toda etapa de execução. O padrão do
kit reforça: `canAccessPanel()` loga negativa em `autenticacao`, `canAccessTenant()` loga em `tenancy`.

### Decisão

**Nenhum log.** E a decisão fica escrita, para que a próxima pessoa não "corrija a falta".

O motivo: o que a feature faz é ler a identidade do próprio usuário autenticado e desenhá-la, uma vez
por página renderizada. Um log aqui seria uma linha por request por usuário, dizendo o que a sessão já
diz. A pergunta que ele responderia — "quem entrou, quando, de onde" — já é respondida, melhor e com
retenção, pelo `tapp/filament-authentication-log` que o kit tem instalado.

O padrão `[Classe@Método]` continua obrigatório em todo log que **exista** no kit. O que se declara
aqui é que nesta feature não deve existir nenhum.

### Alternativas Consideradas

1. **Channel `user-menu` em nível `debug`** — descartada: ruído puro, e um channel a mais para o
   `LogsExplorer` listar sem nada útil dentro.
2. **Logar só a ausência de papel** (badge que não renderiza) — descartada: papel sem painel é estado
   **normal e correto** do modelo de dados (`roles.painel` nulo não é coringa), não anomalia. Logar
   estado normal como aviso é o que faz gente parar de ler log.

### Consequências

- **Positivas**: nenhum ruído acrescentado; o `authentication-log` continua sendo a resposta única
  sobre identidade de sessão.
- **Negativas**: se um dia o badge mostrar papel errado em produção, não há rastro. Aceito: o caso é
  reproduzível em desenvolvimento com um usuário e dois papéis.

---

## ADR-06: `data-user-menu-header` — o primeiro gancho de teste do kit

**Status**: Aceita
**Data**: 2026-08-18

### Contexto

`.ai/rules/testes-browser.md` registra uma dívida conhecida: **o kit não tem `data-testid` em lugar
nenhum**, e a suíte Browser depende de texto traduzido e `aria-label`. Qualquer mudança de rótulo
quebra teste sem relação com o defeito.

O cenário de browser desta feature precisa afirmar "o cabeçalho ficou visível ao abrir o dropdown".
Afirmar isso pelo nome do usuário é frágil de um jeito específico e ruim: o nome aparece **também**
no `AccountWidget` do dashboard, na mesma página. Um `assertSee` do nome passaria com o dropdown
fechado.

### Decisão

Marcar o elemento raiz do cabeçalho com `data-user-menu-header` e usar esse seletor no CT-B.

Não é um `data-testid` genérico: é um atributo nomeado pela função, no espírito do
`data-spotlight-trigger` que o `mini-pff` já usa para o mesmo tipo de problema.

### Alternativas Consideradas

1. **Seletor por classe do Filament** (`.fi-dropdown-panel ...`) — descartada: classe de framework é
   detalhe de implementação de terceiro; quebra em upgrade sem aviso.
2. **`assertSee` do e-mail do usuário** — descartada: o e-mail é único na página **hoje**. Assim que
   uma tela de perfil ou um widget o exibir, o teste vira falso positivo.
3. **Manter a dívida e não testar o clique** — descartada: é justamente a única afirmação da feature
   que só o navegador prova. Não testá-la é entregar o hook sem prova de que o dropdown abre.

### Consequências

- **Positivas**: primeiro precedente de gancho de teste estável no kit; abre caminho para pagar a
  dívida do `.ai/rules/testes-browser.md` de forma incremental, uma tela por vez.
- **Negativas**: um atributo a mais no HTML de toda página de painel (custo: 24 bytes).
- **Riscos**: virar moda e alguém encher o kit de `data-*`. Mitigação: a regra é a mesma do Ponytail —
  gancho só quando o seletor natural é ambíguo ou instável, e o CT-B justifica no cabeçalho.

### Referências

- `.ai/rules/testes-browser.md` (a dívida)
- `mini-pff` — `resources/views/filament/spotlight-trigger.blade.php` (`data-spotlight-trigger`)

---

## ADR-07: pacote de terceiro termina em relatório, não em `composer require`

**Status**: Aceita
**Data**: 2026-08-18

### Contexto

O requisito pede varredura completa do diretório de plugins (547 pacotes, Filament v5, gratuitos),
lista de candidatos e um top 10 com prós e contras. Também pede que a implementação aconteça na branch
para validação posterior.

O `CLAUDE.md` do projeto diz: *"Do not change the application's dependencies without approval."*

### Decisão

Nenhum `composer require` nesta entrega. A varredura, a classificação e o top 10 entram como documento
(`wikis/pacotes-candidatos.md`), e a instalação vira decisão do mantenedor.

Isso **não** é atender pela metade: o próprio texto do requisito diz "feche um relatório a respeito
para minha consideração posterior". Relatório é a entrega pedida; instalação seria a entrega seguinte.

### Alternativas Consideradas

1. **Instalar os finalistas e deixar o usuário remover o que não quiser** — descartada: contraria o
   `CLAUDE.md` e inverte o ônus. Um kit de 50 pacotes que ganha 10 sem aprovação vira 60 pacotes de
   superfície de upgrade que ninguém escolheu.
2. **Instalar só os "óbvios" (autores do core: awcodes, bezhansalleh, filament/*)** — descartada:
   "óbvio" é julgamento, e o `CLAUDE.md` não abre exceção por reputação de autor.

### Consequências

- **Positivas**: `composer.json` e `composer.lock` intocados; a branch pode ser revisada só pelo que
  ela de fato muda; o relatório sobrevive à feature e vira documento de referência.
- **Negativas**: a 1.0 não sai desta branch com pacote novo. Aceito — a decisão é do mantenedor, e
  cada aprovação vira um passo de PRD com CTs próprios.

### Referências

- `CLAUDE.md` → *Application Structure & Architecture*
- `00-requisito.md` → §Ambiguidades
- `wikis/pacotes-candidatos.md`
