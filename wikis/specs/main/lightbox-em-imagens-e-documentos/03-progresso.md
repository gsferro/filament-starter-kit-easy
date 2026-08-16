# Progresso — Lightbox em imagens e documentos

> Plano: `01-plano-acao.md` · Requisito: `00-requisito.md`

## 1. Instalar o pacote e publicar os assets

- [x] `composer require solution-forest/filament-simplelightbox:"^1.0"`
- [x] `php artisan filament:assets`
- [x] Confirmar que `public/js/filament/solutionforest/filament-simplelightbox/` existe

## 2. Registrar o plugin nos três painéis

- [x] `AdminPanelProvider` — `SimpleLightBoxPlugin::make()` + comentário da armadilha do macro
- [x] `AppPanelProvider` — `SimpleLightBoxPlugin::make()`
- [x] `InfraPanelProvider` — `SimpleLightBoxPlugin::make()`

## 3. Coluna de avatar nas duas telas de Usuários

- [x] `app/Filament/Admin/Resources/Users/UserResource.php` — `ImageColumn::make('avatar_url')`
- [x] `app/Filament/App/Resources/Users/UserResource.php` — mesma coluna

## 4. Coluna de logo na tela de Organizações

- [x] `app/Filament/Admin/Resources/Tenants/Tables/TenantsTable.php` — `ImageColumn::make('logo')`

## 5. Testes

- [x] `04-casos-de-teste.md` gerado pela skill `feature-test-design`
- [x] `05-casos-de-teste-browser.md` gerado (a feature depende de JS)
- [x] Testes de componente escritos e verdes
- [ ] CT-B escritos e verdes

## 6. Documentação do kit

- [x] `wikis/pacotes.md` — entrada em "Já existe — não escreva de novo"
- [x] `wikis/receitas.md` — receita "Imagem ou documento em tabela"
- [x] `wikis/convencoes.md` — convenção + armadilha do macro
- [x] `wikis/README.md` — índice, se houver

## 7. README — dependência

- [x] Linha na tabela `### UI e produtividade`

## 8. Candidato a rule de projeto

- [x] Candidato avaliado nos 4 gates
- [x] Apresentado ao usuário
- [x] Gravado manualmente em `.ai/rules/filament.md` (Mídia em tabela) — `requirement-to-rule` indisponível nesta sessão

## Verificação Final

- [x] `/ponytail:ponytail-review` no diff
- [x] `vendor/bin/pint --dirty --format agent`
- [x] `composer types:check`
- [x] `vendor/bin/pest --group=kit --compact`
- [x] `composer test:browser`
- [x] Roteiro "Desenhado × Implementado" do `05-*-browser.md` preenchido
- [x] `git commit`

## Auditoria Pré-Implementação

### Revisão profunda (step 5) — premissas do plano contra o código real

| Premissa do plano | O código real diz | Correção aplicada na wiki |
|---|---|---|
| teste de componente com `Filament::setCurrentPanel('admin')` bastaria para o macro existir | `setCurrentPanel()` **só troca a propriedade** (`FilamentManager.php:885-892`). Quem chama `Panel::boot()` é `bootCurrentPanel()`, e o único chamador dele é o middleware `SetUpPanel` (`SetUpPanel.php:17`) — que teste de componente não atravessa | `04` corrigido: arranjo passa a exigir `Filament::bootCurrentPanel()`; CT-03 trocou a asserção sobre o macro por asserção sobre o plugin registrado no painel, porque macro é estático e vazaria entre as linhas do `Esquema` |
| o pacote depende de asset publicado por `filament:assets` | confirmado: `FilamentSimpleLightBoxServiceProvider::packageBooted()` registra `Js::make(...)` — e é o único asset. Não há CSS, não há dependência de tema | nenhuma; o PRD já estava certo |
| `Tenant::logoUrl()` trata arquivo ausente | confirmado (`Tenant.php:119-146`), e a `ImageColumn` **não** usa esse acessor | nenhuma; já registrado no ADR-05 |

### Auditoria Ponytail (step 6)

| # | Sugestão de corte | Aplicada? | Onde |
|---|---|---|---|
| 1 | `shrink:` CT-05 sozinho num arquivo novo de `tests/Tenancy` | **sim** | `04` — passa a ser acrescentado a um arquivo existente da suíte |
| 2 | `yagni:` plugin no `/infra`, que não tem mídia | **recusada** — ADR-02 mede o custo (um `<script>`) contra o modo de falha (`BadMethodCallException` na renderização, meses depois) |
| 3 | `delete:` a entrega inteira é 3 linhas de plugin + 3 colunas | **nada a cortar** — nenhuma classe nova, nenhuma abstração |

