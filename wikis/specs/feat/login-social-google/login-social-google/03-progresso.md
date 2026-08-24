# Progresso — Login social com Google

Espelha os 12 passos de `01-plano-acao.md` → `## Estrutura de Implementação`.

## 1. Instalar o `laravel/socialite`

- [x] `composer require laravel/socialite` → `^5.30`
- [x] `composer.json` **e** `composer.lock` no mesmo commit (o CI instala a partir do lock)

## 2. Bloco `google` em `config/services.php`

- [x] `client_id`, `client_secret`, `redirect` — o bloco literal do requisito
- [x] Comentário explicando por que o `redirect` é relativo e por que o interruptor não vive aqui

## 3. Bloco `login` em `config/kit.php`

- [x] `login.google.habilitado` com `filter_var(..., FILTER_VALIDATE_BOOLEAN)`
- [x] `login.rodape`
- [x] Comentário de bloco: default false, por que a coerção falha **fechada** (e por que as três
      chaves irmãs com cast de bool **não** estão erradas), e que o login autentica sem criar conta

## 4. `App\Support\ConfiguracaoDoLogin` — o ponto único de ligação com o Settings

- [x] `googleDisponivel(): bool`
- [x] `rodapeDoLogin(): ?string`
- [x] `registroAberto(): bool`
- [x] Docblock declarando o contrato: quando o Settings mergear, só o corpo destes três métodos muda

## 5. `App\Http\Controllers\Auth\LoginComGoogleController`

- [x] `redirecionar()` com `abort_unless` e sem `stateless()`
- [x] `retorno()` com as barreiras na ordem: disponibilidade → provedor → e-mail verificado → e-mail presente → conta → registro aberto
- [x] `recusar()` privado: notifica, volta para o login, nunca autentica e nunca grava
- [x] Logs no channel `autenticacao`, e-mail mascarado, sem segredo e sem token

## 6. As duas rotas em `routes/web.php`

- [x] `GET /auth/google/redirect` → `auth.google.redirect`
- [x] `GET /auth/google/callback` → `auth.google.callback` (path literal do requisito)
- [x] `throttle:10,1` nas duas

## 7. Registrar os dois render hooks no `KitServiceProvider`

- [x] `configureTelaDeLogin()` chamado no `boot()`
- [x] `FilamentView::registerRenderHook(AUTH_LOGIN_FORM_AFTER, ...)` sem escopo — uma registração, três painéis
- [x] Botão registrado antes do rodapé (a ordem de render é a de registro)

## 8. Blade do botão

- [x] `resources/views/filament/auth/botao-google.blade.php`
- [x] SVG inline do Google (quatro cores), `aria-hidden`
- [x] `<x-filament::button tag="a">` — não `<a>` com utilitárias Tailwind
- [x] `aria-label="Entrar com Google"` (âncora do CT-B01 e leitor de tela)

## 9. Blade do rodapé

- [x] `resources/views/filament/auth/rodape-login.blade.php`
- [x] Saída **escapada** — nunca a sintaxe não escapada do Blade

## 10. Chaves novas no `.env.example`

- [x] `KIT_SOCIALITE_GOOGLE=false`
- [x] `GOOGLE_CLIENT_ID=` e `GOOGLE_CLIENT_SECRET=` (vazias)
- [x] `KIT_LOGIN_RODAPE=`

## 11. Documentação nos dois READMEs

- [x] `README.md` — seção "Login social (Google)"
- [x] `README.en.md` — a mesma seção traduzida

## 12. Testes

- [x] `tests/Kit/LoginSocialGoogleTest.php` — CT-01 a CT-23 + CT-18b (54 casos executados, porque
      cada `Esquema do Cenário` expande em linhas)
- [x] `tests/Browser/LoginSocialGoogleTest.php` — CT-B01

## Verificação Final

- [x] `/ponytail:ponytail-review` no diff
- [x] `vendor/bin/pint --dirty --format agent`
- [x] `vendor/bin/phpstan analyse --no-progress` — 0 erros
- [x] `php artisan test --testsuite=Kit --filter=LoginSocialGoogle --compact`
- [x] `php artisan test --testsuite=Unit,Feature,Kit,Tenancy` — a base tinha 662
- [x] `composer test:browser`
- [x] Roteiro "Desenhado × Implementado" do `05` preenchido
- [x] `git push -u origin feat/login-social-google` (sem PR, sem merge)

