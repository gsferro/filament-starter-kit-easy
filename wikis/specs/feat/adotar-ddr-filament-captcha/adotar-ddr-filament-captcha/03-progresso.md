# Progresso — Adotar `ddr/filament-captcha`

## 1. Instalar o pacote e publicar assets

- [x] `composer require ddr/filament-captcha` (v1.1.2)
- [x] ~~`php artisan vendor:publish --tag=captcha-config`~~ não publicado: o kit só lê `verify_url` do config do pacote (ADR-08)
- [x] `php artisan vendor:publish --tag=filament-captcha-views` (só `drivers/*`; o wrapper `forms/components/captcha` publicado foi removido, idêntico ao do pacote)
- [x] Verificar que `FilamentCaptchaServiceProvider` registra o singleton (`packageRegistered()`; o `AppServiceProvider` sobrescreve)

## 2. Migrar o valor do provedor no banco de dados

- [x] Migration única `2026_08_31_100000_adotar_filament_captcha_nas_kit_settings.php` (conversão + pontuação + local)
- [x] `recaptcha` → `recaptcha_v2` no banco (`$kit->update()`), `down()` reverte
- [x] `php artisan migrate`

## 3. Adicionar propriedade `login_anti_robo_score` ao Settings + atualizar mapa

- [x] ~~migration separada~~ na mesma migration acima
- [x] `public float $login_anti_robo_pontuacao_minima` + `public bool $login_anti_robo_local` (nomes em português, como as outras)
- [x] `mapaDeConfiguracao()` → `kit.login.anti_robo.pontuacao_minima` e `.local`
- [x] `php artisan migrate`

## 4. Criar o `CaptchaBridge`

- [x] ~~`app/Support/CaptchaBridge.php`~~ substituído por `app/Support/GerenciadorAntiRobo.php` (ADR-08)
- [x] ~~chamada no `aplicarNaConfig()`~~ projeção acontece em `createDriver()`, por request
- [x] CT-01/CT-03/CT-05 provam `config('captcha.*')` refletindo o kit

## 5. Criar o `CaptchaField`

- [x] ~~`app/Support/CaptchaField.php`~~ `CampoAntiRobo extends Captcha` (ADR-09)
- [x] Subclasse do `Captcha` do pacote, `getDefaultName()` = `anti_robo`
- [x] `->visible()`, `->required()`, `->hiddenLabel()`, `->validationAttribute()`, wrapper attributes (`fi-fo-anti-robo`, `data-anti-robo`), regra de reset

## 6. Atualizar telas de auth + Settings + ConfiguracaoDoLogin

- [x] `TelaLogin.php`, `TelaRecuperarSenha.php`, `RegistroPorConvite.php` — **sem alteração** (API `acrescentarA()` mantida)
- [x] `ConfiguracaoDoLogin::antiRobo()` — continua `?ProvedorAntiRobo`; ganhou a condição `local` (ADR-07) e `pontuacaoMinimaAntiRobo()`
- [x] `secaoAntiRobo()` — 4 provedores, toggle `local`, campo de pontuação mínima (visível só no v3, `numeric` 0..1, `step 0.05`)
- [x] `config/kit.php` — `local`, `pontuacao_minima` (com `is_numeric`), default `recaptcha_v2`

## 7. Adapters: logging, falha fechada, reset, views

- [x] `app/Support/VerificacaoAntiRobo.php` (decorator: try/catch + `warning` no canal `autenticacao`)
- [x] Singleton `CaptchaManager` → `GerenciadorAntiRobo` no `AppServiceProvider::register()`
- [x] 4 views publicadas: `theme` dark/light, `widgetId`, `redefinir()` no `x-on:kit-anti-robo-redefinir.window`; `data-anti-robo`/`fi-fo-anti-robo` vêm do campo, não da view
- [x] Reset via `dispatch` na regra extra do campo (CT "manda o widget se redefinir")

## 8. Remover artefatos antigos

- [x] ~~Remover `CampoAntiRobo.php`~~ mantido como subclasse (ADR-09)
- [x] ~~Remover `ProvedorAntiRobo.php`~~ mantido encolhido (ADR-09)
- [x] Remover `campo-anti-robo.blade.php`
- [x] Limpar referências remanescentes (`grep` por `urlDoScript|urlDeVerificacao|objetoJs|'recaptcha'`)

## 9. Reescrever testes

- [x] `tests/Kit/ProtecaoAntiRoboTest.php` — reescrito: 94 casos, 405 asserções
- [x] `tests/Browser/ProtecaoAntiRoboTest.php` — 5 adaptados + CT-B03 (v3 invisível) = 6
- [x] reCAPTCHA v3: limiar do kit, abaixo recusa, igual passa, `?render={chave}` sem `explicit`
- [x] Decorator: `token_invalido`, `verificacao_indisponivel` com `exception`, 503
- [x] Manager: binding, projeção por driver, env vars do pacote ignoradas, limiar

## 10. Limpeza final

- [x] `.env.example` atualizado (`KIT_ANTI_ROBO_LOCAL`, `KIT_ANTI_ROBO_PONTUACAO_MINIMA`, provedores do pacote)
- [x] README.md / README.en.md — seção "Proteção anti-robô" reescrita; linha do pacote na tabela; bullet em "O que já vem pronto"

## Testes

- [x] `ProtecaoAntiRoboTest` (Kit) — 94 passed
- [x] `ProtecaoAntiRoboTest` (Browser) — 6 passed (CT-B01..05 + v3)

## Verificação Final

