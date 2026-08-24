<?php

use App\Filament\Admin\Pages\ConfiguracoesDoKit;
use App\Settings\ConfiguracoesDoKit as SettingsDoKit;
use App\Support\CorPrimaria;
use App\Support\CustomizadorDaInstalacao;
use Database\Seeders\PapeisSeeder;
use Database\Seeders\ShieldPermissionsSeeder;
use Filament\Facades\Filament;
use Filament\Support\Colors\Color;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\LaravelSettings\Models\SettingsProperty;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * A tela /admin/configuracoes-do-kit — gravação, validação e autorização.
 *
 * IDs de CT em `wikis/specs/feat/settings-do-kit/settings-do-kit/04-casos-de-teste.md`.
 *
 * Teste de COMPONENTE, não de navegador: validação, gravação, autorização na tela
 * e notificação são Livewire e rodam em milissegundos. O que sobrou para o
 * navegador está em `tests/Browser/ConfiguracoesDoKitTest.php`, e é só o que só
 * ele prova — a troca de aba e o erro num campo de aba não-ativa.
 */
beforeEach(function (): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);

    Filament::setCurrentPanel('admin');
});

/**
 * CT-08 — o formulário grava as quatro famílias de campo de uma vez.
 *
 * É a gravação por componente que a regra do par exige (*uma tela aberta não é
 * uma tela que grava*): o `GET` da tela pode ficar verde com o `save()` quebrado,
 * e foi assim que `Select::make('roles')` derrubou o salvamento de usuários com a
 * listagem verde.
 *
 * Um `Quando` só, e é um salvamento único de propósito — preencher e salvar é uma
 * ação. As asserções afirmam o GRUPO `kit`, não só o valor: gravar no grupo errado
 * deixaria a tela funcionando e o alinhamento do boot sem achar nada.
 *
 * Os tipos são asseridos com `toBe`: `"25"` e `"true"` como string satisfazem
 * comparação frouxa e quebram `defaultPaginationPageOption()` e todo `===`.
 */
it('grava as quatro familias de campo pelo formulario', function (): void {
    $this->actingAs(usuarioDoKit('admin'));

    Livewire::test(ConfiguracoesDoKit::class)
        ->fillForm([
            'nome_da_aplicacao'       => 'Projeto Novo',
            'cor_primaria'            => 'Emerald',
            'cor_primaria_hex'        => '#7c3aed',
            'mail_from_address'       => 'contato@exemplo.test',
            'paginacao_padrao'        => 25,
            'hub_de_navegacao'        => true,
            'rotulo_da_organizacao'   => 'Empresa',
            'rotulo_das_organizacoes' => 'Empresas',
        ])
        ->call('save')
        ->assertHasNoFormErrors()
        ->assertNotified();

    expect(configuracaoGravada('nome_da_aplicacao'))->toBe('Projeto Novo')
        ->and(configuracaoGravada('cor_primaria'))->toBe('Emerald')
        ->and(configuracaoGravada('cor_primaria_hex'))->toBe('#7c3aed')
        ->and(configuracaoGravada('mail_from_address'))->toBe('contato@exemplo.test')
        ->and(configuracaoGravada('paginacao_padrao'))->toBe(25)
        ->and(configuracaoGravada('hub_de_navegacao'))->toBeTrue()
        ->and(configuracaoGravada('rotulo_das_organizacoes'))->toBe('Empresas');
})->group('kit');

/**
 * CT-09 — campo fora do domínio é recusado e nada é gravado.
 *
 * Cada partição inválida numa linha própria: combinadas, a primeira validação a
 * disparar mascararia as demais e o caso provaria menos do que aparenta.
 *
 * `0` e `101` são borda−1 e borda+1 de `[1, 100]`; a borda em si é exercitada em
 * `DefaultsDeTabelaTest`.
 *
 * O `Então` afirma o **não-efeito** no banco, não só o erro de formulário: uma
 * implementação que gravasse e depois validasse passaria só com a asserção de
 * erro.
 */
