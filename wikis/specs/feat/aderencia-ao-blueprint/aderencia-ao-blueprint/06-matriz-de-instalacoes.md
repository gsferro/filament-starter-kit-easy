# Matriz de Instalações — oito caminhos de opt-in, do `create-project` ao navegador

> Complemento da RQ-05/RQ-06 depois da v0.21.0. Oito instalações **novas** do pacote publicado
> (`composer create-project gsferro/starter-kit-easy`), cada uma seguindo um caminho diferente de
> customização, e cada caminho verificado em **duas** superfícies: o terminal (comandos, `.env`,
> banco) e o **navegador real** via Playwright (o que o usuário vê). Os cenários e os oráculos foram
> escritos ANTES de rodar; os resultados, depois.
>
> Legenda: ✅ observado como esperado · ⚠️ funciona com ressalva · ❌ não funciona · — não exercitado.

## Os oito caminhos

### Padrão (sem multi-organização)

| # | Caminho | O que se liga | Oráculos (terminal → navegador) |
|---|---|---|---|
| **P1** | Customização por `.env` antes do `kit:install --force` | `APP_NAME=Padaria Central`, `KIT_COR_PRIMARIA=Emerald`, `KIT_ADMIN_EMAIL/PASSWORD/NAME` próprios | `kit:install --force --no-interaction` recria o banco com o admin novo → login em `/admin` com a credencial nova; nome da marca e cor verde na tela; `admin@example.com` **não** existe |
| **P2** | Registro aberto com aprovação manual | `KIT_REGISTRO=true`, `KIT_REGISTRO_APROVACAO_MANUAL=true` | `/app/register` responde 200 (fechado por default = 404/403) → cadastro pela tela → usuário nasce `aprovacao_pendente=1` sem papel → login recusado → `master_global` aprova em `/admin/users` → login abre `/app` só com `panel_user` |
| **P3** | Registro com verificação de e-mail + mudança em Settings | `KIT_REGISTRO=true`, `KIT_REGISTRO_VERIFICAR_EMAIL=true`, `MAIL_MAILER=log`; depois, em `/admin/configuracoes-do-kit`, trocar "Linhas por página" e "Tabela listrada" | cadastro → redirecionado a `/app/email-verification/prompt` → link extraído do `laravel.log` → verificado → entra; mudança em Settings reflete em `/admin/users` sem tocar `.env` |
| **P4** | Convite pelo `/admin` + hub ligado + `kit:admin` | `KIT_HUB=true`; convite em `/admin/convites`; `kit:admin --email --senha --force` | `/admin` mostra a grade de cartões (hub); convite gera link no log → `/app/register?token=` → define senha → entra com o papel do convite; `kit:admin` troca a credencial e o login antigo para de funcionar |

### Tenancy (multi-organização)

| # | Caminho | O que se liga | Oráculos |
|---|---|---|---|
| **T1** | `kit:tenancy` **sem** demo, organização criada pela tela | `git init` + `kit:tenancy --force` | `/app` do admin sem organização → tela de registro/escolha; criar organização em `/admin/organizacoes` pela tela, vincular usuário pela aba; login → `/app/{slug}`; slug inexistente → 404 |
| **T2** | Demo + registro aberto **por organização** | `kit:tenancy --demo --force`, `KIT_REGISTRO=true`; ligar registro só na `acme` (campo da organização) | `/app/register?org=acme` → 200; `?org=globex` → recusado; quem se registra nasce vinculado só à `acme`, com `panel_user`, e `/app/globex` dá 404 |
| **T3** | Convite **por organização** pelo `/app` | demo; `admin_app` (Ana) convida em `/app/acme/convites` | link no log → aceitar → novo usuário só na `acme`; tabela de usuários de `acme` o lista; `/app/globex/users` não |
| **T4** | Identidade visual por organização + rótulo customizado | `KIT_TENANCY_LABEL=Empresa`, `KIT_TENANCY_LABEL_PLURAL=Empresas`; cor `#7c3aed` na `acme` via `/admin/organizacoes/{acme}/edit` | menu do `/admin` diz "Empresas"; `/app/acme` renderiza a cor roxa e `/app/globex` a cor default; logo enviada aparece |

