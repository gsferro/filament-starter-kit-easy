# Relatório de QA — email-com-mais: Permitir ou bloquear o "+" no e-mail do cadastro

> Requisito: `00-requisito.md` · Plano: `01-plano-acao.md`
> Perfil de esforço: padrão. Natureza `nova`. Superfície de UI presente, sem JS próprio: toggle e erros de campo do Filament. Domínio julgado comum: a feature acrescenta uma regra de validação, não mexe em dinheiro, não cria fluxo novo de PII, não muda autorização e não muda o contrato do provedor social.
> Natureza da wiki: nova · Toca infra compartilhada: sim → `config/kit.php`, settings, `RegistroAberto`, `Convite`, os dois `UserResource`, os formulários de convite, login social, perfil do Breezy · Regressão: sim
> Independência: sub-agente fw-qa-gate/claude-opus-5-5, contexto novo sem a conversa (ciclo 2: comprometida — recebeu resumo do tratamento pós-ciclo 1)
> Cobertura: ciclo 1, 5 de 12; ciclo 2, 6 de 12 dimensões verificadas ou provadas não aplicáveis · teto: APROVADO COM DÉBITO

## Veredito — Ciclo 1

**REPROVADO → especificação**

- Blocker: 0 · Major: 2 · Minor: 5 · Cosmético: 2
- Não verificadas: B, E, F, G, H, J, K. As causas estão em *Não Verificado*.
- `RQ` abertas: nenhuma. Q1–Q7 estão abertas com a recomendação implementada; ver QA-01.
- Ambiente: app em `http://127.0.0.1:8091` e `:8092` (respondem; registro fechado, 302 → login) · Pest com `pest-plugin-mutate`, PCOV e Xdebug presentes, sem `--agent` · MCP: Boost falhou ao conectar (timeout), Playwright indisponível

## Achados

### QA-01 — Recortes que dependem de perguntas abertas ao solicitante foram implementados · Major · destino 1
- **Dimensão**: A (auditoria do requisito)
- **Relacionado a**: Q1, Q2, Q3, Q4, Q7 → P-01, P-02, P-03, P-06; passos 1, 2, 3, 4, 6 e 7 do PRD
- **Esperado**: o step 4 da `feature-wiki` manda "bloquear somente o recorte dependente" de uma pergunta da raia requisito, e diz que a premissa de comportamento leva a ➡️ pela opção que falha fechado. O texto original diz só "ao se cadastrar … por convite, register ou crud direto".
- **Observado**: na tabela de perguntas do `00`, Q1–Q7 estão todas como `aberta — implementada a recomendação`. Os recortes que dependem delas foram implementados:
  - a troca de e-mail no perfil (Q2 → passo 7, `app/Livewire/InformacoesPessoais.php`, nos três painéis), que estende o pedido para além de "cadastrar";
  - o aceite de convite com "+" já emitido, com a opção desligada (Q4 → P-06), que relaxa o "bloqueia o uso" da RQ-03 na via convite;
  - o padrão permitido (Q1) e a opção sem nível por organização (Q3).

  O `01` reconhece isso no passo 7: "Feito por último, num commit próprio (Q2 aberta)".
- **Repro**:
  1. `grep -n "aberta — implementada" wikis/specs/feat/email-com-mais/email-com-mais/00-requisito.md` devolve 7 linhas (Q1–Q7).
  2. `git log --format='%h %s' main..HEAD | grep perfil` devolve `120d89bc … troca de e-mail no perfil segue a opção do "+"`.
- **Evidência**: `00-requisito.md:29-35`; `01-plano-acao.md:90`; `.ai/skills/feature-wiki/SKILL.md:458-459`
- **Destino**: 1 especificação
- **Ação exigida**: levar Q1–Q7 ao solicitante e registrar as respostas no `00`. Para cada resposta que divergir da recomendação: `P-nn` revista → CT no `04` → correção. Até lá, o recorte que depende da resposta não entra no PR.

