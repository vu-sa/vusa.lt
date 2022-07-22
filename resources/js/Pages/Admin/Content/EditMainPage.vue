<template>
  <AdminLayout :title="mainPage.text" :back-url="route('mainPage.index')">
    <UpsertModelLayout :errors="$attrs.errors" :model="mainPage">
      <MainPageForm
        :main-page="mainPage"
        model-route="mainPage.update"
        delete-model-route="mainPage.destroy"
      />
    </UpsertModelLayout>
  </AdminLayout>
</template>

<script setup lang="ts">
import route from "ziggy-js";

import AdminLayout from "@/Layouts/AdminLayout.vue";
import MainPageForm from "@/components/Admin/Forms/MainPageForm.vue";
import UpsertModelLayout from "@/components/Admin/Layouts/UpsertModelLayout.vue";

defineProps<{
  mainPage: App.Models.MainPage;
}>();
</script>
