# Plano de Ação — Cache de views no boot do container

> Requisito: `00-requisito.md`

## Natureza da Wiki

- **Tipo**: nova
- **Toca infra compartilhada?**: **sim** — `Dockerfile.laravel` e `docker-compose.yml`, que são
  a superfície de deploy de todo projeto nascido do kit. **Não toca código PHP.**

## Cobertura do Requisito

| RQ | Cláusula | Passo(s) | Observação |
|----|----------|----------|------------|
| RQ-01 | Verificar o `kit:install` | 1 | **Não faz** — e não deve, ver o passo 6 |
| RQ-02 | Verificar o `Dockerfile` | 1 | **Não faz** — só `composer --optimize-autoloader` |
| RQ-03 | Abrir wiki | — | esta |
| RQ-04 | Implementar | 2, 3, 4, 5 | boot do container, não build — ADR-01 |

## Objetivo

Tirar os **38,9 segundos** de compilação de view do caminho do primeiro usuário e movê-los para
o boot do container, onde ninguém está esperando.

## O que foi medido

| | |
|---|---|
| `php artisan view:cache` | **38 891 ms**, **417 views** |
| `php artisan filament:optimize` | **14 ms** (componentes 5,77 + ícones 8,44) |
| `kit:install` faz cache? | **não** — nenhum comando de otimização |
| `Dockerfile.laravel` faz? | **não** — só `composer install --optimize-autoloader` |
| Algum serviço do compose faz? | **não** |

## A armadilha que decide o lugar da correção

```yaml
volumes:
  - 'app-storage:/var/www/storage'
```

`storage/` é volume nomeado, montado **por cima** do diretório da imagem. O `view:cache` grava
em `storage/framework/views` — exatamente o caminho encoberto.

Docker copia o conteúdo da imagem para um volume **vazio** na primeira criação, então num
ambiente novo o cache assado até sobreviveria. Basta o volume já existir para as views serem
substituídas pelo que estiver nele.

**Cache que funciona no primeiro teste e some em produção é pior que nenhum.** Daí a correção ir
para o boot. Ver ADR-01.

## Superfície de UI

**Sem superfície de UI.** Nenhum CT-B.

## Autorização · Rotas · Variáveis de Ambiente · Eventos · Jobs

Nenhum.

## Impacto em Features Existentes

- **Boot do container `app`**: +~39s até servir o primeiro request.
- **Healthcheck**: `pgrep php-fpm` falha durante a compilação. Exige `start_period`.
- **`composer dev`, suíte de testes, instalação local**: **nada muda**.
- **`queue`, `scheduler`, `reverb`, `pulse`**: compartilham o volume, então aproveitam o cache
  que o `app` gerou. Se subirem antes, compilam sob demanda — não é erro, é só perda do ganho
  naquele boot.

## Rollback

Remover o `command:` do serviço `app` e o `RUN` do Dockerfile. Nenhum estado persistente fica.

## Dependências

Nenhuma.

## Riscos

- **Container demora ~40s a mais para ficar pronto.** *Mitigação*: `start_period: 90s`.
- **`view:cache` falhar e derrubar o boot.** É o comportamento desejado (`&&`, não `;`).
- **Alguém trocar por `php artisan optimize`.** *Mitigação*: teste de contrato reprova.

## Channel de Log da Feature

**Nenhum log, nenhum channel.** A entrega é configuração de imagem e de orquestração; não há
código de aplicação executando.

## Estrutura de Implementação

### 1. Verificação (concluída antes deste plano)

- Busca por comandos de cache em `KitInstall.php`, `Dockerfile.laravel`, `docker-compose.yml`
- Medição do `view:cache` e do `filament:optimize`
- Descoberta do volume `app-storage` encobrindo `storage/`

### 2. `Dockerfile.laravel` — `filament:optimize` no estágio `app`

- `RUN php artisan filament:optimize` antes do `EXPOSE`
- Comentário dizendo por que **este** vai no build (grava em `bootstrap/cache`, que não é
  volume) e por que o `view:cache` **não** vai

### 3. `docker-compose.yml` — `view:cache` no `command:` do serviço `app`

- Segue o padrão que o serviço `nginx` já usa (`command: sh -c "... && ..."`)
- `&&` e não `;`
- Comentário com o motivo do lugar e com a proibição de `config:cache` / `route:cache`

### 4. `healthcheck.start_period: 90s`

- Folga para os ~39s em que o php-fpm ainda não existe

### 5. Teste de contrato

- `tests/Kit/CacheDeViewsNoDockerTest.php` — 9 casos

### 6. `kit:install` — nada a fazer, por decisão

Instalação local: view cacheada obriga `view:clear` a cada edição de Blade, contra o loop que o
kit protege com `PHP_OPCACHE_VALIDATE_TIMESTAMPS=1`. O CT-07 fixa a ausência.

## Filosofia de Implementação

> **Ponytail em `full`.** O degrau 1 recusou a solução óbvia: `RUN` no Dockerfile é a resposta
> que todo mundo daria, e a medição do volume mostrou que ela não entrega nada. A entrega são
> três linhas de config, e o valor está em saber **onde** cada uma vai.

## Testes

> Ver `04-casos-de-teste.md`. Sem CT-B.

## Verificação Final

- [x] `docker compose config --quiet` — compose válido
- [x] `vendor/bin/pint --dirty`
- [x] `vendor/bin/phpstan analyse` — 0 erros no level 7
- [x] `vendor/bin/filacheck` — 17 regras
- [x] `php artisan test --testsuite=Kit,Tenancy --parallel` — **446 casos**

## Commits

- `:zap: perf(docker): compila as views no boot do container, não no build`
