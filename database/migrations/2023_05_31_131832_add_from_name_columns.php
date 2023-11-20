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
        Schema::table('aliases', function (Blueprint $table) {
            $table->text('from_name')->after('description')->nullable();
        });

        Schema::table('usernames', function (Blueprint $table) {
            $table->text('from_name')->after('description')->nullable();
        });

        Schema::table('domains', function (Blueprint $table) {
            $table->text('from_name')->after('description')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('aliases', function (Blueprint $table) {
            $table->dropColumn('from_name');
        });

        Schema::table('usernames', function (Blueprint $table) {
            $table->dropColumn('from_name');
        });

        Schema::table('domains', function (Blueprint $table) {
            $table->dropColumn('from_name');
        });
    }
};
