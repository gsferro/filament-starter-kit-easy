# Ranking de adoção — fila de instalação

Companheiro de [`pacotes.md`](pacotes.md) (o que já é dependência) e de
[`pacotes-candidatos.md`](pacotes-candidatos.md) (método da varredura, top 10 analisado a fundo,
descartados por categoria).

**Esta página é a fila.** Os 547 plugins Filament v5 gratuitos varridos em 18/08/2026, filtrados para
o que agrega ao kit, ordenados do que mais agrega para o que menos agrega. De cima para baixo, um de
cada vez, enquanto valer a pena.

> Nada aqui está instalado. `composer.json` intocado. Instalar depende de aprovação do mantenedor
> (`CLAUDE.md`), e cada adoção vira uma feature com wiki e testes próprios.

---

## Como usar

1. Pegue o primeiro item da fila que ainda não foi decidido.
2. Abra o repositório e **confirme o nome Composer real** — o diretório do Filament não expõe
   `vendor/pacote` no card, e os nomes aqui vêm do slug da URL.
3. Decida: **adotar**, **recusar** (registre o motivo aqui) ou **adiar** (registre a condição).
4. Adotado → wiki em `wikis/specs/` + testes, e a linha migra para `pacotes.md`.
5. Recusado → a linha migra para a seção "Excluídos" desta página, com o motivo.

**Não instale dois itens do mesmo grupo excludente.** Estão marcados com ⚔️.

## Critério do ranking

Quatro perguntas, nesta ordem de peso:

| Peso | Pergunta |
|---|---|
| 1º | **Fecha lacuna do kit?** O kit hoje não responde a isso de jeito nenhum? |
| 2º | **Amplitude.** Serve a todo app que nasce do kit, ou só a alguns? |
| 3º | **Custo e risco.** Migration? Breaking? Dado pessoal em banco? Acopla arquitetura? |
| 4º | **Confiança no mantenedor.** Autor do core do Filament, do ecossistema, ou desconhecido? |

Empate resolve pelo **menor risco**. É por isso que um lint de CI aparece antes de uma feature de
usuário: entrega valor sem poder quebrar nada em produção.

## Os tiers

| Tier | O que significa | Posições |
|---|---|---|
| **S** | Fecha lacuna estrutural. O kit é mensuravelmente incompleto sem isso | 1–7 |
| **A** | Alto valor, baixo risco. Instale quando chegar a vez, sem muita deliberação | 8–34 |
| **B** | Ganho real de UX/DX. Instale quando a ausência incomodar | 35–66 |
| **C** | Situacional. Só quando o **seu** projeto tiver a necessidade | 67–98 |
| **D** | Opcional / estético. Nunca é errado pular | 99–112 |

### Lacunas do kit que este ranking fecha

Levantadas na revisão de 18/08/2026, em ordem de gravidade:

1. **Mídia** — não existe camada nenhuma (posições 1, 2)
2. **E-mail** — nada registra o que foi enviado (5, 23)
3. **Exceções** — Health, Pulse e LogsExplorer não agrupam exceção (4, 53)
4. **Lixeira** — `SoftDeletes` sem tela de restauração (3)
5. **i18n** — pt-BR fixo, sem troca de idioma (6, 49)
6. **Concorrência de edição** — dois usuários no mesmo registro, sem aviso (40, 41)
7. **Mobile** — nenhum painel é usável no celular (46)
8. **Auth além de senha+2FA** — sem passkey, social, OTP ou bloqueio ativo (32–39)
9. **Lint de Filament** — Pint e PHPStan não entendem Filament (7)
10. **Agendador e storage sem visibilidade** — (20, 22, 54)

---

# Tier S — fecha lacuna estrutural

### ⚔️ Grupo mídia — escolha **um** entre 1 e 2

| # | Pacote | O que faz | Por que aqui |
|---|--------|-----------|---|
| **1** | `filament/spatie-laravel-media-library-plugin` | Campo e entrada de infolist sobre `spatie/laravel-medialibrary` | Plugin **oficial do core**, mesmo ciclo de release do framework. O caminho padrão do ecossistema. Custa uma migration e a migração de `users.avatar_url` / `tenants.logo` — é **breaking** para quem já instalou |
| **2** | `awcodes/filament-curator` | Biblioteca central de mídia + `CuratorPicker` | Fecha a mesma lacuna **sem** tomar a decisão do medialibrary: tabela própria, adoção mais barata. Preço: o dado de mídia não é portável para fora do Filament |

