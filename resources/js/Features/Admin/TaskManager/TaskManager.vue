<template>
  <div>
    <div class="mb-4 flex items-center justify-between">
      <div class="flex items-center gap-2">
        <TaskFilter
          v-model="currentFilter"
          :disabled="disabled"
          :options="filterOptions" 
        />
        <Badge v-if="filteredTasks.length > 0" variant="secondary" class="ml-2">
          {{ filteredTasks.length }}
        </Badge>
      </div>
      <div v-if="taskable">
        <Button 
          variant="outline" 
          size="sm"
          @click="showCreateTaskDialog = true"
        >
          <PlusIcon class="mr-1 h-4 w-4" />
          {{ $t("tasks.create_new") }}
        </Button>
      </div>
    </div>
    
    <TaskTable :tasks="filteredTasks" :key="taskFilterKey" />

    <CreateTaskDialog 
      :open="showCreateTaskDialog" 
      :taskable="taskable" 
      @close="showCreateTaskDialog = false"
      @task-created="handleTaskCreated"
    />
  </div>
</template>

<script setup lang="ts">
import { trans as $t } from "laravel-vue-i18n";
import { computed, ref, watch, defineAsyncComponent, onMounted } from "vue";
import TaskFilter from "@/Components/Tasks/TaskFilter.vue";
import TaskTable from "./TaskTable.vue";
import { Spinner } from "@/Components/ui/spinner";
import { Button } from "@/Components/ui/button";
import { Badge } from "@/Components/ui/badge";
import { PlusIcon } from "lucide-vue-next";

// Use async component for the dialog to improve initial load performance
const CreateTaskDialog = defineAsyncComponent(() => 
  import('./CreateTaskDialog.vue')
);

// Task filtering states
enum FilterType {
  ALL = "all",
  COMPLETED = "completed",
  INCOMPLETE = "incomplete"
}

const props = defineProps<{
  disabled?: boolean;
  tasks?: App.Entities.Task[];
  taskable?: {
    id: string | number;
    type: string;
  };
}>();

// Component state
const showCreateTaskDialog = ref(false);
const taskFilterKey = ref(0);

// Make sure i18n is initialized before using filter options
const filterOptions = [
  { label: $t('tasks.filters.all'), value: FilterType.ALL },
  { label: $t('tasks.filters.completed'), value: FilterType.COMPLETED },
  { label: $t('tasks.filters.incomplete'), value: FilterType.INCOMPLETE }
];

// Task filtering setup with custom handling for initialization
const currentFilter = ref(FilterType.ALL);
const filteredTasks = computed(() => {
  if (!props.tasks?.length) {
    return [];
  }

  switch (currentFilter.value) {
    case FilterType.COMPLETED:
      return props.tasks.filter(task => task.completed_at !== null);
    case FilterType.INCOMPLETE:
      return props.tasks.filter(task => task.completed_at === null);
    default:
      return props.tasks;
  }
});

// Force re-render of TaskTable when filter changes to ensure checkboxes align properly
watch(currentFilter, () => {
  taskFilterKey.value++;
});

/**
 * Handle successful task creation
 */
const handleTaskCreated = () => {
  // Any additional logic after task creation can be added here
};
</script>
