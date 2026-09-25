<?php

use App\Models\Role;
use App\Models\User;
use App\Policies\RolePolicy;
use App\Support\AdministradorDaInstalacao;
use Database\Seeders\PapeisSeeder;
use Database\Seeders\ShieldPermissionsSeeder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as AuthUser;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;

/**
 * As 16 policies do kit — a fatia que o gate de qualidade nomeou como a **única lacuna real**.
 *
 * ## Por que elas estavam a 23 % de cobertura
 *
 * Elas são exercitadas o tempo todo, e **indiretamente**: a tela nega, o teste vê negado. Só que
 * o `Gate` curto-circuita antes do método na maior parte dos casos — o `master_global` passa pelo
 * `before` do Shield, e quem não tem papel nenhum nem chega ao painel. O resultado é uma camada
 * inteira de autorização que ninguém executa de propósito.
 *
 * ## Qual defeito estes casos existem para pegar
 *
 * As 16 são geradas pelo Shield e estruturalmente idênticas: cada método devolve
 * `$authUser->can('Acao:Modelo')`. Num arquivo assim o defeito plausível **não** é lógica — é a
 * **string**. `ProjetoPolicy::delete()` conferindo `Delete:Tenant` depois de um copiar-colar passa
 * em qualquer teste de tela (nega quando tem de negar, por acaso, porque quem não pode uma coisa
 * costuma não poder a outra) e abre um buraco que só aparece com o papel certo na mão.
 *
 * Nada no kit pegava isso. A matriz abaixo pega, para **toda** policy e **todo** método, sem que
 * ninguém precise lembrar de acrescentar um caso quando nascer a policy 17.
 *
 * ## O que a matriz NÃO cobre, e por isso tem o segundo bloco
 *
 * Delegação pura é só metade. O `RolePolicy` tem quatro métodos com uma **segunda condição** —
 * `AdministradorDaInstalacao::papelEditavelPor()` —, e conjunção é onde mora o mutante que
 * sobrevive (`&&` → `||`). Esses têm tabela de decisão própria, mais abaixo.
 */

/**
 * Os métodos de policy de uma classe: os que recebem o usuário autenticado como 1.º parâmetro.
 *
 * O filtro é por **assinatura**, e não por `getDeclaringClass()`, porque método vindo de trait
 * (`HandlesAuthorization` traz `allow`, `deny`, `denyWithStatus`, `denyAsNotFound`) declara a
 * classe que o **usa** como declarante — o filtro óbvio deixaria os quatro passarem.
 *
 * @return list<ReflectionMethod>
 */
function metodosDePolicy(ReflectionClass $classe): array
{
    $metodos = [];

    foreach ($classe->getMethods(ReflectionMethod::IS_PUBLIC) as $metodo) {
        $parametros = $metodo->getParameters();

        if ($parametros === []) {
            continue;
        }

        $tipo = $parametros[0]->getType();

        if (! $tipo instanceof ReflectionNamedType || $tipo->getName() !== AuthUser::class) {
            continue;
        }

        $metodos[] = $metodo;
    }

    return $metodos;
}

/**
 * Um usuário falso que só sabe responder `can()`, registrando o que foi perguntado.
 *
 * É o oráculo desta matriz: o que interessa não é se o usuário pode, é **qual permissão a policy
 * foi conferir**. Um usuário real com papel semeado responderia a pergunta errada com a resposta
 * certa, e o copiar-colar passaria.
 *
 * @param  array{0: string|null}  $pedida  preenchido por referência com a permissão conferida
 */
function usuarioQueResponde(bool $resposta, ?string &$pedida): AuthUser
{
    $usuario = Mockery::mock(AuthUser::class);

    $usuario->shouldReceive('can')->andReturnUsing(
        function (string $permissao) use ($resposta, &$pedida): bool {
            $pedida = $permissao;

            return $resposta;
        },
    );

    return $usuario;
}

