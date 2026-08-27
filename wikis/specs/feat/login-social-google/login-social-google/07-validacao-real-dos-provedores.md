# Validação real dos provedores — apps OAuth de verdade, ligados um a um

> Rodada de validação, não feature: nenhuma lógica nova foi planejada de antemão. É a mesma
> natureza da `06-matriz-de-instalacoes.md` da wiki `aderencia-ao-blueprint` — instalação nova do
> pacote publicado, e o que se mede é o comportamento real, no terminal e no navegador do
> solicitante. O que a medição achou virou fix com teste (achados 1–5) ou feature com wiki própria
> (`vinculo-de-provedor-social`). Tudo na PR #45.

## Fonte

- **Origem**: pedido do solicitante no chat, em mensagens sucessivas (2026-08-26)
- **Autor / solicitante**: gsferro (dono do kit)
- **Fidelidade**: alta (texto escrito)

### Texto original (o pedido inicial)

<!-- IMUTÁVEL. -->

> - vamos criar os providers para testar o login social em uma nova instalação na pasta que voce tem acesso:"D:\PROJECTS\PACOTES\FILAMENTS\STARTER-KIT-EASY\TESTES KIT".
> - vamos testar todos os providers disponiveis, vamos ligar 1 a 1 e ver o comportamento real.
> - crie uma /feature-wiki se julgar necessário
> - primeiro, faça uma nova instalação na pasta e vamos começar com o google e depois seguir a lista que esta no @readme
> - me de o passo-a-passo para criar a chave

> meu email: "kit:admin --email=gsferroti@gmail.com"

> vamos pular o linkedin para o x (antigo twitter), pois é muito complexo e eu não irei usar, quem for fazer o login por ele que implemente e, caso precise corrigir algo, abra um PR para o kit

Os pedidos intermediários (ajustes de tela, "definir senha", bloqueio, origem, vínculo) estão
citados verbatim junto de cada achado.

## Instalação

| | |
|---|---|
| Pasta | `TESTES KIT/social` |
| Comando | `composer create-project gsferro/starter-kit-easy social --no-interaction --prefer-dist` |
| Versão instalada | `0.21.1` (o pacote publicado). Cada fix desta rodada foi **copiado por cima** na instalação para ser provado ali antes do commit |
| Banco | SQLite, `APP_URL=http://localhost:8000`, `php artisan serve --port=8000`, `MAIL_MAILER=log`, `QUEUE_CONNECTION=database` sem worker |
| URI de redirecionamento | `http://localhost:8000/auth/{driver}/callback` |

### Estado base, antes de ligar qualquer provedor (terminal)

| Medição | Resultado |
|---|---|
| `/app/login`, `/admin/login`, `/infra/login` | 200, **zero** botões "Entrar com…" |
| `/auth/{google,github,linkedin-openid,x}/redirect` | **404** os quatro — provedor desligado derruba a rota, não só o botão |
| `/auth/{facebook,discord}/redirect` | **404** — fora do enum, não chegam ao controller |

## Google — pelo `.env` (de propósito, para medir o caminho que nenhuma suíte exercita)

### Criação da chave (roteiro passado ao solicitante)

`console.cloud.google.com` → projeto novo → **Google Auth Platform** → *Começar* (nome, e-mail de
suporte, público **Externo**) → **Clientes** → *Criar cliente*, tipo **Aplicativo da Web**, origem
`http://localhost:8000`, redirecionamento `http://localhost:8000/auth/google/callback` → copiar ID e
chave secreta → **Público-alvo** → *Usuários de teste* → o Gmail que vai logar (sem isso, `403
access_denied` no consentimento). Escopos: os três padrão — os que o driver pede.

### Terminal

