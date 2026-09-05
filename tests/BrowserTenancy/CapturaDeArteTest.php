<?php

use App\Filament\App\Resources\Projetos\ProjetoResource;
use App\Models\Projeto;
use App\Models\Role;
use App\Models\Tenant;
use App\Settings\ConfiguracoesDoKit;
use App\Support\ProvedorSocial;
use Database\Seeders\PapeisSeeder;
use Database\Seeders\ShieldPermissionsSeeder;
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
    /*
     * O papel `admin`, e não o `admin_app` — e a troca é o conserto de uma captura que ficou
     * impossível de tirar.
     *
     * Desde a v0.18.11 os painéis são um tab VERTICAL, e a aba que abre é a do painel do próprio
     * /admin. Para fotografar as permissões de um papel do /app era preciso clicar na aba dele, e
     * o clique não tem seletor estável: `[role="tab"]:has-text("Painel /app")` estoura o teto de
     * 45 s, e `text=` é ambíguo com o select "Acesso ao painel" lá em cima — o Playwright recusa.
     *
     * A imagem que o README precisa é "a matriz com Import e Export", e o papel `admin` a dá
     * melhor: as 126 permissões dele são todas do painel que já está aberto, então a captura sai
     * com as caixas MARCADAS. A do `admin_app` saía com a seção do /admin vazia — tecnicamente
     * correta e ilegível numa galeria.
     *
     * Sem `hover()`: ele existia para rolar até a seção do /app, que não é mais o alvo.
     */
    $papel = Role::query()->where('name', 'admin')->firstOrFail();

    // Aquece o /admin, e só ele: aquecer o /app aqui é o que publicava a captura errada.
    $this->get('/admin/shield/roles');

    visit("/admin/shield/roles/{$papel->getRouteKey()}/edit")
        ->resize(1400, 875)
        ->assertSee('Convite')
        ->assertSee('Import')
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

/**
 * A tela de boas-vindas da rota `/` — a primeira coisa que alguém vê depois de instalar.
 *
 * Sem `arranjarPainelApp()` e sem `actingAs` relevante: a rota é **anônima** por desenho, e é
 * assim que ela precisa aparecer na documentação. O `beforeEach` autentica alguém, então o
 * `logout()` é o que garante que a captura mostre o que o visitante vê — com o usuário logado,
 * o menu de usuário apareceria no canto e a imagem mentiria sobre a tela.
 *
 * `assertSee` antes do screenshot, e não `wait()`: os cartões vêm de `harvirsidhu/filament-cards`
 * e a infolist é renderizada no servidor, mas o painel bootado pelo `panel:app` carrega CSS e o
 * script de tema — capturar antes disso produz uma tela sem estilo, que é o defeito clássico
 * destas capturas.
 */
it('captura a tela de boas-vindas da raiz', function (): void {
    auth()->logout();

    visit('/')
        ->resize(1400, 875)
        ->assertSee('Painel')
        ->screenshot(fullPage: false, filename: 'boas-vindas');
})->group('browser', 'art');

/*
|--------------------------------------------------------------------------
| Login social e vínculo de provedor — as telas do README
|--------------------------------------------------------------------------
| `ligarProvedor()` põe os botões no ar por config (sem credencial real); nenhuma captura
| chega ao provedor. Cada cenário arranja o SEU painel antes do `visit()` — a barra lateral
| sai do painel do arranjo (.ai/rules/testes-browser.md).
*/

/*
|--------------------------------------------------------------------------
| A tela de login da vitrine
|--------------------------------------------------------------------------
| `art/login.png` é a PRIMEIRA imagem dos dois READMEs, e era a única captura de
| tela de autenticação que o comando não gerava — vinha de antes dele. Ficou de
| fora até a wiki `arte-do-login-com-nome-da-aplicacao`, quando a arte passou a
| carregar o nome da aplicação e a imagem velha passou a vender "starter-kit-easy"
| na vitrine do kit.
|
| Adotada aqui para não envelhecer sozinha de novo. O par é obrigatório
| (`.ai/rules/testes-browser.md`): o `filename` daqui e a linha em
| `KitArte::IMAGENS` — sem a segunda, o comando reporta a imagem como ignorada.
*/

