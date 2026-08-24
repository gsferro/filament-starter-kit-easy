# Relatório de QA — página de boas-vindas na rota `/`

> Requisito: `00-requisito.md` · Plano: `01-plano-acao.md`
> Perfil de esforço: **completo** — UI com JavaScript (tema por Alpine + `localStorage`) **e**
> domínio sensível (rota anônima cuja config é vizinha das credenciais do administrador)
> Natureza da wiki: **nova**, sem infra compartilhada → **regressão não obrigatória**

## Veredito — Ciclo 1

**APROVADO COM DÉBITO**

- Blocker: **0** · Major: **0** · Minor: **2** · Cosmético: **0**
- **QA-01 fechado no mesmo ciclo** (destino 3 → CT-B02 escrito, `05` corrigido, 3 de 3 cenários de
  navegador verdes). QA-02 fica como débito, com destino 4 — não é defeito desta feature.
- **Loop encerrado em 1 ciclo**: um segundo ciclo não teria achado novo. QA-01 era o único achado
  acionável, o fechamento dele acrescentou **cenário de teste**, não superfície de produto, e a
  regra de convergência da skill manda re-revisar só quando o fechamento cria superfície nova.
- Ambiente: requests pelo kernel (`$this->get('/')`) e navegador via `pest-plugin-browser`
  (servidor in-process). Pest **5.1**, Filament **5.7.6**, `harvirsidhu/filament-cards` **1.0.9**
- MCP: **Playwright MCP proibido nesta sessão** (instância única compartilhada com outros agentes).
  Laravel Boost MCP **indisponível** — ver `03-progresso.md` → "Degradações de ferramenta declaradas"

## Achados

### QA-01 — o CT-B não tinha oráculo de cor: `inDarkMode()->assertSee()` não valida tema · Minor · destino 3

- **Dimensão**: G (tema e cor) e K (adequação da suíte)
- **Relacionado a**: RQ-11, CT-B01
- **Esperado**: RQ-11 diz que a página herda "o darkmode já implemetado no starter-kit". O oráculo
  disso é legibilidade sob tema escuro.
- **Observado**: CT-B01 fazia `visit('/')->inDarkMode()->assertSee(...)`. `.ai/rules/testes-browser.md`
  é explícito: *"`assertSee` não valida tema — passa com texto branco em fundo branco"*. O cenário
  provava que a página **abre** sob `prefers-color-scheme: dark`, e nada sobre cor. RQ-11 ficava com
  oráculo apenas estático (CT-14, presença do `<script>` de tema).
- **Repro**: leitura do `05` e de `tests/Browser/BoasVindasTest.php` do ciclo 1. Nenhum cenário
  chamava `assertNoAccessibilityIssues()`, que é o nível 2 de verificação de cor que a própria skill
  de QA prescreve e que o kit já usa em `tests/Browser/TemaEscuroTest.php`.
- **Evidência**: `05-casos-de-teste-browser.md` → "cogitados e cortados", linha
  `assertNoAccessibilityIssues()`, cortada com o argumento "nenhum mutante previsto morre com ele".
  O argumento estava errado: **M30** ("o tema é forçado em claro") e um defeito de contraste próprio
  não são o mesmo mutante, e só o segundo precisa do axe.
- **Destino**: **3 — teste**
- **Ação exigida**: acrescentar CT-B02 com `assertNoAccessibilityIssues()` nos dois temas,
  declarando o modo em cada cenário (`.ai/rules/testes-browser.md` mediu que `inDarkMode()` **vaza**
  para o cenário seguinte e produz quatro achados falsos de contraste).
- **Status: FECHADO.** CT-B02 especificado no `05` (com os mutantes M32/M33 e o estouro de teto
  declarado) e implementado em `tests/Browser/BoasVindasTest.php`. Resultado: **3 cenários, 3
  verdes, 10 asserções** — nenhum achado de acessibilidade em nenhum dos dois temas. A ordem foi a
  que o destino 3 exige: o `05` primeiro, o teste depois; e o teste **passou de primeira**, o que é
  o resultado esperado quando o achado era de lacuna de oráculo e não de defeito de cor.

