# Decisões Arquiteturais — Login social com Google

> Regra dura desta wiki (`.ai/rules/specs.md`): toda afirmação sobre comportamento de pacote
> abaixo vem acompanhada de `arquivo:linha` do `vendor/`, lido antes de a frase ser escrita.

---

## ADR-01: A doc oficial do Socialite é a fonte; os três artigos são pista

**Status**: Aceita
**Data**: 2026-08-24

### Contexto

RQ-01 manda "analisar cuidadosamente a documentação do laravel socialite" e RQ-02 cita três
artigos de terceiros (laraveldaily, medium, dev.to). Artigo de blog envelhece: dois dos três
descrevem Filament 3, e o kit está em Filament 5.7.6 com Laravel 13.25 e Socialite 5.30.

### Decisão

A doc oficial da 13.x é a fonte normativa. Os artigos entram como **pista de forma** — foi de
`dev.to/tadeubdev` que veio a forma "ícone e abaixo do form" (RQ-03), que o requisito nomeia
explicitamente. Todo detalhe de API foi conferido no `vendor/` recém-instalado:

- `Socialite::driver('google')->redirect()` e `->user()` — a rota de duas pontas da doc.
- `redirect` relativo resolve para URL absoluta — afirmação **da doc**: *"If the `redirect` option
  contains a relative path, it will automatically be resolved to a fully qualified URL."* É o que
  torna o valor literal do requisito (`/auth/google/callback`) utilizável sem uma env a mais.
- `state` de CSRF **ligado por default**: `protected $stateless = false`
  (`vendor/laravel/socialite/src/Two/AbstractProvider.php:83`); `redirect()` grava
  `session()->put('state', …)` (`:166`); `user()` chama `hasInvalidState()` e lança
  `InvalidStateException` (`:236-237`); a comparação é `hash_equals` sobre o `state` puxado da
  sessão (`:288-290`). **Nada no kit chama `stateless()`** — verificado por `grep`, e é caso de
  teste (CT-17), não confiança.
- O usuário falso de teste é `Laravel\Socialite\Two\User::fake()`
  (`vendor/laravel/socialite/src/Two/User.php:43-62`), que faz `setRaw($attributes)` — por isso um
  `email_verified => false` passado ao `fake()` chega ao `getRaw()`, e a barreira do ADR-08 é
  testável sem rede.

### Alternativas Consideradas

1. **Seguir o artigo do laraveldaily ao pé da letra** — ele usa `Filament\Pages\Auth\Login` de
   Filament 3 e coloca o botão via `getFormActions()`. Em Filament 5 o ponto de extensão mudou
   para `content()`/render hook, e a página do kit é a do Auth Designer, não a do Filament.
2. **Implementar OAuth à mão com Guzzle** — descartado: o requisito nomeia o pacote, e o pacote
   traz o `state` de CSRF, a verificação de `iss`/`aud` do ID token
   (`vendor/laravel/socialite/src/Two/GoogleProvider.php:130-137`) e a API de teste. Reescrever
   isso é o oposto de lazy.

### Consequências

- **Positivas**: nenhuma afirmação da wiki depende de artigo de terceiro; o CSRF de OAuth é do
  pacote e verificado.
- **Negativas**: o requisito pedia para "analisar" três artigos e eles não moldaram o código além
  da posição do botão. Está declarado, não escondido.

### Referências

- `vendor/laravel/socialite/src/Two/AbstractProvider.php:83,166,236-237,288-290`
- `vendor/laravel/socialite/src/Two/GoogleProvider.php:91,130-137`
- `vendor/laravel/socialite/src/Two/User.php:43-62`

---

## ADR-02: `App\Support\ConfiguracaoDoLogin` é o ponto único de ligação com o Settings

**Status**: Aceita
**Data**: 2026-08-24

### Contexto

RQ-02, RQ-04, RQ-06, RQ-11 e RQ-13 dizem, todos, que a configuração desta feature "vem da tela de
Settings". **A tela de Settings não existe.** Ela está sendo criada em paralelo, na branch
`feat/settings-do-kit`, que mergeia **antes** desta. O kit já tem `spatie/laravel-settings` 3.9.0
e `filament/spatie-laravel-settings-plugin` 5.7.6 instalados, mas nenhuma classe de settings.

