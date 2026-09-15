# Progresso — Dashboard dinâmico nos painéis

> Tracking da feature. Checkbox só fecha com evidência inline
> (arquivo:símbolo:linha ou saída de teste).

## Wiki

- [x] `00-requisito.md` — pedido transcrito + cláusulas RQ-01 a RQ-08 + Ambiguidades P1–P4
- [x] `01-plano-acao.md` — PRD com os 10 blocos de trabalho
- [x] `02-decisoes-arquiteturais.md` — ADR-01 a ADR-07
- [x] `04-casos-de-teste.md` — 33 CTs, 16 regras, 46 mutantes; gate fechado
  (29 CTs e 13 regras na derivação; CT-30 a CT-33 e R14 a R16 nasceram da
  revisão de código, Adendo 2 do `00`)
- [x] `05-casos-de-teste-browser.md` — CT-B01 (drag persiste) + CT-B02 (read-only real)
- [x] Revisão adversarial do 04/05 — rodada antes de implementar; virou o
  Adendo 1 do `00-requisito.md` (P5 a P11), e os achados entraram no código
  (`config/filament-shield.php:330-336` P11, `TenantObserver` P5,
  `KitServiceProvider` P7/P10)
- [x] Revisão de código do diff completo — Adendo 2 do `00-requisito.md`
  (P12 a P15): quatro defeitos, todos em superfície do vendor que a derivação
  tratou como interna. Correções e CTs abaixo.
- [x] Auditoria `ponytail-review` da wiki — 1 achado aplicado:
  `DashboardClassico` era ×3 por painel; virou ×1 compartilhada
  (`App\Filament\Pages\DashboardClassico`), porque não grava `dashboards.page`
  e o kit já registra a mesma `Filament\Pages\Dashboard` nos três painéis.
  Revisão de citações vendor: mecanismo de rota corrigido de `$slug` para
  `$routePath` + `getRoutePath()` (`Pages/Dashboard.php:21,39-42`), e P1 do
  `00` resolvida com evidência.

## Implementação (concluída)

- [x] `config/kit.php` + `.env.example` — bloco `dashboard_dinamico`
  (`config/kit.php:379-385`, flag `KIT_DASHBOARD_DINAMICO` + lista
  `KIT_DASHBOARD_DINAMICO_PAINEIS`)
- [x] `App\Support\DashboardDinamico` — decisor por request
  (`habilitado()` :36, `habilitadoPara()` :53, `paginaDinamicaDo()` :74)
- [x] `App\Filament\Concerns\WidgetDinamico` — trait compartilhado
  (`app/Filament/Concerns/WidgetDinamico.php`)
- [x] Páginas `Dashboard` + `DashboardClassico` nos três painéis
  (`app/Filament/{App,Admin,Infra}/Pages/Dashboard.php` +
  `app/Filament/Pages/DashboardClassico.php` compartilhada)
- [x] Providers dos três painéis — par sempre registrado, decisão por request
  (`AppPanelProvider.php:110-111`, `AdminPanelProvider.php:99-100`,
  `InfraPanelProvider.php:131-132`)
- [x] `KitServiceProvider` — scope de tenant + `TenantObserver`
  (`escoparDashboardsPorTenant()` :261, observer condicionado a
  `kit.tenancy.enabled` :289-291)
- [x] `CriadorDeDashboardPadrao` + `DashboardPadraoSeeder`
  (`app/Services/Dashboard/CriadorDeDashboardPadrao.php`,
  `database/seeders/DashboardPadraoSeeder.php`)
- [x] Migration `tenant_id` em `dashboards`
  (`database/migrations/2026_09_10_100000_add_tenant_id_to_dashboards_table.php`)
- [x] Settings: propriedades + mapa + migration de settings
  (`app/Settings/ConfiguracoesDoKit.php`)
- [x] Aba Kit da tela de configurações — toggle + seletor de painéis
  (`app/Filament/Admin/Pages/ConfiguracoesDoKit.php`)
- [x] `filament-shield.php` + `PapeisSeeder` — `Manage:Dashboard`
  (`config/filament-shield.php:417` custom permission; exclusão das 4 páginas
  de dashboard da geração :330-336; `PapeisSeeder.php:251` nos três painéis e
  subtração do `panel_user` :112)
