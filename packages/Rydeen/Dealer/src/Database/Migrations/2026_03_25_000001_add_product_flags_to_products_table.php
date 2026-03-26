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
        Schema::table('products', function (Blueprint $table) {
            $table->boolean('rydeen_flag_new')->default(false)->after('attribute_family_id');
            $table->boolean('rydeen_flag_updated')->default(false)->after('rydeen_flag_new');
            $table->boolean('rydeen_flag_sale')->default(false)->after('rydeen_flag_updated');
            $table->boolean('rydeen_flag_reduced')->default(false)->after('rydeen_flag_sale');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'rydeen_flag_new',
                'rydeen_flag_updated',
                'rydeen_flag_sale',
                'rydeen_flag_reduced',
            ]);
        });
    }
};
