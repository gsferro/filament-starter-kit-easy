# Relatório de QA — fix/logo-dark-do-tenant: a logo da organização (clara e escura) no topo do `/app`

> Requisito: `00-requisito.md` · Plano: `01-plano-acao.md`
> Perfil de esforço: completo (UI com JS: o swap depende da classe `dark` que o `theme.js` fixa; além disso, Impacto 3 de vazamento entre organizações no `04`)
> Natureza da wiki: correção · Toca infra compartilhada: sim → `CabecalhoDoPainel`, `IdentidadeDoKit`, `TenantFactory::comIdentidadeVisual()` · Regressão: sim
> Independência: sub-agente fw-qa-gate/opus, sem acesso à conversa
> Cobertura: 8 de 12 dimensões verificadas ou provadas não aplicáveis · teto: APROVADO COM DÉBITO

## Veredito — Ciclo 1

**REPROVADO → especificação**

- Blocker: 0 · Major: 1 · Minor: 8 · Cosmético: 1
- Não verificadas: G (nível 3), H (axe e teclado), J (TIA), K (K2). As causas estão em *Não Verificado*.
- `RQ` abertas: nenhuma
- Ambiente: app em `http://127.0.0.1:8012` (a tela de login respondeu, mas não houve login, ver *Não Verificado*) · Pest 5.1.1 · MCP: Playwright indisponível; Boost não usado
- O código entregue não teve defeito confirmado. O único Major é documental (L6) e se corrige no texto do `03`.

## Achados

### QA-01 — Alegações do `03` que nenhum comando reproduz · Major · destino 1
- **Dimensão**: L (L6)
- **Relacionado a**: passo 6; `## Despachos`, linha 9
- **Esperado**: todo `[x]` com número, e toda auditoria marcada como "silencioso", sai do comando que a gera.
- **Observado**: `03:35` e `03:37` dizem "`SiteDeDocumentacaoTest` 91/91", mas a HEAD dá **68/68** (254 asserções). Com `RedeDeDocumentacaoTest` somado (20) ainda não chega a 91. Em `03:170`, `## Despachos` diz "`citacoes.sh` silenciosos", mas o script sai com **exit 1** e 3 linhas (QA-02).
- **Repro**: `XDEBUG_MODE=off php vendor/bin/pest tests/Kit/SiteDeDocumentacaoTest.php --compact` dá `tests:68`; `bash .ai/skills/feature-wiki/scripts/citacoes.sh {wiki}` dá exit 1.
- **Evidência**: `grep -rn "91/91" {wiki}` devolve `03-progresso.md:35` e `03-progresso.md:37`. Os demais números conferem: 41/41, 45/45, 86/86 (447), 9/9 (54), 158/712, filacheck 17 regras, badge 1.976, 77 especificações.
- **Ação exigida**: trocar as duas ocorrências de "91/91" pela saída real e reabrir ou corrigir a auditoria da linha 9 de `## Despachos`.

### QA-02 — Citações `arquivo:símbolo:linha` erradas no `01` · Minor · destino 1
- **Dimensão**: L (L2)
- **Observado**: `01:60` e `01:99` citam o método `resolverSegmentos()` de `app/Support/CabecalhoDoPainel.php`, citado na linha 202, mas o método está na linha **206** na HEAD (145 na `main`). `01:63` cita `emTelaDeAutenticacao():275`, que está na **279**.
- **Repro**: `bash .ai/skills/feature-wiki/scripts/citacoes.sh {wiki}` sai com exit 1 e 3 linhas.
- **Ação exigida**: reancorar as três (só `01:60`, `01:63` e `01:99` repetem esses valores).

### QA-03 — `## Conformidade com Rules` ainda em rascunho e com evidência falsa · Minor · destino 1
- **Dimensão**: L (L4)
- **Observado**:
  - `.ai/rules/css-filament.md` e `.ai/rules/providers.md` casam `app/Providers/Filament/AppPanelProvider.php` e não têm linha na tabela.
  - A linha de `providers-filament.md` diz "n.a. — nenhum `PanelProvider` editado", mas o `AppPanelProvider` foi editado no RC-02 (só comentário; o veredito n.a. procede, a evidência não).
  - Sete linhas continuam "a aplicar", e a de `support.md` diz "a medir".
- **Repro**: `bash .ai/skills/feature-wiki/scripts/conformidade-rules.sh {wiki} main` dá exit 1 com 2 linhas. Conferi o código contra as rules e não achei violação.
- **Ação exigida**: fechar cada linha com aplicada / n.a. e a evidência, acrescentar as duas rules que faltam e corrigir a evidência de `providers-filament.md`.

### QA-04 — Gherkin do `04` contradiz os testes de CT-50 e CT-51 · Minor · destino 1
- **Dimensão**: L (L1/L3)
- **Observado**:
  - Os `Exemplos` de CT-51 (`04:421-422`) ainda têm `/app/new` e `/app`. O dataset tem uma linha `/app/password-reset/request`. A troca está em `## Desvios do Plano` e na linha M51, mas não no Gherkin.
  - CT-50 (`04:376`) ainda afirma "contém kit/logo-ct.png" na linha `sem vínculo`. O teste afirma 404, `sn-brand` e a ausência de `globex.png`.
  - O checklist (`04:574`) diz "nenhum `Então` desta wiki é 4xx", e hoje o CT-50 afirma 404.
