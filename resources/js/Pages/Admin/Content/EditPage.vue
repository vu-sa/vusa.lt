<template>
  <PageContent :title="page.title" :back-url="route('pages.index')" :heading-icon="Icons.PAGE">
    <template #header>
      {{ page.title }}
      <PreviewModelButton main-route="page" padalinys-route="page"
        :main-props="{ lang: page.lang, permalink: page.permalink }" :padalinys-props="{
          lang: page.lang,
          permalink: page.permalink,
          subdomain: page.padalinys?.alias,
        }" :padalinys-shortname="page.padalinys?.shortname" />
    </template>
    <UpsertModelLayout :errors="$page.props.errors" :model="page">
      <template #card-header>
        <span>Puslapio informacija</span>
      </template>
      <PageForm :categories :page :other-lang-pages model-route="pages.update" delete-model-route="pages.destroy" />
    </UpsertModelLayout>
  </PageContent>
</template>

<script setup lang="ts">
import Icons from "@/Types/Icons/regular";
import PageContent from "@/Components/Layouts/AdminContentPage.vue";
import PageForm from "@/Components/AdminForms/PageForm.vue";
import PreviewModelButton from "@/Components/Buttons/PreviewModelButton.vue";
import UpsertModelLayout from "@/Components/Layouts/FormUpsertLayout.vue";

defineProps<{
  categories: App.Entities.Category[];
  page: App.Entities.Page;
  otherLangPages: App.Entities.Page[];
}>();
</script>
