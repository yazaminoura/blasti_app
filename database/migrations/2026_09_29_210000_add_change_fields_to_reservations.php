<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            // what the client really paid (a ticket moved to a dearer departure owes the difference at boarding)
            $table->decimal('montant_paye', 10, 2)->nullable()->after('paye_le');
            // last time the client moved the ticket to another departure
            $table->timestamp('modifiee_le')->nullable()->after('montant_paye');
        });

        // tickets already paid: they paid their price
        DB::table('reservations')->whereNotNull('paye_le')->update(['montant_paye' => DB::raw('prix + frais')]);
    }

    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropColumn(['montant_paye', 'modifiee_le']);
        });
    }
};
