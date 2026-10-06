# Relatório de QA — feat/deploy-multiambiente-docker: Deploy com Docker, vários ambientes não produtivos no mesmo servidor, atrás do Traefik

> Requisito: `00-requisito.md` · Plano: `01-plano-acao.md`
> Perfil de esforço: completo (domínio sensível: `TRUSTED_PROXIES` define em quem a aplicação confia para `X-Forwarded-*`, o IP que vai para o rate limit e para a trilha de autenticação)
> Natureza da wiki: nova · Toca infra compartilhada: sim → `Dockerfile.laravel`, `config/kit.php`, `KitServiceProvider`, `KitUpdate::CAMINHOS_DO_KIT`, `config/logging.php` (`bootstrap/app.php` está igual ao da `main`) · Regressão: sim
> Independência: sub-agente fw-qa-gate/opus, sem acesso à conversa
> Cobertura: 10 de 12 dimensões verificadas ou provadas não aplicáveis · teto: APROVADO COM DÉBITO

## Veredito — Ciclo 2

**REPROVADO → especificação**

- Blocker: 0 · Major: 2 · Minor: 2 · Cosmético: 0
- Não verificadas: J (só em parte), K (K2). As causas estão em *Não Verificado*.
- `RQ` abertas: nenhuma formalmente. O `00` não mudou desde o ciclo 1. As 7 perguntas ao solicitante (Q1, Q2, Q6, Q8, Q9, Q11, Q12) continuam implementadas como `P-nn` na direção que falha fechado, e o PR precisa levá-las a ele.
- Ambiente: app em `http://127.0.0.1:8097` (`/up` 200) · Pest 5 · Docker Compose v5.5.1 · HEAD `ae1448e` · MCP: Boost não foi necessário (sem UI e sem banco); Playwright não usado (sem UI).

Dos 10 achados do ciclo 1, 7 foram reciclados por completo (QA-02, 04, 05, 06, 07, 09, 10) e 3 só em parte (QA-01, QA-03, QA-08). Restam passagens do `01` que contradizem o código, e as próprias correções do ciclo 1 deixaram números velhos no `03`. O código continua entregando o requisito. Os 79 + 128 casos estão verdes, as regressões vizinhas também, e retirar a constante `CORINGAS` não mudou comportamento (sonda abaixo). Há 2 achados novos (QA-11, QA-12), então o loop não convergiu: resta o ciclo 3, que é o último.

### Conferência dos achados do ciclo 1

| ID | Estado | Prova (ciclo 2) |
|---|---|---|
| QA-01 | ⚠️ em parte | (a), (b), (c) e (d) reciclados: `03:61` diz 50; `conformidade-rules.sh` exit 0; CHANGELOG reescrito; `03:12` virou desvio. A mesma classe de defeito reapareceu nos números que a reciclagem deslocou → QA-11 |
| QA-02 | ✅ (o texto: QA-11) | CT-20 tem `/32` e `/128` (`04:813-814` e no teste). O plugin dá **69 tested, 100.00 %, Duration 2.57 s** (37 ms por mutante, contra 11 s por execução do arquivo) e continua implausível; o `03:54` o declara como não-evidência |
| QA-03 | ❌ em parte | marcas *(alterado em …)* em D5, Modelo de Execução, Natureza, Análise do bootstrap, Variáveis, Riscos, Log, passos 1, 2 e 3, Mapeamentos e Impacto (+2 linhas). Seis passagens continuam sem a marca → QA-03r |
| QA-04 | ✅ fechado (aceito) | exceção registrada em `config/logging.php:152-154`, e o `01` D5/`:238-241` foi reescrito. Log real reconferido (dimensão D) |
| QA-05 | ✅ | `CHANGELOG.md:21-29`: config/boot, validação, coringas com `private_ranges`, `warning`, `kit:update` e `.env.docker` |
| QA-06 | ✅ | `.env.docker:84-86` fala do IP fixo do Traefik (`ipv4_address`), alinhado à página pt `:132-133` e en `:134-135` |
| QA-07 | ✅ | as 14 citações curtas distintas por idioma em `docs/{pt,en}/comecar/atualizando-o-projeto.md` batem (`sed -n "Np" … \| grep -F sym`): 28/28 ok |
| QA-08 | ⚠️ em parte | a linha `providers-filament.md` entrou e o `conformidade-rules.sh` sai com exit 0. Duas evidências refeitas não se reproduzem → QA-08r |
| QA-09 | ✅ | `KitServiceProvider.php:78-106`: o método novo e o docblock dele ficam antes do docblock de `garantirRaizDeUrlSemPublic()` (`:108-147`) |
| QA-10 | ✅ | `grep -cE '^\| M-?[0-9]+.*sem matador'` → 2. As outras contagens são 50 CT, 21 regras e 200 M |

