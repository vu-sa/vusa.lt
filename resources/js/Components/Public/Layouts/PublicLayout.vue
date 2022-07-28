<template>
  <NConfigProvider :theme-overrides="themeOverrides">
    <Head :title="title" />
    <MetaIcons />
    <div class="min-h-screen bg-neutral-50 pb-8 antialiased">
      <MainNavigation></MainNavigation>
      <Transition name="fade" appear>
        <main>
          <div class="pt-12 sm:pt-20 2xl:pt-32">
            <slot></slot>
          </div>
        </main>
      </Transition>
    </div>
    <Footer />

    <!-- preconnect to tawk.to -->
    <link rel="preconnect" href="https://embed.tawk.to" />
  </NConfigProvider>
</template>

<script setup lang="ts">
import { Head, usePage } from "@inertiajs/inertia-vue3";
import { NConfigProvider } from "naive-ui";
import { onMounted } from "vue";
import Footer from "@/Components/Public/FooterComponent.vue";
import MainNavigation from "@/Components/Public/Layouts/MainNavigation.vue";
import MetaIcons from "@/Components/MetaIcons.vue";

defineProps<{
  title: string;
}>();

const themeOverrides = {
  common: {
    primaryColor: "#bd2835FF",
    primaryColorHover: "#CD3543FF",
    primaryColorPressed: "#CC2130FF",
    primaryColorSuppl: "#B93945FF",
  },
};

// Userway script

(function (d) {
  let s = d.createElement("script");
  s.setAttribute("data-account", "5OC3pQZI6r");
  s.setAttribute("src", "https://cdn.userway.org/widget.js");
  (d.body || d.head).appendChild(s);
})(document);

// <!--Start of Tawk.to Script-->

// TODO: add Tawk.to EN script

var Tawk_API = Tawk_API || {},
  Tawk_LoadStart = new Date();

(function () {
  let s1 = document.createElement("script"),
    s0 = document.getElementsByTagName("script")[0];
  s1.async = true;
  s1.src = "https://embed.tawk.to/5f71b135f0e7167d00145612/default";
  s1.charset = "UTF-8";
  s1.setAttribute("crossorigin", "*");
  s0.parentNode.insertBefore(s1, s0);
})();

onMounted(() => {
  // if page props app.env is local, then don't run Clarity
  if (usePage().props.value.app.env !== "local") {
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
  }
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
