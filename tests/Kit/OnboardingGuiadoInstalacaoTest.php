<?php

use App\Filament\Infra\Pages\Pulse;
use App\Filament\Pages\PrimeirosPassos;
use Database\Seeders\Onboarding\PulseSeeder;
use Database\Seeders\OnboardingSeeder;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Filament\Resources\Resource;
use Illuminate\Console\OutputStyle;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Finder\Finder;

/**
 * Tutorial guiado (wiki `onboarding-guiado`) — lote da instalação e dos arquivos: G11 (`kit:install`) e
 * G12 (a rule de manutenção, o inventário, a documentação e as duas rotas de entrega do kit).
 *
 * O `Então` de cada caso vem do `04-casos-de-teste.md`. Os casos que leem `.ai/rules/` ou `docs/` levam
 * `skip(! naArvoreDoKit())` (`.ai/rules/testes.md`): esses diretórios não existem num projeto instalado.
 */
afterEach(function (): void {
    foreach (File::glob(sys_get_temp_dir().'/kit_install_onboarding_'.getmypid().'_*') as $temporario) {
        File::deleteDirectory($temporario);
    }
});

/* ---------------------------------------------------------------------------
| R20 — o kit:install
--------------------------------------------------------------------------- */

/** Um diretório de instalação novo: o `.env.example` como `.env`, sem chave de aplicação. Devolve o caminho. */
function instalacaoNovaDoOnboarding(): string
{
    $base = sys_get_temp_dir().'/kit_install_onboarding_'.getmypid().'_'.Str::random(10);

    File::ensureDirectoryExists($base);
    File::put("{$base}/.env", File::get(base_path('.env.example')));

    app()->setBasePath($base);
    config(['app.key' => '']);

    return $base;
}

/**
 * O `kit:install` de verdade. `$interativo` responde às perguntas pelo `OutputStyle` (a resposta
 * da pergunta pedida, o padrão de cada uma das outras) e registra todas; sem ele o comando roda com
 * `--no-interaction` e, para ser de fato não interativo, o ambiente deixa de ser o de teste — o
 * `temTerminal()` do comando é `true` sob `runningUnitTests()`, e um terminal ausente é o que o
 * requisito chama de "não interativo".
 *
 * @param  array<string, mixed>  $opcoes
 * @param  array<string, bool>|null  $respostas  `null` = não interativo
 * @return array{saida: string, perguntas: list<array{texto: string, padrao: mixed}>}
 */
function rodarKitInstallDoOnboarding(array $opcoes, ?array $respostas = null): array
{
    $buffer    = new BufferedOutput;
    $perguntas = [];

    if ($respostas === null) {
        $ambiente = app()->environment();
        app()->instance('env', 'production');

        try {
            Artisan::call('kit:install', [...$opcoes, '--no-interaction' => true, '--no-npm' => true, '--no-support' => true], $buffer);
        } finally {
            app()->instance('env', $ambiente);
        }

        return ['saida' => $buffer->fetch(), 'perguntas' => $perguntas];
    }

    $estilo = Mockery::mock(OutputStyle::class.'[askQuestion]', [new ArrayInput([]), $buffer]);
    $estilo->shouldReceive('askQuestion')->andReturnUsing(
        function (Question $pergunta) use (&$perguntas, $respostas): mixed {
            $perguntas[] = ['texto' => $pergunta->getQuestion(), 'padrao' => $pergunta->getDefault()];

            foreach ($respostas as $trecho => $resposta) {
                if (str_contains($pergunta->getQuestion(), $trecho)) {
                    return $resposta;
                }
            }

            return $pergunta->getDefault();
        },
    );

    app()->bind(OutputStyle::class, fn () => $estilo);

    Artisan::call('kit:install', [...$opcoes, '--no-npm' => true, '--no-support' => true], $buffer);

    return ['saida' => $buffer->fetch(), 'perguntas' => $perguntas];
}

