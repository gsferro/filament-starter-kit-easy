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

O caso que provou isso: `registro_verificar_email` ganhou um toggle na tela e ele **gravava sem fazer efeito**. Pior que a ordem de boot: o middleware de e-mail verificado é fixado no array da rota no momento do registro (`vendor/filament/filament/src/Pages/Concerns/HasRoutes.php:91`), não por request — então nem Closure em `isRequired` resolveria. O toggle foi removido na hora e a chave voltou para o `.env` — e voltou para a tela na v0.19.8, pelo caminho da emenda abaixo. O diagnostico continuou certo; o que mudou foi o que esta fixado na rota.

**Toggle que grava e não faz efeito até o próximo deploy é pior que campo ausente**: a pessoa acha que configurou algo. Se a chave é de boot, deixe no `.env` e diga na tela onde ela mora.

**Chave de boot PODE virar Settings — com um decisor por request na frente.** Foi feito na v0.19.8: `App\Http\Middleware\ExigirEmailVerificado` **estende** `EnsureEmailIsVerified` do Laravel e, no `handle()`, consulta a config a cada request antes de delegar ao pai. O middleware passa a ser aplicado sempre, e quem decide é ele — não o array da rota.

Isso é o padrão para a próxima chave nessa situação, e ele tem três partes:

1. **estenda o middleware existente** em vez de reimplementar; o do Laravel já trata quem não implementa o contrato, quem já está em conformidade, e para onde redirecionar;
2. **aplique sempre** e decida dentro — aplicar condicionalmente devolve o problema ao boot;
3. **prove por `route:list`**, não por argumento. A v0.19.8 mediu: 12 rotas com o middleware, todas sob `/app`, zero em `/admin` e `/infra`. Contrato global (`MustVerifyEmail` no `User`) protege os outros painéis só porque eles não exigem — e isso precisa ser medido, não presumido.

O que continua valendo: se a chave é lida no boot e **não** há decisor por request na frente, ela não vai para a tela.

Ao acrescentar propriedade, são TRÊS lugares, sempre: a propriedade na classe, a linha em `mapaDeConfiguracao()` e o `add()`/`addEncrypted()` na migration de settings. Esquecer a linha do mapa é o defeito silencioso: o campo aparece, grava, e não governa nada. Há caso de teste guardando o mapa por isso.
