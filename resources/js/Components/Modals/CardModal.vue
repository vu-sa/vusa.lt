<template>
  <NModal
    :show="show"
    class="prose prose-sm max-w-xl transition dark:prose-invert"
    :bordered="false"
    size="large"
    role="card"
    aria-modal="true"
    preset="card"
    @close="$emit('close')"
    @mask-click="$emit('close')"
    @esc="$emit('close')"
  >
    <slot />
  </NModal>
</template>

<script setup lang="ts">
import { NModal } from "naive-ui";

defineEmits(["close"]);

defineProps<{
  show: boolean;
}>();
</script>
