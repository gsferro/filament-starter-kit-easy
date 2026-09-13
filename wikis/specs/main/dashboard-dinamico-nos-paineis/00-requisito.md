# Requisito — Dashboard dinâmico nos painéis

> Documento IMUTÁVEL. É a transcrição do pedido e a fonte da verdade: todo o
> resto da wiki deriva daqui. Crescimento de escopo vira Adendo numerado no
> fim, nunca edição do texto original.

## Pedido (transcrito)

> crie uma wiki para adicionarmos o pacote de widgets dinamico como padrão nos
> paineis e para que possamos ter um settings no painel admin para ativar ou
> desativar conforme config
>
> - o projeto já tem instalado, apenas não esta implementando
> - analise o projeto: "D:\PROJECTS\GSFERRO\FM2S\UNIVERSIDADE-CORPORATIVA\universidade-corporativa" lá foi ativado no painel app
> - preveja os cenarios que envolvem ativar ou desativar o pacote dinamicamente
> - reveja o codigo fonte e garanta que tudo esteja entendido antes de implementar.
> - tenha atenção a questão de quando o tenanty estiver ativo e as permissões para poder gerenciar/manipular o plugin, principalmente nos paines que não seja "admin" e "infra"
> - A ideia é que possamos ter outros paineis, além dos 3, então use os paines que estão sendo usados no pacote "Paneil Switch" que da a possibilidade de troca dos que estão configurados no projeto que usa o kit
> - atenção a parte de update do kit, pois já temos projetos que estão rodando em cima

## Cláusulas derivadas (RQ)

- **RQ-01 — Pacote já instalado.** O `mddev31/filament-dynamic-dashboard` já é
  dependência do `composer.json` e suas duas migrations já estão publicadas em
  `database/migrations` (`2026_08_19_153814_create_dynamic_dashboard_tables.php`
  e `2026_08_19_153815_upgrade_dynamic_dashboard_tables_to_v2.php`). O trabalho
  é de IMPLEMENTAÇÃO, não de instalação.
- **RQ-02 — Padrão nos painéis.** O dashboard dinâmico passa a ser o dashboard
  oferecido pelo kit nos painéis — não uma página extra opcional.
- **RQ-03 — Interruptor no /admin.** Um setting em
  `/admin/configuracoes-da-aplicacao` liga e desliga a feature, valendo em tempo
  de execução (sem deploy, sem editar `.env`).
- **RQ-04 — Liga/desliga dinâmico.** Prever os cenários dos dois sentidos:
  ligar numa instalação que nunca teve dashboard dinâmico, desligar numa que já
  tem dashboards montados, e o que acontece com os dados gravados em cada caso.
- **RQ-05 — Tenancy.** Com `kit.tenancy.enabled` ligado, os dashboards são por
  organização: um tenant não vê nem herda o dashboard de outro.
- **RQ-06 — Permissão de gestão.** Ver e montar são coisas distintas: a
  permissão de GERENCIAR o dashboard (criar, editar, mover e remover widgets e
  dashboards) precisa ser controlável por papel, principalmente nos painéis que
  não são `/admin` nem `/infra` — o `/app` e qualquer painel que o projeto
  adicionar.
- **RQ-07 — Painéis além dos três.** O desenho não pode ser uma lista fechada
  `['admin', 'app', 'infra']`: painéis registrados pelo projeto que usa o kit
  (os mesmos que o Panel Switch já enxerga via `Filament::getPanels()`) precisam
  poder receber a feature.
- **RQ-08 — Update do kit.** Projetos já em produção sobre o kit não podem
  quebrar nem ganhar comportamento-surpresa ao atualizar: a feature nasce
  DESLIGADA para quem atualiza.

## Contexto de referência (levantamento já feito)

- Projeto de referência `universidade-corporativa`: a página
  `App\Filament\App\Pages\Dashboard` estende
  `MDDev\DynamicDashboard\Pages\DynamicDashboard`; o trait
  `App\Filament\App\Concerns\WidgetDinamico` (usa `HasEmptySettings` +
  `HasSizeDefaults` do pacote) torna widgets compatíveis; o service
  `App\Services\Dashboard\CriadorDeDashboardPadrao` monta o dashboard "Padrão"
  por tenant; `TenantObserver@created` o dispara; `DashboardPadraoSeeder` faz
  backfill; e o `AppServiceProvider::escoparDashboardsPorTenant()` aplica global
  scope + hook `creating` com `tenant_id` (coluna adicionada por migration
  própria, `nullable()->constrained('tenants')->nullOnDelete()`).
- O pacote NÃO é um Filament plugin: é um `PackageServiceProvider` do spatie
  (`vendor/mddev31/filament-dynamic-dashboard/src/FilamentDynamicDashboardServiceProvider.php`)
  que registra config, views, traduções, migrations, assets e o componente
  Livewire `DashboardManager`. A integração é por PÁGINA: estender
  `DynamicDashboard` e registrar a página no painel.
- A descoberta de widgets é por painel: `discoverDynamicWidgets()` percorre
  `filament()->getCurrentPanel()->getWidgets()` e filtra por
  `is_subclass_of(DynamicWidget::class)`
  (`vendor/.../src/Pages/DynamicDashboard.php:597-614`).
- O isolamento entre painéis é pela coluna `dashboards.page` (FQCN da página):
  `scopeAvailable()` filtra `page = static::class OR page IS NULL`
  (`vendor/.../src/Models/Dashboard.php:206-224`). Logo cada painel precisa da
  PRÓPRIA subclasse de página — uma classe única compartilhada vazaria
  dashboards entre painéis.
