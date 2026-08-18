# Pacotes — instalados, candidatos e descartados

Companheiro de [`pacotes.md`](pacotes.md). Aquele responde *"isto já existe, não escreva de novo"*.
Este responde a outra pergunta, a de quem decide o que entra: **o que mais existe lá fora, o que
vale a pena e o que já foi olhado e recusado — para não ser reavaliado do zero na próxima vez.**

> Varredura completa em 2026-08-18. Fonte bruta, plugin a plugin, em
> `wikis/specs/feature/v1-enriquecimento-kit/varredura/lote-{1..7}-*.md`.

---

## Método e limites da varredura

| | |
|---|---|
| Fonte | `filamentphp.com/plugins?v=5&price=free` — diretório oficial |
| Filtro | Filament **v5** + **gratuito**, aplicado na própria URL |
| Cobertura | páginas **1 a 61**, 9 cards por página |
| Total | **547 plugins** — bate exatamente com o contador do site |
| Fim confirmado | páginas 62 e 63 devolvem listagem vazia |
| Execução | 7 sub-agentes, um por lote de páginas |
| Classificação | `SIM` / `TALVEZ` / `NÃO`, com o perfil do kit e a lista de instalados como critério |

**Limites que o leitor precisa conhecer antes de agir sobre esta lista:**

1. **O card do diretório não expõe o nome Composer.** Os `vendor/pacote` desta página vêm do slug da
   URL do plugin ou de conhecimento prévio do ecossistema. **Confirme no README de cada plugin antes
   de `composer require`** — especialmente os de autor menos conhecido.
2. **"Compatível com v5" é declaração do autor**, não medição. O filtro do site reflete o que o autor
   marcou. Alguns plugins listados como v5 têm README ainda falando em v4.
3. **A classificação é de triagem**, feita pela descrição de uma linha. Nenhum dos 547 teve o código
   lido. Um `SIM` aqui significa "vale abrir o repositório", não "pode instalar".
4. **Nada foi instalado.** O `CLAUDE.md` do projeto proíbe mexer em dependência sem aprovação, e a
   decisão é do mantenedor. Ver ADR-07 em
   `wikis/specs/feature/v1-enriquecimento-kit/02-decisoes-arquiteturais.md`.

### Distribuição

Contado nas tabelas dos 7 lotes, não estimado:

| Classificação | Qtd. | O que significa |
|---|---|---|
| `SIM` | **140** | Infra/plataforma genérica que um kit se beneficiaria |
| `TALVEZ` | **201** | Útil, mas duplicado com o instalado, nichado ou de decisão estética |
| `NÃO` | **206** | Domínio de negócio, integração de nicho, ou **já instalado aqui** |
| **Total** | **547** | — |

Dos 341 `SIM` + `TALVEZ`, **112** sobreviveram à consolidação de duplicatas e ao corte do que não
agrega — são esses que a fila de [`pacotes-ranking.md`](pacotes-ranking.md) ordena.

---

## 1. Instalados hoje

Os 51 pacotes de `require` do `composer.json`, agrupados pelo papel que cumprem. Quem quiser o
mapeamento *funcionalidade → pacote dono* (a tabela do "não escreva de novo") continua em
[`pacotes.md`](pacotes.md).

### Núcleo

`filament/filament` · `laravel/framework` · `laravel/tinker` · `livewire/blaze` · `predis/predis`

### Autenticação, permissão e segurança

`bezhansalleh/filament-shield` · `jeffgreco13/filament-breezy` (perfil + 2FA) ·
`marjose123/filament-lockscreen` · `stechstudio/filament-impersonate` ·
`caresome/filament-auth-designer` · `anselmokossa/filament-sentinel` ·
`tapp/filament-authentication-log`

### Observabilidade e operação

