@props(['disabled' => false])

<textarea @disabled($disabled)
    {{ $attributes->merge(['class' => 'form-textarea border-gray-300 focus:border-purple-500 focus:ring-purple-500 rounded-md shadow-xs mt-1 w-full dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100']) }}>
</textarea>
