<template>
  <PageContent
    title="Naujas pradinio puslapio mygtukas"
    :back-url="route('mainPage.index')"
  >
    <UpsertModelLayout :errors="$page.props.errors" :model="mainPage">
      <MainPageForm
        :padaliniai-options="padaliniaiOptions"
        :type-options="typeOptions"
        :main-page="mainPage"
        model-route="mainPage.store"
      />
    </UpsertModelLayout>
  </PageContent>
</template>

<script setup lang="ts">
import MainPageForm from "@/Components/AdminForms/MainPageForm.vue";
import PageContent from "@/Components/Layouts/AdminContentPage.vue";
import UpsertModelLayout from "@/Components/Layouts/FormUpsertLayout.vue";

defineProps<{
  padaliniaiOptions: Record<string, any>[];
  typeOptions: Record<string, any>[];
}>();

const mainPage = {
  text: "",
  link: "",
  lang: "lt",
  type: "url",
};
</script>
