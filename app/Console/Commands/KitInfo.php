<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Models\User;
use App\Settings\ConfiguracoesDoKit;
use App\Support\AdministradorDaInstalacao;
use App\Support\CorPrimaria;
use App\Support\CustomizadorDaInstalacao;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * Mostra como ESTE projeto foi customizado — e de onde cada valor está vindo.
 *
 * Três lugares respondiam pedaços da pergunta e nenhum respondia inteira: o resumo do
 * `kit:install` aparece uma vez e some, a tela `/admin/configuracoes-do-kit` mostra o banco e nada
 * do `.env`, e o `config:show kit` mostra a config efetiva sem dizer a origem. Este comando reúne
 * os três, é SOMENTE LEITURA, e aponta para quem muda cada coisa.
 *
 * ## A lista de configurações é o mapa, iterado — nunca uma cópia
 *
 * `ConfiguracoesDoKit::mapaDeConfiguracao()` é a única cópia da lista de propriedades do settings
 * (o docblock daquela classe cobra três lugares ao acrescentar uma). Uma tabela de rótulos bonita
 * aqui seria a QUARTA, e esquecer a linha nela é defeito silencioso — a propriedade nova
 * simplesmente não apareceria. Daí o rótulo mecânico do `Str::headline()`. Ver ADR-02.
 *
 * ## Não revela mais do que o `.env` já revela
 *
 * Quem roda `php artisan` tem o `.env`. Mas a SAÍDA de um comando de resumo tem outra vida: vai
 * para chamado de suporte, captura de tela e log de CI. Então segredo sai como "definida/vazia",
 * e-mail do administrador sai mascarado (mesma régua do `kit:admin`), a senha não sai de jeito
 * nenhum, e o log leva as CHAVES divergentes, nunca os valores. Ver ADR-04.
 *
 * ## Banco indisponível degrada a linha, não o comando
 *
 * `noBanco()` existe porque 90% do conteúdo é `config()` e está disponível sem banco — inclusive
 * antes do primeiro `migrate`, que é justamente quando alguém quer saber como isto está
 * configurado. Ver ADR-06.
 */
class KitInfo extends Command
{
    protected $signature = 'kit:info';

    protected $description = 'Mostra como este projeto foi customizado: instalação, configurações do kit e de onde cada valor vem';

    public function handle(): int
    {
        $doBanco = $this->noBanco(static fn (): bool => ConfiguracoesDoKit::gravadoNoBanco()) === true;

        $this->cabecalho($doBanco);
        $this->instalacao();
        $this->configuracoes();

        $divergencias = $doBanco ? $this->divergencias() : [];

        $this->manuais();

        Log::channel('configuracoes')->debug(
            '[KitInfo@handle] Resumo exibido | fonte: '.($doBanco ? 'banco' : 'env'),
            ['fonte' => $doBanco ? 'banco' : 'env', 'divergencias' => array_keys($divergencias)],
        );

        return self::SUCCESS;
    }

    private function cabecalho(bool $doBanco): void
    {
        $this->newLine();
        $this->linha('Versão do kit', (string) config('kit.version'));
        $this->linha('Fonte da configuração', $doBanco
            ? 'banco (/admin/configuracoes-do-kit) — o .env semeia e é o plano B'
            : '.env — a tabela de settings ainda não existe');
        $this->newLine();
    }

    /**
     * As cinco respostas do `kit:install`, com o valor VIGENTE.
     *
     * Vigente é `config()`, não `env()`: o `KitServiceProvider` sobrepõe a config do processo com
     * o banco no boot, e é o banco que vale em tempo de execução. Ler o `.env` aqui mostraria o
     * que a instalação escolheu um dia, não o que está valendo.
     */
    private function instalacao(): void
    {
        $this->components->info('O que a instalação perguntou:');

        $this->linha('Nome do projeto', (string) config('app.name'));
        $this->linha('Banco de dados', $this->banco());
        $this->linha('Administrador da instalação', $this->administradores());
        $this->linha('Senha do administrador', 'não exibida — troque com php artisan kit:admin');
        $this->linha('Cor primária', $this->cor());
        $this->linha('Multi-organização', $this->organizacoes());

        $this->newLine();
    }

    private function configuracoes(): void
    {
        $this->components->info('Configurações do kit (/admin/configuracoes-do-kit):');

        foreach (ConfiguracoesDoKit::mapaDeConfiguracao() as $propriedade => $chave) {
            $this->linha(Str::headline($propriedade), $this->exibir($propriedade, config($chave)));
        }

        $this->newLine();
    }

