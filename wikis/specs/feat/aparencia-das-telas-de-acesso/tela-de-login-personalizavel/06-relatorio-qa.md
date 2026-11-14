# Relatório de QA — feat/aparencia-das-telas-de-acesso: Tela de login personalizável (Ciclo 2 dos Adendos 2 e 3)

> Requisito: `00-requisito.md` · Plano: `01-plano-acao.md`
> Perfil de esforço: completo. A UI tem JS (EasyMDE, iframe de preview, rede animada com Alpine). O domínio é sensível: Markdown vira HTML numa tela pública, e há escopo por organização
> Natureza da wiki: evolução · Toca infra compartilhada: sim → todas as telas de autenticação dos três painéis · Regressão: sim
> Independência: sub-agente fw-qa-gate/claude-opus-5-5, contexto novo sem a conversa
> Cobertura: 8 de 12 dimensões verificadas ou provadas não aplicáveis · teto: APROVADO COM DÉBITO

## Veredito — Ciclo 2 dos Adendos 2 e 3

**REPROVADO → teste (QA-34)**

- Blocker: 0 · Major: 1 novo (QA-34). Fica também 1 Major do ciclo 1 aberto como pendência de release (QA-28, cenários) · Minor: 1 novo (QA-35) · Cosmético: 0
- Não verificadas: G, H, J, K. As causas estão em *Não Verificado*
- `RQ` abertas: nenhuma formalmente. Duas perguntas seguem abertas com a recomendação implementada como premissa estreita: Q1 (P-01) e Q12 (P-27, registrada a pedido do QA-29). Ficam como débito, como no ciclo 1
- Ambiente: app em `http://127.0.0.1:8123`, HEAD `694ec0d8`. As telas `/admin`, `/app` e `/infra/login` respondem 200 com `media-left`, e `/admin/password-reset/request` responde 200 com `media-right`. Pest 5, sem `--agent`. Boost MCP indisponível (`laravel-boost` CONNECT_TIMEOUT). Playwright MCP indisponível
- Rodado por este gate, um arquivo por vez: `tests/Kit/AparenciaDasTelasPorTelaTest.php` 87/87; `tests/Tenancy/AparenciaDasTelasDeAcessoTenancyTest.php` 82/82; `tests/Kit/ConfiguracoesSecoesColapsaveisTest.php` 32/32; `tests/Browser/AparenciaDasTelasDeAcessoTest.php --filter='CT-B1[678]'` 6/6
- Sobre o número alegado pela sessão: Kit 5198 − 5166 = +32. Isso bate com o PorTela, que passou de 55 para 87, e o arquivo foi rodado aqui. O badge do README passou de 2.562 para 2.581, +19, que é CT-79..96 mais CT-B18. A suíte inteira não foi reproduzida

## Situação dos achados do ciclo 1

| QA | Situação | Evidência |
|---|---|---|
| QA-27 | fechado | Há `campos(daOrganizacao: true)` no `TenantForm`. O `PreviewDasTelasDeAcesso::recuperacaoNaOrganizacao()` devolve `global()` e a organização como `null`. A P-31 está no `00`, e as docs pt/en e o CHANGELOG falam em "Login e Cadastro" na organização. CT-79, CT-94 e CT-95 estão verdes (Tenancy 82/82). Não reproduzi o "CT-79 cai sem a correção", porque exigiria editar o código |
| QA-28 | **aberto, como pendência de release** | A linha do QA-18 foi corrigida e a rodada `d1`..`d4` foi registrada. Falta a rodada dos 4 cenários sobre o commit final. A rodada `e1`..`e4` aponta para `7ca671df`, mas esse commit já foi superado: depois dele mudaram 5 arquivos de `app`/`resources` (QA-35) |
| QA-29 | fechado | O trecho da RQ-27 inclui "possibilidade de usar html". A Q12 está registrada com o porquê, e a P-05 está marcada *(alterado)* |
| QA-30 | fechado para CT-57..CT-79 | Pela contagem com `awk` sobre `04:1461-1846`, os 23 cenários têm `Então`. Há M73..M101 no `04` e M102..M108 no `05`, e o `fw-adversario-ct` rodou (ADV-C1..C32). **Os acréscimos dessa revisão repetiram a mesma classe de defeito: QA-34** |
| QA-31 | fechado | `grep "abas\|three tabs"` não encontra mais nada sobre o editor nas docs, no CHANGELOG, no `01` e no `02`. A Superfície Livewire do `02` tem `destaqueDoPainelDeMarca()` e `gradienteDoPainelDeMarca()`, e o docblock da view foi corrigido |
| QA-32 | parcial | Foram feitos os despachos 19..26, a degradação e o Estado. **Não foram feitos** a tabela `## Testes` e a contagem da seção 11 (QA-35) |
| QA-33 | parcial, aceito como débito | O glossário ganhou "Painel de marca", "Tela própria" e "Família de tela". A P-27 continua dizendo "rodapé da tela de login", sem o termo "Recado do login". A Q12 faz a ponte entre os dois termos |

