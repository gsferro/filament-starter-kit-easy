# Casos de Teste — Página única de login (`/login`)

> Requisito: `00-requisito.md` · Plano: `01-plano-acao.md`
> Derivado do **requisito**, não do plano. Nenhum cenário foi escrito olhando implementação — ela
> não existe. Lidos para herdar convenção: `tests/Pest.php` (helpers), `tests/Kit/SituacaoDaContaTest.php:50-57`
> (login por componente: `Filament::setCurrentPanel()` + `Livewire::test(TelaLogin::class)->fillForm()->call('authenticate')`),
> `tests/Kit/LoginSocialPorPainelTest.php`, `tests/Kit/LoginSocialTest.php` (Socialite falso), `tests/Kit/BoasVindasTest.php:56-116`
> (cartões e ausência de sidebar), `tests/Kit/KitUpdateTest.php` (`estaCoberto()`), `tests/Kit/ConfiguracoesDoKitTest.php`
> (gravação do Settings por componente). Vendor aberto para confirmar oráculo, não comportamento: `Login.php:172-179`
> (a recusa por painel corrente é o que o `00` mede como risco).

## Perfil de Derivação

| Área | P | I | P×I | Perfil |
|---|---|---|---|---|
| A — chave e Settings (RQ-01, RQ-02) | 2 — integra com o Settings existente | 2 — toggle que grava e não governa | 4 | **padrão** |
| B — redirecionamento das telas de login e dos fluxos vizinhos (RQ-02, RQ-03, RQ-07) | 2 — um `mount()` na tela dos três painéis | 3 — laço de redirect derruba o login inteiro | 6 | **padrão** |
| C — autenticação na página única (RQ-03) | 3 — sobrescreve a decisão de acesso do Filament | 3 — autorização: quem não pode entrar entra, ou quem pode é barrado | 9 | **completo** (revisão adversarial) |
| D — destino após o login (RQ-04, RQ-06) | 2 — regra com três condições | 3 — mandar para painel que a pessoa não acessa | 6 | **padrão**, técnica escalada para **tabela de decisão** (a regra é combinação) |
| E — a tela de escolha (RQ-05, RQ-08) | 2 — cartões já existem; filtro novo | 3 — cartão para painel inacessível / tela para anônimo | 6 | **padrão** |
| F — login social no modo unificado (RQ-03) | 2 — dois pontos existentes mudam | 2 — destino errado, reversível | 4 | **padrão** |
| G — entrega pelo `kit:update` (`database/settings`) | 1 | 2 — quem atualiza não recebe a migration | 2 | **mínimo** |

- Técnicas aplicadas: **EP** (valores do `.env`; formas de fonte), **matriz papel × resultado** (C), **tabela de decisão** (D: painéis × pretendida), **rastreio de efeito** (log, sessão consumida, não-efeito na recusa), **controle negativo** (chave desligada em cada área), asserção sobre o fonte onde a operação exige git (G reutiliza `estaCoberto()`).
- Cenários: **22** na derivação + **9** da revisão adversarial (CT-23…CT-30, CT-32; CT-31 fundido em CT-03) + **1** CT-B (`05`) · Regras: 8 · Mutantes previstos: 31 (+ os da revisão) · Sem matador na suíte: **1** (M-C7, timing — declarado).

## Varredura SFDIPOT

| Letra | O que existe nesta feature | Cenários gerados |
|---|---|---|
| **S** | chave de config + `.env`; propriedade/mapa/migration do Settings + toggle; duas páginas Filament fora de painel; um `mount()` na tela dos três painéis; uma resposta de login no container; um método no controller social; uma linha no blade dos botões; dois caminhos em `CAMINHOS_DO_KIT` | CT-01…CT-03, CT-21, CT-22 |
| **F** | ligar/desligar; redirecionar; autenticar contra "algum painel"; decidir destino; listar só o acessível; encerrar sessão de quem não tem painel; destino do login social | CT-04…CT-20 |
| **D** | usuários com 0, 1, 2 e 3 painéis; conta inativa; senha errada; `url.intended` presente/ausente/inacessível/prefixo enganoso (`/administracao` × `/admin`); valores de `.env` (`true`, `1`, `false`, `0`, `off`, vazio, lixo) | CT-02, CT-08…CT-15 |
| **I** | `GET /login`, `GET /login/painel`, `GET /{painel}/login`, o formulário (Livewire), o toggle (Livewire), callback do Socialite, `kit:update` | todos |
| **P** | guard `web` único; sessão (`url.intended`); Filament 5 com `SetUpPanel` persistente no Livewire (medição no passo 6 do PRD, não é oráculo aqui) | — (declarado: não é variável) |
| **O** | quem administra a instalação (`admin`, `infra`, `master_global`), quem só opera (`panel_user`), quem não tem papel, conta inativa; instalação **antiga** atualizando (G) | CT-08…CT-13, CT-22 |
| **T** | **não se aplica**: sem expiração nem concorrência. A única ordem que importa (a pretendida é consumida **uma** vez) está em CT-16 | CT-16 |

## Mapa de Regras

