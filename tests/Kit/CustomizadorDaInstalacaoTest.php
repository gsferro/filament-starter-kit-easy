<?php

use App\Console\Commands\KitInstall;
use App\Support\CustomizadorDaInstalacao;
use App\Support\SubstituicaoEmArquivo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

/**
 * O customizador de instalação — as perguntas do `kit:install` e a escrita delas.
 *
 * Todos os casos escrevem num diretório TEMPORÁRIO, nunca no `base_path()`. Não é
 * preciosismo: o customizador reescreve o `.env`, e apontá-lo para o projeto faria
 * a suíte destruir o ambiente de quem roda os testes.
 *
 * O que NÃO é testado aqui, e por quê: as perguntas dentro de um
 * `composer create-project` de verdade — nenhum teste automatizado alcança a
 * camada de TTY do Composer (ela é verificada à mão, ver a wiki da feature) — e a
 * abertura do navegador no convite da estrela, que é `exec()` do sistema
 * operacional. Nos dois casos o oráculo aqui é a DECISÃO, não o efeito externo.
 */
beforeEach(function (): void {
    $this->base = sys_get_temp_dir().'/kit-custom-'.bin2hex(random_bytes(4));

    File::ensureDirectoryExists($this->base.'/config');
    File::copy(base_path('.env.example'), $this->base.'/.env');

    // Cópias dos configs reais: a ativação da tenancy os reescreve, e apontá-la
    // para `config_path()` faria a suíte ligar `permission.teams` no projeto.
    File::copy(config_path('permission.php'), $this->base.'/config/permission.php');
    File::copy(config_path('filament-shield.php'), $this->base.'/config/filament-shield.php');
});

/**
 * A conexão default volta ao que era ANTES do teardown.
 *
 * O `RefreshDatabase` abre a transação na conexão default e a desfaz no
 * teardown. Um caso que troca `database.default` para pgsql deixaria o rollback
 * procurando a transação na conexão errada — e o sintoma é "cannot start a
 * transaction within a transaction" em TODOS os casos seguintes, mais uma
 * tentativa de conectar num Postgres de verdade. Nada disso é defeito do
 * produto: na instalação de verdade não há transação aberta.
 */
afterEach(function (): void {
    config(['database.default' => 'sqlite']);
    DB::purge('pgsql');
    DB::purge('mysql');

    File::deleteDirectory($this->base);
});

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

function respostasDeCustomizacao(array $sobrescritas = []): array
{
    return array_replace([
        'nome'    => 'Loja do Ferro',
        'banco'   => 'sqlite',
        'email'   => 'admin@example.com',
        'senha'   => '',
        'cor'     => '',
        'tenancy' => false,
    ], $sobrescritas);
}

function customizadorNoTemp(): CustomizadorDaInstalacao
{
    return new CustomizadorDaInstalacao(test()->base);
}

/*
|--------------------------------------------------------------------------
| R1 — quando perguntar
|--------------------------------------------------------------------------
| A v0.16.0 saiu quebrada aqui: o gate era "o `.env` já existia?", e num
| `create-project` a resposta é SEMPRE sim — o `post-root-package-install` do
| composer.json copia `.env.example` para `.env` antes de o `kit:install` rodar.
| A instalação seguia em silêncio, sem erro, e a suíte passava porque exercitava
| a classe num diretório temporário, onde aquele script não existe.
*/

it('decide se pergunta a partir de projeto novo, flags e terminal', function (
    bool $projetoNovo,
    bool $forcado,
    bool $pulaPorFlag,
    bool $interativo,
    bool $esperado,
): void {
    expect(CustomizadorDaInstalacao::devePerguntar($projetoNovo, $forcado, $pulaPorFlag, $interativo))
        ->toBe($esperado);
})->with([
    'projeto nascendo, com terminal'          => [true,  false, false, true,  true],
    'o .env do create-project não conta'      => [true,  false, false, true,  true],
    'sem terminal (CI, Docker, -n)'           => [true,  false, false, false, false],
    'pulo explícito por --no-custom'          => [true,  false, true,  true,  false],
    'projeto já instalado não é perguntado'   => [false, false, false, true,  false],
    'reinstalação com --force pergunta'       => [false, true,  false, true,  true],
    '--force sem terminal continua calado'    => [false, true,  false, false, false],
    '--no-custom vence o --force'             => [true,  true,  true,  true,  false],
])->group('kit');

