# Plano de Ação — Roadmap item 1: preferências de aparência por usuário

> Requisito: `00-requisito.md`

## Natureza da Wiki

- **Tipo**: nova *(alterado em 2026-11-18: QA-06 do quality gate, ciclo 1)*
- **Wiki ancestral**: `wikis/specs/feat/layout-compact/` (a densidade por instalação, que esta feature passa a ler por pessoa) e `wikis/specs/feat/paleta-do-filament-na-organizacao/` (a regra da cor)
- **Motivo**: a aparência é decisão de quem administra e vale para todo mundo; a feature deixa cada pessoa escolher, sob duas chaves
- **Toca infra compartilhada?**: sim — o render hook da densidade e a largura do menu dos três painéis passam a ler um decisor novo; o resultado só muda para quem escolheu algo

## Cobertura do Requisito

| RQ | Cláusula | Passo(s) que atende(m) | Observação |
|----|----------|------------------------|------------|
| RQ-01 | Densidade por pessoa | 1, 3, 4, 6 | — |
| RQ-02 | Fonte por pessoa | 1, 3, 4, 6 | — |
| RQ-03 | Cor por pessoa | 1, 3, 4, 6 | — |
| RQ-04 | Menu por pessoa | 1, 3, 4, 6 | — |
| RQ-05 | Sem escolha, vale a instalação | 3 | — |
| RQ-06 | Instalação ganha padrão de fonte e de menu | 2, 4, 5 | — |
| RQ-07 | Chave da instalação | 2, 3, 5, 6 | — |
| RQ-08 | Chave de cada organização | 1, 3, 7 | — |
| RQ-09 | Dentro da organização, só com as duas | 3 | — |
| RQ-10 | Usar é opcional | 6 | — |
| RQ-11 | Dentro do kit | todos | nenhuma dependência nova |
| RQ-12 | Documento de candidatas a pacote | 8 | — |
| RQ-13 | As preferências entram como candidata | 8 | — |
| RQ-14 | Revisão do código lista as demais | 8 | — |
| P-01 | Um conjunto por pessoa | 1, 3 | — |
| P-02 | Pessoa > organização > instalação | 3, 4 | — |
| P-03 | Chave da instalação nasce ligada | 2 | — |
| P-04 | Chave da organização nasce desligada | 1 | — |
| P-05 | Desligar não apaga | 3, 6 | — |
| P-06 | Seção "Aparência" no Meu perfil | 6 | — |
| P-07 | Cor só da lista fechada | 6 | — |
| P-08 | Quatro tamanhos de fonte | 2 | — |
| P-09 | Dois modos de menu | 2 | — |
| P-10 | Sem login, vale o padrão | 3 | — |
| P-11 | Interruptor no cadastro da organização | 7 | — |
| P-12 | Valor ilegível cai no padrão | 2, 3, 5, 6 | — |
| P-13 | Tela sem organização corrente: só a chave da instalação | 3 | — |
| P-14 | Documento em `wikis/` | 8 | — |
| P-15 | Chave desligada depois de a tela abrir: salvar é recusado com aviso | 6 | *(alterado em 2026-11-18: QA-02 do quality gate, ciclo 1)* |
| P-16 | Aviso sobre organizações nos três painéis | 6 | — |
| P-17 | Pré-visualização das telas de acesso no padrão | 3 | step 9, CR-01 |

## Objetivo

Deixar cada pessoa escolher densidade, tamanho da fonte, cor primária e modo do menu, guardados no
perfil, com a configuração da instalação como padrão. Uma chave da instalação libera a escolha; com
organizações, cada uma também precisa liberar. O que a pessoa escolheu nunca é apagado por uma chave
desligada, só ignorado.

## Contexto

As quatro superfícies já são governáveis por request:

