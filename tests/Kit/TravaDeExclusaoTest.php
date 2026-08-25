<?php

use App\Filament\Admin\Resources\Users\UserResource as UserDoAdmin;
use App\Filament\App\Resources\Convites\ConviteResource as ConviteDoApp;
use App\Filament\App\Resources\Users\Pages\ListUsers;
use App\Filament\App\Resources\Users\UserResource as UserDoApp;
use App\Models\Convite;
use Database\Seeders\PapeisSeeder;
use Database\Seeders\ShieldPermissionsSeeder;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Spatie\Permission\PermissionRegistrar;

/**
 * O achado F-01 da auditoria do Filament Blueprint: a trava de exclusão que não travava.
 *
 * ## O que estes casos medem, e por que NÃO medem a tela
 *
 * O jeito natural de testar "não pode excluir" numa tela Filament é abrir a tabela e afirmar que a
 * ação não está lá. Esse teste **fica verde com o defeito presente** — porque o defeito ERA a
 * ausência do botão fazendo o papel de autorização. Ele mediria a barreira errada.
 *
 * ## E por que um dos casos passa pelo framework de verdade
 *
 * A primeira versão deste arquivo tinha só chamadas estáticas ao método que eu ACREDITO ser
 * consultado. A revisão adversarial apontou que isso reproduz o próprio F-01 um nível acima: um
 * override cuja consulta ninguém prova. CT-03B resolve — ele chama
 * `getDefaultActionAuthorizationResponse()` da página real com uma `DeleteAction` real, que é o
 * caminho de `Resources/Pages/Page.php:313`. Se um upgrade do Filament mudar esse mapeamento, é
 * ele que fica vermelho.
 *
 * Ver `wikis/specs/feat/auditoria-de-seguranca/travas-de-exclusao-e-upload-anonimo/`.
 */
beforeEach(function (): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);

    /*
     * Fixar o painel não é cerimônia: `Filament::getCurrentPanel()` VAZA entre arquivos de teste.
     * Medido durante a mutação — um caso deste arquivo rodou com o painel `infra` corrente, herdado
     * de outra suíte, e por isso ficou verde com a correção removida. Sem esta linha, a
     * sensibilidade dos casos depende da ordem de execução, que é o pior tipo de teste verde.
     *
     * O caso do /admin troca o painel por conta própria, e é o único que precisa disso.
     */
    Filament::setCurrentPanel('app');
});

/*
|--------------------------------------------------------------------------
| R1 — a exclusão de usuário no /app é negada pelo método que o Filament consulta
|--------------------------------------------------------------------------
*/

/**
 * ## O arranjo dos casos de negação, e ele custou uma mutação para acertar
 *
 * A primeira versão não autenticava ninguém. A mutação — remover o par de métodos do resource —
 * mostrou o problema: sem ator autenticado, `getDeleteAuthorizationResponse()` cai na policy, a
 * policy pede `Delete:User`, ninguém tem, e a resposta vem **negada de qualquer jeito**. Três dos
 * quatro casos ficavam verdes com a correção removida. Verdes pelo motivo errado.
 *
 * Autenticar quem TEM a permissão inverte isso: sem a nossa negação a policy PERMITE, e o caso
 * fica vermelho. É a única arrumação em que a única variável é a trava do resource — e é por isso
 * que todo caso de negação abaixo começa por um controle positivo.
 */

/**
 * CT-01 — a resposta é negada, e com motivo de verdade.
 *
 * `trim()` na mensagem, e não `not->toBeEmpty()`: a revisão adversarial mostrou que
 * `Response::deny(' ')` passaria pela versão anterior deste caso — 403 mudo com outra roupa.
 */
