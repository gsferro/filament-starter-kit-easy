<?php

use App\Console\Commands\KitInstall;
use App\Support\HostLocal;
use Illuminate\Support\Facades\File;
use Laravel\Prompts\ConfirmPrompt;
use Laravel\Prompts\Prompt;
use Laravel\Prompts\TextPrompt;

/**
 * A etapa de domínio local do `kit:install` — a linha no `hosts` e a `APP_URL`.
 *
 * IDs de CT em `wikis/specs/feat/kit-install-host-local/host-local-no-install/04-casos-de-teste.md`.
 *
 * Nenhum caso escreve no `hosts` do sistema nem no `.env` do projeto: o diretório-base, o
 * caminho do `hosts`, o executor da elevação, a sonda de resolução e o sistema operacional
 * são todos injetados. Errar aqui não seria retrabalho — seria alterar a máquina de quem roda
 * a suíte, e `git checkout` não desfaz isso.
 *
 * ## Por que existem DOIS executores de mentira
 *
 * Um executor que escrevesse "o que o cenário pediu" tornaria toda asserção de efeito
 * auto-realizável: ela mediria a fixture, não o código. O **registrador** guarda o comando e
 * não toca no arquivo — é com ele que se prova o oráculo (código de saída não decide nada). O
 * **intérprete** reconhece a FORMA do comando (`Add-Content` acrescenta, `Set-Content`
 * sobrescreve) e a aplica ao `hosts` de mentira — é ele que torna falsificável o mutante que
 * apagaria o `hosts` da máquina inteira e ainda assim conteria a linha, o domínio, o `ascii` e
 * a elevação.
 *
 * ## O que NÃO é testado aqui, e por quê
 *
 * A janela de UAC de verdade. Nenhuma suíte a alcança; ela é verificação manual, com o mesmo
 * raciocínio que `tests/Kit/CustomizadorDaInstalacaoTest.php` já registra para o TTY do
 * Composer. O oráculo automatizável é a DECISÃO, não o efeito externo.
 */
beforeEach(function (): void {
    $this->base = sys_get_temp_dir().'/kit-host-'.bin2hex(random_bytes(4));

    File::ensureDirectoryExists($this->base);
    File::copy(base_path('.env.example'), $this->base.'/.env');

    // O `hosts` de mentira: um arquivo no diretório temporário, nunca o do sistema.
    $this->hosts = $this->base.'/hosts';
    File::put($this->hosts, "127.0.0.1\tlocalhost\n");

    config(['app.name' => 'Loja do Ferro', 'app.url' => 'http://localhost:8000']);

    // Só para capturar a saída em buffer; as perguntas vão pelo fallback (ver `responderPerguntas`).
    Prompt::fake([]);
});

afterEach(function (): void {
    File::deleteDirectory($this->base);
});

/**
 * A etapa apontada para o diretório temporário do caso.
 *
 * O padrão é **Windows** porque é o único sistema em que a etapa executa: os casos do Unix
 * pedem o `$so` explicitamente.
 */
function hostLocalNoTemp(?Closure $executor = null, ?Closure $resolvedor = null, string $so = 'Windows'): HostLocal
{
    return new HostLocal(
        test()->base,
        $executor ?? static fn (): int => 0,
        test()->hosts,
        $resolvedor ?? static fn (): ?string => null,
        $so,
    );
}

/** Guarda o comando recebido e devolve o código pedido, SEM tocar no arquivo. */
function registradorDeComandos(array &$comandos, int $codigo = 0): Closure
{
    return static function (string $comando) use (&$comandos, $codigo): int {
        $comandos[] = $comando;

        return $codigo;
    };
}

/**
 * Reconhece a forma do comando emitido e a aplica ao `hosts` de mentira.
 *
 * `Add-Content` acrescenta; `Set-Content` (ou qualquer outra forma) **sobrescreve** — é assim
 * que um comando que apagaria o arquivo da máquina fica vermelho aqui. Comando que a própria
 * sonda não reconheceria devolve falha e não escreve nada.
 *
 * **E ele confere o ALVO, não só a forma.** A primeira versão casava o caminho no regex e
 * DESCARTAVA o grupo: escrevia em `test()->hosts` qualquer que fosse o arquivo citado no
 * comando, e com isso um mutante que trocasse o alvo do `Add-Content` ficava verde em todos os
 * cenários de efeito — só CT-12 o pegava, e CT-12 é cenário de texto, não de efeito. Achado A9
 * da revisão de código, mesma família do arnês auto-realizável que a revisão adversarial já
 * havia corrigido.
 */
function interpretadorDeComandos(array &$comandos, int $codigo = 0): Closure
{
    return static function (string $comando) use (&$comandos, $codigo): int {
        $comandos[] = $comando;

        if (preg_match('~(Add-Content|Set-Content)\s+"([^"]*)"\s+"([^"]*)"~', $comando, $achado) !== 1) {
            return 1;
        }

        expect($achado[2])->toBe(
            test()->hosts,
            'o comando emitido escreve num arquivo diferente do que a etapa rele',
        );

        $carga = str_replace(['`n', '`t'], ["\n", "\t"], $achado[3]);

        $achado[1] === 'Add-Content'
            ? File::append(test()->hosts, $carga)
            : File::put(test()->hosts, $carga);

        return $codigo;
    };
}

/**
 * As perguntas, respondidas sem terminal — e com o objeto do prompt EXPOSTO.
 *
 * Simular teclas com `Prompt::fake([...])` não serve: o pacote exige terminal de verdade e o
 * `checkEnvironment()` dele lança exceção no Windows, que é justamente o sistema desta etapa. O
 * caminho que roda de verdade lá é o **fallback** — `ConfiguresPrompts::configurePrompts()` o
 * liga em `windows_os() || runningUnitTests()` —, e registrá-lo aqui dá o que simulação de tecla
 * nenhuma daria: o `label`, o `default` e o `validate` que o código construiu.
 *
 * `null` na lista de respostas é "apertei Enter": o default do próprio prompt. O registro do
 * Laravel volta sozinho no próximo comando, porque `Command::run()` reconfigura os fallbacks.
 *
 * @param  list<string|bool|null>  $respostas
 * @param  list<array<string, mixed>>  $feitas
 */
function responderPerguntas(array $respostas, array &$feitas): void
{
    $proxima = static function () use (&$respostas): string|bool|null {
        return array_shift($respostas);
    };

    ConfirmPrompt::fallbackUsing(static function (ConfirmPrompt $prompt) use ($proxima, &$feitas): bool {
        $feitas[]  = ['label' => $prompt->label, 'default' => $prompt->default, 'hint' => $prompt->hint];
        $resposta  = $proxima();

        return $resposta === null ? $prompt->default : (bool) $resposta;
    });

    TextPrompt::fallbackUsing(static function (TextPrompt $prompt) use ($proxima, &$feitas): string {
        while (true) {
            $feitas[]  = ['label' => $prompt->label, 'default' => $prompt->default, 'hint' => $prompt->hint];
            $resposta  = $proxima();
            $valor     = $resposta === null ? $prompt->default : (string) $resposta;
            $erro      = $prompt->validate === null ? null : ($prompt->validate)($valor);

            if ($erro === null) {
                return $valor;
            }

            $feitas[] = ['erro' => $erro];
        }
    });
}

/** Quantas linhas ATIVAS (não comentadas) do `hosts` apontam exatamente este domínio. */
function linhasAtivasDoHosts(string $dominio): int
{
    $ativas = 0;

    foreach (preg_split('/\R/', File::get(test()->hosts)) ?: [] as $linha) {
        $util = trim(explode('#', $linha)[0]);

        if ($util === '') {
            continue;
        }

        $nomes = preg_split('/\s+/', $util) ?: [];
        array_shift($nomes);

        foreach ($nomes as $nome) {
            if (strcasecmp($nome, $dominio) === 0) {
                $ativas++;
            }
        }
    }

    return $ativas;
}

/** O corpo do `handle()` do `kit:install`, em texto. */
function corpoDoHandle(): string
{
    $fonte = File::get((new ReflectionClass(KitInstall::class))->getFileName());

    preg_match('~public function handle\(\): int\s*\{(.*?)\n    \}~s', $fonte, $achado);

    return $achado[1] ?? '';
}

