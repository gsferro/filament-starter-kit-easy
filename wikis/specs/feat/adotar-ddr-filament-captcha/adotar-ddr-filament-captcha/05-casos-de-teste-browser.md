# Casos de Teste de Browser — Adotar `ddr/filament-captcha`

> Runtime: `pest-plugin-browser` (Playwright). O plugin sobe o próprio servidor.
> Comando: `vendor/bin/pest --testsuite=Browser` (em série — nunca `--parallel`)

## Pré-requisitos

- [ ] `npm run build` executado
- [ ] `tests/Browser/Screenshots` no `.gitignore`
- [ ] Autenticação por `$this->actingAs($user)` — desnecessário aqui (telas públicas)

## Gate de CT-B

As 3 telas de auth carregam script externo (Google reCAPTCHA, Cloudflare Turnstile, hCaptcha) e renderizam o widget via JavaScript + Alpine.js. O iframe/widget e o token gerado pelo JS não são verificáveis por componente Livewire — só o navegador prova que o script carrega, o widget renderiza e o token é capturado.

## Seletores

| Elemento | Seletor | Já existe? |
|---|---|---|
| Container do widget | `[data-anti-robo="{driver}"]` | sim (view customizada, mantido) |
| Container CSS | `.fi-fo-anti-robo` | sim (mantido) |
| Iframe do reCAPTCHA v2 | `iframe[title="reCAPTCHA"]` | sim (existente nos testes browser atuais) |
| Iframe do Turnstile | `iframe[src*="turnstile"]` | não (seletor novo) |
| Widget invisível do v3 | sem iframe visível; badge `.grecaptcha-badge` | não (seletor novo) |
| Widget do hCaptcha | `iframe[data-hcaptcha-widget-id]` | não (seletor novo) |

## Teto

- Perfil padrão: 1 happy path + 0 erro visível
- **Completo** para verificação (R5): 1 happy path + 1 erro visível
- Total: 5 CT-B (3 drivers visuais × 1 happy path + reCAPTCHA v3 invisível + persistência)

---

## CT-B01: Widget reCAPTCHA v2 renderiza nas 3 telas

**Por que browser e não Livewire**: o widget é um iframe carregado por script externo do Google; componente Livewire não executa JS.

```gherkin
# language: pt

  Esquema do Cenário: [CT-B01] widget reCAPTCHA v2 renderiza na tela pública
    Dado a proteção ligada com driver "recaptcha_v2" e chaves de teste do Google
    Quando a tela "<rota>" é visitada
    Então o container com data-anti-robo="recaptcha_v2" é visível
    E um iframe com title="reCAPTCHA" está presente

    Exemplos:
      | rota                           |
      | /app/login                     |
      | /app/password-reset/request    |
      | /app/register?token={convite}  |
```

**Roteiro executável**

| # | Ação | Código Pest | Resultado visível |
|---|---|---|---|
| 1 | Configurar chaves de teste reCAPTCHA v2 no banco | `beforeEach` com Settings | — |
| 2 | Visitar a tela | `visit($rota)` | Tela carrega com widget |
| 3 | Verificar container | `->assertVisible('[data-anti-robo="recaptcha_v2"]')` | Container presente |
| 4 | Verificar iframe | `->assertVisible('iframe[title="reCAPTCHA"]')` | Iframe presente |

**Assertions**: `assertVisible` no container · `assertVisible` no iframe · `assertNoJavaScriptErrors()`

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| MB1 | View publicada não carrega o script do reCAPTCHA | CT-B01 (iframe ausente) |
| MB2 | `data-anti-robo` ausente na view customizada | CT-B01 (container ausente) |

---

## CT-B02: Widget Turnstile renderiza no login

**Por que browser e não Livewire**: widget Turnstile é renderizado por script externo do Cloudflare.

```gherkin
# language: pt

  Cenário: [CT-B02] widget Turnstile renderiza na tela de login
    Dado a proteção ligada com driver "turnstile" e chaves de teste do Cloudflare
    Quando a tela /app/login é visitada
    Então o container com data-anti-robo="turnstile" é visível
```

**Roteiro executável**

| # | Ação | Código Pest | Resultado visível |
|---|---|---|---|
| 1 | Configurar chaves de teste Turnstile no banco | `beforeEach` com Settings | — |
| 2 | Visitar login | `visit('/app/login')` | Tela com widget Turnstile |
| 3 | Verificar container | `->assertVisible('[data-anti-robo="turnstile"]')` | Container presente |

**Assertions**: `assertVisible` no container · `assertNoJavaScriptErrors()`

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| MB3 | View do Turnstile com erro no script src | CT-B02 (container não renderiza) |

---

## CT-B03: reCAPTCHA v3 invisível — badge presente, sem checkbox

**Por que browser e não Livewire**: o badge do reCAPTCHA v3 é renderizado pelo script do Google; é CSS + JS externo.

```gherkin
# language: pt

  Cenário: [CT-B03] reCAPTCHA v3 carrega badge invisível sem checkbox
    Dado a proteção ligada com driver "recaptcha_v3" e chaves de teste do Google v3
    Quando a tela /app/login é visitada
    Então o badge .grecaptcha-badge está presente no DOM
    E nenhum iframe com title="reCAPTCHA" é visível (v3 é invisível)
```

**Roteiro executável**

