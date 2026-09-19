---
title: Starter Kit Easy
description: Kit inicial Laravel 13 + Filament 5, com três painéis separados, convites, login social, proteção anti-robô e multi-tenancy opcional.
template: splash
hero:
  tagline: Três painéis separados, convites, login social, proteção anti-robô e multi-tenancy opcional. Um comando para instalar.
  actions:
    - text: Instalar
      link: /pt/comecar/instalacao-avancada/
      icon: right-arrow
    - text: Ver no GitHub
      link: https://github.com/gsferro/filament-starter-kit-easy
      icon: external
      variant: minimal
---

```bash
composer create-project gsferro/starter-kit-easy meu-projeto
cd meu-projeto && php artisan kit:install
```

![Tela de login do kit](https://raw.githubusercontent.com/gsferro/filament-starter-kit-easy/main/art/login.png)

## Por onde começar

<div class="cartoes-do-kit">

[**Começar** — instalação avançada, atualização de um projeto que já nasceu do kit e domínio local.](/pt/comecar/)

[**Autenticação** — convites, registro aberto com aprovação, login social, proteção anti-robô e a página única de login.](/pt/autenticacao/)

[**Recursos** — multi-tenancy opcional, anexos e mídia, import e export em CSV, as trilhas do `/infra` e as configurações em tela.](/pt/recursos/)

[**Operação** — trabalhar com agentes de IA, convenções do kit, o que fazer depois de criar seus Resources e como desenvolver o próprio kit.](/pt/operacao/)

</div>

## O que já vem pronto

<div class="cartoes-do-kit">

**Três painéis** — `/app` para quem usa, `/admin` para quem administra e `/infra` para quem opera, separados de verdade, cada um com o seu próprio conjunto de permissões.

**Multi-tenancy é opt-in** — nasce desligado. `php artisan kit:tenancy` liga, e a decisão é de instalação: as tabelas de permissão só nascem com a coluna de contexto se ela existir antes do `migrate`.

**Permissões prontas** — Filament Shield semeado com papéis, matriz por painel e uma tela para editar tudo isso.

**Configuração em tela** — identidade, e-mail, tabelas, registro e login vivem em `/admin/configuracoes-da-aplicacao`. Nada de `.env`, nada de deploy.

</div>

---

O código vive em
[github.com/gsferro/filament-starter-kit-easy](https://github.com/gsferro/filament-starter-kit-easy).
O `README.md` continua existindo como apresentação curta do pacote — é ele que o Packagist mostra.
