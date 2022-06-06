<template>
  <Head :title="title" />
  <MetaIcons />
  <FullWindow>
    <MainNavigation></MainNavigation>
    <transition name="fade">
      <main v-if="animated">
        <PageContent class="pt-16 sm:pt-24 2xl:pt-36">
          <slot />
        </PageContent>
      </main>
    </transition>
  </FullWindow>

  <!-- preconnect to tawk.to -->
  <link rel="preconnect" href="https://embed.tawk.to" />
</template>

<script setup>
import FullWindow from "@/Layouts/Partials/Public/FullWindow.vue";
import MainNavigation from "@/Layouts/Partials/Public/MainNavigation.vue";
import PageContent from "@/Layouts/Partials/Public/PageContent.vue";
// import NewsElement from "@/Layouts/Partials/Public/NewsElement.vue";
import { Head, usePage } from "@inertiajs/inertia-vue3";
import { ref, onMounted } from "vue";
import MetaIcons from "@/Components/MetaIcons.vue";

const props = defineProps({
  title: String,
});

const animated = ref(false);
const locale = ref(usePage().props.value.locale);

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
