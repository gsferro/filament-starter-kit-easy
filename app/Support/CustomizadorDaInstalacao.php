<?php

namespace App\Support;

use App\Settings\ConfiguracoesDoKit;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Throwable;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\note;
use function Laravel\Prompts\password;
use function Laravel\Prompts\select;
use function Laravel\Prompts\text;

/**
 * As perguntas de customização do `kit:install` — e a aplicação das respostas.
 *
 * O modelo é o do instalador do Laravel (`laravel new`): poucas perguntas, todas
 * com default, escolha escrita por substituição pontual no .env, e nada de
 * prompt quando não há terminal. Enter em tudo produz exatamente a instalação
 * que o kit fazia antes desta classe existir.
 *
 * Cinco perguntas, e a régua para entrar foi dura: só valor escalar que muda bit
 * no disco. Os outros sete itens de "Personalize seu projeto" são código ou dado
 * de tela (matriz de permissões, health checks, arte do login, agente de IA) —
 * eles aparecem no RESUMO, com o arquivo a editar, e não como pergunta que
 * ninguém consegue responder num terminal.
 *
 * ## O diretório-base é injetável de propósito
 *
 * Esta classe escreve no .env e em dois arquivos de config. Se o alvo fosse
 * sempre `base_path()`, a suíte de testes reescreveria o .env da máquina de quem
 * roda os testes. O construtor recebe o diretório; vazio significa o do projeto.
 *
 * ## Por que a config em memória é alinhada no fim
 *
 * Escrever `DB_CONNECTION=pgsql` no .env não muda `config('database.default')`
 * do processo que está rodando. Sem o alinhamento, o `kit:install` criaria o
 * arquivo SQLite, migraria para o banco errado e criaria o admin com o e-mail
 * padrão — tudo isso SEM erro nenhum. É a mesma armadilha que o
 * `AtivadorDeTenancy::alinharConfigEmMemoria()` documenta para a tenancy.
 */
final class CustomizadorDaInstalacao
{
    /**
     * As cores oferecidas, em lista fechada.
     *
     * Fechada, e não derivada por reflection de `Filament\Support\Colors\Color`:
     * a classe também expõe constantes que não são cor (`WCAG_AA_TEXT` e afins) e
     * neutros que ninguém escolhe como primária.
     *
     * @var list<string>
     */
    public const CORES = [
        'Amber', 'Blue', 'Cyan', 'Emerald', 'Fuchsia', 'Indigo', 'Lime', 'Orange',
        'Pink', 'Purple', 'Red', 'Rose', 'Sky', 'Slate', 'Teal', 'Violet',
    ];

    private string $base;

    public function __construct(string $base = '')
    {
        $this->base = $base !== '' ? $base : base_path();
    }

    /**
     * A decisão de perguntar, isolada e sem efeito colateral.
     *
     * **`$projetoNovo` NÃO pode vir da existência do `.env`.** O `composer.json`
     * traz um `post-root-package-install` que copia `.env.example` para `.env`
     * ANTES de o `kit:install` rodar — num `create-project` o arquivo sempre
     * existe, e um gate baseado nele nunca deixa perguntar nada. Foi exatamente
     * assim que a feature nasceu quebrada na v0.16.0: instalava em silêncio, sem
     * erro, e os testes passavam porque exercitavam esta classe num diretório
     * temporário, onde aquele script do Composer não existe.
     *
     * O sinal honesto é a `APP_KEY`: ela nasce vazia no `.env.example` e só é
     * preenchida pela própria instalação. Vazia significa "este projeto nunca
     * foi instalado" — que é a pergunta que se queria fazer.
     */
    public static function devePerguntar(
        bool $projetoNovo,
        bool $forcado,
        bool $pulaPorFlag,
        bool $interativo,
    ): bool {
        if ($pulaPorFlag || ! $interativo) {
            return false;
        }

        return $projetoNovo || $forcado;
    }

