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
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('daycare_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('images');
            $table->string('gender');
            $table->integer('age');
            $table->string('contact');
            $table->integer('price_half');
            $table->integer('price_full');
            $table->text('experience_description');
            $table->timestamps();
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
