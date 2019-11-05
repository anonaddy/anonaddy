<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDefaultRecipientIdColumnToAdditionalUsernamesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('additional_usernames', function (Blueprint $table) {
            $table->uuid('default_recipient_id')->nullable()->after('user_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('additional_usernames', function (Blueprint $table) {
            $table->dropColumn('default_recipient_id');
        });
    }
}
