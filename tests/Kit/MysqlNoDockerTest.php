<?php

/**
 * O MySQL no Docker do kit, e o nome do projeto no lugar de `starter-kit`.
 *
 * O instalador oferece três bancos e um deles não tinha container. Este arquivo guarda o lado
 * do `docker-compose.yml`: que o serviço existe e é utilizável, que subi-lo NÃO sobe o Postgres
 * junto, que a aplicação containerizada deixou de fixar o banco, e que nenhum container tem nome
 * fixo — o prefixo passou a vir do nome do projeto.
 *
 * O lado do instalador (o que é escrito no `.env`) mora em `CustomizadorDaInstalacaoTest.php`,
 * onde os quatro helpers de que aqueles casos precisam já existem.
 *
 * ## Duas ferramentas de leitura, e por que ficam locais
 *
 * O RECORTE DE BLOCO é o que torna metade dos casos discriminante. `restart: unless-stopped`
 * aparece dez vezes no arquivo, nos serviços vizinhos: uma asserção sobre o arquivo inteiro
 * seria verdadeira ANTES da entrega e não mataria mutante nenhum.
 *
 * O FILTRO DE COMENTÁRIO vale só para as asserções de AUSÊNCIA. Os arquivos do kit citam o que
 * proíbem — é lá que está escrito o porquê —, e afirmar ausência sobre o texto cru reprova pela
 * própria documentação. É a armadilha que `.ai/rules/testes.md` registra, e que já custou três
 * vezes nesta base.
 *
 * As duas são closures deste arquivo, não funções em `tests/Pest.php`: a regra de
 * `.ai/rules/testes.md` é sobre helper usado por MAIS DE UM arquivo. Se um terceiro precisar,
 * é aí que elas sobem — não antes.
 *
 * Ver `wikis/specs/feat/mysql-no-docker/mysql-no-docker/`.
 */

use App\Console\Commands\KitUpdate;

beforeEach(function (): void {
    $this->compose     = (string) file_get_contents(base_path('docker-compose.yml'));
    $this->envExample  = (string) file_get_contents(base_path('.env.example'));

    $this->semComentario = static fn (string $texto): string => implode("\n", array_filter(
        explode("\n", $texto),
        static fn (string $linha): bool => ! str_starts_with(ltrim($linha), '#'),
    ));

    $this->composeExecutavel = ($this->semComentario)($this->compose);

    /*
     * O bloco de UM serviço: do `  <nome>:` até a próxima chave de coluna 2 (outro serviço) ou
     * de coluna 0 (o `volumes:` de topo). Devolve '' quando o serviço não existe — e é isso que
     * faz o cenário reprovar em vez de passar vazio.
     */
    $this->blocoDoServico = function (string $servico): string {
        $linhas = explode("\n", $this->compose);
        $dentro = false;
        $bloco  = [];

        foreach ($linhas as $linha) {
            if ($linha === '  '.$servico.':') {
                $dentro = true;

                continue;
            }

            if ($dentro) {
                $fimDeServico = preg_match('/^  \S/', $linha) === 1;
                $fimDeTopo    = preg_match('/^\S/', $linha) === 1;

                if ($fimDeServico || $fimDeTopo) {
                    break;
                }

                $bloco[] = $linha;
            }
        }

        return implode("\n", $bloco);
    };
});

/*
|--------------------------------------------------------------------------
| R1 — o kit declara um serviço MySQL utilizável e persistente
|--------------------------------------------------------------------------
| CT-01. Cada linha é uma partição com modo de falha próprio: sem o volume de
| topo o arquivo é INVÁLIDO e nenhum comando roda; sem o volume montado os
| dados morrem no `down`; sem o nome do banco o container fica saudável e o
| `migrate` morre em "Unknown database"; com tag flutuante quebra num dia
| qualquer. Uma asserção só não distingue esses casos.
*/

