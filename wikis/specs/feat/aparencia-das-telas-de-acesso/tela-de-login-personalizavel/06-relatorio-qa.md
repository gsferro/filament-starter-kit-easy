# Relatório de QA — feat/aparencia-das-telas-de-acesso: Tela de login personalizável (Adendo 1)

> Requisito: `00-requisito.md` · Plano: `01-plano-acao.md`
> Perfil de esforço: completo. A UI tem JS (rede animada com Alpine, iframe de preview, upload com `live`) e o domínio é sensível (arquivo enviado lido numa tela pública, caminho restrito, saída pública do `$wire`)
> Natureza da wiki: evolução · Toca infra compartilhada: sim → todas as telas de autenticação dos três painéis · Regressão: sim
> Independência: sub-agente fw-qa-gate/claude-opus-5-5, contexto novo sem a conversa
> Cobertura: 9 de 12 dimensões verificadas ou provadas não aplicáveis · teto: APROVADO COM DÉBITO

## Veredito — Ciclo 1 do Adendo 1

**REPROVADO → especificação (QA-19, QA-20), com implementação (QA-18)**

- Blocker: 0 · Major: 3 · Minor: 6 · Cosmético: 1
- Não verificadas: G, H, K. As causas estão em *Não Verificado*
- `RQ` abertas: nenhuma. Q1 continua `aberta`, mas o código implementa só a premissa estreita P-01, então não gera achado. A confirmação de Q2 segue pendente. As duas estão nos Débitos do `03` e não foram contestadas
- Ambiente: app em `http://127.0.0.1:8123`, HEAD `a1a50f6f`. A aparência global é `painel`, 50 %, destaque `logo`, rede em movimento, `logo_no_painel` falso. Pest 5.0.5, sem a opção `--agent`. Playwright MCP indisponível. Boost MCP indisponível (`laravel-boost` CONNECT_TIMEOUT)
- O loop recomeça no ciclo 1 porque o Adendo 1 trouxe pedido novo (RQ-17..RQ-24). Os QA-15..17 da entrega original estão tratados no `03` e não foram reabertos

## Achados

### QA-18 — Os cenários 1–4 não rodaram sobre o código do adendo · Major · destino 2

- **Dimensão**: A / J
- **Relacionado a**: RQ-07, RQ-08, RQ-17, P-23, passo 9 e passo 10 do PRD
- **Esperado**: RQ-17 pede o merge "+ testes dos 4 cenarios". RQ-08 pede os cenários ao finalizar. O código distribuído pelo `kit:update` é o do commit final.
- **Observado**: a última rodada dos cenários foi sobre `d343fc56`, antes do adendo. Desde então o adendo mudou 9 arquivos em `app`, `resources` e `public` (869 inserções), todos distribuídos às instalações. O item do `03` "Quality gate do adendo, CI do PR, merge, tag e cenários 1–4 (RQ-17)" continua `[ ]`. P-23, a aparência gravada antes que precisa continuar igual, não foi provada numa instalação atualizada.
- **Repro**:
  1. `git diff d343fc56..a1a50f6f --stat -- app resources public` → "9 files changed, 869 insertions(+), 115 deletions(-)"
  2. `grep -n "Cenários 1 a 4" 03-progresso.md` → só as rodadas sobre `b28debff` e `d343fc56`
- **Evidência**: as duas saídas acima. O `03` tem o item aberto na seção 10.
- **Destino**: 2
- **Ação exigida**: rodar os 4 cenários do checklist sobre o commit final do adendo, com uma instalação existente com aparência `painel` gravada antes do adendo (P-23). Registrar a saída na Verificação Final antes do merge.

### QA-19 — PRD e ADR ainda descrevem o painel de antes do adendo, sem marca de alteração · Major · destino 1

