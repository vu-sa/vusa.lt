<template>
  <PageContent title="Stud. atstovavimo statistika">
    <NDataTable :data="padaliniai" :columns="columns" />
  </PageContent>
</template>

<script setup lang="tsx">
import { NDataTable } from "naive-ui";
import PageContent from "@/Components/Layouts/AdminContentPage.vue";

const props = defineProps<{
  padaliniai: any;
}>();

const columns = [
  {
    key: "shortname",
    title: "Padalinys",
  },
  {
    key: "count_all_users",
    title: "Bent kartą prisijungę / visi studentų atstovai (%)",
    render(row: any) {
      let active = row.users.filter(
        (user: any) => user.last_action !== null
      ).length;
      let all = row.users.length;

      return `${active} / ${all} (${Math.round((active / all) * 100)}%)`;
    },
  },
  {
    key: "count_all_meetings",
    title: "Susitikimų skaičius",
    render(row: any) {
      return row.institutions.reduce((acc: number, institution: any) => {
        return acc + institution.meetings_count;
      }, 0);
    },
  },
];
</script>
