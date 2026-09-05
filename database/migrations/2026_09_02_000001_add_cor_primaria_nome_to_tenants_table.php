<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A organização passa a escolher uma cor da paleta do Filament — a mesma escolha que o
 * settings do kit já oferece (`kit.cor_primaria`), ao lado da cor livre em hexadecimal.
 *
 * ## Por que uma coluna nova, e não `cor_primaria`
 *
 * `cor_primaria` é `string(7)`, feita para `#RRGGBB`, com regex âncorado no formulário. Os
 * dezesseis nomes da paleta cabem nela **por coincidência** (`Emerald` e `Fuchsia` têm sete
 * letras): um `varchar(7)` que ora guarda hex, ora `Emerald`, obriga toda leitura a adivinhar o
 * tipo pelo primeiro caractere, e o próximo nome com oito letras trunca em silêncio no sqlite.
 * Duas colunas, dois tipos, dois campos — o espelho exato de `kit.cor_primaria` e
 * `kit.cor_primaria_hex`.
 *
 * ## Por que ainda não é JSON
 *
 * A migration da identidade visual deixou o gatilho "ao terceiro campo, reavaliar". Este é o
 * terceiro, e é da mesma natureza dos dois primeiros: apontado direto por um componente de
 * formulário. JSON traria `statePath` aninhado para três campos e nenhum ganho. O gatilho passa
 * para o quarto campo, ou para o primeiro que não seja um componente de formulário. Ver ADR-02 da
 * wiki `paleta-do-filament-na-organizacao`.
 *
 * ## Nulo é o estado neutro
 *
 * Sem paleta e sem hex, o painel usa a cor da aplicação. A coluna nasce nula em toda organização
 * e a feature é inerte até alguém escolher.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table): void {
            // O nome de uma constante de `Filament\Support\Colors\Color` (`Blue`, `Emerald`…), da
            // lista fechada `CustomizadorDaInstalacao::CORES`. 32 dá folga para nomes futuros.
            $table->string('cor_primaria_nome', 32)->nullable()->after('cor_primaria');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table): void {
            $table->dropColumn('cor_primaria_nome');
        });
    }
};
