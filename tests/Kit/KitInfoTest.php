<?php

use App\Models\User;
use App\Settings\ConfiguracoesDoKit;
use Database\Seeders\PapeisSeeder;
use Database\Seeders\ShieldPermissionsSeeder;
use Database\Seeders\UsuarioAdminSeeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Spatie\LaravelSettings\Models\SettingsProperty;

/**
 * `kit:info` — o único caminho do kit que responde "como este projeto está customizado?".
 *
 * Três lugares respondiam pedaços: o resumo do `kit:install` aparece uma vez e some, a tela de
 * settings mostra o banco e nada do `.env`, e o `config:show` mostra a config efetiva sem dizer a
 * origem. Os casos abaixo guardam as três coisas que o comando promete e que são fáceis de perder
 * em silêncio: o valor exibido é o VIGENTE (o banco vence o `.env`), nenhum segredo sai em claro,
 * e exibir não altera nada.
 *
 * Ver `wikis/specs/feat/paleta-do-filament-na-organizacao/kit-info/04-casos-de-teste.md`.
 */
beforeEach(function (): void {
    $this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class, UsuarioAdminSeeder::class]);
});

/*
|--------------------------------------------------------------------------
| R1 — o comando existe, é descoberto e termina com sucesso
|--------------------------------------------------------------------------
*/

/**
 * CT-01 — a chave na lista do artisan, e não só a execução.
 *
 * `Artisan::all()` prova a DESCOBERTA: um comando fora de `app/Console/Commands` roda por
 * `Artisan::call` da classe e não aparece em `php artisan list kit`, que é onde a documentação
 * manda procurar.
 */
it('[CT-01] esta registrado no namespace kit e termina com sucesso', function (): void {
    $this->artisan('kit:info')->assertSuccessful();

    expect(Artisan::all())->toHaveKey('kit:info');
})->group('kit');

/*
|--------------------------------------------------------------------------
| R2 — as cinco respostas da instalação, com o valor vigente
|--------------------------------------------------------------------------
*/

/**
 * CT-02 — o nome vigente é o do banco, não o do arquivo de config.
 *
 * O valor tem acento, cedilha e `&` de propósito: mata um `Str::ascii()` ou um `e()` indevido no
 * caminho, e o `twoColumnDetail` calculando largura por byte. A asserção de ausência só vale
 * porque o `expect()` inicial exige que arquivo e banco DISCORDEM — senão "não contém o nome
 * antigo" passaria por acidente.
 *
 * E ela é sobre a LINHA, não sobre a saída: `Starter Kit` continua aparecendo, legitimamente, na
 * linha do remetente de e-mail (`mail_from_name` é semeado de `${APP_NAME}`).
 */
it('[CT-02] mostra o nome gravado no banco, nao o do arquivo de configuracao', function (): void {
    $doArquivo = (string) config('app.name');
    $doBanco   = 'Organização Ção & Cia';

    expect($doArquivo)->not->toBe($doBanco);

    gravarConfiguracao('nome_da_aplicacao', $doBanco);
    alinharConfiguracoesDoKit();

    expect(linhaDoKitInfo(saidaDoKitInfo(), 'Nome do projeto'))
        ->toContain($doBanco)
        ->not->toContain($doArquivo);
})->group('kit');

/**
 * CT-03 — o rótulo da cor sai da PRECEDÊNCIA, e a linha do hexadecimal inválido é o ponto.
 *
 * `CorPrimaria::resolver()` decide: hex válido vence; hex inválido ou vazio cai para o nome; nome
 * inexistente cai para o padrão. O comando lê o FORMATO do retorno em vez de repetir a regra (uma
 * segunda cópia divergiria no primeiro ajuste — ver o docblock de lá). A linha `#zz` × `Blue`
 * separa "leu a paleta" de "olhou se o hex está preenchido".
 */
