<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Symfony\Component\Console\Output\BufferedOutput;

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
    File::copy(base_path('.env.example'), "{$base}/.env");

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

it('[RD3-01][RD3-04] com --no-seed, o banner e o resumo dao a MESMA orientacao para popular o banco, e ela funciona', function (): void {
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

it('[RD3-03] com senha ja utilizavel via config e o arquivo vazio, semear() RODA e o resumo nao pode negar isso', function (): void {
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
})->group('kit');
