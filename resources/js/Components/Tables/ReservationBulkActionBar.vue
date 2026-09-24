<template>
  <!-- Shared by the resource table while its collection replacement lands. -->
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
      class="absolute -top-12 left-0 z-20 inline-flex flex-wrap items-center gap-2 border border-foreground/80 bg-popover p-2.5 text-popover-foreground"
      role="region"
      :aria-label="$t('reservations.actions.selected')"
      data-slot="reservation-bulk-actions"
    >
      <span class="text-xs font-bold uppercase tracking-wide">
        {{ $t('reservations.actions.selected') }}: <span class="tabular-nums text-brand">{{ count }}</span>
      </span>

      <div class="mx-1 h-4 w-px bg-border" />

      <Button size="sm" :disabled @click="$emit('approve')">
        <Check class="size-4" />
        {{ $t('reservations.actions.approve') }}
      </Button>

      <Button
        v-if="canReject"
        size="sm"
        variant="destructive"
        :disabled
        @click="$emit('reject')"
      >
        <X class="size-4" />
        {{ $t('reservations.actions.reject') }}
      </Button>

      <!-- Housekeeping: close a stale item out in one go instead of stepping through it. -->
      <Button size="sm" variant="secondary" :disabled @click="$emit('resolve')">
        <CheckCheck class="size-4" />
        {{ $t('reservations.actions.resolve') }}
      </Button>

      <Button size="icon-sm" variant="ghost" :aria-label="$t('Atšaukti žymėjimą')" @click="$emit('clear')">
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
  // eslint-disable-next-line vue/no-boolean-default
  canReject: true,
});

defineEmits<{
  approve: [];
  reject: [];
  resolve: [];
  clear: [];
}>();
</script>
