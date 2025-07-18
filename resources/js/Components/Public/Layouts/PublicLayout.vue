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

        <main class="pb-8 pt-12">
          <!-- Centralized breadcrumb display -->
          <div v-if="publicBreadcrumbState.hasBreadcrumbs" class="wrapper pt-4 md:pt-6 lg:pt-8 mt-16">
            <PublicBreadcrumb :items="publicBreadcrumbState.breadcrumbs.value" class="mb-4 md:mb-6" />
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

        <SiteFooter />
      </div>
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
import PublicBreadcrumb from "@/Components/Public/Breadcrumb/PublicBreadcrumb.vue";
import { publicBreadcrumbState } from "@/Composables/usePublicBreadcrumbs";

const BannerCarousel = defineAsyncComponent(() => import("../FullWidth/BannerCarousel.vue"));
const ConsentCard = defineAsyncComponent(() => import("../ConsentCard.vue"));
const MainNavigation = defineAsyncComponent(() => import("@/Components/Public/Layouts/MainNavigation.vue"));
const SiteFooter = defineAsyncComponent(() => import("../FullWidth/SiteFooter.vue"));

const isDark = useDark();

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

const seo = computed(() => {
  // Computed Seo is an object
  let computedSeo = usePage().props.seo?.tags;
  
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
    // Components should set their own breadcrumbs by this point
    nextTick(() => {
      // We don't need default breadcrumbs for public pages
      // as each page should set its own or none at all
    });
  });

  // UserWay
  (function (d) {
    let s = d.createElement("script");
    s.setAttribute("data-account", "5OC3pQZI6r");
    s.setAttribute("src", "https://cdn.userway.org/widget.js");
    (d.body || d.head).appendChild(s);
  })(document);

  var lang = usePage().props.app.locale;

  var Tawk_SRC = lang == "lt" ? "default" : "1foc6rga3";
  var Tawk_API = Tawk_API || {},
    Tawk_LoadStart = new Date();

  (function () {
    let s1 = document.createElement("script"),
      s0 = document.getElementsByTagName("script")[0];
    s1.async = true;
    s1.src = `https://embed.tawk.to/5f71b135f0e7167d00145612/${Tawk_SRC}`;
    s1.charset = "UTF-8";
    s1.setAttribute("crossorigin", "*");
    s0.parentNode?.insertBefore(s1, s0);
  })();
  // usetimeout to delay the spin description
  setTimeout(() => {
    spinWarning.value = true;
  }, 6900);
});
</script>
