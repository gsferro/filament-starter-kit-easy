# Plano de Ação — Issue #148: overrides autorais de `resources/views/vendor` no `kit:update`

> Requisito: `00-requisito.md`

## Natureza da Wiki

- **Tipo**: correção
- **Wiki ancestral**: `wikis/specs/feat/logo-dark-mode/logo-dark-mode/` — a feature que criou o override da lock-screen (v0.43.0) e não o pôs em `CAMINHOS_DO_KIT`; a varredura da classe irmã dela não cobria caminho de view de vendor
- **Motivo**: o override viaja pelo `composer create-project` (está no git e não é `export-ignore`) e **não** viaja pelo `kit:update` (`resources/views/vendor` não está em `CAMINHOS_DO_KIT`). Projeto atualizado fica com a lock-screen do pacote, sem o par de logos claro/escuro, e `LogoDarkModeTest` CT-16 falha 6 vezes
- **Toca infra compartilhada?**: sim → `KitUpdate::CAMINHOS_DO_KIT` (a lista das duas rotas de entrega) e a varredura de `tests/Kit/KitUpdateTest.php`. Regressão obrigatória contra `KitUpdateTest`, `DuasRotasDeEntregaTest`, `LogoDarkModeTest`, `BotaoLimparCacheTest` e os três testes que citam linhas de `KitUpdate.php`

## Cobertura do Requisito

| RQ | Cláusula | Passo(s) que atende(m) | Observação |
|----|----------|------------------------|------------|
| RQ-01 | `filament-auth-designer` entra em `CAMINHOS_DO_KIT` e chega pelo `kit:update` | 1, 3, 4, 5 | o passo 3 é consequência mecânica (as linhas de `KitUpdate.php` deslocam) e o 4 é o rastro (CHANGELOG) |
| RQ-02 | auditoria de `resources/views/vendor`: toda pasta autoral entregue | 1, 6 | as cinco pastas autorais medidas no step 3 (tabela em *Análise*) |
| RQ-03 | varredura automática cobre `resources/views/vendor` | 2 | caso novo em `KitUpdateTest`, onde mora a exclusão que cegou a varredura (D1) |
| RQ-04 | publish cru fica fora; o `kit:update` não sobrescreve customização | 1, 2 | o passo 2 reprova pasta crua **dentro** da lista também (os dois sentidos) |
| RQ-05 | sintoma some: a view do par claro/escuro chega e CT-16 passa no projeto atualizado | 1, 2, 5 | o elo mecânico é `estaCoberto()` da `media.blade.php` (CT da fundação); o fim a fim é o cenário 3 do checklist de release (fora de escopo) |
| P-01 | pasta crua não entra, e a varredura reprova se entrar | 2 | — |
| P-02 | entrada por pasta, não por arquivo | 1, 2 | — |
| P-03 | autoral = conteúdo diferente do pacote instalado | 2 | — |
| P-04 | varredura só na árvore do kit | 2 | — |
| P-05 | pacote que atualiza a view torna o publish cru "autoral": achado legítimo, mensagem com as duas saídas | 2 | — |
| P-06 | caminho novo na lista é comparado tag de destino × árvore do projeto | 5 | *(alterado em 2026-10-06: passo novo, CR-01/RD-01)* |
| P-07 | as dez views autorais mudam nesta release, para a classe antiga entregá-las na primeira rodada | 6 | *(alterado em 2026-10-06: passo novo)* |
| P-08 | arquivo que o projeto não tem é "novo no kit" | 5 | *(alterado em 2026-10-06: QA-03)* |

## Objetivo

Fazer os overrides de view que o kit **escreveu** chegarem a quem atualiza por `kit:update`, e deixar a varredura que protege as duas rotas de entrega enxergar `resources/views/vendor` — distinguindo, por conteúdo, o que é autoral do kit do que é publish cru de pacote, que nunca deve ser entregue.

