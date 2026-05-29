<template>
  <Dialog :open="open" @update:open="emit('update:open', $event)">
    <DialogContent class="sm:max-w-sm">
      <DialogHeader>
        <DialogTitle>{{ $t('Klaviatūros trumpiniai') }}</DialogTitle>
      </DialogHeader>

      <div class="divide-y">
        <div
          v-for="shortcut in shortcuts"
          :key="shortcut.label"
          class="flex items-center justify-between py-2.5"
        >
          <span class="text-sm">{{ $t(shortcut.label) }}</span>
          <kbd class="inline-flex h-6 items-center rounded border bg-muted/60 px-2 font-mono text-xs font-medium text-muted-foreground">
            {{ shortcut.keys }}
          </kbd>
        </div>
      </div>
    </DialogContent>
  </Dialog>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';

import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
} from '@/Components/ui/dialog';

defineProps<{ open: boolean }>();
const emit = defineEmits<(e: 'update:open', value: boolean) => void>();

const isMac = computed(() => {
  if (typeof navigator === 'undefined') {
    return false;
  }
  return navigator.platform.toUpperCase().indexOf('MAC') >= 0;
});

const mod = computed(() => isMac.value ? '⌘' : 'Ctrl');

const shortcuts = computed(() => [
  { label: 'Paieška', keys: `${mod.value}${isMac.value ? '' : '+'}K` },
  { label: 'Perjungti šoninę juostą', keys: `${mod.value}${isMac.value ? '' : '+'}B` },
  { label: 'Klaviatūros trumpiniai', keys: '?' },
]);
</script>
