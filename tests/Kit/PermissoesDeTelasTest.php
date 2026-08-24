<?php

use App\Filament\Concerns\ExigePermissaoDaTela;
use App\Filament\Infra\Pages\HubDeInfraestrutura;
use BezhanSalleh\FilamentShield\Facades\FilamentShield;
use Database\Seeders\PapeisSeeder;
use Database\Seeders\ShieldPermissionsSeeder;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

/**
 * A permissão `View:{Page}` passa a decidir o acesso à Page de painel.
 *
 * O que estes casos medem não é "existe permissão" — isso o Shield já fazia, e é o que tornava o
 * buraco invisível: `View:Pulse` estava no banco, aparecia como checkbox em `/admin/shield/roles`, e
 * desmarcá-la não mudava nada, porque o `canAccess()` default do Filament é `return true`
 * (`vendor/filament/filament/src/Pages/Concerns/CanAuthorizeAccess.php:17-24`).
 *
 * O par é sempre o mesmo: quem TEM a permissão entra, quem NÃO tem toma 403 — e o segundo lado é
 * arranjado revogando a permissão do papel real (`semAPermissao()`), nunca criando papel vazio, que
 * perderia o `canAccessPanel()` e faria o 403 vir da porta do painel.
 *
 * Ver `wikis/specs/feat/permissoes-de-telas-e-acoes/permissoes-de-telas-e-acoes/04-casos-de-teste.md`
 * (R1, R2 e R9).
 */
beforeEach(function (): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);
});

/*
|--------------------------------------------------------------------------
| R1 — a Page abre para quem tem a permissão dela e recusa quem não tem
|--------------------------------------------------------------------------
*/

/**
 * CT-01 — a pessoa com o papel do painel abre a tela.
 *
 * As três linhas são as três partições estruturais do conjunto de 5 Pages, não amostra de
 * conveniência: `HubDeInfraestrutura` é a Page SEM regra local, `Pulse` é a mais simples (sem regra
 * local e sem descoberta de cartões) e `HubDeAdministracao` é a Page COM regra local (a flag
 * `kit.hub`). `HubDoNegocio` e `ConvitesRecebidos` vivem em `tests/Tenancy` — aquele painel só tem
 * persona com contexto de organização.
 */
it('abre a tela para o papel que carrega a permissão dela', function (string $papel, string $rota, string $permissao, string $marca, bool $hub): void {
    config(['kit.hub' => $hub]);

    $usuario = usuarioDoKit($papel, "{$papel}@example.com");

    expect($usuario->can($permissao))->toBeTrue("o papel {$papel} deveria carregar {$permissao}");

    $this->actingAs($usuario)
        ->get($rota)
        ->assertSuccessful()
        ->assertSee($marca);
})->with([
    'hub sem flag' => ['infra', '/infra/hub-de-infraestrutura', 'View:HubDeInfraestrutura', 'Execuções de IA', false],
    'Page simples' => ['infra', '/infra/pulse', 'View:Pulse', 'Pulse', false],
    'hub com flag' => ['admin', '/admin/hub-de-administracao', 'View:HubDeAdministracao', 'Usuários', true],
]);

/**
 * CT-01 — os dois hubs do painel de negócio, com organização no contexto: `tests/Tenancy`.
 *
 * CT-02 — revogar a permissão fecha a tela para o MESMO papel.
 *
 * A segunda asserção é sobre a barra lateral **do painel do próprio papel**, e não de outro: dizer
 * que o papel `infra` não vê "Hub de administração" é trivialmente verdadeiro (ele não abre o
 * `/admin`) e ficaria verde com o item de menu escondido por um `hasRole()` paralelo à permissão —
 * o caso em que o item aparece para quem tem o papel, não tem a permissão, e leva a um 403 no
 * clique.
 */
