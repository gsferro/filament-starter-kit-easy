# Casos de Teste — unificar-logo-da-organizacao

> Requisito: `00-requisito.md` · Plano: `01-plano-acao.md`
> Arquivo de destino: `tests/Kit/UnificaLogoDoTenantTest.php` — mesma suíte do `LogoDarkModeTest`, que já prova que a suíte Kit cria tenants e abre o `EditTenant` com `kit.tenancy.enabled` ligado no arranjo (`LogoDarkModeTest.php:'[CT-09]'`).

## Regras derivadas do requisito

- **R1** (RQ-02, P-02): o toggle `unifica_logo` só aparece no form quando a marca da **instalação** está separada (`kit.identidade.unifica_logo_marca = false`).
- **R2** (RQ-02, P-02): `logo_dark` do tenant só aparece quando a marca global é separada **e** `unifica_logo` do registro/estado é falso.
- **R3** (RQ-03): `unifica_logo = true` **e clara própria resolvível** → `logosPara()` devolve `escura = null` — a `<img>` dark não é emitida e a clara da organização serve os dois temas. Sem clara própria não há o que unificar: a escura da instalação segue aparecendo (o flag decide sobre as logos da organização, não sobre as da instalação).
- **R4** (RQ-03, Q4): `unifica_logo = false` + `logo_dark` vazia → `escura` cai na `logo_dark` da instalação (comportamento atual, preservado).
- **R5** (RQ-02): `unifica_logo = false` + `logo_dark` presente → `escura` é a da organização.
- **R6** (P-04): default `true` em create; organização nova nasce unificada.
- **R7** (P-05): organização `null` nunca consulta o flag — par da instalação intacto (coberto pelo CT-41 do `LogoDarkModeTest`, não repete).

## Matriz de decisão

`escura` em `IdentidadeDoKit::logosPara($org)`:

| global `unifica_logo_marca` | `org.unifica_logo` | clara própria resolvível | `org.logo_dark` | `escura` |
|---|---|---|---|---|
| true | qualquer | qualquer | qualquer | `null` |
| false | true | sim | qualquer | `null` |
| false | true | não | qualquer, inclusive antiga válida | instalação dark; clara também é a da instalação |
| false | false | qualquer | presente | org dark |
| false | false | qualquer | vazia | instalação dark (Q4) |

## Mapa de Regras

| Regra | Descrição | Origem |
|---|---|---|
| R1 | Toggle visível apenas com marca global separada | RQ-02, P-02 |
| R2 | Upload escuro aparece apenas com os dois controles separados | RQ-02, P-02 |
| R3 | Clara própria unificada serve ambos os temas e ignora escura antiga | RQ-03, P-03 |
| R4 | Separada sem escura própria usa a da instalação | RQ-03 |
| R5 | Separada com escura própria usa a dela | RQ-02 |
| R6 | Nova organização unificada; backfill preserva separação existente | RQ-02, P-01, P-04 |
| R8 | Documentação acompanha o comportamento publicado | RQ-04 |

## Índice de Cenários

| ID | Cenário | Regra | Arquivo |
|---|---|---|---|
| CT-01 | Default, atribuição e cast | R6 | tests/Kit/UnificaLogoDoTenantTest.php |
| CT-02 | Visibilidade dos campos | R1, R2 | tests/Kit/UnificaLogoDoTenantTest.php |
| CT-03 | Gravação do toggle | R1, R2 | tests/Kit/UnificaLogoDoTenantTest.php |
| CT-04 | Ignorar escura antiga no modo unificado | R3 | tests/Kit/UnificaLogoDoTenantTest.php |
| CT-05 | Fallback separado sem escura | R4 | tests/Kit/UnificaLogoDoTenantTest.php |
| CT-06 | Escura própria no modo separado | R5 | tests/Kit/UnificaLogoDoTenantTest.php |
| CT-07 | HTML da tela de bloqueio sem classes que escondem logo único | R3 | tests/Kit/UnificaLogoDoTenantTest.php |
| CT-08 | Factory respeita escolha explícita | R6 | tests/Kit/UnificaLogoDoTenantTest.php |
| CT-09 | Docs nos dois idiomas | R8 | tests/Kit/UnificaLogoDoTenantTest.php |
| CT-10 | Atualização de registros anteriores à coluna | R6 | tests/Kit/UnificaLogoDoTenantTest.php |
| CT-11 | Unificada sem clara, com escura antiga válida | R3 | tests/Kit/UnificaLogoDoTenantTest.php |
| CT-12 | Unificada com clara órfã e escura antiga válida | R3 | tests/Kit/UnificaLogoDoTenantTest.php |

## Cenários

### CT-01 — `unifica_logo` é atribuível em massa e nasce `true`

