<?php

namespace App\Support;

use Closure;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;
use Throwable;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\note;
use function Laravel\Prompts\text;

/**
 * A etapa de domínio local do `kit:install` — a linha no `hosts` e a `APP_URL`.
 *
 * Fecha a última lacuna manual da instalação: o comando termina imprimindo
 * `http://localhost:8000/app` e deixava para a pessoa descobrir, na página
 * `dominio-local.md`, como trocar isso por um nome de verdade. São dois passos
 * (uma linha em arquivo de sistema, que exige elevação, e uma chave no `.env`)
 * que o comando tem informação suficiente para oferecer sozinho.
 *
 * A etapa é **opcional, idempotente e não-abortante**: Enter em tudo instala
 * como sempre, rodar de novo não duplica nada, e o que falhar vira aviso.
 *
 * ## O que decide se deu certo é o ARQUIVO, nunca o código de saída
 *
 * `Start-Process -Verb RunAs` sobe um processo novo e não devolve código de
 * retorno confiável — e `ipconfig /flushdns` responde "bem-sucedida" **sem**
 * elevação nenhuma (`docs/pt/comecar/dominio-local.md`, seção "Armadilhas").
 * Nenhum dos dois prova coisa alguma. O oráculo é reler o `hosts` e procurar o
 * domínio; é isso, e só isso, que autoriza escrever a `APP_URL`.
 *
 * ## Windows executa, Unix instrui
 *
 * O `hosts` do Windows aceita elevação por UAC no meio da instalação; no Linux
 * e no macOS o equivalente é `sudo` em processo não-interativo, que pediria
 * senha no lugar errado. Lá a etapa imprime a linha pronta e **ajusta o `.env`
 * assim mesmo** — o ajuste da URL não precisa de elevação em sistema nenhum.
 *
 * ## Por que tudo é injetável
 *
 * Cada parâmetro do construtor existe porque um caso de teste precisa dele, e
 * nenhum deles tem alternativa barata:
 *
 * - `$base`: sem diretório-base injetado a suíte reescreveria o `.env` da
 *   máquina de quem a roda. É o mesmo desenho de `CustomizadorDaInstalacao`, e
 *   aqui ele é **obrigatório** — nenhuma montagem de caminho desta classe pode
 *   partir de `base_path()`, `getcwd()` e afins.
 * - `$executor`: sem ele o teste abriria uma janela de UAC de verdade.
 * - `$hosts`: sem ele o teste escreveria no `hosts` do sistema.
 * - `$resolvedor`: a sonda de resolução real (Herd, Valet, dnsmasq, DNS
 *   corporativo) responde por `*.test` **sem** linha no arquivo; sem injetá-la
 *   o teste dependeria da rede da máquina.
 * - `$so`: o comportamento por sistema operacional é uma tabela de decisão de
 *   três linhas, e `PHP_OS_FAMILY` é constante de compilação — sem injeção só
 *   uma das três linhas seria exercida em cada máquina.
 */
final class HostLocal
{
    /** O piso do slug, idêntico ao que já produz o `COMPOSE_PROJECT_NAME`. */
    private const NOME_PADRAO = 'starter-kit';

    /** @var Closure(string): int */
    private Closure $executor;

    /** @var Closure(string): bool */
    private Closure $resolvedor;

    private string $hosts;

    /**
     * @param  string  $base  diretório do projeto que está sendo instalado
     * @param  (Closure(string): int)|null  $executor  recebe o comando e devolve o código de saída
     * @param  string|null  $hosts  caminho do arquivo de hosts; nulo usa o do sistema
     * @param  (Closure(string): bool)|null  $resolvedor  o domínio já resolve fora do arquivo?
     */
    public function __construct(
        private readonly string $base,
        ?Closure $executor = null,
        ?string $hosts = null,
        ?Closure $resolvedor = null,
        private readonly string $so = PHP_OS_FAMILY,
    ) {
        $this->executor   = $executor ?? self::executorDoSistema();
        $this->resolvedor = $resolvedor ?? static fn (string $dominio): bool => gethostbyname($dominio) !== $dominio;
        $this->hosts      = $hosts ?? $this->caminhoPadraoDoHosts();
    }

