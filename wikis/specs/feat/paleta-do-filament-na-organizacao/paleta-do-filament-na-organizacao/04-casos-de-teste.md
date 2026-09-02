# Casos de Teste — A paleta do Filament na identidade visual da organização

> Requisito: `00-requisito.md` · Plano: `01-plano-acao.md`
> Derivado do **requisito**, não do plano. A implementação não existe. O que foi lido do código é
> convenção de teste (`IdentidadeVisualTenancyTest`, `CorPrimariaTest`, helpers de `tests/Pest.php`)
> e a superfície que o `01` nomeia. Pipeline da `feature-test-design` (já carregada nesta sessão).

## Perfil de Derivação

| Área | P | I | P×I | Perfil |
|---|---|---|---|---|
| A cor aplicada no `/app` (duas fontes, precedência, resolvedor compartilhado com o kit) | 2 — integra com o `ColorManager` e com código que os três painéis usam | 2 — painel do cliente com a cor errada ou acromático; reversível | 4 | **padrão** |
| O formulário da organização (`Select` novo) | 1 | 1 — campo a mais numa tela existente | 1 | mínimo |

- Técnicas aplicadas: **tabela de decisão** (hex × nome → paleta), **EP** por atributo (lista fechada:
  dentro / fora / vazio), **propriedade** (a regra do kit ≡ a regra da organização), **rastreio de
  efeito** (a paleta registrada; o `debug` com a fonte), **gate de tela de escrita** (criar e editar).
- Cenários: **7** · Regras: 4 · Mutantes previstos: 10 · Sem matador: 0
- Revisão adversarial: não exigida (perfil `padrão`).

## Varredura SFDIPOT

| Letra | O que existe nesta feature | Cenários gerados |
|---|---|---|
| **S** | coluna `tenants.cor_primaria_nome`; `$fillable`; `CorPrimaria::resolver()`; `Select` no `TenantForm`; o `FilamentColor::register()` do `bootUsing()` | CT-01 (fillable pelo `fresh()`), CT-05 |
| **F** | escolher (formulário), resolver (precedência), aplicar (painel), registrar (log) | CT-01…CT-07 |
| **D** | nome **dentro** da lista, **fora** da lista, **vazio**, **inexistente em `Color`** (gravado direto); hex válido/inválido/vazio; a combinação dos dois | CT-02, CT-04 (tabela de decisão) |
| **I** | `CreateTenant`/`EditTenant` (Livewire), `GET /app/{slug}` (o painel pintado), `CorPrimaria::resolver()` direto | CT-01, CT-06, CT-04, CT-05 |
| **P** | `Filament\Support\Colors\Color` — os nomes são constantes dela; `ColorManager` aceita string **ou** paleta | CT-03 (todo nome existe), CT-04 |
| **O** | quem edita é `master_global`/`admin` no `/admin`; quem vê é qualquer usuário da organização no `/app` | CT-04 (usuário comum vê a cor) |
| **T** | **não se aplica**: sem estado temporal. A ordem de registro (kit no `boot()`, organização no `bootUsing()`) é a da ancestral e não muda — regressão herdada | — |

## Mapa de Regras

| Regra | Área (perfil) | Origem (`RQ`) | Técnica | Cenários |
|---|---|---|---|---|
| R1 — o `Select` oferece exatamente a lista do kit, grava o que está nela e recusa o que não está; vazio é permitido | formulário (mínimo, **escalado** a padrão por ser a fronteira de dado) | RQ-01, RQ-05 | EP por lista fechada + gate de escrita | CT-01, CT-02, CT-03, CT-06 |
| R2 — no `/app/{slug}` a paleta aplicada segue a regra do kit: hex válido vence; senão nome existente; senão a cor da aplicação; e nada derruba o painel | cor aplicada (padrão) | RQ-02, RQ-03, RQ-04, RQ-05 | tabela de decisão | CT-04 |
| R3 — a regra é **uma**: o que o kit resolve para a config, a organização resolve para as colunas | cor aplicada (padrão) | RQ-05 | propriedade | CT-05 |
| R4 — a aplicação da cor deixa registro com a fonte usada | cor aplicada (padrão) | convenção do kit (log no `bootUsing()`) | rastreio de efeito | CT-07 |

**Escalada**: R1 herdaria `mínimo` da área do formulário, mas é o **ponto de entrada** do dado
(gravação) — a taxonomia manda fronteira na gravação. Ganha o cenário de recusa (CT-02) e o de
identidade da lista (CT-03).

## Fronteira com o Plano

