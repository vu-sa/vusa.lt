<template>
  <PaletteRow
    :value="action.id"
    :icon="action.icon"
    :title="action.label"
    :subtitle="action.workspaceLabel"
    @select="handleSelect"
  >
    <template v-if="action.page" #trailing>
      <PinButton :pinned="isPinned(action.page)" @toggle="togglePin(action.page)" />
    </template>
    <template v-else-if="action.shortcut" #trailing>
      <kbd class="hidden border border-border px-1.5 font-mono text-xs text-muted-foreground sm:inline-flex">{{ action.shortcut }}</kbd>
    </template>
  </PaletteRow>
</template>

<script setup lang="ts">
import type { CommandAction } from '../useCommandActions';

import PaletteRow from './PaletteRow.vue';
import PinButton from './PinButton.vue';

import { useCommandPalette } from '@/Composables/useCommandPalette';
import { useUIPreferences } from '@/Composables/useUIPreferences';

const props = defineProps<{
  action: CommandAction;
}>();

const { close } = useCommandPalette();
const { isPinned, togglePin } = useUIPreferences();

const handleSelect = () => {
  close();
  props.action.action();
};
</script>
