---
name: requirement-to-rule
version: 1.2.0
description: >
  Transforma decisões e restrições de um requisito em Project Rules do Laravel Boost
  (.ai/rules/). Invoque quando uma decisão precisar valer para agentes futuros em
  qualquer sessão — não só na wiki da feature atual. Valida cada candidato contra
  4 gates (durável, escopável por path, não-inferível, não-redundante), deduplica
  contra o index existente, exige aprovação explícita do usuário e grava sempre via
  a tool MCP record-rule do Boost. Cria .ai/rules/index.md no modelo oficial quando
  não existe e o mantém atualizado — uma linha por glob — porque rule fora do índice
  não é descoberta pelos agentes. Complementa a feature-wiki (step 8) e o
  infer-conventions do Boost: aquele varre o código existente, este parte do requisito.
---

# Requirement → Rule — Decisão do Requisito Vira Regra Durável

## Glossário

| Sigla | Significado |
|-------|-------------|
| **Rule** | Project Rule do Boost — arquivo em `.ai/rules/*.md` escopado por glob de path |
| **ADR** | Architecture Decision Record — decisão registrada na wiki da feature |
| **PRD** | Product Requirements Document — plano de ação da feature |
| **Guideline** | Instrução do Boost sobre o **ecossistema** (Laravel, Livewire, Pest), carregada upfront |
| **Skill** | Módulo de conhecimento carregado on-demand por tarefa |

## As três camadas — não confundir

| Camada | Ensina | Carregamento | Quem mantém |
|--------|--------|--------------|-------------|
| **Guidelines** (`.ai/guidelines/`) | como escrever **Laravel** | upfront, sempre | Boost (`boost:update`) |
| **Skills** (`.ai/skills/`) | padrões de um **domínio/tarefa** | on-demand | Boost + você |
| **Rules** (`.ai/rules/`) | como escrever **a sua aplicação** | por glob, quando o arquivo casa | **você**, versionado no git |

> **Regra de ouro**: conhecimento de ecossistema **nunca** vira rule. O Boost já cobre e atualiza isso via `boost:update`; a sua rule apodreceria na próxima versão do framework. Rule é só o que é **específico da sua aplicação**.

## Quando Invocar

- Step 9 da skill `feature-wiki` identificou candidatos a rule e o usuário aprovou
- O usuário disse explicitamente "isso vira rule", "lembre disso para sempre", "todo agente precisa saber disso"
- Uma ADR foi aceita e a consequência dela vale para código futuro fora da feature
- Uma armadilha foi descoberta em implementação e outro agente cairia nela

### Quando NÃO Invocar

- **Decisão de uma feature só**: fica na ADR. Rule é para o que atravessa features.
- **Conhecimento de framework**: "usar Form Request para validação" — já está nas guidelines do Boost.
- **Coisa que linter resolve**: formatação, ordem de imports, tipos faltando → Pint, Rector, PHPStan.
- **Varredura de convenções do código existente**: usar o skill `infer-conventions` do Boost, que foi feito para isso.
- **Preferência pessoal de sessão**: memória do agente, não rule (rule é artefato de equipe, versionado).
- **Fato volátil**: número de versão, nome de sprint, URL de ambiente temporário.

---

## Os 4 Gates

Candidato só vira rule se passar em **todos**. Registrar o veredito de cada gate na apresentação ao usuário.

### Gate 1 — Durável

> Vale além desta feature e desta sprint?

- ✅ "Valores monetários são `integer` em centavos, nunca `float`"
- ❌ "Nesta feature o status inicial é `pending`" — regra de negócio de um fluxo, vai na ADR

### Gate 2 — Escopável por path

> Dá para expressar em glob?

Rules são carregadas por casamento de glob. Se não se consegue nomear os paths onde a regra se aplica, **não é rule** — é ADR ou guideline interna.

- ✅ `app/Models/**`, `app/Http/Controllers/Api/**`, `database/migrations/**`
- ❌ "vale para o projeto todo" → glob `**` é anti-padrão: carrega em cada edição e vira ruído permanente

