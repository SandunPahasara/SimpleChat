<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <script>
            document.addEventListener('alpine:init', () => {
                Alpine.store('theme', {
                    darkMode: localStorage.getItem('darkMode') === 'true',
                    toggle() {
                        this.darkMode = !this.darkMode;
                        localStorage.setItem('darkMode', this.darkMode);
                        this.updateClass();
                    },
                    updateClass() {
                        if (this.darkMode) {
                            document.documentElement.classList.add('dark');
                        } else {
                            document.documentElement.classList.remove('dark');
                        }
                    }
                });
                Alpine.store('theme').updateClass();
            });
        </script>
    </head>
    <body class="font-sans antialiased text-gray-900 dark:text-gray-100 selection:bg-[#FF2D20] selection:text-white">
        <div class="min-h-screen bg-gray-50 dark:bg-zinc-950 transition-colors duration-500 relative overflow-hidden">
            <!-- Background Dot Pattern -->
            <div class="absolute inset-0 z-0 opacity-[0.03] dark:opacity-[0.05] pointer-events-none" style="background-image: radial-gradient(#FF2D20 1px, transparent 1px); background-size: 30px 30px;"></div>

            <!-- Vibrant Red Glow -->
            <div id="dashboard-glow" class="fixed pointer-events-none w-[1200px] h-[1200px] bg-[radial-gradient(circle,rgba(255,45,32,0.3)_0%,rgba(255,45,32,0.1)_45%,transparent_75%)] z-0 -translate-x-1/2 -translate-y-1/2 opacity-0 transition-opacity duration-1000 blur-3xl" style="left: var(--x, 50%); top: var(--y, 50%);"></div>

            <div class="relative z-10 flex flex-col min-h-screen">
                <livewire:layout.navigation />

                <!-- Page Heading -->
                @if (isset($header))
                    <header class="bg-white/80 dark:bg-zinc-900/80 backdrop-blur-md shadow-sm border-b border-gray-200 dark:border-zinc-800 transition-colors">
                        <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                            {{ $header }}
                        </div>
                    </header>
                @endif

                <!-- Page Content -->
                <main class="flex-1">
                    {{ $slot }}
                </main>
            </div>
        </div>

        <script>
            (function() {
                const glow = document.getElementById('dashboard-glow');
                if (!glow) return;

                window.addEventListener('mousemove', (e) => {
                    glow.style.opacity = '1';
                    glow.style.setProperty('--x', e.clientX + 'px');
                    glow.style.setProperty('--y', e.clientY + 'px');
                });
            })();
        </script>
    </body>
</html>
