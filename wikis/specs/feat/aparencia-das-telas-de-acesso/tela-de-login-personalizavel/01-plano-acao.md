# Plano de Ação — feat: tela de login personalizável

> Requisito: `00-requisito.md`

## Natureza da Wiki

- **Tipo**: evolução
- **Wikis ancestrais**: `wikis/specs/fix/auth-designer-telas/` (as telas vestidas pelo Auth Designer,
  redeclaração de `$layout`), `wikis/specs/feature/identidade-visual-da-organizacao/` (cor e logo por
  organização, ADR-01 "uma cor e uma logo"), `wikis/specs/feat/logo-dark-mode/` (override do partial
  `media`), `wikis/specs/feat/settings-do-kit/` (banco vence o `.env`)
- **Motivo**: o kit veste as telas de autenticação com um layout fixo nos providers; não há como o
  admin da aplicação mudar a aparência, nem uma organização ter a sua
- **Toca infra compartilhada?**: **sim** — todas as telas de autenticação dos três painéis. Regressão
  obrigatória: `tests/Kit/TelasDeAutenticacaoTest.php`, `tests/Kit/IdentidadeDoKitTest.php`,
  `tests/Kit/LogoDarkModeTest.php`, `tests/Kit/BloqueioDeSessaoTest.php`,
  `tests/Kit/TelasSemDockNemChatTest.php`, `tests/Kit/ConfiguracoesDoKitTest.php`,
  `tests/Kit/ConfiguracoesSecoesColapsaveisTest.php`, `tests/Kit/KitUpdateTest.php`,
  `tests/Tenancy/IdentidadeVisualTenancyTest.php`, `tests/Browser/RodapeNaDobraTest.php` e
  `tests/Browser/TelasDoKitTest.php`

## Cobertura do Requisito

