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
        Schema::create('merlin_customers', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('merlin_id');
            $table->string('big_commerce_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('merlin_customers');
    }
};
