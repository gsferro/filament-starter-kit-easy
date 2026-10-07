# Plano de Ação — Rodapé separado: o recado volta ao cartão do login e a assinatura fica

> Requisito: `00-requisito.md`

## Natureza da Wiki

- **Tipo**: ajuste
- **Wiki ancestral**: `wikis/specs/feat/rodape-coerente/rodape-coerente/` — a feature (v0.39.0, commit `bfe9a8d`) que juntou a assinatura automática e o recado do login num único hook `FOOTER`. Esta wiki desfaz **só o acoplamento** (P-03 do `00`); a `rodape-coerente` não é editada (a ADR-02 deste conjunto registra o que dela fica superado em parte)
- **Motivo**: o solicitante revisou a unificação e disse que "não ficou bom, melhor manter separado": o recado volta para o lugar de antes de `bfe9a8d` (dentro do cartão do formulário, abaixo dos botões) e a assinatura fica exatamente como está
- **Toca infra compartilhada?**: sim → `KitServiceProvider::configureTelaDeLogin()` registra o hook de **toda** tela de login dos três painéis e do `/login`. Regressão obrigatória contra `RodapeCoerenteTest`, `VersaoNoRodapeTest`, `LoginSocialGoogleTest`, `LoginSocialProvedoresTest`, `LoginSocialContaIndisponivelTest`, `TelasDeAutenticacaoTest`, `LoginUnificadoTest` e os browser `RodapeNaDobraTest` e `LoginSocialTest` (CT-B01 e CT-B03 da ancestral são o foco)

## Cobertura do Requisito

| RQ | Cláusula | Passo(s) que atende(m) | Observação |
|----|----------|------------------------|------------|
| RQ-01 | os dois rodapés voltam a ser separados (deixam de compor um único footer) | 1, 2, 3, 4 | o 1 desfaz o acoplamento; o 3 e o 4 provam a separação |
| RQ-02 | o rodapé do login volta a ser como estava: recado Markdown dentro do cartão do formulário, abaixo dos botões, só nas telas de login | 1, 2, 3, 4, 5 | o "como estava" é o estado de `bfe9a8d^` (P-01) |
| RQ-03 | a assinatura automática fica como está (mesma composição, lugar e visibilidade) | 3, 4 | nenhum passo a edita (P-03); os passos 3 e 4 provam que ela segue no `FOOTER`, fora do cartão, para visitante e autenticado |
| RQ-04 | feature própria, branch separada da outra feature do pedido, em paralelo | — | ⚠️ fora desta entrega como passo de código: já cumprida pela branch `fix/rodape-separado` e pela worktree `wt-rodape`; não há arquivo a mudar |
| P-01 | "como estava" = estado anterior a `bfe9a8d`, no histórico do git | 1, 3, 4 | — |
| P-02 | a assinatura não muda em nada, nem a visibilidade para o visitante | 3, 4 | guardas existentes: CT-01, CT-04, CT-07 da ancestral |
| P-03 | o que a unificação trouxe e não é "juntar no footer" fica (prefixo `v`, `©`, landmarks, CSS da dobra) | 2 | o passo 2 só mexe em comentário de blade; nenhum arquivo da assinatura nem o CSS entra no diff |
| P-04 | a opção de configuração do recado não muda | 1, 5 | `KIT_LOGIN_RODAPE` e o campo Markdown da aba Login intocados |
| P-05 | o recado volta com a tag `<aside>` da `rodape-coerente`, e não com o `<div>` de antes de `bfe9a8d` | 2, 4 | D1; o CT-B03 mede o `<aside>` dentro do cartão, com a bifurcação do passo 4 |

> Q1 e Q2 estão retiradas no `00` (cobertas por P-01 e P-02; o PR as destaca para confirmação do solicitante). Nenhum passo fica bloqueado.

## Passos

| Passo | O quê | Arquivos | Depende | Critério de pronto |
|---|---|---|---|---|
| 1 | O recado volta para `AUTH_LOGIN_FORM_AFTER`, sem `scopes:`, depois dos botões sociais; o comentário e o docblock deixam de descrever o `FOOTER`; o `use` de `TelaLogin` sai | `app/Providers/KitServiceProvider.php` | — | a tela de login emite os botões e depois o recado dentro de `.fi-auth-layout`; `grep -c 'TelaLogin::class' app/Providers/KitServiceProvider.php` = 0 e `grep -n 'scopes:'` sem ocorrência dentro de `configureTelaDeLogin()`; Pint limpo |
| 2 | Só comentários mudam: o cabeçalho da blade do recado e a linha 46 da blade da assinatura (D5) | `resources/views/filament/auth/rodape-login.blade.php`, `resources/views/filament/assinatura-do-rodape.blade.php` | 1 | o `git diff` das duas blades só tem linhas dentro do `{{-- --}}`; `<aside>` e Markdown intactos; nenhuma diretiva Blade no comentário novo |
| 3 | Testes de backend: CT-10 troca de oráculo (e prova a ordem botões → recado); CT-09, CT-12 e CT-01 passam a afirmar ausência no HTML inteiro, cada um com controle positivo; CT-08 e CT-11 conferidos | `tests/Kit/RodapeCoerenteTest.php` | 1 | `vendor/bin/pest tests/Kit/RodapeCoerenteTest.php --compact` verde; cada CT alterado falha sem o passo 1 (falsificabilidade) |
| 4 | Teste de browser: CT-B01 inverte a ordem visual; CT-B03 ganha a regra `landmark-complementary-is-top-level` e o M59 é medido com o recado no cartão | `tests/Browser/RodapeNaDobraTest.php` | 1 | `composer test:browser` (ou o arquivo, com as views aquecidas) verde nas cinco rotas; CT-B01 medido também contra o estado sem o passo 1; bifurcação do CT-B03 resolvida e registrada |
| 5 | Docs pt/en, CHANGELOG e contagem dos READMEs | `docs/pt/recursos/configuracoes-do-kit.md`, `docs/en/recursos/configuracoes-do-kit.md`, `docs/pt/autenticacao/login-social.md`, `docs/en/autenticacao/login-social.md`, `CHANGELOG.md`, `README.md`, `README.en.md` | 1 | `SiteDeDocumentacaoTest` (números objetivos e CT-50) e `RedeDeDocumentacaoTest` verdes; CHANGELOG com a entrada em `[Unreleased]` |
| 6 | Reconciliação com as wikis anteriores, sem editá-las | `02-decisoes-arquiteturais.md` (ADR-02), `wikis/specs/INDEX.md` por script | 1–5 | ADR-02 lista o que da `rodape-coerente` fica superado; `INDEX.md` regenerado pelo `indice.sh`, nunca à mão; `git diff --stat -- wikis/specs/feat/rodape-coerente` vazio |

