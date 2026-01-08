<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('webauthn_enabled')->after('two_factor_enabled')->default(false);
        });

        // After the migration loop over and set webauthn_enabled to true for all users with at least one enabled key.
        User::whereHas('webauthnKeys', function ($query) {
            $query->where('enabled', true);
        })->update(['webauthn_enabled' => true]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('webauthn_enabled');
        });
    }
};
