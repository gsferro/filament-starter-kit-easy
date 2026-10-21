# Relatório de QA — onboarding-guiado: jornadas semeadas, interruptor no settings e rule de manutenção

> Requisito: `00-requisito.md` · Plano: `01-plano-acao.md`
> Perfil de esforço: completo (UI com JS — checklist Livewire e tour Alpine)
> Natureza da wiki: nova · Toca infra compartilhada: sim → três `PanelProvider`, `KitInstall`, `KitUpdate`, `KitServiceProvider`, settings do kit · Regressão: sim
> Independência: sub-agente fw-qa-gate/claude-opus-5-5, contexto novo sem a conversa
> Cobertura: 5 de 12 dimensões verificadas ou provadas não aplicáveis · teto: APROVADO COM DÉBITO

## Veredito — Ciclo 1

**REPROVADO → especificação**

- Blocker: 0 · Major: 2 · Minor: 4 · Cosmético: 1
- Não verificadas: B, D, E, F, G, H, K — causas em *Não Verificado*
- `RQ` abertas: nenhuma. Ficam abertas ao solicitante Q11–Q17 e Q21–Q28, que sustentam as premissas vigentes P-04..P-19 (forma admitida, sem `RQ` aberta implementada)
- Ambiente: app em `http://localhost:8000` (**não é este kit** — ver *Não Verificado*) · Pest 5.0.5, PHP 8.4.25, PCOV e Xdebug carregados · MCP: Boost usado (`database-query`); Playwright MCP indisponível

## Achados

### QA-01 — O PRD e o `03` descrevem as condições de visibilidade antigas · Major · destino 1

- **Dimensão**: L (L3)
- **Relacionado a**: P-03, P-11, P-13, passos 3, 4 e 5 do PRD
- **Esperado**: PRD e `03` dizem o que o código faz, ou levam a marca `*(alterado em …)*`. O código: todo passo com tela recebe `tela:{chave}` (`database/seeders/Onboarding/SeederDeOnboarding.php:94`, `app/Support/TutorialGuiado.php:102`). Só `dock_no_painel`, `dashboard_dinamico_no_painel`, `bloqueio_de_tela_ligado` e `multi_tenant_ligado` são declaradas pelos filhos (`grep -rn visibility_condition database/seeders/Onboarding/`). O plugin roda com `progressPage(false)` e `skipWhen(! self::visivel())` (`TutorialGuiado.php:66-68`).
- **Observado**:
  - `01-plano-acao.md:313-341`, coluna *Visibilidade*, sem marca: `agentes_de_ia_ligados`, `hub_ligado`, `demo_ligada`, `central_de_comandos_ligada`; `—` para os passos com tela; `—` para `TrocaDeOrganizacaoSeeder`, que no código usa `multi_tenant_ligado`.
  - `01:297-299` ainda lista as sete condições, abaixo da marca de `:294` que diz que elas não existem mais.
  - `01:270` descreve `progressPage()` e `skipWhen(... PermissaoDaTela::permite(OnboardingProgress::class))`. A marca de `:276` só cobre o texto que vem *abaixo* dela.
  - `03-progresso.md:26` diz "as sete condições de visibilidade".
- **Repro**:
  1. `grep -rn "agentes_de_ia_ligados\|hub_ligado\|demo_ligada\|central_de_comandos_ligada" wikis/specs/feat/onboarding-guiado/onboarding-guiado/` → `01:298`, `:299`, `:324`, `:325`, `:331`, `:341`
  2. `grep -rn visibility_condition database/seeders/Onboarding/` → só Bloqueio, DashboardDinamico, Dock, TrocaDeOrganizacao e a base
- **Evidência**: as duas saídas acima. O desvio existe só em `03:223` (*Desvios do Plano*).
- **Destino**: 1 especificação
- **Ação exigida**: corrigir a tabela do passo 5 (`tela:{chave}` nos passos com tela; `multi_tenant_ligado` na troca de organização), os bullets de `01:297-299`, o registro de `01:270` e `03:26`, com marca de data em cada um.

### QA-02 — "291/291" do `--filter=Onboarding` não se reproduz · Major · destino 1

