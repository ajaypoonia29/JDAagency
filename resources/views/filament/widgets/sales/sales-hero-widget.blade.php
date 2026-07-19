<x-filament-widgets::widget>

    <x-workspace.card>

        <div class="flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">

            <div>

                <h2 class="text-3xl font-bold tracking-tight">
                    👋 Good Morning, {{ auth()->user()->name }}
                </h2>

                <p class="mt-2 text-gray-600 dark:text-gray-400">
                    Welcome back to AgencyOS.
                    Let's close some deals today.
                </p>

            </div>

            <div class="text-left lg:text-right">

                <div class="text-sm text-gray-500">
                    Today
                </div>

                <div class="text-xl font-semibold">
                    {{ now()->format('l') }}
                </div>

                <div class="text-gray-500">
                    {{ now()->format('d M Y') }}
                </div>

            </div>

        </div>

    </x-workspace.card>

</x-filament-widgets::widget>