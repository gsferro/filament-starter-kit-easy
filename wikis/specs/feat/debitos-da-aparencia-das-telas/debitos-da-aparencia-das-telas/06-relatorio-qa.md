# Relatório de QA — Débitos G, H, J e K da aparência das telas de acesso

> Requisito: `00-requisito.md` · Plano: `01-plano-acao.md`
> Perfil de esforço: padrão
> Natureza da wiki: débito de verificação (fecha débitos de `feat/aparencia-das-telas-de-acesso/tela-de-login-personalizavel`) · Toca infra compartilhada: sim → o helper `visitaComMovimentoReduzido()` passa para `tests/Pest.php`; sobrescrita em `resources/views/vendor/filament-auth-designer/` · Regressão: sim
> Independência: sub-agente fw-qa-gate/claude-opus-5-5, contexto novo sem a conversa
> Cobertura: 9 de 12 dimensões verificadas ou provadas não aplicáveis · teto: APROVADO COM DÉBITO

## Veredito — Ciclo 2

**REPROVADO → especificação**

- Blocker: 0 · Major: 1 (QA-06) · Minor: 0 · Cosmético: 1 (QA-07)
- Não verificadas: G (nível 3), J (a suíte inteira não foi reexecutada), K (K2). As causas estão em *Não Verificado*
- `RQ` abertas: nenhuma
- Ambiente: app em `http://127.0.0.1:8091` (sondado por `curl`) · Pest 5.3.7 · MCP: Playwright indisponível; o Boost MCP não conectou (timeout)
- Convergência: o ciclo 2 é o teto do perfil padrão. O achado aberto escala ao usuário
- Código igual ao do ciclo 1: `git diff --stat a50089aa HEAD -- . ':!wikis'` saiu vazio. Por isso as amostras de J e as medições do ciclo 1 continuam valendo

## Ciclo 1 — resumo e tratamento conferido

O ciclo 1 terminou em **REPROVADO → especificação**: 1 Major (QA-01), 2 Minor (QA-02, QA-03) e 2 Cosméticos (QA-04, QA-05). A independência foi parcial, porque o despacho trouxe um resumo do diff.

| QA | Achado | Tratamento (`03` → `## Quality Gate`) | Conferência do ciclo 2 |
|---|---|---|---|
| QA-01 | os cenários 1–4 foram atribuídos à v0.53.1, mas a rodada foi sobre `e267f909` | `03:21` e `00:51` (P-01) corrigidos; `origem/03:321` marcado; débito em `03:54` | **em parte**: o `01` e `origem/03:261` mantêm a alegação antiga → QA-06 |
| QA-02 | comentário HTML engolia a R4, o CT-114 e o índice | o comentário fecha na própria linha | ✅ `grep -n '<!--\|-->'` → só a `:16`; `grep -cE '^## Regra R[0-9]+'` → 4 |
| QA-03 | débito do cadastro e linha do H1 desatualizados | `03:55` virou o resíduo (a arte); `03:34` diz "12 de 12" | ✅ |
| QA-04 | cabeçalho da origem dizia 113 cenários | 114 em `origem/04:5` e em `03:42` | ✅ o `grep -cE` dá 114 |
| QA-05 | motivo "mínimo" para dispensar a revisão adversarial | `04:5` diz "padrão, sem Impacto 3" | ✅ |

## Achados

### QA-06 — A correção do QA-01 não chegou ao `01` nem ao `## Débitos` da origem: os dois ainda dizem que o RQ-06 fecha pela rodada `f1`..`f4` · Major · destino 1

- **Dimensão**: L (L3) e A
- **Relacionado a**: RQ-06, P-01, P-04, passo 3 do PRD, QA-01 do ciclo 1
- **Esperado**: depois do QA-01, toda afirmação de que os cenários 1–4 estão pagos pela rodada `f1`..`f4` deveria levar a marca `*(alterado em …)*` e apontar o débito. O `03:21`, o `03:54` e a P-01 (`00:51`) já fazem isso. A P-04 manda registrar na origem o resultado de cada débito.
- **Observado**:
  - `01-plano-acao.md:21` → `| RQ-06 | J: cenários 1–4 | 3 | P-01, a rodada \`f1\`..\`f4\` de 2026-11-15 |`
  - `01-plano-acao.md:57` → "Os cenários 1–4 fecham pela rodada registrada (P-01)", sem marca de alteração
  - `origem/03-progresso.md:258–261`, em `## Débitos`: "**G, H, J e K pagos em 2026-11-18** … J: a suíte e a rodada `f1`..`f4`". O débito dos quatro cenários sobre a tag não está na origem. O commit `d7ec68b7` só marcou a `origem/03:321`.
  - O PRD e a origem afirmam um fechamento que o `03` desta wiki registra como desvio. É o padrão L3 "desvio que existe só no `03`", e é o que o próximo leitor da origem vai ler.
