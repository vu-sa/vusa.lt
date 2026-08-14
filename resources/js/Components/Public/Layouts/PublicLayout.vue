<template>
  <!-- https://www.joshwcomeau.com/css/full-bleed/ -->
  <!-- Head metadata (title, description, canonical, Open Graph, hreflang, …) is owned server-side
       by Laravel Head — see app.blade.php's @head directive and PublicController::applyPageHead().
       Inertia adopts and keeps those elements in sync on SPA navigation via serverHead: true in
       public.ts; no client-side <Head> component is needed here. -->
  <div class="@container min-h-screen flex flex-col bg-zinc-50 dark:bg-zinc-900 font-public">
    <!-- Staging environment warning banner -->
    <StagingBanner class="mx-2 mt-2 sm:mx-4" />

    <!-- Skip to main content link - positioned first for keyboard navigation -->
    <a href="#main-content" class="sr-only focus:not-sr-only focus:absolute focus:top-4 focus:left-4 focus:z-[9999] focus:bg-white focus:text-zinc-900 focus:px-4 focus:py-2 focus:rounded-md focus:shadow-lg focus:border-2 focus:border-vusa-red dark:focus:bg-zinc-800 dark:focus:text-zinc-100">
      {{ $t('accessibility.skip_to_main_content') }}
    </a>

    <div
      class="flex-1 flex flex-col text-zinc-800 antialiased dark:text-zinc-300 container px-0 @container/main">
      <MainNavigation :is-theme-dark="isDark" />

      <main id="main-content" class="pb-8" :class="mainContentMarginClass">
        <!-- Centralized breadcrumb display -->
        <div v-if="breadcrumbState.breadcrumbs.value.length > 0" :class="breadcrumbWrapperClass">
          <PublicBreadcrumbs />
        </div>

        <!-- <Suspense> -->
        <div>
          <div :class="contentWrapperClass">
            <slot />
          </div>
          <div v-if="
            $page.props.tenant?.banners &&
              $page.props.tenant.banners.length > 0
          " class="mx-auto mt-8 max-w-7xl">
            <BannerCarousel :banners="$page.props.tenant?.banners" />
          </div>
        </div>
      </main>

      <FadeTransition appear>
        <ConsentCard v-if="!cookieConsentDecided" />
      </FadeTransition>
    </div>

    <!-- Footer outside container for full-width -->
    <SiteFooter class="mt-auto" />
  </div>

  <!-- Toast notifications -->
  <Toaster rich-colors />
</template>

<script setup lang="ts">
/**
 * PublicLayout - Main layout component for public pages
 */
import { computed, defineAsyncComponent, onMounted, ref, watch, nextTick } from 'vue';
import { useDark } from '@vueuse/core';
import { usePage, router } from '@inertiajs/vue3';

import SiteFooter from '../FullWidth/SiteFooter.vue';

import FadeTransition from '@/Components/Transitions/FadeTransition.vue';
import PublicBreadcrumbs from '@/Components/Public/PublicBreadcrumbs.vue';
import { createBreadcrumbState } from '@/Composables/useBreadcrumbsUnified';
import { Toaster } from '@/Components/ui/sonner';
import { useToasts } from '@/Composables/useToasts';
import { useCookieConsent } from '@/Composables/useCookieConsent';
import { useSecondMenu } from '@/Composables/useSecondMenu';
import 'vue-sonner/style.css';

// Critical path components - load synchronously for faster initial render
import MainNavigation from '@/Components/Public/Layouts/MainNavigation.vue';
import StagingBanner from '@/Components/StagingBanner.vue';

// Use existing Skeleton component for consistency
import { Skeleton } from '@/Components/ui/skeleton';

// Non-critical components - load asynchronously
const BannerCarousel = defineAsyncComponent({
  loader: () => import('../FullWidth/BannerCarousel.vue'),
  loadingComponent: {
    components: { Skeleton },
    template: '<div class="mx-8 my-8"><Skeleton class="h-32 rounded" /></div>',
  },
  delay: 200,
});

const ConsentCard = defineAsyncComponent({
  loader: () => import('../ConsentCard.vue'),
  delay: 0, // Load immediately when needed
});

const isDark = useDark();

// Use composable for navigation state
const { hasSecondMenu } = useSecondMenu();

// Initialize breadcrumb state for public pages
const breadcrumbState = createBreadcrumbState('public');

// Layout width configuration
const layoutWidth = computed(() => {
  const page = usePage();
  // Check if page has specified layout width
  return page.props.layoutWidth || 'standard';
});