it('[CT-01] o serviço MySQL traz os atributos sem os quais não serve', function (string $forma, string $comoFalha): void {
    $bloco = ($this->blocoDoServico)('mysql');

    expect($bloco)->not->toBe('', 'O serviço `mysql` não existe sob `services:` do docker-compose.yml.')
        ->and($bloco)->toMatch($forma, $comoFalha);
})->with([
    'imagem fixada em major.minor' => [
        '/^    image: mysql:8\.0$/m',
        'Tag flutuante (`mysql` ou `mysql:latest`) quebra numa atualização silenciosa da imagem.',
    ],
    'política de restart do kit' => [
        '/^    restart: unless-stopped$/m',
        'Divergir da convenção dos dez serviços vizinhos.',
    ],
    'porta publicada, com override' => [
        '/^      - \'\$\{FORWARD_MYSQL_PORT:-3306\}:3306\'$/m',
        'Porta fixa colide com um MySQL já instalado na máquina — e o kit não daria saída.',
    ],
    'o banco da aplicação é criado' => [
        '/^      MYSQL_DATABASE: \'\$\{DB_DATABASE:-starter_kit\}\'$/m',
        'Sem isso o container fica saudável e o `migrate` morre em "Unknown database".',
    ],
    'volume nomeado montado' => [
        '/^      - \'mysql-data:\/var\/lib\/mysql\'$/m',
        'Sem volume, os dados morrem em `docker compose down`.',
    ],
])->group('kit');

/**
 * O volume tem de aparecer DUAS vezes: montado no serviço e declarado no bloco de topo.
 *
 * Esquecer a declaração de topo é o erro de digitação mais comum em compose, e o de maior
 * alcance: o arquivo fica inválido e derruba o `docker compose up -d` de TODO usuário do kit,
 * inclusive quem usa Postgres e nunca ligou o MySQL.
 */
it('[CT-01] o volume do MySQL também é declarado no bloco de topo', function (): void {
    expect($this->composeExecutavel)->toMatch('/^volumes:$.*^  mysql-data:$/ms');
})->group('kit');

/*
|--------------------------------------------------------------------------
| R2 — o MySQL nasce saudável, e a aplicação só arranca depois dele
|--------------------------------------------------------------------------
*/

/**
 * CT-02 — o dólar DOBRADO é o que faz a senha ser lida dentro do container.
 *
 * O dólar simples é a forma que parece certa e falha em silêncio: o Compose interpola
 * `$MYSQL_ROOT_PASSWORD` na leitura do arquivo, onde a variável não existe, e o `mysqladmin`
 * roda com senha vazia. O container fica `unhealthy` para sempre, e quem depende dele por
 * `service_healthy` nunca sobe.
 *
 * A negativa precisa de olhar-para-trás: `$$MYSQL_ROOT_PASSWORD` CONTÉM `$MYSQL_ROOT_PASSWORD`,
 * então "não contém a forma de um dólar só" reprovaria a implementação correta.
 */
it('[CT-02] o healthcheck do MySQL lê a senha dentro do container', function (): void {
    $bloco = ($this->blocoDoServico)('mysql');

    expect($bloco)->toContain('mysqladmin ping')
        ->and($bloco)->toContain('$$MYSQL_ROOT_PASSWORD')
        ->and($bloco)->not->toMatch(
            '/(?<!\$)\$MYSQL_ROOT_PASSWORD/',
            'Dólar simples é interpolado pelo Compose: o healthcheck rodaria com senha vazia.',
        );
})->group('kit');

/**
 * CT-03 — os CINCO serviços do profile `app`, um por um.
 *
 * Contar ocorrências no arquivo inteiro não serve: a contagem `= 5` é satisfeita por cinco
 * serviços ERRADOS — editar o `nginx` (que está logo acima e "também é do app") e esquecer o
 * `pulse` fecha a conta, e o Pulse containerizado nunca acha o banco.
 *
 * A segunda asserção é a que impede a "limpeza" que a ADR-01 mediu e recusou: tirar o Postgres
 * do `depends_on` deixa `--profile app up -d` subir a aplicação SEM BANCO NENHUM, sem erro.
 */
it('[CT-03] cada serviço da aplicação containerizada espera o banco escolhido', function (string $servico): void {
    $bloco = ($this->blocoDoServico)($servico);

    expect($bloco)->not->toBe('', "O serviço `{$servico}` sumiu do docker-compose.yml.")
        ->and($bloco)->toMatch('/^      mysql:\n        condition: service_healthy\n        required: false/m')
        ->and($bloco)->toMatch(
            '/^      pgsql:\n        condition: service_healthy$/m',
            'O `pgsql` foi medido e tem de ficar: sem ele, `--profile app up -d` sobe a aplicação sem banco nenhum.',
        );
})->with(['app', 'queue', 'scheduler', 'reverb', 'pulse'])->group('kit');

