<?php

use App\Console\Commands\KitUpdate;
use App\Models\Convite;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use App\Providers\KitServiceProvider;
use App\Settings\ConfiguracoesDoKit;
use App\Support\ProvedorSocial;
use Filament\Facades\Filament;
use Filament\FilamentManager;
use Filament\Panel;
use Filament\PanelRegistry;
use Filament\Support\Assets\AssetManager;
use Filament\Support\Colors\ColorManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Socialite\Two\User as UsuarioDoProvedor;
use Psr\Log\LoggerInterface;
use Spatie\LaravelSettings\Models\SettingsProperty;
use Spatie\Permission\PermissionRegistrar;
use Tests\TenancyTestCase;
use Tests\TestCase;
use Wezlo\FilamentSearchSpotlight\Actions\SpotlightActionRegistry;

/*
|--------------------------------------------------------------------------
| Testes do SEU projeto
|--------------------------------------------------------------------------
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Testes do KIT
|--------------------------------------------------------------------------
| Ficam isolados em tests/Kit para você conseguir rodar só eles — é o que
| você quer depois de um `kit:update`, para saber se a atualização quebrou
| a fundação sem esperar a suíte inteira do seu negócio:
|
|   composer test:kit
|   php artisan test --testsuite=Kit
|   php artisan test --group=kit
|
| Eles cobrem o que o kit promete: acesso aos três painéis, telas de infra
| e admin de pé, invariantes da fundação (uuid, gates, auditoria) e o
| contrato da camada de IA.
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->group('kit')
    ->in('Kit');

/*
|--------------------------------------------------------------------------
| Testes do KIT — multi-tenancy
|--------------------------------------------------------------------------
| Suíte separada por uma razão de bootstrap, não de organização: a migration
| de permissões do spatie lê `config('permission.teams')` em tempo de execução
| para decidir se cria as colunas de team. Ligar a flag num beforeEach seria
| tarde — o RefreshDatabase já teria migrado sem elas.
|
| O Tests\TenancyTestCase fixa a config em createApplication(), que roda antes
| das migrations; e o Pest não permite dois TestCases na mesma pasta, daí o
| diretório próprio.
|
| Mesmo grupo `kit`, então continua entrando em:
|
|   composer test:kit
|   php artisan test --group=kit
*/

pest()->extend(TenancyTestCase::class)
    ->use(RefreshDatabase::class)
    ->group('kit')
    ->in('Tenancy');

/*
|--------------------------------------------------------------------------
| Testes do KIT — telas em browser real
|--------------------------------------------------------------------------
| Navegador de verdade, com JavaScript executando, sobre as telas dos três
| painéis. O que isto pega e o smoke HTTP de tests/Kit não pega: um painel
| Filament é Livewire + Alpine, então o corpo do HTML pode vir íntegro e a
| tela estar inutilizável porque um x-on:click estourou, porque um asset do
| Vite não subiu ou porque um componente registrou erro no console. Nenhuma
| dessas três falhas move o status HTTP de 200.
|
| Grupo `browser`, e NÃO `kit`, de propósito: o `composer test:kit` é o
| comando de resposta rápida depois de um kit:update, e browser em série
| custa ordens de magnitude mais que HTTP. Rode esta suíte com:
|
|   composer test:browser
|   php artisan test --testsuite=Browser
|   php artisan test --group=browser
|
| `npm run build` é pré-requisito DURO: sem o manifest do Vite toda tela
| responde ViteException e todo cenário falha por um motivo que não é o
| dele. O script test:browser já embute o build.
|
| O plugin sobe servidor HTTP próprio in-process (amphp), em porta
| aleatória — nada de Herd, `artisan serve` ou Sail. E porque é o MESMO
| processo, o `:memory:` do phpunit.xml, o RefreshDatabase e o
| `$this->actingAs()` continuam valendo dentro do navegador.
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->group('browser')
    ->in('Browser');

/*
|--------------------------------------------------------------------------
| Test Impact Analysis (Pest 5)
|--------------------------------------------------------------------------
| Roda só os testes afetados pelo diff e replica o resto do cache — inclusive as
| linhas cobertas, então um run replayado reporta a mesma cobertura de um completo.
| Exige driver de cobertura (Xdebug ou PCOV) instalado.
|
| `defaultBranch('main')` porque o TIA precisa saber contra o que diffar, e o
| default dele é `master`. A alternativa seria `git remote set-head origin --auto`,
| uma vez por clone — esta linha vale para todo mundo de uma vez.
|
| `locally()` liga o TIA sem flag no desenvolvimento e o desliga sozinho em CI,
| que é o que a doc do Pest recomenda: o pipeline deve rodar a suíte completa.
|
| Só funciona porque nenhum helper de teste é usado de outro arquivo — o TIA carrega
| um SUBCONJUNTO dos arquivos, e helper cruzado estoura `Call to undefined function`.
| A guarda disso é tests/Kit/HelpersDeTesteTest.php.
|
| **Só com repositório git.** O TIA diffa contra um branch, então sem `.git` ele estoura
| `MissingDependency: The [Tia mode] feature requires [git]` — e o worker do paratest
| morre com `WorkerCrashedException`, não com uma mensagem que explique o motivo. Isso
| acontece em TODA instalação por `composer create-project`, que não cria repositório:
| quem instalava o kit e rodava `composer test:kit` batia nisso antes de ver um teste.
| Medido na instalação de verificação da v0.22.2.
*/

if (is_dir(__DIR__.'/../.git')) {
    pest()->tia()->defaultBranch('main')->locally();
}

/*
|--------------------------------------------------------------------------
| Testes do KIT — telas em browser real, com multi-tenancy
|--------------------------------------------------------------------------
| Pasta separada de tests/Browser pela MESMA razão que separa tests/Tenancy de
| tests/Kit: o Tests\TenancyTestCase fixa `permission.teams` em
| createApplication(), antes das migrations, e o Pest não permite dois TestCases
| na mesma pasta.
|
| Aqui vivem os CT-B que precisam de /app/{tenant} — identidade visual por
| organização, por exemplo. Mesmo grupo `browser`, então continua fora do
| `composer test:kit` e dentro do `--testsuite=Browser`.
*/

pest()->extend(TenancyTestCase::class)
    ->use(RefreshDatabase::class)
    ->group('browser')
    ->in('BrowserTenancy');

/*
| O plugin reexecuta cada assertion até este teto — é assim que ele espera por
| conteúdo assíncrono, sem nenhum `wait()` de segundos fixos no teste. Teto, e
| não espera: cenário verde não gasta esse tempo.
|
| O default de 5 s não alcança o primeiro boot de um painel Filament em teste
| (sem opcache, com o Livewire compilando na primeira visita): o login pela tela
| redirecionava DEPOIS do teto e falhava dizendo que ainda estava em
| `/app/login`.
|
| ATENÇÃO ao rodar UM arquivo isolado com as views frias
| (`php artisan view:clear && pest tests/BrowserTenancy/AlgumTest.php`): o
| primeiro cenário do arquivo paga a compilação inteira dos componentes Livewire
| sozinho — medido, ~25 s só nisso — e falha por tempo, não por comportamento.
| Subir o teto NÃO resolve: reproduzido igual com 40 s e 60 s.
|
| A suíte completa (`--testsuite=Browser`) não sofre disso, e é o que o CI roda:
| os arquivos anteriores aquecem a compilação antes de o BrowserTenancy começar.
| Medido com as views frias: 23 cenários, 21 verdes, 129 asserções.
|
| Se precisar rodar um arquivo isolado depois de um `view:clear`, rode a suíte
| uma vez antes — ou aceite que o primeiro cenário vai falhar por tempo.
*/
pest()->browser()->timeout(45_000);

/*
|--------------------------------------------------------------------------
| Helpers compartilhados
|--------------------------------------------------------------------------
| Aqui, e não dentro de um arquivo de teste, porque em Pest as funções são
| globais no processo: helper declarado em dois arquivos é fatal error de
| redeclaração, e helper declarado num arquivo só desaparece quando você roda
| o OUTRO arquivo isolado (`php artisan test tests/Tenancy/Algum...Test.php`).
|
| Só entra aqui o que mais de uma suíte usa. Helper de um arquivo continua no
| arquivo.
*/

/**
 * O inventário de telas alcançáveis por URL fixa nos três painéis.
 *
 * Aqui, e não dentro de `tests/Browser/TelasDoKitTest.php`, porque DOIS arquivos o usam: o
 * smoke em navegador visita a lista, e `tests/Kit/InventarioDeTelasTest.php` a reconcilia
 * contra o que os painéis realmente registram. Era essa reconciliação que faltava (DT-07):
 * tela nova não entrava sozinha e a suíte seguia verde, dando a impressão de cobertura
 * completa.
 *
 * A lista continua escrita à mão de propósito. Derivar de `getPages()` + `getResources()`
 * cobre quase tudo, mas **perde** as telas que não são Page nem Resource do painel — as três
 * `two-factor-authentication`, que o Breezy registra como rota — e não sabe das exclusões
 * deliberadas. Ver a resolução de DT-07 em
 * `wikis/specs/feature/wiki-regressao-telas/regressao-de-telas/06-divida-tecnica.md`.
 *
 * @return array<string, list<string>>
 */
function telasDoKit(): array
{
    return [
        // O painel /app é o único dos três que não tinha nenhuma cobertura de tela: o
        // PaginasInfraTest cobria 15 rotas de /infra e 3 de /admin, e o painel de negócio
        // tinha só o `GET /app` genérico do PaineisTest.
        'app' => [
            '/app',
            '/app/meu-perfil',
            '/app/two-factor-authentication',
            '/app/convites',
            '/app/convites/create',
            '/app/convites-recebidos',
            '/app/users',
            '/app/users/create',
            /*
             * `/app/projetos` saiu daqui: o resource de exemplo só existe com a demo
             * ligada (`config('kit.demo')` + tenancy), e esta suíte roda single-tenant.
             *
             * ⚠️ As rotas `/app/convites` e `/app/users` acima estão na MESMA situação —
             * `UserResource` e `ConviteResource` do painel de negócio se escondem sem
             * tenancy, então aqui elas respondem 403. Elas continuam na lista porque
             * `assertNoJavaScriptErrors()` passa numa página de 403 (ela não tem erro de
             * JS nenhum), o que significa que estas três linhas nunca provaram nada
             * sobre as telas. Mover para `tests/BrowserTenancy` é o conserto; fica
             * registrado em vez de removido em silêncio.
             */
        ],
        'admin' => [
            '/admin',
            '/admin/meu-perfil',
            '/admin/two-factor-authentication',
            '/admin/users',
            '/admin/users/create',
            // Shield e onboarding são Resources de plugin, e incompatibilidade de versão de
            // plugin aparece na primeira visita, não no boot.
            '/admin/shield/roles',
            '/admin/shield/roles/create',
            '/admin/convites',
            '/admin/convites/create',
            '/admin/organizacoes',
            '/admin/organizacoes/create',
            /*
             * A tela de configurações da instalação. Entra aqui porque é Page do
             * painel, e o `InventarioDeTelasTest` reprova enquanto ela não estiver
             * listada — o que também lhe dá o smoke de navegador de graça, no lote
             * de `TelasDoKitTest`.
             */
            '/admin/configuracoes-da-aplicacao',
            '/admin/agentes-ia',
            '/admin/agentes-ia/create',
            '/admin/onboarding-flows',
            '/admin/onboarding-flows/create',
            '/admin/onboarding-conditions',
            '/admin/onboarding-conditions/create',
        ],
        // No /infra quase toda tela vem de um pacote de terceiro.
        'infra' => [
            '/infra',
            '/infra/meu-perfil',
            '/infra/two-factor-authentication',
            '/infra/health-check-results',
            '/infra/backup-runs',
            '/infra/queue-monitors',
            '/infra/queue-monitors/failures',
            /*
             * `/infra/queue-monitors/pending` saiu daqui, e não por escolha de escopo: a
             * rota NÃO EXISTE nesta suíte. O `getPages()` do resource só registra a página
             * de pendentes quando `config('queue.default') === 'database'`
             * (`vendor/croustibat/filament-jobs-monitor/src/Models/QueueJob.php:59-64`,
             * chamado em `.../Resources/QueueMonitorResource.php:386`), e o `phpunit.xml`
             * fixa `QUEUE_CONNECTION=sync`.
             *
             * A linha ficou aqui desde a rodada original visitando a página de 404 — e
             * `assertNoJavaScriptErrors()` passa num 404. Foi o primeiro achado da guarda
             * de DT-07, e é exatamente o defeito que ela existe para pegar.
             */
            '/infra/audits',
            '/infra/authentication-logs',
            '/infra/logs',
            '/infra/dependency-graph',
            '/infra/composer-release-packages',
            '/infra/execucoes-ia',
            // Roda com PULSE_ENABLED=false (phpunit.xml): a tela precisa abrir mesmo assim,
            // porque Pulse desligado não é Pulse quebrado.
            '/infra/pulse',
            '/infra/command-center/commands',
            '/infra/command-center/history',
            '/infra/command-center/definitions',
            '/infra/command-center/definitions/create',
            /*
             * As três telas da 0.17.0.
             *
             * A de exceções é a que mais precisa estar aqui, e não pelo motivo óbvio: o
             * plugin dela resolve o painel CORRENTE, e um registro errado não quebra esta
             * tela — quebra a aplicação inteira, em todo request e em todo comando artisan.
             * Um smoke em navegador é justamente o que pega isso de um jeito que nenhum
             * `$this->get()` isolado pegaria.
             *
             * A Lixeira e a trilha de e-mail abrem VAZIAS numa instalação nova, e é assim
             * mesmo: o que se prova aqui é que a tela renderiza sem erro de JS, não que há
             * dado nela.
             */
            '/infra/exceptions',
            '/infra/mail-logs',
            '/infra/recycle-bin',
        ],
    ];
}

