<template>
  <NConfigProvider
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
        <main
          class="mb-4 mr-4 grid max-w-7xl grid-cols-[1fr_minmax(250px,_400px)] items-start overflow-auto pl-4 pt-4 pb-8"
        >
          <slot />
        </main>
        <!-- </FadeTransition> -->
      </Layout>
    </NMessageProvider>
    <!-- </component> -->
  </NConfigProvider>
</template>

<script setup lang="tsx">
import { NConfigProvider, NMessageProvider, darkTheme, enUS } from "naive-ui";
import { ref } from "vue";

// import "./posthog";
import { isDarkMode, updateDarkMode } from "@/Composables/darkMode";
import Layout from "@/Components/Layouts/AdminLayout.vue";

import "@/echo";

const isThemeDark = ref(isDarkMode());

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
</script>

<style scoped>
main {
  scroll-margin: 5rem 0 0 0;
}
</style>