it('[CT-P01] toda policy confere a permissao do proprio modelo, e devolve o que o gate disse', function (string $arquivo): void {
    $classe   = 'App\\Policies\\'.$arquivo;
    $modelo   = Str::beforeLast($arquivo, 'Policy');
    $reflexao = new ReflectionClass($classe);
    $policy   = $reflexao->newInstance();

    $conferidos = [];

    foreach (metodosDePolicy($reflexao) as $metodo) {
        /*
         * Os dois valores de `can()` num caso só, de propósito: `true` prova que a policy devolve
         * o que o gate disse, e `false` prova que ela não devolve `true` por outro caminho. Um
         * método que ignorasse o gate passaria em metade e reprovaria na outra.
         */
        foreach ([true, false] as $resposta) {
            $pedida     = null;
            $argumentos = [usuarioQueResponde($resposta, $pedida)];

            foreach (array_slice($metodo->getParameters(), 1) as $parametro) {
                $tipo = $parametro->getType();

                expect($tipo)->toBeInstanceOf(ReflectionNamedType::class);

                /** @var class-string<Model> $classeDoModelo */
                $classeDoModelo = $tipo->getName();
                $registro       = new $classeDoModelo;

                /*
                 * Nome NÃO reservado: o `RolePolicy` cruza a permissão com
                 * `papelEditavelPor()`, que devolve `true` de saída para papel comum. Assim a
                 * matriz mede só a delegação, e a conjunção fica para a tabela de decisão
                 * dedicada — misturar as duas daria um caso que passa sem provar nenhuma.
                 */
                $registro->setAttribute('name', 'papel-comum-de-teste');

                $argumentos[] = $registro;
            }

            $nome = $metodo->getName();

            $this->assertSame(
                $resposta,
                $metodo->invokeArgs($policy, $argumentos),
                "{$arquivo}::{$nome}() não devolveu o que o gate respondeu",
            );

            $this->assertSame(
                ucfirst($nome).':'.$modelo,
                $pedida,
                "{$arquivo}::{$nome}() conferiu a permissão errada — provável copiar-colar de outra policy",
            );
        }

        $conferidos[] = $metodo->getName();
    }

    /*
     * Sentinela contra a falha silenciosa desta própria varredura: se o filtro de assinatura
     * parar de casar (porque alguém trocou o type-hint do 1.º parâmetro, por exemplo), o `foreach`
     * roda zero vezes e o caso ficaria **verde sem ter conferido nada**.
     */
    expect($conferidos)->not->toBe([], "nenhum método de policy encontrado em {$arquivo}");
    expect($conferidos)->toContain('viewAny', 'view', 'create', 'update', 'delete');
})->with([
    'AgenteIaPolicy',
    'AiRunPolicy',
    'AuditPolicy',
    'AuthenticationLogPolicy',
    'CommandRecordPolicy',
    'ComposerReleasePackageSnapshotPolicy',
    'ConvitePolicy',
    'ExceptionPolicy',
    'MailLogPolicy',
    'OnboardingConditionPolicy',
    'OnboardingFlowPolicy',
    'ProjetoPolicy',
    'QueueMonitorPolicy',
    'RolePolicy',
    'TenantPolicy',
    'UserPolicy',
])->group('kit');

/**
 * O dataset do `[CT-P01]` é conferido contra o disco: policy nova nasce reprovando.
 *
 * Sem este caso, o modo de falha é silencioso e conhecido — a policy 17 nasce, ninguém acrescenta
 * a linha no dataset, e a matriz segue verde cobrindo 16 de 17. É a mesma classe da guarda das
 * duas rotas de entrega: o que protege não é a correção de hoje, é a recusa da próxima omissão.
 */
