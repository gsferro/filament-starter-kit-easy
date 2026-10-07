# Requisito — Rodapé: desfazer a unificação e manter os dois rodapés separados

## Fonte

- **Origem**: pedido do solicitante no chat (invocação de `/feature-wiki`), em 2026-10-07, com uma segunda invocação idêntica acrescentando "crie branchs separadas e rode em paralelo usando subagentes ou worktree"
- **Data**: 2026-10-07
- **Autor / solicitante**: gsferro (mantenedor do kit)
- **Fidelidade**: alta (texto escrito no chat, copiado sem correção)

## Texto Original

<!-- IMUTÁVEL. Não editar, não corrigir ortografia, não resumir, não reordenar. -->

> vamos revisar a parte da unificação do rodape.
>
> * tem a opção de colocar o rodape abaixo das telas de login
> * e tem a opção que já adiciona automaticamente o rodape com o nome da aplicação
> * foi pedido para unifcar, porem não ficou bom, melhor manter separado
> * deixe a parte do rodape das telas de login como estava e o rodape automaticato como esta, ao inves de juntar ambos no rodape (footer)

Da segunda invocação, o trecho que vale para as duas features do pedido:

> crie branchs separadas e rode em paralelo usando subagentes ou worktree

## Decomposição em Cláusulas

<!-- Derivada e revisável. Estado: fechada · aberta — Qn · substituída por RQ-nn (Adendo N) · decomposta em RQ-nn, RQ-mm -->

| ID | Cláusula | Trecho literal de origem | Tipo | Estado |
|----|----------|--------------------------|------|--------|
| RQ-01 | Os dois rodapés voltam a ser separados: o recado das telas de login e o rodapé automático com o nome da aplicação deixam de ser compostos num único footer. | "foi pedido para unifcar, porem não ficou bom, melhor manter separado" · "ao inves de juntar ambos no rodape (footer)" | funcional | fechada |
| RQ-02 | O rodapé das telas de login volta a ser **como estava** antes da unificação: o recado configurável (Markdown) renderizado abaixo do formulário de login, no lugar e com a composição que tinha, e não no footer da página. | "deixe a parte do rodape das telas de login como estava" · "tem a opção de colocar o rodape abaixo das telas de login" | funcional | fechada |
| RQ-03 | O rodapé automático com o nome da aplicação fica **como está** hoje: a assinatura (`©`, ano, nome, versões) continua existindo, composta no mesmo lugar e com a mesma visibilidade atual. | "deixe … o rodape automaticato como esta" · "a opção que já adiciona automaticamente o rodape com o nome da aplicação" | funcional | fechada |
| RQ-04 | A revisão da unificação é feita como feature própria, em branch separada da outra feature pedida no mesmo chat, e as duas correm em paralelo. | "vamos revisar a parte da unificação do rodape" · "crie branchs separadas e rode em paralelo usando subagentes ou worktree" | restrição (processo) | fechada |

## Perguntas ao Solicitante

<!-- Raia requisito: só o solicitante responde. Enquanto aberta, a RQ afetada fica `aberta — Qn`
     e nenhum passo do 01 a implementa. A resposta entra como Adendo com fonte. -->

| ID | Pergunta | Afeta | Recomendação | Estado |
|----|----------|-------|--------------|--------|
| Q1 | "Como estava" para o recado do login é o estado anterior à unificação (v0.38.x): renderizado **dentro do cartão do formulário**, logo abaixo dos botões de login (inclusive os sociais), e só nas telas de login. Confirma que é esse o "como estava", e não outro ponto abaixo do cartão? | RQ-02 | ➡️ sim, o estado de antes da unificação, que está no histórico do git; é o único "como estava" verificável. Vira P-01; o PR a destaca para confirmação. | retirada — coberta por P-01 (confirmar no PR) |
| Q2 | O rodapé automático "como está" hoje aparece também nas telas de login, para o visitante (`©` e nome, sem versão). Com o recado de volta ao formulário, a assinatura continua aparecendo nas telas de login, ou ela volta a ser só para quem está autenticado, como era antes? | RQ-03 | ➡️ continua como está (aparece para o visitante): "como está" é literal e a assinatura não é o que o pedido quer desfazer. Vira P-02; o PR a destaca para confirmação. | retirada — coberta por P-02 (confirmar no PR) |

## Premissas

<!-- Revisável. O que a feature assume sem que o solicitante tenha escrito. -->

| ID | Premissa | Origem | Data | Afeta | Estado |
|----|----------|--------|------|-------|--------|
| P-01 | "Como estava" é o estado do recado do login imediatamente anterior ao commit da unificação, recuperado do histórico: ponto de extensão abaixo do formulário de login, dentro do cartão, depois dos botões, só nas telas de login; a assinatura automática não entra nesse bloco. | step 4 — entrevista (Q1) | 2026-10-07 | RQ-02 | vigente |
| P-02 | A assinatura automática não muda em nada: mesma composição, mesmo lugar, mesma visibilidade (inclusive nas telas de login, para o visitante, sem versão). Só o recado sai dela. | step 4 — entrevista (Q2) | 2026-10-07 | RQ-03 | vigente |
| P-03 | O que a unificação trouxe e **não** é "juntar os dois no footer" fica: o prefixo `v` do campo de versão, o `©` e o nome na assinatura, os marcos de acessibilidade do footer, o CSS da dobra que serve à assinatura. A reversão é do acoplamento, não da release inteira. | step 3 — leitura do commit da unificação | 2026-10-07 | RQ-01, RQ-03 | vigente |
| P-04 | A opção de configuração do recado (a chave de ambiente e o campo Markdown da aba Login) continua a mesma; só o lugar onde o texto aparece muda. | step 3 | 2026-10-07 | RQ-02 | vigente |
| P-05 | O recado volta ao cartão com a tag `<aside>` que a `rodape-coerente` lhe deu (ADR-09 dela), e não com o `<div>` de antes de `bfe9a8d`: a posição é o que o pedido desfaz; o landmark é marco de acessibilidade medido e fica (P-03). Origem: step 5 — RD-04, 2026-10-07. | step 5 — RD-04 | 2026-10-07 | RQ-02 | vigente |

## Fora de Escopo (declarado)

- Mudar o conteúdo, o Markdown ou a configuração do recado do login.
- Mudar a composição, o `©`, o ano, as versões ou a visibilidade da assinatura automática.
- Tag/release: não pedida nesta feature.
- A outra feature do mesmo pedido (logo escura da organização): wiki e branch próprias.
