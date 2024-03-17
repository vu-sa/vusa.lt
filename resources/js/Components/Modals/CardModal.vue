<template>
  <NModal
    :show="show"
    class="max-w-3xl transition"
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
    <template #footer><slot name="footer"></slot></template>
  </NModal>
</template>

<script setup lang="ts">
import { NModal } from "naive-ui";

defineEmits(["close"]);

defineProps<{
  show: boolean;
}>();
</script>
