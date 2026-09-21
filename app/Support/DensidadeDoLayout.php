<?php

declare(strict_types=1);

namespace App\Support;

use App\Providers\KitServiceProvider;
use Filament\Support\Contracts\HasLabel;

/**
 * O quanto o layout dos três painéis aperta — a escala de densidade do kit.
 *
 * ## Por que isto é um enum de NÍVEIS e não um booleano
 *
 * ADR-04 de `wikis/specs/feat/layout-compact/layout-compact/`: apertar por `--spacing` entrega
 * o ganho de altura mas **distorce proporções** — ícone e caixa de seleção encolhem junto, e o
 * campo de texto fica mais baixo que o botão ao lado dele. A distorção não tem conserto barato,
 * mas ela **escala com a intensidade**: quanto menos se encolhe, menos ela aparece. Um booleano
 * fixaria uma intensidade para todo mundo; a escala deixa quem acha a distorção incômoda ficar
 * no degrau do meio. O custo é a MESMA declaração.
 *
 * ## O mecanismo, e por que ele não cita uma classe `fi-*` sequer
 *
 * A CSS publicada do Filament 5 é Tailwind 4: **todo** espaçamento é `calc(var(--spacing) * N)`,
 * e `var(--spacing)` aparece 1.228 vezes em `public/css/filament/filament/app.css`. O valor
 * `--spacing:.25rem` é declarado **uma vez só**, dentro de `@layer theme{:root,:host{…}}`.
 * Redeclarar `--spacing` **fora de cascade layer** vence aquela layer sem `!important`, sem
 * especificidade e sem depender de ordem de folha — e alcança as quatro superfícies do escopo
 * (stats, tabela, menu e botão) de uma vez, porque todas as quatro medem em `--spacing`.
 *
 * **Escrever CSS por classe `fi-*` é proibido aqui, e isso foi MEDIDO, não temido** (ADR-03): a
 * tentativa óbvia — `.fi-ta-cell{padding-block:.5rem}` — **somou** padding e piorou a altura da
 * tabela em 21,9%, porque `vendor/filament/tables/resources/css/cell.css:2` é `@apply p-0` e o
 * padding real mora no elemento da coluna, espalhado por nove arquivos `columns/*.css`. Falha em
 * silêncio e para o lado errado, que é a família de defeito que `.ai/rules/css-filament.md` já
 * registra três vezes.
 *
 * ## Os valores, com o número medido de cada um
 *
 * Medido no KIT e não na demo limpa — decisão do usuário, porque o kit tem jobs-monitor,
 * auth-designer, `resized-column` e Pulse, que a medição da ADR (feita em
 * `demo.filamentphp.com`) não tinha. Playwright, estilo computado, 2026-09-21, viewport
 * 1600×1000, `/admin/users` com 10 linhas e `resized-column` ligado:
 *
 * | | confortável | compacto | denso |
 * |---|---|---|---|
 * | `--spacing`              | `.25rem` (do Filament) | `0.2rem`          | `0.175rem`        |
 * | linha da tabela          | 56,0 px                | 46,4 px (−17,1%)  | 42,9 px (−23,4%)  |
 * | tabela de 10 linhas      | 612 px                 | 509,2 px (−16,8%) | 471,8 px (−22,9%) |
 * | `.fi-btn`                | 36,0 px                | 32,8 px (−8,9%)   | 31,2 px (−13,3%)  |
 * | `.fi-input`              | 36,0 px                | 28,8 px (−20,0%)  | 25,2 px (−30,0%)  |
 * | item do menu lateral     | 40,0 px                | 32,8 px (−18,0%)  | 31,2 px (−22,0%)  |
 * | cartão de estatística    | 150,0 px               | 137,2 px (−8,5%)  | 130,8 px (−12,8%) |
 * | ícone                    | 24,0 px                | 19,2 px (−20,0%)  | 16,8 px (−30,0%)  |
 *
 * As quatro superfícies do escopo respondem, e isso foi **confirmado por medição**, não deduzido
 * de o `--spacing` aparecer no seletor.
 *
 * **Duas superfícies NÃO respondem, e é achado, não defeito**: a **largura** do menu lateral fica
 * em 320 px nos três níveis (ela sai de `--sidebar-width`, não de `--spacing` — só a altura dos
 * itens aperta) e a **topbar** fica em 64 px (altura fixa). Nenhum dos três níveis produziu barra
 * de rolagem horizontal, em nenhuma das oito telas medidas.
 *
 * @see KitServiceProvider::configureDensidadeDoLayout() quem emite a declaração
 */
enum DensidadeDoLayout: string implements HasLabel
{
    /**
     * O padrão do Filament. `--spacing` fica como o framework o declara e o kit **não emite
     * `<style>` nenhum** — nem vazio.
     *
     * É o valor de fábrica de propósito: densidade é gosto, e quem instala o kit não pediu para
     * a aplicação dele mudar de aparência numa atualização. Quem quer apertar escolhe na tela.
     */
    case Confortavel = 'confortavel';

