import { ref, computed, watch } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';

/**
 * Task filtering states enum
 */
export enum FilterType {
  ALL = 'all',
  COMPLETED = 'completed',
  INCOMPLETE = 'incomplete'
}

/**
 * Composable for handling task filtering
 * @param tasks The tasks array to filter
 * @returns Task filtering state and computed filtered tasks
 */
export function useTaskFilter(tasks: App.Entities.Task[]) {
  // Current filter state
  const currentFilter = ref<string>(FilterType.ALL);

  // Filter options
  const filterOptions = [
    { label: $t('tasks.filters.all'), value: FilterType.ALL },
    { label: $t('tasks.filters.completed'), value: FilterType.COMPLETED },
    { label: $t('tasks.filters.incomplete'), value: FilterType.INCOMPLETE }
  ];

  /**
   * Computed property that filters tasks based on the current filter
   */
  const filteredTasks = computed(() => {
    if (!tasks?.length) {
      return [];
    }

    switch (currentFilter.value) {
      case FilterType.COMPLETED:
        return tasks.filter(task => task.completed_at !== null);
      case FilterType.INCOMPLETE:
        return tasks.filter(task => task.completed_at === null);
      default:
        return tasks;
    }
  });

  return {
    currentFilter,
    filteredTasks,
    filterOptions
  };
}