/** O corpo de um método do `kit:install`, em texto. */
function corpoDoMetodoDoKitInstall(string $metodo): string
{
    $fonte = File::get((new ReflectionClass(KitInstall::class))->getFileName());

    preg_match('~function '.preg_quote($metodo, '~').'\([^)]*\)[^{]*\{(.*?)\n    \}~s', $fonte, $achado);

    return $achado[1] ?? '';
}

/*
|--------------------------------------------------------------------------
| R1 — a etapa acontece ao final do TRABALHO, e só quando há quem responda
|--------------------------------------------------------------------------
*/

/**
 * CT-01 — a ordem, ancorada nos VIZINHOS imediatos.
 *
 * Ancorar em extremos distantes deixaria passar a chamada logo depois das perguntas, antes de
 * migrate, seed e build. E chamar a etapa depois do `banner()` — o fim literal do `handle()` —
 * imprimiria `http://localhost:8000/app` logo abaixo do endereço que a pessoa acabou de
 * escolher: a feature funcionando e parecendo não ter funcionado.
 */
it('[CT-01] oferece o host local depois do trabalho de instalacao e antes das URLs', function (): void {
    $corpo = corpoDoHandle();

    expect(str_contains($corpo, '$this->oferecerHostLocal();'))
        ->toBeTrue('o handle() nao chama a etapa do host local');

    $posicao = static fn (string $chamada): int => (int) strpos($corpo, $chamada);

    expect($posicao('$this->migrar()'))->toBeLessThan($posicao('$this->oferecerHostLocal()'))
        ->and($posicao('$this->construirFrontend()'))->toBeLessThan($posicao('$this->oferecerHostLocal()'))
        ->and($posicao('$this->oferecerHostLocal()'))->toBeLessThan($posicao('$this->banner()'));

    preg_match_all('~\$this->(\w+)\(~', substr($corpo, $posicao('$this->oferecerHostLocal()')), $depois);

    expect($depois[1])->toBe(
        ['oferecerHostLocal', 'banner', 'resumoDaCustomizacao', 'oferecerTestes', 'oferecerEstrela'],
        'nenhum passo que escreve em banco ou em disco do projeto pode vir depois da etapa',
    );
})->group('kit');

/**
 * CT-02 — o gate, exercitado como DECISÃO isolada.
 *
 * `KitInstall::temTerminal()` devolve `true` sempre que `runningUnitTests()` é verdadeiro: um
 * caso escrito por `$this->artisan('kit:install')` nunca alcançaria o ramo "sem terminal" e
 * seria um ✅ falso. Por isso o gate é PARÂMETRO de `oferecer()` — o mesmo desenho de
 * `CustomizadorDaInstalacao::devePerguntar()`. A segunda metade do caso amarra o parâmetro à
 * decisão real do comando, senão um `oferecer(true)` fixo passaria aqui.
 */
it('[CT-02] sem alguem para responder nao pergunta, nao executa e nao escreve', function (): void {
    $feitas = [];
    responderPerguntas(['s', 'loja-do-ferro.test'], $feitas);

    $antes    = File::get($this->hosts);
    $comandos = [];

    $aviso = hostLocalNoTemp(registradorDeComandos($comandos))->oferecer(false);

    expect($aviso)->toBeNull()
        ->and($feitas)->toBe([])
        ->and(File::get($this->hosts))->toBe($antes)
        ->and(valorNoEnv('APP_URL'))->toBe('http://localhost:8000')
        ->and($comandos)->toBe([]);

    expect(str_contains(corpoDoMetodoDoKitInstall('oferecerHostLocal'), 'oferecer($this->temTerminal())'))
        ->toBeTrue('a decisao de terminal do comando precisa chegar a etapa');
})->group('kit');

/**
 * CT-29 — a etapa não depende de flag nenhuma, nos DOIS ramos.
 *
 * "Não depende de `--force`" mata `if ($force)` e **não** mata `if (! $force)`, que é a leitura
 * mais provável do requisito ("na reinstalação o host já existe, então pulo"). A guarda é de
 * fonte porque a partição é a ausência de condicional: rodar o `kit:install` inteiro em teste
 * custaria migrate, seed e build para medir um `if`.
 */
it('[CT-29] oferece o host local com e sem --force', function (): void {
    expect(str_contains(corpoDoHandle(), "\n        \$this->oferecerHostLocal();"))
        ->toBeTrue('a chamada esta aninhada em algum condicional do handle()');

    expect(corpoDoMetodoDoKitInstall('oferecerHostLocal'))
        ->not->toContain('force')
        ->not->toContain('custom');
})->group('kit');

/*
|--------------------------------------------------------------------------
| R2 — a oferta é opt-in, e a recusa não produz efeito nenhum
|--------------------------------------------------------------------------
*/

/** CT-03 — a oferta mostra um exemplo de URL construído com o nome do projeto. */
it('[CT-03] mostra na oferta um exemplo de URL derivado do nome do projeto', function (): void {
    $feitas = [];
    responderPerguntas([false], $feitas);

    hostLocalNoTemp()->oferecer(true);

    expect($feitas[0]['label'])->toContain('http://loja-do-ferro.test');
})->group('kit');

/**
 * CT-04 — recusar não toca no sistema nem no `.env`.
 *
 * O `Dado` põe o `hosts` no mundo de propósito: asserção de ausência só discrimina se, naquela
 * configuração, o efeito **poderia** ter acontecido. O caminho feliz acrescentaria a linha
 * exatamente nesta fixture.
 */
it('[CT-04] recusar o host local nao toca no hosts nem no .env', function (): void {
    $feitas = [];
    responderPerguntas([false, 'loja-do-ferro.test'], $feitas);

    $antes    = File::get($this->hosts);
    $comandos = [];

    $aviso = hostLocalNoTemp(registradorDeComandos($comandos))->oferecer(true);

    expect($aviso)->toBeNull()
        ->and(File::get($this->hosts))->toBe($antes)
        ->and(valorNoEnv('APP_URL'))->toBe('http://localhost:8000')
        ->and(config('app.url'))->toBe('http://localhost:8000')
        ->and($comandos)->toBe([])
        ->and($feitas)->toHaveCount(1, 'a segunda pergunta nao pode ser exibida depois do nao');
})->group('kit');

/** CT-05 — apertar Enter na oferta equivale a recusar: o default é negativo. */
it('[CT-05] apertar Enter na oferta equivale a recusar', function (): void {
    $feitas = [];

    // `null` é o Enter: a resposta vira o default do próprio prompt, que é o que se mede aqui.
    responderPerguntas([null, 'loja-do-ferro.test'], $feitas);

    $antes    = File::get($this->hosts);
    $comandos = [];

    hostLocalNoTemp(registradorDeComandos($comandos))->oferecer(true);

    expect($feitas[0]['default'])->toBeFalse('o Enter da oferta precisa recusar')
        ->and(File::get($this->hosts))->toBe($antes)
        ->and(valorNoEnv('APP_URL'))->toBe('http://localhost:8000')
        ->and($comandos)->toBe([]);
})->group('kit');

/*
|--------------------------------------------------------------------------
| R3 — a sugestão deriva do nome escolhido antes, e o digitado vence
|--------------------------------------------------------------------------
*/

/**
 * CT-06 — a sugestão é o nome transformado em slug, com o mesmo piso do nome dos containers.
 *
 * As três últimas linhas são a borda: `Str::slug()` de nome só com símbolos devolve string
 * vazia, e sem o piso a sugestão viraria `.test` sozinho.
 */
it('[CT-06] sugere o dominio a partir do nome do projeto', function (string $nome, string $dominio): void {
    config(['app.name' => $nome]);

    expect(hostLocalNoTemp()->dominioSugerido())->toBe($dominio);
})->with([
    'nome comum, com espaços'          => ['Loja do Ferro', 'loja-do-ferro.test'],
    'acento e símbolo'                 => ['Ação & Cia', 'acao-cia.test'],
    'acento e dígito'                  => ['Clínica 24h', 'clinica-24h.test'],
    'borda: slug vazio'                => ['---', 'starter-kit.test'],
    'borda: só símbolos fora do ASCII' => ['✳ ✳ ✳', 'starter-kit.test'],
    'borda: nome vazio'                => ['', 'starter-kit.test'],
])->group('kit');

