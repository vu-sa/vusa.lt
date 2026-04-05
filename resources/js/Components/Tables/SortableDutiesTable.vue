<template>
  <!-- Implemented in InstitutionForm.vue -->
  <TransitionGroup ref="el" tag="div" class="divide-y divide-border rounded-lg border bg-card">
    <div v-for="model in contents" :key="model?.id || model?.name"
      class="group/row flex items-start gap-2 px-3 hover:bg-muted/30 transition-colors">
      <button type="button" class="handle mt-3 cursor-grab opacity-30 hover:opacity-100 transition-opacity active:cursor-grabbing">
        <IFluentReOrderDotsVertical24Regular class="h-4 w-4 text-muted-foreground" />
      </button>
      <div class="flex-1 min-w-0">
        <slot :model />
      </div>
    </div>
  </TransitionGroup>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue';
import { useSortable } from '@vueuse/integrations/useSortable';

const contents = defineModel<Record<string, any>[]>();

const el = ref(null);

useSortable(el, contents, {
  handle: '.handle', forceFallback: true, animation: 100,
});

watch(() => contents.value, () => {

});
</script>
