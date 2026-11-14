# Relatório de QA — feat/aparencia-das-telas-de-acesso: Tela de login personalizável

> Requisito: `00-requisito.md` · Plano: `01-plano-acao.md`
> Perfil de esforço: completo (UI com JS: iframe de preview e campos `live`; domínio sensível: autorização do preview e escopo por organização)
> Natureza da wiki: evolução · Toca infra compartilhada: sim → todas as telas de autenticação dos três painéis · Regressão: sim
> Independência: sub-agente fw-qa-gate/claude-opus-5-5, contexto novo sem a conversa
> Cobertura: 9 de 12 dimensões verificadas ou provadas não aplicáveis · teto: APROVADO COM DÉBITO

## Veredito — Ciclo 2

**REPROVADO → especificação**

- Blocker: 1 (QA-02, aguardando evidência) · Major: 2 · Minor: 2 · Cosmético: 1
- Não verificadas: G, H, K. As causas estão em *Não Verificado*
- `RQ` abertas: nenhuma. Q1 segue `aberta`, mas só a premissa estreita P-01 foi implementada (nada muda sem decisão do admin). Isso não gera achado e entra no débito
- Ambiente: app em `http://127.0.0.1:8123` (HEAD `1b6d3a09`, aparência global `painel`) · Pest 5.0.5 · Playwright MCP: indisponível · Boost MCP: indisponível (`laravel-boost` CONNECT_TIMEOUT)
- Ciclo com achados novos (QA-10..QA-14). O loop pode seguir para o ciclo 3, o último

## Situação dos achados do ciclo 1

| QA | Situação no ciclo 2 | Evidência |
|---|---|---|
| QA-01 | **fechado no código e no teste**. Ficou resíduo documental, que virou QA-12 | `podeVer()` exige `canAccessPanel(Filament::getPanel('admin'))` antes do escopo. A rota passou para `auth`. Visitante: `curl …/admin/preview-das-telas-de-acesso` → `302 /login`. O CT-38 agora limpa o painel corrente e dá `View:ConfiguracoesDoKit` à conta só do `/app`, de modo que o acesso ao admin é a única variável. Com o código antigo, o `Authenticate` passaria pelo painel `app` e o `canAccess()` daria 200: o caso discrimina |
| QA-02 | **aberto**: os cenários sobre o commit final não estão registrados | `03` › Verificação Final: cenários 1–4 sobre `b28debff` registrados. A linha sobre `1b6d3a09` ainda está `[ ]`. Segundo o orquestrador, outro processo está rodando agora |
| QA-03 | **fechado, com resíduo** (QA-13) | Q2 marcada respondida pela leitura do Texto Original ("as telas de auth", no plural), com a confirmação pedida ao solicitante. As Q3..Q11 seguem a mesma convenção da wiki |
| QA-04 | **fechado para `#RRGGBB` de 6 dígitos e para a primária default**. Ficaram resíduos (QA-10, QA-11) | Ao vivo: `/admin/login` sai com `--kit-painel-fundo: oklch(0.666 0.179 58.318)` e `kit-painel-de-marca--claro`. CT-40 tem 8 linhas, inclusive as de fronteira |
| QA-05 | **fechado** | `grep -rn "mídia da base\|da base, ou a arte\|uuid"` na wiki (sem o 06) não retorna nada. A docs pt/en fala em "arte da aba Identidade" |
| QA-06 | **fechado** | `{ git diff main --name-only; git ls-files --others --exclude-standard; } \| sort -u \| wc -l` = 79, o mesmo valor do `03` |
| QA-07 | **fechado** | doc, CHANGELOG e helperText com o recorte. Glossário com "Telas de acesso", "Painel de marca" e "Aparência padrão" |
| QA-08 | **fechado** | CT-42, com controle positivo (sem organização, aparece `data-kit-aparencia="painel"`). Tenancy verde |
| QA-09 | **fechado em parte**. O resto virou QA-14 | Índice e "Acréscimos" atualizados. A contagem do cabeçalho não segue o template |

## Achados

### QA-02 — RQ-08: cenários 1 a 4 sem evidência sobre o commit final · Blocker · destino 2

