# Relatório de QA — Débitos G, H, J e K da aparência das telas de acesso

> Requisito: `00-requisito.md` · Plano: `01-plano-acao.md`
> Perfil de esforço: padrão
> Natureza da wiki: débito de verificação (fecha débitos de `feat/aparencia-das-telas-de-acesso/tela-de-login-personalizavel`) · Toca infra compartilhada: sim → helper `visitaComMovimentoReduzido()` passa para `tests/Pest.php`; sobrescrita em `resources/views/vendor/filament-auth-designer/` · Regressão: sim
> Independência: sub-agente fw-qa-gate/claude-opus-5-5, contexto novo sem a conversa · comprometida (parcial) — o despacho trouxe um resumo curto do conteúdo do diff e a alegação "a suíte inteira já rodou (registrada no 03)". Os dois foram conferidos contra o `git` e o `03`. O despacho não trouxe justificativa de decisão nem raciocínio da sessão
> Cobertura: 10 de 12 dimensões verificadas ou provadas não aplicáveis · teto: APROVADO COM DÉBITO

## Veredito — Ciclo 1

**REPROVADO → especificação**

- Blocker: 0 · Major: 1 (QA-01) · Minor: 2 (QA-02, QA-03) · Cosmético: 2 (QA-04, QA-05)
- Não verificadas: G (nível 3), K (K2). As causas estão em *Não Verificado*. Independência parcial
- `RQ` abertas: nenhuma
- Ambiente: app em `http://127.0.0.1:8091` (sondado por `curl`) · Pest v5.3.7 / PHPUnit 13.4.7 · MCP: Playwright indisponível; Boost MCP não conectou (timeout)

## Achados

### QA-01 — Os cenários 1–4 do RQ-06 não rodaram sobre a v0.53.1, e sim sobre um commit anterior às correções dela · Major · destino 1

- **Dimensão**: L (L6) e J
- **Relacionado a**: RQ-06, P-01, passo 3 do PRD
- **Esperado**: a alegação "a rodada `f1`..`f4` de 2026-11-15 sobre o commit da v0.53.1" (`03-progresso.md:21`) deveria ser reproduzível. A P-01 (`00-requisito.md:51`) fecha o RQ-06 por referência a essa rodada. O próprio gate da origem fixou a condição de validade: "Se `app/`, `resources/` ou `public/` mudarem […], a rodada precisa ser refeita" (`origem/06-relatorio-qa.md:14`).
- **Observado**: o registro da origem mostra a rodada sobre `e267f909` (`origem/03-progresso.md:244`), e não sobre a v0.53.1 (`6721e912`). Entre os dois, 7 commits mudaram 8 arquivos de `app/` e `resources/`. Entre eles estão `painel-de-marca.blade.php`, `AparenciaDasTelasDeAcesso.php` e `VesteAparenciaDasTelas.php`: as correções da transição e das organizações (bc6c20ab, 25909224, 58c524c6, f7541fc5). A frase "nenhuma mudança de `app/`/`resources/` depois dela" (`origem/03:321`) ficou falsa quando a v0.53.1 saiu. Nenhuma rodada dos quatro cenários existe depois de `e267f909` em `wikis/specs/`. Esta entrega acrescenta um arquivo novo que o `kit:update` distribui (`resources/views/vendor/filament-auth-designer/…/theme-toggle.blade.php`), e isso é justamente o que os cenários 3 e 4 medem (`wikis/checklist-de-release.md:23`).
- **Repro**:
  1. `git merge-base --is-ancestor e267f909 v0.53.1 && echo ancestral` → `ancestral`
  2. `git diff --stat e267f909 v0.53.1 -- app resources public` → `8 files changed, 126 insertions(+), 90 deletions(-)`
  3. `git log --oneline e267f909..v0.53.1` → 10 commits, 4 deles `fix(auth)`/`refactor(auth)`
- **Evidência**: `grep -rn "v0.53.1" wikis/specs/feat/debitos-da-aparencia-das-telas/` encontra a alegação só em `03-progresso.md:21`. A P-01 (`00:51`) repete a premissa "contêm a feature".
- **Destino**: 1 especificação
- **Ação exigida**: corrigir `03:21` com o commit real (`e267f909`) e revisar a P-01 no `00`. Depois, ou rodar os quatro cenários sobre o commit desta entrega (o `checklist-de-release.md` já exige isso para a tag 0.55.1, e a rodada fecha o RQ-06 de verdade), ou registrar como débito explícito que o código das correções da v0.53.1 e a sobrescrita nova nunca passaram pelos cenários. Atualizar também `origem/03:321`, se a decisão mudar o estado do QA-28.

