import { defineConfig } from "vite";
import i18n from "laravel-vue-i18n/vite";
import laravel from "laravel-vite-plugin";
import vue from "@vitejs/plugin-vue";

export default defineConfig({
  plugins: [
    laravel(["resources/css/app.css", "resources/js/app.js"]),
    // react(),
    vue({
      template: {
        transformAssetUrls: {
          base: null,
          includeAbsolute: false,
        },
      },
    }),
    i18n(),
  ],
  resolve: {
    alias: {
      "@": "/resources/js",
    },
  },
});
