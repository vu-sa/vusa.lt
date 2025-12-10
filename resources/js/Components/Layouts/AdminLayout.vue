<template>
  <div class="bg-background">
    <Head :title />

    <SidebarProvider>
      <AppSidebar />
      <SidebarInset class="flex flex-col">
        <!-- Header with breadcrumbs and actions -->
        <header class="sticky top-0 z-40 flex h-16 shrink-0 items-center justify-between border-b bg-background px-6">
          <div class="flex items-center flex-1 gap-3">
            <SidebarTrigger class="-ml-1" />
            <Separator orientation="vertical" class="mr-2 h-4" />
            <UnifiedBreadcrumbs />
          </div>

          <div class="flex items-center gap-2">
            <slot name="headerActions" />
            <TooltipProvider v-if="hasTour">
              <Tooltip>
                <TooltipTrigger as-child>
                  <Button 
                    variant="outline" 
                    size="icon" 
                    class="rounded-full" 
                    data-tour="help-button"
                    @click="startTour"
                  >
                    <HelpCircle class="h-4 w-4" />
                    <span class="sr-only">{{ $t('Kaip veikia?') }}</span>
                  </Button>
                </TooltipTrigger>
                <TooltipContent>{{ $t('Pradėti interaktyvų vadovą') }}</TooltipContent>
              </Tooltip>
            </TooltipProvider>
            <TasksIndicator />
            <NotificationsIndicator />
          </div>
        </header>

        <!-- Single scroll container -->
        <main class="flex-1 min-w-0 overflow-auto" style="scroll-behavior: smooth;">
          <div class="min-h-full p-6">
        <!-- System announcements banner -->
        <div v-if="systemMessage"
          class="mb-6 rounded-lg border p-4 bg-amber-50 text-amber-900 dark:bg-amber-950 dark:text-amber-50">
          <div class="flex">
            <InfoIcon class="mr-3 h-5 w-5 flex-shrink-0" aria-hidden="true" />
            <div>
              <h3 class="font-medium">
                {{ $t('System Announcement') }}
              </h3>
              <div class="mt-1 text-sm" v-html="systemMessage" />
            </div>
          </div>
        </div>

            <slot />
          </div>
        </main>

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
    <Toaster rich-colors />
  </div>
</template>

<script setup lang="ts">
import { Head, Link, router, usePage } from "@inertiajs/vue3";
import { breakpointsTailwind, useBreakpoints, useOnline, useTimeoutFn } from "@vueuse/core";
import { computed, onMounted, watch, onBeforeUnmount, ref, nextTick } from "vue";
import {
  InfoIcon,
  HomeIcon,
  PlusIcon,
  UserIcon,
  HelpCircle,
} from 'lucide-vue-next';
import { trans as $t } from "laravel-vue-i18n";

import { useToasts } from '@/Composables/useToasts';
import 'vue-sonner/style.css'

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
import UnifiedBreadcrumbs from '@/Components/UnifiedBreadcrumbs.vue';
import { createBreadcrumbState } from '@/Composables/useBreadcrumbsUnified';
import type { BreadcrumbItem } from '@/Composables/useBreadcrumbsUnified';
import { createTourProvider } from '@/Composables/useTourProvider';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/Components/ui/tooltip';
import { Toaster } from "@/Components/ui/sonner";

const props = withDefaults(defineProps<{
  title?: string;
  createUrl?: string | null;
  breadcrumbs?: BreadcrumbItem[];
  showMobileActionBar?: boolean;
}>(), {
  showMobileActionBar: false
});

// System message (announcements)
const systemMessage = computed(() => usePage().props.app?.systemMessage || null);

// Initialize breadcrumb state for the entire admin application
const breadcrumbState = createBreadcrumbState('admin');

// Initialize tour provider - pages can register their tours via provideTour()
const { hasTour, startTour, clearTour } = createTourProvider();

// Track the current page component to detect navigation
const currentComponent = ref(usePage().component);

// Clear tour registration when navigating to a new page
// Use flush: 'sync' to ensure this runs immediately when the prop changes,
// before the new page component's setup runs and registers its tour
watch(() => usePage().component, (component, oldComponent) => {
  // Clear breadcrumbs when on home page
  if (component === 'Admin/ShowAdminHome') {
    breadcrumbState.clear();
  }
  
  // Only clear tour when actually navigating (not on initial load)
  if (oldComponent && oldComponent !== component) {
    clearTour();
  }
  currentComponent.value = component;
}, { flush: 'sync' });

// Handle breadcrumb initialization for new pages with prop-provided breadcrumbs
watch(() => props.breadcrumbs, (newBreadcrumbs) => {
  if (newBreadcrumbs?.length) {
    breadcrumbState.set(newBreadcrumbs);
  }
}, { immediate: true });

// Listen for navigation events - don't clear breadcrumbs to avoid flashing
onMounted(() => {
  // Note: We no longer clear breadcrumbs on navigation start to prevent flashing
  // Individual pages will set their own breadcrumbs using usePageBreadcrumbs()
});

const mounted = ref(false);
const online = useOnline();
let offlineToastId: string | number | undefined;

// Initialize unified toast system
const toasts = useToasts();

// Handle online/offline status with Sonner
const handleOnlineStatus = (isOnline: boolean) => {
  if (!isOnline) {
    // Show persistent offline toast
    offlineToastId = toasts.error($t("Your internet connection was lost."));
  } else if (mounted.value) {
    // Dismiss offline toast and show restoration message
    if (offlineToastId) {
      // Note: toast.dismiss() is not available in our composable, but the success message will show
      offlineToastId = undefined;
    }
    toasts.success($t("Your internet connection was restored."));
  }
};

// Watch online status after component is mounted
watch(online, (isOnline) => {
  if (!mounted.value) return;
  handleOnlineStatus(isOnline);
});

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
  
  // Initialize flash message handling
  toasts.initializeToasts();
});

// Clean up event listeners
onBeforeUnmount(() => {
  window.removeEventListener("resize", updateIsMobile);
});
</script>
