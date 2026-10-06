# Relatório de QA — feat/deploy-multiambiente-docker: Deploy com Docker, vários ambientes não produtivos no mesmo servidor, atrás do Traefik

> Requisito: `00-requisito.md` · Plano: `01-plano-acao.md`
> Perfil de esforço: completo (domínio sensível: `TRUSTED_PROXIES` define em quem a aplicação confia para `X-Forwarded-*`, o IP que vai para o rate limit e para a trilha de autenticação)
> Natureza da wiki: nova · Toca infra compartilhada: sim → `Dockerfile.laravel`, `config/kit.php`, `KitServiceProvider`, `KitUpdate::CAMINHOS_DO_KIT`, `config/logging.php` (`bootstrap/app.php` está igual ao da `main`) · Regressão: sim
> Independência: sub-agente fw-qa-gate/opus, sem acesso à conversa
> Cobertura: 10 de 12 dimensões verificadas ou provadas não aplicáveis · teto: APROVADO COM DÉBITO

## Veredito — Ciclo 3

**APROVADO COM DÉBITO**

- Blocker: 0 · Major: 0 · Minor: 2 · Cosmético: 1
- Não verificadas: J (em parte) e K (falta o K2). As causas estão em *Não Verificado*.
- `RQ` abertas: nenhuma formalmente. O `00` não mudou desde o ciclo 1. As perguntas ao solicitante Q1, Q2, Q6, Q8, Q9, Q11 e Q12 continuam implementadas como `P-nn` na direção que falha fechado, e o PR precisa levá-las a ele.
- Ambiente: app em `http://127.0.0.1:8097` (`/up` 200) · Pest 5 · Docker Compose v5.5.1 · HEAD `ac610f6` · MCP: o Boost não foi necessário (não há UI nem banco) e o Playwright não foi usado (não há UI).

Desde o ciclo 2 (`ae1448e`) entraram dois commits, `b073926` e `ac610f6`. Eles tocam `config/kit.php`, só para reordenar o comentário, e a wiki (`01`, `03`, `06`). Os 4 achados do ciclo 2 estão fechados. O que sobra é texto do `03`, todo Minor ou cosmético, e nenhum ponto contradiz o requisito.

Dois achados são novos (QA-13, QA-14), então o loop não convergiu no sentido estrito. Mas este é o ciclo 3, o último do teto, e sem Blocker nem Major o veredito fica em `APROVADO COM DÉBITO`, que é o teto por cobertura.

O código continua entregando o requisito. Rodei em série e todos passaram: os 79 + 128 casos da feature e 13 arquivos vizinhos (lista na J).

### Conferência dos achados do ciclo 2

| ID | Estado | Prova (ciclo 3) |
|---|---|---|
| QA-03r | ✅ | O `grep` do ciclo 2 sobre o `01` só devolve passagens com a marca *(alterado em 2026-10-05)*: `01:140`, D3 `01:196`, `01:249`, Path do passo 1 `01:318`, `01:319`, `01:330-332`, Filosofia `01:521-524`. O Path do passo 4 (`01:446`) inclui `app/Console/Commands/KitUpdate.php`. `git diff --name-only main...HEAD` bate com os Paths dos passos 1 e 4. `grep -c 'alterado em 2026-10-05' 01-plano-acao.md` dá 26 |
| QA-11 | ✅ | `pest tests/Kit/ProxiesConfiaveisTest.php` dá **79/79, 206 asserções**, e `DeployMultiambienteDockerTest` dá **128/128**. Total 207, igual a `03:34`, `03:43` e `03:51`. `grep -nE 'function \|const ' app/Support/ProxiesConfiaveis.php` mostra 5 métodos e nenhuma constante (`03:49`). O `grep -c` de `03:57` dá 26. `03:53` declara que a rodada Kit+Tenancy é anterior a `b92c147`/`d1b0657` e lista o que foi rerodado. `03:54` remediu o mutante `ehIpOuCidr` na classe final: 26 de 79, que bate com o piso que o ciclo 2 derivou dos datasets (≥ 18 do CT-20 + 8 do CT-49). `03:63` data o "7 de 77" |
| QA-08r | ✅ | O `grep` de `support.md` com filtro de comentário dá **0**. O de `config.md` (`grep -rln "trustProxies\|TRUSTED_PROXIES" app config bootstrap`) dá **4 arquivos**, como `03:93` e `03:95` dizem |
| QA-12 | ✅ | O bloco de `proxies_confiaveis` (`config/kit.php:'proxies_confiaveis':162`) agora vem antes do comentário "Cabeçalho dos painéis", e esse comentário fica logo acima de `config/kit.php:'cabecalho':175`. Nenhuma citação viva aponta para as linhas deslocadas: `CitacoesDeCodigoTest` 3/3 |

