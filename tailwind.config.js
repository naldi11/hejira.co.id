import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.{js,jsx}',
    ],

    // Safelist untuk dynamic Blade class seperti bg-{{ $accentColor }}-600
    safelist: [
        { pattern: /^bg-(orange|amber|indigo)-(50|100|200|300|400|500|600|700|800|900)$/ },
        { pattern: /^text-(orange|amber|indigo)-(50|100|200|300|400|500|600|700|800|900)$/ },
        { pattern: /^border-(orange|amber|indigo)-(50|100|200|300|400|500|600|700|800|900)$/ },
        { pattern: /^ring-(orange|amber|indigo)-(50|100|200|300|400|500|600|700|800|900)$/ },
        { pattern: /^shadow-(orange|amber|indigo)-(50|100|200|300|400|500|600|700|800|900)$/ },
        { pattern: /^hover:bg-(orange|amber|indigo)-(50|100|200|300|400|500|600|700|800|900)$/ },
        { pattern: /^hover:text-(orange|amber|indigo)-(50|100|200|300|400|500|600|700|800|900)$/ },
        { pattern: /^hover:border-(orange|amber|indigo)-(50|100|200|300|400|500|600|700|800|900)$/ },
        { pattern: /^focus:border-(orange|amber|indigo)-(50|100|200|300|400|500|600|700|800|900)$/ },
        { pattern: /^focus:ring-(orange|amber|indigo)-(50|100|200|300|400|500|600|700|800|900)$/ },
        { pattern: /^group-hover:bg-(orange|amber|indigo)-(50|100|200|300|400|500|600|700|800|900)$/ },
        { pattern: /^group-hover:text-(orange|amber|indigo)-(50|100|200|300|400|500|600|700|800|900)$/ },
    ],

    theme: {
        extend: {
            colors: {
                primary: '#4f46e5',
            },
            fontFamily: {
                sans: ['Poppins', ...defaultTheme.fontFamily.sans],
                headline: ['Poppins', ...defaultTheme.fontFamily.sans],
            },
            fontWeight: {
                semibold: '500',
                bold: '500',
                extrabold: '500',
                black: '500',
            },
            // Shimmer effect for skeleton loaders (a light gradient sweeping across)
            keyframes: {
                shimmer: {
                    '100%': { transform: 'translateX(100%)' },
                },
            },
            animation: {
                shimmer: 'shimmer 1.5s infinite',
            },
        },
    },

    plugins: [forms],
};
