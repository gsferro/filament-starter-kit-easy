<?php

use App\Models\User;
use App\Support\CustomizadorDaInstalacao;
use App\Support\SenhaDoAdministrador;
use Illuminate\Console\Command;
use Illuminate\Console\OutputStyle;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Question\Question;

/**
 * O `kit:install` de VERDADE, pelo ponto de entrada real (`Artisan::call`) — não pelos métodos
 * privados por Reflection que `tests/Kit/CustomizadorDaInstalacaoTest.php` já usa para
 * `corrigirResumoDaSenha()`/`mensagemDoBanner()` (RD2-05).
 *
 * ## RD3-04 — por que este arquivo existe
 *
 * Os 4 testes `[RD2-05]` de `CustomizadorDaInstalacaoTest.php` chamam `corrigirResumoDaSenha()`
 * e `mensagemDoBanner()` isoladas, num `new KitInstall` cru — nenhum deles passa por `handle()`.
 * A revisão (RD3-04) removeu a chamada `$this->corrigirResumoDaSenha();` de `handle()` numa
 * cópia e trocou o argumento de `mensagemDoBanner()` por `true` fixo: os 4 testes citados,
 * mais `HostLocalTest` e `TenancyNaInstalacaoTest`, continuaram todos verdes. Nada liga a
 * correção ao comando que a pessoa realmente roda.
 *
 * ## O arnês: isolado, e sem travar em pergunta nenhuma
 *
 * `app()->setBasePath()` (a mesma técnica de `tests/Kit/KitArteTest.php:diretorioDeArte()`)
 * aponta TODOS os caminhos derivados de `base_path()` — `.env`, `public/`, `storage/`,
 * `app_path()`, `database_path()` — para um diretório temporário: o `kit:install` de verdade
 * NUNCA escreve no `.env`, no `config/` nem no `art/` deste repositório.
 *
 * `--no-interaction` é o que torna o resto seguro e determinístico. `KitInstall::temTerminal()`
 * continua `true` (o `|| $this->laravel->runningUnitTests()` do método), então o comando SEGUE
 * perguntando de verdade — mas `Symfony\Component\Console\Helper\QuestionHelper::ask()` devolve
 * o DEFAULT de cada pergunta sem tocar STDIN quando `$input->isInteractive()` é falso (é o que
 * `--no-interaction` liga), e é para ESSE valor que toda pergunta de `Laravel\Prompts`
 * degrada (`Illuminate\Console\Concerns\ConfiguresPrompts::configurePrompts()`, chamado por
 * `Command::run()` — o próprio ponto de entrada). Nenhuma trava, nenhuma lê disco fora do
 * `$base` isolado: a personalização, a etapa de host local (declinada, no default) e a oferta
 * de rodar a suíte (também declinada) acontecem todas de verdade, com os PADRÕES do próprio
 * código — nunca um valor que o teste inventou.
 *
 * `config(['app.key' => ''])` é o que faz `CustomizadorDaInstalacao::devePerguntar()` decidir
 * "projeto novo" sem precisar de `--force` (que dispara `ConfiguracoesDoKit::devolverConfigAoEnv()`,
 * dependente de `config_path()` — outro caminho que o `setBasePath()` desviaria para o diretório
 * vazio). `--no-npm` poupa o `npm install/build` real; `--no-support` evita o `exec('start ...')`
 * do convite de estrela.
 */
function diretorioDeInstalacaoDoKit(): string
{
    $base = sys_get_temp_dir().'/kit_install_'.Str::random(10);

    File::ensureDirectoryExists($base);
    // Lido uma vez, do repositório: numa segunda chamada do mesmo teste (CT-149) o base_path()
    // já aponta para o diretório isolado anterior, que não tem `.env.example`.
    static $exemplo = null;
    $exemplo ??= File::get(base_path('.env.example'));

    File::put("{$base}/.env", $exemplo);

    app()->setBasePath($base);

    return $base;
}

afterEach(function (): void {
    foreach (File::glob(sys_get_temp_dir().'/kit_install_*') as $temporario) {
        File::deleteDirectory($temporario);
    }
});

