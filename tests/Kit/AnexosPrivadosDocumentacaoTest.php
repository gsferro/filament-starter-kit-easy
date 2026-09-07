<?php

use Illuminate\Support\Facades\File;

/**
 * A documentação descreve o comportamento real da camada de mídia.
 *
 * **Estes dois casos NÃO têm poder de falsificação sobre a correção.** Eles ficam verdes
 * com o código inteiro vazando, desde que os textos estejam certos — e ficam vermelhos
 * com o código certo e o texto velho, que é exatamente o que aconteceu: seis documentos
 * afirmavam que anexo do kit vive em disco público, e três deles diziam que o
 * `->visibility('private')` do campo era o que protegia.
 *
 * Está declarado aqui porque item de checklist que não protege nada é pior que lacuna.
 *
 * ## Por que os casos que leem a documentação são pulados fora da árvore do kit
 *
 * `documentacaoDoKit()` concatena o README do idioma com `docs/{idioma}/**`, e o `kit:update`
 * não entrega nenhum dos dois: `docs/` é `export-ignore` (nem existe no projeto instalado) e o
 * README passa a ser do projeto no `create-project` — a lista `KitUpdate::CAMINHOS_DO_KIT` só
 * traz `wikis/README.md`. Sem a sentinela, uma atualização que mexesse em texto documentado
 * entregava o código novo, o teste novo e nenhum dos dois documentos: a instalação ficava
 * vermelha acusando o projeto por algo que é do kit. Medido numa v0.30.1 → v0.32.0.
 *
 * A granularidade é por CASO, e não o arquivo inteiro: CT-20b lê `wikis/pacotes-ranking.md`, que
 * o `kit:update` ENTREGA, e continua rodando em toda instalação.
 */
$documentos = [
    'README.md',
    'README.en.md',
    'wikis/arquitetura.md',
    'wikis/receitas.md',
    '.ai/rules/models.md',
];

/**
 * O oráculo é de AFIRMAÇÃO, não de ocorrência da palavra: "com `MEDIA_DISK=public` o
 * caminho é alcançável sem sessão" é a frase que explica POR QUE o default mudou, e
 * proibir a palavra `public` mataria a explicação junto.
 *
 * O que não pode voltar é o kit se descrevendo como público: o default anunciado como
 * `public`, ou a promessa de que a visibilidade do campo protege a URL.
 */
it('[CT-20] nenhum documento anuncia disco público como default da mídia do kit', function (string $documento): void {
    // Reancorado pela migração para o site: ver documentacaoDoKit() em tests/Pest.php.
    $texto = documentacaoDoKit(str_contains($documento, 'en.md') ? 'en' : 'pt');

    expect($texto)
        ->not->toContain("MEDIA_DISK', 'public'")
        ->not->toContain('`public` por padrão')
        ->not->toContain('`public` by default');
})->with($documentos)->skip(fn (): bool => ! naArvoreDoKit(), 'O kit:update não entrega o README (que passa a ser do projeto) nem o site (export-ignore).');

/**
 * `wikis/pacotes-ranking.md` fica FORA do dataset acima de propósito: ele cita o default
 * do PACOTE numa análise de adoção, e proibir o literal ali apagaria a comparação. O que
 * ele precisa é da errata dizendo que o kit não publica mais esse default.
 */
it('[CT-20b] o ranking de pacotes carrega a errata do disco de mídia', function (): void {
    expect(File::get(base_path('wikis/pacotes-ranking.md')))
        ->toContain("env('MEDIA_DISK', 'local')")
        ->toContain('default DO PACOTE');
});

/**
 * O teste do ADR-03: aceitar um limite só é honesto se ele estiver escrito.
 *
 * `getTemporaryUrl()` assina, não autoriza — quem recebe o link entra sem sessão durante
 * a validade. Apagar essa frase por parecer alarmista é o mutante que este caso mata.
 */
it('[CT-21] a documentação declara o limite aceito do link assinado', function (): void {
    expect(File::get(base_path('wikis/arquitetura.md')))
        ->toContain('getTemporaryUrl()')
        ->toContain('quem tem o link entra, sem sessão, durante a validade');

    /*
     * A metade de cima roda em toda instalação: `wikis/arquitetura.md` está em
     * `KitUpdate::CAMINHOS_DO_KIT`. Só a metade de baixo depende do README e do site, e é por
     * isso que a sentinela vive aqui no meio do corpo em vez de num `->skip()` do caso todo —
     * guardar o caso inteiro apagaria a asserção sobre um arquivo que É entregue.
     */
    if (! naArvoreDoKit()) {
        $this->markTestSkipped('O kit:update não entrega o README (que passa a ser do projeto) nem o site (export-ignore).');
    }

    expect(documentacaoDoKit('pt'))
        ->toContain('getTemporaryUrl()')
        ->toContain('Quem tem o link entra, durante a validade da assinatura, sem sessão');
});

/** O caminho de recuperação da mídia legada precisa estar nos dois READMEs, ou ninguém roda. */
it('[CT-21b] os READMEs apontam o comando de migração da mídia legada', function (string $readme): void {
    expect(documentacaoDoKit(str_contains($readme, 'en.md') ? 'en' : 'pt'))->toContain('kit:midia-privada');
})->with(['README.md', 'README.en.md'])->skip(fn (): bool => ! naArvoreDoKit(), 'O kit:update não entrega o README (que passa a ser do projeto) nem o site (export-ignore).');
