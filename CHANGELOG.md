# Changelog

Formato baseado em [Keep a Changelog](https://keepachangelog.com/pt-BR/1.1.0/);
versionamento [SemVer](https://semver.org/lang/pt-BR/).

## [0.13.0] - 2026-08-14

O kit ganha a camada de teste que não tinha: **navegador real, com JavaScript
executando, sobre as 52 telas dos três painéis**. Até aqui a cobertura de tela era
HTTP (`$this->get()`), que prova que o servidor devolveu 200 e nada além disso — e
um painel Filament é Livewire + Alpine, então o HTML pode vir íntegro com status
200 e a tela estar inutilizável porque um `x-on:click` estourou, porque um asset
do Vite não subiu ou porque um componente de plugin registrou erro no console.
Nenhuma dessas três falhas move o status HTTP.

A rodada também virou uma auditoria: **11 dívidas técnicas identificadas, uma
paga**, e duas Project Rules novas para o que foi aprendido não se perder. Wiki
completa em `wikis/specs/feature/wiki-regressao-telas/regressao-de-telas/`.

### Adicionado

- **Suíte de testes de browser** (`tests/Browser`, grupo `browser`), com 11
  cenários cobrindo **100% das 52 telas alcançáveis por URL fixa** — das 74 rotas
  GET dos painéis, 13 exigem `{record}`, 3 são endpoint JSON de passkey e 6 exigem
  estado ou token. Rode com `composer test:browser`, que embute o `npm run build`
  de que a suíte depende.

  O painel `/app` ganha aqui a **primeira cobertura de tela que já teve**: o
  smoke HTTP cobria 15 rotas de `/infra` e 3 de `/admin`, e o painel de negócio —
  o único que o consumidor do kit usa todo dia — tinha só o `GET /app` genérico.

  Fica **fora** do `composer test:kit` de propósito: aquele é o comando de
  resposta rápida depois de um `kit:update`, e browser em série custa ordens de
  magnitude mais que HTTP.

- **Validação de perfis pela tela.** Cada papel entra no painel dele e vê uma
  página de 403 **legível** no painel negado — o teste HTTP afirmava
  `assertForbidden()`, que passa igual se o usuário barrado receber tela branca.

- **Validação de dark mode.** `->inDarkMode()` nos três dashboards, e o alternador
  de tema exercitado por clique. Com uma ressalva honesta, que está na wiki:
  `assertSee()` **passa** com texto branco em fundo branco, então o teste prova
  que a tela abre sob `prefers-color-scheme: dark`, não que está legível. A
  legibilidade foi conferida por inspeção visual de 9 telas nos dois temas —
  nenhum texto ilegível, ícone sumido ou logo com fundo cravado.

- **Job de CI `telas`**, com Node, browsers do Playwright e build do Vite.
  Separado do job de qualidade, que passa a rodar `--exclude-group=browser`:
  registrar a testsuite nova fazia `php artisan test` incluí-la, e o CI quebraria
  em toda tela com `ViteException`.

- **Duas Project Rules** em `.ai/rules/`: `testes.md` (glob `tests/**`) e
  `testes-browser.md` (glob `tests/Browser/**`). A segunda registra os quatro
  fatos sobre o `pest-plugin-browser` que a doc oficial não diz e que custaram uma
  sonda inteira — entre eles que **o plugin sobe o próprio servidor in-process**,
  então `:memory:`, `RefreshDatabase` e `$this->actingAs()` continuam valendo
  dentro do navegador, e nenhum Herd ou `artisan serve` é necessário.

- **`tests/Kit/HelpersDeTesteTest.php`** — guarda automática contra helper de
  teste usado de outro arquivo. Usa `token_get_all()` e não regex, porque menção
  em docblock é comum nesta suíte e guarda com falso positivo ensina o time a
  ignorá-la.

### Corrigido

- **Helper de teste declarado dentro de arquivo de teste** (era a dívida
  bloqueante). Em PHP função é global no processo: quando o Pest carrega **todos**
  os arquivos, um helper declarado em `AlgumTest.php` vaza para o vizinho e tudo
  passa — o acoplamento fica invisível. Ele só aparece em execução **parcial**, que
  é o que fazem `--parallel`, `--tia` e `pest tests/Kit/AlgumTest.php`.

  Eram **7 erros** `Call to undefined function` em `--parallel`, e o `--tia` do
  Pest 5 — a feature que motivou o upgrade — era inutilizável. `usuarioCom()`,
  `noPainelDa()` e `pivotDePapeis()` foram para `tests/Pest.php`, e **dois clones
  desapareceram**: existiam só para escapar da colisão de redeclaração, cada um
  idêntico ao original, trocando um erro que estoura por duas funções iguais que
  ninguém percebe.

  Medido: `pest --parallel --group=kit` de 206/213 com 7 erros para **214/214** em
  196 s, contra 818 s em série — **4,2× mais rápido**.

- **`tests/Unit/ExampleTest.php` convertido para Pest.** Era o scaffolding
  class-based do Laravel, e o `--tia` **aborta a execução inteira** ao encontrar
  uma classe PHPUnit. Um arquivo esquecido desligava o Test Impact Analysis para o
  projeto todo.

### Alterado

- **Pest 4.7 → 5.1** e **PHPUnit 12.5 → 13.3** (requisito duro do Pest 5), mais
  `pestphp/pest-plugin-browser` 5.0 e `playwright`. São dependências de
  desenvolvimento: nada muda em runtime para quem usa o kit.
- `pest()->tia()->defaultBranch('main')->locally()` em `tests/Pest.php` — o default
  do TIA é `master`, e o `locally()` liga o TIA no desenvolvimento e o desliga em
  CI, como a doc do Pest recomenda.

### Conhecido

Dez dívidas seguem abertas, com custo estimado e caminho de correção em
`06-divida-tecnica.md`. As que mais importam:

- **Sem PCOV**, o `--tia` é impraticável: com Xdebug, em série não termina
  (abortado após 35 min), e `--parallel` derruba 4 dos 11 cenários de browser
  porque multiplica processos de navegador. O contorno são dois comandos.
- **Botão *Clear Cache* sem texto acessível** (a11y *critical*) no `/infra`, e
  **contraste 4.25:1** no indicador de ambiente (a11y *serious*, só no tema claro).
  Ambos em `vendor/`.
- **Render hook de plugin vaza entre painéis** no mesmo processo PHP: `/admin`
  isolado tem 0 botões de *Clear Cache* e 9 depois de visitar `/infra`. Impacto em
  produção hoje é nulo (sem Octane), mas os testes de browser validam um DOM
  contaminado.
- **Nenhum `data-testid`** nas telas, então os seletores são `id` de framework,
  texto visível e `aria-label`.

## [0.12.0] - 2026-08-14

O convite deixa de ser só uma porta para gente nova. Três features nasceram de
ler o código-fonte de dois pacotes que resolvem o mesmo problema —
[`jeffersongoncalves/teamkitv4`](https://github.com/jeffersongoncalves/teamkitv4)
(via `jeffersongoncalves/filament-teams`) e
[`offload-project/laravel-invite-only`](https://github.com/offload-project/laravel-invite-only).
**Nenhum dos dois é instalado**; o que se copiou foram ideias, e três decisões
saíram dos defeitos deles. Cada feature tem a wiki dela em `wikis/specs/main/`.

### Adicionado

- **Convite para quem já tem conta.** Convidar um endereço que já é usuário
  deixa de ser erro e passa a ser **oferta de acesso**: a pessoa entra com a
  senha que já tem, confirma, e é vinculada com o papel do convite. Ou **recusa**,
  e a recusa fica registrada — "ela disse não" é diferente de "o convite
  desapareceu".

  Era uma parede no caso mais comum de SaaS multi-tenant: a consultora que
  atende dois clientes, a funcionária em duas unidades. Antes, só o
  `master_global` resolvia, por `/admin` → Organizações → *Vincular usuário*; o
  `admin_organizacao`, a persona criada justamente para dar autonomia à
  organização, **não conseguia**.

  Duas vias, uma tabela, decididas **no aceite** e não na criação (entre criar e
  clicar passam dias, e a pessoa pode ter criado conta por outro caminho). Na via
  de conta nova o token é **suficiente**; na de oferta é **necessário mas não
  suficiente** — exige também que o e-mail do autenticado seja o do convite.

  Junto vem a **caixa de entrada de convites**, no menu do usuário do `/app` com
  a contagem das ofertas pendentes. Ela não substitui o link: vive sob
  `/app/{tenant}`, então não alcança quem tem zero organizações nem quem só tem
  papel de `/admin` ou `/infra`. Ganha o lugar dela por outro motivo — é o único
  lugar onde a **recusa** existe.

- **Convite em massa.** Colar vários endereços, um papel e uma organização para o
  lote, e o resultado por endereço: quantos foram e quais falharam, com o motivo
  de cada um. O lote **não aborta** por causa de um endereço torto.

- **Lembretes de convite pendente.** `kit:convites-lembrar`, agendado às 08:00,
  reenvia em D+3 e D+5. Idempotente e com catch-up: cron parado por dias não
  produz rajada, e cada convite recebe no máximo um lembrete por execução.

  O lembrete leva um **segundo token, também hasheado** (`token_lembrete`), e o
  link original **não é tocado**. Foi a saída do beco: o token em claro existiu
  só no momento do envio, e as alternativas eram rotacionar (um lembrete não
  entregue revogaria o convite) ou guardar uma cópia reversível no banco (que
  contradiria uma promessa já publicada aqui). Ver ADR-01 da wiki.

- **`email_verified_at` no aceite de conta nova**: o token prova posse do
  endereço, então pedir verificação depois é pedir a mesma prova duas vezes.
  Inócuo hoje; no dia em que alguém ligar `->emailVerification()`, sem isso todo
  usuário nascido de convite é barrado na porta.

### Corrigido

- **A subtração do `panel_user` não cobria Page nem Widget.**
  `Paineis::permissoes('app')` sai de `getEntitiesPermissions()`, que mistura
  Resources, Pages, Widgets e permissions custom — mas a subtração só varria
  Resources. Medido: 38 permissões no painel `app`, 36 alcançáveis, 2 não.

  Inofensivas hoje, mas o mecanismo estava aberto: a próxima Page de
  **administração** registrada no `/app` cairia na matriz do usuário comum — todo
  mundo virando administrador da própria organização, sem migration, sem 403 e sem
  log. E não era hipótese: quando o buraco foi encontrado a medição era 37/36/1;
  duas semanas depois já era 38/36/2, porque a feature de convite registrou
  `ConvitesRecebidos` como Page naquele painel. A "próxima Page" chegou, e
  continuou inofensiva por sorte.

  A matriz de nenhum papel mudou — verificado por dump antes/depois. O que mudou
  é o alcance da subtração.

- **`kit:update` não entregava `.ai/rules` nem as wikis.** Projeto novo recebia
  os dois; projeto que atualizava, nenhum dos dois. Para um kit cujo diferencial
  anunciado é "a documentação que o agente de IA lê antes de codar", isso
  significava entregar o **código** de uma feature e não a armadilha que ela
  documenta. `wikis/specs/` continua fora de propósito: é o histórico de
  planejamento do kit, e não do projeto de quem instalou.

- **O form de convite do `/app` recusava quem já tem conta.** Regra própria, além
  da do `/admin`, com comentário que já não valia. Efeito: a feature nascia
  desligada exatamente para a persona que a motivou. Achado por um caso de teste,
  não por leitura.

- **`save()` não gravava a limpeza do token de lembrete.** `save()` escreve só o
  que está sujo: numa instância carregada antes de um lembrete, o `forceFill` que
  zera `token_lembrete` igualava o valor ao `original` e **não entrava no UPDATE**
  — o link de lembrete sobrevivia a um reenvio que promete matá-lo, sem erro.
  Corrigido com `refresh()` na primeira linha de `enviar()`.

### Notas

- **Três decisões vindas dos defeitos dos pacotes analisados**, todas em
  [convenções](wikis/convencoes.md#armadilhas-já-resolvidas):

  - **Asserção de identidade vive no model, não na query da tela.** O
    `filament-teams` tem `TeamInvitation::accept(Authenticatable $user)` que faz
    `attach()` + `delete()` sem comparar e-mail nenhum — a única barreira é o
    `where('email', …)` da página. Enquanto a página for o único chamador funciona;
    o primeiro job, comando ou rota de API passa por cima sem nada acusar.
  - **Consumo por `update` condicional.** O `invite-only` faz check-then-act sem
    transação nem lock, então clique duplo dispara dois eventos de aceite e duplica
    o grant de papel. Onde não existe `unique` que salve, o
    `UPDATE … WHERE aceito_em IS NULL` é o que garante uso único.
  - **Não reprovar o formulário inteiro por causa de um item do lote.** O
    `inviteMany()` do `invite-only` só captura `InvalidArgumentException`, e o
    `unique` do schema dele derruba o lote inteiro num endereço com convite
    recusado.

- **O agrupamento do `orWhere` em `Convite::valido()`** é a armadilha mais cara
  desta versão, e o procedimento de "ver o teste falhar antes de implementar"
  mostrou que ela é pior do que parecia: `AND` liga mais forte que `OR`, então sem
  o closure o **token original** também perde os três filtros de estado — um
  convite já aceito volta a ser aceitável pelo link antigo, sem erro e sem log.

- **`config/` continua fora do `kit:update`.** As chaves novas
  (`kit.convites.limite_do_lote`, `kit.convites.lembretes_dias`) não chegam a
  projeto instalado; os defaults do código cobrem a ausência.

## [0.11.0] - 2026-08-13

Três features que se completam: o painel passa a ser dado do papel, o cadastro
de quem vem de fora passa por convite, e a organização ganha um administrador
próprio dentro do `/app`. Cada uma tem a wiki dela em `wikis/specs/main/`.

### Alterado — quebra deliberada

- **`/app` deixou de ser aberto a qualquer usuário autenticado.** Acesso a painel
  agora vem do papel, pela coluna nova `roles.painel`, e `User::canAccessPanel()`
  lê essa coluna no lugar da lista de nomes que estava escrita dentro do model.
  Usuário sem papel autentica e leva 403 nos três painéis — dar acesso virou um
  ato explícito.

  **Nulo não é coringa**: papel sem painel não abre painel algum. Quem entra em
  todos é o `master_global`, pelo `Gate::before`, como sempre foi.

  Painel **sem** tenancy (`/admin`, `/infra`) exige o papel atribuído no contexto
  global; painel **com** tenancy (`/app`) aceita o papel em qualquer organização,
  e quem barra a organização errada continua sendo `canAccessTenant()`, com 404 e
  não 403. É a propriedade que impede alguém promovido a `admin` dentro de uma
  organização de administrar a instalação inteira.

  **Ao atualizar**, rode os dois seeders e revise seus usuários:

  ```bash
  php artisan migrate
  php artisan db:seed --class=Database\\Seeders\\ShieldPermissionsSeeder
  php artisan db:seed --class=Database\\Seeders\\PapeisSeeder
  ```

- **`User::temPapelGlobal()` foi removido.** Quem o chamava troca por
  `temPapelDoPainel()` ou `isMasterGlobal()`. O método trocava o
  `PermissionRegistrar` do container e descarregava a relação duas vezes para
  responder uma pergunta de leitura; a relação nova `papeisEmQualquerContexto()`
  responde com um `exists()`.

- **`panel_user` deixou de receber a matriz inteira do painel `app`.** Com as
  telas de administração da organização registradas nesse painel, dar tudo ao
  perfil básico promoveria todo usuário comum a administrador — sem migration e
  sem erro nenhum. A subtração é por FQCN de Resource.

### Adicionado

- **Convite por e-mail.** `/admin` → **Convites**: e-mail, papel e (com tenancy) a
  organização. O link leva a `/app/register?token=…`, que é a página de registro
  nativa do Filament com uma guarda no `mount()` — sem token válido ela recusa,
  então o registro nunca vira cadastro aberto. Quem clica escolhe **só nome e
  senha**; o resto vem do convite, imposto pelo servidor.

  O token é a credencial: `Str::random(64)` gravado como `hash('sha256', …)`,
  válido **uma vez** (`aceito_em`) e por um **prazo**
  (`kit.convites.validade_em_dias`, 7 dias). Em claro ele existe no e-mail e em
  lugar nenhum mais — nunca é logado nem entra na trilha de auditoria. Token
  inexistente, expirado e já aceito dão a **mesma** resposta: distinguir
  confirmaria que o convite existiu.

  No aceite o papel é atribuído no contexto certo — global se o papel for de
  `/admin` ou `/infra`, a organização do convite se for de `/app`.

  O e-mail sai por Notification enfileirável: **sem worker no ar o convite não
  chega** (`QUEUE_CONNECTION=database`).

- **Administrador da organização** (`admin_organizacao`, só com a tenancy ligada).
  Ele administra a **própria** organização dentro do `/app` — cria usuários,
  convida por e-mail, vê só quem pertence à organização corrente — e **não entra
  no `/admin`**. Seis barreiras contra escalada de privilégio, cada uma com teste
  próprio: papéis oferecidos e gravados restritos ao painel `app`, atribuição
  sempre no contexto da organização, sem criar ou editar papéis, sem alcançar
  usuário de fora (nem trocando o id na URL), sem promover ninguém a
  `admin`/`infra`/`master_global`, e convite nascendo com a organização dele
  carimbada à força.

- **A tela de papéis agrupa as permissões por painel.** O `RoleResource` do Shield
  foi publicado no projeto (`app/Filament/Admin/Resources/Roles/`) porque o pacote
  não oferece hook para isso, e ganhou o campo **Painel**. As edições em relação
  ao vendor são mínimas de propósito — duas Pages e um método —, para o diff de um
  upgrade continuar legível.

- **`App\Support\Paineis`**: o mapa painel × Resource × permission, colhido na
  mesma fonte que o `shield:generate` usa. É ele que faz o `PapeisSeeder` recortar
  a matriz por painel em vez de adivinhar por substring — o casamento antigo
  (`str_contains($p, 'User')`) colocaria um `UserPreferenceResource` futuro no
  papel `admin` sem ninguém decidir.

- **`App\Models\Role`**, para a coluna `painel` ter tipo. `config/permission.php`
  passa a apontar `models.role` para ele.

- Regras novas em `.ai/rules/filament.md`, que é o que os cinco agentes de IA leem
  antes de escrever código: Resource ou RelationManager novo exige gerar as
  permissões; papel novo precisa declarar o painel; Resource de model sem relação
  de posse com o tenant precisa de `$isScopedToTenant = false` e de um
  `getEloquentQuery()` que falhe fechado.

### Corrigido

- **As permissões de `/app` e `/infra` nunca existiram no banco.** O
  `ShieldPermissionsSeeder` rodava `shield:generate --all --panel=admin` e mais
  nada, e o comando só enxerga o painel corrente. Agora ele varre os três: 79
  permissions viraram 186, e sete policies novas apareceram. Telas que estavam sem
  policy — logo, abertas — passam a exigir permissão.

- **A suíte de testes do kit nunca teve uma permission no banco.** O `$this->seed()`
  do Laravel passa por `PendingCommand`, que liga um mock de `OutputStyle` no
  container; comando chamado de dentro do seeder resolve esse mock e é engolido.
  O `shield:generate` terminava com exit 0, imprimia "79 permissions generated" e
  gravava **zero** linhas. Nada acusava porque os testes autenticavam como
  `master_global`, que vence pelo `Gate::before` justamente sem precisar de
  permission. `Tests\TestCase::seed()` passa a usar `Artisan::call` — medido: 0
  contra 186.

- **Rodar a suíte deixava a árvore de trabalho suja.** O `shield:generate` reescreve
  as policies com o estilo dele, e o seeder roda em todo `beforeEach`: o
  `composer test` seguinte falhava no `lint:check` e o `kit:update` recusava a
  árvore. O seeder passa a usar `--ignore-existing-policies`, o que também o torna
  idempotente de verdade — quem editou uma policy à mão não a perde ao gerar as
  permissões de um Resource novo.

- `kit:update` passa a cobrir `app/Support`, `app/Notifications`,
  `app/Models/Role.php` e `app/Models/Convite.php`. O teste que varre a árvore
  pegou os dois primeiros sozinho.

### Notas

- **`config/` continua fora do `kit:update`, de propósito**, então
  `permission.models.role` apontando para `App\Models\Role` **não chega** a quem já
  instalou — e não precisa: sem a troca, `painel` volta a ser atributo dinâmico e
  tudo funciona igual. É por isso que o `UserResource` tipa o papel pela classe do
  spatie e não pela do kit; com o type hint concreto, um projeto atualizado teria
  `TypeError` na tela de usuários.

- Seis armadilhas novas na tabela de
  [convenções](wikis/convencoes.md#armadilhas-já-resolvidas), todas encontradas
  executando e nenhuma visível na leitura do vendor. As três que mais custaram:

  - **A facade `FilamentShield` cacheia a instância resolvida**, e o
    `forgetInstance()` do container não a alcança — é preciso
    `Facade::clearResolvedInstance()` junto. Sem isso os três painéis devolvem o
    mapa do primeiro, e os três papéis nascem com a mesma matriz. Parecia sucesso.
  - **`->when()` numa relação Eloquent entrega o `Builder`**, não a relação:
    `wherePivot()` dentro do closure não é aplicado, sem erro nenhum.
  - **O Filament injeta parâmetro de closure por NOME, não por tipo** — o parâmetro
    tem de se chamar `$record`, e o erro só aparece ao renderizar o campo.

## [0.10.0] - 2026-08-13

### Adicionado

- **A tela de bloqueio de sessão agora usa o layout do login.** O
  `marjose123/filament-lockscreen` entrega a tela como `SimplePage` do Filament,
  então ela ignorava o `caresome/filament-auth-designer`: quem bloqueava a sessão
  caía numa caixa cinza no meio da tela, sem a arte, sem a marca e sem o
  alternador de tema. Agora é a mesma barreira do login, nos três painéis.

  Quem faz isso é `App\Filament\Pages\Auth\TelaBloqueio`, colocada no lugar da
  classe do pacote por um bind em `AppServiceProvider` — a rota do pacote resolve
  `LockerScreen::class` pelo container.

- **Tradução pt-BR do lockscreen** em `lang/vendor/filament-lockscreen/pt_BR/` —
  o pacote só traz inglês, e "Lock Screen"/"Sign In" apareciam na tela.

### Corrigido

- **`GET /{painel}/screen/lock` com a sessão destravada dava 500.** O `mount()` do
  pacote chama `redirect()` **sem `return`**; num processo onde o Livewire já
  instalou o Redirector dele, esse objeto chega onde o Laravel espera um código
  HTTP e o request morre em `ErrorException: Object of class
  Livewire\...\Redirector could not be converted to int`. É a mesma falha já
  registrada para o Command Center, e aqui doía mais: a URL fica em favorito e
  histórico do usuário. A `TelaBloqueio` sai por `HttpResponseException`.

- **"Bloquear sessão" estava no fim do menu do usuário**, depois do alternador de
  tema e colado em "Sair". O item que o pacote registra nasce sem `sort`, e a view
  do menu agrupa por `getSort() < 0`. Agora vem com `sort(-1)`, logo abaixo de
  "Meu perfil" — registrado em `bootUsing()`, porque plugin boota antes dos
  callbacks de boot e quem registra por último vence.

### Notas

- Armadilha nova na tabela de [convenções](wikis/convencoes.md#armadilhas-já-resolvidas)
  e em `.ai/rules/auth.md`: **a `TelaBloqueio` redeclara `protected static string
  $layout`**, e isso não é redundância com a trait `HasAuthDesignerLayout`. A trait
  faz `static::$layout = ...`; sem storage próprio na subclasse a atribuição cai no
  estático herdado de `Filament\Pages\Page` e o layout de login passa a vestir
  **toda** página Filament do processo (a de 2FA do Breezy morre em
  `getAuthDesignerConfig does not exist`). `tests/Kit/BloqueioDeSessaoTest.php`
  cobre em par: a tela nova com `fi-auth-layout`, e o `/admin` sem ele depois dela.

## [0.9.9] - 2026-08-13

### Corrigido

- **O `kit:update` precisa de DUAS rodadas quando ele próprio muda — e não dizia
  isso.** A lista de caminhos que filtra o diff é uma constante da própria classe
  `KitUpdate`, e o PHP já carregou a versão antiga em memória. Então a rodada que
  traz um `KitUpdate.php` novo ainda filtra pela lista VELHA: arquivo coberto só
  pela lista nova não entra. Verificado na prática — a correção da tela de
  usuários da 0.9.7 só apareceu na segunda rodada.

  O aviso de "atualizou a si próprio" agora manda rodar de novo com o mesmo
  `--from`, e diz como saber que terminou ("Nada a atualizar").

### Notas

- Recuperando um projeto que ficou para trás nos buracos de 0.9.1–0.9.7:

  ```bash
  php artisan optimize:clear
  php artisan kit:update --from=v0.8.0            # traz a lista de caminhos nova
  git add -A && git commit -m "kit:update, rodada 1"
  php artisan kit:update --from=v0.8.0 --no-branch # traz o que só a lista nova cobre
  composer test:kit
  ```

## [0.9.8] - 2026-08-13

### Corrigido

- **Metade do Filament do kit nunca chegava a quem instalou.** A correção da tela
  de usuários publicada na 0.9.7 não alcançou projeto nenhum: o
  `app/Filament/Admin/Resources/Users` não estava em
  `KitUpdate::CAMINHOS_DO_KIT`. Junto com ele estavam de fora
  `app/Filament/Admin/Resources/AgentesIa`, `app/Filament/Infra/Resources/AiRuns`,
  `app/Livewire/AssistenteChatWidget.php`, `app/Models/AgenteIa.php` e as
  policies de `User`, `Role` e `AgenteIa`.

  A causa é a granularidade: a lista tinha uma linha por subpasta do Filament
  (`Admin/Widgets`, `Admin/Resources/Tenants`, `Infra/Pages`…), e o que não
  ganhou linha própria simplesmente não existia para o `kit:update`. Agora entra
  `app/Filament` inteiro, mais `app/Livewire`, `app/Policies` e
  `database/factories`.

- **O teste que devia ter pegado isso era uma lista à mão.** O
  `tests/Kit/KitUpdateTest.php` cobrava 22 arquivos escolhidos a dedo — e
  `UserResource.php` não era um deles. Ele passa a **varrer a árvore**: todo
  arquivo sob `app/`, `database/factories`, `database/migrations` e
  `database/seeders` precisa estar coberto, com uma allowlist explícita para o
  que não é do kit. A varredura roda só no repositório do kit (detectado pelo
  `.github`, que é `export-ignore`), porque em projeto instalado o model e o
  resource do usuário moram nesses mesmos diretórios.

### Notas

- **Quem já atualizou até a 0.9.7 precisa comparar a partir da 0.8.0 uma vez.**
  O `kit:update` compara duas tags: indo de 0.9.7 para 0.9.8 o diff traz apenas o
  que mudou entre elas, e os arquivos que estavam fora da lista **não voltam**.
  Para recuperar tudo o que os buracos de 0.9.1–0.9.7 engoliram:

  ```bash
  php artisan kit:update --from=v0.8.0
  ```

  É também o que traz o `app/Models/Tenant.php` com `HasName` (correção da 0.9.3),
  sem o qual `/app/{tenant}` responde 500 —
  `FilamentManager::getTenantName(): Return value must be of type string, null returned`.

- `config/` segue fora da lista de propósito: é o que cada projeto calibra, e
  sobrescrever apagaria ajuste seu. A exceção é `config/kit.php`, a marca de
  nascença.

## [0.9.7] - 2026-08-13

### Corrigido

- **Salvar papéis do usuário em `/admin/users/{id}/edit` dava 500 com a tenancy
  ligada.** `NOT NULL constraint failed: model_has_roles.team_id`, na gravação —
  abrir a tela funcionava.

  O `Select::make('roles')->relationship('roles', 'name')` salva com
  `$relationship->sync()`, que escreve na pivot apenas as colunas da chave. Com
  multi-tenancy a `model_has_roles.team_id` é NOT NULL e ninguém a preenchia: o
  `wherePivot` que o spatie põe em `roles()` filtra **leitura**, não alimenta
  escrita. Quem carimba o `team_id` do contexto corrente é o
  `assignRole()`/`syncRoles()` — a API que o kit passou a usar, via
  `->saveRelationshipsUsing()`.

  Vale também para single-tenant, onde o sintoma era silencioso: o `sync()` cru
  não invalida o cache de papéis do spatie, então uma permissão recém-tirada
  continuava valendo até o cache expirar.

- Dois testes novos, em par, porque abrir a tela não cobre gravar (o
  `GET /admin/users` seguia verde com o salvamento quebrado): um em `tests/Kit`
  para o modo single-tenant e um em `tests/Tenancy` conferindo o `team_id` da
  pivot.

### Notas

- A armadilha ficou registrada em `.ai/rules/filament.md`: campo que grava
  `roles` ou `permissions` nunca usa o sync da relação.

## [0.9.6] - 2026-08-13

### Corrigido

- **`composer test:kit` estourava `model_has_roles.team_id` em projeto com a
  tenancy ligada.** Vinte e quatro testes de `tests/Kit` morriam com
  `NOT NULL constraint failed: model_has_roles.team_id` na primeira atribuição de
  papel. A suíte `tests/Tenancy` passava inteira, o que tornava o sintoma
  confuso: o modo multi-tenant funcionava, o single-tenant não.

  O modo de tenancy vive em três chaves que precisam concordar, e elas não vêm do
  mesmo lugar. `kit.tenancy.enabled` é env (`KIT_TENANCY`); `permission.teams` e
  `filament-shield.tenant_model` são arquivos que o `kit:tenancy` reescreve em
  **disco**. O `Tests\TestCase` alinhava só a primeira — então num projeto com a
  tenancy ligada as suítes single-tenant migravam o schema COM as colunas de team
  (`permission.teams` ainda `true`) e atribuíam papel SEM contexto de team
  (`kit.tenancy.enabled` já `false`, e é essa flag que o
  `KitServiceProvider::configureTenancy()` usa para fixar o contexto global).

  Agora `usaTenancy()` decide as três, em `Tests\TestCase::createApplication()` —
  antes das migrations, com o `PermissionRegistrar` descartado para renascer
  sabendo do modo. O `Tests\TenancyTestCase` ficou só com a declaração do modo: o
  mecanismo deixou de estar duplicado nos dois arquivos.

- `tests/Kit/PaineisTest.php`: o teste `roda em modo single-tenant` passa a cobrar
  as três chaves, não só a primeira. É o que faz a dessincronia falhar dizendo o
  nome, em vez de virar um 404 ou um `NOT NULL` sem pista.

## [0.9.5] - 2026-08-13

### Corrigido

Os dois erros abaixo só apareciam em **projeto instalado** — no repositório do
kit a suíte passava inteira. É a pior categoria de bug para um starter kit: o
primeiro `composer test:kit` de quem instala falha, e nada no kit reproduz.

- **`.github` na lista de caminhos do `kit:update`.** O `.gitattributes` marca
  `/.github export-ignore`, então a pasta não vai no pacote distribuído — o CI é
  do kit, não do projeto que nasce dele. Mas ela estava em
  `KitUpdate::CAMINHOS_DO_KIT`, e daí duas consequências: o teste "só lista
  caminhos que existem de fato" falhava em toda instalação, e o `kit:update`
  (que lê o repositório git, onde a pasta existe) ofereceria o CI do kit ao
  projeto — justamente o que o `export-ignore` decidiu evitar.

  `tests/Kit/KitUpdateTest.php` passa a ler o `.gitattributes` e a cobrar que
  nenhum caminho `export-ignore` volte para a lista.

- **`GET /app` respondia 404 na suíte do kit quando a config estava cacheada.**
  Não era rota nem painel: `tests/Kit` pressupõe o modo single-tenant, e o
  `Tests\TestCase` garantia isso escrevendo `KIT_TENANCY=false` no ambiente antes
  do bootstrap. Com um `bootstrap/cache/config.php` no lugar, o `env()` nem é
  consultado — a tenancy voltava a ligar, o `->tenant()` reescrevia o painel para
  `/app/{tenant}` e o dashboard sumia. A tela de login seguia de pé, o que
  escondia a causa.

  O `Tests\TestCase` agora aponta `APP_CONFIG_CACHE` e `APP_ROUTES_CACHE` para
  arquivos inexistentes: nos testes o Laravel boota da fonte, sem apagar o cache
  do projeto. Ambos os caches congelam decisões de um ambiente e nunca deveriam
  valer numa suíte que alterna modos do kit.

  `tests/Kit/PaineisTest.php` ganhou o teste `roda em modo single-tenant`, para a
  premissa quebrar com nome em vez de virar um 404 sem pista.

## [0.9.4] - 2026-08-13

### Corrigido

- **O `kit:update` não entregava a multi-tenancy.** Ele compara duas versões do
  kit restrito a uma lista fechada de caminhos, e essa lista não foi atualizada
  quando a feature nasceu: das versões 0.9.1 a 0.9.3, um projeto já instalado só
  recebia `config/kit.php` — a marca de versão, sem nenhum dos arquivos. A
  feature existia no repositório e era invisível na prática.

  Entraram na lista: `app/Console/Commands` (que só cobria dois comandos),
  `app/Http/Middleware`, `app/Policies/TenantPolicy.php`, os resources de
  tenants e da demo, `app/Models/User.php`, `Tenant.php` e `Projeto.php`,
  `database/migrations`, `database/seeders`, `database/factories/TenantFactory.php`
  e as suítes `tests/Tenancy`, `tests/TestCase.php` e `tests/TenancyTestCase.php`.

- `tests/Kit/KitUpdateTest.php` passa a cobrar a lista: 22 arquivos da fundação
  precisam estar cobertos, e todo caminho listado precisa existir. É o que faz a
  lista envelhecer com barulho em vez de em silêncio.

### Notas

- Diretório na lista é seguro mesmo com arquivos seus dentro: a comparação é
  kit-versão-A × kit-versão-B, então um arquivo que só existe no seu projeto
  nunca entra no diff. `app/Models` continua arquivo a arquivo, onde a colisão
  de nome com um model seu é plausível.
- Quem atualizou para 0.9.1–0.9.3 e recebeu só o `config/kit.php` precisa
  comparar a partir da última versão anterior à tenancy — que é a **0.8.0**,
  não uma 0.9.0 (essa nunca existiu como tag; a série foi de 0.8.0 para 0.9.1):

  ```bash
  php artisan kit:update --from=v0.8.0
  ```

## [0.9.3] - 2026-08-13

### Corrigido

- **Trocar para o painel `/app` estourava `TypeError` com tenancy ligada.**
  O `FilamentManager::getTenantName()` é tipado como `string` e cai em
  `$tenant->getAttributeValue('name')` quando o model não implementa `HasName`
  — a coluna do kit é `nome`, então o retorno era `null`. `App\Models\Tenant`
  passa a implementar `Filament\Models\Contracts\HasName`.

  A lição fica documentada em `wikis/arquitetura.md`: toda coluna em pt-BR que
  o Filament espera em inglês precisa de um contrato, e esse tipo de erro só
  aparece ao renderizar a página — nenhum teste de model chega lá.

- Testes de **requisição HTTP real** ao painel de negócio (`/app/{slug}`), que
  é o que teria pego o erro acima. Um deles trava também a propriedade de
  segurança de responder **404, e não 403**, num tenant não vinculado: 403
  confirmaria a existência do tenant e permitiria enumerar os clientes da
  instalação por varredura de slug.

### Alterado

- Os testes passam a ligar a tenancy pelo **ambiente**, antes do bootstrap: o
  `AppPanelProvider` lê a flag durante o boot para registrar as rotas com
  `/{tenant}`, e config ajustada depois chegava tarde (as rotas nasciam sem o
  segmento e o painel dava 404).
- `wikis/receitas.md` corrigido: acesso a tenant não vinculado é 404, não 403.

## [0.9.2] - 2026-08-13

### Corrigido

- **`kit:tenancy` criava as tabelas de permissão sem a coluna de tenant.** O
  comando rodava `config:clear` e em seguida `migrate:fresh` no MESMO processo —
  mas `config:clear` apaga o arquivo de cache, não recarrega a config já em
  memória. A migration do spatie lia `permission.teams` ainda como `false` e
  criava as tabelas sem `team_id`. O banco ficava de pé, o comando terminava com
  sucesso, e o erro só aparecia no primeiro login:
  `SQLSTATE[HY000]: no such column: model_has_roles.team_id`.

  Agora o comando alinha a config em memória e descarta o singleton
  `PermissionRegistrar` antes de migrar, e **confere o schema ao final** — se a
  coluna não existir, falha alto em vez de entregar uma instalação quebrada.

- Dois testes novos travam a invariante: a existência das colunas de team e a
  atribuição de papel no contexto global (o caminho dos seeders).

### Alterado

- `App\Policies\TenantPolicy` passa a ser a saída canônica do `shield:generate`
  (assinaturas com o model, conjunto completo de métodos).

## [0.9.1] - 2026-08-13

### Adicionado

- **Multi-tenancy opt-in.** `php artisan kit:tenancy` liga o modo multi-tenant;
  sem ele o kit continua single-tenant e nada muda. Com o modo ligado, o painel
  `/app` vira `/app/{tenant}` e o usuário só enxerga os tenants aos quais está
  vinculado; o `/admin` ganha o cadastro de tenants e o vínculo de usuários; o
  `/infra` segue global, porque saúde, filas e logs são da instalação e não de
  um cliente.
- **Vocabulário separado do rótulo.** O código usa o padrão da API do Filament
  (`Tenant`, `tenants`, `tenant_id`, `getTenants()`), e o que o usuário lê vem de
  `config('kit.tenancy')` — `label`, `label_plural` e `slug`, que nascem como
  "Organização"/"organizacoes" e cada projeto troca pelo termo do seu negócio
  sem tocar em código.
- **`App\Traits\BelongsToTenant`** para as models de negócio: relação `tenant()`,
  escopo global e preenchimento automático de `tenant_id`. O escopo existe porque
  o Filament só recorta o que passa por um Resource — job, comando, listener e
  API ficariam de fora.
- **Papéis por tenant** (`permission.teams`): definição do papel global
  (`roles.team_id` nulo) e atribuição por tenant. Como `model_has_roles.team_id`
  é NOT NULL e o spatie não tem atribuição global, o kit usa o sentinela
  `Tenant::CONTEXTO_GLOBAL` para os papéis que governam `/admin` e `/infra`.
- **Cenário de demonstração** com `--demo`: dois tenants, três usuários e um
  resource no `/app` para ver o isolamento funcionando. Descartável — o comando
  imprime quais arquivos apagar.
- Ledger de IA e budget passam a gravar o tenant real (`ai_runs.tenant_id`).
- Suíte `tests/Tenancy/` (14 casos), no mesmo grupo `kit`.

### Alterado

- `composer test:kit` passa a rodar `--group=kit`, cobrindo as duas suítes.

## [0.8.0] - 2026-08-13

### Adicionado

- **`wikis/` — a documentação que o agente de IA lê antes de codar.** Sete
  documentos com o que o código não conta sozinho: arquitetura (os três
  painéis, a "cola", o ciclo do request, os três níveis de autorização),
  convenções e armadilhas já resolvidas, a camada de IA (agente como dado,
  fail-closed, ledger), receitas passo a passo, o mapa de agentes e skills e a
  lista de "quem é dono de qual tela" — para não reimplementar vendor.
  `wikis/README.md` é o ponto de entrada; `wikis/specs/{branch}/{feature}/`
  continua sendo onde a skill `feature-wiki` grava cada feature.
- **Skills e plugins de IA no kit.** `feature-wiki` (de
  `gsferro/laravel-ai-skills`) instalada via Boost e sincronizada para os cinco
  agentes; no Claude Code, os plugins Ponytail e Caveman habilitados em
  `.claude/settings.json`. As três cobrem camadas distintas — comunicação,
  planejamento e execução — e a fronteira entre elas está documentada.
- **README em inglês** (`README.en.md`), com troca de idioma no topo dos dois
  arquivos, e banner próprio (`art/banner-en.png`).
- **Seção "Pacotes instalados"** nos dois READMEs: 46 dependências, 11 de
  desenvolvimento e 6 de front-end, agrupadas por função no kit, com nota sobre
  os motores que rodam por baixo dos plugins.
- **Thumbnail 16:9** (`art/thumbnail.png`) para a página do plugin no
  filamentphp.com.
- Badge do Filament nos READMEs.

### Alterado

- Imagens dos READMEs passam a apontar para `raw.githubusercontent.com`, para
  renderizarem também no Packagist e em qualquer lugar fora do GitHub.

## [0.7.2] - 2026-08-12

### Adicionado

- `kit:update` recria pastas de teste declaradas no `phpunit.xml` que não
  existem em disco, com um `.gitkeep`. É a outra metade do bug da 0.7.1: quem
  já tinha o projeto criado não recebia a correção, porque `tests/Feature` é
  pasta do usuário e não entra nos caminhos do kit — e sem a pasta o PHPUnit
  aborta com exit 2.

## [0.7.1] - 2026-08-12

### Corrigido

- **`composer test` abortava com `Test directory "tests/Feature" not found`**
  em projeto novo. Ao mover os testes do kit para `tests/Kit`, a pasta
  `tests/Feature` ficou vazia — e git não versiona diretório vazio, então ela
  não existia no pacote distribuído e o PHPUnit parava com exit 2.
  Agora o kit entrega um `tests/Feature/ExemploTest.php` que serve de ponto de
  partida e mantém a pasta no repositório.

## [0.7.0] - 2026-08-12

### Corrigido

- **A busca ⌘K não aparecia na topbar.** O gatilho estava no render hook
  `USER_MENU_BEFORE`, que no Filament 5.7 renderiza DENTRO do dropdown do
  usuário. Agora usa `GLOBAL_SEARCH_BEFORE`, emitido pela topbar
  incondicionalmente — é o lugar exato do campo nativo.
- O gatilho passa a reusar a **marcação nativa** do campo de busca do Filament
  (lupa, sufixo com o atalho, mesmo visual), em vez de um botão próprio. O
  overlay abre em `setTimeout`: sem isso o próprio clique é visto como
  "clique fora" e fecha o painel recém-aberto.
- Ações "Criar X" na busca: a categoria de ações do pacote não estava
  registrada, então nada aparecia.

### Adicionado

- `App\Filament\Spotlight\AcoesDeCriacao`: sugestões "Criar X" com três
  guards (`canAccess`, `canCreate`, `shouldRegisterNavigation`). O discovery do
  pacote fica desligado — ele não checa permissão e derruba a tela de login
  com 500 ao resolver URLs sem contexto.
- Traduções pt-BR da busca (`lang/vendor/filament-search-spotlight`) e do painel
  de colunas fixas: o placeholder da topbar era a primeira coisa em inglês num
  painel inteiro em português.
- README reescrito: seção da busca ⌘K, badges de contagem (incluindo por que
  resources de terceiros não podem ter), armadilhas já resolvidas e capturas
  atualizadas.

## [0.6.1] - 2026-08-12

### Alterado

- Mensagem de "nada a atualizar" reescrita: diz que o projeto está na versão
  mais atual quando é o caso, e distingue o cenário de comparar com uma versão
  antiga (onde dizer "atualizado" seria mentira).

## [0.6.0] - 2026-08-12

### Corrigido

- **`composer test` falhava num projeto recém-instalado**: o `shield:generate`
  escreve as policies com o estilo dele, e o Pint reprovava três arquivos logo
  na primeira execução. O `kit:install` passa a formatar o código gerado.
- **`phpunit.xml` entra nos caminhos do `kit:update`** — sem ele a testsuite
  `Kit` nunca chegava a quem já tinha o projeto criado, e `composer test:kit`
  não existia.

### Adicionado

- `kit:update` relata o que mudou no `composer.json` do kit (pacotes e scripts)
  sem nunca aplicá-lo: sobrescrever esse arquivo apagaria as dependências do
  projeto. Foi assim que o script `test:kit` deixou de chegar em quem atualizou.

## [0.5.1] - 2026-08-12

### Alterado

- `kit:update` avisa quando atualiza a si próprio: o PHP já carregou a classe
  antiga em memória, então o comportamento (e as mensagens) da versão nova só
  valem na execução seguinte. Sem o aviso, parecia que a melhoria não tinha
  funcionado.
- README documenta que `config/kit.php` sempre aparece como modificado e que
  aplicá-lo substitui o arquivo inteiro, incluindo suas customizações.

## [0.5.0] - 2026-08-12

### Adicionado

- Testes do kit isolados em `tests/Kit` (testsuite `Kit` e grupo `kit`), com o
  atalho `composer test:kit`. Depois de um `kit:update` dá para verificar só a
  fundação, sem esperar a suíte do seu negócio. `tests/Feature` e `tests/Unit`
  ficam livres para os seus testes.

### Alterado

- `kit:update` grava a versão aplicada em `config/kit.php` automaticamente —
  antes ele pedia a edição manual, e esquecer isso estragava o diff da próxima
  atualização. Só a linha da versão é reescrita.
- `kit:update` passa a trazer também `tests/Kit`, para que a suíte da fundação
  acompanhe a atualização.

## [0.4.0] - 2026-08-12

### Adicionado

- `kit:update` aplica em lote: opções `--only-new` (só arquivos que ainda não
  existem no projeto — não sobrescreve nada) e `--all` (tudo, com uma
  confirmação para o conjunto). Durante a revisão arquivo a arquivo também é
  possível mudar para lote a qualquer momento, sem recomeçar.
- Com `--only-new`/`--all` o comando passa a ser scriptável: a aprovação veio
  na linha de comando, então ele roda sem terminal interativo.

## [0.3.1] - 2026-08-12

### Corrigido

- `kit:update --dry-run` não exige mais árvore de trabalho limpa: um relatório
  não altera nada, e cobrar isso atrapalhava justamente quem quer olhar antes
  de mexer. A exigência continua valendo para aplicar mudanças.
- O erro de árvore suja agora **lista os arquivos** que impedem a execução e
  lembra da opção `--dry-run` — antes só dizia que havia pendências.

## [0.3.0] - 2026-08-12

### Adicionado

- Comando `php artisan kit:update`: compara o projeto com uma versão nova do kit,
  mostra o que mudou e aplica só o que for aprovado, arquivo a arquivo. Vincula o
  repositório do kit de forma temporária e somente-leitura (tags em namespace
  `kit-*`), sugere um branch de trabalho e desfaz o vínculo ao sair.
- `config/kit.php` passa a registrar a versão do kit que originou o projeto,
  usada pelo `kit:update` como ponto de partida da comparação.
- README: seção completa sobre atualizar um projeto existente (comando e passo
  a passo manual).

## [0.2.0] - 2026-08-12

### Adicionado

- Documentação visual: banner, GIF da instalação e capturas dos três painéis em `art/`.
- Badges de downloads e de status dos testes no README.
- Dashboards preenchidos nos painéis admin e infra (StatPlus + widgets de funil,
  meta, breakdown, timeline e composição) sobre os dados que os painéis já têm.
- Badge de contagem animado no menu (`App\Filament\Concerns\BadgeContagemNavegacao`).
- Colunas redimensionáveis, reordenáveis e fixáveis como padrão de toda tabela,
  documentadas em "Configuração global do Filament".

### Corrigido

- **Spotlight (⌘K) não abria em nenhum painel**: faltavam as categorias e um
  gatilho visível — a busca nativa do Filament tinha sido desligada sem
  substituto. As categorias do kit checam `canAccess()`, então a busca não
  oferece tela que resultaria em 403.
- **Conflito de JavaScript entre pacotes**: os bundles do Pulse (dotswan) e do
  resized-column declaram constantes no escopo global; o segundo a carregar
  morria inteiro com `SyntaxError: Identifier '$e' has already been declared`,
  derrubando os gráficos do Pulse sem nenhum erro visível na tela. Agora os dois
  são carregados como ES module.
- **Grupos de navegação do painel infra** misturavam inglês e português
  (`Settings`, `System`, `Logins`): agora são Observabilidade, IA, Trilhas e Sistema.
- **Página 403 do Sentinel**: traduzida para pt-BR, expõe o diagnóstico de
  permissão apenas fora de produção, identifica a conta pelo e-mail em vez do id
  e o botão "Voltar" retorna à página anterior em vez da raiz.
- Demais páginas de erro (404, 419, 500, 503) traduzidas.
- Ações de filtro NÃO são mais customizadas globalmente: num `configureUsing()`
  elas atingiam tabelas sem filtro e derrubavam 8 telas do painel infra com
  `LogicException: Action ... must have a unique name`.
- Painel de colunas fixas (resized-column) traduzido para pt-BR.

## [0.1.0] - 2026-08-12

### Adicionado

- Skeleton Laravel 13 + Filament 5 instalável via `composer create-project gsferro/starter-kit-easy`.
- Comando `kit:install`: cria `.env`, gera `APP_KEY`, prepara o banco SQLite, migra,
  semeia papéis/permissões/usuário, publica assets e faz o build do front-end.
  Roda sozinho no `post-create-project-cmd` e é idempotente.
- Três painéis: `/app` (negócio, vazio de propósito), `/admin` (usuários, Shield,
  agentes de IA, onboarding) e `/infra` (health, backups, filas, logs, auditoria,
  caches, Command Center, Pulse, custos de IA).
- Fundação: traits `TemUuid` e `AuditsFillables`, `Gate::before` para `master_global`,
  `CarbonImmutable`, `prohibitDestructiveCommands` em produção, `Password::defaults()`,
  configuração global do Filament (tabelas, toggles, Panel Switch).
- Núcleo de IA com `laravel/ai`: catálogo de agentes no banco, guardrails encadeados,
  ledger `ai_runs` e chat com streaming. Inferência local por padrão (llama.cpp).
- Docker com profiles opt-in: `pgsql`+`redis` na base, `ai`, `mail`, `app`, `realtime`.
- Qualidade: Pest, Pint (setas alinhadas), PHPStan level 6, CI com job que prova o
  `create-project` ponta a ponta.
- Traduções pt-BR (laravel-lang) e UI dos painéis em português.