| RQ | Cláusula | Passo(s) que atende(m) | Observação |
|----|----------|------------------------|------------|
| RQ-01 | tela de login melhor | 2, 3 | layout `painel` |
| RQ-02 | avaliar a página de referência | — | processo: `03` › Auditoria Pré-Implementação |
| RQ-03 | analisar a implementação de referência | — | processo: `03` › Auditoria Pré-Implementação |
| RQ-04 | pacote × camada no kit decidido antes | — | processo: ADR-01 do `02` |
| RQ-05 | Codex como auxiliar | — | processo: `03` › Despachos |
| RQ-06 | branch própria | — | processo: `feat/aparencia-das-telas-de-acesso` |
| RQ-07 | não quebrar quem usa o kit | 1, 2, 3, 4 | P-01: `null` = clássico intacto |
| RQ-08 | cenários ao final | — | processo: `03` › Verificação Final (cenários do checklist) |
| RQ-09 | tela de configuração no admin | 5 | |
| RQ-10 | preview acompanhando a customização | 6 | |
| RQ-11 | uso a critério do admin | 1, 5 | layout `classico` continua opção |
| RQ-12 | nada cita outros projetos | 8 | conferência por grep no `03` |
| RQ-13 | organização usa padrão ou própria | 1, 4, 7 | |
| RQ-14 | mesmo padrão das outras features | 7 | coluna nula = herda, como cor/logo |
| RQ-15 | documentação detalhada | 8 | |
| RQ-16 | Codex juiz cego, escolha imparcial | — | processo: `03` › Despachos e Auditoria |
| P-01 | instalação existente mantém o clássico | 1, 2 | |
| P-02 | aparência vale para todas as telas | 2, 3 | |
| P-03 | os dois layouts e as opções | 1, 3, 5 | |
| P-04 | valor inválido nunca derruba | 1 | |
| P-05 | textos escapados | 3, 5 | |
| P-06 | quando vale a da organização | 2 | |
| P-07 | padrão inteira ou própria inteira | 4, 7 | |
| P-08 | preview inerte e autorizado | 6 | |
| P-09 | faixa no celular, rodapé na dobra | 3 | |
| P-10 | inválido recusado na tela | 5 | |
| P-11 | teto após trim, padrões | 1, 5 | |
| P-12 | voltar à padrão descarta; nova cópia | 7 | |
| P-13 | respostas do preview | 6 | |
| P-14 | clássico = configuração anterior | 1, 2 | |
| P-15 | fundo de arte também no bloqueio | 2 | step 9 |
| P-16 | nada além do que a tela mostra | 2 | step 9 |
| P-17 | preview exige admin e reautoriza | 6 | step 9 |
| P-18 | texto escuro sobre cor clara | 3 | step 9 |
| RQ-17 | resolver o PR, merge com tag e 4 cenários | — | processo: `03` › Verificação Final e Adendo 1 |
| RQ-18 | capturas a mais no site | 10 | arte de fundo, tema escuro, editor da organização |
| RQ-19 | frase e apoio editáveis | 5, 10 | já existiam; o editor ganha exemplo no placeholder |
| RQ-20 | logo ou imagem no meio | 10 | destaque central |
| RQ-21 | ilustração em movimento ou parada | 10 | rede de pontos |
| RQ-22 | sugestão da referência, tudo editável | 10 | cada elemento do painel é opção |
| RQ-23 | tamanho e lado do formulário | 10 | `mediaSize` 30–70 e lado |
| RQ-24 | celular só com a marca | 10 | faixa só com logo ou nome |
| P-19 | largura 30–70 em passos de 5 | 10 | Adendo 1 |
| P-20 | destaque nenhum/logo/imagem | 10 | Adendo 1 |
| P-21 | rede, movimento com pausa, cor | 10 | Adendo 1 |
| P-22 | celular só logo ou nome | 10 | Adendo 1 |
| P-23 | aparência antiga continua igual | 10 | Adendo 1 |
| P-24 | imagem só em `telas-de-acesso/` | 10 | Adendo 1 |
| RQ-25 | uma configuração por tela | 11 | Adendo 2 |
| RQ-26 | lado diferente por tela | 11 | Adendo 2 |
| RQ-27 | textos formatados como o rodapé | 11 | Adendo 2 |
| RQ-28 | cor padrão do Filament | 11 | Adendo 2 |
| RQ-29 | gradiente | 11 | Adendo 2 |
| RQ-30 | celular com arte sem sobreposição | 11 | Adendo 2 |
| RQ-31 | login social no painel | 11 | Adendo 3 |
| RQ-32 | anti-robô no painel | 11 | Adendo 3 |
| RQ-33 | testes de social e anti-robô | 11 | Adendo 3 |
| RQ-34 | troca de tela animada | 12 | Adendo 4; P-32, P-33 |
| RQ-35 | só o painel desliza para o outro lado | 12 | Adendo 4; P-34 |
| RQ-36 | o formulário aparece por fade-in | 12 | Adendo 4; P-34 |
| P-25 | login na raiz, telas próprias em `telas` | 11 | Adendo 2 |
| P-26 | família pela classe da página | 11 | Adendo 2 |
| P-27 | Markdown seguro, teto visível, legado puro | 11 | Adendo 2 |
| P-28 | cor primária ou própria | 11 | Adendo 2 |
| P-29 | gradiente fechado e superfície escura | 11 | Adendo 2 |
| P-30 | faixa do celular sem a arte | 11 | Adendo 2 |

## Objetivo

Dar ao admin da aplicação uma aba **Telas de acesso** em Configurações, com preview, para escolher
entre o layout atual (`clássico`, inalterado) e um layout novo (`painel`): coluna do formulário +
painel de marca arredondado, com cor ou arte de fundo, logo e duas frases opcionais, que no celular
vira uma faixa de marca em vez de sumir. Com tenancy, cada organização usa a aparência padrão ou a
própria, no mesmo formulário da organização. Nada muda para quem atualiza o kit até alguém escolher.