- **Dimensão**: L3
- **Relacionado a**: RQ-20, RQ-23, RQ-24, P-19, P-20, P-22, passos 1 e 3 do PRD, ADR-03
- **Esperado**: pela `feature-wiki` (Adendo, item 2), o passo antigo que muda por causa do adendo recebe a marca *(alterado em {data}: adendo N)*. A L3 não aceita afirmação que o código contradiz.
- **Observado**: quatro trechos afirmam o que o código não faz mais, e nenhum tem marca:
  - `01`, passo 1 (`aplicar`): "`painel` reconstrói com `mediaSize 50%`". O código usa `mediaSize: self::tamanho(...)`, de 30 a 70 (`AparenciaDasTelasDeAcesso::aplicar`).
  - `01`, passo 3: "abaixo de 1024px o painel vira faixa de até 112px acima do formulário (logo + frase; o apoio some)". Pela P-22, o CSS agora esconde meio, base e rede no celular, e a frase também some.
  - `01`, Decisões de Desenho: "**sem upload novo no editor**: não há rascunho de arquivo a limpar". O adendo acrescentou `FileUpload::make('imagem_do_destaque')` em `EditorDeAparenciaDasTelas::campos` e a `imagem_temporaria` do rascunho.
  - `02`, ADR-03: "modo lateral do próprio pacote (`media-left`/`media-right`, `mediaSize 50%`)".
- **Repro**: `grep -rn "50%\|logo + frase\|sem upload novo" wikis/specs/feat/aparencia-das-telas-de-acesso/tela-de-login-personalizavel/` → passo 1 e passo 3 do `01`, as Decisões de Desenho do `01` e a ADR-03 do `02`.
- **Evidência**: a saída do grep acima e `git diff cbb65fb6..a1a50f6f -- resources/css/filament/kit.css app/Support/AparenciaDasTelasDeAcesso.php`, que remove a frase com `line-clamp` do celular e troca o `'50%'` fixo.
- **Destino**: 1
- **Ação exigida**: marcar e reescrever os quatro trechos, no `01` (passo 1, passo 3, Decisões de Desenho) e no `02` (ADR-03, Decisão). Conferir com o mesmo grep que não resta outra cópia.

### QA-20 — Os números de testes do adendo no `03` não se reproduzem · Major · destino 1

- **Dimensão**: L6
- **Relacionado a**: `03` › seção 10, item "Testes"; `03` › Verificação Final, linha "Adendo 1, sobre `a1a50f6f`" (não commitada)
- **Esperado**: todo número de `[x]` é reproduzível pelo comando, no HEAD.
- **Observado**:
  - O `03` diz "`AparenciaDasTelasAdendoTest` 53". Medido: 57.
  - O `03` diz "feature Kit+Entrega+Tenancy 211". Medido: 133 + 5 + 74 = 212.
  - O `03` diz "Browser da feature 46". Medido: 50.
  - A linha nova da Verificação Final diz "`--testsuite=Browser` 230 (193 passaram, 37 pulados, 0 falhas)". Duas execuções minhas no HEAD deram 230 testes com 191 passaram e 2 falhas, depois 192 passaram e 1 falha. As falhas foram `DockDeAtalhosTest` CT-B02 nas duas (flake já registrado no ciclo 3) e o CT-B09 da feature na primeira.
  - Reproduziram: Kit 5110 (5107 passaram, 3 pulados) e Tenancy 743/743.
- **Repro**:
  1. `vendor/bin/pest tests/Kit/AparenciaDasTelasAdendoTest.php --compact` → `"tests":57`
  2. Os três arquivos de feature (`tests/Kit/AparenciaDasTelasDeAcessoTest.php`, `tests/Kit/AparenciaDasTelasDeAcessoEntregaTest.php` e `tests/Tenancy/AparenciaDasTelasDeAcessoTenancyTest.php`) → 133, 5 e 74
  3. `vendor/bin/pest tests/Browser/AparenciaDasTelasDeAcessoTest.php --compact` → `"tests":50`
  4. `vendor/bin/pest --testsuite=Browser --compact`, duas vezes → `failed: 2`, depois `failed: 1`
