/** @type {import('tailwindcss').Config} */
export default {
  content: [
    './resources/views/**/*.blade.php',
    './resources/js/**/*.js',
    './resources/js/**/*.vue',
    './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
    './storage/framework/views/*.php',
  ],
  theme: {
    fontFamily: {
      sans: [
        'Instrument Sans', 'Inter', 'ui-sans-serif', 'system-ui', '-apple-system',
        'Segoe UI', 'Roboto', 'Helvetica Neue', 'Arial',
        'Apple Color Emoji', 'Segoe UI Emoji', 'Segoe UI Symbol', 'Noto Color Emoji'
      ],
      montserrat: ['Montserrat', 'sans-serif'],
    },
    extend: {
      colors: {
        brand:      'oklch(0.62 0.12 255)',
        'brand-600':'oklch(0.58 0.12 255)',
        'brand-700':'oklch(0.52 0.12 255)',
      },
      borderRadius: {
        md: '0.9rem',
        xl: '1.25rem',
        '2xl': '1.75rem',
      },
      boxShadow: {
        soft: '0 6px 18px rgba(0,0,0,.06)',
        mdx:  '0 12px 28px rgba(0,0,0,.10)',
      },
    },
  },
  plugins: [],
}