## Achados

### QA-34 — CT-80..CT-96 da revisão adversarial não têm cenário: o oráculo está só no teste · Major · destino 3

- **Dimensão**: K / L1
- **Relacionado a**: R26–R29, RQ-25..RQ-33, P-25..P-31, ADV-C1..C32
- **Esperado**: cada CT do `04` tem um cenário com `Dado`/`Quando`/`Então` e `Exemplos` derivado do requisito. O mutante previsto aponta o CT que o mata. O QA-30 do ciclo 1 cobrou exatamente essa classe, e a ação dele foi aceita.
- **Observado**: os 17 CT novos (CT-80..CT-96) existem só em dois lugares: uma linha no `## Índice de Cenários` (`04:1137-1153`) e uma célula na tabela de fechamento da revisão adversarial (`04:1855-1877`). Nenhum tem cenário Gherkin. O oráculo de R27 está só em `tests/Kit/AparenciaDasTelasPorTelaTest.php:'[CT-80]':423`..`[CT-92]`. R27 tem Impacto 3: é Markdown hostil numa tela pública, e o CT-81 cobre caixa mista, `data:`, `vbscript:` e formulário cru. O mesmo vale para CT-93..96 em `tests/Tenancy/AparenciaDasTelasDeAcessoTenancyTest.php:'[CT-93]':797`..`[CT-96]`. A coluna de mutantes do índice também não fecha. O CT-80 aponta o M86, e o M86 (`04:1811`) tem o CT-57 como matador. O mesmo acontece com M74, M76, M78, M80, M84, M85 e M87..M99: nenhuma linha `| M` cita CT-80..96 como matador. O cabeçalho diz "Cenários: 79" e "Revisão adversarial … feita". Os dois estão certos pela regra do grep, mas escondem os 17.
- **Repro**:
  1. `grep -nE 'CT-(8[0-9]|9[0-6])\b' 04-casos-de-teste.md` → só as linhas 1137–1153 e 1855–1877
  2. `grep -cE '^[[:space:]]*(Cenário|Esquema do Cenário): \[CT-[0-9]+\]' 04-casos-de-teste.md` → 79. O último cenário é o CT-79 (`04:1767`)
  3. `grep -nE '^\| *M(8[6-9]|9[0-9])' 04-casos-de-teste.md` → a coluna matador cita CT-57, 65, 66, 67, 68, 69, 70, 71 e 72, nunca CT-80..96
- **Evidência**: `ids-ct.sh` exit 0. O ID existe no índice, então o script não pega a falta de cenário. `grep -rn "Cenários: 79" {wiki}` → só `04:5`
- **Destino**: 3
- **Ação exigida**: invocar a `feature-test-design` para dar cenário completo, com `Então` e `Exemplos` em tabela, a CT-80..CT-96. Antes de qualquer outra coisa, R27: CT-81, CT-90, CT-91 e CT-92. Ligar cada CT a um mutante cujo matador seja ele mesmo, ou corrigir a coluna do índice. Recalcular o cabeçalho pelos `grep -cE`. A lacuna se repetiu de um ciclo para o outro: o fechamento de achado adversarial virou teste sem passar pelo `04`. Por isso cabe propor, na `feature-test-design`, uma linha de checklist: "achado adversarial fechado por CT novo exige cenário no `04` antes do teste".

### QA-35 — `03`: a Verificação Final aponta como "final" um commit superado, e Testes e seção 11 seguem defasados · Minor · destino 1

- **Dimensão**: L6 (resíduo do QA-32 e do QA-28)
- **Observado**:
  - `03:208` diz "Cenários 1 a 4 sobre o commit final dos Adendos 2 e 3 (`7ca671df` …) — em execução", e o despacho #25 diz o mesmo. Mas o `325eca85` mudou `app/`/`resources/` depois: `git diff --name-only 7ca671df..HEAD -- app resources public` → `TenantForm.php`, `EditorDeAparenciaDasTelas.php`, `PreviewDasTelasDeAcesso.php`, `AparenciaDasTelasDeAcesso.php` e `painel-de-marca.blade.php`. Uma rodada sobre `7ca671df` não prova o código de HEAD.
  - A última linha de suíte (`03:209`) é sobre `ca469486`, com Kit 5166. Os números de `694ec0d8` que a sessão mediu (Kit 5198, Tenancy 744, Browser 235) não estão no `03`.
  - `## Testes` (`03:125-133`) continua sem `AparenciaDasTelasPorTelaTest`/`AdendoTest` e para no CT-B08. A seção 11 não tem a contagem por arquivo.
  - Cosmético: o despacho #26 aparece antes do #25, e o Estado (`03:3`) ainda diz "ciclo 1 … em tratamento".
