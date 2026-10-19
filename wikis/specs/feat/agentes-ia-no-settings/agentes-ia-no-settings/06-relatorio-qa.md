# Relatório de QA — agentes-ia-no-settings: Agentes de IA ligados pelo settings, por painel

> Requisito: `00-requisito.md` · Plano: `01-plano-acao.md`
> Perfil de esforço: completo (UI com JS: chat flutuante e pilha de botões; domínio sensível: autorização do catálogo e dado do usuário indo ao provedor de IA)
> Natureza da wiki: nova · Toca infra compartilhada: sim → settings do kit, `config/kit.php`, tela de configurações, render hook global do chat, `voltar-ao-topo`, `tests/Pest.php` · Regressão: sim
> Independência: sub-agente fw-qa-gate/claude-opus-5-5, contexto novo sem a conversa (artefatos do scratchpad da sessão — `falsificabilidade*.out`/`.py`, `suite-final2.log`, `browser-suite.log` — lidos só como evidência da L6)
> Cobertura: 9 de 12 dimensões verificadas ou provadas não aplicáveis · teto: APROVADO COM DÉBITO

## Veredito — Ciclo 2

**APROVADO COM DÉBITO**

- Blocker: 0 · Major: 0 · Minor: 0 · Cosmético: 0 (os seis achados do ciclo 1 estão fechados; nenhum achado novo)
- Não verificadas: G, H, K. Continuam com as mesmas causas do ciclo 1, detalhadas em *Não Verificado*.
- `RQ` abertas: nenhuma. Ficam abertas as perguntas Q1, Q3, Q4, Q6, Q8, Q9 e Q10, cobertas por premissa ou determinadas por cláusula fechada. A P-01 depende da Q1.
- Ambiente: app em `http://127.0.0.1:8010`, Pest 5.0.5, Playwright MCP indisponível, Boost MCP indisponível (`CONNECT_TIMEOUT`).

**Escopo do ciclo 2.** Entre `d9211c96` e `eeb47d03` mudaram só texto e versão:
- `git diff --stat d9211c96 HEAD`: 10 arquivos, todos em wiki, glossário, CHANGELOG, docs de configurações e `config/kit.php` (só `version`);
- nenhum arquivo de `app/`, `resources/`, `database/` ou `tests/`.

Por isso as dimensões A, B, C, D, E, F, I e J ficam **aceitas por referência ao ciclo 1**: o código que elas verificaram não mudou. A dimensão L foi reverificada inteira, junto com o que mudou na versão e no CHANGELOG.

### Reverificação dos achados do ciclo 1

| Achado | Estado | Evidência (ciclo 2) |
|---|---|---|
| QA-01 (M-h sem evidência) | fechado | `falsificabilidade-mh.out` mostra o mutante aplicado, o comando `--filter=CT-09\|CT-11`, "19 tests, 17 passed, 2 failed" (as duas linhas do CT-09, ⌘K telas e registros) e `restaurado: True`. Bate com o mecanismo esperado: `shouldRegisterNavigation()` e o trait seguem guardando a barra e o 404, então só o ⌘K vaza. A saída está colada em `03-progresso.md:68`. O "8 mortos" do CHANGELOG `[0.51.0]` agora tem evidência para os 8 |
| QA-02 (`02` defasado) | fechado | `02:6` diz "D1 a D11"; a Superfície ganhou `hydrate()` e `authorizeAccess()`. As citações conferem: `sed -n 287p` dá `public function hydrate()` e `sed -n 43p` dá `protected function authorizeAccess()`. `citacoes.sh` exit 0 |
| QA-03 (`04` defasado) | fechado | `04:107` diz "Q8 aberta no `00`", e `04:822` aponta `AgentesIaNoSettingsChatTest.php`, ambos com a marca `*(alterado em …: QA-03)*` |
| QA-04 (prefixos de log) | fechado | O `01` (passos 5 e 7) e o `04:110` trazem `[ExigeAgentesDeIaLigados@exigirUsoDeAgentesLigado]` e `[AssistenteChatWidget@exigirUsoNoPainel]`, os mesmos do log real visto no ciclo 1 |
| QA-05 (termo "catálogo") | fechado | `wikis/glossario.md` ganhou "Catálogo de agentes", com *Não confundir com* "Catálogo (do dock)" |
| QA-06 (checkboxes) | fechado | Os três itens foram fechados com evidência; `checkbox-sem-evidencia.sh` exit 0 |

