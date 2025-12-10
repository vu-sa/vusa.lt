<template>
  <ModernTipTap v-model="modelValue!" />
</template>

<script setup lang="ts">
import type { Tiptap } from '@/Types/contentParts';

import ModernTipTap from '@/Components/TipTap/ModernTipTap.vue';

const modelValue = defineModel<Tiptap['json_content'] | null>();
</script>

