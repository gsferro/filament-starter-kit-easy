# Plano de Ação — Regressão de telas em browser real

> Requisito: `00-requisito.md`

## Natureza da Wiki

- **Tipo**: nova
- **Wiki ancestral**: nenhuma. Esta wiki **atravessa** as seis existentes em vez de derivar de
  uma delas:
  `wikis/specs/main/perfil-e-acesso-ao-painel/`, `.../convite-de-usuario/`,
  `.../convite-para-usuario-existente/`, `.../convite-em-massa/`,
  `.../lembretes-de-convite/`, `.../admin-da-organizacao/` e
  `wikis/specs/feature/multi-tenancy/organizacoes/`.
- **Motivo**: nenhuma feature nova é implementada. O que nasce aqui é uma **camada de teste
  que não existia**: browser real, com JS executando, sobre as telas que as seis wikis
  produziram.

> **Efeito no `feature-quality-gate`**: tipo `nova` significa que o gate valida a superfície
> desta wiki e **não** dispara regressão contra CT-B ancestrais — não existem. A regressão
> aqui é o produto, não a verificação.

## Cobertura do Requisito

| RQ | Cláusula | Passo(s) que atende(m) | Observação |
|----|----------|------------------------|------------|
| RQ-01 | Instalar Pest 5 + `pest-plugin-browser` | 1 | **já executado** — commit `8e5221d`, antes de escrever este PRD, porque sem sondar o contrato real do plugin o plano seria especulação |
| RQ-02 | Wiki de regressão de todas as telas | 2, 3 | inventário completo em `## Superfície de UI` |
| RQ-03 | Provar que funcionam | 4, 5, 6 | CT-B em `05-casos-de-teste-browser.md` |
| RQ-04 | PRDs como oráculo | — | premissa, não passo. Consequências em `00-requisito.md` → "Oráculo degradado" e ADR-01 |
| RQ-05 | Validar perfis | 6 | matriz papel × painel |
| RQ-06 | Validar dark mode | 7 | `->inDarkMode()` |
| RQ-07 | Identificar dívidas técnicas | 8 | `06-divida-tecnica.md`; **correção fora de escopo** por decisão do próprio requisito |
| RQ-08 | Commits individualizados | 9 | um commit por passo entregável |
| RQ-09 | Branch exclusiva | 0 | `feature/wiki-regressao-telas`, criada |
| RQ-10 | Playwright MCP permitido, execução via `pest-browser` | 5 | MCP **não** foi usado: não está configurado nesta sessão, e o fallback nativo do plugin (`content()` + `screenshot()`) resolveu todos os seletores. Registrado em ADR-05 |

## Objetivo

Dar ao kit a camada de teste que ele não tem: **navegador real, com JavaScript executando,
sobre as 52 telas dos três painéis**. Hoje a cobertura de tela é HTTP (`$this->get()` em
`tests/Kit/PaginasInfraTest.php` e `PaineisTest.php`) — ela prova que o servidor devolveu
200, e nada além disso.

A diferença não é acadêmica. Um painel Filament é Livewire + Alpine: o corpo do HTML pode vir
íntegro e a tela estar inutilizável porque um `x-on:click` estourou, porque um asset do Vite
não subiu, ou porque um componente de plugin registrou erro no console. Nenhuma dessas três
falhas move o status HTTP de 200. É exatamente a classe de defeito que só aparece na primeira
visita de um humano — e é isso que esta wiki passa a pegar em teste vermelho.

Além do smoke, três eixos que o requisito nomeia: **perfis** (quem entra onde), **dark mode**
(as telas em tema escuro) e **dívida técnica** (o que corrigir antes das próximas evoluções).

## Contexto

### O que já existe, e o que ele não alcança

| Peça | Onde | O que prova | O que **não** prova |
|---|---|---|---|
| Smoke HTTP das telas de infra e admin | `tests/Kit/PaginasInfraTest.php:39-78` | 15 rotas devolvem 200 para `master_global` e `infra` | nada sobre JS, CSS, tema ou usabilidade |
| Contrato de acesso a painel | `tests/Kit/PaineisTest.php:39-125` | `canAccessPanel()` recorta por papel, e nega com log | que a tela **renderizada** respeita o recorte |
| Recorte multi-tenant | `tests/Tenancy/*` | escopo por organização em nível de query | idem |
| Cobertura de tela do painel `/app` | — | **quase nada.** Das 12 telas de `/app`, só o `GET /app` genérico | — |

