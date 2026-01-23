import defaultTheme from 'tailwindcss/defaultTheme';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/**/*.blade.php',
        './resources/**/*.js',
        './resources/**/*.vue',
    ],
    theme: {
        extend: {
            colors: {
                primary: '#2563EB',
                tertiary: '#16A34A',
                secondary: '#6B7280',
                error: '#EF4444',
                ink: '#0F172A',
                muted: '#64748B',
                border: '#E4E7EC',
                surface: '#FFFFFF',
                'background-light': '#F6F7FB',
                'background-dark': '#0B1220',
            },
            fontFamily: {
                sans: ['IBM Plex Sans', ...defaultTheme.fontFamily.sans],
                display: ['Sora', ...defaultTheme.fontFamily.sans],
            },
            fontSize: {
                xs: ['0.75rem', { lineHeight: '1.1rem' }],
                sm: ['0.875rem', { lineHeight: '1.25rem' }],
                base: ['1rem', { lineHeight: '1.5rem' }],
                lg: ['1.125rem', { lineHeight: '1.6rem' }],
                xl: ['1.25rem', { lineHeight: '1.7rem' }],
                '2xl': ['1.5rem', { lineHeight: '2rem' }],
            },
        },
    },
    plugins: [],
};
