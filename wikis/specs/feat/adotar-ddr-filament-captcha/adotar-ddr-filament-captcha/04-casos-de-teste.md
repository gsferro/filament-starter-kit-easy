# Casos de Teste — Adotar `ddr/filament-captcha`

> Requisito: `00-requisito.md` · Plano: `01-plano-acao.md`
> Derivado do **requisito**, não do plano. Nenhum cenário foi escrito olhando implementação.

## Perfil de Derivação

| Área | P | I | P×I | Perfil |
|---|---|---|---|---|
| Bridge (Settings → captcha config) | 2 | 3 | 6 | padrão |
| Ativação/desativação (4 condições) | 2 | 3 | 6 | padrão |
| Verificação de token (4 drivers) | 3 | 3 | 9 | completo |
| Reset de token | 2 | 2 | 4 | padrão |
| Tela de Settings (score + provedor) | 1 | 2 | 2 | mínimo |
| Remoção de artefatos antigos | 1 | 1 | 1 | mínimo |

- Técnicas aplicadas: EP, BVA (score), tabela de decisão (ativação), rastreio de efeito (logging)
- Cenários: 24 · Regras: 10 · Mutantes previstos: 28 · Sem matador: 0

## Varredura SFDIPOT

| Letra | O que existe nesta feature | Cenários gerados |
|---|---|---|
| **S** | `CaptchaBridge`, `KitCaptchaManager`, `CaptchaField`, migration de settings, views publicadas | CT-01..CT-06 (bridge), CT-07..CT-13 (ativação + verificação) |
| **F** | Alimentar `config('captcha.*')` a partir de Settings; verificar token com logging e falha fechada; mostrar/ocultar campo; reset de token | CT-01..CT-20 |
| **D** | driver (string enum: 4 valores), sitekey, secret (cifrada), score (float 0.0..1.0), token (one-time-use); `habilitado` (bool); provedor no banco (`recaptcha` → `recaptcha_v2`) | CT-01..CT-13 |
| **I** | 3 telas de auth (Livewire component), 1 tela de Settings (Filament page), `config()` por boot, `Http::post` ao provedor | CT-07..CT-13, CT-17..CT-20 |
| **P** | não se aplica: sem dependência de plataforma além do banco. O `Http::fake()` isola os provedores | — |
| **O** | admin configura na tela; usuário resolve o desafio; robô falha na verificação; provedor cai | CT-07..CT-16 |
| **T** | não se aplica: sem expiração, DST ou concorrência relevante. O token é de uso único e validado sincronamente | — |

## Mapa de Regras

| Regra | Área (perfil) | Origem (`RQ`) | Técnica | Cenários |
|---|---|---|---|---|
| R1 — Bridge alimenta `config('captcha.*')` a partir de Settings | Bridge (padrão) | RQ-03, RQ-05, RQ-11 | EP (4 drivers) | CT-01..CT-04 |
| R2 — Bridge projeta score para reCAPTCHA v3 | Bridge (padrão) | RQ-04, RQ-12 | BVA | CT-05, CT-06 |
| R3 — Ativação exige 4 condições (habilitado + chave_do_site + chave_secreta + driver válido) | Ativação (padrão) | RQ-05 | tabela de decisão | CT-07 |
| R4 — Default nasce `recaptcha_v2` e desligado | Ativação (padrão) | RQ-11 | EP | CT-08 |
| R5 — Verificação de token por driver com falha fechada | Verificação (completo) | RQ-05, RQ-08 | EP (4 drivers) + rastreio de efeito | CT-09..CT-13 |
| R6 — Logging de falha de verificação no canal `autenticacao` | Verificação (completo) | RQ-06 | rastreio de efeito | CT-14, CT-15 |
| R7 — Campo oculto quando proteção desligada, sem script | Ativação (padrão) | RQ-02 | EP | CT-16 |
| R8 — 3 telas usam o campo via wrapper | Ativação (padrão) | RQ-02 | EP | CT-17 |
| R9 — Tela de Settings exibe 4 provedores + score condicional | Settings (mínimo) | RQ-04, RQ-12 | EP | CT-18, CT-19 |
| R10 — Migration converte `recaptcha` → `recaptcha_v2` | Bridge (padrão) | RQ-11 | EP | CT-20 |

