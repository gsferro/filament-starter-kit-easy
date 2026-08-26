# Casos de Teste — Aderência ao Blueprint

> Requisito: `00-requisito.md` · Norma: `05-norma-blueprint.md` · Comparativo: `05-comparativo.md`
> Derivados da **norma e do requisito**, não da implementação. Os quatro sweeps abaixo são o enforço
> que a ADR-04 decidiu: cada um substitui uma rule em prosa por um teste que fica vermelho com o nome
> do arquivo culpado.

## Perfil de Derivação

| Área | P | I | P×I | Perfil |
|---|---|---|---|---|
| Autorização de resources (N-29) | 3 | 3 | **9** | completo |
| Escopo de organização fora de request (N-04) | 2 | 3 | 6 | padrão |
| `permite()` fail-closed (§5 → ADR-02) | 2 | 3 | 6 | padrão |
| Varredura textual (N-11..N-15, N-34) | 1 | 1 | 1 | mínimo |
| Validação, ações e filtros (N-30..N-32) | 2 | 2 | 4 | padrão |

**Por que N-29 é `completo`**: `P=3` porque a causa (descoberta de policy por convenção) é invisível
no código do kit — a policy existe, a permissão existe, e nada acusa; `I=3` porque o efeito medido é
exposição de trilha de auditoria, logs de e-mail e filas a qualquer usuário do painel.

## Varredura SFDIPOT

| Letra | O que existe | Cenários |
|---|---|---|
| **S** | 10 policies, 1 mapa de registro, 1 subclasse de resource + página, 3 `getEloquentQuery()`, 1 predicado | CT-01..CT-10 |
| **F** | três funções de recusa (índice sem permissão, query sem tenant, página fora do mapa) e uma de aceitação (índice com permissão) | CT-01..CT-08 |
| **D** | registros de duas organizações no banco para provar o vazamento; permissões `ViewAny:*` reais do seeder | CT-05, CT-06 |
| **I** | HTTP `GET` no índice (o caminho real do usuário), chamada estática à query (o caminho de job/comando), chamada estática ao predicado | CT-03/04, CT-05, CT-07 |
| **P** | Filament 5.7.6: `CanAuthorizeResourceAccess:19` chama `canAccess()`; `HasAuthorization:35-37` honra `$shouldSkipAuthorization`; Shield `getResources()` é `once()`. Três contratos do framework que os casos fixam | CT-02, CT-04 |
| **O** | papel do painel (`admin`, `infra`), nunca `master_global` — ele vence por `Gate::before` e mascararia tudo | CT-03, CT-04 |
| **T** | não se aplica | — |

## Mapa de Regras

| Regra | Origem | Técnica | Cenários | Arquivo |
|---|---|---|---|---|
| R1 — todo resource dos painéis globais tem policy **registrada** | RQ-04, N-29 | varredura + âncora | CT-01, CT-02 | `tests/Kit/PermissoesDeResourcesTest.php` |
| R2 — o índice abre com `ViewAny` e fecha sem, para o papel do painel | RQ-04, N-29 | matriz papel × resource, um caso por célula | CT-03, CT-04 | idem |
| R3 — resource do `/app` devolve vazio sem organização, e só o dela com | N-04 | varredura de `getResources()` + coluna válida | CT-05, CT-06 | `tests/Tenancy/EscopoFailClosedTest.php` |
| R4 — `permite()` nega chave que não resolve, permite chave mapeada, delega sem usuário | ADR-02 | partição por estado do predicado | CT-07, CT-08, CT-09 | `tests/Kit/PermissaoDaTelaTest.php` |
| R5 — nenhuma construção que o Blueprint marca como errada no v5 em `app/`; nenhum helper depreciado em `tests/` | N-11..N-15, N-34 | varredura de fonte, comentários ignorados | CT-10, CT-11 | `tests/Kit/AderenciaAoBlueprintTest.php` |
| R6 — `TenantResource` fecha sem `ViewAny:Tenant` com a tenancy ligada | N-29 | par tem/não-tem | CT-12, CT-13 | `tests/Tenancy/PermissoesDeTenantResourceTest.php` |
| R7 — validação, ações e filtros sem teste ganham o seu | N-30, N-31, N-32 | dataset / `callAction` / `filterTable` | ver seção própria | vários (sub-agente) |

## Fronteira com o Plano

| Item do PRD | Recusado como oráculo porque | Destino |
|---|---|---|
| `App\Support\PoliciesDeVendor` (nome, forma de mapa) | escolha de implementação | detalhe. CT-01 afirma `Gate::getPolicyFor() !== null`, não que o mapa existe |
| subclasse do resource do Composer | escolha de implementação | detalhe. CT-04 afirma o 403, não a classe |
| `whereRaw('1 = 0')` | escolha de implementação | detalhe. CT-05 afirma `count() === 0` |