- **Dimensão**: L (L6)
- **Relacionado a**: `## Verificação Final` e passo 7 do `03`
- **Esperado**: o número da alegação sai do comando citado.
- **Observado**: `vendor/bin/pest --filter=Onboarding --compact` → `{"result":"passed","tests":293,"passed":293,"assertions":3110}`. O `03` diz `291/291` mais "CT-70/71 3/3". Mas o 291 já incluía o CT-70 antigo, de 1 caso (`03:76`), então a soma não fecha: 293 ≠ 291 e ≠ 294.
- **Repro**:
  1. `vendor/bin/pest --filter=Onboarding --compact`
  2. `grep -rn "291" wikis/specs/feat/onboarding-guiado/onboarding-guiado/ | grep -v "M291\|:291:"` → `03-progresso.md:38`, `:65` e `:208` (este é registro histórico do despacho 18)
- **Evidência**: as saídas acima.
- **Destino**: 1 especificação
- **Ação exigida**: trocar `03:38` e `03:65` pela saída real (293/293, com a data). `03:208` é o registro do despacho e fica, se quiser, com nota de que o número antecede o CT-70 reescrito e o CT-71.

### QA-03 — O índice do `04` aponta um arquivo de teste que não existe · Minor · destino 1

- **Dimensão**: L (L1)
- **Relacionado a**: `## Índice de Cenários`, CT-01..CT-65
- **Esperado**: a coluna `Arquivo` cita onde o CT está (`03:50-55`: `OnboardingGuiadoInterruptorTest`, `…ConsumoTest`, `…SemeaduraTest`, `…InstalacaoTest`).
- **Observado**: 56 linhas citam `tests/Kit/OnboardingGuiadoTest.php`. `ls tests/Kit/OnboardingGuiadoTest.php` → *No such file or directory*.
- **Repro**: `grep -c "tests/Kit/OnboardingGuiadoTest.php" wikis/specs/feat/onboarding-guiado/onboarding-guiado/04-casos-de-teste.md` → 56
- **Destino**: 1
- **Ação exigida**: alinhar a coluna `Arquivo` do índice ao mapa de `03:50-55`.

### QA-04 — O roteiro manual cita a cláusula errada como origem · Minor · destino 1

- **Dimensão**: L (L8)
- **Relacionado a**: RM-02, RM-07
- **Esperado**: a `Origem` do RM é a `RQ`/`P` da regra do CT citado. CT-05 → R3 (RQ-10). CT-08 → R5 (RQ-16, P-05). CT-11 e CT-45 → R6 (RQ-17, P-04) (`04:46-50`).
- **Observado**: `08:42` (RM-02) e `08:96`, `:101-103` (RM-07) citam RQ-11 ("nasce desligada"), que não trata nem de ligar pela tela nem do 404. Além disso, `08:5` diz "Base: 9b4ad23c + correções do step 9 (não commitadas)", mas a HEAD 259732f8 já as contém.
- **Repro**: `grep -n "RQ-11" wikis/specs/feat/onboarding-guiado/onboarding-guiado/08-roteiro-teste-manual.md`, comparado com o *Mapa de Regras* do `04`
- **Destino**: 1
- **Ação exigida**: RM-02 → RQ-10. RM-07 → RQ-16, RQ-17, P-04. Atualizar a base do cabeçalho.

### QA-05 — A rule manda trocar a chave do passo, o que esconde o passo de todo mundo · Minor · destino 1

- **Dimensão**: L (L5/L3)
- **Relacionado a**: RQ-13, RQ-26, P-23, CT-69
- **Esperado**: a orientação da rule respeita o invariante do código. A chave do passo com tela tem de ser igual ao kebab do nome do filho: a condição registrada vem de `Str::kebab(Str::beforeLast(class_basename($filho), 'Seeder'))` (`app/Support/TutorialGuiado.php:134`) e a visibilidade do passo é `tela:{$chave}` da chave passada (`SeederDeOnboarding.php:94`).
- **Observado**: `.ai/rules/onboarding.md:27` diz *"troque a CHAVE do passo (o antigo continua até alguém apagá-lo)"*. Seguido à risca (ex.: `'usuarios'` → `'usuarios-v2'` em `UsuariosSeeder.php:15`), o passo novo recebe `tela:usuarios-v2`. Essa condição não está registrada, e o pacote esconde o item (`01:94`). O CT-69 fica vermelho.
- **Repro** (estático, por leitura das três linhas citadas; o gate não altera a árvore):
  1. ler `.ai/rules/onboarding.md:27`
  2. ler `SeederDeOnboarding.php:94` e `TutorialGuiado.php:134`
- **Destino**: 1
- **Ação exigida**: reescrever a orientação, por exemplo "troque a chave e acrescente a condição `tela:{chave nova}`" ou "derive a chave da condição do passo, não da classe", e confirmar com o CT-69.

