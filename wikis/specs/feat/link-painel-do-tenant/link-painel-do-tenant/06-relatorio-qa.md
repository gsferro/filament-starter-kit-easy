# Relatório de QA — Link de acesso ao painel da organização

> Requisito: `00-requisito.md` · Plano: `01-plano-acao.md`
> Perfil de esforço: **completo** (natureza `nova`, UI presente, domínio **sensível** — a feature
> atravessa dois portões de autorização e compõe URL com dado escrito por humano)
> Natureza da wiki: nova · Regressão obrigatória: **não** (rodada mesmo assim, ver dimensão J)

## Veredito — Ciclo 1

**REPROVADO → especificação**

- Blocker: **0** · Major: **3** · Minor: **9** · Cosmético: **2**
- Nenhum achado de **implementação**: o código entregue atende RQ-01..RQ-05 e as quatro ADRs. Os
  três Major são **texto que contradiz a árvore** — a rule `specs.md` violada e duas seções da wiki
  que descrevem um estado que não existe mais.
- Ambiente: Pest **5.0.5** · app não servido (dimensões dinâmicas feitas por componente/`GET`) ·
  Playwright MCP **indisponível** · Boost MCP **fora do ar** · driver de cobertura **ausente**
- Medições próprias: **180 casos verdes** — 44 da feature (230 asserções), 87 dos gates do kit,
  49 dos testes de tenancy adjacentes

## Achados

### QA-01 — a rule `specs.md` está violada em 13 citações da wiki, e a Verificação Final declara "aplicada" com evidência que não cobre `wikis/specs/**` · **Major** · destino 1

- **Dimensão**: L (L2 + L4) · **Relacionado a**: `.ai/rules/specs.md` → *"Citação de vendor se confere por símbolo"*; tabela `## Conformidade com Rules` do `03`
- **Esperado**: `{path}:{símbolo}:{linha}` conferido mecanicamente — `sed -n "{linha}p" {path}` contém o símbolo.
- **Observado**: o `03` declara a rule **aplicada** com a evidência *"reverificadas por `tests/Kit/CitacoesDeCodigoTest.php` (CT-26)"*. Esse gate **exclui `wikis/specs/**` por decisão explícita** (docblock do próprio arquivo), que é justamente o glob da rule. As citações da wiki nunca passaram por conferência, e 13 estão erradas:

| Citação | Onde | Linha real |
|---|---|---|
| `app/Models/Tenant.php:urlDoPainel:148` | `03:5`, `03:126`, `04:134`, `04:1191` | **164** |
| `tests/Pest.php:telasDoKit:224` | `03:213`, `04:969`, `04:1195` | **225** *(a seção do rebase diz que foi corrigida — foi só no teste)* |
| `app/Models/Tenant.php:urlDaLogo:138` → "virou `:164`" | `03:176` | **186** |
| `…/TenantsTable.php:ativo:83` | `03:183` | **85** (o teste alheio já cita 85) |
| `tests/Pest.php:fronteiraDeRequest:749` | `04:225` | **775** (749 é outra função) |
| `tests/Pest.php:papelNaOrganizacao:773` | `04:261` | **799** |
| `tests/Pest.php:tenant:384` | `04:271` | **385** |
| `app/Models/Tenant.php:CONTEXTO_GLOBAL:66` | `04:252` | **67** |
| `…/helpers.php:generate_href_html:159-162` | `04:55`, `04:1163` | **153** (o teste cita 153) |
| `Authenticate.php:authenticate:35-41` | `00:108`, `04:160` | **15** |
| `IdentifyTenant.php:handle:40-42` | `00:109`, `04:158`, `04:1051` | **13** |
| `app/Models/User.php:canAccessPanel():219` | `00:47` | **156** |
| `…/HasRoutes.php:193` (forma sem símbolo) | docblock de `Tenant::urlDoPainel()` | linha **194**; 193 é branco |

- **Repro**: extrair as citações dos `.md` da wiki e conferir a linha de declaração de cada símbolo (script em `scratchpad`); conferido à mão com `sed -n '749p;775p' tests/Pest.php`, `sed -n '83p;85p' .../TenantsTable.php`, `grep -n "function urlDoPainel" app/Models/Tenant.php`.
- **Ação exigida**: corrigir as citações do `03` e do `04`; as do `00` são imutáveis — vão para Adendo. E trocar a evidência da linha `specs.md` do `03`, que hoje aponta um gate que não cobre o glob da rule.

### QA-02 — o `04` afirma que CT-21 e CT-22 "ainda não escrito", e a seção `### Cenários especificados sem teste escrito` descreve um estado que não existe mais · **Major** · destino 1

- **Dimensão**: L (L1/L3) · **Relacionado a**: D-05 do `03` (marcado **FECHADO**)
- **Esperado**: o `04` descreve o contrato vigente.
- **Observado**: `04:1115` e `04:1116` marcam CT-21/CT-22 como **ainda não escrito**; `04:1121-1137` lista **quatro** itens pendentes (CT-21, CT-22, linha `globex` de CT-16, linha `admin_vinculado` de CT-08) e conclui que *"o gate `04 → teste` fica **aberto**"*. Os quatro existem e passam.
- **Repro**: `grep -n "ainda não escrito" 04-casos-de-teste.md` → 2 linhas · `php vendor/bin/pest --testsuite=Kit --filter='LinkDoPainelSemTenancy'` → 3/3 verdes (CT-19, CT-21, CT-23) · `--testsuite=Tenancy --filter='LinkDoPainelDaOrganizacao'` → 41/41, incluindo CT-22 e as duas linhas de dataset.
- **Ação exigida**: reescrever as duas linhas do índice e a seção, remetendo ao D-05 fechado.

### QA-03 — o `03` (D-05.a) ainda sustenta a string `?tenant={uuid}` — exatamente o achado 1 do `/code-review`, medido como falso · **Major** · destino 1

- **Dimensão**: L3 · **Relacionado a**: adendo do `04` ("Por que o achado 1 é o mais grave dos quatro")
- **Esperado**: nenhum artefato da wiki continua afirmando o raciocínio refutado.
- **Observado**: `03-progresso.md` → `#### D-05.a` afirma *"o ramo de `Route::has()` entrega o modelo a `route()` como parâmetro extra — que vira **query string**, `?tenant={uuid}`"*, e deriva dele as *"55 asserções"* do CT-21. O `04` e o teste dizem o contrário: sem tenancy `getUrl()` cai em `HasRoutes.php:194`, concatena, e devolve `/app/{uuid}` — **sem query string**. O teste afirma `/app/{slug}` e `/app/{chave}`, e mede 65 asserções no arquivo inteiro.
- **Repro**: `sed -n '185,200p' vendor/filament/filament/src/Panel/Concerns/HasRoutes.php` (o ramo é `url(...)`, não `route()`); `grep -n "tenant=" tests/Kit/LinkDoPainelSemTenancyTest.php` → nada.
- **Ação exigida**: reescrever D-05.a com a forma medida, ou marcá-lo `*(invalidado pelo achado 1 do step 7.5)*`. É o parágrafo que sobreviveu à correção e o mais provável de reintroduzir o defeito.

### QA-04 — contadores do `04` e do `03` divergem da árvore, e CT-23 não está no índice de cenários · **Minor** · destino 1

- **Dimensão**: L1 · **Observado**, um a um:
  - `04`, cabeçalho: **"Mutantes previstos: 37"** e *"37 mutantes previstos, 37 com matador"* — os IDs únicos são **36** (`M01`..`M36`, nenhum pulado). O incremento 36→37 preservou um erro que já existia.
  - `04`, cabeçalho: **"Cenários: 23"** (certo), mas o `## Índice de Cenários` tem **22 linhas** — **CT-23 não está lá**, nem na tabela `### Suíte e arnês`. Ele só existe no adendo, na ADR-03 e no teste.
  - `03`: *"43 casos, 43 verdes, 226 asserções"* → medido **44 casos / 230 asserções**; o adendo diz *"174 → 227"* → **230**.
  - `03`: *"os **22** IDs do teste"* → são **23**.
  - `03`: *"**25 dos 43** casos ficam vermelhos"* × `## Notas de Implementação` *"**22 dos 39**"* — duas medições do mesmo experimento no mesmo arquivo.
