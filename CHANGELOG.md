# Changelog

Formato baseado em [Keep a Changelog](https://keepachangelog.com/pt-BR/1.1.0/);
versionamento [SemVer](https://semver.org/lang/pt-BR/).

## [0.1.0] - 2026-08-12

### Adicionado

- Skeleton Laravel 13 + Filament 5 instalável via `composer create-project gsferro/starter-kit-easy`.
- Comando `kit:install`: cria `.env`, gera `APP_KEY`, prepara o banco SQLite, migra,
  semeia papéis/permissões/usuário, publica assets e faz o build do front-end.
  Roda sozinho no `post-create-project-cmd` e é idempotente.
- Três painéis: `/app` (negócio, vazio de propósito), `/admin` (usuários, Shield,
  agentes de IA, onboarding) e `/infra` (health, backups, filas, logs, auditoria,
  caches, Command Center, Pulse, custos de IA).
- Fundação: traits `TemUuid` e `AuditsFillables`, `Gate::before` para `master_global`,
  `CarbonImmutable`, `prohibitDestructiveCommands` em produção, `Password::defaults()`,
  configuração global do Filament (tabelas, toggles, Panel Switch).
- Núcleo de IA com `laravel/ai`: catálogo de agentes no banco, guardrails encadeados,
  ledger `ai_runs` e chat com streaming. Inferência local por padrão (llama.cpp).
- Docker com profiles opt-in: `pgsql`+`redis` na base, `ai`, `mail`, `app`, `realtime`.
- Qualidade: Pest, Pint (setas alinhadas), PHPStan level 6, CI com job que prova o
  `create-project` ponta a ponta.
- Traduções pt-BR (laravel-lang) e UI dos painéis em português.
