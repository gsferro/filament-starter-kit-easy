---
paths:
  - 'bootstrap/app.php'
---

# Bootstrap

## Chave do .env nunca é lida dentro do closure de withMiddleware()
O closure de `withMiddleware()` roda no `afterResolving` do `HttpKernel`, **antes** do `LoadEnvironmentVariables`: `env('X')` ali só enxerga o ambiente do processo, nunca o arquivo `.env` — mesmo sem `config:cache`. A doc oficial mostra `trustProxies(at: '*')` literal no closure e induz a trocar o literal por `env()`; `Middleware::trustProxies(at: null)` é no-op silencioso, então o erro não aparece. Chave de `.env` que alimenta middleware (`trustProxies`, `trustHosts`…) vai para `config/` (lida por `config()`) e é aplicada no `boot()` de um provider — o precedente é `KitServiceProvider::confiarNosProxiesDoEnv()` com `kit.proxies_confiaveis`. Não há `arch()` que pegue `env()` dentro de closure: a guarda é esta rule e os CTs de `tests/Kit/ProxiesConfiaveisTest.php`. Origem: ADR-02 e RD-01 da wiki `wikis/specs/feat/deploy-multiambiente-docker/` (a sessão errou exatamente assim no primeiro desenho).
