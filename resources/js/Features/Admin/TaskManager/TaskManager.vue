<template>
  <div class="mb-4 flex gap-2">
    <FilterPopselect
      :options="buttonNames"
      @click="handleClick"
    ></FilterPopselect
    ><TaskCreator />
  </div>
  <NCard class="subtle-gray-gradient">
    <NSpin :show="loading">
      <NDataTable
        :data="shownTasks"
        size="large"
        :columns="columns"
        :row-class-name="rowClassName"
      ></NDataTable>
      <template #description>Tuojaus... </template>
    </NSpin>
  </NCard>
</template>

<script setup lang="tsx">
import { Home24Filled } from "@vicons/fluent";
import { NCard, NCheckbox, NDataTable, NIcon, NSpin, NTag } from "naive-ui";
import { computed, ref } from "vue";
import { router } from "@inertiajs/vue3";
import type { DataTableColumns } from "naive-ui";

import FilterPopselect from "@/Components/Buttons/FilterPopselect.vue";
import Icons from "@/Types/Icons/filled";
import MoreOptionsButton from "@/Components/Buttons/MoreOptionsButton.vue";
import TaskCreator from "./TaskCreator.vue";
import UsersAvatarGroup from "@/Components/Avatars/UsersAvatarGroup.vue";

const props = defineProps<{
  tasks: App.Entities.Task[];
}>();

const loading = ref(false);
const buttonNames = ["Visos", "Atliktos", "Neatliktos"];

const iconComponent = (row: App.Entities.Task) => {
  switch (row.taskable_type) {
    case "App\\Models\\Doing":
      return Icons.DOING;
    case "App\\Models\\User":
      return Icons.USER;
    default:
      return Home24Filled;
  }
};

const columns: DataTableColumns<App.Entities.Task> = [
  {
    align: "center",
    key: "checkbox",
    render(row) {
      return (
        <NCheckbox
          themeOverrides={{ borderRadius: "50%", border: "1px solid" }}
          size="large"
          onUpdate:checked={() => updateTaskCompletion(row)}
          checked={row.completed_at !== null}
        />
      );
    },
    width: 60,
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
              <span>{row.taskable.title ?? row.taskable.name}</span>,
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
      return <UsersAvatarGroup size={32} users={row.users}></UsersAvatarGroup>;
    },
  },
  {
    title: "Terminas",
    key: "due_date",
    sorter: "default",
  },
  {
    key: "moreOptions",
    render(row) {
      return row.completed_at === null ? (
        <MoreOptionsButton
          delete
          small
          onDeleteClick={() => {
            handleDelete(row);
          }}
        ></MoreOptionsButton>
      ) : null;
    },
  },
];

const rowClassName = (row: App.Entities.Task) => {
  if (row.completed_at !== null) {
    return "bg-zinc-100/50 opacity-30 dark:bg-zinc-900/50 dark:opacity-30";
  }
  return "";
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

const handleDelete = async (task: App.Entities.Task) => {
  loading.value = true;

  router.delete(route("tasks.destroy", task.id), {
    onSuccess: () => {
      loading.value = false;
    },
  });
};

const updateTaskCompletion = (task: App.Entities.Task) => {
  loading.value = true;
  console.log("setTrue", task.completed_at === null);

  router.post(
    route("tasks.updateCompletionStatus", task.id),
    {
      completed: task.completed_at === null,
    },
    {
      onSuccess: () => {
        loading.value = false;
      },
    }
  );
};

const handleClick = (name: string | null) => {
  switch (name) {
    case "Visos":
      showCompletedTasks.value = null;
      break;
    case "Atliktos":
      showCompletedTasks.value = true;
      break;
    case "Neatliktos":
      showCompletedTasks.value = false;
      break;
  }
};
</script>

<style scoped>
div.n-data-table {
  --n-merged-th-color: transparent;
  --n-merged-td-color: transparent;
  --n-merged-border-color: transparent;
}
</style>
