---
title: "Mapa interativo do kit"
description: "Os três painéis, sem e com o tenant ativo, e os fluxos do kit em diagramas interativos, gerados pelo archify no build do site a partir de um modelo…"
sidebar:
  order: 6
---
Os três painéis, sem e com o tenant ativo, e os fluxos do kit em diagramas interativos, gerados pelo
[archify](https://github.com/tt-a1i/archify) no build do site a partir de um modelo versionado do código. Cada peça
mostra as F-nn do [catálogo de features](../../operacao/roteiro-de-features/) que ela entrega. Cada ligação traz a
prova — o arquivo, a linha e o trecho de código — num card do próprio diagrama, e o passaporte de cada peça abre a
linha no GitHub, no commit que gerou o site.

## Como ler

- **Árvore de registro**: tudo o que o painel registra — recursos, páginas, widgets, plugins e middlewares —,
  conferido contra o painel vivo.
- **Resumo por domínio**: o painel e os domínios de feature, que são as seções do catálogo; cada caixa nomeia as
  peças dela num card, e as setas entre as caixas somam as ligações das peças.
- **Mapas de domínio**: as peças de um domínio e as ligações entre elas. As setas não têm rótulo: o traço diz o
  verbo — traço forte, registra; comum, usa; tracejado, lê; de alerta, grava —, e o card "Como ler as setas"
  repete a convenção.
- **Fluxos**: sequências (login, 2FA, convites, IA…), os workflows dos comandos `kit:*`, ciclos de vida, fluxos de
  dados, o modelo de dados, as classes, a linha do tempo das versões e os tempos de instalação e de CI.
- Clique numa peça para abrir o passaporte: o tipo, a origem verificada (repositório e commit) e as provas com
  link. O card "Prova das ligações" lista cada ligação com o arquivo, a linha e o trecho.

## Cada painel, sem e com tenant

<!-- mapa:grade -->

## Todos os tipos, por painel e modo

Cada tipo de diagrama do archify, em cada painel e em cada modo, leva aos diagramas ou diz por que não há.

<!-- mapa:matriz -->

## Do kit inteiro

O que não pertence a um painel: os comandos do kit, os estados das contas, convites e sessões, o modelo de dados,
as classes, as versões e os tempos.

<!-- mapa:kit -->

## Limites declarados

- **O comparativo** sem → com tenant usa a casca do archify, que só existe em inglês e não leva cards nem
  passaporte: a prova de cada ligação que ele desenha está nos dois diagramas que ele compara, ao lado dele nas
  tabelas acima.
- **Acessibilidade**: o CI abre um diagrama de cada tipo, em cada idioma e tema, e reprova qualquer violação
  `serious` ou `critical` do WCAG 2.1 AA, com duas exceções do próprio archify, reconhecidas pela regra e pelo
  elemento — o `nested-interactive` no `<svg>` do diagrama e o contraste da contagem do índice de nós (2,5:1). O
  axe não confere o contraste do texto dentro do desenho. O catálogo de features e a página
  [Arquitetura em diagramas](../arquitetura-em-diagramas/) são a alternativa em texto.
- **Tema**: o seletor de tema do site não chega ao visualizador, que segue o tema do sistema; o comparativo abre
  sempre no escuro.
- **Números e datas**: o archify escreve os números dos eixos em formato inglês; a cascata de tempos usa segundos
  inteiros, e a linha do tempo traz a data dd/mm/aaaa no texto de cada marco.
- **Completude**: o mapa garante um piso — toda peça que um painel registra e toda classe de `app/` fora de
  `app/Filament` entrega uma feature do catálogo ou está numa lista com o motivo —, e não garante que toda
  ligação do código esteja desenhada.
- **Links profundos** do visualizador (`#focus=`) não são oferecidos aqui.

## Como é conferido

- `tests/Kit/MapaInterativoTest.php` e `tests/Tenancy/MapaInterativoTenancyTest.php` confrontam, em todo PR, o
  modelo e os diagramas com o registro vivo dos painéis, com o código e com o catálogo.
- `site/verifica-mapa.mjs` abre a amostra num navegador real, no CI do site, antes de publicar.
- Como manter o mapa: [Desenvolvendo o kit](../../operacao/desenvolvendo-o-kit/).
