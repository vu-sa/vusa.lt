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
    <UpsertModelLayout :errors="$page.props.errors" :model="page">
      <template #card-header>
        <span>Puslapio informacija</span>
      </template>
      <PageForm
        :page="page"
        :other-lang-pages="otherLangPages"
        model-route="pages.update"
        delete-model-route="pages.destroy"
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


import PageContent from "@/Components/Layouts/AdminContentPage.vue";
import PageForm from "@/Components/AdminForms/PageForm.vue";
import PreviewModelButton from "@/Components/Buttons/PreviewModelButton.vue";
import UpsertModelLayout from "@/Components/Layouts/FormUpsertLayout.vue";

defineProps<{
  page: App.Models.Page;
  otherLangPages: App.Models.Page[];
}>();
</script>
