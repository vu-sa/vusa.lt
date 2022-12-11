<template>
  <PageContent title="Naujas turinio tipas" :back-url="route('types.index')">
    <UpsertModelLayout :errors="$attrs.errors" :model="type">
      <TypeForm
        :content-types="contentTypes"
        :type="type"
        model-route="types.store"
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
import TypeForm from "@/Components/Admin/Forms/TypeForm.vue";
import UpsertModelLayout from "@/Components/Admin/Layouts/UpsertModelLayout.vue";

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