### QA-02 — O PRD descreve um mecanismo que o código não tem · Major · destino 1
- **Dimensão**: L3
- **Relacionado a**: passos 1 e 4 do PRD, Decisões de Desenho, RQ-01, P-01, P-06
- **Esperado**: nenhuma afirmação do `01` contradiz o código; um desvio corrige a fonte com `*(alterado em …)*`.
- **Observado**:
  - (a) `01:51` e `01:105` definem a chave como `filter_var(env(…), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? true` e dizem que a vazia permite. Essa fórmula devolve `false` para a string vazia, ou seja, bloqueia. O código (`config/kit.php`, chave `email.permitir_mais`) usa outra expressão, com `in_array(…, [null, ''])` antes do `filter_var`.
  - (b) `01:108` lista "Guardas no servidor … (`registrar()`, `aceitar()`)". O `aceitar()` não tem guarda: `01:72` diz que ele "não muda", e o diff confirma.
  - (c) `01:51` lista os métodos de `EmailComMais` sem `aceitaNoConvite()` e sem `regraDeConvite()`, que existem e aparecem no `03:8`.
  - Não há marca `alterado em` nem seção `## Desvios do Plano` em nenhum arquivo da wiki.
- **Repro**:
  1. `php -r 'var_dump(filter_var("", FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE));'` → `bool(false)`.
  2. `grep -rn "FILTER_NULL_ON_FAILURE\|aceitar()" wikis/specs/feat/email-com-mais/email-com-mais/` → `01:51`, `01:105`, `01:108` (e `01:38`, `01:72`, `04:80`, `04:453`, que estão corretos).
- **Evidência**: o `grep` acima; `git diff main...HEAD -- app/Models/Convite.php` não toca o `aceitar()`.
- **Destino**: 1
- **Ação exigida**: corrigir `01:51`, `01:105` e `01:108` com a marca de data, e acrescentar os dois métodos à lista do passo 1.

### QA-03 — Citações de `Convite.php` desatualizadas nas docs pt e en · Minor · destino 1
- **Dimensão**: L2/L5
- **Esperado**: a linha citada contém o símbolo.
- **Observado**: `docs/pt/autenticacao/convites.md:104-105,152` e `docs/en/autenticacao/convites.md:108-109,161` citam:

  | Símbolo | Linha citada | Linha real |
  |---|---|---|
  | `:lembrar` | 207 | 209 |
  | `:aceitar` | 619 | 638 |
  | `:aceitarComoUsuarioExistente` | 687 | 706 |
  | `:recusar` | 752 | 771 |
  | `:'Aceito'` | 603 | 622 |
  | `:'Recusado'` | 604 | 623 |

  O commit `0c8ab65b` corrigiu `enviar` e `situacao`, mas deixou os vizinhos.
- **Repro**: `grep -n "function lembrar\|function aceitar\|function recusar\|'Aceito'\|'Recusado'" app/Models/Convite.php`
- **Destino**: 1 · **Ação exigida**: corrigir as seis citações nos dois idiomas.

### QA-04 — A tabela de abas das docs omite a seção nova · Minor · destino 1
- **Dimensão**: L5
- **Observado**: a coluna "Seções" da linha **Registro** em `docs/pt/recursos/configuracoes-do-kit.md:21` e `docs/en/…:21` lista só "Cadastro sem convite; E-mail verificado". A aba tem uma terceira seção, `secao('registro', 'email-com-mais', 'E-mail com "+"', …)`, em `app/Filament/Admin/Pages/ConfiguracoesDoKit.php` (`abaRegistro()`, linha 745 no ciclo 1).
- **Repro**: `grep -n "secao('registro'" app/Filament/Admin/Pages/ConfiguracoesDoKit.php` devolve 3 seções; a doc mostra 2.
- **Destino**: 1 · **Ação exigida**: acrescentar "E-mail com "+"" à coluna, em pt e en.

### QA-05 — O CHANGELOG descreve errado a falta da migration · Minor · destino 1
- **Dimensão**: L5
- **Esperado**: o "Atualizando" diz o que acontece sem `migrate`.
- **Observado**: `CHANGELOG.md:36` diz "Sem ela, a tela de configurações não tem o campo e a opção segue o .env". Na prática, sem a linha no settings, carregar `ConfiguracoesDoKit` lança `MissingSettings`. O boot então descarta o banco inteiro e volta ao `.env` para TODAS as opções, não só esta (`KitServiceProvider::configureSettingsDoKit`; o docblock de `app/Settings/ConfiguracoesDoKit.php` (linhas 67 e 68 no ciclo 1) diz o mesmo).
- **Repro**: no banco SQLite local do `.env` (sem as migrations de settings recentes), `php artisan tinker --execute 'app(App\Settings\ConfiguracoesDoKit::class);'` → `MissingSettings … missing: tamanho_da_fonte, modo_do_menu, aparencia_por_usuario, email_permitir_mais`.
- **Destino**: 1 · **Ação exigida**: reescrever a frase no `CHANGELOG.md` e conferir a mesma frase em `wikis/atualizacoes/v0.56.0.md` e em `wikis/agentes-e-skills.md`.

