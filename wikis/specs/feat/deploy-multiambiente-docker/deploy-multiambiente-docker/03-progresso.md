# Progresso — Deploy com Docker: vários ambientes no mesmo servidor, atrás do Traefik

**Estado**: em revisão

> Branch: `feat/deploy-multiambiente-docker` · Base do PR: `main` (`71a7297`, v0.44.0)

## 0. Revisão do levantamento
- [x] Divergências documento × código registradas como premissas (P-01, P-02, P-05, P-06) e no confronto abaixo — tabela `Confronto código × afirmação` em `## Auditoria Pré-Implementação`, 2026-10-05

## 1. `TRUSTED_PROXIES`
- [x] `app/Support/ProxiesConfiaveis.php` com `doEnv()` — `vendor/bin/pint` passed, `phpstan analyse` 0 erros, 2026-10-05
- [x] `config/kit.php` (`proxies_confiaveis`) + `KitServiceProvider::confiarNosProxiesDoEnv()` no `boot()`; `bootstrap/app.php` **igual ao da `main`** (`git diff main...HEAD -- bootstrap/app.php` vazio) — desvio do passo 1 pelo step 9 (RD-01); `php artisan config:show app.name` boota; `UrlSemPrefixoPublicTest` verde, 2026-10-05 *(era: "`bootstrap/app.php` chama `trustProxies(at: …)`" — QA-01)*
- [x] `.env.example` com o bloco comentado — `MysqlNoDockerTest` CT-15/CT-22 verdes depois da normalização para LF, 2026-10-05

## 2. `ARG` de build no estágio `assets`
- [x] `Dockerfile.laravel` com os quatro `ARG` (sem default, sem `ENV` — P-12) antes do `npm run build`, sem citar comando de cache — `CacheDeViewsNoDockerTest` verde no lote de 114, 2026-10-05

## 3. Override de exemplo
- [x] `docker/traefik/docker-compose.override.yml` com cabeçalho, âncora `x-vite-args`, `nginx`, bloco do `reverb` comentado entre `>>>`/`<<<`, rede externa — `docker compose --profile app config` sobre cópia do base real: labels `projeto-dev`, `host_ip: 127.0.0.1`, rede `my-network` externa; sem `TRAEFIK_HOST`, recusa com a mensagem, 2026-10-05

## 4. `.gitignore` e `.env.docker`
- [x] `/docker-compose.override.yml` no `.gitignore` — commit `036c2f4`, 2026-10-05
- [x] Bloco "Vários ambientes / Traefik" no `.env.docker` — `MysqlNoDockerTest` CT-20 verde (o `# DOCKER_DB_SERVICE=mysql` continua), 2026-10-05

## 5. Documentação
- [x] `docs/pt/operacao/deploy-docker-multiambiente.md` — `SiteDeDocumentacaoTest` + `RedeDeDocumentacaoTest` 88/88 e, com `MysqlNoDockerTest` e `UploadLimiteETiposDocumentacaoTest`, 123/123, 2026-10-05
- [x] `docs/en/operacao/deploy-docker-multiambiente.md` — `SiteDeDocumentacaoTest` + `RedeDeDocumentacaoTest` 88/88 e, com `MysqlNoDockerTest` e `UploadLimiteETiposDocumentacaoTest`, 123/123, 2026-10-05
- [x] `docs/{pt,en}/operacao/index.md` — `SiteDeDocumentacaoTest` + `RedeDeDocumentacaoTest` 88/88 e, com `MysqlNoDockerTest` e `UploadLimiteETiposDocumentacaoTest`, 123/123, 2026-10-05
- [x] `node converter.mjs` (sidebar, redirects, stubs) — `SiteDeDocumentacaoTest` + `RedeDeDocumentacaoTest` 88/88 e, com `MysqlNoDockerTest` e `UploadLimiteETiposDocumentacaoTest`, 123/123, 2026-10-05
- [x] `README.md` e `README.en.md` — `SiteDeDocumentacaoTest` + `RedeDeDocumentacaoTest` 88/88 e, com `MysqlNoDockerTest` e `UploadLimiteETiposDocumentacaoTest`, 123/123, 2026-10-05
- [x] `CHANGELOG.md` `[Unreleased]` — `SiteDeDocumentacaoTest` + `RedeDeDocumentacaoTest` 88/88 e, com `MysqlNoDockerTest` e `UploadLimiteETiposDocumentacaoTest`, 123/123, 2026-10-05

## 6. Testes e verificação
- [x] Testes do `04` escritos pelo `fw-executor-ct` — dois arquivos, 205 casos (77 + 128), todos verdes após o `04` v4, 2026-10-05
- [x] Regressão nomeada verde — ver `## Verificação Final`, 2026-10-05
- [x] Contagens dos READMEs — 75 / 177 / 206 / 1.944 por comando (`SiteDeDocumentacaoTest` CT-25/CT-50 e o caso dos números objetivos verdes), 2026-10-05

## 7. Release
- [ ] Bump, CHANGELOG com *Validação antes da tag*, tag, `release.yml`

## Testes
- [x] `tests/Kit/DeployMultiambienteDockerTest.php` (CT-01..CT-19, CT-23..CT-47) — 128/128 verdes após o `04` v4 (122 na v3) (`pest tests/Kit/DeployMultiambienteDockerTest.php --compact`), 2026-10-05
- [x] `tests/Kit/ProxiesConfiaveisTest.php` (CT-20, CT-21, CT-22, CT-48, CT-49, CT-50) — 77/77 verdes após o `04` v4 (44 na v3, 33 na v2) (`pest tests/Kit/ProxiesConfiaveisTest.php --compact`), 2026-10-05

## Tickets
Não fatiado — 2026-10-05: 17 RQ vigentes, 32 CT, compactação: sim (antes do step 0 desta feature, na feature anterior da mesma sessão), 6 perguntas de requisito — sinal de compactação cruzado, sugestão não feita: sessão autônoma (só o usuário invoca a `feature-tickets`) e a feature cabe numa sessão — 3 arquivos de código, o resto é infra declarativa e documentação