O buraco do `/app` é o achado que mais dói: `PaginasInfraTest` cobre `/infra` e `/admin`, e o
painel de negócio — o único que o consumidor do kit vai usar todo dia — não tem uma única
visita testada além do `GET /app` genérico em `PaineisTest.php:115-119`.

### O contrato real do `pest-plugin-browser`, sondado antes de planejar

Quatro fatos que mudam o desenho e que **não** estão na doc oficial de forma explícita. Todos
verificados por sonda executada e descartada (ver ADR-04):

1. **O plugin sobe servidor HTTP próprio, in-process, em porta aleatória.** A sonda imprimiu
   `http://127.0.0.1:60212/app`. Nenhum Herd, `php artisan serve` ou Sail entra nos
   pré-requisitos — e não há `APP_URL` a configurar.
2. **Por rodar no mesmo processo, o banco do teste é o banco do navegador.**
   `DB_DATABASE=:memory:` (`phpunit.xml:36`) e `RefreshDatabase` continuam valendo. Isto é o
   oposto do que um teste de browser costuma exigir.
3. **`$this->actingAs()` autentica o navegador.** Confirmado: `actingAs()` seguido de
   `visit('/app')` abre o dashboard. É o que torna viável cobrir 52 telas — logar pela UI em
   cada uma custaria ~20 s por cenário.
4. **`npm run build` é pré-requisito duro.** Sem `public/build/manifest.json` **toda** tela
   responde `ViteException`, e o CT-B falha por um motivo que não é o dele. A primeira sonda
   falhou exatamente assim.

### Os seletores que as telas oferecem — e o que elas não oferecem

Extraído do HTML real (`content()` da tela de login):

```html
<input id="form.email"    wire:model="data.email"    type="email" ...>
<input id="form.password" wire:model="data.password"  ...>
<button type="submit" ...>Login</button>
<button aria-label="Mudar para tema escuro" x-on:click="(theme = 'dark') && close()" ...>
```

**Nenhum elemento do kit tem `data-testid`.** Os seletores viáveis hoje são o `id` gerado pelo
Filament (`#form\.email` — o ponto precisa de escape em CSS), o texto visível e o
`aria-label`. Isso entra em `06-divida-tecnica.md` como dívida DT-05: seletor por `id` de
framework é estável só enquanto o Filament não mudar a convenção.

## Autorização

Nenhuma policy, gate, middleware ou guard é criado ou modificado. Esta wiki **consome** o
sistema de autorização existente como oráculo:

- `User::canAccessPanel()` — lê `roles.painel`; é o que o CT-B06 exercita pela tela
- `Gate::before` do `master_global` — vence sem permission no banco, o que faz dele o único
  papel capaz de abrir as 52 telas
- Matriz de permissões dos papéis `admin` e `infra` — gerada por `ShieldPermissionsSeeder`

## Rotas

Nenhuma rota nova. O inventário das existentes está em `## Superfície de UI`.

## Superfície de UI

Fonte: `php artisan route:list --method=GET`, filtrado pelos três painéis — **74 rotas GET**.
Delas: 13 exigem `{record}`, 3 são endpoints JSON de passkey (não são tela) e 6 exigem estado
ou token (`/*/screen/lock`, `/*/password-reset/reset`). **52 telas** são
alcançáveis por URL fixa, e são estas:

### Painel `/app` — 12 telas (papel `panel_user` ou superior)

| Tela | Rota | Tipo | Depende de JS? |
|---|---|---|---|
| Login | `/app/login` | Filament Auth (Auth Designer) | Sim |
| Registro por convite | `/app/register` | Filament Auth custom | Sim |
| Recuperar senha | `/app/password-reset/request` | Filament Auth | Sim |
| Dashboard | `/app` | Filament Dashboard + widgets | Sim |
| Meu perfil | `/app/meu-perfil` | Breezy | Sim |
| 2FA | `/app/two-factor-authentication` | Breezy | Sim |
| Convites (lista) | `/app/convites` | Filament Resource | Sim |
| Convite (criar) | `/app/convites/create` | Filament Resource | Sim |
| Convites recebidos | `/app/convites-recebidos` | Filament Page | Sim |
| Projetos | `/app/projetos` | Filament Resource | Sim |
| Usuários (lista) | `/app/users` | Filament Resource | Sim |
| Usuário (criar) | `/app/users/create` | Filament Resource | Sim |