| Regra | Área (perfil) | Origem (`RQ`) | Técnica | Cenários |
|---|---|---|---|---|
| R1 — a chave nasce desligada; só `true`/`1` ligam; o toggle do Settings grava e faz efeito no request seguinte | A (padrão) | RQ-01, RQ-02 | EP sobre os valores; gravação por componente + efeito | CT-01, CT-02, CT-03 |
| R2 — ligada, toda tela de login de painel leva a `/login`; desligada, cada uma responde; `/login` desligada leva ao login do painel default; os fluxos que chegam ao login de painel terminam em `/login` | B (padrão) | RQ-02, RQ-03, RQ-07 | EP por painel; rastreio por fluxo de origem; controle negativo (laço) | CT-04, CT-05, CT-06, CT-07 |
| R3 — na página única, entra quem pode acessar **algum** painel; quem não pode acessar nenhum, ou erra a senha, ou tem conta indisponível, é recusado **sem sessão** e com o mesmo tratamento de hoje | C (completo) | RQ-03 | matriz papel × resultado; rastreio de não-efeito | CT-08, CT-09, CT-10, CT-11 |
| R4 — o destino após o login é: pretendida de painel acessível → ela; senão um painel → ele; senão → escolha; e a pretendida é consumida | D (padrão, escalada) | RQ-04, RQ-06 | tabela de decisão (painéis × pretendida) | CT-12, CT-13, CT-14, CT-15, CT-16 |
| R5 — a tela de escolha só existe para quem entrou e tem mais de um painel; mostra **só** os acessíveis; um painel redireciona; nenhum encerra a sessão | E (padrão) | RQ-05, RQ-08 | matriz (0/1/2/3 painéis × anônimo) | CT-17, CT-18, CT-19 |
| R6 — no modo unificado o login social segue a mesma regra de destino e os botões não carregam painel | F (padrão) | RQ-03, RQ-04, RQ-06 | EP (ligado/desligado) × destino | CT-20, CT-21 |
| R7 — `database/settings` (e o diretório da resposta de login) chegam a quem atualiza | G (mínimo) | — (achado do `00`) | asserção sobre a lista | CT-22 |
| R8 — com a chave desligada, nada muda | todas | RQ-02 | regressão | suítes existentes (lista no PRD) + controles negativos em CT-04, CT-06, CT-21 |

Técnica escalada: **R4** usa tabela de decisão numa área `padrão` — a regra combina três condições (quantos painéis, há pretendida, ela é acessível), e EP isolada não distingue "honra a pretendida sempre" de "honra só quando acessível".

## Fronteira com o Plano

| Item do PRD | Recusado como oráculo porque | Destino |
|---|---|---|
| nomes `TelaLoginUnificada`, `EscolhaDePainel`, `DestinoAposLogin`, `RespostaDeLogin`, `Paineis::cartoes()` | escolha de implementação | detalhe do cenário (o componente a montar) |
| `HttpResponseException` no `mount()` | mecanismo | não aparece em `Então` — o oráculo é o redirect HTTP |
| textos "Em qual painel você quer entrar?", "Sua conta não tem acesso a nenhum painel." | comportamento visível que o requisito **não** determina | não são oráculo; CT-17 afirma os **cartões** (rótulos que o requisito herda da boas-vindas), CT-19 afirma sessão encerrada + redirect |
| `panel:app` como contexto | mecanismo | não é oráculo; a consequência visível (login registrado como `app`) é **documentação**, não cenário |
| rota `/login/painel` | o requisito diz "opção de selecionar o painel", não a URL | a URL entra nos cenários como destino observável porque o requisito exige que **exista** uma tela; o path em si é detalhe — se mudar, muda só o `Então` literal |
| `/login` desligado → login do painel default | premissa do `00`, não cláusula | CT-06 marcado `@premissa` |
| a pretendida vence a escolha | premissa do `00` (ADR-06) | CT-14, CT-15 marcados `@premissa` |
| `/login/painel` com a chave **desligada** | o requisito não diz | **pergunta nova** (abaixo); CT-18 tem uma linha `@premissa` |

**Perguntas em aberto** (replicadas em `00-requisito.md` → `## Ambiguidades`):

- **`/login/painel` com a chave desligada** — autenticado com 2 painéis abre a escolha, ou é mandado ao painel default? Premissa adotada: **abre** (a rota exige `auth`, não expõe nada que a topbar do Panel Switch já não mostre, e gatear por chave é um `if` a mais sem cláusula). CT-18 linha "desligada" marcada `@premissa`.

## Setup Global

### Personas

- `admin` — `usuarioDoKit('admin', 'admin@example.com')`: acessa **só** `/admin` (papel `admin` tem `painel = admin`; sem papel no `/app` nem no `/infra`). É a persona **discriminante** de R3: hoje ela **não** entra pelo `/app/login`.
- `infra` — `usuarioDoKit('infra', …)`: só `/infra`.
- `panel_user` — `usuarioDoKit('panel_user', …)`: só `/app`.
- `admin+infra` — `usuarioDoKit('admin', …)` + `->assignRole('infra')`: dois painéis, **nenhum** deles o default. Discriminante para "≥2 → escolha" e para "pretendida `/infra/...` acessível".
- `master_global` — `usuarioDoKit('master_global', …)`: três painéis.
- `sem papel` — `usuario('ninguem@example.com')`: zero painéis.
- `inativa` — `usuarioDoKit('admin', …)` com `ativo = false` (a forma que `SituacaoDaContaTest` usa).