### QA-06 — Linhas do dataset do CT-01 sem Exemplo no Gherkin, citando uma resposta que não existe · Minor · destino 3
- **Dimensão**: L1
- **Observado**: `tests/Kit/EmailComMaisTest.php` (dataset do `[CT-01]`, linhas 151 e 152 no ciclo 1) tem as linhas `'off (Q?2 respondida)'` e `'no (Q?2 respondida)'`. Os Exemplos do CT-01 (`04:152-159`) não têm essas linhas, e `04:701` diz que elas "entram com a resposta". A Q?2 virou Q6 no `00`, que continua `aberta`. A docstring da chave e as docs anunciam `off` e `no` como bloqueio.
- **Repro**: `sed -n 144,153p tests/Kit/EmailComMaisTest.php` × `sed -n 152,159p wikis/specs/feat/email-com-mais/email-com-mais/04-casos-de-teste.md`
- **Destino**: 3 · **Ação exigida**: o Exemplo nasce no `04` pela `feature-test-design`, depois da resposta à Q6 (ver QA-01). Até lá, o rótulo "respondida" sai.

### QA-07 — Contagem e índice do `04` desatualizados · Cosmético · destino 1
- **Dimensão**: L1
- **Observado**: o cabeçalho diz "Cenários: 27" (`04:18`). O `grep -cE '^[[:space:]]*(Cenário|Esquema do Cenário): \[CT-[0-9]+\]'` devolve **28**. O CT-28 não está no `## Índice de Cenários` nem na linha R6 do `## Mapa de Regras`; só aparece no Adendo. O valor 27 também está em `03:99`, mas lá é registro histórico do despacho e fica como está.
- **Destino**: 1 · **Ação exigida**: recalcular o cabeçalho pelos `grep -c` do template e incluir o CT-28 no índice e no mapa.

### QA-08 — O `03` declara o step 10 feito sem `## Verificação Final` · Minor · destino 1
- **Dimensão**: L6
- **Observado**:
  - `03:3` diz "em revisão … (step 10 feito)", mas o `03` não tem a seção `## Verificação Final` com as saídas coladas dos scripts mecânicos. O `checkbox-sem-evidencia.sh` sai com exit 0 porque não há seção para conferir.
  - Falta a linha de decisão `Roteiro manual:`.
  - O `01` não tem `## Impacto em Features Existentes`, embora declare `Toca infra compartilhada?: sim`. Sem essa seção, a J não tem com o que comparar.
- **Repro**: `grep -n "Verificação Final\|Roteiro manual" wikis/specs/feat/email-com-mais/email-com-mais/03-progresso.md` → vazio; `grep -n "Impacto em Features" …/01-plano-acao.md` → vazio.
- **Destino**: 1 · **Ação exigida**: criar a Verificação Final com a saída de cada script, registrar a decisão de roteiro e escrever a seção de impacto do `01`.

### QA-09 — Comentário contraditório no convite em massa · Cosmético · destino 2
- **Dimensão**: L3
- **Observado**: `app/Models/Convite.php` (bloco do "+", logo antes de `$comMais`) diz "com a mesma ressalva do `whereIn` sobre a coluna crua". As duas linhas seguintes usam `lower(email)` e dizem "ao contrário do `whereIn` dos convites".
- **Destino**: 2 · **Ação exigida**: alinhar o comentário ao código.

## Matriz de Rastreabilidade

O `rastreabilidade.sh` saiu com exit 0. Todo `RQ`/`P-nn` tem passo, CT e código no diff (os passos 1–8 foram conferidos contra `git diff --name-only main...HEAD`). A lacuna está na origem das premissas, não nas células:

| RQ/P | Cláusula ou premissa | Passo PRD | CT | Código | Resultado | Veredito |
|---|---|---|---|---|---|---|
| P-01 | nasce permitindo (Q1 aberta) | 1, 2 | CT-01..03 | `config/kit.php`, `EmailComMais::permitido` | ✅ | ⏳ premissa sem resposta — QA-01 |
| P-02 | opção da instalação (Q3 aberta) | 1, 2 | CT-04..06 | settings, página | ✅ | ⏳ QA-01 |
| P-03 | inclui o perfil (Q2 aberta) | 7 | CT-24..26 | `InformacoesPessoais` | ✅ | ⏳ extensão do pedido — QA-01 |
| P-06 | convite emitido vale (Q4 e Q7 abertas) | 3, 4, 6 | CT-16, CT-17 | `aceitar()` sem mudança | ✅ | ⏳ QA-01 |

## Dimensões

| # | Dimensão | Status | Observação |
|---|---|---|---|
| A | Cobertura do requisito | ❌ | QA-01. `rastreabilidade.sh` exit 0; `git diff` por passo sem arquivo faltando; sem `07-tickets/`. A ambiguidade de "habilitada" no texto original (a proteção ou o "+") foi auditada e não gera achado: os dois estados existem e o padrão segue "proteção extra que pode ser habilitada". |
| B | Fronteiras e dados | ⏭️ | Parte estática feita: `temMais` usa o último "@", `trim`, caixa, `+` no início, no fim, repetido e depois do "@" (datasets do CT-07 e do CT-15). Sondagem dinâmica não feita: este Pest não tem `--agent`. |
| C | Matriz de permissão | ✅ | Nenhuma ação, rota ou permissão nova. A gravação da opção é guardada pelos testes de autorização existentes em `ConfiguracoesDoKitTelaTest.php` (linhas 149, 168, 189 e 222 no ciclo 1). Convite: CT-11 (`/admin`) e CT-12 (`/app`, `admin_app`). CRUD: CT-18–20. Perfil nos três painéis: CT-25. |
| D | Observabilidade | ✅ | Log real: `[RegistroAberto@registrar] Registro aberto recusado \| motivo: email_com_mais {"motivo":"email_com_mais","ip":"127.0.0.1"}` em `autenticacao-2026-11-19.log`. Canal, prefixo e context corretos, sem PII. O log social usa `Str::mask`; o lote mascara o endereço. |
| E | Performance | ⏭️ | Leitura do código: uma query por validação de convite e uma por lote (`whereIn lower(email)`), sem N+1. Contagem mecânica não feita (sem `--agent`). |
| F | UX de erro | ⏭️ | A mensagem diz o quê e como corrigir, em pt-BR (CT-08, CT-10, CT-21, CT-28). A tela servida não foi observada (sem navegador). |
| G | Tema e cor | ⏭️ | Fora do perfil padrão. Rodado mesmo assim: `dark-mode.sh --mecanismo` exit 1 (Filament, classe `dark`); nível 1 nos arquivos do diff exit 0. |
| H | Acessibilidade | ⏭️ | Fora do perfil padrão. |
| I | Segurança da superfície nova | ✅ | Coberto pelo step 9 (`## Revisão do Diff (step 9)` do `03`: 17 linhas, 11 corrigidas e 6 mantidas, fora de escopo ou débito). Além do step 9: nenhuma rota nova; nenhum `fillable` mudou; `DB::raw`/`whereRaw` com bind; nenhum dado sensível em resposta. A exceção de conta existente da regra do convite não cria oráculo novo de enumeração: o `->unique()` do e-mail no `UserResource` do `/app` já responde existência global. |
| J | Regressão adjacente | ⏭️ | `--parallel --tia` não rodado (restrição do ambiente: um processo de teste por vez). Rodados por arquivo, em série e todos verdes: 125 (Convite, RegistroAberto, CadastroSocialPorConvite, HelpersDeTeste, KitInfo), 453 (ConviteEmMassa, ConviteUsuarioExistente, ConfiguracoesDoKitTela, ConfiguracoesDoKit, LoginSocialGoogle, LoginSocialPorPainel, LoginUnificado, DefinirSenhaPorEmail, Paineis, CadastroNoPainelDoPapel, ExclusaoDePapelComConvite, VinculoDeProvedorSocial), 202 (ConfiguracoesSecoesColapsaveis, ConfiguracoesDoKitDocumentacao, TextoDoEnv, KitArte, LinkDoPainelSemTenancy, Tenancy ConviteEmMassa, LoginSocialGoogle, CadastroSocialPorOrganizacao, PermissoesDeTelas). O `01` não tem a seção de impacto para comparar (QA-08). |
| K | Adequação da suíte | ⏭️ | K1 `k1-oraculo-fraco.sh` exit 1, 2 linhas rejeitadas: `ConviteTest.php` (linha 252 no ciclo 1) e `AdminDaOrganizacaoTest.php` (linha 554 no ciclo 1) já existiam na `main`. Testes da feature lidos: os oráculos afirmam efeito e mensagem. K2 fora do perfil. Revisão adversarial do `04`: "não exigida pelo perfil". |
| L | Consistência documental | ⚠️ | QA-02 a QA-08. L1 `ids-ct.sh` (três arquivos da feature) exit 0, com a leitura de dataset e cabeçalho em QA-06 e QA-07; L2 `citacoes.sh` exit 0 na wiki, citações das docs em QA-03; L4 `conformidade-rules.sh main` exit 0, linhas "aplicada" conferidas, nenhuma rule violada (a divergência declarada com `config.md` é n.a.: a chave não abre superfície pública); L6 `checkbox-sem-evidencia.sh` exit 0 (vazio, QA-08); L7 sem termo novo divergente do glossário; L8 `roteiro-manual.sh` exit 0, n/a — sem 08. |

