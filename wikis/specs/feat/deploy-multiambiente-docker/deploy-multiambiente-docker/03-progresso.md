# Progresso — Deploy com Docker: vários ambientes no mesmo servidor, atrás do Traefik

**Estado**: em planejamento

> Branch: `feat/deploy-multiambiente-docker` · Base do PR: `main` (`71a7297`, v0.44.0)

## 0. Revisão do levantamento
- [ ] Divergências documento × código registradas como premissas (P-01, P-02, P-05, P-06) e no confronto abaixo

## 1. `TRUSTED_PROXIES`
- [ ] `app/Support/ProxiesConfiaveis.php` com `doEnv()`
- [ ] `bootstrap/app.php` chama `trustProxies(at: …)` antes do `append`
- [ ] `.env.example` com o bloco comentado

## 2. `ARG` de build no estágio `assets`
- [ ] `Dockerfile.laravel` com os quatro `ARG`/`ENV` antes do `npm run build`, sem citar comando de cache

## 3. Override de exemplo
- [ ] `docker/traefik/docker-compose.override.yml` com cabeçalho, âncora `x-vite-args`, `nginx`, bloco do `reverb` comentado, rede externa

## 4. `.gitignore` e `.env.docker`
- [ ] `/docker-compose.override.yml` no `.gitignore`
- [ ] Bloco "Vários ambientes / Traefik" no `.env.docker`

## 5. Documentação
- [ ] `docs/pt/operacao/deploy-docker-multiambiente.md`
- [ ] `docs/en/operacao/deploy-docker-multiambiente.md`
- [ ] `docs/{pt,en}/operacao/index.md`
- [ ] `node converter.mjs` (sidebar, redirects, stubs)
- [ ] `README.md` e `README.en.md`
- [ ] `CHANGELOG.md` `[Unreleased]`

## 6. Testes e verificação
- [ ] Testes do `04` escritos pelo `fw-executor-ct`
- [ ] Regressão nomeada verde
- [ ] Contagens dos READMEs

## 7. Release
- [ ] Bump, CHANGELOG com *Validação antes da tag*, tag, `release.yml`

## Testes
<!-- Preenchida no step 7. -->

## Tickets
<!-- Step 8. -->

## Verificação Final
- [ ] `/ponytail:ponytail-review` no diff
- [ ] `vendor/bin/pint --dirty`
- [ ] `vendor/bin/pest tests/Kit/DeployMultiambienteDockerTest.php tests/Kit/ProxiesConfiaveisTest.php --compact`
- [ ] Regressão nomeada (`CacheDeViewsNoDockerTest`, `MysqlNoDockerTest`, `DeployDockerLocalTest`, `UrlSemPrefixoPublicTest`, `DiagramasDaArquiteturaTest`, `SiteDeDocumentacaoTest`, `RedeDeDocumentacaoTest`, `KitUpdateTest`)
- [ ] Suíte completa Kit+Tenancy contra a baseline (3.912 / 0 falhas / 841 pulados na simulação da v0.44.0)
- [ ] `pest --mutate --path=app/Support/ProxiesConfiaveis.php` via `pestw.cmd`: score, duração e sobreviventes
- [ ] `docker compose --profile app config` com e sem o override, fora da árvore
- [ ] **`/code-review high main...HEAD` + passe de eixos (step 9)**, antes da reconciliação
- [ ] Desvios propagados ao `01`/`02`/`04` de origem, marcados `*(alterado em …)*`
- [ ] `rastreabilidade.sh {wiki}` silencioso
- [ ] `checkbox-sem-evidencia.sh {wiki}` silencioso
- [ ] `citacoes.sh {wiki}` silencioso
- [ ] `ids-ct.sh {wiki} 'tests/Kit/DeployMultiambienteDockerTest.php' 'tests/Kit/ProxiesConfiaveisTest.php'` silencioso
- [ ] `conformidade-rules.sh {wiki} main` silencioso
- [ ] Falsificabilidade dos CTs novos: quantos falham sem o fix
- [ ] Docs pt/en, CHANGELOG e README reconciliados
- [ ] `node converter.mjs` sem diff residual
- [ ] `git commit`

## Revisão do Diff (step 9)

| ID | Passe | Achado | Destino | `P-nn` / CT | Rejeitado — motivo |
|---|---|---|---|---|---|

## Conformidade com Rules

| Rule | Glob que casou | Aplicada / n.a. / violada | Evidência |
|---|---|---|---|

## Quality Gate

<!-- Preenchido no step 11. -->

## Candidatos a Rule

<!-- Step 12. -->

## Auditoria Pré-Implementação

Entendimento confirmado: 2026-10-05 — sessão autônoma, pelas recomendações (o solicitante confirma ao ler o PR) — 1 rodada; perguntas: 0 fato (resolvidas por leitura/medição), 3 desenho (Q3–Q5), 2 requisito (Q1, Q2 — nenhuma bloqueia passo: ausente = default de hoje; as duas rotas do Reverb entregues)

### Perguntas da entrevista (step 4)

