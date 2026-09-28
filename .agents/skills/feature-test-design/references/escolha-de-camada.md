> Referência da feature-test-design 1.16.0. Lida em: passo 2 (o valor de `Costura` de cada camada,
> ao propor as costuras), passo 7 (alocar a camada de cada cenário; o porquê das regras de teto) e
> na escrita do cenário de componente (qual API do Filament/Livewire usar). Fonte única de: a
> tabela cenário → camada → API em Laravel/Filament, a correspondência camada → `Costura`, os
> helpers `@deprecated` do Filament e o que o teste de componente não distingue.

# Escolha de camada em Laravel/Filament — tabela e API

Os gates desta escolha (regra do par, gate de tela de escrita, gate de camada da regra, assertion
proibida como oráculo único, versões) estão no `SKILL.md` §Escolha de Camada em Laravel/Filament.

Em Laravel + Filament, **a maior parte do que parece exigir browser é teste de componente
Livewire** — milissegundos, sem Node, sem Playwright. Empurrar UI para o browser é a decisão
que mais destrói o orçamento de teste de uma feature.

| O cenário afirma sobre… | Camada | API |
|---|---|---|
| cálculo, regra pura, value object | `Unit` | `expect()`, `toThrow()`, datasets |
| persistência, autorização, efeito colateral | `Feature` | `assertDatabaseHas`, `assertForbidden`, `Queue::fake`, `Mail::fake` |
| validação de formulário Filament | Livewire | `fillForm([...])` → `assertHasFormErrors([...])` |
| gravação pelo formulário | Livewire | `->call('create')` / `->call('save')` + `assertDatabaseHas` |
| listagem, busca, ordenação, filtro | Livewire | `assertCanSeeTableRecords`, `searchTable`, `sortTable`, `filterTable` |
| ação de tabela ou de página | Livewire | `callAction(TestAction::make(X::class)->table(), [...])` |
| notificação exibida | Livewire | `assertNotified()` |
| visibilidade condicional de campo/coluna/ação | Livewire | `assertFormFieldHidden`, `assertTableColumnHidden`, `assertActionHidden` |
| autorização na tela | Livewire | `livewire(...)->assertForbidden()` |
| wizard multi-etapa | Livewire | `goToNextWizardStep()`, `assertWizardCurrentStep()` |
| comportamento dependente do tempo | `Feature` | `travelTo()`, `freezeTime()` |
| **JavaScript executado** (modal que não abre, Alpine, atalho) | **Browser** | — |
| **console limpo / erro de JS** | **Browser** | `assertNoSmoke()`, `assertNoJavaScriptErrors()` |
| **acessibilidade** | **Browser** | `assertNoAccessibilityIssues()` |
| **cor, tema, layout** | **Browser** | `inDarkMode()`, `assertScreenshotMatches()` |

## Camada → valor de `Costura` (costuras do passo 2; conferido no passo 7, item 5)

A coluna `Costura` de `## Costuras de Teste` e do `## Índice de Cenários` usa o enum da skill, não o
nome da camada. Nas costuras, a linha escolhida é a do que a **regra** afirma; no passo 7, a do que o
`Então` afirma — divergência muda o cenário de grupo. A correspondência:

| Camada (tabela acima) | `Costura` | Nota |
|---|---|---|
| `Unit` | `unit de regra` | cálculo, regra pura, value object — sem container nem banco |
| `Feature` | `Pest feature HTTP` | teste em `tests/Feature` com a aplicação de pé: pela rota, ou chamando model, action ou service direto — é onde vive o cenário "por fora do componente de UI" do gate de camada da regra |
| Livewire | `componente Livewire/Filament` | `livewire(...)`, `fillForm`, `callAction`, `assertCanSeeTableRecords` |
| **Browser** | `browser` | só o que o navegador prova; uma linha com esta costura é o que faz o `05` existir |

**Existente > nova**: antes de propor costura nova, procurar a que o projeto já tem — um arquivo de
teste irmão da mesma área (`ls tests/Feature/{Área}`, `ls tests/Unit`), o helper do `tests/Pest.php`
e a ligação do `TestCase` por pasta (passo 7, item 1). A linha "nova" diz por que nenhuma serviu.

## Helpers `@deprecated` no Filament 4/5

Continuam existindo e não avisam nada (regra e comando de conferência no `SKILL.md`):

| Escrever | Em vez de (`@deprecated`) |
|---|---|
| `assertSchemaStateSet` | `assertFormSet` |
| `callAction(TestAction::make(X::class)->table(), [...])` | `callTableAction` |
| `assertActionExists` | `assertTableActionExists` |

## Gate de camada da regra — o que o teste de componente não distingue

O gate (`SKILL.md` §Escolha de Camada) exige um cenário por fora do componente de UI em toda regra
de autorização e de validação de domínio, porque o teste de componente não consegue, por
construção, distinguir estas duas implementações:

| Implementação | Teste de componente | Cenário por fora da UI |
|---|---|---|
| a regra vive no domínio, e a tela a chama | verde | verde |
| a regra vive **só no formulário** (policy no `Resource`, validação no `->rules()`) | verde | **vermelho** |

## Passo 7 — o porquê das regras de teto

**Um `Esquema do Cenário` conta como 1 cenário, não como N linhas.** Sem essa regra, o teto e a
exigência de "100% das células inválidas da tabela de estados" ficam aritmeticamente
incompatíveis — 21 células contra teto de 5. A tabela de `Exemplos` é a forma canônica de
expressar partição, borda e célula de matriz **dentro** de um cenário; contar cada linha como um
cenário puniria exatamente a técnica que a skill quer.
