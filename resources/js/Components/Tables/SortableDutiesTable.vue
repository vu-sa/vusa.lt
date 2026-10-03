<template>
  <!-- Order mode of an institution's Pareigybės section (Forms rule 16): the handle is for a mouse,
       the caller's row slot adds ↑/↓ for keyboards and phones, where dragging is unreliable. -->
  <TransitionGroup ref="el" tag="div" class="divide-y divide-border border-y border-border" data-slot="sortable-duties">
    <div
      v-for="(model, index) in contents"
      :key="model?.id || model?.name"
      class="group/row flex items-start gap-2 px-2 transition-colors hover:bg-accent/40 sm:px-3"
    >
      <button
        type="button"
        class="handle mt-2.5 flex size-6 shrink-0 cursor-grab items-center justify-center text-muted-foreground active:cursor-grabbing pointer-coarse:hidden"
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
import { ref } from 'vue';
import { useSortable } from '@vueuse/integrations/useSortable';
import { GripVertical } from 'lucide-vue-next';

const contents = defineModel<Record<string, any>[]>();

const el = ref(null);

useSortable(el, contents, {
  handle: '.handle', forceFallback: true, animation: 100,
});
</script>