| # | Passo | Esperado | Medido |
|---|---|---|---|
| G1 | `KIT_SOCIALITE_GOOGLE=true` + credenciais no `.env`, `config:clear` | README (passos 2–4): botão aparece | **nenhum botão**, rota 404. O `settings` (semeado `false`/`null` no `migrate`) sobrepõe a config em todo request; com `DB_DATABASE=:memory:` a config lê `true`. **Achado 1a** |
| G2 | `kit:install --force` com o `.env` preenchido | README: "o banco nasce igual ao `.env` novo" | banco novo com `false`/`null`. **Achado 1b** |
| G3 | SQLite apagado à mão + `migrate --seed` em processo limpo | semeia do `.env` | `true`, id, secret cifrado ✅ |
| G4 | os três `/…/login` | botão só do Google | "Entrar com Google" nos três; os outros 404 ✅ |
| G5 | `/auth/google/redirect` | 302 para o Google | `accounts.google.com/o/oauth2/auth`, `redirect_uri` certo, `scope=openid profile email` ✅ |
| G6 | G2 com o fix do achado 1 aplicado | banco igual ao `.env` | controle 0.21.1 reproduz; com o fix: `true`, id, secret cifrado ✅ |

### Navegador (conta `gsferroti@gmail.com`)

| # | Cenário | Esperado | Medido |
|---|---|---|---|
| GB1 | sem conta, registro fechado | volta ao login com recusa; nada criado | ✅ `WARNING Recusado: não há conta e o registro está fechado`, e-mail mascarado, `users` inalterada; aviso vermelho na tela |
| GB2 | registro aberto | conta criada, cai no perfil, e-mail verificado | **403 em `/app/meu-perfil`** — conta sem papel. **Achado 3**. Com o fix: perfil abre ✅ |
| GB3 | conta existente | entra direto no `/app` | ✅ (entrou por senha **e** por Google, depois de definir a senha) |
| GB4 | perfil: trocar senha / ligar 2FA | funcionam | **impossível**: a conta social não tem senha atual. **Achado 4**. Com o bloco "Definir senha por e-mail": link no e-mail → senha definida → troca de senha e 2FA ✅ |
| GB5 | bloquear sessão sem senha local | destrava | **preso na tela de bloqueio**. **Achado 5**. Com o fix: botões sociais na tela, volta do Google destrava ✅ (`lock-session → screen/lock → auth/google/redirect → callback → autenticado`) |
| GB6 | desafio de 2FA | layout | funcionou; solicitante pediu o formulário à esquerda (como esqueci-a-senha) — ajuste `59a7cff` |

## GitHub — pela tela `/admin/configuracoes-do-kit` → Login (o outro caminho)

Solicitante gravou Client ID e Secret pela tela, como admin. Medido: `login_github_habilitado =
true`, `client_id` gravado, `client_secret` **cifrado** (`eyJ…`); "Entrar com GitHub" nos três
painéis ao lado do Google; `/auth/github/redirect` → `github.com/login/oauth/authorize` com
`scope=user:email`. Navegador: o e-mail primário do GitHub do solicitante é **outro**
(`g_s***@yahoo.com.br`) → com registro aberto, **criou uma segunda conta** (`origem = github`,
`panel_user`, verificada) e entrou. ✅

## LinkedIn — **não medido**, por decisão do solicitante

"vamos pular o linkedin (…) é muito complexo e eu não irei usar, quem for fazer o login por ele
que implemente e, caso precise corrigir algo, abra um PR para o kit". O driver `linkedin-openid`
continua no enum, coberto por `LoginSocialProvedoresTest` com fake. Sem app real, sem prova real.

## X — pendente

Roteiro: developer.x.com → Projects & Apps → *User authentication settings*, tipo **Web App**,
**OAuth 2.0**, escopos `users.read` e `users.email`; callback `http://localhost:8000/auth/x/callback`.

## Achados — todos corrigidos nesta PR