## Objetivo

Desfazer o acoplamento que a `rodape-coerente` criou entre dois rodapés que nasceram em features diferentes: a **assinatura automática** (`© {ano} {nome} · v{versão}`, composta por `App\Support\AssinaturaDoRodape` e registrada sem escopo no `FOOTER`) e o **recado Markdown** da aba Login. O recado volta ao lugar de antes de `bfe9a8d` — o hook `AUTH_LOGIN_FORM_AFTER`, dentro do cartão do formulário, abaixo dos botões sociais — e a assinatura não muda.

A entrega é pequena no código (um registro de hook e três comentários) e delicada nos testes: os oráculos de ausência da `rodape-coerente` olham a **cauda** da página (`rodapeDe()`), e com o recado dentro do cartão a cauda deixa de ser o lugar dele — a ausência ali ficaria verde por motivo errado.

## Contexto

`bfe9a8d` moveu o recado de `AUTH_LOGIN_FORM_AFTER` para o `FOOTER` com `scopes:` para obter a ordem "assinatura em cima, recado embaixo" e o escopo só nas telas de login. Funcionou, mas o solicitante achou o resultado ruim ("não ficou bom, melhor manter separado"). O custo colateral da junção também aparece no código: um comentário de 30 linhas sobre ordem e alcance do `FOOTER`, uma regra de CSS (`body.fi-body:has(> .fi-auth-layout)`) que existe porque o recado e a assinatura saíam abaixo da dobra, e oráculos de teste ligados à cauda.

O que fica, por P-03 do `00`: o prefixo `v` do campo de versão, o `©` e o nome na assinatura, o `<footer>` da assinatura e o CSS da dobra (ele serve à assinatura sozinha nas telas de autenticação). O `<aside>` do recado fica por P-05, não por P-03: a posição é o que o pedido desfaz, o landmark é medido e permanece.

## Análise dos Arquivos Existentes

<!-- Raia fato da entrevista: o que o agente descobriu no código, sem perguntar ao usuário. -->

### `app/Providers/KitServiceProvider.php`
- `configureTelaDeLogin()` (`app/Providers/KitServiceProvider.php:configureTelaDeLogin():782`) registra três hooks: os botões sociais em `AUTH_LOGIN_FORM_AFTER` (`app/Providers/KitServiceProvider.php:AUTH_LOGIN_FORM_AFTER:785`), o recado em `FOOTER` com `scopes:` das duas classes (`app/Providers/KitServiceProvider.php:FOOTER:792`) e os botões no registro (`AUTH_REGISTER_FORM_AFTER`, que não muda).
- O estado de antes de `bfe9a8d` (`git show bfe9a8d^:app/Providers/KitServiceProvider.php`): os dois registros no **mesmo** `AUTH_LOGIN_FORM_AFTER`, botões primeiro e recado depois, sem `scopes:`. O comentário daquele método dizia que a ordem de render é a ordem de registro, então os botões vêm antes do rodapé.
- O docblock do método tem um parágrafo que afirma o contrário do que passa a valer: "O rodape USA o hook `FOOTER`, com `scopes:`" (`app/Providers/KitServiceProvider.php:FOOTER:773`). Ele muda junto com o bloco de comentário interno — achado desta análise, que o pedido de despacho não listava.
- `TelaLogin` só é usada no `scopes:` (`app/Providers/KitServiceProvider.php:TelaLogin:9` é o `use`); `TelaLoginUnificada` continua usada pela rota `/login` (`app/Providers/KitServiceProvider.php:TelaLoginUnificada:841`), então só o primeiro `use` sai.

### O hook `AUTH_LOGIN_FORM_AFTER` só sai na tela de login (vendor lido)
- `Login::content()` (`vendor/filament/filament/src/Auth/Pages/Login.php:content():416`) emite `RenderHook::make(PanelsRenderHook::AUTH_LOGIN_FORM_AFTER)` (`vendor/filament/filament/src/Auth/Pages/Login.php:AUTH_LOGIN_FORM_AFTER:423`) depois do formulário. Nenhuma outra página do Filament emite essa chave: o `grep -rn AUTH_LOGIN_FORM_AFTER vendor/filament/filament/src` devolve só essa linha e a definição da constante (`vendor/filament/filament/src/View/PanelsRenderHook.php:AUTH_LOGIN_FORM_AFTER:7`).
- Só `TelaLogin` e `TelaLoginUnificada extends TelaLogin` herdam o `content()`; o passo 1 reconfirma que nenhuma das duas o redeclara (`grep -n 'function content' app/Filament/Pages/Auth/TelaLogin.php app/Filament/Pages/Auth/TelaLoginUnificada.php`). Esta é a justificativa de o hook voltar sem escopo (D2): o escopo só era necessário no `FOOTER`, que o layout autenticado também emite.
- O conteúdo da página entra pelo `{{ $slot }}` dentro de `.fi-auth-layout` no layout do `caresome/filament-auth-designer` (`vendor/caresome/filament-auth-designer/resources/views/components/layouts/auth.blade.php:'fi-auth-layout':28`), e o `FOOTER` sai depois do fechamento dessa div (`vendor/caresome/filament-auth-designer/resources/views/components/layouts/auth.blade.php:FOOTER:63`). É a geometria que o CT-10 novo mede: recado antes do fechamento balanceado, assinatura depois.
- O override do projeto em `resources/views/vendor/filament-auth-designer/` só tem `components/partials/media.blade.php` (a mídia do cartão); não toca o `{{ $slot }}` nem o `FOOTER`.

