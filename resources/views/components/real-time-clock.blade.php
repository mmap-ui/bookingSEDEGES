@props(['class' => ''])

<div
    x-data="realtimeClock(@js(\Carbon\Carbon::now()->toIso8601String()))"
    {{ $attributes->merge(['class' => 'flex flex-col items-end leading-tight '.$class]) }}
>
    <span x-text="time" class="text-sm font-bold tabular-nums text-gray-700 dark:text-gray-200"></span>
    <span x-text="date" class="text-xs capitalize text-gray-400 dark:text-gray-500"></span>
</div>