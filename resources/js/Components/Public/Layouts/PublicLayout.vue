<template>
  <!-- https://www.joshwcomeau.com/css/full-bleed/ -->
  <!-- Overwrite image meta -->
  <Head>
      <meta head-key="og:image" property="og:image" :content="safeString(usePage().props.seo.image)">
      <meta head-key="image" name="image" :content="safeString(usePage().props.seo.image)">
    </Head>

    <Head>
      <link rel="preconnect" href="https://embed.tawk.to" crossorigin="anonymous">
      <link rel="preload" href="https://cdn.userway.org/widgetapp/images/body_wh.svg" as="image">
      <template v-if="seo.length > 0">
        <template v-for="(headItem, index) in seo" :key="index">
          <!-- Title tags -->
          <template v-if="headItem.tag === 'title'">
            <title>{{ safeString(headItem.inner) }}</title>
          </template>
          <!-- Meta tags - build each one individually based on attributes -->
          <template v-else-if="headItem.tag === 'meta'">
            <!-- Meta with name attribute -->
            <meta 
              v-if="headItem.attributes?.name"
              :head-key="safeString(headItem.attributes.name)"
              :name="safeString(headItem.attributes.name)"
              :content="safeString(headItem.attributes.content)">
            <!-- Meta with property attribute -->
            <meta 
              v-else-if="headItem.attributes?.property"
              :head-key="safeString(headItem.attributes.property)"
              :property="safeString(headItem.attributes.property)"
              :content="safeString(headItem.attributes.content)">
            <!-- Meta with http-equiv attribute -->
            <meta 
              v-else-if="headItem.attributes?.['http-equiv']"
              :head-key="safeString(headItem.attributes['http-equiv'])"
              :http-equiv="safeString(headItem.attributes['http-equiv'])"
              :content="safeString(headItem.attributes.content)">
            <!-- Meta with charset attribute -->
            <meta 
              v-else-if="headItem.attributes?.charset"
              :head-key="`charset-${index}`"
              :charset="safeString(headItem.attributes.charset)">
            <!-- Meta with itemprop attribute -->
            <meta 
              v-else-if="headItem.attributes?.itemprop"
              :head-key="safeString(headItem.attributes.itemprop)"
              :itemprop="safeString(headItem.attributes.itemprop)"
              :content="safeString(headItem.attributes.content)">
          </template>
          <!-- Link tags - build each one individually based on attributes -->
          <template v-else-if="headItem.tag === 'link'">
            <!-- Basic link (rel + href only) -->
            <link 
              v-if="!headItem.attributes?.type && !headItem.attributes?.title && !headItem.attributes?.media && !headItem.attributes?.sizes && !headItem.attributes?.hreflang && !headItem.attributes?.crossorigin"
              :head-key="safeString(headItem.attributes?.rel ?? `link-${index}`)"
              :rel="safeString(headItem.attributes?.rel)"
              :href="safeString(headItem.attributes?.href)">
            <!-- Link with type -->
            <link 
              v-else-if="headItem.attributes?.type && !headItem.attributes?.title && !headItem.attributes?.media && !headItem.attributes?.sizes && !headItem.attributes?.hreflang && !headItem.attributes?.crossorigin"
              :head-key="safeString(headItem.attributes?.rel ?? `link-${index}`)"
              :rel="safeString(headItem.attributes?.rel)"
              :href="safeString(headItem.attributes?.href)"
              :type="safeString(headItem.attributes.type)">
            <!-- Link with title -->
            <link 
              v-else-if="!headItem.attributes?.type && headItem.attributes?.title && !headItem.attributes?.media && !headItem.attributes?.sizes && !headItem.attributes?.hreflang && !headItem.attributes?.crossorigin"
              :head-key="safeString(headItem.attributes?.rel ?? `link-${index}`)"
              :rel="safeString(headItem.attributes?.rel)"
              :href="safeString(headItem.attributes?.href)"
              :title="safeString(headItem.attributes.title)">
            <!-- Link with type and title -->
            <link 
              v-else-if="headItem.attributes?.type && headItem.attributes?.title && !headItem.attributes?.media && !headItem.attributes?.sizes && !headItem.attributes?.hreflang && !headItem.attributes?.crossorigin"
              :head-key="safeString(headItem.attributes?.rel ?? `link-${index}`)"
              :rel="safeString(headItem.attributes?.rel)"
              :href="safeString(headItem.attributes?.href)"
              :type="safeString(headItem.attributes.type)"
              :title="safeString(headItem.attributes.title)">
            <!-- Link with crossorigin -->
            <link 
              v-else-if="!headItem.attributes?.type && !headItem.attributes?.title && !headItem.attributes?.media && !headItem.attributes?.sizes && !headItem.attributes?.hreflang && headItem.attributes?.crossorigin"
              :head-key="safeString(headItem.attributes?.rel ?? `link-${index}`)"
              :rel="safeString(headItem.attributes?.rel)"
              :href="safeString(headItem.attributes?.href)"
              :crossorigin="safeString(headItem.attributes.crossorigin)">
            <!-- Fallback - only essential attributes -->
            <link 
              v-else
              :head-key="safeString(headItem.attributes?.rel ?? `link-${index}`)"
              :rel="safeString(headItem.attributes?.rel)"
              :href="safeString(headItem.attributes?.href)">
          </template>
        </template>
      </template>
    </Head>
    <div class="@container min-h-screen flex flex-col bg-zinc-50 dark:bg-zinc-900 font-public">
      <!-- Staging environment warning banner -->
      <StagingBanner />
      
      <!-- Skip to main content link - positioned first for keyboard navigation -->
      <a href="#main-content" class="sr-only focus:not-sr-only focus:absolute focus:top-4 focus:left-4 focus:z-[9999] focus:bg-white focus:text-zinc-900 focus:px-4 focus:py-2 focus:rounded-md focus:shadow-lg focus:border-2 focus:border-vusa-red dark:focus:bg-zinc-800 dark:focus:text-zinc-100">
        {{ $t('accessibility.skip_to_main_content') }}
      </a>
      
      <div
        class="flex-1 flex flex-col text-zinc-800 antialiased dark:text-zinc-300 container px-0 @container/main">
        <MainNavigation :is-theme-dark="isDark" />

        <main id="main-content" class="pb-8 mt-16">
          <!-- Centralized breadcrumb display -->
          <div v-if="breadcrumbState.breadcrumbs.value.length > 0" :class="breadcrumbWrapperClass">
            <PublicBreadcrumb />
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
          <ConsentCard v-if="!cookieConsent" @okay-cookie-consent="cookieConsent = true" />
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
 * 
 * IMPORTANT FIX: Inertia Head Component Compatibility
 * ================================================
 * 
 * Issue: After updating @inertiajs/vue3 to version 2.0.14+, the Head component
 * started using an escape() function to sanitize all attribute values. This function
 * expects string values but was receiving non-string values from the Laravel SEO package,
 * causing "str.replace is not a function" errors.
 * 
 * Root Cause: The Inertia commit 246c1258 added escaping to attribute values in the
 * Head component's renderTagStart function, but the Laravel SEO package was providing
 * complex objects that weren't being properly stringified before reaching the escape function.
 * 
 * Solution: 
 * 1. Added safeString() helper function to ensure all values are strings
 * 2. Created buildMetaAttributes() and buildLinkAttributes() functions to build clean attribute objects
 * 3. Filter out null/undefined/empty values before adding to attribute objects
 * 4. Use v-bind with clean attribute objects instead of individual attribute bindings
 * 5. Fixed title function in public.ts to always return strings
 * 
 * This ensures the escape() function only receives string values, preventing the error
 * while maintaining all SEO functionality.
 */