/*
|--------------------------------------------------------------------------
| R3 — subir o MySQL não sobe o Postgres
|--------------------------------------------------------------------------
*/

/**
 * CT-04 — o MySQL é o único banco atrás de profile, e o profile é só dele.
 *
 * As três outras combinações da tabela de decisão são mutantes medidos: os dois no default
 * sobem juntos em toda instalação; os dois em profile fazem `docker compose up -d` puro deixar
 * de subir banco e `--profile app up -d` subir a aplicação sem banco; e trocar os lados inverte
 * o default do kit, que o `00` põe fora de escopo.
 */
it('[CT-04] o MySQL é o único banco atrás de profile, e o profile é só dele', function (): void {
    $mysql = ($this->blocoDoServico)('mysql');
    $pgsql = ($this->blocoDoServico)('pgsql');

    expect($mysql)->toMatch('/^    profiles: \[mysql\]$/m', 'A lista tem de ter um elemento só — `[mysql, full]` faria `--profile full` subir dois bancos.')
        ->and($pgsql)->not->toMatch('/^    profiles:/m', 'O Postgres continua no profile default: é o comportamento documentado no README.');

    $comOMesmoProfile = preg_grep(
        '/^    profiles: \[.*\bmysql\b.*\]$/',
        explode("\n", $this->composeExecutavel),
    );

    expect($comOMesmoProfile)->toHaveCount(1, 'O profile `mysql` é exclusivo do serviço `mysql`.');
})->group('kit');

/**
 * CT-21 — o invariante é sobre o CONJUNTO, não sobre dois serviços nominados.
 *
 * A premissa A4 do `00` (só o `mysql` entra, e não os `mysql_test`/`mysql_dusk_test` do arquivo
 * de referência) tem consequência declarada e, sem este caso, nenhum cenário. Um transplante que
 * trouxesse os dois containers de teste sem profile passa em CT-04 inteiro e faz
 * `docker compose up -d` subir três bancos.
 */
it('[CT-21] nenhum banco novo entra no profile default', function (): void {
    $bancosNoDefault = [];

    foreach (['pgsql', 'mysql', 'mysql_test', 'mysql_dusk_test', 'mariadb', 'postgres', 'db'] as $servico) {
        $bloco = ($this->blocoDoServico)($servico);

        if ($bloco !== '' && preg_match('/^    profiles:/m', $bloco) !== 1) {
            $bancosNoDefault[] = $servico;
        }
    }

    expect($bancosNoDefault)->toBe(['pgsql']);
})->group('kit');

/**
 * CT-05 — o comando divulgado nomeia serviços, e não liga um profile.
 *
 * Não é teste de documentação: `docker compose --profile mysql up -d` LIGA o profile e o SOMA ao
 * default — sobe MySQL, Redis e Postgres. É a forma que qualquer pessoa escreveria por analogia
 * com os cinco `--profile` que o kit já documenta, e entrega uma violação de RQ-02 a cada pessoa
 * que seguir o texto.
 *
 * O universo são CINCO textos, não três: as duas páginas de `docs/` são as que o site publica.
 */
it('[CT-05] o comando divulgado nomeia os serviços, e não liga um profile', function (string $arquivo): void {
    $texto  = (string) file_get_contents(base_path($arquivo));
    $linhas = explode("\n", $texto);

    $comOComando = preg_grep('/docker compose up -d mysql redis/', $linhas);

    expect($comOComando)->not->toBe([], "{$arquivo} não divulga `docker compose up -d mysql redis`.");

    /*
     * `--profile mysql` SOZINHO soma o profile ao default e sobe o Postgres junto — é a
     * violação de RQ-02 que a pessoa comete por analogia com `--profile ai` e `--profile mail`.
     *
     * Já `--profile app --profile mysql` é a forma CORRETA e documentada: ali a aplicação
     * containerizada precisa mesmo dos dois, e o Postgres ocioso é custo aceito e registrado.
     * Proibir a segunda junto com a primeira reprovaria a documentação certa.
     */
    $ligaOProfileSozinho = array_filter(
        preg_grep('/--profile\s+mysql\b/', $linhas),
        static fn (string $linha): bool => ! str_contains($linha, '--profile app'),
    );

    expect($ligaOProfileSozinho)->toBe([], "{$arquivo} manda ligar o profile do MySQL sozinho, o que sobe o Postgres junto.")
        ->and($texto)->not->toContain('COMPOSE_PROFILES');
})->with([
    'docker-compose.yml',
    'README.md',
    'README.en.md',
    'docs/pt/comecar/instalacao-avancada.md',
    'docs/en/comecar/instalacao-avancada.md',
])->group('kit');

