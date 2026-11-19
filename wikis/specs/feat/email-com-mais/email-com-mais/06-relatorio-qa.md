# Relatório de QA — email-com-mais: Permitir ou bloquear o "+" no e-mail do cadastro

> Requisito: `00-requisito.md` · Plano: `01-plano-acao.md`
> Perfil de esforço: padrão. Natureza `nova`. Superfície de UI presente, sem JS próprio: toggle e erros de campo do Filament. Domínio julgado comum: a feature acrescenta uma regra de validação, não mexe em dinheiro, não cria fluxo novo de PII, não muda autorização e não muda o contrato do provedor social.
> Natureza da wiki: nova · Toca infra compartilhada: sim → `config/kit.php`, settings, `RegistroAberto`, `Convite`, os dois `UserResource`, os formulários de convite, login social, perfil do Breezy · Regressão: sim
> Independência: sub-agente fw-qa-gate/claude-opus-5-5, contexto novo sem a conversa
> Cobertura: 5 de 12 dimensões verificadas ou provadas não aplicáveis · teto: APROVADO COM DÉBITO

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
- **Observado**: a coluna "Seções" da linha **Registro** em `docs/pt/recursos/configuracoes-do-kit.md:21` e `docs/en/…:21` lista só "Cadastro sem convite; E-mail verificado". A aba tem uma terceira seção, `secao('registro', 'email-com-mais', 'E-mail com "+"', …)`, em `app/Filament/Admin/Pages/ConfiguracoesDoKit.php:745`.
- **Repro**: `grep -n "secao('registro'" app/Filament/Admin/Pages/ConfiguracoesDoKit.php` devolve 3 seções; a doc mostra 2.
- **Destino**: 1 · **Ação exigida**: acrescentar "E-mail com "+"" à coluna, em pt e en.

### QA-05 — O CHANGELOG descreve errado a falta da migration · Minor · destino 1
- **Dimensão**: L5
- **Esperado**: o "Atualizando" diz o que acontece sem `migrate`.
- **Observado**: `CHANGELOG.md:36` diz "Sem ela, a tela de configurações não tem o campo e a opção segue o .env". Na prática, sem a linha no settings, carregar `ConfiguracoesDoKit` lança `MissingSettings`. O boot então descarta o banco inteiro e volta ao `.env` para TODAS as opções, não só esta (`KitServiceProvider::configureSettingsDoKit`; o docblock de `app/Settings/ConfiguracoesDoKit.php:67-68` diz o mesmo).
- **Repro**: no banco SQLite local do `.env` (sem as migrations de settings recentes), `php artisan tinker --execute 'app(App\Settings\ConfiguracoesDoKit::class);'` → `MissingSettings … missing: tamanho_da_fonte, modo_do_menu, aparencia_por_usuario, email_permitir_mais`.
- **Destino**: 1 · **Ação exigida**: reescrever a frase no `CHANGELOG.md` e conferir a mesma frase em `wikis/atualizacoes/v0.56.0.md` e em `wikis/agentes-e-skills.md`.

### QA-06 — Linhas do dataset do CT-01 sem Exemplo no Gherkin, citando uma resposta que não existe · Minor · destino 3
- **Dimensão**: L1
- **Observado**: `tests/Kit/EmailComMaisTest.php:151-152` tem as linhas `'off (Q?2 respondida)'` e `'no (Q?2 respondida)'`. Os Exemplos do CT-01 (`04:152-159`) não têm essas linhas, e `04:701` diz que elas "entram com a resposta". A Q?2 virou Q6 no `00`, que continua `aberta`. A docstring da chave e as docs anunciam `off` e `no` como bloqueio.
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
| C | Matriz de permissão | ✅ | Nenhuma ação, rota ou permissão nova. A gravação da opção é guardada pelos testes de autorização existentes em `ConfiguracoesDoKitTelaTest.php:149,168,189,222`. Convite: CT-11 (`/admin`) e CT-12 (`/app`, `admin_app`). CRUD: CT-18–20. Perfil nos três painéis: CT-25. |
| D | Observabilidade | ✅ | Log real: `[RegistroAberto@registrar] Registro aberto recusado \| motivo: email_com_mais {"motivo":"email_com_mais","ip":"127.0.0.1"}` em `autenticacao-2026-11-19.log`. Canal, prefixo e context corretos, sem PII. O log social usa `Str::mask`; o lote mascara o endereço. |
| E | Performance | ⏭️ | Leitura do código: uma query por validação de convite e uma por lote (`whereIn lower(email)`), sem N+1. Contagem mecânica não feita (sem `--agent`). |
| F | UX de erro | ⏭️ | A mensagem diz o quê e como corrigir, em pt-BR (CT-08, CT-10, CT-21, CT-28). A tela servida não foi observada (sem navegador). |
| G | Tema e cor | ⏭️ | Fora do perfil padrão. Rodado mesmo assim: `dark-mode.sh --mecanismo` exit 1 (Filament, classe `dark`); nível 1 nos arquivos do diff exit 0. |
| H | Acessibilidade | ⏭️ | Fora do perfil padrão. |
| I | Segurança da superfície nova | ✅ | Coberto pelo step 9 (`## Revisão do Diff (step 9)` do `03`: 17 linhas, 11 corrigidas e 6 mantidas, fora de escopo ou débito). Além do step 9: nenhuma rota nova; nenhum `fillable` mudou; `DB::raw`/`whereRaw` com bind; nenhum dado sensível em resposta. A exceção de conta existente da regra do convite não cria oráculo novo de enumeração: o `->unique()` do e-mail no `UserResource` do `/app` já responde existência global. |
| J | Regressão adjacente | ⏭️ | `--parallel --tia` não rodado (restrição do ambiente: um processo de teste por vez). Rodados por arquivo, em série e todos verdes: 125 (Convite, RegistroAberto, CadastroSocialPorConvite, HelpersDeTeste, KitInfo), 453 (ConviteEmMassa, ConviteUsuarioExistente, ConfiguracoesDoKitTela, ConfiguracoesDoKit, LoginSocialGoogle, LoginSocialPorPainel, LoginUnificado, DefinirSenhaPorEmail, Paineis, CadastroNoPainelDoPapel, ExclusaoDePapelComConvite, VinculoDeProvedorSocial), 202 (ConfiguracoesSecoesColapsaveis, ConfiguracoesDoKitDocumentacao, TextoDoEnv, KitArte, LinkDoPainelSemTenancy, Tenancy ConviteEmMassa, LoginSocialGoogle, CadastroSocialPorOrganizacao, PermissoesDeTelas). O `01` não tem a seção de impacto para comparar (QA-08). |
| K | Adequação da suíte | ⏭️ | K1 `k1-oraculo-fraco.sh` exit 1, 2 linhas rejeitadas: `ConviteTest.php:252` e `AdminDaOrganizacaoTest.php:554` já existiam na `main`. Testes da feature lidos: os oráculos afirmam efeito e mensagem. K2 fora do perfil. Revisão adversarial do `04`: "não exigida pelo perfil". |
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
