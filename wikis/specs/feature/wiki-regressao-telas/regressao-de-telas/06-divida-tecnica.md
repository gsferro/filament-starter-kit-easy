# Dívida Técnica — encontrada na regressão de telas

> Atende RQ-07: *"use esse momento também para identificar possiveis dividas tecnicas para
> correção, antes das proximas evoluções"*.

**Na entrega original, uma dívida foi paga: DT-03**, por decisão explícita do usuário depois de o
quality gate expor que ela bloqueava o `--tia` — a feature que motivou o upgrade para Pest 5. As
outras nove ficaram abertas: o requisito posiciona a correção *antes das próximas evoluções*, o
que as coloca **depois** daquela entrega.

Confirmação que valia então: `git diff main --stat` **não tocava `app/`**. DT-03 vivia só em
`tests/`.

**Estado em 2026-08-23: ledger fechado, 11 de 11.** Oito pagas com código (DT-01, DT-02,
DT-03, DT-04, DT-06, DT-07, DT-10, DT-11), uma fechada **sem** código porque a correção que
ela prescrevia era no-op (DT-08), uma fechada com resíduo declarado — um título de plugin sem
ponto de extensão (DT-09) — e uma **recusada com medição** (DT-05). DT-03,
DT-10 e DT-11 numa primeira rodada; DT-01, DT-02 e DT-07 numa segunda; DT-09 quase inteira paga
por um commit que não atualizou esta página. **DT-08 fechada numa terceira rodada — e sem
conserto**, porque a correção de duas linhas que ela mesma passou a prescrever é no-op: a facade
`FilamentView` re-registra os hooks em toda instância nova, então descartar o `ViewManager` não
remove nada. O que ela chamava de vazamento é o mecanismo deliberado do "Voltar ao topo", e a
guarda contra a regressão já existia. **Fechadas numa quarta rodada, em 2026-08-23**: DT-04 paga com o smoke do `/app` em
`tests/Tenancy`, DT-06 paga com o aquecimento pelo kernel — e nela a correção da v0.18.4 se
revelou **errada**, porque o falso contraste não era cache frio, era emulação de tema vazando
entre cenários. DT-05 **recusada com medição**: em ~20 episódios de teste desta auditoria,
nenhuma falha veio de seletor frágil.

**O ledger está zerado.** Reabrir qualquer uma exige um exemplo novo, não uma estimativa — e
cinco das prescrições originais estavam erradas, então a próxima também merece ser medida
antes de ser aplicada.

Cada seção diz o que foi **medido**, e nomeia as prescrições originais que estavam erradas — são
**cinco** até aqui: DT-10 (uma linha que piorava em silêncio), DT-01 (a11y dependendo de JS),
DT-02 duas vezes (tema que não existe, e `color()` que não resolve) e DT-08 (duas linhas
inócuas). O padrão é o mesmo: remédio escrito a partir do que se esperava do vendor, sem abrir o
`vendor/`.

Nenhuma das seis correções tocou `app/`: elas vivem em `tests/`, `config/`, `phpunit.xml`,
`resources/css/filament/` e `resources/views/vendor/`.

## Resumo

Sete dívidas nasceram da rodada de CT-B; três (**DT-08**, **DT-09**, **DT-10**) vieram do
`feature-quality-gate` depois, e duas entradas (**DT-01**, **DT-02**) foram **corrigidas** por
ele. O relatório está em `07-relatorio-qa.md`.

> **2026-08-22.** **DT-10 paga** — e a correção prescrita aqui estava **errada**: a de uma linha
> teria piorado o problema em silêncio. Ver a seção dela. **DT-11**: o diagnóstico original se
> confirmou (Xdebug 3.4.4 presente, PCOV ausente) e o PCOV 1.0.12 foi instalado nesta data — a
> seção registra a medição, e por que uma medição minha intermediária estava errada.
>
> **2026-08-22, segunda rodada.** **DT-01, DT-02 e DT-07 pagas.** Com isso o CT-B09 perdeu o
> `->todo()` e passou a medir acessibilidade em todo run. Duas correções prescritas aqui
> estavam **erradas** e estão anotadas nas respectivas seções (a de DT-02 aponta um lugar que
> não existe neste kit; a de DT-07 perderia cobertura). A guarda de DT-07 achou **dois
> defeitos reais no inventário** no primeiro run — um deles uma rota que não existe nesta
> suíte e que estava sendo "coberta" havia semanas. E a revisão das cinco restantes achou
> **DT-09 quase inteira já paga** pelo commit `5511a0a`, sem ninguém ter atualizado esta
> tabela.

| ID | Dívida | Severidade | Custo estimado | Onde |
|----|--------|-----------|----------------|------|
| ~~**DT-03**~~ | ~~Helpers de teste declarados dentro de arquivos de teste~~ | ~~bloqueante~~ | **PAGA** | `tests/Pest.php` |
| ~~**DT-01**~~ | ~~Botão *Clear Cache* sem texto acessível (a11y critical)~~ | ~~relevante~~ | **PAGA** | `resources/views/vendor/` |
| ~~**DT-08**~~ | ~~Render hook de plugin vaza entre painéis no mesmo processo PHP~~ | ~~relevante~~ | **FECHADA** — aceita por escrito, nada a consertar no kit | `vendor` (upstream) |
| ~~**DT-04**~~ | ~~Assimetria de cobertura HTTP: `/app` quase sem smoke de backend~~ | ~~relevante~~ | **PAGA** | `tests/Tenancy` |
| ~~**DT-06**~~ | ~~Suíte `tests/Kit` leva ~14 min em série~~ | ~~relevante~~ | **PAGA** — e o defeito era outro | `tests/Browser` |
| ~~**DT-02**~~ | ~~Contraste 4.25:1 no indicador de ambiente (a11y serious)~~ | ~~cosmética~~ | **PAGA** | `resources/css/filament/kit.css` |
| ~~**DT-05**~~ | ~~Nenhum `data-testid` nas telas do kit~~ | ~~cosmética~~ | **RECUSADA**, com medição | `app/Filament` |
| ~~**DT-07**~~ | ~~Inventário de telas dos CT-B é array escrito à mão~~ | ~~cosmética~~ | **PAGA** (por reconciliação, não por derivação) | `tests/Pest.php` + `tests/Kit` |
| ~~**DT-09**~~ | ~~Telas de `/infra` misturam inglês e português~~ | ~~cosmética~~ | **FECHADA** — resta 1 título sem ponto de extensão | `vendor` (upstream) |
| ~~**DT-10**~~ | ~~A suíte de testes escreve no `storage/logs` real~~ | ~~cosmética~~ | **PAGA** | `config/logging.php` + `phpunit.xml` |
| ~~**DT-11**~~ | ~~Sem PCOV: o `--tia` roda com Xdebug e fica impraticável em série~~ | ~~relevante~~ | **PAGA nesta máquina** | ambiente (não é código) |

---

## DT-03 — Helpers de teste declarados dentro de arquivos de teste · ✅ **PAGA**

**Severidade**: era **bloqueante**
**Como foi encontrada**: rodando o baseline do upgrade em dois modos. Em série a suíte é
213/213 verde; em `--parallel`, **7 erros**.

### O problema

```
Call to undefined function usuarioCom()
Call to undefined function noPainelDa()
Call to undefined function pivotDePapeis()
```

Três funções são declaradas dentro de um arquivo de teste e **chamadas de outro**:

| Função | Declarada em | Chamada de |
|---|---|---|
| `usuarioCom()` | `tests/Kit/PaineisTest.php:24` | `tests/Kit/AdminDaOrganizacaoTest.php:22` |
| `noPainelDa()` | `tests/Tenancy/AdminDaOrganizacaoTest.php:76` | `tests/Tenancy/ConviteUsuarioExistenteTest.php:129,267` |
| `pivotDePapeis()` | `tests/Tenancy/ConviteTenancyTest.php:54` | `tests/Tenancy/ConviteUsuarioExistenteTest.php:173,213,238,407` |

Em PHP, função é global no processo. Quando o Pest carrega **todos** os arquivos, a declaração
de um vaza para o vizinho e tudo passa — o acoplamento fica invisível. Em qualquer execução
**parcial**, a declaração não está lá.

Reproduz sem `--parallel`:

```bash
vendor/bin/pest tests/Kit/AdminDaOrganizacaoTest.php tests/Tenancy/ConviteUsuarioExistenteTest.php
# 13 testes, 6 passados, 7 errors
```

### Por que é bloqueante

O `--tia` do Pest 5 roda, **por definição**, apenas os arquivos afetados pelo diff. Ou seja: a
feature que motivou o upgrade para Pest 5 é inutilizável neste projeto enquanto a dívida
existir. O mesmo vale para `--parallel`, e para qualquer `pest tests/Algum/ArquivoTest.php`
isolado — que é o comando mais usado no dia a dia de desenvolvimento.

**Não é regressão do upgrade.** A dívida é anterior; o Pest 4 do projeto simplesmente nunca foi
rodado em paralelo, então ela nunca apareceu.

### A ironia

`tests/Pest.php:116-123` documenta **exatamente** esta armadilha:

