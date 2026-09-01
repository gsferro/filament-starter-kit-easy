# Requisito — Botão "Voltar ao topo" em todos os painéis

## Fonte

- **Origem**: mensagem do usuário no chat, invocando `/feature-wiki`
- **Data**: 2026-08-18
- **Autor / solicitante**: Guilherme Ferro (mantenedor do kit)
- **Fidelidade**: alta (texto escrito, colado verbatim abaixo)

## Texto Original

<!-- IMUTÁVEL. Não editar, não corrigir ortografia, não resumir, não reordenar. -->

> **Anonimizado em 2026-09-01.** O nome da organização real que aparecia no texto original e as
> URLs dela foram substituídos por `Acme` / `exemplo.test` a pedido do solicitante — o
> repositório é público. Só o identificador mudou; nenhuma cláusula, número ou ordem foi
> alterada, e a decomposição em `RQ-##` continua a mesma.

> veja novamente o pacote: "D:\PROJECTS\<interno>\Mini PFF\mini-pff" e veja o botão de "Voltar ao topo" que foi implementado direto no codigo (tem uma anotação sobre o não uso de um pacote que faz isso) e traga para o starter-kit como padrão em todos os panels e pages

## Decomposição em Cláusulas

| ID | Cláusula | Trecho literal de origem | Tipo |
|----|----------|--------------------------|------|
| RQ-01 | Ler o botão "Voltar ao topo" implementado no `mini-pff` | "veja o botão de \"Voltar ao topo\" que foi implementado direto no codigo" | funcional |
| RQ-02 | Ler a anotação sobre **não** usar um pacote que faz isso | "tem uma anotação sobre o não uso de um pacote que faz isso" | funcional |
| RQ-03 | Trazer o botão para o starter-kit | "traga para o starter-kit" | funcional |
| RQ-04 | O botão deve ser **padrão em todos os painéis** | "como padrão em todos os panels" | funcional |
| RQ-05 | O botão deve valer em **todas as páginas** | "e pages" | funcional |
| RQ-06 | Implementar direto no código, **sem o pacote** | "implementado direto no codigo (tem uma anotação sobre o não uso de um pacote...)" | restrição |

## Ambiguidades e Perguntas Abertas

- **RQ-04 + RQ-05 — "todos os panels e pages" inclui as telas de vendor?** O kit tem telas que vêm
  de plugin (Shield, auditoria, log de autenticação, monitor de filas, releases do Composer,
  exceções, trilha de e-mail, lixeira) e que não podem receber trait nem edição.
  - **Assumido**: **sim, inclui**. É justamente por isso que a implementação é um render hook
    global e não um trait por página — o hook alcança tela de vendor, trait não. Essa é a mesma
    razão que fez o `mini-pff` recusar o pacote.
  - **Se negado**: se a intenção fosse só as telas próprias, a implementação seria a mesma. Não há
    cenário em que a resposta mude o código.

- **RQ-06 — "sem o pacote" é restrição ou constatação?** O texto descreve o que o `mini-pff` fez.
  - **Assumido**: é **restrição** para esta entrega. A análise do `mini-pff` (código-fonte do
    pacote lido inteiro) e a varredura de plugins do próprio kit chegaram, por caminhos
    independentes, à mesma conclusão — o pacote não faz o que o pedido descreve. Ver ADR-01.
  - **Se negado**: nenhuma consequência prática; instalar o pacote não entregaria RQ-03 a RQ-05.

## Fora de Escopo (declarado)

- **Auto-scroll na troca de página da paginação.** É o que o pacote `gboquizosanchez/filament-scroll-to-top`
  de fato faz, e é outra coisa: gatilho automático, sem botão. O `mini-pff` deixou como passo
  opcional. Aqui fica fora — o pedido é o botão.
- Alterar a posição ou o comportamento do widget de chat do `/app`.
- Botão de "ir para o fim" ou navegação por âncora.
