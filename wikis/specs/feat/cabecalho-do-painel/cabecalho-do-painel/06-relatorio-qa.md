# Relatório de QA — feat/cabecalho-do-painel: cabeçalho dos painéis personalizável pelas Configurações da aplicação

> Requisito: `00-requisito.md` · Plano: `01-plano-acao.md`
> Perfil de esforço: completo (UI com JS: o select aparece por `->live()`. A composição entra no layout de todo usuário e mostra dado de organização)
> Natureza da wiki: nova · Toca infra compartilhada: sim → `brandLogo`/`darkModeBrandLogo` dos três `PanelProvider`, Settings do kit, aba Identidade, `kit.css` · Regressão: sim
> Independência: sub-agente fw-qa-gate/opus, sem acesso à conversa
> Cobertura: 11 de 12 dimensões verificadas ou provadas não aplicáveis · teto: APROVADO COM DÉBITO

## Veredito — Ciclo 3

**REPROVADO → especificação** (ciclo 3 de 3: pela regra de convergência, o caso vai para o usuário)

- Blocker: 0 · Major: 1 · Minor: 2 · Cosmético: 2. Há ainda um débito aceito (QA-09).
- Não verificada: H (em parte). As causas estão em *Não Verificado — Ciclo 3*.
- `RQ` abertas: nenhuma. As 9 perguntas (Q1–Q6, Q14, Q16, Q17) seguem `aberta — aplicada pela recomendação` e esperam a confirmação do solicitante. Isso não é achado.
- Ambiente: o app em `http://127.0.0.1:8097` responde 200 em `/admin/login`, sem `kit-cabecalho`. HEAD `5b065e8`. Pest 4.x com `pest-plugin-browser` e `pest-plugin-mutate`. MCP: Boost disponível. Playwright MCP indisponível.
- Escopo do ciclo, pelas regras de convergência: re-verificar QA-06, QA-07, QA-08, QA-03′, QA-09 e QA-10; rodar a dimensão L inteira (todos os scripts) e a A sobre o `5b065e8`; o resto por amostragem. Todos os achados novos nasceram das correções do ciclo 2 ou da re-medição do K2, ou só ficam visíveis com o HEAD de agora.
- O único Major (QA-07′) se corrige trocando um número. Mesmo assim, é a segunda vez que a correção de um número da `## Verificação Final` deixa o número defasado. A tabela da L6 classifica isso como Major, e este gate não tem critério para rebaixar.

### Situação dos achados do ciclo 2

| ID | Situação | Prova |
|---|---|---|
| QA-06 | **fechado** | `01:288` traz a guarda em três sinais: sem autenticação, rota `.auth.` **ou** `SimplePage`. O motivo do QA-01 está escrito e marcado `*(alterado em …)*`. `01:338` diz `.kit-cabecalho__logo { height:2rem }`, como o código. O `height:100%` do `01:337` é o do contêiner `.kit-cabecalho`, que o `kit.css` também tem. `03:203` (Desvios) fala dos três sinais. |
| QA-07 | **reaberto em parte** | Conferem: `03:40` (16/16, 4/4), `03:61` (`4/4/30`, reproduzido), `03:72` (35 + 3, `ids-ct.sh` exit 0) e `03:64` (a aritmética fecha: 9 + 5 + 89 = 103, e 89/103 = 86,41 %). Diverge `03:60`, ver QA-07′. |
| QA-08 | **fechado** | O CT-35 afirma `regiaoDoHeader($html, 'fi-logo')` contendo "Projeto Ômega" nos dois renders. `regiaoDoHeader()` devolve só a região da tag (string vazia quando não a acha), então o M5 ("marca vazia") cai na asserção positiva. O `04` R22 traz o M5. `k1-oraculo-fraco.sh` sobre os testes do diff dá exit 0. O lote passa: 83/83. |
| QA-03′ | **fechado, com resíduo** | `04:19` diz 35 / 23 / 105 / 0, e `05:8` diz "três CT-B" com a justificativa da P-06. O resíduo vem do M5 do QA-08, que fez os mutantes passarem a 106. Ver QA-12. |
| QA-09 | **débito aceito** | Nota no `03:71`. O `citacoes.sh` dá exit 1 com 8 linhas, todas no `06`: as 4 do Ciclo 1 e as mesmas 4 transcritas no QA-09 do Ciclo 2. A transcrição foi erro deste gate no ciclo 2. O texto do Ciclo 3 não traz citação fora do formato. |
| QA-10 | **fechado, com resíduo** | A R26 está no Mapa de Regras (`04:62`, P-06, CT-B03). O CT-B03 aponta para a R26 no índice (`04:1040`) e no `05:129`, e a R20 cita só o CT-B01. O resíduo, a costura G4 sem a R26, está no QA-12. |

## Achados — Ciclo 3

### QA-07′ — `03:60` declara 528 asserções e o HEAD dá 530 · Major · destino 1

- **Dimensão**: L (L6)
- **Relacionado a**: QA-07, QA-08 (commit `5b065e8`)
- **Esperado**: cada `[x]` da `## Verificação Final` com número reproduzível no HEAD.
- **Observado**: `03:60` diz `{"result":"passed","tests":83,"passed":83,"assertions":528}`. Neste ciclo, o mesmo comando deu `{"tool":"pest","result":"passed","tests":83,"passed":83,"assertions":530,"duration_ms":77637}`. A diferença de 2 é o `5b065e8`, que acrescentou duas expectativas `->and(...)->toContain('Projeto Ômega')` ao CT-35 depois de a linha ser escrita: o `03` foi gravado às 17:42 e o commit é das 17:43. A correção do QA-07 repetiu o defeito do QA-07.
- **Repro**: `XDEBUG_MODE=off vendor/bin/pest tests/Kit/CabecalhoDoPainelTest.php tests/Kit/CabecalhoDoPainelTelaTest.php tests/Tenancy/CabecalhoDoPainelTenancyTest.php --compact --no-tia`
- **Evidência**: `grep -rn "528\|530" wikis/specs/feat/cabecalho-do-painel/cabecalho-do-painel/`. Na wiki, o número só aparece no `03:60`; fora dela, só no Ciclo 2 deste `06`, que é histórico.
- **Destino**: 1
- **Ação exigida**: trocar 528 por 530 no `03:60`. Daqui em diante, rodar o comando **depois** do último commit de teste e só então gravar a linha.

