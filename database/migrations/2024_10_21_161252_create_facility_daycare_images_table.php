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
        Schema::create('facility_daycare_images', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('daycare_id');
            $table->string('image_url'); 
            $table->timestamps();

            $table->foreign('daycare_id')->references('id')->on('daycares')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('facility_daycare_images');
    }
};
