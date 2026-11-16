<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fights', function (Blueprint $table) {
            $table->boolean('red_locked')->default(false)->after('odds_blue');
            $table->boolean('blue_locked')->default(false)->after('red_locked');
        });
    }

    public function down(): void
    {
        Schema::table('fights', function (Blueprint $table) {
            $table->dropColumn(['red_locked', 'blue_locked']);
        });
    }
};
