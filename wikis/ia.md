# Camada de IA

> Skill obrigatória ao mexer aqui: **`ai-sdk-development`** (o SDK oficial `laravel/ai`). Ela cobre a API; este documento cobre o que o **kit** faz por cima dela.

## O princípio: o agente é dado, não código

O "paper" do agente — instruções, provider, modelo, temperatura, allowlist de tools e guardrails — mora na tabela **`agentes_ia`** e é editável em `/admin` **sem deploy**. O código traz só o comportamento.

```
agentes_ia (dado)                        App\Ai (código)
├── slug            ─────────────────┐   ├── Agents/AgenteBase   ← lê o registro pelo slug
├── instrucoes  (system prompt)      └──▶├── Agents/Assistente   ← agente concreto
├── provider / modelo / temperatura      ├── Guardrails/…        ← os middlewares
├── tools       (allowlist)              ├── Middleware/…        ← budget e auditoria
├── guardrails  (lista de chaves)        ├── Listeners/…         ← ledger
└── ativo / versao                       └── Health/LocalAiCheck ← check de IA local
```

**Regra de ouro:** validação de regra de negócio **nunca** passa por um agente. Guardrails e escopo de dados são determinísticos; o modelo redige, não decide.

## `AgenteBase`

`app/Ai/Agents/AgenteBase.php`. Todo agente do kit a estende e implementa apenas `slug()`. Herda:

| Comportamento | Detalhe |
|---|---|
| Registro do catálogo | `agente()` busca por `slug`, memoizado por instância (um turno com tools faria várias queries) |
| Instruções | vêm de `agentes_ia.instrucoes` |
| Provider / modelo / timeout | do registro; `null` cai no default de `config/ai.php`. Timeout em `AI_AGENT_TIMEOUT` (180s — o default de 60s do SDK não sobrevive a inferência local em CPU) |
| Guardrails | resolvidos do registro, precedidos do `BudgetGuardMiddleware` |
| Execução | `Promptable` do SDK: `prompt()`, `stream()`, `queue()`, e `::fake()` / `assertPrompted()` nos testes |

### Fail-closed, em três pontos

1. **Agente ausente do catálogo** → `RuntimeException`.
2. **Agente sem guardrails declarados** → `AgenteSemGuardrailsException`. Um agente que sobe sem guardrail chega cru ao provider; o momento de descobrir isso é a primeira execução, não a auditoria.
3. **Guardrail declarado que não existe no registry** → `InvalidArgumentException`. Um typo no paper viraria agente desprotegido em silêncio.

E, no `Assistente`, um quarto: agente **inativo** → `AgenteInativoException`, para o consumidor degradar com indisponibilidade honesta em vez de resposta inventada.

## Guardrails

`app/Ai/Guardrails/GuardrailRegistry.php` é o contrato estável entre o dado e o código: o paper declara **nomes**, o registry resolve **classes**.

| Chave em `agentes_ia.guardrails` | Classe | O que faz |
|---|---|---|
| `prompt_injection` | `PromptInjectionGuardMiddleware` | heurística determinística contra injeção |
| `prompt_guard_local` | `GarantirPromptSeguroMiddleware` | classificador local (1 inferência) |
| `pii_redactor` | `PiiRedactorMiddleware` | mascara PII no que sai da aplicação |
| `filtro_saida_sensivel` | `FiltroSaidaSensivelMiddleware` | filtra a resposta |

Ordem típica no paper: determinísticos (de graça) → classificador local (custa uma inferência) → PII → filtro de saída.

Além deles, dois middlewares que **não** vêm do catálogo:

- **`BudgetGuardMiddleware`** — sempre primeiro, pré-flight. Lê o mesmo cap mensal do `fomvasss/laravel-ai-tasks` (`ai-tasks.budgets`) e o mesmo somatório de `ai_runs.cost`. Sem cap configurado é no-op, zero query.
- **`AiAuditMiddleware`** — no `Assistente`, sempre **por último**, para que o prompt registrado já esteja mascarado pelo redator de PII.

### Adicionar um guardrail

