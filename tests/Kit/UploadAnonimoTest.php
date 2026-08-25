<?php

use App\Filament\Admin\Pages\ConfiguracoesDoKit;
use App\Filament\App\Pages\ConvitesRecebidos;
use App\Filament\Pages\BoasVindas;
use Database\Seeders\PapeisSeeder;
use Database\Seeders\ShieldPermissionsSeeder;
use Filament\Actions\DeleteAction;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Illuminate\Support\Facades\File;

/**
 * O achado F-02 da auditoria do Filament Blueprint: o RPC de upload aberto na rota pública.
 *
 * ## O que estes casos medem, e por que NÃO medem a tela
 *
 * O jeito natural de testar "não pode excluir" numa tela Filament é abrir a tabela e afirmar que a
 * ação não está lá. Esse teste **fica verde com o defeito presente** — porque o defeito ERA a
 * ausência do botão fazendo o papel de autorização. Ele mediria a barreira errada.
 *
 * ## E por que um dos casos passa pelo framework de verdade
 *
 * A primeira versão deste arquivo tinha só chamadas estáticas ao método que eu ACREDITO ser
 * consultado. A revisão adversarial apontou que isso reproduz o próprio F-01 um nível acima: um
 * override cuja consulta ninguém prova. CT-03B resolve — ele chama
 * `getDefaultActionAuthorizationResponse()` da página real com uma `DeleteAction` real, que é o
 * caminho de `Resources/Pages/Page.php:313`. Se um upgrade do Filament mudar esse mapeamento, é
 * ele que fica vermelho.
 *
 * Ver `wikis/specs/feat/auditoria-de-seguranca/travas-de-exclusao-e-upload-anonimo/`.
 */
beforeEach(function (): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class]);

    /*
     * Fixar o painel não é cerimônia: `Filament::getCurrentPanel()` VAZA entre arquivos de teste.
     * Medido durante a mutação — um caso deste arquivo rodou com o painel `infra` corrente, herdado
     * de outra suíte, e por isso ficou verde com a correção removida. Sem esta linha, a
     * sensibilidade dos casos depende da ordem de execução, que é o pior tipo de teste verde.
     *
     * O caso do /admin troca o painel por conta própria, e é o único que precisa disso.
     */
    Filament::setCurrentPanel('app');
});

/*
|--------------------------------------------------------------------------
| R1 — a exclusão de usuário no /app é negada pelo método que o Filament consulta
|--------------------------------------------------------------------------
*/

/*
|--------------------------------------------------------------------------
| R5 — página fora da autenticação do painel restringe o upload ao schema
|--------------------------------------------------------------------------
*/

/**
 * CT-08 e CT-09 — as duas partições estruturais: a página PÚBLICA e a autenticada sem upload.
 *
 * O oráculo é a **resposta do método**, não `class_uses_recursive()`. A revisão adversarial pegou
 * isto: com a inspeção da composição, aplicar o trait e sobrescrever o método depois passaria — o
 * trait continua na lista. Quem o framework consulta é o método
 * (`SchemasServiceProvider.php:69-71`), e é ele que o caso pergunta.
 */
it('restringe o upload ao schema nas paginas sem campo de upload', function (string $pagina): void {
    expect((new $pagina)->shouldRestrictFileUploadsToSchemaComponents())->toBeTrue(
        "{$pagina} herda o RPC de upload do Livewire pela cadeia até BasePage e não tem campo de "
        .'upload no schema. Sem a restrição, o canal fica aberto.'
    );
})->with([
    'a pagina publica da rota /' => BoasVindas::class,
    'convites recebidos do /app' => ConvitesRecebidos::class,
])->group('kit');

/**
 * CT-09B — a coluna VÁLIDA da matriz: página que TEM campo de upload não é restringida.
 *
 * Sem este caso, aplicar o trait numa classe base compartilhada passaria em CT-08 e CT-09 e
 * quebraria o upload legítimo de logo, favicon e arte de login em produção — com a suíte verde.
 * Foi a IMPL-D da revisão adversarial.
 */