/**
 * O sinal de "projeto nascendo" NÃO pode ser a existência do `.env`.
 *
 * Este é o teste que teria pego a v0.16.0. Ele olha a fonte porque o defeito é
 * de ORIGEM DO SINAL: qualquer teste que rode a decisão isolada passa nos dois
 * desenhos — só a sequência real do Composer os distingue, e ela não cabe numa
 * suíte.
 */
it('não decide "projeto novo" pela existência do .env', function (): void {
    $fonte = File::get((new ReflectionClass(KitInstall::class))->getFileName());

    expect($fonte)->not->toContain("File::exists(base_path('.env'))",
        'O `post-root-package-install` copia o .env ANTES do kit:install: gate por existência de '
        .'arquivo nunca deixa perguntar nada num create-project. Use `blank(config(\'app.key\'))`.'
    );
})->group('kit');

/*
|--------------------------------------------------------------------------
| R3 — cada resposta grava a sua chave, qualquer que seja o estado da linha
|--------------------------------------------------------------------------
| Os três estados convivem no mesmo .env recém-copiado: APP_NAME vem
| preenchida, DB_HOST vem comentada, e uma chave nova pode não existir.
*/

it('substitui a chave já preenchida sem tocar no resto do arquivo', function (): void {
    $linhasAntes = substr_count(envDoTeste(), "\n");

    customizadorNoTemp()->aplicar(respostasDeCustomizacao(['nome' => 'Loja do Ferro']));

    expect(valorNoEnv('APP_NAME'))->toBe('Loja do Ferro')
        ->and(envDoTeste())->toContain('# SQLite por padrão')
        ->and(substr_count(envDoTeste(), "\n"))->toBe($linhasAntes);
})->group('kit');

it('descomenta as chaves de banco ao escolher PostgreSQL', function (): void {
    expect(envDoTeste())->toContain('# DB_HOST=');

    customizadorNoTemp()->aplicar(respostasDeCustomizacao(['banco' => 'pgsql']));

    expect(valorNoEnv('DB_CONNECTION'))->toBe('pgsql')
        ->and(valorNoEnv('DB_HOST'))->toBe('127.0.0.1')
        ->and(valorNoEnv('DB_PORT'))->toBe('5432')
        ->and(valorNoEnv('DB_USERNAME'))->toBe('starter_kit');
})->group('kit');

it('acrescenta a chave ausente uma única vez', function (): void {
    File::put($this->base.'/.env', str_replace(
        'KIT_COR_PRIMARIA=',
        '',
        envDoTeste(),
    ));

    customizadorNoTemp()->aplicar(respostasDeCustomizacao(['cor' => 'Blue']));
    customizadorNoTemp()->aplicar(respostasDeCustomizacao(['cor' => 'Emerald']));

    expect(substr_count(envDoTeste(), 'KIT_COR_PRIMARIA='))->toBe(1)
        ->and(valorNoEnv('KIT_COR_PRIMARIA'))->toBe('Emerald');
})->group('kit');

it('mantém a senha padrão quando a resposta vem vazia', function (): void {
    customizadorNoTemp()->aplicar(respostasDeCustomizacao(['senha' => '']));

    expect(valorNoEnv('KIT_ADMIN_PASSWORD'))->toBe('password')
        ->and(config('kit.admin.password'))->toBe('password');
})->group('kit');

it('grava a senha escolhida quando ela é informada', function (): void {
    customizadorNoTemp()->aplicar(respostasDeCustomizacao(['senha' => 's3nh4-secreta']));

    expect(valorNoEnv('KIT_ADMIN_PASSWORD'))->toBe('s3nh4-secreta')
        ->and(config('kit.admin.password'))->toBe('s3nh4-secreta');
})->group('kit');

