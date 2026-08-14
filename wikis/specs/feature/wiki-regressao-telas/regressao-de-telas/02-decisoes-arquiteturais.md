# Decisões Arquiteturais — Regressão de telas em browser real

## ADR-01: Aceitar o PRD como oráculo, e declarar o que isso custa

**Status**: Aceita
**Data**: 2026-08-14

### Contexto

O requisito (RQ-04) diz: *"como não salvamos o requisito bruto, essa rodada será confiando no
que esta nos PRDs"*. As seis wikis existentes foram escritas antes da v2.10.0 da skill, quando
`00-requisito.md` ainda não fazia parte do padrão. Verificado: nenhuma delas tem o arquivo, e
nenhuma tem a seção `## Superfície de UI`.

Um PRD é a **interpretação** do agente sobre o que foi pedido. Usá-lo como oráculo cria um
risco específico e silencioso: se a interpretação original estava errada, ela contaminou plano,
código e testes de forma **coerente** — e um teste de regressão escrito a partir dela vai
confirmar o erro em verde.

### Decisão

Aceitar o PRD como oráculo, **restringindo o escopo do que esta wiki afirma provar**.

Os CT-B desta wiki respondem duas perguntas, e nenhuma terceira:

1. *A tela está de pé e utilizável?* — oráculo é o **app rodando**, não o PRD. Independente.
2. *O papel entra onde deve?* — oráculo é `roles.painel` + `User::canAccessPanel()`, que é
   **código**, e o PRD apenas descreve.

A pergunta que esta wiki **não** responde: *a tela faz o que o usuário pediu quando pediu
aquela feature?* Esse oráculo foi perdido com o requisito bruto, e nenhum teste que eu escreva
agora o recupera.

### Alternativas Consideradas

1. **Reconstruir o `00-requisito.md` das seis wikis, perguntando ao usuário** — descartada:
   RQ-04 dispensa explicitamente, e o requisito é a autoridade sobre o próprio escopo.
   Reconstruir seis requisitos é uma tarefa maior que esta wiki inteira.
2. **Derivar o `00` do PRD de cada wiki** — descartada, e é a pior das três: produz um arquivo
   com aparência de linha de base independente sendo cópia da interpretação. Falsa
   rastreabilidade é mais perigosa que rastreabilidade ausente, porque desliga a desconfiança.
3. **Não escrever wiki, só os testes** — descartada: RQ-02 pede a wiki.

### Consequências

- **Positivas**: os CT-B ficam ancorados em oráculos independentes (app rodando, código de
  autorização). O que a wiki afirma, ela prova.
- **Negativas**: omissão silenciosa nas seis features originais permanece indetectável. Se
  alguma feature entregou 80% do que foi pedido, esta rodada não descobre.
- **Riscos**: alguém ler "regressão de todas as telas" como "as features estão corretas". A
  seção "Oráculo degradado" do `00-requisito.md` existe para impedir essa leitura.

### Referências

- `00-requisito.md` → seção "Oráculo degradado"
- `wikis/specs/main/*/01-plano-acao.md` — nenhum tem `## Superfície de UI`

---

## ADR-02: Smoke em lote por painel, e telas de `{record}` fora

**Status**: Aceita
**Data**: 2026-08-14

### Contexto

São 52 telas alcançáveis por URL fixa e 13 que exigem um `{record}`. Um CT-B por tela custaria
~5-20 s cada — entre 4 e 16 minutos só de smoke, numa suíte que já leva mais de 10 minutos em
`tests/Kit`. Uma suíte que ninguém roda não cobre nada.

O plugin oferece `visit(['/a', '/b', '/c'])`, que visita todas e aplica a assertion a cada uma.
Confirmado por sonda: `visit(['/app','/admin','/infra'])->assertNoJavaScriptErrors()` passou.

### Decisão

**Um CT-B de lote por painel**, com o array de rotas do painel. 52 telas em 4 cenários
(3 painéis autenticados + 1 de telas públicas).

As 13 telas de `{record}` ficam **fora** do lote.

### Alternativas Consideradas

1. **Um CT-B por tela** — descartada por custo, acima. Também piora o diagnóstico menos do que
   parece: quando um lote falha, o plugin nomeia a URL que falhou.
