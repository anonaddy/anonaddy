<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWebauthnKeysTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('webauthn_keys', function (Blueprint $table) {
            $table->uuid('id');
            $table->uuid('user_id');
            $table->string('name')->default('key');
            $table->string('credentialId', 255)->index();
            $table->string('type', 255);
            $table->text('transports');
            $table->string('attestationType', 255);
            $table->text('trustPath');
            $table->text('aaguid');
            $table->text('credentialPublicKey');
            $table->integer('counter');
            $table->timestamps();

            // Causing foreign key mistmatch errors in SQLite
            if (config('database.default') !== 'sqlite') {
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            }
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
        Schema::dropIfExists('webauthn_keys');
    }
}
