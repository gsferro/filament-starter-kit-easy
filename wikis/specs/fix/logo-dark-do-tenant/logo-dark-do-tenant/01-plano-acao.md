# Plano de Ação — fix/logo-dark-do-tenant: a logo da organização (clara e escura) no topo do `/app`

> Requisito: `00-requisito.md`

## Natureza da Wiki

- **Tipo**: correção
- **Wiki ancestral**: `wikis/specs/feat/logo-dark-mode/logo-dark-mode/` (criou a variante escura da instalação e da organização, e a única superfície em que a escura da organização troca com o tema: a tela de bloqueio) e `wikis/specs/feat/cabecalho-do-painel/cabecalho-do-painel/` (decidiu, em Q16/P-12, que a marca do topo é sempre a da instalação, inclusive no `/app`; esta entrega reverte a P-12 só para o `/app` com organização aberta, ver ADR-01)
- **Motivo**: a escura da organização existe (coluna, formulário, model), mas só a tela de bloqueio a consulta; o topo do `/app/{organização}` mostra a logo da instalação, e por isso "não troca com o modo" para quem sobe a logo da organização. O Adendo 1 do `00` escolheu a opção (a) da Q1: o topo do `/app` com organização aberta
- **Toca infra compartilhada?**: sim → `App\Support\CabecalhoDoPainel` (consumida pelos três `PanelProvider`), `App\Support\IdentidadeDoKit` (dezesseis pontos de consumo) e `Database\Factories\TenantFactory::comIdentidadeVisual()` (usada em muitos testes). Regressão obrigatória contra `tests/Kit/LogoDarkModeTest.php`, `tests/Kit/CabecalhoDoPainelTest.php`, `tests/Kit/CabecalhoDoPainelTelaTest.php`, `tests/Tenancy/CabecalhoDoPainelTenancyTest.php`, `tests/Tenancy/IdentidadeVisualTenancyTest.php`, `tests/Browser/LogoDarkModeTest.php`, `tests/Browser/CabecalhoDoPainelTest.php` e `tests/BrowserTenancy/IdentidadeVisualTest.php`

## Cobertura do Requisito

| RQ | Cláusula | Passo(s) que atende(m) | Observação |
|----|----------|------------------------|------------|
| RQ-01 | revisar o uso da feature "logo escura" (onde cada variante aparece e quando troca) | 6 | análise entregue na seção `## Revisão da feature (RQ-01, RQ-02)` abaixo; o passo 6 fixa o mapa de uso na documentação |
| RQ-02 | revisar a implementação e achar o que ficou de fora com organização ativa | 1, 2, 3, 6 | a revisão achou oito lacunas; as corrigidas viram os passos 1 a 3 e o texto do passo 6; as não escolhidas pelo solicitante ficam registradas, fora de escopo |
| RQ-03 | a escura da organização troca com o modo onde a organização é usada | — | substituída por RQ-05 (Adendo 1); sem passo próprio |
| RQ-04 | a logo da marca da instalação (clara e escura) não muda | 1, 2, 4, 5 | `logosPara(null)` devolve o que `logo()` e `logoEscura()` devolviam; CT-16 e o CT-B01 de `LogoDarkModeTest` ficam verdes sem alteração |
| RQ-05 | topo do `/app` com organização aberta usa a logo da organização, clara e escura, com queda para a da instalação | 1, 2, 3, 4, 5, 6, 7 | núcleo da entrega; o passo 3 torna o caso testável (factory) e o texto do formulário verdadeiro |
| P-01 | a tela de bloqueio já troca e não muda | 1, 4 | o passo 1 troca a implementação sem mudar a saída, em nenhum caso |
| P-02 | a composição continua regida pela feature do cabeçalho; só muda qual logo | 2 | — |
| P-03 | resolução por variante, independente, a da tela de bloqueio | 1, 4 | a regra está escrita uma vez, em `## Mapeamentos` |
| P-04 | marca unificada ignora a escura da organização | 1, 4 | idem |
| P-05 | a página de erro do Sentinel dentro de `/app/{slug}` usa a mesma marca | — | ⚠️ fora desta entrega: consequência da marca ser a mesma Closure; sem passo nem CT próprio |
| P-06 | o `alt` das imagens da marca não muda | — | ⚠️ fora desta entrega: o `alt` com o nome da organização é da tela de bloqueio |

## Passos

| Passo | O quê | Arquivos | Depende | Critério de pronto |
|---|---|---|---|---|
| 1 | `IdentidadeDoKit::logosPara()` e delegação de `TelaBloqueio::urlsDasLogos()` | `app/Support/IdentidadeDoKit.php`, `app/Filament/Pages/Auth/TelaBloqueio.php` | — | CT-16 de `LogoDarkModeTest` verde sem alteração; `logosPara(null)` igual ao par da instalação |
| 2 | `CabecalhoDoPainel`: `organizacaoAberta()`, memo único, `marca()`, `marcaEscura()`, `resolverSegmentos()`, docblocks | `app/Support/CabecalhoDoPainel.php` | 1 | `/app/{org}` com logo própria mostra o par da organização nas duas formas; `/admin`, `/infra` e `/app` sem organização iguais aos de hoje |
| 3 | Factory aceita a escura; textos que a mudança desmente | `database/factories/TenantFactory.php`, `app/Filament/Admin/Resources/Tenants/Schemas/TenantForm.php`, `app/Models/Tenant.php` | 1 | `comIdentidadeVisual()` grava `logo_dark`; `filacheck` e `pint` silenciosos |
| 4 | Testes de backend, nos arquivos que já existem | `tests/Kit/LogoDarkModeTest.php`, `tests/Tenancy/CabecalhoDoPainelTenancyTest.php` | 1, 2, 3 | verdes; vermelhos com o passo 2 revertido |
| 5 | Teste de navegador do swap no topo do `/app` | `tests/BrowserTenancy/IdentidadeVisualTest.php` | 2, 3 | verde em série; vermelho com o passo 2 revertido |
| 6 | Documentação pt/en, CHANGELOG, READMEs | `docs/pt/recursos/configuracoes-do-kit.md`, `docs/en/recursos/configuracoes-do-kit.md`, `CHANGELOG.md`, `README.md`, `README.en.md` | 2 | `SiteDeDocumentacaoTest` e `RedeDeDocumentacaoTest` verdes |
| 7 | Índice das wikis por `indice.sh` | `wikis/specs/INDEX.md` | 1 a 6 | linha de `fix/logo-dark-do-tenant` no índice |

## Objetivo