## Auditoria Pré-Implementação

### Revisão profunda (step 5) — premissas do plano contra o código real

| Premissa do plano | O código real diz | Correção aplicada na wiki |
|---|---|---|
| "o botão vai na `TelaLogin`, subclasse do kit" | `TelaLogin` só é usada no `/app` (`AppPanelProvider.php:191`); `/admin` e `/infra` usam a `Login` do pacote direto (`AdminPanelProvider.php:113`, `InfraPanelProvider.php:146`) | plano reescrito para render hook global; ADR-05 escrito |
| "o rodapé vai no hook `FOOTER`, que o layout do Auth Designer renderiza" | o layout renderiza (`auth.blade.php:63`), **mas o layout de painel autenticado também** — o rodapé apareceria em toda tela de todo painel | rodapé movido para `AUTH_LOGIN_FORM_AFTER`; recusa registrada em ADR-05 |
| "criar channel de log `login-social`" | o channel `autenticacao` já existe (`config/logging.php:132-135`) e já recebe a negativa de acesso a painel (`app/Models/User.php:95-102`) | plano passou a reusar; escada do Ponytail para no primeiro degrau |
| "o `state` de CSRF é do Socialite, basta não desligar" | verdade, **e o fake o ignora**: `FakeProvider::user()` devolve o usuário sem checar state (`Testing/FakeProvider.php:71-78`) | CT-05 passou a usar o provedor **real**; a nota está em R4 do `04` |
| "`verified_email` é o campo do Google" | o `GoogleProvider` popula **as duas** chaves, e `verified_email` é o alias legado mantido por compatibilidade (`Two/GoogleProvider.php:90-92`); a do userinfo v3 é `email_verified` | leitura passou a conferir as duas, nessa ordem; CT-11 ganhou a linha do alias |
| "criar coluna `google_id` como na doc do Socialite" | o `updateOrCreate` da doc **cria conta**, o que contorna o convite obrigatório (`RegistroPorConvite.php:48-56`, `config/kit.php:223-224`) | nenhuma coluna, nenhuma migration; ADR-06 e ADR-07 |
| "`Auth::login()` pode contornar o 2FA" | não contorna: `MustTwoFactor` está no stack por default (`filament-breezy/src/Concerns/Plugin/HasTwoFactorAuthentication.php:29`) e redireciona ao desafio (`Middleware/MustTwoFactor.php:42-43`) | virou CT-16 em vez de comentário — barreira de terceiro não é barreira garantida |
| "`Auth::login()` precisa de `session()->regenerate()` contra fixação" | o `SessionGuard::login()` já faz `migrate(true)` | passo removido do plano |
| "a trilha de acesso precisa de código nesta feature" | o `rappasoft/laravel-authentication-log` já escuta `Illuminate\Auth\Events\Login` (`LaravelAuthenticationLogServiceProvider.php:35`) e grava em `authentication_log` (`Models/AuthenticationLog.php:33`) | nenhuma linha escrita; virou CT-21, que é o único cenário que mata "abrir a sessão por fora do `Auth::login()`" |
| "`(bool) env()` serve para o interruptor" | ⚠️ **esta linha estava errada** e só foi desmentida na implementação: o `Env::getOption()` do Laravel já converte `"false"` (`.../Support/Env.php:252-262`). A diferença real é de direção — `off`/`no`/lixo | `filter_var` mantido, **justificativa reescrita**; ver Notas de Implementação, item 1 |
| "o `.env.example` é o único lugar de env fixada em teste" | o `phpunit.xml` fixa seis chaves `KIT_*` com `force="true"` | CT-01 ganhou o `Dado` declarando que `KIT_SOCIALITE_GOOGLE` **não** está fixado — sem isso o cenário mediria o `phpunit.xml` |

### Auditoria Ponytail (step 6)

