# Progresso — Link de acesso ao painel da organização

## 1. Gerador da URL
- [x] Um ponto único que devolve a URL do painel da organização — `Tenant::urlDoPainel()`
      (`app/Models/Tenant.php:urlDoPainel:164`), que chama `Filament::getPanel('app')->getUrl($this)`.
      **Nenhuma classe nova**: o método vive ao lado de `urlDaLogo()`, que é a irmã exata ("o
      endereço de algo desta organização"), e as três superfícies já recebem o registro. Consumido
      pelos três schemas; a string `/app/` não aparece (CT-02 varre o arquivo do gerador)

## 2. `TenantForm` — no `EditTenant`
- [x] Entrada na seção `Identificação`, junto de nome e slug — `TextEntry::make('url_do_painel')`
      dentro de `Section::make('Identificação')`, entre o `slug` e o `ativo`. **CT-04 afirma o
      LUGAR**, subindo a hierarquia do schema até a `Section` e conferindo o título: RQ-02 é
      cláusula de lugar, e um header action passaria em todo cenário de HTML sem atendê-la
- [x] Ausente no `CreateTenant` — `->visible(fn (?Tenant $record) => $record !== null)`; CT-06
      afirma `assertSchemaComponentHidden` **e** que a `description` com `/app/{slug}` continua na
      tela

## 3. `TenantInfolist`
- [x] Entrada na seção `Identificação` — ao lado do `TextEntry::make('slug')->copyable()`

## 4. `TenantsTable`
- [x] Coluna com a URL clicável — `TextColumn::make('url_do_painel')`, com o endereço como
      **estado da coluna** (o conteúdo visível da célula), `->url()` + `->openUrlInNewTab()`.
      CT-04 usa `assertTableColumnStateSet`, que é o único oráculo que separa coluna de
      `Action->url()`: as duas emitem o mesmo `href="…" target="_blank"`
- [x] Citação de terceiro do achado **R2** atualizada no mesmo commit —
      `tests/Tenancy/FiltrosDeTabelaTenancyTest.php:9`

## 5. Documentação
- [x] `docs/{pt,en}/recursos/multi-tenancy.md` — seção "O atalho para o painel de cada
      organização" / "The shortcut to each organization's panel", sem link interno
- [x] `CHANGELOG.md` → `[Unreleased]` → `Adicionado`
- [x] Contadores dos readmes sincronizados (arquivos de teste 146→148 / 172→174; specs 63→64,
      que já estava dessincronizado pelo commit da wiki)

## Testes
- [x] `tests/Tenancy/LinkDoPainelDaOrganizacaoTest.php` — CT-01..CT-18 e CT-20 (38 casos com
      datasets)
- [x] `tests/Kit/LinkDoPainelSemTenancyTest.php` — CT-19

## Verificação Final
- [ ] `/ponytail:ponytail-review` no diff
- [x] `vendor/bin/pint --dirty --format agent` — `fixed`, sem pendência
- [x] `vendor/bin/filacheck --fix` — **All 17 rules passed!**
- [x] CTs da feature — **43 casos, 43 verdes, 226 asserções** (39 na primeira passagem; os
      **quatro** que faltavam entraram depois, ver **D-05**)
- [x] Testes existentes do tenant (`tests/Tenancy/**`) + gates de documentação e citação — 84
      verdes
- [x] `composer test:kit` — **2657 casos, 2657 verdes, 10 390 asserções**
- [x] **Custo medido** — ver `## Notas de Implementação`: **33 / 53** antes, **33 / 53** depois
- [x] Citações `arquivo:símbolo:linha` reverificadas por `tests/Kit/CitacoesDeCodigoTest.php`
      (CT-26) — inclui a **citação de terceiro** do achado R2 e mais **duas** que o próprio diff
      deslocou, listadas nos desvios
- [x] Falsificabilidade — com o `app/` revertido ao merge-base, **25 dos 43** casos ficam
      vermelhos (13 falhas de asserção + 12 erros por método inexistente). Dos **quatro** casos
      novos, **2 reprovam** (CT-22 e a linha `admin_vinculado` de CT-08, as duas por
      `Tenant::urlDoPainel()` não existir) e **2 passam dos dois lados** — CT-21 e a linha
      `globex` de CT-16 —, o que está **previsto** e não é defeito: ver **D-05**
- [x] IDs `[CT-nn]` do teste ⊆ `04` — **fecha**: os **22** IDs do teste (CT-01..CT-18, CT-20 e
      CT-22 em `tests/Tenancy`, CT-19 e CT-21 em `tests/Kit`) existem todos no `04`
- [x] IDs do `04` ⊆ teste — **fecha**: os quatro cenários que a revisão adversarial acrescentou
      (**CT-21**, **CT-22** e as duas linhas de `Examples` — CT-08 vinculado, CT-16 `globex`)
      agora têm caso escrito e versionado. Ver **D-05**. Gate bidirecional, saída **vazia**:
      `diff <(grep -oh 'CT-[0-9]\+' 04-casos-de-teste.md | sort -u) <(grep -oh 'CT-[0-9]\+' tests/Tenancy/LinkDoPainelDaOrganizacaoTest.php tests/Kit/LinkDoPainelSemTenancyTest.php | sort -u)`
- [x] Os 13 achados da revisão adversarial aplicados no `04` — ver `04-casos-de-teste.md` →
      `## Revisão Adversarial`. Contagens do cabeçalho derivadas por `grep`, não digitadas:
      **22** cenários, **9** regras, **36** mutantes, **3** lacunas
- [ ] `/code-review` no diff (step 7.5)

## Conformidade com Rules

| Rule | Glob que casou | Aplicada / n.a. / violada | Evidência |
|---|---|---|---|
| `filament.md` | `app/Filament/**` | **aplicada** | nenhuma Action nem item de navegação novo (por isso `tests/Kit/PermissoesDeAcoesTest.php` não pede declaração de autorização — é um dos motivos de a superfície ser coluna e `TextEntry`, e não `Action`); nenhuma construção reprovada pelo Blueprint (`filacheck`: 17/17, `AderenciaAoBlueprintTest` verde); nenhum `assignRole`/`syncRoles`; nada de papel, permissão ou seeder |
| `resources.md` | `app/Filament/App/Resources/**` | **n.a.** | a feature toca `Admin/Resources`, não `App/` |
| `filament-resources.md` | `app/Filament/**/Resources/**` | **n.a. no que ela exige** | a rule governa Resource novo (badge de contagem, colisão de trait, scope em `getEloquentQuery()`); a feature não cria Resource nem toca `getEloquentQuery()`. `BadgeDeNavegacaoTest`/`BadgeDeNavegacaoTenancyTest` continuam verdes |
| `specs.md` | `wikis/specs/**` | **aplicada** | citações por símbolo conferidas por `tests/Kit/CitacoesDeCodigoTest.php:[CT-26]`; **três** deslocadas pelo diff foram corrigidas (ver desvios) |
| `testes.md` | `tests/**` | **aplicada** | nenhum helper cruzado (os quatro do arquivo novo são usados só por ele — `HelpersDeTesteTest` verde); `noPainelBootado('admin')` + `->loadTable()` em todo caso de listagem; `semComentarios()` na única asserção de ausência sobre arquivo (CT-02); `TestHandler` no channel real em CT-09; CT-19 em `tests/Kit` porque é a única suíte com a tenancy desligada; `fronteiraDeRequest()` entre painéis em CT-09 e CT-10 |
| `models.md` | `app/Models/**` | **n.a. no que ela exige** | `Tenant` não tem Resource no `/app` (`ModeloCacheavel` não se aplica), e a feature não acrescenta `SoftDeletes` nem `InteractsWithMedia`. O método novo é leitura pura, sem query |

## Quality Gate

**Ciclo 1 (2026-09-21) — REPROVADO → especificação.** Blocker 0 · Major 3 · Minor 9 · Cosmético 2.
Relatório completo em `06-relatorio-qa.md`. Nenhum achado de implementação: o código atende
RQ-01..RQ-05 e as quatro ADRs, e os três Major são texto contra a árvore — **QA-01** (a rule
`specs.md` violada em 13 citações da wiki, e a evidência declarada aponta um gate que exclui
`wikis/specs/**`), **QA-02** (o `04` diz que CT-21/CT-22 "ainda não escrito" e que o gate `04 →
teste` está aberto, com o D-05 já fechado) e **QA-03** (D-05.a ainda sustenta a string
`?tenant={uuid}`, que é o achado 1 do step 7.5). Abertos também QA-04 a QA-14. **O PR não abre até
o ciclo 2.**

## Auditoria Pré-Implementação

### Revisão profunda (step 5) — premissas do plano contra o código real

| # | Premissa do plano | O código real diz | Correção aplicada na wiki |
|---|---|---|---|
| **R1** | Passo 1 manda "conferir se já existe helper de URL de tenant antes de escrever" | **Não existe.** `grep -rn "getTenantUrl\|tenantUrl" app/` devolve **zero**; os únicos hits de `filament.app` são nomes de rota em contextos não relacionados (`DashboardClassico.php`, `AcoesDeCriacao.php`, `ConfiguraFilamentGlobal.php`, `AppPanelProvider.php`). O passo 1 **cria**, não reaproveita | Passo 1 do `01` reescrito de "conferir e reaproveitar" para "criar, e aqui está a evidência de que não havia o que reaproveitar" |
| **R2** | O plano não previa impacto em citação de terceiro | **`tests/Tenancy/FiltrosDeTabelaTenancyTest.php:9` cita `TenantsTable.php:55`** — e a linha 55 hoje é o `->filters([`. A coluna nova entra no `->columns([…])`, que fecha na linha 53: **inserir a coluna desloca a linha 55 e invalida a citação de um teste que não é desta feature** | `## Impacto em Features Existentes` do `01` ganhou o item; entrou na Verificação Final como conferência obrigatória; e virou risco declarado |

> **R2 é o achado que justifica o step 5 nesta feature.** A citação está no **docblock de um teste
> alheio**, não na minha wiki — nenhum gate posterior a procuraria, porque o step 7 confere as
> citações *da wiki da feature*. E a rule `specs.md` do projeto trata citação errada como defeito.
> Uma coluna de tabela, que parece a mudança mais inócua possível, quebra uma afirmação a 40 linhas
> de distância num arquivo que o diff não abre.

### Varredura da classe irmã

**Não se aplica: a feature não cria classe nenhuma.** São três entradas declarativas em schemas
existentes (`TenantForm`, `TenantInfolist`, `TenantsTable`) mais um gerador de URL. Nenhum FQCN novo
para aparecer em `config/`, seeder, inventário de teste ou provider.

Conferido mesmo assim, porque "não se aplica" precisa ser verificado e não deduzido:
`grep -rn "TenantsTable\|TenantForm\|TenantInfolist" app config database tests` — as ocorrências são
só o próprio `TenantResource` e os dois testes de filtro já citados em R2.

### Auditoria Ponytail (step 6)

| # | Sugestão de corte | Aplicada? | Onde |
|---|---|---|---|
| — | a rodar | — | — |

## Blockers

Nenhum.

## Desvios do Plano

### D-01 — o "ponto único" é método de model, não classe nova

O passo 1 do `01` pede "um único ponto que devolve a URL do painel da organização" sem dizer onde.
Ficou em `Tenant::urlDoPainel()` (`app/Models/Tenant.php:urlDoPainel:164`), ao lado de
`urlDaLogo()`. Motivo: é a irmã exata — as duas respondem "o endereço de algo desta organização" —,
as três superfícies já recebem o registro, e a `## Filosofia de Implementação` do plano proíbe
classe nova. **CT-02 continua com sujeito**: ele varre o arquivo do gerador, que é este, e a string
`/app` não aparece nele fora de comentário.

### D-02 — CT-14 do `04` afirma uma propriedade FALSA da listagem, e foi medido

O `04` escreveu CT-14 como "a listagem custa o mesmo com uma e com cinco organizações". Medido nas
duas pontas do diff, com o mesmo arnês (sonda de contagem, duas medições quentes):

| | 1 organização | 5 organizações |
|---|---|---|
| **antes** (`git stash push -- app/`) | **33** | **53** |
| **depois** | **33** | **53** |

A coluna nova custa **zero** — o `## Modelo de Execução` do `01` está confirmado. Mas a listagem
**já** crescia 5 consultas por linha antes da feature, então o oráculo escrito nasceria **vermelho
contra a implementação correta**, medindo um N+1 de terceiro que a feature não introduziu e não pode
consertar (mudar a listagem está fora de escopo).

O caso escrito mantém o **mesmo oráculo** — invariância à cardinalidade, derivada de RQ-05 — aplicado
ao que a feature possui: a resolução do endereço. Zero consultas para uma, zero para cinco. É o que
mata M22. O número medido da tela ficou registrado no docblock do caso e no `CHANGELOG.md`, como
afirmação datada e não como asserção.

### D-03 — a linha `acento` do CT-16 está do lado errado da fronteira

O `04` listou `organização` como partição **inválida** do slug. `alpha_dash` do Laravel é
**unicode-aware**: sem o argumento `ascii` a regra é `/\A[\pL\pM\pN_-]+\z/u`
(`vendor/laravel/framework/src/Illuminate/Validation/Concerns/ValidatesAttributes.php:validateAlphaDash:403`),
e `ç`/`ã` são `\pL`. O slug acentuado **grava**.

Trocar `->alphaDash()` por `->alphaDash(ascii: true)` seria alterar uma validação que **não é desta
feature** para fazer um caso passar — recusado. O que foi feito:

- CT-16 perdeu a linha `acento` e ganhou **duas** que o `\pL` de fato recusa e que cobrem o risco
  real de um segmento de URL: **ponto** (`acme.painel`) e **percent-encoding** (`acme%2fpainel`).
- O acento passou para **CT-18**, do lado válido: ele grava, e o link segue o gravado.

**E CT-18 mediu uma terceira coisa, que ninguém tinha afirmado:** `Panel::getUrl()` **não**
percent-encoda o segmento — o endereço sai `http://…/app/organização`, com o UTF-8 cru, e o `e()` de
`generate_href_html()` escapa HTML, não URL. O link funciona (navegador e servidor encodam o
caminho), mas a forma canônica do endereço no kit é a crua, e agora está escrita.

### D-04 — o diff deslocou TRÊS citações, não uma

O achado R2 previu uma (`FiltrosDeTabelaTenancyTest.php:9` → `TenantsTable.php:55`). O método novo
no `Tenant` deslocou **outras duas**, que o R2 não podia prever porque o plano não dizia onde o
gerador ficaria: `TenantHeader.php:32` e `TenantInfolist.php:73` citavam o `urlDaLogo()` pela
linha antiga (`:138`). Hoje as duas citam `app/Models/Tenant.php:urlDaLogo:186` — o `:164`
escrito aqui **já envelheceu**, no mesmo parágrafo que descreve o envelhecimento, e só o
símbolo reancorou a citação. As três foram corrigidas no mesmo commit, e
quem as achou foi `tests/Kit/CitacoesDeCodigoTest.php:[CT-26]` — o gate automático, não a
conferência à mão.

**A citação do R2 mudou de forma, e não só de número.** Era `TenantsTable.php:55`, caminho solto:
`base_path('TenantsTable.php')` não resolve, então o CT-26 a **ignorava** — ela podia ficar errada
para sempre sem nada acusar. Virou
`app/Filament/Admin/Resources/Tenants/Tables/TenantsTable.php:ativo:85`, caminho completo e com
símbolo, que é a forma que o gate confere. O número andou outra vez depois disso — de `:83`
para `:85` — e a citação **não** apodreceu junto: quem a reancorou foi o símbolo, que é
exatamente o que a rule prescreve.

### D-05 — o `04` foi reconciliado DEPOIS da implementação, e quatro cenários ficaram sem teste

> **FECHADO.** Os quatro têm teste escrito e versionado, e o gate bidirecional de IDs devolve
> saída vazia nos dois sentidos. O que cada um virou está na coluna **Teste definitivo** da tabela
> abaixo, e as duas divergências entre a sonda e o definitivo estão logo depois dela.

A revisão adversarial disparada pelo Impacto 3 chegou **depois** de a feature fechar verde. Quatro
dos treze achados (**A-1**, **A-2/A-4**, **A-3**, **A-6**) já tinham sido endereçados **nos
testes** e não no `04` — por um período os testes foram mais fortes que a especificação deles, que
é a pior configuração possível: quem lê o `04` acredita que aquilo é o contrato, e quem apaga uma
linha do teste não encontra nada que reclame. Esta passagem reconciliou os dois.

**O que entrou no `04` e JÁ tem teste** (era a especificação que estava atrás): estado da coluna e
seção do formulário em CT-04 (A-1/A-2/A-4); sujeito do CT-02 nomeado (A-3); `fronteiraDeRequest()`
no `## Setup Global` (A-6); oráculo do CT-06 corrigido para o `href` mais a **presença** da prosa
(A-2 do relatório); `Dado` e medição prévia do CT-10 (A-9); não-efeito do CT-17 (A-13); separação
dos oráculos de HTML de CT-04 e CT-07 (A-11); contagem da matriz 11/3 (A-12); CT-14 reescrito
(D-02); linhas `acme.painel`/`acme%2fpainel` e o acento em CT-18 (D-03).

**O que entrou no `04` e AINDA NÃO tem teste** — os quatro foram **sondados** contra o código real
antes de serem escritos, com casos temporários que foram descartados. A direção está registrada
para que ninguém escreva o teste no sentido errado:

| Onde | Cenário especificado | Sonda | Teste definitivo |
|---|---|---|---|
| CT-16, última linha dos `Examples` | a edição recusa o slug **de outra organização gravada**, e o gravado não muda (A-5) | **PASSA hoje.** `->unique()` do Filament ignora o próprio registro por padrão nesta versão (`vendor/filament/forms/src/Components/Concerns/CanBeValidated.php:unique:563` + a propriedade `true` em `:shouldUniqueValidationIgnoreRecordByDefault:34`), então o mesmo `->unique()` sem argumento atende CT-13 **e** esta linha. Não é achado de implementação: é cenário que faltava | **confirma a sonda.** Linha `slug de OUTRA organização gravada` no dataset, mais a `Globex` no `Dado` (inerte para as demais). `assertHasFormErrors(['slug'])`, gravado segue `acme` |
| CT-08, 3ª linha dos `Examples` | o administrador da instalação **vinculado** à organização continua tomando **403** — vínculo não é papel (A-10) | **403**, como esperado. O portão 1 decide primeiro | **confirma a sonda.** Persona `admin_vinculado` no `match`, mesma pessoa de `administradorDaInstalacao()` com `tenants()->attach()` |
| **CT-21** (novo) | com a tenancy desligada, nenhuma das telas de `tests/Pest.php:telasDoKit:225` do painel `admin` responde **500**, e nenhuma exibe `href` do painel de negócio (A-7) | as 18 telas passam, 51 asserções | **confirma a direção, com asserção a mais.** As mesmas 18 telas, **55** asserções — a sonda afirmava UMA forma do endereço e o definitivo afirma **duas** (ver a divergência D-05.a) |
| **CT-22** (novo) | seguir o link de organização **inativa** sem vínculo devolve **404**, e as duas leituras concordam (A-8) | **404**; `canAccessTenant()` falso e `getTenants()` não a contém | **confirma a sonda**, as três asserções |

#### D-05.a — a sonda de CT-21 media UMA forma do endereço, e existem duas

Sem tenancy, `Panel::getUrl($tenant)`
(`vendor/filament/filament/src/Panel/Concerns/HasRoutes.php:getUrl:170`) **não** produz
`/app/{slug}`: `hasTenancy()` é falso, a rota não tem parâmetro `{tenant}`, e o ramo de
`Route::has()` entrega o modelo a `route()` como parâmetro extra — que vira **query string**,
`?tenant={uuid}`. Uma asserção que só procurasse `/app/acme` ficaria verde diante de uma entrada
renderizada na forma que a instalação single-tenant de fato produz. O caso definitivo afirma as
**duas**, e é daí que vêm as quatro asserções a mais (55, não 51).

Nenhuma das duas é `/app` solto: a raiz do painel de negócio aparece legitimamente no `/admin` (o
seletor de painéis), e a asserção nasceria vermelha contra a instalação correta.

#### D-05.b — o `04` diz "oito linhas de formato e a nona de unicidade", e a tabela tem oito

Achado de **especificação**, não de teste, e por isso registrado aqui em vez de corrigido: a prosa
de R8 no `04` (`## Regra R8` → "Uma recusa por linha") conta **nove** linhas em CT-16, enquanto a
tabela de `Examples` logo acima lista **oito** — sete de formato (`../outra`, `acme/painel`,
`acme painel`, `acme?x=1`, `acme.painel`, `acme%2fpainel`, vazio) e uma de unicidade (`globex`). O
teste segue a **tabela**, que é a parte executável do cenário: 8 linhas no dataset. A contagem da
prosa vem de antes de D-03 ter trocado a linha `acento` por duas, e não acompanhou. Fica para a
próxima passagem no `04`.

#### D-05.c — dois dos quatro passam com o `app/` revertido, e isso é o desenho

CT-21 e a linha `globex` de CT-16 ficam **verdes** sem a implementação. Não são oráculos deste
diff, e nunca foram:

- **CT-21 é asserção de ausência.** Ele mata M27/M36 — uma implementação **errada** (a entrada
  nascendo num widget, num hub ou no menu do `/admin`) —, não a ausência de implementação. Mesma
  categoria de CT-19, que também passa dos dois lados pela mesma razão.
- **A linha `globex` de CT-16 protege uma validação que não é desta feature.** O `->unique()` do
  `TenantForm` é anterior ao diff; a linha mata M34, que é o `unique` ser **perdido** numa
  edição futura. A sonda já dizia isso ("PASSA hoje... é cenário que faltava").

Os outros dois (CT-22 e `admin_vinculado` de CT-08) reprovam, mas por `Tenant::urlDoPainel()` não
existir — erro fatal, não falha de asserção. A força discriminante deles é contra M32 e M33
(mutações **futuras** dos dois portões), não contra a remoção do gerador.

**Um achado roteado ao TESTE, não à especificação (corte C-1).** O segundo `Então` do CT-02 foi
cortado do `04`: "o gerador não contém nenhuma concatenação do slug com um caminho" não é oráculo
executável, e exigiria regex adivinhado sobre fonte — o que `.ai/rules/testes.md` proíbe ("não
invente um regex, ele conta comentário como chamada").

**FECHADO.** O `preg_match` saiu do caso de CT-02
(`tests/Tenancy/LinkDoPainelDaOrganizacaoTest.php:[CT-02]`). O que carrega o cenário é a asserção
do **literal** — `expect($fonte)->not->toContain('/app')` sobre a fonte sem comentários —, e ela
continua. O motivo do corte ficou no docblock do caso, para que ninguém a reescreva.

## Notas de Implementação

### `TextEntry` no formulário, e por que não `TextInput->disabled()`

Em schemas unificados do Filament 5, `Filament\Infolists\Components\Entry` estende
`Schemas\Components\Component` — **não** `Field`. Consequência direta: ela não entra no estado do
formulário nem no `dehydrate`, que é o requisito duro do passo 2 do plano. Um `TextInput`
desabilitado com a URL viria no save e escreveria uma chave que não é coluna; CT-12 e CT-13 são os
casos que o pegariam.

Bônus não previsto: `Entry` usa o concern `CanOpenUrl`, então `->url()->openUrlInNewTab()` produz
exatamente o `href="…" target="_blank"` de `generate_href_html()` — o mesmo HTML da coluna e da
ficha. As três superfícies passaram a ter o mesmo oráculo de asserção sem nenhum adaptador.

### O que separa coluna de ação, e é uma linha

`Action::make()->url(…)->openUrlInNewTab()` emite HTML **idêntico** ao da coluna. Todo cenário de
`assertSeeHtml` da listagem passaria com a feature implementada como ação por linha, e a decisão do
usuário ("coluna, porque ela mostra o endereço") ficaria sem falsificador. O que separa as duas é o
**estado da coluna**: `assertTableColumnStateSet('url_do_painel', $endereco, $organizacao)` só tem
resposta se existir uma coluna, e só passa se o endereço for o **conteúdo** da célula.

### O 302 que parecia defeito e era a sessão

CT-09 visita o painel de negócio com duas personas no mesmo caso. Sem `flushSession()` entre elas o
segundo `GET` vinha **302 para o login**: o `AuthenticateSession` do painel grava o hash da senha na
sessão e desloga quando o do request seguinte não corresponde. A falha se lê como "a administradora
vinculada não entra" — e a única coisa errada era a sessão da anterior. Registrado no docblock do
caso.

### Custo medido

Ver **D-02**. Listagem: **33** consultas com uma organização e **53** com cinco, iguais antes e
depois. Resolução do endereço: **0** consultas, para uma e para cinco (CT-14).

### Falsificabilidade

`git stash push -- app/` e a suíte da feature: **22 dos 39 casos ficam vermelhos** — 12 falhas de
asserção (CT-03, CT-04 ×3, CT-05 ×3, CT-07 ×3, CT-15, CT-20) e 10 erros por
`Call to undefined method urlDoPainel()` (CT-01, CT-08 ×4, CT-09, CT-10, CT-14, CT-18 ×2).

Os 17 que continuam verdes são **por desenho**, e vale dizer quais: CT-02 (varredura de fonte — mata
M01, não prova presença), CT-06 (ausência no cadastro), CT-11 (não-efeito na pivot), CT-12 e CT-13
(a gravação, que tem de continuar funcionando), CT-16 e CT-17 (validação pré-existente de que a
feature **depende** sem ser dona) e CT-19 (a tela fechada sem tenancy). Nenhum deles afirma presença
do link.

## Step 7.5 — `/code-review` no diff (2026-09-21)

Quatro achados, todos fechados. A tabela completa, com o que verificou cada um, está no adendo do
`04-casos-de-teste.md`. O resumo do que **mudou em código**:

| Achado | Mudança |
|---|---|
| 1 — CT-21 guardava string inalcançável | oráculo trocado pela forma real (`/app/{chave}`) + **controle positivo** que mede o gerador |
| 2 — `urlDoPainel()` devolvia link morto sem tenancy | guarda `hasTenancy()` no gerador; ADR-03 revista; **CT-23** novo |
| 3 — comentário "21 queries" defasado | corrigido para 33/53, alinhado ao CHANGELOG e a CT-14 |
| 4 — CT-13 alegava fechar armadilha que não fecha | docblock corrigido para dizer o que o caso prova |

**Dois deles eu não teria encontrado sozinho**, e vale registrar por quê: o 1 e o 4 são asserções
que *parecem* proteger. Ambas vinham com docblock longo, raciocínio plausível e citação real de
`arquivo:linha` do vendor — e a conclusão errada. Os gates anteriores (revisão profunda, ponytail,
revisão adversarial do `04`) leem o **plano**; só o 7.5 lê o **diff** e pergunta se o código está
certo. É o achado que a própria skill já documenta como o de maior rendimento, e a feature confirmou.

### O erro que a correção do achado 1 produziu, e que valeu mais que ela

O controle positivo nasceu **vermelho**, e por um motivo que não era o esperado: o segundo argumento
de `expect()->toContain()` é **outra agulha**, não a mensagem de falha. Isso expôs que as asserções
de ausência de CT-21 já carregavam o mesmo defeito desde o início — `->not->toContain($x, $msg)`
exigia que a mensagem também estivesse ausente do HTML, o que é sempre verdade.

Migradas para `assertStringNotContainsString`. Efeito medido nas duas suítes da feature:
**174 → 227 asserções**, com um único cenário novo. A diferença são asserções que existiam no
arquivo e não contavam.

**Candidato a rule** (step 9): `toContain()`/`not->toContain()` do Pest não recebem mensagem.

## Rebase sobre o `main` pós-#93, e o que ele custou (2026-09-21)

O `main` avançou durante esta wiki: o PR #93 (host local) entrou. O rebase não foi de graça, e o
próprio kit acusou três defasagens que ele criou:

| Defasagem | De → para | Quem pegou |
|---|---|---|
| arquivos de teste nos READMEs | 149→150 fundação, 175→176 total | caso de contadores **vindo com o #93** |
| features especificadas | 64→65 (a wiki desta feature) | idem |
| `tests/Pest.php:telasDoKit` | `:224` → `:225`, empurrado pelo helper novo do host local | `CitacoesDeCodigoTest` |

Vale registrar a primeira linha: o caso que a pegou **nasceu no #93**, trava sete linhas do README
que antes envelheciam em silêncio, e **estreou pegando defeito de outro PR**. É evidência direta
do valor dele, do tipo que normalmente ninguém mede.

Conferido que o rebase não engoliu nada já entregue: `OrdemDasCascadeLayersTest` e as duas
referências a `configureOrdemDasCascadeLayers()` (a correção da `v0.37.1`) e o `HostLocalTest`
seguem na árvore.

### Suíte completa pós-rebase

**2.817 casos, 2.801 verdes, 16 pulados, 0 falhas**, 2026-09-21 — inclui a suíte de navegador.

### O falso positivo que quase virou achado

Numa execução anterior, `tests/Browser/TemaEscuroTest.php` reprovou duas vezes com contraste
`1,47:1` no `h1.fi-header-heading`. Duas rodadas vermelhas **não** sustentavam a conclusão de
"falha consistente", e ela estava errada: em seguida o arquivo passou **cinco vezes seguidas**,
sem mudança de código.

O diagnóstico está no próprio docblock do teste, que já documenta a assinatura — paleta escura
inteira sobre fundo claro é vazamento de tema entre cenários, não defeito de cor — e avisa que o
caso *"tem o formato de teste instável"*. As duas falhas vieram logo após a suíte cheia, o que
aponta contaminação entre arquivos de navegador.

Confirmado que não era desta branch: teste, `.env` e assets publicados são **idênticos** ao repo
principal (conferido por `diff`), onde o mesmo arquivo passou. Fica como **achado de isolamento de
teste, pré-existente**, não como regressão desta feature.

## Retrospectiva

<!-- Preenchido no fim. -->