- **Repro**: comparar `04:366-376` e `04:409-422` com `tests/Tenancy/CabecalhoDoPainelTenancyTest.php` (linhas 434 a 527).
- **Ação exigida**: alinhar os Exemplos e a linha do checklist ao teste, com a marca *(alterado em …)*.

### QA-05 — Contagem "Sem matador" defasada · Cosmético · destino 1
- **Dimensão**: L (L1)
- **Observado**: `04:21` diz "Sem matador: 0" e `03:44` diz "0 sem matador". O `grep -cE '^\| M-?[0-9]+.*sem matador' 04-casos-de-teste.md` dá **1** (M51, declarado na própria tabela).
- **Ação exigida**: recalcular pelo `grep -c` e corrigir os dois arquivos.

### QA-06 — Integridade do `03` · Minor · destino 1
- **Dimensão**: L (L6)
- **Observado**:
  - `03:93-104` é um bloco duplicado e corrompido: o título "`## Conformidade com Rules`: `conformidade-rules.sh {wiki} main` silencioso", itens repetidos da Verificação Final e uma segunda `## Revisão do Diff (step 9)` vazia.
  - `## Despachos` é cortada pela linha em branco `03:164`, e as linhas 4 a 9 ficam fora da tabela.
  - O RC-11 não existe em lugar nenhum, embora `03:168` diga "RC-01 a RC-12".
  - Não há despacho registrado para a derivação do `04` nem para a revisão adversarial que o cabeçalho do `04` declara FEITA.
- **Ação exigida**: remover o bloco duplicado, reunir a tabela, explicar o RC-11 e registrar os despachos do step 7.

### QA-07 — Vocabulário fora do glossário · Minor · destino 1
- **Dimensão**: L (L7)
- **Observado**:
  - A wiki e as docs dizem "composição do cabeçalho" e "marca simples", e o glossário (`wikis/glossario.md:19`) tem "Marca composta", com a "logo solta" como contraste.
  - A definição de "Marca composta" diz que, "com nenhum segmento ligado, a marca é a de sempre", o que deixou de valer no `/app` com organização aberta.
  - "Organização aberta" (a da rota, contra a "da sessão", na ADR-03) foi decidida nesta feature e não entrou no glossário.
- **Ação exigida**: alinhar os termos ao glossário, atualizar a entrada "Marca composta" e criar a entrada "Organização aberta".

### QA-08 — `alt` da logo da organização nomeia a aplicação · Minor · destino 1
- **Dimensão**: H
- **Relacionado a**: P-06, ADV-25 (recusado)
- **Observado**: no topo do `/app/acme`, a `<img>` da logo da Acme tem `alt` "Logo {app.name}" (`vendor/filament/filament/resources/views/components/logo.blade.php:alt:33`) ou `config('app.name')` (`resources/views/filament/cabecalho-do-painel.blade.php:alt:14`). Antes desta entrega o `alt` descrevia a imagem certa; agora descreve outra marca.
- **Ação exigida**: P-06 já declara isso fora de escopo. Registrar como débito de acessibilidade no `03` (ou perguntar ao solicitante), porque a divergência nasce desta entrega.

### QA-09 — Marca misturada no tema escuro (P-03) · Minor · destino 1
- **Dimensão**: A
- **Relacionado a**: RQ-05, P-03, CT-40 (linha "só clara"), CT-43 ("queda só da escura")
- **Observado**: com a marca separada e a organização só com a clara, o tema escuro mostra a escura **da instalação**, ou seja, outra identidade no topo da Acme. O Adendo 1 diz "caindo para a da instalação quando ela **não tem logo**", e essa organização tem logo. A P-03 replica a regra da tela de bloqueio, tem precedente no glossário ("Variante escura … nunca caindo na logo clara") e o formulário avisa "Em branco, usa a da instalação". Mas foi decidida pela sessão, sem pergunta ao solicitante, e no topo o efeito é muito mais visível do que no bloqueio.
- **Repro**: CT-43, linha `queda só da escura`, afirma `fi-logo-dark` = `kit/logo-dark-ct.png` com a clara `acme.png`.
- **Ação exigida**: pergunta de confirmação ao solicitante (`Qn` no `00`). Se ele confirmar o comportamento atual, fecha como destino 5 no ciclo 2.

### QA-10 — Reconciliação (step 10) inacabada · Minor · destino 1
- **Dimensão**: L
- **Observado**:
  - `## Verificação Final` do `03` está inteira em `[ ]`.
  - O segundo item do passo 5 do `03` ("vermelho com o passo 2 revertido") segue aberto.
  - Os pré-requisitos do `05` e o Roteiro "Desenhado × Implementado" estão "a preencher".
- **Ação exigida**: fechar a Verificação Final com evidência e preencher o Roteiro do `05` antes do ciclo 2.

## Matriz de Rastreabilidade

Sem lacuna. O `rastreabilidade.sh` saiu com exit 0. O `git diff` por passo cobre os passos 1 a 6. O passo 7 (`INDEX.md`) fica para depois do Estado final, por desenho. Não existe `07-tickets/`.

## Dimensões

