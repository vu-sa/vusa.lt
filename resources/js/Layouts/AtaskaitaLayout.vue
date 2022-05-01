<template>
  <Head :title="title" />
  <MetaIcons />
  <div class="min-h-screen antialiased bg-neutral-50 pb-8">
    <MainNavigation></MainNavigation>
    <transition name="fade">
      <main v-if="animated">
        <PageContent class="sm:pt-20 pt-16 xl:pt-32 2xl:pt-40">
          <slot />
        </PageContent></main
    ></transition>
  </div>
  <Footer />
</template>

<script setup>
import FullWindow from "@/Layouts/Partials/Public/FullWindow.vue";
import MainNavigation from "@/Layouts/Partials/Public/MainNavigation.vue";
import PageContent from "@/Layouts/Partials/Public/PageContent.vue";
import NewsElement from "@/Layouts/Partials/Public/NewsElement.vue";
import { Head } from "@inertiajs/inertia-vue3";
import { ref, onMounted } from "vue";
import MetaIcons from "@/Components/MetaIcons.vue";
import Footer from "./Partials/Public/Footer.vue";

const props = defineProps({
  title: String,
});

const animated = ref(false);

onMounted(() => {
  animated.value = true;
});
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
