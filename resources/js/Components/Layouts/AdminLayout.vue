<template>
  <Head :title="title" />

  <SidebarProvider>
    <AppSidebar />
    <SidebarInset>
      <header class="flex h-16 shrink-0 items-center gap-2">
        <div class="flex items-center gap-2 px-4">
          <SidebarTrigger class="-ml-1" />
          <Separator orientation="vertical" class="mr-2 h-4" />
          <!-- NOTE: for some reason, we need to unwrap it in the template -->
          <AdminBreadcrumbs :items="breadcrumbState.breadcrumbs.value" />
        </div>
        <div class="ml-auto flex items-center gap-2 px-4">
          <TasksIndicator />
          <NotificationsIndicator />
        </div>
      </header>
      <slot />
    </SidebarInset>
  </SidebarProvider>
</template>

<script setup lang="tsx">
import { Head, Link, router, usePage } from "@inertiajs/vue3";
import {
  type MessageReactive,
  useMessage,
} from "naive-ui";
import { breakpointsTailwind, useBreakpoints, useOnline, useTimeoutFn } from "@vueuse/core";
import { computed, onMounted, watch, onBeforeMount } from "vue";
import { ref } from "vue";

import AppSidebar from '@/Components/AppSidebar.vue'
import TasksIndicator from '@/Components/TasksIndicator.vue'
import NotificationsIndicator from '@/Components/NotificationsIndicator.vue'
import { Separator } from '@/Components/ui/separator'
import {
  SidebarInset,
  SidebarProvider,
  SidebarTrigger,
} from '@/Components/ui/sidebar'
import AdminBreadcrumbs from '@/Components/Admin/AdminBreadcrumbs.vue';
import { createBreadcrumbState } from '@/Composables/useBreadcrumbs';
import type { BreadcrumbItem } from '@/Composables/useBreadcrumbs';

const props = defineProps<{
  title?: string;
  createUrl?: string | null;
  backUrl?: string | null;
  breadcrumbs?: BreadcrumbItem[];
}>();

const pageTitle = computed(() => props.title || usePage().component.split('/').pop() || 'Dashboard');

// Initialize breadcrumb state for the entire admin application
const breadcrumbState = createBreadcrumbState();

// Set initial breadcrumbs from props if available
const updateBreadcrumbs = () => {
  if (props.breadcrumbs && props.breadcrumbs.length > 0) {
    breadcrumbState.setBreadcrumbs(props.breadcrumbs);
  } else if (!breadcrumbState.hasBreadcrumbs.value) {
    // Only set default breadcrumbs if no explicit breadcrumbs were set
    breadcrumbState.resetToHome();
    if (pageTitle.value && pageTitle.value !== 'ShowAdminHome') {
      breadcrumbState.addBreadcrumb({
        label: pageTitle.value,
        href: undefined
      });
    }
  }
};

// Watch for prop changes to update breadcrumbs immediately
watch(() => props.breadcrumbs, () => {
  updateBreadcrumbs();
}, { immediate: true });

// Also update breadcrumbs on page component change (for Inertia navigation)
watch(() => usePage().component, () => {
  updateBreadcrumbs();
}, { immediate: true });

// Listen for Inertia navigation events to update breadcrumbs
onBeforeMount(() => {
  router.on('finish', (event) => {
    // Using nextTick to ensure component props have been updated
    setTimeout(() => updateBreadcrumbs(), 0);
  });
});

const appName = computed(() => usePage().props.app.name || 'VUSA');

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
