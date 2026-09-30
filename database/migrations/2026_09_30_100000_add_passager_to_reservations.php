<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Name of the traveller of each seat (booking several seats for a family): printed on his ticket. */
    public function up(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->string('passager_nom', 120)->nullable()->after('user_id');
        });
    }

    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropColumn('passager_nom');
        });
    }
};
