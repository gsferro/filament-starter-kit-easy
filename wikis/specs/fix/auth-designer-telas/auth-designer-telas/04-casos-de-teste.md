# Casos de Teste — auth-designer-telas

> Requisito: `00-requisito.md` · Plano: `01-plano-acao.md`
> Derivado do **requisito**. A implementação ainda não existe quando estes cenários foram
> escritos; nenhum `Então` saiu de leitura de código da feature.

## Perfil de Derivação

| Área | P | I | P×I | Perfil |
|---|---|---|---|---|
| A1 — layout da tela de 2FA | 3 (integra dois pacotes de terceiro por uma propriedade **estática** compartilhada) | 3 (o vazamento derruba **toda** página Filament do processo, inclusive o login) | **9** | completo |
| A2 — eixo da mídia no `register` | 1 (troca de um enum) | 1 (cosmético, reversível) | **1** | mínimo |
| A3 — confirmação de e-mail registrada e desligada | 2 (integra com o plugin e depende da **ordem** da cadeia do `panel()`) | 3 (se a exigência armar, autorização: todo usuário sem `email_verified_at` perde o painel) | **6** | padrão |

- Técnicas aplicadas: **EP exaustiva** sobre a partição "painel" (não se amostra: são três e a
  configuração é copiada à mão em três arquivos), **tabela estado × operação** na tela de 2FA,
  **rastreio de efeito** para o vazamento de `$layout`, e **matriz de decisão de 2 condições**
  para o par (rota existe × exigência ligada).
- Cenários: **9** (3 deles `Esquema do Cenário`) · Regras: 6 · Mutantes previstos: **16** · Sem matador: 0
- Um cenário da derivação original (CT-10) foi **cortado na implementação**, por deixar de ter
  objeto: a rota que ele protegeria não é mais registrada. Ver R6.

### Divergência declarada — Project Rule vence a skill

A skill sugere `pest --parallel --tia` como comando padrão. Neste projeto os comandos são
`composer test:kit` (`--testsuite=Kit,Tenancy --parallel`) e `composer test:browser`
(`--testsuite=Browser`, em série, com `npm run build` embutido) — ver `composer.json` e
`.ai/rules/testes-browser.md`. Vale o do projeto.

E vale a rule `.ai/rules/testes.md`: **nenhum helper novo** nasce nos arquivos de teste desta
feature. Os que existem em `tests/Pest.php` (`usuario()`, `usuarioDoKit()`, `noPainelBootado()`)
bastam.

## Varredura SFDIPOT

| Letra | O que existe nesta feature | Cenários gerados |
|---|---|---|
| **S**tructure | Uma classe nova (`App\Filament\Pages\Auth\TelaDoisFatores`) e três providers de painel alterados. **Nenhuma** migration, model, policy, seeder ou permission. | CT-01…CT-04 |
| **F**unction | Escolher layout de página e registrar/desregistrar recurso de painel. **Função escondida**: `$layout` é propriedade **estática** — atribuí-la é efeito de processo, não de página. | CT-01, CT-04 |
| **D**ata | Nenhum dado novo. Dois dados **existentes** decidem cenário: (a) `email_verified_at` é nulo em todo usuário que o kit semeia — é exatamente quem a exigência de verificação barraria; (b) a ausência de `breezySession` faz `hasValidTwoFactorSession()` devolver `false` (`vendor/jeffgreco13/filament-breezy/src/Traits/TwoFactorAuthenticatable.php:70-73`), então a tela de 2FA renderiza para qualquer autenticado. | CT-06, CT-08 |
| **I**nterfaces | Uma rota HTTP por painel — a de 2FA, **pré-existente**, que troca de classe — mais a submissão Livewire do código. Nenhuma rota nova: a de confirmação de e-mail não entra no ar (ADR-03). Sem comando artisan, job, webhook ou import. | CT-01, CT-05, CT-06, CT-08 |
| **P**latform | O layout do Auth Designer injeta o alternador de tema e o Alpine dele — depende do manifest do Vite e, para provar ausência de erro de JS, de navegador de verdade. | CT-B01 (arquivo `05`) |
| **O**perations | Quem chega à tela de 2FA é usuário com 2FA confirmado, redirecionado pelo `MustTwoFactor` (`vendor/jeffgreco13/filament-breezy/src/Middleware/MustTwoFactor.php:35,37`). A tela de confirmação de e-mail, por decisão desta entrega (ADR-03), **não é alcançável por ninguém** — nem por URL digitada, porque a rota não existe. | CT-05, CT-06, CT-08 |
| **T**ime | Expiração, DST e timezone **não se aplicam**: nada nesta entrega lê ou grava tempo. Mas **ordem** se aplica, e é decisiva: `Panel::plugin()` registra o plugin na hora (`vendor/filament/filament/src/Panel/Concerns/HasPlugins.php:15-21`), então a chamada de desligamento vale só se vier **depois** do `->plugins([...])`. | CT-08 |

