# Relatório de QA — feat/aparencia-das-telas-de-acesso: Tela de login personalizável

> Requisito: `00-requisito.md` · Plano: `01-plano-acao.md`
> Perfil de esforço: completo (UI com JS — iframe de preview, campos `live` — e domínio sensível: autorização do preview e escopo por organização)
> Natureza da wiki: evolução · Toca infra compartilhada: sim → todas as telas de autenticação dos três painéis · Regressão: sim
> Independência: sub-agente fw-qa-gate/claude-opus-5-5, contexto novo sem a conversa
> Cobertura: 8 de 12 dimensões verificadas ou provadas não aplicáveis · teto: APROVADO COM DÉBITO

## Veredito — Ciclo 1

**REPROVADO → especificação**

- Blocker: 2 · Major: 4 · Minor: 2 · Cosmético: 1
- Não verificadas: E, G, H, K. As causas estão em *Não Verificado*
- `RQ` abertas: nenhuma formalmente. Q1 e Q2 seguem `aberta` no `00` e afetam RQ-01, 07, 09, 10 e 11, que estão marcadas `fechada` (ver QA-03)
- Ambiente: app em `http://127.0.0.1:8123` (layout global `painel`) · Pest 5.0.5 · Playwright MCP: indisponível · Boost MCP: indisponível (o `laravel-boost` não conectou: CONNECT_TIMEOUT)

## Achados

### QA-01 — Preview checa o painel errado: P-17 não vale em produção e o CT-38 está verde pelo motivo errado · Blocker · destino 3 → 2

- **Dimensão**: I / C / L4 (`.ai/rules/auth.md`)
- **Relacionado a**: P-17, CT-38, passo 6 do PRD, ADR-04
- **Esperado**: segundo P-17, *"O preview só abre para quem entra no painel admin"*. O `01` › Autorização diz *"rota sob `panel:admin` + o `Authenticate` do Filament (só quem entra no `/admin`)"*. A rule `auth.md` (§*Página de auth fora do painel…*) manda varrer `getCurrentOrDefaultPanel()` antes de operação que consulta `canAccessPanel()`.
- **Observado**: o Laravel reordena os middlewares, e o `Authenticate` roda **antes** de `SetUpPanel:admin`. O `Authenticate` do Filament usa `Filament::getCurrentOrDefaultPanel()` (`vendor/filament/filament/src/Http/Middleware/Authenticate.php:28`), que nesse momento é o painel default, o `app`. Assim, uma conta só do `/app` passa pelo middleware, e quem barra é apenas o `podeVer()` do `mount()`. No CT-38, o `beforeEach` faz `Filament::setCurrentPanel('admin')` (`tests/Kit/AparenciaDasTelasDeAcessoTest.php:35`), e esse estado vaza para o `$this->get()` no mesmo processo. Por isso o teste dá 403, o que não acontece num request real. O `03` › Conformidade marca `auth.md` como "aplicada".
- **Repro**:
  1. `php artisan tinker --execute '$r = app("router")->getRoutes()->getByName("kit.preview-das-telas-de-acesso"); print_r(app("router")->gatherRouteMiddleware($r));'` → `[5] Filament\Http\Middleware\Authenticate … [8] Filament\Http\Middleware\SetUpPanel:admin`
  2. Como visitante: `curl -s -o /dev/null -w "%{http_code} %{redirect_url}" http://127.0.0.1:8123/admin/preview-das-telas-de-acesso` → `302 http://127.0.0.1:8123/app/login`. Isso é o `getLoginUrl()` do painel `app`, quando `/admin` redireciona para `/admin/login`.
  3. (para a sessão, não executado: o gate não grava fixture) Repetir o CT-38 com `Filament::setCurrentPanel(null)` antes do `get`: o esperado é 200 com o rascunho.
- **Evidência**: as saídas dos passos 1 e 2. Impacto prático baixo: a conta só vê o próprio rascunho, que só existe se ela já teve acesso ao admin nos últimos 30 min. Mas a fronteira declarada não existe, e a severidade segue a tabela da L4 (rule de autenticação violada).
- **Destino**: 3 (o CT-38 precisa de um request sem painel corrente vazado), depois 2
- **Ação exigida**: (1) via `feature-test-design`, o CT-38 limpa o painel corrente antes do request e falha; (2) corrigir a fronteira, por exemplo com o `Authenticate` depois de `SetUpPanel` ou com um `canAccessPanel(Filament::getPanel('admin'))` explícito no `mount()`/`rascunhoAutorizado()`; (3) corrigir a linha `auth.md` do `03` e o docblock de `PreviewDasTelasDeAcesso.php:35` ("`panel:admin` + `auth`").

