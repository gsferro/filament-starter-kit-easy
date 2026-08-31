# Plano de Ação — Adotar `ddr/filament-captcha` como pacote de captcha

> Requisito: `00-requisito.md`

## Natureza da Wiki

- **Tipo**: evolução
- **Wiki ancestral**: `wikis/specs/feat/recaptcha-nas-telas-publicas/recaptcha-nas-telas-publicas/`
- **Motivo**: substituir a implementação própria (`CampoAntiRobo`, `ProvedorAntiRobo`, blade) pelo pacote `ddr/filament-captcha`, que traz reCAPTCHA v3 e arquitetura de drivers extensível
- **Toca infra compartilhada?**: sim → `ConfiguracoesDoKit` (propriedades + mapa), migration de settings, `ConfiguracaoDoLogin`, config `kit.php`, tela de Settings admin, três páginas de auth, `.env.example`

## Cobertura do Requisito

| RQ | Cláusula | Passo(s) que atende(m) | Observação |
|----|----------|------------------------|------------|
| RQ-01 | Instalar o pacote | 1 | — |
| RQ-02 | Substituir `CampoAntiRobo` pelo `Captcha::make()` nas 3 telas | 5, 6 | — |
| RQ-03 | Integração Settings → `config('captcha.*')` via bridge no `aplicarNaConfig()` | 3, 4 | — |
| RQ-04 | Suporte a reCAPTCHA v3 + score na tela de Settings | 3, 4, 6 | — |
| RQ-05 | 4 provedores funcionais | 1, 3, 6 | O pacote já traz os 4 drivers |
| RQ-06 | Logging no canal `autenticacao` | 7 | Decorator sobre `CaptchaDriver::verify()` |
| RQ-07 | Reset do token após verificação | 7 | Publish das views + `x-on:reset.window` |
| RQ-08 | Falha fechada (provider indisponível = recusa) | 7 | Decorator com try/catch no `verify()` |
| RQ-09 | Remover artefatos antigos | 8 | `CampoAntiRobo.php`, `ProvedorAntiRobo.php`, blade |
| RQ-10 | Reescrever testes | 9 | 56 Kit + 5 browser |
| RQ-11 | Compatibilidade de env vars | 3 | Bridge no `mapaDeConfiguracao()` |
| RQ-12 | Campo `login_anti_robo_score` no Settings | 3, 4 | Default 0.5 |
| RQ-13 | Views customizadas (dark mode, `data-anti-robo`) | 7 | Publish + editar views |

## Objetivo

Substituir a implementação própria de anti-robô (~300 linhas em 3 arquivos) pelo pacote `ddr/filament-captcha` (v1.x), que oferece arquitetura de drivers, suporte nativo a reCAPTCHA v3, e manutenção centralizada. A migração deve preservar o comportamento existente (falha fechada, logging, reset de token, integração com Spatie Settings) via adapters/decorators, e adicionar reCAPTCHA v3 como opção na tela de Settings.

## Contexto

A implementação atual (`CampoAntiRobo` + `ProvedorAntiRobo` + blade) cobre reCAPTCHA v2, Turnstile e hCaptcha com ~300 linhas. Funciona, mas:

1. **Não suporta reCAPTCHA v3** — decisão original era que v3 exigiria limiar e `action` configuráveis; o pacote já resolve isso
2. **Enum monolítico** — adicionar um provedor exige editar 5 métodos `match`; no pacote é criar uma classe
3. **View unificada com lógica condicional** — o pacote separa uma view por driver, mais manutenível

O pacote `ddr/filament-captcha` v1.x (4.710 installs, MIT, compatível Filament 5 + PHP 8.2+) tem 3 lacunas que precisam de adapter:
- Sem reset de token (P1)
- Sem tratamento de exceção no `verify()` (P2)
- Sem logging (P3)

## Análise dos Arquivos Existentes

### Arquivos a REMOVER (passo 8)

- `app/Filament/Forms/Components/CampoAntiRobo.php` (173 linhas) — substituído por `Captcha::make()` + adapters
- `app/Support/ProvedorAntiRobo.php` (88 linhas) — substituído pelos drivers do pacote
- `resources/views/filament/forms/components/campo-anti-robo.blade.php` (85 linhas) — substituído pelas views publicadas do pacote

### Arquivos a MODIFICAR