### Conferência dos "em parte" do ciclo 1

| ID | Estado | Prova |
|---|---|---|
| QA-01 | ✅ (por QA-11) | `ids-ct.sh` exit 0 (50 IDs), `conformidade-rules.sh` exit 0, CHANGELOG conferido no ciclo 2. Resíduo cosmético no texto do checkbox: QA-15 |
| QA-03 | ✅ (por QA-03r) | Nenhuma afirmação do `01`/`02` contradiz o código sem a marca. Os resíduos de redação estão em QA-14 |
| QA-08 | ✅ (por QA-08r) | As 9 linhas de `## Conformidade com Rules` conferem com o código e com o `conformidade-rules.sh` |

## Achados

### QA-13 — O checkbox do `citacoes.sh` diz "exit 0 sobre `00`–`04`", mas o próprio `03` é acusado · Minor · destino 1
- **Dimensão**: L (L2, e a L6 sobre a alegação)
- **Relacionado a**: commit `ac610f6`, `## Verificação Final` do `03` (linha 60)
- **Esperado**: a alegação da `## Verificação Final` se reproduz pelo comando que ela cita.
- **Observado**: a linha 60 do `03` diz *"`citacoes.sh {wiki}` silencioso — vazio, exit 0 sobre `00`–`04` … o script acusa **4 citações só com linha dentro do próprio `06`**"*. O script devolve **8 linhas e exit 1**. Quatro são do `06` e outras quatro são da própria linha 60 do `03`, que repete as mesmas citações sem símbolo para explicá-las. A nota que explica o problema reproduz o problema dentro do `03`, que a sessão edita.
- **Repro**: `bash .claude/skills/feature-wiki/scripts/citacoes.sh wikis/specs/feat/deploy-multiambiente-docker/deploy-multiambiente-docker`
- **Evidência**: as quatro linhas `…/03-progresso.md:60: … — citação sem símbolo: use {arquivo}:{símbolo}:{linha}` (para os trechos de `config/logging.php`, `KitServiceProvider.php`, `tests/Kit/ProxiesConfiaveisTest.php` e `config/kit.php`), mais as quatro do `06` (linhas 27, 32, 62 e 77 do `06` do ciclo 2), e `exit 1`.
- **Severidade**: Minor, pela linha "L2 citação errada". Não usei a linha "L6 número irreproduzível" (Major), porque o que o checkbox atesta continua verdadeiro: nenhuma citação de código dos `00`–`04` está errada, e as quatro linhas extras são as mesmas quatro strings que a nota declara, citadas como evidência. O que está errado é o escopo da frase. Este `06` (ciclo 3) não traz citação só com linha, então, depois de gravado verbatim, o `06` deixa de ser acusado.
- **Destino**: 1
- **Ação exigida**: reescrever a linha 60 do `03` sem a forma `arquivo:linha` (por exemplo, "as quatro citações sem símbolo do `06` do ciclo 2") ou com `arquivo:símbolo:linha`. Rodar de novo o `citacoes.sh` e colar a saída real, que deve vir vazia com exit 0 depois que este `06` substituir o do ciclo 2.

