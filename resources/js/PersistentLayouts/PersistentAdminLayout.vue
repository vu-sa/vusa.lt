<template>
  <NConfigProvider
    v-show="mounted"
    :locale="enUS"
    :theme="isThemeDark ? darkTheme : undefined"
    :theme-overrides="isThemeDark ? darkThemeOverrides : themeOverrides"
  >
    <NMessageProvider>
      <Layout>
        <FadeTransition>
          <main>
            <slot />
          </main>
        </FadeTransition>
      </Layout>
    </NMessageProvider>
  </NConfigProvider>
</template>

<script setup lang="tsx">
import { NConfigProvider, NMessageProvider, darkTheme, enUS } from "naive-ui";
import { onMounted, ref } from "vue";

import { isDarkMode, updateDarkMode } from "@/Composables/darkMode";
import FadeTransition from "@/Components/Transitions/FadeTransition.vue";
import Layout from "@/Components/Layouts/AdminLayout.vue";

import "@/echo";

const isThemeDark = ref(isDarkMode());
const mounted = ref(false);

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
