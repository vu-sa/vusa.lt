<template>
  <OriginalTipTap v-model="modelValue!" />
</template>

<script setup lang="ts">
import { defineModel } from 'vue';

import OriginalTipTap from '@/Components/TipTap/OriginalTipTap.vue';

const modelValue = defineModel<Record<string, unknown> | null>();
</script>

