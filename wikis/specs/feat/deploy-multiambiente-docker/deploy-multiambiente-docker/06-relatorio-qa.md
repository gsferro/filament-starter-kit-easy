# Relatório de QA — feat/deploy-multiambiente-docker: Deploy com Docker, vários ambientes não produtivos no mesmo servidor, atrás do Traefik

> Requisito: `00-requisito.md` · Plano: `01-plano-acao.md`
> Perfil de esforço: completo (domínio sensível: `TRUSTED_PROXIES` define em quem a aplicação confia para `X-Forwarded-*`, o IP que vai para o rate limit e para a trilha de autenticação)
> Natureza da wiki: nova · Toca infra compartilhada: sim → `Dockerfile.laravel`, `config/kit.php`, `KitServiceProvider`, `KitUpdate::CAMINHOS_DO_KIT` (`bootstrap/app.php` está igual ao da `main`) · Regressão: sim
> Independência: sub-agente fw-qa-gate/opus, sem acesso à conversa
> Cobertura: 10 de 12 dimensões verificadas ou provadas não aplicáveis · teto: APROVADO COM DÉBITO

## Veredito — Ciclo 1

**REPROVADO → especificação**

- Blocker: 0 · Major: 3 · Minor: 6 · Cosmético: 1
- Não verificadas: J (só em parte), K (K2) — as causas estão em *Não Verificado*
- `RQ` abertas: nenhuma formalmente. Há 7 perguntas ao solicitante em aberto (Q1, Q2, Q6, Q8, Q9, Q11, Q12), todas já implementadas como `P-nn` na direção que falha fechado. Esse é o fluxo `P-nn` + pergunta, então não conta como achado. Mas o PR precisa levá-las ao solicitante, e a Q6 contradiz a nota ¹ do texto original.
- Ambiente: app em `http://127.0.0.1:8097` (`/up` 200) · Pest 5 · Docker Compose v5.5.1 · MCP: Boost não foi necessário (sem UI e sem banco); Playwright não usado (sem UI)

O código entrega o requisito. Os 205 casos e as regressões vizinhas estão verdes, o base fica byte a byte igual ao da `main` e o fixture é igual ao da tag `v0.44.0`. A reprovação é documental: o `01`/`02` e o `03` afirmam coisas que o código final desmente.

## Achados

### QA-01 — Alegações do `03` que não se reproduzem · Major · destino 1
- **Dimensão**: L (L6)
- **Esperado**: todo `[x]` com número ou estado reproduz pelo comando citado.
- **Observado**:
  - (a) `## Verificação Final` diz *"`ids-ct.sh` … (47 IDs do `04` ⊆ 2 arquivos)"*. O `04` tem **50** IDs: `grep -cE '^[[:space:]]*(Cenário|Esquema do Cenário): \[CT-[0-9]+\]' 04-casos-de-teste.md` → 50.
  - (b) A Verificação Final diz *"`conformidade-rules.sh {wiki} main` silencioso — vazio, exit 0"*. Hoje sai com **exit 1** (ver QA-08), porque o commit `8587836` tocou `app/Providers/Filament/InfraPanelProvider.php` depois da checagem.
  - (c) A Verificação Final diz *"CHANGELOG `[Unreleased]` descreve o comportamento final (config/boot …)"*. É falso (ver QA-05).
  - (d) No passo 1, *"[x] `bootstrap/app.php` chama `trustProxies(at: …)` antes do `append`"*. `git diff main...HEAD -- bootstrap/app.php` sai vazio: o checkbox fechado descreve o desenho descartado no step 9.
- **Repro**: os comandos acima, mais `bash .claude/skills/feature-wiki/scripts/conformidade-rules.sh {wiki} main`.
- **Ação exigida**: corrigir no `03` as quatro linhas com a saída real. Para (d), reescrever o item do passo 1 como desvio (o `config/kit.php` mais o `KitServiceProvider`). O `grep -rn "47 IDs" {wiki}` só encontra o `03:61`.

