# Relatório de QA — feat/diagramas-da-arquitetura: Diagramas da arquitetura do kit no README e no site

> Requisito: `00-requisito.md` · Plano: `01-plano-acao.md`
> Perfil de esforço: completo (o site renderiza os diagramas por JavaScript no cliente; o `kit:install` mexe em credencial)
> Natureza da wiki: nova · Toca infra compartilhada: sim → README/`docs/`/`site/`, `kit:arte`, `kit:install` (`CustomizadorDaInstalacao`, `SenhaDoAdministrador`, `SubstituicaoEmArquivo`), `tests/Pest.php`, `ci.yml` · Regressão: sim
> Independência: sub-agente fw-qa-gate/opus, sem acesso à conversa
> *(alterado em 2026-09-29: step 10 do ciclo 2, só nas citações — o conteúdo do ciclo 1 não muda. As que apontam código que o ciclo 2 moveu ou apagou viraram referência histórica, "linha N no ciclo 1"; as de `app/`, que o ciclo 2 não tocou, ganharam o símbolo que o `citacoes.sh` pede)*
> Cobertura: 10 de 12 dimensões verificadas ou provadas não aplicáveis (J e K rodaram só em parte) · teto: APROVADO COM DÉBITO

## Veredito — Ciclo 1

**REPROVADO → especificação**

- Blocker: 0 · Major: 7 · Minor: 5 · Cosmético: 2
- Não verificadas: J (parcial), K (parcial) — as causas estão em *Não Verificado*
- `RQ` abertas: nenhuma (o `00` não tem a coluna `Estado`; as 43 premissas estão como "Assumido", e as 43 aparecem no `04`)
- Ambiente: app em `http://127.0.0.1:8000` (HTTP 200) · Pest 5.1.1 · pest-plugin-mutate 5.0.2 · PCOV e Xdebug carregados · Playwright MCP indisponível; o MCP do Boost falhou ao conectar

## Achados

### QA-01 — O 01, o 02, o 04, o CHANGELOG e a página ainda descrevem a feature de antes da 2ª passada do step 10 · Major · destino 1
- **Dimensão**: L3/L5 · **Relacionado a**: RQ-25, RQ-26, RQ-36, passos 5, 17, 18, 23; ADR-05, ADR-06, ADR-08
- **Esperado**: o PRD e as ADRs dizem o que o código faz, e todo desvio vem marcado.
- **Observado**: o `01` afirma que o DG-10, o DG-19 e o DG-20 ficaram sem guarda (`01:750-756`, `:1375-1383`, `:1427-1434`) e que o teste do CT-105 não existe (`01:82`, `:1600-1605`, `:1681-1683`). Também dá "47 regras, 105 cenários, 266 mutantes" e "33 commits" (`01:1712`). O código tem `tests/Kit/GuardasDosDiagramasTest.php` (CT-106..CT-111, CT-115, CT-116, com 47 testes) e `[CT-105]` em `tests/Kit/DiagramasDaArquiteturaTest.php`, linha 5035 no ciclo 1. O `04` tem 53 regras e 116 cenários, e `git log --oneline origin/main..HEAD | wc -l` dá 36. A ADR-06 ainda diz "o que ficou sem guarda do fato" (`02:399-402`). A ADR-05 (`02:296`) e a ADR-08 (`02:456`) justificam um DG-20 "flowchart + sequência — o que acontece quando eu ligo isso", sem marca de alteração, mas o DG-20 publicado é só a sequência de `/app/{tenant}`, e a justificativa que o RQ-25 exige não bate com ele. O `04` põe as R48–R53 na costura `tests/Kit/DiagramasDaArquiteturaTest.php` (`04:259`) e ainda diz que "o teste [do CT-105] não existe" (`04:27-30`). O CHANGELOG fala em "411 casos" e "35" (`CHANGELOG.md:40-41`), sem o `GuardasDosDiagramasTest`. A página nomeia só `tests/Kit/DiagramasDaArquiteturaTest.php` como guarda (`docs/{pt,en}/referencia/arquitetura-em-diagramas.md:7`).
- **Repro**: `grep -n "não foi implementada\|ainda não existe\|47 regras" {wiki}/01-plano-acao.md`; `grep -n "CT-10[6-9]\|CT-11[0-6]" tests/Kit/GuardasDosDiagramasTest.php`
- **Ação exigida**: refazer o step 10 sobre o delta `35c3eef`/`99c0722` nos arquivos `01`, `02` (ADR-05, 06, 08), `04` (cabeçalho e costuras), CHANGELOG e na frase da página, marcando cada alteração com *(alterado em …)*.