/**
 * Roda `kit:install` pelo Artisan de verdade e devolve a saída inteira (banner + resumo +
 * avisos), na ordem em que o comando de fato imprime.
 *
 * @param  array<string, mixed>  $opcoes
 */
function rodarKitInstallDeVerdade(array $opcoes = []): string
{
    // (RD3-04, achado da retomada) `Artisan::output()` não serve aqui: quando `semear()` roda de
    // verdade (sem `--no-seed`), `ShieldPermissionsSeeder::run()` chama `Artisan::call('shield:generate', ...)`
    // pela FACADE (não por `$this->call()`) — e isso reatribui `Illuminate\Console\Application::$lastOutput`
    // para o BUFFER do `shield:generate`, não do `kit:install`. Como `Artisan::output()` sempre lê o
    // ÚLTIMO buffer atribuído àquela propriedade (fetch() é destrutivo), o texto que o PRÓPRIO
    // `kit:install` escreve depois do seed (banner, resumo) fica noutro buffer e nunca aparece
    // aqui — vira string vazia. Passar nosso PRÓPRIO `BufferedOutput` como terceiro argumento evita
    // isso: `Command::run()` escreve direto nesse objeto (por referência), então o conteúdo
    // sobrevive a qualquer reatribuição posterior de `$lastOutput` — só o buffer que NÓS criamos
    // é lido de volta.
    $buffer = new BufferedOutput;

    Artisan::call('kit:install', [...$opcoes, '--no-interaction' => true], $buffer);

    return $buffer->fetch();
}

/**
 * Separa da saída inteira o BANNER (da linha "Pronto!" até o item "Suba o servidor") e a linha
 * "Senha do administrador" do RESUMO (do item até o próximo, "Cor primária"). Os dois já saem
 * sem acento e sem caixa. Falha se algum dos dois não existe: sem eles, toda asserção de
 * ausência valeria no vazio.
 *
 * @return array{banner: string, linha: string}
 */
function bannerELinhaDaSenha(string $saida): array
{
    $normalizada = semAcentoESemCaixa($saida);

    $inicioDoBanner = mb_strpos($normalizada, 'pronto! o projeto esta instalado');
    $fimDoBanner    = mb_strpos($normalizada, 'suba o servidor com');
    $inicioDoResumo = mb_strpos($normalizada, 'o que foi customizado nesta instalacao');

    expect($inicioDoBanner)->not->toBeFalse('a saida nao tem o banner')
        ->and($fimDoBanner)->not->toBeFalse('a saida nao fecha o banner')
        ->and($inicioDoResumo)->not->toBeFalse('a saida nao tem o resumo');

    $inicioDaLinha = mb_strpos($normalizada, 'senha do administrador', $inicioDoResumo);
    $fimDaLinha    = mb_strpos($normalizada, 'cor primaria', (int) $inicioDaLinha);

    expect($inicioDaLinha)->not->toBeFalse('o resumo nao tem a linha "Senha do administrador"')
        ->and($fimDaLinha)->not->toBeFalse('o resumo nao tem o item seguinte a linha da senha');

    return [
        'banner' => mb_substr($normalizada, $inicioDoBanner, $fimDoBanner - $inicioDoBanner),
        'linha'  => mb_substr($normalizada, $inicioDaLinha, $fimDaLinha - $inicioDaLinha),
    ];
}

/**
 * A instrução do banco não populado: em `$texto` (já normalizado), `KIT_ADMIN_PASSWORD` aparece
 * ANTES de `db:seed` — a posição da primeira ocorrência de cada um.
 */
function chaveVemAntesDoSeed(string $texto): bool
{
    $chave = mb_strpos($texto, 'kit_admin_password');
    $seed  = mb_strpos($texto, 'db:seed');

    return $chave !== false && $seed !== false && $chave < $seed;
}

/**
 * A lista do 04 (R55 M7, ADV2-04): as marcas de "opcional" numa instrução. A classe é aberta;
 * a lista fecha só a forma que cada achado mostrou.
 */