/** CT-07 — a sugestão já vem escrita na segunda pergunta, e confirmar sem digitar a escolhe. */
it('[CT-07] traz a sugestao preenchida na pergunta do dominio', function (): void {
    $feitas = [];

    // Sim na oferta, e Enter na segunda pergunta: o valor usado tem de ser o default dela.
    responderPerguntas([true, null], $feitas);

    $comandos = [];

    hostLocalNoTemp(interpretadorDeComandos($comandos))->oferecer(true);

    expect($feitas)->toHaveCount(2)
        ->and($feitas[1]['default'])->toBe('loja-do-ferro.test')
        ->and(valorNoEnv('APP_URL'))->toBe('http://loja-do-ferro.test')
        ->and(linhasAtivasDoHosts('loja-do-ferro.test'))->toBe(1);
})->group('kit');

/**
 * CT-08 — a sugestão sai do nome recém-escolhido, não do que está gravado em disco.
 *
 * A divergência é deliberada: `config('app.name')` já realinhado em memória contra o `.env` em
 * disco ainda com o valor antigo. Sem ela, "lê de `config()`" e "relê o `.env`" produzem o
 * mesmo observável — e a segunda leitura sairia **vazia** nos caminhos não-interativos.
 */
it('[CT-08] tira a sugestao do nome em memoria, nao do .env em disco', function (): void {
    File::put($this->base.'/.env', "APP_NAME=Laravel\nAPP_URL=http://localhost:8000\n");

    config(['app.name' => 'Loja do Ferro']);

    expect(hostLocalNoTemp()->dominioSugerido())->toBe('loja-do-ferro.test');
})->group('kit');

/** CT-26 — o domínio digitado vence a sugestão. */
it('[CT-26] usa o dominio digitado, e nao a sugestao', function (): void {
    $feitas = [];

    // O valor digitado é DIFERENTE da sugestão de propósito: só assim se distingue a
    // implementação que usa a resposta da que exibe o prompt e descarta o retorno dele.
    responderPerguntas([true, 'outro-nome.test'], $feitas);

    $comandos = [];

    hostLocalNoTemp(interpretadorDeComandos($comandos))->oferecer(true);

    expect($feitas[1]['default'])->toBe('loja-do-ferro.test')
        ->and(linhasAtivasDoHosts('outro-nome.test'))->toBe(1)
        ->and(File::get($this->hosts))->not->toContain('loja-do-ferro.test')
        ->and(valorNoEnv('APP_URL'))->toBe('http://outro-nome.test');
})->group('kit');

/*
|--------------------------------------------------------------------------
| R4 — a entrada é validada antes de qualquer efeito, e a recusa é visível
|--------------------------------------------------------------------------
| `@premissa P1`: recusa com mensagem e repergunta. Se a premissa cair, as
| entradas passam a ser aceitas — mas o invariante de CT-11 e CT-30 fica.
*/

/** CT-09 — domínio malformado é recusado, com motivo e sem efeito nenhum. */
it('[CT-09] recusa dominio malformado, com motivo e sem efeito', function (string $entrada): void {
    $antes    = File::get($this->hosts);
    $comandos = [];

    $aviso = hostLocalNoTemp(registradorDeComandos($comandos))->processar($entrada);

    expect($aviso)->toBeString()
        ->and($aviso)->toContain($entrada)
        ->and(File::get($this->hosts))->toBe($antes)
        ->and(valorNoEnv('APP_URL'))->toBe('http://localhost:8000')
        ->and($comandos)->toBe([]);
})->with([
    'traz esquema'             => ['http://loja.test'],
    'traz caminho'             => ['loja.test/app'],
    'traz espaço'              => ['loja do ferro.test'],
    'sufixo reservado ao mDNS' => ['loja.local'],
    'fecha o argumento'        => ["loja.test'; Remove-Item C:\\"],
    'vazio'                    => [''],
])->group('kit');

/**
 * CT-09 (segunda metade) — a recusa é VISÍVEL, e a pergunta volta.
 *
 * Quatro asserções de ausência não distinguem "recusou e avisou" de "encerrou em silêncio",
 * que deixaria quem instala achando que o host foi cadastrado. Aqui o estado de erro tem
 * destino alcançável: a mensagem aparece citando o que foi digitado, e o domínio que vale no
 * fim é o **segundo** — prova de que a pergunta foi feita de novo.
 */
it('[CT-09] exibe o motivo da recusa e pergunta o dominio de novo', function (): void {
    $recusado = 'http://loja.test';
    $feitas   = [];

    responderPerguntas([true, $recusado, 'loja-do-ferro.test'], $feitas);

    $comandos = [];

    hostLocalNoTemp(interpretadorDeComandos($comandos))->oferecer(true);

    $erros = array_values(array_filter($feitas, static fn (array $passo): bool => isset($passo['erro'])));

    expect($erros)->toHaveCount(1, 'a entrada recusada precisa produzir uma mensagem visivel')
        ->and($erros[0]['erro'])->toContain($recusado)
        ->and($erros[0]['erro'])->toContain('só o nome, sem http://')
        ->and($feitas)->toHaveCount(4, 'depois do erro a pergunta do dominio precisa voltar')
        ->and($feitas[3]['label'])->toBe($feitas[1]['label'])
        ->and(valorNoEnv('APP_URL'))->toBe('http://loja-do-ferro.test')
        ->and(File::get($this->hosts))->not->toContain($recusado)
        ->and($comandos)->toHaveCount(1);
})->group('kit');

/** CT-10 — um domínio bem formado é aceito e chega inteiro ao arquivo. */
it('[CT-10] aceita dominio bem formado e o grava inteiro no arquivo', function (): void {
    $comandos = [];

    $aviso = hostLocalNoTemp(interpretadorDeComandos($comandos))->processar('loja-do-ferro.test');

    expect($aviso)->toBeNull()
        ->and(linhasAtivasDoHosts('loja-do-ferro.test'))->toBe(1)
        ->and(File::get($this->hosts))->toContain("127.0.0.1\tloja-do-ferro.test");
})->group('kit');

/** CT-11 — quebra de linha na entrada não injeta linha no `hosts` nem chave no `.env`. */
it('[CT-11] nao deixa quebra de linha injetar linha no hosts nem chave no .env', function (): void {
    $chavesAntes = count(Dotenv\Dotenv::parse(envDoTeste()));
    $linhasAntes = count(preg_split('/\R/', File::get($this->hosts)) ?: []);
    $comandos    = [];

    hostLocalNoTemp(interpretadorDeComandos($comandos))
        ->processar("loja-do-ferro.test\n127.0.0.1 invasor.test");

    expect(File::get($this->hosts))->not->toContain('invasor.test')
        ->and(count(preg_split('/\R/', File::get($this->hosts)) ?: []))->toBe($linhasAntes)
        ->and(count(Dotenv\Dotenv::parse(envDoTeste())))->toBe($chavesAntes)
        ->and(Dotenv\Dotenv::parse(envDoTeste()))->not->toHaveKey('127.0.0.1 invasor.test');
})->group('kit');

/**
 * CT-40 — valor limite do comprimento, e ele é o do DNS (RFC 1035 §2.3.4).
 *
 * Achado A7: a validação não tinha limite nenhum, e um domínio de 900 octetos — ou um rótulo de
 * 200 — entrava no `hosts` e na `APP_URL` sem uma palavra. Nenhum resolvedor os aceita depois, e
 * a linha fica no arquivo de sistema. As linhas escritas são as **fronteiras**: o último aceito e
 * o primeiro recusado, que é onde um `<` trocado por `<=` mora. O valor interior já é exercido
 * por todos os outros casos do arquivo, que usam domínios curtos.
 */