## Verificação Final
- [x] `/ponytail:ponytail-review` no diff — passe em linha (skill do plugin) sobre `git diff main...HEAD -- app bootstrap config Dockerfile.laravel docker .env.* .gitignore`: código novo = 1 classe (`ProxiesConfiaveis`, 4 métodos, 1 constante), 1 método privado no provider, 1 chave de config, 4 `ARG`, 1 linha no `KitUpdate`; `bootstrap/app.php` idêntico à `main`. Achados: `L? shrink:` nenhum — `texto()`/`separar()`/`ehIpOuCidr()` são a menor forma que mantém `doEnv()` e `descartados()` com uma só regra; `delete:` nenhum (o `ARG` inerte é RQ-14, recusado no step 6 e mantido). `Lean already. Ship.` — `net: -0 lines possible`, 2026-10-05
- [x] `vendor/bin/pint --dirty` — `{"tool":"pint","result":"passed"}` sobre os PHP do diff, 2026-10-05
- [x] `vendor/bin/pest tests/Kit/DeployMultiambienteDockerTest.php tests/Kit/ProxiesConfiaveisTest.php --compact` — 128/128 + 77/77 (205 casos; com `RedeDeDocumentacaoTest` e `HelpersDeTesteTest`, 226/226), 2026-10-05
- [x] Regressão nomeada (`CacheDeViewsNoDockerTest`, `MysqlNoDockerTest`, `DeployDockerLocalTest`, `UrlSemPrefixoPublicTest`, `DiagramasDaArquiteturaTest`, `SiteDeDocumentacaoTest`, `RedeDeDocumentacaoTest`, `KitUpdateTest`) — todos dentro das três fatias da `Kit` (verdes) e rerodados isolados: 114/114 (cache/mysql/deploy/url/update), 88/88 (site/rede), 570/570 (diagramas/checklist/constraint/arquitetura), 2026-10-05
- [x] Suíte completa Kit+Tenancy contra a baseline (3.912 / 0 falhas / 841 pulados na simulação da v0.44.0) — `Kit` em três fatias **em série** (a rodada em paralelo foi interrompida pelo host por falta de memória): 771 + 1.720 + 1.140 = **3.631** testes; 4 falhas nas duas primeiras fatias, todas corrigidas e rerodadas verdes no mesmo dia (`ArquiteturaDoCodigoTest` ×2: `var_export` em `app/` → `json_encode`; `DiagramasDaArquiteturaTest` CT-66 ×2: citações para `KitServiceProvider.php`/`KitUpdate.php` deslocadas pelas linhas novas — 20 citações recalculadas em docs, comentários e testes); `Tenancy` `--parallel`: 486/486. Total 4.117 casos sobre o código final, 0 falhas pendentes (a baseline da `main` tinha 3.912: +205 desta feature), 2026-10-05. Antes era parcial: `pest --testsuite=Tenancy --parallel` → 486/486 verdes (2.012 asserções, 133 s) sobre o código final; a `Kit` roda de novo depois da 3ª passada do executor (a primeira rodada, antes do step 9, deu 3.592 testes com 2 falhas em `DiagramasDaArquiteturaTest` CT-43, já corrigidas na página)
- [x] `pest --mutate --path=app/Support/ProxiesConfiaveis.php` via `pestw.cmd`: score, duração e sobreviventes — **classe final** (sem a constante de coringas: a validação IP/CIDR já os descarta): `XDEBUG_MODE=coverage MSYS_NO_PATHCONV=1 cmd /c pestw.cmd tests/Kit/ProxiesConfiaveisTest.php --mutate --path=app/Support/ProxiesConfiaveis.php --no-tia` → antes da simplificação `75 Mutations, 5 uncovered (RemoveArrayItem da constante), 70 tested, Score 93.33%, Duration 2.76s` — duração **implausível** (≈39 ms por mutante contra ≈11 s por execução do arquivo), então o score **não é evidência** (`.ai/rules/testes.md`). Evidência válida, com a suíte final de **79 casos**: **4 mutantes manuais mortos** — `ehIpOuCidr()` sempre `true` (3 falhas de 79), limite do prefixo `<=`→`<` (2 falhas — só depois de o CT-20 ganhar as linhas `/32` e `/128`, QA-02), filtro invertido `! ehIpOuCidr` → `ehIpOuCidr` (41 falhas de 79), e, na classe anterior, `=== '*'`→`!== '*'` (21 de 33). Mutante **equivalente** descoberto: retirar `private_ranges` da constante não mudava nada (todo não-IP já é descartado) — a constante saiu da classe por isso (ponytail). Árvore restaurada, 79/79, 2026-10-05
- [x] `docker compose --profile app config` com e sem o override, fora da árvore — medido na sonda `scratchpad/compose-real` (cópia do base real): com override, labels `projeto-dev`, `host_ip: 127.0.0.1`, rede `my-network` externa, `build.args` só com a `VITE_REVERB_HOST` definida; sem `TRAEFIK_HOST`/`REVERB_APP_KEY`, recusa nomeando a chave; sem o override, a config é a do golden (CT-34 verde), 2026-10-05
- [x] **`/code-review high main...HEAD` + passe de eixos (step 9)**, antes da reconciliação — `/code-review` indisponível neste host: passe genérico por `general-purpose`/opus cego (CR-01..09) + `fw-revisor-diff` (RD-01..08), 17 achados, todos aceitos e roteados (P-18..P-25 → CT → correção), 0 rejeitados sem motivo; tabela em `## Revisão do Diff (step 9)`, 2026-10-05
- [x] Desvios propagados ao `01`/`02`/`04` de origem, marcados `*(alterado em …)*` — `grep -c 'alterado em 2026-10-05' 01-plano-acao.md` = 8, ADR-02 do `02` revisada com a nota, `04` v4 com `## Revisão Adversarial` + `### Step 9`; lista em `## Desvios do Plano`, 2026-10-05
- [x] `rastreabilidade.sh {wiki}` silencioso — `bash .claude/skills/feature-wiki/scripts/rastreabilidade.sh wikis/specs/feat/deploy-multiambiente-docker/deploy-multiambiente-docker` → vazio, exit 0, 2026-10-05
- [x] `checkbox-sem-evidencia.sh {wiki}` silencioso — vazio, exit 0, 2026-10-05 (reconferido ao fechar o `03`)
- [x] `citacoes.sh {wiki}` silencioso — vazio, exit 0 sobre `00`–`04`, 2026-10-05
- [x] `ids-ct.sh {wiki} 'tests/Kit/DeployMultiambienteDockerTest.php' 'tests/Kit/ProxiesConfiaveisTest.php'` silencioso — vazio, exit 0 (50 IDs do `04` ⊆ 2 arquivos e vice-versa; eram 47 na v3 — QA-01), 2026-10-05
- [x] `conformidade-rules.sh {wiki} main` silencioso — vazio, exit 0 depois das 4 linhas em `## Conformidade com Rules` (`app.md` n.a., `support.md` n.a., `testes.md` aplicada, `specs.md` aplicada), 2026-10-05
- [x] Falsificabilidade dos CTs novos: quantos falham sem o fix — **fiação final** (chamada `confiarNosProxiesDoEnv()` comentada no `boot()` do provider): **7 de 77 falham** em `ProxiesConfiaveisTest` (os CT-22/CT-48/CT-50 de request e log), árvore restaurada e 77/77 depois, 2026-10-05; medições anteriores à mudança do step 9: `ProxiesConfiaveisTest`: com `bootstrap/app.php` da `main` e uma classe-toco que devolve a string crua, **17 de 33 falham** (os 16 que passam são as linhas "ausente/vazio = hoje", que valem nos dois lados por construção); `DeployMultiambienteDockerTest`: com `Dockerfile.laravel`, `.env.docker`, `.gitignore` e `.env.example` da `main` e o override de exemplo removido, **25 casos a mais falham** (96 → 55 verdes de 105; os 9 vermelhos restantes são os de oráculo pendente CT-06/CT-30). Árvore restaurada (`git status --porcelain` vazio), 2026-10-05
- [x] Docs pt/en, CHANGELOG e README reconciliados — página pt/en com todas as âncoras do `04` v4 (CT-27..CT-32, CT-41, CT-42, CT-47 verdes); CHANGELOG `[Unreleased]` descreve o comportamento final (config/boot, coringas, lista de `build.args`, golden textual); READMEs com contagens por comando (75 specs, 177/206 arquivos, badge 1.944), 2026-10-05
- [x] `node converter.mjs` sem diff residual — rerodado após cada edição de página; `git status --porcelain` vazio depois do último, 2026-10-05
- [x] `git commit` — commits da branch até `d73804d` (wiki, código, testes, docs; o do veredito vem depois do `06`), 2026-10-05