### QA-11 — Evidências do `03` que não se reproduzem no HEAD: hashes anteriores ao rebase e contagem do README · Minor · destino 1

- **Dimensão**: L (L6)
- **Observado**:
  - Os commits `7eef7f4`, `6be1303`, `5e3541c` e `cba0b15`, citados em `03:26`, `03:36`, `03:59`, `03:66` e `03:76`, não estão em nenhuma branch: `git branch --contains` não devolve nada e `git log --all` não os lista. O `git reflog` mostra o rebase sobre `origin/main`. Os commits equivalentes na branch são `bbea5a9`, `64748a8`, `371ac34` e `1972811`, respectivamente. Além disso, o `03:76` não lista `37f05d7`, `0314ed9`, `9960ea6` e `5b065e8`.
  - `03:37` alega `grep -c '\*\*74\*\*' README.md README.en.md` = "2 e 2", mas hoje dá 1 e 1. O 2 era o defeito do RD-01: o `sed` também tinha trocado o total de telas. A correção `1972811` levou a contagem a 1, que é o valor certo, e a evidência continuou dizendo 2. Conferido: `git show 64748a8:README.md | grep -c` dá 2.
- **Repro**: `for h in 7eef7f4 6be1303 5e3541c cba0b15; do git branch --contains $h; done` (nenhuma saída), `git reflog | grep rebase` e o `grep -c` acima.
- **Destino**: 1
- **Ação exigida**: no `03`, trocar os quatro hashes pelos da branch nas cinco linhas, completar o `03:76` com os commits que faltam e corrigir o `03:37` para "1 e 1 (depois do RD-01)".

### QA-12 — Resíduos de rastro das correções do QA-08 e do QA-10 · Cosmético · destino 1

- **Dimensão**: L (L1)
- **Observado**:
  - A contagem de mutantes pelo `grep -cE '^\| M-?[0-9]+' 04-casos-de-teste.md` dá **106** (o M5 da R22 entrou). O `04:18` e o comentário do `04:19` dizem 105. Cenários (35), Regras (23) e Sem matador (0) conferem.
  - A linha G4 de `## Costuras de Teste` (`04:76`) lista "R20, R21", sem a R26 de costura `browser`.
  - A linha do CT-35 no índice (`04:1039`) cita "R22 M4" e omite o M5. Ela também põe o CT-35 na costura G2, que não lista a R22.
- **Repro**: os `grep -cE` de `template-04.md` §*Contagem do cabeçalho* e `sed -n '76p;1039p' 04-casos-de-teste.md`.
- **Ação exigida**: recalcular o `04:18`/`04:19` pelos comandos, pôr a R26 na linha G4 e trocar para "R22 M4–M5" no índice. A costura do CT-35 fica a critério da `feature-test-design`.

### QA-13 — K2: sobreviventes e linhas não cobertas nomeados na re-medição · Minor · destino 3

- **Dimensão**: K (K2)
- **Relacionado a**: `01` §Channel de Log, R22, `03:64`
- **Observado**: a re-medição da sessão (`03:64`) é plausível. São 103 mutantes em 40,74 s, cerca de 0,4 s por mutante, numa suíte cobridora de cerca de 78 s que para no primeiro teste que falha. Os sobreviventes vêm nomeados. Os achados são os mutantes, não o score:
  - **9 sobreviventes** no `warning` da composição vazia (linhas 183–190): a chamada removida, a concatenação da mensagem e os campos do contexto. O `01` especifica esse log, e nenhum CT afirma o efeito colateral. A sessão justifica a ausência ("log não é cláusula do `00`"), mas a tabela da K manda isto para o destino 3.
  - **uncovered na linha 221** (o ramo da rota `.auth.` em `app/Support/CabecalhoDoPainel.php:emTelaDeAutenticacao():218`). Toda tela de autenticação do kit é `SimplePage`, então o segundo sinal cobre o primeiro, e o mutante que apaga o ramo da rota passaria mesmo se fosse coberto. O `03:64` diz que "só um GET autenticado de 2FA" alcança a linha. Um GET autenticado do aviso de verificação de e-mail também a alcança.
  - As linhas 96 (guarda de tipo) e 121 (sem `Request`, caminho de artisan) já estavam declaradas como uncovered.
- **Repro**: o comando do `03:64` (este gate não rodou `--mutate`, por instrução), com a leitura de `app/Support/CabecalhoDoPainel.php`, linhas 183–192 e 218–225.
- **Destino**: 3
- **Ação exigida**: pela `feature-test-design`, há duas saídas. A primeira: um CT de efeito colateral do `warning` (canal `configuracoes`, prefixo e contexto sem PII), mais um GET autenticado de `/app/email-verification/prompt` que cubra o ramo `.auth.`. A segunda: a sessão decide, no `01` passo 3, remover o ramo redundante e aceita os sobreviventes do log como débito declarado no `03`. Em qualquer caso, corrigir o "só 2FA" do `03:64`.

### QA-14 — O `## Quality Gate` do `03` cita um resultado que não está na wiki · Cosmético · destino 1

- **Dimensão**: L (L6)
- **Observado**:
  - `03:116` diz "suíte `Kit,Tenancy` completa re-rodada (resultado abaixo, na Verificação Final)". O `03:63` ainda traz a rodada anterior ao `9960ea6` ("baseline 3826 + 82 novos"). A sessão informou que a re-rodada está em curso.
  - `03:119` diz "Débito: (a consolidar no ciclo 2)".
  - `03:71` diz "4 citações", e o script devolve 8 linhas: as mesmas 4 citações, uma vez no Ciclo 1 e outra no Ciclo 2.
