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
        DB::statement('UPDATE driver_profiles dp JOIN users u ON u.id = dp.user_id SET dp.phone = u.phone');

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
        DB::statement('UPDATE users u JOIN driver_profiles dp ON u.id = dp.user_id SET u.phone = dp.phone');

        // Drop phone from driver_profiles
        Schema::table('driver_profiles', function (Blueprint $table) {
            $table->dropColumn('phone');
        });
    }
};
