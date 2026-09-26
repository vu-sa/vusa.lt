<template>
  <ProblemForm
    remember-key="CreateProblem"
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
