# Relatório de QA — mapa-interativo-do-kit: Mapa interativo do kit com a skill archify

> Requisito: `00-requisito.md` · Plano: `01-plano-acao.md`
> Perfil de esforço: completo (superfície de UI com JS: diagramas do archify)
> Natureza da wiki: evolução · Toca infra compartilhada: sim → build do site, conferidores do site, página do catálogo, `.ai/rules/index.md`, contadores do README · Regressão: sim
> Independência: sub-agente fw-qa-gate/claude-opus-5-5, contexto novo sem a conversa
> Cobertura: 9 de 12 dimensões verificadas ou provadas não aplicáveis · teto: APROVADO COM DÉBITO

*(O ciclo 1 foi feito no HEAD `a624bff1`; o texto inteiro dele está no `06` do commit `c642c23c`. O ciclo 2 foi feito no HEAD `b3681538`; o texto inteiro dele está no `06` do commit `2a4df758`. Este é o ciclo 3, no HEAD `3995de51`, o último pela regra de convergência. Gravado pela sessão em 2026-11-25, a partir do texto que o `fw-qa-gate` devolveu no despacho #72; o que a sessão fez com cada achado está no `03`, seção `## Quality Gate`.)*

## Veredito — Ciclo 3

**APROVADO COM DÉBITO**

- Blocker: 0 · Major: 0 · Minor: 2 · Cosmético: 0
- Não verificadas: G (nível 3), J (TIA, as suítes inteiras e as ancestrais por ID), K (K2, e a revisão adversarial das rodadas do ciclo 2). As causas estão em *Não Verificado*
- `RQ` abertas: RQ-09 (Q6), sem implementação, conta no teto. Q12 continua aberta, com a P-11 valendo até a resposta
- Ambiente: prévia `http://localhost:4321` construída no `3995de51`; Pest 5. O `laravel-boost` não conectou, e o Playwright MCP não está exposto; usei o Playwright do `site/` via `node`
- Convergência: o ciclo trouxe um achado novo (QA-16), e o teto de 3 ciclos chegou. Nenhum achado é Blocker nem Major, e por isso o veredito fecha com débito. A pergunta do QA-16 vai ao mantenedor; se ele responder "sim", o trabalho passa do teto de ciclos, e quem decide é o usuário

## Achados

### QA-16 — A página única de login (`TelaLoginUnificada`, F-73) fecha por chave no `mount()`, desligada de fábrica, e o mapa a desenha sem marca; a varredura que a deixou de fora é declarada falsa · Minor · destino 1

- **Dimensão**: A (auditoria do requisito) e L3
- **Relacionado a**: RQ-27, RQ-28, RQ-31, RQ-33, P-16 · Setup Global do `04` ("O que fica fora da montagem") · linha QA-10 de `## Quality Gate` do `03`
- **Esperado**: pelo `04:539`, "a varredura da sessão (`mount()`/`boot` que redireciona ou aborta por chave do kit, em `app/Filament` e `app/Livewire`) só achou os três `Dashboard`"; o `03:241` repete o resultado. Pela RQ-28, nenhum diagrama mostra como sempre disponível uma peça que de fábrica não abre.
- **Observado**:
  - `app/Filament/Pages/Auth/TelaLoginUnificada.php:mount:37` redireciona para o login do painel padrão quando `ConfiguracaoDoLogin::unificado()` é falso. O `unificado()` lê `kit.login.unificado`, com padrão `false` (`config/kit.php:'unificado':932`, `KIT_LOGIN_UNIFICADO=false` no `.env.example:421`). `CadastroUnificado::mount` e `TelaRecuperarSenhaUnificada::mount` fazem o mesmo, e as duas também estão em `app/Filament`.
  - A suíte do kit prova o redirect: `tests/Kit/LoginUnificadoTest.php`, caso `[CT-06]` ("/login com a chave desligada leva ao login do painel default").
  - No `pecas.json`, a `TelaLoginUnificada` tem `paineis: []`, `registrada: ["sem","com"]` e nenhum `disponibilidade`; a prova dela é a rota do `KitServiceProvider`. Ela é desenhada sem marca no `kit-acesso-e-autenticacao` e nos dois `kit-resumo`, em pt e em en.
  - Ela fica fora das três populações do discriminante: não é registrada pelo painel; não tem `canAccess()` nem `canView()` estático (o `SimplePage` não declara nenhum); não é componente flutuante. Também não cabe na definição da página do mapa ("registrada pelo painel, usada dentro de outra tela ou flutuante", `docs/pt/referencia/mapa-interativo.md:17` e o equivalente en). Nenhuma `RQ` entregue está violada; falta a decisão: o requisito não diz o que fazer com a página que o kit registra fora do painel e que fecha por chave.
- **Por que Minor, e não Major**: nenhuma cláusula entregue é contrariada — o texto da RQ-27, da RQ-31 e da RQ-33 não alcança a peça, e as docs são coerentes com esse recorte. A afirmação falsa está numa nota de lacuna do `04` e no `03`, não no PRD nem na ADR, e não muda oráculo de CT. É a forma da QA-13 do ciclo 2: interpretação sem pergunta registrada. O mantenedor respondeu às análogas nos dois sentidos: "falha fechado" na Q28, Q30 e Q32, e "sem marca" na Q29.
- **Repro**: `grep -rln "function mount" app/Filament app/Livewire | xargs grep -ln "redirect\|abort("` lista a `TelaLoginUnificada`, o `CadastroUnificado` e a `TelaRecuperarSenhaUnificada`, além dos três `Dashboard`; `sed -n 35,41p app/Filament/Pages/Auth/TelaLoginUnificada.php` mostra o redirect; `curl -s http://localhost:4321/mapa/{pt,en}/kit-acesso-e-autenticacao.html | grep -c KIT_LOGIN_UNIFICADO` dá 0 nos dois idiomas, com a peça 8 vezes; nos dois `kit-resumo`, 0, com a peça 1 vez.
- **Evidência**: as saídas acima; o `[CT-06]` está verde na suíte do kit.
- **Destino**: 1
- **Ação exigida**: (a) levar ao mantenedor uma Q nova no `00`: "a página que o kit registra fora do painel e que fecha por chave no `mount()` (a página única de login, F-73, `KIT_LOGIN_UNIFICADO=false`) leva a marca?"; com "sim", segue a ordem P-nn/RQ → CT que falha → modelo, com a página na população do discriminante. (b) Corrigir o `04:539` e o `03:241` com o resultado real da varredura e o motivo de cada exclusão.

### QA-17 — A linha da `.ai/rules/kit.md` em `## Conformidade com Rules` dá "n.a." porque "as guardas do mapa não montam componente Livewire", e desde o QA-10 elas montam · Minor · destino 1

- **Dimensão**: L (L4)
- **Observado**: o discriminante agora faz `Livewire::test($classe)->assertStatus(200)->assertNoRedirect()` (o helper em `tests/Pest.php`, chamado pelos `tests/Kit/MapaInterativo*Test.php`). O veredito "n.a." continua certo — a rule trata do oráculo que depende de `Referer` ou query dentro de uma ação, e a montagem não chama ação —, mas a evidência escrita é falsa.
- **Repro**: `git diff b3681538..HEAD -- tests/Pest.php | grep "Livewire::test"`; a linha da `kit.md` em `## Conformidade com Rules` do `03`.
- **Ação exigida**: trocar a evidência, por exemplo: "a guarda só monta a página, sem `->call()`/`->set()`, e não lê cabeçalho nem query".

## Ciclo 2 — situação

| Achado | Situação no ciclo 3 | Como foi conferido |
|---|---|---|
| QA-10 | fechado | 22 peças com `disponibilidade` no `pecas.json`; as quatro novas (os três `Dashboard` e o `DynamicDashboard`) com `KIT_DASHBOARD_DINAMICO`, `padrao: false`, e a âncora do `mount()`. Os 14 diagramas citados no `05` trazem a marca na prévia, nos dois idiomas (`admin-registro-sem`: 5 ocorrências, contra 0 no ciclo 2). O `04` corrigido, com a linha de controle invertida (`04:2458`) e as linhas 551 e 2418 marcadas. CT-32 23/23 (1.074 asserções), `MapaInterativoTest.php` 135/135 (1.503). Na ordem dos commits, o teste vem antes do modelo (`6a998aaa` → `fd9b2ad2`). A classe do achado reabre em outro ponto: QA-16 |
| QA-11 | fechado | remedidos os 95 de cada idioma pelo mesmo método: abaixo de 6,5 px em pt, `marcos-do-changelog` 6,31, `admin-produtividade-e-ui` 6,40, `app-produtividade-e-ui-com` 6,40 e `kit-ia` 6,42 (91 no piso); em en, `marcos-do-changelog` 6,31, `infra-infraestrutura-2` 6,40 e `kit-ia` 6,42 (92 no piso). Batem com o `02:376` e o `03:13`; a página declara os quatro, em pt e en |
| QA-12 | fechado | P-13 a P-17 na Cobertura do `01`; o `04:3` diz "P-01 a P-17", com a Q32 e a Q33; `rastreabilidade.sh` exit 0 |
| QA-13 | fechado | Q33 no `00`, respondida em 2026-11-25; a P-17 "confirmada pelo mantenedor" |
| QA-14 | fechado | o glossário com as duas entradas, e o "não confundir com" da P-16 |
| QA-15 | fechado | o `03:3` e o `03:499` corrigidos, com a marca; os 190 HTML (95 × 2) conferem com o `dist/mapa/{pt,en}` |

Do ciclo 1, vale a tabela do ciclo 2: o QA-05 é débito aceito, e os demais estão fechados.

## Matriz de Rastreabilidade

| RQ/P | Cláusula | Passo PRD | CT | CT-B | Código | Resultado | Veredito |
|------|----------|-----------|----|------|--------|-----------|----------|
| RQ-27, RQ-28 | a peça condicional tem marca e entra só no seu modo | 2, 4, 6, 8 | CT-32, 33, 56, 57 | CT-B01 p.4 | `pecas.json` (22 peças) | as 22 marcadas; a página única de login, fora do texto, sem marca | ⚠️ QA-16 (pergunta) |
| RQ-09 | fluxos | 3, 4 | CT-24, 39 | CT-B01 | — | sem lista fechada | ⏳ aberta (Q6), teto |
| demais | — | — | — | — | — | `rastreabilidade.sh` exit 0 | OK |

## Dimensões

| # | Dimensão | Status | Observação |
|---|----------|--------|------------|
| A | Cobertura do requisito | ⚠️ | QA-16. `rastreabilidade.sh` exit 0; sem `07-tickets/`. Nenhum arquivo em `app/`, `routes/` ou `config/` no diff desde o `b3681538` |
| B | Fronteiras e dados | ✅ | o ciclo não acrescenta entrada nova às ferramentas; o consumidor do CT-33 ignora o painel que não vota e reprova quando nenhum vota — falha fechado (lido no diff) |
| C | Matriz de permissão | n/a | site estático; nenhum arquivo de `app/`, `routes/` ou `config/` no diff |
| D | Observabilidade | ✅ | sem log de aplicação; nenhuma mensagem `[mapa]` nova no ciclo |
| E | Performance | ✅ | 190 HTML; o conferidor completo sem erro |
| F | UX de erro | ✅ | nada novo; as recusas `[mapa]` do ciclo 2 iguais |
| G | Tema e cor | ⏭️ | `dark-mode.sh --mecanismo` exit 1; nível 1 (`dark-mode.sh` sobre o diff) exit 0; nível 2, CT-B05, 42 conferências e 0 reprovação; nível 3 não verificado |
| H | Acessibilidade | ✅ | `verifica-mapa.mjs` exit 0: 42 HTML, 11 tipos, nenhuma violação serious/critical fora das duas toleradas (nested-interactive 40×, color-contrast 12×) |
| I | Segurança | ✅ | coberto pelo step 9 (48 linhas, 5 rejeitadas); além dele, nenhuma rota e nenhum código de aplicação no diff |
| J | Regressão adjacente | ⏭️ | `MapaInterativoTest.php` (135/135) e o CT-32 reproduzidos; lidos os dois testes de fora da feature que o ciclo tocou (`KitArteTest`, `LinkDoPainelSemTenancyTest`): só citações de linha do `tests/Pest.php`. TIA, suítes inteiras e ancestrais por ID não rodados pelo QA |
| K | Adequação da suíte | ⏭️ | K1 `k1-oraculo-fraco.sh` exit 0; o diff dos oráculos lido: a fixture do CT-47 troca o `Dashboard` pelo `DashboardClassico`, e o `04` registra a troca. K2 não verificado. A revisão adversarial não cobriu as rodadas do QA-10 nem da Q32 (`04:20`–`22`, "Sem revisão adversarial nesta rodada") |
| L | Consistência documental | ⚠️ | QA-16 (parte L3), QA-17. L1: `ids-ct.sh` exit 1 com 8 linhas (CT-B01 a CT-B08), rejeitadas pela D17; o cabeçalho 57 · 44 · 356 · 12 confere pelos `grep -c` do template. L2: `citacoes.sh` exit 0. L4: `conformidade-rules.sh` exit 0. L6: `checkbox-sem-evidencia.sh` exit 0; reproduzidos: badge 2.779 (blocos `it()`/`test()` de `tests/`), 135/135, 91/92 no piso, 190 HTML, 22 condicionais; no CT-B01, 212 conferências de condição e 216 de card, 30 a mais que no ciclo 2 (os 15 lugares por idioma do `05`). L7: as entradas novas batem com a P-16. L8: n/a, sem `08` (`roteiro-manual.sh` exit 0) |

## Débitos Aceitos

- QA-05 (ciclo 1): piso de leitura fora do CI, aceito pela sessão.
- QA-16 e QA-17 são débito candidato; o QA-16 depende da resposta do mantenedor.

## Suspeitas Não Confirmadas

- Foco visível nos nós `<g role="button">` (dos ciclos 1 e 2): continua sem olhar a tela.
- `EntrarNoPainelController`, no modelo e sem marca: a rota é do mesmo grupo da página única; não conferido se fecha com a chave desligada.

## Não Verificado

- **G, nível 3**: o Playwright MCP não está exposto.
- **J**: TIA, suítes `Kit` e `Tenancy` inteiras e CT das ancestrais por ID — a sessão roda as suítes, e o limite é de 3 processos pest. Na `## Verificação Final` do `03`, dois `[ ]` abertos: as suítes contra a baseline e o build no contêiner Linux.
- **K2**: o código de produção é JS (`site/mapa/*.mjs`) mais o modelo JSON; o `pest-plugin-mutate` só muta PHP, e o projeto não tem mutação JS.
- **K, revisão adversarial**: não feita nas rodadas dos Adendos 2 a 4, do QA-10 e da Q32 (`04:14`–`22`).
- **MCP `laravel-boost`**: não conectou.
