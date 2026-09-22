---
title: "Trabalhando com agentes de IA"
description: "O kit já vem preparado para você desenvolver com um agente de código (Claude Code, Codex, Cursor, Junie, OpenCode) — e, mais importante, com a documentação…"
sidebar:
  order: 1
---
O kit já vem preparado para você desenvolver com um agente de código (Claude Code, Codex, Cursor, Junie, OpenCode) — e, mais importante, com a **documentação que o agente precisa ler** para não reinventar nem quebrar o que já está pronto.

## 📚 `wikis/` — a documentação do kit

**[`wikis/README.md`](https://github.com/gsferro/filament-starter-kit-easy/blob/main/wikis/README.md) é o ponto de entrada.** É onde mora tudo que um agente (ou uma pessoa nova no time) precisa saber antes da primeira linha de código:

| Documento | O que responde |
|---|---|
| [`wikis/arquitetura.md`](https://github.com/gsferro/filament-starter-kit-easy/blob/main/wikis/arquitetura.md) | três painéis, a "cola" do kit, ciclo de um request, os três níveis de autorização |
| [`wikis/convencoes.md`](https://github.com/gsferro/filament-starter-kit-easy/blob/main/wikis/convencoes.md) | as regras inegociáveis e as **armadilhas já resolvidas** — o documento que evita o "conserto" que quebra |
| [`wikis/ia.md`](https://github.com/gsferro/filament-starter-kit-easy/blob/main/wikis/ia.md) | agente como dado, guardrails fail-closed, ledger de execuções |
| [`wikis/receitas.md`](https://github.com/gsferro/filament-starter-kit-easy/blob/main/wikis/receitas.md) | passo a passo: Resource, página, widget, health check, comando, agente |
| [`wikis/agentes-e-skills.md`](https://github.com/gsferro/filament-starter-kit-easy/blob/main/wikis/agentes-e-skills.md) | Boost, MCP, as skills instaladas e o trio de execução |
| [`wikis/pacotes.md`](https://github.com/gsferro/filament-starter-kit-easy/blob/main/wikis/pacotes.md) | qual pacote é dono de qual tela — para não reimplementar vendor |

É também a pasta onde **você** escreve o que for do seu projeto: `wikis/specs/{branch}/{feature}/` recebe uma pasta por feature, criada pela skill abaixo.

> As `wikis/specs/` **do kit** — as ADRs das features que construíram o próprio kit, citadas ao longo deste README — ficam só no repositório do kit: o `.gitattributes` as marca com `export-ignore`, e o `kit:update` entrega apenas os documentos de topo de `wikis/`. No seu projeto a pasta `wikis/specs/` nasce vazia, para as suas features. Para consultar uma decisão citada aqui, veja o repositório: <https://github.com/gsferro/filament-starter-kit-easy>.

## As skills instaladas

O [Laravel Boost](https://github.com/laravel/boost) está configurado (`boost.json`) para cinco agentes, com servidor MCP (`php artisan boost:mcp`) e nove skills sincronizadas — entre elas `laravel-best-practices`, `pest-testing`, `ai-sdk-development`, `tailwindcss-development`, `pulse-development`, `laravel-backup` e `blaze-optimize`.

A que muda o fluxo de trabalho é a **[`feature-wiki`](https://github.com/gsferro/laravel-ai-skills)**: invocada **antes** de implementar qualquer feature, ela cria `wikis/specs/{branch}/{feature}/` com plano de ação (PRD), decisões arquiteturais (ADR), progresso e casos de teste — além de fixar o padrão de log do projeto.

> 💡 **Feature nova? Chame `/feature-wiki`.** É o primeiro passo, antes de qualquer `php artisan make:*`. A skill pesquisa o código, escreve o plano e só então começa a implementação. Para typo, ajuste de config, refactor puro ou bump de dependência, pule — ela mesma diz quando não vale a pena.

No Claude Code ela trabalha com dois plugins já habilitados em `.claude/settings.json`, cada um cobrindo uma camada diferente:

| Camada | Ferramenta | Papel |
|---|---|---|
| Comunicação | [Caveman](https://github.com/JuliusBrussee/caveman) | resposta enxuta — **não** se aplica a wiki, código, commits e avisos de segurança |
| Planejamento | [feature-wiki](https://github.com/gsferro/laravel-ai-skills) | PRD + ADR + casos de teste + tracking |
| Execução | [Ponytail](https://github.com/DietrichGebert/ponytail) | mínimo código que funciona — sem cortar validação, segurança ou tratamento de erro |

```bash
php artisan boost:add-skill gsferro/laravel-ai-skills   # a skill
php artisan boost:update                                # sincroniza para todos os agentes
```

> `AGENTS.md` e `CLAUDE.md` são **gerados** pelo Boost — editar à mão é trabalho perdido no próximo `boost:update`. Regra durável vai em `.ai/rules` (ferramenta `record-rule`) ou na `wikis/`.

### Caveman e Ponytail fora do Claude Code

O trio acima só é trio de verdade se as três camadas existirem. No Claude Code, Caveman e
Ponytail chegam como **plugin** (`.claude/settings.json`) — com ativação automática por hook e
comandos no namespace `/ponytail:…` e `/caveman:…`. Nos outros agentes não há sistema de plugin,
e a `feature-wiki` invocaria um `/ponytail-review` que não existe.

Por isso o kit **versiona uma cópia** das três skills que a `feature-wiki` cita por nome, em
`.agents/skills/`, `.ai/skills/` e `.junie/skills/`:

| Skill | Para quê a `feature-wiki` usa |
|---|---|
| `ponytail` | a escada de simplicidade durante a implementação (step 7) |
| `ponytail-review` | auditoria do plano contra over-engineering (step 6, obrigatório) e do diff no fim |
| `caveman` | comunicação enxuta agent ↔ você; **não** vale para wiki, código, commit ou aviso de segurança |

Duas consequências práticas:

- **A invocação muda de nome.** No Claude Code é `/ponytail:ponytail-review`; nos demais agentes,
  a cópia local responde por `/ponytail-review`, sem namespace.
- **`.claude/skills/` fica de fora de propósito.** Copiar para lá criaria duas `ponytail` ativas
  ao mesmo tempo — a do plugin e a do projeto.

`boost:update` **não** apaga essas pastas: ele só remove skill que já rastreou e saiu do
`boost.json`, e nenhuma das três está listada lá. São cópias MIT, com o `LICENSE` original junto —
atualizar é recopiar do upstream ([Caveman](https://github.com/JuliusBrussee/caveman),
[Ponytail](https://github.com/DietrichGebert/ponytail)).

## O ciclo de uma feature com agente

O kit não pede que você confie no agente: pede que ele **deixe rastro**. Cada etapa produz um
arquivo que a etapa seguinte confere.

| # | Você faz | O agente produz | Por que existe |
|---|---|---|---|
| 1 | `/feature-wiki` com o pedido em texto corrido | `wikis/specs/{branch}/{feature}/00-requisito.md` — **cópia imutável** do que você pediu | O requisito nunca é reescrito para caber no que foi implementado. É ele que julga a entrega |
| 2 | lê e ajusta | `01-plano-acao.md` (PRD passo a passo), `02-decisoes.md` (ADR), `04-casos-de-teste.md`, e `05-…-browser.md` quando tem tela | Revisar plano é barato; revisar 900 linhas de diff, não |
| 3 | aprova | auditoria automática do plano por `ponytail-review` | Corta passo desnecessário e abstração prematura **antes** de virar código |
| 4 | — | implementação seguindo o plano, com `03-progresso.md` atualizado | Sessão que cai retoma de onde parou, sem reconstruir contexto |
| 5 | — | testes rodando (`--parallel --tia`) | Verde é pré-condição do passo seguinte, não a entrega |
| 6 | — | `/feature-quality-gate` → `06-relatorio-qa.md` | Confronta requisito × plano × app rodando. A **matriz de rastreabilidade** expõe a cláusula que nunca virou passo, teste nem código — a omissão que suíte verde não denuncia |
| 7 | aprova | `/requirement-to-rule` → regra em `.ai/rules` | Decisão que vale além desta feature passa a valer para **toda sessão futura**, de qualquer agente |

**O que isso muda na prática:**

- **O agente lê antes de escrever.** `wikis/` e `.ai/rules` respondem o que já existe, e o
  [roteiro de features](../roteiro-de-features/) abaixo lista as 68 features prontas. Feature
  reimplementada do zero porque o agente não sabia que existia é o custo mais caro e mais invisível.
- **Contexto vira arquivo, não histórico de chat.** Trocar de agente, de máquina ou de pessoa não
  perde o porquê da decisão — ele está no ADR, versionado no mesmo commit do código.
- **Simples por padrão, sem cortar o que importa.** Ponytail nunca simplifica validação em fronteira
  de confiança, tratamento de erro que evita perda de dado, segurança ou acessibilidade.
- **Menos token por resposta.** Caveman corta a prosa da conversa; wiki, código e commit continuam
  em português normal.
- **Cada correção fica.** Armadilha resolvida vira `.ai/rules` — e o gate seguinte já a verifica.
  Quando dá para provar por `pest --arch`, PHPStan ou Rector, a regra aponta para o teste em vez de
  pedir boa vontade.

> Para typo, ajuste de `.env`, bump de dependência ou refactor puro, **pule o ciclo**. A skill
> mesma diz quando não compensa — cerimônia em mudança de uma linha é o over-engineering que o
> Ponytail existe para cortar.

## Saída para agente — `laravel/pao`

O kit já traz o [`laravel/pao`](https://packagist.org/packages/laravel/pao) em `require-dev`, e
provavelmente você nunca notou: ele **só age quando um agente de IA está rodando o comando**. Para
uma pessoa no terminal, tudo continua igual.

Sob agente, a suíte inteira responde **uma linha**:

```json
{"tool":"pest","result":"passed","tests":2614,"passed":2614,"assertions":10164,"duration_ms":283774}
```

E quando falha, a linha traz `file`, `line` e `message` de cada caso — já extraídos, sem o agente
ter que varrer centenas de linhas de saída colorida.

| Ferramenta | O que muda sob agente |
|---|---|
| Pest, PHPUnit, Paratest | uma linha de JSON; falhas em `failures[]` com arquivo e linha |
| PHPStan | JSON agrupado por arquivo, truncado em 30 erros (use `-v` para todos) |
| Rector | JSON; em `--dry-run`, arquivo alterado conta como reprovação |
| Artisan | sem ANSI e sem decoração — `Versão do kit .. 0.38.1` em vez da linha pontilhada |
| Pint | **não é o `pao`** — o Pint tem detecção própria e já emite JSON sozinho |

### Como ele sabe que é um agente

Em três camadas, nesta ordem (`vendor/laravel/agent-detector/src/AgentDetector.php`):

1. **`AI_AGENT`**, se tiver **conteúdo**. Vazia ou só com espaço **não conta** — o detector faz
   `trim()` e devolve "sem agente".
2. A **presença** de qualquer uma de 19 variáveis conhecidas: `CLAUDECODE`, `CURSOR_AGENT`,
   `GEMINI_CLI`, `CODEX_SANDBOX`, `CODEX_CI`, `CODEX_THREAD_ID`, `COPILOT_CLI`, e mais. Aqui só o
   **nome** importa, o valor não. **Não há curinga**: `CODEX_HOME`, por exemplo, não dispara nada.
3. O **sistema de arquivos**: `file_exists('/opt/.devin')` identifica o Devin **sem variável de
   ambiente nenhuma**.

O CI do kit **não** define nenhuma das 19, então lá a saída é a de sempre.

O efeito colateral das camadas 1 e 2 é útil de saber: qualquer coisa que limpe o ambiente —
`env -u`, `sudo` sem `-E`, `docker run` sem `-e` — desliga o `pao` sem avisar. **No Devin não**:
lá a detecção é por arquivo e sobrevive ao ambiente limpo.

### Como desligar

```bash
PAO_DISABLE=1 php artisan test    # saída humana, mesmo sob agente
PAO_FORCE=1   php artisan test    # saída de agente, mesmo sem agente
```

> **As duas são variáveis de ambiente de verdade, e não entram no `.env`.** O `pao` as lê de
> `$_SERVER` antes de o Laravel existir — uma linha no `.env` do projeto não tem efeito.

Use `PAO_DISABLE=1` quando estiver depurando junto com o agente e quiser ver a saída completa.

### Duas pegadinhas que economizam tempo

- **`--compact` é suplantado sob agente.** O plugin injeta `--no-output --no-progress`, então
  `php artisan test --compact` e `php artisan test` dão o mesmo resultado quando um agente roda.
- **`pint --format agent` é redundante sob agente.** O Pint detecta sozinho. O flag continua útil
  para forçar JSON fora de uma sessão com agente.

## Auditoria de segurança do GitHub — `laravel/moat` (opcional)

O [`moat`](https://github.com/laravel/moat) **não é um pacote PHP e não é dependência do kit.** É
uma CLI escrita em Rust que faz uma revisão **somente leitura** da configuração de segurança de uma
organização ou repositório no GitHub: exigência de 2FA, proteção de branch, varredura de segredos,
permissões de workflow — mais de 25 verificações.

Ele não altera nada; apenas relata.

```bash
brew tap laravel/moat https://github.com/laravel/moat
brew install laravel/moat/moat

moat sua-org        # uma organização ou usuário
moat owner/repo     # um repositório
```

Precisa de autenticação no GitHub (`GITHUB_TOKEN`, `GH_TOKEN` ou a CLI `gh` já logada), e aceita
saída em terminal, JSON ou Markdown.

> **É ferramenta de quem mantém o repositório, não de quem usa o kit.** Ela audita configuração no
> GitHub, não o seu código. E a instalação é por Homebrew, que não é padrão no Windows — lá o
> caminho é WSL ou baixar o binário do release.
