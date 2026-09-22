# Plano de Ação — Opção de layout compacto

> Requisito: `00-requisito.md` · Decisões: `02-decisoes-arquiteturais.md` · Casos: `04-casos-de-teste.md`

> **Este PRD foi escrito depois da implementação, e registra o plano REAL executado.** A wiki
> nasceu com o `00` e o `02`; os arquivos `01`, `03` e `04` ficaram para trás e estão sendo
> fechados agora, contra o código que existe. Onde o texto disser "o passo N fez X", há commit e
> arquivo apontados. Nenhuma linha aqui descreve intenção que não virou código — quando algo ficou
> de fora, está marcado ⚠️ e repetido no `03-progresso.md`.

## Natureza da Wiki

- **Tipo**: nova
- **Wiki ancestral**: —
- **Motivo**: a ideia nasceu do defeito de CSS corrigido na `v0.37.1`
  (`wikis/specs/fix/css-quebrado-com-tenant/`), mas esta entrega não corrige nem evolui aquela: ela
  usa **o mesmo mecanismo** (cascade layer) na direção oposta. A `v0.37.1` fixou a ordem das layers
  para impedir que um terceiro derrubasse o estilo; esta declara **fora** de layer para vencer o
  próprio Filament de propósito.
- **Toca infra compartilhada?**: **sim** — três pontos, e os três têm consumidor fora da feature:
  1. `App\Settings\ConfiguracoesDoKit` ganha propriedade nova. A tela `/admin/configuracoes-da-aplicacao`
     salva **todas** as abas de uma vez, então um campo mal declarado derruba e-mail, login social,
     anti-robô e login unificado junto — e derrubou, ver `## Notas de Implementação` do `03`
  2. `KitServiceProvider::boot()` ganha uma registração de render hook em `STYLES_BEFORE`, a mesma
     chave que `configureOrdemDasCascadeLayers()` já usa
  3. O `<style>` novo aparece no HTML de **toda** tela dos três painéis

  **A regressão é obrigatória** e está prestada em `## Verificação Final`.

## Cobertura do Requisito

| RQ | Cláusula | Passo(s) | Resultado |
|----|----------|----------|-----------|
| RQ-01 | Estudar e documentar o plugin pago | 1 | ✅ **ADR-05**, medida por diff dos bundles da demo: 1.354 linhas acrescentadas, 548 blocos de regra, 370 classes `fi-*`, 11 variáveis de ajuste. Recusado para o kit — a licença é de projeto único |
| RQ-02 | Navegar a demo oficial e registrar o que ela mostra | 1 | ✅ Navegada com o pacote npm `playwright` (o MCP não estava configurado — desvio declarado no `00`). A demo **não** exigiu credencial para o painel, então o estudo alcançou o interior e não só a tela de login: as medições da ADR-03 saíram de lá |
| RQ-03 | Documentar onde a opção entraria no *settings* | 1, 3 | ✅ **ADR-06** responde onde **e por quê**: aba **Kit** de `/admin/configuracoes-da-aplicacao`, pelo caminho do render hook. O caminho do `viteTheme()` é o que **não** serve, e a ADR mostra o mecanismo (`vendor/filament/filament/src/Panel/Concerns/HasTheme.php:viteTheme():27` não aceita `Closure`) |
| RQ-04 | **Medir a profundidade e o peso** — o gatilho | 1 | ✅ Medida. **Os três critérios de "fácil" passaram**; os números estão em `### A decisão de RQ-04`, abaixo |
| RQ-05 | **Se fácil, implementar** nas quatro superfícies | 2, 3, 4, 5, 6 | ✅ **ESCOLHIDA.** Stats, tabela, menu e botão apertam nos três níveis, medido no kit — ver a tabela de `### Passo 6` |
| RQ-06 | **Se complexo, só documentar** | — | ⛔ **Excluída por RQ-04.** Mutuamente exclusiva com RQ-05. O estudo foi documentado assim mesmo (ADR-01 a ADR-06 + item 2 do roadmap), mas como **acompanhamento** da entrega, não no lugar dela |
| RQ-07 | Analisar e documentar o TODO de preferências de usuário | 7 | ✅ `wikis/roadmap.md`, item 1 — commit `bf6e799` |
| RQ-08 | O TODO enumera fonte, densidade, cor e modo de ocultação do menu | 7 | ✅ Tabela de quatro linhas no item 1 do roadmap, cada uma com "situação hoje" e "o que falta" |
| RQ-09 | O TODO registra a hipótese de virar pacote Filament externo | 7 | ✅ Item 1 do roadmap, com o argumento de por que nada nele depende do kit |
| RQ-10 | Documento de futuras melhorias, **ligado ao `README.md`** | 7 | ✅ **As duas metades.** `wikis/roadmap.md` (132 linhas, 5 itens) versionado, e a seção *Futuras melhorias* com o link em `README.md` e `README.en.md`. Fora do `export-ignore`, como o `00` decidiu — ele viaja para todo projeto criado do kit |

