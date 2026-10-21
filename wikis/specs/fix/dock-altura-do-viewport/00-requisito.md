# Requisito — Botão do dock nasce e some se não for movido

## Fonte

- **Origem**: pedido do solicitante no chat, com a resposta às perguntas de reprodução e as medições do navegador dele
- **Data**: 2026-10-20
- **Autor / solicitante**: gsferro (desenvolvedor e solicitante)
- **Fidelidade**: alta (texto escrito)

## Texto Original

<!-- IMUTÁVEL. Não editar, não corrigir ortografia, não resumir, não reordenar. -->

> - o botão do dock ainda esta nascendo e "sumindo" se não for movido. precisamos entender o motivo. use o playwrite para simular o "sumiço" da tela visivel para entender e resolver de vez o erro

> (resposta às perguntas de reprodução) "em todas as telas, no celular ela funciona corretamenta. eu abro a tela (sem qual for o painel), ele aparece. passa poucos segundos, ele some. se eu consigo clicar nele antes de sumir (que parece que vai para baixo da tela visivel) eu o movo para cima, e depois movimento livremente pela tela e ai ele não some mais"

> "na janela anonima o "sumiço" não acontece. eu abro e o botão fica no lugar inicial"

## Decomposição em Cláusulas

| ID | Cláusula | Trecho literal de origem | Tipo | Estado |
|----|----------|--------------------------|------|--------|
| RQ-01 | O botão do dock, na posição inicial (sem ter sido movido), fica dentro da área visível da tela depois de aparecer — não desce para fora dela | "o botão do dock ainda esta nascendo e "sumindo" se não for movido" · "que parece que vai para baixo da tela visivel" | funcional | fechada |
| RQ-02 | Vale em todas as telas e painéis no desktop | "em todas as telas" · "sem qual for o painel" | restrição | fechada |
| RQ-03 | O motivo é identificado e a correção é definitiva, reproduzida por simulação no navegador | "precisamos entender o motivo. use o playwrite para simular o "sumiço" … resolver de vez o erro" | restrição | fechada |

## Perguntas ao Solicitante

Nenhuma aberta. As duas perguntas de reprodução (quando some e onde) foram respondidas no chat e estão no Texto Original.

## Premissas

| ID | Premissa | Origem | Data | Afeta | Estado |
|----|----------|--------|------|-------|--------|
| P-01 | A posição já lembrada (dock movido) continua sendo respeitada; a correção muda só a altura usada no cálculo | leitura do pedido ("depois movimento livremente pela tela e ai ele não some mais") | 2026-10-20 | RQ-01 | vigente |

## Fora de Escopo (declarado)

- O comportamento no celular, que o solicitante declarou correto
- Corrigir o pacote no próprio repositório dele — vai como issue e PR upstream (HoceineEl/filament-quick-action-dock#2 e #3), e a correção do kit fica até o pacote publicar a sua
