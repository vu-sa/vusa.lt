<template>
  <NewsCard v-bind="$attrs" />
</template>

<script setup lang="ts">
import NewsCard from '@/Components/News/NewsCard.vue';

defineOptions({ inheritAttrs: false });
</script>
