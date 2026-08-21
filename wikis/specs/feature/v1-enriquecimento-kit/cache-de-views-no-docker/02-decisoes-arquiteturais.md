# Decisões Arquiteturais — Cache de views no Docker

## ADR-01: O cache de views vai no **boot do container**, não no build da imagem

**Status**: Aceita
**Data**: 2026-08-19

### Contexto

A pergunta original foi "por que não um `RUN php artisan view:cache` no Dockerfile?". É o
lugar óbvio: assar no build, custo zero em runtime.

Medido: `view:cache` leva **38,9 segundos** e compila **417 views**. Hoje esse custo é pago
em produção, espalhado pelas primeiras visitas de cada painel.

### O que impede o build

```yaml
volumes:
  - 'app-storage:/var/www/storage'
```

`storage/` é **volume nomeado**, montado por cima do diretório da imagem. O `view:cache`
grava em `storage/framework/views` — exatamente o caminho encoberto.

Docker copia o conteúdo da imagem para um volume **vazio** na primeira criação, então num
ambiente novo o cache até sobreviveria. Mas basta o volume já existir — segundo deploy,
`docker compose down` sem `-v`, qualquer reuso — para as views assadas serem substituídas
pelo que estiver no volume. Um cache que funciona só na primeira subida é pior que nenhum:
funciona no teste e some em produção.

### Decisão

`view:cache` no **`command:` do serviço `app`**, antes do `php-fpm`. Roda depois de o volume
estar montado, uma vez por boot do container.

### Alternativas Consideradas

1. **`RUN` no Dockerfile** — descartada pelo acima. Custaria 39s de build sem entregar nada.
2. **Tirar `storage/` do volume** — descartada: o volume existe para os uploads e os logs
   sobreviverem ao ciclo do container. Trocar persistência de dado do usuário por cache de
   view é péssimo negócio.
3. **Volume só para `storage/app` e `storage/logs`, deixando `framework/` na imagem** —
   descartada por complexidade: três montagens no lugar de uma, e a primeira permissão
   errada quebra sessão ou cache sem mensagem clara. O ganho seria 39s de boot.
4. **Entrypoint script** — descartada: o kit não tem nenhum hoje, e o serviço `nginx` já usa
   `command: sh -c "... && ..."` para o mesmo tipo de preparação. Seguir o padrão existente
   custa menos que introduzir um arquivo novo.

### Consequências

- **Positivas**: os 39s saem do caminho do primeiro usuário e viram tempo de boot, onde
  ninguém está esperando. Todo request depois é rápido.
- **Negativas**: o container leva ~40s a mais para ficar pronto. Precisa de `start_period` no
  healthcheck, senão o Docker marca `unhealthy` antes de o php-fpm subir.
- **Riscos**: se o `view:cache` falhar, o `&&` impede o `php-fpm` de subir — o container
  morre em vez de servir com views não cacheadas. É o comportamento desejado: falha de boot
  visível é melhor que degradação silenciosa.

### Referências

- `docker-compose.yml` → serviço `app`
- `Dockerfile.laravel:49-58` — o skeleton de `storage/` e o `chown`

---

## ADR-02: `config:cache` e `route:cache` ficam de fora — os dois quebram o kit

**Status**: Aceita
**Data**: 2026-08-19

### Contexto

O caminho batido de otimização Laravel é `php artisan optimize`, que roda `config:cache`,
`route:cache`, `view:cache` e `event:cache` de uma vez. A tentação é usar o comando pronto.

### Decisão

**Só `view:cache`.** `config:cache` e `route:cache` não entram, nem no build nem no boot.

### Por que `config:cache` quebra

O `docker-compose.yml` monta o `.env` em runtime:

```yaml
volumes:
  - './.env:/var/www/.env'
```

E o `Dockerfile.laravel` declara a intenção no cabeçalho: *"APP_KEY nunca é assada no build —
vem do .env em runtime"*.

`config:cache` faz o Laravel **parar de ler o `.env`**: a partir dali, `env()` fora dos
arquivos de config devolve `null`, e todo valor vem do cache gerado quando o comando rodou.
Cachear no build congelaria o env do build; cachear no boot congelaria o env daquele boot —
e o bind-mount, que é o mecanismo de configuração do kit, deixaria de ter efeito em qualquer
mudança posterior sem recriar o container.

### Por que `route:cache` quebra

As rotas dos painéis dependem de config avaliada no registro:

- com `kit.tenancy.enabled` ligado, o painel `app` deixa de ser `/app` e passa a `/app/{tenant}`
- `ProjetoResource::canAccess()` depende de `kit.demo`
- o `lockscreen` registra rota condicionada a `lockscreen.enabled`

Assar rotas congela o estado de tenancy na imagem ou no boot. Um `kit:tenancy` posterior
mudaria o config e não as rotas — e o sintoma seria 404 numa tela que "deveria existir",
sem erro que aponte a causa.

### Por que `view:cache` é seguro

Compilação de Blade é **independente de env**: o resultado é PHP gerado a partir do template,
não do ambiente. As poucas coisas que variam em runtime — `config()`, `auth()`, o painel
corrente — são chamadas dentro do PHP compilado, avaliadas a cada request.

### Alternativas Consideradas

1. **`php artisan optimize`** — descartada: é o atalho que traz os dois perigosos junto.
2. **`config:cache` com todas as chaves migradas para `config/`** — descartada: exigiria
   auditar 30 arquivos de config e garantir que nenhum `env()` sobrevive fora deles, para
   ganhar milissegundos. Desproporcional.

### Consequências

- **Positivas**: o `.env` em runtime continua sendo o mecanismo de configuração; o
  `kit:tenancy` continua funcionando num projeto já containerizado.
- **Negativas**: perde-se o ganho de `config:cache` e `route:cache`. É pequeno perto do de
  view, e o preço de errar aqui é alto.

### Referências

- `Dockerfile.laravel:1-6` — a declaração sobre a APP_KEY
- `config/kit.php` → `tenancy.enabled`
- Refine: ADR-01

---

## ADR-03: `filament:optimize` no build; `view:cache` no boot

**Status**: Aceita
**Data**: 2026-08-19

### Contexto

`filament:optimize` = `filament:cache-components` + `icons:cache`. Medido: **14 ms**.

Ele grava em `bootstrap/cache`, que **não** é volume — logo, ao contrário do `view:cache`,
sobrevive ao build.

### Decisão

`filament:optimize` no **Dockerfile**, no estágio `app`. `view:cache` no **boot**.

Cada um no lugar onde de fato funciona, e não os dois juntos por conveniência.

### Alternativas Consideradas

1. **Os dois no boot** — descartada: seria acrescentar 14ms ao tempo de subida sem motivo,
   quando o build pode absorvê-los de graça.
2. **Os dois no build** — descartada: é o erro que ADR-01 documenta.

### Consequências

- **Positivas**: o índice de componentes e os ícones já vêm prontos na imagem.
- **Negativas**: se o `filament:optimize` falhar no build, o build falha. Aceito — ele não
  toca banco nem rede, e falhar cedo é melhor que falhar em produção.
- **Riscos**: `filament:cache-components` congela a lista de componentes descobertos. Um
  Resource novo exige rebuild da imagem — o que já era verdade, porque o código é assado.
