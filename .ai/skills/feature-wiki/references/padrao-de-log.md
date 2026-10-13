> Referência da feature-wiki 4.0.0. Lida em: step 4 (antes de especificar os logs de cada passo do `01`). Fonte única de: por que o padrão, anatomia da mensagem, os sete níveis descritos, contexto estruturado seguro, exemplos, `Log::shareContext`, formatter JSON, teste de log em Pest e a trait de logging do projeto.

# Padrão de Log — `[Classe@Método] mensagem`

O formato obrigatório, as regras de escrita (com a regra de severidade), o que especificar por passo
do PRD e os anti-padrões ficam no corpo do `SKILL.md`, seção *Padrão de Log*.

### Por que este padrão

O formato `[Classe@Método] mensagem` é obrigatório em **todos os logs** do projeto. Ele resolve três problemas:

1. **Rastreabilidade**: ao ler um log, sabe-se imediatamente qual classe e método o gerou — sem precisar buscar no código
2. **Filtragem**: permite `grep` por classe ou método para isolar fluxos específicos
3. **Consistência**: padroniza a leitura em qualquer nível (info, warning, error) e em qualquer channel

### Os sete níveis

A regra de severidade (`fail()` → `warning`; `catch` que interrompe → `error`; `catch` tratada → `warning`;
sistema indisponível → `critical`) está no corpo. A descrição de cada nível:

- `debug` → detalhe intermediário para rastreabilidade
- `info` → sucesso de operação esperada
- `notice` → evento significativo mas normal (ex: queue retry agendado)
- `warning` → condição anormal mas não fatal — **usar em `fail()` de Livewire**, fallback, retry, dado ausente
- `error` → falha que interrompe o fluxo — **usar em `catch` de exceptions** que quebram a execução
- `critical` → erro de sistema que exige intervenção imediata (ex: DB inacessível, API crítica fora do ar)
- `emergency` → sistema indisponível, intervenção humana urgente

Exception pode carregar SQL, URLs ou entradas sensíveis na mensagem e no trace. Registrar classe,
código de erro seguro e correlation ID; não passar o objeto bruto nem `getMessage()` sem uma
sanitização existente e verificada. Aplicar a mesma allowlist ao contexto e à mensagem do log.

### Trait de logging do projeto (ex.: `UnicoLogging`)

- `Grep` por `trait UnicoLogging` ou `trait.*Logging` em `app/`
- Se existir, usar a trait nas classes da feature — ela formata automaticamente o prefixo `[Classe@Método]`
- Se não existir, implementar o formato manualmente via `Log::channel(...)->info('[Classe@metodo] ...')`
- Documentar no plano qual abordagem será usada

### Anatomia da Mensagem

| Parte | Descrição | Exemplo |
|-------|-----------|---------|
| `{Classe}` | Nome da classe (sem namespace) | `AddUserToClassJob` |
| `{Método}` | Nome do método que está logando | `processAddUserToClass` |
| `{mensagem}` | Descrição clara da ação executada | `Membro associado com sucesso` |
| `{parâmetro}` | ID, status, ou valor principal manipulado | `enrollment: 280114` |
| `{contexto}` | Informação adicional relevante (opcional) | `evento: enrollment.requested` |

### Contexto Estruturado (array `$context`)

O Laravel aceita um segundo parâmetro `array $context` em todos os métodos de log. Usar uma
allowlist dos campos necessários para diagnosticar o fluxo: identidade interna do registro,
correlation ID, ação, status e motivo seguro. Adicionar dados por utilidade não autoriza exposição.

#### O que incluir no context

- **IDs internos necessários**: `enrollment_id`, `turma_id`, `job_id`, `correlation_id`;
  sem usar nome, e-mail, CPF ou outro dado pessoal como identificador
- **Estado**: status anterior/atual explicitamente escolhidos e contagens, sem snapshots completos
- **Alterações**: nomes dos campos alterados da allowlist, sem valores ou atributos completos do model
- **Erro**: classe da exception e código de erro definido pela aplicação, sem mensagem/trace brutos
- **Contexto de execução**: `queue`, `attempt`, `connection` em jobs; nome da rota em controllers,
  evitando URI, query string, IP e URLs que possam conter credenciais ou dados pessoais
- **Decisões de fluxo**: `reason`, `condition`, `skip_reason` para branches tomados

Nunca incluir senha, token, cookie, cabeçalho Authorization, segredo, dado pessoal ou corpo bruto
de request/resposta. Não usar `$request->all()`, formulário inteiro, `getAttributes()` ou dumps
de objetos como contexto. Reutilizar a sanitização do projeto quando existir, sem criar um helper
genérico para compensar coleta excessiva. Nome e valor de cada campo permitido precisam ser seguros.