**Preferir o glob mais estreito que cobre o caso.** Uma rule em `app/Models/Enrollment.php` é melhor que a mesma rule em `app/**`.

### Gate 3 — Não-inferível

> Um agente competente, lendo o código ao redor, erraria?

Este é o gate que mais elimina candidatos. Se o padrão é evidente nos arquivos vizinhos, o agente acerta sozinho e a rule só consome contexto.

- ✅ "`Enrollment::find()` aplica scope global de tenant — usar `withoutGlobalScopes()` para busca administrativa": invisível no arquivo, consequência silenciosa
- ❌ "Controllers ficam em `app/Http/Controllers`": o agente vê isso na primeira listagem

### Gate 4 — Não-redundante

> Não é default do framework, não é coberto por ferramenta, não duplica rule existente?

Checar, nesta ordem:

1. `Read .ai/rules/index.md` → alguma rule já cobre este glob? Se sim, **atualizar** aquela rule em vez de criar nova
2. Está em `pint.json` / `rector.php` / `phpstan.neon`? → é enforcement automático, não rule
3. **É comportamento documentado do ecossistema?** → consultar a tool MCP **`search-docs`** do Boost com a afirmação do candidato. Se a Documentation API já responde, é **guideline**, não rule — e uma rule sua duplicando doc oficial apodrece na próxima versão do pacote
4. Existe teste de arquitetura que já garante? → aponte a rule para o teste, não repita a regra

**O teste empírico do gate 4** — `search-docs` transforma "acho que isso é conhecimento de framework" em verificação:

```text
Candidato: "Form Requests devem ter o método authorize() retornando a policy"
→ search-docs: "Laravel 13 form request authorize method"
→ Retornou a doc oficial explicando exatamente isso
→ REPROVA no gate 4: é guideline do ecossistema, não rule da aplicação

Candidato: "Enrollment::find() aplica scope global de tenant"
→ search-docs: "Laravel global scope Enrollment tenant"
→ Retornou só a doc genérica de global scopes, nada sobre o comportamento deste model
→ APROVA no gate 4: o fato é da aplicação, não do framework
```

> **Cobertura do `search-docs`**: Laravel 10–13, Filament 2–5, Livewire 1–4, Inertia 1–2, Flux UI 2, Nova 4–5, **Pest até 4.x**, Tailwind 3–4. Fora dessa lista (Pest 5, `pest-plugin-browser`/Playwright, pacotes de terceiros) o gate 4 é avaliado contra a doc oficial do pacote — e uma restrição sobre pacote de terceiro **pode** legitimamente virar rule, porque não há guideline do Boost para ela.

---

## Fluxo de Execução

### 1. Coletar candidatos

**Se vindo da feature-wiki**: ler `01-plano-acao.md`, `02-decisoes-arquiteturais.md` e a seção "Notas de Implementação" do `03-progresso.md`.

**Se vindo de um requisito solto** (card, ticket, conversa): extrair as afirmações normativas — frases com "sempre", "nunca", "todo", "deve", "não pode".

### 2. Verificar o estado atual das rules

```bash
# existe o diretório de rules?
ls .ai/rules/
```

- `Read .ai/rules/index.md` — mapa glob → arquivo
- `Read` as rules dos globs que o candidato tocaria

**Diagnóstico dos três cenários possíveis**:

| Cenário | Significado | Ação |
|---|---|---|
| `.ai/rules/index.md` existe | projeto já usa rules | usar as linhas para o gate 4 (dedupe) e decidir entre atualizar rule existente ou criar nova |
| `.ai/rules/` existe, mas **sem `index.md`** | rules gravadas sem índice — estão **invisíveis** para os agentes | avisar o usuário; o índice será criado no passo 7, já incluindo as rules órfãs encontradas |
| `.ai/rules/` não existe | primeira rule do projeto, ou Boost ausente / `BOOST_RULES_ENABLED=false` | registrar que diretório e índice serão criados no passo 7; se o Boost estiver ausente, ver "Fallback" |

