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
    >
    </NDataTable>
  </AdminLayout>
</template>

<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import AsideHeader from "../AsideHeader.vue";
import { NDataTable } from "naive-ui";
import { ref } from "vue";
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
    },
    {
      title: "Laikančiųjų padalinys",
      key: "padalinys",
    },
    {
      title: "Registruotojas",
      key: "name",
    },
    {
      title: "Laikančiųjų skaičius",
      key: "exam_holders",
    },
    {
      title: "Trukmė (min.)",
      key: "duration",
    },
    {
      title: "Vieta",
      key: "place",
    },
    {
      title: "Užregistravimo data",
      key: "created_at",
    },
  ];
};

const columns = ref(createColumns());

const rowProps = (row) => {
  return {
    style: "cursor: pointer;",
    onClick: () => {
      Inertia.visit(route("saziningaiExams.show", { id: row.id }));
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