# Relatório de QA — Roadmap item 1: preferências de aparência por usuário

> Requisito: `00-requisito.md` · Plano: `01-plano-acao.md`
> Perfil de esforço: completo (a UI tem JS: o recolher do menu é Alpine; o domínio inclui autorização por duas chaves)
> Natureza da wiki: `feature`, fora do vocabulário da skill; lida como evolução, porque declara ancestrais · Toca infra compartilhada: sim → render hook de densidade/fonte, largura e modo do menu e cor nos três painéis · Regressão: sim
> Independência: sub-agente fw-qa-gate/claude-opus-5-5, contexto novo sem a conversa
> Cobertura: 8 de 12 dimensões verificadas ou provadas não aplicáveis · teto: APROVADO COM DÉBITO

## Veredito — Ciclo 1

**REPROVADO → especificação**

- Blocker: 0 · Major: 2 · Minor: 3 · Cosmético: 1
- Não verificadas: E (contagem de queries), H (axe), K (K2), L (parte de julgamento da L4). As causas estão em *Não Verificado*
- `RQ` abertas: nenhuma. Perguntas ao solicitante ainda abertas: Q5 e Q6, cobertas pelas premissas vigentes P-15 e P-16. Ambas estão implementadas como premissa, e isso conta como débito, não como Major
- Ambiente: app em `http://127.0.0.1:8091` (sem tenancy) e `http://127.0.0.1:8092` (com tenancy) · Pest 5.0.5 · Boost MCP indisponível (falhou ao conectar) · Playwright MCP indisponível. Usei o Playwright de `node_modules` por script headless, só contra o localhost

O comportamento confere com o requisito em todos os pontos que reproduzi. Os dois Major são de documentação: um número do `03` que não se reproduz e um plano/ADR que contradizem o código. Pela taxonomia da skill, os dois reprovam.

## Achados

### QA-01 — Números da `## Testes` e da `## Verificação Final` do `03` não se reproduzem · Major · destino 1

- **Dimensão**: L6
- **Relacionado a**: `03-progresso.md:18`, `:33`, `:34`, `:35`, `:40`, `:44`, `:54`
- **Esperado**: todo `[x]` com número é reproduzível pelo comando que o gera.
- **Observado**:
  - `PreferenciasDeAparenciaTest` diz 33/33 e hoje dá 35/35 (CT-43 e CT-46 entraram depois). `AparenciaEfetivaTest` diz 38/38 e dá 41/41 (CT-45). `ConfiguracoesDeAparenciaTest` diz 10/10 e dá 11/11 (CT-44).
  - As faixas de CT listadas nessas linhas não incluem CT-43..CT-46.
  - O browser diz "5/5 em série, 54 asserções" e hoje dá 7/7 com 89 asserções (o CT-B01 foi ampliado aos três painéis).
  - `:44` fala em "41 CT"; o `04` tem 45.
  - "Playwright 17/17 … 22/23" (`:18`, `:54`) não tem comando nem roteiro versionado que o reproduza.
- **Repro**:
  1. `vendor/bin/pest tests/Kit/PreferenciasDeAparenciaTest.php --no-tia` → `"tests":35,"passed":35`. O mesmo para `AparenciaEfetivaTest` (41) e `ConfiguracoesDeAparenciaTest` (11).
  2. `vendor/bin/pest tests/Browser/PreferenciasDeAparenciaTest.php --no-tia` → `"tests":7,"passed":7,"assertions":89`.
- **Evidência**: `grep -rn "33/33\|38/38\|10/10\|59 de 81\|17/17\|22/23" wikis/specs/feat/preferencias-de-aparencia/preferencias-de-aparencia/` → só `03-progresso.md:18,33,34,35,54`. O "41 CT" aparece só em `03-progresso.md:44`.
- **Destino**: 1
- **Ação exigida**: no `03`, trocar cada número pela saída real e completar as faixas de CT. O "17/17 / 22/23" vira um roteiro reproduzível ou sai como número. Arquivo afetado: só o `03`.

### QA-02 — `01` e `02` afirmam o que o código não faz, e o desvio só aparece no `03` · Major · destino 1

