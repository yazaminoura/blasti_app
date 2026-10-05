<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Custom logo uploads for the platform brand.
     */
    public function up(): void
    {
        Schema::table('parametres', function (Blueprint $table) {
            $table->string('logo', 255)->nullable()->after('couleur');
            $table->string('logo_dark', 255)->nullable()->after('logo');
        });
    }

    public function down(): void
    {
        Schema::table('parametres', function (Blueprint $table) {
            $table->dropColumn(['logo', 'logo_dark']);
        });
    }
};
