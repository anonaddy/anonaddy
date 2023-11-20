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
        Schema::table('usernames', function (Blueprint $table) {
            $table->boolean('can_login')->after('catch_all')->default(true);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('usernames', function (Blueprint $table) {
            $table->dropColumn('can_login');
        });
    }
};
