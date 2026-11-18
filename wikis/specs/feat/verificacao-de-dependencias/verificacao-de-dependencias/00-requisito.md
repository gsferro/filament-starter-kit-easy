# Requisito — Roadmap item 8.2: `composer-require-checker` + `composer-unused`

## Fonte

- **Origem**: `wikis/roadmap.md`, seção 8, linha 8.2, mais o pedido do mantenedor no chat em 2026-11-16 e as duas respostas dele às perguntas Q1–Q2 (ferramenta de perguntas do chat, 2026-11-18)
- **Data**: 2026-11-18
- **Autor / solicitante**: mantenedor do kit
- **Fidelidade**: alta (texto escrito no roadmap e respostas escolhidas por escrito)

## Texto Original

<!-- IMUTÁVEL. Não editar, não corrigir ortografia, não resumir, não reordenar. -->

**Pedido no chat (2026-11-16):**

> abra uma /feature-wiki  para resolver de 1 a 4 que são as pendencias para depois que tudo estiver resolvido, vamos para novas melhorias

(o item 3 da lista oferecida era "composer-require-checker / composer-unused — Item 8.2 do roadmap.")

**`wikis/roadmap.md`, item 8.2:**

> | 8.2 | **`composer-require-checker` + `composer-unused`** | não instalados | respondem mecanicamente a *"das 58 dependências, quais são de fato usadas?"* — pergunta que a crítica externa fez e que hoje só tem a resposta *"não sabemos"*. Melhor razão valor/esforço da lista |

**Q1 — "Pendência 3 (roadmap 8.2) — composer-require-checker e composer-unused respondem "quais das dependências são de fato usadas". Como elas entram no kit?" — resposta escolhida:**

> require-dev no composer.json

(a opção dizia: "As duas viram dependências de desenvolvimento: viajam com todo `create-project` (inclusive para quem nunca vai usar) e o CI roda pelo vendor/bin.")

**Q2 — "Pendência 3 — o que acontece quando a verificação acha dependência não usada ou símbolo de pacote não declarado?" — resposta escolhida:**

> Só relatório

(a opção dizia: "Roda e publica o resultado, sem reprovar. Mais barato agora, mas a lista pode voltar a crescer sem ninguém ver.")

## Decomposição em Cláusulas

| ID | Cláusula | Trecho literal de origem | Tipo | Estado |
|----|----------|--------------------------|------|--------|
| RQ-01 | O kit passa a ter o `composer-unused`, que responde quais dependências declaradas não são usadas | "`composer-unused`" / "quais são de fato usadas?" | funcional | fechada |
| RQ-02 | O kit passa a ter o `composer-require-checker`, que responde quais símbolos o código usa de pacote não declarado | "`composer-require-checker`" | funcional | fechada |
| RQ-03 | As duas ferramentas entram como dependência de desenvolvimento (`require-dev`) | Q1: "require-dev no composer.json" | restrição | fechada |
| RQ-04 | O CI roda as duas ferramentas pelo `vendor/bin` | Q1: "o CI roda pelo vendor/bin" | funcional | fechada |
| RQ-05 | O resultado é publicado como relatório e não reprova o CI | Q2: "Só relatório" / "Roda e publica o resultado, sem reprovar" | restrição | fechada |
| RQ-06 | A pergunta "das dependências, quais são de fato usadas?" passa a ter resposta registrada, e não "não sabemos" | "pergunta que a crítica externa fez e que hoje só tem a resposta *"não sabemos"*" | funcional | fechada |

## Perguntas ao Solicitante

