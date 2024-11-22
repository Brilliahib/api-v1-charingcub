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
        Schema::create('daycares', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('images'); 
            $table->text('description')->nullable();
            $table->time('opening_hours');
            $table->time('closing_hours');
            $table->string('opening_days'); 
            $table->string('phone_number')->nullable();
            $table->decimal('rating', 2, 1)->default(0);
            $table->integer('reviewers_count')->default(0);
            $table->string('location');
            $table->string('location_tracking'); 
            $table->integer('price');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('daycares');
    }
};
