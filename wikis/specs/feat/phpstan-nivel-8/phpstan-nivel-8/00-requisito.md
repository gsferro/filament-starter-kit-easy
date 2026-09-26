# Requisito — Varredura das últimas rodadas e PHPStan no level 8

> **Fonte da verdade.** Captura verbatim do pedido, antes de qualquer interpretação.

## Fonte

- **Origem**: mensagem do mantenedor no chat do Claude Code
- **Data**: 2026-09-26
- **Autor / solicitante**: mantenedor do kit
- **Fidelidade**: alta (texto escrito)

## Texto Original

<!-- IMUTÁVEL. Não editar, não corrigir ortografia, não resumir, não reordenar. -->

> - faça uma varredida nas ultimas rodadas de alterações, veja se ficou algo pendente tanto no codigo, quanto nos testes.
> - garanta que as regras de validação, teste e qualidade do projeto estejam as melhores possiveis
> - veja se ficou algum PR aberto e feche-o aplicando o merge na main + tag
> - rode a analise de forma criteriosa e rigida, crie um /feature-wiki se necessário

## Decomposição em Cláusulas

| ID | Cláusula | Trecho literal de origem | Tipo |
|----|----------|--------------------------|------|
| RQ-01 | O que ficou pendente no **código** das últimas rodadas é identificado e resolvido ou declarado | "veja se ficou algo pendente tanto no codigo" | funcional |
| RQ-02 | O que ficou pendente nos **testes** das últimas rodadas é identificado e resolvido ou declarado | "quanto nos testes" | funcional |
| RQ-03 | As regras de **qualidade** (análise estática) sobem ao melhor degrau alcançável sem ruído | "garanta que as regras de validação, teste e qualidade do projeto estejam as melhores possiveis" | não-funcional |
| RQ-04 | A regra nova é **travada**: um retrocesso de nível reprova a suíte, não depende de memória | idem — "garanta" | restrição |
| RQ-05 | PR aberto é mergeado na `main` e a entrega recebe tag | "veja se ficou algum PR aberto e feche-o aplicando o merge na main + tag" | funcional |
| RQ-06 | A análise é criteriosa e rígida: cada correção trata a **causa**, não cala o analisador | "rode a analise de forma criteriosa e rigida" | restrição |

## O que a varredura achou (medido em 2026-09-26, antes desta wiki)

