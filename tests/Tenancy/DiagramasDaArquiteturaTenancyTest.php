<?php

use App\Filament\Admin\Resources\Convites\Pages\ListConvites;
use App\Filament\App\Pages\ConvitesRecebidos;
use App\Filament\Pages\Auth\RegistroPorConvite;
use App\Http\Middleware\DefinirTenantDePermissoes;
use App\Models\Convite;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\PapeisSeeder;
use Database\Seeders\ShieldPermissionsSeeder;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Filament\Http\Middleware\IdentifyTenant;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;
use Spatie\Permission\PermissionRegistrar;

/**
 * Os CT do lote `T` (Tenancy) da wiki `diagramas-da-arquitetura`, ver
 * `wikis/specs/feat/diagramas-da-arquitetura/diagramas-da-arquitetura/04-casos-de-teste.md`:
 * CT-12, CT-14, CT-80, CT-89, CT-98, CT-112, CT-113, CT-114 — os únicos do `## Índice de
 * Cenários` cujo arquivo é este (`.ai/rules/testes.md` §"Nem todo papel do kit existe em toda
 * suíte": `admin_app` só existe aqui; e a tabela "Extras do catálogo — suíte Tenancy" do `04`,
 * linha 260: R51/R52 exigem o GET real, que só esta suíte tem o modo multi-tenant ligado para
 * fazer).
 *
 * *(RD2-17, 2026-09-28, correção)* Cada cenário mistura uma parte que executa CÓDIGO REAL
 * (canAccessPanel(), a máquina de estados do convite pelos pontos de entrada) com a conferência do
 * bloco Mermaid (README/`docs/`) — mas a sentinela abaixo pula o ARQUIVO INTEIRO, inclusive a
 * parte de código real: não há hoje neste arquivo um cenário que rode independente de `docs/`
 * existir. `docs/` fica ausente num projeto nascido do `composer create-project`
 * (`.gitattributes:40 /docs export-ignore`), por isso a sentinela, na MESMA forma de
 * `tests/Kit/DiagramasDaArquiteturaTest.php:beforeEach:70` (RD-02): sem ela, `composer test:kit`
 * (que roda `--testsuite=Kit,Tenancy`, `composer.json:test:kit:156`) nasceria vermelho em toda
 * instalação nova, quando o certo é pular — o mesmo comportamento que o arquivo irmão já tinha.
 */
beforeEach(function (): void {
    if (! naArvoreDoKit()) {
        test()->markTestSkipped('A guarda dos diagramas só existe na árvore do kit — o projeto instalado não recebe a documentação do site nem o README pelo kit:update.');
    }

    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);
});

/*
|--------------------------------------------------------------------------
| Extração de blocos Mermaid — genérica para flowchart/graph e state diagram
|--------------------------------------------------------------------------
| O bloco do catálogo em si (`blocoDoCatalogoNaArvore()`) mora em `tests/Pest.php`, porque os
| DOIS arquivos (este e `tests/Kit/DiagramasDaArquiteturaTest.php`) o usam (RD-11,
| `.ai/rules/testes.md` §"Nunca crie um clone com outro nome"). O que é LOCAL a este arquivo é a
| leitura do GRAFO a partir do bloco (`grafoDoFluxo()`, `transicoesDeEstado()` etc.) — só o lote
| Tenancy percorre DG-02/DG-03/DG-09/DG-07 pela ótica de organização/contexto.
*/

/**
 * As arestas que SAEM de um nó cujo id OU rótulo contém `$origem` (comparação livre de acento
 * e caixa), pelo texto do destino.
 *
 * @param  array{nos: array<string, string>, arestas: list<array{de: string, para: string, rotulo: ?string, linha: int}>}  $grafo
 * @return list<array{destinoTexto: string, rotulo: ?string}>
 */
function arestasDeOrigem(array $grafo, string $origem): array
{
    $normalizar = static fn (string $s): string => mb_strtolower(str_replace(['á', 'ã', 'â', 'é', 'ê', 'í', 'ó', 'ô', 'õ', 'ú', 'ç'], ['a', 'a', 'a', 'e', 'e', 'i', 'o', 'o', 'o', 'u', 'c'], $s));

    $idsDaOrigem = array_keys(array_filter(
        $grafo['nos'],
        static fn (string $rotulo, string $id): bool => str_contains($normalizar($id), $normalizar($origem)) || str_contains($normalizar($rotulo), $normalizar($origem)),
        ARRAY_FILTER_USE_BOTH,
    ));

    $resultado = [];

    foreach ($grafo['arestas'] as $aresta) {
        if (! in_array($aresta['de'], $idsDaOrigem, true)) {
            continue;
        }

        $resultado[] = [
            'destinoTexto' => $grafo['nos'][$aresta['para']] ?? $aresta['para'],
            'rotulo'       => $aresta['rotulo'],
        ];
    }

    return $resultado;
}

/**
 * O prefixo de entidade de permissão (`Action:Entidade`, Shield) do caso de uso do DG-02 — mapa
 * de MECANISMO, escolhido pela guarda (R6 é EP, não a matriz fechada de R44/CT-83).
 */
