<?php

use App\Filament\Admin\Pages\ConfiguracoesDoKit;
use App\Settings\ConfiguracoesDoKit as SettingsDoKit;
use App\Support\ProvedorSocial;
use Database\Seeders\PapeisSeeder;
use Database\Seeders\ShieldPermissionsSeeder;
use Filament\Facades\Filament;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Section;
use Livewire\Livewire;
use OwenIt\Auditing\Models\Audit;
use Spatie\LaravelSettings\Models\SettingsProperty;

/**
 * Os quatro `client_secret` do login social — cifra, normalização, tela e efeito.
 *
 * R8 a R12 de `wikis/specs/feat/mais-provedores-sociais/mais-provedores-sociais/04-casos-de-teste.md`,
 * e os cinco no MESMO arquivo por uma rule, não por gosto: R10, R11 e R12 precisam do valor
 * gravado na tabela, o que exige `configuracaoGravada()` — helper que já vive em `tests/Pest.php`
 * justamente porque este arquivo virou o segundo consumidor dele (`.ai/rules/testes.md`). Um
 * quarto arquivo só para os casos de tela obrigaria a um clone com outro nome, que a rule proíbe
 * pelo nome.
 *
 * **O defeito que estes casos tornam falsificável é ANTERIOR à feature** (ADR-06):
 * `login_google_client_secret` foi semeado com `addEncrypted` na migration original e NÃO
 * estava em `ConfiguracoesDoKit::encrypted()`. Como `SettingsConfig::isEncrypted()` decide as
 * DUAS pontas — a decifra em `SettingsMapper::fetchProperties()` e a cifra em
 * `SettingsMapper::save()` —, o valor era lido como criptograma (OAuth quebrado com o botão no
 * ar) e regravado em texto claro no primeiro salvamento pela tela. `encrypted()` agora lista os
 * quatro, e a migration desta entrega normaliza o que já estava gravado.
 *
 * Nenhum caso sai para a rede: nada aqui chama `user()` do Socialite.
 */
beforeEach(function (): void {
    Filament::setCurrentPanel('admin');
});

/**
 * Roda SÓ a normalização do segredo do Google, sem os `add()` do resto do `up()`.
 *
 * Fica neste arquivo porque só ele usa — `.ai/rules/testes.md` manda mover apenas o helper
 * usado por mais de um arquivo. Chamar `up()` inteiro não serve: as nove propriedades novas já
 * existem no banco da suíte (a migration roda no `RefreshDatabase`) e o `add()` estouraria
 * `SettingAlreadyExists` antes de qualquer asserção — o que mediria o arnês, não a regra.
 *
 * `Closure::call()` porque o método é privado de propósito: ele é um passo do `up()`, não API.
 * Mesma manobra de `alinharConfiguracoesDoKit()` em `tests/Pest.php`.
 */
function rodarNormalizacaoDoSegredoDoGoogleDaMigration(): void
{
    $migration = require base_path('database/settings/2026_08_25_000000_add_provedores_sociais_to_kit_settings.php');

    (fn () => $this->normalizarSegredoDoGoogle())->call($migration);

    app()->forgetInstance(SettingsDoKit::class);
}

/*
|--------------------------------------------------------------------------
| R8 — os quatro segredos ficam criptograma, legíveis na leitura, mascarados na trilha
|--------------------------------------------------------------------------
*/

/**
 * CT-20 — o oráculo de três pontas, provedor por provedor.
 *
 * As três asserções juntas separam três implementações, e nenhuma sozinha separa as três: **sem
 * cifra** a primeira falha (M56, `encrypted()` devolvendo só `['mail_password']`); **cifra sem
 * decifra** a segunda; **decifra que não chega ao consumidor** a terceira — e essa terceira é o
 * sintoma 1 do ADR-06, o botão no ar apontando para um OAuth que o provedor recusa.
 *
 * A linha do LinkedIn é a discriminante do conjunto: é a única em que o nome da propriedade e a
 * chave de config divergem em FORMA (`_openid` com sublinhado, `-openid` com hífen). Ela mata
 * M58 (o nome sem `_openid` em `encrypted()`, deixando a propriedade real sem cifra) e M59 (o
 * mapa apontando para `services.linkedin.*`).
 *
 * A linha do Google mata M57 — a alternativa recusada no ADR-06, que cifraria os três novos e
 * deixaria de fora justamente o que já estava quebrado.
 */
