import { defineConfig } from "vitest/config";
import { loadEnv } from "vite";
// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-ignore
import { NaiveUiResolver, VueUseComponentsResolver } from 'unplugin-vue-components/resolvers'
import { codecovVitePlugin } from "@codecov/vite-plugin";
import Components from 'unplugin-vue-components/vite'
import Icons from 'unplugin-icons/vite'
import IconsResolver from 'unplugin-icons/resolver'
import i18n from "laravel-vue-i18n/vite";
import laravel from "laravel-vite-plugin";
import mdPlugin, { Mode } from 'vite-plugin-markdown';
import vue from "@vitejs/plugin-vue";
import vueJsx from "@vitejs/plugin-vue-jsx";

const token = loadEnv('production', './', 'CODECOV').CODECOV_TOKEN;


export default defineConfig({
  plugins: [
    laravel(["resources/js/app.ts"]),
    mdPlugin({ mode: [Mode.VUE] }),
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
    codecovVitePlugin({
      enableBundleAnalysis: token !== undefined,
      bundleName: "Application",
      uploadToken: token,
    }),
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
  server: {
    hmr: {
      host: "localhost",
    },
  },
  build: {
    // sourcemap: true,
  },
});
