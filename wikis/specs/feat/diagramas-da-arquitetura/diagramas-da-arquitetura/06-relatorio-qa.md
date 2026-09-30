# Relatório de QA — feat/diagramas-da-arquitetura: Diagramas da arquitetura do kit no README e no site

> Requisito: `00-requisito.md` · Plano: `01-plano-acao.md`
> Perfil de esforço: completo (o site renderiza os diagramas por JavaScript no cliente, e o `kit:install` mexe em credencial e no `.env`)
> Natureza da wiki: nova · Toca infra compartilhada: sim → README/`docs/`/`site/`, `kit:arte`, `kit:install` (`CustomizadorDaInstalacao`, `SenhaDoAdministrador`, `SubstituicaoEmArquivo` com 6 gravadores), `KitTenancy`, `AtivadorDeTenancy`, `tests/Pest.php`, `ci.yml` · Regressão: sim
> Independência: sub-agente fw-qa-gate/opus, sem acesso à conversa
> Cobertura: 9 de 12 dimensões verificadas ou provadas não aplicáveis (G, J e K rodaram só em parte) · teto: APROVADO COM DÉBITO

## Veredito — Ciclo 2

**REPROVADO → especificação**

- Blocker: 0 · Major: 3 · Minor: 6 · Cosmético: 0 novos (o QA-13 do ciclo 1 segue aberto como débito cosmético)
- Não verificadas: G (nível 3), J (parcial), K (parcial). As causas estão em *Não Verificado*
- `RQ` abertas: RQ-50 em parte (Q?9 → P-45), **implementada** (QA-15); RQ-28 em parte (Q?10 → P-46, Q?11 → P-47), sem implementação da direção, o que só limita o teto
- Ambiente: app em `http://127.0.0.1:8000` (`/login` → 302) · Pest 5.0.5 · pest-plugin-mutate · PCOV e Xdebug carregados · site em `astro preview :4399`, já encerrado · Playwright MCP indisponível · Boost MCP não usado
- Convergência: o ciclo trouxe 9 achados novos (nenhum repete um achado do ciclo 1 já fechado), então o loop continua. O ciclo 3 é o último pelo teto da skill

## Achados

### QA-15 — A RQ-50 está aberta (Q?9) e foi implementada com a interpretação da sessão · Major · destino 1
- **Dimensão**: A (auditoria do requisito) · **Relacionado a**: RQ-50, P-45, Q?9, passo 27, CT-139; `[CT-19]` do `HostLocalTest` (outra wiki)
- **Esperado**: uma `RQ` aberta não é implementada por passo sem `**Bloqueado por**` nem por código do diff. A RQ-50 diz: "troca toda linha ATIVA da chave e nenhuma linha comentada".
- **Observado**:
  - O `00` (P-45) diz "RQ-50 fica aberta em parte até a resposta".
  - O passo 27 do `01` "Atende: … P-45" e não tem bloqueio (`01:1754`, `:1761-1763`).
  - `app/Support/SubstituicaoEmArquivo.php:definirLinhaNoEnv` descomenta a primeira linha comentada quando não há linha ativa (`:$comentada:131-136`). O próprio docblock admite: "P-45 da mesma wiki, pergunta Q?9 em aberto".
  - Pela leitura literal da RQ-50, isso altera uma linha comentada. A leitura contrária quebra o `[CT-19]` do `HostLocalTest` (`tests/Kit/HostLocalTest.php:it:847`). O conflito é entre dois requisitos, e quem decide é o solicitante.
  - O `00` não tem a coluna `Estado`, então o `rastreabilidade.sh` (exit 0) não enxerga a `RQ` aberta.
  - O `03` diz "0 perguntas de requisito abertas" (`03:201`), com Q?9, Q?10 e Q?11 pendentes.
  - Na Cobertura do `01`, a linha da RQ-28 não marca "aberta em parte" (`01:79`), e a da RQ-50 marca.
- **Repro**:
  1. `grep -n "aberta em parte\|Q?9" {wiki}/00-requisito.md {wiki}/01-plano-acao.md`
  2. `grep -n "Q?9 em aberto" app/Support/SubstituicaoEmArquivo.php`
  3. Sonda no scratchpad com `.env` = `"# APP_NAME=a\n#APP_NAME=b\n"`: depois do `definirNoEnv`, `APP_NAME="Novo"\n#APP_NAME=b`
