<template>
  <PageContent
    :title="banner.title"
    :back-url="route('banners.index')"
    :heading-icon="Icons.BANNER"
  >
    <UpsertModelLayout :errors="$page.props.errors" :model="banner">
      <BannerForm
        :banner="banner"
        model-route="banners.update"
        delete-model-route="banners.destroy"
      />
    </UpsertModelLayout>
  </PageContent>
</template>

<script setup lang="ts">
import BannerForm from "@/Components/AdminForms/BannerForm.vue";
import Icons from "@/Types/Icons/regular";
import PageContent from "@/Components/Layouts/AdminContentPage.vue";
import UpsertModelLayout from "@/Components/Layouts/FormUpsertLayout.vue";

defineProps<{
  banner: App.Entities.Banner;
}>();
</script>