| # | Sugestão de corte | Aplicada? | Onde |
|---|---|---|---|
| 1 | Não criar interface `ProvedorSocial` nem enum de provedores — um provedor não justifica abstração | sim | ADR-10 |
| 2 | Não criar channel de log próprio — `autenticacao` já existe | sim | `01`, passo 5 |
| 3 | Não criar coluna `google_id` nem tabela `social_accounts` | sim | ADR-07 |
| 4 | Não adicionar pacote de ícones de marca por um ícone | sim | ADR-04 |
| 5 | Não escrever regra em `resources/css/filament/kit.css` para três propriedades (evita o `filament:assets` no fluxo de quem instala) | sim | `01`, passos 8 e 9 — `style` inline, marcado com comentário `ponytail:` |
| 6 | Não criar middleware para o guarda de disponibilidade — `abort_unless` em duas linhas do mesmo controller | sim | ADR-03 |
| 7 | Não gravar token de acesso nem refresh token: o kit não chama API do Google | sim | ADR-07, tabela de mapeamentos |
| 8 | Não criar `App\Support\BooleanoDoEnv` — `filter_var` da stdlib resolve | sim | ADR-08 |
| 9 | Cortar o cenário de `throttle` (429): o limite é escolha do PRD, não cláusula do requisito | sim | `04`, "Cogitado e cortado" |
| 10 | Cortar o CT-B de clique no botão: o destino se prova pelo `href`, sem sair para a rede | sim | `05`, "Cogitado e cortado" |
| 11 | Não subclassar a tela de login dos outros dois painéis — uma registração global cobre os três | sim | ADR-05 |
| 12 | Reduzir os três cenários de rastreio de efeito a um | **recusada** — regra de efeito colateral exige as três direções (aconteceu / não aconteceu quando não devia / pelo canal certo), e é a terceira (CT-21) que mata "abrir a sessão por fora do `Auth::login()`", que nenhum outro cenário vê | `04`, R15 |
| 13 | Cortar CT-15 (escape do rodapé), já que o perfil da área é `mínimo` | **recusada** — é XSS armazenado em página **não autenticada**, e nenhum exemplo de R10 distingue a implementação defeituosa. Escalonamento declarado | `04`, R11 |

## Blockers

Nenhum. A dependência da tela de Settings foi resolvida por desenho (ADR-02) em vez de espera.

## Desvios do Plano

| Passo | O que o plano dizia | O que foi feito | Por quê |
|---|---|---|---|
| 3 e ADR-08 | `filter_var` porque `(bool) env()` transformaria a string `"false"` em `true` | `filter_var` **mantido**, com a justificativa **reescrita** | A justificativa era **factualmente falsa** — ver Notas de Implementação, item 1. É o desvio mais importante desta wiki |
| 5 | `retorno()` com seis barreiras | **sete**: entrou o `instanceof AbstractUser` antes de ler o payload bruto | O PHPStan reprovou `getRaw()` no contrato `Socialite\Contracts\User`. A correção **não** foi alargar o tipo: provedor que não exponha o bruto não permite conferir a verificação, e a resposta é **não**. A estreiteza do tipo virou a barreira |
| 5 | destino da conta nova é a URL do perfil | a URL do perfil **quando resolvível**; painel quando não | Com multi-tenancy ligada a rota do perfil exige o slug de uma organização, e conta recém-criada por login social não pertence a nenhuma. Marcado com comentário `ponytail:` nomeando o teto e o caminho de upgrade |
| 12 | um arquivo de teste com 23 casos | 54 casos executados (os `Esquema do Cenário` expandem em linhas) | Nenhum CT novo: são as linhas de `Exemplos` contadas pelo Pest |
| `04`, R14 | varredura de `(bool) env(` no `config/kit.php` inteiro | varredura **removida**; ficou a presença da coerção + um caso comportamental | A varredura reprovou e estava certa em reprovar. Ver Notas de Implementação, item 1 |
| `04`, Setup Global | `Http::preventStrayRequests()` como rede de segurança | **removido**, com o motivo escrito | O Socialite usa o Guzzle dele, não o cliente do Laravel; a facade `Http` não intercepta nada dele. A promessa era falsa |
| `05` | mutante MB5 (SVG malformado) sem matador | mantido sem matador | Débito DL-05, já declarado |

## Notas de Implementação

### 1. A ADR-08 estava errada, e foi o teste dela que a denunciou

O caso de teste que a própria ADR pediu — varrer o `config/kit.php` afirmando a ausência de
`(bool) env(` — **reprovou**, porque três chaves irmãs do mesmo arquivo usam exatamente isso:
`kit.tenancy.enabled` (`:83`), `kit.demo` (`:115`) e `kit.hub` (`:144`).