- **Repro**: `grep -o "M[0-9][0-9]" 04-casos-de-teste.md | sort -u | wc -l` → 36 · `php vendor/bin/pest --testsuite=Tenancy --filter='LinkDoPainelDaOrganizacao'` → 41/165 · `--testsuite=Kit --filter='LinkDoPainelSemTenancy'` → 3/65.

### QA-05 — o docblock de CT-19 alega proteção que o caso não dá, e CT-21 o refuta no mesmo arquivo · **Minor** · destino 1

- **Dimensão**: K/L3 — mesma família do achado 4 do `/code-review`
- **Observado**: `tests/Kit/LinkDoPainelSemTenancyTest.php:29-31` afirma que CT-19 *"fica vermelho se a entrada do link nascer fora do resource (num hub, num widget, no menu)"*. Doze linhas abaixo, o docblock de CT-21 diz o oposto e está certo: *"CT-19 mata M27 só pela metade… Nenhuma das duas alcança um widget do dashboard, um item do menu do usuário"*. O `04` também registra "M27 *(em parte)*" para CT-19.
- **Repro**: leitura do caso — ele visita `/admin/organizacoes` e `/admin`, e não varre tela nenhuma.
- **Ação exigida**: o docblock de CT-19 passa a dizer "a tela do resource"; a varredura é de CT-21.

### QA-06 — o cabeçalho do arquivo de teste afirma duas coisas que o próprio arquivo contradiz · **Minor** · destino 1

- **Dimensão**: K/L3 · **Observado**: `tests/Tenancy/LinkDoPainelDaOrganizacaoTest.php:34-36` — *"Nenhum caso nomeia a função que gera a URL: o oráculo é o endereço, e ele é sempre calculado com `Filament::getPanel('app')->getUrl($organizacao)`"*. Oito casos chamam `urlDoPainel()` direto (CT-01, CT-08, CT-09, CT-10, CT-14, CT-18, CT-22, e CT-23 no outro arquivo), e três oráculos são **literal escrito à mão**: `toEndWith('/acme-do-brasil')` (CT-01:108), `toEndWith('/'.$organizacao->slug)` (CT-14:672), `toEndWith('/'.$slug)` (CT-18:854).
- **Repro**: `grep -n "urlDoPainel()\|toEndWith" tests/Tenancy/LinkDoPainelDaOrganizacaoTest.php`.
- **Ação exigida**: o cabeçalho deve afirmar o que é verdade — *o endereço **completo** nunca é escrito à mão; o **segmento** é, e de propósito*.

### QA-07 — CT-05 mantém no teste a asserção que o `04` registra como **cortada** (C-2) · **Minor** · destino 3

- **Dimensão**: K/L1 · **Observado**: `04` → `### Cogitado e cortado` diz que o 2º `Então` de CT-05 ("o endereço da inativa não é o da ativa") foi **cortado na revisão** por *"não poder falhar quando o primeiro passa"*. Ele continua no teste: `tests/Tenancy/LinkDoPainelDaOrganizacaoTest.php:263` — `expect(enderecoEsperadoDoPainel($inativa))->not->toBe(enderecoEsperadoDoPainel($ativa));`. É a forma que a taxonomia da skill classifica como `->not->toBe($outro)` sem valor esperado.
- **Repro**: `sed -n '259,264p' tests/Tenancy/LinkDoPainelDaOrganizacaoTest.php` × `grep -n "C-2" 04-casos-de-teste.md`.
- **Ação exigida**: decidir de que lado fica — tirar do teste, ou reabilitar o `Então` no `04` com o motivo. Hoje os dois lados dizem coisas diferentes sobre a mesma linha.

### QA-08 — CT-02 é asserção de ausência sobre fonte **transformada**, sem controle positivo · **Minor** · destino 3

- **Dimensão**: K — mesma estrutura do achado 1 do `/code-review`, um nível acima
- **Observado**: `it('[CT-02]')` faz `$fonte = semComentarios(file_get_contents(Tenant.php)); expect($fonte)->not->toContain('/app');`. Nada no caso afirma que `$fonte` ainda contém código. Se `semComentarios()` (helper **compartilhado**, `tests/Pest.php:1218`, cujo regex `~/\*.*?\*/~s` come blocos inteiros) passar a devolver vazio ou a engolir demais, o caso fica verde para sempre — inclusive contra M01, o mutante que ele é o **único** a matar.
- **Repro**: `php -r 'var_dump(str_contains("", "/app"));'` → `false`: a asserção passa sobre string vazia. O arquivo cru **contém** `/app/{slug}` (`app/Models/Tenant.php:32`), então o controle positivo é escrevível em uma linha.
- **Ação exigida**: `feature-test-design` com o mutante "`semComentarios()` devolve vazio" como entrada — a classe da lacuna é *asserção de ausência sobre entrada transformada*, e o remédio é o mesmo controle positivo que CT-21 ganhou.

### QA-09 — `tests/Kit/ExpectativaVariadicaDoPestTest.php` entra no diff sem rastro, e a rule já foi gravada enquanto a wiki a chama de "candidata" · **Minor** · destino 1

- **Dimensão**: A / L5 · **Observado**: o arquivo (84 linhas, `[CT-01]` e `[CT-02]` próprios) não está no `04`, não está na lista `## Testes` do `03`, e fica **fora** do gate bidirecional de IDs que o `03` declara fechado (o `diff` citado só olha os dois arquivos da feature). E o `.ai/rules/testes.md` já recebeu a rule de 29 linhas neste branch, enquanto `03` e `04` a classificam como *"**Candidato a rule**, roteado ao step 9"*.
- **Repro**: `git diff main...HEAD --stat` × `grep -n "ExpectativaVariadica" wikis/specs/feat/link-painel-do-tenant/**` → nada no `03`/`04`.
- **Ação exigida**: ou o arquivo vira cenário rastreado (Adendo no `00` / linha no `04`), ou a wiki registra por que ele é infraestrutura de rule e não CT da feature. E o step 9 precisa ser reconciliado — a rule está na árvore, não é mais candidata.

### QA-10 — `app.md` não tem linha na tabela `## Conformidade com Rules` · **Minor** · destino 1

- **Dimensão**: L4 · **Observado**: `.ai/rules/index.md` mapeia `app/**` → `.ai/rules/app.md`, e o diff toca `app/Models/Tenant.php` e três schemas. A tabela do `03` lista `filament.md`, `resources.md`, `filament-resources.md`, `specs.md`, `testes.md` e `models.md` — **`app.md` não aparece**.
- **Verificado no mérito**: a rule **não está violada** (nenhum `assignRole`/`syncRoles`, nenhum DTO). É lacuna de declaração, e a skill trata "rule sem linha na tabela" como achado por si.

### QA-11 — a `## Verificação Final` do `03` tem dois itens obrigatórios em aberto, um deles contradizendo o próprio arquivo · **Minor** · destino 1

- **Dimensão**: L3 · **Observado**: `- [ ] /ponytail:ponytail-review no diff` está desmarcado, e a tabela `### Auditoria Ponytail (step 6)` tem uma única linha: *"a rodar"*. E `- [ ] /code-review no diff (step 7.5)` está desmarcado embora o mesmo arquivo traga a seção `## Step 7.5 — /code-review no diff (2026-09-21)` com os quatro achados fechados.
- **Ação exigida**: o step 6 é passo do `01`; ou roda, ou vira desvio declarado. O checkbox do 7.5 é só marcar.

### QA-12 — D-05.b segue aberto: a prosa de R8 conta nove linhas de `Examples`, a tabela tem oito · **Cosmético** · destino 1

- **Dimensão**: L1 · Já registrado no `03` como D-05.b ("fica para a próxima passagem"). Reafirmado aqui porque a passagem seguinte aconteceu (o adendo) e ele não foi fechado: `04` → R8, *"Oito linhas são de formato… e a nona é de unicidade"* e *"vale para as nove linhas… das oito primeiras"*, contra 8 linhas na tabela e 8 no dataset.

