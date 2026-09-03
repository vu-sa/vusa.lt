<template>
  <!-- https://www.joshwcomeau.com/css/full-bleed/ -->
  <!-- Head metadata (title, description, canonical, Open Graph, hreflang, …) is owned server-side
       by Laravel Head — see app.blade.php's @head directive and PublicController::applyPageHead().
       Inertia adopts and keeps those elements in sync on SPA navigation via serverHead: true in
       public.ts; no client-side <Head> component is needed here. -->
  <!-- `overflow-x-clip` absorbs the half-scrollbar overhang of `.rc-viewport` bands (the
       hero). `clip` rather than `hidden`: it does not create a scroll container, so sticky
       descendants keep working. -->
  <div class="@container min-h-screen flex flex-col overflow-x-clip bg-background text-foreground font-public">
    <!-- Skip to main content link - positioned first for keyboard navigation -->
    <a
      href="#main-content"
      :class="[
        'sr-only focus:not-sr-only focus:absolute focus:top-4 focus:left-4 focus:z-[9999]',
        'focus:bg-background focus:text-foreground focus:px-4 focus:py-2 focus:border-2 focus:border-brand',
      ]"
    >
      {{ $t('accessibility.skip_to_main_content') }}
    </a>

    <!-- Outside the `container` wrapper below on purpose. That wrapper is a `@container/main`,
         which establishes a containing block for `position: fixed` descendants — a fixed header
         inside it is clamped to the container's max-width instead of spanning the viewport. -->
    <MainNavigation :is-theme-dark="isDark" />

    <div
      class="flex-1 flex flex-col antialiased container px-0 @container/main">
      <main id="main-content" class="pb-8" :class="mainContentMarginClass">
        <!-- Centralized breadcrumb display. Skipped when the page has claimed the trail for its
             own title band (placement: 'band'), so a detail page shows one trail, not two. -->
        <div
          v-if="breadcrumbState.breadcrumbs.value.length > 0 && breadcrumbState.placement.value === 'layout'"
          :class="breadcrumbWrapperClass"
        >
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

  <!-- Toast notifications (flash messages + the persistent public edit-link toast) -->
  <Toaster />
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
import { usePublicEditLinkToast } from '@/Composables/usePublicEditLinkToast';
import { usePublicStagingToast } from '@/Composables/usePublicStagingToast';
import { useSecondMenu } from '@/Composables/useSecondMenu';
import MainNavigation from '@/Components/Public/Layouts/MainNavigation.vue';
import { Skeleton } from '@/Components/ui/skeleton';
import 'vue-sonner/style.css';

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
  // Must track MainNavigation's real height: a 4rem primary bar, plus a 2.75rem SecondMenu row
  // that only renders from `md` up (max-md:hidden), hence the same mobile value either way.
  return hasSecondMenu.value
    ? 'mt-16 md:mt-[6.75rem]'
    : 'mt-16';
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

// Persistent "edit this page" toast for signed-in editors (no-op for guests —
// the backend never shares the link for them)
usePublicEditLinkToast(() => usePage().props.publicEditLink);
usePublicStagingToast(() => usePage().props.staging);

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

  // Reader preferences (text size, contrast, link underlines) are served by the in-house
  // AccessibilityMenu in the header — see Components/Public/Base/AccessibilityMenu.vue. The
  // third-party UserWay widget it replaced used to be injected here.

  // Tawk.to live chat disabled — its bottom-right widget kept colliding with other
  // floating UI. Re-enable by restoring this block.
  /* const loadTawkTo = () => {
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
  setTimeout(loadTawkOnce, 5000); */
});
</script>
