<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Several seats booked together = one order: one ticket per seat, paid and emailed together
        Schema::table('reservations', function (Blueprint $table) {
            $table->string('commande', 20)->nullable()->index()->after('id');
        });

        // Email verification is now required to book: accounts created before it keep working
        DB::table('users')->whereNull('email_verified_at')->update(['email_verified_at' => now()]);
    }

    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropIndex(['commande']);
            $table->dropColumn('commande');
        });
    }
};
