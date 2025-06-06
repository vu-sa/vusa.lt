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

        <SiteFooter />
      </div>
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

const BannerCarousel = defineAsyncComponent(() => import("../FullWidth/BannerCarousel.vue"));
const ConsentCard = defineAsyncComponent(() => import("../ConsentCard.vue"));
const MainNavigation = defineAsyncComponent(() => import("@/Components/Public/Layouts/MainNavigation.vue"));
const SiteFooter = defineAsyncComponent(() => import("../FullWidth/SiteFooter.vue"));

const isDark = useDark();

const seo = computed(() => {
  // Computed Seo is an object
  let computedSeo = usePage().props.seo.tags;
  
  if (!computedSeo) {
    return [];
  }

  if (computedSeo['RalphJSmit\\Laravel\\SEO\\Support\\MetaTag']['attributes']['name'] === 'image') computedSeo['RalphJSmit\\Laravel\\SEO\\Support\\MetaTag']['attributes']['content'] = usePage().props.seo.image

  // if computedSeo key is OpenGraph, then add og: prefix to the key
  if (computedSeo['RalphJSmit\\Laravel\\SEO\\Tags\\OpenGraphTags']) {
    // foreach property, prefix og:
    for (const [key, value] of Object.entries(computedSeo['RalphJSmit\\Laravel\\SEO\\Tags\\OpenGraphTags'])) {
      // check if property is not already prefixed
      if (!value['attributes']['property'].startsWith('og:')) computedSeo['RalphJSmit\\Laravel\\SEO\\Tags\\OpenGraphTags'][key]['attributes']['property'] = 'og:' + value['attributes']['property'];

      // check image property
      if (value['attributes']['property'] === 'og:image') {
        // check if image is not already prefixed
        computedSeo['RalphJSmit\\Laravel\\SEO\\Tags\\OpenGraphTags'][key]['attributes']['content'] = usePage().props.seo.image
      }
    }
  }

  // reduce Object.entries to an array of objects
  return Object.values(computedSeo).reduce((acc, val) => acc.concat(val), []);
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
