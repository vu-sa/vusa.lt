<template>
  <FormFieldWrapper :id :label="$t('Būsena')" :error :hint>
    <FormSegmentedControl :id v-model="active" :options :aria-label="$t('Būsena')" :test-id-prefix="id" />
  </FormFieldWrapper>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';

import FormFieldWrapper from './FormFieldWrapper.vue';

import { FormSegmentedControl } from '@/Components/Patterns';
import { activityStatuses, statusRoleClasses } from '@/Constants/statuses';

defineProps<{ id: string; error?: string; hint?: string }>();
const active = defineModel<boolean>({ required: true });
const options = computed(() => [false, true].map((value) => {
  const status = activityStatuses[value ? 'active' : 'inactive'];
  return { value, label: $t(status.label), icon: status.icon, activeClass: statusRoleClasses[status.role] };
}));
</script>
