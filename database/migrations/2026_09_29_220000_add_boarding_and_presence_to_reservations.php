<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            // the controller scanned the QR code and let the traveller in
            $table->timestamp('embarque_le')->nullable()->after('modifiee_le');
            // unpaid tickets: "I'm coming" asked by e-mail, and the client's answer (see safar.confirmation)
            $table->timestamp('confirmation_demandee_le')->nullable()->after('embarque_le');
            $table->timestamp('presence_confirmee_le')->nullable()->after('confirmation_demandee_le');
        });
    }

    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropColumn(['embarque_le', 'confirmation_demandee_le', 'presence_confirmee_le']);
        });
    }
};
