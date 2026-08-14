# Progresso — Identidade visual da organização

**Branch**: `feature/identidade-visual-da-organizacao`
**Início**: 2026-08-14
**Conclusão**: 2026-08-14
**Estado**: implementada. `--group=kit` 223/223 (759 asserções), `--testsuite=Browser` 16 cenários.

## 1. Migration — as duas colunas

- [x] `database/migrations/2026_08_14_000003_add_identidade_visual_to_tenants_table.php`
- [x] `cor_primaria` (string 7, nullable, after `ativo`) + `logo` (string, nullable)
- [x] `down()` com `dropColumn(['cor_primaria', 'logo'])`
- [x] Docblock explicando por que **uma** cor e não a paleta

## 2. Model `Tenant`

- [x] `'cor_primaria'` e `'logo'` em `$fillable`
- [x] Acessor `urlDaLogo()`, espelhando `User::getFilamentAvatarUrl()`
- [x] `@property` do docblock atualizado
- [x] Confirmado: **nenhum cast novo** — os dois são string

## 3. Factory, página `view` e permissions

- [x] `TenantFactory` — state `comIdentidadeVisual()`
- [x] `app/Filament/Admin/Resources/Tenants/Pages/ViewTenant.php`
- [x] `'view' => ViewTenant::route('/{record}')` em `getPages()`
- [x] `ViewAction::make()` no `TenantsTable` — **sem `->url()`**
- [x] `php artisan db:seed --class=ShieldPermissionsSeeder` para gerar `View:Tenant`

## 4. `TenantForm` — Section de identidade visual

- [x] `ColorPicker::make('cor_primaria')->hex()`
- [x] `FileUpload::make('logo')->image()->disk('public')->directory('organizacoes/logos')->visibility('public')`
- [x] Confirmado: **`->visibility('public')`**, não `->visible('public')` (o bug do Breezy)
- [x] Docblock marcando a Section como ponto de extensão (RQ-07)

## 5. Cor no painel `/app`

- [x] `FilamentColor::register(Closure)` no `bootUsing()` do `AppPanelProvider`
- [x] Confirmado: **não** usar `$panel->colors()` — ADR-02
- [x] Guarda dupla: painel `app` **e** tenant com `cor_primaria`
- [x] Log `[AppPanelProvider@bootUsing]` no channel `tenancy`
- [x] **Premissa verificada**: o cache de `ColorManager::$cachedColors` não congela a cor entre
      tenants no mesmo processo (CT-B02)

## 6. Sessão com o tenant corrente

- [x] Uma linha em `DefinirTenantDePermissoes::handle()`
- [x] Docblock da classe atualizado — ela passa a ter duas responsabilidades
- [x] Confirmado: nome mantido (renomear tocaria `AppPanelProvider` e testes de tenancy)

## 7. Logo na lock-screen

- [x] `getAuthDesignerConfig()` sobrescrito em `TelaBloqueio`
- [x] `mergeWith()` e **não** `setPageConfig()` — ADR-04
- [x] Guarda de painel: só `app`
- [x] Fallback para a mídia base, com log do motivo
- [x] `tests/Kit/BloqueioDeSessaoTest.php` continua verde

## 8. Testes

- [x] **CT-B01 antes de tocar no `TenantForm`** — responde a ambiguidade de RQ-09; a tabela de
      interpretação do resultado está em ADR-06. O passo que fazia só isso foi absorvido aqui pelo
      `/ponytail:ponytail-review`, porque não produzia código
- [x] **Infra**: pasta `tests/BrowserTenancy/` + bloco em `tests/Pest.php` + diretório na
      testsuite `Browser` do `phpunit.xml` (**não previsto no PRD original** — ver Desvios)
- [x] `tests/Kit/IdentidadeVisualTest.php` — CT-01, CT-02, CT-04, CT-07
- [x] `tests/Tenancy/IdentidadeVisualTenancyTest.php` — CT-03, CT-05, CT-06
- [x] `tests/BrowserTenancy/IdentidadeVisualTest.php` — CT-B01 a CT-B04
- [x] `tests/Browser/IdentidadeVisualPadraoTest.php` — CT-B05
- [x] **Regressão da ancestral rodada ANTES dos CT novos**: CT-11 (`PaineisTest`, matriz de
      permissões) e CT-12 (`BloqueioDeSessaoTest`) são os que mais provavelmente quebram

## Verificação Final