1. Crie o middleware com `handle(AgentPrompt $prompt, Closure $next)`.
2. Registre a chave em `GuardrailRegistry::MAPA`.
3. Adicione a chave na coluna `guardrails` do agente (painel admin ou seeder).

## Tools

O mapa de fábricas no agente é **o que existe no código**; a coluna `agentes_ia.tools` é **a allowlist do que está liberado**. Só o que está nos dois roda — liberar uma tool é editar um registro, não fazer deploy.

O kit nasce **sem tool nenhuma**, de propósito (não há domínio a consultar ainda). Para registrar a sua, em `Assistente::tools()`:

```php
$fabricas = [
    'minha_tool' => fn (): MinhaTool => new MinhaTool($this->user),
];
```

e acrescente `minha_tool` em `agentes_ia.tools`.

> **Perfil assistente = zero escrita.** A tool deve recuperar **somente** o que o usuário logado pode ver, e o filtro de permissão mora **na query da tool**, nunca na instrução do modelo. O `User` é explícito no construtor porque o agente roda fora do ciclo de request (jobs, streaming) e não pode depender de `auth()`.

## Ledger de execuções

`app/Ai/Listeners/RegistrarAiRun.php`, ligado em `KitServiceProvider::configureAiLedger()` aos eventos `AgentPrompted` (fim do `prompt()`) e `AgentStreamed` (fim do `stream()`).

Por que existe: o `fomvasss/laravel-ai-tasks` só grava runs roteados pela própria facade `AI::send/stream/queue`, que monta um agente anônimo — sem os guardrails, conversas e fakes dos agentes do kit. O listener alimenta a **mesma** tabela `ai_runs` a partir dos eventos nativos do `laravel/ai`, para que o dashboard `/ai-tasks`, o resource "Execuções de IA" (`/infra`) e o guard de budget enxerguem toda execução.

**Falha no ledger nunca derruba a execução do agente** — observabilidade não é regra de negócio.

Sem multi-tenancy no kit: `ai_runs.tenant_id` é NOT NULL, então tudo cai no tenant default de `ai-tasks.default_tenant`.

## Provider e inferência local

`config/ai.php` — default `llamacpp` (`AI_PROVIDER`), embeddings em `llamacpp-embed` (`AI_EMBEDDINGS_PROVIDER`).

```bash
docker compose --profile ai up -d    # llama.cpp: chat 8080, embeddings 8081
```

O primeiro boot baixa ~4,5 GB de modelo. Para SaaS, troque `AI_PROVIDER` e configure a API key — nada no código muda.

`App\Ai\Health\LocalAiCheck` acrescenta os checks dos endpoints locais à página de saúde e devolve `[]` quando o provider não é local, deixando a tela intacta.

## Superfícies no painel

| Onde | O quê |
|---|---|
| `/admin` → Agentes de IA | CRUD do catálogo (`AgenteIaResource`) — é aqui que se edita prompt, provider, tools e guardrails |
| `/app` (toda tela) | widget de chat com streaming (`App\Livewire\AssistenteChatWidget`, injetado por render hook `BODY_END`) |
| `/infra` → Execuções de IA | ledger `ai_runs` com custo e tokens (`AiRunResource`) |
| `/infra` → Dashboard de IA | rota do `laravel-ai-tasks`, fora do Filament, atrás do gate `ver-ai-tasks` |

## Seeders e stubs

- `AssistenteSeeder` e `GuardaPromptSeeder` semeiam o catálogo de forma **idempotente** — rodar de novo é seguro (coberto por teste).
- `stubs/agent.stub`, `stubs/structured-agent.stub`, `stubs/agent-middleware.stub` e `stubs/tool.stub` são os moldes do `make:` do SDK, já alinhados às convenções do kit.

## Testes

`tests/Kit/IaTest.php` fixa o contrato da camada:

- semeia o catálogo de forma idempotente;
- resolve os guardrails declarados;
- recusa guardrail desconhecido em vez de silenciar;
- bloqueia agente sem guardrails (fail-closed);
- bloqueia agente ausente do catálogo;
- recusa responder quando o agente está inativo.

Mexeu em `app/Ai/`? Rode `composer test:kit` antes de entregar.