2. **Incluir as telas de `{record}` com fixture** — descartada. Exigiria factory ou `create()`
   para `User`, `Tenant`, `AgenteIa`, `Role`, `OnboardingFlow`, `OnboardingCondition`, `Audit`,
   `AiRun`, `CommandDefinition`, `QueueMonitor`. Custo alto; valor baixo, porque as telas de
   edição do Filament compartilham o Blade das de criação, que **estão** no lote. A regra de
   negócio de gravação já está coberta por `tests/Kit/PaginasInfraTest.php:86-104` (Livewire).
3. **`--parallel` nos CT-B para recuperar tempo** — descartada: multiplica processos de
   navegador e exige DB por worker. A própria skill alerta. CT-B rodam em série.

### Consequências

- **Positivas**: suíte de browser em tempo utilizável. Cada painel novo custa uma linha no
  array, não um cenário novo.
- **Negativas**: um lote para na primeira tela que falha — as seguintes não são verificadas
  naquele run. Aceito: o ciclo é corrigir e rodar de novo.
- **Riscos**: alguém acrescentar tela e esquecer o array. Mitigação possível na evolução
  seguinte — derivar o array de `Filament::getPanel($id)->getPages()` em vez de listar à mão.
  **Não** feito agora: seria abstração antes da segunda necessidade (Ponytail), e um array
  explícito falha de forma legível.

### Referências

- `01-plano-acao.md` → `## Superfície de UI`
- Sonda descartada — ver ADR-04

---

## ADR-03: Nenhum channel de log novo, contra a exigência default da skill

**Status**: Aceita
**Data**: 2026-08-14

### Contexto

A skill `feature-wiki` exige, em `## Channel de Log da Feature`, que toda feature declare um
channel próprio e que cada passo de execução especifique os logs que emite. É uma exigência
com boa razão: log agrupado por feature isola debug e auditoria.

Esta wiki, porém, **não escreve uma linha em `app/`**. Ela escreve testes.

### Decisão

Nenhum channel novo. Nenhum log novo.

O padrão `[Classe@Método]` continua obrigatório para código de aplicação, e nada aqui o
dispensa — simplesmente não há código de aplicação nesta entrega.

O que esta wiki faz com log é o inverso do padrão: **assere que o log existente é emitido**.
CT-B06 confirma `[User@canAccessPanel]` no channel `autenticacao`, com
`motivo = sem_papel_do_painel`, quando um papel é barrado.

### Alternativas Consideradas

1. **Criar `regressao-de-telas` e logar de dentro dos testes** — descartada: log sobre o ato de
   testar não tem consumidor. Quem lê resultado de teste lê a saída do Pest, não
   `storage/logs/`. Seria arquivo de log nascendo morto, e um channel a mais em
   `config/logging.php` para sempre.
2. **Marcar a exigência como "não aplicável" sem ADR** — descartada: desvio silencioso de
   exigência de skill é exatamente o que o `feature-quality-gate` deve acusar. Documentado é
   decisão; omitido é defeito.

### Consequências

- **Positivas**: `config/logging.php` não ganha channel sem consumidor.
- **Negativas**: quem auditar a wiki pelo checklist da skill encontra o item vazio, e precisa
  ler esta ADR para saber que foi deliberado. Mitigado pelo ponteiro explícito na seção do PRD.
- **Riscos**: virar precedente para pular a exigência em feature que **tem** código. O gate
  desta ADR é estreito: vale quando o diff não toca `app/`.

### Referências

- `01-plano-acao.md` → `## Channel de Log da Feature`
- `config/logging.php:101-107` — channel `autenticacao`
- `tests/Kit/PaineisTest.php:81-90` — a asserção de log que já existe

---

## ADR-04: Sondar o plugin antes de escrever o plano, e descartar a sonda

**Status**: Aceita
**Data**: 2026-08-14

### Contexto

A skill manda escrever a wiki **antes** de implementar, e está certa. Mas quatro fatos sobre o
`pest-plugin-browser` decidem o desenho inteiro dos CT-B, e nenhum deles está explícito na doc
oficial:

- o plugin sobe servidor próprio, ou exige um servidor externo?
- `:memory:` sqlite funciona, ou o navegador vê outro banco?
- `actingAs()` autentica o navegador, ou é preciso logar pela UI?
- quais são os seletores reais das telas do Filament?