### QA-02 — RQ-08 não entregue: os cenários 1 a 4 não foram executados · Blocker · destino 2

- **Dimensão**: A
- **Relacionado a**: RQ-08, RQ-07, CT-21, CT-27, Q10, passo 9 do PRD
- **Esperado**: RQ-08, *"rode os cenarios ao finalizar"*. Q10 decidiu que a regressão de RQ-07 se prova também pelos quatro cenários do checklist de release.
- **Observado**: `03-progresso.md:56` traz `- [ ] Cenários 1 a 4`, e `:133` traz `- [ ] Cenários 1 a 4 (pré-validação…)`. CT-21 e CT-27 são "execução pela sessão" e não têm registro de execução.
- **Repro**: `grep -n "Cenários 1 a 4" wikis/specs/feat/aparencia-das-telas-de-acesso/tela-de-login-personalizavel/03-progresso.md`
- **Evidência**: as duas linhas sem marcação
- **Destino**: 2
- **Ação exigida**: rodar os 4 cenários sobre o commit final antes do PR e registrar a evidência no `03`, com CT-21 e CT-27 fechados.

### QA-03 — Q2 aberta com a premissa ampla (P-02) implementada; RQ-09/RQ-10 marcadas `fechada` · Major · destino 1

- **Dimensão**: A (auditoria do requisito)
- **Relacionado a**: Q2, P-02, RQ-09, RQ-10, passo 2 do PRD
- **Esperado**: `feature-wiki` §*Quando o solicitante não responde a tempo*: *"Só a afirmação dependente fica bloqueada… RQ aberta implementada é REPROVADO → especificação"*. Também manda seguir com a premissa **mais estreita**.
- **Observado**: o `00` mantém Q2 (*"todas as telas ou só o login?"*) como `aberta`, com Afeta = RQ-09 e RQ-10, e essas duas RQs estão `fechada`. O código implementa a opção ampla: `TelaRecuperarSenha`, `TelaRedefinirSenha`, `TelaVerificacaoDeEmail`, `TelaDoisFatores`, `TelaBloqueio` e `RegistroPorConvite` usam `VesteAparenciaDasTelas`. Q1, ao contrário, foi tratada pela opção estreita (P-01, nada muda), e isso não é achado.
- **Repro**: `grep -n "^| Q2\|^| RQ-09\|^| RQ-10" …/00-requisito.md` e `grep -ln "use VesteAparenciaDasTelas" app/Filament/Pages/Auth/*.php`
- **Evidência**: Q2 `aberta`; 8 páginas vestidas
- **Destino**: 1
- **Ação exigida**: obter a resposta do solicitante a Q2 (registrar como Adendo) ou estreitar o escopo para o login. Até lá, a decomposição não pode marcar RQ-09/RQ-10 como `fechada`.

### QA-04 — Texto branco no painel com contraste abaixo de 3:1 em cores médias, inclusive na primária default · Major · destino 3 → 2

- **Dimensão**: H (G correlata)
- **Relacionado a**: P-18, RQ-01 (acessibilidade), CT-40, passo 3 do PRD
- **Esperado**: P-18, *"Com uma cor de painel clara, o texto do painel passa a escuro, para continuar legível"*.
- **Observado**: o limiar `luminância > 0,4` (`app/Support/AparenciaDasTelasDeAcesso.php:fundoClaro:266`) deixa texto branco entre L 0,18 e 0,40. Nessa faixa, branco perde do preto e fica abaixo de 4,5:1. Exemplos: `#00aaff` dá 2,56:1, `#ff6600` 2,94:1, `#d97706` 3,19:1. Com a cor em branco (o default), `fundoClaro()` devolve sempre `false`, e o painel usa `--primary-600`. No kit sem cor configurada, isso é o âmbar 600, cerca de 3,2:1 sob o nome da aplicação (17 px bold, no topo, onde o gradiente é transparente). O docblock de `fundoClaro` afirma que as cores da paleta no tom 600 *"são escuras"*, o que é falso para amber, yellow e lime. O CT-40 só testa os extremos (`#ffffff`, `#f5f5dc`, `#123456`, `#000000`), sem os valores de fronteira.
- **Repro**:
  1. `php -r 'require "vendor/autoload.php"; var_dump(App\Support\AparenciaDasTelasDeAcesso::fundoClaro("#00aaff"));'` → `false` (texto branco, 2,56:1)
  2. Abrir `http://127.0.0.1:8123/admin/login`: `.kit-painel-de-marca` sem `style`, cor `--primary-600`, `color: #fff` (`resources/css/filament/kit.css:291`); captura em `art/telas-de-acesso-painel.png`
