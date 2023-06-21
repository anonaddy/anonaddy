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
        Schema::table('users', function (Blueprint $table) {
            $table->string('authentik_id')->after('two_factor_backup_code')->nullable();
            $table->string('authentik_token', 1000)->after('remember_token')->nullable();
            $table->string('authentik_refresh_token')->after('authentik_token')->nullable();
            $table->string('password')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('authentik_id');
            $table->dropColumn('authentik_token');
	    $table->dropColumn('authentik_refresh_token');
	    $table->string('password')->nullable(false)->change();
        });
    }
};