### QA-02 — Evidência de mutação do `03` vem de uma classe e de uma suíte anteriores · Major · destino 1
- **Dimensão**: L (L6) / K
- **Relacionado a**: P-11, P-18, P-19, R10, CT-20, CT-49
- **Esperado**: o `[x]` do `--mutate` traz o score, a duração e os sobreviventes do código final, ou mutantes manuais mortos pela suíte final.
- **Observado**: o `03:54` registra *"27 Mutations, 4 uncovered, 23 tested, 85.19 %, 1.06 s"*. A mesma linha de comando hoje dá **75 mutations, 5 uncovered, 70 tested, 93.33 %, Duration 2.58 s**. Os "4 mutantes manuais" (*"21 falhas de 33"*) foram medidos contra a suíte de 33 casos e a classe de antes do step 9, quando ainda não existiam `ehIpOuCidr()` nem `descartados()`. Para a validação de IP/CIDR, que é a parte nova, não há evidência de mutação válida.
- **Repro**: `XDEBUG_MODE=coverage cmd //c "$(cygpath -w .claude/skills/feature-wiki/scripts/pestw.cmd)" tests/Kit/ProxiesConfiaveisTest.php --mutate --path=app/Support/ProxiesConfiaveis.php --no-tia`
- **Evidência**: `Mutations: 5 uncovered, 70 tested · Score: 93.33% · Duration: 2.58s` (37 ms por mutante, sendo que uma execução do arquivo leva 11,3 s). Isso é implausível, então vale "Não Verificado".
- **Ação exigida**: trocar a linha pela saída atual, declarando a duração implausível, e matar à mão ao menos um mutante da validação (o limite `<=` do prefixo, ou `ehIpOuCidr` sempre `true`) com a suíte de 77 casos.

### QA-03 — `01` e `02` contradizem o código sem a marca *(alterado em …)* · Major · destino 1
- **Dimensão**: L (L3)
- **Observado** (código final × texto):
  - Log: o código emite `Log::channel('configuracoes')->warning(...)` (`KitServiceProvider::confiarNosProxiesDoEnv`). O texto contradiz em quatro lugares, e o desvio existe só no `03`:
    - `01:195` (D5, "Nenhum channel de log");
    - `01:285` ("Nenhum log (D5) … chamado dentro de `withMiddleware()`");
    - `01:339` ("Logs: nenhum (D5)");
    - `02:131` ("classe estática … chamada no bootstrap").
  - `01:233` diz *"roda uma vez no bootstrap (não por request)"*. Ela roda no `boot()` do provider, a cada request no php-fpm e a cada comando artisan (ver QA-04).
  - Os `ARG` estão sem default e sem `ENV` (P-12), mas o texto diz outra coisa em três lugares: `01:40` ("`ARG` entra com default vazio"), `01:219` ("default vazio no `ARG`") e `01:523` (`ARG`/`ENV`).
  - `01:411`: o comentário do Reverb ainda manda `REVERB_PORT=443`/`REVERB_SCHEME=https`. Isso contradiz P-20 e o próprio override.
  - `01:272` ("preferir o CIDR da rede do Traefik") contradiz P-21 e a página.
  - `01:15` e `01:143`: a Natureza e a Análise ainda dão `bootstrap/app.php` como a infra tocada e o ponto de entrada. A tabela `## Impacto em Features Existentes` não previu o que a suíte mediu: `ArquiteturaDoCodigoTest` (`var_export`), `DiagramasDaArquiteturaTest` CT-66 (citações deslocadas) e as 24 citações de QA-07. Pela Regressão Condicional, isso é divergência entre o previsto e o medido.
- **Repro**: `grep -n -E 'D5|Nenhum log|uma vez no bootstrap|default vazio|ARG\`/\`ENV\`|REVERB_PORT=443|CIDR da rede do Traefik|chamada no bootstrap' 01-plano-acao.md 02-decisoes-arquiteturais.md`
- **Ação exigida**: o step 10 reescreve cada passagem com a marca de data (`01` e `02`) e acrescenta à tabela de Impacto os três efeitos medidos.

### QA-04 — O aviso de descarte se repete a cada boot e quebra o contrato do canal · Minor · destino 1
- **Dimensão**: D
- **Relacionado a**: P-18, R21, CT-50, `01:233`
- **Esperado**: o comentário do canal em `config/logging.php` (`'configuracoes'`) diz *"Nada por ABERTURA de tela — um info por request é o ruído que a nota … mediu em 1,1 MB/dia"*.
- **Observado**: com um item inválido, cada boot grava um `warning` por item: cada request no php-fpm, cada `schedule:run` e cada comando. Canal, prefixo `[Classe@Método]`, nível e context estão certos; sem PII.
- **Repro**: rodei duas vezes `TRUSTED_PROXIES='10.0.0.1,172.18.0.0/16x,*' php artisan tinker --execute '…'` e mais uma vez com a mesma chave. Resultado: `grep -c confiarNosProxiesDoEnv storage/logs/configuracoes-2026-10-06.log` → 6 (3 boots × 2 itens).
- **Ação exigida**: decidir a frequência (uma vez por processo ou cache, ou aceitar e registrar a exceção no comentário do canal) e corrigir o `01:233`.

### QA-05 — O CHANGELOG `[Unreleased]` descreve o desenho descartado · Minor · destino 1
- **Dimensão**: L (L5)
- **Observado**: `CHANGELOG.md:21-23` diz *"lida em `bootstrap/app.php` … com a configuração em cache ela precisa estar no ambiente do processo"*. P-02 e P-13 desmentem as duas coisas. Faltam a validação IP/CIDR com aviso (P-18), `private_ranges` (P-19) e `.env.docker` no `kit:update` (P-23). O CT-28 só confere as palavras-chave, por isso passa.
- **Repro**: `git diff main...HEAD -- CHANGELOG.md`
- **Ação exigida**: reescrever a entrada pelo comportamento final.

