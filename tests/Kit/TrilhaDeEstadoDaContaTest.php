<?php

use App\Filament\Admin\Resources\Users\Pages\ListUsers;
use App\Models\AgenteIa;
use App\Models\Convite;
use App\Models\Projeto;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\PapeisSeeder;
use Database\Seeders\ShieldPermissionsSeeder;
use Filament\Actions\Testing\TestAction;
use Illuminate\Support\Str;
use Livewire\Livewire;
use OwenIt\Auditing\Models\Audit;

/**
 * Desativar e reativar conta entram na trilha de `/infra/audits`.
 *
 * ## O defeito que este arquivo guarda
 *
 * `App\Traits\AuditsFillables::getAuditInclude()` devolvia `getFillable()`, e `users.ativo`
 * **nunca** é `$fillable` — atribuição em massa com ela destrancaria conta. Quem a escreve é
 * `desativar()`/`reativar()`, com `forceFill(...)->save()` (`app/Models/User.php:307`, `:326`):
 * o evento `updated` dispara, o auditor observa, e o atributo era descartado pelo filtro.
 *
 * Resultado: a trilha registrava a troca do NOME do usuário e não registrava o CORTE DO ACESSO
 * dele — que é o evento que importa numa auditoria.
 *
 * ## Por que os casos chamam o model direto
 *
 * A barreira é do model, não da tela. Um cenário que passasse pela Action mediria o contexto, não
 * o filtro de auditoria — e ficaria verde com `auditaAlemDoFillable()` inteiro removido, porque a
 * Action grava outras colunas junto. Ver `.ai/rules/filament.md`, "asserção de identidade vive no
 * model".
 *
 * Ver ADR-05 de `wikis/specs/feat/estudo-de-pacotes-rodada-2/`.
 */
beforeEach(function (): void {
    /*
     * `audit.console` é false no kit (`config/audit.php:203`) e a suíte roda em console: sem esta
     * linha, `Auditable::isAuditingEnabled()`
     * (`vendor/owen-it/laravel-auditing/src/Auditable.php:552-559`) devolve false e a trilha nunca
     * é escrita. Os casos passariam por a tabela estar VAZIA em vez de por a coluna estar nela —
     * que é o pior resultado possível para um arquivo cuja tese é "a coluna entra na trilha".
     *
     * O mesmo arranjo está em `tests/Kit/ConviteTest.php`, com a mesma justificativa.
     */
    config(['audit.console' => true]);

    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);
});

/** A última linha de auditoria do usuário, ou `null`. */
function ultimaAuditoriaDe(User $user): ?Audit
{
    return Audit::query()
        ->where('auditable_type', $user->getMorphClass())
        ->where('auditable_id', $user->getKey())
        ->latest('id')
        ->first();
}

/**
 * A contraprova sobre o próprio `User`: a lista não vaza para o que não foi declarado.
 *
 * Sem CT no `04` — CT-31 cobre a mesma proposição nos QUATRO models que usam a trait **sem**
 * override, e este cobre o model que TEM override, que é o caso que aquele `Esquema` não alcança.
 * Um mutante que trocasse o filtro por "auditar tudo" deixaria CT-26 verde e este vermelho —
 * `remember_token` é ruído técnico e não pode entrar na trilha.
 */
it('mantem fora da trilha a coluna tecnica que ninguem declarou', function (): void {
    $alvo = usuarioDoKit('panel_user', 'alvo@example.com');

    $alvo->forceFill(['remember_token' => 'token-novo-qualquer'])->save();

    $trilha = ultimaAuditoriaDe($alvo);

    expect($trilha?->new_values ?? [])->not->toHaveKey('remember_token');
})->group('kit');

/**
 * A conta alvo no estado pedido pela matriz, e a administradora que opera — nunca a mesma pessoa.
 *
 * O par não é decoração: `User::desativar()` recusa a própria conta
 * (`User::motivoParaNaoDesativar()`), então um cenário em que o executor é o alvo mediria a
 * recusa, não a trilha.
 *
 * `forceFill(['aprovacao_pendente' => true])->save()` é como o repositório inteiro arranja esse
 * estado (`tests/Kit/AbasDeListagemTest.php:38`), porque a coluna não é `$fillable` de propósito.
 */
