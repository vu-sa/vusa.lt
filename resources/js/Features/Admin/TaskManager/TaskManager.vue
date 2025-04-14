<template>
  <div>
    <div class="mb-4 flex items-center justify-between">
      <div class="flex items-center gap-2">
        <TaskFilter
          v-model="currentFilter"
          :disabled="isLoading || disabled"
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
          :disabled="isLoading"
          @click="showCreateTaskDialog = true"
        >
          <PlusIcon class="mr-1 h-4 w-4" />
          {{ $t("tasks.create_new") }}
        </Button>
      </div>
    </div>
    
    <Spinner :show="isLoading">
      <TaskTable :tasks="filteredTasks" :key="taskFilterKey" />
      <template #description>
        {{ $t("loading_tasks") }}
      </template>
    </Spinner>

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
import { computed, ref, watch, defineAsyncComponent } from "vue";
import TaskFilter from "@/Components/Tasks/TaskFilter.vue";
import TaskTable from "./TaskTable.vue";
import { Spinner } from "@/Components/ui/spinner";
import { Button } from "@/Components/ui/button";
import { Badge } from "@/Components/ui/badge";
import { PlusIcon } from "lucide-vue-next";
import { useTaskFilter } from "./composables/useTaskFilter";

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
const isLoading = ref(false);
const showCreateTaskDialog = ref(false);
const taskFilterKey = ref(0);

// Task filtering setup using composable
const { currentFilter, filteredTasks, filterOptions } = useTaskFilter(props.tasks || []);

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
