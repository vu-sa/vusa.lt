<template>
  <div class="flex items-center justify-between gap-4 py-3 text-left">
    <dt class="w-32 shrink-0 text-xs font-bold uppercase tracking-wide text-muted-foreground">
      {{ label }}
    </dt>
    <!-- The button lives inside the dd: a <dl> may only hold dt/dd groups. -->
    <dd class="flex min-w-0 flex-1 items-center justify-end gap-3 text-right">
      <span class="min-w-0 truncate text-sm font-bold text-foreground">{{ value || '—' }}</span>
      <Button
        v-if="editable"
        variant="ghost"
        size="sm"
        class="h-7 shrink-0 px-2 text-xs font-bold uppercase tracking-wide text-brand hover:bg-brand/10 hover:text-brand pointer-coarse:h-11"
        @click="emit('edit')"
      >
        {{ $t('action_window.common.change') }}
      </Button>
    </dd>
  </div>
</template>

<script setup lang="ts">
import { Button } from '@/Components/ui/button';

withDefaults(defineProps<{
  label: string;
  value?: string;
  /** False when the screen that owns this value was skipped and cannot be revisited. */
  editable?: boolean;
}>(), {
  editable: true,
});

const emit = defineEmits<(e: 'edit') => void>();
</script>