function contaNoEstado(string $estado): User
{
    $alvo = usuarioDoKit('panel_user', 'alvo@example.com');

    match ($estado) {
        'ativa'      => null,
        'desativada' => $alvo->desativar(),
        'pendente'   => $alvo->forceFill(['aprovacao_pendente' => true])->save(),
        'excluída'   => $alvo->delete(),
    };

    return $alvo->fresh() ?? $alvo;
}

/** Quantas linhas a trilha desta conta já tem. */
function linhasDaTrilhaDe(User $user): int
{
    return Audit::query()
        ->where('auditable_type', $user->getMorphClass())
        ->where('auditable_id', $user->getKey())
        ->count();
}

/**
 * CT-26 — cada operação de fronteira grava o antes e o depois, entrando pelo MODEL.
 *
 * As quatro linhas são exatamente as quatro células `✅F` da matriz estado × operação, e entrar
 * pelo model (e não pela tela) é o gate de camada da regra: é o que distingue "a trilha registra"
 * de "a tela registra". Com a barreira só na Action, M42 sobreviveria — a chamada direta ao model,
 * que é a que um comando artisan ou um job faz, passaria despercebida.
 *
 * As colunas `antes`/`depois` matam M39 (a trilha registra a mudança com `old_values` vazio, e
 * ninguém sabe qual era o estado anterior), e a linha "pendente × aprovar" mata M38 (a lista extra
 * alcança `ativo` e esquece `aprovacao_pendente` — metade da correção deixa o defeito vivo).
 */
it('[CT-26] grava o antes e o depois de cada operacao de fronteira', function (string $estado, string $operacao, string $coluna, bool $antes, bool $depois): void {
    $alvo = contaNoEstado($estado);

    $this->actingAs(usuarioDoKit('admin', 'admin@example.com'));

    $alvo->{$operacao}();

    $trilha = ultimaAuditoriaDe($alvo);

    expect($trilha)->not->toBeNull("a operação {$operacao} não gerou linha em audits")
        ->and($trilha->event)->toBe('updated')
        ->and($trilha->old_values)->toHaveKey($coluna, $antes)
        ->and($trilha->new_values)->toHaveKey($coluna, $depois);
})->with([
    'ativa × desativar'     => ['ativa', 'desativar', 'ativo', true, false],
    'desativada × reativar' => ['desativada', 'reativar', 'ativo', false, true],
    'pendente × aprovar'    => ['pendente', 'aprovar', 'aprovacao_pendente', true, false],
    'pendente × desativar'  => ['pendente', 'desativar', 'ativo', true, false],
])->group('kit');

/**
 * CT-27 — a desativação feita PELA TELA também deixa a linha na trilha.
 *
 * O par de camada de CT-26: lá o model é chamado direto, aqui a operação entra pela Action da
 * listagem do `/admin`, que é o caminho real de quem administra. Uma implementação que gravasse a
 * trilha só no model seria correta; uma que gravasse só na tela não — e é por isso que os dois
 * existem.
 *
 * A autora registrada é a segunda metade, e não é redundante com o log: a trilha de
 * `/infra/audits` é lida por quem audita, e "quem cortou o acesso de quem" é a pergunta que ela
 * responde.
 */
it('[CT-27] registra na trilha a desativacao feita pela acao da listagem', function (): void {
    $alvo  = contaNoEstado('ativa');
    $admin = usuarioDoKit('admin', 'admin@example.com');

    $this->actingAs($admin);
    noPainelDoShield('admin');
    noPainelBootado('admin');

    Livewire::test(ListUsers::class)
        ->loadTable()
        ->callAction(TestAction::make('desativar')->table($alvo))
        ->assertNotified();

    $trilha = ultimaAuditoriaDe($alvo);

    expect($trilha)->not->toBeNull('a ação da listagem não gerou linha em audits')
        ->and($trilha->old_values)->toHaveKey('ativo', true)
        ->and($trilha->new_values)->toHaveKey('ativo', false)
        ->and($trilha->user_id)->toBe($admin->getKey());
})->group('kit');