### Painel `/admin` — 19 telas (papéis `admin`, `master_global`)

| Tela | Rota | Tipo | Depende de JS? |
|---|---|---|---|
| Login | `/admin/login` | Filament Auth | Sim |
| Recuperar senha | `/admin/password-reset/request` | Filament Auth | Sim |
| Dashboard | `/admin` | Dashboard + 6 widgets | Sim |
| Meu perfil | `/admin/meu-perfil` | Breezy | Sim |
| 2FA | `/admin/two-factor-authentication` | Breezy | Sim |
| Usuários (lista / criar) | `/admin/users`, `/admin/users/create` | Resource | Sim |
| Papéis (lista / criar) | `/admin/shield/roles`, `.../create` | Resource (Shield publicado) | Sim |
| Convites (lista / criar) | `/admin/convites`, `.../create` | Resource | Sim |
| Organizações (lista / criar) | `/admin/organizacoes`, `.../create` | Resource | Sim |
| Agentes de IA (lista / criar) | `/admin/agentes-ia`, `.../create` | Resource | Sim |
| Onboarding flows (lista / criar) | `/admin/onboarding-flows`, `.../create` | Resource (plugin) | Sim |
| Onboarding conditions (lista / criar) | `/admin/onboarding-conditions`, `.../create` | Resource (plugin) | Sim |

### Painel `/infra` — 21 telas (papéis `infra`, `master_global`)

| Tela | Rota | Tipo | Depende de JS? |
|---|---|---|---|
| Login | `/infra/login` | Filament Auth | Sim |
| Recuperar senha | `/infra/password-reset/request` | Filament Auth | Sim |
| Dashboard | `/infra` | Dashboard + 14 widgets | Sim |
| Meu perfil | `/infra/meu-perfil` | Breezy | Sim |
| 2FA | `/infra/two-factor-authentication` | Breezy | Sim |
| Health checks | `/infra/health-check-results` | Page (plugin) | Sim |
| Backups | `/infra/backup-runs` | Page (plugin) | Sim |
| Filas | `/infra/queue-monitors` | Resource (plugin) | Sim |
| Filas — falhas | `/infra/queue-monitors/failures` | Resource (plugin) | Sim |
| Filas — pendentes | `/infra/queue-monitors/pending` | Resource (plugin) | Sim |
| Auditoria | `/infra/audits` | Resource (plugin) | Sim |
| Trilha de acesso | `/infra/authentication-logs` | Resource (plugin) | Sim |
| Logs | `/infra/logs` | Page (plugin) | Sim |
| Grafo de dependências | `/infra/dependency-graph` | Page (plugin) | Sim |
| Releases do Composer | `/infra/composer-release-packages` | Resource (plugin) | Sim |
| Execuções de IA | `/infra/execucoes-ia` | Resource próprio | Sim |
| Pulse | `/infra/pulse` | Page (Pulse embutido) | Sim |
| Comandos | `/infra/command-center/commands` | Page (plugin) | Sim |
| Histórico de comandos | `/infra/command-center/history` | Page (plugin) | Sim |
| Definições de comando (lista / criar) | `/infra/command-center/definitions`, `.../create` | Resource (plugin) | Sim |

### Fora do lote — e por quê

**6 rotas exigem estado ou token**, não URL: `/*/screen/lock` (o Lockscreen precisa de sessão
bloqueada) e `/*/password-reset/reset` (precisa de token válido na query). Não são telas
alcançáveis, e por isso não entram nas 52.

### Telas com `{record}` — 13, fora do lote

`/admin/users/{record}/edit`, `/admin/organizacoes/{record}/edit`,
`/admin/agentes-ia/{record}/edit`, `/admin/shield/roles/{record}`,
`/admin/shield/roles/{record}/edit`, `/admin/onboarding-flows/{record}`,
`/admin/onboarding-flows/{record}/edit`, `/admin/onboarding-conditions/{record}/edit`,
`/app/users/{record}/edit`, `/infra/audits/{record}`, `/infra/execucoes-ia/{record}`,
`/infra/command-center/definitions/{record}/edit`, `/infra/command-center/runs/{run}`.

**Decisão**: ficam fora do lote de smoke. Uma delas já tem cobertura de gravação em
`tests/Kit/PaginasInfraTest.php:86-104` (Livewire, `EditUser`), que é onde a regra de negócio
pertence. Criar fixture para as outras doze só para provar que o formulário abre é custo alto
e valor baixo — e as telas de edição do Filament compartilham o mesmo Blade das de criação,
que **estão** no lote. Registrado em ADR-02.

