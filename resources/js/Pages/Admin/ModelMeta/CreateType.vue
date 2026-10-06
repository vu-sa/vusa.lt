<template>
  <TypeForm :type-kind :remember-key="`Create${typeKind}`" :content-types :type @submit:form="submit" />
</template>

<script setup lang="ts">
import type { InertiaForm } from '@inertiajs/vue3';

import TypeForm from '@/Components/AdminForms/TypeForm.vue';

const props = defineProps<{
  typeKind: 'institutionType' | 'dutyType';
  contentTypes: Array<App.Entities.InstitutionType | App.Entities.DutyType>;
}>();
const type = { title: { lt: '', en: '' }, description: { lt: '', en: '' }, parent_id: null, slug: '', extra_attributes: {} } as App.Entities.InstitutionType | App.Entities.DutyType;
function submit(form: unknown): void {
  const resource = props.typeKind === 'institutionType' ? 'institutionTypes' : 'dutyTypes';
  (form as InertiaForm<Record<string, unknown>>).post(route(`${resource}.store`));
}
</script>