function entidadeDoCasoDeUso(string $rotulo): ?string
{
    $r = mb_strtolower($rotulo);

    return match (true) {
        // pt e en: o bloco en traduz o rótulo (CT-03), e o ID do nó não entra aqui.
        // Convite antes de User: "Convidar usuário" / "Invite user" nomeia os dois, e o caso é o convite.
        str_contains($r, 'convite') || str_contains($r, 'invit')                           => 'Convite',
        str_contains($r, 'usuár') || str_contains($r, 'usuar') || str_contains($r, 'user') => 'User',
        default                                                                            => null,
    };
}

/*
|--------------------------------------------------------------------------
| R6 — CT-12: admin_app no DG-02, só com tenancy
|--------------------------------------------------------------------------
*/

it('[CT-12] admin_app administra a propria organizacao e so existe com tenancy', function (): void {
    $papel = Role::findByName('admin_app');

    expect($papel->painel)->toBe('app');

    foreach (['pt', 'en'] as $idioma) {
        $bloco = blocoDoCatalogoNaArvore('DG-02', $idioma);

        expect($bloco)->not->toBeNull("DG-02 não encontrado em nenhum arquivo {$idioma} (README ou docs/{$idioma})");

        $grafo   = grafoDoFluxo($bloco['bloco']);
        $arestas = arestasDeOrigem($grafo, 'admin_app');

        expect($arestas)->not->toBeEmpty("admin_app sem nenhuma aresta no DG-02 ({$idioma})");

        $destinos = array_map(static fn (array $a): string => mb_strtolower((string) $a['destinoTexto']), $arestas);

        expect(collect($destinos)->contains(fn (string $d): bool => entidadeDoCasoDeUso($d) === 'User'))
            ->toBeTrue("admin_app não se liga a \"gerir usuários\" no DG-02 ({$idioma})");
        expect(collect($destinos)->contains(fn (string $d): bool => entidadeDoCasoDeUso($d) === 'Convite'))
            ->toBeTrue("admin_app não se liga a \"gerir convites\" no DG-02 ({$idioma})");

        foreach ($arestas as $aresta) {
            $entidade = entidadeDoCasoDeUso((string) $aresta['destinoTexto']);

            expect($entidade)->not->toBeNull("caso de uso de admin_app sem entidade mapeada no DG-02 ({$idioma}): \"{$aresta['destinoTexto']}\"");

            $temAlgumaPermissaoDaEntidade = collect($papel->permissions->pluck('name'))
                ->contains(fn (string $p): bool => str_ends_with($p, ":{$entidade}"));

            expect($temAlgumaPermissaoDaEntidade)
                ->toBeTrue("admin_app não tem nenhuma permissão de {$entidade}, mas o DG-02 o liga a \"{$aresta['destinoTexto']}\" ({$idioma})");
        }

        test()->assertStringContainsString('KIT_TENANCY', $bloco['bloco'], "o bloco DG-02 não nomeia KIT_TENANCY junto de admin_app ({$idioma})");
    }
});

/*
|--------------------------------------------------------------------------
| R7 — CT-14 e CT-98: a tabela de decisão de canAccessPanel, com e sem acumulação de contexto
|--------------------------------------------------------------------------
*/

it('[CT-14] o papel atribuido dentro de uma organizacao nao abre painel de instalacao', function (string $papel, string $onde, string $painelId, string $desfecho): void {
    $acme = tenant('Acme', 'acme');
    $user = usuario();

    papelNaOrganizacao($user, $papel, $onde === 'dentro de acme' ? $acme : null);

    $painel = Filament::getPanel($painelId);

    expect($user->canAccessPanel($painel))->toBe(
        $desfecho === 'entra',
        "canAccessPanel() real diverge do esperado ({$desfecho}) para papel '{$papel}' {$onde}, painel {$painelId}",
    );

    $bloco = blocoDoCatalogoNaArvore('DG-03', 'pt');

    expect($bloco)->not->toBeNull('DG-03 não encontrado em nenhum arquivo pt (README ou docs/pt)');

    $resultado = resultadoDoFluxo(grafoDoFluxo($bloco['bloco']), avaliadorDoDG03($user, $painel));

    expect($resultado)->toBe(
        $desfecho,
        "DG-03 percorrido diverge de canAccessPanel() para papel '{$papel}' {$onde}, painel {$painelId}",
    );
})->with([
    'admin dentro de acme, painel admin'       => ['admin', 'dentro de acme', 'admin', 'nega'],
    'admin no contexto global, painel admin'   => ['admin', 'no contexto global', 'admin', 'entra'],
    'panel_user dentro de acme, painel app'    => ['panel_user', 'dentro de acme', 'app', 'entra'],
]);

