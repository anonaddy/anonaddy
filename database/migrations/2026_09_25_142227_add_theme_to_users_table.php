<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('theme', 16)->default('system')->after('dark_mode');
        });

        DB::table('users')->where('dark_mode', 1)->update(['theme' => 'dark']);

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('dark_mode');
        });
    }

    /**
     * Reverse the migrations.
     *
     * System and light both become light. The system choice is not restored.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('dark_mode')->default(false)->after('save_alias_last_used');
        });

        DB::table('users')->where('theme', 'dark')->update(['dark_mode' => true]);

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('theme');
        });
    }
};