- **Dimensão**: L3
- **Relacionado a**: passos 2, 3, 5 e 6 e `## Cobertura do Requisito` do `01`; `## Superfície Livewire` do `02`; P-15, P-17
- **Esperado**: o PRD e a ADR batem com o código, ou o trecho traz a marca `*(alterado em …)*`.
- **Observado**:
  1. `01:175` diz `variaveis(): ?array`. O código tem `TamanhoDaFonte::declaracoes(): ?string`. O aceite (BP-03) só existe no `03:131`.
  2. `01:209` e `02:12` dizem que, na gravação, o `in()` do Select recusa o que não é opção. Na verdade `ConfiguracoesDoKit::mutateFormDataBeforeSave()` coage densidade, fonte e menu. O motivo está em `03:101`: o `in()` aceita uma lista forjada como `["grande"]`. Reproduzi o mesmo com `data.densidade = ['denso']` na seção do perfil: passou pela validação.
  3. O `liberada()` do passo 3 (`01:185`) não tem a guarda da pré-visualização. O código tem `request()->routeIs(self::ROTA_DA_PREVIEW)` (P-17, CR-01). A `## Cobertura do Requisito` não traz P-15, P-16 nem P-17.
  4. O log de recusa em `01:225` diz "personalização desligada". O código (`app/Livewire/PreferenciasDeAparencia.php:117`) diz "aparência pessoal desligada".
  5. A `## Superfície de UI` (`01:102`) lista `/app/meu-perfil`. Com tenancy, a rota é `/app/{organização}/meu-perfil`; a correção existe só na Análise (`01:74`).
- **Repro**: abrir cada linha citada e o arquivo de código correspondente. Para o item 2, no 8091 logado, `Livewire.all().find(c => c.name === 'preferencias_de_aparencia').$wire.$set('data.densidade', ['denso'])` e depois `salvar()` → "Aparência salva".
- **Evidência**: `grep -n "personalização desligada\|aparência pessoal desligada"` na wiki e em `app/Livewire/PreferenciasDeAparencia.php`. O "personalização desligada" do `04` (`:304`, `:316`, `:994`) descreve o aviso, não o texto do log.
- **Destino**: 1
- **Ação exigida**: corrigir `01` (passos 2, 3, 5 e 6, Cobertura e Superfície de UI) e `02` (linha `ConfiguracoesDoKit` e a frase sobre o `in()` na linha `$data`) com a marca `*(alterado em …)*`.

### QA-03 — O `04` ainda descreve o desenho antigo em quatro pontos e não indexa CT-41..CT-46 · Minor · destino 1

- **Dimensão**: L3 / L1
- **Relacionado a**: P-13, Q5, Q6, CT-41..CT-46
- **Observado**:
  - `04:43`: a célula E2/O2 diz que "o Meu perfil não está em organização (P-06, P-13)", o que contradiz a P-13 corrigida.
  - `04:304` diz que "a coluna da aparência entra no `$fillable` do usuário". O código (`app/Models/User.php`) a mantém fora do `$fillable`.
  - `04:71` e `:103` dizem que as perguntas foram "fechadas pela sessão", mas o `00` lista Q5 e Q6 como `aberta`.
  - CT-41..CT-46 não estão no `## Índice de Cenários`, e CT-43..CT-46 não estão em regra nenhuma do `## Mapa de Regras`.
  - A tabela "Desenhado × Implementado" do `05` está vazia.
- **Repro**: ler as linhas citadas; `grep -n "CT-4[1-6]" 04-casos-de-teste.md`.
- **Destino**: 1
- **Ação exigida**: alinhar o texto do `04` à P-13 e ao código, indexar CT-41..CT-46 em regra e no Índice, e preencher o roteiro do `05`.

### QA-04 — O placeholder da cor cita organização em instalação sem organizações · Minor · destino 3 → 2

