<template>
  <component
    :is="taskableLink ? Link : 'div'"
    :href="taskableLink || undefined"
    :class="[
      'group relative flex items-start gap-3 border border-transparent p-3 transition-colors',
      taskableLink ? 'cursor-pointer' : '',
      'hover:bg-accent',
      isOverdue && 'bg-status-danger-surface',
      actionTypeStyles.bgHover
    ]"
  >
    <!-- Action indicator - left side -->
    <div class="flex shrink-0 items-center justify-center" @click.stop>
      <!-- Checkbox for manual tasks -->
      <Checkbox
        v-if="canBeManuallyCompleted"
        :model-value="false"
        :disabled="isUpdating"
        class="h-5 w-5 border-2 transition-colors data-[state=checked]:bg-primary"
        @update:model-value="$emit('complete')"
      />

      <!-- Progress ring for auto-completing tasks with progress -->
      <div
        v-else-if="progress"
        class="relative h-6 w-6"
        :title="`${progress.current}/${progress.total}`"
      >
        <svg class="h-6 w-6 -rotate-90" viewBox="0 0 24 24">
          <circle
            cx="12" cy="12" r="10"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
            :class="actionTypeStyles.progressTrack"
          />
          <circle
            cx="12" cy="12" r="10"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
            stroke-linecap="round"
            :stroke-dasharray="`${progress.percentage * 0.628} 62.8`"
            :class="actionTypeStyles.progressFill"
          />
        </svg>
        <span class="absolute inset-0 flex items-center justify-center text-[8px] font-bold" :class="actionTypeStyles.progressText">
          {{ progress.percentage }}
        </span>
      </div>

      <!-- Icon for auto-completing tasks without progress -->
      <div
        v-else
        :class="[
          'flex h-6 w-6 items-center justify-center border border-border',
          actionTypeStyles.iconBg
        ]"
      >
        <component
          :is="actionTypeIcon"
          class="h-3.5 w-3.5"
          :class="actionTypeStyles.iconColor"
        />
      </div>
    </div>

    <!-- Task content -->
    <div class="min-w-0 flex-1">
      <div class="flex items-start justify-between gap-2">
        <div class="min-w-0 flex-1">
          <!-- Task name -->
          <p
            class="truncate text-sm font-medium text-foreground group-hover:text-primary"
            :title="name"
          >
            {{ name }}
          </p>

          <!-- Action type label for auto-completing tasks -->
          <div v-if="!canBeManuallyCompleted" class="mt-0.5 flex items-center gap-1.5">
            <span :class="['text-xs', actionTypeStyles.labelColor]">
              {{ actionTypeLabel }}
            </span>
            <span v-if="progress" class="text-xs text-muted-foreground">
              · {{ progress.current }}/{{ progress.total }}
            </span>
          </div>
        </div>

        <!-- Due date badge -->
        <div v-if="dueDate" class="shrink-0">
          <Badge
            :variant="isOverdue ? 'rose' : 'secondary'"
            :class="[
              'text-xs font-medium',
              isDueSoon && !isOverdue
                ? 'bg-status-attention-surface text-status-attention'
                : ''
            ]"
          >
            {{ formattedDueDate }}
          </Badge>
        </div>
      </div>
    </div>

    <!-- Actions menu (visible on hover, only for manual tasks) -->
    <div v-if="canBeManuallyCompleted" class="shrink-0 opacity-0 transition-opacity group-hover:opacity-100" @click.stop>
      <DropdownMenu>
        <DropdownMenuTrigger as-child>
          <Button
            variant="ghost"
            size="icon"
            class="h-7 w-7"
            :disabled="isUpdating"
          >
            <MoreHorizontalIcon class="h-4 w-4" />
          </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="end" class="w-48">
          <DropdownMenuItem @click="$emit('complete')">
            <CheckIcon class="mr-2 h-4 w-4" />
            {{ $t('Mark Complete') }}
          </DropdownMenuItem>
          <DropdownMenuItem
            class="text-destructive focus:text-destructive"
            @click="$emit('delete')"
          >
            <TrashIcon class="mr-2 h-4 w-4" />
            {{ $t('Delete') }}
          </DropdownMenuItem>
        </DropdownMenuContent>
      </DropdownMenu>
    </div>
  </component>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';
import { usePage, Link } from '@inertiajs/vue3';
import { formatDistanceToNow, parseISO, isToday, isTomorrow, differenceInDays } from 'date-fns';
import {
  ClipboardCheck as ClipboardCheckIcon,
  ShieldCheck as ShieldCheckIcon,
  Package as PackageIcon,
  PackageCheck as PackageCheckIcon,
  MoreHorizontal as MoreHorizontalIcon,
  Check as CheckIcon,
  Trash as TrashIcon,
} from 'lucide-vue-next';

