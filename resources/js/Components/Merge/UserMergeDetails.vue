<template>
  <div class="space-y-3" data-slot="user-merge-details">
    <fieldset class="space-y-2">
      <legend class="text-sm font-medium">
        {{ $t('users.merge.login_email') }}
      </legend>
      <RadioGroup v-model="loginEmail" class="gap-2">
        <label
          v-for="email in emails"
          :key="email"
          :for="`login-email-${email}`"
          class="flex min-h-11 cursor-pointer items-center gap-3 border border-border px-3 py-2 text-sm"
        >
          <RadioGroupItem :id="`login-email-${email}`" :value="email" />
          <span class="truncate">{{ email }}</span>
        </label>
      </RadioGroup>
      <p class="text-xs text-muted-foreground">
        {{ $t('users.merge.login_email_hint') }}
      </p>
    </fieldset>

    <p class="text-sm text-status-attention">
      {{ $t('users.merge.permanent') }}
    </p>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, watch } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';

import { RadioGroup, RadioGroupItem } from '@/Components/ui/radio-group';

const props = defineProps<{
  records: { id: string | number; email?: string | null }[];
  targetId: string | number;
}>();

const loginEmail = defineModel<string | null>({ default: null });

const emails = computed(() => [...new Set(props.records.map(record => record.email).filter((email): email is string => Boolean(email)))]);

// Default to the kept record's own address, so keeping a record never silently changes its login.
// On mount rather than `immediate`: a write during the parent's render would not re-render it.
const useKeptRecordEmail = () => {
  loginEmail.value = props.records.find(record => String(record.id) === String(props.targetId))?.email ?? null;
};

onMounted(useKeptRecordEmail);
watch(() => props.targetId, useKeptRecordEmail);
</script>
