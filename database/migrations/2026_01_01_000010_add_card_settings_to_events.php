<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->unsignedInteger('planned_fights')->nullable()->after('name');
            $table->bigInteger('bomba_amount')->default(0)->after('planned_fights');
            $table->unsignedInteger('bomba_breakdown')->default(3)->after('bomba_amount');
            $table->unsignedInteger('bomba_interval')->default(30)->after('bomba_breakdown');
            $table->string('payout_display', 10)->default('decimals')->after('stop_ratio');
            $table->unsignedTinyInteger('tv_total_bets')->default(100)->after('payout_display');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn([
                'planned_fights', 'bomba_amount', 'bomba_breakdown', 'bomba_interval',
                'payout_display', 'tv_total_bets',
            ]);
        });
    }
};