## Revisão do Diff (step 9)

| ID | Passe | Achado | Destino | `P-nn` / CT | Rejeitado — motivo |
|---|---|---|---|---|---|
| RD-01 / CR-01 | eixos + genérico | `env('TRUSTED_PROXIES')` no `withMiddleware()` roda antes do `LoadEnvironmentVariables`: chave só no arquivo `.env` é ignorada (medido pelos dois revisores) | premissa → CT → correção: leitura via `config/kit.php` + `TrustProxies::at()` no `boot()` do provider; `bootstrap/app.php` volta ao da `main`; docs/`.env.example`/CHANGELOG reescritos | P-02 (alterada), P-13 (reescrita) / CT-21, CT-22 (costura por `refreshApplication`), CT novo da leitura só pelo `.env` | — |
| RD-02 | eixos | `private_ranges` (sinônimo em minúsculas do Symfony) passava pela lista e confiava em todas as faixas privadas | premissa → CT → correção: coringa acrescentado | P-19 / CT-20, CT-21 | — |
| RD-03 / CR-04 | eixos + genérico | o Symfony não valida CIDR: `172.18.0.0/16x` dá `TypeError` em todo request; `17x.18.0.1` silêncio; o docblock afirmava validação que não existe | premissa → CT → correção: validação IP/CIDR na classe, `descartados()` + `warning` no provider | P-18 / CT-20, CT novo do log | — |
| RD-04 / CR-02 | eixos + genérico | a página mandava `REVERB_SCHEME=https`/`REVERB_PORT=443` no `.env` para a rota pelo Traefik — quebra o broadcast servidor-para-servidor (`app` → `reverb:8090` em HTTP) | premissa → CT → correção (página pt/en e comentário do override) | P-20 / CT-30 (âncora) | — |
| RD-05 | eixos | `.env.docker` não estava em `CAMINHOS_DO_KIT`: CT-23/CT-24 viajariam sem o arquivo; `bootstrap/app.php` idem (resolvido pela mudança de RD-01, `app/Providers` e `config/kit.php` estão na lista) | premissa → CT → correção | P-23 / CT-37 (lista cobre `.env.docker`) | — |
| RD-06 | eixos | com a chave ausente, `Host: *.on-forge.com`/`.on-vapor.com` vira `'*'` (vendor, pré-existente) — o texto "nenhum proxy" é impreciso | texto: docblock da classe cita a exceção do vendor | — | parcialmente rejeitado como defeito: comportamento do framework anterior ao diff; só o texto muda |
| RD-07 | eixos | CT-34 (golden) e CT-46 (canário) viajam para o projeto instalado sem saída possível lá | premissa → CT → correção: os dois com `skip` fora da árvore | P-24 / CT-34, CT-46 | — |
| RD-08 | eixos | cabeçalho do override oferece `TRUSTED_PROXIES=*` sem a ressalva do loopback | correção de texto no exemplo | — (D7/P-21) | — |
| CR-03 | genérico | `*` confia em qualquer container da `my-network`, não só no Traefik; o loopback não isola da rede compartilhada; o CIDR da rede também não | premissa → CT → correção (página pt/en, D7) | P-21 / CT-41 (âncora `IP fixo`/`ipv4_address`; o CT-42 é recortado à matriz) | — |
| CR-05 | genérico | receita da Opção D não funciona: `app` não está na rede do llama; faltava `LLAMACPP_EMBED_URL` | premissa → CT → correção (dois overrides com rede própria `ia-compartilhada`) | P-22 / CT-31 (receita da D: `networks:` + `LLAMACPP_EMBED_URL`) | — |
| CR-06 | genérico | matriz: homol `8080` colide com o `llamacpp` (8080) do ambiente com `--profile ai`; `FORWARD_LLAMA/EMBED/MAILPIT_*` não cobertas | premissa → CT → correção (parágrafo das portas dos profiles) | P-22 / CT-30 (âncora `FORWARD_LLAMA_PORT`) | — |
| CR-07 | genérico | golden em JSON gerado pelo Compose 5.5.1 local é frágil à versão do CI (formato de `null`, `required`, durações) | premissa → CT → correção: fixture passa a ser cópia textual do base da `v0.44.0`, os dois lados gerados pelo mesmo CLI em tempo de teste | P-24 / CT-34 | — |
| CR-08 | genérico | CT-01/CT-36/CT-39 fazem `glob('docker-compose*.y*ml')` na raiz e pegam a cópia ativa ignorada pelo git de um checkout que seguiu a doc | teste (oráculo frágil) → CT-36 lista pelos arquivos **rastreados** (`git ls-files` na árvore; fora dela, exclui `docker-compose.override.yml`) | — / CT-36, CT-01, CT-39 | — |
| CR-09 | genérico | aviso de DNS citava `app` e `reverb` como nomes em risco; o risco real é o `nginx` resolver `app` e o `reverb` (se na rede) resolver `pgsql`/`redis` | premissa → CT → correção (página) | P-25 / CT-47 (âncoras `pgsql`, `redis`) | — |
| — | genérico | hipóteses rejeitadas pelo revisor: default do base alterado (não; `git diff main...HEAD -- docker-compose.yml` vazio), `ARG` sem default (ausente no `RUN`), `-p` × nome do router, bloco do `.env.docker` descomentado, `clear_env` do php-fpm, `private_subnets` minúsculo (inválido no Symfony), CRLF, health check com loopback, `skip` escondendo vermelho | — | — | registradas como rejeitadas |

