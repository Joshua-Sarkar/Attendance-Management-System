import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: 'class',
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            colors: {
                // Design Reference Mockup Tokens
                'canvas': '#FAF8F5',          /* Warm Ivory: canvas background */
                'surface': '#F4EFE6',         /* Soft Cream: panels/containers */
                'surface-raised': '#FBF9F6',  /* Parchment: input backgrounds */
                'hairline': 'rgba(156, 124, 56, 0.16)',
                'hairline-strong': 'rgba(156, 124, 56, 0.28)',
                'vellum': '#24211E',          /* Dark Charcoal: primary body */
                'vellum-muted': '#5E5852',    /* Muted Charcoal: subtext */
                'vellum-faint': '#8A8177',    /* Faint Charcoal: labels/dates */
                'vellum-light': '#ECE4D3',    /* Vellum Light: sidebar text */
                'vellum-light-muted': '#9C9180', /* Vellum Muted: sidebar copy */
                'vellum-light-faint': '#6B6457', /* Vellum Faint: sidebar labels */
                'brass': '#9C7C38',           /* Aged Brass: accents */
                'brass-dim': 'rgba(156, 124, 56, 0.55)',
                'brass-bright': '#C9A24B',
                'emerald': '#234E39',
                'emerald-bg': '#E3ECE7',
                'oxblood': '#6E1A24',
                'oxblood-bg': '#F3E6E8',
                'forest': '#234E39',
                'forest-bg': '#E3ECE7',
                'forest-light': '#234E39',
                'burgundy': '#6E1A24',
                'burgundy-bg': '#F3E6E8',
                'burgundy-light': '#6E1A24',
                'cognac': '#8C4E2D',
                'cognac-bg': '#F6ECE6',
                'cognac-light': '#8C4E2D',
                'walnut': '#1E1611',
                'walnut-bg': 'rgba(30, 22, 17, 0.12)',
                'slate': '#3B5368',
                'slate-bg': '#E7EDF1',
                'slate-light': '#3B5368',

                // Primary accents
                'primary': '#ff2d78',
                'primary-fixed': '#ffe0ec',
                'primary-fixed-dim': '#ff80aa',
                'on-primary': '#1a0010',
                'on-primary-fixed': '#3d0020',
                'on-primary-fixed-variant': '#8c0038',
                'primary-container': '#b3004e',
                'on-primary-container': '#ffe0ec',

                // Secondary accents
                'secondary': '#00ffcc',
                'secondary-fixed': '#c0fff4',
                'secondary-fixed-dim': '#00e6b8',
                'on-secondary': '#001a1a',
                'on-secondary-fixed': '#001a1a',
                'on-secondary-fixed-variant': '#004d4d',
                'secondary-container': '#004d3d',
                'on-secondary-container': '#c0fff4',

                // Tertiary accents
                'tertiary': '#ffe04a',
                'tertiary-fixed': '#fff0c0',
                'tertiary-fixed-dim': '#ffe04a',
                'on-tertiary': '#1a1000',
                'on-tertiary-fixed': '#1a1000',
                'on-tertiary-fixed-variant': '#665200',
                'tertiary-container': '#665200',
                'on-tertiary-container': '#fff0c0',

                // Error states
                'error': '#ff4444',
                'on-error': '#1a0000',
                'error-container': '#3d0f0f',
                'on-error-container': '#ffa0a0',

                // Surface layers
                // 'surface': '#0f0f1a', // commented to avoid conflict with design reference
                'surface-dim': '#0f0f1a',
                'surface-bright': '#1a1a2e',
                'surface-container-lowest': '#0a0a12',
                'surface-container-low': '#111118',
                'surface-container': '#141422',
                'surface-container-high': '#1e1e30',
                'surface-container-highest': '#28283e',
                'surface-tint': '#ff2d78',

                // Background & variants
                'background': '#0a0a12',
                'on-background': '#e8e0f0',
                'on-surface': '#e8e0f0',
                'on-surface-variant': '#a098b0',

                // Inverse colors
                'inverse-surface': '#e8e0f0',
                'inverse-on-surface': '#0a0a12',
                'inverse-primary': '#8c0038',

                // Outline
                'outline': '#5a5068',
                'outline-variant': '#302840',
            },
            fontFamily: {
                display: ['Fraunces', 'serif'],
                sans: ['IBM Plex Sans', ...defaultTheme.fontFamily.sans],
                mono: ['IBM Plex Mono', ...defaultTheme.fontFamily.mono],
            },
            spacing: {
                'unit': '4px',
                'stack-sm': '8px',
                'stack-md': '16px',
                'stack-lg': '32px',
                'gutter': '24px',
                'container-padding-mobile': '20px',
                'container-padding-desktop': '40px',
                'section-gap': '64px',
            },
            borderRadius: {
                'xs': '0.125rem',
                'sm': '0.25rem',
                'md': '0.5rem',
                'lg': '0.75rem',
            },
            backdropBlur: {
                'xl': '16px',
            },
            boxShadow: {
                'glass': '0 8px 32px rgba(0, 0, 0, 0.4), 0 0 20px rgba(0, 255, 204, 0.05)',
                'luminous': 'inset 0 0 12px rgba(255, 45, 120, 0.1)',
            },
        },
    },

    plugins: [forms],
};
