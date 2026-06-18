<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Events\MessageSent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ApiController extends Controller
{
    // --- AUTHENTICATION ---

    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'username' => 'required|string|max:255|unique:users|alpha_dash',
            'password' => 'required|string|min:8',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'username' => strtolower($request->username),
            'password' => Hash::make($request->password),
        ]);

        $token = $user->createToken('mobile_token')->plainTextToken;

        return response()->json([
            'message' => 'Registration successful',
            'token' => $token,
            'user' => $user
        ], 201);
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'login' => 'required|string',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Support logging in via email or username
        $loginField = filter_var($request->login, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        $user = User::where($loginField, strtolower($request->login))->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Invalid credentials'
            ], 401);
        }

        $token = $user->createToken('mobile_token')->plainTextToken;

        return response()->json([
            'message' => 'Login successful',
            'token' => $token,
            'user' => $user
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully'
        ]);
    }

    public function me(Request $request)
    {
        return response()->json($request->user());
    }

    // --- CONVERSATIONS ---

    public function indexConversations(Request $request)
    {
        $user = $request->user();

        $conversations = $user->conversations()
            ->with(['users', 'messages' => function ($q) {
                $q->latest()->limit(1);
            }])
            ->orderByDesc('updated_at')
            ->get();

        // Format the conversations to make them extremely easy for the React Native frontend to consume
        $formatted = $conversations->map(function ($conversation) use ($user) {
            $lastMessage = $conversation->messages->first();
            
            // If it's a direct chat (not a group), get the other user's info
            $otherUser = null;
            if (!$conversation->is_group) {
                $otherUser = $conversation->users->first(function ($u) use ($user) {
                    return $u->id !== $user->id;
                });
            }

            return [
                'id' => $conversation->id,
                'is_group' => (bool)$conversation->is_group,
                'name' => $conversation->is_group ? $conversation->name : ($otherUser ? $otherUser->name : 'Unknown User'),
                'username' => $otherUser ? $otherUser->username : null,
                'other_user_id' => $otherUser ? $otherUser->id : null,
                'updated_at' => $conversation->updated_at,
                'last_message' => $lastMessage ? [
                    'id' => $lastMessage->id,
                    'body' => $lastMessage->body,
                    'attachment' => $lastMessage->attachment ? Storage::url($lastMessage->attachment) : null,
                    'attachment_type' => $lastMessage->attachment_type,
                    'created_at' => $lastMessage->created_at,
                    'user_id' => $lastMessage->user_id,
                    'sender_name' => $lastMessage->user ? $lastMessage->user->name : 'System',
                ] : null,
            ];
        });

        return response()->json($formatted);
    }

    public function storeConversation(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'is_group' => 'required|boolean',
            'name' => 'required_if:is_group,true|nullable|string|max:255',
            'user_id' => 'required_if:is_group,false|nullable|exists:users,id',
            'user_ids' => 'required_if:is_group,true|nullable|array',
            'user_ids.*' => 'exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        if (!$request->is_group) {
            $targetUserId = $request->user_id;

            // Check if a direct conversation already exists between these two users
            $conversation = $user->conversations()
                ->where('is_group', false)
                ->whereHas('users', function ($q) use ($targetUserId) {
                    $q->where('users.id', $targetUserId);
                })->first();

            if ($conversation) {
                return response()->json([
                    'id' => $conversation->id,
                    'is_group' => false,
                    'message' => 'Conversation already exists',
                ]);
            }

            // Create new direct conversation
            $conversation = Conversation::create([
                'is_group' => false,
            ]);
            $conversation->users()->attach([$user->id, $targetUserId]);

            return response()->json([
                'id' => $conversation->id,
                'is_group' => false,
                'message' => 'Direct chat created successfully',
            ], 201);
        } else {
            // Create group chat
            $conversation = Conversation::create([
                'is_group' => true,
                'name' => $request->name,
            ]);

            $usersToAttach = array_merge([$user->id], $request->user_ids);
            $conversation->users()->attach(array_unique($usersToAttach));

            return response()->json([
                'id' => $conversation->id,
                'is_group' => true,
                'name' => $conversation->name,
                'message' => 'Group chat created successfully',
            ], 201);
        }
    }

    // --- MESSAGES ---

    public function indexMessages(Request $request, $conversationId)
    {
        $user = $request->user();
        $conversation = Conversation::find($conversationId);

        if (!$conversation) {
            return response()->json(['message' => 'Conversation not found'], 404);
        }

        // Verify user belongs to conversation
        if (!$conversation->users->contains($user->id)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $messages = Message::where('conversation_id', $conversationId)
            ->with('user')
            ->oldest()
            ->get();

        $formatted = $messages->map(function ($message) {
            return [
                'id' => $message->id,
                'conversation_id' => $message->conversation_id,
                'body' => $message->body,
                'attachment' => $message->attachment ? Storage::url($message->attachment) : null,
                'attachment_type' => $message->attachment_type,
                'created_at' => $message->created_at,
                'user' => [
                    'id' => $message->user->id,
                    'name' => $message->user->name,
                    'username' => $message->user->username,
                ]
            ];
        });

        return response()->json($formatted);
    }

    public function storeMessage(Request $request, $conversationId)
    {
        $user = $request->user();
        $conversation = Conversation::find($conversationId);

        if (!$conversation) {
            return response()->json(['message' => 'Conversation not found'], 404);
        }

        // Verify user belongs to conversation
        if (!$conversation->users->contains($user->id)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'body' => 'required_without:attachment|nullable|string',
            'attachment' => 'nullable|file|max:10240', // Max 10MB
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $attachmentPath = null;
        $attachmentType = null;

        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $attachmentPath = $file->store('attachments', 'public');
            $mime = $file->getMimeType();

            if (str_starts_with($mime, 'image/')) {
                $attachmentType = 'image';
            } elseif (str_starts_with($mime, 'video/')) {
                $attachmentType = 'video';
            } else {
                $attachmentType = 'file';
            }
        }

        $message = Message::create([
            'conversation_id' => $conversationId,
            'user_id' => $user->id,
            'body' => $request->body ?? '',
            'attachment' => $attachmentPath,
            'attachment_type' => $attachmentType,
        ]);

        $conversation->touch(); // Update updated_at of conversation

        $message->load('user');

        // Broadcast to Laravel Reverb
        broadcast(new MessageSent($message))->toOthers();

        return response()->json([
            'id' => $message->id,
            'conversation_id' => $message->conversation_id,
            'body' => $message->body,
            'attachment' => $message->attachment ? Storage::url($message->attachment) : null,
            'attachment_type' => $message->attachment_type,
            'created_at' => $message->created_at,
            'user' => [
                'id' => $message->user->id,
                'name' => $message->user->name,
                'username' => $message->user->username,
            ]
        ], 201);
    }

    // --- USER SEARCH ---

    public function searchUser(Request $request)
    {
        $user = $request->user();
        $query = $request->query('q');

        if (empty($query)) {
            return response()->json([]);
        }

        $cleanQuery = ltrim(trim($query), '@');
        $cleanQuery = strtolower($cleanQuery);

        // Search in name or username, excluding the current authenticated user
        $users = User::where('id', '!=', $user->id)
            ->where(function ($q) use ($cleanQuery) {
                $q->where('username', 'like', '%' . $cleanQuery . '%')
                  ->orWhere('name', 'like', '%' . $cleanQuery . '%');
            })
            ->limit(10)
            ->get(['id', 'name', 'username', 'email']);

        return response()->json($users);
    }
}
