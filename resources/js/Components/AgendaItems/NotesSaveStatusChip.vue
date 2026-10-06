<template>
  <Transition name="fade" mode="out-in">
    <span v-if="status === 'saving'" key="saving" class="flex items-center gap-1 text-xs text-muted-foreground">
      <Loader2 class="h-3 w-3 animate-spin" />
      {{ $t('Saugoma…') }}
    </span>
    <span v-else-if="status === 'dirty'" key="dirty" class="flex items-center gap-1 text-xs text-status-attention">
      <span class="size-1.5 animate-pulse bg-status-attention" />
      {{ $t('Neišsaugota') }}
    </span>
    <span v-else-if="status === 'saved'" key="saved" class="flex items-center gap-1 text-xs text-status-success">
      <Check class="h-3 w-3" />
      {{ $t('Įrašyta') }}
    </span>
    <span v-else key="idle" />
  </Transition>
</template>

<script setup lang="ts">
import { trans as $t } from 'laravel-vue-i18n';
import { Check, Loader2 } from 'lucide-vue-next';

import type { NotesSaveStatus } from '@/Composables/useAgendaItemNotes';

defineProps<{ status: NotesSaveStatus }>();
</script>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
