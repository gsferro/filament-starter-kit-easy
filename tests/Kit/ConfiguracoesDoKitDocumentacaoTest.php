<?php

/**
 * A tela de configurações documentada nos DOIS READMEs.
 *
 * IDs de CT em `wikis/specs/feat/settings-do-kit/settings-do-kit/04-casos-de-teste.md`.
 *
 * O kit já tem este padrão (`AnexosPrivadosDocumentacaoTest`), e ele existe porque
 * documentação prometida e não escrita é indistinguível de documentação escrita —
 * para todo mundo menos o leitor. RQ-19 pede os dois idiomas, no plural, e o
 * `README.en.md` é o que costuma ficar para trás.
 *
 * ## Os casos de documentação são pulados fora da árvore do kit
 *
 * O `kit:update` não entrega o README nem `docs/` (a lista `KitUpdate::CAMINHOS_DO_KIT` só traz
 * `wikis/README.md`), então numa instalação real estes casos leem o texto ANTIGO — ou, no caso do
 * site, nada. Foi exatamente o que aconteceu numa v0.30.1 → v0.32.0: CT-50 e o caso da arte do
 * login ficaram vermelhos nos dois idiomas, acusando o projeto por documentação que é do kit.
 *
 * A granularidade é por CASO. O caso do trait continua rodando em toda instalação: ele lê
 * `app/Providers/Concerns/ConfiguraFilamentGlobal.php`, e `app/Providers` É entregue.
 */

/**
 * CT-31 — cada README cita a tela e a regra de precedência.
 *
 * A asserção de PRESENÇA roda sobre o texto cru: citar é o que se quer aqui.
 *
 * As quatro referências não são decoração. A URL é como alguém acha a tela; a
 * regra do banco vencendo o `.env` é a decisão que a wiki chama de mais
 * importante da feature, e quem lê o README sem ela vai editar o `.env` e não
 * entender por que nada muda; a permissão é o que explica um 403; e o aviso de
 * que isto não é o settings de uma organização é literalmente uma cláusula do
 * requisito (RQ-18).
 */
it('[CT-50] documenta a tela de configuracoes nos dois readmes, na URL nova', function (string $arquivo, string $urlDaTela, string $precedencia, string $permissao, string $naoEhTenant): void {
    // Reancorado pela migração para o site: `documentacaoDoKit()` em tests/Pest.php.
    $texto = documentacaoDoKit(str_contains($arquivo, 'en.md') ? 'en' : 'pt');

    expect($texto)->toContain($urlDaTela)
        ->and($texto)->toContain($precedencia)
        ->and($texto)->toContain($permissao)
        ->and($texto)->toContain($naoEhTenant)
        // A doc não pode instruir a URL antiga: ela responde 301, mas mandar alguém para lá é
        // documentar um endereço que a tela não usa mais. Citar continua permitido (CHANGELOG,
        // wiki) — a asserção é sobre o texto que o site publica.
        ->and($texto)->not->toContain('/admin/configuracoes-do-kit');
})->with([
    'português' => [
        'README.md',
        '/admin/configuracoes-da-aplicacao',
        'O banco vence em tempo de execução',
        'View:ConfiguracoesDoKit',
        '/admin/organizacoes',
    ],
    'inglês' => [
        'README.en.md',
        '/admin/configuracoes-da-aplicacao',
        'The database wins at runtime',
        'View:ConfiguracoesDoKit',
        '/admin/organizacoes',
    ],
])->skip(fn (): bool => ! naArvoreDoKit(), 'O kit:update não entrega o README (que passa a ser do projeto) nem o site (export-ignore).')->group('kit');

/**
 * CT-32 — o TODO de virada para settings não sobrevive, e a densidade é explicada.
 *
 * Duas metades, e a segunda é a que a rodada 2 da revisão adversarial cobrou: uma
 * asserção só de AUSÊNCIA passa num README que **apaga** a linha da densidade em
 * silêncio, como se ela tivesse sido entregue. Ausência não distingue apagar de
 * explicar; presença distingue.
 *
 * A asserção de ausência precisa filtrar as linhas de citação, porque o texto novo
 * **cita** o TODO antigo para explicar o que mudou — é a armadilha que
 * `.ai/rules/testes.md` documenta em três casos anteriores deste repositório.
 */