Senha: a da factory (`password`). Em todos os cenários de login o guard começa **deslogado** (`Filament::auth()->logout()`).

### Fixtures

- Chave ligada: `config()->set('kit.login.unificado', true)` **e**, nos cenários do Settings, `gravarConfiguracao('login_unificado', true)` + `alinharConfiguracoesDoKit()` — a chave existe em `config/kit.php` (CT-01 prova) antes de qualquer `config()->set()` (`.ai/rules/config.md`).
- Valores de `.env`: `kitConfigCom('KIT_LOGIN_UNIFICADO', $valor)` (`tests/Pest.php:479`) lê o `config/kit.php` com a variável plantada — é o único jeito de testar o `filter_var`.
- Pretendida: `session()->put('url.intended', url('/admin/users'))` — o mesmo slot que o `Authenticate` do Filament grava.
- Login social: `ligarProvedor(ProvedorSocial::Google)` + `usuarioSocialFalso(...)` + o mock do Socialite como em `LoginSocialTest`.

### Fakes

- `espiarAutenticacao()` (spy do canal `autenticacao`) nos cenários de log.
- Nenhum `Http::fake()` além do que o molde do Socialite já faz.

### Estratégia de DB

- `RefreshDatabase` e seeders de papéis conforme `tests/Pest.php` (suíte `Kit`, `->group('kit')`).

---

## Regra R1 — a chave nasce desligada; só `true`/`1` ligam; o toggle grava e faz efeito

> `RQ-01`, `RQ-02` · perfil **padrão** · técnica: **EP** sobre os valores do `.env` (partições: liga / não liga / irreconhecível) + **gravação por componente com efeito**

```gherkin
# language: pt

Funcionalidade: página única de login para os três painéis

  Regra: a chave nasce desligada, só true/1 ligam, e o toggle do Settings faz efeito no request seguinte

    Cenário: [CT-01] de fábrica a chave está desligada e o login continua por painel
      Dado o config/kit.php lido sem KIT_LOGIN_UNIFICADO no ambiente
      Quando se lê kit.login.unificado
      Então o valor é false
      E GET /admin/login responde 200 com o formulário de login

    Esquema do Cenário: [CT-02] só true e 1 ligam a chave
      Dado KIT_LOGIN_UNIFICADO igual a "<valor>" no ambiente
      Quando se lê kit.login.unificado do config/kit.php
      Então o valor é <ligado>

      Exemplos:
        | valor | ligado | # partição            |
        | true  | true   | liga                  |
        | 1     | true   | liga                  |
        | false | false  | não liga              |
        | 0     | false  | não liga              |
        | off   | false  | não liga              |
        |       | false  | vazio — falha fechado |
        | sim   | false  | irreconhecível        |

    Cenário: [CT-03] ligar pela tela de configurações redireciona o login do painel no request seguinte
      Dado o administrador salva a tela de configurações com "Unificar o login em /login" ligado
      E a propriedade login_unificado está gravada como true no Settings
      Quando um visitante anônimo abre /admin/login
      Então é redirecionado para /login
      E kit.login.unificado lido no request vale true
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M-A1 | `(bool) env('KIT_LOGIN_UNIFICADO')` em vez de `filter_var(..., FILTER_VALIDATE_BOOLEAN)` — `"off"` e `"sim"` ligam | CT-02 (linhas `off`, `sim`) |
| M-A2 | default `true` (ou `env()` sem default) | CT-01, CT-02 (linha vazia) |
| M-A3 | propriedade no Settings **sem** a linha no `mapaDeConfiguracao()` — grava e não governa | CT-03 (`Então` 1: a tela gravou, e o request seguinte **não** redirecionaria) |
| M-A4 | chave lida uma vez no boot e memorizada — o toggle só vale após reiniciar | CT-03 (o redirect acontece no **mesmo processo de teste**, sem reboot) |

---

## Regra R2 — ligada, toda tela de login de painel leva a `/login`; desligada, cada uma responde; sem laço

> `RQ-02`, `RQ-03`, `RQ-07` · perfil **padrão** · técnica: **EP por painel** (os três), **rastreio por fluxo de origem**, **controle negativo** nos dois sentidos do laço

```gherkin
# language: pt

  Regra: com a chave ligada as telas de login dos painéis levam a /login; desligada, cada uma responde; e não há laço

    Esquema do Cenário: [CT-04] cada tela de login de painel obedece à chave
      Dado a chave kit.login.unificado <chave>
      Quando um visitante anônimo abre /<painel>/login
      Então a resposta é <resposta>

      Exemplos:
        | chave     | painel | resposta                          |
        | ligada    | admin  | redirect para /login              |
        | ligada    | infra  | redirect para /login              |
        | ligada    | app    | redirect para /login              |
        | desligada | admin  | 200 com o formulário de login     |
        | desligada | infra  | 200 com o formulário de login     |
        | desligada | app    | 200 com o formulário de login     |

    Esquema do Cenário: [CT-05] os fluxos que hoje terminam na tela de login de um painel terminam em /login
      Dado a chave ligada
      E <situação>
      Quando <ação>
      Então a primeira resposta é um redirect para <primeiro destino>
      E seguir esse redirect termina em /login com o formulário

      Exemplos:
        | situação                                        | ação                                   | primeiro destino |
        | um visitante anônimo                            | abre /admin/users                      | /admin/login     |
        | um visitante anônimo                            | abre /infra/two-factor-authentication  | /infra/login     |
        | um visitante anônimo sem token de convite       | abre /app/register                     | /app/login       |
        | uma pessoa autenticada no /admin                | faz logout pelo /admin                 | /admin/login     |
        | uma sessão bloqueada no /admin (lock screen)    | escolhe "sair" na tela de bloqueio     | /admin/login     |

    Cenário: [CT-06] @premissa /login com a chave desligada leva ao login do painel default, e não volta
      Dado a chave desligada
      Quando um visitante anônimo abre /login
      Então é redirecionado para /app/login
      E /app/login responde 200 com o formulário — não redireciona de volta

    Cenário: [CT-07] /login com a chave ligada responde a própria tela, sem redirecionar
      Dado a chave ligada
      Quando um visitante anônimo abre /login
      Então a resposta é 200
      E contém o formulário de login com os campos de e-mail e senha
      E contém a arte da tela de login e o layout de autenticação (fi-auth-layout)