| # | Ação | Código Pest | Resultado visível |
|---|---|---|---|
| 1 | Configurar chaves de teste v3 | `beforeEach` com Settings | — |
| 2 | Visitar login | `visit('/app/login')` | Tela sem checkbox visível |
| 3 | Verificar badge | `assertPresent('.grecaptcha-badge')` | Badge no DOM |

**Assertions**: `assertPresent` do badge · `assertNoJavaScriptErrors()`

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| MB4 | View do v3 carrega o script do v2 (checkbox aparece) | CT-B03 (iframe visível = falha) |

---

## CT-B04: Widget persiste após erro de validação

**Por que browser e não Livewire**: o re-render do Livewire pode destruir o widget JS; `wire:ignore` precisa funcionar no navegador real.

```gherkin
# language: pt

  Cenário: [CT-B04] widget reCAPTCHA v2 persiste após erro de validação
    Dado a proteção ligada com driver "recaptcha_v2" e chaves de teste
    Quando a tela /app/login é visitada
    E o formulário é enviado com campo de e-mail vazio (erro de validação)
    Então o container com data-anti-robo="recaptcha_v2" continua visível
    E o iframe com title="reCAPTCHA" continua presente
```

**Roteiro executável**

| # | Ação | Código Pest | Resultado visível |
|---|---|---|---|
| 1 | Visitar login | `visit('/app/login')` | Widget renderiza |
| 2 | Submeter sem e-mail | `->press('Entrar')` | Erro de validação |
| 3 | Verificar container | `->assertVisible('[data-anti-robo="recaptcha_v2"]')` | Widget ainda lá |
| 4 | Verificar iframe | `->assertVisible('iframe[title="reCAPTCHA"]')` | Iframe ainda lá |

**Assertions**: `assertVisible` pós-erro · `assertNoJavaScriptErrors()`

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| MB5 | `wire:ignore` ausente na view publicada | CT-B04 (widget desaparece após re-render) |

---

## CT-B05: Proteção desligada não carrega script no browser

**Por que browser e não Livewire**: confirmar que nenhum script externo é injetado no `<head>` pelo JS — algo que componente Livewire não vê.

```gherkin
# language: pt

  Cenário: [CT-B05] proteção desligada não carrega script de provedor
    Dado a proteção desligada
    Quando a tela /app/login é visitada
    Então nenhum elemento com class fi-fo-anti-robo está presente
    E nenhum script de provedor de captcha está no DOM
```

**Roteiro executável**

| # | Ação | Código Pest | Resultado visível |
|---|---|---|---|
| 1 | Proteção desligada (default) | — | — |
| 2 | Visitar login | `visit('/app/login')` | Tela sem widget |
| 3 | Verificar ausência | `->assertMissing('.fi-fo-anti-robo')` | Container ausente |

**Assertions**: `assertMissing` · `assertNoJavaScriptErrors()`

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| MB6 | Script carregado incondicionalmente no `<head>` | CT-B05 |

---

## Cogitado e Cortado

| Cenário cogitado | Por que foi cortado |
|---|---|
| CT-B hCaptcha renderiza | mesmo protocolo que reCAPTCHA v2, já provado por CT-B01 e CT-B02. Adicionaria 1 CT-B sem novo mutante |
| CT-B dark mode no widget | widget de terceiro não respeita tema CSS; `theme` é passado como parâmetro do `render()` e verificado visualmente, não por assertion automática |
| CT-B reset de token após erro | reset é dispatch Livewire → evento JS; componente Livewire já prova o dispatch (CT-14). O efeito JS (widget resetar) depende do provedor e não tem assertion estável |

---

## Roteiro de Validação: Desenhado × Implementado

| # | O que o PRD desenhou | O que foi implementado | Confere? | Evidência |
|---|---|---|---|---|
| 1 | Widget renderiza nas 3 telas | reCAPTCHA v2 com chaves de teste do Google em login, recuperação e registro aberto | sim | `tests/Browser/ProtecaoAntiRoboTest.php` · CT-B01/B02/B03 (`renderiza o widget recaptcha na tela de ...`) |
| 2 | Turnstile renderiza | chaves de teste do Cloudflare (`1x000...AA`) no login; **e** chaves reais do solicitante (`0x4AAAAAAEg-8n2jt9JkRG14`) em `localhost:8765` com `local` ligado | sim | CT-B05 `renderiza o widget turnstile na tela de login`; teste manual via Playwright (2026-08-29, screenshot `turnstile-login.png`) |
| 3 | reCAPTCHA v3 é invisível | badge `.grecaptcha-badge` presente, nenhum `iframe[title="reCAPTCHA"]`, container de 0 px (`assertPresent`) | sim | `renderiza o recaptcha v3 invisivel, com badge e sem caixa` |
| 4 | Widget persiste após erro | submit vazio → iframe continua (`wire:ignore` da view publicada) | sim | CT-B04 `mantem o widget visivel apos erro de validacao` |
| 5 | Proteção desligada = sem script | sete telas sem host de script nem `anti_robo` | sim (camada Kit, não browser) | `tests/Kit/ProtecaoAntiRoboTest.php` · `não carrega script de provedor nenhum ...` — HTTP prova ausência no HTML; browser não acrescenta oráculo |

Suíte: `php artisan test tests/Browser/ProtecaoAntiRoboTest.php` → 6 passed (90 s), após `npm run build` + `view:cache`.
