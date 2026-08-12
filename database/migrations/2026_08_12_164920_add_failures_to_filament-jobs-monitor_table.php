<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('queue_monitors', function (Blueprint $table) {
            $table->string('exception_class')->nullable()->index()->after('exception_message');
            $table->text('exception')->nullable()->after('exception_class');
            $table->string('failure_signature', 64)->nullable()->index()->after('exception');
        });

        Schema::create('queue_monitor_failure_groups', function (Blueprint $table) {
            $table->id();
            $table->string('signature', 64)->unique();
            $table->string('exception_class')->index();
            $table->string('job_class')->nullable();
            $table->string('queue')->nullable();
            $table->text('message')->nullable();
            $table->unsignedInteger('occurrences_count')->default(0);
            $table->timestamp('first_occurred_at')->nullable();
            $table->timestamp('last_occurred_at')->nullable()->index();
            $table->timestamp('resolved_at')->nullable()->index();
            $table->string('tenant_id')->nullable()->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('queue_monitor_failure_groups');

        Schema::table('queue_monitors', function (Blueprint $table) {
            $table->dropColumn(['exception_class', 'exception', 'failure_signature']);
        });
    }
};
