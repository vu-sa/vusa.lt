<template>
  <NThemeEditor>
    <NConfigProvider :theme-overrides="themeOverrides">
      <Head :title="title" />
      <MetaIcons />

      <NLayout
        class="min-h-screen bg-gradient-to-tr from-vusa-red/20 to-vusa-yellow/20"
      >
        <NLayoutHeader class="flex flex-row justify-between py-4 pr-8">
          <div class="invisible">
            <NButton secondary round @click="collapsed = !collapsed"
              >Menu</NButton
            >
          </div>
          <UserAvatar />
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
              v0.2.4 (2022-08-01)
            </NButton>
          </div>
          <NModal v-model:show="showModal">
            <Changelog />
          </NModal>
        </NLayoutFooter>
      </NLayout>
    </NConfigProvider>
  </NThemeEditor>
</template>

<script setup lang="ts">
import { Head, usePage } from "@inertiajs/inertia-vue3";
import {
  NButton,
  NConfigProvider,
  NLayout,
  NLayoutContent,
  NLayoutFooter,
  NLayoutHeader,
  NLayoutSider,
  NModal,
  NThemeEditor,
} from "naive-ui";
import { ref } from "vue";

import AdminMenu from "@/Components/Admin/Nav/AdminMenu.vue";
import AppLogo from "@/Components/AppLogo.vue";
import AsideNavigation from "@/Components/Admin/Layouts/AsideNavigation.vue";
import Changelog from "@/Components/Admin/Misc/ChangelogCard.vue";
import MetaIcons from "@/Components/MetaIcons.vue";
import UserAvatar from "../Nav/UserAvatar.vue";

defineProps<{
  title?: string;
  createUrl?: string | null;
  backUrl?: string | null;
}>();

const collapsed = ref(true);
const showModal = ref(false);

const themeOverrides = {
  Layout: {
    color: "#FFFFFF00",
    headerColor: "#FFFFFF00",
    footerColor: "#FFFFFF00",
  },
};
</script>
