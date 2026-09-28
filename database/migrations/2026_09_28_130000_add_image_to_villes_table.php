<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Photo of the city, shown on the home page "Nos destinations" cards. */
    public function up(): void
    {
        Schema::table('villes', function (Blueprint $table) {
            $table->string('image')->nullable()->after('ville');
        });
    }

    public function down(): void
    {
        Schema::table('villes', function (Blueprint $table) {
            $table->dropColumn('image');
        });
    }
};