it('[CT-38] o kit:install semeia as jornadas conforme o modo e a resposta, e o tutorial fica desligado', function (string $modo, int $jornadas, int $passos, string $pergunta): void {
    $base = instalacaoNovaDoOnboarding();

    $execucao = match ($modo) {
        'interativo, padrão'        => rodarKitInstallDoOnboarding([], []),
        'interativo, sim'           => rodarKitInstallDoOnboarding([], ['Popular os tutoriais guiados?' => true]),
        'não interativo, com'       => rodarKitInstallDoOnboarding(['--onboarding' => true]),
        'não interativo, sem'       => rodarKitInstallDoOnboarding([]),
        'não interativo, sem banco' => rodarKitInstallDoOnboarding(['--no-seed' => true, '--onboarding' => true]),
    };

    $feitas = array_values(array_filter($execucao['perguntas'], fn (array $p): bool => str_contains($p['texto'], 'Popular os tutoriais guiados?')));

    expect(DB::table('onboarding_flows')->count())->toBe($jornadas)
        ->and(DB::table('onboarding_steps')->count())->toBe($passos);

    match ($pergunta) {
        'feita, padrão não'    => expect($feitas)->toHaveCount(1)->and($feitas[0]['padrao'])->toBeFalse(),
        'feita'                => expect($feitas)->toHaveCount(1),
        'nenhuma'              => expect($feitas)->toHaveCount(0)->and($execucao['perguntas'])->toBe([]),
        'resumo traz o seeder' => expect($feitas)->toHaveCount(0)
            ->and($execucao['saida'])->toContain('db:seed --class=OnboardingSeeder'),
    };

    $variavel = Dotenv\Dotenv::parse(File::get("{$base}/.env"))['KIT_ONBOARDING'] ?? null;

    expect(configuracaoGravada('onboarding_habilitado'))->toBeFalse()
        ->and($variavel)->not->toBe('true');
})->with([
    'interativo, padrão'        => ['interativo, padrão', 0, 0, 'feita, padrão não'],
    'interativo, sim'           => ['interativo, sim', 3, 32, 'feita'],
    'não interativo, com'       => ['não interativo, com', 3, 32, 'nenhuma'],
    'não interativo, sem'       => ['não interativo, sem', 0, 0, 'nenhuma'],
    'não interativo, sem banco' => ['não interativo, sem banco', 0, 0, 'resumo traz o seeder'],
]);

/* ---------------------------------------------------------------------------
| R21 — a rule de manutenção
--------------------------------------------------------------------------- */

/** Um glob de rule (`**` atravessa pastas, `*` não) vira expressão regular. */
function globDeRuleCasa(string $glob, string $caminho): bool
{
    $regex = preg_quote($glob, '~');
    $regex = str_replace(['\*\*/', '\*\*', '\*'], ['(?:.*/)?', '.*', '[^/]*'], $regex);

    return preg_match("~^{$regex}$~", $caminho) === 1;
}

