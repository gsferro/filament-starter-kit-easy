<?php

/**
 * A metade de STRING do defeito que o `NumeroDoEnv` resolveu só para inteiro.
 *
 * O defeito é o mesmo, e está escrito no docblock de `App\Support\NumeroDoEnv`: o segundo
 * argumento do `env()` só vale para chave **ausente**. Com a chave presente e o valor vazio
 * (`KIT_ALGUMA_COISA=`, o que sobra quando alguém apaga o valor e esquece de apagar o `=`),
 * `env()` devolve string vazia — e string vazia não é ausência, então o default nunca entra.
 *
 * Para inteiro isso virou `NumeroDoEnv` e um caso de teste. Para string ficou sem dono, em sete
 * chaves, e três delas doem de verdade:
 *
 * - `KIT_TENANCY_SLUG` vazio quebra o prefixo de rota de toda a multi-organização;
 * - `KIT_ADMIN_EMAIL` vazio faz o `UsuarioAdminSeeder` criar o administrador da instalação com
 *   e-mail vazio — conta de acesso total sem credencial de entrada;
 * - `KIT_ADMIN_PASSWORD` vazio vira senha vazia.
 *
 * A correção é `?:` em vez da vírgula, porque para string a regra é UMA (vazio cai no default) e
 * uma regra só não justifica classe — diferente do inteiro, onde `positivo()` e
 * `diasOuDesligado()` precisam discordar sobre o zero.
 *
 * Este arquivo tem os dois lados que a rule `.ai/rules/specs.md` cobra de defeito de fronteira:
 * o comportamento de cada chave, e a **varredura** que impede a reintrodução do padrão. Sem a
 * varredura, a próxima chave nasce com a vírgula e ninguém percebe.
 */
$chavesDeTexto = [
    'KIT_REPOSITORY'           => ['kit.repository', 'https://github.com/gsferro/filament-starter-kit-easy.git'],
    'KIT_TENANCY_LABEL'        => ['kit.tenancy.label', 'Organização'],
    'KIT_TENANCY_LABEL_PLURAL' => ['kit.tenancy.label_plural', 'Organizações'],
    'KIT_TENANCY_SLUG'         => ['kit.tenancy.slug', 'organizacoes'],
    'KIT_ADMIN_NAME'           => ['kit.admin.name', 'Administrador'],
    'KIT_ADMIN_EMAIL'          => ['kit.admin.email', 'admin@example.com'],
    'KIT_ADMIN_PASSWORD'       => ['kit.admin.password', 'password'],
];

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

it('cai no valor de fabrica quando a chave de texto esta presente e vazia', function (string $chave, string $caminho, string $padrao): void {
    $config = kitConfigCom($chave, '');

    expect(data_get($config, str_replace('kit.', '', $caminho)))->toBe($padrao);
})->with(fn (): array => collect($chavesDeTexto)
    ->map(fn (array $par, string $chave): array => [$chave, $par[0], $par[1]])
    ->values()
    ->all());

it('respeita o valor escrito no env quando ele nao esta vazio', function (string $chave, string $caminho): void {
    $config = kitConfigCom($chave, 'valor-do-projeto');

    expect(data_get($config, str_replace('kit.', '', $caminho)))->toBe('valor-do-projeto');
})->with(fn (): array => collect($chavesDeTexto)
    ->map(fn (array $par, string $chave): array => [$chave, $par[0]])
    ->values()
    ->all());

/**
 * A varredura — o que impede a oitava chave de nascer com o defeito.
 *
 * `env('CHAVE', 'default')` com default de TEXTO é o padrão proibido. A isenção abaixo é
 * nomeada de propósito: lista de isenção com motivo escrito é revisável, regex frouxo não.
 */
it('nao reintroduz env com default de texto no config do kit', function (): void {
    $isentas = [
        // Aqui vazio NÃO é acidente: o bloco `convites` documenta que lista vazia DESLIGA os
        // lembretes, e `KIT_CONVITE_LEMBRETES_DIAS=` é justamente como se escreve isso. Trocar
        // por `?:` tiraria a única forma de desligar a feature pelo .env.
        'KIT_CONVITE_LEMBRETES_DIAS',
    ];

    preg_match_all(
        "/env\(\s*'([A-Z0-9_]+)'\s*,\s*'/",
        (string) file_get_contents(base_path('config/kit.php')),
        $achados,
    );

    expect(array_diff($achados[1], $isentas))->toBe([]);
});