Fazer o topo do painel `/app`, com uma organização aberta, mostrar a logo da organização e trocá-la com o tema claro/escuro, caindo para a logo da instalação quando a organização não tem logo. Vale para a marca simples (a que o Filament desenha com `brandLogo()` e `darkModeBrandLogo()`) e para a composição do cabeçalho (nome do projeto, da organização e logo), que já tem o par `fi-logo-light`/`fi-logo-dark` dentro dela.

A regra de queda que decide qual par mostrar já existe, escrita uma vez, na tela de bloqueio. A entrega a promove a um método único da identidade da instalação e a usa nos três lugares: bloqueio, marca simples e composição. Os painéis `/admin` e `/infra`, as telas de autenticação e a instalação sem organização ficam exatamente como estão.

## Contexto

A feature `logo-dark-mode` entregou a variante escura em duas camadas: a da instalação (aba Identidade das configurações) e a da organização (campo `logo_dark` do formulário de organização). A feature `cabecalho-do-painel` depois fixou, por decisão de desenho confirmada pelo solicitante (P-12), que a marca do topo é "sempre a da instalação, inclusive no `/app`". As duas decisões são coerentes entre si e deixam um buraco: quem sobe a logo clara e a escura de uma organização só as vê trocar na tela de bloqueio, que é a tela que o usuário menos usa. O solicitante leu isso como defeito ("no caso do tenant ... não esta sendo alterada conforme troca o modo") e escolheu o topo do `/app` como lugar de correção.

## Revisão da feature (RQ-01, RQ-02)

Mapa de onde cada logo aparece hoje, lido do código na worktree. "Troca com o tema?" é a pergunta do requisito: a variante escura é entregue ao navegador e o CSS escolhe pela classe `dark` do `<html>`.

| Superfície | Logo clara | Logo escura | Troca com o tema? | Fonte |
|---|---|---|---|---|
| Topo do `/admin` | da instalação | da instalação (só com a marca separada) | sim | `app/Providers/Filament/AdminPanelProvider.php:brandLogo():91` e `darkModeBrandLogo():94`, que delegam a `app/Support/CabecalhoDoPainel.php:marca():65` e `marcaEscura():71` |
| Topo do `/infra` | da instalação | da instalação (marca separada) | sim | `app/Providers/Filament/InfraPanelProvider.php:brandLogo():112` e `darkModeBrandLogo():115` |
| Topo do `/app` sem organização aberta (instalação sem tenancy, rota fora de organização) | da instalação | da instalação (marca separada) | sim | `app/Providers/Filament/AppPanelProvider.php:brandLogo():100` e `darkModeBrandLogo():103` |
| Topo do `/app/{organização}`, composição desligada (marca simples) | **da instalação** (a da organização é ignorada) | **da instalação** | sim, mas com a imagem errada para quem tem logo própria | `app/Support/CabecalhoDoPainel.php:marca():65` devolve `IdentidadeDoKit::logo()` na linha 61; `marcaEscura():71` devolve `IdentidadeDoKit::logoEscura()` na linha 73 |
| Topo do `/app/{organização}`, composição ligada | **da instalação** | **da instalação**, dentro da composição (`fi-logo-light`/`fi-logo-dark`) | sim, mas com a imagem errada | `app/Support/CabecalhoDoPainel.php:resolverSegmentos():223`, linhas 179 e 180 (`$logoClara`, `$logoEscura`); par no `resources/views/filament/cabecalho-do-painel.blade.php` |
| Página de erro do Sentinel dentro de `/app/{slug}` | a marca do painel (hoje a da instalação) | idem | sim | `vendor/anselmokossa/filament-sentinel/src/Support/Sentinel.php:brandLogo():144` lê `getBrandLogo()` e `getDarkModeBrandLogo()` do painel; passa a mostrar a da organização, junto com o topo (P-05) |
| Tela de bloqueio do `/app` (rota `/app/screen/lock`, sem organização na URL) | da organização, senão da instalação | da organização, senão da instalação (marca separada); `null` com a marca unificada | **sim** (o único lugar em que a escura da organização é usada) | `app/Filament/Pages/Auth/TelaBloqueio.php:urlsDasLogos():105`; o topo dessa tela não emite a marca (comentário do CT-16 em `tests/Kit/LogoDarkModeTest.php:'[CT-16]':319`) |
| Login (`/login`, `/app/login`, `/admin/login`, `/infra/login`) | da instalação (marca do topo, tela de autenticação) | da instalação (marca separada) | sim | `app/Support/CabecalhoDoPainel.php:emTelaDeAutenticacao():297` devolve a marca de hoje; sem sessão não há organização. A mídia da tela é a arte (`IdentidadeDoKit::arteDoLogin()`), não a logo |
| Cadastro da organização no `/admin`: formulário | upload `logo` | upload `logo_dark`, visível só com a marca separada | não se aplica (é upload) | `app/Filament/Admin/Resources/Tenants/Schemas/TenantForm.php:'logo':173`, `'logo_dark':222`; o texto de ajuda do `logo` ainda diz "Exibida na tela de bloqueio" (linha 208) |
| Cadastro da organização: cabeçalho da ficha | da organização (avatar), `null` cai nas iniciais | **nunca mostrada** | não | `app/Filament/Admin/Resources/Tenants/Schemas/TenantHeader.php:avatar():39` |
| Cadastro da organização: ficha | da organização | **nunca mostrada** | não | `app/Filament/Admin/Resources/Tenants/Schemas/TenantInfolist.php:ImageEntry:94` |
| Cadastro da organização: listagem | da organização (coluna crua, com lightbox) | **nunca mostrada** | não | `app/Filament/Admin/Resources/Tenants/Tables/TenantsTable.php:ImageColumn:41` |
| Seletor de organizações do `/app` | nenhuma (avatar de iniciais) | nenhuma | não se aplica | `Tenant` não implementa `HasAvatar`, então o Filament usa o provedor padrão: `vendor/filament/filament/src/FilamentManager.php:getTenantAvatarUrl():453`, ramo `HasAvatar` na linha 457; a view lê em `vendor/filament/filament/resources/views/components/tenant-menu.blade.php:$tenantImage:124` |

### O que ficou esquecido (RQ-02)

Os consumidores da logo da organização, por `grep` em `app/` e `resources/views/`, são só estes: `TelaBloqueio`, `TenantHeader`, `TenantInfolist` e `TenantsTable` (esta lê a coluna crua). A escura, `app/Models/Tenant.php:urlDaLogoEscura():215`, tem **um único consumidor**, `TelaBloqueio` (linha 110). As lacunas:

