<template>
  <ResponsibilityForm :responsibility @submit:form="submit" />
</template>

<script setup lang="ts">
import type { InertiaForm } from '@inertiajs/vue3';

import ResponsibilityForm, { type ResponsibilityFormData } from '@/Components/AdminForms/ResponsibilityForm.vue';

const props = defineProps<{
  responsibility: { id: string; title: { lt?: string; en?: string }; description: { lt?: string; en?: string }; purpose_label: string | null };
}>();

function submit(form: InertiaForm<ResponsibilityFormData>): void {
  form.defaults();
  form.patch(route('responsibilities.update', props.responsibility.id));
}
</script>
