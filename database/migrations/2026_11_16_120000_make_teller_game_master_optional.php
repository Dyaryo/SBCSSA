<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A teller is no longer assigned to one Game Master when the account is created.
 * Tills work whoever is on duty, so the column becomes nullable; the foreign key
 * is kept so any existing assignment still points at a real GM profile.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teller_profiles', function (Blueprint $table) {
            $table->unsignedBigInteger('game_master_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('teller_profiles', function (Blueprint $table) {
            $table->unsignedBigInteger('game_master_id')->nullable(false)->change();
        });
    }
};