| # | Lacuna | Destino |
|---|---|---|
| L1 | o topo do `/app/{organização}` ignora a logo da organização, clara e escura (a escura é a queixa do requisito) | corrigida: passos 1 e 2 (RQ-05) |
| L2 | a regra de queda mora inline em `TelaBloqueio::urlsDasLogos()`; duplicá-la no cabeçalho produziria duas regras com o mesmo nome | corrigida: passo 1 (método único, ADR-02) |
| L3 | `TenantFactory::comIdentidadeVisual()` não aceita a escura (`database/factories/TenantFactory.php:comIdentidadeVisual():41`): todo teste que precisa de uma organização com par monta o `create()` à mão, como o CT-16 | corrigida: passo 3 |
| L4 | o texto de ajuda do upload `logo` diz "Exibida na tela de bloqueio de sessão do painel de negócio" e "Em branco, usa a imagem padrão", que deixam de ser a história toda; o docblock de `Tenant::urlDaLogoEscura()` diz que só a tela de bloqueio a consulta | corrigida: passo 3 |
| L5 | a documentação diz duas vezes o contrário do que passa a valer: "a logo da composição é sempre a da instalação" e "a da organização segue aparecendo só na tela de bloqueio" (pt e en, seção do cabeçalho dos painéis de `configuracoes-do-kit.md`); já a seção "Isto não é o settings de uma organização" do mesmo arquivo afirma que a logo da organização "vence a do kit dentro de `/app/{slug}`", o que hoje é falso para o topo | corrigida: passo 6 |
| L6 | nenhum teste de navegador cobre o swap de uma logo de **organização** (o CT-B01 de `LogoDarkModeTest` usa `master_global` sem organização, de propósito; o de `IdentidadeVisualTest` cobre a clara na tela de bloqueio) | corrigida: passo 5 |
| L7 | a escura da organização não é visível em nenhuma tela do `/admin`: o administrador que a envia nunca a vê (cabeçalho, ficha e listagem mostram só a clara, sem troca de tema) | **fora de escopo** (opção (b)/(c) da Q1, não escolhida): sugestão ao solicitante, não é passo |
| L8 | organização com **só** a escura (sem a clara): a clara da instalação com a escura da organização | não é lacuna: a regra por variante é a replicada, e o caso de borda fica como está na tela de bloqueio; ganha linha na tabela de decisão do teste unitário |

A tela de bloqueio é o controle de "não muda" (P-01): a saída dela é idêntica, sem ressalva. O seletor de organizações e o login não têm organização em jogo e não mudam.

## Análise dos Arquivos Existentes

### `app/Support/IdentidadeDoKit.php`
- `logo()` (`app/Support/IdentidadeDoKit.php:logo():51`) e `logoEscura()` (`app/Support/IdentidadeDoKit.php:logoEscura():65`) resolvem a instalação pelo disco `public` com guarda de existência e `asset('storage/…')`; `logoEscura()` devolve `null` com a marca unificada (`unificaLogo()`, `app/Support/IdentidadeDoKit.php:unificaLogo():80`). A classe é `final`, só estáticos, e se justifica pela guarda (`doDisco()`, `app/Support/IdentidadeDoKit.php:doDisco():144`). Ganha `logosPara()`.

### `app/Models/Tenant.php`
- `urlDaLogo()` (`app/Models/Tenant.php:urlDaLogo():188`) e `urlDaLogoEscura()` (`app/Models/Tenant.php:urlDaLogoEscura():215`) devolvem `null` com coluna em branco ou arquivo ausente do disco, em silêncio, e `asset('storage/…')` caso contrário, como pede a rule `app.md`. Não mudam; `logo_dark` está no `$fillable` (`app/Models/Tenant.php:'logo_dark':91`). Só o docblock de `urlDaLogoEscura()` muda.

### `app/Filament/Pages/Auth/TelaBloqueio.php`
- `urlsDasLogos()` (`app/Filament/Pages/Auth/TelaBloqueio.php:urlsDasLogos():105`) é a regra de hoje, dentro de um `once()`. A organização vem da **sessão** (`tenant_corrente`), por `organizacaoResolvida()` (`app/Filament/Pages/Auth/TelaBloqueio.php:organizacaoResolvida():185`), porque a rota `/app/screen/lock` não tem organização na URL (ADR-03); é nessa função, e não em `getAuthDesignerConfig()`, que mora a guarda de painel (`app/Filament/Pages/Auth/TelaBloqueio.php:correnteOuPadrao():188`). A partial `media.blade.php` lê o par por `urlsDasLogos()` (`resources/views/vendor/filament-auth-designer/components/partials/media.blade.php:$logos:20`). Passa a delegar.

### `app/Support/CabecalhoDoPainel.php`
- `marca()` (`app/Support/CabecalhoDoPainel.php:marca():65`) e `marcaEscura()` (`app/Support/CabecalhoDoPainel.php:marcaEscura():98`) devolvem a logo da instalação quando `segmentos()` é `null` (composição desligada, tela de autenticação, ou composição pedida sem nenhum segmento resolvido). `marcaEscura()` devolve `null` com a composição ativa, e continua assim. `resolverSegmentos()` (`app/Support/CabecalhoDoPainel.php:resolverSegmentos():223`) já lê `Filament::getTenant()` (linha 166, para o nome da organização) e resolve as duas logos na instalação (linhas 179 e 180). `segmentos()` (`app/Support/CabecalhoDoPainel.php:segmentos():147`) memoiza por `Request` num `WeakMap`.
- O docblock da classe afirma o contrato RQ-06 ("com tudo desligado, os painéis renderizam o que renderizavam antes") e o de `resolverSegmentos()` cita P-07/P-12. Os dois ganham a ressalva da organização aberta.

