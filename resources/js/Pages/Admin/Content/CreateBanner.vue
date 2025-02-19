<template>
  <PageContent title="Naujas baneris" :back-url="route('banners.index')">
    <UpsertModelLayout>
      <BannerForm remember-key="CreateBanner" :banner @submit:form="(form) => form.post(route('banners.store'))" />
    </UpsertModelLayout>
  </PageContent>
</template>

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