- **Repro**: `sed -n 208,209p 03-progresso.md`; `sed -n 125,133p 03-progresso.md`; `git diff --name-only 7ca671df..HEAD -- app resources public`
- **Evidência**: `grep -rn "5166\|7ca671df" {wiki}` → `03:208`, `03:209` e `03:278` (no `06` do ciclo 1, as linhas são históricas)
- **Ação exigida**: apontar a rodada dos cenários para o commit final de verdade (o HEAD depois deste ciclo) e corrigir as linhas 208, 278 e o despacho #25. Registrar na Verificação Final a saída das suítes sobre o HEAD. Atualizar `## Testes` (PorTela 87, Tenancy 82, Adendo, Browser até CT-B18) e a seção 11. Ordenar os despachos.

## Matriz de Rastreabilidade

`rastreabilidade.sh` exit 0. `indice.sh --check` não se aplica (não há `07-tickets/`). Lacunas:

| RQ/P | Cláusula ou premissa | Passo PRD | CT | CT-B | Código | Resultado | Veredito |
|------|----------------------|-----------|----|------|--------|-----------|----------|
| RQ-25/26, RQ-27, RQ-28/29, P-31 | por tela; Markdown seguro; gradiente; organização | 11 | CT-80..96 (sem cenário) | CT-B18 | `textoComoHtml`, `normalizar`, preview | comportamento correto e testes verdes; o oráculo está só no teste | ⚠️ QA-34 |
| RQ-07 / RQ-08 / RQ-17 | não quebrar quem usa o kit; cenários | — (processo) | CT-21, CT-27 `@obsoleto` | — | 5 arquivos mudados depois de `7ca671df` | sem rodada sobre o commit final | ⏳ QA-28 (pendência de release), QA-35 |

## Dimensões

| # | Dimensão | Status | Observação |
|---|----------|--------|------------|
| A | Cobertura do requisito | ⚠️ | `rastreabilidade.sh` exit 0; sem `07-tickets/`. Conferi o diff por passo `7ca671df..HEAD`: a P-31 está implementada no editor e no preview, e o ADV-C8 (`$propria === []` segue o login) está no normalizador e no CT-83. No app servido, o login fica à esquerda (`media-left`) e a recuperação à direita (`media-right`). Pendência: QA-28 |
| B | Fronteiras e dados | ✅ | CT-81 (12 linhas hostis), CT-86 (CSS anexado, 8 dígitos), CT-87 e CT-90 (160/161 visíveis, 4096/4097 bytes, multibyte) rodaram verdes aqui. A sondagem do filtro do ciclo 1 continua válida, porque o `textoComoHtml` não mudou depois de `7ca671df` |
| C | Matriz de permissão | ✅ | Nenhuma rota, policy ou action nova. O preview da organização só troca o que mostra depois de `rascunhoAutorizado()`, então a autorização vem antes. CT-79 e CT-95 cobrem os dois formulários (criar e editar) |
| D | Observabilidade | ✅ | Nenhum ponto de log novo no ciclo. Linha real em `storage/logs/autenticacao-2026-11-14.log`: `[AparenciaDasTelasDeAcesso@normalizar] … {"origem":"x:cadastro","motivo":"layout"}`, sem PII. A recusa do preview loga `user_id`, `motivo` e `escopo` |
| E | Performance | ✅ | Por leitura. No escopo da organização, o `recuperacaoNaOrganizacao()` chama `rascunhoAutorizado()` (cache + `Tenant::find` + `Gate`) mais de uma vez por render. A contagem é fixa e não cresce com N, numa página só do admin, por isso não é achado |
| F | UX de erro | ✅ | Os erros de cor do gradiente aparecem no campo (CT-87). O editor da organização não explica por que não há "Esqueci a senha". A doc pt/en explica, e por isso fica sem achado |
| G | Tema e cor | ⏭️ não verificada | `dark-mode.sh --mecanismo` exit 1 (Filament, classe `dark`). O nível 1 sobre `--diff-filter=AM fe9ea589...HEAD` (app/resources/public) deu exit 0. O CT-B18 (gradiente claro → superfície `rgba(17,24,39,.9)`, texto branco, link sublinhado) está verde. O nível 3 não rodou, por falta de MCP |
| H | Acessibilidade | ⏭️ não verificada | Nenhum CT-B da feature chama `assertNoAccessibilityIssues` (grep = 0) e o MCP está indisponível |
| I | Segurança da superfície nova | ✅ | O step 9 cobre até `ca469486` (`## Revisão do Diff — Adendos 2 e 3`: 14 achados, 0 rejeitados), e as quatro linhas *eixo 9* não rodaram de novo. Por cima do step 9, olhei o `325eca85`, que nenhum revisor viu. O preview da recuperação na organização lê só a padrão **salva**, sem o rascunho de ninguém (ADV-C31). `organizacaoDaAparencia()` devolve `null` nesse caso, então nem cor nem logo da organização vão ao HTML. O editor da organização some com a seção por schema, não por `visible` no cliente. O normalizador com `[]` não abre caminho novo |
| J | Regressão adjacente | ⏭️ não verificada | Rodei só os arquivos da feature e o vizinho do editor (`ConfiguracoesSecoesColapsaveisTest` 32/32). Não rodei `--tia` nem as suítes inteiras, por restrição de memória. Os números da sessão ficam como alegação, com plausibilidade conferida (+32 = PorTela). Os cenários 1–4 sobre o commit final estão pendentes (QA-28) |
| K | Adequação da suíte | ⏭️ não verificada | K1 `k1-oraculo-fraco.sh` sobre os testes de `7ca671df...HEAD` deu exit 1 com 5 candidatas, todas rejeitadas: CT-88 só com negações, mas os mesmos textos têm controle positivo no CT-71 e no CT-89, e `htmlDe` afirma 200; CT-75, CT-07 e CT-11 afirmam pelo helper `afirmarAparenciaNoHtml`; CT-33 já foi julgada. K2 não rodou. A revisão adversarial foi feita, mas os acréscimos dela não têm cenário (QA-34) |
| L | Consistência documental | ❌ | L1 `ids-ct.sh {wiki} 'tests/**/AparenciaDasTelas*.php'` exit 0, mas há o QA-34. L2 `citacoes.sh` exit 0. L3: o passo 11 e a ADR-05 foram reconciliados com marca. L4 `conformidade-rules.sh {wiki} main` exit 0. L5: pt × en coerentes ("Login e Cadastro" na organização) e CHANGELOG alinhado. L6 `checkbox-sem-evidencia.sh` exit 0, mas há o QA-35. L7: resíduo do QA-33, aceito. L8 `roteiro-manual.sh` exit 0; n/a, porque não há `08` e a decisão é `não` |