`laravel/pulse` + `dotswan/filament-laravel-pulse` · `shuvroroy/filament-spatie-laravel-health` ·
`spatie/laravel-backup` + `brimham/filament-backup-monitor` ·
`croustibat/filament-jobs-monitor` · `laboiteacode/filament-logs-explorer` ·
`owen-it/laravel-auditing` + `tapp/filament-auditing` · `syriable/filament-activitylog` ·
`ssbityukov/filament-command-center` · `cms-multi/filament-clear-cache` ·
`mominalzaraa/filament-composer-release-notifier` · `laboiteacode/filament-dependency-graph` ·
`pxlrbt/filament-environment-indicator` · `mike-bronner/laravel-model-caching`

### Navegação, busca e UX de painel

`bezhansalleh/filament-panel-switch` · `wezlo/filament-search-spotlight` ·
`prodstarter/filament-notification-center` · `wallacemartinss/filament-onboarding` ·
`harvirsidhu/filament-cards` · `laboiteacode/filament-dashboard-widgets` ·
`mddev31/filament-dynamic-dashboard`

### Tabela, coluna e mídia em tela

`asmit/resized-column` · `awcodes/filament-badgeable-column` · `lara-zeus/progress` ·
`solution-forest/filament-simplelightbox`

### Gráficos e números

`leandrocfe/filament-apex-charts` · `flowframe/laravel-trend` ·
`gsferro/filament-odometer-easy` + `gsferro/odometer-easy` · `gsferro/filament-stat-plus-easy`

### Configuração

`spatie/laravel-settings` + `filament/spatie-laravel-settings-plugin`

### IA e tempo real

`laravel/ai` · `fomvasss/laravel-ai-tasks` · `laravel/reverb`

### Desenvolvimento (`require-dev`)

`pestphp/pest` 5 + plugins `laravel`, `browser`, `mutate` · `laravel/pint` · `larastan/larastan` ·
`laravel/boost` · `laravel/pail` · `laravel/pao` · `laravel-lang/common` · `nunomaduro/collision` ·
`mockery/mockery` · `fakerphp/faker`

---

## 2. Top 10 candidatos — com prós e contras

Ranqueados por **lacuna real do kit**, não por popularidade. Cada um responde a uma pergunta que
hoje o kit não responde.

> Legenda de esforço: **baixo** = instalar, registrar no painel, um teste. **médio** = migration ou
> decisão de arquitetura. **alto** = muda o modelo de dados ou a superfície pública do kit.

---

### 1. `filament/spatie-laravel-media-library-plugin` — camada de mídia

**Lacuna que fecha**: o kit **não tem camada de mídia**. Upload hoje é `FileUpload` gravando caminho
em coluna (`users.avatar_url`, `tenants.logo`), sem conversões, sem responsive images, sem coleções,
sem uma tabela que saiba o que é anexo de quê. É a maior lacuna aberta para uma 1.0.

**Prós**
- Plugin **oficial do core do Filament**, com o mesmo ciclo de release do framework — risco de
  abandono próximo de zero.
- `spatie/laravel-medialibrary` é o padrão de fato do ecossistema Laravel; qualquer dev que chegue
  no kit já sabe usar.
- Resolve de uma vez conversões, disk por coleção, ordenação e limpeza de órfão.
- Combina com o `solution-forest/filament-simplelightbox` já instalado, que hoje serve imagem sem
  ter de onde tirá-la bem organizada.

**Contras**
- Traz `spatie/laravel-medialibrary` junto: **uma migration** (`media`) e uma dependência pesada para
  quem só quer um avatar.
- Migrar `users.avatar_url` e `tenants.logo` para coleções é **breaking** para quem já instalou o kit
  — exige migration de dados e a decisão de manter ou não a coluna antiga.
- Conversões dependem de `spatie/image` e de driver de imagem (GD ou Imagick) no servidor: mais um
  requisito de ambiente para documentar no README.

**Esforço**: alto · **Veredito**: o candidato número 1, mas é decisão de arquitetura, não de plugin.
Merece wiki própria.

---

### 2. `awcodes/filament-curator` — gerenciador e seletor de mídia

**Lacuna que fecha**: a metade de UI da lacuna acima. Mesmo com media library, falta a tela em que o
operador vê, busca e reaproveita o que já subiu.

