<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFailedDeliveriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('failed_deliveries', function (Blueprint $table) {
            $table->uuid('id');
            $table->uuid('user_id');
            $table->uuid('recipient_id')->nullable();
            $table->uuid('alias_id')->nullable();
            $table->string('bounce_type', 4)->nullable();
            $table->string('remote_mta')->nullable();
            $table->text('sender')->nullable();
            $table->string('email_type', 3)->nullable();
            $table->string('status')->nullable();
            $table->string('code')->nullable();
            $table->timestamps();

            $table->primary('id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('failed_deliveries');
    }
}
