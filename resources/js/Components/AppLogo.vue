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
defineProps<{
  isThemeDark: boolean;
}>();
</script>

<style>
.white {
  filter: invert(1);
}
</style>