    /**
     * Faz as perguntas. Devolve `null` quando a customização foi pulada.
     *
     * O `$interativo` é calculado por `KitInstall::temTerminal()`, que repete a
     * expressão do próprio Laravel — `isInteractive()` **e** `stream_isatty(STDIN)`,
     * ou rodando em teste. Nenhum dos dois primeiros serve sozinho, e o docblock
     * de lá explica por quê: um deixa passar instalação sem terminal no Windows,
     * o outro faz o customizador se pular dentro da suíte.
     *
     * Chega `false` em CI, build Docker e `--no-interaction`: a instalação segue
     * com os padrões e nada é reescrito.
     *
     * @return array<string, mixed>|null
     */
    public function perguntar(Command $comando, bool $interativo): ?array
    {
        if (! self::devePerguntar(
            projetoNovo: blank(config('app.key')),
            forcado: (bool) $comando->option('force'),
            pulaPorFlag: (bool) $comando->option('no-custom'),
            interativo: $interativo,
        )) {
            return $this->pulou(match (true) {
                (bool) $comando->option('no-custom') => 'flag',
                ! $interativo                        => 'sem-tty',
                default                              => 'projeto-ja-instalado',
            });
        }

        note(
            'O kit pode nascer com o seu nome, o seu banco e a sua cor. '
            .'São 5 perguntas, todas com resposta padrão — Enter em tudo instala como sempre.'
        );

        if (! confirm('Personalizar o projeto agora?', default: true)) {
            return $this->pulou('usuario');
        }

        $respostas = [
            'nome'  => text(
                label: 'Nome do projeto',
                default: Str::headline(basename($this->base)),
                required: true,
                hint: 'Vai para APP_NAME, e é o que aparece no topo dos painéis.',
            ),
            'banco' => $this->perguntarBanco(),
            'email' => text(
                label: 'E-mail do administrador',
                default: (string) config('kit.admin.email'),
                required: true,
                validate: fn (string $valor): ?string => filter_var($valor, FILTER_VALIDATE_EMAIL)
                    ? null
                    : 'Informe um e-mail válido.',
            ),
            'senha' => password(
                label: 'Senha do administrador',
                hint: 'Enter mantém a senha padrão do kit. Troque antes de expor o ambiente.',
            ),
            'cor'   => select(
                label: 'Cor primária dos painéis',
                options: ['' => 'Padrão do Filament (âmbar)', ...array_combine(self::CORES, self::CORES)],
                default: '',
            ),
        ];

        $respostas += $this->perguntarTenancy();

        Log::debug(
            '[CustomizadorDaInstalacao@perguntar] Respostas coletadas | banco: '.$respostas['banco'],
            ['respostas' => array_replace($respostas, ['senha' => $respostas['senha'] === '' ? 'padrão' : '***'])],
        );

        return $respostas;
    }