it('[CT-98] papeis em contextos diferentes abrem so o painel cujo papel esta no contexto que ele exige', function (string $combo, string $painelId, string $desfecho): void {
    $acme = tenant('Acme', 'acme');
    $user = usuario();

    match ($combo) {
        'admin global + admin_app acme' => (function () use ($user, $acme): void {
            papelNaOrganizacao($user, 'admin');
            papelNaOrganizacao($user, 'admin_app', $acme);
        })(),
        'infra global + admin acme' => (function () use ($user, $acme): void {
            papelNaOrganizacao($user, 'infra');
            papelNaOrganizacao($user, 'admin', $acme);
        })(),
        'infra global + admin_app acme' => (function () use ($user, $acme): void {
            papelNaOrganizacao($user, 'infra');
            papelNaOrganizacao($user, 'admin_app', $acme);
        })(),
        'panel_user acme + admin_app acme' => (function () use ($user, $acme): void {
            papelNaOrganizacao($user, 'panel_user', $acme);
            papelNaOrganizacao($user, 'admin_app', $acme);
        })(),
        default => throw new RuntimeException("CT-98: combinação desconhecida \"{$combo}\""),
    };

    $painel = Filament::getPanel($painelId);

    expect($user->canAccessPanel($painel))->toBe(
        $desfecho === 'entra',
        "canAccessPanel() real diverge do esperado ({$desfecho}) para o combo '{$combo}', painel {$painelId}",
    );

    $bloco = blocoDoCatalogoNaArvore('DG-03', 'pt');

    expect($bloco)->not->toBeNull('DG-03 não encontrado em nenhum arquivo pt (README ou docs/pt)');

    $resultado = resultadoDoFluxo(grafoDoFluxo($bloco['bloco']), avaliadorDoDG03($user, $painel));

    expect($resultado)->toBe(
        $desfecho,
        "DG-03 percorrido diverge de canAccessPanel() para o combo '{$combo}', painel {$painelId}",
    );
})->with([
    'admin no contexto global + admin_app em acme, painel admin' => ['admin global + admin_app acme', 'admin', 'entra'],
    'admin no contexto global + admin_app em acme, painel app'   => ['admin global + admin_app acme', 'app', 'entra'],
    'infra no contexto global + admin em acme, painel admin'     => ['infra global + admin acme', 'admin', 'nega'],
    'infra no contexto global + admin em acme, painel infra'     => ['infra global + admin acme', 'infra', 'entra'],
    'infra no contexto global + admin_app em acme, painel infra' => ['infra global + admin_app acme', 'infra', 'entra'],
    'infra no contexto global + admin_app em acme, painel app'   => ['infra global + admin_app acme', 'app', 'entra'],
    'infra no contexto global + admin_app em acme, painel admin' => ['infra global + admin_app acme', 'admin', 'nega'],
    'panel_user em acme + admin_app em acme, painel app'         => ['panel_user acme + admin_app acme', 'app', 'entra'],
    'panel_user em acme + admin_app em acme, painel admin'       => ['panel_user acme + admin_app acme', 'admin', 'nega'],
]);

/*
|--------------------------------------------------------------------------
| R13 — CT-80: a matriz fechada do convite (4 estados x 6 eventos), pelo ponto de entrada
|--------------------------------------------------------------------------
*/

// `transicoesDeEstado()` mora em `tests/Pest.php` — dois arquivos a usam (este e
// `tests/Kit/DiagramasDaArquiteturaTest.php`, QA-07, `.ai/rules/testes.md` §"Nunca crie um clone
// com outro nome").

/**
 * Constrói o convite na situação de partida pedida, por TRANSIÇÃO REAL (Setup Global).
 * `$ana` já tem conta — CT-80 roda sobre uma OFERTA, não um convite de conta nova.
 */
function ofertaDeAnaEm(string $estado, Tenant $acme, User $ana): Convite
{
    $convite = ofertaPara($ana->email, $acme);
    $convite->enviar();

    return match ($estado) {
        'Pendente' => $convite->fresh(),
        'Aceito'   => tap($convite, fn (Convite $c) => $c->aceitarComoUsuarioExistente($ana))->fresh(),
        'Recusado' => tap($convite, fn (Convite $c) => $c->recusar($ana))->fresh(),
        'Expirado' => tap($convite, function (Convite $c): void {
            test()->travelTo($c->fresh()->expira_em->clone()->addDay());
        })->fresh(),
    };
}

/** Reenvia pelo ponto de entrada real — a Action `reenviar` da tabela de /admin. */
function reenviarPelaTela(Convite $convite): bool
{
    $oferecido = in_array($convite->fresh()->situacao(), ['Pendente', 'Expirado'], true);

    Filament::setCurrentPanel('admin');
    test()->actingAs(usuarioComPapel('master_global'));

    $componente = Livewire::test(ListConvites::class)->loadTable();

    if (! $oferecido) {
        $componente->assertActionHidden(TestAction::make('reenviar')->table($convite));

        return false;
    }

    $componente
        ->assertActionVisible(TestAction::make('reenviar')->table($convite))
        ->callAction(TestAction::make('reenviar')->table($convite));

    return true;
}

/** Revoga pelo ponto de entrada real — o `DeleteAction` da tabela de /admin, visível em todo estado. */
function revogarPelaTela(Convite $convite): void
{
    Filament::setCurrentPanel('admin');
    test()->actingAs(usuarioComPapel('master_global'));

    Livewire::test(ListConvites::class)
        ->loadTable()
        ->callAction(TestAction::make('delete')->table($convite));
}

/**
 * Aceita ou recusa pelo ponto de entrada real — a caixa de convites recebidos do /app.
 *
 * A tela recorta a query por `Convite::pendentesPara()` (`app/Filament/App/Pages/ConvitesRecebidos.php:88`):
 * um convite fora dessa situação não é só uma Action escondida, é uma LINHA AUSENTE da tabela —
 * `TestAction::make($evento)->table($convite)` não consegue localizar o registro para checar a
 * Action nesse caso (o snapshot do Livewire quebra tentando montar em cima de uma linha que a
 * própria query já excluiu). O oráculo de "não oferecida" É essa ausência da query — a mesma que
 * a tela usa —, então não há Action a checar quando ela é falsa.
 *
 * `$orgDoOperador` é a organização em que ANA JÁ opera (`panel_user`) — precisa ser uma
 * organização diferente da do convite: sem PAPEL nenhum em contexto nenhum ela não tem
 * `Aceitar:Convite`/`Recusar:Convite` (a permissão nasce com o papel, não com "estar
 * autenticado"), e `TestAction` quebra o snapshot do Livewire tentando montar uma Action que a
 * autorização nem chega a registrar — sintoma visto sem esta persona.
 */
