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
            $table->uuid('id')->primary();
            $table->uuid('user_id');
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
            $table->double('longitude', 20, 15)->nullable();
            $table->double('latitude', 20, 15)->nullable();            
            $table->text('address');
            $table->string('location_tracking'); 
            $table->boolean('is_disability');
            $table->string('bank_account');
            $table->string('bank_account_number');
            $table->string('bank_account_name');
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
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