### QA-13 — na ficha o link não sinaliza a nova aba · **Cosmético** · destino 2

- **Dimensão**: F/H (WCAG G201, aviso de nova janela) · **Observado**: o formulário avisa por `helperText` ("Abre em nova aba…"); a listagem usa o ícone convencional `ArrowTopRightOnSquare`; o `TenantInfolist` não tem **nem ícone nem texto** — só `->openUrlInNewTab()`. Três superfícies, duas convenções e uma ausência.
- **Repro**: `git diff main...HEAD -- app/Filament/.../TenantInfolist.php`.

### QA-14 — o `## Superfície Livewire` do `02` declara "fronteira aplicada: nenhuma" para o `href`, e a fronteira que de fato protege existe · **Minor** · destino 1

- **Dimensão**: I · **Esperado**: o inventário torna explícita a fronteira de que a feature **passa a depender** — foi esse exercício que produziu a linha do `slug`.
- **Observado**: a 1ª linha da tabela diz `**nenhuma** — é saída, não entrada`. O `slug` é escrito por humano e vira atributo HTML; quem impede injeção no `href` é o **escape** de `Filament\Support\generate_href_html()` (`e($url)`, `vendor/filament/support/src/helpers.php:159`), não a ausência de entrada. A conclusão ("não há risco") está certa **pelo motivo errado** — o padrão que `.ai/rules/specs.md` descreve como o mais difícil de enxergar. Confirmação de que o escape é a peça: CT-18 já mede que esse `e()` escapa **HTML e não URL**, o que é a outra metade da mesma dependência.
- **Verificado por leitura**: `->alphaDash()` (`\A[\pL\pM\pN_-]+\z/u`) recusa `<`, `"`, `/`, `.`, `%` e espaço; os únicos caminhos de escrita de `slug` fora do formulário são `TenantsSeeder`/`DemoTenancySeeder`, com valores fixos. **Nenhum vetor de injeção aberto.**
- **Ação exigida**: uma linha no `02` — "fronteira: escape de HTML do vendor; a feature depende dela".

## Matriz de Rastreabilidade

| RQ | Cláusula | Passo PRD | CT | Código | Resultado |
|----|----------|-----------|----|--------|-----------|
| RQ-01 | link clicável para `/app/{slug}` | 1–4 | CT-01, CT-04, CT-07, CT-08 | `Tenant::urlDoPainel()` + 3 schemas | ✅ |
| RQ-02 | no formulário, junto de nome e slug | 2 | CT-04 (linha `edicao`, com asserção de **lugar**), CT-06 | `TenantForm` | ✅ |
| RQ-03 | na visualização | 3 | CT-04 (linha `ficha`) | `TenantInfolist` | ✅ |
| RQ-04 | na listagem, acesso direto | 4 | CT-04 (linha `listagem`, com `assertTableColumnStateSet`) | `TenantsTable` | ✅ |
| RQ-05 | URL derivada do slug | 1 | CT-01, CT-02, CT-03, CT-18 | `Tenant::urlDoPainel()` | ✅ |
| ADR-02 | 403 **e** 404, os dois declarados | — | CT-08 (`admin`→403, `admin_vinculado`→403, `sem_vinculo`→404, 2×200) | portões pré-existentes | ✅ **conferido com os dois códigos distintos** |

**Sem omissão silenciosa.** Nenhuma cláusula sem passo, teste ou código; nenhum passo sem RQ —
exceto `tests/Kit/ExpectativaVariadicaDoPestTest.php` (QA-09), que é código de teste sem rastro.

## Dimensões

| # | Dimensão | Status | Observação |
|---|----------|--------|------------|
| A | Cobertura do requisito | ✅ | matriz fechada; 1 item sem rastro (QA-09) |
| B | Fronteiras e dados | ✅ | EP de 8 partições (CT-16), BVA 3-valores em 120 (CT-17), caixa e acento (CT-18) |
| C | Matriz de permissão | ✅ | CT-08 cobre as 4 células persona × portão + 2 positivas; a feature não acrescenta ação destrutiva |
| D | Observabilidade | ✅ | nenhum log novo, declarado no `01`. Log pré-existente conferido: contexto `user_id`/`tenant_id`/`motivo` — **sem PII** |
| E | Performance | ✅ | CT-14 prova 0 consultas para 1 e para 5; N+1 pré-existente da listagem é lacuna 3, medido 33/53 nas duas pontas |
| F | UX de erro | ⚠️ | o beco 403/404 é consequência aceita da ADR-02 e CT-10 prova que não tranca ninguém fora do `/admin`; QA-13 |
| G | Tema e cor | ✅ estático | o diff não tem Blade, CSS, hex nem classe de cor; só tokens (`->color('primary')`). Visual nos dois temas **não verificado** |
| H | Acessibilidade | ⚠️ | texto do link é a URL (descritivo); QA-13 |
| I | Segurança da superfície nova | ⚠️ | nenhum IDOR, nenhuma rota nova, nenhum dado sensível novo; QA-14 é defeito de **motivo**, não de exposição |
| J | Regressão adjacente | ✅ | natureza `nova` ⇒ não obrigatória; rodada mesmo assim: 87 casos dos gates do kit + 49 de tenancy adjacente, todos verdes |
| K | Adequação da suíte | ⚠️ | passo estático feito, 2 achados (QA-07, QA-08); passo **medido** não verificado |
| L | Consistência documental | ❌ | 9 achados — L1, L2, L3, L4 e L5 |

## Suspeitas Não Confirmadas

- **CT-06 com agulha inalcançável** — investigado por ser a família do achado 1. `Filament::getPanel('app')->getUrl()` sem tenant, com usuário sem organizações, cai em `url($this->getPath())` = `http://localhost/app`; somado a `/acme` dá exatamente o endereço que `getUrl($tenant)` produz (confirmado por CT-01 passar com `toEndWith('/acme-do-brasil')`). **Agulha viva — não é achado.**
- **`assertDontSee('/admin/organizacoes')` de CT-19 sem controle** — a agulha é produzível por implementação errada (navegação registrada apesar da config), e o caso tem três oráculos vivos ao lado. Ficou abaixo do limiar.
- **`Filament::getPanel('app')` estourar sem o painel registrado** — `bootstrap/providers.php` registra `AppPanelProvider` incondicionalmente; só alcançável em projeto que removeu o provider. Fora do kit.

## Não Verificado

- **Dimensão K, passo medido (`--mutate`)** — motivo: **sem driver de cobertura**. `php -m` não lista PCOV nem Xdebug. O mutation score de `Tenant::urlDoPainel()` e dos três schemas **não foi medido**; a verificação por mutação citada no `04` (M36 × CT-23) é declaração do implementador, não conferida aqui.
- **Confronto visual e de console/rede** — Playwright MCP não configurado e Boost MCP fora do ar. Sem inventário de elementos × cobertura, sem screenshot nos dois temas, sem `browser_console_messages`.
- **Suíte completa (2.817 casos)** — não re-executada; rodei 180 casos dirigidos. Os números do `03` (`composer test:kit` 2657) são anteriores ao CT-23.
- **`tests/Browser/TemaEscuroTest.php`** — não executado. A instabilidade documentada no `03` fica como está: destino 4, não defeito desta feature.
- **CT-B** — não existem, e o gate do `01`/`04` que os dispensa foi lido e aceito: a feature afirma sobre HTML renderizado, não sobre JS, console, cor ou layout.

## Fechamento — os achados documentais do ciclo 1 (2026-09-21)

Fechados **onze** dos quatorze, todos os de **destino 1**. Um commit por achado. Nenhum número
copiado deste relatório: os contadores foram **remedidos** com `vendor/bin/pest` e `grep`, e as
citações **reconferidas** por extração mecânica.

