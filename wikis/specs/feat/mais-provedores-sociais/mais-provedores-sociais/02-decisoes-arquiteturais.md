# Decisões Arquiteturais — W8: mais provedores de login social

> Toda afirmação sobre comportamento do Socialite abaixo cita `file:line` do `vendor/`, como
> `.ai/rules/specs.md` exige. Versão instalada: `laravel/socialite ^5.30` (`composer.json:51`).
> Os caminhos são relativos a `vendor/laravel/socialite/`.

## ADR-01: A abstração é um enum, e agora ela tem quatro casos

**Status**: Aceita
**Data**: 2026-08-24

### Contexto

O ADR-10 da wiki `login-social-google` recusou toda abstração de provedor e escreveu o critério
de quando reabrir a decisão:

> "Uma constante e não um enum, porque enum de um caso é abstração sem segundo caso. Quando o
> GitHub (ou outro) entrar, a decisão de extrair se toma com DOIS casos na mão — feita com um,
> ela adivinha a forma."

Esta entrega tem os casos na mão. E a forma que eles revelaram **não** é a que se adivinharia com
um: o eixo que varia entre provedores não é o redirect, nem o botão, nem o predicado de
disponibilidade — esses três são idênticos. O que varia, e varia radicalmente, é **como cada
provedor prova que o e-mail está verificado** (ADR-03). Uma interface `ProvedorSocial` desenhada
em cima do Google teria abstraído `redirect()`/`callback()`, que não precisavam de abstração, e
deixado de fora a única coisa que precisava.

### Decisão

Um enum string-backed, `App\Support\ProvedorSocial`, com quatro casos: `Google`, `Github`,
`LinkedIn`, `X`. E uma regra que elimina toda tabela de mapeamento:

> **o `value` do caso é, ao mesmo tempo, o nome do driver do Socialite, o segmento da URL, a
> chave em `config/services.php` e a chave em `config/kit.php`.**

Daí `linkedin-openid` como valor do caso `LinkedIn`: é o nome que o `SocialiteManager` exige
(`src/SocialiteManager.php:108-113`, que lê `services.linkedin-openid` em `:110`). A URL fica
`/auth/linkedin-openid/callback`, que é menos bonito e mais informativo — diz ao operador qual das
duas APIs de LinkedIn está em uso.

O enum carrega, e cada membro tem um consumidor real:

| Membro | Para quê | Consumidor |
|---|---|---|
| `value` | driver, URL, chave de config | Socialite, rotas, `ConfiguracaoDoLogin` |
| `rotulo()` | "Entrar com GitHub" | blade do botão, label do Settings, log |
| `icone()` | nome da partial do SVG | blade do botão |
| `emailVerificado()` | a barreira do ADR-03 | controller |
| `propriedadeDeSettings()` | `login_linkedin_openid_habilitado` | Settings e a tela |

`propriedadeDeSettings()` existe porque nome de propriedade PHP não aceita hífen. É a **única**
transformação de nome no desenho, e ela é um `str_replace('-', '_', $this->value)`.

### Alternativas Consideradas

1. **Manter uma constante por provedor, como o ADR-10 fez** — quatro constantes, quatro
   predicados, quatro controllers, oito rotas, quatro blades. É a alternativa que o próprio
   ADR-10 disse que deixaria de valer no segundo caso, e ela falha no RQ-02: o quinto provedor
   volta a copiar tudo.
2. **Interface `ProvedorSocial` + quatro classes** — quatro arquivos para hospedar um `match` de
   cinco linhas cada. Um enum com métodos é a mesma coisa em um arquivo, e `Socialite::driver()`
   já é a fábrica.
3. **Enum com o valor = nome curto (`linkedin`) + método `driver()`** — deixa a URL bonita e
   introduz duas strings por provedor, mais um lugar para elas divergirem. Recusada: a feiura da
   URL é cosmética e documentada; a divergência é um defeito silencioso (credencial gravada em
   `services.linkedin` e lida em `services.linkedin-openid` = botão no ar apontando para um OAuth
   inexistente).

### Consequências

- **Positivas**: o quinto provedor é um caso no enum, um bloco em `config/services.php`, um em
  `config/kit.php`, três propriedades no Settings e uma partial de SVG. Nenhum arquivo existente
  ganha lógica nova. RQ-02 atendido.
