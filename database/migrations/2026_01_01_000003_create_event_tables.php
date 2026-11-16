<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();
            $table->string('name');
            $table->string('venue')->nullable();
            $table->foreignId('game_master_id')->constrained('game_master_profiles');
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->string('status', 20)->default('draft');
            $table->timestamps();
        });

        Schema::create('cocks', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('breed')->nullable();
            $table->unsignedInteger('weight_grams')->nullable();
            $table->string('owner_name')->nullable();
            $table->string('band_number', 30)->nullable();
            $table->string('status', 20)->default('available');
            $table->timestamps();
        });

        Schema::create('fights', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
            $table->unsignedInteger('fight_no');
            $table->foreignId('red_cock_id')->constrained('cocks');
            $table->foreignId('blue_cock_id')->constrained('cocks');
            $table->timestamp('scheduled_at')->nullable();
            $table->decimal('odds_red', 6, 3)->default(1.850);
            $table->decimal('odds_blue', 6, 3)->default(1.850);
            $table->string('status', 20)->default('scheduled');
            $table->timestamp('betting_opened_at')->nullable();
            $table->timestamp('betting_locked_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->string('winner', 10)->nullable();
            $table->unsignedBigInteger('declared_by')->nullable();
            $table->timestamp('declared_at')->nullable();
            $table->text('void_reason')->nullable();
            $table->timestamps();

            $table->unique(['event_id', 'fight_no']);
            $table->index(['status', 'scheduled_at']);
        });

        Schema::create('fight_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fight_id')->constrained('fights')->cascadeOnDelete();
            $table->string('kind', 30);
            $table->unsignedTinyInteger('round_no')->nullable();
            $table->text('note')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fight_events');
        Schema::dropIfExists('fights');
        Schema::dropIfExists('cocks');
        Schema::dropIfExists('events');
    }
};