- **Dimensão**: A
- **Relacionado a**: RQ-08, RQ-07, Q10, CT-21, CT-27
- **Esperado**: "rode os cenarios ao finalizar". O commit `1b6d3a09` altera código de aplicação (preview, `AparenciaDasTelasDeAcesso`, providers, CSS).
- **Observado**: `03` › Verificação Final, `- [ ] Cenários 1 a 4 sobre o commit pós-gate (1b6d3a09 …)`.
- **Repro**: `grep -n "Cenários 1 a 4" wikis/specs/feat/aparencia-das-telas-de-acesso/tela-de-login-personalizavel/03-progresso.md`
- **Destino**: 2
- **Ação exigida**: registrar no `03` o resultado dos 4 cenários sobre o commit que vai para o PR. Se houver correção depois dele (este ciclo pede correções), rodar de novo sobre o commit seguinte.

### QA-10 — Primária da instalação em `#rgb` deixa texto branco sobre painel claro · Major · destino 3 → 2

- **Dimensão**: B (H correlata)
- **Relacionado a**: P-18, CT-40, passo 3 do PRD
- **Esperado**: P-18, "com uma cor de painel clara, o texto do painel passa a escuro". O docblock de `corDoPainel()` diz "sempre uma cor concreta, para o contraste do texto ser decidido".
- **Observado**: a `cor_primaria_hex` da instalação aceita `#rgb` (regex do `ColorPicker` em `ConfiguracoesDoKit` e `config/kit.php`: "Formato `#rgb` ou `#rrggbb`"). `corDoPainel()` devolve esse valor como está, mas `luminancia()` só entende `#RRGGBB` (`FORMATO_DA_COR`) e `oklch(`. Com isso, `fundoClaro('#ff0')` dá `false`: texto branco sobre amarelo, contraste de cerca de 1,07:1. Com `#ffff00` o resultado é correto (`true`). Também falha para `rgb(...)`. Já existia no ciclo 1 e não foi apontado ali.
- **Repro**: `php artisan tinker --execute 'config(["kit.cor_primaria_hex" => "#ff0"]); $c = App\Support\AparenciaDasTelasDeAcesso::corDoPainel(["layout" => "painel"], null); var_dump($c, App\Support\AparenciaDasTelasDeAcesso::fundoClaro($c));'` → `string(4) "#ff0"`, `bool(false)`
- **Destino**: 3 (o CT-40 não tem a classe "hex curto vindo da primária"), depois 2
- **Ação exigida**: pela `feature-test-design`, uma linha do CT-40 com a primária da instalação em `#rgb` claro, que falha hoje. Depois, expandir `#rgb` ou normalizá-lo em `corDoPainel()`.

### QA-11 — Aproximação `L³` do `oklch` escolhe o texto de menor contraste em Fuchsia e Pink 600 · Minor · destino 3 → 2

- **Dimensão**: B / H
- **Relacionado a**: P-18, CT-40
- **Observado**: comparei o `fundoClaro()` com a luminância real (OKLCH → sRGB linear) em todas as constantes de `Filament\Support\Colors\Color`. Divergem: Fuchsia[600] (escolhe escuro 3,65:1, o branco daria 4,66:1), Pink[600] (escuro 3,74:1, branco 4,54:1), Sky[600] (branco 4,02:1, escuro 4,23:1) e Violet[500], que não é usado. Nos dois primeiros, o texto do apoio cai abaixo de 4,5:1 quando o branco passaria.
- **Repro**: script `php -r` que percorre `ReflectionClass(Color::class)->getConstants()`, converte o `oklch` de cada tom 600 e compara `fundoClaro($c)` com a decisão pela luminância real. Saída: `DIVERGE Fuchsia[600] … escolha=escuro branco=4.66 escuro=3.65`, e o mesmo para Pink[600] e Sky[600].
- **Destino**: 3, depois 2
- **Ação exigida**: incluir Fuchsia/Pink 600 por nome no CT-40 e converter o `oklch` de verdade, ou usar a tabela hex do tom 600, em vez de `L³`.

### QA-12 — PRD e `03` ainda dizem que o preview é protegido pelo `Authenticate` do Filament · Major · destino 1