## Achados

### QA-03r — O `01` ainda contradiz o código em seis passagens sem a marca · Major · destino 1
- **Dimensão**: L (L3) e A (`git diff` por passo)
- **Relacionado a**: passos 1, 2, 4 e 6 do PRD; D3; P-12; P-18; P-23; RD-01, RD-03
- **Esperado**: toda afirmação do PRD que o código final desmente está reescrita com *(alterado em …)*, e cada passo lista no **Path** os arquivos que o diff toca por ele.
- **Observado**:
  - `01:136-138`: *"Um `ARG` promovido a `ENV` no estágio `assets` é o caminho"* contradiz P-12 e o `Dockerfile.laravel` (ARG sem `ENV`).
  - `01:194` (D3): *"o bootstrap fica com uma linha"*. Hoje `git diff main...HEAD -- bootstrap/app.php` sai vazio.
  - `01:247` (Impacto): *"o passo 2 acrescenta `ARG`/`ENV`"*. É a mesma contradição com P-12, e o ciclo 1 não a listou.
  - `01:316` (Path do passo 1): lista `bootstrap/app.php`, que não mudou. Omite `config/kit.php`, `app/Providers/KitServiceProvider.php` e `config/logging.php`, que estão no diff por este passo. O Path do passo 4 (`01:441`) omite `app/Console/Commands/KitUpdate.php` (P-23).
  - `01:317` e `01:324-327`: *"com um método estático"* e *"Nenhuma validação de IP/CIDR: o Symfony aceita os dois e recusa lixo com exceção clara"*. A classe tem dois métodos públicos (`doEnv`, `descartados`) e valida IP/CIDR. O bullet de `:338` traz a marca, mas a frase velha continua de pé logo acima.
  - `01:516-518` (Filosofia): *"o parser do Symfony valida o CIDR"* e *"uma linha quando possível (o bootstrap)"*. As Notas do `03:243` e a ADR-02, alternativa 4, provam o contrário.
- **Repro**: `grep -n -E 'promovido a \`ENV\`|o bootstrap fica com uma linha|acrescenta \`ARG\`/\`ENV\`|\`bootstrap/app.php\`, \`.env.example\`|Nenhuma validação de IP/CIDR|com um método estático|parser do Symfony|uma linha quando possível' 01-plano-acao.md`, mais `git diff --name-only main...HEAD` comparado aos `**Path**` dos passos 1 e 4.
- **Destino**: 1
- **Ação exigida**: o step 10 reescreve as seis passagens com a marca de data e completa os Paths dos passos 1 e 4. Antes de fechar, rodar o `grep` acima na wiki inteira. A ADR-02 do `02` já está certa.