/*
|--------------------------------------------------------------------------
| R4 — o profile `app` deixa de fixar o Postgres, e o default é o de hoje
|--------------------------------------------------------------------------
*/

/**
 * CT-06 — a ausência é da forma LITERAL, não da palavra.
 *
 * Depois da mudança a linha é `DB_CONNECTION: ${DOCKER_DB_SERVICE:-pgsql}`, que CONTÉM `pgsql`:
 * um `not->toContain('pgsql')` reprovaria a implementação correta.
 */
it('[CT-06] os cinco serviços do profile app deixam de fixar o Postgres', function (): void {
    expect($this->composeExecutavel)
        ->not->toMatch('/^      DB_CONNECTION: pgsql$/m')
        ->not->toMatch('/^      DB_HOST: pgsql$/m');

    $conexoes = preg_match_all('/^      DB_CONNECTION: \$\{[A-Z_]+:-pgsql\}$/m', $this->composeExecutavel);
    $hosts    = preg_match_all('/^      DB_HOST: \$\{[A-Z_]+:-pgsql\}$/m', $this->composeExecutavel);

    expect($conexoes)->toBe(5, 'Os cinco serviços do profile `app` precisam da interpolação — não só o `app`.')
        ->and($hosts)->toBe(5, 'Host parametrizado e driver esquecido daria host `mysql` com driver `pgsql`.');
})->group('kit');

/**
 * CT-07 — o valor literal do requisito.
 *
 * A seção *Fora de Escopo* do `00` diz que trocar o banco default do kit está fora do escopo, e
 * o default da interpolação é o único lugar onde esse "fora de escopo" é falsificável. As dez
 * linhas trazem a MESMA string, e não dez que por acaso terminam igual.
 */
it('[CT-07] quem não escolheu nada continua no Postgres', function (): void {
    preg_match_all('/^      DB_(?:CONNECTION|HOST): (\$\{[A-Z_]+:-[a-z]+\})$/m', $this->composeExecutavel, $achados);

    expect($achados[1])->toHaveCount(10)
        ->and(array_unique($achados[1]))->toHaveCount(1, 'As dez linhas têm de trazer a mesma interpolação.')
        ->and($achados[1][0])->toEndWith(':-pgsql}');
})->group('kit');

/**
 * CT-20 (adaptado) — as duas pontas do contrato continuam amarradas.
 *
 * O cenário original amarrava a variável do compose à chave que o INSTALADOR gravava. A
 * auditoria do plano cortou essa escrita, e por um motivo de correção: gravá-la faria quem
 * rodasse `--profile app` sem `--profile mysql` apontar para um container que não subiu.
 *
 * O contrato não sumiu — mudou de ponta. A variável interpolada no compose tem de ser a mesma
 * documentada no `.env.docker`, que é onde quem containeriza a aplicação vai lê-la. Sem esta
 * amarra, o compose pode interpolar uma chave e a doc ensinar outra, e a aplicação
 * containerizada nunca sai do Postgres — com todos os outros casos verdes.
 */
it('[CT-20] a variável do compose é a chave que a documentação ensina', function (): void {
    preg_match('/^      DB_HOST: \$\{([A-Z_]+):-pgsql\}$/m', $this->composeExecutavel, $noCompose);

    expect($noCompose)->not->toBeEmpty();

    $envDocker = (string) file_get_contents(base_path('.env.docker'));

    expect($envDocker)->toMatch('/^# '.$noCompose[1].'=mysql$/m');
})->group('kit');

/*
|--------------------------------------------------------------------------
| R5 — uma fonte só para a senha do MySQL, e ela não é vazia
|--------------------------------------------------------------------------
*/