it('[CT-03] rotula a cor primaria pela particao que venceu', function (string $hex, string $nome, string $esperado, string $ausente): void {
    config(['kit.cor_primaria_hex' => $hex, 'kit.cor_primaria' => $nome]);

    expect(linhaDoKitInfo(saidaDoKitInfo(), 'Cor primária'))
        ->toContain($esperado)
        ->not->toContain($ausente);
})->with([
    'nenhuma escolha'              => ['', '', 'padrão do Filament', 'paleta do Filament'],
    'nome da paleta vence'         => ['', 'Blue', 'Blue (paleta do Filament)', 'padrão do Filament'],
    'hexadecimal vence o nome'     => ['#ff0000', 'Blue', '#ff0000 (hexadecimal', 'Blue (paleta'],
    'hexadecimal invalido recua'   => ['#zz', 'Blue', 'Blue (paleta do Filament)', '#zz'],
])->group('kit');

/**
 * CT-04 — o administrador aparece mascarado, e TODOS eles.
 *
 * Mais de um `master_global` é estado possível (o papel se concede na tela de papéis), e mostrar o
 * primeiro esconderia isso — é a razão de `AdministradorDaInstalacao::todos()` devolver coleção.
 * O e-mail completo não pode aparecer em nenhuma das três cardinalidades: a saída deste comando vai
 * para chamado de suporte.
 *
 * O esperado é a MÁSCARA INTEIRA (`adm` mais catorze asteriscos), não o prefixo: `adm` sozinho
 * aparece em `/admin/configuracoes-do-kit` e em mais meia dúzia de linhas, e a asserção passaria
 * com o mascaramento removido.
 */
it('[CT-04] lista os administradores mascarados, em qualquer cardinalidade', function (int $quantos): void {
    $doSeeder = (string) config('kit.admin.email');
    $segundo  = 'segundo@example.com';

    match ($quantos) {
        0       => User::where('email', $doSeeder)->delete(),
        2       => usuarioDoKit('master_global', $segundo),
        default => null,
    };

    $esperado = match ($quantos) {
        0       => 'UsuarioAdminSeeder',
        1       => Str::mask($doSeeder, '*', 3),
        default => Str::mask($segundo, '*', 3),
    };

    $this->artisan('kit:info')
        ->expectsOutputToContain($esperado)
        ->doesntExpectOutputToContain($doSeeder)
        ->doesntExpectOutputToContain($segundo)
        ->assertSuccessful();
})->with([
    'nenhum' => 0,
    'um'     => 1,
    'dois'   => 2,
])->group('kit');

/**
 * CT-05 — multi-organização desligada é dita, e o comando diz o que a liga.
 *
 * As três afirmações são sobre a MESMA linha, então o oráculo é a linha:
 * `expectsOutputToContain()` casa no máximo uma substring por linha impressa (ver
 * `linhaDoKitInfo()` em `tests/Pest.php`).
 */
it('[CT-05] diz que a multi-organizacao esta desligada e aponta o comando que liga', function (): void {
    expect(config('kit.tenancy.enabled'))->toBeFalse();

    expect(linhaDoKitInfo(saidaDoKitInfo(), 'Multi-organização'))
        ->toContain('desligada')
        ->toContain('kit:tenancy')
        ->not->toContain('cadastrada');
})->group('kit');

/*
|--------------------------------------------------------------------------
| R3 — toda propriedade do settings, na ordem do mapa
|--------------------------------------------------------------------------
*/

/**
 * CT-06 — nenhuma propriedade falta, e a ordem é a do mapa.
 *
 * O esperado é GERADO do próprio `mapaDeConfiguracao()`, então propriedade nova entra no teste
 * sozinha. É o caso que mata a alternativa recusada na ADR-02: uma tabela de rótulos escrita à mão
 * no comando (quarta cópia da lista), ou um agrupamento por prefixo — `hub_de_navegacao` e
 * `rotulo_da_organizacao` não têm prefixo e cairiam fora.
 */
it('[CT-06] exibe uma linha para cada propriedade do settings, na ordem do mapa', function (): void {
    $propriedades = array_keys(ConfiguracoesDoKit::mapaDeConfiguracao());
    $saida        = saidaDoKitInfo();

    expect($propriedades)->toHaveCount(44);

    foreach ($propriedades as $propriedade) {
        expect($saida)->toContain(Str::headline($propriedade));
    }

    expect(strpos($saida, Str::headline($propriedades[0])))
        ->toBeLessThan(strpos($saida, Str::headline(end($propriedades))));
})->group('kit');

