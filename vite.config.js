import { defineConfig } from "vite";
// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-ignore
import i18n from "laravel-vue-i18n/vite";
import laravel from "laravel-vite-plugin";
import vue from "@vitejs/plugin-vue";

export default defineConfig({
  plugins: [
    laravel(["resources/css/app.css", "resources/js/app.ts"]),
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
      ziggy: "/vendor/tightenco/ziggy/dist/vue",
    },
  },
  optimizeDeps: {
    include: ["ziggy"],
  },
});
