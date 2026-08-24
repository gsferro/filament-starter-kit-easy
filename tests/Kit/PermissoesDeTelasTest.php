<?php

use App\Filament\Concerns\ExigePermissaoDaTela;
use App\Filament\Infra\Pages\HubDeInfraestrutura;
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
 * CT-24 — a Page de VENDOR fica declaradamente fora, e a lacuna é observável.
 *
 * ADR-05 recusou subclassear as dez Pages de pacote do `/infra`: são classes de terceiro, sem ponto
 * de extensão, e `.ai/rules/providers-filament.md` documenta que mexer no registro delas derruba a
 * aplicação inteira. A permissão existe no banco e no checkbox, e não é consultada.
 *
 * Este caso afirma isso de forma **observável** — revogar `View:LogsExplorer` e a tela ainda abrir —
 * em vez de tautologicamente ("a classe é de vendor, logo não consulta"). Duas consequências
 * deliberadas:
 *
 *  - a "correção" errada nº 1 (subclassear) passa a ter um lugar onde a decisão está escrita;
 *  - a "correção" errada nº 2 (pôr a classe em `pages.exclude` para o checkbox parar de mentir)
 *    fica vermelha na segunda asserção, porque removeria a alavanca do banco.
 *
 * **Quando alguém fechar a lacuna de verdade, este caso fica vermelho** — e o sinal é que ADR-05
 * precisa ser revisada, não que o teste está errado.
 */
it('deixa a Page de vendor do infra fora da checagem, com a permissão ainda selecionável', function (): void {
    semAPermissao('infra', 'View:LogsExplorer');

    $this->actingAs(usuarioDoKit('infra', 'infra@example.com'))
        ->get('/infra/logs')
        ->assertSuccessful();

    expect(Permission::query()->where('name', 'View:LogsExplorer')->exists())->toBeTrue();
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