function marcaComoOpcional(string $texto): bool
{
    return Str::contains($texto, [
        'se quiser', 'caso queira', 'opcional', 'if you want', 'optional',
        'recomendado', 'recommended', 'ou rode', 'or run', 'direto',
    ]);
}

/**
 * KIT_ADMIN_PASSWORD e db:seed ligados pela palavra inteira "ou" ou "or", entre a primeira
 * ocorrência de um e a primeira do outro, no mesmo texto (já normalizado): o db:seed como
 * alternativa à definição da chave. "e", "depois" e "entao" não ligam.
 */
function chaveESeedLigadosPorOu(string $texto): bool
{
    $chave = mb_strpos($texto, 'kit_admin_password');
    $seed  = mb_strpos($texto, 'db:seed');

    if ($chave === false || $seed === false) {
        return false;
    }

    $entre = mb_substr($texto, min($chave, $seed), abs($seed - $chave));

    return preg_match('~(ou|or)~u', $entre) === 1;
}

/**
 * A forma fechada de "o banner imprime uma senha" (ADV2-17): "e-mail / valor", ou "senha:" ou
 * "password:" seguido de um valor. `$banner` já vem normalizado.
 */
function bannerTemFormaDeSenhaImpressa(string $banner, string $email): bool
{
    $emailNormalizado = preg_quote(semAcentoESemCaixa($email), '~');

    return preg_match("~{$emailNormalizado}\s*/\s*\S~u", $banner) === 1
        || preg_match('~(senha|password):\s*\S~u', $banner) === 1;
}

/**
 * Há senha impressa no banner? Só o ramo "Login inicial: e-mail / senha" a imprime; fora da
 * chave `KIT_ADMIN_PASSWORD` (que é o NOME, não o valor), a palavra "password" também não pode
 * aparecer no banner como se fosse a senha.
 */
function bannerImprimeSenha(string $banner): bool
{
    $semANomeDaChave = str_replace('kit_admin_password', '', $banner);

    return preg_match('~login inicial:[^\n]*\s/\s\S~', $banner) === 1
        || str_contains($semANomeDaChave, 'password');
}

/**
 * A senha que o banner imprime no ramo gerado ("Login inicial: e-mail / senha"), ou null.
 * Recebe a saída CRUA: a senha gerada tem caixa mista, e a normalizada a perderia.
 */
function senhaImpressaNaSaida(string $saida): ?string
{
    return preg_match('~Login inicial: \S+ / (\S+)~', $saida, $achado) === 1 ? $achado[1] : null;
}

/**
 * O valor de KIT_ADMIN_PASSWORD relido do `.env` do diretório isolado.
 */
function senhaRelidaDoEnv(string $base): string
{
    return (string) (Dotenv\Dotenv::parse(File::get("{$base}/.env"))['KIT_ADMIN_PASSWORD'] ?? '');
}

/**
 * Grava KIT_ADMIN_PASSWORD no `.env` isolado e a mesma em `config()`, o desenho de "senha já
 * definida no arquivo" (o boot a levaria à config).
 */
function definirSenhaNoArquivoENaConfig(string $base, string $senha): void
{
    $env = File::get("{$base}/.env");

    File::put("{$base}/.env", (string) preg_replace('~^KIT_ADMIN_PASSWORD=.*$~m', "KIT_ADMIN_PASSWORD={$senha}", $env));
    config(['kit.admin.password' => $senha]);
}

/**
 * O `kit:install` de verdade SEM `--no-interaction`, com a pergunta da senha respondida por
 * `$senhaDigitada` e todas as outras com o PADRÃO da própria pergunta.
 *
 * `$this->artisan()->expectsQuestion()` não serve: ele não devolve a saída inteira. Então repete o
 * que o `PendingCommand` faz por dentro — liga ao container um `OutputStyle` cujo `askQuestion`
 * é interceptado — só que sobre o NOSSO buffer, que é lido de volta.
 */
