# Casos de Teste — Telas externas com o login unificado

> Requisito: `00-requisito.md` · Plano: `01-plano-acao.md` · Ancestral: `wikis/specs/feat/login-unificado/login-unificado/04-casos-de-teste.md`
> Derivado do **requisito**, não do plano. Nenhum cenário foi escrito olhando implementação — ela não
> existe. Lidos para herdar convenção: `tests/Pest.php` (helpers; inventário `telasDoKit():222`),
> `tests/Kit/LoginUnificadoTest.php` (helpers locais `ligarLoginUnificado()`, `adminEInfra()`,
> `personaDoKit()`, `entrarPelaPaginaUnica()`; padrão `[CT-nn]`), `tests/Kit/RegistroAbertoTest.php`
> (`registrarAberto():64`, CT-04b `:698`), `tests/Tenancy/RegistroAbertoTenancyTest.php`
> (`organizacaoComRegistro():47`, `registrarNaOrganizacao():62`), `tests/Kit/DefinirSenhaPorEmailTest.php:41-59`
> (`Notification::fake()` + `ResetPassword`), `tests/Browser/LoginUnificadoTest.php`,
> `tests/Browser/BoasVindasTest.php:54-56`, `tests/Kit/ConfiguracoesDoKitDocumentacaoTest.php:26-49,92`.
> Vendor aberto para confirmar **fixture e oráculo de paridade**, não comportamento do kit:
> `vendor/filament/filament/src/FilamentManager.php:registerPanel():862-865` (público; delega a
> `PanelRegistry::register():17-22`, que guarda o painel e chama `Panel::register():69`),
> `vendor/bezhansalleh/filament-panel-switch/src/PanelSwitch.php:make():42-49` (aplica `configure()`),
> `getIcons():166`, `getLabels():193`, `getPanels():238`, `canAccessPanel:309-310`;
> `resources/views/panel-switch-menu.blade.php:33` (fallback `ucfirst(id)`) e `:5,7`
> (`heroicon-o-square-2-stack`); `vendor/filament/filament/src/Auth/Pages/Register.php:mount():57-61` e
> `vendor/filament/filament/src/Auth/Pages/PasswordReset/RequestPasswordReset.php:mount():47-54` (autenticado → `redirect()->intended(Filament::getUrl())`).
> Numeração contínua à ancestral: começa em **CT-43**.

## Perfil de Derivação

| Área | P | I | P×I | Perfil |
|---|---|---|---|---|
| A — escolha de painel dinâmica e fonte única de rótulo/ícone (RQ-01, RQ-02) | 2 — integra `Paineis`, Panel Switch, boas-vindas | 3 — **autorização**: cartão de painel inacessível, ou painel acessível que não aparece | 6 | **padrão** (Impacto 3 → adversarial) |
| B — slug da página de configurações (RQ-03) | 1 — uma propriedade e um redirect | 2 — URL quebrada em menu, doc e 20 testes | 2 | **mínimo** |
| C — registro na página única e o link de cadastro (RQ-04) | 3 — regra com muitas condições: chave × autenticado × tenancy × token × org × registro aberto | 3 — **criação de conta**; laço de redirect derruba o registro | 9 | **completo** (adversarial) |
| D — recuperação de senha na página única (RQ-06) | 2 — dois pontos existentes mudam de URL | 3 — laço de redirect derruba a recuperação; link de e-mail morto | 6 | **padrão** |
| E — revisão das telas externas com a chave ligada (RQ-07) | 2 — regressão sobre telas de vendor | 2 — dead end reversível | 4 | **padrão** |
| RQ-05 (análise do `projeto-3`) | — | — | — | **não gera CT**: cláusula de investigação; o achado está registrado no `00` (causa medida) e no `01` (`## Contexto`). O que ele explica vira as regras R6 e R4 |

- Técnicas aplicadas: **EP exaustiva** sobre o enum de painéis (os três do kit + um painel **falso** registrado em teste), **matriz persona × painel registrado**, **paridade contra a fonte de referência** (o Panel Switch configurado), **tabela de decisão** (`/cadastro`: chave × autenticado × tenancy × token × org × registro), **rastreio de efeito** (conta gravada, convite aceito, notificação de reset enviada; não-efeito em toda recusa), **controle negativo** (chave desligada em cada área), **par obrigatório** de layout (`.ai/rules/auth.md`).
- Cenários: **23** (CT-43…CT-65; 5 deles **atualizam** teste existente — CT-43, CT-46, CT-50, CT-51, CT-59 —, 18 são novos; 12 são `Esquema`, contados como 1 cada) · Regras: 10 · Mutantes previstos: 49 (37 da derivação, um removido, + 13 da revisão adversarial) · Sem matador na suíte: **2** (M-A6, renderização do ícone — matador parcial na camada de objeto; M-A7, "duas fontes com valores iguais" — estrutural, declarado).
- **Gatilho da revisão adversarial**: Impacto 3 nas áreas A, C e D. Executada sobre o conjunto inteiro por sub-agente independente; 47 achados, todos fechados — ver `## Revisão adversarial`.

## Varredura SFDIPOT

