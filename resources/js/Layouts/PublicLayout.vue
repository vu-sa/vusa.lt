<template>
  <NConfigProvider :theme-overrides="themeOverrides">
    <Head :title="title" />
    <MetaIcons />
    <div class="min-h-screen antialiased bg-neutral-50 pb-8">
      <MainNavigation></MainNavigation>
      <transition name="fade">
        <main v-if="animated">
          <div class="pt-16 sm:pt-24 2xl:pt-36">
            <!-- <NThemeEditor v-if="$page.props.app.env == 'local'"> -->

            <slot></slot>

            <!-- </NConfigProvider> -->
            <!-- </NThemeEditor> -->
          </div>
        </main>
      </transition>
    </div>
    <Footer />

    <!-- preconnect to tawk.to -->
    <link rel="preconnect" href="https://embed.tawk.to" />
  </NConfigProvider>
</template>

<script setup>
import Footer from "@/Layouts/Partials/Public/Footer.vue";
import MainNavigation from "@/Layouts/Partials/Public/MainNavigation.vue";
// import PageContent from "@/Layouts/Partials/Public/PageContent.vue";
// import NewsElement from "@/Layouts/Partials/Public/NewsElement.vue";
import { Head } from "@inertiajs/inertia-vue3";
import {
  NConfigProvider,
  // NDialogProvider,
  // NNotificationProvider,
  // NThemeEditor,
  // darkTheme,
} from "naive-ui";
import { onMounted, ref } from "vue";
import MetaIcons from "@/Components/MetaIcons.vue";

const themeOverrides = {
  common: {
    primaryColor: "#bd2835FF",
    primaryColorHover: "#CD3543FF",
    primaryColorPressed: "#CC2130FF",
    primaryColorSuppl: "#B93945FF",
  },
};

defineProps({
  title: { type: String, default: "" },
});

const animated = ref(false);
// const locale = ref(usePage().props.value.locale);

onMounted(() => {
  animated.value = true;
});

// Userway script

(function (d) {
  var s = d.createElement("script");
  s.setAttribute("data-account", "5OC3pQZI6r");
  s.setAttribute("src", "https://cdn.userway.org/widget.js");
  (d.body || d.head).appendChild(s);
})(document);

// <!--Start of Tawk.to Script-->

// TODO: add Tawk.to EN script

var Tawk_API = Tawk_API || {},
  Tawk_LoadStart = new Date();
(function () {
  var s1 = document.createElement("script"),
    s0 = document.getElementsByTagName("script")[0];
  s1.async = true;
  s1.src = "https://embed.tawk.to/5f71b135f0e7167d00145612/default";
  s1.charset = "UTF-8";
  s1.setAttribute("crossorigin", "*");
  s0.parentNode.insertBefore(s1, s0);
})();

onMounted(() => {
  animated.value = true;

  (function (c, l, a, r, i, t, y) {
    c[a] =
      c[a] ||
      function () {
        (c[a].q = c[a].q || []).push(arguments);
      };
    t = l.createElement(r);
    t.async = 1;
    t.src = "https://www.clarity.ms/tag/" + i;
    y = l.getElementsByTagName(r)[0];
    y.parentNode.insertBefore(t, y);
  })(window, document, "clarity", "script", "bs7culn3gp");
});
</script>

<style>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.5s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