### QA-02 — `config/kit.php` lê rótulo de organização com `env()` direto, e valor vazio engole o default · Minor · destino 4

- **Dimensão**: B (fronteiras e dados)
- **Relacionado a**: RQ-07, CT-07 (linhas de rótulo)
- **Esperado**: `config/kit.php` promete `env('KIT_TENANCY_LABEL', 'Organização')` — default quando
  a chave não está definida.
- **Observado**: `KIT_TENANCY_LABEL=` (presente, valor vazio — o que sobra quando alguém apaga o
  texto e esquece o `=`) faz `env()` devolver string vazia, e o segundo argumento **nunca** entra.
  A página então exibe `" / Organizações"`, com o singular em branco.
- **É o padrão que `.ai/rules/config.md` documenta**, e que já custou cinco chaves a este kit —
  uma delas apagando a trilha de exceções inteira. Ali o remédio é `App\Support\NumeroDoEnv`, que
  é para número; **não existe o equivalente para string** no kit.
- **Repro medida**, não inferida:

  ```
  $ php artisan config:show kit.tenancy.label
  kit.tenancy.label .. Organização              # chave AUSENTE: o default entra

  $ KIT_TENANCY_LABEL= php artisan tinker --execute \
      'var_dump(env("KIT_TENANCY_LABEL"), config("kit.tenancy.label"));'
  string(0) ""
  string(0) ""                                   # chave VAZIA: o default NÃO entra
  ```

- **Varredura do padrão** — `.ai/rules/specs.md` manda varrer antes de consertar um ponto, e a
  varredura muda a leitura do achado. `grep -n "env('KIT_" config/kit.php` mostra que as chaves
  **numéricas** já estão guardadas (`NumeroDoEnv`, `ValidadeDoConvite`) e as de **string** não:

  | Chave | Consequência do valor vazio | Gravidade real |
  |---|---|---|
  | `KIT_TENANCY_LABEL` / `_LABEL_PLURAL` (`:86-87`) | rótulo em branco na interface | cosmética — é o que esta página expõe |
  | `KIT_TENANCY_SLUG` (`:92`) | o segmento do CRUD vira vazio: `/admin/organizacoes` → `/admin/` | **alta** — muda a URL de uma tela |
  | `KIT_ADMIN_EMAIL` (`:287`) | o `UsuarioAdminSeeder` nasce com e-mail vazio | **alta** — administrador sem credencial de login |
  | `KIT_ADMIN_PASSWORD` (`:288`) | senha vazia no seeder | **alta** |
  | `KIT_REPOSITORY` (`:32`) | o `kit:update` perde a origem | média |
  | `KIT_COR_PRIMARIA` (`:53`) | **guardada** — `CorPrimaria::paleta()` trata `''` (e CT-16 assere isso) | nenhuma |

  Ou seja: o achado que esta página expôs é o **mais inofensivo** de uma família de seis, e os três
  graves não têm nada a ver com boas-vindas. É exatamente o padrão que a rule descreve — defeito de
  fronteira se espalha por cópia, e nenhum caso de teste de feature olha para a fronteira.
- **Destino**: **4 — não é defeito desta feature.** O defeito é anterior a ela e vive em
  `config/kit.php`; a página só o torna visível. Consertar aqui seria mexer em fronteira de config
  fora do escopo da entrega, e a rule do projeto manda varrer o padrão inteiro antes de tocar num
  ponto — o que é uma feature própria.
- **Ação exigida**: nenhuma nesta entrega. Registrado como débito no `03-progresso.md` e como
  candidato a `NumeroDoEnv`-para-string numa entrega própria.

## Hipóteses Rejeitadas

`.ai/rules/specs.md` pede que a rejeição seja registrada com o motivo: *"relatório sem rejeições
parece que só procurou onde achou"*. Foram três, e a primeira era a mais grave da lista.

