<template>
  <NConfigProvider :theme-overrides="themeOverrides">
    <Head :title="title" />
    <MetaIcons />
    <Transition name="fade"
      ><img
        v-if="animated"
        class="fixed h-full w-full object-cover opacity-50"
        :src="backgroundImage"
    /></Transition>
    <div class="relative min-h-screen bg-neutral-50/95 pb-8 antialiased">
      <AtaskaitaNavigation></AtaskaitaNavigation>

      <Transition name="fade" appear>
        <main>
          <div class="pt-16 sm:pt-24 md:pt-28 xl:pt-32 2xl:pt-40">
            <slot />
          </div></main
      ></Transition>
    </div>
  </NConfigProvider>
</template>

<script setup lang="ts">
import { Head } from "@inertiajs/inertia-vue3";
import { NConfigProvider } from "naive-ui";
import { onMounted, ref } from "vue";

import AtaskaitaNavigation from "@/Layouts/Partials/Public/AtaskaitaNavigation.vue";
import MetaIcons from "@/Components/MetaIcons.vue";

defineProps<{
  title: string;
  backgroundImage: string;
}>();

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
  const s = d.createElement("script");
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
