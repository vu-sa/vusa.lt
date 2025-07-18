<template>
  <!-- https://www.joshwcomeau.com/css/full-bleed/ -->
  <NConfigProvider :theme="isDark ? darkTheme : undefined" :theme-overrides="usedThemeOverrides">
    <!-- Overwrite image meta -->

    <Head>
      <meta head-key="og:image" property="og:image" :content="usePage().props.seo.image">
      <meta head-key="image" name="image" :content="usePage().props.seo.image">
    </Head>

    <Head>
      <link rel="preconnect" href="https://embed.tawk.to" crossorigin="anonymous">
      <link rel="preload" href="https://cdn.userway.org/widgetapp/images/body_wh.svg" as="image">
      <template v-for="headItem in seo">
        <title v-if="headItem.tag === 'title'">
          {{ headItem.inner }}
        </title>
        <meta v-else-if="headItem.tag === 'meta'" :head-key="headItem.attributes.name ?? headItem.attributes.property"
          v-bind="toValue(headItem.attributes)">
        <link v-else-if="headItem.tag === 'link'" :head-key="headItem.attributes.rel"
          v-bind="toValue(headItem.attributes)">
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
      </div>
      
      <!-- Footer outside container for full-width -->
      <SiteFooter />
    </div>
  </NConfigProvider>
</template>

<script setup lang="ts">
import { NConfigProvider, darkTheme, useMessage, type GlobalThemeOverrides } from "naive-ui";
import { computed, defineAsyncComponent, onMounted, ref, toValue, watch, nextTick } from "vue";
import { useDark, useStorage } from "@vueuse/core";

import { Head, usePage, router } from "@inertiajs/vue3";
import FadeTransition from "@/Components/Transitions/FadeTransition.vue";
import { Spinner } from "@/Components/ui/spinner";
import PublicBreadcrumb from "@/Components/Public/Breadcrumb/PublicBreadcrumb.vue";
import { publicBreadcrumbState } from "@/Composables/usePublicBreadcrumbs";

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

// Simplified SEO computed with better performance
const seo = computed(() => {
  const page = usePage();
  const computedSeo = page.props.seo?.tags;
  
  if (!computedSeo) {
    return [];
  }

  try {
    // Process SEO tags and update image URLs
    const processedSeo = Object.values(computedSeo).flat().map((tag: any) => {
      if (tag.attributes?.name === 'image' || tag.attributes?.property === 'og:image') {
        return {
          ...tag,
          attributes: {
            ...tag.attributes,
            content: page.props.seo.image
          }
        };
      }
      
      // Add og: prefix if needed
      if (tag.attributes?.property && !tag.attributes.property.startsWith('og:')) {
        return {
          ...tag,
          attributes: {
            ...tag.attributes,
            property: 'og:' + tag.attributes.property
          }
        };
      }
      
      return tag;
    });

    return processedSeo;
  } catch (error) {
    console.warn('SEO processing error:', error);
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
    // Clear breadcrumbs when navigation finishes, then let components set their own
    nextTick(() => {
      // If no component has set breadcrumbs after navigation, clear them
      setTimeout(() => {
        if (publicBreadcrumbState.source.value !== 'component') {
          publicBreadcrumbState.clearBreadcrumbs();
        }
      }, 0);
    });
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
