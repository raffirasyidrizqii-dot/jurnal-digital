/** @type {import('tailwindcss').Config} */
module.exports = {
  darkMode: 'class',
  content: [
    './components/**/*.{vue,js,ts}',
    './layouts/**/*.vue',
    './pages/**/*.vue',
    './app.vue',
    './plugins/**/*.{js,ts}'
  ],
  theme: {
    extend: {
      colors: {
        primary: {
          DEFAULT: '#0f47ad', // Biru Resmi SMKN 71
          hover: '#0c398a',
          dark: '#082457',
          light: '#e0e7ff',
          subtle: '#eff4fc'
        },
        slate: {
          850: '#151e2e',
          900: '#0f172a',
          950: '#020617'
        },
        brand: {
          primary: '#1d4ed8',
          emerald: '#059669',
          amber: '#d97706',
          rose: '#e11d48',
          indigo: '#4f46e5'
        },
        presence: {
          hadir: '#059669',
          'hadir-bg': '#ecfdf5',
          sakit: '#d97706',
          'sakit-bg': '#fffbeb',
          izin: '#2563eb',
          'izin-bg': '#eff6ff',
          alpha: '#e11d48',
          'alpha-bg': '#fff1f2'
        }
      },
      fontFamily: {
        sans: ['Inter', 'system-ui', '-apple-system', 'BlinkMacSystemFont', 'Segoe UI', 'Roboto', 'sans-serif']
      },
      boxShadow: {
        '2xs': '0 1px 2px 0 rgba(15, 23, 42, 0.04)',
        'xs': '0 1px 3px 0 rgba(15, 23, 42, 0.06), 0 1px 2px -1px rgba(15, 23, 42, 0.04)',
        'card': '0 1px 3px 0 rgba(15, 23, 42, 0.05), 0 1px 2px -1px rgba(15, 23, 42, 0.05)',
        'card-hover': '0 10px 25px -5px rgba(15, 23, 42, 0.08), 0 8px 10px -6px rgba(15, 23, 42, 0.04)',
        'glow-primary': '0 0 20px -5px rgba(29, 78, 216, 0.25)',
      }
    }
  },
  plugins: []
}