it('grava cifrado, devolve legivel e alcanca a config o client_secret de cada provedor', function (ProvedorSocial $provedor, string $chaveDeConfig): void {
    $propriedade = $provedor->propriedadeDeSettings('client_secret');
    $segredo     = 'SEGREDO-DE-'.mb_strtoupper($provedor->name).'-42';

    $settings                 = app(SettingsDoKit::class);
    $settings->{$propriedade} = $segredo;
    $settings->save();

    app()->forgetInstance(SettingsDoKit::class);
    alinharConfiguracoesDoKit();

    expect((string) configuracaoGravada($propriedade))->not->toContain($segredo)
        ->and(app(SettingsDoKit::class)->{$propriedade})->toBe($segredo)
        ->and(config($chaveDeConfig))->toBe($segredo);
})->with([
    'google'          => [ProvedorSocial::Google, 'services.google.client_secret'],
    'github'          => [ProvedorSocial::Github, 'services.github.client_secret'],
    'linkedin-openid' => [ProvedorSocial::LinkedIn, 'services.linkedin-openid.client_secret'],
    'x'               => [ProvedorSocial::X, 'services.x.client_secret'],
]);

/**
 * CT-21 — o segredo salvo PELA TELA também fica criptograma.
 *
 * É o sintoma 2 do ADR-06, e ele só aparece por este caminho: `SettingsMapper::save()` consulta
 * a mesma `isEncrypted()` da leitura, então uma cifra aplicada só na migration deixaria o
 * primeiro salvamento pela tela regravar o segredo em texto claro (M60). O `Então` é o `payload`
 * bruto, lido por `configuracaoGravada()` justamente para não passar pelo decifrador — quem
 * pergunta se o valor está cifrado não pode perguntar para quem decifra.
 *
 * O interruptor entra no `fillForm` porque os dois campos de credencial são `->visible()` no
 * toggle: campo escondido não é dehidratado, e sem ligar o provedor o `save()` não veria o
 * segredo.
 */
it('grava cifrado o client_secret digitado na tela de configuracoes', function (ProvedorSocial $provedor): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);
    $this->actingAs(usuarioDoKit('admin'));

    $propriedade = $provedor->propriedadeDeSettings('client_secret');

    Livewire::test(ConfiguracoesDoKit::class)
        ->fillForm([
            $provedor->propriedadeDeSettings('habilitado') => true,
            $propriedade                                   => 'DIGITADO-NA-TELA-42',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    app()->forgetInstance(SettingsDoKit::class);

    expect((string) configuracaoGravada($propriedade))->not->toContain('DIGITADO-NA-TELA-42')
        ->and(app(SettingsDoKit::class)->{$propriedade})->toBe('DIGITADO-NA-TELA-42');
})->with([
    'google'          => [ProvedorSocial::Google],
    'github'          => [ProvedorSocial::Github],
    'linkedin-openid' => [ProvedorSocial::LinkedIn],
    'x'               => [ProvedorSocial::X],
]);

/**
 * CT-22 — a trilha de auditoria guarda a máscara, não o segredo.
 *
 * Terceiro sintoma do mesmo defeito, e o ADR-06 não o lista:
 * `AuditarConfiguracoesDoKit` decide o mascaramento com
 * `in_array($propriedade, ConfiguracoesDoKit::encrypted(), true)`, então enquanto a lista tinha
 * só `mail_password` o segredo do Google ia para `audits.old_values`/`new_values` em claro — e
 * `/infra/audits` é tela de OUTRA permissão. Mata M56 e M61 (lista de nomes própria, escrita à
 * mão, que esqueceria os três provedores novos).
 *
 * Os DOIS lados asseridos: uma máscara aplicada só ao valor novo deixaria o antigo vazar na
 * primeira troca.
 *
 * A máscara é asserida pelo VALOR literal, e não só pela desigualdade com o segredo:
 * `AuditarConfiguracoesDoKit::SEGREDO_MASCARADO` é `private`, e um `null` gravado no lugar da
 * máscara satisfaria "não contém o segredo" enquanto perderia a informação de que houve troca.
 */