**Prós**
- Adam Weston (`awcodes`) é um dos autores mais constantes do ecossistema — mantém uma dezena de
  plugins e acompanha os majors do Filament rápido.
- Biblioteca central + `CuratorPicker` como campo: o mesmo arquivo é reaproveitado em vários
  registros em vez de subir três cópias.
- **Independente do medialibrary** — tem tabela própria. Dá para adotar o Curator *sem* tomar a
  decisão do item 1, o que o torna o caminho mais barato para fechar a lacuna de mídia.

**Contras**
- Se o item 1 for adotado depois, ficam **duas** tabelas de mídia. Curator e medialibrary resolvem o
  mesmo problema por caminhos diferentes: escolher um, não os dois.
- Tabela própria significa que o dado de mídia não é portável para fora do Filament — um job ou uma
  API teriam de conhecer o schema do Curator.

**Esforço**: médio · **Veredito**: o par 1 × 2 é uma **decisão excludente**. Curator é o caminho
rápido; medialibrary é o caminho padrão.

---

### 3. `promethys/revive` — lixeira de soft deletes

**Lacuna que fecha**: o kit usa `SoftDeletes` mas não tem tela para restaurar. Hoje um registro
apagado por engano volta por tinker.

**Prós**
- Uma tela central para **qualquer** model com `SoftDeletes`, registrada uma vez no painel `infra` —
  cabe exatamente no papel daquele painel.
- Substitui código próprio: o `mini-pff` resolveu isso com uma `Page` custom que varre `app/Models`
  por reflexão. Trocar código nosso por pacote mantido é o movimento certo.
- Escopo pequeno e legível; superfície de risco baixa.

**Contras**
- Restaurar registro apagado **ignora regra de negócio** por definição: um `Convite` restaurado pode
  voltar já expirado, um `User` pode voltar com papéis de uma organização que não existe mais.
  Precisa de policy, e a policy é trabalho do kit, não do pacote.
- Tela que lista tudo que foi apagado é, em si, exposição de dado: no kit, entra sob permissão do
  `master_global`.

**Esforço**: baixo (o pacote) / médio (as policies) · **Veredito**: forte candidato, com a policy
como parte da entrega, não como detalhe.

---

### 4. `bezhansalleh/filament-exception-viewer` — exceções no painel

**Lacuna que fecha**: o kit vê **saúde** (Health), **desempenho** (Pulse), **arquivo de log**
(LogsExplorer) e **filas** (JobsMonitor) — mas não tem uma tela que agrupe *exceções* por tipo e
frequência. Achar a exception no LogsExplorer exige saber o dia e caçar no arquivo.

**Prós**
- Bezhan Salleh é o autor do Shield e do PanelSwitch, ambos já instalados — mesma casa, mesmo padrão
  de manutenção.
- Complementa o painel `infra` no ponto exato em que ele é fraco hoje.
- Não substitui nada instalado: é informação nova, não outra vista do mesmo dado.

**Contras**
- Persistir exceção em banco cresce tabela rápido num app com um bug em loop — precisa de retenção,
  e retenção é agendamento novo no `routes/console.php`.
- Stack trace guardado em banco pode conter **dado pessoal** (parâmetro de request). Num kit que se
  preocupa com LGPD, isso exige decisão, não só instalação.

**Esforço**: baixo/médio · **Veredito**: candidato claro para o painel `infra`, com retenção junto.

---

### 5. `tapp/filament-mail-log` — trilha de e-mail enviado

**Lacuna que fecha**: o kit envia `ConviteDeAcesso` e não guarda nenhum registro. Quando o convite
"não chegou", não há como distinguir *não foi enviado* de *foi enviado e caiu no spam*.

**Prós**
- Fecha uma pergunta de suporte que **todo** app acaba fazendo, e que hoje só o log do provedor de
  e-mail responde.
- Tapp Network já entrega três pacotes usados no kit (`filament-auditing`,
  `filament-authentication-log`, e este) — consistência de autor.
- Combina direto com a feature de convites, que é a porta de entrada de usuário no kit.