it('fecha a tela e esconde o item de menu quando a permissão é revogada do papel', function (string $papel, string $rota, string $permissao, string $rotulo, string $painel, bool $hub): void {
    config(['kit.hub' => $hub]);

    semAPermissao($papel, $permissao);

    $this->actingAs(usuarioDoKit($papel, "{$papel}@example.com"));

    $this->get($rota)->assertForbidden();

    $this->get($painel)
        ->assertSuccessful()
        ->assertDontSee($rotulo);
})->with([
    'hub sem flag' => ['infra', '/infra/hub-de-infraestrutura', 'View:HubDeInfraestrutura', 'Central de infraestrutura', '/infra', false],
    'Page simples' => ['infra', '/infra/pulse', 'View:Pulse', 'Pulse', '/infra', false],
    'hub com flag' => ['admin', '/admin/hub-de-administracao', 'View:HubDeAdministracao', 'Hub de administração', '/admin', true],
    /*
     * As telas de PACOTE, uma por MECANISMO — não uma amostra de conveniência.
     *
     * Cada pacote fecha a tela de um jeito diferente, e o erro mais plausível de todos é
     * copiar a linha para três dos quatro e esquecer o quarto:
     *
     *   - saúde  → `FilamentSpatieLaravelHealthPlugin::authorize()`
     *   - grafo  → `DependencyGraphPlugin::canAccessUsing()`
     *   - lixeira→ `RevivePlugin::authorize()`
     *   - backups→ subclasse do kit (`App\Filament\Infra\Pages\BackupRunsPage`), porque o pacote
     *              não publica callback nenhum
     *
     * Ver ADR-01 e ADR-04 de
     * `wikis/specs/feat/permissoes-de-telas-de-pacote/permissoes-de-telas-de-pacote/`.
     *
     * `/infra/logs` e `/infra/meu-perfil` NÃO entram aqui, e não é esquecimento: o rótulo de
     * navegação deles ("Logs", "Perfil") aparece em outros itens da mesma barra lateral, então a
     * asserção `assertDontSee($rotulo)` seria falso-vermelho. Os dois estão no caso seguinte,
     * que assere só o 403.
     */
    'pacote com authorize()'      => ['infra', '/infra/health-check-results', 'View:HealthCheckResults', 'Saúde da aplicação', '/infra', false],
    'pacote com canAccessUsing()' => ['infra', '/infra/dependency-graph', 'View:DependencyGraphPage', 'Grafo de dependências', '/infra', false],
    'pacote com authorize() 2'    => ['infra', '/infra/recycle-bin', 'View:RecycleBin', 'Lixeira', '/infra', false],
    'pacote por subclasse'        => ['infra', '/infra/backup-runs', 'View:BackupRunsPage', 'Backups', '/infra', false],
]);

/**
 * CT-03 — o cartão do hub some quando a permissão do destino é revogada, e SÓ ele.
 *
 * `DescobreCardsDoPainel` filtra por `canAccess()` de cada destino, então esta é a célula que
 * nenhum outro caso cobre: a Page passa a consultar permissão e o efeito tem de chegar à grade de
 * cartões. A segunda asserção é o que mata "a grade ficou vazia" — sem ela, uma descoberta que
 * devolvesse nada passaria.
 */
it('tira do hub o cartão do destino cuja permissão foi revogada', function (): void {
    semAPermissao('infra', 'View:Pulse');

    $this->actingAs(usuarioDoKit('infra', 'infra@example.com'));

    Filament::setCurrentPanel('infra');

    Livewire::test(HubDeInfraestrutura::class)
        ->assertDontSee('Requisições e queries lentas')
        ->assertSee('Ledger das execuções de IA');
});

/**
 * CT-04 — o coringa do `Gate::before` atravessa a revogação, e só ele.
 *
 * **As duas linhas são o caso**, não duas amostras: a de `master_global` sozinha ficaria verde com
 * `canAccess()` devolvendo `true` incondicional, e é a de `infra` que distingue "o `Gate::before`
 * venceu" de "a Page não checa nada". Uma linha sem a outra não prova nada.
 *
 * (Ficaram em dataset, e não num `it()` só, porque dois `actingAs()` no mesmo caso deixam a sessão
 * do primeiro request valendo e o segundo responde 302 para o login — a asserção mediria a sessão,
 * não a permissão.)
 *
 * O caso também protege contra a checagem por `hasPermissionTo()` direto em vez de `can()`: aquela
 * ignora o `Gate::before` e trancaria o `master_global` fora do painel dele.
 */
