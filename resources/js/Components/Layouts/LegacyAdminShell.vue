<template>
  <div>
    <SidebarProvider v-model:open="sidebarOpen">
      <AppSidebar />
      <SidebarInset class="flex flex-col">
        <StagingBanner class="mx-2 mt-2" />
        <ImpersonateBanner class="mx-2 mt-2" />

        <!-- Header with breadcrumbs and actions -->
        <header class="sticky top-0 z-40 flex h-14 shrink-0 items-center justify-between border-b bg-background px-4 md:h-16 md:px-6 md:rounded-t-xl">
          <div class="flex items-center flex-1 gap-2 md:gap-3 min-w-0">
            <TooltipProvider>
              <Tooltip>
                <TooltipTrigger as-child>
                  <SidebarTrigger class="h-9 w-9 shrink-0 border md:h-7 md:w-7" />
                </TooltipTrigger>
                <TooltipContent side="bottom">
                  <div class="flex items-center gap-2">
                    <span>{{ $t('Perjungti šoninę juostą') }}</span>
                    <kbd class="inline-flex h-5 items-center rounded bg-white/20 dark:bg-black/20 px-1.5 font-mono text-[10px] font-medium">
                      {{ isMac ? '⌘B' : 'Ctrl+B' }}
                    </kbd>
                  </div>
                </TooltipContent>
              </Tooltip>
            </TooltipProvider>
            <Separator orientation="vertical" class="hidden md:block mr-2 h-4" />
            <AdminBreadcrumbs />
          </div>

          <div class="flex items-center gap-1.5 md:gap-2">
            <slot name="headerActions" />
            <DropdownMenu>
              <DropdownMenuTrigger as-child>
                <Button
                  variant="outline"
                  size="icon"
                  class="rounded-full"
                  :aria-label="$t('vusa.lt pagalba')"
                  :title="$t('vusa.lt pagalba')"
                  data-testid="support-requests-menu-trigger"
                >
                  <MessageSquare class="h-4 w-4" />
                </Button>
              </DropdownMenuTrigger>
              <DropdownMenuContent align="end" data-testid="support-requests-menu">
                <DropdownMenuItem as-child>
                  <Link :href="route('mySupportRequests.create')" prefetch>
                    <PlusIcon class="h-4 w-4" />
                    {{ $t('Naujas pranešimas') }}
                  </Link>
                </DropdownMenuItem>
                <DropdownMenuItem as-child>
                  <Link :href="route('mySupportRequests.index')" prefetch>
                    <MessageSquare class="h-4 w-4" />
                    {{ $t('vusa.lt pagalba') }}
                  </Link>
                </DropdownMenuItem>
              </DropdownMenuContent>
            </DropdownMenu>
            <!-- Admin redesign opt-in (.ai/redesign/admin, PR 2.1) — deliberately plain, no
                 spotlight or dialog: nothing here reaches staging before the whole redesign is
                 done. Replaced by an account-menu entry in PR 4.4. -->
            <TooltipProvider>
              <Tooltip>
                <TooltipTrigger as-child>
                  <Button
                    variant="ghost"
                    size="icon"
                    :aria-pressed="newShellEnabled"
                    :aria-label="$t('Naujas dizainas (beta)')"
                    @click="toggleNewShell"
                  >
                    <Palette class="h-4 w-4" :class="{ 'text-brand': newShellEnabled }" />
                  </Button>
                </TooltipTrigger>
                <TooltipContent>{{ $t('Naujas dizainas (beta)') }}</TooltipContent>
              </Tooltip>
            </TooltipProvider>
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
          <!-- Centred, capped measure: on a wide monitor a full-bleed page stretches
               prose and table rows past the point they are comfortable to scan. -->
          <div class="mx-auto min-h-full w-full max-w-[100rem] p-6" :class="{ 'pb-24': isPWA && isMobile }">
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

    <!-- PWA Bottom Navigation Bar (shown only when installed as PWA on mobile) -->
    <nav
      v-if="isPWA && isMobile"
      class="fixed bottom-0 left-0 right-0 z-50 border-t bg-background/95 backdrop-blur supports-[backdrop-filter]:bg-background/80"
      :style="{ paddingBottom: 'env(safe-area-inset-bottom, 0px)' }"
    >
      <div class="flex items-center justify-around h-16 px-2">
        <Link
          :href="route('dashboard')"
          class="flex flex-col items-center justify-center flex-1 h-full gap-1 text-muted-foreground transition-colors active:scale-95 active:opacity-70"
          :class="{ 'text-primary': isCurrentRoute('dashboard') }"
        >
          <HomeIcon class="h-5 w-5" />
          <span class="text-[10px] font-medium">{{ $t('Pradžia') }}</span>
        </Link>

        <Link
          :href="route('dashboard.atstovavimas')"
          class="flex flex-col items-center justify-center flex-1 h-full gap-1 text-muted-foreground transition-colors active:scale-95 active:opacity-70"
          :class="{ 'text-primary': isCurrentRoute('dashboard.atstovavimas') }"
        >
          <GraduationCapIcon class="h-5 w-5" />
          <span class="text-[10px] font-medium">ViSAK</span>
        </Link>

        <button
          type="button"
          class="flex flex-col items-center justify-center flex-1 h-full gap-1 active:scale-90 transition-transform"
          @click="actionWindow.open()"
        >
          <div class="flex items-center justify-center w-12 h-12 -mt-4 rounded-full bg-primary text-primary-foreground shadow-lg">
            <PlusIcon class="h-6 w-6" />
          </div>
        </button>

        <Link
          :href="route('notifications.index')"
          class="relative flex flex-col items-center justify-center flex-1 h-full gap-1 text-muted-foreground transition-colors active:scale-95 active:opacity-70"
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
          class="flex flex-col items-center justify-center flex-1 h-full gap-1 text-muted-foreground transition-colors active:scale-95 active:opacity-70"
          :class="{ 'text-primary': isCurrentRoute('profile') }"
        >
          <UserIcon class="h-5 w-5" />
          <span class="text-[10px] font-medium">{{ $t('Profilis') }}</span>
        </Link>
      </div>
    </nav>
  </div>