**Gate de CT-B**: 52 linhas na tabela, todas com `Depende de JS? = Sim` → **criar**
`05-casos-de-teste-browser.md`. ✅

## Variáveis de Ambiente

Nenhuma chave nova. O que a suíte de browser consome já existe em `phpunit.xml`:

| Key | Valor em teste | Por que importa aqui |
|---|---|---|
| `DB_CONNECTION` / `DB_DATABASE` | `sqlite` / `:memory:` | funciona porque o servidor do plugin é in-process (ver Contexto) |
| `SESSION_DRIVER` | `array` | idem — é o que faz `actingAs()` valer no navegador |
| `PULSE_ENABLED` | `false` | `/infra/pulse` renderiza com Pulse desligado; a tela precisa abrir mesmo assim |

## Eventos / Listeners / Observers

Nenhum. Esta wiki não emite nem escuta evento.

## Jobs / Queues

Nenhum job novo. `QUEUE_CONNECTION=sync` em teste.

## Impacto em Features Existentes

| Feature | O que pode quebrar e por quê |
|---|---|
| **Toda a suíte de testes** | PHPUnit 12 → 13 é major. `TestResult::__construct()` mudou assinatura; qualquer extensão de PHPUnit desatualizada estoura. Mitigação: passo 2 roda `tests/Kit` + `tests/Tenancy` inteiros antes de qualquer CT-B |
| `composer test` | O script chama `phpstan analyse`; larastan `^3.9` precisa tolerar PHPUnit 13 no autoload. Verificado no passo 2 |
| CI (se existir) | `npm run build` passa a ser pré-requisito do job de teste, não só do de deploy |
| Nada em `app/` | **nenhum arquivo de aplicação é modificado por esta wiki.** Isso é deliberado: RQ-07 pede identificar dívida, não corrigir |

## Rollback

- **Migration down**: não há migration.
- **Reverter as deps**: `git revert 8e5221d && composer install` volta a Pest 4.7 / PHPUnit 12.5.
- **Reverter os testes**: `rm -rf tests/Browser` e desfazer o bloco `->in('Browser')` de
  `tests/Pest.php`. Nenhum teste existente depende deles.
- **Feature flag**: não se aplica — teste não vai a produção.

## Dependências

Instaladas no commit `8e5221d`:

| Pacote | Versão | Papel |
|---|---|---|
| `pestphp/pest` | 5.1.1 | `--tia`, sharding por tempo |
| `pestphp/pest-plugin-browser` | 5.0.1 | `visit()`, Playwright, servidor in-process |
| `pestphp/pest-plugin-laravel` | 5.0.1 | compatibilidade com Pest 5 |
| `phpunit/phpunit` | 13.3.0 | requisito duro do Pest 5 |
| `playwright` (npm) | latest | browsers baixados com `npx playwright install` |

Requisitos de ambiente já satisfeitos: PHP 8.4.10 (Pest 5 exige 8.4+), Xdebug 3.4.4 (driver
de cobertura que o `--tia` exige), Node 25.9.

## Riscos

| Risco | Mitigação |
|---|---|
| Suíte de browser lenta demais para uso diário | lote com `visit([...])` em vez de um cenário por tela: 52 telas em ~7 CT-B. Suíte de browser fica em `--testsuite=Browser`, fora do `composer test:kit` |
| Flake por conteúdo assíncrono (Livewire/Alpine) | nenhum `wait()` fixo. Só assertions sobre estado final visível, que o plugin já reexecuta até o timeout |
| `--parallel` com browser multiplica processos de navegador | CT-B rodam **em série**. `--parallel --tia` fica para o suite de backend |
| `assertNoAccessibilityIssues()` vermelho por problema de vendor | **é o resultado esperado**, não flake: já reproduzido na sonda (2 achados). Vai para `06-divida-tecnica.md`; o CT-B correspondente nasce marcado `->todo()` para não travar a suíte com dívida de terceiro |
| Seletor por `id` do Filament (`#form\.email`) quebrar em upgrade | dívida DT-05; a mitigação real (`data-testid`) é a evolução seguinte |

## Channel de Log da Feature

### Verificação de Channel Existente