- **densidade**: render hook `STYLES_BEFORE` em `app/Providers/KitServiceProvider.php:configureDensidadeDoLayout:742`, que lê `App\Support\DensidadeDoLayout::deConfig()`; a largura do menu sai do mesmo nível por `->sidebarWidth(fn …)` em `app/Providers/Filament/AdminPanelProvider.php:sidebarWidth:139`, `app/Providers/Filament/AppPanelProvider.php:sidebarWidth:151` e `app/Providers/Filament/InfraPanelProvider.php:sidebarWidth:161`
- **fonte**: não existe; o CSS do Filament usa `font-size:var(--text-sm)` (125 vezes), `--text-base`, `--text-xs` e outros, declarados uma vez em `@layer theme` — a mesma técnica da densidade (redeclarar fora de layer) alcança tudo
- **cor**: a da instalação vem de `->colors(fn …)`; a da organização é registrada no `bootUsing` do `/app` por `FilamentColor::register()` (`app/Providers/Filament/AppPanelProvider.php:register:205`), avaliada no render do `<head>` e registrada depois, por isso vence
- **menu**: `->sidebarCollapsibleOnDesktop()` fixo nos três painéis (`app/Providers/Filament/AdminPanelProvider.php:sidebarCollapsibleOnDesktop:125`, `app/Providers/Filament/AppPanelProvider.php:sidebarCollapsibleOnDesktop:137`, `app/Providers/Filament/InfraPanelProvider.php:sidebarCollapsibleOnDesktop:147`). `vendor/filament/filament/src/Panel/Concerns/HasSidebar.php:isSidebarFullyCollapsibleOnDesktop:83` e o irmão fazem `evaluate()` e são lidos no render do layout (`vendor/filament/filament/resources/views/components/layout/index.blade.php:isSidebarFullyCollapsibleOnDesktop:12`), então aceitam Closure por request

## Análise dos Arquivos Existentes

### `app/Models/User.php` e `app/Models/Tenant.php`
- `User` já guarda preferência pessoal fora do `$fillable` (o dock: `dock_atalhos`, `dock_layout`, escritos por `forceFill`, escondidos em `app/Models/User.php:$hidden:97`)
- `Tenant` tem o precedente do interruptor por organização (`app/Models/Tenant.php:registro_habilitado:104`, cast em `:135`), com default `false` na migration

### `app/Settings/ConfiguracoesDoKit.php` e a tela
- `densidade_do_layout` em `app/Settings/ConfiguracoesDoKit.php:$densidade_do_layout:227`, mapeada em `mapaDeConfiguracao()` (`:483`); campo na aba Aplicação (`app/Filament/Admin/Pages/ConfiguracoesDoKit.php:abaAplicacao:1028`), coagido ao abrir a tela em `mutateFormDataBeforeFill()` (`:195`) *(alterado em 2026-11-18: era `mutateFormDataBeforeSave()`, erro de leitura do step 3)*
- Toda seção nasce pelo helper `secao()` (`app/Filament/Admin/Pages/ConfiguracoesDoKit.php:secao:328`; `.ai/rules/pages.md`)

### Meu perfil
- A tela é a do Breezy com a classe do kit; componentes extras entram por `->myProfileComponents([...])` (`app/Providers/Filament/AdminPanelProvider.php:myProfileComponents:262`, `app/Providers/Filament/AppPanelProvider.php:myProfileComponents:373`, `app/Providers/Filament/InfraPanelProvider.php:myProfileComponents:291`) e são filtrados por `canView()` estático (`vendor/jeffgreco13/filament-breezy/src/Concerns/Plugin/HasMyProfile.php:getRegisteredMyProfileComponents:96`)
- Precedente no kit: `app/Livewire/DefinirSenhaPorEmail.php` estende `MyProfileComponent`
- `/app/meu-perfil` não tem organização no endereço **só sem tenancy**; com `KIT_TENANCY=true` a rota é `/app/{tenant}/meu-perfil` *(alterado em 2026-11-18: o `route:list` do step 3 foi rodado sem tenancy — CT-12)*

## Decisões de Desenho

