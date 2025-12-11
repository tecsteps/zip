<div class="mx-auto max-w-2xl">
    {{-- Page Header --}}
    <div class="mb-6 flex items-center gap-4">
        <flux:button href="{{ route('dashboard') }}" variant="ghost" icon="arrow-left" size="sm">
            Back
        </flux:button>
        <flux:heading size="lg">Edit Damage Report</flux:heading>
    </div>

    {{-- Placeholder for Phase 5 implementation --}}
    <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
        <flux:text class="text-zinc-500 dark:text-zinc-400">
            Editing report: {{ $report->package_id }}
        </flux:text>
    </div>
</div>
