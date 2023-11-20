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
        Schema::create('outbound_messages', function (Blueprint $table) {
            $table->string('id', 12);
            $table->uuid('user_id');
            $table->uuid('alias_id')->nullable();
            $table->uuid('recipient_id')->nullable();
            $table->string('email_type', 5);
            $table->boolean('bounced')->default(false);
            $table->timestamps();

            $table->primary('id');
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('outbound_messages');
    }
};
