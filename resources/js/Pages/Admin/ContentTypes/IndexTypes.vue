<template>
  <PageContent title="Turinio tipai">
    <div class="main-card">
      <NDataTable :data="contentTypes" :columns="columns"></NDataTable>
    </div>
  </PageContent>
</template>

<script lang="ts">
import AdminLayout from "@/Components/Admin/Layouts/AdminLayout.vue";

export default {
  layout: AdminLayout,
};
</script>

<script setup lang="ts">
import { NDataTable } from "naive-ui";
import { computed } from "vue";

import PageContent from "@/Components/Admin/Layouts/PageContent.vue";

const props = defineProps<{
  contentTypes: Record<string, any>[];
}>();

// move data with parent_id to children of item
const contentTypes = computed(() => {
  const contentTypes = props.contentTypes;
  const contentTypesWithChildren = contentTypes.map((contentType) => {
    contentType.children = contentTypes.filter(
      (child) => child.parent_id === contentType.id
    );
    return contentType;
  });
  return contentTypesWithChildren.filter(
    (contentType) => contentType.parent_id === null
  );
});

// add columns
const columns = [
  {
    title: "ID",
    key: "id",
  },
  {
    title: "Pavadinimas",
    key: "title",
  },
  {
    title: "Slug",
    key: "slug",
  },
  {
    title: "Modelis",
    key: "model_type",
  },
  {
    title: "Kurtas",
    key: "created_at",
    render(row) {
      return new Date(row.created_at).toLocaleString("lt-LT");
    },
  },
  {
    title: "Atnaujintas",
    key: "updated_at",
    render(row) {
      return new Date(row.updated_at).toLocaleString("lt-LT");
    },
  },
];
</script>
