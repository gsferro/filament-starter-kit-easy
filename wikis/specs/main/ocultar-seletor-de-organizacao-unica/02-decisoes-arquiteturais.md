# Decisões Arquiteturais — ocultar-seletor-de-organizacao-unica

<!-- ADR só quando as três valem: difícil de reverter, surpreendente sem contexto, resultado de
     trade-off real. Falta uma → sem ADR; a decisão vira linha em ## Decisões de Desenho do 01. -->

Nenhuma decisão passou nos três portões. As cinco decisões da feature — `tenantMenu()` no lugar de `tenantSwitcher()`, contagem por `User::getTenants()`, classe `App\Support\SeletorDeOrganizacao` no lugar de Closure inline, `->visible()` no toggle sem tenancy, e log no channel `tenancy` — são reversíveis num diff de poucas linhas e estão em `## Decisões de Desenho` do `01`, cada uma com o portão que falta.

## Superfície Livewire

<!-- A feature não cria página, widget ou componente: adiciona um campo (Toggle) a uma página
     existente e uma Closure a um provider. A fronteira que o cliente alcança é a mesma de sempre —
     a tabela abaixo cobre o que muda. -->

| Ponto | Origem | Escopo do cliente | Fronteira |
|---|---|---|---|
| `Toggle::make('ocultar_seletor_unico')` em `ConfiguracoesDoKit` | código do projeto | estado booleano do formulário | gravado via `SettingsPage::save()` já existente; aceita só `bool` (o componente Toggle dehidrata booleano); sem usuário autenticado na página não há como alcançar — `View:ConfiguracoesDoKit` governa |
| `SeletorDeOrganizacao::visivel()` | código do projeto | nenhum — Closure avaliada no render do servidor | lê `config()` e `Filament::getUserTenants()`; o cliente não injeta nada |