| Achado | Estado | O que fechou |
|---|---|---|
| **QA-01** | ✅ fechado (com 1 item aberto) | doze citações corrigidas no `03` e no `04`; as três do `00` viraram **Adendo 1**; a evidência da linha `specs.md` trocada. **Duas** estavam fora da lista deste relatório (ver abaixo). **Aberto**: `HasRoutes.php:193` no docblock de `Tenant::urlDoPainel()` — é `app/`, não wiki |
| **QA-02** | ✅ fechado | os dois "ainda não escrito" do índice e a seção `Cenários especificados sem teste escrito`, que agora registra o gate `04 → teste` **fechado** nos dois sentidos |
| **QA-03** | ✅ fechado | D-05.a reescrito com a forma **medida** (`/app/{uuid}`, segmento de caminho, ramo da linha 194 de `HasRoutes`), com o raciocínio antigo preservado por cima e datado |
| **QA-04** | ✅ fechado | 37→**36** mutantes; 43/226→**44/229**; 22→**23** IDs; CT-23 no índice e na tabela `Suíte e arnês`; e as duas medições do mesmo experimento viraram uma, remedida: **26 dos 44** |
| **QA-05** | ✅ fechado | o docblock de CT-19 passa a afirmar a tela do resource; a varredura de hub/widget/menu é de CT-21 |
| **QA-06** | ✅ fechado | o cabeçalho do teste passa a dizer que o endereço **completo** nunca é escrito à mão e o **segmento** é, de propósito, com os oito casos e os quatro literais nomeados |
| **QA-07** | ✅ fechado | `df00ddf` — destino 3 |
| **QA-08** | ✅ fechado | `df00ddf` — destino 3 |
| **QA-09** | ✅ fechado | o `ExpectativaVariadicaDoPestTest` entrou na lista de Testes do `03` e ganhou seção no `04` explicando por que **não** é CT desta feature; e os dois lugares que chamavam a rule de "candidata" passaram a registrar que ela está na árvore (`9c6c494`) |
| **QA-10** | ✅ fechado | linha de `app.md` na tabela `Conformidade com Rules`, **n.a. no mérito** |
| **QA-11** | ✅ fechado | `/code-review` marcado (a seção já existia no mesmo arquivo); `/ponytail:ponytail-review` **rodado**, com a tabela do step 6 preenchida — veredito `net: -0 linhas` |
| **QA-12** | ✅ fechado | R8 passa a contar **sete** linhas de formato e a **oitava** de unicidade, nos dois lugares do `04` |
| **QA-13** | ✅ fechado | `24b7f9c` — destino 2 |
| **QA-14** | ✅ fechado | o `02` passa a nomear a fronteira real (`e($url)` de `generate_href_html()`) e a dizer que a feature **depende** dela; a conclusão "sem risco" fica, com o motivo certo |

### Duas citações erradas que este relatório não listou

A varredura de fechamento não usou a lista de treze: extraiu **todas** as citações dos seis `.md`
da wiki e conferiu cada uma. Além das do relatório, apareceram duas no `04`, as duas invisíveis ao
gate por serem caminho solto (`base_path('TenantForm.php')` não resolve):

- `TenantForm.php:configure:35` → o literal `/app/{slug}` está em `:36`, e a âncora é a
  `description`, não a `configure`. Virou `…/TenantForm.php:description:36`
- `TenantForm.php:configure:44-48` → o `live(onBlur: true)`/`afterStateUpdated` do campo `nome`
  está em `:45-46`. Virou `…/TenantForm.php:afterStateUpdated:46`

É a confirmação medida do que a própria rule diz: **conferir por lista escolhida à mão não é
conferência**. Foram 13 na lista, 14 na árvore.

### ~~O que fica aberto~~ — fechado em 2026-09-22

> A citação `HasRoutes.php:193` → `194` foi fechada no commit `5390093`, e o `03` foi atualizado em
> `dcb038e`. **Esta seção continuou dando o item por aberto** — o QA-18 fechou pela metade, e o
> ciclo 3 apontou a outra metade. Fica registrado porque é a terceira ocorrência do mesmo padrão
> nesta wiki: o texto sobrevive à correção que ele descreve, num arquivo que ninguém relê.

### O que ficava aberto (texto original)

- **`HasRoutes.php:193`** no docblock de `Tenant::urlDoPainel()` (`app/Models/Tenant.php`). O ramo
  de concatenação retorna na linha **194**; a 193 é branca. Não foi corrigida porque é código de
  aplicação, fora do escopo desta passagem — e porque a citação é da forma **sem símbolo**, que o
  `CitacoesDeCodigoTest` só confere quanto à existência da linha, e a 193 existe. É o único item
  do QA-01 que sobra.
- **Dimensão K, passo medido (`--mutate`)** e **confronto visual/console** seguem em
  `## Não Verificado`: não são achados documentais e nada nesta passagem os altera.

### Gates rodados no fechamento

    vendor/bin/pest tests/Kit/CitacoesDeCodigoTest.php tests/Kit/SiteDeDocumentacaoTest.php
    → 68 casos, 68 verdes, 242 asserções

    vendor/bin/pest tests/Tenancy/LinkDoPainelDaOrganizacaoTest.php tests/Kit/LinkDoPainelSemTenancyTest.php
    → 44 casos, 44 verdes, 229 asserções

---

# Ciclo 2

> Mesmo perfil de esforço (**completo**) e mesma natureza (`nova`). Entrada: os 15 commits que
> entraram depois do relatório do ciclo 1 (`3e2b2a7..HEAD`) — diff que **nenhuma revisão tinha
> lido** — mais uma reconferência independente de tudo que o fechamento declarou fechado.

## Veredito — Ciclo 2

**REPROVADO → especificação**

- Blocker: **0** · Major: **2** · Minor: **3** · Cosmético: **2**
- **Não converge**: sete achados novos, nenhum deles repetição do ciclo 1. Os dois Major nasceram
  **dentro da própria remediação** — um mutante que ficou com duas definições, e um aviso de
  divergência que descreve um estado desfeito antes do ciclo 1.
- Ambiente: Pest **5.0.5** · app não servido · Playwright MCP **indisponível** · driver de
  cobertura **ausente** (dimensão K segue sem o passo medido)
- Medições próprias deste ciclo: **44 casos / 229 asserções** (feature) e **73 casos / 257
  asserções** (gates de citação, documentação, variádica do Pest e filtro de tabela) — verdes
- `tests/Browser/TemaEscuroTest.php` **não é re-reportado**: instabilidade pré-existente de
  contaminação entre arquivos de navegador, já roteada ao **destino 4** no `03`

### O que foi reconferido, e não virou achado

| Alegação do fechamento | Como foi reconferida | Resultado |
|---|---|---|
| "14 citações erradas na árvore, não 13" | varredura própria sobre os `.md` do ciclo 1 (`git show 3e2b2a7:…`), com padrão que cobre `simbolo()`, caminho solto e intervalo, e resolução de basename | **confirmado: 14** |
| as citações do `03` e do `04` corrigidas | mesma varredura sobre a árvore de hoje | **0 ERRO** — sobram só as três do `00`, cobertas pelo Adendo 1 |
| as correções apontam para a **declaração**, não para menção em docblock | conferido uma a uma: `User::canAccessPanel` `:156`, `Authenticate::authenticate` `:15`, `IdentifyTenant::handle` `:13`, `HasRoutes` `:194`, `TenantForm::description` `:36`, `TenantForm::afterStateUpdated` `:46`, `TenantsTable` `ativo:85`, `generate_href_html` `:153` | **nenhuma aponta para docblock** |
| contadores remedidos | `grep` e `pest` próprios | **36** mutantes (`M01`..`M36`), **23** cenários no índice (CT-23 presente), **9** regras, **3** lacunas, **44/229**, **23** IDs nos dois sentidos, **26 dos 44** nos dois lugares do `03`, R8 com **oito** linhas de `Exemplos` |
| CT-05 perdeu a asserção cortada | `git diff 3e2b2a7..HEAD -- tests/` | confirmado, e a aritmética fecha: −1 asserção × 3 linhas de dataset, +2 em CT-02 = **230 → 229** |

## Achados

### QA-15 — `M36` tem **duas** definições e **dois** matadores no mesmo `04`, e uma delas contradiz a verificação por mutação · **Major** · destino 1

