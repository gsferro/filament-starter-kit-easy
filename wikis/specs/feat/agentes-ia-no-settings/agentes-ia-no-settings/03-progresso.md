# Progresso — Agentes de IA ligados pelo settings, por painel

**Estado**: concluída — 2026-10-19

## Processo do pedido

<!-- Os quatro itens de processo do Texto Original, que não são cláusulas do sistema. -->

- [x] Branch criada — `git checkout -b feat/agentes-ia-no-settings` a partir de `main` (`27e47714`), 2026-10-19
- [x] `codex --yolo` usado como auxiliar — `codex exec --yolo -s read-only` na revisão profunda do plano (despacho 1: 47 premissas conferidas, 5 achados), 2026-10-19
- [x] PR aberto — https://github.com/gsferro/filament-starter-kit-easy/pull/171 (`gh pr create`, base `main`, com o link da wiki e o veredito do `06`), 2026-10-19
- [x] Tag + release criadas depois de o PR passar — CI do PR #171 verde (`instalacao`, `qualidade`, `site`, `telas` — este após duas reexecuções pelos 3 flakes de login/cadastro já conhecidos; Snyk vermelho por cota da conta), merge `db360d58`, tag anotada `v0.51.0` no merge, release https://github.com/gsferro/filament-starter-kit-easy/releases/tag/v0.51.0. **Não feita**: a validação dos quatro cenários do `wikis/checklist-de-release.md` (create-project e kit:update com e sem tenancy) — fica como pendência declarada para o mantenedor, 2026-10-19

## 1. Config `kit.agentes_ia` e `.env.example`
- [x] Bloco `agentes_ia` em `config/kit.php` — `php artisan tinker --execute 'echo json_encode(config("kit.agentes_ia"));'` → `{"habilitado":false,"paineis":[]}`; `php -l` sem erro, 2026-10-19
- [x] `KIT_AGENTES_IA` e `KIT_AGENTES_IA_PAINEIS` no `.env.example` — `grep -n KIT_AGENTES_IA .env.example` → 2 linhas (false; comentada), 2026-10-19

## 2. Settings: duas propriedades e a migration
- [x] Propriedades e linhas do mapa em `ConfiguracoesDoKit` — `php -l` sem erro; `vendor/bin/phpstan analyse` 0 erros, 2026-10-19
- [x] Migration `2026_10_19_100000_add_agentes_ia_to_kit_settings.php` — `vendor/bin/phpstan analyse` 0 erros; semeadura coberta por `ConfiguracoesDoKitTest` ('semeia todas as propriedades que a classe de settings declara'), 2026-10-19

## 3. Decisor `App\Support\AgentesDeIa`
- [x] `habilitado()`, `assistenteNoPainel()` e `chatNaTela()` — `app/Support/AgentesDeIa.php`, `vendor/bin/phpstan analyse` 0 erros, 2026-10-19

## 4. Tela de configurações: seção "Agentes de IA"
- [x] Seção na aba Aplicação com toggle e select — `vendor/bin/filacheck --fix` "All 17 rules passed"; `vendor/bin/pint --dirty` passed, 2026-10-19

## 5. Catálogo: some do menu e responde 404
- [x] `canAccess()` e `shouldRegisterNavigation()` no Resource — `vendor/bin/filacheck --fix` 17/17; phpstan 0 erros, 2026-10-19
- [x] Trait `ExigeAgentesDeIaLigados` nas três Pages — `grep -l ExigeAgentesDeIaLigados app/Filament/Admin/Resources/AgentesIa/Pages/*.php` → 3 arquivos, 2026-10-19

## 6. Widgets do catálogo seguem o interruptor
- [x] `fonteDeDadosDisponivel()` nos dois widgets — `grep -c fonteDeDadosDisponivel app/Filament/Admin/Widgets/AgentesIa*.php` → 1 em cada, 2026-10-19

## 7. Chat do Assistente por painel
- [x] Hook global em `ConfiguraFilamentGlobal::configuraChatDoAssistente()` — phpstan 0 erros; `php artisan about` boota, 2026-10-19
- [x] Hook removido do `AppPanelProvider` — `grep -c assistente-chat-widget app/Providers/Filament/AppPanelProvider.php` → 0 (resta só o comentário que aponta o hook global); Pint removeu o import `Blade`, 2026-10-19
- [x] Guarda em `AssistenteChatWidget::assertContexto()` — phpstan 0 erros, 2026-10-19
- [x] Recuo do "voltar ao topo" por `AgentesDeIa::chatNaTela()` na blade — `grep -n chatNaTela resources/views/filament/voltar-ao-topo.blade.php` → 2 ocorrências (comentário e `$recuoDoBotao`), 2026-10-19

## 8. Lote T0: suíte com o uso desligado e o helper de teste
- [x] `ligarAgentesDeIa()` em `tests/Pest.php` (com `MEDIR_PILHA_FLUTUANTE` e `abrirPainelDaPilha()` movidos, D11) — `php -l tests/Pest.php` sem erro; pint fixed, 2026-10-19
- [x] Casos existentes ligando o uso no arranjo (14 arquivos + `phpunit.xml`, D10) — `php artisan test --compact` em 24 arquivos da Kit (T0 + settings + inventários): 872 testes, 870 verdes, 2 vermelhos em `AssistenteChatCssTest` CT-01 (faltava ligar o uso — corrigido; o arquivo rerodado dá 6/6); `tests/Tenancy/TelasSemDockNemChatTenancyTest.php` + `DockDeAtalhosTenancyTest.php` 46/46, 2026-10-19

## 9. Documentação
- [x] docs pt/en (configurações, atualizando), `wikis/ia.md`, READMEs (bullet da IA e contagem 84 → 85), CHANGELOG — `php artisan test tests/Kit/*Documentacao*Test.php tests/Kit/SiteDeDocumentacaoTest.php` → 124/124 verdes, 2026-10-19

## Testes
<!-- Preenchida no step 7, depois da derivação do 04/05. -->
- [x] `tests/Kit/AgentesIaNoSettingsTest.php` (CT-01 a CT-15, CT-22, CT-25) — `php artisan test --compact tests/Kit/AgentesIaNoSettingsTest.php` 73/73 (278 asserções) depois da correção da causa (b) do CT-11, 2026-10-19
- [x] `tests/Kit/AgentesIaNoSettingsChatTest.php` (CT-16 a CT-21, CT-24, CT-26, CT-27) — `vendor/bin/pest tests/Kit/AgentesIaNoSettingsChatTest.php --compact` 51/51 (259 asserções) com o CT-27 do step 9, despachos 5 e 5b, 2026-10-19
- [x] `tests/Tenancy/AgentesIaNoSettingsTenancyTest.php` (CT-23, CT-28) — `vendor/bin/pest tests/Tenancy/AgentesIaNoSettingsTenancyTest.php --compact` 3/3, 11 asserções, com o CT-28 do step 9 (despachos 6 e 6b), 2026-10-19
- [x] `tests/Browser/AgentesIaNoSettingsTest.php` (CT-B01) — `vendor/bin/pest tests/Browser/AgentesIaNoSettingsTest.php --compact` 2/2 (34 asserções) rerodado pela sessão com os helpers em `tests/Pest.php`; `PilhaDeBotoesFlutuantesTest` + `AssistenteChatEstiloTest` 27/27 na mesma rodada, 2026-10-19
- [x] `tests/Pest.php` — `ligarAgentesDeIa()` (D7) e os helpers da pilha movidos (D11) — `grep -rlc ligarAgentesDeIa tests` → 14 arquivos; `HelpersDeTesteTest` verde na suíte completa, 2026-10-19

