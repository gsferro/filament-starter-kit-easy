# Requisito — Débitos G, H, J e K da aparência das telas de acesso

## Fonte

- **Origem**: pedido do mantenedor no chat em 2026-11-16 e o `06-relatorio-qa.md` da wiki `wikis/specs/feat/aparencia-das-telas-de-acesso/tela-de-login-personalizavel/` (ciclo 3 dos Adendos 2 e 3, ciclo 1 do Adendo 4), seção *Não Verificado*, que define os quatro débitos
- **Data**: 2026-11-18
- **Autor / solicitante**: mantenedor do kit
- **Fidelidade**: alta (texto escrito)

## Texto Original

<!-- IMUTÁVEL. Não editar, não corrigir ortografia, não resumir, não reordenar. -->

**Pedido no chat (2026-11-16):**

> abra uma /feature-wiki  para resolver de 1 a 4 que são as pendencias para depois que tudo estiver resolvido, vamos para novas melhorias

(o item 4 da lista oferecida era "Débitos G, H, J e K da wiki `aparencia-das-telas-de-acesso/tela-de-login-personalizavel`.")

**`06-relatorio-qa.md` da wiki de origem, seção *Não Verificado*:**

> - G nível 3, e H com axe, teclado e "reduzir movimento" no navegador: o Playwright MCP está indisponível, e nenhum CT-B usa `assertNoAccessibilityIssues` nem `reducedMotion` para a transição.
> - J: a suíte inteira (Kit, Tenancy e Browser) e o `--tia` não rodaram, por restrição de memória do orquestrador. A medição está prevista para o commit final.
> - J / RQ-08 / RQ-17: os cenários 1–4 sobre o commit final (`f1`..`f4`) ainda não rodaram. É o QA-28, e bloqueia o merge.
> - K2, `--mutate`: as ferramentas existem (já provado no ciclo 2). Não rodou por restrição de memória e porque o plugin não é confiável no Windows. A amostragem manual exigiria editar a árvore.
> - Revisão adversarial dos CT do Adendo 4: não foi feita. O perfil da área é "padrão" (P×I 4), então ela não é obrigatória, mas o `03` não declara isso.

## Decomposição em Cláusulas

| ID | Cláusula | Trecho literal de origem | Tipo | Estado |
|----|----------|--------------------------|------|--------|
| RQ-01 | (G) As telas de acesso no painel de marca e no clássico são conferidas visualmente no tema escuro (nível 3: captura e leitura) | "G nível 3" | não-funcional | fechada |
| RQ-02 | (H) As telas de acesso passam no axe (`assertNoAccessibilityIssues`) em navegador, no clássico e no painel de marca | "H com axe … nenhum CT-B usa `assertNoAccessibilityIssues`" | não-funcional | fechada |
| RQ-03 | (H) O percurso por teclado nas telas de acesso é verificado em navegador | "H com … teclado" | não-funcional | fechada |
| RQ-04 | (H) Com "reduzir movimento" no sistema, a transição entre as telas (Adendo 4) não anima | "nem `reducedMotion` para a transição" | funcional | fechada |
| RQ-05 | (J) A suíte inteira (Kit, Tenancy e Browser) e o `--tia` rodam sobre um commit que contém a feature | "a suíte inteira (Kit, Tenancy e Browser) e o `--tia` não rodaram" | não-funcional | fechada |
| RQ-06 | (J) Os cenários 1–4 rodam sobre um commit que contém a feature | "os cenários 1–4 sobre o commit final (`f1`..`f4`) ainda não rodaram" | não-funcional | fechada |
| RQ-07 | (K) A adequação da suíte é provada por mutação — por amostragem manual, já que o plugin não é confiável no Windows | "K2, `--mutate` … A amostragem manual exigiria editar a árvore" | não-funcional | fechada |
| RQ-08 | (K) Os casos de teste do Adendo 4 passam por revisão adversarial | "Revisão adversarial dos CT do Adendo 4: não foi feita" | não-funcional | fechada |

## Perguntas ao Solicitante

| ID | Pergunta | Afeta | Recomendação | Estado |
|----|----------|-------|--------------|--------|
| — | nenhuma: os débitos são verificações, e o comportamento esperado já está no `00` da wiki de origem | — | — | — |

## Premissas

| ID | Premissa | Origem | Data | Afeta | Estado |
|----|----------|--------|------|-------|--------|
| P-01 | Débito que já foi pago por evidência posterior (rodada registrada com comando e saída) fecha com a referência a ela, sem rodar de novo — mas a suíte é rodada de novo sobre o commit desta entrega | step 3 — a rodada `f1`..`f4` de 2026-11-15 e as suítes das v0.54.0/v0.55.0 contêm a feature *(alterado em 2026-11-18: QA-01 do gate — a rodada `f1`..`f4` foi sobre `e267f909`, anterior às correções da v0.53.1; o RQ-06 fecha pela prova das duas rotas de entrega do arquivo novo, e os quatro cenários sobre a tag ficam como débito)* | 2026-11-18 | RQ-05, RQ-06 | vigente |
| P-02 | Achado de acessibilidade do axe nas telas do kit é corrigido nesta entrega; achado que vem só do vendor (Filament, auth-designer) é registrado com a evidência e fica como débito do vendor | step 4 — o mesmo critério do `tabindex="-1"` do alternador de tema, já registrado na wiki de origem | 2026-11-18 | RQ-02, RQ-03 | vigente |
| P-03 | A amostragem de mutação é manual: aplicar um mutante por vez nos arquivos da feature, rodar o CT que deveria matá-lo e voltar o arquivo (`.ai/rules/testes.md`, K2 no Windows) | step 3 — regra do projeto | 2026-11-18 | RQ-07 | vigente |
| P-04 | O resultado de cada débito é registrado também na wiki de origem (`03` → `## Débitos` e `## Quality Gate`), com a referência a esta | step 4 — o débito mora lá | 2026-11-18 | RQ-01..RQ-08 | vigente |

## Fora de Escopo (declarado)

- Mudar o comportamento das telas de acesso além do que um achado do axe exigir (P-02)
- Os débitos Q1 e Q12 da wiki de origem (perguntas ao solicitante), que não são G, H, J ou K
