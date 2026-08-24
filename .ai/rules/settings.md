---
paths:
  - 'app/Settings/**'
---

# Settings

## Chave lida no boot não pode virar Settings; chave lida por request pode
Este é o critério que decide se uma configuração pode ser editável na tela, e ele não é sobre a chave — é sobre QUANDO ela é lida.

`ConfiguracoesDoKit::aplicarNaConfig()` sobrepõe a config do processo com o banco no `boot()` do `KitServiceProvider`. Então:

- **Lida por request** (num `abort_unless()`, num `->visible()`, numa closure de render hook, dentro de um método chamado na requisição): pode ir para o Settings. Basta a linha no `mapaDeConfiguracao()` — o consumidor continua lendo `config()` e passa a receber o valor do banco. Nada mais muda.
- **Lida no boot** (para montar painel, registrar rota, decidir middleware): NÃO pode. O painel é montado antes de `aplicarNaConfig()` rodar, e o valor do banco chega tarde.

O caso que provou isso: `registro_verificar_email` ganhou um toggle na tela e ele **gravava sem fazer efeito**. Pior que a ordem de boot: o middleware de e-mail verificado é fixado no array da rota no momento do registro (`vendor/filament/filament/src/Pages/Concerns/HasRoutes.php:91`), não por request — então nem Closure em `isRequired` resolveria. O toggle foi removido e a chave ficou no `.env`.

**Toggle que grava e não faz efeito até o próximo deploy é pior que campo ausente**: a pessoa acha que configurou algo. Se a chave é de boot, deixe no `.env` e diga na tela onde ela mora.

Para tornar uma chave de boot editável de verdade, o caminho é um middleware próprio que decida por request — aí a decisão sai do array da rota. Não foi feito; é dívida conhecida.

Ao acrescentar propriedade, são TRÊS lugares, sempre: a propriedade na classe, a linha em `mapaDeConfiguracao()` e o `add()`/`addEncrypted()` na migration de settings. Esquecer a linha do mapa é o defeito silencioso: o campo aparece, grava, e não governa nada. Há caso de teste guardando o mapa por isso.