/*
|--------------------------------------------------------------------------
| R4 — entrada de humano não corrompe nem injeta linha no .env
|--------------------------------------------------------------------------
| O nome do projeto é texto livre digitado por uma pessoa e escrito DENTRO de
| um arquivo de configuração. É a única fronteira de confiança da feature.
*/

it('grava nome hostil sem quebrar o arquivo nem criar chave nova', function (string $nome): void {
    $chavesAntes = count(Dotenv\Dotenv::parse(envDoTeste()));

    customizadorNoTemp()->aplicar(respostasDeCustomizacao(['nome' => $nome]));

    // A quebra de linha é neutralizada (viraria uma chave nova); o resto sobrevive intacto.
    expect(valorNoEnv('APP_NAME'))->toBe(str_replace("\n", ' ', $nome))
        ->and(count(Dotenv\Dotenv::parse(envDoTeste())))->toBe($chavesAntes)
        ->and(valorNoEnv('APP_DEBUG'))->toBe('true');
})->with([
    'aspas duplas'      => 'Loja "do" Ferro',
    'acento e símbolo'  => 'Ação & Cia',
    'injeção de linha'  => "Loja\nAPP_DEBUG=false",
    'apóstrofo'         => "Ferro's",
    'quatro bytes'      => 'Kit 🚀',
    'cifrão'            => 'Kit $APP_ENV',
])->group('kit');

it('deriva um nome de banco que é identificador válido', function (string $nome, string $esperado): void {
    customizadorNoTemp()->aplicar(respostasDeCustomizacao(['nome' => $nome, 'banco' => 'mysql']));

    expect(valorNoEnv('DB_DATABASE'))->toBe($esperado);
})->with([
    ['Loja do Ferro', 'loja_do_ferro'],
    ['Ação & Cia', 'acao_cia'],
    ['Kit-2026', 'kit_2026'],
    ['2026 Kit', '_2026_kit'],
])->group('kit');

/*
|--------------------------------------------------------------------------
| R5 — o banco escolhido vale para a MESMA execução
|--------------------------------------------------------------------------
| Escrever no .env não muda a config já carregada. Sem o alinhamento em
| memória, o migrate desta execução iria para o SQLite — sem erro nenhum.
*/

it('grava o bloco do driver escolhido', function (string $banco, ?string $porta): void {
    customizadorNoTemp()->aplicar(respostasDeCustomizacao(['banco' => $banco]));

    expect(valorNoEnv('DB_CONNECTION'))->toBe($banco)
        ->and(valorNoEnv('DB_PORT'))->toBe($porta);
})->with([
    'sqlite sem serviço externo' => ['sqlite', null],
    'postgres recomendado'       => ['pgsql', '5432'],
    'mysql (RQ-05)'              => ['mysql', '3306'],
])->group('kit');

it('faz a conexão da execução corrente ser a escolhida', function (): void {
    expect(config('database.default'))->toBe('sqlite');

    customizadorNoTemp()->aplicar(respostasDeCustomizacao(['banco' => 'pgsql']));

    expect(config('database.default'))->toBe('pgsql');
})->group('kit');

/*
|--------------------------------------------------------------------------
| R7 — tenancy ligada na instalação
|--------------------------------------------------------------------------
| Aqui só as CHAVES: o schema com a coluna de contexto é tests/Tenancy.
*/