it('[CT-39] a rule de onboarding está em .ai/rules, indexada, e cobre as telas dos três painéis e as features base sem tela', function (): void {
    $regras = collect(File::glob(base_path('.ai/rules/*.md')))
        ->filter(fn (string $arquivo): bool => str_contains(File::get($arquivo), 'OnboardingSeeder') && str_contains(basename($arquivo), 'onboarding'))
        ->values();

    expect($regras)->toHaveCount(1, 'a rule que cita o seeder pai de onboarding não existe em .ai/rules');

    $texto = File::get($regras[0]);

    preg_match('~^---\R(.*?)\R---~s', $texto, $frontmatter);
    preg_match_all("~^\s*-\s*'([^']+)'~m", $frontmatter[1] ?? '', $achados);

    $caminhos = $achados[1];

    expect($caminhos)->not->toBeEmpty('a rule não declara `paths:` no frontmatter');

    $casa = fn (string $arquivo): bool => collect($caminhos)->contains(fn (string $glob): bool => globDeRuleCasa($glob, $arquivo));

    // As telas dos três painéis, as compartilhadas (app/Filament/**) e os seeders de onboarding.
    foreach ([
        'app/Filament/Admin/Pages/Dashboard.php', 'app/Filament/App/Pages/Dashboard.php', 'app/Filament/Infra/Pages/Dashboard.php',
        'app/Filament/Pages/MyProfilePage.php', 'database/seeders/OnboardingSeeder.php', 'database/seeders/Onboarding/UsuariosSeeder.php',
    ] as $arquivo) {
        expect(file_exists(base_path($arquivo)))->toBeTrue("{$arquivo} não existe")
            ->and($casa($arquivo))->toBeTrue("os caminhos da rule não cobrem {$arquivo}");
    }

    // O código das seis features base sem tela própria: dock, busca ⌘K, bloqueio de tela, dashboard dinâmico, hub, agentes de IA.
    foreach ([
        'dock'                => 'app/Support/DockDeAtalhos.php',
        'busca ⌘K'            => 'app/Filament/Spotlight/AcoesDeCriacao.php',
        'bloqueio de tela'    => 'config/lockscreen.php',
        'dashboard dinâmico'  => 'app/Support/DashboardDinamico.php',
        'hub'                 => 'app/Filament/Admin/Pages/HubDeAdministracao.php',
        'agentes de IA'       => 'app/Support/AgentesDeIa.php',
    ] as $feature => $arquivo) {
        expect(file_exists(base_path($arquivo)))->toBeTrue("{$arquivo} ({$feature}) não existe")
            ->and($casa($arquivo))->toBeTrue("os caminhos da rule não casam o código de {$feature} ({$arquivo})");
    }

    // O corpo: revisar, atualizar ou criar o seeder filho, chamá-lo pelo pai e declarar a isenção com motivo.
    $corpo = Str::lower(Str::after($texto, '---'));

    foreach (['seeder filho', 'revis', 'atualiz', 'cri', 'onboardingseeder', 'isen', 'motivo'] as $trecho) {
        expect($corpo)->toContain($trecho);
    }

    // O index.md: uma linha com os mesmos caminhos apontando para ela.
    $linhaDoIndice = collect(explode("\n", File::get(base_path('.ai/rules/index.md'))))
        ->first(fn (string $linha): bool => str_contains($linha, '.ai/rules/'.basename($regras[0])));

    expect($linhaDoIndice)->not->toBeNull('o index.md não aponta para a rule');

    $doIndice = array_map('trim', explode(',', trim(explode('|', $linhaDoIndice)[1])));

    expect(collect($doIndice)->sort()->values()->all())->toBe(collect($caminhos)->sort()->values()->all());
})->skip(fn (): bool => ! naArvoreDoKit(), '`.ai/rules/` só existe na árvore do kit');

/* ---------------------------------------------------------------------------
| R22 — o inventário
--------------------------------------------------------------------------- */

/** Uma Page que ninguém declarou no inventário. */
class PaginaNovaDoOnboardingParaTeste extends Page {}

/** Um Resource que ninguém declarou no inventário. */
class RecursoNovoDoOnboardingParaTeste extends Resource {}

/**
 * As telas de painel sem seeder de onboarding mapeado (e chamado pelo pai) nem isenção com motivo —
 * a verificação do inventário como função dos painéis e das tabelas, para os controles negativos.
 *
 * @param  array<string, Filament\Panel>  $paineis
 * @param  array<string, array<class-string, class-string>>  $telas
 * @param  array<class-string, string>  $isentas
 * @param  array<string, array<class-string, string>>  $isentasNoPainel
 * @param  list<class-string>  $filhos
 * @return list<class-string>
 */
