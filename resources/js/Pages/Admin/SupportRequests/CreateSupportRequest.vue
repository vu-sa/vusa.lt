<template>
  <AdminContentPage :title="$t('vusa.lt pagalba')" :back-url="route('mySupportRequests.index')">
    <template #below-header>
      <p class="mb-6 max-w-2xl text-sm text-muted-foreground">
        {{ $t('Čia galite pranešti apie vusa.lt svetainės problemas ir siūlyti patobulinimus.') }}
      </p>
    </template>

    <FormUpsertLayout>
      <SupportRequestForm
        :types
        :areas
        :roles
        :back-url="route('mySupportRequests.index')"
      />
    </FormUpsertLayout>
  </AdminContentPage>
</template>

<script setup lang="ts">
import { trans as $t } from 'laravel-vue-i18n';

import AdminContentPage from '@/Components/Layouts/AdminContentPage.vue';
import FormUpsertLayout from '@/Components/Layouts/FormUpsertLayout.vue';
import SupportRequestForm from '@/Components/SupportRequests/SupportRequestForm.vue';
import { usePageBreadcrumbs, BreadcrumbHelpers } from '@/Composables/useBreadcrumbsUnified';
import type {
  SupportRequestRoleOption,
  SupportRequestTaxonomyItem,
} from '@/Types/supportRequests';

defineProps<{
  types: SupportRequestTaxonomyItem[];
  areas: SupportRequestTaxonomyItem[];
  roles: SupportRequestRoleOption[];
  service?: SupportRequestTaxonomyItem;
}>();

usePageBreadcrumbs(() => [
  BreadcrumbHelpers.createRouteBreadcrumb($t('Mano pranešimai'), 'mySupportRequests.index'),
  BreadcrumbHelpers.createBreadcrumbItem($t('Naujas pranešimas')),
]);
</script>
