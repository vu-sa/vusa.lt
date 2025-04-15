<template>
  <Head :title="title" />

  <SidebarProvider>
    <AppSidebar />
    <SidebarInset class="flex flex-col bg-background">
      <header class="sticky top-0 z-40 flex h-16 shrink-0 items-center border-b bg-background px-4">
        <div class="flex flex-1 items-center gap-2">
          <SidebarTrigger class="-ml-1" />
          <Separator orientation="vertical" class="mr-2 h-4" />
          <AdminBreadcrumbs :items="breadcrumbState.breadcrumbs.value" />
        </div>
        
        <div class="flex-shrink-0 flex items-center space-x-2">
          <slot name="headerActions"></slot>
          
          <!-- Search (optional) -->
          <!-- <CommandMenu /> -->
          
          <TasksIndicator />
          <NotificationsIndicator />
        </div>
      </header>
      
      <!-- Main content area -->
      <main class="flex-1 overflow-auto p-4 md:p-6">
        <!-- System announcements banner -->
        <div v-if="systemMessage" class="mb-6 rounded-lg border bg-amber-50 p-4 text-amber-900 dark:bg-amber-950 dark:text-amber-50">
          <div class="flex">
            <InfoIcon class="mr-3 h-5 w-5 flex-shrink-0" />
            <div>
              <h3 class="font-medium">{{ $t('System Announcement') }}</h3>
              <div class="mt-1 text-sm" v-html="systemMessage"></div>
            </div>
          </div>
        </div>
        
        <slot />
      </main>
      
      <!-- Footer (optional) -->
      <footer v-if="showFooter" class="border-t px-4 py-3 text-center text-xs text-muted-foreground">
        <div>© {{ currentYear }} {{ appName }} - {{ $t('Version') }} {{ appVersion }}</div>
      </footer>
      
      <!-- Bottom action bar for mobile screens (can be conditionally shown) -->
      <div v-if="showMobileActionBar" class="md:hidden fixed bottom-0 left-0 right-0 border-t bg-background p-2 flex items-center justify-around">
        <slot name="mobileActions">
          <!-- Default mobile actions -->
          <Button variant="ghost" size="sm" class="flex-col h-14 w-16">
            <HomeIcon class="h-5 w-5" />
            <span class="text-xs mt-1">{{ $t('Home') }}</span>
          </Button>
          <Button variant="ghost" size="sm" class="flex-col h-14 w-16">
            <PlusIcon class="h-5 w-5" />
            <span class="text-xs mt-1">{{ $t('New') }}</span>
          </Button>
          <Button variant="ghost" size="sm" class="flex-col h-14 w-16">
            <UserIcon class="h-5 w-5" />
            <span class="text-xs mt-1">{{ $t('Profile') }}</span>
          </Button>
        </slot>
      </div>
    </SidebarInset>
  </SidebarProvider>
  
  <!-- Toast notifications -->
  <Toaster />
  
  <!-- Command palette dialog -->
  <!-- <CommandDialog /> -->
</template>

<script setup lang="tsx">
import { Head, Link, router, usePage } from "@inertiajs/vue3";
import { useMessage } from "naive-ui";
import { breakpointsTailwind, useBreakpoints, useOnline, useTimeoutFn } from "@vueuse/core";
import { computed, onMounted, watch, onBeforeMount, ref } from "vue";
import { 
  InfoIcon, 
  HomeIcon, 
  PlusIcon, 
  UserIcon,
} from 'lucide-vue-next';

import AppSidebar from '@/Components/AppSidebar.vue'
import TasksIndicator from '@/Components/TasksIndicator.vue'
import NotificationsIndicator from '@/Components/NotificationsIndicator.vue'
import { Separator } from '@/Components/ui/separator'
import { Button } from '@/Components/ui/button'
import {
  SidebarInset,
  SidebarProvider,
  SidebarTrigger,
} from '@/Components/ui/sidebar'
import AdminBreadcrumbs from '@/Components/Admin/AdminBreadcrumbs.vue';
import { createBreadcrumbState } from '@/Composables/useBreadcrumbs';
import type { BreadcrumbItem } from '@/Composables/useBreadcrumbs';
import { Toaster } from "@/Components/ui/sonner";
// import { CommandDialog, CommandMenu } from '@/Components/ui/command';

const props = withDefaults(defineProps<{
  title?: string;
  createUrl?: string | null;
  backUrl?: string | null;
  breadcrumbs?: BreadcrumbItem[];
  showFooter?: boolean;
  showMobileActionBar?: boolean;
}>(), {
  showFooter: true,
  showMobileActionBar: false
});

const pageTitle = computed(() => props.title || usePage().component.split('/').pop() || 'Dashboard');
const appName = computed(() => usePage().props.app.name || 'VUSA');
const appVersion = computed(() => usePage().props.app.version || '1.0.0');
const currentYear = new Date().getFullYear();

// System message (announcements)
const systemMessage = computed(() => usePage().props.app?.systemMessage || null);

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
  router.on('finish', () => {
    // Using setTimeout to ensure component props have been updated
    setTimeout(() => updateBreadcrumbs(), 0);
  });
});

const mounted = ref(false);
const showChanges = ref(false);
const online = useOnline();
const message = useMessage();

const currentPath = computed(() => usePage().props.app.path);

// Flash messages handling
const successMessage = computed(() => usePage().props.flash.success);
const infoMessage = computed(() => usePage().props.flash.info);
const errorMessage = computed(() => usePage().props.errors);

const mdAndGreater = useBreakpoints(breakpointsTailwind).greaterOrEqual('md');

// Handle online/offline status
const handleOnlineStatus = () => {
  if (!online.value) {
    message.error($t("Your internet connection was lost."), {
      duration: 0,
      closable: true,
      onClose: () => {
        if (online.value) {
          message.success($t("Your internet connection was restored."));
        }
      }
    });
  } else {
    message.success($t("Your internet connection was restored."));
  }
};

watch(online, (isOnline) => {
  if (!mounted.value) return;
  handleOnlineStatus();
});

// Handle flash messages
watch(successMessage, (msg) => {
  if (msg) {
    message.success(msg);
    usePage().props.flash.success = null;
  }
});

watch(infoMessage, (msg) => {
  if (msg) {
    message.info(msg);
    usePage().props.flash.info = null;
  }
});

// Debug mode for errors (only in development)
if (usePage().props.app.env === "local") {
  watch(errorMessage, (errors) => {
    if (errors) {
      // Loop over the object and display each error
      Object.entries(errors).forEach(([key, value]) => {
        message.error(`${key}: ${value}`);
      });
    }
  });
}

// Detect mobile
const isMobile = ref(false);

const updateIsMobile = () => {
  isMobile.value = window.innerWidth < 768;
};

// Initialize mobile detection
onMounted(() => {
  mounted.value = true;
  updateIsMobile();
  window.addEventListener("resize", updateIsMobile);
  
  if (usePage().props.auth?.changes?.length > 0) {
    useTimeoutFn(() => {
      showChanges.value = true;
    }, 1000);
  }
});

// Clean up event listeners
onBeforeMount(() => {
  window.removeEventListener("resize", updateIsMobile);
});
</script>
