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

## Implementação (não iniciada — aguardando aprovação da wiki)

- [ ] `config/kit.php` + `.env.example` — bloco `dashboard_dinamico`
- [ ] `App\Support\DashboardDinamico` — decisor por request
- [ ] `App\Filament\Concerns\WidgetDinamico` — trait compartilhado
- [ ] Páginas `Dashboard` + `DashboardClassico` nos três painéis
- [ ] Providers dos três painéis — registro do par de páginas
- [ ] `KitServiceProvider` — scope de tenant + `TenantObserver`
- [ ] `CriadorDeDashboardPadrao` + `DashboardPadraoSeeder`
- [ ] Migration `tenant_id` em `dashboards`
- [ ] Settings: propriedades + mapa + migration de settings
- [ ] Aba Kit da tela de configurações — toggle + seletor de painéis
- [ ] `filament-shield.php` + `PapeisSeeder` — `Manage:Dashboard`
- [ ] Testes (Kit, Tenancy, Browser conforme 04/05)
- [ ] `vendor/bin/pint --dirty` + `composer test:kit`

## Notas de execução

- Pendente de confirmação na implementação: se `DynamicDashboard` aceita
  `$slug = '/'` (PRD §3.3, ADR-02). Se não, inverter o dono da raiz.
- Nome exato da permission sai do que o Shield gerar a partir de
  `custom_permissions` — o teste de contrato fixa a string.
