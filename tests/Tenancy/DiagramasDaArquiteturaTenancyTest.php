<?php

use App\Filament\Admin\Resources\Convites\Pages\ListConvites;
use App\Filament\App\Pages\ConvitesRecebidos;
use App\Filament\Pages\Auth\RegistroPorConvite;
use App\Models\Convite;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\PapeisSeeder;
use Database\Seeders\ShieldPermissionsSeeder;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Filament\Panel;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

/**
 * Os CT do lote `T` (Tenancy) da wiki `diagramas-da-arquitetura`, ver
 * `wikis/specs/feat/diagramas-da-arquitetura/diagramas-da-arquitetura/04-casos-de-teste.md`:
 * CT-12, CT-14, CT-80, CT-89, CT-98 — os únicos do `## Índice de Cenários` cujo arquivo é este
 * (`.ai/rules/testes.md` §"Nem todo papel do kit existe em toda suíte": `admin_app` só existe
 * aqui).
 *
 * **Os diagramas (README, `docs/`, site) ainda NÃO EXISTEM neste commit** — outro lote os
 * constrói depois, lendo estes testes. Todo cenário que afirma conteúdo de DG fica VERMELHO
 * por "bloco não encontrado", de propósito: é o sinal de que a implementação ainda não chegou,
 * não um teste quebrado. A parte de cada cenário que executa CÓDIGO REAL (canAccessPanel(), a
 * máquina de estados do convite pelos pontos de entrada) roda e vale hoje, com ou sem diagrama.
 */
beforeEach(function (): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);
});

/*
|--------------------------------------------------------------------------
| Extração de blocos Mermaid — genérica para flowchart/graph e state diagram
|--------------------------------------------------------------------------
| Não existia NENHUM diagrama Mermaid no repositório antes deste lote, então a convenção de
| sintaxe abaixo é uma HIPÓTESE assumida por este arquivo para quem for desenhar o DG depois —
| documentada aqui porque é a primeira vez que alguém precisa fixá-la. Local a este arquivo
| (não a tests/Pest.php): CT-11/CT-13/CT-71/CT-21/CT-61/CT-81/CT-83/CT-88 (lote Kit) fazem a
| MESMA leitura de DG-02/DG-03/DG-09/DG-07 — ver a pendência reportada ao orquestrador sobre
| duplicação entre os dois arquivos.
*/

/**
 * O bloco Mermaid do catálogo `$idCatalogo`, num idioma — ou `null` se nenhum arquivo o afirma.
 *
 * @return array{bloco: string, linha: int, idCatalogo: ?string, dentroDeComentarioHtml: bool, arquivo: string, idioma: string}|null
 */
function blocoDoCatalogo(string $idCatalogo, string $idioma): ?array
{
    foreach (blocosMermaidDaArvore($idioma) as $bloco) {
        if ($bloco['idCatalogo'] === $idCatalogo && ! $bloco['dentroDeComentarioHtml']) {
            return $bloco;
        }
    }

    return null;
}

/**
 * Nós (id => rótulo) e arestas de um bloco Mermaid `flowchart`/`graph`.
 *
 * Convenção assumida: um "shape" de nó (`id[Texto]`, `id(Texto)`, `id{Texto}`,
 * `id{{Texto}}`, `id((Texto))`, `id([Texto])`, `id[[Texto]]`) pode aparecer em QUALQUER linha —
 * o rótulo de um id é lido da primeira vez que ele aparece com shape, em lugar nenhum
 * específico. O rótulo de uma ARESTA vem do `-->|Rótulo|` entre as duas pontas.
 *
 * @return array{nos: array<string, string>, arestas: list<array{de: string, para: string, rotulo: ?string, linha: int}>}
 */
