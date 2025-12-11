<x-layouts.app :title="__('Dashboard')">
    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
        <div class="mb-4">
            <flux:heading size="xl">Welcome, {{ auth()->user()->name }}!</flux:heading>
            <flux:text class="text-zinc-500 dark:text-zinc-400">
                Role: {{ auth()->user()->role->value }}
            </flux:text>
        </div>

        @if(auth()->user()->isDriver())
            <livewire:driver.dashboard />
        @else
            {{-- Supervisor Dashboard Content --}}
            <div class="rounded-xl border border-neutral-200 p-6 dark:border-neutral-700">
                <flux:heading size="lg" class="mb-4">All Reports</flux:heading>
                <flux:text class="text-zinc-500 dark:text-zinc-400">
                    All damage reports from drivers will appear here.
                </flux:text>
            </div>
        @endif
    </div>
</x-layouts.app>
