# Requisito — `kit:update` lê a lista de caminhos da versão destino

## Fonte

- **Origem**: pedido do solicitante na conversa de 2026-09-05 ("abre wiki pro contorno do kit:update de v0.22.x pra v0.23+"), apontando para um achado **escrito** em três lugares: o `03-progresso.md` da wiki `fix/spotlight-sem-estilo`, a descrição do PR #53 e o CHANGELOG 0.23.1.
- **Data**: 2026-09-05
- **Autor / solicitante**: gsferro
- **Fidelidade**: **alta** para os três trechos escritos (transcritos abaixo); **baixa** para a frase do pedido, que só aponta para eles. A frase do pedido não acrescenta cláusula própria.

## Texto Original

<!-- IMUTÁVEL. Não editar, não corrigir ortografia, não resumir, não reordenar. -->

Pedido na conversa (2026-09-05):

> abre wiki pro contorno do kit:update de v0.22.x pra v0.23+

`wikis/specs/fix/spotlight-sem-estilo/spotlight-sem-estilo/03-progresso.md`, seção "Notas de Implementação":

> - **Dívida encontrada, fora desta entrega**: atualizar de v0.22.x direto para ≥ v0.23.0 pelo
>   `kit:update` **quebra o boot** do projeto entre as duas rodadas — a lista antiga entrega
>   `IdentidadeDoKit.php` sem `resources/views/svg`, e o service provider renderiza a view no boot.
>   O CHANGELOG 0.23.1 documenta o contorno (copiar a view), mas o comando poderia (a) ler a lista
>   da versão **destino** antes de aplicar, ou (b) o provider tolerar a view ausente. Candidata a
>   wiki própria.

Mesmo arquivo, nota seguinte sobre o menu:

> - **Menu do `kit:update` com versões antigas** (relato do solicitante durante a sessão): o piso
>   `PISO_DE_EXIBICAO = 0.23.0` está no kit desde a 0.24.0, mas o menu é montado pelo `KitUpdate.php`
>   **da instalação**, que na v0.22.x não tem piso. Encurta na primeira atualização — e é mais um
>   motivo para a 2ª rodada funcionar sem surpresa.

Descrição do PR #53, seção "Achado fora desta entrega":

> Atualizar de v0.22.x direto para ≥ v0.23.0 pelo `kit:update` quebra o boot entre as duas rodadas (`View [svg.arte-do-login] not found`): a lista antiga entrega `IdentidadeDoKit.php` sem a view. Contorno documentado no CHANGELOG 0.23.1; correção estrutural (ler a lista do destino antes de aplicar, ou o provider tolerar a view ausente) é candidata a wiki própria.

`CHANGELOG.md`, versão 0.23.1:

> - **`kit:update` não entregava a view da arte do login, e o projeto atualizado quebrava.** A v0.23.0
>   transformou a arte num Blade (`resources/views/svg/arte-do-login.blade.php`) e entregou o
>   `IdentidadeDoKit` que o consome — mas `resources/views/svg` não estava em
>   `KitUpdate::CAMINHOS_DO_KIT`. Quem rodou `php artisan kit:update` recebeu
>   `View [svg.arte-do-login] not found` no primeiro `composer dev`. **Atualize para esta versão e rode
>   `php artisan kit:update` de novo**; se preferir resolver na hora, o arquivo pode ser copiado do
>   repositório do kit para `resources/views/svg/arte-do-login.blade.php`.

## O mecanismo, medido no repositório

Registrado aqui porque a decomposição depende dele e ele não está escrito em nenhuma das fontes.

