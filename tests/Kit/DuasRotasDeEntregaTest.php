<?php

use App\Console\Commands\KitUpdate;
use Illuminate\Support\Facades\File;

/**
 * As duas rotas de entrega do kit não podem divergir em silêncio.
 *
 * ## O problema, que já ocorreu duas vezes
 *
 * O kit entrega arquivo por **dois caminhos governados por listas diferentes**:
 *
 * | Caminho | Lista que o governa | Forma |
 * |---|---|---|
 * | `composer create-project` | `.gitattributes` | **exclusão** — viaja tudo, menos o marcado `export-ignore` |
 * | `php artisan kit:update` | `KitUpdate::CAMINHOS_DO_KIT` | **inclusão** — só viaja o que está listado |
 *
 * Uma lista de exclusão e uma de inclusão **divergem sozinhas**: basta um diretório novo nascer.
 * Ele entra na primeira por omissão e fica fora da segunda pelo mesmo motivo — e ninguém percebe,
 * porque quem instala limpo tem o arquivo, quem atualiza não, e os dois veem a suíte verde.
 *
 * **Ocorrência 1**, `v0.23.0`: `resources/views/svg` viajava e não era atualizado. Quem rodou
 * `kit:update` recebeu o `IdentidadeDoKit` que consome a view e não a view — `View [svg.arte-do-login]
 * not found` no primeiro `composer dev`.
 *
 * **Ocorrência 2**, medida na validação da `v0.39.0`: `tests/Browser` e `tests/BrowserTenancy`
 * **nunca** eram atualizados. Instalação limpa com 21 arquivos, instalação atualizada com 20.
 *
 * Nos dois casos **existia** uma varredura de completude (`KitUpdateTest`), e nos dois ela estava
 * cega — porque o escopo dela, `DIRETORIOS_DE_CODIGO`, é uma **terceira lista mantida à mão**.
 * Corrigir a terceira lista a cada ocorrência é fechar o caso e deixar a classe aberta.
 *
 * ## O que este caso afirma
 *
 * Todo caminho de topo que **viaja** está em `CAMINHOS_DO_KIT` **ou** foi declarado aqui, com o
 * motivo. Caminho novo nasce **reprovando** — é o oposto de nascer esquecido.
 */
it('todo caminho de topo que viaja esta coberto pelo kit:update ou declarado fora', function (): void {
    $viajam = caminhosDeTopoQueViajam();

    /*
     * CONTROLE POSITIVO DA VARREDURA, e ele vem primeiro: um `.gitattributes` ilegível, ou um
     * filtro que deixasse de casar, devolveriam lista vazia e este caso ficaria VERDE sobre nada.
     */
    expect(count($viajam))->toBeGreaterThan(8, 'a varredura não encontrou caminhos de topo — a lista abaixo mediria o vazio');

    /*
     * `CAMINHOS_DO_KIT` e PRIVADA, e ler por Reflection e melhor que por regex no fonte: um
     * `ReflectionClassConstant` acompanha refatoracao do arquivo, um regex nao.
     *
     * SEM `use` no topo: arquivo de teste Pest nao tem namespace, entao importar uma classe do
     * namespace global emite "The use statement with non-compound name has no effect" — que em
     * `--parallel` vira FATAL e aborta a suite inteira antes de rodar um caso. Custou uma
     * regressao abortada.
     */
    $cobertos = (new ReflectionClassConstant(KitUpdate::class, 'CAMINHOS_DO_KIT'))->getValue();

    $orfaos = [];

    foreach ($viajam as $caminho) {
        if (array_key_exists($caminho, FORA_DA_ENTREGA_POR_DECISAO)) {
            continue;
        }

        // O diretorio inteiro esta listado: nada abaixo dele pode escapar.
        if (in_array($caminho, $cobertos, true)) {
            continue;
        }

        $parciais = array_filter(
            $cobertos,
            static fn (string $listado): bool => str_starts_with($listado, $caminho.'/'),
        );

        // Nada abaixo dele esta listado: o diretorio inteiro e orfao.
        if ($parciais === []) {
            $orfaos[] = $caminho;

            continue;
        }

        /*
         * COBERTURA PARCIAL — E AQUI QUE A OCORRENCIA 2 MORAVA.
         *
         * `tests` nao esta listado inteiro; o que esta sao `tests/Kit`, `tests/Tenancy` e tres
         * arquivos. Uma guarda que parasse no topo veria `tests` como "coberto" e ficaria VERDE
         * com `tests/Browser` de fora — exatamente o defeito que ela veio guardar, e foi o que
         * a primeira redacao deste caso fez: o mutante sobreviveu a bateria.
         *
         * Entao, quando a cobertura e parcial, cada FILHO de disco precisa estar coberto ou
         * declarado. Um subdiretorio novo nasce reprovando, como o de topo.
         */
        foreach (File::directories(base_path($caminho)) as $sub) {
            $relativo = $caminho.'/'.basename($sub);

            $cobertoFilho = array_any(
                $cobertos,
                static fn (string $listado): bool => $listado === $relativo
                    || str_starts_with($listado, $relativo.'/')
                    || str_starts_with($relativo, $listado.'/'),
            );

            if ($cobertoFilho || array_key_exists($relativo, FORA_DA_ENTREGA_POR_DECISAO)) {
                continue;
            }

            $orfaos[] = $relativo;
        }
    }

    sort($orfaos);

    expect($orfaos)->toBe([], implode("\n", [
        'Estes caminhos de topo VIAJAM no `composer create-project` e NÃO são entregues pelo `kit:update`:',
        ...array_map(static fn (string $c): string => '  '.$c, $orfaos),
        '',
        'Quem instalou numa versão antiga e vem atualizando nunca vai recebê-los.',
        '',
        'Duas saídas, e as duas exigem decisão explícita:',
        '  1. acrescentar a `KitUpdate::CAMINHOS_DO_KIT`, se o kit é dono do conteúdo; ou',
        '  2. declarar em `FORA_DA_ENTREGA_POR_DECISAO`, neste arquivo, COM O MOTIVO.',
    ]));
})->group('kit');