**Contras**
- Guarda **corpo do e-mail** em banco por padrão. Convite tem link de aceite; outros e-mails podem ter
  dado pessoal. Retenção e recorte de conteúdo são obrigatórios, não opcionais.
- Alternativa `backstage/laravel-mails` faz mais (aberturas, cliques, bounces) e é mais pesada —
  escolher uma.

**Esforço**: baixo · **Veredito**: alto valor por linha de código, com política de retenção junto.

---

### 6. `bezhansalleh/filament-language-switch` — troca de idioma na UI

**Lacuna que fecha**: o kit é pt-BR de ponta a ponta, e o `laravel-lang/common` já está instalado
como dev. Não há como um usuário trocar o idioma da interface.

**Prós**
- Drop-in: registra no painel e aparece; nenhuma mudança de model ou migration.
- O kit já é publicado com README em inglês (`README.en.md`) e está no diretório oficial de plugins —
  é um produto com audiência internacional. Um starter kit monolíngue limita a adoção.
- Mesmo autor do Shield/PanelSwitch.

**Contras**
- O switcher é a parte fácil. A parte cara é **traduzir o kit**: hoje os rótulos estão em português
  direto no código (`'Meu perfil'`, `'Administrador Geral'`, `'Acesso ao painel /app'`), não em
  arquivos de idioma. Instalar sem essa faxina entrega um botão que troca metade da tela.
- Tocar `App\Support\Papeis` e os rótulos dos painéis é mudança transversal, com risco de quebrar
  asserção de teste que hoje casa por texto.

**Esforço**: baixo (o pacote) / **alto** (a internacionalização de verdade)
· **Veredito**: candidato **estratégico** para a 1.0, mas é projeto próprio. Não instalar sozinho.

---

### 7. `awcodes/overlook` — widget de visão geral dos recursos

**Lacuna que fecha**: o kit tem páginas hub em cartões (`HubDeAdministracao`, `HubDoNegocio`,
`HubDeInfraestrutura`) construídas com `DescobreCardsDoPainel`. O Overlook faz algo próximo — grade
de todos os resources com contagem — de forma automática.

**Prós**
- Zero configuração: descobre os resources do painel e conta.
- Pode **substituir código próprio** do kit (a concern `DescobreCardsDoPainel`) se cobrir o caso —
  é o tipo de troca que a escada do Ponytail recomenda.
- `awcodes` de novo: manutenção previsível.

**Contras**
- É **sobreposição direta** com uma feature já entregue e documentada em wiki
  (`hub-de-navegacao-em-cards`). Adotar significa desfazer trabalho existente ou conviver com dois
  jeitos de fazer a mesma tela.
- Contagem automática por resource dispara um `count()` por card. Com cache de model ligado no `/app`
  isso é barato; no `/infra` sobre tabela de auditoria, não.

**Esforço**: baixo · **Veredito**: avaliar como **substituição**, nunca como adição. Se não
substituir, é duplicidade.

---

### 8. `awcodes/filament-quick-create` — criar sem sair da tela

**Lacuna que fecha**: para criar um registro hoje é preciso navegar até o resource. O Spotlight já
oferece "Criar X" pelo ⌘K — este põe o mesmo na topbar, visível.

**Prós**
- Ganho de produtividade real e imediato, sem tocar em model nem em migration.
- Respeita `canCreate()` das policies, então não vaza affordance.
- Complementa o Spotlight instalado em vez de competir: um é teclado, o outro é mouse.

**Contras**
- **Terceiro** caminho para a mesma ação (menu lateral, ⌘K, topbar). Excesso de caminho é ruído de
  interface, e num painel com muitos resources o dropdown fica longo.
- Precisa de allow-list por painel para não listar `Role`, `AiRun` e afins.

**Esforço**: baixo · **Veredito**: bom, se vier com a lista de resources explícita por painel.

---

### 9. `laraveldaily/filacheck` — análise estática específica de Filament

**Lacuna que fecha**: o kit tem Pint e PHPStan (larastan) no CI, mas nenhum dos dois entende
Filament. Erros como `->dehydrated(false)` num campo que precisa salvar, ou `Grid` sem
`columnSpanFull()`, passam limpos pelos dois.

