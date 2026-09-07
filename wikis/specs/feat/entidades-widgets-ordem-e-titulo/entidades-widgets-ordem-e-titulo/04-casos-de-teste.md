# Casos de Teste — Listagem de entidades: ordem dos widgets e título

> Requisito: `00-requisito.md` · Plano: `01-plano-acao.md`
> Derivado do **requisito**, não do plano. Nenhum cenário foi escrito olhando implementação — o `01` entrou só para paths, a suíte e a `## Superfície de UI`.

## Perfil de Derivação

| Área | P | I | P×I | Perfil |
|---|---|---|---|---|
| A — posição dos widgets na listagem | 1 (lista declarativa, isolada) | 1 (cosmético, reversível) | 1 | mínimo |
| B — rótulo no título e no breadcrumb | 1 (string devolvida por método) | 1 (cosmético, reversível) | 1 | mínimo |

- Perfil mínimo nas duas áreas: passos 1, 2, 5 e 6 do pipeline; técnica EP; 1 cenário por regra (um `Esquema` conta como 1). Sem revisão adversarial (nenhuma área com Impacto 3).
- Técnicas aplicadas: EP (partições do rótulo configurado; partições de posição acima/abaixo da tabela).
- Cenários: 3 · Regras: 5 · Mutantes previstos: 15 · Sem matador: 0
- Auditoria ponytail (step 6 da `feature-wiki`): CT-04 (menu) fundido em CT-02; oráculo duplicado de CT-02 (`getTitle()` no componente + `<title>` via HTTP) reduzido ao HTTP; segundo `Então` de CT-01 removido por ser consequência do primeiro.
- **Divergência declarada com a skill**: a Verificação Final usa `composer test:kit` em vez de `pest --parallel --tia` — a rule `.ai/rules/testes-browser.md` mediu que sem PCOV o `--tia` não termina. A rule vence.

## Varredura SFDIPOT

| Letra | O que existe nesta feature | Cenários gerados |
|---|---|---|
| **S** | `ListTenants` (página de listagem do `TenantResource`) e `TenantResource` (rótulos); os quatro widgets existentes não mudam; um teste da ancestral é atualizado | CT-01, CT-02, CT-03 |
| **F** | ordenar blocos na página (cabeçalho → tabela → rodapé); devolver o rótulo configurado sem alterar a caixa; não regredir o menu | CT-01 (ordem), CT-02/CT-03 (caixa), CT-02 (menu) |
| **D** | o rótulo plural vem de `config('kit.tenancy.label_plural')` — partições: uma palavra capitalizada ("Entidades", valor literal do requisito); várias palavras com preposição minúscula e substantivo capitalizado ("Unidades de Negócio"); em minúsculas pela própria instalação ("entidades" — premissa registrada no `00`: exibe-se como configurado, **sem** cenário: não é oráculo do requisito e o `00` a declara premissa sem cláusula) | CT-02, CT-03 (via `Exemplos`) |
| **I** | UI: `/admin/{slug}` (listagem), `/admin/{slug}/create` (breadcrumb); nenhum comando, job ou API | CT-01…CT-03 |
| **P** | widgets Filament são lazy por default — no HTML inicial cada um é placeholder Livewire, então texto de heading não serve de marcador; tabela com `deferLoading` global do kit — o contêiner renderiza, as linhas não. Ambos determinam o marcador de CT-01, não o oráculo | CT-01 |
| **O** | persona: administrador da instalação (`admin`), único que abre a tela; tenancy **ligada** (a tela responde 403 sem ela — `TenantResource::canAccess()`), logo suíte `Tenancy`; volume irrelevante | CT-01…CT-03 |
| **T** | não se aplica: nenhum dado temporal, concorrência ou expiração muda posição ou rótulo | — |

## Mapa de Regras

| Regra | Área (perfil herdado) | Origem (`RQ`) | Técnica | Cenários |
|---|---|---|---|---|
| R1 — acima da tabela há um único widget, a visão geral | A (mínimo) | RQ-01, RQ-02 | EP (posição: acima × abaixo da tabela) | CT-01 |
| R2 — os três widgets de detalhe estão abaixo da tabela, todos presentes, na ordem atual | A (mínimo) | RQ-03 | EP (posição) — ordem relativa sob `@premissa` | CT-01 |
| R3 — o título da listagem (cabeçalho e aba) é o rótulo plural exatamente como configurado | B (mínimo) | RQ-04 | EP (caixa do rótulo: 1 palavra capitalizada × várias palavras com preposição) | CT-02 |
| R4 — o primeiro item do breadcrumb é o rótulo plural exatamente como configurado, na listagem e nas demais páginas do Resource | B (mínimo) | RQ-05 | EP (caixa do rótulo × página) | CT-03 |
| R5 — o rótulo de navegação (menu) continua sendo o configurado | B (mínimo) | RQ-06 | EP (caixa do rótulo) | CT-02 (segundo `Então`) |

