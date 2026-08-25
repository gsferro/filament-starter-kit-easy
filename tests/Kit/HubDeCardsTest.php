<?php

use App\Filament\Admin\Pages\HubDeAdministracao;
use App\Filament\Admin\Resources\Roles\RoleResource;
use App\Filament\Admin\Resources\Users\UserResource;
use App\Filament\Infra\Pages\BackupRunsPage;
use App\Filament\Infra\Pages\HubDeInfraestrutura;
use App\Filament\Infra\Resources\AiRuns\AiRunResource;
use Database\Seeders\PapeisSeeder;
use Database\Seeders\ShieldPermissionsSeeder;
use Filament\Facades\Filament;
use Harvirsidhu\FilamentCards\CardGroup;
use Harvirsidhu\FilamentCards\CardItem;
use Harvirsidhu\FilamentCards\Filament\Pages\CardsPage;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

/**
 * As páginas hub em cartões (harvirsidhu/filament-cards).
 *
 * Ver `wikis/specs/main/hub-de-navegacao-em-cards/04-casos-de-teste.md` (CT-01, CT-03, CT-05 da
 * ancestral) e `wikis/specs/feature/v1-enriquecimento-kit/hub-de-cards-opcional/04-casos-de-teste.md`
 * (CT-01, CT-02, CT-05 a CT-08, CT-11, CT-12 desta wiki).
 *
 * **O `phpunit.xml` fixa `KIT_HUB=false`**, então a partição "desligada" não precisa de arranjo — e
 * é isso que torna os casos de default um teste do que o kit ENTREGA, em vez de um teste do `.env`
 * de quem roda a suíte.
 */
beforeEach(function (): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);
});

/**
 * CT-01 (desta wiki) — com a flag no default, os hubs de /admin e /app não são alcançáveis.
 *
 * A linha `master_global` é a discriminante: ele vence toda checagem de PERMISSÃO pelo
 * `Gate::before`. Se o desligamento tivesse sido implementado como permission em vez de config, só
 * ele atravessaria — e é essa linha que ficaria vermelha.
 *
 * A terceira asserção cobre a barra lateral sem gastar um cenário: `canAccess()` falso também faz
 * `Page::registerNavigationItems()` retornar cedo
 * (`vendor/filament/filament/src/Pages/Page.php:133-135`).
 *
 * A linha do painel /app vive em `tests/Tenancy/HubDoNegocioTest.php`: aquele painel só tem
 * persona com contexto de organização.
 */
it('recusa o hub de administração enquanto a flag está desligada', function (string $papel): void {
    expect(config('kit.hub'))->toBeFalse();

    $this->actingAs(usuarioDoKit($papel, "{$papel}@example.com"));

    $this->get('/admin/hub-de-administracao')->assertForbidden();

    $this->get('/admin')
        ->assertSuccessful()
        ->assertDontSee('Hub de administração');
})->with([
    'papel de painel'          => ['admin'],
    'coringa do Gate::before'  => ['master_global'],
]);

/**
 * CT-02 (desta wiki) — ligar a flag devolve a tela que existia, e o item volta ao menu.
 *
 * Os rótulos vêm das próprias classes, nunca escritos como string: o do `RoleResource` é
 * "Funções" (rótulo do Shield), não "Papéis", e cravar o texto aqui tornaria o caso um teste da
 * tradução do vendor em vez do hub.
 *
 * É também o caso que protege os hubs SEM mapa de descrições: eles chamam `cardsDoPainel()` sem o
 * segundo parâmetro, e uma implementação que o exigisse — ou que lesse a chave sem `??` — morreria
 * aqui com TypeError ou "Undefined array key".
 */
it('devolve o hub de administração quando a flag é ligada', function (): void {
    config(['kit.hub' => true]);

    $this->actingAs(usuarioDoKit('admin', 'admin@example.com'));

    $this->get('/admin/hub-de-administracao')
        ->assertSuccessful()
        ->assertSee(UserResource::getNavigationLabel())
        ->assertSee(RoleResource::getNavigationLabel());

    $this->get('/admin')
        ->assertSuccessful()
        ->assertSee('Hub de administração');
});

/**
 * CT-05 (desta wiki) + CT-03 da ancestral — o hub responde em cada painel, para quem tem o papel
 * dele, e o de infraestrutura responde COM A FLAG EM QUALQUER VALOR.
 *
 * As duas linhas de `/infra` com o mesmo resultado esperado não são redundância: uma partição
 * sozinha não distingue "não depende da flag" de "depende, e o arranjo calhou de estar no valor
 * certo". **A linha `flag desligada` é a que fica vermelha se alguém acrescentar `canAccess()` com
 * a flag ao `HubDeInfraestrutura`** — é o guarda de ADR-03.
 *
 * Duas asserções por linha, e a segunda é a que importa: `assertSuccessful()` sozinho passa com a
 * grade VAZIA — que é o que acontece se a descoberta devolver nada (grupo montado com a chave
 * errada, Page na pasta errada, seeders não rodados).
 */
