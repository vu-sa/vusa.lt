<template>
  <RelationshipForm
    :relationship
    enable-delete
    @submit:form="submit"
    @delete="router.delete(route('relationships.destroy', relationship.id))"
  />
</template>

<script setup lang="ts">
import { router, type InertiaForm } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';

import RelationshipForm from '@/Components/AdminForms/RelationshipForm.vue';
import { BreadcrumbHelpers, usePageBreadcrumbs } from '@/Composables/useBreadcrumbsUnified';

const props = defineProps<{ relationship: App.Entities.Relationship }>();

usePageBreadcrumbs(() => BreadcrumbHelpers.adminForm($t('Ryšiai'), 'relationships.index', props.relationship.name));

function submit(form: unknown): void {
  const relationshipForm = form as InertiaForm<App.Entities.Relationship>;
  relationshipForm.defaults();
  relationshipForm.patch(route('relationships.update', props.relationship.id), { preserveScroll: true });
}
</script>
