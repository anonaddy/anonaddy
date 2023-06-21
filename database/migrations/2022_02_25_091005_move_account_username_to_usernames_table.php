<?php

use App\Models\Alias;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class MoveAccountUsernameToUsernamesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Rename the additional_usernames table to usernames
        Schema::rename('additional_usernames', 'usernames');

        // Update unique index name
        Schema::table('usernames', function (Blueprint $table) {
            $table->renameIndex('additional_usernames_username_unique', 'usernames_username_unique');
        });

        // Add default_username_id to users table
        Schema::table('users', function (Blueprint $table) {
            $table->uuid('default_username_id')->nullable()->after('id');
        });

        // Add current username from users table to the usernames table
        User::select(['id', 'username', 'default_username_id', 'catch_all'])->chunkById(200, function ($users) {
            foreach ($users as $user) {
                $username = $user->usernames()->create([
                    'username' => $user->getAttributes()['username'],
                    'catch_all' => $user->catch_all,
                ]);

                $user->update(['default_username_id' => $username->id]);

                // Update all aliases using that username
                $allDomains = config('anonaddy.all_domains')[0] ? config('anonaddy.all_domains') : [config('anonaddy.domain')];

                $usernameSubdomains = collect($allDomains)->map(function ($domain) use ($user) {
                    return $user->getAttributes()['username'].'.'.$domain;
                })->toArray();

                $user->aliases()
                    ->withTrashed()
                    ->select(['id', 'user_id', 'aliasable_id', 'aliasable_type', 'domain'])
                    ->whereIn('domain', $usernameSubdomains)
                    ->update([
                        'aliasable_id' => $username->id,
                        'aliasable_type' => 'App\Models\Username',
                    ]);
            }
        });

        // Remove nullable from default_username_id column
        if (User::whereNull('default_username_id')->count() === 0) {
            Schema::table('users', function (Blueprint $table) {
                $table->uuid('default_username_id')->nullable(false)->change();
            });
        }

        // Update all additional username aliases aliasable_type
        Alias::withTrashed()
            ->where('aliasable_type', 'App\Models\AdditionalUsername')
            ->update(['aliasable_type' => 'App\Models\Username']);

        // Drop the username and catch_all column from the users table
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('username');
        });
        // Separate call to dropColumn since SQLite doesn't support multiple calls
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('catch_all');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Add the username column back to the users table
        Schema::table('users', function (Blueprint $table) {
            $table->string('username')->after('id');
            $table->boolean('catch_all')->after('banner_location')->default(true);
        });

        // Update all usernames back to additional username
        Alias::withTrashed()
            ->where('aliasable_type', 'App\Models\Username')
            ->update(['aliasable_type' => 'App\Models\AdditionalUsername']);

        // Repopulate the username column and delete the defaultUsername from the usernames table
        User::select(['id', 'username', 'default_username_id', 'catch_all'])->chunk(200, function ($users) {
            foreach ($users as $user) {
                // Revert all aliases using that username
                $user->aliases()->withTrashed()->where('aliasable_id', $user->default_username_id)->update(['aliasable_id' => null, 'aliasable_type' => null]);

                $user->setRawAttributes(['username' => $user->defaultUsername->username]);
                $user->catch_all = $user->defaultUsername->catch_all;
                $user->save();

                $user->defaultUsername->delete();
            }
        });

        // Add the unique index back
        Schema::table('users', function (Blueprint $table) {
            $table->unique('username');
        });

        // Drop the default_username_id column
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('default_username_id');
        });

        // Rename the usernames table back to additional_usernames
        Schema::rename('usernames', 'additional_usernames');

        // Update unique index name
        Schema::table('additional_usernames', function (Blueprint $table) {
            $table->renameIndex('usernames_username_unique', 'additional_usernames_username_unique');
        });
    }
}