function grafoDoFluxo(string $bloco): array
{
    $nos = [];

    preg_match_all(
        '/\b([A-Za-z0-9_]+)\s*(?|\{\{\s*(.*?)\s*\}\}|\(\(\s*(.*?)\s*\)\)|\(\[\s*(.*?)\s*\]\)|\[\[\s*(.*?)\s*\]\]|\[\s*(.*?)\s*\]|\(\s*(.*?)\s*\)|\{\s*(.*?)\s*\})/',
        $bloco,
        $achados,
        PREG_SET_ORDER,
    );

    foreach ($achados as $achado) {
        if (! isset($nos[$achado[1]])) {
            $nos[$achado[1]] = trim($achado[2], " \t\"'");
        }
    }

    $arestas = [];

    foreach (explode("\n", $bloco) as $i => $linha) {
        if (preg_match(
            '/^\s*([A-Za-z0-9_]+)\s*(?:\{\{.*?\}\}|\(\(.*?\)\)|\(\[.*?\]\)|\[\[.*?\]\]|\[.*?\]|\(.*?\)|\{.*?\})?\s*(?:--[-.]*>|==>)\s*(?:\|([^|]*)\|)?\s*([A-Za-z0-9_]+)/',
            $linha,
            $m,
        ) === 1) {
            $arestas[] = [
                'de'     => $m[1],
                'para'   => $m[3],
                'rotulo' => ($m[2] ?? '') !== '' ? trim($m[2], " \t\"'") : null,
                'linha'  => $i + 1,
            ];
        }
    }

    foreach ($arestas as $aresta) {
        $nos[$aresta['de']] ??= $aresta['de'];
        $nos[$aresta['para']] ??= $aresta['para'];
    }

    return ['nos' => $nos, 'arestas' => $arestas];
}

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
 * Executa um grafo de DECISÃO (DG-03) a partir do nó sem aresta de entrada, avaliando cada nó
 * pelo PREDICADO REAL (nunca por comparação textual de ordem — mata R7.M5), e devolve o rótulo
 * terminal normalizado ("entra"/"nega").
 *
 * @param  array{nos: array<string, string>, arestas: list<array{de: string, para: string, rotulo: ?string, linha: int}>}  $grafo
 * @param  callable(string): ?bool  $avaliar  null quando o texto do nó não é uma pergunta reconhecida
 *
 * @throws RuntimeException quando o grafo não tem a forma esperada (sem início, laço, pergunta
 *                          não reconhecida ou aresta sem rótulo Sim/Não do lado decidido)
 */
function resultadoDoFluxo(array $grafo, callable $avaliar): string
{
    $comEntrada = array_unique(array_column($grafo['arestas'], 'para'));
    $atual      = null;

    foreach (array_keys($grafo['nos']) as $id) {
        if (! in_array($id, $comEntrada, true)) {
            $atual = $id;

            break;
        }
    }

    if ($atual === null) {
        throw new RuntimeException('DG-03: nenhum nó sem aresta de entrada — não há por onde começar a caminhada.');
    }

    $visitados = [];

    while (true) {
        if (isset($visitados[$atual])) {
            throw new RuntimeException("DG-03: laço encontrado no nó '{$atual}'.");
        }

        $visitados[$atual]  = true;
        $texto              = $grafo['nos'][$atual] ?? $atual;

        if (preg_match('/\bentra\b/i', $texto) === 1) {
            return 'entra';
        }

        if (preg_match('/\bnega\b/i', $texto) === 1) {
            return 'nega';
        }

        $saidas = array_values(array_filter($grafo['arestas'], static fn (array $a): bool => $a['de'] === $atual));

        if ($saidas === []) {
            throw new RuntimeException("DG-03: nó '{$texto}' ({$atual}) não é terminal (\"entra\"/\"nega\") e não tem aresta de saída.");
        }

        if (count($saidas) === 1) {
            $atual = $saidas[0]['para'];

            continue;
        }

        $resultado = $avaliar($texto);

        if ($resultado === null) {
            throw new RuntimeException("DG-03: pergunta não reconhecida pela guarda: \"{$texto}\".");
        }

        $alvo = null;

        foreach ($saidas as $saida) {
            $rotulo = mb_strtolower((string) $saida['rotulo']);
            $ehSim  = str_contains($rotulo, 'sim') || str_contains($rotulo, 'yes') || $rotulo === 'true';
            $ehNao  = str_contains($rotulo, 'não') || str_contains($rotulo, 'nao') || str_contains($rotulo, 'no') || $rotulo === 'false';

            if (($resultado && $ehSim) || (! $resultado && $ehNao)) {
                $alvo = $saida['para'];

                break;
            }
        }

        if ($alvo === null) {
            $ladoEsperado = $resultado ? 'sim' : 'não';

            throw new RuntimeException("DG-03: nó '{$texto}' não tem aresta rotulada para o lado \"{$ladoEsperado}\".");
        }

        $atual = $alvo;
    }
}