import { computed, defineAsyncComponent, onMounted, ref, watch, nextTick } from "vue";
import { useDark, useStorage } from "@vueuse/core";

import { Head, usePage, router } from "@inertiajs/vue3";
import FadeTransition from "@/Components/Transitions/FadeTransition.vue";
import PublicBreadcrumb from "@/Components/Public/PublicBreadcrumb.vue";
import { createBreadcrumbState } from '@/Composables/useBreadcrumbsUnified';
import { Toaster } from "@/Components/ui/sonner";
import { useToasts } from '@/Composables/useToasts';
import 'vue-sonner/style.css'

// Critical path components - load synchronously for faster initial render
import MainNavigation from "@/Components/Public/Layouts/MainNavigation.vue";
import SiteFooter from "../FullWidth/SiteFooter.vue";
import StagingBanner from "@/Components/StagingBanner.vue";

// Use existing Skeleton component for consistency
import { Skeleton } from '@/Components/ui/skeleton';

// Non-critical components - load asynchronously
const BannerCarousel = defineAsyncComponent({
  loader: () => import("../FullWidth/BannerCarousel.vue"),
  loadingComponent: {
    components: { Skeleton },
    template: '<div class="mx-8 my-8"><Skeleton class="h-32 rounded" /></div>'
  },
  delay: 200
});

