<template>
  <Dialog :open @update:open="$emit('update:open', $event)">
    <DialogTrigger v-if="$slots.trigger" as-child>
      <slot name="trigger" />
    </DialogTrigger>
    <DialogContent
      :show-close-button="false"
      :class="[
        'fixed bottom-0 top-auto left-0 flex h-[90dvh] w-full max-w-none',
        'translate-x-0 translate-y-0 flex-col gap-0 overflow-hidden p-0',
        'md:bottom-auto md:top-1/2 md:left-1/2 md:h-[85dvh] md:w-[calc(100%-2rem)]',
        'md:max-w-5xl md:-translate-x-1/2 md:-translate-y-1/2',
      ]"
      @open-auto-focus.prevent
    >
      <DialogHeader class="border-b px-5 py-4 text-left">
        <DialogTitle>{{ title }}</DialogTitle>
        <DialogDescription v-if="description">
          {{ description }}
        </DialogDescription>
      </DialogHeader>

      <div class="flex min-h-0 flex-1 flex-col p-3 sm:p-5">
        <slot
          v-if="open"
          :selected-ids
          :multiple
          :toggle="onToggle"
          :pinned-hits="initialHits ?? []"
        />
      </div>

      <DialogFooter class="flex-row flex-wrap items-center justify-between gap-3 border-t px-3 py-3 sm:px-5">
        <span class="text-sm text-muted-foreground">
          {{ $t(':count pasirinkta', { count: String(selectedHits.length) }) }}
        </span>
        <div class="flex items-center gap-2">
          <Button variant="outline" voice="sentence" @click="cancel">
            {{ $t('Atšaukti') }}
          </Button>
          <Button variant="brand" :disabled="!allowEmpty && selectedHits.length === 0" @click="confirm">
            {{ confirmLabel ?? $t('Pridėti pasirinktus') }}
          </Button>
        </div>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';

import type { NormalizedSearchHit } from '../../Utils/searchHitMappers';

import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from '@/Components/ui/dialog';
import { Button } from '@/Components/ui/button';

const props = defineProps<{
  open: boolean;
  multiple: boolean;
  title: string;
  description?: string;
  confirmLabel?: string;
  /** Hits selected when the dialog opens (pre-checked + pinned first). */
  initialHits?: NormalizedSearchHit[];
  /** Allow confirming with nothing selected (single-select "clear" support). */
  allowEmpty?: boolean;
}>();

const emit = defineEmits<{
  'update:open': [open: boolean];
  'confirm': [hits: NormalizedSearchHit[]];
}>();

// Working selection: a Set of record ids + a Map to retain the full hit objects
// even after a new search removes them from the visible list.
const selectedIds = ref<Set<string>>(new Set());
const selectedMap = ref<Map<string, NormalizedSearchHit>>(new Map());
const selectedHits = ref<NormalizedSearchHit[]>([]);

const syncSelectedHits = () => {
  selectedHits.value = Array.from(selectedIds.value)
    .map(id => selectedMap.value.get(id))
    .filter((hit): hit is NormalizedSearchHit => hit != null);
};

// Reset the working selection each time the dialog opens, seeding any initial
// (currently-selected) hits so they render pre-checked and pinned first.
watch(
  () => props.open,
  (open) => {
    if (open) {
      const initial = props.initialHits ?? [];
      selectedIds.value = new Set(initial.map(hit => hit.id));
      selectedMap.value = new Map(initial.map(hit => [hit.id, hit]));
      syncSelectedHits();
    }
  },
  { immediate: true },
);

const onToggle = (hit: NormalizedSearchHit) => {
  const next = new Set(selectedIds.value);
  if (next.has(hit.id)) {
    next.delete(hit.id);
  }
  else {
    if (!props.multiple) {
      next.clear();
    }
    next.add(hit.id);
    selectedMap.value.set(hit.id, hit);
  }
  selectedIds.value = next;
  syncSelectedHits();
};

const cancel = () => {
  emit('update:open', false);
};

const confirm = () => {
  emit('confirm', selectedHits.value);
  emit('update:open', false);
};
</script>
