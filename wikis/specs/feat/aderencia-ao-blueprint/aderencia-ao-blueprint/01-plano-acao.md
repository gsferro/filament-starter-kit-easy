# Plano de Ação — Aderência total ao Filament Blueprint

> Requisito: `00-requisito.md` · Norma: `05-norma-blueprint.md` · Medição: `05-comparativo.md`

## Natureza da Wiki

- **Tipo**: correção (auditoria + refinamento)
- **Wiki ancestral**: todas as wikis de `feat/` desde a v0.18 — esta rodada mede o conjunto. As
  mais tocadas: `perfis-e-permissoes/tela-de-perfis` (N-29), `pagina-boas-vindas` (N-36),
  `travas-de-exclusao-e-upload-anonimo` (linha de base de segurança).
- **Toca infra compartilhada?**: **sim** — `tests/Pest.php` pode ganhar helper; `.ai/rules` ganha
  duas rules e uma emenda; READMEs inteiros. Regressão obrigatória: suíte completa no CI.

## Cobertura do Requisito

| RQ | Cláusula | Passo(s) | Observação |
|----|----------|----------|------------|
| RQ-01 | comparativo com as 23 referências + skill | — | feito: `05-norma-blueprint.md` + `05-comparativo.md` |
| RQ-02 | corrigir os desvios | 1–8 | — |
| RQ-03 | segurança > arquitetura > qualidade | 1, 2, 3 primeiro | ordem dos passos é a ordem de peso |
| RQ-04 | Shield aderente e funcionando desde a instalação | 1, 2 | medido: 100% pages/widgets; 5 resources sem teste negativo |
| RQ-05 | cada opt-in funciona e está documentado | 6, 7 | 10 chaves sem doc; `--no-npm`/`--no-seed`/`--force`/`--custom` ficam como lacuna medida (cobertos por CT) |
| RQ-06 | instalar em `TESTES KIT` | — | feito: `padrao` e `tenancy` |
| RQ-07 | MCPs Boost e Playwright | — | feito: Playwright com sessão real (N-42, N-06) |
| RQ-08 | READMEs sem divergência | 6 | 37 itens em `05-divergencias-readme.md` |
| RQ-09 | rules em concordância | 7 | 2 rules novas, 1 emenda |
| RQ-10 | sub-agentes e loops | — | 3 de auditoria + 2 de correção |
| RQ-11 | dúvidas técnicas | 8 | as lacunas declaradas do comparativo |

## Objetivo

Fechar os 9 FINDING de código/teste, as 37 divergências de documentação, as 2 dicas de §5 e as 3
lacunas de rules — e deixar **enforço automático** onde hoje há só prosa: um sweep de autorização
sobre `getResources()` que não existia, e é a ausência dele que explica a maioria dos achados.

## Superfície de UI

Nenhuma tela nova ou alterada. Só correções internas de componente (`->integer()`, `->sortable()`)
e de autorização. **Sem CT-B.** A regressão visual já está na suíte `Browser`.

## Impacto em Features Existentes

- **`ProjetoResource` fail-closed** (passo 1): fora de request de painel, consultas ao resource
  passam a devolver vazio. Quem consome fora de request hoje? `Spotlight/AcoesDeCriacao` consulta
  `canAccess()`, não a query. Jobs não consultam o resource. Risco baixo; coberto por CT.
- **`permite()` fail-closed** (passo 3): páginas com o trait cuja chave não resolve passam a
  **negar**. Medido: em request real todas resolvem. Fora de request, `DescobreCardsDoPainel`
  chama `canAccess()` — passa a esconder cartões de página não mapeada, que é o comportamento
  desejado (rule "CardItem não verifica autorização").
- **Sweep de resources** (passo 2): novo teste; se algum resource ganhar `ViewAny` sem policy, ele
  falha — que é o objetivo.

## Rollback

Reverter os commits. Sem migration, sem dado, sem config publicada.