**L6 — números reproduzidos**:

| Alegação do `03` | Reprodução |
|---|---|
| `EmailComMaisTest` 70/70 | 70 passed, 299 assertions |
| `RegistroAbertoTenancyTest` 17/17 | 17 |
| `AdminDaOrganizacaoTest` 38/38 | 38 |
| `ConviteTest` 49/49 | 49 |
| `RegistroAbertoTest` + `CadastroSocialPorConviteTest` + `HelpersDeTesteTest` (35 + 5 + 1) | 41, por diferença no lote de 125 |
| `KitInfoTest` 77 propriedades | `grep -c` das propriedades do settings = 77 |

"Seis mutantes mortos" não é reproduzível sem alterar a árvore; ver *Não Verificado*.

## Débitos Aceitos

- Nenhum aceito por este gate. RD-01 (convite de conta existente aceito depois de a conta trocar de e-mail) continua como débito do `03`.

## Suspeitas Não Confirmadas

- Nenhuma.

## Não Verificado

- B, E e F, parte dinâmica — motivo: este Pest não expõe `--agent`, o Playwright MCP está indisponível e o Boost MCP falhou ao conectar (timeout). A opção não foi desligada no app servido porque isso grava estado num ambiente não confirmado como descartável.
- G e H — motivo: fora do perfil padrão.
- J, passo 1 (`--parallel --tia`) — motivo: restrição do ambiente (um processo de teste por vez; a suíte Browser pode estar rodando).
- K2 (`--mutate`) — motivo: fora do perfil padrão. A alegação "seis mutantes, um por via, todos mortos" (`03:35`) não tem comando que a reproduza e não foi certificada.

## Ciclo 2

> Independência (ciclo 2): sub-agente fw-qa-gate/claude-opus-5-5, contexto novo sem a conversa — **comprometida — recebeu resumo do tratamento pós-ciclo 1** (Q4 respondida divergindo, Reenviar oculto, lembrete pula, BP-01..BP-13, roteiro completado). O mesmo conteúdo está no `03`, que o gate lê por desenho; o resumo não foi usado como evidência.
> Cobertura (ciclo 2): 6 de 12 dimensões verificadas ou provadas não aplicáveis (A, C, D, I, J, L) · teto: APROVADO COM DÉBITO
> Alvo: HEAD `c6ba978b`, árvore limpa, base `main` (62 arquivos, +3379/-219)

### Veredito — Ciclo 2

**REPROVADO → especificação**

- Blocker: 0 · Major: 1 · Minor: 5 · Cosmético: 2
- Não verificadas: B, E, F, G, H, K. As causas estão em *Não Verificado (ciclo 2)*.
- `RQ` abertas: nenhuma. Q5 e Q6 seguem `aberta — implementada a recomendação` (QA-11).
- Teto de ciclos: o perfil padrão permite até 2 ciclos e este é o 2º. Com o Major aberto, o loop para aqui: o próximo passo é escalar ao usuário, não abrir um ciclo 3.
- Ambiente:
  - app em `http://127.0.0.1:8091` (200; `:8000` não responde);
  - Pest 5 com PCOV e Xdebug, `pest-plugin-mutate` e `--tia`, sem `--agent`;
  - Boost MCP **conectou** neste ciclo: `browser-logs` sem arquivo `browser.log`, `last-error` com um erro alheio à feature (`OnboardingGuiadoInstalacaoTest`);
  - Playwright MCP indisponível.

