<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            // regular = an ordinary fight day with no title; derby = a titled
            // event that lists its entries.
            $table->string('name')->nullable()->change();
            $table->string('type', 20)->default('regular')->after('name');
            $table->json('entries')->nullable()->after('type');
        });

        Schema::table('fights', function (Blueprint $table) {
            // Optional payout ceiling: payout = stake × min(odds, stop_ratio).
            $table->decimal('stop_ratio', 6, 3)->nullable()->after('odds_blue');
        });
    }

    public function down(): void
    {
        Schema::table('fights', function (Blueprint $table) {
            $table->dropColumn('stop_ratio');
        });

        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn('entries');
            $table->dropColumn('type');
        });
    }
};