it('[CT-40] recusa o dominio que passa do limite de comprimento do DNS', function (string $dominio, bool $aceita): void {
    $antes    = File::get($this->hosts);
    $comandos = [];

    $aviso = hostLocalNoTemp(interpretadorDeComandos($comandos))->processar($dominio);

    if ($aceita) {
        expect($aviso)->toBeNull()
            ->and(linhasAtivasDoHosts($dominio))->toBe(1);

        return;
    }

    expect($aviso)->toBeString()
        ->and($aviso)->toContain($dominio)
        ->and(File::get($this->hosts))->toBe($antes)
        ->and($comandos)->toBe([]);
})->with([
    'maior rótulo: 63 octetos, dentro'  => [str_repeat('a', 63).'.test', true],
    'maior rótulo: 64 octetos, fora'    => [str_repeat('a', 64).'.test', false],
    'nome inteiro: 253 octetos, dentro' => [
        str_repeat('a', 63).'.'.str_repeat('b', 63).'.'.str_repeat('c', 63).'.'.str_repeat('d', 61),
        true,
    ],
    'nome inteiro: 254 octetos, fora' => [
        str_repeat('a', 63).'.'.str_repeat('b', 63).'.'.str_repeat('c', 63).'.'.str_repeat('d', 62),
        false,
    ],
])->group('kit');

/*
|--------------------------------------------------------------------------
| R5 — o cadastro reproduz o procedimento documentado, e o oráculo é o arquivo
|--------------------------------------------------------------------------
*/

/**
 * CT-12 — oráculo DOCUMENTAL: o comando emitido é o da página do repositório.
 *
 * RQ-06 é literal — "rode conforme a documentação". Sem esta amarra, o comando poderia
 * divergir da página e nada ficaria vermelho: a documentação continuaria descrevendo um
 * procedimento e o kit rodando outro.
 *
 * O comando documentado é um GABARITO com duas variáveis: o domínio e o caminho do `hosts`.
 * A página escreve `$env:windir\…` porque quem a lê está colando dentro de um PowerShell; o
 * código já tem a variável expandida, porque é o mesmo caminho que ele relê depois. As duas
 * substituições acontecem aqui, e quem fixa a segunda contra o oráculo é **CT-35**.
 */
it('[CT-12] emite o mesmo comando de elevacao que a documentacao ensina', function (): void {
    $normalizar = static fn (string $texto): string => trim((string) preg_replace('/\s+/', ' ', $texto));

    preg_match(
        '~```powershell\R(Start-Process.*?)\R```~s',
        File::get(base_path('docs/pt/comecar/dominio-local.md')),
        $achado,
    );

    expect($achado[1] ?? '')->not->toBe('', 'a pagina dominio-local.md deixou de trazer o comando de elevacao');

    // Sem `hosts` injetado: aqui o caminho do sistema é parte do que se compara.
    $etapa   = new HostLocal($this->base, so: 'Windows');
    $comando = $etapa->comandoDeElevacao('loja-do-ferro.test');

    $documentado = $normalizar(str_replace(
        ['meu-projeto.test', '$env:windir\System32\drivers\etc\hosts'],
        ['loja-do-ferro.test', $etapa->caminhoDoHosts()],
        $achado[1],
    ));

    expect($normalizar($comando))->toBe($documentado)
        ->and($comando)->toContain('-Verb RunAs')
        ->and($comando)->toContain('-Encoding ascii')
        ->and($comando)->toContain('System32\drivers\etc\hosts')
        ->and($comando)->not->toContain('/etc/hosts');
})->group('kit');

/**
 * CT-35 — o comando escreve no MESMO arquivo que o oráculo relê.
 *
 * O achado A2 da revisão de código: o comando citava `$env:windir\System32\…` e o oráculo lia
 * `C:\Windows\System32\…` fixo. Numa máquina com `%windir%` diferente a linha ENTRA e o oráculo
 * não a vê — a etapa reporta falha numa instalação bem-sucedida, a `APP_URL` nunca é ajustada,
 * e o aviso manda colar de novo um comando que **duplicaria** a linha. CT-12 não pega isso: lá
 * os dois textos batem porque a divergência está entre o comando e o LEITOR, não entre o
 * comando e a página.
 */
it('[CT-35] emite um comando que escreve no mesmo arquivo que a etapa rele', function (): void {
    $etapa   = hostLocalNoTemp();
    $comando = $etapa->comandoDeElevacao('loja-do-ferro.test');

    expect($comando)->toContain($etapa->caminhoDoHosts())
        ->and($comando)->not->toContain('$env:');
})->group('kit');

/**
 * CT-27 — o comando emitido PRESERVA o que já estava no arquivo.
 *
 * O mutante é `Set-Content` (ou um redirecionamento `>`) no lugar de `Add-Content`: ele monta
 * um comando que contém a linha, o domínio, o `ascii` e a elevação — e **apaga o `hosts` da
 * máquina de quem instala**. Só um executor que distingue as duas formas o revela.
 */
it('[CT-27] emite um comando que preserva o conteudo anterior do hosts', function (): void {
    File::put($this->hosts, "127.0.0.1\tlocalhost\n10.0.0.5 intranet.example\n");

    $comandos = [];

    hostLocalNoTemp(interpretadorDeComandos($comandos))->processar('loja-do-ferro.test');

    $conteudo = File::get($this->hosts);
    $linhas   = array_filter(preg_split('/\R/', $conteudo) ?: [], static fn (string $l): bool => trim($l) !== '');

    expect($conteudo)->toContain('127.0.0.1')
        ->and($conteudo)->toContain('10.0.0.5 intranet.example')
        ->and(linhasAtivasDoHosts('loja-do-ferro.test'))->toBe(1)
        ->and($linhas)->toHaveCount(3);
})->group('kit');

/**
 * CT-30 — nada do que foi digitado vira comando novo no executor.
 *
 * A fronteira de confiança aqui não é entre contas: é entre o terminal comum, onde a pessoa
 * digita, e o processo **Administrador**, que escreve no arquivo de sistema. Uma aspa que
 * fechasse o argumento emendaria um segundo comando do outro lado dessa fronteira.
 */
it('[CT-30] nao deixa a entrada virar comando novo no executor elevado', function (): void {
    $comandos = [];

    hostLocalNoTemp(registradorDeComandos($comandos))->processar("loja.test'; Remove-Item C:\\");

    expect($comandos)->toHaveCount(0);

    foreach ($comandos as $comando) {
        expect($comando)->not->toContain('Remove-Item');
    }
})->group('kit');

/**
 * CT-13 — código de saída de sucesso com o arquivo intocado NÃO confirma o cadastro.
 *
 * `Start-Process -Verb RunAs` sobe outro processo e não devolve código confiável; com o UAC
 * negado o retorno continua sendo 0. Confiar nele gravaria uma `APP_URL` que não resolve.
 */
it('[CT-13] nao confirma o cadastro pelo codigo de saida quando o arquivo nao mudou', function (): void {
    $comandos = [];

    $aviso = hostLocalNoTemp(registradorDeComandos($comandos, codigo: 0))->processar('loja-do-ferro.test');

    expect($aviso)->toBeString()
        ->and($aviso)->toContain('loja-do-ferro.test')
        ->and($aviso)->toContain('Start-Process pwsh -Verb RunAs')
        ->and(valorNoEnv('APP_URL'))->toBe('http://localhost:8000')
        ->and(config('app.url'))->toBe('http://localhost:8000');
})->group('kit');

/** CT-14 — código de saída de falha com a linha presente CONFIRMA o cadastro. */
it('[CT-14] confirma o cadastro quando a linha entrou, mesmo com codigo de falha', function (): void {
    $comandos = [];

    $aviso = hostLocalNoTemp(interpretadorDeComandos($comandos, codigo: 1))->processar('loja-do-ferro.test');

    expect($aviso)->toBeNull()
        ->and(valorNoEnv('APP_URL'))->toBe('http://loja-do-ferro.test');
})->group('kit');

