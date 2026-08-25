<?php

declare(strict_types=1);

namespace App\Filament\Infra\Widgets;

use App\Filament\Concerns\ExigePermissaoDoWidget;
use Illuminate\Support\Facades\Schema;
use MominAlZaraa\FilamentComposerReleaseNotifier\Filament\Widgets\ComposerReleaseOverviewWidget as ComposerReleasesDoPacote;

/**
 * O cartão de releases do Composer, do `mominalzaraa/filament-composer-release-notifier`, com a
 * permissão consultada.
 *
 * O widget do pacote tem `canView(): bool { return auth()->check(); }`
 * (`.../Filament/Widgets/ComposerReleaseOverviewWidget.php:18-21`), e o plugin não expõe callback
 * para trocar isso — só `->widget(enabled: bool)`. Então o `InfraPanelProvider` desliga o do pacote
 * e esta subclasse entra pelo `discoverWidgets()`.
 *
 * ## O nome da classe é OBRIGATÓRIO
 *
 * `ComposerReleaseOverviewWidget`, idêntico ao do pacote, porque é o `class_basename` que produz a
 * chave da permissão (`FilamentShield::getDefaultPermissionKeys()`,
 * `vendor/bezhansalleh/filament-shield/src/FilamentShield.php:91-112`). Ver ADR-04 de
 * `wikis/specs/feat/permissoes-de-telas-de-pacote/permissoes-de-telas-de-pacote/`.
 *
 * ## Por que o trait vence o `canView()` do pai
 *
 * Precedência de PHP: método vindo de trait na classe vence método HERDADO da classe pai (só perde
 * para método declarado na própria classe). Então `ExigePermissaoDoWidget::canView()` é quem
 * responde, e o `parent::canView()` de dentro da trait do Shield continua caindo no
 * `auth()->check()` do pacote quando a chave não resolve — que é o fail-open herdado de ADR-01 da
 * wiki `permissoes-de-telas-e-acoes`.
 *
 * Por isso esta classe NÃO declara `canView()`: a regra própria vai em
 * `fonteDeDadosDisponivel()`. Declarar `canView()` aqui desligaria a permissão em silêncio.
 */
class ComposerReleaseOverviewWidget extends ComposerReleasesDoPacote
{
    use ExigePermissaoDoWidget;

    /**
     * A tabela de snapshots é do pacote, e o `canView()` dele não a conferia.
     *
     * `.ai/rules/filament.md` §"Qual pacote de widget" manda o guarda porque widget que estoura
     * derruba o dashboard INTEIRO — e `getStats()` consulta
     * `ComposerReleasePackageSnapshot` (`protected $table = 'composer_release_package_snapshots'`)
     * sem nenhuma proteção. Numa instalação que ainda não rodou as migrations do pacote, o /infra
     * ficava em branco.
     *
     * Isto NÃO é autorização: as duas coisas convivem em `&&`.
     */
    protected static function fonteDeDadosDisponivel(): bool
    {
        return (bool) rescue(fn (): bool => Schema::hasTable('composer_release_package_snapshots'), false);
    }
}