R1 e R2 compartilham CT-01: a ordem completa dos cinco blocos (visão geral, tabela, três de detalhe) é uma única asserção de sequência, e separá-la em dois cenários repetiria o mesmo arranjo para afirmar metades do mesmo oráculo. R3 e R5 compartilham CT-02: o mesmo `Dado` (rótulo configurado) alimenta título e menu, e o `Esquema` já percorre as duas partições de caixa — um cenário só para o menu repetiria o arranjo inteiro para uma asserção.

## Fronteira com o Plano

| Item do PRD | Recusado como oráculo porque | Destino |
|---|---|---|
| `getHeaderWidgets()` / `getFooterWidgets()` como mecanismo | escolha de implementação — o requisito fala de posição na tela, não de método | detalhe: o oráculo de CT-01 é a ordem no HTML renderizado, não a lista devolvida (a lista fica no `[CT-12]` da ancestral, atualizado pelo passo 3 do PRD) |
| marcador `fi-ta-ctn` da tabela e nome Livewire dos widgets | escolha de implementação / detalhe de plataforma | detalhe do cenário (seção Setup) |
| remoção de `mb_strtolower()` como correção | mecanismo — o requisito pede a caixa certa, não diz como | nenhum cenário afirma sobre o método; CT-02/CT-03 afirmam sobre título e breadcrumb |
| "Criar Entidade" no título da tela de criação | comportamento visível que **só o PRD** determina (consequência da correção) | **não vira `Então`**; já registrado em `00` → Ambiguidades como premissa com plano B |
| ordem relativa dos três widgets abaixo da tabela e a grade de 2 colunas | comportamento visível que só o PRD determina (o requisito diz "depois coloque os demais", sem ordem) | ordem fixada em CT-01 sob `@premissa` (`00` → Ambiguidades, RQ-03); grade **não** vira oráculo — layout, fora do que o HTML prova |
| suíte `Tenancy`, helpers `usuarioComPapel()`/`noPainelBootado()`, seeders | convenção de teste do projeto | Setup |

**Perguntas em aberto** (já replicadas em `00-requisito.md` → `## Ambiguidades`; nenhuma nova nasceu na derivação):

- RQ-03 — quais são "os demais widgets" e em que ordem? Premissa: os três existentes, na ordem atual. CT-01 marcado `@premissa`; se negado, a lista de marcadores de CT-01 muda.
- RQ-04/RQ-05 — "como configurado" ou Title Case? Premissa: como configurado. Se negado, a linha "Unidades de Negócio" de CT-02 e CT-03 inverte para "Unidades De Negócio".

## Setup Global

### Personas

- `administrador da instalação` — `usuarioComPapel('admin', null, 'adm@example.com')` + `$this->actingAs(...)`, como em `tests/Tenancy/InsightsDasOrganizacoesTest.php`.

### Fixtures

- Nenhum registro de organização é necessário: posição de widget e rótulo não dependem de haver linhas na tabela (o contêiner da tabela renderiza vazio).
- Rótulo: `config()->set('kit.tenancy.label_plural', '<rotulo>')` **antes** de montar a página. O `phpunit.xml:KIT_TENANCY_LABEL_PLURAL:67` força `KIT_TENANCY_LABEL_PLURAL=Organizações`; por isso nenhum cenário depende do default — cada linha de `Exemplos` fixa o valor lido. O slug **não** muda (`kit.tenancy.slug` é chave separada), então a URL continua `/admin/organizacoes`.
- Painel: `noPainelBootado('admin')` antes do `Livewire::test()` (regra `.ai/rules/testes.md` — teste de componente não atravessa o middleware que boota o painel).
- Seeders no `beforeEach`: `ShieldPermissionsSeeder` e `PapeisSeeder` (a tela exige `ViewAny:Tenant`).

### Marcadores de CT-01 (detalhe de implementação, não oráculo)

- Widget: o nome Livewire do componente — aparece no atributo `wire:snapshot` da raiz de cada componente montado, inclusive no placeholder lazy. *(alterado em 2026-09-07 na implementação: no Livewire 4 esse nome é o próprio FQCN e `app(Widget::class)->getName()` devolve `null` fora do mount; o marcador vira o FQCN com a contrabarra dobrada, como o JSON do snapshot o escreve.)*
- Tabela: a classe `fi-ta-ctn` do contêiner da tabela, renderizada mesmo com `deferLoading`.
- Asserção: `assertSeeHtmlInOrder([...])` do Livewire sobre o HTML inicial de `Livewire::test(ListTenants::class)`. Se na implementação o nome do componente não aparecer literal, cair para a alternativa 2 da ADR-03 e registrar em `03` → Notas — nunca trocar por `assertSee` de heading, que não está no HTML inicial.