- **Dimensão**: L3 / L4
- **Relacionado a**: P-17, QA-01 (ação 3), passo 6, `.ai/rules/providers.md`
- **Esperado**: o texto bate com o código: rota sob `web` + `panel:admin` + `auth`, e `canAccessPanel(admin)` na página.
- **Observado**: `01-plano-acao.md` › Autorização: "Preview: rota do kit sob `panel:admin` + o `Authenticate` do Filament (só quem entra no `/admin`, P-17)". No `03` › Conformidade com Rules, a linha `providers.md` diz "`web` explícito + `panel:admin` + `Authenticate` do Filament". A linha SEC-02 da Revisão do Diff fala em "correção (middleware do Filament)", sem nota de que foi substituída. A linha `auth.md` do `03` não registra o achado do ciclo 1 (`getCurrentOrDefaultPanel()`). Fora do preview, o `01` (passo 1) descreve `corDoPainel` como "hex validado … senão a da instalação". Isso omite a constante `oklch` e o fallback âmbar 600.
- **Repro**: `grep -rn "Authenticate" wikis/specs/feat/aparencia-das-telas-de-acesso/tela-de-login-personalizavel/ | grep -v 06-relatorio` → `01` (Autorização) e `03` (linhas SEC-02 e `providers.md`)
- **Destino**: 1
- **Ação exigida**: corrigir e datar `*(alterado em …)*` a Autorização e o passo 1 do `01`, as linhas `providers.md` e `auth.md` da Conformidade e a linha SEC-02 do `03`.

### QA-13 — `00`: P-02 ainda condicionada a "Enquanto Q2 não é respondida" · Minor · destino 1

- **Dimensão**: A (auditoria do requisito) / L3
- **Observado**: Q2 aparece como respondida pela leitura do texto, com "confirmação explícita pedida ao solicitante no fechamento". P-02 continua começando por "Enquanto Q2 não é respondida…". A confirmação pendente não aparece como débito no `03`.
- **Repro**: `grep -n "^| Q2\|^| P-02" wikis/specs/feat/aparencia-das-telas-de-acesso/tela-de-login-personalizavel/00-requisito.md`
- **Destino**: 1
- **Ação exigida**: alinhar P-02 ao estado de Q2 e registrar no `03` a confirmação pendente como débito, até o solicitante responder. Se ele responder "só o login", vira Adendo.

### QA-14 — Contagem do cabeçalho do `04` fora do template · Cosmético · destino 1

- **Dimensão**: L1
- **Observado**: o cabeçalho diz "Cenários: 42 · Regras: 17 *(contado por `grep -oE "\[CT-[0-9]+\]" 04 | sort -u | wc -l`)*". Esse comando só conta CT. Pelo template, `grep -cE '^## Regra R[0-9]+'` dá **16**, e não há seção `## Regra R17`. Também faltam "Mutantes previstos" (`grep -cE '^\| M-?[0-9]+'` = 57) e "Sem matador".
- **Repro**: os quatro comandos de `feature-test-design/references/template-04.md` §*Contagem do cabeçalho*, rodados na pasta da feature. `grep -rn "Regras: 17" wikis/specs/feat/aparencia-das-telas-de-acesso/` → só o `04`.
- **Destino**: 1
- **Ação exigida**: recolar a linha do cabeçalho com a saída dos quatro comandos e criar a seção da R17, ou ajustar a contagem.

## Matriz de Rastreabilidade

`rastreabilidade.sh` exit 0. Só estas linhas têm lacuna:

| RQ/P | Cláusula ou premissa | Passo PRD | CT | CT-B | Código | Resultado | Veredito |
|------|----------------------|-----------|----|------|--------|-----------|----------|
| RQ-08 | cenários ao final | 9 | CT-21, CT-27 (sessão) | — | — | só sobre `b28debff` | ❌ QA-02 |
| P-18 | texto legível sobre cor clara | 3 | CT-40 (sem `#rgb`, sem paleta por nome) | — | `fundoClaro()` | `#ff0` branco 1,07:1 | ❌ QA-10, QA-11 |
| P-02 | recorte das telas (Q2) | 2 | CT-07, 25, 33 | B06 | 8 páginas vestidas | texto condicional desatualizado | ⚠️ QA-13 |

## Dimensões