### `vendor/filament/filament/resources/views/components/logo.blade.php`
- Para marca em `string`, o Filament emite a `<img>` clara e, se `darkModeBrandLogo()` é preenchido, uma segunda `<img>` com `fi-logo-dark` (`vendor/filament/filament/resources/views/components/logo.blade.php:$darkModeBrandLogo:8`); para `Htmlable`, envolve numa `div.fi-logo`, sem `<img>` próprio (`vendor/filament/filament/resources/views/components/logo.blade.php:$logo instanceof Htmlable:21`). Com a marca clara `null` e a escura preenchida, o Filament desenha o nome em texto no claro e a `<img>` escura no escuro.
- Quem desenha a marca no painel são os componentes Livewire `Topbar` e `Sidebar`, que chamam `<x-filament-panels::logo />` nas views `topbar.blade.php` e `sidebar.blade.php` de `vendor/filament/filament/resources/views/livewire/` e se redesenham em update por eventos (`vendor/filament/filament/src/Livewire/Topbar.php:'refresh-topbar':22`, `vendor/filament/filament/src/Livewire/Sidebar.php:'refresh-sidebar':22`). A organização continua resolvida nesses updates porque `IdentifyTenant` é middleware persistente do Livewire (`vendor/filament/filament/src/FilamentServiceProvider.php:IdentifyTenant:114`).
- A organização aberta é `Filament::getTenant()`, que devolve `$this->tenant` sem olhar o painel (`vendor/filament/filament/src/FilamentManager.php:getTenant():448`); o binding `filament` é `scoped` (`vendor/filament/filament/src/FilamentServiceProvider.php:scoped:65`). Quem diz se o painel tem organização é `Panel::hasTenancy()` (`vendor/filament/filament/src/Panel/Concerns/HasTenancy.php:hasTenancy():207`).

### `database/factories/TenantFactory.php`
- `comIdentidadeVisual()` (`database/factories/TenantFactory.php:comIdentidadeVisual():41`) recebe cor, logo e paleta, nunca a escura. Ganha `?string $logoEscura = null` no fim.

### Testes que fixam o comportamento atual
- O antigo CT-07 de `tests/Tenancy/CabecalhoDoPainelTenancyTest.php` (substituído pelo CT-49, que o código atual traz; referência histórica) afirmava que, no `/app` da Acme com logo própria, a composição mostra a logo da instalação e nenhuma de organização. **Foi substituído** (passo 4), e a aprovação dessa substituição é a resposta do solicitante no Adendo 1 do `00` (reversão da P-12 no `/app`).
- `tests/Tenancy/CabecalhoDoPainelTenancyTest.php:'[CT-27]':676` usa `tenant('Acme', 'acme')`, uma organização **sem logo** (`tests/Pest.php:tenant():387`): com a regra nova ela cai na logo da instalação e o caso continua verde, sem edição. Ele **não** é o controle de nenhuma queda de `logosPara()` (o controle é o caso do passo 4 que declara a presença da clara da instalação).
- Helpers do arquivo: `gravarCabecalhoDaTenancia()` (`tests/Tenancy/CabecalhoDoPainelTenancyTest.php:gravarCabecalhoDaTenancia():42`), `gravarLogoDaInstalacaoDaTenancia()` (`tests/Tenancy/CabecalhoDoPainelTenancyTest.php:gravarLogoDaInstalacaoDaTenancia():57`) e `imagensDaMarcaDaTenancia()` (`tests/Tenancy/CabecalhoDoPainelTenancyTest.php:imagensDaMarcaDaTenancia():77`). Como os casos novos entram **no mesmo arquivo**, os helpers continuam usados por um arquivo só e ficam onde estão; `regiaoDoHeader()` já mora em `tests/Pest.php` (`tests/Pest.php:regiaoDoHeader():1739`).
- Ficam como estão: `tests/Kit/LogoDarkModeTest.php:'[CT-16]':319` (a tela de bloqueio, tabela de decisão), `tests/Browser/LogoDarkModeTest.php:'[CT-B01]':26` (swap no navegador sem organização), `tests/Kit/CabecalhoDoPainelTest.php` (sem tenancy) e `tests/Browser/CabecalhoDoPainelTest.php:'[CT-B02]':85` (swap da composição no `/admin`).

## Decisões de Desenho

<!-- Decisão tomada com o desenvolvedor que NÃO passou nos três portões de ADR. A que passa vira ADR no 02. -->

| # | Decisão | Pergunta | Portão que falta | Quem decidiu, data |
|---|---|---|---|---|
| D1 | A regra de queda vira **um** método, `IdentidadeDoKit::logosPara(?Tenant $organizacao): array{clara: ?string, escura: ?string}`; a tela de bloqueio delega a ele e `CabecalhoDoPainel` o usa nos dois caminhos. Ver ADR-02 | — | registrada como ADR-02 (falha o portão "difícil de reverter", mantida por pedido do orquestrador) | gsferro, 2026-10-07 |
| D2 | A regra é a da tela de bloqueio, por variante e independente, **escrita uma vez em `## Mapeamentos`** (P-03/P-04 do `00`, P-03 revisada em 2026-10-07). A tela de bloqueio não muda | Q1 (Adendo 1) | difícil de reverter (é o corpo de um método) | gsferro, 2026-10-07; revisão da P-03: orquestrador, 2026-10-07 |
| D3 | `TenantFactory::comIdentidadeVisual()` ganha `?string $logoEscura = null` no **fim** da assinatura, para os chamadores existentes (posicionais) não mudarem | — | difícil de reverter, surpreendente, trade-off (é um parâmetro opcional) | gsferro, 2026-10-07 |
| D4 | **Um memo só**: a entrada do `WeakMap` por `Request` de `CabecalhoDoPainel` guarda os segmentos **e** o par de logos juntos; `marca()` e `marcaEscura()` leem o par dessa entrada (cada uma é chamada duas vezes por página, barra lateral e topbar). Sem chave de organização: ela não muda dentro de um request. `IdentidadeDoKit::logosPara()` fica sem cache (a classe se declara sem cache de propósito) | — | difícil de reverter, trade-off (memoização por request, apagável) | sessão, 2026-10-07 |
| D5 | O CT do update Livewire da `Topbar` usa `Livewire::test(\Filament\Livewire\Topbar::class)` com o painel `app` corrente e a organização fixada por `Filament::setTenant($org)` (o mesmo estado que o middleware persistente `IdentifyTenant` recria no update real), afirmando o par da organização no HTML renderizado; o POST real ao endpoint de update fica fora do arnês. Mata o mutante M31 do `04`; o restante do mutante da rota é coberto pelos CT HTTP | Q6 (`04`) | difícil de reverter, surpreendente, trade-off (é mecanismo de teste) | sessão, 2026-10-07 |

## Autorização

- **Policies / Gates / Middleware / Guards**: nenhum novo. O topo mostra a logo da organização **aberta**, que o middleware de tenancy do Filament já validou para o usuário.

## Rotas

Nenhuma nova.

## Superfície de UI

