<?php

use App\Filament\App\Resources\Convites\ConviteResource;
use App\Filament\App\Resources\Projetos\ProjetoResource;
use App\Filament\App\Resources\Users\UserResource;

/**
 * O painel /app nasce VAZIO — e continua vazio até o projeto ter o que mostrar.
 *
 * É a promessa central do kit para esse painel: ninguém sabe o que o seu negócio
 * vai construir, e resource de exemplo no menu de um projeto de verdade é lixo
 * que alguém vai ter de limpar depois de perguntar de onde veio.
 *
 * Esta suíte roda SEM multi-organização. Os três resources do painel dependem
 * dela — Usuários e Convites porque administram uma organização que não existe,
 * Projetos porque é a demo do isolamento entre organizações.
 */
it('não mostra nenhum resource do kit no menu do /app sem multi-organização', function (string $resource): void {
    expect(config('kit.tenancy.enabled'))->toBeFalse();

    expect($resource::shouldRegisterNavigation())->toBeFalse()
        ->and($resource::canAccess())->toBeFalse();
})->with([
    'projetos (demo)' => ProjetoResource::class,
    'usuários'        => UserResource::class,
    'convites'        => ConviteResource::class,
])->group('kit');

/**
 * `shouldRegisterNavigation()` sozinho tiraria só o item do MENU: a rota
 * continuaria de pé e a busca ⌘K continuaria oferecendo "Criar projeto" — uma
 * affordance para tela que não deveria existir naquele projeto. Por isso o par.
 */
it('esconde o resource de exemplo mesmo com a demo ligada, se não houver tenancy', function (): void {
    config(['kit.demo' => true]);

    expect(ProjetoResource::shouldRegisterNavigation())->toBeFalse()
        ->and(ProjetoResource::canAccess())->toBeFalse();
})->group('kit');