**Prós**
- Entra no CI ao lado do que já existe, sem mudar nada em runtime — **risco zero em produção**.
- O kit já tem `composer types:check`; este vira `composer filament:check` na mesma esteira.
- Para um starter kit, cujo produto é justamente o código-exemplo, um lint específico do framework
  vale mais que na média dos projetos.

**Contras**
- É `require-dev`, então não "enriquece o kit" para quem o usa — enriquece quem o **mantém**.
- Regra de lint discorda de decisão deliberada: o kit tem várias
  (`CommandCenterPlugin` sem `->cluster()`, `RoleResource` publicado byte a byte). Vai precisar de
  baseline ou de exclusões, e baseline mal cuidada é lint desligado com aparência de ligado.

**Esforço**: baixo · **Veredito**: o candidato de melhor relação valor/risco da lista. Começar por
ele.

---

### 10. `dododedodonl/filament-socialite` — login social / SSO

**Lacuna que fecha**: a única porta de entrada do kit é e-mail e senha, mais 2FA. Não há OAuth, nem
SSO corporativo.

**Prós**
- `laravel/socialite` é infraestrutura conhecida; o plugin só faz a ponte com o painel.
- Um starter kit que já nasce com "entrar com Google/GitHub" reduce muito o atrito de adoção.
- Convive com o fluxo de convite do kit: o convite continua sendo a autorização, o provedor vira só
  o meio de autenticar.

**Contras**
- **Conflita com o modelo de acesso do kit.** Aqui o convite é a única porta de entrada, e papel é o
  que dá painel. Login social cria a possibilidade de conta autenticada **sem papel** — que hoje
  simplesmente não existe. Exige decidir o que acontece com quem entra pelo Google sem convite.
- Traz configuração de provedor (`services.php`, callback URL, credenciais) que o `kit:install` teria
  de perguntar ou documentar.
- Alternativas de `auth` na lista (passkeys, OTP, captcha, renew password) resolvem problemas mais
  contidos com menos consequência arquitetural.

**Esforço**: médio/alto · **Veredito**: o mais **transformador** e o mais arriscado. Só depois de
decidir o que fazer com "autenticado sem convite".

---

### Resumo do Top 10

| # | Pacote | Lacuna | Esforço | Risco |
|---|---|---|---|---|
| 1 | `filament/spatie-laravel-media-library-plugin` | mídia | alto | médio |
| 2 | `awcodes/filament-curator` | mídia (UI) | médio | baixo |
| 3 | `promethys/revive` | lixeira | baixo/médio | baixo |
| 4 | `bezhansalleh/filament-exception-viewer` | exceções | baixo/médio | baixo |
| 5 | `tapp/filament-mail-log` | trilha de e-mail | baixo | baixo |
| 6 | `bezhansalleh/filament-language-switch` | i18n | alto (de verdade) | médio |
| 7 | `awcodes/overlook` | dashboard | baixo | **duplicidade** |
| 8 | `awcodes/filament-quick-create` | UX | baixo | ruído de UI |
| 9 | `laraveldaily/filacheck` | lint | baixo | **o menor** |
| 10 | `dododedodonl/filament-socialite` | auth | médio/alto | **o maior** |

**Ordem sugerida de adoção**: 9 → 5 → 4 → 3 → (1 **ou** 2) → 8 → 7 → 6 → 10.
Começa pelo que não toca runtime, sobe para observabilidade, decide mídia, e só depois mexe em
identidade.

---

## 3. Segunda linha — candidatos com mérito, fora do top 10

Agrupados pelo que resolvem. Todos classificados `SIM` na varredura.

### Autenticação e segurança

