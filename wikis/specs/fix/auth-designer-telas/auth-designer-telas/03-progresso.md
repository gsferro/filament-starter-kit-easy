# Progresso — auth-designer-telas

## 1. A tela de 2FA vestida — `app/Filament/Pages/Auth/TelaDoisFatores.php`

- [x] Classe criada estendendo `TwoFactorPage`
- [x] `use HasAuthDesignerLayout`
- [x] `protected static string $layout` redeclarado
- [x] `getAuthDesignerPageKey(): 'login'`

## 2. Ligar a tela nos três painéis

- [x] `AppPanelProvider` — `enableTwoFactorAuthentication(action: TelaDoisFatores::class)`
- [x] `AdminPanelProvider` — idem
- [x] `InfraPanelProvider` — idem

## 3. Espelhar o `register`

- [x] `AppPanelProvider` — `MediaPosition::Right` no bloco `->registration(...)`
- [x] Comentário do bloco atualizado com a razão do espelho

## 4. A confirmação de e-mail vestida, e desligada

- [x] `->emailVerification(...)` no `AuthDesignerPlugin` dos três painéis
- [x] `->emailVerification(null, isRequired: false)` depois do `->plugins([...])` nos três
      (revisado de `EmailVerification::class` para `null` — ver Desvios do Plano)
- [x] Comentário explicando por que a ordem importa e os três passos para ligar de verdade

## 5. Testes

- [x] `tests/Kit/TelasDeAutenticacaoTest.php` — CT-01…CT-09 (CT-10 cortado; docblock do arquivo atualizado)
- [x] `tests/Browser/TelasDoKitTest.php` — CT-B05

## Verificação Final

- [x] `/ponytail:ponytail-review` no diff
- [x] `vendor/bin/pint --dirty --format agent`
- [x] `php artisan test --testsuite=Unit,Feature,Kit,Tenancy` — 608/608
- [x] `vendor/bin/phpstan analyse --no-progress`
- [x] `composer test:browser` — 33/38, 5 pulados, 0 falhas
- [x] Roteiro "Desenhado × Implementado" do `05` preenchido
- [ ] `git commit` + `git push -u origin fix/auth-designer-telas`

## Degradações de ferramenta declaradas

- **Boost MCP (`search-docs`, `database-schema`, `database-query`, `record-rule`)**: o
  coordenador informou que o servidor reconectou, mas as tools **não chegaram ao conjunto deste
  sub-agente** — três consultas ao `ToolSearch`
  (`select:mcp__laravel-boost__search-docs,…`, `+boost`, `laravel artisan database schema tinker
  routes`) devolveram "No matching deferred tools found". Substituído por: `WebFetch` na doc
  oficial do Filament 5 (`filamentphp.com/docs/5.x/users/overview`, que confirma a existência de
  `->emailVerification()` e o padrão "estender a classe base e passar a classe para o método de
  configuração"; as páginas `users/email-verification` e `panel-configuration` não têm o
  parâmetro `isRequired`) **+ leitura do `vendor/` com `file:line` citado**, que é o que
  `.ai/rules/specs.md` exige e é evidência mais forte que a doc para o ponto decisivo desta
  feature.
- **Playwright MCP**: proibido nesta execução (instância única compartilhada). Não usado. Os
  seletores do `05` saíram de leitura do blade do pacote, com `file:line`.

## Auditoria Pré-Implementação

### Revisão profunda (step 5) — premissas do plano contra o código real

