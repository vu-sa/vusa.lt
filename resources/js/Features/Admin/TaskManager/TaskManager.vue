<template>
  <div class="mb-4 flex gap-2">
    <FilterPopselect
      :options="buttonNames"
      @click="handleClick"
    ></FilterPopselect
    ><TaskCreator :taskable="taskable" />
  </div>
  <NCard class="subtle-gray-gradient">
    <NSpin :show="false">
      <TaskTable :tasks="shownTasks" />
      <template #description>Tuojaus... </template>
    </NSpin>
  </NCard>
</template>

<script setup lang="tsx">
import { NCard, NSpin } from "naive-ui";
import { computed, ref } from "vue";

import FilterPopselect from "@/Components/Buttons/FilterPopselect.vue";
import TaskCreator from "./TaskCreator.vue";
import TaskTable from "./TaskTable.vue";

const props = defineProps<{
  tasks?: App.Entities.Task[];
  taskable?: {
    id: number;
    type: string;
  };
}>();

const buttonNames = ["Visos", "Atliktos", "Neatliktos"];

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
