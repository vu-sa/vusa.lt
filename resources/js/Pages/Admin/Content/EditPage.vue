<template>
  <PageContent :title="page.title" :back-url="route('pages.index')">
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
        model-route="pages.update"
        delete-model-route="pages.destroy"
      />
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

import PageContent from "@/Components/Admin/Layouts/PageContent.vue";
import PageForm from "@/Components/Admin/Forms/PageForm.vue";
import PreviewModelButton from "@/Components/Admin/Buttons/PreviewModelButton.vue";
import UpsertModelLayout from "@/Components/Admin/Layouts/UpsertModelLayout.vue";

defineProps<{
  page: App.Models.Page;
}>();
</script>
