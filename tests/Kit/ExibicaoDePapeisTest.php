<?php

use App\Support\Papeis;

/**
 * Como um papel é exibido — e por que a chave não muda.
 *
 * O nome do papel é identificador: vai em `assignRole()`, `hasRole()`, seeders e
 * policies. Trocá-lo para ficar bonito quebraria tudo isso de uma vez — e quando a
 * troca é mesmo necessária, ela vem com migration (ver
 * `rename_admin_organizacao_role`), porque o papel também vive no banco de quem já
 * instalou.
 *
 * O caminho barato é o outro: a chave fica e só a EXIBIÇÃO muda.
 */
it('exibe o papel em title case sem tocar na chave', function (string $chave, string $rotulo): void {
    expect(Papeis::rotulo($chave))->toBe($rotulo);
})->with([
    'admin' => ['admin', 'Admin'],
    'infra' => ['infra', 'Infra'],
    // Papel de fora do kit: deriva da chave, sem entrada em lugar nenhum.
    'papel do usuário' => ['gerente_de_contas', 'Gerente De Contas'],
])->group('kit');

/**
 * `Str::headline('panel_user')` devolve "Panel User" — inglês, e sem dizer o que o
 * papel faz. Quem o tem opera o painel de negócio.
 */
it('traduz o papel cujo Title Case sairia em inglês', function (): void {
    expect(Papeis::rotulo('panel_user'))->toBe('Painel App');
})->group('kit');

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

/**
 * Os três papéis do kit cujo Title Case sairia em inglês ou sem acento.
 *
 * `panel_user` viraria "Panel User"; `admin_app`, "Admin App"; `master_global`,
 * "Master Global". Nenhum diz o que o papel faz, e dois deles nem são português.
 */
it('traduz os papéis do kit em vez de derivar da chave', function (string $chave, string $rotulo): void {
    expect(Papeis::rotulo($chave))->toBe($rotulo);
})->with([
    ['panel_user', 'Painel App'],
    ['admin_app', 'Administrador App'],
    ['master_global', 'Administrador Geral'],
])->group('kit');