- **Dimensão**: L1/L3 + K · **Relacionado a**: gate de falsificabilidade do `04`, CT-21, CT-23, adendo do step 7.5
- **Esperado**: um ID de mutante, uma definição, um matador. O gate do cabeçalho — *"36 mutantes previstos, 36 com matador, 0 sem"* — só vale se cada ID significar uma coisa só.
- **Observado**: `04:987`, na tabela `#### Mutantes previstos` de **R9**, define `M36` como *"a entrada nasce num **widget do dashboard** ou no **menu do usuário** do `/admin` … a tela inteira responde **500**"*, com matador **CT-21**. `04:1286`, no adendo `### M36 — o mutante novo`, define `M36` como *"a guarda `hasTenancy()` sai de `Tenant::urlDoPainel()`"*, com matador **CT-23**, e acrescenta, medido: *"removida a guarda, CT-23 fica vermelho e **CT-19/CT-21 seguem verdes**"* — ou seja, **CT-21 não mata o M36 que a linha 987 diz que ele mata**. O índice (`04:1119`) já foi corrigido para `CT-23 → M36`; a tabela de R9 ficou como estava. De quebra, a definição da linha 987 é quase a de `M27` (`04:985`), que já é atribuída a CT-19 *(em parte)* + CT-21.
- **Repro**: `grep -n "M36" 04-casos-de-teste.md` → `987`, `1119`, `1121`, `1122`, `1286`; ler `987` contra `1286`. E `git show 5f1e81d -- .../04-casos-de-teste.md | grep M36` mostra que o remedimento tocou só o parágrafo do gate, não a linha de R9.
- **Ação exigida**: uma das duas linhas sai, ou a do widget/menu vira **M37** — e aí o contador do cabeçalho muda junto.

### QA-16 — o `04` publica um aviso de **Divergência viva** sobre CT-02 que foi desfeito em `3c326bf`, antes do ciclo 1, e o cita numa linha em branco · **Major** · destino 1

- **Dimensão**: L3/L1 · **Relacionado a**: corte **C-1** da revisão adversarial
- **Esperado**: o `04` descreve o contrato vigente. É o mesmo critério que reprovou **QA-02** no ciclo 1.
- **Observado**: `04:372-375` traz *"⚠️ **Divergência viva.** O teste de CT-02 ainda carrega a asserção cortada, na forma de um `preg_match` sobre a fonte (`tests/Tenancy/LinkDoPainelDaOrganizacaoTest.php:133`) … é linha a **remover do teste**"*. O `preg_match` saiu em `3c326bf` (*remove do CT-02 o regex que o 04 cortou (C-1)*), **anterior** ao relatório do ciclo 1. Hoje a única ocorrência de `preg_match` no arquivo é a **palavra**, no docblock que explica a remoção (`:139`). E a citação `:133` aponta para uma linha que só tem `*`.
- **Repro**: `grep -n "preg_match" tests/Tenancy/LinkDoPainelDaOrganizacaoTest.php` → uma linha, `139`, dentro de comentário · `sed -n '133p' tests/Tenancy/LinkDoPainelDaOrganizacaoTest.php` → `*` · `git log --oneline -- tests/Tenancy/LinkDoPainelDaOrganizacaoTest.php`.
- **Ação exigida**: trocar o bloco por um registro datado de que C-1 foi aplicado, como o `### Cogitado e cortado` já faz com C-2.

### QA-17 — o controle positivo que fechou QA-08 não cobre o mutante realista de `semComentarios()`: CT-02 fica verde com o corpo do gerador inteiro comido · **Minor** · destino 3

- **Dimensão**: K · **Relacionado a**: QA-08 (ciclo 1), M01, `tests/Pest.php:semComentarios:1219`
- **Esperado**: o controle positivo fecha a **classe** de lacuna — *asserção de ausência sobre entrada transformada* —, não a instância. É o que a skill exige do destino 3, e o que `df00ddf` e o adendo do `04` afirmam ter feito.
- **Observado**: os dois controles são `assertStringContainsString('/app/{slug}', $cru)` — sobre o texto **cru** — e `assertNotSame('', trim($fonte))` — só **não-vazio**. Nenhum dos dois afirma que a fonte **transformada** ainda contém o código sob teste. E o mutante plausível de `semComentarios()` não é "devolver vazio": é o `?` cair do quantificador (`~/\*.*?\*/~s` → `~/\*.*\*/~s`), e aí o regex ganancioso come do **primeiro** `/*` ao **último** `*/`, levando junto todo o corpo da classe.
- **Repro** (sem tocar em nenhum arquivo versionado):
  1. aplicar os dois `preg_replace` de `semComentarios()` sobre `app/Models/Tenant.php`, uma vez com `.*?` e outra com `.*`
  2. medido: original → **1852** bytes, contém `function urlDoPainel`; mutante ganancioso → **712** bytes, **não** contém `function urlDoPainel`
  3. nos dois casos: controle 1 `true`, controle 2 `true`, asserção `not->toContain('/app')` `true` → **CT-02 VERDE**, e M01 volta a não ter matador
- **Evidência**: `scratchpad/ct02.php`
- **Ação exigida**: `feature-test-design` com este mutante como entrada. O controle que fecha a classe é sobre a fonte **transformada** (exigir que `$fonte` ainda contenha `function urlDoPainel`, por exemplo), não sobre a crua. E **varrer o padrão antes de consertar o ponto**: `semComentarios()` é compartilhado e também é usado em `tests/Kit/AderenciaAoBlueprintTest.php`, `tests/Kit/HostLocalTest.php` e `tests/Kit/PageHeaderTest.php` — fora do escopo desta feature, mas mesma família.

### QA-18 — `03` e `06` declaram **aberto** o item `HasRoutes.php:193`, que o commit de HEAD fechou · **Minor** · destino 1

- **Dimensão**: L3 · **Observado**: `03:114-118` (*"**Fica aberto um item** … `HasRoutes.php:193` … fica para quem estiver com o `app/` na mão"*) e `06` → `### O que fica aberto`. O commit `5390093` já trocou `:193` por `:194` em `app/Models/Tenant.php` e em `tests/Kit/LinkDoPainelSemTenancyTest.php`. Conferido: a linha 194 de `vendor/filament/filament/src/Panel/Concerns/HasRoutes.php` é o `return url(Str::replaceEnd(...))` do ramo de concatenação.
- **Repro**: `git show 5390093 -- app/Models/Tenant.php` · `grep -n "HasRoutes.php:19" app/Models/Tenant.php tests/Kit/LinkDoPainelSemTenancyTest.php` → só `:194`.
- **Ação exigida**: fechar o parágrafo do `03` (e o do `06`) com o commit. O último item do QA-01 não sobra mais.

### QA-19 — o ícone de nova aba da ficha, que fechou QA-13, entrou sem nenhum CT · **Minor** · destino 3

- **Dimensão**: A/K · **Relacionado a**: QA-13 (ciclo 1, destino 2), R4, CT-07
- **Esperado**: comportamento novo de UI nasce com cenário. R4/CT-07 cobrem o **atributo** `target="_blank"`; o **aviso** ao usuário (`helperText` no formulário, ícone na listagem e agora na ficha) não é afirmado por caso nenhum.
- **Observado**: `24b7f9c` acrescentou `->icon(Heroicon::OutlinedArrowTopRightOnSquare)->iconPosition(IconPosition::After)` a `TenantInfolist`. Apagar as duas linhas deixa os **44** casos da feature verdes — o mutante "a ficha volta a não avisar" é exatamente o defeito que QA-13 reportou, e nada o detecta.
- **Repro**: `grep -rn "ArrowTopRightOnSquare|iconPosition|helperText" tests/Tenancy/LinkDoPainelDaOrganizacaoTest.php tests/Kit/LinkDoPainelSemTenancyTest.php` → **nenhuma ocorrência**.
- **Ação exigida**: cenário em R4 (o aviso de nova aba nas três superfícies, cada uma com a sua convenção) com mutante próprio, ou lacuna declarada com o motivo. Hoje não é nem uma coisa nem outra.

