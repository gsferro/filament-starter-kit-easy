# Casos de Teste — Desbloqueio da sessão volta para a página em que a pessoa estava

> Requisito: `00-requisito.md` · Plano: `01-plano-acao.md`
> Derivado do **requisito**, não do plano. Nenhum cenário foi escrito olhando implementação.
> Revisão adversarial: **FEITA** por `fw-adversario-ct` (contexto novo, só `00` + `04`) — achados ADV-01…ADV-24; aceitos incorporados nesta versão (CT-23…CT-31, M39…M58 e endurecimento de CT-01, CT-05…CT-08, CT-10, CT-14, CT-15, CT-17, CT-18, CT-21, CT-22); ADV-07, ADV-08 e ADV-14 rejeitados, em `## Cogitado e cortado`. O fechamento criou cenário novo: a re-revisão única (teto de 2 rodadas) cabe à sessão principal. Revisão do diff (step 9): RD-01/CX-01, CR-04, P-07, P-08 e P-09 incorporados — CT-32…CT-35, CT-11 e CT-27 endurecidos, M59…M67; os CT nascem antes da correção e devem falhar com o código atual.
> Versões (lidas de `composer.lock`; `composer.phar` ausente e `composer show` não executado): Pest 5.1.1 · Filament 5.10.2 · Livewire 4.4.7 · Laravel 13.36.0 · `marjose123/filament-lockscreen` 3.4.1.

## Perfil de Derivação

| Área | P | I | P×I | Perfil |
|---|---|---|---|---|
| Retorno por senha (captura do pedido, fallback, ciclo, falha) | 2 | 3 | 6 | padrão (Impacto 3: dispara adversarial) |
| Retorno por provedor social (callback externo, sessão travada) | 3 | 3 | 9 | completo |
| Guarda da tela de bloqueio e continuidade da sessão destravada | 2 | 3 | 6 | padrão |
| Fronteira de organização (`/app/{slug}`) | 2 | 3 | 6 | padrão |
| Link de vínculo do e-mail, organização da sessão e logout forçado (P-07, P-08, P-09) | 2 | 3 | 6 | padrão |

- Técnicas aplicadas: EP (painel; tipo de requisição; mecanismo de desbloqueio), tabela estado × evento (16 células, abaixo), 2-switch (ciclo de volta), exemplo discriminante (Referer ≠ URL pedida, query string, painel ≠ painel padrão), rastreio de efeito (consumo da página guardada: aconteceu / não aconteceu na falha / uma só vez), idempotência (segundo desbloqueio), superfície Livewire (valor fora do domínio e tipo errado).
- Técnica escalada acima do perfil: tabela estado × evento completa em área `padrão` — o defeito de "tela raiz" mora justamente nas células de ciclo (S3), que a amostragem não alcança.
- Cenários: 35 · Regras: 13 · Mutantes previstos: 67 · Sem matador: 0
<!-- derivado do arquivo por grep -c; recalcular a cada cenário novo -->

## Varredura SFDIPOT

| Letra | O que existe nesta feature | Cenários gerados |
|---|---|---|
| S | middleware do bloqueio por inatividade, tela de bloqueio (`TelaBloqueio`), callback social (`LoginSocialController::retorno`), sessão (marca de trava) — nenhuma tabela nova | CT-01…CT-21 |
| F | trava por inatividade, trava manual, desbloqueio por senha, desbloqueio por provedor, destino do desbloqueio, abertura da tela de bloqueio | CT-01…CT-18 |
| D | URL pedida (com query), Referer, destino guardado na sessão (dado do cliente que vira redirect), página de ciclo anterior, URL de outra organização | CT-01, CT-03, CT-10, CT-16, CT-20, CT-21 |
| I | rota `/{painel}/screen/lock`, `POST /{painel}/lock-session`, ação Livewire `authenticate`, `GET /auth/{provedor}/callback`, três painéis (um com tenancy) | CT-01, CT-02, CT-06, CT-14 |
| P | sessão `array` no teste (`phpunit.xml`); nenhuma dependência de banco além do usuário. Providers via Socialite fake | — (não se aplica: plataforma não altera o resultado) |
| O | pessoa com sessão ociosa; pessoa que só tem conta social; duplo clique no desbloqueio; requisições que não são abertura de página (JSON, Livewire) | CT-07, CT-08, CT-11, CT-15 |
| T | inatividade (marca de última atividade), segundo ciclo de trava, atividade renovada ao destravar | CT-10, CT-17 |

Fora do eixo T: a borda do tempo de inatividade (`LOCKSCREEN_IDLE_TIMEOUT`) é Fora de Escopo do `00`; os cenários usam um instante muito além do limite, e o `Dado` afirma o valor efetivo lido (`config('lockscreen.idle_timeout')`, default 1800 — `phpunit.xml` não o define).

## Mapa de Regras

| Regra | Área (perfil herdado) | Origem (`RQ`/`P-nn`) | Técnica | Cenários |
|---|---|---|---|---|
| R1 — Pedido de página interceptado por inatividade é o destino do desbloqueio por senha (URL inteira, a pedida e não a anterior) | retorno por senha (padrão) | RQ-01, RQ-02, RQ-03 | EP por painel + exemplo discriminante | CT-01…CT-03 |
| R2 — Enquanto travada, o destino é a última página que a pessoa tentou abrir; nunca a tela de bloqueio; o bloqueio manual segue valendo | retorno por senha (padrão) | RQ-02, RQ-03, P-03 | tabela estado × evento (S1/S2 × pedido) | CT-04…CT-06, CT-25 |
| R3 — Só o GET interceptado que não espera JSON conta como destino; JSON, chamada de componente e envio não-GET não (P-04, régua do *intended*) | retorno por senha (padrão) | RQ-03, P-04 | EP por tipo de requisição (cada inválida isolada) | CT-07, CT-08, CT-26, CT-27 (com Referer) |
| R4 — Sem página a que voltar, o desbloqueio vai à raiz do painel da tela de bloqueio, nunca à pública; nada vaza entre ciclos nem entre envios | retorno por senha (padrão) | RQ-01, P-02 | EP por painel + 2-switch + idempotência | CT-09…CT-11 (CT-11: duplo envio exato), CT-23, CT-24, CT-30 |
| R5 — Só o desbloqueio bem-sucedido leva à página; a falha não leva nem gasta o destino | retorno por senha (padrão) | RQ-02 | rastreio de efeito | CT-12, CT-13, CT-31 |
| R6 — O desbloqueio pelo provedor social também volta à página; outra conta não herda a página (P-06) | retorno por provedor social (completo) | P-01, P-06, RQ-02, RQ-03 | EP por situação de partida + exemplo discriminante | CT-14…CT-16, CT-22, CT-28, CT-29 |
| R7 — Depois do retorno a sessão está de fato destravada e ativa (a página abre, a tela não volta) | continuidade (padrão) | RQ-02 | EP por mecanismo de desbloqueio | CT-17 |
| R8 — A tela de bloqueio só aparece para a sessão travada | guarda da tela (padrão) | P-05, RQ-01 | EP por painel | CT-18 |
| R9 — Superfície do componente: o cliente não escolhe o destino nem destrava com tipo errado | retorno por senha (padrão) | RQ-03, RQ-02 | superfície Livewire (fora do domínio, tipo errado) | CT-19, CT-20 |
| R10 — O retorno não concede o que a organização nega | fronteira de organização (padrão) | RQ-03 | cenário escrito "como se a negativa fosse falsa" | CT-21 |
| R11 — O link de vínculo do e-mail, numa sessão travada, não é desbloqueio: só descarta a página guardada | link de vínculo (padrão) | P-07 | EP por configuração + 2-switch | CT-32, CT-33 |
| R12 — Sem página guardada, o desbloqueio volta à organização da sessão | fronteira de organização (padrão) | P-08 | discriminante de tenant | CT-34 |
| R13 — O logout forçado por excesso de tentativas não deixa página guardada | logout forçado (padrão) | P-09 | EP por situação de partida + rastreio de efeito | CT-35 |

Cobertura (sem recorte aberto; Q3 aberta no `00` e seguida como P-06):
- RQ-02 — coberta: CT-01, CT-04…CT-06, CT-12…CT-17, CT-22, CT-28, CT-29, CT-31 (a outra conta no provedor segue o destino de um login comum: P-06, Q3 aberta no `00` e seguida como premissa).
- RQ-03 — coberta: CT-01, CT-03…CT-08, CT-19…CT-21, CT-23…CT-27 (o bloqueio manual é `POST` sob CSRF; sem recorte aberto — fato, Q?2 retirada).
- RQ-01, P-01…P-09 — cobertas integralmente (RQ-01: R1, R4, R8; P-01 e P-06: R6; P-02: R4; P-03: R2; P-04: R3 (régua do *intended*: CT-07, CT-08, CT-26, CT-27); P-05: R8; P-07: R11; P-08: R12; P-09: R13).