| Qn | Raia | Afeta | Pergunta | Recomendação adotada |
|---|---|---|---|---|
| Q1 | requisito | RQ-09, P-02 | o kit passa a ler `TRUSTED_PROXIES`? (estende o pedido) | sim, falha fechado — `00` |
| Q2 | requisito | RQ-12 | rota do Reverb no servidor: Traefik no mesmo hostname ou porta própria? | Traefik no mesmo hostname; bloco pronto e comentado; a outra rota continua — D4 |
| Q3 | desenho | RQ-11, P-03 | a cópia ativa do override entra no `.gitignore` do kit? | sim — D1 |
| Q4 | desenho | RQ-01 | a página nova fica em Começar ou em Operação? | Operação, `order: 6` — D2 |
| Q5 | desenho | P-02 | parsing de `TRUSTED_PROXIES` inline no bootstrap ou numa classe de `app/Support`? | classe, com teste unitário — D3 |

### Confronto código × afirmação (step 3/5)

| Pergunta | O documento dizia | O código faz | Resposta | Onde a wiki mudou |
|---|---|---|---|---|
| — | "`VITE_*` é assado no build … a correção de build se aplica" | `resources/js/app.js` é vazio; sem `laravel-echo`/`pusher-js`; Filament sem `broadcasting.echo` publicado — nada consome `VITE_REVERB_*` | o `ARG` entra como preparação inerte (P-01); o texto da doc diz que o kit não o consome | `00` P-01; `01` passo 2 |
| — | "`FORWARD_APP_PORT` vira opcional" via override | o Compose **concatena** `ports`; o override não retira a porta do base (medido) | opcional pela chave, `127.0.0.1:porta` para loopback (P-06) | `00` P-06; `01` passo 4/5 |
| — | (silêncio sobre proxies confiáveis) | `TrustProxies` nasce com `$proxies` nulo; o kit nunca chama `trustProxies()` | chave `TRUSTED_PROXIES`, ausente = hoje (P-02, Q1) | `00` P-02/Q1; `01` passo 1; ADR-02 |
| — | labels com `projeto3-dev` fixo no router | labels em **lista** interpolam `${COMPOSE_PROJECT_NAME}`; em mapa, a chave não interpola (medido) | router = nome do projeto Compose (P-05) | `00` P-05; `01` passo 3 |
| — | `networks: my-network: external: true` com o nome literal | chave de topo não interpola; `name: ${TRAEFIK_REDE:-my-network}` com chave fixa `traefik` funciona (medido) | rede parametrizada por `TRAEFIK_REDE` | `01` passo 3 |

### Medições do step 3 (sonda `scratchpad/compose-sonda`, Docker Compose v5.5.1)

| Medição | Comando | Resultado |
|---|---|---|
| override é carregado sem `-f` | `docker compose --profile app config` com `docker-compose.override.yml` na pasta | as chaves do override aparecem no `config`; renomeado o arquivo, o `config` volta ao base |
| `ports` concatenam | `.env` com `FORWARD_APP_PORT=127.0.0.1:8090` | `host_ip: 127.0.0.1`, `published: "8090"`; sem a chave, `published: "8000"` |
| labels em lista interpolam | `- traefik.http.routers.${COMPOSE_PROJECT_NAME:-starter-kit}.rule=…` | `traefik.http.routers.projeto3-dev.rule: Host(\`dev.exemplo.br\`)`; sem `COMPOSE_PROJECT_NAME`, `starter-kit` |
| labels em mapa **não** interpolam a chave | `traefik.http.routers.${COMPOSE_PROJECT_NAME}.rule: …` | chave sai literal `traefik.http.routers.$${COMPOSE_PROJECT_NAME:-starter-kit}.rule` |
| `:?` falha fechado | `.env` sem `TRAEFIK_HOST` | `required variable TRAEFIK_HOST is missing a value: defina TRAEFIK_HOST no .env` |
| rede externa com nome por env | `networks: traefik: {external: true, name: ${TRAEFIK_REDE:-my-network}}` | `traefik: {name: my-network, external: true}`; com a chave de topo interpolada, erro *undefined network* |
| `build.args` mesclam | `app.build.args.VITE_REVERB_HOST: ${VITE_REVERB_HOST:-}` | `args: {VITE_REVERB_HOST: dev.exemplo.br}` ao lado do `context`/`target` do base |

### Revisão profunda (step 5) — premissas do plano contra o código real

Sem despacho — as premissas do plano são as medições do step 3 feitas nesta mesma sessão (tabela
acima), e a conferência mecânica das citações já roda por script.

