<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Filament\Concerns\ExigePermissaoDaTela;
use Jeffgreco13\FilamentBreezy\Pages\MyProfilePage as MeuPerfilDoPacote;

/**
 * A tela "Meu perfil", do `jeffgreco13/filament-breezy`, com a permissão consultada.
 *
 * A classe do pacote não declara `canAccess()` (`vendor/jeffgreco13/filament-breezy/src/Pages/MyProfilePage.php`),
 * então caía no `true` do Filament. `View:MyProfilePage` existia no banco, aparecia como checkbox
 * em `/admin/shield/roles` e não decidia nada.
 *
 * ## Registro: `->customMyProfilePage()`, nos TRÊS painéis
 *
 * O Breezy publica o ponto de extensão — `customMyProfilePage(string $class)`
 * (`src/Concerns/Plugin/HasMyProfile.php:30-38`), lido por `getMyProfilePageClass()` (`:151-154`)
 * tanto no registro da Page (`BreezyCore.php:70`) quanto na URL do item do menu do usuário
 * (`:115,120`). Então, ao contrário do backup monitor, o plugin **continua registrado** e só a
 * classe muda.
 *
 * A tela existe nos três painéis, com uma permissão só — e as quatro papéis do kit já a carregam
 * (`admin`, `infra`, `admin_app`, `panel_user`), então o default do kit não muda: o que passa a
 * existir é a alavanca.
 *
 * ## Por que aqui e não em `Filament/Infra/Pages/`
 *
 * `app/Filament/Pages/` não é varrido por nenhum `discoverPages()` — os três providers apontam
 * para `Filament/{Admin,App,Infra}/Pages`. Numa pasta descoberta, esta classe entraria no painel
 * DUAS vezes: pela descoberta e pelo registro do Breezy. `app/Filament/Pages/Auth/` é o precedente
 * do mesmo arranjo.
 *
 * ## O nome da classe é OBRIGATÓRIO
 *
 * `MyProfilePage`, idêntico ao do pacote, porque é o `class_basename` que produz a chave da
 * permissão (`FilamentShield::getDefaultPermissionKeys()`,
 * `vendor/bezhansalleh/filament-shield/src/FilamentShield.php:91-112`). Um nome em português
 * criaria `View:MeuPerfil` e deixaria `View:MyProfilePage` órfã nos quatro papéis. Daí o alias no
 * import. Ver ADR-04 de
 * `wikis/specs/feat/permissoes-de-telas-de-pacote/permissoes-de-telas-de-pacote/`.
 *
 * Slug, título, ícone, grupo e os componentes de perfil vêm todos do pai.
 */
class MyProfilePage extends MeuPerfilDoPacote
{
    use ExigePermissaoDaTela;
}