### `resources/views/filament/auth/rodape-login.blade.php`
- Lê `ConfiguracaoDoLogin::rodapeDoLogin()` (`app/Support/ConfiguracaoDoLogin.php:rodapeDoLogin():143`) e renderiza `<aside class="fi-login-rodape">` com Markdown `html_input => strip` (`resources/views/filament/auth/rodape-login.blade.php:aside:37`). O comentário de cabeçalho explica o `<aside>` com o argumento "irmão direto de `<body>`" — falso depois do passo 1, mas a **decisão** do `<aside>` se sustenta (D1).

### `resources/views/filament/assinatura-do-rodape.blade.php` e `ConfiguraFilamentGlobal`
- A assinatura é `<footer class="kit-versao">` (`resources/views/filament/assinatura-do-rodape.blade.php:footer:70`), registrada sem escopo no `FOOTER` por `configuraVersaoNoRodape()` (`app/Providers/Concerns/ConfiguraFilamentGlobal.php:configuraVersaoNoRodape():129`, hook em `app/Providers/Concerns/ConfiguraFilamentGlobal.php:FOOTER:132`). Não entram no diff (P-03).
- **Comentário que fica desatualizado**: a linha 46 da blade da assinatura diz "O recado do login, logo abaixo desta linha, aceita Markdown" (`resources/views/filament/assinatura-do-rodape.blade.php:recado:46`). Depois desta entrega o recado está acima, dentro do cartão. É comentário, sem efeito de comportamento: o passo 2 o corrige em uma linha (D5).

### `resources/css/filament/kit.css` e `tests/Pest.php` (fora do diff)
- A regra da dobra (`resources/css/filament/kit.css:'fi-auth-layout':224`) serve à assinatura sozinha e **fica**; a cópia publicada `public/css/kit/kit-correcoes.css` acompanha.
- `rodapeDe()` (`tests/Pest.php:rodapeDe():2032`) mede a cauda após o fechamento do `.fi-auth-layout` (`tests/Pest.php:rodapeDoLayoutDeAutenticacao():2040`); `recadoDoRodape()` (`tests/Pest.php:recadoDoRodape():2156`) recorta o recado em qualquer ponto do HTML. Nenhum dos dois muda.

## Decisões de Desenho

<!-- Raia desenho: decisão tomada com o desenvolvedor que NÃO passou nos três portões de ADR.
     Uma linha por decisão; a que passa nos três vira ADR no 02. "Nenhuma" é resposta válida. -->

| # | Decisão | Pergunta | Portão que falta | Quem decidiu, data |
|---|---|---|---|---|
| D1 | A tag do recado continua `<aside>` (P-05 do `00`), sem mudar a blade além do comentário. A posição é o que o pedido desfaz; o landmark é marco de acessibilidade medido e fica (P-03). Dentro do cartão o `<aside>` se presume inofensivo e o CT-B03 o mede (bifurcação no passo 4: se acusar, volta a `<div>` e P-05 é revisada). | desenho: desfazer o `<aside>` junto? | difícil de reverter (é trocar uma tag e um comentário) | construtor, a pedido do orquestrador, 2026-10-07 |
| D2 | O registro volta **sem `scopes:`**; a justificativa (só a tela de login emite o hook, e `TelaLoginUnificada` herda o `content()`) está na análise do hook acima, com o grep e a citação de `Login::content()`. | desenho: escopo no hook de volta? | difícil de reverter | construtor, 2026-10-07 |
| D3 | Os oráculos de **ausência** do recado (CT-01, CT-09, CT-12) olham o **HTML inteiro**, não a cauda de `rodapeDe()`. Reforço do oráculo: só o CT-09 ficava verde por motivo errado (a ausência na cauda é verdade sempre, com o recado no cartão); CT-01 e CT-12 pegavam o vazamento pela cauda e passam a pegá-lo em qualquer lugar. A ausência é dupla (texto **e** classe `fi-login-rodape`) e cada caso leva controle positivo do detector. | desenho: qual é o oráculo certo? | surpreendente (o oráculo do CT-09 continuaria verde) | construtor, 2026-10-07 |
| D4 | A **ordem dentro do hook** (botões antes do recado) é invariante do "como estava" e depende só da ordem de registro no mesmo método; fica documentada no comentário novo do provider e é provada pelo limite inferior do CT-10 (recado depois do bloco `fi-login-social` num dataset com provedor habilitado), então **não há CT separado de ordem**. Nota: o Breezy registra o passkey em `AUTH_LOGIN_FORM_AFTER` no boot do painel (`vendor/jeffgreco13/filament-breezy/src/BreezyCore.php:AUTH_LOGIN_FORM_AFTER:100`), dentro de `if ($this->passkeys)` (`vendor/jeffgreco13/filament-breezy/src/BreezyCore.php:passkeys:94`), hoje inativo no kit; se ativado, sairia abaixo do recado. | desenho: quem guarda a ordem? | trade-off (custo do arranjo de provedor no teste) | construtor, 2026-10-07 |
| D5 | O comentário da blade da assinatura (linha 46, "logo abaixo desta linha") é corrigido em uma linha, dentro do passo 2; o código dessa blade e o `kit.css` não mudam. P-03 é sobre comportamento, e um comentário falso custa mais ao próximo leitor do que uma linha de diff. Decidido pelo orquestrador em 2026-10-07. | desenho: tocar o comentário? | — | fechada |
| D6 | (Q3) O `[CT-04]` da ancestral também passa a medir a ausência do recado pelo helper de HTML inteiro, na linha da recuperação de senha: o bloco que usa `rodapeDe()` troca por `semRecadoEmLugarNenhum()`. O resto do CT-04 não muda. Entra no passo 3. | desenho: o CT-04 fica de fora? | — | sessão, 2026-10-07 |
| D7 | (Q4) O CT-B01 prova "dentro do cartão" com `closest('.fi-auth-card, .fi-auth-form-container')` não nulo; o seletor é medido no step 10 contra o layout do auth-designer (se nenhum dos dois existir, o contêiner do `<form id="form">`). Entra no passo 4. | desenho: como provar a contenção? | — | sessão, 2026-10-07 |
| D8 | (Q5) `ligarLoginComGoogleDoKit()` sai de `tests/Kit/LoginSocialGoogleTest.php` para `tests/Pest.php`, porque o CT-10 passa a ser o segundo consumidor (`.ai/rules/testes.md`). Entra no passo 3; `tests/Kit/HelpersDeTesteTest.php` (que confere helper usado em outro arquivo) entra na regressão. | desenho: mover o helper ou gravar a config no `Dado`? | — | sessão, 2026-10-07 |