### Status dos achados do ciclo 1

| QA | Status | Evidência |
|---|---|---|
| QA-01 | **parcialmente fechado** | Q1–Q4 respondidas e Q7 derivada da Q4. O recorte da Q4 foi reimplementado (CT-16, CT-17, CT-29..CT-31: 6/6 verdes no `--filter`). Q5 e Q6 seguem abertas com a recomendação implementada; o resíduo virou o QA-11. |
| QA-02 | fechado | o passo 1 e as `## Decisões de Desenho` do `01` dizem a fórmula real (`in_array(…, [null, ''])` antes do `filter_var`), os dois métodos do convite e a guarda do `aceitar()`. Defasagem nova, em outra seção: QA-10. |
| QA-03 | fechado | as citações de `app/Models/Convite.php` (`lembrar`, `aceitar`) batem com o código. `CitacoesDeCodigoTest` verde no lote de 858. |
| QA-04 | fechado | a linha **Registro** de `docs/pt/recursos/configuracoes-do-kit.md` cita "+" no e-mail do cadastro (o en também). |
| QA-05 | fechado | o `CHANGELOG.md` (`### Atualizando`) diz que, sem o `migrate`, o boot lê TODAS as opções do `.env`. |
| QA-06 | **parcialmente fechado** | o rótulo "respondida" saiu (`'off (Q6)'`, `'no (Q6)'`) e os Exemplos entraram no `04`. A Q6 continua aberta: QA-11. |
| QA-07 | **parcialmente fechado** | Cenários 31, Regras 11 e Sem matador 2 batem com o `grep -c`. CT-29..CT-31 no índice e no mapa. Mutantes previstos não batem: QA-12. |
| QA-08 | fechado | `## Verificação Final`, a linha `Roteiro manual:` e `## Impacto em Features Existentes` existem. O conteúdo da tabela de impacto é o QA-10. |
| QA-09 | fechado | o comentário do lote em `Convite::convidarEmMassa()` diz `lower(email)`, como o código. |

### Achados novos

#### QA-10 — A tabela de impacto do `01` contradiz a P-06 da Q4 · Major · destino 1
- **Dimensão**: L3 (e J: previsto × medido)
- **Relacionado a**: P-06, Q4, Q7; passos 3, 4 e 7 do PRD; CT-16, CT-17, CT-29..CT-31; BP-13
- **Esperado**: nenhuma afirmação do `01` contradiz o código. A BP-13 do `03` declara o `01` corrigido para a P-06.
- **Observado**:
  - (a) `01`, `## Impacto em Features Existentes`, linha *Aceite de convite (tela e social)*: coluna "desligada" = "nada: o convite emitido vale". O código recusa (`RegistroPorConvite::recusarPorMais()`, a guarda de `Convite::aceitar()`, o `EmailComMais::aceita($email)` antes do `criarContaPorConvite()` em `LoginSocialController::retorno()`).
  - (b) A mesma tabela não tem linha para a lista de convites do `/admin` (Reenviar oculto) nem para `kit:convites-lembrar` (pula o convite).
  - (c) Passo 4: "Logs: nenhum novo". Passo 3: só o log do `registrar()`. O diff acrescenta `warning` em `[Convite@aceitar]` e em `[RegistroPorConvite@mount]`.
  - (d) O passo 7 ainda diz "(Q2 aberta)". A Q2 foi respondida em 2026-11-19.
- **Repro**: `grep -rn "convite emitido vale\|Q2 aberta" wikis/specs/feat/email-com-mais/email-com-mais/`; `git diff main...HEAD -- app/Models/Convite.php app/Filament/Pages/Auth/RegistroPorConvite.php | grep "warning("`; `vendor/bin/pest tests/Kit/EmailComMaisTest.php --filter='CT-16|CT-17|CT-29'`.
- **Destino**: 1
- **Ação exigida**: reescrever a linha *Aceite de convite* e acrescentar *Lista de convites (Reenviar)* e *Lembrete automático* (CT-30, CT-31); corrigir "Logs" dos passos 3 e 4 e o "(Q2 aberta)" do passo 7, com `*(alterado em …)*`; registrar na BP-13 que a seção de impacto tinha escapado.