- **Repro**:
  1. `grep -rn "f1\`\.\.\|rodada registrada" wikis/specs/feat/debitos-da-aparencia-das-telas/ wikis/specs/feat/aparencia-das-telas-de-acesso/tela-de-login-personalizavel/0[0-5]*.md`
  2. Saída relevante: `debitos/01-plano-acao.md:21`, `debitos/01-plano-acao.md:57`, `origem/03-progresso.md:261` sem marca. As demais linhas (`00:24`, `00:37`, `00:51`, `03:21`, `03:54`, `03:116`, `origem/03:244`, `:290`, `:321`, `:332`) são texto original, histórico ou já corrigidas.
  3. `git show --stat d7ec68b7` não traz o `01` entre os arquivos tocados.
- **Evidência**: a saída do `grep` acima.
- **Destino**: 1 especificação
- **Ação exigida**: marcar `01:21` e `01:57` com `*(alterado em …: QA-01/QA-06 — rodada sobre `e267f909`; os cenários sobre a tag são débito, `03:54`)*`. Corrigir `origem/03:261` e acrescentar ao `## Débitos` da origem o débito dos quatro cenários sobre a v0.55.1, com a referência a `debitos/03:54` (P-04). Antes de fechar, rodar de novo o `grep` do passo 1.

### QA-07 — O comentário de contagem do `04` diz que o grep do template dá 4 cenários, e o grep literal dá 1 · Cosmético · destino 1

- **Dimensão**: L (L1)
- **Esperado / Observado**: `04:16` diz "contado pelos grep -cE de template-04.md … 4 cenários". O comando do template, `grep -cE '^[[:space:]]*(Cenário|Esquema do Cenário): \[CT-[0-9]+\]' 04-casos-de-teste.md`, dá **1**, porque não casa `[CT-B…]`. O 4 do cabeçalho está certo, mas só sai com `\[CT-(B)?[0-9]+\]`.
- **Repro**: os dois `grep -cE` acima, na pasta da wiki → `1` e `4`
- **Destino**: 1
- **Ação exigida**: escrever no comentário o padrão realmente usado, `\[CT-(B)?[0-9]+\]`, ou registrar a adaptação do template para CT-B dentro do `04`.

## Matriz de Rastreabilidade

| RQ/P | Cláusula ou premissa | Passo PRD | CT | CT-B | Código | Resultado | Veredito |
|------|----------------------|-----------|----|------|--------|-----------|----------|
| RQ-06 | cenários 1–4 sobre um commit com a feature | 3 | — (processo) | — | — | rodada `f1`..`f4` sobre `e267f909`, que contém o Adendo 4 mas não as correções da v0.53.1 nem a sobrescrita. Arquivo novo provado só pelas rotas estáticas (`git archive HEAD` o lista; `KitUpdate::CAMINHOS_DO_KIT`, `:93`–`:387`, tem `resources/views/vendor/filament-auth-designer` em `:245`). Cenários sobre a tag: débito (`03:54`) | ⚠️ débito declarado; o `01` contradiz → QA-06 |
| P-01 | débito pago por evidência posterior | 3 | — (processo) | — | — | revista no `00:51`; o `01:21` e o `01:57` não acompanharam | ⚠️ QA-06 |
| P-04 | resultado registrado na origem | 6 | — | — | — | `origem/03:261` dá J como pago | ⚠️ QA-06 |

As demais linhas não têm lacuna:

- RQ-01..RQ-04 e P-02 vão para CT-B21..CT-B23 e CT-114, verdes no ciclo 2.
- RQ-05, RQ-07, RQ-08 e P-03 são processo, com a evidência no `03`.

## Dimensões