### Extras (se o tempo permitir)

| # | Caminho | Oráculo |
|---|---|---|
| **P5** | `kit:install --no-seed --no-npm` | banco sem papéis; `/admin/login` responde mas nenhum login funciona; `db:seed` depois resolve |
| **P6** | `kit:install --custom` numa instalação pronta | troca nome e cor **sem** apagar dados (usuários continuam) |
| **T5** | `kit:update --dry-run` numa instalação | lista o que mudou entre a versão instalada e a tag mais nova, sem alterar arquivo |

## Resultados

### O que a primeira rodada ensinou antes de qualquer cenário fechar

**`.env` depois de instalado não manda nas chaves que têm espelho em Settings** — `APP_NAME`,
`KIT_COR_PRIMARIA`, `KIT_REGISTRO*`, `KIT_HUB`, `KIT_TENANCY_LABEL*`, `KIT_TABELA_*`, e-mail. A
tabela de settings é semeada no `create-project` e `aplicarNaConfig()` a sobrepõe à config em todo
boot. Medido no T4: `env('KIT_TENANCY_LABEL_PLURAL')` = "Empresas", `config()` = "Organizações". **É
o desenho documentado** (README: "o `.env` semeia a instalação nova e é o plano B"; "o valor gravado
na tela vence") — mas ele redefine metade da matriz: P2, P3, P4 e T2/T4 tinham sido desenhados com
toggle por `.env` **depois** da instalação, e o caminho real do usuário é a tela
`/admin/configuracoes-do-kit`. Os cenários foram refeitos por esse caminho. O `.env` continua sendo
o caminho certo **antes** do primeiro migrate — e é isso que o P1 prova.

**`kit:install --force` não recriava o SQLite no Windows — e mentia no resumo. ❌ → corrigido.**
`File::delete()` com retorno ignorado; no Windows arquivo aberto não se apaga, e o próprio boot do kit
abre o SQLite (o `aplicarNaConfig()` lê a tabela de settings). Resultado medido no P1: o `--force`
rodava inteiro, "Rodando migrations" passava no banco **velho**, `admin@example.com` sobrevivia, e o
resumo imprimia "Login inicial: dona@padaria.test" — credencial que **não existia**. No Linux o unlink
funciona com handle aberto; o CI nunca viu. Correção: `App\Support\BancoSqlite` desconecta antes de
apagar e **falha alto** com a causa quando o arquivo sobrevive (as duas formas medidas no Windows —
o `unlink` recusar, e o `unlink` "passar" e o `file_put_contents` seguinte dar `Permission denied`).
Validado na instalação P1 com a correção copiada: recria (`Criando banco SQLite`, `dona@padaria.test |
Dona Maria`, settings "Padaria Central"/Emerald); com um handle alheio, para com a mensagem.

### Padrão

| # | Terminal | Navegador | Veredito |
|---|---|---|---|
| **P1** | `.env` com nome/cor/admin próprios → `kit:install --force` (corrigido) → banco novo com `dona@padaria.test`, settings "Padaria Central"/Emerald; `admin@example.com` não existe | login `/admin` renderiza título **"Padaria Central • Admin"** e a cor **Emerald** no botão e no foco do campo (screenshot conferido) | ✅ ✅ (o `--force` era ❌, corrigido) |
| **P2** | `registrar()` → usuário nasce `aprovacao_pendente=1`, sem papel, `canAccessPanel(/app)=false`; `aprovar()` → `panel_user`, `canAccessPanel=true` | `/app/register` = 200 (fechado por default = 302→login); tela "Criar sua conta" renderiza, zero erro de console | ✅ terminal · ✅ render (submit: ver nota do arnês) |
| **P3** | verify-email ligado por Settings → `registrar()` cria com `email_verified_at=null`, `User instanceof MustVerifyEmail`; `/app/email-verification/prompt` existe | tela de cadastro renderiza | ✅ terminal · ✅ render |
| **P4** | hub ligado por Settings; `kit:admin --email --senha --force` → admin único vira `chefe@p4.test`, **senha antiga inválida, nova válida** | — (hub visual coberto por `HubDeCardsTest` in-process) | ✅ terminal |

### Tenancy

| # | Terminal | Navegador | Veredito |
|---|---|---|---|
| **T1** | `kit:tenancy --force` (com `git init`): `KIT_TENANCY=true`, `permission.teams=true`, banco recriado, 1 tenant `padrao` | login `/app` (via matriz de acesso da v0.20.0, HTTP real) | ✅ terminal |
| **T2** | `--demo`; registro por organização ligado só na `acme` via Settings: `?org=acme`=200, `?org=globex`/inexistente/sem-org=302 | tela de cadastro por organização renderiza | ✅ **o gate por organização segura** |
| **T3** | `--demo`: 3 tenants, 4 usuários (Carla em acme+globex); fronteira de organização já provada em HTTP real na v0.21.0 (Carla → `/app/padrao`=404) | — | ✅ terminal |
| **T4** | rótulo por Settings: `config('kit.tenancy.label_plural')`="Empresas"; cor `#7c3aed` gravada na `acme` | (cor por organização coberta por `IdentidadeVisualTenancyTest` in-process) | ✅ terminal |

### A nota do arnês (por que o submit não foi pelo MCP)

`php artisan serve` + Playwright MCP não fecham o **submit** de formulário Livewire de forma
confiável: o XHR para `/livewire/update` não dispara (medido: nenhum POST no `serve.log` após o
clique, e a sessão do MCP ainda carregava erros de uma porta morta de turno anterior). É limitação
do combo, **não do kit** — a suíte `pest-plugin-browser` do próprio kit sobe o servidor in-process e
exercita esses mesmos submits (login, cadastro, gravação), e está verde. Então a divisão é a que a
regra `.ai/rules/testes.md` já fixa: **o navegador prova render, console e cor/tema** (feito aqui
pelo MCP); **gravação e transição de estado são camada de componente/aplicação** (feito aqui por
`RegistroAberto`/`kit:admin`/Settings no terminal, e pela suíte de componente verde).

## Defeito encontrado, e corrigido nesta rodada

**`kit:install --force` não recriava o SQLite no Windows — e o resumo mentia.** É o achado da
matriz, e o único. `File::delete()` com retorno ignorado; no Windows arquivo aberto não se apaga, e
o boot do kit já abre o SQLite (lê a tabela de settings). O `--force` seguia migrando o banco velho,
o admin antigo sobrevivia, e imprimia "Login inicial: {credencial nova}" — que não existia. No Linux
o unlink funciona com handle aberto, e por isso o CI (Ubuntu) nunca viu. `App\Support\BancoSqlite`
desconecta antes de apagar e **falha alto** com a causa quando o arquivo sobrevive — as duas formas
do problema no Windows medidas (o `unlink` recusar; o `unlink` "passar" e o `file_put_contents`
seguinte dar `Permission denied`). Provado na instalação P1: recria certo; com um handle alheio,
para com a mensagem. Guarda em `tests/Kit/BancoSqliteTest.php` (o caso do handle é `skip` fora do
Windows, honestamente, porque só ali ele discrimina).

## O que a matriz NÃO cobre (declarado)

- Provedor de e-mail real (todas usam `MAIL_MAILER=log`).
- Login social — exige credencial de provedor externo; coberto por `LoginSocialTest` com fake.
- `docker compose` (Postgres, Redis, IA local) — instalações em SQLite.
- Windows com TTY no Composer — não existe; é o caso documentado do bloco Windows do README.

---

## Rodada da v0.22.x — duas instalações, e os dois defeitos que só elas pegaram

> 2026-09-01, em `TESTES KIT/v0223-padrao` e `TESTES KIT/v0223-tenancy`, do pacote publicado no
> Packagist. Esta rodada não repetiu os oito caminhos acima: ela verificou a v0.22.x recém-lançada
> e, no caminho, encontrou **dois defeitos de instalação que a suíte do repositório não pega por
> construção**.

| Instalação | Como | Suíte |
|---|---|---|
| `v0223-padrao` | `create-project`, **sem** `git init`, nada tocado à mão | **1512/1512** (`Kit`) |
| `v0223-tenancy` | `create-project` + `git init` + `kit:tenancy --demo --force` | **1753/1753** (`Kit,Tenancy`) |

### D-01 — o `.env.example` anulava o default do provedor anti-robô

A v0.22.0 trocou o default para `recaptcha_v3` em `config/kit.php`, mas o `.env.example` fixava
`KIT_ANTI_ROBO_PROVEDOR=recaptcha_v2` — e `env()` vence o default do config. Toda instalação nova
nascia com o v2, e a mudança era **inócua**.

Achado com `php artisan config:show kit.login.anti_robo` na instalação da v0.22.1. **A suíte não
pega por construção**: ela roda com o `.env` de teste, não com o do skeleton. Corrigido na v0.22.2 e
reconferido em instalação limpa (`provedor = recaptcha_v3`, `habilitado = false`, chaves nulas).

### D-02 — `composer test:kit` quebrava em projeto recém-instalado

O `tests/Pest.php` ligava o TIA do Pest 5 incondicionalmente. O TIA diffa contra um branch, então
sem `.git` estoura `MissingDependency: The [Tia mode] feature requires [git]` — e o que chega ao
terminal é `WorkerCrashedException` do paratest, que **não diz o motivo**. `composer create-project`
não cria repositório: toda instalação nova batia nisso na primeira vez que rodasse a suíte, antes de
ver um único teste.

**A suíte não pega por construção**: no repositório sempre há `.git`. Corrigido na v0.22.3
(`if (is_dir(__DIR__.'/../.git'))`) e provado onde o defeito acontecia — 1512 casos passam na
instalação sem git.

### Navegação em navegador real (Playwright MCP, `--isolated --headless`, só localhost)

`v0223-padrao` servida em `127.0.0.1:8123`, percorrida como observação — **não** como cobertura;
quem prova comportamento continua sendo a suíte.

| Tela | O que se viu |
|---|---|
| `/` | boas-vindas com a versão **0.22.3**, multi-organização desligada |
| `/admin/login` | login com `admin@example.com` entra |
| `/admin/users` | **as abas da v0.22.0**: "Todos" ativa e "Pendentes de aprovação" com badge `0` |
| `/admin/users?tab=pendentes` | a aba troca **pela URL** e a tabela recorta — o mecanismo que o README indica para linkar listagem já recortada |
| `/admin/convites` | as três abas: Todos, Pendentes, Aceitos, sem badge |
| `/admin/shield/roles` | quatro papéis, com a coluna "Acesso ao painel" |
| `/infra` | navegação inteira de pé |
| `/admin/configuracoes-do-kit`, `/app` | abrem sem erro |

**Zero erros de console** em todas elas.

**O que a navegação não prova, e não podia**: as travas de escalada não barram nada nessa sessão
porque `admin@example.com` é o `master_global` e atravessa pelo `Gate::before` — quem prova a
negação são os CT-05/CT-07 da wiki `travas-de-escalada-de-papeis`. E o widget anti-robô não aparece
porque nasce desligado, que é o comportamento correto.

### A lição desta rodada

Os dois defeitos são da mesma família: **estado que só existe fora do repositório** — o `.env` do
skeleton e a ausência de `.git`. Nenhuma quantidade de teste dentro do repositório os alcança, e os
dois chegariam a quem instala. É o argumento de existir esta matriz, e o critério para a próxima:
procurar onde a instalação difere do repositório, não repetir o que a suíte já cobre.