function rodarKitInstallDigitandoASenha(string $senhaDigitada, array $opcoes = []): string
{
    $buffer = new BufferedOutput;

    $estilo = Mockery::mock(OutputStyle::class.'[askQuestion]', [new ArrayInput([]), $buffer]);
    $estilo->shouldReceive('askQuestion')->andReturnUsing(
        fn (Question $pergunta): mixed => $pergunta->getQuestion() === 'Senha do administrador'
            ? $senhaDigitada
            : $pergunta->getDefault(),
    );

    app()->bind(OutputStyle::class, fn () => $estilo);

    Artisan::call('kit:install', $opcoes, $buffer);

    return $buffer->fetch();
}
/*
|--------------------------------------------------------------------------
| RD3-01/RD3-04 — banco não populado (--no-seed): banner e resumo dão a MESMA
| instrução, e ela funciona
|--------------------------------------------------------------------------
| Hoje o banner manda "php artisan db:seed depois de resolver o banco" (que, com
| KIT_ADMIN_PASSWORD vazio, semeia o admin com a senha PADRÃO PUBLICADA) e o resumo manda
| "php artisan kit:admin" (que falha: não existe administrador nenhum para atualizar). As
| duas promessas contradizem o que a `SenhaDoAdministrador::PADRAO_PUBLICADO` proíbe, e uma
| delas nem funciona.
*/

it('[RD3-01][RD3-04][CT-121] com --no-seed, o banner e o resumo dao a MESMA orientacao para popular o banco, e ela funciona', function (): void {
    diretorioDeInstalacaoDoKit();
    config(['app.key' => '']); // projeto "novo" para CustomizadorDaInstalacao::devePerguntar()

    $saida = rodarKitInstallDeVerdade([
        '--no-seed'    => true,
        '--no-npm'     => true,
        '--no-support' => true,
    ]);

    // Prova de que handle() de fato passou pelas DUAS etapas que RD2-05 corrigiu — não é
    // suposição: se `resumoDaCustomizacao()` ficasse muda (resumo vazio) ou o banner não
    // rodasse, nenhuma das assercoes abaixo teria onde se apoiar.
    expect($saida)->toContain('Pronto! O projeto está instalado.')
        ->and($saida)->toContain('O que foi customizado nesta instalação:')
        ->and($saida)->toContain('Senha do administrador');

    // A MESMA orientação nos dois lugares: a chave a definir, e o comando que só DEPOIS dela
    // semeia de verdade.
    expect($saida)->toContain('KIT_ADMIN_PASSWORD')
        ->and($saida)->toContain('php artisan db:seed');

    // "kit:admin" falha sem administrador nenhum: não pode ser a instrução impressa para o
    // caso banco-não-populado, nem no banner nem no resumo (RD3-01).
    $this->assertStringNotContainsString(
        'kit:admin',
        $saida,
        'a saida do kit:install --no-seed ainda cita "kit:admin" — esse comando falha sem nenhum administrador criado (RD3-01)',
    );

    // R56/CT-121 (a DV-03, RQ-44): as duas metades — a linha do resumo e o banner — comparadas SEM
    // acento e SEM caixa dos dois lados, porque o banner é ASCII e a linha tem acento.
    ['banner' => $banner, 'linha' => $linha] = bannerELinhaDaSenha($saida);

    $this->assertStringNotContainsString('gerada', $linha, 'a linha "Senha do administrador" ainda promete senha "gerada" com --no-seed (R56.M1)');
    $this->assertStringNotContainsString('impressa', $linha, 'a linha "Senha do administrador" ainda promete senha "impressa" com --no-seed (R56.M1)');
    $this->assertStringNotContainsString('a que voce definiu', $banner, 'o banner com --no-seed promete "a que voce definiu" para um administrador que nao existe (R56.M2)');

    // "mandam definir KIT_ADMIN_PASSWORD e só então rodar php artisan db:seed", nos DOIS textos.
    expect($banner)->toContain('php artisan db:seed')
        ->and($linha)->toContain('php artisan db:seed')
        ->and(chaveVemAntesDoSeed($banner))->toBeTrue('no banner, KIT_ADMIN_PASSWORD nao vem antes de db:seed')
        ->and(chaveVemAntesDoSeed($linha))->toBeTrue('na linha, KIT_ADMIN_PASSWORD nao vem antes de db:seed');

    $this->assertStringNotContainsString('kit:admin', semAcentoESemCaixa($saida));
})->group('kit');