A correção é uma constante, um teste, dois estáticos no comando e uma linha em dez views *(alterado em 2026-10-06: QA-04 — era "uma constante e um teste" antes dos passos 5 e 6)*. O que ela tem de delicado é o critério: `resources/views/vendor` mistura cópias editadas pelo kit (lock-screen, captcha, botão de limpar cache, tradução do command-center, painel fixo da coluna redimensionável) com cópias cruas que só existem porque alguém rodou `vendor:publish` no esqueleto. Entregar tudo sobrescreveria customização legítima do projeto; não entregar nada é o bug do issue.

## Contexto

As duas rotas de entrega do kit são governadas por listas diferentes: `composer create-project` pelo `.gitattributes` (exclusão), `kit:update` por `KitUpdate::CAMINHOS_DO_KIT` (inclusão). Arquivo que viaja por uma e não pela outra já aconteceu com `resources/views/svg` (v0.23.0), `tests/Browser` (v0.39.1), `lang/pt_BR.json`, `public/css/kit`/`resources/css/filament` e `.ai/`, `.claude/`, `.agents/`, `.junie/` — cada vez por um diretório que a varredura não olhava. Desta vez a varredura olha o diretório (`resources` está em `DIRETORIOS_DE_CODIGO`) e **pula** `resources/views/vendor/` de propósito, com o comentário *"é o que os pacotes publicam com `vendor:publish`; não é código do kit"* — verdade para sete pastas e falso para cinco.

## Análise dos Arquivos Existentes

<!-- Raia fato da entrevista: o que o agente descobriu no código, sem perguntar ao usuário. -->

### `app/Console/Commands/KitUpdate.php`
- `CAMINHOS_DO_KIT` (`app/Console/Commands/KitUpdate.php:CAMINHOS_DO_KIT:93`) lista `resources/views/auth`, `errors`, `filament`, `livewire` e `svg` (`app/Console/Commands/KitUpdate.php:'resources/views/svg':229`); nenhuma entrada sob `resources/views/vendor`.
- O comando aplica cada caminho com `git checkout {destino} -- {caminho}` (`app/Console/Commands/KitUpdate.php:aplicar():1025`): uma pasta na lista entrega todos os arquivos dela na tag de destino e nunca apaga nada.
- A lista efetiva é a **união** da constante desta versão com a lida na tag de destino (`app/Console/Commands/KitUpdate.php:caminhosUnidos():795`, `caminhosDeclaradosEm():803`, regex `^\s+'([^']+)',`): entrada nova com a forma `        'caminho',` é reconhecida em qualquer tag; comentário de bloco que cite um caminho fica de fora por construção.
- O comentário de `lang/pt_BR.json` (`app/Console/Commands/KitUpdate.php:'lang/pt_BR.json':211`) conta as ocorrências anteriores da divergência; a entrada nova ganha comentário no mesmo tom, com o critério de autoria.

### `tests/Kit/KitUpdateTest.php`
- `estaCoberto()` (`tests/Kit/KitUpdateTest.php:estaCoberto():7`) é o oráculo "está em `CAMINHOS_DO_KIT`"; o dataset da fundação (`tests/Kit/KitUpdateTest.php:'cobre os arquivos da fundação':18`) lista arquivo a arquivo o que nunca pode sair da lista — a `media.blade.php` entra ali (RQ-05).
- O caso *cobre todo o código do kit* (`tests/Kit/KitUpdateTest.php:'cobre todo o código do kit':164`) varre `DIRETORIOS_DE_CODIGO` e pula `resources/views/vendor/` inteiro (`tests/Kit/KitUpdateTest.php:'resources/views/vendor/':190`). É aqui que a varredura cegou — não em `DuasRotasDeEntregaTest`, que compara só o **primeiro nível** de cada caminho que viaja (`resources/views` conta como coberto porque cinco subpastas estão na lista).
- Guarda de árvore: `is_dir(base_path('.github'))` (`tests/Kit/KitUpdateTest.php:'.github':171`), o mesmo sinal que o caso novo usa (P-04).

### `tests/Kit/DuasRotasDeEntregaTest.php`
- Três casos sobre o primeiro nível do `.gitattributes` × `CAMINHOS_DO_KIT` × `FORA_DA_ENTREGA_POR_DECISAO`. Não muda: a lacuna não é de primeiro nível. Entra na regressão.

