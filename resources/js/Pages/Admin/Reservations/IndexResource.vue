<template>
  <IndexPageLayout
    title="Ištekliai"
    model-name="resources"
    :icon="Icons.RESOURCE"
    :can-use-routes="canUseRoutes"
    :columns="columns"
    :paginated-models="resources"
  >
  </IndexPageLayout>
</template>

<script setup lang="tsx">
import { type DataTableColumns, NImage, NImageGroup, NSpace } from "naive-ui";

import Icons from "@/Types/Icons/regular";
import IndexPageLayout from "@/Components/Layouts/IndexModel/IndexPageLayout.vue";

defineProps<{
  resources: PaginatedModels<App.Entities.Resource>;
}>();

const canUseRoutes = {
  create: true,
  show: false,
  edit: true,
  destroy: true,
};

// add columns
const columns: DataTableColumns<App.Entities.Resource> = [
  {
    type: "expand",
    renderExpand(row) {
      return (
        <section class="flex flex-col gap-2 p-2">
          <NImageGroup>
            <NSpace>
              {row.media?.map((image) => (
                <NImage width="150" src={image.original_url} alt={image.name} />
              ))}
            </NSpace>
          </NImageGroup>
          <div>
            <strong>Aprašymas</strong>
            <p>{row.description}</p>
          </div>
        </section>
      );
    },
  },
  {
    title: "Pavadinimas",
    key: "name",
  },
  {
    title: "Kiekis",
    key: "capacity",
  },
  {
    title: "Padalinys",
    key: "padalinys.shortname",
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