- **Destino**: 1 · **Ação exigida**: levar a Q?9 ao solicitante (raia requisito, já formulada). Pôr `Estado` na decomposição (RQ-50 e RQ-28 como `aberta — Q?n`), e marcar o passo 27 como `Bloqueado por` Q?9 na parte da linha sem ativa. Corrigir `03:201` e a linha da RQ-28 no `01`. Depois da resposta, confirmar o código ou mudar o código, com o CT primeiro.

### QA-16 — Afirmações da wiki que o código contradiz ou que nenhum comando reproduz · Major · destino 1
- **Dimensão**: L3/L6 · **Relacionado a**: QA-10 do ciclo 1, RQ-50, passo 26
- **Observado** (cada linha diz o que foi medido agora):
  - `01:1736-1738` (passo 26), sem marca: "o CT-118 prova que `definirNoEnv` troca só a primeira ocorrência da chave". O CT-118 afirma hoje "toda linha ativa" (`tests/Kit/CustomizadorDaInstalacaoTest.php:665`).
  - `03:158` `[x]` QA-10: "só a primeira ocorrência da chave é trocada — CT-118 (`…:it:423`) … 75/75". É falso pela RQ-50. A linha 423 é um `if`, e o arquivo tem hoje 115 testes.
  - `03:212` `[x]`: "o ciclo 2 não toca `app/`, `routes/` nem `database/` (`git diff --stat` vazio)". `git diff --stat -- app/` mostra 4 arquivos e 96 linhas.
  - `03:215`: `grep -c "alterado em 2026-09-29" 04-casos-de-teste.md` dá **181**, não 165.
  - `03:180`: "ChecklistDeRelease 26/26 + 3 pulados". O medido é 26 testes, 23 passaram e 3 foram pulados (`03:208` está certo).
  - `install.gif`: `ls -l` dá 116.347 bytes (1000×1484), mas `01:1540`, `03:111` e `03:123` dizem 115.928. O CHANGELOG e `01:1742` já dizem 116.347 (`grep -rn "115.928" {wiki}/` acha os três).
  - `03:154`: "[CT-B06] … 40 diagramas maiores que a coluna". O conferidor de agora dá **60**.
  - O cabeçalho do `04` (`04:3`) diz "P-01..P-44"; o `00` vai até a P-47.
- **Repro**: os comandos entre parênteses acima; `XDEBUG_MODE=off vendor/bin/pest tests/Kit/ChecklistDeReleaseTest.php --compact --no-tia`
- **Destino**: 1 · **Ação exigida**: trocar cada número pela saída do comando em `01:1540`, `03:111`, `03:123`, `03:154`, `03:180`, `03:212` e `03:215`. Reescrever `01:1736-1738` e `03:158` para a RQ-50, com a marca *(alterado em …)*. Atualizar `04:3`.

### QA-17 — Helper clonado com outro nome, de novo, contra a `.ai/rules/testes.md` · Major · destino 2
- **Dimensão**: L4 · **Relacionado a**: QA-07 do ciclo 1 (mesma classe, instância nova do ciclo 2)
- **Observado**: `textoSemAcentoESemCaixa()` (`tests/Kit/ResumoDoKitInstallTest.php:107`) e `normalizadoSemAcentoESemCaixa()` (`tests/Kit/CustomizadorDaInstalacaoTest.php:494`) têm o mesmo corpo, `mb_strtolower(Str::ascii($texto))`, e os dois nasceram neste delta. A rule diz "Nunca crie um clone com outro nome … Mova para `tests/Pest.php` e use uma só". O `03` marca a `testes.md` como "aplicada".
- **Repro**: `grep -rn "mb_strtolower(Str::ascii" tests/`
- **Destino**: 2 · **Ação exigida**: deixar uma função só em `tests/Pest.php` e trocar a linha da `testes.md` no `03`.