Medido no vendor **depois** da reprovação, que é a ordem errada e é justamente o que
`.ai/rules/specs.md` proíbe: o `Env::getOption()` do Laravel já converte `"true"`, `"false"`,
`"(false)"`, `"null"` e `"empty"` em valor PHP antes de devolver
(`vendor/laravel/framework/src/Illuminate/Support/Env.php:252-262`). Medido no projeto:

| Valor | `env()` devolve | `(bool)` | `filter_var` |
|---|---|---|---|
| `false` | `false` | `false` | `false` |
| (vazio) | `''` | `false` | `false` |
| `off` | `'off'` | **`true`** | **`false`** |
| `no` | `'no'` | **`true`** | **`false`** |
| `lixo` | `'lixo'` | **`true`** | **`false`** |

Ou seja: para **todo valor documentado** os dois jeitos empatam, e a diferença real é de
**direção** — o cast falha aberto, o `filter_var` falha fechado. A decisão (`filter_var`) segue
valendo, por ser um interruptor de segurança que abre superfície pública de OAuth; as três irmãs
**não** estão erradas e o comentário do `config/kit.php` agora diz para não "consertá-las".

**Esta é a forma exata do defeito que `.ai/rules/specs.md` descreve**: a conclusão certa
sustentada por um motivo errado. Se a varredura tivesse sido "corrigida" no sentido oposto — em
nome da consistência — esta feature teria mexido em `kit.tenancy.enabled`, a chave mais
consequente do kit.

### 2. `Socialite::fake()` não passa pela verificação de `state`

`FakeProvider::user()` devolve o usuário falso direto
(`vendor/laravel/socialite/src/Testing/FakeProvider.php:71-78`), sem chamar `hasInvalidState()`.
Nenhum cenário faked pode falsificar a proteção de CSRF, então CT-05 usa o provedor **real** — e
ainda assim não toca a rede, porque `hasInvalidState()` roda antes de `getAccessTokenResponse()`
(`Two/AbstractProvider.php:230-241`).

Junto vem a segunda correção: **`Http::preventStrayRequests()` não protege contra o Socialite**.
Ele usa o Guzzle dele (`AbstractProvider::getHttpClient()`), não o cliente do Laravel, e a facade
`Http` não intercepta nem impede nada. A wiki prometia essa rede de segurança e estava errada.

### 3. `getRaw()` não está no contrato — e a correção virou barreira

O PHPStan reprovou `Laravel\Socialite\Contracts\User::getRaw()`. O contorno óbvio (alargar o tipo
ou anotar) estava proibido, e a saída certa era melhor que o código original: `instanceof
AbstractUser`, e **`false` quando não é**. Provedor que não exponha o payload bruto não permite
conferir a verificação de e-mail, e aí a resposta é não. O tipo estreito é a decisão de segurança.

### 4. Quatro premissas do plano que o kit já resolvia

Confirmadas na implementação, e **nenhuma linha foi escrita** para elas:

- `Auth::login()` já regenera o id de sessão (`SessionGuard::login()` → `migrate(true)`);
- o 2FA já é imposto pelo `MustTwoFactor` do Breezy em todo request de painel;
- a trilha de acesso do `/infra` já escuta `Illuminate\Auth\Events\Login`;
- o channel `autenticacao` já existia, com a régua de e-mail mascarado.

As duas primeiras viraram **caso de teste** (CT-16) e a terceira também (CT-21) — não porque o kit
possa perdê-las, mas porque uma implementação que abra a sessão por fora do `Auth::login()` passa
em todos os outros casos e desaparece da trilha, sem erro nenhum.

### 5. `assertSeeInOrder` sobre `form.password`

A âncora de "abaixo do formulário" é o `id` gerado do campo de senha, e não o rótulo traduzido: o
texto do rótulo aparece mais de uma vez na tela vestida pelo Auth Designer, e o `id` é único.

## Débitos declarados

Cada um tem a razão de não ter sido fechado agora e o gatilho para fechar.

