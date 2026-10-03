<template>
  <ProblemForm
    remember-key="CreateProblem"
    :problem="problem ?? undefined"
    :tenants
    :categories
    :institutions
    @submit:form="handleSubmit"
  />
</template>

<script setup lang="ts">
import type { InertiaForm } from '@inertiajs/vue3';

import ProblemForm from '@/Components/AdminForms/ProblemForm.vue';

defineProps<{
  /** Prefilled when opened from an institution. */
  problem?: { tenant_id: number; institutions: string[] } | null;
  tenants: Array<App.Entities.Tenant>;
  categories: Array<App.Entities.ProblemCategory>;
  institutions: Array<App.Entities.Institution>;
}>();

const handleSubmit = (form: unknown) => {
  (form as InertiaForm<Record<string, unknown>>).post(route('problems.store'), {
    preserveScroll: true,
  });
};
</script>
