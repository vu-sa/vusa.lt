<template>
  <PageContent :title="resource.name[$page.props.app.locale]" :back-url="route('resources.index')"
    :heading-icon="Icons.RESOURCE">
    <Card class="mb-4 min-w-[450px]">
      <CardHeader>
        <CardTitle>{{ $t('Rezervacijų istorija') }}</CardTitle>
      </CardHeader>
      <CardContent>
        <ResourceReservationsTable :reservations="resource.reservations" />
      </CardContent>
    </Card>
    <UpsertModelLayout>
      <ResourceForm enable-delete :resource :categories :assignable-tenants
        @submit:form="handleResourceUpdate"
        @delete="() => router.delete(route('resource.destroy', resource.id))" />
    </UpsertModelLayout>
  </PageContent>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';
import { router, usePage } from '@inertiajs/vue3';

import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { BreadcrumbHelpers, usePageBreadcrumbs } from '@/Composables/useBreadcrumbsUnified';
import Icons from '@/Types/Icons/regular';
import PageContent from '@/Components/Layouts/AdminContentPage.vue';
import ResourceForm from '@/Components/AdminForms/ResourceForm.vue';
import UpsertModelLayout from '@/Components/Layouts/FormUpsertLayout.vue';
import ResourceReservationsTable from '@/Components/Tables/ResourceReservationsTable.vue';

export type ResourceEditType = Omit<
  App.Entities.Resource,
  'created_at' | 'updated_at' | 'deleted_at' | 'name' | 'description'
> & {
  name: Record<'lt' | 'en', string>;
  description: Record<'lt' | 'en', string>;
  media: Record<string, never>[];
  // media: models.Media[];
};

const { resource } = defineProps<{
  resource: ResourceEditType;
  categories: any;
  assignableTenants: Array<App.Entities.Tenant>;
}>();

// Generate breadcrumbs automatically with new simplified API
usePageBreadcrumbs(() =>
  BreadcrumbHelpers.adminForm('Ištekliai', 'resources.index', resource.name[usePage().props.app.locale], Icons.RESOURCE),
);

function handleResourceUpdate(form: InertiaForm<ResourceForm>) {
  form.transform(data => ({
    ...data,
    _method: 'patch',
  })).post(route('resources.update', resource.id), {
    preserveScroll: true,
    forceFormData: true,
  });
}
</script>
