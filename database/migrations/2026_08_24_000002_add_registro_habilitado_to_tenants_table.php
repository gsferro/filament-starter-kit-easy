<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cada organização decide se aceita registro aberto.
 *
 * ## Duas condições, não uma
 *
 * O requisito amarra as duas: *"se tiver um tenancy, **e** o register estiver liberado, também
 * pode optar por habilitar ou não o uso de register no seu tenant"*. Esta coluna só tem efeito
 * com `kit.registro.habilitado` ligado — ela recorta uma porta que a instalação já abriu, nunca
 * abre uma sozinha.
 *
 * ## Default `false`, e isso é a decisão
 *
 * Com default `true`, ligar a chave global abriria registro em TODA organização existente no
 * mesmo instante — decidindo pelo cliente, sem migration e sem ninguém escolher. Opt-in é a
 * única leitura do requisito que não faz isso.
 *
 * ## O endereço
 *
 * A rota de registro do Filament é do PAINEL, não do tenant: `/app/register` fica fora do grupo
 * de rotas com o segmento `/{tenant}`, então não existe organização no caminho da URL. O slug vai
 * por query string — `/app/register?org={slug}` —, no mesmo formato que o convite já usa para o
 * token, que é o precedente do kit para "parâmetro que chega por link".
 *
 * Não confundir com `->tenantRegistration()`, que o /app não tem de propósito: registrar-se NUMA
 * organização não é CRIAR organização.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table): void {
            $table->boolean('registro_habilitado')->default(false)->after('ativo');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table): void {
            // Reverter FECHA o registro de todas as organizações, que é o lado seguro: a porta
            // pública deixa de existir em vez de continuar aberta sem ninguém poder desligá-la.
            $table->dropColumn('registro_habilitado');
        });
    }
};