Implementar contra uma classe que não existe é impossível; implementar espalhando `config()` pelo
controller, pelas rotas e por dois blades é pior — a migração viraria uma caça a chamadas, e o
quality gate não teria um lugar para conferir.

### Decisão

**Toda** leitura de configuração desta feature passa por `App\Support\ConfiguracaoDoLogin`, uma
classe `final` com três métodos estáticos e nenhum estado:

| Método | O que responde | Lê hoje de |
|---|---|---|
| `googleDisponivel(): bool` | o botão e as rotas entram no ar? | `config('kit.login.google.habilitado')` + `config('services.google')` |
| `rodapeDoLogin(): ?string` | qual o texto do rodapé? | `config('kit.login.rodape')` |
| `registroAberto(): bool` | pode criar conta nova? | `config('kit.registro.aberto', false)` — chave que **ainda não existe** |

Quando o Settings mergear, **só o corpo destes três métodos muda**. Controller, rotas, blades e
testes não são tocados.

O contrato está escrito no docblock da classe, não só aqui: quem abrir o arquivo lê que ele é o
ponto de ligação, sem precisar achar esta wiki.

### Alternativas Consideradas

1. **Esperar o merge do Settings** — sequencializaria cinco features paralelas por uma dependência
   que toca três métodos.
2. **`config()` direto no controller e nos blades** — cinco pontos de leitura, migração por
   `grep`, e nenhum lugar para o quality gate apontar.
3. **Interface + implementação `ConfigDoLogin` / `SettingsDoLogin` + binding no container** —
   interface com uma implementação. A escada do Ponytail proíbe, e o benefício (trocar
   implementação sem tocar o consumidor) é exatamente o que a classe estática já entrega, porque o
   consumidor chama um nome e não um corpo.
4. **Ler `Settings::class` com `class_exists()`** — condicional que nasce morta no dia do merge e
   fica no código para sempre.

### Consequências

- **Positivas**: a feature entrega inteira sem o Settings; a migração é um diff de três corpos de
  método; existe **um** lugar para auditar de onde vem cada decisão da tela de login.
- **Negativas**: `registroAberto()` lê uma chave de config que não existe ainda e sempre devolve
  `false`. Isso é **correto** por RQ-14 (o default do register é false), mas significa que o ramo
  de criação de conta só é exercido com a config forçada em teste.
- **Riscos**: a branch do Settings pode escolher outro nome de chave. Mitigado: o consumidor não
  conhece nome de chave nenhum.

### Referências

- `app/Support/ConfiguracaoDoLogin.php`
- Vizinhos do mesmo padrão: `app/Support/CorPrimaria.php`, `app/Support/NumeroDoEnv.php`,
  `app/Support/ValidadeDoConvite.php`
- `composer show --direct` → `spatie/laravel-settings 3.9.0`,
  `filament/spatie-laravel-settings-plugin 5.7.6`

---

## ADR-03: O interruptor desligado derruba a ROTA, não só o botão

**Status**: Aceita
**Data**: 2026-08-24

### Contexto

RQ-06 fala de **exibir o botão**. RQ-15 diz que o `true` "precisa refletir em tudo que vem". Um
desenho que só esconde o botão deixa `/auth/google/redirect` e `/auth/google/callback` no ar com o
interruptor desligado — e "escondido no HTML" não é barreira: a URL é fixa, pública e conhecida.

### Decisão

`abort_unless(ConfiguracaoDoLogin::googleDisponivel(), 404)` na **primeira linha** dos dois
métodos do controller. O mesmo predicado que decide o botão decide as rotas — um predicado, três
consumidores.

**404 e não 403**: 403 confirma que a rota existe e que a feature está no kit, apenas desligada
naquela instalação. 404 não confirma nada. Numa superfície não autenticada, a resposta mais
silenciosa é a certa.

