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
        Schema::create('booking_daycares', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->uuid('daycare_id');
            $table->uuid('price_id');
            $table->string('name_babies');
            $table->integer('age_babies');
            $table->text('special_request')->nullable();
            $table->dateTime('start_time');
            $table->dateTime('end_time');
            $table->string('payment_proof')->nullable();
            $table->string('payment_method')->nullable();
            $table->string('payment_status')->nullable();
            $table->timestamps();

            $table->foreign('daycare_id')->references('id')->on('daycares')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('price_id')->references('id')->on('daycare_price_lists')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('booking_daycares');
    }
};