### QA-14 — `bootstrap/app.php` ainda aparece como arquivo tocado em duas passagens · Minor · destino 1
- **Dimensão**: L (L6 sobre `## Despachos`, L3 sobre o `01`)
- **Relacionado a**: RD-01, P-02
- **Observado**:
  - Linha 230 do `03` (`## Despachos`, re-varredura da Superfície Livewire): *"sobre o diff final: … o único PHP novo é `app/Support/ProxiesConfiaveis.php`, estático, e uma linha em `bootstrap/app.php`"*. Hoje `git diff --stat main...HEAD -- bootstrap app config` mostra `bootstrap/` intocado e PHP alterado em `KitServiceProvider.php`, `KitUpdate.php`, `InfraPanelProvider.php`, `config/kit.php` e `config/logging.php`. A conclusão (nenhuma superfície Livewire) continua de pé: `git diff main...HEAD -- app/Filament app/Livewire | grep -E '^\+.*(public function |public \$)' | wc -l` dá 0.
  - `01:500` (passo 6): *"`tests/Kit/ProxiesConfiaveisTest.php` (parsing + bootstrap)"*. O arquivo testa parsing e o `boot()` do provider (G6/G7), não o `bootstrap/app.php`. A seção datada do step 5 (linha 193 do `03`, *"aparecer só onde é usada (`bootstrap/app.php`)"*) é registro histórico e pode ficar se ganhar a marca.
- **Repro**: `grep -n "bootstrap/app.php" 03-progresso.md 01-plano-acao.md`, e o `git diff --stat` acima.
- **Destino**: 1
- **Ação exigida**: corrigir a linha 230 do `03` com a lista real de PHP do diff final (ou datá-la como pré-step 9). Trocar "bootstrap" por "boot do provider" em `01:500`, com a marca de data.

### QA-15 — O checkbox de `conformidade-rules.sh` ainda diz "depois das 4 linhas" · Cosmético · destino 1
- **Dimensão**: L (L6)
- **Observado**: a linha 62 do `03` diz *"exit 0 depois das 4 linhas em `## Conformidade com Rules` (`app.md`, `support.md`, `testes.md`, `specs.md`)"*. Esse texto vem de `f2aa290` e não mudou. No ciclo 1 o script saía com exit 1 até entrar a linha `providers-filament.md` (QA-01/QA-08). Hoje a tabela tem 9 linhas e o exit 0 depende dessa quinta linha acrescentada, que o checkbox não menciona.
- **Repro**: `git show f2aa290:{wiki}/03-progresso.md | grep -n 'conformidade-rules.sh {wiki}'` e a contagem das linhas da tabela no `03` atual (9).
- **Destino**: 1
- **Ação exigida**: acrescentar a linha `providers-filament.md` (QA-01) ao checkbox, ou reescrevê-lo como "exit 0 com as 9 linhas da tabela".

## Dimensões

| # | Dimensão | Status | Observação |
|---|----------|--------|------------|
| A | Cobertura do requisito | ✅ | `rastreabilidade.sh` exit 0; não há `07-tickets/`. Pelo `git diff` por passo, os Paths dos passos 1 e 4 batem com o diff (QA-03r fechado). Os arquivos fora de passo (citações em `InfraPanelProvider` e nas docs) são recálculo mecânico: não-defeito |
| B | Fronteiras e dados | ✅ | O código da classe não mudou desde o ciclo 2 (`git diff ae1448e..HEAD -- app` vazio). Vale a sonda do ciclo 2, e o CT-20/CT-49 passa (79/79) |
| C | Matriz de permissão | n/a | Não há rota, policy nem gate no diff |
| D | Observabilidade real | ✅ | O provider e a classe não mudaram desde o ciclo 2. Vale o log real medido no ciclo 2: `WARNING [KitServiceProvider@confiarNosProxiesDoEnv]` no canal `configuracoes`, context `{"item":…}`, sem PII. O método está em `app/Providers/KitServiceProvider.php:confiarNosProxiesDoEnv:90` |
| E | Performance | n/a | Nenhuma query nem rota nova |
| F | UX de erro | ✅ | Nada mudou (`:?` do Compose; aviso de log descritivo) |
| G | Tema e cor | n/a | `dark-mode.sh --mecanismo` exit 1 (Filament), mas não há UI no `01` nem no diff |
| H | Acessibilidade | n/a | Não há UI no `01` nem no diff |
| I | Segurança da superfície nova | ✅ | Coberto pelo step 9 (17 achados, 1 rejeitado em parte), e o ciclo 1 mediu ao vivo a fronteira de confiança. A única mudança desde então é a ordem de um comentário em `config/kit.php` |
| J | Regressão adjacente | ⚠️ não verificada em parte | Rodados em série sobre `ac610f6`, todos verdes: ProxiesConfiaveis 79, DeployMultiambienteDocker 128, CitacoesDeCodigo 3, ConfiguracoesDoKit 43, VersaoNoRodape 46, BooleanoDoEnv 30, AlertaDeAlteracoesNaoSalvas 25, QualidadeDeCodigo 29, ArquiteturaDoCodigo 3, UrlSemPrefixoPublic 18, KitUpdate 54, DiagramasDaArquitetura 528, SiteDeDocumentacao 68, RedeDeDocumentacao 20. Os vizinhos de Docker (CacheDeViews, MysqlNoDocker, DeployDockerLocal) passaram no ciclo 2, e nenhum arquivo que eles leem mudou desde então. `pest --parallel --tia` não rodou |
| K | Adequação da suíte | ⏭️ | K1 `k1-oraculo-fraco.sh` exit 0 nos testes do diff. K2 não foi refeito: a classe é a mesma do ciclo 2 (69 tested, 100.00 %, Duration 2,57 s, implausível). O driver existe (`php -m` mostra pcov e xdebug; `vendor/pestphp/pest-plugin-mutate` está instalado). Os mutantes manuais da classe final, do `03` (linha 54), são coerentes com os datasets. Revisão adversarial do `04`: feita (2 rodadas) |
| L | Consistência documental | ⚠️ | QA-13, QA-14 e QA-15, todos Minor ou Cosmético. L1 `ids-ct.sh` exit 0; o cabeçalho do `04` não mudou desde o ciclo 2 (50 IDs). L2 `citacoes.sh` exit 1: 4 linhas no `06` do ciclo 2, que este `06` substitui, e 4 no `03` (QA-13). As citações de código de `00`–`04` estão todas certas. L3: QA-03r fechado; resíduo de redação em QA-14. L4 `conformidade-rules.sh` exit 0, e as 9 linhas conferem com o código. L5: CHANGELOG, `.env.docker` e docs pt/en não mudaram desde o ciclo 2. L6 `checkbox-sem-evidencia.sh` exit 0; os números de QA-11 se reproduzem, e a alegação da linha 60 do `03` não (QA-13). L7: o glossário não mudou e está coerente |

