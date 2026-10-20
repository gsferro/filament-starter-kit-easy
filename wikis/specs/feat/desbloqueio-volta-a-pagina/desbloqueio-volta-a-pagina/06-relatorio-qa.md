# Relatório de QA — desbloqueio-volta-a-pagina: Desbloqueio da sessão volta para a página em que a pessoa estava

> Requisito: `00-requisito.md` · Plano: `01-plano-acao.md`
> Perfil de esforço: completo (domínio sensível: autenticação/sessão e destino de redirect)
> Natureza da wiki: correção · Toca infra compartilhada: sim → middleware de bloqueio em toda requisição autenticada dos três painéis · Regressão: sim
> Independência: sub-agente fw-qa-gate/claude-opus-5-5, contexto novo sem a conversa
> Cobertura: 6 de 12 dimensões verificadas ou provadas não aplicáveis · teto: APROVADO COM DÉBITO

## Ciclo 1

REPROVADO → especificação — 0 Blocker, 2 Major, 3 Minor (QA-01 a QA-05, todos destino 1); achados no `06` do ciclo 1 e em `## Quality Gate` do `03`.

## Veredito — Ciclo 2

**APROVADO COM DÉBITO**

- Blocker: 0 · Major: 0 · Minor: 0 · Cosmético: 0
- Não verificadas: B, C, D, E, J (passo TIA), K (K2) — causas em *Não Verificado*
- `RQ` abertas: nenhuma (Q1, Q2 e Q3 seguem abertas ao solicitante, seguidas como P-01, P-02 e P-06)
- Ambiente: app em `http://127.0.0.1:8123` · Pest 5.1.1 · MCP: Playwright indisponível; `--agent` indisponível

## Reconferência dos achados do ciclo 1 (HEAD `14235534`)

| ID | Estado | Prova |
|---|---|---|
| QA-01 | fechado | `03:45` diz 377; reproduzido `{"tests":75,"passed":75,"assertions":377}` |
| QA-02 | fechado | `01:79-81`, `01:91-95`, `01:107` (estágio 8), `02:16-17` e o docblock `TelaBloqueio.php:61` descrevem o `authenticate()` atual (sessão vence a propriedade, raiz marcada recalculada, limpeza das chaves; `retorno()`/`confirmarVinculo()` apagam a chave) |
| QA-03 | fechado | seção *Colisão de ID…* no `04`; `--filter='\[CT-10\] o segundo ciclo'` casa exatamente 1 teste (`tests:1, passed:1`) |
| QA-04 | fechado (texto) | `03:47` com o rótulo correto e a saída literal por mutante; a reprodução segue pendente (K2) |
| QA-05 | fechado | `## Despachos` com as linhas 4 a 10, todas com Custo |

## Achados

Nenhum novo.

## Dimensões

| # | Dimensão | Status | Observação |
|---|----------|--------|------------|
| A | Cobertura do requisito | ✅ | `rastreabilidade.sh` exit 0; diff por passo inalterado desde o ciclo 1 (o `14235534` só toca texto e um docblock); sem `07-tickets/` |
| B | Fronteiras e dados | ⏭️ não verificada | só estática (CT-01, CT-03, CT-20, CT-24); sem sondagem dinâmica |
| C | Matriz de permissão | ⏭️ não verificada | nenhuma permissão nova; CT-21 verde; sem confirmação dinâmica por papel |
| D | Observabilidade real | ⏭️ não verificada | estático OK (canal `autenticacao`, prefixo, context, só path, e-mail mascarado, sem PII); log real não lido |
| E | Performance | ⏭️ não verificada | leitura: o middleware não faz query; sem contagem medida |
| F | UX de erro | ✅ | nenhuma mensagem nova no diff; erro de senha do pacote em `data.password` (CT-12, CT-20) |
| G | Tema e cor | n/a | sem UI no `01` nem no diff; `dark-mode.sh {AM}` exit 0 (`--mecanismo` exit 1, Filament, só evidência) |
| H | Acessibilidade | n/a | sem UI no `01` nem no diff |
| I | Segurança da superfície nova | ✅ | step 9: 13 achados, 4 rejeitados; nenhuma rota nova, model, upload ou `DB::raw`; `#[Locked]` na propriedade de destino; CT-21 verde |
| J | Regressão adjacente | ⏭️ não verificada (parcial) | passos 2 e 3: os 9 arquivos adjacentes 490/490 (1969 asserções) — família `LoginSocial*`, `LoginUnificado`, `VinculoDeProvedorSocial`, `IdentidadeVisual`, `SiteDeDocumentacao`, `CitacoesDeCodigo`; a feature 75/75 + 5/5 com os 13 casos de `main`; bate com o `## Impacto em Features Existentes` do `01`. Passo 1 (`--parallel --tia`) não rodou |
| K | Adequação da suíte | ⏭️ não verificada (K2) | K1 exit 0 (ciclo 1, testes inalterados); K2: reprodução do M-a numa cópia barrada pelo hook (*Não Verificado*); revisão adversarial do `04` feita |
| L | Consistência documental | ✅ | `ids-ct.sh` exit 0 (colisão do CT-10 registrada), `citacoes.sh` exit 0, `conformidade-rules.sh main` exit 0, `checkbox-sem-evidencia.sh` exit 0; cabeçalho do `04` (37 cenários, 72 mutantes) confere com `grep -c`; L3 e L6 fechados (QA-01, QA-02, QA-04, QA-05) |

## Débitos Aceitos

- Dimensões não verificadas (lista abaixo), replicadas no `03`.

## Suspeitas Não Confirmadas

- `lockscreen_raiz_gravada` sobra na sessão depois do desbloqueio social e do link de vínculo (`TelaBloqueio.php:339,343` são os únicos `forget`). A rejeição da sessão no ciclo 1 ("só muda quando a organização ficou inacessível") está incompleta: `raizDoPainel()` recalcula pela organização da sessão **no envio**, que pode ser outra, acessível, que a da raiz antiga — ex.: raiz antiga `/app/globex`, a pessoa passa para acme e depois é travada ao pedir exatamente `/app/globex`; o desbloqueio levaria a `/app/acme`. Sem repro (o gate não escreve teste e não tem `--agent`). Não reabre como achado.

## Não Verificado

- B, C, E (sondagem dinâmica) — sem `pest-plugin-agent` e sem Playwright MCP; a sessão ociosa exige 1800 s.
- D (log real) — `phpunit.xml` força `monolog` com `NullHandler`; o fluxo no app servido não foi alcançável.
- J passo 1 (`pest --parallel --tia`) — fora do escopo de execução autorizado pelo orquestrador.
- K2 (prova indispensável pendente) — o hook `guarda-subagente` (perfil `qa-gate`) negou montar a cópia: o `git worktree add` em `%TEMP%\qwt` passou, mas o `mklink /J vendor` ("altera a árvore") e a gravação do autoload nesse diretório ("grava dentro do repositório") foram negados; o worktree foi removido. A saída por mutante do `03:47` continua alegação da sessão.
- L6 (alegações fora do escopo de execução): Kit 4385/4395, Tenancy 639/639, Blueprint 62/2/3 (`03:11`, `03:46`).