    /**
     * `--spacing: 0.2rem` — 80% do padrão, e o degrau que a medição apontou como melhor
     * equilíbrio.
     *
     * **−16,8% na altura da tabela** (612 px → 509,2 px em 10 linhas) e **−17,1% por linha**
     * (56,0 → 46,4 px), que já é mais do que os −11,3% que a ADR-03 mediu na demo limpa com
     * ESTE MESMO valor — as tabelas do kit têm mais coluna e mais altura por linha, então a
     * mesma declaração rende mais aqui.
     *
     * É o meio porque é onde a distorção ainda não incomoda: o ícone cai de 24 para 19,2 px
     * (−20%), que continua legível, e a diferença entre o campo (28,8 px) e o botão ao lado
     * dele (32,8 px) fica em 4 px. No denso essa diferença **dobra**.
     */
    case Compacto = 'compacto';

    /**
     * `--spacing: 0.175rem` — 70% do padrão, e o máximo que este mecanismo alcança.
     *
     * **−22,9% na altura da tabela** (612 px → 471,8 px) e **−23,4% por linha** (56,0 → 42,9 px).
     * O número importa: é praticamente o **−22,5% do tema pago oficial** que a ADR-05 mediu, com
     * uma declaração em vez de 548 blocos de regra sobre 370 classes de vendor.
     *
     * O preço é a distorção da ADR-03, e aqui ela é visível: o ícone cai a 16,8 px (−30%), o
     * campo a 25,2 px (−30%) e passa a ser **6 px mais baixo que o botão** ao lado dele (31,2 px)
     * — o botão encolhe só 13,3%, porque parte da altura dele é a linha de texto, que não mede em
     * `--spacing`. É exatamente por isso que existem níveis e não um booleano (ADR-04).
     */
    case Denso = 'denso';

    public function getLabel(): string
    {
        return match ($this) {
            self::Confortavel => 'Confortável — o padrão do Filament, nada muda',
            self::Compacto    => 'Compacto — cabe mais linha sem que ícone e caixa de seleção fiquem estranhos',
            self::Denso       => 'Denso — o máximo deste mecanismo; ícone e caixa de seleção encolhem junto',
        };
    }

    /**
     * As opções do Select da tela de configurações, como `valor => rótulo`.
     *
     * **Existe para que o campo NÃO seja `->options(DensidadeDoLayout::class)`**, e isso não é
     * preferência: passar a classe faz o Filament casteá-la de volta para instância do enum, e o
     * `fill()` do spatie atribui essa instância direto à propriedade
     * (`vendor/spatie/laravel-settings/src/Settings.php:181`). Com a propriedade tipada `string`
     * o resultado é `TypeError` ao **salvar a tela inteira** — não só este campo. Foi a suíte que
     * pegou: 60 casos de outras features (login social, anti-robô, login unificado) passaram a
     * dar erro porque todos salvam a mesma página.
     *
     * O array cru também é mais seguro na volta: um valor ilegível gravado na tabela abre a tela
     * com o campo em branco, em vez de estourar `from()` antes de renderizar.
     *
     * @return array<string, string>
     */
    public static function opcoes(): array
    {
        return array_reduce(
            self::cases(),
            static fn (array $opcoes, self $nivel): array => [...$opcoes, $nivel->value => $nivel->getLabel()],
            [],
        );
    }

    /**
     * O valor de `--spacing` deste nível, ou `null` quando não há nada a declarar.
     *
     * `null` em `Confortavel` é o contrato que impede o `<style>` vazio: o render hook devolve
     * string vazia e o Filament não emite nada. Um `'0.25rem'` aqui seria equivalente na tela e
     * **mentiria na inspeção** — o HTML passaria a carregar uma declaração inútil em toda
     * instalação que nunca mexeu nisto.
     */
    public function espacamento(): ?string
    {
        return match ($this) {
            self::Confortavel => null,
            self::Compacto    => '0.2rem',
            self::Denso       => '0.175rem',
        };
    }

    /**
     * O nível gravado nas configurações, lido POR REQUEST.
     *
     * Ler aqui, e não no `boot()`, é o que separa "a tela governa" de "grava e só vale no próximo
     * deploy" — a armadilha que `.ai/rules/settings.md` documenta e que `registro_verificar_email`
     * já produziu uma vez. Ver ADR-06.
     */
    public static function deConfig(): self
    {
        return self::coagir(config('kit.densidade_do_layout'));
    }

    /**
     * Qualquer coisa vinda de fora → um nível válido, sem estourar.
     *
     * A ÚNICA cópia da coerção, e ela é chamada nos dois lados porque as duas entradas escapam
     * uma da outra: `config/kit.php` coage o que veio do `.env`, e `deConfig()` coage o que veio
     * do BANCO — que `ConfiguracoesDoKit::aplicarNaConfig()` escreve direto na config, sem
     * passar pelo arquivo. Sem a segunda, uma linha adulterada em `settings` chegaria crua ao
     * render hook.
     *
     * `tryFrom()` e não `from()`: vocabulário desconhecido (`compact`, em inglês, que é o erro
     * provável) **falha para o confortável**. O pior resultado de um valor ilegível é a aplicação
     * não mudar de aparência — nunca um `ValueError` no layout base de toda tela dos três
     * painéis. Vazio, `null` e tipo errado caem no mesmo lugar.
     */
    public static function coagir(mixed $bruto): self
    {
        return (is_string($bruto) ? self::tryFrom($bruto) : null) ?? self::padrao();
    }

    /** O valor de fábrica, numa cópia só — o `config/kit.php` e a migration de settings leem daqui. */
    public static function padrao(): self
    {
        return self::Confortavel;
    }
}
