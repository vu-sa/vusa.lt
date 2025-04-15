<template>
  <DataTableProvider 
    :columns="columns" 
    :data="tasks" 
    :rowClassName="rowClassName"
    :enable-pagination="true"
    :page-size="10"
    :empty-message="$t('No tasks found.')"
    @update:sorting="handleSortChange"
  >
    <template #empty>
      <div class="flex flex-col items-center justify-center gap-2 text-zinc-400">
        <div class="flex h-10 w-10 items-center justify-center rounded-full border border-dashed">
          <CheckIcon class="h-5 w-5" />
        </div>
        <span>{{ $t('No tasks found.') }}</span>
      </div>
    </template>
  </DataTableProvider>
</template>

<script setup lang="tsx">
import { Link, router, usePage } from "@inertiajs/vue3";
import { trans as $t } from "laravel-vue-i18n";
import { computed, ref } from "vue";
import { CheckIcon, CalendarIcon, MoreHorizontalIcon, TrashIcon } from "lucide-vue-next";
import { Badge } from "@/Components/ui/badge";
import { Button } from "@/Components/ui/button";
import { Checkbox } from "@/Components/ui/checkbox";
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from "@/Components/ui/dropdown-menu";
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from "@/Components/ui/tooltip";
import { toast } from "vue-sonner";
import { format } from "date-fns";
import IconsFilled from "@/Types/Icons/filled";
import DataTableProvider from "@/Components/ui/data-table/DataTableProvider.vue";
import UsersAvatarGroup from "@/Components/Avatars/UsersAvatarGroup.vue";

// Define Task interface
interface Task extends App.Entities.Task {
  id: string;
  name: string;
  completed_at: string | null;
  due_date: string | null;
  taskable_type: string;
  taskable_id: string;
  taskable: any;
  users?: {
    id: string;
    name: string;
  }[];
}

const props = defineProps<{
  tasks: Task[];
}>();

// Track loading state
const isLoading = ref(false);

/**
 * Handle row styling based on task completion status
 */
const rowClassName = (row: Task) => {
  return row.completed_at !== null 
    ? "bg-zinc-100/50 opacity-50 dark:bg-zinc-900/50 dark:opacity-50" 
    : "";
};

/**
 * Handle sort changes
 */
const handleSortChange = (sorting) => {
  // Additional sorting logic could be added here if needed
};

/**
 * Format date
 */
const formatDate = (dateString: string) => {
  if (!dateString) return '';
  return format(new Date(dateString), 'yyyy-MM-dd');
};

/**
 * Update task completion status
 */
const updateTaskCompletion = (task: Task) => {
  if (isLoading.value) return;
  isLoading.value = true;
  
  // Toggle completion status
  const newCompletionState = task.completed_at === null;
  
  // Store current state for reverting if needed
  const previousState = task.completed_at;
  
  // Optimistic update for better UX
  task.completed_at = newCompletionState ? new Date().toISOString() : null;

  router.post(
    route("tasks.updateCompletionStatus", task.id),
    { completed: newCompletionState },
    {
      preserveScroll: true,
      preserveState: true,
      onSuccess: () => {
        isLoading.value = false;
        
        // Show appropriate toast message
        if (newCompletionState) {
          toast.success($t("Task marked as completed"), {
            description: task.name,
            icon: <CheckIcon class="h-4 w-4" />
          });
        } else {
          toast.info($t("Task marked as incomplete"), {
            description: task.name
          });
        }
      },
      onError: () => {
        // Revert optimistic update if server request fails
        isLoading.value = false;
        task.completed_at = previousState;
        
        toast.error($t("Failed to update task status"), {
          description: $t("Please try again")
        });
      },
    },
  );
};

/**
 * Delete a task
 */
const handleDelete = (task: Task) => {
  if (isLoading.value) return;
  isLoading.value = true;

  router.delete(route("tasks.destroy", task.id), {
    preserveScroll: true,
    preserveState: true,
    onSuccess: () => {
      isLoading.value = false;
      toast.success($t("Task deleted successfully"), {
        description: task.name
      });
    },
    onError: (errors) => {
      isLoading.value = false;
      toast.error($t("Failed to delete task"), {
        description: errors.message || $t("Please try again")
      });
    },
  });
};

