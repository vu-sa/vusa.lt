<template>
  <AdminContentPage title="Užduotys">
    <NDataTable
      :data="tasks"
      :columns="columns"
      :row-class-name="rowClassName"
    ></NDataTable>
  </AdminContentPage>
</template>

<script setup lang="tsx">
import { NCheckbox, NDataTable, NIcon, NTag } from "naive-ui";

import { Home24Filled, Sparkle24Filled } from "@vicons/fluent";
import AdminContentPage from "@/Components/Layouts/AdminContentPage.vue";
import AdminLayout from "@/Components/Layouts/AdminLayout.vue";
import UsersAvatarGroup from "@/Components/Avatars/UsersAvatarGroup.vue";

defineProps<{
  tasks: App.Models.Task[];
}>();

defineOptions({
  layout: AdminLayout,
});

const columns = [
  {
    align: "center",
    key: "checkbox",
    title: "Done",
    render(row) {
      return <NCheckbox checked={row.completed_at !== null} />;
    },
    width: 75,
  },
  {
    key: "icon",
    render(row) {
      switch (row.taskable_type) {
        case "App\\Models\\Doing":
          return <NIcon component={Sparkle24Filled} />;
        default:
          return <NIcon component={Home24Filled} />;
      }
    },
    width: 30,
  },
  {
    title: "Pavadinimas",
    key: "name",
    render(row) {
      return (
        <div>
          <div>{row.name}</div>
          <div class="text-xs text-zinc-400">{row.created_at}</div>
        </div>
      );
    },
  },
  {
    title: "Subjektas",
    key: "subject",
    render(row) {
      return (
        <NTag bordered={false} round size="small">
          {row.taskable.title} <span class="text-xs">#{row.taskable.id}</span>
        </NTag>
      );
    },
  },
  {
    title: "Atsakingi žmonės",
    key: "users",
    render(row) {
      return (
        <UsersAvatarGroup
          size={32}
          users={row.taskable.users}
        ></UsersAvatarGroup>
      );
    },
  },
  {
    title: "Pabaigta",
    key: "completed_at",
  },
];

const rowClassName = (row) => {
  if (row.completed_at !== null) {
    return "bg-zinc-100/50 opacity-30";
  }
  return "";
};
</script>

<style scoped>
div.n-data-table {
  /* --n-merged-th-color: transparent; */
  /* --n-merged-td-color: transparent; */
  /* --n-merged-border-color: transparent; */
}
</style>