/**
 * Os caminhos de topo que viajam e que o `kit:update` NÃO entrega, cada um com o motivo.
 *
 * Declarar aqui não "resolve" o caminho — torna a decisão **visível e revisável**. A diferença
 * entre esta lista e o esquecimento é que esta tem autor e motivo.
 */
const FORA_DA_ENTREGA_POR_DECISAO = [
    'art' => 'Capturas de tela e GIF do README do kit. São material do repositório, não código que '
        .'o projeto instalado execute; `kit:arte` as regera a partir da suíte de browser.',

    'stubs' => 'Stubs publicáveis do Laravel e dos agentes de IA. O projeto instalado os edita para '
        .'moldar o próprio código — sobrescrevê-los apagaria a customização de quem instalou.',

    'bootstrap' => 'DÉBITO DECLARADO, e o mais caro dos três. `bootstrap/app.php` e '
        .'`bootstrap/providers.php` são do ESQUELETO — quem instala registra ali os próprios '
        .'middlewares e providers, e sobrescrevê-los destruiria isso. Mas o kit TAMBÉM escreve '
        .'neles: `RaizDeUrlSemPublic` entrou no stack global na v0.36.1, e `KitServiceProvider` '
        .'é registrado em `providers.php`. Consequência medida: quem instalou antes da v0.36.1 e '
        .'vem rodando `kit:update` tem a CLASSE do middleware (coberta por `app/Http/Middleware`) '
        .'e NÃO tem o registro — a correção de URL não funciona para essa instalação, em silêncio. '
        .'A saída estrutural é o kit registrar o próprio middleware a partir do `KitServiceProvider`, '
        .'que viaja pelas duas rotas, em vez de depender do `bootstrap/app.php` do usuário.',

    'lang/pt_BR' => 'As traduções PADRÃO do Laravel (validation, auth, passwords, pagination). Quem '
        .'instala ajusta ao próprio domínio, e sobrescrevê-las apagaria isso. O que é do kit é '
        .'`lang/pt_BR.json` — as 33 strings que traduzem telas de plugin de terceiro — e esse SIM '
        .'passou a ser entregue pelo `kit:update`, na correção que este arquivo motivou.',

    'public/build' => 'Saída do Vite, gerada por `npm run build` no projeto instalado. Entregar '
        .'artefato compilado pelo update sobrescreveria o build de quem tem front próprio.',

    'public/fonts' => 'Fontes publicadas por `filament:assets`, que o `kit:update` manda rodar ao '
        .'final. Elas se regeneram a partir do vendor instalado, e versioná-las no update faria o '
        .'kit competir com o comando do Filament pela mesma pasta.',

    'public/js' => 'JavaScript publicado por `filament:assets` — uma pasta por vendor (`filament`, '
        .'`dotswan`, `solutionforest`…). Mesmo motivo de `public/fonts`. Note a assimetria '
        .'deliberada com `public/css/kit`, que É entregue: aquela pasta tem CSS ESCRITO pelo kit, '
        .'não publicado por comando, e não se regenera sozinha.',

    'resources/js' => 'Ponto de extensão do front de quem instala. O kit não escreve JS aqui; o '
        .'que ele entrega de front é `resources/css/filament`, que está coberto.',

    'tests/Feature' => 'Esqueleto do Laravel, e o lugar onde quem instala escreve os testes do '
        .'PRÓPRIO negócio. Entregá-lo pelo update sobrescreveria a suíte do usuário — é a mesma '
        .'razão pela qual `tests/Unit` também fica fora.',

    'tests/Unit' => 'Esqueleto do Laravel, ponto de extensão de quem instala. Ver `tests/Feature`.',
];