it('captura a tela de login do painel de administração', function (): void {
    auth()->logout();

    visit('/admin/login')
        ->resize(1400, 875)
        ->assertSee('Faça login')
        // A arte é o motivo desta captura existir: ela mostra o nome da aplicação.
        ->assertPresent('img.fi-auth-media')
        ->assertNoBrokenImages()
        ->screenshot(fullPage: false, filename: 'login');
})->group('browser', 'art');

it('captura a tela de login com os botões sociais e o rodapé', function (): void {
    ligarProvedor(ProvedorSocial::Google);
    ligarProvedor(ProvedorSocial::Github);
    config(['kit.login.rodape' => '**Starter Kit Easy** — [documentação](https://github.com/gsferro/filament-starter-kit-easy)']);
    auth()->logout();

    visit('/app/login')
        ->resize(1400, 875)
        ->assertSee('Entrar com Google')
        ->assertSee('Entrar com GitHub')
        ->screenshot(fullPage: false, filename: 'login-social');
})->group('browser', 'art');

it('captura a aba Login das configurações da aplicação', function (): void {
    // Aquece o /admin, e só ele.
    $this->get('/admin/configuracoes-do-kit');

    visit('/admin/configuracoes-do-kit')
        ->resize(1400, 875)
        ->click('Login')
        ->assertSee('Login social')
        ->assertSee('Rodapé da tela de login')
        ->screenshot(fullPage: false, filename: 'admin-configuracoes-login');
})->group('browser', 'art');

/**
 * A seção "Proteção anti-robô" das configurações — e por que ela, e não a tela de login.
 *
 * A foto óbvia seria o widget na tela de login. Ela não serve: **todo provedor marca a própria
 * chave de teste**. O Google desenha um banner vermelho sobre o widget ("This reCAPTCHA is for
 * testing purposes only") e a Cloudflare uma faixa embaixo ("For testing only. If seen, report to
 * site owner") — medido nos dois. Captura de README com aviso de erro ensina a coisa errada, e
 * chave real com domínio autorizado é coisa que o kit não tem.
 *
 * Esta tela mostra o que interessa e não depende de terceiro: o toggle, o provedor, as duas chaves
 * e a pontuação mínima do v3. É também onde o README manda ir para ligar a proteção.
 *
 * O provedor fica em `recaptcha_v3` — o default do kit — para a captura mostrar o campo de
 * pontuação mínima, que só aparece com o v3.
 */
/*
|--------------------------------------------------------------------------
| O desafio anti-robô nas telas de login — com chave real
|--------------------------------------------------------------------------
| Estes dois cenários existem porque o de baixo (a seção das configurações) NÃO
| consegue mostrar o widget: com chave de teste, Google e Cloudflare carimbam o
| widget com aviso — "for testing purposes only" e "Testing site key". Captura de
| README com aviso de erro ensina a coisa errada.
|
| A saída é chave real autorizada em `localhost`/`127.0.0.1`, que é onde o
| servidor in-process da suíte responde. A chave NÃO mora no repositório: cada
| cenário lê a sua do ambiente e se PULA quando ela não existe, então quem clona o
| kit roda `composer art` sem nada quebrar — só não regenera estas duas imagens.
|
| Só a chave DO SITE é real, e ela é pública por natureza (vai no HTML de toda
| tela de login). A secreta é um marcador: `ConfiguracaoDoLogin::antiRobo()` exige
| as duas preenchidas para considerar a proteção no ar, e a secreta só seria usada
| no `siteverify` — que não roda aqui, porque a captura não envia o formulário.
|
| Dependem de REDE (o script vem do provedor). É aceitável neste arquivo, e só
| neste: ele roda sob `KIT_ART=1`, nunca no CI.
*/

/** A chave do site de um provedor, para as capturas — ou `null` quando não configurada. */
function chaveDeArteAntiRobo(string $variavel): ?string
{
    $chave = env($variavel);

    return is_string($chave) && trim($chave) !== '' ? trim($chave) : null;
}

