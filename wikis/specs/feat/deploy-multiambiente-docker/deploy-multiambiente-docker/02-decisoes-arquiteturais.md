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

## ADR-02: `TRUSTED_PROXIES` é lida no bootstrap, falha fechado, e não entra no `config/kit.php`

**Status**: Aceita
**Data**: 2026-10-05
**Portões**: difícil de reverter ✅ (chave pública no `.env.example` e na documentação; renomear depois é quebrar servidor configurado) · surpreendente ✅ (o kit guarda toda configuração própria em `config/kit.php`, e esta fica fora) · trade-off ✅ (`*` cômodo contra CIDR seguro; ausente = ninguém contra ausente = todos)

### Contexto

O levantamento do solicitante não menciona proxies confiáveis, e sem eles o cenário documentado não
funciona de ponta a ponta: o Traefik termina o TLS e entrega HTTP na porta 80 do container; o
Laravel, sem confiar em `X-Forwarded-Proto`, gera `http://` em toda URL absoluta, não marca o cookie
de sessão como `secure`, e o middleware do kit que remove `/public` da raiz lê o esquema errado. O
framework resolve isso com `trustProxies(at: …)` em `bootstrap/app.php`, e nasce sem ninguém confiável.

### Decisão

Uma chave de ambiente, `TRUSTED_PROXIES`, lida em `bootstrap/app.php` por `ProxiesConfiaveis::doEnv()`:
lista separada por vírgula de IPs/CIDRs, ou `*`. **Vazia ou ausente, nada é chamado** — o
comportamento de hoje. Os cabeçalhos confiados são os default do `TrustProxies` (todos os
`X-Forwarded-*`), que é o que o Traefik envia.

### Alternativas Consideradas

1. **`config/kit.php` → `kit.proxies_confiaveis`** — descartada: `trustProxies()` roda no
   `withMiddleware()` do bootstrap, antes de o `config/` ser carregado; ler `config()` ali é ler o
   valor errado ou nenhum. A doc do Laravel 13 põe a chamada no bootstrap, e `env()` é o único
   leitor disponível nesse ponto.
2. **Confiar em `*` sempre que `APP_URL` for `https://`** — descartada: inferir confiança de outra
   chave é o padrão "uma pergunta, duas donas" que `.ai/rules/config.md` proíbe, e abriria
   `X-Forwarded-For` falsificado para qualquer um que alcance a porta publicada no host.
3. **Default `*` quando a chave está ausente** — descartada: inverte a direção do erro. Quem roda
   hoje sem proxy passaria a aceitar cabeçalhos forjados sem ter pedido nada.
4. **Validar o CIDR no kit** — descartada (ponytail): o Symfony valida ao resolver o IP e lança
   exceção clara; um parser próprio só duplicaria.

### Consequências

- **Positivas**: default intocado; uma chave, um significado; `*` resolve o caso comum (porta 80 do
  container alcançável só pela rede do Traefik) e a lista resolve o caso com porta publicada no host.
- **Negativas**: é a única configuração do kit fora de `config/kit.php` e das Configurações da
  aplicação — e não pode ser diferente. Está escrito no comentário do `.env.example` e no bootstrap.
- **Riscos**: `*` com porta publicada em `0.0.0.0` aceita `X-Forwarded-For` forjado. A documentação
  manda publicar só em `127.0.0.1` ou usar o CIDR da rede do Traefik nesse caso.

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