function aceitarOuRecusarPelaCaixa(string $evento, Convite $convite, User $ana, Tenant $orgDoOperador): bool
{
    $oferecido = Convite::pendentesPara($ana)->whereKey($convite->id)->exists();

    if (! $oferecido) {
        return false;
    }

    noPainelDa($orgDoOperador);
    test()->actingAs($ana);

    Livewire::test(ConvitesRecebidos::class)
        ->loadTable()
        ->assertActionVisible(TestAction::make($evento)->table($convite))
        ->callAction(TestAction::make($evento)->table($convite));

    return true;
}

/** Roda o comando agendado e devolve se ESTE convite recebeu lembrete nesta execução. */
function lembrarPeloComando(Convite $convite): bool
{
    $antes = $convite->fresh()->lembretes_enviados;

    test()->artisan('kit:convites-lembrar')->assertSuccessful();

    return $convite->fresh()->lembretes_enviados > $antes;
}

it('[CT-80] cada uma das 24 celulas da matriz fechada do convite, executada pelo ponto de entrada, e a celula desenhada', function (string $estado, string $evento, bool $selecionado, string $resultado, ?string $alvo) {
    Notification::fake();
    config(['kit.convites.lembretes_dias' => [1]]);

    $acme   = tenant('Acme', 'acme');
    $globex = tenant('Globex', 'globex');

    // Ana já opera a Globex como panel_user — a oferta de Acme é para uma SEGUNDA organização.
    // Sem papel nenhum ela não teria `Aceitar:Convite`/`Recusar:Convite` (a permissão nasce com
    // o papel), e a Action nem chegaria a existir para a checagem da tela.
    $ana = usuarioComPapel('panel_user', $globex, 'ana@example.com');
    $globex->users()->attach($ana);

    $convite = ofertaDeAnaEm($estado, $acme, $ana);

    switch ($evento) {
        case 'reenviar':
            $ofertado = reenviarPelaTela($convite);
            break;
        case 'lembrar':
            // Só o dia do envio não venceria (`kit.convites.lembretes_dias = [1]`); anda 1 dia
            // e meio para o único caminho feliz (Pendente) vencer o prazo.
            test()->travel(36)->hours();
            $ofertado = lembrarPeloComando($convite);
            break;
        case 'aceitar':
        case 'recusar':
            $ofertado = aceitarOuRecusarPelaCaixa($evento, $convite, $ana, $globex);
            break;
        case 'prazo vence':
            test()->travelTo($convite->fresh()->expira_em->clone()->addDay());
            $ofertado = true;
            break;
        case 'revogar':
            revogarPelaTela($convite);
            $ofertado = true;
            break;
        default:
            throw new InvalidArgumentException("evento desconhecido: {$evento}");
    }

    expect($ofertado)->toBe(
        $selecionado,
        "evento '{$evento}' em '{$estado}': oferecido/selecionado deveria ser ".($selecionado ? 'true' : 'false'),
    );

    if ($evento === 'revogar') {
        $this->assertDatabaseMissing('convites', ['id' => $convite->id]);
    } else {
        expect($convite->fresh()->situacao())->toBe($resultado, "situacao() diverge para '{$estado}' x '{$evento}'");
    }

    foreach (['pt', 'en'] as $idioma) {
        $bloco = blocoDoCatalogoNaArvore('DG-09', $idioma);

        expect($bloco)->not->toBeNull("DG-09 não encontrado em nenhum arquivo {$idioma} (README ou docs/{$idioma})");

        if ($idioma !== 'pt') {
            // A tradução do rótulo do evento/estado para EN é fixada pelo lote Kit (R43,
            // CT-86) — aqui confere-se só a PARIDADE ESTRUTURAL (mesmo total de arestas que o
            // bloco pt), não o texto.
            $blocoPt = blocoDoCatalogoNaArvore('DG-09', 'pt');

            expect(transicoesDeEstado($bloco['bloco']))->toHaveCount(
                count(transicoesDeEstado($blocoPt['bloco'])),
                'DG-09 en não tem o mesmo número de transições que o DG-09 pt',
            );

            continue;
        }

        $transicoes = transicoesDeEstado($bloco['bloco']);

        $daCelula = array_values(array_filter(
            $transicoes,
            static fn (array $t): bool => $t['de'] === $estado && mb_strtolower((string) $t['evento']) === mb_strtolower($evento),
        ));

        if ($alvo === null) {
            expect($daCelula)->toBe([], "DG-09 desenha 'de {$estado} por {$evento}', mas a célula não é oferecida/não tem efeito de transição");

            continue;
        }

        expect($daCelula)->not->toBe([], "DG-09 não desenha nenhuma seta de '{$estado}' por '{$evento}' — esperada para '{$alvo}'");

        $paraEsperado = $alvo === 'fim' ? '[*]' : $alvo;

        test()->assertContains(
            $paraEsperado,
            collect($daCelula)->pluck('para')->all(),
            "DG-09: a seta de '{$estado}' por '{$evento}' não vai para '{$paraEsperado}'",
        );
    }
})->with([
    // estado, evento, selecionado/ofertado, resultado (situacao()), alvo da seta (null = nenhuma)
    'Pendente x reenviar'                => ['Pendente', 'reenviar', true, 'Pendente', null],
    'Pendente x lembrar'                 => ['Pendente', 'lembrar', true, 'Pendente', null],
    'Pendente x aceitar'                 => ['Pendente', 'aceitar', true, 'Aceito', 'Aceito'],
    'Pendente x recusar'                 => ['Pendente', 'recusar', true, 'Recusado', 'Recusado'],
    'Pendente x prazo vence'             => ['Pendente', 'prazo vence', true, 'Expirado', 'Expirado'],
    'Pendente x revogar'                 => ['Pendente', 'revogar', true, 'Pendente', 'fim'],
    'Aceito x reenviar'                  => ['Aceito', 'reenviar', false, 'Aceito', null],
    'Aceito x lembrar'                   => ['Aceito', 'lembrar', false, 'Aceito', null],
    'Aceito x aceitar'                   => ['Aceito', 'aceitar', false, 'Aceito', null],
    'Aceito x recusar'                   => ['Aceito', 'recusar', false, 'Aceito', null],
    'Aceito x prazo vence'               => ['Aceito', 'prazo vence', true, 'Aceito', null],
    'Aceito x revogar'                   => ['Aceito', 'revogar', true, 'Aceito', 'fim'],
    'Recusado x reenviar'                => ['Recusado', 'reenviar', false, 'Recusado', null],
    'Recusado x lembrar'                 => ['Recusado', 'lembrar', false, 'Recusado', null],
    'Recusado x aceitar'                 => ['Recusado', 'aceitar', false, 'Recusado', null],
    'Recusado x recusar'                 => ['Recusado', 'recusar', false, 'Recusado', null],
    'Recusado x prazo vence'             => ['Recusado', 'prazo vence', true, 'Recusado', null],
    'Recusado x revogar'                 => ['Recusado', 'revogar', true, 'Recusado', 'fim'],
    'Expirado x reenviar'                => ['Expirado', 'reenviar', true, 'Pendente', 'Pendente'],
    'Expirado x lembrar'                 => ['Expirado', 'lembrar', false, 'Expirado', null],
    'Expirado x aceitar'                 => ['Expirado', 'aceitar', false, 'Expirado', null],
    'Expirado x recusar'                 => ['Expirado', 'recusar', false, 'Expirado', null],
    'Expirado x prazo vence'             => ['Expirado', 'prazo vence', true, 'Expirado', null],
    'Expirado x revogar'                 => ['Expirado', 'revogar', true, 'Expirado', 'fim'],
]);