```

> CT-05: a linha do lock screen depende de como o pacote expõe "sair" (`TelaBloqueio::sairPara`); se
> a ação não for alcançável por HTTP simples, a linha vira asserção sobre o **destino** que a tela
> usa (`getLoginUrl()` do painel), que CT-04 já prova redirecionar. Registrar no `03` qual das duas.
> A linha do 2FA usa a rota `/infra/two-factor-authentication` como **rota autenticada** — é o
> `Authenticate` que redireciona; o desafio 2FA em si acontece na tela de login (Filament MFA) e é
> coberto pelo pacote, não por esta wiki.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M-B1 | o `mount()` da tela de painel redireciona **sem** conferir a rota corrente — `/login` redireciona para si mesmo (laço) | CT-07 |
| M-B2 | a página única desligada redireciona para `/login` de novo, ou responde 200 (superfície nova sem chave) | CT-06 |
| M-B3 | o redirect só foi posto na tela de **um** painel (ex.: só no `app`) | CT-04 (linhas `admin`, `infra`) |
| M-B4 | o redirect é feito **também** com a chave desligada (condição esquecida) | CT-04 (linhas `desligada`) |
| M-B5 | o `Authenticate` foi trocado para mandar direto a `/login`, mas registro e logout continuam na tela do painel — que agora **não** redireciona | CT-05 (linhas registro, logout) |

---

## Regra R3 — na página única entra quem acessa **algum** painel; os demais são recusados sem sessão, como hoje

> `RQ-03` · perfil **completo** · técnica: **matriz papel × resultado** (persona discriminante: `admin`, que hoje **não** entra pelo `/app/login`) + **rastreio de não-efeito** na recusa

```gherkin
# language: pt

  Regra: na página única entra quem pode acessar algum painel; quem não pode é recusado sem sessão, com o tratamento de hoje

    Esquema do Cenário: [CT-08] quem acessa ao menos um painel entra pela página única
      Dado a chave ligada
      E uma pessoa com papel <papel>
      Quando ela envia e-mail e senha corretos no formulário de /login
      Então o formulário não tem erro
      E ela está autenticada

      Exemplos:
        | papel         | # painéis | discriminante                                        |
        | admin         | 1         | não acessa o app — o pai recusaria pelo painel corrente |
        | infra         | 1         | idem                                                 |
        | panel_user    | 1         | acessa só o default                                  |
        | admin+infra   | 2         | nenhum dos dois é o default                          |
        | master_global | 3         | atalho do master                                     |

    Cenário: [CT-09] quem não acessa painel nenhum é recusado com o erro genérico e sem sessão
      Dado a chave ligada
      E uma pessoa sem papel, com senha correta
      Quando ela envia e-mail e senha no formulário de /login
      Então o formulário tem o erro genérico de login no campo de e-mail
      E ela não está autenticada
      E o canal autenticacao recebeu um warning de recusa com o id dela

    Cenário: [CT-10] senha errada é recusada com o erro genérico, mesmo para quem acessa três painéis
      Dado a chave ligada
      E uma pessoa master_global
      Quando ela envia o e-mail correto e a senha errada em /login
      Então o formulário tem o erro genérico de login
      E ela não está autenticada

    Cenário: [CT-11] conta inativa com senha certa recebe a mesma explicação de hoje
      Dado a chave ligada
      E uma pessoa admin com a conta inativa
      Quando ela envia e-mail e senha corretos em /login
      Então ela não está autenticada
      E é redirecionada para a rota de conta indisponível (a mesma que SituacaoDaContaTest afirma no /admin/login)
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M-C1 | a decisão de acesso do Filament **não** foi sobrescrita — pergunta só pelo painel corrente (`app`) | CT-08 (linhas `admin`, `infra`, `admin+infra`) |
| M-C2 | sobrescrita devolvendo sempre `true` — quem não tem papel entra | CT-09 (`Então` 2) |
| M-C3 | sobrescrita confere só o **primeiro** painel da lista (`Filament::getPanels()[0]`) em vez de algum | CT-08 (linha `infra` — não é o primeiro) |
| M-C4 | a recusa por "nenhum painel" **não** é logada | CT-09 (`Então` 3) |
| M-C5 | a recusa por "nenhum painel" autentica e **depois** redireciona ao login (sessão criada) | CT-09 (`Então` 2 — `assertGuest`) |
| M-C6 | a página única quebra o embrulho de `TelaLogin::authenticate()` (conta inativa cai no erro genérico em vez da explicação) | CT-11 |
| M-C7 | senha errada de quem tem painéis passa pela verificação de painéis antes da senha e vaza o resultado (tempo/erro diferente) | CT-10 (mesmo erro genérico) — o **tempo** não é medido: ⚠️ **sem matador na suíte** para a parte de timing; o Filament faz a verificação dentro do `Timebox` e a sobrescrita é chamada **dentro** dele — declarado, coberto por leitura do vendor (`Login.php:92-104`) |

