<template>
  <section v-if="shortcuts.length > 0" class="flex flex-col gap-3" data-slot="create-shortcuts">
    <h2 class="text-xs font-bold uppercase tracking-[0.2em] text-muted-foreground">
      {{ $t('Greiti veiksmai') }}
    </h2>
    <div class="grid grid-cols-2 gap-3 sm:flex sm:flex-wrap">
      <Button
        v-for="(shortcut, index) in shortcuts"
        :key="shortcut.id"
        :variant="index === 0 ? 'brand' : 'outline'"
        voice="sentence"
        size="lg"
        class="h-auto min-h-12 whitespace-normal px-4 text-center sm:text-left"
        @click="shortcut.action"
      >
        <component :is="shortcut.icon" class="size-4" aria-hidden="true" />
        {{ shortcut.label }}
      </Button>
    </div>
  </section>
</template>

<script setup lang="ts">
import { trans as $t } from 'laravel-vue-i18n';
import { computed } from 'vue';

import { useCommandActions } from '@/Components/CommandPalette/useCommandActions';
import { Button } from '@/Components/ui/button';

const props = withDefaults(defineProps<{
  limit?: number;
}>(), {
  limit: 4,
});

const { actions } = useCommandActions();

const shortcuts = computed(() => {
  const permitted = actions.value.filter(action => action.category === 'create');
  const selected = permitted.filter((action, index) =>
    action.workspaceKey && permitted.findIndex(candidate => candidate.workspaceKey === action.workspaceKey) === index);
  const selectedIds = new Set(selected.map(action => action.id));

  return [...selected, ...permitted.filter(action => !selectedIds.has(action.id))].slice(0, props.limit);
});
</script>