- [x] Ponytail aplicado na implementação (desvios acima são os cortes)
- [x] `vendor/bin/pint --dirty --format agent`
- [x] `php artisan test --compact tests/Kit/ProtecaoAntiRoboTest.php` — 94 passed
- [ ] `php artisan test --testsuite=Kit --filter=ConfiguracoesDoKit --compact`
- [x] `tests/Browser/ProtecaoAntiRoboTest.php` — 6 passed (build + view:cache)
- [ ] `php artisan test --testsuite=Unit,Feature,Kit,Tenancy --compact` — base não cair
- [x] `vendor/bin/phpstan analyse` (arquivos tocados) — 0 erros
- [x] Roteiro "Desenhado × Implementado" do `05` preenchido
- [ ] `git commit` por bloco concluído

## Auditoria Pré-Implementação

### Revisão profunda (step 5) — premissas do plano contra o código real

| Premissa do plano | O código real diz | Correção aplicada na wiki |
|---|---|---|
| Pacote co-instala com Pest 5 + Filament 5 + Laravel 13 | `composer require --dry-run` confirma: `v1.1.2` instala sem conflitos | nenhuma correção |
| `CampoAntiRobo` usado em 3 telas | Confirmado: `TelaLogin`, `TelaRecuperarSenha`, `RegistroPorConvite` + `ConfiguracoesDoKit` (admin) | nenhuma correção |
| `ProvedorAntiRobo` usado em 5 arquivos | Confirmado: `CampoAntiRobo`, `ConfiguracaoDoLogin`, `ConfiguracoesDoKit` (settings), `ConfiguracoesDoKit` (page), `ProvedorAntiRobo` | nenhuma correção |
| Testes: 37 referências em `tests/Kit/ProtecaoAntiRoboTest.php`, 9 em `tests/Browser/ProtecaoAntiRoboTest.php` | Confirmado via grep | nenhuma correção |
| `Schema` é o tipo do `form()` nas 3 telas (Filament 5) | Confirmado: `Filament\Schemas\Schema` | nenhuma correção |
| `Captcha` do pacote estende `Field` (compatível com `Schema`) | Confirmado no source do pacote | nenhuma correção |
| Nenhuma referência direta à blade `campo-anti-robo` em PHP | Confirmado: só o `CampoAntiRobo` a referencia via `$view` | nenhuma correção |

### Auditoria Ponytail (step 6)

| # | Sugestão de corte | Aplicada? | Onde |
|---|---|---|---|
| 1 | `CaptchaField` wrapper como classe separada — inline como método estático em `ConfiguracaoDoLogin` | recusada: `CaptchaField` mantém a mesma API `acrescentarA()` que 3 telas usam, e separa responsabilidade de composição de campo da de leitura de config. Uma classe a mais vale a clareza | — |
| 2 | `CaptchaDriverDecorator` — estender `CaptchaManager` em vez de criar decorator | aplicada parcialmente: estender `CaptchaManager` e sobrescrever `createDriver()` para envolver o driver com try/catch + log, eliminando a classe decorator separada | `01`, passo 7a |
| 3 | Debug log no `CaptchaBridge::aplicar()` — remove, já coberto pelo log do `aplicarNaConfig()` | aplicada: removido o log de debug do bridge | `01`, passo 4 |

## Blockers

- Nenhum.

## Desvios do Plano

- **Sem `CaptchaBridge`** — a projeção para `config('captcha.*')` mora em `GerenciadorAntiRobo::createDriver()`, por request (ADR-08). Env vars do pacote ignoradas, não fallback; CT-04 do `04` cortado.
- **`CampoAntiRobo` e `ProvedorAntiRobo` mantidos** — o primeiro vira subclasse do `Captcha`, o segundo encolhe (ADR-09). CT-22/CT-23 cortados; as três telas de auth não mudaram.
- **Nomes em português**: `login_anti_robo_pontuacao_minima` / `KIT_ANTI_ROBO_PONTUACAO_MINIMA` em vez de `score`.
- **Uma migration**, não duas (conversão + duas propriedades).
- **Acréscimo do usuário**: `local` (`app()->isLocal()`), ADR-07 — env, Settings, toggle, CT-07b.
- **`config/captcha.php` não publicado**; o `forms/components/captcha.blade.php` publicado foi removido (idêntico ao do pacote).

## Notas de Implementação

- Auditoria Boost (`search-docs`) indisponível nesta sessão (MCP `laravel-boost` caiu no connect). Justificativas de vendor foram lidas direto do `vendor/` com `file:line`, como manda `.ai/rules/specs.md`.
- O driver do pacote junta 5xx e `success:false` num único `false`; o log do kit registra ambos como `token_invalido`. Separar exigiria estender o driver — não feito.
- Sem `timeout` próprio no `Http` do pacote (default 30 s). Ajuste possível: `Http::globalOptions(['timeout' => 5])` num provider — não feito (marcado `ponytail:` em `VerificacaoAntiRobo`).
- O componente do pacote usa `$wire.$entangle()` sem `.live` — deferido, sem request por token; a troca por `$wire.set(..., false)` da ADR-05 não foi necessária.
- Teste manual com as chaves reais do Turnstile do solicitante em `localhost:8765` com `local` ligado — ver `05`.

## Retrospectiva

- 300 linhas próprias → pacote + ~150 linhas de adapter (manager 25, decorator 60, campo 60). reCAPTCHA v3 ganho.
- O que o plano superestimou: bridge no boot, classe nova para o campo, lista de drivers fora do enum. Ler o pacote antes de desenhar (`CaptchaManager::createDriver()` lê config sob demanda) teria cortado 3 arquivos do plano.
- O que só apareceu implementando: a chave presa ao domínio quebra o login local — virou o opt-in `local` (ADR-07).