/**
 * Grava uma configuração do kit direto na tabela, SEM passar por `Settings::save()`.
 *
 * Duas razões, e as duas mudam resultado: `save()` dispara `SavingSettings`, o que criaria
 * trilha de auditoria já no ARRANJO e estragaria a contagem absoluta de CT-34; e o container
 * guarda a instância de settings como singleton, então sem o `forgetInstance` o objeto
 * devolvido depois seria o de antes da escrita.
 *
 * Aqui, e não dentro de um arquivo de teste, porque QUATRO usam:
 * `ConfiguracoesDoKitTest`, `ConfiguracoesDoKitTelaTest`, `DefaultsDeTabelaTest` e
 * `IdentidadeDoKitTest`.
 */
function gravarConfiguracao(string $propriedade, mixed $valor): void
{
    SettingsProperty::query()
        ->where('group', ConfiguracoesDoKit::group())
        ->where('name', $propriedade)
        ->update(['payload' => json_encode($valor)]);

    app()->forgetInstance(ConfiguracoesDoKit::class);
}

/**
 * Chama o alinhamento da config como o boot chamaria.
 *
 * Com `RefreshDatabase` o `KitServiceProvider::boot()` roda ANTES das migrations — a tabela
 * `settings` ainda não existe, o alinhamento é no-op, e é justamente isso que mantém os
 * valores forçados no `phpunit.xml` valendo para a suíte inteira. Quem quer exercitar o
 * alinhamento o chama.
 *
 * `Closure::call()` no provider porque o método é protegido de propósito: ele não é API, é
 * um passo do boot. Quem prova que o boot o chama de verdade é CT-37, por varredura.
 */
function alinharConfiguracoesDoKit(): void
{
    $provider = new KitServiceProvider(app());

    (fn () => $this->configureSettingsDoKit())->call($provider);
}

/** Espia só o channel `configuracoes`; os outros continuam reais. */
function espiarConfiguracoes(): LoggerInterface
{
    $canal = Mockery::spy(LoggerInterface::class);

    Log::partialMock()->shouldReceive('channel')->with('configuracoes')->andReturn($canal);

    return $canal;
}

function tenant(string $nome, string $slug, bool $ativo = true): Tenant
{
    return Tenant::create(['nome' => $nome, 'slug' => $slug, 'ativo' => $ativo]);
}

function usuario(string $email = 'user@example.com'): User
{
    return User::create(['name' => 'Usuário', 'email' => $email, 'password' => 'password']);
}

/**
 * Usuário com e-mail único e papel OPCIONAL — o `null` é o ponto dela.
 *
 * A diferença para `usuarioDoKit()`, que é a vizinha mais parecida: aqui o papel pode ser
 * nulo, porque "quem não tem papel nenhum não entra em painel nenhum" é um caso que precisa
 * de persona própria; e o e-mail é gerado, porque vários casos criam mais de um usuário no
 * mesmo teste.
 */
function usuarioCom(?string $papel): User
{
    $user = User::create([
        'name'     => 'Teste',
        'email'    => fake()->unique()->safeEmail(),
        'password' => 'password',
    ]);

    if ($papel !== null) {
        $user->assignRole($papel);
    }

    return $user;
}

/**
 * O que o middleware do painel faria num request real.
 *
 * Teste de componente Livewire não passa por ele, e as duas chaves são indispensáveis: sem
 * `setTenant` todo caso cairia no ramo fail-closed de `getEloquentQuery()`; sem
 * `setPermissionsTeamId` o `syncRoles()` gravaria em `Tenant::CONTEXTO_GLOBAL`.
 */
function noPainelDa(Tenant $tenant): void
{
    Filament::setCurrentPanel('app');
    Filament::setTenant($tenant, isQuiet: true);
    app(PermissionRegistrar::class)->setPermissionsTeamId($tenant->getKey());
}

/**
 * Define E BOOTA o painel — o que um request real faz e um teste de componente não.
 *
 * `Filament::setCurrentPanel()` só troca a propriedade `$currentPanel`
 * (FilamentManager.php:885-892). Quem chama `Panel::boot()` é `Filament::bootCurrentPanel()`, e o
 * único chamador dele em todo o Filament é o middleware `SetUpPanel` — que teste de componente
 * Livewire não atravessa.
 *
 * Faz diferença sempre que a tela depende de algo registrado no `boot()` de um plugin. O caso
 * concreto: os macros `ImageColumn::simpleLightbox()` do solution-forest/filament-simplelightbox.
 * Sem o boot, a tela morre com `BadMethodCallException` no ARRANJO do teste, sem defeito nenhum
 * no código.
 *
 * Aqui, e não dentro de um arquivo de teste, porque mais de um arquivo usa.
 */
function noPainelBootado(string $painel): void
{
    Filament::setCurrentPanel($painel);
    Filament::bootCurrentPanel();
}

/**
 * O `.env` do diretório temporário do caso — nunca o do projeto.
 *
 * Os casos que exercitam escrita de `.env` (`CustomizadorDaInstalacaoTest`,
 * `HostLocalTest`) copiam o `.env.example` para um diretório temporário e injetam esse
 * diretório na classe sob teste. Apontar para `base_path()` faria a suíte destruir o
 * ambiente de quem a roda.
 *
 * Aqui, e não dentro de um arquivo de teste, porque mais de um arquivo usa — em PHP função é
 * global no processo, e helper que vaza de um arquivo para o vizinho só estoura em
 * `--parallel`, `--tia` ou ao rodar um arquivo sozinho. Ver `.ai/rules/testes.md`.
 */
function envDoTeste(): string
{
    return File::get(test()->base.'/.env');
}

/** O valor efetivo da chave, já com as aspas e os escapes resolvidos pelo dotenv. */
function valorNoEnv(string $chave): ?string
{
    $lidos = Dotenv\Dotenv::parse(envDoTeste());

    return $lidos[$chave] ?? null;
}

/** Nome da pivot de papéis, que muda com `config('permission.table_names')`. */
function pivotDePapeis(): string
{
    return (string) config('permission.table_names.model_has_roles', 'model_has_roles');
}

/**
 * Usuário com papel atribuído no contexto corrente — a persona de quem OPERA a tela.
 *
 * Sem organização explícita, ao contrário de `usuarioComPapel()`: serve às suítes
 * single-tenant, onde não existe contexto para escolher.
 */
function usuarioDoKit(string $papel, string $email = 'user@example.com'): User
{
    $user = usuario($email);

    $user->assignRole($papel);

    return $user;
}

/**
 * A chave da pagina unica de login (`kit.login.unificado`), ligada ou desligada.
 *
 * Vive aqui, e nao no arquivo de teste, porque dois arquivos a usam: `tests/Kit/LoginUnificadoTest.php`
 * e `tests/Tenancy/LoginUnificadoTenancyTest.php`. Helper cruzado declarado num deles some quando o
 * Pest carrega um subconjunto (`--parallel`, `--tia`, um arquivo so). Ver `.ai/rules/testes.md`.
 */
function ligarLoginUnificado(bool $ligado = true): void
{
    config()->set('kit.login.unificado', $ligado);
}

/**
 * Liga o dashboard dinâmico para o caso corrente — flag e, opcionalmente, a
 * lista de painéis (`[]` = todos, a tradução de `App\Support\DashboardDinamico`).
 *
 * Aqui e não num arquivo de teste porque DOIS arquivos a usam
 * (`tests/Kit/DashboardDinamicoTest.php` e
 * `tests/Tenancy/DashboardDinamicoTenancyTest.php`) — `.ai/rules/testes.md`.
 *
 * @param  list<string>  $paineis
 */
function ligarDashboardDinamico(bool $ligado = true, array $paineis = []): void
{
    config()->set('kit.dashboard_dinamico.habilitado', $ligado);
    config()->set('kit.dashboard_dinamico.paineis', $paineis);
}

/**
 * Uma organizacao com as tres condicoes que o registro aberto avalia: existe, `ativo` e
 * `registro_habilitado` (`App\Support\RegistroAberto::organizacao()`).
 *
 * Usada por `tests/Tenancy/RegistroAbertoTenancyTest.php` e por
 * `tests/Tenancy/LoginUnificadoTenancyTest.php` — por isso mora aqui (`.ai/rules/testes.md`).
 */
function organizacaoComRegistro(string $slug = 'acme', bool $ativo = true, bool $registro = true): Tenant
{
    return Tenant::factory()->create([
        'slug'                => $slug,
        'ativo'               => $ativo,
        'registro_habilitado' => $registro,
    ]);
}

/**
 * Um painel registrado em tempo de teste, como a aplicacao registraria um `PanelProvider` novo
 * depois da instalacao. `FilamentManager::registerPanel()` e publico e o registro morre com o
 * container do teste.
 */
function painelRegistradoEmTeste(string $id): Panel
{
    $painel = Panel::make()->id($id)->path($id);

    // Pelo `PanelRegistry`, e nao por `Filament::registerPanel()`: medido, a chamada pela facade
    // nao aparece em `Filament::getPanels()` dentro do teste, enquanto o registry (singleton, o
    // mesmo objeto antes e depois) registra. A facade continua sendo o caminho de producao.
    app(PanelRegistry::class)->register($painel);

    return $painel;
}

/**
 * O HTML depois do fechamento do <title>.
 *
 * O nome da aplicacao aparece no titulo de toda pagina do Filament: afirmar um rotulo de cartao
 * com `assertSee` cru passaria medindo o <title>. Este recorte poe a asserção no corpo.
 */
function corpoDepoisDoTitulo(string $html): string
{
    $fim = stripos($html, '</title>');

    return $fim === false ? $html : substr($html, $fim + 8);
}

/**
 * Relê o `config/kit.php` com uma variável de ambiente forçada.
 *
 * O `require` direto no arquivo, e não `config()`, porque a config do processo de teste já foi
 * resolvida no boot: mexer em `putenv()` depois não a reavalia. É a mesma manobra que o kit já
 * usou para exercitar coerção de env, e ela funciona porque o arquivo é uma expressão pura —
 * devolve array e não depende de estado do container além do helper `env()`.
 */
function kitConfigCom(string $chave, ?string $valor): array
{
    $anterior = $_ENV[$chave] ?? null;

    if ($valor === null) {
        unset($_ENV[$chave], $_SERVER[$chave]);
        putenv($chave);
    } else {
        $_ENV[$chave]    = $valor;
        $_SERVER[$chave] = $valor;
        putenv("{$chave}={$valor}");
    }

    try {
        return require base_path('config/kit.php');
    } finally {
        if ($anterior === null) {
            unset($_ENV[$chave], $_SERVER[$chave]);
            putenv($chave);
        } else {
            $_ENV[$chave]    = $anterior;
            $_SERVER[$chave] = $anterior;
            putenv("{$chave}={$anterior}");
        }
    }
}

/**
 * Liga um provedor de login social para o caso corrente, com as três chaves preenchidas.
 *
 * Aqui e não num arquivo de teste porque TRÊS arquivos usam
 * (`tests/Kit/LoginSocialProvedoresTest.php`, `tests/Kit/SegredosDoSettingsTest.php` e
 * `tests/Tenancy/LoginSocialProvedoresTenancyTest.php`) — `.ai/rules/testes.md`. Em PHP a
 * função é global no processo, então declarar num arquivo e usar noutro fica VERDE quando o
 * Pest carrega todos e estoura `Call to undefined function` sob `--parallel`, `--tia` e arquivo
 * isolado, que são os três comandos mais usados.
 *
 * `$credenciais` sobrescreve chaves de `services.{provedor}` — passar `client_secret => ''` é o
 * caso do `.env` preenchido pela metade, que é o que o par de CT-01 exige por provedor.
 *
 * @param  array<string, mixed>  $credenciais
 */
function ligarProvedor(ProvedorSocial $provedor, array $credenciais = []): void
{
    config()->set("kit.login.{$provedor->value}.habilitado", true);

    config()->set('services.'.$provedor->value, array_merge([
        'client_id'     => 'id-de-teste',
        'client_secret' => 'segredo-de-teste',
        'redirect'      => "/auth/{$provedor->value}/callback",
    ], $credenciais));
}

/**
 * O usuário do provedor com o campo de verificação SÓ no bruto — como o driver real entrega.
 *
 * Existe porque **o bruto muda de provedor para provedor**, e é justamente essa diferença que a
 * barreira de e-mail verificado atravessa (ADR-03 da wiki `mais-provedores-sociais`):
 *
 * | Provedor | O que o helper monta no bruto |
 * |---|---|
 * | `google` | `email_verified => true` (o alias `verified_email` é do provider) |
 * | `linkedin-openid` | `email_verified => true` |
 * | `x` | só `email` — a PRESENÇA é a prova, o X não tem campo de verificação |
 * | `github` | nada de verificação; quem prova é o `Http::fake()` de `/user/emails` |
 *
 * ## Por que NÃO basta `Two\User::fake()` com o campo dentro
 *
 * `fake()` faz `setRaw($atributos)` **e** `map($atributos)`
 * (`vendor/laravel/socialite/src/Two/User.php:58`). Ou seja, ele popula o **bruto** e o
 * **atributo** — e aí uma implementação que leia `$doProvedor->email_verified` em vez de
 * `getRaw()` fica **verde em todo cenário**, e em produção recusa **todo** login de Google:
 * `AbstractUser::map()` só atribui a propriedade quando `property_exists` (`:138-149`), e o
 * `GoogleProvider` real não a mapeia. O duplo esconderia exatamente o defeito que a barreira
 * existe para impedir.
 *
 * Daí a ordem aqui: `fake()` recebe só os campos que o provedor real MAPEIA, e o `setRaw()`
 * depois substitui o bruto pelo que o provedor real ENTREGA. Um sobrescreve o outro
 * (`AbstractUser::setRaw()` devolve `$this`), e o campo de verificação nunca vira atributo.
 *
 * `token = 'fake-token'` continua vindo do `fake()` — é esse token que o `Http::assertSent()` do
 * caso do GitHub confere.
 *
 * @param  array<string, mixed>  $bruto  acrescenta/sobrescreve o payload bruto
 * @param  array<string, mixed>  $mapeados  acrescenta/sobrescreve o que o provedor mapeia
 */
