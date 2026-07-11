@props([
    'href',
    'icon',
    'title',
    'description',
])

<a
    href="{{ $href }}"
    class="group block h-full rounded-2xl border border-gray-200 bg-white p-6 shadow-sm transition-all duration-200 hover:-translate-y-1 hover:border-primary-500 hover:shadow-xl dark:border-gray-700 dark:bg-gray-900"
>

    <div class="flex h-14 w-14 items-center justify-center rounded-xl bg-primary-100 text-3xl">

        {{ $icon }}

    </div>

    <h3 class="mt-5 text-lg font-semibold">

        {{ $title }}

    </h3>

    <p class="mt-2 text-sm text-gray-500">

        {{ $description }}

    </p>

</a>