> Os dois resolvem a mesma coisa. Adotar ambos deixa duas tabelas de mídia no kit.

#### Multi-tenancy — o critério que decide este par

Verificado no código dos dois em 18/08/2026, não na descrição do diretório.

| | **1 — oficial** | **2 — Curator** |
|---|---|---|
| Código de tenancy | **nenhum** (0 ocorrências de `tenant` no pacote) | `Config/Concerns/SupportsTenancy.php`, `tenantAware()` no picker, `isScopedToTenant()` + `getTenantOwnershipRelationshipName()` no `MediaResource`, coluna `tenant_id` |
| Modelo de dados | `media` com `morphs('model')` — **cada arquivo pertence a um registro** | tabela própria, registros **avulsos** numa biblioteca compartilhada |
| Como o escopo acontece | **por construção**: a mídia herda o escopo do dono. `Projeto` escopado por organização ⇒ mídia escopada junto | **por configuração**: `curator.features.tenancy.enabled` + `relationship_name`, e o picker filtra `{relationship}_id` |
| Superfície de vazamento | **não existe** — não há pool nem tela de navegação | a biblioteca compartilhada **é** a superfície |
| Default | n/a | **`'enabled' => false`** |

**A armadilha do Curator**: ele nasce com a tenancy desligada. Instalado num projeto do kit com
`kit.tenancy.enabled` ligado e sem virar essa chave, **todo arquivo de toda organização aparece no
picker de todas as outras** — sem erro, sem aviso.

Isso inverte a leitura fácil ("Curator é o atalho, medialibrary é o padrão"):

- **Curator tem a feature e tem o risco.** A biblioteca compartilhada é o valor dele *e* a superfície
  de vazamento.
- **O oficial não tem a feature porque não tem o risco.** Sem pool, não há o que escapar.

Para um kit genérico distribuído por `create-project` — em que a tenancy é **opt-in** e pode ser
ligada depois da instalação, quando o default do Curator já foi aceito —, "seguro por construção"
pesa mais que "configurável". **Recomendação: item 1.**

**Se ainda assim for Curator**, três coisas fazem parte da entrega, não são detalhe:

1. `curator.features.tenancy.enabled` amarrado a `config('kit.tenancy.enabled')`, não escrito à mão
2. `relationship_name` apontando para a relação do `Tenant`
3. Um caso de teste que cria arquivo na organização A e afirma que o picker da B **não** o enxerga —
   e que continua valendo depois de o `kit:tenancy` ligar a tenancy num projeto já instalado

| # | Pacote | O que faz | Por que aqui |
|---|--------|-----------|---|
| **3** | `promethys/revive` | Lixeira central para qualquer model com `SoftDeletes` | O kit usa soft delete e não tem tela de restauração — hoje é tinker. Substitui código próprio (o `mini-pff` resolveu com uma `Page` custom). Exige policy: restaurar ignora regra de negócio por definição |
| **4** | `bezhansalleh/filament-exception-viewer` | Exceções agrupadas por tipo e frequência | Autor do Shield e do PanelSwitch. Fecha o ponto fraco do `/infra`. Exige retenção — e stack trace guardado pode conter dado pessoal |
| **5** | `tapp/filament-mail-log` | Registro de todo e-mail enviado | O `ConviteDeAcesso` é a porta de entrada do kit e não deixa rastro. "O convite não chegou" é hoje impossível de responder. Guarda corpo do e-mail: precisa de retenção |
| **6** | `bezhansalleh/filament-language-switch` | Troca de idioma no painel | O kit já é publicado com `README.en.md` no diretório oficial. **O pacote é a parte fácil** — a cara é tirar os rótulos pt-BR de dentro do código (ver 49) |
| **7** | `laraveldaily/filacheck` | Análise estática específica de Filament | `require-dev`, entra no CI ao lado do Pint e do PHPStan. **Risco zero em produção.** Precisa de baseline para as decisões deliberadas do kit (`CommandCenterPlugin` sem `->cluster()`, `RoleResource` publicado) |

---

# Tier A — alto valor, baixo risco

## Tabela e listagem

