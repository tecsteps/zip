<div class="mx-auto max-w-2xl">
    {{-- Page Header --}}
    <div class="mb-6 flex items-center gap-4">
        <flux:button href="{{ route('dashboard') }}" variant="ghost" icon="arrow-left" size="sm">
            Back
        </flux:button>
        <flux:heading size="lg">Create Damage Report</flux:heading>
    </div>

    <form wire:submit.prevent="submit" class="space-y-6">
        {{-- Photo Upload Zone --}}
        <div>
            <flux:label class="mb-2">Photo</flux:label>

            @if ($photo && $photo->isPreviewable())
                {{-- Photo Preview --}}
                <div class="relative overflow-hidden rounded-xl border border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-800">
                    <img
                        src="{{ $photo->temporaryUrl() }}"
                        alt="Uploaded damage photo preview"
                        class="h-64 w-full object-cover"
                    />
                    <button
                        type="button"
                        wire:click="$set('photo', null)"
                        class="absolute right-3 top-3 flex size-8 items-center justify-center rounded-full bg-zinc-900/70 text-white transition-colors hover:bg-zinc-900/90 dark:bg-zinc-100/70 dark:text-zinc-900 dark:hover:bg-zinc-100/90"
                        aria-label="Remove photo"
                    >
                        <flux:icon name="x-mark" class="size-5" />
                    </button>
                </div>
            @elseif ($photo && !$photo->isPreviewable())
                {{-- Non-previewable file uploaded --}}
                <div class="relative rounded-xl border border-zinc-200 bg-zinc-50 p-6 dark:border-zinc-700 dark:bg-zinc-800">
                    <div class="flex items-center gap-3">
                        <flux:icon name="document" class="size-8 text-zinc-500" />
                        <div>
                            <flux:text class="font-medium">{{ $photo->getClientOriginalName() }}</flux:text>
                            <flux:text class="text-sm text-zinc-500">File uploaded (not previewable)</flux:text>
                        </div>
                    </div>
                    <button
                        type="button"
                        wire:click="$set('photo', null)"
                        class="absolute right-3 top-3 flex size-8 items-center justify-center rounded-full bg-zinc-200 text-zinc-600 transition-colors hover:bg-zinc-300 dark:bg-zinc-700 dark:text-zinc-400 dark:hover:bg-zinc-600"
                        aria-label="Remove file"
                    >
                        <flux:icon name="x-mark" class="size-5" />
                    </button>
                </div>
            @else
                {{-- Upload Zone --}}
                <div
                    x-data="{ uploading: false, progress: 0 }"
                    x-on:livewire-upload-start="uploading = true"
                    x-on:livewire-upload-finish="uploading = false; progress = 0"
                    x-on:livewire-upload-cancel="uploading = false; progress = 0"
                    x-on:livewire-upload-error="uploading = false; progress = 0"
                    x-on:livewire-upload-progress="progress = $event.detail.progress"
                    class="relative"
                >
                    <label
                        for="photo-upload"
                        class="flex cursor-pointer flex-col items-center justify-center rounded-xl border-2 border-dashed border-zinc-300 bg-zinc-50 py-12 transition-colors hover:border-zinc-400 hover:bg-zinc-100 dark:border-zinc-600 dark:bg-zinc-800/50 dark:hover:border-zinc-500 dark:hover:bg-zinc-800"
                    >
                        <div class="mb-4 flex size-14 items-center justify-center rounded-full bg-zinc-200 dark:bg-zinc-700">
                            <flux:icon name="camera" class="size-7 text-zinc-500 dark:text-zinc-400" />
                        </div>
                        <flux:text class="mb-1 font-medium text-zinc-700 dark:text-zinc-300">
                            Click to upload or drag and drop
                        </flux:text>
                        <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">
                            JPG, PNG, WebP (max 5MB)
                        </flux:text>

                        <input
                            id="photo-upload"
                            type="file"
                            wire:model="photo"
                            accept="image/jpeg,image/png,image/webp"
                            class="sr-only"
                            aria-describedby="photo-error"
                        />
                    </label>

                    {{-- Upload Progress --}}
                    <div
                        x-show="uploading"
                        x-cloak
                        class="absolute inset-0 flex flex-col items-center justify-center rounded-xl bg-white/90 dark:bg-zinc-900/90"
                    >
                        <div class="mb-3 size-10 animate-spin rounded-full border-4 border-zinc-300 border-t-zinc-900 dark:border-zinc-600 dark:border-t-zinc-100"></div>
                        <flux:text class="font-medium text-zinc-700 dark:text-zinc-300">
                            Uploading...
                        </flux:text>
                        <flux:text class="text-sm text-zinc-500 dark:text-zinc-400" x-text="progress + '%'"></flux:text>
                    </div>
                </div>
            @endif

            {{-- Photo Error --}}
            <flux:error name="photo" id="photo-error" class="mt-2" />
        </div>

        {{-- Package ID --}}
        <flux:input
            wire:model="package_id"
            label="Package ID"
            placeholder="e.g., PKG-12345"
            required
        />

        {{-- Location --}}
        <flux:input
            wire:model="location"
            label="Location"
            placeholder="e.g., 123 Main St, City"
            required
        />

        {{-- Description --}}
        <flux:textarea
            wire:model="description"
            label="Description"
            description="Optional - describe the damage in detail"
            placeholder="Describe the damage..."
            rows="4"
            resize="vertical"
        />

        {{-- Action Buttons --}}
        <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-between">
            <flux:button
                type="button"
                wire:click="saveDraft"
                wire:loading.attr="disabled"
                wire:target="saveDraft,submit"
                variant="filled"
                class="w-full sm:w-auto"
            >
                <span wire:loading.remove wire:target="saveDraft">Save Draft</span>
                <span wire:loading wire:target="saveDraft">Saving...</span>
            </flux:button>

            <flux:button
                type="submit"
                wire:loading.attr="disabled"
                wire:target="saveDraft,submit"
                variant="primary"
                class="w-full sm:w-auto"
            >
                <span wire:loading.remove wire:target="submit">Submit Report</span>
                <span wire:loading wire:target="submit">Submitting...</span>
            </flux:button>
        </div>
    </form>
</div>
