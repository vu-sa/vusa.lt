<template>
  <PageContent title="Nauja kategorija" :back-url="route('categories.index')">
    <UpsertModelLayout>
      <CategoryForm remember-key="CreateCategory" :category
        @submit:form="(form) => form.post(route('categories.store'))" />
    </UpsertModelLayout>
  </PageContent>
</template>

<script setup lang="ts">
import CategoryForm from "@/Components/AdminForms/CategoryForm.vue";
import PageContent from "@/Components/Layouts/AdminContentPage.vue";
import UpsertModelLayout from "@/Components/Layouts/FormUpsertLayout.vue";

const category = {
  name: "",
  alias: "",
  description: "",
};
</script>