| # | Pacote | O que faz | Nota |
|---|--------|-----------|---|
| **8** | `awcodes/filament-sticky-header` | Cabeçalho de tabela fixo no scroll | Uma linha, autor do core, ganho imediato em toda listagem |
| **9** | `zeeshan-tariq/sticky-columns` | Colunas fixas no scroll horizontal | Complementa o 8; o `/infra` tem tabelas largas |
| **10** | `defstudio/filament-column-length-limiter` | Trunca texto longo com tooltip | Resolve o problema que toda tabela com campo de texto tem |
| **11** | `tapp/filament-value-range-filter` | Filtro de intervalo reutilizável | Filtro de data/número é escrito à mão em todo resource hoje |
| **12** | `eddie-rusinskas/filament-queueable-bulk-actions` | Bulk em fila, com status | O kit já tem `jobs-monitor`; casa direto. Evita timeout em ação em massa |
| **13** | `yarmat/table-presets` ⚔️ | Usuário salva combinações de filtro/coluna | ⚔️ com o 14. Este é por preset nomeado |
| **14** | `kisame76/db-table-state` ⚔️ | Persiste filtro/ordem/coluna por usuário | ⚔️ com o 13. Este é automático. O kit já persiste em sessão (`ConfiguraFilamentGlobal`); isto leva para o banco |
| **15** | `tgeorgel/filament-table-layout-toggle` | Alterna grade ↔ tabela | Barato, e é meio caminho para o mobile (ver 46) |
| **16** | `ptplugins/auto-filters` | Gera filtros a partir das colunas | Corta boilerplate repetido em todo resource |

## Formulário

| # | Pacote | O que faz | Nota |
|---|--------|-----------|---|
| **17** | `awcodes/shout` | Aviso contextual dentro do form | Comunica regra e risco onde a decisão acontece. Autor do core |
| **18** | `codewithdennis/filament-select-tree` | Select hierárquico | O kit tem permissões e papéis — o caso de uso está pronto |
| **19** | `novadaemon/filament-pretty-json` | Ver e editar JSON legível | O kit tem `ai_runs` com payload, `audits` com diff, `queue_monitors`. Hoje é blob |
| **20** | `blendbyte/title-with-slug` | Título + slug + preview de permalink | Padrão repetido em quase todo CRUD; o `Tenant` do kit já faz isso à mão |
| **21** | `jim/draft-recovery` ⚔️ | Auto-save e recuperação de rascunho | ⚔️ com 22 e 23 — os três atacam perda de dados em form longo, por caminhos diferentes |
| **22** | `aziz-gasim/unsaved-changes-modal` ⚔️ | Modal do Filament no lugar do diálogo do browser | O mais barato dos três |
| **23** | `cocosmos/sticky-save-bar` ⚔️ | Barra de salvar flutuante | O mais discreto dos três |

## Operação e observabilidade

| # | Pacote | O que faz | Nota |
|---|--------|-----------|---|
| **24** | `husam-tariq/filament-database-schedule` | Gerencia o agendador pelo painel | O kit tem 3 agendamentos e nenhuma visibilidade. Cabe exatamente no `/infra` |
| **25** | `jeffersongoncalves/filament-one-time-operations` | Migrations de dados versionadas | Resolve "rodar isso uma vez em produção", que hoje é tinker |
| **26** | `achyutn/storage-monitor` | Uso de disco por partição | Vizinho natural do Health no `/infra` |
| **27** | `hugomyb/filament-error-mailer` | Alerta por e-mail em exceção | Par do item 4: um mostra, o outro avisa |
| **28** | `backstage/laravel-mails` ⚔️ | Log de e-mail com abertura, clique e bounce | ⚔️ com o 5. Mais completo e mais pesado — escolha um |

## DX (só quem mantém o kit sente)

| # | Pacote | O que faz | Nota |
|---|--------|-----------|---|
| **29** | `bramr94/filament-developer-logins` | Login de 1 clique em local | **Nunca** habilitar fora de `local`. Economiza minutos por dia |
| **30** | `agence-twogether/filament-hooks-helper` | Revela os render hooks da página | Teria economizado horas na feature do cabeçalho do menu do usuário |
| **31** | `niladam/filament-quick-links` | Abre o Resource no editor a partir da tabela | Barato e específico |
| **32** | `guava/icons` | Instala icon packs e gera enums tipados | DX transversal; o kit vive de `Heroicon::` |
| **33** | `bezhansalleh/plugin-essentials` | Traits para desenvolver plugins Filament | O kit publica `gsferro/*-easy` — é público-alvo direto |
| **34** | `guava/filament-mcp` | Expõe o painel como servidor MCP | Casa com a trilha de IA local e o Boost já instalado |

