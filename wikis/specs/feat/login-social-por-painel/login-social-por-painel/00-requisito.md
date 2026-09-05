# Requisito — Login social por painel

## Fonte

- **Origem**: invocação `/feature-wiki` no chat, pelo mantenedor do kit
- **Data**: 2026-09-02
- **Autor / solicitante**: mantenedor do kit
- **Fidelidade**: **média** — texto escrito pelo mantenedor, com o caso de uso explicado e uma
  segunda linha restringindo o escopo. A ambiguidade **A1** abaixo é material e precisa de resposta
  antes da implementação: as duas leituras entregam coisas diferentes.

## Texto Original

<!-- IMUTÁVEL. Não editar, não corrigir ortografia, não resumir, não reordenar. -->

> ao habilitar o login social, ter as opções de definir em quais paineis poderão usar, pois pode ter casos que voce quer liberar o login do empresarial do google para acessar o admin, mas para a empresa (tenancy ativo) ou usuário final, não. então ter como escolher qual painel sera usado, da uma liberdade maior.
> - as outras premissas se mantem, é apenas se tiver ativo, ver se o painel em si também podera usar esse tipo de login

## O que existe hoje, levantado no código

**A decisão de disponibilidade é por provedor e não conhece painel.**
`App\Support\ConfiguracaoDoLogin::disponivel(ProvedorSocial $provedor)` (`app/Support/ConfiguracaoDoLogin.php:64-76`)
é a única dona da pergunta, e conjuga duas condições:

1. `config("kit.login.{$provedor->value}.habilitado")` — o interruptor;
2. `client_id`, `client_secret` e `redirect` preenchidos em `config('services.{provedor}')`.

`disponiveis()` (`:86-93`) é a lista filtrada, na ordem do enum, e é o que a tela percorre.

**Os quatro provedores** vivem em `App\Support\ProvedorSocial`: `Google`, `Github`,
`LinkedIn` (valor `linkedin-openid`) e `X`.

**Os botões são renderizados por render hook GLOBAL, sem escopo de painel.**
`KitServiceProvider::configureTelaDeLogin()` (`app/Providers/KitServiceProvider.php:399-419`)
registra `view('filament.auth.botoes-sociais')` em `PanelsRenderHook::AUTH_LOGIN_FORM_AFTER` e em
`AUTH_REGISTER_FORM_AFTER` via `FilamentView::registerRenderHook` — **uma** registração, sem
`scopes`, então a mesma view sai na tela de login dos **três** painéis. O blade
(`resources/views/filament/auth/botoes-sociais.blade.php:40`) chama `disponiveis()` e não recebe
painel nenhum.

**As rotas são globais e fora do painel.** `routes/web.php:61-70`: `auth/{provedor}/redirect`,
`/callback` e `/confirmar`, sob `throttle:10,1`, com o provedor resolvido por implicit enum binding.
O `redirect` do OAuth é uma URL **fixa** por provedor (`config/services.php:68,74,80,86` —
`/auth/google/callback` e os pares), então **o callback não tem painel na URL**.

**Existe um mecanismo de contexto na sessão, e ele é o gancho natural para o painel.**
`LoginSocialController::redirecionar()` grava `session()->put('login_social.contexto', $contexto)`
(`:85`) e o `retorno()` faz `session()->pull('login_social.contexto', [])` (`:136`). Hoje ele
carrega `org` e `token` da tela de registro (ADR-02 da wiki
`cadastro-social-por-convite-e-organizacao`).

### O achado que muda o tamanho da entrega

**O login social do kit termina SEMPRE no painel `/app`, por construção.** Não é configuração — é
`Filament::getPanel('app')` escrito em seis pontos do `LoginSocialController`:

| Linha | O que é |
|---|---|
| `:645-648` | `urlDoPainel()` — o destino de quem entrou com sucesso |
| `:434` | volta ao login do `app` num caminho de recusa |
| `:466` | idem, outro caminho |
| `:590` | o aviso de conta indisponível volta para o login do `app` |
| `:666` | `urlDoPerfil()` — o painel onde o perfil é procurado |
| `:694` | outro retorno ao login do `app` |