## Mapa de Regras

| Regra | Área (perfil herdado) | Origem (`RQ`) | Técnica | Cenários |
|---|---|---|---|---|
| R1 — A tela onde se digita o código do 2FA é servida com o layout do Auth Designer, nos três painéis | A1 (completo) | RQ-01 | EP exaustiva na partição "painel" | CT-01 |
| R2 — E com a mesma mídia do login (não com um layout vazio) | A1 (completo) | RQ-01 | EP + valor discriminante (`has-media` + `media-left`) | CT-02 |
| R3 — Vestir a tela de 2FA **não** veste as demais páginas do painel | A1 (completo) | RQ-01 (escopo: o requisito nomeia UMA tela) | rastreio de efeito: 2ª direção ("não aconteceu onde não devia") | CT-03, CT-04 |
| R4 — A tela de 2FA continua recebendo e validando o código | A1 (completo) | RQ-01 ("a tela de exibição para inserir o codigo") | tabela estado × operação | CT-05, CT-06 |
| R5 — O `register` exibe a mídia no lado **inverso** ao do login, o mesmo do esqueci-a-senha | A2 (mínimo) | RQ-02, RQ-03 | EP sobre o eixo, com asserção de ausência | CT-09 |
| R6 — A tela de confirmação de e-mail nasce vestida e **não entra no ar** | A3 (padrão) | RQ-04, RQ-05 | tabela de decisão 2×2 (vestida × no ar) | CT-07, CT-08 |

**Técnica escalada acima do perfil da área**: em A2 (perfil `mínimo`, que prevê 1 cenário e só EP)
o cenário CT-09 carrega **duas** asserções — presença de `media-right` **e ausência** de
`media-left`. Motivo: sem a asserção de ausência, o cenário fica verde com as duas classes
emitidas ao mesmo tempo, que é o resultado do mutante M12.

## Fronteira com o Plano

| Item do PRD | Recusado como oráculo porque | Destino |
|---|---|---|
| o nome da classe `TelaDoisFatores` | escolha de implementação | detalhe do cenário (`Livewire::test()` precisa de um nome) |
| a chave `login` do Auth Designer (ADR-02) | escolha de implementação | **não** virou `Então`. O `Então` afirma o que o usuário vê — a mídia do login na tela de 2FA (`media-left` + `has-media`) —, que é verdade sob qualquer chave que aponte para a mesma configuração |
| `->emailVerification(null, ...)` como *mecanismo* | escolha de implementação | o `Então` de CT-08 afirma os **efeitos** (exigência falsa, painel sem tela registrada, rota inexistente), não a linha que os produz |
| `MediaPosition::Right` como *enum* | escolha de implementação | o `Então` de CT-09 afirma a classe CSS `media-right`, que é o que a CSS do pacote consome e o que o usuário vê |
| a ordem da cadeia do `panel()` | escolha de implementação | detalhe; o oráculo é o mesmo de CT-08 |

**Perguntas em aberto** — as três já estão em `00-requisito.md` → `## Ambiguidades`, com o par
Assumido / Se negado. Os cenários que dependem delas: **CT-07 e CT-08 são `@premissa`**
(premissa: a configuração da tela de confirmação de e-mail é registrada, e a tela não entra no
ar). Se a premissa for negada, R6 muda de escopo e os dois são refeitos.

## Setup Global

### Personas

