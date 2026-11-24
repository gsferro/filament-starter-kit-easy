# Relatório de QA — mapa-interativo-do-kit: Mapa interativo do kit com a skill archify

> Requisito: `00-requisito.md` · Plano: `01-plano-acao.md`
> Perfil de esforço: completo (superfície de UI com JS: diagramas do archify)
> Natureza da wiki: evolução · Toca infra compartilhada: sim → build do site, conferidores do site, página do catálogo, `.ai/rules/index.md`, contadores do README · Regressão: sim
> Independência: sub-agente fw-qa-gate/claude-opus-5-5, contexto novo sem a conversa
> Cobertura: 9 de 12 dimensões verificadas ou provadas não aplicáveis · teto: APROVADO COM DÉBITO

*(gravado pela sessão em 2026-11-23, a partir do texto que o `fw-qa-gate` devolveu no despacho #38; o relatório é do
HEAD `a624bff1`. O que a sessão fez com cada achado está no `03`, seção `## Quality Gate`.)*

## Veredito — Ciclo 1

**REPROVADO → especificação**

- Blocker: 0 · Major: 3 · Minor: 5 · Cosmético: 1
- Não verificadas: G (nível 3), J (TIA e suítes inteiras), K (K2). As causas estão em *Não Verificado*
- `RQ` abertas: RQ-09, recorte da Q6, sem implementação (vai para o teto); RQ-04, RQ-05, RQ-11, P-03 e P-09, recorte da Q11, **implementado pelo código** (QA-01)
- Ambiente: prévia `http://localhost:4321` do `site/` construído no `a624bff1` · Pest 5.3.7 · MCP indisponível: o `laravel-boost` não conectou e o Playwright MCP não está exposto. Usei o Playwright do `site/` via `node`

## Achados

### QA-01 — A Q11 está aberta e o mapa publica uma leitura dela: o `/admin` "sem tenant" desenha como disponível o que só abre com tenant ou com chave · Major · destino 1

- **Dimensão**: A
- **Relacionado a**: Q11 (aberta) · RQ-11, RQ-04, RQ-05, P-03, P-09 · D18 do `01` · R34/CT-37 e R30/CT-32–CT-33 do `04` · passos 2, 4 e 6
- **Esperado**: a Q11 pergunta "em que diagrama de modo ela entra?" e "o mapa a marca?", e está `aberta` no `00`. O `04` declara aberto esse recorte (`04-casos-de-teste.md:131`) e diz, na linha 2256, que desenhar o `TenantResource` no diagrama sem tenant "responderia à Q11, e ao contrário da recomendação dela". A D18 (`01-plano-acao.md:207`) põe no recorte bloqueado "qual conjunto se desenha para a peça registrada e indisponível". A recomendação (falha fechado) é "desenhada só nos diagramas do modo em que está disponível, e nunca como sempre disponível". Na raia requisito, a recomendação não autoriza implementar.
- **Observado**:
  - Os mapas de arquitetura `admin-resumo` e `admin-multi-tenancy-opt-in` saem com `modos: ["sem","com"]` e desenham, sem marca, o `TenantResource`, cujo acesso exige `kit.tenancy.enabled` (`app/Filament/Admin/Resources/Tenants/TenantResource.php:canAccess:107`). O `admin-resumo` desenha também o `HubDeAdministracao`, desligado de fábrica (`KIT_HUB=false`).
  - A página põe esses mapas na coluna "sem tenant" como "(compartilhado)" e diz que, no `/admin`, "o resumo do painel é igual nos dois modos".
  - É a leitura "onde está registrada, sem marca": uma das três que o `04` lista, e a oposta à recomendação.
  - Nenhum passo traz `**Bloqueado por**`, e o `03` diz que só "a marca" ficou estacionada.
  - Nada reprova isso: a linha `admin` do CT-37 tira a peça condicional da conta, e o `rastreabilidade.sh` sai com exit 0 porque nenhuma RQ está marcada `aberta — Q11`.