As rotas continuam **registradas** sempre. Registro condicional quebraria o `route('auth.google.redirect')`
do blade (que só é chamado quando o botão aparece, mas passaria a estourar em qualquer
`route:list`, cache de rota ou teste que resolvesse o nome) e faria o comportamento depender da
ordem entre carregamento de config e de rotas.

### Alternativas Consideradas

1. **Só esconder o botão** — deixa a superfície de OAuth viva com a feature desligada.
2. **Registrar as rotas dentro de um `if`** — o nome da rota deixa de existir, e
   `route('auth.google.redirect')` estoura `RouteNotFoundException` em vez de simplesmente não
   aparecer. Também torna `route:cache` sensível ao `.env` do momento do cache.
3. **Middleware dedicado** — uma classe, um alias e uma registração para uma linha de `abort_unless`
   usada em dois lugares do mesmo controller.

### Consequências

- **Positivas**: uma condição governa botão e rota; a feature desligada não tem superfície.
- **Negativas**: o predicado roda duas vezes num fluxo completo (redirect e callback). É uma
  leitura de config.
- **Riscos**: se alguém desligar o interruptor **durante** um fluxo em andamento, o callback
  responde 404 e a pessoa fica sem explicação. Aceito: é o comportamento de uma feature
  desligada.

### Referências

- `app/Http/Controllers/Auth/LoginComGoogleController.php`
- CT-04, CT-05 (rota em 404 com interruptor desligado e com credencial incompleta)

---

## ADR-04: O ícone do Google é SVG inline, sem pacote novo

**Status**: Aceita
**Data**: 2026-08-24

### Contexto

RQ-09 pede "a icon correspondente". O kit usa `Filament\Support\Icons\Heroicon` e tem
`blade-ui-kit/blade-icons` + `blade-heroicons`. **Heroicons não tem logo de marca** — é um set de
ícones de interface, não de marcas. Não existe `Heroicon::Google`.

### Decisão

O SVG oficial do "G" de quatro cores, **inline** no blade do botão
(`resources/views/filament/auth/botao-google.blade.php`), com `aria-hidden="true"` e
`focusable="false"`, dentro de um botão que já tem texto.

### Alternativas Consideradas

1. **`codeat3/blade-simple-icons` (ou outro set de marcas)** — um pacote Composer, ~3.000 ícones e
   um `vendor:publish` para usar **um**. A escada do Ponytail para no degrau "já-instalado
   resolve?" e a resposta é não, mas o degrau seguinte é "uma linha", não "uma dependência".
2. **`Heroicon::GlobeAlt` ou similar** — ícone genérico não é "a icon correspondente"; falha RQ-09.
3. **`<img src="...">` de CDN do Google** — request externo em tela de login, e a tela de login é
   a que precisa carregar mesmo com a rede ruim.
4. **Arquivo `.svg` em `public/images/` + `<img>`** — um request a mais por render de tela de login
   para 700 bytes.

### Consequências

- **Positivas**: zero dependência nova, zero request externo, o ícone acompanha o tema porque as
  cores da marca são fixas por definição.
- **Negativas**: ~700 bytes de SVG no HTML de toda tela de login **onde o botão aparece** (com o
  botão fora do ar, o blade sai vazio). Quando chegar o segundo provedor (RQ-08), o SVG dele vai
  ao lado — e aí, com três ou mais, vale reavaliar um set de marcas.
- **Riscos**: o Google muda as diretrizes de marca do botão. Aceito: é um SVG num arquivo.

### Referências

- `resources/views/filament/auth/botao-google.blade.php`
- `.ai/rules/filament.md` — a armadilha da CSS pré-compilada, que é por que o botão é
  `<x-filament::button>` e não `<a>` com utilitárias

---

## ADR-05: Um render hook global, e não três subclasses de tela nem o hook `FOOTER`

**Status**: Aceita
**Data**: 2026-08-24

### Contexto

O kit tem três telas de login. Só a do `/app` é subclasse nossa
(`App\Filament\Pages\Auth\TelaLogin`, apontada em `AppPanelProvider.php:191`); `/admin` e `/infra`
usam a `Login` do Auth Designer direto (`AdminPanelProvider.php:113`, `InfraPanelProvider.php:146`).
RQ-03 pede o botão abaixo do form e RQ-10 pede o rodapé "na tela de login" — sem dizer em quantos
painéis.

