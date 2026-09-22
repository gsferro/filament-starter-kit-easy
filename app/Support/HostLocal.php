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
 * ## O perímetro à prova de exceção é `oferecer()`, não `processar()`
 *
 * O `try/catch` que sustenta o "não-abortante" precisa cobrir **as perguntas**,
 * e não só o trabalho depois delas. No Windows o Laravel sempre cai no fallback
 * do Symfony (`ConfiguresPrompts::configurePrompts()` liga
 * `Prompt::fallbackWhen(windows_os() || runningUnitTests())`), e o
 * `QuestionHelper` lança `MissingInputException` quando o STDIN acaba no meio de
 * uma pergunta. Como a etapa roda **antes** do `banner()`, uma exceção escapando
 * daqui mataria banner, resumo, avisos, oferta de testes e de estrela — depois
 * de migrate, seed e build já terem acontecido. `processar()` mantém o seu
 * próprio `catch` porque também é ponto de entrada público.
 *
 * ## O que decide se deu certo é o ARQUIVO, nunca o código de saída
 *
 * `Start-Process -Verb RunAs` sobe um processo novo e não devolve código de
 * retorno confiável — e `ipconfig /flushdns` responde "bem-sucedida" **sem**
 * elevação nenhuma (`docs/pt/comecar/dominio-local.md`, seção "Armadilhas").
 * Nenhum dos dois prova coisa alguma. O oráculo é reler o `hosts` e procurar o
 * domínio; é isso, e só isso, que autoriza escrever a `APP_URL`.
 *
 * **E é UM arquivo só.** O caminho que o comando elevado escreve e o caminho que
 * o oráculo relê saem os dois de `caminhoDoHosts()`. Divergir ali produz o pior
 * desfecho possível: a linha entra, o oráculo não a vê, a `APP_URL` nunca é
 * ajustada, e o aviso manda colar de novo um comando que **duplicaria** a linha.
 *
 * ## Windows executa, Unix instrui
 *
 * O `hosts` do Windows aceita elevação por UAC no meio da instalação; no Linux
 * e no macOS o equivalente é `sudo` em processo não-interativo, que pediria
 * senha no lugar errado. Lá a etapa imprime a linha pronta e **ajusta o `.env`
 * assim mesmo** — o ajuste da URL não precisa de elevação em sistema nenhum.
 * A assimetria vale também no ramo de ERRO: o aviso de falha instrui no idioma
 * do sistema em que a falha aconteceu.
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

    /**
     * Os TLD que a RFC 6761 reserva para uso local — os únicos que passam sem confirmação extra.
     *
     * @var list<string>
     */
    private const TLD_RESERVADOS = ['test', 'localhost', 'example', 'invalid'];

    /** Limites do DNS (RFC 1035 §2.3.4), em octetos. */
    private const MAX_ROTULO = 63;

    private const MAX_NOME = 253;

    /** @var Closure(string): int */
    private Closure $executor;

    /** @var Closure(string): ?string */
    private Closure $resolvedor;

    private string $hosts;

    /**
     * A `APP_URL` já foi reescrita nesta instância?
     *
     * Existe para o aviso de falha não AFIRMAR o que pode ser falso: uma exceção
     * levantada dentro de `aplicarNoEnv()` depois de `definirNoEnv()` produziria
     * um aviso dizendo "a APP_URL continua como estava" sobre um arquivo que já
     * tinha mudado.
     */
    private bool $envEscrito = false;

    /**
     * @param  string  $base  diretório do projeto que está sendo instalado
     * @param  (Closure(string): int)|null  $executor  recebe o comando e devolve o código de saída
     * @param  string|null  $hosts  caminho do arquivo de hosts; nulo usa o do sistema
     * @param  (Closure(string): ?string)|null  $resolvedor  o endereço que já responde pelo domínio, ou nulo
     */
    public function __construct(
        private readonly string $base,
        ?Closure $executor = null,
        ?string $hosts = null,
        ?Closure $resolvedor = null,
        private readonly string $so = PHP_OS_FAMILY,
    ) {
        $this->executor   = $executor ?? self::executorDoSistema();
        $this->resolvedor = $resolvedor ?? static function (string $dominio): ?string {
            $endereco = gethostbyname($dominio);

            return $endereco === $dominio ? null : $endereco;
        };
        $this->hosts = $hosts ?? $this->caminhoPadraoDoHosts();
    }

    /**
     * As perguntas e o que decorre delas. Devolve o aviso, ou `null` quando não há o que avisar.
     *
     * O `$interativo` chega de `KitInstall::temTerminal()` e é o gate da etapa
     * inteira: sem alguém do outro lado, nada é perguntado e nada é decidido.
     * Ele é PARÂMETRO, e não uma consulta feita aqui dentro, porque
     * `runningUnitTests()` deixa `temTerminal()` verdadeiro dentro da suíte — um
     * gate lido daqui nunca teria o ramo negativo exercitado.
     *
     * O `try/catch` cobre as perguntas de propósito — ver o docblock da classe.
     */
    public function oferecer(bool $interativo): ?string
    {
        if (! $interativo) {
            return null;
        }

        $dominio = null;

        try {
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

            if (! $this->confirmarDominioPublico($dominio)) {
                return null;
            }

            note(
                "A APP_URL passa a ser http://{$dominio}. Dois efeitos conhecidos:\n"
                .'- login social: a URI de callback registrada no provedor muda junto;'."\n"
                .'- npm run dev: o Vite serve de localhost:5173 e restringe CORS — com npm run build não aparece.'
            );

            return $this->processar($dominio);
        } catch (Throwable $excecao) {
            return $this->registrarFalha('oferecer', $dominio ?? $this->dominioSugerido(), $excecao);
        }
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

            [$resolveAqui, $enderecoDeTerceiro] = $this->sondar($dominio);

            if ($resolveAqui) {
                $this->aplicarNoEnv($dominio);

                return null;
            }

            if ($enderecoDeTerceiro !== null) {
                note($this->avisoDeEnderecoDeTerceiro($dominio, $enderecoDeTerceiro));
            }

            if ($this->so !== 'Windows') {
                /*
                 * O ramo Unix devolve AVISO, não `null` — e a diferença é o banner final.
                 *
                 * O kit não roda `sudo` por conta própria, então aqui a linha do `hosts` fica
                 * pendente de um comando manual. Esse é, do ponto de vista de quem instala, o
                 * MESMO estado que o ramo Windows classifica como falha: a `APP_URL` já aponta
                 * para o domínio e o domínio ainda não resolve.
                 *
                 * A versão anterior emitia um `note()` e devolvia `null`. O `note()` sai na hora
                 * e, quando a instalação termina, já rolou para fora da tela — acima do banner, do
                 * resumo, da oferta de testes e da oferta de estrela. O `foreach ($this->avisos)`
                 * de `KitInstall::banner()` não imprimia nada, e a instalação fechava anunciando
                 * `http://{dominio}/app`, `/admin` e `/infra`: três endereços que não resolvem.
                 *
                 * `oferecerHostLocal()` foi escrito para consumir este retorno; no Unix ele
                 * recebia sempre `null`. Achado do `fw-revisor-diff` (RD-03).
                 *
                 * `aplicarNoEnv()` ANTES: é ele que liga `$envEscrito`, e é isso que faz o aviso
                 * dizer "a APP_URL já tinha sido ajustada" em vez de "continua como estava".
                 */
                $this->aplicarNoEnv($dominio);

                return $this->avisoDeFalha($dominio);
            }

            if ($this->cadastrar($dominio)) {
                $this->aplicarNoEnv($dominio);

                return null;
            }

            return $this->avisoDeFalha($dominio);
        } catch (Throwable $excecao) {
            return $this->registrarFalha('processar', $dominio, $excecao);
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
     * O domínio já resolve **nesta máquina**? — e a pergunta é sobre a RESOLUÇÃO, não sobre o texto do arquivo.
     *
     * Quem tem Laravel Herd ou Valet resolve `*.test` por dnsmasq, **sem** linha
     * nenhuma no `hosts`. Olhar só o arquivo faria a etapa pedir elevação à toa
     * e sujar o arquivo de sistema de quem já tinha a máquina configurada.
     *
     * **Só LOOPBACK conta** (RQ-12, Adendo 1). Um DNS corporativo com curinga, ou
     * com NXDOMAIN sequestrado, responde por domínio inédito — e tratar isso como
     * "já resolve" faria a etapa pular a escrita, gravar a `APP_URL` e devolver
     * nenhum aviso, deixando a impressão final apontando para um endereço de
     * terceiro. "Já resolve" tem de significar "resolve **para aqui**".
     *
     * No arquivo, só uma linha ATIVA do domínio EXATO conta: `# 127.0.0.1 x.test`
     * é um comentário e não resolve nada, `app.x.test` e `x.test.br` são outros
     * domínios, e caixa alta é o mesmo domínio (DNS não diferencia).
     */
    public function jaResolve(string $dominio): bool
    {
        return $this->sondar($dominio)[0];
    }

    /**
     * O comando de elevação — o MESMO que a documentação do repositório ensina.
     *
     * Divergir daqui quebra a promessa de RQ-06 ("rode conforme a documentação")
     * em silêncio: a página continuaria descrevendo um procedimento e o comando
     * rodando outro. Há caso de teste comparando os dois textos.
     *
     * O caminho sai de `caminhoDoHosts()`, e não de `$env:windir` literal, porque
     * é ele que o oráculo relê: a página usa a variável porque quem cola o comando
     * está dentro de um PowerShell, e aqui a variável já foi expandida.
     *
     * O domínio entra já validado por `erroDoDominio()` — ele vira argumento de
     * um processo ELEVADO, e aspa ou ponto-e-vírgula ali emendariam um segundo
     * comando rodando como Administrador.
     */
    public function comandoDeElevacao(string $dominio): string
    {
        return 'Start-Process pwsh -Verb RunAs -Wait -ArgumentList \'-NoProfile\',\'-Command\', '
            .'\'Add-Content "'.$this->caminhoDoHosts().'" "`n127.0.0.1`t'
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
     * O domínio termina em um dos TLD que a RFC 6761 reserva para uso local?
     *
     * É SUFIXO, e não "contém": `loja.test.br` é um domínio público que carrega
     * `.test` no meio, e tratá-lo como reservado o cadastraria calado.
     */
    public function ehDominioReservado(string $dominio): bool
    {
        $sufixos = array_map(static fn (string $tld): string => '.'.$tld, self::TLD_RESERVADOS);

        return Str::endsWith(Str::lower($dominio), $sufixos);
    }

    /**
     * O que recusa uma entrada — e o porquê de cada recusa.
     *
     * O texto digitado num terminal comum vira DUAS coisas perigosas: argumento
     * de um processo elevado e linha de um arquivo de sistema. Esquema, barra,
     * espaço, aspas e quebra de linha são recusados por isso, não por estética.
     *
     * O comprimento é recusado pelo limite do próprio DNS (RFC 1035 §2.3.4):
     * rótulo de 64 octetos e nome de 254 não resolvem em lugar nenhum, e deixar
     * passar só troca uma mensagem clara agora por uma linha inútil no arquivo de
     * sistema e uma `APP_URL` que nunca abre.
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

        if (strlen($dominio) > self::MAX_NOME) {
            return "{$dominio}: o nome inteiro passa de ".self::MAX_NOME.' octetos (RFC 1035)';
        }

        foreach (explode('.', $dominio) as $rotulo) {
            if (strlen($rotulo) > self::MAX_ROTULO) {
                return "{$dominio}: cada parte do nome cabe em ".self::MAX_ROTULO.' octetos (RFC 1035)';
            }
        }

        return null;
    }

    /**
     * O sim a mais que um domínio público exige (RQ-10/RQ-11, Adendo 1).
     *
     * Um domínio que não termina em TLD reservado é uma escolha **legítima e
     * perigosa**: `127.0.0.1 fiotec.fiocruz.br` no `hosts` derruba o acesso ao
     * site real nesta máquina, e remover a linha está fora do escopo desta
     * feature — o único ponto de controle possível é antes de escrever. Por isso
     * a barreira mora aqui, e não em `erroDoDominio()`: recusar tornaria RQ-10
     * inalcançável, porque a pessoa não teria como dizer sim.
     */
    private function confirmarDominioPublico(string $dominio): bool
    {
        if ($this->ehDominioReservado($dominio)) {
            return true;
        }

        return confirm(
            label: "{$dominio} é um domínio público. Apontar mesmo assim para 127.0.0.1?",
            default: false,
            hint: 'Apontá-lo para 127.0.0.1 nesta máquina vai impedir o acesso ao site real, '
                .'e a linha fica no hosts até ser removida à mão.',
        );
    }

    /**
     * Sonda uma vez só, e devolve as duas respostas que a etapa precisa.
     *
     * @return array{0: bool, 1: string|null} resolve nesta máquina? · endereço de terceiro que responde
     */
    private function sondar(string $dominio): array
    {
        $endereco = ($this->resolvedor)($dominio);

        if ($this->temLinhaAtiva($dominio) || self::ehLoopback($endereco)) {
            return [true, null];
        }

        return [false, $endereco];
    }

    /** `127.0.0.0/8` e `::1` — o que significa "esta máquina", e mais nada. */
    private static function ehLoopback(?string $endereco): bool
    {
        if ($endereco === null) {
            return false;
        }

        $limpo = trim($endereco, '[]');

        return $limpo === '::1'
            || preg_match('/\A127\.(\d{1,3})\.(\d{1,3})\.(\d{1,3})\z/', $limpo) === 1;
    }

    /** O domínio já respondia — mas por um endereço que não é desta máquina. */
    private function avisoDeEnderecoDeTerceiro(string $dominio, string $endereco): string
    {
        return "Atenção: {$dominio} já responde como {$endereco}, que não é um endereço desta máquina "
            .'(DNS da rede, ou curinga do provedor). A linha nova no hosts passa a valer sobre ele aqui — '
            .'e só aqui.';
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

        $this->envEscrito = true;

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

    /**
     * O `hosts` do sistema — e no Windows ele sai de `%windir%`, não de uma constante.
     *
     * A pasta do Windows é `C:\Windows` na esmagadora maioria das máquinas e não é
     * em algumas. Como este caminho é ao mesmo tempo o que o comando elevado
     * escreve e o que o oráculo relê, fixá-lo em texto faria os dois apontarem
     * para arquivos diferentes justamente onde o erro é invisível.
     */
    private function caminhoPadraoDoHosts(): string
    {
        return match ($this->so) {
            'Windows' => ((string) (getenv('windir') ?: 'C:\Windows')).'\System32\drivers\etc\hosts',
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

            $campos = preg_split('/\s+/', $util) ?: [];

            /*
             * O ENDEREÇO da linha decide, e é por isso que ele não é descartado aqui.
             *
             * A primeira versão fazia `array_shift($campos)` e jogava o endereço fora, perguntando
             * só se algum dos nomes casava. Com isso, `10.20.30.40 meuapp.test` — quem aponta o
             * domínio para uma VM, WSL ou máquina de staging — contava como "já resolve aqui": a
             * etapa pulava a escrita, gravava a `APP_URL` e devolvia **nenhum aviso**, e a
             * instalação fechava imprimindo três endereços de OUTRA máquina.
             *
             * É exatamente o desfecho que o docblock de `sondar()` declara proibido — *"'Já
             * resolve' tem de significar 'resolve para aqui'"* —, e a guarda existia só no ramo do
             * DNS. O `||` de `sondar()` curto-circuita neste método, então `ehLoopback()` nunca era
             * consultado quando havia linha no arquivo. Achado do `fw-revisor-diff` (RD-01).
             */
            $endereco = array_shift($campos);

            if (! self::ehLoopback($endereco)) {
                continue;
            }

            foreach ($campos as $nome) {
                if (strcasecmp($nome, $dominio) === 0) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Loga a falha e devolve o aviso — o único caminho de saída dos dois `catch`.
     *
     * O log tem o `try` dele porque **registrar a falha não pode virar a falha**: se o que
     * estourou lá atrás foi o próprio canal de log, escrever aqui estouraria de novo e a exceção
     * escaparia do `catch` que existe para que nada escape.
     */
    private function registrarFalha(string $metodo, string $dominio, Throwable $excecao): string
    {
        try {
            Log::channel('configuracoes')->warning(
                "[HostLocal@{$metodo}] Etapa do host local falhou | dominio: {$dominio}",
                ['dominio' => $dominio, 'erro' => $excecao->getMessage(), 'so' => $this->so],
            );
        } catch (Throwable) {
            // Sem canal não há registro, e o aviso devolvido continua sendo a saída que importa.
        }

        return $this->avisoDeFalha($dominio);
    }

    /**
     * O destino alcançável de quem chegou ao estado de erro: o comando pronto para colar.
     *
     * Duas coisas que o aviso NÃO pode fazer, e ambas já custaram um achado de
     * revisão: dar instrução de Windows para quem está no Linux (o `match` abaixo,
     * ADR-03 valendo também no ramo de erro), e **afirmar** que a `APP_URL`
     * continua como estava quando a exceção aconteceu depois da escrita.
     */
    private function avisoDeFalha(string $dominio): string
    {
        $url = $this->envEscrito
            ? "a APP_URL já tinha sido ajustada para http://{$dominio}"
            : 'a APP_URL continua como estava';

        return "Não consegui cadastrar {$dominio} no arquivo hosts — {$url}. "
            .match ($this->so) {
                'Windows' => 'Num PowerShell como administrador: '.$this->comandoDeElevacao($dominio),
                default   => $this->instrucaoManual($dominio),
            };
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