/** 2-switch: o giro novo depois do reenvio também vence o prazo — CT-21/CT-80, linha extra. */
it('[CT-80] 2-switch: expirado reenviado e depois aceito muda para Aceito, com as duas setas', function (): void {
    Notification::fake();

    $acme   = tenant('Acme', 'acme');
    $globex = tenant('Globex', 'globex');
    $ana    = usuarioComPapel('panel_user', $globex, 'ana@example.com');
    $globex->users()->attach($ana);

    $convite = ofertaDeAnaEm('Expirado', $acme, $ana);

    expect(reenviarPelaTela($convite))->toBeTrue();
    expect($convite->fresh()->situacao())->toBe('Pendente');

    expect(aceitarOuRecusarPelaCaixa('aceitar', $convite, $ana, $globex))->toBeTrue();
    expect($convite->fresh()->situacao())->toBe('Aceito');

    $bloco = blocoDoCatalogoNaArvore('DG-09', 'pt');

    expect($bloco)->not->toBeNull('DG-09 não encontrado em nenhum arquivo pt (README ou docs/pt)');

    $transicoes = transicoesDeEstado($bloco['bloco']);

    $temReenvio = collect($transicoes)->contains(fn (array $t): bool => $t['de'] === 'Expirado' && $t['para'] === 'Pendente' && mb_strtolower((string) $t['evento']) === 'reenviar');
    $temAceite  = collect($transicoes)->contains(fn (array $t): bool => $t['de'] === 'Pendente' && $t['para'] === 'Aceito' && mb_strtolower((string) $t['evento']) === 'aceitar');

    expect($temReenvio)->toBeTrue("DG-09 não tem a seta 'Expirado --> Pendente : reenviar'")
        ->and($temAceite)->toBeTrue("DG-09 não tem a seta 'Pendente --> Aceito : aceitar'");
});

/*
|--------------------------------------------------------------------------
| R45 — CT-89: com a tenancy, os dois ramos do aceite ligam a organizacao do convite
|--------------------------------------------------------------------------
*/

