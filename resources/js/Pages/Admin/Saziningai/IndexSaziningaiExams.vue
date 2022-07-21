<template>
  <AdminLayout
    title="Sažiningai"
    :create-url="route('saziningaiExamRegistration')"
  >
    <div class="main-card">
      <!-- <IndexSearchInput payload-name=""></IndexSearchInput> -->
      <IndexDataTable :model="exams" :columns="columns"></IndexDataTable>
    </div>
  </AdminLayout>
</template>

<script setup lang="ts">
import { DataTableColumns } from "naive-ui";
import { Link } from "@inertiajs/inertia-vue3";
import { h } from "vue";
import route from "ziggy-js";

import AdminLayout from "@/Layouts/AdminLayout.vue";
import IndexDataTable from "@/components/Admin/IndexDataTable.vue";
// import IndexSearchInput from "@/components/Admin/IndexSearchInput.vue";

defineProps<{
  exams: PaginatedModels<App.Models.SaziningaiExam[]>;
}>();

const columns: DataTableColumns<App.Models.SaziningaiExam> = [
  {
    title: "Dalyko pavadinimas",
    key: "subject_name",
    ellipsis: {
      tooltip: true,
    },
    render(row: App.Models.SaziningaiExam) {
      return h(
        Link,
        {
          href: route("saziningaiExams.edit", { id: row.id }),
          class: "hover:text-vusa-red transition",
        },
        { default: () => row.subject_name }
      );
    },
  },
  {
    title: "Laikančiųjų padalinys",
    key: "padalinys.shortname_vu",
    render(row) {
      return row.padalinys?.shortname_vu;
    },
  },
  {
    title: "Registravo",
    key: "name",
  },
  {
    title: "Laikančiųjų skaičius",
    key: "exam_holders",
  },
  {
    title: "Užregistravimo data",
    key: "created_at",
  },
  {
    title: "Egzamino pradžia",
    key: "flow_date",
    render(row) {
      return row.flows?.[0].start_time;
    },
  },
  {
    title: "Srautai",
    key: "flow_count",
    render(row) {
      return row.flows?.length;
    },
  },
];
</script>