- **Negativas**: o enum sabe de HTTP (a branch do GitHub, ADR-03). É o preço de manter a
  verificação de e-mail no mesmo lugar que nomeia o provedor, em vez de espalhá-la pelo
  controller.
- **Riscos**: um caso novo no enum entra em rota **imediatamente** (as rotas usam o enum no
  parâmetro). Provedor sem as chaves de config responde 404 pelo predicado, então o risco é
  contido — e quem acrescenta um caso precisa acrescentar o `emailVerificado()` dele, porque um
  `match` exaustivo sem o ramo novo **não passa** no PHPStan e estoura `UnhandledMatchError` em
  execução. É a mitigação: a ferramenta cobra.

### Referências

- `app/Support/ProvedorSocial.php`
- ADR-10 de `wikis/specs/feat/login-social-google/login-social-google/02-decisoes-arquiteturais.md`

---

## ADR-02: Uma rota com `{provedor}`, agora que o parâmetro se valida sozinho

**Status**: Aceita — **substitui** a alternativa 1 recusada no ADR-10 da wiki do Google
**Data**: 2026-08-24

### Contexto

O ADR-10 recusou a rota genérica com dois argumentos: ela quebraria o caminho literal
`/auth/google/callback` que o requisito fixou, e "transforma o nome do provedor em entrada do
usuário a validar contra uma lista branca. Mais superfície, não menos."

O primeiro argumento não se sustenta: `/auth/{provedor}/callback` **produz**
`/auth/google/callback` quando o parâmetro é `google`. O caminho literal está preservado, e
nenhuma URI cadastrada em console de provedor nenhum precisa mudar.

O segundo deixou de valer: com **implicit enum binding**, tipar `ProvedorSocial $provedor` na
assinatura faz o Laravel devolver **404 automático** para qualquer segmento fora do enum (doc do
Laravel 13, seção *Implicit Enum Binding*, consultada via `search-docs`). A lista branca não é
código que alguém escreve e mantém em sincronia — ela **é** o enum, e o roteador a consulta.

### Decisão

Um par de rotas:

```text
Route::middleware('throttle:10,1')->prefix('auth/{provedor}')->name('auth.social.')
    GET redirect  -> auth.social.redirect
    GET callback  -> auth.social.callback
```

As rotas continuam registradas **SEMPRE**, e quem as tira do ar é o `abort_unless()` do
controller — a decisão do ADR-03 da wiki do Google, mantida integralmente e agora por provedor.

O nome da rota deixa de ser `auth.google.*` e passa a ser `auth.social.*` com o provedor como
parâmetro. Foi conferido antes de decidir: **nenhum teste** usa o nome da rota — todos usam a URL
literal — e o único consumidor do nome era
`resources/views/filament/auth/botao-google.blade.php:32`, que esta entrega reescreve de qualquer
forma.

### Alternativas Consideradas

1. **Um par de rotas por provedor, gerado num `foreach` sobre `cases()`** — preserva
   `route('auth.google.redirect')`, e custa oito rotas em `route:list` onde duas bastam. Também
   precisaria de `->defaults('provedor', …)` para o controller saber quem é, e aí o parâmetro
   existe sem estar na URI — mais indireto, não menos.
2. **`->whereIn('provedor', ProvedorSocial::cases())`** — funciona (doc do Laravel 13,
   *Regular Expression Constraints*, aceita `cases()`), e é redundante com o enum binding, que já
   404. Duas guardas para a mesma pergunta é o que `.ai/rules/config.md` chama de "duas fontes";
   uma delas envelheceria.

### Consequências

- **Positivas**: `route:list` tem duas linhas em vez de oito; acrescentar provedor não toca
  `routes/web.php`.
- **Negativas**: o `throttle:10,1` passa a ser compartilhado pelo grupo — dez requisições por
  minuto por IP **somando todos os provedores**, e não dez por provedor. Aceito: o limite existe
  contra script, e quem alterna quatro provedores em um minuto é script.
- **Riscos**: o nome `auth.social.*` é novo, então um `route:cache` antigo com
  `auth.google.redirect` estouraria. Não é risco real — o nome só era usado num blade.

