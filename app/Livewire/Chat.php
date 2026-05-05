<?php

namespace App\Livewire;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Livewire\Component;
use Livewire\Attributes\On;

class Chat extends Component
{
    public $conversations = [];
    public $activeConversation = null;
    public $messages = [];
    public $newMessage = '';
    public $search = '';
    
    public $showGroupModal = false;
    public $groupName = '';
    public $selectedUsers = [];
    public $allUsers = [];
    public $onlineUsers = [];

    public function mount()
    {
        $this->allUsers = User::where('id', '!=', auth()->id())->get();
        $this->loadConversations();
    }

    public function updatedSearch()
    {
        $this->loadConversations();
    }

    public function loadConversations()
    {
        $query = auth()->user()->conversations()
            ->with(['users', 'messages' => function($q) {
                $q->latest()->limit(1);
            }])
            ->orderByDesc('updated_at');

        if (!empty($this->search)) {
            $query->where(function($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhereHas('users', function($q2) {
                      $q2->where('users.id', '!=', auth()->id())
                         ->where('name', 'like', '%' . $this->search . '%');
                  });
            });
        }

        $this->conversations = $query->get();
    }

    public function selectConversation($conversationId)
    {
        $this->activeConversation = Conversation::with('users')->find($conversationId);
        $this->loadMessages();
    }

    public function createOrSelectPrivateChat($userId)
    {
        $conversation = auth()->user()->conversations()
            ->where('is_group', false)
            ->whereHas('users', function($q) use ($userId) {
                $q->where('users.id', $userId);
            })->first();

        if (!$conversation) {
            $conversation = Conversation::create([
                'is_group' => false,
            ]);
            $conversation->users()->attach([auth()->id(), $userId]);
            $this->loadConversations();
        }

        $this->selectConversation($conversation->id);
    }

    public function createGroup()
    {
        if (empty($this->groupName) || count($this->selectedUsers) < 1) {
            return;
        }

        $conversation = Conversation::create([
            'is_group' => true,
            'name' => $this->groupName
        ]);

        $usersToAttach = array_merge([auth()->id()], $this->selectedUsers);
        $conversation->users()->attach($usersToAttach);

        $this->showGroupModal = false;
        $this->groupName = '';
        $this->selectedUsers = [];
        
        $this->loadConversations();
        $this->selectConversation($conversation->id);
    }

    public function loadMessages()
    {
        if ($this->activeConversation) {
            $this->messages = Message::where('conversation_id', $this->activeConversation->id)
                ->with('user')
                ->oldest()
                ->get()
                ->toArray();
        }
    }

    public function sendMessage()
    {
        if (!$this->newMessage) return;
        if (!$this->activeConversation) return;

        $message = Message::create([
            'conversation_id' => $this->activeConversation->id,
            'user_id' => auth()->id(),
            'body' => $this->newMessage
        ]);

        $this->activeConversation->touch();

        $this->newMessage = '';
        
        $message->load('user');
        $this->messages[] = $message->toArray();

        broadcast(new \App\Events\MessageSent($message))->toOthers();
        $this->loadConversations();
    }

    #[On('receiveMessage')]
    public function receiveMessage($event)
    {
        if ($event['message']['conversation_id'] === $this->activeConversation?->id) {
            $this->messages[] = $event['message'];
        }
        $this->loadConversations();
    }

    #[On('updateOnlineUsers')]
    public function updateOnlineUsers($users)
    {
        $this->onlineUsers = $users;
    }

    public function render()
    {
        return view('livewire.chat');
    }
}