## Blockers

Nenhum.

## Desvios do Plano

| Passo | Desvio | Motivo |
|---|---|---|
| 5 — testes | CT-02 (logo) saiu de `tests/Kit` para `tests/Tenancy/LightboxDaOrganizacaoTest.php` | `TenantResource::canAccess()` devolve `config('kit.tenancy.enabled')`. Em single-tenant a página nem renderiza, e o caso falhava com "Invalid Livewire snapshot structure" — por um motivo que não era o dele |
| 5 — testes | CT-05 (`/app`) boota o painel **admin**, não o `app` | bootar o `app` morre em `BreezyCore.php:112` (`Call to a member function parameter() on null`): o boot dele monta a rota do "Meu perfil", que num painel com tenancy exige o parâmetro de organização que só um request real tem. Como macro é estático em `ImageColumn`, qualquer painel do kit serve para registrá-lo — e o CT-03 é quem prova que os três registram o plugin |
| Verificação | `composer types:check` exigiu uma exceção em `phpstan.neon` | ver Notas de Implementação |

## Notas de Implementação

### `ImageColumn` **já** verifica a existência do arquivo — o ADR-05 estava errado

`shouldCheckFileExistence()` é verdadeiro por padrão (`vendor/filament/tables/src/Columns/ImageColumn.php:208-220`): a coluna faz `Storage::exists()` por linha e devolve `null` quando o arquivo não existe, renderizando **célula vazia** — não imagem quebrada.

O ADR-05 afirmava o contrário e recusava o `->state(logoUrl())` justamente para "evitar" esse I/O. A decisão final não muda; o **motivo** mudou, e o ADR foi corrigido com a evidência.

Como apareceu: CT-01 falhou com `<img src="">` porque a fixture preenchia `avatar_url` sem criar o arquivo. A correção foi na fixture (`Storage::fake('public')` + `put()`), e ela está comentada no `beforeEach` do teste — sem isso o próximo a mexer repete o diagnóstico.

### `Filament::setCurrentPanel()` **não** boota o painel

Ele só troca a propriedade `$currentPanel` (`FilamentManager.php:885-892`). Quem chama `Panel::boot()` é `bootCurrentPanel()`, e o único chamador dele em todo o Filament é o middleware `SetUpPanel` (`SetUpPanel.php:17`) — que teste de componente Livewire **não** atravessa.

Como o macro `simpleLightbox()` é registrado no `boot()` do plugin, sem isso os casos morriam com `BadMethodCallException` no arranjo. Virou o helper `noPainelBootado()` em `tests/Pest.php` (helper usado por mais de um arquivo, regra de `.ai/rules/testes.md`).

### `deferLoading()` é global no kit

`ConfiguraFilamentGlobal` liga `->deferLoading()` em toda tabela: a resposta inicial do componente traz o cabeçalho e **nenhuma linha**. Todo caso de tabela precisa de `->loadTable()` (ou `->call('loadTable')`) antes de asserir sobre conteúdo. Já era o idiom de `tests/Tenancy/AdminDaOrganizacaoTest.php:131`.

### PHPStan não enxerga o macro, e o stub não resolve

`composer types:check` acusava `Call to an undefined method ImageColumn::simpleLightbox()` nas três colunas.

Tentado e **descartado com evidência**:

- **`stubFiles`**: PHPStan usa stub para **sobrescrever** assinatura de método existente, não para **acrescentar** método a classe de vendor. Sem efeito nenhum, verificado com `clear-result-cache`.
- **replicar as 5 linhas do macro** com a API pública da coluna: acopla o kit ao miolo do vendor e quebra calado no primeiro upgrade que mudar o gatilho.

Ficou uma exceção **estreita e documentada** em `phpstan.neon` (`message` + `paths`), com o raciocínio inteiro no comentário. A cobertura real deste ponto é o teste, que assere o gatilho no HTML — e que fica vermelho no dia em que o macro sumir, o que o PHPStan não faria aqui de qualquer jeito.

## Degradações declaradas

- **Boost MCP indisponível nesta sessão**: `search-docs` não estava conectado. A confirmação de API foi feita por leitura direta do código do vendor no repositório do pacote (`SimpleLightBoxPlugin.php`, `resources/js/index.js`) e pela documentação oficial do plugin. Onde a doc e o código divergem, vale o código — e é o código que está citado no PRD.

## Retrospectiva

<!-- O que funcionou bem no planejamento e o que faltou -->
