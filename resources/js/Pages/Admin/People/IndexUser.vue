<template>
  <IndexPageLayout
    :title="$t('Nariai')"
    model-name="users"
    :can-use-routes="canUseRoutes"
    :columns="columns"
    :paginated-models="users"
    :icon="Icons.USER"
  >
  </IndexPageLayout>
</template>

<script setup lang="tsx">
import { computed, provide, ref } from "vue";
import type { DataTableSortState } from "naive-ui";

import { formatRelativeTime } from "@/Utils/IntlTime";
import { updateSorters } from "@/Utils/DataTable";
import Icons from "@/Types/Icons/regular";
import IndexPageLayout from "@/Components/Layouts/IndexModel/IndexPageLayout.vue";

defineProps<{
  users: PaginatedModels<App.Entities.User>;
}>();

const sorters = ref<Record<string, DataTableSortState["order"]>>({
  name: false,
});

provide("sorters", { sorters, updateSorters });

const canUseRoutes = {
  create: true,
  show: false,
  edit: true,
  destroy: true,
};

const columns = computed(() => {
  return [
    {
      title: "Vardas",
      key: "name",
      sorter: true,
      sortOrder: sorters.value.name,
    },
    {
      title: "El. paštas",
      key: "email",
      maxWidth: 200,
      ellipsis: {
        tooltip: true,
      },
      render(row: App.Entities.User) {
        return (
          <a
            href={`mailto:${row.email}`}
            class="transition hover:text-vusa-red"
          >
            {row.email}
          </a>
        );
      },
    },
    {
      title: "Telefonas",
      key: "phone",
      maxWidth: 200,
      ellipsis: {
        tooltip: true,
      },
      render(row: App.Entities.User) {
        return (
          <a href={`tel:${row.phone}`} class="transition hover:text-vusa-red">
            {row.phone}
          </a>
        );
      },
    },
    {
      title: "Paskutinis prisijungimas",
      key: "last_action",
      maxWidth: 200,
      ellipsis: {
        tooltip: true,
      },
      render(row: App.Entities.User) {
        return (
          <span class={row.last_action ? "" : "text-vusa-red"}>
            {row.last_action
              ? formatRelativeTime(new Date(row.last_action))
              : "Niekada"}
          </span>
        );
      },
    },
    {
      title: "Pareigų skaičius",
      key: "duties_count",
    },
  ];
});
</script>
