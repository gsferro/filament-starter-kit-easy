---
name: requirement-to-rule
description: >
  Transforma decisões e restrições de um requisito em Project Rules do Laravel Boost
  (.ai/rules/), escopadas por glob, para que valham para agentes futuros em qualquer
  sessão, não só na wiki da feature. Invoque no step 12 da feature-wiki, que delega a
  esta skill a coleta de candidatos, os gates e a aprovação; quando o usuário pedir
  explicitamente ("isso vira rule", "lembre disso para sempre"); ou quando uma ADR
  aceita ou uma armadilha descoberta valer para código futuro fora da feature. Filtra
  candidatos por 4 gates, gera e prova o teste arch() do Pest quando a restrição é
  mecânica, grava via a tool MCP record-rule, confere o índice .ai/rules/index.md e
  propõe poda. Palavras-chave: Project Rules, Laravel Boost, .ai/rules, record-rule,
  índice de rules, arch(), decisão durável. Não é para conhecimento de framework nem
  para varrer o código existente (infer-conventions do Boost).
license: MIT
compatibility: >
  Projeto Laravel com laravel/boost 2.4.12 ou superior, Project Rules ativas
  (BOOST_RULES_ENABLED diferente de false) e agente com MCP, para as tools record-rule
  e search-docs. Sem record-rule: fallback declarado no SKILL, com gravação manual e
  aviso ao usuário. Sem MCP, o gate 4 fica "não verificado" e o usuário decide sabendo
  disso. Pest com arch() para o enforcement gerado. feature-wiki 4.0.0 ou superior só
  quando os candidatos vêm da wiki (step 12).
metadata:
  version: "1.4.0"
  requires: "laravel/boost>=2.4.12; feature-wiki>=4.0.0"
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
| **Área** | Arquivo `.ai/rules/{área}.md` que o Boost escolhe a partir dos segmentos de diretório do glob |
| **Rule mecânica** | Restrição que um teste `arch()` do Pest verifica (herança, interface, uso de classe ou função, sufixo, `final`, trait, atributo) |
| **`{skills}`** | O primeiro dos três diretórios (`.ai/skills/`, `.claude/skills/`, `~/.claude/skills/`) que **contém a skill citada** |

## Referências

Abra a referência no passo indicado. Obrigação fica neste arquivo; template, tabela e comando ficam lá.

| Arquivo | Leia em | Fonte única de |
|---|---|---|
| [`references/coleta-e-apresentacao.md`](references/coleta-e-apresentacao.md) | passos 1, 3, 5 e 8 | fontes de candidatos, comandos da evidência do gate 3 e da recorrência, formato do prompt único, linha de retorno e linha do PR |
| [`references/enforcement-arch.md`](references/enforcement-arch.md) | passos 4 e 6 | restrição → `arch()`, template do teste, violação controlada, uso de `scripts/prova-arch.sh`, PHPStan/Rector/Pint |
| [`references/indice-e-record-rule.md`](references/indice-e-record-rule.md) | passos 2, 6 e 7, Poda, Fallback | o que o `record-rule` faz, glob → arquivo, o índice real, atualizar/remover/mudar glob, `writeIndex()` pelo `tinker` |
| [`scripts/prova-arch.sh`](scripts/prova-arch.sh) | passo 6 | a prova de que o `arch()` gerado pega a violação |

## As três camadas — não confundir

| Camada | Ensina | Carregamento | Quem mantém |
|--------|--------|--------------|-------------|
| **Guidelines** (`.ai/guidelines/`) | como escrever **Laravel** | upfront, sempre | Boost (`boost:update`) |
| **Skills** (`.ai/skills/`) | padrões de um **domínio/tarefa** | on-demand | Boost + você |
| **Rules** (`.ai/rules/`) | como escrever **a sua aplicação** | por glob, quando o arquivo casa | **você**, versionado no git |

> **Regra de ouro**: conhecimento de ecossistema **nunca** vira rule. O Boost já cobre e atualiza isso via `boost:update`; a sua rule apodreceria na próxima versão do framework. Rule é só o que é **específico da sua aplicação**.

## Quando Invocar

- **Step 12 da `feature-wiki`**, depois do veredito do step 11. Esta skill é a dona única do step 12: coleta de candidatos, os 4 gates, o único prompt de aprovação, `record-rule`, índice e commit. A `feature-wiki` só manda rodar. Por quê: antes a wiki coletava, julgava e perguntava, e esta skill recoletava, rejulgava e perguntava de novo (estudo §7.5)
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
- **Vocabulário do domínio**: termo decidido vai para o glossário do projeto (`wikis/glossario.md`, escrito pela `feature-wiki`), não para rule — rule é escopada por path, glossário é global (estudo §2.5).

