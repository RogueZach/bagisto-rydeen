<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedBigInteger('dealer_address_id')->nullable()->after('dealer_contact_id');
            $table->foreign('dealer_address_id')->references('id')->on('rydeen_dealer_addresses')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['dealer_address_id']);
            $table->dropColumn('dealer_address_id');
        });
    }
};
