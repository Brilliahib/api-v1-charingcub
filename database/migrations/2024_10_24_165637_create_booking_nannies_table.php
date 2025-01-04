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
        Schema::create('booking_nannies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->uuid('nanny_id');
            $table->string('name_babies');
            $table->integer('age_babies');
            $table->text('special_request')->nullable();
            $table->dateTime('start_time');
            $table->dateTime('end_time');
            $table->boolean('is_approved')->default(false);
            $table->boolean('is_paid')->default(false);
            $table->string('payment_proof')->nullable();
            $table->timestamps();

            $table->foreign('nanny_id')->references('id')->on('nannies')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('booking_nannies');
    }
};
