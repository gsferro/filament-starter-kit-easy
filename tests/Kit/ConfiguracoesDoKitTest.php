<?php

use App\Filament\Admin\Pages\ConfiguracoesDoKit as TelaDeConfiguracoes;
use App\Filament\Admin\Resources\Users\Pages\ListUsers;
use App\Settings\ConfiguracoesDoKit;
use Database\Seeders\PapeisSeeder;
use Database\Seeders\ShieldPermissionsSeeder;
use Filament\Facades\Filament;
use Filament\Pages\SettingsPage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use OwenIt\Auditing\Models\Audit;
use Spatie\LaravelSettings\Events\SavingSettings;
use Spatie\LaravelSettings\Models\SettingsProperty;
use Symfony\Component\Finder\SplFileInfo;

/**
 * As configurações da instalação gravadas no banco, e o alinhamento com a config.
 *
 * Os IDs de CT são os de
 * `wikis/specs/feat/settings-do-kit/settings-do-kit/04-casos-de-teste.md`.
 *
 * ## Por que estes casos precisam alinhar a config à mão
 *
 * O `KitServiceProvider::boot()` alinha sozinho, mas com `RefreshDatabase` ele
 * roda ANTES das migrations — a tabela `settings` ainda não existe, o alinhamento
 * é no-op, e é justamente isso que mantém os valores forçados no `phpunit.xml`
 * valendo para a suíte inteira. Então quem quer exercitar o alinhamento o chama.
 *
 * Quem prova que o boot faz isso sozinho, no processo de verdade, é CT-37.
 */

/**
 * CT-01 — cada propriedade gravada chega à sua chave de configuração.
 *
 * Uma linha por propriedade, e não uma por TIPO de dado, e essa foi a correção
 * mais importante da revisão adversarial: cada chave de config é uma linha de
 * código independente no mapa, então não existe classe de equivalência entre
 * `mail.from.address` e `mail.mailers.smtp.host`. Com seis linhas, um mapa que
 * cobrisse só aquelas seis passava — e a tela prometeria 21 configurações
 * entregando 6.
 *
 * Todo valor DIFERE do que o ambiente já entrega: `smtp` contra o `log` do
 * `phpunit.xml`, `false` contra os booleanos de tabela que nascem `true`, `true`
 * contra o `KIT_HUB=false` forçado, `25` contra a paginação 10 de fábrica. Um
 * alinhamento que não fizesse nada reprovaria em todas as 21.
 *
 * A asserção é `toBe`, estrita: `"25"` e `"true"` como string satisfazem
 * comparação frouxa, quebram `defaultPaginationPageOption()` e todo `===`.
 */
it('leva cada propriedade gravada para a chave de configuracao dela', function (string $propriedade, mixed $gravado, string $chave): void {
    gravarConfiguracao($propriedade, $gravado);

    alinharConfiguracoesDoKit();

    expect(config($chave))->toBe($gravado);
})->with([
    'nome da aplicação'      => ['nome_da_aplicacao', 'Meu Projeto', 'app.name'],
    'cor da lista'           => ['cor_primaria', 'Emerald', 'kit.cor_primaria'],
    'cor livre'              => ['cor_primaria_hex', '#7c3aed', 'kit.cor_primaria_hex'],
    'logo'                   => ['logo', 'kit/logo.png', 'kit.identidade.logo'],
    'favicon'                => ['favicon', 'kit/favicon.png', 'kit.identidade.favicon'],
    'arte do login'          => ['arte_do_login', 'kit/arte.svg', 'kit.identidade.arte_do_login'],
    'transporte de e-mail'   => ['mail_mailer', 'smtp', 'mail.default'],
    'servidor de e-mail'     => ['mail_host', 'smtp.exemplo.test', 'mail.mailers.smtp.host'],
    'porta de e-mail'        => ['mail_port', 587, 'mail.mailers.smtp.port'],
    'criptografia de e-mail' => ['mail_scheme', 'tls', 'mail.mailers.smtp.scheme'],
    'usuário de e-mail'      => ['mail_username', 'usuario@exemplo.test', 'mail.mailers.smtp.username'],
    'remetente'              => ['mail_from_address', 'contato@exemplo.test', 'mail.from.address'],
    'nome do remetente'      => ['mail_from_name', 'Remetente', 'mail.from.name'],
    'paginação'              => ['paginacao_padrao', 25, 'kit.tabelas.paginacao'],
    'linhas listradas'       => ['tabela_listrada', false, 'kit.tabelas.listrada'],
    'persistir filtros'      => ['persistir_filtros', false, 'kit.tabelas.persistir_filtros'],
    'colunas arrastáveis'    => ['colunas_redimensionaveis', false, 'kit.tabelas.colunas_redimensionaveis'],
    'hub de navegação'       => ['hub_de_navegacao', true, 'kit.hub'],
    'rótulo singular'        => ['rotulo_da_organizacao', 'Empresa', 'kit.tenancy.label'],
    'rótulo plural'          => ['rotulo_das_organizacoes', 'Empresas', 'kit.tenancy.label_plural'],
])->group('kit');