### QA-20 — o `03` diz que QA-01 achou **doze** citações erradas na wiki; foram **14** · **Cosmético** · destino 1

- **Dimensão**: L1/L4 · **Observado**, dois pedaços:
  - linha `specs.md` de `## Conformidade com Rules` (`03`): *"por isso QA-01 achou **doze** citações erradas na wiki"*. Medido por varredura própria sobre os `.md` do ciclo 1: **14** distintas, das quais **13** viviam no `03`/`04` (só `User.php:canAccessPanel():219` era exclusiva do `00`). O `06` → `### Duas citações erradas que este relatório não listou` já diz **14**; o título do QA-01 diz **13**. Três números para a mesma varredura.
  - `## Verificação Final` do `03`: o item de citações continua com a evidência `tests/Kit/CitacoesDeCodigoTest.php` (CT-26) e **nada** sobre a wiki — e é a Verificação Final o lugar onde a rule `specs.md` manda o resultado (`14/14 ok`) aparecer. A evidência da wiki ficou só na tabela de rules.
- **Repro**: extrair os `.md` do ciclo 1 (`git show 3e2b2a7:...`) e repetir a conferência por símbolo → 14 ERRO, listadas no `scratchpad`.

### QA-21 — `04:1222` (achado **A-5**) ainda conta a linha `globex` como a **9ª** dos `Exemplos` de CT-16; é a **8ª** · **Cosmético** · destino 1

- **Dimensão**: L1 · **Relacionado a**: QA-12 (ciclo 1), fechado dentro de R8
- **Observado**: QA-12 fez R8 passar a contar *"**Sete** linhas … e a **oitava** … de unicidade"*, e a tabela de `Exemplos` tem oito linhas — conferido. A linha do `## Revisão Adversarial` não foi junto: *"**9ª linha** dos `Exemplos` de CT-16 (`globex`)"*.
- **Repro**: `grep -n "9ª linha" 04-casos-de-teste.md` · contar a tabela de `Exemplos` de CT-16 → 8 linhas · o dataset de CT-16 no teste → 8 linhas.

## Dimensões — Ciclo 2

| # | Dimensão | Status | Observação |
|---|----------|--------|------------|
| A | Cobertura do requisito | ⚠️ | matriz do ciclo 1 revalidada (RQ-01..RQ-05 e ADR-02 não são tocados pelo diff novo); QA-19 é comportamento novo sem cenário |
| B | Fronteiras e dados | ✅ | nenhuma validação mudou em `3e2b2a7..HEAD`; R8/CT-16..18 reconferidos por contagem |
| C | Matriz de permissão | ✅ | inalterada — o diff novo não toca portão, papel nem política |
| D | Observabilidade | ✅ | nenhum log novo no diff do ciclo 2 |
| E | Performance | ✅ | o diff novo é `->icon()`/`->iconPosition()` declarativos e comentário; zero consulta nova |
| F | UX de erro | ✅ | QA-13 fechado e conferido na fonte: as três superfícies avisam da nova aba |
| G | Tema e cor | ✅ estático | `Heroicon` e `IconPosition` são enum/token; nenhum hex, classe de cor ou Blade no diff. Visual nos dois temas segue **não verificado** |
| H | Acessibilidade | ✅ | o ícone convencional (WCAG G201) fecha a ausência que o ciclo 1 apontou na ficha |
| I | Segurança da superfície nova | ✅ | nada acrescentado à superfície; o `02` já nomeia a fronteira real (`e($url)`) desde QA-14 |
| J | Regressão adjacente | ✅ | 73 casos dos gates (citação, documentação, variádica, filtro de tabela) + 44 da feature, verdes. Suíte completa reportada pelo condutor: 2.817 / 2.800 verdes / 16 pulados / 1 falha pré-existente (destino 4) |
| K | Adequação da suíte | ⚠️ | passo estático refeito nos dois arquivos de teste: QA-17 e QA-19. Passo **medido** (`--mutate`) segue **não verificado**, sem driver de cobertura |
| L | Consistência documental | ❌ | 5 achados — QA-15, QA-16, QA-18, QA-20, QA-21 |

## Suspeitas Não Confirmadas — Ciclo 2

- **`tests/Tenancy/FiltrosDeTabelaTenancyTest.php:16`** — a prosa diz que a coluna nova deslocou a citação *"de 55 para **83**"*, e hoje o `TernaryFilter` está em **85**. A citação ao lado (`:ativo:85`) está certa, e o `83` é relato de um estado intermediário datado, não ponteiro vivo. Abaixo do limiar.
- **24 citações da wiki na forma sem símbolo** (`{path}:{linha}` e `{path}:{início}-{fim}`) — a rule pede `{path}:{símbolo}:{linha}`, mas quase todas apontam para bloco de teste ou trecho de Blade sem símbolo único, e todas resolvem para a linha certa. Não é defeito de ponteiro; é o limite do formato.
- **CT-05, docblock** — *"a segunda asserção é a discriminante"* continua verdadeiro depois do corte C-2, porque a segunda asserção passou a ser o `assertDontSeeHtml` da ativa. Verificado, não é achado.

## Não Verificado — Ciclo 2

- **Dimensão K, passo medido (`--mutate`)** — sem PCOV nem Xdebug, como no ciclo 1.
- **Confronto visual e de console/rede** — Playwright MCP indisponível; Boost MCP também fora do ar nesta sessão.
- **Suíte completa** — não re-executada por este gate; o número veio do condutor. Foram rodados 117 casos dirigidos.

---

# Ciclo 3

> Mesmo perfil de esforço (**completo**) e mesma natureza (`nova`). Entrada: os dois commits que
> entraram depois do relatório do ciclo 2 (`b5a8f9c..HEAD` — `62ffddd` e `dcb038e`), **diff que
> nenhuma revisão tinha lido**, mais a reconferência independente de tudo que o fechamento do
> ciclo 2 declarou fechado. **Este é o teto de três ciclos da skill.**

## Veredito — Ciclo 3

**APROVADO COM DÉBITO**

- Blocker: **0** · Major: **0** · Minor: **4** · Cosmético: **1**
- **Nenhum Blocker e nenhum Major novo** — então **não há escalação ao usuário**, que é o que a
  skill manda quando o teto é atingido com achado que reprova. O loop **encerra aqui**: os cinco
  achados (QA-22 a QA-26) são todos de **destino 1**, texto contra a árvore, e vão para o `03`
  como **débito**. Nenhum toca código de aplicação, teste ou asserção.
- Os cinco nasceram **dentro da remediação do ciclo 2**: o CT-24 entrou no `04` sem ser encaixado
  na estrutura por regra que o arquivo usa para os outros 23, a separação M36/M37 parou antes do
  gate, e três contadores não souberam que a suíte cresceu.
- Ambiente: Pest **5.0.5** · app não servido · Playwright MCP **indisponível** · driver de
  cobertura **ausente** (`php -m` não lista PCOV nem Xdebug)
- Medições próprias deste ciclo: **45 casos / 234 asserções** na feature (42/169 em
  `tests/Tenancy` + 3/65 em `tests/Kit`) e **74 casos / 258 asserções** nos gates de citação,
  documentação, variádica do Pest, filtro de tabela e helpers — todos verdes
- `tests/Browser/TemaEscuroTest.php` **não é re-reportado**: instabilidade medida na própria
  `main`, sem nenhuma das branches — **destino 4**

### O que foi reconferido por medição própria, e NÃO virou achado

