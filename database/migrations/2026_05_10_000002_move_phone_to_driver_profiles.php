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

        // Copy existing phone data from users to their driver_profiles (SQLite-compatible)
        $rows = DB::table('users')
            ->join('driver_profiles', 'users.id', '=', 'driver_profiles.user_id')
            ->whereNotNull('users.phone')
            ->select('driver_profiles.id as profile_id', 'users.phone')
            ->get();

        foreach ($rows as $row) {
            DB::table('driver_profiles')
                ->where('id', $row->profile_id)
                ->update(['phone' => $row->phone]);
        }

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

        // Restore phone data from driver_profiles (SQLite-compatible)
        $rows = DB::table('driver_profiles')
            ->join('users', 'driver_profiles.user_id', '=', 'users.id')
            ->whereNotNull('driver_profiles.phone')
            ->select('users.id as user_id', 'driver_profiles.phone')
            ->get();

        foreach ($rows as $row) {
            DB::table('users')
                ->where('id', $row->user_id)
                ->update(['phone' => $row->phone]);
        }

        // Drop phone from driver_profiles
        Schema::table('driver_profiles', function (Blueprint $table) {
            $table->dropColumn('phone');
        });
    }
};