it('abre o hub do painel para quem tem o papel dele', function (string $rota, string $papel, string $destino, bool $hub): void {
    config(['kit.hub' => $hub]);

    $this->actingAs(usuarioDoKit($papel, "{$papel}@example.com"));

    $this->get($rota)
        ->assertSuccessful()
        ->assertSee($destino);
})->with([
    'infra com a flag desligada'    => ['/infra/hub-de-infraestrutura', 'infra', 'Execuções de IA', false],
    'infra com a flag ligada'       => ['/infra/hub-de-infraestrutura', 'infra', 'Execuções de IA', true],
    'admin com papel admin'         => ['/admin/hub-de-administracao', 'admin', 'Usuários', true],
    'admin com papel master_global' => ['/admin/hub-de-administracao', 'master_global', 'Usuários', true],
]);

/**
 * CT-06 (desta wiki) — desligar o hub esconde a tela e NÃO mexe na matriz de permissões.
 *
 * É o guarda de ADR-02: a alternativa recusada lá — recortar o registro da Page no provider —
 * passaria pelos casos acima sem esforço e faria estas três permissões desaparecerem, porque o
 * Shield descobre as entidades por `$panel->getPages()` cru
 * (`vendor/bezhansalleh/filament-shield/src/Concerns/HasEntityDiscovery.php:30-34`).
 *
 * Sem este caso, aquela decisão de desenho não tem nenhuma barreira executável.
 */
it('mantém a permissão dos três hubs na matriz com a flag desligada', function (string $permissao): void {
    expect(config('kit.hub'))->toBeFalse();

    expect(Permission::query()->where('name', $permissao)->exists())->toBeTrue();
})->with([
    'o hub que fica ligado' => ['View:HubDeInfraestrutura'],
    'o hub de /admin'       => ['View:HubDeAdministracao'],
    'o hub de /app'         => ['View:HubDoNegocio'],
]);

/**
 * CT-07 (desta wiki) — os cartões do /infra trazem a descrição do PRÓPRIO destino.
 *
 * Os três destinos são escolha discriminante, não conveniência: "Backups" tem rótulo
 * autoexplicativo e é o que carrega a string literal; "Execuções de IA" é código do kit;
 * `ExceptionResource` é rótulo de vendor não traduzido ("Exception") — a partição para a qual a
 * descrição existe.
 *
 * A última asserção é a que mata o mapa indexado por posição, ou por rótulo, ou a variável de laço
 * fora do escopo: sem ela, uma implementação que aplica a MESMA frase a todo cartão passaria nas
 * três primeiras.
 */
it('descreve cada destino do hub de infraestrutura com a frase dele', function (): void {
    // `master_global` de propósito: ele passa por todo `canAccess()`, então a grade sai COMPLETA.
    // Com um papel de painel, um destino filtrado deixaria a chave ausente e o caso morreria no
    // arranjo, não na regra.
    $this->actingAs(usuarioDoKit('master_global', 'master@example.com'));

    $descricoes = descricoesDoHub(HubDeInfraestrutura::class, 'infra');

    expect($descricoes[BackupRunsPage::class])
        ->toContain('quando rodaram, o tamanho e se o destino respondeu')
        ->and($descricoes[AiRunResource::class])->not->toBeEmpty()
        ->and(array_unique(array_values($descricoes)))->toHaveCount(count($descricoes));
});

/**
 * CT-08 (desta wiki) — nenhum cartão do hub de infraestrutura fica sem descrição.
 *
 * **Este caso fica vermelho quando alguém instala um plugin com página no /infra e não escreve a
 * frase.** Isso é o comportamento pedido, não um falso positivo: a cláusula do requisito é "uma
 * descrição para explicar o que cada link serve", e cartão novo sem frase a viola. A linha a
 * acrescentar é uma, em `HubDeInfraestrutura::descricoesDosDestinos()`, e a mensagem de falha diz
 * qual classe está faltando.
 *
 * A decisão de manter este caso — em tensão com ADR-04, que recusou a família de teste a que ele
 * pertence — é do mantenedor, registrada em 2026-08-21.
 */
it('não deixa nenhum cartão do hub de infraestrutura sem descrição', function (): void {
    $this->actingAs(usuarioDoKit('master_global', 'master@example.com'));

    $semDescricao = array_keys(array_filter(
        descricoesDoHub(HubDeInfraestrutura::class, 'infra'),
        static fn (?string $descricao): bool => blank($descricao),
    ));

    expect($semDescricao)->toBe([], 'Destinos sem frase em HubDeInfraestrutura::descricoesDosDestinos(): '.implode(', ', $semDescricao));
});

