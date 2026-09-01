---
title: A rota / é pública
parent: Autenticação
grand_parent: Português
nav_order: 6
---

# A rota `/` é pública e não mostra segredo

[![Tela de boas-vindas na rota /: tres cartoes para os paineis /app, /admin e /infra, e duas secoes com o que o kit:install personalizou](https://raw.githubusercontent.com/gsferro/filament-starter-kit-easy/main/art/thumbs/boas-vindas.png)](https://raw.githubusercontent.com/gsferro/filament-starter-kit-easy/main/art/boas-vindas.png)

No lugar da `welcome.blade.php` do Laravel, a raiz serve `App\Filament\Pages\BoasVindas`: um
cartão por painel (`/app`, `/admin`, `/infra`) e uma infolist com o que a instalação
personalizou — nome, cor, tenancy, prazos de retenção, versão do kit.

Ela é **anônima**, como a página que substitui, e é por isso que a lista do que ela **não**
mostra importa: e-mail, nome e senha do administrador, host e usuário do banco, URL do
repositório, `app.env`, `app.debug`, `app.url` e a configuração de e-mail. Há caso de teste que
planta uma sentinela em cada um desses valores e assere a ausência dela no HTML — junto de um
`assertOk()`, senão um 500 passaria em todas as linhas por engano.

Foi recusada, de propósito, a alternativa "exibir tudo fora de produção": segurança que depende
de `APP_ENV` estar certo não é segurança.

A rota carrega o middleware `panel:app`, e isso não é decoração — é o alias de `SetUpPanel`, que
boota o painel e com isso traz a folha do Filament, a paleta do projeto e o alternador de tema.
Foi medido: `@filamentStyles` sozinho não traz a folha e a página sai âmbar mesmo com
`KIT_COR_PRIMARIA=Violet`. O middleware não autentica ninguém.

```php
// routes/web.php
Route::get('/', BoasVindas::class)->middleware('panel:app')->name('boas-vindas');
```

