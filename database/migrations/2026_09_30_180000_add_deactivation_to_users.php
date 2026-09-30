<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A team account is never deleted (its sales, scans and cash payments must keep a name): it is closed instead.
 * A closed account cannot log in; it can be opened again by the super admin.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('desactive_le')->nullable();
            $table->foreignId('desactive_par')->nullable()->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('desactive_par');
            $table->dropColumn('desactive_le');
        });
    }
};