| # | Dimensão | Status | Observação |
|---|----------|--------|------------|
| A | Cobertura do requisito | ⚠️ | `rastreabilidade.sh` exit 1, com 7 linhas (RQ-05..08, P-01, P-03, P-04 "sem CT"), rejeitadas: são cláusulas de execução, com o comando no `03`. Sem `07-tickets/`. Diff por passo como no ciclo 1. Achado: QA-06 |
| B | Fronteiras e dados | ✅ | código igual ao do ciclo 1 (diff vazio). Sem entrada nova; `$position` vem dos providers |
| C | Matriz de permissão | ✅ | nenhuma rota, policy ou ação nova; telas guest |
| D | Observabilidade | ✅ | PRD com "Logs: nenhum" nos 6 passos; o diff não tem PHP de aplicação |
| E | Performance | ✅ | sobrescrita Blade estática, sem query |
| F | UX de erro | ✅ | nenhum caminho de erro novo |
| G | Tema e cor | ⏭️ não verificada | fora do perfil padrão. `dark-mode.sh --mecanismo` exit 1 (Filament, classe `dark`); nível 1 `dark-mode.sh` exit 0 nos arquivos AM de `resources/`; nível 2: CT-B21 linhas "escuro" verdes. Nível 3 não feito |
| H | Acessibilidade | ✅ | executada além do perfil. `AcessibilidadeDasTelasDeAcessoTest` 14/14 no ciclo 2 (sem os `warnings` do ciclo 1). No app servido, `/admin/login` e `/admin/password-reset/request` têm `role="menu"` no wrapper, 1 vez cada |
| I | Segurança da superfície nova | ✅ | coberto pelo step 9 (`## Revisão do Diff (step 9)` do `03`: 11 achados, 1 refutado por medição, 2 mantidos com motivo). Além dele: nenhuma rota nova, nenhum model, nenhum upload; `{!! $styles !!}` vem de configuração em código. Código igual ao do ciclo 1 |
| J | Regressão adjacente | ⏭️ não verificada | o gate não rodou a suíte inteira nem o `--tia` (restrição: um processo de teste por vez). Amostras: ciclo 1 (`TelasDoKitTest`, `IdentidadeVisualTest` 9/9, `RoteiroDeAtualizacaoTest` 212/212) e ciclo 2 (cinco arquivos Kit 630/630; CT-110..CT-114 12/12; Browser 14/14). O ciclo 1 contou a J como verificada; a reclassificação é de critério, não fato novo |
| K | Adequação da suíte | ⏭️ não verificada | K1 `k1-oraculo-fraco.sh` exit 1, com 2 linhas (CT-31 `:724`, CT-75 `:376`) rejeitadas: testes antigos, fora das linhas do diff (o CT-114 é linha nova). K2 fora do perfil. A amostragem manual do RQ-07 não foi reproduzida. M136: equivalência argumentada, com CT-111 matando a remoção dupla. Cabeçalho do `04`: "não exigida (padrão, sem Impacto 3)" |
| L | Consistência documental | ⚠️ | 2 achados (QA-06, QA-07); detalhes por checagem logo abaixo da tabela |

**Checagens da dimensão L:**

- **L1**: `ids-ct.sh` desta wiki com `'tests/**/AcessibilidadeDasTelasDeAcesso*.php'`: exit 1 (CT-114 mora em `tests/Kit/AparenciaDasTelasDeAcessoTest.php` (o CT-114), como declarado). Origem com os arquivos da aparência e o de acessibilidade: exit 0.
- **L2**: `citacoes.sh` exit 0.
- **L4**: `conformidade-rules.sh {wiki} main` exit 0.
- **L5**: CHANGELOG 0.55.1 coerente.
- **L6**: `checkbox-sem-evidencia.sh` exit 0. Números reproduzidos neste ciclo: 630/630 (`03:20`); badge 2.690 (`README.md:8`); "8 de 12 → 12 de 12" coerente entre `03:14` e `03:34`; 114 cenários e 129 mutantes da origem; `git archive HEAD` com os dois arquivos novos; `CAMINHOS_DO_KIT` com as quatro entradas (`:245`, `:252`, `:269`, `:271`, dentro do array `:93`–`:387`).
- **L7**: sem mudança desde o ciclo 1.
- **L8**: n/a. Não há `08`, o `roteiro-manual.sh` saiu com exit 0 e o `03` não registra decisão de roteiro.

## Débitos Aceitos

- Nenhum: com o QA-06 aberto o veredito é REPROVADO.
- O QA-07 entra como débito se não for corrigido junto.
- Seguem como débito declarado no `03` (`## Débitos`): ADV-E5, M3/M4 não medidos, cenários 1–4 sobre a v0.55.1, e a arte no cadastro não afirmada.

## Suspeitas Não Confirmadas

- Os `warnings: 2` do ciclo 1 em `AcessibilidadeDasTelasDeAcessoTest` não reapareceram (14/14, sem warnings). A suspeita foi encerrada.

## Não Verificado

- G nível 3 (olhar a tela no tema escuro): fora do perfil padrão; Playwright MCP indisponível; o gate não tem `Write` para um teste temporário de captura. As capturas da sessão foram apagadas, e a evidência é a leitura do `03:8`.
- J: a suíte inteira (6206 testes sem Browser, 275 com Browser) e o `--tia` não foram reexecutados, por causa da restrição de um processo de teste por vez. Os números do `03:18–19` são de `276cdc21`/`c530039c`, anteriores ao `a50089aa`. A regressão foi conferida por amostragem.
- J / RQ-06: os quatro cenários de `wikis/checklist-de-release.md` sobre a tag. O checklist manda rodá-los a cada tag nova, via `create-project` "na tag nova", então "depois da tag" é coerente com ele. O débito está em `03:54`.
- K2: fora do perfil padrão. A amostragem manual do RQ-07 não foi reproduzida, porque exigiria editar arquivos de aplicação. A equivalência do M136 é argumentada, não medida.
- Cadastro no app servido: `/app/register` responde 302 → `/app/login`, porque `kit.registro.habilitado` é `false` (`php artisan config:show kit.registro`). A arte no cadastro não foi observada; só os CT-B (com `config` própria) cobrem a tela.
- Boost MCP (`browser-logs`, `database-*`): não conectou (timeout). O console foi coberto pelos CT-B verdes.