## Fronteira com o Plano

| Item do PRD | Recusado como oráculo porque | Destino |
|---|---|---|
| Nome da classe `CaptchaBridge` | escolha de implementação | detalhe do cenário |
| Nome da classe `KitCaptchaManager` | escolha de implementação | detalhe do cenário |
| Nome da classe `CaptchaField` | escolha de implementação | detalhe do cenário |
| Ordem de chamada no `aplicarNaConfig()` | escolha de implementação | detalhe do cenário |

**Perguntas em aberto** (replicadas em `00-requisito.md` → `## Ambiguidades`):

- Nenhuma nova nesta derivação.

## Setup Global

### Personas

- `admin` — `usuarioDoKit('admin')` — quem configura na tela de Settings
- `panel_user` — `usuarioDoKit('panel_user')` — quem usa as 3 telas de auth

### Fixtures

- `User::factory()` — usuário padrão
- `Convite::factory()->create(...)` — convite para registro

### Fakes

- `Http::fake()` por URL do provedor + `Http::preventStrayRequests()`
- `Log::partialMock()` via `espiarAutenticacao()` (helper existente em `Pest.php`)

### Estratégia de DB

- `RefreshDatabase` global (via `tests/Pest.php` → `in('Kit')`)

### Divergências com `.ai/rules/`

- Nenhuma.

---

## Regra R1 — Bridge alimenta `config('captcha.*')` a partir de Settings

> `RQ-03`, `RQ-05`, `RQ-11` · perfil **padrão** · técnica: **EP (4 drivers)**

```gherkin
# language: pt

Funcionalidade: Bridge de Settings para config do pacote captcha

  Regra: Após aplicarNaConfig, config('captcha.driver') e config('captcha.{driver}.sitekey') e config('captcha.{driver}.secret') refletem o que está no Settings

    Esquema do Cenário: [CT-01] o bridge projeta chaves para o driver ativo
      Dado o Settings com provedor "<driver>" e chave_do_site "SITE-42" e chave_secreta "SEC-42"
      Quando o alinhamento de config é executado
      Então config('captcha.driver') é "<driver>"
      E config('captcha.<driver>.sitekey') é "SITE-42"
      E config('captcha.<driver>.secret') é "SEC-42"

      Exemplos:
        | driver        | # partição      |
        | hcaptcha      | EP — hCaptcha    |
        | recaptcha_v2  | EP — reCAPTCHA v2 |
        | recaptcha_v3  | EP — reCAPTCHA v3 |
        | turnstile     | EP — Turnstile   |

    Cenário: [CT-02] bridge não projeta chaves de outro driver
      Dado o Settings com provedor "recaptcha_v2" e chave_do_site "SITE-42"
      Quando o alinhamento de config é executado
      Então config('captcha.hcaptcha.sitekey') não é "SITE-42"
      E config('captcha.turnstile.sitekey') não é "SITE-42"

    Cenário: [CT-03] sem habilitado, bridge não projeta nada
      Dado o Settings com habilitado falso e provedor "recaptcha_v2" e chave_do_site "SITE-42"
      Quando o alinhamento de config é executado
      Então config('captcha.driver') não é "recaptcha_v2"

    Cenário: [CT-04] env vars do pacote funcionam como fallback quando Settings não tem valor
      Dado o Settings sem chave_do_site
      E a env RECAPTCHA_V2_SITEKEY está definida como "ENV-SITE"
      Quando o alinhamento de config é executado
      Então config('captcha.recaptcha_v2.sitekey') é "ENV-SITE"
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M1 | Bridge ignora o driver e projeta para `hcaptcha` sempre | CT-01 (linhas recaptcha_v2, recaptcha_v3, turnstile) |
| M2 | Bridge projeta chaves para TODOS os drivers em vez de só o ativo | CT-02 |
| M3 | Bridge projeta mesmo com `habilitado = false` | CT-03 |

---

## Regra R2 — Bridge projeta score para reCAPTCHA v3

> `RQ-04`, `RQ-12` · perfil **padrão** · técnica: **BVA (float, incremento 0.1)**

```gherkin
# language: pt

