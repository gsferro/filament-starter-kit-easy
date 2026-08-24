<?php

use App\Filament\Admin\Resources\Convites\Pages\CreateConvite;
use App\Filament\App\Pages\ConvitesRecebidos;
use App\Models\Convite;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\ConviteDeAcesso;
use Database\Seeders\PapeisSeeder;
use Database\Seeders\ShieldPermissionsSeeder;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Psr\Log\LoggerInterface;

/**
 * Convite para quem JÁ TEM CONTA, com a tenancy ligada — a via de OFERTA DE ACESSO.
 *
 * A feature existe para vincular alguém a uma ORGANIZAÇÃO: sem tenancy o convite não tem a
 * que vincular, então a suíte principal é esta. O que é independente do modo (a asserção de
 * identidade, a normalização do e-mail, `situacao()`) está em
 * `tests/Kit/ConviteUsuarioExistenteTest.php`.
 *
 * ## Como o token chega à tela
 *
 * Por QUERY STRING, sempre — nunca pelo construtor do componente. `RegistroPorConvite::mount()`
 * é `mount(): void` e lê `request()->query('token')`, então `livewire(…, ['token' => …])` não
 * teria onde entregar o valor. Nos casos abaixo o caminho é `$this->get('/app/register?token=…')`,
 * porque as três saídas do desvio são REDIRECTS (`HttpResponseException`) e é o request HTTP
 * que as expõe; para exercitar o formulário dentro do componente, o kit usa
 * `Livewire::withQueryParams(['token' => …])->test(...)` — ver `aceitarConvite()` em
 * `tests/Kit/ConviteTest.php`.
 *
 * Os helpers `tenant()`, `usuario()`, `usuarioComPapel()` e `papelNaOrganizacao()` vêm de
 * `tests/Pest.php` — inclusive `pivotDePapeis()` e `noPainelDa()`, que antes viviam em
 * `AdminDaOrganizacaoTest.php` — o Pest carrega os arquivos da suíte inteira, então rode a
 * pasta, não um arquivo só.
 */
beforeEach(function (): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);
});

/** A consultora do caso de uso: já tem conta e já é usuária de OUTRA organização. */
function carlaDaGlobex(Tenant $globex): User
{
    $carla = usuarioComPapel('panel_user', $globex, 'carla@example.test');

    $carla->tenants()->attach($globex);

    return $carla;
}

/*
|--------------------------------------------------------------------------
| CT-01 e CT-17 — o convite passa a ser criável
|--------------------------------------------------------------------------
*/

/**
 * CT-01 — até a v0.11.0 o `->unique('users', 'email')` do formulário recusava aqui, e era
 * essa linha que fazia do convite uma parede no caso mais comum de SaaS multi-tenant.
 */