- [x] Testes (Kit, Tenancy, Browser conforme 04/05) —
  `tests/Kit/DashboardDinamicoTest.php`, `tests/Tenancy/DashboardDinamicoTenancyTest.php`,
  `tests/Browser/DashboardDinamicoTest.php` (CT-B01/CT-B02)
- [x] `vendor/bin/pint --dirty` + `composer test:kit`

## Notas de execução

- ~~Confirmado: `DynamicDashboard` aceita raiz via `getRoutePath()` retornando
  `'/'`~~ *(alterado em 2026-09-15: o mecanismo funciona, mas a posse da rota
  foi invertida — ver ADR-02 e P16. A clássica herda `/` do
  `Filament\Pages\Dashboard` e a dinâmica cai no slug padrão `/dashboard`.)*
- Permission gerada pelo Shield: `Manage:Dashboard` (string fixada nos CTs de
  contrato em `tests/Kit/DashboardDinamicoTest.php`).
- CT-B01: o gesto de mouse não engata no DD do GridStack (exige
  `setPointerCapture` com pointerId real — nem `drag()` do Playwright nem
  eventos sintéticos). O teste move o widget pela API `grid.update()`, que
  dispara o MESMO `change` → `scheduleFlush` → `$wire.call('persistLayout')`,
  e faz poll no banco até o write assíncrono aterrissar. A espera pela
  instância `el.gridstack` é por Promise no `script()` — o handle é HTML
  server-side e aparece antes do Alpine bootar o grid.

## Correções pós-revisão de código

- [x] P12 — `DashboardDinamico::atende()` + `temDashboardExibivel()`
  (`app/Support/DashboardDinamico.php`), consumidos pelo `mount()` das três
  páginas dinâmicas e pelo `DashboardClassico::mount()`. CT-28 reescrito, CT-30
  novo (`tests/Tenancy/DashboardDinamicoTenancyTest.php`)
- [x] P13 — global scope `tenant` em `DashboardWidget` via `whereHas('dashboard')`
  (`app/Providers/KitServiceProvider.php`), CT-31
- [x] P14 — `#[Session] #[Locked] public ?int $currentDashboardId` nas três
  páginas (`app/Filament/{App,Admin,Infra}/Pages/Dashboard.php`), CT-32
- [x] P15 — scope de dashboards fecha (`1 = 0`) sem tenant em painel
  tenant-aware (`app/Providers/KitServiceProvider.php`), CT-33
- [x] CT-03 — o ciclo liga/desliga/religa, que estava no 04 sem teste desde a
  implementação (`tests/Kit/DashboardDinamicoTest.php`)
- [x] P16 — posse da rota invertida: `DashboardClassico` sem `$routePath`
  (herda `/`), as três dinâmicas sem `$routePath`/`getRoutePath()` (slug
  `dashboard-dinamico`; o slug `dashboard` fica com o clássico para preservar o
  NOME da rota histórica). Conciliações que a inversão exigiu:
  `HubDeInfraestrutura::getCards()` exclui as duas telas de entrada,
  `PermissoesDeTelasTest` isenta as quatro por desenho (P11),
  `TextoDoEnvTest` isenta `KIT_DASHBOARD_DINAMICO_PAINEIS` (lista vazia = todos,
  mesmo caso do login social), `KitInfoTest` de 49 para 51 propriedades,
  `InventarioDeTelasTest` recebe as três rotas novas
- [x] Falsificabilidade conferida: com `git stash` das mudanças de `app/`, os
  CTs 30, 31, 32 e 33 falham (o CT-03 passa — é guarda de regressão, não
  conserto). Com elas, `38 passed` nas duas suítes.

## Pendências conhecidas

- Documentação de usuário (`docs/pt`, `docs/en`) ainda não tem página da
  feature: `docs/*/recursos/configuracoes-do-kit.md` descreve a aba Kit sem o
  toggle e o `roteiro-de-features.md` não tem linha própria.
- `app/Filament/{App,Admin,Infra}/Pages/Dashboard.php` são a mesma
  implementação três vezes (a FQCN por painel é exigência do ADR-03, o corpo
  não). Trait compartilhada reduziria ~90 linhas.
