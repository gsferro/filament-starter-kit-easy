# Decisões Arquiteturais — Deploy com Docker: vários ambientes no mesmo servidor, atrás do Traefik

## ADR-01: O encaixe no Traefik é um override de exemplo em `docker/`, copiado para a raiz por servidor

**Status**: Aceita
**Data**: 2026-10-05
**Portões**: difícil de reverter ✅ (o path do exemplo vira o que a documentação, o `kit:update` e os projetos instalados passam a conhecer; mover depois é quebrar o `cp` ensinado) · surpreendente ✅ (o primeiro instinto é um `docker-compose.traefik.yml` na raiz com `-f`, ou um bloco no YAML base atrás de variável) · trade-off ✅ (auto-load silencioso contra flag explícita; raiz contra `docker/`)

### Contexto

O requisito proíbe mudar o comportamento de hoje e pede que a nova forma entre só por adesão. Há
três lugares onde um encaixe de Traefik poderia viver, e os três têm consequências diferentes para
duas populações: quem instala agora (`composer create-project`, governado pelo `.gitattributes`) e
quem já instalou (`php artisan kit:update`, governado pela lista de caminhos do comando).

### Decisão

O kit versiona **um arquivo de exemplo** em `docker/traefik/docker-compose.override.yml`. Quem quer
o Traefik copia o arquivo para a raiz do checkout com o nome `docker-compose.override.yml` e
preenche as chaves no `.env`. O Compose carrega o override da raiz **sozinho**, sem `-f` — então o
comando de sempre (`docker compose --profile app up -d --build`) e o `deploy_docker_local.sh`
passam a aplicar o encaixe sem mudar uma letra. A cópia ativa é ignorada pelo git do kit, como o
`.env`: é configuração do servidor, não do projeto.

### Alternativas Consideradas

1. **Bloco no `docker-compose.yml` base, atrás de variável** (`networks` e `labels` condicionais) —
   descartada: o Compose não tem condicional; a rede externa teria de existir em toda máquina, e
   `docker compose up -d` de quem nunca ouviu falar de Traefik falharia com *network not found*. Viola
   a restrição de não mudar o default, e os guardas que contam serviços e exigem o piso do base.
2. **Arquivo dedicado com `-f`** (`docker compose -f docker-compose.yml -f docker/traefik/… up`) —
   descartada: explícito, mas obriga a lembrar a flag em **todo** comando (`ps`, `logs`, `exec`,
   `port`) e torna o `deploy_docker_local.sh` inútil sem edição. O auto-load do override elimina a
   classe inteira de "esqueci o `-f` e subiu sem Traefik".
3. **Exemplo na raiz** (`docker-compose.override.example.yml`) — descartada: a raiz não é entregue
   pelo `kit:update` (só os arquivos nomeados um a um); dentro de `docker/` o exemplo viaja pelas
   duas rotas sem tocar a lista do comando, e fica ao lado de `nginx/`, `php/` e `pgsql/`, que é
   onde o kit já guarda o que é "do Docker".
4. **Versionar o override já na raiz, pronto** — descartada: ligaria o Traefik para todo mundo no
   próximo `up`, exatamente o que o requisito proíbe.

### Consequências

- **Positivas**: default intocado por construção (sem o arquivo na raiz nada muda — medido com
  `docker compose config`); adesão em dois comandos; o script de deploy funciona sem mudança;
  chega a quem já instalou.
- **Negativas**: o override é **invisível** na linha de comando — quem olha só o `docker-compose.yml`
  não vê a rede nem os labels. Mitigação: `docker compose config` está na página como passo de
  conferência, e o cabeçalho do exemplo diz que o arquivo da raiz é carregado sozinho.
- **Riscos**: o Compose **concatena** `ports` — o override não consegue retirar a porta 8000 do
  base. A promessa de "porta opcional" se cumpre pela chave (`FORWARD_APP_PORT=127.0.0.1:porta`),
  não pelo arquivo; está documentado, e o CT prova que o base continua publicando quando a chave falta.

### Referências

- `KitUpdate::CAMINHOS_DO_KIT` (a lista de entrega) e `.gitattributes` (o que viaja no pacote)
- ADR-04 da wiki `mysql-no-docker` (o `COMPOSE_PROJECT_NAME` pelo `.env`, que esta ADR reaproveita)
- Documentação do Docker Compose, *Merge Compose files* (regras de mesclagem) — e a medição na sonda
  do step 3, registrada no `03`

---

## ADR-02: `TRUSTED_PROXIES` é lida pelo `config/kit.php`, aplicada no boot do kit, e falha fechado

**Status**: Aceita
**Data**: 2026-10-05
**Portões**: difícil de reverter ✅ (chave pública no `.env.example` e na documentação; renomear depois é quebrar servidor configurado) · surpreendente ✅ (a doc do Laravel põe `trustProxies()` no `bootstrap/app.php`, e o kit **não** o faz ali) · trade-off ✅ (`*` cômodo contra IP do proxy seguro; ausente = ninguém contra ausente = todos; descartar item inválido em silêncio contra derrubar a aplicação)

