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
      ><TaskCreator />
    </div>
    <NCard class="subtle-gray-gradient">
      <NSpin :show="loading">
        <NDataTable
          size="small"
          :data="shownTasks"
          :columns="columns"
          :row-class-name="rowClassName"
        ></NDataTable>
        <template #description>Tuojaus... </template>
      </NSpin>
    </NCard>
  </AdminContentPage>
</template>

<script setup lang="tsx">
import { Home24Filled, Sparkle24Filled } from "@vicons/fluent";
import { Inertia } from "@inertiajs/inertia";
import {
  NButton,
  NCard,
  NCheckbox,
  NDataTable,
  NIcon,
  NSpin,
  NTag,
} from "naive-ui";
import { computed, ref } from "vue";


import AdminContentPage from "@/Components/Layouts/AdminContentPage.vue";
import AdminLayout from "@/Components/Layouts/AdminLayout.vue";
import MoreOptionsButton from "@/Components/Buttons/MoreOptionsButton.vue";
import TaskCreator from "@/Components/Tasks/TaskCreator.vue";
import UsersAvatarGroup from "@/Components/Avatars/UsersAvatarGroup.vue";

const props = defineProps<{
  tasks: App.Models.Task[];
}>();

const loading = ref(false);

defineOptions({
  layout: AdminLayout,
});

const columns = [
  {
    align: "center",
    key: "checkbox",
    title: "Done",
    render(row) {
      return (
        <NCheckbox
          onUpdate:checked={() => updateTaskCompletion(row)}
          checked={row.completed_at !== null}
        />
      );
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
      return <UsersAvatarGroup size={32} users={row.users}></UsersAvatarGroup>;
    },
  },
  {
    title: "Terminas",
    key: "due_date",
  },
  {
    title: "Sukurta",
    key: "created_at",
  },
  {
    key: "moreOptions",
    render(row) {
      return (
        <MoreOptionsButton
          delete
          small
          onDeleteClick={() => {
            handleDelete(row);
          }}
        ></MoreOptionsButton>
      );
    },
  },
];

const rowClassName = (row) => {
  if (row.completed_at !== null) {
    return "bg-zinc-100/50 opacity-30 dark:bg-zinc-900/50 dark:opacity-30";
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

const handleDelete = async (task: App.Models.Task) => {
  loading.value = true;

  Inertia.delete(route("tasks.destroy", task.id), {
    onSuccess: () => {
      loading.value = false;
    },
  });
};

const updateTaskCompletion = (task: App.Models.Task) => {
  loading.value = true;
  console.log("setTrue", task.completed_at === null);

  Inertia.post(
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
</script>

<style scoped>
div.n-data-table {
  --n-merged-th-color: transparent;
  --n-merged-td-color: transparent;
  --n-merged-border-color: transparent;
}
</style>