### Fakes

- Nenhum. Sem fila, e-mail, HTTP externo ou evento.

### Estratégia de DB

- `RefreshDatabase` global via `pest()->extend(TenancyTestCase::class)->use(RefreshDatabase::class)->in('Tenancy')` (`tests/Pest.php:TenancyTestCase:78-81`). Suíte `Tenancy` porque `TenantResource::canAccess()` exige `kit.tenancy.enabled`, que só `Tests\TenancyTestCase` liga.

---

## Regra R1 — acima da tabela há um único widget, a visão geral · Regra R2 — os três de detalhe ficam abaixo da tabela

> `RQ-01`, `RQ-02`, `RQ-03` · perfil **mínimo** · técnica: **EP** (posição de cada widget: acima × abaixo da tabela)

```gherkin
# language: pt

Funcionalidade: Posição dos widgets na listagem de entidades

  Regra: acima da tabela há um único widget, a visão geral; os três widgets de detalhe ficam abaixo dela

    @premissa
    Cenário: [CT-01] a página renderiza visão geral, tabela e os três widgets de detalhe, nesta ordem
      Dado o administrador da instalação com a tenancy ligada
      Quando ele abre a listagem de entidades
      Então o HTML da página contém, nesta ordem: o widget de visão geral, a tabela, o widget de usuários únicos por organização, o widget de acessos por painel e o widget de atualizações recentes
```

> `@premissa` (RQ-03, `00` → Ambiguidades): a ordem dos três abaixo da tabela é a atual. Uma única asserção de sequência: qualquer widget de detalhe antes da tabela, ou a visão geral depois dela, quebra a ordem.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M1 | os quatro continuam acima da tabela, só reordenados (a visão geral primeiro) | CT-01 — os três de detalhe aparecem antes de `fi-ta-ctn` |
| M2 | a visão geral vai para baixo da tabela junto com os outros | CT-01 — a visão geral aparece depois de `fi-ta-ctn` |
| M3 | a visão geral some (só os três de detalhe, abaixo) | CT-01 — marcador da visão geral ausente |
| M4 | um dos três de detalhe é esquecido na mudança | CT-01 — marcador ausente |
| M5 | os três são declarados num método que o Filament não chama (nome errado) | CT-01 — os três marcadores ausentes; o `[CT-12]` da ancestral por closure **não** pegaria este |
| M6 | ordem relativa dos três trocada | CT-01 — sequência fixa (sob `@premissa`) |

---

## Regra R3 — o título da listagem é o rótulo plural exatamente como configurado · Regra R5 — o rótulo de navegação continua sendo o configurado

> `RQ-04`, `RQ-06` · perfil **mínimo** · técnica: **EP** (caixa do rótulo: uma palavra capitalizada × várias palavras com preposição minúscula e substantivo capitalizado)

```gherkin
# language: pt

Funcionalidade: Rótulo configurado no título e no menu da listagem de entidades

  Regra: o título da página (cabeçalho e aba do navegador) e o item de menu exibem o rótulo plural exatamente como foi configurado

    Esquema do Cenário: [CT-02] título e menu preservam a caixa do rótulo configurado
      Dado o administrador da instalação com a tenancy ligada
      E o rótulo plural das organizações configurado como "<rotulo>"
      Quando ele abre a listagem de entidades
      Então a aba do navegador exibe "<rotulo>" dentro da tag <title>
      E o rótulo de navegação do Resource de entidades é "<rotulo>"

      Exemplos:
        | rotulo               | # partição                                                                 |
        | Entidades            | uma palavra capitalizada — valor literal do requisito                       |
        | Unidades de Negócio  | várias palavras: preposição minúscula E substantivo interno capitalizado    |
```

