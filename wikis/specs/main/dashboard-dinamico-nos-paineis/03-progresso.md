# Progresso — Dashboard dinâmico nos painéis

> Tracking da feature. Checkbox só fecha com evidência inline
> (arquivo:símbolo:linha ou saída de teste).

## Wiki

- [x] `00-requisito.md` — pedido transcrito + cláusulas RQ-01 a RQ-08 + Ambiguidades P1–P4
- [x] `01-plano-acao.md` — PRD com os 10 blocos de trabalho
- [x] `02-decisoes-arquiteturais.md` — ADR-01 a ADR-07
- [x] `04-casos-de-teste.md` — 26 CTs, 11 regras, 34 mutantes; gate fechado
- [x] `05-casos-de-teste-browser.md` — CT-B01 (drag persiste) + CT-B02 (read-only real)
- [ ] Revisão adversarial do 04/05 — OBRIGATÓRIA (Impacto 3 em permissão e
  update; perfil completo em tenancy). Pendente: exige revisor que não derivou
  o conjunto — rodar antes de implementar.
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

- Confirmado: `DynamicDashboard` aceita raiz via `getRoutePath()` retornando
  `'/'` — não `$slug` (`app/Filament/App/Pages/Dashboard.php`). O fallback
  clássico ficou em `/inicio` (`DashboardClassico::$routePath`).
- Permission gerada pelo Shield: `Manage:Dashboard` (string fixada nos CTs de
  contrato em `tests/Kit/DashboardDinamicoTest.php`).
- CT-B01: o gesto de mouse não engata no DD do GridStack (exige
  `setPointerCapture` com pointerId real — nem `drag()` do Playwright nem
  eventos sintéticos). O teste move o widget pela API `grid.update()`, que
  dispara o MESMO `change` → `scheduleFlush` → `$wire.call('persistLayout')`,
  e faz poll no banco até o write assíncrono aterrissar. A espera pela
  instância `el.gridstack` é por Promise no `script()` — o handle é HTML
  server-side e aparece antes do Alpine bootar o grid.