| Premissa do plano | O código real diz | Correção aplicada na wiki |
|---|---|---|
| todas as 24 citações `arquivo:símbolo:linha` do `01` | `bash .claude/skills/feature-wiki/scripts/citacoes.sh {wiki}` → silêncio, exit 0 (2026-10-05) | nenhuma |
| `trustProxies(at: null)` é no-op | `Middleware::trustProxies()` só chama `TrustProxies::at()` com `$at` não nulo (`vendor/laravel/framework/src/Illuminate/Foundation/Configuration/Middleware.php:trustProxies():698`) | nenhuma |
| `.gitignore` não está em `CAMINHOS_DO_KIT` | confirmado por grep (`'.env.example'` é o único arquivo de ponto da lista) | o `01` já declara: quem atualiza acrescenta a linha à mão |
| o comentário do passo 2 não pode citar comandos de cache | `CacheDeViewsNoDockerTest` afirma ausência de `view:cache`, `config:cache`, `route:cache` e `filament:optimize` no texto sem comentário do Dockerfile — comentário é filtrado, então citar seria até permitido; o plano manda não citar por prudência | nenhuma |

### Varredura da classe irmã (step 5)

Classe nova: `App\Support\ProxiesConfiaveis`. Irmã escolhida: `App\Support\BooleanoDoEnv` (classe
estática de leitura de `.env`, mesmo papel). `grep -rnF 'App\Support\BooleanoDoEnv' app config
database tests bootstrap` → `config/kit.php` (`use` + um comentário) e `tests/Kit/BooleanoDoEnvTest.php`;
por nome curto também `tests/Kit/AlertaDeAlteracoesNaoSalvasTest.php`, `DiagramasDaArquiteturaTest.php`,
`VersaoNoRodapeTest.php` (asserções sobre o `config/kit.php`). Nenhuma lista paralela (sem config
de inventário, sem seeder, sem provider): a classe nova precisa aparecer só onde é usada
(`bootstrap/app.php`) e no próprio teste. `app/Support` já está em `CAMINHOS_DO_KIT`.

### Auditoria Ponytail (step 6)

`/ponytail:ponytail-review` sobre `01`/`02`, em linha (comando do plugin), 2026-10-05:

| # | Sugestão de corte | Aplicada? | Onde |
|---|---|---|---|
| 1 | `yagni:` `TRAEFIK_ENTRYPOINT` — chave que ninguém define (o DevOps confirmou `websecure`); quem tiver outro entrypoint edita a cópia do override, que já é dele | **sim** | `01` passo 3, tabela de variáveis, mapeamentos; `00` P-04 |
| 2 | `delete:` receita da Opção D em comentário dentro do override — duplicava a página | **sim** | `01` passo 3 |
| 3 | `shrink:` matriz de portas inteira comentada no `.env.docker` — duplicava a página (RQ-16 é atendida pela página) | **sim** — fica uma linha `# FORWARD_APP_PORT=127.0.0.1:8090` e o apontador | `01` passo 4 |
| 4 | `shrink:` comentário de `TRUSTED_PROXIES` no `.env.example`, 5 → 3 linhas | **sim** | `01` passo 1 |
| 5 | `yagni:` classe `ProxiesConfiaveis` para quatro casos de parsing — inline no bootstrap | **recusada**: o bootstrap não é testável por unidade sem subir a aplicação com env fixa; a classe é o único ponto onde `''`, `null`, `*` e lista têm oráculo barato e mutação mensurável (D3) | — |
| 6 | `delete:` quatro `ARG`/`ENV` de `VITE_*` que nada consome (P-01) | **recusada**: é RQ-14, pedido literal do solicitante, e sem o `ARG` o projeto que adicionar Echo não tem como passar o valor sem editar o Dockerfile; 9 linhas inertes | — |

`net: -18 lines possible` no plano; 4 de 6 cortes aplicados.

## Despachos

| # | Step | Agente / tarefa | Modelo | Não recebeu | Resultado | Custo | Auditoria do retorno |
|---|---|---|---|---|---|---|---|
| — | 0–4 | Sem despacho — captura verbatim, decomposição, pesquisa por leitura direta (compose, Dockerfile, script, testes vizinhos, `KitUpdate`, `.gitattributes`, site), `search-docs` (trusted proxies, Reverb), `WebFetch` (Compose merge, Vite env, Traefik docker provider) e sonda local com `docker compose config` | sessão | — | pacote de pesquisa no `01` e nas medições acima | — | medições coladas acima |

## Blockers
- nenhum

## Desvios do Plano
<!-- Pós-implementação. -->

## Notas de Implementação
<!-- Pós-implementação. -->

## Referências Abertas
- `template-00-requisito.md` — step 4 — 2026-10-05
- `entrevista-tres-raias.md` — step 4 — 2026-10-05
- `roteamento-e-despacho.md` — step 3 — 2026-10-05
- `pesquisa-step-3.md` — step 3 — 2026-10-05
- `template-01-plano.md` — step 4 — 2026-10-05
- `template-02-adr.md` — step 4 — 2026-10-05
- `template-03-progresso.md` — step 4 — 2026-10-05
- `padrao-de-log.md` — step 4 — 2026-10-05
- `estrutura-criada.md` — step 4 — 2026-10-05
- `citacoes-de-codigo.md` — step 4 — 2026-10-05
- `glossario.md` — step 3 — 2026-10-05 (nenhum termo novo decidido até aqui)
- `ponytail-caveman.md` — início — 2026-10-05

## Retrospectiva
<!-- Pós-implementação. -->
