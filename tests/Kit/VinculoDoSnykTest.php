<?php

use App\Support\VinculoDoSnyk;
use Illuminate\Support\Facades\File;

/**
 * O vínculo com o Snyk sai na instalação — e só na instalação.
 *
 * Os casos escrevem num diretório TEMPORÁRIO, nunca no `base_path()`, pela mesma razão que o
 * cabeçalho de `CustomizadorDaInstalacaoTest` dá: o código sob teste APAGA arquivos, e
 * apontá-lo para o projeto faria a suíte remover o `.snyk` de quem roda os testes.
 *
 * Os dois últimos casos leem o `composer.json` porque é lá que o erro de verdade acontece. Ao
 * escrever esta feature o flag `--create-project` foi para o script `setup` por engano — o
 * `kit:install --ansi` aparece DUAS vezes no arquivo, e a primeira é a do `setup`. Como
 * `composer setup` é o que se roda depois de clonar o KIT, o efeito seria o instalador apagar
 * o `.snyk` e o workflow da própria fonte. Nenhum caso de comportamento pega isso: os dois
 * scripts chamam o mesmo comando e o mesmo código.
 */
beforeEach(function (): void {
    $this->base = sys_get_temp_dir().'/kit-snyk-'.bin2hex(random_bytes(4));
    File::ensureDirectoryExists($this->base.'/.github/workflows');
});

afterEach(function (): void {
    File::deleteDirectory($this->base);
});

it('apaga o .snyk e o workflow de seguranca, e devolve os dois', function (): void {
    File::put($this->base.'/.snyk', "version: v1.25.0\nignore: {}\n");
    File::put($this->base.'/.github/workflows/seguranca.yml', "name: Seguranca\n");

    $apagados = VinculoDoSnyk::remover($this->base);

    expect($apagados)->toHaveCount(2)
        ->and(File::exists($this->base.'/.snyk'))->toBeFalse()
        ->and(File::exists($this->base.'/.github/workflows/seguranca.yml'))->toBeFalse();
})->group('kit');

it('nao toca no ci.yml, que e do usuario', function (): void {
    File::put($this->base.'/.snyk', "ignore: {}\n");
    File::put($this->base.'/.github/workflows/ci.yml', "name: CI\n");

    VinculoDoSnyk::remover($this->base);

    expect(File::exists($this->base.'/.github/workflows/ci.yml'))->toBeTrue(
        'A varredura mora em workflow PRÓPRIO justamente para o instalador apagar um arquivo '
        .'em vez de operar dentro do ci.yml, que o usuário também edita.'
    );
})->group('kit');

/*
 * Reexecutar não é erro: `kit:install` é idempotente por contrato, e num projeto já instalado
 * os arquivos não existem mais. Sem este caso, uma implementação que estourasse em arquivo
 * ausente passaria pelos outros dois.
 */
it('fica calado quando nao ha nada para apagar', function (): void {
    expect(VinculoDoSnyk::remover($this->base))->toBe([]);
})->group('kit');

it('o post-create-project-cmd passa o flag', function (): void {
    /** @var array{scripts: array<string, list<string>>} $composer */
    $composer = json_decode(File::get(base_path('composer.json')), true, flags: JSON_THROW_ON_ERROR);

    $passos = implode(' ', $composer['scripts']['post-create-project-cmd']);

    expect(str_contains($passos, 'kit:install'))->toBeTrue('O gancho deixou de chamar o kit:install.')
        ->and(str_contains($passos, '--create-project'))->toBeTrue(
            'Sem o flag, quem instala o kit herda o .snyk e o seguranca.yml do repositório do kit.'
        );
})->group('kit');

it('o composer setup NAO passa o flag, porque roda em clone do kit', function (): void {
    /** @var array{scripts: array<string, list<string>>} $composer */
    $composer = json_decode(File::get(base_path('composer.json')), true, flags: JSON_THROW_ON_ERROR);

    foreach ($composer['scripts'] as $nome => $passos) {
        if ($nome === 'post-create-project-cmd') {
            continue;
        }

        /*
         * `str_contains` e não `->not->toContain(...)`: `toContain(mixed ...$needles)` é
         * VARIÁDICO e não aceita mensagem. A primeira versão deste caso passava a explicação
         * como segundo argumento, ela virou um segundo needle que nunca existe, e o `not`
         * passava sempre — caso verde medindo nada. A mutação (flag no `setup`) foi o que
         * revelou: ela continuou verde.
         */
        expect(str_contains(implode(' ', array_filter($passos, 'is_string')), '--create-project'))
            ->toBeFalse(
                "O script `{$nome}` passa --create-project. Só o post-create-project-cmd pode: "
                .'qualquer outro roda DENTRO do repositório do kit e apagaria o .snyk e o '
                .'seguranca.yml da própria fonte.'
            );
    }
})->group('kit');
