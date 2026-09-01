# Progresso — Proteção anti-robô nas telas públicas

Worktree isolado a partir da `main` + merge de `fix/kit-install-force-semeia-do-env` (PR #45), em
2026-08-26. Branch `feat/recaptcha-nas-telas-publicas`.

## 1. Config e `.env.example`

- [x] `config/kit.php` → `login.anti_robo.*`
- [x] `.env.example` → bloco `KIT_ANTI_ROBO*`

## 2. Enum do provedor e ponto único de leitura

- [x] `app/Support/ProvedorAntiRobo.php`
- [x] `ConfiguracaoDoLogin::antiRobo()`, `chaveDoSiteAntiRobo()`, `chaveSecretaAntiRobo()`

## 3. Settings: propriedades, mapa, cifra e migration

- [x] quatro propriedades + `mapaDeConfiguracao()` + `encrypted()`
- [x] `database/settings/2026_08_26_100000_add_anti_robo_to_kit_settings.php`

## 4. O campo de formulário e a view

- [x] `app/Filament/Forms/Components/CampoAntiRobo.php`
- [x] `resources/views/filament/forms/components/campo-anti-robo.blade.php`

## 5. As três páginas

- [x] `TelaLogin::form()`
- [x] `RegistroPorConvite::form()`
- [x] `app/Filament/Pages/Auth/TelaRecuperarSenha.php`

## 6. Os três `PanelProvider`

- [x] `usingPage(TelaLogin::class)` no login de `/admin` e `/infra`
- [x] `usingPage(TelaRecuperarSenha::class)` na recuperação dos três

## 7. A tela de Settings

- [x] seção "Proteção anti-robô" em `abaLogin()`
- [x] `mutateFormDataBeforeFill()` zera a chave secreta

## 8. Testes

- [x] `tests/Kit/ProtecaoAntiRoboTest.php` — CT-01..CT-18
- [x] regressão: `TelasDeAutenticacaoTest`, `BloqueioDeSessaoTest`, `ConviteTest`, `RegistroAbertoTest`, `LoginSocialProvedoresTest`, `SegredosDoSettingsTest`, `ConfiguracoesDoKitTest`, `ConfiguracoesDoKitTelaTest`
- [x] mutação manual (três mutantes do plano)

## 9. README

- [x] `README.md`
- [x] `README.en.md`

## Verificação Final

- [x] `/ponytail:ponytail-review` no diff
- [x] `vendor/bin/pint --dirty --format agent`
- [x] `vendor/bin/phpstan analyse <tocados> --no-progress`
- [x] testes novos + regressão
- [x] mergeado na `main` via `feat/recaptcha-nas-telas-publicas`

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

- O enum abrange três provedores (reCAPTCHA v2, Turnstile, hCaptcha) em vez de um só: o protocolo é idêntico, o custo foi três URLs/nomes por caso, e o ganho cobre quem já usa Cloudflare ou prefere hCaptcha.
- A página de redefinição de senha que o link do e-mail abre não recebeu desafio: ela exige token assinado, então foi considerado fora do escopo do RQ-01.

## Notas de Implementação

- `CampoAntiRobo::acrescentarA()` retorna o schema original quando a proteção está desligada, deixando as telas inalteradas.
- O campo é `dehydrated(false)` para não poluir `$data` que chega a `Convite::aceitar()` / `RegistroAberto::registrar()`, mas continua validado.
- Após qualquer verificação, dispara `kit-anti-robo-redefinir` para resetar o widget, porque o token é de uso único.
- reCAPTCHA v3 foi deliberadamente deixado de fora: ele devolve pontuação, exigiria limiar e `action` por tela; ver seção dedicada no `README.md`/`README.en.md` e possível feature futura.

## Pendências para quem valida no navegador

- [x] Ligar a proteção com as chaves de teste do reCAPTCHA (site `6LeIxAcTAAAAAJcZVRqyHh71UMIEGNQ_MXjiZKhI`,
  secreta `6LeIxAcTAAAAAGG-vFI1TnRWxMZNFuojJ4WifJWe`) e confirmar nas três telas: widget renderiza,
  envio sem marcar reprova, marcar e enviar passa, errar a senha e enviar de novo funciona (o widget
  se redefine). — Validado via `tests/Browser/ProtecaoAntiRoboTest.php` (CT-B01..CT-B04).
- [x] Repetir com Turnstile (`1x00000000000000000000AA` / `1x0000000000000000000000000000000AA`). — Validado via CT-B05.
- [x] Capturas para o README via `kit:arte`. — `art/login-anti-robo.png` (v0.22.0). A captura nasce do próprio `tests/Browser/ProtecaoAntiRoboTest.php`, guardada por `KIT_ART`, e não de um cenário separado: o arranjo que prova o widget é o mesmo que a tela precisa ter na foto. Declarada em `KitArte::IMAGENS` e incluída no script `composer art`.

## Bug corrigido durante validação no browser

- **`campo-anti-robo.blade.php:74`** — `@js($provedor->value)` no final da linha não garante newline
  no HTML compilado pela Blade; a instrução seguinte (`document.head.appendChild(script)`) ficava na
  mesma expressão JS, causando `Uncaught SyntaxError: Unexpected identifier 'document'` e impedindo
  o script do reCAPTCHA de ser injetado. Corrigido com `;` explícito após `@js()`.

## Retrospectiva

- Feature mergeada e testada. reCAPTCHA/Turnstile/hCaptcha compartilham enum e campo.
- Bug de sintaxe JS corrigido na blade (`@js()` sem `;` concatenava duas instruções).
- Testes de browser (CT-B01..CT-B05) cobrem renderização nas três telas públicas + persistência após
  erro de validação + Turnstile. A dívida das capturas foi fechada na v0.22.0.

## Pós-entrega — o default do provedor e a captura (v0.22.0 → v0.22.3)

- **O default do provedor passou a `recaptcha_v3`** (v0.22.0) e, **na v0.22.2, passou a valer de
  fato**: o `.env.example` fixava `KIT_ANTI_ROBO_PROVEDOR=recaptcha_v2` e `env()` vence o default do
  config, então instalação nova nascia com o v2 e a mudança era inócua. Achado com
  `config:show kit.login.anti_robo` numa instalação do pacote publicado — a suíte não pega isso, ela
  roda com o `.env` de teste. **Nada disso liga a proteção**: ela continua nascendo desligada, e sem
  as duas chaves segue desligada mesmo com o toggle ligado.
- **A captura do README é a seção das configurações** (`art/admin-anti-robo.png`), e não o widget na
  tela de login. Motivo medido nos dois provedores: **todo provedor marca a própria chave de teste**
  — o Google desenha um banner vermelho sobre o widget ("This reCAPTCHA is for testing purposes
  only") e a Cloudflare uma faixa embaixo ("For testing only. If seen, report to site owner"). Foto
  de README com aviso de erro ensina a coisa errada, e chave real com domínio autorizado é coisa que
  o kit não tem. A tela de Settings mostra o que interessa — toggle, provedor, chaves, pontuação
  mínima — sem depender de terceiro, e é onde o README manda ir para ligar.
- **A captura usa `screenshotElement()`**, e não a viewport: a seção fica abaixo da dobra, clicar
  nela a **colapsa** (as seções nascem abertas, `->collapsible()` sem `->collapsed()`) e clicar na
  seção de cima não a fecha — o texto do título não é o gatilho. Duas tentativas antes de chegar
  nisso, ambas registradas no comentário do cenário em `tests/BrowserTenancy/CapturaDeArteTest.php`.