it('recusa campo fora do dominio sem gravar nada', function (string $campo, mixed $valor, string $regra): void {
    $this->actingAs(usuarioDoKit('admin'));

    gravarConfiguracao('nome_da_aplicacao', 'Antes');

    Livewire::test(ConfiguracoesDoKit::class)
        ->fillForm([$campo => $valor])
        ->call('save')
        ->assertHasFormErrors([$campo => $regra]);

    expect(configuracaoGravada('nome_da_aplicacao'))->toBe('Antes');
})->with([
    'nome obrigatório ausente'   => ['nome_da_aplicacao', '', 'required'],
    'remetente sem formato'      => ['mail_from_address', 'nao-e-email', 'email'],
    'paginação abaixo do mínimo' => ['paginacao_padrao', 0, 'min'],
    'paginação acima do máximo'  => ['paginacao_padrao', 101, 'max'],
])->group('kit');

/**
 * CT-10 — o arquivo enviado fica no disco público e visível sem sessão.
 *
 * O oráculo é a **visibilidade no disco**, não a existência do arquivo: o default
 * do Filament é `private`, e um favicon privado existe no disco e responde 403 no
 * `<head>` de toda página. "O arquivo existe" não pega isso.
 *
 * Favicon e logo aparecem ANTES de haver sessão, na tela de login — é a razão pela
 * qual `.ai/rules/models.md` manda `->disk('public')` explícito para avatar e logo.
 */