/*
|--------------------------------------------------------------------------
| RD3-03 — o resumo não pode dizer "banco não populado" quando ele FOI
|--------------------------------------------------------------------------
| `corrigirResumoDaSenha()` decide por `$this->senhaGerada === null`, que também é `null`
| quando `garantirNoEnv()` encontrou uma senha JÁ utilizável na config (variável exportada,
| linha do `.env` vazia) — o seeder RODA com essa senha, um administrador de verdade nasce, e
| ainda assim o resumo reescreve a linha para "nenhuma foi definida — o banco não foi populado
| nesta execução", contradizendo o próprio banner (que, com `$semeado=true`, diz "a senha é a
| que você definiu").
*/

it('[RD3-03][CT-122] com senha ja utilizavel via config e o arquivo vazio, semear() RODA e o resumo nao pode negar isso', function (): void {
    diretorioDeInstalacaoDoKit();
    config(['app.key' => '']);

    // O `.env` de destino continua com KIT_ADMIN_PASSWORD= vazio (herdado do .env.example);
    // só a CONFIG já tem uma senha utilizável — o mesmo desenho de uma variável exportada no
    // ambiente por cima de uma linha vazia do arquivo (SONDA-A do achado).
    config(['kit.admin.password' => 'senhaJaUtilizavelViaConfig1']);

    $saida = rodarKitInstallDeVerdade([
        '--no-npm'     => true,
        '--no-support' => true,
    ]);

    // sqlite está sempre acessível e `--no-seed` não foi passado: migrar()+semear() RODAM de
    // verdade — prova de que o cenário não é "nada aconteceu".
    expect($saida)->toContain('Populando papéis, permissões e usuário inicial');

    $this->assertStringNotContainsString(
        'não foi populado',
        $saida,
        'o resumo (corrigirResumoDaSenha) afirma que o banco não foi populado nesta execução, mas semear() rodou de verdade com a senha que a config já tinha (RD3-03)',
    );

    // R56/CT-122: a linha diz que a senha é a que está em KIT_ADMIN_PASSWORD, e o banner não
    // imprime senha nenhuma (só a gerada é impressa; esta foi definida por quem instala).
    ['banner' => $banner, 'linha' => $linha] = bannerELinhaDaSenha($saida);

    expect($linha)->toContain('kit_admin_password')
        ->and($linha)->not->toContain('banco nao foi populado')
        ->and($linha)->not->toContain('nao foi populado');

    $this->assertStringNotContainsString(
        semAcentoESemCaixa('senhaJaUtilizavelViaConfig1'),
        $banner,
        'o banner imprime a senha que quem instalou definiu',
    );
    expect(bannerImprimeSenha($banner))->toBeFalse('o banner imprime senha, e a definida por quem instala nao aparece no terminal');
})->group('kit');

/*
|--------------------------------------------------------------------------
| R56/CT-133 — --no-seed: a chave vem antes do db:seed, como condição, e nada promete senha
|--------------------------------------------------------------------------
*/

