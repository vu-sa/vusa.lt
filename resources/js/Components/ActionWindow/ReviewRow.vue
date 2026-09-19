<template>
  <div class="flex items-center gap-3 px-1 py-3 text-left">
    <dt class="w-28 shrink-0 text-sm text-muted-foreground">
      {{ label }}
    </dt>
    <!-- The button lives inside the dd: a <dl> may only hold dt/dd groups. -->
    <dd class="flex min-w-0 flex-1 items-center gap-3">
      <span class="min-w-0 flex-1 truncate text-sm font-medium text-foreground">{{ value || '—' }}</span>
      <Button v-if="editable" variant="ghost" size="sm" class="shrink-0 pointer-coarse:h-11" @click="emit('edit')">
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