| Item do PRD | Recusado como oráculo porque | Destino |
|---|---|---|
| Nome da coluna `cor_primaria_nome` e do método `resolver()` | escolha de implementação | detalhe dos cenários |
| Rótulos e helper text dos campos | comportamento visível que o requisito não determina (só diz "a mesma opção") | não é oráculo; nenhum `assertSee` de rótulo |
| `columnSpanFull()` na logo | layout | fora dos cenários; Verificação Final olha a tela |
| A lista `CustomizadorDaInstalacao::CORES` | veio do **requisito** ("a mesma opção de escolha" → a mesma lista) e da ambiguidade resolvida no `00` | **oráculo legítimo** em CT-03 |
| Precedência hex > nome, hex inválido cai para o nome, nome inexistente cai para o padrão | é a regra do kit, e o `00` diz "a mesma opção" — confirmada pelo solicitante | **oráculo legítimo** em CT-04; a tabela de `CorPrimariaTest:73-95` é reaproveitada |
| Channel `tenancy`, nível `debug`, chave `fonte` | convenção do kit, não requisito | oráculo **auxiliar** em CT-07, declarado |

**Perguntas em aberto**: nenhuma nova. As três do `00` (lista literal, hex inválido no banco, nome
que deixou de existir) têm premissa adotada e linha própria em CT-04 (`@premissa`).

## Setup Global

### Camada

- `tests/Tenancy/PaletaDaOrganizacaoTest.php` — tudo que precisa de `/app/{slug}` ou do formulário
  de organização (que só existe com tenancy).
- `tests/Kit/CorPrimariaTest.php` — CT-05, ao lado dos casos da regra do kit.

### Personas e fixture

- Quem edita: `usuarioComPapel('master_global')` com `Filament::setCurrentPanel('admin')` antes do
  `Livewire::test()` (padrão de `recusa cor fora do formato hexadecimal`).
- Quem vê: `usuarioComPapel('panel_user', $organizacao)` + `->tenants()->attach()`.
- Organização: `Tenant::factory()->comIdentidadeVisual($hex, null, $paleta)` — o terceiro parâmetro
  é o desta feature; `create(['slug' => …])` para a URL.
- Oráculo da cor aplicada: `fronteiraDeRequest(); $this->get("/app/{$slug}")->assertSuccessful(); FilamentColor::getColors()['primary']` —
  o mesmo de `nao vaza a cor entre organizacoes e paineis`.
- `Color::Blue` (paleta pronta) e `Color::generatePalette('#059669')` (derivada do hex) como valores
  esperados. Default do Filament: medido **antes** de qualquer request, como a ancestral faz.

### Fakes

`Log::spy()` em CT-07. Nada mais.

---

## Regra R1 — o `Select` oferece exatamente a lista do kit, grava o que está nela e recusa o que não está

> `RQ-01`, `RQ-05` · perfil **padrão** (escalado) · técnica: **EP por lista fechada** + **gate de
> tela de escrita** (editar **e** criar — verbo irmão não herda evidência)

```gherkin
# language: pt

Funcionalidade: A paleta do Filament na identidade visual da organização

  Regra: a organização escolhe uma cor da mesma lista do kit, e só dela

    Cenário: [CT-01] a administradora escolhe uma cor da paleta e ela fica gravada
      Dado uma organização sem cor
      Quando a administradora salva a ficha da organização com a paleta "Blue"
      Então a organização, relida do banco, tem a paleta "Blue"
      E a cor livre continua vazia

    Esquema do Cenário: [CT-02] o que não está na lista não grava
      Dado uma organização sem cor
      Quando a administradora tenta salvar a ficha com a paleta "<valor>"
      Então o formulário recusa o campo da paleta
      E a organização, relida do banco, continua sem paleta

      Exemplos:
        | valor | # partição                                  |
        | Roxo  | nome fora da lista                          |
        | blue  | caixa diferente — a constante é `Blue`      |
        | Zinc  | cor que EXISTE no Filament e NÃO está na lista do kit |

    Cenário: [CT-03] a lista oferecida é a lista do kit, nome a nome
      Dado o formulário da organização
      Quando o mantenedor lê as opções do campo de paleta
      Então elas são exatamente os 16 nomes de CustomizadorDaInstalacao::CORES, na mesma ordem
      E cada um é uma constante de Filament\Support\Colors\Color

    Cenário: [CT-06] a criação grava paleta e cor livre juntas
      Dado nenhuma organização
      Quando a administradora cria a organização com a paleta "Emerald" e a cor livre "#059669"
      Então a organização existe com paleta "Emerald" e cor livre "#059669"
```