it('mascara na trilha o client_secret salvo pela tela', function (ProvedorSocial $provedor): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);
    $this->actingAs(usuarioDoKit('admin'));

    $propriedade = $provedor->propriedadeDeSettings('client_secret');

    Livewire::test(ConfiguracoesDoKit::class)
        ->fillForm([
            $provedor->propriedadeDeSettings('habilitado') => true,
            $propriedade                                   => 'DIGITADO-NA-TELA-42',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $trilha = Audit::query()->where('tags', 'configuracoes-do-kit')->get();

    $doSegredo = $trilha->firstWhere(
        fn (Audit $registro): bool => array_key_exists($propriedade, (array) $registro->new_values),
    );

    expect($doSegredo)->not->toBeNull('A troca do segredo tem de deixar linha na trilha.')
        ->and($doSegredo->new_values[$propriedade])->toBe('••••••')
        ->and($doSegredo->old_values[$propriedade])->toBe('••••••');

    $tudo = $trilha->pluck('old_values')->concat($trilha->pluck('new_values'))->toJson();

    expect($tudo)->not->toContain('DIGITADO-NA-TELA-42');
})->with([
    'google'          => [ProvedorSocial::Google],
    'github'          => [ProvedorSocial::Github],
    'linkedin-openid' => [ProvedorSocial::LinkedIn],
    'x'               => [ProvedorSocial::X],
]);

/*
|--------------------------------------------------------------------------
| R9 — a migration normaliza o segredo do Google já gravado, e nada mais
|--------------------------------------------------------------------------
*/

/**
 * CT-23 — as três partições do valor já gravado, mais a fronteira de string vazia.
 *
 * O arranjo em TEXTO CLARO é o dado que uma instalação afetada tem hoje: `gravarConfiguracao()`
 * escreve o `payload` sem passar pelo `save()`, e portanto sem cifrar.
 *
 * As duas primeiras linhas juntas matam M62 (cifrar sempre — o já cifrado viraria criptograma
 * de criptograma e a leitura devolveria criptograma), M63 (heurística sobre o formato da
 * string: qualquer prefixo acerta uma linha e erra a outra) e M68 (o `update()` com
 * `$encrypted = true`, que cifraria o retorno da closure por cima).
 *
 * A linha `nulo` mata M64 — `decrypt(null)` estourando `DecryptException` fora do `catch`. Ela é
 * o caso de quase toda instalação, e é por isso que o defeito viveu duas releases.
 *
 * A linha `string vazia` distingue `is_null()` de `blank()` no guarda: `Crypto::encrypt('')`
 * NÃO é `Crypto::encrypt(null)` (`vendor/spatie/laravel-settings/src/Support/Crypto.php:8-12`
 * devolve `null` só para `null`). Um `blank()` ali deixa a string vazia sem cifrar, o que é
 * inofensivo, e sem DECIFRAR na leitura, o que não é: `decrypt('')` estoura, e o
 * `catch (Throwable)` do `KitServiceProvider` engoliria a leitura do GRUPO INTEIRO — a
 * instalação voltaria ao `.env` em silêncio, perdendo todas as configurações da tela. É M65.
 */
it('normaliza o segredo do google ja gravado em cada uma das partições', function (string $forma, ?string $valor, ?string $leitura, bool $cifrado): void {
    gravarConfiguracao(
        'login_google_client_secret',
        $forma === 'cifrado' && $valor !== null ? encrypt($valor) : $valor,
    );

    rodarNormalizacaoDoSegredoDoGoogleDaMigration();

    $bruto = configuracaoGravada('login_google_client_secret');

    if ($cifrado) {
        expect($bruto)->toBeString()
            ->and($bruto)->not->toBe($valor)
            ->and(decrypt((string) $bruto))->toBe($leitura);
    } else {
        expect($bruto)->toBeNull();
    }

    expect(app(SettingsDoKit::class)->login_google_client_secret)->toBe($leitura);
})->with([
    'criptograma da semeadura original, não mexe' => ['cifrado', 'SEGREDO-42', 'SEGREDO-42', true],
    'texto claro de quem salvou pela tela'        => ['claro', 'SEGREDO-42', 'SEGREDO-42', true],
    'nulo passa reto'                             => ['claro', null, null, false],
    'string vazia, a fronteira'                   => ['claro', '', '', true],
]);

/**
 * CT-24 — a normalização não toca em nenhuma outra propriedade.
 *
 * Mata M66, a normalização rodando para o grupo inteiro: `nome_da_aplicacao` viraria criptograma
 * e a tela passaria a exibir o payload cifrado como nome da instalação. A senha de SMTP está no
 * arranjo pelo motivo oposto — ela JÁ é cifrada, e uma normalização indiscriminada a cifraria
 * duas vezes, quebrando o mailer sem quebrar nenhum caso do login social.
 */