- **Repro**: `sed -n '63p;71p;116p;119p' 03-progresso.md`
- **Ação exigida**: colar no `03:63` o JSON da re-rodada, com a data e "medido pela sessão", e consolidar o débito.

## Dimensões — Ciclo 3

| # | Dimensão | Status | Observação |
|---|----------|--------|------------|
| A | Cobertura do requisito | ✅ | `rastreabilidade.sh` exit 0. Sem `07-tickets/`. O `git diff 9960ea6..5b065e8` só toca `tests/Kit/CabecalhoDoPainelTelaTest.php` (CT-35, QA-08), e o `git diff main...HEAD` continua com os passos 1–8. Nenhuma `RQ` aberta foi implementada pelo dev. |
| B | Fronteiras e dados | ✅ | Por amostragem. Não há código de app novo desde o ciclo 2. |
| C | Matriz de permissão | ✅ | Por amostragem. Nenhuma ação nem rota nova. |
| D | Observabilidade real | ✅ | O `warning` (linhas 183–190) não mudou. O contexto não tem PII. A falta de oráculo dele está no QA-13. |
| E | Performance | ✅ | Sem mudança de app. A guarda é `Livewire::current()`, sem query. |
| F | UX de erro | ✅ | Por amostragem. Sem mudança. |
| G | Tema e cor | ✅ | `dark-mode.sh --mecanismo` exit 1 (a G roda). O nível 1 sobre os arquivos AM de `main...HEAD` dá exit 0. O nível 2 (CT-B02) está verde no lote browser: 4/4/30. |
| H | Acessibilidade | ⏭️ parcial | Mesma causa dos ciclos 1 e 2. |
| I | Segurança da superfície nova | ✅ | Coberta pelo step 9 (15 achados, 5 rejeitados). Desde o ciclo 2 não há superfície nova. A guarda falha **fechada**, ou seja, mostra a marca de hoje. |
| J | Regressão adjacente | ✅ | O código de app é o mesmo do ciclo 2 (`git diff 9960ea6 HEAD -- app config resources database` vazio). A regressão vizinha (159 + 40) foi rodada pelo gate no ciclo 2. A suíte `Kit,Tenancy` completa está sendo re-medida pela sessão (QA-14). |
| K | Adequação da suíte | ⚠️ | K1: `k1-oraculo-fraco.sh` exit 0 sobre os 9 testes AM do diff. K2: medido pela sessão (`03:64`): 103 mutantes, 86,41 %, `Duration` 40,74 s, plausível, com os sobreviventes nomeados (QA-13). A medição é anterior ao `5b065e8`, que só acrescenta asserções, então o score medido é um piso. O gate não rodou `--mutate`, por instrução. Revisão adversarial do `04`: FEITA. |
| L | Consistência documental | ❌ | QA-07′, QA-11, QA-12, QA-14 (e QA-09 aceito). L1 `ids-ct.sh` exit 0, com a contagem do cabeçalho em QA-12. L2 `citacoes.sh` exit 1: as 8 linhas são só do `06` (QA-09) e os `00`–`05` estão limpos. L4 `conformidade-rules.sh main` exit 0. L6 `checkbox-sem-evidencia.sh` exit 0, mais a reprodução dos números (abaixo). L3: o QA-06 fechou e não sobrou contradição no passo 3 nem no 5. L5: nenhuma doc nova no `5b065e8`. L7: nenhum termo novo. |

**L6 — reprodução das alegações do `03` (ciclo 3)**. Conferem: `03:40`, `03:53`, `03:61` (`{"tests":4,"passed":4,"assertions":30}`), `03:72`, `03:73` (exit 0) e a aritmética do `03:64`. O `03:58` foi reproduzido pelo gate sobre o `5b065e8`: `phpstan analyse` deu `{"tool":"phpstan","result":"passed","errors":0}` e `filacheck`, rodado só para varrer e sem `--fix`, deu `All 17 rules passed!`. Divergem `03:60` (QA-07′), `03:37` e `03:76` (QA-11), e `03:63`/`03:116` (QA-14). Nenhuma degradação foi declarada: `pcov` e `xdebug` estão no `php -m`, e `pest-plugin-mutate` e `pest-plugin-browser` estão no vendor, como no ciclo 2.

## Débitos Aceitos — Ciclo 3

- QA-09 (Cosmético): as citações fora do formato estão no texto verbatim dos Ciclos 1 e 2 deste `06`. Nota no `03:71`, que o QA-14 pede para corrigir de "4" para "8 linhas".

## Suspeitas Não Confirmadas — Ciclo 3

- Nenhuma nova.

## Não Verificado — Ciclo 3

- H — acessibilidade. Motivo: o mesmo do ciclo 1. O axe já reprova a página por elementos do Filament anteriores à feature, e o Playwright MCP está indisponível.
- K2 reproduzido pelo próprio gate. Motivo: instrução da sessão para não rodar `--mutate`. A medição da sessão foi aceita pela plausibilidade, não reproduzida.
- J — suíte `Kit,Tenancy` completa sobre o `9960ea6`/`5b065e8`. Motivo: medida pela sessão, com o resultado ainda fora do `03` (QA-14). O gate não roda a suíte completa, por instrução.

---

## Veredito — Ciclo 2

**REPROVADO → especificação**

