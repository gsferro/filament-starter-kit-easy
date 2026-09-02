<?php

use App\Support\TetoDeUpload;

/**
 * O teto de upload documentado onde quem instala o kit vai olhar.
 *
 * IDs de CT em `wikis/specs/feat/upload-limite-e-tipos/upload-limite-e-tipos/04-casos-de-teste.md`.
 *
 * RQ-06 é uma cláusula de documentação: "documentado para caso o usuário queria
 * aumentar ou diminuir de forma facil ao instalar o nosso kit". Documentação
 * prometida e não escrita é indistinguível de documentação escrita — para todo
 * mundo menos o leitor. Daí um arquivo de casos, no mesmo padrão de
 * `ConfiguracoesDoKitDocumentacaoTest` e `AnexosPrivadosDocumentacaoTest`.
 *
 * O `README.en.md` é um caso e não uma nota porque é o que costuma ficar para
 * trás quando alguém mexe só no português.
 */

/**
 * CT-18 — cada README documenta a chave, a unidade e a escada de tetos.
 *
 * Nenhuma das asserções é decorativa: cada uma é uma coisa que falta para
 * "aumentar ou diminuir de forma facil" ser verdade.
 *
 * - o **nome da chave**, senão não há o que editar;
 * - a **unidade** dela, senão alguém escreve 10240 achando que são MB;
 * - os **dois arquivos de infraestrutura** que precisam mudar junto quando o
 *   número sobe muito, senão o teto novo não vale e o erro aparece como falha de
 *   rede;
 * - o aviso do **`upload_max_filesize=2M`** de fábrica do PHP, que é o teto real
 *   de quem não usa o Docker do kit — a armadilha que faz a pessoa culpar o kit;
 * - a seção que explica **por que SVG é recusado**, que é a metade da feature que
 *   não é sobre tamanho.
 */
it('[CT-18] documenta a chave do teto de upload nos dois readmes', function (string $arquivo, string $unidade, string $svg): void {
    // Reancorado: o conteúdo migrou para o site (GitHub Pages). O oráculo continua
    // sendo 'a documentação deste idioma afirma isto', não 'o README afirma isto'.
    $texto = documentacaoDoKit(str_contains($arquivo, 'en.md') ? 'en' : 'pt');

    expect($texto)->toContain('KIT_UPLOAD_MAXIMO_MB')
        ->and($texto)->toContain($unidade)
        ->and($texto)->toContain('docker/php/uploads.ini')
        ->and($texto)->toContain('docker/nginx/nginx.conf')
        ->and($texto)->toContain('upload_max_filesize=2M')
        ->and($texto)->toContain($svg);
})->with([
    'português' => ['README.md', 'Em MEGABYTES', 'Por que SVG é recusado'],
    'inglês'    => ['README.en.md', 'In MEGABYTES', 'Why SVG is refused'],
])->group('kit');

/**
 * CT-19 — a chave aparece no `.env.example`, comentada.
 *
 * É onde quem instala o kit olha primeiro, e o kit já comenta as chaves de
 * default (`KIT_TABELA_*`) em vez de omiti-las: chave ausente do exemplo é chave
 * que ninguém descobre.
 *
 * Comentada, e não ativa, de propósito — e a asserção é sobre a linha COM o `#`.
 * Uma chave ativa no exemplo vira uma chave ativa em todo `.env` copiado, e aí o
 * default do `config/kit.php` deixa de ser o default de fato.
 */
it('[CT-19] oferece a chave comentada no .env.example', function (): void {
    $exemplo = (string) file_get_contents(base_path('.env.example'));

    expect($exemplo)->toContain('# KIT_UPLOAD_MAXIMO_MB=10')
        ->and($exemplo)->toContain('MEGABYTES');
})->group('kit');

/**
 * CT-20 — o número que os READMEs prometem é o número que o kit entrega.
 *
 * Asserção cruzada, e é o único caso que pega a classe de defeito mais chata da
 * documentação: alguém muda o default no `config/kit.php` e a prosa continua
 * dizendo 10 MB. Os dois textos ficam verdes em CT-18 (a chave está citada), o
 * kit entrega outro valor, e ninguém descobre até um cliente reclamar.
 *
 * A frase asserida é montada a partir de `TetoDeUpload::emMb()`, então ela
 * acompanha a config: mudar o default sem mudar os READMEs reprova aqui.
 */
it('[CT-20] promete nos readmes o mesmo teto que o kit entrega', function (string $arquivo, string $frase): void {
    expect(documentacaoDoKit(str_contains($arquivo, 'en.md') ? 'en' : 'pt'))
        ->toContain(str_replace('{mb}', (string) TetoDeUpload::emMb(), $frase));
})->with([
    'português — o título da seção' => ['README.md', 'Teto de upload: {mb} MB, e onde mudar'],
    'português — o valor da chave'  => ['README.md', 'KIT_UPLOAD_MAXIMO_MB={mb}'],
    'inglês — o título da seção'    => ['README.en.md', 'Upload ceiling: {mb} MB, and where to change it'],
    'inglês — o valor da chave'     => ['README.en.md', 'KIT_UPLOAD_MAXIMO_MB={mb}'],
])->group('kit');
