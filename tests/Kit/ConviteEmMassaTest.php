<?php

use App\Filament\Admin\Resources\Convites\Pages\ListConvites;
use App\Models\Convite;
use App\Models\Role;
use App\Models\User;
use App\Notifications\ConviteDeAcesso;
use Database\Seeders\PapeisSeeder;
use Database\Seeders\ShieldPermissionsSeeder;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;

/**
 * Convite em massa — um papel e uma organização para o lote inteiro, com RESULTADO PARCIAL.
 *
 * O que estes casos travam, em ordem de importância: um endereço com problema NUNCA impede os
 * outros (é a feature), o lote não aborta nem quando o envio de um endereço estoura, o token
 * não vai para o log e a ação desaparece para quem não pode criar convite.
 *
 * Os casos com organização (`ja_e_membro`, carimbo do `tenant_id`, trava de papel do /app)
 * estão em `tests/Tenancy/ConviteEmMassaTenancyTest.php`.
 */
beforeEach(function (): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);
});

/** Chama a ação de header do /admin e devolve o Testable. */
function chamarLote(string $emails, ?string $papel = 'panel_user', array $extra = []): Testable
{
    Filament::setCurrentPanel('admin');

    return Livewire::test(ListConvites::class)
        ->callAction(TestAction::make('convidarEmMassa'), [
            'emails'  => $emails,
            'role_id' => Role::findByName((string) $papel)->getKey(),
            ...$extra,
        ]);
}

/** O id do papel do lote, que a maioria dos casos passa direto ao model. */
function papelDoLote(string $papel = 'panel_user'): int
{
    return (int) Role::findByName($papel)->getKey();
}

it('convida todos os enderecos de um lote valido', function (): void {
    Notification::fake();

    $master = usuarioDoKit('master_global');
    $this->actingAs($master);

    chamarLote("um@example.com\ndois@example.com\ntres@example.com")->assertNotified();

    $convites = Convite::all();

    expect($convites)->toHaveCount(3)
        ->and($convites->pluck('role_id')->unique()->all())->toBe([papelDoLote()])
        ->and($convites->pluck('convidado_por_id')->unique()->all())->toBe([$master->id])
        // Token e prazo provam que o laço chamou `enviar()`, e não só gravou a linha.
        ->and($convites->filter(fn (Convite $c): bool => filled($c->token) && ($c->expira_em?->isFuture() ?? false)))
        ->toHaveCount(3);

    Notification::assertSentOnDemandTimes(ConviteDeAcesso::class, 3);
});

/**
 * O caso central: sem ele a feature vira tudo-ou-nada num refactor e nada acusa.
 *
 * `assertHasNoActionErrors()` é a metade que importa — se alguém acrescentar `->email()` ou
 * `->nestedRecursiveRules()` no Textarea "para validar direito", um endereço torto reprova a
 * modal inteira e o resultado parcial morre.
 */
it('envia os validos mesmo com um endereco torto no meio', function (): void {
    Notification::fake();

    $this->actingAs(usuarioDoKit('master_global'));

    chamarLote("um@example.com\nnao-e-email\ntres@example.com")
        ->assertHasNoActionErrors()
        ->assertNotified();

    expect(Convite::count())->toBe(2)
        ->and(Convite::where('email', 'nao-e-email')->doesntExist())->toBeTrue();

    Notification::assertSentOnDemandTimes(ConviteDeAcesso::class, 2);

    // E a forma do retorno, pelo model: duas chaves, sem `total`.
    expect(Convite::convidarEmMassa(Convite::separarEmails("a@b.com\nxxx"), papelDoLote(), null, null))
        ->toBe([
            'enviados' => ['a@b.com'],
            'falhas'   => [['email' => 'xxx', 'motivo' => 'formato_invalido']],
        ]);
});

