# Requisito — Estudo de viabilidade: Advanced Tables e alternativas

## Fonte

- **Origem**: mensagem no chat (texto escrito pelo solicitante)
- **Data**: 2026-08-26
- **Autor / solicitante**: gsferro (dono do kit)
- **Fidelidade**: alta (texto escrito)

## Texto Original

<!-- IMUTÁVEL. Não editar, não corrigir ortografia, não resumir, não reordenar. -->

> analise o pacote pago: "https://filamentphp.com/plugins/kenneth-sese-advanced-tables"
> - faça uma pesquisa completa em: "https://filamentphp.com/plugins" e veja se existem alternativas gratis ou como a gente implementaria as mesmas funções (principalmente a parte onde se cria botões de filtros especificos)
> - não precisa implementar nada por enquanto, vamos fazer um estudo de viabilidade e o quanto seria custoso implementar/criar um pacote free
> - use sub-agentes e worktree para essa analise

## Decomposição em Cláusulas

| ID | Cláusula | Trecho literal de origem | Tipo |
|----|----------|--------------------------|------|
| RQ-01 | O pacote pago Advanced Tables (Kenneth Sese) é analisado: funcionalidades, preço, licença, compatibilidade | "analise o pacote pago: https://filamentphp.com/plugins/kenneth-sese-advanced-tables" | funcional |
| RQ-02 | O diretório oficial de plugins é varrido em busca de alternativas gratuitas que cubram as mesmas funções | "faça uma pesquisa completa em: https://filamentphp.com/plugins e veja se existem alternativas gratis" | funcional |
| RQ-03 | Para o que não houver alternativa pronta, o estudo descreve como o kit implementaria as mesmas funções | "ou como a gente implementaria as mesmas funções" | funcional |
| RQ-04 | A parte de "botões de filtros específicos" (filtros pré-definidos acionados por botão/aba) recebe atenção principal | "principalmente a parte onde se cria botões de filtros especificos" | funcional |
| RQ-05 | Nenhum código é implementado nesta entrega; o produto é o estudo | "não precisa implementar nada por enquanto" | restrição |
| RQ-06 | O estudo é de viabilidade e traz estimativa de custo de implementar / criar um pacote gratuito | "vamos fazer um estudo de viabilidade e o quanto seria custoso implementar/criar um pacote free" | não-funcional |
| RQ-07 | A análise é feita com sub-agentes e em worktree isolado | "use sub-agentes e worktree para essa analise" | restrição |

## Ambiguidades e Perguntas Abertas

- **RQ-04** — "botões de filtros específicos" não diz se são (a) abas acima da tabela que restringem a query, (b) botões que preenchem o formulário de filtros com valores pré-definidos, ou (c) views salvas pelo próprio usuário e exibidas como botões (o que o Advanced Tables chama de *preset views* e *user views*).
  - **Assumido**: os três são analisados e custeados separadamente — o solicitante pediu "principalmente" essa parte, então o estudo cobre o espectro inteiro em vez de escolher um.
  - **Se negado**: só o nível escolhido permanece no `01-plano-acao.md`; a estimativa dos outros sai.

- **RQ-06** — "criar um pacote free" pode significar um pacote Composer publicado (para a comunidade) ou código dentro do kit (para quem nasce dele).
  - **Assumido**: os dois são custeados como níveis distintos — código no kit é pré-requisito do pacote, e o pacote acrescenta o custo de extração, README, testes isolados e manutenção pública.
  - **Se negado**: o nível "pacote publicável" sai do plano e da recomendação.

- **RQ-02** — "pesquisa completa" no diretório: o diretório não expõe o nome Composer no card e a busca é por texto livre. A varredura é por termos (table, filter, view, preset, saved, column) e por busca web complementar; um plugin com nome fora desses termos pode escapar. Registrado como limitação do método no `03-progresso.md`.

## Fora de Escopo (declarado)

- Implementar qualquer código, migration, teste ou instalar qualquer pacote (RQ-05).
- Comprar ou licenciar o pacote pago.
- Abrir PR — a entrega é a wiki commitada no branch `feat/estudo-advanced-tables`.