### Decisão

**Uma** registração por superfície, em `FilamentView::registerRenderHook()`, sem `$scopes`, dentro
de `KitServiceProvider::configureTelaDeLogin()`. Sem escopo o hook cai na chave `''`
(`vendor/filament/support/src/View/ViewManager.php:32-34`), e o `renderHook()` renderiza a chave
`''` em **qualquer** escopo antes dos escopos pedidos (`:93-96`). Uma linha cobre os três painéis.

O hook é `PanelsRenderHook::AUTH_LOGIN_FORM_AFTER`
(`vendor/filament/filament/src/View/PanelsRenderHook.php:7`), emitido pela `content()` da tela de
login do Filament **depois** do componente do formulário
(`vendor/filament/filament/src/Auth/Pages/Login.php:458-466`). Global é seguro porque essa chave
só é emitida ali — não existe outra tela que a renderize.

### Alternativas Consideradas

1. **Sobrescrever `content()` em `TelaLogin`** — cobre só o `/app`. Para cobrir os três, duas
   subclasses novas (`TelaLoginAdmin`, `TelaLoginInfra`), duas linhas `->usingPage()` e — pela
   regra dura de `.ai/rules/auth.md` — duas redeclarações de `$layout` a mais para alguém
   esquecer. Três arquivos e três providers contra uma linha.
2. **`$panel->renderHook(...)` nos três providers** — funciona, e é exatamente o padrão que
   `tests/Kit/TelasDeAutenticacaoTest.php:76-78` chama de "defeito histórico do kit nessa área:
   configurar um painel e esquecer os outros dois". Três blocos idênticos que precisam ficar
   idênticos para sempre.
3. **`PanelsRenderHook::FOOTER` para o rodapé** — tentador, porque o layout do Auth Designer o
   renderiza (`vendor/caresome/filament-auth-designer/resources/views/components/layouts/auth.blade.php:63`).
   **Recusado**: o layout de painel autenticado também renderiza `FOOTER`, então o rodapé
   apareceria em toda tela de todo painel. RQ-10 diz "na tela de login".
4. **`SIMPLE_LAYOUT_END`** — mesmo problema: vale para toda `SimplePage`, e a tela de bloqueio de
   sessão e a de 2FA são `SimplePage`.

### Consequências

- **Positivas**: uma registração, três painéis, nenhuma subclasse nova, nenhum risco de
  reintroduzir o vazamento de `$layout` (nada de página de auth nova).
- **Negativas**: o hook também é emitido durante o desafio de MFA **nativo do Filament**, se
  alguém ligar `->multiFactorAuthentication()` no futuro — nenhum painel do kit liga hoje
  (`grep` em `app/Providers/Filament/`), e o 2FA que o kit usa é o do Breezy, que tem página
  própria e não passa por essa `content()`. Anotado, não mitigado, porque mitigar exigiria um
  `visible()` sobre estado interno da página do vendor.
- **Riscos**: uma feature futura que registre o mesmo hook num painel só vai conviver com este —
  os dois renderizam, na ordem. É o comportamento do `ViewManager`, não surpresa.

### Referências

- `vendor/filament/support/src/View/ViewManager.php:32-34,93-96,86-90`
- `vendor/filament/filament/src/Auth/Pages/Login.php:458-466`
- `vendor/filament/filament/src/View/PanelsRenderHook.php:7`
- `vendor/caresome/filament-auth-designer/resources/views/components/layouts/auth.blade.php:63`

---

## ADR-06: Login social AUTENTICA quem já tem conta; criar conta é privilégio do convite

**Status**: Aceita
**Data**: 2026-08-24

### Contexto

