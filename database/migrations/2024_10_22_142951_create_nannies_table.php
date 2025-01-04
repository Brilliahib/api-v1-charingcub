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
        Schema::create('nannies', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->uuid('daycare_id');
            $table->string('images');
            $table->string('gender');
            $table->integer('age');
            $table->string('contact');
            $table->integer('price_half');
            $table->integer('price_full');
            $table->text('experience_description');
            $table->timestamps();

            $table->foreign('daycare_id')->references('id')->on('daycares')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nannies');
    }
};
