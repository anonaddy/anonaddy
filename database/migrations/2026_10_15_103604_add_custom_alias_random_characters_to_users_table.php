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
            $table->boolean('custom_alias_random_characters')->after('alias_separator')->default(false);
            $table->unsignedTinyInteger('custom_alias_random_length')->after('custom_alias_random_characters')->default(5);
            $table->string('custom_alias_random_charset', 20)->after('custom_alias_random_length')->default('alphanumeric');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'custom_alias_random_characters',
                'custom_alias_random_length',
                'custom_alias_random_charset',
            ]);
        });
    }
};