---

# Tier B — ganho real, instale quando incomodar

## Autenticação e segurança

| # | Pacote | O que faz | Nota |
|---|--------|-----------|---|
| **35** | `eightcedars/filament-inactivity-guard` | Logout por ociosidade | Confirme antes: o `lockscreen` instalado já tem `idle_timeout` — pode ser redundante |
| **36** | `smony/user-sessions` | Lista sessões ativas e revoga | O `authentication-log` **registra**; isto **age** |
| **37** | `smony/filament-login-attempts` | Bloqueio ativo de força bruta | Mesma lógica do 36: registrar não é bloquear |
| **38** | `l3aro/filament-cloudflare-turnstile` ⚔️ | Anti-bot em login e registro | ⚔️ com Shield Captcha e Captcha (marcogermani). Só vale se o kit abrir registro público — hoje a porta é convite |
| **39** | `adriaanzon/filament-passkeys` ⚔️ | Login WebAuthn sem senha | ⚔️ com Robert Boes e Marcel Weidum. Sobre o pacote oficial `laravel/passkeys` |
| **40** | `discoverydesign/filament-gaze` ⚔️ | Mostra quem está editando o registro | ⚔️ com 41. Este avisa (presença) |
| **41** | `balismatz/prevent-outdated-record-update` ⚔️ | Lock otimista contra sobrescrita | ⚔️ com 40. Este **impede** (versão). Mais forte, mais invasivo |
| **42** | `joserojasrodriguez/delete-guard` | Regras de exclusão no model | Impede apagar registro referenciado — integridade genérica |
| **43** | `tapp/filament-invites` | Convite de usuário como action | ⚠️ O kit **já tem** fluxo de convite completo (lote, lembrete, usuário existente). Avaliar só como referência |
| **44** | `afsakar/filament-otp-login` ⚔️ | Login/2º fator por OTP | ⚔️ com o 2FA do Breezy já instalado. Alternativa, não adição |
| **45** | `dododedodonl/filament-socialite` | SSO / OAuth no painel | **Conflita com o modelo do kit**: convite é a única porta. Exige decidir o que fazer com "autenticado sem convite". Alto valor, alta consequência |

## UX de painel

| # | Pacote | O que faz | Nota |
|---|--------|-----------|---|
| **46** | `hammadzafar05/mobile-bottom-nav` + `mobile-preset` | Navegação inferior e tabela em cartões no mobile | Fecha uma lacuna real, mas **exige decidir se a 1.0 se propõe a ser mobile** |
| **47** | `awcodes/recently` | Registros vistos recentemente | Autor do core; complementa o Spotlight (⌘K) |
| **48** | `awcodes/filament-quick-create` | Dropdown de criação na topbar | Vira o **3º** caminho para criar (menu, ⌘K, topbar). Exige allow-list por painel |
| **49** | `statik/chained-translation-manager` ⚔️ | Edita traduções sem conflito de merge | ⚔️ com `momin-alzaraa/localization` (que **gera** os arquivos varrendo resources). **Pré-requisito real do item 6** |
| **50** | `guava/filament-modal-relation-managers` | Relation manager em modal | Encurta o CRUD; boa quando o resource tem muitas relações |
| **51** | `hugomyb/filament-media-action` | Preview de vídeo, áudio, PDF e imagem | Estende o `simplelightbox` instalado para além de imagem |
| **52** | `awcodes/light-switch` | Dark mode nas telas de auth | ⚠️ Confirme: o `auth-designer` instalado já tem `themeToggle()` |
| **53** | `kholil/nitik-error-tracker` ⚔️ | Error tracking self-hosted | ⚔️ com o item 4. Faz mais, pesa mais |
| **54** | `zpmlabs/cron-manager` ⚔️ | Visibilidade do agendador | ⚔️ com o 24 |
| **55** | `swisnl/filament-backgrounds` | Fundo na tela de login | Puro polimento, custo quase nulo, primeira tela que o usuário vê |
| **56** | `marcelweidum/expiration-notice` | Corrige o aviso bruto de sessão expirada do Livewire | Detalhe que separa "funciona" de "acabado" |
| **57** | `isach/collapsible-subnav` ⚔️ | Sub-navegação colapsável | ⚔️ com `agmedia/navigation-enhanced` e `devletes/pinnable-navigation`. Só quando o menu doer |
| **58** | `nben-malla/record-navigation` | Próximo/anterior entre registros | Acelera revisão em massa |
| **59** | `tapp-network/footer` ⚔️ | Rodapé traduzível | ⚔️ com `devonab/easy-footer`. Bom lugar para a versão do kit |

