<?php

use Illuminate\Support\Facades\File;

/**
 * O README explica os três estados do usuário — em PT e em EN.
 *
 * Como os irmãos `*DocumentacaoTest`, este caso NÃO tem poder de falsificação sobre o código:
 * fica verde com a feature quebrada e vermelho com a feature certa e o texto velho. Está aqui
 * porque a cláusula "deixe isso muito bem documentado no README" é do requisito (RQ-12), e uma
 * cláusula sem caso é omissão que ninguém percebe.
 *
 * O oráculo é recortado à SEÇÃO nova, e não ao arquivo inteiro: "senha", "Lixeira" e "Restaurar"
 * já apareciam no README antes desta feature (login social, Revive), e uma busca no arquivo
 * inteiro passaria sem a seção existir — achado da revisão adversarial do `04`.
 *
 * Wiki: `wikis/specs/feat/status-e-exclusao-logica-de-usuario/`, CT-33.
 */
it('[CT-33] a documentação tem a seção de usuário ativo, inativo e excluído e cita cada mecanismo', function (
    string $arquivo,
    string $titulo,
    array $termos,
): void {
    $texto = File::get(base_path($arquivo));

    $inicio = mb_strpos($texto, $titulo);

    expect($inicio)->not->toBeFalse("{$arquivo} não tem a seção \"{$titulo}\"");

    // Da seção até o próximo título de segundo nível — é dentro dela que cada termo tem de estar.
    $fim   = mb_strpos($texto, "\n## ", (int) $inicio + mb_strlen($titulo));
    $secao = mb_substr($texto, (int) $inicio, $fim === false ? null : $fim - (int) $inicio);

    foreach ($termos as $termo) {
        expect($secao)->toContain($termo);
    }
})->with([
    'pt' => [
        // Reancorado: a seção migrou para o site (GitHub Pages) e o h2 virou o h1 da
        // página. A co-localização — cada termo DENTRO da seção — é o que este caso
        // protege, e ela é preservada apontando para a página em vez do README.
        'docs/pt/autenticacao/estados-de-usuario.md',
        '# Usuário ativo, inativo e excluído',
        ['Reativar', 'Lixeira', 'Restaurar', 'senha', 'contato com o administrador', 'Desativar:User'],
    ],
    'en' => [
        'docs/en/autenticacao/estados-de-usuario.md',
        '# Active, inactive and deleted users',
        ['Reactivate', 'Recycle bin', 'Restore', 'password', 'contact the administrator', 'Desativar:User'],
    ],
])->group('kit');