### QA-11 — Números da `## Verificação Final` que a reciclagem do ciclo 1 deixou velhos · Major · destino 1
- **Dimensão**: L (L6)
- **Relacionado a**: QA-01 e QA-02 do ciclo 1, CT-20, `d1b0657` (a constante saiu), `b92c147` (+2 linhas no CT-20)
- **Esperado**: todo `[x]` com número reproduz pelo comando sobre o código final. O lembrete da skill é procurar a cópia do número antes de fechar.
- **Observado**:
  - `03:34`, `03:43` e `03:51` dizem *"205 casos (77 + 128)"* e *"77/77"*. Hoje `XDEBUG_MODE=off php vendor/bin/pest tests/Kit/ProxiesConfiaveisTest.php --compact` → **79/79** (206 asserções), e o total é **207**. O próprio `03:54` e o `03:108` já dizem 79: o `03` se contradiz.
  - `03:49` (ponytail) diz *"4 métodos, 1 constante"*. Hoje `grep -nE 'function |const ' app/Support/ProxiesConfiaveis.php` → 5 métodos, nenhuma constante.
  - `03:57` diz *"`grep -c 'alterado em 2026-10-05' 01-plano-acao.md` = 8"*. Hoje o resultado é **18**.
  - `03:53` diz *"0 falhas pendentes sobre o código final"*, mas a suíte Kit+Tenancy é anterior a `b92c147` e `d1b0657`, que mudaram `KitServiceProvider`, `ProxiesConfiaveis`, `config/logging.php` e as docs de citação. O `03` não registra nova rodada depois disso.
  - `03:54` diz *"`ehIpOuCidr()` sempre `true` (3 falhas de 79)"*, alegado sobre a **classe final**. Na classe final esse mutante não cabe em 3 falhas. Sem a constante, todo coringa passa a ser aceito, e reprovam pelo menos 18 linhas do CT-20 (`* dentro de lista`, `*,*`, `*,`, `**`, `REMOTE_ADDR` ×3, `PRIVATE_SUBNETS` ×3, `private_ranges` ×2, `/16x` ×2, `17x`, `/33`, `/129`) e 8 do CT-49. O "3" só fecha com a constante presente e o `filter_var` removido, ou seja, a classe anterior a `d1b0657`. O mesmo item cita *"75 Mutations … 2.76s"* do plugin "antes da simplificação"; a rodada sobre a classe final dá 69 tested, 2.57 s.
- **Repro**: os comandos acima. Para o mutante, a leitura dos datasets em `tests/Kit/ProxiesConfiaveisTest.php:102-158` contra `ProxiesConfiaveis::separar()`/`ehIpOuCidr()`. Não executei o mutante, porque o gate não edita código.
- **Evidência**: `grep -n -E '\b(77|205)\b' 03-progresso.md` → `:34`, `:43`, `:51` e `:63`. O `:63` ("7 de 77") é medição datada da fiação, anterior às 2 linhas novas, e deve dizer isso.
- **Destino**: 1
- **Ação exigida**: atualizar `03:34/43/49/51/57` com a saída atual. Em `03:54`, separar o que foi medido em qual classe e remedir ou renomear o mutante `ehIpOuCidr`. Em `03:53`, declarar que a suíte Kit+Tenancy é anterior a `b92c147`/`d1b0657` ou registrar a nova rodada.