## Análise do Existente

- O plugin é configurado com valores fixos nos três providers
  (`app/Providers/Filament/AppPanelProvider.php:AuthDesignerPlugin:262`); o layout lê
  `$livewire->getAuthDesignerConfig()` no render
  (`vendor/caresome/filament-auth-designer/resources/views/components/layouts/auth.blade.php:getAuthDesignerConfig:6`)
- `AuthDesignerConfig` é `final readonly`
  (`vendor/caresome/filament-auth-designer/src/Data/AuthDesignerConfig.php:readonly:11`); `TelaBloqueio`
  já reconstrói o objeto por request (`app/Filament/Pages/Auth/TelaBloqueio.php:configComLogoDaOrganizacao():144`)
- O partial `media` já é override autoral do kit
  (`resources/views/vendor/filament-auth-designer/components/partials/media.blade.php`) e só é incluído
  quando há mídia (`auth.blade.php:hasMedia:30`)
- Settings: propriedade + linha em `mapaDeConfiguracao()` + migration nova
  (`app/Settings/ConfiguracoesDoKit.php:mapaDeConfiguracao():483`); a página monta abas pelos helpers
  `aba()`/`secao()` (`app/Filament/Admin/Pages/ConfiguracoesDoKit.php:aba():328`)
- Organização: colunas nulas herdam a instalação (`app/Support/IdentidadeDoKit.php:logosPara():98`,
  `app/Support/CorPrimaria.php:resolver():80`); formulário em
  `app/Filament/Admin/Resources/Tenants/Schemas/TenantForm.php`
- `?org=` chega ao login pela query (`app/Filament/Pages/Auth/TelaLogin.php:orgDoPedido():168`), e
  some no POST do Livewire
- `search-docs` indisponível nesta sessão (MCP `laravel-boost` sem conexão): APIs conferidas no
  vendor instalado (Filament 5.10.2, auth-designer 3.1.0, Livewire 4)

## Decisões de Desenho

- Dois layouts só (`classico`, `painel`); cartão/cobertura ficam fora até haver pedido (juiz cego,
  item 2)
- Fundo do painel: cor (primária da instalação/organização, ou hex próprio) ou a arte já
  configurada em Identidade; a imagem própria do destaque central (Adendo 1) é o único upload do editor, só PNG/JPEG/WebP em `telas-de-acesso/`, e o preview a mostra antes de salvar pela URL temporária do Livewire *(alterado em 2026-11-14: Adendo 1)*
- Recuperar senha, redefinição, verificação de e-mail e 2FA usam a aparência **global**; só login,
  cadastro e bloqueio conhecem a organização (sem contexto confiável nas demais)
- Preview sem alternador de tema: trocar o tema no iframe mudaria a preferência do próprio admin
  (mesmo `localStorage`)
- Preview roda no contexto do painel `admin`: a cor dos botões do formulário é a do admin; o painel de
  marca usa a cor resolvida do escopo. Limite declarado na tela e na doc
- A aparência própria da organização nasce como cópia do global ao desligar "Usar a aparência
  padrão" e **não acompanha** mudanças posteriores do global — dito no `helperText`
- `search-docs` sem conexão: degradação registrada no `03`

## Channel de log

`autenticacao` (existente) e `tenancy` (existente). Logs novos:

- `AparenciaDasTelasDeAcesso@normalizar` — `warning` quando o valor gravado é inválido e cai no
  clássico: `[AparenciaDasTelasDeAcesso@normalizar] Aparencia invalida descartada | origem: {global|tenant:id} - motivo: {campo}`
- `VesteAparenciaDasTelas@organizacaoDaAparencia` — `debug` no `tenancy`:
  `[VesteAparenciaDasTelas@organizacaoDaAparencia] Organizacao da aparencia resolvida | tenant: {id|nenhum} - motivo: {sem_tenancy|painel_sem_tenancy|org_ausente|org_inativa|ok}`
