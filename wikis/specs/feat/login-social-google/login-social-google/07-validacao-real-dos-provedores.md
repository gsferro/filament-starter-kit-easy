# Validação real dos provedores — quatro apps OAuth de verdade, ligados um a um

> Rodada de validação, não feature: nenhuma lógica nova foi planejada. É a mesma natureza da
> `06-matriz-de-instalacoes.md` da wiki `aderencia-ao-blueprint` — instalação nova do pacote
> publicado, e o que se mede é o comportamento real, no terminal e no navegador. O que a medição
> achou de defeito virou fix com teste, no mesmo padrão daquela rodada.

## Fonte

- **Origem**: pedido do solicitante no chat, em duas mensagens (2026-08-26)
- **Autor / solicitante**: gsferro (dono do kit)
- **Fidelidade**: alta (texto escrito)

### Texto original

<!-- IMUTÁVEL. -->

> - vamos criar os providers para testar o login social em uma nova instalação na pasta que voce tem acesso:"D:\PROJECTS\PACOTES\FILAMENTS\STARTER-KIT-EASY\TESTES KIT".
> - vamos testar todos os providers disponiveis, vamos ligar 1 a 1 e ver o comportamento real.
> - crie uma /feature-wiki se julgar necessário
> - primeiro, faça uma nova instalação na pasta e vamos começar com o google e depois seguir a lista que esta no @readme
> - me de o passo-a-passo para criar a chave

> meu email: "kit:admin --email=gsferroti@gmail.com"

### Leitura

Quatro provedores, na ordem do README: **Google, GitHub, LinkedIn (`linkedin-openid`), X**.
Cada um ligado sozinho, com app OAuth real criado pelo solicitante, e observado nas duas
superfícies: o terminal (banco, rotas, logs) e o navegador do solicitante (o consentimento no
provedor exige a conta dele — não há como automatizar sem a senha dele, e não se deve).

A decisão de **não** abrir wiki própria: a skill `feature-wiki` diz para não invocá-la quando a
mudança não adiciona lógica nem cria código. Aqui o plano era medir. O arquivo mora na wiki do
login social porque é dela que o comportamento medido deriva.

## Instalação

| | |
|---|---|
| Pasta | `TESTES KIT/social` |
| Comando | `composer create-project gsferro/starter-kit-easy social --no-interaction --prefer-dist` |
| Versão instalada | `0.21.1` (o pacote publicado, não o checkout) |
| Banco | SQLite, `APP_URL=http://localhost:8000`, `php artisan serve --port=8000` |
| Registro aberto | desligado (default) — é o cenário que o README chama de "só autentica quem já tem conta" |
| URI de redirecionamento | `http://localhost:8000/auth/{driver}/callback`, a mesma para os quatro, mudando só o driver |

### Estado base, antes de ligar qualquer provedor (terminal)

| Medição | Resultado |
|---|---|
| `/app/login`, `/admin/login`, `/infra/login` | 200, **zero** botões "Entrar com…" |
| `/auth/{google,github,linkedin-openid,x}/redirect` | **404** os quatro — provedor desligado derruba a rota, não só o botão |
| `/auth/{facebook,discord}/redirect` | **404** — fora do enum, não chegam ao controller |

## Como cada provedor foi ligado — e o que isso ensinou

O README oferecia dois caminhos, "`.env` **ou** tela". A rodada usou o `.env` no Google de
propósito, para medir o caminho que nenhuma suíte exercita numa instalação de verdade. O resultado
é o achado nº 1 abaixo. Os provedores seguintes vão pela tela `/admin/configuracoes-do-kit` →
**Login**, para cobrir o outro caminho.

## Google

### Criação da chave (roteiro passado ao solicitante, e seguido por ele)

`console.cloud.google.com` → projeto novo → **Google Auth Platform** → *Começar* (nome do app,
e-mail de suporte, público **Externo**) → **Clientes** → *Criar cliente*, tipo **Aplicativo da
Web**, origem `http://localhost:8000`, redirecionamento `http://localhost:8000/auth/google/callback`
→ copiar ID e chave secreta → **Público-alvo** → *Usuários de teste* → adicionar o Gmail que vai
logar (sem isso: `403 access_denied` na tela de consentimento). Escopos: os três padrão
(`openid profile email`), que são os que o driver do Socialite pede.

### Terminal

