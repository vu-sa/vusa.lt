<template>
  <AdminLayout title="Sažiningai" :create-url="create_url">
    <template #aside-header>
      <AsideHeader></AsideHeader>
    </template>
    <div class="main-card">
      <NDataTable
        :data="props.exams"
        :columns="columns"
        :row-props="rowProps"
        :scroll-x="1200"
      >
      </NDataTable>
    </div>
  </AdminLayout>
</template>

<script setup lang="ts">
import { Inertia } from "@inertiajs/inertia";
import { Link } from "@inertiajs/inertia-vue3";
import { NAlert, NDataTable, NEllipsis } from "naive-ui";
import { computed, h, ref } from "vue";
import AdminLayout from "@/Layouts/AdminLayout.vue";
import AsideHeader from "../AsideHeader.vue";

const props = defineProps({
  exams: Object,
  padaliniai: Object,
  create_url: String,
});

const createColumns = () => {
  return [
    {
      title: "Dalyko pavadinimas",
      key: "subject_name",
      className: "w-1/6 truncate max-w-[1em]",
      sorter: "default",
    },
    {
      title: "Laikančiųjų padalinys",
      key: "padalinys",
      // className: "break-normal",
      // defaultFilterOptionValues: ['London', 'New York'],
      // filterOptions: selectOptions,
      // filter(value, row) {
      // return ~row.padalinys.indexOf(value);
      // },
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
      sorter: "default",
    },
    {
      title: "Egzamino pradžia",
      key: "flow_date",
      className: "break-normal",
      sorter: "default",
    },
    {
      title: "Srautai",
      key: "flow_count",
    },
    {
      title: "Stebėtojai",
      key: "observer_count",
      sorter: "default",
    },
  ];
};

const columns = ref(createColumns());

const dataTableInstRef = ref(null);

const rowProps = (row) => {
  return {
    style: "cursor: pointer;",
    onClick: () => {
      Inertia.visit(route("saziningaiExams.edit", row.id));
    },
  };
};

const sortRef = ref(false);

const selectOptions = Object.keys(props.padaliniai).map((key) => {
  return {
    value: key,
    label: props.padaliniai[key],
  };
});

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