---

## ADR-03: A verificação do e-mail é o eixo que varia, e cada provedor prova de um jeito diferente

**Status**: Aceita
**Data**: 2026-08-24

### Contexto

A barreira do Google é `filter_var($raw['email_verified'] ?? $raw['verified_email'] ?? false,
FILTER_VALIDATE_BOOLEAN)`, e ela existe porque casar conta por e-mail que o provedor não verificou
é a tomada de conta clássica do login social: bastaria criar uma conta no provedor com o e-mail de
outra pessoa.

Investigando provedor por provedor no `vendor/`, o que se descobre é que **nenhum dos outros expõe
isso do mesmo jeito**, e dois não expõem de jeito nenhum.

### A tabela, com `file:line`

| Provedor | Onde está a prova de verificação | Forma da prova |
|---|---|---|
| **Google** | `src/Two/GoogleProvider.php:90-92` põe `email_verified` e o alias `verified_email` no payload; `setRaw()` em `:94` | booleano no bruto |
| **LinkedIn OpenID** | `src/Two/LinkedInOpenIdProvider.php:61` pede a projeção, `:73` faz `setRaw()`, `:80` mapeia `email_verified` no objeto | booleano no bruto **e** no objeto |
| **X** | `src/Two/TwitterProvider.php:61` pede `user.fields=…,confirmed_email`; `:74` mapeia `confirmed_email` para `email`; `XProvider.php:8` herda e só troca as URLs (`:15,:23,:31`) | **presença** do valor É a prova |
| **GitHub** | `src/Two/GithubProvider.php:73` avalia `$email['primary'] && $email['verified']` — **e descarta a evidência**: `:48` guarda só a string | nenhuma, no bruto |
| **Facebook** | `src/Two/FacebookProvider.php:34` pede `verified` (nível de conta, legado na Graph v23.0 `:27`); o caminho OIDC `:134-167` não traz `email_verified` | nenhuma → **recusado**, ADR-05 |

Dois detalhes que mudam o desenho e não são óbvios:

1. **`$user->email_verified` funciona só no LinkedIn OpenID.** `AbstractUser::map()`
   (`src/AbstractUser.php:138-149`) só atribui a propriedade real quando `property_exists`
   (`:143`); o resto vai para `$attributes`. O Google **não** mapeia `email_verified`, então
   `$user->email_verified` é `null` para ele e o valor só existe em `getRaw()`. E `$user['x']`
   (`ArrayAccess`, `:170-173`) lê o **bruto**, não os atributos — os dois acessores apontam para
   arrays diferentes. Ler pelo lugar errado devolveria `null` e, com falha fechada, recusaria
   todo login: um defeito que se manifesta como "o provedor nunca deixa ninguém entrar".
2. **O GitHub tem um `catch` que engole.** `GithubProvider.php:62` chama `/user/emails` só quando
   `'user:email'` está nos escopos (`:47`), e em qualquer falha faz `catch → return` (`:68-70`),
   deixando em `$response['email']` o que `/user` devolveu — o e-mail do **perfil público**.
   Então "e-mail não vazio" **não** é prova de verificação: é prova de que ou a verificação
   passou, ou a chamada falhou. Indistinguível de fora.

### Decisão

O enum ganha `emailVerificado(AbstractUser $doProvedor): bool`, com um `match` sobre `$this` e
**falha fechada em todos os ramos**:

- **Google** — `filter_var` sobre `email_verified` / `verified_email` do bruto. Inalterado.
- **LinkedIn** — `filter_var` sobre `email_verified` do bruto.
- **X** — a presença de e-mail já é a prova (o X só devolve `confirmed_email`), então o ramo
  devolve `filled($doProvedor->getEmail())`. Documentado como tal, não escondido atrás de um
  `true`.
- **GitHub** — o kit **refaz a chamada** a `https://api.github.com/user/emails` com o token, e
  exige uma entrada com `primary === true`, `verified === true` e `email` igual ao que veio. É
  prova positiva, não presumida, e fecha o buraco do `catch`.

`filter_var` e não cast de bool, pelo motivo já medido no kit: o valor vem do JSON do provedor, e
a string `"false"` num cast de bool é `true`.

### Alternativas Consideradas