function usuarioSocialFalso(ProvedorSocial $provedor, array $bruto = [], array $mapeados = []): UsuarioDoProvedor
{
    $mapeados = array_merge([
        'id'    => "{$provedor->value}-123",
        'name'  => 'Quem Já Tem',
        'email' => 'ja.tem@example.com',
    ], $mapeados);

    $verificacao = match ($provedor) {
        ProvedorSocial::Google, ProvedorSocial::LinkedIn => ['email_verified' => true],

        // O X não tem campo de verificação e o GitHub não expõe nenhum no bruto — de propósito,
        // e é o que os casos daquelas duas regras exercitam.
        ProvedorSocial::X, ProvedorSocial::Github => [],
    };

    return UsuarioDoProvedor::fake($mapeados)
        ->setRaw(array_merge($mapeados, $verificacao, $bruto));
}

/**
 * O valor gravado de uma propriedade do settings, lido DIRETO da tabela.
 *
 * Veio de `tests/Kit/ConfiguracoesDoKitTelaTest.php`, onde era declarada localmente, quando
 * ganhou o segundo consumidor (`tests/Kit/SegredosDoSettingsTest.php`, que precisa provar que o
 * `payload` de cada `client_secret` é criptograma e não texto claro). Mover foi obrigatório, não
 * escolha: a alternativa era um clone com outro nome, que `.ai/rules/testes.md` proíbe por nome
 * — "troca um erro que estoura por duas funções idênticas que ninguém percebe".
 *
 * Lê o `payload` cru justamente para NÃO passar pelo decifrador do spatie: quem pergunta se o
 * valor está cifrado não pode perguntar para quem decifra.
 */
function configuracaoGravada(string $propriedade): mixed
{
    return json_decode((string) SettingsProperty::query()
        ->where('group', ConfiguracoesDoKit::group())
        ->where('name', $propriedade)
        ->value('payload'), associative: true);
}

/** Espia só o channel `autenticacao`; os outros continuam reais. */
function espiarAutenticacao(): LoggerInterface
{
    $canal = Mockery::spy(LoggerInterface::class);

    Log::partialMock()->shouldReceive('channel')->with('autenticacao')->andReturn($canal);

    return $canal;
}

/**
 * Usuário com papel atribuído num contexto explícito.
 *
 * Com `permission.teams` ligado, `model_has_roles.team_id` guarda o contexto e
 * `assignRole()` carimba o que estiver fixado no PermissionRegistrar. Papel do painel
 * /app pertence a uma organização; papel de /admin e /infra pertence ao contexto global.
 */
function usuarioComPapel(string $papel, ?Tenant $tenant = null, string $email = 'user@example.com'): User
{
    return papelNaOrganizacao(usuario($email), $papel, $tenant);
}

/**
 * Duas organizações com identidade visual diferente, e uma pessoa que opera as duas — e que
 * também administra a instalação.
 *
 * O papel `admin` no contexto global não é enfeite: é ele que torna o vazamento de identidade
 * visual OBSERVÁVEL no mesmo cenário. Sem ele o `/admin` responderia 403, e o caso mediria o
 * barramento em vez da cor.
 *
 * Usada pelos casos de identidade visual das suítes `Tenancy` e `BrowserTenancy`.
 *
 * @return array{acme: Tenant, globex: Tenant, usuario: User}
 */
function duasOrganizacoes(): array
{
    $acme   = Tenant::factory()->comIdentidadeVisual('#7c3aed')->create(['nome' => 'Acme', 'slug' => 'acme']);
    $globex = Tenant::factory()->comIdentidadeVisual('#059669')->create(['nome' => 'Globex', 'slug' => 'globex']);

    $usuario = usuarioComPapel('panel_user', $acme);

    papelNaOrganizacao($usuario, 'panel_user', $globex);
    papelNaOrganizacao($usuario, 'admin');

    $usuario->tenants()->attach([$acme->id, $globex->id]);

    return compact('acme', 'globex', 'usuario');
}

/**
 * A fronteira entre dois requests — que o teste não tem de graça, e o request de verdade tem.
 *
 * Em produção cada request nasce com container próprio (e o Octane, que reaproveita o processo,
 * descarta os `scoped` e as facades entre um e outro). No teste — tanto no HTTP quanto no
 * navegador, porque o servidor do pest-plugin-browser roda IN-PROCESS — o mesmo container
 * atravessa todas as visitas. Dois bindings guardam estado de painel e mentem sobre o request
 * seguinte:
 *
 * - `ColorManager` cacheia a paleta em `$cachedColors` (`ColorManager.php:70-78`) e nunca a
 *   invalida: sem isto, a cor da PRIMEIRA organização visitada é devolvida para todas as outras.
 *   São DUAS caches, e limpar só uma não adianta — o container guarda a instância, e a Facade
 *   guarda outra referência em `Facade::$resolvedInstance`, fora do alcance de `forgetInstance()`.
 * - `SpotlightActionRegistry` é singleton (`FilamentSearchSpotlightServiceProvider.php:25`) e
 *   acumula as ações "Criar X" de todo painel visitado. No painel seguinte, o ⌘K resolve
 *   `getUrl('create')` de um resource que não existe ali e o request morre em 500
 *   (`Route [filament.app.resources.agentes-ia.create] not defined`). É o que impede qualquer
 *   cenário de atravessar dois painéis sem esta fronteira.
 */
function fronteiraDeRequest(): void
{
    app()->forgetInstance(ColorManager::class);
    Facade::clearResolvedInstance(ColorManager::class);

    app()->forgetInstance(AssetManager::class);
    Facade::clearResolvedInstance(AssetManager::class);

    app()->forgetInstance(FilamentManager::class);
    app()->forgetInstance('filament');
    Facade::clearResolvedInstance('filament');

    app()->forgetInstance(SpotlightActionRegistry::class);
}

/**
 * Atribui papel a um usuário que JÁ existe, dentro do contexto de uma organização.
 *
 * É a diferença entre a persona funcionar e ela entrar num painel vazio: papel gravado em
 * `Tenant::CONTEXTO_GLOBAL` fica invisível dentro do /app, porque o `wherePivot` do spatie
 * filtra pelo team do request. Ver ADR-10 da wiki admin-da-organizacao.
 *
 * `null` no tenant = contexto global, que é onde vivem `admin`, `infra` e `master_global`.
 */
function papelNaOrganizacao(User $user, string $papel, ?Tenant $tenant = null): User
{
    $registrar = app(PermissionRegistrar::class);
    $anterior  = $registrar->getPermissionsTeamId();

    try {
        $registrar->setPermissionsTeamId($tenant?->getKey() ?? Tenant::CONTEXTO_GLOBAL);
        $user->unsetRelation('roles');
        $user->assignRole($papel);
    } finally {
        $registrar->setPermissionsTeamId($anterior);
        $user->unsetRelation('roles');
    }

    return $user;
}

/**
 * Revoga uma ou mais permissões do papel — a persona discriminante de toda checagem de permissão.
 *
 * O par que uma feature de autorização precisa é "quem tem entra, quem não tem toma 403", e o
 * segundo lado dele **não** pode ser um papel criado à mão sem permissão nenhuma: um papel assim
 * perde também o `canAccessPanel()`, e o 403 passa a vir da porta do painel em vez da tela. O
 * cenário ficaria verde com a feature inteira removida.
 *
 * Revogar do papel REAL é o único arranjo em que a única variável é a permissão.
 *
 * `master_global` não serve para nada disto: ele vence toda permissão pelo `Gate::before`
 * (`App\Providers\KitServiceProvider`). Ele é a linha de CONTROLE, nunca a de prova.
 *
 * Aqui, e não dentro de um arquivo de teste, porque mais de um arquivo usa
 * (`.ai/rules/testes.md` §"Helper de teste usado por mais de um arquivo").
 */
function semAPermissao(string $papel, string ...$permissoes): Role
{
    $role = papelDoKit($papel);

    foreach ($permissoes as $permissao) {
        $role->revokePermissionTo($permissao);
    }

    app(PermissionRegistrar::class)->forgetCachedPermissions();

    return $role;
}

/**
 * Fixa o painel corrente E descarta a instância memoizada do Shield.
 *
 * Necessário em QUALQUER caso que percorra mais de um painel no mesmo processo, e a razão é a mesma
 * que `App\Support\Paineis` documenta: `FilamentShield` é registrado como `scoped` e memoiza com
 * `once()`, que é por INSTÂNCIA. Trocar o painel corrente não invalida nada — e a **facade** ainda
 * guarda o objeto em `Facade::$resolvedInstance`, então nem o `forgetInstance()` do container basta.
 *
 * Sem os dois descartes, `FilamentShield::getPages()`/`getWidgets()` devolve o conjunto do PRIMEIRO
 * painel em todas as voltas. E a consequência é pior que um resultado errado: as traits
 * `HasPageShield`/`HasWidgetShield` **falham abertas** quando não acham a classe na lista — caem em
 * `parent::canAccess()`/`parent::canView()`, que é `true`. O caso mediria "a tela abre" e concluiria
 * que a permissão não está sendo consultada, quando o que aconteceu foi o arranjo consultar o painel
 * errado.
 *
 * Em request real isso não acontece: um request é um painel só, e o middleware `SetUpPanel` fixa o
 * painel antes de qualquer Page ou Widget ser tocado.
 */
function noPainelDoShield(string $painel): void
{
    app()->forgetInstance('filament-shield');
    Facade::clearResolvedInstance('filament-shield');

    Filament::setCurrentPanel($painel);
}

/**
 * O papel semeado, para asserção direta sobre a matriz de permissões.
 *
 * `Role::findByName()` e não `Role::where('name', …)->first()`: o segundo devolve `null` em silêncio
 * quando o papel não existe naquela suíte, e a asserção seguinte falha com "call to a member
 * function on null" — que esconde a causa real, que é suíte errada. `findByName()` lança
 * `RoleDoesNotExist` com o nome do papel, e é `.ai/rules/testes.md` §"Nem todo papel do kit existe
 * em toda suíte" que diz o que fazer com essa mensagem.
 */
function papelDoKit(string $nome): Role
{
    /** @var Role $role */
    $role = Role::findByName($nome, config('auth.defaults.guard', 'web'));

    return $role;
}

/**
 * Convite pendente para um e-mail, SEM enviar — quem envia é quem precisa do token em claro.
 *
 * `role_id` explícito sempre: o default da `ConviteFactory` é
 * `Config::roleModel()::query()->value('id')` — o PRIMEIRO papel da tabela, que é o
 * `master_global`. Um convite criado sem esta linha concede o papel guarda-chuva.
 *
 * `$tenant` nulo serve às suítes single-tenant, onde não há organização a que vincular.
 *
 * Aqui, e não dentro de um arquivo de teste, porque QUATRO arquivos usam. Antes eram dois
 * near-clones locais — `convitePara()` em `tests/Kit/ConviteUsuarioExistenteTest.php` e
 * `ofertaPara()` em `tests/Tenancy/...`, este um superconjunto daquele. `.ai/rules/testes.md` é
 * explícita: clone com outro nome troca um erro que estoura por duas funções idênticas que
 * ninguém percebe.
 *
 * ## A organização pedida é GARANTIDA, e não só passada
 *
 * Com o painel do Resource bootado, o Filament carimba o `tenant_id` do registro com a
 * organização CORRENTE e descarta o que veio no atributo:
 * `Resources\Resource\Concerns\BelongsToTenant::observeTenancyModelCreation()`
 * (`vendor/filament/filament/src/.../BelongsToTenant.php:158-185`) registra um `creating` que
 * faz `$relationship->associate($tenant)` sem verificar se a coluna já estava preenchida.
 *
 * Medido: sem o painel `app` bootado o valor passado é respeitado e não há listener nenhum; com
 * o painel bootado e a Acme corrente, um convite pedido para a Globex nasce na Acme.
 *
 * **Isso é do vendor e é fail-safe — não "conserte" a trava.** Em produção ela impede que um
 * payload forjado crie registro de outra organização de dentro do /app, e o /admin não é afetado
 * (`getCurrentPanel() !== $panel` desliga o hook). Ver ADR-01 da wiki
 * `wikis/specs/fix/convite-carimba-organizacao-corrente/`.
 *
 * A correção abaixo é CONDICIONAL de propósito: ela só age quando o gravado divergiu do pedido.
 * Incondicional, ela mascararia o dia em que o Filament passasse a respeitar a coluna — e o caso
 * que mede o carimbo diretamente (`CarimboDeOrganizacaoTest`) ficaria verde por engano.
 *
 * @param  array<string, mixed>  $atributos
 */
function ofertaPara(string $email, ?Tenant $tenant = null, string $papel = 'panel_user', array $atributos = []): Convite
{
    $convite = Convite::factory()->create([
        'email'     => $email,
        'role_id'   => Role::findByName($papel)->getKey(),
        'tenant_id' => $tenant?->getKey(),
        ...$atributos,
    ]);

    $pedido = $atributos['tenant_id'] ?? $tenant?->getKey();

    if ($pedido !== null && (int) $convite->tenant_id !== (int) $pedido) {
        DB::table($convite->getTable())->where('id', $convite->getKey())->update(['tenant_id' => $pedido]);
        $convite->refresh();
    }

    return $convite;
}

/**
 * Toda a documentação de um idioma: o README mais as páginas do site.
 *
 * Existe por causa da migração para o GitHub Pages: as afirmações de
 * comportamento que os READMEs faziam mudaram de arquivo, e as asserções que as
 * vigiavam precisavam mudar de alvo NO MESMO COMMIT — senão elas continuariam
 * verdes sem proteger nada, que é o modo silencioso de perder uma garantia.
 *
 * O oráculo continua sendo "a documentação deste idioma afirma X", que é o que
 * as cláusulas de requisito pedem; o que deixou de importar é EM QUE ARQUIVO ela
 * afirma. Reorganizar o site não deve reprovar teste de conteúdo.
 *
 * `docs/` é `export-ignore`: num projeto instalado ele não existe, e aí só o
 * README é lido — que continua trazendo o essencial.
 */