/**
 * CT-15 — fora do Windows a etapa instrui **por aviso**, e ainda assim ajusta a URL.
 *
 * A coluna da `APP_URL` existe porque ADR-03 decide o caso: no Unix o `.env` é ajustado **sem**
 * execução nenhuma. Sem ela, "não ajusta nada no Unix" e "ajusta" produziriam o mesmo
 * observável, e a premissa de falha fechado do Windows seria lida como se valesse nos três.
 *
 * ## A coluna do AVISO entrou em 2026-09-22, e o caso antes afirmava o defeito
 *
 * A primeira versão exigia `$aviso` **nulo nos três sistemas** e procurava a instrução do `sudo`
 * na SAÍDA do prompt. Isso descrevia exatamente o defeito RD-03: no Unix o `.env` era reescrito,
 * a linha do `hosts` não entrava, e a etapa devolvia `null` — então `KitInstall::banner()` fechava
 * a instalação anunciando três endereços que não resolvem, sem aviso nenhum. O `note()` que o
 * caso conferia sai na hora e, quando o banner aparece, já rolou para fora da tela.
 *
 * Aviso e saída não são o mesmo observável: o aviso é **coletado** e reimpresso no fim; a saída é
 * transitória. O caso afirmava o transitório e por isso não via a diferença.
 *
 * Agora a expectativa é **por sistema**: no Windows o cadastro acontece e o aviso é `null`; no
 * Unix o cadastro não acontece e o aviso carrega a instrução manual. Achado do `fw-revisor-diff`.
 */
it('[CT-15] fora do Windows instrui em vez de executar, e ajusta a URL', function (
    string $so,
    string $caminho,
    int $execucoes,
    bool $instrui,
): void {
    Prompt::fake([]);

    // Sufixo, e não igualdade: no Windows a pasta do sistema sai de `%windir%` (ver CT-35), e
    // fixar `C:\Windows` aqui reintroduziria a segunda fonte de verdade que A2 apontou.
    expect((new HostLocal($this->base, so: $so))->caminhoDoHosts())->toEndWith($caminho);

    $comandos = [];

    $aviso = hostLocalNoTemp(interpretadorDeComandos($comandos), so: $so)->processar('loja-do-ferro.test');

    expect($comandos)->toHaveCount($execucoes)
        ->and(valorNoEnv('APP_URL'))->toBe('http://loja-do-ferro.test');

    if ($instrui) {
        // Unix: a linha do `hosts` ficou pendente, e quem avisa é o AVISO — que o banner reimprime.
        expect($aviso)
            ->toContain('loja-do-ferro.test')
            ->toContain("sudo tee -a {$this->hosts}")
            ->toContain('a APP_URL já tinha sido ajustada');

        return;
    }

    // Windows: o cadastro aconteceu de fato, então não há o que avisar.
    expect($aviso)->toBeNull();

    Prompt::assertStrippedOutputDoesntContain('sudo');
})->with([
    'Linux'   => ['Linux', '/etc/hosts', 0, true],
    'Darwin'  => ['Darwin', '/etc/hosts', 0, true],
    'Windows' => ['Windows', '\System32\drivers\etc\hosts', 1, false],
])->group('kit');

/*
|--------------------------------------------------------------------------
| R6 — rodar de novo não duplica nada, nem quando o arquivo muda no meio
|--------------------------------------------------------------------------
*/

/**
 * CT-28 — a etapa reconhece a linha que ELA MESMA escreveu.
 *
 * Idempotência de verdade: a segunda execução acontece com a linha escrita pelo próprio
 * código, e não semeada pela fixture. É o que revela o mutante de gravar num formato
 * (`0.0.0.0`, comentário de assinatura, separador exótico) que a **própria sonda** não
 * reconhece — e que duplicaria a linha a cada `kit:install`.
 */
it('[CT-28] reconhece a linha que ela mesma escreveu e nao duplica', function (): void {
    $comandos = [];
    $etapa    = hostLocalNoTemp(interpretadorDeComandos($comandos));

    $etapa->processar('loja-do-ferro.test');
    $etapa->processar('loja-do-ferro.test');

    expect(linhasAtivasDoHosts('loja-do-ferro.test'))->toBe(1)
        ->and($comandos)->toHaveCount(1);
})->group('kit');

/**
 * CT-16 — `@premissa P3`: com o domínio já resolvendo, a etapa não mexe no arquivo.
 *
 * E ajusta a `APP_URL` assim mesmo, porque o domínio **de fato resolve**: recusar o ajuste
 * puniria quem já tinha feito o passo à mão.
 */
it('[CT-16] com o dominio ja resolvendo nao mexe no arquivo, e ajusta a URL', function (): void {
    File::put($this->hosts, "127.0.0.1\tlocalhost\n127.0.0.1\tloja-do-ferro.test\n");

    $antes    = File::get($this->hosts);
    $comandos = [];

    $aviso = hostLocalNoTemp(registradorDeComandos($comandos))->processar('loja-do-ferro.test');

    expect(File::get($this->hosts))->toBe($antes)
        ->and($comandos)->toBe([])
        ->and(valorNoEnv('APP_URL'))->toBe('http://loja-do-ferro.test')
        ->and($aviso)->toBeNull();
})->group('kit');

/**
 * CT-33 — o arquivo muda entre a sonda e a confirmação (TOCTOU).
 *
 * A janela é de MINUTOS: entre uma e outra há um diálogo de UAC esperando uma pessoa, e nesse
 * intervalo o Docker Desktop, uma VPN corporativa ou um segundo `kit:install` em outro
 * diretório reescrevem o mesmo arquivo. O executor deste caso não cadastra nada — ele encena o
 * outro processo escrevendo enquanto o diálogo espera.
 */
it('[CT-33] confere o arquivo depois da elevacao, e nao a sonda de antes', function (): void {
    $comandos = [];

    $outroProcesso = function (string $comando) use (&$comandos): int {
        $comandos[] = $comando;

        File::append($this->hosts, "127.0.0.1\tloja-do-ferro.test\n");

        return 1;
    };

    $aviso = hostLocalNoTemp($outroProcesso)->processar('loja-do-ferro.test');

    expect(linhasAtivasDoHosts('loja-do-ferro.test'))->toBe(1)
        ->and(File::get($this->hosts))->toContain('localhost')
        ->and($aviso)->toBeNull()
        ->and(valorNoEnv('APP_URL'))->toBe('http://loja-do-ferro.test');
})->group('kit');

/*
|--------------------------------------------------------------------------
| R7 — a URL nova vale no arquivo E em memória
|--------------------------------------------------------------------------
*/

/**
 * CT-18 — o ajuste vale no `.env` e em memória, na mesma execução.
 *
 * Escrever só o `.env` reintroduz o defeito que ADR-02 antecipa: o `banner()` lê
 * `config('app.url')` e imprimiria o endereço velho.
 */
it('[CT-18] ajusta a APP_URL no .env e no config em memoria', function (): void {
    $comandos = [];

    hostLocalNoTemp(interpretadorDeComandos($comandos))->processar('loja-do-ferro.test');

    expect(valorNoEnv('APP_URL'))->toBe('http://loja-do-ferro.test')
        ->and(config('app.url'))->toBe('http://loja-do-ferro.test');
})->group('kit');

/** CT-19 — a chave `APP_URL` termina única, qualquer que fosse o estado dela. */
it('[CT-19] deixa a chave APP_URL unica, preenchida, comentada ou ausente', function (string $estado): void {
    $env = envDoTeste();

    $env = match ($estado) {
        'comentada' => (string) preg_replace('/^APP_URL=.*$/m', '# APP_URL=', $env),
        'ausente'   => (string) preg_replace('/^APP_URL=.*$\R/m', '', $env),
        default     => $env,
    };

    File::put($this->base.'/.env', $env);

    $antes    = Dotenv\Dotenv::parse(envDoTeste());
    $comandos = [];

    hostLocalNoTemp(interpretadorDeComandos($comandos))->processar('loja-do-ferro.test');

    $depois = Dotenv\Dotenv::parse(envDoTeste());

    expect(preg_match_all('/^#?\s*APP_URL=/m', envDoTeste()))->toBe(1)
        ->and($depois['APP_URL'])->toBe('http://loja-do-ferro.test');

    unset($antes['APP_URL'], $depois['APP_URL']);

    expect($depois)->toBe($antes, 'nenhuma outra chave do arquivo pode mudar de valor');
})->with(['preenchida', 'comentada', 'ausente'])->group('kit');

/**
 * CT-20 — `@premissa P2`: elevação negada no Windows não muda a URL do projeto.
 *
 * Gravar uma `APP_URL` que não resolve deixaria a impressão final da instalação com um
 * endereço morto. A última asserção vale qualquer que seja a resposta da premissa.
 */
