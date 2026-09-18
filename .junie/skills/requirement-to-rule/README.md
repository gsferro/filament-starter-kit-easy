# requirement-to-rule — Decisão da Wiki Vira Regra Durável

> **Skill**: [`SKILL.md`](SKILL.md) · versão **1.1.0**
> Este README fala com a **pessoa**: por que a skill existe, o que ela entrega, dependências e limitações. O procedimento que o agente segue está no `SKILL.md` e não é duplicado aqui.

## Índice

- [O que a skill faz](#o-que-a-skill-faz)
- [Quando é invocada](#quando-é-invocada)
- [Do Requisito para a Rule](#-do-requisito-para-a-rule)
- [search-docs como teste do gate 4](#search-docs-como-teste-empírico-do-gate-4)

---

## O que a skill faz

Transforma decisão e restrição descobertas numa feature em **Project Rules do Laravel Boost** (`.ai/rules/`) — arquivos escopados por glob de path que **qualquer agente, em qualquer sessão futura, carrega automaticamente** ao editar um arquivo que casa com o glob.

**O problema que resolve**: a wiki tem memória, o agente não. Uma ADR aceita só é lida por quem abrir aquela pasta. Na feature seguinte, o agente não sabe que a decisão existe e repete o erro que a ADR já resolveu.

### Vantagens

| Vantagem | Como |
|---|---|
| Decisão para de morrer na wiki | ADR generalizável vira rule carregada por glob |
| Sem inflação de contexto | 4 gates eliminatórios + teto de 3 rules por feature |
| Automação antes de prosa | escada de enforcement: `pest --arch` → PHPStan → Rector → Pint → só então texto |
| Rule que o agente obedece | anatomia obrigatória exige a **consequência** de ignorar, não só a regra |
| Rule descoberta de fato | grava via `record-rule` e confere o `.ai/rules/index.md` |
| Você decide, não o agente | nada é gravado sem aprovação explícita |

### Quando é invocada

- **Step 9 da [`feature-wiki`](../feature-wiki/README.md)** — automático, ao fechar a feature
- Quando você disser "isso vira rule", "lembre disso para sempre", "todo agente precisa saber disso"

**Não** invocar para: decisão de uma feature só (fica na ADR), conhecimento de framework (é guideline do Boost), coisa que Pint/Rector/PHPStan resolve, ou varredura de convenções do código existente — para isso existe o `infer-conventions` do Boost.

## 📐 Do Requisito para a Rule

### O problema

Hoje uma decisão registrada em `02-decisoes-arquiteturais.md` só é lida por quem abrir **aquela** wiki. Na sessão seguinte, em outra feature, o agente não sabe que a decisão existe e repete o erro que a ADR já resolveu. A wiki tem memória; o **agente** não.

**Project Rules do Laravel Boost** (`.ai/rules/`) fecham esse ciclo: são carregadas automaticamente por glob de path, para qualquer agente, em qualquer sessão. A skill `requirement-to-rule` faz a ponte entre as duas coisas.

### As três camadas — não confundir

| Camada | Ensina | Carregamento | Quem mantém |
|---|---|---|---|
| **Guidelines** (`.ai/guidelines/`) | como escrever **Laravel** | upfront, sempre presente | Boost (`boost:update`) |
| **Skills** (`.ai/skills/`) | padrões de um **domínio/tarefa** | on-demand | Boost + você |
| **Rules** (`.ai/rules/`) | como escrever **a sua aplicação** | por glob, quando o arquivo casa | **você**, versionado no git |

> **Regra de ouro**: conhecimento de ecossistema **nunca** vira rule. O Boost já cobre e atualiza via `boost:update`; a sua rule apodreceria na próxima versão do framework. Rule é só o que é específico da sua aplicação.

### De onde vêm os candidatos

O **step 8** da `feature-wiki` varre a wiki recém-concluída em três lugares:

| Fonte | O que procurar | Exemplo real |
|---|---|---|
| `02-decisoes-arquiteturais.md` | ADR cuja **consequência generaliza** além desta feature | "Todo valor monetário é `integer` em centavos" |
| `03-progresso.md` → Notas de Implementação | **armadilha descoberta no código**, invisível para quem lê o arquivo | "`Enrollment::find()` aplica scope global de tenant" |
| `01-plano-acao.md` | padrão obrigatório que a wiki repete e que vale para o projeto todo | padrão de log `[Classe@Método]` + channel por feature |

A terceira linha é reveladora: o padrão de log é reescrito em **toda** wiki desde a v1. Isso é a definição literal de rule pelo Boost — *"anything you would otherwise need to explain again in every new session"*.

### Os 4 gates

Candidato só vira rule se passar em **todos**:

| # | Gate | Pergunta | Reprova quando |
|---|---|---|---|
| 1 | **Durável** | vale além desta feature e desta sprint? | regra de negócio de um fluxo → fica na ADR |
| 2 | **Escopável por path** | dá para expressar em glob? | "vale para o projeto todo" → glob `**` é anti-padrão |
| 3 | **Não-inferível** | um agente competente, lendo o código ao redor, erraria? | se ele acertaria sozinho, a rule é só imposto de contexto |
| 4 | **Não-redundante** | não é default do framework, não é coberto por Pint/Rector/PHPStan, não duplica rule existente? | qualquer duplicação |

O gate 3 é o que mais elimina candidatos — e é o mais importante. `"Controllers ficam em app/Http/Controllers"` reprova; `"Enrollment::find() aplica scope global de tenant"` passa.

### Escada de enforcement (Ponytail aplicado a rules)

Antes de escrever prosa, subir a escada:

1. **Teste de arquitetura** (`pest --arch`) resolve? → escrever o teste; a rule fica em 1 linha apontando para ele
2. **PHPStan / Larastan** pega? → configurar; sem rule em prosa
3. **Rector** reescreve automaticamente? → adicionar ao `rector.php`
4. **Pint** normaliza? → configurar o preset
5. **Só então**: rule em prosa

Exemplo: *"Controllers devem estender `BaseController`"* é um arch test de uma linha —

```php
arch()->expect('App\Http\Controllers')->toExtend('App\Http\Controllers\BaseController');
```

— e a rule então diz apenas: *"Enforçado em `tests/Arch/ControllersTest.php` — não contornar."*

### Decisão é sempre do usuário

A skill **nunca** grava rule sem aprovação explícita. Formato de apresentação:

```text
Candidato 1 — [origem: ADR-02]
  Título:    Valores monetários são inteiros em centavos
  Glob:      app/Models/**, app/Services/Billing/**
  Regra:     Campo monetário é integer em centavos, nunca float
  Por quê:   float acumula erro de arredondamento em fechamento
  Evidência: 02-decisoes-arquiteturais.md ADR-02 + app/Models/Invoice.php:34
  Gates:     durável ✅ | escopável ✅ | não-inferível ✅ | não-redundante ✅
  Enforcement: pest --arch em tests/Arch/MoneyTest.php + prosa

Descartados:
  - "Controllers em app/Http/Controllers": falhou no gate 3 — o agente infere

Gravar? (número, "todos", "nenhum")
```

**Teto: 3 candidatos por feature.** Cada rule é imposto permanente de contexto em todo arquivo que casa com o glob — inflação de rules degrada o agente em vez de ajudar.

### Gravação: sempre via `record-rule`

A doc do Boost é explícita, e a skill obedece:

> "You should always record rules using the `record-rule` tool rather than creating rule files by hand. Boost regenerates `.ai/rules/index.md` as part of recording a rule (...). A rule file that is added manually will not be discovered until the index is next regenerated."

Ou seja: escrever o `.md` à mão com o Boost ativo produz uma rule **invisível**. Existe fallback documentado na skill para quando `BOOST_RULES_ENABLED=false` ou o Boost não está instalado — incluindo atualizar o `index.md` na mão e registrar isso no commit.

### O índice é obrigatório: `.ai/rules/index.md`

Os agentes são instruídos a **consultar o índice antes de planejar ou editar qualquer arquivo**. Uma rule que não está no índice existe no disco e é invisível — não importa quão bem escrita esteja. A skill trata o índice como parte da entrega, não como detalhe.

Modelo oficial do Boost, que a skill usa literalmente ao criar o arquivo:

```markdown
# Project Rules Index

Before planning or editing, find the row whose globs match the file's path and read that rule file.

| Applies to | Rule file |
| --- | --- |
| app/Http/Controllers/** | .ai/rules/controllers.md |
| app/Models/** | .ai/rules/models.md |
```

> O cabeçalho e a frase de instrução são preservados **exatamente**. Aquela linha não é decoração: é a instrução que o agente lê para saber o que fazer com a tabela. Traduzir ou reescrever quebra o contrato com os agentes que esperam o formato do Boost.

**O que a skill faz, e quando:**

| Momento | Ação |
|---|---|
| Passo 2 — diagnóstico | `Read .ai/rules/index.md`. Classifica em 3 cenários: índice existe / `.ai/rules/` existe sem índice (rules **órfãs e invisíveis**) / nada existe. **Não escreve nada** — nenhum arquivo é criado antes do "sim" do usuário |
| Passo 7 — após aprovação | Cria o índice no modelo oficial se não existir; adiciona **uma linha por glob**; se havia rules órfãs, inclui todas; confere que cada path do índice corresponde a arquivo real |
| Passo 8 — commit | Commita `.ai/rules/` **inteiro** — rule + índice no mesmo commit |

Se a rule cobre 2 globs, são **2 linhas** apontando para o mesmo arquivo:

```markdown
| app/Models/** | .ai/rules/models.md |
| app/Services/Billing/** | .ai/rules/models.md |
```

**Regras de manutenção**: uma linha por glob (nunca globs concatenados numa célula); path sempre relativo à raiz começando em `.ai/rules/`; ordenar do glob mais estreito para o mais amplo, para o agente achar a rule mais específica primeiro; nenhuma linha órfã, duplicada ou de exemplo; e o índice nunca recebe conteúdo de rule — é só o mapa.

Detalhe importante para o fallback: quando o Boost voltar a estar ativo, `record-rule` **regenera** o índice e sobrescreve edições manuais. Por isso a skill exige registrar no commit que a gravação foi manual — para reconciliar depois.

### Modelo base da rule

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

Anatomia obrigatória: **título imperativo** + **restrição em 1-2 frases** + **consequência concreta** + **escape hatch** (se houver) + **enforcement/origem**.

A consequência é a parte mais importante. O exemplo oficial do Boost termina exatamente assim — *"will leak data across tenants"* — porque é a consequência que faz o agente obedecer em vez de "otimizar" por cima.

### Dependências

```bash
composer require laravel/boost --dev
php artisan boost:install
```

- Rules ficam em `.ai/rules/` e **devem ser commitadas** (diferente de `.mcp.json`, `CLAUDE.md` e `boost.json`, que o Boost regenera)
- Desativar tudo: `BOOST_RULES_ENABLED=false` no `.env` (remove a tool `record-rule`)

### Relação com o `infer-conventions` do Boost

São caminhos opostos e complementares:

| | `infer-conventions` (Boost) | `requirement-to-rule` (esta coletânea) |
|---|---|---|
| Direção | **código existente** → rules | **requisito/decisão** → rules |
| Quando rodar | uma vez, ao adotar o Boost num projeto legado | continuamente, a cada feature concluída |
| O que documenta | o que o código **faz** hoje | o que foi **decidido** que o código fará |

Ordem recomendada: rodar `infer-conventions` uma vez para bootstrapar a base, e usar `requirement-to-rule` como incremento a partir daí.

### Anti-padrões

| Anti-padrão | Por que é ruim |
|---|---|
| Glob `**` ou `app/**` | carrega em quase toda edição; imposto permanente de contexto |
| Rule sem consequência | o agente trata como sugestão e otimiza por cima |
| Rule que repete guideline do Boost | duplicação que apodrece na próxima versão do framework |
| Rule que Pint/Rector/PHPStan já garante | prosa onde a máquina resolve; viola a escada |
| Gravar sem aprovação do usuário | rules entram no git e afetam todo o time |
| Escrever o arquivo à mão com Boost ativo | `index.md` não é regenerado → rule invisível |
| Mais de 3 rules por feature | quanto mais rules, menos cada uma é respeitada |
| Rule narrando história ("decidimos em reunião...") | isso é ADR; rule é imperativa e atemporal |

---

## `search-docs` como teste empírico do gate 4

O gate "não-redundante" deixou de ser opinião e passou a ser verificável:

```text
Candidato: "Form Requests devem ter authorize() retornando a policy"
→ search-docs: "Laravel 13 form request authorize method"
→ A doc oficial já explica exatamente isso
→ REPROVA no gate 4: é guideline do ecossistema, não rule da aplicação

Candidato: "Enrollment::find() aplica scope global de tenant"
→ search-docs: "Laravel global scope Enrollment tenant"
→ Só a doc genérica de global scopes; nada sobre este model
→ APROVA no gate 4: o fato é da aplicação
```

Fora da cobertura da API (Pest 5, Playwright, pacotes de terceiros), o gate 4 é avaliado contra a doc oficial do pacote — e uma restrição sobre pacote de terceiro **pode** legitimamente virar rule, porque não existe guideline do Boost para ela.

> **Dois anti-padrões** que as skills passaram a proibir: escrever assinatura de método, nome de opção de config ou comportamento de componente no PRD **sem** confirmar em `search-docs` (causa nº 1 de plano que não sobrevive à implementação); e usar `search-docs` para descobrir comportamento do **seu próprio** código — ele documenta o ecossistema, o seu código é `Grep`, e as suas convenções são `.ai/rules/`.

---