| Tela / Componente | Tipo | Rota | Interação do usuário | Depende de JS? |
|---|---|---|---|---|
| Topo do `/app/{organização}`, composição **desligada**: `<img class="fi-logo fi-logo-light">` (barra lateral e topbar) e `fi-logo-dark` do Filament | Filament (marca do painel, componentes `Topbar`/`Sidebar`) | `/app/{slug}` | abre o painel e alterna o tema: a imagem visível passa da clara para a escura da **organização**; sem logo da organização, a da instalação | Sim: o swap é CSS, pela classe `dark` do `<html>` que o `theme.js` fixa no load |
| Topo do `/app/{organização}`, composição **ligada**: par `fi-logo-light`/`fi-logo-dark` dentro de `.kit-cabecalho` | Blade (`cabecalho-do-painel`) | `/app/{slug}` | idem, ao lado do nome do projeto e da organização | Sim |
| Tela de bloqueio (controle: **não muda**) | Filament (`TelaBloqueio`, `SimplePage`) | `/app/screen/lock` | o par da organização da sessão troca com o tema, como hoje | Sim |
| Topo do `/admin` e do `/infra` (controle **negativo**: nunca mostram logo de organização) | Filament | `/admin`, `/infra` | a marca é a da instalação, com ou sem organização esquecida no manager | Sim |

**Gate de CT-B**: o swap e a visibilidade da imagem por tema só o navegador prova (`getComputedStyle`, classe `dark` no load). O `05` existe: cobre o topo do `/app/{organização}` nas duas formas (marca simples e composição). O HTML do par (as duas `<img>` com as URLs certas) é costura de teste de requisição em `tests/Tenancy`.

## Variáveis de Ambiente

Nenhuma nova. As existentes (`KIT_UNIFICA_LOGO_MARCA`, `KIT_CABECALHO_*`) continuam semeando as configurações.

## Eventos / Listeners / Observers

Nenhum.

## Jobs / Queues

Nenhum.

## Modelo de Execução

| Pergunta | Resposta |
|---|---|
| Quantos requests a tela custa? | um por navegação **e um por update Livewire de `Topbar`/`Sidebar`**: a marca é desenhada por esses dois componentes e redesenhada em update (`refresh-topbar`, `refresh-sidebar`), com a organização presente porque `IdentifyTenant` é middleware persistente. Cada update é um request com a sua resolução |
| O que é adiado, e por qual gatilho? | nada |
| O que é memoizado **por request**? | uma entrada por `Request` (segmentos e par de logos juntos, D4). **Não alcança** outro request: cada navegação e cada update refazem a resolução, com os `exists()` de disco |
| O que é cacheado **entre** requests? | nada. A classe `IdentidadeDoKit` se declara sem cache de propósito (`ponytail:` no docblock); disco local, um `stat` por `exists()` |
| Custo do caminho principal | organização com par e marca separada: 2 `exists()` da organização e, nas quedas, até 2 da instalação, **uma vez por request** (hoje: 4 `exists()` da instalação por página). Zero query SQL a mais: a organização já está carregada em `Filament::getTenant()` |

## Impacto em Features Existentes

- **`logo-dark-mode`** (ancestral): a tela de bloqueio troca a implementação e mantém a saída, sem exceção. Regressão: `LogoDarkModeTest`, `tests/Browser/LogoDarkModeTest.php`.
- **`cabecalho-do-painel`** (ancestral): a P-12 deixa de valer **no `/app` com organização aberta**; vale no `/admin`, no `/infra`, no `/app` sem organização e nas telas de autenticação. O CT-07 de `CabecalhoDoPainelTenancyTest` é substituído; o contrato RQ-06 ("com tudo desligado renderiza o que renderizava") ganha a ressalva "para quem tem organização com logo própria", no docblock da classe e no CHANGELOG.
- **Projeto instalado com organizações que já enviaram logo** (era "só para a tela de bloqueio"): o próximo update muda a marca do topo do `/app/{slug}` delas. É a consequência pedida pelo Adendo 1; vai no CHANGELOG, em `### Corrigido`, com o caminho de volta (apagar a logo da organização).
- **Testes de arquitetura** (`tests/Kit/ArquiteturaDoCodigoTest.php`): `IdentidadeDoKit` passa a importar `App\Models\Tenant`; `CabecalhoDoPainel` já importa `App\Models\User`, então o precedente existe, e a regressão confirma.

## Rollback

`git revert` da branch; o parâmetro novo da factory é opcional e posicional ao fim, então nenhum chamador quebra. Sem migration e sem feature flag: quem quiser a logo da instalação no topo apaga a logo da organização.

## Dependências

Nenhuma nova (Composer ou NPM).

## Riscos

- **Logo da organização com fundo opaco ou texto escuro** passa a aparecer no topo, onde antes aparecia a da instalação. Com a marca separada e sem escura da organização, entra a escura da instalação, que pode destoar da clara da organização. Mitigação: o formulário já avisa "Em branco, usa a da instalação"; a documentação recomenda enviar o par.
- **Organização só com a escura** (L8): o topo mostra a clara da instalação com a escura da organização, como a tela de bloqueio já mostra; com a instalação sem clara, ver a última linha do Mapeamento. Mitigação: linhas próprias na tabela de decisão do passo 4.
- **Estado de teste**: uma organização esquecida no `FilamentManager` apareceria no topo do `/admin`; a guarda de painel (ADR-03) e o caso do passo 4 a cobrem.
- **Citações de linha**: o passo 2 desloca linhas de `CabecalhoDoPainel.php` citadas em outras wikis; as antigas são registros datados e não se editam. `CitacoesDeCodigoTest` entra na regressão.

## Channel de Log da Feature

`config/logging.php` já tem `tenancy` (`config/logging.php:'tenancy':123`) e `configuracoes` (`config/logging.php:'configuracoes':156`). Nenhum channel novo.

## Logs

Sem log novo; o `warning` existente de `CabecalhoDoPainel::resolverSegmentos()` ("Composição pedida sem nenhum segmento resolvido, usando a marca padrão", channel `configuracoes`) continua. `Tenant::urlDaLogo()` e `urlDaLogoEscura()` já degradam em silêncio e nenhum RQ pede log.

## Estrutura de Implementação

### 1. `IdentidadeDoKit::logosPara()` e a delegação da tela de bloqueio

> Skills: `laravel-best-practices`, `ponytail`

