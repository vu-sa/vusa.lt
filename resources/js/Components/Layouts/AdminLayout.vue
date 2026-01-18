<template>
  <div class="bg-background">
    <Head :title />

    <!-- Staging environment warning banner -->
    <StagingBanner />

    <SidebarProvider>
      <AppSidebar />
      <SidebarInset class="flex flex-col">
        <!-- Header with breadcrumbs and actions -->
        <header class="sticky top-0 z-40 flex h-16 shrink-0 items-center justify-between border-b bg-background px-6">
          <div class="flex items-center flex-1 gap-3">
            <SidebarTrigger class="-ml-1" />
            <Separator orientation="vertical" class="mr-2 h-4" />
            <AdminBreadcrumbs />
          </div>

          <div class="flex items-center gap-2">
            <slot name="headerActions" />
            <CommandPaletteTrigger />
            <PWAStatusButton />
            <SpotlightPopover
              v-if="hasTour"
              :title="$t('tutorials.help_button_spotlight.title')"
              :description="$t('tutorials.help_button_spotlight.description')"
              :is-dismissed="helpButtonSpotlight.isDismissed.value"
              position="bottom"
              @dismiss="helpButtonSpotlight.dismiss"
            >
              <TooltipProvider>
                <Tooltip>
                  <TooltipTrigger as-child>
                    <Button 
                      variant="outline" 
                      size="icon" 
                      class="rounded-full" 
                      data-tour="help-button"
                      @click="handleHelpClick"
                    >
                      <HelpCircle class="h-4 w-4" />
                      <span class="sr-only">{{ $t('Kaip veikia?') }}</span>
                    </Button>
                  </TooltipTrigger>
                  <TooltipContent>{{ $t('Pradėti interaktyvų vadovą') }}</TooltipContent>
                </Tooltip>
              </TooltipProvider>
            </SpotlightPopover>
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

    <!-- PWA Install Banner (smart trigger) -->
    <InstallBanner />
    
    <!-- PWA Update Available Banner (only shown in PWA mode) -->
    <UpdateBanner />
    
    <!-- PWA Bottom Navigation Bar (shown only when installed as PWA on mobile) -->
    <nav 
      v-if="isPWA && isMobile" 
      class="fixed bottom-0 left-0 right-0 z-50 border-t bg-background/95 backdrop-blur supports-[backdrop-filter]:bg-background/80"
      :style="{ paddingBottom: 'env(safe-area-inset-bottom, 0px)' }"
    >
      <div class="flex items-center justify-around h-16 px-2">
        <Link 
          :href="route('dashboard')" 
          class="flex flex-col items-center justify-center flex-1 h-full gap-1 text-muted-foreground transition-colors"
          :class="{ 'text-primary': isCurrentRoute('dashboard') }"
        >
          <HomeIcon class="h-5 w-5" />
          <span class="text-[10px] font-medium">{{ $t('Pradžia') }}</span>
        </Link>
        
        <Link 
          :href="route('dashboard.atstovavimas')" 
          class="flex flex-col items-center justify-center flex-1 h-full gap-1 text-muted-foreground transition-colors"
          :class="{ 'text-primary': isCurrentRoute('dashboard.atstovavimas') }"
        >
          <GraduationCapIcon class="h-5 w-5" />
          <span class="text-[10px] font-medium">ViSAK</span>
        </Link>
        
        <button 
          type="button"
          class="flex flex-col items-center justify-center flex-1 h-full gap-1"
          @click="showQuickCreate = true"
        >
          <div class="flex items-center justify-center w-12 h-12 -mt-4 rounded-full bg-primary text-primary-foreground shadow-lg">
            <PlusIcon class="h-6 w-6" />
          </div>
        </button>
        
        <Link 
          :href="route('notifications.index')" 
          class="relative flex flex-col items-center justify-center flex-1 h-full gap-1 text-muted-foreground transition-colors"
          :class="{ 'text-primary': isCurrentRoute('notifications.index') }"
        >
          <div class="relative">
            <BellIcon class="h-5 w-5" />
            <span 
              v-if="unreadNotificationsCount > 0" 
              class="absolute -top-1 -right-1 flex h-4 w-4 items-center justify-center rounded-full bg-destructive text-[10px] font-medium text-destructive-foreground"
            >
              {{ unreadNotificationsCount > 9 ? '9+' : unreadNotificationsCount }}
            </span>
          </div>
          <span class="text-[10px] font-medium">{{ $t('Pranešimai') }}</span>
        </Link>
        
        <Link 
          :href="route('profile')" 
          class="flex flex-col items-center justify-center flex-1 h-full gap-1 text-muted-foreground transition-colors"
          :class="{ 'text-primary': isCurrentRoute('profile') }"
        >
          <UserIcon class="h-5 w-5" />
          <span class="text-[10px] font-medium">{{ $t('Profilis') }}</span>
        </Link>
      </div>
    </nav>
    
    <!-- Command Palette (global Cmd+K / Ctrl+K search) -->
    <AdminCommandPalette />

    <!-- Quick Create Sheet (for PWA bottom nav) -->
    <Sheet v-model:open="showQuickCreate">
      <SheetContent side="bottom" class="rounded-t-xl">
        <SheetHeader>
          <SheetTitle>{{ $t('Sukurti naują') }}</SheetTitle>
        </SheetHeader>
        <div class="grid gap-2 py-4">
          <Button 
            v-if="can?.create?.meeting" 
            variant="ghost" 
            class="justify-start h-12" 
            @click="navigateAndClose(route('meetings.create'))"
          >
            <CalendarPlusIcon class="h-5 w-5 mr-3" />
            {{ $t('forms.meeting') }}
          </Button>
          <Button 
            v-if="can?.create?.news" 
            variant="ghost" 
            class="justify-start h-12" 
            @click="navigateAndClose(route('news.create'))"
          >
            <FileTextIcon class="h-5 w-5 mr-3" />
            {{ $t('forms.news') }}
          </Button>
          <Button 
            v-if="can?.create?.reservation" 
            variant="ghost" 
            class="justify-start h-12" 
            @click="navigateAndClose(route('reservations.create'))"
          >
            <Building2Icon class="h-5 w-5 mr-3" />
            {{ $t('forms.reservation') }}
          </Button>
        </div>
      </SheetContent>
    </Sheet>
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
  BellIcon,
  GraduationCapIcon,
  CalendarPlusIcon,
  FileTextIcon,
  Building2Icon,
} from 'lucide-vue-next';
import { usePWA } from '@/Composables/usePWA';
import { Sheet, SheetContent, SheetHeader, SheetTitle } from '@/Components/ui/sheet';
import { trans as $t } from "laravel-vue-i18n";