it('normaliza somente o segredo do google e deixa as outras propriedades intactas', function (): void {
    $settings                = app(SettingsDoKit::class);
    $settings->mail_password = 'senha-do-smtp';
    $settings->save();

    gravarConfiguracao('nome_da_aplicacao', 'Kit de Exemplo');

    rodarNormalizacaoDoSegredoDoGoogleDaMigration();

    expect(app(SettingsDoKit::class)->mail_password)->toBe('senha-do-smtp')
        ->and(configuracaoGravada('nome_da_aplicacao'))->toBe('Kit de Exemplo');
});

/**
 * CT-25 — o rollback apaga as nove propriedades novas e preserva a do Google.
 *
 * A célula de operação que ninguém escreve: um `down()` com `deleteIfExists` de DEZ nomes em vez
 * de nove apagaria a credencial do Google de uma instalação configurada, e quem revertesse a
 * versão precisaria recadastrar o app OAuth. É M67.
 *
 * O `Então` lê o `payload` e o decifra à mão, e não pelo settings: depois do `down()` as nove
 * propriedades declaradas na classe não têm linha, e qualquer leitura do grupo estouraria por
 * propriedade ausente — o que é o comportamento correto do plugin, não o oráculo deste caso.
 *
 * `RefreshDatabase` desfaz o `down()` no fim do caso, então nada aqui deixa o banco da suíte
 * quebrado para o vizinho.
 */
it('reverte as nove propriedades novas sem apagar o segredo do google', function (): void {
    $settings                             = app(SettingsDoKit::class);
    $settings->login_google_client_secret = 'SEGREDO-DO-GOOGLE-42';
    $settings->save();

    $novas = [];

    foreach (ProvedorSocial::cases() as $provedor) {
        if ($provedor === ProvedorSocial::Google) {
            continue;
        }

        foreach (['habilitado', 'client_id', 'client_secret'] as $sufixo) {
            $novas[] = $provedor->propriedadeDeSettings($sufixo);
        }
    }

    expect($novas)->toHaveCount(9)
        ->and(SettingsProperty::query()
            ->where('group', SettingsDoKit::group())
            ->whereIn('name', $novas)
            ->count())->toBe(9);

    $migration = require base_path('database/settings/2026_08_25_000000_add_provedores_sociais_to_kit_settings.php');

    $migration->down();

    expect(SettingsProperty::query()
        ->where('group', SettingsDoKit::group())
        ->whereIn('name', $novas)
        ->count())->toBe(0)
        ->and(decrypt((string) configuracaoGravada('login_google_client_secret')))->toBe('SEGREDO-DO-GOOGLE-42');
});

/*
|--------------------------------------------------------------------------
| R10 — segredo fora de toda saída, e ele sobrevive ao save em branco
|--------------------------------------------------------------------------
| O par é obrigatório e a rule o cobra por nome (`.ai/rules/pages.md`):
| `->password()` e `->revealable()` mexem no `type` do input — na TELA. O valor
| continua em `$this->data`, propriedade pública do componente, e o Livewire
| serializa isso inteiro no `wire:snapshot`. Foi Blocker na v0.19.0, com a senha
| de SMTP em claro no corpo de um 200, e esta entrega multiplica por quatro o
| número de campos sujeitos ao mesmo defeito.
|
| Provar só a ausência ficaria verde também se o segredo tivesse sido APAGADO —
| daí CT-28. E provar só a sobrevivência ficaria verde com um
| `->dehydrated(false)` fixo, que impede trocar a credencial pela tela — daí
| CT-29.
*/

/**
 * CT-26 — o segredo fora do HTML da tela de configurações.
 *
 * `assertOk()` junto da ausência, e não sozinho: a página que estoura em 500 também não contém o
 * segredo, e o caso passaria por engano.
 *
 * Os valores são discriminantes de propósito — `SEGREDO-DE-GITHUB-42` não aparece por acidente
 * em lugar nenhum da resposta. Usar `secret` ou `password` produziria falso vermelho pelo
 * próprio formulário.
 *
 * As linhas dos três provedores novos matam M69 (o zeramento em
 * `mutateFormDataBeforeFill()` cobrindo só `mail_password` e o Google, que era o estado
 * anterior) e M73 (zeramento depois do fill, com o valor já no snapshot); a linha do LinkedIn
 * mata M70, o nome sem `_openid` no laço de zeramento.
 */
