> Referência da feature-test-design 1.16.0. Lida em: passos 5 e 7 e na escrita do `04` (antes de
> nomear fake, assertion ou helper num cenário). Fonte única de: as armadilhas de API que invalidam
> CT.

# Armadilhas de API que invalidam CT

Cada linha já produziu teste vermelho sem defeito no código — ou verde sem provar nada.

| Armadilha | Consequência |
|---|---|
| `Mail::assertSent` em mailable `ShouldQueue` | nunca passa — é `assertQueued` |
| `Event::fake()` **antes** das factories | eventos de model (uuid em `creating`) não rodam; fixture nasce quebrada |
| `Http::fake()` sem stub | devolve 200 vazio e o teste passa sem provar nada — use `Http::preventStrayRequests()` |
| `withoutExceptionHandling()` + `assertForbidden()` | o 403 vira exceção lançada; a assertion nunca roda |
| `RefreshDatabase` + job `->afterCommit()` | tudo roda em transação, o job não despacha |
| `travel()` sem `travelBack()` nem closure | vaza para os testes seguintes; flake em `--parallel` |
| `Repeater::fake()` / `Builder::fake()` ausentes no Filament | UUID aleatório quebra `assertSchemaStateSet` |
| helper de teste declarado fora do `tests/Pest.php` e usado por 2 arquivos | `Call to undefined function` em `--parallel`, `--tia` ou arquivo isolado |
| `assertDatabaseHas` só com a chave primária | passa com todos os outros campos errados |
| `Log::spy()` citado como API oficial | é o mecanismo genérico de Facade Spy — funciona, mas não é doc |
