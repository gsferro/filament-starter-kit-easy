<?php

use App\Support\SenhaDoAdministrador;
use App\Support\SubstituicaoEmArquivo;
use Illuminate\Support\Facades\File;

/**
 * O kit não distribui mais uma credencial utilizável.
 *
 * ## O defeito que este arquivo fecha
 *
 * `.env.example` trazia `KIT_ADMIN_PASSWORD=password`, e o `config/kit.php` repetia o literal como
 * fallback. Toda instalação que não trocasse a senha nascia com a **mesma credencial**, e o valor
 * está publicado no repositório — "mesma" quer dizer conhecida por qualquer pessoa que leia o kit.
 *
 * A saída não foi exigir que alguém defina a senha antes de instalar, o que transformaria a
 * instalação de um comando em duas etapas. Foi o instalador **gerar** uma, gravar no `.env` e
 * imprimi-la uma vez.
 */
it('o .env.example nao traz senha de administrador utilizavel', function (): void {
    $exemplo = File::get(base_path('.env.example'));

    /*
     * CONTROLE POSITIVO, e ele vem primeiro: um `.env.example` que deixasse de ter a chave faria
     * a ausência abaixo ficar VERDE sobre nada — e a chave AUSENTE é um defeito diferente e pior,
     * porque o instalador não teria onde gravar a senha gerada.
     */
    expect($exemplo)->toContain(SenhaDoAdministrador::CHAVE.'=');

    /*
     * `assertDoesNotMatchRegularExpression`, e não `expect()->not->toContain()`: o que se proíbe é
     * a chave com VALOR, não a menção ao literal — o comentário acima da chave explica que ela
     * vinha com senha publicada, e citar o fato não pode reprovar.
     */
    $this->assertDoesNotMatchRegularExpression(
        '/^'.preg_quote(SenhaDoAdministrador::CHAVE, '/').'=.+$/m',
        $exemplo,
        'O .env.example voltou a distribuir uma senha de administrador. Toda instalação que não a '
        .'trocar nasce com a mesma credencial, e ela está publicada no repositório.',
    );
})->group('kit');

/**
 * A REGRA, exercitada no predicado puro.
 *
 * A primeira redação deste caso variava o ambiente com `putenv()` e afirmava sobre `definida()`.
 * **Três asserções falharam**, e a causa não era a regra: `env()` lê do repositório que o
 * framework resolveu no boot, e `putenv()` depois disso não o alcança — o caso media o valor do
 * `phpunit.xml`. Daí o predicado puro: a regra virou testável, e `definida()` ficou sendo só a
 * ligação com o ambiente.
 */
it('reconhece senha utilizavel e nao utilizavel', function (?string $bruta, bool $utilizavel): void {
    expect(SenhaDoAdministrador::ehUtilizavel($bruta))->toBe($utilizavel);
})->with([
    'ausente'            => [null, false],
    'vazia'              => ['', false],
    'so espaco'          => ['   ', false],
    'o padrao publicado' => [SenhaDoAdministrador::PADRAO_PUBLICADO, false],

    'escolhida por quem instala'     => ['Uma-Senha-Que-Alguem-Escolheu', true],
    'zero, que e ruim mas e escolha' => ['0', true],
])->group('kit');

/**
 * A senha gerada precisa sobreviver à ida e volta pelo `.env`.
 *
 * É o elo que o docblock de `gerar()` nomeia: gerador → arquivo → Dotenv → terminal. Uma senha
 * impressa que não é a senha gravada é pior que senha fraca, porque ninguém entra e ninguém
 * entende por quê.
 */
it('a senha gerada e forte e volta identica do arquivo', function (): void {
    $senha = SenhaDoAdministrador::gerar();

    expect(mb_strlen($senha))->toBe(24)
        ->and($senha)->toMatch('/^[A-Za-z0-9]+$/')
        ->and($senha)->not->toBe(SenhaDoAdministrador::PADRAO_PUBLICADO);

    // Duas chamadas não podem coincidir — um gerador constante passaria em tudo acima.
    expect(SenhaDoAdministrador::gerar())->not->toBe($senha);

    $arquivo = base_path('storage/framework/testing/env-da-senha-'.uniqid());
    File::put($arquivo, "APP_NAME=Kit\n".SenhaDoAdministrador::CHAVE."=\n");

    SubstituicaoEmArquivo::definirNoEnv($arquivo, SenhaDoAdministrador::CHAVE, $senha);

    $gravado = File::get($arquivo);
    File::delete($arquivo);

    $this->assertMatchesRegularExpression(
        '/^'.preg_quote(SenhaDoAdministrador::CHAVE, '/').'="?'.preg_quote($senha, '/').'"?$/m',
        $gravado,
        'A senha gravada no .env difere da gerada — a pessoa não entraria com o que o instalador imprimiu.',
    );
})->group('kit');

/**
 * `garantirNoEnv()` devolve `null` quando já há senha — e é esse `null` que impede o instalador de
 * imprimir a senha que o usuário escolheu.
 */
it('nao gera nem imprime quando o ambiente ja define a senha', function (): void {
    /*
     * O `phpunit.xml` NÃO força `KIT_ADMIN_PASSWORD`, então o ambiente da suíte cai no fallback do
     * config — que é o padrão publicado, ou seja NÃO utilizável. Este caso precisa do oposto, e
     * `putenv()` não alcança o `env()` (ver o docblock de `ehUtilizavel`). A saída é afirmar o
     * contrato pelo predicado, que é quem `garantirNoEnv()` consulta.
     */
    expect(SenhaDoAdministrador::ehUtilizavel('Escolhida-Por-Quem-Instala'))->toBeTrue();

    $arquivo = base_path('storage/framework/testing/env-intocado-'.uniqid());
    File::put($arquivo, SenhaDoAdministrador::CHAVE."=Escolhida-Por-Quem-Instala\n");
    $antes = File::get($arquivo);

    $gerada = SenhaDoAdministrador::garantirNoEnv($arquivo, 'Escolhida-Por-Quem-Instala');

    $depois = File::get($arquivo);
    File::delete($arquivo);

    expect($gerada)->toBeNull()
        ->and($depois)->toBe($antes, 'o arquivo foi reescrito apesar de já haver senha definida');
})->group('kit');
