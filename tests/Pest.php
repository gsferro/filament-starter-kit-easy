<?php

use App\Models\Convite;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use App\Providers\KitServiceProvider;
use App\Settings\ConfiguracoesDoKit;
use App\Support\ProvedorSocial;
use Filament\Facades\Filament;
use Filament\FilamentManager;
use Filament\Support\Assets\AssetManager;
use Filament\Support\Colors\ColorManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Facades\Log;
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
            '/admin/configuracoes-do-kit',
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
 * @return array<string, string>
 */
function paginasDoSite(string $idioma): array
{
    $raiz    = base_path("docs/{$idioma}");
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
