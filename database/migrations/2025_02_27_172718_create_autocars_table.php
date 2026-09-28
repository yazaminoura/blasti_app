<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();

        Schema::create('autocars', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('nbr_siege');
            $table->string('image')->nullable();
            $table->string('matricule');
            $table->unsignedBigInteger('societe_id');
            $table->foreign('societe_id')->references('id')->on('societes');
            $table->timestamps();
        });

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('autocars');
    }
};
