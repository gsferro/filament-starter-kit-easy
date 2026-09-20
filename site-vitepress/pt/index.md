---
layout: home
title: Starter Kit Easy
titleTemplate: Laravel 13 + Filament 5

hero:
  name: Starter Kit Easy
  text: Três painéis, prontos para produção
  tagline: Convites, login social, proteção anti-robô e multi-tenancy opcional. Um comando para instalar.
  actions:
    - theme: brand
      text: Instalar
      link: /pt/comecar/instalacao-avancada
    - theme: alt
      text: Ver no GitHub
      link: https://github.com/gsferro/filament-starter-kit-easy

features:
  - title: Três painéis separados
    details: "/app para quem usa, /admin para quem administra e /infra para quem opera — separados de verdade, cada um com o seu próprio conjunto de permissões."
  - title: Multi-tenancy é opt-in
    details: Nasce desligado. Um comando liga — e a decisão é de instalação, porque as tabelas de permissão só nascem com a coluna de contexto se ela existir antes do migrate.
  - title: Permissões prontas
    details: Filament Shield semeado com papéis, matriz por painel e uma tela para editar tudo isso.
  - title: Configuração em tela
    details: Identidade, e-mail, tabelas, registro e login vivem em /admin/configuracoes-da-aplicacao. Nada de .env, nada de deploy.
  - title: Autenticação completa
    details: Convite de usuário, registro aberto com aprovação manual, quatro provedores de login social e página única de login.
  - title: Observabilidade no /infra
    details: Trilhas de exceções, e-mails enviados e lixeira de registros excluídos, com permissão própria por tela.
---

```bash
composer create-project gsferro/starter-kit-easy meu-projeto
cd meu-projeto && php artisan kit:install
```

![Tela de login do kit](/art/login.png)