### QA-18 — A `accDescr` do DG-08 e do DG-09 afirma o opcional sem a chave · Minor · destino 3 → 2
- **Dimensão**: A (RQ-10, P-16, P-43) · **Relacionado a**: R58, CT-129
- **Observado**:
  - DG-08 pt: "Pendente aprova para Ativo…"; DG-08 en: "Pending approves…" (`docs/{pt,en}/autenticacao/estados-de-usuario.md:23`).
  - DG-09: "Do Pendente, o convite vai a Aceito, Recusado ou Expirado" (`docs/pt/autenticacao/convites.md:113`, en `:117`).
  - Nenhuma das três traz `KIT_REGISTRO`/`KIT_REGISTRO_APROVACAO_MANUAL` nem `KIT_TENANCY`. A `accDescr` do DG-07 ganhou a condição e o CT-129 a guarda (`tests/Kit/DiagramasDaArquiteturaTest.php:elementosOptInDeR58:1553`), mas a lista não inclui as do DG-08 e do DG-09. É o texto que o leitor de tela lê.
- **Ação exigida**: uma linha no R58/CT-129 pela `feature-test-design`, que nasce vermelha, e depois a correção pt/en.

### QA-19 — Valor multilinha entre aspas deixa o `.env` ilegível depois da gravação · Minor · destino 3
- **Dimensão**: B · **Relacionado a**: RQ-50 ("qualquer leitor fica com o valor gravado")
- **Observado**: o `.env` era `APP_NAME="linha1\nlinha2"\nB=2\n`, um valor que o Dotenv aceita. Depois do `definirNoEnv(…,'APP_NAME','Novo')` ficou `APP_NAME="Novo"\nlinha2"\nB=2`. O `Dotenv::parse()` lança `InvalidFileException … invalid name at [linha2"]`, e o app não sobe. O padrão `.*$` só troca a primeira linha física. O defeito já existia antes, mas o método reescrito promete "toda linha que o Dotenv lê como a chave". Entrada improvável (edição à mão).
- **Repro**: a sonda `sonda-env.php` do scratchpad, caso `multilinha`, sobre um arquivo temporário.
- **Ação exigida**: a `feature-test-design` decide se é linha do R64 ou premissa; depois, a correção.

### QA-20 — Mutantes sobreviventes em `definirLinhaNoEnv` · Minor · destino 3
- **Dimensão**: K2 · **Evidência**: arnês do scratchpad (a `MutationTest` do plugin relança com `PHP_BINARY` na frente, sem `cmd`). 80 mutantes, 69 mortos, 11 sobreviventes, **86,25 % em 230,47 s**: cerca de 2,9 s por mutante, contra 22 s da suíte, o que é plausível. A medição só usou `tests/Kit/CustomizadorDaInstalacaoTest.php`.
- **Sobreviventes do diff**:
  - `:133 IncrementInteger`: com duas comentadas e nenhuma ativa, as duas viram ativas. Nenhum cenário prova "a primeira" da P-45.
  - `:135 TrueToFalse` e `:140 TrueToFalse`: o retorno dos ramos "descomenta" e "anexa" não tem asserção.
  - `:138 ConcatRemoveRight` e `:138 ConcatSwitchSides`: a forma da linha anexada.
  - `:125 RemoveStringCast` e `:133 RemoveStringCast` são equivalentes.
- **Fora do alcance desta suíte**: o mutante do QA-10 (`:60 IncrementInteger`, `aplicar()`) voltou a viver aqui. Hoje o `aplicar()` só serve config PHP (`AtivadorDeTenancy`), e não rodei a suíte da tenancy contra ele.
- **Ação exigida**: a `feature-test-design` com esses mutantes como entrada (tabela de decisão ativa × comentada × ausente, com o retorno).

### QA-21 — A citação `arquivo:it:linha` não prova nada, e várias apontam o teste errado · Minor · destino 1 (+ nota para a skill)
- **Dimensão**: L2
- **Observado**: 38 das 95 citações distintas `*.php:it:N` de `01`–`05` caem numa linha sem `it(`. Exemplos: `})->with([`, docblock, `expect`. O `[CT-105]` é citado como `…DiagramasDaArquiteturaTest.php:it:5595` em `01:87`, `01:1662`, `03:196`, `04:32`, `04:479` e `04:4401`, mas a linha 5595 é o `it('[CT-85]…`, e o CT-105 está na 6221. O `citacoes.sh` sai com exit 0 porque aceita `it` como substring ("with", "str_starts_with"). Pelo `03:180`, a correção automática "pelo símbolo mais próximo" usou esse mesmo símbolo.
- **Repro**: o laço `sed -n "${n}p" | grep -E "^\s*(it|test)\("` sobre `grep -ohE "[^ ]+\.php:it:[0-9]+"`.
- **Ação exigida**: citar pelo ID (`'[CT-105]'`) e refazer as 38. Nota para a `feature-wiki`: o `citacoes.sh` precisa recusar símbolo que não seja identificador.