</template>

<script setup lang="ts">
/**
 * The pre-redesign admin shell: sidebar, header, PWA-only bottom bar.
 *
 * @deprecated Superseded by `Shell/AdminShell.vue` (PR 4.1–4.2); slated for removal, together
 * with `AppSidebar`, in PR 8.1 when the new shell becomes the default.
 */
import { Link, usePage } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import {
  InfoIcon,
  HomeIcon,
  PlusIcon,
  UserIcon,
  HelpCircle,
  MessageSquare,
  BellIcon,
  GraduationCapIcon,
  Palette,
} from 'lucide-vue-next';
import { trans as $t } from 'laravel-vue-i18n';

import { usePWA } from '@/Composables/usePWA';

import AppSidebar from '@/Components/AppSidebar.vue';
import ImpersonateBanner from '@/Components/ImpersonateBanner.vue';
import StagingBanner from '@/Components/StagingBanner.vue';
import PWAStatusButton from '@/Components/PWA/StatusButton.vue';
import TasksIndicator from '@/Components/TasksIndicator.vue';
import NotificationsIndicator from '@/Components/NotificationsIndicator.vue';
import { Separator } from '@/Components/ui/separator';
import { Button } from '@/Components/ui/button';
import {
  SidebarInset,
  SidebarProvider,
  SidebarTrigger,
} from '@/Components/ui/sidebar';
import AdminBreadcrumbs from '@/Components/AdminBreadcrumbs.vue';
import { useFeatureSpotlight } from '@/Composables/useFeatureSpotlight';
import SpotlightPopover from '@/Components/Onboarding/SpotlightPopover.vue';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/Components/ui/tooltip';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from '@/Components/ui/dropdown-menu';
import { useActionWindow } from '@/Composables/useActionWindow';
import { useNewShellToggle } from '@/Composables/useNewShellToggle';
import { useUIPreferences } from '@/Composables/useUIPreferences';
import CommandPaletteTrigger from '@/Components/CommandPalette/CommandPaletteTrigger.vue';

defineProps<{
  createUrl?: string | null;
  showMobileActionBar?: boolean;
  hasTour?: boolean;
}>();

const emit = defineEmits<{ startTour: [] }>();

// System message (announcements)
const systemMessage = computed(() => usePage().props.app?.systemMessage || null);

const { isPWA } = usePWA();
const actionWindow = useActionWindow();
const uiPreferences = useUIPreferences();
const { enabled: newShellEnabled, toggle: toggleNewShell } = useNewShellToggle();

// Unread notifications count (PWA bar badge)
const unreadNotificationsCount = computed(() => {
  const notifications = usePage().props.auth?.user?.unreadNotifications;
  return Array.isArray(notifications) ? notifications.length : 0;
});

// Check if current route matches
function isCurrentRoute(routeName: string): boolean {
  try {
    return route().current(routeName);
  }
  catch {
    return false;
  }
}

// Sidebar expand/collapse, persisted per-user (cross-device) via ui_preferences.
// SidebarProvider still writes its cookie for fast first paint; the server pref
// is the authoritative source. Cmd+B / trigger / rail all flow through here.
const sidebarOpen = computed({
  get: () => !uiPreferences.sidebarCollapsed.value,
  set: (value: boolean) => uiPreferences.setSidebarCollapsed(!value),
});

const isMac = computed(() => {
  if (typeof navigator === 'undefined') {
    return false;
  }
  return navigator.platform.toUpperCase().indexOf('MAC') >= 0;
});

// Spotlight for help button - shows once to draw attention to the help feature
const helpButtonSpotlight = useFeatureSpotlight('help-button-v1');

// Handle help button click: dismiss spotlight and start tour
function handleHelpClick() {
  helpButtonSpotlight.dismiss();
  emit('startTour');
}

// Detect mobile
const isMobile = ref(false);

const updateIsMobile = () => {
  isMobile.value = window.innerWidth < 768;
};

onMounted(() => {
  updateIsMobile();
  window.addEventListener('resize', updateIsMobile);
});

onBeforeUnmount(() => {
  window.removeEventListener('resize', updateIsMobile);
});
</script>
