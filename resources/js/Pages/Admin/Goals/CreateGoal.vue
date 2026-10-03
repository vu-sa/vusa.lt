<template>
  <GoalForm
    :goal
    :tenants
    :cadences
    :statuses
    :duties
    @submit:form="handleSubmit"
  />
</template>

<script setup lang="ts">
import type { InertiaForm } from '@inertiajs/vue3';

import GoalForm from '@/Components/AdminForms/GoalForm.vue';

defineProps<{
  goal: { tenant_id: number | null; cadence_id: string | null };
  tenants: { id: number; shortname: string }[];
  cadences: { id: string; label: string }[];
  statuses: { value: string; label: string }[];
  duties: { id: string; name: string; institution: string | null; tenant_id: number | null }[];
}>();

function handleSubmit(form: unknown): void {
  (form as InertiaForm<Record<string, unknown>>).post(route('goals.store'));
}
</script>