| # | Dimensão | Status | Observação |
|---|----------|--------|------------|
| A | Cobertura do requisito | ⚠️ | QA-09 — `rastreabilidade.sh` exit 0; `indice.sh --check`: sem `07-tickets/`; o diff de cada passo faz o que o passo diz (`logosPara`, `organizacaoAberta`, memo único, factory, textos) |
| B | Fronteiras e dados | ✅ | leitura mais CT-40 (null, branco e órfão nas duas variantes, 12 linhas, verde); o upload não mudou |
| C | Matriz de permissão | ✅ | membro (CT-42…), não membro com 404 (CT-50), `master_global` sem vínculo (CT-56), `admin` em `/admin`, `infra` em `/infra`, visitante (CT-51); nenhuma ação destrutiva |
| D | Observabilidade | ✅ | nenhum log novo; `storage/logs/configuracoes-2026-10-07.log` lido, sem PII; o `warning` existente leva só o id do painel |
| E | Performance | ✅ | grep de `DB::`, `query()`, `->get()` e `->where(` no diff: exit 1 (nenhum acesso a banco); `exists()` uma vez por request (memo) |
| F | UX de erro | n/a | o diff não acrescenta mensagem, validação nem estado de erro (grep de `Notification`, `ValidationException`, `abort`, `throw`: exit 1); o 404 do CT-50 já existia |
| G | Tema e cor | ⏭️ parcial | `dark-mode.sh --mecanismo` exit 1 (Filament, classe `dark`); nível 1 exit 0; nível 2: CT-B01 9/9 (54), alternância nos dois sentidos e nas duas formas; nível 3 não rodou |
| H | Acessibilidade | ⏭️ parcial | QA-08 (estático); CT-B01 sem `assertNoAccessibilityIssues()`; teclado não verificado |
| I | Segurança da superfície nova | ✅ | eixos 9 cobertos pelo step 9 (11 RC, nenhum rejeitado; RC-11 ausente, ver QA-06); além dele: IDOR por CT-50 e CT-56 rodados e verdes; nenhuma rota nova, nenhum mass assignment, upload igual, nenhum dado sensível |
| J | Regressão adjacente | ⏭️ parcial | TIA não rodou; ancestrais por ID verdes: CT-16, CT-04, CT-27, CT-11, CT-15, CT-29 a 31; regressão do `01` 126/126 (659); vizinhos da factory e da identidade 227/227 (1.250); browser `LogoDarkMode` e `CabecalhoDoPainel` 5/5; o RCRCRC (`CabecalhoDoPainel`: Core, Recent, Repaired) bate com `## Impacto` |
| K | Adequação da suíte | ⏭️ | K1 `k1-oraculo-fraco.sh` exit 0; K2 não rodou; os 6 mutantes manuais do `03` são anteriores ao refactor dos testes e não têm score nem Duration; revisão adversarial do `04`: feita |
| L | Consistência documental | ❌ | QA-01 a 07 e QA-10; L1 `ids-ct.sh` (3 arquivos juntos) exit 1, só IDs das ancestrais, como o `### Colisão de IDs` prevê; L2 `citacoes.sh` exit 1; L4 `conformidade-rules.sh` exit 1; L6 `checkbox-sem-evidencia.sh` exit 0, e os números foram reproduzidos um a um (QA-01) |

## Débitos Aceitos

- Nenhum ainda. Os Minor e o Cosmético deste ciclo só viram débito se a sessão os aceitar.

## Suspeitas Não Confirmadas

- O `IdentidadeVisualTest` sai com `warnings: 2` e `warning_details` vazio. Não reproduzi a causa.
- O CT-55 lê `docs/` com `naArvoreDoKit()`: soma 2 pulados ao teto do projeto instalado. Isso é assunto da validação antes da tag, que está fora do escopo daqui.

## Não Verificado

- G, nível 3 — motivo: Playwright MCP indisponível, e o Cloudflare Turnstile do `/app/login` barra o login automatizado no app servido. Só vi a tela de login, que mostra a clara da instalação. Nota de infra (destino 4): os dados de QA passados não previam o captcha.
- O update real do Livewire (`refresh-topbar`) no app servido — motivo: o mesmo bloqueio de login; o CT-53 cobre por `Livewire::test` (D5).
- H, `assertNoAccessibilityIssues` e teclado — motivo: fora do arnês do CT-B01, e o app servido não abriu (captcha).
- J, passo 1 (`pest --parallel --tia`) — motivo: proibido pelo orquestrador (memória do host).
- K2, `pest --mutate` — motivo: proibido pelo orquestrador; os 6 mutantes manuais do `03` antecedem o refactor dos testes e não têm score nem Duration.

## Veredito — Ciclo 2
**REPROVADO → especificação**

> Independência: sub-agente fw-qa-gate/opus, sem acesso à conversa
> Cobertura: 8 de 12 dimensões verificadas ou provadas não aplicáveis · teto: APROVADO COM DÉBITO

- Blocker: 0 · Major: 2 · Minor: 5 · Cosmético: 0
- Achados abertos: QA-11 e QA-13 (Major, novos); QA-12 (Minor, novo); QA-07 e QA-10 (Minor, fechados só em parte); QA-08 e QA-09 (Minor, débito que depende do solicitante).
- Não verificadas: G (nível 3), H (axe e teclado), J (passo 1, TIA) e K (K2). As causas estão em *Não Verificado*.
- `RQ` abertas: nenhuma. A Q7 está "retirada — coberta por P-03 (confirmar no PR)" (ver QA-09).
- Ambiente: app em `http://127.0.0.1:8012`, servido da própria worktree (`php -S … wt-logo\…\server.php`), sem anti-robô. O login foi feito por `curl` no endpoint `livewire-7204384e/update`. Pest 5. Playwright MCP indisponível. Boost não foi usado.
- O código não mudou desde o ciclo 1: `git diff --stat 54ab9d7 HEAD -- app tests database resources docs config CHANGELOG.md README*` vem vazio. Não houve defeito de produto no app servido. Os dois Major são de texto (L3 e L6).