- **Repro**:
  1. `node -e` sobre `site/mapa/diagramas/admin-multi-tenancy-opt-in.json` → `modos: ['sem','com']`, e as peças incluem `…\Tenants\TenantResource`
  2. `curl -s http://localhost:4321/mapa/pt/admin-resumo.html | grep -o TenantResource | wc -l` → 4 (15 no `admin-multi-tenancy-opt-in`)
  3. `docs/pt/referencia/mapa-interativo.md`, linhas 32 e 56
- **Evidência**: as saídas acima. `grep -n -i "bloqueado por" 01-plano-acao.md 03-progresso.md` → nada. Na sonda de teclado do `admin-resumo`, o índice lista "Multi-tenancy (opt-in), 4 peças".
- **Destino**: 1 especificação
- **Ação exigida**: levar a Q11 ao mantenedor antes do PR e, até a resposta, não publicar a leitura escolhida. Isso quer dizer `**Bloqueado por** Q11` nos passos, ou publicar só o que vale nas três leituras. Com a resposta: Adendo, depois CT-32, CT-33 e a linha do CT-37 de volta, e só então a correção.

### QA-02 — Nenhum comando reproduz as "38 citações" da `## Verificação Final` · Major · destino 1

- **Dimensão**: L (L6)
- **Relacionado a**: `03-progresso.md:97`
- **Esperado**: todo `[x]` com número sai de um comando que o reproduz (Proibição 11)
- **Observado**: o `citacoes.sh` sobre as wikis que citam `tests/Pest.php` não dá 38 em nenhum corte:
  - 110 ocorrências simbólicas quebradas, 67 distintas;
  - nas 36 wikis que mencionam o arquivo, 109 "não está na linha" (66 únicas) e 78 "sem símbolo";
  - 48 símbolos, 27 arquivos.

  A substância se sustenta: elas já estavam quebradas na `main`, com saldo zero. A linha `tests/Pest.php:Process:48`, que o diff inseriu, deslocou tudo em 1: a citação de `usuarioDoKit():490` em `wikis/specs/feat/layout-compact/layout-compact/06-relatorio-qa.md:72`, que passava por coincidência num docblock, quebrou, e a de `:491` passou a casar.
- **Repro**: para cada wiki com `tests/Pest.php:` → `bash .ai/skills/feature-wiki/scripts/citacoes.sh {wiki} | grep tests/Pest.php`, contar; para cada citação, comparar a linha em `git show main:tests/Pest.php`
- **Evidência**: `grep -rn "38 citações" wikis/specs/feat/mapa-interativo-do-kit/mapa-interativo-do-kit/` → só `03-progresso.md:97`
- **Destino**: 1
- **Ação exigida**: trocar o "38" pela saída real do comando, junto com o comando, em `03-progresso.md:97`, o único arquivo que o repete

### QA-03 — O `01` e o `02` afirmam o que o código não faz, sem marca `*(alterado em …)*`, e números divergem entre eles · Major · destino 1

- **Dimensão**: L (L3)
- **Esperado**: PRD e ADR batem com o código ou levam a marca de alteração; o desvio não fica só no `03`; a mesma medida tem um valor só na wiki
- **Observado**:
  - `site/mapa/pt-BR.json` (`01:191`, `01:364`, `01:373`, e na Fronteira do `04:184`) → o arquivo é `site/mapa/interface.json`. O desvio está só no `03:9` e no `03:327`.
  - `obterArchify(): Promise<string>` (`01:369`) → a função é síncrona (`site/mapa/archify.mjs:obterArchify:41`). Desvio só no `03:329`.
  - `--quality {showcase|standard}` (`01:456`) → o perfil vai em `site/mapa/gerar.mjs:quality_profile:466`. Desvio só no `03:331`.
  - Comparativo do `/admin`: `01:277`, `01:344`–`347` e `01:457` → ver o QA-01.
  - Passo 8 com dois arquivos de guarda (`01:514`) → são cinco. Desvio só no `03:62`–`69`.
  - D18 e o esquema da peça (`01:207`, `01:398`–`401`): "o modelo guarda a condição de disponibilidade" (`condicao?`) → nenhuma das 284 peças de `pecas.json` a tem. O desvio não está registrado em lugar nenhum.
  - ADR-07 "26 marcos lado a lado" (`02:372`) → a linha do tempo tem 30 eventos em 20 datas desde o `12ed91e0`, o commit em que a frase entrou.
  - Comparativo: ~2,35 MB (`01:99`) × 2,56 MB (`02:14`); o publicado tem 2.457.513 B.
  - `color-contrast` no tema claro do comparativo: cinco (`01:107`) × seis (`02:272`).
