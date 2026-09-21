---
title: "Configuração global do Filament"
description: "Um único arquivo define como toda tabela, toggle, modal e coluna do projeto se comporta: app/Providers/Concerns/ConfiguraFilamentGlobal.php (aplicado pelo…"
sidebar:
  order: 5
---
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

> **Quatro desses defaults são editáveis em [Configurações do kit](../configuracoes-do-kit/)**, na aba *Tabelas*: linhas por página, linhas listradas, persistência do recorte e colunas arrastáveis. No `.env` as mesmas quatro existem como semente e plano B — `KIT_TABELA_PAGINACAO`, `KIT_TABELA_LISTRADA`, `KIT_TABELA_PERSISTIR_FILTROS` e `KIT_TABELA_COLUNAS_REDIMENSIONAVEIS` — e o valor gravado no banco vence. O resto continua sendo decisão de código, de propósito — são escolhas com motivo escrito, não preferência de gosto.
>
> ⚠️ **Densidade de tabela não existe no Filament 5** e por isso não está na tela. O TODO antigo daqui prometia os quatro itens, e um deles não tem API: varredura em `vendor/filament/tables/src` não devolve nenhuma ocorrência de `density`, e `vendor/filament/tables/src/Enums/` traz sete enums, nenhum de densidade. O que o framework oferece de controle visual de aperto é o `striped()`, e é ele que ficou configurável.


## Correções de contraste que o kit aplica por você

Nem tudo que decide a aparência do painel está em PHP. Alguns pares de cor que o Filament e os plugins entregam **não alcançam o mínimo WCAG AA de 4,5:1** para texto pequeno, e o efeito é silencioso: o HTML está correto, o teste passa, e o usuário é que não lê direito. O kit corrige esses casos em `resources/css/filament/kit.css`, carregado nos três painéis pelo mesmo mecanismo dos plugins.

| Onde | O que o Filament/plugin entrega | O que o kit aplica |
|---|---|---|
| Item ativo da navegação **no topo** | `primary-600` sobre `gray-50` — 3,06:1 na paleta padrão | `primary-700` no claro, `primary-400` no escuro |
| Badge do indicador de ambiente | `color-600` sobre `color-50` | `color-700` no claro |
| Utilitárias `*-primary-*` de plugin | paleta âmbar **literal**, do build do pacote | as variáveis `--primary-*` do painel |

### Navegação no topo

Se o seu painel usa `->topNavigation()`, o rótulo do item ativo passa a ser legível na paleta que você escolheu. Medido com as vinte e uma paletas nomeadas do Filament: **nove reprovam** no degrau que o framework aplica, e nenhuma reprova no degrau que o kit aplica. `Amber` é o default quando `KIT_COR_PRIMARIA` está vazio e dá 3,06:1 — a instalação recém-feita cai justamente no pior caso.

**Se o seu painel usa o menu lateral, que é o padrão, nada muda.** O Filament só emite os itens do topo quando a navegação no topo está ligada, então a regra não casa com elemento nenhum. Você pode alternar entre os dois modos sem pensar nisso.

### Tema escuro não é detalhe

Toda sobrescrita de cor do kit tem **duas** regras, uma por tema, e isso não é preciosismo. A regra escura do Filament é escrita como

```css
.fi-topbar-item.fi-active .fi-topbar-item-label:where(.dark, .dark *)
```

e **`:where()` contribui zero especificidade**. Uma sobrescrita escrita só para o tema claro, com especificidade suficiente para vencer, vence nos **dois** — e pinta a cor clara sobre o fundo escuro, piorando exatamente o que veio corrigir.

O par escuro se escreve `.dark:root`, com a classe na **própria raiz**. `.dark :root` e `:root .dark` parecem equivalentes e são letra morta: `:root` *é* o `<html>`, então ele não pode ser descendente de nada nem conter a classe que o alternador de tema escreve nele próprio.