Funcionalidade: Score do reCAPTCHA v3 no bridge

  Regra: Quando o driver é recaptcha_v3, config('captcha.recaptcha_v3.score') reflete o score do Settings

    Cenário: [CT-05] score do Settings é projetado para o pacote
      Dado o Settings com provedor "recaptcha_v3" e score 0.7
      Quando o alinhamento de config é executado
      Então config('captcha.recaptcha_v3.score') é 0.7

    Cenário: [CT-06] score default é 0.5 quando não configurado
      Dado o Settings com provedor "recaptcha_v3" sem score explícito
      Quando o alinhamento de config é executado
      Então config('captcha.recaptcha_v3.score') é 0.5
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M4 | Score nunca projetado (hardcoded 0.5) | CT-05 |
| M5 | Score projetado para o driver errado (`recaptcha_v2.score`) | CT-05 (assertion no path correto) |

---

## Regra R3 — Ativação exige 4 condições

> `RQ-05` · perfil **padrão** · técnica: **tabela de decisão**

```gherkin
# language: pt

Funcionalidade: Ativação da proteção anti-robô

  Regra: A proteção está ativa somente quando habilitado=true E chave_do_site preenchida E chave_secreta preenchida E driver válido

    Esquema do Cenário: [CT-07] tabela de decisão da ativação
      Dado habilitado "<hab>" e provedor "<prov>" e chave_do_site "<site>" e chave_secreta "<sec>"
      Quando a proteção é consultada
      Então o resultado é "<resultado>"

      Exemplos:
        | hab   | prov          | site     | sec      | resultado  | # condição         |
        | true  | recaptcha_v2  | SITE-42  | SEC-42   | ativo      | todas preenchidas  |
        | false | recaptcha_v2  | SITE-42  | SEC-42   | inativo    | habilitado=false   |
        | true  | recaptcha_v2  |          | SEC-42   | inativo    | chave_do_site vazia |
        | true  | recaptcha_v2  | SITE-42  |          | inativo    | chave_secreta vazia |
        | true  | recaptcha_v2  |   " "    | SEC-42   | inativo    | chave_do_site espaço |
        | true  | invalido      | SITE-42  | SEC-42   | inativo    | driver inválido    |
        | true  | recaptcha_v3  | SITE-42  | SEC-42   | ativo      | v3 é válido        |
        | true  | hcaptcha      | SITE-42  | SEC-42   | ativo      | hcaptcha é válido  |
        | true  | turnstile     | SITE-42  | SEC-42   | ativo      | turnstile é válido |
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M6 | Só o interruptor decide (ignora chaves) | CT-07 (linha chave_do_site vazia) |
| M7 | `isset()` em vez de `filled()` para chave_do_site | CT-07 (linha espaço) |
| M8 | Driver inválido cai no default em vez de desligar | CT-07 (linha driver inválido) |
| M9 | `recaptcha_v3` não está na lista de drivers válidos | CT-07 (linha v3 é válido) |

---

## Regra R4 — Default nasce `recaptcha_v2` e desligado

> `RQ-11` · perfil **padrão** · técnica: **EP**

```gherkin
# language: pt

Funcionalidade: Default da proteção anti-robô

  Regra: De fábrica, a proteção está desligada e o driver padrão é recaptcha_v2

    Cenário: [CT-08] o default do config é recaptcha_v2 e desligado
      Dado a configuração de fábrica sem ajuste do teste
      Quando os defaults são lidos
      Então config('kit.login.anti_robo.habilitado') é false
      E config('kit.login.anti_robo.provedor') é "recaptcha_v2"
      E a proteção anti-robô retorna null
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M10 | Default continua `recaptcha` em vez de `recaptcha_v2` | CT-08 |
| M11 | Default nasce habilitado | CT-08 |

---

## Regra R5 — Verificação de token por driver com falha fechada

> `RQ-05`, `RQ-08` · perfil **completo** · técnica: **EP (4 drivers) + rastreio de efeito**

