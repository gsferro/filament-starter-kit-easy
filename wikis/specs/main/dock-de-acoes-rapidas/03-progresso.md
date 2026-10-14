# Progresso — Dock de ações rápidas

**Estado**: concluída — 2026-10-14

## 1. Dependência

- [x] `composer require hoceineel/filament-quick-action-dock:^0.2` e `php artisan filament:assets` — ticket 01 concluído, 2026-10-14
- [x] Afirmações sobre o vendor reconferidas em `vendor/` com citação por símbolo — ticket 01 concluído, 2026-10-14

## 2. Channel de log

- [x] Channel `dock-de-acoes-rapidas` em `config/logging.php` — ticket 01 concluído, 2026-10-14

## 3. Config `kit.dock`

- [x] Bloco `dock` em `config/kit.php` (lista vazia = nenhum painel) — tickets 01/04 concluído, 2026-10-14
- [x] `KIT_DOCK_PAINEIS` em `.env.example` — tickets 01/04 concluído, 2026-10-14

## 4. Settings

- [x] Três propriedades em `ConfiguracoesDoKit` + linhas no `mapaDeConfiguracao()` — tickets 01/04 concluído, 2026-10-14
- [x] Migration de settings nova, com limpeza do cache no `up()` e no `down()` — tickets 01/04 concluído, 2026-10-14
- [x] Seção "Dock de atalhos" na aba Kit — tickets 01/04 concluído, 2026-10-14

## 5. Permissão `Usar:Dock`

- [x] `custom_permissions` + mapa de painel do `PapeisSeeder` + subtração do `panel_user` — ticket 01 concluído, 2026-10-14

## 6. Preferências do usuário

- [x] Migration `dock_atalhos`/`dock_layout` em `users` — tickets 01/03 concluído, 2026-10-14
- [x] Casts no `User`, colunas fora do `$fillable` — tickets 01/03 concluído, 2026-10-14

## 7. `DockDeAtalhos`

- [x] Settings (`habilitadoNoPainel`, `layoutPara`, `estilo`) — tickets 01/03/05/06/07/08 concluído, 2026-10-14
- [x] `noContexto` com restauração no `finally` — tickets 01/03/05/06/07/08 concluído, 2026-10-14
- [x] `podeAbrir`, `atalhosVisiveis` (memo por request), `catalogo` — tickets 01/03/05/06/07/08 concluído, 2026-10-14
- [x] `paginaAtualComoAtalho`, `gravar` — tickets 01/03/05/06/07/08 concluído, 2026-10-14

## 8. `PluginDoDock`

- [x] Plugin com `authorize`, `visible`, `layout`, `style`, `position` — tickets 01/03/05/08 concluído, 2026-10-14
- [x] Actions de favorito, `adicionarPaginaAtual`, `gerenciarAtalhos` (slideover) — tickets 01/03/05/08 concluído, 2026-10-14

## 9. Registro nos três painéis e inventário

- [x] `PluginDoDock::make()` nos três providers — ticket 01 concluído, 2026-10-14
- [x] Linhas no inventário de `PermissoesDeAcoesTest` e `Usar:Dock` no dataset do CT-15 — ticket 01 concluído, 2026-10-14
- [x] Âncora do `KitInfoTest` 63 → 66 — ticket 01 concluído, 2026-10-14

## 10. Documentação e CHANGELOG

- [x] Seção "Dock de atalhos" em `docs/{pt,en}/recursos/hub-de-navegacao.md` — ticket 07 concluído, 2026-10-14
- [x] Opções do dock em `docs/{pt,en}/recursos/configuracoes-do-kit.md` — ticket 07 concluído, 2026-10-14
- [x] `CHANGELOG.md` — ticket 07 concluído, 2026-10-14

## Testes

- [x] `tests/Kit/DockDeAtalhosTest.php` (CT-01, CT-02, CT-03, CT-05, CT-06, CT-07, CT-10–CT-21, CT-23–CT-25, CT-30–CT-36, CT-38–CT-40, CT-45–CT-49, CT-53, CT-54, CT-56, CT-59, CT-60, CT-65) — testes verdes: Kit 124/124, Tenancy 44/44, ConfiguracoesDoKitTela 38/38, Browser CT-B01 1/1 concluído, 2026-10-14
- [x] `tests/Tenancy/DockDeAtalhosTenancyTest.php` (CT-04, CT-22, CT-26–CT-29, CT-43, CT-44, CT-50, CT-51, CT-52, CT-55, CT-58, CT-61–CT-64) — testes verdes: Kit 124/124, Tenancy 44/44, ConfiguracoesDoKitTela 38/38, Browser CT-B01 1/1 concluído, 2026-10-14
- [x] `tests/Kit/ConfiguracoesDoKitTelaTest.php` (CT-08, CT-09, CT-57) — testes verdes: Kit 124/124, Tenancy 44/44, ConfiguracoesDoKitTela 38/38, Browser CT-B01 1/1 concluído, 2026-10-14
- [x] `tests/Kit/PermissoesDeAcoesTest.php` (CT-37, CT-41) — testes verdes: Kit 124/124, Tenancy 44/44, ConfiguracoesDoKitTela 38/38, Browser CT-B01 1/1 concluído, 2026-10-14
- [x] `tests/Tenancy/PermissoesDeAcoesTenancyTest.php` (CT-42) — testes verdes: Kit 124/124, Tenancy 44/44, ConfiguracoesDoKitTela 38/38, Browser CT-B01 1/1 concluído, 2026-10-14
- [x] `tests/Browser/DockDeAtalhosTest.php` (CT-B01) — testes verdes: Kit 124/124, Tenancy 44/44, ConfiguracoesDoKitTela 38/38, Browser CT-B01 1/1 concluído, 2026-10-14

## Tickets

Fatiamento confirmado: 2026-10-13 — gsferro — 1 rodada, 3 perguntas (Q25–Q27, desenho)

