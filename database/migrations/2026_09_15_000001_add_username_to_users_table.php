<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username')->nullable()->unique()->after('name');
        });

        // Auto-backfill existing users without username
        $users = DB::table('users')->get();
        foreach ($users as $user) {
            $baseUsername = '';
            if (!empty($user->email)) {
                $baseUsername = explode('@', $user->email)[0];
            } elseif (!empty($user->name)) {
                $baseUsername = Str::slug($user->name, '');
            } else {
                $baseUsername = 'user' . $user->id;
            }

            // Ensure unique username in case of collisions
            $username = $baseUsername;
            $counter = 1;
            while (DB::table('users')->where('username', $username)->where('id', '!=', $user->id)->exists()) {
                $username = $baseUsername . $counter;
                $counter++;
            }

            DB::table('users')->where('id', $user->id)->update([
                'username' => $username,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('username');
        });
    }
};
