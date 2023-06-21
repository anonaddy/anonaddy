<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('aliases', function (Blueprint $table) {
            $table->index('user_id');
        });

        Schema::table('recipients', function (Blueprint $table) {
            $table->index('user_id');
        });

        Schema::table('usernames', function (Blueprint $table) {
            $table->index('user_id');
        });

        Schema::table('domains', function (Blueprint $table) {
            $table->index('user_id');
        });

        Schema::table('failed_deliveries', function (Blueprint $table) {
            $table->index('user_id');
        });

        Schema::table('rules', function (Blueprint $table) {
            $table->index('user_id');
        });

        Schema::table('alias_recipients', function (Blueprint $table) {
            $table->unique(['alias_id', 'recipient_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('aliases', function (Blueprint $table) {
            $table->dropIndex('aliases_user_id_index');
        });

        Schema::table('recipients', function (Blueprint $table) {
            $table->dropIndex('recipients_user_id_index');
        });

        Schema::table('usernames', function (Blueprint $table) {
            $table->dropIndex('usernames_user_id_index');
        });

        Schema::table('domains', function (Blueprint $table) {
            $table->dropIndex('domains_user_id_index');
        });

        Schema::table('failed_deliveries', function (Blueprint $table) {
            $table->dropIndex('failed_deliveries_user_id_index');
        });

        Schema::table('rules', function (Blueprint $table) {
            $table->dropIndex('rules_user_id_index');
        });

        Schema::table('alias_recipients', function (Blueprint $table) {
            $table->dropIndex('alias_recipients_alias_id_recipient_id_unique');
        });
    }
};
