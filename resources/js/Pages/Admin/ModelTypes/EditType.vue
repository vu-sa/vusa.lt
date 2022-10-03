<template>
  <PageContent :title="contentType.title" :back-url="route('types.index')">
    <UpsertModelLayout :errors="$attrs.errors" :model="contentType">
      <TypeForm
        :content-types="contentTypes"
        :type="contentType"
        model-route="types.update"
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
  contentType: Record<string, any>;
  contentTypes: Record<string, any>[];
}>();
</script>
