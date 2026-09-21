# Plano de Ação — Pacotes Laravel para o ecossistema de IA

> Requisito: `00-requisito.md` · Decisões: `02-decisoes-arquiteturais.md`

## Natureza da Wiki

- **Tipo**: nova
- **Wiki ancestral**: —
- **Motivo**: avaliação de três ferramentas do ecossistema
- **Toca infra compartilhada?**: **sim** — `composer.json` (duas constraints) e `CLAUDE.md`, que é
  lido por todo agente em toda sessão. A regressão obrigatória é a suíte do kit inteira, porque
  mexer em constraint muda o que o resolvedor instala.

## Cobertura do Requisito

| RQ | Cláusula | Passo(s) | Observação |
|----|----------|----------|------------|
| RQ-01 | Análise profunda de cada pacote | — | **Já entregue** nas ADR-01/03/05, com medição |
| RQ-02 | Decidir como implementar de forma eficiente | 1, 2, 3 | ADR-02 e ADR-04 |
| RQ-03 | Muito bem documentado | 2, 3 | pt **e** en |
| RQ-04 | `laravel/vet` | — | **Adiado** (ADR-03). Débito registrado no `03` com gatilho |
| RQ-05 | `laravel/pao` | 1, 2 | **Já instalado**; a entrega é documentação (ADR-01) |
| RQ-06 | `laravel/moat` | 3 | Documentado como ferramenta do mantenedor (ADR-05) |
| RQ-07 | Avaliar necessidade de rules | 5 | Candidato único; decisão do usuário |

> **Nenhum `composer require` nesta entrega.** É consequência da análise que o próprio requisito
> pediu primeiro, e está registrada em três ADRs. Confirmado com o usuário em 2026-09-21.

## Objetivo

Fechar a lacuna entre o que o kit **tem** e o que ele **conta**. O `pao` já trabalha em todo
`composer test` rodado por agente e ninguém que instala o kit sabe disso; o `vet` é atraente e
perigoso no momento errado; o `moat` foi pedido junto e não é da mesma natureza.

## Contexto

O kit é explicitamente desenhado para trabalho com agente — `require-dev` traz `laravel/boost`, e o
repositório tem `.ai/rules`, `CLAUDE.md` e skills. Duas afirmações do `CLAUDE.md` envelheceram sem
que ninguém percebesse, porque só são falsas **sob agente** (ver passo 2).

## Análise dos Arquivos Existentes

### `composer.json`
- `:88` → `"laravel/pao": "^1.0.6"` (lock: v1.1.4)
- `:92` → `"nunomaduro/collision": "^8.6"` — piso real **8.9.5**, forçado pelo `conflict` do `pao`
- `"php": "^8.3"` — piso real **8.4** (29 pacotes de produção no lock)

### `docs/{pt,en}/referencia/pacotes-instalados.md`
`:124` descreve o `pao` como *"ferramentas de desenvolvimento do Laravel"*.

### `docs/{pt,en}/operacao/agentes-de-ia.md`
110 linhas (pt) / 116 (en), com `## As skills instaladas` e `## O ciclo de uma feature com agente`.
**É a casa natural** da seção nova — não criar página.

### `CLAUDE.md`
Manda `vendor/bin/pint --dirty --format agent` e `php artisan test --compact`. As duas instruções
são **redundantes ou inertes sob agente** (passo 2).

## Autorização

Não se aplica — documentação e manifesto.

## Rotas

Nenhuma.

## Superfície de UI

**Sem superfície de UI.** Consequência: **não haverá `05-casos-de-teste-browser.md`**.

## Variáveis de Ambiente

Nenhuma nova. Duas **documentadas** (já existem no `pao`, ausentes do README dele):

| Key | Efeito | Observação |
|-----|--------|------------|
| `PAO_DISABLE` | desliga o `pao` por completo | lida de **`$_SERVER`** — `.env` do Laravel **não** serve |
| `PAO_FORCE` | liga sem agente | útil para conferir o comportamento |

## Modelo de Execução

| Pergunta | Resposta |
|---|---|
| Quantos requests? | **zero** — documentação e manifesto |
| Custo | duas constraints e texto |

## Impacto em Features Existentes

- **`composer.json`**: mexer em constraint muda o que o resolvedor instala. `composer validate` e a
  suíte inteira são obrigatórios
- **`CLAUDE.md`**: lido por todo agente em toda sessão; frase errada ali se propaga
- **Guardas de documentação**: `SiteDeDocumentacaoTest` e `RedeDeDocumentacaoTest` travam
  contadores dos readmes e espelho pt↔en. Wiki nova → contador de especificações sobe

## Rollback

Sem migration. `git revert` do commit correspondente. As constraints voltam a valores que já eram
falsos — o rollback restaura o defeito, não causa outro.

## Dependências

**Nenhuma nova.** É o ponto da entrega.

## Riscos

