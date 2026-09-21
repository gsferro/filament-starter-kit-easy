# Decisões Arquiteturais — Link de acesso ao painel da organização

## ADR-01: A URL sai do gerador do Filament, não de concatenação

**Status**: Aceita · **Data**: 2026-09-21 · **Atende**: RQ-01, RQ-05

### Contexto

O requisito escreve a URL como `app/{slug}`. Montá-la por concatenação
(`url('/app/'.$tenant->slug)`) funcionaria hoje e quebraria em silêncio em três situações que o kit
já permite: o `path` do painel mudar, o kit rodar sob um `APP_URL` com subdiretório, e a chave de
rota do tenant deixar de ser o slug.

**Confirmado**: `AppPanelProvider` declara `->tenant(Tenant::class, slugAttribute: 'slug')` — a
chave de rota **é** o slug hoje, mas isso é configuração, não invariante.

### Decisão

Usar o gerador de URL do painel, com o tenant como argumento. A string `/app/` **não aparece** no
código da feature.

### Alternativas Consideradas

1. **`url('/app/'.$tenant->slug)`** — descartada: três acoplamentos silenciosos, e o kit já tem o
   precedente oposto na própria `TenantsTable`, cujo comentário explica que **não** passar `->url()`
   deixa o Filament resolver sozinho.
2. **`route('filament.app.pages.dashboard', …)`** — descartada: o nome da rota muda quando o painel
   ganha ou perde páginas, e a tenancy reescreve os nomes.

### Consequências

- **Positivas**: a feature sobrevive a mudança de `path`, de subdiretório e de `slugAttribute`
- **Negativas**: exige o painel `app` resolvido em tempo de render
- **Riscos**: com a tenancy **desligada** o painel não tem rota com tenant. Ver ADR-03

---

## ADR-02: O link aparece sempre, e o 403 é comportamento declarado e testado

**Status**: Aceita · **Data**: 2026-09-21 · **Atende**: RQ-01

### Contexto

Quem abre `/admin/organizacoes` **não necessariamente entra** em `/app/{slug}`. São dois portões
independentes:

1. `User::canAccessPanel($painelApp)` — exige papel do painel `app`
2. `User::canAccessTenant($tenant)` — libera só para `isMasterGlobal()` **ou** quem tem linha no
   pivot; senão **nega e loga** `motivo: sem_vinculo`

O docblock de `RelationManagers/UsersRelationManager.php` já registrava isso: *"É esta tela que
decide quem consegue abrir `/app/{slug}`: sem linha no pivot, …"*.

### Decisão

**O link aparece sempre, clicável, nas três superfícies.** Quem não passa nos portões recebe erro —
e **os dois portões devolvem códigos diferentes**, o que esta ADR afirmava errado:

| Portão | O que barra | Código |
|---|---|---|
| `canAccessPanel('app')` | sem papel do painel `app` | **403** |
| `canAccessTenant($tenant)` | sem `isMasterGlobal()` e sem linha no pivot | **404** — `vendor/filament/filament/src/Http/Middleware/IdentifyTenant.php:abort(404):41` |

*(corrigido em 2026-09-21: esta ADR dizia "403 do Filament" para os dois. O 404 do portão 2 é
deliberado no vendor — devolver 403 revelaria que a organização **existe** a quem não pode vê-la.
Achado da derivação dos casos de teste, conferido no vendor.)*

**Decidido pelo usuário em 2026-09-21.** O agente recomendou a alternativa 1 abaixo; a escolha do
usuário prevalece.

### Alternativas Consideradas

1. **Renderizar só quando a pessoa consegue entrar** (os dois portões) — **recomendada pelo agente
   e recusada pelo usuário**. Seria o padrão "falha fechado" que o kit já usa em `canAccess()` de
   Page, e evitaria o clique para o 403. Recusada: esconder o link também esconde a informação de
   que a organização **tem** um painel.
2. **Visível porém desabilitado, com tooltip** — descartada pelo usuário: ocuparia espaço nas três
   telas e exigiria texto de explicação em cada uma.

### Consequências