/**
 * O avaliador das perguntas de DG-03 — a MESMA ordem de `User::canAccessPanel()`
 * (`app/Models/User.php:canAccessPanel:156`): indisponibilidade, pendência, master_global,
 * contexto do painel (tenancy), papel do painel.
 */
function avaliadorDoDG03(User $user, Panel $painel): Closure
{
    return function (string $texto) use ($user, $painel): ?bool {
        $t = mb_strtolower($texto);

        return match (true) {
            str_contains($t, 'indispon')                                           => $user->motivoDeIndisponibilidade() !== null,
            str_contains($t, 'pendente')                                           => (bool) $user->aprovacao_pendente,
            str_contains($t, 'master_global') || str_contains($t, 'master global') => $user->isMasterGlobal(),
            str_contains($t, 'tenancy')                                            => $painel->hasTenancy(),
            str_contains($t, 'papel') && str_contains($t, 'painel')                => $user->temPapelDoPainel(
                $painel->getId(),
                $painel->hasTenancy() ? null : (config('permission.teams') ? Tenant::CONTEXTO_GLOBAL : null),
            ),
            default => null,
        };
    };
}

/**
 * O prefixo de entidade de permissão (`Action:Entidade`, Shield) do caso de uso do DG-02 — mapa
 * de MECANISMO, escolhido pela guarda (R6 é EP, não a matriz fechada de R44/CT-83).
 */
function entidadeDoCasoDeUso(string $rotulo): ?string
{
    $r = mb_strtolower($rotulo);

    return match (true) {
        str_contains($r, 'usuár') || str_contains($r, 'usuar') => 'User',
        str_contains($r, 'convite')                            => 'Convite',
        default                                                => null,
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
        $bloco = blocoDoCatalogo('DG-02', $idioma);

        expect($bloco)->not->toBeNull("DG-02 não encontrado em nenhum arquivo {$idioma} (README ou docs/{$idioma})");

        $grafo   = grafoDoFluxo($bloco['bloco']);
        $arestas = arestasDeOrigem($grafo, 'admin_app');

        expect($arestas)->not->toBeEmpty("admin_app sem nenhuma aresta no DG-02 ({$idioma})");

        $destinos = array_map(static fn (array $a): string => mb_strtolower((string) $a['destinoTexto']), $arestas);

        expect(collect($destinos)->contains(fn (string $d): bool => str_contains($d, 'usuár') || str_contains($d, 'usuar')))
            ->toBeTrue("admin_app não se liga a \"gerir usuários\" no DG-02 ({$idioma})");
        expect(collect($destinos)->contains(fn (string $d): bool => str_contains($d, 'convite')))
            ->toBeTrue("admin_app não se liga a \"gerir convites\" no DG-02 ({$idioma})");

        foreach ($arestas as $aresta) {
            $entidade = entidadeDoCasoDeUso((string) $aresta['destinoTexto']);

            expect($entidade)->not->toBeNull("caso de uso de admin_app sem entidade mapeada no DG-02 ({$idioma}): \"{$aresta['destinoTexto']}\"");

            $temAlgumaPermissaoDaEntidade = collect($papel->permissions->pluck('name'))
                ->contains(fn (string $p): bool => str_ends_with($p, ":{$entidade}"));

            expect($temAlgumaPermissaoDaEntidade)
                ->toBeTrue("admin_app não tem nenhuma permissão de {$entidade}, mas o DG-02 o liga a \"{$aresta['destinoTexto']}\" ({$idioma})");
        }

        expect($bloco['bloco'])->toContain('KIT_TENANCY', "o bloco DG-02 não nomeia KIT_TENANCY junto de admin_app ({$idioma})");
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

    $bloco = blocoDoCatalogo('DG-03', 'pt');

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

    if ($combo === 'admin global + admin_app acme') {
        papelNaOrganizacao($user, 'admin');
        papelNaOrganizacao($user, 'admin_app', $acme);
    } else {
        papelNaOrganizacao($user, 'infra');
        papelNaOrganizacao($user, 'admin', $acme);
    }

    $painel = Filament::getPanel($painelId);

    expect($user->canAccessPanel($painel))->toBe(
        $desfecho === 'entra',
        "canAccessPanel() real diverge do esperado ({$desfecho}) para o combo '{$combo}', painel {$painelId}",
    );

    $bloco = blocoDoCatalogo('DG-03', 'pt');

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
]);

