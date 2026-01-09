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
        Schema::table('recipients', function (Blueprint $table) {
            $table->after('protected_headers', function (Blueprint $table) {
                $table->boolean('remove_pgp_keys')->default(true);
                $table->boolean('remove_pgp_signatures')->default(true);
            });
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('recipients', function (Blueprint $table) {
            $table->dropColumn(['remove_pgp_keys', 'remove_pgp_signatures']);
        });
    }
};
