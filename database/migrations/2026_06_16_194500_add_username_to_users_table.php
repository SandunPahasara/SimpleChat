<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\User;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username')->nullable()->unique();
        });

        // Backfill existing users
        $users = User::all();
        foreach ($users as $user) {
            if (empty($user->username)) {
                $emailPrefix = Str::before($user->email, '@');
                // Remove non-alphanumeric characters except underscore
                $username = preg_replace('/[^a-zA-Z0-9_]/', '', $emailPrefix);
                if (empty($username)) {
                    $username = 'user_' . $user->id;
                }
                
                // Ensure uniqueness
                $baseUsername = strtolower($username);
                $finalUsername = $baseUsername;
                $counter = 1;
                while (User::where('username', $finalUsername)->exists()) {
                    $finalUsername = $baseUsername . $counter;
                    $counter++;
                }
                
                $user->username = $finalUsername;
                $user->save();
            }
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
