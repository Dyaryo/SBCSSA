<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // The stop bet ratio now belongs to the event: every fight created in
        // that event inherits it, so the pit does not type it fight by fight.
        Schema::table('events', function (Blueprint $table) {
            $table->decimal('stop_ratio', 6, 3)->nullable()->after('starts_at');
        });

        // Fighters are no longer recorded per fight: the corners are simply the
        // Meron and Wala sides, so the roster columns must allow no fighter.
        Schema::table('fights', function (Blueprint $table) {
            $table->foreignId('red_cock_id')->nullable()->change();
            $table->foreignId('blue_cock_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn('stop_ratio');
        });
    }
};
