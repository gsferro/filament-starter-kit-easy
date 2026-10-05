# Plano de Ação — feat/cabecalho-do-painel: cabeçalho dos painéis personalizável pelas Configurações da aplicação

> Requisito: `00-requisito.md`

## Natureza da Wiki

- **Tipo**: nova
- **Wiki ancestral**: nenhuma — a feature nasce sobre a identidade visual existente (`wikis/specs/feat/logo-dark-mode/`, que entregou a logo por tema, é vizinha, não ancestral)
- **Motivo**: o kit só mostra a logo (ou o nome em texto) no topo dos painéis; o projeto-3 precisou compor "projeto | painel | logo" e o bloco do usuário à mão, em código
- **Toca infra compartilhada?**: sim → a marca (`brandLogo`/`darkModeBrandLogo`) dos três `PanelProvider`, a classe de Settings do kit e a aba Identidade da tela de Configurações da aplicação, `resources/css/filament/kit.css` (carregado nos três painéis). Regressão **obrigatória** contra `IdentidadeDoKitTest` (CT-35: logo e nome nos três painéis), `LogoDarkModeTest` (CT-17/18: `fi-logo-dark` no login), `CabecalhoDoMenuDoUsuarioTest` + `CabecalhoDoMenuDoUsuarioTenancyTest` (o menu do usuário e o hook `USER_MENU_BEFORE`), `ConfiguracoesDoKitTest`/`ConfiguracoesDoKitTelaTest` (mapa, migrations, trilha) e `KitInfoTest` (contagem de propriedades)

## Cobertura do Requisito

| RQ | Cláusula | Passo(s) que atende(m) | Observação |
|----|----------|------------------------|------------|
| RQ-01 | composição "projeto \| painel \| logo" no lugar da marca | 3, 4, 5, 6 | a composição substitui o `brandLogo` quando qualquer segmento está ligado |
| RQ-02 | organização no segmento do painel com tenant ativo | 3 | `Filament::getTenant()` só é preenchido no `/app` com organização na rota |
| RQ-03 | nome + permissão do usuário à direita | 3, 4, 5, 6 | hook `USER_MENU_BEFORE`, à esquerda do avatar |
| RQ-04 | escolher e-mail ou perfil abaixo do nome | 1, 2, 3, 7 | select com duas opções, `perfil` de nascença (P-05) |
| RQ-05 | todas as opções nas Configurações da aplicação | 1, 2, 7 | cinco propriedades novas, três lugares cada (classe, mapa, migration) |
| RQ-06 | default preserva o visual de hoje | 1, 2, 3 | toda propriedade nova nasce desligada; `marca()` devolve exatamente o que o provider devolvia |
| RQ-07 | wiki de feature e branch a partir da `main` | — | ⚠️ processo — esta wiki e a branch `feat/cabecalho-do-painel`, criada da `main` depois do merge da `feat/logo-dark-mode` (ela toca os mesmos arquivos) |
| RQ-08 | testes que garantem o funcionamento | 9 | `04-casos-de-teste.md` pela `feature-test-design`; CT-B se a costura `browser` for confirmada |
| P-01 | segmento do painel omitido quando repetiria o projeto | 3 | — |
| P-02 | três interruptores independentes, desligados | 1, 2, 3, 7 | — |
| P-03 | permissão = papel do painel, rótulo legível, sem papel sem linha | 3, 4 | mesma fonte do badge do menu do usuário |
| P-04 | bloco do usuário oculto abaixo de 768 px | 5 | regra de mídia no `kit.css` |
| P-05 | detalhe nasce em `perfil` | 1, 2 | default da config e da migration |
| P-06 | composição vale na barra lateral e no topbar | 3, 5 | é o `brandLogo`: o Filament a desenha nos dois; sempre numa linha — na barra lateral o CSS encolhe e corta com reticências *(alterado em 2026-10-05: CR-04)* |
| P-07 | logo da composição é a da aba Identidade (clara e escura) | 3, 4 | `IdentidadeDoKit::logo()`/`logoEscura()` |
| P-08 | nome do projeto é o nome da aplicação | 3 | `config('app.name')`, que a tela de configurações já governa |
| P-09 | bloco do usuário complementa o menu nativo | 6 | `USER_MENU_BEFORE` é renderizado fora do dropdown |
| P-10 | seção própria na aba Identidade; par no `.env` | 2, 7 | — |

## Objetivo

Dar ao projeto que usa o kit a opção de compor a marca do topo dos painéis como "nome do projeto |
nome do painel (ou da organização, quando há uma aberta) | logo da marca", e de mostrar, à esquerda
do avatar, o nome de quem está autenticado com o e-mail ou o perfil embaixo — tudo ligado e
desligado pela aba **Identidade** das Configurações da aplicação, sem código e sem deploy.

Nada disso muda para quem não ligar nada: as cinco opções nascem desligadas (e o detalhe em
`perfil`), e com elas desligadas os três painéis renderizam byte a byte o que renderizam hoje.

## Contexto

O projeto-3, derivado do kit, escreveu uma classe de apoio (`CabecalhoProjTEC`) que devolve um
`HtmlString` para o `brandLogo()`/`darkModeBrandLogo()` de cada painel e registra um
`USER_MENU_BEFORE` com nome e papel. Funciona, mas é código do projeto: módulo e entidade são
literais, o papel vem de `getRoleNames()->first()` (que a rule `.ai/rules/models.md` proíbe com
teams ligado) e nada é configurável. O pedido é trazer o desenho para o kit como **opção**, com a
resolução certa de painel, organização e papel que o kit já tem (`Paineis::rotulo()`,
`Filament::getTenant()`, `User::papelDoPainel()` + `Papeis::rotulo()`).

## Análise dos Arquivos Existentes

<!-- Raia fato da entrevista: o que o agente descobriu no código, sem perguntar ao usuário. -->

