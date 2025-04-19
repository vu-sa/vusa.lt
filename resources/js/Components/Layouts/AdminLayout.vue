<template>
  <Head :title="title" />

  <SidebarProvider>
    <AppSidebar />
    <SidebarInset class="flex flex-col bg-background">
      <!-- Header with breadcrumbs and actions -->
      <header class="sticky top-0 z-40 flex h-16 shrink-0 items-center border-b bg-background px-4 rounded-t-xl">
        <div class="flex flex-1 items-center gap-2">
          <SidebarTrigger class="-ml-1" />
          <Separator orientation="vertical" class="mr-2 h-4" />
          <AdminBreadcrumbs :items="breadcrumbState.breadcrumbs.value" />
        </div>
        
        <div class="flex-shrink-0 flex items-center gap-2">
          <slot name="headerActions"></slot>
          <TasksIndicator />
          <NotificationsIndicator />
        </div>
      </header>
      
      <!-- Main content area -->
      <main class="flex-1 overflow-auto p-4 md:p-6">
        <!-- System announcements banner -->
        <div v-if="systemMessage" 
             class="mb-6 rounded-lg border p-4 bg-amber-50 text-amber-900 dark:bg-amber-950 dark:text-amber-50">
          <div class="flex">
            <InfoIcon class="mr-3 h-5 w-5 flex-shrink-0" aria-hidden="true" />
            <div>
              <h3 class="font-medium">{{ $t('System Announcement') }}</h3>
              <div class="mt-1 text-sm" v-html="systemMessage"></div>
            </div>
          </div>
        </div>
        
        <slot />
      </main>
      
      <!-- Footer -->
      <footer v-if="showFooter" class="border-t px-4 py-3 text-center text-xs text-muted-foreground">
        <div>© {{ currentYear }} {{ appName }} - {{ $t('Version') }} {{ appVersion }}</div>
      </footer>
      
      <!-- Bottom action bar for mobile screens -->
      <div v-if="showMobileActionBar" 
           class="md:hidden fixed bottom-0 left-0 right-0 border-t bg-background p-2 flex items-center justify-around">
        <slot name="mobileActions">
          <!-- Default mobile actions -->
          <Button variant="ghost" size="sm" class="flex-col h-14 w-16" as="a" :href="route('dashboard')">
            <HomeIcon class="h-5 w-5" aria-hidden="true" />
            <span class="text-xs mt-1">{{ $t('Home') }}</span>
          </Button>
          
          <Button v-if="createUrl" variant="ghost" size="sm" class="flex-col h-14 w-16" as="a" :href="createUrl">
            <PlusIcon class="h-5 w-5" aria-hidden="true" />
            <span class="text-xs mt-1">{{ $t('New') }}</span>
          </Button>
          
          <Button variant="ghost" size="sm" class="flex-col h-14 w-16" as="a" :href="route('profile')">
            <UserIcon class="h-5 w-5" aria-hidden="true" />
            <span class="text-xs mt-1">{{ $t('Profile') }}</span>
          </Button>
        </slot>
      </div>
    </SidebarInset>
  </SidebarProvider>
  
  <!-- Toast notifications -->
  <Toaster />
</template>

<script setup lang="ts">
import { Head, Link, router, usePage } from "@inertiajs/vue3";
import { useMessage } from "naive-ui";
import { breakpointsTailwind, useBreakpoints, useOnline, useTimeoutFn } from "@vueuse/core";
import { computed, onMounted, watch, onBeforeUnmount, ref, nextTick } from "vue";
import { 
  InfoIcon, 
  HomeIcon, 
  PlusIcon, 
  UserIcon,
} from 'lucide-vue-next';
import { trans as $t } from "laravel-vue-i18n";

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

// App details
const pageTitle = computed(() => props.title || usePage().component.split('/').pop() || 'Dashboard');
const appName = computed(() => {
  return usePage().props.app.locale === 'en' ? 'My VU SR' : 'Mano VU SA';
});
const appVersion = computed(() => usePage().props.app.version || '1.0.0');
const currentYear = new Date().getFullYear();

// System message (announcements)
const systemMessage = computed(() => usePage().props.app?.systemMessage || null);

// Initialize breadcrumb state for the entire admin application
const breadcrumbState = createBreadcrumbState();

// Generate current page breadcrumbs based on the active component
const defaultBreadcrumbs = computed(() => {
  const currentComponent = usePage().component;
  const isHome = currentComponent === 'Admin/ShowAdminHome';
  
  // No breadcrumbs on home page
  if (isHome) {
    return [];
  }
  
  // Return only the current page breadcrumb by default
  return pageTitle.value ? [{ label: pageTitle.value, href: undefined }] : [];
});

// Handle breadcrumb initialization for new pages with prop-provided breadcrumbs
watch(() => props.breadcrumbs, (newBreadcrumbs) => {
  if (newBreadcrumbs?.length) {
    breadcrumbState.setBreadcrumbs(newBreadcrumbs, 'component');
  }
}, { immediate: true });

// Initialize default breadcrumbs if no explicit ones exist yet
onMounted(() => {
  if (!breadcrumbState.hasBreadcrumbs.value && defaultBreadcrumbs.value.length > 0) {
    breadcrumbState.setBreadcrumbs(defaultBreadcrumbs.value, 'default');
  }
});

// Listen for navigation events
onMounted(() => {
  router.on('start', () => {
    // Don't clear breadcrumbs during navigation - this helps them persist
  });
  
  router.on('finish', () => {
    // Set default breadcrumbs only if no component has set them
    nextTick(() => {
      // Component-specific breadcrumbs should already be set by this point
      // Only add default breadcrumbs if nothing else has been set
      if (!breadcrumbState.hasBreadcrumbs.value && defaultBreadcrumbs.value.length > 0) {
        breadcrumbState.setBreadcrumbs(defaultBreadcrumbs.value, 'default');
      }
    });
  });
});

const mounted = ref(false);
const online = useOnline();
const message = useMessage();

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

// Watch online status after component is mounted
watch(online, (isOnline) => {
  if (!mounted.value) return;
  handleOnlineStatus();
});

// Handle flash messages
watch(() => usePage().props.flash.success, (msg) => {
  if (msg) {
    message.success(msg);
    usePage().props.flash.success = null;
  }
});

watch(() => usePage().props.flash.info, (msg) => {
  if (msg) {
    message.info(msg);
    usePage().props.flash.info = null;
  }
});

// Debug mode for errors (only in development)
if (usePage().props.app.env === "local") {
  watch(() => usePage().props.errors, (errors) => {
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
});

// Clean up event listeners
onBeforeUnmount(() => {
  window.removeEventListener("resize", updateIsMobile);
});
</script>