---

## R1 — toda policy registrada

```gherkin
# language: pt
Funcionalidade: Autorização dos resources dos painéis globais

  Regra: o modelo de todo resource tem uma policy que o Gate consegue resolver

    Cenário: [CT-01] a lista escrita bate com o que os painéis registram
      Dado os painéis /admin e /infra bootados
      Quando se compara resourcesDosPaineisGlobais() + resourcesForaDoPar() com getResources()
      Então os conjuntos são iguais, por painel

    Esquema do Cenário: [CT-02] cada modelo tem policy resolvida
      Dado o painel <painel> bootado
      Quando se pergunta Gate::getPolicyFor(<resource>::getModel())
      Então a resposta não é null

      Exemplos: os 16 resources dos dois painéis
```

**CT-01 é âncora de população, e a razão é o que a rodada ensinou**: o primeiro sweep percorria uma
lista descoberta em runtime e passaria em silêncio se a descoberta devolvesse vazio. Dataset roda
antes do app existir, então a lista é escrita — e é o CT-01 que impede a lista de envelhecer.

#### Mutantes previstos

| # | Implementação errada plausível | Mata |
|---|---|---|
| M1 | remover `PoliciesDeVendor::registrar()` do provider | CT-02 (8 casos) — **medido: 19 vermelhos** com CT-04 |
| M2 | registrar 7 das 8, esquecendo uma | CT-02 daquela |
| M3 | resource novo registrado no painel e ausente da lista | CT-01 |

## R2 — o índice fecha sem a permissão

```gherkin
    Esquema do Cenário: [CT-03] o papel do painel com a permissão abre o índice
      Dado o papel <papel> com a matriz do seeder
      Quando ele faz GET no índice de <resource>
      Então a resposta é 200

    Esquema do Cenário: [CT-04] o mesmo papel sem ViewAny toma 403
      Dado o papel <papel> com ViewAny:<Model> revogada do papel REAL
      Quando ele faz GET no índice de <resource>
      Então a resposta é 403

      Exemplos: os 14 resources do par (Tenant e CommandRecord fora, com motivo escrito)
```

**Um caso por célula, e não um laço**: a primeira versão em laço único produziu 302 e 500 que não
existem em request real — vazamento de sessão e de painel entre iterações. Dataset dá app e banco
frescos, e a falha nomeia o resource.

**Fora do par, com motivo**: `TenantResource` (config-gate `kit.tenancy.enabled`, par em R6) e
`CommandRecordResource` (gate do vendor sem `define`, só `master_global` passa — o par não
discriminaria). Os dois continuam em CT-01 e CT-02.

#### Mutantes previstos

| # | Implementação errada plausível | Mata |
|---|---|---|
| M4 | `AiRunResource::canAccess()` só com o gate, sem `parent::canAccess()` | CT-04 AiRun — **medido** |
| M5 | subclasse do Composer com `$shouldSkipAuthorization = true` (ou sem a página própria) | CT-04 ComposerRelease — **medido** |
| M6 | policy de onboarding do vendor (`return true`) continuar registrada | CT-04 OnboardingFlow/Condition |
| M7 | negar tudo (`Gate::before` devolvendo `false`) | CT-03 |

## R3 — escopo fail-closed no `/app`

```gherkin
  Regra: sem organização corrente, nenhum resource do /app devolve registro

    Cenário: [CT-05] todo resource do /app devolve vazio sem organização
      Dado projetos em duas organizações e nenhuma organização corrente
      Quando se conta getEloquentQuery() de cada resource do painel
      Então todas as contagens são zero

    Cenário: [CT-06] ProjetoResource devolve só os projetos da organização corrente
      Dado 2 projetos na Acme e 3 na Globex, e a Acme corrente
      Quando se lista ProjetoResource::getEloquentQuery()
      Então vêm exatamente os 2 da Acme
```

#### Mutantes previstos

| # | Implementação errada plausível | Mata |
|---|---|---|
| M8 | `ProjetoResource` sem `getEloquentQuery()` (o estado anterior) | CT-05 — **medido** |
| M9 | fechar sempre (`1 = 0` incondicional) | CT-06 |
| M10 | resource novo no `/app` confiando só na trait | CT-05, com o nome |

## R4 — `permite()` fail-closed

