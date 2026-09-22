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