### QA-06 — Termos da feature fora do glossário · Minor · destino 1

- **Dimensão**: L (L7)
- **Relacionado a**: Q2/RQ-18 ("jornada por painel"), RQ-12/RQ-15 ("menu do onboarding") × UI "Tutorial guiado"
- **Observado**: `grep -n -iE "onboarding|jornada|tutorial|checklist|passo" wikis/glossario.md` → nenhuma linha. A wiki usa "jornada" (flow do pacote, uma por painel), "passo" (step) e "tutorial guiado" / "onboarding" como sinônimos (00, 01, UI), sem registro.
- **Destino**: 1
- **Ação exigida**: registrar em `wikis/glossario.md` "Jornada", "Passo" e "Tutorial guiado" (com "Não confundir com: Onboarding, o nome do pacote e do menu de autoria").

### QA-07 — Rótulos duplicados e referências mortas no PRD · Cosmético · destino 1

- **Dimensão**: L (L3)
- **Observado**:
  - `01:138` e `01:140`: duas decisões com o ID `D12`.
  - `01:151` cita "ADR-01", que não existe (`02:3`: "Nenhuma decisão passou nos três portões").
  - `01:3` diz "Adendos 1 e 2"; o `00` tem Adendo 3.
- **Repro**: `grep -n "^| D12\|ADR-01\|Adendos 1 e 2" wikis/specs/feat/onboarding-guiado/onboarding-guiado/01-plano-acao.md`
- **Ação exigida**: renumerar um dos `D12`; trocar "ADR-01" por "D9"; incluir o Adendo 3.

## Dimensões

| # | Dimensão | Status | Observação |
|---|----------|--------|------------|
| A | Cobertura do requisito | ✅ | `rastreabilidade.sh` exit 0; `indice.sh --check` não aplicável (sem `07-tickets/`); `git diff` por passo: todo arquivo citado nos passos 1–8 está no diff (o `PapeisSeeder` do passo 3 é condicional e não entrou, porque `View:PrimeirosPassos` vem do Shield — CT-47 verde) |
| B | Fronteiras e dados | ⏭️ não verificada | Pest 5.0.5 não tem `--agent`, e o app servido não é o kit; só o estático: CT-59 (limite de coluna), CT-18/CT-66 (chave fora do domínio) |
| C | Matriz de permissão | ✅ | papéis × (checklist, página, autoria) desligado/ligado: CT-08 (404 para toda persona, inclusive `master_global`), CT-09, CT-45, CT-47, CT-53, CT-62 (403), CT-68; policies de autoria não mudaram (`PoliciesDeVendor`), e a ação destrutiva da autoria é anterior à feature |
| D | Observabilidade | ⏭️ não verificada | estático ok: canal `onboarding`, prefixo `[Classe@Método]`, `warning` para recusa, `info`/`debug` no seeder, context com `user_id`/`rota`/`motivo`/`flow`, sem PII; log real não lido (suíte com `NullHandler`, app servido não é o kit) |
| E | Performance | ⏭️ não verificada | estático: desligado, o `skipWhen` evita ler `onboarding_*`; ligado, um `canAccess()` por passo; contagem de queries não medida (sem `--agent`, sem app) |
| F | UX de erro | ⏭️ não verificada | mensagens do `kit:install` (P-21, P-10) e do filho de tenancy dizem o quê e como resolver, em pt; telas de 404/403 não observadas |
| G | Tema e cor | ⏭️ não verificada | `dark-mode.sh --mecanismo` exit 1 (Filament, classe `dark`, nos três painéis); nível 1 `dark-mode.sh` exit 0 nos arquivos AM do diff; nível 2 inexistente (CT-B só `inLightMode()`); nível 3 sem Playwright MCP |
| H | Acessibilidade | ⏭️ não verificada | CT-B sem `assertNoAccessibilityIssues()`; sem MCP para teclado e foco; markup do checklist e do tour é do vendor |
| I | Segurança da superfície nova | ✅ | coberto pelo step 9, `## Revisão do Diff (step 9)` do `03` (25 linhas, 5 rejeitadas com motivo); além dele: nenhuma rota nova sem painel/`can`, sem model novo (mass assignment n/a), sem upload, sem `DB::raw`; IDOR de progresso coberto por CT-19, CT-48, CT-50 e CT-66 (sujeito resolvido no servidor, `#[Locked]` no escopo) |
| J | Regressão adjacente | ✅ | `vendor/bin/pest --parallel --exclude-testsuite=Browser` → 5321 testes, 5318 passaram, 3 pulados, 2 risky (mesmo número do `03`); browser em série `OnboardingGuiadoTest` + `TelasDoKitTest` 9/9; `--tia` não rodado (a suíte inteira é superconjunto); CT das ancestrais (`agentes-ia-no-settings`, dock, configurações, `tests/Kit` de instalação) incluídos; RCRCRC: `KitInstall` (Risk/Repaired, CT-67) e providers (Core) verdes |
| K | Adequação da suíte | ⏭️ não verificada | K1 `k1-oraculo-fraco.sh` exit 1 com 8 candidatos, todos rejeitados: testes anteriores à feature em arquivos só tocados (`TelasDoKitTest:41,77` smoke por desenho; `PermissoesDeResourcesTest:141,155`; `QualidadeDeCodigoTest:30,51,66,110`); os arquivos novos não têm candidato. K2: score não certificado — ver *Não Verificado*. Revisão adversarial do `04`: feita (2 rodadas) |
| L | Consistência documental | ❌ | 7 achados (QA-01..07). L1 `ids-ct.sh` exit 0 (73 IDs = 71 CT + 2 CT-B), contagem do cabeçalho do `04` reproduzida (71 · 32 · 192 · 0); L2 `citacoes.sh` exit 0; L4 `conformidade-rules.sh` exit 0; L5 docs pt/en com 27 filhos cada, CHANGELOG coerente; L6 `checkbox-sem-evidencia.sh` exit 0, números reproduzidos (5321, 9/9, 29 arquivos, 3 providers, 71+2, filacheck 17), um não (QA-02); o `--mutate` está declarado como débito, sem falsa ausência; L8 `roteiro-manual.sh` exit 0; RM executados: nenhum — Não Verificado (ver abaixo) |