## Verificação dos achados do Ciclo 1

| QA | Estado no ciclo 2 | Evidência |
|---|---|---|
| QA-01 (Major, L6) | **fechado** | `pest tests/Kit/SiteDeDocumentacaoTest.php` dá 68/68 com 254 asserções. Os três arquivos de docs juntos dão 91/91 com 343 asserções. Os dois números batem com `03:35` e `03:37`, que agora trazem o comando ao lado. A linha 9 de `## Despachos` foi corrigida. |
| QA-02 (Minor, L2) | **fechado** | `citacoes.sh {wiki}`: exit 0, sem saída. |
| QA-03 (Minor, L4) | **fechado** | `conformidade-rules.sh {wiki} main`: exit 0. As 14 linhas estão com aplicada ou n.a. e têm evidência. `css-filament.md` e `providers.md` entraram. Conferi o código contra as rules e não achei violação. |
| QA-04 (Minor, L1/L3) | **fechado** | `04:376` (CT-50, sem vínculo), `04:421-422` (CT-51, `/app/password-reset/request`) e `04:573` (o único 4xx é o 404 do CT-50) estão alinhados ao teste e marcados *(alterado em 2026-10-07: QA-04)*. |
| QA-05 (Cosm., L1) | **fechado** | O `grep -cE '^\| M-?[0-9]+.*sem matador'` dá 1, como dizem `04:21` e `03:44`. As outras contagens do cabeçalho também batem: 18 cenários, 11 regras, 47 mutantes no `04` e 5 no `05`. |
| QA-06 (Minor, L6) | **fechado** | Não há mais bloco duplicado. A tabela de `## Despachos` está contínua (`03:165-177`). O RC-11 está explicado na linha 7 ("o revisor não emitiu"). Os despachos do step 7 estão nas linhas 10 e 11. |
| QA-07 (Minor, L7) | **parcial** (segue Minor) | O glossário atualizou "Marca composta" e criou "Organização aberta". Mas "marca simples" continua fora do glossário, que chama o mesmo conceito de "logo solta". O termo está em `docs/pt/recursos/configuracoes-do-kit.md:286`, no `CHANGELOG.md`, em `CabecalhoDoPainel.php` e em 7 arquivos da wiki. O `grep -rn "logo solta"` na wiki e nas docs dá 1. |
| QA-08 (Minor, H) | **débito registrado** (`03:198`; Blocker `03:183`) | Confirmado no app. O topo de `/app/acme` mostra o `alt` "Logotipo de Starter Kit" (marca simples) e "Starter Kit" (composição) sobre `acme-qa.png`. A tela de bloqueio mostra o `alt` "Acme QA". |
| QA-09 (Minor, A) | **aberto, como débito** | A Q7 foi retirada pela própria sessão ("confirmar no PR"), e o solicitante não respondeu. Não fecha como destino 5. A mitigação que o plano promete para esse risco não foi entregue (QA-11). |
| QA-10 (Minor, L) | **parcial** (segue Minor) | O Roteiro do `05` (4 linhas) e os pré-requisitos estão fechados, e o item do passo 4 também. Mas `## Verificação Final` ainda tem 11 `[ ]`: `03:53`, `56–61`, `63`, `67`, `69`, `70` e `72`. Alguns já têm prova: os itens 56 e 57 correspondem às rodadas deste ciclo (86/86 e 9/9), e o 63 às marcas do QA-04. O item 61 (`/code-review high`) segue sem linha em `## Despachos`. |

## Achados novos

### QA-11 — Mitigação declarada no `01` e no `02` que a documentação não entrega · Major · destino 1
- **Dimensão**: L (L3)
- **Relacionado a**: ADR-01 (Riscos), `## Riscos` do `01`, P-03, QA-09
- **Esperado**: `01-plano-acao.md:186` diz: *"Mitigação: o formulário já avisa 'Em branco, usa a da instalação'; a documentação recomenda enviar o par."* `02-decisoes-arquiteturais.md:26` diz: *"…e a documentação recomenda enviar o par."*
- **Observado**: nem a doc pt nem a en recomendam que a organização envie a clara e a escura. As únicas recomendações (`docs/pt/recursos/configuracoes-do-kit.md:247-248`, `docs/en/…:254-256`) falam do fundo da logo da instalação. `docs/pt…:260-261` só descreve a queda por variante. Essa recomendação é justamente a mitigação da marca misturada no tema escuro (QA-09).
- **Repro**: `grep -n -i "recomend\|recommend" docs/pt/recursos/configuracoes-do-kit.md docs/en/recursos/configuracoes-do-kit.md` não traz nenhuma linha sobre o par da organização. `grep -rn -i "recomend" {wiki}` traz `01:186` e `02:26`.
- **Destino**: 1
- **Ação exigida**: escolher um caminho. (a) Acrescentar a frase nas docs pt e en, perto de `pt:260`/`en` equivalente, com a ADR-01 como origem. (b) Ou tirar a frase de **`01:186` e `02:26`**, os dois lugares que a repetem, e marcar *(alterado em …)*.

