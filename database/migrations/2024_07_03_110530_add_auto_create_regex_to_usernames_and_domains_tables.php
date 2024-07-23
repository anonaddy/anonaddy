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
            $table->string('auto_create_regex')->after('catch_all')->nullable();
        });

        Schema::table('domains', function (Blueprint $table) {
            $table->string('auto_create_regex')->after('catch_all')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('usernames', function (Blueprint $table) {
            $table->dropColumn('auto_create_regex');
        });

        Schema::table('domains', function (Blueprint $table) {
            $table->dropColumn('auto_create_regex');
        });
    }
};
