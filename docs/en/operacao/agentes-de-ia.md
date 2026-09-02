---
title: "Working with AI agents"
parent: "Operations"
grand_parent: "English"
nav_order: 1
---

# Working with AI agents

The kit ships ready for you to develop with a coding agent (Claude Code, Codex, Cursor, Junie, OpenCode) — and, more importantly, with **the documentation the agent needs to read** so it doesn't reinvent or break what's already there.

## 📚 `wikis/` — the kit's documentation

**[`wikis/README.md`](https://github.com/gsferro/filament-starter-kit-easy/blob/main/wikis/README.md) is the entry point.** It's where everything an agent (or a new teammate) needs before the first line of code lives:

| Document | What it answers |
|---|---|
| [`wikis/arquitetura.md`](https://github.com/gsferro/filament-starter-kit-easy/blob/main/wikis/arquitetura.md) | the three panels, the kit's "glue", a request's lifecycle, the three authorization levels |
| [`wikis/convencoes.md`](https://github.com/gsferro/filament-starter-kit-easy/blob/main/wikis/convencoes.md) | the non-negotiable rules and the **traps already handled** — the document that prevents the "fix" that breaks things |
| [`wikis/ia.md`](https://github.com/gsferro/filament-starter-kit-easy/blob/main/wikis/ia.md) | the agent as data, fail-closed guardrails, execution ledger |
| [`wikis/receitas.md`](https://github.com/gsferro/filament-starter-kit-easy/blob/main/wikis/receitas.md) | step by step: Resource, page, widget, health check, command, AI agent |
| [`wikis/agentes-e-skills.md`](https://github.com/gsferro/filament-starter-kit-easy/blob/main/wikis/agentes-e-skills.md) | Boost, MCP, the installed skills and the execution trio |
| [`wikis/pacotes.md`](https://github.com/gsferro/filament-starter-kit-easy/blob/main/wikis/pacotes.md) | which package owns which screen — so you don't reimplement vendor code |

It's also the folder where **you** write your project's own docs: `wikis/specs/{branch}/{feature}/` gets one folder per feature, created by the skill below.

> The wiki is written in pt-BR, like the kit's UI and code comments.

> The kit's own `wikis/specs/` — the ADRs of the features that built the kit itself, cited throughout
> this README — stay in the kit's repository only: `.gitattributes` marks them `export-ignore`, and
> `kit:update` delivers just the top-level documents of `wikis/`. In your project the `wikis/specs/`
> folder is born empty, for your own features. To read a decision cited here, see the repository:
> <https://github.com/gsferro/filament-starter-kit-easy>.

## The installed skills

[Laravel Boost](https://github.com/laravel/boost) is configured (`boost.json`) for five agents, with an MCP server (`php artisan boost:mcp`) and nine synchronized skills — among them `laravel-best-practices`, `pest-testing`, `ai-sdk-development`, `tailwindcss-development`, `pulse-development`, `laravel-backup` and `blaze-optimize`.

The one that changes the workflow is **[`feature-wiki`](https://github.com/gsferro/laravel-ai-skills)**: invoked **before** implementing any feature, it creates `wikis/specs/{branch}/{feature}/` with an action plan (PRD), architecture decisions (ADR), progress tracking and test cases — and it sets the project's logging standard.

> 💡 **New feature? Call `/feature-wiki`.** It's the first step, before any `php artisan make:*`. The skill researches the code, writes the plan, and only then does implementation start. For a typo, a config tweak, a pure refactor or a dependency bump, skip it — the skill itself tells you when it isn't worth it.

In Claude Code it works alongside two plugins already enabled in `.claude/settings.json`, each covering a different layer:

| Layer | Tool | Role |
|---|---|---|
| Communication | [Caveman](https://github.com/JuliusBrussee/caveman) | terse replies — does **not** apply to the wiki, code, commits or security warnings |
| Planning | [feature-wiki](https://github.com/gsferro/laravel-ai-skills) | PRD + ADR + test cases + tracking |
| Execution | [Ponytail](https://github.com/DietrichGebert/ponytail) | the minimum code that works — without cutting validation, security or error handling |

```bash
php artisan boost:add-skill gsferro/laravel-ai-skills   # the skill
php artisan boost:update                                # syncs it to every agent
```

> `AGENTS.md` and `CLAUDE.md` are **generated** by Boost — editing them by hand is lost work on the next `boost:update`. Durable rules go in `.ai/rules` (the `record-rule` tool) or in `wikis/`.

### Caveman and Ponytail outside of Claude Code

The trio above is only a real trio if all three layers exist. In Claude Code, Caveman and
Ponytail come as **plugin** (`.claude/settings.json`) — with automatic activation by hook and
commands in the `/ponytail:…` and `/caveman:…` namespace. In other agents there is no plugin
system, and `feature-wiki` would invoke a `/ponytail-review` that does not exist.

That's why the kit **versions a copy** of the three skills that `feature-wiki` cites by name, in
`.agents/skills/`, `.ai/skills/` and `.junie/skills/`:

| Skill | What `feature-wiki` uses it for |
|---|---|
| `ponytail` | the simplicity ladder during implementation (step 7) |
| `ponytail-review` | plan audit against over-engineering (step 6, mandatory) and diff audit at the end |
| `caveman` | terse agent ↔ you communication; does **not** apply to wiki, code, commit or security warning |

Two practical consequences:

- **The invocation name changes.** In Claude Code it is `/ponytail:ponytail-review`; in the other
  agents the local copy answers to `/ponytail-review`, with no namespace.
- **`.claude/skills/` is left out on purpose.** Copying there would create two active `ponytail`s
  at the same time — the plugin's and the project's.

`boost:update` **does not** delete these folders: it only removes a skill it has already tracked
and that left `boost.json`, and none of the three are listed there. They are MIT copies, with the
original `LICENSE` attached — updating is re-copying from upstream ([Caveman](https://github.com/JuliusBrussee/caveman),
[Ponytail](https://github.com/DietrichGebert/ponytail)).

## The feature cycle with an agent

The kit does not ask you to trust the agent: it asks the agent to **leave a trail**. Each step
produces a file that the next step checks.

| # | You do | The agent produces | Why it exists |
|---|---|---|---|
| 1 | `/feature-wiki` with the request in plain text | `wikis/specs/{branch}/{feature}/00-requisito.md` — **immutable copy** of what you asked | The requirement is never rewritten to fit what was implemented. It is what judges the delivery |
| 2 | read and adjust | `01-plano-acao.md` (step-by-step PRD), `02-decisoes.md` (ADR), `04-casos-de-teste.md`, and `05-…-browser.md` when there is a screen | Reviewing a plan is cheap; reviewing 900 lines of diff is not |
| 3 | approve | automatic plan audit by `ponytail-review` | Cuts unnecessary step and premature abstraction **before** it becomes code |
| 4 | — | implementation following the plan, with `03-progresso.md` updated | A session that drops resumes from where it stopped, without rebuilding context |
| 5 | — | tests running (`--parallel --tia`) | Green is a precondition for the next step, not the delivery |
| 6 | — | `/feature-quality-gate` → `06-relatorio-qa.md` | Confronts requirement × plan × running app. The **traceability matrix** exposes the clause that never became a step, test or code — the omission a green suite does not denounce |
| 7 | approve | `/requirement-to-rule` → rule in `.ai/rules` | A decision that matters beyond this feature starts to matter for **every future session**, of any agent |

**What this changes in practice:**

- **The agent reads before writing.** `wikis/` and `.ai/rules` answer what already exists, and the
  [feature roadmap](roteiro-de-features.md) below lists the 68 ready features. A feature
  reimplemented from scratch because the agent didn't know it existed is the most expensive and most invisible cost.
- **Context becomes a file, not chat history.** Switching agent, machine or person does not
  lose the why of the decision — it is in the ADR, versioned in the same commit as the code.
- **Simple by default, without cutting what matters.** Ponytail never simplifies validation at a trust
  boundary, error handling that prevents data loss, security or accessibility.
- **Less token per reply.** Caveman cuts the prose from the conversation; wiki, code and commit continue
  in normal Portuguese.
- **Every fix sticks.** A resolved trap becomes `.ai/rules` — and the next gate already checks it.
  When it can be proven by `pest --arch`, PHPStan or Rector, the rule points to the test instead of
  asking for goodwill.

> For a typo, `.env` tweak, dependency bump or pure refactor, **skip the cycle**. The skill
> itself tells you when it isn't worth it — ceremony in a one-line change is the over-engineering
> Ponytail exists to cut.

