<template>
  <AdminLayout title="Naujas baneris" :back-url="route('banners.index')">
    <UpsertModelLayout :errors="$attrs.errors" :model="banner">
      <BannerForm :banner="banner" model-route="banners.store" />
    </UpsertModelLayout>
  </AdminLayout>
</template>

<script setup lang="ts">
import route from "ziggy-js";

import AdminLayout from "@/components/Admin/Layouts/AdminLayout.vue";
import BannerForm from "@/components/Admin/Forms/BannerForm.vue";
import UpsertModelLayout from "@/components/Admin/Layouts/UpsertModelLayout.vue";

const banner = {
  title: "",
  link_url: "",
  is_active: null,
  image_url: "",
};
</script>