```gherkin
  Regra: chave que não resolve nega; chave mapeada consulta; sem usuário delega

    Cenário: [CT-07] página fora do mapa nega
      Dado o papel infra autenticado
      Quando permite() é chamado com um FQCN que o Shield nunca mapeou
      Então a resposta é false

    Cenário: [CT-08] página mapeada com a permissão permite
      Dado o papel infra autenticado e Pulse no mapa
      Quando permite(Pulse::class)
      Então a resposta é true

    Cenário: [CT-09] sem usuário, delega
      Dado nenhum usuário autenticado
      Quando permite(Pulse::class)
      Então a resposta é true (o middleware do painel responde por anônimo)
```

#### Mutantes previstos

| # | Implementação errada plausível | Mata |
|---|---|---|
| M11 | `: true` quando a chave não resolve (o estado anterior) | CT-07 — **medido** |
| M12 | fechar também sem usuário (`302` do painel viraria `403`) | CT-09 |
| M13 | fechar sempre | CT-08 |

## R5 — varredura textual

```gherkin
    Cenário: [CT-10] app/ sem construções erradas do v5
      Quando se varre app/ ignorando comentários
      Então nenhum arquivo casa ignoreRecord: true, ->reactive(, Filament\Forms\Get|Set, ou componente inexistente

    Cenário: [CT-11] tests/ sem helper depreciado
      Quando se varre tests/ ignorando comentários
      Então nenhum arquivo casa assertFormSet, callTableAction, assertTableActionExists, assertTableBulkActionExists
```

**Comentários ignorados de propósito**: a primeira regex acusava a própria explicação do erro.
Explicar não é cometer. Âncora: a varredura reprova se ler zero arquivos.

#### Mutantes previstos

| # | Implementação errada plausível | Mata |
|---|---|---|
| M14 | reintroduzir `ignoreRecord: true` num form | CT-10 |
| M15 | `assertFormSet` num teste novo | CT-11 |

## R6 — `TenantResource` com a tenancy ligada

CT-12 (abre com permissão) e CT-13 (403 sem `ViewAny:Tenant`), em `tests/Tenancy`, porque
`TenantResource::canAccess()` exige `kit.tenancy.enabled`.

## R7 — validação, ações e filtros (N-30, N-31, N-32)

Delegados a sub-agente com lista fechada. Ver a seção "Testes do passo 5" do `03-progresso.md` para
os arquivos e o resultado.

## Checklist de Taxonomia

| Item | Cenário |
|---|---|
| Autorização exercida na ação (não só `can()`) | CT-03/CT-04 fazem `GET` real; CT-02 é o registro |
| IDOR / horizontal | CT-05, CT-06 (fronteira de organização) |
| Persona discriminante | papel do painel, nunca `master_global` (Gate::before) |
| Verbo irmão | N/A nesta wiki — `ViewAny` é o verbo; os demais estão na v0.20.0 |
| Sweep vazio | CT-01 (igualdade de conjuntos), âncora em CT-10/11 |
| Estado × operação | não se aplica |
| Timezone, unicode, upload, dinheiro | não se aplica |

## Índice de Cenários

| ID | Cenário | Regra | Camada | Arquivo |
|----|---------|-------|--------|---------|
| CT-01 | lista bate com `getResources()` | R1 | Feature | `tests/Kit/PermissoesDeResourcesTest.php` |
| CT-02 | policy registrada por modelo (16) | R1 | Feature | idem |
| CT-03 | índice abre com permissão (14) | R2 | HTTP | idem |
| CT-04 | índice fecha sem permissão (14) | R2 | HTTP | idem |
| CT-05 | `/app` vazio sem organização | R3 | Feature | `tests/Tenancy/EscopoFailClosedTest.php` |
| CT-06 | só os da organização corrente | R3 | Feature | idem |
| CT-07 | fora do mapa nega | R4 | Unit | `tests/Kit/PermissaoDaTelaTest.php` |
| CT-08 | mapeada permite | R4 | Unit | idem |
| CT-09 | sem usuário delega | R4 | Unit | idem |
| CT-10 | `app/` sem construção errada | R5 | Unit | `tests/Kit/AderenciaAoBlueprintTest.php` |
| CT-11 | `tests/` sem helper depreciado | R5 | Unit | idem |
| CT-12 | Tenant abre com permissão | R6 | HTTP | `tests/Tenancy/PermissoesDeTenantResourceTest.php` |
| CT-13 | Tenant fecha sem | R6 | HTTP | idem |

## Sem CT-B

Nenhum cenário afirma sobre JavaScript, console, acessibilidade, cor ou layout. A confirmação em
navegador real desta rodada foi feita com o Playwright MCP como **observação** (RQ-07) — sessão
real, `View:Pulse` revogada, 403 — e está registrada no comparativo, não como cobertura.