| Pacote | O que traz | Por que ficou de fora do top 10 |
|---|---|---|
| `adriaanzon/filament-passkeys` | login WebAuthn sem senha | O Breezy já entrega 2FA; passkey é o passo seguinte, não o que falta |
| `afsakar/filament-otp-login` | OTP por e-mail/SMS | Sobrepõe o 2FA existente |
| `l3aro/filament-cloudflare-turnstile` | anti-bot no login | O kit não tem registro público aberto — só convite |
| `yebor974/filament-renew-password` | expiração periódica de senha | Exigência corporativa, não de kit genérico |
| `eightcedars/filament-inactivity-guard` | logout por ociosidade | O lockscreen instalado já tem `idle_timeout` |
| `jeffersongoncalves/filament-keyable` | API keys por model | Só faz sentido quando o kit expuser API |
| Login attempts / User sessions (`smony`) | bloqueio de força bruta, sessões ativas | Complementam o `authentication-log`, que hoje só registra |

### Observabilidade

| Pacote | O que traz | Nota |
|---|---|---|
| `hugomyb/filament-error-mailer` | alerta por e-mail em exceção | Par natural do item 4 do top 10 |
| `backstage/laravel-mails` | log de e-mail com aberturas e bounces | Alternativa mais completa (e mais pesada) ao item 5 |
| `achyutn/storage-monitor` | uso de disco por partição | Encaixa ao lado do Health no `/infra` |
| `discoverydesign/filament-gaze` | mostra quem está editando o registro | Evita colisão de edição sem lock |
| Record Watcher / Diffs | assinar registro, diff visual | Complementam a auditoria instalada |

### Devtools e DX

| Pacote | O que traz | Nota |
|---|---|---|
| `bramr94/filament-developer-logins` | login de 1 clique em local | DX pura; **nunca** habilitar fora de `local` |
| `agence-twogether/filament-hooks-helper` | revela os render hooks da página | Teria economizado horas nesta própria feature |
| `niladam/filament-quick-links` | abre o Resource no PhpStorm da tabela | Barato e específico |
| `husam-tariq/filament-database-schedule` | gerencia o scheduler pelo painel | O kit tem 3 agendamentos; cresceria bem no `/infra` |
| `jeffersongoncalves/filament-one-time-operations` | migrations de dados versionadas | Resolve o problema real de "rodar isso uma vez em produção" |
| `guava/filament-mcp` | expõe o painel como servidor MCP | Casa com a trilha de IA local do kit |

### Tabela e formulário

| Pacote | O que traz |
|---|---|
| `awcodes/filament-sticky-header` | cabeçalho fixo em tabela longa |
| `defstudio/filament-column-length-limiter` | trunca texto longo com tooltip |
| `tapp/filament-value-range-filter` | filtro de intervalo reaproveitável |
| `eddie-rusinskas/filament-queueable-bulk-actions` | bulk assíncrono, evita timeout |
| `codewithdennis/filament-select-tree` | select hierárquico (categorias, permissões) |
| `awcodes/shout` | aviso contextual dentro do formulário |
| `novadaemon/filament-pretty-json` | inspeção de payload JSON |
| Table Presets / DB Table State | usuário salva combinações de filtro e coluna |
| Draft Recovery / Unsaved Changes Modal | evita perda de dados em formulário longo |

### UI e navegação

`awcodes/recently` (registros vistos recentemente) · `awcodes/light-switch` (dark mode nas telas de
auth — parcialmente coberto pelo `themeToggle()` do Auth Designer) ·
`swisnl/filament-backgrounds` (polimento da tela de login) · Collapsible Sub-Nav ·
Navigation Enhanced · Mobile Bottom Navigation (o kit **não é usável no celular** hoje) ·
`guava/filament-modal-relation-managers`

---

## 4. Descartados — e por quê

Registrado para que a próxima varredura não reavalie do zero.

### 4.1 Já resolvido por pacote instalado (~40)

Todo plugin de: activity log · auditoria · Shield/permissões · busca global · sininho de notificação ·
grade de cartões · barra de progresso · indicador de ambiente · botão de limpar cache · explorador de
logs · monitor de backup · monitor de filas · odômetro · notificador de release do Composer ·
onboarding · lockscreen · impersonate · dashboard dinâmico · lightbox · Pulse · Health.

> Antes de propor qualquer um destes, ler [`pacotes.md`](pacotes.md): a tabela lá diz qual pacote já
> é dono da funcionalidade.

