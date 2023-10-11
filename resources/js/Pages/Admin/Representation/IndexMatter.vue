<template>
  <IndexPageLayout
    title="Svarstomi klausimai"
    model-name="matters"
    :can-use-routes="canUseRoutes"
    :columns="columns"
    :paginated-models="matters"
    :icon="Icons.MATTER"
  >
  </IndexPageLayout>
</template>

<script setup lang="tsx">
import { NButton, NEllipsis, NIcon } from "naive-ui";

import Icons from "@/Types/Icons/regular";
import IndexPageLayout from "@/Components/Layouts/IndexModel/IndexPageLayout.vue";

defineProps<{
  matters: PaginatedModels<App.Entities.Matter[]>;
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
    key: "title",
    minWidth: 200,
  },
  {
    title: "Institucijos",
    key: "institutions",
    minWidth: 100,
    render(row: App.Entities.Matter) {
      let institutions = row.institutions?.map((institution) => {
        return (
          <a
            href={route("institutions.edit", {
              id: institution.id,
            })}
            target="_blank"
            class="transition hover:text-vusa-red"
          >
            <NButton round size="tiny" tertiary>
              {{
                default: (
                  <NEllipsis style="max-width: 150px">
                    {institution.short_name ?? institution.name}
                  </NEllipsis>
                ),
                icon: <NIcon component={Icons.INSTITUTION}></NIcon>,
              }}
            </NButton>
          </a>
        );
      });

      return institutions;
    },
  },
];
</script>
