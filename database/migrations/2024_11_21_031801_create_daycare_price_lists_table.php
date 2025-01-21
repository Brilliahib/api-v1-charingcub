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
        Schema::create('daycare_price_lists', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('daycare_id'); 
            $table->string('age_start'); 
            $table->string('age_end');
            $table->string('name');
            $table->integer('price'); 
            $table->timestamps();

            $table->foreign('daycare_id')->references('id')->on('daycares')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('daycare_price_lists');
    }
};