`config/logging.php` foi lido. O channel `autenticacao` existe (linhas 101-107) e é usado por
`User::canAccessPanel()` e pelas features de convite.

### Decisão

**Nenhum channel novo, e nenhum log novo.**

O padrão de log da skill se aplica a *código de aplicação que executa lógica*. Esta wiki não
escreve uma linha em `app/` — ela escreve testes. Criar um channel `regressao-de-telas` para
um arquivo de teste emitir `Log::channel(...)->info()` seria log sobre o ato de testar, que
não tem consumidor: quem lê resultado de teste lê a saída do Pest, não `storage/logs/`.

O que esta wiki faz com log é o inverso, e está no CT-B06: **assertar que o log existente
é emitido** quando um papel é barrado num painel — o `[User@canAccessPanel]` no channel
`autenticacao`. A cobertura dessa asserção já existe em `tests/Kit/PaineisTest.php:81-90`,
em nível de unidade; o CT-B confirma que o caminho de browser chega ao mesmo lugar.

> Registrado em ADR-03, porque é um desvio explícito de uma exigência da skill, e desvio
> silencioso é pior que desvio justificado.

## Estrutura de Implementação

### 1. Instalar Pest 5 + `pest-plugin-browser` — **CONCLUÍDO** (commit `8e5221d`)

> Skills: `pest-testing`

Executado antes de escrever este PRD, deliberadamente: os quatro fatos do
`## Contexto → contrato real do plugin` só podiam ser conhecidos sondando, e um plano escrito
sem eles teria desenhado login por UI em 52 telas e exigido Herd nos pré-requisitos.

- `composer require --dev pestphp/pest:^5.1 pestphp/pest-plugin-laravel:^5.0 pestphp/pest-plugin-browser:^5.0 phpunit/phpunit:^13.3 -W`
- `npm install --save-dev playwright@latest && npx playwright install`
- `/tests/Browser/Screenshots` no `.gitignore`
- **Logs**: nenhum — ver `## Channel de Log da Feature`

### 2. Confirmar que o upgrade não quebrou a suíte existente

> Skills: `pest-testing`

- Rodar `vendor/bin/pest --group=kit --parallel` e comparar com o baseline pré-upgrade
  (213 testes, 726 asserções — medido no primeiro run, ainda em Pest 4)
- Rodar `vendor/bin/phpstan analyse` — larastan tem de tolerar PHPUnit 13
- **Critério de aceite**: mesma contagem de testes verdes de antes. Qualquer regressão aqui
  **para o passo 3** — CT-B novo sobre suíte quebrada não mede nada
- **Logs**: nenhum

### 3. Registrar a suíte `Browser` sem contaminar as existentes

> Skills: `pest-testing`

- **Path**: `tests/Pest.php` — acrescentar, **depois** do bloco `Tenancy` e **antes** dos
  helpers compartilhados:

  ```php
  pest()->extend(TestCase::class)
      ->use(RefreshDatabase::class)
      ->group('browser')
      ->in('Browser');
  ```

  `group('browser')` e **não** `group('kit')`: a suíte de browser precisa ficar fora do
  `composer test:kit`, que é o comando de resposta rápida depois de um `kit:update`. Browser
  em série custa ordens de magnitude mais que HTTP.

- **Path**: `phpunit.xml` — nova `<testsuite name="Browser">` apontando para `tests/Browser`,
  declarada **por último**, depois de `Kit`.
- **Path**: `composer.json` — script `test:browser`:
  `["@php artisan config:clear --ansi", "npm run build", "@php vendor/bin/pest --testsuite=Browser"]`.
  O `npm run build` entra no script, e não na documentação: o pré-requisito é duro e
  esquecê-lo produz 52 falhas com mensagem que não aponta para a causa.
- **Critério de aceite**: `vendor/bin/pest --group=kit` continua com a mesma contagem do
  passo 2, e `vendor/bin/pest --testsuite=Browser` reconhece a suíte (vazia, neste ponto).
- **Logs**: nenhum

### 4. Helper de persona para browser

> Skills: `pest-testing`

- **Path**: `tests/Pest.php` — na seção de helpers compartilhados.
- Os CT-B precisam de um usuário com papel e com os seeders de permissão rodados. Os helpers
  existentes (`usuarioDoKit()`, `usuarioComPapel()`) resolvem o papel, mas **não** rodam
  `ShieldPermissionsSeeder` / `PapeisSeeder` — hoje isso está repetido no `beforeEach` de
  cada arquivo de `tests/Kit`.
