<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rydeen_dealer_addresses', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('customer_id');
            $table->string('label', 100);
            $table->string('first_name');
            $table->string('last_name');
            $table->string('company_name')->nullable();
            $table->string('address1');
            $table->string('address2')->nullable();
            $table->string('city');
            $table->string('state');
            $table->string('postcode');
            $table->string('country')->default('US');
            $table->string('phone')->nullable();
            $table->boolean('is_approved')->default(false);
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->index(['customer_id', 'is_approved']);
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rydeen_dealer_addresses');
    }
};