- `app/Settings/ConfiguracoesDoKit.php` — adicionar `login_anti_robo_score`, alterar o mapa para `captcha.*`
- `app/Support/ConfiguracaoDoLogin.php` — reescrever `antiRobo()`, `chaveDoSiteAntiRobo()`, `chaveSecretaAntiRobo()` para ler de `config('captcha.*')`
- `app/Filament/Admin/Pages/ConfiguracoesDoKit.php` — `secaoAntiRobo()` com 4 provedores + campo de score
- `app/Filament/Pages/Auth/TelaLogin.php` — trocar `CampoAntiRobo::acrescentarA()` por wrapper com `Captcha::make()`
- `app/Filament/Pages/Auth/TelaRecuperarSenha.php` — idem
- `app/Filament/Pages/Auth/RegistroPorConvite.php` — idem
- `config/kit.php` — atualizar seção `anti_robo` com `score` e mapear para o formato do pacote
- `.env.example` — documentar as novas env vars
- `database/settings/2026_08_26_100000_add_anti_robo_to_kit_settings.php` — NÃO alterar; criar migration nova

### Arquivos a CRIAR

- `app/Support/CaptchaBridge.php` — classe que alimenta `config('captcha.*')` a partir do Settings
- `app/Support/KitCaptchaManager.php` — estende `CaptchaManager` com logging + falha fechada no `createDriver()`
- `app/Support/CaptchaField.php` — wrapper sobre `Captcha::make()` com `acrescentarA()` e reset de token
- `database/settings/2026_08_31_100000_add_score_to_anti_robo_settings.php` — nova propriedade
- Views publicadas customizadas em `resources/views/vendor/filament-captcha/`

## Autorização

- Sem alteração. A tela de Settings já exige `view_configuracoes-do-kit`.

## Rotas

- Sem alteração de rotas. As 3 telas públicas permanecem em `/app/login`, `/app/register`, `/app/password-reset/request`.

## Superfície de UI

| Tela / Componente | Tipo | Rota | Interação do usuário | Depende de JS? |
|---|---|---|---|---|
| Tela de Login | Filament Page (Auth) | `/app/login` | Widget captcha renderiza; marcar/resolver antes de submeter | Sim |
| Tela de Registro | Filament Page (Auth) | `/app/register` | Idem | Sim |
| Tela de Recuperação de Senha | Filament Page (Auth) | `/app/password-reset/request` | Idem | Sim |
| Tela de Settings (Admin) | Filament Page | `/admin/configuracoes-do-kit` | Select com 4 provedores + campo de score (condicional a v3) | Não |

**Gate de CT-B**: as 3 primeiras telas dependem de JS carregado externamente para renderizar o widget. Os testes de browser existentes (CT-B01..CT-B05) precisam ser reescritos.

## Variáveis de Ambiente

| Key | Default | Descrição |
|-----|---------|-----------|
| `KIT_ANTI_ROBO` | `false` | Interruptor geral — **mantida** |
| `KIT_ANTI_ROBO_PROVEDOR` | `recaptcha_v2` | Driver (`hcaptcha`, `recaptcha_v2`, `recaptcha_v3`, `turnstile`) — **valor muda** de `recaptcha` para `recaptcha_v2` |
| `KIT_ANTI_ROBO_CHAVE_DO_SITE` | — | Chave pública — **mantida** |
| `KIT_ANTI_ROBO_CHAVE_SECRETA` | — | Chave secreta — **mantida** |
| `KIT_ANTI_ROBO_SCORE` | `0.5` | Limiar do reCAPTCHA v3 (0.0 a 1.0) — **nova** |

> O bridge traduz `KIT_ANTI_ROBO_*` → `captcha.*` no `aplicarNaConfig()`. As env vars do pacote (`CAPTCHA_DRIVER`, `RECAPTCHA_V2_SITEKEY`, etc.) ficam como fallback no `config/captcha.php` publicado.

## Eventos / Listeners / Observers

- Sem alteração.

## Jobs / Queues

- Sem alteração.

## Impacto em Features Existentes

- **Login** (3 telas): widget captcha muda de implementação — risco de regressão visual e funcional
- **Tela de Settings**: seção anti-robô ganha 4º provedor e campo de score
- **56 testes Kit `ProtecaoAntiRoboTest`**: precisam ser reescritos para referenciar o pacote
- **5 testes browser `ProtecaoAntiRoboTest`**: precisam ser reescritos para os novos seletores

## Rollback