/**
 * CT-01 (segunda metade) — a senha cifrada também percorre o mapa.
 *
 * Separada das 20 acima porque o arranjo é outro: o `payload` dela é criptograma,
 * então a gravação tem de passar pela API do settings, não pela tabela.
 */
it('leva a senha de e-mail gravada para a chave de configuracao dela', function (): void {
    $settings                = app(ConfiguracoesDoKit::class);
    $settings->mail_password = 'senha-do-smtp';
    $settings->save();

    alinharConfiguracoesDoKit();

    expect(config('mail.mailers.smtp.password'))->toBe('senha-do-smtp');
})->group('kit');

/**
 * CT-02 — propriedade sem linha no banco mantém o valor do `.env`.
 *
 * O mutante é a implementação que sobrepõe a config com `null` quando a
 * propriedade não existe, apagando em silêncio o que o `.env` definiu.
 */
it('mantem o valor do env quando a propriedade nao tem linha no banco', function (): void {
    config(['kit.cor_primaria' => 'Blue']);

    SettingsProperty::query()
        ->where('group', ConfiguracoesDoKit::group())
        ->where('name', 'cor_primaria')
        ->delete();

    app()->forgetInstance(ConfiguracoesDoKit::class);

    $canal = espiarConfiguracoes();

    alinharConfiguracoesDoKit();

    expect(config('kit.cor_primaria'))->toBe('Blue');

    $canal->shouldHaveReceived('warning');
})->group('kit');

/**
 * CT-03 — instalação antes do primeiro migrate não é afetada, e não avisa.
 *
 * O `Schema::hasTable()` fica DENTRO do `try` justamente por este cenário: num
 * banco que ainda não existe ele lança antes de responder, e aí todo comando
 * artisan morreria.
 *
 * A ausência de log é asserção deliberada: tabela ausente é o estado normal de
 * uma instalação nova, e um `warning` ali gritaria em todo `migrate` de todo
 * mundo. Canal que grita no caminho feliz é canal que ninguém lê.
 */
it('nao afeta nem avisa quando a tabela de settings nao existe', function (): void {
    $nome = (string) config('app.name');

    Schema::drop('settings');

    $canal = espiarConfiguracoes();

    alinharConfiguracoesDoKit();

    expect(config('app.name'))->toBe($nome);

    $canal->shouldNotHaveReceived('warning');
})->group('kit');

/**
 * CT-04 — falha na leitura do banco cai para o `.env`, com aviso.
 *
 * O `Error` no lugar de uma `Exception` não é detalhe: `catch (Exception)` em vez
 * de `catch (Throwable)` deixaria `Error` e `TypeError` escaparem e derrubaria a
 * aplicação inteira — não uma tela, porque isto roda no boot.
 */
it('cai para o env com aviso quando a leitura do banco lanca', function (): void {
    $nome = (string) config('app.name');

    app()->forgetInstance(ConfiguracoesDoKit::class);
    app()->bind(ConfiguracoesDoKit::class, function (): never {
        throw new Error('banco inacessível');
    });

    $canal = espiarConfiguracoes();

    alinharConfiguracoesDoKit();

    expect(config('app.name'))->toBe($nome);

    $canal->shouldHaveReceived('warning');
})->group('kit');

