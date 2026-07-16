<x-filament-widgets::widget>
    <x-filament::section
        heading="Homepage readiness"
        description="Featured content that feeds /api/home. Fix warnings before publishing the storefront."
    >
        <div class="grid gap-4 md:grid-cols-4">
            @foreach ($sections as $section)
                <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-sm font-medium text-gray-950 dark:text-white">{{ $section['label'] }}</p>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                {{ $section['count'] }} / {{ $section['target'] }} ready
                            </p>
                        </div>
                        <span @class([
                            'rounded-full px-2 py-1 text-xs font-medium',
                            'bg-success-50 text-success-700 dark:bg-success-500/10 dark:text-success-400' => $section['status'] === 'ready',
                            'bg-warning-50 text-warning-700 dark:bg-warning-500/10 dark:text-warning-400' => $section['status'] !== 'ready',
                        ])>
                            {{ $section['status'] === 'ready' ? 'Ready' : 'Needs work' }}
                        </span>
                    </div>

                    @if ($section['issues'])
                        <ul class="mt-4 space-y-2 text-xs leading-5 text-gray-600 dark:text-gray-300">
                            @foreach ($section['issues'] as $issue)
                                <li class="flex gap-2">
                                    <span class="mt-2 h-1.5 w-1.5 shrink-0 rounded-full bg-warning-500"></span>
                                    <span>{{ $issue }}</span>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="mt-4 text-xs leading-5 text-gray-600 dark:text-gray-300">
                            Featured count and images look ready for the homepage.
                        </p>
                    @endif
                </div>
            @endforeach
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