| Alegação do fechamento do ciclo 2 | Como foi reconferida | Resultado |
|---|---|---|
| CT-24 — `getIcon($state)` não é trivialmente verdadeiro | leitura do vendor: `getIcon(mixed $state)` avalia o closure e, **sem** `->icon()`, só devolve algo se `$state instanceof IconInterface` (`vendor/filament/infolists/src/Components/Concerns/HasIcon.php:getIcon:58` e `vendor/filament/tables/src/Columns/Concerns/HasIcon.php:getIcon:58`). O estado aqui é **string** (a URL) | **confirmado** — o parâmetro é obrigatório, e passá-lo não abre caminho para verdade trivial |
| CT-24 mata o mutante nas **três** superfícies | mutação própria, uma de cada vez, com `git checkout --` entre elas: `->icon()` fora de `TenantInfolist`; `->icon()` fora de `TenantsTable`; a frase trocada no `helperText` de `TenantForm` | **vermelho nos três**, cada um com mensagem distinta (componente de schema, `expect(...)->not->toBeNull`, `assertSee`) |
| CT-02 corrigido resiste ao regex ganancioso | os dois `preg_replace` de `semComentarios()` aplicados fora da árvore, com `.*?` e com `.*` | **confirmado**: original 1852 bytes *(com `trim`)* e **com** `function urlDoPainel` → verde; ganancioso 712 bytes **sem** o método → vermelho; retorno vazio → vermelho |
| M36 tem matador de verdade | guarda `hasTenancy()` removida de `Tenant::urlDoPainel()`, com `tests/Kit/LinkDoPainelSemTenancyTest.php` inteiro | **CT-23 vermelho, CT-19 e CT-21 verdes** — exatamente o que o adendo do `04` afirma |
| citações da wiki | varredura mecânica própria sobre os cinco `.md` (fora o `06`, que **cita** as erradas de propósito), com resolução por basename | **74 OK, 3 ERRO** — as três do `00`, imutáveis, cobertas pelo **Adendo 1** |
| contadores de ID | `grep` próprio | **24** cenários no `04` e **24** IDs no teste, fechando nos dois sentidos; **38** IDs de mutante distintos; **9** regras |
| números do README tocados pelo diff | `find` | **150** arquivos em `Kit`+`Tenancy`, **176** no total, **65** pastas com `00-requisito.md` — os três batem |

## Achados

### QA-22 — o CT-24 entrou no `04` sem ser encaixado na estrutura por regra, em **quatro** lugares · **Minor** · destino 1

- **Dimensão**: L1/L3 · **Relacionado a**: QA-19 (ciclo 2), QA-04 (ciclo 1), R1, R4
- **Esperado**: cenário novo ocupa os mesmos lugares que os 23 anteriores — índice completo, linha em `### Suíte e arnês`, regra que o abriga e mutante declarado numa tabela `#### Mutantes previstos`. É o que o fechamento do QA-04 fez por CT-23.
- **Observado**, quatro pontos:
  1. **`04:1127`** — a linha do índice tem **6 células** onde todas as outras têm **7**. Falta a coluna `Arquivo`, então `Feature (schema resolvido)` cai em `Camada`, `M38` cai em `Arquivo` e `Mata` fica vazia. CT-24 é o único cenário do arquivo sem arquivo de teste declarado no índice.
  2. **`04:1127`** — CT-24 está filiado a **R1** (*"a URL do link é a que o painel `app` gera"*). O cenário é de **R4** (`04:545`, *"o link abre em nova aba nas três superfícies"*) — e a **ação exigida do QA-19** dizia, literalmente, *"cenário em R4"*.
  3. **`04:209`** — a linha de `### Suíte e arnês` do arquivo de Tenancy continua `CT-01..CT-18, CT-20, CT-22`. **CT-24 não está lá.** É o mesmo defeito que o QA-04 fechou para CT-23.
  4. **M38 não aparece em nenhuma tabela `#### Mutantes previstos`** — é o único dos 38 que vive só no índice e no `## Adendo 2`. A tabela de R4 (`04:570-575`) segue com M12 e M13.
- **Repro**:
  1. contar os separadores das linhas do índice: a 1127 tem um `|` a menos que as 23 anteriores
  2. `sed -n '545p;1127p' 04-casos-de-teste.md` — a regra de nova aba é R4, o índice diz R1
  3. `sed -n '209p' 04-casos-de-teste.md | grep -c CT-24` → `0`
  4. `grep -n "M38" 04-casos-de-teste.md` → `1127` e `1288`, nenhuma tabela de mutante
- **Ação exigida**: mover CT-24 para R4 (índice + `Esquema do Cenário` ao lado de CT-07), fechar a 7ª célula da linha, acrescentar CT-24 à linha de `### Suíte e arnês` e declarar M38 na tabela `#### Mutantes previstos` de R4.

### QA-23 — a separação M36/M37 parou antes do gate de falsificabilidade e do índice · **Minor** · destino 1

- **Dimensão**: L1/K · **Relacionado a**: QA-15 (ciclo 2), cuja ação exigida dizia *"e aí o contador do cabeçalho muda junto"*
- **Esperado**: o gate do `04` — *"N mutantes previstos, N com matador, 0 sem"* — vale enquanto o número cobrir todos os IDs, e o índice lista cada mutante na coluna `Mata` de quem o mata.
- **Observado**, dois pontos:
  1. **`04:1129`** ainda publica **"36 mutantes previstos, 36 com matador, 0 sem — `M01`..`M36`, nenhum ID pulado, conferido por `grep -o "M[0-9][0-9]" 04-casos-de-teste.md | sort -u | wc -l`"**. O comando que a própria frase publica devolve **38** hoje, e o cabeçalho (`04:34`) já foi corrigido para 38. **O gate é refutado pelo comando que ele cita** — e, como está escrito, deixa M37 e M38 fora do seu alcance.
  2. **M37 é o único dos 38 ausente da coluna `Mata` do índice.** A linha de CT-21 (`04:1124`) continua só com `M27`, e M37 é declarado matado por CT-21 apenas na tabela de R9 (`04:988`). Antes da separação o ID `M36` aparecia no índice pela linha de CT-23; depois dela, o mutante do widget/menu ficou sem linha nenhuma.
- **Repro**:
  - `sed -n '34p;1129p' 04-casos-de-teste.md` → 38 contra 36
  - `comm -13 <(sed -n '1102,1128p' 04-casos-de-teste.md | grep -o "M[0-9][0-9]" | sort -u) <(grep -o "M[0-9][0-9]" 04-casos-de-teste.md | sort -u)` → saída: **`M37`**, e só ele
- **Não é defeito de mérito**: medido aqui, a separação está **certa**. Removida a guarda `hasTenancy()` de `Tenant::urlDoPainel()`, CT-23 fica vermelho e CT-19/CT-21 seguem verdes (3 casos, 1 falha, 65 asserções). O defeito é de escrituração, nos dois lugares que o QA-15 não alcançou.
- **Ação exigida**: o parágrafo de `04:1129` passa a 38 (e a `M01`..`M38`), e a linha de CT-21 do índice ganha `M37` ao lado de `M27`.

### QA-24 — "44 casos / 229 asserções" sobreviveu ao CT-24 em três checkboxes do `03` e **no CHANGELOG** · **Minor** · destino 1

- **Dimensão**: L1/L5 · **Relacionado a**: QA-04 e QA-20, mesma família
- **Esperado**: a `## Verificação Final` é onde o `03` declara o que foi **medido**; checkbox marcado com número velho declara uma medição que não existe. E o CHANGELOG **publica**.
- **Observado** — medido hoje: **45 casos / 234 asserções** (42/169 em `tests/Tenancy` + 3/65 em `tests/Kit`):

| Onde | O que diz | Árvore |
|---|---|---|
| `03:56-57` | *"**44 casos, 44 verdes, 229 asserções** … (41/164 em `tests/Tenancy` + 3/65 em `tests/Kit`)"* | 45 / 234 (42/169 + 3/65) |
| `03:78` | *"os **23** IDs do teste"* | **24** |
| `03:86` | *"**23** cenários …, **9** regras, **36** mutantes, **3** lacunas"* | 24 cenários, 9 regras, **38** mutantes, 3 lacunas |
| `CHANGELOG.md:31` | *"coberto por **44 casos** em `tests/Tenancy/LinkDoPainelDaOrganizacaoTest.php` e `tests/Kit/LinkDoPainelSemTenancyTest.php`"* | **45** |

