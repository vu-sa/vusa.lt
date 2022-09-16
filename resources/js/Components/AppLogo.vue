<template>
  <img
    class="max-w-lg"
    :class="{ white: isThemeDark }"
    :src="
      $page.props.locale !== 'en'
        ? '/logos/vusa.lin.hor.svg'
        : '/logos/vusa.lin.hor.en.svg'
    "
  />
</template>

<script setup lang="ts">
import { isDarkMode, updateDarkMode } from "@/Composables/darkMode";
import { onMounted, ref } from "vue";

const isThemeDark = ref(isDarkMode());

onMounted(() => {
  // get html target node
  updateDarkMode(isThemeDark);
});
</script>

<style>
.white {
  filter: invert(1);
}
</style>
