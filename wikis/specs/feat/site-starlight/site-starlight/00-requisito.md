# Requisito — Migrar o site de documentação para Astro Starlight

## Fonte

- **Origem**: conversa no chat, em quatro mensagens, mais duas respostas a pergunta fechada
- **Data**: 2026-09-19
- **Autor / solicitante**: gsferro
- **Fidelidade**: **alta para as mensagens escritas** (transcritas verbatim abaixo) · **baixa para
  a intenção por trás de "questão do idioma"**, que é a única frase do conjunto sem objeto
  explícito — ver `## Ambiguidades`

## Texto Original

<!-- IMUTÁVEL. Não editar, não corrigir ortografia, não resumir, não reordenar. -->

**Mensagem 1** — o pedido que abre tudo:

> eu acho o site com o "Jekyll" bem simples e feio. é possivel procurarmos um template mais moderno
> e com um UX bonito?

**Resposta à pergunta fechada sobre o caminho** (opções apresentadas: spike numa prévia · migrar
direto · repaginar o just-the-docs · VitePress):

> Spike do Starlight numa prévia

**Mensagem 2** — depois de ver o spike:

> - testa o VitePress lado a lado para comparar
> - mas esse starlight ficou bom, so algumas cores escuras que não ficou tão legal e valeria uma
> confere usando o mcp do playwrite para voce entender e corrigir

**Mensagem 3** — a decisão:

> - gostei mais do Starlight. o vite é bom também, deixe ele comentando como 2 opção caso algo
> aconteça, mas vamos seguir com o melhor: Starlight.
> - atenção a questão do idioma

**Resposta à pergunta fechada sobre os slugs em inglês** (opções: traduzir · manter em português ·
traduzir só os diretórios), dada **depois** de o agente medir que traduzir quebra o casamento de
tradução do Starlight:

> mantém o slug em português e segue com os redirects

**Resposta à pergunta fechada sobre as URLs antigas** (opções: redirects · sem redirects):

> Redirects das URLs antigas (Recomendado)

**Mensagem 4** — a ordem de executar:

> sim use o /feature-wiki e implemente

## Decomposição em Cláusulas

| ID | Cláusula | Trecho literal de origem | Tipo |
|----|----------|--------------------------|------|
| RQ-01 | O site passa a ter aparência **moderna** e UX melhor que a atual | "é possivel procurarmos um template mais moderno e com um UX bonito?" | funcional |
| RQ-02 | O **VitePress** é testado lado a lado com o Starlight, para comparação | "testa o VitePress lado a lado para comparar" | restrição de processo |
| RQ-03 | As **cores escuras** que não ficaram boas são corrigidas, **conferidas em navegador real** | "so algumas cores escuras que não ficou tão legal e valeria uma confere usando o mcp do playwrite para voce entender e corrigir" | funcional |
| RQ-04 | O gerador do site publicado passa a ser o **Starlight** | "vamos seguir com o melhor: Starlight" | restrição |
| RQ-05 | O **VitePress fica documentado como segunda opção**, para o caso de algo acontecer com o Starlight | "deixe ele comentando como 2 opção caso algo aconteça" | funcional |
| RQ-06 | O site trata **corretamente os dois idiomas** | "atenção a questão do idioma" | funcional |
| RQ-07 | Os **slugs continuam em português** nos dois idiomas | "mantém o slug em português" | restrição |
| RQ-08 | As **URLs antigas** do Jekyll redirecionam para as novas | "segue com os redirects" · "Redirects das URLs antigas (Recomendado)" | funcional |
| RQ-09 | A migração é **implementada**, não apenas planejada | "sim use o /feature-wiki e implemente" | restrição |

## Ambiguidades e Perguntas Abertas

- **RQ-01 não é testável como está.** *"Moderno"* e *"UX bonito"* não têm oráculo mecânico, e
  nenhum teste pode afirmá-los.
  - **Assumido**: o oráculo de RQ-01 é o **julgamento do solicitante sobre o spike**, já emitido —
    *"esse starlight ficou bom"* e *"gostei mais do Starlight"*. O que os testes cobrem são as
    propriedades **falsificáveis** que sustentam a aparência: contraste conferido nos dois temas,
    ausência de título duplicado, ausência de entrada duplicada na navegação.
  - **Se negado**: RQ-01 volta a ser aberto e a entrega precisa de um critério visual declarado
    antes de qualquer CSS novo.

- **RQ-06 chegou sem objeto: "atenção a questão do idioma" não diz *qual* questão.** É a única
  frase do requisito sem referente explícito.
  - **Assumido**: o solicitante pedia atenção ao tratamento de i18n como um todo. O agente mediu
    as quatro propriedades que podiam estar erradas e reportou: `lang` por locale ✅, busca
    separada por idioma ✅, strings de UI ✅, rótulos de navegação ✅, **slugs do inglês em
    português ❌**. O solicitante respondeu à quinta com "mantém o slug em português", o que
    **confirma** que era isso — ou ao menos aceita o tratamento dado.
  - **Se negado**: se "questão do idioma" era outra coisa (por exemplo, traduzir o conteúdo que
    hoje diverge entre pt e en), RQ-06 muda de escopo e vira wiki própria — não é ajuste deste
    plano.

- **Resolvida durante a captura, e vale registrar porque inverteu uma decisão do solicitante**: a
  pergunta fechada sobre slugs foi feita **antes** de o agente medir o comportamento, e o
  solicitante escolheu *"Traduzir os slugs do inglês"*. A medição posterior (renomear uma página
  real e observar o build) mostrou que traduzir produz página-fantasma em português sob `/en/` e
  quebra o seletor de idioma. O achado foi reportado, e o solicitante **reverteu** para "mantém o
  slug em português". RQ-07 registra a decisão final; a primeira não vale.

## Fora de Escopo (declarado)

- **Traduzir ou reconciliar o conteúdo** que diverge entre as árvores pt e en — a paridade
  estrutural continua coberta pelos testes herdados; o texto não é tocado.
- **Reescrever qualquer página de documentação.** A migração é de gerador, não de conteúdo:
  `RQ-03` da wiki ancestral (*"o conteúdo migrado é o dos READMEs"*) continua valendo.
- **Versionamento de documentação** (doc por versão do kit). Não foi pedido.
- **Busca hospedada** (Algolia ou equivalente). A busca local do Starlight atende.
- **Domínio próprio** para o site.
