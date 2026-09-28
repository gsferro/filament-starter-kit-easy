> Referência da requirement-to-rule 1.4.0. Lida em: passo 1 (antes de varrer a wiki ou o requisito
> atrás de candidatos), passo 3 (ao levantar a evidência do gate 3 e a recorrência), passo 5 (antes
> de montar o prompt único) e passo 8 (ao commitar e devolver o resultado). Fonte única de: as fontes
> de candidatos, os comandos da evidência do gate 3 e da recorrência, o formato da apresentação ao
> usuário, a linha de retorno e a linha da descrição do PR.

# Coleta de candidatos e o prompt único

As obrigações — teto de 3 candidatos, os 4 gates, a definição de *Vale virar rule*, um prompt só e
nada gravado antes do "sim" — ficam no corpo do `SKILL.md`. Esta página traz as fontes, os
comandos e o formato que a `feature-wiki` 3.x mantinha no step de candidatos a rule e em
`feature-wiki/references/candidatos-a-rule.md`; a partir da 4.0.0, o step 12 dela aponta para esta skill.

## Fontes de candidatos dentro da wiki

| Fonte | O que procurar | Exemplo |
|---|---|---|
| `02-decisoes-arquiteturais.md` | ADR cuja **consequência generaliza** além desta feature | "Todo valor monetário é `integer` em centavos" |
| `03-progresso.md` → Notas de Implementação | **Armadilha descoberta no código** que não se infere lendo o arquivo | "`Enrollment::find()` aplica scope global de tenant" |
| `03-progresso.md` → `## Conformidade com Rules` | rule `violada` ou `n.a.` — candidata a atualizar, ou a poda se o gatilho bate (`SKILL.md` §Poda) | `` `jobs.md` — {título} `` · `n.a.` pela terceira feature seguida |
| `01-plano-acao.md` | Padrão obrigatório que a wiki repetiu e que vale para o projeto todo | Padrão de log `[Classe@Método]` + channel por feature |
| `04-casos-de-teste.md` → checklist de taxonomia | gatilho que a feature acrescentou por causa de um defeito que escapou; se vale fora da feature, é candidato — a linha em si vai para a tabela da taxonomia (`{skills}/feature-test-design/references/taxonomia-de-defeito.md`) | "unicidade + `SoftDeletes`: criar → excluir → recriar" |

## Fontes num requisito solto

Card, ticket ou conversa: extrair as afirmações normativas — frases com "sempre", "nunca", "todo",
"deve", "não pode". Cada uma é um candidato e passa pelos mesmos gates.

## Evidência do gate 3 — três irmãos e a frase

Irmãos são os arquivos que um agente abriria ao editar um arquivo do glob. Achar por diretório ou
por símbolo:

```bash
ls app/Models/                                              # irmãos pelo diretório do glob
grep -rlF 'App\Models\Enrollment' app/ | head -n 5          # irmãos pelo símbolo (FQCN sempre com -F)
```

Ler os arquivos e registrar, no bloco do candidato:

```text
Gate 3:      lidos app/Models/Enrollment.php, app/Models/Course.php, app/Services/Enrollment/Matricula.php —
             um agente que lesse só esses arquivos erraria porque o scope global de tenant é aplicado
             por um trait do pacote, que não aparece em nenhum dos três
```

Menos de 3 irmãos no projeto: listar todos e declarar o número (`só 2 irmãos existem`).

## Recorrência — onde mais o padrão aparece

```bash
grep -rlF 'withoutGlobalScopes' app/ | head -n 10
```

No bloco do candidato: os paths que já seguem o padrão, ou a área e a feature onde ele vai aparecer
(`Recorrência: app/Services/Billing/** — {feature prevista}`). Sem nenhum dos dois, o
candidato não passa em *Vale virar rule* e a decisão fica na ADR.

## Formato do prompt único

Um prompt por invocação: candidatos, atualizações, poda e descartados juntos. O usuário responde uma
vez.

