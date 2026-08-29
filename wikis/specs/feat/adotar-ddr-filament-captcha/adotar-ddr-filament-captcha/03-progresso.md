# Progresso — Adotar `ddr/filament-captcha`

## 1. Instalar o pacote e publicar assets

- [ ] `composer require ddr/filament-captcha`
- [ ] `php artisan vendor:publish --tag=captcha-config`
- [ ] `php artisan vendor:publish --tag=filament-captcha-views`
- [ ] Verificar que `FilamentCaptchaServiceProvider` registra o singleton

## 2. Migrar o valor do provedor no banco de dados

- [ ] Criar migration `2026_08_31_100000_migrate_anti_robo_provedor_value.php`
- [ ] `recaptcha` → `recaptcha_v2` no banco
- [ ] `php artisan migrate`

## 3. Adicionar propriedade `login_anti_robo_score` ao Settings + atualizar mapa

- [ ] Criar migration `2026_08_31_100001_add_score_to_anti_robo_settings.php`
- [ ] Adicionar `public float $login_anti_robo_score` à `ConfiguracoesDoKit`
- [ ] Atualizar `mapaDeConfiguracao()` com as novas chaves
- [ ] `php artisan migrate`

## 4. Criar o `CaptchaBridge`

- [ ] `app/Support/CaptchaBridge.php`
- [ ] Integrar chamada no `aplicarNaConfig()` após o mapa padrão
- [ ] Testar manualmente que `config('captcha.driver')` reflete o Settings

## 5. Criar o `CaptchaField`

- [ ] `app/Support/CaptchaField.php` com `acrescentarA()`
- [ ] Wrapper sobre `Captcha::make('anti_robo')`
- [ ] `->visible()`, `->hiddenLabel()`, `->validationAttribute()`

## 6. Atualizar telas de auth + Settings + ConfiguracaoDoLogin

- [ ] `TelaLogin.php` — trocar import e chamada
- [ ] `TelaRecuperarSenha.php` — idem
- [ ] `RegistroPorConvite.php` — idem
- [ ] `ConfiguracaoDoLogin::antiRobo()` — retornar `?string` (driver) em vez de `?ProvedorAntiRobo`
- [ ] `secaoAntiRobo()` — 4 provedores + campo de score
- [ ] `config/kit.php` — adicionar `score`, alterar default de `provedor`

## 7. Adapters: logging, falha fechada, reset, views

- [ ] `app/Support/CaptchaDriverDecorator.php`
- [ ] Registrar decorator no container
- [ ] Customizar views publicadas (dark mode, reset, data-anti-robo)
- [ ] Verificar reset de token via dispatch

## 8. Remover artefatos antigos

- [ ] Remover `CampoAntiRobo.php`
- [ ] Remover `ProvedorAntiRobo.php`
- [ ] Remover `campo-anti-robo.blade.php`
- [ ] Limpar referências remanescentes

## 9. Reescrever testes

- [ ] `tests/Kit/ProtecaoAntiRoboTest.php` — adaptar 56 testes
- [ ] `tests/Browser/ProtecaoAntiRoboTest.php` — adaptar 5 testes + adicionar v3
- [ ] Novos cenários para reCAPTCHA v3 (score)
- [ ] Cenários para o decorator (logging, falha fechada)
- [ ] Cenários para o bridge (Settings → captcha config)

## 10. Limpeza final

- [ ] `.env.example` atualizado
- [ ] README atualizado (se existir seção anti-robô)

## Testes

- [ ] `ProtecaoAntiRoboTest` (Kit) — todos os cenários
- [ ] `ProtecaoAntiRoboTest` (Browser) — CT-B01..CT-B06+

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

## Desvios do Plano

## Notas de Implementação

## Retrospectiva