## Dados e conteúdo

| # | Pacote | O que faz | Nota |
|---|--------|-----------|---|
| **60** | `eslam-reda-ragheb/timezone-detector` | Converte UTC ↔ fuso do browser | Resolve globalmente o que hoje seria por coluna |
| **61** | `waad-mawlood/import-wizard` | Import CSV/Excel em fila, com mapeamento | O Filament 5 já tem `ImportAction`; isto acrescenta o mapeamento de colunas e a fila |
| **62** | `dani-hidayat/image-optimizer` | Otimiza imagem antes de persistir | Par natural do grupo mídia (1/2) |
| **63** | `leek/dicebear` ⚔️ | Avatar padrão gerado, com cache em disco | ⚔️ com Facehash, Fin Avatar, Boredom, Gravatar. **Sem chamada externa** — é o diferencial deste |
| **64** | `spykapp/uppy-upload-plugin` | Upload em chunks | Resolve arquivo grande sem tuning de PHP |
| **65** | `kirschbaum/commentions` ⚔️ | Comentários com menção em qualquer resource | ⚔️ com Relaticle Comments, Commentable, Lara Zeus Replies |
| **66** | `jeffersongoncalves/ace-editor-field` | Editor com realce para JSON/config/template | Complementa o 19 |

---

# Tier C — situacional

Só instale quando o **seu** projeto tiver a necessidade. Nenhum destes torna o kit melhor por si.

| # | Pacote | Quando |
|---|--------|--------|
| **67** | `jabir-khan/pennant-manager` | Quando adotar `laravel/pennant` |
| **68** | `jeffersongoncalves/filament-keyable` | Quando o kit expuser API |
| **69** | `yusuf-genc/api-forge` | Quando quiser REST automática sobre os resources |
| **70** | `zpmlabs/api-docs` / `alex-kramarenko/openapi-docs` | Quando houver API pública |
| **71** | `bas-van-dinther/canary` | Quando a matriz de permissão crescer a ponto de precisar de smoke em runtime |
| **72** | `alberto-fuentes/panel-maintenance` | Quando houver janela de manutenção com usuário real |
| **73** | `ashrafic/white-label` / `muazzambuilds/panel-branding` | Quando o kit precisar de branding por organização além da cor |
| **74** | `agmedia/shield-enhanced` | Quando as permissões de página do Shield ficarem curtas |
| **75** | `martin-knops/watchdog-v5` / `wallacemartins/security` | Quando o app for exposto à internet aberta |
| **76** | `yebor974/filament-renew-password` | Quando houver exigência corporativa de expiração de senha |
| **77** | `jrg7/saml2-okta` | Quando houver IdP corporativo |
| **78** | `leek/subtenant-scope` | Quando a tenancy virar hierárquica |
| **79** | `sridhar-s-subramanian/dbview` | Quando quiser inspecionar banco sem shell |
| **80** | `daljo25/dependency-manager` | Quando o `composer-release-notifier` instalado não bastar |
| **81** | `christos-papoulas/export-cleanup` | Depois de adotar export em volume |
| **82** | `cristian-iosif/architect` | Quando o scaffolding do kit não bastar |
| **83** | `coringawc/single-record-resource` | Quando houver tela de registro único (settings, perfil da instalação) |
| **84** | `inerba/db-config` | ⚠️ O `spatie/laravel-settings` **já está instalado**. Só se a abordagem dele não servir |
| **85** | `novius/translatable` / `lara-zeus/spatie-translatable` | Quando houver **conteúdo** traduzível (não só interface) |
| **86** | `ahmed-d-ali/countries` | Quando houver cadastro internacional |
| **87** | `ysfkaya/filament-phone-input` ⚔️ | Quando houver telefone. ⚔️ com `cheesegrits/phone-numbers` |
| **88** | `fawaziwalewa/icon-picker` / `guava/filament-icon-picker` | Quando menu ou tema forem configuráveis pelo usuário |
| **89** | `lara-zeus/qr` | Quando houver 2FA por QR ou compartilhamento |
| **90** | `torgodly/html2media` | Quando houver impressão ou PDF de tela |
| **91** | `saade/filament-fullcalendar` / `guava/calendar` | Quando houver dado temporal com agenda |
| **92** | `lara-zeus/inline-chart` | Quando houver série temporal por linha de tabela |
| **93** | `mkdev/record-watcher` / `kirschbaum/diffs` | Quando a auditoria instalada precisar de assinatura ou diff visual |
| **94** | `arseno25/privacy-blur` | Quando houver demo pública com dado real |
| **95** | `guava/filament-knowledge-base` | Quando a documentação precisar viver dentro do painel |
| **96** | `notebrainslab/email-templates-management` | Quando os e-mails transacionais passarem de dois |
| **97** | `tapp/filament-webhook-client` / `marjose123/filament-webhook-server` | Quando houver integração real |
| **98** | `pxlrbt/filament-excel` | Só se o export nativo do Filament 5 não bastar em estilização |