| Frente | Resultado | Comando |
|---|---|---|
| PRs abertos | **nenhum** | `gh pr list --state open` → `[]` |
| Branches remotas não mergeadas | **nenhuma** | `git branch -r --no-merged origin/main` → vazio |
| Commits na `main` sem tag | **3** — #107, #108, #109 | `git log v0.40.2..origin/main --oneline` |
| CI da `main` | verde nos três últimos commits | `gh run list --branch main` |
| Pint / PHPStan L7 / Filacheck | verde / 0 erros / 17 regras | `pint --test`, `phpstan analyse`, `filacheck` |
| Suíte Unit+Feature+Kit+Tenancy | **2.993 testes, 2.990 passaram, 3 pulados, 0 falhas** | `php artisan test --testsuite=Unit,Feature,Kit,Tenancy --parallel --compact` |
| Rector | sem nenhum set ligado | **decisão documentada** em `rector.php` e travada por `QualidadeDeCodigoTest` — não é pendência |
| Wiki `cobertura-de-testes` | **4 caixas abertas** em `03-progresso.md` | três delas estão feitas (presets em `ArquiteturaDoCodigoTest`, roadmap §8 escrito, CTs em `KitCoberturaTest`/`RecorteDaCoberturaTest`) — caixa desatualizada; a quarta (ponytail) é recusa declarada |
| Wiki `plumb-e-dividas-tecnicas` | **2 caixas abertas** na Verificação Final | "suíte a rodar" e "cenários a reconfirmar" — a evidência existe (#108 e a rodada de hoje), a caixa não foi fechada |
| PHPStan **level 8** | **48 erros em 16 arquivos** — o mesmo número medido em 2026-09-24 no roadmap §8.1 | `phpstan analyse --level=8` |

## Ambiguidades e Perguntas Abertas

- **RQ-03** — *"as melhores possíveis"* não diz qual degrau.
  - **Assumido**: **PHPStan level 8**. É o item 8.1 do `wikis/roadmap.md`, com número medido e
    remedido (48), e o único dos níveis adiados que é degrau e não precipício. Level 9 dá **474**
    erros e `max` **594** — o roadmap já os classifica como precipício. `declare(strict_types=1)`
    (roadmap 8.3) exige **decidir o lado**, não rodar uma ferramenta, e fica fora.
  - **Se negado**: se o mantenedor quiser o level 9, a entrega vira outra wiki: 474 correções não
    cabem num diff revisável junto com esta.
- **RQ-05** — não há PR aberto.
  - **Assumido**: a cláusula se cumpre pelo PR **desta** entrega, mergeado e tagueado junto com o
    `[Unreleased]` que já estava na `main` (#107–#109).
  - **Se negado**: os três commits sem tag sairiam num patch próprio antes desta branch.
- **RQ-06** — *"rígida"* sobre análise estática tem uma leitura mecânica e uma honesta.
  - **Assumido**: nenhum erro é calado por `baseline`, `@phpstan-ignore`, cast ou `@var` inline.
    Onde o erro nasce de **anotação errada do vendor**, a correção é corrigir a anotação (stub),
    não o código do kit — e, quando o stub não passa na validação do próprio PHPStan, uma exceção
    em `ignoreErrors` **com escopo de arquivo e a tentativa registrada**, no molde das três que o
    `phpstan.neon` já tem. *(alterado em 2026-09-26: o stub do `EnsureEmailIsVerified` foi
    medido e reprovou — ADR-03)* Onde o nulo é **impossível pelo desenho**, o código diz isso com a mesma
    expressão que o vendor usa, ou com uma exceção de invariante — nunca com um valor inventado.

### Premissas devolvidas pela `feature-test-design` (2026-09-26)


```markdown
- **RQ-06 / hub fora de painel** — o 00 não diz o que um hub faz sem painel corrente.
  - **Assumido**: exceção de invariante com mensagem clara (falha fechado); nunca cartões do painel padrão. (CT-09)
  - **Se negado**: lista vazia sem exceção; CT-09 inverte as duas primeiras linhas do `Então`. O invariante (nenhum cartão de outro painel) fica.
- **RQ-06 / tela de bloqueio em painel sem login** — painel de projeto sem `->login()` que usa o plugin de bloqueio.
  - **Assumido**: o visitante vai para a raiz do próprio painel (destino dentro do painel). (CT-10, linha `financeiro`)
  - **Se negado**: exceção de invariante que nomeia o painel; CT-10 inverte a linha. Nunca `''`, `/` ou laço.
- **RQ-06 / login social com painel de origem sem login**.
  - **Assumido**: cai no login do painel padrão, como painel inexistente. (CT-11, linha `financeiro`)
  - **Se negado**: raiz do painel de origem (`/financeiro`); CT-11 inverte a linha. Nunca 500, sessão aberta ou conta criada.
- **RQ-06 / `@phpstan-ignore` anteriores a esta entrega** (2 em `RoleResource.php`, 1 em `User.php`).
  - **Assumido**: ficam, congelados por inventário; nenhum novo. (CT-08)
  - **Se negado**: zero; CT-08 inverte para 0 ocorrências e a entrega remove os três.
- **RQ-06 / forma da exceção de invariante** (convite sem papel, hub sem painel).
  - **Assumido**: exceção de domínio (não `Error`/`TypeError`) cuja mensagem cita o que falta; texto livre. Em tela, vira o 500 do handler com mensagem clara no log.
  - **Se negado** (o mantenedor quer saída amigável na tela de cadastro): nasce um CT de `RegistroPorConvite` com aviso e destino.
- **Excluir pela tela um papel que tem convite pendente** (anterior a esta entrega): a FK recusa, e a ação de excluir do `RoleResource` vira `QueryException` (500).
  - **Assumido**: fora desta entrega. O CT-12 prova só o desenho (a FK existe).
  - **Se negado**: nasce um CT pela ação da tela com aviso de recusa, papel e convite intactos.
- **RQ-06 / `?? url('/')` já existente em `LoginSocialController::urlDoPainel`** — é valor inventado pela letra do 00, mas anterior a esta entrega e inalcançável sem tenant por domínio.
  - **Assumido**: fica fora desta entrega (lacuna M16).
  - **Se negado**: vira exceção de invariante; sem CT executável na configuração do kit.
```

## Fora de Escopo (declarado)

- PHPStan level 9 / `max` (474 / 594 erros)
- Incluir `tests/` nos `paths` do PHPStan (decisão medida no `phpstan.neon`)
- `declare(strict_types=1)` em todo `app/` (roadmap 8.3)
- `composer-require-checker`, type coverage, branch coverage (roadmap 8.2, 8.4, 8.5)

## Adendo 1 — 2026-09-26

- **Fonte**: achados do step 6.5 (`/code-review high main...HEAD` e `fw-revisor-diff`, ambos cegos
  ao plano), triados pela sessão. Não é pedido novo do mantenedor: é o que a revisão do diff provou
  que a entrega prometia e não cumpria
- **Fidelidade**: alta (achados com `arquivo:linha` e reprodução)

### Texto Original

<!-- IMUTÁVEL. Resumo fiel dos achados aceitos; os relatórios completos estão em 03-progresso.md. -->

> RD-01 / CR#1 — o [CT-02] novo reprova em todo projeto instalado: a linha do composer não é pulada
> e a asserção sobre o `on:` do workflow roda com o ci.yml ausente.
> RD-02 / CR#3 — `getLoginUrl()` nulo foi tratado só onde o PHPStan acusou; `DefinirSenhaPorEmail`
> e `RegistroPorConvite::register` seguem com `redirect(null)`, que deixa a tela congelada com a
> sessão já invalidada.
> RD-06 — a exceção do `ExigirEmailVerificado` em `ignoreErrors` não considerou a guarda de
> invariante que o próprio diff usa em cinco lugares.
> CR#5 — `responder()`, a ação cuja autorização mais mudou, não tem caso para o visitante.

### Decomposição

| ID | Cláusula | Trecho literal | Tipo | Substitui |
|----|----------|----------------|------|-----------|
| RQ-07 | Toda guarda de teste que lê arquivo não entregue pelo `create-project` roda sob a sentinela, inclusive as asserções de uma linha de dataset que não é pulada | RD-01 | restrição | — |
| RQ-08 | O nulo de URL de login é tratado **onde é alcançável**, não só onde o analisador acusa: nenhum `redirect()` recebe `null` | RD-02 | funcional | — |
| RQ-09 | A anotação errada do vendor é contornada por guarda de invariante no código do kit, não por exceção no `phpstan.neon` — o inventário de `ignoreErrors` volta às três anteriores | RD-06 | restrição | RQ-06 (a parte "exceção em `ignoreErrors`") e ADR-03 |
| RQ-10 | Toda ação pública do widget do assistente, `responder()` inclusive, recusa o visitante sem efeito | CR#5 | autorização | — |