- **Migration down**: a nova migration `add_score` tem `down()` que remove `login_anti_robo_score`
- **Feature flag**: desinstalar o pacote e reverter os arquivos; o `ProvedorAntiRobo` e `CampoAntiRobo` estão no git
- **Git revert**: `git revert` do merge commit

## Dependências

- **Composer**: `ddr/filament-captcha` `^1.0` (requer `filament/filament ^3.0|^4.0|^5.0`, `spatie/laravel-package-tools ^1.16`)

## Riscos

- **R1**: pacote jovem (4.710 installs, 5 stars) — mitigação: adapters isolam; se o pacote morrer, trocar o driver
- **R2**: valor de `provedor` muda de `recaptcha` para `recaptcha_v2` — mitigação: migration de dados com fallback
- **R3**: views publicadas divergem em futuras versões do pacote — mitigação: views são pequenas e o pacote é estável

## Channel de Log da Feature

### Verificação de Channel Existente

- Canal `autenticacao` já existe e é usado pelo `CampoAntiRobo` e `ConfiguracaoDoLogin`
- **Manter** o canal `autenticacao` para todos os logs de captcha

## Estrutura de Implementação

### 1. Instalar o pacote e publicar assets

> Skills: `laravel-best-practices`
> Atende: RQ-01

- `composer require ddr/filament-captcha`
- `php artisan vendor:publish --tag=captcha-config` → `config/captcha.php`
- `php artisan vendor:publish --tag=filament-captcha-views` → `resources/views/vendor/filament-captcha/`
- Verificar que o `FilamentCaptchaServiceProvider` registra o `CaptchaManager` como singleton
- **Logs**: nenhum neste passo

### 2. Migrar o valor do provedor no banco de dados

> Skills: `laravel-best-practices`
> Atende: RQ-11

- **Path**: `database/settings/2026_08_31_100000_migrate_anti_robo_provedor_value.php`
- Criar migration que converte `login_anti_robo_provedor = 'recaptcha'` → `'recaptcha_v2'`
- O `down()` reverte `'recaptcha_v2'` → `'recaptcha'`
- Rodar `php artisan migrate`

### 3. Adicionar propriedade `login_anti_robo_score` ao Settings + atualizar mapa

> Skills: `laravel-best-practices`
> Atende: RQ-03, RQ-04, RQ-11, RQ-12

- **Path**: `database/settings/2026_08_31_100001_add_score_to_anti_robo_settings.php`
- Adicionar `login_anti_robo_score` com default `0.5` (float)
- **Path**: `app/Settings/ConfiguracoesDoKit.php`
  - Adicionar propriedade `public float $login_anti_robo_score;`
  - Atualizar `mapaDeConfiguracao()`: as 4 chaves existentes passam a mapear para `captcha.*`:
    ```
    'login_anti_robo_habilitado'    => NÃO mapear (é flag do kit, não do pacote)
    'login_anti_robo_provedor'      => 'captcha.driver'
    'login_anti_robo_chave_do_site' => depende do driver (bridge resolve)
    'login_anti_robo_chave_secreta' => depende do driver (bridge resolve)
    'login_anti_robo_score'         => 'captcha.recaptcha_v3.score'
    ```
  - O mapa direto não funciona aqui porque a mesma site key precisa ir para o driver correto (`captcha.hcaptcha.sitekey` vs `captcha.recaptcha_v2.sitekey`). Solução: a bridge resolve isso no `aplicarNaConfig()`
- **Logs**: nenhum neste passo

### 4. Criar o `CaptchaBridge` — traduz Settings para `config('captcha.*')`

> Skills: `laravel-best-practices`
> Atende: RQ-03, RQ-04, RQ-05, RQ-11

- **Path**: `app/Support/CaptchaBridge.php`
- Classe `final` com método estático `aplicar(): void`
- Chamada no `ConfiguracoesDoKit::aplicarNaConfig()` APÓS o mapa padrão
- Lógica:
  1. Lê `config('kit.login.anti_robo.provedor')` (já sobrescrito pelo mapa) para saber o driver ativo
  2. Alimenta `config('captcha.driver')` com o driver
  3. Alimenta `config("captcha.{$driver}.sitekey")` com `config('kit.login.anti_robo.chave_do_site')`
  4. Alimenta `config("captcha.{$driver}.secret")` com `config('kit.login.anti_robo.chave_secreta')`
  5. Alimenta `config('captcha.recaptcha_v3.score')` com `config('kit.login.anti_robo.score')` (se driver é `recaptcha_v3`)