### Matriz estado × evento da sessão (uma só; 4 estados × 4 operações = 16 células: 11 com CT novo, 2 com teste existente, 2 fora de escopo (S0×O4, S3×O4), 1 lacuna)

Estados: **S0** livre (nunca travada) · **S1** travada com página guardada · **S2** travada sem página guardada · **S3** destravada depois de ter travado. Operações: **O1** pedir uma página do painel · **O2** abrir a tela de bloqueio · **O3** desbloquear por senha certa · **O4** desbloquear por provedor.

| | O1 pedir página | O2 abrir tela de bloqueio | O3 senha certa | O4 provedor |
|---|---|---|---|---|
| **S0** | existente: `tests/Kit/BloqueioDeSessaoTest.php` ("não vaza o layout…", `GET /admin` com a marca falsa) — a feature não muda a navegação livre | existente: mesmo arquivo ("redireciona em vez de estourar…") | lacuna declarada: sem a tela montada não há `authenticate` a chamar; forjar snapshot Livewire fora do escopo, a feature não altera a ação | não se aplica: é o login social comum (Fora de Escopo) |
| **S1** | CT-04 (linha com página guardada), CT-08 | CT-05 | CT-01, CT-02, CT-03, CT-06, CT-12, CT-13 | CT-14, CT-16 |
| **S2** | CT-04 (linha sem página), CT-07 | CT-09 (tela aberta direto, sem pedido) | CT-09 | CT-15 |
| **S3** | CT-17 | CT-18 | CT-11 (segundo envio), CT-10 (novo ciclo) | não se aplica: é o login social comum (Fora de Escopo) |

Legenda auditada: toda célula com CT afirma o **destino** (o valor concreto) e o efeito de consumo da página guardada que a operação dispara no caminho feliz (CT-10, CT-11, CT-13); as de recusa (CT-12) afirmam não-redirecionamento e a marca de trava mantida. S0×O3 é a única lacuna.

## Costuras de Teste

| Grupo | Regras | Costura | Existente ou nova | Por quê esta camada | Confirmada |
|---|---|---|---|---|---|
| G1 — Retorno por senha (Kit: `admin`, `infra`, `app` sem tenancy) | R1…R5, R9, R13 | componente Livewire/Filament | existente — `tests/Kit/BloqueioDeSessaoTest.php` (`Livewire::test(TelaBloqueio::class)` + `Filament::setCurrentPanel`) | o `Então` é o destino do `authenticate` (`assertRedirect`); a captura do pedido entra por `$this->get()` como arranjo por transição real || sessão, 2026-10-20 — convenção do arquivo existente; autorização do pedido |
| G2 — Retorno por senha com tenancy (`/app/{slug}`) | R1, R10, R12 | componente Livewire/Filament | nova — `tests/Tenancy/BloqueioComRetornoTenancyTest.php` (CT-02, CT-21, CT-30, CT-34): o `/app` com organização só existe no `TenancyTestCase` (`.ai/rules/testes.md`, `admin_app` e `permission.teams`) | mesma API do G1, em outra suíte; o `Pest.php` liga cada pasta a um `TestCase` || sessão, 2026-10-20 — convenção do arquivo existente; autorização do pedido |
| G3 — Guarda e continuidade da sessão (HTTP) | R7, R8 | Pest feature HTTP | existente — `tests/Kit/BloqueioDeSessaoTest.php` (`$this->get(route("lockscreen.{$painel}.page"))`) | o `Então` é redirecionamento e status de uma rota, sem componente || sessão, 2026-10-20 — convenção do arquivo existente; autorização do pedido |
| G4 — Retorno por provedor social (e login comum de outra conta, CT-29) | R6, R7, R11 | Pest feature HTTP | existente — `tests/Kit/BloqueioDeSessaoTest.php` caso "destrava a sessão bloqueada na volta do provedor" (`Socialite::fake` + `ligarProvedor`) | a rota de callback é HTTP puro, fora do componente de UI (gate de camada da regra) || sessão, 2026-10-20 — convenção do arquivo existente; autorização do pedido |

Nenhum grupo com costura `browser`. Arquivos de teste: o G1/G3/G4 entram em `tests/Kit/BloqueioDeSessaoTest.php`; o G2 em arquivo novo da suíte `Tenancy`.

## Fronteira com o Plano

| Item do PRD | Recusado como oráculo porque | Destino |
|---|---|---|
| nome da classe do middleware, o bind no container, o canal e a mensagem de log | escolha de implementação; log não é cláusula (Proibição 12) | detalhe: nenhum cenário nomeia a classe nem lê log |
| a chave de sessão onde o destino é guardado | escolha de implementação; o observável é o redirecionamento do desbloqueio | detalhe do cenário (o `Dado` produz o destino por requisição real, nunca gravando a chave) |
| qual helper devolve a raiz do painel | escolha de implementação | o `Então` usa o caminho do painel (`/admin`, `/infra`, `/app`) |
| a condição exata que o plano usa para decidir se o desbloqueio social é um desbloqueio | escolha de implementação; comportamento visível para outra pessoa não é determinado pelo `00` | pergunta Q3, seguida como P-06 |
| os ramos *Idle* e *Normal* do vendor | fato de vendor; serve só para materializar o `Dado` (como travar), não define o esperado | `## Contexto` do `01` usado para nomes e rotas |
| "o login comum continua como hoje" (Fora de Escopo) | o `00` não escreve o destino de hoje; derivá-lo exigiria ler o código | M24 agora morto por CT-29 (oráculo: destino ≠ página de A); as suítes existentes de login social (`tests/Kit/LoginSocial*Test.php`, `LoginUnificadoTest.php`) fazem de regressão e devem seguir verdes |

**Perguntas geradas pela derivação** (ambas já tratadas pela sessão):

❓ Q3 (era Q?1; aberta no 00, seguida como P-06) · raia: requisito · afeta: RQ-02 · depende de: —
Numa sessão travada, se quem volta do provedor é uma conta social de **outra** pessoa (não a dona da sessão), o sistema trata isso como desbloqueio e leva à página que a primeira pessoa tentava abrir?
➡️ Recomendação: não — a página guardada pertence à sessão da primeira pessoa e não é entregue a outra; a outra conta segue o destino do login comum — falha fechado: o retorno só vale para a mesma identidade que travou.

❓ Q?2 — **retirada — fato: CSRF no POST lock-session** (`POST /{painel}/lock-session` sob o middleware `web`; site externo não o submete e o Referer legítimo é página do app) · afeta: RQ-03, sem recorte aberto
O destino do desbloqueio pode apontar para um endereço fora da aplicação (quando o bloqueio manual é disparado com origem externa)?
➡️ Recomendação: não — o retorno só leva a páginas do próprio sistema; sem página válida, vale a raiz do painel (P-02) — falha fechado. O invariante já tem cenário no caminho de inatividade (CT-03).

## Setup Global

### Personas
- Kit: `usuarioMaster()` — local de `tests/Kit/BloqueioDeSessaoTest.php` (senha `password`), enquanto só esse arquivo o usa.
- Tenancy: `usuarioComPapel('panel_user', $acme)` com `organizacaoComRegistro('acme')` e uma segunda organização `globex` à qual a pessoa **não** pertence; a senha do helper `usuario()` deve ser conferida pelo executor.

### Fixtures
- Sessão ociosa: `session(['session_last_activity' => time() - (config('lockscreen.idle_timeout') + 60)])`, com o `Dado` afirmando o `config('lockscreen.idle_timeout')` efetivo.
- **Situação de partida por transições reais.** Helper `travadaPorInatividadeAoPedir(string $url, array $cabecalhos = [])` em `tests/Pest.php` (usado por `tests/Kit` e `tests/Tenancy`, logo fora do arquivo): autentica, torna a sessão ociosa, faz `$this->withHeaders($cabecalhos)->get($url)` e confirma o redirecionamento para a tela de bloqueio do painel. Nunca gravar a chave de destino nem `lockscreen` à força quando o cenário depende do destino. O bloqueio manual usa `$this->from($pagina)->post("/{$painel}/lock-session")`.
- Estado S2 (travada sem página): `session(['lockscreen' => true])` direto, sem pedido, é legítimo porque o cenário É a tela aberta direto.

### Fakes
- `Socialite::fake('google', UsuarioDoProvedor::fake([...]))` + `ligarProvedor(ProvedorSocial::Google)` (padrão do caso existente); sem `Http::fake`.

### Estratégia de DB
- `RefreshDatabase` ligado por pasta em `tests/Pest.php` (`Kit` e `Tenancy`); `beforeEach` semeia `ShieldPermissionsSeeder` e `PapeisSeeder`.
- Teste de componente: `Filament::setCurrentPanel($painel)` antes do `Livewire::test(TelaBloqueio::class)`; no `/app` com tenancy, boot por um `GET` real antes do primeiro Livewire (`.ai/rules/testes.md`).

---

## Regra R1 — Pedido de página interceptado por inatividade é o destino do desbloqueio por senha