## Quem executa (rota)

| Rota | Faz | Por quê |
|---|---|---|
| **Sessão principal** | tudo | tem MCP (`search-docs` do gate 4, `record-rule`) e fala com o usuário |
| Sub-agente que herda MCP (sem `tools` restrito) | passos 1–4 e a apresentação do passo 5 **como texto**; a sessão faz o prompt único e os passos 6–8 | sub-agente não pergunta ao usuário |
| `analista`, ou qualquer sub-agente sem MCP | **nunca** | o gate 4 ficava impossível justamente onde a esteira julgava os gates (estudo §7.5) |

**Sem MCP** (agente sem MCP, Boost ausente): o gate 4 não é verificado. O candidato vai para a apresentação marcado `não-redundante ⚠️ gate 4 não verificado (sem MCP)`, e gravá-lo é decisão explícita do usuário — nunca gravado sem ele saber. A gravação cai no [Fallback](#fallback--boost-ausente-ou-rules-desativadas).

---

## Vale virar rule

Definição única da coletânea. O step 12 da `feature-wiki` e o `feature-quality-gate` apontam para esta seção e não usam critério próprio. Por quê: havia três definições — tempo no gate 1, o gate 3 reprovando o que a wiki chamava de "candidato natural", e "recorrente entre features" no quality gate (estudo §7.5).

Um candidato vale virar rule quando as três coisas valem juntas:

1. **Durável** — passa no [gate 1](#gate-1--durável).
2. **Não-inferível** — passa no [gate 3](#gate-3--não-inferível) com a evidência mínima: arquivos irmãos listados e a frase *"um agente que lesse só esses arquivos erraria porque …"*.
3. **Recorrência declarada** — o candidato diz onde mais o padrão aparece (paths) ou vai aparecer (área, feature prevista). Sem recorrência declarada, a decisão fica na ADR.

"Vale virar rule" é a triagem. Gravar exige ainda os gates 2 e 4 e a aprovação do usuário.

## Os 4 Gates

Candidato só vira rule se passar em **todos**. Registrar o veredito de cada gate na apresentação ao usuário.

### Gate 1 — Durável

> Vale além desta feature e desta sprint?

- ✅ "Valores monetários são `integer` em centavos, nunca `float`"
- ❌ "Nesta feature o status inicial é `pending`" — regra de negócio de um fluxo, vai na ADR

### Gate 2 — Escopável por path

> Dá para expressar em glob?

O Boost instrui o agente a consultar o índice antes de planejar ou editar arquivos que casam o glob. Se não se consegue nomear os paths onde a regra se aplica, **não é rule** — é ADR ou guideline interna.

- ✅ `app/Models/**`, `app/Http/Controllers/Api/**`, `database/migrations/**`
- ❌ "vale para o projeto todo" → glob `**` é anti-padrão: o custo de um glob largo é o agente ter de **ler** a rule a cada tarefa, e ela vira ruído permanente

**Preferir o glob mais estreito que cobre o caso.** Uma rule em `app/Models/Enrollment.php` é melhor que a mesma rule em `app/**`.

### Gate 3 — Não-inferível

> Um agente competente, lendo o código ao redor, erraria?

Este é o gate que mais elimina candidatos. Se o padrão é evidente nos arquivos vizinhos, o agente acerta sozinho e a rule só consome contexto.

**Evidência mínima** — sem ela o gate não passa: ao menos **3 arquivos irmãos** (os que um agente abriria ao editar um arquivo do glob), achados por `ls`/`grep`, lidos e listados com path, e a frase *"um agente que lesse só esses arquivos erraria porque …"*. O 3 é hipótese a calibrar; com menos irmãos no projeto, listar todos e declarar o número. Por quê: o gate era o mais decisivo e o menos decidível — não dizia quais arquivos, quantos, nem que evidência registrar (estudo §7.5). Comandos em [`references/coleta-e-apresentacao.md`](references/coleta-e-apresentacao.md#evidência-do-gate-3--três-irmãos-e-a-frase).

- ✅ "`Enrollment::find()` aplica scope global de tenant — usar `withoutGlobalScopes()` para busca administrativa": invisível no arquivo, consequência silenciosa
- ❌ "Controllers ficam em `app/Http/Controllers`": o agente vê isso na primeira listagem

### Gate 4 — Não-redundante

> Não é default do framework, não é coberto por ferramenta, não duplica rule existente?

Checar, nesta ordem:

1. `Read .ai/rules/index.md` → alguma rule já cobre este glob? Se sim, **atualizar** aquela rule em vez de criar nova
2. Está em `pint.json` / `rector.php` / `phpstan.neon`? → é enforcement automático, não rule
3. **É comportamento documentado do ecossistema?** → consultar a tool MCP **`search-docs`** do Boost com a afirmação do candidato. Se a Documentation API já responde, é **guideline**, não rule — e uma rule sua duplicando doc oficial apodrece na próxima versão do pacote. Sem MCP, este item não roda: ver [Quem executa](#quem-executa-rota)
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

Antes de varrer, leia as fontes em [`references/coleta-e-apresentacao.md`](references/coleta-e-apresentacao.md#fontes-de-candidatos-dentro-da-wiki).

**Se vindo da feature-wiki (step 12)**: as ADRs do `02-decisoes-arquiteturais.md`; as `## Notas de Implementação` e a tabela `## Conformidade com Rules` do `03-progresso.md` (rule `violada`/`n.a.` é candidata a atualizar ou a [Poda](#poda)); os padrões que o `01-plano-acao.md` repete; o checklist de taxonomia do `04` da `feature-test-design`.

**Se vindo de um requisito solto** (card, ticket, conversa): extrair as afirmações normativas — frases com "sempre", "nunca", "todo", "deve", "não pode".

**Teto: no máximo 3 candidatos apresentados por feature.** Cada rule é imposto permanente de contexto em todo arquivo que casa com o glob — inflação de rules degrada o agente em vez de ajudar.

### 2. Verificar o estado atual das rules

```bash
# existe o diretório de rules?
ls .ai/rules/
```

- `Read .ai/rules/index.md` — uma linha por arquivo de rule, com os globs dele
- `Read` as rules dos globs que o candidato tocaria, e prever o arquivo que o Boost vai escolher para o glob novo ([tabela glob → arquivo](references/indice-e-record-rule.md#glob--arquivo-primeira-gravação-da-área))

**Diagnóstico dos três cenários possíveis**:

| Cenário | Significado | Ação |
|---|---|---|
| `.ai/rules/index.md` existe | projeto já usa rules | usar as linhas para o gate 4 (dedupe) e decidir entre atualizar rule existente ou criar nova |
| `.ai/rules/` existe, mas **sem `index.md`** | rules gravadas sem índice — estão **invisíveis** para os agentes | avisar o usuário; o primeiro `record-rule` regenera o índice com todo arquivo que tem `paths:`; no fallback, o índice é escrito no passo 7, já incluindo as rules órfãs encontradas |
| `.ai/rules/` não existe | primeira rule do projeto, ou Boost ausente / `BOOST_RULES_ENABLED=false` | registrar que diretório e índice serão criados no passo 6; se o Boost estiver ausente, ver "Fallback" |

> **Não criar nada aqui.** Este passo é só diagnóstico — nenhum arquivo é escrito em `.ai/rules/` nem em `tests/` antes da aprovação explícita do usuário (passo 5).

### 3. Aplicar os 4 gates

Descartar candidato que falhe em qualquer gate, **dizendo qual gate falhou**. Não silenciar descarte — o usuário precisa saber que o candidato foi considerado e por que caiu. Registrar a evidência do gate 3 e a recorrência de cada candidato que segue.

### 4. Preferir enforcement automático (escada de rules)

Antes de escrever prosa, subir esta escada:

1. **Teste de arquitetura** (`arch()` do Pest) resolve? → a skill **gera** o teste ([Enforcement que roda](#enforcement-que-roda--arch-gerado-e-provado)); rule fica em 1 linha apontando para ele
2. **PHPStan / Larastan** pega? → sugerir a regra na apresentação; sem rule em prosa
3. **Rector** pode reescrever automaticamente? → sugerir a regra do `rector.php`
4. **Pint** normaliza? → é formatação: sugerir o preset; não vira rule
5. **Só então**: rule em prosa

> Exemplo: "Controllers devem estender `BaseController`" é um teste de arquitetura `arch()` do Pest:
> `arch()->expect('App\Http\Controllers')->toExtend('App\Http\Controllers\BaseController');`
> A rule então diz: *"Enforçado em `tests/Arch/ControllersTest.php` — não contornar."* — só depois da prova do passo 6.

### 5. Apresentar ao usuário e ESPERAR decisão

**Nunca gravar sem aprovação explícita.** **Um** prompt por invocação, com tudo de uma vez: cada candidato (origem, título, glob, arquivo que o Boost vai escolher, regra, por quê, evidência, evidência do gate 3, recorrência, veredito dos 4 gates, enforcement), as atualizações de rule existente, a poda proposta e os descartados com o gate que falhou. Candidato mecânico traz o teste `tests/Arch/{Área}Test.php` que será gravado e, se preciso, a mudança de suíte no `phpunit.xml`. Formato em [`references/coleta-e-apresentacao.md`](references/coleta-e-apresentacao.md#formato-do-prompt-único).

A resposta vale como foi dada. Candidato mecânico cuja prova falha no passo 6 não é gravado em outra forma: volta ao usuário com a saída, sem segunda pergunta nesta invocação.

**Se recusado**: não insistir. A decisão continua registrada na ADR, que já é um lugar válido.

### 6. Gravar via `record-rule` (obrigatório)

Só os aprovados, nesta ordem:

1. **Candidato mecânico**: aplicar a mudança de suíte aprovada, gravar `tests/Arch/{Área}Test.php` e rodar a prova ([Enforcement que roda](#enforcement-que-roda--arch-gerado-e-provado)). Prova falhou → desfazer o que a skill escreveu, não gravar o candidato, relatar. Só a violação controlada, arquivo temporário fora da aprovação, pode ser corrigida para rodar de novo; o teste e a mudança de suíte aprovados, não.
2. **A rule**, pelo `record-rule`.

Gravar **sempre** pela tool MCP `record-rule` do Boost, passando `glob`, `title` e `note`. A doc do Boost (https://laravel.com/framework/docs/13.x/boost#project-rules) é explícita:

> "You should always record rules using the `record-rule` tool rather than creating rule files by hand. Boost regenerates `.ai/rules/index.md` as part of recording a rule, and agents rely on that index to discover which rules apply to the file they are working on. A rule file that is added manually will not be discovered until the index is next regenerated."

Ou, em linguagem natural para o agente com Boost ativo:

```text
Remember that all money values are stored as integer cents, never as floats.
```

Um glob por chamada. O Boost escolhe o arquivo pela área do glob e **sempre** acrescenta uma seção `## {title}`: o mesmo título em dois globs da mesma área duplica a seção, e `record-rule` não atualiza rule existente — acrescenta outra. Procedimentos para dois globs e para atualizar: [`references/indice-e-record-rule.md`](references/indice-e-record-rule.md#chamadas-que-duplicam-ou-espalham).

### 7. Garantir o índice (`.ai/rules/index.md`)

**Obrigatório e não-negociável**: sem a linha no índice, a rule existe no disco e é **invisível** para os agentes. Ver a seção [Índice de Rules](#índice-de-rules-airulesindexmd) — conferir sempre; escrever à mão só no Fallback e na Poda.

### 8. Verificar e commitar

- `Read .ai/rules/index.md` e confirmar que a linha do arquivo que o `record-rule` devolveu (`Recorded rule in .ai/rules/{área}.md: …`) traz o glob aprovado na célula `Applies to` — `| app/Models/**, app/Models/Invoice.php | .ai/rules/models.md |`
- `Read` o arquivo de rule gravado e checar se o `note` ficou fiel ao aprovado e se nenhuma seção `## {título}` ficou duplicada
- **Commitar `.ai/rules/` inteiro** (rule + índice), mais `tests/Arch/{Área}Test.php` e `phpunit.xml` quando houve enforcement — rules são artefato de equipe, versionado (diferente de `.mcp.json`, dos arquivos de guidelines — `CLAUDE.md`, `AGENTS.md` etc. — e do `boost.json`, que o Boost regenera)
- **Na branch do PR já aberto** no step 11 da `feature-wiki`, com **uma linha na descrição do PR** ([formato e comando](references/coleta-e-apresentacao.md#a-linha-de-retorno-e-o-pr)). Por quê: a rule sai das decisões desta feature; no mesmo PR, quem aprova a feature vê também o que ela deixa para os próximos agentes. Fora da `feature-wiki`, sem PR: a branch atual. Nada gravado nem podado: sem commit e sem linha no PR
- Commit sugerido: `:memo: rules: {título da rule}` com corpo citando a origem (ADR / wiki / card) e, com enforcement, o resultado da prova (`exit=0`, a falha citando a violação, status idêntico)
- **Devolver à sessão**, como última linha da resposta, a linha de `## Candidatos a Rule` do `03` no formato fixo `apresentados N · gravados N · recusados N · descartados no gate N · poda N` — sempre, inclusive com zeros e fora da `feature-wiki`. O que cada número conta: [referência](references/coleta-e-apresentacao.md#a-linha-de-retorno-e-o-pr)

---

## Enforcement que roda — `arch()` gerado e provado

Por quê: a escada só sugeria, ninguém escrevia o `arch()` nem rodava a suíte, e a rule dizia "enforçado em `tests/Arch/X.php`" apontando para arquivo que podia não existir (estudo §7.5; §5.1 — regra determinística que roda, não que é descrita). Template, tabela e comandos: [`references/enforcement-arch.md`](references/enforcement-arch.md); sintaxe do `arch()` conferida em https://pestphp.com/docs/arch-testing (doc 5.x).

- **Rule mecânica → a skill gera o teste** a partir do template da referência e o grava em `tests/Arch/{Área}Test.php` (`{Área}` = arquivo de rule que o Boost escolhe, em StudlyCase; arquivo existente ganha um bloco, nunca é sobrescrito). O teste entra no prompt único: gravá-lo sem aprovação é proibido, como gravar rule.
- **Prova obrigatória** com `bash {skills}/requirement-to-rule/scripts/prova-arch.sh`, que roda `vendor/bin/pest tests/Arch/{Área}Test.php`. Os rótulos são os do script:
  - (0) a suíte padrão do `phpunit.xml` inclui `tests/Arch` — senão o CI nunca roda o teste.
  - (a) o teste **passa no código atual**. Se falha mostrando violação existente, é **achado, não rule nova**: desfazer o teste, não gravar o candidato, relatar com `arquivo:linha`.
  - (b) uma **violação controlada** derruba o teste: arquivo temporário num diretório que o teste varre, criado e removido pelo script.
  - (c) a árvore fica intacta: o arquivo temporário saiu e o `git status --porcelain` é idêntico antes e depois de (b).
- A rule só diz **"Enforçado em `tests/Arch/{Área}Test.php` — não contornar."** depois que o arquivo existe e a saída da prova (`exit=0`, `a-atual.txt`, `b-violacao.txt`, `status-antes.txt`, `status-depois.txt`) foi colada na conversa. Sem isso a rule é prosa e não cita teste.
- **PHPStan/Larastan, Rector, Pint**: sugerir a configuração mínima quando a restrição couber nelas. A skill não gera o que não consegue provar; a rule só cita a ferramenta com uma execução colada que acusa uma violação controlada.

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

É o que o `RuleRepository::writeIndex()` escreve (laravel/boost v2.10.0): **uma linha por arquivo de rule**, com os globs do `paths:` desse arquivo concatenados por `, ` na mesma célula, linhas em ordem de path do arquivo. Exemplo real e detalhes em [`references/indice-e-record-rule.md`](references/indice-e-record-rule.md#o-índice-que-o-boost-escreve).

### Procedimento

**Passo 2 do fluxo — diagnóstico (antes da aprovação):**

- `Read .ai/rules/index.md`
- **Se o arquivo não existir**: registrar que será criado, **sem criar ainda**. A criação acontece depois da aprovação do usuário — nada é escrito em `.ai/rules/` antes do "sim".
- Se existir: usar as linhas para o gate 4 (dedupe) e para decidir entre **atualizar rule existente** ou criar nova.

**Passo 7 do fluxo — após a aprovação:**

| Situação | Ação |
|---|---|
| `record-rule` disponível **e** índice foi regenerado com a linha nova | nada a fazer além de conferir |
| `record-rule` disponível | **não editar o índice à mão**: cada gravação regenera o `index.md` inteiro a partir dos arquivos; conferir o resultado depois da última chamada |
| arquivo de rule removido ou glob mudado ([Poda](#poda)) | editar à mão a linha afetada — a única edição manual do índice com `record-rule` disponível, com o motivo no commit; alternativa, com ressalva: `writeIndex()` pelo `tinker` |
| `record-rule` indisponível (fallback) | criar/atualizar o índice à mão, obrigatoriamente, no formato do Boost |

**Ao criar o arquivo pela primeira vez** (fallback): copiar o modelo oficial, substituindo as linhas de exemplo pelas rules reais do projeto. Não deixar as linhas de exemplo (`controllers.md`, `models.md`) se elas não existirem — índice apontando para arquivo inexistente faz o agente perder tempo tentando ler.

### Regras de manutenção do índice

1. **Uma linha por arquivo de rule**, com os globs do arquivo na mesma célula, separados por `, ` — é o formato que o Boost regenera; linha por glob seria desfeita no próximo `record-rule`
2. **Path do arquivo sempre relativo à raiz do projeto** e começando com `.ai/rules/` — igual ao modelo oficial
3. **Linhas em ordem de path do arquivo**, como o Boost ordena; especificidade se resolve no glob, não na ordem
4. **Sem linha órfã**: arquivo removido ou glob mudado deixa o índice defasado até o próximo `record-rule` — ver [Poda](#poda)
5. **Sem duplicata**: mesmo glob apontando para dois arquivos é ambiguidade — consolidar as rules num arquivo só; mesma seção `## {título}` duas vezes no arquivo é duplicata — apagar a cópia
6. **O índice não recebe conteúdo de rule** — é só o mapa. Restrição em prosa vai no arquivo da área
7. **Arquivo sem `paths:` no frontmatter não entra no índice** — o Boost o ignora, e a rule fica invisível

### Verificação final do índice

Depois de gravar, conferir estas quatro coisas:

- [ ] `.ai/rules/index.md` existe e tem o cabeçalho + a frase de instrução oficiais
- [ ] O arquivo que o `record-rule` devolveu tem uma linha, e **cada** glob aprovado está na célula `Applies to` dessa linha
- [ ] Todo path citado no índice corresponde a um arquivo que existe de fato
- [ ] Nenhuma linha órfã, nenhuma seção `## {título}` duplicada

---

## Poda

Por quê: o ciclo era só de crescimento — sem gatilho de revisão nem procedimento de remoção compatível com o Boost; com 20 features seriam 60 rules, e "glob estreito" viraria letra morta (estudo §7.5). O Boost não tem tool de remoção: a única tool é `record-rule`, que grava (fonte: `laravel/boost` v2.10.0, `RuleRepository`).

- **Gatilho** (hipótese a calibrar): a rule aparece como `n.a.` ou `violada` em `## Conformidade com Rules` do `03` em **3 features seguidas**; ou o glob dela não casa mais nenhum arquivo. Conferido no passo 1 — comandos em [`references/indice-e-record-rule.md`](references/indice-e-record-rule.md#gatilho-da-poda--como-conferir).
- **Ação**: propor ao usuário, no prompt único, **atualizar** ou **remover**. Nunca podar sem aprovação.
- **Procedimento compatível com o Boost**:
  - Remover uma seção `## {título}` de `.ai/rules/{área}.md`: apagar a seção. O índice não muda — ele só mapeia `paths:` → arquivo.
  - Remover o arquivo inteiro ou mudar o glob (`paths:`): o `.ai/rules/index.md` fica defasado até o próximo `record-rule`. Com a configuração padrão (`BOOST_RULES_SCOPED_GUIDELINES=false`), o `boost:update` **não** regenera o índice; então a linha do índice é editada à mão, no formato do `writeIndex()` — a única edição manual permitida — e o motivo vai no commit.
  - Alternativa à edição manual, que continua o procedimento padrão: regenerar o índice com `php artisan tinker --execute='app(\Laravel\Boost\Rules\RuleRepository::class)->writeIndex();'`. **API interna do Boost, não documentada — pode mudar.** O método é público na v2.10.0; quando a chamada falha e como conferir o resultado: [referência](references/indice-e-record-rule.md#alternativa-writeindex-pelo-tinker).

---

## Modelo Base do Conteúdo da Rule

O `record-rule` recebe `glob`, `title` e `note`. O modelo abaixo é o arquivo `.ai/rules/{área}.md` como o Boost o deixa: o frontmatter `paths:` e o `# {Área}` o Boost gera; cada gravação acrescenta `## {title}` com o `note` embaixo. O **`note` é o corpo da seção**:

```markdown
---
paths:
  - 'app/Models/**'
  - app/Models/Invoice.php
---

# Models

## Valores monetários são inteiros em centavos
Todo campo monetário é persistido como `integer` representando centavos — nunca
`float` ou `decimal`. Converter na borda (Form Request na entrada, cast na saída).
Usar `float` introduz erro de arredondamento que se acumula em relatórios de
fechamento e não é detectado pelos testes unitários de cada operação isolada.

Sem enforcement automático: `arch()` não enxerga tipo de coluna. Origem: ADR-02 de
`wikis/specs/ferro/579/cobranca-lote/02-decisoes-arquiteturais.md`.
```

### Anatomia obrigatória do `note`

| Parte | Regra | Por quê |
|---|---|---|
| **Título (`##`)** | imperativo, curto, uma restrição só | O agente varre títulos; título vago não é aplicado |
| **Restrição** | 1-2 frases, afirmativa, sem hedge ("deve preferencialmente" → não) | Ambiguidade vira desvio |
| **Consequência** | o que quebra se ignorar, concretamente | **A parte mais importante.** É a consequência que faz o agente obedecer em vez de "otimizar" |
| **Escape hatch** | quando a regra **não** se aplica, se houver | Rule sem exceção declarada é contornada em silêncio |
| **Enforcement / Origem** | teste provado, ferramenta, ou ADR de origem | Rastreabilidade e prova de que não é opinião solta |

> O `title` vai no parâmetro próprio do `record-rule`; o `note` leva só o corpo (regra, porquê, enforcement) — sem frontmatter e sem `#`, que o Boost gera.

### Regras de escrita

1. **Uma rule = uma restrição.** Duas restrições = dois blocos `##`.
2. **Escrever o porquê, não só o quê.** O exemplo oficial do Boost termina em consequência: *"will leak data across tenants"*. Copiar essa disciplina.
3. **Presente do indicativo, afirmativo.** "Todo controller estende `BaseController`" vence "não deveria estender o controller do framework".
4. **Nomes totalmente qualificados** para classes (`App\Http\Controllers\BaseController`).
5. **Sem número de versão volátil** no corpo — apodrece.
6. **Prosa normal, não terse.** Rules são boundary do Caveman: ambiguidade aqui se propaga para todo agente futuro.
7. **Máximo ~10 linhas por rule.** Rule longa é guideline disfarçada e não será lida por inteiro.

---

## Áreas — o Boost escolhe o arquivo

O Boost arquiva a rule "under the matching area". O `record-rule` não recebe arquivo: o Boost escolhe pela área do glob, feita dos segmentos de diretório (`app/Http/Controllers/Api/**` vai para `api.md`, não para `controllers.md`). A tabela glob → arquivo, tirada do código do Boost, está em [`references/indice-e-record-rule.md`](references/indice-e-record-rule.md#glob--arquivo-primeira-gravação-da-área); no Fallback, usar o nome que o Boost usaria. Manter poucas áreas, com globs estreitos.

---

## Fallback — Boost ausente ou rules desativadas

Se `record-rule` não estiver disponível (`BOOST_RULES_ENABLED=false`, Boost não instalado, agente sem MCP):

1. **Avisar o usuário** de que a gravação manual não é o caminho recomendado pela doc do Boost
2. Criar/editar `.ai/rules/{area}.md` com o frontmatter `paths:` e o corpo no modelo acima, com o nome de arquivo que o Boost escolheria
3. **Criar `.ai/rules/index.md`** se não existir, usando o [modelo oficial](#modelo-oficial-doc-do-boost) — cabeçalho e frase de instrução idênticos, linhas de exemplo substituídas pelas rules reais
4. **Uma linha por arquivo de rule** na tabela do índice, com os globs do `paths:` separados por `, ` — sem isso a rule não é descoberta ([formato](references/indice-e-record-rule.md#índice-escrito-à-mão-fallback))
5. Se havia rules órfãs (sem linha no índice), incluí-las agora — o índice deve refletir tudo que existe em `.ai/rules/`
6. Registrar no commit que a rule e o índice foram gravados manualmente, para reconciliar quando o Boost voltar (`record-rule` regenera o índice e sobrescreve edições manuais)

Para agentes sem suporte a `.ai/rules` (Windsurf, Cline e outros que não leem `.ai/rules`), espelhar o conteúdo no formato do agente (`.windsurf/rules/`, etc.), mantendo `.ai/rules/` como fonte da verdade.

---

## Anti-padrões

| Anti-padrão | Por que é ruim |
|---|---|
| Glob `**` ou `app/**` | Carrega em quase toda edição; vira imposto permanente de contexto |
| Rule sem consequência | O agente trata como sugestão e "otimiza" por cima |
| Rule que repete guideline do Boost | Duplicação que apodrece na próxima versão do framework |
| Reprovar/aprovar o gate 4 "de cabeça" | `search-docs` existe para tornar isso verificável — usar; sem MCP, declarar "não verificado" |
| Gate 3 sem os arquivos irmãos listados | "Um agente erraria" vira opinião; é o gate que mais decide |
| Rule que o Pint/Rector/PHPStan já garante | Prosa onde a máquina já resolve; viola a escada |
| "Enforçado em `tests/Arch/X.php`" sem a prova colada | Aponta para teste que pode não existir, não rodar no CI ou não pegar a violação |
| Gravar sem aprovação do usuário | Rules são artefato de equipe; entram no git e afetam todos |
| Segundo prompt de aprovação na mesma invocação | Era a dupla aprovação que o step 12 com um dono eliminou |
| Escrever o arquivo à mão com Boost ativo (criar arquivo de rule, acrescentar glob ao `paths:`) — exceto na [Poda](#poda) (tirar ou trocar glob, apagar arquivo), com a linha do índice ajustada e o motivo no commit | O `index.md` não é regenerado e a rule fica invisível. Editar o texto de uma seção existente não mexe no índice |
| `record-rule` com o mesmo título para "atualizar" | O Boost acrescenta outra seção; a antiga continua valendo |
| Gravar a rule e não conferir o `index.md` | Rule no disco sem linha no índice = rule que nenhum agente lê |
| Deixar as linhas de exemplo do modelo no índice | Índice aponta para `controllers.md`/`models.md` inexistentes e o agente perde tempo |
| Traduzir ou reescrever a frase de instrução do índice | É a instrução que o agente lê para usar a tabela; alterá-la quebra o contrato |
| Mais de 3 **candidatos apresentados** por feature | Inflação: quanto mais rules, menos cada uma é respeitada |
| Rule contando história ("decidimos em reunião que...") | Isso é ADR. Rule é imperativa e atemporal |

---

## Checklist Final

- [ ] Rota com MCP (sessão principal ou sub-agente que herda MCP); sem MCP, candidatos marcados "gate 4 não verificado"
- [ ] Candidatos coletados de fonte concreta (ADR, nota de implementação, requisito) com evidência `arquivo:linha`
- [ ] `.ai/rules/index.md` lido; rules dos globs afetados lidas
- [ ] Cada candidato avaliado nos 4 gates, com veredito registrado
- [ ] Gate 3 com os arquivos irmãos listados (3, ou todos os que existem) e a frase "um agente que lesse só esses arquivos erraria porque …"
- [ ] Recorrência declarada — critério de [Vale virar rule](#vale-virar-rule)
- [ ] Gate 4 verificado com `search-docs` — candidato que a Documentation API já responde foi reprovado como guideline
- [ ] Descartados comunicados ao usuário com o gate que falhou
- [ ] Escada de enforcement subida — automação preferida à prosa quando possível
- [ ] Atualização de rule existente preferida à criação de nova — pela edição da seção, não por novo `record-rule`
- [ ] Glob é o mais estreito que cobre o caso (nunca `**`)
- [ ] `note` contém restrição + consequência + origem
- [ ] Um prompt de aprovação só, com candidatos, atualizações, poda e descartados
- [ ] Aprovação explícita do usuário obtida — nada escrito em `.ai/rules/` nem em `tests/` antes disso
- [ ] Rule mecânica: `tests/Arch/{Área}Test.php` gravado, prova com `exit=0` e saídas coladas antes da rule dizer "enforçado"
- [ ] Gravado via `record-rule` (ou fallback documentado no commit)
- [ ] `.ai/rules/index.md` **existe** — criado pelo Boost, ou no modelo oficial no fallback
- [ ] Índice tem **uma linha por arquivo de rule**, e cada glob aprovado está na linha do arquivo que o `record-rule` devolveu
- [ ] Todo path citado no índice existe de fato; nenhuma linha órfã, duplicada ou de exemplo; nenhuma seção `## {título}` duplicada
- [ ] Cabeçalho e frase de instrução do índice preservados na forma oficial
- [ ] Poda: gatilho conferido; remoção de arquivo ou mudança de glob com a linha do índice ajustada (à mão, ou pelo `writeIndex()` com a ressalva) e o motivo no commit
- [ ] `.ai/rules/` commitado **inteiro** (rule + índice), com `tests/Arch/` e `phpunit.xml` quando houve enforcement, com gitmoji `:memo: rules:`, na branch do PR já aberto e com a linha na descrição do PR
- [ ] Teto de no máximo 3 **candidatos apresentados** por feature respeitado
- [ ] Linha `apresentados N · gravados N · recusados N · descartados no gate N · poda N` devolvida à sessão

## Skills Companheiras

| Skill | Relação |
|---|---|
| `feature-wiki` | Produz as fontes (ADR, notas, PRD, `## Conformidade com Rules`). O step 12 dela manda rodar esta skill e delega tudo: coleta, gates, aprovação, gravação, índice e commit; grava em `## Candidatos a Rule` do `03` a linha que esta skill devolve no passo 8 |
| `feature-quality-gate` | Usa [Vale virar rule](#vale-virar-rule) quando aponta candidato a rule; não tem critério próprio |
| `feature-test-design` | Fonte de candidato com evidência forte: linha nova do **checklist de taxonomia de defeito** (tabela em `{skills}/feature-test-design/references/taxonomia-de-defeito.md`), nascida de defeito que escapou para produção. Se generaliza além da feature, é candidato, julgado por [Vale virar rule](#vale-virar-rule) e pelos [4 gates](#os-4-gates) |
| `infer-conventions` (Boost) | Caminho inverso: varre o **código existente** para bootstrapar rules. Rodar uma vez, no início do projeto; esta skill é o incremento contínuo |
| `ponytail` | A escada de enforcement é a escada de simplicidade aplicada a rules: automação antes de prosa, nada antes de automação desnecessária |
| `pest-testing` | Referência de Pest do Boost para ajustar o `arch()` gerado quando a restrição foge dos exemplos da referência |

> **Caveman**: arquivos de rule são **boundary** — prosa normal. A rule é lida por todo agente futuro; compressão aqui multiplica ambiguidade.
