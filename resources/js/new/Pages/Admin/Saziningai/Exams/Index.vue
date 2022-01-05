<template>
  <AdminLayout title="Sažiningai">
    <template #aside-header>
      <AsideHeader></AsideHeader>
    </template>
    <NDataTable
      :data="props.exams"
      :columns="columns"
      :row-props="rowProps"
      :scroll-x="1200"
      class="main-card"
    >
    </NDataTable>
  </AdminLayout>
</template>

<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import AsideHeader from "../AsideHeader.vue";
import { NDataTable, NEllipsis } from "naive-ui";
import { ref, h } from "vue";
import { Inertia } from "@inertiajs/inertia";
import { Link } from "@inertiajs/inertia-vue3";

const props = defineProps({
  exams: Object,
});

const createColumns = () => {
  return [
    {
      title: "Dalyko pavadinimas",
      key: "subject_name",
      className: "w-1/6 truncate max-w-[1em]",
    },
    {
      title: "Laikančiųjų padalinys",
      key: "padalinys",
      className: "break-normal",
    },
    {
      title: "Registruotojas",
      key: "name",
      className: "break-normal",
    },
    {
      title: "Laikančiųjų skaičius",
      key: "exam_holders",
      className: "w-20 break-normal",
    },
    {
      title: "Užregistravimo data",
      key: "created_at",
      className: "break-normal",
    },
    {
      title: "Egzamino pradžia",
      key: "flow_date",
      className: "break-normal",
    },
    {
      title: "Srautai",
      key: "flow_count",
    },
    {
      title: "Stebėtojai",
      key: "observer_count",
    },
  ];
};

const columns = ref(createColumns());

const rowProps = (row) => {
  return {
    style: "cursor: pointer;",
    onClick: () => {
      Inertia.visit(route("saziningaiExams.edit", row.id));
    },
  };
};

const sortRef = ref(false);

const inertiaPromise = () => {
  return new Promise((resolve, reject) => {
    Inertia.reload({
      only: ["exams", "message"],
      data: { sortValue: sortRef.value },
    });
    resolve(!sortRef.value);
  });
};

const sortAndGetData = async () => {
  sortRef.value = await inertiaPromise();
  console.log(sortRef.value);
};
</script>