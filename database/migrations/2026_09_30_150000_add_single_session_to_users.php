<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One open session per team / company account (App\Support\SessionUnique) and the last login shown in the admin.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // token of the only valid session; a login elsewhere replaces it, so the older browser is logged out
            $table->string('session_jeton', 64)->nullable();
            $table->timestamp('derniere_connexion_le')->nullable();
            $table->string('derniere_connexion_ip', 45)->nullable();
            $table->string('derniere_connexion_appareil', 120)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['session_jeton', 'derniere_connexion_le', 'derniere_connexion_ip', 'derniere_connexion_appareil']);
        });
    }
};
