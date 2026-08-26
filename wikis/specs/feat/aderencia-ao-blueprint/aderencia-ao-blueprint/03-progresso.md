# Progresso — Aderência ao Blueprint

**Concluída em 2026-08-26.**

## 1. `ProjetoResource::getEloquentQuery()` fail-closed — N-04

- [x] `whereRaw('1 = 0')` sem tenant, delegação ao pai com tenant; docblock com a medição (4 de 4)
- [x] `tests/Tenancy/EscopoFailClosedTest.php` — sweep de `getResources()` do `/app` + coluna válida
- [x] Mutação: sem o override → vermelho

## 2. Sweep de autorização sobre `getResources()` — N-29

- [x] `tests/Kit/PermissoesDeResourcesTest.php` — 47 casos: âncora de população, policy registrada por modelo, índice abre com permissão, fecha sem
- [x] **O sweep achou o que a falta dele escondia** — ver "Notas de Implementação"
- [x] `App\Support\PoliciesDeVendor` — 10 `Gate::policy()` (8 não registradas + 2 do onboarding)
- [x] `App\Policies\OnboardingFlowPolicy` e `OnboardingConditionPolicy` (as do vendor devolvem `true`)
- [x] `AiRunResource::canAccess()` com `&& parent::canAccess()`
- [x] `App\Filament\Infra\Resources\ComposerReleasePackages\{ComposerReleasePackageResource, Pages\ListComposerReleasePackages}` — subclasse com `$shouldSkipAuthorization = false` e página apontando para ela; plugin com `resource(enabled: false)`; docblock do provider corrigido ("ali não havia lacuna" → havia)
- [x] `tests/Tenancy/PermissoesDeTenantResourceTest.php` — o par do `Tenant`, que é config-gate na suíte Kit
- [x] Mutação: sem o registro → **19 vermelhos**; AiRun sem o pai → 1; Composer com skip → 1

## 3. `PermissaoDaTela::permite()` fail-closed — §5 → ADR-02

- [x] `false` quando a chave não resolve e há usuário; delega sem usuário; docblock com o mecanismo (`once()`)
- [x] `tests/Kit/PermissaoDaTelaTest.php` — 3 casos (fora do mapa, mapeada, sem usuário)
- [x] Mutação: `: true` de volta → vermelho

## 4. Qualidade de componente — N-11, N-14, N-19, N-34

- [x] 6 `ignoreRecord: true` removidos; `->integer()` ×2; `->sortable()` ×1; `assertSchemaStateSet`
- [x] `tests/Kit/AderenciaAoBlueprintTest.php` — varredura textual em `app/` e `tests/`, comentários ignorados, âncora de população

## 5. Cobertura de teste — N-30, N-31, N-32 (sub-agente, lista fechada)

- [x] `tests/Kit/AgenteIaResourceTest.php` — 16 casos (o resource não tinha uma linha de teste)
- [x] `tests/Kit/FiltrosDeTabelaTest.php` — 5 (`pendente`, `status`, `task`, `driver`)
- [x] `tests/Tenancy/FiltrosDeTabelaTenancyTest.php` — 2 (`ativo` de organizações)
- [x] `ConviteTest` +3 (dataset `CreateConvite`), `AdminDaOrganizacaoTest` +7 (dataset `CreateUser` do `/app`), `PermissoesDeAcoesTenancyTest` +4 (`recusar` via `callAction` com efeito e metade negativa; item de menu `convitesRecebidos` com/sem permissão), `PermissoesDeAcoesTest` +`assertActionHasUrl('dashboardAiTasks')`
- [x] Re-verificados por mim: 97/97 na suíte Kit

## 6. READMEs — RQ-08 (sub-agente, lista fechada)

- [x] 37 divergências aplicadas nos dois idiomas + `.env.example` (`KIT_ADMIN_NAME`, `KIT_REPOSITORY`)
- [x] Seção nova "Pacote novo com Resource: a policy precisa ser registrada" (PT/EN) — escrita por mim, porque o achado nasceu depois do agente
- [x] Testes de documentação: 36/36

## 7. Rules — RQ-09

- [x] `policies.md` (nova), `resources.md` (nova), varredura textual em `filament.md` (nova)
- [x] Duas emendas em `filament.md` (Resource no enforço; `canAccess` ≠ autorização de ação)
- [x] `index.md` regenerado pelo Boost

