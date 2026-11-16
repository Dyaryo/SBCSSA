<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bets', function (Blueprint $table) {
            // Window-side payout bookkeeping: the credits move automatically when
            // the fight is graded, these columns record the hand-over so the same
            // winning slip cannot be paid twice.
            $table->timestamp('paid_out_at')->nullable()->after('settled_at');
            $table->unsignedBigInteger('paid_out_by')->nullable()->after('paid_out_at');

            $table->index(['teller_id', 'paid_out_at'], 'bets_teller_paid_out_index');
        });
    }

    public function down(): void
    {
        Schema::table('bets', function (Blueprint $table) {
            $table->dropIndex('bets_teller_paid_out_index');
            $table->dropColumn(['paid_out_at', 'paid_out_by']);
        });
    }
};
