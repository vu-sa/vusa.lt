<template>
  <!-- v-show="mounted" -->
  <NConfigProvider
    :theme="isDark ? darkTheme : undefined"
    :theme-overrides="isDark ? darkThemeOverrides : themeOverrides"
  >
    <NMessageProvider>
      <Layout>
        <FadeTransition>
          <slot />
        </FadeTransition>
      </Layout>
    </NMessageProvider>
  </NConfigProvider>
</template>

<script setup lang="tsx">
import { NConfigProvider, NMessageProvider, darkTheme } from "naive-ui";
import { useDark } from "@vueuse/core";

import FadeTransition from "@/Components/Transitions/FadeTransition.vue";
import Layout from "@/Components/Public/Layouts/PublicLayout.vue";

const isDark = useDark();

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
</script>
