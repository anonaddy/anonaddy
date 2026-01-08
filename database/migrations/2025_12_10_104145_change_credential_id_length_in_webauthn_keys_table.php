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
        Schema::table('webauthn_keys', function (Blueprint $table) {
            // Drop the index first before changing column type
            $table->dropIndex(['credentialId']);
        });

        Schema::table('webauthn_keys', function (Blueprint $table) {
            $table->mediumText('credentialId')->change();
            $table->bigInteger('counter')->unsigned()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('webauthn_keys', function (Blueprint $table) {
            $table->string('credentialId', 255)->change();
            $table->integer('counter')->change();
        });

        Schema::table('webauthn_keys', function (Blueprint $table) {
            // Recreate the index after changing back to string
            $table->index('credentialId');
        });
    }
};
