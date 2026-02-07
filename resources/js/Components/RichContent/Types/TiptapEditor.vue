<!--
  RichContent TiptapEditor Wrapper
  
  This component wraps the unified TiptapEditor for the rich content system.
  It provides a full-featured editor for content blocks.
-->
<template>
  <TiptapEditor v-model="modelValue!" preset="full" />
</template>

<script setup lang="ts">
import type { Tiptap } from '@/Types/contentParts';

import TiptapEditor from '@/Components/TipTap/TiptapEditor.vue';

const modelValue = defineModel<Tiptap['json_content'] | null>();
</script>

