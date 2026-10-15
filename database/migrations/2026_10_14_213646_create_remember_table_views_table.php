<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(config('remember-table.table_name', 'remember_table_views'), function (Blueprint $table) {
            $table->id();
            $table->morphs('user');
            $table->string('tenant_id')->nullable()->index();
            $table->string('table_key');
            $table->string('name');
            $table->string('icon')->nullable();
            $table->json('state');
            $table->boolean('is_default')->default(false);
            $table->boolean('is_public')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['table_key', 'user_type', 'user_id']);
            $table->index(['table_key', 'is_public']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config('remember-table.table_name', 'remember_table_views'));
    }
};
