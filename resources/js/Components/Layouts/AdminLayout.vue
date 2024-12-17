<template>
<Head :title="title" />

  <NDrawer v-model:show="isDrawerActive" :width="325" placement="left">
    <NDrawerContent closable>
      <div class="grid grid-rows-2">
        <AdminMenu />
      </div>
    </NDrawerContent>
  </NDrawer>
  <div
    class="relative grid min-h-screen bg-zinc-50 dark:bg-zinc-900 max-md:grid-rows-[7.3rem,_auto] md:grid-cols-[18rem,_auto] md:gap-4">
    <div
      class="sticky top-4 z-50 my-4 ml-4 flex flex-col overflow-y-auto rounded-md border border-zinc-200/90 bg-gradient-to-b from-white to-zinc-100 shadow-inner dark:border-zinc-700 dark:from-zinc-900 dark:to-[#141416] max-md:mr-4 max-md:w-[calc(100vw-3rem)] md:h-[calc(100svh-2rem)]">
      <div class="flex items-center gap-2">
        <Link class="size-fit" :href="route('dashboard')">
        <AppLogo class="mr-auto w-32 p-4 md:w-36" />
        </Link>
        <TaskIndicatorButton size="small" />
        <NNotificationProvider placement="bottom-right">
          <NotificationBell size="small" />
        </NNotificationProvider>
        <div v-if="!mdAndGreater" class="ml-auto mr-4 w-fit">
          <NButton class="ml-auto" size="small" @click="isDrawerActive = !isDrawerActive">
            <template #icon>
              <LineHorizontal320Filled />
            </template>
          </NButton>
        </div>
      </div>
      <AdminMenu v-if="mdAndGreater" />
    </div>
    <div class="md:pr-4">
      <slot />
    </div>
  </div>
  <CardModal :title="`⭐️ ${$t('vusa.lt atsinaujino')}!`" :show="showChanges" @close="approveChanges">
    <div class="mb-8">
      <template v-for="change in $page.props.auth?.changes" :key="change.id">
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
</template>

<script setup lang="tsx">
import { Head, Link, router, usePage } from "@inertiajs/vue3";
import {
  type MessageReactive,
  useMessage,
} from "naive-ui";
import { breakpointsTailwind, useBreakpoints, useOnline, useTimeoutFn } from "@vueuse/core";
import { computed, onMounted, watch } from "vue";
import { ref } from "vue";

import { formatStaticTime } from "@/Utils/IntlTime";
import AdminMenu from "@/Components/Menus/AdminMenu.vue";
import AppLogo from "@/Components/AppLogo.vue";
import CardModal from "../Modals/CardModal.vue";
import NotificationBell from "@/Features/Admin/Notifications/NotificationBell.vue";
import TaskIndicatorButton from "../../Features/Admin/TaskManager/TaskIndicatorButton.vue";
import LineHorizontal320Filled from "~icons/fluent/line-horizontal-3-20-filled";

defineProps<{
  title?: string;
  createUrl?: string | null;
  backUrl?: string | null;
}>();

const mounted = ref(false);
const showChanges = ref(false);
const online = useOnline();
const message = useMessage();
const isDrawerActive = ref(false);

const currentPath = computed(() => usePage().props.app.path);

watch(
  () => currentPath.value,
  () => {
    isDrawerActive.value = false;
  },
);

const successMessage = computed(() => usePage().props.flash.success);
const infoMessage = computed(() => usePage().props.flash.info);
const errorMessage = computed(() => usePage().props.errors);

const mdAndGreater = useBreakpoints(breakpointsTailwind).greaterOrEqual('md');

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