    /**
     * As duas perguntas e o que decorre delas. Devolve o aviso, ou `null` quando não há o que avisar.
     *
     * O `$interativo` chega de `KitInstall::temTerminal()` e é o gate da etapa
     * inteira: sem alguém do outro lado, nada é perguntado e nada é decidido.
     * Ele é PARÂMETRO, e não uma consulta feita aqui dentro, porque
     * `runningUnitTests()` deixa `temTerminal()` verdadeiro dentro da suíte — um
     * gate lido daqui nunca teria o ramo negativo exercitado.
     */
    public function oferecer(bool $interativo): ?string
    {
        if (! $interativo) {
            return null;
        }

        /*
         * O rótulo cabe em 74 colunas de propósito: é onde o Laravel Prompts trunca,
         * e o exemplo — que é a cláusula do requisito — mora no fim dele.
         */
        if (! confirm(
            label: "Cadastrar um domínio local (ex.: {$this->urlSugerida()})?",
            default: false,
            hint: 'Escreve uma linha no hosts da máquina e ajusta a APP_URL.',
        )) {
            return null;
        }

        $dominio = text(
            label: 'Qual domínio?',
            default: $this->dominioSugerido(),
            validate: fn (string $valor): ?string => $this->erroDoDominio($valor),
            hint: 'Só o nome, sem http:// e sem barra. O sufixo .test é reservado pela RFC 6761 para isto.',
        );

        note(
            "A APP_URL passa a ser http://{$dominio}. Dois efeitos conhecidos:\n"
            .'- login social: a URI de callback registrada no provedor muda junto;'."\n"
            .'- npm run dev: o Vite serve de localhost:5173 e restringe CORS — com npm run build não aparece.'
        );

        return $this->processar($dominio);
    }

    /**
     * Sonda, cadastra se preciso, e só então ajusta a `APP_URL`.
     *
     * Devolve o aviso quando o cadastro não se confirmou — nunca uma exceção, e
     * nunca um código de falha: a etapa é acessória e o `KitInstall` não aborta
     * a instalação por causa dela.
     */
    public function processar(string $dominio): ?string
    {
        try {
            $erro = $this->erroDoDominio($dominio);

            if ($erro !== null) {
                return $erro;
            }

            if ($this->jaResolve($dominio)) {
                $this->aplicarNoEnv($dominio);

                return null;
            }

            if ($this->so !== 'Windows') {
                note($this->instrucaoManual($dominio));
                $this->aplicarNoEnv($dominio);

                return null;
            }

            if ($this->cadastrar($dominio)) {
                $this->aplicarNoEnv($dominio);

                return null;
            }

            return $this->avisoDeFalha($dominio);
        } catch (Throwable $excecao) {
            Log::channel('configuracoes')->warning(
                "[HostLocal@processar] Etapa do host local falhou | dominio: {$dominio}",
                ['dominio' => $dominio, 'erro' => $excecao->getMessage(), 'so' => $this->so],
            );

            return $this->avisoDeFalha($dominio);
        }
    }

    /** `Str::slug()` do nome escolhido na primeira pergunta, com o mesmo piso do nome dos containers. */
    public function dominioSugerido(): string
    {
        return (Str::slug((string) config('app.name')) ?: self::NOME_PADRAO).'.test';
    }

    public function urlSugerida(): string
    {
        return 'http://'.$this->dominioSugerido();
    }

    public function caminhoDoHosts(): string
    {
        return $this->hosts;
    }

    /**
     * O domínio já resolve? — e a pergunta é sobre a RESOLUÇÃO, não sobre o texto do arquivo.
     *
     * Quem tem Laravel Herd ou Valet resolve `*.test` por dnsmasq, **sem** linha
     * nenhuma no `hosts`. Olhar só o arquivo faria a etapa pedir elevação à toa
     * e sujar o arquivo de sistema de quem já tinha a máquina configurada.
     *
     * No arquivo, só uma linha ATIVA do domínio EXATO conta: `# 127.0.0.1 x.test`
     * é um comentário e não resolve nada, `app.x.test` e `x.test.br` são outros
     * domínios, e caixa alta é o mesmo domínio (DNS não diferencia).
     */
    public function jaResolve(string $dominio): bool
    {
        return $this->temLinhaAtiva($dominio) || ($this->resolvedor)($dominio);
    }

    /**
     * O comando de elevação — o MESMO que a documentação do repositório ensina.
     *
     * Divergir daqui quebra a promessa de RQ-06 ("rode conforme a documentação")
     * em silêncio: a página continuaria descrevendo um procedimento e o comando
     * rodando outro. Há caso de teste comparando os dois textos.
     *
     * O domínio entra já validado por `erroDoDominio()` — ele vira argumento de
     * um processo ELEVADO, e aspa ou ponto-e-vírgula ali emendariam um segundo
     * comando rodando como Administrador.
     */
    public function comandoDeElevacao(string $dominio): string
    {
        return 'Start-Process pwsh -Verb RunAs -Wait -ArgumentList \'-NoProfile\',\'-Command\', '
            .'\'Add-Content "$env:windir\System32\drivers\etc\hosts" "`n127.0.0.1`t'
            .$dominio.'" -Encoding ascii\'';
    }

    /** A linha pronta para quem está no Linux ou no macOS, onde a etapa instrui em vez de executar. */
    public function instrucaoManual(string $dominio): string
    {
        return "Falta uma linha no {$this->caminhoDoHosts()} (precisa de sudo):\n"
            ."    echo '127.0.0.1\t{$dominio}' | sudo tee -a {$this->caminhoDoHosts()}";
    }