## Autorização

- **Policies / Gates / Middleware / Guards**: nenhum. A superfície é de leitura pública (telas de login); o recado continua sem credencial e escapado por Markdown com `html_input => strip`.

## Rotas

Nenhuma nova. As quatro telas de login (`/admin/login`, `/app/login`, `/infra/login`, `/login`) e a recuperação de senha já existem.

## Superfície de UI

| Tela / Componente | Tipo | Rota | Interação do usuário | Depende de JS? |
|---|---|---|---|---|
| Login do painel admin | Filament (`TelaLogin`) | `/admin/login` | vê o recado no cartão, abaixo dos botões; a assinatura abaixo do cartão | Não |
| Login do painel com tenancy | Filament (`TelaLogin`) | `/app/login` | idem | Não |
| Login do painel infra | Filament (`TelaLogin`) | `/infra/login` | idem | Não |
| Login unificado | Filament (`TelaLoginUnificada extends TelaLogin`) | `/login` | idem | Não |
| Recuperação de senha | Filament (controle negativo) | `/admin/password-reset/request` | vê a assinatura e **nenhum** recado | Não |

**Gate de CT-B**: os CT-B da ancestral estão em `04-casos-de-teste.md` dela, seção `## Gate de CT-B` (l.980); o que muda é **geometria e acessibilidade**, que só o navegador prova — a ordem visual (recado acima da assinatura), a dobra (assinatura e recado dentro do viewport) e o axe com o `<aside>` dentro do cartão. É costura `browser` e fica no passo 4. Presença, ausência, ordem no documento e escopo são costura de backend (passo 3).

**Gate de tela de escrita**: não há tela `create`/`edit` nova. A tela que grava o recado (`/admin/configuracoes-da-aplicacao`, aba Login) não muda (P-04) e já tem o seu caso de gravação na ancestral.

## Variáveis de Ambiente

Nenhuma nova. `KIT_LOGIN_RODAPE` (`config/kit.php`, chave `login.rodape`) continua a mesma (P-04).

## Eventos / Listeners / Observers

Nenhum.

## Jobs / Queues

Nenhum.

## Modelo de Execução

| Pergunta | Resposta |
|---|---|
| Quantos requests a tela custa? | um na carga; o recado vive dentro do componente Livewire do login e é redesenhado a cada ida e volta (login com erro), sem query nova |
| O que é adiado, e por qual gatilho? | nada |
| O que é memoizado **por request**? | nada novo; `rodapeDoLogin()` é lido na renderização da blade, como hoje |
| O que é cacheado **entre** requests? | nada |
| Custo do caminho principal | zero query nova: o hook troca de chave, não de trabalho. A blade do recado já rodava uma vez por render da tela de login |

## Impacto em Features Existentes

- **`rodape-coerente`** (ancestral): CT-01, CT-09, CT-10, CT-12 e CT-B01 mudam de oráculo ou de mensagem; CT-08 perde só uma variável sem uso; CT-11, CT-B02 e CT-B03 seguem; CT-24 e CT-25 (varredor de cópias do extrator) não são tocados.
- **Login social** (`tests/Kit/LoginSocialGoogleTest.php`, caso do rodapé ~l.601; `tests/Browser/LoginSocialTest.php` ~l.114): voltam a ter o recado no mesmo hook dos botões, como antes de `bfe9a8d`; o comentário do browser test ("sai pelo MESMO render hook dos botões") volta a ser verdadeiro. Regressão obrigatória.
- **`VersaoNoRodapeTest`**: lê `rodapeDe()` só para a assinatura; não muda.
- **CSS da dobra**: o cartão volta a carregar o recado; o CT-B01 mede se a assinatura continua dentro do viewport (risco R1).
- **Docs e README**: pt/en da página de configurações e do login social; contagem de features (+1).

## Rollback

- **Migration down**: não há.
- **Reversão**: `git revert` dos commits da branch. Reverter devolve o recado ao `FOOTER` escopado, o estado de `bfe9a8d`.

## Dependências

Nenhuma nova (Composer ou NPM).

## Riscos

- **R1 — a assinatura cair abaixo da dobra com o cartão mais alto.** O recado de volta ao cartão aumenta a altura dele; a regra de CSS faz o `.fi-auth-layout` ceder altura mas não encolhe o conteúdo. Mitigação: o CT-B01 mede `assinaturaBottom <= vh` nas quatro telas de login; se vermelho, o conserto é CSS e vira Desvio do Plano, nunca relaxar o oráculo.
- **R2 — o axe acusar o `<aside>` dentro do cartão.** As três regras do CT-B03 só foram medidas com o recado fora do cartão, e um `<aside>` aninhado em contêiner de formulário pode acusar `landmark-complementary-is-top-level`, que o CT-B03 não roda. Mitigação: o passo 4 acrescenta essa regra ao CT-B03; se ele ficar vermelho, a tag volta a `<div>` e P-05 é revisada (D1).
- **R3 — conflito de contagem nos READMEs.** A outra feature do pedido (logo escura da organização) também soma 1 à contagem de features; quem fizer o segundo merge reconcilia `README.md` e `README.en.md` (`find wikis/specs -name 00-requisito.md | wc -l`).