| # | Dimensão | Status | Observação |
|---|----------|--------|------------|
| A | Cobertura do requisito | ❌ | QA-02, QA-13. `rastreabilidade.sh` exit 0. `indice.sh --check`: não aplicável, sem `07-tickets/`. Diff `b28debff..1b6d3a09` conferido contra as ações do ciclo 1 |
| B | Fronteiras e dados | ❌ | QA-10 e QA-11, por sonda no `tinker` e varredura da paleta. Cor da aparência ainda limitada a `#RRGGBB`, sem regressão |
| C | Matriz de permissão | ✅ | visitante → 302 (ao vivo). Conta só do `/app` com permissão do escopo → 403 (CT-38, agora discriminante). Papel admin global com `Update:Tenant` → CT-39. Admin/master × global/tenant/novo → CT-17/19 |
| D | Observabilidade | ✅ | o ciclo 2 não acrescenta ponto de log. A negativa nova passa pelo `[User@canAccessPanel]` existente (`user_id`, `painel`, `motivo`), sem PII. Log real lido no ciclo 1 |
| E | Performance | ✅ | `/admin/login` com a aparência `painel`: 3 queries, todas de `sessions`, medidas pelo kernel HTTP no `tinker`. Caminho da organização: uma query por slug, com memo, sem coleção |
| F | UX de erro | ✅ | sem mudança no ciclo 2. As evidências do ciclo 1 continuam valendo (mensagens pt no campo, nada salvo na recusa) |
| G | Tema e cor | ⏭️ não verificada | `dark-mode.sh --mecanismo` exit 1 (Filament, classe `dark`). Nível 1 `dark-mode.sh` sobre views/css/Filament do diff exit 0. Nível 2: CT-B03/B04 com `inDarkMode()`, `tests/Browser/AparenciaDasTelasDeAcessoTest.php` 40/40. Nível 3 sem MCP |
| H | Acessibilidade | ⏭️ não verificada (com achados) | QA-10, QA-11. `assertNoAccessibilityIssues()` ausente nos CT-B da feature |
| I | Segurança da superfície nova | ✅ | step 9 cobriu (4 passes, tabela de 19 linhas, BP-20 rejeitado). Além dele: ordem real do middleware resolvida na página, autorização refeita no update Livewire (`rascunhoAutorizado()` → `podeVer()`), visitante sem beco, `#[Locked]` mantido, escopo inexistente falha fechado |
| J | Regressão adjacente | ✅ | feature Kit + Entrega + Tenancy 207/207. Browser da feature 40/40. Kit adjacentes (Citações, Diagramas, Site, TelasDeAutenticação, Bloqueio, PageHeader, ConfiguraçõesTela, SeçõesColapsáveis, CabeçalhoDoPainel, KitInfo) 905/905. Suíte inteira e cenários não rodados por este gate (cenários: QA-02) |
| K | Adequação da suíte | ⏭️ não verificada | K1 `k1-oraculo-fraco.sh` exit 1: 4 candidatas. CT-31, CT-07 e CT-11 rejeitadas (asserção em helper, como no ciclo 1). CT-33 complementada pelo CT-42 (QA-08 fechado). K2 não rodado neste ciclo: `fundoClaro()`/`luminancia()` mudaram depois da medição do ciclo 1 (91,41 %), e o `04` ainda declara a re-revisão adversarial pendente |
| L | Consistência documental | ❌ | QA-12, 13, 14. L1 `ids-ct.sh` exit 0. L2 `citacoes.sh` exit 1: 9 linhas, todas no `06` do ciclo 1 (texto deste gate, que este relatório substitui), destino 5. L4 `conformidade-rules.sh` exit 0 (19 linhas), com `providers.md` desatualizada (QA-12). L6 `checkbox-sem-evidencia.sh` exit 0; reproduzidos: 79 arquivos e as contagens do `04` (42 cenários). L7: três termos no glossário. L8 `roteiro-manual.sh` exit 0; n/a, sem `08` e com decisão `não` |

## Débitos Aceitos

- Nenhum: há Blocker e Major abertos.

## Suspeitas Não Confirmadas

- A rota do preview usa `auth`, sem a pilha `authMiddleware` do painel (`MustTwoFactor`, `Locker`). Uma sessão bloqueada ainda abriria o próprio rascunho. O ciclo 1 (com o `Authenticate` do Filament) tinha a mesma lacuna. Não foi reproduzido: exige sessão autenticada e bloqueada no app servido, sem MCP.
- "Browser 171/171" (`03`, Verificação Final), herdado do ciclo 1: não reconferido.

## Não Verificado

- G nível 3 (olhar nos dois temas): Playwright MCP indisponível.
- H, axe (`assertNoAccessibilityIssues`): nenhum CT-B da feature o chama, e o MCP está indisponível.
- K: a re-revisão adversarial do `04` revisado (CT-29..42, B07/B08) está declarada pendente no próprio `04`. O K2 não foi refeito sobre o `fundoClaro()` novo, e a amostragem de mutante morto exige editar arquivo, o que é vedado ao gate.
- Sessão autenticada no app servido (editor, preview 200/403 ao vivo): sem MCP. Coberta por CT/CT-B.
- Cenários 1–4 sobre `1b6d3a09`: em execução por outro processo, fora do escopo deste gate (QA-02).
- Busca do RQ-12 pelos nomes do projeto de referência: os nomes estão mascarados no `00`. Só o oráculo genérico (CT-24) e a contagem de 79 arquivos foram reproduzidos.
