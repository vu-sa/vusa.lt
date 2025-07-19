<template>
  <!-- https://www.joshwcomeau.com/css/full-bleed/ -->
  <NConfigProvider :theme="isDark ? darkTheme : undefined" :theme-overrides="usedThemeOverrides">
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
    <div class="@container bg-zinc-50 dark:bg-zinc-900">
      <div
        class="flex flex-col justify-between text-zinc-800 antialiased dark:text-zinc-300 container px-0 @container/main">
        <MainNavigation :is-theme-dark="isDark" />

        <main class="pb-8 pt-12 mt-16">
          <!-- Centralized breadcrumb display -->
          <div v-if="breadcrumbState.breadcrumbs.value.length > 0" class="wrapper pt-4 md:pt-6 lg:pt-8">
            <UnifiedBreadcrumbs class="mb-4 md:mb-6" />
          </div>
          
          <!-- <Suspense> -->
          <div>
            <FadeTransition v-if="!$page.props.disablePageTransition" appear>
              <div :key="$page.url" class="wrapper">
                <slot />
              </div>
            </FadeTransition>
            <div v-else class="wrapper">
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
      <SiteFooter />
    </div>
  </NConfigProvider>
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
import { NConfigProvider, darkTheme, useMessage, type GlobalThemeOverrides } from "naive-ui";
import { computed, defineAsyncComponent, onMounted, ref, toValue, watch, nextTick } from "vue";
import { useDark, useStorage } from "@vueuse/core";

import { Head, usePage, router } from "@inertiajs/vue3";
import FadeTransition from "@/Components/Transitions/FadeTransition.vue";
import { Spinner } from "@/Components/ui/spinner";
import UnifiedBreadcrumbs from "@/Components/UnifiedBreadcrumbs.vue";
import { createBreadcrumbState } from '@/Composables/useBreadcrumbsUnified';

// Use existing Skeleton component for consistency
import { Skeleton } from '@/Components/ui/skeleton';

// Optimize component loading - critical path components should load faster
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

const MainNavigation = defineAsyncComponent({
  loader: () => import("@/Components/Public/Layouts/MainNavigation.vue"),
  loadingComponent: {
    components: { Skeleton },
    template: '<Skeleton class="h-16" />'
  },
  delay: 0 // Critical component - load immediately
});

const SiteFooter = defineAsyncComponent({
  loader: () => import("../FullWidth/SiteFooter.vue"),
  loadingComponent: {
    components: { Skeleton },
    template: '<div class="w-full border-t border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-900 py-8"><div class="mx-auto max-w-7xl px-4"><Skeleton class="h-20 w-full" /></div></div>'
  },
  delay: 100
});

const isDark = useDark();

// Initialize breadcrumb state for public pages
const breadcrumbState = createBreadcrumbState('public');

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
const spinWarning = ref(false);

const themeOverrides: GlobalThemeOverrides = {
  common: {
    primaryColor: "#bd2835FF",
    primaryColorHover: "#CD3543FF",
    primaryColorPressed: "#CC2130FF",
    primaryColorSuppl: "#B93945FF",
    borderRadius: "6px",
    fontWeightStrong: "600",
    lineHeight: "1.5",
  },
  DataTable: {
    tdColor: "transparent",
    borderColor: "#dddddd",
  }
};

const darkThemeOverrides: GlobalThemeOverrides = {
  common: {
    primaryColor: "#bd2835FF",
    primaryColorHover: "#CD3543FF",
    primaryColorPressed: "#CC2130FF",
    primaryColorSuppl: "#B93945FF",
    borderRadius: "6px",
    fontWeightStrong: "600",
    lineHeight: "1.5",
  },
  DataTable: {
    tdColor: "transparent",
    borderColor: "#333333",
  }
};

const usedThemeOverrides = computed(() => {
  return isDark.value ? darkThemeOverrides : themeOverrides;
});

const cookieConsent = useStorage("cookie-consent", false);

const message = useMessage();

const successMessage = computed(() => usePage().props.flash.success);
const infoMessage = computed(() => usePage().props.flash.info);
const errorMessage = computed(() => usePage().props.errors);

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

watch(errorMessage, (errorMessage) => {
  if (errorMessage) {
    // loop over the object and display each error
    for (const [key, value] of Object.entries(errorMessage)) {
      message.error(`${key}: ${value}`);

      // In public page, show only one error message at a time
      break
    }
  }
});

// Listen for navigation events to handle breadcrumb persistence
onMounted(() => {
  mounted.value = true;

  // Setup router navigation events for breadcrumbs
  router.on('start', () => {
    // Don't clear breadcrumbs during navigation - helps with transition
  });

    router.on('finish', () => {
    // Note: We no longer clear breadcrumbs on navigation to prevent flashing
    // Individual pages will set their own breadcrumbs using usePageBreadcrumbs()
  });

  // Defer non-critical script loading to improve INP
  nextTick(() => {
    // Use requestIdleCallback or setTimeout to defer script loading
    const loadThirdPartyScripts = () => {
      // UserWay - defer to not block main thread
      const userWayScript = document.createElement("script");
      userWayScript.setAttribute("data-account", "5OC3pQZI6r");
      userWayScript.setAttribute("src", "https://cdn.userway.org/widget.js");
      userWayScript.defer = true;
      document.head.appendChild(userWayScript);

      // Tawk.to - defer to not block main thread
      const lang = usePage().props.app.locale;
      const Tawk_SRC = lang === "lt" ? "default" : "1foc6rga3";
      
      const tawkScript = document.createElement("script");
      tawkScript.async = true;
      tawkScript.defer = true;
      tawkScript.src = `https://embed.tawk.to/5f71b135f0e7167d00145612/${Tawk_SRC}`;
      tawkScript.charset = "UTF-8";
      tawkScript.setAttribute("crossorigin", "*");
      
      const firstScript = document.getElementsByTagName("script")[0];
      if (firstScript && firstScript.parentNode) {
        firstScript.parentNode.insertBefore(tawkScript, firstScript);
      } else {
        document.head.appendChild(tawkScript);
      }
    };

    // Use requestIdleCallback if available, otherwise setTimeout
    if ('requestIdleCallback' in window) {
      requestIdleCallback(loadThirdPartyScripts, { timeout: 2000 });
    } else {
      setTimeout(loadThirdPartyScripts, 100);
    }
  });

  // Delay spin warning - keep this as is for UX
  setTimeout(() => {
    spinWarning.value = true;
  }, 6900);
});
</script>
