<?php

use App\Console\Commands\KitInstall;
use App\Support\CustomizadorDaInstalacao;
use App\Support\SubstituicaoEmArquivo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

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
})->group('kit');

/**
 * A ativação escreve no diretório que RECEBE, e em nenhum outro.
 *
 * A versão anterior deste caso afirmava que o `config/permission.php` do projeto
 * continuava com `'teams' => false` — o que é falso, e legitimamente, em qualquer
 * projeto que tenha ligado a multi-organização. O que interessa provar é que a
 * escrita foi para o diretório informado; o estado do projeto não é oráculo de
 * nada aqui.
 */
it('escreve a tenancy no diretório recebido, e não no do projeto', function (): void {
    $antes = File::get(config_path('permission.php'));

    customizadorNoTemp()->aplicar(respostasDeCustomizacao([
        'tenancy'              => true,
        'tenancy_label'        => 'Empresa',
        'tenancy_label_plural' => 'Empresas',
    ]));

    expect(File::get($this->base.'/config/permission.php'))->toContain("'teams' => true")
        ->and(File::get(config_path('permission.php')))->toBe($antes);
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

    // `login.svg` saiu da lista: a arte do login virou campo em
    // /admin/configuracoes-do-kit, e a linha que a substituiu aponta para lá.
    foreach (['Configurações do kit', 'Funções', 'PapeisSeeder', 'configureHealthChecks', 'command-center', 'backup.php', 'Agentes de IA'] as $referencia) {
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

/*
|--------------------------------------------------------------------------
| O plural sugerido para o rótulo da organização
|--------------------------------------------------------------------------
| Encontrado em teste manual da v0.16.1: quem apertasse Enter nas duas perguntas
| via "Organizaçãos" oferecido — regra de plural do inglês aplicada a português,
| no caminho mais comum de todos.
*/

it('sugere o plural certo quando o rótulo não foi alterado', function (): void {
    $sugerido = (new ReflectionMethod(CustomizadorDaInstalacao::class, 'pluralSugerido'))
        ->invoke(customizadorNoTemp(), 'Organização', 'Organização');

    expect($sugerido)->toBe('Organizações')
        ->and($sugerido)->not->toBe('Organizaçãos');
})->group('kit');

it('cai no palpite "+s" só quando o rótulo é novo', function (string $novo, string $esperado): void {
    $sugerido = (new ReflectionMethod(CustomizadorDaInstalacao::class, 'pluralSugerido'))
        ->invoke(customizadorNoTemp(), $novo, 'Organização');

    expect($sugerido)->toBe($esperado);
})->with([
    'Empresa' => ['Empresa', 'Empresas'],
    'Escola'  => ['Escola', 'Escolas'],
    'Loja'    => ['Loja', 'Lojas'],
])->group('kit');

/*
|--------------------------------------------------------------------------
| R9 — o caminho não destrutivo (`--custom`)
|--------------------------------------------------------------------------
| O `--force` refaz as cinco perguntas e APAGA o SQLite antes. Isso é inócuo no
| minuto seguinte à instalação e destrutivo depois — e o README recomendava o
| `--force` sem dizer isso. O `--custom` cobre o "depois", e o recorte dele é
| conservador de propósito: só o que vale por reescrita de `.env`.
*/

it('aplica nome e cor sem tocar em mais nada', function (): void {
    $env = test()->base.DIRECTORY_SEPARATOR.'.env';

    file_put_contents($env, "APP_NAME=Antigo\nKIT_COR_PRIMARIA=\nDB_CONNECTION=sqlite\n");

    $resumo = customizadorNoTemp()->aplicarSemBanco(['nome' => 'Meu Projeto', 'cor' => 'Blue']);

    $conteudo = (string) file_get_contents($env);

    // Com aspas porque é o que o `SubstituicaoEmArquivo::definirNoEnv()` escreve — asserir a
    // forma sem aspas passaria a medir a minha expectativa em vez do escritor de `.env`.
    expect($conteudo)->toContain('APP_NAME="Meu Projeto"')
        ->and($conteudo)->toContain('KIT_COR_PRIMARIA="Blue"')
        // A terceira asserção é a que importa: o que ele NÃO mexeu. Um `aplicarSemBanco()` que
        // reescrevesse DB_CONNECTION quebraria a promessa do nome do método.
        ->and($conteudo)->toContain('DB_CONNECTION=sqlite');

    expect($resumo)->toHaveCount(2);
});

/**
 * A cor vazia volta a ser "Padrão do Filament" no resumo, e não uma linha em branco.
 *
 * O `.env` recebe a chave vazia de propósito — é assim que o kit expressa "usa o default do
 * Filament" —, mas o resumo impresso precisa dizer isso em palavras.
 */
it('descreve a cor vazia como padrao do filament', function (): void {
    file_put_contents(test()->base.DIRECTORY_SEPARATOR.'.env', "APP_NAME=x\nKIT_COR_PRIMARIA=Blue\n");

    $resumo = customizadorNoTemp()->aplicarSemBanco(['nome' => 'x', 'cor' => '']);

    expect($resumo[1])->toBe(['Cor primária', 'Padrão do Filament']);
});

/**
 * CT-27 — o `--custom` propaga nome e cor para o settings, não só para o `.env`.
 *
 * Este caso existe por causa do conflito entre as DUAS fontes de configuração que a
 * feature de settings criou. O banco vence o `.env` em tempo de execução (ADR-01),
 * então um `aplicarSemBanco()` que só reescrevesse o arquivo faria o comando
 * imprimir "Nome do projeto: Refeito" e a tela continuar mostrando o nome antigo —
 * a resposta do usuário pareceria não ter efeito nenhum, sem erro nenhum.
 *
 * As duas metades no mesmo `Então` são o ponto: afirmar só o `.env` é exatamente o
 * que o caso vizinho já faz, e ele passa com a propagação removida.
 */
it('propaga nome e cor para o settings, alem do env', function (): void {
    $env = test()->base.DIRECTORY_SEPARATOR.'.env';

    file_put_contents($env, "APP_NAME=Antigo\nKIT_COR_PRIMARIA=\n");

    customizadorNoTemp()->aplicarSemBanco(['nome' => 'Refeito', 'cor' => 'Teal']);

    $conteudo = (string) file_get_contents($env);

    expect($conteudo)->toContain('APP_NAME="Refeito"')
        ->and($conteudo)->toContain('KIT_COR_PRIMARIA="Teal"');

    expect(json_decode((string) DB::table('settings')->where('group', 'kit')->where('name', 'nome_da_aplicacao')->value('payload')))
        ->toBe('Refeito')
        ->and(json_decode((string) DB::table('settings')->where('group', 'kit')->where('name', 'cor_primaria')->value('payload')))
        ->toBe('Teal');
});

/**
 * A cor vazia chega ao settings como NULO, não como string vazia.
 *
 * É a mesma semântica que o `.env` expressa com a chave vazia, e a propriedade é
 * `?string`: `''` num campo de cor é um valor que parece configurado e não é —
 * `CorPrimaria::paleta()` teria de tratar os dois casos em vez de um.
 */
it('propaga a cor vazia para o settings como nulo', function (): void {
    file_put_contents(test()->base.DIRECTORY_SEPARATOR.'.env', "APP_NAME=x\nKIT_COR_PRIMARIA=Blue\n");

    customizadorNoTemp()->aplicarSemBanco(['nome' => 'x', 'cor' => '']);

    expect(json_decode((string) DB::table('settings')->where('group', 'kit')->where('name', 'cor_primaria')->value('payload')))
        ->toBeNull();
});

/**
 * Sem a tabela de settings, o instalador segue — o `.env` é a única fonte ali.
 *
 * O `--custom` também roda em projeto que ainda não migrou, e um comando de
 * instalação não pode morrer por causa da tela de configurações. É o mesmo
 * raciocínio do `try/catch` do alinhamento no boot.
 */
it('aplica nome e cor mesmo sem a tabela de settings', function (): void {
    $env = test()->base.DIRECTORY_SEPARATOR.'.env';

    file_put_contents($env, "APP_NAME=Antigo\nKIT_COR_PRIMARIA=\n");

    Schema::drop('settings');

    customizadorNoTemp()->aplicarSemBanco(['nome' => 'Refeito', 'cor' => 'Teal']);

    expect((string) file_get_contents($env))->toContain('APP_NAME="Refeito"');
});

/*
|--------------------------------------------------------------------------
| R5 a R11 — o MySQL com container, e o nome do projeto Compose
|--------------------------------------------------------------------------
| O kit passou a subir um container MySQL, e isso muda o que o instalador grava:
| a senha deixou de nascer vazia (a imagem recusa inicializar sem ela), e o nome
| do projeto passou a ir para o `.env` como `COMPOSE_PROJECT_NAME`, que é o que
| prefixa todo container.
|
| O lado do `docker-compose.yml` mora em `tests/Kit/MysqlNoDockerTest.php`.
| Ver `wikis/specs/feat/mysql-no-docker/mysql-no-docker/`.
*/

/**
 * CT-08 — a instalação com MySQL grava um bloco de banco UTILIZÁVEL.
 *
 * `secret` literal, e não `config()`: é o valor que o requisito fixa, e o único jeito de um
 * default errado ficar vermelho é alguém escrever o valor do requisito.
 *
 * `DB_DATABASE` entra junto porque a regra "uma fonte só" vale para o nome do banco também: um
 * container que sobe, fica saudável e aceita `root` — mas sem o banco criado — faz o `migrate`
 * morrer em "Unknown database".
 */
it('[CT-08] a instalação com MySQL grava um bloco de banco utilizável', function (): void {
    customizadorNoTemp()->aplicar(respostasDeCustomizacao(['banco' => 'mysql', 'nome' => 'Loja do Ferro']));

    expect(valorNoEnv('DB_CONNECTION'))->toBe('mysql')
        ->and(valorNoEnv('DB_PASSWORD'))->toBe('secret')
        ->and(valorNoEnv('DB_USERNAME'))->toBe('root')
        ->and(valorNoEnv('DB_PORT'))->toBe('3306')
        ->and(valorNoEnv('DB_DATABASE'))->toBe('loja_do_ferro')
        ->and(valorNoEnv('DB_HOST'))->toBe('127.0.0.1');
})->group('kit');

/**
 * CT-11 — as DUAS chaves, e a linha crua.
 *
 * `APP_NAME` continuar com o nome cru é o que separa "derivei um nome de projeto" de "estraguei
 * o nome da aplicação": gravar o slug nas duas passaria numa asserção só.
 *
 * A linha é afirmada CRUA, com aspas, porque é assim que o escritor de `.env` do kit grava toda
 * chave — e é essa forma que o Compose vai ler. Medido: a chave com aspas produz o nome de
 * projeto sem elas, porque o Compose as remove.
 */
it('[CT-11] a instalação grava o nome do projeto Compose no .env', function (): void {
    customizadorNoTemp()->aplicar(respostasDeCustomizacao(['nome' => 'Loja do Ferro']));

    expect(envDoTeste())->toContain('COMPOSE_PROJECT_NAME="loja-do-ferro"')
        ->and(valorNoEnv('APP_NAME'))->toBe('Loja do Ferro');
})->group('kit');

/**
 * CT-12 — toda classe de nome digitado produz um nome que o Compose aceita.
 *
 * A linha do dígito inicial é a mais importante do conjunto: a implementação errada mais
 * provável é reusar o `nomeDeBanco()` que existe ao lado, e ele acerta a maioria dos nomes —
 * erra EXATAMENTE os que começam com dígito, porque prefixa underscore, e o Compose exige
 * começar por letra ou número.
 *
 * O invariante não substitui os valores exatos: o padrão aceito também é satisfeito por
 * `loja_do_ferro`, que é o que o reuso produziria.
 */
it('[CT-12] toda classe de nome digitado produz um nome de projeto válido', function (string $digitado, string $gravado): void {
    $chavesAntes = count(Dotenv\Dotenv::parse(envDoTeste()));

    customizadorNoTemp()->aplicar(respostasDeCustomizacao(['nome' => $digitado]));

    expect(valorNoEnv('COMPOSE_PROJECT_NAME'))->toBe($gravado)
        ->and($gravado)->toMatch('/^[a-z0-9][a-z0-9_-]*$/')
        ->and(count(Dotenv\Dotenv::parse(envDoTeste())))->toBe($chavesAntes);
})->with([
    'espaço vira hífen, não underscore' => ['Loja do Ferro', 'loja-do-ferro'],
    'dígito inicial é aceito'           => ['2026 Kit', '2026-kit'],
    'acento e símbolo'                  => ['Ação & Cia', 'acao-cia'],
    'aspas'                             => ['Loja "do" Ferro', 'loja-do-ferro'],
    'cifrão'                            => ['Kit $APP_ENV', 'kit-app-env'],
    'slug vazio cai no piso'            => ['###', 'starter-kit'],
    'injeção de linha'                  => ["Loja\nAPP_DEBUG=false", 'loja-app-debugfalse'],
])->group('kit');

it('[CT-13] o nome sugerido produz exatamente o prefixo de hoje', function (): void {
    customizadorNoTemp()->aplicar(respostasDeCustomizacao(['nome' => 'Starter Kit']));

    expect(valorNoEnv('COMPOSE_PROJECT_NAME'))->toBe('starter-kit');
})->group('kit');

/**
 * CT-16 (adaptado) — o instalador NÃO grava a chave do serviço de banco do Compose.
 *
 * O plano original mandava gravá-la no ramo `mysql`. A auditoria cortou, e por um motivo de
 * correção: gravada, ela sobreviveria a uma reinstalação que trocasse o banco — e faria o
 * profile `app` procurar um host morto. Pior: quem escolhesse MySQL e rodasse o profile `app`
 * sem ligar o profile do MySQL teria o `DB_HOST` apontando para um container que não subiu.
 *
 * A chave passou a viver comentada no `.env.docker`, onde quem containeriza a aplicação já está
 * copiando variáveis. Este caso trava a decisão nos três bancos.
 */
it('[CT-16] o instalador não grava a chave do serviço de banco do Compose', function (string $banco): void {
    customizadorNoTemp()->aplicar(respostasDeCustomizacao(['banco' => $banco]));

    expect(envDoTeste())->not->toContain('DOCKER_DB_SERVICE');
})->with(['sqlite', 'pgsql', 'mysql'])->group('kit');

/**
 * CT-17 — reaplicar não duplica a chave nova.
 *
 * O `.env` perde a chave de propósito antes: é o estado de um projeto instalado por uma versão
 * anterior do kit, que é justamente quem exercita o caminho de append do escritor.
 */
it('[CT-17] reaplicar a customização não duplica a chave do nome do projeto', function (): void {
    File::put(test()->base.'/.env', str_replace(
        'COMPOSE_PROJECT_NAME=starter-kit',
        '',
        envDoTeste(),
    ));

    customizadorNoTemp()->aplicar(respostasDeCustomizacao(['nome' => 'Primeiro']));
    customizadorNoTemp()->aplicar(respostasDeCustomizacao(['nome' => 'Segundo']));

    expect(substr_count(envDoTeste(), 'COMPOSE_PROJECT_NAME='))->toBe(1)
        ->and(valorNoEnv('COMPOSE_PROJECT_NAME'))->toBe('segundo');
})->group('kit');

/**
 * CT-18 — o caminho NÃO destrutivo também renomeia os containers.
 *
 * Sem isso, `kit:install --custom` renomeia a aplicação e deixa todos os containers com o
 * prefixo antigo — o mesmo modo de falha que o docblock de `propagarParaOSettings()` já
 * documenta para o settings: a resposta parece não ter efeito, e sem erro nenhum.
 *
 * A terceira asserção é a promessa do nome do método, e impede o conserto errado: copiar o
 * bloco de `aplicar()` para cá traria o bloco de banco junto, e o `--custom` passaria a
 * reescrever o banco de um projeto em produção.
 */
it('[CT-18] renomear a aplicação num projeto já instalado renomeia os containers', function (): void {
    File::put(test()->base.'/.env', "APP_NAME=Antigo\nDB_CONNECTION=sqlite\nKIT_COR_PRIMARIA=\n");

    customizadorNoTemp()->aplicarSemBanco(['nome' => 'Meu Projeto', 'cor' => '']);

    expect(envDoTeste())->toContain('COMPOSE_PROJECT_NAME="meu-projeto"')
        ->and(envDoTeste())->toContain('APP_NAME="Meu Projeto"')
        ->and(envDoTeste())->toContain('DB_CONNECTION=sqlite');
})->group('kit');

/**
 * CT-19 — o instalador deixa de afirmar que o kit não sobe container MySQL.
 *
 * Este é o único caso do conjunto em que a ausência NÃO filtra comentário: aqui o comentário É
 * o artefato sob teste. O docblock afirma um fato sobre o produto, e o fato deixou de ser
 * verdadeiro. Por isso o recorte é dos DOIS trechos — a linha do rótulo e o docblock do método
 * — em vez de varrer o arquivo, que proibiria qualquer comentário futuro citando a frase antiga
 * para explicar a mudança.
 *
 * A presença é o que fecha o buraco do sinônimo: proibir uma frase literal em português é
 * contornável por "o kit não provê MySQL", e o cenário ficaria verde com a mesma mentira na
 * tela. Exigir que o rótulo CITE o comando que a pessoa vai digitar não é.
 */
it('[CT-19] o instalador anuncia o container em vez de negá-lo', function (): void {
    $fonte = (string) file_get_contents(
        (string) (new ReflectionClass(CustomizadorDaInstalacao::class))->getFileName()
    );

    $rotulos = preg_grep('/^\s+.mysql.\s+=> ./', explode("\n", $fonte));
    $rotulo  = (string) reset($rotulos);

    $docblock = (string) (new ReflectionMethod(CustomizadorDaInstalacao::class, 'aplicarBanco'))->getDocComment();

    expect($rotulo)->toContain('docker compose up -d mysql redis');

    foreach (['rotulo' => $rotulo, 'docblock' => $docblock] as $onde => $trecho) {
        expect($trecho)->not->toMatch(
            '/\bnão\b(?:\W+\w+){0,4}\W+container/iu',
            "O {$onde} continua negando que o kit sobe container.",
        )->and($trecho)->not->toContain('o kit não sobe container MySQL');
    }
})->group('kit');