it('deixa só o master_global entrar quando a permissão não está em papel nenhum', function (string $papel, int $status): void {
    semAPermissao('infra', 'View:Pulse');

    $this->actingAs(usuarioDoKit($papel, "{$papel}@example.com"))
        ->get('/infra/pulse')
        ->assertStatus($status);
})->with([
    'coringa do Gate::before' => ['master_global', 200],
    'papel de painel'         => ['infra', 403],
]);

/**
 * CT-26 — a permissão AUSENTE da tabela fecha a tela, em vez de abri-la.
 *
 * A partição que nenhum outro caso exercita: as suítes semeiam as permissões, então todos os
 * demais rodam com a tabela populada. Aqui a linha não existe — instalação sem seeder, permissão
 * apagada, `kit:install --custom`.
 *
 * O caminho errado plausível é uma guarda `if (! Permission::where('name', $chave)->exists())
 * return true;`, escrita para "não travar instalação nova", e ela passa em CT-01..CT-05 inteiros.
 *
 * A linha do `master_global` é o controle: sem ela, uma implementação que negasse a TODOS por erro
 * de resolução de chave também ficaria verde.
 */
it('nega a tela quando a permissão dela não existe no banco', function (string $papel, int $status): void {
    Permission::query()->where('name', 'View:Pulse')->delete();
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $this->actingAs(usuarioDoKit($papel, "{$papel}@example.com"))
        ->get('/infra/pulse')
        ->assertStatus($status);
})->with([
    'papel de painel'         => ['infra', 403],
    'coringa do Gate::before' => ['master_global', 200],
]);

/*
|--------------------------------------------------------------------------
| R2 — a regra local da Page e a permissão valem as duas
|--------------------------------------------------------------------------
*/

/**
 * CT-05 — a flag desligada fecha o hub para todos, inclusive para o `master_global`.
 *
 * A linha do `master_global` é a discriminante e o guarda de ADR-02 da wiki
 * `hub-de-cards-opcional`: ele vence PERMISSÃO pelo `Gate::before` e não vence CONFIG. Se alguém
 * "simplificar" trocando a flag pela permissão, é esta linha que fica vermelha.
 *
 * A segunda asserção é sobre o PAPEL ter a permissão, não sobre a linha existir no banco: "a
 * permissão existe" é o arranjo do `beforeEach` e seria tautologia. O que ADR-02 promete é que
 * desligar a flag não mexe na matriz.
 *
 * `expect(config('kit.hub'))->toBeFalse()` primeiro porque o `phpunit.xml:65` fixa `KIT_HUB=false`:
 * o caso mede o que o kit ENTREGA, não o que o teste arranjou.
 */
it('fecha o hub com a flag desligada sem mexer na matriz de permissões', function (string $papel): void {
    expect(config('kit.hub'))->toBeFalse();

    $this->actingAs(usuarioDoKit($papel, "{$papel}@example.com"))
        ->get('/admin/hub-de-administracao')
        ->assertForbidden();

    expect(papelDoKit('admin')->hasPermissionTo('View:HubDeAdministracao'))->toBeTrue();
})->with([
    'papel de painel'         => ['admin'],
    'coringa do Gate::before' => ['master_global'],
]);

/*
|--------------------------------------------------------------------------
| R9 — nenhuma Page de painel do kit fica sem consultar a permissão dela
|--------------------------------------------------------------------------
*/