it('nao serializa o client_secret de nenhum provedor no html da tela de configuracoes', function (ProvedorSocial $provedor): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);

    $segredo = 'SEGREDO-DE-'.mb_strtoupper($provedor->name).'-42';

    $settings                                                            = app(SettingsDoKit::class);
    $settings->{$provedor->propriedadeDeSettings('habilitado')}          = true;
    $settings->{$provedor->propriedadeDeSettings('client_id')}           = 'id-de-teste';
    $settings->{$provedor->propriedadeDeSettings('client_secret')}       = $segredo;
    $settings->save();

    $this->actingAs(usuarioDoKit('admin'));

    $resposta = $this->get('/admin/configuracoes-do-kit');

    $resposta->assertOk();

    // A comparação é sobre o conteúdo cru: o snapshot do Livewire chega com as aspas escapadas
    // como `&quot;`, então procurar a string dentro de um `assertDontSee` escapado acharia nada
    // mesmo com o vazamento presente.
    expect($resposta->getContent())->not->toContain($segredo);
})->with([
    'google'          => [ProvedorSocial::Google],
    'github'          => [ProvedorSocial::Github],
    'linkedin-openid' => [ProvedorSocial::LinkedIn],
    'x'               => [ProvedorSocial::X],
]);

/**
 * CT-27 — o segredo fora do HTML da tela de login.
 *
 * A tela de login é a única superfície PÚBLICA da feature, e o `href` do botão é montado a
 * partir do provedor — não da credencial. Mata M74, a credencial injetada no blade para montar
 * o link.
 *
 * O botão presente é asserido JUNTO da ausência, pela mesma razão do `assertOk()`: uma tela sem
 * botão nenhum passaria trivialmente na metade que importa.
 */
it('nao serializa o client_secret de nenhum provedor no html da tela de login', function (ProvedorSocial $provedor): void {
    $segredo = 'SEGREDO-DE-'.mb_strtoupper($provedor->name).'-42';

    ligarProvedor($provedor, ['client_secret' => $segredo]);

    $resposta = $this->get('/app/login');

    $resposta->assertOk()
        ->assertSee('Entrar com '.$provedor->rotulo());

    expect($resposta->getContent())->not->toContain($segredo);
})->with([
    'google'          => [ProvedorSocial::Google],
    'github'          => [ProvedorSocial::Github],
    'linkedin-openid' => [ProvedorSocial::LinkedIn],
    'x'               => [ProvedorSocial::X],
]);

/**
 * CT-28 — o save que não tocou no segredo não o apaga.
 *
 * Mata M72, o campo de segredo dehidratado sempre: o save em branco gravaria `null` e o
 * administrador descobriria no próximo login social de alguém.
 *
 * Os QUATRO segredos são asseridos em cada linha, e não só o da linha corrente. É essa terceira
 * asserção que mata "o laço de `mutateFormDataBeforeFill()` zera os quatro e o save grava `null`
 * em três" — um caso que olhasse só o provedor da linha ficaria verde com três credenciais
 * apagadas.
 */
it('mantem o client_secret guardado de todos os provedores quando o campo fica em branco', function (ProvedorSocial $provedor): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);

    $segredoDe = fn (ProvedorSocial $p): string => 'SEGREDO-DE-'.mb_strtoupper($p->name).'-42';

    $settings = app(SettingsDoKit::class);

    foreach (ProvedorSocial::cases() as $cada) {
        $settings->{$cada->propriedadeDeSettings('habilitado')}    = true;
        $settings->{$cada->propriedadeDeSettings('client_id')}     = 'id-de-teste';
        $settings->{$cada->propriedadeDeSettings('client_secret')} = $segredoDe($cada);
    }

    $settings->save();

    $this->actingAs(usuarioDoKit('admin'));

    Livewire::test(ConfiguracoesDoKit::class)
        ->fillForm(['nome_da_aplicacao' => 'Outro Nome'])
        ->call('save')
        ->assertHasNoFormErrors();

    app()->forgetInstance(SettingsDoKit::class);

    $gravado = app(SettingsDoKit::class);

    expect($gravado->{$provedor->propriedadeDeSettings('client_secret')})->toBe($segredoDe($provedor));

    foreach (ProvedorSocial::cases() as $cada) {
        expect($gravado->{$cada->propriedadeDeSettings('client_secret')})
            ->toBe($segredoDe($cada), "O save em branco apagou o segredo do {$cada->rotulo()}.");
    }

    expect(configuracaoGravada('nome_da_aplicacao'))->toBe('Outro Nome');
})->with([
    'google'          => [ProvedorSocial::Google],
    'github'          => [ProvedorSocial::Github],
    'linkedin-openid' => [ProvedorSocial::LinkedIn],
    'x'               => [ProvedorSocial::X],
]);