### QA-12 — RC-03 dado como "aplicado", mas os docblocks de `marca()` e `marcaEscura()` seguem com "de hoje" · Minor · destino 2 (código) e 1 (`03`)
- **Dimensão**: L (L3/L6)
- **Relacionado a**: RC-03 (`03:82`), item `[x]` em `03:16`
- **Esperado**: o RC-03 diz *"'a marca é a de hoje'/'o de hoje' em `CabecalhoDoPainel` … aplicado: 'o par de `logos()`'"*. `03:16` diz que os docblocks de marca() e marcaEscura() foram revisados no RC-03.
- **Observado**: `app/Support/CabecalhoDoPainel.php`, nas linhas 61 ("a marca é a de hoje"), 78 ("do contrário, o de hoje") e 120 ("a marca deve ser a de hoje") da HEAD do ciclo 2, estão idênticos aos de `main` (`git show main:… | grep -n "de hoje"` dá as linhas 52, 69 e 111). O `git log main..HEAD -- app/Support/CabecalhoDoPainel.php` mostra só `e969341`: a rodada pós-revisão (`54ab9d7`) não tocou o arquivo. Desde esta entrega, `marcaEscura()` devolve a escura **da organização**, que não é mais "a de hoje".
- **Repro**: `grep -n "de hoje" app/Support/CabecalhoDoPainel.php`
- **Ação exigida**: alinhar os três docblocks (a linha `:217`, de autenticação, segue verdadeira) e corrigir a evidência de `03:16` e do RC-03.

### QA-13 — Contagem de arquivos do diff defasada pela reciclagem · Major · destino 1
- **Dimensão**: L (L6)
- **Relacionado a**: `03:71`
- **Esperado**: o item diz *"`git diff --name-only main...HEAD` lista 24 arquivos; fora da tabela do `01` só `wikis/convencoes.md`, `TenantsTable.php`, `AppPanelProvider.php` e `tests/Pest.php`"*.
- **Observado**: na HEAD são **26**. Em `54ab9d7` eram 24. A reciclagem do ciclo 1 acrescentou `wikis/glossario.md` (QA-07), que também fica fora da tabela do `01` (`grep -n glossario 01-plano-acao.md` não acha nada), e o `06`. A severidade segue a tabela da L6 (número que nenhum comando reproduz). A correção é de uma linha.
- **Repro**: `git diff --name-only main...HEAD | wc -l` dá 26. `grep -rn "24 arquivos" {wiki}` dá só `03:71`.
- **Ação exigida**: refazer o item pelo comando e acrescentar `wikis/glossario.md` à lista do que ficou fora do plano, com o motivo (QA-07).

## Matriz de Rastreabilidade

Nenhuma lacuna. `rastreabilidade.sh` saiu com exit 0. O diff por passo é o mesmo do ciclo 1, porque o código não mudou. Não há `07-tickets/`.

## Dimensões

| # | Dimensão | Status | Observação |
|---|----------|--------|------------|
| A | Cobertura do requisito | ⚠️ | QA-09 (débito). `rastreabilidade.sh` exit 0. `indice.sh --check` não se aplica (sem `07-tickets/`). RQ-05 confirmada no app (detalhe abaixo da tabela). |
| B | Fronteiras e dados | ✅ | Globex sem logo cai no par da instalação. CT-40 (null, branco, órfão) passa. O upload não mudou. |
| C | Matriz de permissão | ✅ | `master_global` nas três organizações (CT-56). `/app/inexistente` dá 404 sem logo. Não membro: CT-50 (404 e controle positivo). Nenhuma ação destrutiva. |
| D | Observabilidade | ✅ | Nenhum log novo no diff. `configuracoes-2026-10-07.log` registrou as duas gravações deste QA só com `user_id` e nomes de campo, sem PII. Nenhum erro no `laravel.log` durante a sessão. |
| E | Performance | ✅ | O código é o do ciclo 1: nenhum acesso a banco no diff e uma resolução por request (memo). |
| F | UX de erro | n/a | O diff não acrescenta mensagem nem estado de erro (prova do ciclo 1). O 404 do Sentinel já existia. |
| G | Tema e cor | ⏭️ parcial | `dark-mode.sh --mecanismo` exit 1 (Filament, classe `dark`). Nível 1 exit 0. Nível 2: CT-B01 9/9 (54 asserções), com `getComputedStyle` e clique real no alternador. O HTML servido traz o par nas classes `fi-logo-light`/`fi-logo-dark` em todos os painéis. Nível 3 não rodou. |
| H | Acessibilidade | ⏭️ parcial | `alt` conferido no app (QA-08). axe e teclado não rodaram. |
| I | Segurança da superfície nova | ✅ | Eixos 9 cobertos pelo step 9: `## Revisão do Diff` com 11 RC, nenhum rejeitado. O `/code-review` não está registrado (QA-10). Além do step 9: troca de organização verificada no app, e IDOR por CT-50, CT-56 e `/app/inexistente`. |
| J | Regressão adjacente | ⏭️ parcial | TIA proibido. Código idêntico ao do ciclo 1, então os ancestrais rodados por ID no ciclo 1 continuam valendo. Reexecutados: 86/86 (447), 9/9 (54), docs 91/91 (343), `filacheck` 17/17, `pint --test` nos 12 PHP. |
| K | Adequação da suíte | ⏭️ | K1 `k1-oraculo-fraco.sh` exit 0. K2 proibido pelo orquestrador. A revisão adversarial do `04` foi feita. |
| L | Consistência documental | ❌ | QA-11, QA-12, QA-13, mais o que resta de QA-07 e QA-10. L1 `ids-ct.sh` (3 arquivos juntos) exit 1, só com IDs das ancestrais, como `### Colisão de IDs` prevê. L2 exit 0. L4 exit 0. L6 `checkbox-sem-evidencia.sh` exit 0, e cada número foi reproduzido: 86/447, 9/54, 68/254, 91/343, 12 PHP, 17 regras, 18/11/47/5/1; diverge só o 24 (QA-13). |