### `resources/views/vendor/*` — a auditoria (RQ-02), por script, 2026-10-06
Comparação byte a byte (CRLF normalizado) de cada view com a de mesmo caminho relativo em `vendor/*/*/resources/views`:

| Pasta | Views | ≠ pacote | Pacote instalado | Veredito | Evidência de autoria |
|---|---|---|---|---|---|
| `asmit-resized-column` | 1 | 1 | `asmit/resized-column` 4.0.2 | **autoral** | nasceu editada em `5511a0a` (tradução do /infra) |
| `command-center` | 4 | 3 | `ssbityukov/filament-command-center` v1.0.2 | **autoral** (P-02) | `output`, `commands`, `run` editadas em `5511a0a` |
| `filament-auth-designer` | 1 | 1 | `caresome/filament-auth-designer` v3.1.0 | **autoral** | `c6d900d` (logo por tema, v0.43.0) — o issue |
| `filament-captcha` | 4 | 4 | `ddr/filament-captcha` v1.1.2 | **autoral** | `3fc22d5` (anti-robô) |
| `filament-clear-cache` | 1 | 1 | `cms-multi/filament-clear-cache` 3.0.3 | **autoral** | `193cca0` (a11y DT-01); `BotaoLimparCacheTest` lê essa cópia |
| `ai-tasks` | 3 | 0 | `fomvasss/laravel-ai-tasks` | publish cru | só o skeleton `1eded2b` |
| `authentication-log` | 3 | 0 | `rappasoft/laravel-authentication-log` | publish cru | idem |
| `filament-composer-release-notifier` | 2 | 0 | `mominalzaraa/filament-composer-release-notifier` | publish cru | idem |
| `filament-jobs-monitor` | 8 | 0 | `croustibat/filament-jobs-monitor` | publish cru | idem |
| `filament-onboarding` | 16 | 0 | `wallacemartinss/filament-onboarding` | publish cru | idem |
| `filament-sentinel` | 12 | 0 | `anselmokossa/filament-sentinel` | publish cru | idem |
| `pulse` | 1 | 0 | `laravel/pulse` | publish cru | idem |

Laravel procura a view primeiro em `resources/views/vendor/{namespace}` e só depois no pacote (doc *Packages → Overriding Package Views*, `search-docs`): por isso um publish cru congela a tela na versão publicada, e por isso o publish cru **não** pode ser entregue pelo kit (RQ-04) — sobrescreveria o que o projeto editou ali.

### Citações de código de `KitUpdate.php` fora da wiki
- `docs/pt/comecar/atualizando-o-projeto.md`, `docs/en/comecar/atualizando-o-projeto.md`, `tests/Kit/ChecklistDeReleaseTest.php`, `tests/Kit/ConstraintDeDependenciaTest.php`, `tests/Kit/DeployDockerLocalTest.php`: 20 citações `KitUpdate.php:…:linha`. Cinco entradas novas na constante deslocam tudo abaixo delas; o guarda CT-66 de `DiagramasDaArquiteturaTest` e `CitacoesDeCodigoTest` acusam citação velha.

## Decisões de Desenho

