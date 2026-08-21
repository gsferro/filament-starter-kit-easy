<?php

use App\Filament\App\Resources\Projetos\ProjetoResource;
use App\Models\Projeto;
use App\Models\Tenant;
use Database\Seeders\PapeisSeeder;
use Database\Seeders\ShieldPermissionsSeeder;
use Spatie\Permission\Models\Role;
use Tests\TenancyTestCase;

/**
 * As capturas de tela do README — código, não trabalho manual.
 *
 * Screenshot feito à mão envelhece: ninguém refaz quinze imagens a cada release, então o
 * README passa a mostrar uma versão do kit que não existe mais. Aqui as telas nascem da
 * MESMA suíte que prova que elas funcionam.
 *
 * **Não roda por default.** Ele escreve arquivo em `art/`, e a suíte de CI não pode sujar
 * a árvore de trabalho. Roda só com a variável ligada:
 *
 *     composer art
 *
 * que é `KIT_ART=1 pest --filter=CapturaDeArte` seguido do redimensionamento das thumbs e
 * da montagem do GIF (`php artisan kit:arte`).
 *
 * A viewport é fixa em **1400x875** e `fullPage: false`, porque é a proporção das imagens
 * que já estão no `art/` (e das thumbs, 760x475). Captura de página inteira sairia mais
 * alta e a galeria do README ficaria desalinhada.
 *
 * ## Cada cenário arranja o SEU painel — o `beforeEach` não arranja painel nenhum
 *
 * Cenário de navegador precisa visitar o painel em que o processo foi deixado. Atravessar painel
 * dentro do mesmo processo faz a tela renderizar com **a barra lateral do painel anterior**: o
 * cabeçalho e o conteúdo saem certos, e a navegação ao lado é de outro painel.
 *
 * Foi o que aconteceu com `art/admin-papeis-import-export.png`, publicada errada desde o commit
 * `04642b0`: o `beforeEach` arranjava o /app (contexto de tenant + aquecimento) e aquele cenário
 * visita o /admin. As outras três capturas nunca estiveram erradas porque arranjam e visitam o
 * MESMO painel — o /app.
 *
 * Medido, com o mesmo cenário de papéis:
 *
 * | Arranjo | Barra lateral da captura |
 * |---|---|
 * | `/app` arranjado antes (o que havia aqui) | `/app` — Projetos, Convites, Usuários (ERRADA) |
 * | nenhum arranjo de `/app` | `/admin` — Usuários, Onboarding, Organizações, Funções |
 *
 * Não reproduz por `$this->get()`: em HTTP puro o painel troca certo em qualquer ordem. É
 * específico do servidor in-process do `pest-plugin-browser`.
 *
 * `fronteiraDeRequest()` **não resolve** e foi tentado duas vezes: no `beforeEach` ele derruba os
 * cenários que criam `Projeto` (esquece `Filament::getTenant()`), e antes do `visit()` produz topbar
 * duplicada com a barra lateral ainda errada.
 */
beforeEach(function (): void {
    if (! env('KIT_ART')) {
        $this->markTestSkipped('Captura de arte roda só com KIT_ART=1 (composer art).');
    }

    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);

    // O ProjetoResource exige `kit.demo`, e o phpunit.xml o fixa em `false`.
    config(['kit.demo' => true]);

    $this->organizacao = tenant('Acme', 'acme');
    $this->usuario     = usuarioComPapel('master_global');
    $this->usuario->tenants()->attach($this->organizacao);

    $this->actingAs($this->usuario);
});

/**
 * O arranjo do painel /app: contexto de tenant + aquecimento, para os cenários que visitam /app.
 *
 * ## Por que isto NÃO está no `beforeEach`
 *
 * Estava, e produzia a captura errada. Ver o cabeçalho do arquivo: cenário que é arranjado num
 * painel e visita OUTRO renderiza a barra lateral do primeiro.
 *
 * ## Por que o aquecimento existe
 *
 * O `view:cache` do `composer art` compila as Blade do repositório, mas o primeiro render de um
 * painel ainda paga a compilação dos componentes Livewire do Filament — ~25s, medido. Dentro de um
 * cenário de navegador isso estoura os 45s do plugin; num request pelo KERNEL o mesmo trabalho
 * acontece em PHP, onde ninguém cronometra, e os arquivos compilados ficam em disco para o servidor
 * do navegador reusar. Mesma causa raiz que o `tests/Pest.php` documenta em
 * `pest()->browser()->timeout()`.
 */
