<template>
  <!-- https://www.joshwcomeau.com/css/full-bleed/ -->
  <FadeTransition>
    <!-- <Suspense> -->
    <NConfigProvider
      v-show="mounted"
      :theme="isDark ? darkTheme : undefined"
      :theme-overrides="themeOverrides"
    >
      <div
        class="flex min-h-screen flex-col justify-between bg-zinc-50 antialiased dark:bg-zinc-900 text-zinc-800 dark:text-zinc-300"
      >
        <FadeTransition appear
          ><MainNavigation :is-theme-dark="isDark"
        /></FadeTransition>
        <main class="pb-8">
          <Suspense>
            <div>
              <div class="wrapper"><slot /></div>
              <div
                v-if="
                  $page.props.padalinys?.banners &&
                  $page.props.padalinys.banners.length > 0
                "
                class="mx-auto mt-8 max-w-7xl"
              >
                <BannerCarousel :banners="$page.props.padalinys?.banners" />
              </div>
            </div>
            <template #fallback>
              <div class="flex h-screen items-center justify-center">
                <NSpin>
                  <template #description>
                    <div class="mt-2 h-8 text-vusa-red">
                      <FadeTransition>
                        <span v-if="spinWarning">
                          Pabandykite perkrauti puslapį arba grįžkite į
                          <a class="underline" :href="$page.props.app.url"
                            >vusa.lt</a
                          >
                        </span>
                        <span v-else></span>
                      </FadeTransition>
                    </div>
                  </template>
                </NSpin>
              </div>
            </template>
          </Suspense>
        </main>

        <FadeTransition appear>
          <ConsentCard
            v-if="!cookieConsent"
            @okay-cookie-consent="cookieConsent = true"
          />
        </FadeTransition>

        <Footer />
      </div>

      <!-- preconnect to tawk.to -->
      <link rel="preconnect" href="https://embed.tawk.to" />
    </NConfigProvider>

    <!-- </Suspense> -->
  </FadeTransition>
</template>

<script setup lang="ts">
import { NConfigProvider, NSpin, darkTheme } from "naive-ui";
import { defineAsyncComponent, onMounted, ref } from "vue";
import { useDark, useStorage } from "@vueuse/core";

import BannerCarousel from "../FullWidth/BannerCarousel.vue";
import FadeTransition from "@/Components/Transitions/FadeTransition.vue";
import MainNavigation from "@/Components/Public/Layouts/MainNavigation.vue";
import { usePage } from "@inertiajs/vue3";

const isDark = useDark();

const mounted = ref(false);
const spinWarning = ref(false);

const themeOverrides = {
  common: {
    primaryColor: "#bd2835FF",
    primaryColorHover: "#CD3543FF",
    primaryColorPressed: "#CC2130FF",
    primaryColorSuppl: "#B93945FF",
    borderRadius: "6px",
    fontWeightStrong: "600",
    lineHeight: "1.5",
    paddingMedium: '16px 24px 8px',
    titleFontSizeMedium: '1.25rem',
    titleFontWeight: '700',
    // textColor1: 'rgb(24, 8, 6)',
    // textColor2: 'rgb(32, 13, 11)',
    // textColor3: 'rgb(130, 121, 118)'
  },
};

const ConsentCard = defineAsyncComponent(
  () => import("@/Components/Public/ConsentCard.vue")
);

const Footer = defineAsyncComponent(
  () => import("@/Components/Public/FullWidth/SiteFooter.vue")
);

const cookieConsent = useStorage("cookie-consent", false);

onMounted(() => {
  mounted.value = true;

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