- **Dimensão**: F (UX), P-16 / CT-31
- **Esperado**: sem tenancy, a seção não fala de organização (CT-31: "sem organizações: sem aviso nos três painéis").
- **Observado**: no 8091, sem tenancy, o placeholder é "Padrão — Amber, a do Filament (ou a da organização, dentro dela)". O sufixo é incondicional em `app/Livewire/PreferenciasDeAparencia.php:96`. O oráculo do CT-31 (`/organiza[çc][ãa]o\s+liber/`) não pega essa frase.
- **Repro**: 1) entrar no 8091; 2) abrir `/admin/meu-perfil`; 3) olhar o "Padrão" do campo Cor primária.
- **Destino**: 3, depois 2
- **Ação exigida**: fortalecer o CT-31 pela `feature-test-design` para que nenhuma menção a organização apareça sem tenancy. Ele deve falhar primeiro; só então condicionar o sufixo a `kit.tenancy.enabled`.

### QA-05 — P-13 estende a resposta de Q2 sem pergunta ao solicitante · Minor · destino 1

- **Dimensão**: A (auditoria do requisito)
- **Esperado**: Q2 diz "desde que os settings (principal e, se o tenant tiver sendo usado, também liberado naquela organização)".
- **Observado**: com tenancy, a escolha pessoal vale no `/admin` e no `/infra` mesmo para quem pertence só a organizações que não liberaram. No 8092, `/admin` e `/infra` mostram Purple, Denso, Grande e Oculto, enquanto `/app/globex` (não liberada) mostra Rose e o padrão. É a decisão do dev (P-13, "step 3"), não do solicitante.
- **Repro**: 1) entrar no 8092; 2) visitar `/admin`, `/infra` e `/app/globex`; 3) comparar `--primary-500` e a declaração `:root{…}`.
- **Destino**: 1
- **Ação exigida**: registrar em `## Perguntas ao Solicitante` se, com tenancy, os painéis sem organização seguem só a chave da instalação. P-13 continua vigente até a resposta.

### QA-06 — Contagens do cabeçalho do `04` e tipo da natureza fora do vocabulário · Cosmético · destino 1

- **Dimensão**: L1
- **Observado**:
  - O cabeçalho do `04` diz `Cenários: 39 · Mutantes previstos: 85`. Os `grep -c` da `template-04.md` dão 45 cenários, 16 regras, 86 mutantes e 0 sem matador.
  - O `01` diz `Tipo: feature`; o vocabulário da skill é nova · evolução · correção · ajuste · refatoração.
- **Evidência**: `grep -rn "Cenários: \|39 CT\|85 mutantes"` na wiki → `04:19` e `03:174` (registro histórico do despacho, que fica como está).
- **Ação exigida**: recalcular `04:19` pelos comandos e trocar o tipo no `01`.

## Dimensões