---

## Regra R4 — o destino após o login: pretendida acessível → ela; um painel → ele; senão → escolha; a pretendida é consumida

> `RQ-04`, `RQ-06` · perfil **padrão**, técnica escalada: **tabela de decisão** (condições: nº de painéis, há pretendida, pretendida é de painel acessível)

| Painéis | Pretendida | Acessível | Destino | Cenário |
|---|---|---|---|---|
| 1 | não | — | o painel | CT-12 |
| ≥2 | não | — | a escolha | CT-13 |
| 1 | sim | sim | a pretendida | CT-14 |
| ≥2 | sim | sim | a pretendida | CT-14 |
| 1 | sim | **não** | o painel (pretendida descartada) | CT-15 |
| ≥2 | sim | não | a escolha | CT-15 |
| 1 | sim | prefixo enganoso (`/administracao` para `admin`) | o painel | CT-15 |

```gherkin
# language: pt

  Regra: depois de entrar pela página única, o destino é a pretendida se for de um painel acessível, senão o único painel, senão a escolha

    Esquema do Cenário: [CT-12] quem tem um só painel vai direto para ele
      Dado a chave ligada e nenhuma URL pretendida na sessão
      E uma pessoa com papel <papel>
      Quando ela entra pelo formulário de /login
      Então é redirecionada para <destino>

      Exemplos:
        | papel      | destino |
        | admin      | /admin  |
        | infra      | /infra  |
        | panel_user | /app    |

    Esquema do Cenário: [CT-13] quem tem mais de um painel vai para a escolha
      Dado a chave ligada e nenhuma URL pretendida na sessão
      E uma pessoa com papel <papel>
      Quando ela entra pelo formulário de /login
      Então é redirecionada para /login/painel

      Exemplos:
        | papel         |
        | admin+infra   |
        | master_global |

    Esquema do Cenário: [CT-14] @premissa a URL pretendida de um painel acessível vence, com um ou mais painéis
      Dado a chave ligada
      E a URL pretendida na sessão é <pretendida>
      E uma pessoa com papel <papel>
      Quando ela entra pelo formulário de /login
      Então é redirecionada para <pretendida>

      Exemplos:
        | papel       | pretendida    |
        | admin       | /admin/users  |
        | admin+infra | /infra/health |
        | admin+infra | /admin        |

    Esquema do Cenário: [CT-15] @premissa a URL pretendida de painel inacessível, ou de prefixo enganoso, é descartada
      Dado a chave ligada
      E a URL pretendida na sessão é <pretendida>
      E uma pessoa com papel <papel>
      Quando ela entra pelo formulário de /login
      Então é redirecionada para <destino>
      E não para a pretendida

      Exemplos:
        | papel       | pretendida            | destino       | # partição                         |
        | admin       | /infra/health         | /admin        | painel inacessível, um painel      |
        | admin+infra | /app/users            | /login/painel | painel inacessível, dois painéis   |
        | admin       | /administracao/x      | /admin        | prefixo enganoso (não é /admin/…)  |
        | admin       | https://evil.test/adm | /admin        | host externo                       |

    Cenário: [CT-16] a URL pretendida é consumida no login
      Dado a chave ligada e a URL pretendida /admin/users na sessão
      E uma pessoa admin
      Quando ela entra pelo formulário de /login
      Então a sessão não tem mais URL pretendida
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M-D1 | `redirect()->intended(...)` cru — honra a pretendida **sempre** | CT-15 (linhas inacessível e externo) |
| M-D2 | pretendida ignorada sempre (só a regra 1/≥2) | CT-14 |
| M-D3 | comparação por `str_starts_with($path, '/admin')` sem barra — `/administracao` passa | CT-15 (linha prefixo enganoso) |
| M-D4 | `count > 1` trocado por `>= 1` (ou vice-versa): um painel vai para a escolha | CT-12 |
| M-D5 | destino fixo no painel **default** quando há um só (em vez do único acessível) | CT-12 (linhas `admin`, `infra`) |
| M-D6 | pretendida lida com `session()->get()` e não consumida — vaza para o próximo login | CT-16 |
| M-D7 | a resposta de login do kit **não** foi vinculada no container (a do Filament continua) — todo mundo vai para `/app` | CT-12 (linhas `admin`, `infra`), CT-13 |

---

## Regra R5 — a tela de escolha: só para quem entrou e tem mais de um painel; mostra só os acessíveis; um redireciona; nenhum encerra a sessão

> `RQ-05`, `RQ-08` · perfil **padrão** · técnica: **matriz** (0 / 1 / 2 / 3 painéis × anônimo)

```gherkin
# language: pt

  Regra: a escolha só aparece para quem entrou e tem mais de um painel, e só com os painéis acessíveis

    Esquema do Cenário: [CT-17] a tela mostra um cartão por painel acessível, e nenhum a mais
      Dado a chave ligada
      E uma pessoa autenticada com papel <papel>
      Quando ela abre /login/painel
      Então a resposta é 200 sob o escopo de CSS dos cartões do kit
      E contém os cartões <presentes>, cada um apontando para a raiz do painel
      E não contém os cartões <ausentes>
      E não traz a barra lateral nem a topbar de painel

      Exemplos:
        | papel         | presentes                                        | ausentes                     |
        | admin+infra   | Administração (/admin), Infraestrutura (/infra)  | Painel do negócio (/app)     |
        | master_global | Painel do negócio, Administração, Infraestrutura | —                            |

    Esquema do Cenário: [CT-18] quem tem um só painel nunca vê a tela; quem não entrou também não
      Dado <situação>
      Quando abre /login/painel
      Então é redirecionada para <destino>
      E a resposta não contém cartão nenhum

      Exemplos:
        | situação                                                  | destino | # partição                         |
        | uma pessoa autenticada só com admin, chave ligada          | /admin  | um painel                          |
        | um visitante anônimo, chave ligada                         | /login  | anônimo                            |
        | uma pessoa autenticada admin+infra, chave desligada        | 200 com dois cartões (@premissa) | a rota não é gateada pela chave |

    Cenário: [CT-19] quem entrou e não tem painel nenhum tem a sessão encerrada e volta ao login
      Dado a chave ligada
      E uma pessoa autenticada sem nenhum papel
      Quando ela abre /login/painel
      Então é redirecionada para /login
      E não está mais autenticada
      E o canal autenticacao recebeu um warning com o id dela
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M-E1 | cartões montados sem filtro (os três da boas-vindas) | CT-17 (linha `admin+infra`, `Então` 3) |
| M-E2 | filtro por **papel do painel** em vez de `canAccessPanel()` — `master_global` (sem papel de painel `app`) perde cartões | CT-17 (linha `master_global`) |
| M-E3 | rota sem `auth` — anônimo vê a tela vazia ou estoura | CT-18 (linha anônimo) |
| M-E4 | um painel **renderiza** a tela com um cartão, em vez de redirecionar | CT-18 (linha um painel) |
| M-E5 | zero painéis mostra a tela vazia com sessão viva | CT-19 |
| M-E6 | a tela usa o layout do painel (sidebar/topbar) — `kit-cards-page` sem `simple` | CT-17 (`Então` 4) |

