<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('game_master_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->string('employee_no', 40)->nullable();
            $table->string('contact', 60)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // Workstations are registered terminals (POS-001 ...). A terminal is only
        // trusted once a supervisor/game master approves the browser fingerprint.
        Schema::create('workstations', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('label', 80);
            $table->string('device_uuid', 64)->nullable()->unique();
            $table->unsignedBigInteger('assigned_teller_id')->nullable()->index();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();
        });

        Schema::create('teller_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->string('teller_code', 20)->unique();
            $table->foreignId('game_master_id')->constrained('game_master_profiles');
            $table->foreignId('workstation_id')->nullable()->constrained('workstations')->nullOnDelete();
            $table->string('status', 20)->default('active');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teller_profiles');
        Schema::dropIfExists('workstations');
        Schema::dropIfExists('game_master_profiles');
    }
};