## Channel de Log da Feature

Nenhum, e é decisão: o passo 1 troca a chave de um render hook e o resto é comentário e teste. Não há ramo de execução novo nem decisão silenciosa a rastrear em arquivo. Verificado: `grep -n -i "rodape\|footer" config/logging.php` devolve vazio (não existe channel de rodapé) e `KitServiceProvider::configureTelaDeLogin()` não chama `Log::` hoje.

## Estrutura de Implementação

### 1. O recado volta para `AUTH_LOGIN_FORM_AFTER`

> Skills: `filament-development`, `laravel-best-practices`, `ponytail`

- **Path**: `app/Providers/KitServiceProvider.php:configureTelaDeLogin():782`
- O segundo `registerRenderHook` (hoje em `app/Providers/KitServiceProvider.php:FOOTER:792`) passa a usar `PanelsRenderHook::AUTH_LOGIN_FORM_AFTER` e **perde** o argumento `scopes:`. Ele fica **imediatamente depois** do registro dos botões (`app/Providers/KitServiceProvider.php:AUTH_LOGIN_FORM_AFTER:785`) — a ordem de render é a ordem de registro (D4).
- O bloco de comentário sobre ORDEM e ALCANCE do `FOOTER` (linhas 791–824, entre os dois registros) é **substituído** por um curto que diz: (a) o recado voltou ao hook do formulário a pedido do solicitante, wiki `rodape-separado`, desfazendo o acoplamento da `rodape-coerente`; (b) fica depois dos botões, e a ordem é a de registro; (c) o hook dispensa `scopes:` porque só a tela de login o emite (`Login::content()`), e `/login` herda a mesma view por `TelaLoginUnificada extends TelaLogin`; (d) a assinatura segue no `FOOTER`, fora do cartão, registrada em `ConfiguraFilamentGlobal`. Sem diretiva Blade e no estilo do método (PT sem acento).
- O parágrafo do docblock "O rodape USA o hook `FOOTER`, com `scopes:`…" (`app/Providers/KitServiceProvider.php:FOOTER:773`) é reescrito no mesmo sentido; o resto do docblock (por que `FilamentView::registerRenderHook()` e não `$panel->renderHook()`, duas registrações, view única dos botões) fica.
- O mesmo docblock cita a citação de linhas 458-466 do `Login.php` do Filament para o hook do formulário, linha errada hoje: reancorar para `vendor/filament/filament/src/Auth/Pages/Login.php:content():416` (o hook está na 423).
- O `use App\Filament\Pages\Auth\TelaLogin;` (`app/Providers/KitServiceProvider.php:TelaLogin:9`) sai por ficar sem uso; `TelaLoginUnificada` fica (rota `/login`).
- **Atende**: RQ-01, RQ-02, P-01, P-04
- **Logs**: nenhum (ver `## Channel de Log da Feature`).
- **Critério de pronto**: `grep -c 'TelaLogin::class' app/Providers/KitServiceProvider.php` = 0 e `grep -n 'scopes:'` sem ocorrência dentro de `configureTelaDeLogin()`; `php artisan route:list --path=login` ainda lista as telas; `vendor/bin/pint --dirty --format agent` limpo; `vendor/bin/filacheck --fix` não se aplica (nada em `app/Filament`).

### 2. O comentário da blade do recado

> Skills: `ponytail`

- **Path**: `resources/views/filament/auth/rodape-login.blade.php`
- Só o comentário `{{-- --}}` de cabeçalho. A seção "`<aside>`, e nao `<div>` nem `<footer>`" fundamenta a tag com "este elemento e irmao direto de `<body>`" e com o conflito de `contentinfo` com a assinatura. Passa a dizer: o recado está **dentro do cartão do formulário**, depois dos botões; a tag `<aside>` fica (P-05) porque (i) continua inofensiva ali, (ii) o `[CT-B03]` da `rodape-coerente` a mede e (iii) a medição das três formas (ADR-09 da ancestral) vale para a assinatura, que segue no `<body>`. O trecho "SAIDA POR MARKDOWN" e o "ponytail" do estilo inline ficam.
- O código (o bloco php, o `@if`, o `<aside>` com o `style`, o `Str::markdown`) **não muda**.
- **Regra de `views.md`**: nenhuma diretiva Blade em comentário, nem entre crases — escrever "o bloco php abaixo", não a diretiva.
- **Atende**: RQ-01, RQ-02, P-03
- **Logs**: nenhum.
- **Também** (D5): a linha 46 de `resources/views/filament/assinatura-do-rodape.blade.php` passa a dizer que o recado do login vive dentro do cartão do formulário, acima desta linha. Só o comentário.
- **Critério de pronto**: `git diff -U0 -- resources/views/filament/auth/rodape-login.blade.php resources/views/filament/assinatura-do-rodape.blade.php` só mostra linhas dentro de comentário; `git diff --name-only` **não** lista `AssinaturaDoRodape.php`, `ConfiguraFilamentGlobal.php` nem `kit.css`.

### 3. Testes de backend

> Skills: `pest-testing`, `testing-best-practices`

- **Path**: `tests/Kit/RodapeCoerenteTest.php`. Os IDs são os da ancestral, **renomeados no lugar** quando o oráculo muda; o `04` desta wiki é derivado depois pela `feature-test-design` e é ele que fecha a lista. O que o plano prevê, caso a caso:

| CT | Hoje | Passa a afirmar | Motivo |
|---|---|---|---|
| `tests/Kit/RodapeCoerenteTest.php:'[CT-01]':89` | na linha `/admin/password-reset/request` (`recadoNaoDeveAparecer`), ausência do recado em `rodapeDe()` | ausência do texto **e** da classe `fi-login-rodape` no HTML inteiro, pelo helper local `semRecadoEmLugarNenhum(string $html)` (usado também por CT-09 e CT-12; sem `preg_*` com o marcador na mesma linha, por causa do CT-24). **Controle positivo no mesmo caso**: um `GET /admin/login` com o mesmo recado confere a presença por `recadoDoRodape()` antes da ausência | a cauda nunca contém o recado no cartão (D3) |
| `tests/Kit/RodapeCoerenteTest.php:'[CT-08]':356` | recado por `recadoDoRodape()` e assinatura por `assinaturaDoRodape()` | igual; só sai a variável `$rodape` que o caso nunca usa | presença por recorte vale em qualquer posição |
| `tests/Kit/RodapeCoerenteTest.php:'[CT-09]':377` | `fi-login-rodape` ausente em `rodapeDe()`, com controle de `rodapeDe()` não vazio | `semRecadoEmLugarNenhum($html)` no HTML inteiro, com a assinatura presente. **Controle positivo no mesmo caso**: primeiro grava o recado "Fale com o suporte" e confere a presença por `recadoDoRodape()`, depois aplica o valor do dataset (`ausente`/`vazio`/`só espaços`) e confere a ausência | o único oráculo que ficava verde por motivo errado: a ausência na cauda é verdade sempre (D3) |
| `tests/Kit/RodapeCoerenteTest.php:'[CT-10]':420` | `kit-versao` **antes** de `fi-login-rodape` em `strpos` | **recado dentro do cartão, depois do formulário e dos botões; assinatura fora e depois**. Seja `$fim = strlen($html) - strlen(rodapeDoLayoutDeAutenticacao($html))`, o fechamento balanceado de `.fi-auth-layout`. Exige `rodapeDoLayoutDeAutenticacao($html) !== ''` (controle positivo: sem âncora o `$fim` seria o fim do documento e o recado estaria "dentro" de graça) e: posição do recado `>` posição de `fi-auth-layout` (limite inferior), `>` fim do `</form>` do login (`strpos($html, '</form>', $posicaoDoLayout)`), `<` `$fim`; posição de `kit-versao` `>=` `$fim`. Num dataset com provedor social habilitado, o recado também `>` o fim do bloco `fi-login-social`: isso **prova a ordem botões → recado (D4)**, e por isso o CT de ordem previsto antes para o `04` fica dispensado. O nome muda para algo como "o recado sai dentro do cartão, depois dos botões, e a assinatura fora dele, depois" | é o oráculo da separação (RQ-01) e da ordem (RQ-02) |
| `tests/Kit/RodapeCoerenteTest.php:'[CT-11]':485` | recado por recorte e assinatura nas quatro telas | igual. Os mutantes da ancestral sobre o escopo do hook (M16, M17, M43) **deixam de existir** com o hook sem escopo; o dataset mantém `/login` e `/infra/login` porque a regressão que elas pegam agora é "o recado sumiu de uma das telas" | continua matando o recado ausente |
| `tests/Kit/RodapeCoerenteTest.php:'[CT-12]':510` | ausência na tela autenticada por `rodapeDe()` | `semRecadoEmLugarNenhum($html)` nas três telas autenticadas; a linha do visitante (`/admin/login`) segue como controle positivo, já no dataset | idem CT-09 (D3) |
| `tests/Kit/RodapeCoerenteTest.php:'[CT-24]':899` e CT-25 | varredor de cópias do extrator | **não mudam**; entram na regressão. O texto novo dos testes **não** pode reintroduzir `preg_*` com `kit-versao` ou `fi-login-rodape` na mesma linha | o varredor acusaria a cópia |

- Fora de `RodapeCoerenteTest`, nenhum caso muda: `LoginSocialGoogleTest` (`assertSee('fi-login-rodape')` no HTML inteiro) já vale para o recado no cartão. A guarda da ordem botões → recado (D4) é o limite inferior do CT-10, não um CT novo.
- **D6**: o bloco de ausência do recado na linha `/admin/password-reset/request` do `'[CT-04]'` troca `rodapeDe()` por `semRecadoEmLugarNenhum()`.
- **D8**: `ligarLoginComGoogleDoKit()` é movido de `tests/Kit/LoginSocialGoogleTest.php` para `tests/Pest.php` (o CT-10 com provedor habilitado é o segundo consumidor); `tests/Kit/HelpersDeTesteTest.php` entra na regressão.
- `tests/Pest.php` e os extratores não mudam, **exceto** pelo helper de D8.
- **Atende**: RQ-01, RQ-02, RQ-03, P-01, P-02
- **Logs**: nenhum (teste).
- **Critério de pronto**: `vendor/bin/pest tests/Kit/RodapeCoerenteTest.php tests/Kit/LoginSocialGoogleTest.php tests/Kit/HelpersDeTesteTest.php --compact` verde; para cada CT alterado, a falsificabilidade: com o passo 1 desfeito (voltar o provider ao estado de `main`) o caso fica **vermelho**; se não ficar, ele não mede o defeito. Asserção de ausência com `assertStringNotContainsString`, nunca `not->toContain($x, $mensagem)` (`.ai/rules/testes.md`).

### 4. Teste de browser

> Skills: `pest-testing`