## Tickets
<!-- Step 8. -->
Não fatiado — 2026-10-19: 9 RQ vigentes (+6 P-nn vigentes), 26 CT + 1 CT-B, compactação: não, 7 perguntas de requisito — nenhum sinal

## Verificação Final
- [x] `/ponytail:ponytail-review` no diff (validar contra over-engineering) — passe em linha (skill `ponytail-review`) sobre o `01`/`02` no step 6 (2 cortes aplicados) e sobre o diff final: trait com 3 consumidores, decisor de 3 métodos, hook global, helper de teste; `net: 0 lines` além dos cortes do step 6, 2026-10-19
- [x] `vendor/bin/pint --dirty` — `{"tool":"pint","result":"passed"}` depois das correções do step 9, 2026-10-19
- [x] `vendor/bin/filacheck --fix` — "All 17 rules passed!", 2026-10-19
- [x] `vendor/bin/pest tests/Kit/AgentesIaNoSettingsTest.php --compact` — 73/73 (278 asserções); `AgentesIaNoSettingsChatTest.php` 51/51 (259); `tests/Tenancy/AgentesIaNoSettingsTenancyTest.php` 3/3 (11), 2026-10-19
- [x] `vendor/bin/pest tests/Browser/AgentesIaNoSettingsTest.php --compact` — 2/2 (34 asserções); na mesma rodada `PilhaDeBotoesFlutuantesTest` + `AssistenteChatEstiloTest` 27/27 (os dois vermelhos da primeira rodada foram `rename … Acesso negado` no cache de views, colisão com o lote A em paralelo; verdes sozinhos), 2026-10-19
- [x] `php artisan test --testsuite=Browser --compact` (suíte de navegador inteira) — 164 testes: 126 verdes, 37 pulados (guardas `naArvoreDoKit()`/arte, iguais à release anterior), 1 vermelho = o CT-B01 por `rename … Acesso negado` no cache de views (o quality gate rodava testes em paralelo); rerodado sozinho: 2/2, 36 asserções, 2026-10-19
- [x] `php artisan test --testsuite=Kit,Tenancy --parallel`: nada mais no suite quebrou além da **baseline** de `main` (falhas pré-existentes por nome) — `php artisan test --testsuite=Kit,Tenancy --parallel --compact` na árvore final (d9211c96): **4.977 testes, 4.974 verdes, 3 pulados, 0 vermelhos** (baseline: 4.850, 4.846 verdes, 3 pulados, 1 vermelho causado pela própria wiki — resolvido); +127 testes (lotes A/B/C e linhas de dataset), 350 s; a rodada intermediária acusou 4 listas paralelas (`KitInfoTest`, mapa opt-in dos diagramas, contagens dos READMEs, citações de `docs/`), todas corrigidas, 2026-10-19
- [x] `pest --mutate --path=app/Support/AgentesDeIa.php`: no Windows o `--mutate` não mede (`.ai/rules/testes.md`) — amostragem manual de 8 mutantes aplicados um a um e restaurados (script `falsificabilidade.py`, `php vendor/pestphp/pest/bin/pest … --filter`): **8/8 mortos** — M-a trait sem `authorizeAccess()` → CT-11 2 vermelhos (403); M-b chat sem `hydrate()` → CT-27 4/5 vermelhos (controle verde); M-c `habilitado()` sempre `true` → CT-09/CT-11 12 vermelhos; M-d `assistenteNoPainel()` sem a guarda do nulo → CT-19 1 erro (`TypeError` no painel nulo); M-e `chatNaTela()` sem a tela de autenticação → CT-17 5 vermelhos; M-f `ResolvedorDeTenant` pela organização do usuário → CT-28 `/admin` vermelho; M-g widgets sempre visíveis → CT-20 2 vermelhos; M-h `Resource::canAccess()` sem o interruptor → rerodado sozinho com `PYTHONIOENCODING=utf-8` (o script original morreu no `print` do ⌘ antes de gravar o M-h — QA-01): `php vendor/pestphp/pest/bin/pest tests/Kit/AgentesIaNoSettingsTest.php --filter="CT-09|CT-11" --compact` → mutante M-h aplicado: AgenteIaResource::canAccess() sem AgentesDeIa::habilitado() · comando: php vendor/pestphp/pest/bin/pest tests/Kit/AgentesIaNoSettingsTest.php --filter=CT-09|CT-11 --compact · resultado: failed tests= 19 passed= 17 failed= 2 · vermelho: __pest_evaluable_it__CT_09__cada_superf?cie_de_navega?o_lista_o_cat?logo_s?_com_o_uso_ligado with data set "dataset "te · vermelho: __pest_evaluable_it__CT_09__cada_superf?cie_de_navega?o_lista_o_cat?logo_s?_com_o_uso_ligado with data set "dataset "re · restaurado: True (saída em `scratchpad/falsificabilidade-mh.out`: 2 linhas do CT-09 vermelhas, as de telas e registros do ⌘K); `git status -- app` igual antes e depois, 2026-10-19
- [x] **Custo medido**: consultas de `GET /admin` com o uso ligado × desligado, contra o `## Modelo de Execução` — CT-06 (`AgentesIaNoSettingsTest`): soma das consultas à tabela `settings` em dois requests com o uso ligado = soma com o uso desligado, e nenhuma consulta ao `settings` com arquivo da feature na pilha (controle positivo do contador ≥ 1); verde, 2026-10-19
- [x] **`/code-review high main...HEAD` + passe de eixos (step 9)**, antes da reconciliação: achados fechados ou rejeitados com motivo — 12 achados na tabela `## Revisão do Diff (step 9)`: 7 corrigidos, 2 viraram CT (CT-27, CT-28; P-08), 1 para o step 12, 3 rejeitados com motivo, 2026-10-19
- [x] Roteiro "Desenhado × Implementado" do `05-*-browser.md` preenchido — 2 linhas ✅ com a evidência do CT-B01, 2026-10-19
- [x] Desvios propagados ao `01`/`02`/`04`/`05` de origem, marcados `*(alterado em …)*` — `grep -c 'alterado em 2026-10-19' 01-plano-acao.md` → 14; `04` 1 (mais as notas de causa (a) do lote B); `05` 1; os 4 itens de `## Desvios do Plano` têm a edição na fonte, 2026-10-19
- [x] `rastreabilidade.sh {wiki}` silencioso — `bash .claude/skills/feature-wiki/scripts/rastreabilidade.sh wikis/specs/feat/agentes-ia-no-settings/agentes-ia-no-settings` → (vazio), exit 0 (depois de renomear R6b → R14: o script só vê `R\d+`), 2026-10-19
- [x] `checkbox-sem-evidencia.sh {wiki}` silencioso — (vazio), exit 0, 2026-10-19
- [x] Citações `arquivo:símbolo:linha` reverificadas: `citacoes.sh {wiki}` silencioso — 31 citações deslocadas pela implementação (Pint, imports, hook removido) reancoradas; (vazio), exit 0, 2026-10-19
- [x] IDs `[CT-nn]` do teste ⊆ `04`/`05` e vice-versa: `ids-ct.sh {wiki} 'tests/**/AgentesIaNoSettings*.php'` silencioso — (vazio), exit 0 — 28 CT + CT-B01, 4 arquivos, 2026-10-19
- [x] Rules casadas pelo diff com linha em `## Conformidade com Rules`: `conformidade-rules.sh {wiki} main` silencioso — 14 rules casadas, 14 linhas, nenhuma `violada`; (vazio), exit 0, 2026-10-19
- [x] Falsificabilidade em cópia temporária, revertendo só o patch do fix: quantos falham sem ele — na própria árvore, com nenhum outro teste rodando, revertendo só o hunk de cada correção do step 9 e restaurando o arquivo (cópia em memória; `git status -- app` idêntico antes/depois): correção do CT-11 (`authorizeAccess()` no trait) → **2 de 6** linhas do CT-11 falham sem ela (403); correção do RD-01 (`hydrate()` no chat) → **4 de 5** linhas do CT-27 falham sem ela; CT-28 não tem patch de correção (documenta a P-08) e foi falsificado pelo mutante M-f; CT-22 pelo M-a (o `hydrate` das Pages é do vendor), 2026-10-19
- [x] Docs pt/en, CHANGELOG e README reconciliados com o comportamento final — seção nova em `configuracoes-do-kit` (pt/en), nota v0.51.0 em `atualizando-o-projeto` (pt/en), `wikis/ia.md`, `roteiro-de-features` (DG-11 e prosa do widget), diagrama DG-01 (docs + READMEs) com `KIT_AGENTES_IA`, READMEs (bullet da IA, 85 specs, 197/232 arquivos de teste, badge 2.265), CHANGELOG Unreleased; 16 citações de `docs/` reancoradas; `SiteDeDocumentacaoTest` + `CitacoesDeCodigoTest` + `DiagramasDaArquiteturaTest` verdes, 2026-10-19
- [x] `php artisan kit:info` lista as duas propriedades novas — `php artisan migrate` local (`2026_10_19_100000_add_agentes_ia_to_kit_settings DONE`) e `kit:info` imprime `Agentes Ia Habilitado .. não` e `Agentes Ia Paineis .. —`, 2026-10-19
- [x] Adendo 1 incorporado (RQ-09: grupo de widgets "Agentes de IA" some com o uso desligado — `04` re-derivado antes do teste) — R11 com origem RQ-09, CT-20 verde (3 linhas), 2026-10-19
- [x] `git commit` — 6 commits em `feat/agentes-ia-no-settings` (`git log --oneline main..HEAD`: 65ce8c2b, b11144da, 98c45918, 084dc71a, 866bb7fb, d9211c96) + o commit de release depois do veredito, 2026-10-19