it('nega a exclusao de usuario no /app para quem tem a permissao', function (): void {
    $ator = usuario('ator@example.com');
    $ator->givePermissionTo('Delete:User');
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    $alvo = usuario('alvo@example.com');

    $this->actingAs($ator);

    expect($ator->fresh()?->can('Delete:User'))->toBeTrue('Controle positivo.');

    $resposta = UserDoApp::getDeleteAuthorizationResponse($alvo);

    expect($resposta->denied())->toBeTrue(
        'A negação é do PAINEL, não da matriz: o ator tem Delete:User e ainda assim não passa.'
    )
        ->and(trim((string) $resposta->message()))->not->toBe('',
            'Negar sem mensagem dá 403 mudo — mesma segurança, pior experiência.'
        );
})->group('kit');

/**
 * CT-02 — a exclusão em MASSA resolve por outro método, e precisa do seu próprio caso.
 *
 * `DeleteBulkAction` cai em `getDeleteAnyAuthorizationResponse()` (`Page.php:329`), não no par por
 * registro. A mensagem é afirmada aqui também: a revisão apontou que sem isso o mutante "negar sem
 * mensagem" sobrevivia no verbo irmão.
 */
it('nega a exclusao em massa de usuario no /app para quem tem a permissao', function (): void {
    $ator = usuario('ator@example.com');
    $ator->givePermissionTo('DeleteAny:User');
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $this->actingAs($ator);

    expect($ator->fresh()?->can('DeleteAny:User'))->toBeTrue('Controle positivo.');

    $resposta = UserDoApp::getDeleteAnyAuthorizationResponse();

    expect($resposta->denied())->toBeTrue(
        'A DeleteBulkAction autoriza por getDeleteAnyAuthorizationResponse(), não pelo par por registro.'
    )->and(trim((string) $resposta->message()))->not->toBe('');
})->group('kit');

/**
 * CT-03 — negado mesmo para quem TEM a permissão na matriz.
 *
 * Separa "negado por falta de permissão" de "negado porque este painel proíbe". A primeira
 * asserção é controle positivo: sem ela, o caso ficaria verde num sistema onde a negação viesse só
 * do Shield, e a trava do resource poderia sair sem nada apitar.
 */
it('nega a exclusao no /app mesmo para quem tem a permissao Delete:User', function (): void {
    $comPermissao = usuario('com.permissao@example.com');
    $comPermissao->givePermissionTo('Delete:User');
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    $alvo = usuario('alvo@example.com');

    $this->actingAs($comPermissao);

    expect($comPermissao->fresh()?->can('Delete:User'))->toBeTrue(
        'Controle positivo: sem a permissão concedida, este caso não discrimina nada.'
    )->and(UserDoApp::getDeleteAuthorizationResponse($alvo)->denied())->toBeTrue(
        'A negação é do PAINEL, não da matriz de permissões.'
    );
})->group('kit');

/**
 * CT-03B — o mapeamento do FRAMEWORK, e o não-efeito.
 *
 * É o caso que fixa a premissa da entrega inteira: que a `DeleteAction` autoriza por
 * `getDeleteAuthorizationResponse()` e a `DeleteBulkAction` por `getDeleteAnyAuthorizationResponse()`.
 * Ele não chama o nosso método — chama o do Filament, que escolhe qual dos nossos consultar.
 *
 * A última asserção é o não-efeito que a linha F da varredura SFDIPOT exige: recusa que grava antes
 * de recusar não é recusa.
 */
it('recusa a DeleteAction e a DeleteBulkAction pelo caminho real do Filament', function (): void {
    $ator = usuario('ator@example.com');
    $ator->givePermissionTo(['Delete:User', 'DeleteAny:User']);
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    $alvo = usuario('alvo@example.com');

    $this->actingAs($ator);

    $pagina = new ListUsers;

    $porRegistro = $pagina->getDefaultActionAuthorizationResponse(
        DeleteAction::make()->record($alvo)
    );
    $emMassa = $pagina->getDefaultActionAuthorizationResponse(DeleteBulkAction::make());

    expect($porRegistro?->denied())->toBeTrue(
        'A DeleteAction resolve por Page::getDefaultActionAuthorizationResponse (Page.php:313). '
        .'Se este caso ficar verde e os outros vermelhos, o mapeamento do Filament mudou.'
    )->and($emMassa?->denied())->toBeTrue(
        'A DeleteBulkAction resolve pelo par *Any (Page.php:329).'
    )->and($alvo->fresh())->not->toBeNull(
        'Não-efeito: resolver a autorização não pode tocar o registro.'
    );
})->group('kit');

