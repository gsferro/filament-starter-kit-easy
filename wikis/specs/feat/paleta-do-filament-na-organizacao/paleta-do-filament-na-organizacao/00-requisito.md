# Requisito — A paleta do Filament na identidade visual da organização

## Fonte

- **Origem**: mensagem do mantenedor no chat, via `/feature-wiki`, mais uma resposta a pergunta de
  esclarecimento na mesma sessão
- **Data**: 2026-09-02
- **Autor / solicitante**: mantenedor do kit
- **Fidelidade**: **alta** — texto escrito; a forma da UI foi decisão explícita

## Texto Original

<!-- IMUTÁVEL. Não editar, não corrigir ortografia, não resumir, não reordenar. -->

> na config de uma empresa, temos a opção de identidade visual com cor e logo, so que a cor é escolha livre, mas no settings do kit, tem a opção de escolha das cores
>   padroes do filament. traga para ca a mesma opção de escolha

**Resposta à pergunta de esclarecimento (2026-09-02):**

> **Como a escolha da paleta entra na tela da organização?**
> Igual ao settings do kit — dois campos: Select com a paleta do Filament + o ColorPicker livre que
> já existe. O hexadecimal VENCE quando preenchido, como no kit.

## O que foi medido antes de escrever (estado, não requisito)

| Fato | Onde |
|---|---|
| A organização tem **um** campo de cor: `ColorPicker::make('cor_primaria')` com `->hex()` e regex `#RRGGBB`, coluna `tenants.cor_primaria` `string(7)` | `TenantForm.php:101-118`; migration `2026_08_14_000003` |
| O settings do kit tem **dois**: `Select::make('cor_primaria')` com as 16 cores de `CustomizadorDaInstalacao::CORES`, e `ColorPicker::make('cor_primaria_hex')` livre — "VENCE a seleção acima quando preenchida" | `ConfiguracoesDoKit.php:246-262` |
| A precedência do kit está codificada e testada em `CorPrimaria::paleta()`: hex válido → hex; senão nome válido → `Color::{Nome}`; senão `[]` (padrão do Filament). Hex **inválido cai para o nome**, não zera | `app/Support/CorPrimaria.php`; `tests/Kit/CorPrimariaTest.php:73-95` |
| A cor da organização é aplicada por `FilamentColor::register()` no `bootUsing()` do `/app`, devolvendo `['primary' => $tenant->cor_primaria]` (string → `Color::generatePalette()`); vence a do kit dentro de `/app/{slug}` | `AppPanelProvider.php:152-175`; ADR-02 da wiki `identidade-visual-da-organizacao` |
| Os nomes da paleta cabem em 7 caracteres **por coincidência** (`Emerald`, `Fuchsia`) — a coluna de hex não é lugar para eles | `CORES`, migration |
| A lista do kit tem `Slate`, um neutro, entre as 16 | `CustomizadorDaInstalacao::CORES` |

## Decomposição em Cláusulas

| ID | Cláusula | Trecho literal de origem | Tipo |
|----|----------|--------------------------|------|
| RQ-01 | A tela de identidade visual da organização oferece a escolha de uma cor da **paleta padrão do Filament** — a mesma lista que o settings do kit oferece | "no settings do kit, tem a opção de escolha das cores padroes do filament. traga para ca a mesma opção" | funcional |
| RQ-02 | A escolha livre em hexadecimal **continua existindo** na organização | "a cor é escolha livre" + "dois campos: Select … + o ColorPicker livre que já existe" | funcional (já existe; regressão) |
| RQ-03 | Com os dois preenchidos, o **hexadecimal vence** — a mesma precedência do kit | "O hexadecimal VENCE quando preenchido, como no kit" | funcional |
| RQ-04 | A cor escolhida na paleta é **aplicada** no painel `/app` da organização, do mesmo jeito que a cor livre já é | implícito em "a mesma opção" — escolher sem aplicar não é opção | funcional |
| RQ-05 | A escolha é **a mesma** do kit: mesma lista, mesmo comportamento com valor vazio (cai para a cor da aplicação) e com valor inválido (não derruba o painel) | "a mesma opção de escolha" | restrição |

## Ambiguidades e Perguntas Abertas

- **RQ-05, "mesma lista"** — a lista do kit inclui `Slate` (neutro). Assumido: a lista é
  **literalmente** `CustomizadorDaInstalacao::CORES`, sem editar — "mesma" é mesma, e a lista tem
  um dono só. **Se negado** (tirar `Slate` da organização): a organização ganha uma lista própria,
  e o `04` perde o cenário de identidade entre as duas.
- **RQ-03, hex inválido gravado** — no kit, hex inválido **cai para o nome**. Na organização o
  `ColorPicker` já recusa hex inválido na gravação (regex), então o caso só existe se alguém
  escrever direto no banco. Assumido: mesma regra do kit (cai para o nome, depois para a cor da
  aplicação); é o que um resolvedor único produz sem código a mais.
- **Valor de paleta que deixou de existir** (renomeado num upgrade do Filament, ou removido da
  lista) — assumido: mesma tolerância do kit: ignora e cai para a cor da aplicação, sem derrubar o
  painel. O `Select` mostra vazio; quem editar salva de novo.

## Fora de Escopo (declarado)

- Paleta **por painel** ou cor secundária — a wiki `identidade-visual-da-organizacao` (ADR-01)
  decidiu uma cor por organização; isto só muda **como** ela é escolhida.
- Exibir a cor na listagem ou na tela `view` da organização — hoje não exibe (só o formulário);
  não muda.
- O `kit:install`/`CustomizadorDaInstalacao` — pergunta a cor da **instalação**, não de organização.
- A cor livre do kit e a precedência organização > kit — já existem e já são testadas
  (`mantem a cor da organizacao vencendo a cor livre do kit`).
