<template>
  <!-- <NThemeEditor> -->
  <!-- <Head>
    <meta v-if="isThemeDark" name="theme-color" content="#bd2835" />
  </Head> -->
  <FadeTransition>
    <NConfigProvider
      v-show="mounted"
      :theme="isThemeDark ? darkTheme : undefined"
      :theme-overrides="isThemeDark ? darkThemeOverrides : themeOverrides"
    >
      <Head :title="title" />
      <MetaIcons />

      <NLayout class="min-h-screen">
        <NLayoutHeader
          class="fixed z-50 flex flex-row justify-between py-4 pr-8 backdrop-blur-md"
        >
          <div class="invisible">
            <NButton secondary round @click="collapsed = !collapsed"
              >Menu</NButton
            >
          </div>
          <div class="flex items-center gap-4">
            <NotificationBell />
            <UserAdminOptionsMenu />
          </div>
        </NLayoutHeader>
        <NLayout class="mt-16" has-sider>
          <NLayoutSider
            class="main-card-gradient ml-4 mb-24 h-fit rounded-md from-white shadow-md"
            collapse-mode="width"
            :collapsed-width="isMobile ? 0 : 64"
            :width="200"
            :collapsed="collapsed"
            show-trigger="bar"
            @collapse="collapsed = true"
            @expand="collapsed = false"
          >
            <a class="h-fit w-fit" href="/">
              <AppLogo
                :is-theme-dark="isThemeDark"
                class="mx-auto w-full p-2"
              />
            </a>
            <AdminMenu :collapsed="collapsed" />
            <NDivider />

            <div class="mb-4 flex justify-center gap-4 overflow-hidden">
              <div class="w-fit"><DarkModeSwitch /></div>
              <div v-if="!collapsed" class="w-fit">
                <FadeTransition>
                  <NButton size="tiny" quaternary @click="showModal = true">
                    v0.3.9
                  </NButton>
                </FadeTransition>
              </div>
            </div>
            <NModal v-model:show="showModal">
              <Changelog />
            </NModal>
          </NLayoutSider>
          <NLayoutContent
            class="min-h-full"
            content-style="padding: 0rem 2rem 2rem 3rem"
          >
            <slot />
          </NLayoutContent>
        </NLayout>
      </NLayout>
    </NConfigProvider>
  </FadeTransition>
  <!-- </NThemeEditor> -->
</template>

<script setup lang="ts">
import {
  ConfigProviderProps,
  NButton,
  NConfigProvider,
  NDivider,
  NLayout,
  NLayoutContent,
  NLayoutHeader,
  NLayoutSider,
  NModal,
  createDiscreteApi,
  darkTheme,
  // NThemeEditor,
} from "naive-ui";
import { Head, usePage } from "@inertiajs/inertia-vue3";
import { computed, onMounted, ref, watch } from "vue";

import { isDarkMode, updateDarkMode } from "@/Composables/darkMode";
import AdminMenu from "@/Components/Menus/AdminMenu.vue";
import AppLogo from "@/Components/AppLogo.vue";
import Changelog from "@/Components/Cards/ChangelogCard.vue";
import DarkModeSwitch from "@/Components/Buttons/DarkModeSwitch.vue";
import FadeTransition from "@/Components/Transitions/FadeTransition.vue";
import MetaIcons from "@/Components/MetaIcons.vue";
import NotificationBell from "@/Components/Notifications/NotificationBell.vue";
import UserAdminOptionsMenu from "@/Components/Menus/UserSettingsDropdown.vue";

defineProps<{
  title?: string;
  createUrl?: string | null;
  backUrl?: string | null;
}>();

const collapsed = ref(false);
const showModal = ref(false);
const mounted = ref(false);

const isThemeDark = ref(isDarkMode());

const successMessage = computed(() => usePage().props.value.flash.success);
const infoMessage = computed(() => usePage().props.value.flash.info);

watch(successMessage, (successMessage) => {
  if (successMessage) {
    message.success(successMessage);
    usePage().props.value.flash.success = null;
  }
});

watch(infoMessage, (infoMessage) => {
  if (infoMessage) {
    message.info(infoMessage);
    usePage().props.value.flash.info = null;
  }
});

// compute if the width is less than 768px
const isMobile = ref(window.innerWidth < 768);

// update the isMobile value when the window is resized
window.addEventListener("resize", () => {
  isMobile.value = window.innerWidth < 768;
});

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

const configProviderPropsRef = computed<ConfigProviderProps>(() => ({
  theme: isThemeDark.value ? darkTheme : undefined,
}));

const { message } = createDiscreteApi(["message"], {
  configProviderProps: configProviderPropsRef,
});
</script>