/**
 * CT-40 — a desativação bem-sucedida continua registrando no canal de autenticação.
 *
 * É o par POSITIVO do efeito, e o achado mais silencioso da revisão adversarial: sem ele, as seis
 * linhas de não-log de CT-28 seriam verdadeiras por vácuo. Com o log removido de toda a aplicação
 * — uma "simplificação" plausível para quem acabou de mover a auditoria para `audits` (M59) —,
 * CT-28 continuaria verde e o checklist seguiria marcando "canal correto do efeito" como coberto.
 *
 * Executor e alvo são nomeados por POSIÇÃO: um log que trocasse os dois passaria numa asserção que
 * só perguntasse "os dois identificadores aparecem".
 *
 * A última asserção é a conjunção que nenhum outro caso faz: os DOIS efeitos, na mesma operação.
 * `tests/Kit/SituacaoDaContaTest.php` afirma o log (da wiki `situacao-da-conta`); aqui ele entra
 * emparelhado com a linha da trilha, que é o que R9 compra.
 */
it('[CT-40] registra a desativacao no canal de autenticacao e na trilha, na mesma operacao', function (): void {
    $alvo  = contaNoEstado('ativa');
    $admin = usuarioDoKit('admin', 'admin@example.com');
    $canal = espiarAutenticacao();

    $this->actingAs($admin);

    $alvo->desativar();

    $canal->shouldHaveReceived('info')
        ->withArgs(fn (string $mensagem, array $contexto = []): bool => str_starts_with($mensagem, '[User@desativar]')
            && ($contexto['executor_id'] ?? null) === $admin->getKey()
            && ($contexto['alvo_id'] ?? null) === $alvo->getKey())
        ->once();

    expect(ultimaAuditoriaDe($alvo)?->new_values ?? [])->toHaveKey('ativo', false);
})->group('kit');

/**
 * CT-28 — operação que não muda nada não muda coluna, não grava linha nem loga.
 *
 * As seis células `❌` da matriz, e a idempotência ancorada no AGREGADO: a conta persistida, com
 * as duas colunas de fronteira afirmadas explicitamente, mais o **delta** de linhas da trilha. O
 * delta e não a contagem absoluta — se o auditor passar a registrar `created`, uma contagem
 * absoluta reprovaria sem defeito nenhum.
 *
 * As três metades são afirmadas porque a legenda promete três: coluna, linha e canal. A versão
 * anterior prometia as três e afirmava duas.
 *
 * As linhas `desativada × aprovar` e `pendente × reativar` são as que matam M60 — `reativar()`
 * limpando `aprovacao_pendente` de lambuja, ou `aprovar()` reativando a conta, misturando as duas
 * fronteiras. Nenhum eixo sozinho as alcança, e é por isso que a técnica é a matriz.
 *
 * As asserções de ausência têm destinatário e alvo: a conta existe, o channel existe e é o mesmo
 * que CT-40 prova receber no caminho feliz, e a mesma operação no estado oposto GRAVA, o que
 * CT-26 mostra no mesmo arquivo.
 */
it('[CT-28] nao muda coluna, nao grava linha e nao loga quando a operacao nao muda nada', function (string $estado, string $operacao): void {
    $alvo  = contaNoEstado($estado);
    $canal = espiarAutenticacao();

    $this->actingAs(usuarioDoKit('admin', 'admin@example.com'));

    $antes  = $alvo->only(['ativo', 'aprovacao_pendente']);
    $linhas = linhasDaTrilhaDe($alvo);

    $alvo->{$operacao}();

    $depois = $alvo->fresh()?->only(['ativo', 'aprovacao_pendente']) ?? [];

    expect($depois)->toBe($antes)
        ->and(linhasDaTrilhaDe($alvo))->toBe($linhas);

    $canal->shouldNotHaveReceived('info');
})->with([
    'ativa × reativar'       => ['ativa', 'reativar'],
    'ativa × aprovar'        => ['ativa', 'aprovar'],
    'desativada × desativar' => ['desativada', 'desativar'],
    'desativada × aprovar'   => ['desativada', 'aprovar'],
    'pendente × reativar'    => ['pendente', 'reativar'],
    /*
     * A célula `excluída × excluir` da matriz **não tem linha**, e o motivo é achado, não corte:
     * `delete()` sobre um registro já excluído logicamente roda o `UPDATE` de novo, dispara o
     * evento `deleted` de novo, e o observer da lixeira estoura
     * `UNIQUE constraint failed: recycle_bin_items.model_type, recycle_bin_items.model_id`.
     *
     * Pela tela o estado é inalcançável — a ação de exclusão da listagem não vê registro excluído,
     * e a lixeira restaura antes —, então isto não é defeito de produção observável hoje; é uma
     * lacuna de idempotência do model, registrada em `04-casos-de-teste.md` › `## Reconciliação`
     * com destino **implementação**. Escrever a linha deixaria a suíte vermelha por algo que esta
     * reconciliação não tem mandato para consertar.
     */
])->group('kit');