/**
 * CT-29 — preencher o campo do segredo substitui o valor guardado.
 *
 * Contrapeso obrigatório de CT-28: sem ele, um `->dehydrated(false)` fixo — em vez do
 * `->dehydrated(fn ($e) => filled($e))` — faz o campo NUNCA gravar, CT-28 fica verde, e o
 * administrador não consegue trocar credencial pela tela. É M71.
 */
it('substitui o client_secret guardado quando o campo e preenchido', function (ProvedorSocial $provedor): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);

    $propriedade = $provedor->propriedadeDeSettings('client_secret');

    $settings                                                   = app(SettingsDoKit::class);
    $settings->{$provedor->propriedadeDeSettings('habilitado')} = true;
    $settings->{$propriedade}                                   = 'SEGREDO-ANTIGO-42';
    $settings->save();

    $this->actingAs(usuarioDoKit('admin'));

    Livewire::test(ConfiguracoesDoKit::class)
        ->fillForm([$propriedade => 'SEGREDO-NOVO-42'])
        ->call('save')
        ->assertHasNoFormErrors();

    app()->forgetInstance(SettingsDoKit::class);

    expect(app(SettingsDoKit::class)->{$propriedade})->toBe('SEGREDO-NOVO-42');
})->with([
    'google'          => [ProvedorSocial::Google],
    'github'          => [ProvedorSocial::Github],
    'linkedin-openid' => [ProvedorSocial::LinkedIn],
    'x'               => [ProvedorSocial::X],
]);

/*
|--------------------------------------------------------------------------
| R11 — o interruptor abre os campos daquele provedor, e só dele
|--------------------------------------------------------------------------
| Camada de componente, com `assertSchemaComponentVisible` / `...Hidden` /
| `...Exists`: `assertFormFieldVisible` e `assertFormFieldHidden` estão
| `@deprecated` no Filament instalado (`vendor/filament/forms/.stubs.php:35,40`)
| e continuam funcionando sem avisar — caso escrito com eles passa hoje e quebra
| no upgrade.
|
| O que estes casos NÃO provam é que o `->live()` existe: aqui `fillForm()` muda
| o estado e a asserção seguinte reavalia o schema, então eles ficam verdes com o
| `->live()` removido — e no navegador os campos só apareceriam depois de a
| pessoa clicar em outra coisa. Esse mutante (M80) é do navegador, em CT-B02.
*/

/**
 * CT-30 — a visibilidade dos campos segue o interruptor daquele provedor.
 *
 * As quatro linhas `desligado` matam M75 (campos sem `visible()`, sempre na tela — doze campos
 * de credencial abertos de uma vez, nove deles inúteis); as quatro `ligado` matam M76, o
 * `visible()` invertido, que esconderia exatamente o campo que a pessoa acabou de pedir.
 */
it('abre os campos de credencial conforme o interruptor do provedor', function (ProvedorSocial $provedor, bool $ligado): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);
    $this->actingAs(usuarioDoKit('admin'));

    $componente = Livewire::test(ConfiguracoesDoKit::class)
        ->fillForm([$provedor->propriedadeDeSettings('habilitado') => $ligado]);

    foreach (['client_id', 'client_secret'] as $sufixo) {
        $campo = $provedor->propriedadeDeSettings($sufixo);

        $ligado
            ? $componente->assertSchemaComponentVisible($campo)
            : $componente->assertSchemaComponentHidden($campo);
    }
})->with([
    'google desligado'          => [ProvedorSocial::Google, false],
    'google ligado'             => [ProvedorSocial::Google, true],
    'github desligado'          => [ProvedorSocial::Github, false],
    'github ligado'             => [ProvedorSocial::Github, true],
    'linkedin-openid desligado' => [ProvedorSocial::LinkedIn, false],
    'linkedin-openid ligado'    => [ProvedorSocial::LinkedIn, true],
    'x desligado'               => [ProvedorSocial::X, false],
    'x ligado'                  => [ProvedorSocial::X, true],
]);

/**
 * CT-31 — ligar um provedor não abre os campos de nenhum outro.
 *
 * Mata M77, o `visible()` de todos os provedores lendo o interruptor do primeiro — que com o
 * Google desligado esconderia os doze campos e faria a tela inteira parecer sem credencial.
 * Nenhuma linha de CT-30 pega isso: lá cada provedor é exercitado sozinho.
 */