| # | Decisão | Pergunta | Portão que falta | Quem decidiu, data |
|---|---|---|---|---|
| D1 | O caso novo mora em `tests/Kit/KitUpdateTest.php`, ao lado de `estaCoberto()` e no lugar da exclusão que cegou a varredura — não em `DuasRotasDeEntregaTest`, que é de primeiro nível. O issue aceita "irmão". | Q4 (desenho): onde fica a varredura? | difícil de reverter (é mover um `it()`) | sessão, pelas recomendações, 2026-10-06 |
| D2 | Entrada em `CAMINHOS_DO_KIT` por **pasta** (`resources/views/vendor/{pacote}`), uma por pasta autoral; nenhuma entrada `resources/views/vendor` inteira. | Q5 (desenho): pasta, arquivo ou raiz? | difícil de reverter | sessão, 2026-10-06 — raiz inteira entregaria os 7 publishes crus (viola RQ-04); arquivo repete o esquecimento no próximo arquivo editado |
| D3 | Oráculo de autoria = conteúdo da view do kit × view de mesmo caminho relativo em `vendor/*/*/resources/views` (CRLF normalizado), resolvido pelo nome do arquivo e não por mapa pasta → pacote. Pasta com ≥ 1 arquivo diferente é autoral; com todos iguais é crua. View sem original no vendor cai na mesma expressão (nenhum original igual ⇒ autoral), sem ramo extra *(ponytail, 2026-10-06)*. | Q6 (desenho): como decidir "autoral"? | difícil de reverter | sessão, 2026-10-06 — git não serve (P-03), lista à mão é a quarta lista. Consequência P-05 aceita |
| D4 | Nenhum channel de log: a correção é uma constante e um teste, sem caminho de execução novo. O `kit:update` já loga o que aplica no console (`aplicado: {caminho}`). | — | — | sessão, 2026-10-06 |
| D5 | Na árvore real, a varredura enumera os arquivos de `resources/views/vendor` pelo `git ls-files` (o rastreado, que é o que o kit entrega), não pelo disco; as fixtures, que não são repositório, enumeram pelo disco. *(alterado em 2026-10-06: RD-06)* | — | difícil de reverter | sessão, 2026-10-06 — a irmã `DuasRotasDeEntregaTest` já mede pelo git; disco varia por máquina |
| D6 | O caminho novo na lista (P-06) é detectado comparando a lista do destino com a lida no fonte da **origem** (`caminhosDeclaradosEm` sobre `git show {origem}:…/KitUpdate.php`), e o diff extra é `git diff --name-status {destino} -- {novos}` com a rotulagem do modo sem origem; lista da origem ilegível ⇒ nenhum diff extra (fecha para o comportamento de hoje). *(alterado em 2026-10-06: CR-01/RD-01)* | — | difícil de reverter | sessão, 2026-10-06 — a alternativa "sempre diff contra a árvore" acusaria as edições do projeto em toda a lista |
| D7 | As dez views autorais ganham uma linha de comentário Blade nesta release, para a classe **antiga** do `kit:update` (a que roda na primeira rodada) as listar em qualquer origem (P-07). Alternativa recusada: documentar "rode duas vezes, a segunda com `--from`". *(alterado em 2026-10-06: era a Q4 da raia requisito; reclassificada como desenho em QA-01 e renumerada Q9)* | Q9 | difícil de reverter | sessão, 2026-10-06 — entrega sem passo manual; o comentário não chega ao HTML |
| D8 | O rótulo do diff olha a árvore do projeto: arquivo que o projeto não tem é "novo no kit" mesmo quando o tag→tag o vê como modificado (P-08); `rotularDiff()` recebe um `callable` de existência, para o estático continuar puro no teste. *(alterado em 2026-10-06: QA-03)* | — | difícil de reverter | sessão, 2026-10-06 — é o que `--only-new` promete |

## Autorização

- **Policies / Gates / Middleware / Guards**: nenhum — comando de console e teste.

## Rotas

Nenhuma.

## Superfície de UI

Sem superfície de UI. (A lock-screen muda **para quem atualiza**, mas a view já existe e já tem os CT-B da wiki ancestral; esta entrega não a toca.)

## Variáveis de Ambiente

Nenhuma.

## Eventos / Listeners / Observers

Nenhum.

## Jobs / Queues

Nenhum.

## Modelo de Execução

Um comando de console, sem trabalho adiado: `kit:update` passa a fazer até 10 `git checkout` a mais (um por **arquivo** entregue — `aplicar()` é por arquivo), mais um `git show` e um `git diff` por rodada com origem (P-06), e um `is_file` por arquivo rotulado (P-08) *(alterado em 2026-10-06: QA-04 — dizia "até 5, um por pasta")*. O teste novo lê `resources/views/vendor` (até 56 views) e `vendor/*/*/resources/views` uma vez por run, na árvore do kit.

## Impacto em Features Existentes

