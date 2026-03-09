<template>
  <FormFieldWrapper id="digest-emails" :label="$t('notifications.preferences.digest_emails')">
    <div class="space-y-2">
      <p class="text-sm text-muted-foreground mb-3">
        {{ $t('notifications.preferences.digest_emails_description') }}
      </p>
      <div class="space-y-2">
        <div
          v-for="emailOption in availableEmails"
          :key="emailOption.email"
          class="flex items-center gap-3 p-3 border rounded-lg dark:border-zinc-700 hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition-colors"
        >
          <Checkbox
            :id="`digest-email-${emailOption.email}`"
            :model-value="isSelected(emailOption.email)"
            @update:model-value="(checked) => toggleEmail(emailOption.email, checked === true)"
          />
          <label
            :for="`digest-email-${emailOption.email}`"
            class="flex-1 flex items-center gap-2 cursor-pointer"
          >
            <component
              :is="emailOption.type === 'duty' ? IFluentBriefcase24Regular : IFluentPerson24Regular"
              class="size-4 text-zinc-500"
            />
            <span class="font-mono text-sm">{{ emailOption.email }}</span>
            <span class="text-xs text-muted-foreground">
              ({{ emailOption.type === 'duty' ? $t('notifications.preferences.duty_email') : $t('notifications.preferences.personal_email') }})
            </span>
          </label>
        </div>
      </div>
      <p v-if="selectedEmails.length === 0" class="text-sm text-amber-600 dark:text-amber-400 flex items-center gap-2 mt-2">
        <IFluentInfo24Regular class="size-4" />
        {{ $t('notifications.preferences.digest_emails_default_info') }}
      </p>
    </div>
  </FormFieldWrapper>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';

import { Checkbox } from '@/Components/ui/checkbox';
import FormFieldWrapper from '@/Components/AdminForms/FormFieldWrapper.vue';
import IFluentBriefcase24Regular from '~icons/fluent/briefcase-24-regular';
import IFluentPerson24Regular from '~icons/fluent/person-24-regular';
import IFluentInfo24Regular from '~icons/fluent/info-24-regular';

interface EmailOption {
  email: string;
  label: string;
  type: 'user' | 'duty';
}

const props = defineProps<{
  availableEmails: EmailOption[];
  modelValue: string[];
}>();

const emit = defineEmits<{
  'update:modelValue': [value: string[]];
}>();

const selectedEmails = computed({
  get: () => props.modelValue,
  set: (value: string[]) => emit('update:modelValue', value),
});

const isSelected = (email: string): boolean => {
  return selectedEmails.value.includes(email);
};

const toggleEmail = (email: string, checked: boolean) => {
  if (checked) {
    selectedEmails.value = [...selectedEmails.value, email];
  }
  else {
    selectedEmails.value = selectedEmails.value.filter(e => e !== email);
  }
};
</script>