const ConsentCard = defineAsyncComponent({
  loader: () => import("../ConsentCard.vue"),
  delay: 0 // Load immediately when needed
});

const isDark = useDark();

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

const breadcrumbWrapperClass = computed(() => {
  const width = layoutWidth.value;
  const baseClasses = 'pt-6 md:pt-16 lg:pt-16';
  
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

// Clear breadcrumbs when on home page
watch(() => usePage().component, (component) => {
  if (component === 'Public/HomePage') {
    breadcrumbState.clear();
  }
}, { immediate: true });

// Safe string conversion function to prevent Inertia Head escape() errors
const safeString = (value: any): string => {
  if (value === null || value === undefined || value === '') {
    return '';
  }
  try {
    return String(value);
  } catch (error) {
    console.error('Error converting value to string:', value, error);
    return '';
  }
};

// Helper function to check if an attribute has a meaningful value
const hasValue = (value: any): boolean => {
  return value !== null && value !== undefined && value !== '';
};

// Simplified SEO computed with better performance
const seo = computed(() => {
  const page = usePage();
  const computedSeo = page.props.seo?.tags;
  
  if (!computedSeo) {
    return [];
  }

  try {
    // Safely access the meta tag
    const metaTag = computedSeo['RalphJSmit\\Laravel\\SEO\\Support\\MetaTag'];
    if (metaTag && metaTag.attributes && metaTag.attributes.name === 'image') {
      metaTag.attributes.content = usePage().props.seo.image;
    }

    // Safely access OpenGraph tags
    const openGraphTags = computedSeo['RalphJSmit\\Laravel\\SEO\\Tags\\OpenGraphTags'];
    if (openGraphTags) {
      for (const [key, value] of Object.entries(openGraphTags)) {
        if (value && typeof value === 'object' && 'attributes' in value) {
          const attributes = value.attributes as any;
          if (attributes.property && !attributes.property.startsWith('og:')) {
            attributes.property = 'og:' + attributes.property;
          }
          if (attributes.property === 'og:image') {
            attributes.content = usePage().props.seo.image;
          }
        }
      }
    }

    // Safely flatten the structure
    const result = Object.values(computedSeo)
      .filter(val => val !== null && val !== undefined)
      .reduce((acc: any[], val: any) => {
        if (Array.isArray(val)) {
          return acc.concat(val);
        } else if (val && typeof val === 'object') {
          return acc.concat([val]);
        }
        return acc;
      }, []);
    
    return result;
  } catch (error) {
    console.error('Error processing SEO tags:', error);
    return [];
  }
});

const mounted = ref(false);

const cookieConsent = useStorage("cookie-consent", false);

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
  const userWayScript = document.createElement("script");
  userWayScript.setAttribute("data-account", "5OC3pQZI6r");
  userWayScript.setAttribute("src", "https://cdn.userway.org/widget.js");
  userWayScript.async = true;
  document.head.appendChild(userWayScript);

  // Defer Tawk.to loading - not critical for initial experience
  const loadTawkTo = () => {
    const lang = usePage().props.app.locale;
    const Tawk_SRC = lang === "lt" ? "default" : "1foc6rga3";
    
    const tawkScript = document.createElement("script");
    tawkScript.async = true;
    tawkScript.src = `https://embed.tawk.to/5f71b135f0e7167d00145612/${Tawk_SRC}`;
    tawkScript.charset = "UTF-8";
    tawkScript.setAttribute("crossorigin", "anonymous");
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
