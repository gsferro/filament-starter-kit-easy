<?php

use Illuminate\Support\Facades\File;

/**
 * O `filament/blueprint` é ferramenta de QUEM DESENVOLVE o kit, e não pode viajar no pacote.
 *
 * ## Por que isto é guarda automática e não um aviso no README
 *
 * Se `filament/blueprint` entrar no `composer.json` ou no `composer.lock` publicados, **o kit
 * fica não-instalável para todo mundo**. Não é "instala e depois limpa": `composer
 * create-project` instala as dependências de dev por default — o próprio `--help` diz
 * "Enables installation of require-dev packages (enabled by default)" — e faz isso ANTES de
 * rodar o `post-create-project-cmd`. Quem não tem licença leva 403 em
 * `packages.filamentphp.com` durante a resolução, e o gancho que limparia nunca chega a rodar.
 *
 * Por isso não existe "remover na instalação" para este pacote, ao contrário do vínculo com o
 * Snyk (ver `App\Support\VinculoDoSnyk`): lá o arquivo é inerte e sai no gancho; aqui a
 * dependência é resolvida antes de qualquer gancho existir. A única defesa é o pacote nunca
 * estar no estado commitado.
 *
 * ## Como conviver com isso
 *
 * `composer bp:on` liga (repositório + require --dev), `composer bp:off` desliga. O estado
 * commitado é sempre "desligado".
 *
 * **Com o Blueprint ligado localmente, estes casos ficam VERMELHOS — e é o desenho.** Eles são
 * o lembrete de rodar `composer bp:off` antes de commitar. Um caso verde durante o uso não
 * lembraria nada, e o CI é quem tem a palavra final: o que ele vê é o estado publicado.
 */
it('nao tem filament/blueprint no composer.json', function (): void {
    /** @var array<string, mixed> $composer */
    $composer = json_decode(File::get(base_path('composer.json')), true, flags: JSON_THROW_ON_ERROR);

    /** @var array<string, string> $require */
    $require = $composer['require'] ?? [];
    /** @var array<string, string> $dev */
    $dev = $composer['require-dev'] ?? [];

    expect(array_key_exists('filament/blueprint', $require + $dev))->toBeFalse(
        'Rode `composer bp:off`. Com o blueprint no composer.json publicado, o create-project '
        .'de quem não tem licença falha na RESOLUÇÃO das dependências — antes de qualquer gancho.'
    );
})->group('kit');

/*
 * A checagem é na chave `repositories`, não no texto bruto do arquivo: o script `bp:on` contém
 * a URL do repositório privado por definição, e a primeira versão deste caso reprovava por causa
 * dele. Um oráculo que acusa a própria ferramenta de desligar é um oráculo errado.
 */
it('nao tem o repositorio privado do filament declarado', function (): void {
    /** @var array<string, mixed> $composer */
    $composer = json_decode(File::get(base_path('composer.json')), true, flags: JSON_THROW_ON_ERROR);

    /** @var array<string, array{type?: string, url?: string}> $repositorios */
    $repositorios = $composer['repositories'] ?? [];

    $privados = array_filter(
        $repositorios,
        static fn (mixed $repo): bool => is_array($repo)
            && str_contains((string) ($repo['url'] ?? ''), 'packages.filamentphp.com'),
    );

    expect($privados)->toBe([],
        'Rode `composer bp:off`. O repositório privado declarado no composer.json publicado faz o '
        .'Composer de terceiros pedir credencial que eles não têm.'
    );
})->group('kit');

/*
 * O lock é o que o `create-project` realmente instala, e ele pode conter o pacote mesmo depois
 * de alguém editar o composer.json à mão. Sem este caso, um `bp:off` incompleto passaria.
 */
it('nao tem blueprint nem o repositorio privado no composer.lock', function (): void {
    $lock = File::get(base_path('composer.lock'));

    expect(str_contains($lock, 'filament/blueprint'))->toBeFalse(
        'Rode `composer bp:off`. O composer.lock é o que o create-project instala.'
    )->and(str_contains($lock, 'packages.filamentphp.com'))->toBeFalse(
        'O lock aponta para o repositório privado. Rode `composer bp:off` e confira o diff.'
    );
})->group('kit');

/*
 * A credencial do Blueprint vive em `auth.json`. O recomendado é o GLOBAL
 * (`composer config --global --auth ...`), que nem existe dentro do projeto — mas se alguém
 * usar o local, o .gitignore é a última linha de defesa e ela não pode sair por descuido.
 */
it('mantem o auth.json fora do versionamento', function (): void {
    expect(str_contains(File::get(base_path('.gitignore')), 'auth.json'))->toBeTrue(
        'O auth.json guarda a credencial do repositório privado do Filament. Fora do .gitignore, '
        .'um `git add -A` publica a licença.'
    );
})->group('kit');

it('o auth.json nao esta rastreado pelo git', function (): void {
    exec('git ls-files --error-unmatch auth.json 2>&1', $saida, $codigo);

    expect($codigo)->not->toBe(0,
        'auth.json ESTÁ rastreado. Rode `git rm --cached auth.json` e gere um token novo: '
        .'o antigo está no histórico.'
    );
})->group('kit');

/*
 * O `composer.lock` em sincronia com o `composer.json` — e é o `bp:on`/`bp:off` quem dessincroniza.
 *
 * A primeira versão do `bp:off` fazia `composer remove` (que grava o `content-hash`) e SÓ DEPOIS
 * `config --unset repositories.filament`, que edita o json sem tocar o lock. Resultado: lock com hash
 * defasado, `composer validate` reprovando, e um `composer install` em CI avisando que o lock está
 * velho. Medido ao desligar o Blueprint depois da auditoria de aderência. A ordem foi invertida; este
 * caso impede a volta.
 *
 * `composer validate` e não comparar hash à mão: o algoritmo do hash é do Composer, e a flag
 * `--no-check-all` deixa só a checagem de lock (sem alertar sobre versões soltas).
 */
it('mantem o composer.lock em sincronia com o composer.json', function (): void {
    exec('composer validate --no-check-publish --no-check-all --no-interaction 2>&1', $saida, $codigo);

    expect($codigo)->toBe(0,
        "O lock está defasado do json — normalmente é o `bp:on`/`bp:off` fora de ordem. Rode `composer update --lock`.\n".implode("\n", $saida)
    );
})->group('kit');