/**
 * Os caminhos de primeiro nível que o `composer create-project` entrega.
 *
 * Derivado do `.gitattributes`, que é a fonte da verdade da PRIMEIRA rota — e não de uma lista à
 * mão, que seria a quarta.
 *
 * @return list<string>
 */
function caminhosDeTopoQueViajam(): array
{
    $excluidos = [];

    foreach (preg_split('~\R~', File::get(base_path('.gitattributes'))) ?: [] as $linha) {
        $linha = trim($linha);

        if ($linha === '' || str_starts_with($linha, '#') || ! str_contains($linha, 'export-ignore')) {
            continue;
        }

        $alvo = trim((string) strtok($linha, " \t"), '/');

        if ($alvo !== '') {
            $excluidos[] = explode('/', $alvo)[0];
        }
    }

    /*
     * `vendor` e `node_modules` não são versionados; `storage` viaja, mas o que ele carrega é
     * estrutura de diretório vazia, não conteúdo que o kit atualize.
     */
    $nuncaSaoDoKit = ['vendor', 'node_modules', 'storage', '.git'];

    $viajam = [];

    foreach (File::directories(base_path()) as $diretorio) {
        $nome = basename($diretorio);

        if (in_array($nome, $excluidos, true) || in_array($nome, $nuncaSaoDoKit, true)) {
            continue;
        }

        $viajam[] = $nome;
    }

    sort($viajam);

    return $viajam;
}

/**
 * Controle positivo do leitor do `.gitattributes` — sem ele, um parser que devolvesse lista vazia
 * de exclusões faria o caso acima acusar `docs`, `site` e `wikis`, e alguém os "declararia" para
 * ficar verde, escondendo que o leitor estava quebrado.
 */
it('o leitor do gitattributes reconhece as exclusoes declaradas', function (): void {
    $viajam = caminhosDeTopoQueViajam();

    // Estes estão marcados `export-ignore` e NÃO podem aparecer como viajantes.
    expect($viajam)->not->toContain('docs')
        ->and($viajam)->not->toContain('site')
        ->and($viajam)->not->toContain('site-vitepress');

    // E estes viajam — se sumirem daqui, o leitor parou de enxergar o disco.
    $this->assertContains('app', $viajam);
    $this->assertContains('config', $viajam);
    $this->assertContains('tests', $viajam);
})->group('kit');

/**
 * Nenhuma declaração de `FORA_DA_ENTREGA_POR_DECISAO` sobrevive ao caminho que ela descreve.
 *
 * Entrada que aponta para caminho inexistente é lixo que envelhece em silêncio, e pior: ela
 * **cobriria** um caminho novo de mesmo nome no futuro, sem ninguém rever o motivo.
 */
it('toda declaracao de excecao aponta um caminho que existe e viaja', function (): void {
    expect(FORA_DA_ENTREGA_POR_DECISAO)->not->toBe([], 'a lista de exceções está vazia — o caso acima não estaria provando nada sobre ela');

    $viajam = caminhosDeTopoQueViajam();

    foreach (FORA_DA_ENTREGA_POR_DECISAO as $caminho => $motivo) {
        // A declaração pode ser de topo (`art`) ou de segundo nível (`lang/pt_BR`).
        $topo = explode('/', $caminho)[0];

        /*
         * `assertContains`, e NAO `expect()->toContain($x, $msg)`: o `toContain()` do Pest e
         * VARIADICO e leria a mensagem como uma segunda agulha, passando sempre
         * (`.ai/rules/testes.md`). Custou uma rodada vermelha ao escrever isto — a rule estava
         * lida, e ainda assim.
         */
        $this->assertContains($topo, $viajam, "`{$caminho}` está declarado como exceção e o topo dele não viaja mais — remova a declaração");
        $this->assertDirectoryExists(base_path($caminho), "`{$caminho}` está declarado como exceção e não existe mais no disco — remova a declaração");
        expect(mb_strlen($motivo))->toBeGreaterThan(60, "a exceção `{$caminho}` precisa de um motivo escrito, não de um rótulo");
    }
})->group('kit');
