---
name: feature-wiki
version: 2.4.0
description: >
  Cria estrutura de documentação wiki para uma feature antes de implementá-la.
  Invoque SEMPRE ao iniciar implementação de qualquer feature nova.
  Cria pasta em wikis/specs/{branch} com 4 arquivos obrigatórios: plano de ação (PRD),
  decisões arquiteturais (ADR), tracking de progresso e casos de teste. O plano de ação
  deve ser minucioso o suficiente para um agente implementar sem ambiguidade.
  Inclui padrão de log obrigatório, channel por feature, etapa de pós-implementação,
  auditoria automática da wiki via /ponytail:ponytail-review, e integração com
  Caveman (comunicação terse) e Ponytail (execução minimalista).
---

# Feature Wiki — Documentação Antes de Implementar

## Glossário

| Sigla | Significado |
|-------|-------------|
| **PRD** | Product Requirements Document — plano de ação detalhado |
| **ADR** | Architecture Decision Record — registro de decisão arquitetural |
| **CT** | Caso de Teste — especificação de um cenário de teste |
| **DB** | Database — banco de dados |
| **FK** | Foreign Key — chave estrangeira |

## Índice

- [Quando Invocar](#quando-invocar)
- [Fluxo de Execução](#fluxo-de-execução)
  - [1. Descobrir Branch](#1-descobrir-branch-e-estrutura-de-pasta)
  - [2. Definir Nome da Feature](#2-definir-nome-da-feature)
  - [3. Pesquisa e Contexto](#3-pesquisa-e-contexto-obrigatório-antes-de-escrever)
  - [4. Criar os Arquivos](#4-criar-os-arquivos)
  - [5. Revisão Profunda Pós-Escrita](#5-revisão-profunda-pós-escrita-obrigatório)
  - [6. Auditoria da Wiki com Ponytail-review](#6-auditoria-da-wiki-com-ponytail-review-obrigatório)
  - [7. Pós-Implementação](#7-pós-implementação-obrigatório)
- [Arquivo 01: PRD](#arquivo-01-plano-de-ação-prd)
- [Padrão de Log](#padrão-de-log--classeétodo-mensagem)
- [Arquivo 02: ADR](#arquivo-02-decisões-arquiteturais)
- [Arquivo 03: Progresso](#arquivo-03-progresso--tracking)
- [Arquivo 04: Casos de Teste](#arquivo-04-casos-de-teste-ct)
- [Arquivos Extras](#arquivos-extras-conforme-necessidade)
- [Skills Companheiras](#skills-companheiras)
- [Checklist Final](#checklist-final-da-skill)

## Ordem de Leitura para o Agente Implementador

Ao implementar, o agente deve ler os arquivos nesta ordem:
1. **`01-plano-acao.md`** — entende o que fazer e em que ordem
2. **`04-casos-de-teste.md`** — entende como validar cada passo
3. **`02-decisoes-arquiteturais.md`** — entende as restrições e justificativas
4. **`03-progresso.md`** — marca o que já foi feito e retoma de onde parou

---

## Quando Invocar

- Sempre que o usuário pedir para implementar uma feature nova
- Ao iniciar qualquer card/ticket/task de desenvolvimento
- Antes de qualquer `php artisan make:*` ou criação de código
- Quando o usuário pedir para arquitetar ou analisar uma feature antes de implementá-la

### Quando NÃO Invocar

- **Typo fixes**: correções de texto, mensagens, labels
- **Mudanças triviais**: ajustes de config simples, tweak de CSS isolado, mudança de 1-2 linhas sem nova lógica
- **Refactoring puro**: renomear variáveis, extrair método, sem mudança de comportamento
- **Bump de dependência**: atualizar versão de package sem mudança de API
- **Adição de seeders/migrations isoladas**: sem lógica de negócio associada

> **Critério**: se a mudança não adiciona nova lógica de negócio, não altera fluxo de dados e não cria novos arquivos de código → não precisa de wiki.
> **Bug fix com nova lógica**: se o fix introduz nova regra de negócio, novo estado ou novo fluxo → invocar a wiki.

## Fluxo de Execução

### 1. Descobrir Branch e Estrutura de Pasta

```bash
git rev-parse --abbrev-ref HEAD
```

Branch `ferro/501` → pasta base: `wikis/specs/ferro/501/`
Branch `feature/user-auth` → pasta base: `wikis/specs/feature/user-auth/`
Branch `fix/boleto-juros` → pasta base: `wikis/specs/fix/boleto-juros/`

### 2. Definir Nome da Feature

Perguntar ao usuário (ou derivar do contexto):
- Nome deve ser `kebab-case`
- Deve descrever a feature, não o ticket
- Exemplos: `envio-progresso`, `unico-jobs-progress-tracking`, `api-webhook-payments`

Pasta final: `wikis/specs/{branch}/{feature-name}/`

### 3. Pesquisa e Contexto (OBRIGATÓRIO antes de escrever)

Antes de escrever qualquer documento:
- Usar `database-schema` se a feature envolve novas tabelas ou alterações
- Usar `search-docs` para tecnologias envolvidas (Filament, Livewire, Queue, etc.)
- Ler arquivos existentes relevantes com `Read` ou `Grep`
- Executar `php artisan model:show ModelName` para models relacionados
- Examinar padrões existentes com `Glob "**/[padrão]/**/*.php"`
- **Inspecionar APIs de terceiros** antes de escrever CTs — verificar vendor source ou docs oficiais para confirmar nomes de métodos, assinaturas e restrições de schema
- Para features médias/grandes: delegar o mapeamento amplo a um agent `Explore` e depois **confirmar os trechos críticos com `Read` direto** (linhas exatas, imports, assinaturas) — não confiar apenas no resumo do agent
- **Validar dados fornecidos pelo usuário** (CSV, listas, IDs) contra o banco via `database-query` — detectar divergências de título/chave, escolher chave estável (ID) para mapeamentos e documentar as divergências no plano
- **Verificar existência de factories** (`Glob "database/factories/{Model}*"`) e states disponíveis antes de escrever CTs; se não houver factory, especificar `Model::create([...])` no Setup Global
- **Confirmar padrões internos citados** no plano com grep/read (ex: seeder-em-migration, guards de environment, `$casts` property vs `casts()`) — citar `arquivo:linha` de referência no plano
- **Verificar rotas existentes** — `Grep` em `routes/web.php` e `routes/api.php` para evitar conflito de endpoints e entender naming conventions
- **Verificar Policies/Gates** — `Glob "app/Policies/*.php"` e `Grep` por `Gate::define` para entender o padrão de autorização do projeto
- **Verificar config files** — `Read` em `config/*.php` relevantes à feature (services, logging, queue, auth)
- **Verificar composer.json** — `Read` em `composer.json` para pacotes instalados que poderiam ser reutilizados em vez de criar do zero
- **Verificar wikis existentes** — `Glob "wikis/specs/**/*.md"` para features relacionadas que já foram documentadas e podem ter decisões relevantes
- **Verificar git log do branch** — `git log --oneline -20` para contexto do que já foi feito no branch
- **Verificar scheduled tasks** — `Grep` em `app/Console/Kernel.php` ou `routes/console.php` se a feature envolve cron/scheduling
- **Verificar eventos/listeners** — `Glob "app/Events/*.php"` e `Glob "app/Listeners/*.php"` se a feature emite ou escuta eventos
- **Verificar observers** — `Glob "app/Observers/*.php"` para hooks de model existentes
- **Verificar middleware** — `Grep` em `app/Http/Middleware/` e em `bootstrap/app.php` (Laravel 11+) para middleware stack
- **Verificar variáveis de ambiente** — `Read` em `.env.example` para chaves existentes e padrão de naming

### 4. Criar os Arquivos

Criar os **4 arquivos obrigatórios** + extras se necessário.

**Ordem de criação** (cada arquivo depende do anterior):
1. **`01-plano-acao.md`** — PRD é a base; tudo deriva dele
2. **`02-decisoes-arquiteturais.md`** — ADRs justificam escolhas do PRD
3. **`04-casos-de-teste.md`** — CTs validam os passos do PRD
4. **`03-progresso.md`** — espelha os passos do PRD (por isso é o último)

**Wiki já existente**: se `wikis/specs/{branch}/{feature}/` já existe:
- **Perguntar ao usuário** se deseja sobrescrever, incrementar (v2) ou retomar
- Se retomar: ler `03-progresso.md` para ver o que já foi feito e continuar de onde parou
- Se sobrescrever: backup manual pelo usuário antes de criar a nova (a skill não arquiva automaticamente)

### 5. Revisão Profunda Pós-Escrita (OBRIGATÓRIO)

Após escrever os 4 arquivos, **re-validar cada premissa do plano contra o código real** antes de apresentar ao usuário:

- Reler os pontos exatos citados no plano: imports dos arquivos a editar, assinaturas de métodos, relações de models, padrão das migrations-referência, factories/states usados nos CTs
- **Corrigir a wiki imediatamente** quando a revisão contradisser o plano (ex: plano diz "adicionar import X" → import já existe; plano cita guard genérico → padrão real é `! app()->environment('testing')`)
- Só então avançar para o step 6 (Auditoria da Wiki)

> Exemplo real (feature/implementar-carga-horaria): a revisão pós-escrita detectou que o import `MbaTrack` já existia no arquivo a editar e confirmou o padrão exato do guard de environment nas migrations com seeder — ambos corrigidos na wiki antes da implementação.

### 6. Auditoria da Wiki com Ponytail-review (OBRIGATÓRIO)

Após a revisão profunda (step 5), **invocar automaticamente** `/ponytail:ponytail-review` para auditar a wiki criada. Este step é função direta da skill — o agente NÃO deve esperar o usuário pedir.

**Por que auditar a wiki**: O plano de ação pode conter over-engineering — passos desnecessários, abstrações prematuras, complexidade que não agrega valor. A auditoria com Ponytail-review identifica esses pontos **antes** da implementação começar, economizando tempo de desenvolvimento.

**Como executar**:
1. Invocar `/ponytail:ponytail-review` apontando para os arquivos da wiki criada em `wikis/specs/{branch}/{feature}/`
2. Analisar cada sugestão de corte/simplificação retornada
3. **Aplicar as sugestões relevantes** diretamente nos arquivos da wiki:
   - Passos desnecessários → remover do `01-plano-acao.md` e `03-progresso.md`
   - Abstrações prematuras → simplificar ou marcar como YAGNI
   - Complexidade excessiva → quebrar em passos menores ou simplificar
   - Over-engineering em CTs → simplificar setup, reduzir mocks desnecessários
4. **Re-executar** `/ponytail:ponytail-review` se houver mudanças significativas (>3 arquivos alterados)
5. Só então apresentar ao usuário para aprovação / iniciar implementação

> **Importante**: Esta auditoria revisa o **plano** (a wiki), não o código implementado. A auditoria do código implementado acontece no step 7 (Pós-Implementação) e nos templates de Verificação Final.
>
> **Comando correto**: `/ponytail:ponytail-review` (com namespace `ponytail:`). NUNCA usar `/ponytail-review` sem o namespace — o comando não será encontrado.

### 7. Pós-Implementação (OBRIGATÓRIO)

Após a implementação ser concluída e testes passarem:

1. **Atualizar `03-progresso.md`**: marcar todos os checkboxes como `[x]`, adicionar data de conclusão
2. **Adicionar seção "Desvios do Plano"** em `03-progresso.md`: documentar onde a implementação divergiu do PRD e por quê (ex: "Passo 3 alterado: API retornava campo `uuid` em vez de `id` — ajustado mapeamento")
3. **Adicionar seção "Notas de Implementação"** em `03-progresso.md**: descobertas durante o código que não estavam no plano (ex: "Descoberto que `Enrollment::find()` aplica scope global de tenant — documentado em `02-decisoes-arquiteturais.md`")
4. **Linkar wiki ao PR**: incluir link da wiki na descrição do PR para rastreabilidade
5. **Retrospectiva breve**: anotar na wiki o que funcionou bem no planejamento e o que faltou — serve para melhorar futuras invocações da skill
6. **Limpeza de channel de log**: se a feature foi mergeada e está estável, considerar reduzir o level do channel de `debug` para `info` ou remover o channel se não for mais necessário

---

## Arquivo 01: Plano de Ação (PRD)

**Path**: `wikis/specs/{branch}/{feature}/01-plano-acao.md`

**Propósito**: PRD completo — deve ser detalhado o suficiente para um agente implementar sem ambiguidade.

**Obrigatório incluir**:
- Objetivo claro em 1-2 parágrafos
- Contexto e problema que resolve
- Análise dos arquivos/código existente que será tocado
- **Channel de log da feature** (ver seção "Padrão de Log" abaixo)
- **Autorização**: policies, gates, middleware, guards — quais serão criados/modificados
- **Rotas**: endpoints a registrar, middleware aplicado, naming convention
- **Variáveis de Ambiente**: `.env` keys necessárias, config publish, defaults
- **Eventos/Listeners/Observers**: se a feature emite ou escuta eventos, hooks de model
- **Jobs/Queues**: queue connection, timeout, retries, backoff — quando aplicável
- **Impacto em Features Existentes**: regression risk, o que pode quebrar
- **Rollback**: como reverter se algo der errado (migration down, feature flag, etc.)
- **Dependências**: composer/npm packages necessários, versões mínimas
- **Riscos**: áreas de incerteza, dependências externas, prazos apertados
- Passos de implementação numerados com:
  - Path exato de cada arquivo a criar/modificar
  - Assinatura de classes, métodos, interfaces
  - Lógica de negócio detalhada
  - **Logs em todas as etapas de execução** — cada passo que executa lógica deve especificar quais logs emitir (ver seção "Padrão de Log" abaixo)
  - Campos de DB com tipos, nullable, índices, constraints
  - Mapeamentos de campos (ex: API → DB)
  - Tratamento de erros esperados
- Skills a invocar em cada passo (ver lista abaixo)
- Referência ao `04-casos-de-teste.md` (não duplicar cenários aqui)
- Passos de verificação (pint, tests, artisan commands)
- Passos de commit (gitmoji + escopo + mensagem)

**Skills disponíveis para referenciar no PRD**:
```
- laravel-best-practices   → qualquer código PHP Laravel
- eloquent-best-practices  → models, queries, relacionamentos
- laravel-specialist       → Sanctum, queues, Livewire, API resources
- laravel-11-12-app-guidelines → features, bugs, UI
- pest-testing             → escrever/editar testes Pest
- tailwindcss-development  → qualquer Tailwind/Blade/UI
- livewire-development     → componentes Livewire
- ponytail                 → execução minimalista (escada de simplicidade)
```

> **Integração com Ponytail**: Após a wiki ser aprovada, o Ponytail deve ser a skill de execução ativa durante toda a implementação. Ele garante que cada passo do plano seja executado com o mínimo de código necessário (reutilização → stdlib → feature nativa → uma linha → mínimo que funciona). Após implementar, rodar `/ponytail:ponytail-review` no diff para validar contra over-engineering. Atalhos deliberados devem ser marcados com `ponytail:` comment. Ver o README do repositório para o passo a passo completo da integração.

**Template `01-plano-acao.md`**:
```markdown
# Plano de Ação — {Card}: {Título da Feature}

## Objetivo

{1-2 parágrafos descrevendo o que será implementado e por quê}

## Contexto

{Problema atual, limitações, por que essa feature é necessária}

## Análise dos Arquivos Existentes

### {NomeDoArquivo}
- {Descrição do que existe e como será afetado}

## Autorização

- **Policies**: {quais criar/modificar, métodos autorizados}
- **Gates**: {se aplicável}
- **Middleware**: {rotas protegidas por qual middleware}
- **Guards**: {se aplicável}

## Rotas

| Método | URI | Name | Middleware |
|--------|-----|------|------------|
| {GET/POST/...} | {/path} | {route.name} | {auth,can:...} |

## Variáveis de Ambiente

| Key | Default | Descrição |
|-----|---------|-----------|
| {FEATURE_KEY} | {default} | {o que controla} |

## Eventos / Listeners / Observers

- **Eventos emitidos**: {lista}
- **Listeners**: {lista}
- **Observers**: {model e métodos hooked}

## Jobs / Queues

- **Job**: {nome} → queue: {connection/name}, timeout: {s}, retries: {n}, backoff: {s}

## Impacto em Features Existentes

- {Feature X}: {o que pode quebrar e por quê}
- {Feature Y}: {dependência compartilhada}

## Rollback

- **Migration down**: {o que `down()` faz}
- **Feature flag**: {se aplicável, como desativar}
- **Reversão de dados**: {se aplicável, como reverter dados migrados}

## Dependências

- **Composer**: {package} {version}
- **NPM**: {package} {version}

## Riscos

- {Risco 1}: {mitigação}
- {Risco 2}: {mitigação}

## Channel de Log da Feature

### Verificação de Channel Existente

- Buscar em `config/logging.php` por channels já configurados
- Verificar se já existe um channel com nome relacionado à feature (ex: `feature-{nome}`, `{sistema}-{feature}`)
- Usar `Grep` em `config/logging.php` e em `app/` por referências a `Log::channel(`

### Decisão

- **Se channel existe**: referenciar no plano como `Log::channel('{nome}')` em todos os passos
- **Se não existe**: incluir como primeiro passo de implementação a criação do channel em `config/logging.php`, com:
  - Nome: `{feature-name}` (kebab-case, mesmo nome da pasta da feature)
  - Driver: `daily` (rotação automática)
  - Path: `storage/logs/{feature-name}.log`
  - Level: `debug` (para rastreabilidade completa durante desenvolvimento)
  - Exemplo de configuração:
    ```php
    '{feature-name}' => [
        'driver' => 'daily',
        'path' => storage_path('logs/{feature-name}.log'),
        'level' => 'debug',
        'days' => 14,
    ],
    ```

> **Por que agrupar por channel**: Logs de uma feature ficam isolados em arquivo próprio, facilitando debug, auditoria e remoção futura. Evita poluir o log principal do sistema com ruído de uma feature específica.

## Estrutura de Implementação

### 1. {Nome do Passo}

> Skills: `laravel-best-practices`, `pest-testing`

- **Path**: `app/...`
- {Detalhes de implementação}
- **Logs**:
  - `Log::channel('{feature-name}')->info('[{Classe}@{metodo}] {mensagem da ação} | {parametro principal}')`
  - Especificar cada ponto de log: início, sucesso, falha, decisões de fluxo

### 2. {Nome do Passo}
...

## Filosofia de Implementação

> **Ponytail ativo em modo `full`** durante toda a implementação.
> Cada passo deve aplicar a escada de simplicidade:
> 1. Reutilizar código existente antes de criar novo
> 2. Usar stdlib do PHP/Laravel antes de código custom
> 3. Usar features nativas antes de dependências
> 4. Uma linha quando possível
> 5. Mínimo código que funciona
>
> Atalhos deliberados devem ser marcados com `ponytail:` comment.
> Após implementação, rodar `/ponytail:ponytail-review` no diff.
>
> **Caveman ativo em modo `full`** na comunicação agent ↔ usuário.
> Arquivos wiki (01-05) são boundary do Caveman — escrever em prosa normal.
> Código, commits e PRs também são boundary do Caveman.

## Mapeamentos

{Tabelas de mapeamento de campos, status, etc. — quando aplicável}

## Testes

> Ver `04-casos-de-teste.md` para especificação completa dos cenários.

## Verificação Final
- [ ] `/ponytail:ponytail-review` no diff (validar contra over-engineering)
- [ ] `vendor/bin/pint --dirty`
- [ ] `php artisan test --compact --filter={Feature}`
- [ ] {outros comandos de verificação específicos}

## Commits
- `{gitmoji} {escopo}: {mensagem}`
- `:memo: {escopo}: wiki da feature {nome}`
```

---

## Padrão de Log — `[Classe@Método] mensagem`

### Por que este padrão

O formato `[Classe@Método] mensagem` é obrigatório em **todos os logs** do projeto. Ele resolve três problemas:

1. **Rastreabilidade**: ao ler um log, sabe-se imediatamente qual classe e método o gerou — sem precisar buscar no código
2. **Filtragem**: permite `grep` por classe ou método para isolar fluxos específicos
3. **Consistência**: padroniza a leitura em qualquer nível (info, warning, error) e em qualquer channel

### Formato Obrigatório

```
[{Classe}@{Método}] {mensagem descritiva} | {parâmetro principal}: {valor} - {contexto adicional}
```

### Anatomia da Mensagem

| Parte | Descrição | Exemplo |
|-------|-----------|---------|
| `{Classe}` | Nome da classe (sem namespace) | `AddUserToClassJob` |
| `{Método}` | Nome do método que está logando | `processAddUserToClass` |
| `{mensagem}` | Descrição clara da ação executada | `Membro associado com sucesso` |
| `{parâmetro}` | ID, status, ou valor principal manipulado | `enrollment: 280114` |
| `{contexto}` | Informação adicional relevante (opcional) | `evento: enrollment.requested` |

### Regras de Escrita

1. **Prefixo sempre entre colchetes**: `[Classe@Método]` — sem espaços dentro dos colchetes
2. **Mensagem em português**: descrever a **ação executada**, não o estado (use "Membro associado com sucesso" em vez de "Membro foi associado")
3. **Pipe `|` como separador**: entre a mensagem e os parâmetros/contexto
4. **Hífen `-` como separador secundário**: entre múltiplos parâmetros de contexto
5. **Incluir parâmetro principal sempre que possível**: IDs, slugs, status — valores que identificam o registro manipulado
6. **Nível de log apropriado**:
   - `debug` → detalhe intermediário para rastreabilidade
   - `info` → sucesso de operação esperada
   - `notice` → evento significativo mas normal (ex: queue retry agendado)
   - `warning` → condição anormal mas não fatal — **usar em `fail()` de Livewire**, fallback, retry, dado ausente
   - `error` → falha que interrompe o fluxo — **usar em `catch` de exceptions** que quebram a execução
   - `critical` → erro de sistema que exige intervenção imediata (ex: DB inacessível, API crítica fora do ar)
   - `emergency` → sistema indisponível, intervenção humana urgente
7. **Máximo de contexto estruturado**: SEMPRE passar o segundo parâmetro `array $context` do Laravel com todos os dados relevantes — IDs, payloads, snapshots de estado, dados do modelo (ver seção "Contexto Estruturado" abaixo)
8. **Exceptions no contexto**: ao logar uma exception, incluir `'exception' => $e` no array de contexto — o Laravel serializa automaticamente stack trace, mensagem e código
9. **Nível do log = severidade da ação**: `fail()` → `warning`; `catch` de exception que interrompe → `error`; `catch` de exception tratada/ignorada → `warning`

### Contexto Estruturado (array `$context`)

O Laravel aceita um segundo parâmetro `array $context` em todos os métodos de log. **Sempre usar** — é onde vai o máximo de informação estruturada para debug e auditoria.

#### O que incluir no context

- **IDs**: todos os IDs relacionados ao fluxo (`user_id`, `enrollment_id`, `turma_id`, `job_id`)
- **Payloads**: dados de entrada que dispararam a ação (`payload`, `request_data`, `webhook_data`)
- **Snapshots de estado**: valores antes/depois de alterações (`before`, `after`)
- **Dados do modelo**: atributos relevantes do model manipulado (`attributes`, `changes`)
- **Exception**: `'exception' => $e` — o Laravel serializa stack trace, mensagem e código automaticamente
- **Contexto de execução**: `queue`, `attempt`, `connection` em jobs; `route`, `ip` em controllers
- **Decisões de fluxo**: `reason`, `condition`, `skip_reason` para branches tomados

#### Exemplo de context rico

```php
Log::channel('feature-name')->info(
    '[AddUserToClassJob@processAddUserToClass] Membro associado com sucesso | enrollment: 280114',
    [
        'enrollment_id' => 280114,
        'user_id'       => 123,
        'turma_id'      => 456,
        'evento'        => 'enrollment.requested',
        'payload'       => $request->all(),
        'attributes'    => $enrollment->getAttributes(),
        'changes'       => $enrollment->getChanges(),
    ]
);
```

> **Regra de ouro**: se a informação pode ajudar a reproduzir ou diagnosticar o problema, vai no `context`. Melhor ter informação demais que de menos.

### Exemplos Práticos

```php
// Início de processamento
Log::channel('feature-name')->info('[AddUserToClassJob@handle] Iniciando adição do usuário | user_id: 123 - turma_id: 456', [
    'user_id'  => 123,
    'turma_id' => 456,
    'attempt'  => 1,
    'queue'    => 'default',
]);

// Sucesso com contexto
Log::channel('feature-name')->info('[AddUserToClassJob@processAddUserToClass] Membro associado com sucesso | enrollment: 280114 - evento: enrollment.requested', [
    'enrollment_id' => 280114,
    'user_id'       => 123,
    'turma_id'      => 456,
    'evento'        => 'enrollment.requested',
    'changes'       => $enrollment->getChanges(),
]);

// Criação de recurso externo
Log::channel('feature-name')->info('[CreateCurseducaUserJob@createCurseducaAccount] Conta criada com sucesso | aluno_id: 789', [
    'aluno_id'        => 789,
    'external_id'     => $response->json('id'),
    'response_status' => $response->status(),
]);

// Webhook recebido
Log::channel('feature-name')->info('[UnicoWebhookController@handle] Webhook recebido | evento: enrollment.requested', [
    'evento'  => 'enrollment.requested',
    'payload' => $request->all(),
    'ip'      => $request->ip(),
    'route'   => $request->path(),
]);

// Condição de fluxo — warning (dado ausente, fallback, retry)
Log::channel('feature-name')->warning('[ProcessCurseducaAccountCreationJob@handle] Usuário já existe, pulando criação | aluno_id: 789', [
    'aluno_id'   => 789,
    'skip_reason'=> 'user_already_exists',
    'existing_id'=> $existingUser->id,
]);

// fail() de Livewire — warning (fluxo interrompido pelo usuário, não é erro de sistema)
Log::channel('feature-name')->warning('[CreateEnrollmentForm@submit] Validação falhou | user_id: 123', [
    'user_id'    => 123,
    'errors'     => $this->getErrorBag()->toArray(),
    'input'      => $this->form->toArray(),
]);

// catch de exception que interrompe o fluxo — error
Log::channel('feature-name')->error('[AddUserToClassJob@processAddUserToClass] Falha ao associar membro | enrollment: 280114', [
    'enrollment_id' => 280114,
    'exception'     => $e,  // Laravel serializa stack trace + mensagem + código
    'attempt'       => $this->attempts(),
    'payload'       => $this->payload,
]);

// catch de exception tratada/ignorada — warning (não quebra o fluxo)
Log::channel('feature-name')->warning('[SyncEnrollmentsJob@handle] Erro ao sincronizar um item, continuando | enrollment: 280114', [
    'enrollment_id' => 280114,
    'exception'     => $e,
    'will_retry'    => true,
]);

// Erro crítico de sistema — critical
Log::channel('feature-name')->critical('[ProcessCurseducaAccountCreationJob@handle] API Curseduca indisponível | tentativa: 3', [
    'attempt'       => 3,
    'exception'     => $e,
    'api_endpoint'  => config('services.curseduca.url'),
    'queue'         => 'default',
]);
```

### Como Implementar no Plano

Para **cada passo de implementação** no PRD, especificar:

1. **Quais métodos terão logs** — listar cada método e os pontos exatos (início, sucesso, falha, decisão de fluxo, catch de exception, fail de validação)
2. **Qual channel usar** — `Log::channel('{feature-name}')` em todos os logs da feature
3. **Qual nível** — debug/info/notice/warning/error/critical conforme a regra de severidade:
   - `fail()` de Livewire → `warning`
   - `catch` de exception que **interrompe** o fluxo → `error`
   - `catch` de exception **tratada/ignorada** → `warning`
   - Sistema/API indisponível → `critical`
4. **Qual mensagem** — já escrever a string completa no plano, seguindo o formato
5. **Qual context** — listar o array de contexto com todos os campos relevantes (IDs, payloads, snapshots, `exception`)

> **Anti-padrão**: NUNCA usar `Log::info('...')` sem channel — sempre especificar o channel da feature.
> **Anti-padrão**: NUNCA usar mensagens genéricas como "Processando..." ou "Erro ocorrido" — sempre incluir `[Classe@Método]` e parâmetro principal.
> **Anti-padrão**: NUNCA logar apenas em `catch` — logar também no sucesso e nos pontos de decisão de fluxo.
> **Anti-padrão**: NUNCA passar context vazio — incluir o máximo de informação estruturada possível (IDs, payloads, snapshots, exception).
> **Anti-padrão**: NUNCA usar `error` para `fail()` de validação — `fail()` é uma condição esperada de interrupção, usar `warning`.
> **Anti-padrão**: NUNCA usar `info` para exceptions — exceptions são anomalias, usar no mínimo `warning` (tratada) ou `error` (interrompe).

### Contexto Compartilhado (`Log::shareContext`)

Para contexto que se propaga automaticamente em **todos** os logs da requisição/job (correlation ID, user ID, request ID):

```php
// No início do lifecycle (middleware, job boot, service provider)
Log::shareContext([
    'correlation_id' => Str::uuid()->toString(),
    'user_id'        => Auth::id(),
    'request_uri'    => request()->path(),
]);

// Todos os logs subsequentes incluem automaticamente esses campos
Log::channel('feature-name')->info('[Controller@handle] Processando requisição');
// → context mesclado: ['correlation_id' => '...', 'user_id' => 123, 'request_uri' => '...', ...]
```

> **Quando usar**: em jobs longos, webhooks, fluxos multi-etapas onde o mesmo ID precisa aparecer em todos os logs para rastreabilidade.

### Driver JSON em Produção

O channel `daily` gera arquivos de texto. Para parsing estruturado em produção (ELK, Datadog, Grafana), trocar o driver para `json`:

```php
'{feature-name}' => [
    'driver' => 'daily',
    'path'   => storage_path('logs/{feature-name}.log'),
    'level'  => env('LOG_LEVEL', 'debug'),
    'days'   => 14,
    'replace_placeholders' => true,
],
```

> O Laravel 11+ já formata context como JSON automaticamente quando o handler suporta. Para garantir, usar `'driver' => 'json'` ou configurar o handler do channel.

### Testando Logs em Pest

Para verificar que os logs foram emitidos corretamente nos CTs:

```php
// Spy — verifica que foi chamado sem bloquear
Log::spy();

it('emite log de sucesso ao associar membro', function () {
    Log::shouldReceive('channel')
        ->once()
        ->with('feature-name')
        ->andReturn(Mockery::self());

    Log::channel('feature-name')
        ->shouldReceive('info')
        ->once()
        ->with('[AddUserToClassJob@processAddUserToClass] Membro associado com sucesso | enrollment: 280114', \Mockery::on(fn ($context) => $context['enrollment_id'] === 280114));

    // ... executar ação
});

// Alternativa mais simples — Log::spy() captura tudo
it('emite log no channel correto', function () {
    Log::spy();

    // ... executar ação

    Log::shouldHaveReceived('channel')
        ->with('feature-name')
        ->atLeast()
        ->once();
});
```

> **Incluir CTs de log** no `04-casos-de-teste.md` para validar: channel correto, nível correto, mensagem no formato `[Classe@Método]`, e context com campos esperados.

### Trait UnicoLogging (se aplicável)

Se o projeto possuir uma trait de logging (ex: `UnicoLogging`), verificar:

- `Grep` por `trait UnicoLogging` ou `trait.*Logging` em `app/`
- Se existir, usar a trait nos classes da feature — ela formata automaticamente o prefixo `[Classe@Método]`
- Se não existir, implementar o formato manualmente via `Log::channel(...)->info('[Classe@metodo] ...')`
- Documentar no plano qual abordagem será usada

---

## Arquivo 02: Decisões Arquiteturais (ADR)

**Path**: `wikis/specs/{branch}/{feature}/02-decisoes-arquiteturais.md`

**Propósito**: Registrar o "porquê" das escolhas — não o "o quê". Usa formato **ADR (Architecture Decision Record)** para padronizar e facilitar consulta futura.

**Incluir**:
- Cada decisão não-óbvia com justificativa
- Alternativas consideradas e por que foram descartadas
- Trade-offs aceitos
- Restrições externas (APIs, limites de parceiros, compliance)
- Padrões reutilizados de outras partes do sistema
- Link entre decisões relacionadas (ex: "Refine ADR-01")
- Decisões overridden (quando uma ADR substitui outra)

**Template `02-decisoes-arquiteturais.md`**:
```markdown
# Decisões Arquiteturais — {Card}

## ADR-01: {Título da Decisão}

**Status**: Aceita | Proposta | Deprecada
**Data**: {YYYY-MM-DD}

### Contexto
{Por que esta decisão é necessária — problema, restrições, pressões}

### Decisão
{O que foi decidido — a escolha feita}

### Alternativas Consideradas
1. {Alternativa A} — {por que foi descartada}
2. {Alternativa B} — {por que foi descartada}

### Consequências
- **Positivas**: {benefícios da decisão}
- **Negativas**: {trade-offs aceitos}
- **Riscos**: {riscos introduzidos e mitigações}

### Referências
- {arquivo:linha ou link relacionado}
- Refine: ADR-{xx} (se aplicável)

---

## ADR-02: {Título da Decisão}
...
```

---

## Arquivo 03: Progresso / Tracking

**Path**: `wikis/specs/{branch}/{feature}/03-progresso.md`

**Propósito**: Checklist de implementação para rastrear o que foi feito e retomar de onde parou.

**Estrutura**: Seções com checkboxes `- [ ]` agrupadas pelos mesmos passos do `01-plano-acao.md`. Atualizar os checkboxes **em tempo real** durante a implementação — não em lote no final.

**Validação de espelho**: verificar que a estrutura de seções do `03-progresso.md` espelha exatamente os passos do `01-plano-acao.md` — se o plano tem 8 passos, o progresso tem 8 seções correspondentes.

**Template `03-progresso.md`**:
```markdown
# Progresso — {Card}

## {Seção 1 do Plano}
- [ ] {Item 1}
- [ ] {Item 2}

## {Seção 2 do Plano}
- [ ] {Item 1}

## Testes
- [ ] `{NomeDoTesteTest}` — CT-01, CT-02, CT-03

## Verificação Final
- [ ] `/ponytail:ponytail-review` no diff (validar contra over-engineering)
- [ ] `vendor/bin/pint --dirty`
- [ ] `php artisan test --compact --filter={Feature}`
- [ ] `git commit`

## Blockers
<!-- Impedimentos encontrados durante implementação -->
- [ ] {Blocker 1}: {descrição + o que está sendo feito para resolver}

## Desvios do Plano
<!-- Onde a implementação divergiu do PRD e por quê -->
- {Passo X alterado}: {motivo}

## Notas de Implementação
<!-- Descobertas durante o código que não estavam no plano -->
- {Descoberta 1}: {impacto e onde foi documentado}

## Retrospectiva
<!-- O que funcionou bem no planejamento e o que faltou -->
- **Funcionou bem**: {ponto positivo}
- **Faltou no plano**: {ponto de melhoria para próxima wiki}
```

---

## Arquivo 04: Casos de Teste (CT)

**Path**: `wikis/specs/{branch}/{feature}/04-casos-de-teste.md`

**Propósito**: Especificação completa de cada caso de teste — setup, mocks, dados de entrada e assertions esperadas. Deve ser detalhado o suficiente para um agente escrever os testes sem ambiguidade.

**Por que este arquivo existe**: Escrever os CTs *antes* da implementação força a pesquisa das APIs envolvidas (métodos reais, restrições de schema, contratos de terceiros) na fase de planejamento — não na fase de debug. Previne surpresas como nomes de método incorretos, FKs obrigatórias em fixtures ou restrições de schema de bibliotecas externas.

**Obrigatório incluir por cenário**:
- ID único (`CT-01`, `CT-02`, ...)
- Tipo: `Feature` ou `Unit`
- Path do arquivo de teste
- Nome do método `it()`
- Precondições (estado do DB, factories, mocks ativos)
- Dados de entrada (payload, CSV row, argumentos)
- Resultado esperado (assertions específicas)
- **Estratégia de DB**: `RefreshDatabase` (isola entre tests) vs `DatabaseTransactions` (mais rápido, não recria schema) — especificar qual e por quê
- **`withoutExceptionHandling()`**: para CTs que precisam validar exception exata (tipo, mensagem, código) — caso contrário o Laravel converte para HTTP error

**Cobertura esperada**: todos os métodos públicos das classes da feature devem ter pelo menos 1 CT. Métodos com branches (if/else, switch) devem ter CTs para cada branch.

**CTs de Autorização** (quando aplicável):
- Usuário sem permissão → `403 Forbidden`
- Usuário não autenticado → `401 Unauthorized` ou redirect
- Policy denial → `403` com mensagem esperada

**CTs de Log** (quando aplicável):
- Verificar que log foi emitido no channel correto
- Verificar nível correto (info/warning/error)
- Verificar formato da mensagem `[Classe@Método]`
- Verificar context com campos esperados
- Usar `Log::spy()` ou `Log::shouldReceive()`

**Data Providers (Pest)**: para testar múltiplos inputs no mesmo CT, usar `dataset()`:
```php
dataset('valid_enrollments', [
    ['status' => 'active', 'expected' => true],
    ['status' => 'pending', 'expected' => true],
    ['status' => 'cancelled', 'expected' => false],
]);

it('valida enrollment status', function (string $status, bool $expected) {
    // ... assertion com $expected
})->with('valid_enrollments');
```

**Setup Global**: documentar uma vez as factories/mocks comuns a vários CTs.

**Template `04-casos-de-teste.md`**:
```markdown
# Casos de Teste — {Card}: {Título da Feature}

## Setup Global

### Factories / Fixtures
- `{Model}::factory()->{state}()->create([...])` — {descrição}
- ...

### Estratégia de Mock
- `{ExternalService}`: Mockery via `app()->instance()` — métodos: `{método}()`
- `Queue::fake()` — verificar dispatch de `{JobClass}`
- `Http::fake()` — endpoints: `{url}`
- `Log::spy()` — verificar logs emitidos (channel, nível, mensagem, context)

### Estratégia de DB
- `RefreshDatabase` — {justificativa se isolamento total é necessário}
- `DatabaseTransactions` — {justificativa se performance é crítica}
- Seeders: {quais rodar no setup}

---

## CT-01: {Nome do Cenário — Happy Path}

**Tipo**: `Feature` | `Unit`
**Arquivo**: `tests/{Feature|Unit}/{Path}/{NomeTest}.php`
**Método**: `it('{descrição legível}')`

### Precondições
- {O que deve existir no DB antes — model + estado}
- {Mocks a configurar}

### Dados de Entrada
```
{input: payload, CSV row, argumentos de método, etc.}
```

### Resultado Esperado
- `{Model}` criado/atualizado com `{campo}` = `{valor}`
- `{Job}` despachado com `{argumento}` = `{valor}`
- `{Relacionamento}` existe com `{n}` registros

---

## CT-02: {Nome do Cenário — Falha / Edge Case}

**Tipo**: `Feature`
**Arquivo**: `tests/Feature/...`
**Método**: `it('{descrição}')`

### Precondições
- {Estado inicial que causa a falha}
- `withoutExceptionHandling()` — {para validar exception exata}

### Dados de Entrada
```
{input inválido ou condição de borda}
```

### Resultado Esperado
- Lança `{ExceptionClass}` com mensagem `"{texto}"`
- `{Model}` NÃO criado (`{Model}::count()` = 0)
- `{Job}` NÃO despachado
- Log `warning` emitido com `{campos do context}`

---

## CT-03: {Nome do Cenário — Autorização}

**Tipo**: `Feature`
**Arquivo**: `tests/Feature/...`
**Método**: `it('{descrição}')`

### Precondições
- Usuário sem permissão `{policy}`
- {Mocks a configurar}

### Dados de Entrada
```
{request para endpoint protegido}
```

### Resultado Esperado
- HTTP `403 Forbidden`
- `{Model}` NÃO modificado

---

## CT-04: {Nome do Cenário — Log Emitido}

**Tipo**: `Unit`
**Arquivo**: `tests/Unit/...`
**Método**: `it('{descrição}')`

### Precondições
- `Log::spy()` ativo

### Dados de Entrada
```
{ação que dispara o log}
```

### Resultado Esperado
- `Log::shouldHaveReceived('channel')` com `'{feature-name}'`
- `shouldHaveReceived('info')` com mensagem `[Classe@Método] ...`
- Context contém `{campo}` = `{valor}`

---

## Índice de Casos

| ID | Cenário | Tipo | Arquivo |
|----|---------|------|---------|
| CT-01 | {happy path} | Feature | `tests/...` |
| CT-02 | {falha X} | Feature | `tests/...` |
| CT-03 | {autorização} | Feature | `tests/...` |
| CT-04 | {log emitido} | Unit | `tests/...` |
| CT-05 | {edge case Y} | Unit | `tests/...` |
```

---

## Arquivos Extras (conforme necessidade)

Criar apenas quando a feature exige:

| Arquivo | Quando criar |
|---------|-------------|
| `05-design.md` | Feature com UI significativa (Filament, Livewire, Blade) |
| `05-api-contract.md` | Feature com API externa (payloads, endpoints, autenticação) |
| `05-db-schema.md` | Feature com schema complexo (múltiplas tabelas, migrations em cadeia) |
| `05-fluxo.md` | Feature com fluxo multi-etapas (queues + jobs + callbacks) |
| `05-rollback.md` | Feature com migrations destrutivas ou mudanças de schema irreversíveis |
| `05-performance.md` | Feature com volume alto (batch processing, relatórios, imports de CSV) |
| `05-security.md` | Feature com dados sensíveis (PII, pagamentos, autenticação, LGPD) |

---

## Checklist Final da Skill

Antes de encerrar a invocação:

### Planejamento
- [ ] Branch lida e estrutura de pasta criada
- [ ] Wiki existente verificada (retomar/sobrescrever/incrementar se já existe)
- [ ] Nome da feature confirmado com usuário
- [ ] Pesquisa feita (`search-docs`, `database-schema`, leitura de arquivos)
- [ ] Rotas, policies, config, composer, wikis existentes verificados
- [ ] APIs de terceiros inspecionadas (vendor source ou docs) — métodos e schema confirmados
- [ ] Dados fornecidos pelo usuário validados contra o DB (quando aplicável)
- [ ] Factories confirmadas (existência + states) para todos os CTs

### Documentação
- [ ] `01-plano-acao.md` escrito com passos numerados + skills referenciadas + logs em todas as etapas
- [ ] `01-plano-acao.md` inclui seções: Autorização, Rotas, Variáveis de Ambiente, Eventos, Jobs, Impacto, Rollback, Dependências, Riscos
- [ ] `02-decisoes-arquiteturais.md` escrito em formato ADR (Status, Contexto, Decisão, Alternativas, Consequências)
- [ ] `03-progresso.md` escrito com checkboxes espelhando o plano + seções Blockers, Desvios, Notas, Retrospectiva
- [ ] `04-casos-de-teste.md` escrito com todos os CTs identificados (happy path, falha, autorização, log)
- [ ] `04-casos-de-teste.md` inclui estratégia de DB, data providers e cobertura esperada
- [ ] Arquivos extras (`05-*`) criados se necessário (rollback, performance, security)

### Log
- [ ] Channel de log da feature verificado/criado e referenciado em todos os passos do PRD
- [ ] Padrão de log `[Classe@Método] mensagem` especificado em cada passo de execução do PRD
- [ ] Context estruturado (array `$context`) especificado em cada log do PRD
- [ ] CTs de log incluídos no `04-casos-de-teste.md`

### Validação
- [ ] Revisão profunda pós-escrita executada — premissas do plano re-validadas contra o código
- [ ] **Auditoria da wiki executada** — `/ponytail:ponytail-review` invocado e sugestões aplicadas
- [ ] `03-progresso.md` espelha exatamente os passos do `01-plano-acao.md`
- [ ] Filosofia de Implementação (Ponytail) incluída no PRD
- [ ] Confirmar com usuário se o plano está correto antes de implementar

### Pós-Implementação (após merge)
- [ ] `03-progresso.md` atualizado com checkboxes `[x]` + data de conclusão
- [ ] Desvios do plano e notas de implementação documentados
- [ ] Wiki linkada no PR
- [ ] Retrospectiva breve escrita
- [ ] Channel de log ajustado (level reduzido ou removido)

## Skills Companheiras

A feature-wiki faz parte de um trio de skills que cobrem o ciclo completo de desenvolvimento:

| Camada | Skill | Responsabilidade | Boundary |
|--------|-------|------------------|----------|
| **Comunicação** (agent ↔ usuário) | [Caveman](https://github.com/JuliusBrussee/caveman) | Prosa terse — corta ~75% dos tokens removendo fluff, artigos, fillers | **NÃO aplica em arquivos wiki** (01-05), código, commits, PRs |
| **Planejamento** (estrutura de documentação) | feature-wiki | PRD + ADR + CTs + tracking + padrão de log | Arquivos wiki são detalhados por design — compressão cria ambiguidade |
| **Execução** (código) | [Ponytail](https://github.com/DietrichGebert/ponytail) | Mínimo código que funciona — escada de simplicidade | Não corta validação, segurança, tratamento de erros |

### Caveman + feature-wiki: fronteira clara

O Caveman tem uma regra de **Auto-Clarity** que desativa o modo terse em situações críticas (security warnings, irreversible actions, multi-step sequences). Mas isso é implícito — a feature-wiki torna explícito:

> **Arquivos wiki são boundary do Caveman.**
>
> - `01-plano-acao.md` — PRD precisa ser "minucioso o suficiente para um agente implementar sem ambiguidade". Compressão destrói essa propriedade.
> - `02-decisoes-arquiteturais.md` — ADR é argumentativo por natureza (Contexto, Decisão, Alternativas, Consequências). Fragmentos perdem o raciocínio.
> - `03-progresso.md` — Checklists e descrições de blockers/desvios precisam de clareza.
> - `04-casos-de-teste.md` — CTs já são estruturados (tabelas, code blocks), mas a prosa explicativa entre eles não deve ser comprimida.
> - `05-*.md` — Arquivos extras (rollback, performance, security) são críticos e não podem ser ambíguos.

**Onde Caveman é bem-vindo**:
- Conversa agent ↔ usuário durante a sessão de planejamento
- Resumos de progresso ("CT-01 passou, CT-02 falha em assertion X")
- Perguntas e confirmações ("Confirmar nome da feature: X?")
- Respostas a dúvidas rápidas durante a implementação

**Onde Caveman NÃO se aplica** (já definido pelo próprio Caveman):
- Código/commits/PRs: "write normal"
- Security warnings e irreversible action confirmations

### Como ativar o trio

```bash
# 1. feature-wiki (via Laravel Boost)
php artisan boost:add-skill gsferro/laravel-ai-skills
php artisan boost:update

# 2. Ponytail (escolha um agente)
# Claude Code:
#   /plugin marketplace add DietrichGebert/ponytail
#   /plugin install ponytail@ponytail
# Windsurf:
#   curl -o .windsurf/rules/ponytail.md https://raw.githubusercontent.com/DietrichGebert/ponytail/main/.windsurf/rules/ponytail.md

# 3. Caveman (escolha um agente)
# Claude Code:
#   /plugin marketplace add JuliusBrussee/caveman
#   /plugin install caveman@caveman
# Windsurf:
#   curl -o .windsurf/rules/caveman.md https://raw.githubusercontent.com/JuliusBrussee/caveman/main/.windsurf/rules/caveman.md
```

Sessão com o trio ativo: `caveman full` + `ponytail full` + `feature-wiki` → resposta curta + diff curto + plano detalhado.

---

## Exemplo de Estrutura Criada

```text
wikis/
└── specs/
    └── ferro/
        └── 579/
            └── relatorio-mba-lote/
                ├── 01-plano-acao.md
                ├── 02-decisoes-arquiteturais.md      ← formato ADR
                ├── 03-progresso.md                   ← + Blockers, Desvios, Retrospectiva
                ├── 04-casos-de-teste.md              ← + CTs de log e autorização
                ├── 05-api-contract.md                ← extra quando necessário
                └── 05-rollback.md                    ← extra quando necessário
```