### O que mudou além dos achados

- **`config/kit.php` com `version` 0.51.0**: `php artisan config:show kit.version` dá 0.51.0.
- **Docs pt/en com "(kit 0.51.0)"**: `SiteDeDocumentacaoTest` + `KitInfoTest` deram 103/103 (415 asserções), rodados por mim.
- **CHANGELOG `[0.51.0]`, números da seção *Validação***:
  - Kit/Tenancy 4.977/4.974/3 bate com `suite-final2.log`.
  - Navegador 164/126/37 pulados/1 colisão bate com `browser-suite.log` (164 − 126 − 1 = 37). A colisão no CT-B01 foi causada pelos testes que este gate rodou em paralelo no ciclo 1. O CT-B01 deu 2/2 na minha execução do ciclo 1.
  - Mutação "8 mortos" agora tem evidência para os 8 (QA-01).
- **CHANGELOG *Atualizando***: coerente com as docs pt/en de `atualizando-o-projeto` já conferidas no ciclo 1. Os helpers que cita (`ligarAgentesDeIa()`, `abrirPainelDaPilha()`, `moverDockDaPilha()`, `rolarPaginaDaPilha()`, `MEDIR_PILHA_FLUTUANTE`) existem em `tests/Pest.php` e foram conferidos no ciclo 1 (`HelpersDeTesteTest` verde).

## Achados

Nenhum achado aberto no ciclo 2.

## Dimensões (ciclo 2)

| # | Dimensão | Status | Observação |
|---|----------|--------|------------|
| A | Cobertura do requisito | ✅ | Por referência ao ciclo 1 (código inalterado). `rastreabilidade.sh` reexecutado: exit 0 |
| B | Fronteiras e dados | ✅ | Por referência ao ciclo 1 |
| C | Matriz de permissão | ✅ | Por referência ao ciclo 1 |
| D | Observabilidade | ✅ | Por referência ao ciclo 1. O prefixo do plano agora bate com o log (QA-04 fechado) |
| E | Performance | ✅ | Por referência ao ciclo 1 |
| F | UX de erro | ✅ | Por referência ao ciclo 1 |
| G | Tema e cor | ⏭️ não verificada | Mesmas causas do ciclo 1: sem CT-B em tema escuro no `/admin` e no `/infra`, sem MCP |
| H | Acessibilidade | ⏭️ não verificada | Mesmas causas do ciclo 1: sem `assertNoAccessibilityIssues()` no CT-B01, sem MCP |
| I | Segurança da superfície nova | ✅ | Por referência ao ciclo 1 (step 9: 12 achados, 3 rejeitados; probes HTTP reais de P-03 e P-06) |
| J | Regressão adjacente | ✅ | Por referência ao ciclo 1. No ciclo 2: guardas de docs e versão 103/103. A suíte completa não foi rodada de novo, por instrução |
| K | Adequação da suíte | ⏭️ não verificada | K1 sem mudança (exit 1, 8 linhas rejeitadas, testes pré-existentes). A amostragem manual agora tem 8/8 mutantes com evidência, mas não substitui um score medido. O `pest --mutate` não foi medido: o projeto declara o plugin não confiável no Windows (`.ai/rules/testes.md`), e a execução do gate no ciclo 1 abortou por colisão |
| L | Consistência documental | ✅ | Seis achados fechados. L1 `ids-ct.sh` exit 0; L2 `citacoes.sh` exit 0; L4 `conformidade-rules.sh` exit 0; L6 `checkbox-sem-evidencia.sh` exit 0, e os números novos do CHANGELOG reproduzidos pelos logs; L7 com o glossário atualizado |

