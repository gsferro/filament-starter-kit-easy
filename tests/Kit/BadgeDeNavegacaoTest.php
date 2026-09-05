<?php

use App\Filament\Admin\Resources\Convites\ConviteResource;
use App\Filament\Admin\Resources\Roles\RoleResource;
use App\Models\Convite;
use App\Models\Role;
use BezhanSalleh\FilamentExceptions\Resources\ExceptionResource;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Gsferro\FilamentOdometerEasy\Navigation\OdometerNavigationBadge;

/**
 * Todo Resource escrito no app exibe badge de contagem no menu — e o zero aparece.
 *
 * Duas coisas que este arquivo protege, e a segunda é a que custou uma wiki inteira para ser
 * diagnosticada:
 *
 * 1. **Cobertura**: nada impedia um Resource de nascer sem badge, e foi assim que `RoleResource` e
 *    `ComposerReleasePackageResource` ficaram de fora sem ninguém perceber. A lista de CT-01 é
 *    DERIVADA dos painéis registrados — escrita à mão ela não pegaria a classe nova, que é a única
 *    razão de o caso existir.
 * 2. **O zero**: até a v0.28 o trait devolvia `null` quando a contagem era zero. Badge ausente não
 *    distingue "está vazio" de "o badge quebrou", e alguém olhou "Convites" com a tabela em zero e
 *    concluiu que a feature não existia. Ver ADR-01 de
 *    `wikis/specs/main/badge-de-contagem-em-todo-resource/`.
 *
 * O oráculo do número é a IGUALDADE contra a mesma renderização do valor esperado, nunca
 * `toContain('3')`: o odômetro embrulha o número em `<span>` e em word joiners (U+2060), então
 * qualquer `3` de atributo casaria. O valor esperado sai da fixture, jamais do código sob teste.
 *
 * Efeito colateral deliberado de CT-01 e CT-06: percorrer os três painéis CARREGA todas as classes
 * de Resource. Colisão de trait é erro fatal de compilação (ver ADR-02) e mataria o run aqui — é a
 * única forma de a suíte "ver" um defeito que nenhuma assertion alcança.
 */

/**
 * Um papel qualquer, para a `ConviteFactory` ter `role_id`.
 *
 * `Role::create()` direto e não `$this->seed(...)`: os cenários deste arquivo contam CONVITES, e
 * qual papel o convite carrega é irrelevante para a contagem. Semear a matriz do Shield custaria
 * segundos por caso para produzir um `role_id` que nenhuma asserção olha.
 */
function convites(int $quantidade): void
{
    if ($quantidade === 0) {
        return;
    }

    Role::firstOrCreate(['name' => 'papel-do-badge', 'guard_name' => 'web']);

    Convite::factory()->count($quantidade)->create();
}

/**
 * Os Resources ESCRITOS NO APP, registrados em cada painel.
 *
 * Sai de `Filament::getPanels()` porque o que interessa é o que está REGISTRADO. O filtro por
 * namespace `App\Filament` é a fronteira de escopo decidida com o usuário: Resource de pacote de
 * terceiro fica de fora, porque não dá para lhe aplicar o trait sem editar `vendor/`.
 *
 * Helper de um arquivo só, então fica no arquivo (`.ai/rules/testes.md`).
 *
 * @return array<class-string<resource>, string> FQCN do Resource => id do painel
 */
function resourcesDoAppPorPainel(): array
{
    $resources = [];

    foreach (Filament::getPanels() as $id => $painel) {
        foreach ($painel->getResources() as $classe) {
            $fqcn = is_string($classe) ? $classe : $classe->resource;

            if (! str_starts_with($fqcn, 'App\Filament')) {
                continue;
            }

            $resources[$fqcn] = (string) $id;
        }
    }

    return $resources;
}

/**
 * A varredura do enforço. UMA só, e o oráculo é COMPORTAMENTAL.
 *
 * "O Resource devolve badge?" e não "o Resource usa o trait?": a segunda forma ficaria verde num
 * `use BadgeContagemNavegacao;` inerte — método de classe vence método de trait em silêncio, e o
 * dia em que alguém declarar `getNavigationBadge()` na classe o `use` para de decidir com o diff
 * parecendo correto. É a mesma armadilha que `ExigePermissaoDoWidget` documenta.
 *
 * Isolada em função para que CT-06 possa exercitá-la contra uma classe plantada — sem isso o
 * próprio enforço fica sem teste, e um filtro estreito demais deixaria a suíte verde para sempre
 * com a regra desligada.
 *
 * @param  array<class-string<resource>, string>  $resources  FQCN => id do painel
 * @return list<class-string<resource>>
 */
function resourcesSemBadge(array $resources): array
{
    $sem = [];

    foreach ($resources as $classe => $painel) {
        Filament::setCurrentPanel($painel);

        if ($classe::getNavigationBadge() === null) {
            $sem[] = $classe;
        }
    }

    return $sem;
}

/*
|--------------------------------------------------------------------------
| R1 — todo Resource do app registrado num painel expõe badge
|--------------------------------------------------------------------------
*/

/**
 * CT-01 — nenhum Resource do app fica sem badge.
 *
 * As duas âncoras não são redundantes com a primeira asserção: uma varredura que devolvesse lista
 * vazia satisfaria "todos eles" por vacuidade, e o caso ficaria verde medindo nada. `Convite` e
 * `Role` são os dois itens que o requisito cita pelo nome.
 */
