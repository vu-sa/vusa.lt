<template>
  <UserSearchField
    :model-value
    :taken-ids
    :error
    :allow-create
    :label="$t('Narys')"
    purpose="duty-member"
    @update:model-value="emit('update:modelValue', $event)"
  />
</template>

<script setup lang="ts">
import { trans as $t } from 'laravel-vue-i18n';

import UserSearchField, { type MemberHit } from '@/Components/Pickers/UserSearchField.vue';

export type { MemberHit } from '@/Components/Pickers/UserSearchField.vue';

// Searching all units lets a member join another unit through a new duty.
defineProps<{
  modelValue: MemberHit | null;
  takenIds?: string[];
  error?: string;
  allowCreate?: boolean;
}>();

const emit = defineEmits<(e: 'update:modelValue', value: MemberHit | null) => void>();
</script>
