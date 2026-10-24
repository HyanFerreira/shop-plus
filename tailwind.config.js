import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';
import typography from '@tailwindcss/typography';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './vendor/laravel/jetstream/**/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    // Blade badge/alert variants are assembled dynamically; retain every public variant.
    safelist: [
        'ds-badge--neutral', 'ds-badge--info', 'ds-badge--success', 'ds-badge--danger', 'ds-badge--warning',
        'ds-alert--neutral', 'ds-alert--info', 'ds-alert--success', 'ds-alert--danger', 'ds-alert--warning',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
        },
    },

    plugins: [forms, typography],
};