    /**
     * Onde o `.env` diz diferente do banco — e nada quando não diz.
     *
     * A seção existe por causa da confusão que o próprio settings documenta: alguém edita o
     * `.env`, nada muda, e ninguém consegue explicar por quê. A informação útil não é o par de
     * valores de cada uma das 44 chaves (quase todas iguais, e a divergência somiria no meio) — é
     * a lista curta do que discorda.
     *
     * ## O separador não é seta, e não é travessão
     *
     * `no .env: X · no banco: Y`, e não `X → Y`. Medido: dentro de um comando artisan deste
     * projeto o `→` (U+2192) **desaparece da saída**, inclusive num `$this->line()` cru, enquanto
     * `—` (U+2014) e `·` (U+00B7) passam. Reprodução mínima:
     *
     *     Artisan::command('probe', fn () => $this->line("a \xE2\x86\x92 b"));
     *     Artisan::call('probe');   // a saída vem "a  b"
     *
     * Fora do comando (`BufferedOutput`, `OutputStyle::writeln`, o próprio `TwoColumnDetail`
     * instanciado à mão) a seta sobrevive — a causa não foi localizada no vendor e por isso NÃO
     * está afirmada aqui. O sintoma é anterior a este comando: os rótulos de
     * `CustomizadorDaInstalacao::itensManuais()` usam `→` e já saem sem ele no `kit:install`.
     *
     * E o separador é `·`, não `—`, porque `—` já é como o comando escreve "vazio" em toda linha
     * — com ele no meio, uma chave vazia no `.env` sairia `no .env: — — no banco: X`.
     *
     * @return array<string, array{arquivo: mixed, vigente: mixed}>
     */
    private function divergencias(): array
    {
        $divergentes  = [];
        $propriedades = array_flip(ConfiguracoesDoKit::mapaDeConfiguracao());

        foreach (ConfiguracoesDoKit::valoresDosArquivos() as $chave => $doArquivo) {
            $vigente = config($chave);

            if ($this->normalizar($doArquivo) !== $this->normalizar($vigente)) {
                $divergentes[$chave] = ['arquivo' => $doArquivo, 'vigente' => $vigente];
            }
        }

        if ($divergentes === []) {
            return [];
        }

        $this->components->info('Onde o .env diz diferente do banco:');

        foreach ($divergentes as $chave => $par) {
            $this->linha($chave, $this->ehSegredo($propriedades[$chave] ?? '')
                ? 'diverge (valores não exibidos)'
                : 'no .env: '.$this->comoTexto($par['arquivo']).' · no banco: '.$this->comoTexto($par['vigente']));
        }

        $this->newLine();

        return $divergentes;
    }

    private function manuais(): void
    {
        $this->components->info('O que continua sendo ajustado à mão:');
        $this->components->bulletList(CustomizadorDaInstalacao::itensManuais());
        $this->newLine();

        $this->linha(
            'Para mudar',
            'kit:install --custom (nome e cor) · /admin/configuracoes-do-kit · kit:admin · kit:tenancy',
        );
        $this->newLine();
    }

    /*
    |--------------------------------------------------------------------------
    | As linhas que dependem de mais de um valor
    |--------------------------------------------------------------------------
    */

    private function banco(): string
    {
        $conexao = (string) config('database.default');
        $nome    = (string) config("database.connections.{$conexao}.database");
        $host    = config("database.connections.{$conexao}.host");

        return filled($host)
            ? "{$conexao} — {$host}/{$nome}"
            : "{$conexao} — {$nome}";
    }

    /**
     * Quem administra a instalação, mascarado, e TODOS eles.
     *
     * Mais de um `master_global` é estado possível — o papel pode ser concedido na tela de
     * papéis —, e escolher o primeiro esconderia isso. É a mesma razão pela qual
     * `AdministradorDaInstalacao::todos()` devolve coleção em vez de `?User`.
     */
    private function administradores(): string
    {
        $administradores = $this->noBanco(static fn () => AdministradorDaInstalacao::todos());

        if ($administradores === null) {
            return 'indisponível (banco não acessível)';
        }

        if ($administradores->isEmpty()) {
            return 'nenhum — rode php artisan db:seed --class=UsuarioAdminSeeder';
        }

        return $administradores
            ->map(static fn (User $u): string => '#'.$u->getKey().' — '.Str::mask((string) $u->email, '*', 3))
            ->implode(', ');
    }