/**
 * Liga a proteção no provedor e na chave pedidos.
 *
 * `config()` e NÃO as settings do banco, ao contrário do cenário da seção de
 * configurações logo abaixo. O motivo está em `KitServiceProvider:169-174`: com
 * `RefreshDatabase` o `boot()` roda ANTES das migrations, a tabela ainda não
 * existe e `aplicarNaConfig()` é inerte — settings gravadas num cenário nunca
 * alcançam a config do request. É o mesmo caminho de `ligarProvedor()` e do
 * rodapé, e a captura do rodapé é a prova de que ele funciona.
 */
function ligarAntiRoboParaArte(string $provedor, string $chaveDoSite): void
{
    config([
        'kit.login.anti_robo.habilitado'    => true,
        'kit.login.anti_robo.provedor'      => $provedor,
        'kit.login.anti_robo.chave_do_site' => $chaveDoSite,
        // Marcador: `ConfiguracaoDoLogin::antiRobo()` exige as duas chaves
        // preenchidas, e a secreta só seria usada no `siteverify` — que não roda
        // aqui, porque a captura não submete o formulário.
        'kit.login.anti_robo.chave_secreta' => 'nao-usada-na-captura',
    ]);
}

/**
 * O Cloudflare Turnstile é o que MOSTRA caixa — é a captura informativa dos dois.
 *
 * O widget vive num iframe de `challenges.cloudflare.com`; a asserção espera por
 * ele antes do clique do obturador, senão a foto sai com o espaço vazio.
 */
it('captura a tela de login com o desafio do Cloudflare Turnstile', function (): void {
    ligarAntiRoboParaArte('turnstile', (string) chaveDeArteAntiRobo('KIT_ART_TURNSTILE_SITE_KEY'));

    auth()->logout();

    visit('/app/login')
        ->resize(1400, 875)
        ->assertSee('Faça login')
        // O widget monta em shadow DOM, e o iframe interno não é alcançável por
        // seletor comum. O input de resposta que o Turnstile cria no container FICA
        // no DOM light, e só existe depois de o widget renderizar — é o oráculo
        // barato de "pintou", e não só de "o script baixou".
        ->assertPresent('input[name="cf-turnstile-response"]')
        // O input nasce assim que o widget monta, ANTES de ele terminar de desenhar:
        // sem esta pausa a foto sai no estado transitório ("Verifying…", com spinner),
        // que não é o que o README precisa mostrar.
        ->wait(3)
        ->screenshot(fullPage: false, filename: 'login-turnstile');
})->skip(
    fn (): bool => chaveDeArteAntiRobo('KIT_ART_TURNSTILE_SITE_KEY') === null,
    'Defina KIT_ART_TURNSTILE_SITE_KEY com uma chave autorizada em localhost para regerar esta arte.',
)->group('browser', 'art');

/**
 * O reCAPTCHA v3 é INVISÍVEL: não há caixa, só o emblema flutuante no canto.
 *
 * É exatamente isso que a captura precisa mostrar — o README compara os dois lado
 * a lado, e a diferença entre "caixa" e "emblema discreto" é a decisão que quem
 * instala o kit toma ao escolher o provedor.
 */
it('captura a tela de login com o emblema do reCAPTCHA v3', function (): void {
    ligarAntiRoboParaArte('recaptcha_v3', (string) chaveDeArteAntiRobo('KIT_ART_RECAPTCHA_V3_SITE_KEY'));

    auth()->logout();

    visit('/app/login')
        ->resize(1400, 875)
        ->assertSee('Faça login')
        ->assertPresent('.grecaptcha-badge')
        /*
         * O `hover` NÃO é enfeite. O Google mantém o emblema COLAPSADO, com boa parte
         * dele fora da viewport de propósito — fotografado assim, ele sai cortado pela
         * borda e parece defeito de captura. No hover ele expande e mostra o texto
         * "protected by reCAPTCHA", que é o que o leitor do README precisa reconhecer.
         *
         * A pausa depois é pela animação de expansão.
         */
        ->hover('.grecaptcha-badge')
        ->wait(2)
        ->screenshot(fullPage: false, filename: 'login-recaptcha-v3');
})->skip(
    fn (): bool => chaveDeArteAntiRobo('KIT_ART_RECAPTCHA_V3_SITE_KEY') === null,
    'Defina KIT_ART_RECAPTCHA_V3_SITE_KEY com uma chave autorizada em localhost para regerar esta arte.',
)->group('browser', 'art');