/**
 * CT-11 (desta wiki) — tirar o hub do padrão não tira o pacote do projeto.
 *
 * É o único caso da cláusula "deixe ele instalado", porque ela não gera passo de implementação: ela
 * PROÍBE uma ação. Sem este caso, `composer remove harvirsidhu/filament-cards` — o caminho que
 * ADR-01 recusou — passaria por toda a suíte, e o /infra só quebraria quando alguém abrisse o
 * painel.
 *
 * As duas asserções não são a mesma: o `composer.json` prova a DECLARAÇÃO (que sobrevive a um
 * `composer install`), e o `class_exists` prova que o pacote está de fato instalado. A primeira lê
 * `require`, e não a união com `require-dev`, de propósito — o hub roda em produção.
 */
it('mantém o pacote do hub declarado e carregável', function (): void {
    $composer = json_decode((string) file_get_contents(base_path('composer.json')), true, flags: JSON_THROW_ON_ERROR);

    expect($composer['require'])->toHaveKey('harvirsidhu/filament-cards')
        ->and(class_exists(CardsPage::class))->toBeTrue();
});

/**
 * CT-12 (desta wiki) — a opção documentada é uma opção encontrável.
 *
 * As duas últimas linhas são as que valem. Imagem que existe e ninguém referencia é peso morto no
 * repositório; referência para imagem que não existe é o ícone quebrado que o GitHub renderiza —
 * defeito visível para quem abre a documentação e invisível para toda a suíte.
 *
 * Todas as asserções são de PRESENÇA, então não precisam do filtro de comentário que
 * `.ai/rules/testes.md` exige das asserções de ausência.
 */
it('publica e referencia os artefatos da documentação do hub', function (): void {
    expect(file_exists(base_path('art/infra-hub.png')))->toBeTrue('art/infra-hub.png não foi publicada — rode `composer art`')
        ->and(file_exists(base_path('art/thumbs/infra-hub.png')))->toBeTrue('a thumb não foi gerada — rode `php artisan kit:arte`');

    $receita = (string) file_get_contents(base_path('wikis/receitas.md'));

    expect($receita)
        ->toContain('art/thumbs/infra-hub.png')
        ->toContain('KIT_HUB');
});

/**
 * As descrições que o hub realmente montou, por FQCN do destino.
 *
 * Lê o resultado de `getCards()` em vez do mapa declarado: é a diferença entre provar que a frase
 * foi ESCRITA e provar que ela chegou ao cartão. Um mapa perfeito com `->description()` nunca
 * chamado passaria numa leitura do mapa e falha aqui.
 *
 * `Filament::setCurrentPanel()` é arranjo, não detalhe: quem define o painel corrente num request é
 * o middleware `SetUpPanel`, que teste de componente não atravessa. Sem ele a descoberta lê o
 * painel que estiver ambiente no processo.
 *
 * Helper de um arquivo só, então fica no arquivo — `.ai/rules/testes.md` só manda mover para o
 * `tests/Pest.php` o que mais de um arquivo usa.
 *
 * @param  class-string<CardsPage>  $hub
 * @return array<class-string, string|null>
 */
function descricoesDoHub(string $hub, string $painel): array
{
    Filament::setCurrentPanel($painel);

    $metodo = new ReflectionMethod($hub, 'getCards');
    $metodo->setAccessible(true);

    /** @var list<CardGroup> $grupos */
    $grupos = $metodo->invoke(null);

    $descricoes = [];

    foreach ($grupos as $grupo) {
        /** @var CardItem $item */
        foreach ($grupo->getItems() as $item) {
            $descricoes[$item->getPage()] = $item->getDescription();
        }
    }

    return $descricoes;
}

/**
 * CT-01 da ancestral — o administrador da instalação vê os destinos de administração.
 *
 * É a persona de controle: se o hub some para ele, o defeito é de renderização, não de
 * autorização. O recorte por papel é assunto do par em `tests/Tenancy`, onde as personas divergem
 * de verdade.
 */
it('mostra os destinos de administração para quem administra a instalação', function (): void {
    config(['kit.hub' => true]);

    $this->actingAs(usuarioDoKit('master_global', 'master@example.com'));

    Filament::setCurrentPanel('admin');

    Livewire::test(HubDeAdministracao::class)
        ->assertSee(UserResource::getNavigationLabel())
        ->assertSee(RoleResource::getNavigationLabel());
});

/**
 * CT-05 da ancestral — o cartão aponta para o destino que ele nomeia, no grupo a que o destino
 * pertence.
 *
 * A URL vem de `AiRunResource::getUrl()`, nunca escrita como string fixa: string fixa
 * transformaria o caso num teste do PRD, e quebraria no dia em que o slug mudasse por um motivo
 * legítimo.
 *
 * O grupo entra porque é ele que separa "grade organizada" — o que o requisito pede — de "lista de
 * links soltos".
 */
it('aponta o cartão para o destino e o coloca no grupo dele', function (): void {
    $this->actingAs(usuarioDoKit('infra', 'infra@example.com'));

    Filament::setCurrentPanel('infra');

    Livewire::test(HubDeInfraestrutura::class)
        ->assertSee(AiRunResource::getUrl(), escape: false)
        ->assertSee('IA');
});