| Letra | O que existe nesta feature | Cenários gerados |
|---|---|---|
| **S** | dois mapas (rótulo, ícone) e um iterador de painéis em `Paineis`; o Panel Switch lendo deles; um `$slug` e um `Route::redirect`; duas páginas novas fora de painel (`/cadastro`, `/esqueci-minha-senha`) com guarda de laço nas páginas de painel; um método de URL do cadastro; o link de registro e o hint de senha da tela de login. **Não gera cenário de `kit:update`**: `app/Filament/Pages/Auth`, `app/Support`, `app/Providers` já estão em `CAMINHOS_DO_KIT` (CT-22 da ancestral prova o mecanismo) | CT-44, CT-48, CT-49, CT-62 |
| **F** | listar painéis acessíveis com rótulo/ícone; redirecionar registro e reset para a página única (e de volta, desligada); recusar cadastro sem destino; mostrar/ocultar/apontar o link de cadastro; gravar conta; enviar e-mail de reset | CT-43, CT-45…CT-47, CT-51…CT-61 |
| **D** | painéis: 3 do kit, 4 com o falso (`financeiro` — id com mais de uma letra, para o rótulo não casar com texto acidental do HTML); usuário com 2/3/4 painéis (0 e 1 são da ancestral, CT-12/CT-18/CT-19, e não mudam); nome da aplicação **não padrão** (`Projeto Três`); token válido / inexistente / expirado / já aceito / ausente; `?org=` de organização ativa+registro / ativa sem registro / inativa / inexistente / ausente; token × org cruzados; conta existente / inexistente para o reset | CT-43…CT-46, CT-51, CT-53, CT-55, CT-58, CT-61, CT-65 |
| **I** | `GET /login/painel`, `GET /`, `GET /admin` (Panel Switch), `GET /admin/configuracoes-da-aplicacao` e `/admin/configuracoes-do-kit`, `GET /app/register`, `GET /cadastro`, `GET /{painel}/password-reset/request`, `GET /esqueci-minha-senha`, os formulários (Livewire), o link do e-mail de reset, a doc (`documentacaoDoKit()`) | todos |
| **P** | **tenancy é modo de boot, não flag de request**: `tests/TestCase.php:createApplication()` fixa `KIT_TENANCY`, `permission.teams` e o `PermissionRegistrar` antes do boot e das migrations; `config()->set('kit.tenancy.enabled', true)` no meio de um teste da suíte `Kit` produz um **meio-modo** (rotas do `/app` sem `/{tenant}`, papéis sem team) que não existe em produção. A tabela `tenants` existe na suíte `Kit` (`database/migrations/0001_01_01_000020_create_tenants_table.php`, incondicional), mas isso só torna o meio-modo *executável*, não representativo → toda célula com tenancy ligada vive em **`tests/Tenancy/`** (`.ai/rules/testes.md`, "Nem todo papel do kit existe em toda suíte") | CT-51 (linha `org`), CT-53 (linhas E2–E5), CT-55 (linha `org`), CT-58 |
| **O** | instalação com painel extra (o `projeto-3` tem cinco), nome de aplicação próprio, tenancy ligada com uma única organização sem cadastro público (o caso medido); e-mail de convite antigo apontando para `/app/register?token=` | CT-45, CT-46, CT-51, CT-58 |
| **T** | sem expiração nem concorrência novas. Dois efeitos de ordem: a chave desligada depois de ligada (controle negativo, CT-52, CT-60) e o **cache de redirect no navegador** — um 301 na rota do painel sobrevive ao desligamento da chave e vira laço no cliente (revisão #4) → os redirects de painel afirmam **302**. Convite vencido entra como partição do token (CT-53); o throttle do registro é de `RegistroAbertoTest` | CT-51, CT-53, CT-59 |

## Mapa de Regras

| Regra | Área (perfil herdado) | Origem (`RQ`) | Técnica | Cenários |
|---|---|---|---|---|
| R1 — a escolha (e a boas-vindas) lista **um cartão por painel registrado** que a pessoa acessa, inclusive painel registrado depois da instalação; nenhum a mais; e o **destino do login** conta sobre a mesma lista | A (padrão) | RQ-01, RQ-02 | EP exaustiva do enum de painéis + matriz persona × painel (painel falso) + fluxo de login | CT-43, CT-45, CT-46, CT-65 |
| R2 — rótulo e ícone de cada cartão são **os mesmos** que o Panel Switch exibe, lidos da mesma fonte, com o mesmo fallback | A (padrão) | RQ-01 | paridade contra a instância configurada do Panel Switch; partição exaustiva (3 mapeados + 1 fora do mapa) | CT-44, CT-47 |
| R3 — a página de configurações responde em `/admin/configuracoes-da-aplicacao`; o slug antigo não serve a tela; a doc aponta a URL nova | B (mínimo) | RQ-03 | EP (URL nova / antiga) + asserção sobre a doc | CT-48, CT-49, CT-50 |
| R4 — com a chave ligada o registro vive em `/cadastro` (a rota do painel redireciona para lá preservando a query); desligada, `/cadastro` devolve à rota do painel; `/cadastro` serve a própria tela para convite e para aberto, e recusa sem destino **sem criar conta**; sem laço | C (completo) | RQ-04 | tabela de decisão (chave × tenancy × token × org × registro) + rastreio de não-efeito | CT-51, CT-52, CT-53, CT-56 |
| R5 — o formulário de `/cadastro` **grava** a conta, nas três partições de entrada (aberto, convite, organização) | C (completo) | RQ-04 | gate de tela de escrita: gravação por componente em cada partição do discriminador | CT-55 |
| R6 — o link "Cadastre-se" da tela de login aponta para a URL do cadastro (página única ou painel, conforme a chave), carrega `?org=` quando há organização resolvível, e **não aparece** quando não há destino | C (completo) | RQ-04 (causa medida) | tabela de decisão (registro × chave × tenancy × org) | CT-57, CT-58 |
| R7 — com a chave ligada "Esqueci minha senha" leva a `/esqueci-minha-senha`, que serve a própria tela e envia o e-mail de reset; as rotas de painel redirecionam para ela; desligada, o inverso; o link do e-mail abre | D (padrão) | RQ-06 | EP por painel + controle negativo + rastreio de efeito (notificação) | CT-59, CT-60, CT-61 |
| R8 — toda tela externa nova veste o layout de auth **sem vazar** para o painel; as voltas ao login terminam em `/login`; as telas que ficam por painel (verificação de e-mail) continuam funcionando com a chave ligada | E (padrão) | RQ-07 | par obrigatório (`.ai/rules/auth.md`) + rastreio por origem | CT-62, CT-63, CT-64 |
| R9 — quem já está autenticado não vê as páginas únicas de cadastro nem de recuperação: vai para o destino dela | C (completo) | RQ-04, RQ-06, RQ-07 (falha fechado) | EP (as duas páginas) | CT-54 |
| R10 — com a chave **desligada**, nada muda além do link de cadastro carregar `?org=`/sumir sem organização | todas | RQ-04, RQ-06 | regressão + controles negativos | linhas `desligada` de CT-52, CT-57, CT-58, CT-60 + suítes do `## Impacto` do PRD |

Técnica escalada: **R6** usa tabela de decisão numa área cujo perfil já é completo — não é escalada. **R1** usa a partição exaustiva do enum de painéis numa área `padrão`: a regra "estado exibido → partição exaustiva" da skill obriga, porque amostrar dois painéis deixaria passar exatamente o defeito medido (lista fixa).

## Fronteira com o Plano

| Item do PRD | Recusado como oráculo porque | Destino |
|---|---|---|
| nomes `CadastroUnificado`, `TelaRecuperarSenhaUnificada`, `Paineis::rotulos()/icones()/rotulo()/icone()`, `RegistroAberto::urlDoCadastro()`, `ehAPaginaUnica()` | escolha de implementação | detalhe do cenário (o componente a montar); nenhum `Então` afirma sobre eles |
| `HttpResponseException` no `mount()`, `Route::redirect(..., 301)`, `$slug` | mecanismo | o oráculo é o redirect HTTP / a resposta |
| os **valores** dos mapas de rótulo e ícone (`Administração`, `heroicon-o-rocket-launch`…) | o requisito não os dita; dita **igualdade com o Panel Switch** | o `Então` compara com o que o Panel Switch **configurado** devolve (CT-44), nunca com o literal do PRD. Exceção: `config('app.name')` para o `app` e os fallbacks `ucfirst(id)` / `heroicon-o-square-2-stack` são **premissa do `00`** (RQ-01, Assumido) → afirmados em CT-43/CT-45 como `@premissa` |
| descrição só para os três painéis do kit; painel novo sem descrição; cor | premissa do `00` sobre texto que o requisito não menciona | não é oráculo; nenhum cenário afirma descrição |
| texto do subtítulo da boas-vindas (`'Um cartão por painel…'`) | comportamento visível que o requisito **não** determina | não é oráculo. O que RQ-02 **determina** é que a página não pode afirmar um número fixo de painéis quando há quatro → CT-46 afirma a **ausência** de "Três painéis" com o painel falso registrado |
| log `[TelaLogin@getSubheading] Link de cadastro ocultado` e `[RegistroPorConvite@mount] … /cadastro` | só o PRD determina; não é visível ao usuário | detalhe; asserção de apoio opcional no teste, nunca oráculo único |
| slug `configuracoes-da-aplicacao` | premissa do `00` (RQ-03, Assumido) | CT-48 usa o literal como destino observável; se negado muda só o literal |
| redirect 301 do slug antigo | o `00` diz **opcional**; o PRD decidiu fazer | CT-49 `@premissa`, com o invariante das duas leituras (o slug antigo **nunca serve a tela**) |
| `/cadastro` (URL sem prefixo para o registro) | premissa (b) do `00` (RQ-04) | CT-51/CT-52/CT-56 usam o literal; se negado, cai a rota e CT-51 passa a afirmar 200 em `/app/register?…` com o link corrigido |
| e-mail de reset apontando para `/app/password-reset/reset` | premissa do `00` (RQ-06, escopo mínimo) | CT-61 `@premissa` no path; o **invariante** (o link do e-mail abre o formulário e não é redirecionado a `/login`) é afirmado no mesmo cenário |
| autenticado em `/cadastro` / `/esqueci-minha-senha` → destino da pessoa | o requisito não decide; o vendor manda para `Filament::getUrl()` do painel corrente (`Register.php:59`, `RequestPasswordReset.php:49`), que para `admin` é `/app` (403) | **pergunta nova**; CT-54 `@premissa` por falha fechado (não mandar para painel inacessível), invariante: sessão preservada, nenhuma conta criada |
| organização resolvível **só** por `?org=` na URL do login | mecanismo do PRD; o `00` diz "quando há organização resolvível" | **pergunta nova**; CT-58 linha E5 `@premissa` por falha fechado (sem `?org=`, sem link) |
| tela de conta indisponível "com link para `/login`" | o PRD escreveu "conferir no step 7"; o requisito (RQ-07) exige que nenhuma tela externa vire dead end com a chave ligada, mas não diz se há link | **pergunta nova**; CT-63 linha `@premissa` |

## Perguntas para o 00-requisito.md

> **Desvio declarado**: o `00` está fechado para edição neste passo (branch já criada; o `00` é a linha de base da wiki). As perguntas ficam aqui, em bloco pronto para colar em `## Ambiguidades e Perguntas Abertas` do `00`. Cada uma continua **bloqueando** o cenário que depende dela.

```markdown
- **RQ-04 / RQ-06 / RQ-07** — quem **já está autenticado** e abre `/cadastro` ou `/esqueci-minha-senha` vai para onde? O Filament manda para o painel corrente (`/app`), que um `admin` não acessa (403 — dead end).
  - **Assumido** (falha fechado): o mesmo destino que `/login` dá a quem já entrou (CT-32 da ancestral: painel único, pretendida ou escolha). Invariante nas duas leituras: a sessão é preservada e nenhuma conta é criada.
  - **Se negado**: CT-54 inverte só o destino (`/app`); o invariante permanece.
- **RQ-04** — com tenancy ligada, a organização do link "Cadastre-se" vem **só** de `?org=` na URL da tela de login? Ou há outra resolução (ex.: única organização com cadastro público)?
  - **Assumido** (falha fechado): só por `?org=`; sem ele, sem link. Invariante: o link nunca aponta para organização que não aceita cadastro.
  - **Se negado**: CT-58 linha E5 ganha a resolução alternativa; as linhas E2–E4 não mudam.
- **RQ-03** — o `00` diz que o redirect do slug antigo é opcional; o plano decidiu fazê-lo (301). Confirma?
  - **Assumido**: 301 para o slug novo. Invariante: o slug antigo **nunca renderiza a tela** (301 ou 404, nunca 200 com o formulário).
  - **Se negado**: CT-49 passa a esperar 404; o invariante permanece.
- **RQ-07** — a tela de conta indisponível (destino da recusa por conta inativa/excluída) tem link de volta ao login? Se tem, com a chave ligada ele deve terminar em `/login`.
  - **Assumido**: tem, e termina em `/login`. **Se não tem link**: a linha de CT-63 vira "não se aplica" e a tela só precisa responder 200.
```

## Setup Global

### Personas

- `admin`, `infra`, `panel_user`, `admin+infra`, `master_global`, `sem papel` — `personaDoKit()` de `tests/Kit/LoginUnificadoTest.php:43-50` (sobre `usuarioDoKit()` e `usuario()` de `tests/Pest.php:462,387`).
- **Discriminante de R1/R2**: `master_global` acessa **qualquer** painel registrado, inclusive o falso, sem papel dele (`app/Models/User.php:canAccessPanel():140-217`, `isMasterGlobal()` → `true`); `admin+infra` **não** acessa o falso (sem papel `financeiro`). É o par que separa "lista os registrados que a pessoa acessa" de "lista todos os registrados".
- `admin+panel_user` — `usuarioDoKit('admin')->assignRole('panel_user')`: acessa o `app` por um papel cujo **nome** não é o id do painel (revisão #2). Discriminante contra "papel com nome igual ao id, ou master".
- `admin+financeiro` — `usuarioDoKit('admin')` + `Role::create(['name' => 'financeiro', 'guard_name' => 'web', 'painel' => 'financeiro'])` atribuído: dois painéis, um deles registrado só em teste (CT-65). `roles.painel` é a coluna que `canAccessPanel()` compara (`database/migrations/2026_08_13_000001_add_painel_to_roles_table.php:8-14`).
- `admin inativa` — `personaDoKit('admin')->forceFill(['ativo' => false])->save()` (molde de CT-11 da ancestral), para a tela de conta indisponível (CT-63).
- Tenancy: `organizacaoComRegistro($slug, $ativo, $registro)` (`tests/Tenancy/RegistroAbertoTenancyTest.php:47`) — se `LoginUnificadoTenancyTest` a usar, **mover para `tests/Pest.php`** (`.ai/rules/testes.md`: helper usado por dois arquivos).

### Fixtures

- Chave: `ligarLoginUnificado()` (`tests/Kit/LoginUnificadoTest.php:35-38`, `config()->set('kit.login.unificado', …)`). Passa a ser usada por `tests/Tenancy/LoginUnificadoTenancyTest.php` → **mover para `tests/Pest.php`** antes de escrever o segundo arquivo (`.ai/rules/testes.md`; `HelpersDeTesteTest` reprova o uso cruzado).
- **Painel falso** (R1, R2): `Filament::registerPanel(Panel::make()->id('financeiro')->path('financeiro'))` no arranjo, antes do `get()`. `registerPanel()` é público (`FilamentManager.php:862-865`) e o registro é por processo de teste (o container é recriado a cada teste), então não vaza. `Panel::register()` (`Panel.php:69-81`) só registra componentes Livewire e middleware persistente — não boota, não precisa de provider. O rótulo esperado é `Financeiro` (fallback do Panel Switch, `panel-switch-menu.blade.php:33`) e o ícone `heroicon-o-square-2-stack` (`:5,7`).
- **Nome da aplicação discriminante**: `config(['app.name' => 'Projeto Três'])` em todo cenário que afirma o rótulo do `app` — o default do kit também é o texto que aparece no `<title>`, e um rótulo fixo `'Starter Kit Easy'` passaria com o default.
- Registro aberto: `config(['kit.registro.habilitado' => true])` (é o que `ligarRegistroAberto()` de `RegistroAbertoTest.php:49` faz; helper local daquele arquivo — não reutilizar sem mover).
- Convite: `$token = ofertaPara('novo@example.com')->enviar()` (`tests/Pest.php:823`).
- Reset: `Notification::fake()` + `Filament\Auth\Notifications\ResetPassword` (`tests/Kit/DefinirSenhaPorEmailTest.php:7,42,54`).
- Panel Switch **configurado**: `PanelSwitch::make()` (`PanelSwitch.php:42-49` — `app(static::class)->configure()` aplica as `configureUsing` do kit) → `getLabels()`, `getIcons()`.
- Login por componente: `entrarPelaPaginaUnica()` (`LoginUnificadoTest.php:53-62`) para chegar ao destino de CT-32.

### Fakes

- `Notification::fake()` (CT-61). `espiarAutenticacao()` (`tests/Pest.php:606`) apenas como asserção de apoio, nunca oráculo único.

### Estratégia de DB

- `RefreshDatabase`; suíte `Kit` (`tests/Kit/*`, `->group('kit')`) para tudo que não depende de tenancy; suíte `Tenancy` (`tests/Tenancy/*`, `TenancyTestCase`) para as linhas marcadas **[Tenancy]**. Seeders `ShieldPermissionsSeeder` + `PapeisSeeder` no `beforeEach`, como os arquivos vizinhos.

---

## Tabela de decisão — `/cadastro` e `/esqueci-minha-senha` (áreas C e D)

Condições: **chave** (`kit.login.unificado`), **autenticado**, **tenancy**, **token** (válido / inválido / ausente), **org** (`?org=` resolvível — ativa **e** `registro_habilitado` — / não resolvível / ausente), **registro aberto**. Colapsada onde a ação comprovadamente não depende da condição (`—`).

| # | Rota pedida | chave | autenticado | tenancy | token | org | registro | Resultado esperado | Cenário |
|---|---|---|---|---|---|---|---|---|---|
| 1 | `/app/register{?query}` | on | não | — | qualquer | qualquer | — | 302 → `/cadastro{?query}` (query íntegra) | CT-51 |
| 2 | `/cadastro{?query}` | **off** | — | — | qualquer | qualquer | — | 302 → `/app/register{?query}`, que responde 200 (sem laço) | CT-52 |
| 3 | `/cadastro` | on | **sim** | — | — | — | — | redirect para o destino da pessoa (CT-32), sessão preservada, nenhuma conta | CT-54 `@premissa` |
| 4 | `/cadastro?token=T` | on | não | off | **válido** | — | — | 200, formulário do convite (e-mail preenchido e desabilitado), sem redirect | CT-56 |
| 5 | `/cadastro` | on | não | off | ausente | — | **on** | 200, formulário aberto (e-mail vazio e editável) | CT-56 |
| 6 | `/cadastro` | on | não | off | ausente | — | **off** | recusa: termina em `/login`; `User::count()` inalterado | CT-53 |
| 7 | `/cadastro?token=lixo` | on | não | off | **inválido** | — | on | recusa (o token inválido não cai no modo aberto) | CT-53 |
| 8 | `/cadastro?org=acme` **[Tenancy]** | on | não | **on** | ausente | resolvível | on | 200, formulário aberto vinculado à `acme`; gravação cria a conta **na** `acme` | CT-51 linha 3 (GET), CT-55 linha `org` (gravação) |
| 9 | `/cadastro?org=…` **[Tenancy]** | on | não | on | ausente | E2 registro off / E3 inativa / E4 inexistente / E5 ausente | on | recusa, **a mesma** nas quatro linhas (mesma mensagem, mesmo destino); nenhuma conta | CT-53 |
| 10 | `/cadastro?token=T` **[Tenancy]** | on | não | on | válido | ausente | on | 200, convite (o token dispensa `?org=`) | CT-51 linha 4 |
| 11 | `/{painel}/password-reset/request` | on | não | — | — | — | — | **302** → `/esqueci-minha-senha` (os três painéis) | CT-59 |
| 12 | `/esqueci-minha-senha` | on | não | — | — | — | — | 200, formulário de e-mail, sem redirect; pedido envia `ResetPassword` só à conta pedida; e-mail desconhecido não envia nem quebra | CT-59, CT-61 |
| 13 | `/esqueci-minha-senha` | **off** | — | — | — | — | — | 302 → `/app/password-reset/request`, que responde 200 (sem laço) | CT-60 |
| 14 | `/esqueci-minha-senha` | on | **sim** | — | — | — | — | redirect para o destino da pessoa (CT-32) | CT-54 `@premissa` |
| 15 | `/cadastro?token=T` | on | não | off | válido | — | **on** | 200, formulário do **convite** — o convite prevalece sobre o aberto (célula que a linha 4 colapsava, revisão #6) | CT-56 linha 1, CT-55 linha 2 |
| 16 | `/cadastro?token=T` | on | não | off | **expirado** / **já aceito** | — | on | recusa (partições do token inválido além de "inexistente", revisão #5) | CT-53 linhas 3–4 |
| 17 | `/cadastro?token=T&org=acme` **[Tenancy]** | on | não | on | válido | resolvível **ou** E2 | on | 200, convite — o token manda; a organização não recusa quem tem token (revisão #7) | CT-51 linhas 5–6 |
| 18 | `/cadastro?org=acme` **[Tenancy]** | on | não | on | ausente | resolvível | **off** | recusa — o registro global desligado vence a organização (revisão #8) | CT-53 linha 9 |
| 19 | `/cadastro?token=T` | on | **sim** | — | válido | — | on | redirect para o destino da pessoa; convite **não** aceito (revisão #10) | CT-54 linha 3 |

Total: **19 linhas**, todas com cenário. Nas linhas **inválidas** (6, 7, 9, 16, 18) só uma condição é inválida por linha; as combinações **válido × válido** que a primeira versão colapsava (15, 17) e o colapso de `registro` no ramo tenancy (18) foram abertos pela revisão adversarial.

---

## Regra R1 — um cartão por painel registrado que a pessoa acessa; painel novo entra sozinho; nenhum a mais

> `RQ-01`, `RQ-02` · perfil **padrão** · técnica: **EP exaustiva do enum de painéis** (app, admin, infra, **falso `financeiro`**) × **matriz persona** (`admin+infra`, `admin+panel_user`, `master_global`, `admin+financeiro`) · estouro do teto (4 cenários, teto 3) justificado pela revisão adversarial (#14): o fluxo de login sobre a lista dinâmica não é provado por `GET /login/painel`

```gherkin
# language: pt

Funcionalidade: telas externas com o login unificado

  Regra: a escolha e a boas-vindas listam um cartão por painel registrado que a pessoa acessa, e nenhum a mais

    Esquema do Cenário: [CT-43] @premissa a escolha mostra os painéis acessíveis com o rótulo do Panel Switch
      Dado a chave ligada e o nome da aplicação "Projeto Três"
      E uma pessoa autenticada com papel <papel>
      Quando ela abre /login/painel
      Então a resposta é 200, sob o escopo de CSS dos cartões, sem barra lateral nem topbar
      E contém, uma única vez cada, os links de entrada <links presentes>
      E contém os rótulos <rótulos> depois do fechamento de <title> (o cartão, não o título da página)
      E não contém os links <links ausentes>
      E não contém o texto "Painel do negócio"

      Exemplos:
        | papel            | links presentes                                             | rótulos                                       | links ausentes      | # discriminante |
        | admin+infra      | /login/painel/admin, /login/painel/infra                    | Administração, Infraestrutura                 | /login/painel/app   | nenhum é o default |
        | admin+panel_user | /login/painel/admin, /login/painel/app                      | Administração, Projeto Três                   | /login/painel/infra | acessa o app por papel cujo nome ≠ id do painel |
        | master_global    | /login/painel/app, /login/painel/admin, /login/painel/infra | Projeto Três, Administração, Infraestrutura   | —                   | acessa sem papel de painel |

    Esquema do Cenário: [CT-45] @premissa um painel registrado depois da instalação aparece só para quem o acessa
      Dado a chave ligada
      E um painel "financeiro" registrado no Filament, sem nenhuma alteração nos arquivos do kit
      E uma pessoa autenticada com papel <papel>
      Quando ela abre /login/painel
      Então o link /login/painel/financeiro <presença> e o rótulo "Financeiro" <presença>

      Exemplos:
        | papel         | presença      | # discriminante                                  |
        | master_global | está presente | acessa qualquer painel registrado (sem papel "financeiro") |
        | admin+infra   | está ausente  | não acessa "financeiro": lista dinâmica não é "lista todos" |

    Cenário: [CT-65] o login de quem acessa um painel registrado depois da instalação leva à escolha, e ela mostra os dois cartões
      Dado a chave ligada
      E um painel "financeiro" registrado no Filament, e um papel cujo painel é "financeiro"
      E uma pessoa com os papéis admin e esse papel, deslogada
      Quando ela entra pelo formulário de /login
      Então é redirecionada para /login/painel — e não para /admin
      E /login/painel contém os links /login/painel/admin e /login/painel/financeiro, e não /login/painel/app

    Cenário: [CT-46] a boas-vindas lista um cartão por painel registrado e não afirma um número fixo de painéis
      Dado a chave ligada e o nome da aplicação "Projeto Três"
      E um painel "financeiro" registrado no Filament
      Quando um visitante anônimo abre /
      Então a resposta é 200 com um cartão apontando para cada raiz de painel: /app, /admin, /infra e /financeiro
      E contém os rótulos "Projeto Três", "Administração", "Infraestrutura" e "Financeiro" depois do fechamento de <title>
      E não contém "Painel do negócio" nem "Três painéis"
```

> CT-65 (revisão adversarial #14): toda linha de R1 abria `/login/painel` por `GET`; a **decisão de destino no login** (um painel → direto, vários → escolha) podia continuar contando sobre a lista fixa e ficar verde. Fixture: `Role::create(['name' => 'financeiro', 'guard_name' => 'web', 'painel' => 'financeiro'])` — a coluna `roles.painel` é o que `User::canAccessPanel()` compara com o id do painel (`database/migrations/2026_08_13_000001_add_painel_to_roles_table.php:8-14`; `PapeisSeeder.php:316`). Login por `entrarPelaPaginaUnica()`.

> CT-43 **atualiza** CT-17 da ancestral (`tests/Kit/LoginUnificadoTest.php:[CT-17]`): o rótulo `'Painel do negócio'` sai; a **ausência** do cartão do `app` passa a ser afirmada pelo `href` (`route('login.painel.entrar', ['painel' => 'app'])`), não pelo rótulo — `config('app.name')` também aparece no `<title>`, e `assertDontSee` do nome falharia por motivo errado. A contagem "uma única vez" fica sobre o `href`, pelo mesmo motivo.
> CT-46 **atualiza** `tests/Kit/BoasVindasTest.php:56` (`tem um cartao por painel apontando para a raiz do painel`): ganha a linha do painel falso e as asserções de rótulo. `tests/Browser/BoasVindasTest.php:54-56` troca `'Painel do negócio'` por `config('app.name')` (regressão de navegador, sem CT-B novo).

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M-A1 | a lista continua fixa nos três painéis do kit (só o rótulo mudou) | CT-45 (linha `master_global`: `/login/painel/financeiro` ausente), CT-46 |
| M-A2 | itera `Filament::getPanels()` **sem** o filtro de acesso — quem não acessa `financeiro` vê o cartão | CT-45 (linha `admin+infra`), CT-43 (linha `admin+infra`, link `/app` ausente) |
| M-A3 | rótulo do `app` fixo em `'Starter Kit Easy'` (ou mantido `'Painel do negócio'`) em vez de `config('app.name')` | CT-43 (`Projeto Três` **depois de `</title>`** — o título também carrega o nome, revisão #1), CT-46 |
| M-A4 | filtro por **papel do painel** em vez de `canAccessPanel()` — `master_global` perde os cartões de painel sem papel próprio (`financeiro` e `app`) | CT-45 (linha `master_global`), CT-43 (linha `master_global`, link `/app`) |
| M-A4b | filtro "papel com o **nome** igual ao id do painel, ou `master_global`" — passa em todas as linhas com `admin`/`infra`/`master_global` (revisão #2) | CT-43 (linha `admin+panel_user`: acessa o `app` por papel chamado `panel_user`) |
| M-A5 | a boas-vindas continua com a lista antiga (só a escolha ficou dinâmica) ou mantém o subtítulo "Três painéis" | CT-46 |
| M-A10 | os cartões são dinâmicos, mas a **decisão de destino no login** (`DestinoAposLogin`) continua contando sobre os três painéis do kit — quem acessa `admin` + `financeiro` vai direto para `/admin` (revisão #14) | CT-65 |

---

## Regra R2 — rótulo e ícone vêm da mesma fonte que o Panel Switch, com o mesmo fallback

> `RQ-01` · perfil **padrão** · técnica: **paridade contra a instância configurada** do Panel Switch (`PanelSwitch::make()->getLabels()/getIcons()`) + **partição exaustiva** (3 mapeados + 1 fora do mapa)

```gherkin
# language: pt

  Regra: o cartão de cada painel tem exatamente o rótulo e o ícone que o Panel Switch exibe para ele

    Esquema do Cenário: [CT-44] o cartão de cada painel registrado tem o rótulo e o ícone do Panel Switch configurado
      Dado o nome da aplicação "Projeto Três"
      E um painel "financeiro" registrado no Filament
      E o Panel Switch configurado pelo kit, lido pela instância que ele mesmo monta
      Quando se monta o cartão do painel <painel>
      Então o rótulo do cartão é igual ao rótulo que o Panel Switch devolve para <painel>, ou "<fallback rótulo>" se ele não tiver um
      E o ícone do cartão é igual ao ícone que o Panel Switch devolve para <painel>, ou "<fallback ícone>" se ele não tiver um

      Exemplos:
        | painel | fallback rótulo | fallback ícone             | # partição            |
        | app    | —               | —                          | mapeado, rótulo = app.name |
        | admin  | —               | —                          | mapeado               |
        | infra  | —               | —                          | mapeado               |
        | financeiro | Financeiro  | heroicon-o-square-2-stack  | fora do mapa (fallback do pacote) |

    Cenário: [CT-47] dentro do painel, o Panel Switch mostra os mesmos rótulos que a escolha mostrou
      Dado a chave ligada e o nome da aplicação "Projeto Três"
      E um painel "financeiro" registrado no Filament
      E uma pessoa master_global autenticada
      Quando ela abre /admin
      Então a resposta é 200 e contém os rótulos "Projeto Três", "Infraestrutura" e "Financeiro" depois do fechamento de <title>
      E não contém "Painel do negócio"
```

> CT-44 é o único cenário desta wiki na **camada de objeto**: o HTML não permite comparar ícones de forma barata (o SVG renderizado não carrega o nome). O oráculo é a **instância do Panel Switch**, não o literal do PRD — se alguém trocar o rótulo do `admin` no mapa, o cenário continua verde **e o Panel Switch muda junto**, que é exatamente o que RQ-01 pede. O `Então` de rótulo pode ser reafirmado no HTML em CT-43 (é o que acontece).
> **Matador proposto para a renderização do ícone** (M-A6): `assertSee(svg('heroicon-o-rocket-launch')->contents(), false)` no HTML de `/login/painel` — depende de o wrapper do Filament preservar o `contents()` do Blade Icons. Confirmar na implementação; se o markup divergir, a linha fica como **lacuna declarada** (matador só na camada de objeto).

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M-A6 | o cartão recebe o ícone certo no objeto, mas a view do pacote de cartões ignora ícone em string e mostra o default | ⚠️ **matador parcial**: CT-44 prova o objeto; a renderização depende do `svg()->contents()` proposto acima — **lacuna declarada** se não fechar |
| M-A7 | dois mapas com os **mesmos valores hoje**: o Panel Switch continua com os literais inline e os cartões leem de `Paineis` (ou vice-versa) — divergem no primeiro ajuste | ⚠️ **sem matador na suíte** (revisão #3): CT-44/CT-47 comparam **valores**, e dois literais iguais passam. A fonte é `static` e não é alterável em teste; um teste de arquitetura sobre a fonte única (`arch()` proibindo literal de rótulo/ícone fora de `Paineis`) é o matador possível — não escrito, **lacuna declarada** |
| M-A8 | fallback do cartão diferente do fallback do pacote (ex.: `id` sem `ucfirst`, ou ícone `null` → erro de render) | CT-44 (linha `financeiro`), CT-45 (rótulo `Financeiro`), CT-46 |
| ~~M-A9~~ | *removido pela revisão adversarial (#38)*: "`config('app.name')` congelado no boot" não é defeito observável em produção (boot e request leem a mesma config) e o matador citado era o `<title>` | — |

---

## Regra R3 — a página de configurações responde em `/admin/configuracoes-da-aplicacao`; o slug antigo não serve a tela; a doc aponta a URL nova

> `RQ-03` · perfil **mínimo** · técnica: **EP** (URL nova / antiga) + **asserção sobre a doc**. Estouro do teto (3 cenários numa área mínima) justificado: cada um mata um mutante que os outros não alcançam — slug não aplicado, slug antigo ainda servindo, doc desatualizada.

```gherkin
# language: pt

  Regra: a URL da página de configurações reflete "Configurações da aplicação"

    Cenário: [CT-48] a tela de configurações responde na URL nova, e é a URL que o Filament gera para ela
      Dado a chave em qualquer estado (executado com a chave desligada, o padrão da suíte)
      E uma pessoa admin autenticada
      Quando ela abre /admin/configuracoes-da-aplicacao
      Então a resposta é 200 com o componente da página de configurações
      E a URL que a página gera para si mesma termina em /admin/configuracoes-da-aplicacao

    Esquema do Cenário: [CT-49] @premissa o slug antigo redireciona para o novo, para qualquer visitante, e nunca serve a tela
      Dado a chave em qualquer estado (executado com a chave desligada)
      E <visitante>
      Quando abre /admin/configuracoes-do-kit
      Então a resposta é um redirect 301 para /admin/configuracoes-da-aplicacao
      E seguir esse redirect <resultado> — o slug antigo nunca respondeu 200 com o componente

      Exemplos:
        | visitante                    | resultado                                                |
        | uma pessoa admin autenticada | responde 200 com o componente da página de configurações |
        | um visitante anônimo         | é mandado ao login (não ao slug antigo)                  |

    Esquema do Cenário: [CT-50] a documentação dos dois idiomas e o trait global citam a URL nova, e não instruem a antiga
      Dado a documentação do kit em <idioma>
      Quando se lê o texto
      Então ele contém "/admin/configuracoes-da-aplicacao"
      E, filtradas as citações, não contém "/admin/configuracoes-do-kit"

      Exemplos:
        | idioma |
        | pt     |
        | en     |
```

> CT-50 **atualiza** `tests/Kit/ConfiguracoesDoKitDocumentacaoTest.php:26-49` (dataset `urlDaTela`) e `:92` (o trait `ConfiguraFilamentGlobal.php` passa a conter a URL nova). A ausência usa `readmeSemCitacao()` (`.ai/rules/testes.md`: citar não é instruir — o CHANGELOG e a wiki podem citar a antiga).
> O inventário `telasDoKit()` (`tests/Pest.php:270`) e os 20 literais listados no passo 3 do PRD **não ganham CT**: `tests/Kit/InventarioDeTelasTest.php:115,130` reprova sozinho — tela registrada fora do inventário **e** rota morta no inventário. A troca do literal é forçada por esse teste.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M-B1 | `$slug` declarado com tipo errado ou em propriedade que o Filament não lê — a tela continua em `configuracoes-do-kit` | CT-48 (404 na URL nova), CT-49 (200 na antiga) |
| M-B2 | slug novo aplicado, sem redirect — link antigo em favorito responde 404 | CT-49 (`@premissa`; se a premissa for negada, o cenário inverte para 404 e o invariante "não serve a tela" permanece) |
| M-B3 | redirect registrado **sem** o middleware `web`, ou dentro do grupo do painel (com `auth`) — funciona só anônimo, ou só autenticado | CT-49 (as duas linhas: anônimo recebe 301, não redirect ao login — revisão #39) |
| M-B4 | código muda, doc e `KitInfo` continuam com a URL antiga | CT-50 |

---

## Regra R4 — com a chave ligada o registro vive em `/cadastro`; a rota do painel redireciona preservando a query; desligada, o inverso; `/cadastro` serve convite e aberto e recusa sem destino, sem criar conta e sem laço

> `RQ-04` (premissa b) · perfil **completo** · técnica: **tabela de decisão** (linhas 1, 2, 4–10) + **rastreio de não-efeito** na recusa (o destinatário existe: `User` é o agregado; registro **ligado** onde a recusa vem do token)

```gherkin
# language: pt

  Regra: com a chave ligada o registro é /cadastro; a rota do painel leva até lá com a query inteira; desligada, /cadastro devolve ao painel

    Esquema do Cenário: [CT-51] a rota de registro do painel redireciona para /cadastro preservando a query
      Dado a chave ligada
      E <situação>
      Quando um visitante anônimo abre /app/register<query>
      Então a resposta é um redirect temporário (302, nunca 301) para /cadastro<query>
      E seguir esse redirect <resultado final>

      Exemplos:
        | situação                                                              | query                   | resultado final                                                                  | # suíte |
        | registro aberto desligado, nenhum convite                             |                         | termina em /login (recusa; sem conta)                                            | Kit     |
        | registro aberto desligado; convite válido para novo@example.com, token T | ?token=T             | responde 200 com o formulário do convite, e-mail preenchido e desabilitado       | Kit     |
        | registro ligado; acme ativa com cadastro público                      | ?org=acme               | responde 200 com o formulário aberto vinculado à acme                            | Tenancy |
        | registro ligado; convite válido com token T                           | ?token=T                | responde 200 com o formulário do convite (o token dispensa ?org=)                | Tenancy |
        | registro ligado; convite válido T; acme ativa com cadastro público    | ?token=T&org=acme       | responde 200 com o formulário do **convite** (e-mail travado) — o token manda    | Tenancy |
        | registro ligado; convite válido T; acme ativa **sem** cadastro público | ?token=T&org=acme      | responde 200 com o formulário do convite — a organização inválida não recusa quem tem token | Tenancy |

    Esquema do Cenário: [CT-52] com a chave desligada /cadastro devolve à rota do painel, com a query, e não volta
      Dado a chave desligada
      E um convite válido com token T
      Quando um visitante anônimo abre /cadastro<query>
      Então a resposta é um redirect para /app/register<query>
      E /app/register<query> responde 200 — não redireciona de volta

      Exemplos:
        | query    | # arranjo |
        |          | cadastro aberto **ligado** *(alterado em 2026-09-07: sem token e sem aberto a tela do painel RECUSA, e o 302 da recusa não distingue "não volta" de "voltou")* |
        | ?token=T | cadastro aberto desligado; o token basta |

    Esquema do Cenário: [CT-53] /cadastro sem destino recusa como hoje: termina em /login e nenhuma conta nasce
      Dado a chave ligada
      E <situação>
      E existem N usuários cadastrados
      Quando um visitante anônimo abre /cadastro<query>
      Então a primeira resposta é um redirect, e seguir os redirects termina em /login com o formulário de login
      E a recusa é a de hoje, idêntica em todas as linhas: mesma mensagem ("Convite inválido ou expirado") e mesmo destino final
      E continuam existindo N usuários, e o visitante não está autenticado

      Exemplos:
        | situação                                                  | query                  | # partição inválida                 | # suíte |
        | registro aberto desligado, sem convite                    |                        | sem token, sem aberto               | Kit     |
        | registro aberto ligado                                    | ?token=nao-existe      | token inexistente                   | Kit     |
        | registro aberto ligado; convite para x@ com validade vencida | ?token=T            | token **expirado** (revisão #5)     | Kit     |
        | registro aberto ligado; convite para x@ já aceito         | ?token=T               | token **já aceito** (revisão #5)    | Kit     |
        | registro ligado; acme ativa **sem** cadastro público      | ?org=acme              | E2                                  | Tenancy |
        | registro ligado; acme **inativa** com cadastro público    | ?org=acme              | E3                                  | Tenancy |
        | registro ligado; nenhuma organização com esse slug        | ?org=nao-existe        | E4                                  | Tenancy |
        | registro ligado; acme ativa com cadastro público          |                        | E5 — parâmetro ausente              | Tenancy |
        | registro **desligado**; acme ativa com cadastro público   | ?org=acme              | tenancy com registro global off (revisão #8) | Tenancy |

    Esquema do Cenário: [CT-56] /cadastro com a chave ligada serve a própria tela, sem redirecionar
      Dado a chave ligada
      E <situação>
      Quando um visitante anônimo abre /cadastro<query>
      Então a resposta é 200 com o componente do cadastro e o layout de autenticação (fi-auth-layout)
      E o campo de e-mail está <estado do e-mail>

      Exemplos:
        | situação                                                        | query    | estado do e-mail                                  | # partição |
        | registro aberto **ligado** e um convite válido para novo@example.com | ?token=T | preenchido com novo@example.com e desabilitado | convite prevalece sobre o aberto (revisão #6) |
        | registro aberto ligado, sem convite                             |          | vazio e editável                                  | aberto     |
```

> CT-51 **atualiza** duas linhas da ancestral: CT-05 linha `registro sem token de convite` (`tests/Kit/LoginUnificadoTest.php:[CT-05]`, hoje `/app/register` → `/app/login`; passa a `/cadastro` primeiro, e `followingRedirects()` continua terminando em `TelaLoginUnificada`) e **CT-28** (`:[CT-28]`, hoje `/app/register?token=` → 200; passa a 302 → `/cadastro?token=` → 200). As linhas **[Tenancy]** vivem em `tests/Tenancy/LoginUnificadoTenancyTest.php` (novo).
> CT-52 é o controle negativo de R10: `tests/Kit/TelasDeAutenticacaoTest.php:227` (`/app/register?token=` → 200, chave desligada) **não muda** e prova a segunda metade do "não volta".
> CT-53: o não-efeito tem destinatário — `User` é o agregado que o caminho feliz (CT-55) cria; `N` é a contagem antes do pedido, e a recusa acontece em `GET`, então o cenário mede que a recusa **não** cria conta parcial nem autentica (`assertGuest()` como apoio).

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M-C1 | a guarda de laço redireciona `/cadastro` para si mesma (decide por `request()->routeIs()` — `.ai/rules/auth.md`) | CT-56 (200, não redirect), CT-51 (`seguir esse redirect` termina em 200) |
| M-C2 | redirect de `/app/register` **descarta a query** — o token do e-mail de convite antigo se perde e a pessoa é recusada | CT-51 (linhas `?token=T`: o destino carrega o token e o formulário abre com o e-mail travado) |
| M-C3 | redirect feito **também** com a chave desligada, ou `/cadastro` responde 200 desligada (superfície nova sem chave) | CT-52 |
| M-C4 | `/cadastro` desligada redireciona para `/app/register`, que com a chave desligada… redireciona para `/cadastro` (laço nos dois sentidos) | CT-52 (`Então` 2: `/app/register` responde 200) |
| M-C5 | token inválido cai no modo aberto quando o registro está ligado (segunda porta) — inclusive a variante "confere só se o token **existe**", que deixa passar convite vencido ou já aceito | CT-53 (linhas `inexistente`, `expirado`, `já aceito`) |
| M-C6 | com tenancy, `/cadastro?org=` aceita organização inativa ou sem cadastro público (valida só existência) | CT-53 (linhas E2, E3) |
| M-C7 | a recusa em `/cadastro` termina em `/app/login` **sem** seguir para `/login` (chave ignorada na volta), ou grava algo antes de recusar | CT-53 (`Então` 1 e 3) |
| M-C18 | o redirect `/app/register → /cadastro` é **301** (copiado do redirect do slug) — o navegador cacheia e, ao desligar a chave, `/app/register` (cache) → `/cadastro` → `/app/register` vira laço no cliente que nenhum teste HTTP vê — revisão #4 | CT-51 (`Então` 1: 302) |
| M-C19 | com registro aberto ligado, `/cadastro?token=T` ignora o token e serve o formulário aberto ("aberto vence") — o convidado cria conta sem `aceito_em` — revisão #6 | CT-56 (linha convite, registro **ligado**), CT-55 (linha convite) |
| M-C20 | com tenancy, `?org=` é avaliado **antes** do token: `?token=T&org=acme` vira cadastro aberto na acme, e `?token=T&org=fechada` recusa quem tem convite — revisão #7 | CT-51 (duas últimas linhas) |
| M-C21 | o ramo tenancy de `/cadastro?org=` confere a organização e esquece `RegistroAberto::habilitado()` — cadastro aberto com o registro global desligado — revisão #8 | CT-53 (linha `registro desligado; acme`) |

---

## Regra R5 — o formulário de `/cadastro` grava a conta, nas três partições de entrada

> `RQ-04` · perfil **completo** · técnica: **gate de tela de escrita** — gravação por componente em **cada partição do discriminador** (aberto / convite / organização), com o `Então` no agregado persistido

```gherkin
# language: pt

  Regra: o cadastro pela página única grava a conta como o cadastro pelo painel gravaria

    Esquema do Cenário: [CT-55] o formulário de /cadastro cria a conta com o papel e o vínculo da partição
      Dado a chave ligada
      E <situação>
      Quando o visitante envia nome, e-mail e senha válidos no formulário de /cadastro
      Então o formulário não tem erro
      E existe a conta <e-mail> com o papel panel_user
      E o visitante está autenticado como essa conta e a resposta redireciona para o painel app (o registro é funcional, não só gravado)
      E <efeito da partição>

      Exemplos:
        | situação                                                        | e-mail                | efeito da partição                                         | # suíte |
        | registro aberto ligado, sem convite                             | novo@example.com      | ela acessa o painel app e nenhum outro                     | Kit     |
        | registro aberto **ligado** e um convite válido para convidado@example.com, com token na query | convidado@example.com | o convite fica aceito (aceito_em preenchido) e a conta não é tratada como aberta | Kit |
        | registro ligado; acme ativa com cadastro público; ?org=acme     | novo@example.com      | a conta está vinculada à acme, com o papel no contexto dela | Tenancy |
```

> Molde: `registrarAberto()` (`tests/Kit/RegistroAbertoTest.php:64-87`) e `registrarNaOrganizacao()` (`tests/Tenancy/RegistroAbertoTenancyTest.php:62-72`), trocando o componente pelo da página única e mantendo `Filament::auth()->logout()` antes (o `mount()` do Filament redireciona autenticado — `Register.php:59`). Um `Esquema` conta como 1 cenário.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M-C8 | a página única herda a tela mas **não** o `register()` (ex.: `$layout` redeclarado numa classe que não estende a do kit) — o `GET` abre e o `POST` não grava | CT-55 (todas as linhas) |
| M-C9 | o token da query é lido no `mount()` da rota do painel e **não** na página única — o convite abre como formulário aberto e a conta nasce sem aceitar o convite | CT-55 (linha convite: `aceito_em`), CT-56 (e-mail travado) |
| M-C10 | o `?org=` chega ao `GET` mas o `register()` da página única grava sem organização (contexto global) | CT-55 (linha `org`: vínculo e papel no contexto da acme) |
| M-C24 | a página fora de painel grava a conta mas resolve guard/destino errado — a pessoa cai deslogada em `/login` com a conta criada (registro "gravado", não "funcional") — revisão #13 | CT-55 (`Então` 3: autenticada + redirect para o `app`) |

---

## Regra R6 — o link "Cadastre-se" da tela de login aponta para a URL do cadastro, carrega `?org=` quando há organização resolvível, e não aparece sem destino

> `RQ-04` (causa medida em RQ-05) · perfil **completo** · técnica: **tabela de decisão** (registro × chave × tenancy × org). Não-efeito com destinatário: em toda linha "sem link" por organização o **registro está ligado** — é a configuração em que o link **poderia** aparecer; a linha `registro desligado` de CT-58 tem, por sua vez, a organização resolvível como destinatário. "Não existe" é sempre **duas** ausências: o texto do link **e** qualquer `href` para `/cadastro` ou `/app/register` (revisão #17).

```gherkin
# language: pt

  Regra: o link de cadastro do login só aparece quando leva a um cadastro possível, e leva para a URL certa

    Esquema do Cenário: [CT-57] sem tenancy, o link segue a chave e só existe com o registro ligado
      Dado a tenancy desligada
      E o registro aberto <registro> e a chave <chave>
      Quando um visitante anônimo abre <tela de login>
      Então a resposta é 200 e o link de cadastro <resultado>

      Exemplos:
        | registro  | chave     | tela de login | resultado                                                                  |
        | ligado    | ligada    | /login        | existe e aponta exatamente para /cadastro (sem ?org=)                       |
        | ligado    | desligada | /app/login    | existe e aponta exatamente para /app/register                               |
        | desligado | ligada    | /login        | não existe: sem o texto do link e sem nenhum href para /cadastro ou /app/register |

    Esquema do Cenário: [CT-58] @premissa com tenancy, o link só existe com organização resolvível, e a carrega
      Dado a tenancy ligada e o registro aberto <registro>
      E a chave <chave>
      E <organização>
      Quando um visitante anônimo abre <tela de login>
      Então a resposta é 200 e o link de cadastro <resultado>

      Exemplos:
        | registro  | chave     | organização                                      | tela de login          | resultado                                                         | # partição |
        | ligado    | ligada    | acme ativa com cadastro público                  | /login?org=acme        | existe e aponta exatamente para /cadastro?org=acme                | E1 — resolvível |
        | ligado    | desligada | acme ativa com cadastro público                  | /app/login?org=acme    | existe e aponta exatamente para /app/register?org=acme            | E1, chave off |
        | ligado    | ligada    | acme ativa **sem** cadastro público              | /login?org=acme        | não existe (sem texto do link, sem href para /cadastro nem /app/register) | E2 |
        | ligado    | ligada    | acme **inativa** com cadastro público            | /login?org=acme        | não existe                                                        | E3 |
        | ligado    | ligada    | nenhuma organização com esse slug                | /login?org=nao-existe  | não existe                                                        | E4 |
        | ligado    | ligada    | acme ativa com cadastro público, sem ?org=       | /login                 | não existe                                                        | E5 — @premissa |
        | ligado    | desligada | acme ativa **sem** cadastro público              | /app/login?org=acme    | não existe                                                        | E2, chave off (revisão #9) |
        | ligado    | desligada | acme ativa com cadastro público, sem ?org=       | /app/login             | não existe                                                        | E5, chave off (revisão #9) |
        | desligado | ligada    | acme ativa com cadastro público                  | /login?org=acme        | não existe                                                        | registro global off com tenancy (revisão #8) |
```

> CT-57 **não substitui** CT-04b de `tests/Kit/RegistroAbertoTest.php:698` (que afirma `getSubheading()`): acrescenta a dimensão da chave e o **destino** do link. A linha `desligado` é controle. CT-58 vive em `tests/Tenancy/LoginUnificadoTenancyTest.php`; a linha E5 é a que a pergunta ao `00` pode inverter.
> É a regra que fecha o defeito medido no `projeto-3` (RQ-05): organização `padrao` com `registro_habilitado = 0` → hoje o link leva à recusa; com R6, o link não aparece (E2) até a organização ligar "Aceita cadastro público", e quando aparece carrega `?org=` (E1).

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M-C11 | o link continua sendo o `registerAction()` do Filament — aponta para a rota do painel corrente (`/app/register`) mesmo com a chave ligada | CT-57 (linha `ligada`), CT-58 (linha E1) |
| M-C12 | o link aponta para `/cadastro` **também** com a chave desligada | CT-57 (linha `desligada`), CT-58 (linha `chave off`) |
| M-C13 | com tenancy o link aparece sempre (só `RegistroAberto::habilitado()`), sem carregar `?org=` — o dead end medido | CT-58 (E1: `?org=acme` no href; E5: ausência) |
| M-C14 | a organização é considerada resolvível só por **existir** (não confere `ativo` nem `registro_habilitado`) | CT-58 (E2, E3) |
| M-C15 | o link some **também** sem tenancy quando não há `?org=` (condição de tenancy esquecida) | CT-57 (linha `ligado, ligada`: existe, sem `?org=`) |
| M-C13b | variante de M-C13: o link está **sempre** presente e só **anexa** `?org=` quando ele veio — E5 passaria com "não contém `/cadastro?org=`" | CT-58 (E5: ausência do **texto do link** e de qualquer href para `/cadastro`) |
| M-C22 | a validação da organização só existe no ramo da chave ligada — no `/app/login` (chave off) o link aparece sempre e concatena o `?org=` que veio: exatamente o dead end medido no `projeto-3` **antes** do login unificado — revisão #9 | CT-58 (linhas `E2, chave off` e `E5, chave off`) |
| M-C25 | o ramo tenancy do link confere a organização e esquece `RegistroAberto::habilitado()` — link com o registro global desligado — revisão #8 | CT-58 (linha `registro desligado`) |

---

## Regra R7 — "Esqueci minha senha" na página única: `/esqueci-minha-senha` serve a tela e envia o e-mail; as rotas de painel redirecionam; desligada, o inverso; o link do e-mail abre

> `RQ-06` · perfil **padrão** · técnica: **EP por painel** (os três) + **controle negativo** (laço nos dois sentidos) + **rastreio de efeito** (notificação de reset enviada ao destinatário certo, com URL que abre)

```gherkin
# language: pt

  Regra: com a chave ligada a recuperação de senha começa em /esqueci-minha-senha, sem prefixo de painel

    Esquema do Cenário: [CT-59] a página única aponta para /esqueci-minha-senha, que serve a própria tela; as rotas de painel levam até ela
      Dado a chave ligada
      Quando um visitante anônimo abre <rota>
      Então <resultado>

      Exemplos:
        | rota                            | resultado                                                                                        |
        | /login                          | 200; o hint de senha aponta para /esqueci-minha-senha e não para /app/password-reset/request      |
        | /esqueci-minha-senha            | 200 com o componente do pedido de reset, o campo de e-mail e o layout de autenticação, sem redirect |
        | /app/password-reset/request     | redirect temporário (302, nunca 301) para /esqueci-minha-senha                                    |
        | /admin/password-reset/request   | redirect temporário (302) para /esqueci-minha-senha                                               |
        | /infra/password-reset/request   | redirect temporário (302) para /esqueci-minha-senha                                               |

    Esquema do Cenário: [CT-60] com a chave desligada /esqueci-minha-senha devolve à rota do painel default, e nada mais muda
      Dado a chave desligada
      Quando um visitante anônimo abre <rota>
      Então <resultado>

      Exemplos:
        | rota                          | resultado                                                                          | # papel |
        | /esqueci-minha-senha          | redirect para /app/password-reset/request                                          | superfície nova obedece à chave |
        | /app/password-reset/request   | 200 com o componente do pedido de reset — não redireciona de volta                 | sem laço |
        | /admin/login                  | 200; o hint de senha aponta para /admin/password-reset/request                     | controle negativo (R10) |

    Esquema do Cenário: [CT-61] @premissa o pedido em /esqueci-minha-senha envia o e-mail de redefinição só à conta pedida, e o link do e-mail abre com a chave ligada
      Dado a chave ligada, o envio de notificações capturado
      E uma conta admin com o e-mail admin@example.com e outra conta outra@example.com
      Quando um visitante anônimo pede a redefinição para <e-mail pedido> no formulário de /esqueci-minha-senha
      Então <envio>
      E a resposta é a mesma notificação de tela nas duas linhas (quem pede não descobre se o e-mail existe)
      E <link>

      Exemplos:
        | e-mail pedido       | envio                                                                        | link                                                                                                   | # partição |
        | admin@example.com   | a notificação de redefinição foi enviada uma vez, à conta admin, e não à outra | a URL da notificação começa por /admin/password-reset/reset *(alterado em 2026-09-07: o painel do link é o DA PESSOA, não o `app` da rota — sem isso o e-mail não saía; ADR-05)* e abri-la responde 200 com o formulário de redefinição e fi-auth-layout — não é redirecionada a /login | conta existente |
        | ninguem@example.com | nenhuma notificação de redefinição foi enviada a conta alguma                  | —                                                                                                      | conta inexistente (revisão #12) |
```

> CT-59 **atualiza e inverte** CT-40 da ancestral (`tests/Kit/LoginUnificadoTest.php:[CT-40]`, hoje afirma o link para `/app/password-reset/request` e o 200 dele). A rota de painel passa a redirecionar, então o `Então` 2 de CT-40 vira a linha 3 daqui. `IdentidadeDoKitTest.php:488-490` (`/{painel}/password-reset/request` → 200) roda com a chave **desligada** e não muda.
> CT-61: o não-envio à segunda conta é o que separa "enviou ao destinatário certo" de "enviou a qualquer um"; a premissa é o **path** do link (por painel, RQ-06 Assumido); o **invariante** afirmado junto é que o link abre o formulário — vale para qualquer resposta à premissa. Notificação: `Filament\Auth\Notifications\ResetPassword` (`DefinirSenhaPorEmailTest.php:54-59` é o molde).

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M-D1 | o hint da página única continua com `filament()->getRequestPasswordResetUrl()` (rota do painel corrente) | CT-59 (linha `/login`) |
| M-D2 | a guarda de laço posta na classe base faz `/esqueci-minha-senha` redirecionar para si (`.ai/rules/auth.md`) | CT-59 (linha `/esqueci-minha-senha`: 200) |
| M-D3 | o redirect só foi posto na tela de **um** painel (`app`) | CT-59 (linhas `admin`, `infra`) |
| M-D4 | redirect feito também com a chave desligada, ou `/esqueci-minha-senha` desligada responde 200 / redireciona em laço | CT-60 (linhas 1 e 2) |
| M-D5 | a página única redeclara a tela mas perde o `request()` (não envia), ou envia com URL de reset que a chave ligada redireciona para `/login` (dead end no e-mail) | CT-61 (linha `conta existente`) |
| M-D8 | o pedido é feito no contexto do painel da ROTA (`app`): o vendor engole o envio para quem não acessa o `/app`, com a mesma mensagem de sucesso — falha silenciosa *(acrescentado em 2026-09-07: era o comportamento real; a persona `admin` é a discriminante)* | CT-61 (linha `conta existente`: a notificação chega **e** a URL é `/admin/...`) |
| M-D6 | o redirect da rota de painel é **301** — cacheado pelo navegador, vira laço no cliente ao desligar a chave — revisão #4 | CT-59 (linhas 3–5: 302) |
| M-D7 | a página única, com e-mail desconhecido, lança (500) ou responde diferente da tela de painel (enumeração de e-mail) — revisão #12 | CT-61 (linha `conta inexistente`) |

---

## Regra R8 — as telas externas novas vestem o layout de auth sem vazar; as voltas ao login terminam em `/login`; as telas que ficam por painel continuam funcionando

> `RQ-07` + `.ai/rules/auth.md` · perfil **padrão** · técnica: **par obrigatório** (layout não vaza) + **rastreio por origem** (cada volta ao login) + regressão da tela que fica por painel

```gherkin
# language: pt

  Regra: nenhuma tela externa vira dead end com a chave ligada, e o layout de autenticação não veste página comum

    Esquema do Cenário: [CT-62] cada página única nova veste fi-auth-layout, e a página comum do painel aberta em seguida não
      Dado a chave ligada, o registro aberto ligado
      E <página> já respondida com 200 e fi-auth-layout na mesma execução (pré-condição afirmada no arranjo)
      Quando uma pessoa admin autenticada abre /admin
      Então a resposta é 200 sem fi-auth-layout

      Exemplos:
        | página                |
        | /cadastro             |
        | /esqueci-minha-senha  |

    Esquema do Cenário: [CT-63] @premissa toda volta ao login a partir das telas externas termina em /login, em no máximo um salto
      Dado a chave ligada
      E <origem>
      Quando um visitante anônimo segue o link de voltar ao login dessa tela
      Então o href do link é /login, ou uma URL cuja única resposta é um redirect para /login (um salto, nunca mais)
      E o destino responde 200 com o formulário de login da página única

      Exemplos:
        | origem                                                        | # premissa                                |
        | a tela /cadastro?token=T de um convite válido                 | —                                         |
        | a tela /esqueci-minha-senha                                   | —                                         |
        | a tela de conta indisponível (destino da recusa de uma pessoa admin inativa em /login, CT-11 da ancestral) | @premissa — a tela tem link de volta |

    Cenário: [CT-64] a verificação de e-mail continua dentro do painel com a chave ligada
      Dado a chave ligada e a verificação de e-mail exigida no painel app (fixture de tests/Kit/VerificacaoDeEmailTest.php)
      E uma pessoa panel_user autenticada sem e-mail verificado
      Quando ela abre /app
      Então é redirecionada para /app/email-verification/prompt
      E essa página responde 200 com fi-auth-layout — não é redirecionada a /login
```

> CT-62 é o par de `.ai/rules/auth.md` para as **duas** páginas novas — cada subclasse precisa redeclarar `$layout`, e CT-38 da ancestral só cobre `/login`. O `GET` prévio da página nova fica no `Dado` como pré-condição **afirmada** (200 + `fi-auth-layout`), no mesmo molde de CT-38 (`tests/Kit/LoginUnificadoTest.php:[CT-38]`); a metade positiva do par é assim provada no arranjo, e o único `Quando` é o `/admin`.
> CT-63: o "um salto" é a premissa da ancestral (ADR-04 do PRD: `getLoginUrl()` do painel → `/login`); o oráculo é sobre o **href** e sobre a **cadeia** (revisão #11), não só sobre onde termina. CT-64 é a linha ⚠️ da tabela do passo 7 do PRD que o requisito manda revisar; `RegistroAbertoTest.php:642` e `tests/Kit/VerificacaoDeEmailTest.php` cobrem o mesmo com a chave desligada.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M-E1 | `$layout` não redeclarado numa das páginas novas — o layout de auth veste todo o painel a partir dali | CT-62 (linha da página faltante) |
| M-E2 | o `loginAction()` das telas novas aponta para uma rota inexistente (404), ou a guarda de laço da tela de login do painel foi removida "para evitar laço" e `/app/login` volta a servir a `TelaLogin` do painel | CT-63 (`Então` 1: um salto e destino `/login`; `Então` 2: o componente é o da página única) |
| M-E3 | a guarda de laço posta na base do Auth Designer captura a verificação de e-mail e a manda para `/login` | CT-64 |

---

## Regra R9 — quem já está autenticado não vê as páginas únicas de cadastro nem de recuperação

> `RQ-04`, `RQ-06`, `RQ-07` · perfil **completo** · técnica: **EP** (as duas páginas) · premissa de comportamento com direção **falha fechado**: não mandar a pessoa para um painel que ela não acessa

```gherkin
# language: pt

  Regra: as páginas únicas de cadastro e de recuperação mandam quem já entrou para o destino dela

    Esquema do Cenário: [CT-54] @premissa autenticado, /cadastro e /esqueci-minha-senha levam ao destino da pessoa, sem criar conta e sem encerrar a sessão
      Dado a chave ligada e o registro aberto ligado
      E uma pessoa admin autenticada, e N usuários cadastrados
      E <extra>
      Quando ela abre <página>
      Então é redirecionada para /admin — o mesmo destino que /login lhe dá
      E não para /app
      E continua autenticada, e continuam existindo N usuários
      E <invariante extra>

      Exemplos:
        | página               | extra                                        | invariante extra                                   |
        | /cadastro            | —                                            | —                                                  |
        | /esqueci-minha-senha | —                                            | —                                                  |
        | /cadastro?token=T    | um convite válido para x@example.com, token T | o convite continua não aceito (aceito_em nulo) — revisão #10 |
```

> Persona **discriminante**: `admin` não acessa o `app`; é o único papel em que "destino da pessoa" e "`Filament::getUrl()` do painel corrente" divergem. `panel_user` passaria com o mutante. Se a premissa for negada, o `Então` 1 vira `/app` e os demais permanecem.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M-C16 | o `mount()` do vendor fica intacto: autenticado vai para `Filament::getUrl()` do painel corrente (`/app`), onde `admin` toma 403 | CT-54 (`Então` 1 e 2) |
| M-C17 | a página única trata autenticado como anônimo e serve o formulário (uma segunda conta pode ser criada na mesma sessão) | CT-54 (`Então` 1: redirect, não 200) |
| M-C23 | o `mount()` valida o token e serve o convite **antes** de olhar `auth()` — autenticado com link de convite vê o formulário — revisão #10 | CT-54 (linha `/cadastro?token=T`: redirect e `aceito_em` nulo) |

---

## Checklist de Taxonomia

| Item | Cenário que mata |
|---|---|
| IDOR / autorização horizontal | não se aplica: nenhuma rota recebe `{id}`; `?org=` é slug **público** e a autorização dele é a decisão E1–E5 (CT-53, CT-58) |
| Autorização exercida na ação (não só `can()`) | CT-45 (a lista filtra por acesso, na tela), CT-55 (a gravação ocorre com o papel certo), CT-53 (a recusa não cria conta). **Por fora da UI**: CT-51/CT-53 são HTTP, não componente; a recusa por organização já é exercida direto no domínio por `RegistroAbertoTenancyTest.php:161,400` (`RegistroAberto::registrar()` lança) |
| Idempotência (ancorada no agregado) | não se aplica como regra nova: o duplo envio do registro é o throttle de `RegistroAbertoTest.php:672`; nada desta wiki muda o `register()` |
| Concorrência | não se aplica |
| Fronteira no ponto de entrada (gravação) | CT-55 (as três partições gravam), CT-53 (as inválidas não gravam) |
| Domínio condicionado (tipo × valor) | CT-58 — a validade de `?org=` depende de `ativo` **e** `registro_habilitado` (E2, E3 isolados) |
| Estado × operação de escrita | CT-53 linha E3 — organização **inativa** não recebe cadastro pela página única |
| Ausente ≠ null ≠ vazio | CT-53 (E5 `?org=` ausente × E4 slug desconhecido), CT-52 (query vazia × `?token=`) |
| Paginação / ordenação | não se aplica |
| Timezone / DST | não se aplica: a validade do convite é de `ConviteTest` |
| Unicode / limite de varchar | não se aplica: nenhum campo novo |
| Unicidade + soft delete | não se aplica aqui (o e-mail único do cadastro é de `RegistroAbertoTest`) |
| CRUD combinado | não se aplica |
| Mass assignment | não se aplica: o formulário é o do Filament, já coberto em `RegistroAbertoTest.php:136` |
| Upload | não se aplica |
| Precisão monetária | não se aplica |
| **Open redirect** (linha da ancestral) | CT-51/CT-52 — a query é preservada, mas o **destino** é rota nomeada; não há redirect para URL vinda do pedido |
| **Laço de redirecionamento** (linha da ancestral) | CT-51 (`seguir esse redirect` termina em 200/login), CT-52, CT-56, CT-59, CT-60 |
| **Lista fixa onde o requisito pede lista dinâmica** (linha nova desta feature) | CT-45, CT-46 — painel falso registrado em teste |
| **Duas fontes para o mesmo dado exibido** (linha nova) | CT-44, CT-47 |

## Índice de Cenários

| ID | Cenário | Regra | Técnica | Camada | Arquivo | Novo / atualiza | Mata |
|----|---------|-------|---------|--------|---------|-----------------|------|
| CT-43 | escolha: rótulos do Panel Switch (depois do `</title>`), `app` = `config('app.name')`, ausência pelo `href` (3 linhas) | R1 | EP exaustiva + matriz | Kit (HTTP) | `tests/Kit/LoginUnificadoTest.php` | **atualiza CT-17** | M-A2, M-A3, M-A4, M-A4b |
| CT-44 | paridade rótulo+ícone com `PanelSwitch::make()` por painel, incl. falso (4 linhas) | R2 | paridade + partição exaustiva | Kit (objeto) | idem | novo | M-A6 (parcial), M-A8 · M-A7 **lacuna** |
| CT-45 | painel falso `financeiro` aparece só para quem o acessa (2 linhas) | R1 | matriz persona × painel | Kit (HTTP) | idem | novo | M-A1, M-A2, M-A4, M-A8 |
| CT-46 | boas-vindas: cartão por painel registrado, rótulos depois do `</title>`, sem "Três painéis" | R1 | EP exaustiva | Kit (HTTP) | `tests/Kit/BoasVindasTest.php` | **atualiza** `tem um cartao por painel…:56` | M-A1, M-A3, M-A5, M-A8 |
| CT-47 | Panel Switch no `/admin` com os mesmos rótulos (depois do `</title>`) | R2 | paridade (HTML) | Kit (HTTP) | `tests/Kit/LoginUnificadoTest.php` | novo | M-A7 (parcial: só valores) |
| CT-48 | URL nova responde 200; `getUrl()` termina nela | R3 | EP | Kit (HTTP) | `tests/Kit/ConfiguracoesDoKitTelaTest.php` | novo | M-B1 |
| CT-49 | `@premissa` slug antigo → 301 para anônimo e autenticado, nunca serve a tela (2 linhas) | R3 | EP + invariante | Kit (HTTP) | idem | novo | M-B1, M-B2, M-B3 |
| CT-50 | doc pt/en e trait citam a URL nova, não instruem a antiga (2 linhas) | R3 | asserção sobre doc | Kit (arquivo) | `tests/Kit/ConfiguracoesDoKitDocumentacaoTest.php` | **atualiza** `:26-49`, `:92` | M-B4 |
| CT-51 | `/app/register{query}` → 302 `/cadastro{query}` → resultado (6 linhas; 4 Tenancy, incl. token × org) | R4 | tabela de decisão | Kit + Tenancy (HTTP) | `tests/Kit/LoginUnificadoTest.php`, `tests/Tenancy/LoginUnificadoTenancyTest.php` (novo) | **atualiza CT-05** (linha registro) **e CT-28** | M-C1, M-C2, M-C18, M-C20 |
| CT-52 | desligada: `/cadastro{query}` → `/app/register{query}` → 200 (2 linhas) | R4 | controle negativo | Kit (HTTP) | `tests/Kit/LoginUnificadoTest.php` | novo | M-C3, M-C4 |
| CT-53 | `/cadastro` recusa sem destino — a mesma recusa, sem conta (9 linhas; 5 Tenancy) | R4 | tabela de decisão + não-efeito | Kit + Tenancy (HTTP) | idem + Tenancy | novo | M-C5, M-C6, M-C7, M-C21 |
| CT-54 | `@premissa` autenticado nas páginas únicas → destino da pessoa; convite intacto (3 linhas) | R9 | EP, falha fechado | Kit (HTTP) | `tests/Kit/LoginUnificadoTest.php` | novo | M-C16, M-C17, M-C23 |
| CT-55 | gravação por componente em `/cadastro`: aberto, convite (registro ligado), org — autenticada e redirecionada (3 linhas; 1 Tenancy) | R5 | gate de tela de escrita | Kit + Tenancy (Livewire + DB) | idem + Tenancy | novo | M-C8, M-C9, M-C10, M-C19, M-C24 |
| CT-56 | `/cadastro` serve a própria tela: convite (com registro ligado) / aberto (2 linhas) | R4 | EP | Kit (HTTP) | `tests/Kit/LoginUnificadoTest.php` | novo | M-C1, M-C9, M-C19 |
| CT-57 | link de cadastro sem tenancy: chave × registro; href exato; ausência dupla (3 linhas) | R6 | tabela de decisão | Kit (HTTP) | idem | novo (CT-04b de `RegistroAbertoTest` continua) | M-C11, M-C12, M-C15 |
| CT-58 | `@premissa` link de cadastro com tenancy: E1–E5, chave off em E1/E2/E5, registro off (9 linhas) | R6 | tabela de decisão + não-efeito | Tenancy (HTTP) | `tests/Tenancy/LoginUnificadoTenancyTest.php` | novo | M-C11, M-C12, M-C13, M-C13b, M-C14, M-C22, M-C25 |
| CT-59 | `/login` aponta para `/esqueci-minha-senha`; ela serve; painéis redirecionam com 302 (5 linhas) | R7 | EP por painel | Kit (HTTP) | `tests/Kit/LoginUnificadoTest.php` | **atualiza e inverte CT-40** | M-D1, M-D2, M-D3, M-D6 |
| CT-60 | desligada: `/esqueci-minha-senha` → painel, sem laço; hint do `/admin/login` (3 linhas) | R7 | controle negativo | Kit (HTTP) | idem | novo | M-D4 |
| CT-61 | `@premissa` pedido envia `ResetPassword` só à conta pedida; e-mail desconhecido não envia; link abre (2 linhas) | R7 | rastreio de efeito + não-efeito | Kit (Livewire + Notification::fake + HTTP) | idem | novo | M-D5, M-D7 |
| CT-62 | par de layout para `/cadastro` e `/esqueci-minha-senha` (2 linhas) | R8 | par obrigatório | Kit (HTTP) | idem | novo | M-E1 |
| CT-63 | `@premissa` voltas ao login: href direto ou um salto, destino `/login` (3 linhas) | R8 | rastreio por origem | Kit (HTTP) | idem | novo | M-E2 |
| CT-64 | verificação de e-mail continua no painel com a chave ligada | R8 | regressão | Kit (HTTP) | idem | novo | M-E3 |
| CT-65 | login de `admin+financeiro` cai na escolha com os dois cartões — o destino conta sobre a lista dinâmica | R1 | fluxo (Livewire + HTTP) | Kit | idem | novo (revisão adversarial #14) | M-A10 |

**Contagem por RQ**: RQ-01 → CT-43, CT-44, CT-47 (2 novos, 1 atualizado) · RQ-02 → CT-45, CT-46, CT-65 (2 novos, 1 atualizado) · RQ-03 → CT-48, CT-49, CT-50 (2 novos, 1 atualizado) · RQ-04 → CT-51…CT-58 (7 novos, 1 atualizado) · RQ-05 → nenhum (investigação; fecha via R6) · RQ-06 → CT-59, CT-60, CT-61 (2 novos, 1 atualizado) · RQ-07 → CT-54, CT-62, CT-63, CT-64 (4 novos; CT-54 compartilhado com RQ-04/RQ-06). Total: **18 novos, 5 atualizados**.

## Testes existentes atualizados (sem CT novo)

| Teste existente | O que muda | Por causa de |
|---|---|---|
| `tests/Kit/LoginUnificadoTest.php:[CT-17]` | vira **CT-43** (rótulo do `app` = `config('app.name')` discriminante; ausência pelo `href`) | R1 |
| `tests/Kit/LoginUnificadoTest.php:[CT-05]` linha `registro sem token de convite` | primeiro destino `/app/login` → `/cadastro`; o `followingRedirects()` continua em `TelaLoginUnificada` — absorvida por **CT-51** linha 1 | R4 |
| `tests/Kit/LoginUnificadoTest.php:[CT-28]` | `/app/register?token=` → 302 `/cadastro?token=` → 200 — absorvida por **CT-51** linha 2 | R4 |
| `tests/Kit/LoginUnificadoTest.php:[CT-40]` | **inverte** → **CT-59** | R7 |
| `tests/Browser/LoginUnificadoTest.php:33-38` | `assertDontSee('Painel do negócio')` → ausência do link do `app` (`assertNotPresent('a[href$="/login/painel/app"]')` ou equivalente); `assertSee('Administração')`, `click('Administração')` **continuam** | R1 (regressão de navegador; CT-B01 segue válido) |
| `tests/Browser/BoasVindasTest.php:54-56` | `'Painel do negócio'` → `(string) config('app.name')` | R1 |
| `tests/Kit/BoasVindasTest.php:56` | ganha linha do painel falso + rótulos → **CT-46** | R1 |
| `tests/Kit/ConfiguracoesDoKitDocumentacaoTest.php:26-49,92` | URL nova → **CT-50** | R3 |
| `tests/Pest.php:270` (`telasDoKit()`) + 20 literais (`ConfiguracoesDoKitTelaTest:286`, `SegredosDoSettingsTest:358`, `LoginSocialGoogleTest:892`, `ProtecaoAntiRoboTest:737,923,944`, `tests/Browser/ConfiguracoesDoKitTest:44,77,101`, `tests/Browser/LoginSocialTest:158,163`, `tests/BrowserTenancy/CapturaDeArteTest:324,326,482,494`) | slug novo; **forçado** por `InventarioDeTelasTest.php:115,130` — sem CT próprio | R3 |
| `tests/Kit/RegistroAbertoTest.php:698` (CT-04b) | **não muda**; CT-57 acrescenta chave e destino | R6 |
| `tests/Kit/TelasDeAutenticacaoTest.php:227,234` | **não muda** (chave desligada) — é o controle de CT-52 | R10 |
| `tests/Kit/IdentidadeDoKitTest.php:488-490` | **não muda** (chave desligada) | R10 |

## Sem CT-B

- Motivo: nenhum `Então` desta wiki depende de JavaScript executado, tema ou layout — rótulo, ícone (objeto), `href`, redirect, recusa, gravação e notificação são provados por HTTP, componente Livewire e `Notification::fake()`. A tabela `## Superfície de UI` do PRD marca "Depende de JS? Não" em todas as linhas, e a derivação confirma.
- O **CT-B01 da ancestral** (`tests/Browser/LoginUnificadoTest.php`) continua sendo a regressão de navegador: o rótulo que ele clica ("Administração") não muda; só a asserção de ausência do cartão do `app` troca de texto para `href` (tabela acima). Não é CT-B novo.
- Cogitado e cortado: (1) "o cartão do painel falso é clicável e leva a `/financeiro`" — mata o mesmo mutante que CT-45 (`href` presente) e o clique é o que CT-B01 já prova para um cartão real; (2) "tema escuro na tela de escolha com quatro cartões" — nenhum mutante previsto depende de cor; `tests/Browser/BoasVindasTest.php` CT-B02 já cobre acessibilidade dos cartões nos dois temas.

## Divergência declarada com a skill

- **Camada Unit**: a skill sugere `Unit` para "valor calculado"; `tests/Pest.php` não liga `tests/Unit` ao `TestCase` da aplicação, então CT-44 (paridade de objeto) roda na suíte `Kit`, que tem container e config — não existe camada mais barata sustentada pelo arnês.
- **Tenancy**: a skill sugere `config()->set()` para ligar modo; aqui a tenancy é decidida no `createApplication()` (`tests/TestCase.php`), e a rule `.ai/rules/testes.md` vence — as células com tenancy vivem em `tests/Tenancy/`.
- **Comandos**: `composer test:kit` (Kit + Tenancy, `--parallel`, sem `--tia`) e `composer test:browser` em série (`.ai/rules/testes-browser.md`), como na ancestral.
- **Revisão adversarial**: a skill pede sub-agente sem o PRD nem o raciocínio de quem derivou. Executada por sub-agente independente que recebeu **só** o `00` e este arquivo; o fechamento dos achados é de quem derivou (abaixo).

## Revisão adversarial (Impacto 3 nas áreas A, C e D)

### Rodada 1 — 47 achados, todos fechados (2026-09-06)

Sub-agente independente recebeu **só** o `00-requisito.md` e a primeira versão deste arquivo (sem PRD, sem código, sem o raciocínio de derivação). Percorreu R1–R10, a tabela de decisão célula a célula, SFDIPOT, checklist, índice e a seção de testes atualizados. Fechamento:

| # | Achado | Destino |
|---|---|---|
| 1, 15, 38, 40, 42 | `assertSee('Projeto Três')` satisfeito pelo `<title>` — M-A3/M-A9 não morriam | CT-43, CT-46, CT-47 afirmam o rótulo **depois de `</title>`** (`assertSeeInOrder(['</title>', 'Projeto Três'])`). **M-A9 removido** (não é defeito observável em produção) |
| 2, 43 | filtro "papel com nome igual ao id do painel, ou master" passava em toda a matriz | linha `admin+panel_user` em **CT-43**; **M-A4b** |
| 3, 41 | "duas fontes com valores iguais" — CT-44/CT-47 comparam valores, não fonte | **M-A7 declarado sem matador** (lacuna estrutural; matador possível é `arch()` sobre a fonte única, não escrito) |
| 4, 19, 35 | redirects de painel sem código de status — 301 copiado do slug vira laço no cache do navegador ao desligar a chave | CT-51 e CT-59 afirmam **302**; **M-C18**, **M-D6**; SFDIPOT T reescrito |
| 5, 44 | token inválido só como "inexistente" | CT-53 ganha linhas **expirado** e **já aceito**; M-C5 estendido |
| 6, 24, 37 | token válido × registro aberto ligado colapsado — "aberto vence" passava | CT-56 e CT-55 (linhas convite) fixam **registro ligado**; **M-C19**; linha 15 da tabela |
| 7 | token × org nunca cruzados nas linhas válidas | CT-51 ganha `?token=T&org=acme` (resolvível **e** E2); **M-C20**; linha 17 |
| 8 | tenancy + registro global desligado inexistente | CT-53 linha 9 e CT-58 linha `registro desligado`; **M-C21**, **M-C25**; linha 18 |
| 9, 36 | validação da organização só com a chave ligada — o dead end medido no `projeto-3` existia **antes** do login unificado | CT-58 ganha E2 e E5 com a **chave desligada**; **M-C22** |
| 10 | autenticado × token válido: token checado antes do `auth()` | CT-54 linha `/cadastro?token=T` com `aceito_em` nulo; **M-C23**; linha 19 |
| 11, 18, 46 | CT-63 "termina em `/login`" aceitava qualquer cadeia | `Então` sobre o **href** (direto ou um salto) + componente da página única; M-E2 reescrito |
| 12, 34 | e-mail inexistente no reset declarado no SFDIPOT e não executado | CT-61 vira `Esquema` com a linha `conta inexistente` (não-efeito com destinatário: as duas contas existem); **M-D7** |
| 13, 20 | CT-55 gravava e não provava "funcional" | `Então`: autenticada como a conta criada + redirect para o `app`; **M-C24** |
| 14 | destino do login ainda podia contar sobre a lista fixa | **CT-65** (persona `admin+financeiro`, papel com `roles.painel = financeiro`); **M-A10**; teto de R1 estourado com justificativa |
| 16 | rótulo `X` de uma letra casa com texto acidental | painel falso renomeado para **`financeiro`** / `Financeiro` |
| 17, 45 | "não existe" sem dizer o quê — variante de M-C13 sobrevivia | CT-57/CT-58: ausência **dupla** (texto do link e qualquer href para `/cadastro` ou `/app/register`); **M-C13b** |
| 21 | CT-49 afirmava ausência de componente num 301 (corpo vazio) | `Então` sobre seguir o redirect + invariante "o slug antigo nunca respondeu 200 com o componente" |
| 22, 26 | CT-62 com a metade positiva escondida no `Dado` | `Dado` declarado como pré-condição **afirmada** (200 + `fi-auth-layout`), molde de CT-38 da ancestral |
| 23 | tabela dizia "a mesma recusa" e CT-53 não afirmava | CT-53 afirma mesma mensagem ("Convite inválido ou expirado", texto que o `00` fixa como inalterado) e mesmo destino em todas as linhas |
| 25 | CT-46…CT-49 sem a chave no `Dado` | CT-46/CT-47: chave ligada; CT-48/CT-49: "qualquer estado, executado com a chave desligada" |
| 27 | CT-60 com três `GET` num cenário | vira `Esquema` de três linhas |
| 28 | persona "admin inativa" fora do Setup | acrescentada ao Setup, com o molde de CT-11 |
| 29 | CT-64 sem fixture de verificação de e-mail | cita `tests/Kit/VerificacaoDeEmailTest.php` como molde |
| 30 | CT-44 é objeto onde RQ-01 fala de tela | mantido e declarado; M-A6 fica parcial |
| 31 | tabela linha 8 citava CT-56 (que não tem linha `org`) | corrigido para CT-51 linha 3 + CT-55 linha `org` |
| 32, 33 | "Regras: 9" e "7 atualizam / 15 novos" errados | 10 regras; 5 atualizam / 18 novos (com CT-65) |
| 39, 47 | M-B3 "só autenticado" sobrevivia (linha anônima descartada como redundante) | CT-49 vira `Esquema` anônimo/autenticado, ambos 301 |

**Re-revisão**: não executada. Os fechamentos que criaram superfície nova são linhas de `Esquema` em regras existentes e um cenário (CT-65) numa regra existente; nenhum criou regra nova. Registrado como decisão — se o quality gate apontar lacuna de segunda ordem em CT-65 ou nas linhas token × org, a segunda rodada roda aí.