    /**
     * As perguntas que podem ser refeitas SEM tocar no banco — e por que são só duas.
     *
     * O `--force` do `kit:install` refaz as cinco perguntas, mas apaga o SQLite antes
     * (`KitInstall.php:229-231`). Isso é inócuo no minuto seguinte à instalação e destrutivo
     * depois. Este caminho existe para o "depois", e por isso o recorte é conservador:
     *
     * - **nome** e **cor** são reescrita de `.env`, e valem no próximo request. Entram aqui.
     * - **banco** exige recriar: trocar SQLite por PostgreSQL depois do `migrate` não é
     *   reescrita de config, é outra instalação.
     * - **multi-organização** idem, e a razão está no `kit:tenancy`: as tabelas de permissão só
     *   nascem com a coluna de contexto se a flag estiver ativa ANTES do migrate.
     * - **credenciais do admin** ficam de fora porque o `UsuarioAdminSeeder` **não sincroniza**:
     *   ele busca pelo PAPEL e, achando administrador, não faz nada. É deliberado — ele roda em
     *   todo `db:seed`, e atualizar senha ali reverteria, em silêncio, a troca feita pela tela de
     *   perfil. Reescrever o `.env` aqui daria a impressão de ter trocado a credencial sem trocar
     *   nada, que é pior que não oferecer.
     *
     *   (Até a 0.18.8 era pior ainda: o seeder buscava pelo E-MAIL, então mudar o endereço e
     *   semear criava um SEGUNDO `master_global`, com o primeiro vivo e a senha antiga.)
     *
     * Quem precisa das três de fora tem caminho, e o comando o imprime: `--force` para recomeçar
     * do zero, `kit:tenancy` para a multi-organização, e a tela de perfil para a senha.
     *
     * @return array{nome: string, cor: string}|null `null` quando o usuário desistiu
     */
    public function perguntarSemBanco(): ?array
    {
        note(
            'Refazendo só o que não toca o banco: nome e cor. '
            .'Banco, multi-organização e credenciais exigem recriar — veja o aviso no fim.'
        );

        if (! confirm('Personalizar nome e cor agora?', default: true)) {
            $this->pulou('usuario');

            return null;
        }

        return [
            'nome' => text(
                label: 'Nome do projeto',
                default: (string) config('app.name'),
                required: true,
                hint: 'Vai para APP_NAME, e é o que aparece no topo dos painéis.',
            ),
            /*
             * O `(string)` não é para calar o analisador: o `select()` do Prompts devolve
             * `int|string`, porque uma lista de opções sem chaves explícitas vem com índice
             * numérico. Aqui as chaves são strings por construção (`''` mais o
             * `array_combine`), e o valor vai direto para o `.env` — normalizar na fronteira é
             * o que torna o tipo declarado verdadeiro em vez de suposto.
             */
            'cor' => (string) select(
                label: 'Cor primária dos painéis',
                options: ['' => 'Padrão do Filament (âmbar)', ...array_combine(self::CORES, self::CORES)],
                default: (string) config('kit.cor_primaria', ''),
            ),
        ];
    }

    /**
     * Aplica o par nome/cor e devolve o resumo. Não toca em banco, seeder nem asset.
     *
     * @param  array{nome: string, cor: string}  $respostas
     * @return list<array{0: string, 1: string}>
     */
    public function aplicarSemBanco(array $respostas): array
    {
        $env = $this->base.DIRECTORY_SEPARATOR.'.env';

        SubstituicaoEmArquivo::definirNoEnv($env, 'APP_NAME', $respostas['nome']);
        SubstituicaoEmArquivo::definirNoEnv($env, 'KIT_COR_PRIMARIA', $respostas['cor']);

        $this->propagarParaOSettings($respostas);

        Log::debug(
            '[CustomizadorDaInstalacao@aplicarSemBanco] Nome e cor reescritos | cor: '
            .($respostas['cor'] === '' ? 'padrao' : $respostas['cor']),
        );

        return [
            ['Nome do projeto', $respostas['nome']],
            ['Cor primária', $respostas['cor'] === '' ? 'Padrão do Filament' : $respostas['cor']],
        ];
    }

    /**
     * Escreve as respostas e devolve o resumo, já pronto para impressão.
     *
     * @param  array<string, mixed>  $respostas
     * @return list<array{0: string, 1: string}>
     */
    public function aplicar(array $respostas): array
    {
        $env    = $this->base.DIRECTORY_SEPARATOR.'.env';
        $nome   = (string) $respostas['nome'];
        $banco  = (string) $respostas['banco'];
        $email  = (string) $respostas['email'];
        $senha  = (string) $respostas['senha'];
        $cor    = (string) $respostas['cor'];
        $resumo = [];

        SubstituicaoEmArquivo::definirNoEnv($env, 'APP_NAME', $nome);
        $resumo[] = ['Nome do projeto', $nome];

        $this->aplicarBanco($env, $banco, $nome);
        $resumo[] = ['Banco de dados', $this->rotuloDoBanco($banco)];

        SubstituicaoEmArquivo::definirNoEnv($env, 'KIT_ADMIN_EMAIL', $email);
        $resumo[] = ['E-mail do administrador', $email];

        if ($senha !== '') {
            SubstituicaoEmArquivo::definirNoEnv($env, 'KIT_ADMIN_PASSWORD', $senha);
        }

        $resumo[] = ['Senha do administrador', $senha !== '' ? '•••••••• (a que você digitou)' : 'password (padrão do kit)'];

        SubstituicaoEmArquivo::definirNoEnv($env, 'KIT_COR_PRIMARIA', $cor);
        $resumo[] = ['Cor primária', $cor !== '' ? $cor : 'padrão do Filament'];

        if ($respostas['tenancy'] ?? false) {
            AtivadorDeTenancy::escreverEnv(
                $env,
                (string) $respostas['tenancy_label'],
                (string) $respostas['tenancy_label_plural'],
                Str::slug((string) $respostas['tenancy_label_plural']),
            );
            AtivadorDeTenancy::ligarPapeisPorTenant($this->base.DIRECTORY_SEPARATOR.'config');

            $resumo[] = ['Multi-organização', 'ligada — '.$respostas['tenancy_label_plural']];
        }

        $this->alinharConfigEmMemoria($respostas);

        Log::info(
            '[CustomizadorDaInstalacao@aplicar] Customização aplicada | banco: '.$banco,
            [
                'banco'       => $banco,
                'cor'         => $cor,
                'tenancy'     => (bool) ($respostas['tenancy'] ?? false),
                'admin_email' => $email,
            ],
        );

        return $resumo;
    }

