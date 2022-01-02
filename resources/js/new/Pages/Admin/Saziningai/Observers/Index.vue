<template>
  <AdminLayout title="Sažiningai">
    <template #aside-header>
      <AsideHeader></AsideHeader>
    </template>
    <NDataTable
      
      :data="props.observers"
      :columns="columns"
      :row-props="rowProps"
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
  observers: Object,
});

const createColumns = () => {
  return [
    {
      title: "Vardas ir pavardė",
      key: "name",
    },
    {
      title: "Padalinys",
      key: "padalinys_p",
    },
    {
      title: "Telefonas",
      key: "phone",
    }
  ];
};

const columns = ref(createColumns());

const rowProps = (row) => {
  return {
    style: "cursor: pointer;",
    onClick: () => {
      Inertia.visit(route("saziningaiObservers.show", { id: row.id }));
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