```text
Candidatos a rule — {feature ou origem} (decisão sua; máximo 3)

1. [ADR-02] Valores monetários são inteiros em centavos
   Glob:        app/Models/**
   Arquivo:     .ai/rules/models.md (escolhido pelo Boost; já existe — entra como seção nova)
   Regra:       {uma frase — a restrição}
   Por quê:     {consequência de ignorar — é isto que faz o agente obedecer}
   Evidência:   02-decisoes-arquiteturais.md ADR-02 + app/Models/Invoice.php:34
   Gate 3:      lidos {3 paths} — um agente que lesse só esses arquivos erraria porque {…}
   Recorrência: {paths que já seguem, ou área/feature onde vai aparecer}
   Gates:       durável ✅ | escopável ✅ | não-inferível ✅ | não-redundante ✅
   Enforcement: prosa — arch() não enxerga tipo de coluna

2. [Nota] Controllers estendem BaseController
   Glob:        app/Http/Controllers/**
   Arquivo:     .ai/rules/controllers.md (novo)
   …
   Gates:       durável ✅ | escopável ✅ | não-inferível ✅ | não-redundante ✅
   Enforcement: arch() — grava tests/Arch/ControllersTest.php e prova antes do record-rule:
                  arch('Controllers estendem BaseController')
                      ->expect('App\Http\Controllers')
                      ->toExtend('App\Http\Controllers\BaseController')
                      ->ignoring('App\Http\Controllers\BaseController');
                phpunit.xml: acrescenta <testsuite name="Arch"> (nenhuma suíte inclui tests/Arch)

Atualizar em vez de criar:
  - .ai/rules/models.md › "{título}": {o que muda}

Poda proposta:
  - Poda 1: .ai/rules/jobs.md › "{título}" — n.a. em {feature}, {feature}, {feature} → remover | atualizar

Descartados:
  - {candidato}: falhou no gate {N} — {motivo}

Candidato com arch(): a aprovação cobre o teste e a mudança no phpunit.xml. Se a prova falhar, nada
dele é gravado e a saída volta para você.

Gravar? (números, "todos", "nenhum"; poda: "Poda 1 remover" | "Poda 1 atualizar" | "Poda 1 manter")
```

O exemplo é de uma sessão com MCP. Sem MCP, o `search-docs` não roda para nenhum candidato: **todos**
levam `não-redundante ⚠️ gate 4 não verificado (sem MCP)`, e o prompt ganha, antes de "Gravar?", a
linha abaixo (`SKILL.md` §Quem executa). Por quê: um ✅ no gate 4 sem `search-docs` seria veredito
"de cabeça", que o corpo lista como anti-padrão.

```text
Gate 4 não verificado: sem MCP, search-docs não rodou para nenhum candidato. Gravar assim mesmo é decisão sua.
```

A poda usa `Poda N`, não `P-nn`: `P-nn` é premissa do `00-requisito.md` na `feature-wiki`.

## A linha de retorno e o PR

Passo 8. A resposta da skill termina com uma linha só, sem prefixo, no formato fixo abaixo. A
`feature-wiki` a grava em `## Candidatos a Rule` do `03`; fora dela não há `03`, e a linha fecha a
resposta do mesmo jeito.

```text
apresentados 2 · gravados 1 · recusados 1 · descartados no gate 3 · poda 1
```

| Campo | Conta | Não conta |
|---|---|---|
| `apresentados` | candidatos numerados no prompt único, rule nova ou atualização de rule existente (teto 3) | descartados e itens de poda |
| `gravados` | aprovados que chegaram a `.ai/rules/`: rule nova (pelo `record-rule` ou no Fallback) ou seção atualizada | aprovado cuja prova do `arch()` falhou no passo 6 |
| `recusados` | apresentados que o usuário não aprovou | — |
| `descartados no gate` | candidatos listados em "Descartados": caíram num dos 4 gates ou ficaram sem recorrência declarada (*Vale virar rule*) | — |
| `poda` | itens `Poda N` propostos no prompt, qualquer que tenha sido a decisão (remover, atualizar, manter); a decisão vai no commit | — |

- `gravados + recusados` menor que `apresentados`: a diferença é aprovado cuja prova falhou. A saída
  da prova vai na resposta, antes da linha, com `arquivo:linha` quando é violação existente.
- Nenhum candidato: `apresentados 0 · gravados 0 · recusados 0 · descartados no gate 0 · poda 0`. A
  linha volta mesmo assim, porque é o registro de que o step 12 rodou.

**Descrição do PR.** Quando há commit (algo gravado ou podado), ele entra na branch do PR já aberto no
step 11, e a mesma linha vai para o fim da descrição do PR, com o hash curto do commit
(`git rev-parse --short HEAD`):

```text
Step 12 (requirement-to-rule): apresentados 2 · gravados 1 · recusados 1 · descartados no gate 3 · poda 1 — a1b2c3d
```

Acrescentar sem reescrever o resto da descrição (o `gh` acha o PR pela branch atual):

```bash
{ gh pr view --json body --template '{{.body}}'; printf '\n\n%s\n' 'Step 12 (requirement-to-rule): … — a1b2c3d'; } | gh pr edit --body-file -
```

As opções foram conferidas no `--help` do `gh` 2.83.0 em 2026-09-27; o comando não foi executado
contra um PR real. Sem `gh` autenticado, entregar a linha ao usuário para ele colar na descrição.