> **Não criar nada aqui.** Este passo é só diagnóstico — nenhum arquivo é escrito em `.ai/rules/` antes da aprovação explícita do usuário (passo 5).

### 3. Aplicar os 4 gates

Descartar candidato que falhe em qualquer gate, **dizendo qual gate falhou**. Não silenciar descarte — o usuário precisa saber que o candidato foi considerado e por que caiu.

### 4. Preferir enforcement automático (escada de rules)

Antes de escrever prosa, subir esta escada:

1. **Teste de arquitetura** (`pest --arch`) resolve? → escrever o teste; rule fica em 1 linha apontando para ele
2. **PHPStan / Larastan** pega? → configurar a regra; sem rule em prosa
3. **Rector** pode reescrever automaticamente? → adicionar a regra ao `rector.php`
4. **Pint** normaliza? → configurar o preset
5. **Só então**: rule em prosa

> Exemplo: "Controllers devem estender `BaseController`" é um `pest --arch` de uma linha:
> `arch()->expect('App\Http\Controllers')->toExtend('App\Http\Controllers\BaseController');`
> A rule então diz: *"Enforçado em `tests/Arch/ControllersTest.php` — não contornar."*

### 5. Apresentar ao usuário e ESPERAR decisão

**Nunca gravar sem aprovação explícita.** Formato:

```text
Candidato 1 — [origem: ADR-02 / Nota de implementação / requisito]
  Título:    {título curto, imperativo}
  Glob:      {glob mais estreito que cobre}
  Regra:     {uma frase — a restrição}
  Por quê:   {consequência de ignorar — é isto que faz o agente obedecer}
  Evidência: {arquivo:linha ou seção da wiki}
  Gates:     durável ✅ | escopável ✅ | não-inferível ✅ | não-redundante ✅
  Enforcement: {prosa | pest --arch em tests/Arch/X.php | phpstan | rector}

Descartados:
  - {candidato}: falhou no gate {N} — {motivo}

Gravar? (número, "todos", "nenhum")
```

### 6. Gravar via `record-rule` (obrigatório)

Gravar **sempre** pela tool MCP `record-rule` do Boost, passando `glob`, `title` e `note`. A doc do Boost é explícita:

> "You should always record rules using the `record-rule` tool rather than creating rule files by hand. Boost regenerates `.ai/rules/index.md` as part of recording a rule, and agents rely on that index to discover which rules apply to the file they are working on. A rule file that is added manually will not be discovered until the index is next regenerated."

Ou, em linguagem natural para o agente com Boost ativo:

```text
Remember that all money values are stored as integer cents, never as floats.
```

### 7. Garantir o índice (`.ai/rules/index.md`)