- [x] `/ponytail:ponytail-review` no diff
- [x] `vendor/bin/pint --dirty --format agent`
- [x] `vendor/bin/phpstan analyse`
- [x] `vendor/bin/pest --parallel --group=kit` — 214/214 + os CT novos
- [x] `vendor/bin/pest --testsuite=Browser` — em série
- [x] Regressão: `tests/Tenancy/*` e `tests/Kit/AdminDaOrganizacaoTest.php` verdes
- [x] Roteiro *Desenhado × Implementado* do `05` preenchido
- [ ] `feature-quality-gate` invocado, veredito registrado
- [ ] Candidatos a rule avaliados e apresentados
- [x] `git commit` — 5 commits: model, resource, cor, lock-screen, testes

## Blockers

Nenhum. As duas premissas do requisito que o código contradiz (RQ-06 e RQ-09) têm saída definida
em ADR-03 e ADR-06 — não bloqueiam.

## Desvios do Plano

<!-- Os dois primeiros nasceram da revisão pós-escrita; os demais, da implementação. -->

- **RQ-09 respondido: o `EditAction` JÁ navega.** CT-B01 verde na primeira execução — clicou em
  "Editar" e foi para `/admin/organizacoes/{uuid}/edit`, tela cheia, com a Section nova visível.
  Pela tabela de interpretação de ADR-06, isso significa que **a premissa do requisito não se
  aplica à tabela principal**, e que **nada foi corrigido no `EditAction`** — que era exatamente o
  risco que a ADR existia para evitar. O modal que o usuário viu é o do `UsersRelationManager`, e
  ali modal é a forma certa.
- **CT-06 contradizia ADR-03 da própria wiki, e a ADR venceu.** O CT desenhado pedia
  `session('tenant_corrente')` **nula** depois do `/admin`. A implementação não limpa — e ADR-03
  assume isso explicitamente, porque o middleware é `tenantMiddleware` do `/app` e não roda no
  `/admin`; o que protege o administrador é a **guarda de painel** da lock-screen, não a limpeza da
  chave. O CT foi reescrito para afirmar o que de fato protege: a chave sobrevive **e** a
  lock-screen do `/admin` continua sem exibir a logo da organização, com logo preenchida no
  cenário. **Teste que contradiz a ADR é teste errado, não implementação errada.**
- **`Log::partialMock()` não serve no painel `/app`.** É o desenho de `espiarAutenticacao()`
  (`tests/Pest.php`), mas o `/app` escreve no channel `ai` a cada request: a chamada cai no método
  real de um `LogManager` construído sem construtor e morre em *"Trying to access array offset on
  null"*, **mascarando o erro original**. Trocado por `TestHandler` no channel real — prova mais (o
  registro chega ao channel) e não mascara nada.
- **CT-07 virou dataset de três casos**, um por motivo (`sem_tenant`, `sem_logo`,
  `painel_sem_tenancy`), por causa do achado do Spotlight abaixo.
- **CT-B02 não consegue observar o risco de cache que ADR-02 nomeia.** O cache do `ColorManager`
  tem duas referências (container e `Facade::$resolvedInstance`), e nenhuma é invalidada por
  `register()`. Em produção morre com o request (FPM) ou é descartada pelo Octane, então a premissa
  da ADR **se sustenta** — mas o servidor do `pest-plugin-browser` é in-process, então sem uma
  fronteira explícita de request o cenário ficaria vermelho **por artefato do arnês, não por
  defeito**. O CT-B02 prova o que resta, e é o que importa: cada organização registra a sua cor,
  ela chega ao `--primary-500`, e as duas diferem.
- **Nenhum arquivo fora do escopo foi tocado.** `git status` durante a implementação confirma: só o
  que os passos previam, mais `tests/` e `phpunit.xml`.

- **A pasta `tests/BrowserTenancy/` não estava no PRD.** Apareceu ao escrever o `05`: os CT-B desta
  feature precisam de `kit.tenancy.enabled`, e `tests/Browser` está registrado com `TestCase`
  (single-tenant) em `tests/Pest.php`. O Pest **não permite dois TestCases na mesma pasta** — a
  mesma restrição que separou `tests/Tenancy` de `tests/Kit` (`tests/Pest.php:44-61`). Exige pasta
  nova, bloco novo no `Pest.php` **e** o diretório na testsuite `Browser` do `phpunit.xml`. Virou o
  primeiro item do passo 9.
