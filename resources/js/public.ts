import "../css/app.css";

import { type DefineComponent, createApp, h } from "vue";
import { ZiggyVue } from 'ziggy-js'
import { createInertiaApp } from "@inertiajs/vue3";
import { defineAsyncComponent } from "vue";
import { i18nVue } from "laravel-vue-i18n";
import { resolvePageComponent } from "laravel-vite-plugin/inertia-helpers";

const PublicLayout = defineAsyncComponent(
  () => import("./Layouts/PersistentPublicLayout.vue"),
);

const getPosthog = async () => {
  let PosthogPlugin = null;

  if (import.meta.env.PROD) {
    PosthogPlugin = await import("./Plugins/posthog");
  }

  return PosthogPlugin;
};

//const metaTitle =
//  window.document.getElementsByTagName("title")[0]?.innerText || "VU SA";
//
//// get title from appTitle by removing the suffix
//const pageTitle = metaTitle.replace(" - VU SA", "");

const meta = document.createElement("meta");
meta.name = "naive-ui-style";
document.head.appendChild(meta);

createInertiaApp({
  title: (title) => {
    // Ensure title is always a string to prevent Inertia Head escape() errors
    return title ? String(title) : '';
  },
  resolve: (name) => {
    const page = resolvePageComponent(
      `./Pages/${name}.vue`,
      import.meta.glob("./Pages/Public/**/*.vue"),
    ) as Promise<DefineComponent>;

    page.then((module) => {
      if (!module) {
        return import("./Pages/NotFound.vue");
      }

      if (name.startsWith("Public/")) {
        module.default.layout = PublicLayout;
      }
    });

    return page;
  },
  setup({ App, props, el, plugin }) {
    // https://github.com/inertiajs/inertia/discussions/372#discussioncomment-6052940
    const application = createApp({ render: () => h(App, props) })
      .use(plugin)
      .use(i18nVue, {
        fallbackLang: "en",
        resolve: async (lang: string) => {
          // Load JSON translations (shared between admin/public)
          const jsonLangs = import.meta.glob<{ default: Record<string, string> }>("../../lang/*.json");
          // Load public-specific PHP translations (shared + public combined)
          const phpLangs = import.meta.glob<{ default: Record<string, string> }>("../../lang/php_public_*.json");
          
          const jsonPath = `../../lang/${lang}.json`;
          const phpPath = `../../lang/php_public_${lang}.json`;
          
          const jsonModule = jsonLangs[jsonPath] ? await jsonLangs[jsonPath]() : null;
          const phpModule = phpLangs[phpPath] ? await phpLangs[phpPath]() : null;
          
          // Merge translations: JSON base + PHP compiled
          return {
            ...(jsonModule?.default || {}),
            ...(phpModule?.default || {}),
          };
        },
      })
      .use(ZiggyVue)

    const PosthogPlugin = getPosthog();

    PosthogPlugin.then((module) => {
      if (module) {
        application.use(module.default);
      }
    });

    application.mount(el);

    delete el.dataset.page;

    return application;
  },
  progress: {
    // The delay after which the progress bar will
    // appear during navigation, in milliseconds.
    delay: 250,

    // The color of the progress bar.
    color: "#fbb01b",

    // Whether to include the default NProgress styles.
    includeCSS: true,

    // Whether the NProgress spinner will be shown.
    showSpinner: true,
  },
});