    /**
     * O `--custom` também grava no settings, senão a resposta não tem efeito.
     *
     * Este método existe por causa do conflito entre as duas fontes de
     * configuração. O `--custom` reescreve o `.env`; o banco vence o `.env` em
     * tempo de execução (ADR-01). Sem esta propagação, quem rodasse
     * `kit:install --custom` num projeto já instalado veria o comando dizer
     * "Nome do projeto: X" e a tela continuar mostrando o nome antigo — a
     * resposta pareceria não ter efeito nenhum, sem erro nenhum.
     *
     * A gravação é **condicional à tabela existir**: o `--custom` também roda em
     * projeto que ainda não migrou, e ali o `.env` é a única fonte de qualquer
     * forma. O `catch (Throwable)` está aqui pelo mesmo motivo do alinhamento no
     * boot — um comando de instalação não pode morrer por causa da tela de
     * configurações.
     *
     * `refresh()` depois do `save()`: a gravação altera a config em memória do
     * processo do COMANDO, e o `alinharConfigEmMemoria()` do caminho completo
     * não passa por aqui.
     *
     * @param  array{nome: string, cor: string}  $respostas
     */
    private function propagarParaOSettings(array $respostas): void
    {
        try {
            if (! Schema::hasTable(config('settings.repositories.database.table') ?? 'settings')) {
                return;
            }

            $settings = app(ConfiguracoesDoKit::class);

            $settings->nome_da_aplicacao = $respostas['nome'];
            $settings->cor_primaria      = $respostas['cor'] !== '' ? $respostas['cor'] : null;
            $settings->save();

            Log::channel('configuracoes')->info(
                '[CustomizadorDaInstalacao@propagarParaOSettings] Nome e cor propagados para o settings | cor: '
                .($respostas['cor'] === '' ? 'padrao' : $respostas['cor']),
                ['nome' => $respostas['nome'], 'cor' => $respostas['cor']],
            );
        } catch (Throwable $e) {
            Log::channel('configuracoes')->warning(
                '[CustomizadorDaInstalacao@propagarParaOSettings] Settings não atualizado, valendo o .env | motivo: '.$e->getMessage(),
                ['exception' => $e],
            );
        }
    }

    /**
     * Os itens que continuam sendo editados à mão, com o lugar de cada um.
     *
     * A arte do login saiu desta lista: ela virou campo em
     * /admin/configuracoes-do-kit, junto com logo, favicon, nome, cor, dados de
     * e-mail e os defaults de tabela.
     *
     * @return list<string>
     */
    public static function itensManuais(): array
    {
        return [
            'Identidade e e-mail ........ /admin → Configurações do kit (logo, favicon, arte do login, SMTP)',
            'Acesso aos painéis ......... /admin → Funções (o campo Painel de cada papel)',
            'Matriz de permissões ....... database/seeders/PapeisSeeder.php',
            'Health checks .............. KitServiceProvider::configureHealthChecks()',
            'Comandos da UI ............. config/command-center.php',
            'Backups .................... config/backup.php',
            'Agente de IA ............... /admin → Agentes de IA',
        ];
    }