> **`Zinc` em CT-02** é a linha que separa "está em `Color`" de "está na lista do kit": uma
> implementação que valide por `defined(Color::X)` em vez de pela lista aceita `Zinc`. **`blue`**
> separa `in()` estrito de comparação frouxa. O `Então` de não-efeito é obrigatório: recusa que
> grava antes de reprovar passa num `assertHasFormErrors` sozinho.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M1 | `cor_primaria_nome` fora do `$fillable` — o `create()`/`update()` descarta em silêncio | CT-01, CT-06 (`fresh()` do banco) |
| M2 | Lista própria da organização, filtrada (sem `Slate`) ou copiada e desatualizada | CT-03 (nome a nome, mesma ordem) |
| M3 | Validação por `defined(Color::X)` em vez da lista — `Zinc` entra | CT-02 (linha `Zinc`) |
| M4 | `Select` sem o `in()` (por exemplo `->rules([])` ou `getOptionLabelUsing()` que nunca devolve null) — `Roxo` entra | CT-02 (linhas `Roxo`, `blue`) |

---

## Regra R2 — no `/app/{slug}` a paleta aplicada segue a regra do kit

> `RQ-02`, `RQ-03`, `RQ-04`, `RQ-05` · perfil **padrão** · técnica: **tabela de decisão** (hex × nome)

```gherkin
# language: pt

  Regra: a cor aplicada no painel da organização segue a precedência do kit — hex, depois paleta, depois a cor da aplicação

    Esquema do Cenário: [CT-04] a paleta registrada no /app é decidida pelas duas colunas
      Dado uma organização com cor livre "<hex>" e paleta "<nome>"
      E um usuário comum dessa organização
      Quando ele abre o painel da organização
      Então o painel responde 200
      E a paleta primária registrada é "<paleta>"

      Exemplos:
        | hex     | nome  | paleta                          | # regra da tabela                         |
        | #059669 | Blue  | generatePalette(#059669)        | os dois: hex vence                        |
        | vazio   | Blue  | Color::Blue                     | só a paleta — o novo                      |
        | #059669 | vazio | generatePalette(#059669)        | só o hex — regressão da cor livre         |
        | vazio   | vazio | a cor da aplicação (default)    | nada: neutro                              |
        | vazio   | Roxo  | a cor da aplicação (default)    | @premissa nome inexistente, gravado direto |
        | lixo    | Blue  | Color::Blue                     | @premissa hex inválido gravado direto cai para a paleta |
```

> **"Gravado direto"**: as duas últimas linhas só existem por `Tenant::factory()->create()` com o
> valor cru — o formulário recusa os dois (CT-02 e o caso herdado `recusa cor fora do formato
> hexadecimal`). São as linhas que provam RQ-05 "mesmo inválido": hoje, `lixo` em `cor_primaria`
> chega a `generatePalette()` e o painel fica acromático; com a regra do kit, cai para a paleta.
> O `Então` "responde 200" é o que separa "cai para o padrão" de "derruba o painel".
>
> **Default medido, não suposto**: `FilamentColor::getColors()['primary']` **antes** de qualquer
> request, como a ancestral faz — a cor da aplicação pode ser a do kit (config) e não o âmbar.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M5 | O `bootUsing()` continua lendo só `cor_primaria` — a paleta escolhida nunca é aplicada | CT-04 (linha "só a paleta") |
| M6 | Precedência invertida na organização (paleta vence hex) | CT-04 (linha "os dois") |
| M7 | A guarda `blank($tenant->cor_primaria)` é mantida antes de olhar o nome — com hex vazio devolve `[]` e a paleta nunca entra | CT-04 (linha "só a paleta") — **é a forma mais provável de M5**, e por isso a linha está duas vezes na tabela de mutantes |
| M8 | `constant()` sem `defined()` para o nome gravado direto — `Error: Undefined constant` em **toda** página do `/app` da organização | CT-04 (linha `Roxo`: 200 + default) |

---

## Regra R3 — a regra é uma: o que o kit resolve para a config, a organização resolve para as colunas

> `RQ-05` · perfil **padrão** · técnica: **propriedade** sobre a tabela que o kit já testa

```gherkin
# language: pt

  Regra: kit e organização compartilham a mesma resolução de cor

    Esquema do Cenário: [CT-05] o resolvedor devolve para dois argumentos o que a regra do kit devolve para a config
      Dado a config do kit com hex "<hex>" e nome "<nome>"
      Quando o mantenedor compara a paleta do kit com o resolvedor chamado com os mesmos dois valores
      Então as duas respostas são idênticas
      E são "<esperado>"

      Exemplos:
        | hex     | nome | esperado                 |
        | #7c3aed | Blue | ['primary' => '#7c3aed'] |
        | #7c3aed |      | ['primary' => '#7c3aed'] |
        | azul    | Blue | ['primary' => Color::Blue] |
        |         | Blue | ['primary' => Color::Blue] |
        | azul    | Roxo | []                        |
        |         |      | []                        |
```