### QA-06 — O `.env.docker` recomenda a sub-rede que a página rejeita · Minor · destino 1
- **Dimensão**: L (L5)
- **Observado**: `.env.docker:83-84` diz *"`*` SO com as portas abaixo no loopback — senao, a sub-rede do Traefik"*. A página (`docs/pt/operacao/deploy-docker-multiambiente.md:132-133`, en `:134-135`) e P-21 dizem que *"o CIDR da rede inteira não resolve"*: só o IP fixo do Traefik fecha.
- **Repro**: `grep -n -iE 'sub-?rede|IP fixo' .env.docker docs/*/operacao/deploy-docker-multiambiente.md`
- **Ação exigida**: alinhar o comentário a P-21 (IP fixo do Traefik).

### QA-07 — 24 citações curtas de `KitUpdate` ficaram 9 linhas defasadas · Minor · destino 1
- **Dimensão**: L (L2)
- **Observado**: a feature inseriu 9 linhas em `CAMINHOS_DO_KIT`. A recalculação ("20 citações") pegou só a forma completa. Em `docs/pt/comecar/atualizando-o-projeto.md:69-75` e `docs/en/…:70-76`, as 12 citações curtas por idioma continuam com as linhas da `main`: `:preVoo:464` (hoje 473), `:vincularKit:524` (533), `:arquivosAlterados:630`, `:mostrarResumo:749`, `:isInteractive:428`, `:prepararBranch:769`, `:revisarEAplicar:818`, `:relatarComposerJson:1003`, `:CAMINHOS_SO_RELATORIO:367`, `:marcarVersao:1112`, `:encerrar:1037` e `:desvincularKit:533`, todas +9. Nenhum guarda confere a forma curta.
- **Repro**: para cada `` `:sym:N` `` dos dois arquivos, `sed -n "Np" app/Console/Commands/KitUpdate.php | grep -F sym`. As 24 falham.
- **Ação exigida**: somar 9 às 24 citações nos dois idiomas.

