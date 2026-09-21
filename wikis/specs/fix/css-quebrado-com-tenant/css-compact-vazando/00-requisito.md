# Requisito — CSS da aplicação quebrado na v0.37.0 com tenant ativo

## Fonte

- **Origem**: relato do usuário no chat, invocando a skill `feature-wiki`
- **Data**: 2026-09-21
- **Autor / solicitante**: guilhermeferro@fiotec.fiocruz.br (mantenedor do kit)
- **Fidelidade**: **alta** — texto escrito, colado diretamente
- **Natureza**: **defeito em versão publicada** (v0.37.0, tag existente, release no ar)

## Texto Original

<!-- IMUTÁVEL. Não editar, não corrigir ortografia, não resumir, não reordenar. -->

> eu rodei localmente a versão v0.37.0 com o tenant ativo e o CSS de toda a aplicação esta quebrado. parece que tentou adicionar um efeito de compact mas ficou pessimo.
> - rodei em outra maquina para testar o pacote novo de Header no User e no Tenant e vi essa quebra do CSS
> - rode voce localmente, na pasta: "C:\PROJECTS\PACOTES\FILAMENTS\STARTER-KIT-EASY\TESTES-KIT" fazendo a instalação e rodando a aplicação
> - use o mcp do playwrite para navegar
> - faça uma revisão completa dos ultimos commits e das alterações nos arquivos de css para entender o que quebrou e o porque. Deixe tudo muito bem documentado e crie rules para evitar que isso aconteça novamente
> - use subagents para rodar em paralelo
> - use blueprint para entender como o filament funciona
> - crie uma worktree para rodar em paralelo com a outra wiki e faça os commits indivudalizados para cada wiki, quando corrigir, já faça o PR + merge na main + tag + release de correção

## Decomposição em Cláusulas

| ID | Cláusula | Trecho literal de origem | Tipo |
|----|----------|--------------------------|------|
| RQ-01 | Na v0.37.0, **com multi-tenancy ativo**, o CSS da aplicação inteira está quebrado | "rodei localmente a versão v0.37.0 com o tenant ativo e o CSS de toda a aplicação esta quebrado" | defeito |
| RQ-02 | A aparência da quebra é de um **efeito "compact" indesejado** | "parece que tentou adicionar um efeito de compact mas ficou pessimo" | sintoma |
| RQ-03 | A quebra foi observada ao exercitar o **pacote novo de Header no User e no Tenant** | "rodei em outra maquina para testar o pacote novo de Header no User e no Tenant e vi essa quebra do CSS" | contexto |
| RQ-04 | A quebra deve ser **reproduzida localmente**, instalando e rodando a aplicação em `TESTES-KIT` | "rode voce localmente, na pasta: \"...\\TESTES-KIT\" fazendo a instalação e rodando a aplicação" | restrição de método |
| RQ-05 | A navegação da aplicação é feita por **browser automatizado** | "use o mcp do playwrite para navegar" | restrição de método |
| RQ-06 | Revisão **completa** dos últimos commits e das alterações nos arquivos de CSS, explicando **o que** quebrou e **por quê** | "faça uma revisão completa dos ultimos commits e das alterações nos arquivos de css para entender o que quebrou e o porque" | funcional |
| RQ-07 | O diagnóstico fica **muito bem documentado** | "Deixe tudo muito bem documentado" | não-funcional |
| RQ-08 | São criadas **rules** que impeçam a reincidência | "crie rules para evitar que isso aconteça novamente" | não-funcional |
| RQ-09 | O CSS volta a ficar correto — a correção é entregue | "quando corrigir" (pressupõe a correção como entrega) | funcional |

> **RQ-09 é a única cláusula de correção.** As demais governam método (04, 05), diagnóstico
> (06, 07) e prevenção (08). Uma correção que conserte a tela e não produza o diagnóstico e as
> rules **não atende o requisito** — e é exatamente o tipo de omissão que o quality gate procura.

## Instruções de Processo (não são RQ do produto)

| ID | Instrução | Trecho literal |
|----|-----------|----------------|
| PR-01 | Usar subagents em paralelo | "use subagents para rodar em paralelo" |
| PR-02 | Usar "blueprint" para entender o funcionamento do Filament | "use blueprint para entender como o filament funciona" |
| PR-03 | Worktree separada, rodando em paralelo com a outra wiki | "crie uma worktree para rodar em paralelo com a outra wiki" |
| PR-04 | Commits individualizados por wiki | "faça os commits indivudalizados para cada wiki" |
| PR-05 | Ao corrigir: PR + merge na main + tag + **release de correção** | "quando corrigir, já faça o PR + merge na main + tag + release de correção" |

> **PR-05 é autorização explícita** para merge, tag e release desta wiki — diferente da wiki
> `feat/kit-install-host-local`, onde o usuário exigiu aprovação prévia. Registrado aqui porque
> a assimetria entre as duas é deliberada e não deve ser "uniformizada" por conveniência.

## Ambiguidades e Perguntas Abertas

- **RQ-01 — "o CSS de toda a aplicação"**: "toda" é literal (todos os três painéis: `app`,
  `admin`, `infra`) ou apenas o painel `app`, que é o que tem tenancy? A distinção decide o
  escopo da reprodução e dos cenários de teste.
  **Pergunta em aberto; a reprodução em `TESTES-KIT` responde empiricamente.**

- **RQ-01 — "com o tenant ativo"**: o tenant é *condição necessária* da quebra, ou apenas o
  cenário em que o usuário a viu? Se o CSS quebrar também sem tenancy, o recorte do defeito muda
  e a rule a escrever é outra.
  **Falsificável: reproduzir nos dois modos. Está previsto nos casos de teste.**

- **RQ-02 — "efeito de compact"**: é descrição de **sintoma visual** pelo usuário, não
  identificação de causa. Não assumir que existe literalmente uma feature "compact" no código —
  a investigação precisa confirmar ou refutar. Tratar como pista, não como diagnóstico.

- **RQ-05 — MCP do Playwright**: **não há servidor MCP do Playwright configurado nesta sessão**
  (conferido: não consta na lista de servidores MCP). **Decidido com o usuário em 2026-09-21**:
  usar o **pacote npm `playwright`**, que entrega a mesma capacidade de navegação e ainda permite
  ler estilo computado e capturar screenshot. A cláusula é atendida pelo meio, não pela ferramenta
  nomeada — desvio registrado aqui porque o requisito nomeia a ferramenta.

- **PR-02 — "blueprint"**: o termo não foi definido pelo usuário. Há menções a "Blueprint" em
  `.ai/rules/filament.md` e `.ai/rules/resources.md` ("auditoria de aderência ao Blueprint F-01"),
  sugerindo um documento interno do projeto.
  **Pergunta aberta**: é esse documento interno, ou a ferramenta externa `laravel-shift/blueprint`?
  Enquanto não resolvido, a compreensão do Filament vem da `search-docs` e do vendor.

- **RQ-08 — quantas rules**: a skill `feature-wiki` impõe **teto de 3 candidatos a rule por
  feature**, e exige aprovação explícita do usuário antes de gravar. "Crie rules" no plural será
  atendido dentro desse teto.

## Fora de Escopo (declarado)

- Redesenhar o tema visual do kit — o pedido é **consertar a quebra**, não reestilizar
- Corrigir CSS de pacotes de terceiros **no upstream deles** (o kit pode isolar/neutralizar
  localmente; abrir PR em pacote de terceiro é outra entrega)
- A wiki `feat/kit-install-host-local`, que roda em paralelo e tem requisito próprio
