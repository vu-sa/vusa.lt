<template>
  <span
    v-if="label"
    class="inline-flex w-fit items-center gap-1 border border-status-info-border bg-status-info-surface px-1.5 py-0.5 text-xs text-status-info"
    data-slot="purpose-chip"
  >
    <Compass class="size-3" aria-hidden="true" />{{ label }}
  </span>
  <span v-else-if="showAccess" class="text-xs text-muted-foreground">{{ $t('responsibilities.index.access_only') }}</span>
</template>

<script setup lang="ts">
import { trans as $t } from 'laravel-vue-i18n';
import { Compass } from '@lucide/vue';

/** Marks the few responsibilities the app finds people by; the rest only grant permissions. */
defineProps<{
  label: string | null;
  /** Say "Prieiga" for a responsibility without a purpose instead of rendering nothing. */
  showAccess?: boolean;
}>();
</script>