1. **Aceitar o e-mail do GitHub como verificado** — é o que a maioria dos tutoriais faz, e é
   exatamente o furo que o `catch` abre. Recusada.
2. **Confiar em `approvedScopes` para o GitHub** — `AbstractProvider::userInstance()` popula
   `setApprovedScopes()` em `:261`, então dá para saber se `user:email` foi concedido. Mas isso
   prova que o **escopo** foi concedido, não que a **chamada** funcionou — o `catch` continua
   engolindo. Recusada por não responder à pergunta.
3. **Um método por provedor num serviço separado** — mesmo código, um arquivo a mais, e o nome do
   provedor deixa de ficar ao lado da regra dele.

### Consequências

- **Positivas**: a barreira que o Google já tinha vale igual para os quatro, com prova positiva
  em todos.
- **Negativas**: o GitHub custa **uma requisição HTTP a mais** por login, na mesma rota em que o
  Socialite já bateu duas vezes. Aceito.
- **Riscos**: a chamada do GitHub pode falhar por rede e recusar um login legítimo. É a direção
  correta do erro (falha fechada), e o motivo vai para o log com o e-mail mascarado. Em teste,
  `Http::fake()` — **nenhum caso sai para a rede**.

---

## ADR-04: Discord fica fora — não é driver do Socialite

**Status**: Aceita
**Data**: 2026-08-24

### Contexto

O requisito nomeia Discord (RQ-01), e a instrução de escopo desta entrega afirmava que "o
socialite já suporta os cinco". A premissa é **falsa**, e foi conferida por duas fontes
independentes:

- `vendor/laravel/socialite/src/Two/` não tem `DiscordProvider.php`. Os drivers do diretório são
  Bitbucket, Facebook, Github, Gitlab, Google, LinkedInOpenId, LinkedIn, SlackOpenId, Slack,
  Twitter e X. `vendor/socialiteproviders/` não existe nesta instalação.
- A documentação oficial (via `search-docs`, seção *Introduction*) lista "Facebook, X, LinkedIn,
  Google, GitHub, GitLab, Bitbucket, and Slack" e remete o resto a `socialiteproviders.com`,
  descrito como "community driven".

### Decisão

Discord **fora desta entrega**. Habilitá-lo exigiria `composer require socialiteproviders/discord`
e o registro de um listener de `SocialiteWasCalled` — uma dependência nova **e** um segundo
mecanismo de extensão, num desenho cuja premissa é que um provedor é um caso de enum.

A instrução de escopo é explícita: "Não adicione dependência". Entre desobedecê-la e entregar sem
Discord, entregar sem Discord é a premissa mais estreita — e é reversível numa linha de
`composer require` no dia em que o dono do kit disser que quer.

O README documenta **como** adicioná-lo, para que a ausência seja escolha registrada e não
esquecimento.

### Consequências

- **Negativas**: RQ-01 fica parcialmente atendida. Registrado em `## Cobertura do Requisito` do
  PRD como fora de escopo justificado, nunca como atendido.

---

## ADR-05: Facebook fica fora — não há como confirmar o e-mail

**Status**: Aceita
**Data**: 2026-08-24

### Contexto

O requisito desta rodada põe a decisão por escrito: "Provedor que não permite confirmar
verificação do e-mail é decisão de ADR: recusar o provedor, ou aceitar e documentar o risco.
Escolha a premissa mais estreita e registre."

O Facebook não permite confirmar. O `$fields` do provider (`src/Two/FacebookProvider.php:34`) pede
`verified`, que é campo de nível de **conta**, legado, e ausente na Graph v23.0 que o provider usa
(`:27`); o caminho OIDC/Limited Login (`:134-167`) devolve claims JWT sem `email_verified`. Não
existe, em nenhum dos dois caminhos, um valor que afirme que **aquele endereço** foi confirmado.

### Decisão

**Recusar.** O Facebook não entra no enum.

O que se ganharia aceitando: um botão a mais. O que se perderia: a barreira que separa "o kit
autentica quem prova ser dono do e-mail" de "o kit autentica quem digitou o e-mail em algum
formulário". Com a conta já existente no kit — o caminho normal, porque o registro nasce
fechado — o e-mail não verificado é **exatamente** o vetor de tomada de conta: alguém cria um
Facebook com o endereço de um usuário do sistema e entra como ele.

