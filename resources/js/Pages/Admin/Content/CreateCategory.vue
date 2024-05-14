<template>
  <PageContent
    title="Nauja kategorija"
    :back-url="route('categories.index')"
  >
    <UpsertModelLayout :errors="$page.props.errors" :model="category">
      <CategoryForm 
      :category
        model-route="categories.store"
      />
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