it('pula endereco que ja tem convite pendente', function (): void {
    Notification::fake();

    $convite = Convite::factory()->create([
        'email'   => 'repetida@example.com',
        'role_id' => papelDoLote(),
    ]);
    $token = $convite->enviar();

    $resultado = Convite::convidarEmMassa(
        Convite::separarEmails("repetida@example.com\nnova@example.com"),
        papelDoLote(),
        null,
        null,
    );

    expect($resultado['enviados'])->toBe(['nova@example.com'])
        ->and($resultado['falhas'])->toBe([['email' => 'repetida@example.com', 'motivo' => 'convite_pendente']])
        ->and(Convite::where('email', 'repetida@example.com')->count())->toBe(1)
        /*
         * O token antigo continua valendo. O lote PULA em vez de chamar `enviar()` de novo,
         * que sobrescreveria a coluna e mataria o link de quem já foi convidado.
         */
        ->and(Convite::valido($token)?->is($convite))->toBeTrue();

    // Já um convite EXPIRADO não bloqueia: é a mesma noção de pendente de `valido()`.
    $convite->forceFill(['expira_em' => now()->subDay()])->save();

    $depois = Convite::convidarEmMassa(
        Convite::separarEmails('repetida@example.com'),
        papelDoLote(),
        null,
        null,
    );

    expect($depois['enviados'])->toBe(['repetida@example.com'])
        ->and($depois['falhas'])->toBeEmpty();
});

/**
 * A maior diferença de comportamento em relação ao `laravel-invite-only`, e só passa porque a
 * wiki `convite-para-usuario-existente` removeu o `unique` dos forms e o `throw` do `aceitar()`.
 */
it('convida quem ja tem conta como oferta de acesso', function (): void {
    Notification::fake();

    User::factory()->create(['email' => 'existente@example.com']);

    $this->actingAs(usuarioDoKit('master_global'));

    chamarLote("existente@example.com\nnova@example.com")->assertNotified();

    expect(Convite::count())->toBe(2);

    Notification::assertSentOnDemandTimes(ConviteDeAcesso::class, 2);

    // Pelo model, para ver a lista de falhas: nenhum motivo `email_ja_cadastrado` existe mais.
    User::factory()->create(['email' => 'terceira@example.com']);

    $resultado = Convite::convidarEmMassa(
        Convite::separarEmails('terceira@example.com'),
        papelDoLote(),
        null,
        null,
    );

    expect($resultado['enviados'])->toBe(['terceira@example.com'])
        ->and($resultado['falhas'])->toBeEmpty();
});

it('recusa o lote inteiro acima do limite sem enviar nada', function (): void {
    Notification::fake();

    // O limite vem de config, então o caso o APERTA em vez de gerar 101 endereços.
    config(['kit.convites.limite_do_lote' => 3]);

    $this->actingAs(usuarioDoKit('master_global'));

    chamarLote("a@example.com\nb@example.com\nc@example.com\nd@example.com")
        ->assertNotified()
        // A modal NÃO fechou: `$action->halt()` interrompeu a ação com o texto colado
        // ainda na tela. É a metade do caso que importa para quem colou cento e vinte linhas.
        ->assertActionMounted('convidarEmMassa');

    expect(Convite::count())->toBe(0);

    Notification::assertNothingSent();

    // Com EXATAMENTE o limite passa: a comparação é `>`, não `>=`.
    chamarLote("a@example.com\nb@example.com\nc@example.com");

    expect(Convite::count())->toBe(3);
});

it('dispara uma notificacao por endereco enviado e nenhuma para os pulados', function (): void {
    $pendente = Convite::factory()->create([
        'email'   => 'repetida@example.com',
        'role_id' => papelDoLote(),
    ]);
    $pendente->enviar();

    // O fake entra DEPOIS do convite pendente, para que a contagem seja só a do lote.
    Notification::fake();

    Convite::convidarEmMassa(
        Convite::separarEmails("repetida@example.com\nnao-e-email\nnova@example.com"),
        papelDoLote(),
        null,
        null,
    );

    Notification::assertSentOnDemandTimes(ConviteDeAcesso::class, 1);
    Notification::assertSentOnDemand(
        ConviteDeAcesso::class,
        fn ($notification, array $channels, $notifiable): bool => $notifiable->routes['mail'] === 'nova@example.com',
    );
});

