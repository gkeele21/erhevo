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
        './resources/js/**/*.vue',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                // Primary Palette - Deep Ocean
                navy: {
                    DEFAULT: '#1A365D',
                    50: '#E8EDF5',
                    100: '#C5D1E5',
                    200: '#9FB3D0',
                    300: '#7995BB',
                    400: '#5377A6',
                    500: '#2D5991',
                    600: '#1A365D',
                    700: '#142A4A',
                    800: '#0E1E37',
                    900: '#081224',
                },
                teal: {
                    DEFAULT: '#136F74',
                    50: '#E6F4F5',
                    100: '#B3DDDF',
                    200: '#80C6C9',
                    300: '#4DAFB3',
                    400: '#1A989D',
                    500: '#136F74',
                    600: '#0F595D',
                    700: '#0B4346',
                    800: '#072D2F',
                    900: '#031718',
                },
                aqua: {
                    DEFAULT: '#39B8CF',
                    50: '#E9F7FA',
                    100: '#C7ECF2',
                    200: '#A5E1EA',
                    300: '#83D6E2',
                    400: '#5ECBD9',
                    500: '#39B8CF',
                    600: '#2E93A6',
                    700: '#236E7D',
                    800: '#184954',
                    900: '#0D242B',
                },
                // Accent Palette - Golden Glow
                gold: {
                    DEFAULT: '#F0C56F',
                    50: '#FDF9F0',
                    100: '#FAEFD5',
                    200: '#F7E5BA',
                    300: '#F4DB9F',
                    400: '#F2D084',
                    500: '#F0C56F',
                    600: '#E5AA3A',
                    700: '#C08A1F',
                    800: '#8B6417',
                    900: '#563E0E',
                },
                amber: {
                    DEFAULT: '#F4933D',
                    50: '#FEF5EC',
                    100: '#FCE4CC',
                    200: '#FAD3AC',
                    300: '#F8C28C',
                    400: '#F6B16C',
                    500: '#F4933D',
                    600: '#E5740E',
                    700: '#B35A0B',
                    800: '#814108',
                    900: '#4F2805',
                },
                ivory: {
                    DEFAULT: '#FFF4DD',
                    50: '#FFFCF7',
                    100: '#FFF9EE',
                    200: '#FFF4DD',
                    300: '#FFECC4',
                    400: '#FFE4AB',
                    500: '#FFDC92',
                },
                // Neutral
                soft: {
                    DEFAULT: '#FAFAFA',
                    white: '#FAFAFA',
                },
            },
        },
    },

    plugins: [forms, typography],
};