| Fato | Onde |
|---|---|
| A lista de caminhos que filtra o diff é a constante `CAMINHOS_DO_KIT` da própria classe do comando | `app/Console/Commands/KitUpdate.php:84` |
| O diff usa **só** essa constante: `git diff --name-status origem destino -- {CAMINHOS_DO_KIT}` | `KitUpdate.php:519-525` (`arquivosAlterados()`) |
| Quem roda o comando é a classe **da instalação** (a versão antiga), não a do kit destino — o PHP já carregou a classe antes de qualquer arquivo ser aplicado | comentário em `KitUpdate.php:868-883` (`encerrar()`) |
| Na v0.22.3, `CAMINHOS_DO_KIT` já tinha `app/Support` (onde mora `IdentidadeDoKit.php`) e **não** tinha `resources/views/svg` | `git show v0.22.3:app/Console/Commands/KitUpdate.php`, linhas 116 e 152-154 |
| `resources/views/svg` só entrou na lista na v0.23.1 (55 → 57 caminhos) | `git show v0.23.1:app/Console/Commands/KitUpdate.php` |
| O boot renderiza a view: `IdentidadeDoKit::arteDoLogin()` chama `view('svg.arte-do-login')->render()` e os três providers de painel a consomem em `->media()` | `app/Support/IdentidadeDoKit.php:84`, `app/Providers/Filament/AdminPanelProvider.php:139,146,156` |
| O comando **já avisa** que a segunda rodada é necessária e imprime o comando pronto com `--from` e `--no-branch` | `KitUpdate.php:877-889`; teste `tests/Kit/KitUpdateTest.php:294` |
| O `git fetch` do comando traz os objetos das tags (`refs/tags/*:refs/tags/kit-*`), então `git show kit-vX:caminho` funciona para qualquer arquivo de qualquer versão publicada | `KitUpdate.php:419`; `aplicar()` já depende disso (`git checkout destino -- caminho`, linha 765) |
| A constante tem a mesma forma textual em todas as tags relevantes: uma expressão regular sobre `CAMINHOS_DO_KIT = [ … ];` extrai 55 caminhos na v0.22.3 e na v0.23.0, 57 na v0.23.1 e na v0.29.0 | medido em 2026-09-05 com `preg_match` sobre `git show <tag>:app/Console/Commands/KitUpdate.php` |
| Existe uma instalação real em v0.22.3 para reproduzir: `TESTES KIT/v0223-tenancy` (`config/kit.php` marca `0.22.3`); e duas em v0.29.0 (`v0290-sem-tenancy`, `v0290-com-tenancy`) | `D:\PROJECTS\PACOTES\FILAMENTS\STARTER-KIT-EASY\TESTES KIT\` |

A sequência do defeito, portanto: rodada 1 com a classe v0.22.x aplica tudo o que a lista **antiga** cobre — inclusive `app/Support/IdentidadeDoKit.php` e os providers que chamam `arteDoLogin()` — e nada de `resources/views/svg`; o projeto não sobe mais (`View [svg.arte-do-login] not found`) até a rodada 2, que roda com a classe nova e entrega a view.

## Decomposição em Cláusulas

| ID | Cláusula | Trecho literal de origem | Tipo |
|----|----------|--------------------------|------|
| RQ-01 | Ao calcular o que mudou entre origem e destino, o `kit:update` considera a lista de caminhos **da versão destino**, e não apenas a constante da classe que está em execução | "o comando poderia (a) ler a lista da versão **destino** antes de aplicar" | funcional |
| RQ-02 | Arquivo coberto **só** pela lista da versão destino entra na **mesma** rodada em que o comando é executado — não fica para a segunda rodada | "a lista antiga entrega `IdentidadeDoKit.php` sem `resources/views/svg`"; "quebra o boot do projeto entre as duas rodadas" | funcional |
| RQ-03 | Alternativa nomeada pela fonte: o provider (via `IdentidadeDoKit`) tolera a ausência da view `svg.arte-do-login` sem derrubar o boot | "ou (b) o provider tolerar a view ausente" | funcional — **alternativa**, ver Ambiguidades |
| RQ-04 | Quando a lista do destino **não puder** ser lida, o comando continua funcionando com a lista da própria classe (comportamento atual) e o aviso da segunda rodada continua valendo | derivada: "é mais um motivo para a 2ª rodada funcionar sem surpresa" + o aviso já existente em `encerrar()` | restrição |
| RQ-05 | O aviso "RODE O COMANDO DE NOVO" não manda para uma segunda rodada quem já recebeu tudo o que a lista do destino cobre | derivada de RQ-02: uma segunda rodada que entrega nada é a "surpresa" que a fonte quer evitar | funcional |
| RQ-06 | O contorno documentado (copiar a view; atualizar para ≥ 0.23.1 e rodar de novo) continua registrado para quem está numa instalação cuja classe **ainda não tem** esta correção | "O CHANGELOG 0.23.1 documenta o contorno (copiar a view)" | documentação |

## Ambiguidades e Perguntas Abertas

- **RQ-01 × RQ-03 — "(a) … ou (b)"**: a fonte apresenta duas alternativas, não duas exigências. A decisão é de arquitetura e está em `02-decisoes-arquiteturais.md` (ADR-01).
  - **Assumido**: implementar **(a)**. É a única das duas que corrige a **classe** do defeito ("diretório novo no kit não chega na primeira rodada"), e não só a instância `svg.arte-do-login`. (b) esconde o sintoma desta instância e deixa o próximo diretório novo quebrar outra coisa — e o próprio `IdentidadeDoKit` diz que `null` ali "é uma regressão visível, não um default" (`IdentidadeDoKit.php:65-66`).
  - **Se negado** (o solicitante quer também (b)): entra um passo no PRD para `IdentidadeDoKit::artePadrao()` devolver um SVG mínimo quando `view()->exists('svg.arte-do-login')` for falso, com cenário próprio no `04`. RQ-03 sai de "alternativa descartada" para "atendida".

- **Alcance da correção — quem se beneficia**: a rodada 1 roda com a classe **da instalação**. Uma instalação em v0.22.x continuará usando a classe v0.22.x na primeira rodada; esta correção só age a partir da versão que a carrega.
  - **Assumido**: aceito. A correção vale para toda atualização **futura** (instalação ≥ versão desta correção → qualquer versão posterior que adicione diretório novo). Para instalações anteriores, o aviso da segunda rodada com `--from` (v0.30.0) e o contorno do CHANGELOG 0.23.1 continuam sendo o caminho — RQ-06.
  - **Se negado**: não há alternativa técnica; o código que roda na rodada 1 já está na máquina do usuário.

- **Como ler a lista do destino**: a fonte não diz. Duas formas: (i) `git show destino:app/Console/Commands/KitUpdate.php` e extrair a constante do fonte; (ii) mover a lista para um arquivo de dados e ler esse arquivo do destino.
  - **Assumido**: (i). Funciona com **toda tag já publicada** (a constante existe com a mesma forma desde a 0.9.x); (ii) só funcionaria a partir da tag que criasse o arquivo. Ver ADR-01.
  - **Se negado**: (ii) exige o arquivo de dados + fallback para (i) nas tags antigas — mais código, mesmo resultado.

- **Registro em log**: a skill pede channel por feature. É um comando interativo, rodado à mão, cuja saída no terminal **é** o registro.
  - **Assumido**: sem channel; o aviso de fallback (RQ-04) vai para o terminal via `$this->components->warn()`. Ver ADR-03.
  - **Se negado**: criar channel `kit-update` em `config/logging.php` e espelhar o aviso.

## Fora de Escopo (declarado)

- Corrigir a rodada 1 de instalações que **já estão** em v0.22.x: a classe que roda lá não pode ser alterada remotamente. RQ-06 cobre o que se pode fazer (documentar).
- O default de `--from` na segunda rodada (a versão já marcada em `config/kit.php`): já corrigido pelo aviso com o comando pronto na v0.30.0 (PR #53).
- O piso de exibição do menu (`PISO_DE_EXIBICAO`) nas versões antigas: mesmo motivo — é a classe da instalação.
- Substituir a lista curada `CAMINHOS_DO_KIT` por varredura da árvore do kit: a lista **exclui** de propósito código do usuário que mora nos mesmos diretórios (ver `tests/Kit/KitUpdateTest.php:131-141`).