### QA-02 — O `04` desta wiki tem um comentário HTML não fechado que esconde o CT-114, a Regra R4 e o Índice de Cenários · Minor · destino 1

- **Dimensão**: L (L1 e L3)
- **Relacionado a**: P-02, CT-114, R4
- **Esperado**: o cenário CT-114, o título `## Regra R4` e o `## Índice de Cenários` deveriam aparecer no documento renderizado. A contagem do cabeçalho deveria sair dos `grep -c` de `template-04.md` §*Contagem do cabeçalho*, e não de contagem à mão.
- **Observado**: `04-casos-de-teste.md:16` abre `<!-- contado à mão: … 5 linhas em ## Regra R4 — a sobrescrita acompanha o pacote`, e o comentário só fecha em `:38` (`## Mutantes Previstos -->`). O título da Regra R4 foi engolido pela linha do comentário. O Gherkin do CT-114 e o Índice ficam invisíveis para o leitor, e `## Mutantes Previstos` aparece duas vezes (`:38` e `:107`). O cabeçalho (`:15`) diz `Regras: 4`, e o grep dá 3.
- **Repro**:
  1. `grep -n '<!--\|-->' 04-casos-de-teste.md` → `16:<!--`, `38:## Mutantes Previstos -->`
  2. `grep -cE '^## Regra R[0-9]+' 04-casos-de-teste.md` → `3`
- **Evidência**: as duas saídas acima. O `ids-ct.sh` não pega o problema, porque lê o texto cru, com o comentário.
- **Destino**: 1
- **Ação exigida**: fechar o comentário de contagem na própria linha, restaurar `## Regra R4 — …` como título, tirar o `## Mutantes Previstos` duplicado e recalcular o cabeçalho pelos quatro `grep -cE`.

### QA-03 — O `03` desta wiki tem afirmações anteriores ao step 9 que o step 9 já invalidou · Minor · destino 1

- **Dimensão**: L (L3)
- **Relacionado a**: RQ-02, CT-B21, mutante H1
- **Esperado**: o `## Débitos` e a tabela de mutantes deveriam refletir o estado final, como fazem os passos 1 e 2 do mesmo arquivo.
- **Observado**: `03:54` mantém o débito "CT-B21 no cadastro roda sem a arte e o alternador, pelo vazamento…". Os itens `03:9` e `04:76-79` e o teste (`assertPresent('.fi-auth-theme-switcher-wrapper[role="menu"]')` em todas as linhas) dizem que isso foi corrigido. A tabela do passo 4 (`03:34`) diz que o H1 "cai (8 de 12)", e `03:14` diz "12 de 12 depois do step 9".
- **Repro**: 1. `sed -n '9p;14p;34p;54p' 03-progresso.md`. 2. `vendor/bin/pest tests/Browser/AcessibilidadeDasTelasDeAcessoTest.php` → 14/14, com as 4 linhas do cadastro exigindo o alternador.
- **Evidência**: execução do gate: `{"tests":14,"passed":14}`.
- **Destino**: 1
- **Ação exigida**: tirar o terceiro débito, ou reescrevê-lo como o resíduo que ainda vale: a arte no cadastro, que o CT-B21 não afirma. Alinhar a linha do H1 no passo 4 com "12 de 12".

### QA-04 — O cabeçalho do `04` da origem diz 113 cenários, e há 114 · Cosmético · destino 1

- **Dimensão**: L (L1)
- **Relacionado a**: CT-114 (acrescentado ao `04` da origem, `:2376`)
- **Esperado / Observado**: `origem/04-casos-de-teste.md:5` diz `Cenários: 113`. O `grep -cE '^[[:space:]]*(Cenário|Esquema do Cenário): \[CT-[0-9]+\]'` dá **114**.
- **Repro**: o comando acima, na pasta da origem.
- **Evidência**: `grep -rn "113 cenários\|Cenários: 113" wikis/specs/feat/` → `origem/04-casos-de-teste.md:5` e `debitos/03-progresso.md:42`
- **Destino**: 1
- **Ação exigida**: recalcular pelo `grep -c` e corrigir os **dois** arquivos.

