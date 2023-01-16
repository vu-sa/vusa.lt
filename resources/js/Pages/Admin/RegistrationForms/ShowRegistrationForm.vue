<template>
  <IndexPageLayout
    title="Registracija"
    model-name="registrations"
    :can-use-routes="canUseRoutes"
    :columns="columns"
    :paginated-models="registrationForm"
  >
  </IndexPageLayout>
</template>

<script setup lang="ts">
import { h } from "vue";
import type { DataTableColumns } from "naive-ui";

import IndexPageLayout from "@/Components/Layouts/IndexModel/IndexPageLayout.vue";

const props = defineProps<{
  registrationForm: PaginatedModels<any>;
}>();

const canUseRoutes = {
  create: false,
  show: false,
  edit: false,
  destroy: false,
};

const renderObjects = (object: Record<string, any>) => {
  return Object.entries(object).map(([key, value]) => {
    return h(
      "div",
      { class: "flex flex-row gap-2" },
      {
        default: () => [
          h("span", { class: "font-bold" }, key),
          h("span", value),
        ],
      }
    );
  });
};

const columns: DataTableColumns<any> = [
  // generate columns from row.data
  ...Object.keys(props.registrationForm.data[0].data).map((key) => ({
    title: key,
    key: `data.${key}`,
    minWidth: 150,
  })),
  {
    title: "Užregistravimo data",
    key: "created_at",
    width: 120,
  },
];
</script>