- **usuário autenticado sem papel** — `usuario()` (`tests/Pest.php:312`). Basta para a tela de
  2FA: a rota do Breezy roda sob `$panel->getMiddleware()`, **não** sob o `authMiddleware`
  (`vendor/jeffgreco13/filament-breezy/routes/web.php:15`), e a checagem de sessão é feita pelo
  próprio `mount()` (`vendor/jeffgreco13/filament-breezy/src/Pages/TwoFactorPage.php:52-58`).
- **`master_global`** — `usuarioDoKit('master_global')` (`tests/Pest.php:387`), precedido de
  `$this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class])`. Necessário só onde o
  cenário abre uma **página de painel** (CT-03, CT-04), que é onde o acesso ao painel é
  verificado.

### Fixtures

- 2FA habilitado: `$user->enableTwoFactorAuthentication()`
  (`vendor/jeffgreco13/filament-breezy/src/Traits/TwoFactorAuthenticatable.php:40-51`). Exige
  painel corrente — `noPainelBootado('admin')` antes, porque `BreezySession::booted()` grava
  `panel_id` a partir do painel corrente e instala o `PanelScope`
  (`vendor/jeffgreco13/filament-breezy/src/Models/BreezySession.php:23-31`).
- Código de recuperação: `$user->breezySession->two_factor_recovery_codes[0]`. **Preferido ao
  TOTP** de propósito — `verifyRecoveryCode()` é `hash_equals` contra o que está gravado
  (`vendor/…/Concerns/Plugin/HasTwoFactorAuthentication.php:104-114`), então o cenário é
  determinístico e não depende de janela de tempo.

### Fakes

Nenhum. Não há fila, e-mail, notificação nem HTTP nesta entrega. `Http::preventStrayRequests()`
não se aplica.

### Estratégia de DB

`RefreshDatabase` global, aplicado a `tests/Kit` em `tests/Pest.php:45-48`.

---

## Regra R1 — A tela de 2FA é servida com o layout do Auth Designer, nos três painéis

> `RQ-01` · perfil **completo** · técnica: **EP exaustiva** na partição "painel"

Por que exaustiva e não amostrada: a configuração do Auth Designer é **copiada à mão** em três
arquivos de provider, e o defeito histórico do kit nessa área é justamente configurar um painel
e esquecer os outros dois — é o que o comentário de `AppPanelProvider.php:201-210` documenta.
Três painéis, três linhas de `Exemplos`.

```gherkin
# language: pt

Funcionalidade: Auth Designer nas telas de autenticação

  Regra: a tela de desafio do 2FA usa o layout do Auth Designer

    Esquema do Cenário: [CT-01] a tela do código de 2FA sai vestida em cada painel
      Dado um usuário autenticado no painel "<painel>"
      Quando ele abre a tela onde se digita o código do 2FA desse painel
      Então a página é servida com o layout de autenticação do Auth Designer
      E não é servida com o layout simples do Filament

      Exemplos:
        | painel | # partição |
        | app    | painel de negócio, com tenancy |
        | admin  | painel de administração        |
        | infra  | painel de infraestrutura       |
```

Oráculo concreto: presença de `fi-auth-layout` e **ausência** de `fi-simple-layout` no HTML —
as duas classes que o `layouts/auth.blade.php` do pacote e o `layout.simple` do Filament
emitem, e o mesmo par que `tests/Kit/BloqueioDeSessaoTest.php:83-91` já usa como oráculo.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|--------------------------------|------------------|
| M1 | a subclasse é criada mas ninguém a liga no painel — `enableTwoFactorAuthentication()` fica sem `action:` | CT-01 (as três linhas) |
| M2 | `enableTwoFactorAuthentication(TelaDoisFatores::class)` **posicional**: o class-string cai em `$condition` e `$action` fica no default do Breezy (`HasTwoFactorAuthentication.php:29`) | CT-01 |
| M3 | a subclasse é ligada só no painel `app` (o mais visitado), e `admin`/`infra` ficam crus | CT-01 (linhas `admin` e `infra`) |
| M4 | a subclasse existe e é ligada, mas sem `use HasAuthDesignerLayout` — o `$layout` fica no valor de `SimplePage` (`vendor/filament/filament/src/Pages/SimplePage.php:12`) | CT-01 |

---

## Regra R2 — E com a mesma mídia do login, não com um layout vazio