O exemplo da doc oficial do Socialite faz `User::updateOrCreate(['github_id' => …], [...])` no
callback. Copiado para este kit, isso significa: **qualquer pessoa com uma conta Google se torna
usuária do sistema**. O kit é construído em cima do oposto — o convite é a única porta
(`config/kit.php:223-224`: *"O convite é a única forma de alguém de fora virar usuário: a tela de
registro do painel /app recusa quem não traz um token válido"*), e `RegistroPorConvite::mount()`
recusa quem não traz token (`app/Filament/Pages/Auth/RegistroPorConvite.php:48-56`).

RQ-07 fala de "quem se registrar por um login social", o que pressupõe que registrar por social
seja possível. RQ-14 diz que o default do register é `false`.

### Decisão

Duas responsabilidades separadas, e a conjunção é a barreira:

1. **Autenticar** conta existente: sempre, quando o interruptor está ligado e o e-mail do Google
   está verificado.
2. **Criar** conta: **somente** quando `ConfiguracaoDoLogin::registroAberto()` é verdadeiro. Ele
   nasce `false` e a chave que ele lê ainda não existe (ADR-02), então o default do kit é
   **não criar**.

Sem conta e com registro fechado, o callback **recusa**: log `warning` com motivo
`conta_inexistente_registro_fechado`, notificação e volta para o login. Não cria, não autentica,
não vaza se o e-mail existe no sistema (a mensagem é a mesma da recusa por conta inexistente,
mas não confirma nem nega cadastro — ela diz que o acesso é por convite).

Quando cria, **não atribui papel**. Papel é o que dá acesso a painel
(`app/Models/User.php:76-105`), e decidir qual papel um registro aberto recebe é da feature de
registro e aprovação, que roda em paralelo. Consequência assumida: a conta nova é redirecionada
para `/app/meu-perfil` e recebe 403, porque não tem papel. **Isso é o comportamento correto do
kit**, não um bug a contornar — e está em `## Ambiguidades` do `00-requisito.md` com o par
"Assumido / Se negado".

### Alternativas Consideradas

1. **`updateOrCreate` como na doc** — furo de autorização. É o defeito que esta ADR existe para
   não cometer.
2. **Criar sempre, mas com a conta "pendente de aprovação"** — inventa um estado (`aprovado_em`,
   uma coluna, uma migration, uma tela de aprovação) que é exatamente o escopo da branch
   `feat/registro-e-aprovacao`. Duas features escrevendo o mesmo estado é conflito garantido.
3. **Criar e atribuir `panel_user`** — decide no lugar da outra feature qual papel o registro
   aberto concede, e para o kit single-tenant `panel_user` dá acesso ao `/app` inteiro.
4. **Aceitar um token de convite na query string do redirect** (login social que consome convite)
   — feature nova que o requisito não pede, e o convite já tem fluxo próprio para quem já tem
   conta (`Convite::aceitarComoUsuarioExistente()`).

### Consequências

- **Positivas**: o convite continua sendo a única porta no default; o furo mais caro desta feature
  não existe; a fronteira é uma linha de `if` que um teste mata (CT-08).
- **Negativas**: o ramo de criação de conta é código que **não roda** na configuração de fábrica.
  Ponytail normalmente proibiria — aqui ele existe porque RQ-07 o pede explicitamente e porque a
  alternativa (não implementar) deixaria a cláusula sem cobertura. Fica coberto por CT-09, que
  força a config.
- **Riscos**: a branch de registro aberto pode nomear a chave diferente. Mitigado por ADR-02.

### Referências

- `app/Filament/Pages/Auth/RegistroPorConvite.php:48-66`
- `config/kit.php:223-224`
- `app/Models/User.php:76-105`
- `.ai/rules/filament.md` — "Asserção de identidade vive no model, não na query da tela": o mesmo
  raciocínio de fronteira, aplicado à identidade que vem de fora
- CT-06 a CT-09

---

## ADR-07: O vínculo é pelo e-mail verificado, sem coluna `google_id`

**Status**: Aceita
**Data**: 2026-08-24

### Contexto

Duas formas de casar o usuário do provedor com o do kit:

- **por `provider_id`**: coluna nova (`google_id`), migration, e um vínculo estável a um
  identificador imutável do Google (`sub`);
- **por e-mail**: nenhuma coluna, mas o e-mail é mutável e — o risco real — um provedor que
  devolve um e-mail **não verificado** permite que alguém crie uma conta Google com o e-mail de
  outra pessoa e entre na conta dela. É a tomada de conta clássica do login social.

### Decisão

Casar por **e-mail**, com duas condições não negociáveis:

1. **`email_verified` tem de ser verdadeiro.** O `GoogleProvider::mapUserToObject()` põe no raw
   tanto `email_verified` (do userinfo v3) quanto o alias legado `verified_email`
   (`vendor/laravel/socialite/src/Two/GoogleProvider.php:90-92`). O callback lê os dois, na ordem,
   com `filter_var(..., FILTER_VALIDATE_BOOLEAN)`. **Falso, ausente ou não-booleano ⇒ recusa**,
   com log `warning` e motivo `email_nao_verificado`. Falha fechado.
2. **Comparação normalizada** nos dois lados (`mb_strtolower(trim(...))`), a mesma régua de
   `Convite::exigirDono()` — e-mail não é case-sensitive na prática.

**Nenhuma coluna nova.** O `sub` do Google não é gravado, e o token de acesso e o refresh token
também não.

### Alternativas Consideradas

1. **Coluna `google_id` + migration** — o benefício real é sobreviver à troca de e-mail no Google,
   e o custo é uma migration numa tabela (`users`) que quatro features paralelas estão tocando
   nesta rodada. O `updateOrCreate(['google_id' => …])` da doc, além disso, **cria** — o que
   ADR-06 recusa.
2. **Tabela `social_accounts` polimórfica** — a forma certa quando há vários provedores com vários
   vínculos por usuário. RQ-08 diz que há **um** provedor e que os outros vêm depois; interface
   com uma implementação, de novo.
3. **Gravar o token de acesso** — o kit não chama nenhuma API do Google em nome do usuário. Token
   guardado sem uso é passivo: mais um segredo em repouso, mais uma coluna a excluir da auditoria
   e do export.
4. **Aceitar e-mail não verificado e mandar verificar por e-mail do kit** — o kit não exige
   verificação de e-mail (`->emailVerification(null, isRequired: false)` nos três providers), então
   não há para onde mandar.

### Consequências

- **Positivas**: zero schema, zero migration, zero conflito com as features paralelas. A tomada de
  conta por e-mail não verificado está fechada e testada (CT-10).
- **Negativas**: se a pessoa trocar o e-mail na conta Google, o vínculo se perde e ela precisa
  entrar por senha. Aceitável para um kit; documentado no README.
- **Riscos**: um provedor futuro que **não** informe verificação de e-mail (nem todos informam)
  não pode ser aceito com este desenho sem uma decisão nova. Isso é registrado aqui de propósito,
  para o dia do segundo provedor.

### Referências

- `vendor/laravel/socialite/src/Two/GoogleProvider.php:90-92`
- `app/Models/Convite.php` — `exigirDono()`, comparação normalizada
- CT-10, CT-07

---

## ADR-08: `filter_var` no interruptor, nunca `(bool) env()`

**Status**: Aceita
**Data**: 2026-08-24

### Contexto

`.ai/rules/config.md` é explícita: o segundo argumento do `env()` só vale para chave **ausente**.
Com `KIT_SOCIALITE_GOOGLE=` (presente, valor vazio — o que sobra quando alguém apaga o valor), o
`env()` devolve string vazia e o default nunca entra. A rule documenta cinco chaves do kit que
nasceram com esse defeito, uma delas apagando dado.

Para booleano o caso é mais traiçoeiro que para inteiro: `(bool) ''` é `false`, que **por acidente**
é o default correto aqui — mas `KIT_SOCIALITE_GOOGLE=false` chega como a **string** `"false"`, e
`(bool) "false"` é `true`. O jeito errado liga a feature exatamente quando a pessoa escreveu que
não queria.

### Decisão

`filter_var(env('KIT_SOCIALITE_GOOGLE', false), FILTER_VALIDATE_BOOLEAN)` em `config/kit.php`.
Ele devolve `false` para `""`, `"false"`, `"0"`, `"off"`, `"no"` e `null`, e `true` para `"true"`,
`"1"`, `"on"`, `"yes"`. É a mesma coerção que o próprio Laravel usa no helper `env()` para os
literais que ele reconhece — a diferença é que aqui ela é aplicada ao **valor**, não à ausência.

Não se cria uma classe em `app/Support/` para isso, como `NumeroDoEnv` fez para inteiros: uma
função da stdlib resolve, e `NumeroDoEnv` existe porque o zero tem **significado diferente** por
chave, o que não acontece com booleano.

### Alternativas Consideradas

1. **`(bool) env(...)`** — o defeito descrito acima.
2. **`env('KIT_SOCIALITE_GOOGLE', false)` cru** — `config()` devolveria a string `"true"`, e o
   `if` funcionaria por coincidência, mas `=== true` não.
3. **`App\Support\BooleanoDoEnv`** — classe nova para uma chamada de `filter_var`.

### Consequências

- **Positivas**: `KIT_SOCIALITE_GOOGLE=false` desliga, que é o que quem escreve espera.
- **Negativas**: nenhuma.
- **Riscos**: se o kit ganhar mais interruptores booleanos por env, a linha se repete. Aí vale
  extrair — hoje é uma.

### Referências

- `.ai/rules/config.md`
- `config/kit.php` — bloco `login`
- CT-02, CT-03

---

## ADR-09: `client_secret` e o rodapé — os dois lados da mesma fronteira

**Status**: Aceita
**Data**: 2026-08-24

### Contexto

Esta feature acrescenta um segredo (`GOOGLE_CLIENT_SECRET`) e um campo de **texto livre que a tela
de Settings vai editar e que a tela de login, pública e não autenticada, vai renderizar** (o
rodapé). Os dois são fronteira de confiança, em direções opostas: o segredo não pode **sair**, o
rodapé não pode **entrar** como código.

### Decisão

**O segredo, hoje (env):**

- Vive em `GOOGLE_CLIENT_SECRET`, lido por `config('services.google.client_secret')`, e é usado
  **só** pelo Socialite, dentro do pacote.
- **Nenhuma** linha de log desta feature o menciona. Os logs carregam `motivo`, `user_id`, `ip`,
  `provedor` e o e-mail **mascarado** (`Str::mask($email, '*', 3)`) — a mesma régua de
  `app/Models/Convite.php:176-179` e `app/Console/Commands/KitAdmin.php:216-226`.
- **Nenhuma** mensagem de erro ao usuário o menciona: no `catch` a mensagem é genérica, o que
  também evita dizer a um atacante qual barreira ele encostou.
- Token de acesso e refresh token **não são gravados nem logados** (ADR-07).
- É caso de teste, não promessa: CT-11 assere que o valor do segredo não aparece no HTML das três
  telas de login **nem** no que o channel `autenticacao` grava durante o callback.

**O segredo, quando o Settings o editar (banco) — condições que a branch do Settings tem de
cumprir:**

1. Coluna **encriptada** — `spatie/laravel-settings` suporta cast de encriptação; o valor não fica
   em claro na tabela, no dump nem no backup (`spatie/laravel-backup` 10.3.1 está no kit).
2. Campo Filament `->password()->revealable(false)`, e o valor gravado **não** repopulado no form
   — campo vazio significa "mantém o que está lá".
3. **Fora da auditoria.** A model de Settings vai usar o pacote de audits (é o requisito integral
   que pede), e `App\Traits\AuditsFillables` grava os `fillable`. O segredo precisa sair da lista
   auditada, senão a tela de auditoria do `/infra` passa a exibir o par (valor antigo, valor novo)
   do client secret para quem tem acesso a ela.
4. **Fora do export.** `App\Support\ImportExport\ExportadorDoKit` — se houver export de Settings, a
   coluna não vai.

Está escrito aqui, e não só na wiki do Settings, porque quem introduz o segredo é esta feature.

**O rodapé:**

Renderizado com `{{ $rodape }}`, **nunca** `{!! !!}`. O valor vem de campo editável e é servido
numa página **sem autenticação**: HTML cru ali é XSS armazenado com o pior alcance possível — a
tela por onde todo mundo entra. Se algum dia for preciso link no rodapé, a resposta é um campo
estruturado (texto + URL, com validação de esquema), não um campo de HTML. CT-16 é o caso.

### Alternativas Consideradas

1. **Logar o `client_id` para depurar configuração** — recusado. Não é segredo, mas é metade do
   par e não ajuda em nada que o `motivo` do log já não diga.
2. **Mensagem de erro específica por barreira** ("credencial inválida", "state inválido") —
   ajudaria o operador e ajudaria também quem estivesse sondando. O `motivo` no log dá ao operador
   a mesma informação, sem dar à tela.
3. **Permitir HTML no rodapé com sanitização** (`HTMLPurifier`) — dependência nova, uma superfície
   de bypass a manter, para um rodapé.

### Consequências

- **Positivas**: o segredo não tem caminho de saída; o rodapé não tem caminho de entrada de
  código; as duas coisas têm caso de teste.
- **Negativas**: quem configurar o OAuth errado vê uma mensagem genérica e precisa do log para
  descobrir o motivo. Aceito — é o lado certo do trade-off numa tela pública.
- **Riscos**: as quatro condições do segredo no banco dependem da branch do Settings cumpri-las.
  Mitigação: estão escritas aqui, e o `03-progresso.md` as lista como débito a conferir depois do
  rebase.

### Referências

- `app/Models/Convite.php:176-179` — régua de mascaramento
- `app/Console/Commands/KitAdmin.php:216-226` — "Log sem credencial: e-mail mascarado e nenhuma
  menção à senha além do fato de ter"
- `.ai/rules/views.md` — o comentário Blade não protege diretiva
- CT-11, CT-16

---

## ADR-10: Nenhuma abstração de provedor com uma implementação

**Status**: Aceita
**Data**: 2026-08-24

### Contexto

RQ-08 diz: Google primeiro, "depois, podemos disponibilizar mais opções como github, facebook,
linkedin, x, discord e etc". A leitura natural de um plano é preparar o terreno: uma interface
`ProvedorSocial`, um enum de provedores, um blade que percorre uma lista, um controller genérico
com `{provedor}` na rota.

### Decisão

**Não.** Um provedor, um controller, uma rota por ponta, um blade. A extensão futura é:
acrescentar o bloco em `config/services.php`, uma chave em `config/kit.php`, um método no
`ConfiguracaoDoLogin`, um par de rotas e um blade — e **então**, com dois ou três provedores reais
na frente, extrair o que se repetir de fato.

O que o desenho faz para não **impedir** a extensão, e nada além disso:

- a constante `ConfiguracaoDoLogin::PROVEDOR_GOOGLE`, que nomeia o driver num só lugar;
- o predicado de disponibilidade separado do resto, então um segundo provedor não mexe no primeiro;
- as rotas com prefixo `auth/google/` e nome `auth.google.*`, que deixam espaço nominal para
  `auth.github.*` sem renomear nada.

### Alternativas Consideradas

1. **Rota genérica `/auth/{provedor}/callback`** — quebra RQ-05, que fixa `/auth/google/callback`
   literalmente, e transforma o nome do provedor em entrada do usuário a validar contra uma lista
   branca. Mais superfície, não menos.
2. **Interface `ProvedorSocial` + `GoogleProvedor`** — interface com uma implementação. A escada do
   Ponytail proíbe, e o `Socialite` **já é** essa abstração: `Socialite::driver($nome)`.
3. **Enum `ProvedorSocial: string`** — enum de um caso.

### Consequências

- **Positivas**: o diff é pequeno e legível; nenhuma abstração para alguém decifrar às 3h da manhã.
- **Negativas**: o segundo provedor duplica ~30 linhas de controller antes de valer a extração.
  Assumido: a extração feita com **dois** casos na mão acerta a forma; feita com **um**, adivinha.
- **Riscos**: alguém acrescenta o segundo provedor copiando e colando e esquece o
  `abort_unless`/`email_verified`. Mitigação: o README diz o que o segundo provedor precisa ter, e
  a lista de casos de teste é a mesma.

### Referências

- `app/Support/ConfiguracaoDoLogin.php`
- README, seção "Login social" — o roteiro do segundo provedor
