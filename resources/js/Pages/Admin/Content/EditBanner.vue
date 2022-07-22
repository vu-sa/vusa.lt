<template>
  <AdminLayout :title="banner.title" :back-url="route('banners.index')">
    <UpsertModelLayout :errors="$attrs.errors" :model="banner">
      <BannerForm
        :banner="banner"
        model-route="banners.update"
        delete-model-route="banners.destroy"
      />
    </UpsertModelLayout>
  </AdminLayout>
</template>

<script setup lang="ts">
import route from "ziggy-js";

import AdminLayout from "@/Layouts/AdminLayout.vue";
import BannerForm from "@/components/Admin/Forms/BannerForm.vue";
import UpsertModelLayout from "@/components/Admin/Layouts/UpsertModelLayout.vue";

defineProps<{
  banner: App.Models.Banner;
}>();
</script>