#### QA-11 — Q5 e Q6 continuam abertas com a recomendação implementada · Minor · destino 1
- **Dimensão**: A
- **Relacionado a**: Q5 → P-05; Q6 → P-01; CT-01 (Exemplos `off`/`no`); resíduo de QA-01 e QA-06
- **Observado**: a Q6 nasceu como "Q?2 · raia: desenho" no `04`, mas está na tabela do solicitante do `00` como `aberta`. O `04`, `## Cogitado e cortado`, ainda diz que as linhas `off`/`no` "entram com a resposta", e elas já estão no CT-01. `KIT_EMAIL_PERMITIR_MAIS=nao` (ou `não`) **permite**; só `false`/`0`/`off`/`no` bloqueiam. A Q5 não podia ficar de fora (a P-05 exige uma mensagem): confirmação pendente.
- **Repro**: `grep -n "aberta — implementada" .../00-requisito.md`; `php -r 'foreach (["nao","off","no"," false"] as $v) var_dump(filter_var($v, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? true);'`
- **Destino**: 1
- **Ação exigida**: decidir a Q6 como desenho no `01` e marcá-la decidida no `00`, ou levá-la ao solicitante junto com a Q5; corrigir `## Cogitado e cortado` do `04`.

#### QA-12 — Cabeçalho do `04` diz 58 mutantes previstos; o `grep -c` dá 56 · Cosmético · destino 1
- **Dimensão**: L1
- **Repro**: `grep -cE '^\| M-?[0-9]+' .../04-casos-de-teste.md` → `56`.
- **Ação exigida**: recalcular; conferir a numeração da R7 (M1..M7), que repete IDs de outras regras.

#### QA-13 — O `04` mantém o texto da P-06 antiga fora da R7 · Minor · destino 1
- **Dimensão**: L3/L1
- **Observado**: `## Mapa de Regras` ("Q1 a Q4 estão `aberta…`", "P-06 tem cobertura parcial … reenvio e lembrete (Q?3)"); `## Fronteira com o Plano` ("o aceite passa"); recomendação da Q?3 ("segue valendo"); `## Varredura SFDIPOT`, linhas F e T.
- **Repro**: `grep -n "Q1 a Q4\|segue valendo\|o aceite passa\|convite já emitido (P-06)" .../04-casos-de-teste.md`
- **Ação exigida**: alinhar à P-06 vigente, com a marca de data.

#### QA-14 — O `03` diz que as citações sem símbolo do `06` foram reescritas; não foram · Minor · destino 1
- **Dimensão**: L6
- **Repro**: `bash .ai/skills/feature-wiki/scripts/citacoes.sh wikis/specs/feat/email-com-mais/email-com-mais` → exit 1, seis linhas do ciclo 1 do `06`.
- **Ação exigida**: corrigir a alegação do `03` (declarar a exceção do `06`, gravado verbatim, ou reescrever e registrar); rodar de novo a `## Verificação Final` em HEAD.

#### QA-15 — O login social por convite recusa sem dizer o que fazer · Minor · destino 3 → 2
- **Dimensão**: F
- **Observado**: na tela, `recusarPorMais()` usa `EmailComMais::mensagem()` mais "Peça a quem convidou um convite para o endereço sem o "+"". No login social, `LoginSocialController::retorno()` recusa só com `EmailComMais::mensagem()` ("Use o endereço sem o "+""), que a pessoa convidada não consegue seguir. O CT-17 afirma só `toContain(EmailComMais::mensagem())`.
- **Ação exigida**: estender o Então do CT-17 no `04` pela `feature-test-design`; ver o teste falhar; ajustar a mensagem do ramo de convite do `retorno()`.

#### QA-16 — Docblock de `recusar()` ficou órfão acima de `recusarPorMais()` · Cosmético · destino 2
- **Repro**: `grep -n "function recusarPorMais\|function recusar()" app/Filament/Pages/Auth/RegistroPorConvite.php`
- **Ação exigida**: devolver o docblock a `recusar()`.

#### QA-17 — O CHANGELOG lista uma mudança em "Não muda" e diz CT-01..CT-28 · Minor · destino 1
- **Dimensão**: L5
- **Observado**: o item "Desligada, o convite para conta nova com "+" enviado antes de desligar não se aceita mais…" está sob `### Não muda`; `### Adicionado` diz CT-01..CT-28 (o arquivo vai até CT-31); a linha `Roteiro manual:` do `03` fala em "28 CT".
- **Ação exigida**: mover o item, corrigir para CT-01..CT-31 e o "28 CT" do `03`.