**Obrigatório e não-negociável**: sem a linha no índice, a rule existe no disco e é **invisível** para os agentes. Ver a seção [Índice de Rules](#índice-de-rules-airulesindexmd) para o procedimento completo — criar se não existir, atualizar após a aprovação, conferir sempre.

### 8. Verificar e commitar

- `Read .ai/rules/index.md` e confirmar a linha `{glob} | .ai/rules/{area}.md`
- `Read` o arquivo de rule gravado e checar se o `note` ficou fiel ao aprovado
- **Commitar `.ai/rules/` inteiro** (rule + índice) — rules são artefato de equipe, versionado (diferente de `.mcp.json` e `CLAUDE.md`, que o Boost regenera)
- Commit sugerido: `:memo: rules: {título da rule}` com corpo citando a origem (ADR / wiki / card)

---

## Índice de Rules (`.ai/rules/index.md`)

O Boost mantém um índice que mapeia glob → arquivo de rule. **Os agentes são instruídos a consultar esse índice antes de planejar ou editar qualquer arquivo** — uma rule fora do índice não é descoberta, não importa quão bem escrita esteja.

### Modelo oficial (doc do Boost)

```markdown
# Project Rules Index

Before planning or editing, find the row whose globs match the file's path and read that rule file.

| Applies to | Rule file |
| --- | --- |
| app/Http/Controllers/** | .ai/rules/controllers.md |
| app/Models/** | .ai/rules/models.md |
```

> Manter o cabeçalho e a frase de instrução **exatamente** como acima. Ela não é decoração: é a instrução que o agente lê para saber o que fazer com a tabela. Reescrever ou traduzir essa linha quebra o contrato com os agentes que esperam o formato do Boost.

### Procedimento

**Passo 2 do fluxo — diagnóstico (antes da aprovação):**

- `Read .ai/rules/index.md`
- **Se o arquivo não existir**: registrar que será criado, **sem criar ainda**. A criação acontece depois da aprovação do usuário — nada é escrito em `.ai/rules/` antes do "sim".
- Se existir: usar as linhas para o gate 4 (dedupe) e para decidir entre **atualizar rule existente** ou criar nova.

**Passo 7 do fluxo — após a aprovação:**

| Situação | Ação |
|---|---|
| `record-rule` disponível **e** índice foi regenerado com a linha nova | nada a fazer além de conferir |
| `record-rule` disponível **mas** índice não existe / não tem a linha | criar/atualizar o índice à mão, no modelo oficial acima, e avisar o usuário da inconsistência |
| `record-rule` indisponível (fallback) | criar/atualizar o índice à mão, obrigatoriamente |

**Ao criar o arquivo pela primeira vez**: copiar o modelo oficial, substituindo as linhas de exemplo pelas rules reais do projeto. Não deixar as linhas de exemplo (`controllers.md`, `models.md`) se elas não existirem — índice apontando para arquivo inexistente faz o agente perder tempo tentando ler.

**Ao atualizar**: uma linha por glob. Se a rule cobre 2 globs, são **2 linhas** apontando para o mesmo arquivo:

```markdown
| app/Models/** | .ai/rules/models.md |
| app/Services/Billing/** | .ai/rules/models.md |
```

### Regras de manutenção do índice

1. **Uma linha por glob**, nunca globs concatenados numa célula
2. **Path do arquivo sempre relativo à raiz do projeto** e começando com `.ai/rules/` — igual ao modelo oficial
3. **Ordenar por especificidade**: globs mais estreitos antes dos mais amplos, para que o agente encontre a rule mais específica primeiro
4. **Sem linha órfã**: se uma rule for removida, remover as linhas dela do índice no mesmo commit
5. **Sem duplicata**: mesmo glob apontando para dois arquivos é ambiguidade — consolidar as rules num arquivo só
6. **O índice não recebe conteúdo de rule** — é só o mapa. Restrição em prosa vai no arquivo da área

### Verificação final do índice

Depois de gravar, conferir estas quatro coisas:

- [ ] `.ai/rules/index.md` existe e tem o cabeçalho + a frase de instrução oficiais
- [ ] Existe uma linha para **cada** glob da rule aprovada
- [ ] Todo path citado no índice corresponde a um arquivo que existe de fato
- [ ] Nenhuma linha ficou órfã ou duplicada

---

## Modelo Base do Conteúdo da Rule

O `record-rule` recebe `glob`, `title` e `note`. O modelo abaixo é a **forma do `note`** — e também o formato final do arquivo gravado em `.ai/rules/{area}.md`:

```markdown
---
paths:
  - app/Models/**
  - app/Services/Billing/**
---

# Models

## Valores monetários são inteiros em centavos

Todo campo monetário é persistido como `integer` representando centavos — nunca
`float` ou `decimal`. Converter na borda (Form Request na entrada, cast na saída).
Usar `float` introduz erro de arredondamento que se acumula em relatórios de
fechamento e não é detectado pelos testes unitários de cada operação isolada.

Enforçado parcialmente por `tests/Arch/MoneyTest.php`. Origem: ADR-02 de
`wikis/specs/ferro/579/cobranca-lote/02-decisoes-arquiteturais.md`.
```

### Anatomia obrigatória do `note`

| Parte | Regra | Por quê |
|---|---|---|
| **Título (`##`)** | imperativo, curto, uma restrição só | O agente varre títulos; título vago não é aplicado |
| **Restrição** | 1-2 frases, afirmativa, sem hedge ("deve preferencialmente" → não) | Ambiguidade vira desvio |
| **Consequência** | o que quebra se ignorar, concretamente | **A parte mais importante.** É a consequência que faz o agente obedecer em vez de "otimizar" |
| **Escape hatch** | quando a regra **não** se aplica, se houver | Rule sem exceção declarada é contornada em silêncio |
| **Enforcement / Origem** | teste, ferramenta, ou ADR de origem | Rastreabilidade e prova de que não é opinião solta |

### Regras de escrita

1. **Uma rule = uma restrição.** Duas restrições = dois blocos `##`.
2. **Escrever o porquê, não só o quê.** O exemplo oficial do Boost termina em consequência: *"will leak data across tenants"*. Copiar essa disciplina.
3. **Presente do indicativo, afirmativo.** "Todo controller estende `BaseController`" vence "não deveria estender o controller do framework".
4. **Nomes totalmente qualificados** para classes (`App\Http\Controllers\BaseController`).
5. **Sem número de versão volátil** no corpo — apodrece.
6. **Prosa normal, não terse.** Rules são boundary do Caveman: ambiguidade aqui se propaga para todo agente futuro.
7. **Máximo ~10 linhas por rule.** Rule longa é guideline disfarçada e não será lida por inteiro.

---

## Áreas Sugeridas para Agrupar Rules

O Boost arquiva a rule "under the matching area". Manter poucas áreas, com globs estreitos:

| Arquivo | Glob típico | O que vive nele |
|---|---|---|
| `.ai/rules/models.md` | `app/Models/**` | invariantes de dados, scopes globais, casts obrigatórios |
| `.ai/rules/controllers.md` | `app/Http/Controllers/**` | base class, autorização, formato de resposta |
| `.ai/rules/requests.md` | `app/Http/Requests/**` | padrão de validação, mensagens |
| `.ai/rules/jobs.md` | `app/Jobs/**` | idempotência, retries, channel de log |
| `.ai/rules/migrations.md` | `database/migrations/**` | convenções de nome, FK, seeder-em-migration, guard de environment |
| `.ai/rules/testing.md` | `tests/**` | estratégia de DB, factories obrigatórias, o que precisa CT-B |
| `.ai/rules/livewire.md` | `app/Livewire/**` | padrão de `fail()`, log de validação |
| `.ai/rules/filament.md` | `app/Filament/**` | resources, policies, `data-test` obrigatório |

---

## Fallback — Boost ausente ou rules desativadas

Se `record-rule` não estiver disponível (`BOOST_RULES_ENABLED=false`, Boost não instalado, agente sem MCP):

1. **Avisar o usuário** de que a gravação manual não é o caminho recomendado pela doc do Boost
2. Criar/editar `.ai/rules/{area}.md` com o frontmatter `paths:` e o corpo no modelo acima
3. **Criar `.ai/rules/index.md`** se não existir, usando o [modelo oficial](#modelo-oficial-doc-do-boost) — cabeçalho e frase de instrução idênticos, linhas de exemplo substituídas pelas rules reais
4. **Adicionar uma linha por glob** à tabela do índice — sem isso a rule não é descoberta
5. Se havia rules órfãs (sem linha no índice), incluí-las agora — o índice deve refletir tudo que existe em `.ai/rules/`
6. Registrar no commit que a rule e o índice foram gravados manualmente, para reconciliar quando o Boost voltar (`record-rule` regenera o índice e sobrescreve edições manuais)

Para agentes sem suporte a `.ai/rules` (Windsurf, Cursor, Cline), espelhar o conteúdo no formato do agente (`.windsurf/rules/`, `.cursor/rules/`), mantendo `.ai/rules/` como fonte da verdade.

---

## Anti-padrões

| Anti-padrão | Por que é ruim |
|---|---|
| Glob `**` ou `app/**` | Carrega em quase toda edição; vira imposto permanente de contexto |
| Rule sem consequência | O agente trata como sugestão e "otimiza" por cima |
| Rule que repete guideline do Boost | Duplicação que apodrece na próxima versão do framework |
| Reprovar/aprovar o gate 4 "de cabeça" | `search-docs` existe para tornar isso verificável — usar |
| Rule que o Pint/Rector/PHPStan já garante | Prosa onde a máquina já resolve; viola a escada |
| Gravar sem aprovação do usuário | Rules são artefato de equipe; entram no git e afetam todos |
| Escrever o arquivo à mão com Boost ativo | O `index.md` não é regenerado e a rule fica invisível |
| Gravar a rule e não conferir o `index.md` | Rule no disco sem linha no índice = rule que nenhum agente lê |
| Deixar as linhas de exemplo do modelo no índice | Índice aponta para `controllers.md`/`models.md` inexistentes e o agente perde tempo |
| Traduzir ou reescrever a frase de instrução do índice | É a instrução que o agente lê para usar a tabela; alterá-la quebra o contrato |
| Mais de 3 rules por feature | Inflação: quanto mais rules, menos cada uma é respeitada |
| Rule contando história ("decidimos em reunião que...") | Isso é ADR. Rule é imperativa e atemporal |

---

## Checklist Final

- [ ] Candidatos coletados de fonte concreta (ADR, nota de implementação, requisito) com evidência `arquivo:linha`
- [ ] `.ai/rules/index.md` lido; rules dos globs afetados lidas
- [ ] Cada candidato avaliado nos 4 gates, com veredito registrado
- [ ] Gate 4 verificado com `search-docs` — candidato que a Documentation API já responde foi reprovado como guideline
- [ ] Descartados comunicados ao usuário com o gate que falhou
- [ ] Escada de enforcement subida — automação preferida à prosa quando possível
- [ ] Atualização de rule existente preferida à criação de nova
- [ ] Glob é o mais estreito que cobre o caso (nunca `**`)
- [ ] `note` contém restrição + consequência + origem
- [ ] Aprovação explícita do usuário obtida — nada escrito em `.ai/rules/` antes disso
- [ ] Gravado via `record-rule` (ou fallback documentado no commit)
- [ ] `.ai/rules/index.md` **existe** — criado no modelo oficial se era a primeira rule do projeto
- [ ] Índice tem **uma linha por glob** da rule aprovada, apontando para o arquivo correto
- [ ] Todo path citado no índice existe de fato; nenhuma linha órfã, duplicada ou de exemplo
- [ ] Cabeçalho e frase de instrução do índice preservados na forma oficial
- [ ] `.ai/rules/` commitado **inteiro** (rule + índice) com gitmoji `:memo: rules:`
- [ ] Teto de 3 rules por feature respeitado

## Skills Companheiras

| Skill | Relação |
|---|---|
| `feature-wiki` | Produz as fontes (ADR, notas, PRD). O step 9 dela invoca esta skill |
| `feature-test-design` | Fonte de candidato com evidência forte: linha nova do **checklist de taxonomia de defeito**, nascida de defeito que escapou para produção. Se generaliza além da feature, é rule — de preferência com enforcement em `pest --arch` |
| `infer-conventions` (Boost) | Caminho inverso: varre o **código existente** para bootstrapar rules. Rodar uma vez, no início do projeto; esta skill é o incremento contínuo |
| `ponytail` | A escada de enforcement é a escada de simplicidade aplicada a rules: automação antes de prosa, nada antes de automação desnecessária |
| `pest-testing` | Materializa o enforcement em `pest --arch` quando o gate 4 aponta para automação |

> **Caveman**: arquivos de rule são **boundary** — prosa normal. A rule é lida por todo agente futuro; compressão aqui multiplica ambiguidade.
