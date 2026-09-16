<?php

declare(strict_types=1);

namespace App\Support;

use SplFileInfo;
use Symfony\Component\Finder\Finder;

/**
 * O guarda do padrão de DTO do kit — a rule `.ai/rules/dto.md` em forma executável.
 *
 * ## Por que um guarda, e não só uma rule escrita
 *
 * `RQ-04` da wiki `laravel-data-como-padrao-de-dto` diz que **toda resposta de API tem Data**, e o
 * kit não expõe API nenhuma hoje. Uma regra escrita para código que ainda não existe morre no dia
 * em que alguém cria o primeiro endpoint sem ler `.ai/rules/`. Este guarda acorda nesse dia.
 *
 * ## As raízes são DADO, de propósito
 *
 * `RAIZES_PADRAO` é pública e verificável: a revisão adversarial mostrou que um guarda que só
 * enxerga a raiz que o teste entrega passa em qualquer suíte e não vê nada em produção. O teste
 * afirma **o valor das raízes** e que o kit publicado passa por elas sem violação — e as fixtures
 * defeituosas rodam numa raiz temporária, sem escrever nada em `app/` ou `routes/`.
 *
 * ## A mensagem é discriminante
 *
 * Cada violação cita **o motivo daquele defeito e não os outros**. Uma mensagem única listando
 * todos os motivos satisfaz qualquer asserção de "cita X" e não distingue nada — foi um dos
 * achados da revisão adversarial.
 *
 * A análise é por TOKEN, não por `str_contains`: `extends \Spatie\LaravelData\Data` escrito por
 * extenso e `readonly` declarado em cada propriedade promovida são formas válidas, e um guarda
 * ingênuo reprovaria as duas.
 */
final class GuardaDoPadraoDeDto
{
    /** O `Data` do pacote, que todo DTO do kit estende. */
    public const CLASSE_BASE = 'Spatie\\LaravelData\\Data';

    /** Onde o guarda procura, quando ninguém lhe entrega uma raiz. */
    public const RAIZES_PADRAO = ['app', 'routes/api.php', 'app/Http/Resources'];

    /** O diretório (segmento de caminho) em que os DTO moram. */
    public const DIRETORIO_DE_DTO = 'Data';

    /** Nomes de propriedade que nunca viajam num Data (ADR-04). */
    public const NOMES_DE_CREDENCIAL = ['senha', 'password', 'token', 'secret', 'api_key', 'apiKey'];

    /**
     * As violações encontradas. Lista vazia = o padrão está de pé.
     *
     * @param  string|null  $raiz  diretório a varrer; `null` usa `RAIZES_PADRAO` a partir de `base_path()`
     * @return list<string>
     */
    public static function violacoes(?string $raiz = null): array
    {
        $violacoes = [];

        foreach (self::arquivos($raiz) as $arquivo) {
            $violacoes = [...$violacoes, ...self::analisar($arquivo)];
        }

        $violacoes = [...$violacoes, ...self::superficiesDeApi($raiz)];

        sort($violacoes);

        return $violacoes;
    }

    /**
     * @return list<SplFileInfo>
     */
    private static function arquivos(?string $raiz): array
    {
        $diretorios = $raiz !== null
            ? [$raiz]
            : array_map(static fn (string $r): string => base_path($r), self::RAIZES_PADRAO);

        $diretorios = array_values(array_unique(array_filter($diretorios, 'is_dir')));

        if ($diretorios === []) {
            return [];
        }

        return iterator_to_array(
            Finder::create()->files()->in($diretorios)->name('*.php'),
            false,
        );
    }