> ### A cláusula que quase ficou de fora — e o que isso diz sobre a suíte
>
> RQ-07 a RQ-10 estiveram **escritas e não entregues** durante toda a implementação:
> `wikis/roadmap.md` existia na árvore de trabalho, completo, e `git status` devolvia
> `?? wikis/roadmap.md` — fora do índice, e sem a linha correspondente no `README.md`. A suíte ficou
> **verde o tempo inteiro**, porque nenhum caso afirma sobre a existência do documento nem sobre a
> ligação com o README.
>
> Fechado pelo commit `bf6e799`, que versionou o roadmap e acrescentou a seção *Futuras melhorias*
> aos dois READMEs mais a linha em `wikis/README.md`. **A lacuna de cobertura continua aberta**: o
> cenário que teria pego a omissão no dia — `CT-15`, oráculo documental — **foi escrito** (como
> `[CT-48]` de `tests/Kit/SiteDeDocumentacaoTest.php`) e está em Gherkin no
> `04-casos-de-teste.md` (`L3`) e **não foi implementado**. Enquanto não for, a mesma omissão pode
> voltar sem nada ficar vermelho.
>
> A decisão de `.gitattributes` que o `00` registra continua válida e não precisou mudar:
> `/wikis/specs export-ignore` em `.gitattributes:24`, e `wikis/*.md` **fora** do `export-ignore`.

### A decisão de RQ-04 — os números que escolheram RQ-05 e descartaram RQ-06

O `00-requisito.md` fixou três critérios para "fácil", e a decisão entre implementar e apenas
documentar dependia dos três. **Os três passaram, e cada um tem evidência, não opinião:**

| Critério do `00` | Medido | Veredito |
|---|---|---|
| **(a)** não exige tema Vite customizado novo — o kit hoje não tem nenhum | O mecanismo é **uma declaração** de `--spacing` num render hook. Zero arquivo de tema, zero `viteTheme()`, zero `npm run build`. O tema pago **reprovaria** aqui: ele é build-time por construção (ADR-05) | ✅ passou |
| **(b)** não reintroduz o modo de falha da `v0.37.1` (ordem de cascade layer decidida por terceiro) | Declaração **fora** de cascade layer vence **qualquer** layer, independentemente de ordem de folha e de especificidade. O resultado não depende de quem carrega primeiro — que era exatamente a variável que a `v0.37.1` não controlava. Guardado por CT-05 | ✅ passou |
| **(c)** o toggle em tela tem efeito no **próximo request**, sem deploy | `STYLES_BEFORE` é avaliado no render do layout base (`vendor/filament/filament/resources/views/components/layout/base.blade.php:STYLES_BEFORE:44`), e o hook registra uma `Closure`. Provado por CT-06, que muda a resposta **no mesmo processo** sem remontar o painel | ✅ passou |

E o custo, que é a outra metade de RQ-04:

| | Esta entrega | Tema pago oficial | CSS artesanal por classe `fi-*` |
|---|---|---|---|
| Tamanho do mecanismo | **1 declaração CSS** | 548 blocos de regra, 370 classes `fi-*` | 11 seletores, na tentativa medida |
| Ganho na altura da tabela | **−16,8%** (`compacto`) · **−22,9%** (`denso`) | −22,5% | **+21,9%** — *piorou* |
| Sobrevive a `composer update`? | sim, **com uma dependência declarada** *(alterado em 2026-09-22)* — o caminho do `--spacing` não cita nada do vendor; o da largura do menu depende de `sidebarWidth()` aceitar `Closure` e do default `'20rem'` (`vendor/filament/filament/src/Panel/Concerns/HasSidebar.php:$sidebarWidth:11`), guardado por CT-18 | sim (é do vendor) | **não** — lista congelada de classes de terceiro |
| Roda em runtime? | sim | **não** — build-time | sim |
| Entra num starter kit? | sim | **não** — licença de projeto único | — |

**Diff da entrega**: **515 linhas acrescentadas e 1 removida**, em **11 arquivos**. O número sai
de um comando, e o comando fica escrito ao lado dele *(alterado em 2026-09-22 — antes o número
estava solto, sem SHA de base nem lista de paths, e nenhum comando o reproduzia)*:

```
git diff 5400faf..8d3f2f3 --stat -- app/ config/ database/ .env.example
# 11 files changed, 515 insertions(+), 1 deletion(-)
```