function telasDescobertasNoInventarioDoOnboarding(array $paineis, array $telas, array $isentas, array $isentasNoPainel, array $filhos): array
{
    $descobertas = [];

    foreach ($paineis as $id => $painel) {
        foreach (array_unique([...$painel->getPages(), ...$painel->getResources()]) as $tela) {
            $filho  = $telas[$id][$tela] ?? null;
            $motivo = $isentas[$tela] ?? $isentasNoPainel[$id][$tela] ?? null;

            $coberta = ($filho !== null && in_array($filho, $filhos, true))
                || (is_string($motivo) && trim($motivo) !== '');

            if (! $coberta) {
                $descobertas[] = $tela;
            }
        }
    }

    return array_values(array_unique($descobertas));
}

it('[CT-40] o inventário acusa exatamente a tela, a isenção ou o filho defeituoso', function (string $situacao): void {
    $telas           = OnboardingSeeder::TELAS;
    $isentas         = OnboardingSeeder::ISENTAS;
    $isentasNoPainel = OnboardingSeeder::ISENTAS_NO_PAINEL;
    $filhos          = [...OnboardingSeeder::FILHOS, ...OnboardingSeeder::FILHOS_DE_TENANCY];

    $esperadas = [];

    switch ($situacao) {
        case 'estado do kit':
            break;

        case 'page nova':
            Filament::getPanel('admin')->pages([PaginaNovaDoOnboardingParaTeste::class]);
            $esperadas = [PaginaNovaDoOnboardingParaTeste::class];
            break;

        case 'resource novo':
            painelRegistradoEmTeste('financeiro')->resources([RecursoNovoDoOnboardingParaTeste::class]);
            $esperadas = [RecursoNovoDoOnboardingParaTeste::class];
            break;

        case 'isenção sem motivo':
            $isentas[PrimeirosPassos::class] = '';
            $esperadas                       = [PrimeirosPassos::class];
            break;

        case 'filho não chamado':
            $filhos    = array_values(array_diff($filhos, [PulseSeeder::class]));
            $esperadas = [Pulse::class];
            break;
    }

    expect(telasDescobertasNoInventarioDoOnboarding(Filament::getPanels(), $telas, $isentas, $isentasNoPainel, $filhos))->toBe($esperadas);
})->with(['estado do kit', 'page nova', 'resource novo', 'isenção sem motivo', 'filho não chamado']);

/* ---------------------------------------------------------------------------
| R23 — a documentação
--------------------------------------------------------------------------- */