## Revisão do Diff (step 9)

| ID | Passe | Achado | Destino | `P-nn` / CT | Rejeitado — motivo |
|---|---|---|---|---|---|
| CR-01 | `/code-review` | chat no `/admin`/`/infra` atribui execução e orçamento de IA ao tenant `default` do ledger (`ResolvedorDeTenant` lê `Filament::getTenant()`, nulo fora de rota de organização) | premissa → pergunta → CT (sem correção: é o desenho já escrito do ledger) | P-08, Q10 / CT-28 | — |
| CR-02 | `/code-review` | `authorizeAccess()` do trait chamava `authorizeResourceAccess()` inteiro e a policy do índice era avaliada duas vezes por mount/update | correção (simplificação): `authorizeAccess()` confere só o interruptor; o `parent::authorizeResourceAccess()` fica com o hook do vendor | — / CT-11, CT-13, CT-22 (inalterados) | — |
| CR-03 | `/code-review` | import `Blade` morto em `AppPanelProvider` (= RD-04) | correção | — | — |
| CR-04 | `/code-review` | `chatNaTela()` avaliado duas vezes por render (hook + recuo), cada uma com `TelaDeAutenticacao::corrente()` | — | — | rejeitado: custo é uma busca de rota nomeada + `class_uses_recursive` por chamada, da ordem de microssegundos; memoizar por `once()` em método estático sobreviveria ao request (Octane, suíte) e quebraria "o request seguinte obedece à mudança" (CT-05) |
| CR-05 | `/code-review` | `limparCacheDoRepositorio()` copiado da migration do dock | — | — | rejeitado: é a convenção das três migrations de settings (dock, rodada 3, esta) — migration autocontida, sem acoplar a código de `app/` que pode mudar depois de ela ter rodado |
| CR-06 | `/code-review` | o 404 diverge da rule `filament.md` ("a rota responde 403, não 404") e a exceção vive só no docblock do trait | step 12 (`requirement-to-rule`): candidata a emenda da rule | — | — |
| CR-07 | `/code-review` | comentários da blade do chat e do `voltar-ao-topo.css` ainda diziam "/app" | correção (comentários) | — | — |
| CR-08 | `/code-review` | `Filament::getCurrentPanel()` lido duas vezes em `assertContexto()` | correção (extraído para `exigirUsoNoPainel()`) | — | — |
| RD-01 | eixos | o render/`$refresh` do chat já montado não passava pela guarda do painel — só as actions (simetria de guarda) | CT → correção (`hydrate()` do componente passa pela mesma guarda) | P-03 / CT-27 | — |
| RD-02 | eixos | widgets fecham com 403 silencioso no polling; catálogo e chat fecham com 404 + warning | — | — | rejeitado: o 403 no polling é o comportamento do vendor para todo widget que perde `canView()` (inclusive por permissão) e o widget some no próximo carregamento; logar em `fonteDeDadosDisponivel()` geraria um `warning` por render de dashboard enquanto o uso estiver desligado ("nada por abertura de tela") |
| RD-03 | eixos | comentário que justifica a ausência de `mutateFormDataBeforeSave()` sem `arquivo:linha` do vendor | correção (citação de `vendor/filament/schemas/src/Components/Concerns/HasState.php:$isDehydratedWhenHidden:55` e `vendor/filament/spatie-laravel-settings-plugin/src/Pages/SettingsPage.php:fill():83`) | — | — |
| RD-04 | eixos | import `Blade` morto em `AppPanelProvider` | correção | — | — |

Re-revisão: não exigida — nenhuma correção tocou eixo de fronteira de dado ou superfície pública nova (o `hydrate()` do chat só estende a guarda existente aos updates). Rodada 1 de 2.

## Conformidade com Rules

