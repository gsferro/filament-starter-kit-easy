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
- [x] Comentário de bloco: default false, por que não `(bool) env()`, e que o login autentica sem criar conta

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

- [x] `tests/Kit/LoginSocialGoogleTest.php` — CT-01 a CT-23
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
| "`(bool) env()` serve para o interruptor" | `.ai/rules/config.md`: `(bool) "false"` é `true` — o jeito errado **liga** a feature quando a pessoa escreveu que não queria | `filter_var(..., FILTER_VALIDATE_BOOLEAN)`; ADR-08 e CT-18 |
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

Preenchido durante a implementação.

## Notas de Implementação

Preenchido durante a implementação.

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

### 2. `filter_var(..., FILTER_VALIDATE_BOOLEAN)` para interruptor booleano de env

- **Glob**: `config/**`
- **Evidência**: ADR-08 + `.ai/rules/config.md` (que já cobre inteiro, via `NumeroDoEnv`, e **não**
  cobre booleano)
- **Gates**: durável ✅ · escopável ✅ · não-inferível ✅ (`(bool) "false"` é `true`, e o `(bool)`
  acerta o default por acidente, o que esconde o defeito) · não-redundante ⚠️ — é **acréscimo** à
  rule existente, não rule nova
- **Forma preferida**: **atualizar** `.ai/rules/config.md` com um parágrafo de booleano, não criar
  arquivo. A skill diz que atualizar rule existente é sempre preferível

### 3. Superfície pública nova derruba a ROTA, não só o botão

- **Glob**: `routes/**`, `app/Http/Controllers/**`
- **Evidência**: ADR-03; o kit tinha **uma** rota pública (`routes/web.php:21-23`) e passa a ter três
- **Gates**: durável ✅ · escopável ✅ · não-inferível ⚠️ — um dev competente **pode** acertar
  sozinho · não-redundante ✅
- **Observação**: o mais fraco dos três, justamente pelo gate 3. Fica proposto, com a ressalva.

**Nada foi gravado.** A skill não grava rule sem aprovação explícita, e a instrução desta rodada é
apenas **propor** aqui.

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