it('[CT-133] com --no-seed, a instrucao poe a chave antes do db:seed, como condicao, e nada promete senha', function (): void {
    diretorioDeInstalacaoDoKit();
    config(['app.key' => '']);

    $saida = rodarKitInstallDeVerdade([
        '--no-seed'    => true,
        '--no-npm'     => true,
        '--no-support' => true,
    ]);

    ['banner' => $banner, 'linha' => $linha] = bannerELinhaDaSenha($saida);

    // "KIT_ADMIN_PASSWORD vem antes de db:seed", no banner E na linha, e nenhum marca a definição
    // como opcional (lista fechada do 04, sem acento e sem caixa).
    expect(chaveVemAntesDoSeed($banner))->toBeTrue('no banner, db:seed vem antes de KIT_ADMIN_PASSWORD (ou um deles falta)')
        ->and(chaveVemAntesDoSeed($linha))->toBeTrue('na linha, db:seed vem antes de KIT_ADMIN_PASSWORD (ou um deles falta)')
        ->and(marcaComoOpcional($banner))->toBeFalse('o banner marca a definicao de KIT_ADMIN_PASSWORD como opcional')
        ->and(marcaComoOpcional($linha))->toBeFalse('a linha marca a definicao de KIT_ADMIN_PASSWORD como opcional');

    // ADV2-04: o db:seed nunca é alternativa à definição da chave ("ou"/"or" entre os dois).
    expect(chaveESeedLigadosPorOu($banner))->toBeFalse('no banner, KIT_ADMIN_PASSWORD e db:seed estao ligados por "ou"/"or": o db:seed e oferecido como alternativa')
        ->and(chaveESeedLigadosPorOu($linha))->toBeFalse('na linha, KIT_ADMIN_PASSWORD e db:seed estao ligados por "ou"/"or": o db:seed e oferecido como alternativa');

    // A promessa da senha gerada, lida da CONSTANTE na execução — não as palavras de hoje dela.
    $this->assertStringNotContainsString(
        semAcentoESemCaixa(CustomizadorDaInstalacao::RESUMO_SENHA_GERADA),
        $linha,
        'a linha ainda diz o que RESUMO_SENHA_GERADA promete',
    );

    // Sem acento e sem caixa: o banner ASCII não diz "a que voce definiu"; a linha não diz
    // "gerada" nem "impressa".
    $this->assertStringNotContainsString('a que voce definiu', $banner);
    $this->assertStringNotContainsString('gerada', $linha);
    $this->assertStringNotContainsString('impressa', $linha);

    $this->assertStringNotContainsString('kit:admin', semAcentoESemCaixa($saida));
})->group('kit');

/*
|--------------------------------------------------------------------------
| R56/CT-134 — senha já definida + --no-seed: nada promete a gerada, o banner não a imprime,
| e o .env a guarda
|--------------------------------------------------------------------------
*/

it('[CT-134] com a senha ja definida e --no-seed, nada promete a senha gerada, o banner nao imprime senha, e o .env a guarda', function (string $origem, string $senha): void {
    $base = diretorioDeInstalacaoDoKit();
    config(['app.key' => '']);

    $opcoes = ['--no-seed' => true, '--no-npm' => true, '--no-support' => true];

    if ($origem === 'arquivo') {
        definirSenhaNoArquivoENaConfig($base, $senha);

        $saida = rodarKitInstallDeVerdade($opcoes);
    } else {
        // KIT_ADMIN_PASSWORD vazio no arquivo (o do .env.example) e digitada no prompt, SEM
        // --no-interaction: as outras perguntas recebem o padrão de cada uma.
        $saida = rodarKitInstallDigitandoASenha($senha, $opcoes);
    }

    ['banner' => $banner, 'linha' => $linha] = bannerELinhaDaSenha($saida);

    $this->assertStringNotContainsString(
        semAcentoESemCaixa(CustomizadorDaInstalacao::RESUMO_SENHA_GERADA),
        $linha,
        'a linha promete a senha gerada, mas a senha ja estava definida',
    );
    $this->assertStringNotContainsString('gerada', $linha);
    $this->assertStringNotContainsString('impressa', $linha);

    $this->assertStringNotContainsString(semAcentoESemCaixa($senha), $banner, "o banner imprime a senha {$senha}");
    expect(bannerImprimeSenha($banner))->toBeFalse('o banner imprime alguma senha')
        ->and(bannerTemFormaDeSenhaImpressa($banner, (string) config('kit.admin.email')))->toBeFalse('o banner tem a forma de senha impressa: "e-mail / valor", "senha:" ou "password:" seguido de valor');

    // ADV2-02: o banner diz que nenhum administrador existe, e nao oferece login.
    expect(Str::contains($banner, ['nenhum usuario foi criado', 'nenhum administrador foi criado']))
        ->toBeTrue('o banner nao diz que nenhum usuario/administrador foi criado')
        ->and($banner)->not->toContain('login inicial');

    $this->assertStringNotContainsString('kit:admin', semAcentoESemCaixa($saida));

    expect(senhaRelidaDoEnv($base))->toBe($senha);
})->with([
    'definida no arquivo (a que voce ja definiu)' => ['arquivo', 'senhaDoArquivo1'],
    'digitada no prompt (a que voce digitou)'     => ['digitada', 'segredoDigitado1'],
])->group('kit');