```gherkin
# language: pt

Funcionalidade: Verificação de token via pacote ddr/filament-captcha

  Regra: O token é verificado pelo driver ativo e o envio é recusado se a verificação falhar ou o provedor estiver indisponível

    Esquema do Cenário: [CT-09] token válido é aceito por cada driver
      Dado a proteção ligada com driver "<driver>"
      E o provedor responde sucesso para o token "TOKEN-VALIDO"
      Quando o formulário de login é enviado com o token "TOKEN-VALIDO"
      Então o login é aceito

      Exemplos:
        | driver        |
        | hcaptcha      |
        | recaptcha_v2  |
        | recaptcha_v3  |
        | turnstile     |

    Cenário: [CT-10] token inválido é recusado
      Dado a proteção ligada com driver "recaptcha_v2"
      E o provedor responde falha para qualquer token
      Quando o formulário de login é enviado com o token "TOKEN-INVALIDO"
      Então o envio é recusado com erro no campo anti_robo

    Cenário: [CT-11] provedor indisponível (ConnectionException) recusa o envio
      Dado a proteção ligada com driver "recaptcha_v2"
      E o provedor lança ConnectionException
      Quando o formulário de login é enviado com o token "TOKEN-QUALQUER"
      Então o envio é recusado com erro no campo anti_robo

    Cenário: [CT-12] token ausente (null) recusa o envio
      Dado a proteção ligada com driver "recaptcha_v2"
      Quando o formulário de login é enviado sem token
      Então o envio é recusado com erro no campo anti_robo

    Cenário: [CT-13] reCAPTCHA v3 com score abaixo do limiar recusa
      Dado a proteção ligada com driver "recaptcha_v3" e score mínimo 0.7
      E o provedor responde sucesso com score 0.3
      Quando o formulário de login é enviado com o token "TOKEN-BAIXO"
      Então o envio é recusado com erro no campo anti_robo
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M12 | `verify()` sempre retorna true | CT-10 |
| M13 | Exceção de rede não capturada (500 em vez de falha fechada) | CT-11 |
| M14 | Token null passa pela validação | CT-12 |
| M15 | Score do v3 ignorado (só `success`) | CT-13 |
| M16 | Driver errado usado para verificação (mismatch entre config e driver real) | CT-09 (pelo `Http::fake` que espera a URL correta do driver) |

---

## Regra R6 — Logging de falha de verificação no canal `autenticacao`

> `RQ-06` · perfil **completo** · técnica: **rastreio de efeito**

```gherkin
# language: pt

Funcionalidade: Logging de falha de captcha

  Regra: Falha de verificação e indisponibilidade do provedor geram warning no canal autenticacao

    Cenário: [CT-14] token recusado loga warning com driver e motivo
      Dado a proteção ligada com driver "recaptcha_v2"
      E o canal autenticacao é espiado
      E o provedor responde falha
      Quando o formulário de login é enviado com o token "TOKEN-RUIM"
      Então o canal autenticacao recebeu exatamente 1 warning
      E o warning contém o nome do driver

    Cenário: [CT-15] provedor indisponível loga warning com exceção
      Dado a proteção ligada com driver "recaptcha_v2"
      E o canal autenticacao é espiado
      E o provedor lança ConnectionException
      Quando o formulário de login é enviado com o token "TOKEN-QUALQUER"
      Então o canal autenticacao recebeu exatamente 1 warning
      E o warning contém "indisponível"
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M17 | Logging removido do verify() | CT-14 |
| M18 | Exceção logada no canal errado (e.g., `stack`) | CT-14, CT-15 (spy só no `autenticacao`) |
| M19 | Logging de sucesso mas não de falha | CT-14 |

---

## Regra R7 — Campo oculto quando proteção desligada, sem script

> `RQ-02` · perfil **padrão** · técnica: **EP**

```gherkin
# language: pt

Funcionalidade: Visibilidade do campo captcha

  Regra: Com a proteção desligada, nenhuma tela pública carrega script de provedor nem o campo

    Cenário: [CT-16] proteção desligada não carrega script nem campo
      Dado a proteção desligada
      Quando as 7 telas públicas são visitadas
      Então nenhuma tela contém script de provedor de captcha
      E nenhuma tela contém o nome do campo "anti_robo"
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M20 | Campo sempre visível, só a regra de validação condicional | CT-16 |
| M21 | Script carregado incondicionalmente via render hook | CT-16 |

---

## Regra R8 — 3 telas usam o campo via wrapper

> `RQ-02` · perfil **padrão** · técnica: **EP**

```gherkin
# language: pt

