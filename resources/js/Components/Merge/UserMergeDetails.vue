<template>
  <div class="space-y-3" data-slot="user-merge-details">
    <fieldset class="min-w-0 space-y-2">
      <legend class="text-sm font-medium">
        {{ $t('users.merge.login_email') }}
      </legend>
      <RadioGroup v-model="loginEmail" class="min-w-0 gap-2">
        <label
          v-for="email in emails"
          :key="email"
          :for="`login-email-${email}`"
          class="flex min-h-11 min-w-0 cursor-pointer items-center gap-3 border border-border px-3 py-2 text-sm"
        >
          <RadioGroupItem :id="`login-email-${email}`" :value="email" class="shrink-0" />
          <span class="truncate">{{ email }}</span>
        </label>
      </RadioGroup>
      <p class="text-xs text-muted-foreground">
        {{ $t(canUpdateIdentity ? 'users.merge.login_email_hint' : 'users.merge.login_email_locked') }}
      </p>
    </fieldset>

    <UserMergeDutyEmails
      v-model="dutiableEmailIds"
      :records
      :target-id
      :login-email
      @loading="value => emit('loading', value)"
    />

    <p class="text-xs text-muted-foreground">
      {{ $t('users.merge.photos_preserved') }}
    </p>

    <p class="text-sm text-status-attention">
      {{ $t('users.merge.permanent') }}
    </p>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, watch } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';

import UserMergeDutyEmails from './UserMergeDutyEmails.vue';

import { RadioGroup, RadioGroupItem } from '@/Components/ui/radio-group';

const props = defineProps<{
  records: { id: string | number; email?: string | null; can_update_identity: boolean }[];
  targetId: string | number;
}>();

const loginEmail = defineModel<string | null>({ default: null });
const dutiableEmailIds = defineModel<string[]>('dutiableEmailIds', { default: () => [] });
const emit = defineEmits<{ loading: [value: boolean] }>();

const target = computed(() => props.records.find(record => String(record.id) === String(props.targetId)));
const canUpdateIdentity = computed(() => target.value?.can_update_identity === true);
const emails = computed(() => [...new Set(
  (canUpdateIdentity.value ? props.records : [target.value])
    .map(record => record?.email)
    .filter((email): email is string => Boolean(email)),
)]);

// Default to the kept record's own address, so keeping a record never silently changes its login.
// On mount rather than `immediate`: a write during the parent's render would not re-render it.
const useKeptRecordEmail = () => {
  loginEmail.value = target.value?.email ?? null;
};

onMounted(useKeptRecordEmail);
watch(() => props.targetId, useKeptRecordEmail);
</script>
