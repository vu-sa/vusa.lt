<template>
  <div
    data-slot="notification-type-row"
    :data-type="type.value"
    class="flex flex-col gap-3 border-b border-border px-4 py-3 last:border-b-0 sm:flex-row sm:items-start sm:justify-between sm:gap-6"
  >
    <div class="flex min-w-0 flex-col gap-0.5">
      <p class="text-sm font-bold text-foreground">
        {{ $t(`notifications.types.${type.value}.label`) }}
      </p>
      <p class="max-w-prose text-xs leading-snug text-pretty text-muted-foreground">
        {{ $t(`notifications.types.${type.value}.description`) }}
      </p>
      <div v-if="$slots.default" class="pt-2">
        <slot />
      </div>
    </div>

    <div class="flex shrink-0 items-center gap-2">
      <p
        v-if="type.lockedEmail"
        data-testid="email-locked"
        class="flex h-9 items-center gap-1.5 text-xs text-muted-foreground"
      >
        <Lock class="size-3.5 shrink-0" aria-hidden="true" />
        {{ $t('notifications.preferences.email_locked') }}
      </p>
      <Select
        v-else
        :model-value="email"
        @update:model-value="value => emit('update:email', value as EmailDeliveryValue)"
      >
        <SelectTrigger
          size="sm"
          class="w-40 pointer-coarse:h-11"
          data-testid="email-select"
          :aria-label="`${$t('notifications.preferences.email_label')}: ${$t(`notifications.types.${type.value}.label`)}`"
        >
          <SelectValue :icon="selectedOption.icon" />
        </SelectTrigger>
        <SelectContent>
          <SelectItem v-for="option in emailOptions" :key="option.value" :value="option.value" :icon="option.icon">
            {{ option.label }}
          </SelectItem>
        </SelectContent>
      </Select>

      <button
        type="button"
        data-testid="push-toggle"
        :aria-pressed="push"
        :disabled="!pushAvailable"
        :aria-label="`${$t('notifications.preferences.push_label')}: ${$t(`notifications.types.${type.value}.label`)}`"
        :class="[controlVariants({ size: 'sm', active: push && pushAvailable, voice: 'sentence' }), 'h-9 font-medium']"
        @click="emit('update:push', !push)"
      >
        <component :is="push && pushAvailable ? BellRing : BellOff" class="size-3.5" aria-hidden="true" />
        {{ $t('notifications.preferences.push_label') }}
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';
import { BellOff, BellRing, Lock } from 'lucide-vue-next';

import { controlVariants } from '@/Components/ui/control';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { emailDeliveryOptions, type EmailDeliveryValue } from '@/Features/Admin/Notifications/emailDelivery';

export interface NotificationTypeOption {
  value: string;
  section: string;
  sectionModelEnumKey: string;
  lockedEmail: EmailDeliveryValue | null;
  email: EmailDeliveryValue;
  push: boolean;
}

const props = defineProps<{
  type: NotificationTypeOption;
  email: EmailDeliveryValue;
  push: boolean;
  /** False with no connected device: a push choice there would silently reach nobody. */
  pushAvailable: boolean;
}>();

const emit = defineEmits<{
  'update:email': [value: EmailDeliveryValue];
  'update:push': [value: boolean];
}>();

const emailOptions = emailDeliveryOptions();

const selectedOption = computed(() => emailOptions.find(option => option.value === props.email) ?? emailOptions[0]);
</script>
