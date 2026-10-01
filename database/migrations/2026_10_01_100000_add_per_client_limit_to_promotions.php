<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('promotions', function (Blueprint $table) {
            // orders one client may pay with the code (null = no limit); 1 by default, existing codes included
            $table->unsignedInteger('max_par_client')->nullable()->default(1)->after('max_utilisations');
        });
    }

    public function down(): void
    {
        Schema::table('promotions', function (Blueprint $table) {
            $table->dropColumn('max_par_client');
        });
    }
};