### 4.2 Coberto por recurso nativo do Filament 5 ou do Laravel

| Descartado | Já existe em |
|---|---|
| Vários plugins de export CSV/XLSX | `ExportAction`/`ImportAction` nativos do Filament |
| `pxlrbt/filament-excel` | idem — só agrega em estilização avançada de planilha |
| Plugins de slug automático | `Str::slug()` no `afterStateUpdated()`, 2 linhas |
| Pikaday, Inline Date-Time Picker | `DatePicker`/`DateTimePicker` nativos |
| Plugins de tabs de layout | `Tabs` do `Filament\Schemas\Components` |
| Timezone Field | `Select` com `timezone_identifiers_list()` |
| Scroll-to-top (3 plugins concorrentes) | um render hook `BODY_END` de 20 linhas de Blade |

### 4.3 Domínio de negócio — fora do escopo de um kit genérico

E-commerce · ERP · CRM · helpdesk/ticketing · blog e CMS de conteúdo · imobiliária ·
gateways de pagamento regionais (Transbank, MercadoPago) · chat de loja · jogos · SEO · AdSense ·
gestão de assinaturas · agendamento de consultas.

### 4.4 Superfície de risco alta demais para um kit

| Descartado | Motivo |
|---|---|
| Web Terminal (3 variantes), Server Access | execução remota de shell pelo navegador. Um kit não decide isso pelo usuário |
| `.Env Editor` | expõe segredo em tela; só com policy dura e decisão consciente |
| Wildcard Login | login sem senha por domínio de e-mail |
| Auto Panel, Module Manager | geram Resource em runtime — o kit vale justamente por ser código legível e auditável |

### 4.5 Decisão estética, não técnica

~15 temas prontos (Shadcn, Clarity, Aurum, Moonlight, Neobrutalism, Bonsai, Aura, Edinburgh,
Aberdeen, Inverness…) e os seletores de paleta/fonte. O kit já tem `KIT_COR_PRIMARIA` e paleta por
organização; tema é escolha de quem usa o kit, não do kit.

### 4.6 Starter kits concorrentes

ServiceDeskKit · FilaKit · TeamKit · MFAKit · EvolutionKit · Member Management · Team Guard.
Não são dependência — são **referência de arquitetura multi-painel**, e vale olhar como resolveram
o que aqui ainda está aberto.

### 4.7 Duplicados entre si

Quando dois ou mais plugins resolvem a mesma coisa, todos foram marcados e **nenhum** escolhido no
top 10 sem que o par estivesse explícito:

- Mídia: Curator × Media Library × Uni File Manager × TinyFinder × File Manager (3 autores)
- Passkeys: Adriaan Zonnenberg × Robert Boes × Marcel Weidum
- Ícones: Tabler (2 autores) × Phosphor × Guava Icons
- Autosave: Draft Recovery × Autosave
- Timeline de atividade: La Boîte à Code × Lara Zeus × Bokshorn
- Resource lock: Androsamp × Blendbyte × Prevent Outdated Record Update × Gaze
- Comentários: Relaticle × Kirschbaum Commentions × Commentable × Lara Zeus Replies

---

## 5. O que fazer com esta página

1. Ela **não** é backlog. Nada aqui está aprovado.
2. Pacote aprovado sai daqui e vira uma feature com wiki própria em `wikis/specs/`, com seus casos de
   teste — do jeito que toda dependência entrou no kit até hoje.
3. Pacote instalado sai daqui e entra em [`pacotes.md`](pacotes.md), na tabela do "já existe".
4. Pacote recusado **fica** aqui, na seção 4, com o motivo. Motivo registrado é o que impede a
   próxima varredura de trazer o mesmo nome de volta.

---

## Onde continua

O **top 10** acima é a análise a fundo. A lista completa e ordenada — os 112 pacotes que sobraram da
triagem, do que mais agrega ao que menos, com os grupos excludentes marcados e os 435 excluídos com
motivo registrado — está em **[pacotes-ranking.md](pacotes-ranking.md)**.

Aquela é a fila de instalação. Esta é o dossiê dos finalistas.