- `PreviewDasTelasDeAcesso@mount` — `warning` quando o rascunho não existe, expirou ou o escopo é
  negado: `[PreviewDasTelasDeAcesso@mount] Preview recusado | user: {id} - motivo: {rascunho_ausente|escopo_negado}`
- Contexto: `user_id`, `tenant_id`, `escopo`, `motivo` — nunca o conteúdo da aparência

## Autorização

- Aba global: a mesma da página de configurações (`ExigePermissaoDaTela`, `canEdit() = canAccess()`)
- Organização: o formulário já é autorizado pela `TenantPolicy` (`update`/`create`)
- Preview: rota do kit sob `panel:admin` + `auth`; a página exige `canAccessPanel(admin)` (só quem entra no `/admin`, P-17) *(alterado em 2026-11-14: QA-01/QA-12 — o `Authenticate` do Filament rodava antes do `SetUpPanel:admin` e decidia pelo painel default)*;
  o `mount()` exige rascunho do **próprio usuário** e autoriza o escopo gravado nele (configurações para
  `global`, `update` da organização, `create` para organização nova), e a autorização é refeita a cada
  request. Sem rascunho: 404; escopo negado: 403 *(alterado em 2026-11-13: SEC-02/SEC-03, BP-35)*

## Rotas

Uma rota nova, a da página de preview, `/admin/preview-das-telas-de-acesso` (nome
`kit.preview-das-telas-de-acesso`), registrada no `KitServiceProvider` como as rotas do login unificado:
a página é uma `SimplePage` com o layout das telas de autenticação, que o Filament não descobre como página
do painel. Nada em `routes/web.php`. *(alterado em 2026-11-13: BP-34)*

## Superfície de UI

| Tela | Elemento | Onde |
|---|---|---|
| `/admin/configuracoes-da-aplicacao?tab=telas-de-acesso` | aba **Telas de acesso**: layout, lado, fundo, cor, frase, texto de apoio, logo no painel, alternador de tema; preview em iframe com seletor de tela/largura e botão **Atualizar preview** | `ConfiguracoesDoKit` (página) |
| `/admin/tenants/{id}/edit` e `/create` | seção **Telas de acesso**: toggle "Usar a aparência padrão" + o mesmo editor e preview | `TenantForm` |
| `/admin/preview-das-telas-de-acesso` | documento renderizado no iframe: login/recuperar senha/cadastro demonstrativos, inertes | `PreviewDasTelasDeAcesso` |
| login, recuperar senha, redefinição, cadastro, verificação, 2FA, bloqueio (3 painéis + `/login`, `/cadastro`, `/esqueci-minha-senha`) | layout aplicado | páginas de `app/Filament/Pages/Auth` |

## Variáveis de Ambiente

Nenhuma. `config/kit.php` ganha `identidade.aparencia_das_telas` = `null` (sem `env()`: estrutura
não cabe em `.env`; o banco governa).

## Eventos, Jobs

Nenhum. A auditoria das configurações já registra o salvamento (listener existente).

## Modelo de Execução

Um request. A aparência é lida da config (já sobreposta pelo banco no boot) e da organização
resolvida, memoizada por instância da página. Rascunho do preview em cache (TTL 30 min).

## Impacto em Features Existentes

- Com `null` (default) as telas saem como hoje: o `getAuthDesignerConfig()` devolve o objeto do pai
- `TelaBloqueio` passa a aplicar a aparência depois da troca de logo
- Redefinição de senha e verificação de e-mail ganham classes do kit (antes do vendor), registradas
  nos três providers — sem mudança de comportamento
- Formulário da organização ganha uma seção

## Rollback

Reverter o commit; a migration de settings tem `down()`; a coluna de `tenants` tem `down()`.

## Dependências

Nenhuma nova.

## Riscos