> **Divergência declarada entre a skill e a rule do projeto.** A skill de quality gate dá teto de
> ~150 linhas ao relatório e manda "cortar detalhe, não cortar achado". Esta seção é detalhe pelo
> critério dela, e o relatório passa do teto por causa dela. **A rule do projeto vence**: ela é
> medição local (*"a rejeição costuma custar o mesmo que o achado"*), e o caminho de HR-01 —
> `RecordsCategory` do vendor não checa `canAccess()`, mas `canGloballySearch()` checa — é
> exatamente o tipo de coisa que a próxima pessoa refaria do zero se só houvesse a conclusão.

### HR-01 — a busca ⌘K na rota pública vazaria registros para o visitante anônimo

**Por que a hipótese era plausível.** A página `/` agora boota o painel `app`, e medi que
**quatro** componentes Livewire renderizam para um visitante anônimo: a própria página, as
notificações do Filament, o `assistente-chat-widget` e o **overlay de busca do spotlight**. O
`AdminPanelProvider` avisa por escrito, na linha 101, que *"as categorias do vendor NÃO checam
`canAccess()`"* — e o painel `app` registra `RecordsCategory`, que é do vendor
(`AppPanelProvider.php:178`). Leitura do vendor confirma: `RecordsCategory::search()` filtra só por
`canGloballySearch()` e chama `getGlobalSearchResults($query)` direto
(`vendor/wezlo/filament-search-spotlight/src/Categories/RecordsCategory.php:48-58`).

**Por que foi rejeitada**, por dois caminhos independentes:

1. **Leitura do vendor**: `canGloballySearch()` termina em `&& static::canAccess()`
   (`vendor/filament/filament/src/Resources/Resource/Concerns/HasGlobalSearch.php:47`). Para
   anônimo é falso em todo Resource, e o laço do `RecordsCategory` nem chega ao
   `getGlobalSearchResults()`.
2. **Medido**: montei o `SpotlightComponent` sem autenticação, com o painel `app` bootado, criei um
   usuário-alvo e busquei pelo nome dele. `groups` voltou `[]`, e a tela renderizou
   *"Nenhum resultado para …"*.

**E nenhum caso de teste foi escrito para isso** — deliberadamente. A segunda medição mostrou que a
busca devolve `[]` **também** para `master_global`, nos painéis `admin` e `app`: os Resources do kit
não declaram `$recordTitleAttribute`, então `getGloballySearchableAttributes()` é vazio e
`canGloballySearch()` é falso para todo mundo. Um CT afirmando "vazio para anônimo" passaria com o
`canAccess()` **removido** — tautologia disfarçada de cobertura de segurança, que é pior que lacuna
declarada. Fica como hipótese rejeitada, com o caminho da medição escrito, para quem ligar a busca
global no kit saber o que testar.

### HR-02 — um visitante anônimo poderia conversar com o assistente de IA na rota `/`

O `assistente-chat-widget` é montado pelo render hook `BODY_END` do painel `app`
(`AppPanelProvider.php:94-97`), e a rota `/` não tem `Authenticate` no middleware. Se a barreira
estivesse só na blade, um request Livewire anônimo chamaria `enviar()` e gastaria inferência.

**Rejeitada**: a barreira está na **ação**, não na UI —
`AssistenteChatWidget::assertContexto()` começa com `abort_unless(auth()->check(), 403)`
(`app/Livewire/AssistenteChatWidget.php:216`), e `enviar()` a chama na primeira linha. O `render()`
passa `'disponivel' => auth()->check()`, o que é a camada de UI **em cima** da barreira, não em vez
dela.

### HR-03 — a página exporia o ambiente pelo indicador de ambiente do painel

`EnvironmentIndicatorPlugin` está registrado nos três painéis, com
`->visible(fn () => ! app()->isProduction())`, e ele renderiza o nome do ambiente.

