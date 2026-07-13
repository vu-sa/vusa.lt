<template>
  <!--
    The one bulk-action bar for reservations. Both the reservation page (ReservationResourceTable)
    and the reservations dashboard (ReservationsTable) render this, so the two can never drift apart
    in wording, iconography or layout again.
  -->
  <Transition
    enter-active-class="transition-all duration-200 ease-out"
    enter-from-class="opacity-0 -translate-y-2 scale-95"
    enter-to-class="opacity-100 translate-y-0 scale-100"
    leave-active-class="transition-all duration-150 ease-in"
    leave-from-class="opacity-100 translate-y-0 scale-100"
    leave-to-class="opacity-0 -translate-y-2 scale-95"
  >
    <div
      v-if="count > 0"
      class="absolute -top-12 left-0 z-20 inline-flex items-center gap-2 rounded-lg border border-zinc-200 bg-white px-3 py-2 shadow-lg dark:border-zinc-700 dark:bg-zinc-800"
    >
      <div class="flex size-6 items-center justify-center rounded-full bg-zinc-900 text-xs font-bold text-white dark:bg-zinc-100 dark:text-zinc-900">
        {{ count }}
      </div>
      <span class="text-sm font-medium text-zinc-600 dark:text-zinc-400">
        {{ $t('reservations.actions.selected') }}
      </span>

      <div class="mx-1 h-4 w-px bg-zinc-200 dark:bg-zinc-700" />

      <Button size="sm" :disabled="disabled" @click="$emit('approve')">
        <Check class="size-4" />
        {{ $t('reservations.actions.approve') }}
      </Button>

      <Button
        v-if="canReject"
        size="sm"
        variant="destructive"
        :disabled="disabled"
        @click="$emit('reject')"
      >
        <X class="size-4" />
        {{ $t('reservations.actions.reject') }}
      </Button>

      <!-- Housekeeping: close a stale item out in one go instead of stepping through it. -->
      <Button size="sm" variant="secondary" :disabled="disabled" @click="$emit('resolve')">
        <CheckCheck class="size-4" />
        {{ $t('reservations.actions.resolve') }}
      </Button>

      <Button size="icon-sm" variant="ghost" @click="$emit('clear')">
        <X class="size-4" />
      </Button>
    </div>
  </Transition>
</template>

<script setup lang="ts">
import { trans as $t } from 'laravel-vue-i18n';
import { Check, CheckCheck, X } from 'lucide-vue-next';

import { Button } from '@/Components/ui/button';

withDefaults(defineProps<{
  count: number;
  /** Rejection is only legal out of `created`, so hide it when nothing selected qualifies. */
  canReject?: boolean;
  disabled?: boolean;
}>(), {
  canReject: true,
  disabled: false,
});

defineEmits<{
  approve: [];
  reject: [];
  resolve: [];
  clear: [];
}>();
</script>