---

# Tier D — opcional, estético, pule sem culpa

| # | Pacote | Nota |
|---|--------|------|
| **99** | `awcodes/richer-editor` ⚔️ / `mohamedsabil83/rich-editor-extra` | Extensões do RichEditor nativo. Só com uso editorial |
| **100** | `tonegabes/better-options` / `codewithdennis/advanced-choice` | Radio e checkbox estilizados |
| **101** | `cocosmos/quick-add-select` | Cria opção de relação sem sair do form |
| **102** | `novadaemon/filament-combobox` / `alona/dropdowncheckboxlist` | Melhor que `CheckboxList` em lista grande |
| **103** | `zvizvi/user-fields` | Avatar do usuário em select, coluna e filtro |
| **104** | `shreejan/actionable-column` / `muhammad-kazim/quick-edit` | Ação e edição embutidas na linha |
| **105** | `leek/right-click` / `harvir/action-overflow` | Menu de contexto, header limpo |
| **106** | `akunbeben/fluid-actions` / `narayan-dhakal/draggable-modal` / `anish-regmi/resizable-modal` | Conforto de interação |
| **107** | `lara-zeus/tabler-icons-enum` ⚔️ / `daljo25/tabler-icons` | ⚔️ com o item 32 (Guava Icons), que é mais geral |
| **108** | `andreia-bohner/ui-switcher` / `supianidz/palette` | Usuário troca cor, fonte e tamanho. Toca acessibilidade — o único argumento não-estético do tier |
| **109** | `pxlrbt/favicon` | Cosmético, autor confiável |
| **110** | `filament/spatie-laravel-google-fonts-plugin` | Fontes servidas localmente — argumento é LGPD, não estética |
| **111** | `hasan-yagout/announcement` ⚔️ | Anúncio agendado e dispensável. ⚔️ com Marcelo Delgado Announcements |
| **112** | `lara-zeus/popover` / `tinus-guichelaar/hover-preview-for-imagecolumn` | Detalhe sob demanda em tabela |

**Temas prontos** — fora do ranking por serem escolha de quem usa o kit, não do kit:
Shadcn · Clarity · Aurum · Moonlight · Neobrutalism · Bonsai · Aura · Nord · Edinburgh · Aberdeen ·
Inverness · BenRiadh. O kit já tem `KIT_COR_PRIMARIA` e paleta por organização.

---

# Excluídos — e por quê

Registrado para a próxima varredura não trazer os mesmos nomes de volta.

## 1. Já resolvido por pacote instalado