### QA-02 — As alegações do 03 não se reproduzem · Major · destino 1
- **Dimensão**: L6
- **Observado**:
  - A `## Verificação Final` diz "DiagramasDaArquitetura 411/411; …Tenancy 35/35". O medido é 412 e 45 (`vendor/bin/pest {arquivo} --compact --no-tia`), e o `GuardasDosDiagramasTest` (47) não aparece.
  - O mesmo item diz "suíte vermelha, 1 falha CT-27", e `## Blockers` mantém o A-04 aberto (`03:156`, `:548`). Mas `CoberturaDeTestesTest` e `CitacoesDeCodigoTest` passam (23/23), e a suíte serial completa também.
  - O item do `ids-ct.sh` diz "uma linha só" e cola duas logo abaixo (`03:166` × `:167-168`).
  - `grep -c "alterado em 2026-09-29" 04-casos-de-teste.md` dá 80, não 46.
  - `## Testes` lista a Tenancy como "CT-12, CT-14, CT-80, CT-89, CT-98" sem os CT-112..CT-114, e `## Tickets` diz "105 CT".
- **Ação exigida**: trocar cada número pela saída do comando e fechar ou retirar o A-04 de `## Blockers` e da `## Verificação Final`. `grep -rn "411\|35/35" {wiki}/` acha `03:19, 62, 78, 83, 118, 136, 137, 155`.

### QA-03 — 14 testes da revisão do diff sem cenário no 04, e RD2-08 sem P-nn · Major · destino 1 → 3
- **Dimensão**: L1/A · **Relacionado a**: DV-10; RD2-08 (`SubstituicaoEmArquivo`, infraestrutura compartilhada com 6 chamadores)
- **Esperado**: todo teste nasce de um cenário do `04`, e comportamento que nenhuma `RQ` escreve vira `P-nn`.
- **Observado**: `grep -nE "^(it|test)\('\[RD" tests/Kit/*.php tests/Tenancy/*.php tests/BrowserTenancy/*.php | wc -l` dá 14 testes sem `[CT-nn]`. O `it('captura os quadros do instalador')` também não tem ID. O próprio `03:187` admite que o RD2-08 pedia `P-nn`, e ela não foi escrita.
- **Ação exigida**: registrar a `P-nn` do RD2-08 em `## Premissas`; depois a `feature-test-design` deriva os cenários e os mutantes dos 14 testes e renomeia os `it()`.

### QA-04 — Recurso opcional desenhado como sempre ligado no DG-02, no DG-07 e no DG-08 · Major · destino 3 → 2
- **Dimensão**: A · **Relacionado a**: RQ-10, P-16, P-19, P-32 ("aceitar convite recebido … com KIT_TENANCY"), P-43; R4; CT-08, CT-57
- **Esperado**: todo elemento que só existe com uma chave desligada por padrão leva a condição, como o DG-09 já faz ("recusar exige KIT_TENANCY").
- **Observado**:
  - DG-02: `cu_aceitar_convite["Aceitar/recusar convite recebido"]` aparece sem condição (`docs/pt/referencia/arquitetura-em-diagramas.md:78`, `:105`). A recusa só existe na caixa de convites recebidos, que responde `false` sem tenancy (`app/Filament/App/Pages/ConvitesRecebidos.php:regraLocalDeAcesso:75-77`; único chamador de `Convite::recusar`, em `:144`).
  - DG-07: a `accDescr` diz "sempre ligando a organização do convite", e os dois ramos afirmam o vínculo à organização (`docs/pt/autenticacao/convites.md:74, 89, 92`). O código só vincula com `tenant_id` (`app/Models/Convite.php:tenant_id:636`, `:tenant_id:708`), e o ramo `else recusa` (`:93-94`) também não traz condição.
  - DG-08: o estado `Pendente` aparece sem condição (`docs/pt/autenticacao/estados-de-usuario.md:24-33`). A única escrita de `aprovacao_pendente` vem de `RegistroAberto` com `KIT_REGISTRO_APROVACAO_MANUAL`, que vem desligada por padrão (`app/Support/RegistroAberto.php:aprovacao_manual:81`, `:aprovacao_pendente:182`).
  - A guarda não enxerga esses casos por dois motivos. Ela confere por bloco: basta a chave aparecer em qualquer ponto do bloco. E aceita a chave por substring: "provedor social" é aceito por causa de `KIT_SOCIALITE_VINCULO_CONFIRMAR`, e `KIT_REGISTRO` por causa de `KIT_REGISTRO_APROVACAO_MANUAL` (`tests/Kit/DiagramasDaArquiteturaTest.php:mapaOptInDaGuarda:1162`).