it('[CT-89] com a tenancy, os dois ramos do aceite ligam a conta a organizacao do convite e dao o papel no contexto dela', function (string $particao, string $ramoPt, string $ramoEn): void {
    Notification::fake();

    $acme      = tenant('Acme', 'acme');
    $panelUser = Role::findByName('panel_user');

    $convite = ofertaPara('ana@example.com', $acme);
    $token   = $convite->enviar();

    if ($particao === 'sem conta com o e-mail') {
        Filament::setCurrentPanel('app');

        Livewire::withQueryParams(['token' => $token])
            ->test(RegistroPorConvite::class)
            ->fillForm([
                'name'                 => 'Ana',
                'password'             => 'segredo-bem-longo-123',
                'passwordConfirmation' => 'segredo-bem-longo-123',
            ])
            ->call('register')
            ->assertHasNoFormErrors();
    } else {
        $ana = usuario('ana@example.com');

        $this->actingAs($ana)
            ->get("/app/register?token={$token}")
            ->assertRedirectContains('/app/acme');
    }

    $anaConta = User::whereRaw('lower(email) = ?', ['ana@example.com'])->firstOrFail();

    expect($anaConta->tenants()->whereKey($acme->id)->exists())->toBeTrue();

    $this->assertDatabaseHas(pivotDePapeis(), [
        'model_id' => $anaConta->id,
        'role_id'  => $panelUser->getKey(),
        'team_id'  => $acme->id,
    ]);

    // QA-09: o marcador procurado em CADA bloco é o do IDIOMA DELE — nunca o pt nos dois. A
    // guarda de antes procurava o marcador pt também no en (`ramo` único), e o en passava com
    // português dentro ("(conta nova)"/"conta existente" em docs/en/autenticacao/convites.md,
    // que R61/CT-132 recusa). "organiza" cobre "organização" e "organization" nos dois idiomas.
    $ramoPorIdioma = ['pt' => $ramoPt, 'en' => $ramoEn];

    foreach (['pt', 'en'] as $idioma) {
        $bloco = blocoDoCatalogoNaArvore('DG-07', $idioma);

        expect($bloco)->not->toBeNull("DG-07 não encontrado em nenhum arquivo {$idioma} (README ou docs/{$idioma})");

        $normalizar = static fn (string $s): string => mb_strtolower(str_replace(['á', 'ã', 'â', 'é', 'ê', 'í', 'ó', 'ô', 'õ', 'ú', 'ç'], ['a', 'a', 'a', 'e', 'e', 'i', 'o', 'o', 'o', 'u', 'c'], $s));
        $marcador   = $normalizar($ramoPorIdioma[$idioma]);
        $linhas     = explode("\n", $bloco['bloco']);

        $trecho = null;

        foreach ($linhas as $i => $linha) {
            if (str_contains($normalizar($linha), $marcador)) {
                $trecho = implode("\n", array_slice($linhas, $i, 8));

                break;
            }
        }

        expect($trecho)->not->toBeNull("DG-07 não nomeia o ramo \"{$ramoPorIdioma[$idioma]}\" ({$idioma})");
        test()->assertStringContainsString('organiza', $normalizar((string) $trecho), "DG-07, ramo \"{$ramoPorIdioma[$idioma]}\" ({$idioma}), não menciona a ligação à organização do convite");
    }
})->with([
    'conta nova'      => ['sem conta com o e-mail', 'conta nova', 'new account'],
    'conta existente' => ['conta existente de ana, autenticada como ela', 'conta existente', 'existing account'],
]);

/*
|--------------------------------------------------------------------------
| R51/R52 — CT-112, CT-113, CT-114: DG-20, a requisição em /app/{tenant}
|--------------------------------------------------------------------------
| `mensagensDeSequencia()` (tests/Pest.php) devolve as MENSAGENS de um bloco `sequenceDiagram`,
| na ordem em que aparecem — mas ignora as linhas de controle (`alt`/`else`/`end`), então não
| basta para saber se uma mensagem está DENTRO do ramo que permite ou fora do bloco `alt`
| inteiro. O que é local a este arquivo é só essa leitura de POSIÇÃO — nenhum outro arquivo
| confere ramo de `alt` de um `sequenceDiagram` (.ai/rules/testes.md §"Helper de teste usado por
| mais de um arquivo").
*/

// `ordemDg20EhCorreta()` mora em `tests/Pest.php` — dois arquivos a usam (este e
// `tests/Kit/GuardasDosDiagramasTest.php`, QA-07, `.ai/rules/testes.md` §"Nunca crie um clone com
// outro nome"). A versão canônica já traz as duas condições que este arquivo precisa: a ORDEM
// (`identify_tenant->>can_access_tenant` antes de `identify_tenant->>definir_tenant`) e o
// ANINHAMENTO (a segunda dentro do ramo `else` do `alt`, entre a linha `else` e o `end` que o
// fecha) — as duas são necessárias: a primeira mata a ordem invertida (R51/M2, primeira cópia de
// CT-112); a segunda mata o contexto fixado também fora do ramo que nega (R51/M2, segunda cópia).

/**
 * A linha da mensagem `identify_tenant->>definir_tenant` do bloco real — para as duas cópias
 * abaixo, que a MOVEM (nunca reescrevem o texto à mão, para não divergir se o rótulo mudar).
 */
function linhaDeDefinirTenant(string $bloco): string
{
    foreach (explode("\n", $bloco) as $linha) {
        if (preg_match('/^\s*identify_tenant\s*-{1,2}>>\s*definir_tenant\s*:/', trim($linha)) === 1) {
            return $linha;
        }
    }

    throw new RuntimeException('DG-20: linha "identify_tenant->>definir_tenant" não encontrada no bloco real — o rótulo mudou?');
}

