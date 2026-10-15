# Progresso — Estudo e adoção de pacotes Filament, rodada 3

**Estado**: em implementação

## 0. Dossiê dos pacotes e baseline
- [x] `07-dossies-dos-pacotes.md` escrito, um pacote por seção — 5 seções + comparativo, 2026-10-14
- [x] Baseline de `Kit` e `Tenancy` sobre a árvore de `main` registrada aqui — `vendor/bin/pest --parallel --testsuite=Kit --compact`: 3874 testes, 3870 verdes, 1 falha (`SiteDeDocumentacaoTest::mantem os numeros objetivos dos readmes sincronizados com a arvore`, linha `Features especificadas … **81**`: a própria wiki nova é o 82º `00-requisito.md` — corrigido no passo 1); `--testsuite=Tenancy`: 578/578; saída em `scratchpad/baseline/{kit,tenancy}.txt`, 2026-10-14

## 1. Dependências e publicações
- [x] `composer require` dos dois pacotes de runtime e do `--dev` — `composer.lock`: `sehsah/filament-remember-table` v1.0.0, `asignua/filament-infinite-scroll` v1.0.1, `happenv-com/filament-phpstan-macros` v1.0.0; Filament 5.8.2 → 5.10.2 (P-12); `composer validate --no-check-all` válido, 2026-10-14
- [x] Migration e config do `remember-table` publicadas; `allow_public_views` semeada do `.env` — `database/migrations/2026_10_14_213646_create_remember_table_views_table.php`, `config/remember-table.php` com `BooleanoDoEnv::comPadrao(env('KIT_TABELA_VISOES_COMPARTILHADAS'), false)`; `php artisan config:show remember-table.allow_public_views` = false, 2026-10-14
- [x] `filament:assets` publicado; `CAMINHOS_DO_KIT` com `config/remember-table.php` — `public/css/asignua/` novo; `KitUpdateTest` CT-10 verde (lista parseada = constante, 85 entradas), 2026-10-14
- [x] Números dos READMEs (pacotes, migrations, "77 pacotes") — 61 produção / 21 dev / 64 migrations / 82 specs (`composer.json` por `/` nas chaves; `ls database/migrations | wc -l`); "77" → 82 (61 + 21), 2026-10-14

## 2. PHPStan entende macros
- [x] `includes:` no `phpstan.neon` com comentário — linha `vendor/happenv-com/filament-phpstan-macros/extension.neon`, 2026-10-14
- [x] `composer types:check` verde com o macro chamado direto — `vendor/bin/phpstan analyse`: `{"result":"passed","errors":0}`, sem linha nova em `ignoreErrors` (3), 2026-10-14

## 3. Config e `.env.example`
- [x] `config/kit.php` com `carregamento` e `visoes_salvas` — `php artisan config:show kit.tabelas.carregamento` = paginacao, `kit.tabelas.visoes_salvas` = false, 2026-10-14
- [x] `.env.example` com as três chaves — 7 linhas `KIT_TABELA_*` no bloco das tabelas, 2026-10-14

## 4. Enum `CarregamentoDaTabela`
- [x] `app/Support/CarregamentoDaTabela.php` com `opcoes()`, `deConfig()`, `coagir()`, `padrao()`, `modoDoPacote()` — enum de 3 casos, PHPStan verde, 2026-10-14

## 5. Settings: propriedades, mapa e migration
- [x] Três propriedades em `ConfiguracoesDoKit` — `KitInfoTest` CT-06: 69 propriedades (66 + 3), 2026-10-14
- [x] Três linhas em `mapaDeConfiguracao()` — `tabela_carregamento`, `visoes_de_tabela`, `visoes_de_tabela_compartilhadas` (a terceira para `remember-table.allow_public_views`), 2026-10-14
- [x] Migration de settings com `add()`/`delete()` e limpeza de cache — `database/settings/2026_10_14_200000_add_tabelas_rodada_3_to_kit_settings.php`, `php artisan migrate` DONE, 2026-10-14

## 6. Aba *Tabelas* da tela de configurações
- [x] Select do carregamento, toggle das visões e toggle do compartilhamento (condicional) — `abaTabelas()` da Page; `filacheck --fix`: 17/17, 2026-10-14
- [x] Coerção em `mutateFormDataBeforeSave()` — carregamento coagido e compartilhamento forçado a `false` com visões desligadas (P-15), 2026-10-14

## 7. Carregamento contínuo em toda tabela
- [x] `aplicaCarregamento()` em `ConfiguraFilamentGlobal`, macro chamado direto — PHPStan 0 erros sobre `$table->infiniteScroll(perPage:, mode:)`, 2026-10-14
- [x] Docblock do trait atualizado ("sete defaults") — título `## Sete destes defaults` no docblock de `ConfiguraFilamentGlobal`, 2026-10-14

## 8. Permissão `Usar:VisoesDeTabela`
- [x] `custom_permissions` + mapa de painéis do seeder + subtração do `panel_user` — chave `usar:visoes_de_tabela` (a grafia `usar:visoesdetabela` gerava `Usar:Visoesdetabela`: o Shield formata o subject por studly); `tinker`: admin tem, infra tem, panel_user não, 2026-10-14
- [x] Inventário de `PermissoesDeAcoesTest` — sem linha, por decisão (revisão profunda: Action do vendor não é varrida; linha viraria órfã), 2026-10-14
- [x] Seeders ressemeados — `db:seed --class=ShieldPermissionsSeeder` e `--class=PapeisSeeder` no banco local; `Usar:VisoesDeTabela` presente, 2026-10-14

## 9. Trait `VisoesDeTabela` e as dez listagens
- [x] `app/Filament/Concerns/VisoesDeTabela.php` com guarda por request e `->authorize()` — 7 aliases, 3 sobrescritas de guarda, 4 Actions autorizadas; PHPStan verde, 2026-10-14
- [x] `use` + `getTableViewActions()` nas 10 páginas `List*` — 10 arquivos com `use VisoesDeTabela`; `ListRoles` mantém `getActions()` (revisão profunda); `ListComposerReleasePackages` sobrescreve `getHeaderActions()` com `...parent::getHeaderActions()`, 2026-10-14

## 10. Traduções pt_BR do pacote
- [x] `lang/vendor/filament-remember-table/pt_BR/remember-table.php` — todas as chaves do `en` do pacote (button, actions, modals, fields, badges, notifications), 2026-10-14

## 11. Testes de backend (`04`)
- [x] Escritos pelo Codex sob o contrato do `executor-ct`; vermelhos classificados — lotes D0, L1…L8 + 2 segundas passadas; 20 vermelhos (b) roteados (4 código, 13 especificação, 1 docs, 1 capturas), 0 relaxados; ver Desvios, 2026-10-14

