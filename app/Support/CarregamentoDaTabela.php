<?php

declare(strict_types=1);

namespace App\Support;

use Asignua\FilamentInfiniteScroll\InfiniteScrollMode;
use Filament\Support\Contracts\HasLabel;

/**
 * Como TODA tabela dos três painéis carrega as linhas — a escala de carregamento do kit.
 *
 * ## Por que um enum de três valores, e não um toggle mais um select
 *
 * "Ligado sem modo" não é um estado que exista: ou a tabela pagina (o de hoje), ou cresce por
 * rolagem, ou cresce por botão. Um campo só, com três valores, é um estado só — e é o mesmo
 * desenho de {@see DensidadeDoLayout}, com o mesmo contrato (`opcoes()`, `deConfig()`,
 * `coagir()`, `padrao()`), para a tela de configurações e a migration de settings lerem igual.
 * Wiki `estudo-de-pacotes-rodada-3`, D1.
 *
 * ## O que cada modo faz na tabela
 *
 * Os dois modos contínuos são o macro `Table::infiniteScroll()` do
 * `asignua/filament-infinite-scroll`, aplicado em `ConfiguraFilamentGlobal::aplicaCarregamento()`
 * por request: a tabela continua UMA consulta, com `LIMIT` crescendo um bloco por vez (o bloco é
 * "Linhas por página"), reinicia no primeiro bloco quando busca, filtro, ordenação, agrupamento ou
 * aba mudam, e para no teto de 500 linhas do pacote. O seletor "registros por página" some, porque
 * é o modo que decide o tamanho. Em `Paginacao` o macro não é chamado e nada muda.
 *
 * ## Por que a coerção fica no consumo
 *
 * A mesma lição da densidade: `config/kit.php` coage o que veio do `.env`, e `deConfig()` coage
 * o que veio do BANCO — que `ConfiguracoesDoKit::aplicarNaConfig()` escreve direto na config, sem
 * passar pelo arquivo. Valor ilegível cai em `Paginacao` sem log e sem erro: o pior resultado de
 * um valor adulterado é a tabela continuar paginando — nunca um `ValueError` em toda listagem dos
 * três painéis (P-11).
 */
enum CarregamentoDaTabela: string implements HasLabel
{
    /** As páginas numeradas de hoje. O valor de fábrica: quem instala o kit não pediu rolagem. */
    case Paginacao = 'paginacao';

    /** O próximo bloco carrega sozinho quando o fim da tabela entra na tela. */
    case Rolagem = 'rolagem';

    /** O próximo bloco carrega no clique de "Carregar mais". */
    case Botao = 'botao';

    public function getLabel(): string
    {
        return match ($this) {
            self::Paginacao => 'Paginação — as páginas numeradas de hoje',
            self::Rolagem   => 'Rolagem infinita — o próximo bloco carrega ao chegar ao fim',
            self::Botao     => 'Botão "Carregar mais" — o próximo bloco carrega no clique',
        };
    }

    /**
     * As opções do Select da tela de configurações, como `valor => rótulo`.
     *
     * Array cru, e não `->options(CarregamentoDaTabela::class)`, pelo motivo medido em
     * {@see DensidadeDoLayout::opcoes()}: a classe faz o Filament castear de volta para instância
     * do enum, o `fill()` do spatie atribui a instância a uma propriedade `string` e a tela
     * inteira estoura `TypeError` ao salvar.
     *
     * @return array<string, string>
     */
    public static function opcoes(): array
    {
        return array_reduce(
            self::cases(),
            static fn (array $opcoes, self $modo): array => [...$opcoes, $modo->value => $modo->getLabel()],
            [],
        );
    }

    /**
     * O modo do pacote que este carregamento pede, ou `null` quando a tabela só pagina.
     *
     * `null` em `Paginacao` é o contrato que mantém a tabela de hoje intacta: quem consome não
     * chama o macro quando não há modo — chamar com um modo "neutro" fixaria `paginated()`,
     * `persistRecordsPerPageInSession(false)` e o resto que o pacote impõe.
     */
    public function modoDoPacote(): ?InfiniteScrollMode
    {
        return match ($this) {
            self::Paginacao => null,
            self::Rolagem   => InfiniteScrollMode::Scroll,
            self::Botao     => InfiniteScrollMode::Button,
        };
    }

    /**
     * O carregamento gravado nas configurações, lido POR REQUEST.
     *
     * Ler aqui, dentro do `Table::configureUsing()`, e não no `boot()`, é o que faz a tela
     * governar sem deploy — o critério de `.ai/rules/settings.md`.
     */
    public static function deConfig(): self
    {
        return self::coagir(config('kit.tabelas.carregamento'));
    }

    /**
     * Qualquer coisa vinda de fora → um modo válido, sem estourar.
     *
     * `tryFrom()` e não `from()`: vocabulário desconhecido (`scroll`, em inglês, que é o erro
     * provável), vazio, `null` e tipo errado caem em `Paginacao`.
     */
    public static function coagir(mixed $bruto): self
    {
        return (is_string($bruto) ? self::tryFrom($bruto) : null) ?? self::padrao();
    }

    /** O valor de fábrica, numa cópia só — `config/kit.php` e a migration de settings leem daqui. */
    public static function padrao(): self
    {
        return self::Paginacao;
    }
}