    /**
     * A cor, lida pelo FORMATO do que `CorPrimaria::paleta()` devolve.
     *
     * A precedência (hex válido vence; hex inválido cai para o nome; nome inexistente cai para o
     * padrão) NÃO é reimplementada aqui: o docblock de `CorPrimaria::resolver()` avisa que uma
     * segunda cópia é a forma de ela divergir no primeiro ajuste. `primary` string só acontece
     * quando o hexadecimal venceu; array de tons só quando foi o nome. Ver ADR-03.
     */
    private function cor(): string
    {
        $paleta = CorPrimaria::paleta();

        if ($paleta === []) {
            return 'padrão do Filament (âmbar)';
        }

        return is_string($paleta['primary'] ?? null)
            ? $paleta['primary'].' (hexadecimal — vence o nome)'
            : config('kit.cor_primaria').' (paleta do Filament)';
    }

    private function organizacoes(): string
    {
        if (! config('kit.tenancy.enabled')) {
            return 'desligada — php artisan kit:tenancy';
        }

        $rotulo  = 'ligada — '.config('kit.tenancy.label_plural').' em /admin/'.config('kit.tenancy.slug');
        $quantas = $this->noBanco(static fn (): int => Tenant::count());

        return $quantas === null ? $rotulo : "{$rotulo}, {$quantas} cadastrada(s)";
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers de leitura e formatação
    |--------------------------------------------------------------------------
    */

    /**
     * Roda algo que toca o banco; sem banco, devolve `null` em vez de derrubar o comando.
     *
     * Um resumo com uma linha "indisponível" vale mais do que nenhum resumo — e sem banco é
     * exatamente quando alguém pergunta como o projeto está configurado. O `catch` é de
     * `Throwable` pelo mesmo motivo do `KitServiceProvider`: `Schema::hasTable()` num banco
     * inexistente lança antes de responder, e nem sempre uma `Exception`.
     *
     * O genérico não é enfeite: sem ele o retorno é `mixed`, e cada chamador precisaria de um
     * `instanceof` para poder usar a coleção ou o inteiro que pediu.
     *
     * @template TValor
     *
     * @param  callable(): TValor  $consulta
     * @return TValor|null
     */
    private function noBanco(callable $consulta): mixed
    {
        try {
            return $consulta();
        } catch (Throwable) {
            return null;
        }
    }

    /** Uma linha do resumo, no mesmo visual do `kit:install`. */
    private function linha(string $rotulo, string $valor): void
    {
        $this->components->twoColumnDetail("<fg=gray>{$rotulo}</>", $valor);
    }

    /**
     * Como um valor aparece na tela.
     *
     * Segredo vira presença, nunca valor. O booleano vira `sim`/`não` porque `(string) false` é
     * string vazia — a linha ficaria em branco e se leria como "não configurado", que é outra
     * coisa. Vazio de verdade vira travessão, pelo mesmo motivo.
     */
    private function exibir(string $propriedade, mixed $valor): string
    {
        if ($this->ehSegredo($propriedade)) {
            return filled($valor) ? 'definida' : 'vazia';
        }

        return $this->comoTexto($valor);
    }

    private function comoTexto(mixed $valor): string
    {
        return match (true) {
            is_bool($valor)  => $valor ? 'sim' : 'não',
            is_array($valor) => $valor === [] ? '—' : implode(', ', array_map(strval(...), $valor)),
            blank($valor)    => '—',
            default          => (string) $valor,
        };
    }

    private function ehSegredo(string $propriedade): bool
    {
        return in_array($propriedade, ConfiguracoesDoKit::encrypted(), true);
    }

    /**
     * `.env` × banco comparam pelo TEXTO, porque os tipos divergem por construção.
     *
     * Dois casos medidos: a migration de settings semeia texto com `textoOuNulo()`, então o banco
     * guarda `null` onde o arquivo diz `''` — em TODA instalação recém-semeada; e a porta de SMTP
     * chega `string` do `env()` e é gravada `int`. Com `!==` cru, o comando acusaria divergência
     * em toda instalação, no primeiro `migrate`, e a seção que existe para apontar problema real
     * viraria ruído permanente.
     *
     * `(string)` sozinho não serve: `false` viraria `''` e casaria com `null`, escondendo o
     * booleano que diverge de verdade. Ver ADR-05.
     */
    private function normalizar(mixed $valor): string
    {
        return match (true) {
            is_bool($valor)  => $valor ? '1' : '0',
            $valor === null  => '',
            is_array($valor) => (string) json_encode($valor),
            default          => (string) $valor,
        };
    }
}