    /**
     * @return list<string>
     */
    private static function analisar(SplFileInfo $arquivo): array
    {
        $fonte  = (string) file_get_contents($arquivo->getPathname());
        $classe = self::classeDo($fonte);

        if ($classe === null) {
            return self::instanciacoesForaDaFabrica($fonte, $arquivo, false);
        }

        $caminho          = str_replace('\\', '/', $arquivo->getPathname());
        $emDiretorioDeDto = str_contains($caminho, '/'.self::DIRETORIO_DE_DTO.'/');
        $violacoes        = [];

        if ($emDiretorioDeDto) {
            if (! $classe['estende_data']) {
                $violacoes[] = "{$classe['nome']}: precisa estender o Data do pacote (".self::CLASSE_BASE.').';
            } elseif ($classe['abstrata']) {
                $violacoes[] = "{$classe['nome']}: não pode ser abstract — Data é instanciável.";
            } elseif (! $classe['final']) {
                $violacoes[] = "{$classe['nome']}: precisa ser final.";
            } elseif ($classe['propriedades_mutaveis'] !== []) {
                $propriedade = $classe['propriedades_mutaveis'][0];
                $violacoes[] = "{$classe['nome']}: a propriedade \${$propriedade} precisa ser readonly.";
            }

            foreach ($classe['propriedades'] as $propriedade) {
                if (in_array($propriedade, self::NOMES_DE_CREDENCIAL, true)) {
                    $violacoes[] = "{$classe['nome']}: a propriedade \${$propriedade} é credencial e não pode viajar num Data.";
                }
            }
        }

        if (! $emDiretorioDeDto && str_ends_with($classe['nome'], 'Data')) {
            $violacoes[] = "{$classe['nome']}: o sufixo Data é reservado para DTO em ".self::DIRETORIO_DE_DTO.'/.';
        }

        if (! $emDiretorioDeDto && $classe['propriedade_publica_de_data'] !== null) {
            $propriedade = $classe['propriedade_publica_de_data'];
            $violacoes[] = "{$classe['nome']}: a propriedade pública \${$propriedade} é um Data — o pacote a reconstrói a partir do payload do navegador (LivewireDataSynth).";
        }

        return [...$violacoes, ...self::instanciacoesForaDaFabrica($fonte, $arquivo, $emDiretorioDeDto)];
    }

    /**
     * `new XData(...)` fora de `Data/` — a criação passa pela fábrica nomeada, que é a única que
     * roda o pipeline do pacote (e, com ele, os casts). ADR-03.
     *
     * @return list<string>
     */
    private static function instanciacoesForaDaFabrica(string $fonte, SplFileInfo $arquivo, bool $emDiretorioDeDto): array
    {
        if ($emDiretorioDeDto || ! self::instanciaDataForaDeComentario($fonte)) {
            return [];
        }

        return [$arquivo->getFilename().': instancia um Data com new fora da fábrica nomeada — só o from() do pacote roda os casts.'];
    }

    /**
     * `new XData(` no CÓDIGO, não em comentário.
     *
     * A varredura por texto cru encontrava a própria documentação — este arquivo e o cast citam
     * `new MeuData(...)` em docblock para explicar por que ele é proibido. Token resolve.
     */
    private static function instanciaDataForaDeComentario(string $fonte): bool
    {
        $tokens = token_get_all($fonte);

        foreach ($tokens as $i => $token) {
            if (! is_array($token) || $token[0] !== T_NEW) {
                continue;
            }

            $nome = self::nomeQualificado($tokens, $i);

            if ($nome !== null && str_ends_with($nome, 'Data')) {
                return true;
            }
        }

        return false;
    }

    /**
     * As três superfícies que `RQ-04` nomeia. Sem Data no payload, cada uma é violação.
     *
     * @return list<string>
     */
    private static function superficiesDeApi(?string $raiz): array
    {
        $base      = $raiz ?? base_path();
        $violacoes = [];

        $rotas = $base.'/routes/api.php';

        if (is_file($rotas) && ! self::mencionaData((string) file_get_contents($rotas))) {
            $violacoes[] = 'routes/api.php: rota de API precisa devolver um Data.';
        }

        $resources = $base.'/app/Http/Resources';
        $resources = is_dir($resources) ? $resources : $base.'/Http/Resources';

        if (is_dir($resources)) {
            foreach (Finder::create()->files()->in($resources)->name('*.php') as $arquivo) {
                if (! self::mencionaData((string) file_get_contents($arquivo->getPathname()))) {
                    $violacoes[] = $arquivo->getFilename().': Resource de API precisa devolver um Data.';
                }
            }
        }

        foreach (self::arquivos($raiz) as $arquivo) {
            $fonte = (string) file_get_contents($arquivo->getPathname());

            if (! str_contains($arquivo->getFilename(), 'Controller')) {
                continue;
            }

            if (preg_match('/response\(\)\s*->\s*json\s*\(/', $fonte) === 1 && ! self::mencionaData($fonte)) {
                $violacoes[] = $arquivo->getFilename().': resposta JSON de controller precisa de um Data.';
            }
        }

        return $violacoes;
    }

    private static function mencionaData(string $fonte): bool
    {
        return preg_match('/[A-Za-z0-9_]+Data\b/', $fonte) === 1;
    }

