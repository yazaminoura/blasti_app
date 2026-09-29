<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Public contact details and social links, edited in Admin > Paramètres > Coordonnées (were only in .env). */
    public function up(): void
    {
        Schema::table('parametres', function (Blueprint $table) {
            $table->string('telephone', 40)->nullable();
            $table->string('email', 120)->nullable();
            $table->string('adresse', 200)->nullable();
            $table->string('facebook', 200)->nullable();
            $table->string('instagram', 200)->nullable();
            $table->string('tiktok', 200)->nullable();
            $table->string('x', 200)->nullable();
            $table->string('linkedin', 200)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('parametres', function (Blueprint $table) {
            $table->dropColumn(['telephone', 'email', 'adresse', 'facebook', 'instagram', 'tiktok', 'x', 'linkedin']);
        });
    }
};
