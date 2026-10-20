---
paths:
  - 'tests/Kit/**'
---

# Kit

## Livewire::test leva cabeçalho e query só para a montagem
`Livewire::withHeaders()` e `Livewire::withQueryParams()` valem para a primeira renderização; cada `->call()`/`->set()` seguinte troca os cabeçalhos só por `X-Livewire` e vai à rota de update sem a query (`vendor/livewire/livewire/src/Features/SupportTesting/SubsequentRender.php`, `RequestBroker.php`). Teste cujo oráculo depende do `Referer` ou da query DENTRO de uma ação passa vácuo: uma implementação que lê `request()->header('referer')` na ação fica verde. Envie a ação pela rota de update do Livewire (`app('livewire')->getUpdateUri()`) com os cabeçalhos e a query, e prove que chegaram (ex.: listener de `RouteMatched` gravando o que o servidor viu). Ver `authenticatePelaRotaDeUpdate` em `tests/Kit/BloqueioDeSessaoTest.php`; origem: F-01 da conformidade Blueprint de `wikis/specs/feat/desbloqueio-volta-a-pagina/`.
