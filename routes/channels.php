<?php

use Illuminate\Support\Facades\Broadcast;
use App\Models\Conversation;

Broadcast::channel('chat.{conversationId}', function ($user, $conversationId) {
    $conversation = Conversation::find($conversationId);
    if (!$conversation) {
        return false;
    }
    return $conversation->users->contains($user->id);
});

Broadcast::channel('chat.presence', function ($user) {
    return ['id' => $user->id, 'name' => $user->name];
});