- **Ação exigida**: primeiro o CT que falha, por elemento e com a chave exata, pela `feature-test-design`; depois a correção dos três blocos, em pt e en.

### QA-05 — A paridade pt × en não vê mensagem de sequência nem aresta tracejada · Major · destino 3
- **Dimensão**: K/A · **Relacionado a**: RQ-26, RQ-34 ("-.->, ==> … em pt e en"), R2/CT-03, R42/CT-85, R53/CT-116
- **Observado**:
  - O CT-03 usa um extrator local que só reconhece `-->` (`tests/Kit/DiagramasDaArquiteturaTest.php`, `estruturaNormalizada`, linha 232 no ciclo 1), e não o normalizado de `tests/Pest.php`.
  - O CT-85 compara só blocos sintéticos e nunca lê o publicado (`:4631`). O Gherkin pede "o bloco real" e a linha do DG-11, que falta no dataset. O extrator dele (`mensagensDaSequencia:4601`) também perde as setas `-->>`: acha 8 das 11 mensagens do DG-04.
  - Para 17 DGs, o "fato declarado" do CT-116 é só a presença de um literal fixo (`tests/Kit/GuardasDosDiagramasTest.php:paresDeAdulteracaoPorDg:830`), sem nenhum valor lido do código.
- **Repro**: as funções do teste, copiadas sem alteração para o scratchpad, rodaram sobre os blocos publicados com o bloco en alterado em memória: o CT-03 dá "iguais" para as 6 alterações. As alterações foram: sem `painel_infra -.-> packagist` e com OAuth só no `/admin` (DG-01); sem o `finally` (DG-16); sem `resposta_login-->>visitante` e com `Authenticate`/`AuthenticateSession` invertidos (DG-04); sem `BudgetExceededException` (DG-11). Nenhuma outra guarda lê a mensagem en: `grep -n "mensagensDeSequencia(" tests/` só acha o DG-20.
- **Ação exigida**: pela `feature-test-design`, reescrever o CT-03 e o CT-85 sobre os blocos publicados, com o extrator de `tests/Pest.php`; os mutantes são as 6 alterações acima.

### QA-06 — No site, os diagramas saem com texto de 2 a 7 px · Major · destino 3 → 2
- **Dimensão**: H (e G, texto que o usuário não lê) · **Relacionado a**: RQ-06, RQ-12, RQ-17; CT-B01
- **Observado**: medido com o Playwright do `site/` a 1280×900, com a coluna de 600 px. A fonte efetiva (fonte × escala do `viewBox`) é:
  - DG-11: 2,2 px (escala 0,14)
  - DG-12: 3,0 px
  - DG-04: 4,2 px
  - DG-13: 5,2 px
  - DG-18: 5,3 px
  - DG-07 e DG-19: 5,6 px
  - DG-01: 6,2 px
  - DG-05: 6,7 px
  - DG-15: 6,9 px

  No total, 13 dos 20 ficam abaixo de 10 px. O `astro-mermaid` 2.1.0 não tem zoom. O CT-B01 conta SVGs e o axe não mede o tamanho do texto em SVG, então os dois passam.
- **Evidência**: `qagate-escala.mjs` e `qagate-dg11-pagina.png` (scratchpad), com o DG-11 ilegível na página.
- **Ação exigida**: um CT-B de legibilidade com piso de fonte efetiva, e só então o layout (orientação, divisão do bloco ou rolagem horizontal).

### QA-07 — Helper clonado com outro nome, contra a `.ai/rules/testes.md` · Major · destino 2
- **Dimensão**: L4
- **Observado**:
  - `transicoesDoEstado()` (`tests/Tenancy/DiagramasDaArquiteturaTenancyTest.php`, linha 396 no ciclo 1) é a mesma regex de `tests/Pest.php:transicoesDeEstado:1583`.
  - `mensagensDaSequencia()` (`tests/Kit/DiagramasDaArquiteturaTest.php`, linha 4601 no ciclo 1) é um clone mais estreito de `tests/Pest.php:mensagensDeSequencia:1475`, e é a causa de parte do QA-05.
  - `ordemDoDG20EstaCorreta()` (Tenancy, `:759`) repete `ordemDg20EhCorreta()` (`tests/Kit/GuardasDosDiagramasTest.php`, linha 859 no ciclo 1; hoje `tests/Pest.php:ordemDg20EhCorreta:1516`).
