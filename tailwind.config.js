import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: 'class',
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './Modules/**/*.blade.php',
        './app/**/*.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['-apple-system', 'BlinkMacSystemFont', '"SF Pro Display"', '"SF Pro Text"', 'Inter', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                canvas: 'var(--bg-canvas)',
                surface: 'var(--bg-surface)',
                elevated: 'var(--bg-elevated)',
                'border-hairline': 'var(--border-hairline)',
                'apple-blue': 'var(--accent-blue)',
                'apple-green': 'var(--accent-green)',
                'apple-orange': 'var(--accent-orange)',
                'apple-red': 'var(--accent-red)',
                'apple-purple': 'var(--accent-purple)',
            },
            borderRadius: {
                'apple': '16px',
                'apple-lg': '20px',
            },
            boxShadow: {
                'apple-card': 'var(--card-shadow)',
                'whisper': '0 1px 3px rgba(0, 0, 0, 0.03), 0 4px 12px rgba(0, 0, 0, 0.02)',
            },
        },
    },

    plugins: [forms],
};
