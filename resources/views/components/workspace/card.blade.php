@props([
    'heading' => null,
    'description' => null,
])

<x-filament::section>

    @if ($heading || $description)
        <div class="mb-6">

            @if ($heading)
                <h3 class="text-lg font-semibold tracking-tight">
                    {{ $heading }}
                </h3>
            @endif

            @if ($description)
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    {{ $description }}
                </p>
            @endif

        </div>
    @endif

    {{ $slot }}

</x-filament::section>