/**
 * CT-38 — propriedade limpada devolve o consumidor ao padrão.
 *
 * Achado da rodada 2 da revisão adversarial: `if (! is_null($valor))` é a guarda
 * que qualquer dev escreve depois de pensar no mutante de CT-02, e com ela **não
 * existe caminho de volta ao default pela tela** — escolher "padrão" na cor,
 * remover a logo ou apagar o usuário de SMTP gravaria, geraria trilha, e não
 * teria efeito nenhum.
 *
 * As 20 linhas de CT-01 usam valor não-nulo, então nenhuma delas pega isso.
 */
it('zera a chave de configuracao quando a propriedade e limpada', function (string $propriedade, string $chave, string $antes): void {
    config([$chave => $antes]);

    gravarConfiguracao($propriedade, null);

    alinharConfiguracoesDoKit();

    expect(config($chave))->toBeNull();
})->with([
    'escolheu a cor padrão' => ['cor_primaria', 'kit.cor_primaria', 'Blue'],
    'removeu a logo'        => ['logo', 'kit.identidade.logo', 'kit/logo.png'],
    'apagou o usuário SMTP' => ['mail_username', 'mail.mailers.smtp.username', 'usuario'],
])->group('kit');

/**
 * CT-37 — o alinhamento está ligado no provider da aplicação, não num painel.
 *
 * Achado da rodada 2: pendurar o alinhamento no `bootUsing()` de um painel ou num
 * middleware `web` é o lugar mais natural num projeto Filament, e passaria em
 * todos os outros casos — que ou chamam o alinhamento direto, ou fazem visita
 * HTTP. Comando artisan, fila e agendador ficariam com o `.env`, e a aba E-mail
 * seria decorativa exatamente onde o convite é enviado.
 *
 * Asserção ESTRUTURAL, e declarada como tal: o falsificador comportamental
 * exigiria um processo separado (`php artisan` de verdade), lento e frágil na
 * suíte. O kit já usa este padrão em `CacheDeViewsNoDockerTest` e
 * `QualidadeDeCodigoTest`.
 *
 * ## Duas correções, e as duas vieram de defeito medido
 *
 * **1. Comentário fora antes de afirmar ausência.** É a regra de
 * `.ai/rules/testes.md`, agora na quarta ocorrência: os arquivos bem comentados
 * do kit CITAM o que proíbem, e é lá que está escrito o porquê. O middleware
 * `ExigirEmailVerificado` explica a ordem de boot que o levou a existir, e o
 * `AppPanelProvider` explica por que a decisão saiu do array da rota — os dois
 * mencionam `aplicarNaConfig()` em docblock. Sem o filtro, este caso reprovava
 * pela documentação, não pelo comportamento.
 *
 * **2. A mensagem virou agulha, e neutralizava o laço dos painéis.** A versão
 * anterior passava a explicação como SEGUNDO argumento de `toContain()`, achando
 * que era mensagem de falha. `toContain()` é variádico: o texto entrava como
 * outra agulha. E `->not` do Pest passa assim que a asserção positiva lança —
 * ou seja, bastava a mensagem longa não estar no arquivo para o caso passar.
 * O laço dos painéis **não podia falhar**, com qualquer conteúdo. Uma agulha por
 * chamada, e a explicação onde ela pertence: aqui.
 *
 * Se este caso reprovar, o que ele quer dizer é: alguém pendurou o alinhamento num
 * painel ou num middleware, e aí comando artisan, fila e agendador seguem lendo o
 * `.env` — a aba E-mail da tela fica decorativa exatamente onde o convite é enviado.
 */
it('liga o alinhamento no provider da aplicacao e em nenhum painel', function (): void {
    $semComentario = static fn (string $arquivo): string => (string) preg_replace(
        ['~/\*.*?\*/~s', '~//[^\n]*~'],
        '',
        (string) file_get_contents($arquivo),
    );

    // Presença continua rodando sobre o texto cru: citar não é executar, mas executar também
    // aparece no texto cru. É só a ausência que precisa do filtro.
    expect(file_get_contents(base_path('app/Providers/KitServiceProvider.php')))
        ->toContain('aplicarNaConfig()');

    $arquivos = [
        ...glob(base_path('app/Providers/Filament/*.php')) ?: [],
        ...glob(base_path('app/Http/Middleware/*.php')) ?: [],
    ];

    expect($arquivos)->not->toBeEmpty();

    foreach ($arquivos as $arquivo) {
        expect($semComentario($arquivo))->not->toContain('aplicarNaConfig');
    }
})->group('kit');