- **`logo-dark-mode`** (ancestral): nenhum código muda; o override passa a viajar. Regressão: `LogoDarkModeTest` (24 casos) *(alterado em 2026-10-06: QA-04)*.
- **`kit:update`** (`KitUpdateTest`, 54 casos; `DuasRotasDeEntregaTest`, 3): a constante cresce 5 linhas; `caminhosDeclaradosEm()` tem caso que compara o fonte com a constante — continua verde se as entradas tiverem a forma `        'caminho',`.
- **`filament-clear-cache`** (`BotaoLimparCacheTest`): passa a ser entregue; o teste já lê a cópia do kit.
- **Citações `KitUpdate.php:…`** em docs pt/en e em 3 testes: deslocam; recalculadas no passo 3 (`CitacoesDeCodigoTest`, `DiagramasDaArquiteturaTest` CT-66).
- **Projeto instalado que editou uma das 5 pastas autorais** (ex.: trocou o texto do botão de limpar cache): o próximo `kit:update` passa a **oferecer** a view do kit no diff — é o comportamento de toda pasta da lista (o comando mostra e pergunta; `--all` aplica). Vai no CHANGELOG.

## Rollback

- **Migration down**: não há.
- **Reversão**: `git revert` dos commits da branch — a constante, os dois estáticos e o segundo diff em `arquivosAlterados()`, os casos do teste e a linha de comentário das dez views *(alterado em 2026-10-06: QA-04)*.

## Dependências

Nenhuma nova.

## Riscos

- **Pacote atualiza a própria view** → o publish cru do kit passa a diferir e o teste novo acusa (P-05). Mitigação: mensagem do teste com as duas saídas (republicar ou listar); é sinal útil, não ruído.
- **Mesmo nome de arquivo em dois pacotes** (ex.: `index.blade.php` em `pulse` e em `mohaphez/pulse`; `filament-onboarding` casa com quatro pacotes): o oráculo aceita a view como crua se **algum** original for igual. Falso "crua" só se um pacote estranho tiver, por acaso, byte a byte a view editada pelo kit — improvável; o caso da fundação cobre a `media.blade.php` de qualquer jeito.
- **Vendor ausente** (`composer install --no-dev`? não: views de pacote vêm sempre). Fora da árvore do kit o caso pula (P-04).

## Channel de Log da Feature

Nenhum (D4). Verificado: `config/logging.php` não tem channel de `kit:update`, e o comando reporta pelo `components->info()`.

## Estrutura de Implementação

### 1. As cinco pastas autorais em `CAMINHOS_DO_KIT`

> Skills: `laravel-best-practices`, `ponytail`

- **Path**: `app/Console/Commands/KitUpdate.php`
- Depois de `'resources/views/svg',` (`app/Console/Commands/KitUpdate.php:'resources/views/svg':229`), um comentário de bloco de **até 6 linhas** *(ponytail, 2026-10-06: era "no tom dos vizinhos", que têm 15)* — a ocorrência, o critério **autoral = conteúdo diferente do pacote**, publish cru fora de propósito e o nome do caso de teste que decide — e as entradas, em ordem alfabética, na forma que `caminhosDeclaradosEm()` lê:
  ```php
  'resources/views/vendor/asmit-resized-column',
  'resources/views/vendor/command-center',
  'resources/views/vendor/filament-auth-designer',
  'resources/views/vendor/filament-captcha',
  'resources/views/vendor/filament-clear-cache',
  ```
- Nenhuma outra linha do comando muda.
- **Atende**: RQ-01, RQ-02, RQ-04, P-02
- **Logs**: nenhum (D4).

### 2. A varredura passa a decidir autoria em `resources/views/vendor`

> Skills: `pest-testing`

