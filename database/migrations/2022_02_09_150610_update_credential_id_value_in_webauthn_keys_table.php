<?php

use App\Models\WebauthnKey;
use Illuminate\Database\Migrations\Migration;

class UpdateCredentialIdValueInWebauthnKeysTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Update from base64 to base64URL encoding
        WebauthnKey::select(['id', 'credentialId'])->chunk(200, function ($keys) {
            foreach ($keys as $key) {
                $key->update(['credentialId' => $key->credentialId]);
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        WebauthnKey::select(['id', 'credentialId'])->chunk(200, function ($keys) {
            foreach ($keys as $key) {
                $key->setRawAttributes(['credentialId' => base64_encode($key->credentialId)]);
                $key->save();
            }
        });
    }
}
