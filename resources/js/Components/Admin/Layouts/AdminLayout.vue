<template>
  <!-- <NThemeEditor> -->
  <FadeTransition>
    <NConfigProvider
      v-show="mounted"
      :theme="isThemeDark ? darkTheme : undefined"
      :theme-overrides="themeOverrides"
    >
      <Head :title="title" />
      <MetaIcons />

      <NLayout
        class="min-h-screen bg-gradient-to-tr from-vusa-red/90 to-vusa-yellow/90 transition-colors before:absolute before:block before:h-full before:w-full before:bg-white/60 before:content-[''] dark:from-vusa-red dark:to-vusa-yellow/80 dark:before:bg-zinc-800/80"
      >
        <NLayoutHeader class="flex flex-row justify-between py-4 pr-8">
          <div class="invisible">
            <NButton secondary round @click="collapsed = !collapsed"
              >Menu</NButton
            >
          </div>
          <div class="flex items-center gap-4">
            <DarkModeSwitch />
            <UserAvatar />
          </div>
        </NLayoutHeader>
        <NLayout class="min-h-full" has-sider>
          <NLayoutSider
            class="ml-4 h-fit rounded-md shadow-sm"
            collapse-mode="width"
            :collapsed-width="64"
            :width="200"
            :collapsed="collapsed"
            show-trigger="bar"
            @collapse="collapsed = true"
            @expand="collapsed = false"
          >
            <a class="h-fit w-fit" href="/">
              <AppLogo class="mx-auto w-full p-2" />
            </a>
            <AdminMenu :collapsed="collapsed" />
          </NLayoutSider>
          <NLayoutContent
            class="min-h-full"
            content-style="padding: 0rem 2rem 2rem 3rem"
          >
            <slot />
          </NLayoutContent>
        </NLayout>
        <NLayoutFooter class="absolute bottom-0 w-full"
          ><div class="mx-auto mb-2 w-fit">
            <NButton size="tiny" quaternary @click="showModal = true">
              v0.3.6 (2022-09-21)
            </NButton>
          </div>
          <NModal v-model:show="showModal">
            <Changelog />
          </NModal>
        </NLayoutFooter>
      </NLayout>
    </NConfigProvider>
  </FadeTransition>
  <!-- </NThemeEditor> -->
</template>

<script setup lang="ts">
import { Head } from "@inertiajs/inertia-vue3";
import {
  NButton,
  NConfigProvider,
  NLayout,
  NLayoutContent,
  NLayoutFooter,
  NLayoutHeader,
  NLayoutSider,
  NModal,
  darkTheme,
  // NThemeEditor,
} from "naive-ui";
import { onMounted, ref } from "vue";

import { isDarkMode, updateDarkMode } from "@/Composables/darkMode";
import AdminMenu from "@/Components/Admin/Nav/AdminMenu.vue";
import AppLogo from "@/Components/AppLogo.vue";
import Changelog from "@/Components/Admin/Misc/ChangelogCard.vue";
import DarkModeSwitch from "@/Components/DarkModeSwitch.vue";
import FadeTransition from "@/Components/Public/Utils/FadeTransition.vue";
import MetaIcons from "@/Components/MetaIcons.vue";
import UserAvatar from "../Nav/UserAvatar.vue";

defineProps<{
  title?: string;
  createUrl?: string | null;
  backUrl?: string | null;
}>();

const collapsed = ref(true);
const showModal = ref(false);
const mounted = ref(false);

const isThemeDark = ref(isDarkMode());

const themeOverrides = {
  common: {
    primaryColor: "#bd2835FF",
    primaryColorHover: "#CD3543FF",
    primaryColorPressed: "#CC2130FF",
    primaryColorSuppl: "#B93945FF",
  },
  Layout: {
    color: "#FFFFFF00",
    headerColor: "#FFFFFF00",
    footerColor: "#FFFFFF00",
  },
};

onMounted(() => {
  updateDarkMode(isThemeDark);
  mounted.value = true;
});
</script>
