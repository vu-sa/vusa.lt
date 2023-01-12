<template>
  <PageContent title="Naujas turinio tipas" :back-url="route('types.index')">
    <UpsertModelLayout :errors="$page.props.errors" :model="type">
      <TypeForm
        :content-types="contentTypes"
        :type="type"
        model-route="types.store"
      />
    </UpsertModelLayout>
  </PageContent>
</template>

<script setup lang="tsx">

import PageContent from "@/Components/Layouts/AdminContentPage.vue";
import TypeForm from "@/Components/AdminForms/TypeForm.vue";
import UpsertModelLayout from "@/Components/Layouts/FormUpsertLayout.vue";



defineProps<{
  contentTypes: Record<string, any>[];
}>();

const type = {
  title: "",
  slug: "",
  description: "",
  model_type: "",
};
</script>