## 12. Testes de navegador (`05`)
- [x] Escritos pelo Codex sob o contrato do `executor-ctb` (se houver costura `browser`) — `tests/Browser/CarregamentoContinuoTest.php` (CT-B01) verde na primeira execução, 1/1 em 73 s; Roteiro "Desenhado × Implementado" do `05` preenchido (4 linhas, ✅), 2026-10-14

## 13. Documentação
- [x] Página nova pt/en `recursos/visoes-de-tabela-e-carregamento` — com a seção "Os cinco pacotes avaliados e o critério", 2 thumbs pareados claro/escuro e 4 GIFs; CT-49 e CT-52 (`PacotesRodada3Test` 6/6), 2026-10-14
- [x] `configuracoes-do-kit`, `configuracao-global-filament`, `pacotes-instalados` (+ "Avaliados e não adotados"), `depois-de-criar-resources`, `estudo-advanced-tables`, `qualidade-de-codigo` (pt/en) — CT-50 (`PacotesRodada3Test`), 2026-10-14
- [x] `site/sidebar.json`, `site/redirects.json` e os stubs `site/public/{pt,en}/recursos/visoes-de-tabela-e-carregamento.html` — `git status` lista os cinco, 2026-10-14
- [x] `wikis/pacotes.md`, `CHANGELOG.md` (`[Unreleased]`: Adicionado, Alterado, Corrigido), `README.md`/`README.en.md` (61/21/64/82) — `KitInfoTest` 69 e `DiagramasDaArquiteturaTest` verdes; `wikis/pacotes-candidatos.md` não precisou mudar (os dois recusados ficam documentados em `pacotes-instalados`), 2026-10-14

## 14. Capturas e GIF, nos dois temas
- [x] Cenários em `CapturaDeArteTest` (claro e escuro) e listas em `KitArte` — 6 cenários novos (`visoes-*`, `tabela-rolagem`, `tabela-carregar-mais`, `admin-configuracoes-tabelas`, mais os escuros); `KitArte::IMAGENS` +8, `CLIPES` +2; `KitArteTest` + `CitacoesDeCodigoTest` 57/57, 2026-10-14
- [x] `composer art` com as imagens conferidas — `CapturaDeArteTest` 36/38 (2 skips anteriores à feature), 29 imagens + thumbs publicadas; os 4 clipes novos montados por `kit:arte` com ffmpeg 9.0.2 (instalado na máquina por `winget install Gyan.FFmpeg`, fora do PATH do Git Bash — exportado só para a rodada); `visoes-aberta`, `tabela-carregar-mais-escuro` e `admin-configuracoes-tabelas` abertas e conferidas pela sessão, 2026-10-14

## 15. Branch, commits e PR
- [ ] Commits por passo na `feat/estudo-de-pacotes-rodada-3`
- [ ] PR aberto depois do veredito, sem merge

