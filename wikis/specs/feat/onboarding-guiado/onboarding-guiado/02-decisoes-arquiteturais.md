# Decisões Arquiteturais — Onboarding guiado

Nenhuma decisão passou nos três portões. As escolhas de desenho estão em `## Decisões de Desenho`
do `01` (D1 a D9): a do 404 por middleware de painel (D9) é a mais próxima de uma ADR, mas troca-se
por um trait nas Pages sem migração de dado, então não é difícil de reverter.

## Superfície Livewire

A feature não cria componente Livewire, Page nem Widget próprios: registra os do pacote nos três
painéis (o checklist flutuante `OnboardingLauncher`, montado em toda tela pelo render hook, e a
página `OnboardingProgress`) e acrescenta condições ao `canView()` de um widget existente. A tabela
inventaria o que o cliente alcança no pacote.

Greps rodados no step 3 (`V=vendor/wallacemartinss/filament-onboarding/src`):

- `grep -rn "::find(\|whereKey(\|findOrFail(" $V` → 0 linhas: nenhuma busca por id cru
- `grep -rnE 'public…\$' $V/Livewire $V/Pages $V/Widgets` → 2 linhas (`$isOpen`, `$showWhenCompleted`); as do trait `InteractsWithOnboarding` conferidas por leitura
- `grep -rn '\$arguments\[\|\$data\[' $V` → 4 linhas (`StartTourAction`, três em `OnboardingProgress`)
- `grep -rn "extends Model" $V/Models` → 6 models

| Ponto de entrada (vendor) | Alcançável por | Fronteira aplicada | Evidência |
|---|---|---|---|
| `completeStep`, `skipStep`, `undoStep`, `startTour`, `finishTour`, `openMedia` (`string $stepKey, ?string $flowKey`) | `$wire.completeStep('chave')` no checklist de qualquer tela | o passo é procurado só entre as jornadas visíveis do sujeito resolvido no servidor (`findStepState` → `SubjectOnboarding::flow()`), e cada ação confere `canSelfComplete()`/`canSkip()`/`canUndo()`/`hasTour()`; o sujeito nunca vem do cliente. **Com o tutorial desligado depois de a tela abrir, o sujeito ainda é resolvido** (o trait usa `resolveSubject()`, não `current()`): ver Q10/P-04 | `vendor/wallacemartinss/filament-onboarding/src/Concerns/InteractsWithOnboarding.php:findStepState:410` |
| `tourProgress(string $key, int $index, int $total)`, `videoProgress(string $key, float $seconds, float $duration)` | eventos do runner de tour e do player | mesmo `findStepState`; `index`/`total`/`seconds` sem validação de faixa — gravam só no progresso do próprio sujeito | `vendor/wallacemartinss/filament-onboarding/src/Concerns/InteractsWithOnboarding.php:tourProgress:221` |
| `dismissFlow`, `restoreFlow`, `restartFlow` (`?string $flowKey`), e `$arguments['flow']` das actions da página de progresso | `$wire` no checklist e `mountAction` na página | `resolveFlowState` só acha jornada visível no painel corrente | `vendor/wallacemartinss/filament-onboarding/src/Pages/OnboardingProgress.php:$flowKey:106` |
| `public ?string $flowKey` (sem `#[Locked]`) | `$wire.set('flowKey', …)` | escolhe só entre jornadas já visíveis; chave inválida resolve para nulo | `vendor/wallacemartinss/filament-onboarding/src/Concerns/InteractsWithOnboarding.php:$flowKey:31` |
| `$onboardingScopeType`, `$onboardingScopeId`, `$onboardingPanel` | — | `#[Locked]` no vendor | `vendor/wallacemartinss/filament-onboarding/src/Concerns/InteractsWithOnboarding.php:$onboardingPanel:57` |
| `public bool $isOpen` | `$wire.set` | só UI | `vendor/wallacemartinss/filament-onboarding/src/Livewire/OnboardingLauncher.php:$isOpen:23` |
| `StartTourAction` (`$data['tour']`) | — | **não usada** pela feature (botões nas telas estão fora de escopo) | `vendor/wallacemartinss/filament-onboarding/src/Actions/StartTourAction.php:$chosen:64` |
| Models `OnboardingFlow`, `OnboardingStep`, `OnboardingCondition` | autoria no `/admin` | policies do kit (`App\Support\PoliciesDeVendor`) + interruptor (passo 3) + 404 do middleware | `app/Support/PoliciesDeVendor.php:OnboardingFlowPolicy:76` |
| Models `OnboardingFlowProgress`, `OnboardingStepProgress`, `OnboardingPreference` | só pelas ações acima | linha única por `(…, subject, scope)`; o escopo é o tenant corrente, `#[Locked]` | `database/migrations/2026_08_12_164953_harden_onboarding_progress_scope.php:up:38` |

Valores do cliente que viram índice, `parse`, coluna ou operador: nenhum no código da feature. No
pacote, `stepKey`/`flowKey` são comparados com chaves de registros já carregados — sem `where` montado
com o valor.