- **Cortes do `/ponytail:ponytail-review` aplicados**, por decisão do usuário: o passo que só
  verificava o modal foi absorvido pelo passo de testes (não produzia código); CT-07 (log debug da
  cor aplicada) removido, porque nenhuma decisão depende dele; CT-B02 virou nota na tabela de
  interpretação do CT-B01 — documentar comportamento de vendor que não mudamos é trabalho de
  comentário, não de cenário de navegador; CT-B03 removido e sua única asserção própria absorvida
  pelo CT-03, que já abre a mesma tela por HTTP. 11 passos → 10, 8 CT → 7, 7 CT-B → 5, ~140 linhas.
- **`Storage::fake('public')` não serve nos CT-B de logo.** O navegador faz request HTTP real à URL
  da imagem, e disk fake não é servido por rota. Os CT-B que exibem logo usam
  `Storage::disk('public')->put()` real. Registrado nos pré-requisitos do `05`.

## Notas de Implementação

<!-- Descobertas da pesquisa, antes de a implementação começar. -->

- **`Panel::colors()` e `FilamentColor::register()` parecem equivalentes e não são.** Os dois aceitam
  `Closure` na assinatura, mas o primeiro a avalia em `Panel::boot()` (`Panel.php:95` →
  `HasColors.php:31`), disparado pelo **primeiro** middleware da pilha
  (`HasMiddleware.php:97-103`), antes do `IdentifyTenant`. `Filament::getTenant()` é sempre `null`
  ali. É falha silenciosa: o código roda sem erro e nunca aplica cor. ADR-02.
- **A rota da lock-screen não tem tenant, e o Filament não o guarda em sessão.** O pacote usa
  `->prefix($panel->getPath())` e só o middleware base
  (`vendor/marjose123/filament-lockscreen/routes/web.php`). E `FilamentManager::$tenant` é
  propriedade de instância (`FilamentManager.php:54`), preenchida só pelo `IdentifyTenant` a partir
  da rota. **A premissa de RQ-06 não se sustenta** — ADR-03.
- **O `EditAction` da tabela já navega**, porque o resource tem página `edit`
  (`Resources/Pages/Page.php:373-380`). O modal que o requisito menciona vem do
  `UsersRelationManager`, que não declara `$relatedResource` e por isso cai em modal sempre
  (`RelationManagers/RelationManager.php:396-398`). A confirmar por CT-B — ADR-06.
- **`Color::generatePalette()` deriva as 11 shades de um hex** (`Color.php:663`), e o `ColorManager`
  já a chama quando recebe string (`ColorManager.php:84-85`). Uma coluna basta.
- **O kit não tem nenhum `FileUpload` próprio.** O padrão a copiar é o do Breezy
  (`HasMyProfile.php:59-64`), **exceto a linha 64**: ela escreve `->visible('public')` em vez de
  `->visibility('public')`, e `visible()` espera `bool|Closure` — a visibility nunca é declarada.
  Funciona lá por acidente, porque o disk `public` já tem `'visibility' => 'public'`.
- **`FILESYSTEM_DISK` default é `local`**, que aponta para `storage/app/private` e não é servível por
  URL (`config/filesystems.php:16,35`). `->disk('public')` explícito é obrigatório. O `storage:link`
  já roda no install (`KitInstall.php:163`).

## Retrospectiva

<!-- Preencher ao fim. -->

- **Funcionou bem no planejamento**: usar a suíte de browser da wiki anterior como instrumento para
  resolver uma ambiguidade do requisito (RQ-09) em vez de discuti-la. O CT-B01 nasceu com a tabela
  de interpretação do resultado escrita **antes** de rodar — o que impede o viés de ler o resultado
  a favor da premissa.
- **Funcionou bem, e é o resultado que mais justifica a ADR**: escrever a tabela de interpretação do
  CT-B01 **antes** de rodá-lo. Ele voltou verde, o que significava "o requisito estava enganado" — e
  com a tabela escrita de antemão não houve espaço para ler o resultado a favor da premissa e sair
  "corrigindo" um `EditAction` que estava certo.
- **Funcionou bem**: ler o vendor antes de escrever o plano. As três descobertas que mais mudaram o
  desenho (`Panel::colors()` avaliar cedo, a rota da lock-screen sem tenant, o `EditAction` já
  navegar) são todas invisíveis na documentação e todas mudariam o plano depois de implementado.
- *a completar após a implementação*
