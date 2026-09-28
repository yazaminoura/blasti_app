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

        Schema::create('autocar_equipements', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('autocar_id');
            $table->foreign('autocar_id')->references('id')->on('autocars');
            $table->unsignedBigInteger('equipement_id');
            $table->foreign('equipement_id')->references('id')->on('equipements');
            $table->timestamps();
        });

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('autocar_equipements');
    }
};
