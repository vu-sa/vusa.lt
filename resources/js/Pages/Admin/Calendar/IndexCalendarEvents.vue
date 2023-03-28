<template>
  <IndexPageLayout
    title="Renginiai"
    model-name="calendar"
    :can-use-routes="canUseRoutes"
    :columns="columns"
    :paginated-models="calendar"
    :icon="Icons.CALENDAR"
  >
  </IndexPageLayout>
</template>

<script setup lang="tsx">
import IndexPageLayout from "@/Components/Layouts/IndexModel/IndexPageLayout.vue";

import { formatStaticTime } from "@/Utils/IntlTime";
import Icons from "@/Types/Icons/regular";

defineProps<{
  calendar: PaginatedModels<App.Entities.Calendar>;
}>();

const canUseRoutes = {
  create: true,
  show: false,
  edit: true,
  destroy: true,
};

const columns = [
  {
    title: "Pavadinimas",
    key: "title",
    maxWidth: 200,
    ellipsis: {
      tooltip: true,
    },
  },
  {
    title: "Data",
    key: "date",
    render(row) {
      return formatStaticTime(row.date, {
        year: "numeric",
        month: "long",
        day: "numeric",
        hour: "numeric",
        minute: "numeric",
      });
    },
  },
  {
    title: "Padalinys",
    key: "padalinys.shortname",
  },
  {
    title: "Kategorija",
    key: "category.name",
  },
];
</script>
