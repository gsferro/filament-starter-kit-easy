<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * Lê o relatório Clover, aplica o piso de cobertura e mantém o badge honesto.
 *
 * ## Por que um comando Artisan, e não um script em `.github/`
 *
 * A primeira redação morava em `.github/cobertura.php`, e isso era um defeito de **entrega**: o
 * `.gitattributes` marca `/.github` com `export-ignore`, então o diretório **não existe** em
 * projeto nascido de `composer create-project` — enquanto o `composer.json`, que ganhou o script
 * `test:coverage` apontando para lá, viaja inteiro. Quem instalasse o kit rodaria os ~25 minutos
 * de suíte com cobertura para só então bater em `Could not open input file`.
 *
 * O mesmo `.gitattributes` já escreve a regra, a propósito dos scripts `lint` e `types:check`: o
 * que o `composer.json` distribuído invoca precisa viajar junto.
 *
 * Como comando, ele viaja pelas **duas** rotas — `app/` não é `export-ignore`, e
 * `app/Console/Commands` está em `KitUpdate::CAMINHOS_DO_KIT` — e passa a ser útil para quem
 * instala: o piso funciona em qualquer projeto, com o `phpunit.xml` que ele tiver.
 *
 * ## O badge é a única parte que é só do kit
 *
 * `.github/badges/cobertura.json` alimenta o *endpoint* do shields.io no README **do kit**. Fora
 * da árvore do kit esse diretório não existe, e o comando simplesmente não escreve nem confere
 * badge nenhum — o `--min` continua valendo. É a degradação certa: o projeto instalado ganha o
 * gate, e não herda um badge que não é dele.
 */
class KitCobertura extends Command
{
    protected $signature = 'kit:cobertura
        {clover=cobertura.xml : Caminho do relatório Clover gerado pelo --coverage-clover}
        {--min= : Piso de cobertura em percentual; abaixo dele o comando falha}
        {--write : Grava o badge em vez de conferi-lo}';

    protected $description = 'Lê o Clover, aplica o piso de cobertura e escreve ou confere o badge';

    /**
     * O badge guarda o percentual INTEIRO TRUNCADO, e isso é deliberado.
     *
     * O número real move na terceira casa a cada rodada — uma linha a mais em `app/` já o muda.
     * Se o JSON guardasse a casa decimal, o job reprovaria por ruído e a guarda viraria alarme falso,
     * que é como guarda morre. Guardando o inteiro, ele só muda quando a cobertura cruza um ponto
     * percentual: ~98 statements nesta árvore.
     *
     * Quem gatilha o gate de verdade é o `--min`, não o badge.
     */
    public function handle(): int
    {
        $clover = (string) $this->argument('clover');

        if (! is_file($clover)) {
            $this->components->error("Relatório Clover não encontrado: {$clover}");
            $this->components->bulletList(['Gere-o com `composer test:coverage`.']);

            return self::INVALID;
        }

        $piso = $this->pisoPedido();

        if ($piso === false) {
            return self::INVALID;
        }

        $metricas = $this->metricasDo($clover);

        if ($metricas === null) {
            return self::INVALID;
        }

        [$cobertas, $total] = $metricas;

        $percentual = 100 * $cobertas / $total;
        $inteiro    = (int) floor($percentual);

        $this->components->twoColumnDetail(
            'Cobertura de <fg=gray>app/</>',
            sprintf(
                '<fg=green;options=bold>%.2f%%</> <fg=gray>(%d / %d statements)</>',
                $percentual,
                $cobertas,
                $total,
            ),
        );

        $falhas = [];

        if ($piso !== null && $percentual < $piso) {
            $falhas[] = sprintf(
                /*
                 * Mensagem AUTOCONTIDA: ela não pode citar `wikis/specs/`, que é `export-ignore`
                 * no `.gitattributes` e portanto não existe em projeto nascido de
                 * `create-project`. Mandar o operador a um caminho inexistente é a mesma classe
                 * de defeito que tirou o script de dentro de `.github/` — referência que só vale
                 * na árvore do kit, embarcada no que viaja.
                 */
                'Cobertura de %.2f%% abaixo do piso de %s%% — suba a cobertura ou ajuste o `--min`.',
                $percentual,
                $this->semZerosAtoa($piso),
            );
        }

        $falhas = [...$falhas, ...$this->cuidarDoBadge($inteiro)];

        if ($falhas !== []) {
            foreach ($falhas as $falha) {
                $this->components->error($falha);
            }

            return self::FAILURE;
        }

        $this->components->info($piso === null
            ? 'Badge confere.'
            : 'Badge confere e o piso foi respeitado.');

        return self::SUCCESS;
    }

