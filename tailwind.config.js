import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
    './app/Filament/**/*.php',

    './app/Livewire/**/*.php',

    './resources/**/*.blade.php',

    './vendor/filament/**/*.blade.php',

    './vendor/filament/**/*.php',

    './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',

    './storage/framework/views/*.php',
],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
        },
    },

    plugins: [forms],
};
