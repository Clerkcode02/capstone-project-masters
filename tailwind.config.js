import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
                board: ['"Big Shoulders"', ...defaultTheme.fontFamily.sans],
                slip: ['"JetBrains Mono"', ...defaultTheme.fontFamily.mono],
            },
            colors: {
                board: {
                    ink: '#12151a',
                    panel: '#1a1e25',
                    flap: '#20252e',
                    line: '#333a46',
                    text: '#f4f2ec',
                    muted: '#9aa1af',
                    amber: '#e8a53d',
                    'amber-ink': '#3a2a0d',
                    red: '#e8756a',
                    'red-ink': '#3a1512',
                    lit: '#f4f2ec',
                },
                slip: {
                    paper: '#f4ecdd',
                    ink: '#372f24',
                    rule: '#d9cbaf',
                },
            },
        },
    },

    plugins: [forms],
};