    /**
     * O piso pedido, `null` quando não foi pedido, `false` quando veio ilegível.
     *
     * ## Por que `--min` ilegível PRECISA falhar
     *
     * A redação anterior fazia `(float) $bruto`, e `(float) 'abc'` é `0.0`. Um `--min=abc` — ou um
     * `--mim=78` com o dedo trocado — desligava o gate **em silêncio** e o comando ainda imprimia
     * *"o piso foi respeitado"*. Fail-open com mensagem de sucesso é a pior combinação possível
     * numa guarda, e o `--min` está escrito à mão em dois lugares (`composer.json` e `ci.yml`),
     * que é exatamente onde erro de digitação mora.
     *
     * O Artisan já recusa opção desconhecida por conta própria, o que fecha a outra metade: um
     * `--mim=78` não vira argumento posicional em silêncio, vira erro.
     */
    private function pisoPedido(): float|false|null
    {
        $bruto = $this->option('min');

        if ($bruto === null) {
            return null;
        }

        if (! is_numeric($bruto)) {
            $this->components->error('`--min` precisa ser um número, e veio "'.((string) $bruto).'".');

            return false;
        }

        $piso = (float) $bruto;

        /*
         * O ZERO e recusado, e a direcao vem de falha fechado.
         *
         * `--min=0` passava: a faixa era `0 <= piso <= 100`, e o comando ainda imprimia "o piso foi
         * respeitado". Um piso que nao reprova ninguem nao e piso -- e a diferenca para o
         * `--min=abc` que a revisao de diff pegou (RD-02) e so a porta por onde entra.
         *
         * Achado pelo quality gate, ciclo 1.
         */
        if ($piso <= 0 || $piso > 100) {
            $this->components->error("`--min` precisa estar entre 1 e 100, e veio {$piso}.");

            return false;
        }

        return $piso;
    }

    /**
     * O piso como ele foi pedido, para a MENSAGEM dizer o número que a comparação usou.
     *
     * `%.0f` arredondava: um `--min=78.5` reprovava corretamente contra 78,5 e anunciava
     * *"abaixo do piso de 79%"* — nomeando um piso que não era o aplicado.
     */
    private function semZerosAtoa(float $piso): string
    {
        $texto = number_format($piso, 2, '.', '');

        return str_contains($texto, '.')
            ? rtrim(rtrim($texto, '0'), '.')
            : $texto;
    }

    /**
     * As métricas do projeto no Clover, ou `null` com o erro já impresso.
     *
     * @return array{0: int, 1: int}|null `[statements cobertas, statements totais]`
     */
    private function metricasDo(string $clover): ?array
    {
        $xml = @simplexml_load_file($clover);

        if ($xml === false) {
            $this->components->error("Relatório Clover ilegível: {$clover}");

            return null;
        }

        $metricas = $xml->project->metrics ?? null;

        if ($metricas === null) {
            $this->components->error("Relatório sem `<project><metrics>`: {$clover}");

            return null;
        }

        $total = (int) $metricas['statements'];

        if ($total === 0) {
            $this->components->error('Relatório com zero statements — o denominador `app/` não foi medido.');

            return null;
        }

        return [(int) $metricas['coveredstatements'], $total];
    }

    /**
     * Escreve ou confere `.github/badges/cobertura.json`, e não faz nada fora da árvore do kit.
     *
     * ## A comparação é do VALOR, não dos bytes
     *
     * A redação anterior comparava o JSON inteiro como string. Qualquer reindentação, reordenação
     * de chave ou perda da quebra de linha final reprovava o CI com a mensagem autocontraditória
     * *"o badge commitado diz 79% e o medido é 79%"* — e o motivo declarado para guardar o inteiro
     * era justamente não reprovar por ruído. Comparar o valor decodificado corrige isso sem abrir
     * mão da guarda: `message` e `color` continuam travados.
     *
     * @return list<string>
     */
    private function cuidarDoBadge(int $inteiro): array
    {
        $diretorio = base_path('.github/badges');

        if (! is_dir($diretorio)) {
            return [];
        }

        $caminho = $diretorio.'/cobertura.json';

        $badge = [
            'schemaVersion' => 1,
            'label'         => 'cobertura',
            'message'       => $inteiro.'%',
            'color'         => match (true) {
                $inteiro >= 80 => 'brightgreen',
                $inteiro >= 70 => 'green',
                $inteiro >= 60 => 'yellow',
                default        => 'red',
            },
        ];

        if ($this->option('write')) {
            file_put_contents($caminho, json_encode($badge, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n");

            $this->components->twoColumnDetail(
                '<fg=gray>Badge gravado</>',
                ".github/badges/cobertura.json → {$inteiro}%",
            );

            return [];
        }

        $commitado = is_file($caminho)
            ? json_decode((string) file_get_contents($caminho), true)
            : null;

        if (! is_array($commitado) || ($commitado['message'] ?? null) !== $badge['message']) {
            return [sprintf(
                'O badge commitado diz %s e o medido é %d%% — rode `composer test:coverage` e commite `.github/badges/cobertura.json`.',
                is_array($commitado) && is_string($commitado['message'] ?? null)
                    ? '"'.$commitado['message'].'"'
                    : '(ausente ou ilegível)',
                $inteiro,
            )];
        }

        if (($commitado['color'] ?? null) !== $badge['color']) {
            return [sprintf(
                'O badge commitado tem cor `%s` e a do medido é `%s` — rode `composer test:coverage` e commite.',
                (string) ($commitado['color'] ?? ''),
                $badge['color'],
            )];
        }

        return [];
    }
}
