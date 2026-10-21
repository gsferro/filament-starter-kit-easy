<?php

namespace Tests\Tenancy\Fixtures;

use Filament\Facades\Filament;
use Filament\Pages\Page;
use RuntimeException;

class PaginaComFalhaDeAcessoDoDock extends Page
{
    public static ?string $painelAvaliado = null;

    public static function canAccess(): bool
    {
        static::$painelAvaliado = Filament::getCurrentPanel()->getId();
        throw new RuntimeException('Falha de acesso deliberada do CT-29');
    }
}
