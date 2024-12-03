<template>
  <NDataTable :data="tasks" :scroll-x="800" :bordered="false" :columns :row-class-name="rowClassName">
    <template #empty>
      <div class="flex flex-col items-center justify-center gap-2 text-zinc-400">
        <NIcon :size="24" :component="IconsRegular.TASK" />
        <span>Užduočių nėra.</span>
      </div>
    </template>
  </NDataTable>
</template>

<script setup lang="tsx">
import {
  type DataTableColumns,
  NButton,
  NCheckbox,
  NDataTable,
  NEllipsis,
  NIcon,
} from "naive-ui";
import { Link, router, usePage } from "@inertiajs/vue3";
import { computed, ref } from "vue";

import { trans as $t } from "laravel-vue-i18n";
import IconsFilled from "@/Types/Icons/filled";
import IconsRegular from "@/Types/Icons/regular";
import MoreOptionsButton from "@/Components/Buttons/MoreOptionsButton.vue";
import UsersAvatarGroup from "@/Components/Avatars/UsersAvatarGroup.vue";

const props = defineProps<{
  tasks: App.Entities.Task[];
}>();

const tasks = ref(props.tasks);

const loading = ref(false);

const rowClassName = (row: App.Entities.Task) => {
  if (row.completed_at !== null) {
    return "bg-zinc-100/50 opacity-30 dark:bg-zinc-900/50 dark:opacity-30";
  }
  return "";
};

const columns = computed<DataTableColumns<App.Entities.Task>>(() => [
  {
    align: "center",
    key: "checkbox",
    render(row) {
      return (
        <NCheckbox
          themeOverrides={{ border: "1px solid" }}
          size="large"
          onUpdate:checked={() => updateTaskCompletion(row)}
          disabled={
            !row.users?.find(
              (user) => user.id === usePage().props.auth?.user?.id,
            )
          }
          checked={row.completed_at !== null}
        />
      );
    },
    width: 40,
    fixed: "left",
  },
  {
    title() {
      return $t("forms.fields.title");
    },
    key: "name",
    width: 180,
    ellipsis: {
      tooltip: true,
    },
    resizable: true,
  },
  {
    title() {
      return $t("forms.fields.subject");
    },
    key: "subject",
    render(row) {
      let modelType = row.taskable_type.split("\\").pop() + "s";

      return (
        <Link href={route(`${modelType.toLowerCase()}.show`, row.taskable_id)}>
          <NButton secondary round size="tiny">
            {{
              default: () => [
                <NEllipsis class="w-28">
                  {row.taskable?.title ??
                    row.taskable?.name ??
                    row.taskable?.start_time}
                </NEllipsis>,
              ],
              icon: () => <NIcon component={iconComponent(row)}></NIcon>,
            }}
          </NButton>
        </Link>
      );
    },
  },
  {
    title() {
      return $t("forms.fields.responsible_people");
    },
    key: "users",
    minWidth: 150,
    render(row) {
      return <UsersAvatarGroup size={32} users={row.users}></UsersAvatarGroup>;
    },
  },
  {
    title() {
      return $t("forms.fields.due_date");
    },
    key: "due_date",
    minWidth: 150,
    sorter: "default",
  },
  {
    key: "moreOptions",
    fixed: "right",
    width: 60,
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
]);

const iconComponent = (row: App.Entities.Task) => {
  switch (row.taskable_type) {
    case "App\\Models\\Meeting":
      return IconsFilled.MEETING;
    case "App\\Models\\User":
      return IconsFilled.USER;
    case "App\\Models\\Reservation":
      return IconsFilled.RESERVATION;
    default:
      return IconsFilled.HOME;
  }
};

const updateTaskCompletion = (task: App.Entities.Task) => {
  loading.value = true;

  const updateValue = task.completed_at === null 

  // find task from taskRef
  const taskRef = tasks.value.find((t) => t.id === task.id);

  // update task
  taskRef.completed_at = task.completed_at === null ? new Date() : null;

  router.post(
    route("tasks.updateCompletionStatus", task.id),
    {
      completed: updateValue,
    },
    {
      onSuccess: () => {
        loading.value = false;
      },
      preserveScroll: true,
    },
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