> **Ao acrescentar sua própria sobrescrita de cor**, siga o mesmo par e rode `php artisan filament:assets` depois de editar. As guardas ficam em `tests/Kit/ContrasteDaNavegacaoNoTopoTest.php` e `tests/Kit/CorrecaoDeCorPrimariaTest.php`: elas leem o CSS e as folhas do `vendor/` em runtime, recalculam o contraste com as paletas do próprio Filament e ficam vermelhas quando um upgrade de pacote muda o jogo.

## Onde apontar o `DocumentRoot` — e o que o kit faz quando ele está errado

Um projeto Laravel é servido a partir de **`public/`**, nunca da raiz. O `DocumentRoot` do
servidor (ou a *Document Root* do painel de hospedagem) tem de apontar para:

```
/caminho/do/projeto/public
```

Em hospedagem compartilhada isso nem sempre é configurável, e a saída comum é um `.htaccess` na
raiz reescrevendo tudo para dentro de `public/`. **Funciona, com um efeito colateral.**

Depois de uma reescrita *interna*, o Apache entrega ao PHP `SCRIPT_NAME=/public/index.php`
enquanto o `REQUEST_URI` continua o que o usuário pediu. O Laravel deriva a base do endereço
comparando os dois:

| O que o navegador pediu | Base derivada | Endereços gerados na página |
|---|---|---|
| `/app`, `/admin`, `/infra` | vazia | limpos |
| `/public/app` | `/public` | **todos** com o prefixo |

Ou seja: basta entrar **uma vez** por um endereço com `/public` — um favorito antigo, um link
compartilhado — para que toda a navegação daquela página saia prefixada. Como o `.htaccess`
costuma redirecionar de volta, o prefixo aparece e some, o que faz o problema parecer aleatório.

**O kit se defende disso — quando consegue saber que é seguro.** Ele recusa honrar uma base de
endereço terminada em `/public` e reconstrói a raiz sem o sufixo, uma vez por requisição, **mas só
quando há sinal de que `/` realmente roteia para dentro de `public/`**. Sem esse sinal ele não age,
e o motivo está logo abaixo. Três consequências que valem saber:

- vale para **qualquer painel**, inclusive os que você criar — a correção é na raiz do endereço,
  não numa lista de painéis;
- vale para **asset** também (`/css`, `/js`, `/build`), que sai do mesmo gerador;
- em instalação correta **nada acontece**: a base é vazia e a verificação sai na primeira linha,
  sem consulta e sem custo.

> **Isso não dispensa arrumar o `DocumentRoot`.** A defesa do kit tira o prefixo dos endereços que
> a aplicação gera; ela não impede alguém de digitar `/public/...` na barra, nem corrige o
> redirecionamento extra que o `.htaccess` faz a cada clique. Se você puder apontar o
> `DocumentRoot` para `public/`, aponte.
>
**Quando o kit remove o prefixo, e quando não remove.** Ele só encurta se houver **sinal** de que
`/` realmente roteia para dentro de `public/` — na prática, uma `RewriteRule` apontando para
`public/` no `.htaccess` da raiz. Sem sinal ele **não age**, e o motivo é concreto: uma instalação
servida em `https://host/public/...` **sem** reescrita funciona assim, e encurtar transformaria
todo link e todo asset em 404.

Em nginx não há `.htaccess` para inspecionar. Declare por configuração:

```dotenv
KIT_URL_REMOVER_SUFIXO_PUBLIC=true   # a reescrita está no vhost
KIT_URL_REMOVER_SUFIXO_PUBLIC=false  # desliga de vez
```

Sem a chave, o kit detecta sozinho.

> **O que a defesa NÃO alcança.** Ela conserta o endereço que a aplicação **gera**. O que vem do
> próprio pedido continua com o prefixo: a URL guardada quando você é mandado ao login
> (`redirect()->guest()`) e o "voltar" que sai do `Referer`. Quem cai no login vindo de
> `/public/app` volta para `/public/app` depois de autenticar. Mais uma razão para arrumar o
> `DocumentRoot`.

> Guarda: `tests/Kit/UrlSemPrefixoPublicTest.php`.