function arranjarPainelApp(TenancyTestCase $teste, Tenant $organizacao): void
{
    noPainelDa($organizacao);

    $teste->get(ProjetoResource::getUrl('index', tenant: $organizacao));
}

/** O modal de import: upload de CSV, mapeamento de colunas e o CSV de exemplo. */
it('captura o modal de import', function (): void {
    arranjarPainelApp($this, $this->organizacao);

    Projeto::create(['nome' => 'Contrato de fornecimento 2026']);

    visit("/app/{$this->organizacao->slug}/projetos")
        ->resize(1400, 875)
        ->click('Importar Projetos')
        ->assertSee('Arquivo')
        ->screenshot(fullPage: false, filename: 'import-modal');
})->group('browser', 'art');

/** O modal de export: uma linha por coluna do exporter, com rótulo editável. */
it('captura o modal de export', function (): void {
    arranjarPainelApp($this, $this->organizacao);

    Projeto::create(['nome' => 'Contrato de fornecimento 2026']);

    visit("/app/{$this->organizacao->slug}/projetos")
        ->resize(1400, 875)
        ->click('Exportar Projetos')
        ->assertSee('Colunas')
        ->screenshot(fullPage: false, filename: 'export-modal');
})->group('browser', 'art');

/**
 * A matriz de papéis com as permissões novas.
 *
 * É a tela onde `Import:` e `Export:` aparecem como decisão separada de `ViewAny:` — o
 * ponto inteiro de elas existirem.
 */
it('captura a matriz de papéis com Import e Export', function (): void {
    $papel = Role::query()->where('name', 'admin_app')->firstOrFail();

    // Aquece o /admin, e SÓ ele: este cenário visita o /admin, então é o /admin que o processo
    // precisa ter visto. Aquecer o /app aqui é exatamente o que publicava a captura errada.
    $this->get('/admin/shield/roles');

    // O RoleResource do Shield vive sob `/admin/shield/roles`, não `/admin/roles`.
    //
    // `hover()` antes do screenshot: o plugin não tem `scrollTo()`, e o Playwright rola o
    // elemento para a viewport antes de posicionar o mouse. Sem isso a captura pega a
    // seção do painel /admin, onde o `admin_app` não tem permissão nenhuma marcada —
    // tecnicamente correto e ilegivelmente confuso num README.
    visit("/admin/shield/roles/{$papel->getKey()}/edit")
        ->resize(1400, 875)
        ->assertSee('Projeto')
        // Seletor por atributo, e não `text=`: o texto "Painel /app" também casa com o
        // select "Acesso ao painel" lá em cima, e o Playwright recusa seletor ambíguo.
        ->hover('[id="form.painel-app::data::section-heading"]')
        ->screenshot(fullPage: false, filename: 'admin-papeis-import-export');
})->group('browser', 'art');