/**
 * CT-21 — toda Page de painel escrita no kit usa o concern.
 *
 * Enforço estrutural, e ele existe porque CT-01..CT-05 cobrem 3 Pages de 5: escrever o par
 * tem/não-tem para as outras 21 superfícies (2 Pages + 19 Widgets) seria burocracia abandonada, e
 * `.ai/rules/specs.md` manda preferir enforço automático a prosa.
 *
 * **Este caso fica vermelho quando alguém cria uma Page de painel nova sem o concern**, e a
 * mensagem diz qual classe. É o comportamento pedido, não falso positivo: a cláusula do requisito é
 * "TODAS as telas [...] precisa ter sua permissão especifica".
 *
 * A lista sai do sistema de arquivos, e não escrita à mão, porque escrita à mão ela não pega classe
 * nova — que é justamente o que o caso serve para pegar.
 */
it('não deixa nenhuma Page de painel do kit sem o concern de permissão', function (): void {
    $sem = collect(paginasDePainelDoKit())
        ->keys()
        ->reject(fn (string $classe): bool => in_array(
            ExigePermissaoDaTela::class,
            class_uses_recursive($classe),
            true,
        ))
        ->values()
        ->all();

    expect($sem)->toBe([], 'Pages de painel sem ExigePermissaoDaTela: '.implode(', ', $sem));
});

/**
 * CT-23 — a checagem é observável, e não só declarada.
 *
 * O par comportamental de CT-21, e o que CT-21 **não** pega: `use ExigePermissaoDaTela;` numa classe
 * que sobrescreva `canAccess()` é no-op silencioso, porque método de classe vence método de trait.
 * Aqui o oráculo é o comportamento — nenhum nome de trait é mencionado —, então o caso sobrevive a
 * uma renomeação e mata o `use` inerte.
 *
 * Percorrer todas as classes NO MESMO PROCESSO também cobre a memoização estática de
 * `HasPageShield::$pagePermissionKey`: uma implementação que a compartilhasse entre classes daria a
 * decisão da primeira Page a todas as seguintes.
 *
 * A flag `kit.hub` fica LIGADA de propósito: com ela desligada os dois hubs opcionais responderiam
 * `false` pela regra local, e o caso ficaria verde sem provar nada sobre permissão.
 */
it('faz cada Page de painel do kit negar acesso a quem não tem permissão nenhuma', function (): void {
    config(['kit.hub' => true]);

    $this->actingAs(usuarioCom(null));

    $abertas = [];

    foreach (paginasDePainelDoKit() as $classe => $painel) {
        noPainelDoShield($painel);

        if ($classe::canAccess()) {
            $abertas[] = $classe;
        }
    }

    expect($abertas)->toBe([], 'Pages que abrem para usuário sem permissão: '.implode(', ', $abertas));
});

/**
 * CT-24 — a Page de PACOTE consulta a permissão, e a permissão continua selecionável.
 *
 * ## A inversão, e por que o caso não foi apagado
 *
 * Até a 0.19.3 este caso afirmava o OPOSTO: `assertSuccessful()` com a permissão revogada, porque
 * ADR-05 da wiki `permissoes-de-telas-e-acoes` havia declarado as telas de pacote fora de escopo.
 * O docblock de lá dizia, por escrito, que ele ficaria vermelho no dia em que alguém fechasse a
 * lacuna de verdade. Ficou. Ver ADR-01 de
 * `wikis/specs/feat/permissoes-de-telas-de-pacote/permissoes-de-telas-de-pacote/`.
 *
 * O ORÁCULO é o mesmo, com o sinal trocado — e é exatamente a repro que o requisito nomeia:
 * *"revogar `View:LogsExplorer` do papel `infra` e abrir /infra/logs responde 200"*. Agora
 * responde 403.
 *
 * ## A segunda asserção não mudou, e é a que importa preservar
 *
 * A permissão continua EXISTINDO na tabela. Ela mantém vermelha a "correção" errada de pôr a
 * classe em `config('filament-shield.pages.exclude')` para o checkbox parar de mentir: aquilo
 * removeria a alavanca do banco em vez de ligá-la.
 *
 * ## Por que estas duas rotas
 *
 * `/infra/logs` é a repro literal do requisito, e é a única tela em que a permissão convive com um
 * gate (`ver-logs`) — o `&&` do `InfraPanelProvider`. `/{painel}/meu-perfil` é UMA classe
 * registrada em TRÊS painéis com UMA permissão só, e é a única linha capaz de falsificar um helper
 * que resolvesse a chave pelo painel errado: `FilamentShield::getPages()` é memoizado por
 * instância, e um request é um painel só.
 */