/**
 * CT-07 — o valor vigente sai no formato do tipo dele.
 *
 * `37` e não `10` (o default de fábrica): a linha falha se o comando mostrar o valor do arquivo em
 * vez do banco. `false` precisa virar "não" porque `(string) false` é string vazia — a linha
 * ficaria em branco e se leria como "não configurado", que é outra coisa.
 */
it('[CT-07] exibe o valor vigente no formato do tipo dele', function (string $propriedade, mixed $gravado, string $exibido): void {
    gravarConfiguracao($propriedade, $gravado);
    alinharConfiguracoesDoKit();

    $rotulo = Str::headline($propriedade);
    $saida  = saidaDoKitInfo();

    expect($saida)->toMatch('/'.preg_quote($rotulo, '/').'[\s.]+'.preg_quote($exibido, '/').'/u');
})->with([
    'inteiro nao redondo' => ['paginacao_padrao', 37, '37'],
    'booleano falso'      => ['tabela_listrada', false, 'não'],
    'booleano verdadeiro' => ['hub_de_navegacao', true, 'sim'],
    'vazio'               => ['login_rodape', null, '—'],
    'texto'               => ['rotulo_da_organizacao', 'Unidade', 'Unidade'],
])->group('kit');

/*
|--------------------------------------------------------------------------
| R4 — nenhum segredo em claro
|--------------------------------------------------------------------------
*/

/**
 * CT-08 — cada segredo aparece como presença, nunca como valor.
 *
 * O dataset é a própria lista `encrypted()`; a contagem dela está em CT-08b, porque um `it()` com
 * dataset roda uma vez por linha e a contagem é uma afirmação sobre a lista inteira.
 *
 * **O arranjo passa por `$settings->save()`, e não por `gravarConfiguracao()`.** O `payload` de
 * propriedade cifrada é criptograma: gravar texto claro direto na tabela faz a decifragem da
 * leitura falhar, e o valor nunca chega ao comando — o caso reprovaria por defeito do arranjo. É a
 * mesma razão que separa CT-01 de CT-02 em `ConfiguracoesDoKitTest.php:85-88`.
 *
 * A senha do administrador entra no mesmo caso porque é a quinta resposta da instalação e não tem
 * valor a exibir — só o caminho para trocá-la.
 */
it('[CT-08] exibe cada segredo como presenca, nunca o valor', function (string $propriedade): void {
    $segredo = "Segredo-{$propriedade}-9f3";

    $settings                 = app(ConfiguracoesDoKit::class);
    $settings->{$propriedade} = $segredo;
    $settings->save();

    alinharConfiguracoesDoKit();
    config(['kit.admin.password' => 'SenhaUnicaXYZ']);

    $this->artisan('kit:info')
        ->expectsOutputToContain('definida')
        ->doesntExpectOutputToContain($segredo)
        ->doesntExpectOutputToContain('SenhaUnicaXYZ')
        ->assertSuccessful();
})->with(fn () => ConfiguracoesDoKit::encrypted())->group('kit');

it('[CT-08b] a lista de segredos do settings tem as seis entradas que o comando espera', function (): void {
    expect(ConfiguracoesDoKit::encrypted())->toHaveCount(6);
})->group('kit');

/*
|--------------------------------------------------------------------------
| R5 — exibir é somente leitura
|--------------------------------------------------------------------------
*/

/**
 * CT-10 — a tabela de settings sai do comando exatamente como entrou.
 *
 * O agregado é a tabela inteira, não uma propriedade: mata um `save()` colado por engano (o
 * caminho vizinho, `CustomizadorDaInstalacao::propagarParaOSettings()`, faz exatamente isso).
 */
it('[CT-10] nao altera nenhuma linha da tabela de settings', function (): void {
    gravarConfiguracao('nome_da_aplicacao', 'Do Banco');
    gravarConfiguracao('paginacao_padrao', 37);

    $antes = SettingsProperty::query()
        ->orderBy('name')->get(['group', 'name', 'payload'])->toArray();

    $this->artisan('kit:info')->assertSuccessful();

    $depois = SettingsProperty::query()
        ->orderBy('name')->get(['group', 'name', 'payload'])->toArray();

    expect($depois)->toBe($antes);
})->group('kit');