## Conformidade com Rules

| Rule | Glob que casou | Aplicada / n.a. / violada | Evidência |
|---|---|---|---|
| `app.md` — papel por `ContextoDePapeis`; DTO em `app/Data`; painel por `Paineis::correnteOuPadrao()`; URL pública por `asset()` | `app/**` | n.a. | `app/Support/ProxiesConfiaveis.php`, `app/Providers/KitServiceProvider.php` (método novo) e `app/Console/Commands/KitUpdate.php` (linha nova) não tocam papel, DTO, painel nem URL de arquivo (`grep -c "ContextoDePapeis\|assignRole\|Storage::url\|Paineis::"` no diff deles → 0) |
| `support.md` — chave do `.env` se grava por `SubstituicaoEmArquivo::definirNoEnv()`/`definirLinhaNoEnv()` | `app/Support/**` | n.a. | a classe só **interpreta** a string que o provider lê de `config('kit.proxies_confiaveis')` (não chama `env()` nem grava nada): `grep -c "definirNoEnv\|File::put\|preg_replace\|env(" app/Support/ProxiesConfiaveis.php` → 0 |
| `testes.md` — helper usado por 2+ arquivos em `tests/Pest.php`; ausência filtra comentário; `toContain()` sem mensagem; caso que lê `docs/`/README/site pula fora da árvore; CHANGELOG pelo arquivo inteiro; `UNTESTED` é sobrevivente / `--mutate` só com duração plausível; ID de CT em docblock sem colchete | `tests/**` | aplicada | helpers locais a um arquivo só (`HelpersDeTesteTest` verde no lote 21/21); CT-02/CT-19/CT-35 afirmam ausência sobre texto sem comentário; nenhuma mensagem em `toContain()` (os casos usam `assertStringContainsString`/`toBeTrue` com mensagem); todo caso de docs/README/site/git com `naArvoreDoKit()` e mensagem sem `docs` (`RedeDeDocumentacaoTest` CT-10/CT-11 verdes); CT-28 lê o `CHANGELOG.md` inteiro; score do `--mutate` descartado por duração implausível, 4 mutantes manuais mortos; `ids-ct.sh` silencioso |
| `config.md` — vazio no `.env` nunca `(int) env()`; interruptor que abre superfície falha fechado; uma pergunta, uma dona | `config/**` | aplicada | `kit.proxies_confiaveis` guarda a **string crua** de `env('TRUSTED_PROXIES')` (nenhum `(int) env()`); ausente/vazia/inválida fecha (ninguém confiável — CT-20, CT-21, CT-49); a chave é a única dona da pergunta "em quem confiar" (`grep -rln "trustProxies\|TRUSTED_PROXIES" app config bootstrap` → `config/kit.php`, `app/Providers/KitServiceProvider.php` e `app/Support/ProxiesConfiaveis.php` — este só no docblock; `bootstrap/` nada) |
| `providers.md` — rota do kit nasce no `KitServiceProvider` com `web` explícito | `app/Providers/**` | n.a. | o diff não cria rota: `confiarNosProxiesDoEnv()` só chama `TrustProxies::at()` e loga |
| `commands.md` — chave do `.env` se grava por `SubstituicaoEmArquivo::definirNoEnv()`/`definirLinhaNoEnv()` | `app/Console/Commands/**` | n.a. | a mudança em `KitUpdate` é uma linha a mais em `CAMINHOS_DO_KIT` (`.env.docker`); nada grava `.env` |
| `css-filament.md` — utilitária de blade no CSS do kit; `@layer`; swap `fi-logo-*` | `app/Providers/**` | n.a. | nenhum CSS, blade ou asset no diff |
| `providers-filament.md` | `app/Providers/Filament/**` | n.a. | `app/Providers/Filament/InfraPanelProvider.php` entrou no diff só por duas citações em comentário recalculadas para `KitServiceProvider` (`ver-logs`, linha 463; `command-center`, 464) (`git diff main...HEAD -- app/Providers/Filament` só muda números em `*` de docblock) |
| `specs.md` — comportamento de vendor citado com `file:line` depois de ler; conferência por símbolo (`citacoes.sh`); citação de teste pelo ID entre aspas, nunca `arquivo:it:N` | `wikis/specs/**` | aplicada | `citacoes.sh` silencioso (exit 0) sobre `00`–`04`; todas as afirmações sobre `TrustProxies`, `Middleware::trustProxies()`, `ApplicationBuilder::withMiddleware`, `loadEnv` do Vite e Compose vêm de leitura/medição citada; `grep -c ':it:'` na wiki → 0 em todos os arquivos |

## Quality Gate

- **Ciclo**: 1 · **Veredito**: REPROVADO → especificação · **Data**: 2026-10-05
- **Relatório**: `06-relatorio-qa.md` (transcrito do retorno do `fw-qa-gate`; ver despacho 15)
- **Reciclagem do ciclo 1** (tudo destino 1, salvo QA-09 destino 2), 2026-10-05:
  - QA-01 → `03`: 50 IDs; conformidade rerodada (exit 0 com a linha `providers-filament.md`); CHANGELOG reescrito; checkbox do passo 1 reescrito como desvio.
  - QA-02 → mutação medida na classe final + 4 mutantes manuais com a suíte de 79; CT-20 ganhou `/32` e `/128` (04 + teste); a constante redundante saiu.
  - QA-03 → `01` (Natureza, P-01, Análise do bootstrap, D5, variáveis `VITE_*`, Modelo de Execução, Riscos, Channel de Log, passo 1 Logs, passo 3 Reverb, Mapeamentos, Impacto +2 linhas) e `02` (Superfície) com `*(alterado em 2026-10-05)*`.
  - QA-04 → aceito e registrado: a exceção (um `warning` por boot enquanto a chave estiver errada) entrou no comentário do canal `configuracoes` em `config/logging.php`; `01` Modelo de Execução e D5 reescritos.
  - QA-05 → CHANGELOG `[Unreleased]` pelo comportamento final (config/boot, validação, coringas, `.env.docker` no `kit:update`, golden textual).
  - QA-06 → `.env.docker`: IP fixo do Traefik no lugar da sub-rede.
  - QA-07 → 24 citações curtas de `docs/*/comecar/atualizando-o-projeto.md` recalculadas por script.
  - QA-08 → linha `providers-filament.md` (n.a.) e evidências de `app.md`/`support.md`/`config.md` refeitas.
  - QA-09 → método e docblock movidos para antes do docblock de `garantirRaizDeUrlSemPublic()`.
  - QA-10 → M131 e M183 com `sem matador`.
