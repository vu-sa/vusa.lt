<template>
  <span>
    <div v-html="data.text"></div>
  </span>
</template>

<script setup lang="ts">
import type { NotificationData } from "../NotificationItem.vue";

defineProps<{
  data: NotificationData;
}>();
</script>