## Débitos Aceitos

- QA-04 (ciclo 1, Minor): um `warning` por boot enquanto `TRUSTED_PROXIES` tiver item inválido. Foi aceito pela sessão e está registrado em `config/logging.php` e no D5 do `01`.
- QA-13 (Minor), QA-14 (Minor) e QA-15 (Cosmético): texto do `03`/`01`, sem efeito no comportamento. Vão para o `03-progresso.md`.
- J em parte e K2 não verificado: débito de cobertura (*Não Verificado*).

## Suspeitas Não Confirmadas

- As 26 falhas de 79 do mutante `ehIpOuCidr()` sempre `true`, e os mutantes `<=`→`<` (2) e filtro invertido (41), registrados no `03` (linha 54). Não dá para reproduzir sem editar código. O 26 coincide com o piso derivado independentemente dos datasets no ciclo 2.

## Não Verificado

- J, passo 1 (`pest --parallel --tia`) e a suíte completa Kit+Tenancy sobre `ac610f6`. Motivo: o orquestrador proibiu `--parallel` e a suíte inteira por falta de memória no host. Rodei 14 arquivos em série; não há diff de código de comportamento desde o ciclo 2.
- K2, o mutation score de `App\Support\ProxiesConfiaveis`. Motivo: 2,57 s para 69 mutantes é implausível (o arnês do `pest-plugin-mutate` no Windows não executa de fato). O driver existe (prova na K).
- Sonda própria com `docker compose config` numa pasta temporária. Motivo: o hook do `qa-gate` nega `mkdir`. O CT-34 e os CTs da G2 cobrem esse ponto (128/128, nenhum pulado). O override e o base não mudaram desde o ciclo 1.

---

## Histórico — Ciclo 2 (2026-10-05)

**REPROVADO → especificação**. Blocker 0 · Major 2 · Minor 2 · Cosmético 0. Cobertura 10/12 (J em parte; K2 implausível, 69 tested, 2,57 s). HEAD `ae1448e`. Independência: sub-agente fw-qa-gate/opus, sem acesso à conversa. Dos 10 achados do ciclo 1, 7 fecharam e 3 só em parte.

