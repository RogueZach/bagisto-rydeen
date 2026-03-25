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
        Schema::create('rydeen_promotion_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('promotion_id');
            $table->unsignedInteger('product_id');
            $table->decimal('override_price', 12, 4)->nullable();
            $table->timestamps();

            $table->foreign('promotion_id')
                ->references('id')
                ->on('rydeen_promotions')
                ->cascadeOnDelete();

            $table->index('product_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rydeen_promotion_items');
    }
};