Consequência direta para o caso de uso do requisito: **"liberar o login do Google para acessar o
admin" não funciona hoje nem se o botão aparecer na tela do `/admin`.** A pessoa autentica no
Google, volta no callback, e é levada para `/app`. Se ela não tiver papel do `app`, o painel recusa.

Isso não é ambiguidade de redação do requisito — é **premissa de escopo**: a segunda linha do texto
("é apenas se tiver ativo, ver se o painel em si também poderá usar") pede a *permissão*, e o
exemplo da primeira linha ("liberar o login do empresarial do google para acessar o admin") pede o
*destino*. Ver **A1**.

## Decomposição em Cláusulas

| ID | Cláusula | Trecho literal de origem | Tipo |
|----|----------|--------------------------|------|
| RQ-01 | Cada provedor social habilitado tem uma escolha de **em quais painéis** ele pode ser usado | "ter as opções de definir em quais paineis poderão usar" | funcional |
| RQ-02 | A escolha é por provedor, não global — o Google pode valer no `/admin` e não valer no `/app` | "liberar o login do empresarial do google para acessar o admin, mas para a empresa … ou usuário final, não" | funcional |
| RQ-03 | A condição nova é **conjuntiva** com as que já existem: o provedor precisa estar ativo **e** o painel precisa estar autorizado | "as outras premissas se mantem, é apenas se tiver ativo, ver se o painel em si também podera usar" | restrição |
| RQ-04 | O botão do provedor não aparece na tela de login de um painel não autorizado | "definir em quais paineis poderão usar" | funcional |
| RQ-05 | A recusa vale também fora da tela — a rota do provedor recusa quando o painel de origem não está autorizado | "poderão usar" (usar é o fluxo, não o botão) | autorização |
| RQ-06 | As premissas atuais seguem intactas: interruptor por provedor e as três credenciais preenchidas | "as outras premissas se mantem" | restrição |

## Ambiguidades e Perguntas Abertas

- **A1 — a entrega inclui o DESTINO, ou só a PERMISSÃO?** É a pergunta que decide o tamanho da
  feature, e as duas leituras têm apoio no texto.
  - **Leitura estreita (permissão)**: o botão aparece só nos painéis autorizados e a rota recusa
    os demais. O destino continua sendo o `/app`, como hoje. Apoio no texto: *"é apenas se tiver
    ativo, ver se o painel em si também podera usar esse tipo de login"*.
  - **Leitura ampla (permissão + destino)**: além do acima, quem entra pelo botão na tela do
    `/admin` termina **no `/admin`**. Apoio no texto: *"liberar o login do empresarial do google
    para acessar o admin"* — que é o caso de uso declarado e **não se realiza** na leitura estreita.
  - **Assumido**: **leitura ampla**, porque a estreita entrega uma configuração que não produz o
    efeito que o requisito usa para justificá-la — o botão apareceria no `/admin` e levaria a
    pessoa para o `/app`.
  - **Se negado** (só a permissão): saem os passos de propagação do painel pela sessão e a
    parametrização dos seis `getPanel('app')`; a entrega encolhe para a condição nova em
    `ConfiguracaoDoLogin` mais o filtro no blade. **Registrar como débito** que o caso de uso do
    requisito continua não atendido.

- **A2 — qual o default para instalação existente?** Não foi dito.
  - **Assumido**: **todos os painéis**, preservando o comportamento atual — quem já usa login
    social não perde nada num update. A feature nasce inerte.
  - **Se negado** (default só `app`, o comportamento efetivo de hoje): é uma mudança de
    comportamento em update, e precisa de nota no `CHANGELOG` e na documentação.

- **A3 — a escolha é do settings do kit (banco, editável em `/admin`) ou do `.env`?**
  - **Assumido**: **settings do kit**, junto das outras três propriedades por provedor
    (`login_{provedor}_habilitado`, `client_id`, `client_secret`), semeado do `.env`. É onde o
    requisito espera achar ("ao habilitar o login social, ter as opções").
  - **Atenção medida**: propriedade de settings lida no **boot do painel** vira toggle que grava e
    não faz nada — foi o caso de `registro_verificar_email`, documentado em
    `app/Settings/ConfiguracoesDoKit.php:318-330`. Esta feature é lida **por request** (no render
    hook e no controller), então não cai naquela armadilha. Confirmar no plano.

