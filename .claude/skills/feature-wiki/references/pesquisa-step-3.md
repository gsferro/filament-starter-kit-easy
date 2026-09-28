> Referência da feature-wiki 4.0.0. Lida em: step 3 (ao preencher a `## Superfície Livewire` e antes de escrever o PRD) e antes do step 9 (re-varredura da Superfície sobre o código final). Fonte única de: tabela das três origens da Superfície Livewire, formato da linha e greps; cobertura, uso e lacunas do `search-docs`.

# Pesquisa do step 3 — tabelas e greps

As obrigações do step 3 (o que inventariar, as duas regras duras da Superfície Livewire, os
anti-padrões do `search-docs`) ficam no corpo do `SKILL.md`.

## Superfície Livewire — formato e greps

As três origens que o corpo manda inventariar, com o porquê de cada uma:

| Origem | O que inventariar | Por que |
|---|---|---|
| **o código do projeto** | todo `public function` de Page, Widget ou componente Livewire; toda `public $` sem `#[Locked]` | método público de componente Livewire **é ação chamável pelo cliente**, e o retorno vai para o navegador; propriedade pública é escrita pelo cliente **entre requests** |
| **o framework** | os arrays de estado que o framework publica e o seu código consome — `$filters` (`HasFilters`), `$pageFilters` (`InteractsWithPageFilters`), `$tableFilters`, `$tableSearch`, `$tableSortColumn` | são **entrada de usuário não validada** que vira `where`, índice de array e parse de data. O framework os declara `public` |
| **o pacote de terceiro** | ações que recebem id/argumento do cliente, propriedades públicas e models que a feature persiste | ver os greps do pacote, abaixo |

Uma linha por ponto, com a fronteira e a evidência. A evidência é `arquivo:símbolo:linha` (seção
*Citações de código* do `SKILL.md`): o `citacoes.sh` confere a tabela no step 10 e acusa citação só com linha.

| Ponto de entrada (vendor) | Alcançável por | Fronteira aplicada pelo projeto | Evidência |
|---|---|---|---|
| `Widget::find($arguments['widget'])` | `$wire.mountAction('deleteWidget', {widget: <id>})` | global scope `whereHas('pai')` | `vendor/{vendor}/{pkg}/src/Pages/X.php:mountAction():962` |
| `public ?int $currentDashboardId` | `$wire.set()` em qualquer request após o `mount()` | `#[Locked]` na subclasse do projeto | `vendor/{vendor}/{pkg}/src/Pages/X.php:$currentDashboardId:71` |

**No código que a feature escreve** (sempre):

```bash
grep -rn "public function " app/Filament/{Painel}/{Pages,Widgets}   # ação chamável por $wire.
grep -rn "public \$\|public ?" app/Filament app/Livewire | grep -v Locked
```

**No pacote de terceiro** (quando a feature monta sobre um):

```bash
grep -rn "::find(\|whereKey(\|findOrFail(" vendor/{vendor}/{pkg}/src        # busca por id cru
grep -rn "public \$\|public ?" vendor/{vendor}/{pkg}/src | grep -v Locked   # prop que o cliente escreve
grep -rn '\$arguments\[\|\$data\[' vendor/{vendor}/{pkg}/src              # argumento do cliente na ação
grep -rn "extends Model" vendor/{vendor}/{pkg}/src/Models                    # models a escopar
```

#### Documentation API do Boost (`search-docs`)

**Cobertura oficial da Documentation API** (versões suportadas):

| Stack | Versões cobertas |
|---|---|
| Laravel Framework | 10.x, 11.x, 12.x, **13.x** |
| Filament | 2.x, 3.x, 4.x, **5.x** |
| Livewire | 1.x, 2.x, 3.x, **4.x** |
| Inertia | 1.x, 2.x |
| Flux UI | 2.x Free, 2.x Pro |
| Nova | 4.x, 5.x |
| Pest | 3.x, **4.x** |
| Tailwind CSS | 3.x, 4.x |

**Quando é obrigatório consultar** — antes de escrever qualquer um destes trechos do PRD:

| O que vai escrever | Consultar `search-docs` sobre |
|---|---|
| Rotas, middleware, policies, validação | Laravel Framework (versão do projeto) |
| Componente de UI, tabela, form, modal | Filament / Livewire / Flux (versão do projeto) |
| Jobs, queues, batching, scheduling | Laravel Framework — queues |
| CTs do arquivo `04` | Pest — expectations, mocking, datasets |
| CT-B do arquivo `05` | Livewire/Filament (comportamento assíncrono) + Pest browser |
| Broadcasting, eventos, Reverb/Echo | Laravel Framework |

**Como consultar bem**:

1. **Uma pergunta por consulta**, específica: *"Filament 5 table bulk action confirmation modal"* vence *"Filament tabelas"*
2. **Citar a versão** do pacote na consulta — a busca é filtrada pelos pacotes instalados, mas a versão desambigua o trecho retornado
3. **Confirmar no código antes de escrever no PRD**: a doc diz o que a API oferece; o `Grep`/`Read` diz o que o **seu** projeto faz. Divergência entre os dois vai para `02-decisoes-arquiteturais.md`
4. **Citar a origem no PRD** quando a decisão veio da doc: *"conforme doc do Filament 5 (search-docs)"* — dá rastreabilidade e evita re-pesquisa na próxima wiki

**Lacunas conhecidas — o que `search-docs` NÃO cobre**:

| Stack | Situação | Fallback |
|---|---|---|
| **Pest 5** | API cobre até **4.x** | doc oficial em `pestphp.com/docs` — `--tia`, `--agent`, sharding e os matchers novos **não** estão no `search-docs` |
| **Playwright / `pest-plugin-browser`** | não coberto | `pestphp.com/docs/browser-testing` + `playwright.dev` |
| Pacotes de terceiros | não coberto | vendor source (`Read vendor/{vendor}/{pkg}/src/...`) — já obrigatório no step 3 |
| Código da sua aplicação | não coberto por design | `Grep`/`Read` + `.ai/rules/` do projeto |