- **Evidência**: `grep -rn "53, feature\|211\|da feature 46\|193 passaram" wikis/specs/feat/aparencia-das-telas-de-acesso/tela-de-login-personalizavel/` → só o `03`: o item "Testes" da seção 10 e a linha nova da Verificação Final. Fora isso, há o "211/211" histórico no `06` do ciclo 3, que este relatório substitui.
- **Destino**: 1
- **Ação exigida**: trocar os três números do item "Testes" pela saída real. Na linha da suíte `Browser`, registrar as execuções com as falhas intermitentes e a classificação delas (ver QA-21), em vez de "0 falhas".

### QA-21 — CT-B05 e CT-B09 falham de forma intermitente · Minor · destino 3

- **Dimensão**: J / K
- **Relacionado a**: RQ-24, P-22, P-08
- **Observado**: em 4 execuções do arquivo de navegador da feature, 2 tiveram uma falha:
  - Arquivo isolado: CT-B05 "recuperar-senha em computador" falhou com "o navegador não chegou a: … `innerWidth === 1440` … `RASCUNHORECXD`".
  - Suíte `Browser`: CT-B09 "com logo" falhou com "Expected `''`, Actual `'Starter Kit'`", ou seja, a faixa mostrou o nome em vez da logo.
  - Duas repetições do arquivo isolado deram 50/50, e a segunda rodada da suíte passou no CT-B09.
- **Repro**: `vendor/bin/pest tests/Browser/AparenciaDasTelasDeAcessoTest.php --compact`, repetido; e `vendor/bin/pest --testsuite=Browser --compact`
- **Destino**: 3. Teste não determinístico: não se distingue defeito de flake.
- **Ação exigida**: tornar os dois casos determinísticos (espera pelo estado, isolamento da identidade com arquivos reais entre testes) e provar com repetições.

### QA-22 — `## Despachos` não registra os disparos do adendo, e a degradação citada no `04` não está no `03` · Minor · destino 1

- **Dimensão**: L6
- **Esperado**: segundo a `feature-wiki` (Delegação), todo disparo vai para `## Despachos`, e a degradação vai para o `03`.
- **Observado**: a seção "Revisão do Diff — Adendo 1" cita um "workflow de 4 revisores em paralelo" com cético, o Codex `exec --yolo` rodado de novo e o `fw-adversario-ct` (ADV-A1..A18). O quadro termina no #15, do ciclo 3. O `04` › Adendo 1 diz "Degradação declarada no `03`: a `feature-test-design` não derivou às cegas estes cenários", mas o `03` › Degradações lista só `search-docs` e Ponytail.
- **Repro**: `grep -n "^| 1[5-9] \|^| 2[0-9] " 03-progresso.md` → só o #15. `grep -n "Degradações" 03-progresso.md` → uma linha, sem a derivação do adendo.
- **Destino**: 1
- **Ação exigida**: uma linha por disparo do adendo, com modelo, `Custo` e auditoria, e a degradação da derivação declarada em Degradações.

### QA-23 — `04`/`05` do adendo: IDs reaproveitados em cenários diferentes, cabeçalhos e índices defasados · Minor · destino 1

- **Dimensão**: L1
- **Observado**:
  - IDs reaproveitados: CT-47, CT-48 e CT-50 nomeiam dois cenários distintos cada no `04`, e o step 9 do adendo os reaproveitou. No `05`, CT-B10 aparece 3 vezes e CT-B12 2 vezes. O `ids-ct.sh` passa (exit 0) porque confere só a presença, mas a matriz deixa de distinguir os cenários.
  - Cabeçalho do `04`: diz "Cenários: 42 · Regras: 17", e os `grep -cE` do template dão 56 · 17 · 57 · 0. Ainda diz "Oráculo atual: RQ-01..RQ-16, P-01..P-18".
  - Cabeçalho do `05`: diz "RQ-01..RQ-16, P-01..P-14".
  - O `## Índice de Cenários` do `05` não tem CT-B09..CT-B12.
  - No `04`, o Índice e o Checklist de Taxonomia (linha Upload) ainda tratam o CT-35, já `@obsoleto`, como vigente, e o M66 "Editor oferece upload novo" descreve hoje o comportamento pedido.
