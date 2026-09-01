---
title: "Configuração global do Filament"
parent: Recursos
grand_parent: Português
nav_order: 5
---

# Configuração global do Filament

Um único arquivo define como **toda** tabela, toggle, modal e coluna do projeto se comporta: `app/Providers/Concerns/ConfiguraFilamentGlobal.php` (aplicado pelo `KitServiceProvider`). Mudou ali, mudou em todo lugar — inclusive nas telas dos plugins de terceiros, que você não conseguiria editar de outro jeito.

**Toda tabela nasce com:**

| Comportamento | Por quê |
|---|---|
| `deferLoading()` | a tela aparece antes da query terminar |
| `striped()` + `stackedOnMobile()` | leitura em lista no desktop, cartão no celular |
| `persistFilters/Search/Sort/ColumnSearchesInSession()` | o recorte do usuário sobrevive à navegação |
| `reorderableColumns()` + `dragReorderableColumns()` + `stickableColumns()` | colunas reordenáveis, arrastáveis e fixáveis |
| **colunas redimensionáveis** (`asmit/resized-column`) | largura ajustável pelo usuário, preservada na sessão |
| `filtersLayout(Modal)` + `filtersFormColumns(2)` + `deferFilters()` | com 3+ filtros o dropdown vira rolagem; o modal não |
| `defaultPaginationPageOption(10)` + `extremePaginationLinks()` | paginação previsível, com atalhos de primeira/última |
| `deselectAllRecordsWhenFiltered(false)` | filtrar não descarta a seleção |

Também são globais: modal que **não** fecha no Esc (um toque acidental descartaria o formulário), toggles com cor e ícone de estado, coluna de ícone booleana com check/x colorido, `CreateAction` com ícone padrão e o alternador de painéis.

> **Colunas redimensionáveis em telas novas:** o comportamento padrão já vale para qualquer tabela; para que a largura escolhida seja **lembrada**, a página de listagem precisa do trait:
>
> ```php
> use Asmit\ResizedColumn\HasResizableColumn;
>
> class ListProdutos extends ListRecords
> {
>     use HasResizableColumn;
> }
> ```

> **Quatro desses defaults são editáveis em [Configurações do kit](configuracoes-do-kit.md)**, na aba *Tabelas*: linhas por página, linhas listradas, persistência do recorte e colunas arrastáveis. No `.env` as mesmas quatro existem como semente e plano B — `KIT_TABELA_PAGINACAO`, `KIT_TABELA_LISTRADA`, `KIT_TABELA_PERSISTIR_FILTROS` e `KIT_TABELA_COLUNAS_REDIMENSIONAVEIS` — e o valor gravado no banco vence. O resto continua sendo decisão de código, de propósito — são escolhas com motivo escrito, não preferência de gosto.
>
> ⚠️ **Densidade de tabela não existe no Filament 5** e por isso não está na tela. O TODO antigo daqui prometia os quatro itens, e um deles não tem API: varredura em `vendor/filament/tables/src` não devolve nenhuma ocorrência de `density`, e `vendor/filament/tables/src/Enums/` traz sete enums, nenhum de densidade. O que o framework oferece de controle visual de aperto é o `striped()`, e é ele que ficou configurável.