## Dependências

Nenhuma nova. Blueprint segue fora do pacote (`bp:off` antes do commit).

## Riscos

- **Fail-closed quebrar caminho legítimo fora de request** — mitigação: CT que exercita
  `ProjetoResource` sem tenant e afirma vazio; CT que exercita `permite()` com página fora do mapa.
- **README: correção de número que muda de novo na semana seguinte** — mitigação: onde há teste
  que conta (`TextoDoEnvTest` conta chaves), citar o teste; onde não há, escrever "à data de".
- **Volume**: 37 edições de doc em dois arquivos de 2.300 linhas. Mitigação: sub-agente com a
  lista fechada e proibição de reescrever prosa fora do item.

## Channel de Log

Nenhum log novo. Autorização negada já é silenciosa por desenho (ADR da v0.20.0).

## Estrutura de Implementação

### 1. `ProjetoResource::getEloquentQuery()` fail-closed — N-04 (A)

- **Path**: `app/Filament/App/Resources/Projetos/ProjetoResource.php`
- Copiar o padrão de `App/Users/UserResource.php:176`: sem `Filament::getTenant()`, `whereRaw('1 = 0')`.
- Docblock: por que o escopo global da trait **não** basta (ele falha aberto por desenho, e a
  `BelongsToTenant.php:48-52` diz isso), e por que os três resources do painel agora falam a
  mesma língua.
- **Teste**: `tests/Tenancy/EscopoFailClosedTest.php` — sem tenant, `getEloquentQuery()->count()`
  é 0 com projetos no banco; com tenant, só os dele. Mutação: remover o override → vermelho.

### 2. Sweep de autorização sobre `getResources()` — N-29 (S)

- **Path**: `tests/Kit/PermissoesDeResourcesTest.php`
- Para cada painel, para cada resource com `getModel()` e policy: usuário com o papel do painel
  **sem** `ViewAny:X` → `GET index` = 403; com → 200. Mesmo padrão de `PermissoesDeTelasTest`
  (revogar do papel real via `semAPermissao()`, nunca papel vazio).
- Âncora de população: `toHaveCount(n)` com o total esperado de resources — se cair, o sweep
  parou de achar.
- Exclusões declaradas com motivo: resources de vendor sem policy do kit.
- É o **enforço** que substitui a prosa: resource novo sem policy quebra o CI.

### 3. `PermissaoDaTela::permite()` fail-closed — §5

- **Path**: `app/Support/PermissaoDaTela.php:70-72`
- Trocar o `: true` por `: false` **quando a chave não resolve mas o usuário existe**. Manter
  `true` quando não há usuário? Não — sem usuário a página de painel já exige `auth`. Decisão na
  ADR-02.
- Docblock reescrito: o `true` era "falha aberta declarada"; agora explica o cenário que a torna
  alcançável (mapa do Shield tocado antes do `SetUpPanel`) e por que fechar custa zero em request.
- **Teste**: caso em `PermissoesDeTelasTest` com página **fora do mapa** (mock de `getPages()` ou
  classe de teste) → `permite()` = false.

### 4. Qualidade de componente — N-11, N-14, N-19, N-34 (Q)

- Remover `ignoreRecord: true` nos 6 pontos (em `RoleResource.php:111` só o argumento; manter
  `modifyRuleUsing`).
- `->integer()` em `ConfiguracoesDoKit.php:300` e `:376`.
- `->sortable()` em `RoleResource.php:248`.
- `assertFormSet` → `assertSchemaStateSet` em `ConfiguracoesDoKitTelaTest.php:306`.
- **Teste** (um só, `tests/Kit/AderenciaAoBlueprintTest.php`): varredura que reprova
  `ignoreRecord: true`, `->reactive(`, `Filament\Forms\Get`, `assertFormSet`,
  `callTableAction`, `assertTableActionExists` em `app/` e `tests/`. É o enforço de N-11..N-15 e
  N-34 de uma vez.