it('[CT-P03] o dataset da matriz lista exatamente as policies do disco', function (): void {
    $noDisco = collect(glob(base_path('app/Policies/*.php')))
        ->map(fn (string $caminho): string => basename($caminho, '.php'))
        ->sort()
        ->values()
        ->all();

    $fonte = (string) file_get_contents(__FILE__);

    preg_match('~\)->with\(\[(.*?)\]\)->group~s', $fonte, $casado);

    expect($casado)->toHaveCount(2, 'não achei o dataset do [CT-P01] neste arquivo');

    preg_match_all("~'(\\w+Policy)'~", $casado[1], $listadas);

    $noDataset = collect($listadas[1])->sort()->values()->all();

    $this->assertSame(
        $noDisco,
        $noDataset,
        'o dataset do [CT-P01] divergiu de `app/Policies/` — acrescente ou remova a linha',
    );
})->group('kit');

/**
 * O `RolePolicy` cruza a permissão com a proteção do papel do administrador — a tabela inteira.
 *
 * Quatro dos seus métodos são `can('Acao:Role') && papelEditavelPor($papel, $operador)`, e a
 * conjunção existe por um motivo concreto: sem ela, qualquer papel com `Update:Role` poderia
 * reescrever o `master_global` e se promover. É escalada de privilégio em uma linha.
 *
 * Conjunção é também onde mora o mutante que mais sobrevive (`&&` → `||`), e ele **não morre** com
 * um caso positivo e um negativo: morre com as duas células em que os operandos discordam. Por
 * isso a tabela é o produto cartesiano fechado, e não uma amostra.
 *
 * | permissão | papel reservado | operador é master_global | esperado |
 * |---|---|---|---|
 * | sim | não | — | **true** |
 * | não | não | — | false |
 * | sim | **sim** | **não** | **false** ← a célula que a conjunção existe para proteger |
 * | sim | sim | sim | **true** |
 * | não | sim | sim | false |
 */
it('[CT-P04] o RolePolicy so libera o papel reservado para o master global', function (
    string $metodo,
    bool $temPermissao,
    bool $papelReservado,
    bool $operadorEhMaster,
    bool $esperado,
): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);

    /*
     * `firstOrCreate`, e nao `create`: o `PapeisSeeder` ja semeia o `master_global`, e criar de
     * novo estoura `RoleAlreadyExists`. O papel reservado do caso PRECISA ser o mesmo que o kit
     * usa -- um homonimo criado a parte nao seria o papel que `papelEditavelPor()` protege.
     */
    $papel = Role::firstOrCreate([
        'name'       => $papelReservado ? AdministradorDaInstalacao::papel() : 'papel-comum-de-teste',
        'guard_name' => 'web',
    ]);

    $operador = $operadorEhMaster
        ? usuarioCom(AdministradorDaInstalacao::papel())
        : usuarioCom(null);

    if ($temPermissao && ! $operadorEhMaster) {
        $operador->givePermissionTo(Permission::findOrCreate(ucfirst($metodo).':Role', 'web'));
    }

    /*
     * `fresh()` porque `givePermissionTo()` grava, mas o `PermissionRegistrar` guarda em cache o
     * que já foi carregado — sem recarregar, o `can()` responderia pelo estado anterior.
     */
    $operador = $operador->fresh();

    expect($operador)->toBeInstanceOf(User::class);

    $this->assertSame(
        $esperado,
        (new RolePolicy)->{$metodo}($operador, $papel),
        "RolePolicy::{$metodo}() decidiu diferente do esperado para esta combinação",
    );
})->with(function (): Generator {
    foreach (['update', 'delete', 'restore', 'forceDelete'] as $metodo) {
        yield "{$metodo}: papel comum, com permissão" => [$metodo, true, false, false, true];
        yield "{$metodo}: papel comum, sem permissão" => [$metodo, false, false, false, false];
        yield "{$metodo}: papel RESERVADO, com permissão, operador comum" => [$metodo, true, true, false, false];
        yield "{$metodo}: papel RESERVADO, operador master global" => [$metodo, true, true, true, true];
        yield "{$metodo}: papel RESERVADO, sem permissão, operador comum" => [$metodo, false, true, false, false];
    }
})->group('kit');