### QA-22 — As citações curtas da prosa do DG-16 ficaram uma linha atrás · Minor · destino 1
- **Dimensão**: L2/L5 · **Observado**: em `docs/{pt,en}/comecar/atualizando-o-projeto.md:69-75` estão `:vincularKit:523`, `:arquivosAlterados:629`, `:mostrarResumo:748`, `:isInteractive:427`, `:prepararBranch:768`, `:revisarEAplicar:817`, `:desvincularKit:532` e outras. O `ef68f6d` deslocou cada uma em +1 (`sed -n 524p app/Console/Commands/KitUpdate.php` → `vincularKit`). A linha 40 do mesmo arquivo já diz `:isInteractive:428`. O `[CT-26]` só confere a forma com o caminho completo.
- **Ação exigida**: corrigir nos dois idiomas; se o step 12 aceitar, estender o `[CT-26]` à forma curta.

### QA-23 — Termos decididos nesta feature, fora do glossário · Minor · destino 1
- **Dimensão**: L7 · **Observado**:
  - `wikis/glossario.md` não tem "linha ativa" nem "linha comentada" do `.env`, que a RQ-50 e a P-45 definiram (por `EntryParser`/`Lines`).
  - Também não tem "Pendente", que nomeia dois estados diferentes: conta à espera de aprovação (DG-08) e convite sem resposta (DG-09).
- **Ação exigida**: incluir os termos, com "Não confundir com".

## Matriz de Rastreabilidade

| RQ/P | Cláusula ou premissa | Passo PRD | CT | CT-B | Código | Resultado | Veredito |
|---|---|---|---|---|---|---|---|
| RQ-50 / P-45 | toda linha ativa, nenhuma comentada; sem ativa, descomenta (aberta, Q?9) | 27 (sem bloqueio) | CT-118, CT-137..CT-139, CT-150 | — | `definirLinhaNoEnv` | interpretação da sessão implementada | ❌ QA-15 |
| RQ-28 / P-46, P-47 | senha já definida ou gerada, sem admin (aberta, Q?10/Q?11) | 1, 21, 22 | CT-134, CT-136 (invariante) | — | texto de hoje mantido | sem implementação da direção | ⏳ teto |
| RQ-10 / P-16, P-43 | opcional nunca sem condição | 8, 10, 26 | CT-129, CT-144 | — | `accDescr` do DG-08 e do DG-09 | sem a chave | ⚠️ QA-18 |
| RQ-50 | qualquer leitor fica com o valor gravado | 27 | CT-117, CT-118 | — | valor multilinha | `.env` ilegível | ⚠️ QA-19 |

## Dimensões