- **Path**: `tests/Kit/KitUpdateTest.php`
- Caso novo, ao lado de *cobre todo o código do kit*: para cada pasta de `resources/views/vendor`, classifica cada view por conteúdo contra `vendor/*/*/resources/views/{mesmo caminho relativo}` (D3) e exige, nos **dois sentidos**: pasta autoral → `estaCoberto('resources/views/vendor/{pasta}/…')` verdadeiro para toda view dela; pasta crua → falso para toda view dela. A mensagem de falha nomeia a pasta, os arquivos que diferem e as duas saídas (listar em `CAMINHOS_DO_KIT`, ou republicar a view do pacote se a diferença veio de atualização do pacote — P-05). Fora da árvore do kit, pula com motivo (P-04, mesma guarda `.github` do vizinho).
- O `continue` que pulava `resources/views/vendor/` no caso *cobre todo o código do kit* fica, com o comentário reescrito: a pasta é decidida pelo caso novo, por conteúdo, e não "não é código do kit".
- Dataset da fundação (`tests/Kit/KitUpdateTest.php:'cobre os arquivos da fundação':18`) ganha `resources/views/vendor/filament-auth-designer/components/partials/media.blade.php` com o comentário do issue (RQ-05).
- O Gherkin vem do `04`; quem escreve o teste é o `fw-executor-ct`.
- **Atende**: RQ-03, RQ-04, RQ-05, P-01, P-02, P-03, P-04, P-05
- **Logs**: nenhum (teste).

### 3. Recalcular as citações de `KitUpdate.php`

> Skills: `ponytail`

- **Path**: `docs/pt/comecar/atualizando-o-projeto.md`, `docs/en/comecar/atualizando-o-projeto.md`, `tests/Kit/ChecklistDeReleaseTest.php`, `tests/Kit/ConstraintDeDependenciaTest.php`, `tests/Kit/DeployDockerLocalTest.php`
- As 20 citações `KitUpdate.php:{símbolo}:{linha}` (e as curtas que as seguem) são reancoradas pelo símbolo, por script, depois do passo 1; `CitacoesDeCodigoTest` e `DiagramasDaArquiteturaTest` CT-66 provam.
- **Atende**: RQ-01 (consequência mecânica da entrada nova)
- **Logs**: nenhum.

### 5. Caminho novo na lista é comparado com a árvore do projeto *(passo novo em 2026-10-06: CR-01/RD-01)*

> Skills: `laravel-best-practices`, `ponytail`

- **Path**: `app/Console/Commands/KitUpdate.php`
- `arquivosAlterados()` ganha, quando há origem **e** a lista da origem foi lida: `$novos = caminhosNovosNaLista($listaDestino, $listaOrigem)` (estático, público: `array_values(array_diff(...))`); se `$novos !== []`, um segundo `git diff --name-status {destino} -- {novos}` rotulado como o modo sem origem (`D` → "novo no kit", `M` → "modificado", `A` → ignorado), somado ao resultado tag→tag (o tag→tag prevalece para o mesmo caminho). A rotulagem sai para um método estático público `rotularDiff(string $saida, bool $comOrigem, ?callable $existeNoProjeto = null): array`, testável sem git (D6); com o `callable`, arquivo com rótulo "modificado" que não existe no projeto vira "novo no kit" (P-08, D8) *(alterado em 2026-10-06: QA-03)*.
- A leitura da lista da origem reaproveita `caminhosDeclaradosEm($this->git(['show', "{$origem}:app/Console/Commands/KitUpdate.php"]))`.
- Docs pt/en `atualizando-o-projeto`: uma frase nova na seção do que o `kit:update` traz — caminho que entrou na lista depois da sua versão é comparado com a sua árvore, então o arquivo que falta aparece como "novo no kit" mesmo que o kit não o tenha mudado desde a sua versão.
- Docs pt/en e CHANGELOG: a view ausente é "novo no kit" com a classe nova; na primeira rodada (classe antiga) ainda sai "modificado" — aplicar pelo modo interativo ou `--all`, não `--only-new` *(alterado em 2026-10-06: QA-03)*.
- **Atende**: P-06, P-08 (RQ-01, RQ-05)
- **Logs**: nenhum — o comando já imprime o resumo por arquivo.

### 6. As dez views autorais mudam nesta release, para a classe antiga entregá-las *(passo novo em 2026-10-06: P-07)*

> Skills: `ponytail`

