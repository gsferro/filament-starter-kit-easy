# Requisito — Pacotes Laravel para o ecossistema de IA

## Fonte

- **Origem**: pedido do usuário no chat, invocando a skill `feature-wiki`
- **Data**: 2026-09-21
- **Autor / solicitante**: guilhermeferro@fiotec.fiocruz.br (mantenedor do kit)
- **Fidelidade**: **alta** — texto escrito, colado diretamente

## Texto Original

<!-- IMUTÁVEL. Não editar, não corrigir ortografia, não resumir, não reordenar. -->

> instalar novos pacotes do laravel para ajudar no uso do ecosistema de IAs:
> - necessário analisar profundamente e entender como cada um funciona e como implementa-lo no kit de forma eficiente e muito bem documentado
> - segue os links:
>    - https://github.com/laravel/vet
>    - https://github.com/laravel/pao
>    - https://github.com/laravel/moat
> - use subagents para rodar em paralelo
> - crie uma branch exclusiva para ele e depois de finalizado, abra o PR, aguarde o PR ser aprovado e faça o merge na main + tag + release
> - faça commits individualizados
> - veja se precisa criar rules para uso correto nesses novos pacotes

## Decomposição em Cláusulas

| ID | Cláusula | Trecho literal de origem | Tipo |
|----|----------|--------------------------|------|
| RQ-01 | Cada pacote é **analisado profundamente** — como funciona, mecanicamente | "necessário analisar profundamente e entender como cada um funciona" | não-funcional |
| RQ-02 | Fica decidido e registrado **como implementá-lo no kit de forma eficiente** | "e como implementa-lo no kit de forma eficiente" | funcional |
| RQ-03 | A implementação fica **muito bem documentada** | "e muito bem documentado" | não-funcional |
| RQ-04 | `laravel/vet` é avaliado e, se couber, instalado | "https://github.com/laravel/vet" | funcional |
| RQ-05 | `laravel/pao` é avaliado e, se couber, instalado | "https://github.com/laravel/pao" | funcional |
| RQ-06 | `laravel/moat` é avaliado e, se couber, adotado | "https://github.com/laravel/moat" | funcional |
| RQ-07 | É avaliado se são necessárias **rules** para o uso correto dos pacotes novos | "veja se precisa criar rules para uso correto nesses novos pacotes" | não-funcional |

> **RQ-04, RQ-05 e RQ-06 são condicionais.** O texto pede "instalar", mas o primeiro item pede
> **análise** antes — "analisar profundamente e entender **como** implementá-lo". Um pacote cuja
> análise conclua que ele não cabe no kit é atendido pela **decisão registrada**, não pela
> instalação forçada. A ADR é a entrega nesse caso.

## Instruções de Processo (não são RQ do produto)

| ID | Instrução | Trecho literal |
|----|-----------|----------------|
| PR-01 | Subagents rodando em paralelo | "use subagents para rodar em paralelo" |
| PR-02 | Branch exclusiva | "crie uma branch exclusiva para ele" |
| PR-03 | Commits individualizados | "faça commits individualizados" |
| PR-04 | Abrir PR, **aguardar aprovação**, então merge + tag + release | "abra o PR, aguarde o PR ser aprovado e faça o merge na main + tag + release" |

> **PR-04 tem gate de aprovação explícito**, igual ao da wiki `feat/kit-install-host-local`. O
> agente para no PR.

## Ambiguidades e Perguntas Abertas

- **RQ-06 — `laravel/moat` NÃO é um pacote PHP, e isso quebra a premissa do requisito.**
  O texto o agrupa com os outros dois sob "instalar novos pacotes do laravel". Confirmado na
  fonte (https://github.com/laravel/moat): é uma **CLI escrita em Rust**, instalada por
  **Homebrew** (`brew install laravel/moat/moat`), que faz **auditoria read-only de configuração
  de segurança do GitHub** (2FA, branch protection, secret scanning, permissões de workflow).
  Não entra em `composer.json`, não é dependência do projeto, e **não tem relação com IA** —
  diferente do enquadramento "para ajudar no uso do ecossistema de IAs".
  Três leituras possíveis, com custos muito diferentes:
  (a) documentar como ferramenta opcional do mantenedor;
  (b) integrar ao CI como passo de auditoria de segurança;
  (c) tirar de escopo.
  **DECIDIDO com o usuário em 2026-09-21: opção (a)** — o `moat` entra na **documentação do kit
  como ferramenta opcional do mantenedor**, não como dependência e não no CI. Zero peso para quem
  instala o kit. RQ-06 é atendido pela documentação e pela ADR, não por instalação.
  **Se negado**: RQ-06 muda de forma e o passo correspondente do PRD é refeito; nada mais na wiki
  depende dessa escolha.

- **RQ-04 — `laravel/vet` é um plugin do Composer, e o kit é distribuído por `create-project`.**
  Plugin que engancha em `install`/`update` roda na máquina de **quem instala o kit**. O projeto
  já foi mordido por dependência que não podia viajar no pacote (`.ai/rules/general.md`, o caso
  do `filament/blueprint`, com guarda em `tests/Kit/BlueprintForaDoPacoteTest.php`). Além disso o
  `vet` está em **beta**, com API declarada instável.
  **Investigação delegada a subagente; a ADR decide.**

- **RQ-05 — `laravel/pao` reformata a saída de ferramentas quando detecta um agente.**
  O kit tem 2.614 testes e workflows de CI. Se algum teste ou passo de workflow fizer asserção
  sobre o **texto** da saída, o `pao` pode torná-los instáveis **apenas quando um agente roda a
  suíte** — um modo de falha que não aparece para humano nenhum.
  **Investigação delegada a subagente; a ADR decide.**

- **Alcance dos pacotes PHP — DECIDIDO com o usuário em 2026-09-21.** `composer create-project`
  instala `require-dev` por **default**, então um pacote em `require-dev` **viaja para quem instala
  o kit**. A decisão é **por pacote, na ADR, com a evidência da investigação** — e não uma regra
  única para os dois. O `pao` e o `vet` têm perfis de risco diferentes: um reformata saída de
  ferramenta, o outro é plugin do Composer **em beta** que engancha no ciclo de install de
  terceiros.
  **Se negado**: as ADRs de alcance são refeitas; os passos de instalação do PRD mudam de forma.

- **RQ-02 — "de forma eficiente" não é testável como está.** Eficiente em quê: tokens consumidos
  pelo agente, tempo de execução, esforço de manutenção? Tratado como "o pacote entrega o valor
  que promete sem custo novo para quem instala o kit", e o que for medido vai para a ADR.

- **RQ-07 — teto de rules.** A skill `feature-wiki` impõe **no máximo 3 candidatos a rule por
  feature**, com aprovação explícita do usuário antes de gravar.

## Fora de Escopo (declarado)

- Adotar outros pacotes do ecossistema além dos três citados
- Reescrever a suíte de testes ou o CI para acomodar formato de saída novo (se um pacote exigir
  isso, a ADR recusa o pacote em vez de reescrever a fundação)
- Publicar qualquer coisa fora do repositório
