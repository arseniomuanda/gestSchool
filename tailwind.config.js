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
                sans: ['Nunito', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                // 3 cores brand alinhadas com a welcome page (paleta "brochura escolar"):
                //   · primary  #0f4d3a (teal)   — CTAs, links, brand
                //   · accent   #e85d4e (coral)  — atrasos, realces
                //   · navy     #001737          — texto, surfaces escuras
                primary: {
                    DEFAULT: '#0f4d3a',
                    soft: '#dfe8e3',
                    600: '#0a3a2c',
                },
                success: {
                    DEFAULT: '#44ce42',
                    soft: '#e3f8e3',
                },
                danger: {
                    DEFAULT: '#fc5a5a',
                    soft: '#feebeb',
                },
                warning: {
                    DEFAULT: '#ffc542',
                    soft: '#fff5d5',
                },
                // info passa a ser alias visual de primary (era roxo #a461d8) —
                // badges/alerts "info" continuam a existir como conceito, mas
                // renderizam em primary-soft, sem introduzir 4ª cor.
                info: {
                    DEFAULT: '#0f4d3a',
                    soft: '#dfe8e3',
                },
                accent: '#e85d4e',
                navy: '#001737',
                body: '#a7afb7',
                muted: '#76838f',
                sidebar: {
                    DEFAULT: '#181824',
                    hover: '#161621',
                    text: '#bfbfd0',
                },
                'secondary-text': '#8e94a9',
            },
            borderRadius: {
                btn: '3px',
                input: '2px',
            },
            spacing: {
                sidebar: '258px',
                navbar: '64px',
                'sidebar-collapsed': '70px',
            },
            boxShadow: {
                card: '0 1px 3px 0 rgba(0, 23, 55, 0.04)',
                'card-hover': '0 4px 12px 0 rgba(0, 23, 55, 0.08)',
            },
        },
    },

    plugins: [forms],
};