- **Decisão Ponytail**: não criar helper novo. O `beforeEach` do arquivo de CT-B chama
  `$this->seed([...])` e `usuarioDoKit()`, exatamente como `tests/Kit/PaineisTest.php:20-22`
  já faz. Um helper para dois usos é abstração especulativa; herdar o padrão do vizinho é a
  escolha mais barata **e** a mais legível para quem já conhece a suíte.
- **Critério de aceite**: nenhum arquivo novo neste passo. Se ele produzir código, algo saiu
  do plano.
- **Logs**: nenhum

### 5. Escrever e rodar os CT-B de smoke das 52 telas

> Skills: `pest-testing`, `ponytail`

- **Path**: `tests/Browser/TelasDoKitTest.php`
- Especificação completa em `05-casos-de-teste-browser.md` — CT-B01 a CT-B04.
- Desenho: **lote com `visit([...])`**. Escrito primeiro como um cenário por painel; depois do
  `/ponytail:ponytail-review` os três viraram **um `it()` com dataset** por painel, porque o
  corpo era idêntico — 52 telas em 2 cenários (o dataset de 3 painéis + 1 para as públicas).
- Cada lote assere `assertNoJavaScriptErrors()`. Não `assertNoSmoke()`: `assertNoSmoke()`
  também reprova `console.log`, e `console.log` de pacote de terceiro é dívida cosmética que
  não deve deixar a suíte vermelha. Ver ADR-06.
- Execução delegada a sub-agente, conforme a skill (loop de correção de seletor isolado).
- **Critério de aceite**: CT-B verdes, ou vermelho **classificado** — CT-B errado (a),
  implementação divergente (b) ou flake (c). Vermelho por (b) é achado, não falha do ciclo.
- **Logs**: nenhum

### 6. CT-B de perfis — a matriz papel × painel pela tela

> Skills: `pest-testing`

- **Path**: `tests/Browser/PerfisTest.php`
- Especificação em `05-casos-de-teste-browser.md` — CT-B05 e CT-B06.
- O que se prova, e que `tests/Kit/PaineisTest.php` não prova: o recorte que
  `canAccessPanel()` decide **chega à tela**. Hoje o teste HTTP afirma `assertForbidden()`;
  o CT-B confirma que o usuário barrado vê uma página de 403 legível, e não uma tela branca
  ou um erro de JS.
- Inclui o **login real pela UI** (`#form\.email`, `#form\.password`, `press('Login')`),
  que é o único caminho pelo qual um usuário de verdade entra — e o único CT-B que não usa
  `actingAs()`.
- **Critério de aceite**: `admin` entra em `/admin` e é barrado em `/infra`; `infra` o
  inverso; `panel_user` entra em `/app` e é barrado nos dois; usuário sem papel é barrado
  nos três.
- **Logs**: nenhum novo. O CT-B confirma o log **existente** `[User@canAccessPanel]` no
  channel `autenticacao`, com `motivo = sem_papel_do_painel`

### 7. CT-B de dark mode

> Skills: `pest-testing`, `tailwindcss-development`

- **Path**: `tests/Browser/TemaEscuroTest.php`
- Especificação em `05-casos-de-teste-browser.md` — CT-B07 e CT-B08.
- Duas metades, que falham por motivos diferentes:
  1. **`->inDarkMode()`** — o navegador anuncia `prefers-color-scheme: dark`. Cobre o default
     do painel, que é `--default-theme-mode: system` (confirmado no HTML da tela).
  2. **O alternador** — clicar em `[aria-label="Mudar para tema escuro"]` e confirmar que a
     escolha pega. O seletor por `aria-label` é o mais estável que o kit oferece hoje.
- **Critério de aceite**: os três dashboards e a tela de login renderizam em tema escuro sem
  erro de JS, e o alternador de tema muda o estado sem recarregar.
- **Logs**: nenhum

### 8. Registrar as dívidas técnicas encontradas

> Skills: nenhuma — é documento

- **Path**: `wikis/specs/feature/wiki-regressao-telas/regressao-de-telas/06-divida-tecnica.md`
- Uma entrada por dívida: o que é, onde, como foi encontrada, severidade, custo estimado da
  correção e **por que não foi corrigida agora**.
