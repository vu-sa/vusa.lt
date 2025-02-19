<template>
  <PageContent title="Naujas ryšys" :back-url="route('relationships.index')">
    <UpsertModelLayout>
      <RelationshipForm remember-key="CreateRelationship" :relationship :content-types @submit:form="(form) => form.post(route('relationships.store'))" />
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
