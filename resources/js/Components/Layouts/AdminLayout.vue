<template>
  <Head :title="title" />

  <NLayout class="min-h-screen">
    <nav
      class="fixed z-50 flex h-16 w-full flex-row items-center justify-between border-b py-2 px-8 shadow-sm backdrop-blur-lg dark:border-zinc-800 md:justify-end"
    >
      <div class="block md:hidden">
        <NButton size="small" strong quaternary @click="activeDrawer = true">
          <template #icon>
            <NIcon :component="Navigation24Filled" />
          </template>
        </NButton>
      </div>
      <div class="mt-1 flex items-center gap-8">
        <Link v-if="canSeeWorkspace" class="mt-2" :href="route('workspace')"
          ><NButton text
            ><template #icon
              ><NIcon
                :size="24"
                :component="Board24Regular"
              ></NIcon></template></NButton
        ></Link>
        <TaskIndicatorButton class="mt-0.5" />
        <NNotificationProvider placement="bottom-right"
          ><NMessageProvider><NotificationBell class="mt-1" /></NMessageProvider
        ></NNotificationProvider>
        <UserAdminOptionsMenu />
      </div>
    </nav>
    <NDrawer v-model:show="activeDrawer" :width="325" placement="left">
      <NDrawerContent>
        <Link class="h-fit w-fit" :href="route('dashboard')">
          <AppLogo class="mx-auto w-full p-2" />
        </Link>
        <NScrollbar class="max-h-[calc(100vh-20rem)] px-1.5">
          <AdminMenu :collapsed="false" @close:drawer="activeDrawer = false"
        /></NScrollbar>
        <NDivider />
        <div
          class="mb-4 flex items-center justify-center gap-6 overflow-hidden"
        >
          <div class="h-fit w-fit"><DarkModeSwitch /></div>
          <NButton text @click="changeLocale">
            <template #icon>
              <NIcon :size="16"
                ><img
                  v-if="locale === 'en'"
                  class="opacity-40 transition hover:opacity-70"
                  src="https://hatscripts.github.io/circle-flags/flags/gb.svg"
                />
                <img
                  v-else
                  class="opacity-40 transition hover:opacity-70"
                  src="https://hatscripts.github.io/circle-flags/flags/lt.svg"
                />
              </NIcon>
            </template>
          </NButton>
        </div>
      </NDrawerContent>
    </NDrawer>
    <NLayout class="mt-16" has-sider>
      <NLayoutSider
        class="subtle-gray-gradient my-6 ml-4 hidden h-fit rounded-md from-white shadow-md md:block"
        collapse-mode="width"
        :collapsed-width="69"
        :width="220"
        :collapsed="collapsed"
        show-trigger="bar"
        @collapse="collapsed = true"
        @expand="collapsed = false"
      >
        <Link class="h-fit w-fit" :href="route('dashboard')">
          <AppLogo class="mx-auto w-full p-2" />
        </Link>
        <NScrollbar class="max-h-[calc(100vh-20rem)] px-1.5">
          <AdminMenu
            :collapsed="collapsed"
            :collapsed-width="56"
            :collapsed-icon-size="26"
        /></NScrollbar>
        <NDivider />

        <div
          class="mb-4 flex items-center justify-center gap-6 overflow-hidden"
        >
          <div class="h-fit w-fit"><DarkModeSwitch /></div>
          <NButton v-if="!collapsed" text @click="changeLocale">
            <template #icon>
              <NIcon :size="16"
                ><img
                  v-if="locale === 'en'"
                  class="opacity-40 transition hover:opacity-70"
                  src="https://hatscripts.github.io/circle-flags/flags/gb.svg"
                />
                <img
                  v-else
                  class="opacity-40 transition hover:opacity-70"
                  src="https://hatscripts.github.io/circle-flags/flags/lt.svg"
                />
              </NIcon>
            </template>
          </NButton>
        </div>
      </NLayoutSider>
      <NLayoutContent :content-style="{ paddingLeft: '1rem' }">
        <NMessageProvider><slot /></NMessageProvider>
      </NLayoutContent>
    </NLayout>
  </NLayout>
</template>

<script setup lang="tsx">
import { Head, Link, router, usePage } from "@inertiajs/vue3";
import {
  type MessageReactive,
  NButton,
  NDivider,
  NDrawer,
  NDrawerContent,
  NIcon,
  NLayout,
  NLayoutContent,
  NLayoutSider,
  NMessageProvider,
  NNotificationProvider,
  NPopover,
  NScrollbar,
  useMessage,
} from "naive-ui";
import { computed, onMounted, watch } from "vue";
import { ref } from "vue";
import { useOnline, useStorage } from "@vueuse/core";

import { Board24Regular, Navigation24Filled } from "@vicons/fluent";
import { loadLanguageAsync } from "laravel-vue-i18n";
import AdminMenu from "@/Components/Menus/AdminMenu.vue";
import AppLogo from "@/Components/AppLogo.vue";
import DarkModeSwitch from "@/Components/Buttons/DarkModeSwitch.vue";
import NotificationBell from "@/Features/Admin/Notifications/NotificationBell.vue";
import TaskIndicatorButton from "../../Features/Admin/TaskManager/TaskIndicatorButton.vue";
import UserAdminOptionsMenu from "@/Components/Menus/UserSettingsDropdown.vue";

defineProps<{
  title?: string;
  createUrl?: string | null;
  backUrl?: string | null;
}>();

const mounted = ref(false);
const online = useOnline();
const message = useMessage();
const activeDrawer = ref(false);
const locale = useStorage("locale", usePage().props.app.locale);

const changeLocale = () => {
  locale.value = locale.value === "en" ? "lt" : "en";
  router.reload({ data: { lang: locale.value } });
};

const successMessage = computed(() => usePage().props.flash.success);
const infoMessage = computed(() => usePage().props.flash.info);
const errorMessage = computed(() => usePage().props.errors);

const canSeeWorkspace = computed(() => {
  let index = usePage().props.auth?.can?.index;

  if (!index) {
    return false;
  }
  // check if at least one property of index is true
  return Object.values(index).some((value) => value);
});

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

watch(locale, (locale) => {
  usePage().props.app.locale = locale;
  loadLanguageAsync(locale);
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

const collapsed = useStorage("admin-menu-collapsed", isMobile.value);

// update the isMobile value when the window is resized
window.addEventListener("resize", () => {
  isMobile.value = window.innerWidth < 768;
});

onMounted(() => {
  mounted.value = true;
});
</script>