- **Repro**:
  - `grep -rn "pt-BR\.json\|Promise<string>\|--quality\|26 marcos\|2,35 MB\|2,56 MB" {wiki}`;
  - `node -e` contando `condicao` em `pecas.json` → 0, e `events.length` da timeline → 30.
- **Evidência**: o grep lista todas as cópias, nos três arquivos citados acima
- **Destino**: 1
- **Ação exigida**: corrigir, com a marca, o `01` (99, 107, 191, 207, 364, 369, 373, 398–401, 456, 514), o `02` (14, 272, 372) e o `04:184`. Para os números, medir de novo e deixar um valor só.

### QA-04 — O posicionador aceita opção desconhecida e cai no padrão; o erro de uso sai sem `[mapa]` · Minor · destino 3

- **Dimensão**: B (e F)
- **Relacionado a**: P-14, R40/CT-49, Q24
- **Esperado**: pela P-14, a ferramenta "recusa o argumento sem valor válido, em vez de assumir um", e todo erro leva o prefixo `[mapa]`
- **Observado**:
  - Executado: `--tentativa 0` (erro de digitação) passa calado pela conferência de argumentos, porque o posicionador (`site/mapa/posicionar.mjs`, `posicao`, no commit `a624bff1`) só procura `--tentativas`.
  - Executado: sem slug, sai `uso: …`, sem o prefixo `[mapa]`.
  - Lido no código, não executado, porque gravaria na árvore: com um slug real, a ferramenta segue com 200 tentativas e grava o layout.
  - O gerador recusa token desconhecido.
  - As partições do CT-49 não cobrem esses dois casos.
- **Repro** (sem efeito, em `site/`):
  1. `node mapa/posicionar.mjs nao-existe --tentativa 0` → "o diagrama não existe", exit 2
  2. Controle: `… --tentativas 0` → `[mapa] ERRO --tentativas: espera um inteiro positivo, e recebeu "0"`
  3. `node mapa/posicionar.mjs` → `uso: node mapa/posicionar.mjs <slug> …`
- **Destino**: 3
- **Ação exigida**: a `feature-test-design` acrescenta ao CT-49 as partições "opção desconhecida" e "slug ausente", o teste fica vermelho, e só então vem a correção

### QA-05 — O `03` diz que o piso de 6,5 px "fica com o conferidor do mapa (CT-B do `05`)", e nenhum CT-B o mede · Minor · destino 1

- **Dimensão**: L (L6)
- **Observado**: no `site/verifica-mapa.mjs` e no `05` não há "piso", nem "6,5", nem medida de fonte. Medi na prévia o menor texto visível no carregamento (1440×900): 86 de 87 diagramas pt ficam em 6,5 px ou acima; a linha do tempo fica em 6,31 px. Os números da ADR-07 conferem; a guarda alegada não existe.
- **Repro**: `grep -n "piso\|6[.,]5" site/verifica-mapa.mjs 05-casos-de-teste-browser.md` → nada
- **Ação exigida**: corrigir o `03:13`. A medição é da sessão e não está no CI. Se o piso deve ser protegido, criar um CT-B.

### QA-06 — Perguntas da raia requisito foram fechadas pela sessão, com estados fora do template · Minor · destino 1

- **Dimensão**: A (auditoria do requisito)
- **Observado**: Q12 "assumida como P-11"; Q23 e Q24 "decididas pela sessão". Os estados válidos são `aberta`, `respondida no Adendo N` e `retirada — {motivo}`. Q23 e Q24 não estão no `## Decisões de Desenho` do `01`.

  Por que é Minor, e não Major como o QA-01: a Q12 virou premissa registrada (P-11), um mecanismo que a skill prevê, com as duas exceções a sinalizar no PR. Q23 e Q24 têm cara de desenho. Na Q11, ao contrário, a própria wiki declara o recorte bloqueado, e o código o implementa.
