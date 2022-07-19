<template>
  <AdminLayout title="Institucijos">
    <template #aside-header>
      <AsideHeader></AsideHeader>
    </template>
    <div class="main-card">
      <NInput
        class="md:col-span-4 mb-2"
        type="text"
        size="medium"
        round
        placeholder="Ieškoti pagal pavadinimą, trumpąjį pavadinimą arba trumpinį..."
        :loading="loading"
        @input="handleSearchInput"
      ></NInput>
      <NDataTable
        :data="props.dutyInstitutions"
        :columns="columns"
        :row-props="rowProps"
      >
      </NDataTable>
    </div>
  </AdminLayout>
</template>

<script setup lang="ts">
import { Inertia } from "@inertiajs/inertia";
import { Link } from "@inertiajs/inertia-vue3";
import { NDataTable, NInput } from "naive-ui";
import { ref } from "vue";
import AdminLayout from "@/Layouts/AdminLayout.vue";
import AsideHeader from "../AsideHeader.vue";

const props = defineProps({
  dutyInstitutions: Object,
});

const loading = ref(false);

const createColumns = () => {
  return [
    {
      title: "Pavadinimas",
      key: "name",
      ellipsis: true,
      width: 300,
    },
    {
      title: "Trumpas",
      key: "short_name",
    },
    {
      title: "Alias",
      key: "alias",
    },

    {
      title: "Padalinys",
      key: "padalinys.shortname",
    },
  ];
};

const columns = ref(createColumns());

const rowProps = (row) => {
  return {
    style: "cursor: pointer;",
    onClick: () => {
      Inertia.visit(route("dutyInstitutions.edit", { id: row.id }));
    },
  };
};

const handleSearchInput = _.debounce((input) => {
  const search = input;
  // if (name.length > 2) {
  loading.value = true;
  Inertia.reload({
    data: { search: search },
    onSuccess: () => {
      console.log(props.duties);
      loading.value = false;
      // },
    },
  });
}, 500);
</script>