> `RQ-01`, `RQ-02`, `RQ-03` · perfil **padrão** · técnica: **EP por painel + exemplo discriminante** (o `Referer` difere da URL pedida; a URL tem query string)

```gherkin
# language: pt
Funcionalidade: Desbloqueio volta para a página em que a pessoa estava

  Regra: Pedido de página interceptado por inatividade é o destino do desbloqueio por senha

    Esquema do Cenário: [CT-01] a sessão travada por inatividade volta à página pedida, com a query, e não à última visitada
      Dado a inatividade efetiva é a de config('lockscreen.idle_timeout') e o usuário do <painel> visitou "<anterior>" com a sessão ativa, com resposta 200
      E a sessão ficou ociosa e foi travada ao pedir "<pedido>" com o Referer "<anterior>"
      Quando o usuário informa a senha correta na tela de bloqueio do <painel>
      Então o desbloqueio redireciona exatamente para "<pedido>" (query equivalente, comparada decodificada)
      E a marca de trava da sessão é falsa e o usuário continua autenticado

      Exemplos:
        | painel | pedido                                                   | anterior          | # partição                          |
        | admin  | /admin/users?q=ana                                       | /admin/shield/roles | painel admin                      |
        | infra  | /infra/execucoes-ia?q=ana                                | /infra            | painel infra                        |
        | app    | /app/users?q=ana                                         | /app              | painel app (sem tenancy, suíte Kit) |
        | admin  | /admin/users?tableFilters[status][value]=ativo&tableSearch=ana | /admin/shield/roles | query de array dos filtros do Filament |

    Cenário: [CT-02] com tenancy, a sessão travada por inatividade volta à página da organização pedida
      Dado a sessão da pessoa da organização acme está ociosa
      E a sessão foi travada ao pedir "/app/acme/users" vindo do Referer "/app/acme"
      Quando a pessoa informa a senha correta na tela de bloqueio do painel app
      Então o desbloqueio redireciona exatamente para "/app/acme/users"
      E a marca de trava da sessão é falsa

    Cenário: [CT-03] um Referer de fora do sistema nunca vira destino
      Dado o usuário do painel admin visitou "/admin/shield/roles" com a sessão ativa e a sessão ficou ociosa
      E a sessão foi travada ao pedir "/admin/users" vindo do Referer "https://externo.example/phishing"
      Quando o usuário informa a senha correta na tela de bloqueio do painel admin
      Então o desbloqueio redireciona exatamente para "/admin/users"
      E nenhum redirecionamento do desbloqueio aponta para o host "externo.example"
```

> Caminhos conferidos em `php artisan route:list --method=GET`: `/admin/users`, `/admin/shield/roles`, `/infra/execucoes-ia` e `/app/users` existem; os dois últimos painéis, sem tenancy na suíte Kit. Discriminantes fixados: query `?q=ana`, Referer e visita real ≠ pedido.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M1 | guarda o Referer (página anterior) em vez da URL pedida | CT-01 (as três linhas) | `assertRedirect('/admin/users?q=ana')`: o mutante redireciona a `/admin` |
| M2 | guarda só o caminho e perde a query string | CT-01 | o destino exato inclui `?q=ana`; o mutante redireciona a `/admin/users` |
| M3 | não guarda nada na inatividade (comportamento de hoje) | CT-01, CT-02 | o destino é o pedido; o mutante redireciona a `/` |
| M4 | aceita o Referer externo como destino | CT-03 | destino `/admin/users` e nenhum host `externo.example`; o mutante leva a `https://externo.example/phishing` |
| M5 | em painel com tenancy guarda a raiz do painel em vez da página da organização | CT-02 | destino `/app/acme/users`; o mutante redireciona a `/app` ou `/app/acme` |

---

## Regra R2 — Enquanto travada, o destino é a última página que a pessoa tentou abrir; a tela de bloqueio não é destino

> `RQ-02`, `RQ-03`, `P-03` · perfil **padrão** · técnica: **tabela estado × evento** (S1 e S2 × pedido; S1 × abrir tela)

```gherkin
    Esquema do Cenário: [CT-04] a pessoa já travada que abre outra página volta a essa última página
      Dado a sessão travada <partida>
      E a pessoa pediu "/admin/shield/roles" estando travada
      Quando o usuário informa a senha correta na tela de bloqueio do painel admin
      Então o desbloqueio redireciona exatamente para "/admin/shield/roles"
      E o destino não é a tela de bloqueio nem "/admin/users"

      Exemplos:
        | partida                                                | # estado |
        | sem página guardada (tela aberta direto)               | S2       |
        | com "/admin/users" pedida antes por inatividade        | S1       |

    Esquema do Cenário: [CT-05] abrir a tela de bloqueio depois do redirecionamento não troca o destino
      Dado a sessão foi travada por inatividade ao pedir "/admin/users"
      E a tela de bloqueio "<tela>" foi aberta depois do redirecionamento
      Quando o usuário informa a senha correta na tela de bloqueio do painel admin
      Então o desbloqueio redireciona exatamente para "/admin/users"
      E o destino não é nenhuma tela de bloqueio

      Exemplos:
        | tela                  | # partição        |
        | /admin/screen/lock    | painel da trava   |
        | /infra/screen/lock    | outro painel      |

    Cenário: [CT-06] o bloqueio manual a partir de uma página, com query, volta a essa página inteira
      Dado o usuário do painel admin está na página "/admin/users?q=ana" com a sessão ativa
      E a sessão foi travada pelo botão de bloquear, com origem "/admin/users?q=ana"
      Quando o usuário informa a senha correta na tela de bloqueio do painel admin
      Então o desbloqueio redireciona exatamente para "/admin/users?q=ana"
      E a marca de trava da sessão é falsa

    Cenário: [CT-25] query na própria tela de bloqueio não escolhe o destino
      Dado a sessão foi travada por inatividade ao pedir "/admin/users"
      E a tela de bloqueio foi aberta em "/admin/screen/lock?redirect=https://externo.example/x&intended=/infra"
      Quando o usuário informa a senha correta na tela de bloqueio do painel admin, com a mesma query na montagem do componente e na ação authenticate
      Então o desbloqueio redireciona exatamente para "/admin/users"
      E nenhum redirecionamento aponta para o host "externo.example"
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M6 | a abertura da tela de bloqueio regrava o destino com ela mesma (laço tela → tela) | CT-05 | destino `/admin/users`; o mutante redireciona à tela de bloqueio |
| M7 | só a primeira página interceptada é guardada; pedidos seguintes não atualizam | CT-04 (linha S1) | destino `/admin/shield/roles`; o mutante redireciona a `/admin/users` |
| M8 | a troca da barreira perde o caminho nativo do bloqueio manual | CT-06 | destino `/admin/users?q=ana`; o mutante redireciona a `/` ou à raiz do painel |
| M9 | com a sessão já travada, o pedido guarda o "anterior" (a própria tela de bloqueio) em vez da URL pedida | CT-04 (linha S2) | destino `/admin/shield/roles`; o mutante redireciona à tela de bloqueio |
| M53 | o bloqueio manual guarda só o caminho e perde a query | CT-06 | destino exato `/admin/users?q=ana`; o mutante redireciona a `/admin/users` |
| M54 | abrir a tela de bloqueio de outro painel regrava o destino | CT-05 (linha `/infra/screen/lock`) | destino `/admin/users`; o mutante redireciona a uma tela de bloqueio ou `/infra` |
| M43 | a tela de bloqueio lê `redirect`/`intended` da própria query como destino | CT-25 | destino `/admin/users`; o mutante redireciona a `https://externo.example/x` ou `/infra` |

> Arnês de CT-24 e CT-25: `Livewire::test(TelaBloqueio::class)` com `withHeaders(["Referer" => …])` / `withQueryParams([...])` equivalentes valem para a montagem E para a chamada de `authenticate` — o mesmo Referer/query nos dois.

> R2 tem 4 cenários (teto padrão: 3): o gate vence o teto — CT-25 e a linha extra de CT-05 têm matador próprio (M43, M54) que nenhum outro cenário mata.

---

## Regra R3 — Só o GET interceptado que não espera JSON conta como destino (régua do *intended* do Laravel)

> `RQ-03`, `P-04` · perfil **padrão** · técnica: **EP por tipo de requisição**, cada partição inválida isolada