**Detalhe da RQ-05 no app (dimensão A).**

- **Marca simples**:
  - `/app/acme` tem o par `organizacoes/logos/acme-qa(-dark).png`.
  - `/app/globex`, `/admin`, `/infra` e `/app/login` (anônimo) têm `kit/logo-qa(-dark).png`.
- **Composição** (logo da marca ligada):
  - `/app/acme` tem o par da Acme dentro de `kit-cabecalho`, e `/admin`, `/infra` e `/app/globex` o da instalação.
  - O `$refresh` Livewire da `Topbar` em `/app/acme` mantém a Acme.
  - Desliguei a opção ao terminar. A trilha de auditoria registrou uma única propriedade alterada em cada gravação, e o estado final é `cabecalho_logo_da_marca=false`, com a logo e a logo escura da instalação preservadas.
- **Tela de bloqueio** de `/app` (sessão na Acme): par da Acme, `alt` "Acme QA". Destravada em seguida.

## Débitos Aceitos

- Nenhum aceito pelo solicitante. QA-08 e QA-09 estão registrados no `03` (`## Débitos` e `## Blockers`) com confirmação pedida no PR.

## Suspeitas Não Confirmadas

- `IdentidadeVisualTest` volta a sair com `warnings: 2` e `warning_details` vazio. A causa não foi reproduzida (2 rodadas, ciclos 1 e 2).

## Não Verificado

- **G, nível 3** (olhar o swap renderizado): Playwright MCP indisponível e nenhum navegador neste agente. O swap é provado pelo CT-B01 (`getComputedStyle` e clique real). O app foi conferido só no HTML.
- **H, axe e teclado**: nenhum CT-B tem `assertNoAccessibilityIssues()`, e o gate não escreve teste.
- **J, passo 1** (`pest --parallel --tia`): proibido pelo orquestrador.
- **K2** (`pest --mutate`): proibido pelo orquestrador. Os 6 mutantes manuais do `03` são anteriores ao refactor dos testes e não têm score nem `Duration`.

## Veredito — Ciclo 3
**APROVADO COM DÉBITO**

> Cobertura: 8 de 12 dimensões verificadas ou provadas não aplicáveis · teto: APROVADO COM DÉBITO

- Blocker: 0 · Major: 0 · Minor: 5 · Cosmético: 1
- Achados abertos:
  - QA-12 (Minor), fechado só em parte;
  - QA-14 e QA-15 (Minor, novos);
  - QA-16 (Cosmético, novo);
  - QA-08 e QA-09 (Minor), débitos que dependem do solicitante.
- Não verificadas: G (nível 3), H (axe e teclado), J (passo 1, TIA) e K (K2). As causas estão em *Não Verificado*.
- `RQ` abertas: nenhuma. A Q7 continua "retirada — coberta por P-03 (confirmar no PR)" (QA-09).
- Ambiente: app em `http://127.0.0.1:8012`, sem anti-robô. Login feito por `curl` no endpoint `livewire-7204384e/update` (componente `TelaLogin`, método `authenticate`). Pest 5. PCOV e Xdebug carregados (`php -m`). Playwright MCP indisponível. Boost não foi usado.
- Ciclo 3 é o último pelo teto do perfil completo. Os dois Major do ciclo 2 (QA-11 e QA-13) estão fechados. O que sobra é texto do `03`, uma frase das docs e um docblock, e nada disso é defeito de produto.

## Verificação dos achados abertos do Ciclo 2