it('liga os campos de um provedor sem abrir os dos outros', function (): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);
    $this->actingAs(usuarioDoKit('admin'));

    $desligados = array_fill_keys(
        array_map(
            fn (ProvedorSocial $provedor): string => $provedor->propriedadeDeSettings('habilitado'),
            ProvedorSocial::cases(),
        ),
        false,
    );

    $componente = Livewire::test(ConfiguracoesDoKit::class)
        ->fillForm([...$desligados, ProvedorSocial::Github->propriedadeDeSettings('habilitado') => true]);

    foreach (ProvedorSocial::cases() as $provedor) {
        foreach (['client_id', 'client_secret'] as $sufixo) {
            $campo = $provedor->propriedadeDeSettings($sufixo);

            $provedor === ProvedorSocial::Github
                ? $componente->assertSchemaComponentVisible($campo)
                : $componente->assertSchemaComponentHidden($campo);
        }
    }
});

/**
 * CT-32 — a aba Login tem uma seção por provedor, e o rodapé fora delas.
 *
 * A existência dos doze campos mata M78, a aba com só a seção do Google — o estado anterior a
 * esta entrega, que é justamente o que um `foreach` esquecido reproduz.
 *
 * A última asserção mata M79: o `foreach` sobre o enum montando o rodapé DENTRO de cada seção.
 * O oráculo é estrutural e não visual — o componente-pai do rodapé é a aba, nunca uma `Section`
 * de provedor. `assertSchemaComponentVisible('login_rodape')` sozinho ficaria verde com quatro
 * rodapés colapsados dentro dos quatro provedores.
 */
it('monta uma secao por provedor na aba de login, com o rodape fora delas', function (): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);
    $this->actingAs(usuarioDoKit('admin'));

    $componente = Livewire::test(ConfiguracoesDoKit::class);

    foreach (ProvedorSocial::cases() as $provedor) {
        foreach (['habilitado', 'client_id', 'client_secret'] as $sufixo) {
            $componente->assertSchemaComponentExists($provedor->propriedadeDeSettings($sufixo));
        }
    }

    $componente->assertSchemaComponentExists(
        'login_rodape',
        null,
        fn (Component $rodape): bool => ! $rodape->getContainer()->getParentComponent() instanceof Section,
    );
});

/*
|--------------------------------------------------------------------------
| R12 — o que se grava na tela alcança tudo que vem do interruptor
|--------------------------------------------------------------------------
| O efeito é observável só no request SEGUINTE: `aplicarNaConfig()` roda no
| `boot()` do `KitServiceProvider`, uma vez por request, e um teste não
| rebootstrapa a aplicação entre um `save()` e um `get()`. Daí o
| `alinharConfiguracoesDoKit()` entre a ação e o oráculo — é o boot do próximo
| request, e não um atalho.
*/

/**
 * CT-33 — a gravação pela tela alcança a tela de login e as rotas.
 *
 * Rastreio de efeito ponta a ponta: o `Então` não olha o banco, olha as DUAS superfícies que o
 * interruptor governa. Mata M84 (o interruptor gravado que não chega à config, com as
 * credenciais chegando — a rota continuaria em 404 com a tela oferecendo o botão) e M86 (o
 * toggle que só faz efeito no próximo deploy, que `.ai/rules/settings.md` chama de pior que
 * campo ausente).
 *
 * O 302 e não "não é 404": a rota de ida monta a URL de autorização e devolve o redirect sem um
 * byte de rede (`AbstractProvider::redirect()`), então 302 é o estado observável correto e um
 * 500 não passa disfarçado de sucesso.
 */
it('poe o botao no ar e tira a rota do 404 ao ligar o provedor pela tela', function (ProvedorSocial $provedor): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);
    $this->actingAs(usuarioDoKit('admin'));

    $this->get("/auth/{$provedor->value}/redirect")->assertNotFound();

    Livewire::test(ConfiguracoesDoKit::class)
        ->fillForm([
            $provedor->propriedadeDeSettings('habilitado')    => true,
            $provedor->propriedadeDeSettings('client_id')     => "id-de-{$provedor->value}",
            $provedor->propriedadeDeSettings('client_secret') => 'segredo-de-teste',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    app()->forgetInstance(SettingsDoKit::class);
    alinharConfiguracoesDoKit();

    // A tela de login é do VISITANTE: com o administrador ainda na sessão, `/app/login` responde
    // 302 para o painel e o oráculo mediria o redirecionamento de quem já entrou.
    auth()->logout();

    $this->get('/app/login')
        ->assertOk()
        ->assertSee('Entrar com '.$provedor->rotulo());

    $this->get("/auth/{$provedor->value}/redirect")->assertStatus(302);
})->with([
    'google'          => [ProvedorSocial::Google],
    'github'          => [ProvedorSocial::Github],
    'linkedin-openid' => [ProvedorSocial::LinkedIn],
    'x'               => [ProvedorSocial::X],
]);