| Premissa do plano | O código real diz | Correção aplicada na wiki |
|---|---|---|
| "vestir a tela do Breezy exige bind no container, como `TelaBloqueio`" | a rota do Breezy usa `$plugin->getTwoFactorRouteAction()` (`vendor/jeffgreco13/filament-breezy/routes/web.php:24`), alimentado pelo parâmetro `action:` de `enableTwoFactorAuthentication()` (`HasTwoFactorAuthentication.php:29,33`). Bind no container seria **código morto** | ADR-01 escrito com a alternativa recusada; passo 2 do PRD usa `action:` |
| "ligar `emailVerification()` barra usuário sem `email_verified_at`" | só se o model implementar `MustVerifyEmail` (`EnsureEmailIsVerified.php:32-40`), e `App\Models\User` **não** implementa (`app/Models/User.php:29`). O risco é **latente**, não imediato | ADR-03 reescrita: o argumento passou de "barra hoje" para "arma a armadilha para amanhã", que é o motivo real do `isRequired: false` |
| "a rota da confirmação de e-mail seria superfície pública nova" | ela nasceria dentro do grupo `$panel->getAuthMiddleware()` (`vendor/filament/filament/routes/web.php:60,75-84`) — e, depois do desvio do passo 4, **nem nasce** | PRD e ADR-03 corrigidos; CT-10 (que a protegeria) foi cortado, e CT-08 assere a ausência da rota |
| "a tela de 2FA precisa de 2FA habilitado para renderizar" | `hasValidTwoFactorSession()` devolve `false` sem `breezySession` (`TwoFactorAuthenticatable.php:70-73`) e a rota roda sob `$panel->getMiddleware()`, não sob o `authMiddleware` (`routes/web.php:15`) — qualquer autenticado renderiza | Setup Global do `04` simplificado: CT-01/CT-02 usam `usuario()`, sem seeder |
| "vou precisar de um helper novo para 2FA nos testes" | `usuario()`, `usuarioDoKit()` e `noPainelBootado()` de `tests/Pest.php` bastam; e `.ai/rules/testes.md` proíbe helper novo em arquivo de teste usado por mais de um arquivo | `04` declara "nenhum helper novo" |
| "`fi-auth-layout` prova que a tela está vestida" | prova só o layout. A mídia e o eixo vêm de `$config->hasMedia()`/`$config->position` (`layouts/auth.blade.php:7-9,28`), e chave não configurada devolve `AuthPageConfig` vazio **sem erro** | R2/CT-02 acrescentado ao `04` só por causa disso |

### Auditoria Ponytail (step 6)

| # | Sugestão de corte | Aplicada? | Onde |
|---|---|---|---|
| 1 | não criar bind no container para a tela de 2FA — usar o parâmetro `action:` do pacote | sim | ADR-01, passo 2 do PRD |
| 2 | não criar channel de log novo, nem log nenhum: a entrega não tem ramo de fluxo | sim | seção "Channel de Log" do PRD |
| 3 | não criar arquivo de teste novo — os CT cabem em `tests/Kit/TelasDeAutenticacaoTest.php`, cujo assunto declarado é exatamente este, e o CT-B cabe no fim de `tests/Browser/TelasDoKitTest.php` | sim | Índice de Cenários do `04`, "Onde vive" do `05` |
| 4 | um CT-B, não cinco: cinco cenários cogitados matam mutantes que os CT de HTTP/Livewire já matam | sim | tabela "Cogitado e cortado" do `05` |
| 5 | não publicar a view do Breezy nem criar layout novo | sim | ADR-01, alternativa 2 |
| 6 | não criar chave `two-factor` no Auth Designer (exigiria PR no pacote) | sim | ADR-02, alternativa 2 |
| 7 | cortar CT-03 (a página comum limpa), porque "não mata mutante nenhum" | **recusada** | é a linha de base sem a qual CT-04 não distingue "não vazou" de "nunca teve". Registrado como tal no Índice de Cenários |

## Data de conclusão

2026-08-24.

## Resultado dos testes

| Comando | Resultado |
|---|---|
| `php artisan test tests/Kit/TelasDeAutenticacaoTest.php` | **24 passando, 78 asserções**, 16,0 s |
| `vendor/bin/pint --dirty --format agent` | passou |
| `vendor/bin/phpstan analyse --no-progress` | passou, 0 erros |
| `php artisan test --testsuite=Unit,Feature,Kit,Tenancy` | **608 passando, 1629 asserções**, 1383 s (23 min, em série) |
| `composer test:browser` | **38 testes, 33 passando, 0 falhando, 5 pulados** (a suíte roda em série) |

## Blockers

Nenhum.

## Desvios do Plano

### Passo 4 reescrito: `->emailVerification(null, ...)` em vez de `EmailVerification::class`

O plano previa registrar a tela de confirmação de e-mail e apenas desarmar a exigência
(`isRequired: false`), deixando a rota no ar. Foi **implementado exatamente assim e reprovou**,
nos três painéis:

```
TypeError: Filament\Auth\Pages\EmailVerification\EmailVerificationPrompt::getVerifiable():
Return value must be of type Illuminate\Contracts\Auth\MustVerifyEmail,
App\Models\User returned
```

