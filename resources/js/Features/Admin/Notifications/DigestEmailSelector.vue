<template>
  <FormFieldWrapper id="notification-emails" :label="$t('notifications.preferences.emails_label')" :hint="$t('notifications.preferences.emails_hint', { email: defaultEmail })">
    <div class="space-y-2">
      <div class="space-y-2">
        <label
          v-for="emailOption in availableEmails"
          :key="emailOption.email"
          :for="`notification-email-${emailOption.email}`"
          data-slot="digest-email-option"
          :class="[
            'flex items-center gap-3 border border-border p-3 cursor-pointer select-none transition-colors pointer-coarse:min-h-11',
            'hover:bg-accent/60',
            isSelected(emailOption.email) ? 'border-brand/40 bg-brand/5' : '',
          ]"
        >
          <Checkbox
            :id="`notification-email-${emailOption.email}`"
            :model-value="isSelected(emailOption.email)"
            @update:model-value="(checked) => toggleEmail(emailOption.email, checked === true)"
          />
          <div class="flex-1 flex items-center gap-2 min-w-0">
            <component
              :is="emailOption.type === 'duty' ? Briefcase : User"
              class="size-4 shrink-0 text-muted-foreground"
            />
            <span class="truncate text-sm">{{ emailOption.email }}</span>
            <span class="shrink-0 text-xs text-muted-foreground">
              ({{ emailOption.type === 'duty' ? $t('notifications.preferences.duty_email') : $t('notifications.preferences.personal_email') }})
            </span>
          </div>
        </label>
      </div>
    </div>
  </FormFieldWrapper>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';
import { Briefcase, User } from 'lucide-vue-next';

import { Checkbox } from '@/Components/ui/checkbox';
import FormFieldWrapper from '@/Components/AdminForms/FormFieldWrapper.vue';

interface EmailOption {
  email: string;
  label: string;
  type: 'user' | 'duty';
}

const props = defineProps<{
  availableEmails: EmailOption[];
  modelValue: string[];
  /** Where mail goes while nothing is selected. */
  defaultEmail: string;
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