it('faz a Page de pacote negar acesso sem a permissão, mantendo a permissão selecionável', function (string $papel, string $rota, string $permissao): void {
    semAPermissao($papel, $permissao);

    $this->actingAs(usuarioDoKit($papel, "{$papel}@example.com"))
        ->get($rota)
        ->assertForbidden();

    expect(Permission::query()->where('name', $permissao)->exists())->toBeTrue();
})->with([
    'a repro do requisito'     => ['infra', '/infra/logs', 'View:LogsExplorer'],
    'uma classe, painel infra' => ['infra', '/infra/meu-perfil', 'View:MyProfilePage'],
    'a mesma, painel admin'    => ['admin', '/admin/meu-perfil', 'View:MyProfilePage'],
]);

/**
 * CT-25 — o coringa e a permissão ausente do banco, no caminho das telas de PACOTE.
 *
 * CT-04 e CT-26 acima cobrem estas duas partições para as Pages do KIT, que decidem pela trait
 * `HasPageShield` do Shield. As telas de pacote decidem por outro código — o predicado
 * `App\Support\PermissaoDaTela`, chamado do callback de autorização de cada plugin —, e é ele que
 * este caso exercita.
 *
 * As quatro linhas são o produto de duas partições, e cada uma mata um mutante diferente:
 *
 *  - `revogada` × `master_global` mata "a checagem usa `hasPermissionTo()` em vez de `can()`",
 *    que ignora o `Gate::before` e trancaria o coringa fora do painel dele;
 *  - `revogada` × `infra` mata "o predicado devolve `true` sempre";
 *  - `apagada` × `infra` mata a guarda `if (! Permission::exists()) return true;`, escrita para
 *    "não travar instalação nova" e que passa em todos os outros casos deste arquivo;
 *  - `apagada` × `master_global` é o controle: sem ela, um erro de resolução de chave que negasse
 *    a TODOS também ficaria verde.
 *
 * `View:RecycleBin` e não `View:LogsExplorer`: a Lixeira é fechada por `RevivePlugin::authorize()`,
 * sem gate nenhum ao lado, então o oráculo mede só a permissão.
 */
it('nega a tela de pacote a quem não tem a permissão, tendo ela sido revogada ou apagada', function (string $arranjo, string $papel, int $status): void {
    if ($arranjo === 'apagada') {
        Permission::query()->where('name', 'View:RecycleBin')->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    } else {
        semAPermissao('infra', 'View:RecycleBin');
    }

    $this->actingAs(usuarioDoKit($papel, "{$papel}@example.com"))
        ->get('/infra/recycle-bin')
        ->assertStatus($status);
})->with([
    'revogada do papel'   => ['revogada', 'infra', 403],
    'revogada, coringa'   => ['revogada', 'master_global', 200],
    'apagada da tabela'   => ['apagada', 'infra', 403],
    'apagada, coringa'    => ['apagada', 'master_global', 200],
]);

/**
 * CT-27 — o inventário do que AINDA não consulta a permissão, no painel /infra.
 *
 * O substituto honesto do antigo CT-24, e o enforço que `.ai/rules/specs.md` pede no lugar de
 * prosa: em vez de uma lista escrita à mão de "telas fechadas" e "telas declaradas" — duas listas
 * para manter em sincronia com a wiki —, o caso PERGUNTA ao painel e compara com a única lista que
 * é uma decisão: as três telas da Central de comandos, declaradas em ADR-05.
 *
 * O arranjo é o que faz o caso discriminar: papel `infra` REAL, com todas as permissões de tela de
 * pacote revogadas. Assim `canAccessPanel()` continua valendo (usuário sem papel levaria 403 na
 * porta do painel e o caso mediria a porta) e o gate `command-center:access`, que é
 * `temPapelDoPainel('infra')` (`app/Providers/KitServiceProvider.php:173`), continua passando —
 * que é exatamente por que as três da Central seguem abrindo.
 *
 * **Este caso fica vermelho em dois eventos, e os dois são o que se quer saber:**
 *
 *  - um upgrade de plugin registra uma tela nova no `/infra` e ninguém liga a permissão dela — ela
 *    aparece na lista com o nome, e a mensagem diz qual;
 *  - alguém fecha a Central de comandos (o pacote publicou o setter por Page) — a lista fica menor
 *    que o esperado, e o sinal é revisar ADR-05, não consertar o teste.
 *
 * O filtro pelo mapa do Shield tira a `Dashboard` e as telas de autenticação: elas estão em
 * `config('filament-shield.pages.exclude')`, não têm permissão gerada, e não há o que consultar.
 */
