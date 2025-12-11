@props([
    'href' => '#',
    'icon' => 'plus',
    'label' => 'Create new report',
])

<a
    href="{{ $href }}"
    {{ $attributes->class([
        'fixed bottom-6 right-6 z-50',
        'flex size-14 items-center justify-center',
        'rounded-full shadow-lg',
        'bg-zinc-900 text-white hover:bg-zinc-800',
        'dark:bg-zinc-100 dark:text-zinc-900 dark:hover:bg-zinc-200',
        'transition-colors duration-200',
        'focus:outline-none focus:ring-2 focus:ring-zinc-500 focus:ring-offset-2',
        'dark:focus:ring-zinc-400 dark:focus:ring-offset-zinc-900',
        'sm:bottom-8 sm:right-8',
    ]) }}
    aria-label="{{ $label }}"
>
    <flux:icon :name="$icon" class="size-6" />
</a>