- Blocker: 0 · Major: 2 · Minor: 1 · Cosmético: 3
- Não verificadas: H (em parte) e K. Na K, o K2 foi medido pela sessão sobre o código anterior ao `9960ea6` e está sendo re-medido. As causas estão em *Não Verificado — Ciclo 2*.
- `RQ` abertas: nenhuma. As 9 perguntas (Q1–Q6, Q14, Q16, Q17) seguem `aberta — aplicada pela recomendação` e esperam a confirmação do solicitante. Isso não é achado.
- Ambiente: app em `http://127.0.0.1:8097`. A migration de settings agora está aplicada: `database-query` mostra as cinco `cabecalho_*` com `false`/`"perfil"` (o RQ-06 vale no banco servido), e `/admin/login` responde 200 sem `kit-cabecalho`. Pest 4.x com `pest-plugin-browser` e `pest-plugin-mutate`. MCP: Boost usado (`database-query`). Playwright MCP indisponível.
- Escopo do ciclo, pelas regras de convergência: re-verificar QA-01..QA-05; rodar a dimensão L inteira (L1–L7, com os scripts) e a A sobre as mudanças `9960ea6`/`37f05d7`/`0314ed9` e sobre a wiki reescrita; o resto por amostragem. Os achados novos abaixo são todos de ciclo 2, ou seja, nasceram das correções do ciclo 1 ou são resíduo delas.

### Situação dos achados do ciclo 1

| ID | Situação | Prova |
|---|---|---|
| QA-01 | **fechado** | `CabecalhoDoPainel::emTelaDeAutenticacao()` passou a olhar a rota `.auth.` **ou** `Livewire::current() instanceof SimplePage`. O CT-35 está verde no lote (83/83). A regressão das telas vizinhas passou inteira: `BloqueioDeSessao`, `CabecalhoDoMenuDoUsuario{,Tenancy}`, `IdentidadeDoKit`, `LogoDarkMode`, `VerificacaoDeEmail`, `TelasDeAutenticacao` e `IdentidadeVisual{,Tenancy}`, 159 + 40 testes. Toda tela de autenticação do kit é `SimplePage` (`TelaDoisFatores` estende `TwoFactorPage`, e o `LockerScreen` também é `SimplePage`). O kit não tem `RegisterTenant`, então a guarda nova não engole nenhuma tela interna. |
| QA-02 | **fechado, com resíduo** | Os trechos listados no ciclo 1 foram reescritos com `*(alterado em …)*`: ADR-01, `01:29`, `01:169`, `01:171`, `01:195`, `01:199`, `01:300`, `01:452`, `03:18` e `03:201`. O resíduo da mesma classe está no QA-06. |
| QA-03 | **reaberto em parte** | `04:18` dá 35 · 23 · 105 · 0 e confere com os `grep -c`. O comentário do `04:19` continua com "Cenários 33, Regras 22, Mutantes 99", e a ação do ciclo 1 o nomeava. Ver QA-03′. |
| QA-04 | **fechado** | CT-B03 verde: `tests/Browser/CabecalhoDoPainelTest.php`, `{"tests":4,"passed":4,"assertions":30}`, em série. Ressalva de rastro no QA-10. |
| QA-05 | **fechado** | `01:171` e `03:65` agora dizem "2 queries". |

## Achados — Ciclo 2

### QA-06 — O passo 3 e o passo 5 do PRD não acompanham o código final · Major · destino 1

- **Dimensão**: L (L3)
- **Relacionado a**: QA-01 (correção `9960ea6`), R22/CT-35, RD-05, RD-06, passos 3 e 5 do PRD
- **Esperado**: desvio propagado ao `01` com `*(alterado em …)*`. É a mesma exigência do QA-02.
- **Observado**:
  - `01:288` diz que a guarda é "Sem usuário autenticado, ou em rota `filament.{painel}.auth.*`, devolve `null`: **toda** tela de autenticação (… 2FA do Breezy, aviso de verificação de e-mail) fica com a marca de hoje". Essa é exatamente a afirmação que o QA-01 provou falsa. O código tem um terceiro sinal: `Livewire::current() instanceof SimplePage` em `app/Support/CabecalhoDoPainel.php:emTelaDeAutenticacao():218`. A linha não tem marca do QA-01. Quem implementar a partir do `01` reintroduz o defeito.
  - `03:202` (Desvios, "guarda de tela pública") descreve só a guarda por autenticação, sem a rota nem o `SimplePage`.
  - `01:338` diz `.kit-cabecalho__logo { height:100%; width:auto }`. O código tem `height: 2rem` (`resources/css/filament/kit.css`, bloco `.kit-cabecalho__logo`) e a blade repete `height:2rem` inline. O próprio `01:339` já diz "2rem absoluto (RD-06)", então o passo 5 se contradiz.
- **Repro**: `grep -n "auth\.\*\|SimplePage\|height:100%" wikis/specs/feat/cabecalho-do-painel/cabecalho-do-painel/0{1,3}-*.md`. O `01` não devolve nenhum `SimplePage`; o `03` cita `SimplePage` só no Quality Gate (115), nos Despachos (191) e na Verificação Final (74), nunca no passo 3 nem nos Desvios.
- **Evidência**: `app/Support/CabecalhoDoPainel.php:emTelaDeAutenticacao():218` contra `01:288`. O `04:846` (R22) já descreve a guarda certa.
- **Destino**: 1
- **Ação exigida**: reescrever `01:288` (guarda: rota `.auth.` **ou** componente `SimplePage`, com o motivo do QA-01), `01:338` (logo `2rem`) e `03:202`, todos com `*(alterado em …)*`.

### QA-07 — Números da `## Verificação Final` do `03` defasados pela rodada do ciclo 1 · Major · destino 1

- **Dimensão**: L (L6)
- **Esperado**: cada `[x]` com número reproduzível pelo comando citado.
- **Observado** (comandos rodados neste ciclo):

