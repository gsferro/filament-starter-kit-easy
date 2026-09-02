# Progresso — A paleta do Filament na identidade visual da organização

> Tipo **evolução** (`identidade-visual-da-organizacao`, `settings-do-kit`); toca `App\Support\CorPrimaria`
> (os três painéis) → regressão obrigatória: `CorPrimariaTest`, `IdentidadeVisualTest`,
> `IdentidadeVisualTenancyTest`, CT-B `BrowserTenancy/IdentidadeVisualTest`.

## 1. Migration `tenants.cor_primaria_nome`

- [x] `2026_09_02_000001_add_cor_primaria_nome_to_tenants_table.php`: `string(32)` nula,
      `after('cor_primaria')`, `down()` com `dropColumn`
- [x] Docblock: coluna separada (7 caracteres por coincidência), JSON adiado ao quarto campo, nulo é o neutro

## 2. `Tenant` e `TenantFactory`

- [x] `$fillable` + `@property ?string $cor_primaria_nome`
- [x] `comIdentidadeVisual(?string $cor = '#7c3aed', ?string $logo = null, ?string $paleta = null)` —
      `$cor` virou anulável para a linha "só a paleta" de CT-04

## 3. `CorPrimaria::resolver()`

- [x] `resolver(mixed $hex, mixed $nome)` com o corpo antigo; `paleta()` delega. `mixed` e não
      `?string`: a config pode devolver qualquer coisa, e o `is_string()` já era a guarda
- [x] Docblock da classe: a organização segue a mesma regra por `resolver()`

## 4. `Select` no `TenantForm`

- [x] `Select cor_primaria_nome` antes do `ColorPicker`, `array_combine(CORES, CORES)`, placeholder
      "Cor da aplicação (padrão)", `native(false)`, `searchable()`; imports de `Select` e
      `CustomizadorDaInstalacao`
- [x] `ColorPicker` → "Cor primária livre", helper "VENCE a paleta escolhida ao lado"
- [x] Logo em `columnSpanFull()`

## 5. `bootUsing()` resolve as duas colunas

- [x] `CorPrimaria::resolver($tenant->cor_primaria, $tenant->cor_primaria_nome)`; a guarda `blank()`
      saiu (o resolvedor devolve `[]`)
- [x] `debug` no channel `tenancy` com `cor_primaria_nome` e `fonte` (`hex` | `paleta`)

## 6. CHANGELOG e documentação

- [x] `CHANGELOG.md` → `[Unreleased]` → `### Adicionado`
- [x] `docs/pt/recursos/configuracoes-do-kit.md:135` e `docs/en/…:137` citam a paleta e a precedência

## Testes

- [x] `tests/Tenancy/PaletaDaOrganizacaoTest.php` — CT-01, CT-02, CT-03, CT-04, CT-06, CT-07: **14 casos, 67 asserções, verdes** (85 s)
- [x] `tests/Kit/CorPrimariaTest.php` — CT-05 (7 linhas, inclusive `null`/`null`): **30 casos verdes** no arquivo
- [ ] Regressão: `IdentidadeVisualTest`, `IdentidadeVisualTenancyTest` — na suíte completa (abaixo)

## Verificação Final

- [ ] `/ponytail:ponytail-review` no diff
- [ ] `vendor/bin/pint --dirty --format agent`
- [ ] `vendor/bin/pest --no-tia tests/Kit/CorPrimariaTest.php tests/Kit/IdentidadeVisualTest.php tests/Tenancy/PaletaDaOrganizacaoTest.php tests/Tenancy/IdentidadeVisualTenancyTest.php --compact`
- [ ] `vendor/bin/pest --no-tia --parallel --testsuite=Kit,Tenancy --compact`
- [ ] `composer test:browser` filtrado em `IdentidadeVisual`
- [ ] Tela `/admin/organizacoes/{id}/edit` olhada: `Select` com 16 cores, `ColorPicker` ao lado, logo em linha inteira
- [ ] `git commit`

