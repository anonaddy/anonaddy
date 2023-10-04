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
        Schema::dropIfExists('postfix_queue_ids');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::create('postfix_queue_ids', function (Blueprint $table) {
            $table->uuid('id');
            $table->string('queue_id', 20)->unique();

            $table->timestamps();
            $table->primary('id');
        });
    }
};
