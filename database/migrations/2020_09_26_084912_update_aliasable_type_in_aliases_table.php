<?php

use App\Models\Alias;
use Illuminate\Database\Migrations\Migration;

class UpdateAliasableTypeInAliasesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Alias::withTrashed()
            ->where('aliasable_type', 'App\AdditionalUsername')
            ->update(['aliasable_type' => 'App\Models\AdditionalUsername']);

        Alias::withTrashed()
            ->where('aliasable_type', 'App\Domain')
            ->update(['aliasable_type' => 'App\Models\Domain']);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Alias::withTrashed()
            ->where('aliasable_type', 'App\Models\AdditionalUsername')
            ->update(['aliasable_type' => 'App\AdditionalUsername']);

        Alias::withTrashed()
            ->where('aliasable_type', 'App\Models\Domain')
            ->update(['aliasable_type' => 'App\Domain']);
    }
}
