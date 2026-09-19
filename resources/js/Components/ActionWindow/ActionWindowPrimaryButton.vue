<template>
  <Button
    variant="brand"
    size="lg"
    data-slot="action-window-primary"
    class="w-full text-xs font-bold uppercase tracking-wide pointer-coarse:h-11"
    :disabled="disabled || loading"
  >
    <Loader2 v-if="loading" class="size-4 animate-spin" aria-hidden="true" />
    <slot />
  </Button>
</template>

<script setup lang="ts">
import { Loader2 } from 'lucide-vue-next';

import { Button } from '@/Components/ui/button';

defineProps<{
  disabled?: boolean;
  /** Shows a spinner and blocks a second tap while the request is in flight. */
  loading?: boolean;
}>();
</script>