- **Path**: `app/Support/IdentidadeDoKit.php`, `app/Filament/Pages/Auth/TelaBloqueio.php`
- `IdentidadeDoKit::logosPara(?Tenant $organizacao): array{clara: ?string, escura: ?string}` estático, ao lado de `logo()`/`logoEscura()`, com docblock em prosa (aponta para a regra de `## Mapeamentos`) e `@return array{clara: ?string, escura: ?string}`. Corpo: a regra de `urlsDasLogos()` de hoje, movida: `clara = $organizacao?->urlDaLogo() ?? logo()`; `escura = unificaLogo() ? null : ($organizacao?->urlDaLogoEscura() ?? logoEscura())`.
- `TelaBloqueio::urlsDasLogos()` fica com o `once()` e devolve `IdentidadeDoKit::logosPara($this->organizacaoResolvida())`; a assinatura pública e o uso da partial (`method_exists`) não mudam.
- **Pronto quando**: CT-16 de `LogoDarkModeTest` verde **sem alteração**; `logosPara(null)` devolve `['clara' => logo(), 'escura' => logoEscura()]`.
- **Atende**: RQ-04, RQ-05, P-01, P-03, P-04
- **Logs**: nenhum.

### 2. `CabecalhoDoPainel`: marca simples e composição com a organização aberta

> Skills: `laravel-best-practices`, `ponytail`

- **Path**: `app/Support/CabecalhoDoPainel.php`
- Helper privado `organizacaoAberta(): ?Tenant`, usado nos **dois** pontos que leem a organização, o nome do segmento do painel e as logos: devolve `Filament::getTenant()` quando `Filament::getCurrentPanel()?->hasTenancy() === true` e o objeto é `Tenant`; senão `null`. **Nunca** `Paineis::correnteOuPadrao()`: sem painel corrente ele cai no `app`, que tem tenancy. É a exceção da rule `app.md` (quem não pode cair no painel padrão lê `getCurrentPanel()`), com precedente na Closure da cor da organização (`app/Providers/Filament/AppPanelProvider.php:getCurrentPanel():201`).
- Memo único (D4): a entrada do `WeakMap` por `Request` passa a guardar `segmentos` e `logos`; o par é `IdentidadeDoKit::logosPara(self::organizacaoAberta())`. `segmentos()` e `marca()`/`marcaEscura()` leem a mesma entrada; sem `Request` ligada, resolve sem memo, como hoje.
- `marca()` devolve `logos['clara']` no ramo sem composição; `marcaEscura()` devolve `logos['escura']` **sem composição** e continua `null` com a composição ativa (o par já vai dentro dela, exatamente um `fi-logo-dark`); `resolverSegmentos()` usa o par (`$logoClara = $exibeLogo ? logos['clara'] : null`, `$logoEscura = $logoClara !== null ? logos['escura'] : null`).
- Docblocks: o da classe diz que "com tudo desligado" a marca é a da instalação **para quem não tem organização aberta com logo própria**, e que `logosPara()` é a fonte; o de `resolverSegmentos()` troca "P-07, P-12" por "P-07; a P-12 vale fora do `/app` com organização aberta, ver `wikis/specs/fix/logo-dark-do-tenant/`".
- `vendor/bin/pint --dirty`.
- **Pronto quando**: no `/app/{organização}` com logo própria, nas duas formas (composição ligada e desligada), o HTML tem o par da organização; no `/admin`, `/infra` e `/app` sem organização, é idêntico ao de hoje.
- **Atende**: RQ-04, RQ-05, P-02
- **Logs**: o `warning` de composição vazia continua.

### 3. Factory e textos que a mudança desmente

> Skills: `laravel-best-practices`, `filament-development`

- **Path**: `database/factories/TenantFactory.php`, `app/Filament/Admin/Resources/Tenants/Schemas/TenantForm.php`, `app/Models/Tenant.php`
- `TenantFactory::comIdentidadeVisual()` ganha `?string $logoEscura = null` no fim da assinatura (D3) e grava `'logo_dark' => $logoEscura`; o docblock lista a nova chave.
- `TenantForm`: o `helperText` do upload `logo` passa a dizer "Exibida no topo do painel de negócio e na tela de bloqueio de sessão. Em branco, usa a da instalação (ou o nome da aplicação, sem ela)." O do `logo_dark` ("Em branco, usa a da instalação") continua verdadeiro.
- `Tenant::urlDaLogoEscura()`: só o docblock (`app/Models/Tenant.php:urlDaLogoEscura():215`), que deixa de dizer que apenas a tela de bloqueio a consulta.
- Depois de editar `app/Filament`: `vendor/bin/filacheck --fix` e `vendor/bin/pint --dirty`.
- **Pronto quando**: `Tenant::factory()->comIdentidadeVisual('#7c3aed', 'a.png', null, 'b.png')->create()` grava `logo_dark`; `filacheck` silencioso.
- **Atende**: RQ-02, RQ-05
- **Logs**: nenhum.

### 4. Testes de backend, nos arquivos que já existem

> Skills: `pest-testing`, `testing-best-practices`

- **Path**: `tests/Kit/LogoDarkModeTest.php`, `tests/Tenancy/CabecalhoDoPainelTenancyTest.php`
- O Gherkin e os IDs vêm do `04` (step 7, `feature-test-design`); os IDs desta wiki são numerados para não colidir com os que já existem em cada arquivo (o `ids-ct.sh` confere o arquivo). O inventário previsto:
  - **A regra `logosPara()`**, em `tests/Kit/LogoDarkModeTest.php`, como CT **novo** desta wiki (o CT-16 não é editado): tabela de decisão no molde de `tests/Kit/LogoDarkModeTest.php:'[CT-16]':319`, com as linhas de `## Mapeamentos`; `null` devolve o par da instalação; organização só com a escura devolve a clara da instalação com a escura da organização; organização só com a escura e instalação sem clara devolve `clara = null` e a escura da organização.
  - **Requisição ao `/app/{slug}`**, em `tests/Tenancy/CabecalhoDoPainelTenancyTest.php` (usa `tests/Pest.php:fronteiraDeRequest():777` e `tests/Pest.php:regiaoDoHeader():1739`): (a) marca simples com organização com par e marca separada: `fi-logo-light` com a clara da organização e `fi-logo-dark` com a escura; (b) composição ligada: o par da organização dentro de `kit-cabecalho`; (c) as quedas por dataset; (d) marca unificada ignora a escura da organização; (e) `/admin` e `/infra` nunca mostram logo de organização, inclusive com uma organização obsoleta no `FilamentManager` (`tests/Pest.php:noPainelDa():427` antes de visitar o `/admin`); (f) o mesmo usuário em duas organizações vê a de cada uma (molde do CT-07, que abre `/app/globex` e depois `/app/acme` com `fronteiraDeRequest()`); (g) **update Livewire da `Topbar`** mantém o par da organização: `Livewire::test(\Filament\Livewire\Topbar::class)` com o painel `app` corrente e `Filament::setTenant($org)` (D5; o POST real ao endpoint de update fica fora do arnês); (h) composição ligada + marca separada + par da organização ⇒ **exatamente um** `fi-logo-dark`, dentro de `kit-cabecalho`; (i) organização só com a escura e instalação sem clara: a composição descarta a escura (depende da clara) e a marca simples mostra o nome em texto no claro e a `<img>` escura no escuro; (j) login do `/app` sem organização continua com a da instalação.
  - **Todo caso de ausência traz o controle positivo no mesmo caso**: antes de afirmar que a logo da organização **não** aparece (e) ou que não há `fi-logo-dark` (d), o caso afirma a presença da clara esperada nas `<img class="fi-logo">` (`imagensDaMarcaDaTenancia()`) ou na região `kit-cabecalho`. O oráculo de ausência usa `assertStringNotContainsString`, nunca `not->toContain($x, $msg)` (rule `testes.md`).
  - **CT-07 substituído**: o caso invertido ganha ID desta wiki e afirma a logo da **organização** na composição; a aprovação da substituição é a resposta do solicitante no Adendo 1 (reversão da P-12 no `/app`). O docblock do arquivo passa a citar as duas wikis. O CT-27 não muda.
  - O helper `gravarLogoDaInstalacaoDaTenancia()` ganha `bool $unifica = true`, para os casos de marca separada; continua no arquivo.