| # | Decisão | Pergunta | Portão que falta | Quem decidiu, data |
|---|---|---|---|---|
| D1 | As escolhas da pessoa numa coluna JSON `aparencia` da `users`, fora do `$fillable`, escrita por `forceFill` e escondida na serialização — o precedente do dock, custo zero de consulta (o usuário já está carregado) | autorização existente ("prossiga até finalizar") | trade-off | sessão, 2026-11-18 |
| D2 | Um decisor só, `App\Support\AparenciaDoUsuario`, responde "a personalização vale aqui?" e "qual o valor efetivo de cada preferência"; os quatro consumidores leem dele | autorização existente | — | sessão, 2026-11-18 |
| D3 | Fonte por redeclaração das variáveis `--text-*` no mesmo render hook do `--spacing`, fora de layer, sem CSS por classe `fi-*` (ADR-03 da `layout-compact`) | autorização existente | — | sessão, 2026-11-18 |
| D4 | Dois enums novos com o molde da densidade (`opcoes()`, `coagir()`, `padrao()`): `TamanhoDaFonte` e `ModoDoMenu` | autorização existente | — | sessão, 2026-11-18 |
| D5 | A cor da pessoa é um `FilamentColor::register()` no `bootUsing` dos três painéis, registrado depois do da organização; devolve `[]` quando não vale, e a cor anterior sobrevive | autorização existente | — | sessão, 2026-11-18 |
| D6 | A densidade sai da seção "Comportamento dos painéis" e vai, com fonte, menu e a chave, para uma seção nova "Aparência dos painéis" da aba Aplicação | autorização existente | — | sessão, 2026-11-18 |
| D7 | Log no canal padrão da aplicação (sem canal novo) | convenção (`.ai/rules/config.md`: canal novo exige garantia no provider) | trade-off | sessão, 2026-11-18 |

## Autorização

- **Policies**: nenhuma. A seção vive dentro do Meu perfil, que já exige `View:MyProfilePage` (`App\Filament\Pages\MyProfilePage`); cada pessoa só grava a própria linha (o usuário autenticado, nunca um id vindo do cliente)
- **Interruptor**: o `canView()` do componente e o `salvar()` consultam a chave da instalação; desligada, o `salvar()` recusa (falha fechada)
- **Gates / Middleware / Guards**: nenhum

## Rotas

Nenhuma rota nova.

## Superfície de UI

| Tela / Componente | Tipo | Rota | Interação do usuário | Depende de JS? |
|---|---|---|---|---|
| Meu perfil — seção "Aparência" | Livewire (componente do Breezy) | `/admin/meu-perfil`, `/infra/meu-perfil` e `/app/meu-perfil` (com tenancy, `/app/{organização}/meu-perfil`) *(alterado em 2026-11-18: QA-02 do quality gate, ciclo 1)* | escolhe densidade, fonte, cor e menu (cada um com "Padrão") e salva | Não |
| Configurações da aplicação — aba Aplicação, seção "Aparência dos painéis" | Filament (página de settings) | `/admin/configuracoes-da-aplicacao?tab=aplicacao` | liga/desliga a personalização e escolhe densidade, fonte e menu padrão | Não |
| Organização — cadastro e edição | Filament (Resource) | `/admin/organizacoes/create`, `/admin/organizacoes/{record}/edit` | liga/desliga "Liberar a aparência pessoal" (só com a chave da instalação ligada) | Não |
| Painéis — layout | render hook + sidebar | toda tela autenticada dos três painéis | vê a densidade, fonte, cor e menu efetivos | Sim (o recolher do menu é Alpine do Filament) |

## Variáveis de Ambiente

- `KIT_APARENCIA_POR_USUARIO` (bool, default `true`) — semente da chave da instalação
- `KIT_TAMANHO_DA_FONTE` (`pequena`|`normal`|`grande`|`muito-grande`, default `normal`)
- `KIT_MODO_DO_MENU` (`icones`|`oculto`, default `icones`)
- As três comentadas no `.env.example`, ao lado de `KIT_DENSIDADE_DO_LAYOUT`

