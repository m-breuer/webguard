@props(['head', 'body', 'class' => ''])

@php
    $classes = 'w-full text-left text-gray-500 dark:text-gray-300 ' . $class;
@endphp

<div
    {{ $attributes->merge(['class' => 'overflow-hidden rounded-md bg-white shadow-md dark:bg-gray-800', 'class' => $classes]) }}>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-700">
                <tr>
                    {{ $head }}
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-800">
                {{ $body }}
            </tbody>
        </table>
    </div>
</div>
