// import "../css/app.css";
import "./bootstrap";
// import * as base from "./Composables/translation";

import { InertiaProgress } from "@inertiajs/progress";
import { createApp, h } from "vue";
import { createInertiaApp } from "@inertiajs/inertia-vue3";
import { i18nVue } from "laravel-vue-i18n";
import { resolvePageComponent } from "laravel-vite-plugin/inertia-helpers";

const appName =
  window.document.getElementsByTagName("title")[0]?.innerText || "Laravel";

// const meta = document.createElement("meta");
// meta.name = "naive-ui-style";
// document.head.appendChild(meta);

createInertiaApp({
  title: (title) => `${title} - ${appName}`,
  resolve: (name) =>
    resolvePageComponent(
      `./Pages/${name}.vue`,
      import.meta.glob("./Pages/**/*.vue")
    ),
  setup({ el, app, props, plugin }) {
    return createApp({ render: () => h(app, props) })
      .use(plugin)
      .use(i18nVue, {
        fallbackLang: "lt",
        resolve: async (lang) => {
          const langs = import.meta.glob("../../lang/*.json");
          return await langs[`../../lang/${lang}.json`]();
        },
      })
      .mixin({ methods: { route } })
      .mount(el);
  },
});

InertiaProgress.init({
  // The delay after which the progress bar will
  // appear during navigation, in milliseconds.
  delay: 50,

  // The color of the progress bar.
  color: "#fbb01b",

  // Whether to include the default NProgress styles.
  includeCSS: true,

  // Whether the NProgress spinner will be shown.
  showSpinner: true,
});
