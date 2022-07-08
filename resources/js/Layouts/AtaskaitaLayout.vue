<template>
  <NConfigProvider :theme-overrides="themeOverrides">
    <Head :title="title" />
    <MetaIcons />
    <transition name="fade"
      ><img
        v-if="animated"
        class="object-cover h-full w-full fixed opacity-50"
        :src="backgroundImage"
    /></transition>
    <div class="min-h-screen antialiased bg-neutral-50/95 pb-8 relative">
      <AtaskaitaNavigation></AtaskaitaNavigation>

      <transition name="fade">
        <main v-if="animated">
          <PageContent class="pt-16 sm:pt-24 md:pt-28 xl:pt-32 2xl:pt-40">
            <slot />
          </PageContent></main
      ></transition>
    </div>
  </NConfigProvider>
</template>

<script setup>
import AtaskaitaNavigation from "@/Layouts/Partials/Public/AtaskaitaNavigation.vue";
import PageContent from "@/Layouts/Partials/Public/PageContent.vue";
// import NewsElement from "@/Layouts/Partials/Public/NewsElement.vue";
import { Head } from "@inertiajs/inertia-vue3";
import { onMounted, ref } from "vue";
import MetaIcons from "@/Components/MetaIcons.vue";
// import Footer from "./Partials/Public/Footer.vue";

defineProps({
  title: String,
  backgroundImage: String,
});

const themeOverrides = {
  common: {
    primaryColor: "#bd2835FF",
    primaryColorHover: "#CD3543FF",
    primaryColorPressed: "#CC2130FF",
    primaryColorSuppl: "#B93945FF",
  },
};

const animated = ref(false);

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

(function (d) {
  var s = d.createElement("script");
  s.setAttribute("data-account", "5OC3pQZI6r");
  s.setAttribute("src", "https://cdn.userway.org/widget.js");
  (d.body || d.head).appendChild(s);
})(document);
</script>

<style>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 1s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