`filament/spatie-laravel-settings-plugin` (**já é dependência** — apareceu na varredura),
Overlook e Launchpad e Tabify e Layout Manager (duplicam `dashboard-widgets` /
`dynamic-dashboard` / a página de hub em cartões do próprio kit), Global Search Modal (duplica o
Spotlight), Notifications Tabs e Modal Notifications e Notification Sound (duplicam o
`notification-center`), Plotly e ECharts e Chart Palette (duplicam o `apex-charts`), Progress Bar
Column ×2 (duplica `lara-zeus/progress`), Image Gallery e Infolist Media Gallery (duplicam o
`simplelightbox`), Versions e Changelog e App Version e Feature Showcase (sobrepõem o
`composer-release-notifier` e o `environment-indicator`), Blackbox e Logger e Activity Log da
Relaticle (duplicam `auditing` / `activitylog`), Guardian e Jaga (duplicam o Shield), Clear Cache
Button, Fin Sentinel, Grid Card, iPatco Profile (Breezy já entrega perfil), Tour (sobrepõe o
`onboarding`).

> **Activity Timeline** (La Boîte à Code, Lara Zeus, Bokshorn) fica fora do ranking pelo mesmo
> motivo, com uma ressalva: os três **visualizam** o `spatie/activitylog` que o kit já tem. Se a
> timeline em infolist virar necessidade, o da Lara Zeus é o de menor acoplamento.

## 2. Coberto por recurso nativo

| Excluído | Já existe em |
|---|---|
| Action Export, Advanced Table Export | `ExportAction` / `ImportAction` nativos do Filament 5 |
| Slug (Novius) | `Str::slug()` no `afterStateUpdated()`, 2 linhas |
| Pikaday, Inline Date-Time Picker, Time picker, Smart Time Picker | `DatePicker` / `DateTimePicker` nativos |
| Tab Layout Plugin, Accordion | `Tabs` e `Section` collapsible do `Filament\Schemas` |
| Timezone Field | `Select` com `timezone_identifiers_list()` |
| Scroll to top, Alert Box | um render hook `BODY_END` de ~20 linhas |
| ClearField Action, User Field, Searchable Input, Palette (awcodes) | poucas linhas de código próprio |
| Phosphor Icons Enum | `Heroicon` nativo + item 32 |

## 3. Domínio de negócio

E-commerce · ERP · CRM · helpdesk e ticketing (Service Desk, Creators Ticketing) · blog e CMS
(Page Builder, Graper, Atelier, Mason, Bolt, Redberry) · imobiliária · pagamentos regionais
(Transbank, MercadoPago) · chat de loja · jogos (Play Room) · SEO · AdSense · Flowforge (Kanban de
domínio) · Lookups · Inbox · agendamento de consultas · wrappers de analytics de terceiros
(Matomo, Plausible, Gtag, Pixel, Umami, Fathom).

## 4. Risco alto demais para um kit

| Excluído | Motivo |
|---|---|
| Web Terminal, Web Terminal Stream, Server Access | shell remoto pelo navegador. Um kit não decide isso por quem o usa |
| `.Env Editor` | expõe segredo em tela |
| Wildcard Login | login sem senha por domínio de e-mail |
| Auto Panel, Module Manager, Modular Luncher, Themer Luncher | geram ou trocam estrutura em runtime — o kit vale por ser código legível e auditável |
| Custom Fields, Flex Fields ×2 | campos sem migration convidam a EAV; o schema deixa de contar a verdade |

## 5. Depende de stack que o kit não adota

Meilisearch e Scout Manager (sem Scout) · Passport UI (sem Passport) · Astrotomic (o kit usaria
Spatie) · Spatie Model States Visualizer e StateFusion (sem `model-states`) · Vacuum (só PostgreSQL;
o kit é agnóstico e nasce em SQLite) · AI Translator e AI writer e AI Actions e AI OpenRouter Gateway
(provedor externo pago, contra a trilha de IA local) · Recurrence (RFC 5545 sem consumidor) ·
Cookie Consent e Action Preview (sem frontend público) · Bug Reports (amarra ao GitHub) ·
Multi-Factor WhatsApp (exige provedor WhatsApp).

## 6. Starter kits concorrentes

ServiceDeskKit · FilaKit · TeamKit · MFAKit · EvolutionKit · Member Management · Team Guard ·
Tenant Members. Não são dependência — são **referência de arquitetura multi-painel**, e vale olhar
como resolveram o que aqui ainda está aberto.

## 7. Duplicados internos ao próprio ranking

Só o vencedor de cada grupo entrou. Perdedores, com o vencedor entre parênteses:

- **Mídia**: Uni File Manager, File Manager (Marco Messa), File Manager (mwguerra), TinyFinder,
  Media Manager (Slimani), Media Library (Waad), Attachment Library, Attachmate, File Explorer
  (→ 1 ou 2)
- **Lixeira**: Recycle Bin (Muazzam) (→ 3)
- **Passkeys**: Robert Boes, Marcel Weidum (→ 39)
- **Captcha**: Shield Captcha, Captcha (Marcogermani), Turnstile (Muazzam), Registration Plugin (→ 38)
- **OTP**: Taha Moghaddam (→ 44)
- **Avatar gerado**: Facehash, Fin Avatar, Boredom, Gravatar (→ 63)
- **Idioma**: Craft Forge Language Switcher, Locale Switcher (Pruna) (→ 6)
- **Traduções**: Translation Manager (Riadh) (→ 49)
- **Autosave**: Autosave (Yousef), Hades (→ 21/22/23)
- **JSON**: JCCoca JSON Column, PepperFM, Valentin Morice (→ 19)
- **Comentários**: Relaticle Comments, Commentable, Lara Zeus Replies (→ 65)
- **Árvore**: Tree View, Adjacency List, Tree (Solution Forest), Tree Table, Relation Nested,
  Nestedset, Nestable Tree (→ 18)
- **Ícones**: Tabler ×2, Phosphor (→ 32 / 107)
- **Rodapé**: Easy Footer (→ 59)
- **Navegação**: Navigation Enhanced, Pinnable Navigation, Drilldown Sidebar, Topbar Menu,
  Navigation Footer Menu, Delia, Workspace Tabs (→ 47 / 57)
- **Repeater**: Advance Table Repeater, Modal Repeater (→ nativo)

## 8. Cosmético sem argumento

Click Spark · Sidebar Resize · Slick Scrollbar · Swippable Notification · Drag & Scroll ·
Connection Badge · Masonry · Fluid Actions (parcial, ver 106) · Hidden Action · Barcode Field ·
QrCode Field · CEP Field · Take Picture Field · Echoo · Image Radio Button · Font Picker ·
Icon Select Column · Toggle Table Group Action · Relation Components · Relation Pages ·
TextInput Entry · TextInput Autocomplete · Number Input · Quantity · Form Settings ·
Common Helper Components · Components (RalphJSmit) · Peek · Torch · Rich Editor Fullscreen ·
Rich Editor Extender · BlockNote · Fin Modal Table Select · Collapsible Column Group ·
Country Code Field · Fila Calendar · Van Ons Redirects · Fin Mail · SimpleStats ·
Pivot Table Free · Data Copilot · Copilot · AI-Kit · AI Table Filters · Async Column ·
Filter Sets (Kingmaker) · Column Filters (Zvizvi) · Header Filters (Leek) · Sticky Table Header
(Watheq) · Form Request Validation · Outbox · Notifier · Inspector · Ban Guard · Resource Lock ×2 ·
Browser Notifications · File View Entry · Spatie Tags · Currency · Guava Calendar ·
Quick Links duplicados.

> Vários destes são bons plugins. "Cosmético sem argumento" aqui significa: **não fecha lacuna do
> kit e não é mais barato que escrever**. Num projeto específico, podem ser a escolha certa.

---

## Resumo

| | |
|---|---|
| Varridos | **547** (Filament v5, gratuitos, páginas 1–61) |
| Classificados `SIM` na triagem | 140 |
| Classificados `TALVEZ` | 201 |
| **No ranking** | **112** |
| Excluídos com motivo registrado | 435 |
| Instalados hoje | 51 (`require`) + 15 (`require-dev`) |

**Se for instalar só cinco**: 7 (FilaCheck) → 5 (Mail Log) → 4 (Exception Viewer) → 3 (Revive) →
**1** (mídia — ver o critério de tenancy acima; para este kit o oficial vence o Curator). Os quatro primeiros somam risco quase nulo; o quinto é a decisão de arquitetura que
a 1.0 precisa tomar de qualquer jeito.

> ⚠️ **Todo `vendor/pacote` desta página veio do slug da URL do diretório**, que não expõe o nome
> Composer. Confirme no README antes de qualquer `composer require`. "Compatível com v5" também é
> declaração do autor, não medição.