Funcionalidade: Campo captcha nas telas de auth

  Regra: As 3 telas públicas (login, registro, recuperação de senha) exibem e validam o campo captcha quando a proteção está ligada

    Esquema do Cenário: [CT-17] cada tela valida o captcha quando ligado
      Dado a proteção ligada com driver "recaptcha_v2"
      E o provedor responde sucesso
      Quando o formulário de "<tela>" é enviado com token válido
      Então o envio é aceito

      Exemplos:
        | tela                  |
        | login                 |
        | registro              |
        | recuperação de senha  |
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M22 | Wrapper não adicionado na tela de registro | CT-17 (linha registro) |
| M23 | Wrapper não adicionado na tela de recuperação de senha | CT-17 (linha recuperação de senha) |

---

## Regra R9 — Tela de Settings exibe 4 provedores + score condicional

> `RQ-04`, `RQ-12` · perfil **mínimo** · técnica: **EP**

```gherkin
# language: pt

Funcionalidade: Tela de Settings do captcha

  Regra: A seção anti-robô da tela de Settings exibe select com 4 provedores e campo de score só para reCAPTCHA v3

    Cenário: [CT-18] select de provedores tem 4 opções
      Dado o admin autenticado na tela de Settings
      Quando a seção anti-robô é visualizada com habilitado ativo
      Então o select de provedor contém "hcaptcha", "recaptcha_v2", "recaptcha_v3", "turnstile"

    Cenário: [CT-19] campo de score é visível somente com reCAPTCHA v3
      Dado o admin autenticado na tela de Settings
      Quando o provedor selecionado é "recaptcha_v3"
      Então o campo login_anti_robo_score é visível
      E quando o provedor selecionado é "recaptcha_v2"
      Então o campo login_anti_robo_score é oculto
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M24 | Select tem só 3 provedores (v3 esquecido) | CT-18 |
| M25 | Campo de score sempre visível | CT-19 (v2 → score oculto) |

---

## Regra R10 — Migration converte `recaptcha` → `recaptcha_v2`

> `RQ-11` · perfil **padrão** · técnica: **EP**

```gherkin
# language: pt

Funcionalidade: Migration de valor do provedor

  Regra: A migration converte o valor 'recaptcha' para 'recaptcha_v2' no banco e o down reverte

    Cenário: [CT-20] migration up converte recaptcha para recaptcha_v2
      Dado o Settings com login_anti_robo_provedor = "recaptcha"
      Quando a migration é executada
      Então login_anti_robo_provedor no banco é "recaptcha_v2"

    Cenário: [CT-21] migration down reverte recaptcha_v2 para recaptcha
      Dado o Settings com login_anti_robo_provedor = "recaptcha_v2" após migration up
      Quando a migration down é executada
      Então login_anti_robo_provedor no banco é "recaptcha"
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M26 | Migration não altera nada (no-op) | CT-20 |
| M27 | Down não reverte | CT-21 |

---

## Cenários Adicionais — Regressão

