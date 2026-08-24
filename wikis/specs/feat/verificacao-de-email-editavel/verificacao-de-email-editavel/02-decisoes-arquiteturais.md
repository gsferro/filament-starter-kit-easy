# Decisões Arquiteturais — W7: validação de e-mail editável

## ADR-01: Aplicar o middleware SEMPRE e deixar a decisão dentro dele

**Status**: Aceita
**Data**: 2026-08-24

### Contexto

`HasRoutes::getRouteMiddleware()` monta o array de middleware da rota no momento do registro:

```php
...(static::isEmailVerificationRequired($panel) ? [static::getEmailVerifiedMiddleware($panel)] : []),
```

(`vendor/filament/filament/src/Pages/Concerns/HasRoutes.php:91`)

O `?:` é avaliado **uma vez**, no boot, e o resultado fica gravado no array. Duas consequências
medidas pelo quality gate da v0.19.1:

1. o valor do banco chega depois (o painel é montado antes de `ConfiguracoesDoKit::aplicarNaConfig()`);
2. mesmo que chegasse antes, mudar a config em runtime não reavalia o array — a rota já está
   registrada. É por isso que **Closure em `isRequired` não resolve**: `isEmailVerificationRequired()`
   chama `evaluate()` (`HasAuth.php:303-306`), mas quem chama `isEmailVerificationRequired()` é o
   registro de rota, não o request.

### Decisão

Inverter a condição de lugar. O array da rota passa a conter o middleware **incondicionalmente**, e
a condição vira a primeira linha do `handle()`:

```php
if (! RegistroAberto::exigirVerificacaoDeEmail()) {
    return $next($request);
}
```

O array da rota continua fixo — o que é fato do Filament e não se combate. O que se muda é **o que
está fixado nele**: em vez de uma decisão, um decisor.

### Alternativas Consideradas

1. **Closure em `isRequired`** — descartada pelo próprio requisito, e a razão é verificável no
   vendor: quem avalia é o registro da rota.
2. **`->middleware([...], isPersistent: true)` do painel, com a lógica inteira ali** — funciona, mas
   perde o parâmetro da rota de destino que o Filament já calcula
   (`getEmailVerificationPromptRouteName()`), obrigando a hardcodar o nome da rota ou a recalculá-lo.
   Mais código para o mesmo efeito, e um nome de rota duplicado.
3. **Sobrescrever `getRouteMiddleware()`** — exigiria uma classe base própria para toda página e
   todo resource do painel. Sete arquivos de infraestrutura para uma decisão booleana.
4. **`Route::matched` / macro / manipular o router no boot** — mexer no roteador para não mexer no
   middleware é trocar um ponto de extensão documentado por um truque.
5. **`canAccessPanel()` no `User`** — parece atraente (é lido por request) e é a alternativa mais
   perigosa: `canAccessPanel()` **nega o acesso**, com 403, em vez de levar à tela de confirmação. A
   pessoa ficaria trancada fora sem caminho de saída, e o contrato é global — mexer nele mexe em
   `/admin` e `/infra` (RQ-08).

### Consequências

- **Positivas**: a decisão vira runtime de verdade; o `.env` volta a ser só semeador; a tela deixa
  de mentir. E o mecanismo é genérico — qualquer chave de boot do Filament que tenha um
  `...MiddlewareName()` pode ser resolvida do mesmo jeito.
- **Negativas**: o middleware roda em toda rota de página do `/app`, inclusive com a opção
  desligada. Custo real: uma leitura de `config()` (array em memória) e um `if`. Não há query.
- **Riscos**: se alguém reintroduzir a condição no `emailVerification()`, o toggle volta a mentir
  em silêncio. Mitigado por CT-07, que afirma sobre o painel montado nos dois estados.

### Referências

- `vendor/filament/filament/src/Panel/Concerns/HasAuth.php:174-178` — `emailVerifiedMiddlewareName()`
- `vendor/filament/filament/src/Panel/Concerns/HasAuth.php:367-370` — a string que vai para a rota
- `vendor/filament/filament/src/Pages/Concerns/HasRoutes.php:91` — o array fixo
- Refina: ADR-05 da wiki `registro-e-aprovacao` (que decidiu pela dívida)

---

## ADR-02: Estender `EnsureEmailIsVerified` em vez de reimplementar

**Status**: Aceita
**Data**: 2026-08-24

### Contexto

O `handle()` do middleware do Laravel tem 10 linhas e três comportamentos que não são óbvios:

```php
if (! $request->user() ||
    ($request->user() instanceof MustVerifyEmail &&
    ! $request->user()->hasVerifiedEmail())) {
    return $request->expectsJson()
        ? abort(403, 'Your email address is not verified.')
        : Redirect::guest(URL::route($redirectToRoute ?: 'verification.notice'));
}
```

(`vendor/laravel/framework/src/Illuminate/Auth/Middleware/EnsureEmailIsVerified.php:31-42`)

Três coisas que uma reimplementação apressada erra: quem **não** implementa `MustVerifyEmail` passa
(o `&&` protege); quem espera JSON recebe 403 em vez de redirect (Livewire e as requisições AJAX do
painel dependem disso); e `Redirect::guest()` grava a URL pretendida, o que faz a pessoa voltar
para onde ia depois de validar.

### Decisão

`final class ExigirEmailVerificado extends EnsureEmailIsVerified`, com `handle()` sobrescrito
apenas para acrescentar a guarda e delegar com `parent::handle()`.

### Alternativas Consideradas

