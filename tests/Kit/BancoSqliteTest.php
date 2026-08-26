<?php

use App\Support\BancoSqlite;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

/**
 * `kit:install --force` recria o SQLite de verdade — também no Windows.
 *
 * Tudo em diretório TEMPORÁRIO e numa conexão de nome próprio (`sqlite_teste`), nunca no banco da
 * suíte: o código sob teste APAGA arquivo, e apontá-lo para o `:memory:` da suíte ou para o projeto
 * destruiria o arnês de quem roda.
 *
 * O caso decisivo é o segundo: o próprio processo abre o arquivo (como o boot do kit faz ao ler os
 * settings) e depois pede a recriação. Antes da correção, no Windows, o arquivo sobrevivia em
 * silêncio e o comando seguia migrando o banco velho. Ver o docblock de `App\Support\BancoSqlite`.
 */
beforeEach(function (): void {
    $this->dir = sys_get_temp_dir().'/kit-sqlite-'.bin2hex(random_bytes(4));
    File::ensureDirectoryExists($this->dir);
    $this->caminho = $this->dir.'/banco.sqlite';

    config(['database.connections.sqlite_teste' => [
        'driver'   => 'sqlite',
        'database' => $this->caminho,
    ]]);
});

afterEach(function (): void {
    DB::disconnect('sqlite_teste');
    DB::purge('sqlite_teste');
    File::deleteDirectory($this->dir);
});

it('cria o arquivo quando ele nao existe, e diz que criou', function (): void {
    expect(BancoSqlite::criarSeFaltar($this->caminho))->toBeTrue()
        ->and(File::exists($this->caminho))->toBeTrue()
        ->and(BancoSqlite::criarSeFaltar($this->caminho))->toBeFalse('Existente: não é "criado agora".');
})->group('kit');

/**
 * O processo abre o banco (uma tabela-marca dentro), e então recria. O marcador tem de sumir — e a
 * conexão tem de ter sido derrubada, senão no Windows o arquivo nem apagaria.
 */
it('recria o banco mesmo com a conexao do proprio processo aberta', function (): void {
    File::put($this->caminho, '');
    DB::connection('sqlite_teste')->statement('create table marca (id integer)');
    expect(array_key_exists('sqlite_teste', DB::getConnections()))->toBeTrue('Pré-condição: a conexão está aberta.');

    BancoSqlite::recriar($this->caminho, 'sqlite_teste');

    expect(File::exists($this->caminho))->toBeTrue('O arquivo é recriado, não só apagado.')
        ->and(File::size($this->caminho))->toBe(0, 'Banco novo: vazio. Se o marcador sobreviveu, o delete falhou em silêncio.')
        ->and(array_key_exists('sqlite_teste', DB::getConnections()))->toBeFalse(
            'A conexão tem de ser purgada ANTES do delete — é ela quem segura o arquivo no Windows.'
        );
})->group('kit');

/**
 * Outro processo segurando o arquivo: no Windows o delete falha, e a resposta certa é a exceção com
 * a causa — não seguir adiante. No Linux o unlink funciona com handle aberto, então o caso só prova
 * algo no Windows; fora dele fica marcado como pulado, não como verde.
 */
it('falha alto quando outro handle segura o arquivo', function (): void {
    File::put($this->caminho, 'x');
    $handle = fopen($this->caminho, 'r');

    try {
        expect(fn () => BancoSqlite::recriar($this->caminho, 'sqlite_teste'))
            ->toThrow(RuntimeException::class, 'mantém aberto');
    } finally {
        if (is_resource($handle)) {
            fclose($handle);
        }
    }
})->skip(PHP_OS_FAMILY !== 'Windows', 'No Linux/macOS o unlink apaga arquivo aberto; o caso só discrimina no Windows.')->group('kit');