| Linha do `03` | Diz | Reproduzido agora |
|---|---|---|
| 60 | "50 + 15 + 17 = 82 verdes" | `pest tests/Kit/CabecalhoDoPainelTest.php tests/Kit/CabecalhoDoPainelTelaTest.php tests/Tenancy/CabecalhoDoPainelTenancyTest.php --compact --no-tia` dá `{"tests":83,"passed":83,"assertions":528}` (o lote Tela tem 16) |
| 61 | `{"tests":3,"passed":3,"assertions":20}` | `pest tests/Browser/CabecalhoDoPainelTest.php --compact --no-tia` dá `{"tests":4,"passed":4,"assertions":30}` |
| 71 | `citacoes.sh {wiki}` exit 0 | **exit 1**. As 4 linhas acusadas estão no próprio `06`, no Ciclo 1 (ver QA-09) |
| 72 | `ids-ct.sh` "34 CT + 2 CT-B" | o exit 0 confere; a contagem é 35 CT + 3 CT-B |
| 64 | K2 "uncovered linha 94 … linha 119" | medido antes do `9960ea6`, que acrescentou 2 `use`. As linhas hoje são 96 e 121, e o método novo (218–225) não foi mutado |

  O `03:40` (passo 9) também diz "Tela 15/15, Browser 3/3". O `03:53` já diz 35 + 3, então o `03` se contradiz.
- **Repro**: `grep -n "15/15\|3/3\|\b82\b\|34 CT\|\"assertions\":20" wikis/specs/feat/cabecalho-do-painel/cabecalho-do-painel/0[0-5]-*.md` aponta `03:40`, `03:60`, `03:61` e `03:72`. As linhas `03:186` e `03:193` são histórico de Despachos e ficam.
- **Destino**: 1
- **Ação exigida**: substituir os números de `03:40`, `03:60`, `03:61`, `03:71` e `03:72` pela saída real, e o `03:64` pela re-medição em curso (score, `Duration`, sobreviventes e uncovered com as linhas atuais).

### QA-08 — O CT-35 só tem expectativa negada; o "marca de hoje" do Gherkin não é afirmado · Minor · destino 3

- **Dimensão**: K (K1)
- **Relacionado a**: R22/CT-35, QA-01
- **Esperado**: o Gherkin do `04:843` diz "E a marca da tela é a de hoje nos dois".
- **Observado**: `tests/Kit/CabecalhoDoPainelTelaTest.php`, CT-35, só faz `not->toContain('kit-cabecalho')`, duas vezes. Um mutante que esvazie a marca na `SimplePage` autenticada (por exemplo, `marca()` devolvendo `HtmlString('')` quando a guarda dispara) passa. Há ainda um detalhe: com `Livewire::test()` o render inicial não tem rota, então o M4 ("só pelo nome da rota") já morre na primeira asserção. A metade do `$refresh` não discrimina nada sozinha.
- **Repro**: `bash .claude/skills/feature-quality-gate/scripts/k1-oraculo-fraco.sh tests/Kit/CabecalhoDoPainelTelaTest.php`
- **Evidência**: `tests/Kit/CabecalhoDoPainelTelaTest.php:223: … só expectativa negada (not->toContain(), not->toContain()) — nenhum valor esperado` (k1 exit 1).
- **Destino**: 3
- **Ação exigida**: pela `feature-test-design`, acrescentar ao CT-35 a asserção positiva da marca de hoje (o nome da aplicação ou a logo da instalação no HTML) nos dois renders, com o mutante "marca vazia na `SimplePage` autenticada".

### QA-03′ — Contagens remanescentes (reabertura parcial do QA-03) · Cosmético · destino 1

- **Dimensão**: L (L1)
- **Observado**:
  - o comentário do `04:19` ainda diz "Cenários 33, Regras 22, Mutantes 99, Sem matador 0";
  - o `05:8` diz "São **dois** CT-B", e hoje são três;
  - o `03:72` diz "34 CT + 2 CT-B", também listado no QA-07.
- **Repro**: os `grep -cE` de `template-04.md` §*Contagem do cabeçalho* dão 35 / 23 / 105 / 0. `grep -n "dois\*\* CT-B" 05-casos-de-teste-browser.md` mostra a linha 8.
- **Ação exigida**: alinhar `04:19` aos comandos, e `05:8` a três CT-B, com a justificativa do terceiro (P-06, que só o navegador calcula).

### QA-09 — O `citacoes.sh` reprova as citações do Ciclo 1 do próprio `06` · Cosmético · destino 1

- **Dimensão**: L (L2)
- **Observado**: `bash .claude/skills/feature-wiki/scripts/citacoes.sh {wiki}` dá exit 1 com quatro linhas, todas no texto do ciclo 1:
  - `06:25` — `app/Support/CabecalhoDoPainel.php:segmentos():114`; o símbolo hoje está na linha 116;
  - `06:32` — `CabecalhoDoPainel.php:114-131`, sem símbolo;
  - `06:40` — `app/Support/CabecalhoDoPainel.php:161`, sem símbolo;
  - `06:40` — `vendor/filament/filament/resources/views/components/page/simple.blade.php:13`, sem símbolo.
- **Ação exigida**: o Ciclo 1 é histórico gravado verbatim. Ou o débito é aceito, com a nota no `03:71` de que o exit 1 vem só do Ciclo 1 do `06`, ou a sessão pede ao gate do ciclo 3 que reemita o Ciclo 1 com as citações no formato do script. O texto do ciclo 2 não traz citação nova fora do formato.

### QA-10 — O CT-B03 está pendurado na R20, regra do bloco do usuário · Cosmético · destino 3

- **Dimensão**: L (L1) / A
- **Observado**: o `05:129` e o `04:1038` ligam o CT-B03 (P-06, composição na barra lateral) à "regra R20". A R20 é "o bloco do usuário some abaixo de 768 px" (`04:60`, P-04/P-09). A linha da R20 no Mapa de Regras não cita o CT-B03 nem a P-06, e a P-06 revisada fica sem regra própria no `04`. O `rastreabilidade.sh` não acusa porque o CT-B03 cita a P-06 na origem.
- **Ação exigida**: pela `feature-test-design`, dar ao CT-B03 a regra da P-06 (nova ou a regra da composição em duas regiões, a do CT-09) e atualizar o Mapa de Regras.

## Dimensões — Ciclo 2

