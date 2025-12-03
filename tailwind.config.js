/** @type {import('tailwindcss').Config} */
import forms from '@tailwindcss/forms';

export default {
    darkMode: 'class', // Enable dark mode based on the 'dark' class
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.js',
    ],
    plugins: [forms],
};