> *"Aqui, e não dentro de um arquivo de teste, porque em Pest as funções são globais no
> processo: (…) helper declarado num arquivo só desaparece quando você roda o OUTRO arquivo
> isolado"*

A regra está escrita. Três arquivos a violam.

### Correção

Mover as três funções para a seção de helpers compartilhados de `tests/Pest.php` e apagar as
declarações locais. Nenhuma mudança de assinatura, nenhuma mudança de comportamento.

Cuidado com **`usuarioCom()` × `usuarioDoKit()`**: são quase a mesma função. `usuarioCom()`
aceita `?string $papel` (nulo = sem papel) e gera e-mail com `fake()`; `usuarioDoKit()` exige
papel e recebe e-mail fixo. A correção óbvia é fundir as duas em `usuarioDoKit(?string $papel)`
e ajustar os dois chamadores — mas isso toca os testes de quatro features, e a decisão é de
quem for pagar a dívida.

### Resolução — aplicada nesta branch

Movidos para a seção de helpers compartilhados de `tests/Pest.php`: `usuarioCom()`,
`noPainelDa()` e `pivotDePapeis()`.

**Dois clones desapareceram no processo** — e eles são a parte instrutiva. Existiam só para
escapar da colisão de redeclaração, cada um idêntico ao original:

| Clone | Era idêntico a | Chamadores atualizados |
|---|---|---|
| `pivotDePapeisDaOrganizacao()` (`Tenancy/AdminDaOrganizacaoTest.php`) | `pivotDePapeis()` | 12 |
| `entrarNoPainelDa()` (`Tenancy/ConviteEmMassaTenancyTest.php`) | `noPainelDa()` | 2 |

O padrão trocava *um erro que estoura* por *duas funções idênticas que ninguém percebe*. Está
proibido na rule nova `.ai/rules/testes.md`.

**Guarda automática**: `tests/Kit/HelpersDeTesteTest.php`. Ela usa `token_get_all()` e não regex
— a menção a `conviteCom()` dentro de um docblock é comum nesta suíte, e um regex a contaria como
chamada. Guarda com falso positivo é pior que guarda nenhuma, porque ensina o time a ignorá-la.
Verificada nos dois sentidos: verde no estado atual, e vermelha nomeando o arquivo quando uma
violação é injetada de propósito.

A regra que a guarda enforça **não** é "nenhum helper local" — os 16 helpers de arquivo único
continuam onde estão, como `tests/Pest.php` determina. O defeito é o uso **cruzado**.

### Resultado medido

| Comando | Antes | Depois |
|---|---|---|
| `pest --group=kit --parallel` | 206/213, **7 erros** | **214/214**, 727 asserções |
| Tempo do `--parallel` | 249 s (com erros) | **196 s** |
| `pest --group=kit` (série) | 213/213, 818 s | 214/214, ~690 s |
| `pest --parallel --tia` | **abortava** | roda, grafo criado |

