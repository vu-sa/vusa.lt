<template>
  <PageContent :title="mainPage.text" :back-url="route('mainPage.index')">
    <UpsertModelLayout :errors="$page.props.errors" :model="mainPage">
      <MainPageForm
        :main-page="mainPage"
        model-route="mainPage.update"
        delete-model-route="mainPage.destroy"
      />
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


import MainPageForm from "@/Components/AdminForms/MainPageForm.vue";
import PageContent from "@/Components/Layouts/AdminContentPage.vue";
import UpsertModelLayout from "@/Components/Layouts/FormUpsertLayout.vue";

defineProps<{
  mainPage: App.Models.MainPage;
}>();
</script>