### QA-08r — Duas evidências de `## Conformidade com Rules` refeitas no ciclo 1 não se reproduzem · Minor · destino 1
- **Dimensão**: L (L4)
- **Observado**:
  - A linha `config.md` (`03:95`) diz *"`grep -rln "trustProxies\|TRUSTED_PROXIES" app config bootstrap` → `config/kit.php`, `KitServiceProvider.php` e `ProxiesConfiaveis.php`"*. Hoje saem **4** arquivos: entrou o `config/logging.php`, pelo comentário de QA-04.
  - A linha `support.md` (`03:93`) diz *"`grep -c "definirNoEnv\|File::put\|preg_replace\|env(" app/Support/ProxiesConfiaveis.php` → 0"*. Hoje o resultado é **2**: as linhas `:15` e `:65` citam `env()` em comentário. A afirmação de que a classe não chama `env()` é verdadeira; o número não é.
- **Repro**: os dois `grep`.
- **Ação exigida**: refazer as duas evidências com um comando que reproduza (por exemplo, filtrar comentário) e colar a saída real. O código cumpre as duas rules.

### QA-12 — Docblock do "Cabeçalho dos painéis" ficou órfão em `config/kit.php` · Minor · destino 2
- **Dimensão**: L (código × doc), a mesma classe de QA-09
- **Observado**: o bloco novo de `proxies_confiaveis` (`config/kit.php:161-173`) entrou **entre** o comentário `Cabeçalho dos painéis` (`:150-160`, *"Quem lê é `App\Support\CabecalhoDoPainel`"*) e a chave `'cabecalho'` (`:175`). Ficam dois `/* */` seguidos, e o primeiro não documenta a chave que vem depois dele. O ciclo 1 não viu porque olhou só o provider.
- **Repro**: `sed -n 150,176p config/kit.php`
- **Ação exigida**: mover o bloco de `proxies_confiaveis` para antes do comentário do cabeçalho, ou para depois do array `'cabecalho'`.

## Dimensões

| # | Dimensão | Status | Observação |
|---|----------|--------|------------|
| A | Cobertura do requisito | ⚠️ | `rastreabilidade.sh` exit 0; sem `07-tickets/`. Pelo `git diff` por passo, os Paths dos passos 1 e 4 não batem com o diff (QA-03r). Os arquivos fora de passo (citações em `InfraPanelProvider` e nas docs) são recálculo mecânico: não-defeito |
| B | Fronteiras e dados | ✅ | Reconferida depois da retirada da constante, por sonda `tinker`: `**` → null e descartado; `10.0.0.1,*` → `[10.0.0.1]`; `private_ranges`, `PRIVATE_SUBNETS`, `REMOTE_ADDR` e `**` em lista são descartados; `/32` e `::1/128` aceitos; `/129`, `/33`, `/+1` e `/ 1` descartados. Equivalente ao ciclo 1 |
| C | Matriz de permissão | n/a | Sem rota, policy ou gate no diff (inalterado desde o ciclo 1) |
| D | Observabilidade real | ✅ | `TRUSTED_PROXIES='10.0.0.1,172.18.0.0/16x,*' php artisan tinker …` gravou em `storage/logs/configuracoes-2026-10-06.log` dois `WARNING` `[KitServiceProvider@confiarNosProxiesDoEnv]` com context `{"item":…}`: canal, prefixo e nível certos, sem PII. A frequência por boot ficou registrada como exceção do canal (QA-04 fechado) |
| E | Performance | n/a | Nenhuma query nem rota nova |
| F | UX de erro | ✅ | Sem mudança desde o ciclo 1 (`:?` do Compose; aviso de log descritivo) |
| G | Tema e cor | n/a | `dark-mode.sh --mecanismo` exit 1 (Filament), mas não há UI no `01` nem no diff |
| H | Acessibilidade | n/a | Sem UI no `01` nem no diff |
| I | Segurança da superfície nova | ✅ | Coberto pelo step 9 (17 achados, 1 rejeitado em parte), e o ciclo 1 mediu ao vivo a fronteira de confiança. O que mudou depois é só a retirada da constante, provada equivalente na B |
| J | Regressão adjacente | ⚠️ não verificada em parte | Rodados um por um, todos verdes: CacheDeViews 9, MysqlNoDocker 28, DeployDockerLocal 5, UrlSemPrefixoPublic 18, KitUpdate 54, ArquiteturaDoCodigo 3, Site 68, Rede 20, Diagramas 528, Checklist 23 (+3 pulados), Constraint 13. Mais os que leem `logging.php`/provider: ConfiguracoesDoKit 43, PermissoesDeTelas 27, QualidadeDeCodigo 29. `pest --parallel --tia` não rodou |
| K | Adequação da suíte | ⏭️ | K1 `k1-oraculo-fraco.sh` exit 0 nos testes do diff. K2: 69 tested, 100.00 %, Duration 2.57 s, implausível, portanto não verificada. O driver existe (`php -m` → pcov, xdebug; `vendor/pestphp/pest-plugin-mutate`). Pela derivação estática, as linhas `/32` e `/128` matam `<=`→`<`. Revisão adversarial do `04`: feita (2 rodadas) |
| L | Consistência documental | ❌ | QA-03r, QA-08r, QA-11, QA-12. L1 `ids-ct.sh` exit 0 e o cabeçalho do `04` bate (50/21/200/2). L2 `citacoes.sh` exit 0; as citações completas `arquivo:símbolo:linha` do repo e as curtas de `atualizando-o-projeto` estão todas ok. L4 `conformidade-rules.sh` exit 0, com evidências velhas (QA-08r). L5: CHANGELOG, `.env.docker` e página alinhados. L6 `checkbox-sem-evidencia.sh` exit 0, mas os números não se reproduzem (QA-11). L7: glossário inalterado e coerente |

## Débitos Aceitos

- QA-04 (ciclo 1, Minor): um `warning` por boot enquanto `TRUSTED_PROXIES` tiver item inválido. Aceito pela sessão e registrado em `config/logging.php` e no D5 do `01`.

## Suspeitas Não Confirmadas

- Os mutantes manuais do `03:54` (`<=`→`<` com 2 falhas; filtro invertido com 41 de 79) e o "7 de 77" da fiação (`03:63`). Não dá para reproduzir sem editar código. O primeiro é coerente com os datasets; o segundo é medição anterior às 2 linhas novas do CT-20.

## Não Verificado

- J, passo 1 (`pest --parallel --tia`) e a suíte completa Kit+Tenancy sobre `ae1448e`. Motivo: o orquestrador proibiu `--parallel` e a suíte inteira por falta de memória no host. Rodei 14 arquivos nomeados, em série.
- K2, mutation score de `App\Support\ProxiesConfiaveis`. Motivo: Duration de 2,57 s para 69 mutantes é implausível (arnês do `pest-plugin-mutate` no Windows, mesmo com o `pestw.cmd`). O driver existe (prova acima).
- Sonda própria com `docker compose config` numa pasta temporária. Motivo: o hook do `qa-gate` nega `mkdir`. Coberta pelo CT-34 e pelos CTs de G2 de `DeployMultiambienteDockerTest`: 128/128, sem nenhum pulado.

---

## Histórico — Ciclo 1 (2026-10-05)

**REPROVADO → especificação**: Blocker 0 · Major 3 · Minor 6 · Cosmético 1. Cobertura 10/12 (J parcial, K2 implausível: 75 mutations, 93.33 %, 2.58 s). Independência: sub-agente fw-qa-gate/opus, sem acesso à conversa. O código entregava o requisito; a reprovação foi documental.

- **QA-01** · Major · 1 · L6. Alegações do `03` que não se reproduziam: "47 IDs" (eram 50); `conformidade-rules.sh` "exit 0" (era 1); o CHANGELOG "descreve o comportamento final" (falso); o passo 1 dizia "`bootstrap/app.php` chama `trustProxies`" (diff vazio).
- **QA-02** · Major · 1 · L6/K. Evidência de mutação vinda da classe e da suíte anteriores ao step 9 (27 mutations, 85.19 %); nenhuma evidência válida para a validação IP/CIDR. Pedido: matar à mão um mutante da validação.
- **QA-03** · Major · 1 · L3. `01`/`02` contradiziam o código sem a marca: D5 "nenhum log" (`01:195/285/339`, `02:131`); "uma vez no bootstrap" (`01:233`); `ARG` com default/`ENV` (`01:40/219/523`); `REVERB_PORT=443` (`01:411`); "CIDR da rede do Traefik" (`01:272`); Natureza/Análise com `bootstrap/app.php`; Impacto sem `ArquiteturaDoCodigoTest`, CT-66 e as 24 citações.
- **QA-04** · Minor · 1 · D. Um `warning` por boot quebrava o contrato do canal `configuracoes` (6 linhas em 3 boots).
- **QA-05** · Minor · 1 · L5. CHANGELOG `[Unreleased]` descrevia o desenho descartado (`bootstrap/app.php`, ambiente do processo) e omitia P-18/P-19/P-23.
- **QA-06** · Minor · 1 · L5. O `.env.docker` recomendava a sub-rede do Traefik, que a página e P-21 rejeitam.
- **QA-07** · Minor · 1 · L2. 24 citações curtas de `KitUpdate` em `docs/*/comecar/atualizando-o-projeto.md`, 9 linhas defasadas.
- **QA-08** · Minor · 1 · L4. Faltava a linha `providers-filament.md`; as evidências de `app.md`/`support.md`/`config.md` estavam velhas.
- **QA-09** · Minor · 2 · L. Docblock órfão em `KitServiceProvider` (dois `/** */` seguidos antes de `garantirRaizDeUrlSemPublic()`).
- **QA-10** · Cosmético · 1 · L1. "Sem matador: 2" no cabeçalho do `04` não saía do `grep -c` (M131/M183 sem o marcador).

Não verificado no ciclo 1: J passo 1 e suíte completa (memória do host); K2 (duração implausível); sonda `docker compose config` própria (hook nega `mkdir`). Suspeita não confirmada: "7 de 77" da fiação.