## 8. Lacunas declaradas — RQ-11

Ver seção própria abaixo.

## Verificação Final

- [x] `vendor/bin/pint --dirty` — passa
- [x] `vendor/bin/phpstan analyse` — 0 erros
- [x] Testes novos + afetados: 102/102 (sweeps) · 97/97 (Kit do agente) · regressão de 11 arquivos afetados: ver linha abaixo
- [x] Regressão dos 11 arquivos que tocam as telas cujas policies acordaram: **262/263** — o único vermelho foi o enforço do hub (`HubDeCardsTest`: "nenhum cartão sem descrição") acusando que `HubDeInfraestrutura::descricoesDosDestinos()` ainda apontava para o FQCN do **vendor** do Composer, e quem entra agora é a subclasse do kit. Um `use` trocado; 16/16. É o enforço existente pegando a minha própria mudança — exatamente para o que ele existe
- [x] Suíte `Tenancy` completa — ver linha do CI; rodada local em background durante a escrita
- [x] `composer.json`/`lock` fora de todos os commits (só carregam o `bp:on`)
- [ ] CI 100% verde no PR → merge → tag → release

## Auditoria Pré-Implementação

### Revisão profunda — premissas da norma contra o vendor

| Premissa | O vendor diz | Consequência |
|---|---|---|
| `ignoreRecord: true` é redundante (Blueprint) | `CanBeValidated.php:34` `= true`, consumido em `:566`/`:598` | N-11 confirmada; 6 remoções |
| `canAccess()` decide o índice | `CanAuthorizeResourceAccess.php:19` `abort_unless(canAccess())`; default = `canViewAny()` (`HasAuthorization:28-31`) | sobrescrever sem `parent::` desliga a policy — AiRun |
| `$shouldSkipAuthorization` vence a policy | `HasAuthorization:35-37` devolve `allow()` primeiro | Composer do vendor tinha `true` |
| Shield resolve policy de vendor para `App\Policies\{Basename}Policy` | `Utils::resolvePolicyFor():146-148` | as 8 policies do kit **teriam** funcionado com `enforcePolicies()` — mas ele lê `getResources()` `once()`, por isso o registro explícito |
| `discover_all_pages` está desligado | `config/filament-shield.php:404` | o mapa do Shield é por painel corrente; `once()` congela |

### Três sub-agentes de auditoria, e o que a re-verificação mudou

| Agente | Devolveu | Caiu na re-verificação |
|---|---|---|
| Código (A–F) | 4 FINDING, 21 PASS, 3 N/A | nada — mas N-04 subiu de "lido" para "medido" na instalação (4 de 4) |
| Testes (G) | 18 FINDING em 6 normas | nada — e o N-29 dele ("5 sem teste") escondia o achado principal, que só apareceu **escrevendo** o teste |
| Docs (RQ-08) | 39 divergências | **2 falsas**: `@laravel/multiplex` está no `package.json`; `general.md` está no `index.md` |

## Matriz de Mutação

| Mutação | Vermelhos | Esperado |
|---|---|---|
| `PoliciesDeVendor::registrar()` comentado | **19** (CT-02 ×8, CT-04 ×11) | sim |
| `AiRunResource::canAccess()` só o gate | 1 (CT-04 AiRun) | sim |
| Composer `$shouldSkipAuthorization = true` na subclasse | 1 (CT-04 ComposerRelease) | sim |
| `permite()` com `: true` | 1 (CT-07) | sim |
| `ProjetoResource` sem `getEloquentQuery()` | 1 (CT-05) | sim |
| nenhuma | 0 | sim |

## Desvios do Plano

- **O passo 2 cresceu três vezes.** O plano previa "um sweep"; o sweep achou 10 policies mortas, um `canAccess()` sem pai e um resource com autorização desligada. Virou uma classe de Support, duas policies, uma subclasse com página, uma mudança de plugin e um docblock corrigido. É o desvio certo: o plano dizia "escrever o enforço", e o enforço fez o seu trabalho antes de ser commitado.
- **O sweep de resources virou dataset, não laço.** A primeira versão em laço único produziu 302/500 que não existem em request real — vazamento de sessão e de painel entre iterações no mesmo processo. Dataset dá app e banco frescos por caso e a falha nomeia o resource. A lista é escrita (dataset roda antes do app), e a âncora de população compara com `getResources()`.
- **`Tenant` e `CommandRecord` fora do par, com motivo escrito.** `Tenant` é config-gate na suíte Kit (par próprio em `tests/Tenancy`); `CommandRecord` é gate do vendor sem `define`, só `master_global` passa.
- **ADR-03 (tampering) mantida**: não aplicado.