/**
 * CT-09 — o container lê a senha do `.env`, e não tem saída alternativa.
 *
 * As três ausências são as três saídas que a própria mensagem de erro da imagem oferece.
 * `MYSQL_USER` a imagem recusa com `root`; `MYSQL_ALLOW_EMPTY_PASSWORD` abriria root sem senha
 * numa porta publicada; `MYSQL_RANDOM_ROOT_PASSWORD` produz uma senha que NÃO está no `.env` —
 * a divergência silenciosa que a decisão existe para impedir.
 *
 * O default é VAZIO de propósito. Um default com valor valeria só para o container: todo projeto
 * MySQL instalado antes desta versão tem `DB_PASSWORD=` vazio e receberia container com a senha
 * do default e aplicação conectando com vazio. Vazio, a imagem recusa subir e diz o que falta.
 */
it('[CT-09] o container cria o banco, lê a senha do .env e não abre sem senha', function (): void {
    $bloco          = ($this->blocoDoServico)('mysql');
    $blocoSemComent = ($this->semComentario)($bloco);

    expect($bloco)->toMatch('/^      MYSQL_ROOT_PASSWORD: \'\$\{DB_PASSWORD:-\}\'$/m')
        ->and($bloco)->toMatch('/^      MYSQL_DATABASE: \'\$\{DB_DATABASE:-\w+\}\'$/m');

    foreach (['MYSQL_USER', 'MYSQL_ALLOW_EMPTY_PASSWORD', 'MYSQL_RANDOM_ROOT_PASSWORD'] as $saidaProibida) {
        expect($blocoSemComent)->not->toContain(
            $saidaProibida.':',
            "{$saidaProibida} dá ao container uma senha que a aplicação não conhece.",
        );
    }
})->group('kit');

/*
|--------------------------------------------------------------------------
| R6 e R8 — o prefixo vem do nome da aplicação, e sem customização é o de hoje
|--------------------------------------------------------------------------
*/

/**
 * CT-10 — `container_name` fixo IGNORA o `COMPOSE_PROJECT_NAME`.
 *
 * Filtra comentário porque o comentário junto do `name:` explica justamente a nomeação dos
 * containers, e cita a palavra para dizer por que ela não está lá.
 */
it('[CT-10] nenhum container do kit tem nome fixo no compose', function (): void {
    expect($this->composeExecutavel)->not->toContain('container_name');
})->group('kit');

it('[CT-14] o piso do arquivo continua valendo para quem não tem a chave', function (): void {
    expect($this->composeExecutavel)->toMatch('/^name: starter-kit$/m');
})->group('kit');

it('[CT-15] a chave nova nasce ativa no .env.example', function (): void {
    expect($this->envExample)->toMatch('/^COMPOSE_PROJECT_NAME=starter-kit$/m');
})->group('kit');

/**
 * CT-22 — o caminho negativo de RQ-06: quem não customizou nada continua com o nome de hoje.
 *
 * As duas fontes precisam concordar. Se o `.env.example` dissesse um nome e o piso do arquivo
 * outro, o prefixo dependeria de o `.env` existir — e o resultado mudaria entre a instalação e
 * o primeiro `docker compose` de quem apagou a chave.
 */
it('[CT-22] a instalação que não customiza nada não muda o prefixo de hoje', function (): void {
    preg_match('/^name: (\S+)$/m', $this->composeExecutavel, $noArquivo);
    preg_match('/^COMPOSE_PROJECT_NAME=(\S+)$/m', $this->envExample, $noEnv);

    expect($noArquivo[1] ?? '')->toBe('starter-kit')
        ->and($noEnv[1] ?? '')->toBe('starter-kit');
})->group('kit');

/**
 * CT-23 — a premissa que sustenta o mecanismo inteiro, travada em três linhas.
 *
 * A decisão de levar o nome pelo `.env` vale porque o `kit:update` nunca sobrescreve esse
 * arquivo. É um fato sobre uma lista de caminhos que alguém pode editar amanhã sem perceber o
 * que derrubou.
 */
it('[CT-23] a chave do nome do projeto sobrevive ao kit:update', function (): void {
    $listas = collect((new ReflectionClass(KitUpdate::class))->getConstants())
        ->filter(fn (mixed $valor): bool => is_array($valor))
        ->flatten();

    expect($listas)->not->toContain('.env')
        ->and($listas)->not->toContain('.env.example');
})->group('kit');
