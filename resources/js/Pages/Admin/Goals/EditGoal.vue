<template>
  <GoalForm
    enable-delete
    :goal
    :tenants
    :cadences
    :statuses
    :duties
    @submit:form="handleSubmit"
    @delete="router.delete(route('goals.destroy', goal.id))"
  />
</template>

<script setup lang="ts">
import { router, type InertiaForm } from '@inertiajs/vue3';

import GoalForm from '@/Components/AdminForms/GoalForm.vue';

const props = defineProps<{
  goal: Record<string, unknown> & { id: string };
  tenants: { id: number; shortname: string }[];
  cadences: { id: string; label: string }[];
  statuses: { value: string; label: string }[];
  duties: { id: string; name: string; institution: string | null; tenant_id: number | null }[];
}>();

function handleSubmit(form: unknown): void {
  const inertiaForm = form as InertiaForm<Record<string, unknown>>;
  inertiaForm.patch(route('goals.update', props.goal.id), {
    preserveScroll: true,
    onSuccess: () => inertiaForm.defaults(),
  });
}
</script>