**Rejeitada**: o badge é pendurado em `SIDEBAR_LOGO_AFTER` ou `GLOBAL_SEARCH_BEFORE`
(`vendor/pxlrbt/filament-environment-indicator/src/EnvironmentIndicatorPlugin.php:215-218`), e
**nenhum dos dois existe no `layout.simple`**. O que o plugin emite na nossa página é só o bloco de
`<style>` do hook `panels::styles.after` (linhas 112-134), que contém uma borda colorida e nenhum
texto. Confirmado por CT-12, linha `app.env`, com valor sentinela plantado.

## Matriz de Rastreabilidade

Sem lacuna. Impressa por exceção — a feature é nova e a matriz é pequena o bastante para caber:

| RQ | Cláusula | Passo PRD | CT | CT-B | Código | Veredito |
|----|----------|-----------|----|------|--------|----------|
| RQ-01 | `/` serve a página do kit | 1, 3 | CT-01 | CT-B01 | `BoasVindas`, `routes/web.php` | ✅ |
| RQ-02 | substitui a welcome | 3, 4 | CT-02 | — | welcome apagada | ✅ |
| RQ-03 | cards para os painéis | 1 | CT-03, CT-04 | CT-B01 | `getCards()` | ✅ |
| RQ-04 | cards do pacote de Cards | 1 | CT-05 | CT-B01 | `extends CardsPage` | ✅ |
| RQ-05 | infos do kit | 2 | CT-07, CT-08 | CT-B01 | `informacoesDoKit()` | ✅ |
| RQ-06 | informações da config | 2 | CT-07, CT-08, CT-10, CT-11, CT-12 | — | Section "Configuração do kit" | ✅ |
| RQ-07 | dados do `kit:install` | 2 | CT-07, CT-16 | — | Section "Este projeto" | ✅ |
| RQ-08 | skill de design | 0 | — | — | `design/Main.dc.html` + artboard publicado | ✅ por artefato — **não falsificável por teste**, declarado no `04` |
| RQ-09 | estrutura nativa do Filament | 1, 2 | CT-05, CT-07 | — | `CardsPage` + `Schema`/`TextEntry` | ✅ |
| RQ-10 | herda o CSS | 1, 3 | CT-05, CT-14 | CT-B01 | `panel:app` + `kit-cards-page` | ✅ |
| RQ-11 | herda o dark mode | 1, 3 | CT-14 | CT-B01 → **CT-B02** | `panel:app` | ⚠️ **QA-01** — oráculo de cor faltava |
| RQ-12 | informações customizadas | 2 | CT-07, CT-16 | — | Section "Este projeto" | ✅ |

Nenhum passo, CT ou trecho de código existe sem `RQ` de origem — não houve *scope creep*.

## Dimensões

