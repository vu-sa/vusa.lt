<template>
  <PageContent
    title="Naujas pradinio puslapio mygtukas"
    :back-url="route('mainPage.index')"
  >
    <UpsertModelLayout :errors="$attrs.errors" :model="mainPage">
      <MainPageForm :main-page="mainPage" model-route="mainPage.store" />
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

import MainPageForm from "@/Components/Admin/Forms/MainPageForm.vue";
import PageContent from "@/Components/Admin/Layouts/PageContent.vue";
import UpsertModelLayout from "@/Components/Admin/Layouts/UpsertModelLayout.vue";

const mainPage = {
  text: "",
  link: "",
  lang: "lt",
};
</script>