- **Path**: `tests/Browser/RodapeNaDobraTest.php`
- **CT-B01** (`tests/Browser/RodapeNaDobraTest.php:'[CT-B01]':37`): a asserção de ordem visual **inverte** — `recadoTop` menor que `assinaturaTop` (o recado está acima, no cartão; a assinatura abaixo dele) — com **uma** mensagem só ("o recado deveria aparecer ACIMA da assinatura, dentro do cartão"). A mensagem de `recadoBottom` (`tests/Browser/RodapeNaDobraTest.php:DENTRO:81`), que ainda diz "ele era visível DENTRO do cartão antes desta feature", e a do `temRecado` (linha 66, "como o escopo do hook promete") são ajustadas para o estado novo. A medição de `assinaturaBottom <= vh` fica: é ela que detecta o R1.
- **CT-B03** (`tests/Browser/RodapeNaDobraTest.php:'[CT-B03]':152`), duas mudanças verificáveis:
  1. **A constante `regras`** do script do axe (`const regras = [...]`) ganha `landmark-complementary-is-top-level`, a regra que um `<aside>` aninhado pode acusar (R2). **Bifurcação**: se o CT-B03 ficar vermelho com o `<aside>` dentro do cartão, a tag volta a `<div>` na blade e **P-05 é revisada** no `00` (Adendo), com a decisão registrada como Desvio do Plano; se ficar verde, P-05 e D1 se confirmam.
  2. **O mutante M59** (`<aside>` → `<div>`) é medido **com o recado no cartão**: se o CT-B03 continuar verde com a `<div>`, o oráculo não protege mais o landmark e passa a afirmar "o recado está contido num landmark" (`closest('aside,[role],main,form')`), no lugar da ausência na lista de acusados.
- **D7**: o CT-B01 ganha a asserção de contenção, `recado.closest('.fi-auth-card, .fi-auth-form-container')` não nulo, medida no mesmo `script()`; o seletor é conferido no step 10 contra o layout do auth-designer (`.fi-auth-card` em `vendor/caresome/filament-auth-designer/resources/views/components/layouts/auth.blade.php:'fi-auth-card':51`, `.fi-auth-form-container` na linha 56) e, se nenhum existir, vale o contêiner do `<form id="form">`.
- **CT-B02** (estilo da faixa da assinatura) não é tocado.
- Rodar via `composer test:browser` (embute `npm run build` e `view:cache`, pré-requisitos duros) ou, para um arquivo, aquecendo as views pelo kernel como pede `.ai/rules/testes-browser.md`; nunca `--parallel`.
- **Atende**: RQ-01, RQ-02, RQ-03
- **Logs**: nenhum.
- **Critério de pronto**: as cinco linhas do dataset verdes, com a bifurcação do CT-B03 resolvida e registrada. Falsificabilidade: com o recado no `FOOTER` (estado atual de `main`), a asserção invertida de ordem fica vermelha nas quatro telas de login.

### 5. Docs, CHANGELOG e READMEs

> Skills: `ponytail`

- **`docs/pt/recursos/configuracoes-do-kit.md`** (`### O recado da tela de login`, linha 60): "uma **segunda linha do rodapé, abaixo da assinatura**" passa a dizer que o recado fica **dentro do cartão do formulário, abaixo dos botões de login**; o resto (Markdown, HTML descartado, telas de registro e recuperação sem recado) fica. **Espelho en** (`### The login screen notice`, linha 61).
- **`docs/pt/autenticacao/login-social.md`** (`## O rodapé da tela de login`, linha 293): "na base da tela de login dos três painéis" passa a dizer onde fica — dentro do cartão, abaixo dos botões sociais —, sem mexer no bloco `KIT_LOGIN_RODAPE`. **Espelho en** (`## The login screen footer`, linha 299).
- **`CHANGELOG.md`**: `## [Unreleased]` já existe vazio no topo (linha 6); entra `### Alterado` com a entrada do ajuste — o recado volta ao cartão do login, abaixo dos botões, e a assinatura não muda — e uma frase de que isso desfaz **só** a junção no `FOOTER` da v0.39.0. **Sem tag**: não pedida (fora de escopo do `00`). A seção histórica da v0.39.0 não é editada.
- **`README.md` e `README.en.md`**: a contagem "Features especificadas (`wikis/specs/`)" sobe de **76** para **77** (`find wikis/specs -name 00-requisito.md | wc -l` na worktree já conta esta wiki). O `SiteDeDocumentacaoTest` (caso "mantem os numeros objetivos dos readmes sincronizados com a arvore") exige a igualdade nos dois idiomas. O badge "Casos de teste" (1.958) só muda se o `04` acrescentar `it()` (CT-50 do `SiteDeDocumentacaoTest`).
- **Citações**: conferir `grep -rn 'KitServiceProvider.php' docs`; se alguma doc citar linha do provider, reancorar pelo símbolo (o comentário do método encolhe e desloca tudo abaixo).
- **Atende**: RQ-02, P-04
- **Logs**: nenhum.
- **Critério de pronto**: `vendor/bin/pest tests/Kit/SiteDeDocumentacaoTest.php tests/Kit/RedeDeDocumentacaoTest.php tests/Kit/CitacoesDeCodigoTest.php --compact` verde; as docs pt e en descrevem a mesma posição.

### 6. Reconciliação com as wikis anteriores

> Skills: `requirement-to-rule` (só no step 12)

- **Não editar** `wikis/specs/feat/rodape-coerente/**`. O registro de supersessão está na **ADR-02** do `02-decisoes-arquiteturais.md` desta wiki, só com o que muda: ADR-01 (parcial), ADR-05 (contexto), ADR-07 e ADR-09 (vigentes, com nota), RQ-06 do `00` da ancestral e o passo 3 do `01` dela (superados). A ADR-02 da ancestral segue vigente.
- **`wikis/specs/INDEX.md`** é gerado por `bash .claude/skills/feature-tickets/scripts/indice.sh` — rodar no step 10, nunca editar à mão.
- **Atende**: RQ-01
- **Logs**: nenhum.
- **Critério de pronto**: `git diff --stat -- wikis/specs/feat/rodape-coerente` vazio; `indice.sh` regenerado sem editar o `INDEX.md` à mão.

## Filosofia de Implementação