### `app/Providers/Filament/{Admin,App,Infra}PanelProvider.php`
- Os três declaram `->brandLogo(fn (): ?string => IdentidadeDoKit::logo())` e `->darkModeBrandLogo(fn (): ?string => IdentidadeDoKit::logoEscura())` por Closure (`app/Providers/Filament/AdminPanelProvider.php:brandLogo:91`, `:darkModeBrandLogo:89`; `app/Providers/Filament/AppPanelProvider.php:brandLogo:100`; `app/Providers/Filament/InfraPanelProvider.php:brandLogo:112`), avaliadas no render — depois de `aplicarNaConfig()` (é o que permite a Settings governar).
- `brandName` é `config('app.name').' • Admin'` / `' • Infra'` e só `config('app.name')` no `/app` (`app/Providers/Filament/AdminPanelProvider.php:brandName:74`, `app/Providers/Filament/AppPanelProvider.php:brandName:83`, `app/Providers/Filament/InfraPanelProvider.php:brandName:95`). Com logo enviada, o Filament não mostra o `brandName`.
- Nenhum deles usa `USER_MENU_BEFORE`; os três usam `USER_MENU_PROFILE_BEFORE` (dentro do dropdown) e `GLOBAL_SEARCH_BEFORE` (`app/Providers/Filament/AdminPanelProvider.php:USER_MENU_PROFILE_BEFORE:411`). O docblock ao lado registra que `USER_MENU_BEFORE` é emitido **fora** do dropdown (`vendor/filament/filament/resources/views/components/user-menu.blade.php:USER_MENU_BEFORE:43`).
- O Filament aceita `string | Htmlable | null` nos dois (`vendor/filament/filament/src/Panel/Concerns/HasBrandLogo.php:getBrandLogo():37`, `:getDarkModeBrandLogo():47`). Com `Htmlable`, `components/logo.blade.php` envolve o HTML numa `<div class="fi-logo">` com `height: {brandLogoHeight}` e **não** acrescenta `<img>`; com `darkModeBrandLogo` preenchido, renderiza o par com `fi-logo-light`/`fi-logo-dark` (`vendor/filament/filament/resources/views/components/logo.blade.php:$hasDarkModeBrandLogo:9`).
- A marca é desenhada em dois lugares: topo da barra lateral (`vendor/filament/filament/resources/views/livewire/sidebar.blade.php:'fi-sidebar-header-logo-ctn':89`, com `x-show="$store.sidebar.isOpen"` quando ela é recolhível) e no topbar (`vendor/filament/filament/resources/views/livewire/topbar.blade.php:TOPBAR_LOGO_BEFORE:118`). Os três painéis são `sidebarCollapsibleOnDesktop()`.

### `app/Support/IdentidadeDoKit.php`
- `logo()` (`app/Support/IdentidadeDoKit.php:logo():50`), `logoEscura()` (`:logoEscura():64`, `null` na marca unificada) e `unificaLogo()` (`:unificaLogo():79`) resolvem a URL pela config, com `asset('storage/'.$caminho)` (rule `app.md`). Continuam sendo a fonte da logo; a feature só decide **onde** e **com o quê** ela aparece.

### `app/Support/Paineis.php` e `app/Support/Papeis.php`
- `Paineis::rotulo(Panel)` é a fonte única do nome do painel (`app/Support/Paineis.php:rotulo():298`): `Administração`, `Infraestrutura` e, para o `app`, `config('app.name')` — por isso a P-01. `Paineis::correnteOuPadrao()` (`:correnteOuPadrao():252`) é o jeito exigido pela rule `app.md` de obter o painel.
- `Papeis::rotulo(?string)` (`app/Support/Papeis.php:rotulo():49`) devolve `—` para nulo — o bloco do usuário testa o nulo **antes** de chamar, para não imprimir um traço.

### `app/Models/User.php` e `app/Models/Tenant.php`
- `User::papelDoPainel(string $painel, ?int $contexto)` (`app/Models/User.php:papelDoPainel():444`): `master_global` primeiro, depois `papeisEmQualquerContexto()` filtrado por painel e contexto. É exibição, nunca autorização (rule `models.md`).
- `Tenant::getFilamentName()` devolve `nome` (`app/Models/Tenant.php:getFilamentName():108`) — é o que entra no segmento do painel com tenant ativo.

### `app/Settings/ConfiguracoesDoKit.php` e `database/settings/`
- Três lugares por propriedade (rule `settings.md`): a propriedade, a linha em `mapaDeConfiguracao()` (`app/Settings/ConfiguracoesDoKit.php:mapaDeConfiguracao:396`) e o `add()` numa migration **nova**. `ConfiguracoesDoKitTest` reprova propriedade sem migration ("semeia todas as propriedades que a classe de settings declara") e tem dataset com uma linha por propriedade ("leva cada propriedade gravada para a chave de configuracao dela").
- Modelo de migration: `database/settings/2026_10_03_100000_add_logo_dark_e_unifica_marca_to_kit_settings.php` — `add()` com o default lido de `config()`, `delete()` no `down()`.
- `KitInfoTest` conta as propriedades do mapa: **57** hoje (`tests/Kit/KitInfoTest.php:'toHaveCount':186`) → **62** com as cinco novas.

### `app/Filament/Admin/Pages/ConfiguracoesDoKit.php`
- A aba Identidade (`app/Filament/Admin/Pages/ConfiguracoesDoKit.php:abaIdentidade():273`) termina com os uploads `logo`, `logo_dark`, `favicon` e `arte_do_login` (`:arquivo():998`). A aba Login já usa `Section::make()` para agrupar (`:'Página única de login':590`) — mesmo padrão para a seção nova.
- `Toggle::make('unifica_logo_marca')->live()->inline(false)` (`:'unifica_logo_marca':342`) é o modelo do toggle que governa a visibilidade de outro campo (`->visible(fn (Get $get) => …)`).

### `config/kit.php` e `.env.example`
- Booleano com default vem de `BooleanoDoEnv::comPadrao(env('KIT_…'), default)` (`config/kit.php:'exibir_versao':356`, `:'unifica_logo_marca':144`); string com lista fechada vem de um enum com `coagir()` (`config/kit.php:'densidade_do_layout':323`, `App\Support\DensidadeDoLayout`). O `.env.example` documenta cada chave com o default (`.env.example:KIT_EXIBIR_VERSAO:128`).

