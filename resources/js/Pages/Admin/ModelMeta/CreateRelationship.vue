<template>
  <PageContent title="Naujas ryšys" :back-url="route('relationships.index')">
    <UpsertModelLayout :errors="$attrs.errors" :model="relationship">
      <RelationshipForm
        :relationship="relationship"
        :content-types="contentTypes"
        model-route="relationships.store"
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
import RelationshipForm from "@/Components/Admin/Forms/RelationshipForm.vue";
import UpsertModelLayout from "@/Components/Admin/Layouts/UpsertModelLayout.vue";

defineProps<{
  contentTypes: Record<string, any>[];
}>();

const relationship = {
  name: "",
  slug: "",
  description: "",
};
</script>