## Débitos Aceitos

- Nenhum neste ciclo, porque há Major aberto. Continuam no `03`, sem contestação: Q1, Q2, Q12 (recomendação implementada), alternador fora do teclado, organização fora do navegador, positivo em redefinir/verificar/2FA, preview sem a pilha do painel, ADV-A15, ADV-C23 (largura com o widget do reCAPTCHA) e ADV-C30 (dois eventos no "Quando").
- Resíduo do QA-33: a P-27 sem o termo "Recado do login". A Q12 faz a ponte.

## Suspeitas Não Confirmadas

- O CT-81 não tem controle positivo de que o bloco da frase ou do apoio existe. Se o texto hostil fosse descartado inteiro, o teste passaria no vazio. Na prática, o CT-80 ancora a classe da frase e o CT-57 a do apoio. Não reproduzi a vacuidade, porque exigiria alterar código.
- A imagem trocada deixa o arquivo antigo em `telas-de-acesso/` (ciclo 1). Não reproduzi, porque exigiria gravar no app.

## Não Verificado

- G, nível 3, e H, axe, teclado e foco ao vivo: Playwright MCP indisponível, e nenhum CT-B da feature chama `assertNoAccessibilityIssues`.
- J: as suítes inteiras e o `--tia` não rodaram, por restrição de memória do orquestrador.
- J / RQ-08 / RQ-17: os cenários 1–4 sobre o commit final não rodaram. A rodada `e1`..`e4` aponta para `7ca671df`, que já foi superado (QA-35). Pendência de release, e bloqueia o merge.
- K2, `--mutate`: as ferramentas existem (`php -m` → pcov e Xdebug; `vendor/pestphp/pest-plugin-mutate`). Não rodou por três motivos: a restrição de memória; o plugin é sabidamente pouco confiável no Windows (filtro truncado acima de ~8 KB e cache mostram mortos falsos); e a amostragem manual exigiria editar a árvore, o que este gate não faz. A falsificabilidade alegada no `03` (CT-79 e CT-83 caem sem a correção) ficou como alegação.
- B e I ao vivo: Markdown hostil e aparência por organização gravados no app servido exigiriam escrever configuração. A cobertura vem dos CT rodados neste ciclo.
- Cadastro próprio, login social e anti-robô no app servido: `/cadastro`, `/app/register` e `/login` respondem 302 (cadastro e login unificado desligados localmente). A cobertura vem de CT-71, CT-72, CT-88, CT-89 e CT-B17.
- RQ-12: os nomes de referência estão mascarados no `00`. Conferi só o oráculo genérico: o diff `7ca671df..HEAD` não traz host novo fora do próprio kit.
