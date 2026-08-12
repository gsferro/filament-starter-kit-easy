# Changelog

Formato baseado em [Keep a Changelog](https://keepachangelog.com/pt-BR/1.1.0/);
versionamento [SemVer](https://semver.org/lang/pt-BR/).

## [0.2.0] - 2026-08-12

### Adicionado

- Documentação visual: banner, GIF da instalação e capturas dos três painéis em `art/`.
- Badges de downloads e de status dos testes no README.
- Dashboards preenchidos nos painéis admin e infra (StatPlus + widgets de funil,
  meta, breakdown, timeline e composição) sobre os dados que os painéis já têm.
- Badge de contagem animado no menu (`App\Filament\Concerns\BadgeContagemNavegacao`).
- Colunas redimensionáveis, reordenáveis e fixáveis como padrão de toda tabela,
  documentadas em "Configuração global do Filament".

### Corrigido

- **Spotlight (⌘K) não abria em nenhum painel**: faltavam as categorias e um
  gatilho visível — a busca nativa do Filament tinha sido desligada sem
  substituto. As categorias do kit checam `canAccess()`, então a busca não
  oferece tela que resultaria em 403.
- **Conflito de JavaScript entre pacotes**: os bundles do Pulse (dotswan) e do
  resized-column declaram constantes no escopo global; o segundo a carregar
  morria inteiro com `SyntaxError: Identifier '$e' has already been declared`,
  derrubando os gráficos do Pulse sem nenhum erro visível na tela. Agora os dois
  são carregados como ES module.
- **Grupos de navegação do painel infra** misturavam inglês e português
  (`Settings`, `System`, `Logins`): agora são Observabilidade, IA, Trilhas e Sistema.
- **Página 403 do Sentinel**: traduzida para pt-BR, expõe o diagnóstico de
  permissão apenas fora de produção, identifica a conta pelo e-mail em vez do id
  e o botão "Voltar" retorna à página anterior em vez da raiz.
- Demais páginas de erro (404, 419, 500, 503) traduzidas.
- Ações de filtro NÃO são mais customizadas globalmente: num `configureUsing()`
  elas atingiam tabelas sem filtro e derrubavam 8 telas do painel infra com
  `LogicException: Action ... must have a unique name`.
- Painel de colunas fixas (resized-column) traduzido para pt-BR.

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
