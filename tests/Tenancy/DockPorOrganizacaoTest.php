<?php

use App\Filament\Admin\Resources\Users\UserResource;
use App\Filament\App\Resources\Projetos\ProjetoResource;
use App\Filament\Pages\DashboardClassico;
use App\Models\User;
use App\Settings\ConfiguracoesDoKit;
use App\Support\Paineis;
use Database\Seeders\PapeisSeeder;
use Database\Seeders\ShieldPermissionsSeeder;
use Filament\Facades\Filament;

beforeEach(function (): void {
    config(['kit.hub' => true]);
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);
});

it('[CT-05] os grupos preservam organização e ordem sem expor organizações sem acesso', function (string $caso): void {
    ['acme' => $acme, 'globex' => $globex, 'usuario' => $usuario, 'chaves' => $chaves] = arranjarFavoritosDoDockTenancy();
    if ($caso === 'b') {
        $usuario = usuarioComPapel('panel_user', $acme, 'ana@example.com');
        $usuario->tenants()->attach([$acme->id]);
        $this->actingAs($usuario);
    }
    $favoritos = match ($caso) {
        'a'     => [$chaves['PG'], $chaves['PA'], $chaves['A']],
        'b'     => [$chaves['PA']],
        'f'     => [$chaves['PA'], $chaves['PG'], $chaves['PA2']],
        default => [$chaves['PA'], $chaves['PG']],
    };
    if ($caso === 'd') {
        $usuario->tenants()->detach($globex->id);
    }
    if ($caso === 'e') {
        $globex->delete();
    }
    $painel = $caso === 'c' ? 'admin' : 'app';
    gravarAtalhos($usuario, []);
    $semFavoritos = abrirDockTenancy($painel, $painel === 'app' ? $acme : null)->instance()->getDockGroups();
    expect(array_column($semFavoritos, 'label'))->toBe(['Atalhos']);
    $actionsSemFavoritos = array_keys($semFavoritos[0]['actions']);
    expect($actionsSemFavoritos)->not->toBeEmpty();
    gravarAtalhos($usuario, $favoritos);

    $grupos = abrirDockTenancy($painel, $painel === 'app' ? $acme : null)->instance()->getDockGroups();

    $rotulo    = Paineis::rotulo(Filament::getPanel('app'));
    $ga        = [$rotulo.' · Acme', [$chaves['PA']]];
    $gg        = [$rotulo.' · Globex', [$chaves['PG']]];
    $esperados = match ($caso) {
        'a'           => [$gg, $ga, ['Administração', [$chaves['A']]]],
        'b', 'd', 'e' => [$ga],
        'c'           => [$ga, $gg],
        'f'           => [[$rotulo.' · Acme', [$chaves['PA'], $chaves['PA2']]], $gg],
    };
    foreach ($esperados as &$grupoEsperado) {
        $grupoEsperado[1] = array_map(static fn (string $chave): string => 'atalho_'.array_search($chave, $favoritos, true), $grupoEsperado[1]);
    }
    unset($grupoEsperado);
    // Na linha f o dock abre no Dashboard da Acme, que é o favorito PA2: a página já favoritada
    // não oferece "Adicionar esta página" (regra do dock-de-acoes-rapidas).
    $esperados[] = ['Atalhos', $caso === 'f'
        ? array_values(array_diff($actionsSemFavoritos, ['adicionarPaginaAtual']))
        : $actionsSemFavoritos];
    $resumo      = [];
    foreach ($grupos as $grupo) {
        expect($grupo['actions'])->not->toBeEmpty();
        $resumo[] = [$grupo['label'], array_keys($grupo['actions'])];
        if (in_array($caso, ['d', 'e'], true)) {
            expect($grupo['label'])->not->toContain('Globex');
            foreach (['PG', 'PG2'] as $nome) {
                expect(array_keys($grupo['actions']))->not->toContain($chaves[$nome]);
                $indice = array_search($chaves[$nome], $favoritos, true);
                if ($indice !== false) {
                    expect(array_keys($grupo['actions']))->not->toContain('atalho_'.$indice);
                }
            }
            $caminho = parse_url(ProjetoResource::getUrl('index', panel: 'app', tenant: $globex), PHP_URL_PATH);
            foreach ($grupo['actions'] as $action) {
                $this->assertStringNotContainsString($caminho, (string) $action->getUrl());
                $this->assertStringNotContainsString($caminho, (string) $action->getTooltip());
            }
        }
    }
    expect($resumo)->toBe($esperados);
})->with(['a' => ['a'], 'b' => ['b'], 'c' => ['c'], 'd' => ['d'], 'e' => ['e'], 'f' => ['f']]);