function documentacaoDoKit(string $idioma): string
{
    $readme = $idioma === 'en' ? 'README.en.md' : 'README.md';
    $partes = [(string) file_get_contents(base_path($readme))];

    $raiz = base_path("docs/{$idioma}");

    if (is_dir($raiz)) {
        $arquivos = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($raiz));

        foreach ($arquivos as $arquivo) {
            if ($arquivo->isFile() && $arquivo->getExtension() === 'md') {
                $partes[] = (string) file_get_contents($arquivo->getPathname());
            }
        }
    }

    return implode('
', $partes);
}

/*
|--------------------------------------------------------------------------
| Site de documentação (docs/) — helpers compartilhados
|--------------------------------------------------------------------------
| `docs/` é `export-ignore`: existe na árvore do kit e não no projeto instalado.
| A sentinela é `.github`, pelo mesmo motivo de `tests/Kit/KitUpdateTest.php` —
| e NÃO `is_dir('docs')`, que seria auto-anulante: se a migração não acontecer,
| tudo é ignorado e a suíte fica verde com zero entrega (CT-10 da wiki
| `site-de-documentacao` inspeciona os arquivos de teste para impedir isso).
*/

/** Estamos na árvore do kit (e não num projeto nascido do `create-project`)? */
function naArvoreDoKit(): bool
{
    return is_dir(base_path('.github'));
}

/**
 * As páginas do site de um idioma, indexadas pelo caminho relativo a `docs/{idioma}/`
 * (sempre com `/`, mesmo no Windows), em ordem alfabética.
 *
 * Fora da árvore do kit `docs/` não existe (`.gitattributes: /docs export-ignore`) — devolve
 * `[]` em vez de deixar o `RecursiveDirectoryIterator` lançar `UnexpectedValueException` (RD-02:
 * o docblock de `blocosMermaidDaArvore()` já prometia isto, e não era verdade até este `is_dir`).
 *
 * @return array<string, string>
 */
function paginasDoSite(string $idioma): array
{
    $raiz = base_path("docs/{$idioma}");

    if (! is_dir($raiz)) {
        return [];
    }

    $paginas = [];

    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($raiz, FilesystemIterator::SKIP_DOTS)) as $arquivo) {
        if ($arquivo->isFile() && $arquivo->getExtension() === 'md') {
            $relativo           = str_replace('\\', '/', substr($arquivo->getPathname(), strlen($raiz) + 1));
            $paginas[$relativo] = (string) file_get_contents($arquivo->getPathname());
        }
    }

    ksort($paginas);

    return $paginas;
}

/**
 * Todo bloco Mermaid dentro de um texto Markdown: o conteúdo cercado, a linha da cerca de
 * abertura, o ID de catálogo (o marcador `%% DG-xx` dentro do bloco, ou `null` se ausente) e se a
 * cerca está dentro de um comentário HTML (`<!-- … -->`).
 *
 * Aqui, e não dentro de um arquivo de teste, porque DOIS arquivos o usam —
 * `tests/Kit/DiagramasDaArquiteturaTest.php` e `tests/Tenancy/DiagramasDaArquiteturaTenancyTest.php`
 * —, o Setup Global do `04` da wiki `diagramas-da-arquitetura` declara o extrator como
 * compartilhado (`.ai/rules/testes.md`).
 *
 * ## Duas cercas, não uma (ciclo 2, A2-21 — R1)
 *
 * O CommonMark aceita a cerca de crase (` ``` `, 3 ou mais) e a de til (`~~~`, 3 ou mais) com a
 * mesma info string, e as duas RENDERIZAM no GitHub e no site. Um extrator que só reconhece três
 * crases perde o bloco de til ou de quatro crases em silêncio: o diagrama aparece na tela e
 * ninguém o confere (`[CT-103]`, linhas de til e de quatro crases). A info string precisa ser
 * exatamente "mermaid" (mais espaço em volta): um bloco ```php que MENCIONA a palavra "mermaid" no
 * corpo não é um bloco Mermaid — a cerca de abertura dele não casa.
 *
 * ## Comentário HTML esconde o bloco do leitor, não do extrator ingênuo (ciclo 2, A2-21 — R1)
 *
 * `<!-- ```mermaid … ``` -->` é um bloco que o GitHub NÃO mostra e que um extrator de cercas cru
 * conta do mesmo jeito — o achado que `dentroDeComentarioHtml` fixa. Este extrator só LEVANTA a
 * marca; quem decide "recusa, nomeando o comentário e a linha" é a guarda que a consome.
 *
 * ## Toda cerca de código é rastreada, não só a de Mermaid e o comentário HTML (CR-7)
 *
 * Sem isto, um `<!--` de EXEMPLO dentro de um bloco ` ```html `/` ```blade ` (ensinando como
 * esconder um diagrama) liga "dentro de comentário" até achar um `-->` qualquer — inclusive o
 * do PRÓPRIO fechamento da cerca de exemplo —, e um bloco visível de verdade que vier depois no
 * arquivo é lido como escondido. E um ` ```mermaid ` aninhado dentro de uma cerca de quatro
 * crases (um bloco Markdown de EXEMPLO, mostrando a sintaxe) é lido como diagrama real. A
 * correção: toda cerca de código de qualquer linguagem — info string diferente de `mermaid` —
 * é pulada inteira, do jeito que apareceu, até o fechamento da MESMA marca e do MESMO tamanho (ou
 * maior); só fora dela `<!--`/`-->` e ` ```mermaid ` contam.
 *
 * ## A info string pode ter META depois da linguagem (RD2-18)
 *
 * O CommonMark aceita texto depois da linguagem na info string da cerca de ABERTURA (` ```html
 * title="x" `, ` ```mermaid title="Exemplo" `) — só a linguagem (a primeira palavra) importa.
 * Exigir a linha INTEIRA em branco depois dela (como CR-7 fazia) faz a cerca alheia com meta não
 * casar nem como "mermaid" nem como "outra linguagem a pular": ela vira texto comum, o `<!--` de
 * exemplo dentro dela vaza (CR-7 de novo, por outra porta) e um ` ```mermaid ` de verdade que vier
 * depois some (`bloco escondido em cerca alheia lida como texto`). A cerca de FECHAMENTO continua
 * exigindo a linha inteira em branco (o CommonMark não aceita meta ali).
 *
 * ## Resíduo de RD2-18: crase que fecha na MESMA linha é span embutido, não cerca
 *
 * O CommonMark proíbe crase na info string de uma cerca de CRASE — é assim que ele distingue
 * ` ```texto``` ` (um span de código embutido, com a MESMA linha fechando) de uma cerca de
 * abertura de verdade. Sem checar isto, uma linha de PROSA que começa com esse span (ex. "```código
 * inline``` explica o resto da frença") casava como abertura de cerca "alheia" (o `\S*` da info
 * string não vê a segunda crase) e, sem fechamento genuíno depois, engolia o resto do arquivo —
 * inclusive um ` ```mermaid ` de verdade mais adiante. A cerca de TIL não tem essa restrição (o
 * CommonMark permite crase na info string dela), então o guard vale só para o marcador `` ` ``.
 *
 * @return list<array{bloco: string, linha: int, idCatalogo: ?string, dentroDeComentarioHtml: bool}>
 */
function blocosMermaidDe(string $markdown): array
{
    $linhas             = explode("\n", $markdown);
    $total              = count($linhas);
    $blocos             = [];
    $dentroDeComentario = false;

    for ($i = 0; $i < $total; $i++) {
        $linha = $linhas[$i];

        // Resíduo de RD2-18: se o marcador é CRASE e o resto da linha (depois do primeiro run de
        // crases) contém OUTRA crase, esta linha é um span de código embutido que fecha na própria
        // linha — nunca abertura de cerca (CommonMark: a info string de cerca de crase não pode
        // conter crase). O til não tem essa restrição.
        preg_match('#^\s*(`{3,}|~{3,})#', $linha, $marcadorDaLinha);
        $ehSpanEmbutido = ($marcadorDaLinha[1] ?? '') !== ''
            && $marcadorDaLinha[1][0] === '`'
            && str_contains(substr($linha, strlen($marcadorDaLinha[0])), '`');

        // Delimitador `#`, e não `~`: a própria alternativa da cerca de til usa o caractere `~`,
        // e um delimitador `~` cru quebra ali com "Unknown modifier" — o `~` da cerca fecha o
        // regex antes da hora.
        //
        // Sem `\s*$` no fim (RD2-18): a info string pode ter META depois da linguagem
        // (` ```html title="x" `) — só a PRIMEIRA palavra (a linguagem) decide se a cerca é
        // "mermaid" ou "outra, a pular"; exigir linha em branco depois dela perdia a cerca com
        // meta por inteiro (nem mermaid, nem "outra" — texto comum, e o `<!--` de exemplo vazava).
        if (! $ehSpanEmbutido
            && preg_match('#^\s*(`{3,}|~{3,})\s*(\S*)#', $linha, $cercaQualquer) === 1
            && $cercaQualquer[2] !== 'mermaid'
        ) {
            $marcadorAlheio = $cercaQualquer[1][0];
            $tamanhoAlheio  = strlen($cercaQualquer[1]);
            $fechouAlheio   = false;

            for ($j = $i + 1; $j < $total; $j++) {
                if (preg_match('#^\s*'.preg_quote($marcadorAlheio, '#').'{'.$tamanhoAlheio.',}\s*$#', $linhas[$j]) === 1) {
                    $i            = $j;
                    $fechouAlheio = true;
                    break;
                }
            }

            // Sem fechamento: o resto do arquivo é literal (dentro da cerca aberta).
            if (! $fechouAlheio) {
                $i = $total;
            }

            continue;
        }

        if (str_contains($linha, '<!--') && ! str_contains($linha, '-->')) {
            $dentroDeComentario = true;
        }

        // Idem (RD2-18): `mermaid` pode vir seguido de meta (` ```mermaid title="x" `) — o que
        // fecha a linha aqui é a cerca de FECHAMENTO, abaixo, que continua exigindo linha em
        // branco (o CommonMark não aceita meta ali). E, pelo mesmo resíduo acima, um span
        // embutido cuja info string comece por "mermaid" (` ```mermaid``` `) não abre cerca.
        if ($ehSpanEmbutido || preg_match('#^\s*(`{3,}|~{3,})\s*mermaid(?:\s|$)#', $linha, $cerca) !== 1) {
            if (str_contains($linha, '-->')) {
                $dentroDeComentario = false;
            }

            continue;
        }

        $marcador        = $cerca[1][0];
        $tamanho         = strlen($cerca[1]);
        $linhaDeAbertura = $i + 1;
        $escondido       = $dentroDeComentario;

        $conteudo = [];
        $fechou   = false;

        for ($j = $i + 1; $j < $total; $j++) {
            if (preg_match('#^\s*'.preg_quote($marcador, '#').'{'.$tamanho.',}\s*$#', $linhas[$j]) === 1) {
                $fechou = true;
                $i      = $j;
                break;
            }

            $conteudo[] = $linhas[$j];
        }

        // Cerca sem fechamento: não é um bloco válido, e a varredura segue da linha seguinte.
        if (! $fechou) {
            continue;
        }

        $texto = implode("\n", $conteudo);

        preg_match('~%%\s*(DG-\d+)~', $texto, $id);

        $blocos[] = [
            'bloco'                  => $texto,
            'linha'                  => $linhaDeAbertura,
            'idCatalogo'             => $id[1] ?? null,
            'dentroDeComentarioHtml' => $escondido,
        ];
    }

    return $blocos;
}

/**
 * Todos os blocos Mermaid da árvore do kit, num idioma: o README do idioma mais cada página real
 * de `docs/{idioma}/`, cada bloco marcado com o arquivo de origem e o idioma — o inventário que a
 * guarda do catálogo (R1, `[CT-01]`/`[CT-02]`) confere.
 *
 * Reusa `paginasDoSite()`, e não repete a varredura de diretório: fora da árvore do kit (`docs/`
 * é `export-ignore`) ela devolve `[]` e este array só traz o README.
 *
 * @return list<array{bloco: string, linha: int, idCatalogo: ?string, dentroDeComentarioHtml: bool, arquivo: string, idioma: string}>
 */
function blocosMermaidDaArvore(string $idioma): array
{
    $readme = $idioma === 'en' ? 'README.en.md' : 'README.md';

    $paginas = [$readme => (string) file_get_contents(base_path($readme))];

    foreach (paginasDoSite($idioma) as $relativo => $conteudo) {
        $paginas["docs/{$idioma}/{$relativo}"] = $conteudo;
    }

    $blocos = [];

    foreach ($paginas as $arquivo => $conteudo) {
        foreach (blocosMermaidDe($conteudo) as $bloco) {
            $blocos[] = [...$bloco, 'arquivo' => $arquivo, 'idioma' => $idioma];
        }
    }

    return $blocos;
}

/**
 * Localiza, na árvore REAL, o bloco de um DG do catálogo (fora de comentário HTML); `null` se
 * ausente.
 *
 * Aqui, e não em `tests/Kit/DiagramasDaArquiteturaTest.php` (nem clonado com outro nome em
 * `tests/Tenancy/DiagramasDaArquiteturaTenancyTest.php`, que tinha `blocoDoCatalogo()` idêntico
 * byte a byte), porque os DOIS arquivos o usam — `.ai/rules/testes.md` (RD-11).
 *
 * @return ?array{bloco: string, linha: int, idCatalogo: ?string, dentroDeComentarioHtml: bool, arquivo: string, idioma: string}
 */
function blocoDoCatalogoNaArvore(string $id, string $idioma): ?array
{
    foreach (blocosMermaidDaArvore($idioma) as $bloco) {
        if ($bloco['idCatalogo'] === $id && ! $bloco['dentroDeComentarioHtml']) {
            return $bloco;
        }
    }

    return null;
}

