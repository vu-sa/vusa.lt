<template>
  <!-- `font-admin` is dropped once the new shell is opted in: its [data-surface="admin"] scope
       sets `font-family: var(--font-public)` on <html> itself (so teleported content inherits
       it too), and font-admin here would otherwise outrank that inherited value. -->
  <div class="bg-background" :class="{ 'font-admin': !uiPreferences.newShell.value }">
    <Head :title />

    <AdminShell v-if="uiPreferences.newShell.value">
      <slot />
    </AdminShell>
    <LegacyAdminShell
      v-else
      :create-url
      :show-mobile-action-bar
      :has-tour
      @start-tour="startPageTour(true)"
    >
      <slot />
    </LegacyAdminShell>

    <!-- Guided action window. Outside both shells: it needs neither a sidebar nor its provider. -->
    <ActionWindow />
    <StartFmDock />

    <!-- Toast notifications -->
    <Toaster rich-colors />

    <!-- PWA Install Banner (smart trigger) -->
    <InstallBanner />

    <!-- PWA Update Available Banner (only shown in PWA mode) -->
    <UpdateBanner />

    <!-- Command Palette (global Cmd+K / Ctrl+K search) -->
    <AdminCommandPalette />
  </div>
</template>

<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import { useOnline, useDebounceFn } from '@vueuse/core';
import { computed, onMounted, watch, ref, nextTick } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';

import { usePWA } from '@/Composables/usePWA';
import { useToasts } from '@/Composables/useToasts';
import 'vue-sonner/style.css';

import InstallBanner from '@/Components/PWA/InstallBanner.vue';
import UpdateBanner from '@/Components/PWA/UpdateBanner.vue';
import { Toaster } from '@/Components/ui/sonner';
import AdminShell from '@/Components/Layouts/Shell/AdminShell.vue';
import StartFmDock from '@/Components/Layouts/Shell/StartFmDock.vue';
import LegacyAdminShell from '@/Components/Layouts/LegacyAdminShell.vue';
import { createBreadcrumbState } from '@/Composables/useBreadcrumbsUnified';
import type { BreadcrumbItem } from '@/Composables/useBreadcrumbsUnified';
import { createTourProvider } from '@/Composables/useTourProvider';
import { createActionWindowProvider } from '@/Composables/useActionWindow';
import { createCommandPaletteProvider } from '@/Composables/useCommandPalette';
import { createUIPreferencesProvider } from '@/Composables/useUIPreferences';
import { createStartFmProvider } from '@/Composables/useStartFm';
import AdminCommandPalette from '@/Components/CommandPalette/AdminCommandPalette.vue';
import ActionWindow from '@/Components/ActionWindow/ActionWindow.vue';

const props = withDefaults(defineProps<{
  title?: string;
  createUrl?: string | null;
  breadcrumbs?: BreadcrumbItem[];
  showMobileActionBar?: boolean;
}>(), {
  showMobileActionBar: false,
});

// PWA state
const { setAppBadge } = usePWA();

// Unread notifications count
const unreadNotificationsCount = computed(() => {
  const notifications = usePage().props.auth?.user?.unreadNotifications;
  return Array.isArray(notifications) ? notifications.length : 0;
});

// Update PWA app badge when notification count changes
watch(unreadNotificationsCount, (count) => {
  setAppBadge(count);
}, { immediate: true });

// Initialize breadcrumb state for the entire admin application
const breadcrumbState = createBreadcrumbState('admin');

// Initialize tour provider - pages can register their tours via provideTour()
const { hasTour, startTour: startPageTour, clearTour } = createTourProvider();

// Initialize UI preferences provider (sidebar customization + recently visited)
const uiPreferences = createUIPreferencesProvider();

// The action window is openable from any admin page, so its state is provided
// here rather than owned by whichever page holds a trigger.
createActionWindowProvider();

// Initialize command palette provider for global Cmd+K / Ctrl+K search.
// Share the recently-visited source so the palette and sidebar stay in sync.
createCommandPaletteProvider({
  recentPages: uiPreferences.recentPages,
  clearRecent: uiPreferences.clearRecent,
});
createStartFmProvider();

// Track every admin page the user visits. The page-specific title comes from
// the breadcrumb trail (the last crumb), which every admin page registers —
// document.title is unreliable (it's just the app name on pages without a
// <Head>). The customization dialog is a Vue overlay (no component change) so
// it is inherently excluded.
const SITE_NAME = /^(mano\s+)?vu\s*sa$/i;

// The admin landing page (/mano) is not worth keeping in history.
const EXCLUDED_ROUTES = new Set(['dashboard']);
const ADMIN_HOME_PATH = /^\/mano\/?$/;

function resolveVisitTitle(): string | undefined {
  const crumbs = breadcrumbState.breadcrumbs.value;
  const last = crumbs[crumbs.length - 1];
  if (crumbs.length > 1 && last?.label && !SITE_NAME.test(last.label)) {
    return last.label;
  }

  const docTitle = document.title.split(/\s[|–-]\s/)[0].trim();
  if (docTitle && !SITE_NAME.test(docTitle)) {
    return docTitle;
  }

  return undefined; // useUIPreferences falls back to catalog label / route
}

watch(() => usePage().component, () => {
  if (typeof window === 'undefined') {
    return;
  }
  const routeName = route().current();
  if (!routeName || EXCLUDED_ROUTES.has(routeName)) {
    return;
  }
  if (ADMIN_HOME_PATH.test(window.location.pathname)) {
    return;
  }
  const params = (route().params ?? {}) as Record<string, unknown>;
  // Identity is the path WITHOUT the query string: query params (?page=2,
  // filters, …) are still the same page and must not create duplicates.
  const url = window.location.pathname;

  // Defer past the child page's setup so its breadcrumbs / <Head> are set.
  nextTick(() => {
    debouncedTrackVisit(routeName, params, resolveVisitTitle(), url);
  });
}, { flush: 'post', immediate: true });

// Debounce rapid navigations (pagination, tab switching, etc.) so we don't
// flood the server with PATCH requests.
const debouncedTrackVisit = useDebounceFn(
  (routeName: string, params: Record<string, unknown>, title: string | undefined, url: string) => {
    uiPreferences.trackVisit(routeName, params, title, url);
  },
  1200,
  { maxWait: 3000 },
);

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
    offlineToastId = toasts.error($t('Your internet connection was lost.'));
  }
  else if (mounted.value) {
    // Dismiss offline toast and show restoration message
    if (offlineToastId) {
      // Note: toast.dismiss() is not available in our composable, but the success message will show
      offlineToastId = undefined;
    }
    toasts.success($t('Your internet connection was restored.'));
  }
};

// Watch online status after component is mounted
watch(online, (isOnline) => {
  if (!mounted.value) return;
  handleOnlineStatus(isOnline);
});

onMounted(() => {
  mounted.value = true;

  // Initialize flash message handling
  toasts.initializeToasts();
});
</script>