/**
 * CT-29 — editar só o nome não arrasta a coluna de fronteira, em nenhum estado.
 *
 * As três células `✅C` da matriz. O `Esquema` percorre os três estados porque é justamente no
 * estado de fronteira (desativada, pendente) que o arrasto é mais plausível — a versão anterior
 * deste cenário tinha `Dado` fixo em "uma conta ativa" e resolvia as outras duas células por
 * ponteiro.
 *
 * M41 é fraco de propósito, e isso está declarado: sob o auditor do projeto a linha só carrega
 * campo alterado, então só a variante que **força** a coluna é alcançável. Mantido porque é a
 * única forma de M41 que alguém escreveria.
 */
it('[CT-29] registra so o nome ao editar o nome, em qualquer estado', function (string $estado): void {
    $alvo = contaNoEstado($estado);

    $this->actingAs(usuarioDoKit('admin', 'admin@example.com'));

    $fronteiraAntes = $alvo->only(['ativo', 'aprovacao_pendente']);

    $alvo->update(['name' => 'Nome Trocado']);

    $trilha = ultimaAuditoriaDe($alvo);

    expect($trilha)->not->toBeNull('editar o nome não gerou linha em audits')
        ->and($trilha->new_values)->toHaveKey('name', 'Nome Trocado')
        ->and($trilha->old_values)->not->toHaveKey('ativo')
        ->and($trilha->new_values)->not->toHaveKey('ativo')
        ->and($trilha->old_values)->not->toHaveKey('aprovacao_pendente')
        ->and($trilha->new_values)->not->toHaveKey('aprovacao_pendente')
        ->and($alvo->fresh()?->only(['ativo', 'aprovacao_pendente']))->toBe($fronteiraAntes);
})->with(['ativa', 'desativada', 'pendente'])->group('kit');

/**
 * CT-42 — a exclusão lógica deixa registro na trilha, a partir de qualquer estado.
 *
 * A coluna `excluir` da matriz deixou de ser "não se aplica": a premissa nº 5 (a lista de colunas
 * de fronteira fechada em `ativo` e `aprovacao_pendente`) é premissa de **mecanismo** — ela decide
 * *como* a exclusão aparece na trilha, não *se* a exclusão deixa rastro. Para uma regra de
 * compliance, falha fechado é auditar mais, e o invariante que vale nas duas leituras é o que este
 * caso afirma.
 *
 * O `Dado` exige uma alteração anterior na trilha para que "linha NOVA" tenha contra o que ser
 * medida: sem ela, a primeira linha da tabela satisfaria a asserção por acidente.
 */
it('[CT-42] registra na trilha a exclusao logica, a partir de qualquer estado', function (string $estado): void {
    $alvo  = contaNoEstado($estado);
    $admin = usuarioDoKit('admin', 'admin@example.com');

    $this->actingAs($admin);

    $alvo->update(['name' => 'Alteração anterior']);

    $antes          = linhasDaTrilhaDe($alvo);
    $fronteiraAntes = $alvo->fresh()?->only(['ativo', 'aprovacao_pendente']);

    $alvo->delete();

    $trilha = ultimaAuditoriaDe($alvo);

    expect(linhasDaTrilhaDe($alvo))->toBeGreaterThan($antes, 'a exclusão não deixou rastro na trilha')
        ->and($trilha?->user_id)->toBe($admin->getKey())
        ->and($alvo->fresh()?->only(['ativo', 'aprovacao_pendente']))->toBe($fronteiraAntes);
})->with(['ativa', 'desativada', 'pendente'])->group('kit');

