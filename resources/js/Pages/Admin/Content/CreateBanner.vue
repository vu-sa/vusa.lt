<template>
  <PageContent title="Naujas baneris" :back-url="route('banners.index')">
    <UpsertModelLayout :errors="$page.props.errors" :model="banner">
      <BannerForm :banner="banner" model-route="banners.store" />
    </UpsertModelLayout>
  </PageContent>
</template>

<script lang="ts">
import AdminLayout from "@/Components/Layouts/AdminLayout.vue";

export default {
  layout: AdminLayout,
};
</script>

<script setup lang="ts">


import BannerForm from "@/Components/AdminForms/BannerForm.vue";
import PageContent from "@/Components/Layouts/AdminContentPage.vue";
import UpsertModelLayout from "@/Components/Layouts/FormUpsertLayout.vue";

const banner = {
  title: "",
  link_url: "",
  is_active: null,
  image_url: "",
};
</script>
