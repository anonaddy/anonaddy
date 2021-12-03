<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddEmailTypesToRulesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('rules', function (Blueprint $table) {
            $table->boolean('sends')->after('operator')->default(false);
            $table->boolean('replies')->after('operator')->default(false);
            $table->boolean('forwards')->after('operator')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('rules', function (Blueprint $table) {
            $table->dropColumn('sends');
            $table->dropColumn('replies');
            $table->dropColumn('forwards');
        });
    }
}
