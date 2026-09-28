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

        Schema::create('voyages', function (Blueprint $table) {
            $table->id();
            $table->date('date_depart');
            $table->date('date_arrivee');
            $table->time('heure_depart');
            $table->time('heure_arrivee');
            $table->unsignedBigInteger('ville_depart_id');
            $table->foreign('ville_depart_id')->references('id')->on('villes');
            $table->unsignedBigInteger('ville_arrivee_id');
            $table->foreign('ville_arrivee_id')->references('id')->on('villes');
            $table->unsignedBigInteger('autocar_id');
            $table->foreign('autocar_id')->references('id')->on('autocars');
            $table->unsignedBigInteger('type_voyage_id');
            $table->foreign('type_voyage_id')->references('id')->on('type_voyages');
            $table->float('prix');
            $table->timestamps();
        });

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('voyages');
    }
};
