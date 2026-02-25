/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./index.html",
    "./src/**/*.{vue,js,ts,jsx,tsx}",
  ],
  theme: {
    extend: {
      colors: {
        // Colores del sidebar (estilo similar a la imagen)
        sidebar: {
          dark: '#1e293b',
          darker: '#0f172a',
          hover: '#334155',
          active: '#3b82f6',
        },
        // Colores de estado
        estado: {
          confirmado: '#22c55e',
          pendiente: '#f59e0b',
          entregado: '#3b82f6',
          rechazado: '#ef4444',
        }
      },
      fontFamily: {
        sans: ['Inter', 'system-ui', 'sans-serif'],
      },
    },
  },
  plugins: [],
}
