<template>
  <IndexPageLayout
    title="Sažiningai"
    model-name="saziningaiExams"
    :can-use-routes="canUseRoutes"
    :columns="columns"
    :paginated-models="exams"
  >
  </IndexPageLayout>
</template>

<script setup lang="tsx">
import type { DataTableColumns } from "naive-ui";

import IndexPageLayout from "@/Components/Layouts/IndexModel/IndexPageLayout.vue";

defineProps<{
  exams: PaginatedModels<App.Entities.SaziningaiExam>;
}>();

const canUseRoutes = {
  create: true,
  show: false,
  edit: true,
  destroy: true,
};

const columns: DataTableColumns<App.Entities.SaziningaiExam> = [
  {
    title: "Dalyko pavadinimas",
    key: "subject_name",
    minWidth: 150,
  },
  {
    title: "Laikančiųjų padalinys",
    key: "padalinys.shortname_vu",
    width: 100,
    render(row) {
      return row.padalinys?.shortname_vu;
    },
  },
  // {
  //   title: "Registravo",
  //   key: "name",
  // },
  {
    title: "Laikančiųjų skaičius",
    key: "exam_holders",
    width: 100,
  },
  {
    title: "Užregistravimo data",
    key: "created_at",
    width: 120,
  },
  {
    title: "Egzamino pradžia",
    key: "flow_date",
    render(row) {
      return row.flows?.[0]?.start_time;
    },
    width: 100,
  },
  // {
  //   title: "Srautai",
  //   key: "flow_count",
  //   render(row) {
  //     return row.flows?.length;
  //   },
  // },
];
</script>
