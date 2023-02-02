import "../css/app.css";
import { type DefineComponent, createApp, h } from "vue";
import { ZiggyVue } from "../../vendor/tightenco/ziggy/src/js/vue.js";
import { createInertiaApp } from "@inertiajs/vue3";
import { defineAsyncComponent } from "vue";
import { i18nVue } from "laravel-vue-i18n";
import { resolvePageComponent } from "laravel-vite-plugin/inertia-helpers";
import PosthogPlugin from "./Plugins/posthog";

const AdminLayout = defineAsyncComponent(
  () => import("./PersistentLayouts/PersistentAdminLayout.vue")
);

const PublicLayout = defineAsyncComponent(
  () => import("./PersistentLayouts/PersistentPublicLayout.vue")
);

const appName =
  window.document.getElementsByTagName("title")[0]?.innerText || "Laravel";

createInertiaApp({
  title: (title) => `${title} - ${appName}`,
  resolve: (name) => {
    const page = resolvePageComponent(
      `./Pages/${name}.vue`,
      import.meta.glob("./Pages/**/*.vue")
    ) as Promise<DefineComponent>;

    page.then((module) => {
      if (!module) {
        return import("./Pages/NotFound.vue");
      }

      if (name.startsWith("Admin/")) {
        module.default.layout = AdminLayout;
      }

      if (name.startsWith("Public/")) {
        module.default.layout = PublicLayout;
      }
    });

    return page;
  },
  setup({ App, props, el, plugin }) {
    return createApp({ render: () => h(App, props) })
      .use(plugin)
      .use(PosthogPlugin)
      .use(i18nVue, {
        fallbackLang: "lt",
        resolve: async (lang: string) => {
          const langs = import.meta.glob("../../lang/*.json");
          return await langs[`../../lang/${lang}.json`]();
        },
      })
      .use(ZiggyVue)
      .mount(el);
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
