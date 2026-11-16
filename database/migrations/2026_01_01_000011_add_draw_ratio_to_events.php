<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            // The arena board reads DRAW as a priced side (x7 on the paper
            // board), so the ratio belongs to the card like the stopper does.
            $table->decimal('draw_ratio', 6, 3)->default(7.000)->after('stop_ratio');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn('draw_ratio');
        });
    }
};
