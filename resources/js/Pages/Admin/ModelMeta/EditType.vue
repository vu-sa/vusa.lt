<template>
  <TypeForm :type-kind :content-types :type="contentType" @submit:form="submit" />
</template>

<script setup lang="ts">
import type { InertiaForm } from '@inertiajs/vue3';

import TypeForm from '@/Components/AdminForms/TypeForm.vue';

const props = defineProps<{
  typeKind: 'institutionType' | 'dutyType';
  contentType: App.Entities.InstitutionType | App.Entities.DutyType;
  contentTypes: Array<App.Entities.InstitutionType | App.Entities.DutyType>;
}>();
function submit(form: unknown): void {
  const resource = props.typeKind === 'institutionType' ? 'institutionTypes' : 'dutyTypes';
  (form as InertiaForm<Record<string, unknown>>).patch(route(`${resource}.update`, props.contentType.id), { preserveScroll: true });
}
</script>
