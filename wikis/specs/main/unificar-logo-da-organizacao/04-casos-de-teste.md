# Casos de Teste — unificar-logo-da-organizacao

> Requisito: `00-requisito.md` · Plano: `01-plano-acao.md`
> Arquivo de destino: `tests/Kit/UnificaLogoDoTenantTest.php` — mesma suíte do `LogoDarkModeTest`, que já prova que a suíte Kit cria tenants e abre o `EditTenant` com `kit.tenancy.enabled` ligado no arranjo (`LogoDarkModeTest.php:'[CT-09]'`).

## Regras derivadas do requisito

- **R-A** (RQ-02, P-02): o toggle `unifica_logo` só aparece no form quando a marca da **instalação** está separada (`kit.identidade.unifica_logo_marca = false`).
- **R-B** (RQ-02, P-02): `logo_dark` do tenant só aparece quando a marca global é separada **e** `unifica_logo` do registro/estado é falso.
- **R-C** (RQ-03): `unifica_logo = true` **e clara própria resolvível** → `logosPara()` devolve `escura = null` — a `<img>` dark não é emitida e a clara da organização serve os dois temas. Sem clara própria não há o que unificar: a escura da instalação segue aparecendo (o flag decide sobre as logos da organização, não sobre as da instalação).
- **R-D** (RQ-03, Q4): `unifica_logo = false` + `logo_dark` vazia → `escura` cai na `logo_dark` da instalação (comportamento atual, preservado).
- **R-E** (RQ-02): `unifica_logo = false` + `logo_dark` presente → `escura` é a da organização.
- **R-F** (P-04): default `true` em create; organização nova nasce unificada.
- **R-G** (P-05): organização `null` nunca consulta o flag — par da instalação intacto (coberto pelo CT-41 do `LogoDarkModeTest`, não repete).

## Matriz de decisão

`escura` em `IdentidadeDoKit::logosPara($org)`:

| global `unifica_logo_marca` | `org.unifica_logo` | `org.logo_dark` | `escura` |
|---|---|---|---|
| true | qualquer | qualquer | `null` |
| false | true | qualquer | `null` |
| false | false | presente | org dark |
| false | false | vazia | instalação dark (Q4) |

## Cenários

### CT-01 — `unifica_logo` é atribuível em massa e nasce `true`

**Origem**: RQ-02, P-04 · **Regra**: R-F · **Técnica**: transição de estado (coluna nova)

```gherkin
Dado um create de organização sem declarar unifica_logo
Quando o registro é lido do banco
Então unifica_logo é true
E um create com unifica_logo=false persiste false
```

Mutantes que mata: atributo fora do `$fillable` (descartado em silêncio), sem cast `boolean` (volta `0`/`1` int), default errado na coluna.

### CT-02 — o toggle governa o campo `logo_dark` no form

**Origem**: RQ-02, P-02 · **Regra**: R-A + R-B · **Técnica**: tabela de decisão × visibilidade

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

**Origem**: RQ-02, RQ-03 · **Regra**: R-C · **Técnica**: decisão em `logosPara()`

```gherkin
Dado marca global separada, organização com logo + logo_dark no disco e unifica_logo=true
Quando logosPara(organizacao) resolve
Então clara é a da organização e escura é null
```

Mutante que mata: `||` virando `&&` — a `logo_dark` gravada vazaria no modo unificado.

### CT-05 — separada sem `logo_dark` cai na da instalação (Q4, compat)

**Origem**: Q4 · **Regra**: R-D

```gherkin
Dado marca global separada, organização com só a clara e unifica_logo=false
Então escura é a logo_dark da instalação
```

### CT-06 — separada com `logo_dark` usa a da organização

**Origem**: RQ-02 · **Regra**: R-E — cobre o caminho que já existia, com o flag desligado explicitamente.

### CT-06b — unificada SEM clara própria não suprime a escura da instalação

**Origem**: revisão adversarial — o flag decide sobre as logos DELA; organização sem clara resolvível cai no par da instalação inteiro · **Regra**: R-C, fronteira · **Técnica**: valor limite (org sem logo nenhuma)

```gherkin
Dado marca global separada, instalação com logo_dark e organização unificada sem logo
Então escura é a logo_dark da instalação
```

### CT-06c — unificada com a clara órfã cai na escura da instalação

**Origem**: mesma cláusula, pelo caminho órfão (coluna declarada, arquivo fora do disco) · **Regra**: R-C, fronteira

### CT-07 — a lock screen não emite `<img>` dark para organização unificada

**Origem**: RQ-03, P-03 · **Regra**: R-C na segunda superfície

```gherkin
Dado marca global separada e organização unificada com só a clara, como tenant_corrente da sessão
Quando a lock screen renderiza
Então a clara da organização aparece e fi-logo-dark não
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

Sem costura `browser` — nenhum cenário afirma sobre JS, cor ou console; a visibilidade do campo é estado de schema, assertível no componente. `05` não existe.
