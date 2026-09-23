<template>
  <div data-slot="palette-field" class="contents">
    <button
      type="button"
      :class="[
        'hidden h-8 w-full max-w-md items-center gap-2 lg:flex',
        'border border-border bg-secondary/40 px-3 text-left text-sm text-muted-foreground',
        'transition-colors hover:border-brand hover:text-foreground',
      ]"
      @click="toggle"
    >
      <Search class="size-4 shrink-0" />
      <span class="flex-1 truncate">{{ $t('shell.chrome.search_field') }}</span>
      <kbd class="hidden border border-border px-1.5 font-mono text-xs lg:inline">{{ shortcut }}</kbd>
    </button>

    <Button
      variant="ghost"
      size="icon"
      class="u-touch lg:hidden"
      :aria-label="$t('shell.chrome.search')"
      @click="toggle"
    >
      <Search class="size-5" />
    </Button>
  </div>
</template>

<script setup lang="ts">
import { trans as $t } from 'laravel-vue-i18n';
import { Search } from 'lucide-vue-next';
import { computed } from 'vue';

import { Button } from '@/Components/ui/button';
import { useCommandPalette } from '@/Composables/useCommandPalette';

const { toggle } = useCommandPalette();

const shortcut = computed(() => (typeof navigator !== 'undefined' && /mac/i.test(navigator.platform) ? '⌘K' : 'Ctrl K'));
</script>