**Um bloqueio extra apareceu ao testar o `--tia`, e também foi resolvido**: `tests/Unit/ExampleTest.php`
era o scaffolding class-based do Laravel, e o `--tia` **aborta a execução inteira** ao encontrar
uma classe PHPUnit (*"Encountered PHPUnit class … Convert it to a Pest test, or run without
Tia"*). Um único arquivo esquecido desliga o Test Impact Analysis para todo o projeto. Convertido
para Pest, com o motivo no cabeçalho do arquivo.

Também configurado em `tests/Pest.php`: `pest()->tia()->defaultBranch('main')->locally()`. O
default do TIA é `master`, e sem isso ele pede `git remote set-head origin --auto` uma vez por
clone; a linha vale para todo mundo de uma vez. O `locally()` liga o TIA no desenvolvimento e o
desliga sozinho em CI, que é o que a doc do Pest recomenda.

---

## DT-01 — Botão *Clear Cache* sem texto acessível · ✅ **PAGA**

**Severidade**: relevante (a11y **critical** pelo axe-core)
**Como foi encontrada**: `assertNoAccessibilityIssues()` em **`/infra`**.

> **Correção de proveniência (QA-02 do `07-relatorio-qa.md`).** A primeira escrita desta
> entrada dizia `/admin`, porque foi ali que a sonda registrou o botão. Estava errado: o
> `FilamentClearCachePlugin` é registrado **só** em `InfraPanelProvider.php:198`, e o botão
> apareceu no `/admin` por contaminação de processo — ver **DT-08**. Verificado: `/admin`
> isolado tem **0** ocorrências de `clear-cache-button`.
>
> Isto importa para quem for pagar a dívida: verificando em `/admin`, não encontraria o botão
> e concluiria que já estava resolvida.

### O problema

```html
<button x-tooltip="{ content: 'Clear Cache', ... }"
        class="fi-icon-btn fi-size-md flex-shrink-0 w-10 h-10 rounded-full"
        type="button" wire:click="clear" wire:key="clear-cache-button">
```

Sem texto interno, sem `aria-label`, sem `title`, sem `<label>`. O rótulo existe **só** dentro
do `x-tooltip`, que o Alpine renderiza em tempo de hover — leitor de tela nunca o alcança.

Diagnóstico do axe-core: *"Buttons must have discernible text"*. Para quem usa leitor de tela,
o botão é anunciado como *"botão"*, sem nada mais — e ele **limpa o cache da aplicação**.

**Origem**: `cms-multi/filament-clear-cache`, em `vendor/`.

### Correção

Não editar `vendor/`. Duas saídas:

1. **PR upstream** — acrescentar `aria-label` ao Blade do pacote. Certo, mas fora do controle
   do cronograma do kit.
2. **`renderHook` no painel** com um trecho de JS que preenche o `aria-label` a partir do
   `x-tooltip`. Funciona já, e some quando o upstream corrigir.

O kit já injeta comportamento neste pacote — `KitServiceProvider::configureClearCacheButton()`
acrescenta os comandos extras (`config:clear`, `view:clear`, `modelCache:clear`). É o lugar
natural para o `aria-label` também.

**Verificação**: remover `->todo()` do CT-B09 e rodar `pest --testsuite=Browser`.

**Atenção**: o CT-B09 é um lote, e o lote **aborta na primeira falha** — o `/app` já falha no
contraste, então o `/infra` nunca é avaliado e esta `critical` não aparece. Separar o cenário
por painel antes de usá-lo como verificação. Ver QA-03 do `07-relatorio-qa.md`.

### Resolução — 2026-08-22

Nenhuma das duas saídas prescritas acima. A correção é uma **cópia da blade do pacote** em
`resources/views/vendor/filament-clear-cache/livewire/clear-cache-button.blade.php`, com uma
propriedade a mais:

```blade
:label="__('filament-clear-cache::general.clear_cache')"
```

**Por que não o `renderHook` com JS que a seção prescrevia.** Ele faria a acessibilidade
depender de JavaScript — o `aria-label` não existiria no HTML servido, e nenhum teste de
servidor poderia prová-lo. O leitor de tela leria o botão corretamente só depois de o Alpine
rodar. Trocar um defeito de a11y por um defeito de a11y condicionado a JS não é conserto.

**Por que a propriedade e não um `aria-label` escrito à mão.** Quem converte `label` em
atributo é o próprio componente do Filament, com escape:
`vendor/filament/support/resources/views/components/icon-button.blade.php:85`. E na linha 94 do
mesmo arquivo o `title` sai **nulo** quando existe tooltip — então não há rótulo duplicado, que
é o outro achado de a11y que uma correção descuidada produziria.

**Por que a sobreposição funciona, lido no vendor** e não suposto: o pacote é um
`PackageServiceProvider` com `hasViews()` (`FilamentClearCacheServiceProvider.php:18`), a view é
resolvida por nome em `Http/Livewire/ClearCache.php:109`, o `bootPackageViews()` chama
`loadViewsFrom()` (`vendor/spatie/laravel-package-tools/src/Concerns/PackageServiceProvider/ProcessViews.php:18`)
e o `loadViewsFrom()` do framework registra `resources/views/vendor/<namespace>` **na frente** do
diretório do pacote (`vendor/laravel/framework/src/Illuminate/Support/ServiceProvider.php:212-226`).
O namespace é o `shortName()` do pacote, `filament-clear-cache`
(`vendor/spatie/laravel-package-tools/src/Concerns/Package/HasViews.php:22`).

É o caminho que o kit já usa para tela de vendor: nove pacotes em `resources/views/vendor/`.

**Custo novo que a cópia cria, e como ele fica visível.** Publicar view de terceiro congela a
versão do dia — é o mesmo custo que o cabeçalho de `resources/css/filament/kit.css` cobra ("quatro
cópias que quebram a cada upgrade"). Por isso `tests/Kit/BotaoLimparCacheTest.php` tem três
casos, e um deles **diffa a cópia contra o arquivo do `vendor/`**: removido o cabeçalho de
comentário e a linha da propriedade, os dois arquivos têm de ser idênticos. Upgrade que mexa na
blade fica vermelho nomeando a divergência, em vez de o kit servir para sempre a versão antiga.

**Medido**:

| O que | Antes | Depois |
|---|---|---|
| `aria-label` no HTML de `GET /infra` | ausente | `aria-label="Clear Cache"` |
| `tests/Kit/BotaoLimparCacheTest.php` | não existia | 3 casos, 5 asserções, 4,5 s |
| CT-B09 (`assertNoAccessibilityIssues` no `/infra`) | `->todo()` | verde, um cenário por painel |

A guarda foi vista **falhando**: renomeando a cópia (para o vendor voltar a responder), 2 dos 3
casos ficam vermelhos — o smoke do `/infra` e o que assere o caminho resolvido da view.

**O que ficou de fora, de propósito**: o rótulo continua em inglês. A chave usada é a mesma do
tooltip, então o texto visível e o acessível estão em paridade; traduzir é publicar
`lang/vendor/filament-clear-cache/pt_BR/`, e isso é **DT-09**.

---

## DT-04 — Assimetria de cobertura: o painel `/app` quase sem smoke de backend

**Severidade**: relevante
**Como foi encontrada**: inventariando a cobertura existente para escrever
`04-casos-de-teste.md`.

### O problema

| Painel | Rotas com smoke HTTP em `tests/Kit` |
|---|---|
| `/infra` | 15 (`PaginasInfraTest.php:51-68`) |
| `/admin` | 3 (`PaginasInfraTest.php:70-78`) |
| `/app` | **1** — só o `GET /app` genérico de `PaineisTest.php:115-119` |

O painel de negócio — o único que o consumidor do kit usa todo dia — é o menos coberto dos
três. `/app/convites`, `/app/projetos`, `/app/users`, `/app/convites-recebidos` e
`/app/meu-perfil` não tinham nenhuma visita testada antes desta wiki.

### O que esta wiki resolve, e o que não

Os CT-B01 cobrem as 9 telas de `/app` em browser real, que é **mais** do que smoke HTTP prova.
Mas o desequilíbrio na suíte de backend permanece, e ele importa: browser é caro e fica num job
de CI separado; o smoke HTTP é o que roda em `composer test:kit`, o comando de resposta rápida
depois de um `kit:update`.

### Correção

Acrescentar a `tests/Kit/PaginasInfraTest.php` (ou arquivo novo `PaginasAppTest.php`) o dataset
das telas de `/app`, no mesmo formato dos dois blocos que já existem. Cerca de 10 linhas.

### Revisão — 2026-08-22: **continua verdadeira, e a correção prescrita não serve**

A assimetria segue existindo: em `tests/Kit` não há dataset de telas de `/app`. O que há são três
visitas incidentais (`CabecalhoDoMenuDoUsuarioTest:150`, `ConviteTest:372`, `VoltarAoTopoTest:65`,
todas ao `/app` genérico) e uma asserção de **403** em `AdminDaOrganizacaoTest:26`.

Mas a contagem da tabela (`/app`: 1) subestima o estado real, porque olhou só `tests/Kit`. Com
`tests/Tenancy` na conta:

| Rota | Onde |
|---|---|
| `/app/{t}` | `AdminDaOrganizacaoTest:77,409`, `IdentidadeVisualTenancyTest:116` |
| `/app/{t}/users` | `AdminDaOrganizacaoTest:78` (200) e `:410` (403) |
| `/app/{t}/convites` | `AdminDaOrganizacaoTest:411` (403) |
| `/app/{t}/projetos` | `AnexosPrivadosTest:136,170,312`, `ImportExportTenancyTest:149,173,194,255` |

Falta smoke HTTP de `/app/{t}/meu-perfil`, `/app/{t}/convites-recebidos`,
`/app/{t}/convites/create`, `/app/{t}/users/create` e `/app/{t}/two-factor-authentication`.

**E a correção prescrita colocaria o dataset no lugar errado.** Em `tests/Kit` a tenancy está
desligada, e `UserResource`/`ConviteResource` do painel de negócio se escondem sem ela: um dataset
ali responderia **403**, provando permissão em vez de "a tela abre". É o mesmo defeito que a
guarda de DT-07 acabou de encontrar no inventário dos CT-B — 403 e 404 passam por asserções
frouxas. O destino certo é `tests/Tenancy`, com organização, e o formato certo é o dos blocos que
já existem lá.

Custo revisado: as ~10 linhas continuam sendo ~10 linhas, mas em `tests/Tenancy` e com o arranjo
de organização que aqueles casos já têm no `beforeEach`. A estimativa de ~1 h segue razoável.

---

## DT-06 — A suíte `tests/Kit` leva ~14 minutos em série

**Severidade**: relevante
**Como foi encontrada**: medindo o baseline do upgrade.

### Os números

| Modo | Tempo | Resultado |
|---|---|---|
| Série | **818 s** (13 min 38 s) | 213/213 verde |
| `--parallel` | **249 s** (4 min 9 s) | 206/213 — 7 erros de DT-03 |

O comando que **funciona** é o lento. O rápido está quebrado por DT-03.

Uma suíte de 14 minutos não é rodada durante o desenvolvimento — é rodada no CI, ou depois. Isso
desloca a descoberta de regressão para horas depois da causa, que é justamente o problema que
`composer test:kit` foi criado para resolver.

### Correção

**Dependia de DT-03, que foi paga.** Com `--parallel` de volta, o tempo caiu de **818 s para
196 s** (4,2×) — a suíte volta a caber no ciclo de desenvolvimento.

O que resta: 196 s ainda é muito para rodar a cada passo. Agora **vale medir** com
`pest --profile`, que aponta o cenário lento em vez de adivinhar. Otimizar antes de medir é
adivinhação — e era por isso que esta dívida esperava a DT-03.

### Revisão — 2026-08-22: **o número que a define ficou irrelevante, e o `--profile` deixou de ser o próximo passo**

O título desta dívida é *"~14 min em série"*, e série deixou de ser como alguém roda a suíte. Duas
dívidas pagas depois, existem dois caminhos rápidos, os dois medidos:

| Caminho | Tempo | Desbloqueado por |
|---|---|---|
| `--parallel`, só o grupo `kit` | 196 s | DT-03 |
| `--parallel`, `Unit,Feature,Kit,Tenancy` — **medido nesta rodada** | **249 s**, 536 casos, 1.469 asserções | DT-03 |
| `--tia`, na sequência, sem mudança de código | **6,4 s** de suíte (18,4 s de parede) | DT-11 (PCOV) |

Os 818 s em série só aparecem em CI, e em CI 14 min de suíte completa é o comportamento correto —
o pipeline **deve** rodar tudo, e é por isso que `pest()->tia()->locally()` desliga o TIA lá.

**Não re-medi a série**, de propósito: são 14 min de máquina para confirmar um número que já não
governa decisão nenhuma. Registro isso em vez de repetir o número antigo como se fosse novo.

**A correção prescrita acima (`pest --profile`) é a próxima etapa errada.** Ela otimizaria o
caminho de 196 s enquanto o caminho de 6,4 s já existe na máquina de quem desenvolve. O gargalo
real mudou de lugar duas vezes: primeiro para os CT-B (browser em série, 168 s, e que **não**
convivem com `--parallel`), e agora para a **primeira** execução num clone novo — que é a que
paga a compilação dos componentes Livewire e é a única em que a suíte de navegador nasce
vermelha. Isto foi reproduzido nesta rodada, neste worktree limpo: o primeiro
`--testsuite=Browser` derrubou 2 dos 37 cenários (um `Timeout 45000ms` no primeiro arquivo e um
falso achado de contraste no `/app`, com todo texto da página reportado a ~1,05:1); a **segunda**
execução, sem mudar uma linha, deu **37 casos, 32 passados, 5 skipped, 168 s**. É exatamente o
disfarce que `.ai/rules/testes-browser.md` descreve — "tem o formato de teste instável" — e custou
duas execuções para separar.

**Recomendação revisada**: esta dívida deixa de ser sobre tempo e passa a ser sobre **aquecimento
determinístico** do primeiro run (o `composer test:browser` já embute `npm run build` e
`view:cache`; falta a compilação dos componentes Livewire, que a rule manda pagar com um
`$this->get()` no `beforeEach`). Severidade: continua **relevante**, e agora com um sintoma
observável em vez de um número.

---

## DT-02 — Contraste 4.25:1 no indicador de ambiente · ✅ **PAGA**

**Severidade**: cosmética (a11y **serious** pelo axe-core)
**Como foi encontrada**: `assertNoAccessibilityIssues()` em `/admin`, **no tema claro**.

### O problema

`.environment-indicator` renderiza `#e60076` sobre `#fdf2f8` a 12 px. Contraste **4.25:1**;
mínimo WCAG AA para texto pequeno é **4.5:1**.

> **Vale só no tema claro** (QA-04 do `07-relatorio-qa.md`). O elemento é
> `fi-text-color-600 dark:fi-text-color-400`: no escuro usa a cor 400 e **atravessa** o
> limiar. Verificado — `visit('/admin')->inDarkMode()->assertNoAccessibilityIssues()`
> **passa**, e sem `inDarkMode()` **falha**. A sonda que originou esta entrada só mediu no
> claro, que é justamente o eixo que o requisito (RQ-06) pedia distinguir.

Falta pouco — e é o suficiente para o badge ficar ilegível em tela com brilho baixo ou para
quem tem baixa visão. O badge diz em que ambiente a pessoa está, o que o torna exatamente o
tipo de informação que não deveria depender de boa visão.

**Origem**: `pxlrbt/filament-environment-indicator`, cor definida via `--color-*` inline.

### Correção prescrita aqui — **certa no degrau, errada nos dois conselhos**

O degrau estava certo: `--color-700` atravessa o limiar. As duas frases em volta, não.

1. **"uma regra de CSS no tema do painel"** — o kit **não tem** tema Filament. `viteTheme()`
   não é usado em nenhum dos três painéis, e é justamente por isso que existe
   `KitServiceProvider::configureCorrecoesDeCss()` registrando CSS por
   `FilamentAsset::register()`. A rule `.ai/rules/css-filament.md` diz isso desde antes desta
   dívida. Quem seguisse a frase iria escrever a regra em `resources/css/app.css` — que o painel
   **não carrega**, e a correção falharia em silêncio, que é exatamente o modo de falhar
   documentado naquela rule.

2. **"o plugin já aceita cor customizada — vale checar `EnvironmentIndicatorPlugin::color()`"**
   — checado, e não serve. `color()` recebe uma paleta inteira
   (`EnvironmentIndicatorPlugin.php:194-199`) e o que o badge faz com ela é sempre o mesmo par:
   texto no degrau 600 sobre fundo no degrau 50. Trocar de cor troca o **significado** (a cor
   codifica o ambiente) e não resolve o contraste — **três das quatro** cores que o próprio
   plugin escolhe reprovam no 600. Medido, com `Color::convertToRgb()` do Filament:

   | Cor | ambiente | 600 sobre 50 | 700 sobre 50 |
   |---|---|---|---|
   | Pink | `local`, `testing` (default) | **4,16:1** | 5,41:1 |
   | Orange | `staging` | **3,39:1** | 4,92:1 |
   | Red | `production` | **4,36:1** | 5,87:1 |
   | Blue | `development` | 4,82:1 | 6,28:1 |

   Não existe escolha de cor que conserte isto. O `Orange` do staging é **pior** que o Pink que
   originou a dívida.

### Resolução — 2026-08-22

Uma linha em `resources/css/filament/kit.css`, o arquivo que o `configureCorrecoesDeCss()` já
registra nos três painéis:

```css
.environment-indicator.fi-badge { --text: var(--color-700); }
```

**Por que `--text` e não `color`**, lido no tema compilado do Filament
(`vendor/filament/filament/dist/theme.css`):

```css
.fi-badge.fi-color { background-color: var(--color-50); color: var(--text); }
.fi-text-color-600 { --text: var(--color-600); }
```

A blade do plugin emite `fi-badge fi-color fi-text-color-600 dark:fi-text-color-400`
(`badge.blade.php:4-11`). Sobrescrever a **variável** mantém tudo o mais intacto e, o que
importa mais, **não toca o tema escuro**: no escuro a mesma regra usa `color: var(--dark-text)`,
que é a variável preenchida por `dark:fi-text-color-400` — outra variável. O QA já havia
verificado que o escuro passava (QA-04); a correção precisava não estragá-lo, e por construção
não pode.

Duas classes no seletor (`0,2,0`) contra a classe única de `.fi-text-color-600` (`0,1,0`): vence
por **especificidade**, não por ordem de carregamento — que entre assets registrados não é
garantida. Este detalhe não é preciosismo: o bloco vizinho no mesmo arquivo existe porque a
ordem de assets *importou* uma vez.

### Verificação — o que foi medido, e uma divergência que fica registrada

`tests/Kit/ContrasteDoIndicadorDeAmbienteTest.php`, 3 casos, 14 asserções, 3,3 s. Ele fecha a
corrente em três elos, todos em PHP, sem navegador:

1. **lê o degrau do `kit.css`** por regex (sobre o arquivo com os comentários removidos — o
   cabeçalho do bloco *cita* `600/50` e `700/50`, e citar não é aplicar) e recalcula o contraste
   das quatro paletas. Redigitar `700` no teste faria ele concordar consigo mesmo;
2. **assere que o degrau 600 reprova** em Pink, Orange e Red. Se este caso ficar vermelho, o
   Filament mudou a paleta e o bloco do `kit.css` pode sair — é o que impede a regra de virar
   CSS supérfluo que ninguém ousa remover;
3. **renderiza a blade do vendor** e confere que as classes do seletor ainda estão lá. Sem este
   elo, um upgrade que renomeie o badge transformaria a regra em CSS morto sem mover teste
   nenhum.

Visto **falhando**: baixando o degrau para 600 no `kit.css`, o caso 1 fica vermelho com
`Pink 600 sobre 50 não alcança 4,5:1 / Failed asserting that 4.16 is equal to 4.5 or is greater`.

E no navegador: o CT-B09 perdeu o `->todo()` e passou a rodar um cenário por painel, com
`assertNoAccessibilityIssues()`.

> **Divergência de medição, registrada em vez de escondida.** O axe-core reportou **4,25:1** e o
> meu recálculo dá **4,16:1** para o mesmo par de cores (`#e60076` sobre `#fdf2f8` — os hexes
> conferem com os do relatório de QA). Não consegui reconciliar os dois números, e **não vou
> escrever uma explicação que não medi**. O veredito não muda: os dois estão abaixo de 4,5:1, e
> os dois cruzam o limiar no degrau 700. Quem for mexer nisto deve saber que a diferença existe
> e que a fonte dela é desconhecida.

---

## DT-05 — Nenhum `data-testid` nas telas do kit

**Severidade**: cosmética
**Como foi encontrada**: escrevendo os CT-B. `grep -rn "data-testid\|data-test=" app/ resources/`
não retorna nada.

### O problema

Os seletores dos CT-B são, hoje:

| Tipo de seletor | Exemplo | Estabilidade |
|---|---|---|
| `id` gerado pelo Filament | `#form\.email` | frágil — convenção de framework, muda entre majors |
| Texto visível | `Login`, `Faça login`, `Painel de Controle` | frágil — quebra ao traduzir ou reescrever rótulo |
| `aria-label` | `[aria-label="Mudar para tema escuro"]` | razoável — é contrato de acessibilidade |

Já custou nesta rodada: o CT-B07 foi escrito assertando `Dashboard` e falhou porque o texto real
é `Painel de Controle` (tradução pt_BR do Filament). Um `data-testid` no heading teria evitado.

### Correção

`->extraAttributes(['data-testid' => '...'])` nos elementos que os testes tocam — não em todos.
Começar pelos três campos de login e pelo heading do dashboard.

**Contra-argumento honesto**: o kit tem 40 pacotes Filament, e `data-testid` só cobriria as
telas próprias. Nas de plugin o problema continua. A dívida é real, mas o retorno é parcial —
por isso cosmética, e não relevante.

### Revisão — 2026-08-22: **verdadeira, e é a que eu atacaria por último**

Confirmado: `grep -rn "data-testid\|data-test=" app/ resources/` continua **vazio**.

O contra-argumento da própria seção ficou mais forte, não mais fraco. Nesta rodada os seletores
que de fato quebraram nada tinham a ver com `data-testid`:

- em DT-01, o problema era `aria-label` **ausente** — e a correção foi acrescentar um, que é
  contrato de acessibilidade **e** seletor estável de graça. A tabela desta seção já classifica
  `aria-label` como *"razoável"*, e é a única das três linhas que não é frágil;
- em DT-07, o que quebrou a cobertura foram **rotas**, não seletores. `data-testid` não teria
  ajudado.

Ou seja: o caminho que rende nos dois eixos ao mesmo tempo é continuar pagando a11y, não plantar
`data-testid`. Cada `aria-label` que a a11y exige é um seletor estável que esta dívida quer — e o
inverso não vale: `data-testid` não melhora acessibilidade nenhuma.

Custo: as ~1 h seguem válidas para os quatro elementos citados. A recomendação é **não pagar
sozinha**: pagar como subproduto de a11y, e só recorrer a `data-testid` onde não houver rótulo
acessível que sirva.

---

## DT-07 — O inventário de telas dos CT-B é array escrito à mão · ✅ **PAGA**

**Severidade**: cosmética
**Como foi encontrada**: escrevendo os CT-B (e prevista em ADR-02 como risco aceito).

### O problema

`tests/Browser/TelasDoKitTest.php` lista as 52 rotas em quatro arrays literais. Tela nova que
alguém acrescente ao kit **não entra sozinha** — e a suíte segue verde, dando a impressão de
cobertura completa que não existe mais.

### Correção prescrita aqui — **implementada, medida e recusada**

> Derivar as rotas de `Filament::getPanel($id)->getPages()` + `getResources()`, filtrando as que
> exigem `{record}`. Cerca de 15 linhas.

A derivação foi escrita e rodada contra os três painéis. Ela funciona — e **substituir o array
por ela perde cobertura**, em duas frentes que só aparecem depois de rodar:

1. **Perde as telas que não são Page nem Resource do painel.** As três
   `/{painel}/two-factor-authentication` são rota registrada pelo Breezy: não estão em
   `getPages()` nem em `getResources()`, e a derivação simplesmente não as vê. Seriam 3 das 52
   telas saindo da cobertura em silêncio — o defeito que esta dívida existe para evitar,
   reintroduzido pela própria correção dela.
2. **Não sabe das exclusões deliberadas.** `/app/projetos` está fora por decisão, e
   `/app/exceptions` e `/admin/exceptions` existem por obrigação de registro
   (`.ai/rules/providers-filament.md`), com `registerNavigation(false)`. A derivação as
   traria de volta, então precisaria de uma lista de exceção escrita à mão — mais estado
   manual do que o array que ela ia substituir, e menos legível.

Somando: derivação + lista de extras + lista de exclusões, em lugar de uma lista. Pior nos
dois eixos que importam.

### Resolução — 2026-08-22: o defeito não era o array, era a ausência de conferência

O array continua escrito à mão, e mudou de lugar: `telasDoKit()` em `tests/Pest.php`, porque
agora **dois** arquivos o usam (`.ai/rules/testes.md` manda helper cruzado ir para lá). O que
entrou de novo é `tests/Kit/InventarioDeTelasTest.php`, que reconcilia a lista com a realidade
nos **dois sentidos**:

- tela que o painel **registra** e o inventário não lista → falha nomeando a URL;
- rota que o inventário **lista** e o roteador não resolve → falha nomeando a URL.

O segundo sentido é o menos óbvio e o mais perigoso: `visit('/rota-que-nao-existe')` abre a
página de 404, e `assertNoJavaScriptErrors()` **passa** nela. Uma tela renomeada por upgrade sai
da cobertura sem nada ficar vermelho.

O filtro de `{record}` e `{tenant}` que a seção pedia é feito pelo **próprio gerador de rotas**:
URL que exige parâmetro estoura `UrlGenerationException`, e é isso que a distingue de uma tela de
lista. Um regex sobre o padrão da rota faria o mesmo trabalho pior.

Fica em `tests/Kit` e não em `tests/Browser` de propósito: é comparação de listas, não precisa de
navegador, e assim roda no `composer test:kit` — o comando que se roda depois de um `kit:update`,
que é exatamente quando uma tela aparece ou desaparece.

### O que a guarda achou no primeiro run — dois defeitos reais

**1. `/infra/queue-monitors/pending` não existe nesta suíte.** O `getPages()` do resource só
registra a página de pendentes quando `config('queue.default') === 'database'`
(`vendor/croustibat/filament-jobs-monitor/src/Models/QueueJob.php:59-64`, chamado em
`.../Resources/QueueMonitorResource.php:386`), e o `phpunit.xml` fixa `QUEUE_CONNECTION=sync`.
A linha estava no inventário desde a rodada original, **visitando a página de 404 e passando**.
Removida, com o motivo escrito na própria lista.

**2. O comentário sobre `/app/projetos` estava errado.** Ele dizia que a tela "só existe com a
demo ligada". Não: o resource é descoberto sempre — o que a demo desliga é o `canAccess()`
(`app/Filament/App/Resources/Projetos/ProjetoResource.php:80-88`). A rota existe, e visitá-la
nesta suíte renderiza um **403** — e `assertNoJavaScriptErrors()` passa num 403 também. Ficou em
`FORA_DO_INVENTARIO` com o motivo medido e o apontamento para onde a tela é coberta de verdade
(`tests/BrowserTenancy/AnexosDoProjetoTest.php`, `ImportExportDeProjetosTest.php`).

**3. E três telas registradas que ninguém tinha notado**, agora nomeadas na lista de exclusão em
vez de esquecidas: os três hubs em cartões (`/app/hub-do-negocio`, `/admin/hub-de-administracao`,
`/infra/hub-de-infraestrutura`), que o kit entrega desligados (`KIT_HUB=false`) e que têm
cobertura própria mais forte que smoke.

Nota de rigor: o item 2 e o item 3 dizem que `assertNoJavaScriptErrors()` passa em 403 e em 404.
Isso já estava escrito no próprio inventário para `/app/convites` e `/app/users` — a novidade não
é o fato, é que agora **três outras rotas na mesma situação apareceram sozinhas**.

### Medido

| Comando | Resultado |
|---|---|
| `pest tests/Kit/InventarioDeTelasTest.php` | 6 casos (2 × 3 painéis), 6 asserções, 2,2 s |
| suíte de backend com as três guardas novas | 536 casos (era 524), 1.469 asserções, 249 s em `--parallel` |
| a mesma guarda, com `/admin/users` tirado do inventário | vermelha: *"Telas registradas no painel /admin e ausentes de telasDoKit(): /admin/users"* |
| a mesma guarda, com `/infra/tela-que-nao-existe` posto no inventário | vermelha: *"Rotas listadas em telasDoKit() que o roteador não resolve"* |
| `tests/Kit/HelpersDeTesteTest.php` (o helper novo é cruzado) | verde |

---

## DT-08 - Render hook de plugin vaza entre paineis no mesmo processo PHP

**Severidade**: relevante
**Como foi encontrada**: `feature-quality-gate`, ciclo 1 - QA-01. E a **causa raiz** do erro de
proveniencia de DT-01.

### O problema

`FilamentClearCachePlugin` esta registrado so em `InfraPanelProvider.php:198`. Ainda assim:

| Cenario, no mesmo processo PHP | ocorrencias de `clear-cache-button` em `/admin` |
|---|---|
| `/admin` e a primeira tela do processo | **0** - correto |
| `/infra` visitado antes, depois `/admin` | **9** - o painel renderiza um botao que nao e dele |
| `/infra` tres vezes seguidas | 9, 9, 9 - estavel; o vazamento e **cross-painel**, nao acumulo |

O `register()` do pacote registra um `renderHook` no painel, e o hook sobrevive a troca de painel
dentro do processo.

### Por que importa aqui

Os CT-B01-04 visitam os tres paineis **no mesmo processo**. Logo **o DOM que a suite valida nao e
o DOM que o usuario ve** - e essa e a premissa da suite inteira. Foi tambem o que fez a sonda
desta wiki atribuir o botao ao `/admin`, produzindo um erro de documentacao que so o quality gate
pegou.

### Impacto em producao hoje: nulo

Octane, FrankenPHP e RoadRunner **nao** estao instalados, entao cada request web e um processo
novo. O risco e latente: ligar um worker persistente o torna real, e a manifestacao seria um
botao de administracao aparecendo em painel que nao o registrou.

### Correcao

Duas frentes, e a ordem importa:

1. **Nos testes** (barata): isolar processo por painel nos CT-B de smoke, ou aceitar por escrito
   que o lote valida um DOM contaminado. Aceitar sem escrever e o que produziu QA-02.
2. **No pacote** (upstream): o `renderHook` deveria ser registrado em `boot()` e nao em
   `register()`, ou ser idempotente. Fora do controle do kit.

**Verificacao**: os cenarios da tabela acima, num teste efemero.

### Investigação — 2026-08-22: a hipótese estava vaga, e a causa é precisa

A frase *"o hook sobrevive à troca de painel dentro do processo"* descreve o sintoma. A causa é
uma cadeia de quatro elos, e cada um foi lido no `vendor/`:

1. O plugin chama `$panel->renderHook('panels::user-menu.before', $hook)` **sem escopo**
   (`vendor/cms-multi/filament-clear-cache/src/FilamentClearCachePlugin.php:61-69` — o terceiro
   parâmetro, `$scopes`, não é passado).
2. `Panel::renderHook()` com `$scopes === null` guarda o hook sob a chave de escopo **`''`**
   (`vendor/filament/filament/src/Panel/Concerns/HasRenderHooks.php:18-33`).
3. No **boot** do painel, `registerRenderHooks()` repassa cada hook para o registro global,
   mantendo o escopo `''` (`HasRenderHooks.php:35-43`, chamado em
   `vendor/filament/filament/src/Panel.php:102`).
4. O registro global é o `ViewManager`, e escopo `''` significa **"todo escopo"**: o
   `renderHook()` dele renderiza `$this->renderHooks[$name][''] ?? []` sem consultar escopo
   nenhum (`vendor/filament/support/src/View/ViewManager.php:74-96`, com o array em `:17`).

Ou seja: **o hook nunca foi "do painel infra"**. Ele é registrado globalmente no instante em que
o `/infra` boota, com um escopo que casa com qualquer painel, e nada o remove. Não é o painel que
vaza — é o registro que nunca foi escopado.

**A correção "no pacote" que esta seção prescrevia está errada.** Mover o `renderHook` de
`register()` para `boot()` não muda nada: o elo 3 já roda no boot, e o escopo `''` continuaria
`''`. O que consertaria é passar o **escopo** — `$panel->renderHook($nome, $hook, $panel->getId())`
—, e isso é uma linha diferente da prescrita, no mesmo arquivo.

**E o risco em produção é menor do que esta seção estimava.** O `ViewManager` é registrado com
`$this->app->scoped()`, não `singleton`
(`vendor/filament/support/src/SupportServiceProvider.php:104-107`). `scoped` é exatamente o
binding que os runtimes de processo longo descartam entre unidades de trabalho — o próprio
framework faz `$app->forgetScopedInstances()` no laço do worker de fila
(`vendor/laravel/framework/src/Illuminate/Queue/QueueServiceProvider.php:263`). Octane não está
instalado aqui, então não cito o listener dele; o que se pode afirmar do que está no `vendor/` é
que a escolha de `scoped` é a que cobre esse caso. A frase *"ligar um worker persistente o torna
real"* precisa então de medição antes de valer.

**Onde o vazamento é real hoje: o processo de teste.** Nada descarta `scoped` ali. E o
`fronteiraDeRequest()` de `tests/Pest.php:344-357`, que existe para simular a fronteira entre
requests, esquece **justamente** o `ViewManager` — descarta `ColorManager`, `AssetManager`,
`FilamentManager` e `SpotlightActionRegistry`, e não o registro de render hooks. Duas linhas
fechariam o buraco (`app()->forgetInstance(ViewManager::class)` e
`Facade::clearResolvedInstance(ViewManager::class)`; o accessor da facade `FilamentView` é a
própria classe, `vendor/filament/support/src/Facades/FilamentView.php:20-23`).

### Medição — 2026-08-22, segunda rodada: **as duas linhas não consertam nada**

A investigação acima acertou a cadeia e errou o remédio. As duas linhas propostas —
`forgetInstance(ViewManager::class)` + `Facade::clearResolvedInstance(...)` no
`fronteiraDeRequest()` — são **no-op para render hooks**. Medido antes de escrever:

Aplicado o experimento e sondado com um caso efêmero que renderiza `/admin`, chama
`fronteiraDeRequest()` e renderiza de novo:

    antes  do forget → data-voltar-ao-topo presente
    depois do forget → data-voltar-ao-topo presente

O hook sobrevive ao descarte da instância. O elo que faltava na cadeia de quatro está no
`FilamentView`: ele **não** chama o `ViewManager` direto, embrulha tudo em
`static::resolved(...)` (`vendor/filament/support/src/Facades/FilamentView.php`, método
`registerRenderHook`). E `Facade::resolved()` registra um
`afterResolving` no container
(`vendor/laravel/framework/src/Illuminate/Support/Facades/Facade.php`, método `resolved`), que
**persiste**: toda instância nova de `ViewManager` recebe de volta todos os hooks já
registrados.

Logo a cadeia real tem **cinco** elos, e o quinto inverte a conclusão:

1. o plugin chama `$panel->renderHook(...)` sem escopo;
2. `Panel::renderHook()` normaliza `null` para o bucket `''`
   (`vendor/filament/filament/src/Panel/Concerns/HasRenderHooks.php`, o `if ($scopes === null)`);
3. `Panel::registerRenderHooks()` repassa cada um para `FilamentView::registerRenderHook()`;
4. o `ViewManager::renderHook()` lê o bucket `''` **sempre**, antes de qualquer escopo;
5. **e a facade re-registra tudo em cada instância nova** — então descartar a instância não
   remove hook nenhum. Nada em `fronteiraDeRequest()` poderia removê-los: não existe API de
   `unregister`.

### Conclusão: não há o que consertar no kit, e a guarda que importa já existe

O que esta dívida chamava de vazamento é o **mecanismo deliberado** do botão "Voltar ao topo":
o kit registra três hooks globais de propósito (`ConfiguraFilamentGlobal`, no
`KitServiceProvider`) exatamente porque o bucket `''` alcança tela de vendor, que é onde o kit
não tem como colar trait. Escopar o registro do plugin de terceiro é decisão upstream; escopar
os do kit **quebraria** a feature.

O risco que sobra é o de leitura, e ele já cobrou preço uma vez: foi esta dívida que fez a sonda
atribuir o botão de limpar cache ao painel errado. A defesa contra isso não é código novo — é
`tests/Kit/VoltarAoTopoTest.php`, que já tem o caso *"injeta o botão em todos os painéis"* com
dataset dos três e o caso *"alcança também as telas que vêm de plugin"*, com o comentário que
diz em voz alta: *"Se alguém trocar o hook por um registro por painel ou por um trait, este caso
é o que cai."*

**Fica aceita por escrito**, que é a frente 1 que esta seção pedia: o lote de CT-B valida um DOM
onde hooks globais de qualquer painel visitado estão presentes — porque é isso que o usuário vê
também, em qualquer painel. O que NÃO se pode concluir daquele DOM é a **proveniência** de um
hook global. Quem precisar disso mede num processo por painel.

---

## DT-09 - Telas de `/infra` misturam ingles e portugues

**Severidade**: cosmetica
**Como foi encontrada**: `feature-quality-gate`, ciclo 1 - QA-06, por inspecao visual de
screenshot.

### O problema

O kit traduz o essencial (`Dashboard` para *Painel de Controle*), mas telas de plugin do `/infra`
ficaram para tras:

| Tela | Strings em ingles |
|---|---|
| `/infra/logs` | *"Logs explorer"*, *"Browse, read and search your application log files, grouped by channel."*, *"1 file"* / *"3 files"*, *"Refresh"* |
| `/infra` (dashboard) | *"Composer releases"*, *"From composer.json / composer.lock on GitHub"*, *"Informational - no auto-updates"* |
| Tela de 403 | *"Message no. SNT-403-783"* |

Pior que ingles puro e o hibrido: *"Modified ha 1 hora"*.

### Correcao

Publicar as traducoes dos pacotes envolvidos (`laboiteacode/filament-logs-explorer`,
`mominalzaraa/filament-composer-release-notifier`) em `lang/vendor/`, ou sobrescrever os rotulos
pelos setters de plugin que ja sao usados para outros - o `InfraPanelProvider` ja faz isso com o
Command Center (`CommandCenterCommands::navigationLabel('Comandos')`).

Nenhuma das outras nove dividas cobre i18n, e o projeto e pt-BR por decisao.

### Revisão — 2026-08-22: **quase toda paga, e ninguém atualizou esta seção**

A dívida foi conferida linha por linha contra o repositório de hoje. Das quatro entradas da
tabela acima, **as quatro estão traduzidas**, pelo commit `5511a0a` (*"traduz as telas de plugin
que ainda saiam em ingles no /infra"*, 2026-08-15) — que é **posterior** à escrita desta seção e
não a atualizou:

| Entrada da tabela | Hoje |
|---|---|
| `/infra/logs`: *"Logs explorer"*, a descrição, *"1 file"*/*"3 files"*, *"Refresh"* | `lang/vendor/filament-logs-explorer/pt_BR/filament-logs-explorer.php` — 60+ chaves, inclusive `'heading' => 'Explorador de logs'`, `'refresh' => 'Atualizar'`, `'file_count'` com pluralização, e `'modified' => 'Modificado :time'` (o híbrido) |
| `/infra` (dashboard): *"Composer releases"*, *"From composer.json…"*, *"Informational — no auto-updates"* | `lang/pt_BR.json`, linhas 5, 29 e 30 — o pacote resolve por `__()` sem arquivo de lang próprio |
| Tela de 403: *"Message no. SNT-403-783"* | `resources/views/errors/sentinel-layout.blade.php:153-155` — *"Mensagem nº"* e *"ID da requisição"* |

E a **causa da terceira estava mal atribuída** nesta seção: a tabela põe o *"Message no."* na
conta do `sentinel`, e a correção proposta é publicar `lang/vendor/`. A tradução do pacote sempre
esteve certa e completa — o kit **ejetou** as páginas de erro para `resources/views/errors/`, que
têm prioridade, e as duas strings estavam escritas à mão no layout do próprio kit. Publicar
`lang/vendor/sentinel/` não teria mudado nada na tela. É o mesmo modo de errar que
`.ai/rules/specs.md` descreve: conclusão certa (*"está em inglês"*), causa errada, e o conserto
pelo motivo escrito conserta a coisa errada.

**O que resta**, e está documentado no corpo daquele commit: o título/`<h1>` das telas do Command
Center. `Commands.php:44` do pacote é `protected static ?string $title = 'Commands';` — literal,
`protected`, sem setter, e o heading vem de `getHeading()` dentro do componente de página, então
não passa pela Blade publicada nem por `lang/`. Não há saída limpa sem subclassificar a página e
reregistrá-la. O `InfraPanelProvider` já ajusta o rótulo de **navegação**
(`CommandCenterCommands::navigationLabel('Comandos')`), que é o que dá para ajustar de fora.

**Proposta**: rebaixar esta dívida para *"1 título de plugin sem ponto de extensão"* e fechá-la
como **não-defeito do kit** (destino "especificação"), ou abrir um PR upstream pedindo um setter.
Não é mais uma dívida de ~2 h de tradução.

---

## DT-10 - A suite de testes escreve no `storage/logs` real · ✅ **PAGA**

**Severidade**: cosmetica, destino **infra**
**Como foi encontrada**: `feature-quality-gate`, ciclo 1 - QA-07.

### O problema

`phpunit.xml` fixa `DB_CONNECTION`, `CACHE_STORE`, `SESSION_DRIVER`, `MAIL_MAILER` e
`QUEUE_CONNECTION` em drivers de teste - mas **nao** redireciona o log. O channel `autenticacao`
e `daily` em disco, entao a suite escreve nos logs de trabalho do desenvolvedor:

```
storage/logs/autenticacao-2026-08-14.log  ->  4.463 linhas, 1,1 MB
                                              1.033 delas sao [User@canAccessPanel]
```

Tudo produzido pelas rodadas de teste do dia. Precede esta branch (vem de `tests/Kit`), e a suite
de browser nova soma ao volume.

### Correcao prescrita aqui — **estava errada**

O que esta secao mandava fazer era uma linha em `phpunit.xml`:

```xml
<env name="LOG_CHANNEL" value="null"/>
```

Nao resolve, e **piora em silencio**. Duas razoes, as duas medidas contra o vendor:

1. `LOG_CHANNEL` troca so o canal **default**. As **60** chamadas de log do kit sao
   `Log::channel('ai'|'tenancy'|'autenticacao')` nomeadas (21 + 16 + 23, contadas com
   `grep -rho "Log::channel('[a-z_]*'" app/`) e passam por cima dele. Os tres canais eram
   `daily` fixo em `config/logging.php` — continuavam gravando.

2. A extensao obvia — `<env name="LOG_KIT_DRIVER" value="null"/>` com `'driver' => env(...)` —
   **tambem** nao funciona, e foi medida falhando. Nao existe `createNullDriver` no
   `LogManager` (os drivers vao de `:260` a `:433` em
   `vendor/laravel/framework/src/Illuminate/Log/LogManager.php`; `resolve()` estoura em `:240`),
   e o `env()` do Laravel converte a string `"null"` em `null` de verdade. O `resolve()` lanca,
   o `get()` captura o `Throwable` e devolve o **emergency logger** — que grava em
   `storage/logs/laravel.log`. O log continuaria em disco, no arquivo errado, com um
   `emergency` a cada resolucao de canal. O `null` do `config/logging.php` e um **canal**
   (`driver: monolog` + `NullHandler`), nunca um driver.

### Resolucao — aplicada em 2026-08-22

Driver dos tres canais por env, com o `handler` sempre presente:

```php
// config/logging.php — nos canais 'ai', 'tenancy' e 'autenticacao'
'driver'  => env('LOG_KIT_DRIVER', 'daily'),
'handler' => NullHandler::class,
```

```xml
<!-- phpunit.xml -->
<env name="LOG_CHANNEL" value="null"/>
<env name="LOG_KIT_DRIVER" value="monolog" force="true"/>
```

`createDailyDriver` ignora a chave `handler`; quem a usa e o `createMonologDriver`
(`LogManager.php:433`). Verificado nos dois lados:

| Contexto | Handler resolvido |
|---|---|
| suite (`LOG_KIT_DRIVER=monolog`) | `Monolog\Handler\NullHandler` |
| producao (env ausente, driver `daily`) | `Monolog\Handler\RotatingFileHandler` |

**Guarda**: `tests/Kit/QualidadeDeCodigoTest.php` — *"nao escreve log em disco durante a suite"*,
dataset de `ai`, `tenancy`, `autenticacao` e do canal default. Assere o **handler resolvido**, nao
a chave de config, entao cobre a corrente inteira (env do `phpunit.xml` -> `env()` -> `LogManager`)
e morre se alguem errar o nome da variavel, tirar o `env()` de um canal ou apagar a linha do
`phpunit.xml`. Foi visto **falhando** com `StreamHandler` antes da correcao final.

**Medido depois de aplicar**, e nao so pelo teste-guarda:

- `php artisan test tests/Kit/PaineisTest.php` — o arquivo que produzia
  `[User@canAccessPanel]` — deixou `autenticacao-2026-08-22.log` em **2.304 linhas antes e 2.304
  depois**: zero escrita.
- `php artisan test --testsuite=Unit,Feature,Kit,Tenancy --parallel` — **524/524, 1.444
  asseroes**, e nenhuma escrita nova em `autenticacao-*`, `tenancy-*` ou `laravel.log` (a ultima
  linha dos tres e anterior a rodada). Vale tambem em `--parallel`, onde os workers sao processos
  proprios.
- Pint e PHPStan verdes.

O aviso original se confirmou inofensivo: `tests/Kit/PaineisTest.php` usa `Log::shouldReceive()`,
nao arquivo, e nenhum teste da suite le `storage/logs` (`grep -rn "storage_path('logs" tests/` =
vazio).

---

## DT-11 — Sem PCOV, o `--tia` é impraticável em série · ✅ **PAGA nesta máquina**

**Severidade**: relevante
**Como foi encontrada**: tentando fechar a Verificação Final com `pest --tia` em série, que é a
única combinação em que o TIA liga **e** os CT-B ficam estáveis.

### O problema

> **Confirmada em 2026-08-22, e o diagnóstico original estava certo.** Registro aqui uma medição
> minha que estava **errada**, porque o modo de errar se repete: `php -m | grep -i -e pcov -e
> xdebug` devolveu **vazio** neste ambiente (Git Bash no Windows) e eu li isso como "não há driver
> algum". Não era. O pipe do `grep` aborta contra a saída do `php -m` aqui — a mensagem
> `Aborted` aparece no stderr e o exit code é de erro, não de "zero linhas". O `php.ini`
> declarava `zend_extension = xdebug` com `xdebug.mode = debug,develop,coverage`, e o próprio
> `php -v` imprime `with Xdebug v3.4.4` na quarta linha. **Sinal de alerta**: concluir ausência a
> partir de um `grep` vazio sem checar o exit code — leia a fonte (`php.ini`, `php -v`), não o
> filtro.
>
> O que o TIA diz quando de fato falta driver está em
> `vendor/pestphp/pest/src/Plugins/Tia.php:741-742` — *"Recorded zero edges — coverage driver
> likely missing"*. Não era o caso: o `--tia` sobe e roda neste checkout (`vendor/bin/pest --tia`
> imprime `─ Running in TIA mode.`), só era lento, exatamente como esta seção dizia desde o
> começo.

O `--tia` exige driver de cobertura. O ambiente tinha **Xdebug 3.4.4 e não tinha PCOV**
(`php -m | grep -i pcov` → vazio). Xdebug com cobertura ativa é ordens de magnitude mais lento
que PCOV, e o efeito era este:

| Combinação | Tempo |
|---|---|
| `pest --parallel --tia` (completo) | 507 s — mas derruba 4 dos 11 CT-B, porque `--parallel` não convive com browser |
| `pest --tia` (série, completo) | **abortado após 35 min sem terminar** |

Ou seja: das duas formas de rodar o TIA no run completo que ele exige, uma é rápida e instável, e
a outra é estável e não termina. O TIA fica tecnicamente desbloqueado (DT-03 foi paga) e
praticamente inutilizável.

### Correção

Instalar PCOV no ambiente de desenvolvimento e no job de baseline do CI:

```bash
pecl install pcov
```

No Windows não é `pecl install`: é baixar a DLL de PCOV que casa com a assinatura do PHP
(8.4, **ZTS**, VC22, x64), pôr em `ext/` e declarar `extension=pcov` + `pcov.enabled=1` no
`php.ini`.

E, no CI, um job dedicado — nunca `--tia` no job que roda a suíte, que deve rodar tudo. **Mas a
linha abaixo é palpite, não medição**, e está aqui só como ponto de partida:

```yaml
- run: vendor/bin/pest --tia --coverage --fresh   # job de baseline, artefato pest-tia-baseline
```

O TIA do Pest 5 **não** é um artefato solto: ele guarda estado próprio (`coverage.bin.gz`,
`Tia.php:89`), resolve o *default branch* de que os outros branches herdam o baseline (daí
`TiaRequiresRemote` e `TiaRequiresDefaultBranch`, e o `pest()->tia()->defaultBranch()` que as duas
sugerem) e tem `--baselined` / `--refetch` / `--locally` para isso (`Tia.php:55-63`). Um quarto
job só entrega ganho se esse estado **persistir entre execuções**; sem isso ele reconstrói o
baseline toda vez, gasta minutos e não acelera nada. Quem for fazer, lê o `Tia.php` primeiro.

O `.github/workflows/ci.yml` hoje usa `coverage: none` nos três jobs, que é o correto para eles.
**Deliberadamente não foi adicionado um quarto**: a suíte inteira já roda verde em minutos, o
ganho do TIA é local, e um job de baseline sem persistência seria custo sem benefício.

### Resolução — 2026-08-22, e por que ela não vale para as outras máquinas

PCOV **1.0.12** instalado, com autorização explícita do usuário. No Windows não é
`pecl install`: é a DLL que casa com a assinatura exata do PHP.

| Item | Valor |
|---|---|
| Assinatura do PHP | `API20240924`, **TS**, `VS17`, x64 (PHP 8.4.10 ZTS VC22) |
| Artefato | `php_pcov-1.0.12-8.4-**ts**-vs17-x64.zip`, de `windows.php.net/downloads/pecl/releases/pcov/1.0.12/` |
| DLL | `C:\php-8.4.10\ext\php_pcov.dll` |
| `php.ini` | `extension=pcov` + `pcov.enabled=1` |
| Backup do ini | `C:\php-8.4.10\php.ini.bak-2026-08-22-pcov` |

**O `xdebug.mode` não foi tocado, de propósito.** O `Selector::select()` do php-code-coverage testa
PCOV **antes** do Xdebug quando a granularidade é por linha
(`vendor/phpunit/php-code-coverage/src/Driver/Selector.php:30-34`), então os dois convivem: Xdebug
segue servindo debug/step, PCOV atende cobertura. Confirmado — o `Selector` devolve
`PCOV 1.0.12`. As duas linhas do ini são obrigatórias porque `Runtime::hasPCOV()` exige extensão
carregada **e** `pcov.enabled === '1'` (`vendor/sebastian/environment/src/Runtime.php:243`).

### O ganho, medido

| Comando | Antes (Xdebug) | Depois (PCOV) |
|---|---|---|
| `pest --tia` em série, run completo | **abortado após 35 min** sem terminar | `--tia --fresh`: **24m59s**, 559 casos, 553 passados, 6 skipped, 1.603 asserções |
| `pest --tia` na sequência, sem mudança de código | não chegava a existir | **6,4 s** de suíte (18,4 s de parede, com boot) |

É o ponto todo do TIA: o `--fresh` grava o grafo uma vez e caro; a partir daí a suíte inteira
custa segundos. A matriz de comandos da seção seguinte continua valendo para `--parallel` × browser
— o que mudou aqui é só o driver de cobertura.

### Por que continua aberta para os outros

Instalar extensão PHP é mudança de **ambiente**, não de código: não entra num commit, não é
revisável em PR, e afeta a máquina de quem rodar. É a razão de a coluna "Onde" dizer *ambiente
(não é código)*, e **nenhum commit fecha este item para quem clonar o kit** — cada máquina precisa
da DLL que casa com a própria assinatura de PHP. O CI segue com `coverage: none` nos três jobs, que
é o correto para eles.

O contorno usado nesta wiki: `pest --parallel --group=kit` para o backend (196 s, verde) e
`pest --testsuite=Browser` em série para as telas (120 s, verde). Cobre o mesmo, sem o TIA.

---

## Nota — a matriz de comandos de teste, medida

Não é dívida; é característica das ferramentas, e custa uma tarde para redescobrir. Registrada
aqui e em `.ai/rules/testes-browser.md`.

| Comando | TIA liga? | Browser estável? | Resultado medido |
|---|---|---|---|
| `pest --parallel --tia` (completo) | ✅ | ❌ | **4 dos 11 CT-B falham** por timeout — `--parallel` multiplica processos de navegador |
| `pest --parallel --tia --exclude-group=browser` | ❌ *"does not apply to partial runs"* | n/a | 217/217 verde |
| `pest --parallel --group=kit` | ❌ (partial) | n/a | 214/214 verde, 196 s |
| `pest --testsuite=Browser` (série) | ❌ (partial) | ✅ | 11 cenários, verde em 4 execuções |
| `pest --tia` (série, completo) | ✅ | ✅ | **abortado após 35 min** — ver DT-11 |

**A consequência prática**: o `--tia` exige run **completo** — `--group` e `--exclude-group` o
desligam. E `--parallel` é incompatível com browser. Logo os dois **não convivem numa invocação
só** enquanto os CT-B estiverem na mesma suíte. Use dois comandos, que é o que a Verificação
Final da wiki faz.

---

## O que **não** é dívida, e por que está escrito aqui

Três coisas que pareceriam dívida numa leitura rápida:

1. **`->todo()` no CT-B09** — é o registro de DT-01 e DT-02, não dívida própria. Ver ADR-07.
2. **CT-B usando `actingAs()` em vez de login pela UI** — deliberado, e o CT-B06 cobre o
   formulário. Ver `05-casos-de-teste-browser.md` → Setup Global.
3. **`pest()->browser()->timeout(20_000)`** — é **teto** de reexecução de assertion, não espera
   fixa. Cenário verde não gasta esse tempo. O default de 5 s não alcança o primeiro boot de um
   painel Filament em teste, sem opcache e com Livewire compilando.
4. **Stats do dashboard mostrando `0` por ~2,5 s** — é o odômetro funcionando como
   configurado (`FilamentOdometerEasyPlugin::make()->delay(1000)->duration(1500)`,
   `AdminPanelProvider.php:146-148`). Escolha explícita do mantenedor, não defeito. O que
   **é** lacuna: nenhum CT-B assere o valor de um indicador, então se o custom element
   falhar o `0` fica permanente e silencioso. Ver QA-05 do `07-relatorio-qa.md`.
5. **Senha preservada no formulário após login inválido** — default do Filament, e
   defensável. Sem requisito contra. Ver R-2 do `07-relatorio-qa.md`.
6. **N+1 em `/admin/users`** — medido: **13 queries constantes** com 1, 10 e 30 usuários. A
   aparência de N+1 vinha de deriva de medição no mesmo processo. Ver R-4.

---

## Fechamento das três últimas — 2026-08-23

### DT-04 · ✅ **PAGA**

Dataset de **8 telas** do `/app` em `tests/Tenancy/TenancyTest.php`, no caso
*"abre as telas do painel de negocio"* — e não em `tests/Kit`, como esta seção prescrevia. A
revisão de 2026-08-22 já tinha apontado que ali a resposta seria **403**, provando permissão em
vez de "a tela abre".

**O smoke rendeu na primeira execução**: `/projetos` respondeu 403 porque
`ProjetoResource::canAccess()` exige `kit.demo` e o `phpunit.xml` o fixa em `false`
(`ProjetoResource.php:81-88`). Não é defeito — é a forma do ambiente, e agora está escrita no
teste com o motivo. Era o tipo de coisa que ninguém sabe de cabeça.

Fora do dataset, com razão declarada: `hub-do-negocio` (exige `KIT_HUB=true`), `exceptions` (não é
tela do `/app`, e o `admin_app` perdeu a permissão na v0.18.3) e `users/{record}/edit` (já tem
caso que **grava**).

### DT-06 · ✅ **PAGA — e o defeito era outro, pela terceira vez**

A revisão anterior acertou que a dívida deixou de ser sobre tempo e passou a ser sobre
aquecimento do primeiro run. A correção foi a que `.ai/rules/testes-browser.md` já prescrevia e
ninguém tinha aplicado: pagar a compilação num `$this->get()` pelo **kernel**, fora do cronômetro
do Playwright. Aplicada nos dois arquivos que falhavam frios.

Resultado no cenário exato da falha (`npm run build` + `view:clear`): **o `Timeout 45000ms`
desapareceu.**

**Mas apareceu o resto — e aí a v0.18.4 se revelou errada.** No mesmo run frio voltou o falso
achado de contraste no `/app`, quatro `serious` com todo o texto em `#d0d0d0` sobre `#fafafa`. A
v0.18.4 atribuiu isso a cache frio ("varre antes de a folha de estilo assentar") e acrescentou um
`waitForEvent('networkidle')`. **Diagnóstico errado, e a release note dizia isso.**

O experimento que o derruba, e a matriz que faltava:

| Estado | `inLightMode()` no caso | Resultado |
|---|---|---|
| frio | não | **falha** — medido duas vezes |
| frio | **sim** | passa |
| quente | não | passa |
| quente | sim | passa |

Cold + isolado **passa**; cold + arquivo inteiro **falha**. Não é o cache: é o cenário anterior.
O primeiro caso do arquivo chama `->inDarkMode()`, e a emulação de `prefers-color-scheme` alcança
o cenário seguinte — o Filament emite os tokens de texto do tema escuro (`#d0d0d0` é cinza-claro,
correto sobre fundo escuro) enquanto o fundo continua claro. **Paleta escura sobre fundo claro**,
não página sem CSS. Todo o texto trocado ao mesmo tempo é assinatura de tema, não de um elemento
com cor mal escolhida.

A correção é o caso **declarar** o tema em vez de herdá-lo: `->inLightMode()`. O próprio docblock
dele já dizia que "o tema claro é o eixo que interessa aqui" — só não estava pedindo isso ao
navegador. O `networkidle` ficou, porque não custa nada e é pré-condição honesta, mas não era ele
que faltava.

**O que não está provado**: o frio é o que abre a janela, então a falha é uma corrida. Um único
run frio verde com a correção reduz a suspeita; não a elimina. O que a correção remove é a
**dependência de estado herdado**, que é a causa estrutural.

### DT-05 · ❌ **RECUSADA, com medição**

Não vou pagar, e o motivo é evidência acumulada em vez de preferência.

Seis releases desta auditoria produziram cerca de vinte episódios de escrita ou conserto de
teste. Em **nenhum** deles a falha veio de seletor frágil. As causas reais foram: painel não
bootado em teste de componente Livewire, emulação de tema herdada entre cenários, compilação de
view dentro do cronômetro, `env()` que o runner não enxerga, e rota que não existe sob a flag do
ambiente. `data-testid` não teria evitado nenhuma.

E o kit **já usa** âncora estável onde houve motivo: `data-voltar-ao-topo`, escrita quando um
caso precisou dela. Isso é o padrão sendo adotado sob demanda, que é o comportamento saudável.
Espalhar `data-testid` por todas as telas antes de existir um teste que precise é inventário sem
consumidor — e cada atributo é uma linha que alguém mantém.

**Fica disponível, não pendente**: no dia em que um caso quebrar por seletor, o atributo entra
naquela tela, com o caso que o justifica. Reabrir esta dívida exige um exemplo, não uma
estimativa.