- **Ação exigida**: a Q12 volta a `aberta`, com a P-11 apresentada ao mantenedor no PR; a Q23 e a Q24 passam a decisões de desenho no `01`

### QA-07 — A página do mapa nomeia 2 dos 5 arquivos de guarda · Minor · destino 1

- **Dimensão**: L (L5)
- **Observado**: `docs/pt/referencia/mapa-interativo.md:128` e `docs/en/referencia/mapa-interativo.md:127` citam só o `MapaInterativoTest` e o Tenancy. O `desenvolvendo-o-kit.md` cita os cinco.
- **Ação exigida**: alinhar "Como é conferido" / "How it is checked" nos dois idiomas

### QA-08 — Glossário: "Ligação (en: link)" colide com "Sem vínculo (en: no link)" · Minor · destino 1

- **Dimensão**: L (L7)
- **Observado**: `wikis/glossario.md:53` × `wikis/glossario.md:13`. O "Não confundir com" não cita o outro termo, e `docs/en/recursos/multi-tenancy.md` usa "no link" no sentido de "Sem vínculo".
- **Ação exigida**: acrescentar "Sem vínculo" ao "Não confundir com", ou trocar o equivalente em inglês

### QA-09 — Texto do `03` defasado ou contraditório · Cosmético · destino 1

- **Dimensão**: L (L6/L4)
- **Observado**:
  - `03:3`: o estado ainda diz "a revisão do diff roda sobre `main...168d1827`".
  - `03:90`: o comando nomeia 2 arquivos e a evidência fala de 5.
  - `03:98`: diz "silencioso", mas o `ids-ct.sh` sai com exit 1 e 8 linhas.
  - `03:179`: diz que "o motivo do pulo não escreve o caminho", e `tests/Kit/MapaInterativoDiagramasTest.php:markTestSkipped:37` escreve "site/, docs/". Hoje esse arquivo está fora do gatilho do `[CT-10]`.
- **Ação exigida**: corrigir as quatro linhas

## Matriz de Rastreabilidade

| RQ/P | Cláusula ou premissa | Passo PRD | CT | CT-B | Código | Resultado | Veredito |
|------|----------------------|-----------|----|------|--------|-----------|----------|
| RQ-11 | separada com e sem tenant | 2, 4, 6, 8 | CT-11–13, 17–19, 22, 37, 38, 43, 45 | CT-B01 | `admin-*.json` (sem,com) | o `/app` se divide; o `/admin` publica uma leitura da Q11 | ❌ recorte aberto implementado (QA-01) |
| RQ-04, RQ-05 | todas as features | 2, 5 | CT-01, 05, 06, 28–30, 35, 36, 41, 44, 45 | CT-B01 | `pecas.json` | feature desligada de fábrica desenhada sem condição | ❌ (QA-01) |
| P-03, P-09 | afirmação = código; o igual sai uma vez | 2, 6, 8 | CT-06, 08–13, 17–19, 37, 38 | CT-B01 | `admin-*.json` | o `/admin` sem tenant mostra uma tela que o código fecha | ❌ (QA-01) |
| RQ-09 | os fluxos do kit | 3, 4 | CT-24, CT-39 | CT-B01 | 20 fluxos curados | a página não afirma uma lista fechada | ⏳ aberta (Q6), sem implementação — teto |
| demais RQ e P | — | `## Cobertura do Requisito` | — | — | — | `rastreabilidade.sh` exit 0; RQ-15: 0 arquivo de aplicação no diff; RQ-01: análise em `61db6c1a`, antes do archify em `ae5d20fa` | OK |

## Dimensões

