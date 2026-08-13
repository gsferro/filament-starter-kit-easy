# Decisões Arquiteturais — Multi-tenancy por Organização

## ADR-01: Tenancy opt-in por comando, não sempre ligada

**Status**: Aceita
**Data**: 2026-08-13

### Contexto

O starter-kit é um ponto de partida para projetos de perfis muito diferentes. Multi-tenancy muda a URL do painel de negócio, o schema das tabelas de permissão e o modelo mental de toda query. Impor isso a quem só quer um painel administrativo cobra um preço permanente por um recurso que boa parte dos projetos nunca usará.

### Decisão

O kit continua nascendo single-tenant. Um comando `php artisan kit:tenancy` liga o modo multi-organização, escrevendo `KIT_TENANCY=true`, ligando `permission.teams`, apontando o `tenant_model` do Shield e recriando o banco.

Todo o código de tenancy fica guardado por `config('kit.tenancy')` — em especial o `->tenant(...)` do `AppPanelProvider` e o listener de `TenantSet` no `KitServiceProvider`.

### Alternativas Consideradas

1. **Sempre ligada, com um tenant "Padrão"** — descartada: obriga `/app/{tenant}` e o vocabulário de organização em todo projeto, inclusive nos que têm um único cliente. Também tornaria a suíte atual do kit inválida sem reescrita.
2. **Só uma receita documentada em `wikis/`** — descartada: joga para cada usuário o trabalho difícil (Spotlight, ledger de IA, seeders, testes de isolamento), que é justamente onde o kit agrega. Um starter kit que documenta em vez de entregar não está entregando.

### Consequências

- **Positivas**: quem não usa tenancy não paga nada; a suíte `tests/Kit` atual continua verde sem alteração; o modo pode ser ligado no dia 1 do projeto, que é quando custa barato.
- **Negativas**: dois caminhos de código para manter, e todo teste de tenancy precisa ligar a flag no setup.
- **Riscos**: o caminho com tenancy fica menos exercitado que o default. Mitigação: `tests/Kit/TenancyTest.php` roda no mesmo `composer test:kit`.

### Referências

- `app/Providers/Filament/AppPanelProvider.php`
- `config/kit.php`

---

## ADR-02: `/app` é tenant-aware; `/admin` e `/infra` seguem globais

**Status**: Aceita
**Data**: 2026-08-13

### Contexto

O kit tem três painéis com públicos distintos (`wikis/arquitetura.md`). Precisava-se decidir quais deles ganham o recorte por organização.

### Decisão

Só o `/app`. O `/admin` recebe o CRUD de Organizações e o vínculo de usuários — ele **administra** os tenants, então precisa enxergar todos. O `/infra` continua global: health checks, filas, logs, backups e Pulse são propriedades da instalação, não de um cliente.

### Alternativas Consideradas

1. **Tenancy nos três painéis** — descartada: recortar observabilidade por cliente esconderia justamente o que o operador precisa ver (uma fila travada não pertence a um tenant). E o `/admin` recortado não conseguiria cadastrar organização nenhuma.
2. **`/admin` como painel "central app" do Shield** — descartada por ora: o Shield tem esse conceito (`InstallCommand:78-82`), mas ele adiciona uma camada de configuração que só se paga quando há papéis administrativos por tenant. Fica registrado como evolução possível.

### Consequências

- **Positivas**: a fronteira é fácil de explicar ("dados do cliente × operação da instalação"); os testes de `/infra` e `/admin` seguem intactos.
- **Negativas**: um operador com acesso ao `/infra` vê logs de todas as organizações. É o comportamento correto, mas precisa estar claro numa instalação com dados sensíveis.

### Referências

- `wikis/arquitetura.md` — seção "Os três painéis"
- `app/Models/User.php:65` — `canAccessPanel()`

---

## ADR-03: Papéis por organização (`permission.teams = true`)

**Status**: Aceita
**Data**: 2026-08-13

### Contexto

Havia duas formas de recortar acesso: manter os papéis globais (tenancy escopa só os dados) ou torná-los por organização, permitindo que o mesmo usuário tenha papéis diferentes em cada uma.

O Shield decide seu modo de tenancy lendo exatamente essa flag: `Utils::isTenancyEnabled()` retorna `config('permission.teams', false)` (`vendor/bezhansalleh/filament-shield/src/Support/Utils.php`).

### Decisão

Papéis por organização. `permission.teams = true`, `filament-shield.tenant_model = Organizacao::class`.

### Alternativas Consideradas

1. **Papéis globais** — mais barata (nenhuma mudança de schema, Shield intocado) e suficiente para "ver só as organizações a que estou vinculado". Descartada por decisão do produto: o caso de um usuário ser gestor numa organização e operador em outra é comum, e resolvê-lo depois custaria migration de dados, não só de schema.

### Consequências