/*
|--------------------------------------------------------------------------
| R63/CT-135 — a senha que a saída dá como a do administrador é a que o autentica
|--------------------------------------------------------------------------
| O oráculo é o BANCO: `Hash::check()` contra o administrador que o `db:seed` do próprio
| `handle()` semeou, lido por `config('kit.admin.email')` na mesma conexão.
*/

it('[CT-135] a senha que a saida da como a do administrador e a que o autentica, e password nao', function (string $noArquivo, string $naConfig, string $origem): void {
    $base = diretorioDeInstalacaoDoKit();
    config(['app.key' => '']);

    if ($noArquivo !== '') {
        definirSenhaNoArquivoENaConfig($base, $noArquivo);
    }

    config(['kit.admin.password' => $naConfig]);

    $saida = rodarKitInstallDeVerdade(['--no-npm' => true, '--no-support' => true]);

    ['banner' => $banner] = bannerELinhaDaSenha($saida);

    $administrador = User::where('email', config('kit.admin.email'))->first();

    expect($administrador)->not->toBeNull('o db:seed do kit:install nao semeou o administrador');

    if ($origem === 'gerada') {
        $impressa = senhaImpressaNaSaida($saida);

        expect($impressa)->not->toBeNull('o banner nao imprime a senha gerada')
            ->and($impressa)->not->toBe('password')
            ->and(Hash::check($impressa, $administrador->password))->toBeTrue('o administrador semeado nao autentica com a senha que o banner imprime')
            ->and(senhaRelidaDoEnv($base))->toBe($impressa);
    } else {
        $senhaQueVale = $noArquivo !== '' ? $noArquivo : $naConfig;

        expect(bannerImprimeSenha($banner))->toBeFalse('o banner imprime senha, mas a que vale foi definida por quem instala')
            ->and(Hash::check($senhaQueVale, $administrador->password))->toBeTrue("o administrador semeado nao autentica com {$senhaQueVale}")
            ->and(senhaRelidaDoEnv($base))->toBe($noArquivo);
    }

    expect(Hash::check('password', $administrador->password))->toBeFalse('o administrador semeado autentica com "password"');
})->with([
    'nada utilizavel: gerada (G e S), padrao publicado no ambiente' => ['', 'password', 'gerada'],
    'utilizavel so no ambiente'                                     => ['', 'outraSenhaUtilizavel1', 'ambiente'],
    'no arquivo, levada a config pelo boot'                         => ['senhaDoArquivo1', 'senhaDoArquivo1', 'arquivo'],
])->group('kit');

/*
|--------------------------------------------------------------------------
| R55/CT-136 — o db:seed que não completa, depois de a senha ser gerada
|--------------------------------------------------------------------------
| Arnês: um comando `db:seed` de teste, registrado no Artisan da própria execução, que devolve 1.
*/

