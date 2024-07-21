<template>
  <PageContent :title="mainPage.text" :back-url="route('mainPage.index')" :heading-icon="Icons.MAIN_PAGE">
    <UpsertModelLayout :errors="$page.props.errors" :model="mainPage">
      <MainPageForm :main-page :tenant-options :type-options model-route="mainPage.update"
        delete-model-route="mainPage.destroy" />
    </UpsertModelLayout>
  </PageContent>
</template>

<script setup lang="ts">
import Icons from "@/Types/Icons/regular";
import MainPageForm from "@/Components/AdminForms/MainPageForm.vue";
import PageContent from "@/Components/Layouts/AdminContentPage.vue";
import UpsertModelLayout from "@/Components/Layouts/FormUpsertLayout.vue";

defineProps<{
  mainPage: App.Entities.MainPage;
  tenantOptions: Record<string, any>[];
  typeOptions: Record<string, any>[];
}>();
</script>
