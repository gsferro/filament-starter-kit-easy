# Progresso — Proteção anti-robô nas telas públicas

Worktree isolado a partir da `main` + merge de `fix/kit-install-force-semeia-do-env` (PR #45), em
2026-08-26. Branch `feat/recaptcha-nas-telas-publicas`.

## 1. Config e `.env.example`

- [ ] `config/kit.php` → `login.anti_robo.*`
- [ ] `.env.example` → bloco `KIT_ANTI_ROBO*`

## 2. Enum do provedor e ponto único de leitura

- [ ] `app/Support/ProvedorAntiRobo.php`
- [ ] `ConfiguracaoDoLogin::antiRobo()`, `chaveDoSiteAntiRobo()`, `chaveSecretaAntiRobo()`

## 3. Settings: propriedades, mapa, cifra e migration

- [ ] quatro propriedades + `mapaDeConfiguracao()` + `encrypted()`
- [ ] `database/settings/2026_08_26_100000_add_anti_robo_to_kit_settings.php`

## 4. O campo de formulário e a view

- [ ] `app/Filament/Forms/Components/CampoAntiRobo.php`
- [ ] `resources/views/filament/forms/components/campo-anti-robo.blade.php`

## 5. As três páginas

- [ ] `TelaLogin::form()`
- [ ] `RegistroPorConvite::form()`
- [ ] `app/Filament/Pages/Auth/TelaRecuperarSenha.php`

## 6. Os três `PanelProvider`

- [ ] `usingPage(TelaLogin::class)` no login de `/admin` e `/infra`
- [ ] `usingPage(TelaRecuperarSenha::class)` na recuperação dos três

## 7. A tela de Settings

- [ ] seção "Proteção anti-robô" em `abaLogin()`
- [ ] `mutateFormDataBeforeFill()` zera a chave secreta

## 8. Testes

- [ ] `tests/Kit/ProtecaoAntiRoboTest.php` — CT-01..CT-18
- [ ] regressão: `TelasDeAutenticacaoTest`, `BloqueioDeSessaoTest`, `ConviteTest`, `RegistroAbertoTest`, `LoginSocialProvedoresTest`, `SegredosDoSettingsTest`, `ConfiguracoesDoKitTest`, `ConfiguracoesDoKitTelaTest`
- [ ] mutação manual (três mutantes do plano)

## 9. README

- [ ] `README.md`
- [ ] `README.en.md`

## Verificação Final

- [ ] `/ponytail:ponytail-review` no diff
- [ ] `vendor/bin/pint --dirty --format agent`
- [ ] `vendor/bin/phpstan analyse <tocados> --no-progress`
- [ ] testes novos + regressão
- [ ] `git push -u origin feat/recaptcha-nas-telas-publicas`

## Auditoria Pré-Implementação

### Degradação declarada — `feature-test-design`

A skill existe em `.claude/skills/feature-test-design/` e foi **lida e seguida pelo mesmo agente**
que escreveu o PRD, sem sub-agente: perfil de risco, SFDIPOT, mapa de regras, técnica por regra,
mutantes e taxonomia estão no `04`. A derivação partiu do `00`; o PRD entrou só para nomes. O que
se perde sem o segundo agente é a cegueira independente — mitigada pela seção "Fronteira com o
Plano" do `04`, que lista o que foi recusado como oráculo.

### Revisão profunda (step 5) — premissas do plano contra o código real

| Premissa do plano | O código real diz | Correção aplicada na wiki |
|---|---|---|
| "campo oculto não é validado" | `CanBeValidated::getValidationRules()` itera `withHidden: true` mas pula `isNeitherDehydratedNorValidated()`, que é `true` para oculto sem `dehydratedWhenHidden()` (`HasState.php:801-821`) | ADR-05 cita `file:line`; premissa confirmada |
| "`dehydrated(false)` ainda valida" | `isNeitherDehydratedNorValidated()` devolve `false` quando `isValidatedWhenNotDehydrated()` (default `true`) | ADR-06 confirmada |
| "o Auth Designer resolve a página de reset por `usingPage()`" | `AuthDesignerPlugin::register()` chama `$panel->passwordReset($this->getRequestPasswordResetPageClass(), $this->getResetPasswordPageClass())`; `usingPage()` alimenta a primeira | passo 6 escreve "a de redefinição continua a do vendor" |
| "`Login::form()` do vendor lista três componentes" | `Login.php:244-252`: e-mail, senha, lembrar | confirmado |
| "`RequestPasswordReset::form()` só tem o e-mail" | `RequestPasswordReset.php:141-147` | confirmado |
| "o `/admin` e o `/infra` usam a `Login` do vendor" | `AdminPanelProvider.php:131-137` e `InfraPanelProvider.php:187-193` sem `usingPage` | confirmado; passo 6 |
| "`KIT_ANTI_ROBO` não está no `phpunit.xml`" | só `KIT_COR_PRIMARIA`, `KIT_DEMO`, `KIT_HUB`, `KIT_TENANCY_*` | CT-01 mede o default real |
| "canal `autenticacao` existe" | `config/logging.php:132` | confirmado; sem canal novo |
| "a migration mais recente é `2026_08_26_000000`" | sim (`add_login_vinculo_confirmar`) | a nova é `2026_08_26_100000` |
| "`Field::make()` aceita nome default" | `Field::make(?string $name = null)` usa `static::getDefaultName()` (`Field.php:73-99`) | `CampoAntiRobo::make()` sem argumento |
| "`Schema::getComponents()` aceita `withHidden`" | `getComponents(bool $withActions = true, bool $withHidden = false, bool $withOriginalKeys = false)` (`HasComponents.php:309`) | `acrescentarA()` usa `withHidden: true` |

### Auditoria Ponytail (step 6)

| # | Sugestão de corte | Aplicada? | Onde |
|---|---|---|---|
| 1 | Canal de log próprio da feature (a skill manda criar) | **recusada**: o canal `autenticacao` já é o de toda decisão de entrada; separar dificultaria a investigação | `01`, "Channel de Log" |
| 2 | Trait `AcrescentaCampoAntiRobo` para as três páginas | aplicada: virou o método estático `CampoAntiRobo::acrescentarA()` — uma linha por página, sem trait | `01`, passo 4 |
| 3 | Classe `VerificadorAntiRobo` separada do campo | aplicada: a verificação é um método privado do campo; só o campo a chama | `01`, passo 4 |
| 4 | `retry` na chamada ao provedor | recusada e registrada em ADR-04 | `02` |
| 5 | Oferecer só reCAPTCHA (um provedor) | recusada: os dois extras custam três linhas por método do enum e o protocolo é o mesmo (ADR-02) | `02` |
| 6 | Página de reset com `->form()` copiando o `getEmailFormComponent()` do vendor | aplicada: `parent::form()` + `acrescentarA()`; nada copiado | `01`, passo 5 |

## Blockers

- (nenhum)

## Desvios do Plano

- (preencher na implementação)

## Notas de Implementação

- (preencher na implementação)

## Pendências para quem valida no navegador

- Ligar a proteção com as chaves de teste do reCAPTCHA (site `6LeIxAcTAAAAAJcZVRqyHh71UMIEGNQ_MXjiZKhI`,
  secreta `6LeIxAcTAAAAAGG-vFI1TnRWxMZNFuojJ4WifJWe`) e confirmar nas três telas: widget renderiza,
  envio sem marcar reprova, marcar e enviar passa, errar a senha e enviar de novo funciona (o widget
  se redefine).
- Repetir com Turnstile (`1x00000000000000000000AA` / `1x0000000000000000000000000000000AA`).
- Capturas para o README via `kit:arte`.

## Retrospectiva

- (preencher ao final)