it('nao restringe as paginas que tem campo de upload legitimo', function (): void {
    /*
     * `method_exists`, e não chamar o método: sem o trait ele não existe, e chamá-lo estoura em
     * vez de devolver `false`. É a mesma pergunta que o próprio Filament faz antes de decidir
     * (`SchemasServiceProvider.php:69-70`), o que torna este o oráculo fiel.
     */
    expect(method_exists(ConfiguracoesDoKit::class, 'shouldRestrictFileUploadsToSchemaComponents'))
        ->toBeFalse(
            'A tela de configurações tem três FileUpload de verdade. Se ela passou a restringir, o '
            .'trait foi aplicado numa base compartilhada e o upload legítimo pode ter quebrado.'
        );
})->group('kit');

/**
 * CT-10 — o sweep, para a correção não envelhecer.
 *
 * Página do Filament montada DIRETO em `routes/web.php` está fora da autenticação de painel: é
 * sempre o caso perigoso. As duas âncoras existem porque `foreach` sobre lista vazia passa calado,
 * e porque "não está vazia" prova apenas `>= 1` — a revisão apontou que uma extração que achasse
 * só uma página passaria sendo justamente o cenário que o caso quer pegar.
 */
it('nao deixa pagina montada fora de um painel sem a restricao de upload', function (): void {
    $rotas = File::get(base_path('routes/web.php'));

    preg_match_all('/^use (App\\\\Filament\\\\[\w\\\\]+);/m', $rotas, $importadas);

    $montadas = array_values(array_filter(
        $importadas[1] ?? [],
        static fn (string $classe): bool => class_exists($classe)
            && is_subclass_of($classe, Page::class)
            && str_contains($rotas, class_basename($classe).'::class'),
    ));

    expect($montadas)->toContain(BoasVindas::class)
        ->and($montadas)->toHaveCount(1,
            'Âncora de população: hoje há exatamente UMA página montada fora de painel. Se este '
            .'número mudou, a lista abaixo tem de cobrir a nova — e se caiu a zero, a extração '
            .'quebrou e o caso ficaria verde para sempre.'
        );

    foreach ($montadas as $classe) {
        expect((new $classe)->shouldRestrictFileUploadsToSchemaComponents())->toBeTrue(
            "{$classe} é servida fora de um painel: rota sem `auth` não reautoriza nada, e o RPC "
            .'de upload do Livewire vem junto com InteractsWithSchemas.'
        );
    }
})->group('kit');

/*
|--------------------------------------------------------------------------
| R6 — a documentação nomeia o mecanismo que de fato trava
|--------------------------------------------------------------------------
*/

/**
 * CT-11 — a frase errada é o vetor de reintrodução do defeito.
 *
 * A varredura é em TODO o `app/`, e não só no `EditUser`: a revisão adversarial apontou que a regra
 * é declarada repo-wide e a primeira versão verificava um arquivo. A asserção positiva é sobre a
 * frase inteira, e não sobre a presença do identificador — "getDeleteAuthorizationResponse NÃO é a
 * trava" satisfaria um `contains` do identificador.
 */
it('nao afirma em lugar nenhum que um can-metodo e a trava de exclusao', function (): void {
    $arquivos = File::allFiles(base_path('app'));

    $suspeitos = [];
    foreach ($arquivos as $arquivo) {
        if ($arquivo->getExtension() !== 'php') {
            continue;
        }

        /*
         * Só a forma AFIRMATIVA no presente: "a trava é canDelete()". A primeira versão casava
         * "trava" e "canDelete" na mesma frase em qualquer ordem, e flagrava o próprio texto da
         * correção — que diz "já disse que a trava ERA canDelete, e era falso". Explicar o erro
         * antigo não é cometê-lo, e um oráculo que não distingue as duas coisas proíbe documentar.
         */
        if (preg_match('/trava (de verdade )?é[^.]{0,40}canDelete/iu', $arquivo->getContents())) {
            $suspeitos[] = $arquivo->getRelativePathname();
        }
    }

    expect($arquivos)->not->toBeEmpty('Âncora de população: a varredura não leu arquivo nenhum.')
        ->and($suspeitos)->toBe([],
            'O Filament v5 nunca chama canDelete(). Apontar essa trava faz o próximo mantenedor '
            .'registrar uma DeleteAction achando que está protegido.'
        );

    expect(File::get(base_path('app/Filament/App/Resources/Users/Pages/EditUser.php')))
        ->toContain('A trava é `UserResource::getDeleteAuthorizationResponse()`');
})->group('kit');
