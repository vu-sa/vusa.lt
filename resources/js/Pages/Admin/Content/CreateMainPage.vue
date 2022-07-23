<template>
  <AdminLayout
    title="Naujas pradinio puslapio mygtukas"
    :back-url="route('mainPage.index')"
  >
    <UpsertModelLayout :errors="$attrs.errors" :model="mainPage">
      <MainPageForm :main-page="mainPage" model-route="mainPage.store" />
    </UpsertModelLayout>
  </AdminLayout>
</template>

<script setup lang="ts">
import route from "ziggy-js";

import AdminLayout from "@/Components/Admin/Layouts/AdminLayout.vue";
import MainPageForm from "@/Components/Admin/Forms/MainPageForm.vue";
import UpsertModelLayout from "@/Components/Admin/Layouts/UpsertModelLayout.vue";

const mainPage = {
  text: "",
  link: "",
  alias: "",
};
</script>
