<template>
  <div>
    <div class="mb-4 flex gap-2">
      <FilterPopselect
        :disabled="disabled"
        :options="buttonNames"
        @select:value="handleClick"
      ></FilterPopselect
      ><TaskCreator :taskable="taskable" />
    </div>
    <NCard>
      <NSpin :show="false">
        <TaskTable :tasks="shownTasks" />
        <template #description>Tuojaus... </template>
      </NSpin>
    </NCard>
  </div>
</template>

<script setup lang="tsx">
import { trans as $t } from "laravel-vue-i18n";
import { NCard, NSpin } from "naive-ui";
import { computed, ref } from "vue";

import FilterPopselect from "@/Components/Buttons/FilterPopselect.vue";
import TaskCreator from "./TaskCreator.vue";
import TaskTable from "./TaskTable.vue";

const props = defineProps<{
  disabled?: boolean;
  tasks?: App.Entities.Task[];
  taskable?: {
    id: number;
    type: string;
  };
}>();

const buttonNames = computed(() => {
  return [$t("Visos"), $t("Atliktos"), $t("Neatliktos")];
});

const showCompletedTasks = ref<boolean | null>(null);

const shownTasks = computed(() => {
  if (props.tasks === undefined) {
    return [];
  }

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

const handleClick = (name: string | null) => {
  switch (name) {
    case $t("Visos"):
      showCompletedTasks.value = null;
      break;
    case $t("Atliktos"):
      showCompletedTasks.value = true;
      break;
    case $t("Neatliktos"):
      showCompletedTasks.value = false;
      break;
  }
};
</script>
