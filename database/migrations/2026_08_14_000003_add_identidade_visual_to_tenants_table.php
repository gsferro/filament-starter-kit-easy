<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A identidade visual da organização: uma cor e uma logo.
 *
 * ## Por que UMA cor, e não a paleta
 *
 * `Filament\Support\Colors\Color::generatePalette()` deriva as 11 tonalidades (50…950) de um
 * único hex, e o `ColorManager` já a chama sozinho quando recebe string
 * (`vendor/filament/support/src/Colors/ColorManager.php:84-85`). Gravar a paleta seria gravar
 * dado calculável — e desatualizável, no dia em que o Filament ajustar a curva de luminosidade.
 *
 * ## Por que colunas, e não JSON
 *
 * Enquanto são dois campos, coluna nomeada é mais simples em tudo: o `ColorPicker` e o
 * `FileUpload` apontam direto para ela, sem `statePath` aninhado. O gatilho para reavaliar está
 * escrito em ADR-01 da wiki `identidade-visual-da-organizacao`: **ao terceiro campo**.
 *
 * ## Nulo é o estado neutro, não "desligado"
 *
 * Sem cor, o painel usa o default do Filament; sem logo, a mídia base do Auth Designer. A
 * feature é INERTE com os dois campos vazios — é o que a torna segura de mergear antes de
 * qualquer organização preencher.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table): void {
            // 7 caracteres: `#RRGGBB`. O ColorPicker do Filament grava neste formato com `->hex()`.
            $table->string('cor_primaria', 7)->nullable()->after('ativo');

            // Path relativo no disk `public`, no mesmo formato de `users.avatar_url`.
            $table->string('logo')->nullable()->after('cor_primaria');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table): void {
            $table->dropColumn(['cor_primaria', 'logo']);
        });

        // Os arquivos em storage/app/public/organizacoes/logos NÃO são apagados: `down()` que
        // remove arquivo de usuário é destrutivo além do que a migration prometeu. Arquivo órfão
        // é o custo aceito.
    }
};