it('[CT-20] com a elevacao negada nao muda a URL do projeto, e avisa', function (): void {
    $comandos = [];

    $aviso = hostLocalNoTemp(registradorDeComandos($comandos))->processar('loja-do-ferro.test');

    expect(valorNoEnv('APP_URL'))->toBe('http://localhost:8000')
        ->and(config('app.url'))->toBe('http://localhost:8000')
        ->and($aviso)->toBeString();
})->group('kit');

/*
|--------------------------------------------------------------------------
| R8 — nenhuma falha da etapa aborta a instalação
|--------------------------------------------------------------------------
*/

/** CT-21 — a elevação negada deixa um aviso com o comando pronto para colar. */
it('[CT-21] deixa um aviso com o comando pronto para colar', function (): void {
    $comandos = [];

    $aviso = hostLocalNoTemp(registradorDeComandos($comandos))->processar('loja-do-ferro.test');

    expect($aviso)->toContain('loja-do-ferro.test')
        ->and($aviso)->toContain('Start-Process pwsh -Verb RunAs');

    expect(corpoDoMetodoDoKitInstall('oferecerHostLocal'))
        ->toContain('$this->avisos[] = $aviso')
        ->not->toContain('FAILURE');
})->group('kit');

/** CT-22 — qualquer modo de falha da etapa vira aviso, e nenhuma exceção escapa. */
it('[CT-22] transforma qualquer modo de falha em aviso', function (Closure $arranjo): void {
    $comandos = [];
    $etapa    = $arranjo($comandos);

    $aviso = $etapa->processar('loja-do-ferro.test');

    expect($aviso)->toBeString()
        ->and($aviso)->toContain('loja-do-ferro.test');
})->with([
    'o executor lança exceção (pwsh ausente do PATH)' => [function (array &$comandos): HostLocal {
        return hostLocalNoTemp(static fn (): int => throw new RuntimeException('pwsh nao encontrado'));
    }],
    'o arquivo de hosts não pode ser lido' => [function (array &$comandos): HostLocal {
        File::delete(test()->hosts);
        File::ensureDirectoryExists(test()->hosts);

        return hostLocalNoTemp(registradorDeComandos($comandos));
    }],
    'o arquivo de hosts não existe no caminho' => [function (array &$comandos): HostLocal {
        File::delete(test()->hosts);

        return hostLocalNoTemp(registradorDeComandos($comandos));
    }],
])->group('kit');

/**
 * CT-22 (segunda metade) — o perímetro à prova de exceção é `oferecer()`, não `processar()`.
 *
 * Achado A1 da revisão de código. O `try/catch` ficava DENTRO de `processar()`, e as duas
 * perguntas — que são onde a exceção de verdade nasce — ficavam de fora. No Windows o Laravel
 * sempre cai no fallback do Symfony (`Prompt::fallbackWhen(windows_os() || runningUnitTests())`,
 * `vendor/laravel/framework/src/Illuminate/Foundation/Console/ConfiguresPrompts.php`), e o
 * `QuestionHelper` lança `MissingInputException` quando o STDIN acaba no meio de uma pergunta.
 * Como a etapa roda ANTES do `banner()`, essa exceção matava banner, resumo, lista de avisos,
 * oferta de testes e de estrela — depois de migrate, seed e build.
 */
it('[CT-22] nao deixa excecao da pergunta escapar da etapa', function (Closure $arranjo): void {
    $arranjo();

    $comandos = [];
    $aviso    = null;

    expect(function () use (&$aviso, &$comandos): void {
        $aviso = hostLocalNoTemp(registradorDeComandos($comandos))->oferecer(true);
    })->not->toThrow(Throwable::class);

    expect($aviso)->toBeString()
        ->and($aviso)->toContain('loja-do-ferro.test')
        ->and($comandos)->toBe([]);
})->with([
    'a pergunta da oferta lança exceção' => [function (): void {
        ConfirmPrompt::fallbackUsing(static fn (): bool => throw new RuntimeException('Aborted.'));
    }],
    'a pergunta do domínio lança exceção' => [function (): void {
        ConfirmPrompt::fallbackUsing(static fn (): bool => true);
        TextPrompt::fallbackUsing(static fn (): string => throw new RuntimeException('Aborted.'));
    }],
])->group('kit');

/**
 * CT-22 (terceira metade) — fora do Windows o aviso de falha instrui com `sudo`, não com UAC.
 *
 * Achado A4: o ramo de erro devolvia "Num PowerShell como administrador: Start-Process…" em
 * qualquer sistema. Quebra a ADR-03, que decide a assimetria Windows-executa/Unix-instrui, e
 * deixa quem está no Linux num beco sem saída — `instrucaoManual()` já existia e não era usada
 * ali. O aviso é o **destino alcançável** do estado de erro; destino escrito para outro sistema
 * operacional não é destino.
 */
it('[CT-22] fora do Windows o aviso de falha instrui com sudo, e nao com UAC', function (string $so): void {
    File::delete($this->hosts);

    $comandos = [];

    $aviso = hostLocalNoTemp(registradorDeComandos($comandos), so: $so)->processar('loja-do-ferro.test');

    expect($aviso)->toBeString()
        ->and($aviso)->toContain('sudo')
        ->and($aviso)->toContain($this->hosts)
        ->and($aviso)->not->toContain('Start-Process');
})->with(['Linux', 'Darwin'])->group('kit');

/**
 * CT-39 — o aviso de falha não AFIRMA o que pode ser falso.
 *
 * Achado A6: o aviso dizia **sempre** "a APP_URL continua como estava", e isso é falso em todo
 * caminho em que a etapa já reescreveu o `.env` antes de falhar.
 *
 * O arranjo é o de uma instância que processa duas vezes — o mesmo de CT-28, e o que acontece
 * de verdade num `kit:install --force` seguido de um segundo `--custom`. Na primeira passada o
 * domínio já resolvia e a `APP_URL` foi gravada; na segunda o arquivo de hosts desapareceu
 * (antivírus, VPN, outro processo) e a etapa cai no ramo de erro. O aviso dessa segunda passada
 * não pode dizer que a `APP_URL` está como estava: ela não está.
 *
 * A última asserção é o antídoto do remédio — apagar a frase em todos os casos "corrige" M62 e
 * introduz M63, tirando de quem falhou ANTES de qualquer escrita a única informação que o
 * tranquilizava.
 */
it('[CT-39] no aviso de falha, so afirma sobre a APP_URL o que e verdade', function (): void {
    $comandos = [];

    $etapa = hostLocalNoTemp(
        registradorDeComandos($comandos),
        resolvedor: static fn (): ?string => '127.0.0.1',
    );

    expect($etapa->processar('loja-do-ferro.test'))->toBeNull()
        ->and(valorNoEnv('APP_URL'))->toBe('http://loja-do-ferro.test');

    File::delete($this->hosts);

    $depoisDaEscrita = $etapa->processar('loja-do-ferro.test');

    expect($depoisDaEscrita)->toBeString()
        ->and($depoisDaEscrita)->not->toContain('continua como estava')
        ->and(valorNoEnv('APP_URL'))->toBe('http://loja-do-ferro.test');

    // O complementar, na mesma medida: quem falhou sem ter escrito nada continua sabendo disso.
    $semEscrita = hostLocalNoTemp(registradorDeComandos($comandos))->processar('loja-do-ferro.test');

    expect($semEscrita)->toContain('continua como estava');
})->group('kit');

/*
|--------------------------------------------------------------------------
| R9 — a escrita cai no .env do projeto que está sendo instalado
|--------------------------------------------------------------------------
*/

/** CT-23 — o ajuste de `APP_URL` cai no diretório recebido, e o `.env` de fora não é tocado. */
it('[CT-23] escreve no .env do diretorio recebido, e nao no de fora', function (): void {
    $fora = sys_get_temp_dir().'/kit-host-sentinela-'.bin2hex(random_bytes(4));

    File::ensureDirectoryExists($fora);
    File::put($fora.'/.env', 'APP_URL="http://sentinela.test"'.PHP_EOL);

    $comandos = [];

    hostLocalNoTemp(interpretadorDeComandos($comandos))->processar('loja-do-ferro.test');

    expect(valorNoEnv('APP_URL'))->toBe('http://loja-do-ferro.test')
        ->and(Dotenv\Dotenv::parse(File::get($fora.'/.env'))['APP_URL'])->toBe('http://sentinela.test');

    File::deleteDirectory($fora);
})->group('kit');

