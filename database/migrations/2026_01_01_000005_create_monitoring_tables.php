<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('login_events', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('username_attempt', 50)->nullable();
            $table->unsignedBigInteger('workstation_id')->nullable();
            $table->string('kind', 30);
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        // Append-only. Never updated or deleted by the application.
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('actor_user_id')->nullable()->index();
            $table->string('actor_role', 20)->nullable();
            $table->string('action', 60)->index();
            $table->string('entity_type', 40)->nullable();
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->json('before')->nullable();
            $table->json('after')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->unsignedBigInteger('workstation_id')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['entity_type', 'entity_id']);
            $table->index('created_at');
        });

        // Hash-chained report snapshots so a past report can be proven unchanged.
        Schema::create('report_snapshots', function (Blueprint $table) {
            $table->id();
            $table->string('scope', 30);
            $table->unsignedBigInteger('scope_ref_id')->nullable();
            $table->date('period_start')->nullable();
            $table->date('period_end')->nullable();
            $table->json('payload');
            $table->string('payload_hash', 64);
            $table->string('prev_hash', 64)->nullable();
            $table->unsignedBigInteger('generated_by')->nullable();
            $table->timestamp('generated_at')->useCurrent();

            $table->index(['scope', 'scope_ref_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_snapshots');
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('login_events');
    }
};