- CSS do layout `painel` depende das classes do vendor (`.fi-auth-media-section`,
  `.fi-auth-media-wrapper`); upgrade do auth-designer pode mexer — guarda por teste que lê o CSS do vendor
- Telas baixas no celular: a faixa de marca empurra o formulário; altura limitada no CSS e medida em CT-B

## Estrutura de Implementação

### 1. `AparenciaDasTelasDeAcesso` (decisor) + config + settings

> Skills: `laravel-best-practices`

- **Path**: `app/Support/AparenciaDasTelasDeAcesso.php`, `config/kit.php`,
  `app/Settings/ConfiguracoesDoKit.php`, `database/settings/2026_11_13_100000_add_aparencia_das_telas_to_kit_settings.php`
- `normalizar(mixed $bruto, string $origem): ?array` — vocabulário fechado: `layout` ∈ {classico,
  painel}; `lado_do_formulario` ∈ {esquerda, direita}; `fundo` ∈ {cor, arte}; `cor` = `#RRGGBB` ou
  nulo; `frase` ≤ 80; `apoio` ≤ 160; `logo_no_painel` bool; `alternador_de_tema` bool. Campo inválido
  cai no default do campo; `layout` inválido ou ausente → `null` (clássico) com `warning`. Nunca lança
- `global(): ?array`, `daOrganizacao(?Tenant): ?array` (coluna nula → global)
- `padraoDoPainel(): array` — o objeto inicial (layout `painel`, formulário à direita, fundo cor,
  logo ligada, alternador ligado)
- `aplicar(AuthDesignerConfig $base, ?array $aparencia): AuthDesignerConfig` — `null`/clássico devolve
  `$base` (o mesmo objeto); `painel` reconstrói com `mediaSize` = a largura escolhida (30–70 %, padrão 50 %) *(alterado em 2026-11-14: Adendo 1)*, posição pelo lado, mídia = a arte
  da identidade, lida no request (P-15), blur 0, alternador conforme a opção, hooks preservados *(alterado em 2026-11-13: P-15, QA-05)*
- `corDoPainel(?array, ?Tenant): string` — sempre uma cor concreta: o `#RRGGBB` da aparência, senão a primária da
  organização, senão a da instalação (hex, com `#rgb` expandido, ou a constante `oklch` da paleta), senão o âmbar 600
  do Filament; `fundoClaro()` escolhe o texto de maior contraste (P-18) *(alterado em 2026-11-14: QA-04, QA-10, QA-11, QA-12)*
- Settings: propriedade `aparencia_das_telas` (`?array`), linha no mapa
  (`identidade.aparencia_das_telas`), migration nova com `null` e limpeza do cache na ida e na volta
- **Atende**: RQ-07, RQ-11, RQ-13, P-01

### 2. Páginas de autenticação vestidas

> Skills: `filament-development`

- **Path**: `app/Filament/Concerns/VesteAparenciaDasTelas.php` (trait novo, ao lado dos demais concerns do kit *(alterado em 2026-11-13: BP-09)*),
  `TelaLogin`, `TelaRecuperarSenha`, `RegistroPorConvite`, `TelaDoisFatores`, `TelaBloqueio`; classes
  novas `TelaRedefinirSenha` (estende `ResetPassword` do pacote) e `TelaVerificacaoDeEmail` (estende
  `EmailVerification` do pacote), cada uma redeclarando `$layout`; providers dos três painéis passam
  `usingResetPage(TelaRedefinirSenha::class)` e `usingPage(TelaVerificacaoDeEmail::class)`
- O trait dá `aparenciaDaTela(): ?array` (memo), `organizacaoDaAparencia(): ?Tenant` (padrão `null`)
  e `aplicarAparencia(AuthDesignerConfig): AuthDesignerConfig`; cada página sobrescreve
  `getAuthDesignerConfig()` numa linha
- `TelaLogin`: `#[Locked] public ?string $orgDaAparencia`, preenchido no `mount()` pelo `?org=`;
  `organizacaoDaAparencia()` só resolve com tenancy ligada, painel `app` e organização **ativa** (sem
  exigir cadastro aberto)
