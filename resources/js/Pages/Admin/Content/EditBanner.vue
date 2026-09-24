<template>
  <BannerForm
    :banner
    enable-delete
    @submit:form="submitForm"
    @delete="deleteBanner"
  />
</template>

<script setup lang="ts">
import { router, type InertiaForm } from '@inertiajs/vue3';

import BannerForm from '@/Components/AdminForms/BannerForm.vue';

const props = defineProps<{
  banner: App.Entities.Banner;
}>();

function submitForm(form: unknown): void {
  const inertiaForm = form as InertiaForm<App.Entities.Banner>;
  inertiaForm.defaults();
  inertiaForm.patch(route('banners.update', props.banner.id), { preserveScroll: true });
}

function deleteBanner(): void {
  router.delete(route('banners.destroy', props.banner.id));
}
</script>