- As chaves de config do kit (`kit.login.anti_robo.*`) continuam existindo como fonte canônica; o bridge projeta para o formato do pacote
- **Logs**: nenhum neste passo (o `aplicarNaConfig()` já loga o alinhamento geral)

### 5. Criar o `CaptchaField` — wrapper para as 3 telas

> Skills: `laravel-best-practices`, `filament`
> Atende: RQ-02, RQ-07

- **Path**: `app/Support/CaptchaField.php`
- Classe `final` com:
  - `public static function acrescentarA(Schema $schema): Schema` — mesma API de antes
  - Dentro: `Captcha::make('anti_robo')` do pacote, com:
    - `->hiddenLabel()`
    - `->validationAttribute('verificação anti-robô')`
    - `->visible(fn (): bool => ConfiguracaoDoLogin::antiRobo() !== null)`
    - `->extraFieldWrapperAttributes(['class' => 'fi-fo-anti-robo'])` — mantém o seletor CSS dos testes
  - O reset do token é tratado na view customizada (passo 7)
- **Logs**: nenhum neste passo (a validação fica no driver via decorator)

### 6. Atualizar as 3 telas de auth + Settings + ConfiguracaoDoLogin

> Skills: `laravel-best-practices`, `filament`
> Atende: RQ-02, RQ-04, RQ-05

- **Path**: `app/Filament/Pages/Auth/TelaLogin.php`
  - Trocar `use App\Filament\Forms\Components\CampoAntiRobo` por `use App\Support\CaptchaField`
  - `return CaptchaField::acrescentarA(parent::form($schema));`
- **Path**: `app/Filament/Pages/Auth/TelaRecuperarSenha.php` — idem
- **Path**: `app/Filament/Pages/Auth/RegistroPorConvite.php` — idem
- **Path**: `app/Support/ConfiguracaoDoLogin.php`
  - `antiRobo()`: retorna `?string` (nome do driver) em vez de `?ProvedorAntiRobo`
  - Drivers válidos: `['hcaptcha', 'recaptcha_v2', 'recaptcha_v3', 'turnstile']`
  - Mesma lógica: habilitado + chave preenchida + driver válido = driver; senão `null`
  - `chaveDoSiteAntiRobo()` e `chaveSecretaAntiRobo()`: sem alteração de lógica, leem da mesma config
- **Path**: `app/Filament/Admin/Pages/ConfiguracoesDoKit.php`
  - `secaoAntiRobo()`:
    - Select de provedores: 4 opções (`hcaptcha`, `recaptcha_v2`, `recaptcha_v3`, `turnstile`) com labels pt-BR
    - Campo `login_anti_robo_score`: `TextInput::make('login_anti_robo_score')->numeric()->minValue(0)->maxValue(1)->step(0.1)->visible(fn (Get $get) => $get('login_anti_robo_provedor') === 'recaptcha_v3')`
    - `helperText` do provedor atualizado com instruções do reCAPTCHA v3
- **Path**: `config/kit.php`
  - Adicionar `'score' => env('KIT_ANTI_ROBO_SCORE', 0.5)` à seção `anti_robo`
  - Alterar default de `provedor` de `'recaptcha'` para `'recaptcha_v2'`
- **Logs**:
  - `Log::channel('autenticacao')->warning('[ConfiguracaoDoLogin@antiRobo] Driver anti-robô desconhecido | driver: {valor}')` (mantém o log existente)

### 7. Adapters: logging, falha fechada, reset de token, views customizadas

> Skills: `laravel-best-practices`
> Atende: RQ-06, RQ-07, RQ-08, RQ-13

#### 7a. `KitCaptchaManager` — estende `CaptchaManager` com logging + falha fechada

- **Path**: `app/Support/KitCaptchaManager.php`
- Estende `Ddr\FilamentCaptcha\CaptchaManager`, sobrescreve `createDriver()` para envolver o driver retornado com try/catch + logging
- O wrapper (classe anônima ou classe interna) sobrescreve `verify(string $token): bool`:
  ```php
  try {
      $resultado = $this->inner->verify($token);
  } catch (Throwable $e) {
      Log::channel('autenticacao')->warning(
          '[KitCaptchaManager@verify] Verificação indisponível — envio recusado | driver: ...',
          ['exception' => $e, ...]
      );
      return false;
  }
  if (!$resultado) {
      Log::channel('autenticacao')->warning(
          '[KitCaptchaManager@verify] Token recusado pelo provedor | driver: ...',
          [...]
      );
  }
  return $resultado;
  ```