### QA-05 — O `04` declara o perfil "mínimo" para dispensar a revisão adversarial, mas a tabela de perfil dele tem uma área "padrão" · Cosmético · destino 1

- **Dimensão**: L (L3)
- **Observado**: `04:5` diz "não exigida pelo perfil (mínimo)", e `04:11` classifica a acessibilidade como `padrão` (P×I 4). Nenhum dos dois perfis exige revisão adversarial, então a conclusão se mantém. O motivo citado é que está errado.
- **Repro**: `sed -n '5p;11p' 04-casos-de-teste.md`
- **Destino**: 1
- **Ação exigida**: trocar o motivo para "perfil padrão, sem Impacto 3".

## Matriz de Rastreabilidade

| RQ/P | Cláusula ou premissa | Passo PRD | CT | CT-B | Código | Resultado | Veredito |
|------|----------------------|-----------|----|------|--------|-----------|----------|
| RQ-06 | cenários 1–4 sobre commit com a feature | 3 | — (processo) | — | — | rodada sobre `e267f909`, anterior às correções da v0.53.1 | ⚠️ QA-01 |
| P-01 | débito pago por evidência posterior | 3 | — (processo) | — | — | a premissa cita "v0.53.1", que a evidência não confirma | ⚠️ QA-01 |

As demais linhas não têm lacuna. RQ-01..RQ-04 e P-02 vão para CT-B21..CT-B23 e CT-114, verdes na execução do gate. RQ-05, RQ-07, RQ-08, P-03 e P-04 são processo, com a evidência no `03`.

## Dimensões

| # | Dimensão | Status | Observação |
|---|----------|--------|------------|
| A | Cobertura do requisito | ⚠️ | `rastreabilidade.sh` exit 1, com 7 linhas (RQ-05..08, P-01, P-03, P-04 "sem CT"), rejeitadas: são cláusulas de execução, com o comando e a saída no `03`. Sem `07-tickets/`. Diff por passo: passo 2 com os 3 arquivos no diff; passo 1 com o teste temporário fora da árvore; passo 6 com `03`/`04`/`05` da origem no diff. Achado: QA-01 |
| B | Fronteiras e dados | ✅ | sem entrada nova. O diff não toca `app/`. O `$position` da sobrescrita vem de `->themeToggle()` nos providers, não do usuário |
| C | Matriz de permissão | ✅ | nenhuma rota, policy ou ação nova. As telas tocadas são públicas (guest) |
| D | Observabilidade | ✅ | o PRD declara "Logs: nenhum" nos 6 passos, e o diff não tem código PHP de aplicação. Não há ponto de log a conferir |
| E | Performance | ✅ | a sobrescrita é Blade estática, sem query. Nada cresce com N |
| F | UX de erro | ✅ | nenhum caminho de erro ou mensagem nova |
| G | Tema e cor | ⏭️ não verificada | fora do perfil padrão. Mesmo assim rodou: `dark-mode.sh --mecanismo` exit 1 (Filament, classe `dark`); nível 1 `dark-mode.sh {AM}` exit 0; nível 2: CT-B21, linhas "escuro", com axe e contraste, 6/6 verdes na execução do gate. O nível 3 (olhar a tela) não foi feito pelo gate: ver *Não Verificado* |
| H | Acessibilidade | ✅ | executada além do perfil. `AcessibilidadeDasTelasDeAcessoTest` 14/14; `AparenciaDasTelasDeAcessoTest` (Browser) com CT-B03, CT-B13, CT-B19 e CT-B20: 8/8. O app servido tem `role="menu"` no wrapper em `/admin/login` e em `/admin/password-reset/request`, sem `aria-label` duplicado. O alternador fora do teclado (`tabindex="-1"` do Filament) é débito do vendor já registrado na origem |
| I | Segurança da superfície nova | ✅ | coberto pelo step 9 (`## Revisão do Diff (step 9)` do `03`): 11 achados, 1 refutado por medição e 2 mantidos com motivo. Coberto além disso: nenhuma rota nova, nenhum model tocado, nenhum upload, `{!! $styles !!}` vindo de código. O `02` declara a Superfície Livewire sem propriedade pública |
| J | Regressão adjacente | ⚠️ | a suíte (sem Browser) rodou sobre `276cdc21` e a Browser sobre `c530039c`, os dois antes do `a50089aa`, que só tocou a sobrescrita (`git diff 276cdc21 HEAD --stat -- app resources public tests/Pest.php`: 1 arquivo). RCRCRC por amostragem, no gate: `TelasDoKitTest` com o filtro das telas públicas e do 2FA, 2/2; `BrowserTenancy/IdentidadeVisualTest` 9/9; `KitUpdateTest` CT-06/CT-07 4/4; `RoteiroDeAtualizacaoTest` 212/212. Com `--exclude-testsuite`, o `--tia` rodou tudo, sem seleção por impacto (declarado no `03`). Achado: QA-01 |
| K | Adequação da suíte | ⏭️ não verificada | K1 `k1-oraculo-fraco.sh` exit 1, com 2 linhas (CT-31 `:724` e CT-75 `:376`) rejeitadas: são testes antigos, fora das linhas que o diff acrescentou. K2 está fora do perfil padrão. A amostragem manual do RQ-07 (M134..M145, H1) não foi reproduzida pelo gate. M136 é sobrevivente triado como equivalente: plausível pela leitura (`VesteAparenciaDasTelas::aparenciaDaTela():58` aplica `transicaoDaInstalacao()` em todo render, e no preview também), mas a equivalência é argumentada, não medida. Cabeçalho do `04`: "Revisão adversarial: não exigida" (não é `NÃO FEITA`) |
| L | Consistência documental | ⚠️ | 5 achados (QA-01..QA-05). L1 `ids-ct.sh` desta wiki com o arquivo Browser: exit 1 (CT-114 mora no arquivo Kit, como declarado). Com o arquivo Kit junto, acusa os CT da origem, como o `03` explica. Na origem, com `'tests/**/AparenciaDasTelas*.php' 'tests/**/AcessibilidadeDasTelasDeAcesso*.php'`: exit 0, reproduz o `03`. L2 `citacoes.sh` exit 0. L4 `conformidade-rules.sh {wiki} main` exit 0; a exceção de seletor por classe em `testes-browser.md` foi declarada e justificada (o que se afirma é o papel ARIA de um wrapper sem texto nem rótulo), e não é achado. L5: CHANGELOG, docs pt/en e roteiro coerentes entre si, e a sobrescrita está em `CAMINHOS_DO_KIT`. L6 `checkbox-sem-evidencia.sh` exit 0; números reproduzidos: CT-110..113 11/11, Browser 14/14, `SiteDeDocumentacaoTest` 68/68, CT-114 1/1, 265 arquivos de teste, 95 features; não reproduzidos: 113 cenários (QA-04) e "commit da v0.53.1" (QA-01). L7: glossário coerente ("Painel de marca", "Transição entre as telas", "Telas de acesso"). L8 n/a: sem `08`, `roteiro-manual.sh` exit 0, e o `03` não registra decisão de roteiro manual |