Um plano escrito sem essas respostas teria exigido Herd nos pré-requisitos e desenhado login
por UI em 52 telas — e teria morrido na primeira execução.

### Decisão

Sondar com um arquivo de teste **temporário** (`tests/Browser/SondaTest.php`), extrair os
fatos, e **apagar a sonda** antes de escrever o PRD. Os fatos entram no PRD como
`## Contexto → contrato real do plugin`, com o valor observado citado.

Sonda também produziu os seletores, lendo o HTML real via `$page->content()` gravado em
arquivo e filtrado com `grep` — em vez de despejar a página inteira no contexto.

### Alternativas Consideradas

1. **Escrever o PRD a partir da doc e corrigir depois** — descartada: é o anti-padrão que a
   própria skill nomeia como causa nº 1 de plano que não sobrevive à implementação.
2. **Manter a sonda versionada como teste** — descartada: sonda é descoberta, não prova. Ela
   asserta `dump()` de URL e conteúdo de HTML, que não é comportamento que alguém queira
   proteger. Os CT-B do arquivo `05` são a prova.
3. **Usar o Playwright MCP para a sondagem** — ver ADR-05.

### Consequências

- **Positivas**: o PRD cita valores observados (`http://127.0.0.1:60212/app`, `#form.email`),
  não suposições. Os CT-B nasceram com seletor certo na primeira escrita.
- **Negativas**: a ordem dos passos do PRD não é a ordem cronológica real — passo 1 aparece
  como "CONCLUÍDO" antes do plano existir. Registrado no próprio passo, para não parecer erro.
- **Riscos**: normalizar "implementar antes de planejar". O gate: vale só para descobrir
  **contrato de ferramenta**, nunca para adiantar regra de negócio.

### Referências

- `01-plano-acao.md` → passo 1 e `## Contexto`
- Commit `8e5221d`

---

## ADR-05: Playwright MCP permitido, não usado

**Status**: Aceita
**Data**: 2026-08-14

### Contexto

RQ-10 autoriza: *"pode usar o mcp do playwrite também nessa rodada, mas a skill trata usando o
pest-browser"*. A skill posiciona o MCP como **ferramenta de observação** — ele nunca produz
cobertura, nunca substitui um CT-B; existe para o agente *ver* a página quando o teste falha.

Nesta sessão o MCP do Playwright **não está configurado** (nenhum `browser_*` disponível). O
MCP do Laravel Boost também não está — `search-docs` indisponível, o que empurrou a pesquisa de
doc para a doc oficial na web.

### Decisão

Não usar o MCP. Usar o fallback nativo do plugin, que a skill prevê, na ordem:

1. `$page->screenshot()` no ponto da falha — foi assim que a tela de login foi vista, e foi o
   screenshot que revelou que o botão é *"Login"* e não *"Entrar"*
2. `$page->content()` **filtrado** — gravado em arquivo e lido com `grep -oE '<input[^>]*'`,
   nunca despejado inteiro no contexto
3. Leitura do Blade / componente quando os dois acima não bastassem — não foi necessário

### Alternativas Consideradas

1. **Configurar o MCP nesta sessão** — descartada: exigiria editar config de MCP do usuário
   (`--isolated --headless --caps=testing`), que é mudança de ambiente fora do escopo do
   requisito. E o fallback resolveu 100% dos seletores.
2. **Registrar sessões de MCP como evidência no arquivo `05`** — descartada por proibição
   explícita da skill: sessão de MCP não é cobertura, e registrá-la como tal criaria falsa
   cobertura — pior que cobertura ausente.

### Consequências

- **Positivas**: nenhuma dependência de ambiente além do que o `composer.json` declara. A
  suíte roda em qualquer máquina com PHP 8.4 + Node.
- **Negativas**: descobrir seletor por screenshot + grep é mais lento que
  `browser_generate_locator`. Custou dois ciclos de sonda.
- **Riscos**: nenhum. RQ-10 diz "pode", não "deve".

### Referências

- `00-requisito.md` → RQ-10
- Screenshot da sonda em `tests/Browser/Screenshots/` (gitignored, descartado)

---

## ADR-06: `assertNoJavaScriptErrors()` no lote, `assertNoSmoke()` fora

**Status**: Aceita
**Data**: 2026-08-14

### Contexto

O plugin oferece dois níveis:

- `assertNoJavaScriptErrors()` — reprova **erro** de JS
- `assertNoSmoke()` — reprova erro de JS **e** qualquer `console.log`

O kit registra 40 pacotes Filament de terceiro. `console.log` esquecido em pacote de vendor é
comum, e não é defeito do kit: não quebra tela, não afeta usuário, e a correção não está sob
controle de quem mantém o kit.

### Decisão

`assertNoJavaScriptErrors()` nos CT-B de lote das 52 telas.

`assertNoSmoke()` reservado para as telas **próprias do kit** — as de autenticação
(`TelaLogin`, `RegistroPorConvite`, `TelaBloqueio`) e o dashboard de cada painel. Nessas o kit
tem autoria e um `console.log` é dele.

### Alternativas Consideradas

1. **`assertNoSmoke()` em tudo** — descartada: suíte vermelha por `console.log` de vendor.
   Uma suíte que fica vermelha por coisa que ninguém vai corrigir treina o time a ignorar
   vermelho, e aí ela deixa de proteger o que importa.
2. **`assertNoJavaScriptErrors()` em tudo** — descartada por baratear demais: nas telas do kit
   o `console.log` **é** sujeira própria, e pegar de graça vale.

### Consequências

- **Positivas**: vermelho da suíte significa defeito acionável.
- **Negativas**: `console.log` em tela de plugin passa sem registro. Aceito — se aparecer,
  aparece no CT-B de dashboard, que usa `assertNoSmoke()`.
- **Riscos**: `console.log` **do kit** numa tela de Resource escapar. Mitigação: a tela de
  Resource é gerada pelo Filament; código próprio do kit nela é schema PHP, que não emite JS.

### Referências

- `01-plano-acao.md` → passo 5
- `05-casos-de-teste-browser.md` → CT-B01 a CT-B04

---

## ADR-07: Dívida de acessibilidade vira `->todo()`, não vermelho

**Status**: Aceita
**Data**: 2026-08-14

### Contexto

A sonda rodou `assertNoAccessibilityIssues()` em `/admin` e encontrou dois problemas reais,
ambos em pacote de terceiro:

- **critical**: `<button wire:click="clear">` do *Clear Cache* sem texto acessível — nenhum
  `aria-label`, nenhum `title`, nenhum texto interno. Pacote `cms-multi/filament-clear-cache`.
- **serious**: contraste 4.25:1 no `.environment-indicator` (`#e60076` sobre `#fdf2f8`),
  abaixo do mínimo WCAG de 4.5:1. Pacote `pxlrbt/filament-environment-indicator`.

A skill é explícita: *"o Ponytail não corta acessibilidade"*. Então o CT-B de a11y tem de
existir. Mas RQ-07 coloca a **correção** depois desta entrega, e as duas causas estão em
`vendor/`.

### Decisão

O CT-B de acessibilidade **existe e fica marcado `->todo()`**, com as duas dívidas citadas na
descrição e o ponteiro para `06-divida-tecnica.md`.

`->todo()` e não comentado, e não removido: o cenário aparece na saída do Pest como pendência
nomeada em cada run. Uma dívida que o time vê toda vez que roda a suíte é uma dívida viva; uma
dívida em comentário é uma dívida esquecida.

### Alternativas Consideradas

1. **Deixar vermelho** — descartada: suíte que nasce vermelha por dívida conhecida de vendor
   perde a função de sinal.
2. **Não escrever o CT-B** — descartada: a skill proíbe cortar acessibilidade, e sem o cenário
   a dívida sai do radar na próxima wiki.
3. **Corrigir agora** — descartada: as duas causas estão em `vendor/`. Corrigir exigiria
   `renderHook` de CSS para o contraste e patch ou PR upstream para o botão. Ambos mexem em
   `app/`, que RQ-07 coloca fora desta entrega.

### Consequências

- **Positivas**: dívida rastreada e visível, sem custo de suíte falsamente vermelha.
- **Negativas**: a11y não fica protegida por teste até a correção. Regressão nova de a11y não
  é pega nesta rodada.
- **Riscos**: `->todo()` virar depósito. Mitigação: `06-divida-tecnica.md` registra custo
  estimado e caminho de correção de cada uma, para a decisão da próxima evolução ser informada.

### Referências

- `06-divida-tecnica.md` → DT-01, DT-02
- `01-plano-acao.md` → passo 8
- Refine: nenhuma
