<template>

  <Head :title="title" />

  <NLayout class="min-h-screen">
    <nav
      class="fixed z-50 flex h-16 w-full flex-row items-center justify-between border-b px-8 py-2 shadow-sm backdrop-blur-lg dark:border-zinc-800 md:justify-end">
      <div class="flex items-center gap-2 md:hidden">
        <NButton size="small" strong quaternary @click="activeDrawer = true">
          <template #icon>
            <IFluentNavigation24Filled />
          </template>
        </NButton>
        <Link class="size-fit" :href="route('dashboard')">
        <AppLogo class="h-12" />
        </Link>
      </div>
      <div class="mt-1 flex items-center gap-9">
        <Link v-if="$page.props.auth.can.index.user" :href="route('stats.representativesInTenant')">
        <IFluentChartMultiple20Filled width="20" height="20" />
        </Link>
        <FeedbackModalButton />
        <Link v-if="canSeeWorkspace && false" class="mt-2 hidden md:inline" :href="route('workspace')">
        <NButton text><template #icon>
            <IFluentBoard20Regular width="20" height="20" />
          </template></NButton>
        </Link>
        <TaskIndicatorButton class="mt-0.5" />
        <NNotificationProvider placement="bottom-right">
          <NMessageProvider>
            <NotificationBell class="mt-1" />
          </NMessageProvider>
        </NNotificationProvider>
        <UserAdminOptionsMenu />
      </div>
    </nav>
    <NDrawer v-model:show="activeDrawer" :width="325" placement="left">
      <NDrawerContent>
        <AdminMenu :collapsed="false" @close:drawer="activeDrawer = false" />
        <NDivider />
        <div class="flex items-center justify-center gap-6 overflow-hidden">
          <DarkModeSwitch style="margin-top: auto; margin-bottom: auto" />
          <NButton text @click="changeLocale">
            <template #icon>
              <NIcon :size="16"><img v-if="locale === 'en'" class="transition hover:opacity-70"
                  src="https://hatscripts.github.io/circle-flags/flags/gb.svg">
                <img v-else class="transition hover:opacity-70"
                  src="https://hatscripts.github.io/circle-flags/flags/lt.svg">
              </NIcon>
            </template>
          </NButton>
        </div>
      </NDrawerContent>
    </NDrawer>
    <NLayout class="mt-16" has-sider>
      <NLayoutSider v-if="mdAndGreater" class="my-6 ml-4 h-fit rounded-md from-white shadow-md" collapse-mode="width"
        :collapsed-width="69" :width="220" :collapsed="collapsed" show-trigger="bar" @collapse="collapsed = true"
        @expand="collapsed = false">
        <Link class="size-fit" :href="route('dashboard')">
        <AppLogo class="mx-auto w-full p-2" />
        </Link>
        <NScrollbar class="max-h-[calc(100vh-20rem)] px-1.5">
          <AdminMenu :collapsed="collapsed" :collapsed-width="56" :collapsed-icon-size="26" />
        </NScrollbar>
        <NDivider />

        <div class="mb-4 flex items-center justify-center gap-6 overflow-hidden">
          <DarkModeSwitch style="margin-top: auto; margin-bottom: auto" />
          <NButton v-if="!collapsed" text @click="changeLocale">
            <template #icon>
              <NIcon :size="16"><img v-if="locale === 'en'" class="transition hover:opacity-70"
                  src="https://hatscripts.github.io/circle-flags/flags/gb.svg">
                <img v-else class="transition hover:opacity-70"
                  src="https://hatscripts.github.io/circle-flags/flags/lt.svg">
              </NIcon>
            </template>
          </NButton>
        </div>
      </NLayoutSider>
      <NLayoutContent>
        <NMessageProvider>
          <slot />
        </NMessageProvider>
      </NLayoutContent>
    </NLayout>
    <CardModal :title="`⭐️ ${$t('vusa.lt atsinaujino')}!`" :show="showChanges" @close="approveChanges">
      <div class="mb-8">
        <template v-for="change in $page.props.auth.changes" :key="change.id">
          <h4 class="mb-0 tracking-tight">
            {{ formatStaticTime(new Date(change.date)) }}
          </h4>
          <small class="text-zinc-400">{{ change.title }}</small>
          <div class="mt-4" v-html="change.description" />
          <NDivider class="last:hidden" />
        </template>
      </div>
      <NButton type="primary" @click="approveChanges">
        <template #icon>
          <IFluentThumbLike16Regular />
        </template>
        Liuks
      </NButton>
    </CardModal>
  </NLayout>
</template>

<script setup lang="tsx">
import { Head, Link, router, usePage } from "@inertiajs/vue3";
import {
  type MessageReactive,
  useMessage,
} from "naive-ui";
import { breakpointsTailwind, useBreakpoints, useOnline, useStorage, useTimeoutFn } from "@vueuse/core";
import { computed, onMounted, watch } from "vue";
import { ref } from "vue";

import { formatStaticTime } from "@/Utils/IntlTime";
import { loadLanguageAsync } from "laravel-vue-i18n";
import AdminMenu from "@/Components/Menus/AdminMenu.vue";
import AppLogo from "@/Components/AppLogo.vue";
import CardModal from "../Modals/CardModal.vue";
import DarkModeSwitch from "@/Components/Buttons/DarkModeButton.vue";
import FeedbackModalButton from "../Buttons/FeedbackModalButton.vue";
import NotificationBell from "@/Features/Admin/Notifications/NotificationBell.vue";
import TaskIndicatorButton from "../../Features/Admin/TaskManager/TaskIndicatorButton.vue";
import UserAdminOptionsMenu from "@/Components/Menus/UserSettingsDropdown.vue";

defineProps<{
  title?: string;
  createUrl?: string | null;
  backUrl?: string | null;
}>();

const mounted = ref(false);
const showChanges = ref(false);
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

const mdAndGreater = useBreakpoints(breakpointsTailwind).greaterOrEqual('md');

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

const approveChanges = () => {
  router.post(
    route("changelogItems.approve"),
    {},
    {
      preserveState: true,
      onStart() {
        showChanges.value = false;
      },
    },
  );
};

onMounted(() => {
  mounted.value = true;

  if (usePage().props.auth?.changes.length > 0) {
    useTimeoutFn(() => {
      showChanges.value = true;
    }, 1000);
  }
});
</script>