| # | Passo | Esperado | Medido |
|---|---|---|---|
| G1 | `KIT_SOCIALITE_GOOGLE=true` + `GOOGLE_CLIENT_ID/SECRET` no `.env`, `config:clear` | README (passos 2–4): botão aparece | **nenhum botão**, rota `404`. `config:show kit.login.google` → `habilitado: false`, `services.google.client_id: null`. O banco (`settings` semeado com `false`/`null` no `migrate`) sobrepõe a config em todo request. Com `DB_DATABASE=:memory:` (sem tabela) a mesma config lê `true` e o id — o `.env` estava certo, o banco vencia. **Achado nº 1a (doc)** |
| G2 | `kit:install --force --no-npm` com o `.env` preenchido | README (§ *Quem manda*): "o banco nasce igual ao `.env` novo" | banco novo com `login_google_habilitado = false`, `client_id = null`. **Achado nº 1b (código)** — ver abaixo |
| G3 | apagar o SQLite à mão, `migrate --seed` num processo **limpo** (sem tabela no boot) | settings semeado do `.env` | `habilitado = true`, `client_id` gravado, `client_secret` **cifrado** (`eyJ…`) ✅ |
| G4 | os três `/…/login` | botão só do Google | **"Entrar com Google"** nos três; GitHub/LinkedIn/X seguem `404` ✅ |
| G5 | `/auth/google/redirect` | 302 para o Google | `302` → `accounts.google.com/o/oauth2/auth` com `redirect_uri=http://localhost:8000/auth/google/callback`, `scope=openid profile email`, `response_type=code` ✅ |
| G6 | reproduzir G2 com o fix aplicado à instalação (os dois arquivos do PR #45 copiados por cima do 0.21.1) | banco novo igual ao `.env` | controle com o 0.21.1: `false`/`null` (reproduz). Com o fix: `true`, id, secret cifrado ✅ |

### Navegador (conta do solicitante: `gsferroti@gmail.com`)

Os três cenários que o README promete, na ordem em que um e-mail só consegue percorrê-los:

| # | Cenário | Esperado | Medido |
|---|---|---|---|
| GB1 | sem conta no kit, registro **fechado**, "Entrar com Google" | volta ao login com a recusa; nenhum usuário criado; linha na trilha | _pendente — aguardando o clique_ |
| GB2 | registro **aberto** (tela Settings), clicar de novo | conta criada com `email_verified_at` preenchido, redireciona para o perfil | _pendente_ |
| GB3 | conta existente, clicar de novo | entra direto no `/app` | _pendente_ |

## Achado nº 1 — `kit:install --force` semeava o settings do banco que acabou de apagar

**1a, documentação.** O passo 3 do roteiro do login social no README ("Limpe a config e
recarregue") era falso num kit instalado: o `.env` **semeia** o settings e depois perde para ele
em todo request — o próprio README diz isso vinte seções depois, em *Quem manda: o banco ou o
`.env`?*. Corrigido em PT e EN (o passo 3 agora nomeia os dois caminhos reais: a tela, ou o
`--force` com o `.env` preenchido) e no `.env.example`.

**1b, código.** O `--force` prometia "o banco nasce igual ao `.env` novo" e não cumpria. Causa: o
processo do comando sobe com o banco **velho** — `KitServiceProvider::boot()` aplica a tabela
`settings` à config antes de o comando rodar. O comando apaga o SQLite, migra, e a migration de
settings semeia o banco novo lendo `config()`, que ainda dizia o que o banco velho dizia. Só nome,
cor e admin escapavam, porque o `CustomizadorDaInstalacao::alinharConfigEmMemoria()` os reescreve
em memória depois das perguntas — e foi **por isso que a matriz P1 da rodada anterior passou**:
ela mediu justamente nome e cor. As outras dezenas de chaves do mapa (login social, e-mail,
tabelas, identidade) herdavam o banco apagado, em silêncio.

Correção (PR #45): `ConfiguracoesDoKit::devolverConfigAoEnv()`, o inverso de `aplicarNaConfig()`
— relê as chaves do mapa dos **arquivos** de config (é neles que mora a coerção de cada chave),
como se o banco não existisse. O `--force` chama antes das perguntas, para o customizador realinhar
por cima. Caso novo em `tests/Kit/ConfiguracoesDoKitTest.php` com uma chave de cada arquivo do
mapa e controle ("o banco venceu antes de ser desfeito"); mutação verificada (método vazio →
vermelho). Provado na instalação real (G6).

**O que NÃO foi mexido, e por quê.** `kit:tenancy --force` faz `migrate:fresh` no mesmo processo e
tem a mesma mecânica — o banco novo herda os settings do velho. Ali isso é o comportamento
desejável: quem liga a tenancy num kit já configurado não quer perder nome, cor e e-mail. Fica
registrado como observação, não como defeito.

## GitHub

_pendente — próximo da lista._

## LinkedIn (`linkedin-openid`)

_pendente._

## X

_pendente._

## O que a rodada NÃO cobre (declarado)

- Login social com **2FA** confirmado na conta — exige a conta do solicitante com TOTP ligado;
  coberto por `LoginSocialTest` com fake.
- Facebook e Discord — fora do enum por decisão registrada (ADR-04/05); só se mediu o `404`.
- Provedor com e-mail **não verificado** — exige conta real sem verificação no provedor; coberto
  pelo fake.