- **Path**: `resources/views/vendor/asmit-resized-column/sticky-panel.blade.php`, `resources/views/vendor/command-center/{components/output,pages/commands,pages/run}.blade.php`, `resources/views/vendor/filament-auth-designer/components/partials/media.blade.php`, `resources/views/vendor/filament-captcha/drivers/{hcaptcha,recaptcha-v2,recaptcha-v3,turnstile}.blade.php`, `resources/views/vendor/filament-clear-cache/livewire/clear-cache-button.blade.php`
- Uma linha de comentário Blade — `{{-- Override autoral do kit, entregue pelo kit:update (issue #148). --}}` — no topo do arquivo; nos cinco que já começam com um bloco `{{-- … --}}`, a linha entra **dentro** desse bloco (o teste `BotaoLimparCacheTest` só descarta o primeiro bloco de comentário antes de comparar com o vendor).
- Prova: `git diff --name-only v0.45.0 HEAD -- resources/views/vendor` lista exatamente os dez arquivos; a verificação ponta a ponta do passo 5 roda também com a classe antiga (sem `--from`).
- **Atende**: P-07 (RQ-01, RQ-02, RQ-05)
- **Logs**: nenhum.

### 4. CHANGELOG

> Skills: `ponytail`

- **Path**: `CHANGELOG.md` → `[Unreleased]` → `### Corrigido`: a entrada (#148) com as cinco pastas, o critério de autoria, o que fica de fora e o efeito para quem já editou uma delas (passa a aparecer no diff do `kit:update`).
- Docs de usuário: `docs/*/comecar/atualizando-o-projeto.md` não enumera `CAMINHOS_DO_KIT` (só cita linhas); nenhuma frase a mudar além das citações do passo 3 — conferido no step 5.
- **Atende**: RQ-01
- **Logs**: nenhum.

## Filosofia de Implementação

> **Ponytail ativo em modo `full`** durante toda a implementação.
> Cada passo deve aplicar a escada de simplicidade:
> 1. Reutilizar código existente antes de criar novo
> 2. Usar stdlib do PHP/Laravel antes de código custom
> 3. Usar features nativas antes de dependências
> 4. Uma linha quando possível
> 5. Mínimo código que funciona
>
> Atalhos deliberados devem ser marcados com `ponytail:` comment.
> Após implementação, rodar `/ponytail:ponytail-review` no diff.
>
> **Caveman ativo em modo `ultra`** (padrão) na comunicação agent ↔ usuário.
> Arquivos wiki (00-06) são boundary do Caveman — escrever em prosa normal.
> Código, commits e PRs também são boundary do Caveman.
>
> **Baseline antes do primeiro commit**: a `main` (`dcb3083`, v0.45.0) está verde — CI do PR #147 (`qualidade`: 4.116 passaram, 6 pulados) e a simulação do cenário 1 (4.119 testes, 0 falhas). Nenhuma falha pré-existente.

## Mapeamentos

Não se aplica.

## Testes

> Ver `04-casos-de-teste.md` para especificação completa dos cenários de backend. Sem `05`: não há superfície de UI nova.

## Verificação Final
- [ ] `/ponytail:ponytail-review` no diff (validar contra over-engineering)
- [ ] `vendor/bin/pint --dirty`
- [ ] `vendor/bin/pest tests/Kit/KitUpdateTest.php --compact` (CTs de backend)
- [ ] Regressão: `DuasRotasDeEntregaTest`, `LogoDarkModeTest`, `BotaoLimparCacheTest`, `CitacoesDeCodigoTest`, `ChecklistDeReleaseTest`, `ConstraintDeDependenciaTest`, `DeployDockerLocalTest`, `DiagramasDaArquiteturaTest`
- [ ] `pest --mutate`: não se aplica à constante; os dois estáticos (`caminhosNovosNaLista`, `rotularDiff`) e o caso novo são provados por mutantes manuais (entrada removida da lista; pasta crua acrescentada; `array_diff` invertido; `D` sem origem como "removido") *(alterado em 2026-10-06: QA-04)*
- [ ] **Custo medido**: não se aplica (sem query)
- [ ] **`/code-review high main...HEAD` + passe de eixos (step 9)** — antes da reconciliação

## Commits
- `:bug: fix(kit-update): overrides autorais de resources/views/vendor passam a viajar pelo kit:update (#148)`
- `:memo: wiki(kit-update): wiki da correção views-vendor-no-kit-update`
