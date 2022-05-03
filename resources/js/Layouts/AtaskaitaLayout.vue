<template>
  <Head :title="title" />
  <MetaIcons />
  <transition name="fade"
    ><img v-if="animated" class="object-cover h-full w-full fixed" :src="backgroundImage"
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

  <!-- <Footer /> -->
</template>

<script setup>
// import FullWindow from "@/Layouts/Partials/Public/FullWindow.vue";
import AtaskaitaNavigation from "@/Layouts/Partials/Public/AtaskaitaNavigation.vue";
import PageContent from "@/Layouts/Partials/Public/PageContent.vue";
// import NewsElement from "@/Layouts/Partials/Public/NewsElement.vue";
import { Head } from "@inertiajs/inertia-vue3";
import { ref, onMounted } from "vue";
import MetaIcons from "@/Components/MetaIcons.vue";
// import Footer from "./Partials/Public/Footer.vue";

const props = defineProps({
  title: String,
  backgroundImage: String,
});

const animated = ref(false);

onMounted(() => {
  animated.value = true;
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
