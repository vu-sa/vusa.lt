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

<script setup lang="ts">
import MainPageForm from "@/Components/AdminForms/MainPageForm.vue";
import PageContent from "@/Components/Layouts/AdminContentPage.vue";
import UpsertModelLayout from "@/Components/Layouts/FormUpsertLayout.vue";

defineProps<{
  mainPage: App.Entities.MainPage;
}>();
</script>