## Eventos / Listeners / Observers

Nenhum.

## Jobs / Queues

Nenhum.

## Modelo de Execução

| Pergunta | Resposta |
|---|---|
| Quantos requests a tela custa? | o Meu perfil: 1 + 1 por salvar; o layout: nenhum a mais |
| O que é adiado, e por qual gatilho? | nada |
| O que é memoizado por request? | nada — o decisor lê `config()`, o usuário autenticado e a organização corrente, já carregados |
| O que é cacheado entre requests? | nada |
| Custo do caminho principal | zero consulta a mais por tela: `users.aparencia` vem com o usuário da sessão e `tenants.aparencia_por_usuario` com a organização corrente |

## Impacto em Features Existentes

- Densidade (`DensidadeDoLayoutTest`): o render hook e a largura do menu passam pelo decisor; sem escolha pessoal, o resultado é idêntico
- Cor da organização (`IdentidadeVisualDaOrganizacao*`): continua vencendo a da instalação; só perde para a da pessoa onde a organização liberou
- Tela de configurações (`ConfiguracoesSecoesColapsaveisTest`, `ConfiguracoesDoKitTelaTest`): seção nova, campos novos; densidade muda de seção
- Inventários: mapa do settings (`PermissoesDeAcoesTest` não muda: o salvar é método do componente do perfil, não Action *(alterado em 2026-11-18: QA-09 do quality gate, ciclo 2)*), `SemanticaDoVazio…` (não se aplica: não é lista de painéis), âncora de `tests/Kit/KitInfoTest.php` (73 → 76 propriedades)

## Rollback

- Migrations: `users.aparencia` e `tenants.aparencia_por_usuario` com `down()`; settings `aparencia_por_usuario`, `tamanho_da_fonte` e `modo_do_menu` com `delete()` no `down()`
- Reverter o commit devolve densidade e cor da instalação/organização para todos

## Dependências

Nenhuma (RQ-11).

## Riscos

- Fonte redeclarada quebrar proporção de algum componente: as variáveis de altura de linha do Tailwind 4 são relativas ao tamanho, e o nível "Normal" não emite nada (como o confortável)
- Menu "Esconder tudo" sem jeito de reabrir: o Filament põe o botão no topo quando `isSidebarFullyCollapsibleOnDesktop()` é verdadeiro; o Playwright confere
- Cor da pessoa pintar outra organização: o decisor só considera a organização corrente do painel `/app`, e a chave dela

## Channel de Log da Feature

Canal padrão (`Log::info`/`Log::warning`), sem canal novo (D7).

## Estrutura de Implementação

### 1. Colunas na pessoa e na organização

> Skills: `laravel-best-practices`

- **Path**: `database/migrations/2026_11_18_100000_add_aparencia_to_users_table.php`, `database/migrations/2026_11_18_100001_add_aparencia_por_usuario_to_tenants_table.php`, `app/Models/User.php`, `app/Models/Tenant.php`
- `users.aparencia` JSON nulo; cast `array`; em `$hidden`; fora do `$fillable` (D1)
- `tenants.aparencia_por_usuario` boolean default `false` (P-04); no `$fillable` (decisão de quem administra, como `registro_habilitado`) e cast `boolean`
- **Atende**: RQ-01..RQ-04, RQ-08, P-01, P-04
- **Logs**: nenhum (schema)

### 2. Vocabulário e padrões da instalação

> Skills: `laravel-best-practices`