| Rule | Glob que casou | Aplicada / n.a. / violada | Evidência |
|---|---|---|---|
| `app.md` — papel por `ContextoDePapeis`; DTO; `Paineis::correnteOuPadrao()`; `asset()` em vez de `Storage::url()` | `app/**` | n.a. | o diff não atribui papel, não cria DTO nem URL de arquivo; `Filament::getCurrentPanel()` nulo é tratado explicitamente em `AgentesDeIa::assistenteNoPainel()` (nulo = não), e o hub continua lendo `getCurrentPanel()` |
| `settings.md` — chave lida no boot não vira Settings; três lugares | `app/Settings/**` | aplicada | as duas chaves são lidas por request (`AgentesDeIa`, dentro de `canAccess()`, `fonteDeDadosDisponivel()`, Closure do render hook e `assertContexto()`); propriedade + mapa + migration nova — CT-02, CT-04, CT-05 |
| `filament.md` — Resource condicionado: `canAccess()` **e** `shouldRegisterNavigation()`, com `parent::`; Page/Widget nascem com permissão consultada; `fonteDeDadosDisponivel()` e não `canView()` | `app/Filament/**` | aplicada | `AgenteIaResource::canAccess()`/`shouldRegisterNavigation()` com `parent::` (CT-13 mantém o par tem/não-tem); widgets pelo hook do trait (CT-20); exceção deliberada ao "responde 403, não 404": RQ-05 pede 404, documentado no trait |
| `filament-resources.md` — badge de contagem; scope por subquery | `app/Filament/**/Resources/**` | n.a. | o Resource mantém `BadgeContagemNavegacao` e `getEloquentQuery()` não mudou |
| `pages.md` — segredo em formulário; toda seção por `secao()`, todo campo em seção, aba por `aba()` | `app/Filament/Admin/Pages/**` | aplicada | sem segredo novo; seção `agentes-ia` via `secao()` com ícone e id estável; `ConfiguracoesSecoesColapsaveisTest` 100% verde no lote T0 (CT-03..CT-06 dela) |
| `providers.md` — rota do kit no `KitServiceProvider` | `app/Providers/**` | n.a. | nenhuma rota nova |
| `providers-filament.md` — plugin que resolve o painel corrente nos três painéis | `app/Providers/Filament/**` | n.a. | nenhum plugin novo; o hook do chat saiu do `AppPanelProvider` para o registro global |
| `css-filament.md` — utilitária de blade de vendor no CSS do kit; `@layer` | `app/Providers/**` | n.a. | nenhuma blade de vendor nova e nenhuma folha nova; `assistente-chat.css` já cobria o chat (`AssistenteChatCssTest` verde no `/admin` e no `/app`) |
| `config.md` — `filter_var` para interruptor que abre superfície; sem default de texto em `env()`; uma pergunta, uma dona | `config/**` | aplicada | `KIT_AGENTES_IA` por `filter_var(..., FILTER_VALIDATE_BOOLEAN)` (CT-03); `KIT_AGENTES_IA_PAINEIS` sem default (`TextoDoEnvTest` verde); a única dona é `kit.agentes_ia.*` lida por `AgentesDeIa` |
| `support.md` | `app/Support/**` | aplicada | `AgentesDeIa` é `final`, estática, sem estado, única leitura de `kit.agentes_ia.*` (CT-06: zero consulta ao settings) |
| `views.md` — comentário Blade não protege diretiva; citação ≠ execução | `resources/views/**` | aplicada | a blade do "voltar ao topo" só ganhou prosa no comentário `{{-- --}}` sem diretiva e a troca da condição em `@php` |
| `testes.md` — helper de mais de um arquivo em `tests/Pest.php`; tela aberta não é tela que grava; `chaveDoCampo()`/`carregarAba()`; `toContain` variádico; painel bootado | `tests/**` | aplicada | `ligarAgentesDeIa()`, `MEDIR_PILHA_FLUTUANTE`, `abrirPainelDaPilha()`, `moverDockDaPilha()`, `rolarPaginaDaPilha()` em `tests/Pest.php` (`HelpersDeTesteTest` verde); CT-07/CT-08/CT-25 gravam pela tela com `chaveDoCampo()`/`carregarAba()`; CT-14/CT-22 gravam o catálogo |
| `testes-browser.md` | `tests/Browser/**` | aplicada | CT-B01 com `actingAs` + `get()` antes do `visit()`, oráculo de geometria, sem `wait()`; roda em série |
| `specs.md` — vendor citado por `arquivo:símbolo:linha` depois de ler; conferência por símbolo | `wikis/specs/**` | aplicada | `citacoes.sh` exit 0 em 2026-10-19 depois da reconciliação; toda afirmação sobre vendor no `01` cita símbolo e linha |

## Quality Gate

<!-- Preenchido no step 11. Enquanto vazio, a feature NÃO está concluída e o PR não abre. -->

- **Ciclo**: 1 · **Veredito**: REPROVADO → especificação (1 Major documental: QA-01, evidência do mutante M-h; 4 Minor + 1 cosmético de texto da wiki) · **Data**: 2026-10-19
- **Relatório**: `06-relatorio-qa.md` (ciclo 1 gravado verbatim; o ciclo 2 o substitui)
- **Correções do ciclo 1**: QA-01 (M-h rerodado com saída gravada), QA-02 (`02`: D1–D11 e `hydrate()`/`authorizeAccess()` na Superfície), QA-03 (`04`: Q8 aberta; path de CT-24/CT-26), QA-04 (`01`/`04`: prefixos de log reais), QA-05 (glossário: "Catálogo de agentes"), QA-06 (três checkboxes fechados com evidência)
- **Ciclo**: 2 · **Veredito**: **APROVADO COM DÉBITO** · **Data**: 2026-10-19 — 0 Blocker, 0 Major, 0 Minor; os seis achados do ciclo 1 fechados; A–F, I e J aceitos por referência ao ciclo 1 (nenhum arquivo de código mudou entre `d9211c96` e `eeb47d03`); L reverificada inteira
- **Débito** (teto por cobertura): **G** tema e cor (sem CT-B em tema escuro no `/admin`/`/infra` e sem Playwright MCP); **H** acessibilidade (CT-B01 sem `assertNoAccessibilityIssues()`, sem MCP); **K2** score de mutação não medido (`pest --mutate` não confiável no Windows; só amostragem manual, 8/8 com evidência). Candidatos a verificação em CI/Linux ou numa rodada com Playwright MCP

## Candidatos a Rule

