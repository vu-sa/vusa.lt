import { defineConfig } from 'vitest/config';
import { VueUseComponentsResolver } from 'unplugin-vue-components/resolvers';
import Components from 'unplugin-vue-components/vite';
import Icons from 'unplugin-icons/vite';
import IconsResolver from 'unplugin-icons/resolver';
import Markdown from 'unplugin-vue-markdown/vite';
import i18nSplit from './vite-plugins/i18n-split';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import vueDevTools from 'vite-plugin-vue-devtools';
import vueJsx from '@vitejs/plugin-vue-jsx';
import tailwindcss from '@tailwindcss/vite';
import ziggy from 'vite-plugin-ziggy';
import { VitePWA } from 'vite-plugin-pwa';

import { codecovVitePlugin } from "@codecov/vite-plugin";

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
          isCustomElement: tag => tag.includes('cropper-'),
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
        VueUseComponentsResolver(),
      ],
      // For fixing imports: https://github.com/unplugin/unplugin-icons/issues/317#issuecomment-1789146323
      importPathTransform(path) {
        return path === '~icons/fluent/speaker224-regular' ? '~icons/fluent/speaker2-24-regular' : path;
      },
      dts: 'resources/js/Types/components.d.ts',
    }),
    Icons(),
    i18nSplit(),
  ];

  // Core plugins needed for both dev and build
  const corePlugins = [
    laravel([
      'resources/js/app.ts',
      'resources/css/app.css',
    ]),
    tailwindcss(),
    ziggy({
      sail: true,
    }),
    Markdown({
      markdownItOptions: {
        html: true,
        linkify: true,
        typographer: true,
      },
      wrapperClasses: undefined,
    }),
  ];

  // Dev-only plugins - disabled for Storybook and builds
  const devPlugins = command === 'serve' && !process.env.STORYBOOK
    ? [
        vueDevTools({
          appendTo: 'resources/js/app.ts',
        }),
      ]
    : [];

  // Codecov bundle analysis - only during CI builds with token
  const codecovPlugins = command === 'build' && process.env.CODECOV_TOKEN
    ? [
        codecovVitePlugin({
          enableBundleAnalysis: true,
          bundleName: "vusa-frontend",
          uploadToken: process.env.CODECOV_TOKEN,
        }),
      ]
    : [];

  // PWA plugin configuration
  //
  // TODO: Track additional PWA enhancements (icons, OAuth popup flow, extended sessions,
  // offline form queue, notification handling, offline content strategy, etc.).
  // Refer to the PWA roadmap in project documentation or the associated GitHub issues
  // for the full list of ideas and implementation details.
  //    delivers. Add logic to deduplicate notifications shown via both channels.
  //
  // 6. REMAINING NOTIFICATIONS: Add toWebPush() method to remaining notification classes:
  //    - MeetingNotFinishedNotification.php
  //    - MemberRegistered.php
  //    - ReminderToLoginNotification.php
  //    - StudentRepRegistered.php
  //    - TaskCreatedNotification.php
  //    - TaskReminderNotification.php
  //
  const pwaPlugin = VitePWA({
    registerType: 'prompt',
    includeAssets: ['images/icons/favicons/favicon.ico', 'logos/vusa.lin.hor.svg'],
    manifest: {
      name: 'VU SA - Mano',
      short_name: 'VU SA Mano',
      description: 'VU SA administravimo sistema',
      theme_color: '#27272a',
      background_color: '#27272a',
      display: 'standalone',
      scope: '/mano',
      start_url: '/mano',
      icons: [
        {
          src: '/images/icons/favicons/pwa-192x192.png',
          sizes: '192x192',
          type: 'image/png',
        },
        {
          src: '/images/icons/favicons/pwa-512x512.png',
          sizes: '512x512',
          type: 'image/png',
        },
        {
          src: '/images/icons/favicons/pwa-512x512-maskable.png',
          sizes: '512x512',
          type: 'image/png',
          purpose: 'maskable',
        },
      ],
      // Screenshots for richer install UI (Chrome shows these in install dialog)
      // Mobile: 540x720 or similar portrait
      // Desktop: 1280x720 or similar landscape
      screenshots: [
        {
          src: '/images/pwa/screenshot-mobile.png',
          sizes: '540x720',
          type: 'image/png',
          form_factor: 'narrow',
          label: 'VU SA Mano - Dashboard',
        },
        {
          src: '/images/pwa/screenshot-desktop.png',
          sizes: '1280x720',
          type: 'image/png',
          form_factor: 'wide',
          label: 'VU SA Mano - Dashboard',
        },
      ],
    },
    workbox: {
      // Import custom push notification handler
      importScripts: ['/sw-push.js'],
      // Network-first for Inertia/API requests
      runtimeCaching: [
        {
          urlPattern: /^https?:\/\/[^/]+\/mano.*/i,
          handler: 'NetworkFirst',
          options: {
            cacheName: 'admin-pages',
            expiration: {
              maxEntries: 50,
              maxAgeSeconds: 60 * 60 * 24, // 1 day
            },
            networkTimeoutSeconds: 10,
          },
        },
        {
          urlPattern: /\.(?:png|jpg|jpeg|svg|gif|webp|ico)$/i,
          handler: 'CacheFirst',
          options: {
            cacheName: 'images',
            expiration: {
              maxEntries: 100,
              maxAgeSeconds: 60 * 60 * 24 * 30, // 30 days
            },
          },
        },
        {
          urlPattern: /\.(?:js|css)$/i,
          handler: 'StaleWhileRevalidate',
          options: {
            cacheName: 'static-resources',
            expiration: {
              maxEntries: 100,
              maxAgeSeconds: 60 * 60 * 24 * 7, // 7 days
            },
          },
        },
      ],
      // Precache critical admin home assets for faster first load
      // These patterns match the chunks needed for ShowAdminHome.vue
      globPatterns: [
        // Main entry points
        'assets/app-*.js',
        'assets/app-*.css',
        'assets/admin-*.js',
        'assets/admin-*.css',
        // Vue core (shared by all pages)
        'assets/index-*.js',
        // Admin home page and layout
        'assets/ShowAdminHome-*.js',
        'assets/ShowAdminHome-*.css',
        'assets/AdminContentPage*.js',
        'assets/AdminLayout*.js',
        // Dashboard components
        'assets/TasksCard*.js',
        'assets/UpcomingMeetingsCard*.js',
        'assets/CalendarEventsCard*.js',
        'assets/NewsListCard*.js',
        // UI primitives heavily used by admin home
        'assets/Card*.js',
        'assets/Separator*.js',
        // date-fns locales for greeting/date formatting
        'assets/lt-*.js',
        'assets/en-US-*.js',
        'assets/format-*.js',
        // Translation bundles
        'assets/php_admin_lt-*.js',
        'assets/php_admin_en-*.js',
      ],
      // Enable navigation preload for faster NetworkFirst responses
      navigationPreload: true,
      // Disable navigateFallback - Laravel handles routing, not the service worker
      navigateFallback: null,
    },
    // PWA dev mode is disabled because Laravel Sail architecture doesn't support it:
    // - Laravel serves the app at https://www.vusa.test (or similar)
    // - Vite serves HMR assets at http://localhost:5173
    // - Service workers require same-origin, so dev-sw.js gets redirected → error
    //
    // To test PWA features: run `npm run build` and access the site normally
    devOptions: {
      enabled: false,
    },
  });

  return {
    plugins: [
      ...commonPlugins,
      ...corePlugins,
      ...devPlugins,
      ...codecovPlugins,
      pwaPlugin,
    ],
    resolve: {
      alias: {
        '@': '/resources/js',
        'vue': 'vue/dist/vue.esm-bundler.js',
        'ziggy-js': '/vendor/tightenco/ziggy/dist',
      },
    },
    build: {
      // sourcemap: true,

      // NOTE: manualChunks approach was tested (Aug 2025) and found to hurt performance
      // - Reduced 583 → 409 files (-30%) and 7.9MB → 7.1MB bundle size (-10%)
      // - But increased FCP from 11.3s → 19.6s (+73%) due to waterfall loading
      // - Performance score dropped from 53 → 50 points
      // - The 583 tiny files loaded faster in parallel than 12 larger sequential chunks
      // - Real optimization target is 418KB unused JavaScript, not chunk reorganization
    },
    // NOTE: if not included, causes the error: "Cannot read properties of null (reading 'ce')"
    optimizeDeps: {
      include: [
        '@inertiajs/vue3',
        'laravel-vue-i18n',
        'vue',
      ],
    },
    css: {
      transformer: 'lightningcss',
    },
  };
});
