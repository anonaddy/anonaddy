<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAliasesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('aliases', function (Blueprint $table) {
            $table->uuid('id');
            $table->uuid('user_id');
            $table->string('email')->unique();
            $table->boolean('active')->default(true);
            $table->text('description')->nullable();
            $table->unsignedInteger('emails_forwarded')->default(0);
            $table->unsignedInteger('emails_blocked')->default(0);
            $table->timestamps();
            $table->softDeletes();

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
        Schema::dropIfExists('aliases');
    }
}