> **Ponytail ativo em modo `full`** durante toda a implementação.
> Cada passo deve aplicar a escada de simplicidade:
> 1. Reutilizar código existente antes de criar novo
> 2. Usar stdlib do PHP/Laravel antes de código custom
> 3. Usar features nativas antes de dependências
> 4. Uma linha quando possível
> 5. Mínimo código que funciona
>
> Atalhos deliberados devem ser marcados com `ponytail:` comment.
> Após implementação, rodar `/ponytail:ponytail-review` no diff.
>
> **Caveman ativo em modo `ultra`** (padrão) na comunicação agent ↔ usuário.
> Arquivos wiki (00-06) são boundary do Caveman — escrever em prosa normal.
> Código, commits e PRs também são boundary do Caveman.
>
> **Baseline antes do primeiro commit**: a `main` (`7221dd3`, v0.45.1) é a base. Rodar a suíte completa em `main` e listar por nome as falhas pré-existentes; a `## Verificação Final` compara contra a baseline, não contra zero. Teto de pulados em `main`: 914 (v0.45.0), a reconferir.

## Mapeamentos

Não se aplica.

## Testes

> Ver `04-casos-de-teste.md` (derivado depois pela `feature-test-design`) para a especificação completa dos cenários de backend. Os CT-B01 e CT-B03 da ancestral estão em `04-casos-de-teste.md` dela, seção `## Gate de CT-B` (l.980); o `05` desta wiki existe só se o `04` novo tiver costura `browser`.

## Logs

Sem log novo. O único caminho de execução alterado é a chave de um render hook em `KitServiceProvider::configureTelaDeLogin()`; não há ramo, falha tratada, `catch` nem decisão silenciosa a rastrear, e a blade do recado e a assinatura não logam hoje. Criar channel para isto seria ruído (`padrao-de-log.md`: o log existe para reproduzir ou diagnosticar fluxo, e aqui não há fluxo novo). A revisão do diff (step 9) confere que nenhum `Log::` entrou.

| Ponto | Log | Motivo |
|---|---|---|
| `KitServiceProvider::configureTelaDeLogin()` | nenhum | registro de hook no boot, sem ramo |
| `rodape-login.blade.php` | nenhum | blade de apresentação; `rodapeDoLogin()` devolve `null` para vazio e a blade não renderiza nada |

## Rastreabilidade

RQ → passo → CT previsto. Os CT ainda são os da ancestral (IDs preservados); o `04` os renumera ou acrescenta.

| RQ / P | Passo(s) | CT previsto |
|---|---|---|
| RQ-01 (separação) | 1, 2, 3, 4 | CT-10 (recado no cartão, assinatura fora e depois), CT-B01 (ordem visual) |
| RQ-02 (recado como estava) | 1, 2, 3, 4, 5 | CT-08 e CT-11 (presença nas quatro telas), CT-12, CT-09 e CT-01 (ausência fora da tela de login, HTML inteiro), CT-B01 (cabe na dobra), CT-B03 (landmark), CT-10 (ordem botões → recado, D4) |
| RQ-03 (assinatura como está) | 3, 4 | CT-01, CT-04, CT-07 e CT-02 (regressão, sem mudança), CT-10 (assinatura fora do cartão), CT-B02 |
| RQ-04 (processo) | — | n.a.: cumprida pela branch e pela worktree |
| P-01 | 1, 3, 4 | CT-10, CT-B01 |
| P-02 | 3, 4 | CT-04, CT-18 (regressão), CT-10 |
| P-03 | 2 | a ausência de diff nos arquivos da assinatura e do CSS (`git diff --name-only` no passo 2) e CT-B02/CT-B03 |
| P-04 | 1, 5 | CT-08 (o valor lido de `kit.login.rodape` continua o mesmo) |
| P-05 | 2, 4 | CT-B03 (`landmark-complementary-is-top-level` e M59 com o recado no cartão) |

## Verificação Final
- [ ] `/ponytail:ponytail-review` no diff (validar contra over-engineering)
- [ ] `vendor/bin/pint --dirty --format agent`
- [ ] `vendor/bin/pest tests/Kit/RodapeCoerenteTest.php --compact` (CTs de backend)
- [ ] `vendor/bin/pest tests/Browser/RodapeNaDobraTest.php` (CT-B01, CT-B03; via `composer test:browser`, nunca `--parallel`)
- [ ] Regressão: `VersaoNoRodapeTest`, `LoginSocialGoogleTest`, `LoginSocialProvedoresTest`, `LoginSocialContaIndisponivelTest`, `TelasDeAutenticacaoTest`, `LoginUnificadoTest`, `SiteDeDocumentacaoTest`, `RedeDeDocumentacaoTest`, `CitacoesDeCodigoTest` e `tests/Browser/LoginSocialTest.php`
- [ ] `vendor/bin/pest --parallel --tia` (Pest 5), comparado à baseline de `main`; suíte completa (`php artisan test --compact`) pedida ao solicitante
- [ ] `pest --mutate`: não se aplica (sem classe de regra; a lógica é um registro de hook). Mutantes manuais sobre o provider: recado de volta ao `FOOTER` (mata CT-10 e CT-B01); recado registrado antes dos botões (mata o limite inferior do CT-10); M59 no CT-B03 (passo 4)
- [ ] **Custo medido**: não se aplica (sem query)
- [ ] **`/code-review high main...HEAD` + passe de eixos (step 9)**
- [ ] Roteiro "Desenhado × Implementado" do `05` preenchido (se houver `05`)
- [ ] `citacoes.sh` e `rastreabilidade.sh` silenciosos sobre esta wiki
- [ ] Docs pt/en, CHANGELOG e README reconciliados com o comportamento final

## Commits
- `:recycle: refactor(rodape): o recado do login volta ao cartão do formulário e a assinatura fica separada`
- `:white_check_mark: test(rodape): oráculos de ausência pelo HTML inteiro e ordem recado no cartão, assinatura fora`
- `:memo: docs(rodape): recado dentro do cartão — pt/en, CHANGELOG e contagem dos READMEs`
- `:memo: wiki(rodape): wiki da feature rodape-separado`