| QA | Estado no ciclo 3 | Evidência |
|---|---|---|
| QA-07 (Minor, L7) | **fechado** | `wikis/glossario.md:20` criou "Marca simples" e a declara sinônimo de "logo solta" das wikis anteriores. "Marca composta" (`:19`) e "Organização aberta" seguem atualizadas. `docs/pt…:287` e `CHANGELOG.md:12` usam o termo do glossário. |
| QA-10 (Minor, L) | **fechado** | Todo item de `## Verificação Final` tem agora evidência (`[x]`) ou motivo (`[ ]`). `checkbox-sem-evidencia.sh` dá exit 0. Os `[ ]` restantes (`03:53`, `58`, `59`, `60`, `67`, `69`, `70`, `72`) foram para *Não Verificado* e *Débitos*. O caso `03:61` virou QA-14. |
| QA-11 (Major, L3) | **fechado** | `docs/pt/recursos/configuracoes-do-kit.md:262` e `docs/en/…:271` passam a recomendar o par da organização. Isso cumpre a mitigação de `01:186` e `02:26`. A precisão da frase virou QA-15. |
| QA-12 (Minor, L3/L6) | **parcial** (segue Minor) | Feito: `grep -n "de hoje" app/Support/CabecalhoDoPainel.php` só acha `:217` (autenticação, verdadeira), e `03:16` foi corrigido. Falta: `03:82` (RC-03) e `03:92` (Ponytail do diff, "Aplicados") ainda dizem "ressalva da P-12 só no docblock da classe" e "docblock de `organizacaoAberta()` em 3 linhas". O código contradiz os dois:<br>• `app/Support/CabecalhoDoPainel.php:resolverSegmentos():223` (`resolverSegmentos()`) mantém "a P-12 vale fora do `/app` com organização aberta", e `03:16` diz o mesmo.<br>• O docblock de `organizacaoAberta()` (`:175-180`) tem 5 linhas de texto.<br>• `git show 54ab9d7 --stat` não toca o arquivo, e o código de `e969341` é o de hoje, então o corte nunca foi feito. |
| QA-13 (Major, L6) | **fechado** | `git diff --name-only main...HEAD \| wc -l` dá 26, como `03:71`. A lista do que ficou fora do plano inclui `wikis/glossario.md` e o `06`, com o motivo. |
| QA-08 (Minor, H) | **débito, solicitante** | Confirmado de novo no app: em `/app/acme`, `alt="Logotipo de Starter Kit"` sobre `organizacoes/logos/acme-qa.png`. Registrado em `03:206` e `03:191`. |
| QA-09 (Minor, A) | **débito, solicitante** | A Q7 do `00:40` segue "retirada — coberta por P-03 (confirmar no PR)", sem resposta do solicitante. A mitigação documental agora existe (QA-11). |

## Achados novos

### QA-14 — `03:61` fecha com `[x]` uma checagem que não rodou · Minor · destino 1
- **Dimensão**: L (L6)
- **Esperado**: `[x]` só para checagem feita. Para o mesmo caso, uma checagem substituída por outro passe, `03:53` usa `[ ]` com motivo ("o ponytail do diff rodou como despacho 8, não como `/ponytail:ponytail-review`").
- **Observado**:
  - `03:61` diz "[x] `/code-review high` não rodado como `/code-review`…". O próprio item afirma que a checagem não aconteceu.
  - A evidência diz "RC-01 a RC-12 fechados", mas o RC-11 não existe (`03:179`: "o revisor não emitiu RC-11"). O RC-08 está "sem ação registrada".
  - O despacho 12 (`03:184`) está na coluna Step "11", mas descreve um passe do step 9 que não foi disparado.
- **Repro**: `sed -n '53p;61p;184p' 03-progresso.md`; `grep -n "RC-11" 03-progresso.md`.
- **Ação exigida**: reabrir `03:61` como `[ ]`, com o motivo, como em `03:53` (ou rodar o `/code-review`). Escrever "RC-01 a RC-10 e RC-12" e corrigir o Step do despacho 12.

### QA-15 — A recomendação nova das docs descreve o risco só para uma configuração · Minor · destino 1
- **Dimensão**: L (L5)
- **Esperado**: a consequência descrita vale no caso mais comum. O padrão é a marca simples, sem nenhum segmento do cabeçalho ligado.
- **Observado**: `docs/pt/recursos/configuracoes-do-kit.md:262` diz "o tema escuro mostra a escura da instalação **ao lado do nome da organização**". `docs/en/…:271` diz "next to the organisation's name". O nome só aparece com o interruptor "Nome do painel na marca do topo" ligado (`app/Support/CabecalhoDoPainel.php:resolverSegmentos():223`). Com a marca simples, `marcaEscura()` devolve `logosPara()['escura']` (`app/Support/IdentidadeDoKit.php:logosPara():94`). Nesse caso a escura da instalação aparece sozinha, no lugar da logo da organização, que é o efeito mais forte e o que o QA-09 descreve.
- **Repro**: `grep -n "ao lado do nome da organização\|next to the organisation's name" docs/*/recursos/configuracoes-do-kit.md`. Ler `app/Support/CabecalhoDoPainel.php:marca():65` e `:232-241`.
- **Ação exigida**: reescrever a frase em pt e en sem "ao lado do nome da organização". Por exemplo: "o tema escuro mostra a escura da instalação no lugar da logo da organização". `01:186` e `02:26` não repetem o trecho e ficam como estão.

### QA-16 — Docblock de `marca()` com dois "dois-pontos" seguidos · Cosmético · destino 2
- **Dimensão**: L (L3)
- **Observado**: `app/Support/CabecalhoDoPainel.php:marca():65` diz "a marca é o par de `logos()`: o da organização aberta no `/app`, senão o da instalação: nenhum segmento ligado, tela de autenticação…". Com os dois "dois-pontos", a lista parece enumerar os casos da instalação. As linhas de prosa `:61`, `:78` e `:120`, de 162 a 172 colunas, também saem da quebra em ~100 do resto do docblock.
- **Repro**: `awk 'length>110{print FNR": "length}' app/Support/CabecalhoDoPainel.php`
- **Ação exigida**: trocar o segundo ":" por "—" ou por um parêntese e quebrar as três linhas na largura do resto do bloco. Depois, `pint` e `citacoes.sh`.

## Matriz de Rastreabilidade

