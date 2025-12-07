<template>
  <!-- Implemented in FormForm.vue -->
  <TransitionGroup ref="el" tag="div">
    <div v-for="model in contents" :key="model?.id || model?.name"
      class="relative grid w-full grid-cols-[24px__1fr] gap-4 border border-b-0 border-zinc-300 p-1 first:rounded-t-lg last:rounded-b-lg last:border-b dark:border-zinc-700/40 dark:bg-zinc-800/5">
      <Button class="handle" style="height: 100%;" variant="ghost" size="sm">
        <IFluentReOrderDotsVertical24Regular />
      </Button>
      <slot :model="model" />
    </div>
  </TransitionGroup>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { useSortable } from "@vueuse/integrations/useSortable";
import { watch } from 'vue';

import { Button } from '@/Components/ui/button';

const contents = defineModel<Record<string, any>[]>();

const el = ref(null);

useSortable(el, contents, {
  handle: ".handle", forceFallback: true, animation: 100,
});

watch(() => contents.value, () => {
  // update order value
  contents.value?.forEach((item, index) => {
    item.order = index;
  });
});
</script>