    /**
     * A observação de IA local não é enfeite: é a única diferença funcional entre
     * as três opções. Busca semântica e embeddings do kit dependem de `pgvector`,
     * que só existe no Postgres.
     */
    private function perguntarBanco(): string
    {
        // `(string)`: o `select()` dos Prompts devolve `int|string` porque a chave da opção
        // pode ser inteira. As três chaves declaradas logo abaixo são strings não numéricas,
        // então o retorno é sempre uma delas.
        $banco = (string) select(
            label: 'Banco de dados',
            options: [
                'sqlite' => 'SQLite — padrão, não depende de nenhum serviço externo',
                'pgsql'  => 'PostgreSQL — recomendado: único com pgvector, exigido pelas funções de IA local',
                'mysql'  => 'MySQL / MariaDB — traga o seu servidor (o kit não sobe container MySQL)',
            ],
            default: 'sqlite',
        );

        if ($banco !== 'pgsql') {
            note(
                'Escolha registrada. Lembre que as funções de IA local que usam busca semântica '
                .'(embeddings/pgvector) só funcionam no PostgreSQL — o resto do kit roda igual.'
            );
        }

        return $banco;
    }

    /** @return array<string, mixed> */
    private function perguntarTenancy(): array
    {
        $padrao = (string) config('kit.tenancy.label', 'Organização');

        if (! confirm(
            label: 'Ligar o modo multi-organização (multi-tenancy)?',
            default: false,
            hint: 'O painel /app passa a ser /app/{organização}. Ligar depois exige recriar o banco.',
        )) {
            return ['tenancy' => false];
        }

        $label = text(label: 'Como chamar cada organização?', default: $padrao, required: true);

        return [
            'tenancy'              => true,
            'tenancy_label'        => $label,
            'tenancy_label_plural' => text(
                label: 'E no plural?',
                default: $this->pluralSugerido($label, $padrao),
                required: true,
            ),
        ];
    }

    /**
     * O plural OFERECIDO — que é só um palpite, e por isso é editável.
     *
     * Acrescentar "s" é a regra de plural do inglês, não do português: o default do
     * kit é "Organização", e a sugestão ingênua oferecia **"Organizaçãos"** para
     * quem só apertasse Enter — no caminho mais comum de todos.
     *
     * Quando o singular não foi alterado, a resposta certa já está na config
     * (`label_plural`), e é ela que vale. Só um rótulo NOVO cai no palpite, e aí o
     * "+s" acerta a maioria das palavras que alguém escolheria aqui (Empresa,
     * Escola, Loja, Unidade, Cliente) e erra visivelmente nas outras — o que é
     * aceitável num campo que está ali, preenchido, esperando correção.
     */
    private function pluralSugerido(string $label, string $padrao): string
    {
        if ($label === $padrao) {
            return (string) config('kit.tenancy.label_plural', $padrao.'s');
        }

        return $label.'s';
    }

    /**
     * O bloco `DB_*` do driver escolhido.
     *
     * Postgres usa os valores que o `docker-compose.yml` lê do próprio .env, para
     * que `docker compose up -d` suba o container já com este banco. MySQL usa
     * `root` sem senha, que é o mesmo default que o instalador do Laravel grava —
     * o kit não sobe container MySQL, então o servidor é o de quem instalou.
     */
    private function aplicarBanco(string $env, string $banco, string $nome): void
    {
        SubstituicaoEmArquivo::aplicar($env, '/^#?\s*DB_CONNECTION=.*$/m', 'DB_CONNECTION='.$banco);

        foreach ($this->valoresDoBanco($banco, $nome) as $chave => $valor) {
            SubstituicaoEmArquivo::aplicar(
                $env,
                '/^#?\s*'.$chave.'=.*$/m',
                $chave.'='.$valor,
                PHP_EOL.$chave.'='.$valor.PHP_EOL,
            );
        }
    }