Não dá para mitigar recusando apenas a criação de conta: a criação é o risco menor. O risco maior
é o casamento com conta existente, que é o caminho principal.

### Alternativas Consideradas

1. **Aceitar e documentar o risco** — a outra metade da escolha que o requisito ofereceu.
   Recusada porque o kit já cobra prova positiva do Google e do LinkedIn; aceitar aqui faz o nível
   de garantia depender de qual botão a pessoa clicou, o que é pior do que não ter o botão.
2. **Aceitar só quando o registro aberto estiver ligado** — inverte a régua: liberaria o provedor
   menos confiável no cenário mais permissivo.
3. **Exigir segundo fator para quem entra por Facebook** — o kit tem 2FA (Breezy), mas ele é
   opcional por usuário; condicioná-lo ao provedor é feature nova, não mitigação.

### Consequências

- **Negativas**: RQ-01 perde o Facebook. Registrado como fora de escopo justificado.
- **Riscos**: se a Graph API voltar a expor sinal de verificação, esta ADR precisa ser
  revisitada. O README diz o que faltaria para incluí-lo.

---

## ADR-06: O `client_secret` no Settings estava cifrado só na ida — o defeito é anterior e bloqueia esta entrega

**Status**: Aceita
**Data**: 2026-08-24

### Contexto

O requisito manda imitar o campo do Google: "`client_secret` de cada um: cifrado no Settings,
zerado no fill, só gravado quando preenchido. (…) imite, não reinvente." Ao ler o campo do Google
para imitá-lo, apareceu um defeito **anterior a esta entrega**:

- `database/settings/2026_08_24_000000_create_kit_settings.php` semeia com
  **`addEncrypted('login_google_client_secret', …)`** — cifra na gravação;
- `App\Settings\ConfiguracoesDoKit::encrypted()` devolve **`['mail_password']`** — o segredo do
  Google **não está lá**, e a classe não usa o atributo `#[ShouldBeEncrypted]` (nenhuma ocorrência
  em `app/`).

Quem decide se um valor é decifrado na leitura é `SettingsConfig::isEncrypted()`
(`vendor/spatie/laravel-settings/src/SettingsConfig.php:84-87`), alimentado por `encrypted()` mais
o atributo (`:57-59`). Os dois consumidores são `SettingsMapper::fetchProperties()` (`:92`, decifra
na leitura) e `SettingsMapper::save()` (`:67`, cifra na gravação). Com o nome fora da lista,
**os dois** se omitem.

Consequência, em dois sintomas de direções opostas:

1. **Instalação nova com `GOOGLE_CLIENT_SECRET` preenchido no `.env`**: a migration cifra, a
   leitura devolve o **texto cifrado**, e `config('services.google.client_secret')` recebe o
   ciphertext. `filled()` é verdadeiro, então o botão entra no ar — e o OAuth falha no Google com
   credencial inválida. Botão no ar apontando para um login quebrado.