```gherkin
# language: pt

Funcionalidade: Regressão pós-migração

  Regra: Artefatos antigos não devem existir após a migração

    Cenário: [CT-22] classe CampoAntiRobo não existe mais
      Quando o arquivo app/Filament/Forms/Components/CampoAntiRobo.php é verificado
      Então o arquivo não existe

    Cenário: [CT-23] enum ProvedorAntiRobo não existe mais
      Quando o arquivo app/Support/ProvedorAntiRobo.php é verificado
      Então o arquivo não existe

    Cenário: [CT-24] blade campo-anti-robo não existe mais
      Quando o arquivo resources/views/filament/forms/components/campo-anti-robo.blade.php é verificado
      Então o arquivo não existe
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M28 | Artefatos antigos não removidos | CT-22, CT-23, CT-24 |

---

## Checklist de Taxonomia

| Item | Cenário que mata |
|---|---|
| IDOR / autorização horizontal | não se aplica: telas públicas, sem recurso por ID |
| Autorização exercida na ação | não se aplica: Settings já tem permissão `view_configuracoes-do-kit` (herdada, sem alteração) |
| Idempotência | não se aplica: verificação é stateless (token one-time é do provedor) |
| Concorrência | não se aplica: verificação síncrona por request |
| Fronteira no ponto de entrada | CT-07 (chave vazia, espaço), CT-13 (score na borda) |
| Domínio condicionado (driver × score) | CT-19 (score só com v3) |
| Estado × operação de escrita | não se aplica: sem ciclo de vida / status |
| Ausente ≠ null ≠ vazio | CT-07 (linha vazia vs espaço), CT-12 (token null) |
| Paginação / ordenação | não se aplica |
| Timezone / DST | não se aplica |
| Unicode / limite de varchar | não se aplica: chaves são strings curtas controladas pelo provedor |
| Unicidade + soft delete | não se aplica |
| CRUD combinado | não se aplica |
| Mass assignment | não se aplica: campo é `dehydrated(false)` |
| Upload | não se aplica |
| Precisão monetária | não se aplica |

## Índice de Cenários

| ID | Cenário | Regra | Técnica | Camada | Arquivo | Mata |
|----|---------|-------|---------|--------|---------|------|
| CT-01 | Bridge projeta chaves para o driver ativo (4 drivers) | R1 | EP | Kit | `tests/Kit/ProtecaoAntiRoboTest.php` | M1 |
| CT-02 | Bridge não projeta chaves de outro driver | R1 | EP | Kit | idem | M2 |
| CT-03 | Bridge não projeta sem habilitado | R1 | EP | Kit | idem | M3 |
| CT-04 | Env vars do pacote como fallback | R1 | EP | Kit | idem | — |
| CT-05 | Score projetado para v3 | R2 | BVA | Kit | idem | M4, M5 |
| CT-06 | Score default 0.5 | R2 | EP | Kit | idem | M4 |
| CT-07 | Tabela de decisão da ativação (9 linhas) | R3 | tabela de decisão | Kit | idem | M6, M7, M8, M9 |
| CT-08 | Default recaptcha_v2 e desligado | R4 | EP | Kit | idem | M10, M11 |
| CT-09 | Token válido aceito por cada driver (4) | R5 | EP | Kit | idem | M16 |
| CT-10 | Token inválido recusado | R5 | EP | Kit | idem | M12 |
| CT-11 | Provedor indisponível recusa (falha fechada) | R5 | EP | Kit | idem | M13 |
| CT-12 | Token ausente recusado | R5 | EP | Kit | idem | M14 |
| CT-13 | reCAPTCHA v3 score abaixo do limiar | R5 | BVA | Kit | idem | M15 |
| CT-14 | Token recusado loga warning | R6 | rastreio de efeito | Kit | idem | M17, M18, M19 |
| CT-15 | Provedor indisponível loga warning com exceção | R6 | rastreio de efeito | Kit | idem | M18 |
| CT-16 | Proteção desligada não carrega script | R7 | EP | Kit | idem | M20, M21 |
| CT-17 | Cada tela valida o captcha (3 telas) | R8 | EP | Kit | idem | M22, M23 |
| CT-18 | Select de provedores tem 4 opções | R9 | EP | Kit | idem | M24 |
| CT-19 | Score visível só com v3 | R9 | EP | Kit | idem | M25 |
| CT-20 | Migration up converte valor | R10 | EP | Kit | idem | M26 |
| CT-21 | Migration down reverte valor | R10 | EP | Kit | idem | M27 |
| CT-22 | CampoAntiRobo.php removido | — | regressão | Kit | idem | M28 |
| CT-23 | ProvedorAntiRobo.php removido | — | regressão | Kit | idem | M28 |
| CT-24 | blade removido | — | regressão | Kit | idem | M28 |

## CT-B → `05-casos-de-teste-browser.md`

O gate de CT-B **passa**: as 3 telas de auth dependem de JavaScript externo para renderizar o widget captcha. O que só o navegador prova: o widget renderiza, o iframe aparece, o token é capturado pelo Alpine. Ver `05`.