/**
 * CT-11 — a migration semeia com o que a CONFIGURAÇÃO tinha, não com literais.
 *
 * O arranjo é o ponto do caso, e foi reescrito duas vezes. A primeira versão
 * contava 21 linhas — cardinalidade, que passa com todos os valores errados. A
 * segunda comparava cada propriedade com `config()`, o que é auto-referencial: para
 * paginação (10), os três booleanos (`true`) e o hub (`false`), uma migration com
 * literais produz exatamente o valor que a config já tem.
 *
 * Esta versão arranja valores que NÃO são os de fábrica, apaga o grupo e roda o
 * `up()` de novo. Aí literal e `config()` divergem, e M12 tem matador.
 */
it('semeia as propriedades com o valor que a configuracao tinha', function (): void {
    config([
        'app.name'                              => 'Semeado Pelo Env',
        'kit.tabelas.paginacao'                 => 42,
        'kit.tabelas.listrada'                  => false,
        'kit.hub'                               => true,
        'kit.tenancy.label'                     => 'Empresa',
        'mail.mailers.smtp.host'                => 'smtp.semeado.test',
    ]);

    SettingsProperty::query()->where('group', ConfiguracoesDoKit::group())->delete();

    (require base_path('database/settings/2026_08_24_000000_create_kit_settings.php'))->up();

    $semeado = fn (string $nome): mixed => json_decode((string) SettingsProperty::query()
        ->where('group', ConfiguracoesDoKit::group())
        ->where('name', $nome)
        ->value('payload'), associative: true);

    expect($semeado('nome_da_aplicacao'))->toBe('Semeado Pelo Env')
        ->and($semeado('paginacao_padrao'))->toBe(42)
        ->and($semeado('tabela_listrada'))->toBeFalse()
        ->and($semeado('hub_de_navegacao'))->toBeTrue()
        ->and($semeado('rotulo_da_organizacao'))->toBe('Empresa')
        ->and($semeado('mail_host'))->toBe('smtp.semeado.test');
})->group('kit');

/**
 * CT-11 (invariante) — toda propriedade declarada tem linha no banco.
 *
 * Por reflexão, e não contra o número 21: o número é do plano, não do requisito, e
 * uma contagem literal envelhece na primeira propriedade nova. O que se afirma é a
 * coerência entre a classe e a migration, que é o defeito de verdade — propriedade
 * declarada e não semeada faz o boot avisar em todo request.
 */
it('semeia todas as propriedades que a classe de settings declara', function (): void {
    $declaradas = array_keys(ConfiguracoesDoKit::mapaDeConfiguracao());

    $noBanco = SettingsProperty::query()
        ->where('group', ConfiguracoesDoKit::group())
        ->pluck('name')
        ->all();

    expect(array_diff($declaradas, $noBanco))->toBe([], 'Propriedade declarada e não semeada: o boot vai avisar em todo request.')
        ->and(array_diff($noBanco, $declaradas))->toBe([], 'Linha no banco sem propriedade na classe: o mapa de config esqueceu alguém.');
})->group('kit');

/**
 * CT-12 — as migrations de settings são reversíveis, e o desfazer devolve o `.env` como fonte
 * única.
 *
 * O segundo `Quando` (remigrar) é o que mata o mutante de `add()` sem
 * `deleteIfExists()` no `down()`: sem ele o segundo `migrate` estoura
 * `SettingAlreadyExists`. Estava só na prosa antes da revisão.
 *
 * **Percorre TODAS as migrations de `database/settings/`, e não uma fixada pelo nome.** A versão
 * anterior fixava `2026_08_24_000000_create_kit_settings.php` e afirmava
 * `count(linhas) === count(mapa)` depois do `up()` — o que se tornou aritmeticamente impossível
 * quando `registro_verificar_email` entrou por uma migration nova (obrigatoriamente nova: a
 * primeira já rodou em instalação de terceiro, e editá-la deixaria essas bases sem a
 * propriedade). Generalizar mata o mesmo mutante para toda migration presente e futura, e tira um
 * nome de arquivo de dentro do teste.
 *
 * `down()` na ordem inversa e `up()` na ordem direta — é o que o migrator faz.
 */
