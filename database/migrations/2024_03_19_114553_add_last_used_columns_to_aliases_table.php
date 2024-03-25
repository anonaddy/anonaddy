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
            $table->after('emails_sent', function (Blueprint $table) {
                $table->timestamp('last_forwarded')->nullable();
                $table->timestamp('last_blocked')->nullable();
                $table->timestamp('last_replied')->nullable();
                $table->timestamp('last_sent')->nullable();
            });
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('aliases', function (Blueprint $table) {
            $table->dropColumn('last_forwarded');
            $table->dropColumn('last_blocked');
            $table->dropColumn('last_replied');
            $table->dropColumn('last_sent');
        });
    }
};