/*
|--------------------------------------------------------------------------
| RQ-34 — extrator de arestas normalizado (flowchart, erDiagram, sequenceDiagram, stateDiagram)
|--------------------------------------------------------------------------
|
| Aqui, e não em tests/Kit/DiagramasDaArquiteturaTest.php, porque toda guarda de ARESTA do bloco
| REAL usa (CT-10, CT-11, CT-73, CT-83, CT-63, CT-94) — a mesma razão de blocoDoCatalogoNaArvore
| acima (.ai/rules/testes.md, RD-11): um helper usado por mais de um propósito no MESMO arquivo
| ainda pode morar em tests/Pest.php quando o achado que o motivou (RQ-34) o declara compartilhado.
|
| RD2-10/RD2-11: as guardas antigas comparavam string crua com UMA forma de seta (`\s*-->\s*`, ou
| a string literal `reverb --> painel_admin`) — uma aresta desenhada com `--->`, `-.->`, `==>` etc.
| (mesmo grafo, sintaxe Mermaid válida) passava sem ser vista. RD2-12: a mesma cegueira valia para
| `erDiagram` (regex fixa por cardinalidade e por CAIXA — `USER`/`ROLE` maiúsculo nunca casava com
| o `users`/`roles` reais, minúsculos).
*/

/**
 * As formas de seta de `flowchart`/`stateDiagram-v2` reconhecidas (RD2-10/RD2-11, RD3-05): normal
 * (2 ou mais traços, com ou sem ponta — inclusive `---` sem seta e `----->` com qualquer número de
 * traços), pontilhada (1 ou mais pontos, com ou sem o traço/ponta em cada lado — inclusive
 * `-...->`), grossa (2 ou mais iguais) e as pontas circulo/X/bidirecional (`<`, `o`, `x`) em
 * qualquer combinação nas duas pontas.
 *
 * Espelha, char a char, o lexer real do Mermaid 11.17.2 (`mermaid/dist/chunks/mermaid.core/
 * chunk-SHT3W25Y.mjs`, as regras de LINK/START_LINK): `[xo<]?--+[-xo>]`, `[xo<]?==+[=xo>]` e
 * `[xo<]?-?\.+-[xo>]?` — não uma aproximação com quantificador fixo. A forma antiga (`-{2,4}>`,
 * `-\.{1,2}->`, `={2,3}>`, `--o`, `--x`) perdia `----->`/`<-->`/`---` (normal com 5+ traços, sem
 * ponta ou com `<` na origem) e `-...->` (3+ pontos) — sintaxe válida que o `mermaid.parse` aceita
 * e que o extrator "normalizado" (RQ-34) precisa reconhecer para não confundir uma aresta redesenhada
 * com uma aresta ausente.
 */
const SETA_DE_FLUXO = '(?:[xo<]?-{2,}[-xo>]|[xo<]?={2,}[=xo>]|[xo<]?-?\.+-[xo>]?)';

/** O rótulo do MEIO da aresta, entre dois traços (`A -- "rótulo" --> B` ou `A -- rótulo --> B`),
 * como alternativa ao rótulo depois da seta (`-->|"rótulo"|`) — RD3-05: `existeArestaDeFluxo()` só
 * reconhecia a forma com pipe; a forma com travessão (a que o Mermaid gera para rótulo sem aspas)
 * passava sem ser vista. Compartilhado com `arestasDeFluxo()`, que já a usava. */
const ROTULO_TRACO_DE_FLUXO = '--\s+"?([^"\n-]+?)"?\s*';

/** O desenho de um nó (a forma logo depois do ID: `["..."]`, `{"..."}`, `(["..."])`, etc.), quando
 * houver — usado para PULAR o desenho ao procurar a próxima aresta na mesma linha. */
const FORMA_DE_NO_DE_FLUXO = '(?:\(\([^\)\n]*\)\)|\{\{[^}\n]*\}\}|\(\[[^\]\n]*\]\)|\[\([^\)\n]*\)\]|\[[^\]\n]*\]|\{[^}\n]*\}|\([^\)\n]*\))?';

/**
 * Existe uma aresta DIRETA $de -> $para num bloco `flowchart`/`stateDiagram-v2`, em QUALQUER forma
 * de seta (RD2-10/RD2-11), com ou sem rótulo (`-->|"rótulo"|` ou sem rótulo) — inclusive um elo de
 * uma cadeia `A --> B --> C` (a busca é por SUBSTRING "de(forma)? seta para", não por linha
 * inteira, e "B --> C" é substring de "A --> B --> C"). Ignora o que vier depois do ID de destino
 * (o desenho do PRÓPRIO nó, ex. `nega_403["Nega — 403"]`) — só o ID conta como identidade da
 * aresta, nunca o rótulo humano (a mesma regra de RD-03).
 *
 * RD3-05: o rótulo aceita as DUAS formas do Mermaid — depois da seta (`-->|"rótulo"|`) OU entre
 * dois traços ANTES da seta completa (`-- "rótulo" -->`, a forma de `A -- ws --> B`) —, não só a
 * primeira.
 */
function existeArestaDeFluxo(string $bloco, string $de, string $para): bool
{
    foreach (arestasDeFluxo($bloco) as $aresta) {
        if ($aresta['de'] === $de && $aresta['para'] === $para) {
            return true;
        }
    }

    return false;
}

/**
 * O rótulo do PRÓPRIO nó $id num bloco `flowchart`/`stateDiagram-v2` (o texto do desenho, entre
 * aspas ou não: `id{"texto"}`, `id["texto"]`, `id(["texto"])`, `id("texto")`) — `null` se o nó
 * nunca é desenhado com forma neste bloco (só citado como origem/destino de aresta).
 *
 * @return array<string, string>
 */
function rotulosDeNoDeFluxo(string $bloco): array
{
    $rotulos = [];

    preg_match_all(
        '/\b([A-Za-z0-9_]+)(?:\(\[\s*"?([^"\]\)]*?)"?\s*\]\)|\[\(\s*"?([^"\]\)]*?)"?\s*\)\]|\[\s*"?([^"\]]*?)"?\s*\]|\{\s*"?([^"}]*?)"?\s*\}|\(\s*"?([^")]*?)"?\s*\))/',
        $bloco,
        $m,
        PREG_SET_ORDER,
    );

    foreach ($m as $grupo) {
        $id = $grupo[1];

        if (array_key_exists($id, $rotulos)) {
            continue;
        }

        foreach (array_slice($grupo, 2) as $possivel) {
            if ($possivel !== '') {
                $rotulos[$id] = trim($possivel);

                break;
            }
        }
    }

    return $rotulos;
}

/**
 * Todas as arestas de um bloco `flowchart`, NA ORDEM em que aparecem, com o rótulo quando houver
 * (`-->|"rótulo"|` ou `-- rótulo -->`), e com o SENTIDO que o Mermaid desenha (R65, CT-140):
 *
 *  - uma cadeia `A --> B --> C` vira dois elos (A → B, B → C), nunca A → C;
 *  - o `&` expande o produto: `A & D --> B & C` são quatro arestas;
 *  - a seta com `<` na origem (`<-->`, `<==>`, `<-.->`) é bidirecional: as duas direções;
 *  - a seta dentro do rótulo de um nó (`a["A --> c"]`) ou de uma aresta (`-->|"x --> c"|`) é
 *    texto, e não aresta;
 *  - a linha que começa com `%%` é comentário — o Mermaid a tira do texto antes do lexer
 *    (`site/node_modules/mermaid/dist/mermaid.core.mjs:cleanupComments:967`).
 *
 * Ignora também as linhas de metadado (`accTitle`/`accDescr`/`classDef`/`class`/`style`/
 * `subgraph`/`end`). Quando os dois IDs são conhecidos, `existeArestaDeFluxo()` é só esta função
 * com um `foreach`.
 *
 * @return list<array{de: string, para: string, rotulo: ?string}>
 */
function arestasDeFluxo(string $bloco): array
{
    $ligacao  = '/^(?:'.SETA_DE_FLUXO.'\s*\|\s*"?([^"|]*)"?\s*\||'.ROTULO_TRACO_DE_FLUXO.SETA_DE_FLUXO.'|'.SETA_DE_FLUXO.')/';
    $lerGrupo = static function (string $resto): ?array {
        $ids = [];

        while (preg_match('/^\s*([A-Za-z0-9_]+)\s*'.FORMA_DE_NO_DE_FLUXO.'\s*/', $resto, $m) === 1) {
            $ids[] = $m[1];
            $resto = substr($resto, strlen($m[0]));

            if (preg_match('/^&\s*/', $resto, $amp) !== 1) {
                break;
            }

            $resto = substr($resto, strlen($amp[0]));
        }

        return $ids === [] ? null : [$ids, $resto];
    };

    $arestas = [];

    foreach (explode("\n", $bloco) as $linha) {
        $linha = trim($linha);

        if ($linha === ''
            || preg_match('/^(%%|(?:flowchart|graph|stateDiagram|erDiagram|sequenceDiagram|accTitle|accDescr|classDef|class|style|state|note|linkStyle|subgraph)\b|end$)/i', $linha) === 1
            || preg_match('/'.SETA_DE_FLUXO.'/', $linha) !== 1
        ) {
            continue;
        }

        $grupo = $lerGrupo($linha);

        while ($grupo !== null) {
            [$origens, $resto] = $grupo;

            if (preg_match($ligacao, $resto, $mLigacao) !== 1) {
                break;
            }

            $rotulo         = trim(($mLigacao[1] ?? '') !== '' ? $mLigacao[1] : ($mLigacao[2] ?? ''));
            $bidirecional   = str_starts_with($mLigacao[0], '<');
            $grupoDoDestino = $lerGrupo(substr($resto, strlen($mLigacao[0])));

            if ($grupoDoDestino === null) {
                break;
            }

            foreach ($origens as $origem) {
                foreach ($grupoDoDestino[0] as $destino) {
                    $arestas[] = ['de' => $origem, 'para' => $destino, 'rotulo' => $rotulo !== '' ? $rotulo : null];

                    if ($bidirecional) {
                        $arestas[] = ['de' => $destino, 'para' => $origem, 'rotulo' => $rotulo !== '' ? $rotulo : null];
                    }
                }
            }

            $grupo = $grupoDoDestino;
        }
    }

    return $arestas;
}

/**
 * A relação `erDiagram` entre duas entidades, em qualquer ordem de declaração — as duas
 * cardinalidades (`||` exatamente um, `|o` zero ou um, `}o`/`o{` zero ou muitos, `}|`/`|{` um ou
 * muitos), o rótulo e se a linha veio invertida ($b antes de $a). `null` se não houver relação
 * DIRETA entre as duas (RD2-12: a conta e o papel do kit se ligam por `model_has_roles`, então
 * `relacaoDeEr($bloco, 'users', 'roles')` é `null` no bloco correto).
 *
 * RD3-05: a linha de conexão aceita `--` (identificadora) OU `..` (não identificadora) entre as
 * duas cardinalidades — o Mermaid distingue as duas (a segunda desenha tracejado), e exigir só `--`
 * perdia toda relação opcional desenhada com o traço certo.
 *
 * @return ?array{cardDe: string, cardPara: string, rotulo: ?string, invertida: bool}
 */
function relacaoDeEr(string $bloco, string $a, string $b): ?array
{
    foreach (relacoesDeEr($bloco) as $r) {
        if ($r['a'] === $a && $r['b'] === $b) {
            return ['cardDe' => $r['cardDe'], 'cardPara' => $r['cardPara'], 'rotulo' => $r['rotulo'], 'invertida' => false];
        }

        if ($r['a'] === $b && $r['b'] === $a) {
            return ['cardDe' => $r['cardPara'], 'cardPara' => $r['cardDe'], 'rotulo' => $r['rotulo'], 'invertida' => true];
        }
    }

    return null;
}

/** A cardinalidade de um lado de uma relação `erDiagram` (RD3-05, R57): exatamente um, zero ou um, zero ou muitos, um ou muitos. */
const CARDINALIDADE_DE_ER = '(?:\|\||\|o|o\||o\{|\{o|\}o|o\}|\|\{|\{\||\}\||\|\})';

/**
 * TODAS as relações `erDiagram` de um bloco, na ordem em que aparecem, cada uma com as duas
 * entidades NA ORDEM DA LINHA (`$a` antes de `$b`) — usado quando os dois IDs não são conhecidos de
 * antemão (R2/CT-03, sobre os blocos publicados); quando já se sabe os dois lados,
 * `relacaoDeEr()` é mais simples.
 *
 * @return list<array{a: string, b: string, cardDe: string, cardPara: string, rotulo: ?string}>
 */
function relacoesDeEr(string $bloco): array
{
    $relacoes = [];

    foreach (explode("\n", $bloco) as $linha) {
        $linha = trim($linha);

        if (preg_match('/^([A-Za-z0-9_]+)\s*('.CARDINALIDADE_DE_ER.')(?:--|\.\.)('.CARDINALIDADE_DE_ER.')\s*([A-Za-z0-9_]+)\s*(?::\s*"?([^"\n]*)"?)?\s*$/', $linha, $m) === 1) {
            $relacoes[] = ['a' => $m[1], 'b' => $m[4], 'cardDe' => $m[2], 'cardPara' => $m[3], 'rotulo' => isset($m[5]) ? trim($m[5]) : null];
        }
    }

    return $relacoes;
}

/**
 * As formas de seta de `sequenceDiagram` reconhecidas (R57, RD3-05/QA-05): sólida sem ponta
 * (`->`), tracejada sem ponta (`-->`), sólida com ponta (`->>`), tracejada com ponta (`-->>`),
 * assíncrona sólida (`-)`) e tracejada (`--)`), com X sólida (`-x`) e tracejada (`--x`), e as duas
 * bidirecionais (`<<->>`, `<<-->>`), mais as meias-setas que o lexer aceita (R57, CT-142: `-|\`,
 * `-|/`, `-\\`, `-//` e as tracejadas `--|\`, `--|/`, `--\\`, `--//`) — espelha o lexer de sequência
 * do Mermaid 11.17.2
 * (`site/node_modules/mermaid/dist/chunks/mermaid.core/sequenceDiagram-WJ2MYXX4.mjs:rules`). A
 * ordem das alternativas importa: a forma de DOIS caracteres de ponta (`>>`) vem antes da de UM
 * (`>`), senão `-->>` seria lida como `-->` e sobraria um `>` solto; o mesmo vale para bidirecional
 * antes da forma simples.
 */