/**
 * Get taskable icon based on type
 */
const getTaskableIcon = (taskableType: string) => {
  switch (taskableType) {
    case "App\\Models\\Meeting": return IconsFilled.MEETING;
    case "App\\Models\\User": return IconsFilled.USER;
    case "App\\Models\\Reservation": return IconsFilled.RESERVATION;
    default: return IconsFilled.HOME;
  }
};

/**
 * Get model route based on taskable type
 */
const getModelRoute = (taskableType: string) => {
  const modelType = taskableType.split("\\").pop() + "s";
  return modelType.toLowerCase();
};

/**
 * Table column definitions using Tanstack Table format
 */
const columns = [
  {
    id: "completion",
    header: "",
    cell: ({ row }) => {
      const task = row.original;
      const canComplete = task.users?.find(
        (user) => user.id === usePage().props.auth?.user?.id
      );
      
      return (
        <div class="flex justify-center">
          <Checkbox
            checked={task.completed_at !== null}
            disabled={!canComplete || isLoading.value}
            onUpdate:modelValue={() => updateTaskCompletion(task)}
            class="transition-all duration-200 hover:scale-110"
          />
        </div>
      );
    },
    size: 40,
  },
  {
    accessorKey: "name",
    header: () => $t("forms.fields.title"),
    cell: ({ row }) => (
      <TooltipProvider>
        <Tooltip>
          <TooltipTrigger asChild>
            <div class="max-w-[200px] truncate font-medium">
              {row.original.name}
            </div>
          </TooltipTrigger>
          <TooltipContent side="top" align="start">
            <p>{row.original.name}</p>
          </TooltipContent>
        </Tooltip>
      </TooltipProvider>
    ),
    size: 200,
  },
  {
    accessorKey: "subject",
    header: () => $t("forms.fields.subject"),
    cell: ({ row }) => {
      const task = row.original;
      const modelRoute = getModelRoute(task.taskable_type);
      const Icon = getTaskableIcon(task.taskable_type);
      
      return (
              <Link href={route(`${modelRoute}.show`, task.taskable_id)}>
                <Badge variant="outline" class="flex items-center gap-1">
                  <Icon class="h-3 w-3" />
                  <span class="max-w-[120px] truncate">
                    {task.taskable?.title || task.taskable?.name || task.taskable?.start_time}
                  </span>
                </Badge>
              </Link>
      );
    },
    size: 150,
  },
  {
    accessorKey: "users",
    header: () => $t("forms.fields.responsible_people"),
    cell: ({ row }) => (
      <UsersAvatarGroup size={32} users={row.original.users || []} />
    ),
    size: 150,
  },
  {
    accessorKey: "due_date",
    header: () => $t("forms.fields.due_date"),
    cell: ({ row }) => (
      <div class="flex items-center gap-2">
        <CalendarIcon class="h-4 w-4 text-zinc-500" />
        <span>{formatDate(row.original.due_date || '')}</span>
      </div>
    ),
    size: 150,
  },
  {
    id: "actions",
    cell: ({ row }) => {
      const task = row.original;
      if (task.completed_at !== null) return null;
      
      return (
        <DropdownMenu>
          <DropdownMenuTrigger asChild>
            <Button variant="ghost" size="icon" class="h-8 w-8 p-0">
              <MoreHorizontalIcon class="h-4 w-4" />
            </Button>
          </DropdownMenuTrigger>
          <DropdownMenuContent align="end">
            <DropdownMenuItem onClick={() => handleDelete(task)} class="text-destructive focus:text-destructive">
              <TrashIcon class="mr-2 h-4 w-4" />
              <span>{$t("Delete")}</span>
            </DropdownMenuItem>
          </DropdownMenuContent>
        </DropdownMenu>
      );
    },
    size: 60,
  },
];
</script>
