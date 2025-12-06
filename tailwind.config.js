import defaultTheme from 'tailwindcss/defaultTheme';

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: ['class'],
    content: [
    './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
    './storage/framework/views/*.php',
    './resources/views/**/*.blade.php',
    './resources/js/**/*.{vue,js,ts,jsx,tsx}',
    ],
    theme: {
        extend: {
            fontFamily: {
                sans: ['Instrument Sans', ...defaultTheme.fontFamily.sans],
            },
            borderRadius: {
                lg: 'var(--radius)',
                md: 'calc(var(--radius) - 2px)',
                sm: 'calc(var(--radius) - 4px)',
            },
            colors: {
                /* nuevos tokens */
                bg:      'rgb(var(--bg) / <alpha-value>)',
                fg:      'rgb(var(--fg) / <alpha-value>)',
                muted:   'rgb(var(--muted) / <alpha-value>)',
                card:    'rgb(var(--card) / <alpha-value>)',
                border:  'rgb(var(--border) / <alpha-value>)',
                primary: 'rgb(var(--primary) / <alpha-value>)',
                'primary-fg': 'rgb(var(--primary-fg) / <alpha-value>)',
                accent:  'rgb(var(--accent) / <alpha-value>)',
                success: 'rgb(var(--success) / <alpha-value>)',
                warning: 'rgb(var(--warning) / <alpha-value>)',
                danger:  'rgb(var(--danger) / <alpha-value>)',
                focus:   'rgb(var(--focus) / <alpha-value>)',

                /* alias para las clases que ya existen en tu código */
                background: 'rgb(var(--bg) / <alpha-value>)',
                foreground: 'rgb(var(--fg) / <alpha-value>)',
                mutedfg:    'rgb(var(--muted) / <alpha-value>)',

                sidebar: {
                    DEFAULT: 'hsl(var(--sidebar-background))',
                    foreground: 'hsl(var(--sidebar-foreground))',
                    primary: 'hsl(var(--sidebar-primary))',
                    'primary-foreground': 'hsl(var(--sidebar-primary-foreground))',
                    accent: 'hsl(var(--sidebar-accent))',
                    'accent-foreground': 'hsl(var(--sidebar-accent-foreground))',
                    border: 'hsl(var(--sidebar-border))',
                    ring: 'hsl(var(--sidebar-ring))',
                    },
                },
            borderColor: {
                DEFAULT: 'rgb(var(--border) / 1)',
            },
            textColor: {
                skin: {
                    base: 'rgb(var(--fg))',
                    muted: 'rgb(var(--muted))',
                },
            },
            backgroundColor: {
                skin: {
                    base: 'rgb(var(--bg))',
                    card: 'rgb(var(--card))',
                    },
                },
            },
    },
    plugins: [require('tailwindcss-animate')],
};