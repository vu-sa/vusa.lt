<template>
  <!-- Implemented in FormForm.vue -->
  <TransitionGroup ref="el" tag="div" class="divide-y divide-border border-y border-border" data-slot="sortable-form-fields">
    <div
      v-for="(model, index) in contents"
      :key="model?.id || model?.name"
      class="group/row flex items-center gap-2 px-2 py-1.5 transition-colors hover:bg-accent/40 sm:px-3"
    >
      <button
        type="button"
        class="handle flex size-6 shrink-0 cursor-grab items-center justify-center text-muted-foreground active:cursor-grabbing pointer-coarse:hidden"
        :aria-label="$t('Tempti, kad pakeistum tvarką')"
        tabindex="-1"
      >
        <GripVertical class="size-4" />
      </button>
      <div class="min-w-0 flex-1">
        <slot :model :index />
      </div>
    </div>
  </TransitionGroup>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue';
import { useSortable } from '@vueuse/integrations/useSortable';
import { GripVertical } from 'lucide-vue-next';
import { trans as $t } from 'laravel-vue-i18n';

// eslint-disable-next-line @typescript-eslint/no-explicit-any
const contents = defineModel<Record<string, any>[]>();

const el = ref(null);

useSortable(el, contents, {
  handle: '.handle',
  forceFallback: true,
  animation: 100,
});

watch(() => contents.value, () => {
  // update order value
  contents.value?.forEach((item, index) => {
    item.order = index;
  });
});
</script>