## Débitos Aceitos

- (nenhum: QA-03..07 voltam com os Major no ciclo 2)

## Suspeitas Não Confirmadas

- `--mutate` no middleware deu kill nos mutantes do log (linhas 54–56: `RemoveMethodCall`, `ConcatRemove*`, `RemoveArrayItem` do context), que nenhum teste observa (`grep "Log::" tests/Kit/OnboardingGuiado{Interruptor,Consumo}Test.php` vazio): sinal de morte falsa do arnês no Windows, não de assertion forte.
- O tour no `/app` não tem CT-B (só `/admin` e `/infra`). O plugin é um só (`TutorialGuiado::plugin()`), então o mutante por painel é pouco plausível; sem repro.

## Não Verificado

- **B, D, E, F (parte dinâmica)** — motivo: app não servido. `http://localhost:8000` responde `302 → /projetos/login` com `<title>Projetos Fiotec</title>`; o kit tem `/login` e `APP_NAME="Starter Kit"`, e há dois processos ouvindo na 8000. O banco local também não tem a migration de settings da feature (`settings` sem `onboarding_habilitado`). Pest 5.0.5 sem `--agent` (prova: `vendor/bin/pest --help`).
- **G** — níveis 2 e 3: sem CT-B em `inDarkMode()` e Playwright MCP indisponível (o gate não escreve teste).
- **H** — sem `assertNoAccessibilityIssues()` e sem MCP.
- **K2** — prova indispensável pendente. O driver existe (`php -m`: pcov, xdebug; `vendor/pestphp/pest-plugin-mutate`). A rodada escopada pelo `pestw.cmd` (`--path=app/Http/Middleware/ExigeTutorialGuiadoLigado.php --no-cache --filter='CT-08|CT-45|CT-10|CT-46'`) deu 39 mutantes, 37 *tested*, 2 *uncovered* (linha 45 `RemoveEarlyReturn`, linha 67 `ContinueToBreak`), 94,87 %, `Duration` 17,38 s. Isso dá ~0,47 s por mutante contra 20 testes em 157 s na rodada de cobertura, e houve kill em mutantes de log que nenhum teste observa. No Windows, sem aplicar um mutante discriminante à mão (o gate não altera a árvore), o score não é certificável.
- **L8 (execução)** — Não Verificado: destrutivo / ambiente não confirmado. RM-01, RM-02, RM-06, RM-08 e RM-09 gravam estado. RM-03, RM-04, RM-05 e RM-07 exigem semear e ligar o tutorial no banco local, que não é descartável. E o app servido não é o kit.