- **Ação exigida**: uma função só em `tests/Pest.php` (a regra diz "Nunca crie um clone com outro nome"). O `03` marca a `testes.md` como "aplicada".

### QA-08 — Frases erradas nas docs novas · Minor · destino 3 → 2
- **Dimensão**: L5
- **Observado**:
  - O índice chama o DG-10 de "Assistente de IA na sessão" e "AI assistant in the session", mas ele é a sessão autenticada (`docs/{pt,en}/referencia/arquitetura-em-diagramas.md:205`).
  - O DG-16 vira "kit:update — relatório", e o fluxo também aplica (`:211`).
  - O texto cita "`restaurar()` devolve o estado…", mas `restaurar` não existe em `app/` (`docs/pt/autenticacao/estados-de-usuario.md:44`, `docs/en/…:49`).
  - "O health é o único agendado" (`docs/pt/recursos/trilhas-de-infraestrutura.md:24-25`, `docs/en/…:25`) é falso: `routes/console.php` agenda mais eventos, incluindo podas que apagam tabelas do mapa (`:32` `authentication-log:purge`, `:64` `model:prune` de exceções).
- **Ação exigida**: corrigir o texto nos dois idiomas; o índice ganha guarda de título × `accTitle`.

### QA-09 — Português visível nos blocos en · Minor · destino 3 → 2
- **Dimensão**: L5/RQ-26, P-23 · **Observado**: "(conta nova)" e "conta existente" aparecem no DG-07 en (`docs/en/autenticacao/convites.md:90, 93`), e "(escolha de painel)" no DG-05 en (`docs/en/autenticacao/login-unificado.md:49`). O oráculo do CT-89 procura o marcador em pt nos dois idiomas (`tests/Tenancy/…:732-733`), e o detector de tradução só usa acento como sinal.
- **Ação exigida**: marcador por idioma no teste e o rótulo traduzido.

### QA-10 — Mutante sobrevivente numa linha do diff · Minor · destino 3
- **Dimensão**: K2 · **Observado**: `app/Support/SubstituicaoEmArquivo.php:preg_replace_callback:54`, `IncrementInteger` (limite `1` → `2` no `preg_replace_callback` do RD2-08) sobrevive. Nenhum teste prova que só a primeira ocorrência é trocada. O `RemoveStringCast` da mesma linha é equivalente.
- **Evidência**: arnês próprio (QA-12), 54 mutantes, 13 sobreviventes, 208,1 s.
- **Ação exigida**: um cenário com a chave duas vezes no `.env`, pela `feature-test-design`.

### QA-11 — Termos decididos na feature, sem glossário · Minor · destino 1
- **Dimensão**: L7 · **Observado**: não existe `wikis/glossario.md`. Ficaram sem registro a P-34 (Accepted/Expired), a P-35 (`Excluida`) e a Q?2 ("no link"). O DG-20 en mistura "inactive tenant" e "organization" no mesmo bloco (`docs/en/recursos/multi-tenancy.md`).
- **Ação exigida**: registrar os termos e alinhar o en.

### QA-12 — `pest --mutate` pelo `pestw.cmd` dá score falso quando o mutante tem 2 ou mais testes cobridores · Minor · destino 4
- **Dimensão**: K2 (arnês)
- **Observado**: o plugin relança com `--filter="A|B"`, e o `%*` do `pestw.cmd` passa pelo `cmd`, que lê o `|` como pipe. O resultado é exit 255 em 0,22 s, e o plugin conta como mutante morto. Com um só teste cobridor, funciona (1,46 s).
- **Comparação** (pelo `pestw.cmd`, sem cache, × pelo arnês do scratchpad, que relança por `proc_open` com array):

  | Classe | `pestw.cmd` | Arnês do scratchpad |
  |---|---|---|
  | `CustomizadorDaInstalacao` | 98,58 % em 22 s | 57,09 % em 1.099,8 s |
  | `SubstituicaoEmArquivo` | 100 % em 51 s | 75,93 % em 208,1 s |

- **Efeito colateral**: com o arnês que funciona, um mutante de `CustomizadorDaInstalacao` fez os testes gravarem no `.env` e em `config/` reais. Detalhe em *Para o orquestrador*.
- **Ação exigida**: nota para a `feature-wiki` (`pestw.cmd`) e para a `.ai/rules/testes.md`; não reprova a feature.

