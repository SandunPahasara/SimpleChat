<div class="flex h-[calc(100vh-65px)] bg-white/20 dark:bg-zinc-950/20 backdrop-blur-sm overflow-hidden text-gray-800 dark:text-zinc-100 transition-colors"
     x-data="{ showGroupModal: @entangle('showGroupModal') }">
    <!-- Left Sidebar -->
    <div class="w-1/3 bg-gray-50/40 dark:bg-zinc-900/40 border-r border-gray-200 dark:border-zinc-800 flex flex-col transition-colors">
        <div class="p-3 border-b border-gray-200 dark:border-zinc-800 bg-white/60 dark:bg-zinc-950/60 flex flex-col gap-3 transition-colors">
            <div class="flex justify-between items-center">
                <span class="font-semibold text-xl text-gray-800 dark:text-white">Chats</span>
                <div class="flex space-x-1 text-gray-500 dark:text-zinc-400">
                    <button class="p-2 hover:text-[#FF2D20] hover:bg-gray-100 dark:hover:bg-zinc-800 rounded-full transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" stroke-width="2" stroke-dasharray="10 20" stroke-linecap="round"></circle></svg>
                    </button>
                    <button class="p-2 hover:text-[#FF2D20] hover:bg-gray-100 dark:hover:bg-zinc-800 rounded-full transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                    </button>
                    <button @click="showGroupModal = true" class="p-2 hover:text-[#FF2D20] hover:bg-gray-100 dark:hover:bg-zinc-800 rounded-full transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                    </button>
                </div>
            </div>
            <!-- Search Bar -->
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                </div>
                <input wire:model.live.debounce.300ms="search" type="text" class="w-full bg-gray-100/80 dark:bg-zinc-800/80 border-none rounded-lg pl-9 pr-4 py-2 text-sm text-gray-800 dark:text-white placeholder-gray-500 focus:ring-1 focus:ring-[#FF2D20] transition-shadow" placeholder="Search or start a new chat">
            </div>
        </div>
        <div class="overflow-y-auto flex-1 p-2">
            @forelse($conversations as $conversation)
                @php
                    $isGroup = $conversation->is_group;
                    $name = $isGroup ? $conversation->name : $conversation->users->where('id', '!=', auth()->id())->first()?->name;
                    $otherUser = !$isGroup ? $conversation->users->where('id', '!=', auth()->id())->first() : null;
                    $isOnline = !$isGroup && $otherUser ? in_array($otherUser->id, $onlineUsers) : false;
                @endphp
                <div 
                    wire:click="selectConversation({{ $conversation->id }})" 
                    class="flex items-center p-3 mb-2 cursor-pointer rounded-lg hover:bg-gray-200 dark:hover:bg-zinc-800 transition-colors {{ $activeConversation?->id === $conversation->id ? 'bg-gray-200 dark:bg-zinc-800 ring-1 ring-[#FF2D20]' : '' }}"
                >
                    <div class="h-10 w-10 rounded-full bg-[#FF2D20] text-white flex items-center justify-center font-bold text-lg shadow-md relative">
                        {{ strtoupper(substr($name ?? 'G', 0, 1)) }}
                        @if($isOnline)
                            <span class="absolute bottom-0 right-0 block h-3 w-3 rounded-full bg-green-500 ring-2 ring-white dark:ring-zinc-900"></span>
                        @endif
                    </div>
                    <div class="ml-4 flex-1 overflow-hidden">
                        <div class="font-semibold text-gray-800 dark:text-white flex justify-between items-baseline">
                            <span class="truncate">{{ $name }}</span>
                            @if(isset($conversation->messages) && $conversation->messages->count() > 0)
                                <span class="text-xs text-gray-500 dark:text-zinc-500 whitespace-nowrap ml-2">
                                    {{ \Carbon\Carbon::parse($conversation->messages->first()->created_at)->shortAbsoluteDiffForHumans() }}
                                </span>
                            @elseif($isGroup)
                                <span class="text-[10px] text-gray-500 bg-gray-200 dark:bg-zinc-700 px-1.5 py-0.5 rounded-md whitespace-nowrap ml-2">Group</span>
                            @endif
                        </div>
                        <div class="flex justify-between items-center mt-0.5">
                            <div class="text-sm text-gray-500 dark:text-zinc-400 truncate pr-2">
                                @if(isset($conversation->messages) && $conversation->messages->count() > 0)
                                    {{ $conversation->messages->first()->body }}
                                @else
                                    <span class="italic text-gray-400">No messages yet</span>
                                @endif
                            </div>
                            <!-- Mock Unread Badge (1 in 4 chance for demo) -->
                            @if(rand(0, 3) === 1)
                                <span class="bg-[#FF2D20] text-white text-[10px] font-bold px-1.5 py-0.5 rounded-full min-w-[18px] flex items-center justify-center">
                                    {{ rand(1, 5) }}
                                </span>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div class="text-center text-gray-500 dark:text-zinc-500 mt-10">No chats yet.</div>
            @endforelse
        </div>
    </div>

    <!-- Right Sidebar: Chat Window -->
    <div class="w-2/3 flex flex-col bg-white/30 dark:bg-black/30 backdrop-blur-md relative transition-colors">
        @if($activeConversation)
            @php
                $isGroup = $activeConversation->is_group;
                $name = $isGroup ? $activeConversation->name : $activeConversation->users->where('id', '!=', auth()->id())->first()?->name;
                $otherUser = !$isGroup ? $activeConversation->users->where('id', '!=', auth()->id())->first() : null;
                $isOnline = !$isGroup && $otherUser ? in_array($otherUser->id, $onlineUsers) : false;
            @endphp
            <!-- Chat Header -->
            <div class="p-4 border-b border-gray-200 dark:border-zinc-800 bg-gray-50/50 dark:bg-zinc-950/50 flex items-center shadow-sm z-10 transition-colors">
                <div class="h-10 w-10 rounded-full bg-[#FF2D20] text-white flex items-center justify-center font-bold text-lg shadow-md">
                    {{ strtoupper(substr($name ?? 'G', 0, 1)) }}
                </div>
                <div class="ml-4">
                    <div class="font-semibold text-lg text-gray-800 dark:text-white">{{ $name }}</div>
                    @if($isOnline)
                        <div class="text-xs text-green-500 font-medium">Online</div>
                    @endif
                </div>
            </div>

            <!-- Messages Area -->
            <div class="flex-1 overflow-y-auto p-4 flex flex-col space-y-4 relative bg-gray-50/20 dark:bg-black/20 transition-colors" 
                 id="messages" 
                 x-data 
                 x-init="$watch('$wire.messages', () => { setTimeout(() => { $el.scrollTop = $el.scrollHeight }, 0) })"
                 @mousemove="(e) => { 
                    const rect = $el.getBoundingClientRect();
                    $el.style.setProperty('--x', (e.clientX - rect.left) + 'px'); 
                    $el.style.setProperty('--y', (e.clientY - rect.top) + 'px'); 
                 }">
                <div class="absolute inset-0 bg-[radial-gradient(circle_at_var(--x,50%)_var(--y,50%),rgba(255,45,32,0.08),transparent_50%)] pointer-events-none hidden dark:block"></div>
                
                @foreach($messages as $message)
                    @if($message['user_id'] === auth()->id())
                        <!-- My Message -->
                        <div class="flex items-end justify-end relative z-10">
                            <div class="bg-[#FF2D20] text-white p-3 rounded-2xl rounded-tr-none max-w-xs lg:max-w-md shadow-md">
                                {{ $message['body'] }}
                                <div class="text-xs text-red-100 mt-1 text-right opacity-80">
                                    {{ \Carbon\Carbon::parse($message['created_at'])->format('h:i A') }}
                                </div>
                            </div>
                        </div>
                    @else
                        <!-- Their Message -->
                        <div class="flex items-end justify-start relative z-10">
                            <div class="bg-white dark:bg-zinc-800 text-gray-800 dark:text-white p-3 rounded-2xl rounded-tl-none max-w-xs lg:max-w-md shadow-md border border-gray-200 dark:border-zinc-700 transition-colors">
                                @if($isGroup)
                                    <div class="text-xs font-semibold text-[#FF2D20] mb-1">{{ $message['user']['name'] }}</div>
                                @endif
                                {{ $message['body'] }}
                                <div class="text-xs text-gray-400 dark:text-zinc-400 mt-1 text-left">
                                    {{ \Carbon\Carbon::parse($message['created_at'])->format('h:i A') }}
                                </div>
                            </div>
                        </div>
                    @endif
                @endforeach
            </div>

            <!-- Input Area -->
            <div class="p-3 bg-gray-50/80 dark:bg-zinc-950/80 border-t border-gray-200 dark:border-zinc-800 z-10 transition-colors">
                <form wire:submit.prevent="sendMessage" class="flex items-center space-x-3">
                    <div class="flex space-x-1 text-gray-500 dark:text-zinc-400">
                        <button type="button" class="p-2 hover:text-[#FF2D20] transition-colors rounded-full hover:bg-gray-200 dark:hover:bg-zinc-800">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        </button>
                        <button type="button" class="p-2 hover:text-[#FF2D20] transition-colors rounded-full hover:bg-gray-200 dark:hover:bg-zinc-800">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                        </button>
                    </div>
                    
                    <input 
                        type="text" 
                        wire:model="newMessage" 
                        class="flex-1 rounded-xl border-none focus:ring-1 focus:ring-[#FF2D20] px-4 py-2.5 bg-white dark:bg-zinc-900 text-gray-800 dark:text-white placeholder-gray-400 dark:placeholder-zinc-500 transition-shadow shadow-sm"
                        placeholder="Type a message"
                        required
                    />
                    
                    @if($newMessage)
                        <button type="submit" class="p-2.5 bg-[#FF2D20] hover:bg-red-600 text-white rounded-full flex items-center justify-center transition-colors shadow-md min-w-[44px] min-h-[44px]">
                            <svg class="w-5 h-5 ml-1 transform -rotate-45" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path></svg>
                        </button>
                    @else
                        <button type="button" class="p-2.5 text-gray-500 dark:text-zinc-400 hover:text-[#FF2D20] transition-colors rounded-full hover:bg-gray-200 dark:hover:bg-zinc-800 min-w-[44px] min-h-[44px] flex items-center justify-center">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"></path></svg>
                        </button>
                    @endif
                </form>
            </div>
        @else
            <!-- Empty State -->
            <div class="flex-1 flex items-center justify-center flex-col relative bg-white dark:bg-black transition-colors"
                 @mousemove="(e) => { 
                    const rect = $el.getBoundingClientRect();
                    $el.style.setProperty('--x', (e.clientX - rect.left) + 'px'); 
                    $el.style.setProperty('--y', (e.clientY - rect.top) + 'px'); 
                 }">
                <div class="absolute inset-0 bg-[radial-gradient(circle_at_var(--x,50%)_var(--y,50%),rgba(255,45,32,0.12),transparent_70%)] pointer-events-none hidden dark:block"></div>
                <div class="bg-gray-100 dark:bg-zinc-800/50 p-6 rounded-full mb-6 ring-1 ring-gray-200 dark:ring-white/10 transition-colors">
                    <svg class="w-16 h-16 text-[#FF2D20]/80" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path></svg>
                </div>
                <h3 class="text-2xl font-semibold text-gray-800 dark:text-white mb-2">Welcome to SimpleChat</h3>
                <p class="text-gray-500 dark:text-zinc-400 text-center max-w-sm">Select a chat from the sidebar or start a new one to begin messaging.</p>
            </div>
        @endif
    </div>

    <!-- New Chat / Group Modal -->
    <div x-show="showGroupModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50" style="display: none;">
        <div class="bg-white dark:bg-zinc-900 rounded-lg shadow-xl w-full max-w-md mx-4 overflow-hidden border border-gray-200 dark:border-zinc-700">
            <div class="p-4 border-b border-gray-200 dark:border-zinc-800 flex justify-between items-center">
                <h2 class="text-lg font-semibold text-gray-800 dark:text-white">New Chat or Group</h2>
                <button @click="showGroupModal = false" class="text-gray-500 hover:text-gray-700 dark:hover:text-gray-300">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
            <div class="p-4">
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Group Name (Optional)</label>
                    <input type="text" wire:model="groupName" class="w-full rounded-md border-gray-300 dark:border-zinc-700 bg-white dark:bg-zinc-800 text-gray-800 dark:text-white focus:border-[#FF2D20] focus:ring-[#FF2D20] sm:text-sm" placeholder="Leave empty for 1-on-1 chat">
                </div>
                <div class="mb-4 max-h-60 overflow-y-auto">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Select Users</label>
                    @foreach($allUsers as $u)
                        <label class="flex items-center space-x-3 mb-2 p-2 rounded hover:bg-gray-50 dark:hover:bg-zinc-800 cursor-pointer">
                            <input type="checkbox" wire:model="selectedUsers" value="{{ $u->id }}" class="rounded text-[#FF2D20] focus:ring-[#FF2D20] dark:bg-zinc-700 dark:border-zinc-600">
                            <span class="text-gray-800 dark:text-white">{{ $u->name }}</span>
                        </label>
                    @endforeach
                </div>
                <div class="flex justify-end gap-2 mt-4">
                    <button @click="showGroupModal = false" class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-zinc-800 rounded-md hover:bg-gray-200 dark:hover:bg-zinc-700">Cancel</button>
                    <!-- Logic to dynamically call createGroup or createOrSelectPrivateChat based on selection -->
                    <div x-data="{
                        get selectedCount() {
                            return document.querySelectorAll('input[type=checkbox][wire\\\\:model=selectedUsers]:checked').length;
                        },
                        get hasGroupName() {
                            return document.querySelector('input[wire\\\\:model=groupName]').value.length > 0;
                        }
                    }">
                        <button wire:click="createGroup" x-show="selectedCount > 1 || hasGroupName" class="px-4 py-2 text-sm font-medium text-white bg-[#FF2D20] rounded-md hover:bg-red-600">Create Group</button>
                        <button wire:click="createOrSelectPrivateChat(@this.selectedUsers[0])" x-show="selectedCount === 1 && !hasGroupName" class="px-4 py-2 text-sm font-medium text-white bg-[#FF2D20] rounded-md hover:bg-red-600">Start Chat</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('livewire:initialized', () => {
        let currentChannel = null;

        window.Echo.join('chat.presence')
            .here((users) => {
                @this.call('updateOnlineUsers', users.map(u => u.id));
            })
            .joining((user) => {
                let currentOnline = @this.get('onlineUsers') || [];
                if (!currentOnline.includes(user.id)) {
                    currentOnline.push(user.id);
                    @this.call('updateOnlineUsers', currentOnline);
                }
            })
            .leaving((user) => {
                let currentOnline = @this.get('onlineUsers') || [];
                currentOnline = currentOnline.filter(id => id !== user.id);
                @this.call('updateOnlineUsers', currentOnline);
            });

        Livewire.hook('commit', ({ component, commit, respond, succeed, fail }) => {
            succeed(({ snapshot, effect }) => {
                const conversation = component.get('activeConversation');
                
                if (conversation && currentChannel !== 'chat.' + conversation.id) {
                    if (currentChannel) {
                        window.Echo.leave(currentChannel);
                    }
                    
                    currentChannel = 'chat.' + conversation.id;
                    window.Echo.private(currentChannel)
                        .listen('MessageSent', (e) => {
                            component.call('receiveMessage', e);
                        });
                }
            })
        })
    });
</script>
