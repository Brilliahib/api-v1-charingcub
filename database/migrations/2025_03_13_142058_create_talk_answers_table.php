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
        Schema::create('talk_answers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('talk_id');
            $table->uuid('user_id');
            $table->text('answer');
            $table->timestamps();

            $table->foreign('talk_id')->references('id')->on('talks')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('talk_answers');
    }
};