- **Positivas**: modelo de autorização completo desde o início; é o modo que o Shield suporta nativamente, então a UI de papéis já acompanha.
- **Negativas**: liga um flag que altera três tabelas (`roles`, `model_has_roles`, `model_has_permissions`) e não tem volta fácil — ver ADR-04.
- **Riscos**: `setPermissionsTeamId()` precisa ser chamado a cada request; esquecer isso faz o recorte falhar em silêncio (papéis resolvidos com `team_id` nulo). Mitigação: o listener de `TenantSet` no `KitServiceProvider` e o CT-04.

### Referências

- `config/permission.php:151`
- `vendor/bezhansalleh/filament-shield/src/Support/Utils.php`
- Refina: ADR-04

---

## ADR-04: `kit:tenancy` recria o banco em vez de migrar aditivamente

**Status**: Aceita
**Data**: 2026-08-13

### Contexto

A migration de permissões do spatie cria as colunas de team **condicionalmente**, lendo `config('permission.teams')` em tempo de execução (`database/migrations/2026_08_12_164859_create_permission_tables.php:14, 40-48, 65-67`). Ligar a flag depois do `migrate` deixa a config dizendo "teams" e o schema sem as colunas — estado incoerente e silencioso.

Corrigir aditivamente exige acrescentar `team_id` a três tabelas e **refazer índices únicos** (`roles` passa de `unique(name, guard_name)` para `unique(team_id, name, guard_name)`). Em SQLite, o banco default do kit, alterar índice único implica recriar a tabela.

### Decisão

O comando exige árvore git limpa, avisa que é destrutivo, pede confirmação explícita e roda `migrate:fresh --seed`. Com `--no-interaction` só prossegue com `--force`.

### Alternativas Consideradas

1. **Migration aditiva** — descartada para a v1: o custo (recriação de tabela em SQLite, três bancos suportados, risco de corromper permissões existentes) não se justifica num comando cuja recomendação é rodar no dia 1 do projeto.
2. **Detectar e avisar sem agir** — descartada: deixaria o usuário no meio do caminho, que é pior que não começar.

### Consequências

- **Positivas**: um caminho só, previsível, sem estado intermediário incoerente.
- **Negativas**: projeto com dados em produção não pode ligar tenancy pelo comando. Fica documentado que, nesse caso, a migração é manual.
- **Riscos**: perda de dados por engano. Mitigações: exigência de git limpo, confirmação explícita, aviso em vermelho e `--force` obrigatório em modo não-interativo.

### Referências

- `app/Console/Commands/KitUpdate.php` — a mesma checagem de árvore limpa
- Refina: ADR-03

---

## ADR-05: Manter `team_id` como nome da coluna, apesar do vocabulário pt-BR

**Status**: Aceita
**Data**: 2026-08-13

### Contexto

O kit nomeia domínio em pt-BR (`AgenteIa`, `PapeisSeeder`, `organizacoes`). A coluna de tenant do spatie chama-se `team_id` por default, configurável em `permission.team_foreign_key` (`config/permission.php:113`).

### Decisão

Manter `team_id`. Só o model e a tabela do kit usam pt-BR (`Organizacao`, `organizacoes`).

### Alternativas Consideradas

1. **Renomear para `organizacao_id`** — descartada: a coluna é lida por código de vendor em vários pontos (migration, `PermissionRegistrar`, Shield), e cada ponto que não respeitar a config vira bug difícil de achar. O ganho é cosmético numa coluna que o desenvolvedor do projeto raramente escreve à mão.

### Consequências

- **Positivas**: zero divergência com o caminho testado do pacote.
- **Negativas**: uma coluna em inglês no meio de um schema em português. Documentado no mapeamento do PRD.

### Referências

- `config/permission.php:113`
- `01-plano-acao.md` — seção "Mapeamentos"

---

## ADR-06: A demo é opt-in e descartável

**Status**: Aceita
**Data**: 2026-08-13

### Contexto

O painel `/app` "nasce vazio de propósito — é aqui que seu projeto nasce" (`wikis/arquitetura.md`). Uma demo de tenancy precisa de um resource no `/app` para provar o isolamento, o que colide diretamente com essa promessa.

### Decisão

A demo entra só com `php artisan kit:tenancy --demo` e se resume a quatro arquivos removíveis (`Projeto` model, migration, `ProjetoResource`, `DemoTenancySeeder`). O comando imprime, ao final, quais arquivos apagar para removê-la.

### Alternativas Consideradas

1. **Demo sempre criada** — descartada: quebra a promessa do painel vazio e obriga todo projeto a apagar código antes de começar.
2. **Demo só nos testes** — descartada: valida o código, mas não deixa o usuário *ver* o isolamento funcionando, que é o pedido original ("vamos criar uma demo para validar").

### Consequências

- **Positivas**: prova visual do isolamento sem custo para quem não quer.
- **Negativas**: mais um caminho no comando e nos testes.

### Referências

- `01-plano-acao.md` — passo 11