/**
 * CT-24 — a guarda de ORIGEM, por lista branca.
 *
 * CT-23 mata o mutante `base_path()` **executando-o** — e a execução já reescreveu o `.env` de
 * quem roda a suíte quando a asserção fica vermelha. Esta guarda reprova sem disparar escrita
 * nenhuma, e tem precedente direto em `tests/Kit/CustomizadorDaInstalacaoTest.php`.
 *
 * A asserção primária é a POSITIVA (todo caminho parte da propriedade injetada); a lista de
 * nomes é reforço — uma lista negra curta deixaria passar `getcwd()`, `realpath('.')` e
 * `app_path('../.env')`. O filtro de comentário é a regra de `.ai/rules/testes.md`: o arquivo
 * CITA o que proíbe, e é lá que está o porquê.
 */
it('[CT-24] monta o caminho do .env exclusivamente a partir do diretorio injetado', function (): void {
    $fonte = semComentarios(File::get((new ReflectionClass(HostLocal::class))->getFileName()));

    $linhasDeEnv = array_values(array_filter(
        explode("\n", $fonte),
        static fn (string $linha): bool => str_contains($linha, "'.env'"),
    ));

    expect($linhasDeEnv)->not->toBeEmpty();

    foreach ($linhasDeEnv as $linha) {
        expect($linha)->toContain('$this->base');
    }

    foreach (['base_path', 'app_path', 'config_path', 'storage_path', 'getcwd', 'realpath', '$_SERVER'] as $proibido) {
        expect($fonte)->not->toContain($proibido);
    }
})->group('kit');

/*
|--------------------------------------------------------------------------
| R10 — só uma resolução real do domínio exato conta como "já resolve"
|--------------------------------------------------------------------------
*/

/** CT-17 — só uma linha ativa do domínio exato conta como já resolvido. */
it('[CT-17] considera resolvido so o dominio exato em linha ativa', function (string $conteudo, bool $resolve): void {
    File::put($this->hosts, $conteudo.PHP_EOL);

    expect(hostLocalNoTemp()->jaResolve('loja-do-ferro.test'))->toBe($resolve);
})->with([
    'E2 — linha exata'                 => ['127.0.0.1 loja-do-ferro.test', true],
    'E2 — caixa alta'                  => ['127.0.0.1 LOJA-DO-FERRO.TEST', true],
    'E4 — tombstone, não resolve'      => ['# 127.0.0.1 loja-do-ferro.test', false],
    'E3 — prefixo, outro domínio'      => ['127.0.0.1 app.loja-do-ferro.test', false],
    'E3 — sufixo maior, outro domínio' => ['127.0.0.1 loja-do-ferro.test.br', false],
    'E1 — ausente'                     => ['127.0.0.1 localhost', false],
])->group('kit');

/** CT-25 — um domínio parecido no arquivo não impede nem contamina o cadastro. */
it('[CT-25] cadastra o dominio pedido mesmo com um parecido no arquivo', function (): void {
    File::put($this->hosts, "127.0.0.1\tapp.loja-do-ferro.test\n");

    $comandos = [];

    hostLocalNoTemp(interpretadorDeComandos($comandos))->processar('loja-do-ferro.test');

    expect(linhasAtivasDoHosts('loja-do-ferro.test'))->toBe(1)
        ->and(File::get($this->hosts))->toContain('app.loja-do-ferro.test')
        ->and(valorNoEnv('APP_URL'))->toBe('http://loja-do-ferro.test');
})->group('kit');

/**
 * CT-31 — cadastrar sobre uma linha comentada do mesmo domínio.
 *
 * O `#` é o tombstone deste domínio, e cadastrar sobre ele é o análogo de "criar, excluir e
 * recriar com o mesmo valor único". Descomentar a linha em vez de acrescentar uma nova perderia
 * o que a pessoa comentou de propósito.
 */
it('[CT-31] cadastra sobre a linha comentada sem apaga-la', function (): void {
    File::put($this->hosts, "# 127.0.0.1 loja-do-ferro.test\n");

    $comandos = [];

    hostLocalNoTemp(interpretadorDeComandos($comandos))->processar('loja-do-ferro.test');

    expect(linhasAtivasDoHosts('loja-do-ferro.test'))->toBe(1)
        ->and(File::get($this->hosts))->toContain('# 127.0.0.1 loja-do-ferro.test')
        ->and(valorNoEnv('APP_URL'))->toBe('http://loja-do-ferro.test');
})->group('kit');

/**
 * CT-32 — `@premissa P6`: o domínio já resolve FORA do arquivo `hosts`, e resolve para AQUI.
 *
 * Herd, Valet e dnsmasq respondem por `*.test` sem linha nenhuma no arquivo. Sondar só o
 * arquivo faria a etapa pedir elevação à toa e sujar o arquivo de sistema de quem já tinha a
 * máquina configurada.
 */
it('[CT-32] nao eleva quando o dominio ja resolve fora do arquivo hosts', function (): void {
    $antes    = File::get($this->hosts);
    $comandos = [];

    $aviso = hostLocalNoTemp(
        registradorDeComandos($comandos),
        resolvedor: static fn (string $dominio): ?string => $dominio === 'loja-do-ferro.test' ? '127.0.0.1' : null,
    )->processar('loja-do-ferro.test');

    expect($comandos)->toBe([])
        ->and(File::get($this->hosts))->toBe($antes)
        ->and(valorNoEnv('APP_URL'))->toBe('http://loja-do-ferro.test')
        ->and($aviso)->toBeNull();
})->group('kit');

/**
 * CT-36 — resolução que NÃO é loopback não conta como "já resolve" (RQ-12, Adendo 1).
 *
 * CT-32 sozinho passa com a sonda ingênua (`gethostbyname($d) !== $d`): lá o endereço que
 * responde É 127.0.0.1, e "aceita qualquer coisa" e "aceita só loopback" dão o mesmo
 * observável. A discriminância mora no endereço de fora — num DNS corporativo com curinga, ou
 * com NXDOMAIN sequestrado, a sonda ingênua faz a etapa PULAR a escrita, gravar a `APP_URL` e
 * devolver `null`: a instalação termina com o banner apontando para um endereço de terceiro,
 * sem um único aviso. `198.18.0.1` é do bloco de benchmark da RFC 2544, escolhido para não ser
 * confundido com rede de ninguém.
 */
it('[CT-36] nao trata resolucao fora do loopback como ja resolvido', function (): void {
    Prompt::fake([]);

    $comandos = [];

    hostLocalNoTemp(
        interpretadorDeComandos($comandos),
        resolvedor: static fn (string $dominio): ?string => $dominio === 'loja-do-ferro.test' ? '198.18.0.1' : null,
    )->processar('loja-do-ferro.test');

    expect($comandos)->toHaveCount(1, 'a etapa pulou a escrita por causa de um endereco que nao e desta maquina')
        ->and(linhasAtivasDoHosts('loja-do-ferro.test'))->toBe(1)
        ->and(valorNoEnv('APP_URL'))->toBe('http://loja-do-ferro.test');

    Prompt::assertStrippedOutputContains('198.18.0.1');
})->group('kit');

