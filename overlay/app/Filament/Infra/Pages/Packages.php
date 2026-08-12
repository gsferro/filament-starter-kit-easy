<?php

namespace App\Filament\Infra\Pages;

use BackedEnum;
use Composer\InstalledVersions;
use Filament\Pages\Page;

class Packages extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-cube';

    protected static ?string $title = 'Pacotes';

    protected static string|\UnitEnum|null $navigationGroup = 'Manutenção';

    protected string $view = 'filament.infra.pages.packages';

    public function getPackages(): array
    {
        return collect(InstalledVersions::getInstalledPackages())
            ->sort()
            ->mapWithKeys(fn (string $package) => [
                $package => InstalledVersions::getPrettyVersion($package) ?? '—',
            ])
            ->all();
    }
}