> *(revisada em 2026-10-05, step 9 — RD-01/CR-01: a primeira versão lia a chave no `bootstrap/app.php` com `env()`, por entender que o `config/` ainda não existia ali; o revisor mediu que o closure de `withMiddleware()` roda antes do `LoadEnvironmentVariables` e portanto nunca lê o arquivo `.env`, só o ambiente do processo. A decisão inverteu: a chave vive em `config/kit.php` como as outras, e a aplicação é no `boot()` de `KitServiceProvider`.)*

### Contexto

O levantamento do solicitante não menciona proxies confiáveis, e sem eles o cenário documentado não
funciona de ponta a ponta: o Traefik termina o TLS e entrega HTTP na porta 80 do container; o
Laravel, sem confiar em `X-Forwarded-Proto`, gera `http://` em toda URL absoluta, não marca o cookie
de sessão como `secure`, e o middleware do kit que remove `/public` da raiz lê o esquema errado. O
framework resolve isso com `trustProxies(at: …)` em `bootstrap/app.php`, e nasce sem ninguém confiável.

### Decisão

Uma chave de ambiente, `TRUSTED_PROXIES`, exposta crua em `config/kit.php` (`kit.proxies_confiaveis`)
e interpretada por `ProxiesConfiaveis::doEnv()` no `boot()` de `KitServiceProvider`, que chama
`TrustProxies::at()`: lista separada por vírgula de IPs/CIDRs, ou `*`. **Vazia, ausente ou sem item
válido, nada é chamado** — o comportamento de hoje. Coringa dentro de lista e item que não é IP nem
CIDR são descartados e registrados no log `configuracoes`: a aplicação não cai e a confiança não
abre por erro de digitação. Os cabeçalhos confiados são os default do `TrustProxies` (todos os
`X-Forwarded-*`), que é o que o Traefik envia.

### Alternativas Consideradas

1. **`$middleware->trustProxies(at: …env('TRUSTED_PROXIES'))` no `bootstrap/app.php`**, como a doc
   do Laravel 13 mostra — descartada depois de medida: o closure roda no `afterResolving` do
   Kernel, antes do `LoadEnvironmentVariables`, e `env()` só vê o ambiente do processo. Com a chave
   só no arquivo `.env` (todo deploy fora do Docker: `artisan serve`, php-fpm com `clear_env`
   padrão), a confiança nunca liga e nada avisa. A doc assume a variável no ambiente, o que o
   `env_file` do Compose faz e o `.env` não.
2. **Confiar em `*` sempre que `APP_URL` for `https://`** — descartada: inferir confiança de outra
   chave é o padrão "uma pergunta, duas donas" que `.ai/rules/config.md` proíbe, e abriria
   `X-Forwarded-For` falsificado para qualquer um que alcance a porta publicada no host.
3. **Default `*` quando a chave está ausente** — descartada: inverte a direção do erro. Quem roda
   hoje sem proxy passaria a aceitar cabeçalhos forjados sem ter pedido nada.
4. **Passar o item cru ao Symfony e deixar que ele valide** — descartada depois de medida (RD-03):
   o Symfony **não** valida — `172.18.0.0/16x` é `TypeError` em `substr_compare()` em todo request
   que leia `isSecure()`/`ip()`/`url()`, e `17x.18.0.1` é silêncio. Um `filter_var(FILTER_VALIDATE_IP)`
   mais o tamanho do prefixo custa dez linhas e separa os dois modos de falha do mesmo jeito: fora,
   com aviso.

### Consequências

- **Positivas**: default intocado; uma chave, um significado; `*` resolve o caso comum (porta 80 do
  container alcançável só pela rede do Traefik) e a lista resolve o caso com porta publicada no host.
- **Negativas**: a chave fica em `config/kit.php` como as outras, mas não nas Configurações da
  aplicação (é infraestrutura do servidor, não preferência do produto). Com `config:cache`, vale o
  valor cacheado — como toda chave.
- **Riscos**: `*` confia em qualquer container da rede compartilhada do Traefik, não só nele; a
  documentação recomenda o IP fixo do Traefik e declara o `*` como risco aceito (CR-03, P-21).

### Referências

- `Illuminate\Http\Middleware\TrustProxies` e `Illuminate\Foundation\Configuration\Middleware::trustProxies()`
- `App\Http\Middleware\RaizDeUrlSemPublic` (o consumidor do kit que depende do esquema real)
- Doc do Laravel 13, *HTTP Requests → Configuring Trusted Proxies* (`search-docs`)
- `.ai/rules/config.md` — interruptor que abre superfície falha fechado; uma pergunta, uma dona

---

## Superfície Livewire

Não exigida: a feature não cria página, widget nem componente Livewire. Nenhum `public function`
nem `public $` novo em `app/Filament` ou `app/Livewire`; o único código PHP novo é uma classe
estática de parsing chamada no bootstrap.
