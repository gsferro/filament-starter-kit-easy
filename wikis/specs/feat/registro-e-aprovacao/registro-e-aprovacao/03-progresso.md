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

- [ ] `$layout` redeclarado (regra do kit); NENHUM rename — ver ADR-04
- [ ] `mount()` com o garfo por **ausência** de token
- [ ] token presente e inválido continua recusando
- [ ] tenancy ligada exige organização resolvida
- [ ] `getEmailFormComponent()` condicional (desabilitado só no convite)
- [ ] `getHeading()` nos dois modos
- [ ] `mutateFormDataBeforeRegister()` força o e-mail só no convite
- [ ] `handleRegistration()` nos dois modos
- [ ] `register()` sobrescrito só para o pendente (trata `null` do throttle)
- [ ] docblock da classe reescrito
- [ ] docblock da classe abre com os dois modos e a tabela do garfo (substitui o rename)

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
| `Register.php:157-176` é `sendEmailVerificationNotification()` | o método vai de `:161` a `:180`; as duas saídas antecipadas são `:163-165` e `:167-169` | citações corrigidas em `01`, `02` e `04` |
| throttle do registro em `Register.php:71-79` e `:126-148` | `rateLimit(2)` em `:73`, dentro do `try` de `:72-78`; o limitador por e-mail é `:129-148`, chamado em `:80-82` | corrigido em `02` (ADR-09) e `04` (CT-13) |
| transação `:84-107`, login `:105` | transação `:84-102`, evento `:104`, envio `:106`, login `:108` | corrigido em `02` (ADR-10) e `04` |
| **CT-22b é escrevível?** — o plano supôs que dava para montar o painel pelo provider | **confirmado**: `(new AppPanelProvider(app()))->panel(Panel::make())` devolve um painel utilizável fora do boot — medido, `hasEmailVerification() === false`, `hasRegistration() === true`, `isEmailVerificationRequired() === false` | premissa mantida; o `04` já registra as duas alternativas descartadas |
| `pest --agent` disponível para sondagem | **não instalado** (`pestphp/pest-plugin-agent` não está no `composer.json`) | sondagens feitas por `php artisan tinker --execute`; nenhuma dependência nova foi adicionada |
| `phpunit.xml` não fixa `KIT_REGISTRO*` | confirmado — nenhuma das três chaves aparece no arquivo | premissa de CT-26 mantida, e a dependência está declarada no `04` |

### Auditoria Ponytail (step 6) — sub-agente independente

| # | Sugestão de corte | Aplicada? | Onde |
|---|---|---|---|
| 1 | **CORTE** o rename `RegistroPorConvite → TelaRegistro`: ~10 arquivos, nenhum RQ pede, e 2 dos arquivos são asserções de prefixo de log de testes do convite | **sim** | ADR-04 reescrita (decisão invertida, registrada como substituição em vez de apagada); `01` passos 5 e Riscos; `03` seção 5 |
| 2 | **SIMPLIFIQUE** o `TenantForm`: `Section::make('Registro')` só para um `Toggle` contraria o arquivo, onde `ativo` (mesma natureza) já vive na `Section` de Identificação | **sim** | `01` passo 8 |
| 3 | **CORTE** CT-16: provava `->unique(ignoreRecord: true)` que já existe e não tem relação com esta feature | **parcialmente** — o cenário ficou, com o oráculo trocado para o risco que **esta** feature cria ("editar pendente não o aprova em silêncio"), que é o par comportamental do CT-02 estrutural. Cortar por inteiro deixaria M18 sem matador comportamental | `04` R4, CT-16, M18, taxonomia |
| 4 | **SIMPLIFIQUE** CT-06: a linha "desligado" do `Esquema` repete `tests/Kit/ConviteTest.php:199`, que já roda sob o default | **sim** — virou cenário simples, só a partição "ligado" (a coexistência, que é o que a feature introduz) | `04` R2 |

Nada apontado em `00-requisito.md` nem em `05-casos-de-teste-browser.md` (o segundo já se
autopoda pela tabela *Cogitado e cortado*).

**Saldo**: −10 arquivos tocados, −1 `Section`, −1 linha de `Esquema`, 0 cenário perdido.

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