it('registra o resumo do lote sem vazar endereco nem token', function (): void {
    Notification::fake();

    $canal  = espiarAutenticacao();
    $master = usuarioDoKit('master_global');

    Convite::factory()->create(['email' => 'repetida@example.com', 'role_id' => papelDoLote()])->enviar();

    Convite::convidarEmMassa(
        Convite::separarEmails("repetida@example.com\nnao-e-email\nnova@example.com"),
        papelDoLote(),
        null,
        $master->id,
    );

    $canal->shouldHaveReceived('info')
        ->withArgs(function (string $mensagem, array $contexto) use ($master): bool {
            if (! str_starts_with($mensagem, '[Convite@convidarEmMassa]')) {
                return false;
            }

            $serializado = (string) json_encode($contexto);

            return $contexto['recebidos'] === 3
                && $contexto['enviados'] === 1
                && $contexto['falhas'] === 2
                // O `countBy`, comparado por conteúdo: a ORDEM das chaves segue a ordem em que
                // os motivos apareceram no lote, e não é o que este caso quer travar.
                && $contexto['motivos'] == ['convite_pendente' => 1, 'formato_invalido' => 1]
                && $contexto['convidado_por'] === $master->id
                // Nenhum endereço em claro, nem na lista de falhas — que é onde o descuido é
                // mais provável, porque ela é o produto do método.
                && ! str_contains($serializado, 'nova@example.com')
                && ! str_contains($serializado, 'repetida@example.com')
                && str_contains($serializado, Str::mask('repetida@example.com', '*', 3));
        })
        ->once();
});

/**
 * O caso que existe por causa do defeito do `laravel-invite-only`: se alguém estreitar o
 * `catch (Throwable)` para uma exceção específica, este é o único que fica vermelho.
 *
 * SEM `Notification::fake()` — o envio precisa chegar ao mailer de verdade. `MAIL_MAILER=array`
 * e fila `sync` (phpunit.xml): nada sai da máquina, e a exceção volta pelo
 * `SyncQueue::handleException()`, que relança.
 */
it('segue o lote quando o envio de um endereco lanca excecao', function (): void {
    $canal = espiarAutenticacao();

    Event::listen(function (MessageSending $evento): void {
        $para = $evento->message->getTo()[0]?->getAddress() ?? '';

        if ($para === 'quebra@example.com') {
            throw new RuntimeException('SMTP fora do ar');
        }
    });

    $resultado = Convite::convidarEmMassa(
        Convite::separarEmails("antes@example.com\nquebra@example.com\ndepois@example.com"),
        papelDoLote(),
        null,
        null,
    );

    // O endereço DEPOIS do que estourou é o que prova que o laço continuou.
    expect($resultado['enviados'])->toBe(['antes@example.com', 'depois@example.com'])
        ->and($resultado['falhas'])->toBe([['email' => 'quebra@example.com', 'motivo' => 'erro_no_envio']])
        /*
         * O convite do endereço que falhou EXISTE, pendente e com token: o `create()` e o
         * `forceFill` acontecem antes da notificação. É o failure mode desejado — aparece
         * como Pendente e o `Reenviar` por linha resolve.
         */
        ->and(Convite::where('email', 'quebra@example.com')->first()?->token)->not->toBeEmpty();

    // Nada é engolido em silêncio: o warning leva a exception inteira.
    $canal->shouldHaveReceived('warning')
        ->withArgs(fn (string $mensagem, array $contexto): bool => str_starts_with($mensagem, '[Convite@convidarEmMassa]')
            && $contexto['motivo'] === 'erro_no_envio'
            && $contexto['email'] === Str::mask('quebra@example.com', '*', 3)
            && $contexto['exception'] instanceof Throwable)
        ->once();
});

it('deduplica endereco repetido no proprio texto', function (): void {
    Notification::fake();

    $resultado = Convite::convidarEmMassa(
        Convite::separarEmails("uma@example.com, uma@example.com\nUMA@EXAMPLE.COM"),
        papelDoLote(),
        null,
        null,
    );

    // As três variações juntas: vírgula, quebra de linha e caixa diferente.
    expect($resultado['enviados'])->toBe(['uma@example.com'])
        ->and($resultado['falhas'])->toBeEmpty()
        ->and(Convite::count())->toBe(1)
        ->and(Convite::first()?->email)->toBe('uma@example.com');

    Notification::assertSentOnDemandTimes(ConviteDeAcesso::class, 1);
});