```gherkin
    Esquema do Cenário: [CT-07] requisição que não é GET navegável não vira destino
      Dado o usuário do painel admin visitou "/admin/shield/roles" com a sessão ativa e a sessão ficou ociosa
      E a sessão foi travada por <requisição> com o Referer "/admin/shield/roles"
      E a marca de trava é verdadeira e a tela de bloqueio do painel admin abre sem redirecionar
      Quando o usuário informa a senha correta na tela de bloqueio do painel admin
      Então o desbloqueio redireciona exatamente para "/admin"
      E o destino não é o endereço da requisição que travou a sessão nem "/admin/shield/roles"

      Exemplos:
        | requisição                                                         | # partição       |
        | um GET a "/admin/users" que espera JSON (Accept: application/json) | JSON             |
        | uma chamada de componente Livewire (cabeçalho X-Livewire)          | Livewire (POST)  |

    Cenário: [CT-08] uma chamada de componente com a sessão já travada não troca a página guardada
      Dado a sessão foi travada por inatividade ao pedir "/admin/users"
      E uma chamada de componente Livewire chegou com a sessão já travada e o Referer "/admin/shield/roles"
      Quando o usuário informa a senha correta na tela de bloqueio do painel admin
      Então o desbloqueio redireciona exatamente para "/admin/users"
      E o destino não é o endereço da chamada de componente nem "/admin/shield/roles"

    @obsoleto
    # Motivo (2026-10-20, executor-ct): `route:list --path=admin --method=POST` sem logout, lock-session e /livewire/* não deixa
    # nenhuma rota que atravesse a barreira do bloqueio; o pacote, além disso, só intercepta GET (os dois ramos do `Locker`
    # começam por `isMethod('GET')`). Sem arnês, o cenário passaria sem exercitar nada.
    Esquema do Cenário: [CT-26] envio não-GET a uma rota do painel não vira destino
      Dado a sessão <partida>
      E um POST de formulário chegou à rota POST "<rota_post>" do painel admin com a sessão travada ou ociosa
      E a marca de trava é verdadeira e a tela de bloqueio do painel admin abre sem redirecionar
      Quando o usuário informa a senha correta na tela de bloqueio do painel admin
      Então o desbloqueio redireciona exatamente para "<destino>"
      E o destino não é o endereço do POST

      Exemplos:
        | partida                                         | destino      | # partição        |
        | está ociosa, sem página guardada                | /admin       | S0 → trava pelo POST |
        | foi travada ao pedir "/admin/users"             | /admin/users | S1, POST depois   |

      Em `<rota_post>` o executor escolhe, de `php artisan route:list --path=admin --method=POST`, uma rota POST do painel que NÃO seja o logout, o `lock-session` nem qualquer `/livewire/*`; se nenhuma sobrar, a lacuna é declarada com o que foi tentado.

    Esquema do Cenário: [CT-27] um GET que espera JSON com a sessão já travada não troca a página guardada, com ou sem Referer
      Dado a sessão foi travada por inatividade ao pedir "/admin/users"
      E um GET a "/admin/users" que espera JSON chegou com a sessão já travada, <referer>
      Quando o usuário informa a senha correta na tela de bloqueio do painel admin
      Então o desbloqueio redireciona exatamente para "/admin/users"
      E o destino não é o Referer enviado nem "/admin/shield/roles"

      Exemplos:
        | referer                                      | # partição |
        | sem Referer                                  | sem        |
        | com o Referer "/admin/shield/roles"          | interno    |
        | com o Referer "https://externo.example/x"    | externo    |
```

> Nota de arnês: o executor confirma que a chamada Livewire, o GET JSON e o POST atravessam a barreira do painel no teste; se o arnês não os fizer chegar à barreira, tentar `Livewire::test` numa página do painel ou `$this->postJson` com o cabeçalho `X-Livewire` antes de declarar lacuna.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M10 | guarda todo pedido, inclusive o que espera JSON | CT-07 (linha JSON) | destino `/admin`; o mutante redireciona ao endereço do JSON |
| M11 | filtra JSON mas guarda a chamada Livewire | CT-07 (linha Livewire) | destino `/admin`; o mutante redireciona ao endereço da chamada de componente |
| M12 | em sessão já travada, qualquer pedido (inclusive Livewire) sobrescreve a página guardada | CT-08 | destino `/admin/users`; o mutante redireciona ao endereço do componente |
| M44 | o POST com a sessão ociosa vira destino (o método não é conferido) | equivalente — o pacote só intercepta GET; nenhum POST chega ao ramo que grava (CT-26 @obsoleto) | destino `/admin`; o mutante redireciona ao endereço do POST |
| M45 | o POST com a sessão já travada sobrescreve a página guardada | equivalente — idem M44 (CT-26 @obsoleto) | destino `/admin/users`; o mutante redireciona ao endereço do POST |
| M59 | em sessão já travada, o GET que espera JSON com Referer diferente sobrescreve a página guardada (a régua usa o "anterior" do request) | CT-27 (linhas com Referer) | destino exato `/admin/users`; o mutante redireciona ao Referer enviado (`/admin/shield/roles` ou `https://externo.example/x`) |
| M46 | em sessão já travada, o GET que espera JSON sobrescreve a página guardada | CT-27 | destino `/admin/users`; o mutante redireciona a `/admin/shield/roles` |
| M56 | a barreira deixa de travar a sessão para requisição JSON/Livewire (a marca fica falsa) | CT-07 | no `Dado`, `session('lockscreen')` é `true` e a tela abre sem redirecionar; o mutante deixa `false` |

> R3 tem 4 cenários (teto padrão: 3): são partições distintas do tipo de requisição, cada uma com matador próprio (M44…M46, M56); o gate vence o teto.

---

## Regra R4 — Sem página a que voltar, o desbloqueio vai à raiz do painel; nada vaza entre ciclos nem entre envios

> `RQ-01`, `P-02` · perfil **padrão** · técnica: **EP por painel + 2-switch + idempotência** (agregado: o destino do segundo evento)

```gherkin
    Esquema do Cenário: [CT-09] a tela de bloqueio aberta direto leva à raiz do painel, nunca à página pública
      Dado a sessão do usuário do <painel> está travada sem nenhum pedido de página
      E a tela de bloqueio do <painel> foi aberta
      Quando o usuário informa a senha correta na tela de bloqueio do <painel>
      Então o desbloqueio redireciona exatamente para "<raiz>"
      E o destino não é "/", que é a página pública de boas-vindas

      Exemplos:
        | painel | raiz   | # partição |
        | admin  | /admin | admin      |
        | infra  | /infra | infra      |
        | app    | /app   | app (Kit, sem tenancy) |

    Cenário: [CT-10] o segundo ciclo de trava não herda a página do primeiro
      Dado a sessão foi travada por inatividade ao pedir "/admin/users" e destravada com a senha, voltando a "/admin/users"
      E a marca de trava foi forçada de novo na sessão do usuário do painel admin, sem nenhum pedido de página, e a tela de bloqueio foi aberta
      Quando o usuário informa a senha correta na tela de bloqueio do painel admin
      Então o desbloqueio redireciona exatamente para "/admin"
      E o destino não é "/admin/users"

    Cenário: [CT-11] o duplo envio da senha correta leva, nos dois envios, à página guardada
      Dado a sessão foi travada por inatividade ao pedir "/admin/users?q=ana"
      Quando a senha correta é enviada duas vezes seguidas no mesmo componente da tela de bloqueio do painel admin
      Então o primeiro envio redireciona exatamente para "/admin/users?q=ana"
      E o segundo envio redireciona exatamente para "/admin/users?q=ana"
      E nenhum dos dois redireciona para "/"

    Esquema do Cenário: [CT-23] depois de uma visita real, a trava sem pedido de página vai à raiz, não à última visitada
      Dado o usuário do painel admin visitou "/admin/shield/roles" com a sessão ativa, resposta 200
      E a sessão foi travada por <trava>
      E a marca de trava é verdadeira e a tela de bloqueio do painel admin abre sem redirecionar
      Quando o usuário informa a senha correta na tela de bloqueio do painel admin
      Então o desbloqueio redireciona exatamente para "/admin"
      E o destino não é "/admin/shield/roles"

      Exemplos:
        | trava                                                                    | # partição        |
        | marca forçada na sessão e tela de bloqueio aberta direto                 | tela aberta direto |
        | um GET a "/admin/users" que espera JSON com a sessão ociosa              | JSON               |

    Esquema do Cenário: [CT-24] a tela aberta direto ignora o Referer, de dentro ou de fora do sistema
      Dado a marca de trava foi forçada na sessão do usuário do painel admin, sem pedido de página
      E a tela de bloqueio foi aberta com o Referer "<referer>"
      Quando o usuário informa a senha correta na tela de bloqueio do painel admin, com o mesmo Referer "<referer>" na montagem do componente e na ação authenticate
      Então o desbloqueio redireciona exatamente para "/admin"
      E o destino não é o Referer "<referer>"

      Exemplos:
        | referer                          | # partição |
        | /admin/shield/roles              | interno    |
        | https://externo.example/phishing | externo    |

    Cenário: [CT-30] com tenancy, sem página a que voltar, o desbloqueio fica no painel app e na organização da sessão
      Dado a pessoa da organização acme tem a marca de trava forçada, sem pedido de página, e a tela de bloqueio do painel app foi aberta
      Quando a pessoa informa a senha correta na tela de bloqueio do painel app
      E ao seguir o destino devolvido pelo desbloqueio, seguindo os redirecionamentos
      Então o desbloqueio redirecionou para um endereço que começa com "/app" e não é "/"
      E a resposta final foi 200 numa URL sob "/app/acme"
      E a organização da sessão continua acme
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M13 | sem página, o desbloqueio segue o padrão do framework e vai a `/` | CT-09 | destino igual à raiz do painel; o mutante redireciona a `/` |
| M14 | o fallback usa o painel padrão e não o painel da tela de bloqueio | CT-09 (linhas `infra`, `app`) | destino `/infra` na linha `infra`; o mutante redireciona a `/admin` |
| M15 | a página do ciclo anterior não é gasta e volta num novo bloqueio sem pedido | CT-10 | destino `/admin`; o mutante redireciona a `/admin/users` |
| M16 | o segundo envio, depois de a página ter sido gasta, cai em `/` | CT-11 | segundo envio: destino exato `/admin/users?q=ana`; o mutante redireciona a `/` |
| M60 | o segundo envio, depois de a página ter sido gasta, cai na raiz do painel | CT-11 | segundo envio: destino exato `/admin/users?q=ana`; o mutante redireciona a `/admin` |
| M39 | a tela aberta direto usa a última página visitada com 200 como destino | CT-23 (linha tela direto) | destino `/admin`; o mutante redireciona a `/admin/shield/roles` |
| M40 | a trava por JSON, depois de visita real, leva à última visitada | CT-23 (linha JSON) | destino `/admin`; o mutante redireciona a `/admin/shield/roles` |
| M41 | a tela aberta direto usa o Referer interno como destino | CT-24 (linha interno) | destino `/admin`; o mutante redireciona a `/admin/shield/roles` |
| M42 | a tela aberta direto usa o Referer externo como destino | CT-24 (linha externo) | destino `/admin`; o mutante redireciona a `https://externo.example/phishing` |
| M49 | no `/app` com tenancy, sem página, o fallback vai a `/` ou ao painel padrão | CT-30 | destino começa com `/app` e não é `/`; o mutante redireciona a `/` ou `/admin` |
| M50 | o fallback do `/app` leva a outra organização ou à raiz sem organização | CT-30 | seguir o destino responde 200 com organização `acme`; o mutante responde outra organização ou redireciona |

> R4 tem 6 cenários (teto padrão: 3) porque junta fallback (CT-09, CT-23, CT-24, CT-30), ciclo (CT-10) e idempotência (CT-11): a regra deveria ser duas, e a revisão adversarial trouxe os matadores M39…M42, M49, M50, que não contam para o teto (SKILL, passo 6, item 1). Desdobrar renumeraria a rastreabilidade; fica registrado.

---

## Regra R5 — Só o desbloqueio bem-sucedido leva à página; a falha não leva nem gasta o destino

> `RQ-02` · perfil **padrão** · técnica: **rastreio de efeito** (o efeito: levar à página e gastar o destino; direções: não leva na falha, não gasta na falha)

```gherkin
    Cenário: [CT-12] senha errada não leva a lugar nenhum e mantém a sessão travada
      Dado a sessão foi travada por inatividade ao pedir "/admin/users"
      Quando o usuário informa uma senha errada na tela de bloqueio do painel admin
      Então há erro de validação em "data.password" e nenhum redirecionamento
      E a marca de trava da sessão continua verdadeira

    Cenário: [CT-13] a senha errada não gasta a página guardada: a senha certa em seguida ainda volta a ela
      Dado a sessão foi travada por inatividade ao pedir "/admin/users"
      E uma senha errada já foi recusada na tela de bloqueio do painel admin
      Quando o usuário informa a senha correta na mesma tela
      Então o desbloqueio redireciona exatamente para "/admin/users"
      E a marca de trava da sessão é falsa

    Cenário: [CT-31] o provedor negado não destrava nem gasta a página: a senha certa em seguida ainda volta a ela
      Dado a sessão foi travada por inatividade ao pedir "/admin/users"
      E o provedor devolveu a pessoa ao callback com "?error=access_denied" e a marca de trava continua verdadeira
      Quando o usuário informa a senha correta na tela de bloqueio do painel admin
      Então o desbloqueio redireciona exatamente para "/admin/users"
      E a marca de trava da sessão é falsa
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M17 | a página guardada é consumida antes de validar a senha | CT-13 | após o erro, a senha certa redireciona a `/admin/users`; o mutante redireciona a `/admin` |
| M18 | redireciona à página mesmo com a senha errada | CT-12 | `assertNoRedirect()` e erro em `data.password`; o mutante redireciona |
| M19 | marca a sessão como destravada mesmo com a senha errada | CT-12 | `session('lockscreen')` continua `true`; o mutante deixa `false` |
| M51 | o callback do provedor com `error=access_denied` gasta a página guardada | CT-31 | a senha certa em seguida redireciona a `/admin/users`; o mutante redireciona a `/admin` |
| M52 | o callback do provedor com `error=access_denied` destrava a sessão | CT-31 | no `Dado`, `session('lockscreen')` continua `true` depois do callback; o mutante deixa `false` |

---

## Regra R6 — O desbloqueio pelo provedor social também volta à página

> `P-01`, `RQ-02`, `RQ-03` · perfil **completo** · técnica: **EP por situação de partida** (com página / tela aberta direto) + exemplo discriminante (painel ≠ painel padrão do usuário)

```gherkin
    Cenário: [CT-14] a volta do provedor destrava e leva à página pedida
      Dado o usuário do provedor "google" é o dono da sessão e a sessão foi travada por inatividade ao pedir "/admin/users?q=ana"
      Quando o provedor devolve a pessoa ao callback com a mesma conta
      Então o redirecionamento do callback é exatamente "/admin/users?q=ana"
      E a marca de trava da sessão é falsa e o usuário autenticado é a conta A, a mesma de antes de travar

    Esquema do Cenário: [CT-15] com a tela de bloqueio aberta direto, o provedor leva à raiz do painel dela
      Dado o usuário do provedor "google" é o dono da sessão e a tela de bloqueio do <painel> foi aberta direto, sem pedido de página
      Quando o provedor devolve a pessoa ao callback com a mesma conta e o Referer "https://accounts.google.com"
      Então o redirecionamento do callback é exatamente "<raiz>"
      E o destino não é "/" e a marca de trava da sessão é falsa

      Exemplos:
        | painel | raiz   | # partição |
        | admin  | /admin | admin      |
        | infra  | /infra | infra, diferente do painel padrão |

    Cenário: [CT-16] parâmetro de destino no callback é ignorado
      Dado o usuário do provedor "google" é o dono da sessão e a sessão foi travada por inatividade ao pedir "/admin/users"
      Quando o provedor devolve a pessoa a "/auth/google/callback?redirect=https://externo.example/x&intended=/infra"
      Então o redirecionamento do callback é exatamente "/admin/users"
      E nenhum redirecionamento aponta para o host "externo.example"

    Esquema do Cenário: [CT-22] outra conta no provedor não herda a página guardada da primeira pessoa
      Dado a sessão da conta A foi travada por inatividade ao pedir "/admin/users?q=ana"
      E a conta B (<conta_b>) existe, com acesso ao painel admin
      Quando o provedor devolve a pessoa ao callback com a conta B
      E ao seguir o destino devolvido pelo callback
      Então o redirecionamento do callback não foi "/admin/users?q=ana", nem "/admin/users", nem a tela de bloqueio
      E a sessão ficou destravada e autenticada como a conta B
      E a resposta ao seguir o destino foi 200

      Exemplos:
        | conta_b                              | # partição          |
        | outra conta do mesmo provedor        | mesmo provedor      |
        | conta de outro provedor ("github")   | outro provedor      |

    Cenário: [CT-28] a página da conta A não vaza para a conta B num bloqueio posterior sem pedido
      Dado a sessão da conta A foi travada ao pedir "/admin/users?q=ana" e a conta B destravou pelo provedor
      E a marca de trava foi forçada de novo na sessão da conta B, sem pedido de página, e a tela de bloqueio foi aberta
      Quando a conta B informa a senha correta na tela de bloqueio do painel admin
      Então o desbloqueio redireciona exatamente para "/admin"
      E o destino não é "/admin/users" nem "/admin/users?q=ana"

    Esquema do Cenário: [CT-29] a conta que entra depois de "Trocar de conta" não recebe a página da conta A
      Dado a sessão da conta A foi travada ao pedir "/admin/users?q=ana"
      E a conta A saiu pelo "Trocar de conta" (logout)
      Quando a conta B entra por <mecanismo>
      Então o destino do login não é "/admin/users?q=ana" nem "/admin/users"
      E a conta B fica autenticada

      Exemplos:
        | mecanismo                       | # partição |
        | senha, na tela de login comum   | senha      |
        | provedor, no callback           | provedor   |
```

> Origem P-06 (CT-22, CT-28, CT-29). O destino do login comum não é especificado pelo `00`: o oráculo é o discriminante (não é a página guardada de A, com ou sem query) mais o estado da sessão. Arnês de CT-29: a linha `senha` usa a tela de login do painel (`App\Filament\Pages\Auth\TelaLogin`); a linha `provedor`, o callback. R6 tem 7 cenários (teto completo: 5): o gate vence o teto, cada um com matador próprio.

> Raiz do painel com tenancy no social não entra: o `00` não decide o destino do provedor com tenancy e o `/app` do callback depende da organização (lacuna: tentado Kit, sem tenancy, no G4).

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M20 | o provedor ignora a página guardada e vai ao painel do usuário | CT-14 | destino `/admin/users?q=ana`; o mutante redireciona a `/admin` |
| M21 | sem página, o provedor vai ao painel padrão ou a `/` | CT-15 (linha `infra`) | destino `/infra`; o mutante redireciona a `/admin` ou `/` |
| M22 | o callback usa parâmetro da query como destino | CT-16 | destino `/admin/users`; o mutante redireciona a `https://externo.example/x` ou `/infra` |
| M23 | o desbloqueio social leva à página mas não destrava a sessão | CT-14 | `session('lockscreen')` é `false`; o mutante deixa `true` |
| M24 | o retorno à página se aplica também ao login comum depois de "Trocar de conta": o destino guardado sobrevive ao logout **fora da sessão** (cookie, cache) | CT-29 | o destino do login da conta B não é `/admin/users?q=ana` nem `/admin/users`; o mutante redireciona a eles. Mutante morto só para destino guardado fora da sessão: destino guardado na sessão some no logout por construção (a sessão é invalidada) e não discrimina |
| M36 | o retorno social usa a página guardada para qualquer conta que volte | CT-22 | o destino não é `/admin/users?q=ana`; o mutante redireciona a ela |
| M37 | a outra conta não destrava a sessão, ou a sessão segue autenticada como a conta A | CT-22 | `auth()->id()` é o da conta B e `session('lockscreen')` é `false`; o mutante mantém A ou `true` |
| M58 | o retorno da mesma conta autentica de novo como outra identidade | CT-14 | `auth()->id()` é o da conta A; o mutante autentica outra |
| M38 | a outra conta é mandada de volta à tela de bloqueio (laço) | CT-22 | destino não é a tela de bloqueio e seguir o destino responde 200; o mutante redireciona à tela |
| M47 | a página da conta A não é gasta na troca de conta e é entregue a B num bloqueio posterior | CT-28 | destino `/admin`; o mutante redireciona a `/admin/users?q=ana` |
| M48 | o login por senha da conta B, depois do "Trocar de conta", usa a página guardada de A | CT-29 (linha `senha`) | destino não é `/admin/users?q=ana`; o mutante redireciona a ela |
| M55 | o callback usa o Referer do provedor como destino | CT-15 | destino exato `/infra`/`/admin`; o mutante redireciona a `https://accounts.google.com` |

---

## Regra R7 — Depois do retorno a sessão está de fato destravada e ativa

> `RQ-02` · perfil **padrão** · técnica: **EP por mecanismo de desbloqueio** (senha, provedor); eixo T: a marca de última atividade da trava por inatividade

```gherkin
    Esquema do Cenário: [CT-17] seguir o retorno depois da trava por inatividade abre a página e não trava de novo
      Dado a sessão foi travada por inatividade ao pedir "/admin/users" e destravada por <mecanismo>, que devolveu um destino de retorno
      E o endereço devolvido pelo desbloqueio é exatamente "/admin/users"
      Quando o usuário abre o endereço devolvido pelo desbloqueio
      Então a resposta é a página, com status 200
      E a marca de trava da sessão continua falsa e a resposta não redireciona à tela de bloqueio

      Exemplos:
        | mecanismo     | # partição |
        | senha         | componente |
        | provedor      | callback   |
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M25 | o destravamento leva à página mas a atividade continua ociosa, e a barreira trava de novo (laço) | CT-17 (ambas) | `GET /admin/users` responde 200; o mutante redireciona à tela de bloqueio |
| M26 | só a senha renova a atividade; o provedor não | CT-17 (linha `provedor`) | 200 na linha `provedor`; o mutante redireciona à tela de bloqueio |
| M27 | a marca de trava permanece verdadeira depois do retorno | CT-17 | `session('lockscreen')` é `false`; o mutante deixa `true` |

---

## Regra R8 — A tela de bloqueio só aparece para a sessão travada

> `P-05`, `RQ-01` · perfil **padrão** · técnica: **EP por painel**; saída do estado de erro afirmada

```gherkin
    Esquema do Cenário: [CT-18] com a sessão já destravada, abrir a tela de bloqueio volta ao painel
      Dado a sessão do usuário do <painel> foi travada e destravada com a senha
      Quando o usuário abre a tela de bloqueio do <painel>
      E ao seguir o destino devolvido pela resposta
      Então a abertura da tela redirecionou exatamente para "<raiz>"
      E a resposta ao seguir o destino foi 200

      Exemplos:
        | painel | raiz   | # partição |
        | admin  | /admin | admin      |
        | infra  | /infra | infra      |
        | app    | /app   | app (Kit)  |
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M28 | a guarda testa a existência da marca e não o valor verdadeiro | CT-18 | a resposta é redirecionamento; o mutante renderiza a tela com 200 |
| M29 | a sessão destravada é mandada à página pública | CT-18 | destino igual à raiz do painel; o mutante redireciona a `/` |
| M30 | a sessão destravada é mandada de volta à própria tela de bloqueio (laço) | CT-18 | destino da raiz responde 200; o mutante redireciona à tela |

---

## Regra R9 — Superfície do componente: o cliente não escolhe o destino nem destrava com tipo errado

> `RQ-03`, `RQ-02` · perfil **padrão** · técnica: **superfície Livewire** — valor fora do domínio e tipo errado, sobre a propriedade pública `data` e a ação `authenticate` (`02` `## Superfície Livewire`)

```gherkin
    Cenário: [CT-19] chave extra no estado do componente não escolhe o destino
      Dado a sessão foi travada por inatividade ao pedir "/admin/users"
      E o estado do componente recebe "data.redirect" = "https://externo.example/x"
      Quando o usuário informa a senha correta na tela de bloqueio do painel admin
      Então o desbloqueio redireciona exatamente para "/admin/users"
      E nenhum redirecionamento aponta para o host "externo.example"

    Cenário: [CT-20] senha com tipo errado é recusada sem exceção e mantém a trava
      Dado a sessão foi travada por inatividade ao pedir "/admin/users"
      Quando o estado do componente recebe "data.password" como lista vazia e a ação authenticate é chamada
      Então a sessão continua travada, sem redirecionamento e sem exceção
      E há erro de validação em "data.password"
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M31 | o destino lido do estado público do componente | CT-19 | destino `/admin/users`; o mutante redireciona a `https://externo.example/x` |
| M32 | tipo errado estoura exceção (500) em vez de recusar | CT-20 | o teste de componente não lança e há erro em `data.password`; o mutante lança |
| M33 | tipo errado é aceito e destrava | CT-20 | `session('lockscreen')` continua `true`; o mutante deixa `false` |

---

## Regra R10 — O retorno não concede o que a organização nega

> `RQ-03` · perfil **padrão** · técnica: negativa de controle de fronteira escrita "como se fosse falsa" (o destino é de outra organização)

```gherkin
    Cenário: [CT-21] o retorno a uma organização da qual a pessoa não participa continua recusado pelo painel
      Dado a pessoa participa só da organização acme e a sessão foi travada por inatividade ao pedir "/app/globex/users"
      Quando a pessoa informa a senha correta na tela de bloqueio do painel app
      E ao seguir o destino devolvido pelo desbloqueio
      E ao seguir "/app/acme/users"
      Então o desbloqueio redirecionou exatamente para "/app/globex/users"
      E a resposta ao seguir o destino devolvido foi 404 e a resposta de "/app/acme/users" foi 200
      E a organização da sessão continua acme
```

> Fato a confirmar no vendor pelo executor: o status do Filament para organização à qual o usuário não pertence (`@premissa`: 404). Se for 403, ajustar o valor mantendo "não é 200".

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M34 | o retorno reescreve o destino para a organização da sessão | CT-21 | destino `/app/globex/users`; o mutante redireciona a `/app/acme/users` |
| M35 | o retorno concede acesso (ignora a fronteira da organização no destino) | CT-21 | a página do destino responde 404; o mutante responde 200 |
| M57 | o desbloqueio troca a organização da sessão para a do destino | CT-21 | a organização da sessão continua `acme`; o mutante muda para `globex` |

---

## Regra R11 — O link de vínculo do e-mail, numa sessão travada, não é desbloqueio: só descarta a página guardada

> `P-07` · perfil **padrão** · técnica: **EP por configuração** (login unificado ligado/desligado) + **2-switch** (o ciclo seguinte não herda a página)

```gherkin
    Esquema do Cenário: [CT-32] a outra conta que confirma o vínculo pelo link não recebe a página guardada de A
      Dado a sessão da conta A foi travada por inatividade ao pedir "/admin/users?q=ana", com o login unificado <unificado>
      E o modo de vínculo com confirmação está ligado e a conta B tem um vínculo pendente do provedor "google"
      Quando a conta B abre o link assinado de confirmação (rota `auth.social.confirmar`) no mesmo navegador
      E ao seguir o destino devolvido pelo link
      Então o destino devolvido pelo link não foi "/admin/users?q=ana" nem "/admin/users"
      E a sessão ficou autenticada como a conta B

      Exemplos:
        | unificado  | # partição |
        | ligado     | unificado  |
        | desligado  | painel     |

    Cenário: [CT-33] a mesma conta que confirma o vínculo não deixa a página antiga para o ciclo seguinte
      Dado a sessão da conta A foi travada por inatividade ao pedir "/admin/users?q=ana"
      E a conta A confirmou o vínculo pelo link assinado do e-mail, no modo com confirmação
      E a marca de trava foi forçada de novo na sessão da conta A, sem pedido de página, e a tela de bloqueio do painel admin foi aberta
      Quando a conta A informa a senha correta na tela de bloqueio do painel admin
      Então o desbloqueio redireciona exatamente para "/admin"
      E o destino não é "/admin/users" nem "/admin/users?q=ana"
```

> Arnês: partir de `tests/Kit/VinculoDeProvedorSocialTest.php` (`config('kit.login.vinculo_confirmar')`, `URL::temporarySignedRoute('auth.social.confirmar', …)`); o login unificado é `kit.login.unificado` (`tests/Kit/LoginUnificadoTest.php`).

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M61 | com o login unificado ligado, o link de confirmação consome a página guardada de A para B | CT-32 (linha `ligado`) | destino de B não é `/admin/users?q=ana` nem `/admin/users`; o mutante redireciona a eles |
| M62 | com o login unificado desligado, o link de confirmação consome a página guardada de A para B | CT-32 (linha `desligado`) | destino de B não é `/admin/users?q=ana` nem `/admin/users`; o mutante redireciona a eles |
| M63 | o link da mesma conta mantém a página guardada, que sobrevive e volta no ciclo seguinte sem pedido | CT-33 | destino `/admin`; o mutante redireciona a `/admin/users?q=ana` |

---

## Regra R12 — Sem página guardada, o desbloqueio volta à organização da sessão

> `P-08` · perfil **padrão** · técnica: **discriminante de tenant** (a organização da sessão ≠ a primeira da conta)

```gherkin
    Cenário: [CT-34] sem página guardada, o desbloqueio volta à organização em que a pessoa estava
      Dado a pessoa participa das organizações acme e globex e trabalha em "/app/globex/users" (visita real, resposta 200), com a organização da sessão globex
      E a marca de trava foi forçada na sessão, sem pedido de página, e a tela de bloqueio do painel app foi aberta
      Quando a pessoa informa a senha correta na tela de bloqueio do painel app
      E ao seguir o destino devolvido pelo desbloqueio, seguindo os redirecionamentos
      Então o desbloqueio redirecionou para um endereço que começa com "/app/globex"
      E o destino não começa com "/app/acme" e a resposta final foi 200 numa URL sob "/app/globex"
```

> Arnês: `duasOrganizacoes()` de `tests/Pest.php` (a pessoa é `panel_user` em acme e globex); acme é a primeira da conta, e é por isso que o cenário discrimina.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M64 | o fallback leva à primeira organização da conta | CT-34 | destino começa com `/app/globex`; o mutante redireciona a `/app/acme…` |
| M65 | o fallback leva à raiz `/app` sem organização, que resolve para a primeira | CT-34 | destino começa com `/app/globex`; o mutante redireciona a `/app` |

---

## Regra R13 — O logout forçado por excesso de tentativas não deixa página guardada para o próximo login

> `P-09` · perfil **padrão** · técnica: **EP por situação de partida** (com página / tela aberta direto) + **rastreio de efeito** (o destino guardado é descartado no logout)

```gherkin
    Esquema do Cenário: [CT-35] esgotar as tentativas desloga e o próximo login segue o destino de um login sem página guardada
      Dado a conta tem acesso a 2 painéis, o login unificado está ligado e a sessão tem <partida>
      E um login por senha de referência, sem nenhuma trava anterior, para a mesma conta e configuração, devolveu o destino D0, diferente de "/admin" e de "/admin/users?q=ana"
      Quando a conta erra a senha na tela de bloqueio até esgotar as 5 tentativas e é deslogada
      E a conta entra de novo pela senha no login unificado
      Então o destino devolvido pelo login é igual a D0
      E o destino não é "/admin" nem "/admin/users?q=ana"
      E a sessão não guarda destino de retorno (afirmação auxiliar de arnês: `url.intended` ausente)

      Exemplos:
        | partida                                                              | # partição     |
        | a página guardada ao travar por inatividade ao pedir "/admin/users?q=ana" | com página     |
        | a tela de bloqueio aberta direto, sem página (fallback para "/admin")     | fallback       |
```

> O `Dado` exige conta com 2 painéis para que D0 não coincida com a raiz do painel da tela de bloqueio; sem isso a linha `fallback` não discrimina (D0 = `/admin` igual ao mutante).

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M66 | o logout forçado deixa a página guardada, e o próximo login vai a ela | CT-35 (linha com página) | destino do login igual a D0; o mutante redireciona a `/admin/users?q=ana` |
| M67 | o logout forçado deixa a raiz guardada pelo fallback, e o próximo login vai a ela | CT-35 (linha fallback) | destino do login igual a D0; o mutante redireciona a `/admin` |

---

## Checklist de Taxonomia

| Item | Cenário que mata | Grupo |
|---|---|---|
| IDOR / autorização horizontal | CT-21 (organização alheia no destino) · CT-22 (conta de outra pessoa no provedor, P-06) | G2 · G4 |
| Autorização exercida na ação (não só `can()`) | CT-12, CT-20 (a ação `authenticate`); o callback por fora do componente: CT-14 | G1 · G4 |
| Idempotência (ancorada no agregado) | CT-11 (segundo envio) | G1 |
| Concorrência | não se aplica: sem contador, saldo nem limite; duas abas com a mesma sessão repetem o CT-11 | — |
| Fronteira no ponto de entrada (gravação) | CT-03, CT-16, CT-19, CT-24, CT-25 (o destino nunca vem do cliente) | G1 · G4 |
| Domínio condicionado (tipo × valor) | não se aplica: nenhum campo cujo domínio depende de outro | — |
| Estado × operação de escrita | matriz 4×4 acima: CT-04, CT-05, CT-09, CT-10, CT-11, CT-17, CT-18; S0×O3 lacuna declarada | G1 · G3 |
| Ausente ≠ null ≠ vazio | CT-09 (destino ausente → raiz do painel); Referer ausente: CT-09; Referer presente interno e externo: CT-24, CT-15 | G1 · G4 |
| Paginação / ordenação | não se aplica: a feature não lista nada | — |
| Timezone / DST | não se aplica: a inatividade usa timestamp relativo (`time()`); a borda do tempo é Fora de Escopo | — |
| Unicode / limite de varchar | não se aplica: a URL vem do próprio request; a query de CT-01 é ASCII. Lacuna declarada: URL com acento/emoji codificado, não tentada (sem cláusula no `00`) | — |
| Unicidade + soft delete | não se aplica: sem unicidade nem exclusão | — |
| CRUD combinado | não se aplica: sem escrita de entidade | — |
| Mass assignment | CT-19 (`data.redirect`), CT-16 (query do callback), CT-25 (query da tela) | G1 · G4 |
| Upload | não se aplica: sem upload | — |
| Precisão monetária | não se aplica: sem valor monetário | — |
| Superfície Livewire (método público, propriedade pública, estado do framework) | CT-19, CT-20 (propriedade `data`, ação `authenticate`); métodos `urlsDasLogos()`, `getAuthDesignerConfig()`, `content()`: não se aplica — sem argumento do cliente nem relação com o destino, conforme a varredura do `02` (conferir o grep do `02` no passo de implementação) | G1 |
| Estado do framework usado sem validar | CT-19: `data` não é índice nem destino | G1 |
| IDOR por entidade (por tabela persistida) | não se aplica: a feature não persiste tabela; o destino vive só na sessão | — |
| Escopo com discriminante nulo | CT-09 (destino nulo → raiz do painel, não pública); CT-34 (organização da sessão ≠ primeira da conta) | G1 · G2 |
| Saída do estado de erro | CT-18 (destino alcançável com 200), CT-12 (erro na própria tela, que continua alcançável: CT-13), CT-21 | G3 · G1 · G2 |

## Índice de Cenários

| ID | Cenário | Regra | Técnica | Grupo | Costura | Arquivo | Mata |
|----|---------|-------|---------|-------|---------|---------|------|
| CT-01 | volta à página pedida, com query, não à anterior (3 painéis) | R1 | EP + discriminante | G1 | componente Livewire/Filament | `tests/Kit/BloqueioDeSessaoTest.php` | M1, M2, M3 |
| CT-02 | com tenancy, volta à página da organização | R1 | EP | G2 | componente Livewire/Filament | `tests/Tenancy/BloqueioComRetornoTenancyTest.php` | M3, M5 |
| CT-03 | Referer externo nunca vira destino | R1 | EP | G1 | componente Livewire/Filament | `tests/Kit/BloqueioDeSessaoTest.php` | M4 |
| CT-04 | já travada, abre outra página: volta à última | R2 | estado × evento | G1 | componente Livewire/Filament | `tests/Kit/BloqueioDeSessaoTest.php` | M7, M9 |
| CT-05 | abrir a tela de bloqueio não troca o destino | R2 | estado × evento | G1 | componente Livewire/Filament | `tests/Kit/BloqueioDeSessaoTest.php` | M6, M54 |
| CT-06 | bloqueio manual volta à página do clique | R2 | estado × evento | G1 | componente Livewire/Filament | `tests/Kit/BloqueioDeSessaoTest.php` | M8, M53 |
| CT-25 | query na tela de bloqueio não escolhe o destino | R2 | superfície | G1 | componente Livewire/Filament | `tests/Kit/BloqueioDeSessaoTest.php` | M43 |
| CT-07 | JSON e chamada Livewire não viram destino | R3 | EP | G1 | componente Livewire/Filament | `tests/Kit/BloqueioDeSessaoTest.php` | M10, M11, M56 |
| CT-08 | chamada de componente não troca a página guardada | R3 | estado × evento | G1 | componente Livewire/Filament | `tests/Kit/BloqueioDeSessaoTest.php` | M12 |
| ~~CT-26~~ | envio não-GET não vira destino — @obsoleto: sem rota POST que atravesse a barreira | R3 | EP | G1 | componente Livewire/Filament | `tests/Kit/BloqueioDeSessaoTest.php` | M44, M45 |
| CT-27 | GET JSON com sessão já travada não troca a página | R3 | estado × evento | G1 | componente Livewire/Filament | `tests/Kit/BloqueioDeSessaoTest.php` | M46, M59 |
| CT-09 | sem página, raiz do painel e nunca a pública | R4 | EP por painel | G1 | componente Livewire/Filament | `tests/Kit/BloqueioDeSessaoTest.php` | M13, M14 |
| CT-10 | segundo ciclo não herda a página do primeiro | R4 | 2-switch | G1 | componente Livewire/Filament | `tests/Kit/BloqueioDeSessaoTest.php` | M15 |
| CT-11 | segundo envio da senha não cai na pública | R4 | idempotência | G1 | componente Livewire/Filament | `tests/Kit/BloqueioDeSessaoTest.php` | M16, M60 |
| CT-23 | depois de visita real, trava sem pedido vai à raiz | R4 | EP + discriminante | G1 | componente Livewire/Filament | `tests/Kit/BloqueioDeSessaoTest.php` | M39, M40 |
| CT-24 | tela aberta direto ignora o Referer | R4 | EP | G1 | componente Livewire/Filament | `tests/Kit/BloqueioDeSessaoTest.php` | M41, M42 |
| CT-30 | com tenancy, sem página: fica no /app e na organização | R4 | EP | G2 | componente Livewire/Filament | `tests/Tenancy/BloqueioComRetornoTenancyTest.php` | M49, M50 |
| CT-12 | senha errada não leva e mantém a trava | R5 | rastreio de efeito | G1 | componente Livewire/Filament | `tests/Kit/BloqueioDeSessaoTest.php` | M18, M19 |
| CT-13 | senha errada não gasta a página guardada | R5 | rastreio de efeito | G1 | componente Livewire/Filament | `tests/Kit/BloqueioDeSessaoTest.php` | M17 |
| CT-31 | provedor negado não destrava nem gasta a página | R5 | rastreio de efeito | G4 | Pest feature HTTP | `tests/Kit/BloqueioDeSessaoTest.php` | M51, M52 |
| CT-14 | provedor destrava e leva à página | R6 | EP + discriminante | G4 | Pest feature HTTP | `tests/Kit/BloqueioDeSessaoTest.php` | M20, M23, M58 |
| CT-15 | provedor, tela aberta direto: raiz do painel dela | R6 | EP por painel | G4 | Pest feature HTTP | `tests/Kit/BloqueioDeSessaoTest.php` | M21, M55 |
| CT-16 | parâmetro de destino no callback é ignorado | R6 | superfície | G4 | Pest feature HTTP | `tests/Kit/BloqueioDeSessaoTest.php` | M22 |
| CT-22 | outra conta no provedor não herda a página guardada | R6 | EP por conta | G4 | Pest feature HTTP | `tests/Kit/BloqueioDeSessaoTest.php` | M36, M37, M38 |
| CT-28 | a página de A não vaza para B num bloqueio posterior | R6 | 2-switch | G1 | componente Livewire/Filament | `tests/Kit/BloqueioDeSessaoTest.php` | M47 |
| CT-29 | quem entra depois de "Trocar de conta" não recebe a página de A | R6 | EP por mecanismo | G4 | Pest feature HTTP | `tests/Kit/BloqueioDeSessaoTest.php` | M24, M48 |
| CT-17 | seguir o retorno abre a página e não trava de novo | R7 | EP por mecanismo | G3 | Pest feature HTTP | `tests/Kit/BloqueioDeSessaoTest.php` | M25, M26, M27 |
| CT-18 | sessão destravada abrindo a tela volta ao painel | R8 | EP por painel | G3 | Pest feature HTTP | `tests/Kit/BloqueioDeSessaoTest.php` | M28, M29, M30 |
| CT-19 | chave extra no estado do componente é ignorada | R9 | superfície | G1 | componente Livewire/Filament | `tests/Kit/BloqueioDeSessaoTest.php` | M31 |
| CT-20 | senha com tipo errado recusada sem exceção | R9 | superfície | G1 | componente Livewire/Filament | `tests/Kit/BloqueioDeSessaoTest.php` | M32, M33 |
| CT-21 | retorno a organização alheia segue recusado | R10 | negativa como falsa | G2 | componente Livewire/Filament | `tests/Tenancy/BloqueioComRetornoTenancyTest.php` | M34, M35, M57 |
| CT-32 | link de vínculo de outra conta não entrega a página de A (unificado ligado/desligado) | R11 | EP por configuração | G4 | Pest feature HTTP | `tests/Kit/BloqueioDeSessaoTest.php` | M61, M62 |
| CT-33 | link da mesma conta não deixa a página antiga para o ciclo seguinte | R11 | 2-switch | G4 | Pest feature HTTP | `tests/Kit/BloqueioDeSessaoTest.php` | M63 |
| CT-34 | sem página, o desbloqueio volta à organização da sessão | R12 | discriminante de tenant | G2 | componente Livewire/Filament | `tests/Tenancy/BloqueioComRetornoTenancyTest.php` | M64, M65 |
| CT-35 | logout forçado não deixa página guardada para o próximo login | R13 | EP + rastreio de efeito | G1 | componente Livewire/Filament | `tests/Kit/BloqueioDeSessaoTest.php` | M66, M67 |

## Cogitado e cortado

| Cenário cogitado | Por que foi cortado |
|---|---|
| tabela de borda do tempo de inatividade (timeout−1, timeout, +1) | Fora de Escopo do `00` (`LOCKSCREEN_IDLE_TIMEOUT` não muda) |
| login social comum com página interceptada anterior | o `00` não escreve o destino de hoje; o oráculo discriminante é o de CT-29 (M24); regressão pelas suítes `LoginSocial*` existentes |
| browser: tela de bloqueio, foco e teclado | sem superfície de UI nova; nada que só o navegador prove |
| ADV-07 — navegação SPA do Livewire (`X-Livewire-Navigate`) como partição | rejeitado, fato: nenhum painel usa `->spa()` (grep em `app/Providers`: 0), então o cabeçalho não existe nesta aplicação |
| ADV-08 — duas réguas de "página" em P-04 | rejeitado, resolvido no `00`: P-04 foi reescrita com a régua do *intended* do Laravel (GET que não espera JSON vira destino); não há partição ambígua |
| ADV-14 — Host forjado compondo a URL guardada | rejeitado, fato: o Host de um request legítimo é posto pelo navegador da própria pessoa e um site externo não o forja; mesma exposição do *intended* do login do Laravel; `TrustHosts`/proxy é infraestrutura, fora do escopo |
| IDOR de `{id}` | a feature não recebe `{id}`; o destino é a URL do próprio request |

## Sem CT-B

- Motivo: nenhuma linha de `## Costuras de Teste` tem costura `browser`. O `01` declara "Sem superfície de UI nova"; todos os `Então` são redirecionamento, status e marca de sessão, provados por componente Livewire ou HTTP. O `05` não é criado.