- `RegistroPorConvite`: a organização já resolvida no `mount()` (ou a do convite); `TelaBloqueio`: a da sessão,
  com a guarda de tenancy acrescentada; no `painel` o bloqueio parte da configuração base, sem trocar a mídia pela
  logo (P-15) *(alterado em 2026-11-13: RD-01/CX-02)*
- `corDoPainelDeMarca()`/`logosDoPainelDeMarca()` saem vazias fora do `painel` (e as logos, com a logo desligada) — P-16
- `TelaVerificacaoDeEmail` só no `/app`, o único painel com a rota no ar *(alterado em 2026-11-13: BP-13)*
- **Atende**: RQ-01, RQ-07, RQ-13, P-02
- **Logs**: o `debug` de resolução no `tenancy`

### 3. Painel de marca (partial + CSS)

> Skills: `tailwindcss-development` (só para consulta: o CSS é escrito à mão em `kit.css`)

- **Path**: `resources/views/vendor/filament-auth-designer/components/partials/media.blade.php`
  (novo ramo no topo), `resources/views/filament/auth/painel-de-marca.blade.php` (novo),
  `resources/css/filament/kit.css` (bloco `.kit-painel-de-marca`), `public/css/kit/*` via `filament:assets`
- Com `aparenciaDaTela()['layout'] === 'painel'`, o partial renderiza o painel de marca: fundo cor
  (`style="--kit-painel-fundo: #RRGGBB"`, hex validado) ou arte (`<img>` cover), logo (par
  claro/escuro por `IdentidadeDoKit::logosPara()`, alt = nome da aplicação), `frase` (`<p>`, nunca
  maior que o título do formulário), `apoio`; tudo escapado
- CSS: moldura de 8px, raio 16px, overlay só sobre arte, contraste do texto garantido por fundo
  sólido ou overlay; abaixo de 1024px o painel vira faixa de até 112px acima do formulário
  só com a logo ou o nome (P-22: frase, apoio, destaque e ilustração somem) *(alterado em 2026-11-14: Adendo 1)*, reservando à direita o espaço do alternador de tema; cor em que o texto escuro tem mais contraste que o branco (inclusive a primária default)
  passa o texto a escuro (P-18) *(alterado em 2026-11-13: BP-23)*
- **Atende**: RQ-01, RQ-07

### 4. Coluna da organização

> Skills: `laravel-best-practices`

- **Path**: `database/migrations/2026_11_13_100000_add_aparencia_das_telas_to_tenants_table.php`,
  `app/Models/Tenant.php` (fillable + cast `array`)
- Coluna `aparencia_das_telas` JSON nula. Nula = usa a aparência padrão
- **Atende**: RQ-13, RQ-14

### 5. Aba **Telas de acesso** em Configurações

> Skills: `filament-development`

- **Path**: `app/Filament/Admin/Pages/ConfiguracoesDoKit.php`,
  `app/Filament/Admin/Schemas/EditorDeAparenciaDasTelas.php` (componentes compartilhados; teto dos textos por regra
  própria, medido depois do trim; a cor oculta com fundo de arte continua gravada)
- Aba pelo helper `aba('telas-de-acesso', …)`, duas seções pelo helper `secao()`: **Layout** e
  **Preview**. Select de layout com `classico` explícito ("Clássico — como sempre foi"); campos do
  `painel` visíveis só com ele; validação no servidor igual ao vocabulário do passo 1
- **Atende**: RQ-09, RQ-11

### 6. Preview

> Skills: `filament-development`

- **Path**: `app/Filament/Pages/Auth/PreviewDasTelasDeAcesso.php`, `resources/views/filament/pages/auth/botao-de-demonstracao.blade.php`,
  `resources/views/filament/admin/preview-das-telas-de-acesso.blade.php` (iframe e controles), rota no `KitServiceProvider`
  *(alterado em 2026-11-13: BP-34)*