it('envia o favicon para o disco publico com visibilidade publica', function (): void {
    Storage::fake('public');

    $this->actingAs(usuarioDoKit('admin'));

    Livewire::test(ConfiguracoesDoKit::class)
        ->fillForm([
            'favicon' => UploadedFile::fake()->image('favicon.png'),
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $caminho = configuracaoGravada('favicon');

    expect($caminho)->toBeString()
        ->and(Storage::disk('public')->exists($caminho))->toBeTrue()
        ->and(Storage::disk('public')->getVisibility($caminho))->toBe('public');
})->group('kit');

/**
 * CT-13 — o administrador geral abre e grava sem nenhuma permissão atribuída.
 *
 * `master_global` não tem permission nenhuma no banco de propósito: ele entra pelo
 * `Gate::before` do `KitServiceProvider`. Uma implementação que exigisse a
 * permission na tabela trancaria justamente quem tem mais acesso.
 */
it('deixa o administrador geral abrir e gravar sem permissao atribuida', function (): void {
    $this->actingAs(usuarioDoKit('master_global'));

    Livewire::test(ConfiguracoesDoKit::class)
        ->fillForm(['nome_da_aplicacao' => 'Pelo geral'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect(configuracaoGravada('nome_da_aplicacao'))->toBe('Pelo geral');
})->group('kit');

/**
 * CT-14 — quem tem a permissão vê o item no menu do painel.
 *
 * Asserção de APOIO, e declarada como tal: no Filament uma Page descoberta com
 * `canAccess()` verdadeiro entra na navegação por default, então este caso mata
 * só o `shouldRegisterNavigation()` sobrescrito errado. Custa um `assertSee` e
 * ficou por isso.
 */
it('mostra a tela de configuracoes no painel de quem tem a permissao', function (): void {
    $this->actingAs(usuarioDoKit('admin'));

    $this->get('/admin')
        ->assertOk()
        ->assertSee('Configurações do kit');
})->group('kit');

/**
 * CT-15 — quem não tem a permissão não abre a tela.
 *
 * A persona "administrador sem a permissão" é a discriminante: as outras duas
 * passariam numa implementação que conferisse o PAPEL em vez da permission.
 *
 * A coluna "gravar" da matriz **não** é exercitada aqui, e isso é decisão
 * registrada, não esquecimento: com uma permissão só governando as duas ações
 * (ADR-04), o `mount()` aborta em `canAccess()` antes de `save()` existir, e
 * `canEdit()` fixo em `true` é inobservável por qualquer cenário. Foi a rodada 2
 * da revisão adversarial que mostrou isso — a rodada 1 tinha criado um CT-33 que
 * não matava mutante nenhum.
 */
it('recusa a tela de configuracoes a quem nao tem a permissao', function (string $persona): void {
    $usuario = match ($persona) {
        'panel_user' => usuarioDoKit('panel_user'),
        'sem papel'  => usuarioCom(null),
        // A persona discriminante: tem o papel, não tem a permission.
        'admin sem a permissão' => tap(usuarioDoKit('admin'), function (): void {
            Role::findByName('admin')->revokePermissionTo('View:ConfiguracoesDoKit');

            app(PermissionRegistrar::class)->forgetCachedPermissions();
        }),
    };

    $this->actingAs($usuario);

    Livewire::test(ConfiguracoesDoKit::class)->assertForbidden();
})->with([
    'usuário comum do negócio' => 'panel_user',
    'sem papel nenhum'         => 'sem papel',
    'admin sem a permissão'    => 'admin sem a permissão',
])->group('kit');

/**
 * CT-16 — os seeders do kit entregam a permissão ao administrador e a mais ninguém.
 *
 * RQ-15 pede que a permissão seja padrão "desde o início do starter-kit", isto é,
 * sem passo manual. O que prova isso é que `PapeisSeeder` **não precisou de
 * edição**: a matriz do painel `admin` já a inclui, porque a Page é descoberta em
 * `app/Filament/Admin/Pages` e `config('filament-shield.tabs.pages')` é `true`.
 *
 * `master_global` sem permission nenhuma é invariante do kit, repetida aqui porque
 * é a asserção que impede o conserto errado ("acrescentar a permission a ele") de
 * passar.
 */
it('entrega a permissao de configuracoes ao papel admin e a mais ninguem', function (): void {
    expect(Permission::query()->where('name', 'View:ConfiguracoesDoKit')->exists())->toBeTrue();

    $tem = fn (string $papel): bool => Role::findByName($papel)
        ->permissions
        ->pluck('name')
        ->contains('View:ConfiguracoesDoKit');

    expect($tem('admin'))->toBeTrue()
        ->and($tem('infra'))->toBeFalse()
        ->and($tem('panel_user'))->toBeFalse()
        ->and(Role::findByName('master_global')->permissions)->toHaveCount(0);
})->group('kit');

/**
 * CT-36 — a cor escolhida no formulário chega à paleta resolvida.
 *
 * Achado da rodada 2 da revisão adversarial. A primeira versão deste caso afirmava
 * o CONJUNTO de opções do campo, e as duas formas de escrever isso são ruins:
 * derivar a lista esperada da constante é tautologia, e fixar "16" é número
 * mágico. Pior, nenhuma das duas pega o defeito de verdade — uma opção com chave
 * em slug (`emerald` em vez de `Emerald`) grava, e `CorPrimaria` cai em paleta
 * vazia sem erro nenhum, porque a guarda de nome inválido existe justamente para
 * não derrubar o painel.
 *
 * Então o oráculo é comportamental: escolher, salvar, alinhar e conferir a paleta.
 * A cadeia com `CorPrimariaTest` ("só oferece cores que existem de verdade na
 * paleta do Filament") é o que entrega RQ-06 sem oferecer as constantes de
 * `Color` que não são cor.
 */
it('leva a cor escolhida no formulario para a paleta resolvida', function (): void {
    $this->actingAs(usuarioDoKit('admin'));

    $cor = CustomizadorDaInstalacao::CORES[3];

    Livewire::test(ConfiguracoesDoKit::class)
        ->fillForm(['cor_primaria' => $cor])
        ->call('save')
        ->assertHasNoFormErrors();

    app()->forgetInstance(SettingsDoKit::class);
    alinharConfiguracoesDoKit();

    expect(CorPrimaria::paleta())->toBe(['primary' => constant(Color::class.'::'.$cor)]);
})->group('kit');

/*
|--------------------------------------------------------------------------
| A senha de SMTP não sai da instalação — nem para quem pode editá-la
|--------------------------------------------------------------------------
| Estes três casos fecham o achado QA-02 do `06-relatorio-qa.md`, que era
| Blocker: a senha decifrada ia para `$this->data`, propriedade pública da
| Page, e o Livewire a serializava no `wire:snapshot` do HTML. Resposta 200,
| senha em claro no corpo, sem clique em "revelar".
|
| O roteiro do `05` prometia por escrito "nunca em claro no HTML inicial" e a
| linha ficou em branco — era ela que teria pegado. Está escrita agora.
|
| O par importa: provar que o segredo NÃO aparece, sozinho, ficaria verde
| também se a senha tivesse sido apagada. Por isso o terceiro caso prova que
| ela sobrevive a um salvamento que não a tocou.
*/
it('nao serializa a senha de smtp no html da tela', function (): void {
    $settings                = app(SettingsDoKit::class);
    $settings->mail_mailer   = 'smtp';
    $settings->mail_password = 'SENHA-SUPER-SECRETA-42';
    $settings->save();

    $this->actingAs(usuarioDoKit('admin'));

    $resposta = $this->get('/admin/configuracoes-do-kit');

    $resposta->assertOk();

    // `assertDontSee` com `escape: false` porque o snapshot do Livewire chega
    // com as aspas escapadas como `&quot;` — procurar a string crua acharia
    // nada mesmo com o vazamento presente, e o caso passaria por engano.
    expect($resposta->getContent())->not->toContain('SENHA-SUPER-SECRETA-42');
});

it('nao entrega a senha de smtp no estado do formulario', function (): void {
    $settings                = app(SettingsDoKit::class);
    $settings->mail_mailer   = 'smtp';
    $settings->mail_password = 'SENHA-SUPER-SECRETA-42';
    $settings->save();

    $this->actingAs(usuarioDoKit('admin'));

    Livewire::test(ConfiguracoesDoKit::class)
        ->assertFormFieldExists('mail_password')
        ->assertFormSet(['mail_password' => null]);
});

it('mantem a senha guardada quando o campo fica em branco', function (): void {
    $settings                  = app(SettingsDoKit::class);
    $settings->mail_mailer     = 'smtp';
    $settings->mail_host       = 'smtp.exemplo.test';
    $settings->mail_password   = 'SENHA-SUPER-SECRETA-42';
    $settings->save();

    $this->actingAs(usuarioDoKit('admin'));

    Livewire::test(ConfiguracoesDoKit::class)
        ->fillForm(['nome_da_aplicacao' => 'Outro Nome'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect(app(SettingsDoKit::class)->mail_password)->toBe('SENHA-SUPER-SECRETA-42')
        ->and(configuracaoGravada('nome_da_aplicacao'))->toBe('Outro Nome');
});

/*
|--------------------------------------------------------------------------
| Valor legítimo fora do domínio do campo não trava a tela
|--------------------------------------------------------------------------
| Fecha o achado QA-01 do `06-relatorio-qa.md` (Major): `Select` acrescenta
| um `Rule::in()` das próprias opções sozinho, então um valor gravado que a
| tela não oferece reprovava o formulário INTEIRO — nem o nome da aplicação
| gravava. E o valor não era inválido: `config/mail.php` tem 9 transportes e
| a tela oferece 3; `Color` tem 26 cores e a lista do kit tem 16.
|
| O dataset é a tabela do achado. Cada linha grava um valor fora do domínio
| direto no settings (como o `.env` faria), abre a tela e muda OUTRO campo.
| O oráculo é duplo de propósito: o outro campo gravou **e** o valor de fora
| sobreviveu. Sem a segunda metade, "normalizar para o default" passaria —
| e normalizar em silêncio é perda de dado, não validação.
*/
it('grava a tela mesmo com valor configurado fora da lista oferecida', function (string $campo, mixed $valorDeFora): void {
    $settings         = app(SettingsDoKit::class);
    $settings->$campo = $valorDeFora;
    $settings->save();

    $this->actingAs(usuarioDoKit('admin'));

    Livewire::test(ConfiguracoesDoKit::class)
        ->fillForm(['nome_da_aplicacao' => 'Gravou Mesmo Assim'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect(configuracaoGravada('nome_da_aplicacao'))->toBe('Gravou Mesmo Assim')
        ->and(app(SettingsDoKit::class)->$campo)->toBe($valorDeFora);
})->with([
    // 6 dos 9 mailers de config/mail.php ficam fora da lista de 3 da tela.
    'transporte real fora da lista'   => ['mail_mailer', 'ses'],
    // 10 cores reais de Color ficam fora das 16 do kit.
    'cor real fora da lista do kit'   => ['cor_primaria', 'Green'],
    // NumeroDoEnv::positivo() não tem teto, então 500 é estado alcançável.
    'paginacao acima do teto da tela' => ['paginacao_padrao', 500],
]);

it('oferece o valor configurado como opcao, marcado, quando ele esta fora da lista', function (): void {
    $settings              = app(SettingsDoKit::class);
    $settings->mail_mailer = 'ses';
    $settings->save();

    $this->actingAs(usuarioDoKit('admin'));

    // A marca importa: sem ela o valor de fora fica indistinguível de uma opção
    // que o kit recomenda, e a lista curta perde a função de guiar a escolha.
    Livewire::test(ConfiguracoesDoKit::class)
        ->assertSee('ses — configurado no .env');
});