- **Path**: `app/Support/TamanhoDaFonte.php`, `app/Support/ModoDoMenu.php`, `app/Settings/ConfiguracoesDoKit.php`, `config/kit.php`, `database/settings/2026_11_18_100000_add_aparencia_por_usuario_to_kit_settings.php`, `.env.example`
- `TamanhoDaFonte` (`pequena`, `normal`, `grande`, `muito-grande`; P-08) com `declaracoes(): ?string` (as `--text-*` escaladas, já numa declaração; `null` no normal) *(alterado em 2026-11-18: QA-02, BP-03 do quality gate, ciclo 1)* e `ModoDoMenu` (`icones`, `oculto`; P-09), ambos com `opcoes()`, `coagir()` e `padrao()` (D4, P-12)
- Settings: `aparencia_por_usuario` (bool), `tamanho_da_fonte`, `modo_do_menu`, cada um nos três lugares (propriedade, `mapaDeConfiguracao()`, migration de settings — `.ai/rules/settings.md`)
- **Atende**: RQ-02, RQ-04, RQ-06, RQ-07, P-03, P-08, P-09, P-12
- **Logs**: nenhum

### 3. O decisor

> Skills: `laravel-best-practices`, `ponytail`

- **Path**: `app/Support/AparenciaDoUsuario.php`
- `liberada(): bool` — chave da instalação ligada, pessoa logada, a tela não é a pré-visualização das telas de acesso (P-17) e, se houver organização corrente no `/app`, a chave dela ligada (RQ-07, RQ-09, P-10, P-13) *(alterado em 2026-11-18: QA-02 do quality gate, ciclo 1)*
- `densidade()`, `tamanhoDaFonte()`, `modoDoMenu()` — a escolha da pessoa (coagida) quando `liberada()` e preenchida; senão a da instalação (RQ-05, P-02, P-05, P-12)
- `paletaDaPessoa(): array` — `CorPrimaria::resolver(null, $nome)` quando liberada e preenchida; senão `[]` (P-02, P-07)
- **Atende**: RQ-01..RQ-05, RQ-07..RQ-09, P-01, P-02, P-05, P-10, P-12, P-13
- **Logs**: nenhum (lido em toda tela; log aqui seria ruído por request)

### 4. Os quatro consumidores

> Skills: `filament-development`

- **Path**: `app/Providers/KitServiceProvider.php`, `app/Providers/Filament/{Admin,App,Infra}PanelProvider.php`
- Render hook: `--spacing` de `AparenciaDoUsuario::densidade()` e as `--text-*` de `tamanhoDaFonte()`, numa declaração só, nada quando os dois são o padrão do Filament (D3)
- `->sidebarWidth(fn …)` lê `AparenciaDoUsuario::densidade()`
- `->sidebarCollapsibleOnDesktop(fn … === Icones)` e `->sidebarFullyCollapsibleOnDesktop(fn … === Oculto)`
- `FilamentColor::register(fn (): array => AparenciaDoUsuario::paletaDaPessoa())` no `bootUsing`, no `/app` depois do da organização (D5)
- **Atende**: RQ-01..RQ-04, RQ-06, P-02
- **Logs**: nenhum

### 5. A tela de configurações

> Skills: `filament-development`

- **Path**: `app/Filament/Admin/Pages/ConfiguracoesDoKit.php`
- Seção `secao('aplicacao', 'aparencia', 'Aparência dos painéis', …)` com: `Toggle aparencia_por_usuario`, `Select densidade_do_layout` (movido, D6), `Select tamanho_da_fonte`, `Select modo_do_menu` — `->options(Enum::opcoes())`, sem placeholder
- `mutateFormDataBeforeFill()` coage fonte e menu como coage a densidade (P-12); `mutateFormDataBeforeSave()` coage densidade, fonte e menu de novo, porque o `in()` do `Select` aceita uma lista forjada (`["grande"]`), que chegaria nula ao `fill()` do spatie (CT-25) *(alterado em 2026-11-18: a coerção ao abrir era descrita como `mutateFormDataBeforeSave()` — erro de leitura do step 3 — e a da gravação foi acrescentada pelo CT-25; QA-02 do ciclo 1 e QA-10 do ciclo 2)*
- **Atende**: RQ-06, RQ-07, P-12
- **Logs**: nenhum (a gravação do settings já é auditada pela tela)

### 6. A seção "Aparência" do Meu perfil

> Skills: `filament-development`, `livewire`