- **Evidência**: contraste calculado com a fórmula do próprio `fundoClaro`
- **Destino**: 3 (o CT-40 precisa de BVA no limiar, de cerca de 0,18 a 0,40, e do caso da primária default), depois 2
- **Ação exigida**: via `feature-test-design`, BVA do contraste. O CT novo falha antes da correção; depois, corrigir o limiar (escolher a cor de texto de maior contraste) e o caso sem hex.

### QA-05 — PRD, ADR e docs descrevem a mídia do painel e a superfície do preview de um jeito que o código contradiz · Major · destino 1

- **Dimensão**: L3 (e L5, parte Minor)
- **Relacionado a**: P-15, ADR-03, ADR-04, passos 1 e 6
- **Esperado**: o texto bate com o código, ou leva a marca `*(alterado em …)*`.
- **Observado**:
  - (a) `01-plano-acao.md:197` diz *"mídia da base (ou a arte, se a base não tiver)"* e `02-decisoes-arquiteturais.md:75` diz *"(a da base, ou a arte)"*. O código usa sempre a arte da identidade (`AparenciaDasTelasDeAcesso.php:aplicar:176`, P-15).
  - (b) A docs pt e en, na linha 127, diz que *"O painel de marca aproveita a arte… que os providers configuraram"* / *"uses the artwork… the providers configured"*. É a mesma consequência, invalidada pela P-15.
  - (c) A `## Superfície Livewire` do `02` lista *"ação **Atualizar preview** (código novo, Action de schema)"* e *"uuid do rascunho no formulário — cliente pode trocar"*. O código tem um botão Alpine `$wire.$refresh()` sem Action (`preview-das-telas-de-acesso.blade.php:56`) e a chave usa o id do componente Livewire (`EditorDeAparenciaDasTelas.php:urlDoPreview:249`), não um campo de formulário. A ADR-04 ainda diz *"uuid da instância"*.
- **Repro**: `grep -rn "mídia da base\|da base, ou a arte\|uuid" wikis/specs/feat/aparencia-das-telas-de-acesso/` e `grep -n "aproveita\|providers configured" docs/*/autenticacao/aparencia-das-telas.md`
- **Evidência**: as quatro ocorrências citadas
- **Destino**: 1
- **Ação exigida**: corrigir e datar `01:197`, `02:75`, `02` ADR-04 e a Superfície Livewire, e `docs/pt` e `docs/en/autenticacao/aparencia-das-telas.md:127`.

### QA-06 — "80 arquivos alterados" da Verificação Final não se reproduz · Major · destino 1

- **Dimensão**: L6
- **Relacionado a**: RQ-12, `## Verificação Final`
- **Esperado**: um número que algum comando reproduza.
- **Observado**: `git diff main --name-only | wc -l` = 77 (71 de código + 6 da wiki); `git diff --name-only main...HEAD | wc -l` = 77. Nenhum comando conferido chega a 80.
- **Repro**: `grep -rn "80 arquivos" wikis/specs/feat/aparencia-das-telas-de-acesso/` → só `03-progresso.md:130`
- **Evidência**: as saídas acima
- **Destino**: 1
- **Ação exigida**: refazer a busca do RQ-12 e colar a contagem real com o comando que a produz.

### QA-07 — Vocabulário: "telas de autenticação" com outra extensão e termos novos fora do glossário · Minor · destino 1