it('[CT-06] indicar organização no rótulo não acrescenta consultas pelo dock', function (string $afirmacao): void {
    ['acme' => $acme, 'globex' => $globex, 'usuario' => $usuario, 'chaves' => $chaves] = arranjarFavoritosDoDockTenancy();
    // Na linha "limite", só favoritos da organização corrente: o da Globex paga a conferência de
    // acesso que já existia (dock-de-acoes-rapidas, R22), e não o rótulo da organização.
    $tres                                                         = $afirmacao === 'limite' ? [$chaves['PA'], $chaves['PA2']] : [$chaves['PA'], $chaves['PA2'], $chaves['PG']];
    $medir                                                        = function (array $favoritos) use ($acme, $usuario): array {
        gravarAtalhos($usuario, $favoritos);
        fronteiraDeRequest();
        app()->forgetInstance(ConfiguracoesDoKit::class);
        noPainelDa($acme);
        $this->actingAs(User::query()->findOrFail($usuario->getKey()));

        return consultasDoGet('/app/'.$acme->slug);
    };
    $medir([]);
    $medir($tres);

    $zeroAntes  = $medir([]);
    $tresDepois = $medir($tres);
    $tresAntes  = $medir($tres);
    $zeroDepois = $medir([]);

    expect($zeroAntes)->not->toBeEmpty();
    expect($tresDepois)->not->toBeEmpty();
    if ($afirmacao === 'limite') {
        expect(count($tresDepois) + count($tresAntes))->toBeLessThanOrEqual(count($zeroAntes) + count($zeroDepois) + 4);

        return;
    }
    $daOrganizacao = array_values(array_filter([...$tresDepois, ...$tresAntes], static fn (array $consulta): bool => pilhaComCodigoDoDock($consulta['arquivos'])
        && preg_match('/\b(from|join)\s+[`"\[]?(tenants|tenant_user)[`"\]]?(\s|$)/i', $consulta['sql']) === 1));
    // Controle positivo: a conferência de acesso ao favorito da Globex continua passando por aqui.
    expect($daOrganizacao)->not->toBeEmpty();
    // Toda consulta de organização do dock é a do favorito da Globex: nenhuma pela Acme (nome do
    // rótulo) e nenhuma sem organização (contar as organizações do usuário).
    $proibidas = array_filter($daOrganizacao, static fn (array $consulta): bool => ! in_array($globex->getKey(), $consulta['bindings']));
    expect(array_column($proibidas, 'sql'))->toBe([]);
})->with(['origem' => ['origem'], 'limite' => ['limite']]);

it('[CT-07] tooltip mostra o caminho exato na action e no HTML do favorito', function (string $nome): void {
    ['acme' => $acme, 'globex' => $globex, 'usuario' => $usuario, 'chaves' => $chaves] = arranjarFavoritosDoDockTenancy();
    gravarAtalhos($usuario, [$chaves['PA'], $chaves['PG'], $chaves['PA2'], $chaves['A'], $chaves['PG2']]);
    $dock = abrirDockTenancy('app', $acme);
    $url  = match ($nome) {
        'PA'  => ProjetoResource::getUrl('index', panel: 'app', tenant: $acme),
        'PG'  => ProjetoResource::getUrl('index', panel: 'app', tenant: $globex),
        'PA2' => DashboardClassico::getUrl(panel: 'app', tenant: $acme),
        'A'   => UserResource::getUrl('index', panel: 'admin'),
        'PG2' => DashboardClassico::getUrl(panel: 'app', tenant: $globex),
    };
    $caminho = parse_url($url, PHP_URL_PATH);

    $actions = array_merge(...array_column($dock->instance()->getDockGroups(), 'actions'));

    $nomeDaAction = ['PA' => 'atalho_0', 'PG' => 'atalho_1', 'PA2' => 'atalho_2', 'A' => 'atalho_3', 'PG2' => 'atalho_4'][$nome];
    expect($actions)->toHaveKey($nomeDaAction);
    expect($actions[$nomeDaAction]->getTooltip())->toBe($caminho);
    $documento = new DOMDocument;
    @$documento->loadHTML('<?xml encoding="UTF-8">'.$dock->html());
    $xpath    = new DOMXPath($documento);
    $favorito = $xpath->query('//a[@href="'.$url.'"]');
    expect($favorito->length)->toBe(1);
    $tooltip = $favorito->item(0)->getAttribute('x-tooltip');
    expect($tooltip)->not->toBeEmpty();
    // `content: @js($caminho)` do icon-button do Filament: string JSON entre aspas simples.
    expect(preg_match("/content:\s*'([^']*)'/", $tooltip, $conteudo))->toBe(1);
    expect(json_decode('"'.$conteudo[1].'"'))->toBe($caminho);
})->with(['organização corrente' => ['PA'], 'outra organização' => ['PG'], 'admin' => ['A'], 'Page avulsa' => ['PA2'], 'Page avulsa de outra organização' => ['PG2']]);
