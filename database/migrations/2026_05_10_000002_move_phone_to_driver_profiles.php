<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add phone to driver_profiles
        Schema::table('driver_profiles', function (Blueprint $table) {
            $table->string('phone', 30)->nullable()->after('is_available');
        });

        // Copy existing phone data from users to their driver_profiles
        // Using subquery instead of JOIN for cross-database compatibility
        DB::table('driver_profiles')->update([
            'phone' => DB::raw("(SELECT phone FROM users WHERE users.id = driver_profiles.user_id)"),
        ]);

        // Drop phone from users
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('phone');
        });
    }

    public function down(): void
    {
        // Add phone back to users
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone', 30)->nullable()->after('email');
        });

        // Restore phone data from driver_profiles
        // Using subquery instead of JOIN for cross-database compatibility
        DB::table('users')->update([
            'phone' => DB::raw("(SELECT phone FROM driver_profiles WHERE driver_profiles.user_id = users.id)"),
        ]);

        // Drop phone from driver_profiles
        Schema::table('driver_profiles', function (Blueprint $table) {
            $table->dropColumn('phone');
        });
    }
};