## Débitos Aceitos

- Cobertura: G, H e K não verificadas (ver *Não Verificado*). Replicar no `03-progresso.md`.

## Suspeitas Não Confirmadas

- Contraste do botão e do slide-over do chat no tema escuro do `/admin` e do `/infra`: nunca medido. Mesma paleta (`CorPrimaria::paleta()`) do `/app`, então o risco é baixo.

## Não Verificado

- **G**: níveis 2 e 3 no `/admin` e no `/infra`. Motivo: o CT-B01 só roda no tema claro, e o Playwright MCP está indisponível.
- **H**: axe, teclado e foco do chat nos painéis novos. Motivo: o CT-B01 não chama `assertNoAccessibilityIssues()`, e não há MCP.
- **K2**: score de mutação não medido. Motivos: o plugin é declarado não confiável no Windows (`.ai/rules/testes.md`), e a execução do gate no ciclo 1 abortou por colisão de cache de views. Existe só amostragem manual, com 8/8 mutantes e evidência.
- **J**: a suíte completa foi aceita pelos logs da sessão (`suite-final2.log`, `browser-suite.log`) e não reexecutada pelo gate.

---

## Ciclo 1 (histórico)

**Veredito do ciclo 1: REPROVADO → especificação.** Blocker 0 · Major 1 · Minor 4 · Cosmético 1.

- **QA-01 — Major — L6 — destino 1.** O `03` alegava "8/8 mortos", mas o resultado do M-h não tinha evidência. O `falsificabilidade.py` morreu com `UnicodeEncodeError` (⌘) no `print` do M-h, e o filtro que rodou era só CT-09, não CT-09/CT-11.
- **QA-02 — Minor — L3 — destino 1.** O `02` dizia "D1 a D9", e a Superfície Livewire não listava o `hydrate()` do chat nem o `authorizeAccess()` do trait.
- **QA-03 — Minor — L3/L5 — destino 1.** O `04:107` dizia "Q8 respondida" com a Q8 aberta no `00`, e o `04:822` dava o arquivo errado para CT-24 e CT-26.
- **QA-04 — Minor — L3 — destino 1.** O prefixo de log do `01` (passos 5 e 7) e do `04:110` diferia do código.
- **QA-05 — Minor — L7 — destino 1.** "Catálogo" estava em dois sentidos, sem entrada no glossário.
- **QA-06 — Cosmético — L6 — destino 1.** Três checkboxes do `03` estavam abertos embora feitos.

**Dimensões do ciclo 1:**
- **A** ✅: `rastreabilidade.sh` exit 0. No app servido, 404 nas três URLs desligado e 200 ligado; widgets e chat conforme a lista.
- **B** ✅: CT-08, CT-03 e CT-16.
- **C** ✅: CT-11, CT-13 e CT-15.
- **D** ✅: `warning` real no canal `ai`, context sem PII, um por request recusado.
- **E** ✅: CT-06.
- **F** ✅: 404 em pt, com saída.
- **G** ⏭️ e **H** ⏭️: as mesmas causas acima.
- **I** ✅: step 9 + HTTP real (`novaConversa` e `$refresh` do chat no `/admin` com o painel fora da lista → 404; `$refresh` da `ListAgentesIa` depois de desligar → 404).
- **J** ✅: ancestrais 140/140 + browser 29/29 + log da suíte.
- **K** ⏭️: K1 exit 1, linhas rejeitadas; K2 abortado por colisão.
- **L** ⚠️: os seis achados acima.

**Números reproduzidos no ciclo 1:** 127/127 (548 asserções), cabeçalho do `04` 28/15/74/0, 14 marcas `alterado em` no `01`.
