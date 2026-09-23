<template>
  <div v-if="shortcuts.length > 0" class="flex flex-wrap items-center gap-2" data-slot="create-shortcuts">
    <span class="mr-1 text-sm text-muted-foreground">{{ $t('Sukurti') }}:</span>
    <Button
      v-for="shortcut in shortcuts"
      :key="shortcut.id"
      variant="outline"
      size="sm"
      class="pointer-coarse:h-11"
      @click="shortcut.action"
    >
      <component :is="shortcut.icon" aria-hidden="true" />
      {{ shortcut.label }}
    </Button>
  </div>
</template>

<script setup lang="ts">
import { trans as $t } from 'laravel-vue-i18n';
import { computed } from 'vue';

import { useCommandActions } from '@/Components/CommandPalette/useCommandActions';
import { Button } from '@/Components/ui/button';

const props = withDefaults(defineProps<{
  limit?: number;
}>(), {
  limit: 3,
});

// The catalog already knows what this person may create, in the order of their workspaces —
// a rep's first shortcut is recording a meeting. No second list to keep in step.
const { actions } = useCommandActions();

const shortcuts = computed(() => actions.value.filter(action => action.category === 'create').slice(0, props.limit));
</script>
