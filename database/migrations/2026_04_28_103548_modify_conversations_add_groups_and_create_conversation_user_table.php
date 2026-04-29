<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->boolean('is_group')->default(false);
            $table->string('name')->nullable();
        });

        Schema::create('conversation_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
        });

        $conversations = DB::table('conversations')->get();
        foreach ($conversations as $conv) {
            DB::table('conversation_user')->insert([
                ['conversation_id' => $conv->id, 'user_id' => $conv->sender_id, 'created_at' => now(), 'updated_at' => now()],
                ['conversation_id' => $conv->id, 'user_id' => $conv->receiver_id, 'created_at' => now(), 'updated_at' => now()],
            ]);
        }

        Schema::table('conversations', function (Blueprint $table) {
            $table->dropForeign(['sender_id']);
            $table->dropForeign(['receiver_id']);
            $table->dropColumn(['sender_id', 'receiver_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->foreignId('sender_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->foreignId('receiver_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->dropColumn(['is_group', 'name']);
        });

        Schema::dropIfExists('conversation_user');
    }
};