/** Cópia EM MEMÓRIA do DG-20 real com a mensagem a definir_tenant movida para ANTES da consulta a can_access_tenant — nunca escrita em disco (CT-112, 2ª linha de exemplos). */
function dg20ComDefinirAntesDaConsulta(string $blocoReal): string
{
    $linhaDefinir = linhaDeDefinirTenant($blocoReal);
    $linhas       = array_values(array_filter(
        explode("\n", $blocoReal),
        static fn (string $linha): bool => trim($linha) !== trim($linhaDefinir),
    ));

    $idxConsulta = null;

    foreach ($linhas as $i => $linha) {
        if (preg_match('/^\s*identify_tenant\s*-{1,2}>>\s*can_access_tenant\s*:/', trim($linha)) === 1) {
            $idxConsulta = $i;

            break;
        }
    }

    if ($idxConsulta === null) {
        throw new RuntimeException('DG-20: linha "identify_tenant->>can_access_tenant" não encontrada no bloco real — o rótulo mudou?');
    }

    array_splice($linhas, $idxConsulta, 0, [$linhaDefinir]);

    return implode("\n", $linhas);
}

/** Cópia EM MEMÓRIA do DG-20 real com a mensagem a definir_tenant movida para DEPOIS do `end` que fecha o alt — nunca escrita em disco (CT-112, 3ª linha de exemplos). */
function dg20ComDefinirForaDoAltDepoisDoEnd(string $blocoReal): string
{
    $linhaDefinir = linhaDeDefinirTenant($blocoReal);
    $linhas       = array_values(array_filter(
        explode("\n", $blocoReal),
        static fn (string $linha): bool => trim($linha) !== trim($linhaDefinir),
    ));

    $idxEnd = null;

    foreach ($linhas as $i => $linha) {
        if (trim($linha) === 'end') {
            $idxEnd = $i;
        }
    }

    if ($idxEnd === null) {
        throw new RuntimeException('DG-20: linha "end" não encontrada no bloco real — o bloco mudou de forma?');
    }

    array_splice($linhas, $idxEnd + 1, 0, [$linhaDefinir]);

    return implode("\n", $linhas);
}

it('[CT-112] a ordem que o DG-20 desenha e a da pilha de middlewares de uma rota do /app/{tenant}', function (string $variante, bool $resultadoEsperado): void {
    $rota = Route::getRoutes()->getByName('filament.app.pages.dashboard');

    expect($rota)->not->toBeNull('rota filament.app.pages.dashboard não encontrada — o painel app não tem mais uma rota com {tenant}?');

    $pilha       = $rota->gatherMiddleware();
    $idxIdentify = array_search(IdentifyTenant::class, $pilha, true);
    $idxDefinir  = array_search(DefinirTenantDePermissoes::class, $pilha, true);

    expect($idxIdentify)->not->toBeFalse('IdentifyTenant não está na pilha de middlewares REAL da rota /app/{tenant}')
        ->and($idxDefinir)->not->toBeFalse('DefinirTenantDePermissoes não está na pilha de middlewares REAL da rota /app/{tenant}')
        ->and($idxIdentify)->toBeLessThan($idxDefinir, 'na pilha REAL, IdentifyTenant não vem antes de DefinirTenantDePermissoes');

    expect(Filament::getPanel('app')->getTenantMiddleware())->toBe(
        [IdentifyTenant::class, DefinirTenantDePermissoes::class],
        'getTenantMiddleware() do painel app não é exatamente [IdentifyTenant, DefinirTenantDePermissoes], nessa ordem',
    );

    foreach (['pt', 'en'] as $idioma) {
        $bloco = blocoDoCatalogoNaArvore('DG-20', $idioma);

        expect($bloco)->not->toBeNull("DG-20 não encontrado em nenhum arquivo {$idioma} (README ou docs/{$idioma})");

        $texto = match ($variante) {
            'real'                    => $bloco['bloco'],
            'ordem invertida'         => dg20ComDefinirAntesDaConsulta($bloco['bloco']),
            'fora do alt, apos o end' => dg20ComDefinirForaDoAltDepoisDoEnd($bloco['bloco']),
        };

        expect(ordemDg20EhCorreta($texto))->toBe(
            $resultadoEsperado,
            "DG-20 ({$idioma}, variante '{$variante}'): esperado '".($resultadoEsperado ? 'aceita' : 'recusa')."', a guarda discordou",
        );
    }
})->with([
    'DG-20 real, pt e en'                                                              => ['real', true],
    'cópia com identify_tenant->>definir_tenant antes da consulta a can_access_tenant' => ['ordem invertida', false],
    'cópia com a mensagem a definir_tenant fora do alt, depois do end'                 => ['fora do alt, apos o end', false],
]);

it('[CT-113] o contexto de papeis que o DG-20 desenha e fixado com o id da organizacao da rota, e so no pedido permitido', function (string $slug, int $status, bool $teamIdEhDaAcme): void {
    $acme   = tenant('Acme', 'acme');
    $globex = tenant('Globex', 'globex');

    $operador = usuarioComPapel('panel_user', $acme);
    $operador->tenants()->attach($acme);

    $sentinela = 999999;
    expect($sentinela)->not->toBe($acme->id)->and($sentinela)->not->toBe($globex->id);

    $registrar = app(PermissionRegistrar::class);
    $registrar->setPermissionsTeamId($sentinela);

    $this->actingAs($operador)->get("/app/{$slug}")->assertStatus($status);

    $teamId = (int) app(PermissionRegistrar::class)->getPermissionsTeamId();

    if ($teamIdEhDaAcme) {
        expect($teamId)->toBe($acme->id, 'o DefinirTenantDePermissoes deveria ter fixado o id de time com o da acme, a organização da rota permitida');
    } else {
        expect($teamId)->not->toBe($globex->id, 'o pedido negado não pode fixar o id de time da organização que o operador não pode ver');
    }
})->with([
    'acme (permitido)'   => ['acme', 200, true],
    'globex (negado)'    => ['globex', 404, false],
]);