- **Ciclo 2**: *(a despachar)*

## Candidatos a Rule

<!-- Step 12. -->

## Auditoria Pré-Implementação

Entendimento confirmado: 2026-10-05 — sessão autônoma, pelas recomendações (o solicitante confirma ao ler o PR) — 2 rodadas (step 4; step 7 devolveu seis); perguntas: 0 fato (resolvidas por leitura/medição), 5 desenho (Q3–Q5, Q7, Q10), 6 requisito (Q1, Q2, Q6, Q8, Q9, Q11 — nenhuma bloqueia passo: cada uma implementada pela direção que falha fechado, como premissa)

### Perguntas da entrevista (step 4)

| Qn | Raia | Afeta | Pergunta | Recomendação adotada |
|---|---|---|---|---|
| Q1 | requisito | RQ-09, P-02 | o kit passa a ler `TRUSTED_PROXIES`? (estende o pedido) | sim, falha fechado — `00` |
| Q2 | requisito | RQ-12 | rota do Reverb no servidor: Traefik no mesmo hostname ou porta própria? | Traefik no mesmo hostname; bloco pronto e comentado; a outra rota continua — D4 |
| Q3 | desenho | RQ-11, P-03 | a cópia ativa do override entra no `.gitignore` do kit? | sim — D1 |
| Q4 | desenho | RQ-01 | a página nova fica em Começar ou em Operação? | Operação, `order: 6` — D2 |
| Q5 | desenho | P-02 | parsing de `TRUSTED_PROXIES` inline no bootstrap ou numa classe de `app/Support`? | classe, com teste unitário — D3 |
| Q6 | requisito | RQ-10, RQ-16 | portas "opcionais" ou obrigatórias e distintas? (da derivação) | obrigatórias e distintas — P-09 |
| Q7 | desenho | RQ-14 | `ARG X=`+`ENV` ou `ARG X` sem default? (da derivação) | sem default e sem `ENV` — P-12, medido |
| Q8 | requisito | RQ-12 | recorte da rota do Reverb: `/app/<chave>` ou subdomínio? (da derivação) | `/app/<chave>` + `/apps/<id>` — P-10 |
| Q9 | requisito | P-02 | `*` dentro de lista? (da derivação) | lista; `*` só sozinho — P-11 |
| Q10 | desenho | RQ-12 | delimitadores no bloco comentado do Reverb? (da derivação) | sim — D6 |
| Q11 | requisito | P-02, RQ-10 | `*` ao lado de porta em `0.0.0.0`? (da derivação) | `*` só com a porta em `127.0.0.1` — D7; depois do step 9 (CR-03): IP fixo do Traefik, `*` como risco aceito — P-21 |
| Q12 | requisito | RQ-07, RQ-09 | nome de serviço global na rede do Traefik (da 2ª rodada adversarial) | documentar e perguntar ao DevOps — P-14, P-25 |
| Q13 | desenho | P-18 | `descartados()`/aviso também para coringa sozinho e não-string? (do `04` v4) | sim para os dois — classe e CT-49/CT-50 |

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
| 1 | 7 | `general-purpose` (fallback: o `analista` não existe em `.claude/agents/`) — derivação do `04` lendo e seguindo `feature-test-design/SKILL.md` | opus | `01` (só paths/rotas/UI no prompt), `02` (só a Superfície), código como comportamento | `04` com 32 CT, 19 regras, 82 mutantes, 6 perguntas `Q?1–Q?6` (renumeradas Q6–Q11), 3 achados que contrariam o plano (Reverb `PathPrefix(/app)` captura o painel; `ARG X=`+`ENV` muda o build; portas "opcionais" não sobem) | 243,9 k tokens · 852 s | gravado verbatim do rascunho do scratchpad (`cp`), `git status`: só o `04` novo; amostragem: R7/R9/R13/R14 lidos, os três achados reproduzidos (`AppPanelProvider` em `/app`; `docker build` medido para o `ARG`; `ports` concatenam na sonda) |
| 2 | 7 | `fw-adversario-ct` — revisão adversarial do `04` | opus | `01`, `02`, código, conversa (hook `adversario-ct`) | 36 achados: 3 blockers (base ganhando `TRUSTED_PROXIES`; defaults do base alterados sem golden; default de faixas privadas), 8 altos, 18 médios, 7 baixos; RQ-02/03 (processo), P-01, P-07, P-09, P-11 sem cenário discriminante; `04` dessincronizado do `00` (Q7 citada, RQ-10/12/16 "abertas") | 109,9 k tokens · 430 s | leu só `00`, `04`, glossário e a reference da skill (declarado, coerente com o hook); 3 achados reproduzidos pela sessão (ADV-05: `env('X')` devolve bool para `true`; ADV-19: `config:cache` pula o `.env`; ADV-25: `check-ignore` sem `--no-index` ignora arquivo rastreado); **todos os 36 aceitos** — decisões da sessão: golden `tests/Kit/fixtures/compose-base.json` (ADV-02), coringas descartados em lista (ADV-20 → P-11 refinada, código alterado), `config:cache` como P-13 + doc (ADV-19) |
| 3 | 7 | `general-purpose`/opus (fallback do `analista`) — `04` v2 fechando os 36 achados, com as decisões da sessão no prompt | opus | `01`, código como comportamento | `04` v2: 43 CT, 20 regras, 127 mutantes, tabela `## Revisão Adversarial` com os 36; 8 pontos devolvidos à sessão — decididos: P-13 já existia; P-11 ganhou `PRIVATE_SUBNETS`; `REMOTE_ADDR` sozinho → `null` (linha nova no CT-20); R12 deixa de ser `@premissa` (Q1 = P-02); Q10 = D6 → CT-44 fecha a L3; segunda rodada adversarial despachada | 219,9 k tokens · 739 s | copiado verbatim do scratchpad; `grep -c` da sessão: 44 CT depois do CT-44, 0 `Q?`; citações conferidas pelo `citacoes.sh` |
| 4 | impl. | `fw-executor-ct` — `tests/Kit/ProxiesConfiaveisTest.php` (CT-20..CT-22), com a medição da costura G6 | sonnet | `01`, `02`; `app/` só para nomes; não edita `app/` nem a wiki (hook) | `tests/Kit/ProxiesConfiaveisTest.php`: 3 `it()` → 33 casos (CT-20 19 linhas, CT-21 11, CT-22 3), 33 verdes; costura G6 medida: `forgetInstance(Kernel)` refaz o `withMiddleware` (opção a); 1 vermelho de causa (a) corrigido (`Host` por `withServerVariables`); 0 divergências (b) | 87,4 k tokens · 122 s | `git status`: só o teste novo, `app/`/`bootstrap/` intactos (`git diff --stat` vazio); rerodado pela sessão: 33/33, 103 asserções; amostra: `forgetInstance`, `REMOTE_ADDR` por `withServerVariables`, UTF-8/LF |
| 5 | impl. | `fw-executor-ct` — `tests/Kit/DeployMultiambienteDockerTest.php` (CT-01..19, CT-23..44) + golden `tests/Kit/fixtures/compose-base.json` | sonnet | `01`, `02`; infra e docs só para nomes/caminhos | `tests/Kit/DeployMultiambienteDockerTest.php` (105 casos com datasets) + `tests/Kit/fixtures/compose-base.json`; 72 verdes, 33 vermelhos de causa (b) em CT-06/27/29/30/31 — todos sobre a **página** (âncoras que o `04` exige e o texto não tinha) e o `x-vite-args` que o `config` devolve; 2 ajustes de ambiente medidos (`ProgramFiles` para o plugin do Compose no Windows; `up=(compose …)` do script) | 230,5 k tokens · 684 s | `git status`: só teste + fixture novos; roteamento da sessão: CT-27/29/31 → página (índice da seção linka, trechos literais dos labels, três `.env`, títulos Opção A–D, `pgsql`/`redis` na D) — 96/105 depois; CT-06 (`x-`) e CT-30 (loopback/nota na matriz) → oráculo do `04` v3 + 2ª passada do executor; 7 mensagens de `skip` reescritas pela sessão (o `[CT-10]` de `RedeDeDocumentacaoTest` proíbe `docs` no `skip`) |
| 6 | 7 | `fw-adversario-ct` — segunda rodada sobre o `04` v2 (foco: CT novos/reescritos) | opus | `01`, `02`, código, conversa | 33 achados (1 blocker: golden que se certifica sozinho; 5 altos; 10 médios; 16 baixos; 1 malformado) + Q?2 (DNS na rede compartilhada → Q12/P-14); 4 fechamentos da rodada 1 reabertos (ADV-02, -05, -20, -28) e a sincronia do `04` quebrada de novo (contagens, M128 fora da tabela, 2ª `Regra:` no R7, L3) | 153,7 k tokens · 454 s | leu só `00`/`04`/glossário; 3 achados reproduzidos pela sessão: ADV2-07 (medido: `${VAR:-}` passa `""`, lista sem valor omite a ausente), ADV2-03 (`--profile '*'` funciona), ADV2-04 (`:-starter-kit-key` aceitaria vazio); **todos aceitos** com as decisões em P-14..P-17 e D8/D9; teto de 2 rodadas atingido — fechamento no `04` v3 sem nova rodada |
| 7 | 7 | `general-purpose`/opus (fallback do `analista`) — `04` v3 fechando os 33 achados da 2ª rodada + sincronia + 2 ajustes de oráculo do executor | opus | `01`, código como comportamento | `04` v3: 47 CT, 20 regras, 164 mutantes; CT-16 migrou para G2; CT-44 Esquema; CT-45/46/47 novos; L3/L7 retiradas; 5 pontos devolvidos — decididos: citação `forceRootUrl:73` aceita; `'*,'`→`null` confirmado na classe; CT-46 em R1 e CT-47 próprio aceitos | 236,1 k tokens · 876 s | copiado verbatim; `grep`: 47 CT, 20 `Regra:`, 164 M, 0 `Q?` provisória (as 4 ocorrências são históricas); `citacoes.sh` e `rastreabilidade.sh` silenciosos |
| 8 | impl. | `fw-executor-ct` — 2ª passada em `tests/Kit/ProxiesConfiaveisTest.php` (linhas novas de CT-20/21/22) | sonnet | `01`, `02`; `app/` só para nomes | 44 casos (CT-20 24 linhas, CT-21 16, CT-22 4), 44 verdes na 1ª execução; dataset × Exemplos sem linha de um lado só; 0 divergências | 67,9 k tokens · 45 s | `git status`: só o teste; `app/`/`bootstrap/` intactos; rerodado pela sessão 44/44; amostra por grep: `'*,'`, `8443`, `PRIVATE_SUBNETS` presentes |
| 9 | impl. | `fw-executor-ct` — 2ª passada em `tests/Kit/DeployMultiambienteDockerTest.php` + golden da `v0.44.0` com `--profile '*'` | sonnet | `01`, `02`; infra/docs só para nomes | parou no teto de 40 turnos com o arquivo íntegro (110 casos, 101 verdes); retomado por `SendMessage` com o estado medido; final: 122 casos, 114 verdes, 8 vermelhos (b) — CT-30 (células da matriz com crases) e CT-41 (frase sem nomear `TRUSTED_PROXIES`); golden regenerado da `v0.44.0` com 12 serviços, idempotente (md5 igual), `<raiz>` normalizado; IDs `04` × arquivo sem diferença | 216,0 k + 250,2 k tokens · 327 s + 276 s | `git status`: só teste + fixture; sessão corrigiu a página (células sem crases, frase nomeando a chave) → 122/122; guardas `RedeDeDocumentacaoTest`/`HelpersDeTesteTest` 21/21 |
| 10 | 9 | `fw-revisor-diff` — passe de eixos sobre `main...HEAD` sem `wikis/` | opus | `01`, `03`, conversa (hook `revisor-diff`) | 8 achados RD-01..RD-08 (3 altos: `.env` não lido no bootstrap; `private_ranges`; CIDR inválido = `TypeError`), 5 rejeitados, 6 não verificados declarados | 109,8 k tokens · 510 s | `git status --porcelain` igual antes/depois (só o `03` já modificado); 3 achados reproduzidos pela sessão (`afterResolving` antes do `LoadEnvironmentVariables` — `vendor/laravel/framework/src/Illuminate/Foundation/Configuration/ApplicationBuilder.php:withMiddleware():287`; `vendor/symfony/http-foundation/Request.php:private_ranges:658`; `vendor/symfony/http-foundation/IpUtils.php:checkIp4():87` sem validação); **todos aceitos** |
| 11 | 9 | `general-purpose`/opus cego (fallback: `/code-review` não existe neste host) — passe genérico sobre `main...HEAD` | opus | `01`, `03`, `06`, conversa (por prompt) | 9 achados CR-01..CR-09 (2 altos, coincidentes com RD-01/RD-04), 15 hipóteses rejeitadas com reprodução | 187,7 k tokens · 662 s | `git status --porcelain`: o `M bootstrap/app.php` staged que ele viu era o revert da **sessão** em curso, não dele; 2 achados reproduzidos (CR-05 `app.networks = [default]` no `config`; CR-06 porta 8080 do llama); **todos aceitos**; CR-08 roteado a teste |
| 12 | 9 | `general-purpose`/opus (fallback do `analista`) — `04` v4 com os CT das premissas P-18..P-25 (feature-test-design reinvocada só para os achados) | opus | `01`, código como comportamento | `04` v4: 50 CT, 21 regras, 200 mutantes (2 sem matador → L9, L11); CT-48/49/50 novos; CT-34 reescrito sobre o fixture textual; 9 pontos devolvidos — decididos: G7 com re-execução do `boot()` do provider; Q13 (coringa sozinho e não-string descartados **e** avisados — classe ajustada); L12 aceita (prova com a chave só no arquivo `.env` fica sem CT, a reprodução do step 9 é a evidência); CT-48 textual só na árvore; L9 sem arnês; CR-03 em CT-41 | 266,8 k tokens · 865 s | copiado verbatim; `grep`: 50 CT, 21 regras, 200 M, 0 `compose-base.json`; 6 citações com linha deslocada corrigidas pela sessão (`checkIp4:87`, `private_ranges:658`, `bootstrap:18`, `setTrustedProxyIpAddresses:67`); `rastreabilidade.sh` silencioso |
| 13 | impl. | `fw-executor-ct` — 3ª passada em `tests/Kit/ProxiesConfiaveisTest.php` (CT-20..22 novas linhas, CT-48, CT-49, CT-50 com G6/G7) | sonnet | `01`, `02`; `app/` só para nomes | 77 casos, 77 verdes em 2 iterações; dataset × Exemplos vazio; G7 medida: `boot()` inteiro estoura `DuplicateCheckNamesFound` → `Closure::call` do método privado, como `alinharConfiguracoesDoKit()` (o `04` foi ajustado) | 101,4 k tokens · 157 s | `git status`: só o teste; `app/`/`config/`/`bootstrap/` intactos; rerodado pela sessão 77/77; amostra: `refreshApplication`/`confiarNosProxiesDoEnv` presentes |
| 14 | impl. | `fw-executor-ct` — 3ª passada em `tests/Kit/DeployMultiambienteDockerTest.php` (CT-34 sobre o fixture textual, CT-46 skip, CR-08, âncoras P-20..P-25) | sonnet | `01`, `02`; infra/docs só para nomes | 128 casos, 127 verdes + 1 (b): CT-15 — a linha do cabeçalho do exemplo com `TRUSTED_PROXIES=*` não citava `127.0.0.1` na própria linha; sessão corrigiu o exemplo → 128/128; `compose-base.json`/`GOLDEN_COMPOSE_REGENERAR` apagados; IDs `04` × arquivo vazio | 125,6 k tokens · 289 s | `git status`: só o teste; rerodado pela sessão com `ProxiesConfiaveisTest` + guardas: 226/226 |
| 15 | 11 | `fw-qa-gate` — `feature-quality-gate` inteira, ciclo 1 | opus | conversa; recebeu só path da wiki, URL `http://127.0.0.1:8097`, `git diff --stat` e `{base}` = `main` | **REPROVADO → especificação**: 0 blocker, 3 Major (QA-01 alegações do `03`; QA-02 mutação medida na classe anterior; QA-03 `01`/`02` sem a marca do desvio do step 9), 6 Minor (QA-04 aviso por boot; QA-05 CHANGELOG velho; QA-06 `.env.docker` × página; QA-07 24 citações curtas; QA-08 conformidade; QA-09 docblock órfão), 1 cosmético (QA-10); 10/12 dimensões; J parcial e K2 não verificadas | 247,7 k tokens · 769 s | `git status --porcelain` antes vazio; depois só o `03` — e a diferença era a **sessão** (esta linha e o checkbox `git commit`), gravada em paralelo, conferida pelo conteúdo; o `06` **não pôde ser gravado por arquivo** (o agente não tem Write e o heredoc por Bash foi cortado): a sessão transcreveu **o texto entre `<<<06` e `>>>06` da resposta dele**, sem editar, desfazendo só o escape HTML do canal; todos os 10 achados aceitos e corrigidos (ver `## Quality Gate`) |
| — | pré-9 | Sem despacho — re-varredura da `## Superfície Livewire`: não exigida (nenhum componente, página ou widget no diff; `grep -rn "public function \|public \$" app/Filament app/Livewire` sobre o diff final: nenhuma linha nova — o único PHP novo é `app/Support/ProxiesConfiaveis.php`, estático, e uma linha em `bootstrap/app.php`) | sessão | — | — | — | — |
| — | 0–4 | Sem despacho — captura verbatim, decomposição, pesquisa por leitura direta (compose, Dockerfile, script, testes vizinhos, `KitUpdate`, `.gitattributes`, site), `search-docs` (trusted proxies, Reverb), `WebFetch` (Compose merge, Vite env, Traefik docker provider) e sonda local com `docker compose config` | sessão | — | pacote de pesquisa no `01` e nas medições acima | — | medições coladas acima |

