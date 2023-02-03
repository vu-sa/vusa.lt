<template>
  <PageContent title="Naujas ryšys" :back-url="route('relationships.index')">
    <UpsertModelLayout :errors="$page.props.errors" :model="relationship">
      <RelationshipForm
        :relationship="relationship"
        :content-types="contentTypes"
        model-route="relationships.store"
      />
    </UpsertModelLayout>
  </PageContent>
</template>

<script setup lang="ts">
import PageContent from "@/Components/Layouts/AdminContentPage.vue";
import RelationshipForm from "@/Components/AdminForms/RelationshipForm.vue";
import UpsertModelLayout from "@/Components/Layouts/FormUpsertLayout.vue";

defineProps<{
  contentTypes: Record<string, any>[];
}>();

const relationship = {
  name: "",
  slug: "",
  description: "",
};
</script>