| # | Dimensão | Status | Observação |
|---|----------|--------|------------|
| A | Cobertura do requisito | ✅ | 12 de 12 `RQ` com rastro. RQ-08 verificada por artefato, com o motivo declarado |
| B | Fronteiras e dados | ⚠️ | cobertas: numérica (`-1/0/1/14`, CT-10), lista vazia (CT-11), ausente≠vazio≠valor (CT-16), HTML e acento em texto livre (CT-17). **QA-02** achado numa fronteira de config anterior à feature |
| C | Matriz de permissão | ✅ | a página não autoriza nada, e a ausência é decisão escrita (ADR-03). Duas personas exercidas: anônima (16 casos) e autenticada (CT-13) |
| D | Observabilidade real | ✅ | **sem log, por decisão** (ADR-05). Verificado: `grep -n "Log::" app/Filament/Pages/BoasVindas.php` → nada. Sem log não há PII em log |
| E | Performance | ✅ | **medido: zero queries** na resposta anônima de `/` (`DB::getQueryLog()` vazio). N+1 é estruturalmente impossível — a página não toca banco |
| F | UX de erro | ⏭️ | não se aplica: a página não tem formulário, ação nem caminho de erro |
| G | Tema e cor | ✅ após CT-B02 | mecanismo detectado antes de validar: o kit usa a classe `dark` no `<html>`, aplicada pelo `loadDarkMode()` que o `layout.base` do painel emite a partir de `localStorage.theme` e `prefers-color-scheme` (`base.blade.php:96-124`). Estático ✅ — o código desta feature não emite **nenhuma** classe Tailwind própria: tudo vem de componente do Filament e do `cards.css`, que já tem contraparte `dark` escrita à mão. Dinâmico: era **QA-01**, fechado por CT-B02 |
| H | Acessibilidade | ✅ após CT-B02 | o axe passa nos dois temas. Os elementos de autoria da página são um `<h1>`, três `<a>` e texto — sem armadilha de foco. Os dois **overlays** que o painel bootado monta (busca ⌘K e chat do assistente) ficam com `x-cloak` + `x-show="isOpen"`, ou seja `display: none`, e portanto fora da ordem de tabulação enquanto fechados; abertos, eles são superfície do vendor, não desta feature |
| I | Segurança da superfície nova | ✅ | IDOR: n/a (sem parâmetro). Mass assignment: n/a (sem formulário). Dado sensível: CT-12, 8 partições sentinela. Três hipóteses levantadas e **rejeitadas com evidência** (HR-01 a HR-03) |
| J | Regressão adjacente | ⏭️ | wiki **nova** sem infra compartilhada. A suíte completa de backend foi rodada de todo jeito |
| K | Adequação da suíte | ⚠️ | estático: nenhum caso sem oráculo, nenhum `assertOk()` solitário, nenhum `assertDatabaseHas` só com chave. Um oráculo fraco encontrado → **QA-01**. Duas assertions corrigidas no ciclo de implementação (`fi-sidebar` e o `assertDontSee(Amber)`), já registradas em `03-progresso.md`. Medição por `--mutate`: ver "Não Verificado" |

## Débitos Aceitos

- **QA-02** (Minor, destino 4): `config/kit.php` lê os rótulos de organização com `env()` direto, e
  valor vazio engole o default. Anterior a esta feature; a página só o torna visível. Replicado em
  `03-progresso.md`.

## Suspeitas Não Confirmadas

Nenhuma. As três suspeitas levantadas foram reproduzidas até a conclusão e viraram "Hipóteses
Rejeitadas", com o caminho da medição escrito.

## Não Verificado

- **Mutation score (`pest --mutate`)**: não rodado. Motivo honesto e duplo. (1) O ambiente não tem
  PCOV, e `.ai/rules/testes-browser.md` mediu que com Xdebug em série o `--tia` não termina
  (abortado após 35 min) — o `--mutate` tem a mesma dependência de driver. (2) A página é
  **declarativa**: `CardItem::make()->label()->url()`, `TextEntry::make()->state()`. Os operadores
  de mutação têm quase nada em que morder, e o único código com ramo — os quatro formatadores
  (`emDias`, `retencao`, `lembretes`, `corPrimaria`) — já tem cobertura de fronteira explícita em
  CT-10, CT-11 e CT-16. Um score alto aqui não absolveria nada, e a própria skill avisa que o
  indicador é **cego à omissão**, que é o que a dimensão A responde.
- **Confronto visual por Playwright MCP** (dimensão G, nível 3 — screenshot nos dois temas e o
  agente olhar): **proibido nesta sessão** por instrução do coordenador — instância única
  compartilhada com outros agentes rodando em paralelo. O nível 2 (axe por `pest-plugin-browser`)
  cobre contraste computado; o que fica sem verificação é o defeito visual que o axe não pega —
  ícone que desaparece, sombra invertida, imagem com fundo cravado. A página não tem imagem nem
  sombra própria, o que reduz a superfície, mas não a zera.
- **Inventário de elementos × cobertura do CT-B** (o confronto por `browser_snapshot`): mesma razão.
  Substituído por leitura direta: a página tem três elementos interativos (os três `<a>` dos
  cartões), e CT-03 assere o `href` dos três.
- **`app.url` na lista negra**: não tem cenário sentinela. Motivo em `04` e em
  `03-progresso.md` → "Desvios do Plano".