/**
 * As DUAS pontas são obrigatórias: só a metade "escondida" passaria se a ação estivesse
 * escondida para todo mundo — por exemplo com o `->authorize()` apontando para uma habilidade
 * inexistente.
 *
 * A persona que ENXERGA a listagem sem poder criar é um papel de leitura, criado aqui: nenhum
 * papel semeado tem `ViewAny:Convite` sem `Create:Convite`, e o `panel_user` nem abre o /admin
 * (a página responde 403 antes de haver ação para esconder). É a diferença entre provar o
 * `->authorize()` da ação e provar de novo o acesso ao painel, que `PaineisTest` já cobre.
 */
it('esconde a acao de lote de quem nao pode criar convite', function (): void {
    $leitura = Role::create(['name' => 'leitor_de_convites', 'guard_name' => 'web', 'painel' => 'admin']);
    $leitura->syncPermissions(['ViewAny:Convite', 'View:Convite']);

    $leitor = usuarioDoKit('leitor_de_convites', 'leitor@example.com');
    $admin  = usuarioDoKit('admin', 'admin@example.com');

    Filament::setCurrentPanel('admin');

    $this->actingAs($admin);
    Livewire::test(ListConvites::class)->assertActionVisible('convidarEmMassa');

    $this->actingAs($leitor);
    Livewire::test(ListConvites::class)->assertActionHidden('convidarEmMassa');

    // A tela reflete a permission, e não uma condição inventada.
    expect($leitor->can('viewAny', Convite::class))->toBeTrue()
        ->and($leitor->can('create', Convite::class))->toBeFalse()
        ->and($admin->can('create', Convite::class))->toBeTrue()
        // E o usuário comum do negócio, que é quem a subtração do painel `app` mantém fora.
        ->and(usuarioDoKit('panel_user', 'comum@example.com')->can('create', Convite::class))->toBeFalse();
});

it('nao reconvida pelo lote quem recusou antes', function (): void {
    Notification::fake();

    $recusado = Convite::factory()->create([
        'email'   => 'recusou@example.com',
        'role_id' => papelDoLote(),
    ]);
    $recusado->forceFill(['recusado_em' => now()])->save();

    $resultado = Convite::convidarEmMassa(
        Convite::separarEmails("recusou@example.com\nnova@example.com"),
        papelDoLote(),
        null,
        null,
    );

    expect($resultado['falhas'])->toBe([['email' => 'recusou@example.com', 'motivo' => 'recusou_antes']])
        ->and(Convite::where('email', 'recusou@example.com')->count())->toBe(1);

    /*
     * E o convite INDIVIDUAL continua podendo: o model permite, o LOTE é que não faz
     * automaticamente. É a metade que resolve a contradição aparente com a wiki irmã — o lote
     * é broadcast, e broadcast não é a ferramenta para insistir com quem disse não.
     */
    $novo      = Convite::create(['email' => 'recusou@example.com', 'role_id' => papelDoLote()]);
    $tokenNovo = $novo->enviar();

    expect(Convite::valido($tokenNovo)?->is($novo))->toBeTrue();
});

it('separa e normaliza os enderecos do texto', function (?string $texto, array $esperado): void {
    expect(Convite::separarEmails($texto)->all())->toBe($esperado);
})->with([
    'quebra de linha' => ["a@x.com\nb@x.com", ['a@x.com', 'b@x.com']],
    'virgula'         => ['a@x.com, b@x.com', ['a@x.com', 'b@x.com']],
    'ponto e virgula' => ['a@x.com;b@x.com', ['a@x.com', 'b@x.com']],
    'espaco e tab'    => ["a@x.com \t b@x.com", ['a@x.com', 'b@x.com']],
    // Normalização em minúsculas E deduplicação depois dela.
    'caixa e espacos' => ["  A@X.com \n a@x.COM  ", ['a@x.com']],
    'linhas vazias'   => ["a@x.com\n\n\nb@x.com\n", ['a@x.com', 'b@x.com']],
    'vazio'           => ['', []],
    // A assinatura aceita `?string`: o parser não pode estourar antes de o `->required()`
    // do campo falar.
    'nulo' => [null, []],
]);
