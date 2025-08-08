import "../css/app.css";

import { type DefineComponent, createApp, h } from "vue";
import { ZiggyVue } from 'ziggy-js'
import { createInertiaApp } from "@inertiajs/vue3";
import { defineAsyncComponent } from "vue";
import { i18nVue } from "laravel-vue-i18n";
import { resolvePageComponent } from "laravel-vite-plugin/inertia-helpers";

const AdminLayout = defineAsyncComponent(
  () => import("./Components/Layouts/AdminLayout.vue"),
);

const getPosthog = async () => {
  let PosthogPlugin = null;

  if (import.meta.env.PROD) {
    PosthogPlugin = await import("./Plugins/posthog");
  }

  return PosthogPlugin;
};

const metaTitle =
  window.document.getElementsByTagName("title")[0]?.innerText || "VU SA";

// get title from appTitle by removing the suffix
const pageTitle = metaTitle.replace(" - VU SA", "");


createInertiaApp({
  title: (title) => {
    return title ? `${title} - VU SA` : pageTitle;
  },
  resolve: (name) => {
    const page = resolvePageComponent(
      `./Pages/${name}.vue`,
      import.meta.glob("./Pages/Admin/**/*.vue"),
    ) as Promise<DefineComponent>;

    page.then((module) => {
      if (!module) {
        return import("./Pages/NotFound.vue");
      }

      if (name.startsWith("Admin/") && name !== "Admin/LoginForm") {
        module.default.layout = AdminLayout;
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
          const langs = import.meta.glob("../../lang/*.json");
          return await langs[`../../lang/${lang}.json`]();
        },
      })
      .use(ZiggyVue);

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