it('cria convite para e-mail que ja tem conta', function (): void {
    Notification::fake();

    $acme = tenant('Acme', 'acme');
    usuario('ja@example.test');

    Filament::setCurrentPanel('admin');
    $this->actingAs(usuarioComPapel('master_global'));

    Livewire::test(CreateConvite::class)
        ->fillForm([
            'email'     => 'ja@example.test',
            'role_id'   => Role::findByName('panel_user')->getKey(),
            'tenant_id' => $acme->getKey(),
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('convites', ['email' => 'ja@example.test', 'tenant_id' => $acme->id]);

    Notification::assertSentOnDemand(ConviteDeAcesso::class);
});

/**
 * CT-17 — é a razão de a feature existir.
 *
 * Antes dela o `admin_app` não tinha NENHUM caminho para trazer alguém que já tem
 * conta: só o `master_global`, por /admin → Organizações → Vincular usuário. E o
 * `tenant_id` continua carimbado à força pelo painel (barreira 6 da wiki
 * `admin-da-organizacao`), então o convite nasce na Acme mesmo com a Globex forjada no
 * payload.
 */
it('deixa o admin da organizacao convidar quem ja tem conta', function (): void {
    Notification::fake();

    $acme   = tenant('Acme', 'acme');
    $globex = tenant('Globex', 'globex');

    $ana = usuarioComPapel('admin_app', $acme, 'ana@example.test');
    $ana->tenants()->attach($acme);

    carlaDaGlobex($globex);

    noPainelDa($acme);
    $this->actingAs($ana);

    Livewire::test(App\Filament\App\Resources\Convites\Pages\CreateConvite::class)
        ->fillForm([
            'email'     => 'carla@example.test',
            'role_id'   => Role::findByName('panel_user')->getKey(),
            'tenant_id' => $globex->getKey(),
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('convites', ['email' => 'carla@example.test', 'tenant_id' => $acme->id]);
    $this->assertDatabaseMissing('convites', ['tenant_id' => $globex->id]);
});

/*
|--------------------------------------------------------------------------
| CT-02, CT-06, CT-07 — o aceite
|--------------------------------------------------------------------------
*/

/**
 * CT-02 — aceitar na Acme não mexe na Globex, e ninguém nasce de novo.
 *
 * As duas últimas asserções são o que separa "vincular" de "criar": mesmo id, uma linha só
 * para o endereço. Uma implementação que "resolvesse" o e-mail duplicado criando um segundo
 * usuário passaria em todo o resto.
 */
it('vincula usuario existente a organizacao do convite', function (): void {
    Notification::fake();

    $acme      = tenant('Acme', 'acme');
    $globex    = tenant('Globex', 'globex');
    $panelUser = Role::findByName('panel_user');

    $carla = carlaDaGlobex($globex);
    $token = ofertaPara('carla@example.test', $acme)->enviar();

    $this->actingAs($carla)
        ->get("/app/register?token={$token}")
        ->assertRedirectContains('/app/acme');

    $this->assertDatabaseHas('tenant_user', ['user_id' => $carla->id, 'tenant_id' => $acme->id]);
    $this->assertDatabaseHas(pivotDePapeis(), [
        'model_id' => $carla->id,
        'role_id'  => $panelUser->getKey(),
        'team_id'  => $acme->id,
    ]);

    // A Globex continua intacta, nos dois lados: vínculo e papel. É o ponto da feature.
    $this->assertDatabaseHas('tenant_user', ['user_id' => $carla->id, 'tenant_id' => $globex->id]);
    $this->assertDatabaseHas(pivotDePapeis(), [
        'model_id' => $carla->id,
        'role_id'  => $panelUser->getKey(),
        'team_id'  => $globex->id,
    ]);

    expect(Convite::valido($token))->toBeNull()
        ->and(User::whereRaw('lower(email) = ?', ['carla@example.test'])->count())->toBe(1)
        ->and(User::where('email', 'carla@example.test')->value('id'))->toBe($carla->id);
});

/**
 * CT-06 — o consumo é atômico, e é aqui que esta via difere da de conta nova.
 *
 * Lá o `unique` de `users.email` aborta o segundo aceite concorrente. Aqui não existe unique
 * que salve: `syncWithoutDetaching` e `assignRole` são idempotentes, então um consumo
 * check-then-act (o desenho do `laravel-invite-only`) passaria as duas vezes e atribuiria o
 * papel duas vezes. A segunda chamada usa a MESMA instância em memória — é o que simula a
 * segunda requisição que já passou pelo próprio check. Ver ADR-04.
 */
it('consome a oferta uma unica vez', function (): void {
    $acme      = tenant('Acme', 'acme');
    $panelUser = Role::findByName('panel_user');
    $carla     = usuario('carla@example.test');

    $convite = ofertaPara('carla@example.test', $acme);

    $convite->aceitarComoUsuarioExistente($carla);

    expect(fn (): User => $convite->aceitarComoUsuarioExistente($carla))
        ->toThrow(RuntimeException::class, 'Este convite já foi usado.');

    expect(DB::table(pivotDePapeis())
        ->where('model_id', $carla->id)
        ->where('role_id', $panelUser->getKey())
        ->where('team_id', $acme->id)
        ->count())->toBe(1)
        ->and(DB::table('tenant_user')->where('user_id', $carla->id)->count())->toBe(1);
});

/**
 * CT-07 — reconvite de quem já é membro.
 *
 * `syncWithoutDetaching` e não `attach`: o unique de `tenant_user` estouraria. E o papel novo
 * ENTRA sem derrubar o antigo — `assignRole()` acrescenta, o que é o comportamento certo para
 * quem foi promovido por convite.
 */
it('aceita oferta de quem ja e membro sem duplicar vinculo', function (): void {
    $acme  = tenant('Acme', 'acme');
    $carla = usuarioComPapel('panel_user', $acme, 'carla@example.test');
    $carla->tenants()->attach($acme);

    ofertaPara('carla@example.test', $acme, 'admin_app')
        ->aceitarComoUsuarioExistente($carla);

    expect(DB::table('tenant_user')->where('user_id', $carla->id)->count())->toBe(1);

    $this->assertDatabaseHas(pivotDePapeis(), [
        'model_id' => $carla->id,
        'role_id'  => Role::findByName('admin_app')->getKey(),
        'team_id'  => $acme->id,
    ]);
});

/*
|--------------------------------------------------------------------------
| CT-05 e CT-15 — a caixa de entrada e o item de menu
|--------------------------------------------------------------------------
*/

/**
 * CT-05 — a lista é escopada pelo e-mail autenticado.
 *
 * Filtro de UI, e não a barreira: quem barra é a asserção do model (CT-04 da suíte do kit).
 * `loadTable()` antes das asserções porque `ConfiguraFilamentGlobal` liga `deferLoading()`
 * em toda tabela do kit — sem ele o HTML testado é o do esqueleto.
 */
it('lista apenas as ofertas do proprio e-mail', function (): void {
    $acme   = tenant('Acme', 'acme');
    $globex = tenant('Globex', 'globex');

    $carla = carlaDaGlobex($globex);

    $daCarla = ofertaPara('carla@example.test', $acme);
    $daOutra = ofertaPara('outra@example.test', $acme);

    noPainelDa($globex);
    $this->actingAs($carla);

    Livewire::test(ConvitesRecebidos::class)
        ->loadTable()
        ->assertCanSeeTableRecords([$daCarla])
        ->assertCanNotSeeTableRecords([$daOutra]);
});

/**
 * CT-15 — a MESMA query alimenta a tabela e o badge.
 *
 * Duas cópias divergiriam, e a que divergisse seria o contador dizendo "1" numa tela vazia.
 * O item só aparece quando há algo a decidir: badge zero é ruído.
 */
it('conta as ofertas pendentes no menu do usuario', function (): void {
    $acme   = tenant('Acme', 'acme');
    $globex = tenant('Globex', 'globex');

    $carla = carlaDaGlobex($globex);

    ofertaPara('carla@example.test', $acme);
    ofertaPara('carla@example.test', $globex);
    ofertaPara('carla@example.test', $acme, atributos: ['aceito_em' => 'now']);
    ofertaPara('carla@example.test', $acme, atributos: ['recusado_em' => 'now']);

    // `noPainelDa()` e nao `setCurrentPanel('app')`: o item de menu consulta
    // `ConvitesRecebidos::canAccess()`, que agora exige `View:ConvitesRecebidos`, e com
    // `permission.teams` ligado a relacao `roles` do spatie e filtrada pelo team corrente. Sem
    // fixar o team, `can()` nao acha papel nenhum e o item some — medindo o arranjo, nao a regra.
    // E o que o middleware `DefinirTenantDePermissoes` faz num request real.
    noPainelDa($globex);
    $this->actingAs($carla);

    // O badge sai do Filament como string — o cast é o que deixa a asserção falar de
    // CONTAGEM, e não do tipo com que a view a imprime.
    expect(Convite::pendentesPara($carla)->count())->toBe(2)
        ->and((int) ConvitesRecebidos::itemDeMenu()->getBadge())->toBe(2)
        ->and(ConvitesRecebidos::itemDeMenu()->isVisible())->toBeTrue();

    // Sem oferta nenhuma o item desaparece, em vez de mostrar um zero.
    $sozinho = usuarioComPapel('panel_user', $globex, 'sozinho@example.test');
    $this->actingAs($sozinho);

    expect(Convite::pendentesPara($sozinho)->count())->toBe(0)
        ->and(ConvitesRecebidos::itemDeMenu()->getBadge())->toBeNull()
        ->and(ConvitesRecebidos::itemDeMenu()->isVisible())->toBeFalse();
});

/*
|--------------------------------------------------------------------------
| CT-09 e CT-10 — a recusa
|--------------------------------------------------------------------------
*/

/**
 * CT-09 — recusar REGISTRA, em vez de apagar a linha (que é o que o teamkit faz).
 *
 * "Ela disse não" é informação diferente de "o convite desapareceu", e é o que impede
 * reconvidar quem já recusou. `warning` e não `info`: recusa não é falha, mas é o fim de uma
 * concessão de acesso — e é no nível de aviso que se procura por isso no log.
 */
it('registra a recusa e invalida o convite', function (): void {
    Notification::fake();

    $canal = Mockery::spy(LoggerInterface::class);
    Log::shouldReceive('channel')->with('autenticacao')->andReturn($canal);

    $acme  = tenant('Acme', 'acme');
    $carla = usuario('carla@example.test');

    $convite = ofertaPara('carla@example.test', $acme);
    $token   = $convite->enviar();

    $convite->recusar($carla);

    expect($convite->fresh()?->recusado_em)->not->toBeNull()
        ->and($convite->fresh()?->situacao())->toBe('Recusado')
        // Recusado não volta a valer nem pelo link, e é `valido()` que fecha a porta.
        ->and(Convite::valido($token))->toBeNull();

    $this->assertDatabaseMissing('tenant_user', ['user_id' => $carla->id, 'tenant_id' => $acme->id]);

    $canal->shouldHaveReceived('warning')
        ->withArgs(fn (string $mensagem, array $contexto): bool => str_starts_with($mensagem, '[Convite@recusar]')
            && $contexto['convite_id'] === $convite->id
            && $contexto['user_id'] === $carla->id)
        ->once();
});

/** CT-10 — o link de um convite recusado responde como qualquer outro inválido. */
it('nao aceita convite recusado nem pelo link', function (): void {
    Notification::fake();

    $acme  = tenant('Acme', 'acme');
    $carla = usuario('carla@example.test');

    $convite = ofertaPara('carla@example.test', $acme);
    $token   = $convite->enviar();

    $convite->recusar($carla);

    $this->actingAs($carla)
        ->get("/app/register?token={$token}")
        ->assertRedirect(Filament::getPanel('app')->getLoginUrl());

    $this->assertDatabaseMissing('tenant_user', ['user_id' => $carla->id, 'tenant_id' => $acme->id]);

    expect($convite->fresh()?->aceito_em)->toBeNull();
});

/*
|--------------------------------------------------------------------------
| CT-11 e CT-12 — o link nas mãos erradas
|--------------------------------------------------------------------------
*/

/**
 * CT-11 — é o caso que o `laravel-invite-only` erra.
 *
 * Lá o `accept()` não compara e-mail nenhum, então o Bruno entraria na Acme com o papel do
 * convite da Carla. Aqui ele não entra, o convite da Carla NÃO é queimado, e a sessão do
 * Bruno continua de pé: derrubar o login de alguém por causa de um link é pior que explicar.
 */
it('nao vincula quando o link e aberto por outra conta', function (): void {
    Notification::fake();

    $acme   = tenant('Acme', 'acme');
    $globex = tenant('Globex', 'globex');

    usuario('carla@example.test');

    $bruno = usuarioComPapel('panel_user', $globex, 'bruno@example.test');
    $bruno->tenants()->attach($globex);

    $convite = ofertaPara('carla@example.test', $acme);
    $token   = $convite->enviar();

    $this->actingAs($bruno)
        ->get("/app/register?token={$token}")
        ->assertRedirect();

    $this->assertDatabaseMissing('tenant_user', ['user_id' => $bruno->id, 'tenant_id' => $acme->id]);

    // Nem o papel: em nenhum contexto o Bruno ganha o que o convite da Carla concede.
    $this->assertDatabaseMissing(pivotDePapeis(), ['model_id' => $bruno->id, 'team_id' => $acme->id]);

    // E o convite da Carla NÃO foi queimado por alguém que abriu o link dela.
    expect($convite->fresh()?->aceito_em)->toBeNull();

    $this->assertAuthenticatedAs($bruno);
});

/**
 * CT-12 — visitante vai ao login, e o convite continua pendente.
 *
 * Nenhum formulário de registro é exibido para um e-mail que já tem conta, e o token NÃO é
 * consumido no caminho: o aceite é ato explícito, feito autenticado. Depois do login a oferta
 * aparece no item de menu (CT-15).
 */
it('manda visitante ao login sem consumir a oferta', function (): void {
    Notification::fake();

    $acme  = tenant('Acme', 'acme');
    $carla = usuario('carla@example.test');

    $convite = ofertaPara('carla@example.test', $acme);
    $token   = $convite->enviar();

    $usuariosAntes = User::count();

    $this->get("/app/register?token={$token}")
        ->assertRedirect(Filament::getPanel('app')->getLoginUrl());

    expect($convite->fresh()?->aceito_em)->toBeNull()
        ->and(User::count())->toBe($usuariosAntes);

    $this->assertDatabaseMissing('tenant_user', ['user_id' => $carla->id, 'tenant_id' => $acme->id]);
    $this->assertGuest();
});

/*
|--------------------------------------------------------------------------
| CT-16 — o e-mail
|--------------------------------------------------------------------------
*/

/**
 * CT-16 — dois textos, UMA classe.
 *
 * SEM `Notification::fake()` de propósito: é o único caso em que `toMail()` realmente
 * renderiza, e o mailer do `phpunit.xml` é `array` — nada sai da máquina. Quem já tem conta
 * não vai "criar uma senha": dizer o contrário faz a pessoa procurar um formulário que a tela
 * não mostra. O botão aponta para o MESMO link das duas vias, e o token não aparece em
 * lugar nenhum fora dessa URL.
 */
it('manda texto de oferta para quem ja tem conta', function (): void {
    $acme = tenant('Acme', 'acme');
    usuario('carla@example.test');

    $token = ofertaPara('carla@example.test', $acme)->enviar();

    // Decodificado porque quoted-printable quebra linha a cada 76 colunas, e a quebra cairia
    // no meio das frases (e no meio do token) sem nenhuma relação com o conteúdo.
    $corpo = quoted_printable_decode(
        (string) Mail::mailer()->getSymfonyTransport()->messages()->first()?->getOriginalMessage()->toString()
    );

    expect($corpo)->toContain('Entrar e aceitar')
        ->and($corpo)->toContain('Entre com a sua senha')
        ->and($corpo)->toContain('você também pode recusar')
        ->and($corpo)->not->toContain('Ao aceitar, você escolhe a sua senha')
        ->and($corpo)->toContain('/app/register?token='.$token)
        // Toda ocorrência do token está DENTRO da URL do botão: nenhuma linha do corpo o
        // exibe solto, que é como uma credencial vaza em print de tela e em suporte.
        ->and(substr_count($corpo, 'token='.$token))->toBe(substr_count($corpo, $token));
});