**Origem**: RQ-02, P-04 · **Regra**: R6 · **Técnica**: transição de estado (coluna nova)

```gherkin
Dado um create de organização sem declarar unifica_logo
Quando o registro é lido do banco
Então unifica_logo é true
E um create com unifica_logo=false persiste false
```

Mutantes que mata: atributo fora do `$fillable` (descartado em silêncio), sem cast `boolean` (volta `0`/`1` int), default errado na coluna.

### CT-02 — o toggle governa o campo `logo_dark` no form

**Origem**: RQ-02, P-02 · **Regra**: R1 + R2 · **Técnica**: tabela de decisão × visibilidade

```gherkin
Dado a marca da instalação separada e uma organização unificada
Quando o EditTenant abre
Então o toggle unifica_logo está visível e logo_dark está oculto
E ao desligar o toggle logo_dark aparece
Dado a marca da instalação unificada
Então nem o toggle nem logo_dark aparecem
```

Mutantes que mata: `logo_dark` visível ignorando o flag; toggle visível com marca global unificada.

### CT-03 — a gravação por componente persiste o toggle

**Origem**: RQ-02 · **Regra**: gate de tela de escrita · **Técnica**: gravação por Livewire

```gherkin
Dado a marca separada e uma organização unificada aberta no EditTenant
Quando o toggle é desligado e o form salvo
Então tenants.unifica_logo vale 0 no banco
```

### CT-04 — unificada ignora a `logo_dark` da organização

**Origem**: RQ-02, RQ-03 · **Regra**: R3 · **Técnica**: decisão em `logosPara()`

```gherkin
Dado marca global separada, organização com logo + logo_dark no disco e unifica_logo=true
Quando logosPara(organizacao) resolve
Então clara é a da organização e escura é null
```

Mutante que mata: `||` virando `&&` — a `logo_dark` gravada vazaria no modo unificado.

### CT-05 — separada sem `logo_dark` cai na da instalação (Q4, compat)

**Origem**: Q4 · **Regra**: R4

```gherkin
Dado marca global separada, organização com só a clara e unifica_logo=false
Então escura é a logo_dark da instalação
```

### CT-06 — separada com `logo_dark` usa a da organização

**Origem**: RQ-02 · **Regra**: R5 — cobre o caminho que já existia, com o flag desligado explicitamente.

### CT-11 — unificada SEM clara própria não suprime a escura da instalação (antigo CT-06b)

**Origem**: revisão adversarial — o flag decide sobre as logos DELA; organização sem clara resolvível cai no par da instalação inteiro · **Regra**: R3, fronteira · **Técnica**: valor limite (org sem logo nenhuma)

```gherkin
Dado marca global separada, instalação com o par de logos e organização unificada sem clara, com escura antiga válida
Então ambas as variantes são as da instalação
```

### CT-12 — unificada com a clara órfã cai na escura da instalação (antigo CT-06c)

**Origem**: mesma cláusula, pelo caminho órfão (coluna declarada, arquivo fora do disco) · **Regra**: R3, fronteira

### CT-07 — a lock screen não emite `<img>` dark para organização unificada

**Origem**: RQ-03, P-03 · **Regra**: R3 na segunda superfície

```gherkin
Dado marca global separada e organização unificada com só a clara, como tenant_corrente da sessão
Quando a lock screen renderiza
Então a clara da organização aparece e fi-logo-dark não
E a clara não recebe fi-logo-light, que a esconderia no tema escuro
```

### CT-08 — factory: `comIdentidadeVisual(..., unifica: false)` grava

**Origem**: costura de teste · **Técnica**: exercita o parâmetro novo

### CT-09 — a documentação menciona o toggle nos dois idiomas

**Origem**: RQ-04 (a entrega inclui docs) · **skip**: `naArvoreDoKit()` — docs são `export-ignore`.

## Costuras de Teste

| Costura | Onde | Motivo |
|---|---|---|
| Livewire/Filament | CT-02, CT-03 | visibilidade de campo e gravação |
| Unidade (`logosPara`, model) | CT-01, CT-04, CT-05, CT-06, CT-08 | regra de decisão |
| HTTP render | CT-07 | lock screen — superfície real |
| Documentação | CT-09 | presença de docs |

A visibilidade do upload é estado de schema, assertível no componente. A visibilidade real do logo depende do CSS e do tema no navegador: CT-B02 definido no `05`.

### CT-10 — atualização preserva escolhas anteriores

```gherkin
Cenário: [CT-10] organizações existentes recebem o modo compatível
Dado registros anteriores à coluna unifica_logo, com e sem logo_dark
Quando a migration adiciona a coluna e executa o backfill
Então quem tinha logo_dark continua separado, com o arquivo preservado
E quem não tinha logo_dark fica unificado, com a clara preservada
```