- **Dimensão**: L7
- **Esperado**: no glossário, *Tela de autenticação* inclui a escolha de painel (`wikis/glossario.md:38`).
- **Observado**: a doc, o helperText e o CHANGELOG dizem que a aparência vale para "todas as telas de autenticação", mas P-02 exclui a escolha de painel. "Aparência padrão", "painel de marca" e "telas de acesso" foram decididos nesta feature e não entraram no glossário.
- **Repro**: `sed -n 38p wikis/glossario.md`; `grep -n "todas as telas de autenticação" docs/pt/autenticacao/aparencia-das-telas.md CHANGELOG.md`
- **Destino**: 1
- **Ação exigida**: alinhar o texto ("as telas vestidas pelo Auth Designer, exceto a escolha de painel") e acrescentar os três termos ao glossário.

### QA-08 — CT-33 (Tenancy) só com expectativas negadas e a padrão nula: não distingue o "clássico próprio" da "padrão" · Minor · destino 3

- **Dimensão**: K1
- **Relacionado a**: CT-33, P-07
- **Observado**: `k1-oraculo-fraco.sh` → `tests/Tenancy/AparenciaDasTelasDeAcessoTenancyTest.php:224 … só expectativa negada`. Com a padrão nula, um defeito que trate o `['layout'=>'classico']` da organização como "usa a padrão" passa. Nenhum teste combina "padrão em painel + organização no clássico", que a doc promete (*"A organização pode escolher o clássico mesmo com a padrão em painel"*).
- **Repro**: `bash .ai/skills/feature-quality-gate/scripts/k1-oraculo-fraco.sh tests/Tenancy/AparenciaDasTelasDeAcessoTenancyTest.php`
- **Destino**: 3
- **Ação exigida**: um cenário com a padrão em `painel` e a organização em `classico`, com a ausência do painel confirmada no HTML.

### QA-09 — Cabeçalhos e registro desatualizados no `04` e no `03` · Cosmético · destino 1

- **Dimensão**: L1
- **Observado**: o cabeçalho do `04` não tem a linha `Cenários: n · Regras: n` (o valor real por `grep -cE … \[CT-[0-9]+\]` é 41). O "Oráculo atual" para em P-14 e os "Acréscimos" em CT-35 (faltam P-15..18 e CT-36..41). O `## Índice de Cenários` ainda diz *"propostos, ainda não implementados"*. No `03`, a linha 5 de `## Despachos` está quebrada por uma quebra de linha literal na célula.
- **Destino**: 1
- **Ação exigida**: recalcular pelos comandos do `template-04.md` §*Contagem do cabeçalho* e consertar a linha da tabela.

## Matriz de Rastreabilidade

`rastreabilidade.sh` exit 0. Indicam lacuna só estas linhas:

| RQ/P | Cláusula ou premissa | Passo PRD | CT | CT-B | Código | Resultado | Veredito |
|------|----------------------|-----------|----|------|--------|-----------|----------|
| RQ-08 | cenários ao final | 9 | CT-21, CT-27 (sessão) | — | — | não executado | ❌ QA-02 |
| RQ-09/RQ-10 | tela de config / preview (Q2 aberta) | 5, 6 | CT-01..03, 14..20 | B05 | 8 páginas vestidas | escopo amplo sem resposta | ❌ QA-03 |
| P-17 | preview só para quem entra no admin | 6 | CT-38 (verde por estado vazado), CT-39 | — | `KitServiceProvider` (rota) | barreira avalia o painel `app` | ❌ QA-01 |
| P-18 | texto legível sobre cor clara | 3 | CT-40 (sem fronteira) | — | `fundoClaro()` | 2,56–3,2:1 em cores médias | ❌ QA-04 |

## Dimensões