    /**
     * Executa o cadastro e **confere relendo o arquivo**.
     *
     * A releitura acontece DEPOIS da execução, e não reaproveita a sonda de
     * antes: entre uma e outra há um diálogo de UAC esperando uma pessoa, e
     * nesse intervalo o Docker Desktop, uma VPN ou um segundo `kit:install`
     * podem ter escrito no mesmo arquivo.
     */
    public function cadastrar(string $dominio): bool
    {
        $comando = $this->comandoDeElevacao($dominio);

        ($this->executor)($comando);

        if (! $this->temLinhaAtiva($dominio)) {
            Log::channel('configuracoes')->warning(
                "[HostLocal@cadastrar] Host nao entrou no arquivo | dominio: {$dominio}",
                ['dominio' => $dominio, 'motivo' => 'elevacao negada ou bloqueada', 'comando' => $comando],
            );

            return false;
        }

        Log::channel('configuracoes')->info(
            "[HostLocal@cadastrar] Host local cadastrado | dominio: {$dominio}",
            ['dominio' => $dominio, 'caminho' => $this->caminhoDoHosts(), 'so' => $this->so],
        );

        return true;
    }

    /**
     * O que recusa uma entrada — e o porquê de cada recusa.
     *
     * O texto digitado num terminal comum vira DUAS coisas perigosas: argumento
     * de um processo elevado e linha de um arquivo de sistema. Esquema, barra,
     * espaço, aspas e quebra de linha são recusados por isso, não por estética.
     *
     * Devolve a mensagem de erro, ou `null` quando o domínio serve. É o formato
     * que o `validate:` do Laravel Prompts espera: a mensagem aparece e a
     * pergunta é feita de novo.
     */
    public function erroDoDominio(string $dominio): ?string
    {
        if (Str::endsWith(Str::lower($dominio), '.local')) {
            return "{$dominio}: use .test, e nunca .local (RFC 6762, mDNS)";
        }

        if (preg_match('/\A[a-z0-9]([a-z0-9-]*[a-z0-9])?(\.[a-z0-9]([a-z0-9-]*[a-z0-9])?)+\z/i', $dominio) !== 1) {
            return "{$dominio}: só o nome, sem http:// nem barra nem espaço";
        }

        return null;
    }

    /**
     * Grava a `APP_URL` no `.env` **e** alinha o `config()` em memória.
     *
     * Sem a segunda metade o `banner()` do `kit:install`, que lê
     * `config('app.url')`, imprimiria o endereço velho logo depois de a pessoa
     * ter escolhido outro — a feature funcionaria e pareceria não ter
     * funcionado.
     */
    private function aplicarNoEnv(string $dominio): void
    {
        $anterior = (string) config('app.url');
        $url      = 'http://'.$dominio;

        SubstituicaoEmArquivo::definirNoEnv($this->caminhoDoEnv(), 'APP_URL', $url);

        config(['app.url' => $url]);

        Log::channel('configuracoes')->info(
            "[HostLocal@aplicarNoEnv] APP_URL ajustada | url: {$url}",
            ['url' => $url, 'anterior' => $anterior],
        );
    }

    /** O único ponto em que o caminho do `.env` é montado, e ele parte do diretório recebido. */
    private function caminhoDoEnv(): string
    {
        return $this->base.DIRECTORY_SEPARATOR.'.env';
    }

    private function caminhoPadraoDoHosts(): string
    {
        return match ($this->so) {
            'Windows' => 'C:\Windows\System32\drivers\etc\hosts',
            default   => '/etc/hosts',
        };
    }

    /** Há uma linha ATIVA apontando exatamente este domínio? */
    private function temLinhaAtiva(string $dominio): bool
    {
        foreach (preg_split('/\R/', File::get($this->caminhoDoHosts())) ?: [] as $linha) {
            $util = trim(explode('#', $linha)[0]);

            if ($util === '') {
                continue;
            }

            $nomes = preg_split('/\s+/', $util) ?: [];
            array_shift($nomes);

            foreach ($nomes as $nome) {
                if (strcasecmp($nome, $dominio) === 0) {
                    return true;
                }
            }
        }

        return false;
    }

    /** O destino alcançável de quem chegou ao estado de erro: o comando pronto para colar. */
    private function avisoDeFalha(string $dominio): string
    {
        return "Não consegui cadastrar {$dominio} no arquivo hosts — a APP_URL continua como estava. "
            .'Num PowerShell como administrador: '.$this->comandoDeElevacao($dominio);
    }

    /**
     * O executor de verdade: um shell do PowerShell recebendo o comando documentado.
     *
     * `pwsh` primeiro porque é o que a documentação usa; `powershell` é o piso do
     * Windows, onde o 7 pode não estar instalado. Sem nenhum dos dois, o processo
     * falha — e falhar aqui não decide nada, porque o oráculo é o arquivo.
     */
    private static function executorDoSistema(): Closure
    {
        return static function (string $comando): int {
            $finder = new ExecutableFinder;
            $shell  = $finder->find('pwsh') ?? $finder->find('powershell') ?? 'powershell';

            $processo = new Process([$shell, '-NoProfile', '-Command', $comando], timeout: 300);
            $processo->run();

            return $processo->getExitCode() ?? 1;
        };
    }
}