import { useDateLocale } from '@/Composables/useDateLocale';
import { Checkbox } from '@/Components/ui/checkbox';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from '@/Components/ui/dropdown-menu';
import { TaskActionType, type TaskProgress, getActionTypeConfig } from '@/Types/TaskTypes';

const props = defineProps<{
  id: string;
  name: string;
  dueDate?: string | null;
  isOverdue?: boolean;
  actionType?: TaskActionType | string | null;
  progress?: TaskProgress | null;
  canBeManuallyCompleted?: boolean;
  isUpdating?: boolean;
  taskableType?: string | null;
  taskableId?: string | null;
}>();

defineEmits<{
  complete: [];
  delete: [];
}>();

// Locale for date formatting
const dateLocale = useDateLocale();

// Parse action type
const parsedActionType = computed(() => {
  if (!props.actionType) return null;
  if (typeof props.actionType === 'string') {
    return props.actionType as TaskActionType;
  }
  return props.actionType;
});

// Get action type configuration
const actionTypeConfig = computed(() => getActionTypeConfig(parsedActionType.value));

// Check if task can be manually completed
const canBeManuallyCompleted = computed(() => {
  return props.canBeManuallyCompleted ?? actionTypeConfig.value.canBeManuallyCompleted;
});

// Action type icon component
const actionTypeIcon = computed(() => {
  switch (parsedActionType.value) {
    case TaskActionType.Approval:
      return ShieldCheckIcon;
    case TaskActionType.Pickup:
      return PackageIcon;
    case TaskActionType.Return:
      return PackageCheckIcon;
    default:
      return ClipboardCheckIcon;
  }
});

// Action type label
const actionTypeLabel = computed(() => {
  switch (parsedActionType.value) {
    case TaskActionType.Approval:
      return $t('Auto-completes on approval');
    case TaskActionType.Pickup:
      return $t('Auto-completes on pickup');
    case TaskActionType.Return:
      return $t('Auto-completes on return');
    default:
      return '';
  }
});

// Styling based on action type
const actionTypeStyles = computed(() => {
  const type = parsedActionType.value;

  const styles = {
    [TaskActionType.Approval]: {
      bgHover: 'hover:bg-status-progress-surface',
      iconBg: 'bg-status-progress-surface',
      iconColor: 'text-status-progress',
      labelColor: 'text-status-progress',
      progressTrack: 'text-status-progress-surface',
      progressFill: 'text-status-progress',
      progressText: 'text-status-progress',
    },
    [TaskActionType.Pickup]: {
      bgHover: 'hover:bg-status-attention-surface',
      iconBg: 'bg-status-attention-surface',
      iconColor: 'text-status-attention',
      labelColor: 'text-status-attention',
      progressTrack: 'text-status-attention-surface',
      progressFill: 'text-status-attention',
      progressText: 'text-status-attention',
    },
    [TaskActionType.Return]: {
      bgHover: 'hover:bg-status-success-surface',
      iconBg: 'bg-status-success-surface',
      iconColor: 'text-status-success',
      labelColor: 'text-status-success',
      progressTrack: 'text-status-success-surface',
      progressFill: 'text-status-success',
      progressText: 'text-status-success',
    },
  };

  // Default/Manual task styles
  const defaultStyles = {
    bgHover: '',
    iconBg: 'bg-muted',
    iconColor: 'text-muted-foreground',
    labelColor: 'text-muted-foreground',
    progressTrack: 'text-muted',
    progressFill: 'text-muted-foreground',
    progressText: 'text-muted-foreground',
  };

  return type && styles[type] ? styles[type] : defaultStyles;
});

// Check if due soon (within 3 days)
const isDueSoon = computed(() => {
  if (!props.dueDate || props.isOverdue) return false;
  try {
    const date = parseISO(props.dueDate);
    const days = differenceInDays(date, new Date());
    return days >= 0 && days <= 3;
  }
  catch {
    return false;
  }
});

// Generate taskable link URL
const taskableLink = computed(() => {
  if (!props.taskableType || !props.taskableId) return null;

  const typeName = props.taskableType.includes('\\')
    ? props.taskableType.split('\\').pop()
    : props.taskableType;

  const modelRoute = (`${typeName}s`).toLowerCase();

  try {
    return route(`${modelRoute}.show`, props.taskableId);
  }
  catch {
    return null;
  }
});

// Format due date
const formattedDueDate = computed(() => {
  if (!props.dueDate) return '';
  try {
    const date = parseISO(props.dueDate);
    if (isToday(date)) return $t('Today');
    if (isTomorrow(date)) return $t('Tomorrow');
    return formatDistanceToNow(date, { addSuffix: false, locale: dateLocale.value });
  }
  catch {
    return '';
  }
});
</script>