#### Exemplo de context seguro

```php
Log::channel('feature-name')->info(
    '[AddUserToClassJob@processAddUserToClass] Membro associado com sucesso | enrollment: 280114',
    [
        'enrollment_id' => 280114,
        'user_id'       => 123,
        'turma_id'      => 456,
        'evento'        => 'enrollment.requested',
        'operation'     => 'associate_member',
        'result'        => 'success',
    ]
);
```

> **Regra de ouro**: registrar a menor allowlist segura que explique ação, resultado e motivo.
> Metadados úteis preservam observabilidade sem copiar a entrada do usuário.

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
    'result'        => 'success',
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
    'route'   => $request->route()?->getName(),
    'result'  => 'received',
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
    'reason'     => 'invalid_input',
    'error_count'=> $this->getErrorBag()->count(),
]);

// catch de exception que interrompe o fluxo — error
Log::channel('feature-name')->error('[AddUserToClassJob@processAddUserToClass] Falha ao associar membro | enrollment: 280114', [
    'enrollment_id' => 280114,
    'exception_type'=> $e::class,
    'error_code'    => 'member_association_failed',
    'attempt'       => $this->attempts(),
]);

// catch de exception tratada/ignorada — warning (não quebra o fluxo)
Log::channel('feature-name')->warning('[SyncEnrollmentsJob@handle] Erro ao sincronizar um item, continuando | enrollment: 280114', [
    'enrollment_id' => 280114,
    'exception_type'=> $e::class,
    'error_code'    => 'enrollment_sync_failed',
    'will_retry'    => true,
]);

// Erro crítico de sistema — critical
Log::channel('feature-name')->critical('[ProcessCurseducaAccountCreationJob@handle] API Curseduca indisponível | tentativa: 3', [
    'attempt'       => 3,
    'exception_type'=> $e::class,
    'service'       => 'curseduca',
    'queue'         => 'default',
]);
```

### Contexto Compartilhado (`Log::shareContext`)

Para contexto que se propaga automaticamente em **todos** os logs da requisição/job (correlation ID, user ID, request ID):

```php
// No início do lifecycle (middleware, job boot, service provider)
Log::shareContext([
    'correlation_id' => Str::uuid()->toString(),
    'user_id'        => Auth::id(),
    'route'          => request()->route()?->getName(),
]);

// Todos os logs subsequentes incluem automaticamente esses campos
Log::channel('feature-name')->info('[Controller@handle] Processando requisição');
// → context mesclado: ['correlation_id' => '...', 'user_id' => 123, 'route' => '...', ...]
```

> **Quando usar**: em jobs longos, webhooks, fluxos multi-etapas onde o mesmo ID precisa aparecer em todos os logs para rastreabilidade.

### Formatter JSON em Produção

Manter `daily` para rotação. Se o projeto precisa de um registro JSON por linha para seu coletor,
configurar o formatter Monolog; Laravel não inclui um driver chamado `json`:

```php
'{feature-name}' => [
    'driver' => 'daily',
    'path'   => storage_path('logs/{feature-name}.log'),
    'level'  => env('LOG_LEVEL', 'debug'),
    'days'   => 14,
    'replace_placeholders' => true,
    'formatter' => \Monolog\Formatter\JsonFormatter::class,
],
```

O `daily` passa a configuração para `prepareHandler()`, que aplica `formatter` — conferido no
[`LogManager` do Laravel 13](https://github.com/laravel/framework/blob/13.x/src/Illuminate/Log/LogManager.php)
e no [`JsonFormatter` do Monolog](https://github.com/Seldaek/monolog/blob/main/src/Monolog/Formatter/JsonFormatter.php).
Confirmar suporte na versão do projeto antes de planejar. JSON altera o formato, não sanitiza os
dados; a allowlist continua obrigatória. Se o coletor não exige JSON, o `daily` normal basta.

### Testando Logs em Pest

> Técnica **opcional**. Log não é cláusula do requisito, então a `feature-test-design` **não deriva
> CT de log** e este template não os exige mais — quem confere o log é a **dimensão D** da
> `feature-quality-gate` (17 logs conferidos um a um na feature de referência, sem nenhum CT de
> log). Use quando o requisito pede trilha de auditoria (aí é `RQ`) ou quando um passo do PRD
> trata o log como saída observável. Helper de log declarado e nunca usado é código morto
> (achado F9 do step 9, em 2026-09-21).

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