- **Repro**:
  1. `grep -oE '(Cenário|Esquema do Cenário): \[CT-[0-9]+\]' 04-casos-de-teste.md | grep -oE 'CT-[0-9]+' | sort | uniq -d` → CT-47, CT-48, CT-50
  2. `grep -cE '^[[:space:]]*(Cenário|Esquema do Cenário): \[CT-[0-9]+\]' 04-casos-de-teste.md` → 56
- **Destino**: 1
- **Ação exigida**: renumerar os cenários do step 9 do adendo com IDs novos (no `04`/`05` e nos `it()`). Recalcular o cabeçalho pelos `grep -cE`. Atualizar o oráculo dos cabeçalhos, os índices e a linha Upload/M66.

### QA-24 — P-23, a doc pt/en e o glossário dizem "continua igual" e "metade", e o celular mudou · Minor · destino 1

- **Dimensão**: L5 / L7
- **Relacionado a**: P-09, P-22, P-23, RQ-24
- **Observado**:
  - P-23 diz que uma aparência gravada antes "continua com o mesmo resultado". A doc pt/en repete: "continua igual" / "keeps looking the same". No celular, porém, ela perde a frase: o bloco `line-clamp` foi removido do CSS e `__base` é escondida (RQ-24). Isso é correto pelo pedido, mas o texto não ressalva.
  - P-09 continua `vigente` sem marca de substituição parcial. A coluna `Substitui` do Adendo cita uma premissa, não uma `RQ`.
  - O glossário define "Painel de marca" como "o formulário ocupa uma metade … no celular vira uma faixa", o que está defasado pela P-19 e pela P-22.
- **Repro**: `grep -n "continua igual\|keeps looking" docs/pt/autenticacao/aparencia-das-telas.md docs/en/autenticacao/aparencia-das-telas.md`; `grep -n "Painel de marca" wikis/glossario.md`
- **Destino**: 1
- **Ação exigida**: P-23 e as duas docs ressalvam o celular (RQ-24). P-09 recebe a marca de alteração parcial. O glossário atualiza a definição.

### QA-25 — O botão de pausar troca o rótulo e o `aria-pressed` ao mesmo tempo · Minor · destino 3 → 2

- **Dimensão**: H
- **Relacionado a**: P-21 (WCAG 2.2.2)
- **Esperado**: no padrão de botão alternável do WAI-ARIA APG, o rótulo não muda quando o estado muda.
- **Observado**: na view `painel-de-marca.blade.php`, o botão tem `x-text="pausado ? 'Retomar o movimento' : 'Pausar o movimento'"` junto com `x-bind:aria-pressed="pausado"`. O leitor de tela anuncia "Retomar o movimento, pressionado", o que é ambíguo. O CT-B10 afirma as duas coisas, então o teste codifica o padrão.
- **Repro**: ler o bloco do botão `data-kit-pausa` na view, e o CT-B10 em `tests/Browser/AparenciaDasTelasDeAcessoTest.php`
- **Destino**: 3 → 2
- **Ação exigida**: escolher um dos dois mecanismos (rótulo fixo com `aria-pressed`, ou rótulo variável sem `aria-pressed`), com o CT-B10 ajustado primeiro.

### QA-26 — A ajuda da largura traz um exemplo fixo que contradiz o valor escolhido · Cosmético · destino 2

- **Dimensão**: F
- **Observado**: o `helperText` de `tamanho_do_painel` diz "(painel em 35 %, formulário em 65 %)" qualquer que seja o valor. Na captura `art/telas-de-acesso-organizacao.png`, o campo mostra "45 %" e a ajuda continua dizendo 35/65.
- **Repro**: abrir a aba Telas de acesso, escolher 45 % e ler a ajuda
- **Destino**: 2
- **Ação exigida**: dizer "por exemplo" ou calcular o resto a partir do valor escolhido.

## Matriz de Rastreabilidade

`rastreabilidade.sh` exit 0. `indice.sh --check` não se aplica (não há `07-tickets/`). Lacunas:

| RQ/P | Cláusula ou premissa | Passo PRD | CT | CT-B | Código | Resultado | Veredito |
|------|----------------------|-----------|----|------|--------|-----------|----------|
| RQ-17 / RQ-08 | merge com tag depois dos 4 cenários | — (processo) | CT-21, CT-27 `@obsoleto` (sessão) | — | adendo, 9 arquivos distribuídos | cenários só sobre `d343fc56` | ❌ QA-18 |
| P-23 | aparência antiga continua igual | 10 | CT-46 (desktop) | — | `normalizar`, CSS do celular | no celular, a frase some (RQ-24) | ⚠️ QA-18, QA-24 |
| RQ-24 / P-22 | celular só com a marca | 10 | — | CT-B09 | CSS do celular | verde, mas intermitente | ⚠️ QA-21 |

## Dimensões

| # | Dimensão | Status | Observação |
|---|----------|--------|------------|
| A | Cobertura do requisito | ⚠️ | `rastreabilidade.sh` exit 0. `indice.sh --check` não se aplica (sem `07-tickets/`). Diff por passo conferido: passo 10 × `cbb65fb6..a1a50f6f` (decisor, trait, editor, preview, view/CSS, docs). RQ-19..RQ-24 e P-19..P-24 estão implementadas e cobertas por CT-43..CT-53 e CT-B09..CT-B12. RQ-18: as capturas `painel-arte`, `painel-escuro` e `organizacao` existem e estão citadas nas docs pt/en. Lacuna: QA-18 |
| B | Fronteiras e dados | ✅ | Pest 5.0.5 não tem `--agent`. As classes de valor foram lidas no `normalizar` e nos datasets do CT-44/CT-45 (25, 75, 52, -50, `"45"`, texto, `\n` no fim, `..`, SVG, URL, subpasta, dois arquivos) e rodadas: `AparenciaDasTelasAdendoTest` 57/57. O nome gravado vem de `hashName()`, com extensão pelo MIME, e casa o formato do caminho |
| C | Matriz de permissão | ✅ | Nenhuma rota ou policy nova. O upload só existe nos dois editores já autorizados (configurações; `TenantPolicy`). CT-43 cobre os dois. O preview continua com `canAccessPanel(admin)` e reautorização (CT-38/39 verdes) |
| D | Observabilidade | ✅ | Ponto novo: `AparenciaDasTelasDeAcesso::urlDaImagem`, `warning` no canal `autenticacao`, prefixo `[Classe@Método]`, contexto `caminho`/`disco`, sem PII, com limitação de um aviso por hora por caminho (`Cache::add`). CT-48 verde. O log real não foi gerado (ver *Não Verificado*) |
| E | Performance | ✅ | `/admin/login` com painel, destaque logo e rede: 3 queries (só a sessão), igual ao ciclo anterior. A `rede()` tem 14 pontos e é O(n²) trivial. Uma chamada `exists()` ao disco por render, só com destaque imagem |
| F | UX de erro | ⚠️ | O SVG é recusado com erro no campo (CT-51). Imagem ausente não derruba a tela (CT-48). Achado: QA-26 |
| G | Tema e cor | ⏭️ não verificada | `dark-mode.sh --mecanismo` exit 1 (Filament, classe `dark`). Nível 1, `dark-mode.sh` sobre `--diff-filter=AM` do adendo: exit 0. Nível 3 sem MCP. A captura versionada `art/telas-de-acesso-painel-escuro.png`, olhada, está legível (texto escuro sobre âmbar, P-18), mas é artefato da sessão, não observação ao vivo |
| H | Acessibilidade | ⏭️ não verificada | Nenhum CT-B da feature chama `assertNoAccessibilityIssues` (`grep -c` = 0), e o MCP está indisponível. Leitura: SVG `aria-hidden`/`focusable=false`, botão com `focus-visible`. Achado: QA-25 |
| I | Segurança da superfície nova | ✅ | Coberto pelo step 9 do adendo, `## Revisão do Diff — Adendo 1` do `03` (14 linhas, 10 ADV refutados). Coberto além dele: upload com `acceptedFileTypes` explícito, sem `image()`, que casaria SVG; teto do kit; caminho gravado preso por `\z` e sem `/` interna (CT-45); URL por `asset()` (`app.md`); `aparenciaDaTela()`/`destaqueDoPainelDeMarca()` só com o que a tela mostra (CT-50 × 2); `imagem_temporaria` só PNG/JPEG/WebP por `guessExtension()` e escapada no `src` (CT-52). As quatro linhas *eixo 9* não rodaram de novo |
| J | Regressão adjacente | ⚠️ | Kit `--parallel` 5110 (5107 passaram, 3 pulados, 0 falhas); Tenancy `--parallel` 743/743. Suíte `Browser` em duas rodadas: 2 e 1 falhas (Dock CT-B02, flake conhecido; CT-B09 uma vez). Arquivo de navegador da feature: 50/50 em 2 de 4 execuções. Achados: QA-18, QA-21. RCRCRC: o partial `media` e o CSS do celular (Recent, Core, Repaired), conferidos por CT-46, CT-B09 e CT-B11 |
| K | Adequação da suíte | ⏭️ não verificada | K1 `k1-oraculo-fraco.sh` exit 1, com as mesmas 4 candidatas dos ciclos anteriores (CT-31, CT-33, CT-07, CT-11), rejeitadas porque a asserção está em helper. O `AparenciaDasTelasAdendoTest` não tem candidata. O K2 não foi rodado (ver *Não Verificado*) |
| L | Consistência documental | ❌ | L1 `ids-ct.sh` exit 0, mas há QA-23. L2 `citacoes.sh` exit 1, com uma linha só, no `06` anterior, que este relatório substitui (rejeitada). L3: QA-19. L4 `conformidade-rules.sh main` exit 0; `views.md` (comentário Blade sem diretiva), `app.md` (`asset()`) e `css-filament.md` (classes `kit-*`) conferidas nos arquivos do adendo. L5: QA-24; pt × en coerentes. L6 `checkbox-sem-evidencia.sh` exit 0; Kit 5110 e Tenancy 743 reproduzidos; QA-20, QA-22. L7: QA-24 (glossário). L8 `roteiro-manual.sh` exit 0; n/a, sem `08` e com decisão `não` |