| # | Dimensão | Status | Observação |
|---|----------|--------|------------|
| A | Cobertura do requisito | ❌ | QA-02, QA-03 — `rastreabilidade.sh` exit 0; `indice.sh --check`: sem `07-tickets/`; `git diff` por passo conferido (arquivos dos passos 1–8 presentes no diff) |
| B | Fronteiras e dados | ✅ | validação lida (`in`, regex `\z`, teto após trim por ponto de código, escape); CT-03/05/06/28–30, B07/B08 verdes |
| C | Matriz de permissão | ❌ | admin/master × global/tenant/novo cobertos por CT-17/19/39; célula "conta só `/app`" × preview: QA-01 |
| D | Observabilidade | ✅ | `storage/logs/tenancy-2026-11-13.log`: `[VesteAparenciaDasTelas@organizacaoDaAparencia]` com context `tenant_id/motivo/pagina`; preview e normalizar em `autenticacao`; sem PII no context |
| E | Performance | ⏭️ não verificada | leitura: uma query por slug, memo por instância, sem coleção; contagem mecânica não executada (sem `pest-plugin-agent`; prova: `ls vendor/pestphp/`) |
| F | UX de erro | ✅ | mensagens pt no campo ("Use no máximo 80 caracteres.", "Use uma cor no formato #RRGGBB."); nada salvo na recusa (CT-29) |
| G | Tema e cor | ⏭️ não verificada | `dark-mode.sh --mecanismo` exit 1 (Filament, classe `dark`); nível 1 `dark-mode.sh` sobre views/css/Filament do diff exit 0; nível 2: CT-B03/B04 com `inDarkMode()` verdes; nível 3 sem MCP (só capturas claras de `art/` vistas) |
| H | Acessibilidade | ⏭️ não verificada (com achado) | QA-04; teclado: CT-B03 verde; `assertNoAccessibilityIssues()` ausente nos CT-B da feature |
| I | Segurança da superfície nova | ❌ | step 9 cobriu (4 passes; 19 linhas na tabela, BP-20 rejeitado); além dele: ordem real de middleware (QA-01), chave do rascunho com o id do usuário (sem IDOR), `#[Locked]` em `orgDaAparencia`/`rascunho`/`tela`, escopo inexistente falha fechado (403) |
| J | Regressão adjacente | ✅ | os 13 arquivos Kit do `01` (+ helpers, site, citações): 499/499; Tenancy ancestrais: 52/52; Kit da feature 130/130; Tenancy 72/72; Browser da feature 40/40 (B05 falhou 1× sob carga e passou 2/2 isolado: flake, destino 4) |
| K | Adequação da suíte | ⏭️ não verificada | K1 exit 1: 4 candidatas, 3 rejeitadas (asserção em helper: CT-31, CT-07, CT-11), 1 vira QA-08; K2 em `AparenciaDasTelasDeAcesso` pelo `pestw.cmd`, `--no-cache --parallel`, só o arquivo Kit: 198 mutantes, 181 testados, 0 sobreviventes, 17 uncovered (constantes 46–54, 154 e 241: fora do arquivo Kit), Score 91,41 %, `Duration` 8,80 s com relógio de parede de 15 min 38 s em 16 processos; morte não amostrada; re-revisão adversarial do conjunto revisado não feita (o próprio `04` declara) |
| L | Consistência documental | ❌ | QA-05, 06, 07, 09 — L1 `ids-ct.sh` exit 0; L2 `citacoes.sh` exit 0; L4 `conformidade-rules.sh` exit 0 (19 linhas), com `auth.md` violada (QA-01); L6 `checkbox-sem-evidencia.sh` exit 0; reproduzidos: filacheck 17/17, Kit 5045 e Tenancy 741 por `--list-tests`, 19 rules; não reproduzido: "80 arquivos"; L8 `roteiro-manual.sh` exit 0 — L8 n/a: sem `08`, decisão `não` no `03` |

## Débitos Aceitos

- Nenhum: há Blocker e Major abertos.

## Suspeitas Não Confirmadas

- "Browser 171/171" (`03:131`): `pest --testsuite=Browser --list-tests` lista 220. Pode ser skip condicional; a suíte inteira não foi rodada para confirmar.
- Score do `--mutate` (0 sobreviventes em 181): o relógio de parede indica que os subprocessos rodaram, mas no Windows o plugin já deu morte falsa por filtro truncado. Sem amostragem manual de mutante, o número não fica certificado.

## Não Verificado

- E — contagem de queries por `--agent` não executada; motivo: `pest-plugin-agent` ausente (prova: `ls vendor/pestphp/`)
- G nível 3 (olho nos dois temas) — motivo: Playwright MCP indisponível
- H — axe (`assertNoAccessibilityIssues`) não executado; motivo: nenhum CT-B da feature o chama e o MCP está indisponível
- K — prova indispensável pendente: amostragem de mutante morto (exige editar arquivo, vedado ao gate) e a re-revisão adversarial do `04` revisado (CT-29..41, B07/B08), declarada pendente no próprio `04`
- Sessão autenticada no app servido (editor, preview 404/403 ao vivo) — motivo: sem MCP; coberta só por CT/CT-B
- Busca do RQ-12 pelos nomes do projeto de referência — motivo: os nomes estão mascarados no `00`; só o oráculo genérico de links (CT-24) rodou