    /**
     * O bloco `DB_*` do driver — vazio para SQLite, que não depende de nada.
     *
     * @return array<string, string>
     */
    private function valoresDoBanco(string $banco, string $nome): array
    {
        return match ($banco) {
            'pgsql' => [
                'DB_HOST'     => '127.0.0.1',
                'DB_PORT'     => '5432',
                'DB_DATABASE' => $this->nomeDeBanco($nome),
                'DB_USERNAME' => 'starter_kit',
                'DB_PASSWORD' => 'secret',
            ],
            'mysql' => [
                'DB_HOST'     => '127.0.0.1',
                'DB_PORT'     => '3306',
                'DB_DATABASE' => $this->nomeDeBanco($nome),
                'DB_USERNAME' => 'root',
                'DB_PASSWORD' => '',
            ],
            default => [],
        };
    }

    /**
     * Nome de banco a partir do nome do projeto.
     *
     * Identificador, não slug: hífen exige aspas em Postgres e nome começando com
     * dígito é recusado pelo MySQL. `Str::slug` primeiro para derrubar acento e
     * símbolo; o resto é a normalização que o instalador do Laravel também faz.
     */
    private function nomeDeBanco(string $nome): string
    {
        $identificador = str_replace('-', '_', Str::slug($nome, '-'));

        if ($identificador === '') {
            return 'starter_kit';
        }

        return ctype_digit($identificador[0]) ? '_'.$identificador : $identificador;
    }

    private function rotuloDoBanco(string $banco): string
    {
        return match ($banco) {
            'pgsql' => 'PostgreSQL (com pgvector, para IA local)',
            'mysql' => 'MySQL / MariaDB',
            default => 'SQLite',
        };
    }

    /** @param  array<string, mixed>  $respostas */
    private function alinharConfigEmMemoria(array $respostas): void
    {
        config([
            'app.name'         => $respostas['nome'],
            'database.default' => $respostas['banco'],
            'kit.admin.email'  => $respostas['email'],
            'kit.cor_primaria' => $respostas['cor'] !== '' ? $respostas['cor'] : null,
        ]);

        if ($respostas['senha'] !== '') {
            config(['kit.admin.password' => $respostas['senha']]);
        }

        $this->alinharConexao((string) $respostas['banco'], (string) $respostas['nome']);

        if ($respostas['tenancy'] ?? false) {
            config([
                'kit.tenancy.label'        => $respostas['tenancy_label'],
                'kit.tenancy.label_plural' => $respostas['tenancy_label_plural'],
                'kit.tenancy.slug'         => Str::slug((string) $respostas['tenancy_label_plural']),
            ]);

            AtivadorDeTenancy::alinharConfigEmMemoria();
        }
    }

    /**
     * A conexão escolhida, alinhada em memória.
     *
     * Trocar só `database.default` não basta quando o driver escolhido já era o
     * default e o que mudou foi o banco em si (uma reinstalação em que o nome do
     * projeto mudou): o array de conexão em memória continuaria apontando para o
     * banco antigo.
     *
     * SQLite não entra: ele não recebe nenhum valor customizado, e um
     * `DB::purge('sqlite')` aqui descartaria uma conexão que pode estar em uso —
     * na suíte de testes, que roda em `:memory:`, isso apaga o banco inteiro no
     * meio do caso.
     */
    private function alinharConexao(string $banco, string $nome): void
    {
        $valores = $this->valoresDoBanco($banco, $nome);

        if ($valores === []) {
            return;
        }

        config([
            "database.connections.{$banco}.host"     => $valores['DB_HOST'],
            "database.connections.{$banco}.port"     => $valores['DB_PORT'],
            "database.connections.{$banco}.database" => $valores['DB_DATABASE'],
            "database.connections.{$banco}.username" => $valores['DB_USERNAME'],
            "database.connections.{$banco}.password" => $valores['DB_PASSWORD'],
        ]);

        DB::purge($banco);
    }

    private function pulou(string $motivo): null
    {
        Log::info(
            '[CustomizadorDaInstalacao@perguntar] Customização pulada | motivo: '.$motivo,
            ['motivo' => $motivo],
        );

        return null;
    }
}