## Débitos Aceitos

- Nenhum neste ciclo, porque há Major aberto. Os débitos do `03` (Q1, Q2, alternador fora do teclado, organização fora do navegador, positivo em redefinir/verificar/2FA, preview sem a pilha do painel, ADV-A15) seguem lá e não foram contestados.

## Suspeitas Não Confirmadas

- Imagem substituída ou destaque trocado deixam o arquivo antigo em `telas-de-acesso/` no disco público, já que o `FileUpload` não apaga o arquivo anterior por padrão. Não foi reproduzido porque exigiria gravar configuração no app. Nenhuma `RQ` trata da limpeza.

## Não Verificado

- G, nível 3 (olhar os dois temas ao vivo): Playwright MCP indisponível.
- H, axe: nenhum CT-B da feature o chama, e o MCP está indisponível.
- K2, `--mutate`: no Windows o plugin conta como morto o subprocesso com filtro truncado ou com nome de teste entre aspas, e o adendo tem descrições com `"` (CT-48 `destaque "imagem"`). Certificar exige aplicar o mutante no arquivo, o que é vedado ao gate. Há também prova pendente: os cenários do adendo foram derivados pela sessão (não às cegas), e os acrescentados depois da revisão adversarial (CT-53 e as segundas ocorrências de CT-47/48/50, CT-B10, CT-B12) não tiveram re-revisão.
- D, log real do aviso de imagem ausente: gerar o aviso exigiria gravar a aparência no app servido. Coberto pelo CT-48 com `Log::spy`.
- Sessão autenticada no app servido (editor, upload e preview ao vivo): sem MCP. Coberta por CT/CT-B.
- RQ-12, busca pelos nomes do projeto de referência: os nomes estão mascarados no `00`. Foi conferido só o oráculo genérico: hosts novos do diff do adendo = `github.com`/`raw.githubusercontent.com` do kit, `img.shields.io`, `www.w3.org` e `exemplo.test` em teste.