it('captura a seção Proteção anti-robô das configurações', function (): void {
    /*
     * A proteção LIGADA nas settings, porque os campos são condicionais: o provedor, as chaves e a
     * pontuação mínima só existem no schema com `login_anti_robo_habilitado` verdadeiro
     * (`ConfiguracoesDoKit.php:550`), e a pontuação, só com o v3. Sem isto a captura seria um
     * toggle desligado e mais nada.
     *
     * As chaves são de demonstração e não saem daqui: a do site vai para a foto, a secreta é
     * cifrada e a tela nunca a exibe.
     */
    $settings                                = app(ConfiguracoesDoKit::class);
    $settings->login_anti_robo_habilitado    = true;
    $settings->login_anti_robo_provedor      = 'recaptcha_v3';
    $settings->login_anti_robo_chave_do_site = '6LcExemploDaDocumentacaoDoKit000000000';
    $settings->login_anti_robo_chave_secreta = '6LcExemploSecretoDaDocumentacao00000000';
    $settings->save();

    // Aquece o /admin, e só ele — mesma razão do cenário da aba Login acima.
    $this->get('/admin/configuracoes-do-kit');

    /*
     * `screenshotElement()`, e não a captura da viewport.
     *
     * A seção fica abaixo da dobra na aba Login, e as duas tentativas de trazê-la para cima
     * falharam: clicar nela a COLAPSA (as seções nascem abertas, `->collapsible()` sem
     * `->collapsed()`), e clicar em "Login social" para fechar a de cima não colapsa nada — o
     * texto do título não é o gatilho. Capturar o elemento resolve sem depender de layout.
     *
     * O seletor vem do `id` que o Filament dá à Section pelo rótulo.
     */
    visit('/admin/configuracoes-do-kit')
        ->resize(1400, 875)
        ->click('Login')
        ->assertSee('Pontuação mínima')
        ->screenshotElement('.fi-sc-section:has-text("Proteção anti-robô")', 'admin-anti-robo');
})->group('browser', 'art');

it('captura o bloco Definir senha por e-mail no perfil', function (): void {
    arranjarPainelApp($this, $this->organizacao);

    visit("/app/{$this->organizacao->slug}/meu-perfil")
        ->resize(1400, 875)
        ->assertSee('Definir senha por e-mail')
        ->screenshot(fullPage: false, filename: 'app-perfil-definir-senha');
})->group('browser', 'art');

it('captura a tela de bloqueio com o login social', function (): void {
    ligarProvedor(ProvedorSocial::Google);
    ligarProvedor(ProvedorSocial::Github);
    arranjarPainelApp($this, $this->organizacao);
    session(['lockscreen' => true, 'tenant_corrente' => $this->organizacao->getKey()]);

    visit(route('lockscreen.app.page'))
        ->resize(1400, 875)
        ->assertSee('Entrar com Google')
        ->screenshot(fullPage: false, filename: 'app-bloqueio-social');
})->group('browser', 'art');

it('captura a lista de usuários com a coluna Origem', function (): void {
    usuarioComPapel('panel_user', $this->organizacao, 'ana@example.com')->forceFill(['origem' => 'google'])->save();
    usuarioComPapel('panel_user', $this->organizacao, 'bruno@example.com')->forceFill(['origem' => 'github'])->save();
    usuarioComPapel('panel_user', $this->organizacao, 'carla@example.com')->forceFill(['origem' => 'convite'])->save();

    // Aquece o /admin, e só ele.
    $this->get('/admin/users');

    visit('/admin/users')
        ->resize(1400, 875)
        ->assertSee('Origem')
        ->assertSee('Google')
        ->screenshot(fullPage: false, filename: 'admin-users-origem');
})->group('browser', 'art');