2. **Depois de alguém salvar o segredo pela tela**: `save()` também consulta `isEncrypted()`,
   então grava em **texto claro**. Aí o login passa a funcionar, e o segredo fica em claro na
   tabela `settings` — contrariando por escrito o `helperText` do campo ("Guardado cifrado"), o
   comentário da migration e a rule `.ai/rules/pages.md` ("Os dois estão em
   `ConfiguracoesDoKit::encrypted()`").

Por que ninguém viu: `Crypto::encrypt(null)` devolve `null`
(`vendor/spatie/laravel-settings/src/Support/Crypto.php:8-12`), e o segredo é `null` em toda
instalação de desenvolvimento e em toda a suíte de testes. **O defeito só existe quando há
valor**, e os dois casos que cobrem o campo (`tests/Kit/LoginSocialGoogleTest.php:822,841`)
verificam o HTML e a sobrevivência do valor — nenhum verifica que o que está gravado é ciphertext.

### Decisão

Consertar antes de estender, porque estender sem consertar entrega **três** segredos com o mesmo
defeito. Duas mudanças:

1. `encrypted()` passa a devolver os quatro segredos: `mail_password` e os `*_client_secret` dos
   três provedores com credencial (`google`, `github`, `linkedin-openid`, `x`).
2. A migration desta entrega **normaliza** o valor já gravado: para `login_google_client_secret`,
   tenta decifrar — se decifra, já era ciphertext e fica como está; se estoura `DecryptException`,
   estava em claro e é cifrado agora. `null` passa reto.

O ponto 2 é o que impede que o conserto do ponto 1 quebre quem já salvou o segredo pela tela: sem
ele, `encrypted()` passaria a mandar decifrar um texto claro, e o `catch (Throwable)` do
`KitServiceProvider` engoliria a leitura inteira do grupo — a instalação voltaria ao `.env` em
silêncio, perdendo **todas** as configurações da tela, não só o segredo.

### Alternativas Consideradas

1. **Tirar o `addEncrypted` da migration e assumir texto claro** — mais simples e para o lado
   errado. A tabela `settings` guarda JSON em claro, e dump de banco, backup e a tela de auditoria
   são três caminhos que a permissão da tela não cobre (é o argumento do ADR-05 da wiki
   `settings-do-kit`, escrito para a senha do SMTP e igualmente válido aqui).
2. **Só acrescentar os três novos a `encrypted()` e deixar o Google como está** — deixaria o
   defeito vivo no provedor de referência, e `.ai/rules/specs.md` diz exatamente para não fazer
   isso ("varra o padrão no repo inteiro antes de consertar aquele ponto").
3. **Migration de dados separada** — mesma quantidade de código, um arquivo a mais, e a
   normalização deixa de estar ao lado do `add` dos novos segredos.

### Consequências

- **Positivas**: os quatro segredos ficam cifrados de verdade, na ida e na volta. A rule
  `.ai/rules/pages.md` passa a ser verdade.
- **Riscos**: rotacionar a `APP_KEY` torna os quatro ilegíveis. Comportamento normal de valor
  cifrado no Laravel, e o `catch (Throwable)` do provider garante que isso derrube a leitura da
  configuração, não a aplicação.

### Referências

- `vendor/spatie/laravel-settings/src/SettingsConfig.php:57-59,84-87`
- `vendor/spatie/laravel-settings/src/SettingsMapper.php:67,92`
- `vendor/spatie/laravel-settings/src/Support/Crypto.php:8-12`
- `.ai/rules/pages.md`, `.ai/rules/settings.md`

---

## ADR-07: Uma seção colapsável por provedor, com os campos abrindo no toggle

**Status**: Aceita
**Data**: 2026-08-24

### Contexto

RQ-05 e RQ-06 pedem que ligar a opção **abra** os campos de configuração, para todos os
provedores. A aba "Login" tinha 4 campos (toggle + 2 credenciais + rodapé). Com três provedores
novos são 9 campos a mais, num total de 13.

Treze campos soltos numa aba, nove deles condicionais a um toggle acima, é uma lista em que não se
acha nada — e a condicionalidade fica invisível, porque o campo que aparece empurra os outros para
baixo sem indicar de quem ele é.

### Decisão

Uma `Section` por provedor, colapsável, rotulada com o nome do provedor, contendo o toggle
`->live()` e as duas credenciais com `->visible(fn (Get $get) => $get(…habilitado))`. O rodapé fica
fora das seções, porque não é de provedor nenhum.

As seções são geradas por um `foreach` sobre `ProvedorSocial::cases()` — o método
`secaoDoProvedor(ProvedorSocial $p): Section` é escrito uma vez. Provedor novo aparece na tela sem
tocar na tela.

`Section` precisa de `->columnSpanFull()` explícito: `Grid`, `Section` e `Fieldset` não ocupam
todas as colunas por default no Filament 5.

### Alternativas Consideradas

1. **Um `Tabs` aninhado, uma sub-aba por provedor** — abas dentro de abas; a pessoa perde onde
   está, e `persistTabInQueryString()` de dois níveis colide.
2. **Um `Repeater` de provedores** — provedor não é dado do usuário, é caso de enum. Repeater
   deixaria acrescentar e remover linhas que não correspondem a nada.
3. **Manter os campos soltos** — recusada acima.

### Consequências

- **Positivas**: a aba cresce por seção, não por campo; o próximo provedor é uma linha no enum.
- **Negativas**: quatro seções colapsadas exigem um clique a mais para chegar às credenciais.
  Aceito: a aba é de configuração raríssima, e a seção diz de quem é o campo.

---

## ADR-08: Um blade que percorre os provedores disponíveis, e um SVG por partial

**Status**: Aceita
**Data**: 2026-08-24

### Contexto

RQ-03 pede o ícone da marca de cada provedor. O ADR-04 da wiki do Google decidiu SVG inline,
porque Heroicons não tem logo de marca e o kit não ganha um pacote de ~3.000 ícones para usar um.
A instrução desta entrega repete: "Não adicione dependência de ícones."

Com quatro provedores, quatro SVGs no mesmo arquivo do botão fariam um blade de ~120 linhas em que
a lógica desaparece entre os elementos de desenho.

### Decisão

`resources/views/filament/auth/botoes-sociais.blade.php` percorre
`ConfiguracaoDoLogin::disponiveis()` e traz, por inclusão, `filament.auth.icones.{icone}` de cada
provedor. O divisor "ou" é renderizado **uma vez**, antes do laço, e só quando há pelo menos um
provedor disponível.

O `botao-google.blade.php` é **removido**: um blade específico ao lado do genérico é a segunda
fonte da verdade que o kit já pagou uma vez (`.ai/rules/config.md`, "uma pergunta, uma dona").

O `aria-label` continua `"Entrar com {rotulo}"` — a forma exata que o CT-B do Google já asserta
(`tests/Browser/LoginSocialGoogleTest.php:61`), então aquele caso segue verde sem edição.

**Atenção de escrita** (`.ai/rules/views.md`): comentário de blade **não** pode conter diretiva
escrita por extenso com arroba — o compilador processa a diretiva antes de remover o comentário, e
a menção vira código. Nos comentários, "por inclusão".

### Consequências

- **Positivas**: um arquivo com a lógica, quatro arquivos com só o desenho da marca.
- **Negativas**: quatro inclusões por render da tela de login. Custo de view compilada,
  irrelevante.

---

## ADR-09: O que esta entrega deliberadamente NÃO mexeu

**Status**: Aceita
**Data**: 2026-08-24

Registro explícito para o quality gate não acusar omissão, e para a próxima wiki não refazer a
investigação:

1. **`->stateless()` continua fora**, nos quatro provedores. O `state` de CSRF é do Socialite e
   fica ligado (`src/Two/AbstractProvider.php:83,236-237`). E há um motivo novo, específico do X:
   `TwitterProvider::getCodeFields()` (`:116-125`) manda a **string literal** `'state'` quando
   stateless — desligar ali é pior que nos outros.
2. **PKCE no X é do provider, não nosso.** `TwitterProvider.php:22` liga `$usesPKCE = true`. Nada
   a configurar.
3. **Os escopos default bastam nos três novos** — GitHub `['user:email']` (`GithubProvider.php:16`),
   LinkedIn OpenID `['openid','profile','email']` (`LinkedInOpenIdProvider.php:14`), X
   `['users.read','users.email','tweet.read']` (`TwitterProvider.php:15`). Nenhuma chave `scopes`
   em `config/services.php`. Cuidado registrado: `scopes()` **soma** aos defaults
   (`AbstractProvider.php:396-401`) e só `setScopes()` substitui (`:409-414`) — um
   `'scopes' => [...]` na config passa por `scopes()` (`SocialiteManager.php:254`), logo soma; mas
   um `setScopes(['read:user'])` tiraria `user:email` e o GitHub deixaria de trazer e-mail
   verificado **em silêncio**, porque o gate em `GithubProvider.php:47` lê a propriedade crua.
4. **`registro_verificar_email` continua fora do Settings** — chave de boot, ver
   `.ai/rules/settings.md`. Nada nesta entrega muda isso.
5. **O vínculo continua por e-mail**, sem coluna `provider_id`. ADR-07 da wiki do Google.
6. **O driver `twitter` (OAuth 1.0) não é oferecido** — o `One\TwitterProvider` não põe o e-mail
   nem no payload bruto (`src/One/TwitterProvider.php:23`), então a barreira de verificação não
   tem onde encostar.