## Blockers
- nenhum

## Desvios do Plano
- **Passo 1 (`TRUSTED_PROXIES`)**: a leitura saiu do `bootstrap/app.php` para `config/kit.php` + `KitServiceProvider::boot()` — RD-01/CR-01 do step 9 (o closure de `withMiddleware()` roda antes do `.env` ser carregado). Propagado: `01` passo 1 e tabela de variáveis *(alterado em 2026-10-05)*, ADR-02 do `02` (decisão, alternativa 1 e consequências reescritas), P-02/P-13 do `00`, docs pt/en, `.env.example`, CHANGELOG.
- **Passo 1**: a classe passou a validar IP/CIDR e a expor `descartados()`; o provider loga cada descarte em `configuracoes` — RD-02/RD-03/CR-04 (P-18, P-19, Q13). D5 ("nenhum log") caiu: o único log da feature é esse `warning`, por passo do PRD tratado como saída observável (CT-50).
- **Passo 2 (`Dockerfile`)**: `ARG` sem default e sem `ENV` — Q7/P-12 da derivação (era `ARG X=` + `ENV`).
- **Passo 3 (override)**: `build.args` em lista sem valor (P-15, ADV2-07); `:?` em `REVERB_APP_KEY`/`REVERB_APP_ID` (P-16); recorte `/app/<chave>`/`/apps/<id>` (P-10); marcadores `>>> <<<` (D6); cabeçalho cita o loopback e o IP fixo (RD-08, P-21); `TRAEFIK_ENTRYPOINT` cortado (Ponytail).
- **Passo 4 (`.env.docker`)**: as quatro `FORWARD_*` comentadas (P-09, ADV-09); `.env.docker` entrou em `CAMINHOS_DO_KIT` (P-23, RD-05).
- **Passo 5 (docs)**: página reestruturada pelas âncoras do `04` e pelos achados: trechos literais dos labels, três `.env`, títulos Opção A–D, portas obrigatórias e distintas (P-09), `REVERB_*` não mudam (P-20), `*` × rede compartilhada e IP fixo (P-21), Opção D com rede própria e portas de `ai`/`mail` (P-22), aviso de DNS (P-14/P-25), frase do cache (P-13); a seção `## Portas` mudou de título.
- **Passo 6 (testes)**: golden trocado de JSON gerado para cópia textual do base (P-24, CR-07); CT-34 e CT-46 só na árvore (RD-07); arquivos de Compose por `git ls-files` (CR-08).