### QA-13 — `install.gif` e acentos no DG-19 · Cosmético · destino 2
- **Observado**: o GIF novo mede 1000×1500 (o antigo, 960×590). Os quadros 1 a 3 ficam com cerca de 70 % vazios, as linhas saem espaçadas e o último quadro mostra o WARN do terminal do Windows. O `resize(1400, 2100)` está em `tests/BrowserTenancy/CapturaDeArteTest.php`. O DG-19 pt escreve "retencao" e "papeis".

### QA-14 — Falta a coluna `Custo` em `## Despachos` · Cosmético · destino 1
- **Dimensão**: L6 · **Observado**: as tabelas #1–#28 não têm a coluna, e a #45–#57 usa "Onde".

## Matriz de Rastreabilidade

| RQ/P | Cláusula ou premissa | Passo PRD | CT | CT-B | Código | Resultado | Veredito |
|---|---|---|---|---|---|---|---|
| RQ-10 (P-16/19/32/43) | opcional nunca aparece sem condição | 4, 8, 10 | CT-08, CT-57, CT-81 | — | DG-02, DG-07, DG-08 | três elementos sem a chave | ❌ QA-04 |
| RQ-26 / RQ-34 | pt e en com guarda, setas normalizadas nos dois idiomas | 14, 22, 24 | CT-03, CT-85, CT-116 | — | `estruturaNormalizada`, `mensagensDaSequencia` | en diverge em silêncio | ❌ QA-05 |
| RQ-06 / RQ-12 / RQ-17 | o diagrama mostra como o kit funciona, no site | 2, 4–18 | — | CT-B01, CT-B02 | 13 de 20 blocos | < 10 px | ❌ QA-06 |
| RQ-25 | extras justificados no `02` | 5, 17, 18 | CT-106..CT-116 | — | DG-20 (sequência) | a ADR justifica um flowchart | ⚠️ QA-01 |
| — (RD2-08) | barra invertida no `.env` | 22 | `[RD2-08]` | — | `SubstituicaoEmArquivo` | sem `RQ` nem `P-nn` | ❌ QA-03 |
| RQ-28 | nenhuma superfície afirma `password` | 1, 21, 22, 24 | CT-40, CT-41, CT-52 | CT-B03 | README, resumo, GIF (visto) | ✅ | ⏳ DV-01/DV-02 aceitas (RQ-44) |
| RQ-36 | o CI de PR confere o site | 23 | CT-105 | — | job `site` | nunca rodou num PR | ⚠️ L-07 |

## Dimensões