it('liga as três chaves da tenancy de uma vez', function (): void {
    customizadorNoTemp()->aplicar(respostasDeCustomizacao([
        'tenancy'              => true,
        'tenancy_label'        => 'Empresa',
        'tenancy_label_plural' => 'Empresas',
    ]));

    expect(valorNoEnv('KIT_TENANCY'))->toBe('true')
        ->and(valorNoEnv('KIT_TENANCY_LABEL'))->toBe('Empresa')
        ->and(valorNoEnv('KIT_TENANCY_LABEL_PLURAL'))->toBe('Empresas')
        ->and(valorNoEnv('KIT_TENANCY_SLUG'))->toBe('empresas')
        ->and(File::get($this->base.'/config/permission.php'))->toContain("'teams' => true")
        ->and(File::get($this->base.'/config/filament-shield.php'))->toContain('tenant_model')
        ->and(config('permission.teams'))->toBeTrue()
        ->and(config('kit.tenancy.label'))->toBe('Empresa');

    // O config real do projeto continua intocado.
    expect(File::get(config_path('permission.php')))->toContain("'teams' => false");
})->group('kit');

it('preserva chave própria do usuário ao reaplicar', function (): void {
    File::append($this->base.'/.env', PHP_EOL.'MINHA_CHAVE=valor-do-usuario'.PHP_EOL);

    customizadorNoTemp()->aplicar(respostasDeCustomizacao(['nome' => 'Primeiro Nome']));
    customizadorNoTemp()->aplicar(respostasDeCustomizacao(['nome' => 'Segundo Nome']));

    // Ancorado no início da linha: `VITE_APP_NAME` também contém "APP_NAME=".
    expect(valorNoEnv('APP_NAME'))->toBe('Segundo Nome')
        ->and(valorNoEnv('MINHA_CHAVE'))->toBe('valor-do-usuario')
        ->and(preg_match_all('/^APP_NAME=/m', envDoTeste()))->toBe(1);
})->group('kit');

/*
|--------------------------------------------------------------------------
| R9 e taxonomia — resumo e segredo
|--------------------------------------------------------------------------
*/

it('resume o que foi escolhido sem mostrar a senha', function (): void {
    $resumo = customizadorNoTemp()->aplicar(respostasDeCustomizacao([
        'nome'  => 'Loja do Ferro',
        'banco' => 'pgsql',
        'senha' => 's3nh4-secreta',
        'cor'   => 'Blue',
    ]));

    $texto = collect($resumo)->flatten()->implode(' | ');

    expect($texto)->toContain('Loja do Ferro')
        ->toContain('PostgreSQL')
        ->toContain('Blue')
        ->not->toContain('s3nh4-secreta');
})->group('kit');

it('aponta os sete itens que continuam manuais, cada um com o seu arquivo', function (): void {
    $itens = CustomizadorDaInstalacao::itensManuais();

    expect($itens)->toHaveCount(7);

    foreach (['login.svg', 'Funções', 'PapeisSeeder', 'configureHealthChecks', 'command-center', 'backup.php', 'Agentes de IA'] as $referencia) {
        expect(implode(' ', $itens))->toContain($referencia);
    }
})->group('kit');

it('nunca registra a senha escolhida no log', function (): void {
    Log::spy();

    customizadorNoTemp()->aplicar(respostasDeCustomizacao(['senha' => 's3nh4-secreta', 'banco' => 'pgsql']));

    Log::shouldHaveReceived('info')->withArgs(function (string $mensagem, array $contexto): bool {
        return str_contains($mensagem, '[CustomizadorDaInstalacao@aplicar]')
            && str_contains($mensagem, 'pgsql')
            && ! str_contains(json_encode($contexto, JSON_THROW_ON_ERROR), 's3nh4-secreta');
    })->once();
})->group('kit');

/*
|--------------------------------------------------------------------------
| A substituição em arquivo, isolada
|--------------------------------------------------------------------------
*/

it('não anexa nada quando o padrão não casa e não há fallback', function (): void {
    $alterou = SubstituicaoEmArquivo::aplicar($this->base.'/.env', '/^NAO_EXISTE=.*$/m', 'NAO_EXISTE=1');

    expect($alterou)->toBeFalse()
        ->and(envDoTeste())->not->toContain('NAO_EXISTE');
})->group('kit');

it('ignora arquivo inexistente em vez de estourar', function (): void {
    expect(SubstituicaoEmArquivo::aplicar($this->base.'/nao-existe.env', '/^A=.*$/m', 'A=1'))->toBeFalse();
})->group('kit');