## Notas de Implementação
- **`withMiddleware()` roda antes do `.env`**: o closure é `afterResolving(HttpKernel)` (`vendor/laravel/framework/src/Illuminate/Foundation/Configuration/ApplicationBuilder.php:withMiddleware():287`), e o Kernel é resolvido antes de `bootstrap()` → `LoadEnvironmentVariables`; `env()` ali só vê o ambiente do processo. No Docker funcionava por acidente: `env_file` + `clear_env = no` da imagem php-fpm. É o motivo de a chave viver no `config/kit.php` — e vale para qualquer chave futura que alguém queira ler no bootstrap.
- **`docker compose config` inlina o `.env` em `environment`** e devolve os extension fields `x-*` de topo; o JSON muda de forma entre versões do Compose — por isso o golden é textual e os dois lados são gerados pelo mesmo CLI.
- **Chave de label em mapa não interpola; em lista, sim** (Compose v5.5.1); `ports` concatenam entre base e override; `build.args` em lista sem valor omite a chave ausente do `.env`, `${VAR:-}` passa `""`.
- **`ARG X` sem default fica ausente no `RUN`; `ARG X=` fica `''`** (medido com `docker build --progress=plain`), e o Vite copia `VITE_*` vazia para o bundle.
- **O Symfony não valida IP/CIDR** em `setTrustedProxies()`: `172.18.0.0/16x` é `TypeError` em `substr_compare()` a cada request; `private_ranges` é sinônimo de `PRIVATE_SUBNETS`.
- **`refreshApplication()` limpa as fachadas antes do boot dos providers**: spy de `Log` tem de vir depois do refresh e o passo de boot tem de ser re-executado por `Closure::call` (o `boot()` inteiro re-registra os health checks do spatie/laravel-health e estoura `DuplicateCheckNamesFound`).
- **O guarda CT-43 de `DiagramasDaArquiteturaTest` lê qualquer linha da documentação com dois nomes de processo do `composer dev`** (`server`/`reverb`…): um label `…-reverb.loadbalancer.server.port` numa página en dispara; a linha saiu do snippet.
- **A constante `CORINGAS` era redundante**: `ehIpOuCidr()` já recusa `*`, `**`, `REMOTE_ADDR`, `PRIVATE_SUBNETS` e `private_ranges` (não são IP nem CIDR); um mutante manual que a removia era equivalente. Saiu (ponytail: `delete:`), e o docblock explica que o único filtro cobre coringa e erro de digitação. Achado ao fechar QA-02.
- **`ArquiteturaDoCodigoTest` (preset `php` do Pest) proíbe `var_export` em `app/`**: o primeiro `descartados()` usava `var_export($bruto, true)` para o não-string; a 1ª fatia da suíte `Kit` pegou (2 casos) e virou `(string) json_encode($bruto)` — mesmo oráculo (`true` → `'true'`).
- **Edição por Python no Windows grava CRLF**: `open(p, 'w')` sem `newline=` converteu `.env.docker`, `.env.example` e os `.md` da wiki para CRLF (`git ls-files --eol` → `w/crlf`), e três casos de `MysqlNoDockerTest` reprovaram porque `$` em regex `m` não casa antes de ``. Corrigido reescrevendo em modo binário; toda edição seguinte usa `newline='
'`. Registrado também na memória do agente.

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
- **Funcionou bem**: medir com `docker compose config` antes de escrever o plano (a sonda do step 3 virou a base de P-05/P-06 e evitou um override que não interpolava); a derivação do `04` a partir do `00` achou três erros do plano antes de existir código (Reverb capturando `/app`, `ARG X=`, portas "opcionais"); as duas rodadas adversariais e o step 9 pegaram o defeito mais sério (chave lida antes do `.env`) que os 166 testes verdes não viam.
- **Faltou no plano**: validar a premissa "`env()` no bootstrap lê o `.env`" com a mesma disciplina que as premissas do Compose receberam — era confronto código × afirmação do step 5 e passou como fato da doc do framework; a ADR-02 nasceu invertida por isso. Também faltou prever as âncoras de página que a `feature-test-design` exige (três `.env`, títulos Opção A–D): a página foi escrita antes do `04` e precisou de três retoques.
- **Custo**: 14 despachos (~2,6 M tokens de sub-agente), 4 versões do `04`, 3 passadas de executor por arquivo — a maior parte do custo veio de escrever código e página **antes** de o `04` estabilizar; na próxima, segurar a implementação até a revisão adversarial fechar.
