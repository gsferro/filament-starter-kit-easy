<?php

use App\Support\Papeis;

/**
 * Como um papel é exibido — e por que a chave não muda.
 *
 * O nome do papel é identificador: vai em `assignRole()`, `hasRole()`, seeders e
 * policies. Renomeá-lo para ficar bonito quebraria tudo isso de uma vez. Então a
 * chave fica `master_global` e só a EXIBIÇÃO vira "Master Global".
 */
it('exibe o papel em title case sem tocar na chave', function (string $chave, string $rotulo): void {
    expect(Papeis::rotulo($chave))->toBe($rotulo);
})->with([
    'master_global'     => ['master_global', 'Master Global'],
    'admin_organizacao' => ['admin_organizacao', 'Admin Organizacao'],
    'panel_user'        => ['panel_user', 'Panel User'],
    'admin'             => ['admin', 'Admin'],
])->group('kit');

it('não inventa rótulo para papel ausente', function (?string $chave): void {
    expect(Papeis::rotulo($chave))->toBe('—');
})->with(['nulo' => null, 'vazio' => ''])->group('kit');

/**
 * `painel` é a coluna que DÁ o acesso (`User::canAccessPanel()` a lê). Exibi-la
 * como "app" fazia parecer categoria; o rótulo precisa dizer o que ela é.
 *
 * E vazio não é coringa: papel sem painel não abre painel nenhum — o rótulo diz
 * isso com todas as letras, em vez de um traço que o leitor interpreta.
 */
it('diz por extenso qual painel o papel abre', function (?string $painel, string $esperado): void {
    expect(Papeis::rotuloDoPainel($painel))->toBe($esperado);
})->with([
    'app'        => ['app', 'Acesso ao painel /app'],
    'admin'      => ['admin', 'Acesso ao painel /admin'],
    'infra'      => ['infra', 'Acesso ao painel /infra'],
    'sem painel' => [null, 'Não abre painel'],
])->group('kit');
