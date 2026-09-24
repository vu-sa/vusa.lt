<template>
  <FormFieldWrapper id="digest-emails" :label="$t('notifications.preferences.digest_emails')">
    <div class="space-y-2">
      <p class="text-sm text-muted-foreground mb-3">
        {{ $t('notifications.preferences.digest_emails_description') }}
      </p>
      <div class="space-y-2">
        <label
          v-for="emailOption in availableEmails"
          :key="emailOption.email"
          :for="`digest-email-${emailOption.email}`"
          data-slot="digest-email-option"
          :class="[
            'flex items-center gap-3 border border-border p-3 cursor-pointer select-none transition-colors pointer-coarse:min-h-11',
            'hover:bg-accent/60',
            isSelected(emailOption.email) ? 'border-brand/40 bg-brand/5' : '',
          ]"
        >
          <Checkbox
            :id="`digest-email-${emailOption.email}`"
            :model-value="isSelected(emailOption.email)"
            @update:model-value="(checked) => toggleEmail(emailOption.email, checked === true)"
          />
          <div class="flex-1 flex items-center gap-2 min-w-0">
            <component
              :is="emailOption.type === 'duty' ? Briefcase : User"
              class="size-4 shrink-0 text-muted-foreground"
            />
            <span class="font-mono text-sm truncate">{{ emailOption.email }}</span>
            <span class="text-xs text-muted-foreground shrink-0">
              ({{ emailOption.type === 'duty' ? $t('notifications.preferences.duty_email') : $t('notifications.preferences.personal_email') }})
            </span>
          </div>
        </label>
      </div>
      <p v-if="selectedEmails.length === 0" class="text-xs text-[var(--status-attention)] flex items-center gap-1.5 mt-2">
        <Info class="size-3.5 shrink-0" aria-hidden="true" />
        {{ $t('notifications.preferences.digest_emails_default_info') }}
      </p>
    </div>
  </FormFieldWrapper>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';
import { Briefcase, Info, User } from 'lucide-vue-next';

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
