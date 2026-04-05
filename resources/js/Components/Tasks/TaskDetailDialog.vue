<template>
  <Dialog :open @update:open="$emit('close')">
    <DialogContent class="sm:max-w-lg">
      <DialogHeader>
        <div class="flex items-center gap-3">
          <div :class="[
            'flex h-10 w-10 shrink-0 items-center justify-center rounded-lg',
            actionTypeClasses
          ]">
            <component :is="actionTypeIcon" class="h-5 w-5" />
          </div>
          <div class="min-w-0 flex-1">
            <DialogTitle class="text-base">
              {{ task.name }}
            </DialogTitle>
            <DialogDescription v-if="task.taskable?.name" class="mt-0.5">
              {{ task.taskable.name }}
            </DialogDescription>
          </div>
        </div>
      </DialogHeader>

      <!-- Task description/instructions -->
      <div v-if="task.description" class="rounded-lg border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-800/50">
        <div class="flex gap-3">
          <InfoIcon class="mt-0.5 h-4 w-4 shrink-0 text-zinc-500" />
          <div class="text-sm text-zinc-700 dark:text-zinc-300">
            <p class="font-medium text-zinc-900 dark:text-zinc-100">
              {{ $t('Ką reikia padaryti?') }}
            </p>
            <p class="mt-1">
              {{ task.description }}
            </p>
          </div>
        </div>
      </div>

      <!-- Due date and status -->
      <div class="flex flex-wrap items-center gap-3">
        <Badge v-if="task.due_date" :variant="isOverdue ? 'destructive' : 'secondary'" class="gap-1.5">
          <CalendarIcon class="h-3 w-3" />
          {{ formatDueDate(task.due_date) }}
        </Badge>
        <Badge v-if="!task.can_be_manually_completed" variant="outline" class="gap-1.5">
          <RotateCwIcon class="h-3 w-3" />
          {{ $t('Automatinė užduotis') }}
        </Badge>
      </div>

      <!-- Quick actions for periodicity gap tasks -->
      <div v-if="isPeriodicityGapTask && !task.completed_at" class="space-y-3">
        <p class="text-sm font-medium text-zinc-700 dark:text-zinc-300">
          {{ $t('Pasirinkite veiksmą:') }}
        </p>
        <div class="grid gap-2 sm:grid-cols-2">
          <Button
            variant="outline"
            class="h-auto flex-col gap-2 border-emerald-200 bg-emerald-50 p-4 text-left hover:bg-emerald-100 dark:border-emerald-800 dark:bg-emerald-950/30 dark:hover:bg-emerald-900/40"
            @click="$emit('schedule-meeting')"
          >
            <CalendarPlusIcon class="h-6 w-6 text-emerald-600 dark:text-emerald-400" />
            <div>
              <p class="font-medium text-emerald-700 dark:text-emerald-300">
                {{ $t('tasks.periodicity_gap.action_schedule_meeting') }}
              </p>
              <p class="mt-0.5 text-xs text-emerald-600/80 dark:text-emerald-400/80">
                {{ $t('Sukurti naują posėdį šiai institucijai') }}
              </p>
            </div>
          </Button>
          <Button
            variant="outline"
            class="h-auto flex-col gap-2 border-amber-200 bg-amber-50 p-4 text-left hover:bg-amber-100 dark:border-amber-800 dark:bg-amber-950/30 dark:hover:bg-amber-900/40"
            @click="$emit('report-no-meeting')"
          >
            <CalendarOffIcon class="h-6 w-6 text-amber-600 dark:text-amber-400" />
            <div>
              <p class="font-medium text-amber-700 dark:text-amber-300">
                {{ $t('tasks.periodicity_gap.action_report_no_meeting') }}
              </p>
              <p class="mt-0.5 text-xs text-amber-600/80 dark:text-amber-400/80">
                {{ $t('Pranešti, kad posėdžių nebus tam tikrą laikotarpį') }}
              </p>
            </div>
          </Button>
        </div>
      </div>

      <!-- Assigned users -->
      <div v-if="task.users?.length" class="space-y-2">
        <p class="text-sm font-medium text-zinc-700 dark:text-zinc-300">
          {{ $t('forms.fields.responsible_people') }}
        </p>
        <div class="flex flex-wrap gap-2">
          <div v-for="user in task.users" :key="user.id" class="flex items-center gap-2 rounded-full bg-zinc-100 py-1 pl-1 pr-3 dark:bg-zinc-800">
            <Avatar class="h-6 w-6">
              <AvatarImage v-if="user.profile_photo_path" :src="user.profile_photo_path" :alt="user.name" />
              <AvatarFallback class="text-xs">
                {{ getInitials(user.name) }}
              </AvatarFallback>
            </Avatar>
            <span class="text-sm">{{ user.name }}</span>
          </div>
        </div>
      </div>

      <DialogFooter>
        <Button variant="outline" @click="$emit('close')">
          {{ $t('Uždaryti') }}
        </Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';