### `resources/css/filament/kit.css` e `resources/views/filament/`
- Não há tema Vite: utilitária Tailwind emitida por blade do kit **não existe** na página (rule `css-filament.md`). Toda classe nova vai em `kit.css`, escopada e com par `.dark:root`, e depois `php artisan filament:assets` republica em `public/css/kit/` (versionado — `app/Console/Commands/KitUpdate.php:'resources/css/filament':224`).
- `resources/views/filament/user-menu-header.blade.php` e `perfil-indicator.blade.php` já resolvem nome, e-mail e badge do papel — o bloco novo usa a mesma resolução, em blade própria, porque o markup é outro (duas linhas, sem avatar).
- `KitUpdate::CAMINHOS_DO_KIT` cobre os diretórios inteiros que a feature toca (`app/Support`, `app/Settings`, `database/settings`, `resources/css/filament`, `resources/views/filament`, `tests/Kit`, `tests/Tenancy`, `tests/Browser`) — arquivo novo dentro deles viaja pelo `kit:update` sem editar a lista (`app/Console/Commands/KitUpdate.php:'resources/views/filament':227`).

### Testes vizinhos
- `tests/Kit/CabecalhoDoMenuDoUsuarioTest.php` — GET nos três painéis com `usuarioCom('master_global')` e `assertSee` do markup; o CT-35 afirma a posição relativa dos hooks na blade do vendor.
- `tests/Kit/IdentidadeDoKitTest.php` CT-35 — nome e logo gravados pela tela aparecem nos três painéis (regressão do default).
- `tests/Kit/ContrasteDaNavegacaoNoTopoTest.php`, `SpotlightCssTest.php`, `CardsCssTest.php` — guardas de CSS do kit (modelo para a guarda das classes do cabeçalho).
- Helpers em `tests/Pest.php`: `usuarioDoKit()`, `usuarioCom()`, `usuarioComPapel()`, `duasOrganizacoes()`, `noPainelDa()`, `gravarConfiguracao()`, `alinharConfiguracoesDoKit()`, `configuracaoGravada()`.

### `docs/{pt,en}/recursos/configuracoes-do-kit.md`
- Tabela "Aba | O que você troca" no topo (linha **Identidade**) e uma seção `##` por opção; a seção "Logo da marca: uma só, ou duas por tema" é o modelo mais próximo (aba, campo, efeito, default e por quê).

## Decisões de Desenho

<!-- Raia desenho: decisão tomada com o desenvolvedor que NÃO passou nos três portões de ADR.
     Uma linha por decisão; a que passa nos três vira ADR no 02. "Nenhuma" é resposta válida. -->

Sessão autônoma: o desenvolvedor é a própria sessão; cada decisão leva a alternativa recusada.

| # | Decisão | Pergunta | Portão que falta | Quem decidiu, data |
|---|---|---|---|---|
| D1 | As cinco opções moram na aba **Identidade**, numa `Section` "Cabeçalho dos painéis" depois dos uploads — e não numa aba nova nem na aba Kit | Q7 | difícil de reverter (mover campo de aba é uma edição) | sessão, 2026-10-05 |
| D2 | Uma classe de apoio só, `App\Support\CabecalhoDoPainel`, com `marca()`, `marcaEscura()` e `usuario()`; os providers só trocam a Closure. Alternativa recusada: lógica nas blades (três cópias) ou um trait nos providers | Q8 | surpreendente (é o padrão de `IdentidadeDoKit`/`AssinaturaDoRodape`) | sessão, 2026-10-05 |
| D3 | O detalhe do usuário é um enum string `App\Support\DetalheDoUsuario` (`perfil`, `email`) com `coagir()`, como `DensidadeDoLayout` — valor fora da lista no `.env` ou no banco cai em `perfil`. Alternativa recusada: string solta com `in_array` | Q9 | difícil de reverter | sessão, 2026-10-05 |
| D4 | Resolução memoizada **por request** num `WeakMap` keyed pela `Request` corrente *(alterado em 2026-10-05, duas vezes: `once()` estático recusado porque sobrevive entre requests do mesmo processo; "sem memo" recusado no step 9 porque a revisão mediu 4 resoluções e até 8 `exists()` por página — `getBrandLogo()` + `getDarkModeBrandLogo()` em dois lugares)*. Alternativa recusada: cache entre requests (invalidação pela tela de settings) | Q10 | surpreendente | sessão, 2026-10-05 |
| D5 | Log no channel existente `configuracoes` (o da identidade e da tela de settings), só nos ramos anormais; nenhum log no caminho feliz, que roda a cada request. Alternativa recusada: channel novo `cabecalho-do-painel` para dois `warning`/`debug` | Q11 | trade-off (não há) | sessão, 2026-10-05 |
| D6 | O par do `.env` existe (`KIT_CABECALHO_*`) porque todas as opções booleanas vizinhas têm o seu e a seção "Quem manda" das docs promete que o `.env` semeia a primeira gravação | Q12 | surpreendente | sessão, 2026-10-05 |
| D7 | Sem opção "nenhum" no detalhe do usuário: o texto cita duas; quem não quer detalhe deixa o bloco do usuário desligado | Q13 | difícil de reverter | sessão, 2026-10-05 |
| D8 | Detalhe fora da lista gravado direto (`.env` ou banco): a tela carrega o valor **coagido** (`perfil`) e grava os demais campos normalmente — `telefone` não é valor legítimo de nenhuma fonte, então normalizar não perde dado (diferente do precedente do `mail_mailer`). Alternativa recusada: recusar a gravação até alguém escolher | Q15 (da derivação do `04`) | trade-off | sessão, 2026-10-05 |

## Autorização

- **Policies**: nenhuma nova. A tela de configurações segue `View:ConfiguracoesDoKit` (`ExigePermissaoDaTela`); o cabeçalho é renderizado para quem já está autenticado no painel.
- **Gates / Middleware / Guards**: nenhum.

## Rotas

Nenhuma rota nova. O cabeçalho vive no layout dos painéis; a tela de configurações já existe em `/admin/configuracoes-da-aplicacao`.

## Superfície de UI

| Tela / Componente | Tipo | Rota | Interação do usuário | Depende de JS? |
|---|---|---|---|---|
| Seção "Cabeçalho dos painéis" na aba Identidade de `ConfiguracoesDoKit` | Filament (SettingsPage existente) | `/admin/configuracoes-da-aplicacao?tab=identidade` (edit) | liga/desliga os quatro interruptores, escolhe e-mail/perfil, salva | Sim — o select só aparece com o bloco do usuário ligado (`->live()`) |
| Marca composta (`brandLogo`) | Blade via `HtmlString`, em `components/logo.blade.php` do Filament | toda tela dos painéis `/app`, `/admin`, `/infra` | vê; clica (link para a home do painel, nativo) | Não — a troca clara/escura é CSS pela classe `dark` |
| Bloco do usuário (`USER_MENU_BEFORE`) | Blade via `HtmlString` | idem | vê (o avatar ao lado continua abrindo o menu) | Não — a ocultação abaixo de 768 px é CSS |