it('[CT-01] não deixa nenhum Resource do app sem badge de navegação', function (): void {
    $resources = resourcesDoAppPorPainel();

    $sem = resourcesSemBadge($resources);

    expect($sem)->toBe([], 'Resources do app sem badge: '.implode(', ', $sem))
        ->and($resources)->toHaveKey(ConviteResource::class)
        ->and($resources)->toHaveKey(RoleResource::class);
})->group('kit');

/*
|--------------------------------------------------------------------------
| R2 — o badge é a contagem dos registros que a listagem mostraria
|--------------------------------------------------------------------------
*/

/**
 * CT-02 — a contagem acompanha os registros existentes.
 *
 * Três é o "muitos" mínimo que discrimina: com 2, um mutante que devolvesse a contagem de outra
 * tabela pequena poderia acertar por acidente.
 *
 * A partição "vazio" NÃO está aqui — CT-04 já a afirma, e com mais força (existência do badge mais
 * a cor). Corte da auditoria Ponytail da wiki.
 */
it('[CT-02] mostra no badge a quantidade de registros da listagem', function (int $quantidade): void {
    Filament::setCurrentPanel('admin');

    convites($quantidade);

    expect(ConviteResource::getNavigationBadge())
        ->toBe(OdometerNavigationBadge::make($quantidade));
})->with([
    'um'     => 1,
    'muitos' => 3,
])->group('kit');

/*
|--------------------------------------------------------------------------
| R4 — o zero aparece, em cor distinta
|--------------------------------------------------------------------------
*/

/**
 * CT-04 — zero aparece, em cor discreta; acima de zero, na cor default.
 *
 * As duas linhas são um par obrigatório. Afirmar só `gray` no zero passaria numa implementação que
 * devolvesse `gray` SEMPRE — e aí a distinção que o requisito pede não existiria, com o checklist
 * marcado como coberto.
 *
 * A cor acima de zero é afirmada como "não é gray", e não como um valor: o requisito diz
 * "colorido" sem nomear cor, e fixar `null` aqui seria testar o plano.
 *
 * Dataset e não um caso só com dois momentos: `contagemDoBadge()` memoiza por request
 * (`once()`, ADR-03), então mudar o número de registros no MEIO de um teste não muda o badge.
 * Cada linha é um teste, com container próprio.
 */
it('[CT-04] exibe o zero em cinza e o resto na cor default', function (int $quantidade, bool $esperaCinza): void {
    Filament::setCurrentPanel('admin');

    convites($quantidade);

    expect(ConviteResource::getNavigationBadge())
        ->toBe(OdometerNavigationBadge::make($quantidade))
        ->and(ConviteResource::getNavigationBadgeColor() === 'gray')
        ->toBe($esperaCinza);
})->with([
    'vazio'      => [0, true],
    'preenchido' => [1, false],
])->group('kit');

/*
|--------------------------------------------------------------------------
| R5 — o odômetro renderiza, e Resource sem badge reprova
|--------------------------------------------------------------------------
*/

/**
 * CT-05 — o badge é a renderização do odômetro, não o número cru.
 *
 * A primeira asserção é a que mata `return (string) $contagem`: sem ela, um badge que devolvesse
 * "3" passaria na segunda por coincidência apenas se o odômetro também devolvesse "3" — e ele não
 * devolve, porque embrulha em markup.
 */
it('[CT-05] renderiza o badge pelo odômetro, e não como número cru', function (): void {
    Filament::setCurrentPanel('admin');

    convites(3);

    $badge = ConviteResource::getNavigationBadge();

    expect($badge)
        ->not->toBe('3')
        ->and($badge)->toBe(OdometerNavigationBadge::make(3));
})->group('kit');

/**
 * CT-06 — a varredura reprova, nomeando a classe.
 *
 * O único caso que testa o próprio teste, e ele exercita EXATAMENTE a função que CT-01 usa. Uma
 * varredura paralela só para este caso provaria que a paralela funciona, não que o enforço
 * funciona — e um filtro quebrado em CT-01 continuaria verde aqui.
 */
it('[CT-06] reprova Resource do app que nasça sem o badge', function (): void {
    $plantado = new class extends Resource
    {
        protected static ?string $model = Convite::class;
    };

    expect(resourcesSemBadge([$plantado::class => 'admin']))->toBe([$plantado::class]);
})->group('kit');

/**
 * CT-07 — Resource de pacote de terceiro não é cobrado.
 *
 * Protege a fronteira de escopo. Sem este caso, alguém "conserta" a varredura incluindo vendor, o
 * enforço fica vermelho em classes que ninguém pode editar, e a reação provável é desligá-lo.
 *
 * Ancorado numa classe CONCRETA de vendor, e não no complemento do conjunto do app: comparar dois
 * conjuntos calculados com o mesmo `str_starts_with` é tautologia — não há implementação que a
 * faça falhar. A terceira asserção é o que dá sentido às duas primeiras: `ExceptionResource` está
 * registrado, está fora do escopo, e de fato não tem badge.
 */
it('[CT-07] não cobra badge de Resource de pacote de terceiro', function (): void {
    $registrados = array_values(Filament::getPanel('admin')->getResources());

    // `toContain()` recebe VALORES, não mensagem — um segundo argumento vira outro esperado.
    expect($registrados)->toContain(ExceptionResource::class)
        ->and(resourcesDoAppPorPainel())->not->toHaveKey(ExceptionResource::class)
        ->and(ExceptionResource::getNavigationBadge())->toBeNull();
})->group('kit');