- Registrar no `AppServiceProvider`: `$this->app->singleton(CaptchaManager::class, fn () => new KitCaptchaManager())`

#### 7b. Views customizadas com reset + dark mode + data-anti-robo

- **Path**: `resources/views/vendor/filament-captcha/drivers/recaptcha-v2.blade.php`
  - Adicionar `x-on:kit-anti-robo-redefinir.window` que chama `grecaptcha.reset()`
  - Adicionar detecção de `theme: dark/light`
  - Adicionar `data-anti-robo="recaptcha_v2"` no container
  - Manter `class="fi-fo-anti-robo"` para os seletores de teste
- Repetir para `recaptcha-v3.blade.php`, `turnstile.blade.php`, `hcaptcha.blade.php`
- A view `recaptcha-v3` já não tem checkbox visível — o token é gerado automaticamente

#### 7c. Reset de token via dispatch

- No `CaptchaField`, adicionar regra de validação que faz `dispatch('kit-anti-robo-redefinir')` após verificação
- Ou: estender `Captcha::make()` com `->rules([fn () => ...])` que adiciona o dispatch
- A escolha entre estender a classe ou adicionar rule depende da API do pacote

- **Logs**:
  - Os logs estão no decorator (7a)

### 8. Remover artefatos antigos

> Skills: `laravel-best-practices`
> Atende: RQ-09

- Remover `app/Filament/Forms/Components/CampoAntiRobo.php`
- Remover `app/Support/ProvedorAntiRobo.php`
- Remover `resources/views/filament/forms/components/campo-anti-robo.blade.php`
- Buscar e corrigir quaisquer referências remanescentes (imports, docblocks, wikis)

### 9. Reescrever testes

> Skills: `pest-testing`
> Atende: RQ-10

- **Path**: `tests/Kit/ProtecaoAntiRoboTest.php`
  - Substituir referências a `CampoAntiRobo` e `ProvedorAntiRobo`
  - Testar com os 4 drivers do pacote
  - Adicionar cenários para reCAPTCHA v3 (score)
  - Testar o decorator (logging, falha fechada)
  - Testar o bridge (Settings → `config('captcha.*')`)
- **Path**: `tests/Browser/ProtecaoAntiRoboTest.php`
  - Atualizar seletores para as views publicadas
  - Adicionar teste para reCAPTCHA v3 (widget invisível, sem checkbox)
  - Manter CT-B para Turnstile
- **Logs**: nenhum neste passo

### 10. Limpeza final

> Skills: `laravel-best-practices`

- `vendor/bin/pint --dirty --format agent`
- `php artisan test --testsuite=Unit,Feature,Kit,Tenancy --compact`
- `composer test:browser`
- Atualizar `.env.example` com as novas env vars documentadas
- Atualizar o `README` da seção anti-robô (se existir)

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

## Testes

> Ver `04-casos-de-teste.md` para especificação completa dos cenários de backend.
> Ver `05-casos-de-teste-browser.md` para os cenários de UI.

## Verificação Final

- [ ] `/ponytail:ponytail-review` no diff
- [ ] `vendor/bin/pint --dirty --format agent`
- [ ] `php artisan test --testsuite=Kit --filter=ProtecaoAntiRobo --compact`
- [ ] `php artisan test --testsuite=Kit --filter=ConfiguracoesDoKit --compact`
- [ ] `composer test:browser`
- [ ] `php artisan test --testsuite=Unit,Feature,Kit,Tenancy --compact` — base não cair
- [ ] `vendor/bin/phpstan analyse` — 0 erros
- [ ] Roteiro "Desenhado × Implementado" do `05` preenchido
- [ ] `git commit` por bloco concluído

## Commits

- `:memo: captcha: wiki da feature adotar-ddr-filament-captcha`
- `:heavy_plus_sign: captcha: instalar ddr/filament-captcha e publicar assets`
- `:card_file_box: captcha: migration de valor do provedor e score`
- `:recycle: captcha: bridge, field wrapper e decorator com logging/reset`
- `:recycle: captcha: atualizar telas de auth e Settings para usar o pacote`
- `:fire: captcha: remover CampoAntiRobo, ProvedorAntiRobo e blade antigos`
- `:white_check_mark: captcha: reescrever testes Kit e browser para o pacote`