const SETA_DE_SEQUENCIA = '(?:<<-{1,2}>>|-{1,2}>>|-{1,2}x|-{1,2}\)|-{1,2}\|(?:\\\\|\/)|-{1,2}(?:\\\\{2}|\/{2})|-{1,2}>)';

/**
 * As mensagens de um bloco `sequenceDiagram`, NA ORDEM em que aparecem: qualquer forma de
 * `SETA_DE_SEQUENCIA` (R57), com o marcador de ativação opcional (`+`/`-`) entre a seta e o
 * destino, e o rótulo depois de `:`. Cada mensagem também traz a PILHA de blocos (`alt`, `opt`,
 * `loop`, `par`) que a envolvem no momento em que aparece — `else` é um marcador dentro do MESMO
 * `alt`, nunca abre nem fecha um — usada por R42/CT-85 para comparar o ANINHAMENTO, não só a ordem.
 *
 * @return list<array{de: string, seta: string, para: string, rotulo: ?string, pilha: list<string>}>
 */
function mensagensDeSequencia(string $bloco): array
{
    $mensagens = [];
    $pilha     = [];

    foreach (explode("\n", $bloco) as $linha) {
        $linha = trim($linha);

        if (preg_match('/^(alt|opt|loop|par)\b/', $linha, $mBloco) === 1) {
            $pilha[] = $mBloco[1];

            continue;
        }

        if ($linha === 'end') {
            array_pop($pilha);

            continue;
        }

        if (preg_match('/^([A-Za-z0-9_]+)\s*('.SETA_DE_SEQUENCIA.')\s*[+-]?\s*([A-Za-z0-9_]+)\s*:?\s*(.*)$/', $linha, $m) === 1) {
            $mensagens[] = ['de' => $m[1], 'seta' => $m[2], 'para' => $m[3], 'rotulo' => $m[4] !== '' ? trim($m[4]) : null, 'pilha' => $pilha];
        }
    }

    return $mensagens;
}

/**
 * A ordem do DG-20 é a que o código executa: `identify_tenant` fala com `can_access_tenant` ANTES
 * de falar com `definir_tenant`, e a mensagem a `definir_tenant` está DENTRO do ramo `else` do
 * `alt` (entre a linha `else` e o `end` que o fecha) — nunca antes do `alt`, nunca depois do `end`
 * (R51/R52, CT-112/CT-116; ver a nota de R53 no `04`).
 *
 * Único helper deste par (QA-07, `.ai/rules/testes.md` §"Nunca crie um clone com outro nome"):
 * `tests/Kit/GuardasDosDiagramasTest.php` (CT-116) e `tests/Tenancy/DiagramasDaArquiteturaTenancyTest.php`
 * (CT-112) tinham cada um a sua cópia — a de Tenancy com a checagem de aninhamento, a de Kit sem
 * ela; as duas condições são necessárias: a ordem sozinha mata a inversão (R51/M2, primeira cópia
 * de CT-112), e o aninhamento mata o contexto fixado fora do ramo que nega, mesmo com a ordem
 * intacta (R51/M2, segunda cópia).
 */
function ordemDg20EhCorreta(string $bloco): bool
{
    $mensagens   = mensagensDeSequencia($bloco);
    $idxConsulta = null;
    $idxDefinir  = null;

    foreach ($mensagens as $i => $m) {
        if ($idxConsulta === null && $m['de'] === 'identify_tenant' && $m['para'] === 'can_access_tenant') {
            $idxConsulta = $i;
        }

        if ($idxDefinir === null && $m['de'] === 'identify_tenant' && $m['para'] === 'definir_tenant') {
            $idxDefinir = $i;
        }
    }

    if ($idxConsulta === null || $idxDefinir === null || $idxConsulta >= $idxDefinir) {
        return false;
    }

    $linhaElse    = null;
    $linhaEnd     = null;
    $linhaDefinir = null;

    foreach (explode("\n", $bloco) as $i => $linha) {
        $t = trim($linha);

        if ($linhaElse === null && str_starts_with($t, 'else')) {
            $linhaElse = $i;
        }

        if ($linhaElse !== null && $linhaEnd === null && $t === 'end') {
            $linhaEnd = $i;
        }

        if ($linhaDefinir === null && preg_match('/^identify_tenant\s*-{1,2}>>\s*definir_tenant\s*:/', $t) === 1) {
            $linhaDefinir = $i;
        }
    }

    return $linhaElse !== null && $linhaEnd !== null && $linhaDefinir !== null
        && $linhaDefinir > $linhaElse && $linhaDefinir < $linhaEnd;
}

/**
 * Troca os DESTINOS de duas mensagens de sequência (`->>destinoA:` <-> `->>destinoB:`) — uma
 * involução: aplicar duas vezes devolve o bloco original. Usado para adulterar EM MEMÓRIA uma
 * mensagem sem mover linha nenhuma (só troca QUEM cada mensagem já existente alcança) — R53/CT-116
 * (DG-20) e R42/CT-85 (guardrails do DG-11).
 *
 * Único (QA-07): morava clonado dentro de `tests/Kit/GuardasDosDiagramasTest.php`.
 */
function trocarDestinosDeMensagem(string $bloco, string $destinoA, string $destinoB): string
{
    $marcador = "\0TROCA\0";
    $bloco    = str_replace("->>{$destinoA}:", $marcador, $bloco);
    $bloco    = str_replace("->>{$destinoB}:", "->>{$destinoA}:", $bloco);

    return str_replace($marcador, "->>{$destinoB}:", $bloco);
}

/**
 * As transições de um bloco `stateDiagram-v2`, NA ORDEM em que aparecem: `A --> B : evento`
 * (`[*]` conta como estado inicial/final).
 *
 * @return list<array{de: string, para: string, evento: ?string}>
 */
function transicoesDeEstado(string $bloco): array
{
    $transicoes = [];

    foreach (explode("\n", $bloco) as $linha) {
        $linha = trim($linha);

        if (preg_match('/^(\[\*\]|[A-Za-z0-9_]+)\s*-->\s*(\[\*\]|[A-Za-z0-9_]+)\s*(?::\s*(.*))?$/', $linha, $m) === 1) {
            $transicoes[] = ['de' => $m[1], 'para' => $m[2], 'evento' => isset($m[3]) && $m[3] !== '' ? trim($m[3]) : null];
        }
    }

    return $transicoes;
}

/** Um documento markdown sem as linhas de citação (`>`), para asserção de AUSÊNCIA. */
function readmeSemCitacao(string $arquivo): string
{
    return implode("\n", array_filter(
        explode("\n", (string) file_get_contents(base_path($arquivo))),
        static fn (string $linha): bool => ! str_starts_with(ltrim($linha), '>'),
    ));
}

/**
 * As seções de um arquivo Markdown, quebradas nos títulos.
 *
 * "Na mesma seção" é o que separa uma recusa EXPLICADA de uma menção decorativa numa linha
 * de roadmap — o achado da revisão adversarial de `LoginSocialProvedoresTest` (CT-42b).
 *
 * @return array<int, string>
 */
function secoesDoMarkdown(string $caminho): array
{
    return preg_split('~^#{1,6} ~m', (string) file_get_contents(base_path($caminho))) ?: [];
}

/*
|--------------------------------------------------------------------------
| `kit:info` — a saída do comando, e uma linha dela
|--------------------------------------------------------------------------
| Aqui, e não no arquivo de teste, porque DOIS usam: `tests/Kit/KitInfoTest.php` e
| `tests/Tenancy/KitInfoTenancyTest.php`. Helper cruzado declarado dentro de um arquivo de teste
| vaza para o vizinho e só estoura sob `--parallel`, `--tia` ou arquivo isolado — ver
| `.ai/rules/testes.md` e a guarda em `tests/Kit/HelpersDeTesteTest.php`.
*/

/** A saída de `kit:info`, em texto cru. */
function saidaDoKitInfo(): string
{
    Artisan::call('kit:info');

    return Artisan::output();
}

/**
 * A linha da saída de `kit:info` que começa por este rótulo.
 *
 * ## Por que quase todo caso de `kit:info` afirma sobre a LINHA
 *
 * Dois motivos independentes, os dois medidos numa execução vermelha.
 *
 * **1. O comando exibe cerca de cinquenta linhas, e o mesmo texto aparece legitimamente em mais de
 * uma.** `Starter Kit` está no nome do projeto e no remetente de e-mail (`mail_from_name` nasce de
 * `${APP_NAME}`); `#zz` está na linha da cor e na linha `Cor Primaria Hex`, que mostra o valor
 * vigente de propósito. `doesntExpectOutputToContain()` sobre a saída inteira reprova o comando
 * CORRETO.
 *
 * **2. `expectsOutputToContain()` casa no máximo UMA substring esperada por linha impressa.**
 * `PendingCommand::createABufferedOutputMock()` registra uma expectativa de Mockery por substring
 * (`vendor/laravel/framework/src/Illuminate/Testing/PendingCommand.php:615-622`), e o Mockery
 * satisfaz **uma** expectativa por chamada de `doWrite` — a primeira que casa. Duas substrings
 * esperadas na mesma linha deixam a segunda pendente, e `verifyExpectations()` (`:531-533`) falha
 * com `Output does not contain "..."` **mesmo com o texto na tela**. Foi assim que
 * `mail.mailers.smtp.password` + `valores não exibidos` (uma linha só) e `ligada` +
 * `Organizações` + `3 cadastrada` (idem) reprovaram sem defeito nenhum no comando.
 *
 * Empilhar `expectsOutputToContain()` continua valendo para substrings em linhas DIFERENTES.
 */
function linhaDoKitInfo(string $saida, string $rotulo): string
{
    foreach (explode("\n", $saida) as $linha) {
        if (str_starts_with(trim($linha), $rotulo)) {
            return trim($linha);
        }
    }

    return '';
}

/**
 * O interior do elemento que carrega a classe pedida — e só ele.
 *
 * Vive aqui, e não num arquivo de teste, porque três arquivos o usam
 * (`tests/Kit/PageHeaderTest.php`, `tests/Tenancy/PageHeaderTenancyTest.php` e os casos de
 * recorte). Helper cruzado declarado num deles some quando o Pest carrega um subconjunto
 * (`--parallel`, `--tia`, um arquivo só) — ver `.ai/rules/testes.md`.
 *
 * ## Por que balanceado, e não `strpos` até o primeiro fechamento
 *
 * Predicado de HTML aplicado sobre região grande demais não afirma nada: a rodada anterior desta
 * base produziu um oráculo sobre a cauda inteira da página em que QUALQUER texto o satisfazia —
 * 46 casos verdes sobre nada. Mas o erro simétrico também existe: cortar no primeiro `</div>`
 * aninhado devolve um pedaço, e a asserção de ausência sobre esse pedaço passa por engano.
 *
 * `fph-badges` e `fph-metadata` contêm markup do Filament, com divs aninhadas. Por isso a
 * caminhada conta abertura e fechamento da MESMA tag em vez de procurar o próximo fechamento.
 *
 * ## A classe casa como TOKEN, nunca como substring
 *
 * `fph-heading` não pode casar `fph-heading-icon`, que é filho dele
 * (`vendor/mortalkiller/filament-page-header/resources/views/components/heading.blade.php:5`).
 * Substring aqui devolveria o ícone no lugar do título.
 *
 * String vazia quando o elemento não existe — e aí **asserção de ausência sobre o retorno não vale
 * nada**, porque região vazia satisfaz toda ausência. O caso que usa ausência tem de provar antes
 * que a região não está vazia. É a regra que `[CT-02]` institui.
 *
 * Controles negativos do detector: `[CT-01]` e `[CT-02]` de `tests/Kit/PageHeaderTest.php`.
 */
function regiaoDoHeader(string $html, string $classe): string
{
    $cursor = 0;

    /*
     * Busca por POSIÇÃO da classe, e não por um `preg_match_all` sobre o documento inteiro: o
     * segundo é caro num HTML de painel e não compra nada aqui.
     */
    while (($posDaClasse = strpos($html, $classe, $cursor)) !== false) {
        $cursor = $posDaClasse + strlen($classe);

        // `strrpos` com deslocamento negativo caminha para trás a partir da ocorrência, sem copiar.
        $inicioDaTag = strrpos($html, '<', $posDaClasse - strlen($html));
        $fimDaTag    = strpos($html, '>', $posDaClasse);

        if ($inicioDaTag === false || $fimDaTag === false) {
            continue;
        }

        $tag = substr($html, $inicioDaTag, $fimDaTag - $inicioDaTag + 1);

        if (preg_match('~^<([a-zA-Z][a-zA-Z0-9]*)[\s>]~', $tag, $nome) !== 1) {
            continue;
        }

        if (preg_match('~[\s]class\s*=\s*"([^"]*)"~', $tag, $atributo) !== 1) {
            continue;
        }

        // Token inteiro: `fph-heading` não pode casar `fph-heading-icon`, que é filho dele.
        if (! in_array($classe, preg_split('~\s+~', $atributo[1], flags: PREG_SPLIT_NO_EMPTY) ?: [], true)) {
            continue;
        }

        $nomeDaTag    = strtolower($nome[1]);
        $inicio       = $fimDaTag + 1;
        $profundidade = 1;
        $andando      = $inicio;

        while ($profundidade > 0) {
            $abre  = stripos($html, '<'.$nomeDaTag, $andando);
            $fecha = stripos($html, '</'.$nomeDaTag, $andando);

            if ($fecha === false) {
                return '';
            }

            if ($abre !== false && $abre < $fecha) {
                // `<div` só abre quando o caractere seguinte encerra o nome; senão é `<divisor`.
                $seguinte = $html[$abre + strlen($nomeDaTag) + 1] ?? '';

                if ($seguinte === '>' || $seguinte === '/' || trim($seguinte) === '') {
                    $profundidade++;
                }

                $andando = $abre + strlen($nomeDaTag) + 1;

                continue;
            }

            $profundidade--;

            if ($profundidade === 0) {
                return substr($html, $inicio, $fecha - $inicio);
            }

            $andando = $fecha + strlen($nomeDaTag) + 2;
        }
    }

    return '';
}

