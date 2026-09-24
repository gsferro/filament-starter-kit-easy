<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * A senha do administrador inicial — quem decide se ela é utilizável, e quem a gera.
 *
 * ## Por que esta classe existe
 *
 * O `.env.example` trazia `KIT_ADMIN_PASSWORD=password`, e o `config/kit.php` repetia o literal
 * como fallback. Toda instalação que não trocasse a senha nascia com a **mesma credencial
 * conhecida** — e o valor está publicado no repositório, então "conhecida" quer dizer conhecida
 * por qualquer pessoa.
 *
 * O kit instala em um comando, e quebrar isso seria pior que o problema: exigir que alguém defina
 * a senha antes do primeiro `composer create-project` transforma a instalação em duas etapas. A
 * saída é a terceira: **o instalador gera uma senha aleatória, grava no `.env` e a imprime uma
 * vez**. Continua um comando, e deixa de ser a mesma senha em toda instalação do mundo.
 *
 * ## Uma pergunta, uma dona
 *
 * `.ai/rules/config.md` proíbe duas classes respondendo a mesma pergunta de config por caminhos
 * diferentes — foi assim que `kit.registro.aberto` e `kit.registro.habilitado` passaram a
 * discordar em silêncio. Aqui a pergunta *"a senha do administrador é utilizável?"* tem uma dona
 * só, e o `config/kit.php`, o `kit:install` e o seeder a consultam.
 */
final class SenhaDoAdministrador
{
    /**
     * O valor que o kit distribuía, e que nenhuma instalação pode manter.
     *
     * Fica como constante — e não como literal espalhado — porque três lugares precisam
     * reconhecê-lo: o fallback do config, a guarda do seeder e o caso de teste.
     */
    public const PADRAO_PUBLICADO = 'password';

    /**
     * A chave do `.env`. Uma só grafia, para o grep encontrar todos os consumidores.
     */
    public const CHAVE = 'KIT_ADMIN_PASSWORD';

    /**
     * A senha definida no ambiente é utilizável?
     *
     * Vazia, ausente e o padrão publicado são todas **não** — `filled()` trata `'0'` como
     * presente, que é o comportamento certo aqui: `0` é senha ruim, mas é escolha de alguém.
     */
    public static function definida(): bool
    {
        return self::ehUtilizavel(self::doAmbiente());
    }

    /**
     * A senha como o ambiente a define, lida por `config()` e nunca por `env()`.
     *
     * PHPStan pegou isto, e era defeito de verdade: `env()` fora de `config/` devolve `null`
     * quando a config está cacheada (`larastan.noEnvCallsOutsideOfConfig`). Com `config:cache`
     * ligado — o que uma instalação de produção faz — `garantirNoEnv()` concluiria "não há senha"
     * e **geraria outra**, sobrescrevendo a que a pessoa havia definido.
     *
     * `config('kit.admin.password')` cai no padrão publicado quando a chave falta, e o predicado
     * trata esse valor como não-utilizável — que é exatamente o desfecho certo.
     */
    private static function doAmbiente(): ?string
    {
        $bruta = config('kit.admin.password');

        return is_string($bruta) ? $bruta : null;
    }

    /**
     * O predicado PURO, e é ele que o caso de teste exercita.
     *
     * Separado de `definida()` por uma razão medida: `env()` lê do repositório do Dotenv que o
     * framework já resolveu no boot, e `putenv()` depois disso **não** o alcança. Um caso que
     * tentasse variar o ambiente para testar a regra ficaria verde sobre o valor do `phpunit.xml`,
     * não sobre o que passou — três asserções minhas falharam exatamente assim.
     *
     * Com a regra num predicado puro, o caso testa a REGRA e `definida()` fica sendo só a ligação
     * com o ambiente.
     */
    public static function ehUtilizavel(?string $bruta): bool
    {
        return $bruta !== null
            && filled($bruta)
            && trim($bruta) !== self::PADRAO_PUBLICADO;
    }

    /**
     * Uma senha nova, forte e **sem símbolo**.
     *
     * Sem símbolo **não** porque o `.env` quebraria: `SubstituicaoEmArquivo::escaparValorDeEnv()`
     * já trata `\`, `"`, `$` e quebra de linha, e a linha sai citada. Escrevi essa justificativa
     * primeiro e ela era falsa — conferida na fonte e corrigida.
     *
     * O motivo real é de custo/benefício: 24 caracteres alfanuméricos dão entropia de sobra, e
     * tirar o símbolo elimina uma ida e volta de escape entre o gerador, o arquivo, o Dotenv e o
     * terminal de onde a pessoa vai copiar. Um elo a menos onde a senha impressa pode diferir da
     * senha gravada.
     */
    public static function gerar(): string
    {
        return Str::password(24, symbols: false);
    }

    /**
     * Garante uma senha utilizável no `.env` e devolve a que foi gerada, ou `null` se já havia uma.
     *
     * Devolver `null` quando já existe é o que permite ao chamador imprimir a senha **apenas
     * quando ele próprio a criou** — imprimir a que o usuário definiu seria vazá-la no terminal e
     * no log de CI sem que ninguém tivesse pedido.
     */
    public static function garantirNoEnv(string $caminhoDoEnv, ?string $atual = null): ?string
    {
        /*
         * `$atual` existe para o caso de teste poder dizer "ja ha senha" sem mexer no ambiente.
         * Em producao ninguem o passa, e a fonte e `config('kit.admin.password')` — nunca `env()`,
         * que devolve null com a config cacheada. Ver `doAmbiente()`.
         */
        $bruta = $atual ?? self::doAmbiente();

        if (self::ehUtilizavel($bruta)) {
            return null;
        }

        $nova = self::gerar();

        SubstituicaoEmArquivo::definirNoEnv($caminhoDoEnv, self::CHAVE, $nova);

        /*
         * O `.env` já foi lido quando o framework subiu: gravar o arquivo não muda `config()` nem
         * `env()` nesta execução. Realinhar em memória é o que faz o seeder, que roda logo abaixo
         * no `kit:install`, semear a senha nova em vez da que ele leu no boot.
         */
        config()->set('kit.admin.password', $nova);

        return $nova;
    }
}
