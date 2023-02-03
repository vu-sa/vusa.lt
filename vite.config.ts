import { defineConfig } from "vite";
// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-ignore
import DefineOptions from "unplugin-vue-define-options/vite";
import i18n from "laravel-vue-i18n/vite";
import laravel from "laravel-vite-plugin";
import vue from "@vitejs/plugin-vue";
import vueJsx from "@vitejs/plugin-vue-jsx";

export default defineConfig({
  plugins: [
    laravel(["resources/js/app.ts"]),
    vue({
      template: {
        transformAssetUrls: {
          base: null,
          includeAbsolute: false,
        },
        // compilerOptions: {
        //   // treat all tags with a dash as custom elements
        //   isCustomElement: (tag) => tag.includes("-"),
        // },
      },
    }),
    i18n(),
    vueJsx({
      // options are passed on to @vue/babel-plugin-jsx
    }),
    DefineOptions(),
  ],

  resolve: {
    alias: {
      "@": "/resources/js",
      // vue: "vue/dist/vue.esm-bundler.js",
      ziggy: "/vendor/tightenco/ziggy/dist/vue",
    },
  },
  // server: {
  //   watch: {
  //     usePolling: true,
  //   },
  // },
  build: {
    // sourcemap: true,
  },
});