it('substitui o TODO de settings nos dois readmes, explicando a densidade', function (string $arquivo, string $promessaAntiga, string $explicacaoDaDensidade): void {
    expect(readmeSemCitacao($arquivo))->not->toContain($promessaAntiga);

    expect(documentacaoDoKit(str_contains($arquivo, 'en.md') ? 'en' : 'pt'))->toContain($explicacaoDaDensidade);
})->with([
    'português' => ['README.md', 'TODO:** transformar esses defaults', 'Densidade de tabela não existe no Filament 5'],
    'inglês'    => ['README.en.md', 'TODO:** turn these defaults', 'Table density does not exist in Filament 5'],
])->skip(fn (): bool => ! naArvoreDoKit(), 'O kit:update não entrega o README (que passa a ser do projeto) nem o site (export-ignore).')->group('kit');

/**
 * O TODO do código também foi fechado.
 *
 * `ConfiguraFilamentGlobal` era o dono da promessa, e o requisito cita justamente
 * esses TODOs ("agora é o momento da implementação").
 *
 * ⚠️ A asserção é sobre o TEXTO DA PROMESSA, não sobre a palavra `TODO`, e a
 * primeira versão deste caso reprovou por isso: em português, `TODOS` e `TODA`
 * contêm `TODO` como substring, e o trait usa as duas em três lugares ("vale para
 * TODOS", "em TODA tela"). É a mesma família de armadilha que
 * `.ai/rules/testes.md` documenta para asserção de ausência — só que aqui o falso
 * positivo vem do idioma, não do comentário.
 *
 * As duas asserções de presença são o que distingue "fechou explicando" de
 * "apagou": sem elas, remover o docblock inteiro passaria.
 */
it('fecha o TODO de settings no trait de configuracao global do filament', function (): void {
    $trait = (string) file_get_contents(base_path('app/Providers/Concerns/ConfiguraFilamentGlobal.php'));

    expect($trait)->not->toContain('transformar estes defaults')
        ->and($trait)->toContain('/admin/configuracoes-da-aplicacao')
        ->and($trait)->toContain('densidade de tabela');
})->group('kit');

/**
 * CT-11 — cada README diz que a arte usa o nome, e como trocá-la.
 *
 * Wiki `feat/arte-do-login-com-nome-da-aplicacao`, RQ-04. Mora aqui, e não em
 * arquivo próprio, porque `readmeSemCitacao()` já vive neste arquivo: um clone
 * com outro nome é o que `.ai/rules/testes.md` proíbe, e mover o helper para
 * `tests/Pest.php` só para ganhar um arquivo novo não paga.
 *
 * A ausência precisa do FILTRO de citação: o README pode perfeitamente citar o
 * caminho antigo num bloco `>` explicando o que mudou, e citar não é instruir.
 * Antes desta feature o caminho aparecia três vezes por README, sempre como
 * instrução ao leitor — "troque a arte em `public/images/auth/login.svg`". Esse
 * arquivo não existe mais, e é essa mentira que o caso falsifica.
 *
 * A linha do inglês é a que importa: o `README.en.md` é o que historicamente
 * fica para trás no kit.
 */
it('documenta nos dois readmes que a arte do login usa o nome da aplicacao', function (string $arquivo, string $frase): void {
    $texto = documentacaoDoKit(str_contains($arquivo, 'en.md') ? 'en' : 'pt');

    expect($texto)->toContain('APP_NAME')
        ->and($texto)->toContain($frase)
        ->and($texto)->toContain('/admin/configuracoes-da-aplicacao')
        ->and(readmeSemCitacao($arquivo))->not->toContain('public/images/auth/login.svg');
})->with([
    'README.md'    => ['README.md', 'mostra o nome da aplicação'],
    'README.en.md' => ['README.en.md', 'shows the application name'],
])->skip(fn (): bool => ! naArvoreDoKit(), 'O kit:update não entrega o README (que passa a ser do projeto) nem o site (export-ignore).')->group('kit');

