<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedBigInteger('dealer_contact_id')->nullable()->after('customer_id');

            $table->foreign('dealer_contact_id')
                ->references('id')
                ->on('rydeen_dealer_contacts')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['dealer_contact_id']);
            $table->dropColumn('dealer_contact_id');
        });
    }
};