- Rodar: `pest tests/Kit/LogoDarkModeTest.php` e `pest tests/Tenancy/CabecalhoDoPainelTenancyTest.php`.
- **Pronto quando**: os dois arquivos verdes; o caso (a) e o CT-07 substituído ficam vermelhos com o passo 2 revertido (falsificabilidade).
- **Atende**: RQ-04, RQ-05, P-01, P-03, P-04
- **Logs**: nenhum.

### 5. Teste de navegador do swap no topo do `/app`

> Skills: `pest-testing`

- **Path**: `tests/BrowserTenancy/IdentidadeVisualTest.php` (existe; é o arquivo da identidade da organização no navegador)
- Dois cenários novos no arquivo, molde de `tests/Browser/LogoDarkModeTest.php:'[CT-B01]':26` (uma visita por tema, `inLightMode()` e `inDarkMode()` no load, `getComputedStyle`) e do cenário da tela de bloqueio já presente nele (disk `public` **real**, usuário da organização por `usuarioComPapel()` + `$usuario->tenants()->attach()`): (B1) marca simples: no claro a `fi-logo-light` visível com `src` da clara da organização e a `fi-logo-dark` oculta; no escuro o inverso, com a escura da organização; (B2) composição ligada: o mesmo dentro de `.kit-cabecalho`, molde de `tests/Browser/CabecalhoDoPainelTest.php:'[CT-B02]':85`. O `afterEach` do arquivo passa a apagar também os arquivos novos.
- Regras de `testes-browser.md`: o `beforeEach` só semeia; o cenário arranja o painel e **aquece pelo kernel** com um `$this->get('/app/{slug}')` antes do `visit()` (ver `tests/BrowserTenancy/CapturaDeArteTest.php:arranjarPainelApp():92`); nunca `waitForEvent('networkidle')`; sem `--parallel`.
- Sem captura de arte nova: nenhuma linha em `KitArte::IMAGENS`.
- **Pronto quando**: os dois cenários verdes em série, vermelhos com o passo 2 revertido.
- **Atende**: RQ-05
- **Logs**: nenhum.

### 6. Documentação (pt e en), CHANGELOG e README

> Skills: `ponytail`

- **Path**: `docs/pt/recursos/configuracoes-do-kit.md`, `docs/en/recursos/configuracoes-do-kit.md`, `CHANGELOG.md`, `README.md`, `README.en.md`
- **Onde** (achado por `grep -rn "logo" docs/pt --include=*.md -l`): a seção "Cabeçalho dos painéis: o que mostrar no topo" (pt) e "Panel header: what to show at the top" (en), onde está "No painel do negócio a logo da composição é sempre a da **instalação** — a da organização segue aparecendo só na tela de bloqueio"; e a seção da logo por tema, onde está "Organização sem variante escura cai para a da instalação ... A tela de bloqueio usa o mesmo par". A seção "Isto não é o settings de uma organização" já afirma que a logo da organização "vence a do kit dentro de `/app/{slug}`" e passa a ser verdadeira (sem edição).
- **Parágrafo proposto (pt)**, no lugar da frase da composição: "No painel do negócio, com uma organização aberta, a logo da composição (e a marca simples, com a composição desligada) é a da **organização** — a clara e, com a marca separada, a escura, trocadas pelo navegador com o tema —, e cai para a da instalação quando a organização não tem logo. Nos painéis de administração e infraestrutura, e nas telas de autenticação, a logo é a da instalação. A tela de bloqueio usa a mesma regra."; na seção da logo por tema, trocar "A tela de bloqueio usa o mesmo par: logo da **organização**, se houver; da instalação, se não." por "O topo do painel do negócio e a tela de bloqueio usam o mesmo par: logo da **organização**, se houver; da instalação, se não."
- **Equivalente (en)**: "In the business panel, with an open organisation, the composition's logo (and the plain brand, with the composition off) is the **organisation's** — the light one and, with a split brand, the dark one, swapped by the browser with the theme — and falls back to the installation's when the organisation has none. In the administration and infrastructure panels, and on authentication screens, it is the installation's. The lock screen follows the same rule." e "The top of the business panel and the lock screen use the same pair: the **organisation's** logo if there is one, the installation's if not."
- **CHANGELOG**: `## [Unreleased]` já existe vazio no topo; entra `### Corrigido` com: a logo da organização, clara e escura, passa a trocar com o tema no topo do `/app/{organização}` (marca simples e composição do cabeçalho), caindo para a da instalação; reverte a P-12 do `cabecalho-do-painel` só para o `/app` com organização aberta; a regra de queda vira `IdentidadeDoKit::logosPara()`; projeto com organizações que já enviaram logo vê a marca do topo delas mudar no update (apagar a logo da organização devolve a da instalação). Cita a wiki. Sem item de release/tag (fora de escopo).
- **README e README.en**: a linha "Features especificadas" sobe uma unidade: o README diz **76** e `find wikis/specs -name 00-requisito.md | wc -l` conta **77** nesta worktree (com o `00` desta feature). O guarda é `tests/Kit/SiteDeDocumentacaoTest.php:$especificacoes:1002`. O badge de "casos de teste" (`grep -rhoE '^(it|test)\(' tests | wc -l`) é recontado depois do passo 5. **Aviso**: a outra feature do mesmo pedido (rodapé) também soma uma especificação em outra branch; quem mergear por último reconcilia o número.
- **Pronto quando**: `pest tests/Kit/SiteDeDocumentacaoTest.php` e `pest tests/Kit/RedeDeDocumentacaoTest.php` verdes; as duas docs com o mesmo conteúdo.
- **Atende**: RQ-01, RQ-02, RQ-05
- **Logs**: nenhum.