| ID | Pergunta | Afeta | Recomendação | Estado |
|----|----------|-------|--------------|--------|
| Q1 | Como as ferramentas entram no kit? | RQ-03, RQ-04 | só no CI, sem dependência | respondida no Texto Original (2026-11-18): `require-dev` |
| Q2 | Achado reprova o CI ou só relata? | RQ-05 | reprovar | respondida no Texto Original (2026-11-18): só relatório |
| Q3 | O `composer-require-checker` atual exige a extensão PHP `sodium` (por uma dependência dele) e limita o PHP a 8.4–8.5 em toda instalação COM dependências de desenvolvimento — o `create-project` padrão. Aceita esse pré-requisito, ou prefere outra via (ferramenta só no CI, por phar)? | RQ-03, P-08 | aceitar e documentar: a extensão vem ligada no Linux e no Docker; no Windows é uma linha do `php.ini`; quem instala com `--no-dev` não é afetado | aberta — premissa P-08 vigente até a resposta (step 11, QA-02 do quality gate) |

## Premissas

| ID | Premissa | Origem | Data | Afeta | Estado |
|----|----------|--------|------|-------|--------|
| P-01 | Quem desenvolve roda as duas ferramentas por um comando só do composer (`composer deps:check`), ao lado de `lint`, `types:check` e `filament:check` | step 3 — convenção dos scripts de qualidade do `composer.json` | 2026-11-18 | RQ-01, RQ-02 | vigente |
| P-02 | No CI, o relatório aparece no resumo do job (`GITHUB_STEP_SUMMARY`), e o job continua verde com achados | step 4 — "publica o resultado, sem reprovar" | 2026-11-18 | RQ-04, RQ-05 | vigente |
| P-03 | Pacote que a ferramenta não enxerga em uso, mas que o kit usa por outro caminho (config, rota, view, auto-discovery, comando) entra numa lista de exclusão versionada, cada um com o motivo; pacote realmente sem uso NÃO entra na lista | step 4 — sem isso o relatório acusa o que é falso e esconde o que é real | 2026-11-18 | RQ-05, RQ-06 | vigente |
| P-04 | A resposta de RQ-06 é uma triagem registrada na documentação de qualidade: cada pacote acusado, com o veredito (usado por outro caminho, sem uso, ou uso direto de dependência só transitiva) e a evidência | step 4 — "respondem mecanicamente" e "hoje só tem a resposta não sabemos" | 2026-11-18 | RQ-06 | vigente |
| P-05 | Esta entrega não remove nem acrescenta dependência de produção: o que a triagem achar (pacote sem uso, dependência transitiva usada direto, extensão PHP não declarada) fica registrado como recomendação, para decisão do mantenedor | step 4 — `CLAUDE.md`: dependência só muda com aprovação; Q2 escolheu "só relatório" | 2026-11-18 | RQ-05, RQ-06 | vigente |
| P-07 | O `composer deps:check` fica FORA do `composer test` (o gate local que reprova): um gate que reprova com achado contradiria o "só relatório" de Q2 para quem desenvolve | step 7 — Q?2 da `feature-test-design`; decorre da resposta a Q2 | 2026-11-18 | P-01, RQ-05 | vigente |
| P-08 | A instalação com dependências de desenvolvimento passa a exigir a extensão PHP `sodium` e PHP 8.4 ou 8.5, documentado no README, no CHANGELOG, na página de atualização e no roteiro. A versão do `composer-require-checker` sem essa exigência (4.17) foi medida e recusada: rebaixaria o `symfony/console` de produção de 8.1 para 7.4, contra P-05 | step 9 — CR-01 do `/code-review`; registrada como premissa no step 11 (QA-02) | 2026-11-18 | RQ-03, P-05 | vigente |
| P-06 | Os arquivos de configuração das ferramentas viajam com o kit (instalação e `kit:update`), como os de Pint, PHPStan e Rector | step 3 — os irmãos `pint.json`, `phpstan.neon`, `rector.php` | 2026-11-18 | RQ-01, RQ-02, RQ-03 | vigente |

## Fora de Escopo (declarado)

- Remover, trocar ou declarar dependências de produção apontadas pelo relatório (P-05)
- Reprovar o CI com achado (Q2)