| # | Dimensão | Status | Observação |
|---|---|---|---|
| A | Cobertura do requisito | ❌ | QA-03, QA-04; `rastreabilidade.sh` exit 0. Ressalva: 13 `RQ` saem da cobrança pela coluna `Substitui` (A-07) e o `00` não tem `Estado` nem tabela de `P-nn`; conferi por leitura (43/43 `P-nn` citadas no `04`). Sem `07-tickets/` |
| B | Fronteiras e dados | ✅ | sonda no scratchpad: 14 valores (barra invertida, `$`, aspas, `#`, emoji, 500 caracteres, `${}`) fazem a ida e volta pelo `.env`; a quebra de linha vira espaço (intencional); `ehUtilizavel` ok; `.env` malformado devolve `null` |
| C | Matriz de permissão | ✅ | não há superfície nova (`git diff` de `app/Policies`, `routes/web.php`, `app/Http` e `config` vazio); a matriz do DG-02 é executada por CT-11, CT-73 e CT-83, verdes |
| D | Observabilidade | ✅ | nenhum `Log::` novo (grep = 0); só console; a senha aparece só no banner, como antes; fixture mascarada; os 4 quadros do GIF conferidos no olho |
| E | Performance | ✅ | nenhum request novo; maior chunk 662.109 B; o JS do mermaid só nas páginas com diagrama (`dist/pt/index.html` sem mermaid) |
| F | UX de erro | ✅ | mensagens em pt, dizem o que e como; a contradição com banco inacessível é a DV-01 (RQ-44) |
| G | Tema e cor | ✅ | `dark-mode.sh --mecanismo` exit 1 (Filament + JS); nível 1 exit 0; CT-B02 verde; nível 3 por screenshot próprio, claro e escuro ok |
| H | Acessibilidade | ❌ | axe em 96 páginas sem serious/critical; GIFs com `alt`; QA-06 |
| I | Segurança | ✅ | step 9 com 68 linhas (60 achados, 8 rejeitados); eixo 9 não refeito nas rodadas 1–4. O delta do step 10 (testes e 4 docs) não passou pelo step 9: eixo 9 aplicado, não se aplica. Além disso: nenhuma rota nova (a da captura só existe no teste); job `site` em `pull_request` com `contents: read`, só SHAs interpolados; lock com mermaid 11.17.2 |
| J | Regressão | ⚠️ | suíte Unit+Feature+Kit+Tenancy em série: 3.627 testes, 3.624 passaram, 3 pulados, 0 falhas, 1.664,5 s. Browser, só o que o Impacto nomeia: CapturaDeArte 22/22 (`KIT_ART=1`), HubDeCards 2/2, RoteiroDoKit 7/7, LoginUnificado 1/1. Site: links 2.584/0 quebrados, 56 redirects; Pint e PHPStan verdes. O resto da suíte Browser e o `--tia` ficaram fora |
| K | Adequação da suíte | ⚠️ | K1 `k1-oraculo-fraco.sh` exit 1, 5 candidatos rejeitados (4 já existiam na base; 1 `assertDatabaseMissing` por id é o oráculo certo da exclusão). K2 pelo arnês próprio, sem cache: `KitArte` 145 mutantes, 3 sobreviventes fora do diff, 102 timeouts, 1.409,7 s; `Customizador` 282, 121 sobreviventes, 1 no diff e equivalente (`:327`); `SenhaDoAdministrador` 16, 1 fora do diff, 90,5 s; `Substituicao` QA-10; `KitInstall` só contra o `CustomizadorDaInstalacaoTest`: 58 mutantes, 37 sobreviventes, os do diff nas mensagens (espaço da DV-03). QA-05. Revisão adversarial: ciclos 1 e 2; a R48–R53 não teve (o gatilho não disparou) |
| L | Consistência documental | ❌ | QA-01, 02, 03, 07, 08, 09, 11, 14; L1 `ids-ct.sh` exit 1 (só o CT-39, costura `diff`: `git diff --name-only … composer.json composer.lock package.json package-lock.json` vazio); L2 `citacoes.sh` exit 0; L4 `conformidade-rules.sh` exit 0, com a `testes.md` violada (QA-07); L6 `checkbox-sem-evidencia.sh` exit 0 |

## Débitos Aceitos

- DV-01 (RD4-02), DV-02 (RD4-10), DV-03 (RD4-05), DV-04 (RD4-07), DV-05 (RD4-08), DV-06 (RD4-09): aceitos pelo solicitante (Adendo 5, RQ-44). Já estão replicados no `03`.
- DV-07 (limite do arnês) e DV-08 (capturas órfãs, que já existiam): declarados pela sessão; este gate não os reavaliou.

## Suspeitas Não Confirmadas

- No DG-20 en, a mensagem "is the organization active and linked?" sugere vínculo obrigatório, enquanto o `alt` excetua o `master_global`. É semântica; não afirma nada falso.
- No DG-02, `panel_user → Trocar de painel`: um `panel_user` sozinho tem um painel só. Só vale com papéis acumulados.
- Fora do diff e já existente: `admin_email` em claro no contexto de `[CustomizadorDaInstalacao@aplicar]`.

## Não Verificado

- A renderização no GitHub (L-03) e o job `site` num PR de verdade (L-07). Motivo: ainda não existe PR.
- A suíte Browser além dos 4 arquivos do Impacto, e o `pest --parallel --tia`. Motivo: memória (cerca de 0,9 GB livres) e a restrição "nunca `--parallel`".
- O K2 do `KitInstall.php` com o `ResumoDoKitInstallTest`. Motivo: tempo — cada caso roda o `kit:install` real.
- Os scores do `pestw.cmd` (QA-12). Motivo: são inválidos; os que valem são os do arnês próprio.
- O nível 3 da G/H. Motivo: o Playwright MCP não está disponível; usei o Playwright do `site/` por script. `browser-logs` e `database-query`: o MCP do Boost falhou ao conectar.
- `--agent`. Motivo: o `pest-plugin-agent` não está instalado; usei sonda por script no scratchpad. `qa-skills`: não instalado, usei o fallback em linha.
- RQ-43 (cada correção provada vermelha antes). Motivo: é histórico de processo e não se reproduz sem alterar a árvore.