**Gate de CT-B**: o que só o navegador prova aqui é (a) a composição legível nos dois temas dentro da barra lateral e do topbar e (b) o bloco do usuário oculto em viewport estreita — candidatos a costura `browser`. Gravação, visibilidade condicional do select e o markup renderizado são costura `componente`/HTTP e pertencem ao `04`.

**Gate de tela de escrita**: a seção nova é tela de edição → o `04` precisa do cenário de **gravação por componente** (`Livewire::test(ConfiguracoesDoKit::class)->fillForm([...])->call('save')`).

## Variáveis de Ambiente

| Key | Default | Descrição |
|-----|---------|-----------|
| `KIT_CABECALHO_NOME_DO_PROJETO` | `false` | mostra o nome da aplicação na marca do topo |
| `KIT_CABECALHO_NOME_DO_PAINEL` | `false` | mostra o nome do painel — ou da organização aberta — na marca do topo |
| `KIT_CABECALHO_LOGO_DA_MARCA` | `false` | mostra a logo (clara/escura) ao fim da composição |
| `KIT_CABECALHO_USUARIO` | `false` | mostra nome e detalhe de quem está autenticado à esquerda do avatar |
| `KIT_CABECALHO_DETALHE_DO_USUARIO` | `perfil` | `perfil` ou `email` — o que vai abaixo do nome |

Todas semeiam a primeira gravação do banco (migration de settings) e são o plano B sem tabela `settings`; o banco vence em runtime, como as demais.

## Eventos / Listeners / Observers

- **Eventos emitidos**: nenhum. A gravação passa pelo `SavingSettings` do pacote, já auditado por `AuditarConfiguracoesDoKit`.
- **Listeners / Observers**: nenhum novo.

## Jobs / Queues

Nenhum.

## Modelo de Execução

| Pergunta | Resposta |
|---|---|
| Quantos requests a tela custa? | 1 — a marca e o bloco do usuário são renderizados no mesmo request da página (e re-renderizados em `refresh-topbar`/`refresh-sidebar`, que o kit não dispara) |
| O que é adiado, e por qual gatilho? | nenhum |
| O que é memoizado **por request**? | `segmentos()`, num `WeakMap` com a `Request` corrente como chave *(alterado em 2026-10-05: RD-04/CR-05 — a revisão mediu 4 resoluções e até 8 `exists()` por página sem memo)*: o Filament chama `getBrandLogo()` + `getDarkModeBrandLogo()` em dois lugares, e todos leem a mesma entrada; o papel do usuário (`papelNoPainelCorrente()`) uma vez em `usuario()` |
| O que é cacheado **entre** requests? | nada novo; a config já vem do banco uma vez por request (`aplicarNaConfig()`) |
| Custo do caminho principal | com tudo desligado: **zero** query e zero `exists()` a mais (a Closure devolve o que devolvia). Com a composição ligada: os `exists()` de `IdentidadeDoKit::logo()`/`logoEscura()` que já existiam, uma vez por request pelo memo (`WeakMap` keyed pela `Request`) *(alterado em 2026-10-05: RD-04)*. Com o bloco do usuário ligado: **2** queries constantes (`exists` do `master_global` + `select roles … painel`, as mesmas que o badge do menu já faz) *(alterado em 2026-10-05: QA-05 mediu 2, não 1)* |

## Impacto em Features Existentes

- **Identidade visual / logo por tema** (`IdentidadeDoKitTest` CT-35, `LogoDarkModeTest` CT-17/18): com as opções desligadas o `brandLogo`/`darkModeBrandLogo` devolvem os mesmos valores — regressão obrigatória nos três painéis e no `/admin/login`.
- **Menu do usuário** (`CabecalhoDoMenuDoUsuarioTest`, `…TenancyTest`): o hook `USER_MENU_BEFORE` passa a existir nos três painéis e devolve `''` desligado — o CT-35 (posições dos hooks na blade do vendor) não muda.
- **Tela de configurações** (`ConfiguracoesDoKitTest`, `ConfiguracoesDoKitTelaTest`, `ConfiguracoesDoKitDocumentacaoTest`): dataset de propriedades, migrations em ida e volta, e a conferência de que cada propriedade está documentada — as cinco novas entram nos três.
- **`kit:info`** (`KitInfoTest`): contagem de propriedades 57 → 62.
- **READMEs** (`SiteDeDocumentacaoTest`, números objetivos): `Features especificadas` 73 → 74 (esta wiki); `Migrations` não muda (a migration é de settings, em `database/settings/`).
- **CSS do kit** (`OrdemDasCascadeLayersTest`, guardas de CSS): `kit.css` ganha regras novas, escopadas em `.kit-cabecalho`/`.kit-usuario`; o asset publicado em `public/css/kit/` é regenerado.

## Rollback

- **Migration down**: a migration de settings apaga as cinco chaves do grupo `kit` (`delete()`); a config volta ao `.env`/default.
- **Feature flag**: as próprias opções — desligar tudo na tela (ou no `.env`) devolve o visual de hoje sem deploy.
- **Reversão de dados**: nenhuma; não há dado de negócio.

## Dependências

- **Composer**: nenhuma nova (Filament 5.8.x, spatie/laravel-settings e o plugin já instalados).
- **NPM**: nenhuma.

## Riscos

- **Composição não cabe na barra lateral** (20 rem no confortável, 16,5 rem no denso): mitigado por fonte menor e `text-overflow: ellipsis` dentro de `.fi-sidebar`, sempre numa linha *(alterado em 2026-10-05: CR-04 — `flex-wrap` vazava por baixo do cabeçalho)*; provado pelo CT-B03.
- **Classe emitida pela blade sem regra no `kit.css`** (falha silenciosa, rule `css-filament.md`): mitigado pela guarda que extrai as classes das duas blades e exige cada uma no CSS, escopada, com piso de contagem.
- **Toggle que grava e não governa** (rule `settings.md`): mitigado pelos testes do mapa e por um CT que grava pela tela, alinha a config e lê o HTML dos painéis.
- **`papelDoPainel()` pela relação errada** (rule `models.md`): usa-se o método do model, nunca `roles()`; CT em `tests/Tenancy` com teams ligado.
- **Asset publicado desatualizado**: `php artisan filament:assets` entra na Verificação Final e o `public/css/kit/kit-correcoes.css` vai no commit *(alterado em 2026-10-05: nome do asset publicado)*.

