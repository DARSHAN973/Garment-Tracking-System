/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    "./**/*.php",
    "./**/*.html",
    "./**/*.js"
  ],
  theme: {
    extend: {
      colors: {
        primary: {
          50: '#eff6ff',
          500: '#3b82f6',
          600: '#2563eb',
          700: '#1d4ed8',
          900: '#1e3a8a'
        }
      }
    },
  },
  plugins: [],
  safelist: [
    // Include commonly used utility classes
    'bg-blue-600',
    'bg-blue-700',
    'bg-green-600',
    'bg-red-600',
    'bg-gray-50',
    'bg-gray-100',
    'text-white',
    'text-gray-900',
    'text-gray-600',
    'text-gray-500',
    'hover:bg-blue-700',
    'hover:bg-green-700',
    'hover:bg-red-700',
    'px-6',
    'py-3',
    'px-4',
    'py-2',
    'rounded-lg',
    'rounded-md',
    'font-medium',
    'font-bold',
    'text-sm',
    'text-xs',
    'ml-64',
    'p-8',
    'mb-8',
    'mt-2',
    'max-w-7xl',
    'mx-auto',
    'flex',
    'justify-between',
    'items-center',
    'text-3xl',
    'transition-colors'
  ]
}