`getVerifiable()` declara retorno `MustVerifyEmail`
(`vendor/filament/filament/src/Auth/Pages/EmailVerification/EmailVerificationPrompt.php:36-43`) e
é chamada no `mount()` (`:31`); `App\Models\User` não implementa a interface
(`app/Models/User.php:29`). A tela é **estruturalmente inutilizável** enquanto o model não
implementar a interface — não é questão de configuração.

Correção: passar `null` no primeiro parâmetro, o que apaga a **ação da rota** sem apagar a
**configuração do Auth Designer** (os dois efeitos vivem em lugares diferentes — ver a tabela em
ADR-03). Resultado verificado com `php artisan route:list`: `--path=email-verification` não
devolve nada, e `--path=two-factor` devolve as três rotas apontando para
`App\Filament\Pages\Auth\TelaDoisFatores`.

Alternativa recusada: implementar `MustVerifyEmail` no `User`. Está fora do requisito e mudaria o
fluxo de todo mundo — todo aceite de convite passaria a disparar e-mail de verificação
(`vendor/filament/filament/src/Auth/Pages/Register.php:106,161-164`).

### CT-B05 nasceu com o seletor errado (causa (a) — CT-B especificado errado)

O `05` especificava `#data\.code`, derivado de `->statePath('data')` do `TwoFactorPage`
(`vendor/jeffgreco13/filament-breezy/src/Pages/TwoFactorPage.php:86`). O `id` que o Filament
gera é **`form.code`** — pelo nome do SCHEMA, não pelo statePath. Sondado com um teste
descartável (`preg_match` por `id="[^"]*code[^"]*"` no HTML da tela), corrigido no teste e no
`05`. `.ai/rules/testes-browser.md` já dizia `#form\.email` / `#form\.password`; a derivação
por statePath foi minha, e estava errada.

Efeito colateral do cenário vermelho, que vale registrar: **as duas asserções de acessibilidade
do dashboard (`TemaEscuroTest`) reprovaram junto**, e passaram sozinhas quando o CT-B05 ficou
verde. É a rule "cenário de navegador visita o painel em que o processo foi deixado"
(`.ai/rules/testes-browser.md`) agindo — um cenário que falha deixa o processo num painel e
contamina os seguintes. Verificado por comparação: com `--filter="acessibilidade no dashboard"`,
**baseline (`git stash`) e branch dão exatamente o mesmo resultado** (3 testes, 1 reprovando em
`/app`), e na suíte completa os dois passam. Nada nesta entrega mexeu nisso.

### CT-10 cortado

O cenário "a tela nova não é superfície pública" perdeu o objeto: sem rota não há superfície a
proteger. A asserção `Route::has(...) === false` de CT-08 é o mesmo oráculo, mais forte. O `04`
registra o corte.

### O oráculo de RQ-04 desceu de HTTP para o objeto de configuração

CT-07 deixou de ser `$this->get()` da tela e passou a afirmar sobre
`AuthDesignerConfigRepository::hasPageConfig('email-verification', $painel)` e sobre o
`AuthDesignerConfig` resolvido (mídia, `position`, `showThemeSwitcher`). É um nível abaixo do
HTML, e foi o preço de não publicar uma rota que erra. Em troca, ganhou duas asserções que a
versão HTTP não tinha: o eixo espelhado e o alternador de tema.

## Notas de Implementação

- **O Breezy tem ponto de extensão declarado para a tela de 2FA** — o parâmetro `action:` de
  `enableTwoFactorAuthentication()`
  (`vendor/jeffgreco13/filament-breezy/src/Concerns/Plugin/HasTwoFactorAuthentication.php:29,33`),
  consumido pela rota do pacote (`routes/web.php:24`). O bind no container que `TelaBloqueio`
  precisou fazer **não se aplica** aqui: seria código morto com cara de simetria. Está em ADR-01.
- **O alias Livewire não foi problema.** O Breezy registra `two-factor-page` para a classe dele
  (`FilamentBreezyServiceProvider.php:40`) e a subclasse não tem alias próprio; o registry do
  Livewire resolveu sozinho, e CT-05/CT-06 (que passam pelo `Livewire::test()`) provam isso.
  Nenhum `Livewire::component()` foi necessário.