| # | Dimensão | Status | Observação |
|---|----------|--------|------------|
| A | Cobertura do requisito | ✅ | QA-01 fechado; `rastreabilidade.sh` exit 0; sem `07-tickets/`; `git diff` do `9960ea6` trata do que o QA-01 pediu (guarda + CT-35 + CT-B03 + docs); QA-10 é só de rastro |
| B | Fronteiras e dados | ✅ | por amostragem; a superfície não mudou desde o ciclo 1 |
| C | Matriz de permissão | ✅ | por amostragem; nenhuma ação nem rota nova |
| D | Observabilidade real | ✅ | o `warning` não mudou (linhas 183–190); o prefixo `[CabecalhoDoPainel@resolverSegmentos]` confere com o `01:211`/`01:300` |
| E | Performance | ✅ | a guarda nova é `Livewire::current()`, sem query; o memo por `Request` não mudou |
| F | UX de erro | ✅ | por amostragem; sem mudança |
| G | Tema e cor | ✅ | `dark-mode.sh --mecanismo` exit 1 (Filament, classe `dark`); nível 1 sobre os arquivos AM do diff exit 0; nível 2 CT-B02 verde (4/4) |
| H | Acessibilidade | ⏭️ parcial | como no ciclo 1 |
| I | Segurança da superfície nova | ✅ | step 9 já coberto; a guarda nova falha **fechada** (`SimplePage` → marca de hoje), sem dado novo exposto; nenhuma `SimplePage` interna no kit (`grep "extends SimplePage" app/` vazio; sem `tenantRegistration()`) |
| J | Regressão adjacente | ✅ | depois do `9960ea6`: 159 + 40 verdes nas suítes vizinhas da guarda (lista no QA-01 da tabela acima); a suíte `Kit,Tenancy` inteira não foi re-rodada (ver *Não Verificado*) |
| K | Adequação da suíte | ⏭️ | K1 `k1-oraculo-fraco.sh` exit 1 (QA-08, confirmado); K2 medido pela sessão: `98 mutantes, 87.76 %, Duration 52.53s`, 9 sobreviventes nomeados (todos no `warning`), plausível com parada no primeiro teste que falha, mas é anterior ao `9960ea6` e não mutou `emTelaDeAutenticacao()`; re-medição em curso; revisão adversarial do `04`: FEITA |
| L | Consistência documental | ❌ | QA-06, QA-07, QA-03′, QA-09, QA-10. L1 `ids-ct.sh` exit 0 (contagens: QA-03′); L2 `citacoes.sh` exit 1 (QA-09); L4 `conformidade-rules.sh main` exit 0; L6 `checkbox-sem-evidencia.sh` exit 0 + números (QA-07); L5 docs pt × en do `9960ea6` equivalentes ("em tela estreita com a barra lateral aberta" = "on a narrow screen with the sidebar open"), coerentes com a P-06; L7 sem termo novo |

**L6 — reprodução das alegações do `03` (ciclo 2)**: conferem as degradações (nenhuma declarada; `php -m` tem `pcov` e `xdebug`; `ls vendor/pestphp/` tem `pest-plugin-mutate` e `pest-plugin-browser`), `rastreabilidade`, `checkbox`, `ids-ct` e `conformidade` com exit 0, `04:18` e `03:53`. Divergem as linhas listadas no QA-07.

## Débitos Aceitos — Ciclo 2

- (a definir pela sessão; candidatos: QA-09, e QA-03′/QA-10 se a sessão os fechar como texto)

## Suspeitas Não Confirmadas — Ciclo 2

- Nenhuma nova. A do ciclo 1 sobre o lote "841/840" segue não reproduzida (outro recorte).

## Não Verificado — Ciclo 2

- K2: re-medição do `--mutate` depois do `9960ea6`. Motivo: medido pela sessão em outro processo, por instrução. Falta colar score, `Duration`, sobreviventes e uncovered com as linhas atuais, e julgar a plausibilidade.
- H: acessibilidade. Mesma causa do ciclo 1.
- J: suíte `Kit,Tenancy` completa depois do `9960ea6`. Motivo: custo (cerca de 11 min em paralelo); o gate rodou a regressão vizinha da guarda.
- `phpstan`/`filacheck` sobre o `9960ea6`: não rodados (o `filacheck` corrige por padrão).

---

## Veredito — Ciclo 1

**REPROVADO → especificação**

- Blocker: 0 · Major: 1 · Minor: 2 · Cosmético: 2
- Não verificadas: H (em parte), K (K2 medido pela sessão). As causas estão em *Não Verificado*
- `RQ` abertas: nenhuma. As 9 perguntas ao solicitante (Q1–Q6, Q14, Q16, Q17) continuam `aberta — aplicada pela recomendação`, viraram P-01..P-06 e P-11..P-13, e as `RQ` estão `fechada`. O `03` registra "pendente de confirmação do solicitante". Não é achado: o texto foi implementado como diz. Mas a confirmação continua devida.
- Ambiente: app em `http://127.0.0.1:8097` (sem a migration de settings da feature, ver *Não Verificado*) · Pest 4.x com `pest-plugin-browser` e `pest-plugin-mutate` · MCP: Boost usado (`database-query`, `browser-logs`), Playwright MCP indisponível, `pest-plugin-agent` ausente (`ls vendor/pestphp/`). As sondagens dinâmicas foram feitas com arquivos Pest efêmeros fora do repositório.

## Achados

### QA-02 — O PRD e a ADR-01 ainda descrevem o desenho que o step 9 revogou · Major · destino 1

