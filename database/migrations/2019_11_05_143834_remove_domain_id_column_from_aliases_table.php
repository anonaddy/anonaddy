<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RemoveDomainIdColumnFromAliasesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('aliases', function (Blueprint $table) {
            $table->dropColumn('domain_id');
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
            $table->uuid('domain_id')->after('user_id')->nullable();
        });
    }
}