## Débitos Aceitos

- Nenhum neste ciclo: com o QA-01 aberto o veredito é REPROVADO. QA-02..QA-05 entram como débito se não forem corrigidos junto com ele.

## Suspeitas Não Confirmadas

- A execução de `AcessibilidadeDasTelasDeAcessoTest` reporta `warnings: 2`, sem detalhe na saída `--agent`. A natureza deles não foi identificada em 1 tentativa.
- O CT-B21 não afirma a presença da arte (`fi-auth-media`) no cadastro. O teste só garante o alternador. Pela leitura do aquecimento por tela, a arte deve estar presente, mas não foi observada no navegador.

## Não Verificado

- G nível 3 (captura e leitura no tema escuro): Playwright MCP indisponível. O gate não tem `Write` para um teste temporário de captura, e gravar capturas em `tests/Browser/Screenshots` alteraria a árvore. As seis capturas da sessão foram apagadas, e a única evidência é a leitura registrada no `03:8`.
- K2: fora do perfil padrão. A amostragem manual do RQ-07 não foi reproduzida, porque exigiria editar arquivos de aplicação. A equivalência do M136 é argumentada, não medida.
- J: a suíte inteira, sem Browser (6206 testes), e a Browser (275 testes, 556 s) não foram reexecutadas pelo gate, por causa da restrição de memória e de tempo. A regressão foi conferida por amostragem.
- Independência: parcial. O despacho trouxe um resumo do conteúdo do diff e a alegação de que a suíte já rodou.
- Boost MCP (`browser-logs`, `database-*`): não conectou (timeout de 30 s). O console foi coberto pelos CT-B verdes, sem leitura de log do navegador.
