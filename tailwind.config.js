/** @type {import('tailwindcss').Config} */

module.exports = {
  content: [
    "./vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php",
    "./storage/framework/views/*.php",
    "./resources/views/**/*.blade.php",
    "./resources/js/**/*.vue",
  ],
  darkMode: ["class"],
  corePlugins: {
    preflight: false,
  },
  theme: {
    extend: {
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

  plugins: [require("@tailwindcss/typography")],
};
