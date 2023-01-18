<template>
  <Head :title="title" />
  <MetaIcons />

  <NLayout class="min-h-screen">
    <div
      class="fixed z-50 flex h-16 w-full flex-row justify-between border-b py-2 pr-8 dark:border-zinc-800"
    >
      <div class="invisible">
        <NButton secondary round @click="collapsed = !collapsed">Menu</NButton>
      </div>
      <!-- <div class="w-96">
            <NInput round placeholder="Ieškoti...">
              <template #suffix
                ><NIcon :component="Search20Filled"></NIcon
              ></template>
            </NInput>
          </div> -->
      <div class="mt-1 flex items-center gap-8">
        <Link :href="route('workspace')"
          ><NButton secondary>Workspace</NButton></Link
        >
        <TaskIndicatorButton class="mt-0.5" />
        <NNotificationProvider placement="bottom-right"
          ><NMessageProvider><NotificationBell class="mt-1" /></NMessageProvider
        ></NNotificationProvider>
        <UserAdminOptionsMenu />
      </div>
    </div>
    <NLayout class="mt-16" has-sider>
      <NLayoutSider
        class="subtle-gray-gradient ml-4 mt-4 h-fit rounded-md from-white shadow-md"
        collapse-mode="width"
        :collapsed-width="isMobile ? 0 : 76"
        :width="220"
        :collapsed="collapsed"
        show-trigger="bar"
        @collapse="collapsed = true"
        @expand="collapsed = false"
      >
        <a class="h-fit w-fit" href="/">
          <AppLogo class="mx-auto w-full p-2" />
        </a>
        <NScrollbar class="max-h-[calc(100vh-20rem)] px-1.5"
          ><AdminMenu :collapsed="collapsed"
        /></NScrollbar>
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
      <NLayoutContent content-style="padding: 0rem 0rem 0rem 3rem">
        <NMessageProvider><slot /></NMessageProvider>
      </NLayoutContent>
    </NLayout>
  </NLayout>
</template>

<script setup lang="tsx">
import { Head, Link, usePage } from "@inertiajs/vue3";
import {
  type MessageReactive,
  NButton,
  NDivider,
  NLayout,
  NLayoutContent,
  NLayoutSider,
  NMessageProvider,
  NModal,
  NNotificationProvider,
  NScrollbar,
  useMessage,
  // NThemeEditor,
} from "naive-ui";
import { computed, onMounted, watch } from "vue";
import { ref } from "vue";
import { useOnline, useStorage } from "@vueuse/core";

import AdminMenu from "@/Components/Menus/AdminMenu.vue";
import AppLogo from "@/Components/AppLogo.vue";
import Changelog from "@/Components/Cards/ChangelogCard.vue";
import DarkModeSwitch from "@/Components/Buttons/DarkModeSwitch.vue";
import FadeTransition from "@/Components/Transitions/FadeTransition.vue";
import MetaIcons from "@/Components/MetaIcons.vue";
import NotificationBell from "@/Features/Admin/Notifications/NotificationBell.vue";
import TaskIndicatorButton from "../../Features/Admin/TaskManager/TaskIndicatorButton.vue";
import UserAdminOptionsMenu from "@/Components/Menus/UserSettingsDropdown.vue";

defineProps<{
  title?: string;
  createUrl?: string | null;
  backUrl?: string | null;
}>();

const showModal = ref(false);
const mounted = ref(false);
const collapsed = useStorage("admin-menu-collapsed", false);
const online = useOnline();
const message = useMessage();

const successMessage = computed(() => usePage().props.flash.success);
const infoMessage = computed(() => usePage().props.flash.info);
const errorMessage = computed(() => usePage().props.errors);

const errorOnlineReactiveMessage = () => {
  return message.error("Jūsų interneto ryšys buvo nutrauktas.", {
    duration: 0,
  });
};

const errorOnlineMessage = ref<MessageReactive | null>(null);

watch(successMessage, (successMessage) => {
  if (successMessage) {
    message.success(successMessage);
    usePage().props.flash.success = null;
  }
});

watch(infoMessage, (infoMessage) => {
  if (infoMessage) {
    message.info(infoMessage);
    usePage().props.flash.info = null;
  }
});

watch(online, (online) => {
  if (!online) {
    errorOnlineMessage.value = errorOnlineReactiveMessage();
  } else {
    if (errorOnlineMessage.value) {
      errorOnlineMessage.value.destroy();
      message.success("Jūsų interneto ryšys buvo atkurtas.");
    }
  }
});

// Run only in dev
if (usePage().props.app.env === "local") {
  watch(errorMessage, (errorMessage) => {
    if (errorMessage) {
      // loop over the object and display each error
      for (const [key, value] of Object.entries(errorMessage)) {
        message.error(`${key}: ${value}`);
      }
    }
  });
}

// compute if the width is less than 768px
const isMobile = ref(window.innerWidth < 768);

// update the isMobile value when the window is resized
window.addEventListener("resize", () => {
  isMobile.value = window.innerWidth < 768;
});

onMounted(() => {
  mounted.value = true;
});
</script>