| # | Achado | Causa | Correção (commit) |
|---|---|---|---|
| 1a | README do login social mandava `.env` + `config:clear` num kit instalado | o `settings` vence o `.env`; o README dizia isso em outra seção | README PT/EN e `.env.example` (`ede2fa7`) |
| 1b | `kit:install --force` semeava o banco novo com os valores do banco **velho** | o processo sobe com o banco velho aplicado à config; só nome/cor/admin escapavam (o customizador os realinha em memória) — por isso a matriz P1 anterior passou | `ConfiguracoesDoKit::devolverConfigAoEnv()` chamado no `--force`; teste com mutação (`ede2fa7`) |
| 2 | o comentário da partial do ícone vazava para dentro do botão | `{--`/`--}` com uma chave só nas quatro partials | `{{--`/`--}}` + caso que renderiza cada partial (`b150654`) |
| 3 | conta criada pelo provedor recebia 403 no perfil | `User::create()` cru, sem papel; o CT-09 só olhava o destino do redirect, com premissa caducada no docblock | `RegistroAberto::registrar()` como porta; pendência e recusa da tenancy; CT-09 segue o redirect (`8c92658`) |
| 4 | conta social não consegue trocar senha, ligar 2FA nem desbloquear | senha aleatória que ninguém conhece | bloco "Definir senha por e-mail" no perfil dos três painéis, reaproveitando o reset do Filament (`d74c33c`, `5497f9d`) |
| 5 | quem vive sem senha local fica preso na tela de bloqueio | a tela só pedia senha | botões sociais na `TelaBloqueio`; a volta do provedor destrava (`67ba838`) |
| 6 | "o spotlight não abre no `/admin`" | **não era defeito**: por HTTP o overlay está nos três painéis; pelo `pest-plugin-browser` e pelo Playwright MCP na própria instalação o clique abre (`display: flex`, `isOpen: true`, console limpo). Estado do navegador do solicitante (suspeito: `localStorage` `spotlight.recent` corrompido — o `x-data` faz `JSON.parse` dele) | o F-45 deixa de ser `todo` e passa a exigir o campo visível (`6df209d`) |

### Ajustes pedidos durante a rodada (não eram defeitos)

- Aba Login: seções de provedor fechadas com ícone de status, "Habilitar botão do X", duas seções
  (provedores / rodapé); rodapé em **Markdown** com HTML cru descartado (`3ffb154`).
- Coluna **`origem`** em `users` (Google/GitHub/…, convite, registro, interno) na lista de
  usuários dos dois painéis e no widget do dashboard (`8f33903`).
- **Vínculo de provedor** (tabela `vinculos_sociais`, aviso na primeira vez, modo estrito com
  confirmação por e-mail) — feature com wiki própria: `wikis/specs/feat/vinculo-de-provedor-social/`
  (`1e71fc6`). Nasceu da pergunta "e se eu criar uma conta no Google com o e-mail de outra pessoa?"
  — a resposta e o argumento estão na ADR-01 daquela wiki.

### A nota do arnês

- O Playwright MCP **conseguiu** submeter o formulário de login do Filament nesta rodada
  (`admin@example.com`), ao contrário da rodada anterior — a diferença provável é o servidor
  estável na 8000 sem porta morta de turno anterior. Serviu para ver o console e o estado do
  Alpine na instalação, exatamente o que a regra permite (observação, não cobertura).
- `queue:work` foi necessário à mão duas vezes: o reset de senha e as notificações do vínculo são
  `ShouldQueue`, e a instalação de validação não tinha worker. O README passou a avisar.
- Toda mudança de código foi provada **na instalação** antes do commit (arquivo copiado por cima,
  `migrate` quando havia migration), e cada teste novo teve **mutação verificada**.

## Capturas (README, seção "As telas")

`login-social`, `admin-configuracoes-login`, `app-perfil-definir-senha`, `app-bloqueio-social`,
`admin-users-origem` — via `CapturaDeArteTest` + `kit:arte`, publicadas em `art/`.

## O que a rodada NÃO cobre (declarado)

- LinkedIn com app real (decisão do solicitante) e X (pendente — próximo passo).
- Login social com **2FA** já ligado voltando pelo provedor — o solicitante ligou o 2FA, mas o
  desafio foi medido no login por senha; a suíte cobre por fake (R12 do `LoginSocialGoogleTest`).
- Tenancy com app OAuth real — próximo passo pedido pelo solicitante ("replicar na pasta com
  tenancy ligado e `/register` ligado"). Já se sabe o que vai aparecer: conta **nova** via
  provedor é recusada sem organização (por desenho, `8c92658`), e a evolução é o botão carregar o
  `?org=` no `state` do OAuth.
- Provedor de e-mail real (`MAIL_MAILER=log`); Facebook/Discord (fora do enum, só o 404 medido).
