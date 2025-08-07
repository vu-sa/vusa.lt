import { defineConfig } from "vitest/config";
import { NaiveUiResolver, VueUseComponentsResolver } from 'unplugin-vue-components/resolvers'
import Components from 'unplugin-vue-components/vite'
import Icons from 'unplugin-icons/vite'
import IconsResolver from 'unplugin-icons/resolver'
import Markdown from 'unplugin-vue-markdown/vite'
import i18n from "laravel-vue-i18n/vite";
import laravel from "laravel-vite-plugin";
import vue from "@vitejs/plugin-vue";
import vueDevTools from 'vite-plugin-vue-devtools'
import vueJsx from "@vitejs/plugin-vue-jsx";
import tailwindcss from '@tailwindcss/vite'
import ziggy from 'vite-plugin-ziggy';

// Load the CODECOV_TOKEN from the .env file
// import { codecovVitePlugin } from "@codecov/vite-plugin";
// import { loadEnv } from "vite";
// const token = loadEnv('production', './', 'CODECOV').CODECOV_TOKEN;

export default defineConfig(({ command }) => {
  // Define common plugins that will be used in both build and test
  const commonPlugins = [
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
    vueJsx({
      // options are passed on to @vue/babel-plugin-jsx
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
    i18n(),
  ]

  // Core plugins needed for both dev and build
  const corePlugins = [
    laravel([
      "resources/js/app.ts",
      "resources/css/app.css"
    ]),
    tailwindcss(),
    ziggy(),
    Markdown({
      markdownItOptions: {
        html: true,
        linkify: true,
        typographer: true,
      },
      wrapperClasses: undefined
    }),
  ]

  // Dev-only plugins - disabled for Storybook
  const devPlugins = command !== 'test' && !process.env.STORYBOOK ? [
    vueDevTools({
      appendTo: 'resources/js/app.ts',
    }),
  ] : []

  return {
    plugins: [
      ...commonPlugins,
      ...corePlugins,
      ...devPlugins,
    ],
  resolve: {
    alias: {
      "@": "/resources/js",
      vue: "vue/dist/vue.esm-bundler.js",
      "ziggy-js": "/vendor/tightenco/ziggy/dist",
    },
  },
  build: {
    // sourcemap: true,
  },
  // NOTE: if not included, causes the error: "Cannot read properties of null (reading 'ce')"
  optimizeDeps: {
    include: [
      '@inertiajs/vue3',
      'laravel-vue-i18n',
      'vue'
    ]
  },
    css: {
      transformer: 'lightningcss',
    }
  }
})