### 7. Índice das wikis

> Skills: `ponytail`

- **Path**: `wikis/specs/INDEX.md`
- Não se edita à mão: `bash .claude/skills/feature-tickets/scripts/indice.sh` (na raiz, grava e imprime os paths) depois de o `03` receber o **Estado** final.
- **Pronto quando**: o `INDEX.md` tem a linha de `fix/logo-dark-do-tenant`.
- **Atende**: RQ-05 (registro da entrega)
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
> **Caveman ativo em modo `ultra`** (padrão) na comunicação agent e usuário.
> Arquivos wiki (00-06) são boundary do Caveman: escrever em prosa normal.
> Código, commits e PRs também são boundary do Caveman.
>
> **Baseline antes do primeiro commit**: `main` em `b347fcc` (v0.45.1), verde no CI da release anterior; nenhuma falha pré-existente conhecida. Rodar a suíte afetada (os arquivos da regressão) em `main` e listar por nome qualquer falha antes de commitar.

## Mapeamentos

### A regra de resolução do par (D1, D2): a única descrição

Por variante e **independente** (a escura não olha a clara), a mesma de `TelaBloqueio::urlsDasLogos()` de hoje: `clara` = clara da organização, senão clara da instalação; `escura` = `null` com a marca unificada, senão escura da organização, senão escura da instalação.

| Marca | Organização aberta | clara | escura |
|---|---|---|---|
| qualquer | nenhuma (`null`) | `logo()` | `logoEscura()` (`null` se unificada) |
| qualquer | sem logo nenhuma (colunas em branco ou arquivos ausentes) | `logo()` | `logoEscura()` (`null` se unificada) |
| unificada | com clara | clara da organização | `null` |
| separada | com clara e com escura | clara da organização | escura da organização |
| separada | com clara, sem escura (ou escura ausente do disco) | clara da organização | `logoEscura()` (da instalação) |
| separada | sem clara, com escura (só dado: o formulário não produz) | `logo()` (da instalação) | escura da organização |
| separada | só com a escura, e a instalação sem clara | `null` | escura da organização: a composição **descarta** a escura (depende da clara); a marca simples mostra o nome em texto no claro e a `<img>` escura no escuro |

## Testes

> Ver `04-casos-de-teste.md` para a especificação completa dos cenários de backend e `05-casos-de-teste-browser.md` para os de navegador (a feature tem superfície de UI, ver o gate de CT-B).

## Rastreabilidade

| RQ / P | Passo(s) | Casos de teste previstos (os IDs saem do `04` e do `05`) |
|---|---|---|
| RQ-01 | 6 | análise: sem teste próprio; a tabela de `## Revisão da feature` é o entregável; a documentação a fixa |
| RQ-02 | 1, 2, 3, 6 | factory aceita a escura (usada pelos casos do passo 4); `filacheck`; L4/L5 são texto, sem teste de comportamento |
| RQ-04 | 1, 2, 4, 5 | CT-16 e CT-B01 de `LogoDarkModeTest` verdes sem alteração; `CabecalhoDoPainelTest` (marca de fábrica sem tenancy); `logosPara(null)`; `/admin` e `/infra` sem logo de organização, com controle positivo |
| RQ-05 | 1 a 7 | marca simples e composição com o par da organização; quedas; unificada; duas organizações; update Livewire da `Topbar`; um só `fi-logo-dark` na composição; só a escura com instalação sem clara; navegador B1 e B2; CT-07 substituído |
| P-01 | 1, 4 | CT-16 sem alteração; linha de decisão com só a escura da organização |
| P-02 | 2 | `CabecalhoDoPainelTest`/`CabecalhoDoPainelTelaTest` (composição sem tenancy não muda); CT-27 |
| P-03 | 1, 4 | tabela de decisão de `logosPara()` |
| P-04 | 1, 4 | marca unificada nas duas formas (marca simples e composição) |

## Verificação Final
- [ ] `/ponytail:ponytail-review` no diff (validar contra over-engineering)
- [ ] `vendor/bin/pint --dirty`
- [ ] `vendor/bin/filacheck --fix` (o passo 3 toca `app/Filament`)
- [ ] `vendor/bin/pest tests/Kit/LogoDarkModeTest.php --compact` e `vendor/bin/pest tests/Tenancy/CabecalhoDoPainelTenancyTest.php --compact` (CTs de backend)
- [ ] `vendor/bin/pest tests/BrowserTenancy/IdentidadeVisualTest.php` (CT-B, em série, nunca `--parallel`)
- [ ] Regressão: `LogoDarkModeTest`, `CabecalhoDoPainelTest`, `CabecalhoDoPainelTelaTest`, `CabecalhoDoPainelTenancyTest`, `IdentidadeVisualTenancyTest`, `ArquiteturaDoCodigoTest`, `CitacoesDeCodigoTest`, `SiteDeDocumentacaoTest`, `RedeDeDocumentacaoTest`, e no navegador `tests/Browser/LogoDarkModeTest.php`, `tests/Browser/CabecalhoDoPainelTest.php`, `tests/BrowserTenancy/IdentidadeVisualTest.php`
- [ ] Suíte afetada (ou `--group=kit`) contra a **baseline** de `main`: nada além das falhas pré-existentes por nome
- [ ] `pest --mutate --path=app/Support/IdentidadeDoKit.php` (via `pestw.cmd`, sem `--filter`, `--no-tia`): score, duração e sobreviventes listados; mutantes manuais: remover a guarda de painel de `organizacaoAberta()` e inverter a ordem das quedas
- [ ] **`/code-review high main...HEAD` + passe de eixos (step 9)**, antes da reconciliação
- [ ] Docs pt/en, CHANGELOG e README reconciliados com o comportamento final

## Commits
- `:bug: fix(logo): a logo da organização (clara e escura) troca com o tema no topo do /app`
- `:white_check_mark: test(logo): topo do /app com logo da organização (backend e navegador)`
- `:memo: docs(logo): topo do /app com organização usa a logo dela (pt/en, CHANGELOG, README)`
- `:memo: wiki(logo): wiki da correção logo-dark-do-tenant`