- **A tela de 2FA renderiza para qualquer autenticado**, sem 2FA habilitado: a rota do Breezy roda
  sob `$panel->getMiddleware()` e não sob o `authMiddleware` (`routes/web.php:15`), e
  `hasValidTwoFactorSession()` devolve `false` sem `breezySession`
  (`TwoFactorAuthenticatable.php:70-73`). Isso simplificou o arranjo de CT-01/CT-02 — sem seeder,
  sem 2FA.
- **`fi-auth-layout` não é oráculo suficiente** para "a tela está vestida". O blade do pacote
  emite a classe sempre; mídia e eixo vêm de `$config->hasMedia()`/`$config->position`
  (`layouts/auth.blade.php:7-9,28`), e chave não configurada devolve `AuthPageConfig` vazio sem
  erro. Foi por isso que R2/CT-02 existe.

## Candidatos a Rule (decisão do usuário — NÃO gravar)

Avaliados nos 4 gates. **Nada é gravado por este agente**; a decisão é do usuário e a gravação é
do agente principal.

1. **[ADR-03] Recurso de painel do Filament cujo `enable` liga um `isRequired` por default**
   - **Glob**: `app/Providers/Filament/**`
   - **Nota**: `Panel::emailVerification()` (e a família de métodos com par
     `ação + isRequired`) liga a exigência por default (`HasAuth.php:110`). Plugin que chama o
     método com um argumento só arma a exigência sem dizer. Registrar a tela e desligar a
     exigência exige que a chamada de desligamento venha **depois** do `->plugins([...])`, porque
     `Panel::plugin()` registra na hora (`HasPlugins.php:15-21`).
   - **Gates**: durável ✅ (vale para qualquer painel futuro) · escopável ✅ · não-inferível ✅
     (a ordem da cadeia não se lê no arquivo) · não-redundante — **⚠️ parcial**: já existe
     `.ai/rules/providers-filament.md`, e o certo é **acrescentar uma seção lá**, não criar rule
     nova.
   - **Recomendação**: acrescentar seção a `.ai/rules/providers-filament.md`.

2. **[R3 / CT-04] Atribuição de propriedade estática em `boot()` é efeito de processo**
   - **Glob**: `app/Filament/Pages/Auth/**`
   - **Nota**: já é o conteúdo de `.ai/rules/auth.md`. O que esta feature acrescenta é que a
     manobra tem **dois** pontos de entrada agora (bind no container e parâmetro `action:` do
     pacote), e que a página vítima do vazamento passou a ser também página vestida.
   - **Gates**: durável ✅ · escopável ✅ · não-inferível ✅ · não-redundante ❌ — **é a rule que
     já existe**.
   - **Recomendação**: **atualizar** `.ai/rules/auth.md` com uma frase, não criar rule nova.

3. Nenhum terceiro candidato. O teto é 3 e há 2, ambos como **atualização** de rule existente.

## Retrospectiva

- **Funcionou bem**: ler o `vendor/` antes de escrever a ADR trocou duas justificativas erradas
  por duas certas (o bind no container e o "barra hoje"). As duas sustentavam decisões de
  desenho — exatamente o padrão que `.ai/rules/specs.md` descreve.
- **Faltou no plano**: a primeira versão de R1 tinha `fi-auth-layout` como oráculo único. Foi a
  releitura do blade do pacote (`layouts/auth.blade.php:7-9,28`) que mostrou que a classe sai
  sempre, e que a mídia é outra asserção. Sem R2/CT-02 o conjunto ficaria verde com a tela
  vestida e vazia.
- **Faltou no plano, segunda vez**: a auditoria pré-implementação leu a assinatura de
  `emailVerification()` e o middleware do Laravel, e concluiu certo sobre os dois — mas **não
  leu a própria página** que ia entrar no ar. `EmailVerificationPrompt::getVerifiable()` estava
  a três arquivos de distância e decidia a viabilidade inteira do passo 4. A lição, na forma
  operável: quando o passo REGISTRA uma classe de vendor numa rota, leia o `mount()` dessa
  classe — não só o método de configuração que a registra.
- **Custou barato porque o teste existia antes do conserto**: o defeito apareceu como CT-07
  vermelho nos três painéis, não como uma tela quebrada em produção. Foi exatamente o ciclo que
  a wiki existe para produzir.