> `RQ-01` · perfil **completo** · técnica: **EP + valor discriminante**

Este cenário existe porque `fi-auth-layout` **sozinho não discrimina**. O blade do pacote emite
a classe do layout sempre; a mídia e o eixo saem de `$config->hasMedia()` e `$config->position`
(`vendor/caresome/filament-auth-designer/resources/views/components/layouts/auth.blade.php:7-9,28`).
Chave de configuração não registrada não estoura: `AuthDesignerConfigRepository` devolve um
`AuthPageConfig` vazio, e a tela sai com `no-media`, sem eixo e sem alternador de tema — **com
`fi-auth-layout` presente**. É a armadilha que `AppPanelProvider.php:201-210` descreve.

```gherkin
  Regra: a tela do 2FA mostra a mesma arte do login, do mesmo lado

    Cenário: [CT-02] a tela do código de 2FA herda a mídia e o eixo do login
      Dado um usuário autenticado no painel de administração
      Quando ele abre a tela onde se digita o código do 2FA
      Então a página traz a seção de mídia preenchida
      E a arte fica do mesmo lado em que fica no login
```

Oráculo concreto: `has-media` presente, `no-media` ausente, `media-left` presente — o mesmo
eixo que `tests/Kit/TelasDeAutenticacaoTest.php:17-23` já fixa para o login.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|--------------------------------|------------------|
| M5 | `getAuthDesignerPageKey()` devolve uma chave não configurada (`'two-factor'`, `'profile'`) — tela vestida e vazia | CT-02 (`no-media` presente) |
| M6 | a chave devolvida é `'password-reset'`: mídia existe, mas o eixo fica **espelhado** em relação ao login | CT-02 (`media-left` ausente) |

---

## Regra R3 — Vestir a tela de 2FA não veste as demais páginas do painel

> `RQ-01` (escopo: o requisito nomeia **uma** tela) · perfil **completo** ·
> técnica: **rastreio de efeito, 2ª direção** — "não aconteceu onde não devia"

`HasAuthDesignerLayout::boot()` faz `static::$layout = …`
(`vendor/caresome/filament-auth-designer/src/Concerns/HasAuthDesignerLayout.php:14`). Sem
redeclaração da propriedade na subclasse, a escrita cai no estático do ancestral e passa a valer
para o processo. **CT-01 fica verde com esse defeito presente** — é CT-03/CT-04 que o distingue,
e é o par que `.ai/rules/auth.md` cobra.

```gherkin
  Regra: o layout de autenticação não escapa para o resto do painel

    Cenário: [CT-03] a página comum do painel não usa o layout de autenticação
      Dado um administrador autenticado que ainda não abriu nenhuma tela de autenticação
      Quando ele abre o painel de administração
      Então a página não é servida com o layout de autenticação do Auth Designer

    Cenário: [CT-04] abrir a tela do 2FA não contamina a página comum aberta depois
      Dado um administrador autenticado que acabou de abrir a tela do código de 2FA
      Quando ele abre o painel de administração
      Então a página não é servida com o layout de autenticação do Auth Designer
```

