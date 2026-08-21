# Pacotes: quem é dono de quê

> A lista completa, com link e descrição de cada pacote, está no [README](../README.md#pacotes-instalados). Este documento responde a outra pergunta, a que interessa a um agente: **isto já existe? quem é o dono desta tela?** Antes de implementar qualquer coisa desta lista, você está reimplementando vendor.

## Já existe — não escreva de novo

| Se você fosse implementar… | Já vem de |
|---|---|
| CRUD de papéis e permissões | `bezhansalleh/filament-shield` (`/admin`) — com o `RoleResource` **publicado no projeto**, ver abaixo |
| Perfil, avatar, troca de senha, 2FA, passkeys | `jeffgreco13/filament-breezy` (`/meu-perfil`) |
| Tela de login com arte | `caresome/filament-auth-designer` |
| Bloqueio de sessão por inatividade | `marjose123/filament-lockscreen` |
| "Entrar como" outro usuário | `stechstudio/filament-impersonate` |
| Histórico de login, IP, dispositivo | `tapp/filament-authentication-log` |
| Trilha de alterações de model | `owen-it/laravel-auditing` + `tapp/filament-auditing` (`/infra/audits`) |
| Troca de painel no menu do usuário | `bezhansalleh/filament-panel-switch` |
| Página de saúde da aplicação | `spatie/laravel-health` + `shuvroroy/filament-spatie-laravel-health` |
| Backup e monitor de backup | `spatie/laravel-backup` + `brimham/filament-backup-monitor` |
| Monitor de filas | `croustibat/filament-jobs-monitor` |
| Leitor de logs na UI | `laboiteacode/filament-logs-explorer` |
| Rodar Artisan pela UI, com histórico | `ssbityukov/filament-command-center` |
| APM / performance | `laravel/pulse` + `dotswan/filament-laravel-pulse` |
| Mapa de models e relações | `laboiteacode/filament-dependency-graph` |
| Aviso de versão nova de pacote | `mominalzaraa/filament-composer-release-notifier` |
| Botão de limpar cache | `cms-multi/filament-clear-cache` |
| Busca global estilo ⌘K | `wezlo/filament-search-spotlight` (+ categorias do kit) |
| Centro de notificações com abas | `prodstarter/filament-notification-center` |
| Indicador de ambiente | `pxlrbt/filament-environment-indicator` |
| Contador animado (tabela, stat, badge) | `gsferro/filament-odometer-easy` |
| Stat card com ícone de canto e skeleton | `gsferro/filament-stat-plus-easy` |
| Badge dentro de coluna de tabela | `awcodes/filament-badgeable-column` |
| Coluna redimensionável | `asmit/resized-column` |
| Widget de funil, meta, breakdown, timeline | `laboiteacode/filament-dashboard-widgets` |
| **Gráfico** (linha, área, barra, rosca, radial, radar) | `leandrocfe/filament-apex-charts` — nunca `<canvas>` nem Chart.js à mão |
| **Ampliar imagem ou documento de tabela** | `solution-forest/filament-simplelightbox` — nunca modal de preview escrito à mão |
| **Página hub: grade de cartões de navegação** | `harvirsidhu/filament-cards` — **ligado no `/infra`; opt-in (`KIT_HUB`) em `/admin` e `/app`**. Nunca Blade de cartões à mão |
| Dashboard arrastável pelo usuário | `mddev31/filament-dynamic-dashboard` |
| Barra de progresso em coluna/entry | `lara-zeus/progress` |
| Checklist e tour guiado | `wallacemartinss/filament-onboarding` |
| Página de erro branda | `anselmokossa/filament-sentinel` |
| **Upload com coleções, conversões e ordenação** | `spatie/laravel-medialibrary` + `filament/spatie-laravel-media-library-plugin` — nunca `FileUpload` gravando caminho em coluna, exceto para avatar e logo, que já são assim |
| **Restaurar registro apagado** (`SoftDeletes`) | `promethys/revive` (`/infra`, "Lixeira") — nunca uma Page própria varrendo `app/Models` |
| **Ver exceções agrupadas por tipo e frequência** | `bezhansalleh/filament-exceptions` (`/infra`) — o LogsExplorer mostra o arquivo, não o agrupamento |
| **Saber se um e-mail foi enviado** | `tapp/filament-maillog` (`/infra`, grupo "Trilhas") |
| Trocar o idioma da interface | `bezhansalleh/filament-language-switch` — ligado por `config('kit.idiomas')` |
| Lint específico de Filament no CI | `laraveldaily/filacheck` (dev) — `composer filament:check`, dentro de `composer test` |
| **Upgrade automatizado de major** (Laravel, PHP) | `rector/rector` + `driftingly/rector-laravel` (dev) — `composer refactor:preview`. **Fora** do `composer test`, por decisão medida: ver [qualidade-de-codigo.md](qualidade-de-codigo.md) |
| Upgrade automatizado de major do **Filament** | `filament/upgrade` — ferramenta oficial, também baseada em Rector. Não existe regra de Filament no `rector-laravel` |
| Agregar série temporal para gráfico | `flowframe/laravel-trend` |
| Página de configurações persistidas | `filament/spatie-laravel-settings-plugin` + `spatie/laravel-settings` |
| Cache automático de query Eloquent | `mike-bronner/laravel-model-caching` |
| WebSocket para notificação em tempo real | `laravel/reverb` |
| SDK de IA (agentes, tools, streaming) | `laravel/ai` |
| Orquestração de tarefa de IA, budget, ledger | `fomvasss/laravel-ai-tasks` |

### O `RoleResource` do Shield é código do projeto

`php artisan shield:publish --panel=admin` copiou o Resource e as quatro Pages para `app/Filament/Admin/Resources/Roles/`. Enquanto esses arquivos existirem, o `FilamentShieldPlugin` **não** registra o dele — `Utils::isResourcePublished()` procura `\RoleResource` entre os resources do painel. A URL não muda (`/admin/shield/roles`).

Foi preciso porque o Shield não oferece hook para agrupar as permissões por painel: nada em `HasShieldFormComponents` consulta o painel corrente, e a tela mostrava os Resources dos três misturados numa lista só.

| O que mudou em relação ao vendor | Onde |
|---|---|
| `Select::make('painel')` — o campo que dá acesso ao painel | `RoleResource::form()` |
| `getResourceEntitiesSchema()` agrupa as seções por painel, lendo `App\Support\Paineis` | `RoleResource` |
| `secaoDoResource()` — o corpo do `map()` original do vendor, extraído para ser reusado | `RoleResource` |
| `'painel'` nas listas de `mutateFormDataBefore*` | `Pages/CreateRole.php`, `Pages/EditRole.php` |
| Normalização de tipo nas três fronteiras em que o Shield é mais largo que o Filament: `colunasDaGrade()` (o `getGridColumns()` do plugin é `int|string|array`, o `columns()` do Filament não aceita string nem array solto) e as guardas de `getModel()`/`getCluster()` (o `Utils` devolve `string`, o Filament exige `class-string`). Sem mudança de comportamento com config válida; com config inválida o erro passa a ser explícito | `RoleResource` |

**No upgrade do Shield:** o resto dos cinco arquivos é cópia byte a byte, de propósito, para o `diff` contra o vendor novo continuar legível. Depois de um major do pacote, compare `app/Filament/Admin/Resources/Roles/` com `vendor/bezhansalleh/filament-shield/src/Resources/Roles/` e traga o que mudou, preservando as cinco linhas acima. O formato da entidade (`resourceFqcn`, `model`, `modelFqcn`, `permissions`) é contrato interno do Shield — se mudar, o agrupamento quebra. `tests/Kit/PaineisTest.php` acusa os dois casos: um teste afirma que o Resource registrado é o do projeto, outro que a tela mostra os três grupos de painel.

Reverter é apagar a pasta: o plugin volta a registrar o Resource dele, e a tela perde o agrupamento e o campo `Painel`.

## Onde cada plugin é registrado

Plugin do Filament é registrado no `PanelProvider` do painel em que aparece — não há registro global:

| Painel | Provider |
|---|---|
| `/app` | `app/Providers/Filament/AppPanelProvider.php` |
| `/admin` | `app/Providers/Filament/AdminPanelProvider.php` |
| `/infra` | `app/Providers/Filament/InfraPanelProvider.php` |

O que **não** é plugin de painel (defaults de tabela, Panel Switch, gates, health, ledger de IA) mora no `KitServiceProvider` e na concern `ConfiguraFilamentGlobal`. Ver [arquitetura.md](arquitetura.md).

Oito plugins estão registrados nos **três** painéis, de propósito: Spotlight, Auth Designer, Breezy, Lockscreen, Environment Indicator, Odometer, ResizedColumn e Notification Center. No caso do Lockscreen isso é **obrigatório** — ver a tabela de armadilhas em [convencoes.md](convencoes.md#armadilhas-já-resolvidas).

### Pacote não registrado ainda mexe nos seus models

O parágrafo acima diz que não há registro global. `mddev31/filament-dynamic-dashboard` é o
contraexemplo: não está em painel nenhum e mesmo assim age. O service provider é
auto-descoberto e pendura um `User::deleting` global que apaga os dashboards **pessoais** de
quem está saindo. Não dá para optar por não tê-lo sem remover o pacote.

As migrations dele vêm como `.stub` e exigiram `vendor:publish` — estão em
`database/migrations/*_dynamic_dashboard_*`. **Não as apague por parecerem de feature
desligada**: sem a tabela `dashboards`, excluir QUALQUER usuário devolve 500 nas três
superfícies de exclusão (DeleteAction da edição, da tabela e DeleteBulkAction). O defeito
nasceu com o skeleton e sobreviveu 449 casos verdes porque nenhum deles excluía um usuário;
`tests/Kit/ExclusaoDeUsuarioTest.php` fecha a lacuna.
## Motores por baixo

Não estão no `require`, mas são o que de fato roda:

| Pacote | Quem o traz |
|---|---|
| `spatie/laravel-permission` | Shield |
| `spatie/laravel-health` | plugin de health |
| `spatie/laravel-activitylog` | `syriable/filament-activitylog` |
| `livewire/livewire` | Filament inteiro |

## Regras ao mexer com dependências

- **Não adicione nem atualize dependência sem aprovação explícita do usuário.**
- Antes de usar uma API de pacote, confirme a versão instalada: `composer show <vendor/pacote>` ou `composer show --direct`. Não presuma major.
- Use `search-docs` (MCP do Boost): ele devolve a documentação **da versão instalada**, não a mais recente do site.
- Personalização de plugin: traduções em `lang/vendor/`, views em `resources/views/vendor/`. Nunca editar `vendor/`.

## O que ainda não está aqui

Esta página lista o que **já é dependência**. A pergunta complementar — *o que mais existe no
diretório oficial, o que vale avaliar e o que já foi olhado e recusado* — tem página própria:

**[pacotes-candidatos.md](pacotes-candidatos.md)** — varredura dos 547 plugins Filament v5 gratuitos
(agosto/2026), top 10 candidatos com prós e contras, segunda linha e a lista de descartados com o
motivo de cada um.

E a fila de adoção, com os 112 que sobraram ordenados do que mais agrega ao que menos:
**[pacotes-ranking.md](pacotes-ranking.md)**.

Pacote aprovado sai de lá e entra aqui, na tabela do "já existe". Pacote recusado **fica** lá, com o
motivo — é o que impede a próxima varredura de trazer o mesmo nome de volta.
