<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 mt-6">
        <div class="bg-white dark:bg-black overflow-hidden shadow-sm dark:shadow-2xl sm:rounded-lg border border-gray-200 dark:border-zinc-800 ring-1 ring-black/5 dark:ring-white/10 transition-colors">
            @livewire('chat')
        </div>
    </div>
</x-app-layout>