> Discriminância das linhas: "Entidades" fica diferente de "entidades" (mata o rótulo ainda minúsculo). "Unidades de Negócio" fica diferente de "Unidades De Negócio" (Title Case por `ucwords`) **e** de "Unidades de negócio" (`ucfirst` sobre o rótulo minúsculo) — uma linha só com "Unidades de negócio" não distinguiria `ucfirst(strtolower())` de "como configurado". O `<title>` e o `<h1>` do cabeçalho saem do mesmo `getTitle()` da página (`vendor/filament/filament/resources/views/components/layout/base.blade.php:getTitle():30`), por isso o cenário afirma só a aba, por HTTP — afirmar também o método no componente seria o mesmo oráculo duas vezes (corte da auditoria ponytail).

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M7 | rótulo continua sendo posto em minúsculas antes de virar título | CT-02 linha "Entidades" |
| M8 | título capitaliza toda palavra (`ucwords`), como o Filament faz em inglês | CT-02 linha "Unidades de Negócio" |
| M9 | título capitaliza só a primeira letra de um rótulo já minúsculo (`ucfirst(strtolower())`) | CT-02 linha "Unidades de Negócio" |
| M10 | título fixo ("Organizações") ignorando o rótulo configurado | CT-02 — as duas linhas |
| M14 | a correção do título passa a capitalizar também o menu (`ucwords`) | CT-02 linha "Unidades de Negócio", segundo `Então` |
| M15 | o menu passa a derivar do rótulo do modelo enquanto este ainda é minúsculo (correção parcial, ordem errada das mudanças) | CT-02 — as duas linhas, segundo `Então` |

---

## Regra R4 — o primeiro item do breadcrumb é o rótulo plural exatamente como configurado

> `RQ-05` · perfil **mínimo** · técnica: **EP** (caixa do rótulo × página do Resource)

```gherkin
# language: pt

Funcionalidade: Rótulo configurado no breadcrumb das páginas de entidades

  Regra: o primeiro item do breadcrumb exibe o rótulo plural exatamente como foi configurado, em toda página do Resource

    Esquema do Cenário: [CT-03] o breadcrumb preserva a caixa do rótulo configurado
      Dado o administrador da instalação com a tenancy ligada
      E o rótulo plural das organizações configurado como "Unidades de Negócio"
      Quando ele abre a página "<pagina>" das entidades
      Então o primeiro item do breadcrumb é "Unidades de Negócio"

      Exemplos:
        | pagina   | # partição                                                                  |
        | listagem | a página do requisito                                                        |
        | criação  | outra página do mesmo Resource — o primeiro item do breadcrumb é compartilhado |
```

> "Unidades de Negócio" é a única linha de rótulo porque mata os três mutantes de caixa de uma vez (minúsculo, `ucwords`, `ucfirst`); "Entidades" já está coberto em CT-02 e a fonte do breadcrumb é a mesma do título. A partição aqui é a **página**: uma correção feita só no título da listagem deixaria o breadcrumb das outras páginas errado.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M11 | só o título da listagem é corrigido; o breadcrumb continua lendo o rótulo minúsculo | CT-03 linha "listagem" |
| M12 | breadcrumb fixo ("Organizações") ignorando o rótulo configurado | CT-03 — as duas linhas |
| M13 | breadcrumb corrigido só na página de listagem (sobrescrito ali), não no Resource | CT-03 linha "criação" |

---

## Checklist de Taxonomia

| Item | Cenário que mata |
|---|---|
| IDOR / autorização horizontal | não se aplica: nenhuma rota com `{id}` é alterada; a barreira `TenantResource::canAccess()` não muda e é coberta por `[CT-15]` da ancestral |
| Autorização exercida na ação (não só `can()`) | não se aplica: nenhuma ação nova |
| Idempotência (ancorada no agregado) | não se aplica: nenhuma escrita |
| Concorrência | não se aplica |
| Fronteira no ponto de entrada (gravação) | não se aplica: o rótulo entra por Settings, coberto por `tests/Kit/ConfiguracoesDoKitTest.php`; fora de escopo declarado no `00` |
| Domínio condicionado | não se aplica |
| Estado × operação de escrita | não se aplica: nenhuma entidade muda de estado |
| Ausente ≠ null ≠ vazio | não se aplica: `config()` tem default e Settings valida; rótulo vazio fora de escopo (`00`) |
| Paginação / ordenação | não se aplica: a tabela não muda; a posição dela sim, e é CT-01 |
| Timezone / DST | não se aplica |
| Unicode / limite de varchar | rótulo com acento e cedilha em CT-02/CT-03 ("Negócio") — prova que a caixa é preservada sem corromper multibyte; limite de tamanho fora de escopo |
| Unicidade + soft delete | não se aplica |
| CRUD combinado | não se aplica |
| Mass assignment | não se aplica |
| Upload | não se aplica |
| Precisão monetária | não se aplica |
| **Widget escondido por `canView()`** (coluna `painel` ausente) continua escondido no rodapé | não se aplica como cenário novo: `[CT-16]` da ancestral afirma `canView()` por widget, e o filtro do Filament é o mesmo para cabeçalho e rodapé (`Page::getWidgetsSchemaComponents()`) — regressão coberta pela suíte da ancestral, exigida pelo tipo `ajuste` |