it('[CT-136] o db:seed que nao completa depois de a senha ser gerada nao deixa promessa sem saida', function (): void {
    $base = diretorioDeInstalacaoDoKit();
    config(['app.key' => '']);
    config(['kit.admin.password' => 'password']); // o padrao publicado, com a chave vazia no arquivo

    Artisan::registerCommand(new class extends Command
    {
        protected $signature = 'db:seed {--force} {--class=} {--database=}';

        protected $description = 'db:seed de teste: nunca completa';

        public function handle(): int
        {
            return self::FAILURE;
        }
    });

    $saida = rodarKitInstallDeVerdade(['--no-npm' => true, '--no-support' => true]);

    $normalizada = semAcentoESemCaixa($saida);

    // "a saída diz que a semeadura não completou e manda rodar php artisan db:seed"
    expect(Str::contains($normalizada, ['nao completaram', 'nao completou', 'nao foi concluida', 'nao terminou']))
        ->toBeTrue('a saida nao diz que a semeadura nao completou')
        ->and($normalizada)->toContain('php artisan db:seed');

    // O .env já tem uma senha utilizável, e toda senha que a saída imprime é ela.
    $noEnv = senhaRelidaDoEnv($base);

    expect(SenhaDoAdministrador::ehUtilizavel($noEnv))->toBeTrue('KIT_ADMIN_PASSWORD, relido do .env, nao e utilizavel');

    // ADV2-03: presenca, nao so condicional — e a impressa e a relida do .env.
    $impressa = senhaImpressaNaSaida($saida);

    expect($impressa)->not->toBeNull('a saida nao imprime senha nenhuma: a gerada ficou no .env sem que ninguem a visse')
        ->and($impressa)->toBe($noEnv);

    // "não apresenta password como a senha": fora do NOME da chave, a palavra nao aparece.
    $this->assertStringNotContainsString('password', str_replace('kit_admin_password', '', $normalizada));
})->group('kit');

/*
|--------------------------------------------------------------------------
| R63/CT-149 — duas instalações sem senha definida geram senhas diferentes
|--------------------------------------------------------------------------
| O arnês do CT-135 duas vezes: dois diretórios isolados com o mesmo `.env` do `.env.example`, e o
| administrador de A apagado entre as execuções (`migrate:fresh` não roda: VACUUM dentro da transação
| do teste) — o seeder garante que EXISTA administrador, e sem apagar B acharia o de A.
*/

it('[CT-149] duas instalacoes sem senha definida geram senhas diferentes, e cada uma autentica so o seu administrador', function (): void {
    $instalar = function (): array {
        $base = diretorioDeInstalacaoDoKit();
        config(['app.key' => '', 'kit.admin.password' => 'password']);

        $saida = rodarKitInstallDeVerdade(['--no-npm' => true, '--no-support' => true]);

        return [$base, senhaImpressaNaSaida($saida), senhaRelidaDoEnv($base), User::where('email', config('kit.admin.email'))->first()];
    };

    [$baseA, $impressaA, $noEnvA, $administradorA] = $instalar();

    expect($administradorA)->not->toBeNull('a instalacao A nao semeou o administrador')
        ->and($impressaA)->not->toBeNull('a saida de A nao imprime senha');

    $autenticaEmAComSA = Hash::check($impressaA, $administradorA->password);

    // "Banco recriado" dentro da transacao do teste: `migrate:fresh` falha no sqlite (VACUUM dentro de
    // transacao), entao o que o seeder acharia de A — o administrador — e apagado.
    User::withTrashed()->forceDelete();

    [$baseB, $impressaB, $noEnvB, $administradorB] = $instalar();

    expect($administradorB)->not->toBeNull('a instalacao B nao semeou o administrador')
        ->and($impressaB)->not->toBeNull('a saida de B nao imprime senha')
        ->and($impressaB)->not->toBe($impressaA)
        ->and($impressaA)->not->toBe('password')
        ->and($impressaB)->not->toBe('password')
        ->and($autenticaEmAComSA)->toBeTrue('o administrador de A nao autenticava com S_A')
        ->and($noEnvB)->toBe($impressaB)
        ->and($noEnvA)->toBe($impressaA)
        ->and(senhaRelidaDoEnv($baseA))->toBe($impressaA)
        ->and(Hash::check($impressaB, $administradorB->password))->toBeTrue('o administrador de B nao autentica com S_B')
        ->and(Hash::check($impressaA, $administradorB->password))->toBeFalse('o administrador de B autentica com S_A');
})->group('kit');