/**
 * O `<header>` do pacote de page header. Atalho de `regiaoDoHeader($html, 'fph-header')`.
 *
 * Existe como nome próprio porque é a região usada em quase todo caso, e porque o nome diz o que
 * se está medindo. A implementação é a mesma do extrator geral de propósito: um extrator, um
 * conjunto de controles negativos.
 */
function regiaoDoCabecalho(string $html): string
{
    return regiaoDoHeader($html, 'fph-header');
}

/**
 * O código sem comentário de bloco nem de linha.
 *
 * Só para asserção de AUSÊNCIA: os arquivos do kit **citam** o que proíbem, e é lá que está o
 * porquê — sem o filtro, a varredura reprova pela própria documentação. A asserção de PRESENÇA
 * roda sobre o texto cru. `.ai/rules/testes.md` registra o padrão, que já custou três vezes nesta
 * base, uma delas derrubando três telas com `ParseError`.
 */
function semComentarios(string $codigo): string
{
    $codigo = (string) preg_replace('~/\*.*?\*/~s', '', $codigo);

    return (string) preg_replace('~^\s*//.*$~m', '', $codigo);
}

/**
 * A lista fechada de caminhos que o `kit:update` entrega a quem JÁ instalou o kit.
 *
 * O comando compara duas versões restrito a essa lista. Arquivo do kit fora dela **não chega**:
 * a feature existe no repositório e é invisível na prática. Foi o que aconteceu com a
 * multi-tenancy — três versões inteiras (0.9.1 a 0.9.3) em que o `kit:update` só oferecia
 * `config/kit.php`.
 *
 * Aqui, e não dentro de um arquivo de teste, porque DOIS arquivos usam: `KitUpdateTest` faz a
 * varredura genérica, e `SiteDeDocumentacaoTest` amarra a promessa do README sobre o roadmap ao
 * mecanismo que a cumpre. Em PHP função é global no processo, e helper que vaza de um arquivo
 * para o vizinho só estoura em `--parallel`, `--tia` ou ao rodar um arquivo sozinho
 * (`.ai/rules/testes.md`).
 *
 * @return list<string>
 */
function caminhosDoKit(): array
{
    $reflexao = new ReflectionClass(KitUpdate::class);

    /** @var list<string> $caminhos */
    $caminhos = $reflexao->getConstant('CAMINHOS_DO_KIT');

    return $caminhos;
}

/**
 * O bloco de UM serviço do `docker-compose.yml`: do `  <nome>:` até a próxima chave de coluna 2
 * (outro serviço) ou de coluna 0 (o `volumes:` de topo). Devolve `''` quando o serviço não existe.
 *
 * Movido de `tests/Kit/MysqlNoDockerTest.php` (era closure local) porque a wiki
 * `diagramas-da-arquitetura` ganhou um segundo consumidor — `tests/Kit/DiagramasDaArquiteturaTest.php`,
 * que confere os profiles do DG-18 contra o mesmo `docker-compose.yml`. `.ai/rules/testes.md`: helper
 * usado por mais de um arquivo vive aqui, nunca clonado.
 */
function blocoDoServico(string $compose, string $servico): string
{
    $linhas = explode("\n", $compose);
    $dentro = false;
    $bloco  = [];

    foreach ($linhas as $linha) {
        if ($linha === '  '.$servico.':') {
            $dentro = true;

            continue;
        }

        if ($dentro) {
            $fimDeServico = preg_match('/^  \S/', $linha) === 1;
            $fimDeTopo    = preg_match('/^\S/', $linha) === 1;

            if ($fimDeServico || $fimDeTopo) {
                break;
            }

            $bloco[] = $linha;
        }
    }

    return implode("\n", $bloco);
}

/**
 * O código PHP sem comentários e docblocks — só o que executa.
 *
 * `token_get_all()` e não regex, porque a menção a um literal dentro de um docblock é o caso
 * mais comum nesta suíte: um caso que **fala sobre** `docs/pt/…` num comentário não o lê.
 *
 * Mora aqui, e não dentro de um arquivo de teste, porque **dois** arquivos o usam
 * (`RedeDeDocumentacaoTest` e `ChecklistDeReleaseTest`) — `.ai/rules/testes.md`. A primeira
 * versão do segundo trouxe um clone chamado `codigoPhpSemComentario()`, byte a byte igual, que
 * é exatamente o que a rule proíbe: em vez de estourar redeclaração, ficam duas funções
 * idênticas que divergem em silêncio.
 */
function codigoSemComentario(string $codigo): string
{
    $saida = '';

    foreach (token_get_all($codigo) as $token) {
        if (is_array($token)) {
            if (in_array($token[0], [T_COMMENT, T_DOC_COMMENT], true)) {
                continue;
            }

            $saida .= $token[1];
        } else {
            $saida .= $token;
        }
    }

    return $saida;
}

/*
|--------------------------------------------------------------------------
| Rodapé coerente — helpers compartilhados por VersaoNoRodapeTest.php e
| RodapeCoerenteTest.php (wiki feat/rodape-coerente)
|--------------------------------------------------------------------------
| Os quatro primeiros já existiam em VersaoNoRodapeTest.php e ganharam um
| segundo consumidor com esta feature — `.ai/rules/testes.md` é explícita:
| helper usado por dois arquivos vem para cá, com um nome só, porque
| declarado só num arquivo de teste ele some quando o Pest carrega um
| subconjunto (`--parallel`, `--tia`, um arquivo isolado).
*/

/**
 * As duas chaves são lidas por request (`config()` dentro da blade), então `config()->set()` no
 * caso é o arranjo fiel — não há nada congelado no boot do painel a contornar.
 */
function comVersoes(?string $sistema, bool $exibirKit): void
{
    config()->set('app.version', $sistema);
    config()->set('kit.exibir_versao', $exibirKit);
}

/*
|--------------------------------------------------------------------------
| Os recortes do rodape, em UM lugar
|--------------------------------------------------------------------------
|
| Tres extratores liam o mesmo elemento com tres copias do mesmo regex, e as
| copias divergiram: `recadoDoRodape()` foi endurecido para aceitar o atributo
| quebrado em varias linhas, e os dois da assinatura ficaram para tras. Achado
| QA-31 do quality gate, ciclo 3 — e a forma do defeito que esta feature
| inteira persegue: a mesma regra escrita em dois lugares diverge.
|
| `[^>]*` dos DOIS lados do `class`: a blade quebra a linha entre a tag e os
| atributos, e o elemento pode ganhar uma classe utilitaria ou um `data-testid`
| `["']` como classe de aspa porque aspas simples sao HTML valido.
|
| A TAG E CAPTURADA, NAO LISTADA. A versao anterior listava `(?:div|footer)`
| na assinatura e `div` FIXO no recado. Quando o recado virou `<aside>`
| (ADR-09 de `rodape-coerente`), o recorte do recado parou de casar e 8 casos
| Os que afirmavam AUSENCIA teriam ficado VERDES sobre o vazio se nao fosse
| o controle positivo exigido acima. `(?P<tag>[a-z]+)` com `</(?P=tag)>`
| fecha pela MESMA tag: a semantica do elemento muda sem tocar no extrator,
| e um `</div>` que feche um `<aside>` continua sendo recusado.
|
| ATENCAO AO USAR QUALQUER UM DELES EM ASSERCAO DE AUSENCIA.
|
| Os tres extratores (`rodapeDe`, `assinaturaDoRodape`, `recadoDoRodape`)
| devolvem `''` em silencio quando nao acham a ancora — e string vazia
| satisfaz qualquer `assertStringNotContainsString`. Falham ABERTO, que e o
| defeito n.1 desta base (`.ai/rules/testes.md`). Achado RD-06 do step 6.5.
|
| Todo caso que os usa para provar ausencia precisa de um controle positivo
| NA MESMA ROTA, provando que o extrator devolveu conteudo ali — nao basta um
| controle positivo noutra rota.
|
| Este aviso estava num docblock solto acima deste bloco, a 70 linhas
| da funcao que dizia descrever: preso a nada, e duplicando o que ja se lia
| aqui. Achado QA-46 do ciclo 4. Fundido, e agora vale para os tres — que e o
| escopo certo, porque os tres falham do mesmo jeito.
*/
/**
 * Os dois marcadores de classe do rodape, como ALTERNATIVA de regex.
 *
 * Existe como constante por um motivo especifico: `[CT-24]` varre `tests/` procurando linhas que
 * reescrevam os extratores, e reconhece uma copia por conter um destes marcadores ao lado de um
 * `preg_*`. Se o proprio `[CT-24]` escrevesse os marcadores como literal, ele se acusaria — e a
 * saida seria cega-lo no proprio arquivo, que e exatamente onde o QA-45 morava. A constante
 * mantem o varredor sensivel ao arquivo que o hospeda.
 */
const MARCADORES_DO_RODAPE = '~kit-versao|fi-login-rodape~';

const RECORTE_DA_ASSINATURA = '~<(?P<tag>[a-z]+)[^>]*class=[\x22\x27][^\x22\x27]*kit-versao[^\x22\x27]*[\x22\x27][^>]*>(?P<corpo>.*?)</(?P=tag)>~s';

const RECORTE_DO_RECADO = '~<(?P<tag>[a-z]+)[^>]*class=[\x22\x27][^\x22\x27]*fi-login-rodape[^\x22\x27]*[\x22\x27][^>]*>(?P<corpo>.*?)</(?P=tag)>~s';

/**
 * O RODAPÉ da página, e não a página inteira.
 *
 * É oráculo, não conveniência. `assertSee` sobre o documento todo fica verde com a versão emitida
 * na barra do topo, no menu lateral ou no corpo da tela — e a regra é sobre o rodapé.
 *
 * ## Dois layouts, dois anchors — medido, não suposto
 *
 * Nos painéis autenticados (`vendor/filament/filament/resources/views/components/layout/index.blade.php:126`)
 * o hook `FOOTER` é emitido depois do fechamento do `</main>`, e o trecho posterior ao último
 * `</main>` contém o rodapé sem topbar nem conteúdo.
 *
 * **Nas telas de autenticação deste kit, isso não vale.** O `01`/`02` desta feature registram o
 * `FOOTER` como emitido também por `filament/resources/views/components/layout/simple.blade.php:61`
 * — mas esta instalação usa `caresome/filament-auth-designer` para as telas de login, registro e
 * recuperação de senha, cujo layout PRÓPRIO
 * (`vendor/caresome/filament-auth-designer/resources/views/components/layouts/auth.blade.php`)
 * **não tem `<main>` nenhum**: medido, `str_contains($html, '</main>')` é `false` em `/admin/login`.
 * O `FOOTER` ali é emitido logo após o fechamento BALANCEADO da `<div class="fi-auth-layout">`
 * (linhas 28→61 daquele arquivo). Sem este segundo caminho, `rodapeDe()` devolveria `''` para toda
 * tela de autenticação, e "o rodapé contém/não contém X" mediria uma string vazia — verde sobre
 * nada nas asserções de ausência, vermelho sem destinatário nas de presença.
 */
function rodapeDe(string $html): string
{
    $fim = strrpos($html, '</main>');

    return $fim !== false ? substr($html, $fim) : rodapeDoLayoutDeAutenticacao($html);
}

/** O trecho posterior ao fechamento BALANCEADO da `<div class="fi-auth-layout">` — ver `rodapeDe()`. */
function rodapeDoLayoutDeAutenticacao(string $html): string
{
    $posDaClasse = strpos($html, 'fi-auth-layout');

    if ($posDaClasse === false) {
        return '';
    }

    $inicioDaTag      = strrpos($html, '<div', $posDaClasse - strlen($html));
    $fimDaTagAbertura = strpos($html, '>', $posDaClasse);

    if ($inicioDaTag === false || $fimDaTagAbertura === false) {
        return '';
    }

    $profundidade = 1;
    $andando      = $fimDaTagAbertura + 1;

    while ($profundidade > 0) {
        $abre  = stripos($html, '<div', $andando);
        $fecha = stripos($html, '</div', $andando);

        if ($fecha === false) {
            return '';
        }

        if ($abre !== false && $abre < $fecha) {
            $profundidade++;
            $andando = $abre + 4;

            continue;
        }

        $profundidade--;
        $andando = $fecha + 5;
    }

    $fimDoFechamento = strpos($html, '>', $andando - 5);

    return $fimDoFechamento === false ? '' : substr($html, $fimDoFechamento + 1);
}

/**
 * O SEGMENTO do rodapé em que uma versão aparece.
 *
 * O corte usa o separador ` · `, que é a composição do próprio kit
 * (`assinatura-do-rodape.blade.php`, `implode(' · ', $partes)`). O que ele NÃO acopla é o texto do
 * rótulo nem a posição dele.
 */
function segmentoDaVersao(string $html, string $versao): string
{
    /*
     * A DIV, não o `rodapeDe()`. `rodapeDe()` devolve tudo depois de `</main>` — a cauda inteira
     * da página, com menu do usuário, scripts e texto de sobra. O recorte tem de ser o elemento
     * que a feature emite, e só ele.
     */
    if (preg_match(RECORTE_DA_ASSINATURA, $html, $m) !== 1) {
        return '';
    }

    foreach (explode('·', strip_tags($m['corpo'])) as $segmento) {
        if (str_contains($segmento, $versao)) {
            return trim($segmento);
        }
    }

    return '';
}