## Índice de Cenários

| ID | Cenário | Regra | Técnica | Camada | Arquivo | Mata |
|----|---------|-------|---------|--------|---------|------|
| CT-01 | visão geral, tabela e os três de detalhe, nesta ordem no HTML | R1, R2 | EP | componente Livewire (`Tenancy`) | `tests/Tenancy/EntidadesWidgetsOrdemETituloTest.php` | M1–M6 |
| CT-02 | `<title>` da listagem e rótulo de navegação preservam a caixa do rótulo — 2 linhas | R3, R5 | EP | Feature HTTP (`<title>`) + método estático do Resource (menu) | `tests/Tenancy/EntidadesWidgetsOrdemETituloTest.php` | M7–M10, M14, M15 |
| CT-03 | primeiro item do breadcrumb preserva a caixa — listagem e criação | R4 | EP | componente (`getBreadcrumbs()`) | `tests/Tenancy/EntidadesWidgetsOrdemETituloTest.php` | M11–M13 |

**Cenário da ancestral que muda por causa desta wiki** (não é CT desta wiki; é regressão do tipo `ajuste`):

| ID | Onde | O que muda |
|----|------|------------|
| CT-12 | `wikis/specs/main/insights-das-organizacoes/04-casos-de-teste.md` e `tests/Tenancy/InsightsDasOrganizacoesTest.php` | passa a afirmar as **duas** listas — visão geral no cabeçalho; os três de detalhe no rodapé, na ordem — com a marca `*(alterado em 2026-09-06: wiki entidades-widgets-ordem-e-titulo)*`. Continua matando "widget esquecido" (M4) pelo lado estrutural; CT-01 mata pelo lado renderizado |

**Camada de CT-02** — desempate pelo observável: o requisito fala do que a pessoa vê (título da aba, menu). O `<title>` só existe no layout HTTP, daí a asserção de Feature (`assertSeeInOrder(['<title>', $rotulo, '</title>'], escape: false)` sobre `GET /admin/organizacoes`); o `<h1>` sai do mesmo `getTitle()` que a aba e não precisa de asserção própria. O menu é montado a partir de `TenantResource::getNavigationLabel()`, único ponto observável sem renderizar a barra lateral inteira (o rótulo também aparece no `<title>`, então `assertSee` na página não distinguiria os dois) — método estático com container (`config()`), no mesmo cenário.

## Cogitado e cortado

| Cenário cogitado | Por que foi cortado |
|---|---|
| CT-B: `visit('/admin/organizacoes')` medindo `getBoundingClientRect()` de cada bloco | ordem de blocos empilhados num template linear é ordem no HTML; browser só para o que só ele prova (`.ai/rules/testes-browser.md`). Mesmo mutante, dezenas de segundos a mais |
| Linha "entidades" (rótulo configurado em minúsculas) em CT-02 | premissa de comportamento sem cláusula no requisito — "exibe como configurado" é a direção assumida no `00`, mas nenhum RQ a determina; um `Então` afirmaria comportamento que só a premissa sustenta. Se o solicitante decidir, vira Adendo e a linha entra |
| Linha com o rótulo default "Organizações" sem `config()->set()` | o `phpunit.xml` força o valor: o cenário mediria o ambiente. As duas linhas fixam o valor lido |
| Título da tela de criação ("Criar Entidade") | consequência do PRD, não do requisito (Fronteira com o Plano) |
| Asserção sobre a grade (2 colunas, `columnSpan`) | layout, não provável por HTML; nenhum RQ a determina |
| Segundo `Então` de CT-01 ("nenhum widget de detalhe aparece antes da tabela") | consequência lógica da asserção de sequência; cortado na auditoria ponytail |
| CT-04 — cenário só para o rótulo de navegação | mesmo `Dado` e mesmas partições de CT-02; virou o segundo `Então` do `Esquema` (auditoria ponytail) |
| Asserção `getTitle()` no componente em CT-02 | mesma fonte do `<title>` já afirmado por HTTP (`base.blade.php:getTitle():30`); oráculo duplicado (auditoria ponytail) |

## Sem CT-B

- Motivo: nenhuma linha da `## Superfície de UI` afirma algo que só o navegador prova. Posição dos blocos é ordem no HTML (template linear do Filament: cabeçalho → conteúdo → rodapé); título, breadcrumb e menu são strings de método. Não há JavaScript, tema, geometria calculada por CSS nem acessibilidade em jogo. Gate do `05` não passa; arquivo não criado.