- [x] [01](07-tickets/01-dock-aparece-por-painel-e-permissao.md) quem tem Usar:Dock vê o dock nos painéis ligados — concluído — 2026-10-14, aprovado por gsferro (autorização de execução contínua); CT-01…07, 37, 41, 42, 59 verdes (`pest tests/Kit/DockDeAtalhosTest.php` 23/23, Tenancy CT-04 2/2, Permissoes 61/61); suíte completa sem regressão contra a baseline
- [x] [02](07-tickets/02-dock-sem-consulta-extra-ao-banco.md) ligar o dock não acrescenta consulta ao banco — concluído — 2026-10-14, aprovado por gsferro (autorização de execução contínua); CT-35, 36, 56, 65 verdes (`pest tests/Kit/DockDeAtalhosTest.php --filter="CT-(35|36|56|65)\]"` 5/5); suíte completa sem regressão contra a baseline
- [x] [03](07-tickets/03-usuario-gerencia-favoritos-no-slideover.md) o usuário gerencia os favoritos no slideover — concluído — 2026-10-14, aprovado por gsferro (autorização de execução contínua); CT-13…21 verdes (`pest tests/Kit/DockDeAtalhosTest.php` 29/29), CT-22, CT-50 verdes (Tenancy 5/5); suíte sem regressão de código contra a baseline
- [x] [04](07-tickets/04-layout-e-estilo-do-dock.md) layout e estilo do dock escolhíveis — concluído — 2026-10-14, aprovado por gsferro (autorização de execução contínua); CT-08, 09, 57 verdes (`pest tests/Kit/ConfiguracoesDoKitTelaTest.php` 38/38), CT-10, 11, 12 verdes; suíte sem regressão de código contra a baseline
- [x] [05](07-tickets/05-adicionar-esta-pagina-com-teto.md) adicionar a página atual aos favoritos, até 10 — concluído — 2026-10-14, aprovado por gsferro (autorização de execução contínua); CT-30…34, 38, 39, 48, 49 verdes (`pest tests/Kit/DockDeAtalhosTest.php` 111/111); suíte completa 4424 testes, só a falha pré-existente da baseline
- [x] [06](07-tickets/06-favoritos-entre-paineis-e-ocultos.md) favoritos de outro painel e favoritos ocultos — concluído — 2026-10-14, aprovado por gsferro (autorização de execução contínua); CT-23, 24, 25, 45, 46, 47, 53, 54, 60 verdes (`pest tests/Kit/DockDeAtalhosTest.php` 111/111) — comportamento entregue pelo código dos tickets 03 e 05; suíte completa sem regressão
- [x] [07](07-tickets/07-favoritos-presos-a-organizacao.md) favoritos do painel de organização presos à organização — concluído — 2026-10-14, aprovado por gsferro (autorização de execução contínua); CT-26…29, 40, 43, 44, 51, 52, 55, 58, 61…64 verdes (`pest tests/Tenancy/DockDeAtalhosTenancyTest.php` 38/38, Kit 111/111); passo 10 (docs pt/en, CHANGELOG, READMEs) com os 24 testes de docs de `tests/Kit` verdes; suíte completa 4424 testes, só a falha pré-existente da baseline
- [x] [08](07-tickets/08-achados-da-revisao-do-diff.md) achados da revisão do diff — concluído — 2026-10-14, aprovado por gsferro (autorização de execução contínua); CT-71…75 verdes
- [x] [09](07-tickets/09-dock-no-tema-escuro.md) o dock acompanha o tema escuro do painel — concluído — 2026-10-14, aprovado por gsferro; CT-B02 3/3 verde

## Verificação Final

- [x] Passe de excesso de complexidade no diff — feito em linha pela sessão, sem invocar a skill `ponytail-review` sobre o diff final (a skill está instalada sem o namespace `ponytail:` e foi usada no step 6); sem cortes aplicados: os candidatos (`organizacaoDoRequest` × `organizacaoDoDestino`, `podeAbrir` público) reabririam código recém-falsificado; dívida declarada *(alterado em 2026-10-14: QA-04, ciclo 1 do quality gate)*, 2026-10-14
- [x] `vendor/bin/pint --dirty --format agent` e `vendor/bin/filacheck --fix` — pint `passed`, filacheck "All 17 rules passed!", 2026-10-14
- [x] Testes do dock — `pest tests/Kit/DockDeAtalhosTest.php` 124/124, `pest tests/Tenancy/DockDeAtalhosTenancyTest.php` 44/44, `ConfiguracoesDoKitTelaTest` 38/38, `PermissoesDeAcoes*` verdes, 2026-10-14
- [x] `pest tests/Browser/DockDeAtalhosTest.php` — CT-B01 1/1 (1º run timeout de navegador frio, c; 2º verde sem alteração), 2026-10-14
- [x] Suíte completa contra a baseline de `main` — `php artisan test --testsuite=Unit,Feature,Kit,Tenancy --parallel`: 4443 testes, falhas = `AgentesDaEsteiraTest` (baseline) + badge do README (corrigido, `SiteDeDocumentacaoTest` 68/68), 2026-10-14
- [x] `pest --mutate --path=app/Support/DockDeAtalhos.php` (no Windows via `pestw.cmd`) — sessão: `tests/Kit/DockDeAtalhosTest.php --mutate --path=app/Support/DockDeAtalhos.php --covered-only --parallel` = 316 mutantes cobertos, 0 sobreviventes, 671,57 s; **sem `--covered-only`** (medido pelo gate, ciclo 2): Kit sozinho 80 não cobertos, 79,80 %, 605,75 s; Kit + Tenancy (`--testsuite=Kit,Tenancy --filter=DockDeAtalhos --no-cache`) 396 mutantes, 364 tested + 1 timeout, 0 sobreviventes cobertos, **31 não cobertos, 92,17 %**, 658,29 s — os 31 viram débito QA-06 *(alterado em 2026-10-14: QA-07, ciclo 2 do quality gate)*; `.env` md5 OK antes/depois, 2026-10-14
- [x] Custo medido — CT-35, 36, 56, 65 (ligado × desligado × sem plugin) e CT-71, 72 (0 × 3 favoritos, P-04) verdes, 2026-10-14
- [x] `/code-review high main...HEAD` + passe de eixos (step 9) — 14 achados, 12 confirmados e roteados, 2 rejeitados com motivo (`## Revisão do Diff`), 2026-10-14
- [x] Desvios propagados — `01` (passos 4/6, Mapeamentos) e `02` (ADR-01, Superfície Livewire) com `*(alterado em 2026-10-14: …)*`, 2026-10-14
- [x] `rastreabilidade.sh` silencioso — exit 0, 2026-10-14
- [x] `checkbox-sem-evidencia.sh` silencioso — exit 0 (rodado antes desta edição; reconferido no fechamento), 2026-10-14
- [x] `citacoes.sh` silencioso — exit 0, 2026-10-14
- [x] `ids-ct.sh` silencioso — `ids-ct.sh {wiki} 'tests/**/DockDeAtalhos*.php' 'tests/Kit/ConfiguracoesDoKitTelaTest.php' 'tests/Kit/PermissoesDeAcoesTest.php' 'tests/Tenancy/PermissoesDeAcoesTenancyTest.php'` exit 0, 2026-10-14
- [x] `conformidade-rules.sh` silencioso — exit 0 (15 rules com linha), 2026-10-14
- [x] Falsificabilidade dos CT do step 9 — 6 de 9 falham sem a correção; CT-67/68 prova pendente (arranjo), CT-73 invariante (ver `## Revisão do Diff`), 2026-10-14
- [x] Docs pt/en, CHANGELOG e README reconciliados — 24 testes de docs de `tests/Kit` verdes; frase `mod+.` sem RQ removida, 2026-10-14
- [ ] `git commit`