/**
 * A listagem com anexo E os quadros do GIF — um cenário, uma navegação.
 *
 * Estavam separados, e a separação custava caro: cada miniatura é servida pela rota
 * `storage.local`, ou seja **uma requisição PHP por imagem**, e o servidor do teste atende
 * uma por vez. Duas telas de listagem com três anexos cada estouravam os 45s do Playwright
 * de forma intermitente — e o quadro do GIF e a captura da listagem são a MESMA tela.
 *
 * Também é por isso que o GIF nasce daqui: quadro capturado em cenário separado nasce de
 * um banco recriado, e a listagem mudaria entre os quadros.
 *
 * **O quadro da notificação com o botão de download NÃO está aqui, e o motivo é vendor.**
 * `Exports\Jobs\ExportCsv:131` (e o `ImportCsv:139`) chamam `auth()->forgetGuards()` num
 * `finally`. Com `QUEUE_CONNECTION=sync` — o da suíte — o job roda DENTRO da requisição
 * web, então os guards da própria requisição são esquecidos e a navegação seguinte cai no
 * login. Medido: a captura do quadro seguinte voltava a tela de login.
 *
 * Não é defeito do kit e não é o que se vê em produção (o kit nasce com
 * `QUEUE_CONNECTION=database` e worker). A notificação com botão de download é provada por
 * `tests/Tenancy/ImportExportTenancyTest.php`, que afirma sobre o ARQUIVO gerado — oráculo
 * mais forte que o texto do botão.
 */
it('captura a listagem com anexos e os quadros do fluxo', function (): void {
    /*
     * A coluna é `->circular()->stacked()`, e isso decide a imagem de origem.
     *
     * `UploadedFile::fake()->image()` gera PNG de cor sólida preta — a miniatura sai como
     * um círculo preto. As próprias capturas do `art/` são quase todas brancas, e saem como
     * círculo vazio, que no README lê como imagem quebrada. As duas versões anteriores
     * desta captura foram publicadas assim antes de eu abrir o arquivo e conferir.
     *
     * Então o anexo é gerado aqui, com cor forte, e são TRÊS: é o que mostra o
     * `->stacked()` fazendo o que promete.
     */
    arranjarPainelApp($this, $this->organizacao);

    $projeto = Projeto::create(['nome' => 'Contrato de fornecimento 2026']);

    foreach (['planta-baixa', 'orcamento', 'cronograma'] as $indice => $nome) {
        $projeto->addMedia(anexoColorido([[245, 158, 11], [13, 148, 136], [79, 70, 229]][$indice]))
            ->usingFileName("{$nome}.png")
            ->toMediaCollection('anexos');
    }

    Projeto::create(['nome' => 'Reforma da sede']);
    Projeto::create(['nome' => 'Migração de infraestrutura']);

    visit("/app/{$this->organizacao->slug}/projetos")
        ->resize(1400, 875)
        ->assertSee('Contrato de fornecimento 2026')
        // Duas vezes o mesmo estado, dois nomes: a imagem da galeria do README e o
        // primeiro quadro do GIF. Screenshot é barato; carregar a tela de novo não é.
        ->screenshot(fullPage: false, filename: 'app-projetos-anexos')
        ->screenshot(fullPage: false, filename: 'fluxo-1-listagem')
        ->click('Exportar Projetos')
        ->assertSee('Colunas')
        ->screenshot(fullPage: false, filename: 'fluxo-2-export')
        ->press('Cancelar')
        ->click('Importar Projetos')
        ->assertSee('Arquivo')
        ->screenshot(fullPage: false, filename: 'fluxo-3-import');
})->group('browser', 'art');

/**
 * Um PNG de cor forte, para a miniatura circular ter o que mostrar.
 *
 * GD, e não um arquivo commitado: imagem de exemplo no repositório só para alimentar
 * screenshot é peso morto, e a cor aqui é escolhida para contrastar com o fundo claro do
 * painel.
 *
 * @param  array{int, int, int}  $cor
 */
function anexoColorido(array $cor): string
{
    $imagem = imagecreatetruecolor(400, 400);

    imagefill($imagem, 0, 0, imagecolorallocate($imagem, ...$cor));

    // Um arco mais claro, para a miniatura não parecer um disco chapado de 20px.
    imagefilledarc(
        $imagem, 200, 200, 300, 300, 200, 340,
        imagecolorallocate($imagem, min(255, $cor[0] + 70), min(255, $cor[1] + 70), min(255, $cor[2] + 70)),
        IMG_ARC_PIE,
    );

    $caminho = tempnam(sys_get_temp_dir(), 'arte').'.png';
    imagepng($imagem, $caminho);
    imagedestroy($imagem);

    return $caminho;
}
