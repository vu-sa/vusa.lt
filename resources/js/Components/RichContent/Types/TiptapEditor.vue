<template>
  <ModernTipTap v-model="modelValue!" />
</template>

<script setup lang="ts">
import { defineModel } from 'vue';

import ModernTipTap from '@/Components/TipTap/ModernTipTap.vue';

const modelValue = defineModel<Record<string, unknown> | null>();
</script>

