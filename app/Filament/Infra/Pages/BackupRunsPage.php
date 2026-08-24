<?php

declare(strict_types=1);

namespace App\Filament\Infra\Pages;

use App\Filament\Concerns\ExigePermissaoDaTela;
use Brimham\FilamentBackupMonitor\Pages\BackupRunsPage as BackupRunsDoPacote;

/**
 * A tela de execuções de backup, do `brimham/filament-backup-monitor`, com a permissão consultada.
 *
 * A classe do pacote não declara `canAccess()`, e o default do Filament é permissivo — o próprio
 * vendor diz isso em comentário (`Pages/Concerns/CanAuthorizeAccess.php:17-23`). O plugin dela
 * também não publica callback de autorização nenhum: `FilamentBackupMonitorPlugin::register()`
 * (`:17-27`) só registra a Page e o componente Livewire do header widget. Sem ponto de extensão,
 * a saída é a subclasse.
 *
 * ## O nome da classe é OBRIGATÓRIO
 *
 * `BackupRunsPage`, idêntico ao do pacote, porque é o `class_basename` que produz a chave da
 * permissão (`FilamentShield::getDefaultPermissionKeys()`,
 * `vendor/bezhansalleh/filament-shield/src/FilamentShield.php:91-112`). Renomear para
 * `BackupRunsDoKit` criaria `View:BackupRunsDoKit` e deixaria `View:BackupRunsPage` órfã no banco —
 * ninguém a teria, e o checkbox que já existe passaria a apontar para o vazio. É por isso que o
 * import do pai usa alias, e não porque duas classes de nome igual sejam elegantes.
 *
 * ## O plugin não está registrado no painel
 *
 * Registrar o plugin junto criaria duas Pages disputando o slug `backup-runs`, e qual vence
 * dependeria da ordem de registro. `InfraPanelProvider` explica a troca no lugar onde o plugin
 * estava, e o `->livewireComponents([LatestBackupsWidget::class])` de lá é a parte dele que
 * continua necessária.
 *
 * Tirar um plugin do painel costuma ser perigoso — `.ai/rules/providers-filament.md` documenta o
 * `LogicException` que isso dispara em pacotes que resolvem o painel corrente. Aqui é seguro, e a
 * razão foi medida: nada no pacote chama `filament('filament-backup-monitor')`. Ver ADR-04 de
 * `wikis/specs/feat/permissoes-de-telas-de-pacote/permissoes-de-telas-de-pacote/`.
 *
 * Slug, título, ícone, tabela e header widget vêm todos do pai. Esta classe acrescenta uma coisa
 * só: a permissão.
 */
class BackupRunsPage extends BackupRunsDoPacote
{
    use ExigePermissaoDaTela;
}