- **Repro**: `vendor/bin/pest tests/Tenancy/LinkDoPainelDaOrganizacaoTest.php tests/Kit/LinkDoPainelSemTenancyTest.php` → `tests: 45, assertions: 234` · a extração de IDs dos dois arquivos de teste devolve 24
- **Ação exigida**: remedir os quatro. O do CHANGELOG é o que sai do repositório, e é o que urge.
- **Nota**: `03:133` também diz "44/229, 23 IDs", mas ali está **dentro do parágrafo datado do ciclo 2** — é registro do que foi medido naquele dia e fica como está.

### QA-25 — `### Falsificabilidade` do `03` desatualizou, e nomeia **CT-02 no lado errado** · **Minor** · destino 1

- **Dimensão**: L3/K · **Relacionado a**: QA-17 (ciclo 2), QA-19 (ciclo 2)
- **Esperado**: a seção declara quantos casos morrem com o `app/` revertido ao merge-base e **quais** continuam verdes "por desenho". A lista nominal é a parte que não pode envelhecer em silêncio: ela é o argumento de que os verdes são verdes de propósito.
- **Observado** — `03:419-438` diz *"**26 dos 44** casos ficam vermelhos — 13 falhas de asserção (CT-03, CT-04 ×3, CT-05 ×3, CT-06, CT-07 ×3, CT-15, CT-20) e 13 erros"*, e *"Os **18** que continuam verdes … **CT-02** (varredura de fonte — mata M01, não prova presença)"*. **Medido agora**: `git checkout fbfe528 -- app/` e as duas suítes dão **28 de 45 vermelhos — 15 falhas + 13 erros**, e **CT-02 está entre as falhas**. Os dois a mais são consequência direta das correções do ciclo 2:
  - **CT-02** passou a reprovar porque o controle do **QA-17** exige `function urlDoPainel` na fonte **transformada**, e no merge-base o método não existe. Ou seja: o QA-17 **aumentou** a falsificabilidade da suíte, e o `03` ainda lista CT-02 como insensível ao diff.
  - **CT-24** nasceu (QA-19) e reprova.
- **Repro**: `git checkout fbfe528 -- app/` · as duas suítes → `tests: 45, passed: 17, failed: 15, errors: 13` · `git checkout HEAD -- app/` (árvore conferida limpa depois)
- **Ação exigida**: remedir para **28 de 45**, tirar CT-02 da lista dos verdes e registrar o motivo — é a melhor evidência que a feature tem de que o fechamento do QA-17 funcionou.

### QA-26 — o cabeçalho do arquivo de teste volta a contar errado: "Oito casos", são **nove** · **Cosmético** · destino 1

- **Dimensão**: L1 · **Relacionado a**: QA-06 (ciclo 1), que corrigiu este mesmo cabeçalho
- **Observado**: `tests/Tenancy/LinkDoPainelDaOrganizacaoTest.php:41-43` afirma *"**Oito** casos deste arquivo chamam `Tenant::urlDoPainel()` direto (CT-01, CT-03, CT-08, CT-09, CT-10, CT-14, CT-18, CT-22)"*. CT-24 chama `$organizacao->urlDoPainel()` duas vezes (`:968` e `:984`) — são **nove**.
- **Repro**: as chamadas de `->urlDoPainel()` no arquivo estão em 9 casos distintos, e a lista do cabeçalho nomeia 8.
- **Ação exigida**: a correção não é só somar um. Em CT-24 o gerador é **argumento de estado**, não sujeito da afirmação — o que a frase quer dizer é *"o sujeito da afirmação é o gerador"*, e é a frase que precisa mudar, com CT-24 nomeado como a exceção.

## Achados do ciclo 2 não integralmente fechados

<!-- Não contam na tabela de severidade deste ciclo: já foram contados no ciclo 2. -->

- **QA-18 fechou metade.** A ação exigida pedia fechar o parágrafo *"fica aberto um item"* no `03`
  **e** no `06`. O `03:119-124` fechou em `dcb038e`; o `06:214-220` → `### O que fica aberto`
  continua declarando `HasRoutes.php:193` em aberto, e o commit não tocou o `06`.
  Repro: `git diff b5a8f9c..HEAD --stat -- wikis/` lista só `03` e `04`.
  Segue **Minor, destino 1**.

## Dimensões — Ciclo 3

| # | Dimensão | Status | Observação |
|---|----------|--------|------------|
| A | Cobertura do requisito | ✅ | a matriz do ciclo 1 (RQ-01..RQ-05 + ADR-02) não é tocada pelo diff novo, que é teste e texto. A lacuna do ciclo 2 (QA-19, aviso sem cenário) **fechou**: CT-24 existe e mata os três mutantes |
| B | Fronteiras e dados | ✅ | nenhuma validação mudou em `b5a8f9c..HEAD` |
| C | Matriz de permissão | ✅ | o diff novo não toca portão, papel nem política |
| D | Observabilidade | ✅ | nenhum log novo; CT-24 não emite nem afirma log |
| E | Performance | ✅ | o diff novo é teste e markdown; zero consulta nova |
| F | UX de erro | ✅ | as três superfícies avisam da nova aba, e agora há caso afirmando cada uma |
| G | Tema e cor | ✅ estático | nenhum Blade, CSS, hex ou classe de cor no diff novo. Visual nos dois temas segue **não verificado** |
| H | Acessibilidade | ✅ | o ícone convencional (WCAG G201) nas duas telas de leitura e a prosa no formulário, agora travados por CT-24 |
| I | Segurança da superfície nova | ✅ | nada acrescentado à superfície |
| J | Regressão adjacente | ✅ | natureza `nova` ⇒ não obrigatória; rodada: 74 casos / 258 asserções dos gates (citação, documentação, variádica, filtro de tabela, helpers) + 45 da feature, verdes |
| K | Adequação da suíte | ✅ passo estático | CT-24 auditado por **mutação nas três superfícies** e CT-02 pelo mutante ganancioso — os dois oráculos que o ciclo 2 abriu estão vivos. Passo **medido** (`--mutate`) segue **não verificado**, sem driver de cobertura |
| L | Consistência documental | ❌ | **5 achados** — QA-22 a QA-26, todos L1/L3/L5 |

## Suspeitas Não Confirmadas — Ciclo 3

- **CT-24 não chama `->loadTable()` na listagem**, e a tabela `## Conformidade com Rules` do `03` declara *"`noPainelBootado('admin')` + `->loadTable()` em todo caso de listagem"*. **Não é violação**: a rule protege asserção sobre **HTML** (*"sem `->loadTable()` o HTML testado é o do esqueleto"*), e CT-24 lê a **definição** da coluna (`getTable()->getColumn()`), que não depende de registro carregado. O painel está bootado — `noAdminComo()` chama `noPainelBootado('admin')` (`tests/Tenancy/LinkDoPainelDaOrganizacaoTest.php:noAdminComo:93`). E a mutação prova o oráculo vivo. Abaixo do limiar; o que é absoluto demais é a frase do `03`.
- **`tests/Tenancy/BoasVindasTest.php:21` cita `HasRoutes.php:196`** — arquivo fora do diff desta feature, não conferido. Não é achado deste gate.
- **Os 1852/712 bytes do docblock de CT-02** — medidos aqui como 1853/713 com `strlen()` e **1852/712 com `strlen(trim())`**. O docblock bate com a segunda leitura. Não é achado.

## Não Verificado — Ciclo 3

- **Dimensão K, passo medido (`--mutate`)** — `php -m` não lista PCOV nem Xdebug, como nos ciclos 1 e 2. A mutação deste ciclo foi **manual e dirigida** (cinco mutantes aplicados e revertidos um a um, com `git status` limpo ao fim); não é mutation score.
- **Confronto visual e de console/rede** — Playwright MCP indisponível; Boost MCP fora do ar nesta sessão. Sem inventário de elementos, sem screenshot nos dois temas.
- **Suíte completa** — não re-executada por este gate. Foram rodados **119 casos dirigidos** (45 da feature + 74 dos gates), mais as seis execuções de mutação.
- **`tests/Browser/TemaEscuroTest.php`** — não executado. Instabilidade medida na `main` sem nenhuma das branches: **destino 4**, e não se re-reporta.