- Cada render do editor grava `{escopo, aparencia normalizada}` no cache `aparencia-preview:{user}:{id do componente
  Livewire}` (30 min); a URL leva o hash do rascunho, e o iframe recarrega quando ele muda. O botão **Atualizar preview**
  (na view, sem Action) re-renderiza e recarrega o iframe. Seletor de tela (login, recuperar senha, cadastro) e de
  largura: o quadro tem o tamanho de uma tela real (1440×900 ou 390×844) e é escalado para caber
  *(alterado em 2026-11-13: BP-37/RD-05 e o preview que caía no layout de celular em "Computador")*
- A página: `$layout` do auth-designer redeclarado, `#[Locked]` em `rascunho`, `tela`; `mount()` lê o
  rascunho do usuário, autoriza o escopo, aborta 404 sem ele; formulários demonstrativos desabilitados,
  sem ação, sem hooks de login; alternador de tema desligado
- **Atende**: RQ-10, RQ-11
- **Logs**: `warning` de preview recusado

### 7. Seção **Telas de acesso** no formulário da organização

> Skills: `filament-development`

- **Path**: `app/Filament/Admin/Resources/Tenants/Schemas/TenantForm.php`
- Toggle "Usar a aparência padrão" (não desidratado): ligado grava `null`; ao desligar, preenche o
  editor com a cópia da padrão vigente — com a padrão nula, a cópia é o clássico (P-07) — também sem recarregar a
  página (P-12). Mesmo editor e preview do passo 5/6, com escopo da organização
  *(alterado em 2026-11-13: CX-03, BP-45)*
- **Atende**: RQ-13, RQ-14

### 8. Documentação

- **Path**: `docs/{pt,en}/autenticacao/aparencia-das-telas.md` (nova, detalhada, com capturas),
  índice/navegação do site, `docs/{pt,en}/recursos/configuracoes-do-kit.md`,
  `docs/{pt,en}/recursos/multi-tenancy.md`, `README.md`, `README.en.md`, `CHANGELOG.md`
  (`[Unreleased]`), `art/` (capturas novas via `tests/BrowserTenancy/CapturaDeArteTest.php`)
- Nenhuma menção a outro projeto ou URL externa (grep na Verificação Final)
- **Atende**: RQ-12, RQ-15

### 9. Testes e cenários

- CT do `04` pelo `fw-executor-ct`; CT-B do `05` pelo `fw-executor-ctb`
- Os 4 cenários de `wikis/checklist-de-release.md` sobre o commit final
- **Atende**: RQ-07, RQ-08, RQ-01, RQ-09, RQ-10, RQ-11, RQ-13, RQ-14, P-01, P-02

### 10. Adendo 1 — largura, destaque, ilustração, celular

- **Decisor** (`app/Support/AparenciaDasTelasDeAcesso.php`): campos `tamanho_do_painel`
  (`TAMANHOS_DO_PAINEL`, 30–70, vira `mediaSize`), `destaque`, `imagem_do_destaque`
  (`caminhoDaImagem()`, só `telas-de-acesso/*.png|jpg|jpeg|webp`), `tamanho_do_destaque`,
  `ilustracao`, `movimento`, `cor_da_ilustracao`; `urlDaImagem()` (disco público, `warning` se sumiu);
  `rede()` (desenho fixo, duas ligações por ponto). Padrões reproduzem o painel anterior (P-23)
- **Trait** (`VesteAparenciaDasTelas`): `destaqueDoPainelDeMarca()` público, vazio fora do painel
  (P-16); `urlDaImagemDoDestaque()` protegido, sobrescrito pelo preview
- **Editor**: campos agrupados em `Fieldset` (Disposição, Fundo, Textos, Logo e destaque central,
  Ilustração); upload só PNG/JPEG/WebP no disco público; campos ocultos desidratados (ADR-02)