/**
 * CT-11 — a config do processo continua dizendo o que o banco diz.
 *
 * Este é o caso que justifica `valoresDosArquivos()` existir separado. A implementação preguiçosa
 * chamaria `devolverConfigAoEnv()` para obter os valores do arquivo — e ela ESCREVE em `config()`,
 * devolvendo o processo ao `.env` em silêncio. O próximo consumidor no mesmo processo passaria a
 * ler o nome errado, sem erro nenhum. O `Dado` exige que arquivo e banco discordem, senão o
 * cenário é vácuo.
 */
it('[CT-11] nao devolve a config do processo ao env', function (): void {
    gravarConfiguracao('nome_da_aplicacao', 'Do Banco');
    alinharConfiguracoesDoKit();

    expect(ConfiguracoesDoKit::valoresDosArquivos()['app.name'])->not->toBe('Do Banco')
        ->and(config('app.name'))->toBe('Do Banco');

    $this->artisan('kit:info')->assertSuccessful();

    expect(config('app.name'))->toBe('Do Banco');
})->group('kit');

/** CT-12 — duas execuções produzem a mesma saída: nada acumula entre elas. */
it('[CT-12] produz a mesma saida em duas execucoes', function (): void {
    expect(saidaDoKitInfo())->toBe(saidaDoKitInfo());
})->group('kit');

/*
|--------------------------------------------------------------------------
| R6 — fonte declarada, divergência só quando existe
|--------------------------------------------------------------------------
*/

/**
 * CT-09 — sem divergência real, o comando não abre a seção.
 *
 * E este caso já é discriminante SEM arranjo nenhum: a migration de settings semeia texto com
 * `textoOuNulo()`, então toda instalação recém-semeada tem `null` no banco onde o arquivo diz `''`.
 * Uma comparação `!==` crua acusaria divergência logo depois do `migrate`, em toda instalação — e
 * a seção que existe para apontar problema real viraria ruído permanente.
 */
it('[CT-09] nao abre a secao de divergencia quando banco e arquivo concordam', function (): void {
    alinharConfiguracoesDoKit();

    $this->artisan('kit:info')
        ->expectsOutputToContain('banco')
        ->doesntExpectOutputToContain('diz diferente')
        ->assertSuccessful();
})->group('kit');

/**
 * CT-13 — a divergência aparece quando é real, e não aparece quando é só de tipo.
 *
 * A linha do rodapé é o coração: `KIT_LOGIN_RODAPE=""` está forçado no `phpunit.xml`, então o
 * arquivo devolve `''` e o banco recebe `null` — os dois normalizam para vazio e NÃO são
 * divergência. A linha do booleano é o contrapeso: quem colapsar tudo em texto vazio
 * (`(string) false`) esconde o booleano que discorda de verdade.
 */
it('[CT-13] acusa divergencia real e ignora divergencia de tipo', function (string $propriedade, mixed $banco, string $chave, bool $divergente): void {
    gravarConfiguracao($propriedade, $banco);
    alinharConfiguracoesDoKit();

    $teste = $this->artisan('kit:info');

    $divergente
        ? $teste->expectsOutputToContain($chave)
        : $teste->doesntExpectOutputToContain($chave);

    $teste->assertSuccessful();
})->with([
    'texto diferente'      => ['nome_da_aplicacao', 'Do Banco', 'app.name', true],
    'nulo contra vazio'    => ['login_rodape', null, 'kit.login.rodape', false],
    'booleano diferente'   => ['tabela_listrada', false, 'kit.tabelas.listrada', true],
])->group('kit');

/**
 * CT-13b — segredo que diverge é dito sem os valores. Arranjo pela API, como CT-08.
 *
 * A chave e o aviso ficam na mesma linha, então o oráculo é a linha — ver `linhaDoKitInfo()`.
 */
it('[CT-13b] diz que o segredo diverge sem exibir os dois lados', function (): void {
    $segredo                 = 'Segredo-divergente-9f3';
    $settings                = app(ConfiguracoesDoKit::class);
    $settings->mail_password = $segredo;
    $settings->save();

    alinharConfiguracoesDoKit();

    expect(ConfiguracoesDoKit::valoresDosArquivos()['mail.mailers.smtp.password'])->not->toBe($segredo);

    $saida = saidaDoKitInfo();

    expect(linhaDoKitInfo($saida, 'mail.mailers.smtp.password'))
        ->toContain('valores não exibidos')
        ->not->toContain($segredo)
        ->and($saida)->not->toContain($segredo);
})->group('kit');

