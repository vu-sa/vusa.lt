import type { Config } from 'tailwindcss'
import tailwindcssAnimate from 'tailwindcss-animate'

export default {
  content: [
    "./vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php",
    "./storage/framework/views/*.php",
    "./resources/views/**/*.blade.php",
    "./resources/js/**/*.vue",
    "./app/Tiptap/*.php",
  ],
  darkMode: ["class"],
  theme: {
    container: {
      center: true,
      padding: "2rem",
      screens: {
        "2xl": "1400px",
      },
    },
    extend: {
      //fontFamily: {
      //  sans: ["DM Sans", "sans-serif"],
      //},
      keyframes: {
        "accordion-down": {
          from: { height: 0 },
          to: { height: "var(--radix-accordion-content-height)" },
        },
        "accordion-up": {
          from: { height: "var(--radix-accordion-content-height)" },
          to: { height: 0 },
        },
        'collapsible-down': {
          from: { height: 0 },
          to: { height: 'var(--radix-collapsible-content-height)' },
        },
        'collapsible-up': {
          from: { height: 'var(--radix-collapsible-content-height)' },
          to: { height: 0 },
        },
      },
      animation: {
        "accordion-down": "accordion-down 0.2s ease-out",
        "accordion-up": "accordion-up 0.2s ease-out",
        'collapsible-down': 'collapsible-down 0.2s ease-in-out',
        'collapsible-up': 'collapsible-up 0.2s ease-in-out',
      },
      colors: {
        "vusa-red": "#bd2835",
        "vusa-red-secondary": "#8c1d27",
        "vusa-red-tertiary": "#bb2734",
        "vusa-red-quaternary": "#d74350",
        "vusa-red-dark": "#5d131a",
        "vusa-yellow": "#fbb01b",
        "vusa-yellow-secondary": "#de9503",
        "vusa-yellow-tertiary": "#fbb120",
        "vusa-yellow-quaternary": "#fcc557",
        "vusa-yellow-dark": "#a77002",
      },
      gridTemplateColumns: {
        ramFill: "repeat(auto-fill, minmax(320px, 1fr))",
        ramFit: "repeat(auto-fit, minmax(320px, 1fr))",
      },
    },
  },

  plugins: [tailwindcssAnimate]
} satisfies Config
