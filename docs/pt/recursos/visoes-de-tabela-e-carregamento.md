---
title: "Visões salvas e carregamento contínuo"
description: "Duas opções de tabela que nascem desligadas e se ligam na aba Tabelas das configurações: visões salvas com nome (filtros, busca, ordenação, agrupamento e colunas, por usuário e por organização) e carregamento contínuo (rolagem infinita ou botão Carregar mais)…"
sidebar:
  order: 10
---
Duas opções de tabela que **nascem desligadas** e se ligam na aba **Tabelas** de
`/admin/configuracoes-da-aplicacao`, sem deploy:

| Opção | O que faz | Pacote |
|---|---|---|
| **Visões salvas** | o usuário grava o recorte da listagem (filtros, busca, ordenação, agrupamento e colunas) com um nome e volta a ele num clique | `sehsah/filament-remember-table` |
| **Carregamento da tabela** | em vez de páginas numeradas, a tabela cresce por rolagem ou por um botão *Carregar mais* | `asignua/filament-infinite-scroll` |

Os dois saíram da terceira rodada de estudo de pacotes do kit, que avaliou cinco plugins e adotou
três — o terceiro é a extensão de PHPStan descrita em [Qualidade de código](../../referencia/qualidade-de-codigo/).
Os dois que ficaram de fora, e por quê, estão em [Pacotes instalados](../../referencia/pacotes-instalados/#avaliados-e-não-adotados).

![Animação das visões salvas: o botão Visões abre a lista, Salvar como nova visão grava o recorte atual com nome e Gerenciar visões renomeia, reordena e apaga](https://raw.githubusercontent.com/gsferro/filament-starter-kit-easy/main/art/visoes-de-tabela.gif)

<details>
<summary>A mesma animação em tema escuro</summary>

![Animação das visões salvas em tema escuro](https://raw.githubusercontent.com/gsferro/filament-starter-kit-easy/main/art/visoes-de-tabela-escuro.gif)

</details>

## Visões salvas

Com a opção ligada, toda listagem do kit (usuários, organizações, convites, agentes de IA, papéis,
projetos, execuções de IA e pacotes do Composer, nos três painéis) ganha o botão **Visões** no
cabeçalho, antes de *Criar*.

### O que uma visão guarda

Filtros, busca, buscas por coluna, ordenação, agrupamento, as colunas visíveis e a ordem delas, e a
aba ativa da listagem. **Não guarda dados**: é um recorte, aplicado sobre o que o usuário já pode
ver. A largura das colunas continua sendo do `resized-column`, e o último recorte usado continua
sendo lembrado na sessão (opção *Lembrar filtro, busca e ordenação*) — as visões são **vários**
recortes, com nome, no banco.

### Para quem

Salvar, atualizar, gerenciar e limpar exigem a permissão **`Usar:VisoesDeTabela`**, concedida a
papéis na tela de papéis. Numa instalação nova, os papéis de administração dos três painéis a
recebem; o usuário comum (`panel_user`) não — o administrador concede, se quiser. **Aplicar** uma
visão que o usuário já vê não exige permissão.

### De quem é cada visão

| Regra | O que acontece |
|---|---|
| Dono | uma visão pertence a quem a gravou e à listagem em que foi gravada; só o dono atualiza, renomeia, reordena ou apaga |
| Organização | no painel de organização, a visão pertence à organização aberta: não aparece em outra, nem para o mesmo usuário |
| Padrão | o usuário marca uma visão como padrão e ela abre sozinha com a listagem (a menos que a URL já traga filtro, busca ou ordenação) |
| Compartilhada | com a opção *Compartilhar com todos* ligada nas configurações, o dono pode marcar a visão como compartilhada: todo usuário do painel (na organização, todo membro dela) a vê e aplica, com o rótulo *Compartilhada*; só o dono a altera |
| Link | a visão aplicada vai na URL (`?view=12`): o link abre a listagem já naquele recorte para quem puder vê-la |
| Modificada | mudou o filtro depois de aplicar uma visão? O botão passa a mostrar `Nome (modificada)` e oferece *Atualizar visão atual* ao dono |
| Robustez | filtro, coluna, agrupamento ou aba que deixou de existir é ignorado ao aplicar; coluna nova aparece normalmente |
| Limite | até 50 visões por usuário em cada listagem |

| Tema claro | Tema escuro |
|---|---|
| [![Botão Visões aberto numa listagem, com uma visão padrão, uma compartilhada e as ações de salvar, atualizar, gerenciar e limpar](https://raw.githubusercontent.com/gsferro/filament-starter-kit-easy/main/art/thumbs/visoes-aberta.png)](https://raw.githubusercontent.com/gsferro/filament-starter-kit-easy/main/art/visoes-aberta.png) | [![O mesmo botão Visões aberto em tema escuro](https://raw.githubusercontent.com/gsferro/filament-starter-kit-easy/main/art/thumbs/visoes-aberta-escuro.png)](https://raw.githubusercontent.com/gsferro/filament-starter-kit-easy/main/art/visoes-aberta-escuro.png) |
| [![Modal Gerenciar visões, com as visões do usuário renomeáveis, reordenáveis e apagáveis](https://raw.githubusercontent.com/gsferro/filament-starter-kit-easy/main/art/thumbs/visoes-gerenciar.png)](https://raw.githubusercontent.com/gsferro/filament-starter-kit-easy/main/art/visoes-gerenciar.png) | [![Modal Gerenciar visões em tema escuro](https://raw.githubusercontent.com/gsferro/filament-starter-kit-easy/main/art/thumbs/visoes-gerenciar-escuro.png)](https://raw.githubusercontent.com/gsferro/filament-starter-kit-easy/main/art/visoes-gerenciar-escuro.png) |

### Desligar não apaga

Desligar a opção esconde o botão e as ações em toda listagem; as visões gravadas ficam no banco e
voltam quando a opção é religada. Com a opção desligada, nenhuma consulta de visão é feita.

### Numa listagem sua

Resource novo criado no seu projeto entra com duas linhas na página de listagem, como manda
[Depois de criar resources](../../operacao/depois-de-criar-resources/):

```php
use App\Filament\Concerns\VisoesDeTabela;

class ListProdutos extends ListRecords
{
    use VisoesDeTabela;

    protected function getHeaderActions(): array
    {
        return [
            ...$this->getTableViewActions(),
            CreateAction::make(),
        ];
    }
}
```

Visões **pré-definidas em código** (filtros fixos que todo mundo vê, sem poder editar) são um
recurso do pacote que o kit não embarca: implemente `getPresetTableViews()` na sua página, conforme a
documentação do `remember-table`. Resource de plugin de terceiro fica sem o botão — não há como
editar a página dele.

## Carregamento da tabela

Um campo de três valores, que vale para **toda** tabela dos três painéis, inclusive as dos plugins:

| Valor | Como a tabela carrega |
|---|---|
| **Paginação** (o padrão) | as páginas numeradas de hoje; nada muda |
| **Rolagem infinita** | o próximo bloco carrega sozinho quando o fim da tabela entra na tela |
| **Botão "Carregar mais"** | o próximo bloco carrega no clique |

Em qualquer um dos dois modos contínuos:

- cada bloco tem o tamanho de **Linhas por página** (a opção ao lado, na mesma aba);
- o rodapé mostra *Mostrando 10 de 120* enquanto há mais, e *Todos os 120 registros foram carregados* no fim;
- busca, filtro, ordenação, agrupamento ou troca de aba **reiniciam** no primeiro bloco;
- há um teto de **500 linhas** na tela: ao chegar nele o rodapé pede busca ou filtro para restringir a lista;
- o seletor "registros por página" some, porque é o modo que decide o tamanho;
- seleção, ações em massa e agrupamento continuam funcionando — a tabela continua sendo uma só consulta, com mais linhas.

![Animação do carregamento contínuo no modo botão: Mostrando 10 de 31, Carregar mais, 20 de 31, 30 de 31](https://raw.githubusercontent.com/gsferro/filament-starter-kit-easy/main/art/carregamento-continuo.gif)

<details>
<summary>A mesma animação em tema escuro</summary>

![Animação do carregamento contínuo em tema escuro](https://raw.githubusercontent.com/gsferro/filament-starter-kit-easy/main/art/carregamento-continuo-escuro.gif)

</details>

| Rolagem infinita (claro) | Rolagem infinita (escuro) |
|---|---|
| [![Rodapé da tabela em rolagem infinita, com Mostrando 10 de 30 e o indicador de carga](https://raw.githubusercontent.com/gsferro/filament-starter-kit-easy/main/art/thumbs/tabela-rolagem.png)](https://raw.githubusercontent.com/gsferro/filament-starter-kit-easy/main/art/tabela-rolagem.png) | [![O mesmo rodapé em tema escuro](https://raw.githubusercontent.com/gsferro/filament-starter-kit-easy/main/art/thumbs/tabela-rolagem-escuro.png)](https://raw.githubusercontent.com/gsferro/filament-starter-kit-easy/main/art/tabela-rolagem-escuro.png) |

| Botão "Carregar mais" (claro) | Botão "Carregar mais" (escuro) |
|---|---|
| [![Rodapé da tabela no modo botão, com Mostrando 10 de 31 e o botão Carregar mais](https://raw.githubusercontent.com/gsferro/filament-starter-kit-easy/main/art/thumbs/tabela-carregar-mais.png)](https://raw.githubusercontent.com/gsferro/filament-starter-kit-easy/main/art/tabela-carregar-mais.png) | [![O mesmo rodapé com o botão Carregar mais em tema escuro](https://raw.githubusercontent.com/gsferro/filament-starter-kit-easy/main/art/thumbs/tabela-carregar-mais-escuro.png)](https://raw.githubusercontent.com/gsferro/filament-starter-kit-easy/main/art/tabela-carregar-mais-escuro.png) |

O modo contínuo só vale para a paginação padrão do Filament. Uma tabela que troque para paginação
simples ou por cursor (`paginationMode()` no `table()` do recurso) não ganha o rodapé e, com o
carregamento em rolagem ou botão, fica presa na primeira página: o hook do pacote reinicia a página
em todo request. Nenhuma tabela do kit usa esses modos; se o seu projeto precisar deles, mantenha o
carregamento em `paginacao`.

## Configuração

Na aba **Tabelas** de `/admin/configuracoes-da-aplicacao`:

| Opção | O que define | Nasce |
|---|---|---|
| **Carregamento da tabela** | `Paginação`, `Rolagem infinita` ou `Botão "Carregar mais"` | `Paginação` |
| **Visões salvas** | o botão *Visões* nas listagens do kit | desligado |
| **Compartilhar visões com todos** | a opção *Compartilhar com todos* ao salvar e ao gerenciar (só aparece com as visões ligadas) | desligado |

As três existem no `.env` como semente e plano B — `KIT_TABELA_CARREGAMENTO`
(`paginacao`, `rolagem` ou `botao`; outro valor cai em `paginacao`), `KIT_TABELA_VISOES_SALVAS` e
`KIT_TABELA_VISOES_COMPARTILHADAS` — e, como no restante da aba, **o banco vence o `.env`**. Mais
detalhes em [Configurações do kit](../configuracoes-do-kit/).

[![Aba Tabelas das configurações da aplicação, com Carregamento da tabela, Visões salvas e Compartilhar visões com todos](https://raw.githubusercontent.com/gsferro/filament-starter-kit-easy/main/art/thumbs/admin-configuracoes-tabelas.png)](https://raw.githubusercontent.com/gsferro/filament-starter-kit-easy/main/art/admin-configuracoes-tabelas.png)

## Os cinco pacotes avaliados e o critério

O **critério** da rodada foi um só: o pacote agrega valor ao kit se entrega uma capacidade que o kit
não tem, cabe no que o kit já é (multi-organização, sem tema customizado do Filament, PHPStan nível 8,
toda opção desligável nas configurações) e pode ser embrulhado num ponto só do código. Veredito por
pacote, com o motivo e o que reabriria a decisão:

- `sehsah/filament-remember-table` — **ADOTAR**: visões salvas com escopo por organização e sem blade própria.
- `asignua/filament-infinite-scroll` — **ADOTAR**: um macro no `configureUsing()` global, governado pela aba Tabelas.
- `happenv-com/filament-phpstan-macros` — **ADOTAR** (só em desenvolvimento): o macro novo é tipado sem exceção no `phpstan.neon`.
- `spiggle/list-view` — **RECUSAR**. Motivo: o modo lista reconstrói as colunas (badge, lightbox e colunas custom do kit não sobrevivem), a preferência fica na sessão sem organização e o kit já empilha linha em cartão no celular. Reabrir quando: um projeto do kit pedir lista alfabética com iniciais no desktop.
- `happenv-com/filament-saved-views` — **RECUSAR**. Motivo: o mesmo requisito do `remember-table`, sem escopo por organização e com blade própria em utilitárias Tailwind que exige tema customizado do Filament. Reabrir quando: o `remember-table` for abandonado, ou o kit adotar tema customizado e o pacote ganhar escopo por organização.

## Tema escuro

Os dois recursos acompanham o tema do painel sem configuração: o botão, os modais e as badges das
visões são componentes do Filament, e o rodapé do carregamento contínuo tem a cor escura na folha do
próprio pacote. As imagens desta página mostram os dois temas.

## Custo por requisição

- **Visões desligadas**: zero consulta a mais em qualquer listagem.
- **Visões ligadas**: quatro consultas pequenas na abertura da listagem (a lista de visões do usuário, a visão padrão e duas verificações de "tem visão própria?" que decidem se *Gerenciar* aparece) e três a cada atualização seguinte da tabela. Todas filtram por tabela e usuário, com índice.
- **Carregamento contínuo**: cada bloco é um request que reexecuta a consulta da tabela com um `LIMIT` maior, mais a contagem total — é assim que o pacote mantém seleção e agrupamento numa consulta só. O bloco redesenha todas as linhas já carregadas, não só as novas: numa listagem que consulta algo por linha (papéis, avatar), esse custo cresce com o total na tela, e é por isso que o teto de 500 linhas existe. Com `Paginação`, nada muda.
