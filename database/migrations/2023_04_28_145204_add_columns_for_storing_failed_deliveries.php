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
            $table->boolean('is_stored')->after('alias_id')->default(false);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->boolean('store_failed_deliveries')->after('use_reply_to')->default(true);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('failed_deliveries', function (Blueprint $table) {
            $table->dropColumn('is_stored');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('store_failed_deliveries');
        });
    }
};