## Revisão do Diff (step 9)

Falsificabilidade (2026-10-14, no próprio repositório, escopo estreito — sem agente concorrente): 4 arquivos de `app/` copiados e com md5 anotado, restaurados para o commit 513a500 (sem as correções), CT-66…70, 72…75 rodados, arquivos devolvidos e `md5sum -c` OK. Árvore temporária (`git worktree` + junção do `vendor`) descartada: o Pest resolvia as classes de teste no caminho original. **6 de 9 falham sem a correção**: CT-66, 69, 70, 72, 74, 75. CT-67 e CT-68: prova pendente — o arranjo não monta o mapa do Shield antes do slideover (a `@premissa` de mecanismo que o 04 declara); RD-01 segue provado pelo CT-66 por HTTP. CT-73: invariante que já valia antes da correção (as duas leituras da Q28). CT-71 não entrou na rodada (vermelho com e sem correção até o ajuste da P-04)


| ID | Passe | Achado | Destino | `P-nn` / CT | Rejeitado — motivo |
|---|---|---|---|---|---|
| RD-01 | eixos | memo `once()` do Shield não troca com o painel em `noContexto()`: Page de outro painel abre sem `View:` (fail-open do `HasPageShield`) | teste (viola RQ-04, RQ-15) → CT → correção | — / CT a derivar | — |
| CR-01 | `/code-review` | após salvar no slideover, o componente do vendor reaproveita `cachedActions` e o dock mostra os atalhos antigos no mesmo request | teste (viola RQ-10, RQ-30) → CT → correção | — / CT a derivar | — |
| CR-02 | `/code-review` | `avaliar()` troca contexto e recarrega papéis por favorito do painel corrente | premissa → CT → correção | P-01 / CT a derivar | — |
| CR-03 | `/code-review` | favorito de organização excluída preservado para sempre e contando no teto | premissa → CT → correção; pergunta Q28 | P-02 / CT a derivar | — |
| CR-05 / RD-02 | ambos | hook `call` só cobre o 1º argumento de `mountAction`; `callMountedAction('x')` etc. dão 500 | teste (viola RQ-13, CT-05) → CT → correção | — / CT a derivar | — |
| RD-03 | eixos | "Adicionar esta página" que vira no-op não dá retorno | premissa → CT → correção | P-03 / CT a derivar | — |
| CR-04 | `/code-review` | `catalogo()` sem memo por request | correção (custo, sem mudança de comportamento) | — | — |
| RD-04 | eixos | comentário que dispensa `canAccessPanel` no painel corrente sem citar o vendor | correção do comentário | — | — |
| RD-05 | eixos | `canAccessPanel()` chamado pelo dock grava warning no canal `autenticacao` | correção (log não é CT) | — | — |
| CR-08 | `/code-review` | `->in()` redundante em `dock_layout`/`dock_estilo` | simplificação | — | — |
| CR-09 | `/code-review` | comentários inline (um falso, ver CR-01) | correção com CR-01 | — | — |
| CR-06 | `/code-review` | duplica o filtro de `DescobreCardsDoPainel` | — | — | rejeitado: o concern é dos cards do hub (rule `filament.md` cobre CardItem); reaproveitar exige refatorar o hub, fora do escopo |
| CR-07 | `/code-review` | id de organização não canônico ('07') duplica destino | — | — | rejeitado: só alcançável editando o banco; `gravar()` só aceita chaves que o catálogo gera, canônicas |

## Conformidade com Rules