<!-- Step 12. -->
- 2026-10-19 — rota: sessão principal, **sem MCP** (Boost `CONNECT_TIMEOUT` nesta sessão: `record-rule` e o `search-docs` do gate 4 indisponíveis; gate 4 ⚠️ não verificado nos dois) — apresentados 2 · gravados 2 · recusados 0 · descartados no gate 0 · poda 0. **Aprovados pelo solicitante no chat** ("aprovado as rules", 2026-10-19) e gravados pelo **fallback manual** da `requirement-to-rule` (aviso dado: a doc do Boost recomenda o `record-rule`; reconciliar quando o MCP voltar): R-cand-1 como parágrafo novo na seção existente de `.ai/rules/filament.md` ("Em Page, canAccess() sozinho basta; em Resource são dois métodos"), R-cand-2 como seção nova em `.ai/rules/settings.md` com enforcement em `tests/Kit/SemanticaDoVazioNasListasDePaineisTest.php` (2 `it()`: docblock das 7 listas e `helperText` dos 7 selects). O índice `.ai/rules/index.md` não muda (mesmos arquivos e globs). Commit em `main`, depois do merge do PR #171. Revisão independente das duas rules por `codex exec --yolo -s read-only` (sem o contexto da sessão): 14 afirmações conferidas com `arquivo:linha`, 13 ok; 1 corrigida — a frase "`KIT_*_PAINEIS` sem default de texto" valia só para dock e agentes, pois `config/kit.php:567,809-833` usam `''` com isenção em `tests/Kit/TextoDoEnvTest.php:92-97`; reescrita para distinguir chave nova de isenção antiga. Enforcement: cobre as 7 propriedades e os 7 selects (4 declarações de `Select`, uma compartilhada pelos provedores sociais); não cobre o docblock do decisor (terceiro lugar da rule) — débito aceito, o decisor novo é revisado no step 9. Redundância: nenhuma. Índice: inalterado.

| # | Candidato | Glob | Gate 1 durável | Gate 2 escopável | Gate 3 não-inferível (irmãos lidos) | Gate 4 não-redundante | Recorrência |
|---|---|---|---|---|---|---|---|
| R-cand-1 (CR-06) | **Resource escondido por settings responde 404 pelo trait nas Pages, antes da permissão** — emenda à rule `filament.md` "Em Page, canAccess() sozinho basta; em Resource são dois métodos", que hoje afirma "a rota fica registrada e responde 403 (não 404)". Quando o requisito pede 404, o caminho é um trait nas Pages do Resource que sobrescreve `authorizeResourceAccess()` **e** `authorizeAccess()` (o `mount()` de `CreateRecord`/`EditRecord` confere a permissão antes do hook do vendor), mantendo `canAccess()`/`shouldRegisterNavigation()` no Resource para menu, hub, ⌘K e dock | `app/Filament/**/Resources/**` (já coberto por `filament-resources.md`) ou a seção existente de `filament.md` | ✅ vale para todo Resource opcional futuro (demo, pacotes) | ✅ | ✅ irmãos lidos: `ProjetoResource.php`, `TenantResource.php`, `ConviteResource.php` (os três só sobrescrevem `canAccess()`/`shouldRegisterNavigation()` → 403); um agente que lesse só esses três e a rule atual **erraria**: entregaria 403 e, se tentasse 404 só por `authorizeResourceAccess()`, deixaria o 403 vencer em criar/editar (foi o vermelho do CT-11) | ⚠️ gate 4 não verificado (sem MCP); não é default do framework (o Filament devolve 403) nem está em pint/rector/phpstan; não há `arch()` que garanta | `ExigeAgentesDeIaLigados` hoje; o próximo Resource opcional (`ProjetoResource` da demo é candidato natural a migrar) |
| R-cand-2 | **Lista de painéis no settings declara a própria semântica do vazio** — o kit tem duas semânticas vivas: vazio = **todos** (`login_*_paineis`, `dashboard_dinamico_paineis`) e vazio = **nenhum** (`dock_paineis`, `agentes_ia_paineis`); toda lista nova diz no `helperText`, no docblock da propriedade e no decisor qual das duas usa, e a de recurso que nasce desligado usa "nenhum" (falha fechado) | `app/Settings/**`, `app/Filament/Admin/Pages/ConfiguracoesDoKit.php` | ✅ | ✅ | ✅ irmãos lidos: `ConfiguracoesDoKit.php` (Settings: `$login_google_paineis` "vazio = todos", `$dock_paineis` "vazio = nenhum" lado a lado), `DashboardDinamico::habilitadoPara()` (`$paineis === [] \|\| in_array`), `DockDeAtalhos::habilitadoNoPainel()` (`in_array` seco); um agente que lesse só um deles **erraria** ao copiar a semântica errada para a lista nova — a tela só avisa porque "a vizinha diz o oposto" | ⚠️ gate 4 não verificado (sem MCP); comportamento da aplicação, não do framework | `KIT_DASHBOARD_DINAMICO_PAINEIS`, `KIT_DOCK_PAINEIS`, `KIT_AGENTES_IA_PAINEIS`, `KIT_SOCIALITE_*_PAINEIS` — cinco listas, e a próxima feature por painel |

## Auditoria Pré-Implementação

Entendimento confirmado: 2026-10-19 — autorização existente no pedido (execução autônoma: "ao finalizar, abra o PR, quando passar, crie a nova tag + release") — 2 rodadas (step 4 e step 7); perguntas: 0 fato ao usuário, 2 desenho (Q5, Q7, decididas pela sessão sob convenção — D10, D11; D1–D9 idem), 6 requisito (Q1–Q4, Q6, Q8 em `## Perguntas ao Solicitante` do `00`; Q1–Q4 e Q6 seguidas como premissas P-01 a P-04 e P-06; Q8 sem bloqueio)

### Confronto código × afirmação (step 5)
| Pergunta | O `01` dizia | O código faz | Resposta (quem, data) | Onde a wiki mudou |
|---|---|---|---|---|

