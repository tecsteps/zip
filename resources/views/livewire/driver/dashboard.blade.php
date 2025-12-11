<div>
    <flux:heading size="lg" class="mb-6">My Reports</flux:heading>

    @if ($this->hasReports)
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($this->reports as $report)
                <div wire:key="report-{{ $report->id }}">
                    @include('livewire.driver.partials.report-card', ['report' => $report])
                </div>
            @endforeach
        </div>
    @else
        <div class="flex flex-col items-center justify-center rounded-xl border border-dashed border-zinc-300 py-16 dark:border-zinc-700">
            <div class="mb-4 flex size-16 items-center justify-center rounded-full bg-zinc-100 dark:bg-zinc-800">
                <flux:icon name="document-text" class="size-8 text-zinc-400 dark:text-zinc-500" />
            </div>
            <flux:heading size="lg" class="mb-2">No damage reports yet</flux:heading>
            <flux:text class="mb-6 text-zinc-500 dark:text-zinc-400">
                Create your first damage report to get started.
            </flux:text>
            <flux:button href="{{ route('driver.reports.create') }}" variant="primary" icon="plus">
                Create Report
            </flux:button>
        </div>
    @endif

    <x-fab href="{{ route('driver.reports.create') }}" />

    <flux:modal name="confirm-delete" class="max-w-md">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Delete Report</flux:heading>
                <flux:text class="mt-2 text-zinc-500 dark:text-zinc-400">
                    Are you sure you want to delete this report? This action cannot be undone.
                </flux:text>
            </div>

            <div class="flex justify-end gap-3">
                <flux:modal.close>
                    <flux:button variant="filled">Cancel</flux:button>
                </flux:modal.close>
                <flux:button
                    variant="danger"
                    x-on:click="$wire.delete($wire.reportToDelete); $flux.modal('confirm-delete').close()"
                >
                    Delete
                </flux:button>
            </div>
        </div>
    </flux:modal>
</div>