---

## Regra R6 — no modo unificado o login social segue a mesma regra de destino, e os botões não carregam painel

> `RQ-03`, `RQ-04`, `RQ-06` · perfil **padrão** · técnica: **EP** (chave ligada / desligada) × destino; controle negativo pelo molde de `LoginSocialPorPainelTest`

```gherkin
# language: pt

  Regra: com a chave ligada o login social decide o destino como o login por senha, e os botões não carregam painel

    Esquema do Cenário: [CT-20] a volta do provedor segue a regra de destino
      Dado a chave ligada e o Google habilitado
      E uma conta existente com papel <papel>, sem URL pretendida
      Quando o provedor devolve o callback dessa conta
      Então ela está autenticada
      E é redirecionada para <destino>

      Exemplos:
        | papel       | destino       |
        | admin       | /admin        |
        | admin+infra | /login/painel |

    Esquema do Cenário: [CT-21] o botão social carrega o painel só no modo por painel
      Dado a chave <chave> e o Google habilitado
      Quando se renderiza <tela>
      Então o link do botão "Entrar com Google" <painel na query>

      Exemplos:
        | chave     | tela         | painel na query        |
        | ligada    | /login       | não tem `painel=`      |
        | desligada | /admin/login | tem `painel=admin`     |
```

> CT-21 linha `desligada` é o controle negativo: é o que `LoginSocialPorPainelTest` já afirma —
> incluída aqui para o par ficar visível; pode apontar para o caso existente em vez de duplicar.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M-F1 | o callback continua mandando para o painel default/da sessão — `admin` cai em `/app`, onde toma 403 | CT-20 (linha `admin`) |
| M-F2 | o callback aplica a regra só para "um painel" e ignora a escolha | CT-20 (linha `admin+infra`) |
| M-F3 | o botão mantém `painel=app` na página única — filtra provedores pela lista do `/app` e força destino | CT-21 (linha `ligada`) |
| M-F4 | o botão perde `painel=` **também** com a chave desligada (regressão de `login-social-por-painel`) | CT-21 (linha `desligada`) |

---

## Regra R7 — `database/settings` e `app/Http/Responses` chegam a quem atualiza

> achado do `00` · perfil **mínimo** · técnica: asserção sobre a lista (`estaCoberto()` de `KitUpdateTest`)