- **Preview**: o rascunho guarda `imagem_temporaria` (URL assinada do Livewire, só PNG/JPEG/WebP);
  a página a prefere à gravada
- **View e CSS**: topo/meio/base; rede em SVG com componente Alpine (pausa, `prefers-reduced-motion`,
  aba oculta); abaixo de 1024 px some tudo menos o topo
- **Docs** pt/en, CHANGELOG e capturas novas (`art/telas-de-acesso-{painel-arte,painel-escuro,organizacao}.png`)

### 11. Adendo 2 e 3 — por tela, textos formatados, cor/gradiente, social e anti-robô

- **Decisor**: `normalizar()` devolve o login na raiz e `telas.{cadastro,recuperacao}` (sem
  recursão; `igual_ao_login` = segue o login; `{"layout":"classico"}` explícito); `daFamilia()`
  escolhe a tela; `normalizarTela()` é a normalização de antes, com `gradiente_*` e
  `formato_dos_textos`; `textoComoHtml()`/`textoVisivel()` (Markdown seguro do rodapé, só
  `p/br/strong/em/del/a`); `gradienteDoPainel()` e `primariaEm()` (tons 600/900)
- **Trait**: `familiaDaAparencia()` (login por padrão; `RegistroPorConvite` = cadastro;
  `TelaRecuperarSenha`/`TelaRedefinirSenha` = recuperação; preview pela tela escolhida);
  `gradienteDoPainelDeMarca()` com o gate P-16
- **Editor**: seções colapsáveis Login / Cadastro / Esqueci a senha (na organização, sem "Esqueci a senha", P-31) *(alterado em 2026-11-14: QA-31 — abas aninhadas violavam a convenção da tela de configurações)*; "Igual ao login" copia o login ao desligar;
  `MarkdownEditor` com a barra do rodapé; `origem_da_cor` (primária × própria); gradiente; o
  `layout` é obrigatório só na raiz (CT-20 × CT-28)
- **View/CSS**: textos em `div` com o HTML seguro; `--gradiente` com superfície escura; faixa do
  celular sem a arte
- **Social e anti-robô**: sem mudança de código — o formulário é o mesmo em qualquer layout; os
  CT-71/72 e o CT-B17 provam

### 12. Adendo 4 — transição entre as telas

- **Decisor**: `transicao_entre_telas` no vocabulário (`padraoDoPainel()`, `normalizarTela()`), e
  `normalizar()` copia a do login para as telas próprias no painel (P-32)
- **Editor**: o interruptor "Transição entre as telas" só na seção Login (`camposDaTela(raiz: true)`)
- **View**: a página que participa empurra para o `<head>` a regra `@view-transition` dentro de
  `@media (min-width: 1024px) and (prefers-reduced-motion: no-preference)` e marca o painel com
  `data-kit-transicao` (P-33)
- **CSS** (`kit.css` e o espelho publicado): `view-transition-name` no `.fi-auth-media-wrapper`
  (`kit-painel`) e no `.fi-auth-form-container` (`kit-formulario`); o grupo do formulário sem
  animação, o antigo invisível e o novo com fade-in depois do início do deslize (P-34)
- **Testes**: CT-98..CT-101 e CT-B19 (prova pelo `pageswap` da página de origem)
- **Arte e docs**: `telas-de-acesso-transicao.gif` (quadros do meio reconstruídos — o screenshot do
  Pest adianta as animações até o fim); seção "Transição entre as telas" nas docs pt/en

## Verificação

`vendor/bin/pint --dirty --format agent`, `vendor/bin/phpstan analyse`, `vendor/bin/filacheck --fix`,
`php artisan filament:assets`, os arquivos de teste novos, a regressão de *Natureza da Wiki*, Kit +
Tenancy completos e os 4 cenários.

## Commit

`:sparkles: feat(auth): aparência das telas de acesso personalizável, com preview e por organização`
