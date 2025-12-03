@props(['disabled' => false, 'multiple' => false])

<select @disabled($disabled)
    {{ $attributes->merge(['class' => 'border-gray-300 focus:border-purple-500 focus:ring-purple-500 rounded-md shadow-xs dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100']) }}>
    {{ $slot }}
</select>