it('desfaz e refaz as migrations de settings sem quebrar', function (): void {
    $migrations = collect(File::files(base_path('database/settings')))
        ->sortBy(fn (SplFileInfo $arquivo): string => $arquivo->getFilename())
        ->map(fn (SplFileInfo $arquivo): object => require $arquivo->getRealPath())
        ->values();

    expect($migrations)->not->toBeEmpty();

    gravarConfiguracao('nome_da_aplicacao', 'Gravado no banco');
    config(['app.name' => 'Vindo do env']);

    $migrations->reverse()->each(fn (object $migration) => $migration->down());

    expect(SettingsProperty::query()->where('group', ConfiguracoesDoKit::group())->count())->toBe(0);

    app()->forgetInstance(ConfiguracoesDoKit::class);
    alinharConfiguracoesDoKit();

    expect(config('app.name'))->toBe('Vindo do env');

    $migrations->each(fn (object $migration) => $migration->up());

    expect(SettingsProperty::query()->where('group', ConfiguracoesDoKit::group())->count())
        ->toBe(count(ConfiguracoesDoKit::mapaDeConfiguracao()));
})->group('kit');

/**
 * CT-21 — a senha vai cifrada para a tabela e volta legível.
 *
 * As três asserções juntas separam três implementações, e nenhuma delas sozinha
 * distingue as três: sem cifra a primeira falha; cifra sem decifra, a segunda;
 * decifra que não chega ao consumidor, a terceira (o mailer tentaria autenticar
 * com o criptograma).
 */
it('grava a senha de e-mail cifrada e a devolve legivel', function (): void {
    $settings                = app(ConfiguracoesDoKit::class);
    $settings->mail_password = 'senha-do-smtp';
    $settings->save();

    $bruto = (string) SettingsProperty::query()
        ->where('group', ConfiguracoesDoKit::group())
        ->where('name', 'mail_password')
        ->value('payload');

    app()->forgetInstance(ConfiguracoesDoKit::class);
    alinharConfiguracoesDoKit();

    expect($bruto)->not->toContain('senha-do-smtp')
        ->and(app(ConfiguracoesDoKit::class)->mail_password)->toBe('senha-do-smtp')
        ->and(config('mail.mailers.smtp.password'))->toBe('senha-do-smtp');
})->group('kit');

/** CT-22 — a alteração registra quem mudou, o que mudou e os dois valores. */
it('registra na trilha o valor antigo e o novo de cada alteracao', function (): void {
    $usuario = usuario();
    $this->actingAs($usuario);

    gravarConfiguracao('nome_da_aplicacao', 'Antes');

    $settings                    = app(ConfiguracoesDoKit::class);
    $settings->nome_da_aplicacao = 'Depois';
    $settings->save();

    $trilha = Audit::query()->where('tags', 'configuracoes-do-kit')->get();

    expect($trilha)->toHaveCount(1);

    $registro = $trilha->first();

    expect($registro->user_id)->toBe($usuario->getKey())
        ->and($registro->old_values)->toBe(['nome_da_aplicacao' => 'Antes'])
        ->and($registro->new_values)->toBe(['nome_da_aplicacao' => 'Depois'])
        ->and($registro->auditable_type)->toBe(SettingsProperty::class)
        ->and($registro->auditable_id)->toBe(
            SettingsProperty::query()
                ->where('group', ConfiguracoesDoKit::group())
                ->where('name', 'nome_da_aplicacao')
                ->value('id')
        );
})->group('kit');

/**
 * CT-23 — gravar sem alterar nada não gera registro.
 *
 * É também o cenário de idempotência, ancorado no agregado persistido: salvar duas
 * vezes o mesmo valor não acumula trilha.
 */
it('nao registra na trilha quando o valor gravado e igual ao anterior', function (): void {
    $this->actingAs(usuario());

    gravarConfiguracao('nome_da_aplicacao', 'Igual');

    $settings                    = app(ConfiguracoesDoKit::class);
    $settings->nome_da_aplicacao = 'Igual';
    $settings->save();
    $settings->save();

    expect(Audit::query()->where('tags', 'configuracoes-do-kit')->count())->toBe(0);
})->group('kit');

