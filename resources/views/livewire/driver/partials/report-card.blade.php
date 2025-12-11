@php
    use App\Enums\ReportStatus;
    use Illuminate\Support\Facades\Storage;

    $badgeColor = match($report->status) {
        ReportStatus::Draft => 'zinc',
        ReportStatus::Submitted => 'amber',
        ReportStatus::Approved => 'green',
    };

    $statusLabel = match($report->status) {
        ReportStatus::Draft => 'Draft',
        ReportStatus::Submitted => 'Submitted',
        ReportStatus::Approved => 'Approved',
    };

    $severityColor = match($report->ai_severity) {
        'low' => 'sky',
        'moderate' => 'amber',
        'high' => 'orange',
        'critical' => 'red',
        default => 'zinc',
    };
@endphp

<div class="group relative flex flex-col overflow-hidden rounded-xl border border-zinc-200 bg-white transition-shadow hover:shadow-md dark:border-zinc-700 dark:bg-zinc-900">
    <a href="#" class="absolute inset-0 z-10" aria-label="View report {{ $report->package_id }}"></a>

    <div class="flex gap-4 p-4">
        {{-- Photo Thumbnail --}}
        <div class="size-20 shrink-0 overflow-hidden rounded-lg bg-zinc-100 dark:bg-zinc-800">
            @if ($report->photo_path)
                <img
                    src="{{ Storage::url($report->photo_path) }}"
                    alt="Damage photo for {{ $report->package_id }}"
                    class="size-full object-cover"
                />
            @else
                <div class="flex size-full items-center justify-center">
                    <flux:icon name="photo" class="size-8 text-zinc-400 dark:text-zinc-500" />
                </div>
            @endif
        </div>

        {{-- Report Details --}}
        <div class="flex min-w-0 flex-1 flex-col">
            <div class="mb-1 flex items-start justify-between gap-2">
                <flux:heading size="sm" class="truncate">{{ $report->package_id }}</flux:heading>
                <div class="flex shrink-0 gap-1">
                    <flux:badge size="sm" :color="$badgeColor">{{ $statusLabel }}</flux:badge>
                    @if ($report->ai_severity)
                        <flux:badge size="sm" :color="$severityColor">{{ ucfirst($report->ai_severity) }}</flux:badge>
                    @endif
                </div>
            </div>

            <flux:text class="mb-1 truncate text-sm text-zinc-500 dark:text-zinc-400">
                {{ $report->location }}
            </flux:text>

            <flux:text class="text-sm text-zinc-400 dark:text-zinc-500">
                {{ $report->created_at->format('M j, Y') }}
            </flux:text>
        </div>
    </div>

    {{-- Action Buttons (Draft only) --}}
    @if ($report->status === ReportStatus::Draft)
        <div class="relative z-20 flex border-t border-zinc-200 dark:border-zinc-700">
            <flux:button
                href="{{ route('driver.reports.edit', $report) }}"
                variant="ghost"
                size="sm"
                icon="pencil"
                class="flex-1 rounded-none border-r border-zinc-200 dark:border-zinc-700"
            >
                Edit
            </flux:button>
            <flux:button
                wire:click="submit({{ $report->id }})"
                variant="ghost"
                size="sm"
                icon="paper-airplane"
                class="flex-1 rounded-none border-r border-zinc-200 dark:border-zinc-700"
            >
                Submit
            </flux:button>
            <flux:button
                variant="ghost"
                size="sm"
                icon="trash"
                class="flex-1 rounded-none text-red-600 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300"
                x-data="{ reportId: {{ $report->id }} }"
                x-on:click="$wire.reportToDelete = reportId; $flux.modal('confirm-delete').show()"
            >
                Delete
            </flux:button>
        </div>
    @endif
</div>
