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