/**
 * A condição do `alt` do DG-20 ("organização inativa ou sem vínculo", ou a forma que o bloco
 * publicado tiver) cobre esta situação concreta? Reconhece os dois motivos pela FORMA — livre de
 * acento e caixa, pt/en — e não por uma lista fixa de blocos: "organização inativa"/"inactive
 * organization", e "sem vínculo"/"no link". Só quando o rótulo QUALIFICA o segundo motivo com a
 * exceção do `master_global` ("... e não é master_global"/"... and not master_global") essa
 * exceção retira o `master_global` do motivo "sem vínculo" — sem a exceção no rótulo, "sem
 * vínculo" cobre todo mundo sem vínculo, inclusive o `master_global` (R52/M2, achado contra o
 * bloco publicado hoje: Q?2).
 */
function condicaoDoAltDoDg20Cobre(string $bloco, bool $organizacaoInativa, bool $semVinculo, bool $ehMasterGlobal): bool
{
    $normalizar = static fn (string $s): string => mb_strtolower(str_replace(
        ['á', 'ã', 'â', 'é', 'ê', 'í', 'ó', 'ô', 'õ', 'ú', 'ç'],
        ['a', 'a', 'a', 'e', 'e', 'i', 'o', 'o', 'o', 'u', 'c'],
        $s,
    ));

    $condicao = null;

    foreach (explode("\n", $bloco) as $linha) {
        if (preg_match('/^\s*alt\s+(.*)$/', $linha, $m) === 1) {
            $condicao = $normalizar($m[1]);

            break;
        }
    }

    if ($condicao === null) {
        throw new RuntimeException('DG-20: nenhuma linha "alt ..." encontrada no bloco.');
    }

    $motivoInativa    = str_contains($condicao, 'inativ') || str_contains($condicao, 'inactive');
    $motivoSemVinculo = str_contains($condicao, 'sem vinculo') || str_contains($condicao, 'no link');
    $excetuaMaster    = str_contains($condicao, 'master_global') || str_contains($condicao, 'master global');

    $cobreOrgInativa  = $motivoInativa && $organizacaoInativa;
    $cobreSemVinculo  = $motivoSemVinculo && $semVinculo && ! ($ehMasterGlobal && $excetuaMaster);

    return $cobreOrgInativa || $cobreSemVinculo;
}

it('[CT-114] o ramo do DG-20 que cobre a situacao leva ao desfecho que o GET produz', function (string $situacaoOrg, string $persona, string $vinculo, int $status): void {
    $acme   = tenant('Acme', 'acme');
    $globex = tenant('Globex', 'globex', ativo: $situacaoOrg === 'ativa');

    if ($persona === 'master_global') {
        $operador = usuarioComPapel('master_global');
    } else {
        $operador = usuario();
        papelNaOrganizacao($operador, 'panel_user', $acme);

        if ($vinculo === 'com vinculo') {
            papelNaOrganizacao($operador, 'panel_user', $globex);
            $operador->tenants()->attach([$acme->id, $globex->id]);
        } else {
            $operador->tenants()->attach($acme);
        }
    }

    $this->actingAs($operador)->get("/app/{$globex->slug}")->assertStatus($status);

    $organizacaoInativa = $situacaoOrg === 'inativa';
    $semVinculo         = $vinculo === 'sem vinculo';
    $ehMasterGlobal     = $persona === 'master_global';

    foreach (['pt', 'en'] as $idioma) {
        $bloco = blocoDoCatalogoNaArvore('DG-20', $idioma);

        expect($bloco)->not->toBeNull("DG-20 não encontrado em nenhum arquivo {$idioma} (README ou docs/{$idioma})");

        $cobre = condicaoDoAltDoDg20Cobre($bloco['bloco'], $organizacaoInativa, $semVinculo, $ehMasterGlobal);

        expect($cobre)->toBe(
            $status === 404,
            "DG-20 ({$idioma}): a condição do alt ".($status === 404 ? 'deveria cobrir' : 'não deveria cobrir')." a situação '{$situacaoOrg} × {$persona} × {$vinculo}' (GET real respondeu {$status})",
        );
    }
})->with([
    'ativa × panel_user da acme e da globex × com vinculo'   => ['ativa', 'panel_user', 'com vinculo', 200],
    'ativa × panel_user so da acme × sem vinculo'            => ['ativa', 'panel_user', 'sem vinculo', 404],
    'inativa × panel_user da acme e da globex × com vinculo' => ['inativa', 'panel_user', 'com vinculo', 404],
    'inativa × master_global × sem vinculo'                  => ['inativa', 'master_global', 'sem vinculo', 404],
    'ativa × master_global × sem vinculo'                    => ['ativa', 'master_global', 'sem vinculo', 200],
]);