import { format, parseISO, isPast } from 'date-fns';
import { lt, enUS } from 'date-fns/locale';
import { usePage } from '@inertiajs/vue3';
import {
  Info as InfoIcon,
  Calendar as CalendarIcon,
  CalendarPlus as CalendarPlusIcon,
  CalendarOff as CalendarOffIcon,
  RotateCw as RotateCwIcon,
  ClipboardCheck as ClipboardCheckIcon,
  ShieldCheck as ShieldCheckIcon,
  Package as PackageIcon,
  PackageCheck as PackageCheckIcon,
  Clock as ClockIcon,
} from 'lucide-vue-next';

import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/Components/ui/dialog';
import { Button } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';
import { Avatar, AvatarFallback, AvatarImage } from '@/Components/ui/avatar';
import { TaskActionType } from '@/Types/TaskTypes';

interface TaskUser {
  id: string;
  name: string;
  profile_photo_path?: string;
}

interface TaskWithDetails {
  id: string;
  name: string;
  description?: string | null;
  due_date?: string | null;
  completed_at?: string | null;
  action_type?: TaskActionType | string | null;
  is_overdue?: boolean;
  can_be_manually_completed?: boolean;
  taskable?: {
    id: string;
    name?: string;
  } | null;
  taskable_type: string;
  taskable_id: string;
  users?: TaskUser[];
}

const props = defineProps<{
  task: TaskWithDetails;
  open: boolean;
}>();

defineEmits<{
  'close': [];
  'schedule-meeting': [];
  'report-no-meeting': [];
}>();

const getDateLocale = () => usePage().props.app?.locale === 'lt' ? lt : enUS;

const isOverdue = computed(() => {
  if (props.task.is_overdue !== undefined) return props.task.is_overdue;
  if (!props.task.due_date || props.task.completed_at) return false;
  return isPast(parseISO(props.task.due_date));
});

const isPeriodicityGapTask = computed(() => {
  return props.task.action_type === TaskActionType.PeriodicityGap || props.task.action_type === 'periodicity_gap';
});

const actionTypeIcon = computed(() => {
  switch (props.task.action_type) {
    case TaskActionType.Approval:
    case 'approval':
      return ShieldCheckIcon;
    case TaskActionType.Pickup:
    case 'pickup':
      return PackageIcon;
    case TaskActionType.Return:
    case 'return':
      return PackageCheckIcon;
    case TaskActionType.PeriodicityGap:
    case 'periodicity_gap':
      return ClockIcon;
    default:
      return ClipboardCheckIcon;
  }
});

const actionTypeClasses = computed(() => {
  switch (props.task.action_type) {
    case TaskActionType.Approval:
    case 'approval':
      return 'bg-blue-100 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400';
    case TaskActionType.Pickup:
    case 'pickup':
      return 'bg-amber-100 text-amber-600 dark:bg-amber-900/30 dark:text-amber-400';
    case TaskActionType.Return:
    case 'return':
      return 'bg-emerald-100 text-emerald-600 dark:bg-emerald-900/30 dark:text-emerald-400';
    case TaskActionType.PeriodicityGap:
    case 'periodicity_gap':
      return 'bg-orange-100 text-orange-600 dark:bg-orange-900/30 dark:text-orange-400';
    default:
      return 'bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400';
  }
});

const formatDueDate = (dateString: string) => {
  return format(parseISO(dateString), 'yyyy-MM-dd', { locale: getDateLocale() });
};

const getInitials = (name: string) => {
  return name
    .split(' ')
    .map(n => n[0])
    .join('')
    .toUpperCase()
    .slice(0, 2);
};
</script>