| Rule | Glob que casou | Aplicada / n.a. / violada | Evidência |
|---|---|---|---|
| `app.md` — papel por `ContextoDePapeis`; painel corrente | `app/**` | aplicada | nenhum `assignRole` no diff; `DockDeAtalhos` lê `Filament::getCurrentPanel()` nulo de propósito (exceção da rule: o dock não pode cair no painel padrão) e `habilitadoNoPainel(null)` devolve `false` — `app/Support/DockDeAtalhos.php:habilitadoNoPainel:90`; troca de team_id só por `ContextoDePapeis::em()` |
| `config.md` — sem `(int) env()`; interruptor falha fechado | `config/**` | aplicada | `config/kit.php` lê `KIT_DOCK_PAINEIS` por `explode((string) env(...))`, sem default: vazio = nenhum painel (CT-06, CT-07) |
| `css-filament.md` — utilitárias/`@layer` de vendor | `app/Providers/**` | n.a. | o pacote traz CSS próprio pré-compilado (`public/css/hoceineel/…`), sem utilitária do Tailwind do kit e sem CSS do kit novo; CT-B01 verde no navegador |
| `filament-resources.md` | `app/Filament/**/Resources/**` | n.a. | só citação deslocada em `UserInfolist.php` |
| `filament.md` — Page/Action nascem com permissão; inventário | `app/Filament/**` | aplicada | as três actions do dock com `->authorize('Usar:Dock')` e linhas no `inventarioDeAutorizacao()` (`PermissoesDeAcoesTest` verde); `Usar:Dock` em `custom_permissions` + mapa do `PapeisSeeder` (CT-19 daquele arquivo, CT-37, CT-41, CT-42) |
| `general.md` — dependência privada | `composer.json` | aplicada | `hoceineel/filament-quick-action-dock` é pacote público do Packagist (MIT) |
| `models.md` — `ModeloCacheavel`; `papelDoPainel()` | `app/Models/**` | aplicada | `User` não ganhou Resource novo; `motivoDaNegativaDoPainel()` extraído sem mudar `canAccessPanel()` (8 suítes de User/Paineis 826/826, relato do #25; suíte completa verde) |
| `pages.md` — segredo em formulário | `app/Filament/Admin/Pages/**` | n.a. | os três campos do dock não são segredo |
| `providers-filament.md` — plugin que resolve o painel corrente nos três painéis | `app/Providers/Filament/**` | aplicada | `PluginDoDock::make()` registrado em Admin, App e Infra (o componente usa `QuickActionDockPlugin::get()` pelo painel corrente) |
| `providers.md` — rota do kit | `app/Providers/**` | n.a. | nenhuma rota nova |
| `settings.md` — chave lida por request; três lugares | `app/Settings/**` | aplicada | `dock_paineis/dock_layout/dock_estilo` na classe, no `mapaDeConfiguracao()` e em migration de settings nova; lidas por Closure no render (CT-07 grava pelo settings e governa; `KitInfoTest` 66) |
| `specs.md` — comportamento de vendor com `file:line` | `wikis/specs/**` | aplicada | citações de vendor por símbolo conferidas pelo `citacoes.sh` (exit 0, 2026-10-14) |
| `support.md` — escrita no `.env` | `app/Support/**` | n.a. | o dock não grava `.env` |
| `testes-browser.md` | `tests/Browser/**` | aplicada | CT-B01: aquecimento por `get('/admin')`, `assertPathIs` antes das demais, em série, seletor por atributo do vendor (`x-ref="trigger"`), não por classe |
| `testes.md` — helper em `tests/Pest.php`; papéis por suíte; `/app` boota por GET | `tests/**` | aplicada | helpers compartilhados (`ligarDock`, `concederDock`, `chaveDeAtalho`, `opcoesDoSlideover`…) em `tests/Pest.php`; `admin_app` só na suíte Tenancy (CT-42); `/app` boota por `get('/app')` antes do `Livewire::test()` |

## Quality Gate

- **Ciclo**: 1 · **Veredito**: REPROVADO → especificação · **Data**: 2026-10-14 — 0 Blocker, 2 Major (QA-01, QA-02: o `01` afirmava cast de `dock_layout` e barreiras que o código não tem), 2 Minor (QA-03 termo do glossário; QA-04 passe Ponytail), 1 Cosmético (QA-05); não verificadas D (parcial), G, H, K2 (o `--mutate` ainda rodava). Correções só de texto: `01` passos 6/7 e `## Autorização`, "organização aberta" no `01`/`04`/docs, 3 termos no `wikis/glossario.md`, linha do Ponytail e checklists dos passos no `03`; K2 fechada depois pela sessão com `--covered-only` (316 cobertos, 0 sobreviventes) — o número completo está no ciclo 2
- **Ciclo**: 2 · **Veredito**: APROVADO COM DÉBITO · **Data**: 2026-10-14 — 0 Blocker, 0 Major, 3 Minor, 1 Cosmético; teto por D (parcial), G e H não verificadas (sem Playwright MCP, sem CT-B em tema escuro nem `assertNoAccessibilityIssues()`, log real não lido)
- **Relatório**: `06-relatorio-qa.md`
- **Débito**:
  - QA-03 (Minor) — "organização corrente" no `02`, `04` e doc en: **corrigido depois do ciclo 2** pela sessão (só texto), 2026-10-14
  - QA-06 (Minor) — 31 mutantes não cobertos em `DockDeAtalhos` (recusa do servidor em `gravar()` atrás do `Select`; chaves adulteradas no JSON; `habilitadoNoPainel(null)`; logs) — **aberto**, destino `feature-test-design`
  - QA-07 (Minor) — score sem os não cobertos: **corrigido depois do ciclo 2** na `## Verificação Final`, 2026-10-14
  - QA-08 (Cosmético) — docblocks com P-01: **corrigido depois do ciclo 2** (só comentário em `DockDeAtalhos.php`), 2026-10-14
  - Dimensões não verificadas: D (log real), H (acessibilidade) — sem Playwright MCP. **G (tema escuro) fechada depois do ciclo 2**: premissa P-05, CT-B02 (luminância computada: escuro 0,009, claro 1,0, painel escuro com navegador claro 0,009) e capturas escuras em `art/`, 2026-10-14
  - Dívida do passe de complexidade (`## Verificação Final`) e Q28 aberta ao solicitante (P-02 implementada pela recomendação)

## Candidatos a Rule

- 2026-10-14 — não executado: a `requirement-to-rule` exige o próprio prompt de aprovação ao solicitante antes de gravar rule, e a execução foi autorizada sem perguntas; o gate (ciclos 1 e 2) não apontou candidato que passe em *Vale virar rule*. Pendente para o solicitante invocar

## Auditoria Pré-Implementação

Autorização de execução contínua: 2026-10-13 — gsferro — "rode todos os tickets em sequência sem me perguntar… do início ao fim… em paralelo caso seja possível"; vale como aprovação de cada ticket (`concluído`); commit e PR autorizados em seguida pelo mesmo solicitante ("ao finalizar tudo, pode comitar e abrir o PR para o CI rodar"), numa branch nova a partir de `main`

Entendimento confirmado: 2026-10-13 — solicitante (gsferro), Adendo 1 — 1 rodada; perguntas: 0 fato, 2 desenho (Q9, Q10), 8 requisito (Q1–Q8). Rodadas 2–5: Q11–Q13 (Adendo 2), Q14–Q19 (Adendo 3), Q21–Q23 (Adendo 4), Q24 (Adendo 5) — requisito, aprovadas pelo solicitante; Q20 desenho, decidida pela sessão (D9). Fronteira vazia. Sinal de escopo: 23 perguntas de requisito (< 30)

Baseline `main` (2026-10-13): `php artisan test --testsuite=Unit,Feature,Kit,Tenancy --parallel` — 4253 testes, 4248 passed, 3 skipped, 2 failed pré-existentes: `AgentesDaEsteiraTest` ("todo agente das skills está em .claude/agents idêntico ao da skill" — `fw-qa-gate.md` de outra versão) e o teste "mantém os números objetivos dos READMEs sincronizados com a árvore"

### Confronto código × afirmação (step 5)

| Pergunta | O `01` dizia | O código faz | Resposta (quem, data) | Onde a wiki mudou |
|---|---|---|---|---|

### Revisão profunda (step 5) — premissas do plano contra o código real

| Premissa do plano | O código real diz | Correção aplicada na wiki |
|---|---|---|
| `getUrl(panel:, tenant:)` em Resource e Page | confirmado: `vendor/filament/filament/src/Resources/Resource/Concerns/CanGenerateUrls.php:getUrl():16`, `vendor/filament/filament/src/Pages/Page.php:getUrl():91` | citações acrescentadas ao `01` |
| `Filament::setCurrentPanel()`/`setTenant()` existem para a troca de contexto | confirmado: `vendor/filament/filament/src/FilamentManager.php:setCurrentPanel():885`, `vendor/filament/filament/src/FilamentManager.php:setTenant():899` | citações acrescentadas ao `01` |
| Inventário só precisava das linhas das Actions | o CT-15 de `PermissoesDeAcoesTest` também lista permissões novas por nome | passo 9 ganha `Usar:Dock` no dataset |
| Settings novo só exigia propriedade + mapa + migration | `KitInfoTest` tem âncora escrita à mão (63) | passo 9 ganha a âncora 63 → 66 |
| Nenhuma doc a tocar | `docs/{pt,en}/` são obrigatórios nos dois idiomas (`docs/pt/operacao/desenvolvendo-o-kit.md`) | passo 10 criado |
| Citação `tests/Kit/KitInfoTest.php` na linha 189 | linha real 188 | corrigida; `citacoes.sh` exit 0, 2026-10-13 |

### Varredura da classe irmã (step 5)

- **Settings `dock_paineis`** × irmã `dashboard_dinamico_paineis` (`grep -rnF`): propriedade, mapa, migration de settings, `Select` da Page, `DashboardDinamicoTest`, `KitInfoTest` (âncora). Todas previstas no plano depois da correção acima
- **Permissão `Usar:Dock`** × irmã `Manage:Dashboard`: `custom_permissions`, mapa do `PapeisSeeder`, subtração do `panel_user`, CT-15/CT-19 de `PermissoesDeAcoesTest`, `DiagramasDaArquiteturaTest::mapaCasoDeUsoParaPermissao()` (mapa de caso de uso dos diagramas). O último não entra: o dock não é caso de uso dos diagramas de arquitetura — decisão registrada, não esquecimento
- **`DockDeAtalhos`** × irmã `DashboardDinamico`/`CabecalhoDoPainel`: nenhuma lista as enumera. Nenhuma ocorrência além das previstas
- **`PluginDoDock`**: sem irmã (primeira fábrica de plugin fora dos providers); o registro nos três providers é a lista paralela, prevista no passo 9

### Auditoria Ponytail (step 6)

| # | Sugestão de corte | Aplicada? | Onde |
|---|---|---|---|
| 1 | DTO `AtalhoDoDock` para 3 campos: gravar a chave D6 como `list<string>` | sim | `01` passo 6 e 7; `02` ADR-02 |
| 2 | `User::atalhosDoDock()`/`gravarPreferenciasDoDock()` duplicavam o corte em 10 de `gravar()` | sim | `01` passo 6 |
| 3 | Log de entrada malformada: `podeAbrir()` já nega | sim | `01` passo 6 |
| 4 | `KIT_DOCK_LAYOUT`/`KIT_DOCK_ESTILO` sem uso por ambiente | sim | `01` passo 3 e Variáveis de Ambiente |
| 5 | Risco do ADR-02 apontava método removido | sim | `02` ADR-02 |

`/ponytail-review` (skill `ponytail-review`, sem o namespace `ponytail:` neste host) — net estimado −60 linhas, 2026-10-13. Mudança < 3 arquivos: sem segunda rodada.

## Despachos

| # | Step | Agente / tarefa | Modelo | Não recebeu | Resultado | Custo | Auditoria do retorno |
|---|---|---|---|---|---|---|---|
| 1 | 0–4 | Sem despacho — captura verbatim, entrevista direta com o solicitante e pesquisa curta com leitura direta (greps pontuais, sem mapeamento amplo) | — | — | — | — | — |
| 2 | 7 | `general-purpose` como analista — derivação do `04`/`05` pela `feature-test-design` (agente `analista` inexistente em `.claude/agents/`) | opus | conversa; `03`; do `01` só paths/rotas/UI; do `02` só Superfície Livewire | `04` (37 CT, 14 regras, 72 mutantes, 1 sem matador), `05` (1 CT-B), 7 perguntas (Q14–Q20) | 303.338 tokens · 1.051 s | presença ok (delimitadores `<<<04`/`<<<05`); integridade: hash antes não capturado pela sessão — `git status --porcelain` mostra só a pasta da wiki untracked, sem arquivo fora dela; amostragem: linha `Cenários: 37 · Regras: 14` confere com `grep -o 'CT-[0-9]*\]' \| sort -u \| wc -l` = 37; conflito do `gravar()` com RQ-19 confirmado no `01` passo 7 |
| 3 | 7 | mesmo agente retomado por `SendMessage` — re-derivação após Adendos 2 e 3 | opus | idem #2 | `04` com 50 CT, 21 regras, 103 mutantes, 1 sem matador; `05` inalterado; 3 perguntas (Q21–Q23); 3 contradições do plano (M73, M91, M102) | 446.014 tokens · 719 s | presença ok; rascunho do scratchpad = texto devolvido (`Cenários: 50` na linha 22; `grep -c '^\| CT-[0-9]* \|'` = 50); contradições M91/M102/M73 já resolvidas no `01` passo 7 pelo Adendo 3; a linha `gerenciarAtalhos` da Superfície Livewire do `02` ainda dizia "corte em 10" — corrigida |
| 4 | 7 | `fw-adversario-ct` — revisão adversarial do `04`/`05` | opus (agente) | `01`, `02`, `03`, código, conversa | 22 achados (12 implementações que passam, 6 oráculos fracos, 1 cenário malformado, 3 sondas), 8 tentativas rejeitadas; leituras negadas: nenhuma | 98.802 tokens · 249 s | presença ok; leu só `00`, `04`, `05`, `wikis/glossario.md` e a reference (declarado); amostragem: ADV-17 confere (CT-33 linha 8 "e a página atual" sem posição), ADV-14 confere com o fato da pilha (`permission.teams` filtra papéis pelo `team_id` da organização — `DefinirTenantDePermissoes`) |
| 5 | 7 | mesmo derivador retomado por `SendMessage` — fechar ADV-01…ADV-21 e Adendo 4 | opus | idem #2 | `04` com 60 CT, 22 regras, 122 mutantes, 1 sem matador; 20 ADV fechados, ADV-07 rejeitado com motivo; matriz 16/16 sem lacuna; `05` inalterado; sem perguntas novas | 590.313 tokens · 785 s | rascunho = texto devolvido (`Cenários: 60` linha 24; `grep -c '^\| CT-[0-9]* \|'` = 60); `rastreabilidade.sh` exit 0 silencioso, 2026-10-13; achado de plano (M110/M121) confere com o `01` passo 7 já reescrito nos Adendos 3/4 — sem edição nova |
| 6 | 7 | `fw-adversario-ct` retomado — rodada 2 (última) | opus (agente) | idem #4 | 18/21 fechamentos conferidos (ADV-03 e ADV-06 parciais; rejeição do ADV-07 derrubada); 9 achados novos (ADV-22…ADV-30) + ADV-31/32; 1 pergunta de requisito (Q24 → Adendo 5) | 175.753 tokens · 149 s | presença ok; leituras negadas: nenhuma; amostragem: ADV-22 confere com a nota do próprio CT-56 ("cache já aquecido no desligado"); ADV-29 confere (CT-59 em G1 com Então por HTTP) |
| 7 | 7 | derivador retomado — correção final pós-rodada 2 + Adendo 5 | opus | idem #2 | `04` com 65 CT, 22 regras, 131 mutantes, 2 sem matador (M58, M130 — lacunas de pilha declaradas); `05` inalterado; sem perguntas novas | 686.079 tokens · 593 s | `Cenários: 65` linha 25 = `grep -c '^\| CT-[0-9]* \|'` 65; `rastreabilidade.sh` e `citacoes.sh` exit 0, 2026-10-13; achado de plano M121/M127 conferido contra o `01` passo 7 — regras (1) e (3) de `gravar()` já recusam a inválida reenviada e preservam o favorito sem acesso: sem edição; teto de 2 rodadas adversariais atingido |
| 8 | ticket 01 | `general-purpose` com contrato do `executor-ct` — testes de CT-01…07, 37, 41, 42, 59 (fallback: `fw-executor-ct` com `maxTurns` 40 corta lote de 11 CT — perde hook e restrição de ferramenta) | sonnet | `01`, `02`, `03`, outros tickets | 11 CT escritos (39 casos), todos vermelhos (b): coluna, config e permissão ausentes; helpers `ligarDock`, `concederDock`, `chaveDeAtalho`, `gravarAtalhos`, `atalhosGravados`, `dockNaPagina` em `tests/Pest.php` | 192.635 tokens · 412 s | `git status`: só `tests/` alterado (+ arquivos novos de teste); nenhum `app/`; ambiguidades do 04 resolvidas e declaradas (gatilho do dock, link por diferença de contagem, chamada direta via `mountedActions`) |
| 9 | ticket 01 | `general-purpose` como `construtor` — passos 1–5, 7–9 | opus | outros tickets, 03, 01 fora dos passos 1–5, 7–9 | código dos passos 1–5, 7–9 do ticket 01; 20/23 Kit + 2/2 Tenancy + 61/61 permissões; 3 vermelhos (a) — o teste procurava `quick-action-dock`, que o pacote põe em toda página (CSS/JS); corrigido pela sessão para `wire:name="quick-action-dock"`; 3 citações em `tests/Pest.php`/`KitArteTest`/`LinkDoPainelSemTenancyTest` deslocadas, corrigidas pela sessão | 280.475 tokens · 3.036 s | pint, filacheck 17/17 e PHPStan limpos (relato); amostragem: `pest tests/Kit/DockDeAtalhosTest.php` 23/23 e `CitacoesDeCodigoTest` 3/3 rodados pela sessão; suíte completa: 4324 testes, falhas = 2 da baseline + 2 do `SiteDeDocumentacaoTest` (números do README, reconciliação do step 10) + 22 erros dos CT do ticket 03 (esperados) |
| 10 | ticket 02 | `general-purpose` com contrato do `executor-ct` — CT-35, 36, 56, 65 (paralelo ao #9: arquivos disjuntos, `tests/` × `app/`) | sonnet | `01`, `02`, `03`, outros tickets | CT-35, 36, 56, 65 escritos (5 testes) e já verdes contra a implementação em andamento do #9; 2 vermelhos intermediários (a) corrigidos no próprio teste (usuário em memória sem `dock_atalhos`; 500 transitório da implementação em escrita, c) | 133.836 tokens · 593 s | só `tests/Kit/DockDeAtalhosTest.php` alterado; deriva de +6 consultas `model_has_roles` por request no mesmo processo, sem relação com o dock — neutralizada por soma em ordem simétrica no CT-56; registrada em Notas |
| 11 | ticket 03 | `general-purpose` com contrato do `executor-ct` — CT-13…22, 50 (paralelo ao #9: `tests/` × `app/`) | sonnet | `01`, `02`, `03`, outros tickets | 11 CT escritos (32 execuções); CT-13, 14, 18, 19 verdes; 19 vermelhos (b) — slideover sem formulário (esperado antes do ticket 03); helper `opcoesDoSlideover()` em `tests/Pest.php`; CT-50 num teste só (asserção cruza as linhas) | 169.932 tokens · 377 s | só `tests/` alterado; agente relatou que `tests/Kit/DockDeAtalhosTest.php` mudou no disco durante a escrita — conferir no retorno do #9 se o construtor tocou o arquivo |
| 12 | ticket 03 | `general-purpose` como `construtor` — passos 6, 7, 8 (catálogo, gravação, slideover) | opus | outros tickets, 03, 01 fora dos passos 6–8 | catálogo, `gravar()` e slideover; 14 vermelhos (a) — `assertHasNoFormErrors()` após fechar a action (CT-15/16) e URL calculada antes da troca de request (CT-17) — corrigidos pela sessão no teste; citação de enum do vendor com símbolo errado corrigida (`SpeedDial`, `Native`) | 180.154 tokens · 735 s | pint, filacheck, PHPStan limpos (relato); CT-13…21 29/29 rodados pela sessão |
| 13 | ticket 01 (CT-B01) + ticket 04 (testes) | **Codex CLI 0.162.0** (`codex exec --yolo`, autorizado pelo solicitante em 2026-10-14) com os contratos `fw-executor-ctb`/`fw-executor-ct` (Markdown + perfis `agents/codex/*.toml`) — sem sandbox nem hook: limite só contratual, conferido por `git status --porcelain` antes/depois | modelo padrão do Codex (não exposto) | `01`, `02`, `03`, outros tickets | CT-B01 verde (1º run timeout c, 2º verde sem alteração); CT-08, 57, 10, 11, 12 verdes; CT-09 linha "painéis como texto" vermelha (b) — a tela aceita texto em `dock_paineis` — para o construtor do ticket 04; 2 vermelhos (a) corrigidos no teste; seletores do vendor citados (`dock.blade.php` `x-ref="trigger"`, `data-layout`, `data-qad-style`) | — (host não reporta) | `git status --porcelain` antes × depois: só `tests/Kit/ConfiguracoesDoKitTelaTest.php` (M) e `tests/Browser/DockDeAtalhosTest.php` (??) novos no diff — `tests/Kit/DockDeAtalhosTest.php` já estava modificado antes; dentro do contrato |
| 14 | ticket 05 (testes) | Codex CLI (`codex exec --yolo`) com contrato do `fw-executor-ct` — CT-30…34, 38, 39, 48, 49 (paralelo ao #12: `tests/` × `app/`) | modelo padrão do Codex (não exposto) | `01`, `02`, `03`, outros tickets | 9 CT (25 execuções): 14 verdes, 11 vermelhos (b) — `adicionarPaginaAtual` oculta, recusa do 11º sem aviso, CT-48 sem link (possível host, a conferir) | — | `git status` antes × depois sem arquivo novo fora de `tests/Kit/DockDeAtalhosTest.php` (já modificado) |
| 15 | ticket 04 | Sem despacho — 1 passo: `->rule('array')` em `dock_paineis` da Page de configurações (CT-09 "painéis como texto") | — | — | CT-08, 09, 57 13/13 | — | `pest tests/Kit/ConfiguracoesDoKitTelaTest.php` 38/38 |
| 16 | ticket 05 | `general-purpose` como `construtor` — passos 7, 8 (página atual, teto com aviso, sem duplicata, fim da lista) | opus | outros tickets, 03, 01 fora dos passos 7–8 | `paginaAtualComoAtalho()`, `adicionarPaginaAtual()` (fim da lista, teto contando toda entrada, sem duplicata), aviso "Limite de 10 atalhos" na adição e no slideover (`halt()`); `gravar()` devolve `bool`; página do painel corrente pula `noContexto` (CT-56 voltava a 140×128 consultas); 10 vermelhos (a): host da URL (CT-30, 48, 49) e leitura do aviso depois de `assertNotified()` (CT-33, 34, 47) | 165.096 tokens · 900 s | pint, filacheck, PHPStan limpos (relato); LF conferido; Kit 98/108, Tenancy 5/5, permissões+citações+configurações 79/79 (relato) — conferência da sessão após o #18 |
| 17 | ticket 06 (testes) | Codex CLI (`codex exec --yolo`) com contrato do `fw-executor-ct` — CT-23, 24, 25, 45, 46, 47, 53, 54, 60 (paralelo ao #16: `tests/` × `app/`) | modelo padrão do Codex (não exposto) | `01`, `02`, `03`, outros tickets | 9 CT (19 execuções): 16 verdes, 3 vermelhos (b) no CT-47 — o aviso do limite não cita "10" (aviso é do ticket 05, em andamento) | — | `git status` antes × depois sem arquivo novo; `tests/Kit/DockDeAtalhosTest.php` em LF (`grep -c $''` = 0) |
| 18 | ticket 07 (testes) | Codex CLI (`codex exec --yolo`) com contrato do `fw-executor-ct` — CT-26…29, 40, 43, 44, 51, 52, 55, 58, 61…64 | modelo padrão do Codex (não exposto) | `01`, `02`, `03`, outros tickets | 15 CT (36 execuções) escritos; todos verdes menos CT-44 "teto" (não reexecutado: teto de iterações); comportamento já entregue pelos tickets 03 e 05 | — | `git status` sem arquivo novo; LF nos dois arquivos |
| 19 | tickets 05–07 | Sem despacho — correção (a) de teste pela sessão: `ocorrenciasDoLink()`/ordem dos links por caminho, independente do host (CT-30, 48, 49); leitura do aviso antes do `assertNotified()`, nas chaves `filament.notifications` e `claimed_notifications` (CT-33, 34, 47, 44) | — | — | Kit 111/111, Tenancy 38/38 | — | suíte completa: 4424 testes, 1 falha = `AgentesDaEsteiraTest` da baseline; CT-B01 verde (1º run timeout de navegador frio, c) |
| 20 | ticket 07 (passo 10) | `general-purpose` — docs pt/en, CHANGELOG, números do README | sonnet | `03`, outros tickets | docs pt/en (`hub-de-navegacao.md`, `configuracoes-do-kit.md`), CHANGELOG, READMEs (números) — cada parágrafo com RQ declarada; 2 frases sem RQ apontadas pelo próprio agente: `mod+.` (removida pela sessão, regra do step 10 item 7) e rótulos de layout/estilo, que seguem o código e divergem da tabela Mapeamentos do `01` (reconciliar o `01`) | 89.706 tokens · 886 s | 13 arquivos de teste de docs rodados pela sessão, todos verdes; LF conferido pelo agente |
| 21 | 9 | `fw-revisor-diff` — passe de eixos sobre `main...HEAD` sem `wikis/` (commits 27156e3, 513a500) | opus (agente) | `01`, `03`, raciocínio da sessão | 5 achados (RD-01 major; RD-02…RD-05 minor) + eixos percorridos + não verificado declarado | 139.270 tokens · 382 s | `git status` antes × depois: só `03-progresso.md`, gravado pela sessão (registro do despacho #22) durante a revisão — explicado, achados aceitos; RD-01 conferido pela sessão em `Paineis.php` (comentário do `once()`) e `tests/Pest.php` (`noPainelDoShield`) |
| 22 | 9 | `/code-review high main...HEAD` (skill, execução em fork) | o do comando (não exposto) | conversa | 9 achados não verificados (CR-01…CR-09): dock com atalhos velhos após salvar no mesmo request; troca de contexto por favorito do painel corrente; favorito de organização excluída preservado para sempre; `catalogo()` sem memo; guarda do hook só no 1º parâmetro; duplicação de `DescobreCardsDoPainel`; id de organização não canônico; `->in()` redundante; comentários inline | 118.941 tokens · 227 s | consolidação com o passe de eixos (#21) antes do roteamento |
| 23 | 9 | derivador (`general-purpose`, retomado) — CT dos achados do step 9 via `feature-test-design` | opus | `01`, `03`, código | CT-66…CT-75 em R23…R28 (origem RQ-04/15, RQ-10/30, RQ-13, P-01, P-02, P-03); nenhum CT antigo mudou; 75 CT, 148 mutantes | 773.712 tokens · 595 s | `Cenários: 75` na linha 26; `rastreabilidade.sh` exit 0, 2026-10-14; P-02 derivada pela ➡️ da Q28 aberta, por instrução da sessão — divergência declarada no `04` |
| 24 | 9 | Codex CLI (`--yolo`) — testes CT-66…CT-75 | modelo padrão do Codex (não exposto) | `01`, `02`, `03` | 10 CT (16 execuções): 12 verdes contra a correção em andamento (#25); 4 vermelhos (b): CT-71/72 (+2 consultas), CT-75 (sem aviso) | — | `git status` antes × depois: tests/ só nos 2 arquivos combinados; mudanças em `app/` são do #25, concorrente |
| 25 | 9 | `general-purpose` como `construtor` — correções RD-01, CR-01…05, CR-08, CR-09, RD-03…05 | opus | `03`, raciocínio da sessão | 7 achados corrigidos (RD-01, CR-01, CR-03, CR-04, CR-05/RD-02, RD-03, RD-04, RD-05, CR-09), CR-08 mantido (tirar `->in()` quebrou o CT-09), CR-02 parcial: 1 consulta por favorito Resource vinda do `Gate::before` → `isMasterGlobal()` (global, sem memo) — premissa P-01 substituída por P-04 pela sessão; `Paineis::shieldNovo()` público; `User::motivoDaNegativaDoPainel()` sem log | 237.456 tokens · 1.482 s | pint, filacheck, PHPStan limpos (relato); Kit 122/123, Tenancy 41/42 (CT-71/72); 8 suítes de User/Paineis 826/826 (relato); citações de `tests/` deslocadas corrigidas pela sessão (`CitacoesDeCodigoTest` 3/3) |
| 26 | 9 | Codex CLI (`--yolo`) — reescrita de CT-71/72 para a P-04 | modelo padrão do Codex (não exposto) | `01`, `02`, `03` | CT-71 2/2, CT-72 3/3; arquivos inteiros Kit 124/124, Tenancy 44/44 | — | `git status` sem arquivo novo; LF |
| 27 | 11 | `fw-qa-gate` — quality gate (`.claude/agents/fw-qa-gate.md` de versão anterior à de `.ai/skills`: falha pré-existente do `AgentesDaEsteiraTest`, não alterada neste PR) | opus (agente) | conversa | `06` ciclo 1: REPROVADO → especificação (2 Major de texto, 2 Minor, 1 Cosmético); 8/12 dimensões | 262.774 tokens · 1.402 s | `06` gravado verbatim entre `<<<06`/`>>>06` (o transcript não expôs o texto final ao script; gravado a partir do retorno, só com o escape HTML desfeito); `git status` antes = depois; md5 de `app/` igual; o `03` mudou por edição da sessão (registro da mutação) |
| 28 | 11 | `fw-qa-gate` — ciclo 2 | opus (agente) | conversa | `06` ciclo 2: APROVADO COM DÉBITO (3 Minor, 1 Cosmético); 9/12 dimensões; K2 refeita sem `--covered-only` | 210.525 tokens · 2.863 s | `06` gravado verbatim a partir do retorno; `git status` antes = depois; md5 de `app/` e wiki iguais (exceto o `06`, regravado pela sessão) |
| 29 | pós-11 | `general-purpose` — documentação completa do dock (página própria pt/en, índices, README, site) | sonnet | `03`, raciocínio da sessão | `docs/{pt,en}/recursos/dock-de-atalhos.md`, hub e configurações apontando para ela, sidebar/redirects do site | 117.329 tokens · 786 s | testes de docs verdes (1102 no lote ampliado, 1 falha esperada até o GIF existir); siglas RQ/P removidas do texto de usuário pela sessão |
| 30 | pós-11 | `general-purpose` — capturas e GIF do dock (tema claro) pelo pipeline `CapturaDeArteTest` + `kit:arte` | opus | `wikis/`, docs | 4 PNG + thumbs + `dock-de-atalhos.gif` (5 quadros) | 212.976 tokens · 1.786 s | imagens conferidas visualmente pela sessão; `git status art/` só arquivos novos; `KitArteTest` + `CitacoesDeCodigoTest` 41/41 |
| 31 | pós-11 | derivador retomado — CT-B02 (P-05, tema escuro) | opus | `01`, `03`, código | CT-B02 no `05` (3 linhas, luminância computada) e 2 linhas do `04` | 804.919 tokens · 130 s | o rascunho do `04` no scratchpad era anterior às correções do gate e as desfazia — `04` restaurado do commit e só as 2 linhas novas + R29 aplicadas pela sessão |
| 32 | pós-11 | `general-purpose` — capturas e GIF em tema escuro | opus | `wikis/`, docs | 3 PNG escuros + thumbs + `dock-de-atalhos-escuro.gif`; cenários claros passam a declarar `inLightMode()` | 124.828 tokens · 447 s | imagens conferidas pela sessão; vazamento do tema para o `HubDeCardsTest` (captura `infra-hub`) fechado pela sessão com `inLightMode()` (2/2) |
| 33 | pós-11 | Codex CLI (`--yolo`) — CT-B02 | modelo padrão do Codex (não exposto) | `01`, `02`, `03` | CT-B02 3/3 verde; CT-B01 timeout de navegador frio na 1ª execução (c) | — | `git status` antes × depois: só `tests/Browser/DockDeAtalhosTest.php` além das docs editadas pela sessão |

## Blockers

- nenhum

## Desvios do Plano

- Passo 4/6: migrations com o timestamp do artisan (`2026_10_14_*`), não `2026_10_13_100000` — corrigido no `01` passo 4/6 (step 10)
- Passo 6: `dock_atalhos`/`dock_layout` também em `User::$hidden` (`PageHeaderTest` trava as colunas expostas) — registrado no `01` passo 6 (step 10)
- Passo 8: hook Livewire `call` no `PluginDoDock` recusa nome de action forjado (array) antes do `TypeError` (CT-05) — registrado na Superfície Livewire do `02` (step 10)
- Ambiente: arquivos tocados por ferramentas de edição no Windows ficaram com CRLF na cópia de trabalho (o repositório é `eol=lf`); `ConfiguracoesDoKitTelaTest` CT-50 lê o fonte por regex com `

` e quebrou. 14 arquivos convertidos para LF em 2026-10-14; edições seguintes gravam com `newline='
'`
- Passo 7: avaliação de destino do painel corrente sem `noContexto` (o middleware já fixou painel, tenant e `team_id`) — evita recarregar papéis por avaliação; registrado na ADR-01 do `02` (step 10)
- Passo 4: `dock_paineis` ganhou `->rule('array')` (ticket 04, CT-09)
- Passo 7: `DockDeAtalhos` lê `dock_atalhos` do banco só quando o modelo autenticado não tem a coluna carregada

## Notas de Implementação

- Arnês de teste: no mesmo processo, cada `get('/admin')` seguinte faz +6 consultas `exists` em `model_has_roles` (`User::isMasterGlobal`, `temPapelDoPainel`), com ou sem o dock — causa não identificada, fora do escopo; o CT-56 compara somas em ordem desligado, ligado, ligado, desligado. Candidato a investigação própria

## Referências Abertas

- `template-00-requisito.md` — step 4 — 2026-10-13
- `entrevista-tres-raias.md` — step 4 — 2026-10-13
- `template-01-plano.md` — step 4 — 2026-10-13
- `template-02-adr.md` — step 4 — 2026-10-13
- `template-03-progresso.md` — step 4 — 2026-10-13
- `exemplo-de-fatiamento.md`, `template-ticket.md` (feature-tickets) — passos 4 e 7 — 2026-10-13
- `hosts-e-subagentes.md`, `roteamento-e-despacho.md`, `delegacao-casos-de-teste.md` — step 7 — 2026-10-13

## Retrospectiva

- **Funcionou bem**: o `00` com adendos verbatim segurou o oráculo em cinco rodadas de perguntas; a revisão adversarial às cegas (2 rodadas) e o step 9 pegaram defeitos reais que a suíte verde escondia (fail-open do Shield entre painéis, dock com atalhos velhos no mesmo request); a divisão Claude (código) × Codex (testes) em paralelo com auditoria por `git status`
- **Faltou no plano**: o `01` não previu o memo `once()` do Shield nem o `cachedActions` do componente do vendor — os dois só apareceram no diff; o ambiente Windows gravou CRLF na cópia de trabalho e quebrou um teste que lê fonte por regex; `isMasterGlobal()` sem memo (global do kit) limitou a P-01 — candidato a feature própria