## Testes
<!-- Step 7: um arquivo por linha, com os IDs (do ## Índice de Cenários do 04). Escritos pelo Codex sob o contrato do executor-ct/ctb (RQ-03). -->
- [x] `tests/Pest.php` (lote D0: helpers `visaoGravada`, `chaveDaTabela`, `ligarVisoesDeTabela`, `ligarCarregamento`, `concederVisoesDeTabela`, `composerDoKit`) — 6 funções conferidas por `grep -n '^function '`; `PacotesRodada2Test` + `DockDeAtalhosTest` 137/137 após a mudança, 2026-10-14
- [x] `tests/Kit/CarregamentoDaTabelaTest.php` (CT-09, CT-10) — 15/15, 2026-10-14
- [x] `tests/Kit/DefaultsDeTabelaTest.php` (CT-01, CT-02, CT-03, CT-11) — 26/26, 2026-10-14
- [x] `tests/Kit/ConfiguracoesDoKitTelaTest.php` (CT-12, CT-13, CT-14, CT-15, CT-16, CT-58, CT-60, CT-76) — 55/55, 2026-10-14
- [x] `tests/Kit/CarregamentoContinuoTest.php` (CT-41 linhas Kit, CT-42…CT-47, CT-69, CT-70, CT-71) — 46/46, 2026-10-14
- [x] `tests/Kit/VisoesDeTabelaTest.php` — 130/130, 2026-10-14 (CT-04, CT-06, CT-07, CT-08, CT-20…CT-22, CT-24, CT-25, CT-27…CT-30, CT-32, CT-33, CT-35…CT-40, CT-53…CT-57, CT-59, CT-63, CT-64, CT-72…CT-75, CT-77, CT-78)
- [x] `tests/Tenancy/VisoesDeTabelaTenancyTest.php` — 34/34, 2026-10-14 (CT-05, CT-23, CT-26, CT-31, CT-34, CT-41 linhas Tenancy, CT-61, CT-65…CT-68)
- [x] `tests/Kit/PermissoesDeAcoesTest.php` (CT-17, CT-18) · `tests/Tenancy/PermissoesDeAcoesTenancyTest.php` (CT-19) — 42/42 e 27/27, 2026-10-14
- [x] `tests/Kit/QualidadeDeCodigoTest.php` (CT-48) — 30/30, 2026-10-14
- [x] `tests/Kit/PacotesRodada3Test.php` (CT-49…CT-52, CT-62) — 6/6 depois do `composer art`, 2026-10-14
- [x] `tests/Browser/CarregamentoContinuoTest.php` (CT-B01) — 1/1 (11 asserções, 73 s), Codex sob o contrato do `fw-executor-ctb`, 2026-10-14

## Tickets
Não fatiado — 2026-10-14: 8 RQ vigentes, 78 CT (o sinal de tamanho, ≥ 60 CT, foi cruzado — mas só na rodada adversarial, com a implementação já em curso e cabendo numa sessão: o `04` tinha 52 CT no step 8), compactação: não, 22 perguntas de requisito (Q1…Q22, todas respondidas sob autonomia salvo Q11) — sugestão de `/feature-tickets` registrada para o solicitante, não feita a tempo

## Verificação Final
- [ ] `/ponytail:ponytail-review` no diff
- [x] `vendor/bin/pint --dirty` — `passed`, 2026-10-14
- [x] `vendor/bin/filacheck --fix` — "Fixed 21 issue(s) in 2 file(s)" nos testes L3/L5 escritos pelo Codex (`ConfiguracoesDoKitTelaTest`, `VisoesDeTabelaTest`); os dois arquivos rerodados: 185/185 (1 flake de `rename()` do cache de views concorrente com o Codex, verde isolado), 2026-10-14
- [x] `composer types:check` — `vendor/bin/phpstan analyse`: `errors: 0`, 2026-10-14
- [ ] `vendor/bin/pest --parallel --testsuite=Kit` e `--testsuite=Tenancy`, contra a baseline
- [x] `vendor/bin/pest tests/Browser…` / `tests/BrowserTenancy…` (CT-B) — `tests/Browser/CarregamentoContinuoTest.php` 1/1; `tests/BrowserTenancy/CapturaDeArteTest.php` 36/38 (2 skips anteriores), em série, 2026-10-14
- [ ] `pest --mutate` nas classes de regra: score, duração e sobreviventes (via `pestw.cmd`)
- [ ] Custo medido contra o `## Modelo de Execução`
- [ ] `/code-review high main...HEAD` + passe de eixos (step 9)
- [x] Roteiro "Desenhado × Implementado" do `05` — 4 linhas, todas ✅, 2026-10-14
- [ ] Desvios propagados ao `01`/`02`/`04`/`05`
- [ ] `rastreabilidade.sh {wiki}` silencioso
- [x] `checkbox-sem-evidencia.sh {wiki}` silencioso — exit 0, 2026-10-14
- [x] `citacoes.sh {wiki}` silencioso — exit 0 depois do bump para a 1.0.1 e das 18 citações refeitas, 2026-10-14
- [x] `ids-ct.sh {wiki} 'tests/**/…'` silencioso — exit 0 com os 11 arquivos de teste da feature como padrões, 2026-10-14
- [x] `conformidade-rules.sh {wiki} main` silencioso — exit 0 (16 rules na tabela), 2026-10-14
- [ ] Falsificabilidade dos CTs de achado do step 9
- [ ] Docs pt/en, CHANGELOG e README reconciliados
- [x] `composer art` com capturas conferidas — ver seção 14, 2026-10-14
- [ ] `git commit`

## Revisão do Diff (step 9)

| ID | Passe | Achado | Destino | `P-nn` / CT | Rejeitado — motivo |
|---|---|---|---|---|---|

## Conformidade com Rules

<!-- Uma linha por rule que `conformidade-rules.sh {wiki} main` listou (16), 2026-10-14. -->

| Rule | Glob que casou | Aplicada / n.a. / violada | Evidência |
|---|---|---|---|
| `app.md` — `ContextoDePapeis`, DTO em `app/Data`, `Paineis::correnteOuPadrao()`, `asset()` em URL de arquivo | `app/**` | n.a. | nenhum `assignRole`, Data, `getCurrentOrDefaultPanel()` ou `Storage::url()` no diff (`git diff main -- app | grep` = 0 para os quatro) |
| `commands.md` — `.env` por `SubstituicaoEmArquivo`; caminho novo em `CAMINHOS_DO_KIT` só entrega arquivo que mudou | `app/Console/Commands/**` | aplicada | `KitArte`/`KitUpdate` não gravam `.env`; `config/remember-table.php` entra na lista como arquivo NOVO (sempre entregue); `KitUpdateTest` CT-10 verde |
| `config.md` — nunca `(int) env()`; interruptor de superfície falha fechado; uma chave, uma dona | `config/**` | aplicada | `BooleanoDoEnv::comPadrao(…, false)` em `config/kit.php:'visoes_salvas'` e `config/remember-table.php:'allow_public_views'`; `CarregamentoDaTabela::coagir(env(…))`; o compartilhamento tem UMA dona (`remember-table.allow_public_views`, lida pelo pacote) — CT-01, CT-09, CT-16 |
| `css-filament.md` — utilitária de blade de vendor coberta; guarda em runtime; `@layer`; `display` no swap da logo | `resources/css/filament/**`, `app/Providers/**` | aplicada | a folha do `infinite-scroll` usa só classes `fi-*` próprias e não declara `@layer` (`grep -c '@layer' vendor/asignua/filament-infinite-scroll/resources/dist/filament-infinite-scroll.css` = 0; `OrdemDasCascadeLayersTest` verde); a regra nova do kit (`.fi-dropdown-panel { z-index: 22 }`) não é utilitária nem toca `display` — correção de empilhamento documentada no próprio CSS |
| `filament-resources.md` — `BadgeContagemNavegacao` em Resource novo; scope em `parent::getEloquentQuery()` | `app/Filament/**/Resources/**` | n.a. | nenhum Resource novo; as 10 Pages só ganharam o trait e o spread (`BadgeDeNavegacaoTest` verde na suíte Kit) |
| `filament.md` — Action nova com permissão consultada; Page/Widget com `canAccess`; construções do Blueprint; BulkAction com autorização por registro | `app/Filament/**` | aplicada | as quatro Actions do pacote com `->authorize('Usar:VisoesDeTabela')` + `->visible()` (`app/Filament/Concerns/VisoesDeTabela.php:saveTableViewAction():123-128`); permissão na matriz do `PapeisSeeder` (CT-17, CT-18, CT-19); nenhuma `BulkAction` nova; `AderenciaAoBlueprintTest` verde na suíte Kit |
| `general.md` — dependência privada fora do `composer.json` | `composer.json` | aplicada | só Packagist; `BlueprintForaDoPacoteTest` verde (chave `repositories` ausente); `spiggle/list-view` recusado também por isso ter exigido VCS na época da análise |
| `pages.md` — segredo em formulário: fill/dehydrate/encrypted/trio | `app/Filament/Admin/Pages/**` | n.a. | os três campos novos não são segredo (`encrypted()` inalterado — CT-09 da densidade cobre o padrão); `ConfiguracoesDoKitTelaTest` 55/55 |
| `providers-filament.md` — plugin que resolve o painel corrente nos três painéis | `app/Providers/Filament/**` | n.a. | nenhum plugin de painel novo (os dois pacotes são provider global + trait/macro); os três providers só mudaram em comentário de citação |
| `providers.md` — rota do kit no `KitServiceProvider` com `web` | `app/Providers/**` | n.a. | nenhuma rota nova (`## Rotas` do `01`) |
| `resources.md` — Resource do /app fecha a query sem organização; assimetria em `get*AuthorizationResponse()` | `app/Filament/App/Resources/**` | n.a. | `getEloquentQuery()` e respostas de autorização intocados; `EscopoFailClosedTest` verde na suíte Tenancy |
| `settings.md` — chave lida por request; três lugares (propriedade, mapa, migration) | `app/Settings/**` | aplicada | as três lidas por request (`aplicaCarregamento()` no `configureUsing`, `visoesHabilitadas()` no trait, `allow_public_views` pelo pacote) — CT-16; propriedade + mapa + migration — CT-02, `KitInfoTest` CT-06 (69) |
| `specs.md` — justificativa de pacote só depois de ler o vendor, citação por símbolo, varredura de padrão | `wikis/specs/**` | aplicada | dossiê e Superfície Livewire com `arquivo:símbolo:linha` do vendor lido por inteiro no step 3; `citacoes.sh` na Verificação Final (pendente do bump 1.0.1) |
| `support.md` — `.env` por `SubstituicaoEmArquivo` | `app/Support/**` | n.a. | `CarregamentoDaTabela` não grava `.env`; `DensidadeDoLayout`/`DockDeAtalhos` só mudaram citação e rótulo |
| `testes-browser.md` — servidor in-process, `npm run build` + `view:cache`, tema por visita, arranjo por cenário, `kit:arte` por lista, sem `--parallel`, seletores por atributo/texto | `tests/BrowserTenancy/**` | aplicada | `CapturaDeArteTest`: `visitarDockNoTema()` declara o tema em toda visita, cada cenário aquece e arranja o próprio painel, nomes literais em `KitArte::IMAGENS`/`CLIPES` (`KitArteTest` 57/57), seletor do gatilho por classe `fi-ac-btn-group` + texto (o modo estrito do Playwright acusou o ambíguo); `composer art` em série |
| `testes.md` — helper cruzado em `Pest.php`; tela que grava; `noPainelBootado`; papéis por suíte; `toContain` sem mensagem; docs com `naArvoreDoKit()`; ID de CT nos nomes | `tests/**` | aplicada | lote D0 (helpers em `tests/Pest.php`); CT-13 grava a aba; L6 boota o /app por `GET` real; `admin_app` só em `tests/Tenancy`; nenhum `toContain` com mensagem nos 7 arquivos novos/alterados (as 4 ocorrências de `grep 'toContain([^)]*,'` são needles variádicos ou a vírgula interna do `__()`); `PacotesRodada3Test` com `->skip(fn () => ! naArvoreDoKit())` nos casos que leem docs e `art/`; todo `it()` começa com `[CT-nn]` (`ids-ct.sh` silencioso salvo CT-B01, pendente) |

## Quality Gate

<!-- Preenchido no step 11. -->

## Candidatos a Rule

<!-- Step 12. -->

## Auditoria Pré-Implementação

Entendimento confirmado: 2026-10-14 — autorização existente na mensagem do solicitante ("instale no projeto em caso de agregar valor", "crie uma nova branch") e sessão em autonomia (o solicitante não está disponível durante a execução) — 1 rodada; perguntas: 0 fato (todas respondidas no step 3 pelo código e pelo vendor), 10 desenho (D1–D10 do `01`, decididas sob convenção existente), 4 requisito (Q1–Q4 do `00`, respondidas pela recomendação que falha fechado e registradas como P-01…P-04; resposta diferente entra como Adendo)

### Confronto código × afirmação (step 5)
| Pergunta | O `01` dizia | O código faz | Resposta (quem, data) | Onde a wiki mudou |
|---|---|---|---|---|
| — | nenhuma divergência de comportamento: o plano só estende pontos existentes (`configuraTable()`, `abaTabelas()`, `mapaDeConfiguracao()`, `paineisDasPermissoesCustomizadas()`) | idem | sessão, 2026-10-14 | — |

### Revisão profunda (step 5) — premissas do plano contra o código real
| Premissa do plano | O código real diz | Correção aplicada na wiki |
|---|---|---|
| derivação do `04` (despacho #5): "o inventário de `PermissoesDeAcoesTest` vai ficar vermelho até as quatro Actions novas entrarem nele" | mesma varredura: `Action::make(` em `app/Filament/**`; Action do vendor não aparece e a linha viraria órfã | nenhuma — a nota do agente foi devolvida corrigida na re-derivação |
| passo 8: "`inventarioDeAutorizacao()` ganha as quatro linhas das Actions do pacote" | o caso `não deixa nenhuma Action ou item de navegação do kit fora do inventário` (`tests/Kit/PermissoesDeAcoesTest.php:'não deixa nenhuma Action ou item de navegação do kit fora do inventário':368`) cala também `$orfas` — linha do inventário sem `Action::make(` correspondente em `app/Filament/**` reprova; as quatro Actions são construídas no vendor | passo 8 reescrito: sem linha no inventário; a prova da permissão é CT do `04` |
| citações `abaTabelas():530`, `'tabelas':278`, `coagir():241` | `:529`, `:277`, `:236` (`grep -n`) | corrigidas no `01`; `citacoes.sh` acusa só os paths de `vendor/` dos pacotes ainda não instalados (existirão depois do passo 1) |
| passo 9: "`ListRoles`: `getActions()` → `getHeaderActions()`" | `getHeaderActions()` do Filament devolve `$this->getActions()` por default (`vendor/filament/filament/src/Pages/Concerns/InteractsWithHeaderActions.php:getHeaderActions():55-58`): o nome antigo é honrado | passo 9: `ListRoles` mantém `getActions()`; o spread entra ali |
| `ListRoles` sem `HasResizableColumn` (7 de 10 páginas o têm) | `grep -lF HasResizableColumn app/Filament` = 7; `ListRoles`, `ListConvites` (admin) e `ListComposerReleasePackages` não o usam | fora do escopo (opt-in por decisão da wiki `settings-do-kit`); o passo 9 põe `VisoesDeTabela` nas 10 |
| `docs/*/operacao/depois-de-criar-resources.md` cita o `HasResizableColumn` como passo de Resource novo | a varredura da irmã achou a página | passo 13 ganha a linha dessa página |

### Varredura da classe irmã (step 5)
| Classe nova | Irmã | Ocorrências da irmã | A nova entra? |
|---|---|---|---|
| `App\Support\CarregamentoDaTabela` | `App\Support\DensidadeDoLayout` | `grep -rlF DensidadeDoLayout app config database tests` = 12 arquivos: a Page de configurações, os 3 providers de painel e o `KitServiceProvider` (render hook da densidade — não se aplica), `ConfiguracoesDoKit` (settings), `DetalheDoUsuario` e `DockDeAtalhos` (só citam no docblock), `config/kit.php`, a migration de settings da densidade, `DensidadeDoLayoutTest` | entra em: Page de configurações (passo 6), settings + migration (passo 5), `config/kit.php` (passo 3), teste próprio (`04`). Não entra nos providers (o consumo é `ConfiguraFilamentGlobal`, passo 7) |
| permissão `Usar:VisoesDeTabela` | `Usar:Dock` | `grep -rlF 'Usar:Dock' app config database tests docs` = 20 arquivos: `filament-shield.php`, `PapeisSeeder`, `PluginDoDock`/`DockDeAtalhos` (consumo), os 3 providers (plugin do dock), Page de configurações e `config/kit.php` (só prosa), `tests/Pest.php` (`concederDock()`), `PermissoesDeAcoesTest` + `PermissoesDeAcoesTenancyTest` (CT-37/41 — oferecida na tela, semeada por papel), `DockDeAtalhosTest`/`Tenancy`, 6 páginas de docs | entra em: shield config + seeder (passo 8), helper em `tests/Pest.php` (`04` decide), CT de "semeada por papel" e "oferecida na tela" (`04`), docs (passo 13). Não entra nos providers (não é plugin) |
| setting `visoes_de_tabela` / `tabela_carregamento` | `colunas_redimensionaveis` | 9 arquivos: Page, `ConfiguraFilamentGlobal`, settings, `config/kit.php`, migration de criação, `BooleanoDoEnvTest`, `ConfiguracoesDoKitTest` (dataset do mapa, `:80`), `DefaultsDeTabelaTest`, `.env.example` | entra em todos os equivalentes (passos 3, 5, 6, 7) e nos datasets de `ConfiguracoesDoKitTest` e `DefaultsDeTabelaTest` (`04`) |
| trait `App\Filament\Concerns\VisoesDeTabela` | `Asmit\ResizedColumn\HasResizableColumn` | 7 páginas `List*` + `ConfiguraFilamentGlobal` (docblock) + 4 docs (`configuracao-global-filament`, `depois-de-criar-resources`, pt/en) | entra nas 10 páginas (passo 9) e nas duas páginas de docs (passo 13) |

### Auditoria Ponytail (step 6) — skill `ponytail-review` carregada na sessão (passe sobre `01`/`02`)
| # | Sugestão de corte | Aplicada? | Onde |
|---|---|---|---|
| 1 | `01` passo 8: delete — 4 linhas de inventário para Actions que a varredura não encontra (viram órfãs e reprovam). Nada as substitui; a permissão é provada por CT | sim | `01`, passo 8 |
| 2 | `01` passo 14: delete — captura `tabela-carregar-mais-escuro`: o rodapé é o mesmo nos dois modos; uma captura escura (`tabela-rolagem-escuro`) prova o tema | sim | `01`, passo 14 |
| 3 | `01` passo 13: shrink — parágrafo em `qualidade-de-codigo.md` vira uma frase | sim | `01`, passo 13 |
| 4 | `01` passo 9: delete — renomear `getActions()` de `ListRoles`: o Filament já o honra | sim | `01`, passo 9 |
| 5 | `01` passo 4: yagni? — `padrao()` separado de `coagir()` | recusada: dois chamadores (`config/kit.php` e a migration de settings), mesmo contrato de `DensidadeDoLayout` | — |
| 6 | `01` passo 9: shrink? — sete aliases de trait | recusada: três sobrescritas são a guarda D4 (zero consulta desligado) e quatro são o único ponto onde `->authorize()` entra sem editar o vendor; alternativa (percorrer o `ActionGroup` devolvido) é mais código | — |
| 7 | `02`: Lean already | — | — |
| net | −4 linhas de teste, −1 cenário de captura, −1 renomeação | | |

## Despachos

| # | Step | Agente / tarefa | Modelo | Não recebeu | Resultado | Custo | Auditoria do retorno |
|---|---|---|---|---|---|---|---|
| 1 | 0–3 | Sem despacho — captura verbatim do requisito, criação da branch e leitura direta das rules, das referências da skill e das wikis parentes | — | — | — | — | — |
| 2 | 3 | **Codex CLI 0.162.1** (`codex exec --yolo`, pedido pelo solicitante — RQ-03): análise dos cinco pacotes (Packagist P2, GitHub raw, composer.json literal, sobreposição com o kit) sem alterar o repositório | modelo padrão do Codex (não exposto) | a conversa; a leitura do vendor feita pela sessão | relatório de 26 KB em `scratchpad/codex/analise-pacotes.md`: recomenda instalar só o `phpstan-macros`; prefere o `remember-table` ao `saved-views` se visões por organização forem requisito; aponta que o `saved-views` não tem `tenant_id` e que o `list-view` guarda o modo sem organização | — (host não reporta) | `git status --porcelain` antes = depois (só a wiki untracked); amostragem: constraints do `remember-table` (`php ^8.2`, `filament ^5.0`, sem Laravel) conferem com o `composer.json` da cópia temporária; "o `saved-views` CI parte de `^5.8.3`" não conferido pela sessão (não decide nada); o relatório diz Laravel 13.32/Filament 5.8.2 instalados — era o `vendor/` defasado, realinhado depois (ver Notas) |
| 3 | 3 | Sem despacho — `WebFetch` das cinco páginas do diretório de plugins, resolução dos quatro pacotes do Packagist numa cópia temporária (`scratchpad/vendor-cands`, Laravel 13.36 + Filament 5.10.2) e leitura integral de `HasRememberTable`, `TableView`, `TableState`, `InfiniteScroll*`, `extension.neon`; `composer.json` cru do `list-view` pelo GitHub | — | — | tabela de vereditos no `01`; Superfície Livewire no `02` | — | — |
| 4 | 3 | Sem despacho — baseline `Kit`/`Tenancy` em background (primeira rodada reprovou no boot por `vendor/` defasado; segunda rodada após `composer install`) | — | — | ver seção 0 | — | — |
| 5 | 7 | `general-purpose` como analista — derivação do `04`/`05` pela `feature-test-design` (agente `analista` inexistente em `.claude/agents/`; `fw-*` só para adversário/executores) | opus | conversa; `03`; `01` fora do extrato (rotas, UI, env, mapeamentos, paths); `02` fora da Superfície Livewire; o código novo da árvore | `04` com 52 CT, 21 regras, 87 mutantes, 0 sem matador; `05` com CT-B01 (rolagem); 11 costuras propostas; 7 perguntas (Q?1–Q?7 → Q6–Q12); rascunhos em `scratchpad/derivacao/` | 365.375 tokens · 1.377 s · 35 tool uses | presença ok (dois rascunhos + relatório no formato pedido); integridade: `git status --porcelain` antes × depois sem arquivo novo fora do que a sessão editou (o agente declarou não ter lido os modificados); amostragem: `grep -cE '^[[:space:]]*(Cenário\|Esquema do Cenário): \[CT-[0-9]+\]'` = 52, `^## Regra R` = 21, `^\| M-?[0-9]+` = 87 conferidos pela sessão; uma premissa do agente reprovada (inventário de `PermissoesDeAcoesTest` para Actions do vendor — viraria órfã, já descartada no step 5) e devolvida na re-derivação |
| 6 | 7 | mesmo agente retomado por `SendMessage` — re-derivação após as respostas Q6–Q12 (P-13…P-17, Q11 aberta, D11) | opus | idem #5 | `04` com 60 CT, 25 regras, 107 mutantes, 0 sem matador; CT-15/24/39 alterados, CT-53…CT-60 novos, R22…R25; nenhum obsoleto; 1 pergunta nova (Q?1 → Q13) | 408.480 tokens · 315 s | greps conferidos pela sessão (60/25/107); amostragem: CT-15 invertido conforme P-15; nota do inventário removida |
| 8 | 7 | `fw-adversario-ct` — revisão adversarial do `04`/`05` (rodada 1) | opus (agente) | `01`, `02`, `03`, `07`, código, vendor, conversa | 41 achados (22 implementações que passam, 9 oráculos fracos, 4 cenários malformados, 3 RQ sem cenário discriminante, 3 sondas), 14 tentativas rejeitadas com motivo, 7 perguntas de requisito (→ Q14–Q20 / P-19…P-25); leituras negadas: nenhuma | 134.815 tokens · 513 s · 8 tool uses | presença ok (formato fixo); amostragem: ADV-02 confere (CT-28 roda com compartilhamento desligado), ADV-13 confere (CT-45 sem `"all"`), ADV-28 confere (CT-12 só "sem erro"); relatório gravado pela sessão em `scratchpad/derivacao/adv-rodada-1.md` para o derivador |
| 9 | 7 | derivador retomado — fechar ADV-01…ADV-41 | opus | idem #5 | **reprovado pela própria auditoria do agente**: o arquivo do relatório ainda não existia quando ele leu; recusou fechar "15 de 41" só pela mensagem e devolveu o `04` intacto — comportamento correto; redespachado com o arquivo gravado (#10) | 426.855 tokens · 105 s | `04` conferido intacto (62/26/110) |
| 10 | 7 | derivador retomado — fechar ADV-01…ADV-41 com o relatório no disco e a correção da P-24 | opus | idem #5 | `04` com **74 CT, 26 regras, 147 mutantes, 0 sem matador**; CT-63…CT-74 novos, 41 ADV fechados (nenhum rejeitado), seção `## Revisão adversarial — rodada 1`, personas Fábio e Gil, helpers do Setup Global; backup em `scratchpad/derivacao/04-wiki-antes-adv1.bak` | 505.545 tokens · 592 s | greps conferidos pela sessão (74/26/147, 0 `Q?n`); amostragem: ADV-28 → CT-12 com a P-24 corrigida (Select mostra `paginacao`); ADV-29 → `chaveDaTabela()` devolve a string recebida; R10 com 8 CT e R15 com 5 (estouro justificado pelos achados) |
| 11 | 7 | `fw-adversario-ct` retomado — rodada 2 (última): conferir os 41 fechamentos e CT-63…CT-74 | opus (agente) | idem #8 | 36 fechados, 5 parciais (ADV-01, 11, 15, 28, 38), 9 achados novos (ADV-42…ADV-50), 15 rejeições, 2 perguntas (→ Q21/P-26, que substitui P-19, e Q22/P-27); leituras negadas: nenhuma | 206.754 tokens · 172 s | presença ok; amostragem: ADV-44 confere (o CT-12 tinha o `paginacao` no Dado), ADV-48 confere (texto do rodapé contradiz o CT-44 no teto); relatório gravado em `scratchpad/derivacao/adv-rodada-2.md` |
| 13 | 7 | derivador retomado — fechar os 5 parciais e ADV-42…ADV-50 com P-26/P-27 | opus | idem #5 | `04` final: **78 CT, 26 regras, 154 mutantes, 0 sem matador**; CT-75…CT-78 novos; `## Revisão adversarial — rodada 2`; débito declarado: CT-75…CT-78 sem revisão independente (teto de 2 rodadas); `chaveDaTabela()` passa a ler `getTableViewsKey()` da página montada | 535.127 tokens · 169 s | greps conferidos pela sessão (78/26/154); `rastreabilidade.sh`: só RQ-03 e RQ-08 sem CT (restrições de processo — Cobertura do `01` ajustada para "—" com justificativa) |
| 14 | impl. | Codex CLI (`--yolo`) com o contrato do `fw-executor-ct` — onda 1, 4 lotes em paralelo: L1 `CarregamentoDaTabelaTest` (CT-09, 10) · L2 `DefaultsDeTabelaTest` (CT-01, 02, 03, 11) · L3 `ConfiguracoesDoKitTelaTest` (CT-12…16, 58, 60, 76) · L4 `PacotesRodada3Test` (CT-49…52, 62) + `QualidadeDeCodigoTest` (CT-48) | modelo padrão do Codex (não exposto) | `01`, `02`, `03`, conversa; `tests/Pest.php` (dono D0) | L1 15/15 · L2 21/26 (CT-01 ×3 e CT-03 ×2 vermelhos **b**) · L3 51/55 (CT-14, CT-15, CT-60 vermelhos **b**) · L4 3/6 + 30/30 (CT-50 ×2 e CT-51 vermelhos **b**); nenhum vermelho relaxado | — (host não reporta) | `git status` antes × depois: só os 6 arquivos dos lotes; vermelhos (b) roteados pela sessão — ver Desvios: 2 correções de especificação (CT-01, CT-03), 3 correções de código (CT-14 `rule('string')`, CT-15 `mutateFormDataBeforeSave()` que não existia, CT-60 rótulos), 1 de docs (CT-50) e 1 de capturas (CT-51); reexecução: L2 26/26 (com L4 31/32), L3 55/55 (17/17 no filtro), L4 só CT-51 vermelho até o `composer art` |
| 15 | impl. | Codex CLI (`--yolo`) com o contrato do `fw-executor-ct` — onda 2, 4 lotes em paralelo: L5 `VisoesDeTabelaTest` (35 CT) · L6 `VisoesDeTabelaTenancyTest` (11 CT) · L7 `CarregamentoContinuoTest` (10 CT) · L8 `PermissoesDeAcoesTest` + `PermissoesDeAcoesTenancyTest` (CT-17, 18, 19) | modelo padrão do Codex (não exposto) | idem #14 | L5 120/130 (CT-08, CT-28, CT-32, CT-63 vermelhos **b**) · L6 34/34 · L7 43/46 (CT-46, CT-71 vermelhos **b**) · L8 42/42 + 27/27; fixture `tests/Kit/fixtures/ListUsersAgrupavel.php` criado para o CT-75 (namespace `Tests\Kit\fixtures`, em caixa baixa como a pasta `tests/Kit/fixtures/` que já existia com o golden do Docker — PSR-4 é sensível a caixa no Linux do CI e a pasta manda; a sessão chegou a trocar para `Fixtures` e voltou atrás ao ver a pasta tracked) | — (host não reporta) | `git status` antes × depois: só os 5 arquivos de teste + o fixture; vermelhos (b) roteados — 1 correção de código (CT-08: `->visible()` nas quatro Actions) e 5 de especificação (CT-28 payload com "Minha", CT-32 tipo errado → recusa/P-28, CT-46 recusa fora do contínuo + 5, CT-63 coluna `origem`, CT-71 5 usuários); segunda passada do Codex em L5 e L7 (#16) |
| 16 | impl. | Codex CLI (`--yolo`) — segunda passada: L5 `VisoesDeTabelaTest` (CT-08, 28, 32, 63) e L7 `CarregamentoContinuoTest` (CT-46, 71), com o `04` corrigido | modelo padrão do Codex (não exposto) | idem #14 | L5 **130/130** (646 asserções) · L7 **46/46** (284); nenhum vermelho restante | — (host não reporta) | `git status` antes × depois: nenhum arquivo além dos dois lotes (o diff do status é vazio); saída literal do pest no relatório |
| 12 | impl. | **Codex CLI 0.162.1** (`codex exec --yolo`) com o contrato do `fw-executor-ct` — lote D0: helpers de `tests/Pest.php` (`visaoGravada`, `chaveDaTabela`, `ligarVisoesDeTabela`, `ligarCarregamento`, `concederVisoesDeTabela`) e mover `composerDoKit()` (RQ-03) | modelo padrão do Codex (não exposto) | `01`, `02`, `03`, conversa | 5 helpers + `composerDoKit()` movido; `PacotesRodada2Test` + `DockDeAtalhosTest` 137/137 (816 asserções); pint e `php -l` limpos (saída literal no relatório) | — (host não reporta) | `git status --porcelain` antes × depois: só `tests/Kit/PacotesRodada2Test.php` novo no diff (`tests/Pest.php` já estava modificado pela sessão); 6 funções conferidas por `grep -n '^function '` em `tests/Pest.php`; `function composerDoKit` = 0 no arquivo de origem |
| 17 | impl. | **Codex CLI 0.162.1** (`codex exec --yolo`) com o contrato do `fw-executor-ctb` — `tests/Browser/CarregamentoContinuoTest.php` (CT-B01) | modelo da sessão do Codex (não exposto pelo host) | `01` fora da Superfície de UI; `02`, `03`, `04`, conversa | CT-B01 verde na primeira execução (`{"tests":1,"passed":1,"assertions":11,"duration_ms":73366}`); nenhuma correção do `05`; Roteiro parcial (só a linha 1 exercitada, as demais marcadas ⚠️ e fechadas pela sessão com as capturas) | 76.876 tokens · ~4 min | arquivo lido inteiro pela sessão: seed dos papéis, `ligarCarregamento('rolagem', 25)`, 74 usuários + master, aquecimento por `GET`, `inLightMode()`, esperas por estado (`assertSee`/`assertAttribute`), sem `wait(n)`; nenhum arquivo fora do teste tocado (`find … -newer prompt-ctb.md`: só `VisoesDeTabela.php`, editado pela sessão às 20:43, antes do despacho); pint aprovado; rerodado pela sessão dentro do `ids-ct.sh` (exit 0), 2026-10-14 |
| 7 | 7 | mesmo agente retomado — Q13 → P-18 (teto por organização) e P-12 coberta | opus | idem #5 | `04` com **62 CT, 26 regras, 110 mutantes, 0 sem matador**; CT-61 (P-18, Tenancy) e CT-62 (P-12, lock ≥ 5.10 e `CONTENT_AFTER`); `05` inalterado (CT-B01) | 415.234 tokens · 49 s | greps conferidos pela sessão (62/26/110); rascunhos copiados para `04-casos-de-teste.md` e `05-casos-de-teste-browser.md`; `## Costuras de Teste` confirmadas pela sessão (11 linhas, convenção existente), 2026-10-14 |

## Blockers
- [ ] nenhum

## Desvios do Plano
- **CT-14 (b → código)**: `Select` do carregamento com array no payload passava pelo `in()` das opções e chegava ao settings como `null` (`TypeError` na propriedade `string`); ganhou `->rule('string')` — o mesmo remédio do `->rule('array')` do dock. `01` passo 6 segue válido (validação é detalhe do campo).
- **CT-15 (b → código)**: o `01` passo 6 mandava a regra P-15 para `mutateFormDataBeforeSave()`, mas a Page do kit **não tinha** esse método (a densidade coage no `mutateFormDataBeforeFill()`), e a primeira edição caiu no fill por engano; criado o `mutateFormDataBeforeSave()` com a coerção do carregamento (P-11/P-24) e a regra P-15. `01` passo 6 reescrito *(alterado em 2026-10-14)*.
- **CT-60 (b → código)**: os rótulos do enum divergiam do `## Mapeamentos` do `01` ("; nada muda", "da tabela"); o código passou a usar os rótulos do plano (P-17).
- **CT-01 (b → especificação)**: o `04` exigia "linha ativa" no `.env.example`; a convenção do kit é a linha COMENTADA com o default (`# KIT_TABELA_...=`), porque o banco vence e o arquivo é semente. `04` e teste corrigidos *(alterado em 2026-10-14)*, sem mudar o `.env.example`.
- **CT-03 (b → especificação)**: o `04` exigia tamanho inicial 7 com as opções de página de hoje (5, 10, 25, 50) — 7 fora da lista faz a tabela começar pela primeira opção, comportamento pré-existente que "nada muda" preserva; o cenário passou a usar 25. `04` e teste corrigidos *(alterado em 2026-10-14)*.
- **CT-50 (b → docs)**: a página nova precisava trazer, nela mesma, o critério e os cinco vereditos com motivo e reabertura (RQ-01, RQ-06, P-06), não só o link para `pacotes-instalados`; seção "Os cinco pacotes avaliados e o critério" acrescentada em pt e en.
- **CT-08 (b → código)**: com as visões desligadas, `getTableViewActions()` vazio escondia o botão, mas as quatro Actions continuavam montáveis pelo NOME e gravavam; o trait ganhou `->visible(visoesHabilitadas())` em cada uma (bloqueia o `mountAction` como o `authorize`). `01` passo 9 segue válido (a guarda era prometida; faltava nas Actions).
- **CT-28 (b → especificação)**: o formulário de gerenciar reenvia todas as visões do usuário; payload só com id alheio/inexistente é, para o pacote, "apague as minhas que faltam" — apagar o próprio dado por payload forjado não fere P-07/P-08. As linhas passam a enviar "Minha" junto do intruso *(04 alterado em 2026-10-14)*.
- **CT-32 (b → especificação, premissa P-28)**: `activeTableView` com array é recusado pelo Livewire com `TypeError` na propriedade `string` do vendor — nada aplicado nem gravado; a linha afirma a recusa, não "sem exceção".
- **CT-46 (b → especificação)**: fora do modo contínuo o hook do pacote não intercepta e o Livewire responde "method not found" (recusa); e 7 não está nas opções de página (ver CT-03) — o controle passa a 5.
- **CT-63 (b → especificação)**: só `origem` é `toggleable()` na listagem de usuários; a visão só esconde o que o gerenciador de colunas deixa esconder — coluna trocada de "email" para "origem".
- **CT-71 (b → especificação)**: mesmo motivo do CT-03 (7 fora das opções) — 5 usuários.
- **CT-51 (b → capturas, reverte um corte do Ponytail)**: toda captura da feature tem o par escuro e o contínuo tem clipe animado (RQ-07); `tabela-carregar-mais-escuro` restaurada e clipes `carregamento-continuo` / `-escuro` (3 quadros no modo botão) acrescentados em `CapturaDeArteTest` e `KitArte`; `01` passo 14 reescrito *(alterado em 2026-10-14)*. Fica vermelho até o `composer art` publicar os arquivos.
- Passo 1: Filament 5.8.2 → 5.10.2 (`composer update "filament/*"`), não previsto — o `infinite-scroll` usa `TablesRenderHook::CONTENT_AFTER`, que só existe no 5.10 (a cópia temporária do step 3 resolveu Filament 5.10.2 e por isso não acusou). Registrado como Q5/P-12 no `00`, passo 1 e Riscos do `01` marcados `*(alterado em 2026-10-14)*`, `CHANGELOG` em *Alterado*. Suíte pós-bump medida antes de qualquer código do kit (ver seção 1).

## Notas de Implementação
- O `vendor/` da árvore de trabalho estava defasado em relação ao `composer.lock` (o pacote do dock, `hoceineel/filament-quick-action-dock`, não estava instalado e o boot morria em `PluginDoDock::make()`); `composer install` o realinhou (Laravel 13.36.0). O `filament:assets` do `post-update` republicou `public/js/wallacemartinss/filament-onboarding/components/onboarding-tour.js` diferente do commitado — restaurado com `git checkout`, fora do escopo desta feature.
- O `composer require` via PowerShell gravou `"1.0"` em vez de `"^1.0"` no `composer.json` (o `composer.bat` passa por `cmd.exe`, que come o `^`); corrigido à mão e `composer update --lock` refez o hash (`composer validate --no-check-all`: válido). Lição: composer no Windows pelo `php composer.phar` em bash, não pelo `.bat`.
- O `filament:assets` do bump deixou `public/js/filament/forms/components/cod72F7.tmp` (resto de cópia concorrente) — apagado. Os assets do Filament em `public/` nunca foram recomitados nos bumps anteriores (último commit: o esqueleto), então o deploy depende do `filament:assets` do `post-update`; neste PR eles entram atualizados para 5.10.2, e `public/css/asignua/` (folha do `infinite-scroll`) entra como as folhas dos outros plugins.
- **Quebras do bump do Filament 5.10 (P-12), todas em código do kit e corrigidas antes de qualquer teste da feature**: (1) notificações saíram da sessão (`filament.notifications`/`claimed_notifications`) para `Filament\Notifications\NotificationMailbox` (cache) — helpers `avisosDoFilament()`/`limparAvisosDoFilament()` em `tests/Pest.php` e os testes do dock trocados (Kit 124/124, Tenancy 65/65); (2) todo widget aborta 403 antes do `mount()` sem `canView()` (`vendor/filament/widgets/src/Concerns/CanAuthorizeAccess.php:bootCanAuthorizeAccess():9-19`, `:16`) — `InsightsDasOrganizacoesTest` monta os widgets como `admin` no `beforeEach` (21/21); (3) `getNavigationLabel()`, `NavigationGroup::getLabel()` e `Filament::getTenantName()` passaram a devolver `string|Htmlable` — `textoDoRotulo()` em `DescobreCardsDoPainel` e `DockDeAtalhos` (PHPStan 0); (4) 11 citações `arquivo:símbolo:linha` deslocadas em `app/`, `docs/` e `tests/` (`CitacoesDeCodigoTest` CT-26 verde); (5) `GraficosDoDashboardTest` ficou vermelho só no run paralelo pós-bump e verde isolado e no run seguinte — flake de ordem a vigiar.
- **Achado das capturas (não previsto em CT)**: o dropdown "Visões" abria por baixo do campo de busca da tabela — `asmit/resized-column` eleva `.fi-ta-header-ctn` a `z-index: 21`, e `.fi-dropdown-panel` do Filament 5.10 fica em `20`. Vale para qualquer `ActionGroup` do cabeçalho da página, não só para esta feature. Correção em `resources/css/filament/kit.css` (`.fi-dropdown-panel { z-index: 22 }`), CHANGELOG em *Corrigido*. É defeito de **interação entre plugins**, que o `04` não derivaria do `00`: candidato a `P-nn` + CT no step 9 (eixo "simetria de guarda"/layout) — registrado aqui para o revisor.
- O painel do dropdown das visões nasce estreito e truncava "Administradores" com o badge; o trait põe `dropdownWidth(Width::Small)` no grupo.
- O lock resolveu `asignua/filament-infinite-scroll` **v1.0.0**; a cópia temporária do step 3 era a 1.0.1 (correção de Octane no render hook). Subido para `^1.0.1` em 2026-10-14 (`composer require`, lock v1.0.1, `filament:assets` republicado em `public/css/asignua/filament-infinite-scroll/`); as 13 citações do vendor e as 5 do kit refeitas contra a 1.0.1 — `citacoes.sh` silencioso.
- O `remember-table` **não traz pt_BR** (`resources/lang/` tem só `ar` e `en`), ao contrário do que a página do plugin lista — por isso o passo 10.
- Precedência de trait provada por script no step 3: método declarado no trait que `use` outro trait vence o do trait usado (`kit→pacote`); o Livewire chama `booted{NomeDoTrait}` por `class_uses_recursive` + nome do método (`vendor/livewire/livewire/src/Features/SupportLifecycleHooks/SupportLifecycleHooks.php:'booted':125`), então a sobrescrita de `bootedHasRememberTable` no trait do kit é a chamada.

## Referências Abertas
- `roteamento-e-despacho.md` — step 3 (antes do primeiro despacho) — 2026-10-14
- `ponytail-caveman.md` — início da sessão — 2026-10-14
- `hosts-e-subagentes.md` — **não existe** em `references/` da skill instalada (`cat` devolve "No such file"); limitação registrada
- `template-00-requisito.md` — step 4 — 2026-10-14
- `entrevista-tres-raias.md` — step 4 — 2026-10-14
- `pesquisa-step-3.md` — step 3 — 2026-10-14
- `template-01-plano.md` — step 4 — 2026-10-14
- `template-02-adr.md` — step 4 — 2026-10-14
- `template-03-progresso.md` — step 4 — 2026-10-14
- `estrutura-criada.md` — step 4 — 2026-10-14
- `glossario.md` — step 4 — 2026-10-14
- `padrao-de-log.md` — step 4 — 2026-10-14
- `citacoes-de-codigo.md` — step 4 — 2026-10-14
- `delegacao-casos-de-teste.md` — step 4 (lida antes do step 7) — 2026-10-14

## Retrospectiva
- **Funcionou bem**: —
- **Faltou no plano**: —