### 5. Cobertura de teste — N-30, N-31, N-32 (Q)

- **Path**: `tests/Kit/AgenteIaResourceTest.php` (novo — o resource sem uma linha de teste),
  `tests/Kit/FiltrosDeTabelaTest.php` (novo), acréscimos em `ConviteTest`, `AdminDaOrganizacaoTest`.
- Validação com dataset: `AgenteIa`, `Admin/Convites`, `App/Users`.
- Ações: `callAction('recusar')` com efeito e a metade negativa; `assertActionVisible/Hidden` para
  `convitesRecebidos`; `assertActionHasUrl` para `dashboardAiTasks`.
- Filtros: `filterTable()` para os 6 sem teste, cada um com `assertCanSee` + `assertCanNotSee`.

### 6. READMEs — RQ-08 (D)

- **Path**: `README.md`, `README.en.md`, `.env.example`
- Os 37 itens de `05-divergencias-readme.md`, delegados a sub-agente com a lista fechada.
- Prioridade: (a) o bloco Windows/sem-TTY em inglês (#38); (b) seção do hub (#36); (c) as 10
  chaves `KIT_*` (#3-7); (d) números (#9, #17-27); (e) flags (#10-13); (f) pacotes (#28-31).
- `KIT_ADMIN_NAME` entra no `.env.example`. `KIT_REPOSITORY` também, comentado.

### 7. Rules — RQ-09

- **Nova** (`app/Filament/**/Resources/**`): resource do `/app` tem `getEloquentQuery()`
  fail-closed; a trait não basta.
- **Nova** (`app/Filament/**`): `unique()`/`scopedUnique()` sem `ignoreRecord` — o v4+ já ignora;
  apontar para o teste de varredura do passo 4.
- **Emenda** em `filament.md` → "Page, Widget e Action novos nascem com a permissão consultada":
  acrescentar **Resource**, apontando para o sweep do passo 2.
- **Emenda** em `filament.md` → "Em Page, canAccess() sozinho basta": uma frase separando acesso
  à tela (`canAccess`) de autorização de ação (`get*AuthorizationResponse`).

### 8. Lacunas declaradas — RQ-11

Registrar no `03-progresso.md`, sem fingir cobertura:

- `--no-npm`, `--no-seed`, `--force`, `--custom` do `kit:install` não exercitados em instalação
  real nesta rodada (cobertos por CT).
- `kit:admin`, `kit:update`, `kit:midia-privada`, `kit:convites-lembrar`, `kit:arte` idem.
- `preventFilePathTampering` global (N-36): decisão em ADR-03 — **não** aplicar agora.
- Contagens do README sem critério operacional ("telas navegáveis").

## Commits

Um por passo, na ordem de peso:

1. `:lock: fix(app): ProjetoResource fecha sem organizacao, como os irmaos`
2. `:white_check_mark: test(shield): sweep de autorizacao sobre os resources dos tres paineis`
3. `:lock: fix(shield): PermissaoDaTela::permite() falha fechado quando a chave nao resolve`
4. `:recycle: refactor(filament): aderencia ao Blueprint — ignoreRecord, integer, sortable, helpers`
5. `:white_check_mark: test(filament): validacao, acoes e filtros que a auditoria achou sem teste`
6. `:memo: docs(readme): 37 divergencias entre o que o README afirma e o que o kit faz`
7. `:memo: docs(rules): resource fail-closed, unique sem ignoreRecord, e Resource no enforço`
8. `:memo: docs(wiki): auditoria de aderencia ao Blueprint`

## Verificação Final

- [ ] `vendor/bin/pint --dirty`
- [ ] `vendor/bin/phpstan analyse`
- [ ] testes novos, cada um com mutação verificada
- [ ] `composer bp:off` — Blueprint fora do pacote antes do commit
- [ ] CI 100% verde no PR
- [ ] merge, tag, release