### QA-08 — `## Conformidade com Rules` incompleta e com evidência velha · Minor · destino 1
- **Dimensão**: L (L4)
- **Observado**:
  - `conformidade-rules.sh` → exit 1: *"`.ai/rules/providers-filament.md` casa `app/Providers/Filament/InfraPanelProvider.php` e não tem linha"*. O código cumpre a rule: só mudaram números de citação em comentário.
  - As linhas `app.md` e `support.md` citam `bootstrap/app.php` e *"a classe só lê `env('TRUSTED_PROXIES')"`*, mas a classe não chama `env()`.
  - Na linha `config.md`, o grep alegado devolve 3 arquivos, não 2 (`ProxiesConfiaveis.php` também aparece).
- **Ação exigida**: incluir a linha `providers-filament.md` (n.a.) e refazer as três evidências.

### QA-09 — Docblock órfão em `KitServiceProvider` · Minor · destino 2
- **Dimensão**: L (código × doc)
- **Observado**: o docblock de `confiarNosProxiesDoEnv()` entrou **entre** o docblock longo de `garantirRaizDeUrlSemPublic()` ("Por que rede de segurança…") e o método. Hoje há dois `/** */` seguidos em `app/Providers/KitServiceProvider.php:~70-127`. O primeiro não pertence a nada, e `garantirRaizDeUrlSemPublic()` ficou sem documentação.
- **Repro**: `sed -n 90,145p app/Providers/KitServiceProvider.php`
- **Ação exigida**: mover o método novo e o docblock dele para antes do docblock existente.

### QA-10 — Cabeçalho do `04`: "Sem matador: 2" não sai do comando · Cosmético · destino 1
- **Dimensão**: L (L1)
- **Observado**: `grep -cE '^\| M-?[0-9]+.*sem matador' 04-casos-de-teste.md` → 0. As linhas M131 e M183 usam `—`, não o marcador. As outras três contagens batem: 50, 21, 200.
- **Ação exigida**: marcar M131 e M183 com `sem matador` na coluna do matador.

## Dimensões

| # | Dimensão | Status | Observação |
|---|----------|--------|------------|
| A | Cobertura do requisito | ✅ | `rastreabilidade.sh` exit 0; sem `07-tickets/`. O `git diff` por passo bate. O passo 1 ainda lista `bootstrap/app.php`, que ficou fora do diff de propósito (QA-03). RQ-03 fica pendente no passo 7, por desenho. Os arquivos fora de passo (citações em `InfraPanelProvider`, docs de autenticação, roteiro, configurações e 3 testes) são recálculo mecânico: não-defeito |
| B | Fronteiras e dados | ✅ | Dataset CT-20 (EP exaustiva) mais sonda por tinker: `/032`, `/-1`, `/ 8`, `/129` IPv6, `%lo`, `*,*`, `" * "`, `**`, tab. Todos falham fechado ou validam. `0.0.0.0/0` é aceito como CIDR explícito (não é defeito) |
| C | Matriz de permissão | n/a | Sem rota, policy ou gate no diff (`git diff --name-only main...HEAD \| grep -E '^(routes\|app/Policies\|app/Filament\|app/Livewire\|resources/)'` → nada) |
| D | Observabilidade real | ⚠️ | Log real lido (`configuracoes-2026-10-06.log`): canal, prefixo, nível e context certos, sem PII. Achado QA-04 (repetição por boot) |
| E | Performance | n/a | Nenhuma query nem rota nova: parse O(n) de uma string no boot |
| F | UX de erro | ✅ | `:?` do Compose nomeia a chave e diz o que fazer (`TRAEFIK_HOST`, `REVERB_APP_KEY`/`ID`, em pt). Aviso de log descritivo |
| G | Tema e cor | n/a | `dark-mode.sh --mecanismo` exit 1 (Filament, classe `dark`), mas não há UI no `01` nem no diff |
| H | Acessibilidade | n/a | Sem UI no `01` nem no diff |
| I | Segurança da superfície nova | ✅ | Coberto pelo step 9: `## Revisão do Diff (step 9)`, 17 achados (RD-01..08, CR-01..09), 1 rejeitado em parte (RD-06), mais as hipóteses rejeitadas. Além disso: sem rota nova, mass assignment, upload, `DB::raw` nem dado sensível em resposta. Fronteira de confiança medida ao vivo: `curl` com `X-Forwarded-Proto/Host/For` forjados em `/app` → `Location: http://127.0.0.1:8097/app/login`, cookie sem `secure` (ausente = ninguém confiável) |
| J | Regressão adjacente | ⚠️ não verificada em parte | Arquivos vizinhos, um por um, todos verdes: CacheDeViews 9, MysqlNoDocker 28, DeployDockerLocal 5, UrlSemPrefixoPublic 18, Diagramas 528, KitUpdate 54, Site 68, Rede 20, ArquiteturaDoCodigo 3, Checklist 23 (+3 pulados), Constraint 13, DuasRotasDeEntrega 3. Base igual ao da `main`; fixture igual ao `git show v0.44.0:docker-compose.yml`. O passo 1 (`--parallel --tia`) não rodou. Impacto previsto × medido divergente (QA-03) |
| K | Adequação da suíte | ⏭️ | K1 `k1-oraculo-fraco.sh` exit 0 nos 5 testes do diff. K2: 75 mutações, 70 mortas, 5 uncovered (linha da constante), 93.33 %, Duration 2.58 s, implausível, portanto não verificada (QA-02). Revisão adversarial do `04`: feita (2 rodadas) |
| L | Consistência documental | ❌ | QA-01, 02, 03, 05–10. L1 `ids-ct.sh` exit 0 (contagem do cabeçalho: QA-10); L2 `citacoes.sh` exit 0 na wiki (docs: QA-07); L4 `conformidade-rules.sh` exit 1 (QA-08); L6 `checkbox-sem-evidencia.sh` exit 0, números reproduzidos (QA-01/02). Os que bateram: 205 casos, 114/88/570 da regressão, 226, `grep -c 'alterado em 2026-10-05'` = 8. L7: glossário ganhou "Override de exemplo" e "Cópia ativa", coerentes com a página |

## Débitos Aceitos

- nenhum (ciclo 1; a sessão decide)

## Suspeitas Não Confirmadas

- O "7 de 77 falham com a chamada comentada" (`03:63`) não pôde ser reproduzido sem editar o código. A contagem é plausível (CT-22, CT-48 e CT-50 dependem da fiação).

## Não Verificado

- J, passo 1 (`pest --parallel --tia`) e suíte completa Kit+Tenancy (3.631 + 486). Motivo: o orquestrador proibiu `--parallel` e a suíte inteira por falta de memória no host. Rodei só os arquivos nomeados.
- K2, mutation score de `App\Support\ProxiesConfiaveis`. Motivo: Duration de 2,58 s para 70 mutantes é implausível (arnês do `pest-plugin-mutate` no Windows). Prova de que o driver existe: `php -m` → `pcov`, `xdebug`; `vendor/pestphp/pest-plugin-mutate` presente.
- Sonda própria com `docker compose config` numa pasta temporária. Motivo: o hook do `qa-gate` nega `mkdir`. Coberta pelos CT de G2, que rodaram de verdade: 128/128, sem nenhum pulado.