it('deixa só as três telas da Central de comandos sem consultar a permissão', function (): void {
    $usuario = usuarioDoKit('infra', 'infra@example.com');

    // O painel PRIMEIRO: `FilamentShield::getPages()` responde pelo painel corrente, e ler o mapa
    // antes de fixá-lo devolveria as Pages de outro painel.
    noPainelDoShield('infra');

    $doShield = collect(FilamentShield::getPages() ?? []);

    /*
     * As chaves do mapa são FQCN; a permission é a primeira chave de `permissions` — o mesmo
     * caminho que `HasPageShield::getPagePermission()` (`:29-37`) e `Paineis::entidadesDoPainel()`
     * usam. `array_column($…, 'key')` aqui devolveria `[]` sem erro: Page guarda
     * `[chave => rótulo]`, e é só Resource que guarda `['key' => …]`.
     *
     * O `intersect` com o que o papel realmente tem existe porque a matriz do painel `infra`
     * inclui Pages de OUTROS painéis quando o Shield já foi resolvido lá, e `revokePermissionTo()`
     * lança `PermissionDoesNotExist` para chave que o papel não carrega.
     */
    $daMatriz = collect($usuario->getAllPermissions())->pluck('name');

    $chaves = $doShield
        ->map(fn (array $e): ?string => array_key_first($e['permissions']))
        ->filter(fn (?string $chave): bool => is_string($chave) && $daMatriz->contains($chave))
        ->values()
        ->all();

    semAPermissao('infra', ...$chaves);

    $this->actingAs($usuario);

    $abertas = collect(Filament::getPanel('infra')->getPages())
        ->reject(fn (string $classe): bool => str_starts_with($classe, 'App\\Filament\\'))
        ->filter(fn (string $classe): bool => $doShield->has($classe))
        ->filter(fn (string $classe): bool => $classe::canAccess())
        ->map(fn (string $classe): string => class_basename($classe))
        ->values()
        ->sort()
        ->values()
        ->all();

    expect($abertas)->toBe(
        ['Commands', 'History', 'RunView'],
        'telas de pacote que ainda abrem sem a permissão: '.implode(', ', $abertas),
    );
});

/**
 * As Pages de painel ESCRITAS NO KIT, por painel.
 *
 * Sai de `Filament::getPanels()` e não do sistema de arquivos porque o que interessa é o que está
 * REGISTRADO num painel — Page num diretório que o `discoverPages()` não varre não tem permissão
 * gerada e não é o assunto. O filtro por namespace `App\` é o que separa o kit do vendor, que é a
 * fronteira de ADR-05.
 *
 * `Dashboard` sai porque `config('filament-shield.pages.exclude')` a exclui da geração — sem
 * permissão, não há o que consultar.
 *
 * Helper de um arquivo só, então fica no arquivo (`.ai/rules/testes.md`).
 *
 * @return array<class-string<Page>, string> FQCN da Page => id do painel
 */
function paginasDePainelDoKit(): array
{
    $paginas = [];

    foreach (Filament::getPanels() as $id => $painel) {
        foreach ($painel->getPages() as $classe) {
            if (! str_starts_with((string) $classe, 'App\\Filament\\')) {
                continue;
            }

            $paginas[$classe] = (string) $id;
        }
    }

    return $paginas;
}
