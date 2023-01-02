<template>
  <PageContent title="Turinio tipai" :create-url="route('types.create')">
    <div class="main-card">
      <IndexSearchInput payload-name="text" />
      <IndexDataTable
        :model="contentTypes"
        :columns="columns"
        edit-route="types.edit"
        destroy-route="types.destroy"
      />
    </div>
  </PageContent>
</template>

<script lang="ts">
import AdminLayout from "@/Components/Layouts/AdminLayout.vue";

export default {
  layout: AdminLayout,
};
</script>

<script setup lang="ts">
import { computed } from "vue";
import route from "ziggy-js";

import IndexDataTable from "@/Components/IndexDataTable.vue";
import IndexSearchInput from "@/Components/IndexSearchInput.vue";
import PageContent from "@/Components/Layouts/AdminContentPage.vue";

const props = defineProps<{
  contentTypes: PaginatedModels<any>;
}>();

// move data with parent_id to children of item

// this function crashes the page

// const computedContentTypes = computed(() => {
//   const contentTypes = props.contentTypes;
//   let contentTypesWithChildren = {
//     ...contentTypes,
//     data: contentTypes.data.map((contentType) => {
//       contentType.children = contentTypes.data.filter(
//         (child) => child.parent_id === contentType.id
//       );
//       return contentType;
//     }),
//   };
//   // filter content types without parent_id
//   contentTypesWithChildren.data = contentTypesWithChildren.data.filter(
//     (contentType) => contentType.parent_id === null
//   );
//   return contentTypesWithChildren;
// });

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
    title: "Sukurtas",
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