/*
|--------------------------------------------------------------------------
| R13 — CT-80: a matriz fechada do convite (4 estados x 6 eventos), pelo ponto de entrada
|--------------------------------------------------------------------------
*/

/** Transições `de --> para : evento` de um bloco Mermaid `stateDiagram-v2` (sintaxe canônica). */
function transicoesDoEstado(string $bloco): array
{
    $transicoes = [];

    foreach (explode("\n", $bloco) as $i => $linha) {
        if (preg_match('/^\s*(\[\*\]|[A-Za-z0-9_]+)\s*-->\s*(\[\*\]|[A-Za-z0-9_]+)\s*(?::\s*(.+))?$/', $linha, $m) === 1) {
            $transicoes[] = [
                'de'     => $m[1],
                'para'   => $m[2],
                'evento' => isset($m[3]) ? trim($m[3]) : null,
                'linha'  => $i + 1,
            ];
        }
    }

    return $transicoes;
}

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
        $bloco = blocoDoCatalogo('DG-09', $idioma);

        expect($bloco)->not->toBeNull("DG-09 não encontrado em nenhum arquivo {$idioma} (README ou docs/{$idioma})");

        if ($idioma !== 'pt') {
            // A tradução do rótulo do evento/estado para EN é fixada pelo lote Kit (R43,
            // CT-86) — aqui confere-se só a PARIDADE ESTRUTURAL (mesmo total de arestas que o
            // bloco pt), não o texto.
            $blocoPt = blocoDoCatalogo('DG-09', 'pt');

            expect(transicoesDoEstado($bloco['bloco']))->toHaveCount(
                count(transicoesDoEstado($blocoPt['bloco'])),
                'DG-09 en não tem o mesmo número de transições que o DG-09 pt',
            );

            continue;
        }

        $transicoes = transicoesDoEstado($bloco['bloco']);

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

        expect(collect($daCelula)->pluck('para')->all())->toContain(
            $paraEsperado,
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

    $bloco = blocoDoCatalogo('DG-09', 'pt');

    expect($bloco)->not->toBeNull('DG-09 não encontrado em nenhum arquivo pt (README ou docs/pt)');

    $transicoes = transicoesDoEstado($bloco['bloco']);

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

it('[CT-89] com a tenancy, os dois ramos do aceite ligam a conta a organizacao do convite e dao o papel no contexto dela', function (string $particao, string $ramo): void {
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

    foreach (['pt', 'en'] as $idioma) {
        $bloco = blocoDoCatalogo('DG-07', $idioma);

        expect($bloco)->not->toBeNull("DG-07 não encontrado em nenhum arquivo {$idioma} (README ou docs/{$idioma})");

        $normalizar = static fn (string $s): string => mb_strtolower(str_replace(['á', 'ã', 'â', 'é', 'ê', 'í', 'ó', 'ô', 'õ', 'ú', 'ç'], ['a', 'a', 'a', 'e', 'e', 'i', 'o', 'o', 'o', 'u', 'c'], $s));
        $marcador   = $normalizar($ramo);
        $linhas     = explode("\n", $bloco['bloco']);

        $trecho = null;

        foreach ($linhas as $i => $linha) {
            if (str_contains($normalizar($linha), $marcador)) {
                $trecho = implode("\n", array_slice($linhas, $i, 8));

                break;
            }
        }

        expect($trecho)->not->toBeNull("DG-07 não nomeia o ramo \"{$ramo}\" ({$idioma})");
        expect($normalizar((string) $trecho))->toContain('organiza', "DG-07, ramo \"{$ramo}\" ({$idioma}), não menciona a ligação à organização do convite");
    }
})->with([
    'conta nova'      => ['sem conta com o e-mail', 'conta nova'],
    'conta existente' => ['conta existente de ana, autenticada como ela', 'conta existente'],
]);