| # | Débito | Gatilho para fechar |
|---|---|---|
| DL-01 | **Conferir as quatro condições do `client_secret` no banco** (encriptação, campo `password()` não repopulado, fora da auditoria, fora do export) quando `feat/settings-do-kit` mergear. Escrito em ADR-09 porque quem introduz o segredo é esta feature | rebase em `origin/main` depois do merge do Settings |
| DL-02 | **Ligar `ConfiguracaoDoLogin` ao Settings real** — hoje os três métodos leem `config()` | idem |
| DL-03 | **Papel de quem se registra por login social**: esta feature não atribui nenhum, e o mutante M36 ficou sem matador porque asserir sobre papel congelaria uma decisão da branch `feat/registro-e-aprovacao` | merge daquela branch |
| DL-04 | **Concorrência na criação de conta**: dois callbacks simultâneos com o registro aberto. `users.email` é único, então a segunda gravação falha no banco — o tratamento dessa colisão é comportamento que o requisito não determina | quando o registro aberto existir de verdade |
| DL-05 | **SVG malformado do ícone** (mutante MB5) não tem matador: nem `assertNoJavaScriptErrors` nem o Pint enxergam | se o ícone quebrar uma vez, virar caso com âncora no `<svg>` |
| DL-06 | **Legibilidade do rodapé em tema escuro** (mutante MB4, metade "cor sobre cor"): `assertSee` não valida tema e para defeito de cor não há saída barata | screenshot e olhar, se alguém reclamar |
| DL-07 | **Acessibilidade da tela de login** com a superfície nova: a tela é de plugin de terceiro e já tem achados próprios; um cenário aqui mediria a dívida do vendor | auditoria de acessibilidade do kit, quando houver |
| DL-09 | **Volume de comentário** acima do que o código sustenta em alguns trechos (QA-04). Os piores foram cortados; o restante segue o estilo da casa — `config/kit.php` tem 291 linhas majoritariamente de comentário | revisão de estilo do kit, se alguém a fizer |
| DL-08 | **Nível do channel `autenticacao`**: fica em `debug` durante o desenvolvimento da feature. Reduzir depois de a feature estabilizar | duas releases depois do merge |

## Candidatos a Rule de Projeto (step 9 — decisão do usuário)

Avaliados nos quatro gates. Teto de três por feature, respeitado.

### 1. Render hook global cobre os três painéis; hook por painel é o defeito histórico

- **Glob**: `app/Providers/KitServiceProvider.php`, `app/Providers/Filament/**`
- **Evidência**: ADR-05 + `vendor/filament/support/src/View/ViewManager.php:32-34,93-96`; o docblock de
  `tests/Kit/TelasDeAutenticacaoTest.php:76-78` já nomeia "configurar um painel e esquecer os
  outros dois" como o defeito histórico do kit nessa área
- **Gates**: durável ✅ (vale para toda superfície nova de tela de auth) · escopável ✅ ·
  não-inferível ✅ (o default do `registerRenderHook` sem escopo não é óbvio; a leitura natural é
  registrar por painel) · não-redundante ✅ (`.ai/rules/providers-filament.md` fala de **plugin**
  nos três painéis, não de render hook)
- **Observação**: candidato mais forte dos três — é o inverso exato de uma rule que já existe, e
  quem lê a rule de plugin conclui "logo, replique nos três", que aqui é o caminho errado

### 2. Interruptor de env que abre superfície pública falha FECHADO

- **Glob**: `config/**`
- **Evidência**: ADR-08 (**reescrita** durante a implementação) + o medido em
  `vendor/laravel/framework/src/Illuminate/Support/Env.php:252-262`
- **Enunciado correto** (o primeiro estava errado): não é que `(bool) env()` seja antipadrão — o
  Laravel já converte `"true"`/`"false"` em valor PHP, e as três chaves irmãs do `config/kit.php`
  que usam cast de bool **estão certas**. A diferença é de **direção**: `off`, `no` e qualquer
  valor irreconhecível dão `true` no cast (falha **aberta**) e `false` no `filter_var` (falha
  **fechada**). Para chave que **abre superfície pública**, o lado certo é o fechado
- **Gates**: durável ✅ · escopável ✅ · não-inferível ✅ (a intuição é que os dois empatam, e
  para todo valor documentado eles empatam — só divergem onde importa) · não-redundante ⚠️ é
  **acréscimo** a `.ai/rules/config.md`, que cobre inteiro via `NumeroDoEnv` e **não** cobre
  booleano
- **Forma preferida**: **atualizar** `.ai/rules/config.md` com um parágrafo, não criar arquivo. E
  o parágrafo precisa dizer as duas coisas — quando usar `filter_var` **e** que as chaves com cast
  de bool não devem ser "consertadas" —, senão a rule produz exatamente a varredura errada que
  esta feature quase fez

