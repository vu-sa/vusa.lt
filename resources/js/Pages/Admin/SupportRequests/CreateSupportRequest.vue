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
        :context="reportContext"
        :back-url="route('mySupportRequests.index')"
      />
    </FormUpsertLayout>
  </AdminContentPage>
</template>

<script setup lang="ts">
import { computed } from 'vue';
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

const reportContext = computed<{ url?: string; viewport?: string; browser?: string } | undefined>(() => {
  if (typeof window === 'undefined') {
    return undefined;
  }

  try {
    const raw = new URLSearchParams(window.location.search).get('context');
    const context = raw ? JSON.parse(raw) as Record<string, unknown> : null;

    return context && typeof context.url === 'string'
      ? {
          url: context.url,
          viewport: typeof context.viewport === 'string' ? context.viewport : undefined,
          browser: typeof context.browser === 'string' ? context.browser : undefined,
        }
      : undefined;
  }
  catch {
    return undefined;
  }
});

usePageBreadcrumbs(() => [
  BreadcrumbHelpers.createRouteBreadcrumb($t('Mano pranešimai'), 'mySupportRequests.index'),
  BreadcrumbHelpers.createBreadcrumbItem($t('Naujas pranešimas')),
]);
</script>
