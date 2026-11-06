<template>
  <UserSearchField
    :model-value
    :taken-ids
    :error
    :allow-create
    :label="$t('Narys')"
    permission="duties.update.padalinys"
    scope="all"
    @update:model-value="emit('update:modelValue', $event)"
  />
</template>

<script setup lang="ts">
import { trans as $t } from 'laravel-vue-i18n';

import UserSearchField, { type MemberHit } from '@/Features/Admin/AdminSearch/Components/Select/UserSearchField.vue';

export type { MemberHit } from '@/Features/Admin/AdminSearch/Components/Select/UserSearchField.vue';

// Searching all units lets a member join another unit through a new duty.
defineProps<{
  modelValue: MemberHit | null;
  takenIds?: string[];
  error?: string;
  allowCreate?: boolean;
}>();

const emit = defineEmits<(e: 'update:modelValue', value: MemberHit | null) => void>();
</script>
