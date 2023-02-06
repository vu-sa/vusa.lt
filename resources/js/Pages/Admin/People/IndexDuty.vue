<template>
  <IndexPageLayout
    title="Pareigos"
    model-name="duties"
    :can-use-routes="canUseRoutes"
    :columns="columns"
    :paginated-models="duties"
  >
  </IndexPageLayout>
</template>

<script setup lang="tsx">
import { NButton, NEllipsis, NIcon } from "naive-ui";
import Icons from "@/Types/Icons/filled";
import IndexPageLayout from "@/Components/Layouts/IndexModel/IndexPageLayout.vue";

defineProps<{
  duties: PaginatedModels<App.Entities.Duty>;
}>();

const canUseRoutes = {
  create: true,
  show: true,
  edit: true,
  destroy: false,
};

const columns = [
  {
    title: "Pavadinimas",
    key: "name",
    minWidth: 150,
  },
  {
    title: "Institucija",
    key: "institution.id",
    minWidth: 100,
    render(row: App.Entities.Duty) {
      return row.institution ? (
        <a
          href={route("institutions.edit", {
            id: row.institution.id,
          })}
          target="_blank"
          class="transition hover:text-vusa-red"
        >
          <NButton round size="tiny" tertiary>
            {{
              default: (
                <NEllipsis style="max-width: 150px">
                  {row.institution?.short_name ?? row.institution?.name}
                </NEllipsis>
              ),
              icon: <NIcon component={Icons.INSTITUTION}></NIcon>,
            }}
          </NButton>
        </a>
      ) : null;
    },
  },
  {
    title: "Tipas",
    key: "type.name",
    width: 150,
  },
  {
    title: "El. paštas",
    key: "email",
    minWidth: 150,
    render(row) {
      return (
        <a href={`mailto:${row.email}`} class="transition hover:text-vusa-red">
          {row.email}
        </a>
      );
    },
  },
];
</script>