Nenhuma lacuna:
- `rastreabilidade.sh` saiu com exit 0.
- Desde o ciclo 2 só mudaram docblocks, docs, glossário e wiki (`git diff --stat fc7a712 HEAD`), então o diff por passo é o mesmo.
- Não há `07-tickets/`.

## Dimensões

| # | Dimensão | Status | Observação |
|---|----------|--------|------------|
| A | Cobertura do requisito | ⚠️ | QA-09 (débito). `rastreabilidade.sh` exit 0. Amostra no app: `/app/acme` serve o par `acme-qa(-dark).png` em `fi-logo-light`/`fi-logo-dark`; `/app/globex` serve `kit/logo-qa(-dark).png`. O resto vale pelo ciclo 2. |
| B | Fronteiras e dados | ✅ | Globex sem logo cai no par da instalação. CT-40 verde dentro dos 89. O código de comportamento não mudou. |
| C | Matriz de permissão | ✅ | `/admin` serve `kit/logo-qa(-dark).png`, sem a logo da Acme. CT-50 e CT-56 verdes. Nenhuma ação destrutiva. |
| D | Observabilidade | ✅ | Nenhum log novo no diff. `tenancy-2026-10-07.log` e `configuracoes-2026-10-07.log` só trazem ids e nomes de chave, sem PII. O único `ERROR` do `laravel.log` é de um comando artisan (`--columns`) anterior a esta sessão, fora do app. |
| E | Performance | ✅ | Código do ciclo 1: nenhum acesso a banco no diff e uma resolução por request (memo). |
| F | UX de erro | n/a | O diff não acrescenta mensagem nem estado de erro (prova do ciclo 1). |
| G | Tema e cor | ⏭️ parcial | `dark-mode.sh --mecanismo` exit 1 (Filament, classe `dark`). Nível 1 nos arquivos AM de `app`/`resources`: exit 0. Nível 2: `IdentidadeVisualTest` 9/9 (54), depois de `view:cache`. Nível 3 não rodou. |
| H | Acessibilidade | ⏭️ parcial | `alt` conferido no app (QA-08). axe e teclado não rodaram. |
| I | Segurança da superfície nova | ✅ | Os eixos 9 foram cobertos pelo `fw-revisor-diff` (11 RC, nenhum rejeitado). O `/code-review` não rodou (QA-14). Além do step 9: `/admin` sem dado da organização, CT-50 e CT-56 verdes, nenhuma rota nova. |
| J | Regressão adjacente | ⏭️ parcial | TIA proibido. Rodados agora:<br>• 89/89 (457);<br>• `IdentidadeVisualTest` 9/9 (54);<br>• docs 91/91 (343) e `Site`+`Rede` 88/88 (333);<br>• o resto da regressão do `01:10` e de `03:58` (`ArquiteturaDoCodigoTest`, `BloqueioDeSessaoTest`, `CabecalhoDoPainelTelaTest`, `CabecalhoDoPainelTest`, `HelpersDeTesteTest`, `IdentidadeVisualTenancyTest`): 100/100 (556).<br>Os browser `tests/Browser/LogoDarkModeTest.php` e `CabecalhoDoPainelTest.php` valem pelo ciclo 1 (5/5); o código de comportamento é o mesmo. |
| K | Adequação da suíte | ⏭️ | K1 `k1-oraculo-fraco.sh` exit 0. K2 proibido pelo orquestrador. Cabeçalho do `04`: revisão adversarial FEITA. |
| L | Consistência documental | ⚠️ | QA-12 (resto), QA-14, QA-15 e QA-16. Scripts:<br>• L1 `ids-ct.sh` (3 arquivos juntos): exit 1, só CT-01…CT-31 das ancestrais (`### Colisão de IDs`);<br>• L2 `citacoes.sh`: exit 0;<br>• L4 `conformidade-rules.sh {wiki} main`: exit 0;<br>• L6 `checkbox-sem-evidencia.sh`: exit 0.<br>Números reproduzidos: 89/457, 9/54, 68/254, 91/343, 26 arquivos, 12 PHP (`pint --test`: passed), "alterado em 2026-10-07" 5/1/0/0, 18/11/47/5/1. |

## Débitos Aceitos

- Nenhum aceito pelo solicitante. QA-08 e QA-09 estão em `03` (`## Débitos`, `## Blockers`), com confirmação pedida no PR.

## Suspeitas Não Confirmadas

- Nenhuma nova. O `warnings: 2` do `IdentidadeVisualTest` dos ciclos 1 e 2 não apareceu na saída `--compact` deste ciclo.

## Não Verificado

- **G, nível 3** (olhar o swap renderizado): Playwright MCP indisponível e nenhum navegador neste agente. O swap é provado pelo CT-B01 (9/9). O app foi conferido só no HTML.
- **H, axe e teclado**: nenhum CT-B tem `assertNoAccessibilityIssues()`, e o gate não escreve teste.
- **J, passo 1** (`pest --parallel --tia`) e **suíte contra a baseline de `main`** (`03:59`): proibidos pelo orquestrador.
- **K2** (`pest --mutate`) e **falsificabilidade** (`03:60`, `03:69`): proibidos pelo orquestrador. PCOV e Xdebug estão carregados, então a causa é a proibição e não a ferramenta. Os 6 mutantes manuais do `03` são anteriores ao refactor dos testes e não têm score nem `Duration`.
