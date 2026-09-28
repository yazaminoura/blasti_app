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

        Schema::create('autocar_options', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('autocar_id');
            $table->foreign('autocar_id')->references('id')->on('autocars');
            $table->unsignedBigInteger('option_id');
            $table->foreign('option_id')->references('id')->on('options');
            $table->timestamps();
        });

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('autocar_options');
    }
};
