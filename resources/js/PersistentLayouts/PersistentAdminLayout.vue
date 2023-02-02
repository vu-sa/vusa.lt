<template>
  <NConfigProvider
    v-show="mounted"
    :locale="enUS"
    :theme="isThemeDark ? darkTheme : undefined"
    :theme-overrides="isThemeDark ? darkThemeOverrides : themeOverrides"
  >
    <!-- <component
      :is="$page.props.app.env === 'local' ? NThemeEditor : 'div'"
      id="before-layout"
    > -->
    <NMessageProvider>
      <Layout>
        <!-- <FadeTransition> -->
        <NScrollbar ref="scroll" class="max-h-[calc(100vh-4rem)]">
          <main
            class="grid max-w-7xl grid-cols-[1fr_minmax(250px,_400px)] items-start py-4 pb-8"
          >
            <slot />
          </main>
        </NScrollbar>
        <!-- </FadeTransition> -->
      </Layout>
    </NMessageProvider>
    <!-- </component> -->
  </NConfigProvider>
</template>

<script setup lang="tsx">
import {
  NConfigProvider,
  NMessageProvider,
  NScrollbar,
  darkTheme,
  enUS,
} from "naive-ui";
import { onMounted, ref } from "vue";

import { isDarkMode, updateDarkMode } from "@/Composables/darkMode";
// import { usePage } from "@inertiajs/vue3";
// import "./posthog";
import Layout from "@/Components/Layouts/AdminLayout.vue";

import "@/echo";
import { usePage } from "@inertiajs/vue3";

const isThemeDark = ref(isDarkMode());
const mounted = ref(false);

if (usePage().props?.auth?.user) {
  $posthog.identify(usePage().props.auth?.user.id);
}

// const NThemeEditor = defineAsyncComponent(
//   () => import("naive-ui/lib/theme-editor/src/ThemeEditor")
// );

updateDarkMode(isThemeDark);

const themeOverrides = {
  common: {
    primaryColor: "#bd2835FF",
    primaryColorHover: "#CD3543FF",
    primaryColorPressed: "#CC2130FF",
    primaryColorSuppl: "#B93945FF",
  },
  Carousel: {
    dotColor: "#ebebeb",
    dotColorActive: "#bd2835CC",
    arrowColor: "#bbbbbb",
  },
  Layout: {
    color: "rgb(250 248 248)",
    headerColor: "rgb(250 248 248)",
    footerColor: "rgb(250 248 248)",
  },
};

const darkThemeOverrides = {
  common: {
    primaryColor: "#bd2835FF",
    primaryColorHover: "#CD3543FF",
    primaryColorPressed: "#CC2130FF",
    primaryColorSuppl: "#B93945FF",
  },
  Layout: {
    color: "rgb(30 30 33)",
    headerColor: "rgb(30 30 33)",
    footerColor: "rgb(30 30 33)",
  },
};

onMounted(() => {
  // (function (c, l, a, r, i, t, y) {
  //   c[a] =
  //     c[a] ||
  //     function () {
  //       (c[a].q = c[a].q || []).push(arguments);
  //     };
  //   t = l.createElement(r);
  //   t.async = 1;
  //   t.src = "https://www.clarity.ms/tag/" + i;
  //   y = l.getElementsByTagName(r)[0];
  //   y.parentNode.insertBefore(t, y);
  // })(window, document, "clarity", "script", "fads3o3wtb");

  mounted.value = true;
});
</script>

<style scoped>
main {
  scroll-margin: 5rem 0 0 0;
}
</style>
