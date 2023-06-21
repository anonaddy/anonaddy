<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCatchAllToAdditionalUsernamesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('additional_usernames', function (Blueprint $table) {
            $table->boolean('catch_all')->after('active')->default(true);
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
            $table->dropColumn('catch_all');
        });
    }
}
