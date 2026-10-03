<template>
  <ProblemForm
    enable-delete
    :problem
    :tenants
    :categories
    :initial-responsible-user
    :institutions
    @submit:form="handleUpdateProblem"
    @delete="() => router.delete(route('problems.destroy', problem.id))"
  />
</template>

<script setup lang="ts">
import { router, type InertiaForm } from '@inertiajs/vue3';

import ProblemForm from '@/Components/AdminForms/ProblemForm.vue';

const props = defineProps<{
  problem: App.Entities.Problem;
  tenants: Array<App.Entities.Tenant>;
  categories: Array<App.Entities.ProblemCategory>;
  initialResponsibleUser?: { id: string; name: string } | null;
  institutions: Array<App.Entities.Institution>;
}>();

function handleUpdateProblem(form: unknown) {
  (form as InertiaForm<Record<string, unknown>>)
    .transform(data => ({
      ...data,
      _method: 'patch',
    }))
    .post(route('problems.update', props.problem.id), {
      preserveScroll: true,
    });
}
</script>
