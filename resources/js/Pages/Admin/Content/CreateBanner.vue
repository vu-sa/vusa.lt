<template>
  <PageContent title="Naujas baneris" :back-url="route('banners.index')">
    <UpsertModelLayout :errors="$attrs.errors" :model="banner">
      <BannerForm :banner="banner" model-route="banners.store" />
    </UpsertModelLayout>
  </PageContent>
</template>

<script lang="ts">
import AdminLayout from "@/Components/Admin/Layouts/AdminLayout.vue";

export default {
  layout: AdminLayout,
};
</script>

<script setup lang="ts">
import route from "ziggy-js";

import BannerForm from "@/Components/Admin/Forms/BannerForm.vue";
import PageContent from "@/Components/Admin/Layouts/PageContent.vue";
import UpsertModelLayout from "@/Components/Admin/Layouts/UpsertModelLayout.vue";

const banner = {
  title: "",
  link_url: "",
  is_active: null,
  image_url: "",
};
</script>
