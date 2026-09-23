<template>
  <RelationshipForm remember-key="CreateRelationship" :relationship @submit:form="(form) => (form as InertiaForm<Record<string, unknown>>).post(route('relationships.store'))" />
</template>

<script setup lang="ts">
import type { InertiaForm } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';

import RelationshipForm from '@/Components/AdminForms/RelationshipForm.vue';
import { BreadcrumbHelpers, usePageBreadcrumbs } from '@/Composables/useBreadcrumbsUnified';

const relationship = {
  name: '',
  slug: '',
  description: '',
} as App.Entities.Relationship;

usePageBreadcrumbs(BreadcrumbHelpers.adminForm($t('Ryšiai'), 'relationships.index', $t('Naujas ryšys')));
</script>
