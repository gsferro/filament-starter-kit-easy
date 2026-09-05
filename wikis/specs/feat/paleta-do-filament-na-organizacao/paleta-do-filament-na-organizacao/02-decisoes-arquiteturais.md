# Decisões Arquiteturais — A paleta do Filament na identidade visual da organização

## ADR-01: Um resolvedor, duas fontes — `CorPrimaria::resolver()` serve ao kit e à organização

**Status**: Aceita
**Data**: 2026-09-02
**Refina**: ADR-02 de `wikis/specs/feature/identidade-visual-da-organizacao/` e a precedência de
`wikis/specs/feat/settings-do-kit/`

### Contexto

O kit já decide "hex vence nome; hex inválido cai para o nome; nome inexistente cai para o padrão" em
`CorPrimaria::paleta()`, com sete casos de teste. A organização vai precisar exatamente da mesma
decisão sobre duas colunas em vez de duas chaves de config. Escrever a decisão de novo no
`bootUsing()` — ou num accessor do `Tenant` — criaria a segunda cópia de uma regra que já custou uma
ADR para ficar certa (a inversão da precedência tornava a cor livre inalcançável).

### Decisão

Extrair o corpo de `paleta()` para `resolver(?string $hex, ?string $nome): array` e fazer `paleta()`
chamá-lo com a config. O `bootUsing()` do `/app` chama `resolver($tenant->cor_primaria, $tenant->cor_primaria_nome)`.
A ordem de registro (organização depois do kit, no `bootCallbacks`) não muda — é o que faz a
organização vencer o kit dentro de `/app/{slug}`.

### Alternativas Consideradas

1. **Accessor `Tenant::paletaPrimaria()`** com a lógica dentro — segunda cópia da regra, no model,
   e o model passa a conhecer `Filament\Support\Colors\Color`.
2. **`Tenant::paletaPrimaria()` que delega a `CorPrimaria::resolver()`** — um método a mais para
   uma linha; quem lê o `bootUsing()` já vê as duas colunas. Se um segundo consumidor aparecer
   (tela `view`, listagem), aí vale.
3. **Gravar a paleta resolvida no banco** — dado calculável e desatualizável; recusado pela
   migration ancestral pelo mesmo motivo.

### Consequências

- **Positivas**: `CorPrimariaTest` passa a cobrir a organização por construção; o comportamento com
  hex inválido gravado direto no banco melhora sozinho (hoje cai em paleta acromática).
- **Negativas**: `CorPrimaria` deixa de ser "a cor do projeto" e vira "a regra de cor" — o docblock
  precisa dizer isso, senão o nome engana.

### Referências

- `app/Support/CorPrimaria.php`, `tests/Kit/CorPrimariaTest.php:73-95`
- `app/Providers/Filament/AppPanelProvider.php:152-175`

---

## ADR-02: Uma coluna nova, `cor_primaria_nome` — não reaproveitar `cor_primaria`, não JSON

**Status**: Aceita
**Data**: 2026-09-02
**Refina**: ADR-01 de `identidade-visual-da-organizacao` ("ao terceiro campo, reavaliar JSON")

### Contexto

`tenants.cor_primaria` é `string(7)`, criada para `#RRGGBB`, com regex âncorado no formulário e uma
justificativa escrita para os 7 caracteres. Os 16 nomes da paleta cabem nela **por coincidência**
(`Emerald` e `Fuchsia` têm 7). A migration ancestral deixou o gatilho: "ao terceiro campo,
reavaliar JSON".

### Decisão

Coluna nova `cor_primaria_nome` (`string(32)`, nula), ao lado de `cor_primaria`. Duas colunas, dois
tipos, dois campos — o espelho exato de `kit.cor_primaria` e `kit.cor_primaria_hex`.

### Alternativas Consideradas

1. **Guardar o nome em `cor_primaria`** — um `varchar(7)` que ora é hex ora é `Emerald`; o regex do
   `ColorPicker` reprovaria; toda leitura precisaria adivinhar o tipo pelo primeiro caractere; e o
   próximo nome com 8 letras trunca em silêncio no sqlite.
2. **JSON `identidade_visual`** — o gatilho da ancestral foi avaliado e **recusado**: o terceiro
   campo é da mesma natureza dos dois primeiros e continua apontado direto por um componente de
   formulário. JSON traria `statePath` aninhado para três campos e nenhum ganho. O gatilho passa
   para "ao quarto campo, ou ao primeiro que não seja um componente de formulário".
3. **`Select` com opção "Personalizada" que revela o `ColorPicker`, um valor só** — recusado pelo
   solicitante: difere do kit e mistura tipos na coluna.

### Consequências

- **Positivas**: nada do que lê `cor_primaria` hoje muda de significado; a coluna nova nasce nula e a
  feature é inerte até alguém escolher.
- **Negativas**: mais uma migration a chegar por `kit:update` (o aviso pós-update já manda migrar).

### Referências

- `database/migrations/2026_08_14_000003_add_identidade_visual_to_tenants_table.php` (docblock)
- `app/Filament/Admin/Pages/ConfiguracoesDoKit.php:246-262`

---

## ADR-03: A lista é `CustomizadorDaInstalacao::CORES`, sem cópia e sem filtro

**Status**: Aceita
**Data**: 2026-09-02

### Contexto

O requisito diz "a mesma opção de escolha". A lista do kit vive em `CustomizadorDaInstalacao::CORES`
(16 nomes, incluindo o neutro `Slate`), e `CorPrimariaTest` já prova que todos existem em `Color`.

### Decisão

O `Select` da organização usa `array_combine(CORES, CORES)` direto. Nenhuma constante nova, nenhum
filtro (tirar `Slate` seria "quase a mesma" lista — e a resposta do solicitante foi "a mesma").

### Alternativas Consideradas

1. **Constante própria da organização** — segunda lista a manter; divergem no primeiro upgrade.
2. **Reflection sobre `Color`** — recusada pelo próprio kit no settings: a classe expõe constantes
   que não são cor (`WCAG_AA_TEXT`) e neutros que ninguém escolhe.

### Consequências

- **Positivas**: uma lista, um dono, um teste de existência.
- **Negativas**: a organização pode escolher `Slate` (cinza). É o que o kit também permite.

### Referências

- `app/Support/CustomizadorDaInstalacao.php:58-61`, `tests/Kit/CorPrimariaTest.php:45`