    /**
     * A leitura por token da primeira classe do arquivo.
     *
     * @return array{nome: string, final: bool, abstrata: bool, estende_data: bool, propriedades: list<string>, propriedades_mutaveis: list<string>, propriedade_publica_de_data: string|null}|null
     */
    private static function classeDo(string $fonte): ?array
    {
        $tokens = token_get_all($fonte);
        $total  = count($tokens);

        $nome          = null;
        $final         = false;
        $abstrata      = false;
        $estende       = null;
        $propriedades  = [];
        $mutaveis      = [];
        $publicaDeData = null;

        for ($i = 0; $i < $total; $i++) {
            $token = $tokens[$i];

            if (! is_array($token)) {
                continue;
            }

            if ($nome === null && $token[0] === T_CLASS) {
                for ($j = $i - 1; $j >= 0 && $i - $j < 6; $j--) {
                    if (is_array($tokens[$j]) && $tokens[$j][0] === T_FINAL) {
                        $final = true;
                    }

                    if (is_array($tokens[$j]) && $tokens[$j][0] === T_ABSTRACT) {
                        $abstrata = true;
                    }
                }

                $nome = self::proximoTexto($tokens, $i);

                continue;
            }

            if ($token[0] === T_EXTENDS && $estende === null) {
                $estende = self::nomeQualificado($tokens, $i);

                continue;
            }

            if (in_array($token[0], [T_PUBLIC, T_PROTECTED, T_PRIVATE, T_READONLY], true)) {
                [$propriedade, $tipo, $temReadonly, $ehPublica] = self::propriedade($tokens, $i);

                if ($propriedade === null) {
                    continue;
                }

                $propriedades[] = $propriedade;

                if (! $temReadonly) {
                    $mutaveis[] = $propriedade;
                }

                if ($ehPublica && $publicaDeData === null && $tipo !== null && str_ends_with($tipo, 'Data')) {
                    $publicaDeData = $propriedade;
                }
            }
        }

        if ($nome === null) {
            return null;
        }

        return [
            'nome'                        => $nome,
            'final'                       => $final,
            'abstrata'                    => $abstrata,
            'estende_data'                => $estende !== null && str_ends_with(ltrim($estende, '\\'), 'Data'),
            'propriedades'                => array_values(array_unique($propriedades)),
            'propriedades_mutaveis'       => array_values(array_unique($mutaveis)),
            'propriedade_publica_de_data' => $publicaDeData,
        ];
    }

    /**
     * @param  array<int, array{0: int, 1: string, 2: int}|string>  $tokens
     * @return array{0: string|null, 1: string|null, 2: bool, 3: bool}
     */
    private static function propriedade(array $tokens, int $inicio): array
    {
        $readonly = is_array($tokens[$inicio]) && $tokens[$inicio][0] === T_READONLY;
        $publica  = is_array($tokens[$inicio]) && $tokens[$inicio][0] === T_PUBLIC;
        $tipo     = null;

        for ($i = $inicio + 1, $limite = min($inicio + 12, count($tokens)); $i < $limite; $i++) {
            $token = $tokens[$i];

            if ($token === ';' || $token === '{' || $token === '(') {
                break;
            }

            if (! is_array($token)) {
                continue;
            }

            if ($token[0] === T_READONLY) {
                $readonly = true;

                continue;
            }

            if ($token[0] === T_PUBLIC) {
                $publica = true;

                continue;
            }

            if (in_array($token[0], [T_STRING, T_NAME_QUALIFIED, T_NAME_FULLY_QUALIFIED], true)) {
                $tipo ??= $token[1];

                continue;
            }

            if ($token[0] === T_VARIABLE) {
                return [ltrim($token[1], '$'), $tipo, $readonly, $publica];
            }

            if ($token[0] === T_FUNCTION) {
                break;
            }
        }

        return [null, null, $readonly, $publica];
    }

    /**
     * @param  array<int, array{0: int, 1: string, 2: int}|string>  $tokens
     */
    private static function proximoTexto(array $tokens, int $inicio): ?string
    {
        for ($i = $inicio + 1, $limite = min($inicio + 5, count($tokens)); $i < $limite; $i++) {
            if (is_array($tokens[$i]) && $tokens[$i][0] === T_STRING) {
                return $tokens[$i][1];
            }
        }

        return null;
    }

    /**
     * @param  array<int, array{0: int, 1: string, 2: int}|string>  $tokens
     */
    private static function nomeQualificado(array $tokens, int $inicio): ?string
    {
        for ($i = $inicio + 1, $limite = min($inicio + 5, count($tokens)); $i < $limite; $i++) {
            if (is_array($tokens[$i]) && in_array($tokens[$i][0], [T_STRING, T_NAME_QUALIFIED, T_NAME_FULLY_QUALIFIED], true)) {
                return $tokens[$i][1];
            }
        }

        return null;
    }
}