- Duas já estão confirmadas pela sonda, e nasceram desta rodada:
  - **DT-01** (critical, a11y): botão *Clear Cache* sem texto acessível — `aria-label` vazio,
    pacote `cms-multi/filament-clear-cache`
  - **DT-02** (serious, a11y): contraste 4.25:1 no `environment-indicator`, abaixo do mínimo
    4.5:1 — pacote `pxlrbt/filament-environment-indicator`
- **Critério de aceite**: nenhum arquivo de `app/` alterado. Correção é a evolução seguinte,
  por decisão do requisito (RQ-07).
- **Logs**: nenhum

### 9. Commits individualizados

> Skills: nenhuma

Um commit por passo entregável, na ordem: `8e5221d` (passo 1) → suíte registrada (passo 3) →
CT-B de smoke (5) → CT-B de perfis (6) → CT-B de dark mode (7) → dívida técnica (8) → wiki
(00–06). A wiki entra ao final, com o roteiro *Desenhado × Implementado* já preenchido — antes
disso ela mentiria sobre o que foi verificado.

## Filosofia de Implementação

> **Ponytail ativo em modo `full`** durante toda a implementação.
> A escada, aplicada a esta wiki:
> 1. **Reutilizar**: o `beforeEach` de `tests/Kit/PaineisTest.php` é o padrão de seed; herdar,
>    não reinventar (passo 4 fecha em "nenhum arquivo novo")
> 2. **Stdlib / feature nativa**: `visit([...])` em lote é do plugin. Escrever 52 cenários à
>    mão seria ignorar a feature que existe
> 3. **Uma linha quando possível**: cada CT-B de smoke é uma cadeia de chamadas
> 4. **Mínimo que funciona**: nenhum helper novo, nenhum trait, nenhum `data-testid` adicionado
>    (isso seria mexer em `app/`, fora de escopo)
>
> Atalhos deliberados marcados com `ponytail:` comment.
> Após implementação, rodar `/ponytail:ponytail-review` no diff.
>
> **Caveman ativo em modo `full`** na comunicação agent ↔ usuário.
> Arquivos wiki (00-06) são boundary do Caveman — prosa normal. Código e commits também.

## Mapeamentos

O mapa papel → painel → telas alcançáveis não é redigitado aqui: ele vive em
`PapeisSeeder` + `User::canAccessPanel()`, é assertado em `tests/Kit/PaineisTest.php:43-79`
e é exercitado pela tela no dataset do CT-B05
(`05-casos-de-teste-browser.md` → CT-B05, `tests/Browser/PerfisTest.php:31-35`).

Uma quarta cópia em prosa envelheceria sem ninguém notar — foi exatamente o que aconteceu com
o inventário de telas nesta wiki, antes da correção de D-02.

## Testes

> Ver `05-casos-de-teste-browser.md` para os CT-B.
>
> **Sem CTs de backend novos neste plano.** O `04-casos-de-teste.md` desta wiki é uma nota de
> ausência deliberada, não um arquivo esquecido: a regra de negócio das seis features já tem
> 213 testes verdes, e duplicá-los em `Feature` seria acrescentar custo sem acrescentar
> cobertura. A camada que faltava é browser, e é só ela que nasce aqui.

## Verificação Final

- [ ] `/ponytail:ponytail-review` no diff
- [ ] `vendor/bin/pint --dirty --format agent`
- [ ] `vendor/bin/pest --group=kit --parallel` — mesma contagem do baseline do passo 2
- [ ] `vendor/bin/pest --testsuite=Browser` — CT-B em série
- [ ] `vendor/bin/pest --parallel --tia` — confirma o que o diff afetou, contra
      `## Impacto em Features Existentes`
- [ ] `vendor/bin/phpstan analyse`
- [ ] Roteiro *Desenhado × Implementado* do `05-*-browser.md` preenchido
- [ ] `06-divida-tecnica.md` escrito, e **nenhum** arquivo de `app/` no diff

## Commits

- `:arrow_up: pest 5 + pest-plugin-browser: base para os testes de browser` — **feito** (`8e5221d`)
- `:white_check_mark: suite Browser: registro em Pest.php, phpunit.xml e script composer`
- `:white_check_mark: CT-B de smoke: 52 telas dos tres paineis em browser real`
- `:white_check_mark: CT-B de perfis: matriz papel x painel pela tela`
- `:white_check_mark: CT-B de dark mode: tema escuro e alternador`
- `:memo: divida tecnica: o que corrigir antes das proximas evolucoes`
- `:memo: wiki: regressao de telas em browser real`