## Notas de Implementação

- **O achado principal não veio de nenhum agente.** O agente de testes disse "5 resources sem teste de autorização negativa". Escrever esse teste revelou que 10 policies nunca eram consultadas — o Laravel não descobre policy para modelo de vendor, e ninguém registrava. A medição que para em "falta teste" sem escrever o teste não mediu o que importava.
- **Um falso achado meu, e o que ele ensinou.** "Pulse não fecha ao revogar" era artefato: sonda em processo único, `once()` do Shield congelando o mapa no primeiro painel tocado. Em HTTP real (Playwright, sessão viva): 403. Retirado do comparativo — e o mecanismo que causou o falso achado é real fora de request, virou a ADR-02 e o `permite()` fechou.
- **Duas sondas minhas com controle errado.** `logout()` + segundo `actingAs` no mesmo teste → 302 até no controle (`/admin/users`, que eu sabia fechar). Sem o controle no conjunto eu teria reportado 10 "abertos" quando eram 9 e um artefato. **Controle positivo sempre, em toda sonda de negação.**
- **`Filament::getCurrentPanel()` e o cache do Spatie vazam entre casos no mesmo arquivo** — já registrado na v0.20.0; voltou a morder aqui, no laço do sweep. Dataset resolve os dois.
- **O sub-agente de docs errou 2 em 39** e eu marquei um como ✓ antes de checar. Toda correção que vira edição passa por segunda medição (ADR-05).
- **`kit:tenancy` recusa sem `.git`** e o `create-project` não cria um. Deliberado e documentado (README:938, 1369, 1700; o comando imprime o `git init`). Fica como observação de UX: é o primeiro tropeço de quem liga a tenancy no dia 1.

## Lacunas Declaradas

- **Flags do `kit:install` não exercitadas em instalação real nesta rodada**: `--no-npm`, `--no-seed`, `--force`, `--custom`. Cobertas por `CustomizadorDaInstalacaoTest` e `KitAdminTest`.
- **Comandos não rodados nas instalações**: `kit:admin`, `kit:update`, `kit:midia-privada`, `kit:convites-lembrar`, `kit:arte`. Têm suíte própria.
- **N-36 `preventFilePathTampering` global** — ADR-03: exige enumerar toda fonte de preenchimento antes; wiki própria.
- **Contagens do README sem critério operacional** ("telas navegáveis", "telas varridas") — ficam como estão, declaradas no `05-divergencias-readme.md`.
- **`ComposerReleasePackageResource`: o `shield:generate` futuro** vai gerar permissões pela subclasse do kit (mesmo modelo, mesmas chaves). Se o vendor um dia mudar o modelo, a subclasse acompanha por herança; se mudar a página, a subclasse da página precisa acompanhar à mão.

## Candidatos a Rule

Três gravadas (`policies.md`, `resources.md`, varredura em `filament.md`) e duas emendas — dentro do teto. Uma **não** gravada, por não passar o gate de escopo por path: "controle positivo em toda sonda de negação" é regra de método de teste, vale para `tests/**`, e já está implícita em `testes.md` ("Nem todo papel do kit existe em toda suíte"). Fica nesta nota.

## Retrospectiva

- **Funcionou**: traduzir a norma antes de medir. Cada achado aponta para uma linha do Blueprint e uma linha do vendor; discordar é discordar de algo escrito.
- **Funcionou**: instalação real do pacote publicado, não do checkout. O `--create-project` removendo o `.snyk`, o `kit:tenancy` exigindo git, o demo com Carla em duas organizações — nada disso aparece em teste de componente.
- **Funcionou melhor que o esperado**: escrever o enforço em vez de listar o que faltava. O sweep de 47 casos custou uma hora e achou o maior defeito de segurança do kit até hoje.
- **Faltou**: eu confiar em sonda de processo único para autorização por painel. Duas vezes. A regra "um teste por caso, app fresco" devia ter sido o ponto de partida, não a correção.
- **Faltou no plano**: prever que o agente de docs terminaria antes do achado principal. A seção de README sobre `PoliciesDeVendor` foi escrita à mão depois — certo, mas fora do fluxo.
