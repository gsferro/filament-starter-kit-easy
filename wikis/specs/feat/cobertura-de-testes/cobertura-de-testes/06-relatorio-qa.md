# Relatório de QA — Cobertura de testes medida, com meta e badge

> Requisito: `00-requisito.md` (RQ-01…RQ-09 + Adendo 1: RQ-10…RQ-14) · Plano: `01-plano-acao.md`
> Perfil: **padrão + dimensão K medida** — o artefato sob teste *é* um gate, e falhar aberto é o
> modo caro.
> Independência: sub-agente `fw-qa-gate` (`opus`), sem acesso à conversa. Recebeu só o caminho da
> wiki e o `git diff`.

## Veredito

| Ciclo | Data | Veredito | Blocker | Major | Minor | Cosmético |
|---|---|---|---|---:|---:|---:|
| **1** | 2026-09-25 | **REPROVADO → especificação** | 0 | 9 | 8 | 1 |
| **2** | 2026-09-25 | **fechamento aplicado** — ver `## Disposição dos achados` | — | — | — | — |

**Nenhum Major era defeito de comportamento do produto.** O que o gate rodou, passou: suíte
`Kit,Tenancy` verde, Pint verde, PHPStan **0 erros**, e mutation score real de **93,71 %** no
comando novo (medido por ele, com comando e duração). Os 9 Major eram de **especificação e de
consistência documental** — o texto que a próxima pessoa leria não descrevia o que foi entregue.

### Degradações declaradas no ciclo 1

- **`04-casos-de-teste.md` e `05` não existiam.** O step 4 da `feature-wiki` nunca rodou nesta
  feature. O gate de entrada da `feature-quality-gate` exige o `04`; o gate rodou assim mesmo, por
  instrução, e a ausência virou o achado **QA-01**. Toda a coluna `CT` da matriz saiu vazia por
  essa causa.
- **App não servido.** `php artisan serve` não sobe neste ambiente. A entrega não tem superfície de
  UI (workflow, comando Artisan, documentação e badges), então as dimensões dinâmicas não tinham
  alvo — mas a limitação está declarada, não omitida.

## Disposição dos achados

| # | Achado | Sev. | Destino | O que foi feito |
|---|---|---|---|---|
| QA-07 | **RQ-07 cumprida por construção**: o piso (78 %) foi fixado **abaixo** do medido (79,79 %), e o passo "fechar lacuna real" não fechou nenhuma — o único teste escrito cobria o comando que a própria entrega criou | Major | 1 | **decisão do mantenedor: fechar de verdade.** `tests/Kit/PoliciesTest.php`, 37 casos; `app/Policies` saiu de 23 % e a cobertura foi de 79,89 % a **81,61 %** |
| QA-01 | `04`/`05` inexistentes; 14 `[CT-nn]` só no código | Major | 3 | `04-casos-de-teste.md` derivado **às cegas** do `00` por sub-agente (32 cenários, 65 mutantes), com reconciliação nos dois sentidos. Colisão de IDs resolvida: `PoliciesTest` passou ao prefixo `CT-P##` |
| QA-03 | `~27 min` sobrevivia em **11 lugares**, contradizendo a tabela correta da mesma página | Major | 1 | corrigido nos 11, por contexto: **25 min** local, **52 min** no runner |
| QA-04 | ADR-06/ADR-03/passo 2 com a medição obsoleta (6 pontos) | Major | 1 | remedido; os números finais entraram em `03-progresso.md` |
| QA-05 | a guarda do `KitInstall` afirmava ausência sobre o **fonte cru**, sem filtrar comentário — `.ai/rules/testes.md` lista **três** ocorrências anteriores | Major | 2 | corrigido; verificado nos dois sentidos (comentário citando `system()` não reprova; `system()` real reprova) |
| QA-08 | mutation score de **89,24 %** publicado **sem comando e sem `Duration`**, violando a regra escrita duas páginas antes — e não reproduzia (o gate mediu 93,71 %) | Major | 1 | republicado em `docs/{pt,en}` com comando, duração, plataforma e sobreviventes |
| QA-09 | o `01` não descrevia `kit:cobertura` nem `pestw.cmd`; `## Desvios do Plano` vazio | Major | 1 | passo 5 do `01` reescrito; `## Desvios do Plano` preenchido com os quatro artefatos fora do plano |
| QA-02 | `## Verificação Final` toda aberta e `## Quality Gate` vazio, com a feature já em `main` | Major | 1 | preenchidos com a saída real de cada comando |
| QA-06 | `## Conformidade com Rules` em branco; faltavam `app.md` e `specs.md` | Major | 1 | as 5 linhas preenchidas com evidência — **duas rules violadas e corrigidas** |
| QA-10 | `KitInstall.php:592` não é o `exec` (são 635–637), e a citação estava em **dois** arquivos | Minor | 1 | corrigida para `KitInstall.php:oferecerEstrela:635`, no formato que `specs.md` exige |
| QA-11 | ADR-05 dizia "1 violação" onde a revisão de diff já apurara **3** `exec()` | Minor | 1 | corrigido, com o custo real da exceção (20 funções liberadas) escrito |
| QA-12 | "415 de 1.517 blocos" em três lugares; a árvore dizia 427 de 1.617 | Minor | 1 | remedido |
| QA-13 | o `03` registrava 157/184 arquivos; a árvore dizia 158/185 | Minor | 1 | remedido |
| QA-14 | `app/Console` com **três** valores diferentes | Minor | 1 | unificado |
| QA-15 | denominador "240 arquivos"; são 242 | Cosmético | 1 | remedido |
| QA-16 | a mensagem de reprovação apontava para `wikis/specs/`, que é `export-ignore` — mesma classe do RD-01, noutro artefato | Minor | 2 | mensagem autocontida |
| QA-17 | *"o `--tia` funciona"* provado por uma **mensagem de recusa** | Minor | 1 | frase corrigida em vez de a evidência ser inflada |
| QA-18 | `[CT-06]` afirma só `assertFailed()` para as quatro formas de relatório indomável | Minor | 3 | **débito aceito**, declarado no `04` |