- `canEdit()` é `static` e devolve `true` por default
  (`vendor/.../src/Pages/DynamicDashboard.php:189-192`) — sem override, TODO
  usuário que abre a página gerencia tudo. É aqui que a permissão entra.
- O kit aplica o banco sobre a config no `boot()` do `KitServiceProvider`
  (`aplicarNaConfig()`), e os painéis são montados no `register()` dos
  PanelProviders — ANTES. Pela regra de `.ai/rules/settings.md`, a decisão
  liga/desliga precisa ser tomada POR REQUEST, não no registro do painel.

## Ambiguidades

Perguntas que o pedido não determina, com a premissa adotada (cenários
dependentes marcados `@premissa` no `04`):

- **P1 — A dinâmica pode ocupar a rota `/`?** RESOLVIDA: sim, via
  `protected static string $routePath = '/'` + `getRoutePath()` — o mesmo
  mecanismo de `Filament\Pages\Dashboard`
  (`vendor/filament/filament/src/Pages/Dashboard.php:21,39-42`).
- **P2 — Painel novo do projeto ganha a feature sozinho?** Não há como o kit
  registrar páginas num PanelProvider que ele não escreveu. Premissa (falha
  fechado): painel novo NÃO ganha a feature até registrar o par de páginas —
  a receita é documentada; o decisor e a lista `paineis` já o enxergam via
  `Filament::getPanels()`. Invariante: sem o registro, o painel responde o
  dashboard clássico, qualquer que seja a config.
- **P3 — Dashboard com `page = null` aparece em todo painel com a feature
  ligada?** É o desenho do pacote (`orWhereNull('page')`,
  `Models/Dashboard.php:221`). Premissa: aceito — dashboards criados pela UI
  sempre gravam a página corrente; `null` é o caso de dashboard "coringa".
- **P4 — O nome exato da permission de gestão.** Premissa: `Manage:Dashboard`,
  seguindo o formato que o Shield gerar a partir de `custom_permissions`; o
  teste de contrato fixa a string final.

## Adendo 1 — Revisão adversarial (achados que viram premissa)

Revisão independente do `04`/`05` contra o vendor e o kit. O que o conjunto
original não cobria ou assumia errado:

- **P5 — O gate do observer é por painel, não pela flag global.** "Feature
  desligada" no CT-15 tem duas leituras: flag off OU painel fora da lista. Com
  flag ligada e `paineis=['admin']`, um observer que consulta só a flag criaria
  dashboards órfãos do `/app`. Premissa: o decisor expõe
  `habilitadoPara(string $panelId)` e o observer/seeder consultam o painel
  dono do dashboard — coberto pelo CT-27.
- **P6 — O observer/seeder só semeia os painéis tenant-aware que o kit
  conhece.** A linha gravada precisa de `page = <FQCN da página dinâmica>`;
  painel registrado pelo projeto tem FQCN que o kit não conhece. Premissa:
  observer e seeder cobrem os painéis do kit com `hasTenancy()` (hoje: `app`);
  painel do projeto semeia o seu — a receita é documentada.
- **P7 — `use_spatie_permissions` é eixo de VISIBILIDADE, não de edição.**
  A flag troca o model para `DashboardWithRoles` (HasRoles) e liga o filtro de
  roles em `canDisplay()` (`DynamicDashboard.php:164-176`) + o seletor de
  roles no `DashboardManager`. Edição continua sendo `canEdit()`. Decisão:
  ligar a flag (paridade com a referência; dashboard sem roles fica visível a
  todos). Consequência de implementação: scope e hook de tenancy se aplicam a
  `DashboardModelHelper::model()` — a classe RESOLVIDA — porque global scope
  registrado em `Dashboard` não alcança `DashboardWithRoles`.
- **P8 — Contrato do vendor: 403 quando há dashboards e nenhum é exibível.**
  `initializeCurrentDashboard()` aborta 403 se `getAvailableDashboards()` tem
  linhas mas `canDisplay()` reprova todas (`DynamicDashboard.php:122-124`) —
  ex.: `panel_user` num tenant cujo único dashboard tem roles que o excluem.
  Premissa: aceito como contrato; o dashboard padrão semeado não tem roles,
  logo é visível a todos. Coberto pelo CT-28.
- **P9 — Sem tenancy, ligar a feature não semeia nada automaticamente.** O
  `DashboardPadraoSeeder` cobre os dois mundos (por tenant ou global
  `tenant_id = null`), mas roda por artisan — ligar o toggle não dispara
  backfill. Premissa: aceito; sem dashboard a página responde grade vazia
  (CT-26) e o gestor cria pelo "Manage".
- **P10 — `tenant_id` fora do `$fillable` já protege mass assignment**
  (`Models/Dashboard.php:44-54`), mas atribuição direta
  (`$d->tenant_id = X; $d->save()`) ainda forjaria. Premissa: o hook
  `creating` sobrescreve SEMPRE que há tenant corrente — não só quando nulo.
- **P11 — As 4 páginas novas entram em `shield.pages.exclude`.** O kit já
  exclui `Filament\Pages\Dashboard` (`config/filament-shield.php:327`) e o
  gate de página do Shield é opt-in via `HasPageShield` — sem o trait, a
  permission gerada não bloqueia nada, só polui a tela de papéis. Premissa:
  excluir as 4; "ver" é livre, "gerenciar" é `Manage:Dashboard`.