- **Dimensão**: L (L3)
- **Relacionado a**: P-06, D4, ADR-01, passos 3 e 5 do PRD, CR-04, RD-04
- **Esperado**: desvio propagado ao `01`/`02` com marca `*(alterado em …)*` (o `03` declara isso feito na Verificação Final, linha 68).
- **Observado**: o código faz a composição ficar sempre numa linha, com reticências (`resources/css/filament/kit.css`, bloco `.kit-cabecalho`, `white-space: nowrap`) e memoiza por `WeakMap` (`app/Support/CabecalhoDoPainel.php:segmentos():114`). O texto da wiki diz outra coisa, sem marca:
  - `02` ADR-01, *Decisão*: "Dentro da barra lateral o CSS do kit deixa a composição quebrar linha". *Consequências*: "o CSS do kit assume a responsabilidade por `flex-wrap`".
  - `01:29`, Cobertura P-06: "o CSS deixa quebrar linha dentro da barra". `01:195`, Riscos: "mitigado por `flex-wrap`".
  - `01:171`, Modelo de Execução: "uma vez por request pelo `once()`". `01:300`: "uma vez por request (`once()`)". O mesmo trecho também cita o prefixo `[CabecalhoDoPainel@segmentos]`, e o código loga `[CabecalhoDoPainel@resolverSegmentos]` (confirmado na sonda da D).
  - `03:18`: "(sem memo, guarda de tela pública)". `03:199`, Desvios: "sem `once()` (D4 revogada) … O custo sem memo é o de hoje". As duas linhas contradizem o `WeakMap` atual.
  - `01:452`: "`public/css/kit/kit.css` no commit". O asset publicado é `kit-correcoes.css` (`03:204`).
- **Repro**: `grep -n "quebrar linha\|flex-wrap\|once()\|@segmentos\|sem memo" wikis/specs/feat/cabecalho-do-painel/cabecalho-do-painel/0{1,2,3}-*.md`
- **Evidência**: as linhas acima, contra `CabecalhoDoPainel.php:114-131` e `kit.css`, que tem "A composição fica SEMPRE numa linha".
- **Ação exigida**: reescrever o ADR-01 (*Decisão* e *Consequências*), `01:29`, `01:171`, `01:195`, `01:211`, `01:300`, `01:452`, `03:18` e `03:199` para o desenho final, todos com `*(alterado em …)*`.

### QA-01 — Tela de autenticação ganha a composição depois de um round-trip Livewire · Minor · destino 3 → 2

- **Dimensão**: A (fronteira do `00` §Fora de Escopo; R22)
- **Relacionado a**: RD-05, R22/CT-28, docs pt/en "Cabeçalho dos painéis"
- **Esperado**: "As telas de autenticação (login, recuperação de senha, bloqueio, dois fatores, confirmação de e-mail) continuam com a marca de sempre" (`docs/pt/recursos/configuracoes-do-kit.md`, e o mesmo texto em en).
- **Observado**: a guarda de `resolverSegmentos()` (`app/Support/CabecalhoDoPainel.php:161`) exige `.auth.` no nome da rota corrente. Num update Livewire a rota é `default-livewire.update`, e a `SimplePage` redesenha a logo dentro do componente (`vendor/filament/filament/resources/views/components/page/simple.blade.php:13`). Resultado: no GET a marca é a de hoje, e depois de qualquer interação na tela (reenviar a verificação, código 2FA errado) a composição aparece.
- **Repro**:
  1. Ligar `cabecalho_nome_do_projeto`.
  2. Entrar com um usuário de e-mail não verificado e abrir `/app/email-verification/prompt`.
  3. Disparar qualquer ação Livewire da tela.
- **Evidência**: a sonda efêmera (`Livewire::actingAs($u)->test(EmailVerification::class)->call('$refresh')`) deu `GET tem kit-cabecalho => false` e `update tem kit-cabecalho => true`, com `rota do update => "default-livewire.update"`.
- **Destino**: 3, depois 2
- **Ação exigida**: incluir no `04` (R22) um CT de round-trip Livewire numa tela `.auth.` que falhe hoje, e só então corrigir a guarda (por exemplo, pela página Livewire ser `SimplePage`, ou pela rota original do componente).

### QA-04 — A P-06 revisada ("sempre numa linha, reticências") não tem cenário · Minor · destino 3

- **Dimensão**: A / K
- **Relacionado a**: P-06 (revisada pelo CR-04), R8/CT-09
- **Esperado**: premissa vigente com cenário que a prove. O risco do `01:195` diz "provado por CT-B".
- **Observado**: o CT-09 prova só a presença nas duas regiões. Nenhum CT/CT-B mede altura, quebra ou reticências. Na sonda de navegador (nome de 59 caracteres, 1280 px), o topbar truncou (`projScroll 529 > projClient 399`, `compBottom 51 ≤ hostBottom 64`). O cabeçalho da barra lateral ficou não visível na sonda e não foi medido.
- **Repro**: `grep -n "reticências\|ellipsis\|scrollWidth" wikis/specs/feat/cabecalho-do-painel/cabecalho-do-painel/0{4,5}-*.md tests/Browser/CabecalhoDoPainelTest.php` não devolve nada.
- **Ação exigida**: CT-B em `feature-test-design` com a barra lateral aberta: a composição não passa do `fi-sidebar-header` e o texto longo trunca.

### QA-03 — Contagens defasadas no `04` e no `03` · Cosmético · destino 1

- **Dimensão**: L (L1)
- **Observado**: o cabeçalho do `04:18` diz `Cenários: 33 · Regras: 22 · Mutantes previstos: 99`. O `grep -c` da template-04 dá **34 · 23 · 104** (o CT-34 e a R25 entraram depois). O `03:53` (Tickets) diz "26 CT + 2 CT-B".
- **Repro**: os três `grep -cE` de `template-04.md` §*Contagem do cabeçalho*, rodados na pasta da feature. `grep -rn "33 CT\|26 CT\|Cenários: 3" {wiki}` mostra `03:49`, `03:53`, `03:185` e `04:18`. As linhas 49 e 185 são histórico e ficam.
- **Ação exigida**: recalcular `04:18` (e o comentário `04:19`) pelos comandos, e corrigir `03:53`.

### QA-05 — Custo do bloco do usuário: o PRD diz 1 query, a medição dá 2 · Cosmético · destino 1

