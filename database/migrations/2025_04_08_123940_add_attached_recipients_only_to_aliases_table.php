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
            $table->boolean('attached_recipients_only')->after('from_name')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('aliases', function (Blueprint $table) {
            $table->dropColumn('attached_recipients_only');
        });
    }
};
