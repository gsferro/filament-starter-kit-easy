> Referência da feature-test-design 1.17.3. Lida em: passos 5 e 7 e na escrita do `04` (antes de
> nomear fake, assertion ou helper num cenário). Fonte única de: as armadilhas de API que invalidam
> CT.

# Armadilhas de API que invalidam CT

Cada linha já produziu teste vermelho sem defeito no código — ou verde sem provar nada.

| Armadilha | Consequência |
|---|---|
| `Mail::assertSent` em mailable `ShouldQueue` | nunca passa — é `assertQueued` |
| `Event::fake()` **antes** das factories | eventos de model (uuid em `creating`) não rodam; fixture nasce quebrada |
| `Http::fake()` sem stub restrito | devolve 200 vazio para qualquer URL; `preventStrayRequests()` não bloqueia esse catch-all. Use `Http::fake(['{URL esperada}' => Http::response(...)])` + `Http::preventStrayRequests()` e afirme o payload enviado |
| `withoutExceptionHandling()` + `assertForbidden()` | o 403 vira exceção lançada; a assertion nunca roda |
| assumir que `RefreshDatabase` impede todo `->afterCommit()` | no Laravel 12.0.0, o manager de testes ignora a transação invólucro e executa callbacks; confira o manager da versão instalada e observe o despacho antes de declarar a lacuna |
| assumir que `travel()` sempre vaza para o teste seguinte | o `tearDown` padrão do Laravel 12.0.0 limpa Carbon e CarbonImmutable. Use closure/`travelBack()` para restaurar o relógio dentro do mesmo teste; em outro arnês, confira o cleanup |
| `Repeater::fake()` / `Builder::fake()` ausentes no Filament | UUID aleatório quebra `assertSchemaStateSet` |
| helper de teste declarado fora do `tests/Pest.php` e usado por 2 arquivos | `Call to undefined function` em `--parallel`, `--tia` ou arquivo isolado |
| `assertDatabaseHas` só com a chave primária | passa com todos os outros campos errados |
| Filament `Select::options(Enum::class)` e comparação `Get('tipo') === Enum::Caso->value` | no Filament 5.7.6, Get aplica EnumStateCast e retorna o caso enum; comparar com string falha. Use `Get::enum()`/caso enum conforme vendor e prove unidade visual e fronteira por tipo |
| `assertHasFormErrors()` genérico em recusa de um campo | outro campo inválido pode satisfazer o teste. Afirme o erro no campo visado e a validade das demais fixtures; confirme que o operador realmente libera a entrada defeituosa antes de classificar a sobrevivência |
| `Log::spy()` citado como API oficial | é o mecanismo genérico de Facade Spy — funciona, mas não é doc |

Fontes primárias, fixadas no Laravel **12.0.0**: [`Factory::fake()`](https://github.com/laravel/framework/blob/v12.0.0/src/Illuminate/Http/Client/Factory.php),
[`DatabaseTransactionsManager`](https://github.com/laravel/framework/blob/v12.0.0/src/Illuminate/Foundation/Testing/DatabaseTransactionsManager.php)
e [`InteractsWithTestCaseLifecycle::tearDownTheTestEnvironment()`](https://github.com/laravel/framework/blob/v12.0.0/src/Illuminate/Foundation/Testing/Concerns/InteractsWithTestCaseLifecycle.php).
Para outra versão, confirmar no vendor do projeto.

O caso Filament foi encontrado em Chromium na [rodada real](../../../../experimentos/2026-10-09-projeto-real/README.md).
Fontes locais primárias do Filament **5.7.6**: `HasOptions::options`,
`Field::getDefaultStateCasts`, `Get::__invoke`, `Get::enum`, `HasState::getState` e
[`EnumStateCast::get`](https://github.com/filamentphp/filament/blob/v5.7.6/packages/schemas/src/Components/StateCasts/EnumStateCast.php).
A cadeia de chamadas e o patch estão na correção da referência v3 arquivada nessa rodada.
O primeiro operador de mínimo negativo repetia a comparação escalar e não manifestava o defeito;
seu resultado verde foi preservado, sem ser contado como sobrevivência válida de uma entrada liberada.
