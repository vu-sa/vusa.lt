<template>
  <PageContent :title="banner.title" :back-url="route('banners.index')" :heading-icon="Icons.BANNER">
    <UpsertModelLayout>
      <BannerForm :banner enable-delete
        @submit:form="(form) => form.patch(route('banners.update', banner.id), { preserveScroll: true })"
        @delete="() => router.delete(route('banners.destroy', banner.id))" />
    </UpsertModelLayout>
  </PageContent>
</template>

<script setup lang="ts">
import { router } from "@inertiajs/vue3";

import BannerForm from "@/Components/AdminForms/BannerForm.vue";
import Icons from "@/Types/Icons/regular";
import PageContent from "@/Components/Layouts/AdminContentPage.vue";
import UpsertModelLayout from "@/Components/Layouts/FormUpsertLayout.vue";

defineProps<{
  banner: App.Entities.Banner;
}>();
</script>
