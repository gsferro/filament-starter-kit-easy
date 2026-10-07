---
paths:
  - 'docker/**'
  - 'docker-compose.yml'
  - 'Dockerfile*'
---

# Docker

## Compose do kit: o opt-in é só pelo override; base e Dockerfile.laravel ficam idênticos ao golden
O `docker-compose.yml` e o `Dockerfile.laravel` da raiz são o deploy padrão e têm golden textual (`tests/Kit/fixtures/docker-compose.v0.44.0.yml`, CT-34 de `tests/Kit/DeployMultiambienteDockerTest.php`; o canário CT-46 pega mudança no base). Recurso opcional de deploy (Traefik, Horizon, Reverb, Octane, segundo banco) entra por um override de exemplo em `docker/` (`docker/traefik/docker-compose.override.yml`), copiado para a raiz por servidor; a cópia ativa é gitignored. Fatos medidos no Compose v5.5.1 que invertem a intuição e não estão em nenhum arquivo do projeto: `ports` de override **somam** aos do base (nunca substituem — acrescentar porta no override publica em dobro); `${VAR}` só interpola em `labels` em **lista**, nunca em chave de mapa (label em mapa fica com `${COMPOSE_PROJECT_NAME}` literal); `build.args` em lista sem valor **omite** a chave ausente (`${VAR:-}` manda `""`); `ARG X=` no Dockerfile existe como `''` e o Vite copia o vazio para o bundle — `ARG X` sem default fica ausente. Enforçado em `tests/Kit/DeployMultiambienteDockerTest.php` (CT-34 golden, CT-46 canário) — não contornar. Origem: ADR-01, D1/D2/D9 e CT-34 da wiki `wikis/specs/feat/deploy-multiambiente-docker/`.