/**
 * CT-14 — sem as tabelas que ele consulta, o comando ainda responde o que não depende de banco.
 *
 * Sem banco é justamente quando alguém pergunta como o projeto está configurado — o primeiro
 * `migrate` de uma instalação nova, um clone, um CI. Morrer aqui é pior do que não existir.
 *
 * ## Duas tabelas, e essas duas
 *
 * A escolha é cirúrgica porque as alternativas mais amplas não funcionam sob `RefreshDatabase`,
 * e as duas foram medidas:
 *
 * - `Schema::dropAllTables()` emite um `vacuum` em SQLite, e a suíte roda dentro de uma transação:
 *   `cannot VACUUM from within a transaction`;
 * - dropar `users` e `tenants` estoura `no such table: main.tenants` ao validar as chaves
 *   estrangeiras pendentes — e `Schema::disableForeignKeyConstraints()` não salva, porque o
 *   `PRAGMA foreign_keys` do SQLite é **no-op dentro de uma transação**.
 *
 * Então: `settings` (nada a referencia; faz `gravadoNoBanco()` responder `false`) e o pivô de
 * papéis (nada a referencia; faz o `whereHas` de `AdministradorDaInstalacao::todos()` lançar).
 * As duas cobrem os dois ramos que o comando precisa sobreviver.
 *
 * **Lacuna declarada**: a variante "conexão morta", em que `gravadoNoBanco()` LANÇA em vez de
 * responder `false`, não é expressável aqui — purgar a conexão quebra o rollback do tearDown.
 */
it('[CT-14] sem as tabelas que consulta, degrada a linha e nao o comando', function (): void {
    $nome = (string) config('app.name');

    Schema::drop('settings');
    Schema::drop((string) config('permission.table_names.model_has_roles', 'model_has_roles'));

    $this->artisan('kit:info')
        ->expectsOutputToContain($nome)
        ->expectsOutputToContain('indisponível')
        ->expectsOutputToContain('.env')
        ->doesntExpectOutputToContain('diz diferente')
        ->assertSuccessful();
})->group('kit');

/*
|--------------------------------------------------------------------------
| R4/R6 — o log
|--------------------------------------------------------------------------
*/

/**
 * CT-16 — uma linha de log, com as chaves divergentes e sem os valores.
 *
 * O canal `configuracoes` é lido pelo Logs Explorer do `/infra`: o que entra ali sai numa tela. O
 * caso afirma o formato `[Classe@Método]` (convenção do kit) e a ausência do segredo; o NÍVEL é
 * escolha do plano e não está travado aqui.
 */
it('[CT-16] registra uma linha no canal de configuracoes, com as chaves e sem os valores', function (): void {
    gravarConfiguracao('mail_password', 'Segredo-do-log-9f3');
    alinharConfiguracoesDoKit();

    $canal = espiarConfiguracoes();

    $this->artisan('kit:info')->assertSuccessful();

    $canal->shouldHaveReceived('debug')
        ->once()
        ->withArgs(function (string $mensagem, array $contexto): bool {
            $serializado = $mensagem.json_encode($contexto);

            return str_contains($mensagem, '[KitInfo@handle]')
                && ! str_contains($serializado, 'Segredo-do-log-9f3');
        });
})->group('kit');

/*
|--------------------------------------------------------------------------
| R8 — documentado nos dois idiomas
|--------------------------------------------------------------------------
*/

/**
 * CT-17 — o comando está documentado onde os outros `kit:*` estão, nos dois idiomas.
 *
 * `documentacaoDoKit()` concatena o README e a árvore do idioma, então o caso não fixa em QUAL
 * página está — reorganizar o site não reprova teste de conteúdo. O `CHANGELOG.md` não entra nessa
 * concatenação, então anunciar lá e não documentar aqui continua reprovando.
 */
it('[CT-17] esta documentado nos dois idiomas', function (string $idioma): void {
    expect(documentacaoDoKit($idioma))->toContain('php artisan kit:info');
})->with(['pt', 'en'])->group('kit');
