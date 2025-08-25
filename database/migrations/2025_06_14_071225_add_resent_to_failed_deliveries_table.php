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
        Schema::table('failed_deliveries', function (Blueprint $table) {
            $table->boolean('resent')->after('is_stored')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('failed_deliveries', function (Blueprint $table) {
            $table->dropColumn('resent');
        });
    }
};
