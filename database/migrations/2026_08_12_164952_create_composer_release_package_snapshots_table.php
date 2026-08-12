<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('composer_release_package_snapshots', function (Blueprint $table) {
            $table->id();
            $table->string('package_name')->unique();
            $table->string('repository_owner');
            $table->string('repository_name');
            $table->string('installed_version');
            $table->string('latest_release_tag')->nullable();
            $table->boolean('is_outdated')->default(false);
            $table->text('compare_html_url')->nullable();
            $table->text('release_notes')->nullable();
            $table->json('commits_payload')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('composer_release_package_snapshots');
    }
};
