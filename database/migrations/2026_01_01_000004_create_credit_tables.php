<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('virtual_wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->bigInteger('balance_credits')->default(0);
            $table->unsignedInteger('version')->default(0);
            $table->timestamps();
        });

        Schema::create('shift_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teller_id')->constrained('teller_profiles');
            $table->foreignId('workstation_id')->nullable()->constrained('workstations')->nullOnDelete();
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->text('opening_note')->nullable();
            $table->text('closing_note')->nullable();
            $table->unsignedInteger('total_bets_count')->default(0);
            $table->bigInteger('total_amount_credits')->default(0);
            $table->string('status', 20)->default('open');
            $table->text('closed_reason')->nullable();
            $table->timestamps();

            $table->index(['teller_id', 'status']);
        });

        Schema::create('bets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fight_id')->constrained('fights')->cascadeOnDelete();
            $table->foreignId('teller_id')->constrained('teller_profiles');
            $table->foreignId('shift_session_id')->nullable()->constrained('shift_sessions')->nullOnDelete();
            $table->string('side', 10);
            $table->bigInteger('amount_credits');
            $table->decimal('odds_at_placement', 6, 3);
            $table->bigInteger('potential_payout');
            $table->string('status', 20)->default('active');
            $table->timestamp('placed_at')->useCurrent();
            $table->timestamp('settled_at')->nullable();
            $table->unsignedBigInteger('voided_by')->nullable();
            $table->text('void_reason')->nullable();
            $table->string('idempotency_key', 64)->nullable()->unique();
            $table->timestamps();

            $table->index(['fight_id', 'status']);
            $table->index(['teller_id', 'placed_at']);
        });

        // Append-only ledger. Balance changes only ever happen through this table.
        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wallet_id')->constrained('virtual_wallets')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('fight_id')->nullable()->constrained('fights')->nullOnDelete();
            $table->unsignedBigInteger('bet_id')->nullable()->index();
            $table->string('type', 40);
            $table->string('direction', 10);
            $table->bigInteger('amount_credits');
            $table->bigInteger('balance_after');
            $table->string('reference_no', 40)->unique();
            $table->string('idempotency_key', 64)->nullable()->unique();
            $table->unsignedBigInteger('performed_by')->nullable();
            $table->string('reason')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallet_transactions');
        Schema::dropIfExists('bets');
        Schema::dropIfExists('shift_sessions');
        Schema::dropIfExists('virtual_wallets');
    }
};