### 3. Superfície pública nova derruba a ROTA, não só o botão

- **Glob**: `routes/**`, `app/Http/Controllers/**`
- **Evidência**: ADR-03; o kit tinha **uma** rota pública (`routes/web.php:21-23`) e passa a ter três
- **Gates**: durável ✅ · escopável ✅ · não-inferível ⚠️ — um dev competente **pode** acertar
  sozinho · não-redundante ✅
- **Observação**: o mais fraco dos três, justamente pelo gate 3. Fica proposto, com a ressalva.

### Rejeitado — "arquivo novo do kit entra em `KitUpdate::CAMINHOS_DO_KIT`"

Seria o candidato óbvio depois de QA-01 (Blocker). **Recusado**: já existe enforço automático —
`tests/Kit/KitUpdateTest.php` varre a árvore e reprova o arquivo fora da lista, com mensagem que
diz exatamente o que fazer. A escada do Ponytail aplicada a rules manda preferir a máquina à
prosa, e uma rule aqui seria imposto de contexto em todo arquivo do kit para repetir o que o teste
já grita no momento certo.

**Nada foi gravado.** A skill não grava rule sem aprovação explícita, e a instrução desta rodada é
apenas **propor** aqui.

## Quality Gate (step 8)

Relatório completo em `06-relatorio-qa.md`. Perfil **completo** (domínio sensível), 2 ciclos.

| Ciclo | Veredito | Achados |
|---|---|---|
| 1 | **REPROVADO → implementação** (Blocker) e **→ teste** (Major) | QA-01, QA-02, QA-03, QA-04 |
| 2 | **APROVADO COM DÉBITO** | nenhum achado novo; 2 débitos aceitos |

Os dois achados que valeram o gate inteiro:

- **QA-01 (Blocker)** — `app/Http/Controllers` não estava em `KitUpdate::CAMINHOS_DO_KIT`, e quem
  já instalou o kit receberia a config, a rota e o botão **sem** o controller que a rota aponta.
  Quem pegou foi um teste do próprio kit (`tests/Kit/KitUpdateTest.php:142`) — o gate só leu o
  vermelho. O kit não tinha nenhum controller próprio até agora, então a linha nunca existiu.
- **QA-02 (Major)** — a feature estava coberta **só em single-tenant**, e todo o ramo
  `hasTenancy()` nunca era executado. Escrito o teste primeiro, como manda o destino 3: os quatro
  casos passaram, então **não houve correção de código** — a lacuna era de prova, não de produto.
  O caso que importa é "conta criada por login social sem organização nenhuma", onde um `route()`
  sem guarda responderia 500 no exato caminho de quem acabou de se cadastrar.

Dois riscos investigados e **fechados como não-defeito**, com leitura de `vendor/`:

- a notificação de recusa **chega** à tela de login (o `layout.base` do Filament renderiza o
  componente, e ele puxa da sessão);
- o `'exception' => $e` do `catch` **não** vaza o `client_secret` (o channel não inclui stack
  trace, o segredo viaja dentro de array, e a mensagem do Guzzle traz o corpo da resposta).

## Retrospectiva

- **Funcionou bem**
  - Ler o `vendor/` **antes** de escrever a wiki mudou cinco decisões, não uma: o fake que ignora
    o `state`, as duas chaves de e-mail verificado, o `MustTwoFactor` que já barra, o
    `SessionGuard` que já regenera a sessão e a trilha de acesso que já escuta o evento. Quatro
    dessas cinco **removeram** trabalho do plano.
  - Isolar a leitura de configuração antes de a tela de Settings existir transformou uma
    dependência de merge em três corpos de método.
  - Derivar os casos de teste do requisito e não do plano produziu CT-05, CT-10, CT-21 e CT-23,
    que nenhum "teste dos passos do plano" teria pedido.
- **Faltou no plano**
  - A primeira versão pendurava o botão na `TelaLogin` do `/app` e teria entregue a feature em um
    painel de três. O que pegou isso foi ler os **três** providers, não o requisito.
  - O `phpunit.xml` fixar seis chaves `KIT_*` com `force="true"` só apareceu ao escrever o `04`.
    Verificar o arnês de teste é parte da pesquisa, não do fim.
