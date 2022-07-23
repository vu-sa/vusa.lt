<template>
  <AdminLayout :title="page.title" :back-url="route('pages.index')">
    <template #header>
      {{ page.title }}
      <PreviewModelButton
        main-route="main.page"
        padalinys-route="padalinys.page"
        :main-props="{ lang: page.lang, permalink: page.permalink }"
        :padalinys-props="{
          lang: page.lang,
          permalink: page.permalink,
          padalinys: page.padalinys?.alias,
        }"
        :padalinys-shortname="page.padalinys?.shortname"
      ></PreviewModelButton>
    </template>
    <UpsertModelLayout :errors="$attrs.errors" :model="page">
      <template #card-header>
        <span>Puslapio informacija</span>
      </template>
      <PageForm
        :page="page"
        model-route="duties.update"
        delete-model-route="duties.destroy"
      />
    </UpsertModelLayout>
  </AdminLayout>
</template>

<script setup lang="ts">
import route from "ziggy-js";

import AdminLayout from "@/Layouts/AdminLayout.vue";
import PageForm from "@/components/Admin/Forms/PageForm.vue";
import PreviewModelButton from "@/components/Admin/Buttons/PreviewModelButton.vue";
import UpsertModelLayout from "@/components/Admin/Layouts/UpsertModelLayout.vue";

defineProps<{
  page: App.Models.Page;
}>();
</script>