| # | Dimensão | Status | Observação |
|---|----------|--------|------------|
| A | Cobertura do requisito | ⚠️ | QA-05 — `rastreabilidade.sh` exit 0; `indice.sh --check` não se aplica (sem `07-tickets/`). As 14 RQ conferidas no diff e no app. RQ-11: `composer.json`/`lock` fora do diff. Testes da feature verdes: 35+41+11+3 (Kit), 14+16+4 (Tenancy), 7 (Browser) |
| B | Fronteiras e dados | ✅ | Forjado no `$wire` no 8091: `cor` Gray e injeção `</style><script>` recusados por validação; `fonte` 12345 recusado; campo extra não gravado nem logado; `densidade` em lista passa pelo `in()` e é coagida para o padrão (só a própria pessoa, sem defeito; ver QA-02, item 2) |
| C | Matriz de permissão | ✅ | Configurações só para quem administra (CT-27), chave da organização pela `TenantPolicy` (CT-28), `salvar()` grava só no usuário autenticado (CT-06). Todos verdes |
| D | Observabilidade | ✅ | `storage/logs/laravel.log`: `[PreferenciasDeAparencia@salvar]` com context `user_id` e valores do vocabulário; a recusa sai em WARNING. Sem PII. Canal padrão (D7) |
| E | Performance | ⏭️ | O decisor não tem I/O (leitura de `app/Support/AparenciaDoUsuario.php`); a contagem de queries não foi medida |
| F | UX de erro | ⚠️ | Recusa: "A aparência pessoal foi desligada nesta aplicação. Nada foi gravado. Recarregue a página." Validação em pt_BR. Ver QA-04 |
| G | Tema e cor | ✅ | `dark-mode.sh --mecanismo` exit 1 (Filament, classe `dark`); nível 1 exit 0 nos arquivos AM; nível 3: screenshot com `filament:theme:mode=dark`, seção legível; o aviso tem par `dark:text-gray-400` |
| H | Acessibilidade | ⏭️ | Os quatro `<select>` com `<label for>`; ordem de Tab densidade → fonte → cor → menu → Salvar; "Esconder tudo" recolhe a 0 px com o botão "Expandir" visível. Sem `assertNoAccessibilityIssues` nem axe |
| I | Segurança da superfície nova | ✅ | Coberto pelo step 9 (13 achados, 5 rejeitados). Além dele: nenhuma rota nova; nenhum `DB::raw`; `users.aparencia` fora do `$fillable` e em `$hidden`; o CSS sai do enum; nenhum id do cliente no `salvar()` |
| J | Regressão adjacente | ✅ | `pest --tia --parallel --processes=3 --exclude-testsuite=Browser`: 6182 testes, 6179 passaram, 3 pulados, 0 falhas (o TIA rodou a seleção inteira). Ancestrais por ID: 247/247 (Kit) e 52/52 (Tenancy). Bate com o `## Impacto` do `01` |
| K | Adequação da suíte | ⏭️ | K1 `k1-oraculo-fraco.sh` exit 1: `AparenciaEfetivaTest.php:169` (CT-16) rejeitado, porque `expect(...)->{$preferencia}->toBe($fabrica)` é assertion. K2 não verificado (ver *Não Verificado*). Revisão adversarial do `04` feita (2 rodadas). Amostragem manual: mutante sem a guarda da pré-visualização morto por CT-41; `&&`→`\|\|` e remoção da chave da organização (linha 134) mortos por CT-12 |
| L | Consistência documental | ❌ | QA-01, QA-02, QA-03, QA-06 — L1 `ids-ct.sh` exit 0 (8 arquivos), L2 `citacoes.sh` exit 0, L4 `conformidade-rules.sh` exit 0, L6 `checkbox-sem-evidencia.sh` exit 0, L8 n/a (sem `08`; o `03` decidiu `Roteiro manual: não`). Prova negativa: `phpstan` 0 erros, `filacheck` 17/17, `filament/blueprint` ausente de `vendor/filament` (confere com o limite declarado) |

## Débitos Aceitos

- Nenhum ainda. QA-03, QA-04, QA-05 e QA-06 entram no `03` como débito se não forem corrigidos neste ciclo.

## Suspeitas Não Confirmadas

- O Browser trouxe 2 warnings (`"warnings":2`, sem detalhe); a causa não foi investigada.

## Não Verificado

- K2 — prova indispensável à K pendente. O `pest --mutate --path=app/Support/AparenciaDoUsuario.php --covered-only --no-cache`, lançado por `php pestw.cmd` e dividido em blocos, deu:
  - 38 mutantes, 100 %, em 42 s;
  - 37 mutantes, 100 %, em **1,53 s**.
  
  O mutante `Line 99: EmptyStringToNotEmpty` foi dado como morto, mas é equivalente: `CorPrimaria::resolver(null, 'PEST Mutator was here!')` devolve `[]`. Carregado por `-d auto_prepend_file`, ele passa os 41 testes do arquivo. O arnês conta mortes falsas no Windows, então nenhum score é certificável. Teto: débito.
- E — contagem de queries por request não medida: sem `pest-plugin-agent` (`ls vendor/pestphp`) e sem o Boost MCP.
- H — `assertNoAccessibilityIssues()`/axe não rodado. O CT-B não o usa, e não tenho como gravar teste.
- L4 (julgamento) — conferi contra o código `livewire.md` e `settings.md`, que estão cumpridas. As outras 17 rules casadas ficaram aceitas pela linha do `03`, sem conferência própria.
- Suíte Browser inteira não rodada; só o arquivo da feature (7/7).