### Revisão profunda (step 5) — premissas do plano contra o código real
| Premissa do plano | O código real diz | Correção aplicada na wiki |
|---|---|---|
| 29 citações `arquivo:símbolo:linha` do `01` e 13 do `02` | `citacoes.sh` acusou 4 fora do formato (`Classe::método`), nenhuma linha errada | reescritas; `citacoes.sh` exit 0, 2026-10-19 |
| passo 7: "a pilha já detecta o chat pelo DOM" (Codex, despacho #1) | o DOM decide só a faixa de colisão com o dock (`resources/views/filament/voltar-ao-topo.blade.php:temChat:167`); o recuo do botão é `bottom-24` **se o painel é o `/app`** (`resources/views/filament/voltar-ao-topo.blade.php:$recuoDoBotao:100`) — chat no `/admin`/`/infra` sobreporia o botão; chat desligado no `/app` deixaria 4,5 rem vazios | passo 3 ganha `AgentesDeIa::chatNaTela()`; passo 7 troca a condição da blade; T0 ganha `VoltarAoTopoTest` e `AssistenteChatCssTest` |
| passo 8: "`KitInfoTest` absorve as propriedades sem edição" (Codex) | `tests/Kit/KitInfoTest.php:toHaveCount():192` fixa a contagem; CT-07 tem dataset explícito | passo 8: 69 → 71 com a linha de histórico |
| passo 7: "toda ação do chat responde 404" (P-03) (Codex) | `app/Livewire/AssistenteChatWidget.php:responder():94` devolve sem chamar `assertContexto()` quando não há pendência ou ela passa de 2000 — no-op que não alcança agente nem banco | mantido: o no-op continua sem 404; P-03 vale para toda ação com carga (a que alcançaria o agente ou o banco). Registrado aqui; o `04` decide o cenário |
| citação `TelasSemDockNemChatTest.php:'controle app':91` (Codex) | a linha 91 é `'controle admin'`; `'controle app'` está na 90 | citação corrigida no `01` |
| T0 lista `BoasVindasTest`, CT-11 e CT-15 como quebras (Codex) | comentário, round-trip do dock e render direto não passam por `assertContexto()` nem pelo hook | passo 8 reescrito: só os casos que de fato dependem |
| passo 1: `explode(',', (string) env('KIT_AGENTES_IA_PAINEIS', ''))` | `tests/Kit/TextoDoEnvTest.php:'nao reintroduz env com default de texto':69` reprova default de texto em `env()` fora da lista de isenção; o dock usa `env('KIT_DOCK_PAINEIS')` sem default (`config/kit.php:'dock':591`) | passo 1 do `01`: sem default, como o dock |

### Varredura da classe irmã (step 5)
<!-- uma linha por classe nova: irmã escolhida, ocorrências do FQCN dela e o que a nova herda -->

| Classe nova | Irmã | `grep -rnF` do FQCN da irmã (app, config, database, tests, routes, bootstrap) | A nova entra? |
|---|---|---|---|
| `App\Support\AgentesDeIa` | `App\Support\DockDeAtalhos` | `ConfiguracoesDoKit` (Page e Settings: docblocks/comentários), `PluginDoDock` (consumidor), `User` (favoritos), `Paineis` (comentário), `config/kit.php` (comentário do bloco), `tests/Kit/DockDeAtalhosTest.php` — nenhuma lista/inventário; só consumidores e comentários | só nos consumidores previstos (Resource, trait, widgets, hook, Page e Settings) e no comentário do bloco novo de `config/kit.php` |
| `App\Filament\Concerns\ExigeAgentesDeIaLigados` | `App\Filament\Concerns\ExigePermissaoDaTela` | 9 Pages (consumidoras), `App\Support\DockDeAtalhos` e `PermissaoDaTela` (avaliam o fail-open do trait), `tests/Kit/PermissoesDeTelasTest.php` (varre Pages com o trait) — nenhuma lista que enumere traits | não: o trait novo não é de permissão; `PermissoesDeTelasTest` varre Pages, não Resources |
| chave `KIT_AGENTES_IA_PAINEIS` | `KIT_DOCK_PAINEIS` / `KIT_DASHBOARD_DINAMICO_PAINEIS` | `tests/Kit/TextoDoEnvTest.php:'nao reintroduz env com default de texto':69` acusa `env('X', '')` fora da isenção; o dock escreve `env('KIT_DOCK_PAINEIS')` sem default | seguir o dock: `env('KIT_AGENTES_IA_PAINEIS')` sem default (sem linha na isenção) — corrigido no passo 1 do `01` |
| arquivos novos × `KitUpdate::CAMINHOS_DO_KIT` | — | `app/Support`, `app/Filament`, `database/settings`, `config/kit.php` já na lista; `tests/Kit/KitUpdateTest.php:'cobre todo o código do kit':165` varre | nada a acrescentar |

### Revisão adversarial (step 7)
`fw-adversario-ct` (opus, só `00` + `04` + `05` + glossário): 23 achados — ADV-01..ADV-11, ADV-13..ADV-23 aplicados ao `04` pela derivação (CT-03, CT-04, CT-06, CT-07, CT-08, CT-10, CT-12, CT-15, CT-16, CT-19, CT-22 alterados; CT-23 Tenancy, CT-24, CT-25, CT-26 novos; R13 nova; M55–M67); ADV-22 virou Q9 + P-07 no `00`. **ADV-12 rejeitado**: "o dashboard dinâmico desenha o widget sem consultar `canView()`" — se o pacote `mddev31/filament-dynamic-dashboard` ignorar `canView()`, ele já ignora a permissão `View:{Widget}` de todo widget do kit, defeito da feature `dashboard-dinamico-nos-paineis`, não desta; anotado em `## Notas de Implementação`.

### Auditoria Ponytail (step 6)
| # | Sugestão de corte | Aplicada? | Onde |
|---|---|---|---|
| 1 | `shrink`: helper privado `paineis()` com filtro `is_string` no decisor — `in_array(..., (array) config(...), true)` inline, como o dock | sim | `01`, passo 3 |
| 2 | `delete`: reescrever o docblock do Resource — uma frase no existente basta | sim | `01`, passo 5 |
| 3 | Trait com 3 consumidores, hook global, helper de teste usado por 10 arquivos: lean | — | — |

Ponytail-review rodado em linha (skill `ponytail-review`, sem namespace de plugin neste projeto), 2026-10-19 — `net: -6 lines possible`.

## Despachos

| # | Step | Agente / tarefa | Modelo | Não recebeu | Resultado | Custo | Auditoria do retorno |
|---|---|---|---|---|---|---|---|
| 1 | 5 | `codex exec --yolo -s read-only` (auxiliar pedido pelo solicitante) — revisão profunda independente: cada premissa do `01` contra o código, testes que faltam no lote T0, listas paralelas | codex-cli 0.163.0, modelo da sessão do Codex (não exposto pelo host) | a conversa desta sessão; o `03` | tabela com 47 premissas: 42 sem divergência, 5 achados (recuo da pilha por id de painel; `KitInfoTest` com contagem fixa 69; `responder()` sem 404 no no-op; citação deslocada; T0 com referências incidentais) + 1 já corrigido (default de texto no `env()`) | tokens não reportados · ~4 min | `git status --porcelain` igual antes/depois (só a wiki untracked); 5/5 achados reproduzidos por `sed -n` (`resources/views/filament/voltar-ao-topo.blade.php:$recuoDoBotao:100`, `tests/Kit/KitInfoTest.php:toHaveCount():192`, `app/Livewire/AssistenteChatWidget.php:responder():94`, `tests/Kit/TelasSemDockNemChatTest.php:'controle app':92`); todos aplicados ao `01` |
| 2 | 7 | `general-purpose`/opus (fallback do `analista`, sem agente de projeto) — deriva o `04` (e o `05` só com costura browser) lendo e seguindo `feature-test-design/SKILL.md`; gravou os dois arquivos direto (desvio do "devolve como texto": 850 linhas; a sessão auditou antes de usar) | opus | `01` inteiro (só rotas, UI, env, mapeamentos e nomes), `02` fora da Superfície Livewire, `03`, a conversa e qualquer implementação (inexistente) | `04` com 11 regras, 21 CT, 50 mutantes (0 sem matador), 8 costuras propostas; `05` com CT-B01 (pilha no `/admin` e `/infra`); 4 perguntas (Q?1–Q?4 → Q5–Q8); revisão adversarial pendente | 280.876 tokens · 14 min 55 s | `git status --porcelain`: só `04` e `05` novos, `00`–`03` intactos; amostra: R6 (CT-11/CT-12, 404 com e sem permissão, matriz papel × ação) e R9 (CT-18, 5 verbos × 2 motivos, `assertNeverPrompted` + `warning` uma vez) conferidas contra o `00`; costuras confirmadas pela sessão sob convenção |
| 3 | 7 | `fw-adversario-ct` — revisão adversarial do `04`/`05` | opus | `01`, `02` (fora da Superfície Livewire — e nem ela), `03`, código, conversa | 23 achados (12 implementações que passam, 5 oráculos fracos, 4 malformados, 2 sondas) | 80.419 tokens · 3 min 40 s | hashes de `00`/`04`/`05`/glossário iguais antes e depois (`sha1sum`); 22 aceitos, 1 rejeitado com motivo (ADV-12) |
| 5b | 9 | mesmo agente do lote B (`SendMessage`) — CT-27 (RD-01) | sonnet | idem | 5 linhas verdes; controle positivo 200 + 0 warning | 137.053 tokens (acumulado) · 1 min 1 s | `git status`: só o arquivo do lote; amostra: `html()` sem "Plano de férias" depois do 404 e com ele no controle |
| 6b | 9 | mesmo agente do lote C (`SendMessage`) — CT-28 (P-08) | sonnet | idem | 2 linhas verdes (`default` no `/admin`, id da Acme no `/app/{acme}`); 1ª rodada causa (a) (`use` faltando) | 78.963 tokens (acumulado) · 55 s | `git status`: só o arquivo do lote; amostra: `ai_runs` esvaziado antes do `responder`, `assertPrompted`, Bruno membro da Acme |
| 2c | 9 | mesmo agente do despacho 2 (`SendMessage`) — CT-27 e CT-28 no `04` (R15, R16) | opus | idem | `04` com 28 CT, 15 regras, 74 mutantes, 0 sem matador | 382.301 tokens (acumulado) · 1 min 27 s | só o `04` mudou; Gherkin dos dois lido |
| 10 | 11 | `fw-qa-gate` — ciclo 1 | opus (claude-opus-5-5) | a conversa; só path da wiki, URL do app (`127.0.0.1:8010`), `git diff --stat`, base `main` | REPROVADO → especificação: 1 Major (QA-01, evidência do mutante M-h perdida no `print`), 4 Minor, 1 cosmético — todos texto da wiki; 9/12 dimensões; probes HTTP reais do 404 | 295.467 tokens · 22 min | `06` gravado verbatim entre `<<<06`/`>>>06`; `git status` antes vazio, depois só edições da própria sessão (release em preparação), sem arquivo de código; hashes dos arquivos de código conferidos |
| 10b | 11 | mesmo agente (`SendMessage`) — ciclo 2 | opus | idem | **APROVADO COM DÉBITO** (G, H, K); 6/6 achados fechados; 103/103 nos guardas de docs/versão | 313.290 tokens (acumulado) · 1 min 47 s | `06` gravado verbatim; `git status` depois: só o `03` (edição da sessão: candidatos a rule) |
| 7 | 10 | `fw-executor-ctb` — `tests/Browser/AgentesIaNoSettingsTest.php` (CT-B01) | sonnet | `01` fora da Superfície de UI, `03`, código de aplicação | 2 linhas verdes (34 asserções) na 2ª iteração; 1ª vermelha por causa (a): o `05` não armava o dock no canto nem rolava a página; `05` corrigido pela sessão (passo 1b) e "Desenhado × Implementado" preenchido | 70.192 tokens · 2 min 55 s | `git status`: só o arquivo do lote (a sonda `ZzSondaTest.php` foi apagada); o agente reimplementou `moverDockDaPilha`/`rolarPaginaDaPilha` inline — a sessão os moveu para `tests/Pest.php` (D11) e o teste passou a chamá-los; rerodado pela sessão |
| 2b | 7 | mesmo agente do despacho 2 (`SendMessage`) — CT-22 (P-06) e depois os 22 achados + Adendo 1 | opus | idem | `04` com 26 CT, 13 regras, 67 mutantes, 0 sem matador | 291k + 371k tokens (acumulados) · 50 s + 5 min 43 s | `git status`: só o `04` mudou; contagens reconferidas por `grep -c` (26 / 13 / 67); amostra CT-22 e CT-23 lidas |
| 4 | impl. | `fw-executor-ct` lote A — `tests/Kit/AgentesIaNoSettingsTest.php` (CT-01..15, 22, 25) | sonnet | `01`, `02`, `03`; não edita `app/`, `database/migrations/`, `00`/`04`/`05`, `tests/Pest.php` | 17 `it()`, 73 linhas: 71 verdes + **2 vermelhas por causa (b)** — CT-11 `criar sem`/`editar sem` respondiam 403 em vez de 404 (o `mount()` de `CreateRecord`/`EditRecord` chama `authorizeAccess()` antes do hook do trait); 3 iterações; 6 ambiguidades do `04` resolvidas no arquivo (`.env.example` lido por `Dotenv::parse`, migration `up()` chamada, hub por reflexão, ⌘K pelas categorias) | 181.343 tokens · 9 min 14 s | `git status`: só o arquivo do lote untracked; vermelho (b) reproduzido pela sessão em `vendor/filament/filament/src/Resources/Pages/CreateRecord.php:mount():64` e corrigido no trait (`authorizeAccess()` sobrescrito); rerodado: 73/73 |
| 5 | impl. | `fw-executor-ct` lote B — `tests/Kit/AgentesIaNoSettingsChatTest.php` (CT-16..21, 24, 26) | sonnet | idem | 8 `it()`, 46 linhas de dataset verdes (239 asserções); 13 vermelhos na 1ª iteração, todos causa (a); causa (b): nenhuma; 2 correções do `04` devolvidas como texto (CT-17 `Dado` do login único; CT-18 `conversaId` ilegível depois do 404) e gravadas pela sessão | 132.325 tokens · 4 min 44 s | `git status`: só o arquivo do lote untracked; amostra: CT-18 com 5 verbos × 2 motivos, `assertNeverPrompted` nos dois agentes, `warning` once; CT-19 inclui `setCurrentPanel(null)`. Ressalva: o agente leu `AssistenteChatWidget.php` também para entender o estado pós-404 (contrato pede só nomes) — o `Então` continuou vindo do `04` |
| 6 | impl. | `fw-executor-ct` lote C — `tests/Tenancy/AgentesIaNoSettingsTenancyTest.php` (CT-23) | sonnet | idem | 1 teste verde, 4 asserções, causa b: nenhuma | 67.040 tokens · 1 min 3 s | `git status`: só o arquivo do lote untracked; amostra: `ligarAgentesDeIa(true, ['admin'])`, `Filament::setCurrentPanel('admin')`, controle da Carla presente |
| 8 | 9 | `/code-review high main...HEAD` (sub-agente isolado do comando) | o do comando (não exposto) | conversa, wiki (lê o diff inteiro, inclusive `wikis/`) | 8 achados: 5 aceitos (CR-02, CR-03, CR-07, CR-08 corrigidos; CR-01 → P-08/Q10/CT-28), 1 para o step 12 (CR-06), 2 rejeitados (CR-04, CR-05) | 137.006 tokens · 6 min 12 s | achados reproduzidos por leitura (`app/Ai/Support/ResolvedorDeTenant.php:id():21`, `vendor/filament/filament/src/Resources/Pages/CreateRecord.php:mount():64`, o import `Blade` então na linha 54 do `AppPanelProvider`); nenhum arquivo alterado pelo passe |
| 9 | 9 | `fw-revisor-diff` — eixos sobre `main...HEAD` sem `wikis/` | opus | `01`, `03`, raciocínio da sessão | 9 eixos percorridos, 4 achados (RD-01 → CT-27 + `hydrate()`; RD-03 e RD-04 corrigidos; RD-02 rejeitado); 149 testes rodados pelo revisor, verdes | 95.122 tokens · 7 min 53 s | `git status` antes vazio; depois só `01`/`03`/`04` modificados — pela sessão (citações, R14, conformidade), confirmado por `git diff --stat` sem arquivo de código; 2 leituras negadas pelo hook refeitas pelo caminho permitido |
| — | pré-9 | Sem despacho — re-varredura da Superfície Livewire feita em linha (2 greps do `pesquisa-step-3.md` sobre `git diff --name-only -- app resources`): 0 métodos públicos de instância novos, 0 propriedades públicas novas; linhas do `02` atualizadas | — | — | — | — | — |
| — | 0–4 | Sem despacho — captura verbatim do requisito, decomposição, entrevista e escrita de `00`–`03` são da sessão; pesquisa feita em linha por `grep`/`sed` (feature pequena: 2 propriedades, 1 decisor, 1 trait, 1 hook) | — | — | — | — | — |

## Blockers

## Desvios do Plano
- Passo 5 (trait `ExigeAgentesDeIaLigados`): além de `authorizeResourceAccess()`, o trait sobrescreve `authorizeAccess()` — o `mount()` de `CreateRecord`/`EditRecord` confere a permissão (403) antes de o Livewire rodar o hook do trait, e quem não tinha a permissão recebia 403 com o uso desligado (causa (b) do lote A, CT-11). Fonte corrigida: `01`, passo 5.
- Passo 8 (lote T0): `tests/Kit/AssistenteChatCssTest.php` CT-01 também dependia do chat no `/app` (o grep de aceite do Codex pegou só o CT-03); ligado no arranjo. Fonte corrigida: `01`, passo 8.
- Implementação: o lote Kit do `04` foi dividido em dois arquivos (`AgentesIaNoSettingsTest` e `AgentesIaNoSettingsChatTest`); índice do `04` ajustado.
- Step 10 (CT-B): o `05` não armava o dock no canto nem rolava a página (causa (a)); passo 1b acrescentado; `moverDockDaPilha()`/`rolarPaginaDaPilha()` promovidos a `tests/Pest.php` (D11 estendida).

## Notas de Implementação
- **CI do PR #171, job `qualidade` vermelho** (PHPStan `staticClassAccess.privateMethod`): o helper `exigirUsoDeAgentesLigado()` do trait é `private static` e era chamado por `static::` — o PHPStan local rodou antes do refactor do CR-02 que o extraiu (a rodada `types-check2` cobria só o `authorizeAccess()`). Corrigido para `self::` (o Pint e os CT-11/CT-13/CT-22 continuam verdes); PHPStan rerodado antes do push. Lição: rerodar o `phpstan analyse` depois de TODA correção do step 9, não só da primeira.
- **ADV-12 (rejeitado)**: se o dashboard dinâmico desenhar um widget sem consultar `canView()`, o grupo "Agentes de IA" apareceria lá com o uso desligado — e a permissão `View:AgentesIaStats` também seria ignorada. É propriedade do pacote/feature `dashboard-dinamico-nos-paineis`; fica registrado como candidato a verificação naquela wiki.
- **Lote Kit dividido em dois arquivos** (`AgentesIaNoSettingsTest` e `AgentesIaNoSettingsChatTest`): 25 cenários num arquivo só estourariam o teto de turnos do `fw-executor-ct`; o índice do `04` foi ajustado pela sessão.
- **Python no Windows**: `io.open(..., 'w')` sem `newline=''` converteu quatro arquivos inteiros para CRLF (o `git ls-files --eol` acusou); normalizados para LF antes do primeiro commit. Os scripts seguintes escrevem com `newline=''`.
- **Baseline de `main` (27e47714), antes do primeiro commit** — `php artisan test --testsuite=Kit,Tenancy --parallel --compact`, 2026-10-19: 4.850 testes, 4.846 verdes, 3 pulados, **1 vermelho**: `tests/Kit/SiteDeDocumentacaoTest.php:'mantem os numeros objetivos dos readmes sincronizados com a arvore':993` — o README diz `**84**` features especificadas e a árvore já tem 85 `00-requisito.md` (a pasta desta wiki, untracked, já contava). Não é falha pré-existente: é consequência da própria feature, e o passo 9 (docs) atualiza os dois READMEs para 85. Duração: 553 s.

## Referências Abertas
- `hosts-e-subagentes.md` — início — 2026-10-19
- `ponytail-caveman.md` — início — 2026-10-19
- `roteamento-e-despacho.md` — início (antes de qualquer despacho) — 2026-10-19
- `pesquisa-step-3.md` — step 3 — 2026-10-19
- `template-00-requisito.md` — step 4 — 2026-10-19
- `entrevista-tres-raias.md` — step 4 — 2026-10-19
- `glossario.md` — step 3 (lido; nenhum termo novo decidido até aqui) — 2026-10-19
- `template-01-plano.md` — step 4 — 2026-10-19
- `template-02-adr.md` — step 4 — 2026-10-19
- `template-03-progresso.md` — step 4 — 2026-10-19
- `padrao-de-log.md` — step 4 — 2026-10-19
- `estrutura-criada.md` — step 4 — 2026-10-19
- `citacoes-de-codigo.md` — step 3/4 — 2026-10-19

## Retrospectiva
- **Funcionou bem**: o Codex como revisor profundo do plano (achou o recuo da pilha decidido pelo id do painel antes de qualquer linha de código); a revisão adversarial do `04` (23 achados, 22 aplicados — inclusive a conversa que segue a pessoa entre painéis e o favorito oculto do dock); os executores cegos devolveram um vermelho real por causa (b) (o 403 antes do 404 em criar/editar) e o `/code-review` + eixos devolveram outro (o `$refresh` do chat já montado), os dois virando CT antes da correção.
- **Faltou no plano**: a lista T0 subestimou os testes que assumiam o chat fixo no `/app` (`AssistenteChatCssTest` CT-01, `VoltarAoTopoTest`) e as listas paralelas de documentação (`KitInfoTest` 69→71, `DiagramasDaArquiteturaTest` com o mapa opt-in das chaves `KIT_*`, DG-01 espelhado nos READMEs, as contagens e o badge do README) — a suíte inteira as acusou, mas só no fim; `grep -rn KIT_DOCK_PAINEIS tests docs README*` no step 3 teria listado todas. E o `io.open()` do Python sem `newline=''` converteu arquivos inteiros para CRLF no Windows: toda edição por script passou a usar `newline=''`.
