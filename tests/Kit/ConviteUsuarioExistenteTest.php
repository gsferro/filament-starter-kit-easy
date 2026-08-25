<?php

use App\Models\Convite;
use App\Models\User;
use Database\Seeders\PapeisSeeder;
use Database\Seeders\ShieldPermissionsSeeder;
use Illuminate\Support\Facades\Log;
use Psr\Log\LoggerInterface;

/**
 * Convite para quem JÁ TEM CONTA — o que não depende de tenancy.
 *
 * O desvio existe nos dois modos do kit: quem já tem conta nunca ganha outra, e a asserção
 * de identidade é do model. Os casos que precisam de organização (o vínculo, a caixa de
 * entrada, a recusa pela tela) estão em `tests/Tenancy/ConviteUsuarioExistenteTest.php`.
 *
 * Os helpers `usuarioDoKit()`, `conviteCom()` e `espiarAutenticacao()` são de
 * `ConviteTest.php` — o Pest carrega os arquivos da suíte inteira, então rode a pasta, não
 * um arquivo só.
 */
beforeEach(function (): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);
});

/**
 * CT-04 — a asserção de e-mail vive no MODEL, e este caso a chama direto.
 *
 * É o caso central da feature. Ele existe porque a query da caixa de entrada já filtra por
 * e-mail, o que faz a asserção do model *parecer* redundante — e foi esse raciocínio que
 * produziu o furo do `jeffersongoncalves/filament-teams`, onde
 * `TeamInvitation::accept(Authenticatable $user)` anexa qualquer usuário e confia no
 * `->where('email', …)` da tabela da página. Sem tela nenhuma no caminho: se alguém remover
 * a asserção "porque a tela já garante", este caso fica vermelho. Ver ADR-03.
 */
it('recusa aceite quando o e-mail nao corresponde', function (): void {
    $canal = Mockery::spy(LoggerInterface::class);
    Log::shouldReceive('channel')->with('autenticacao')->andReturn($canal);

    $convite = ofertaPara('dona@example.test');
    $outra   = User::factory()->create(['email' => 'outra@example.test']);

    expect(fn (): User => $convite->aceitarComoUsuarioExistente($outra))
        ->toThrow(RuntimeException::class, 'Este convite não é para a sua conta.');

    // Nada foi consumido e nada foi vinculado: a exceção acontece ANTES do update.
    $this->assertDatabaseMissing('tenant_user', ['user_id' => $outra->id]);

    expect($convite->fresh()?->aceito_em)->toBeNull()
        ->and($outra->fresh()?->hasRole('panel_user'))->toBeFalse();

    $canal->shouldHaveReceived('warning')
        ->withArgs(fn (string $mensagem, array $contexto): bool => str_starts_with($mensagem, '[Convite@aceitarComoUsuarioExistente]')
            && $contexto['motivo'] === 'email_nao_corresponde'
            && $contexto['convite_id'] === $convite->id
            // E-mail mascarado nos dois lados: o log de negativa não é lugar de identificar
            // ninguém em claro (config/logging.php:80-81).
            && ! str_contains((string) json_encode($contexto), 'dona@example.test'))
        ->once();
});

/**
 * CT-08 — caixa e espaços não decidem identidade.
 *
 * As duas pontas usam `mb_strtolower(trim(...))`: `exigirDono()`, que compara, e
 * `usuarioExistente()`, que decide a via. Se uma delas perder a normalização, um convite
 * digitado com maiúsculas cria conta DUPLICADA em vez de desviar — e o `unique` de
 * `users.email` não salva, porque as duas strings são diferentes byte a byte.
 */
it('compara e-mail sem depender de caixa', function (): void {
    $carla = User::factory()->create(['email' => 'Carla@Example.test']);

    ofertaPara('  carla@example.TEST  ')->aceitarComoUsuarioExistente($carla);

    expect($carla->fresh()?->hasRole('panel_user'))->toBeTrue();

    // O desvio de `aceitar()` reconhece a mesma conta: devolve a existente, com o mesmo id,
    // e não cria uma segunda linha para o endereço.
    $aceito = ofertaPara(' CARLA@example.test ')->aceitar([
        'name'     => 'Ignorada',
        'password' => 'senha-forte-123',
    ]);

    expect($aceito->getKey())->toBe($carla->getKey())
        ->and(User::whereRaw('lower(email) = ?', ['carla@example.test'])->count())->toBe(1);
});

/**
 * CT-13 — o token PROVA posse do endereço, então a conta nasce verificada.
 *
 * O "dia em que ligar" que esta nota previa **chegou na v0.20**: o `/app` exige e-mail validado
 * quando a opção está ligada, e sem esta linha todo usuário nascido de convite seria barrado na
 * porta sem motivo. Deixou de ser inócuo — quem prova o efeito é
 * `tests/Kit/VerificacaoDeEmailTest.php`, no caso do convidado. A
 * segunda asserção é a que trava o COMO: `email_verified_at` está fora do `$fillable`, logo
 * mass assignment o descartaria em silêncio e só o `forceFill` grava. Ver ADR-06.
 */
it('cria o usuario com o e-mail ja verificado', function (): void {
    $novo = ofertaPara('nova@example.test')->aceitar([
        'name'     => 'Nova',
        'password' => 'senha-forte-123',
    ]);

    expect($novo->email_verified_at)->not->toBeNull()
        ->and($novo->fresh()?->email_verified_at)->not->toBeNull()
        ->and(in_array('email_verified_at', $novo->getFillable(), true))->toBeFalse();
});

/**
 * CT-14 — os quatro estados, derivados de três colunas.
 *
 * `situacao()` vive no model porque DUAS telas o mostram (a do /admin e a do /app), e elas
 * divergiam: a do /app mostrava `aceito_em` com placeholder "Pendente", que mentiria para
 * sempre num convite recusado.
 *
 * A ordem de precedência é o que o dataset trava: aceito vence expirado (um convite aceito
 * ontem não passa a ser "Expirado" hoje), e `expira_em` nulo — convite que nunca foi
 * enviado — falha fechado como expirado.
 */
it('deriva a situacao do convite', function (array $atributos, string $esperado): void {
    expect(ofertaPara('quem@example.test', atributos: $atributos)->situacao())->toBe($esperado);
})->with([
    'pendente'         => [['expira_em' => '+1 day'], 'Pendente'],
    'aceito'           => [['expira_em' => '+1 day', 'aceito_em' => 'now'], 'Aceito'],
    'recusado'         => [['expira_em' => '+1 day', 'recusado_em' => 'now'], 'Recusado'],
    'expirado'         => [['expira_em' => '-1 day'], 'Expirado'],
    'sem envio'        => [['expira_em' => null], 'Expirado'],
    'aceito e vencido' => [['expira_em' => '-1 day', 'aceito_em' => 'now'], 'Aceito'],
]);