- **Positivas**: uma só regra nas três superfícies; o endereço da organização fica sempre visível
- **Negativas**: **um administrador sem papel do painel `app` leva 403, e um com papel mas sem
  vínculo leva 404**, em qualquer das três telas. É consequência aceita, não descuido
- **Riscos**: 403 e 404 são telas de erro, não caminho de volta. E o **404 é pior para quem clica**:
  ele sugere que a organização não existe, quando na verdade existe e a pessoa não tem vínculo.
  **Mitigação declarada**: a consequência vira **caso de teste** — o `04` cobre os dois portões com
  o código de cada um, para que o comportamento seja **conhecido e travado**. Se incomodar na
  prática, a alternativa 1 é uma condição em cada superfície

---

## ADR-03: Com a tenancy desligada, a feature não existe

**Status**: Aceita · **Data**: 2026-09-21 · **Atende**: RQ-01

### Contexto

A multi-tenancy é **opt-in** (`config('kit.tenancy.enabled')`). Com ela desligada, o painel `app`
não tem rota com tenant, e o próprio `TenantResource` já se esconde —
`shouldRegisterNavigation()` e `canAccess()` devolvem `config('kit.tenancy.enabled')`.

### Decisão

Nenhuma guarda nova. A feature vive **dentro** do `TenantResource`, que já não existe com a tenancy
desligada. Adicionar uma segunda verificação seria uma segunda dona para a mesma pergunta — o que
`.ai/rules/config.md` proíbe explicitamente.

### Alternativas Consideradas

1. **Checar `config('kit.tenancy.enabled')` nas três superfícies** — descartada: redundante e cria
   a chance de as duas respostas divergirem

### Consequências

- **Positivas**: zero código para um caso que já é impossível de alcançar
- **Negativas**: a feature depende de o `TenantResource` continuar fechado. É um invariante que o
  `04` afirma
- **Riscos**: se alguém abrir o `TenantResource` sem tenancy, o gerador de URL da ADR-01 falha.
  Coberto por CT

---

## ADR-04: Nova aba, e por quê

**Status**: Aceita · **Data**: 2026-09-21 · **Atende**: RQ-01

### Contexto

O link sai de `/admin` (administração da instalação) para `/app/{slug}` (painel de negócio de uma
organização). São dois contextos com **sessões de painel distintas** e seletor de tenant próprio.

### Decisão

Abrir em **nova aba**.

### Alternativas Consideradas

1. **Mesma aba** — descartada: quem estava revisando uma lista de organizações perde a lista, e o
   caminho de volta é o botão do navegador. O atalho é "ir ver como está lá", não "mudar de contexto
   de trabalho"

### Consequências

- **Positivas**: a tela de administração permanece; comparar duas organizações fica trivial
- **Negativas**: acumula abas em uso intenso
- **Riscos**: nenhum medido

---

## Superfície Livewire

A feature **não cria** Page, Widget nem componente Livewire: ela acrescenta entradas declarativas a
três schemas já existentes (`TenantForm`, `TenantInfolist`, `TenantsTable`). Ainda assim, a skill
exige o inventário — e o exercício encontrou o ponto que importa.

| Ponto de entrada | Alcançável por | Fronteira aplicada | Evidência |
|---|---|---|---|
| A URL renderizada no `href` | leitura da página | **nenhuma** — é saída, não entrada | o `slug` vem do registro já carregado pelo resource |
| O `slug` que compõe a URL | escrita no formulário (`TextInput::make('slug')`) | `->alphaDash()` + `->unique()` + `->maxLength(120)` | `TenantForm.php:'slug'` |
| O registro alcançado | rota do resource (`/admin/organizacoes/{record}`) | policy do `TenantResource` | — |

**O achado do inventário**: o `slug` é **escrito pelo usuário** e depois **entra numa URL**. Sem
`->alphaDash()` ele aceitaria `../` e `?`, e a URL gerada apontaria para outro lugar. A validação já
existe e **não é da feature** — mas a feature passa a **depender** dela, e é isso que o inventário
torna explícito. Um CT afirma essa dependência, para que remover o `alphaDash()` no futuro fique
vermelho aqui e não só na tela de cadastro.

**Nenhuma propriedade pública nova, nenhum método público novo, nenhum valor de `$tableFilters`
consumido.**