/*
|--------------------------------------------------------------------------
| R2 — a exclusão de convite no /app é negada do mesmo modo
|--------------------------------------------------------------------------
*/

/**
 * CT-04 — o segundo resource, que é a omissão real possível: corrigir um e esquecer o outro.
 *
 * Separado do CT-04B por operação, e não fundido como na primeira versão: a revisão apontou que
 * juntar as duas num cenário faz a falha não dizer se faltou o resource ou faltou o verbo.
 */
it('nega a exclusao de convite no /app para quem tem a permissao', function (): void {
    $ator = usuario('ator@example.com');
    $ator->givePermissionTo('Delete:Convite');
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    $this->actingAs($ator);

    expect($ator->fresh()?->can('Delete:Convite'))->toBeTrue('Controle positivo.');

    $organizacao = tenant('Acme', 'acme');
    $convite     = Convite::factory()->create(['tenant_id' => $organizacao->getKey()]);

    $resposta = ConviteDoApp::getDeleteAuthorizationResponse($convite);

    expect($resposta->denied())->toBeTrue('Convite não se exclui a partir da organização.')
        ->and(trim((string) $resposta->message()))->not->toBe('')
        ->and($convite->fresh())->not->toBeNull('Não-efeito.');
})->group('kit');

/** CT-04B — o verbo irmão do convite, que é o que a DeleteBulkAction consulta. */
it('nega a exclusao em massa de convite no /app para quem tem a permissao', function (): void {
    $ator = usuario('ator@example.com');
    $ator->givePermissionTo('DeleteAny:Convite');
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    $this->actingAs($ator);

    expect($ator->fresh()?->can('DeleteAny:Convite'))->toBeTrue('Controle positivo.');

    $resposta = ConviteDoApp::getDeleteAnyAuthorizationResponse();

    expect($resposta->denied())->toBeTrue()
        ->and(trim((string) $resposta->message()))->not->toBe('');
})->group('kit');

/*
|--------------------------------------------------------------------------
| R3 — a negação é LOCAL ao painel
|--------------------------------------------------------------------------
*/

/**
 * CT-05 — o caso que reprova a correção "óbvia", e a persona aqui é a decisão.
 *
 * A correção tentadora para o achado é negar na `UserPolicy::delete()`, e ela passaria em CT-01 a
 * CT-04B inteiros. Este é o único caso que a reprova.
 *
 * **A persona NÃO é o `master_global`.** A primeira versão usava, e a revisão adversarial mostrou
 * que o caso não provava nada: o `master_global` vence por `Gate::before`
 * (`app/Models/Role.php:13`), então a resposta seria `allowed()` mesmo com a negação plantada na
 * policy. A persona discriminante é o papel `admin`, que decide **pela matriz**.
 */
it('mantem a exclusao de usuario permitida no /admin', function (): void {
    $admin = usuarioDoKit('admin', 'admin@example.com');
    $alvo  = usuario('alvo@example.com');

    $this->actingAs($admin);
    noPainelBootado('admin');

    expect($admin->fresh()?->isMasterGlobal())->toBeFalse(
        'Persona discriminante: o master_global vence por Gate::before e mascararia uma negação '
        .'plantada na policy.'
    )->and($admin->fresh()?->can('Delete:User'))->toBeTrue(
        'Controle positivo: o papel admin decide pela matriz, e a matriz tem a permissão.'
    )->and(UserDoAdmin::getDeleteAuthorizationResponse($alvo)->allowed())->toBeTrue(
        'A proibição pertence ao painel de negócio. Negar na policy quebraria a administração global.'
    );
})->group('kit');