- **QA-03r** · Major · 1 · L3/A. Seis passagens do `01` contradiziam o código sem a marca: `ARG` "promovido a `ENV`"; D3 "o bootstrap fica com uma linha"; Impacto com `ARG`/`ENV`; o Path do passo 1 com `bootstrap/app.php` e sem `config/kit.php`, provider e `logging.php`, e o Path do passo 4 sem `KitUpdate.php`; "um método estático" e "nenhuma validação de IP/CIDR"; a Filosofia com "o parser do Symfony valida".
- **QA-11** · Major · 1 · L6. Números do `03` que a reciclagem do ciclo 1 deixou velhos: 77/205 em vez de 79/207; "4 métodos, 1 constante"; `grep -c` = 8 (eram 18); suíte Kit+Tenancy anterior a `b92c147`/`d1b0657` sem declarar; o mutante `ehIpOuCidr` ("3 de 79") medido na classe anterior.
- **QA-08r** · Minor · 1 · L4. Duas evidências de `## Conformidade com Rules` que não se reproduziam: o `grep` de `config.md` dava 4 arquivos, não 3; o de `support.md` dava 2, não 0, por causa de comentário.
- **QA-12** · Minor · 2 · L. O bloco de `proxies_confiaveis` estava em `config/kit.php` entre o comentário "Cabeçalho dos painéis" e a chave `'cabecalho'`, deixando aquele comentário órfão.

Não verificado no ciclo 2: J passo 1 e suíte completa (memória do host); K2 (duração implausível); sonda `docker compose config` própria (o hook nega `mkdir`). Suspeitas: os mutantes manuais e o "7 de 77".

## Histórico — Ciclo 1 (2026-10-05)

**REPROVADO → especificação**. Blocker 0 · Major 3 · Minor 6 · Cosmético 1. Cobertura 10/12 (J em parte; K2 implausível: 75 mutations, 93.33 %, 2,58 s). Independência: sub-agente fw-qa-gate/opus, sem acesso à conversa. O código entregava o requisito; a reprovação foi documental.

- **QA-01** · Major · 1 · L6. Alegações do `03` que não se reproduziam: "47 IDs" (eram 50); `conformidade-rules.sh` "exit 0" (era 1); "o CHANGELOG descreve o comportamento final" (falso); "o passo 1 faz `bootstrap/app.php` chamar `trustProxies`" (diff vazio).
- **QA-02** · Major · 1 · L6/K. A evidência de mutação vinha da classe e da suíte anteriores ao step 9 (27 mutations, 85.19 %), e não havia evidência válida para a validação IP/CIDR. Pedido: matar à mão um mutante da validação.
- **QA-03** · Major · 1 · L3. O `01`/`02` contradiziam o código sem a marca: D5 "nenhum log"; "uma vez no bootstrap"; `ARG` com default/`ENV`; `REVERB_PORT=443`; "CIDR da rede do Traefik"; Natureza/Análise com `bootstrap/app.php`; Impacto sem `ArquiteturaDoCodigoTest`, CT-66 e as 24 citações.
- **QA-04** · Minor · 1 · D. Um `warning` por boot quebrava o contrato do canal `configuracoes` (6 linhas em 3 boots).
- **QA-05** · Minor · 1 · L5. O CHANGELOG `[Unreleased]` descrevia o desenho descartado e omitia P-18/P-19/P-23.
- **QA-06** · Minor · 1 · L5. O `.env.docker` recomendava a sub-rede do Traefik, que a página e P-21 rejeitam.
- **QA-07** · Minor · 1 · L2. 24 citações curtas de `KitUpdate` em `docs/*/comecar/atualizando-o-projeto.md`; 9 linhas defasadas.
- **QA-08** · Minor · 1 · L4. Faltava a linha `providers-filament.md`, e as evidências de `app.md`/`support.md`/`config.md` estavam velhas.
- **QA-09** · Minor · 2 · L. Docblock órfão em `KitServiceProvider`: dois `/** */` seguidos antes de `garantirRaizDeUrlSemPublic()`.
- **QA-10** · Cosmético · 1 · L1. "Sem matador: 2" no cabeçalho do `04` não saía do `grep -c` (M131/M183 sem o marcador).

Não verificado no ciclo 1: J passo 1 e suíte completa (memória do host); K2 (duração implausível); sonda `docker compose config` própria (o hook nega `mkdir`). Suspeita não confirmada: "7 de 77" da fiação.