it('[CT-02] remove o nome antigo da aba de todos os docs e readmes', function (): void {
    $arquivos = [base_path('README.md'), base_path('README.en.md'), base_path('.env.example'), base_path('config/kit.php')];

    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator(base_path('docs'), FilesystemIterator::SKIP_DOTS)) as $arquivo) {
        if ($arquivo->isFile() && $arquivo->getExtension() === 'md') {
            $arquivos[] = $arquivo->getPathname();
        }
    }

    foreach ($arquivos as $arquivo) {
        $texto = (string) file_get_contents($arquivo);

        // Sem distinção de caixa ("Aba Kit" no alt de uma imagem escapou da primeira varredura) e com
        // fronteira de palavra, para "kit table" não casar com "Kit tab".
        expect(preg_match('/\baba \*{0,2}kit\b|\bkit\*{0,2} tab\b|\| \*\*kit\*\* \|/iu', $texto))->toBe(0, $arquivo);
    }
})->skip(fn (): bool => ! naArvoreDoKit(), 'A documentação só está disponível na árvore do kit.')->group('kit');

it('[CT-02] preserva por arquivo as mencoes a aba agora chamada Aplicacao', function (string $arquivo, int $minimoDeMencoes): void {
    $texto = (string) file_get_contents(base_path($arquivo));

    $mencoes = preg_match_all('/^.*(?:aba Aplicação|aba \*\*Aplicação\*\*|Aplicação tab|\*\*Aplicação\*\* tab).*$/mu', $texto);

    expect($mencoes)->toBeGreaterThanOrEqual($minimoDeMencoes, $arquivo);
})->with([
    'configurações pt' => ['docs/pt/recursos/configuracoes-do-kit.md', 8],
    'configurações en' => ['docs/en/recursos/configuracoes-do-kit.md', 8],
    'dock pt'          => ['docs/pt/recursos/dock-de-atalhos.md', 2],
    'dock en'          => ['docs/en/recursos/dock-de-atalhos.md', 3],
    'multi-tenancy pt' => ['docs/pt/recursos/multi-tenancy.md', 1],
    'multi-tenancy en' => ['docs/en/recursos/multi-tenancy.md', 1],
    'atualização pt'   => ['docs/pt/comecar/atualizando-o-projeto.md', 1],
    'atualização en'   => ['docs/en/comecar/atualizando-o-projeto.md', 1],
])->skip(fn (): bool => ! naArvoreDoKit(), 'A documentação só está disponível na árvore do kit.')->group('kit');

it('[CT-02] preserva as oito mencoes historicas a aba Kit no changelog', function (): void {
    $texto  = (string) file_get_contents(base_path('CHANGELOG.md'));
    $partes = preg_split('/^## \[0\.49\.0\][^\r\n]*\R/m', $texto, 2);

    expect($partes)->toHaveCount(2);

    $historico = $partes[1];

    expect(preg_match_all('/aba Kit|aba \*\*Kit\*\*|Kit tab|\*\*Kit\*\* tab/u', $historico))->toBe(8);
    expect(substr_count($historico, 'aba Kit'))->toBe(6);
    expect(substr_count($historico, 'aba **Kit**'))->toBe(2);
})->skip(fn (): bool => ! naArvoreDoKit(), 'O CHANGELOG só está disponível na árvore do kit.')->group('kit');

it('[CT-19] documenta a convencao de todas as secoes com icone e colapso na rule indexada', function (): void {
    $indice = (string) file_get_contents(base_path('.ai/rules/index.md'));
    $rule   = (string) file_get_contents(base_path('.ai/rules/pages.md'));

    expect($indice)->toMatch('~^\|\s*app/Filament/Admin/Pages/\*\*\s*\|\s*\.ai/rules/pages\.md\s*\|\s*$~m');

    preg_match_all('/^## ([^\r\n]+)\R(.*?)(?=^## |\z)/msu', $rule, $secoes, PREG_SET_ORDER);
    $corpoDaConvencao = null;

    foreach ($secoes as $secao) {
        if (preg_match('/seç(?:ão|ões)|sections?/iu', $secao[1]) === 1
            && preg_match('/ícone/iu', $secao[1]) === 1
            && preg_match('/colaps/iu', $secao[1]) === 1) {
            $corpoDaConvencao = $secao[2];

            break;
        }
    }

    expect($corpoDaConvencao)->not->toBeNull('.ai/rules/pages.md sem seção de seções com ícone e colapso (step 12 pendente).');
    expect($corpoDaConvencao)->toMatch('/\btod[ao]\b/iu')
        ->toMatch('/colaps/iu')
        ->toMatch('/ícone/iu')
        ->toContain('ConfiguracoesSecoesColapsaveisTest');
})->skip(fn (): bool => ! naArvoreDoKit(), 'As rules só estão disponíveis na árvore do kit.')->group('kit');
