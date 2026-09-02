---
title: Proteção anti-robô
parent: Autenticação
grand_parent: Português
nav_order: 4
---

# Proteção anti-robô

As telas públicas de **login**, **recuperação de senha** e **registro** dos três painéis podem receber um desafio anti-robô. A proteção nasce **desligada** e, quando desligada, as telas são exatamente as mesmas de antes — sem scripts externos e sem campos extras.

As duas capturas abaixo são da **mesma** tela de login, mudando só o provedor — e mostram a diferença que pesa na escolha: o Turnstile pede um clique, o reCAPTCHA v3 não pede nada.

| Cloudflare Turnstile — a caixa aparece e pede o clique | Google reCAPTCHA v3 — nenhuma caixa, só o emblema no canto |
|---|---|
| [![Tela de login com o desafio do Cloudflare Turnstile: a caixa "Verify you are human" entre "Lembre de mim" e o botão Login](https://raw.githubusercontent.com/gsferro/filament-starter-kit-easy/main/art/thumbs/login-turnstile.png)](https://raw.githubusercontent.com/gsferro/filament-starter-kit-easy/main/art/login-turnstile.png) | [![Tela de login com o reCAPTCHA v3: o formulário sem nenhum campo a mais e o emblema "protected by reCAPTCHA" no canto inferior direito](https://raw.githubusercontent.com/gsferro/filament-starter-kit-easy/main/art/thumbs/login-recaptcha-v3.png)](https://raw.githubusercontent.com/gsferro/filament-starter-kit-easy/main/art/login-recaptcha-v3.png) |

[![Seção "Proteção anti-robô" nas configurações do kit: o toggle que exige o desafio, o toggle de ambiente local, o provedor (reCAPTCHA v3), a pontuação mínima em 0,5 e os campos de chave do site e chave secreta](https://raw.githubusercontent.com/gsferro/filament-starter-kit-easy/main/art/thumbs/admin-anti-robo.png)](https://raw.githubusercontent.com/gsferro/filament-starter-kit-easy/main/art/admin-anti-robo.png)

Quem renderiza o widget e fala com o provedor é o pacote [`ddr/filament-captcha`](https://github.com/danie1net0/filament-captcha); o kit acrescenta o que o pacote não faz: a decisão de aparecer vem da tela de Settings, a falha é **fechada** (provedor fora do ar = envio recusado, não liberado), toda recusa vai para o canal `autenticacao`, e o widget se redefine depois de cada tentativa (o token é de uso único). Um provedor por vez:

| Provedor | Valor | Como é |
|---|---|---|
| Google reCAPTCHA v2 | `recaptcha_v2` | a caixa "não sou um robô" |
| Google reCAPTCHA v3 | `recaptcha_v3` | **padrão**; invisível — o Google devolve uma pontuação de 0 a 1 e o kit recusa abaixo da **pontuação mínima** (0,5 por padrão). Não pede clique de quem entra |
| Cloudflare Turnstile | `turnstile` | sem rastreamento, sem custo |
| hCaptcha | `hcaptcha` | — |

Quem tiver a permissão `View:ConfiguracoesDoKit` liga e configura em `/admin/configuracoes-do-kit` › Login › Proteção anti-robô: provedor, chave do site (vai para o HTML), chave secreta (cifrada no banco, nunca exibida) e, para o v3, a pontuação mínima. No `.env` as mesmas chaves são `KIT_ANTI_ROBO`, `KIT_ANTI_ROBO_PROVEDOR`, `KIT_ANTI_ROBO_CHAVE_DO_SITE`, `KIT_ANTI_ROBO_CHAVE_SECRETA` e `KIT_ANTI_ROBO_PONTUACAO_MINIMA` — o banco vence. As env vars próprias do pacote (`CAPTCHA_DRIVER`, `RECAPTCHA_V2_SITEKEY`, ...) são ignoradas de propósito: uma configuração, uma dona.

**O provedor padrão não liga nada.** `recaptcha_v3` é apenas qual provedor vale **se** alguém habilitar a proteção e gravar as duas chaves — a proteção nasce desligada e, sem as chaves, continua desligada mesmo com o toggle ligado. Nenhum desafio é carregado em tela nenhuma até essa decisão ser tomada na tela de Settings (ou no `.env`).

Ligar não liga sozinho: sem as duas chaves, ou com provedor fora da lista, a proteção fica desligada (com aviso no log) — um campo obrigatório que ninguém consegue preencher trancaria o login de todo mundo, inclusive o seu.

**Em ambiente local o desafio fica desligado por padrão**, mesmo com tudo configurado: chave de produção não aceita `localhost`, e o widget renderizaria um erro no lugar da caixa. Para ver o desafio com `APP_ENV=local` (com chaves que aceitam localhost, ou as [chaves de teste do Google](https://developers.google.com/recaptcha/docs/faq#id-like-to-run-automated-tests-with-recaptcha.-what-should-i-do) / [do Cloudflare](https://developers.cloudflare.com/turnstile/troubleshooting/testing/)), ligue `KIT_ANTI_ROBO_LOCAL=true` ou o toggle "Aplicar também em ambiente local" na mesma seção — **o toggle só aparece na tela quando a aplicação roda com `APP_ENV=local`**, porque fora dali ele não decide nada: quem consulta a chave é `ConfiguracaoDoLogin::antiRobo()`, e só em ambiente local. Esconder o campo não apaga o valor gravado; ele continua no banco, e continua sem efeito fora de local.

Se você já usava a proteção antes da v0.22 com o valor `recaptcha`, a migration de settings converte para `recaptcha_v2` sozinha — rode `php artisan migrate`.

> O estudo de adoção do pacote e as alternativas recusadas estão em `wikis/specs/feat/adotar-ddr-filament-captcha/adotar-ddr-filament-captcha/`; a decisão original de ter a proteção, em `wikis/specs/feat/recaptcha-nas-telas-publicas/recaptcha-nas-telas-publicas/`.