/*
|--------------------------------------------------------------------------
| R10 — a extensão soma, não substitui, e não vaza
|--------------------------------------------------------------------------
*/

/**
 * CT-30 — alterar um campo comum continua entrando na trilha do usuário.
 *
 * O par que mata a SUBSTITUIÇÃO (M43): uma implementação que trocasse a lista de campos editáveis
 * pela lista extra passaria em toda a R9 — `ativo` entraria na trilha — e apagaria da trilha o
 * nome, o e-mail e os demais campos. Nenhum cenário que olhe para a coluna nova percebe isso; só
 * um cenário sobre um campo comum percebe.
 */
it('[CT-30] mantem na trilha a alteracao de um campo comum', function (): void {
    $alvo = usuarioDoKit('panel_user', 'antigo@example.com');

    $this->actingAs(usuarioDoKit('admin', 'admin@example.com'));

    $alvo->update(['email' => 'novo@example.com']);

    $trilha = ultimaAuditoriaDe($alvo);

    expect($trilha?->old_values)->toHaveKey('email', 'antigo@example.com')
        ->and($trilha?->new_values)->toHaveKey('email', 'novo@example.com');
})->group('kit');

/**
 * CT-31 — nenhum model sem override passa a auditar coluna de fora.
 *
 * O `Esquema` percorre os QUATRO models que usam a mesma trait sem declarar colunas extras, porque
 * M44 fala de vazamento: o ponto de extensão declarado no model base alcançaria todos eles, e um
 * caso sobre um só deixaria três sem guarda.
 *
 * **A asserção é sobre a LINHA gravada, não sobre a lista devolvida**, e a distinção é a mesma que
 * a `## Degradação declarada` da wiki usa para desqualificar os dois casos pré-existentes de
 * `tests/Kit/FundacaoTest.php`: consultar `getAuditInclude()` prova que a função devolve o que
 * devolve; só a linha de `audits` prova que o auditor a respeita.
 *
 * `uuid` é a coluna de fora nos quatro: existe em todos, é única, e nenhum deles a declara
 * `$fillable` — mudá-la por `forceFill` é exatamente o caminho que `desativar()` usa em `User`.
 */
it('[CT-31] mantem fora da trilha a coluna nao declarada dos models sem override', function (string $classe, string $editavel): void {
    $this->actingAs(usuarioDoKit('admin', 'admin@example.com'));

    $registro = match ($classe) {
        Tenant::class   => Tenant::create(['nome' => 'Acme', 'slug' => 'acme']),
        // `tenant_id` não é `$fillable` no Projeto (quem o carimba é a trait de tenancy), então a
        // fixture o escreve por `forceFill` — sem isso o insert morre no NOT NULL da coluna.
        Projeto::class  => tap(new Projeto(['nome' => 'Contrato 2026']), fn (Projeto $projeto): bool => $projeto
            ->forceFill(['tenant_id' => Tenant::create(['nome' => 'Beta', 'slug' => 'beta'])->getKey()])
            ->save()),
        Convite::class  => Convite::factory()->create(),
        AgenteIa::class => AgenteIa::create(['slug' => 'assistente-teste', 'nome' => 'Assistente', 'instrucoes' => 'Teste.']),
    };

    $registro->forceFill([$editavel => 'valor-novo-qualquer', 'uuid' => (string) Str::uuid()])->save();

    $trilha = Audit::query()
        ->where('auditable_type', $registro->getMorphClass())
        ->where('auditable_id', $registro->getKey())
        ->latest('id')
        ->first();

    expect($trilha)->not->toBeNull("alterar {$editavel} em {$classe} não gerou linha em audits")
        ->and($trilha->new_values)->toHaveKey($editavel, 'valor-novo-qualquer')
        ->and($trilha->new_values)->not->toHaveKey('uuid')
        ->and($trilha->old_values)->not->toHaveKey('uuid');
})->with([
    'Tenant'   => [Tenant::class, 'nome'],
    'Projeto'  => [Projeto::class, 'nome'],
    'Convite'  => [Convite::class, 'email'],
    'AgenteIa' => [AgenteIa::class, 'nome'],
])->group('kit');
