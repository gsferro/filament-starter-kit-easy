# Como usar este pacote

Este zip NÃO é o projeto final — é o **gerador** do seu start-kit (não dá para rodar
`composer` aqui no ambiente do chat, então o projeto completo é materializado na sua máquina).

## Passo a passo (uma única vez)

1. Extraia esta pasta em qualquer lugar (ex.: `D:\PROJECTS\start-kit-builder`).
2. Em um terminal com PHP 8.3+ e Composer 2 (Git Bash ou WSL no Windows):

   ```bash
   ./build-kit.sh fiotec-start-kit
   ```

   O script cria um Laravel 13 novo, instala Filament v5 + todos os pacotes,
   publica migrations/configs e aplica o conteúdo de `overlay/` por cima
   (painéis, páginas, seeders, docker, README).

3. Teste o resultado (`migrate --seed`, acesse `/admin`, `/infra`, `/app`).
4. Publique a pasta gerada no seu Git como o repositório-template do kit:

   ```bash
   git init && git add . && git commit -m "start-kit v0.1.0"
   git tag v0.1.0
   ```

5. (Opcional) registre no Packagist → novos projetos nascem com:

   ```bash
   composer create-project fiotec/start-kit meu-projeto
   ```

   Sem Packagist, dá para usar o repositório Git direto:

   ```bash
   composer create-project fiotec/start-kit meu-projeto \
     --repository='{"type":"vcs","url":"https://github.com/SUA-ORG/start-kit"}' \
     --stability=dev
   ```

## O que tem em `overlay/`

Tudo que diferencia o kit de um Laravel cru — é copiado por cima do projeto gerado:

- 3 PanelProviders (admin/infra/app) + `bootstrap/providers.php`
- `User` com `canAccessPanel()` por papel + `KitServiceProvider` (health checks e gates)
- Painel Admin: CRUD de usuários, Shield (papéis/permissões), Assistente IA (Prism)
- Painel Infra: Health Check, Manutenção (limpeza de caches/filas), Pacotes instalados,
  links para Pulse e Horizon
- `compose.yaml` (Postgres, Redis, Mailpit + profiles: Horizon, MinIO) e `.env.docker`
- `README.md` completo com o checklist de customização
- Logo do Laravel como placeholder em `public/images/logo.svg`

Se quiser alinhar os pacotes com o seu projeto-base (mini-pff), edite a lista no
`build-kit.sh` antes de rodar — ou me envie o `composer.json` do mini-pff que eu ajusto.