| # | Dimensão | Status | Observação |
|---|---|---|---|
| A | Cobertura do requisito | ❌ | QA-15, QA-18; `rastreabilidade.sh` exit 0, mas cego para `RQ` aberta sem a coluna `Estado`; sem `07-tickets/`. Deduplicação: QA-04, QA-05 e QA-10 do ciclo 1 conferidos como fechados (CT-129, CT-03/CT-85, CT-118 verdes) |
| B | Fronteiras e dados | ⚠️ | 10 sondas de `definirNoEnv` em arquivo temporário (CRLF, `export` com tab, espaço no `=`, prefixo `APP_NAME_X`, sem quebra no fim, arquivo vazio, duas comentadas, aspas simples duplicadas, `$1`/`\1`/`${}`, multilinha): só a multilinha falha (QA-19). `valoresDoBanco()` passa o nome por `Str::slug`, então não há injeção |
| C | Matriz de permissão | ✅ | nenhuma superfície nova: o diff de `routes/`, `app/Http`, `app/Policies` e `config` é só o comentário de `routes/console.php` |
| D | Observabilidade | ✅ | nenhum `Log::` novo (`grep -c` = 0); só saída de console, e o `.env` e o `config/` com o mesmo md5 antes e depois de cada execução |
| E | Performance | ✅ | nenhum request novo; o custo é de build do site (69 páginas em 9,8 s) |
| F | UX de erro | ✅ | o delta não muda mensagem do `kit:install`; "Não consegui publicar…" do `kit:arte` (`app/Console/Commands/KitArte.php:394`) nomeia a etapa (RQ-52). O que o banner diz no caso P-46/P-47 depende da resposta (teto) |
| G | Tema e cor | ⏭️ parcial | `dark-mode.sh --mecanismo` exit 1 (Filament + JS); nível 1 exit 0; nível 2 pelo conferidor: CT-B02 e CT-B05/CT-B11 no escuro, 14,0 px; nível 3 sem MCP |
| H | Acessibilidade | ✅ | `node verifica-acessibilidade.mjs 4399` exit 0: 96 páginas, nenhuma violação serious/critical; CT-B01 20/20 por idioma e tema; menor fonte efetiva 14,0 px (DG-13) nas 7 combinações; CT-B09 com 0 violações. Achado de texto alternativo: QA-18 |
| I | Segurança | ✅ | `## Revisão do Diff (step 9)` com 68 linhas (60 achados, 8 rejeitados); eixo 9 não refeito sobre o que ele cobriu. O `app/` do Adendo 7 (`definirLinhaNoEnv` e 3 chamadores) não passou pelo step 9, então rodei o eixo 9 sobre ele: não se aplica (sem Livewire, rota, escopo nem 403). Além disso: sem mass assignment, upload nem `DB::raw` |
| J | Regressão | ⏭️ parcial | 12 arquivos, um por vez, todos verdes: Diagramas 525/525 (3.831 asserções, 132 s), Tenancy 50/50, Guardas 47/47, KitArte 29/29, Customizador 115/115, Resumo 10/10, Checklist 23 + 3 pulados, SiteDeDocumentacao 68/68, Citacoes 3/3, HostLocal 77/77, KitUpdate 54/54, MysqlNoDocker 28/28. Suíte completa e `--tia` não rodados |
| K | Adequação da suíte | ⏭️ parcial | K1 `k1-oraculo-fraco.sh` exit 1, 5 candidatos rejeitados (4 já estão na `origin/main`; o `assertDatabaseMissing` por id do CT-80 é o oráculo certo). K2: QA-20. Revisão adversarial do `04`: feita, duas rodadas da adição. Os outros três arquivos de `app/` do delta não foram mutados |
| L | Consistência documental | ❌ | QA-16, 17, 21, 22, 23. L1 `ids-ct.sh` (8 arquivos) exit 1, só o CT-39, cuja costura `diff` vem vazia; contagem do `04` = 150 · 65 · 427 · 5 e do `05` = 11 · 36, que batem. L2 `citacoes.sh` exit 0, vacuidade em QA-21. L4 `conformidade-rules.sh` exit 0, `testes.md` violada (QA-17). L6 `checkbox-sem-evidencia.sh` exit 0; os números em QA-16. L5: pt × en do delta paralelos |

## Débitos Aceitos

- QA-13 (Cosmético, do ciclo 1, parcial): os quadros 1 a 3 do `install.gif` têm área vazia e o `WARN` aparece no último. É decisão do mantenedor e já está no `03`.
- DV-01..DV-08 e DV-11, como no ciclo 1.

## Suspeitas Não Confirmadas

- CRLF: a linha regravada perde o `\r`, e a linha anexada usa `PHP_EOL`, o que deixa fim de linha misto. O Dotenv lê igual, e o comportamento já existia.

## Não Verificado

- G nível 3 (olho nos dois temas). Motivo: Playwright MCP indisponível; só as medidas do conferidor.
- J: a suíte completa e `pest --parallel --tia`. Motivo: a instrução é não rodar a suíte completa (memória) e nunca usar `--parallel`. O `03` alega 3.796 testes com 0 falhas, e não reproduzi.
- K2 de `CustomizadorDaInstalacao::aplicarBanco`, `AtivadorDeTenancy` e `KitTenancy::semearDemo`, e o de `SubstituicaoEmArquivo` contra `HostLocalTest`, `TenancyNaInstalacaoTest` e `SenhaDoAdministradorTest`. Motivo: tempo e memória. Os scores do `pestw.cmd` continuam inválidos (DV-11).
- L-03 (render no GitHub) e o job `site` num PR. Motivo: não existe PR.
- `--agent`: `pest-plugin-agent` não instalado, usei sonda por script. `qa-skills`: fallback em linha.
