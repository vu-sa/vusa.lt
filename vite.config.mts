import { defineConfig } from "vitest/config";
import { NaiveUiResolver, VueUseComponentsResolver } from 'unplugin-vue-components/resolvers'
//import { codecovVitePlugin } from "@codecov/vite-plugin";
import Components from 'unplugin-vue-components/vite'
import Icons from 'unplugin-icons/vite'
import IconsResolver from 'unplugin-icons/resolver'
import Markdown from 'unplugin-vue-markdown/vite'
import i18n from "laravel-vue-i18n/vite";
import laravel from "laravel-vite-plugin";
import vue from "@vitejs/plugin-vue";
import vueDevTools from 'vite-plugin-vue-devtools'
import vueJsx from "@vitejs/plugin-vue-jsx";

// Load the CODECOV_TOKEN from the .env file
import { loadEnv } from "vite";
const token = loadEnv('production', './', 'CODECOV').CODECOV_TOKEN;

// This makes so that the <docs> block in Vue SFCs is removed
const vueDocsPlugin = {
  name: 'vue-docs',
  transform(code, id) {
    if (!/vue&type=docs/.test(id)) return
    return `export default ''`
  }
}

export default defineConfig({
  plugins: [
    vueDevTools({
      appendTo: 'resources/js/app.ts',
    }),
    vueDocsPlugin,
    laravel([
      "resources/js/app.ts", 
      // Compiles the same way as app.ts
      //"resources/js/admin.ts",
      //"resources/js/public.ts",

      // Also build .css, because it is used in minimal.blade.php
      "resources/css/app.css"
    ]),
    Markdown({
      markdownItOptions: {
        html: true,
        linkify: true,
        typographer: true,
      },
      wrapperClasses: undefined
    }),
    Components({
      resolvers: [
        IconsResolver(),
        NaiveUiResolver(),
        VueUseComponentsResolver()
      ],
      dts: 'resources/js/Types/components.d.ts',
    }),
    Icons(),
    vue({
      include: [/\.vue$/, /\.md$/],
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
        propsDestructure: true,
      },
    }),
    i18n(),
    vueJsx({
      // options are passed on to @vue/babel-plugin-jsx
    }),
    //codecovVitePlugin({
    //  enableBundleAnalysis: token !== undefined,
    //  bundleName: "Application",
    //  uploadToken: token,
    //}),
  ],
  test: {
    globals: true,
    environment: "happy-dom",
    setupFiles: ["vitest.setup.ts"],
  },
  resolve: {
    alias: {
      "@": "/resources/js",
      // vue: "vue/dist/vue.esm-bundler.js",
      "ziggy-js": "/vendor/tightenco/ziggy/dist",
    },
  },
  build: {
    // sourcemap: true,
  },
});
