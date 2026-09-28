# requirement-to-rule — Decisão da Wiki Vira Regra Durável

> **Skill**: [`SKILL.md`](SKILL.md) · versão **1.4.0** · licença MIT
> Este README fala com a **pessoa**: por que a skill existe, quando usar, o que ela não faz e do que depende. O procedimento que o agente segue (gates, escada de enforcement, prova do `arch()`, índice, poda, modelo da rule, fallback) vive só no `SKILL.md` e em `references/`. Aqui há ponteiros para ele, não cópia.

## Índice

- [Por que existe](#por-que-existe)
- [Quando usar](#quando-usar)
- [Quando não usar](#quando-não-usar)
- [O que ela entrega e onde está o procedimento](#o-que-ela-entrega-e-onde-está-o-procedimento)
- [Limites](#limites)
- [Dependências](#dependências)

---

## Por que existe

**A wiki tem memória, o agente não.** Uma decisão registrada em `02-decisoes-arquiteturais.md` só é lida por quem abrir **aquela** wiki. Na feature seguinte, em outra sessão, o agente não sabe que a decisão existe e repete o erro que a ADR já resolveu.

Uma decisão pode morar em três lugares, e cada um tem outro leitor:

| Camada | Onde fica | Quem lê, e quando | Serve para |
|---|---|---|---|
| **Wiki da feature** | `02-decisoes-arquiteturais.md`, Notas de Implementação do `03` | quem abrir aquela pasta, durante aquela feature | o porquê de uma decisão, com contexto e alternativas |
| **Project Rule** | `.ai/rules/*.md`, escopada por glob de path | todo agente que lê `.ai/rules/`, em qualquer sessão, antes de planejar ou editar arquivo que casa o glob | restrição da **sua aplicação** que atravessa features |
| **Guideline do Boost** | `.ai/guidelines/` e o próprio pacote do Boost | todo agente, carregada no início da sessão | como escrever **Laravel**: o ecossistema, que o Boost mantém atualizado |

A skill leva da primeira camada para a segunda só o que merece, e impede que a segunda vire cópia da terceira. O critério de cada fronteira está no `SKILL.md`, em [As três camadas](SKILL.md#as-três-camadas--não-confundir), [Vale virar rule](SKILL.md#vale-virar-rule) e [Os 4 Gates](SKILL.md#os-4-gates).

Um exemplo do que o Boost chama de rule, *"anything you would otherwise need to explain again in every new session"*: o padrão de log `[Classe@Método]` com channel por feature é reescrito em **toda** wiki desde a v1 da `feature-wiki`. É explicação repetida a cada sessão. Como rule, seria escrita uma vez.

Quando a restrição é mecânica, a prosa não basta: a skill gera o teste de arquitetura `arch()` do Pest e prova, rodando, que ele pega a violação. Regra que roda vale mais que regra descrita.

## Quando usar

- **Ao fechar uma feature com a [`feature-wiki`](https://github.com/gsferro/laravel-ai-skills/blob/main/.ai/skills/feature-wiki/README.md).** O step 12 dela, depois do quality gate, manda rodar esta skill, que faz tudo: procura os candidatos na wiki, julga, pergunta a você **uma vez** e grava.
- **Quando você pedir.** "Isso vira rule" ou "lembre disso para sempre" disparam a skill fora da `feature-wiki`, a partir de um card, um ticket ou da conversa.
- **Quando uma decisão ou armadilha vale além da feature.** Uma ADR aceita cuja consequência vale para código futuro, ou uma armadilha em que outro agente cairia.

A lista que o agente segue está em [Quando Invocar](SKILL.md#quando-invocar); quem pode executar (sessão principal ou sub-agente com MCP), em [Quem executa](SKILL.md#quem-executa-rota).

## Quando não usar

| Situação | O lugar certo |
|---|---|
| Decisão que só vale para a feature atual | ADR na wiki da feature |
| Como Laravel, Livewire ou Pest funcionam | guideline do Boost |
| Estilo de código, imports, tipagem | Pint, Rector, PHPStan |
| Convenções do código que já existe | skill `infer-conventions` do Boost |
| Preferência sua, de uma sessão | memória do agente |
| Termo do domínio decidido na feature | glossário do projeto, `wikis/glossario.md` (escrito pela `feature-wiki`) |

O critério que o agente aplica está em [Quando NÃO Invocar](SKILL.md#quando-não-invocar).

O `infer-conventions` e esta skill andam em sentidos opostos e se completam:

| | `infer-conventions` (Boost) | `requirement-to-rule` (esta coletânea) |
|---|---|---|
| Direção | **código existente** → rules | **requisito/decisão** → rules |
| Quando rodar | uma vez, ao adotar o Boost num projeto legado | continuamente, a cada feature concluída |
| O que documenta | o que o código **faz** hoje | o que foi **decidido** que o código fará |

Ordem recomendada: rodar `infer-conventions` uma vez para bootstrapar a base, e usar `requirement-to-rule` como incremento a partir daí.

## O que ela entrega e onde está o procedimento

| Você recebe | Procedimento no `SKILL.md` |
|---|---|
| Candidatos com origem e evidência, tirados da wiki ou do requisito | [1. Coletar candidatos](SKILL.md#1-coletar-candidatos) |
| Diagnóstico do que já existe em `.ai/rules/` | [2. Verificar o estado atual das rules](SKILL.md#2-verificar-o-estado-atual-das-rules) |
| Cada candidato julgado nos gates, inclusive os descartados, com os arquivos lidos no gate 3 | [Vale virar rule](SKILL.md#vale-virar-rule), [Os 4 Gates](SKILL.md#os-4-gates) e [3. Aplicar os 4 gates](SKILL.md#3-aplicar-os-4-gates) |
| Automação no lugar de prosa, quando a máquina alcança | [4. Preferir enforcement automático](SKILL.md#4-preferir-enforcement-automático-escada-de-rules) |
| **Uma** pergunta para você decidir o que grava, o que atualiza e o que poda | [5. Apresentar ao usuário](SKILL.md#5-apresentar-ao-usuário-e-esperar-decisão) |
| Um teste `tests/Arch/{Área}Test.php` que roda, com a prova colada de que ele pega a violação | [Enforcement que roda](SKILL.md#enforcement-que-roda--arch-gerado-e-provado) |
| A rule gravada pelo Boost | [6. Gravar via `record-rule`](SKILL.md#6-gravar-via-record-rule-obrigatório) e [Modelo Base do Conteúdo da Rule](SKILL.md#modelo-base-do-conteúdo-da-rule) |
| O índice `.ai/rules/index.md` conferido | [7. Garantir o índice](SKILL.md#7-garantir-o-índice-airulesindexmd) e [Índice de Rules](SKILL.md#índice-de-rules-airulesindexmd) |
| Proposta de atualizar ou remover rule que não serve mais | [Poda](SKILL.md#poda) |
| Um commit de `.ai/rules/` (e do teste, quando há) na branch do PR que a `feature-wiki` já abriu, com uma linha na descrição do PR | [8. Verificar e commitar](SKILL.md#8-verificar-e-commitar) |
| O resultado numa linha, `apresentados N · gravados N · recusados N · descartados no gate N · poda N`, que a `feature-wiki` grava no `03` | [8. Verificar e commitar](SKILL.md#8-verificar-e-commitar) |
| Caminho para projeto sem Boost, com rules desativadas ou em agente sem MCP | [Fallback](SKILL.md#fallback--boost-ausente-ou-rules-desativadas) |

O que a skill evita está em [Anti-padrões](SKILL.md#anti-padrões); a conferência de saída, em [Checklist Final](SKILL.md#checklist-final).

## Limites

O que a skill **não** faz, ou faz pela metade:

- **A poda depende de histórico e de você.** O gatilho (rule `n.a.` ou `violada` em 3 features seguidas, ou glob sem arquivo) é hipótese, não medida, e só funciona se os `03` das features anteriores tiverem a tabela `## Conformidade com Rules`. Não há expiração nem dono. Remover arquivo de rule ou mudar glob exige editar uma linha do índice à mão, porque o Boost não tem tool de remoção e o `boost:update` com a configuração padrão não regenera o índice. A alternativa é regenerá-lo com `RuleRepository::writeIndex()` pelo `php artisan tinker` — API interna do Boost, não documentada — pode mudar ([como e quando falha](references/indice-e-record-rule.md#alternativa-writeindex-pelo-tinker)).
- **O commit do step 12 chega depois do veredito.** Ele entra na branch do PR já aberto e muda o PR depois do quality gate; a linha na descrição do PR avisa quem revisa. O comando que acrescenta a linha (`gh pr edit`) foi conferido pelo `--help`, não contra um PR real.
- **Só prova enforcement de `arch()`.** Para PHPStan, Rector e Pint a skill sugere configuração e não gera nada; a rule só cita essas ferramentas se você colar uma execução que acusa a violação. `arch()` não enxerga tipo de coluna, migration nem lógica dentro do método: essas restrições continuam em prosa.
- **Sem MCP, o gate 4 fica sem verificação.** Ele usa a tool `search-docs` do Boost. Sem MCP o candidato chega a você marcado "gate 4 não verificado" e a decisão de gravar é sua; não há verificação alternativa.
- **O script de prova foi exercitado num projeto-fixture, não num projeto Laravel real.** As saídas no cabeçalho de `scripts/prova-arch.sh` vêm de um projeto mínimo com Pest 5.2.1. A remoção do arquivo temporário em interrupção foi testada só com `TERM`, no Git Bash do Windows, com um `pest` falso: o arquivo sai quando o `pest` em curso termina.
- **Só alcança agentes que leem `.ai/rules/`.** Os demais só veem a rule se houver espelho no formato deles (ver [Fallback](SKILL.md#fallback--boost-ausente-ou-rules-desativadas)).
- **Ainda sem execução medida.** Os exemplos vêm da doc do Boost ou são hipotéticos; a skill não tem rodada no protocolo de [`experimentos/`](https://github.com/gsferro/laravel-ai-skills/blob/main/experimentos/README.md).

## Dependências

| Dependência | Versão mínima | Para quê |
|---|---|---|
| `laravel/boost` | **2.4.12** | Project Rules e a tool MCP `record-rule` surgiram nessa versão ([CHANGELOG do laravel/boost](https://github.com/laravel/boost/blob/main/CHANGELOG.md), PR #852); a tool `search-docs` do gate 4 vem no mesmo pacote. O comportamento de índice e de arquivo descrito no `SKILL.md` foi lido no código da v2.10.0 |
| Agente com MCP | — | `record-rule` e `search-docs` são tools MCP. Sem MCP, a gravação cai no [Fallback](SKILL.md#fallback--boost-ausente-ou-rules-desativadas) e o gate 4 fica "não verificado" (ver [Limites](#limites)) |
| Pest com `arch()` | — | para o teste de arquitetura gerado e provado; a sintaxe citada é a da doc 5.x (https://pestphp.com/docs/arch-testing) |
| `bash`, `git`, `php` | — | o `scripts/prova-arch.sh` (sem `jq`, `node` ou `python`) |
| `gh` autenticado | —, opcional | acrescentar a linha do step 12 à descrição do PR; sem ele, a skill entrega a linha para você colar |
| `laravel/tinker` | 2.x, opcional | só para a alternativa de regenerar o índice na poda (`tinker --execute`) |
| [`feature-wiki`](https://github.com/gsferro/laravel-ai-skills/blob/main/.ai/skills/feature-wiki/README.md) | 4.0.0, opcional | só quando os candidatos vêm da wiki: o step 12 dela delega tudo a esta skill a partir da 4.0.0 |
| [`feature-test-design`](https://github.com/gsferro/laravel-ai-skills/blob/main/.ai/skills/feature-test-design/README.md) | 1.15.0, opcional | o checklist de taxonomia do `04` é fonte de candidato; o `SKILL.md` aponta para `feature-test-design/references/taxonomia-de-defeito.md`, que só existe a partir da 1.15.0 |

```bash
composer require laravel/boost --dev
php artisan boost:install
```

- Doc do Boost, Project Rules: https://laravel.com/framework/docs/13.x/boost#project-rules
- `BOOST_RULES_ENABLED=false` no `.env` remove a tool `record-rule` e tira `.ai/rules/` da gestão do Boost; a skill passa ao [Fallback](SKILL.md#fallback--boost-ausente-ou-rules-desativadas).
- Skills que conversam com esta (Ponytail, `pest-testing` do Boost, `infer-conventions`) estão em [Skills Companheiras](SKILL.md#skills-companheiras); nenhuma é obrigatória.