import { useToasts } from '@/Composables/useToasts';
import 'vue-sonner/style.css'

import AppSidebar from '@/Components/AppSidebar.vue'
import StagingBanner from '@/Components/StagingBanner.vue'
import InstallBanner from '@/Components/PWA/InstallBanner.vue'
import UpdateBanner from '@/Components/PWA/UpdateBanner.vue'
import PWAStatusButton from '@/Components/PWA/StatusButton.vue'
import TasksIndicator from '@/Components/TasksIndicator.vue'
import NotificationsIndicator from '@/Components/NotificationsIndicator.vue'
import { Separator } from '@/Components/ui/separator'
import { Button } from '@/Components/ui/button'
import {
  SidebarInset,
  SidebarProvider,
  SidebarTrigger,
} from '@/Components/ui/sidebar'
import AdminBreadcrumbs from '@/Components/AdminBreadcrumbs.vue';
import { createBreadcrumbState } from '@/Composables/useBreadcrumbsUnified';
import type { BreadcrumbItem } from '@/Composables/useBreadcrumbsUnified';
import { createTourProvider } from '@/Composables/useTourProvider';
import { useFeatureSpotlight } from '@/Composables/useFeatureSpotlight';
import SpotlightPopover from '@/Components/Onboarding/SpotlightPopover.vue';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/Components/ui/tooltip';
import { Toaster } from "@/Components/ui/sonner";
import { createCommandPaletteProvider } from '@/Composables/useCommandPalette';
import AdminCommandPalette from '@/Components/CommandPalette/AdminCommandPalette.vue';
import CommandPaletteTrigger from '@/Components/CommandPalette/CommandPaletteTrigger.vue';

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

// PWA state
const { isPWA } = usePWA();
const showQuickCreate = ref(false);

// Permissions for quick create
const can = computed(() => usePage().props.auth?.can);

// Unread notifications count
const unreadNotificationsCount = computed(() => {
  const notifications = usePage().props.auth?.user?.unreadNotifications;
  return Array.isArray(notifications) ? notifications.length : 0;
});

// Check if current route matches
function isCurrentRoute(routeName: string): boolean {
  try {
    return route().current(routeName);
  } catch {
    return false;
  }
}

// Navigate and close quick create sheet
function navigateAndClose(url: string) {
  showQuickCreate.value = false;
  router.visit(url);
}

// Initialize breadcrumb state for the entire admin application
const breadcrumbState = createBreadcrumbState('admin');

// Initialize tour provider - pages can register their tours via provideTour()
const { hasTour, startTour: startPageTour, clearTour } = createTourProvider();

// Initialize command palette provider for global Cmd+K / Ctrl+K search
createCommandPaletteProvider();

// Spotlight for help button - shows once to draw attention to the help feature
const helpButtonSpotlight = useFeatureSpotlight('help-button-v1');

// Handle help button click: dismiss spotlight and start tour
function handleHelpClick() {
  helpButtonSpotlight.dismiss();
  startPageTour(true); // true = voluntary tour
}

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
