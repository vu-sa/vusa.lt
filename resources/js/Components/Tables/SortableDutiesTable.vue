<template>
  <TransitionGroup ref="el" tag="div">
    <div v-for="model in contents" :key="model?.id || model?.name"
      class="relative grid w-full grid-cols-[24px,_1fr] gap-4 border border-b-0 border-zinc-300 p-1 first:rounded-t-lg last:rounded-b-lg last:border-b dark:border-zinc-700/40 dark:bg-zinc-800/5">
      <NButton class="handle" style="height: 100%;" quaternary size="small">
        <template #icon>
          <IFluentReOrderDotsVertical24Regular />
        </template>
      </NButton>
      <slot :model="model" />
    </div>
  </TransitionGroup>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { useSortable } from "@vueuse/integrations/useSortable";
import { watch } from 'vue';

const contents = defineModel<Record<string, any>[]>();

const el = ref(null);

useSortable(el, contents, {
  handle: ".handle", forceFallback: true, animation: 100,
});

watch(() => contents.value, () => {

});
</script>