- **Path**: `app/Livewire/PreferenciasDeAparencia.php`, `resources/views/livewire/preferencias-de-aparencia.blade.php`, os três providers (`->myProfileComponents([... 'preferencias_de_aparencia' => PreferenciasDeAparencia::class])`)
- `canView()`: chave da instalação ligada (P-06)
- Formulário com quatro `Select` (densidade, fonte, cor da lista fechada de `CustomizadorDaInstalacao::CORES`, menu), cada um com placeholder "Padrão (…)" mostrando o padrão vigente (RQ-10, P-07)
- `salvar()`: recusa se a chave da instalação está desligada; coage cada valor; grava por `forceFill(['aparencia' => …])` no usuário autenticado; vazio vira `null` (padrão); notificação "Aparência salva" e recarrega a página para aplicar (P-05, P-12)
- Texto da seção: numa organização que não liberou, vale o padrão dela (P-06)
- **Atende**: RQ-01..RQ-04, RQ-07, RQ-10, P-05, P-06, P-07, P-12
- **Logs**:
  - `Log::info("[PreferenciasDeAparencia@salvar] Aparência pessoal salva | user: {$user->getKey()}", ['user_id' => …, 'aparencia' => $valoresCoagidos])` — só os valores coagidos (vocabulário fechado), nunca o bruto do cliente (`.ai/rules/livewire.md`)
  - `Log::warning("[PreferenciasDeAparencia@salvar] Gravação recusada: aparência pessoal desligada | user: {$user->getKey()}", ['user_id' => …])` *(alterado em 2026-11-18: QA-02 do quality gate, ciclo 1; marca fora da citação, QA-10 do ciclo 2)*

### 7. A chave da organização

> Skills: `filament-development`

- **Path**: `app/Filament/Admin/Resources/Tenants/Schemas/TenantForm.php`
- `Toggle::make('aparencia_por_usuario')` na seção da identidade visual, `->default(false)`, `->visible(fn (): bool => (bool) config('kit.aparencia_por_usuario'))` (P-11) *(alterado em 2026-11-18: Ponytail cortou a entrada no infolist)*
- **Atende**: RQ-08, P-11
- **Logs**: nenhum (o `Tenant` é auditado por `AuditsFillables`)

### 8. O documento de candidatas a pacote

- **Path**: `wikis/candidatas-a-pacote.md`, link em `wikis/roadmap.md` (item 1) e no índice de wikis
- A preferência de aparência como primeira candidata (RQ-13); as demais vindas da revisão do código (RQ-14), cada uma com o que faz, os arquivos, o acoplamento ao kit e o esforço estimado de extração
- **Atende**: RQ-12, RQ-13, RQ-14, P-14
- **Logs**: nenhum

## Filosofia de Implementação

> **Ponytail ativo em modo `full`** durante toda a implementação. Atalhos deliberados marcados com `ponytail:`.
> **Caveman** só na conversa; wiki, código, commits e PR em prosa normal.
> **Baseline antes do primeiro commit**: a suíte de `main` (`a1c21b74`) da release v0.53.2 — Kit+Tenancy+Unit sem falhas e Browser 254 com 0 falhas (2026-11-17, registrada no `03` da wiki `excluir-papel-com-convites`).

## Testes

> Ver `04-casos-de-teste.md`.

## Verificação Final
- [ ] `/ponytail:ponytail-review` no diff
- [ ] `vendor/bin/pint --dirty --format agent`
- [ ] `vendor/bin/filacheck --fix`
- [ ] `vendor/bin/phpstan analyse` (level 8)
- [ ] testes da feature (Kit e Tenancy)
- [ ] `vendor/bin/pest --parallel --tia` contra a baseline
- [ ] `/code-review high main...HEAD` + passe de eixos (step 9)
- [ ] Playwright nos três painéis, sem e com tenancy

## Commits
- `:sparkles: feat(aparencia): preferências de aparência por usuário, sob chave da instalação e da organização`
- `:memo: docs(wiki): wiki e documento de candidatas a pacote`
