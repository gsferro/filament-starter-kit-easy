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
    $texto = File::get(base_path($documento));

    expect($texto)
        ->not->toContain("MEDIA_DISK', 'public'")
        ->not->toContain('`public` por padrão')
        ->not->toContain('`public` by default');
})->with($documentos);

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

    expect(File::get(base_path('README.md')))
        ->toContain('getTemporaryUrl()')
        ->toContain('Quem tem o link entra, durante a validade da assinatura, sem sessão');
});

/** O caminho de recuperação da mídia legada precisa estar nos dois READMEs, ou ninguém roda. */
it('[CT-21b] os READMEs apontam o comando de migração da mídia legada', function (string $readme): void {
    expect(File::get(base_path($readme)))->toContain('kit:midia-privada');
})->with(['README.md', 'README.en.md']);