it('[CT-42] a página do tutorial guiado de cada idioma ensina a semear e tabela os seeders filhos', function (string $idioma): void {
    $arquivo = base_path("docs/{$idioma}/recursos/tutorial-guiado.md");

    expect(file_exists($arquivo))->toBeTrue("a página do tutorial guiado não existe em {$idioma}");

    $pagina = File::get($arquivo);

    // O comando do seeder pai e a opção do instalador.
    expect($pagina)->toContain('php artisan db:seed --class=OnboardingSeeder')
        ->and($pagina)->toContain('kit:install --onboarding');

    // Depois de semear, ligar na tela de configurações.
    expect(preg_match('~(ligue|ligar|turn on|enable|switch on)[^\n]{0,80}configuracoes-da-aplicacao~iu', $pagina))->toBe(1, 'a página não manda ligar o tutorial na tela de configurações');

    // Cada filho numa linha da tabela, com painel(is) e tela; os de multi-organização depois da marca de tenancy.
    $linhas  = explode("\n", $pagina);
    $marca   = collect($linhas)->search(fn (string $linha): bool => str_contains($linha, 'KIT_TENANCY'));
    $filhos  = collect((new Finder)->files()->in(database_path('seeders/Onboarding'))->name('*Seeder.php'))
        ->map(fn ($f): string => 'Database\\Seeders\\Onboarding\\'.$f->getBasename('.php'))
        ->reject(fn (string $classe): bool => (new ReflectionClass($classe))->isAbstract());

    expect($marca)->not->toBeFalse('a página não marca os seeders de multi-organização (KIT_TENANCY)');

    foreach ($filhos as $classe) {
        $nome  = class_basename($classe);
        $linha = collect($linhas)->search(fn (string $l): bool => str_contains($l, "`{$nome}`"));

        expect($linha)->not->toBeFalse("{$nome} não tem linha na tabela");

        $celulas = array_map('trim', explode('|', trim($linhas[$linha], '| ')));
        $paineis = collect(OnboardingSeeder::TELAS)->filter(fn (array $mapa): bool => in_array($classe, $mapa, true))->keys();

        expect(count($celulas))->toBeGreaterThanOrEqual(3);

        foreach ($paineis as $painel) {
            $this->assertStringContainsString($painel, $celulas[1], "{$nome}: o painel {$painel} não está na linha");
        }

        expect(trim($celulas[2], '—- '))->not->toBe('', "{$nome}: a linha não diz a tela");

        if (in_array($classe, OnboardingSeeder::FILHOS_DE_TENANCY, true)) {
            expect($linha)->toBeGreaterThan($marca, "{$nome} (multi-organização) está antes da marca de tenancy");
        }
    }

    // As telas excluídas do tutorial, com o motivo.
    $excluidas = $idioma === 'pt'
        ? ['login', 'cadastro', 'recuperação de senha', 'boas-vindas', 'widgets']
        : ['login', 'sign-up', 'password recovery', 'welcome', 'widgets'];

    foreach ($excluidas as $tela) {
        $this->assertStringContainsString($tela, Str::lower($pagina), "a página não cita a tela excluída: {$tela}");
    }
})->with(['pt', 'en'])->skip(fn (): bool => ! naArvoreDoKit(), '`docs/` não viaja no create-project');

/* ---------------------------------------------------------------------------
| R27 — as duas rotas de entrega
--------------------------------------------------------------------------- */

it('[CT-57] cada arquivo da feature está nas duas rotas de entrega', function (string $grupo): void {
    $arquivos = match ($grupo) {
        'seeder pai'            => ['database/seeders/OnboardingSeeder.php'],
        'seeders filhos'        => collect((new Finder)->files()->in(database_path('seeders/Onboarding'))->name('*.php'))
            ->map(fn ($f): string => 'database/seeders/Onboarding/'.$f->getFilename())->values()->all(),
        'migration de settings' => array_map(fn (string $a): string => 'database/settings/'.basename($a), File::glob(database_path('settings/*_add_onboarding_to_kit_settings.php'))),
        'rule'                  => ['.ai/rules/onboarding.md'],
        'config do pacote'      => ['config/filament-onboarding.php'],
    };

    expect($arquivos)->not->toBeEmpty();

    $ignorados = collect(explode("\n", File::get(base_path('.gitattributes'))))
        ->map(fn (string $linha): string => trim($linha))
        ->filter(fn (string $linha): bool => $linha !== '' && ! str_starts_with($linha, '#') && str_ends_with($linha, 'export-ignore'))
        ->map(fn (string $linha): string => trim(preg_split('~\s+~', $linha)[0], '/'))
        ->all();

    foreach ($arquivos as $arquivo) {
        expect(file_exists(base_path($arquivo)))->toBeTrue("{$arquivo} não existe");

        $fora = collect($ignorados)->contains(fn (string $padrao): bool => $arquivo === $padrao || str_starts_with($arquivo, $padrao.'/'));
        $kit  = collect(caminhosDoKit())->contains(fn (string $caminho): bool => $arquivo === $caminho || str_starts_with($arquivo, rtrim($caminho, '/').'/'));

        expect($fora)->toBeFalse("{$arquivo} está excluído do create-project (.gitattributes)")
            ->and($kit)->toBeTrue("{$arquivo} não está coberto pelos caminhos do kit:update");
    }
})->with(['seeder pai', 'seeders filhos', 'migration de settings', 'rule', 'config do pacote']);
