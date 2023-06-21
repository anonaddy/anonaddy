<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddEmailsSentToAliasesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('aliases', function (Blueprint $table) {
            $table->unsignedInteger('emails_sent')->default(0)->after('emails_replied');
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
            $table->dropColumn('emails_sent');
        });
    }
}
