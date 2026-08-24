# Progresso — w3b: registro aberto e aprovação

## 1. `config/kit.php` — o bloco `registro`

- [ ] Bloco `registro` com as três chaves, depois de `tenancy`
- [ ] Comentário explicando por que `(bool) env()` é seguro e `(int) env()` não é
- [ ] Comentário dizendo que ninguém lê estas chaves direto
- [ ] `.env.example` com as três chaves comentadas

## 2. Migrations

- [ ] `users.aprovacao_pendente` boolean default `false`, depois de `email_verified_at`
- [ ] Docblock: por que boolean e não `aprovado_em` nullable (direção do default)
- [ ] `tenants.registro_habilitado` boolean default `false`, depois de `ativo`
- [ ] `down()` das duas, com a nota de que pendente vira aprovado ao reverter

## 3. `App\Support\RegistroAberto` — o ponto único

- [ ] `habilitado()`, `exigirAprovacao()`, `exigirVerificacaoDeEmail()` — marcados como o
      ponto de ligação com o Settings
- [ ] `papel()` devolvendo o papel do painel `app`
- [ ] `organizacao(?string $slug)` exigindo existe + ativo + registro habilitado
- [ ] `registrar(array $dados, ?Tenant $organizacao)` reafirmando as fronteiras
- [ ] `email_verified_at` gravado quando a verificação está desligada
- [ ] `aprovacao_pendente` gravado quando a aprovação é manual
- [ ] vínculo com a organização **antes** da aprovação
- [ ] papel atribuído **só** quando não está pendente, no contexto da organização
- [ ] `info` de sucesso e `warning` de recusa no channel `autenticacao`, e-mail mascarado

## 4. `App\Models\User`

- [ ] `implements MustVerifyEmail`
- [ ] `'aprovacao_pendente' => 'boolean'` nos casts
- [ ] `aprovacao_pendente` **fora** do `$fillable`
- [ ] guarda de pendência como **primeira** instrução de `canAccessPanel()`, com `warning`
- [ ] `aprovar()` idempotente, com papel e `info`

## 5. `App\Filament\Pages\Auth\TelaRegistro`

- [ ] `git mv` de `RegistroPorConvite.php`; `$layout` redeclarado
- [ ] `mount()` com o garfo por **ausência** de token
- [ ] token presente e inválido continua recusando
- [ ] tenancy ligada exige organização resolvida
- [ ] `getEmailFormComponent()` condicional (desabilitado só no convite)
- [ ] `getHeading()` nos dois modos
- [ ] `mutateFormDataBeforeRegister()` força o e-mail só no convite
- [ ] `handleRegistration()` nos dois modos
- [ ] `register()` sobrescrito só para o pendente (trata `null` do throttle)
- [ ] docblock da classe reescrito
- [ ] rename propagado: `AppPanelProvider` (3 pontos), 4 testes, `wikis/arquitetura.md`

## 6. `TelaLogin` — o link "Cadastre-se"

- [ ] `getSubheading()` devolve o do pai quando o registro está ligado
- [ ] docblock atualizado

## 7. `AppPanelProvider` — verificação de e-mail condicional

- [ ] `->emailVerification()` condicional pelo ponto único
- [ ] bloco de comentário de `:341-377` reescrito
- [ ] **afirmação falsa corrigida**: "NENHUM usuário semeado tem `email_verified_at`"
- [ ] comentário de `:249-262` atualizado

## 8. `TenantForm` — toggle por organização

- [ ] `Section` "Registro" com o `Toggle`, visível só com o registro global ligado
- [ ] `helperText` com o endereço `/app/register?org={slug}`
- [ ] `registro_habilitado` no `$fillable` e nos `casts()` do `Tenant`

## 9. Os dois `UserResource`

- [ ] coluna de situação (`/app` e `/admin`)
- [ ] filtro de pendentes (`/app` e `/admin`)
- [ ] `Action::make('aprovar')` com `->authorize('update')` e `->requiresConfirmation()`
- [ ] `->visible()` só para pendente

## 10. Testes

- [ ] `tests/Kit/RegistroAbertoTest.php` — CT-01…CT-13, CT-15…CT-22b, CT-26
- [ ] `tests/Tenancy/RegistroAbertoTenancyTest.php` — CT-14, CT-23, CT-24, CT-25
- [ ] `tests/Browser/RegistroAbertoTest.php` — CT-B01, CT-B02
- [ ] nenhum helper novo em `tests/Pest.php` sem uso cruzado

## 11. README

- [ ] `README.md` → `## Registro aberto e aprovação`, depois de `## Convite de usuário`
- [ ] `README.en.md` → `## Open registration and approval`, depois de `## User invitation`
- [ ] tabela "o que ligar cada chave faz refletir" (RQ-12) nos dois
- [ ] consequência de ligar a verificação de e-mail em base legada, com o reparo
- [ ] `## Roteiro de features` → `### Acesso e autenticação`, nos dois idiomas

## Verificação Final

- [ ] `/ponytail:ponytail-review` no diff
- [ ] `vendor/bin/pint --dirty --format agent`
- [ ] `vendor/bin/phpstan analyse --no-progress` — 0 erros
- [ ] `php artisan test --testsuite=Unit,Feature,Kit,Tenancy` — 662 na base, nenhuma queda
- [ ] `composer test:browser`
- [ ] Roteiro "Desenhado × Implementado" do `05` preenchido
- [ ] `git push -u origin feat/registro-e-aprovacao`

## Auditoria Pré-Implementação

### Revisão profunda (step 5) — premissas do plano contra o código real

| Premissa do plano | O código real diz | Correção aplicada na wiki |
|---|---|---|
| *(a preencher no step 5)* | | |

### Auditoria Ponytail (step 6)

| # | Sugestão de corte | Aplicada? | Onde |
|---|---|---|---|
| *(a preencher no step 6)* | | | |

## Blockers

- *(nenhum)*

## Desvios do Plano

- *(a preencher na implementação)*

## Notas de Implementação

- *(a preencher na implementação)*

## Candidatos a Rule (PROPOSTA — decisão do usuário)

> A instrução desta rodada é **propor**, nunca gravar. Nada foi passado para `record-rule`.

*(a preencher no step 9)*

## Retrospectiva

- *(a preencher no fim)*