/**
 * CT-34 — desligar pela tela derruba a rota sem apagar a credencial guardada.
 *
 * A direção de volta, e a terceira asserção é o que separa "desligou" de "desligou apagando a
 * credencial" (M85) — que obrigaria o administrador a recadastrar o app OAuth no provedor só
 * para religar o botão. Os campos de credencial ficam escondidos quando o interruptor cai, e
 * campo escondido não é dehidratado: é por isso que eles sobrevivem, e é isso que o caso cobra.
 */
it('derruba a rota ao desligar o provedor pela tela sem apagar as credenciais', function (): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);

    $settings                             = app(SettingsDoKit::class);
    $settings->login_github_habilitado    = true;
    $settings->login_github_client_id     = 'id-do-github';
    $settings->login_github_client_secret = 'segredo-do-github';
    $settings->save();

    app()->forgetInstance(SettingsDoKit::class);
    alinharConfiguracoesDoKit();

    $this->get('/auth/github/redirect')->assertStatus(302);

    $this->actingAs(usuarioDoKit('admin'));

    Livewire::test(ConfiguracoesDoKit::class)
        ->fillForm(['login_github_habilitado' => false])
        ->call('save')
        ->assertHasNoFormErrors();

    app()->forgetInstance(SettingsDoKit::class);
    alinharConfiguracoesDoKit();

    $this->get('/auth/github/redirect')->assertNotFound();

    // Mesma razão de CT-33: autenticado, `/app/login` redireciona para o painel.
    auth()->logout();

    $this->get('/app/login')
        ->assertOk()
        ->assertDontSee('Entrar com GitHub');

    $gravado = app(SettingsDoKit::class);

    expect($gravado->login_github_client_id)->toBe('id-do-github')
        ->and($gravado->login_github_client_secret)->toBe('segredo-do-github');
});

/**
 * CT-35 — cada propriedade nova alcança a chave de config dela.
 *
 * Nove linhas, uma por propriedade nova, porque nove é o número de chances de esquecer uma linha
 * do `mapaDeConfiguracao()` — o defeito silencioso que `.ai/rules/settings.md` cobra por escrito:
 * "o campo aparece, grava, e não governa nada". É M81.
 *
 * As três linhas do LinkedIn são as discriminantes do conjunto: a propriedade tem `_openid` com
 * SUBLINHADO e a chave de config tem `-openid` com HÍFEN, e essa é a única transformação de nome
 * do desenho inteiro (ADR-01). Um `str_replace` esquecido, ou aplicado na direção errada, morre
 * exatamente aqui — M82 (mapa em `services.linkedin.*`) e M83 (propriedade `login_linkedin_*`).
 */
it('alcanca a chave de config de cada propriedade nova de login social', function (string $propriedade, mixed $valor, string $chave): void {
    $settings                 = app(SettingsDoKit::class);
    $settings->{$propriedade} = $valor;
    $settings->save();

    app()->forgetInstance(SettingsDoKit::class);
    alinharConfiguracoesDoKit();

    expect(config($chave))->toBe($valor);
})->with([
    'github habilitado'     => ['login_github_habilitado', true, 'kit.login.github.habilitado'],
    'github client_id'      => ['login_github_client_id', 'id-do-github', 'services.github.client_id'],
    'github client_secret'  => ['login_github_client_secret', 'segredo-github', 'services.github.client_secret'],

    'linkedin habilitado'    => ['login_linkedin_openid_habilitado', true, 'kit.login.linkedin-openid.habilitado'],
    'linkedin client_id'     => ['login_linkedin_openid_client_id', 'id-do-linkedin', 'services.linkedin-openid.client_id'],
    'linkedin client_secret' => ['login_linkedin_openid_client_secret', 'segredo-linkedin', 'services.linkedin-openid.client_secret'],

    'x habilitado'    => ['login_x_habilitado', true, 'kit.login.x.habilitado'],
    'x client_id'     => ['login_x_client_id', 'id-do-x', 'services.x.client_id'],
    'x client_secret' => ['login_x_client_secret', 'segredo-x', 'services.x.client_secret'],
]);