| # | Dimensão | Status | Observação |
|---|----------|--------|------------|
| A | Cobertura do requisito | ❌ | QA-01, QA-06 — `rastreabilidade.sh` exit 0, cego à Q11; sem `07-tickets/` |
| B | Fronteiras e dados | ⚠️ | QA-04; JSON quebrado e caixa vazia cobertos (CT-47, CT-50, CT-B08, verdes); trecho com `<form…>` renderiza como texto |
| C | Matriz de permissão | n/a | `## Autorização` do `01`: nenhuma; diff com 0 arquivo em `app/`, `routes/`, `config/`; site estático; publicação só da `main` (D31, `pages.yml`) |
| D | Observabilidade | ✅ | `gerar.mjs --saida {scratchpad} admin-resumo login`: 5 linhas `[mapa]` no formato do `01`, sem PII; mesmos 904.396 B que a prévia serve |
| E | Performance | ✅ | 174 HTML, 147 MB em `dist/mapa`, 0 sidecar; 0,84 a 2,46 MB por HTML; ~0,5 s por render; guardas 332 casos em 200 s |
| F | UX de erro | ⚠️ | QA-04; mensagens em pt, nomeiam o culpado; diagrama inexistente → 404 |
| G | Tema e cor | ⏭️ | `--mecanismo` exit 1; nível 1 `dark-mode.sh` exit 0 (141 arquivos); nível 2 CT-B05 nos dois temas, 0 reprovação; nível 3 não verificado |
| H | Acessibilidade | ✅ | CT-B05: 42 HTML, 11 tipos, 2 tolerados (P-11, declarados); `verifica-acessibilidade.mjs`: 108 páginas, 0 serious/critical; Tab alcança barra, nós, busca e índice |
| I | Segurança | ✅ | coberto pelo step 9: 28 achados, 0 rejeitados por inteiro, 3 em parte. Além dele: CI em `push`/`pull_request`; archify fixado em 40 hex e conferido; 1.560 trechos sem segredo; HTML escapado; nenhuma rota |
| J | Regressão adjacente | ⏭️ | ancestrais por ID: 909 casos verdes; CT-B da ancestral verdes; `verifica-links` com 0 quebrado em 4.170 links; TIA e suítes inteiras não rodadas |
| K | Adequação da suíte | ⏭️ | K1 `k1-oraculo-fraco.sh` exit 0; K2 não verificado (JS); revisão adversarial do `04`: feita, em 2 rodadas |
| L | Consistência documental | ❌ | QA-02, 03, 05, 07, 08, 09. L1 `ids-ct.sh` exit 1: 8 linhas (CT-B01–B08), rejeitadas pela D17, porque rodam no conferidor (87×2, 0 reprovação). Cabeçalho 53·39·260·7 confere; dataset do CT-49 = Exemplos. L2 `citacoes.sh` exit 0 (controle: acusa em outras wikis). L4 `conformidade-rules.sh` exit 0. L6 `checkbox-sem-evidencia.sh` exit 0; conferem 332, 2.775, 231/271, net −356, Pint e 91 pacotes; não conferem 38 e o piso. L8 n/a — sem `08` (`roteiro-manual.sh` exit 0) |

## Débitos Aceitos

- Nenhum aceito nesta execução. Os Minor e o Cosmético (QA-04 a QA-09) são débito candidato; aceitar é decisão da sessão, com registro no `03`.

## Suspeitas Não Confirmadas

- Nos nós `<g role="button">` do SVG, o estilo computado não tem `outline` nem `box-shadow` ao receber foco. O archify pode desenhar o foco num elemento filho; sem olhar a tela, não confirmei.
- O contraste do texto dentro do SVG não foi medido. O axe não o confere, e essa limitação está declarada na página.

## Não Verificado

- **G, nível 3**: Playwright MCP não exposto nesta sessão, e o hook nega gravar captura.
- **J, TIA e suítes `Kit`/`Tenancy` inteiras contra a baseline**: não rodei. Pela baseline do `03` levam cerca de 55 min, e há uma execução do orquestrador em andamento; o item continua `[ ]` no `03:91`. O build num clone raso no Linux (`03:92`) também não foi verificado.
- **K2**: o código de produção é JS (`site/mapa/*.mjs`). O `pest-plugin-mutate` está instalado e `php -m` mostra PCOV e Xdebug, mas ele só muta PHP, e `git diff --name-only main...HEAD -- app` sai vazio. Não há ferramenta de mutação JS no projeto, e o gate não planta mutante.
- **MCP `laravel-boost`**: não conectou (timeout). Conferi o console pelo CT-B01 e pelas sondas Playwright.
