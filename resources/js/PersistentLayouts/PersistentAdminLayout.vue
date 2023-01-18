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
        <NScrollbar id="main-scroll-container" class="max-h-[calc(100vh-4rem)]">
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
import { defineAsyncComponent, onMounted, ref } from "vue";

import { isDarkMode, updateDarkMode } from "@/Composables/darkMode";
import FadeTransition from "@/Components/Transitions/FadeTransition.vue";
import Layout from "@/Components/Layouts/AdminLayout.vue";

// import "@/echo";

const isThemeDark = ref(isDarkMode());
const mounted = ref(false);

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
  mounted.value = true;
});
</script>

<style scoped>
main {
  scroll-margin: 5rem 0 0 0;
}
</style>