CT-03 é a linha de base do mesmo processo (sem ela, CT-04 não distingue "não vazou" de "nunca
teve"); CT-04 é o cenário que mata o mutante.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|--------------------------------|------------------|
| M7 | a subclasse **não** redeclara `protected static string $layout` — a atribuição da trait vaza para `SimplePage` e todo o processo | CT-04 |
| M8 | a redeclaração existe mas com o valor errado (`'filament-panels::components.layout.simple'`) | CT-01 |
| M9 | alguém "simplifica" removendo a redeclaração por parecer duplicada com a trait | CT-04 |

---

## Regra R4 — A tela de 2FA continua recebendo e validando o código

> `RQ-01` — o requisito chama a tela de "tela de exibição para inserir o codigo" ·
> perfil **completo** · técnica: **tabela estado × operação**

Tabela estado × operação (a operação é sempre `enviar código`, o estado é o do 2FA do usuário):

| Estado do usuário | `enviar código` inválido | `enviar código` válido |
|---|---|---|
| 2FA habilitado, sessão de 2FA ausente | recusa, sessão **não** marcada — **CT-05** | aceita, sessão marcada, código consumido — **CT-06** |
| sem 2FA habilitado | fora de escopo: é o estado em que o Breezy nem exige código; não muda nesta entrega | idem |

A célula válida (`CT-06`) é o equivalente, aqui, do gate de tela de escrita: **uma tela de 2FA
que abre e não autentica é o mesmo defeito de "tela aberta não é tela que grava"**
(`.ai/rules/testes.md`).

```gherkin
  Regra: a tela vestida continua autenticando pelo código

    Cenário: [CT-05] código errado é recusado e não abre a sessão de 2FA
      Dado um administrador com 2FA habilitado e sem sessão de 2FA aberta
      Quando ele envia um código que não é o dele
      Então a tela acusa erro no campo do código
      E a sessão de 2FA continua fechada

    Cenário: [CT-06] código de recuperação correto abre a sessão de 2FA e é consumido
      Dado um administrador com 2FA habilitado e sem sessão de 2FA aberta
      Quando ele envia um dos códigos de recuperação dele
      Então a sessão de 2FA passa a estar aberta
      E aquele código de recuperação deixa de existir na lista dele
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|--------------------------------|------------------|
| M10 | a subclasse sobrescreve `authenticate()`, `getFormSchema()` ou `$view` "para caber no layout" e quebra o envio | CT-05 e CT-06 |
| M11 | a classe funciona no `GET` mas o componente Livewire não resolve pelo nome (o alias `two-factor-page` do Breezy aponta para a classe **dele** — `vendor/jeffgreco13/filament-breezy/src/FilamentBreezyServiceProvider.php:40`), então nenhuma interação da tela funciona | CT-05 e CT-06 (ambos passam pelo registry do Livewire) |

---

## Regra R5 — O `register` exibe a mídia no lado inverso ao do login

> `RQ-02`, `RQ-03` · perfil **mínimo** · técnica: **EP sobre o eixo, com asserção de ausência**

"Inverso" tem referência declarada no requisito: *"como esta o esqueci a senha"*. O
esqueci-a-senha do kit já é medido por `tests/Kit/TelasDeAutenticacaoTest.php:25-30` com
`media-right`, e o login por `:17-23` com `media-left`. Logo o oráculo do `register` é
`media-right`.

O `register` do kit recusa quem chega sem convite válido e redireciona para o login
(`app/Filament/Pages/Auth/RegistroPorConvite.php:200-255`), então o cenário **precisa** de um
convite válido — sem ele o teste mediria a tela de login e ficaria verde com o eixo errado.

```gherkin
  Regra: a tela de aceite de convite espelha o login

    Cenário: [CT-09] a tela de aceite mostra a arte do lado oposto ao do login
      Dado um convite válido para um endereço que ainda não tem conta
      Quando o convidado abre a tela de aceite pelo link do convite
      Então a arte aparece do lado oposto ao do login
      E não aparece do lado em que aparece no login
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|--------------------------------|------------------|
| M12 | o eixo é acrescentado sem remover o anterior, e as duas classes saem no HTML | CT-09 (asserção de ausência de `media-left`) |
| M13 | o eixo é trocado no bloco de `login` em vez do de `registration` — os dois blocos são quase idênticos e ficam a 20 linhas um do outro | CT-09 + `TelasDeAutenticacaoTest.php:17-23`, que passa a reprovar |

---

## Regra R6 — A confirmação de e-mail nasce vestida, e não entra no ar

> `RQ-04`, `RQ-05` · perfil **padrão** · técnica: **tabela de decisão 2×2** · `@premissa`

Duas condições independentes — a tela está **vestida** (configuração do Auth Designer para a
chave `email-verification`) e a tela está **no ar** (rota registrada):

| # | vestida | no ar | Atende o requisito? | Cenário |
|---|---|---|---|---|
| 1 | não | não | não — RQ-04 falha (é o estado antes desta entrega) | CT-07 reprova aqui |
| 2 | sim | **sim** | não — MEDIDO, a tela responde **500**: `EmailVerificationPrompt::getVerifiable()` declara retorno `MustVerifyEmail` (`vendor/filament/filament/src/Auth/Pages/EmailVerification/EmailVerificationPrompt.php:36-43`, chamada no `mount()` em `:31`) e `App\Models\User` não implementa a interface | CT-08 reprova aqui |
| 3 | **sim** | **não** | **sim** — vestida para quando alguém ligar, e sem rota que erre | CT-07 + CT-08 |
| 4 | não | sim | tela crua no ar: pior de todos | CT-07 reprova aqui |

**A revisão que mudou esta regra.** A primeira derivação punha o oráculo de RQ-04 num
`$this->get()` da tela. Foi implementado e reprovou nos três painéis com o `TypeError` acima —
achado de implementação que virou ADR-03. O oráculo passou para o objeto de configuração, que é
onde a afirmação "o auth designer está implementado nela" de fato mora.

```gherkin
  Regra: a tela de confirmação de e-mail está vestida, e desligada

    Esquema do Cenário: [CT-07] @premissa a confirmação de e-mail já está vestida em cada painel
      Dado o painel "<painel>" bootado como o kit o entrega
      Quando se consulta a configuração de autenticação da tela de confirmação de e-mail
      Então ela tem mídia
      E a mídia está do lado oposto ao do login
      E o alternador de tema está ligado

      Exemplos:
        | painel | # partição |
        | app    | painel de negócio  |
        | admin  | painel de admin    |
        | infra  | painel de infra    |

    Esquema do Cenário: [CT-08] @premissa nenhum painel põe a tela no ar
      Dado o painel "<painel>" configurado como o kit o entrega
      Quando se consulta se ele exige e-mail verificado
      Então a resposta é "não"
      E ele não tem tela de confirmação de e-mail registrada
      E a rota da tela não existe

      Exemplos:
        | painel |
        | app    |
        | admin  |
        | infra  |
```

O par é indissociável: CT-07 sozinho ficaria verde com a tela no ar (o caso 2, que responde
500); CT-08 sozinho ficaria verde com o bloco inteiro removido (o caso 1, o estado anterior).

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|--------------------------------|------------------|
| M14 | passar só pelo plugin, sem a chamada de desligamento — `isRequired` fica no default `true` e a rota entra no ar respondendo 500 | CT-08 |
| M15 | mover o `->emailVerification(null, isRequired: false)` para **antes** do `->plugins([...])`: o plugin registra na hora (`HasPlugins.php:15-21`) e sobrescreve de volta | CT-08 |
| M16 | chamar só `$panel->emailVerification(null, ...)`, sem o bloco no plugin: nada entra no ar, mas a chave `email-verification` não é gravada no repositório e a tela **não está vestida** | CT-07 |
| M17 | vestir a tela com o eixo do login (`MediaPosition::Left`) em vez do espelho | CT-07 (asserção sobre `position`) |
| M18 | esquecer o `->themeToggle()` no bloco novo — a tela nasceria sem alternador, como o `registration` nascia antes da wiki `identidade-visual-da-organizacao` | CT-07 (asserção sobre `showThemeSwitcher`) |

> **Teto**: o perfil `padrão` prevê 3 cenários por regra; R6 tem **2** (CT-07 e CT-08), ambos
> `Esquema do Cenário`. O CT-10 da derivação original ("a tela nova não é superfície pública")
> foi **cortado**: sem rota não há superfície a proteger, e a asserção de rota inexistente de
> CT-08 é o mesmo oráculo, mais forte.

---

## Checklist de Taxonomia

| Item | Cenário que mata |
|---|---|
| IDOR / autorização horizontal | **não se aplica**: nenhuma rota desta entrega recebe `{id}` de recurso do usuário — e a `email-verification/verify/{id}/{hash}` do Filament nem chega a ser registrada (CT-08) |
| Autorização exercida na ação (não só `can()`) | **não se aplica**: a entrega não acrescenta ação nem rota. As três rotas de 2FA já existiam e trocaram de classe; a de confirmação de e-mail não entra no ar (CT-08) |
| Superfície nova não é pública | CT-08 — não há superfície nova, pública ou autenticada |
| Idempotência (ancorada no agregado) | **não se aplica**: a entrega não tem operação de escrita. A única escrita no caminho é o consumo do código de recuperação, que é comportamento de vendor não alterado — e CT-06 já ancora no agregado (a lista de códigos do usuário) |
| Concorrência | **não se aplica**: sem contador, saldo ou limite |
| Fronteira no ponto de entrada (gravação) | **não se aplica**: nenhum campo novo, nenhuma gravação nova |
| Domínio condicionado | **não se aplica** |
| Estado × operação | CT-05, CT-06 (tabela em R4) |
| Ausente ≠ null ≠ vazio | **não se aplica**: nenhum campo opcional novo |
| Paginação / ordenação | **não se aplica**: nenhuma listagem |
| Timezone / DST | **não se aplica**: a entrega não lê nem grava tempo |
| Unicode / limite de varchar | **não se aplica**: nenhum campo de texto novo |
| Unicidade + soft delete | **não se aplica** |
| CRUD combinado | **não se aplica** |
| Mass assignment | **não se aplica**: nenhum formulário novo; a submissão de CT-05/CT-06 usa o schema do Breezy, com um campo só |
| Upload | **não se aplica** |
| Precisão monetária | **não se aplica** |
| **Ordem de configuração** (linha nova, específica deste projeto) | CT-08 — `Panel::plugin()` registra na hora, então a ordem da cadeia do `panel()` é comportamento, não estilo |
| **Efeito de propriedade estática** (linha nova) | CT-04 — atribuir estático em `boot()` é efeito de processo |

> As duas últimas linhas são candidatas a entrar na taxonomia do projeto. Ver
> `03-progresso.md` → *Candidatos a Rule*.

## Índice de Cenários

| ID | Cenário | Regra | Técnica | Camada | Arquivo | Mata |
|----|---------|-------|---------|--------|---------|------|
| CT-01 | 2FA vestida nos três painéis | R1 | EP exaustiva | Feature (HTTP) | `tests/Kit/TelasDeAutenticacaoTest.php` | M1, M2, M3, M4, M8 |
| CT-02 | 2FA herda mídia e eixo do login | R2 | EP + valor discriminante | Feature (HTTP) | `tests/Kit/TelasDeAutenticacaoTest.php` | M5, M6 |
| CT-03 | página comum limpa (linha de base) | R3 | rastreio de efeito | Feature (HTTP) | `tests/Kit/TelasDeAutenticacaoTest.php` | — (linha de base de CT-04) |
| CT-04 | 2FA não contamina a página comum | R3 | rastreio de efeito | Feature (HTTP) | `tests/Kit/TelasDeAutenticacaoTest.php` | M7, M9 |
| CT-05 | código errado recusado, sessão fechada | R4 | estado × operação | componente Livewire | `tests/Kit/TelasDeAutenticacaoTest.php` | M10, M11 |
| CT-06 | código de recuperação abre a sessão e é consumido | R4 | estado × operação (célula válida) | componente Livewire | `tests/Kit/TelasDeAutenticacaoTest.php` | M10, M11 |
| CT-07 | confirmação de e-mail já vestida nos três painéis | R6 | tabela de decisão | Feature (repositório de config, painel bootado) | `tests/Kit/TelasDeAutenticacaoTest.php` | M16, M17, M18 |
| CT-08 | nenhum painel põe a tela no ar | R6 | tabela de decisão | Feature (`Filament::getPanel()` + `Route::has()`) | `tests/Kit/TelasDeAutenticacaoTest.php` | M14, M15 |
| CT-09 | aceite de convite com a arte espelhada | R5 | EP + ausência | Feature (HTTP) | `tests/Kit/TelasDeAutenticacaoTest.php` | M12, M13 |

Todos os nove ficam em `tests/Kit/TelasDeAutenticacaoTest.php`: é o arquivo cujo assunto declarado
é o layout das telas de autenticação, e a rule `.ai/rules/testes.md` desaconselha espalhar o
mesmo assunto por arquivos novos. O docblock do arquivo precisa deixar de dizer "telas públicas".

## CT-B

Há um cenário de browser — ver `05-casos-de-teste-browser.md`. Motivo do gate: os cenários
acima afirmam sobre **classe no HTML** ou sobre objeto de configuração, e as duas coisas se
provam sem navegador. O que só
o navegador prova é que a tela de 2FA, movida para um layout que ela não foi desenhada para
habitar, **carrega sem erro de JavaScript** — o layout do Auth Designer injeta o alternador de
tema e o Alpine dele, e nenhuma dessas falhas move o status HTTP.
