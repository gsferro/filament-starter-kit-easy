---
title: Login social
parent: Autenticação
grand_parent: Português
nav_order: 3
---

# Login social: quatro provedores (opt-in, um por um)

Um segundo caminho de entrada, ao lado da senha: os botões **Entrar com…** abaixo do formulário de
login dos três painéis. Cada provedor nasce **desligado**, é ligado **individualmente**, e ligado
faz uma coisa só — autenticar quem **já tem conta**.

| Provedor | Driver do Socialite | URI de redirecionamento | Como o kit confirma o e-mail verificado |
|---|---|---|---|
| **Google** | `google` | `/auth/google/callback` | campo `email_verified` no payload |
| **GitHub** | `github` | `/auth/github/callback` | o Socialite já entrega só o e-mail `primary` + `verified`, ou nada — a presença é a prova |
| **LinkedIn** | `linkedin-openid` | `/auth/linkedin-openid/callback` | campo `email_verified` do userinfo OpenID |
| **X** (antigo Twitter) | `x` | `/auth/x/callback` | o X só devolve `confirmed_email` — a presença é a prova |

**Facebook e Discord ficaram de fora**, e não por esquecimento. A seção
[Facebook e Discord: por que não estão aqui](#facebook-e-discord-por-que-não-estão-aqui) explica o
que faltaria para incluí-los.

## O que o login social faz, e o que ele deliberadamente não faz

Vale para os **quatro** provedores, sem exceção:

| | |
|---|---|
| **Autentica** quem já tem conta com o e-mail que o provedor devolve | ✅ sempre, quando ligado |
| **Cria** conta para quem não tem | ❌ só com o registro aberto ligado, que nasce desligado |
| Aceita e-mail **não verificado** no provedor | ❌ nunca — recusa e registra o motivo |
| Contorna o **segundo fator** | ❌ nunca — quem tem 2FA confirmado cai no desafio igual |
| Guarda token de acesso ou `refresh_token` | ❌ nada é gravado |
| Guarda a **identidade** no provedor (`sub`) | ✅ em `vinculos_sociais` — é por ela que a conta é reconhecida da segunda vez em diante ([detalhes](#vínculo-com-o-provedor-a-primeira-vez-e-as-seguintes)) |
| Cria coluna nova em `users` | ✅ **uma**, `origem` — só diz por qual porta a conta entrou (`google`, `github`, `convite`, `registro`, `interno`), exibida na lista de usuários e no dashboard. **Não é vínculo**: nada de id do provedor nem token; o vínculo continua sendo o e-mail verificado |
| Marca a conta criada como **e-mail verificado** | ✅ sim — o provedor já provou, e pedir de novo seria a mesma prova duas vezes |

A linha que mais importa é a segunda, e ela não é timidez: **o convite é a única porta de entrada
do kit**. O exemplo que a documentação do Laravel Socialite dá para o callback é
`User::updateOrCreate()` — copiado para cá, ele transformaria qualquer pessoa com uma conta em
**qualquer** um dos provedores em usuária do sistema, contornando convite, verificação e atribuição
de papel. Isso é furo de autorização, não conveniência. Se você **quer** cadastro por login social,
ligue o registro aberto: o kit passa a criar a conta e a levar a pessoa para a tela do perfil dela,
onde ela completa o que falta.

E lembre do resto do kit: **conta sem papel não abre painel nenhum** (`User::canAccessPanel()`).
Quem entra por login social precisa de papel como qualquer outra pessoa — a conta criada pelo
registro aberto recebe o papel único dele, pela mesma porta do formulário.

**A conta criada pelo provedor não tem senha que a pessoa conheça** (nasce com uma aleatória), e
três coisas pedem a senha atual: trocar a senha, ligar o 2FA e desbloquear a sessão. Por isso o
perfil (`/app/meu-perfil`, e o dos outros dois painéis) tem o bloco **Definir senha por e-mail**:
ele envia o mesmo link do "Esqueceu a senha?", encerra a sessão — a página que define a senha só
abre para quem está fora — e, com a senha definida, os três passam a funcionar. Medido numa
instalação real: era o primeiro tropeço de quem entrava pelo Google. E quem escolhe **viver sem
senha local** não fica preso na tela de bloqueio de sessão: ela oferece os mesmos botões do
login, e a volta do provedor destrava.

## Ligando um provedor, em quatro passos

O roteiro é o mesmo para os quatro; só muda onde se cria o app OAuth. Você pode fazer tudo pelo
`.env` **ou** pela tela `/admin/configuracoes-do-kit` → aba **Login** — mas saiba quem manda: **o
banco vence o `.env` em tempo de execução, e o `.env` só semeia** (ver
[Quem manda: o banco ou o `.env`?](../recursos/configuracoes-do-kit.md#quem-manda-o-banco-ou-o-env)). O passo 3 é onde isso pesa.

**1. Crie o app OAuth no provedor** e cadastre a URI de redirecionamento — que é o seu `APP_URL`
mais o caminho da tabela acima:

| Provedor | Onde criar | O que pedir lá |
|---|---|---|
| **Google** | [console.cloud.google.com](https://console.cloud.google.com) → *APIs e serviços* → *Credenciais* → *ID do cliente OAuth*, tipo **Aplicativo da Web** | nada além do padrão |
| **GitHub** | [github.com/settings/developers](https://github.com/settings/developers) → *OAuth Apps* → *New OAuth App* | nada a marcar; o kit pede o escopo `user:email` no código, e é ele que permite confirmar a verificação |
| **LinkedIn** | [linkedin.com/developers](https://www.linkedin.com/developers) → *Create app* → aba *Products* | **habilite o produto _Sign In with LinkedIn using OpenID Connect_**. Sem ele o provedor não devolve `email_verified`, e o kit recusa todo login |
| **X** | [developer.x.com](https://developer.x.com) → *Projects & Apps* → *User authentication settings* | tipo **Web App**, **OAuth 2.0**, e os escopos `users.read` e `users.email` |

Exemplo de URI a cadastrar:

```text
https://seu-dominio.com.br/auth/github/callback
http://localhost:8000/auth/github/callback     # para desenvolvimento
```

Esse caminho não é escolha: ele está em `config/services.php` como caminho **relativo**, de
propósito, para acompanhar o `APP_URL` de cada ambiente sem uma variável a mais para esquecer.

**2. Escreva as três chaves do provedor no `.env`:**

```dotenv
# Google
KIT_SOCIALITE_GOOGLE=true
GOOGLE_CLIENT_ID=1234567890-abc.apps.googleusercontent.com
GOOGLE_CLIENT_SECRET=GOCSPX-seu-segredo

# GitHub
KIT_SOCIALITE_GITHUB=true
GITHUB_CLIENT_ID=Iv1.abc123
GITHUB_CLIENT_SECRET=seu-segredo

# LinkedIn (driver linkedin-openid)
KIT_SOCIALITE_LINKEDIN=true
LINKEDIN_CLIENT_ID=86abc123
LINKEDIN_CLIENT_SECRET=seu-segredo

# X (antigo Twitter)
KIT_SOCIALITE_X=true
X_CLIENT_ID=seu-client-id
X_CLIENT_SECRET=seu-segredo
```

**3. Leve as chaves ao banco.** Numa instalação **nova** o `migrate` faz isso sozinho — a
migration de settings semeia cada propriedade do `config()`, que vem do `.env`. Num kit **já
instalado**, o `.env` sozinho **não** liga nada, e `config:clear` não muda isso: a tabela
`settings` já tem a linha (`false`, credencial vazia) e ela vence em todo request. Aí são dois
caminhos: gravar pela tela `/admin/configuracoes-do-kit` → **Login**, ou
`php artisan kit:install --force` com o `.env` já preenchido — que **recria o banco** (APAGA os
dados; inócuo só no minuto seguinte à instalação). Medido numa instalação real: o `.env` com as
três chaves do Google, `config:clear`, e nenhum botão — até a migration reler o `.env`.

**4. Confirme que o botão apareceu.** Se não apareceu, é uma das duas condições abaixo.

> **Pela tela, em vez do `.env`**: em `/admin/configuracoes-do-kit` → **Login** há uma seção por
> provedor. Ligar o interruptor **abre** os campos de *Client ID* e *Client Secret* daquele
> provedor — e só dele. O *Client Secret* é guardado **cifrado**, nunca é exibido de volta e não
> aparece no código-fonte da página; deixar o campo em branco **mantém** o que estava gravado.

## As telas

| | |
|---|---|
| [![Login com os botões sociais](https://raw.githubusercontent.com/gsferro/filament-starter-kit-easy/main/art/thumbs/login-social.png)](https://raw.githubusercontent.com/gsferro/filament-starter-kit-easy/main/art/login-social.png) | [![Aba Login das configurações do kit](https://raw.githubusercontent.com/gsferro/filament-starter-kit-easy/main/art/thumbs/admin-configuracoes-login.png)](https://raw.githubusercontent.com/gsferro/filament-starter-kit-easy/main/art/admin-configuracoes-login.png) |
| A tela de login com **Entrar com Google** e **Entrar com GitHub**, e o rodapé em Markdown | `/admin/configuracoes-do-kit` → **Login**: um bloco fechado por provedor com o ícone de status, o interruptor do vínculo e o rodapé |
| [![Definir senha por e-mail, no perfil](https://raw.githubusercontent.com/gsferro/filament-starter-kit-easy/main/art/thumbs/app-perfil-definir-senha.png)](https://raw.githubusercontent.com/gsferro/filament-starter-kit-easy/main/art/app-perfil-definir-senha.png) | [![Tela de bloqueio com login social](https://raw.githubusercontent.com/gsferro/filament-starter-kit-easy/main/art/thumbs/app-bloqueio-social.png)](https://raw.githubusercontent.com/gsferro/filament-starter-kit-easy/main/art/app-bloqueio-social.png) |
| O perfil: **Definir senha por e-mail** acima de "Senha" — quem entrou pelo provedor não tem senha atual | A tela de bloqueio de sessão oferece os mesmos botões; a volta do provedor destrava |
| [![Lista de usuários com a coluna Origem](https://raw.githubusercontent.com/gsferro/filament-starter-kit-easy/main/art/thumbs/admin-users-origem.png)](https://raw.githubusercontent.com/gsferro/filament-starter-kit-easy/main/art/admin-users-origem.png) | |
| `/admin/users`: a coluna **Origem** diz por qual porta cada conta entrou (Google, GitHub, Convite, Registro aberto, Interno) | |

## Vínculo com o provedor: a primeira vez, e as seguintes

A pergunta que motivou esta seção: *"eu poderia criar uma conta no Google com o e-mail de outra
pessoa e entrar na conta dela?"* **Não** — e vale entender por quê antes de ler o que o kit faz a
mais.

O kit só aceita e-mail que o provedor declara **verificado** (tabela acima). Google, GitHub,
LinkedIn e X só marcam um e-mail como verificado depois de mandar um código ou link **para aquela
caixa postal**. Então quem consegue uma identidade "verificada" com o e-mail de outra pessoa já
controla a caixa postal dela — e quem controla a caixa postal já entra pelo **"Esqueceu a senha?"**
do próprio kit. O login social não abre uma porta que não existisse: ele aceita a mesma prova. É o
modelo que o Auth0 chama de *trusted providers*.

Sobram dois riscos residuais, e são eles que o vínculo endereça: um **endereço reciclado** pelo
provedor de correio (o novo dono verifica o endereço no Google e chegaria à conta do antigo — mas
também resetaria a senha dele), e um **bug ou comprometimento do provedor OAuth**.

**O vínculo.** Toda entrada social grava, na tabela `vinculos_sociais`, a identidade da pessoa
**no provedor** — o `sub`, o id da conta lá, estável mesmo quando o e-mail muda — junto da conta do
kit. Nada de token: é reconhecimento, não credencial. Da segunda entrada em diante a conta é
reconhecida **pelo vínculo, antes de olhar o e-mail**: uma troca de e-mail no provedor, ou um
endereço reciclado, não leva a outra conta.

**A primeira vez.** Quando um provedor aparece pela primeira vez numa conta que **já existia**, o
que acontece depende de um interruptor — `KIT_SOCIALITE_VINCULO_CONFIRMAR` no `.env`, ou a tela
`/admin/configuracoes-do-kit` → **Login** → "Exigir confirmação por e-mail…":

| | Modo padrão (`false`) | Modo estrito (`true`) |
|---|---|---|
| Conta existe, primeira vez deste provedor | vincula, **entra**, e envia o e-mail *"sua conta foi acessada pelo Google pela primeira vez — não foi você? troque a senha e avise quem administra"* | **não entra**; envia o e-mail *"confirme a entrada pelo Google"* com um link assinado de **30 minutos**; ao abri-lo, o vínculo nasce e a sessão começa |
| Conta existe, já vinculada | entra pelo vínculo, sem e-mail | idem |
| Conta não existe, registro aberto ligado | cria pela porta do registro aberto e já nasce vinculada — não há conta anterior a proteger | idem |
| Conta não existe, registro fechado | recusa ("o acesso é por convite") | idem |

O aviso do modo padrão é **detecção**: torna o risco residual visível para a própria pessoa. O modo
estrito é **prevenção**: exige a prova (a caixa postal) no exato momento em que ela importa. O link
de confirmação vale só para aquela conta e aquela identidade, é assinado, expira, e se a identidade
já for de **outra** conta a confirmação recusa — uma identidade de provedor pertence a uma conta só.

> Os dois e-mails vão pela **fila** (`ShouldQueue`, como o convite). Sem um worker rodando nada sai
> — `composer dev` sobe um. Na validação real isso foi o primeiro tropeço: o aviso "link enviado"
> apareceu, e o e-mail ficou na tabela `jobs` até o `queue:work`.

**Cadastro pelo provedor, a partir da tela de registro.** `/app/register` mostra os mesmos botões
(com o registro aberto ligado), e o clique carrega o contexto da tela até a volta do OAuth: com a
multi-organização, `/app/register?org=acme` cria a conta **na `acme`**, com o papel do registro
aberto nela — a mesma porta do formulário, com as mesmas recusas (organização inexistente ou
fechada). A partir do **link de um convite** (`?token=`), entrar pelo provedor **cria a conta pelo
convite**: ela nasce com a organização e o papel do convite, e o convite é consumido — desde que o
e-mail verificado do provedor seja o e-mail convidado; se for outro, recusa e o convite fica intacto.
Sem senha em nenhum dos casos: o provedor provou o e-mail. Conta nova **sem** `?org=` na
multi-organização continua recusada.

Conta **existente** entra normalmente em qualquer modo, mas **não consome convite** nesse caminho: o
`?token=` viaja numa rota GET pública sem CSRF, e com SSO silencioso do provedor o aceite aconteceria
sem clique da pessoa. Quem já tem conta aceita o convite na tela autenticada **Convites recebidos**,
que exige o dono e pede confirmação. Auditoria e decisão em
`wikis/specs/feat/travas-de-escalada-de-papeis/` (F-03 e F-04).
Decisões e casos: `wikis/specs/feat/cadastro-social-por-convite-e-organizacao/`.

Decisões e casos: `wikis/specs/feat/vinculo-de-provedor-social/`.

## O botão só aparece com TUDO preenchido — e por provedor

São **duas** condições, em conjunção, e elas falham por motivos diferentes:

- o interruptor daquele provedor ligado — desligado é escolha de quem instalou;
- o `client_id`, o `client_secret` e o `redirect` **todos preenchidos** — credencial vazia é
  descuido de quem configurou.

Interruptor ligado com o `client_secret` vazio mantém o botão fora do ar, e é de propósito: botão
que leva a um OAuth inexistente é uma promessa que a tela não pode cumprir.

**E o desligamento derruba a ROTA, não só o botão.** Com um provedor indisponível,
`/auth/{provedor}/redirect` e `/auth/{provedor}/callback` respondem **404** — e só os dele, os
outros seguem no ar. Esconder o botão não seria barreira nenhuma: a URL é fixa, pública e conhecida.

**Provedor fora da lista responde 404 sem nem chegar ao controller.** `/auth/facebook/callback`,
`/auth/discord/callback` ou qualquer outro segmento devolvem 404 porque o parâmetro da rota é
tipado como `App\Support\ProvedorSocial` — a lista branca é o próprio enum, e o roteador a consulta.

Cada interruptor também **falha fechado**: `false`, `0`, `off`, `no`, vazio e qualquer valor
irreconhecível o mantêm desligado. Só `true` e `1` ligam.

## O rodapé da tela de login

A mesma configuração traz um rodapé em Markdown (negrito, itálico, link; HTML cru é descartado) na base da tela de login dos três painéis:

```dotenv
KIT_LOGIN_RODAPE="Acme · Todos os direitos reservados"
```

Vazio (ou só espaços) = sem rodapé, sem faixa vazia.

É **texto, não HTML**, e o valor sai escapado. A tela de login é pública e não autenticada: HTML
cru vindo de um campo editável ali seria XSS armazenado com o pior alcance possível — a tela por
onde todo mundo entra. Se você precisar de link no rodapé, o caminho é um campo estruturado
(texto + URL, com validação), não um campo de HTML solto.

## E-mail não verificado: por que recusamos, e como cada provedor prova

O vínculo com a conta do kit é feito **pelo e-mail**, comparado sem diferenciar caixa nem espaços
nas bordas. Isso é simples e não custa coluna nova — mas tem um risco conhecido: se o provedor
devolvesse um e-mail **não verificado**, bastaria criar uma conta naquele provedor com o e-mail de
outra pessoa para entrar na conta dela. Com o registro do kit fechado — o default — esse é
justamente o caminho principal, não um caso de borda.

Então o kit **exige prova positiva** em todos os provedores. Ausente, falsa, ou com um valor que
não seja claramente verdadeiro ⇒ **recusa**, com aviso na tela e o motivo `email_nao_verificado` no
log. Falha fechado, sempre.

O que muda de provedor para provedor é **onde** a prova está, e a diferença é grande:

- **Google** — informa `email_verified` no payload (e um alias `verified_email`). O kit lê e exige
  verdadeiro.
- **LinkedIn** — o userinfo do OpenID Connect traz `email_verified`. É por isso que o kit usa o
  driver `linkedin-openid` e não o `linkedin` legado: o legado **não informa verificação nenhuma**,
  e os escopos dele foram descontinuados pela própria LinkedIn.
- **X** — não tem campo de verificação, porque não precisa: o X só devolve `confirmed_email`, ou
  seja, um endereço que ele já confirmou. **A presença do e-mail é a prova.** Se o X não devolver
  e-mail (app sem o escopo `users.email`, ou conta sem endereço confirmado), o kit recusa com o
  motivo `email_ausente`.
- **GitHub** — a presença também, e por um caminho que vale entender antes de mexer nos escopos.
  O Socialite consulta `/user/emails`, escolhe a entrada `primary` **e** `verified`, e
  **sobrescreve** o e-mail com ela — ou com **nada**, se a consulta falhar ou se nenhuma entrada
  casar. Não há campo de verificação no payload, e não precisa: e-mail preenchido já significa
  `primary` + `verified`, e e-mail vazio cai na recusa por `email_ausente`.

  Isso depende de **uma** condição: o escopo `user:email`. Ele é o default do driver, e a chave
  `scopes` de `config/services.php` **soma** aos defaults em vez de substituí-los, então
  acrescentar escopos é seguro. O que quebraria a garantia é um `setScopes()` no código do kit
  removendo `user:email` — aí o GitHub passaria a entregar o e-mail do **perfil público**, não
  verificado, em silêncio. Há caso de teste guardando exatamente isso; não desligue esse escopo.

  > Versões anteriores desta seção diziam que o kit refazia a consulta a `/user/emails` por conta
  > própria. Ele fazia, e era redundante: a leitura do código do Socialite que justificava a
  > chamada estava errada. A chamada foi removida — o kit **não** chama API de provedor nenhuma.

Consequência a conhecer, em todos: se a pessoa **trocar o e-mail** na conta do provedor, o vínculo
se perde e ela volta a entrar por senha.

## Limitação conhecida: o destino é sempre o painel `/app`

Os botões aparecem nas telas de login dos **três** painéis, porque o render hook é único. Mas quem
entra por login social cai sempre no `/app`, mesmo tendo clicado em `/admin/login` ou
`/infra/login` — e uma recusa também volta para o login do `/app`.

Não é furo de segurança: a pessoa é autenticada e o papel dela continua governando o que ela
alcança. É atrito de navegação, e está registrado como limitação aceita porque guardar o painel de
origem entre a ida e a volta do OAuth é feature nova, não conserto desta. Quem administra e quem
opera infra normalmente entra por senha; o login social existe para o caminho do `/app`.

## Facebook e Discord: por que não estão aqui

O requisito original pedia os dois. Nenhum entrou, e cada um por um motivo diferente.

**Facebook — não há como confirmar o e-mail.** O Socialite tem o driver, e ele funciona; o que não
existe é um campo que afirme que **aquele endereço** foi confirmado. O `verified` que o provider
pede é de nível de **conta**, legado, e ausente na versão da Graph API que ele usa; o caminho
OIDC/Limited Login devolve claims sem `email_verified`. Aceitar o Facebook faria o nível de garantia
do seu login depender de **qual botão a pessoa clicou** — e o botão mais fraco seria o vetor. Se
você aceitar esse risco conscientemente, o que falta é: um caso em `App\Support\ProvedorSocial` com
o ramo de verificação declarando a premissa, o bloco em `config/services.php` (chave `facebook`) e
em `config/kit.php`, e as três propriedades no Settings. **Leia o ADR-05 antes** — ele lista as
alternativas que foram consideradas e por que cada uma é pior.

**Discord — não é driver do Socialite.** A documentação oficial suporta Facebook, X, LinkedIn,
Google, GitHub, GitLab, Bitbucket e Slack; o resto vem do catálogo comunitário
[socialiteproviders.com](https://socialiteproviders.com). Incluí-lo exige
`composer require socialiteproviders/discord` **e** o registro de um listener de `SocialiteWasCalled`
— uma dependência nova e um segundo mecanismo de extensão. O kit não adiciona dependência por
conta própria; se você quiser, o caminho é esse mais um caso no enum (o Discord expõe um campo
`verified` no payload, então a barreira tem onde encostar).

## Onde ficam os registros

Tudo no channel **`autenticacao`** (`storage/logs/autenticacao-*.log`), no mesmo formato do resto
do kit — `[Classe@Método] mensagem | chave: valor`, com **e-mail mascarado**, o `provedor` em todas
as linhas e um `motivo` legível em cada recusa:

| `motivo` | O que aconteceu |
|---|---|
| `falha_no_provedor` | `state` de CSRF inválido, rede fora, ou credencial recusada pelo provedor |
| `email_ausente` | o provedor não devolveu e-mail (no X, é o caso de escopo faltando) |
| `email_nao_verificado` | o e-mail não está verificado no provedor |
| `conta_inexistente_registro_fechado` | não há conta e o registro aberto está desligado |
| `conta_criada_por_login_social` | conta nova criada (registro aberto ligado) |

Nenhum **`client_secret` aparece** — não em log, não em tela, não em mensagem de erro, e não no
HTML da tela de configuração. E as mensagens devolvidas ao visitante são propositalmente genéricas:
dizer qual barreira reprovou é entregar informação de reconhecimento a quem estiver sondando. O
motivo fica no log, para você.

O login social também entra na **trilha de acesso** do painel `/infra` (quem entrou, quando, de
onde), como qualquer outro login — sem configuração nenhuma.

## O segredo do Google ficou em claro na trilha de auditoria até a v0.19.3

**Se você configurou o `GOOGLE_CLIENT_SECRET` pela tela `/admin/configuracoes-do-kit` em alguma
versão entre a 0.19.2 e a 0.19.3, rotacione esse segredo no console do Google.**

O motivo: a máscara de segredo da trilha de auditoria decide o que esconder consultando a lista
`ConfiguracoesDoKit::encrypted()`, e o `client_secret` do Google estava fora dessa lista. Então
cada gravação pela tela escreveu o valor **em claro** nas colunas `old_values`/`new_values` da
tabela `audits` — e a tela de auditoria exibe essas colunas para leitura.

O que esta versão faz por você:

- **corrige a lista**, o que fecha o vazamento daqui para a frente nos quatro segredos de provedor
  e na senha de SMTP, de uma vez (uma lista, três consumidores: o decifrador da leitura, o
  cifrador da gravação e a máscara da trilha);
- **mascara o que já está gravado**, numa migration que substitui o valor pela mesma máscara que a
  trilha usa hoje. A linha da trilha é preservada — quem alterou, quando e de onde continua
  registrado; sai só o valor que nunca deveria ter entrado;
- **avisa no log** (channel `configuracoes`) quantas linhas foram mascaradas, com a instrução de
  rotacionar.

Mascarar a trilha **não desfaz** o fato de o valor ter estado legível. Por isso a rotação é sua, e
é o único passo que o kit não pode fazer no seu lugar.

## Acrescentando o próximo provedor

O kit **tem** abstração de provedor agora, e ela é um enum: `App\Support\ProvedorSocial`. A decisão
foi tomada com quatro casos na mão, não com um — o que revelou que o eixo a abstrair não era o
redirect nem o botão (idênticos em todos), mas a **verificação de e-mail** (radicalmente diferente
em cada um).

O roteiro do quinto provedor, e é curto de propósito:

1. um caso novo no enum, com o `value` = **nome do driver do Socialite** (esse mesmo valor é o
   segmento da URL e a chave nos dois arquivos de config), mais os ramos de `rotulo()`, `icone()` e
   **`emailVerificado()`**;
2. um bloco em `config/services.php` e um em `config/kit.php` → `login`, e as chaves no
   `.env.example`;
3. três propriedades em `App\Settings\ConfiguracoesDoKit`, a linha de cada uma em
   `mapaDeConfiguracao()`, o `client_secret` em **`encrypted()`**, e o par `add`/`addEncrypted` numa
   migration nova em `database/settings/`;
4. uma partial de SVG em `resources/views/filament/auth/icones/`.

**Nenhum arquivo de lógica muda**: as rotas, o controller, o blade dos botões e a aba Login da tela
de Settings percorrem `ProvedorSocial::cases()`. E o `match` exaustivo de `emailVerificado()`
**cobra** o ramo novo — esquecê-lo não compila em análise estática.

**Se o provedor não permitir confirmar a verificação do e-mail, essa é uma decisão de arquitetura,
não um `?? true`.** Foi o que tirou o Facebook da lista. Registre a escolha antes de escrever o
ramo.

> O raciocínio completo, com as alternativas recusadas e o `file:line` do `vendor/` de cada
> afirmação sobre o Socialite, está em
> `wikis/specs/feat/mais-provedores-sociais/mais-provedores-sociais/`. A decisão anterior — a de
> **não** abstrair, com um provedor só — está em
> `wikis/specs/feat/login-social-google/login-social-google/`, ADR-10.