## O que a reconciliação do `04` acrescentou ao veredito

A derivação cega, feita depois do gate, achou **15 buracos** que nenhum dos dois passes anteriores
tinha visto. **Quatro** foram fechados neste ciclo:

| # | Buraco | Fechado com |
|---|---|---|
| 1 | **o piso `78` não estava ligado a nada** — literal à mão no `composer.json` **e** no `ci.yml`, documentação em prosa. Baixá-lo para 70 não deixava nada vermelho, e era a única mudança de uma linha capaz de fazer a meta "passar" sem cobrir uma linha de `app/` | `[CT-51]`, que amarra os três e morde dos dois lados |
| 3 | **`--min=0` era aceito** e o comando ainda anunciava *"o piso foi respeitado"* | faixa passou a `1..100`, com o caso `zero` no `[CT-04]` |
| 4 | **o recorte medido não tinha guarda nenhuma** — `phpunit.xml` era a única coisa que definia o denominador, e trocar `<directory>app</directory>` por `app/Models`, ou acrescentar um `<exclude>` sobre o diretório de pior cobertura, **sobe o percentual** e passa por todo o CI | `[CT-52]` e `[CT-53]`, verificados com três mutantes: estreitar por inclusão, por exclusão, e apagar o `<source>` inteiro |
| 10 e 11 | **RQ-13 e RQ-14 não estavam na documentação de usuário** — o levantamento de níveis de qualidade e o mutation score viviam só na wiki e no roadmap, e RQ-08 diz *"tudo fica na documentação"* | duas seções novas em `docs/{pt,en}/referencia/qualidade-de-codigo.md` |

Os **onze restantes** estão no `04`, cada um com o cenário que os fecha, e são o roteiro do próximo
ciclo. O maior deles: nenhuma guarda sobre a **cadeia de propagação do código de saída** no CI —
um `|| true` no passo, ou tolerância a erro no job, deixa o gate verde com a cobertura abaixo da
meta.

## Dimensões

| # | Dimensão | Status | Observação |
|---|---|---|---|
| A | Cobertura do requisito | ✅ **no ciclo 2** | QA-07 fechado com código, não com redação; QA-01 fechado com o `04` |
| B | Fronteiras e dados | ✅ | valor limite do piso, quatro formas de relatório indomável, piso como texto livre |
| C | Matriz de permissão | ⏭️ | a entrega não tem superfície de autorização — confirmado no diff |
| D | Observabilidade | ✅ n.a. | zero `Log::` no código novo; sem PII possível |
| E | Performance | ✅ | o custo é a medição serial: **25 min local / 52 min no runner**, medido, decidido e fora do PR |
| F | UX de erro | ✅ **no ciclo 2** | QA-16 fechado; as mensagens dizem o quê e o que rodar |
| G | Tema e cor | ⏭️ | sem superfície de UI |
| H | Acessibilidade | ⏭️ | idem |
| I | Segurança da superfície nova | ✅ | sem rota, model ou upload; `simplexml_load_file` sem `LIBXML_NOENT` em PHP 8 não carrega entidade externa |
| J | Regressão adjacente | ✅ | suíte completa verde |
| K | Adequação da suíte | ✅ | mutation medido em três alvos, com comando e duração; **4 mutantes para 222 statements** nas policies, e por isso ali o instrumento é a asserção de permissão, não a mutação |
| L | Consistência documental | ✅ **no ciclo 2** | 13 achados do ciclo 1, todos fechados |

## Não verificado, e por quê

- **Dimensões dinâmicas** — app não servido; e a entrega não tem superfície de UI, então não há alvo.
- **O caminho positivo do `--tia`** — só a recusa foi observada (QA-17). Declarado no `03`.
- **RQ-12 (custo do Xdebug)** — exige trocar o `php.ini` da máquina do mantenedor; aceito como
  medido no `03`.
- **A obrigatoriedade do job `cobertura` como *required check*** — vive na configuração do GitHub,
  fora da árvore. É a pergunta devolvida ao `00` no `04`.

## Retorno do gate, verbatim, sobre a própria desconfiança do orquestrador

> *"Você suspeitou da RQ-07: a suspeita procede, e o mecanismo é pior do que 'débito com nome
> bonito' — a meta foi calibrada **abaixo** do ponto de partida, então nem o débito era necessário
> para passar."*

> *"Você suspeitou de número velho em README/docs/CHANGELOG/roadmap: procede, mas o pior não está
> nos números que você listou — está no `~27 min`, uma estimativa que o `03:53` declara errada, que
> o RD-12 declara corrigida, e que continua viva em 11 lugares, incluindo o comentário que abre o
> `ci.yml` e o docblock de um teste do mesmo commit."*