/**
 * CT-34 — duas propriedades alteradas geram duas linhas, cada uma com a sua.
 *
 * Achado da rodada 1: a dimensão "quantas propriedades mudaram" tinha só 0 (CT-23)
 * e 1 (CT-22), e com cardinalidade 1 as duas implementações são indistinguíveis.
 * **2 é o limite** que separa "uma linha por propriedade" de "uma linha por
 * salvamento com o diff inteiro dentro".
 *
 * O arranjo escreve direto na tabela (não por `save()`), então a trilha começa
 * vazia — a contagem absoluta é legítima. A rodada 2 cobrou essa declaração.
 */
it('gera uma linha de trilha por propriedade alterada, com so a propriedade dela', function (): void {
    $this->actingAs(usuario());

    gravarConfiguracao('nome_da_aplicacao', 'Antes');
    gravarConfiguracao('paginacao_padrao', 10);

    $settings                    = app(ConfiguracoesDoKit::class);
    $settings->nome_da_aplicacao = 'Depois';
    $settings->paginacao_padrao  = 25;
    $settings->save();

    $trilha = Audit::query()->where('tags', 'configuracoes-do-kit')->get();

    expect($trilha)->toHaveCount(2);

    $doNome      = $trilha->firstWhere(fn (Audit $a): bool => array_key_exists('nome_da_aplicacao', (array) $a->new_values));
    $daPaginacao = $trilha->firstWhere(fn (Audit $a): bool => array_key_exists('paginacao_padrao', (array) $a->new_values));

    expect($doNome)->not->toBeNull()
        ->and($daPaginacao)->not->toBeNull()
        ->and($doNome->new_values)->toBe(['nome_da_aplicacao' => 'Depois'])
        ->and($daPaginacao->new_values)->toBe(['paginacao_padrao' => 25]);
})->group('kit');

/**
 * CT-24 — o registro não oferece restauração, que corromperia a linha de settings.
 *
 * `RestoreAuditAction` só aparece com `event === 'updated'`, e o restore faria
 * `fill(['nome_da_aplicacao' => …])->save()` numa linha cujas colunas são
 * `group`/`name`/`payload` — SQL inválido. O oráculo é o RENDERIZADO, não o valor
 * da coluna `event`: a rodada 2 apontou, com razão, que afirmar o `event` é afirmar
 * um detalhe de implementação.
 *
 * O `->with(['user','auditable'])` da listagem é a segunda metade do caso: um
 * `auditable_type` que não fosse model Eloquent derrubaria a tela inteira.
 */
it('lista a trilha de configuracoes sem oferecer restauracao', function (): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);

    $this->actingAs(usuarioDoKit('infra'));

    gravarConfiguracao('nome_da_aplicacao', 'Antes');

    $settings                    = app(ConfiguracoesDoKit::class);
    $settings->nome_da_aplicacao = 'Depois';
    $settings->save();

    $this->get('/infra/audits')
        ->assertOk()
        ->assertDontSee('restoreAudit');
})->group('kit');

/**
 * CT-25 — a troca da senha de e-mail é registrada sem o valor.
 *
 * Os dois lados mascarados: uma máscara aplicada só ao valor novo deixaria o
 * antigo vazar na primeira troca.
 */
it('registra a troca da senha de e-mail sem gravar o segredo na trilha', function (): void {
    $this->actingAs(usuario());

    $settings                = app(ConfiguracoesDoKit::class);
    $settings->mail_password = 'segredo-antigo';
    $settings->save();

    Audit::query()->delete();

    $settings->mail_password = 'segredo-novo';
    $settings->save();

    $trilha = Audit::query()->where('tags', 'configuracoes-do-kit')->get();

    expect($trilha)->toHaveCount(1)
        ->and($trilha->first()->new_values)->toHaveKey('mail_password');

    $tudo = $trilha->pluck('old_values')->concat($trilha->pluck('new_values'))->toJson();

    expect($tudo)->not->toContain('segredo-novo')
        ->and($tudo)->not->toContain('segredo-antigo');
})->group('kit');

/**
 * CT-39 — gravação fora de qualquer tela também deixa trilha.
 *
 * Achado da rodada 2: escrever a trilha dentro do `save()` da Page é a saída óbvia
 * de quem lê só o requisito, e passaria em CT-22, CT-23, CT-24, CT-25 e CT-34 —
 * todos gravam pela tela. O requisito pede tracking das alterações do settings, não
 * das telas: instalador, comando e tinker também alteram.
 *
 * Sem `actingAs()` de propósito: é o caso do instalador, onde não há usuário. O
 * `user_id` nulo é resultado esperado, não lacuna.
 */