- **A4 — o `/infra` entra na lista de painéis oferecidos?** O requisito nomeia `admin`, "a empresa
  (tenancy ativo)" e "usuário final" — que são `admin` e `app`.
  - **Assumido**: **os três** (`admin`, `app`, `infra`), porque a lista é o que o kit tem e excluir
    o `infra` seria uma regra a mais sem pedido. O default de A2 os inclui.
  - **Se negado**: a lista tem dois itens e o `infra` nunca oferece login social.

- **A5 — o que acontece com quem entra por um painel autorizado mas não tem papel dele?** Não foi
  dito, e é o caminho mais provável de suporte.
  - **Assumido**: o comportamento atual do painel — `canAccessPanel()` recusa e a pessoa cai na
    tela que o painel já mostra. A feature **não** cria papel nem altera autorização.
  - **Se negado**: entra uma mensagem específica, e isso é UI nova.

### Devolvidas pela derivação dos casos de teste (`feature-test-design`, 2026-09-02)

- **A6 — painel inexistente na query: 404 ou segue no default?** A derivação achou uma
  **contradição na própria wiki**: a ADR-03 dizia "as duas falhas respondem 404" e o código do
  passo 5b fazia `painelDaRequisicao()` devolver `null` (segue no default).
  - **Resolvido**: **segue no default**. O código estava certo; a prosa da ADR-03 e do `01` foi
    corrigida. Motivo: painel inexistente é indistinguível de link antigo sem `painel`, e 404 ali
    quebraria a compatibilidade que a ADR-04 protege.
  - **Bloqueia**: R4 / CT-10 do `04`.

- **A7 — lista gravada só com painel inexistente (`['marketing']`): o provedor vale em nenhum
  painel, ou o valor inválido é ignorado?** Alcançável por `.env` com painel renomeado ou removido,
  e o efeito é **silencioso**: o provedor desaparece dos três painéis sem erro.
  - **Assumido**: falha **fechada** — não vale em nenhum painel. Coerente com "vazio = todos, e
    quem não quer o provedor em painel nenhum desliga o interruptor".
  - **Se negado** (ignorar o inválido = todos): CT-02 muda de linha, e a tela precisa dizer isso.
  - **Bloqueia**: R1 / CT-02.

- **A8 — a volta reconfere a autorização por painel?** A ADR-05 decide que não; o texto não diz.
  - **Assumido**: não reconfere. **Se negado**: configuração alterada entre o clique e a volta
    produz 404 depois de a pessoa já ter autenticado no provedor, e CT-19 inverte.

- **A9 — o registro da recusa é requisito?** O `01` prevê um `warning`; o texto não pede rastro.
  - **Assumido**: é entrega do plano, não cláusula. CT-13 está marcado `@do-plano`.

- **A10 — o rótulo e o texto de ajuda do campo são requisito?** O default "vazio = todos" **não é
  adivinhável**, então o texto é parte do contrato com quem configura.
  - **Assumido**: não é cláusula, e nenhum `Então` afirma texto de tela. **Se negado**: entra um
    cenário de componente sobre o texto de ajuda.

- **A11 — RQ-04 alcança a tela de registro (`/app/register`)?** O texto diz "tela de login", mas o
  render hook é registrado nas duas superfícies.
  - **Assumido**: **sim**. Não alcançar abriria pelo cadastro exatamente o que a feature fecha pelo
    login. CT-07 está marcado `@premissa`.

## Fora de Escopo (declarado)

- Criar ou atribuir **papel** por provedor social. O requisito fala de qual painel pode *usar* o
  login, não de quem tem acesso ao painel — quem decide acesso continua sendo `roles.painel`.
- Restringir provedor por **organização** (tenant). O texto diz "para a empresa (tenancy ativo) …
  não", que é o painel `/app` inteiro, não uma organização específica. Uma escolha por organização
  seria outra feature, e cara: o tenant não é conhecido no momento do login.
- Restringir por **domínio de e-mail** (o "Google empresarial" do exemplo é um Workspace, e limitar
  a `@empresa.com` é outra feature — vale registrar como ideia adjacente).
- Mudar o mecanismo de credenciais: um `client_id` por painel não é pedido, e o OAuth do provedor
  tem um `redirect` só.
