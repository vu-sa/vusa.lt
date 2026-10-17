<template>
  <!-- Solid: a step the record needs. Dashed: an empty slot that would only enrich it. -->
  <ul v-if="actions.length || fillActions.length" class="flex flex-wrap items-center gap-2" data-slot="record-next-action-list">
    <li v-for="action in actions" :key="action.key">
      <Button
        variant="outline"
        size="sm"
        voice="sentence"
        :class="['pointer-coarse:h-11', action.attention && 'border-brand text-brand hover:bg-brand/10 motion-safe:animate-attention-wash']"
        :data-attention="action.attention || undefined"
        :data-testid="`record-next-action-${action.key}`"
        @click="action.run()"
      >
        <component :is="action.icon" v-if="action.icon" class="size-4" aria-hidden="true" />
        {{ action.label }}
      </Button>
    </li>
    <li v-if="actions.length && fillActions.length" role="separator" aria-orientation="vertical" class="mx-1 h-6 w-px bg-border" />
    <li v-for="entry in fillActions" :key="entry.id">
      <Button
        variant="outline"
        size="sm"
        voice="sentence"
        class="border-dashed border-muted-foreground/50 text-muted-foreground pointer-coarse:h-11"
        data-tier="fill"
        @click="entry.action.run()"
      >
        <component :is="entry.action.icon ?? Plus" class="size-4" aria-hidden="true" />
        {{ entry.action.label }}
      </Button>
    </li>
  </ul>
</template>

<script setup lang="ts">
import { Plus } from '@lucide/vue';
import { computed, inject, type Component } from 'vue';

import { Button } from '@/Components/ui/button';

import { OVERVIEW_STATUS_KEY, type OverviewStatusEntry } from './overviewStatus';

export interface RecordNextAction {
  key: string;
  label: string;
  icon?: Component;
  /** The record is blocked on this step: outline it in brand, not as a second primary button. */
  attention?: boolean;
  run: () => void;
}

withDefaults(defineProps<{
  /** Steps the record needs, ahead of the empty sections' fill-in actions. */
  actions?: RecordNextAction[];
}>(), {
  actions: () => [],
});

// Empty `OverviewSection`s register here through the page's status registry; one without an action
// (a viewer who cannot fill it) is simply left out.
const registry = inject(OVERVIEW_STATUS_KEY, null);

const fillActions = computed(() => [...(registry?.entries ?? [])]
  .filter((pair): pair is [string, OverviewStatusEntry & { action: NonNullable<OverviewStatusEntry['action']> }] => Boolean(pair[1].action))
  .map(([id, entry]) => ({ id, action: entry.action })));
</script>
