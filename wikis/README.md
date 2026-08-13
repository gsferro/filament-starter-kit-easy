# Wiki do projeto

Esta pasta é a memória de longo prazo do projeto — o que um agente de IA (ou uma pessoa nova no time) precisa ler **antes** de escrever a primeira linha de código.

Ela tem duas metades:

| Pasta | O que é | Quem escreve |
|---|---|---|
| `wikis/*.md` (aqui) | **O starter kit**: arquitetura, convenções, camada de IA, receitas. Muda pouco. | mantenedor do kit, e você quando muda a fundação |
| `wikis/specs/{branch}/{feature}/` | **Cada feature**: PRD, ADR, progresso, casos de teste. Uma pasta por feature. | a skill `feature-wiki`, no início de cada implementação |

> **Regra de ouro:** a fonte da verdade é o código. Esta wiki existe para explicar **o porquê** — as decisões que o código não consegue contar sozinho e que, sem contexto, um agente "conserta" e quebra.

## Por onde começar

Leia nesta ordem. São ~20 minutos e evitam a maior parte dos erros caros:

| # | Documento | O que responde |
|---|---|---|
| 1 | [Arquitetura](arquitetura.md) | Como o kit é montado: três painéis, a "cola", o ciclo de um request, onde cada coisa mora |
| 2 | [Convenções](convencoes.md) | As regras inegociáveis e as armadilhas já resolvidas — **o documento mais importante para não quebrar nada** |
| 3 | [Camada de IA](ia.md) | Agentes como dado, guardrails fail-closed, ledger de execuções |
| 4 | [Receitas](receitas.md) | Passo a passo do que você mais vai fazer: Resource novo, página, widget, health check, comando, agente |
| 5 | [Agentes e skills](agentes-e-skills.md) | Boost, MCP, as skills instaladas e o trio feature-wiki + Ponytail + Caveman |
| 6 | [Pacotes](pacotes.md) | Qual pacote é dono de qual tela — para não reimplementar o que já existe |

## O kit em dez linhas

- **Laravel 13 + Filament 5**, PHP 8.3+, instalação em um comando (`composer create-project`), banco SQLite por padrão.
- **Três painéis** com fronteiras de acesso distintas: `/app` (negócio, nasce vazio), `/admin` (usuários, papéis, agentes de IA), `/infra` (observabilidade e manutenção).
- A **autorização** sai de `App\Models\User::canAccessPanel()` + papéis do Shield; `master_global` vence qualquer gate via `Gate::before`.
- Toda a **cola** do kit está em `app/Providers/KitServiceProvider.php` e `app/Providers/Concerns/ConfiguraFilamentGlobal.php`.
- A **camada de IA** trata o agente como dado (tabela `agentes_ia`), com guardrails encadeados e ledger em `ai_runs`.
- A **suíte do kit** (`tests/Kit/`) valida a fundação e é separada da sua (`tests/Feature`, `tests/Unit`).
- O kit é um **ponto de partida, não uma dependência**: depois do `create-project` o projeto é seu.

Versão do kit que originou este projeto: veja `config/kit.php` → `version`.

## O que um agente deve fazer antes de escrever código

1. **Ler [convencoes.md](convencoes.md)** — em especial a tabela de armadilhas. Código que parece errado ali costuma ser deliberado, com o motivo documentado.
2. **Invocar a skill certa** para o domínio (`laravel-best-practices`, `pest-testing`, `ai-sdk-development`, …). Veja [agentes-e-skills.md](agentes-e-skills.md).
3. **Usar o Laravel Boost** antes de decidir API: `search-docs` para a documentação da versão instalada, `database-schema` antes de migration, `list-artisan-commands` antes de inventar comando.
4. **Criar a wiki da feature** com a skill `feature-wiki` quando a mudança adiciona lógica de negócio nova (ela mesma diz quando **não** é necessária).
5. **Verificar antes de entregar**: `vendor/bin/pint --dirty`, `php artisan test --compact` no que foi tocado e `composer test:kit` se encostou na fundação.

## O que NÃO fazer

- Não criar pasta base nova em `app/` sem necessidade — a estrutura por painel (`app/Filament/{App,Admin,Infra}/`) já responde a quase tudo.
- Não usar `factory()` nem `faker` em seeder (só em teste). O motivo está em [convencoes.md](convencoes.md).
- Não "limpar" as anotações longas dos providers: elas são o registro de armadilhas que custaram caro.
- Não editar `AGENTS.md` / `CLAUDE.md` à mão — são gerados pelo Laravel Boost e sobrescritos no `php artisan boost:update`. Regra durável vai em `.ai/rules` (via `record-rule`) ou aqui.
- Não adicionar dependência sem aprovação explícita do usuário.