1. **Reimplementar do zero** — copiaria as três sutilezas acima, e a cópia envelhece sozinha na
   próxima versão do framework.
2. **Compor em vez de herdar** (`app(EnsureEmailIsVerified::class)->handle(...)`) — o mesmo efeito
   com uma indireção a mais e sem o benefício de o Filament ver uma subclasse do middleware que ele
   espera.
3. **Não estender nada e usar `->middleware()` do painel com uma closure** — closure em middleware
   de painel não recebe o nome da rota de destino calculado pelo Filament.

### Consequências

- **Positivas**: 5 linhas de lógica nossa. Toda a semântica de "e-mail não verificado" continua
  sendo a do framework, e acompanha upgrade.
- **Negativas**: acoplamento a uma classe do framework que não é marcada como `final` mas também
  não é ponto de extensão anunciado. Se a assinatura de `handle()` mudar, o PHPStan acusa.
- **Riscos**: nenhum medido.

### Referências

- `app/Http/Middleware/ExigirEmailVerificado.php`
- Refina: ADR-01

---

## ADR-03: A rota de verificação passa a nascer sempre

**Status**: Aceita
**Data**: 2026-08-24

### Contexto

Hoje `->emailVerification(null, isRequired: false)` apaga a ação da rota, e
`hasEmailVerification()` — que é `filled($action)` (`HasAuth.php:620-622`) — fica falso: nenhuma das
duas rotas de verificação nasce (`vendor/filament/filament/routes/web.php:75-84`).

Com a decisão vindo do request, isso deixa de ser sustentável. O middleware pode redirecionar em
qualquer request; `Redirect::guest(URL::route($redirectToRoute))` com uma rota que não existe é
`RouteNotFoundException` — um 500, não uma tela. Alguém ligaria o toggle e derrubaria o `/app`.

### Decisão

`->emailVerification(EmailVerification::class)` sem condição. As duas rotas existem sempre; quem
decide se alguém é **levado** até elas é o middleware.

### Alternativas Consideradas

1. **Registrar a rota condicionalmente e o middleware sempre** — é exatamente o bug: decisão de
   boot para a rota, decisão de request para o middleware, e os dois discordando.
2. **Redirecionar para o dashboard quando a rota não existe** — esconderia o defeito e deixaria a
   pessoa num laço de redirecionamento silencioso.

### Consequências

- **Positivas**: RQ-09 satisfeito por construção. O destino existe antes de alguém poder ser
  mandado para lá.
- **Negativas**: `/app/email-verification/prompt` fica alcançável com a opção desligada. Quem já
  tem `email_verified_at` é redirecionado pelo `mount()` da própria página
  (`EmailVerificationPrompt.php:29-33`); quem não tem vê uma tela funcional e pode validar o
  e-mail. Ninguém é **barrado**, que é o que RQ-05 exige. Registrado em `## Ambiguidades` do `00`.
- **Riscos**: a rota `verify` assinada passa a existir sempre. Ela já é protegida por `signed` e
  `throttle:6,1` — a superfície nova é uma URL que só aceita link assinado pela `APP_KEY`.

### Referências

- `vendor/filament/filament/routes/web.php:75-84`
- `vendor/filament/filament/src/Auth/Pages/EmailVerification/EmailVerificationPrompt.php:29-33`

---

## ADR-04: Reutilizar o channel `autenticacao`

**Status**: Aceita
**Data**: 2026-08-24

### Contexto

A skill `feature-wiki` pede um channel por feature. Já existe `autenticacao`, usado por
`RegistroAberto::registrar()`, pelo convite e pelo bloqueio de sessão.

### Decisão

Reutilizar `autenticacao`. Nenhum channel novo.

### Alternativas Consideradas

1. **Channel `verificacao-de-email`** — fragmentaria a trilha de uma pergunta única ("por que esta
   pessoa não entra no /app?") em dois arquivos, para um middleware que emite **um** tipo de
   evento.

### Consequências

- **Positivas**: a investigação continua tendo um lugar para olhar.
- **Negativas**: o `autenticacao.log` cresce um pouco mais. Só no barramento — o caminho liberado
  não loga, de propósito: ele é todo request de todo usuário do `/app`.

### Referências

- `config/logging.php` — channel `autenticacao`
- `app/Support/RegistroAberto.php:180-196` — o padrão de log que este middleware espelha

---

## ADR-05: Migration de settings nova, não edição da existente

**Status**: Aceita
**Data**: 2026-08-24

### Contexto

Há uma única migration de settings, e o docblock dela é explícito: *"Não edite esta migration depois
de ela ter rodado em algum lugar — crie outra"*. Além disso, três branches paralelas mexem no mesmo
Settings nesta rodada.

### Decisão

`database/settings/2026_08_25_000000_add_registro_verificar_email_to_kit_settings.php`, com um
`add()` e um `deleteIfExists()`.

### Alternativas Consideradas

1. **Acrescentar o `add()` na migration existente** — quebra a regra do docblock (instalação que já
   rodou não recebe a propriedade e estoura `Spatie\LaravelSettings\Exceptions\MissingSettings`) e
   garante conflito com as outras duas branches, que precisam da mesma linha.

### Consequências

- **Positivas**: instalação existente recebe a propriedade no `migrate`; conflito de merge nenhum.
- **Negativas**: mais um arquivo em `database/settings/`. O `down()` da migration original também
  apaga a chave (ela itera `mapaDeConfiguracao()`), então há sobreposição inofensiva de rollback.
- **Riscos**: nenhum.

### Referências

- `database/settings/2026_08_24_000000_create_kit_settings.php:34-38`