> É a tabela de `CorPrimariaTest:73-95`, reaproveitada: os seis casos que definem a regra do kit
> passam a definir também a da organização. O segundo `Então` impede que as duas formas concordem
> por estarem erradas juntas.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M9 | `paleta()` mantém o corpo antigo e `resolver()` nasce como cópia — divergem no primeiro ajuste (por exemplo, o `FORMATO_HEX` muda num só) | CT-05 (primeiro `Então`, em toda linha) |

---

## Regra R4 — a aplicação da cor deixa registro com a fonte usada

> convenção do kit · perfil **padrão** · técnica: **rastreio de efeito** (o `debug` do `bootUsing()`)

```gherkin
# language: pt

  Regra: quem lê o log sabe se a cor veio da paleta ou do hexadecimal

    Esquema do Cenário: [CT-07] o registro de cor aplicada diz a fonte
      Dado uma organização com cor livre "<hex>" e paleta "<nome>"
      Quando um usuário dela abre o painel da organização
      Então há um debug no canal tenancy com o id da organização e fonte "<fonte>"

      Exemplos:
        | hex     | nome | fonte  |
        | vazio   | Blue | paleta |
        | #059669 | Blue | hex    |
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M10 | O log não diz a fonte, ou diz `hex` sempre (copiado do código anterior) | CT-07 (linha `paleta`) |

---

## Checklist de Taxonomia

| Item | Cenário que mata |
|---|---|
| IDOR / autorização horizontal | não se aplica: sem rota nova; o `TenantResource` já é governado por `TenantPolicy` |
| Autorização exercida na ação | não se aplica: nenhuma ação nova |
| Idempotência | não se aplica: gravação de coluna; salvar duas vezes o mesmo valor é o mesmo estado |
| Concorrência | não se aplica |
| **Fronteira no ponto de entrada** (gravação) | **CT-02** (fora da lista, caixa, cor fora da lista do kit), herdado `recusa cor fora do formato hexadecimal` para o hex |
| **Domínio condicionado** | **CT-04** — o efeito da paleta depende do hex estar vazio ou não (tabela de decisão) |
| Estado × operação de escrita | não se aplica |
| **Ausente ≠ null ≠ vazio** | **CT-04** linhas "vazio/vazio" (nulo no banco) e "Roxo" (preenchido com valor que não resolve); **CT-01** (cor livre continua vazia ao gravar paleta) |
| Paginação / ordenação | não se aplica |
| Timezone / DST | não se aplica |
| Unicode / limite de varchar | **CT-03** — todo nome da lista cabe na coluna nova; e a coluna é `string(32)`, não a de 7 (ADR-02) |
| Unicidade + soft delete | não se aplica |
| CRUD combinado | **CT-01** (editar) e **CT-06** (criar) |
| Mass assignment | **CT-01/CT-06** via `$fillable` — M1 |
| Upload | não se aplica |
| Precisão monetária | não se aplica |
| **Silêncio por valor inválido** (linha do projeto — painel acromático) | **CT-04** linhas `Roxo` e `lixo` |

## Índice de Cenários

| ID | Cenário | Regra | Técnica | Camada | Arquivo | Mata |
|----|---------|-------|---------|--------|---------|------|
| CT-01 | editar grava a paleta, cor livre segue vazia | R1 | gate de escrita (editar) | Livewire (`EditTenant`) | `tests/Tenancy/PaletaDaOrganizacaoTest.php` | M1 |
| CT-02 | fora da lista não grava (Roxo, blue, Zinc) | R1 | EP inválida isolada | Livewire | idem | M3, M4 |
| CT-03 | opções ≡ `CORES`, cada uma constante de `Color` | R1 | identidade de lista | Feature (schema do form) | idem | M2 |
| CT-06 | criar grava paleta + hex | R1 | gate de escrita (criar) | Livewire (`CreateTenant`) | idem | M1 |
| CT-04 | tabela de decisão hex × nome no `/app` | R2 | tabela de decisão | Feature (HTTP + `FilamentColor`) | idem | M5, M6, M7, M8 |
| CT-05 | `paleta()` ≡ `resolver()` | R3 | propriedade | Kit | `tests/Kit/CorPrimariaTest.php` | M9 |
| CT-07 | `debug` com a fonte | R4 | rastreio de efeito | Feature | `tests/Tenancy/PaletaDaOrganizacaoTest.php` | M10 |

## Sem CT-B

- **Motivo**: o oráculo de "a cor foi aplicada" é a paleta registrada em `FilamentColor::getColors()`,
  o mesmo que a wiki ancestral usa em `Feature`. O pixel a partir de uma paleta registrada é do
  Filament, e o CT-B da ancestral (`tests/BrowserTenancy/IdentidadeVisualTest.php`) roda na regressão
  — o mecanismo de registro não muda, só a origem do valor.

## Divergência entre skill e rule do projeto

- `pest --parallel --tia` da skill → `--no-tia` pela rule (`testes-browser.md`, sem PCOV).
