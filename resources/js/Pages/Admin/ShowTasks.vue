<template>
  <AdminContentPage title="Užduotys">
    <div class="mb-4 flex gap-2">
      <NButton
        :type="showCompletedTasks === null ? 'primary' : 'default'"
        round
        size="small"
        @click="showCompletedTasks = null"
        >Visos</NButton
      >
      <NButton
        :type="showCompletedTasks === true ? 'primary' : 'default'"
        round
        size="small"
        @click="showCompletedTasks = true"
        >Atliktos</NButton
      ><NButton
        :type="showCompletedTasks === false ? 'primary' : 'default'"
        round
        size="small"
        @click="showCompletedTasks = false"
        >Neatliktos</NButton
      >
    </div>
    <NDataTable
      :data="shownTasks"
      :columns="columns"
      :row-class-name="rowClassName"
    ></NDataTable>
  </AdminContentPage>
</template>

<script setup lang="tsx">
import { Home24Filled, Sparkle24Filled } from "@vicons/fluent";
import { NButton, NCheckbox, NDataTable, NIcon, NTag } from "naive-ui";
import { computed, ref } from "vue";

import AdminContentPage from "@/Components/Layouts/AdminContentPage.vue";
import AdminLayout from "@/Components/Layouts/AdminLayout.vue";
import UsersAvatarGroup from "@/Components/Avatars/UsersAvatarGroup.vue";

const props = defineProps<{
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
    title: "Pavadinimas",
    key: "name",
  },
  {
    title: "Subjektas",
    key: "subject",
    render(row) {
      return (
        <NTag bordered={false} round size="small">
          {{
            default: () => [
              <span>{row.taskable.title}</span>,
              <span class="text-xs"> #{row.taskable.id}</span>,
            ],
            icon: () => <NIcon component={iconComponent(row)}></NIcon>,
          }}
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
    title: "Sukurta",
    key: "created_at",
  },
];

const rowClassName = (row) => {
  if (row.completed_at !== null) {
    return "bg-zinc-100/50 opacity-30";
  }
  return "";
};

const iconComponent = (row) => {
  switch (row.taskable_type) {
    case "App\\Models\\Doing":
      return Sparkle24Filled;
    default:
      return Home24Filled;
  }
};

const showCompletedTasks = ref<boolean | null>(null);

const shownTasks = computed(() => {
  if (showCompletedTasks.value === null) {
    return props.tasks;
  }

  return props.tasks.filter((task) => {
    if (showCompletedTasks.value) {
      return !!task.completed_at;
    }
    return !task.completed_at;
  });
});
</script>

<style scoped>
div.n-data-table {
  /* --n-merged-th-color: transparent; */
  /* --n-merged-td-color: transparent; */
  /* --n-merged-border-color: transparent; */
}
</style>
