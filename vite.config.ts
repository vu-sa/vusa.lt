import { defineConfig } from "vite";
// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-ignore
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
        compilerOptions: {
          isCustomElement: (tag) => tag.includes("cropper-"),
        },
      },
      script: {
        defineModel: true,
        propsDestructure: true,
      },
    }),
    i18n(),
    vueJsx({
      // options are passed on to @vue/babel-plugin-jsx
    }),
  ],
  resolve: {
    alias: {
      "@": "/resources/js",
      // vue: "vue/dist/vue.esm-bundler.js",
      ziggy: "/vendor/tightenco/ziggy/dist/vue",
    },
  },
   server: {
     hmr: {
       host: 'localhost',
     },
   },
  build: {
    // sourcemap: true,
  },
});