// Computed classes for wrapper elements
const contentWrapperClass = computed(() => {
  const width = layoutWidth.value;
  const baseClasses = 'pt-4 md:pt-6 lg:pt-8';

  switch (width) {
    case 'wide':
      return `wrapper-wide ${baseClasses}`;
    case 'full':
      return `wrapper-full ${baseClasses}`;
    case 'content':
      return `wrapper-content ${baseClasses}`;
    default:
      return `wrapper ${baseClasses}`;
  }
});

/**
 * Top margin for <main> — clears the fixed MainNavigation header only.
 *
 * Depends solely on navbar height: the SecondMenu adds a row on desktop (it is
 * hidden on mobile via max-md:hidden, so the mobile margin is the same either
 * way). It deliberately does NOT depend on page content such as breadcrumbs —
 * that previously created a hidden coupling where calling usePageBreadcrumbs()
 * implicitly shifted the entire page down. Breadcrumb spacing is now handled
 * by the breadcrumb wrapper itself (see {@link breadcrumbWrapperClass}).
 */
const mainContentMarginClass = computed(() => {
  return hasSecondMenu.value
    ? 'mt-20 md:mt-32'
    : 'mt-20';
});

const breadcrumbWrapperClass = computed(() => {
  // Consistent top padding gives the breadcrumb bar breathing room below the
  // navbar. Breadcrumbs always use the standard wrapper width for consistency.
  // Reduced from md:pt-6/lg:pt-8 — the gap above the trail read as too generous on
  // non-mobile once compared against the rest of the page's vertical rhythm; mobile
  // is unchanged.
  const baseClasses = 'pt-4 md:pt-4 lg:pt-5';

  return `wrapper ${baseClasses}`;
});

// Clear breadcrumbs when on home page
watch(() => usePage().component, (component) => {
  if (component === 'Public/HomePage') {
    breadcrumbState.clear();
  }
}, { immediate: true });

const mounted = ref(false);

const { decided: cookieConsentDecided } = useCookieConsent();

// Initialize toast system for flash messages
const toasts = useToasts();

// Handle validation errors (show only first error for public pages)
watch(() => usePage().props.errors, (errors) => {
  if (errors && typeof errors === 'object' && Object.keys(errors).length > 0) {
    // In public page, show only one error message at a time
    const entries = Object.entries(errors);
    const firstEntry = entries[0];
    if (firstEntry) {
      const [key, value] = firstEntry;
      if (key && value) {
        toasts.error(`${key}: ${value}`);
      }
    }
  }
});

// Listen for navigation events to handle breadcrumb persistence
onMounted(() => {
  mounted.value = true;

  // Initialize flash message handling
  toasts.initializeToasts();

  // Setup router navigation events for breadcrumbs
  router.on('start', () => {
    // Don't clear breadcrumbs during navigation - helps with transition
  });

  router.on('finish', () => {
    // Note: We no longer clear breadcrumbs on navigation to prevent flashing
    // Individual pages will set their own breadcrumbs using usePageBreadcrumbs()
  });

  // Load UserWay immediately for accessibility (needs to modify styles early)
  const userWayScript = document.createElement('script');
  userWayScript.setAttribute('data-account', '5OC3pQZI6r');
  userWayScript.setAttribute('src', 'https://cdn.userway.org/widget.js');
  userWayScript.async = true;
  document.head.appendChild(userWayScript);

  // Defer Tawk.to loading - not critical for initial experience
  const loadTawkTo = () => {
    const lang = usePage().props.app.locale;
    const Tawk_SRC = lang === 'lt' ? 'default' : '1foc6rga3';

    const tawkScript = document.createElement('script');
    tawkScript.async = true;
    tawkScript.src = `https://embed.tawk.to/5f71b135f0e7167d00145612/${Tawk_SRC}`;
    tawkScript.charset = 'UTF-8';
    tawkScript.setAttribute('crossorigin', 'anonymous');
    document.head.appendChild(tawkScript);
  };

  // Load Tawk.to after user interaction or after 5 seconds (whichever comes first)
  let tawkLoaded = false;
  const loadTawkOnce = () => {
    if (!tawkLoaded) {
      tawkLoaded = true;
      loadTawkTo();
      // Remove event listeners after loading
      document.removeEventListener('scroll', loadTawkOnce);
      document.removeEventListener('click', loadTawkOnce);
      document.removeEventListener('touchstart', loadTawkOnce);
    }
  };

  // Listen for user interaction
  document.addEventListener('scroll', loadTawkOnce, { once: true, passive: true });
  document.addEventListener('click', loadTawkOnce, { once: true });
  document.addEventListener('touchstart', loadTawkOnce, { once: true, passive: true });

  // Fallback: load after 5 seconds if no interaction
  setTimeout(loadTawkOnce, 5000);
});
</script>