`5400faf` é o **merge-base** da feature com `main` (o merge do #94), e `8d3f2f3` é o merge do #95 —
não `HEAD`, que já andou. A lista de paths é o escopo do mecanismo: a wiki, os testes e as docs
ficam de fora de propósito, porque a comparação que esta conta sustenta é contra os *"548 blocos de
regra sobre 370 classes `fi-*`"* do tema pago, que também são só mecanismo. Remedido em 2026-09-21,
depois de o quality gate acrescentar a largura do menu (três painéis) e o `/code-review` acrescentar
o `KitUpdate` e a coerção na tela. A conta original, `bf6e799`, era 398 linhas em seis arquivos.
Das 246 linhas de `app/Support/DensidadeDoLayout.php`, **~70** são código — o resto é o docblock
que carrega os números medidos. Nenhuma dependência nova,
nenhum pacote, nenhum passo de build.

Foi essa conta que fechou RQ-04 para o lado de **RQ-05**. RQ-06 (documentar e esperar o Filament)
deixou de ser a entrega e virou o **item 3 do roadmap** — o gatilho de reavaliação, para quando o
Filament ganhar densidade de painel.

## Objetivo

Dar ao kit uma opção de **layout compacto** que valha para as quatro superfícies que o requisito
fechou — cartões de estatística, tabelas, menu lateral e botões —, nos três painéis, **ligável e
desligável em tela**, sem tema Vite, sem `npm run build` e sem deploy.

O segundo objetivo é de método e vale tanto quanto: **medir antes de decidir**. O requisito não
pediu a implementação, pediu a medição e delegou a escolha ao resultado. Todo número desta wiki foi
lido de navegador com estilo computado — o que fez, no caminho, a tentativa "óbvia" ser descartada
por ter **piorado** o layout em 21,9%.

## Contexto

O Filament 5 **não tem densidade global** (ADR-01). Ele tem `compact()` por componente —
`Section`, `EmptyState`, `Repeater` — e `Table` não tem nem isso; `Panel` não tem `densely()` nem
equivalente em nenhum dos 34 concerns. "Compacto para tudo" é CSS, obrigatoriamente.

O que existe é um **tema oficial pago** (`filament/compact-theme`, US$ 29), que resolve bem e não
pode entrar num starter kit: a licença é de projeto único, e starter kit é distribuído.

Do outro lado, a CSS publicada do Filament 5 é Tailwind 4, e **todo** espaçamento dela é
`calc(var(--spacing) * N)`: `var(--spacing)` aparece **1.228 vezes** em
`public/css/filament/filament/app.css`, e o valor `.25rem` é declarado **uma vez só**, dentro de
`@layer theme`. É essa assimetria que torna a entrega barata.

## Análise dos Arquivos Existentes

### `app/Providers/KitServiceProvider.php`

Já registra render hooks globais pelo `FilamentView::registerRenderHook()` — uma registração cobre
os três painéis. `configureOrdemDasCascadeLayers()` (`app/Providers/KitServiceProvider.php:configureOrdemDasCascadeLayers():551`)
é o **molde exato**: mesma chave (`STYLES_BEFORE`), mesmo assunto (cascade layer), criada na
`v0.37.1`. A feature entra ao lado dela, na linha seguinte do `boot()`.

### `app/Settings/ConfiguracoesDoKit.php`

`.ai/rules/settings.md` fixa que propriedade nova são **três lugares, sempre**: a propriedade, a
linha do `mapaDeConfiguracao()` (`app/Settings/ConfiguracoesDoKit.php:mapaDeConfiguracao():367`) e
o `add()` numa migration de settings. `aplicarNaConfig()`
(`app/Settings/ConfiguracoesDoKit.php:aplicarNaConfig():504`) sobrepõe a config do processo com o
banco no `boot()` — é o mapa que liga uma coisa à outra, e **esquecê-lo é o defeito silencioso**:
o campo aparece, grava, e não governa nada.

### `app/Filament/Admin/Pages/ConfiguracoesDoKit.php`

A tela de configurações, aba **Kit**, onde já moram o hub em cartões, o alerta de alterações não
salvas e o dashboard dinâmico. A tela salva **todas as abas numa chamada só** — o que transforma um
campo mal declarado em falha da página inteira.

### `config/kit.php`

Convenção do arquivo: cada chave lê do `.env` com coerção explícita (`BooleanoDoEnv`,
`NumeroDoEnv`, `ValidadeDoConvite`). Chave de vocabulário fechado não tinha precedente — esta é a
primeira, e por isso a coerção mora no próprio enum.

### `vendor/filament/tables/resources/css/cell.css`

Leitura obrigatória antes de escrever qualquer CSS de tabela: a linha 2 é `@apply p-0`. O padding da
célula **não mora nela**; mora no elemento da coluna, espalhado por nove arquivos `columns/*.css`.
É a causa de a tentativa artesanal ter somado padding em vez de reduzir (ADR-03).

## Autorização

**Nada novo.** O Select vive numa aba de uma tela que já é protegida por
`App\Filament\Concerns\ExigePermissaoDaTela`; quem alcança a tela alcança o campo. Não há policy,
gate, guard nem middleware nesta entrega. O render hook não consulta usuário: a densidade é
configuração **da instalação**, não do usuário logado — e é exatamente essa distinção que produz o
item 1 do roadmap.

## Rotas

**Nenhuma rota nova.** A feature é lida em rota existente (toda rota de painel, pelo layout base) e
escrita em rota existente (`/admin/configuracoes-da-aplicacao`).

## Superfície de UI

| Tela / Componente | Tipo | Rota | Interação do usuário | Depende de JS? |
|---|---|---|---|---|
| `ConfiguracoesDoKit`, aba **Kit**, campo *Densidade do layout* | Filament (Page de settings) | `/admin/configuracoes-da-aplicacao` | escolhe um dos três níveis e salva | Não |
| O `<style>` emitido em `STYLES_BEFORE` | render hook | **toda** rota dos três painéis | nenhuma — é consequência | Não |

**Gate do `05`**: a tentação aqui é grande, porque a feature é visual. Mas os cenários que a
suíte precisa provar não são visuais: *"o valor gravado chega ao HTML servido"*, *"a declaração
está fora de `@layer`"*, *"a resposta muda no mesmo processo"* — os três se resolvem lendo o HTML
e o retorno do hook, sem navegador. **Não há `05-casos-de-teste-browser.md`**, e o motivo está
declarado em `04-casos-de-teste.md` → `## Sem CT-B`.

O que **só** o navegador prova — de quantos pixels a linha da tabela encolheu — foi medido com
Playwright como **instrumento de decisão** (passo 6), não versionado como cobertura. É a mesma
separação que a skill faz: o Pest atesta, o Playwright observa.

**Gate de tela de escrita**: a rota é de edição, e o `04` tem o cenário de gravação por componente
— **CT-14**, `Livewire::test(ConfiguracoesDoKitTela::class)->fillForm(...)->call('save')`. Não é
formalidade: foi esse cenário que pegou o único defeito real da entrega.

## Variáveis de Ambiente

| Key | Default | Descrição |
|-----|---------|-----------|
| `KIT_DENSIDADE_DO_LAYOUT` | `confortavel` | Semente e plano B da chave editável na tela. Vocabulário fechado: `confortavel`, `compacto`, `denso` |

Documentada em `.env.example`, no bloco entre o alerta de alterações não salvas e a versão do kit,
com o aviso de que valor fora do vocabulário cai em `confortavel`.

> `.ai/rules/config.md` — *"uma pergunta, uma dona"*: a pergunta *"o quanto o layout aperta"* não
> tinha dona antes desta entrega, e passa a ter exatamente uma. O consumo é sempre por
> `config('kit.densidade_do_layout')`, nunca por `env()` direto.

## Eventos / Listeners / Observers

Nenhum. A feature não emite nem escuta evento, e não tem observer.

## Jobs / Queues

Nenhum. Não há trabalho assíncrono.

## Modelo de Execução

| Pergunta | Resposta |
|---|---|
| Quantos requests a tela custa? | **zero a mais.** A feature não acrescenta request: ela acrescenta uma `Closure` ao `STYLES_BEFORE`, avaliada uma vez por render de layout |
| O que é adiado, e por qual gatilho? | nada |
| O que é memoizado **por request**? | nada — e é deliberado. `DensidadeDoLayout::deConfig()` (`app/Support/DensidadeDoLayout.php:deConfig():217`) lê `config()`, que já está em memória desde o `boot()`; memoizar um `match` sobre um array em memória seria cache de nada |
| O que é cacheado **entre** requests? | nada. Cachear **quebraria** o critério (c) de RQ-04 — o toggle deixaria de valer no próximo F5 |
| Custo do caminho principal | **zero query.** A leitura sai de `config()`; o banco já foi consultado uma vez no `boot()` por `aplicarNaConfig()`, que é infraestrutura pré-existente e não muda de custo por causa desta chave |

Declarar isto não é burocracia: a ADR-06 inteira depende da premissa *"lido por request, e por
request mesmo"*. Um memo estático dentro do hook — o reflexo de quem vê uma `Closure` chamada em
toda página — passaria em CT-03 e reprovaria em CT-06, que é o caso que existe para essa premissa.

## Impacto em Features Existentes

| Superfície | Risco | Como ficou |
|---|---|---|
| **Tela de configurações inteira** | a tela salva todas as abas juntas; campo mal declarado derruba as outras | **Realizou-se.** `->options(DensidadeDoLayout::class)` derrubou 60 casos de login social, anti-robô e login unificado. Corrigido e coberto por CT-14 — ver `03` |
| `configureOrdemDasCascadeLayers()` (v0.37.1) | duas registrações na mesma chave `STYLES_BEFORE` | Convivem. A da v0.37.1 declara **ordem de layers**; a desta declara **fora** de layer. CT-01 exige que as duas saiam no mesmo bloco |
| `caresome/filament-auth-designer` | veste as telas de autenticação com blade própria — poderia não emitir o hook | Emite. CT-03 mede nas telas de **login** dos três painéis de propósito, por serem o layout mais provável de escapar |
| `croustibat/filament-jobs-monitor` | folha própria em `@layer components` — foi a causa da `v0.37.1` | Não interfere: declaração fora de layer vence qualquer layer |
| `laravel/pulse` (`/infra/pulse`) | CSS própria | **Os cartões não apertam** — medido, 128 px nos três níveis. Achado documentado, não defeito |
| `tests/Kit/KitInfoTest.php` | âncora escrita à mão com a contagem de propriedades do settings | 54 → 55, atualizada no mesmo commit do settings |
| `tests/Kit/CitacoesDeCodigoTest.php` e os contadores dos READMEs | o `use` novo em `config/kit.php` desloca linhas; arquivo de teste e wiki novos deslocam contadores | Três guardas ficaram vermelhas e foram fechadas no commit `022e027` |

## Rollback

- **Migration**: `database/settings/2026_09_21_100000_add_densidade_do_layout_to_kit_settings.php`
  tem `down()`, que remove a propriedade do grupo `kit`. Coberto por CT-08
- **Sem deploy**: gravar `confortavel` na tela desliga a feature por completo — nesse nível o kit
  **não emite `<style>` nenhum** (CT-01, CT-04), e o HTML volta byte a byte ao que era
- **Sem tela**: `KIT_DENSIDADE_DO_LAYOUT=confortavel` no `.env` faz o mesmo, desde que a linha do
  banco não sobreponha
- **Reversão de dados**: nenhuma. A chave é cosmética e não deriva nada

## Dependências

**Nenhuma nova.** Composer e `package.json` intactos. O `playwright` usado na medição é ferramenta
de sessão, não dependência do kit — e é por isso que a medição é registrada em prosa e em número,
não em teste versionado.

## Riscos

| Risco | Mitigação | Estado |
|---|---|---|
| Valor ilegível no `.env` ou na tabela estoura no layout base de toda tela | `coagir()` com `tryFrom() ?? padrao()`, chamado nos **dois** lados (`config/kit.php` e `deConfig()`) | fechado — CT-10 (7 entradas) e CT-11 |
| A declaração acabar **dentro** de uma cascade layer e não mudar nada | CT-05 recorta a tag do kit e exige ausência de `@layer` e de `!important` | fechado |
| O toggle gravar e não fazer efeito (armadilha do `settings.md`) | ADR-06 + CT-06, que muda a resposta no mesmo processo | fechado |
| Esquecer a linha do `mapaDeConfiguracao()` | CT-07 afirma sobre as três pontas; a mutação confirmou que apagar a linha reprova **10 dos 31 casos** (re-rodada em 2026-09-22 *(alterado em 2026-09-22)*; dizia "10 dos 30", e antes disso "7 dos 14") | fechado |
| A medição da ADR ter sido feita na **demo limpa**, e o kit reagir diferente | Medição refeita **no kit**, com jobs-monitor, auth-designer, `resized-column` e Pulse ligados | fechado — passo 6; o kit rendeu **mais** que a demo |
| Distorção de proporção (ícone e input encolhem junto) | Não tem conserto barato. Mitigada por **níveis** (ADR-04) e **declarada** nas docs pt/en, com os números | aceito |

## Channel de Log da Feature

**Nenhum log, e a ausência é decisão.** `config/logging.php` tem quatro canais nomeados (`ai`,
`tenancy`, `autenticacao`, `configuracoes`), e nenhum deles recebe linha desta feature.

O motivo: o único ponto de execução é um render hook que roda em **toda tela dos três painéis**.
Logar ali produziria uma linha por página servida, para informar um valor que já está no HTML
servido e na tabela `settings`. A gravação, que é o evento que vale rastrear, já é registrada pelo
caminho comum da tela de configurações.

Criar um canal `layout-compact` seria uma quinta dona para uma pergunta que já tem resposta.

## Estrutura de Implementação

### 1. Medir a profundidade e o peso (RQ-01, RQ-02, RQ-03, RQ-04)

> Commit `c862361` · Skills: `feature-wiki`

- Varredura de `vendor/filament/*/src` atrás de densidade nativa — resultado em **ADR-01**
- Navegação da demo oficial com o pacote npm `playwright`, lendo **estilo computado** — o MCP não
  estava configurado, desvio declarado no `00`
- Diff dos bundles `stock-*.css` × `stock-compact-*.css` da demo — **ADR-05**
- Três caminhos medidos lado a lado: `--spacing`, CSS artesanal e tema pago — **ADR-03**
- Confronto com `.ai/rules/settings.md` (o toggle que grava e não governa) — **ADR-06**

**Saída**: as seis ADRs e a decisão de RQ-04 por **RQ-05**.

### 2. `App\Support\DensidadeDoLayout` — a escala (RQ-05)

> Commit `589110c` · Skills: `laravel-best-practices`, `ponytail`

- **Path**: `app/Support/DensidadeDoLayout.php`
- `enum DensidadeDoLayout: string implements HasLabel`, três casos: `Confortavel`, `Compacto`,
  `Denso`
- `getLabel(): string` — o rótulo do Select, com o preço de cada degrau escrito no próprio texto
- `opcoes(): array` (`app/Support/DensidadeDoLayout.php:opcoes():131`) — `valor => rótulo`, **array
  cru**. Existe para que o campo **não** seja `->options(DensidadeDoLayout::class)`; o porquê está
  no `03` → `## Notas de Implementação` e no docblock do método
- `espacamento(): ?string` (`app/Support/DensidadeDoLayout.php:espacamento():148`) — `null`,
  `0.2rem`, `0.175rem`. O `null` é **contrato**, não conveniência: é ele que impede o `<style>`
  vazio no nível padrão
- `deConfig(): self` (`app/Support/DensidadeDoLayout.php:deConfig():217`) — lê
  `config('kit.densidade_do_layout')`, **por request**
- `coagir(mixed): self` (`app/Support/DensidadeDoLayout.php:coagir():236`) — a **única** cópia da
  coerção, `tryFrom() ?? padrao()`. Chamada nos dois lados porque as duas entradas escapam uma da
  outra: o `config/kit.php` coage o que veio do `.env`, e `deConfig()` coage o que veio do **banco**,
  que `aplicarNaConfig()` escreve direto na config sem passar pelo arquivo
- `padrao(): self` (`app/Support/DensidadeDoLayout.php:padrao():242`) — a única cópia do valor de
  fábrica; o config e a migration leem daqui
- **Também**: `config/kit.php:densidade_do_layout:287` e o bloco de `.env.example`

**Logs**: nenhum — ver `## Channel de Log da Feature`.

### 3. Os três lugares do Settings, e o campo na tela (RQ-03, RQ-05)

> Commit `d82eccf` · Skills: `laravel-best-practices`

1. **Propriedade**: `public string $densidade_do_layout`
   (`app/Settings/ConfiguracoesDoKit.php:densidade_do_layout:162`). `string` e não o enum — o
   spatie grava o `payload` em JSON, e o vocabulário é garantido no **consumo**, não no
   armazenamento
2. **Linha do mapa**: `'densidade_do_layout' => 'kit.densidade_do_layout'` em
   `mapaDeConfiguracao()`. **É a ligação**; sem ela o Select grava e não governa nada
3. **Migration nova**: `database/settings/2026_09_21_100000_add_densidade_do_layout_to_kit_settings.php`,
   nunca a que já rodou — instalação de terceiro que só roda `migrate` ficaria sem a linha e o
   `boot()` estouraria `MissingSettings` em todo request. Semeia com
   `DensidadeDoLayout::coagir(config('kit.densidade_do_layout'))->value`, e não com `config()`
   direto, porque um `.env` com `compact` semearia a tabela com um valor que nenhum nível conhece
4. **O campo**: `Select::make('densidade_do_layout')`
   (`app/Filament/Admin/Pages/ConfiguracoesDoKit.php:densidade_do_layout:810`), com
   `->options(DensidadeDoLayout::opcoes())`, `->selectablePlaceholder(false)` e `->required()`.
   Select e não Toggle é a ADR-04

A semente nasce **confortável** de propósito, e a direção é o oposto da do
`alerta_alteracoes_nao_salvas`: lá o comportamento anterior perdia digitação e o silêncio não era
default a preservar; aqui o comportamento anterior é **a aparência que o projeto já tem**, e
atualizar o kit não deve mudá-la sozinho.

### 4. O render hook (RQ-05)

> Commit `3c6d5f6` · Skills: `tailwindcss-development`, `ponytail`

- **Path**: `app/Providers/KitServiceProvider.php:configureDensidadeDoLayout():611`, chamado no
  `boot()` logo depois de `configureOrdemDasCascadeLayers()`
- `FilamentView::registerRenderHook(PanelsRenderHook::STYLES_BEFORE, Closure)` — uma registração
  cobre os três painéis, pelo mesmo motivo da irmã da `v0.37.1`
- A `Closure` devolve `''` quando `espacamento()` é `null`, e
  `'<style>:root{--spacing:'.$espacamento.'}</style>'` nos outros dois níveis
- **Fora de cascade layer**, sem `@layer` e sem `!important`. É o ponto inteiro do mecanismo:
  `--spacing:.25rem` do Filament mora em `@layer theme`, e declaração sem layer vence layer

**Logs**: nenhum.

### 5. A suíte (RQ-05)

> Commit `c2189d7` · Skills: `pest-testing`, `feature-test-design`

- **Path**: `tests/Kit/DensidadeDoLayoutTest.php` — **17 CTs, 31 casos** com datasets
- Especificação completa em `04-casos-de-teste.md`
- Âncora de `tests/Kit/KitInfoTest.php` de 54 para 55 propriedades, com o motivo escrito no
  comentário — ela é manual de propósito, para ficar vermelha e obrigar a decisão

### 6. Medir o resultado **no kit**, nos quatro níveis (RQ-04, RQ-05)

> Skills: — (Playwright como instrumento)

A ADR-03 mediu na **demo limpa**. O risco declarado lá era o kit reagir diferente, por causa de
jobs-monitor, auth-designer, `resized-column` e Pulse. A medição foi refeita no kit, com Playwright,
estilo computado, viewport **1600×1000**, **oito telas por nível**:

| | padrão | confortável | compacto (`0.2rem`) | denso (`0.175rem`) |
|---|---|---|---|---|
| linha da tabela | 56,0 px | 56,0 px | **46,4 px (−17,1%)** | **42,9 px (−23,4%)** |
| tabela de 10 linhas | 612 px | 612 px | **509,2 px (−16,8%)** | **471,8 px (−22,9%)** |
| `.fi-btn` | 36,0 px | 36,0 px | 32,8 px (−8,9%) | 31,2 px (−13,3%) |
| `.fi-input` | 36,0 px | 36,0 px | 28,8 px (−20,0%) | 25,2 px (−30,0%) |
| item de menu lateral | 40,0 px | 40,0 px | 32,8 px (−18,0%) | 31,2 px (−22,0%) |
| stat (`/admin`, `/infra`) | 150 / 140 px | 150 / 140 px | 137,2 / 127,2 px (−8,5%) | 130,8 / 120,8 px (−12,8%) |
| `.fi-section` | 92 px | 92 px | 77,6 px (−15,7%) | 70,4 px (−23,5%) |
| ícone | 24 px | 24 px | 19,2 px (−20%) | 16,8 px (−30%) |
| largura da sidebar *(alterado em 2026-09-22)* | 320 px | 320 px | **272 px (−15,0%)** | **264 px (−17,5%)** |
| topbar | 64 px | 64 px | **64 px** | **64 px** |
| overflow horizontal | não | não | não | não |

> **A linha da largura da sidebar não é mais a medição de 2026-09-21** *(alterado em 2026-09-22)*.
> Ela dizia **320 px nos três níveis**, e isso é falso desde o commit `ec665a5`, que colocou a
> largura na escala: `app/Support/DensidadeDoLayout.php:larguraDaSidebar:201` devolve
> `'20rem' / '17rem' / '16.5rem'` — **320 / 272 / 264 px** a 16 px de raiz — e os três painéis a
> consomem por `->sidebarWidth(fn (): string => DensidadeDoLayout::deConfig()->larguraDaSidebar())`
> (`app/Providers/Filament/AdminPanelProvider.php:sidebarWidth:117`,
> `app/Providers/Filament/AppPanelProvider.php:sidebarWidth:128`,
> `app/Providers/Filament/InfraPanelProvider.php:sidebarWidth:138`). Os valores de compacto e denso são o **mínimo medido** no
> navegador, não estimativa — o docblock do método registra a varredura. **As outras linhas da
> tabela continuam sendo a medição Playwright de 21/09.** As docs pt/en, o CHANGELOG e o roadmap já
> traziam 320 → 272 → 264; era esta tabela, a que serve de prova, que tinha ficado para trás.

> **A coluna `padrão` foi medida com a feature FORA da árvore**, por `git stash`, e **não** é a
> coluna `confortavel` renomeada. É ela que prova a promessa de CT-01: o nível de fábrica é
> idêntico ao kit sem a feature, nas onze linhas.

As quatro superfícies do escopo respondem. As três leituras que **não** apertam estão em
`03-progresso.md` → `## Achados da Medição`, e viraram o item 5 do roadmap.

### 7. Documentação (RQ-01, RQ-06, RQ-07, RQ-08, RQ-09, RQ-10)

> Commits `e98f436` e `022e027` · Skills: —

- `docs/pt/recursos/configuracoes-do-kit.md` e `docs/en/...` — seção *"Layout compacto: uma escala,
  não um interruptor"*, com a tabela dos três níveis, **o preço declarado junto com o ganho**, o
  que não aperta, e o apontamento do tema pago com o número dele
- `CHANGELOG.md` — entrada em `[Unreleased] › Adicionado`
- `README.md` / `README.en.md` — contadores (arquivos de teste 147/173 → 148/174; features
  especificadas 64 → 65)
- `tests/Kit/CitacoesDeCodigoTest.php` e `wikis/convencoes.md` — `arte_do_login` de `:135` para
  `:136`, deslocada pelo `use` novo em `config/kit.php`
- `wikis/roadmap.md` — os cinco itens (preferências de usuário, tema pago, gatilho de densidade
  nativa, armadilha do `viteTheme()` e as superfícies que não apertam), mais a seção *Futuras
  melhorias* em `README.md` e `README.en.md` e a linha em `wikis/README.md`. Commit `bf6e799`,
  acrescentado depois dos oito primeiros — ver o aviso em `## Cobertura do Requisito`

### 8. A coerção na entrada do formulário (RQ-05) — acrescentado pelo `/code-review`

> Commit `d19e1e3` · Achado 2 do step 7.5

`Select` acrescenta sozinho um `Rule::in()` das próprias opções, então um nível ilegível gravado
na linha de settings travava **a tela inteira** — quem tentasse mudar o nome da aplicação levava
erro num campo que não tocou.

`DensidadeDoLayout::coagir()` entra em `mutateFormDataBeforeFill()`, e **não**
`comValorConfigurado()`: aquele helper existe para valor *legítimo porém fora da lista curta*
(`MAIL_MAILER=ses`), e rebaixá-lo seria perda de dado. Nível de densidade tem vocabulário
**fechado** — `compact` é lixo, e oferecê-lo como opção marcada exibiria lixo e o gravaria de volta.

Coberto por **CT-16**, que nasceu vermelho contra a implementação anterior.

### 9. O roadmap na lista de entrega do `kit:update` (RQ-10) — acrescentado pelo `/code-review`

> Commit `8c3ef0b` · Achado 1 do step 7.5

`wikis/roadmap.md` entrou no repositório e não em `KitUpdate::CAMINHOS_DO_KIT`. "Viajar com o
projeto" tem **dois** caminhos, e só um estava coberto:

| Caminho | Governado por | Atende |
|---|---|---|
| `composer create-project` | `.gitattributes` | quem instala **agora** |
| `php artisan kit:update` | `CAMINHOS_DO_KIT` | quem **já** instalou |

Coberto por **CT-15** (`[CT-48]` de `tests/Kit/SiteDeDocumentacaoTest.php`), nos dois caminhos.

### 10. A largura do menu na escala (RQ-05) — acrescentado pelo quality gate

> Commit `ec665a5` · QA-01 do step 8, decidido pelo usuário

O menu entregava **metade** do compacto: a altura dos itens encolhia e a largura não. O `00` afirma
que nenhuma superfície fica parcialmente compacta, e o menu é uma das quatro do escopo.

A largura vem de `--sidebar-width`, emitido **inline** pelo Filament — fora do alcance de qualquer
cascade layer. Ela entra por `Panel::sidebarWidth()` com `Closure`, nos três painéis, e o getter
faz `evaluate()` no render: mesma propriedade do render hook, sem a armadilha do `viteTheme()` da
ADR-06. **Esse fato não estava na ADR-03.**

Os valores são o **mínimo medido** por varredura no navegador, com o `--spacing` de cada nível
ativo. A primeira escolha (`16rem` para o denso) foi **reprovada pela medição** — truncava o rótulo
mais longo do kit por 5 px.

Coberto por **CT-17**, que afirma sobre os três painéis porque `sidebarWidth()` é por painel e foi
escrito três vezes.

## Filosofia de Implementação

> **Ponytail** ativo. A escada foi aplicada, e o resultado era literal: o mecanismo inteiro cabia
> em **uma declaração CSS**. Hoje são **dois caminhos** — a declaração de `--spacing`, mais
> `Panel::sidebarWidth(Closure)` nos três painéis para a largura do menu, que `--spacing` não
> alcança (passo 10). *(corrigido em 2026-09-21: o texto continuava afirmando "uma declaração" 50
> linhas depois do passo que descreve o segundo caminho.)* A alternativa "séria" — CSS por classe `fi-*` — foi medida e **piorou** o
> layout. É o caso raro em que a solução preguiçosa não é só mais barata: é a única que funciona.
>
> Arquivos da wiki (00–06) são boundary do Caveman — prosa normal.

## Testes

> Ver `04-casos-de-teste.md`. **17 CTs, 31 casos** em `tests/Kit/DensidadeDoLayoutTest.php`, mais **CT-15** em `tests/Kit/SiteDeDocumentacaoTest.php` — **18 cenários, 32 casos** na feature.
> **Sem `05`** — nenhum cenário afirma sobre algo que só o navegador prova.

## Verificação Final

- [x] `vendor/bin/pint --dirty --format agent` — `passed`, 2026-09-21
- [x] `vendor/bin/filacheck --fix` — **17/17 regras**, 2026-09-21
- [x] `vendor/bin/pest tests/Kit/DensidadeDoLayoutTest.php --compact` — **31/31, 68 asserções**, remedido em **2026-09-22** *(alterado em 2026-09-22)* — o CT-18 entrou em 22/09 e não disparou a remedição; o número anterior, `30/66`, era de 21/09. Saída do runner: `{"tool":"pest","result":"passed","tests":31,"passed":31,"assertions":68}`
- [x] **Regressão completa** (obrigatória por tocar infra compartilhada) — **2.770 passaram, 10.782 asserções, 0 falhas**, **2026-09-22** *(alterado em 2026-09-22 — dizia 2.722 / 10.539, de antes do rebase sobre o #94)*; detalhe e histórico em `03-progresso.md` → `## Verificação Final`

  `{"tool":"pest","result":"passed","tests":2770,"passed":2770,"assertions":10782,"duration_ms":178100}`

  **Rodada por `php artisan test --testsuite=Kit,Tenancy --parallel`, e não por `composer test:kit`**: num shell sem o `composer` no PATH o script imprime `command not found` e **sai com código 0**, o que se lê como suíte verde. Registrado no `03`
- [x] **Falsificabilidade** — apagar a linha do `mapaDeConfiguracao()` reprova **10 dos 31 casos**, mutação **re-rodada em 2026-09-22** *(alterado em 2026-09-22)* (antes: 10 de 30 em 21/09; e 7 de 25, com 14 CTs). Saída sob o mutante: `{"result":"failed","tests":31,"passed":21,"failed":10,"assertions":58}` — arquivo restaurado depois, `md5sum` `1a0adc204e585486e604aadf92436256` igual ao de antes
- [x] **Custo medido** — zero request e zero query a mais, contra o `## Modelo de Execução`, 2026-09-21
- [x] **Medição no kit** — quatro níveis, oito telas, `padrão` medido com a feature fora da árvore, 2026-09-21
- [x] **`/code-review` no diff (step 7.5)** — executado durante a implementação; o achado do
  `->options(DensidadeDoLayout::class)` foi pego antes, pela própria suíte, 2026-09-21
- [x] `wikis/roadmap.md` commitado e ligado ao `README.md` — RQ-07 a RQ-10, commit `bf6e799`, 2026-09-21
- [x] **CT-15** (oráculo documental do roadmap) **escrito** — vive em `tests/Kit/SiteDeDocumentacaoTest.php` sob o ID local `[CT-48]`, o arquivo que já é dono dos contadores de README
- [x] **CT-16** — a tela de configurações não trava com nível ilegível gravado
- [x] **CT-17** — a largura do menu acompanha o nível nos três painéis (QA-01 do quality gate)
- [x] **Citações `arquivo:símbolo:linha` reverificadas** — **30/30 ok** *(alterado em 2026-09-22)*, sobre um escopo declarado: os arquivos **`00`–`04`**
  desta wiki, **sem o `06-relatorio-qa.md`**. O número anterior, *"36/36"*, só existe com o `06`
  dentro da varredura — e o `06` **cita citações erradas de propósito**, porque é o registro do que
  o gate achou. Contar o que ele transcreve como se fossem citações do kit infla o denominador com
  as próprias acusações. Sobre `00`–`04`, antes das correções deste ciclo, eram **22/22**; as oito
  novas entraram com as notas de remediação (`larguraDaSidebar`, os três `->sidebarWidth()` e os
  dois pontos de `HasSidebar.php`).

  Escopo e comando, um só, sem lista escolhida à mão:

  ```
  grep -rhoE '[A-Za-z0-9_/.-]+\.(php|blade\.php|md|css|json|xml):[A-Za-z_$][A-Za-z0-9_]*(\(\))?:[0-9]+' wikis/specs/feat/layout-compact/layout-compact/0[0-4]*.md | sort -u
  ```

  → **30** citações distintas; para cada uma, `sed -n "{linha}p" {path}` **contém** o símbolo, que é
  o critério de `.ai/rules/specs.md:46`. **30 ok, 0 erro, 0 caminho que não resolve.**

  O `CitacoesDeCodigoTest` **exclui `wikis/specs/**` por decisão registrada**, então ele nunca
  conferiu esta wiki; a declaração anterior de "21/21 ok" apontava um gate que não cobre este glob
- [x] `feature-quality-gate` (step 8) — **ciclo 1 REPROVADO → especificação**, ver `06-relatorio-qa.md`; achados fechados em 2026-09-21
- [ ] PR

## Commits

- `:memo: docs(wiki): requisito da opcao de layout compacto` — `456f66c`
- `:memo: docs(wiki): fecha duas ambiguidades do layout compacto` — `ae92423`
- `:memo: docs(wiki): ADRs do layout compacto, com os numeros medidos` — `c862361`
- `:sparkles: feat(kit): a escala de densidade do layout, com os valores medidos` — `589110c`
- `:sparkles: feat(settings): a densidade do layout nos tres lugares do settings` — `d82eccf`
- `:sparkles: feat(kit): emite a densidade no render hook, fora de cascade layer` — `3c6d5f6`
- `:white_check_mark: test(kit): a cadeia inteira da densidade do layout` — `c2189d7`
- `:memo: docs: o layout compacto, com os numeros medidos e o preco declarado` — `e98f436`
- `:wrench: chore(docs): sincroniza os contadores e a citacao que a linha nova deslocou` — `022e027`
- `:memo: docs(roadmap): o que o kit olhou e decidiu adiar, com o motivo e a medicao` — `bf6e799`
- `:memo: docs(wiki): o PRD, os casos de teste e o progresso do layout compacto` — esta rodada