/** O segmento tem rótulo? Palavra, não letra solta — `v` de `v2.4.1` é marcador de versão. */
function temRotulo(string $segmento): bool
{
    return preg_match('/\p{L}{2,}/u', $segmento) === 1;
}

/**
 * Congela o relógio num instante neutro — longe das duas bordas do ano.
 *
 * Todo cenário que afirma a assinatura (`© {ano} {nome}`) precisa disto: o ano compõe a linha no
 * RENDER (`now()->year`), e um literal `© 2026 Acme` escrito sem congelar o relógio fica verde
 * hoje e vermelho em 1º de janeiro. Sem `travelBack()` explícito: o Laravel o faz no teardown, e um
 * `travel()` solto vazaria para o vizinho e viraria flake em `--parallel`. `[CT-22]` é o cenário que
 * MEDE o relógio, e por isso não usa este helper — ele controla fuso e instante caso a caso.
 */
function emJunhoDe2026(): void
{
    test()->travelTo('2026-06-15 12:00:00');
}

/**
 * As quatro chaves da composição do rodapé, de uma vez.
 *
 * `.ai/rules/testes.md` e o Setup Global da wiki `feat/rodape-coerente` exigem que todo `Dado`
 * fixe as QUATRO chaves — `app.name`, `app.version`, `kit.exibir_versao` e `kit.login.rodape` —, e
 * não só a que o cenário discute: um `Dado` que cala sobre o resto mede o que o `phpunit.xml`
 * forçou (nome vindo do `.env` da máquina, versão e recado forçados vazios), não o comportamento.
 */
function comIdentidade(?string $nome, ?string $versao, bool $exibirKit, ?string $recado = null): void
{
    config()->set('app.name', $nome);
    config()->set('app.version', $versao);
    config()->set('kit.exibir_versao', $exibirKit);
    config()->set('kit.login.rodape', $recado);
}

/**
 * O texto do RECADO do rodape, recortado do elemento — e nao da cauda da pagina.
 *
 * Espelha `assinaturaDoRodape()`, e existe pelo mesmo motivo: `rodapeDe()` devolve tudo depois do
 * ancora, inclusive o `wire:snapshot` do Livewire, onde o recado pode aparecer SERIALIZADO. Uma
 * assercao de presenca sobre aquela cauda fica verde com a faixa inexistente.
 *
 * O Setup Global do `04` declara isso como regra — "rodapeDe() so serve de entrada para
 * segmentoDaVersao() e para as assercoes de AUSENCIA" — e tres casos a violavam. Achado QA-07 do
 * quality gate, que eu tinha declarado como lacuna quando custava este helper.
 */
function recadoDoRodape(string $html): string
{
    /*
     * `preg_match_all` e a PRIMEIRA ocorrencia NAO-VAZIA, e as duas decisoes custaram medicao.
     *
     * 1. O `class` nao vem colado no `<div`: a blade quebra a linha entre a tag e os atributos,
     *    entao o `[^>]*` no meio e obrigatorio.
     *
     * 2. `preg_match_all` + primeira ocorrencia NAO-VAZIA, por defesa e nao por diagnostico.
     *
     *    A primeira redacao desta nota afirmava que "a classe aparece mais de uma vez no
     *    documento". O ciclo 3 do quality gate MEDIU e desmentiu: `substr_count()` devolve 1 em
     *    `/admin/login` e 1 em `/login`. O que de fato consertou o helper foi a decisao 1 — o
     *    `[^>]*`, porque a blade quebra a linha entre a tag e os atributos.
     *
     *    O laco fica, porque custa tres linhas e cobre o dia em que houver duas. Mas a premissa
     *    escrita nao era medida, e registrar isso importa mais que a linha de codigo: foi o
     *    mesmo erro que produziu o Blocker do 6.5 (medir o layout errado) e as verificacoes
     *    V3/V5 falsas. Achado QA-31.
     */
    if (preg_match_all(RECORTE_DO_RECADO, $html, $m) < 1) {
        return '';
    }

    foreach ($m['corpo'] as $conteudo) {
        if (filled($texto = trim(strip_tags($conteudo)))) {
            return $texto;
        }
    }

    return '';
}

/**
 * O conteúdo TEXTUAL e normalizado do elemento que compõe a assinatura do rodapé.
 *
 * `Então o rodapé contém X` significa isto, nunca `rodapeDe()`: `rodapeDe()` devolve a cauda
 * inteira da página — menu do usuário, scripts e o SNAPSHOT do Livewire, onde `app.name` e o
 * recado do login aparecem serializados. Presença medida ali é satisfeita por payload de JS, com a
 * faixa do rodapé inexistente.
 *
 * `strip_tags` → `html_entity_decode` → colapsar espaços → `trim`, nesta ordem. `html_entity_decode`
 * não é detalhe: `{{ }}` do Blade não converte `©`, mas uma implementação que escreva `&copy;`
 * literal produz o MESMO pixel e uma string diferente — sem a normalização um oráculo escrito com
 * `©` ficaria vermelho contra uma implementação correta.
 *
 * String vazia quando o elemento não existe. Não usar isto para afirmar AUSÊNCIA: elemento
 * ausente e elemento presente-e-vazio devolvem a mesma string vazia — a ausência é sobre o HTML
 * (marcador `kit-versao`), não sobre este retorno normalizado.
 */
function assinaturaDoRodape(string $html): string
{
    if (preg_match(RECORTE_DA_ASSINATURA, $html, $m) !== 1) {
        return '';
    }

    $texto = html_entity_decode(strip_tags($m['corpo']));
    $texto = preg_replace('~\s+~', ' ', $texto) ?? $texto;

    return trim($texto);
}

/*
|--------------------------------------------------------------------------
| DG-03 — leitura do grafo de decisão e caminhada pelo predicado REAL
|--------------------------------------------------------------------------
|
| Aqui, e não em tests/Tenancy/DiagramasDaArquiteturaTenancyTest.php, porque dois arquivos os usam
| (CT-14 e CT-98 no Tenancy; CT-71 no Kit, que caminha o DG-03 para a conta que acumula papéis) —
| `.ai/rules/testes.md` §"Helper de teste usado por mais de um arquivo vive em tests/Pest.php".
*/

/**
 * Nós (id => rótulo) e arestas de um bloco Mermaid `flowchart`/`graph`.
 *
 * Convenção assumida: um "shape" de nó (`id[Texto]`, `id(Texto)`, `id{Texto}`,
 * `id{{Texto}}`, `id((Texto))`, `id([Texto])`, `id[[Texto]]`) pode aparecer em QUALQUER linha —
 * o rótulo de um id é lido da primeira vez que ele aparece com shape, em lugar nenhum
 * específico. O rótulo de uma ARESTA vem do `-->|Rótulo|` entre as duas pontas.
 *
 * @return array{nos: array<string, string>, arestas: list<array{de: string, para: string, rotulo: ?string, linha: int}>}
 */
function grafoDoFluxo(string $bloco): array
{
    $nos = [];

    preg_match_all(
        '/\b([A-Za-z0-9_]+)\s*(?|\{\{\s*(.*?)\s*\}\}|\(\(\s*(.*?)\s*\)\)|\(\[\s*(.*?)\s*\]\)|\[\[\s*(.*?)\s*\]\]|\[\s*(.*?)\s*\]|\(\s*(.*?)\s*\)|\{\s*(.*?)\s*\})/',
        $bloco,
        $achados,
        PREG_SET_ORDER,
    );

    foreach ($achados as $achado) {
        if (! isset($nos[$achado[1]])) {
            $nos[$achado[1]] = trim($achado[2], " \t\"'");
        }
    }

    $arestas = [];

    foreach (explode("\n", $bloco) as $i => $linha) {
        if (preg_match(
            '/^\s*([A-Za-z0-9_]+)\s*(?:\{\{.*?\}\}|\(\(.*?\)\)|\(\[.*?\]\)|\[\[.*?\]\]|\[.*?\]|\(.*?\)|\{.*?\})?\s*(?:--[-.]*>|==>)\s*(?:\|([^|]*)\|)?\s*([A-Za-z0-9_]+)/',
            $linha,
            $m,
        ) === 1) {
            $arestas[] = [
                'de'     => $m[1],
                'para'   => $m[3],
                'rotulo' => ($m[2] ?? '') !== '' ? trim($m[2], " \t\"'") : null,
                'linha'  => $i + 1,
            ];
        }
    }

    foreach ($arestas as $aresta) {
        $nos[$aresta['de']] ??= $aresta['de'];
        $nos[$aresta['para']] ??= $aresta['para'];
    }

    return ['nos' => $nos, 'arestas' => $arestas];
}

/**
 * Executa um grafo de DECISÃO (DG-03) a partir do nó sem aresta de entrada, avaliando cada nó
 * pelo PREDICADO REAL (nunca por comparação textual de ordem — mata R7.M5), e devolve o rótulo
 * terminal normalizado ("entra"/"nega").
 *
 * @param  array{nos: array<string, string>, arestas: list<array{de: string, para: string, rotulo: ?string, linha: int}>}  $grafo
 * @param  callable(string): ?bool  $avaliar  null quando o texto do nó não é uma pergunta reconhecida
 *
 * @throws RuntimeException quando o grafo não tem a forma esperada (sem início, laço, pergunta
 *                          não reconhecida ou aresta sem rótulo Sim/Não do lado decidido)
 */
function resultadoDoFluxo(array $grafo, callable $avaliar): string
{
    $comEntrada = array_unique(array_column($grafo['arestas'], 'para'));
    $atual      = null;

    foreach (array_keys($grafo['nos']) as $id) {
        if (! in_array($id, $comEntrada, true)) {
            $atual = $id;

            break;
        }
    }

    if ($atual === null) {
        throw new RuntimeException('DG-03: nenhum nó sem aresta de entrada — não há por onde começar a caminhada.');
    }

    $visitados = [];

    while (true) {
        if (isset($visitados[$atual])) {
            throw new RuntimeException("DG-03: laço encontrado no nó '{$atual}'.");
        }

        $visitados[$atual]  = true;
        $texto              = $grafo['nos'][$atual] ?? $atual;

        if (preg_match('/\bentra\b/i', $texto) === 1) {
            return 'entra';
        }

        if (preg_match('/\bnega\b/i', $texto) === 1) {
            return 'nega';
        }

        $saidas = array_values(array_filter($grafo['arestas'], static fn (array $a): bool => $a['de'] === $atual));

        if ($saidas === []) {
            throw new RuntimeException("DG-03: nó '{$texto}' ({$atual}) não é terminal (\"entra\"/\"nega\") e não tem aresta de saída.");
        }

        if (count($saidas) === 1) {
            $atual = $saidas[0]['para'];

            continue;
        }

        $resultado = $avaliar($texto);

        if ($resultado === null) {
            throw new RuntimeException("DG-03: pergunta não reconhecida pela guarda: \"{$texto}\".");
        }

        $alvo = null;

        foreach ($saidas as $saida) {
            $rotulo = mb_strtolower((string) $saida['rotulo']);
            $ehSim  = str_contains($rotulo, 'sim') || str_contains($rotulo, 'yes') || $rotulo === 'true';
            $ehNao  = str_contains($rotulo, 'não') || str_contains($rotulo, 'nao') || str_contains($rotulo, 'no') || $rotulo === 'false';

            if (($resultado && $ehSim) || (! $resultado && $ehNao)) {
                $alvo = $saida['para'];

                break;
            }
        }

        if ($alvo === null) {
            $ladoEsperado = $resultado ? 'sim' : 'não';

            throw new RuntimeException("DG-03: nó '{$texto}' não tem aresta rotulada para o lado \"{$ladoEsperado}\".");
        }

        $atual = $alvo;
    }
}

/**
 * O avaliador das perguntas de DG-03 — a MESMA ordem de `User::canAccessPanel()`
 * (`app/Models/User.php:canAccessPanel:157`): indisponibilidade, pendência, master_global,
 * contexto do painel (tenancy), papel do painel.
 */
function avaliadorDoDG03(User $user, Panel $painel): Closure
{
    return function (string $texto) use ($user, $painel): ?bool {
        $t = mb_strtolower($texto);

        return match (true) {
            str_contains($t, 'indispon')                                           => $user->motivoDeIndisponibilidade() !== null,
            str_contains($t, 'pendente')                                           => (bool) $user->aprovacao_pendente,
            str_contains($t, 'master_global') || str_contains($t, 'master global') => $user->isMasterGlobal(),
            str_contains($t, 'tenancy')                                            => $painel->hasTenancy(),
            str_contains($t, 'papel') && str_contains($t, 'painel')                => $user->temPapelDoPainel(
                $painel->getId(),
                $painel->hasTenancy() ? null : (config('permission.teams') ? Tenant::CONTEXTO_GLOBAL : null),
            ),
            default => null,
        };
    };
}

/**
 * Texto da saída do `kit:install` comparado sem acento e sem caixa — dos dois lados, na presença e na
 * ausência. O banner é ASCII e a linha do resumo tem acento: com acento de um lado só, a ausência passa
 * no vazio e a presença falha sem defeito (wiki `diagramas-da-arquitetura`, Setup Global do `04`, ADV-26).
 * Usado por `tests/Kit/ResumoDoKitInstallTest.php` e `tests/Kit/CustomizadorDaInstalacaoTest.php`.
 */
function semAcentoESemCaixa(string $texto): string
{
    return mb_strtolower(Str::ascii($texto));
}

/**
 * Liga o login com Google (credenciais fake) para o caso corrente.
 *
 * Em `tests/Pest.php` por ter dois consumidores (`LoginSocialGoogleTest`, `RodapeCoerenteTest`); nome longo por colisão global de função.
 *
 * @param  array<string, mixed>  $credenciais  sobrescreve chaves de `services.google`
 */
function ligarLoginComGoogleDoKit(array $credenciais = []): void
{
    config()->set('kit.login.google.habilitado', true);

    config()->set('services.google', array_merge([
        'client_id'     => 'id-de-teste',
        'client_secret' => 'segredo-de-teste',
        'redirect'      => '/auth/google/callback',
    ], $credenciais));
}