| Risco | Mitigação |
|---|---|
| `^8.4` excluir quem hoje instala | Nenhum: o piso real já é 8.4. `composer validate` + suíte confirmam |
| `^8.9.3` conflitar com outro pacote | O lock já está em 8.9.5; a constraint só descreve o que existe |
| Doc do `moat` sugerir que é dependência | O texto diz explicitamente que é CLI em Rust, opcional, do mantenedor |
| Contadores dos readmes quebrarem | Esperado; sincronizar no commit da wiki |

## Channel de Log da Feature

**Não se aplica** — a entrega não executa lógica. Nenhum log novo, nenhum channel novo.

## Estrutura de Implementação

### 1. As duas constraints do `composer.json`

> Skills: `ponytail`

- `"nunomaduro/collision": "^8.6"` → `"^8.9.3"` (ADR-04)
- `"php": "^8.3"` → `"^8.4"` (ADR-06) — **commit separado**, conforme decidido
- Verificação: `composer validate --strict` e `composer install --dry-run` sem mudança de lock

### 2. A verdade sobre o `pao` na documentação

> Skills: —

- **`docs/{pt,en}/referencia/pacotes-instalados.md:124`** — trocar a descrição genérica por uma que
  diga o que ele faz e como desligar
- **`docs/{pt,en}/operacao/agentes-de-ia.md`** — seção nova `## Saída para agente (laravel/pao)`:
  o que muda por ferramenta, `PAO_DISABLE`/`PAO_FORCE`, e o aviso de que leem de `$_SERVER`
- **`CLAUDE.md`** — corrigir as duas afirmações que só são falsas sob agente:
  - `pint --format agent` é **redundante** sob agente (o Pint tem detecção própria)
  - `--compact` é **suplantado** sob agente (o plugin injeta `--no-output --no-progress`)
  - registrar `PAO_DISABLE=1` como o jeito de ver saída humana ao depurar

### 3. O `moat` como ferramenta opcional do mantenedor

> Skills: —

- **`docs/{pt,en}/operacao/agentes-de-ia.md`** — subseção curta: o que é (CLI **Rust**, Homebrew),
  o que audita, que **não é dependência do kit** e **não tem relação com IA**, e a ressalva de que
  Homebrew não é padrão no Windows
- **Links relativos** — `[CT-45]` de `SiteDeDocumentacaoTest` reprova caminho absoluto

### 4. CHANGELOG e contadores

> Skills: —

- `## [Unreleased]` → `### Alterado` (constraints) e `### Adicionado` (documentação)
- Sincronizar o contador de especificações dos dois readmes (wiki nova = +1)

### 5. Candidato a rule (RQ-07)

> Skills: `requirement-to-rule`

Candidato único, a apresentar ao usuário — **não gravar sem aprovação**:

> **Dependência de dev pública e MIT *deve* viajar no `create-project`.** O veto de
> `.ai/rules/general.md` é sobre **repositório privado** (o caso `filament/blueprint`), não sobre
> `require-dev`. Sem essa distinção escrita, um agente futuro lê a regra do Blueprint por analogia
> e remove o `pao` "por precaução".

Gates: durável ✅ · escopável (`composer.json`) ✅ · não-inferível ✅ · não-redundante — **é emenda à
`general.md` existente**, não rule nova (a skill prefere atualizar a criar).

> **O MCP do `laravel-boost` caiu nesta sessão.** Se não voltar, a emenda vai à mão e o desvio fica
> registrado no `03`.

## Filosofia de Implementação

> **Ponytail em modo `full`.** A entrega é texto e duas constraints. Nada de criar página nova onde
> cabe seção, nada de guarda de teste onde não há erro a impedir (ADR-02 explica por que o `pao`
> não precisa da guarda que o Blueprint precisa).

## Testes

Ver `04-casos-de-teste.md`. **Sem `05-casos-de-teste-browser.md`** — sem superfície de UI.

## Verificação Final

- [ ] `composer validate --strict`
- [ ] `composer install --dry-run` — lock inalterado
- [ ] `php artisan test --compact tests/Kit/SiteDeDocumentacaoTest.php tests/Kit/RedeDeDocumentacaoTest.php`
- [ ] `composer test:kit` — a suíte inteira (mexeu em constraint)
- [ ] `vendor/bin/pint --dirty --format agent`
- [ ] `/code-review` no diff (step 7.5)
- [ ] `feature-quality-gate` (step 8)

## Commits

Individualizados (PR-03):

- `:memo: docs(wiki): requisito dos pacotes Laravel para o ecossistema de IA` *(feito)*
- `:memo: docs(wiki): ADRs — pao fica, vet adiado, moat documentado`
- `:wrench: chore(composer): alinha collision ao piso que o pao ja impoe`
- `:wrench: chore(composer): php ^8.4, que e o piso real desde antes desta wiki`
- `:memo: docs(agentes): o que o laravel/pao faz, e como desligar`
- `:memo: docs(agentes): laravel/moat como ferramenta opcional do mantenedor`
