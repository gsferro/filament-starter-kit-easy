<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A variante escura da logo da organização.
 *
 * Mesma forma de `logo`: path relativo no disk `public`, `nullable` e inerte quando
 * vazia — organização sem `logo_dark` cai para a `logo_dark` da instalação (ou para
 * a clara, quando a marca é unificada). Nenhuma organização passa a exibir outra
 * imagem por causa desta coluna: quem não a preencher continua vendo o que via.
 *
 * Coluna separada de `logo`, como `cor_primaria` × `cor_primaria_nome`: os dois
 * campos têm semânticas de exibição distintas (fundo claro × fundo escuro) e o
 * `FileUpload` do `TenantForm` aponta direto para ela.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table): void {
            $table->string('logo_dark')->nullable()->after('logo');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table): void {
            $table->dropColumn('logo_dark');
        });

        // Arquivos em storage/app/public/organizacoes/logos NÃO são apagados — a
        // mesma decisão de `down()` da migration que criou `logo`.
    }
};