/**
 * CT-37 — linha no `hosts` apontando para FORA do loopback não conta como "já resolve aqui".
 *
 * ## A metade que faltava do CT-36
 *
 * `sondar()` pergunta duas coisas com um `||`: *"tem linha no arquivo?"* **ou** *"o DNS resolve
 * para loopback?"*. CT-36 cobre o segundo ramo — DNS respondendo `198.18.0.1`. O primeiro nunca
 * teve caso, e era ele que abria: `temLinhaAtiva()` descartava o ENDEREÇO da linha com
 * `array_shift()` e perguntava só se algum nome casava. Com `10.20.30.40 loja-do-ferro.test` no
 * arquivo, a etapa respondia "já resolve aqui".
 *
 * O `||` curto-circuita, então `ehLoopback()` — que existe exatamente para isto — **nunca era
 * consultado quando havia linha no arquivo**. A guarda estava escrita e não era alcançada.
 *
 * ## O cenário não é exótico
 *
 * É quem aponta o domínio para uma VM, para o WSL ou para uma máquina de staging: caso comum de
 * quem já tinha o ambiente montado antes de instalar o kit. O desfecho sem a guarda é o que o
 * docblock de `sondar()` declara proibido — *"'Já resolve' tem de significar 'resolve para
 * aqui'"*: a `APP_URL` é gravada, nenhum aviso é devolvido, e a instalação fecha imprimindo
 * `/app`, `/admin` e `/infra` de **outra máquina**.
 *
 * ## O oráculo é a ESCRITA, não o aviso
 *
 * O que prova a correção é a etapa **escrever** a linha de loopback: ela reconhece que o domínio
 * ainda não resolve para cá. Afirmar só o aviso deixaria passar uma implementação que avisasse e
 * não escrevesse. A linha de terceiro continua no arquivo — remover linha alheia está fora do
 * escopo desta feature —, então o arquivo passa a ter duas, e é a de loopback que vale no Windows.
 *
 * Achado do `fw-revisor-diff` (RD-01) na inspeção da `v0.38.0`.
 */
it('[CT-37] linha do hosts fora do loopback nao conta como ja resolvido', function (): void {
    Prompt::fake([]);

    // O arquivo já nomeia o domínio — mas apontando para outra máquina.
    File::put($this->hosts, File::get($this->hosts).'
10.20.30.40	loja-do-ferro.test
');

    $comandos = [];

    $aviso = hostLocalNoTemp(interpretadorDeComandos($comandos))->processar('loja-do-ferro.test');

    expect($comandos)->toHaveCount(
        1,
        'a etapa pulou a escrita por causa de uma linha que aponta para outra maquina',
    );

    expect(linhasAtivasDoHosts('loja-do-ferro.test'))->toBe(
        2,
        'a linha de loopback tinha de entrar ao lado da de terceiro, nao no lugar dela',
    );

    expect(valorNoEnv('APP_URL'))->toBe('http://loja-do-ferro.test')
        ->and($aviso)->toBeNull();
})->group('kit');

/*
|--------------------------------------------------------------------------
| R12 — domínio fora dos TLD reservados só passa com uma confirmação explícita
|--------------------------------------------------------------------------
| `Adendo 1` do `00-requisito.md` (RQ-10, RQ-11). Apontar um domínio público
| para 127.0.0.1 é irreversível POR ESTA FEATURE — remover a linha está fora
| de escopo por declaração do `00` —, então o único ponto de controle é antes
| de escrever.
*/

/**
 * CT-37 — a confirmação a mais existe e DIZ as três consequências.
 *
 * A segunda linha do dataset é a discriminante do sufixo: `loja.test.br` CONTÉM `.test` e não
 * termina nele. Uma verificação por `str_contains` o trataria como reservado e o cadastraria
 * calado — que é justamente o caminho do typo que o achado A5 descreve.
 */
it('[CT-37] pede uma confirmacao a mais para dominio publico, dizendo o que vai acontecer', function (string $dominio): void {
    $feitas = [];
    responderPerguntas([true, $dominio, false], $feitas);

    $comandos = [];

    hostLocalNoTemp(registradorDeComandos($comandos))->oferecer(true);

    expect($feitas)->toHaveCount(3, 'a confirmacao extra do dominio publico nao foi exibida');

    $extra = $feitas[2];

    expect($extra['label'])->toContain($dominio)
        ->and($extra['label'])->toContain('domínio público')
        ->and($extra['default'])->toBeFalse('o Enter da confirmacao extra precisa recusar')
        ->and($extra['hint'])->toContain('impedir o acesso ao site real')
        ->and($extra['hint'])->toContain('até ser removida à mão');
})->with([
    'domínio real, o typo do achado A5'        => ['fiotec.fiocruz.br'],
    'contém ".test" e não termina nele'        => ['loja.test.br'],
])->group('kit');

/** CT-37 — recusar a confirmação extra encerra a etapa sem efeito nenhum. */
it('[CT-37] recusar a confirmacao extra nao toca no hosts nem no .env', function (): void {
    $feitas = [];
    responderPerguntas([true, 'fiotec.fiocruz.br', false], $feitas);

    $antes    = File::get($this->hosts);
    $comandos = [];

    $aviso = hostLocalNoTemp(registradorDeComandos($comandos))->oferecer(true);

    expect($aviso)->toBeNull()
        ->and($comandos)->toBe([])
        ->and(File::get($this->hosts))->toBe($antes)
        ->and(valorNoEnv('APP_URL'))->toBe('http://localhost:8000')
        ->and(config('app.url'))->toBe('http://localhost:8000');
})->group('kit');

/**
 * CT-37 — aceitar a confirmação extra cadastra o domínio público.
 *
 * Sem este caso, "pergunta e ignora a resposta" passaria — é o mesmo defeito de M45, já visto
 * nesta feature na segunda pergunta.
 */
it('[CT-37] aceitar a confirmacao extra cadastra o dominio publico', function (): void {
    $feitas = [];
    responderPerguntas([true, 'fiotec.fiocruz.br', true], $feitas);

    $comandos = [];

    hostLocalNoTemp(interpretadorDeComandos($comandos))->oferecer(true);

    expect(linhasAtivasDoHosts('fiotec.fiocruz.br'))->toBe(1)
        ->and(valorNoEnv('APP_URL'))->toBe('http://fiotec.fiocruz.br');
})->group('kit');

/**
 * CT-38 — domínio em TLD reservado não pede confirmação nenhuma a mais.
 *
 * A partição complementar, e sem ela a regra é satisfeita por "perguntar sempre" — o que daria
 * ao caminho feliz do kit (o `.test` que o próprio comando sugere) uma terceira pergunta que
 * ninguém pediu, contra RQ-02 e RQ-05.
 */
it('[CT-38] nao pede confirmacao extra para TLD reservado ao uso local', function (string $tld): void {
    $dominio = 'loja-do-ferro.'.$tld;
    $feitas  = [];

    responderPerguntas([true, $dominio], $feitas);

    $comandos = [];

    hostLocalNoTemp(interpretadorDeComandos($comandos))->oferecer(true);

    expect($feitas)->toHaveCount(2, 'o TLD reservado pela RFC 6761 nao pode ganhar pergunta a mais')
        ->and(linhasAtivasDoHosts($dominio))->toBe(1)
        ->and(valorNoEnv('APP_URL'))->toBe('http://'.$dominio);
})->with(['test', 'localhost', 'example', 'invalid'])->group('kit');

/*
|--------------------------------------------------------------------------
| R11 — a documentação de instalação descreve a etapa, nos dois idiomas
|--------------------------------------------------------------------------
*/

/**
 * CT-34 — a cláusula de documentação com falsificador próprio.
 *
 * Delegar RQ-08 à suíte do site não serviria: ela é ANTERIOR à feature e não pode afirmar
 * sobre uma etapa que não existia. O mutante "a etapa foi entregue e a documentação não a
 * menciona" ficaria sem matador, contra uma cláusula explícita do requisito.
 */
it('[CT-34] descreve a etapa do host local na documentacao de instalacao', function (string $idioma, string $titulo): void {
    $secoes = array_values(array_filter(
        secoesDoMarkdown("docs/{$idioma}/comecar/instalacao-avancada.md"),
        static fn (string $secao): bool => str_starts_with($secao, $titulo),
    ));

    expect($secoes)->toHaveCount(1, "a pagina de instalacao em {$idioma} nao descreve a etapa do host local");

    expect($secoes[0])->toContain('kit:install')
        ->toContain('hosts')
        ->toContain('APP_URL')
        ->toContain('dominio-local');
})->with([
    'pt' => ['pt', 'Domínio local no fim da instalação'],
    'en' => ['en', 'Local domain at the end of the installation'],
])->skip(fn (): bool => ! naArvoreDoKit(), 'O site de documentação não viaja no projeto instalado.')->group('kit');
