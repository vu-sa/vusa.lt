<template>
  <BannerForm
    remember-key="CreateBanner"
    :banner
    @submit:form="submitForm"
  />
</template>

<script setup lang="ts">
import type { InertiaForm } from '@inertiajs/vue3';

import BannerForm from '@/Components/AdminForms/BannerForm.vue';
import { BreadcrumbHelpers, usePageBreadcrumbs } from '@/Composables/useBreadcrumbsUnified';
import { BannerIcon } from '@/Components/icons';

usePageBreadcrumbs(
  BreadcrumbHelpers.adminForm('Baneriai', 'banners.index', 'Naujas baneris', BannerIcon),
);

const banner = {
  title: '',
  link_url: '',
  is_active: 1,
  image_url: '',
};

function submitForm(form: unknown): void {
  (form as InertiaForm<Record<string, unknown>>).post(route('banners.store'));
}
</script>
