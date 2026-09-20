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
 *
 * O arquivo inteiro é de documentação, então a sentinela é um `beforeEach` — mesma forma de
 * `RedeDeDocumentacaoTest` e `SiteDeDocumentacaoTest`. Fora da árvore do kit não há o que ler: o
 * `kit:update` não entrega `docs/` (export-ignore) nem o README, e a suíte que a atualização
 * acabou de instalar acusaria o projeto por documentação que é do kit.
 */
beforeEach(function (): void {
    if (! naArvoreDoKit()) {
        $this->markTestSkipped('O kit:update não entrega o site do kit: o diretório do site é export-ignore e não existe no projeto instalado.');
    }
});

/**
 * CT-33 — a seção existe e cada mecanismo é citado DENTRO dela.
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
        // Reancorado DUAS vezes. Primeiro a seção migrou do README para o site e o h2
        // virou o h1 da página. Depois a migração para o Starlight tirou o h1 do corpo,
        // porque lá o `title` do front-matter É o h1 renderizado — mantê-lo no corpo
        // produzia o título duas vezes na tela. A âncora segue o título para onde ele foi.
        //
        // A co-localização — cada termo DENTRO da seção — continua sendo o que este caso
        // protege: a varredura vai da âncora até o próximo `##`, e nesta página não há
        // nenhum, então a seção é a página inteira, como já era antes.
        'docs/pt/autenticacao/estados-de-usuario.md',
        'title: "Usuário ativo, inativo e excluído"',
        ['Reativar', 'Lixeira', 'Restaurar', 'senha', 'contato com o administrador', 'Desativar:User'],
    ],
    'en' => [
        'docs/en/autenticacao/estados-de-usuario.md',
        'title: "Active, inactive and deleted users"',
        ['Reactivate', 'Recycle bin', 'Restore', 'password', 'contact the administrator', 'Desativar:User'],
    ],
])->group('kit');