## Channel de Log da Feature

### Verificação de Channel Existente

- `config/logging.php` tem o channel `configuracoes` (`config/logging.php:'configuracoes':153`), usado por `IdentidadeDoKit::doDisco()` e pela tela de configurações (`afterSave()`).

### Decisão

- **Channel existente**: `Log::channel('configuracoes')` — é o channel da identidade e das settings, e o cabeçalho é consumidor das duas (D5). Sem channel novo.
- Logs só nos ramos anormais (o caminho feliz roda em todo request e não é evento):
  - `warning` `[CabecalhoDoPainel@resolverSegmentos] Composição pedida sem nenhum segmento resolvido | painel: {id}` — os interruptores pedem logo e/ou painel e nada resolveu (ex.: só a logo ligada e nenhuma enviada); context `['painel' => id, 'nome_do_projeto' => bool, 'nome_do_painel' => bool, 'logo_da_marca' => bool, 'tenant_id' => ?int]`. A composição cai para a marca de hoje.
  - Sem log para "usuário sem papel no painel": é estado normal, não anomalia (corte do Ponytail #3).

## Estrutura de Implementação

### 1. Settings: cinco propriedades, o mapa e a migration

> Skills: `laravel-best-practices`

- **Path**: `app/Settings/ConfiguracoesDoKit.php` — bloco "Identidade", depois de `arte_do_login`:
  ```php
  public bool $cabecalho_nome_do_projeto;
  public bool $cabecalho_nome_do_painel;
  public bool $cabecalho_logo_da_marca;
  public bool $cabecalho_usuario;
  /** `perfil` | `email` — coagido por `DetalheDoUsuario::coagir()` ao aplicar na config. */
  public string $cabecalho_detalhe_do_usuario;
  ```
  e em `mapaDeConfiguracao()`, logo após `'arte_do_login'`:
  ```php
  'cabecalho_nome_do_projeto'    => 'kit.cabecalho.nome_do_projeto',
  'cabecalho_nome_do_painel'     => 'kit.cabecalho.nome_do_painel',
  'cabecalho_logo_da_marca'      => 'kit.cabecalho.logo_da_marca',
  'cabecalho_usuario'            => 'kit.cabecalho.usuario',
  'cabecalho_detalhe_do_usuario' => 'kit.cabecalho.detalhe_do_usuario',
  ```
  A coerção do detalhe acontece **uma vez, no consumidor** (`CabecalhoDoPainel::usuario()` chama `DetalheDoUsuario::coagir(config(...))`): nem `aplicarNaConfig()` nem a página precisam saber do enum — valor fora da lista, venha do banco ou do `.env`, vira `perfil` no único lugar que o lê (D3; corte do Ponytail #2).
- **Path**: `database/settings/2026_10_05_100000_add_cabecalho_to_kit_settings.php` (nova, `php artisan make:settings-migration` ou cópia do modelo) — `inGroup('kit')` com `add()` de cada propriedade lendo o default de `config('kit.cabecalho.*')` (booleanos com `(bool)`, detalhe com `DetalheDoUsuario::coagir(...)->value`); `down()` com `delete()` das cinco.
- **Atende**: RQ-04, RQ-05, RQ-06, P-02, P-05
- **Logs**: nenhum (a trilha de alteração é do listener existente).

### 2. `config/kit.php` e `.env.example`

> Skills: `laravel-best-practices`

- **Path**: `config/kit.php` — bloco novo, logo depois de `'identidade'`:
  ```php
  /*
  | Cabeçalho dos painéis — a marca do topo composta e o bloco do usuário.
  | Tudo nasce desligado: com as cinco chaves no default, os painéis renderizam
  | exatamente o que renderizavam antes. Governado pela aba Identidade da tela
  | de Configurações da aplicação; o .env semeia a primeira gravação.
  */
  'cabecalho' => [
      'nome_do_projeto'     => BooleanoDoEnv::comPadrao(env('KIT_CABECALHO_NOME_DO_PROJETO'), false),
      'nome_do_painel'      => BooleanoDoEnv::comPadrao(env('KIT_CABECALHO_NOME_DO_PAINEL'), false),
      'logo_da_marca'       => BooleanoDoEnv::comPadrao(env('KIT_CABECALHO_LOGO_DA_MARCA'), false),
      'usuario'             => BooleanoDoEnv::comPadrao(env('KIT_CABECALHO_USUARIO'), false),
      'detalhe_do_usuario'  => DetalheDoUsuario::coagir(env('KIT_CABECALHO_DETALHE_DO_USUARIO'))->value,
  ],
  ```
- **Path**: `.env.example` — as cinco chaves **ativas** (`KIT_CABECALHO_NOME_DO_PROJETO=false` … `KIT_CABECALHO_DETALHE_DO_USUARIO=perfil`), ao lado de `KIT_UNIFICA_LOGO_MARCA`, com o bloco de explicação *(alterado em 2026-10-05: CT-20 — o par "com o mesmo default" é lido pelo Dotenv; precedente `KIT_EXIBIR_VERSAO=false`)*.
- **Atende**: RQ-05, RQ-06, P-02, P-05, P-10
- **Logs**: nenhum.

### 3. `App\Support\DetalheDoUsuario` e `App\Support\CabecalhoDoPainel`

> Skills: `laravel-best-practices`, `ponytail`

- **Path**: `app/Support/DetalheDoUsuario.php` — `enum DetalheDoUsuario: string { case Perfil = 'perfil'; case Email = 'email'; }` com `public static function coagir(mixed $bruto): self` (`self::tryFrom((string) $bruto) ?? self::Perfil`) e `public static function opcoes(): array` (`['perfil' => 'Perfil', 'email' => 'E-mail']`) para o select. Sem `rotulo()` por case: o único consumidor do rótulo é o select (corte do Ponytail #1). Modelo: `App\Support\DensidadeDoLayout`.
- **Path**: `app/Support/CabecalhoDoPainel.php` — `final class`, só estáticos (como `IdentidadeDoKit`):
  ```php
  /** O que o `brandLogo()` dos três painéis devolve. */
  public static function marca(): string|Htmlable|null
  // composição desligada (nenhum dos três interruptores) OU nenhum segmento resolvido → IdentidadeDoKit::logo()
  // senão → new HtmlString(view('filament.cabecalho-do-painel', self::segmentos())->render())

  /** O que o `darkModeBrandLogo()` devolve: null com a composição ativa (o par de <img> já vai dentro dela). */
  public static function marcaEscura(): ?string

  /** O que o hook USER_MENU_BEFORE devolve: '' desligado ou sem usuário autenticado. */
  public static function usuario(): Htmlable

  /** @return array{projeto: ?string, painel: ?string, logo_clara: ?string, logo_escura: ?string}|null */
  public static function segmentos(): ?array   // memo por REQUEST *(alterado em 2026-10-05: RD-04/CR-05 — `WeakMap` com a `Request` corrente como chave: uma resolução por request, nada vaza entre requests da suíte; `once()` estático vazaria)*
  ```
  Regras de `segmentos()`:
  - *(alterado em 2026-10-05: ADV-06, RD-05, QA-01)* **Sem usuário autenticado, ou em tela de autenticação, devolve `null`** — tela de autenticação = rota `filament.{painel}.auth.*` **ou** componente Livewire corrente `instanceof Filament\Pages\SimplePage` (`emTelaDeAutenticacao()`). O segundo sinal existe porque o QA-01 provou que o nome da rota não cobre o update Livewire das telas autenticadas de autenticação (2FA do Breezy, aviso de verificação de e-mail): ali a rota é `default-livewire.update` e a `SimplePage` redesenha a logo dentro do componente. Toda tela de autenticação do kit é `SimplePage`; a guarda vive aqui porque o `brandLogo()` é o mesmo nas duas.
  - *(alterado em 2026-10-05: RD-07/CR-06)* O papel vem de `User::papelNoPainelCorrente()` (método novo, fonte única com o badge `perfil-indicator.blade.php`; chave de tenant não numérica fecha para `null`).
  - `projeto` = `config('app.name')` se `kit.cabecalho.nome_do_projeto`; senão `null` (P-08).
  - `painel`: se `kit.cabecalho.nome_do_painel`: tenant ativo (`Filament::getTenant()`) → `getFilamentName()`; senão `Paineis::rotulo(Paineis::correnteOuPadrao())`, e `null` quando `projeto` está preenchido e é idêntico ao rótulo (P-01).
  - `logo_clara`/`logo_escura` = `IdentidadeDoKit::logo()`/`logoEscura()` se `kit.cabecalho.logo_da_marca`; senão `null` (P-07).
  - Composição **ativa** = pelo menos um dos três interruptores ligado **e** pelo menos um segmento não nulo; com interruptor ligado e nada resolvido, `warning` e fallback para `IdentidadeDoKit::logo()` (RQ-06: nunca some a marca).
  Regras de `usuario()`:
  - `kit.cabecalho.usuario` falso ou `filament()->auth()->user()` nulo → `new HtmlString('')`.
  - `nome` = `$user->name`; `detalhe` = e-mail, ou `Papeis::rotulo($papel)` com `$papel = $user->papelDoPainel($painel->getId(), Filament::getTenant()?->getKey())` — nulo → sem linha, sem log: usuário sem papel no painel é estado normal (entra pelo `Gate::before` ou por outro caminho), não anomalia (P-03; corte do Ponytail #3).
  - Renderiza `view('filament.usuario-no-cabecalho', [...])`.
- **Atende**: RQ-01, RQ-02, RQ-03, RQ-04, RQ-06, P-01, P-02, P-03, P-07, P-08
- **Logs** (channel `configuracoes`):
  - `Log::channel('configuracoes')->warning('[CabecalhoDoPainel@resolverSegmentos] Composição pedida sem nenhum segmento resolvido | painel: '.$id, [...])` — uma vez por request (memo por `Request`); é a única anomalia: interruptor ligado e nada para mostrar

### 4. As duas blades

> Skills: `tailwindcss-development` (só para saber o que NÃO usar: nenhuma utilitária), `laravel-best-practices`

- **Path**: `resources/views/filament/cabecalho-do-painel.blade.php` — recebe `projeto`, `painel`, `logo_clara`, `logo_escura`:
  ```blade
  <span class="kit-cabecalho">
      @if (filled($projeto)) <span class="kit-cabecalho__projeto">{{ $projeto }}</span> @endif
      @if (filled($projeto) && filled($painel)) <span class="kit-cabecalho__sep" aria-hidden="true"></span> @endif
      @if (filled($painel)) <span class="kit-cabecalho__painel">{{ $painel }}</span> @endif
      @if (filled($logo_clara))
          @if (filled($projeto) || filled($painel)) <span class="kit-cabecalho__sep" aria-hidden="true"></span> @endif
          <img src="{{ $logo_clara }}" alt="{{ config('app.name') }}" class="kit-cabecalho__logo fi-logo {{ filled($logo_escura) ? 'fi-logo-light' : '' }}" />
          @if (filled($logo_escura))
              <img src="{{ $logo_escura }}" alt="{{ config('app.name') }}" class="kit-cabecalho__logo fi-logo fi-logo-dark" />
          @endif
      @endif
  </span>
  ```
  Só `{{ }}`, nunca `{!! !!}`; nenhuma utilitária Tailwind; nenhuma diretiva dentro de comentário (rule `views.md`). As classes `fi-logo-light`/`fi-logo-dark` reaproveitam o swap nativo do Filament já verificado em `feat/logo-dark-mode`.
- **Path**: `resources/views/filament/usuario-no-cabecalho.blade.php` — recebe `nome` e `detalhe` (`?string`):
  ```blade
  <span class="kit-usuario">
      <span class="kit-usuario__nome" title="{{ $nome }}">{{ $nome }}</span>
      @if (filled($detalhe)) <span class="kit-usuario__detalhe" title="{{ $detalhe }}">{{ $detalhe }}</span> @endif
  </span>
  ```
- **Atende**: RQ-01, RQ-03, RQ-04, P-03, P-07
- **Logs**: nenhum (blade não loga).

### 5. CSS em `kit.css` + republicação

> Skills: `tailwindcss-development`

- **Path**: `resources/css/filament/kit.css` — bloco novo, comentado no estilo do arquivo (sem `**/*` no comentário):
  - `.kit-cabecalho { display:inline-flex; align-items:center; gap:.75rem; white-space:nowrap; height:100%; color: var(--gray-950) }` e `.dark:root .kit-cabecalho { color: #fff }` (par dark obrigatório — o Filament usa `:where()`).
  - `.kit-cabecalho__projeto { font-weight:700; font-size:1.125rem }`, `.kit-cabecalho__painel { font-weight:600; font-size:.8125rem; letter-spacing:.08em; text-transform:uppercase; opacity:.85 }`, `.kit-cabecalho__sep { width:1px; height:1.5rem; background: currentColor; opacity:.25 }`, `.kit-cabecalho__logo { height:2rem; width:auto }` *(alterado em 2026-10-05: RD-06 — altura absoluta, não `100%`; repetida inline na blade)* — **sem `display`** *(alterado em 2026-10-05: CT-B02)*: qualquer `display` na classe da imagem vence o `:where()` do Filament e as duas logos aparecem juntas; o swap é repetido pelo kit com especificidade própria: `.kit-cabecalho .fi-logo-light { display:block }`, `.kit-cabecalho .fi-logo-dark { display:none }`, `.dark:root .kit-cabecalho .fi-logo-light { display:none }`, `.dark:root .kit-cabecalho .fi-logo-dark { display:block }`.
  - Dentro da barra lateral *(alterado em 2026-10-05: CR-04 — `flex-wrap` vazava por baixo do cabeçalho de altura fixa)*: **sem quebra de linha**; `.kit-cabecalho { max-width:100%; min-width:0 }`, `__projeto`/`__painel` com `overflow:hidden; text-overflow:ellipsis; min-width:0`, e na barra lateral `gap:.5rem`, `__projeto` 1rem, `__painel` .6875rem (P-06). A logo tem `height:2rem` absoluto (CSS e inline na blade — RD-06: a página de erro do Sentinel ecoa o `brandLogo` sem o `div.fi-logo`).
  - Bloco do usuário: `.kit-usuario { display:none; flex-direction:column; line-height:1.15; text-align:end; margin-inline-end:.5rem; color: var(--gray-950) }`, `@media (min-width:768px) { .kit-usuario { display:flex } }` (P-04), `.kit-usuario__nome { font-weight:600; font-size:.875rem }`, `.kit-usuario__detalhe { font-size:.75rem; opacity:.75 }`, `.dark:root .kit-usuario { color:#fff }`.
- Depois: `php artisan filament:assets` e o `public/css/kit/kit-correcoes.css` regenerado entra no commit *(alterado em 2026-10-05: o nome publicado vem do `Css::make('kit-correcoes', …)`, não do arquivo fonte)*.
- **Atende**: RQ-01, RQ-03, P-04, P-06
- **Logs**: nenhum.

### 6. Os três `PanelProvider`

> Skills: `laravel-best-practices`

- **Path**: `app/Providers/Filament/AdminPanelProvider.php`, `AppPanelProvider.php`, `InfraPanelProvider.php` — trocar as duas Closures e acrescentar o hook:
  ```php
  ->brandLogo(fn (): string|Htmlable|null => CabecalhoDoPainel::marca())
  ->darkModeBrandLogo(fn (): ?string => CabecalhoDoPainel::marcaEscura())
  // …
  ->renderHook(PanelsRenderHook::USER_MENU_BEFORE, fn (): Htmlable => CabecalhoDoPainel::usuario())
  ```
  O docblock existente sobre "Closure e não escalar" continua valendo e ganha a frase de que a Closure agora decide entre a logo solta e a composição. O comentário vizinho que diz "GLOBAL_SEARCH_BEFORE, e não USER_MENU_BEFORE" fala do gatilho do Spotlight e continua verdadeiro.
- **Atende**: RQ-01, RQ-03, RQ-06, P-06, P-09
- **Logs**: nenhum (os logs estão na classe de apoio).

### 7. A seção na aba Identidade

> Skills: `filament-development`

- **Path**: `app/Filament/Admin/Pages/ConfiguracoesDoKit.php` — ao fim do `schema` de `abaIdentidade()`:
  ```php
  Section::make('Cabeçalho dos painéis')
      ->description('A marca do topo e o bloco do usuário. Tudo nasce desligado: com nada ligado, os painéis ficam como hoje.')
      ->schema([
          Toggle::make('cabecalho_nome_do_projeto')->label('Nome do projeto na marca do topo')
              ->helperText('O nome da aplicação em texto, antes do nome do painel e da logo.')->inline(false),
          Toggle::make('cabecalho_nome_do_painel')->label('Nome do painel na marca do topo')
              ->helperText('Administração, Infraestrutura ou, no painel do negócio, o nome da organização aberta.')->inline(false),
          Toggle::make('cabecalho_logo_da_marca')->label('Logo da marca ao fim da composição')
              ->helperText('A logo da aba Identidade (clara e escura, se a marca estiver separada). Sem logo enviada, nada aparece neste lugar.')->inline(false),
          Toggle::make('cabecalho_usuario')->label('Nome do usuário ao lado do avatar')
              ->helperText('Quem está autenticado, à esquerda do avatar. Some em tela estreita; o menu do avatar continua mostrando tudo.')->live()->inline(false),
          Select::make('cabecalho_detalhe_do_usuario')->label('Abaixo do nome do usuário')
              ->options(DetalheDoUsuario::opcoes())
              ->selectablePlaceholder(false)->required()
              ->visible(fn (Get $get): bool => (bool) $get('cabecalho_usuario')),
      ])
      ->columns(2),
  ```
  Sem `->default()` (a `SettingsPage` preenche do banco, que já nasce com `perfil`; corte do Ponytail #4). *(alterado em 2026-10-05: ADV-14/D8)* **Com** coerção em `mutateFormDataBeforeFill()` — uma linha ao lado da da densidade, pelo mesmo motivo: `telefone` gravado à mão no estado do formulário trava a tela inteira na validação do `Select`. O consumidor continua coagindo a leitura (passo 3); só a coerção em `aplicarNaConfig()` ficou cortada (Ponytail #2, revisto).
- **Atende**: RQ-04, RQ-05, P-02, P-05, P-10
- **Logs**: `afterSave()` existente (`[ConfiguracoesDoKit@afterSave]`) já registra a gravação.

### 8. Documentação pt e en, READMEs e contagens

> Skills: nenhuma

- **Path**: `docs/pt/recursos/configuracoes-do-kit.md` e `docs/en/recursos/configuracoes-do-kit.md` — (a) linha **Identidade** da tabela do topo ganha "e o cabeçalho dos painéis (marca composta e bloco do usuário)"; (b) seção nova `## Cabeçalho dos painéis: o que mostrar no topo` logo depois de "Logo da marca: uma só, ou duas por tema", no formato das vizinhas: aba e campos, efeito e alcance, o default e o porquê, a nota de que no `/app` o nome do painel é o da organização aberta (e omitido quando repetiria o projeto), a ocultação abaixo de 768 px e as cinco chaves do `.env`.
- **Path**: `README.md` e `README.en.md` — `Features especificadas (wikis/specs/)` **73 → 74** (os dois lugares em cada readme).
- **Path**: `tests/Kit/KitInfoTest.php` — `toHaveCount(57)` → `62`, com a linha de histórico no docblock.
- **Atende**: RQ-05 (documentação da opção), RQ-07
- **Logs**: nenhum.

### 9. Testes

> Skills: `pest-testing`, `testing-best-practices`

- Derivados pela `feature-test-design` (step 7) a partir do `00`; arquivos previstos: `tests/Kit/CabecalhoDoPainelTest.php` (default intacto, cada interruptor, gravação pela tela, mapa/migration, guarda de CSS das blades), `tests/Tenancy/CabecalhoDoPainelTenancyTest.php` (organização no segmento do painel; papel no contexto da organização) e, se a costura `browser` for confirmada, `tests/Browser/CabecalhoDoPainelTest.php`.
- **Atende**: RQ-08
- **Logs**: nenhum.

## Filosofia de Implementação

> **Ponytail ativo em modo `full`** durante toda a implementação.
> Cada passo deve aplicar a escada de simplicidade:
> 1. Reutilizar código existente antes de criar novo
> 2. Usar stdlib do PHP/Laravel antes de código custom
> 3. Usar features nativas antes de dependências
> 4. Uma linha quando possível
> 5. Mínimo código que funciona
>
> Atalhos deliberados devem ser marcados com `ponytail:` comment.
> Após implementação, rodar `/ponytail:ponytail-review` no diff.
>
> **Caveman ativo em modo `ultra`** (padrão) na comunicação agent ↔ usuário.
> Arquivos wiki (00-06) são boundary do Caveman — escrever em prosa normal.
> Código, commits e PRs também são boundary do Caveman.
>
> **Baseline antes do primeiro commit**: suíte `Kit,Tenancy` da `feat/logo-dark-mode` (que vira a `main`
> desta branch) em 2026-10-05: `php artisan test --testsuite=Kit,Tenancy --parallel --processes=4 --compact`
> → **3829 testes, 3826 passaram, 3 pulados, 0 falhas** (610 s). A `## Verificação Final` compara contra isso.

## Mapeamentos

| Propriedade (settings) | Chave de config | `.env` | Tipo | Default |
|---|---|---|---|---|
| `cabecalho_nome_do_projeto` | `kit.cabecalho.nome_do_projeto` | `KIT_CABECALHO_NOME_DO_PROJETO` | bool | `false` |
| `cabecalho_nome_do_painel` | `kit.cabecalho.nome_do_painel` | `KIT_CABECALHO_NOME_DO_PAINEL` | bool | `false` |
| `cabecalho_logo_da_marca` | `kit.cabecalho.logo_da_marca` | `KIT_CABECALHO_LOGO_DA_MARCA` | bool | `false` |
| `cabecalho_usuario` | `kit.cabecalho.usuario` | `KIT_CABECALHO_USUARIO` | bool | `false` |
| `cabecalho_detalhe_do_usuario` | `kit.cabecalho.detalhe_do_usuario` | `KIT_CABECALHO_DETALHE_DO_USUARIO` | `perfil` \| `email` | `perfil` |

| Situação | `marca()` | `marcaEscura()` |
|---|---|---|
| três interruptores desligados | `IdentidadeDoKit::logo()` (hoje) | `IdentidadeDoKit::logoEscura()` (hoje) |
| algum ligado e algum segmento resolvido | `HtmlString` da composição | `null` |
| algum ligado e nenhum segmento resolvido | `IdentidadeDoKit::logo()` + `warning` | `IdentidadeDoKit::logoEscura()` |

## Testes

> Ver `04-casos-de-teste.md` para especificação completa dos cenários de backend.
> Ver `05-casos-de-teste-browser.md` para os cenários de UI (se a costura `browser` for confirmada).

## Verificação Final
- [ ] `/ponytail:ponytail-review` no diff (validar contra over-engineering)
- [ ] `vendor/bin/pint --dirty`
- [ ] `vendor/bin/phpstan analyse` (nível 8) e `vendor/bin/filacheck`
- [ ] `php artisan filament:assets` e `public/css/kit/kit-correcoes.css` no commit *(alterado em 2026-10-05)*
- [ ] `vendor/bin/pest --filter=CabecalhoDoPainel --compact` (CTs de backend, Kit e Tenancy)
- [ ] `vendor/bin/pest tests/Browser --filter=CabecalhoDoPainel` (CT-B — só se houver `05-*-browser.md`)
- [ ] Regressão: `IdentidadeDoKitTest`, `LogoDarkModeTest`, `CabecalhoDoMenuDoUsuario*Test`, `ConfiguracoesDoKit*Test`, `KitInfoTest`, `SiteDeDocumentacaoTest`, `CitacoesDeCodigoTest`
- [ ] `php artisan test --testsuite=Kit,Tenancy --parallel --processes=4 --compact` — comparado à **baseline** (3826 verdes / 0 falhas)
- [ ] `pest --mutate --path=app/Support/CabecalhoDoPainel.php` — score, **duração** e lista de sobreviventes (no Windows, via `pestw.cmd`)
- [ ] **Custo medido** — queries com tudo desligado × com tudo ligado, contra o `## Modelo de Execução`
- [ ] **`/code-review high main...HEAD` + passe de eixos (step 9)** — antes da reconciliação

## Commits
- `:sparkles: feat(cabecalho): marca composta e bloco do usuário no topo dos painéis, pelas configurações`
- `:memo: docs(cabecalho): opções do cabeçalho na página de configurações (pt/en)`
- `:white_check_mark: test(cabecalho): casos de teste do cabeçalho dos painéis`
- `:memo: wiki: feature cabecalho-do-painel`
