<template>
  <NDataTable
    :data="tasks"
    :scroll-x="1000"
    :bordered="false"
    :columns="tasks.length > 0 ? columns() : []"
    :row-class-name="rowClassName"
    ><template #empty
      ><div
        class="flex flex-col items-center justify-center gap-2 text-zinc-400"
      >
        <NIcon :size="24" :component="IconsRegular.TASK"></NIcon>
        <span>Užduočių nėra.</span>
      </div></template
    ></NDataTable
  >
</template>

<script setup lang="tsx">
import {
  type DataTableColumns,
  NCheckbox,
  NDataTable,
  NIcon,
  NTag,
} from "naive-ui";
import { ref } from "vue";
import { router } from "@inertiajs/vue3";

import IconsFilled from "@/Types/Icons/filled";
import IconsRegular from "@/Types/Icons/regular";
import MoreOptionsButton from "@/Components/Buttons/MoreOptionsButton.vue";
import UsersAvatarGroup from "@/Components/Avatars/UsersAvatarGroup.vue";

defineProps<{
  tasks: App.Entities.Task[];
}>();

const loading = ref(false);

const rowClassName = (row: App.Entities.Task) => {
  if (row.completed_at !== null) {
    return "bg-zinc-100/50 opacity-30 dark:bg-zinc-900/50 dark:opacity-30";
  }
  return "";
};

const columns: () => DataTableColumns<App.Entities.Task> = () => [
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
    fixed: "left",
  },
  {
    title: "Pavadinimas",
    key: "name",
    fixed: "left",
    minWidth: 160,
  },
  {
    title: "Subjektas",
    key: "subject",
    render(row) {
      return (
        <NTag bordered={false} round size="small">
          {{
            default: () => [
              <span>
                {row.taskable.title ??
                  row.taskable.name ??
                  row.taskable.start_time}
              </span>,
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
    fixed: "right",
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

const iconComponent = (row: App.Entities.Task) => {
  switch (row.taskable_type) {
    case "App\\Models\\Meeting":
      return IconsFilled.MEETING;
    case "App\\Models\\User":
      return IconsFilled.USER;
    default:
      return IconsFilled.HOME;
  }
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

const handleDelete = async (task: App.Entities.Task) => {
  loading.value = true;

  router.delete(route("tasks.destroy", task.id), {
    onSuccess: () => {
      loading.value = false;
    },
    preserveScroll: true,
  });
};
</script>

<style scoped>
div.n-data-table {
  /* --n-merged-th-color: transparent; */
  /* --n-merged-td-color: transparent; */
  --n-merged-border-color: transparent;
}
</style>