- **Dimensão**: E / L3
- **Observado**: com o bloco ligado, +2 queries constantes por página (`exists … name = master_global` e o `select roles … limit 1`). A composição dá +0. Não há N+1. O `01:171` e o `03:65` dizem "1 query de papéis".
- **Evidência**: sonda de contagem com linha de base re-medida (o arnês tem deriva de +12 também com tudo desligado). Os extras exclusivos do caso `usuario` foram 1× `exists(name)` e 1× `select roles … painel`.
- **Ação exigida**: trocar "1 query" por "2 queries (master_global + papel do painel)" no `01:171` e no `03:65`.

## Dimensões

| # | Dimensão | Status | Observação |
|---|----------|--------|------------|
| A | Cobertura do requisito | ⚠️ | QA-01, QA-04 — `rastreabilidade.sh` exit 0; sem `07-tickets/`; `git diff` por passo conferido (passos 1–8 presentes no diff) |
| B | Fronteiras e dados | ✅ | texto de 255 caracteres, emoji, marcação (CT-10/11); detalhe com tipo errado, lista, vazio (CT-18/22/23); `coagir()` sensível a maiúscula, coerente com "fora da lista vira perfil" |
| C | Matriz de permissão | ✅ | nenhuma ação nova; tela `ExigePermissaoDaTela` (CT-26 negativo); cabeçalho só de leitura para quem já está autenticado |
| D | Observabilidade real | ✅ | sonda: 1 `warning` por request, context `{painel, 3 bools, tenant_id}`, sem PII; prefixo diverge do `01` (QA-02) |
| E | Performance | ✅ | composição +0 queries, bloco +2 constantes, memo por request (QA-05 é só texto) |
| F | UX de erro | ✅ | CT-22/23: valor inválido recusado no campo, nada gravado; CT-25 normaliza sem travar a tela |
| G | Tema e cor | ✅ | `dark-mode.sh --mecanismo` exit 1 (Filament, classe `dark`); nível 1 exit 0; nível 2 CT-B02 + sonda: texto `#fff` no topbar escuro, `gray-950` no claro; nível 3 sem Playwright MCP |
| H | Acessibilidade | ⏭️ parcial | ver *Não Verificado* |
| I | Segurança da superfície nova | ✅ | coberto pelo step 9, `## Revisão do Diff (step 9)` do `03` (15 achados, 5 rejeitados); além: nenhuma rota nova, sem mass assignment, tenant vem do middleware do Filament, escape por `{{ }}` (CT-10/11), discriminante nulo fecha (`papelNoPainelCorrente()` com chave não numérica) |
| J | Regressão adjacente | ✅ | `Kit,Tenancy` `--parallel --processes=4`: 3911 testes, 3908 passaram, 3 pulados, 0 falhas; regressão nomeada no `01`: 465 + 578 verdes; browser vizinhos 3/3; TIA não usado (instrução da sessão: `--no-tia`); RCRCRC: a refatoração do `perfil-indicator` para `papelNoPainelCorrente()` é equivalente (mesmo painel corrente e contexto) |
| K | Adequação da suíte | ⏭️ | K1 `k1-oraculo-fraco.sh` exit 0; K2 medido pela sessão (score, duração e sobreviventes a colar); revisão adversarial do `04`: FEITA |
| L | Consistência documental | ❌ | QA-02, QA-03, QA-05 — L1 `ids-ct.sh` exit 0, L2 `citacoes.sh` exit 0, L4 `conformidade-rules.sh` exit 0 (14 rules, tabela confere), L6 `checkbox-sem-evidencia.sh` exit 0; L5: docs pt × en equivalentes; L7: "Marca composta" e "Bloco do usuário" no glossário, sem divergência |

**L6 — reprodução das alegações do `03`**

Conferem:

- 82 verdes (50 + 15 + 17)
- browser `3/3/20`
- `3911/3908/19018/3 pulados`
- "34 CT + 2 CT-B"
- 14 rules
- READMEs 74 / 175 / 204 / 1.891 (`SiteDeDocumentacaoTest` verde)
- `pint` (`--test` passou)

Não reproduzidos:

- o lote "841, 840 passaram" (outro recorte, ver *Suspeitas*)
- `phpstan` e `filacheck` (não rodados, ver *Não Verificado*)

Nenhuma degradação declarada a desmentir.

## Débitos Aceitos

- (a definir pela sessão no fechamento; candidatos: QA-03, QA-05)

## Suspeitas Não Confirmadas

- `03:62` declara `{"tests":841,"passed":840}` sem explicar o teste que não passou (pulado?). Meu recorte da regressão nomeada (465 + 578) passou inteiro, mas não é o mesmo conjunto, então não reproduzi o número.
- O axe acusou contraste em elementos nativos do Filament no `/admin` (h1, account widget). Isso é anterior à feature e sem nó `kit-*` nos trechos lidos. A lista completa não foi lida.

## Não Verificado

- H — acessibilidade. Motivo: a página já reprova no axe por elementos do Filament anteriores à feature, então não há `assertNoAccessibilityIssues()` limpo para isolar os nós `kit-*`. O relatório lido foi truncado, sem Playwright MCP. Teclado e foco não se aplicam (bloco e composição são `span` não interativos).
- K2 — mutation score. Motivo: medido pela sessão em outro processo, por instrução. A sessão cola score, `Duration` e sobreviventes, e a plausibilidade (N × tempo dos testes cobridores) tem de ser julgada antes de aceitar.
- P-06 na barra lateral aberta (sonda com o cabeçalho da barra não visível em 1280 px). Coberto pelo QA-04.
- L6 — `phpstan analyse` e `filacheck`. Motivo: não rodados (custo; o `filacheck` corrige por padrão neste projeto).
- App servido em `:8097`: o banco não tem a migration `2026_10_05_100000_add_cabecalho_to_kit_settings` (57 propriedades `kit` em `settings`). Destino 4: rodar `migrate` no ambiente servido antes da validação manual. As dimensões dinâmicas foram feitas em processo, pelo arnês da suíte.