it('registra na trilha a gravacao feita fora de qualquer tela', function (): void {
    gravarConfiguracao('nome_da_aplicacao', 'Antes');

    $settings                    = app(ConfiguracoesDoKit::class);
    $settings->nome_da_aplicacao = 'Depois';
    $settings->save();

    $registro = Audit::query()->where('tags', 'configuracoes-do-kit')->sole();

    expect($registro->old_values)->toBe(['nome_da_aplicacao' => 'Antes'])
        ->and($registro->new_values)->toBe(['nome_da_aplicacao' => 'Depois'])
        ->and($registro->user_id)->toBeNull();
})->group('kit');

/**
 * CT-28 — a tela está registrada no painel `admin` e em nenhum outro.
 *
 * A segunda metade — que ela é do PACOTE, e não uma Page artesanal que faz a mesma
 * coisa — fecha RQ-02, e foi a rodada 2 que cobrou: registro de Page é
 * indistinguível de reimplementação.
 *
 * Estar no painel `app` é defeito de autorização, não de organização: todo
 * `panel_user` herdaria a configuração da instalação.
 */
it('registra a tela de configuracoes so no painel admin, pelo pacote de settings', function (): void {
    $paginas = fn (string $painel): array => Filament::getPanel($painel)->getPages();

    expect($paginas('admin'))->toContain(TelaDeConfiguracoes::class)
        ->and($paginas('app'))->not->toContain(TelaDeConfiguracoes::class)
        ->and($paginas('infra'))->not->toContain(TelaDeConfiguracoes::class);

    expect(new TelaDeConfiguracoes)->toBeInstanceOf(SettingsPage::class)
        ->and(TelaDeConfiguracoes::getSettings())->toBe(ConfiguracoesDoKit::class);
})->group('kit');

/**
 * CT-30 — a paginação alinhada chega à tabela renderizada.
 *
 * CT-17 afirma sobre o objeto `Table`; este afirma sobre a tela de verdade, que é
 * onde `Table::configureUsing()` roda no ciclo do Livewire.
 *
 * **Desvio declarado**: o cenário do `04` pedia que ninguém alinhasse à mão, para
 * provar o boot. Não é observável em processo único — o boot já aconteceu antes de
 * o `RefreshDatabase` criar a tabela, e `refreshApplication()` descartaria o SQLite
 * `:memory:`. A metade "o boot faz isso sozinho" é de CT-37.
 */
it('leva a paginacao gravada para a tabela renderizada', function (): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);

    $this->actingAs(usuarioDoKit('admin'));

    gravarConfiguracao('paginacao_padrao', 25);
    alinharConfiguracoesDoKit();

    noPainelBootado('admin');

    $tabela = Livewire::test(ListUsers::class)
        ->loadTable()
        ->instance()
        ->getTable();

    expect($tabela->getDefaultPaginationPageOption())->toBe(25);
})->group('kit');

/** A tabela `settings` e o grupo `kit` existem depois do migrate — pré-condição de todo o resto. */
it('tem a tabela de settings migrada e o grupo do kit semeado', function (): void {
    expect(Schema::hasTable('settings'))->toBeTrue()
        ->and(DB::table('settings')->where('group', 'kit')->count())
        ->toBe(count(ConfiguracoesDoKit::mapaDeConfiguracao()));
})->group('kit');

/**
 * O listener da trilha registrado UMA vez — a guarda de um defeito medido.
 *
 * O Laravel descobre listeners em `app/Listeners` pela assinatura do `handle()`.
 * A primeira versão desta feature ACRESCENTAVA um `Event::listen` no
 * `KitServiceProvider`, e o resultado eram DUAS linhas idênticas de auditoria por
 * alteração — o listener rodava duas vezes. Só apareceu porque CT-39 conta os
 * registros com `sole()`.
 *
 * Este caso existe para o defeito não voltar pelo caminho que parece certo:
 * "registrar explicitamente é mais claro que confiar em descoberta".
 */
it('registra o listener da trilha uma unica vez', function (): void {
    expect(Event::getListeners(SavingSettings::class))->toHaveCount(1);
})->group('kit');
