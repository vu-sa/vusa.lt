<template>
  <PageContent title="Naujas pradinio puslapio mygtukas" :back-url="route('mainPage.index')">
    <UpsertModelLayout>
      <MainPageForm :tenant-options :type-options :main-page
        @submit:form="(form) => form.post(route('mainPage.store'))" />
    </UpsertModelLayout>
  </PageContent>
</template>

<script setup lang="ts">
import MainPageForm from "@/Components/AdminForms/MainPageForm.vue";
import PageContent from "@/Components/Layouts/AdminContentPage.vue";
import UpsertModelLayout from "@/Components/Layouts/FormUpsertLayout.vue";

defineProps<{
  tenantOptions: Record<string, any>[];
  typeOptions: Record<string, any>[];
}>();

const mainPage = {
  text: "",
  link: "",
  lang: "lt",
  icon: "",
};
</script>