```gherkin
# language: pt

  Regra: a migration de Settings e a resposta de login entram na lista do kit:update

    Esquema do Cenário: [CT-22] os caminhos novos estão cobertos
      Quando se pergunta se <arquivo> está coberto por CAMINHOS_DO_KIT
      Então a resposta é sim

      Exemplos:
        | arquivo                                                                     |
        | database/settings/2026_09_05_100000_add_login_unificado_to_kit_settings.php |
        | app/Http/Responses/RespostaDeLogin.php                                      |
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M-G1 | `database/settings` esquecido (como esteve por sete migrations) | CT-22 (linha 1) |
| M-G2 | a classe de resposta criada em `app/Http/Responses` sem a linha na lista | CT-22 (linha 2) — e a varredura "cobre todo o código do kit" com `database/settings` em `DIRETORIOS_DE_CODIGO` |

---

## Checklist de Taxonomia

| Item | Cenário que mata |
|---|---|
| IDOR / autorização horizontal | não se aplica: nenhuma rota recebe `{id}` |
| Autorização exercida na ação (não só `can()`) | CT-08, CT-09 (o `authenticate()` é a ação), CT-17/CT-19 (a tela filtra e encerra) |
| Idempotência (ancorada no agregado) | CT-16 — segundo login não herda a pretendida do primeiro |
| Concorrência | não se aplica |
| Fronteira no ponto de entrada (gravação) | CT-03 — o toggle grava e governa |
| Domínio condicionado | CT-14/CT-15 — a pretendida só vale condicionada ao painel dela |
| Estado × operação de escrita | não se aplica: nada é escrito além do toggle |
| Ausente ≠ null ≠ vazio | CT-02 (vazio) e CT-12/CT-13 (pretendida ausente) |
| Paginação / ordenação | não se aplica |
| Timezone / DST | não se aplica |
| Unicode / limite de varchar | não se aplica |
| Unicidade + soft delete | CT-11 — conta indisponível (o soft delete cai no mesmo caminho de `motivoDeIndisponibilidade()`; uma linha excluída pode entrar em CT-11 como esquema) |
| CRUD combinado | não se aplica |
| Mass assignment | não se aplica: o formulário é o do Filament |
| Upload | não se aplica |
| Precisão monetária | não se aplica |
| **Open redirect** (linha nova, desta feature) | CT-15 (linha host externo) |
| **Laço de redirecionamento** (linha nova) | CT-06, CT-07 |

## Índice de Cenários

| ID | Cenário | Regra | Técnica | Camada | Arquivo | Mata |
|----|---------|-------|---------|--------|---------|------|
| CT-01 | de fábrica: desligada, login por painel | R1 | EP / literal do requisito | Kit (HTTP + config) | `tests/Kit/LoginUnificadoTest.php` | M-A2 |
| CT-02 | valores do `.env` (7 linhas) | R1 | EP | Kit (config) | idem | M-A1, M-A2 |
| CT-03 | toggle grava e governa | R1 | gravação por componente + efeito | Kit (Livewire + HTTP) | idem | M-A3, M-A4 |
| CT-04 | telas por painel × chave (6 linhas) | R2 | EP | Kit (HTTP) | idem | M-B3, M-B4 |
| CT-05 | fluxos vizinhos terminam em `/login` (5 linhas) | R2 | rastreio por origem | Kit (HTTP) | idem | M-B5 |
| CT-06 | `/login` desligada → default, sem laço | R2 | controle negativo | Kit (HTTP) | idem | M-B2 |
| CT-07 | `/login` ligada responde a tela | R2 | controle negativo | Kit (HTTP) | idem | M-B1 |
| CT-08 | entra quem acessa algum painel (5 linhas) | R3 | matriz papel × resultado | Kit (Livewire) | idem | M-C1, M-C3 |
| CT-09 | sem papel: erro genérico, sem sessão, log | R3 | não-efeito | Kit (Livewire + spy) | idem | M-C2, M-C4, M-C5 |
| CT-10 | senha errada, três painéis | R3 | negativo | Kit (Livewire) | idem | (controle) |
| CT-11 | conta inativa explicada | R3 | regressão do embrulho | Kit (Livewire) | idem | M-C6 |
| CT-12 | um painel → direto (3 linhas) | R4 | tabela de decisão | Kit (Livewire) | idem | M-D4, M-D5, M-D7 |
| CT-13 | ≥2 → escolha (2 linhas) | R4 | tabela de decisão | Kit (Livewire) | idem | M-D7 |
| CT-14 | pretendida acessível vence (3 linhas) | R4 | tabela de decisão | Kit (Livewire) | idem | M-D2 |
| CT-15 | pretendida inacessível/enganosa/externa descartada (4 linhas) | R4 | tabela de decisão | Kit (Livewire) | idem | M-D1, M-D3 |
| CT-16 | pretendida consumida | R4 | rastreio de efeito | Kit (Livewire) | idem | M-D6 |
| CT-17 | cartões só dos acessíveis (2 linhas) | R5 | matriz | Kit (HTTP) | idem | M-E1, M-E2, M-E6 |
| CT-18 | um painel / anônimo / chave desligada (3 linhas) | R5 | matriz | Kit (HTTP) | idem | M-E3, M-E4 |
| CT-19 | zero painéis encerra a sessão | R5 | não-efeito | Kit (HTTP + spy) | idem | M-E5 |
| CT-20 | callback social segue a regra (2 linhas) | R6 | EP | Kit (HTTP + Socialite falso) | idem | M-F1, M-F2 |
| CT-21 | botão com/sem `painel=` (2 linhas) | R6 | EP | Kit (HTTP) | idem | M-F3, M-F4 |
| CT-22 | caminhos cobertos pelo `kit:update` (2 linhas) | R7 | lista | Kit | `tests/Kit/KitUpdateTest.php` | M-G1, M-G2 |
| CT-23…CT-30, CT-32 | cenários da revisão adversarial (rate limit, e-mail inexistente, chave desligada no destino, escolha desligada, 2FA, convite válido, pós-escolha, recusa social, `/login` autenticado) | R2…R6 | ver Rodada 1 | Kit | `tests/Kit/LoginUnificadoTest.php` | achados #1, #2, #4, #14, #18-#21, #23 |

**Resultado (2026-09-05)**: `tests/Kit/LoginUnificadoTest.php` **63/63**; CT-22 dentro de `KitUpdateTest` verde; CT-B01 verde (9 assertions).

CT-10 não mata mutante próprio; fica como **controle** de CT-08 (mesmo formulário, mesma persona, resultado oposto) — sem ele, um `isUserAllowedToAccessPanel()` que devolve `true` antes da senha passaria despercebido em CT-08.

## Divergência declarada com a skill

- A skill pede `vendor/bin/pest --parallel --tia` na verificação final; o projeto usa `composer test:kit` (Kit + Tenancy em `--parallel`, sem `--tia` — PCOV não é garantido) e `composer test:browser` em série (`.ai/rules/testes-browser.md`). Prevalecem os comandos do projeto.

## Revisão adversarial (perfil completo em R3)

Obrigatória para a área C. Executada por sub-agente que recebe **só** o `00-requisito.md` e este arquivo (sem PRD, sem código). Achados e fechamento registrados na seção abaixo após a rodada.

### Rodada 1 — 24 achados, todos fechados (2026-09-05)

Sub-agente recebeu só o `00` e este arquivo. Fechamento:

| # | Achado | Destino |
|---|---|---|
| 1 | página única sem rate limit / `Timebox` | **CT-23** — 5 erros e a 6ª tentativa recebe a notificação de excesso |
| 2 | e-mail inexistente (500 ou enumeração) | **CT-24** — erro genérico, sem sessão (2 linhas) |
| 3 | pretendida avaliada por papel, não por `canAccessPanel()` — `master_global` | linha nova em **CT-14** (`master_global`, `/app/users`) |
| 4 | resposta unificada aplicada com a chave desligada | **CT-25** — login por `/admin/login` e `/infra/login` desligado vai ao painel do login |
| 5 | `/login` servindo a tela antiga | **CT-07** e **CT-05** ganham `assertSeeLivewire(TelaLoginUnificada)`; CT-08 ganha `assertRedirect()` |
| 6, 7 | `Então` de layout em CT-07/CT-01 | CT-01 e CT-04 (desligada) afirmam o componente `TelaLogin`; CT-07 o componente único |
| 8 | CT-08 sem redirect | `assertRedirect()` em CT-08 |
| 9 | CT-17 `master_global` sem contagem | cada rótulo aparece **exatamente uma vez** (`substr_count`) e a URL do painel está no HTML |
| 10 | CT-16 sem destino | CT-16 afirma o destino, a sessão vazia e o **segundo** login sem herança |
| 11 | logout sem `assertGuest` | CT-05 (logout) ganha `assertGuest()` |
| 12 | CT-11 sem não-efeito | `assertGuest()` em CT-11 (o log da recusa explicada é da `TelaLogin`, coberto em `SituacaoDaContaTest`) |
| 13 | CT-03 `Então` 2 redundante | removido; CT-03 passa a **ligar e desligar** (achado 22) |
| 14 | CT-18 linha contraditória | virou **CT-26** `@premissa` |
| 15, 16 | dois `Quando` em CT-03/CT-05 | CT-03: a gravação é `Dado`; CT-05: `followingRedirects()` é uma ação só, com o `Então` no destino final |
| 17 | CT-10 controle; M-C7 sem matador | mantidos e declarados (timing não é medido na suíte) |
| 18 | RQ-07 2FA sem conta com segundo fator | **CT-27** — 2FA confirmado no `/admin`: login em `/login` → escolha; `GET /admin` → desafio (`two-factor`). A escolha antes do desafio é comportamento aceito e **documentado** (a tela só lista painéis) |
| 19 | convite válido quebrado pelo redirect | **CT-28** — `/app/register?token=…` responde 200 com a chave ligada |
| 20 | RQ-08 "não aparece em outro momento" | **CT-29** — depois de escolher, `/admin` e `/infra` respondem 200 sem voltar à escolha |
| 21 | recusa no login social | **CT-30** — conta social sem painel termina deslogada em `/login`. **Achou defeito de desenho**: zero painéis autenticado ia ao login e a página única devolveria à escolha — laço; a decisão de zero painéis passou a ser sempre da `EscolhaDePainel` (encerra a sessão) |
| 22 | toggle só liga | CT-03 desliga também |
| 23 | `/login` autenticado | **CT-32** — vai ao destino |
| 24 | cartão leva ao painel | CT-B01 (clique) + CT-29 (HTTP) |

Re-revisão: não executada — os cenários novos são linhas de regras existentes e o fechamento do #21 mudou implementação, não regra. Registrado como decisão.