## Auditoria Pré-Implementação

### Captura do requisito

- Texto escrito (fidelidade alta). Uma pergunta ao solicitante — forma da UI — respondida: espelhar
  o kit (dois campos, hex vence). Registrada verbatim no `00`.
- `search-docs` consultado (Filament 5): `Select` aplica `in()` por padrão em toda seleção
  ("Valid options validation"); `ColorPicker` é hex por padrão. Confirma que o `Select` não precisa
  de regra manual para recusar valor fora da lista — CT-02 mede isso.

### Medições que antecederam a wiki (2026-09-02)

| O que | Resultado |
|---|---|
| Campos de cor no kit | `Select cor_primaria` (16 nomes de `CORES`) + `ColorPicker cor_primaria_hex`; hex vence (`ConfiguracoesDoKit.php:246-262`) |
| Campos de cor na organização | só `ColorPicker cor_primaria` `#RRGGBB`, `string(7)` |
| Regra de precedência | `CorPrimaria::paleta()`, 7 casos em `CorPrimariaTest`, inclusive "hex inválido cai para o nome" |
| Como a cor da organização é aplicada | `FilamentColor::register()` no `bootUsing()`, string → `generatePalette()` (`AppPanelProvider.php:152-175`) |
| `ColorManager` aceita paleta pronta? | sim — só gera quando recebe string (`ColorManager.php:84-85`, citado no código do kit) |
| Nomes da paleta × coluna de 7 | `Emerald` e `Fuchsia` têm 7: cabem por coincidência — ADR-02 |

### Revisão profunda (step 5) — premissas do plano contra o código real

| Premissa do plano | O código real diz | Correção aplicada na wiki |
|---|---|---|
| `TenantForm` já importa `Select` | **não** — importa `ColorPicker`, `FileUpload`, `TextInput`, `Toggle`; falta `use Filament\Forms\Components\Select;` e `use App\Support\CustomizadorDaInstalacao;` | passo 4 registra os dois imports |
| A tela `view` da organização exibe a cor | não há `TenantInfolist`; `ViewTenant` existe mas nenhum arquivo do resource exibe `cor_primaria` fora do formulário | "Fora de Escopo" do `00` confirmado; nada a atualizar na view |
| `duasOrganizacoes()` serve de fixture para CT-04 | ela fixa `#7c3aed`/`#059669` **sem** paleta; CT-04 precisa das combinações | fixture própria por `Tenant::factory()->comIdentidadeVisual(hex, null, paleta)` + `usuarioComPapel('panel_user', $org)` — registrado no `04` |
| Docs: `multi-tenancy.md` fala da identidade visual | **não** — quem fala é `configuracoes-do-kit.md:135` (pt) / `:137` (en) | passo 6 aponta só esses dois |

### Auditoria Ponytail (step 6)

| # | Sugestão de corte | Aplicada? | Onde |
|---|---|---|---|
| 1 | `yagni:` `Tenant::paletaPrimaria()` — um método para uma linha com um chamador | já fora (ADR-01, alternativa 2) | `02` |
| 2 | `yagni:` `comValorConfigurado()` no `Select` da organização — não há valor legado (coluna nasce nula) | fora, condicionado ao step 5 (não houve gatilho) | `01`, Riscos |
| 3 | `yagni:` estado de factory novo (`comPaleta()`) — um parâmetro opcional basta | aplicada no plano | `01`, passo 2 |
| 4 | `delete:` "`multi-tenancy.md` só se mencionar identidade visual" — não menciona | sim | `01`, passo 6 |

`net: -1 linha`. Plano mínimo: uma coluna, um `Select`, uma extração de método, uma chamada no
`bootUsing()`.

## Blockers

- Nenhum.

## Desvios do Plano

<!-- pós-implementação -->

## Notas de Implementação

<!-- pós-implementação -->

## Retrospectiva

<!-- pós-implementação -->