### Dimensões — Ciclo 2

| # | Dimensão | Status | Observação |
|---|---|---|---|
| A | Cobertura do requisito | ⚠️ | QA-11. `rastreabilidade.sh` exit 0. Os passos 3, 4 e 6 estão no diff e fazem o que a P-06 diz. |
| B | Fronteiras e dados | ⏭️ | `EmailComMais::temMais()` sondado estaticamente com `""`, `"+"`, `"a+b@c+d.com"`, `"a@b+c.com"`, `"\"a+b\"@x.com"`, U+FF0B, bordas com espaço, `%2B`, dois "@", 300+ caracteres; coerente com a P-04. Sondagem dinâmica não feita. |
| C | Matriz de permissão | ✅ | Nenhuma permissão nova. O Reenviar oculto é recusado no servidor (`CanBeDisabled::isDisabled()` no `mountAction`). Revogar segue (CT-30). |
| D | Observabilidade | ✅ | `[RegistroPorConvite@mount]`, `[Convite@aceitar]` e `[LoginSocialController@retorno]`: `warning` no `autenticacao`, e-mail por `Str::mask`. Emissão real das duas novas em *Não Verificado*. |
| E | Performance | ⏭️ | `ConvitesTable` faz um `exists()` por linha no `visible()` do Reenviar, só nas linhas com "+" e opção desligada; limitado. Contagem mecânica não feita. |
| F | UX de erro | ⏭️ | QA-15, por leitura. Tela servida não observada. |
| G | Tema e cor | ⏭️ | Fora do perfil. Nível 1 nos arquivos do diff exit 0. |
| H | Acessibilidade | ⏭️ | Fora do perfil; sem Playwright. |
| I | Segurança da superfície nova | ✅ | Eixos do step 9 rodados na superfície da Q4 (`5c990052`): Reenviar oculto = desabilitado no mount; nenhuma propriedade pública Livewire nova; `desviarParaAceite()` é `never`; `recusarPorMais()` leva ao login sem redirect de volta; a guarda do model exclui a conta existente (CT-29). Sem achado. |
| J | Regressão adjacente | ✅ | Vias: 292/292. Docs: 858/858. Feature: 73/73 (318 assertions). Previsto × medido: QA-10 (b). |
| K | Adequação da suíte | ⏭️ | K1 exit 1 em dois testes que já existem na `main`. Revisão adversarial não exigida pelo perfil. A R7 foi reescrita sem a `feature-test-design` (declarado no `04`). K2 abaixo. |
| L | Consistência documental | ⚠️ | QA-10, 12, 13, 14, 17. `ids-ct.sh`, `conformidade-rules.sh`, `checkbox-sem-evidencia.sh`, `roteiro-manual.sh` exit 0; `citacoes.sh` exit 1 (QA-14). |

**K2 (`--mutate`), fora do perfil**: `--path=app/Support/EmailComMais.php --covered-only --no-cache` → 28 mutantes, 26 mortos, 2 sobreviventes (92,86 %), ambos em `EmailComMais::regraDeConvite()`: `UnwrapTrim` (equivalente comprovado: o `aceitaNoConvite()` faz `trim()` antes) e `RemoveStringCast` (equivalente no domínio: campo `required` e `email`). Classes da Q4 não mutadas: `Convite`, `LoginSocialController`, `ConvitesTable`, `KitConvitesLembrar`.

### Débitos Aceitos (ciclo 2)

- Nenhum aceito por este gate.

### Suspeitas Não Confirmadas (ciclo 2)

- Nenhuma.

### Não Verificado (ciclo 2)

- B, E, F, parte dinâmica — sem `--agent`, sem Playwright MCP, e emitir convite no `:8091` grava estado num ambiente não confirmado como descartável.
- D, emissão real de `[RegistroPorConvite@mount]` e `[Convite@aceitar]` — `NullHandler` nos testes; conferidas por leitura.
- G, H — fora do perfil padrão.
- J com `--tia` — grava o grafo em cache; regressão feita por arquivo.
- K2 — só `EmailComMais`; os "cinco mutantes" da seção 9 do `03` seguem sem certificação.
