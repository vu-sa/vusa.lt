<template>
  <SectionCard
    :title="$t('Užduotys')"
    :icon="ListTodo"
    :count="openTasks.length || undefined"
    :action-label="$t('Visos užduotys')"
    :empty="openTasks.length === 0"
    @action="$emit('view-all')"
  >
    <div class="space-y-1">
      <div
        v-for="task in previewTasks"
        :key="task.id"
        class="flex items-center gap-3 rounded-md px-2 py-2"
      >
        <component
          :is="task.is_overdue ? AlertCircle : Circle"
          :class="['h-4 w-4 shrink-0', task.is_overdue ? 'text-destructive' : 'text-muted-foreground']"
        />
        <span class="min-w-0 flex-1 truncate text-sm font-medium text-foreground">
          {{ task.name }}
        </span>

        <Badge v-if="urgencyBadge(task)" :variant="urgencyBadge(task)!.variant" class="shrink-0 text-xs">
          {{ urgencyBadge(task)!.label }}
        </Badge>

        <UsersAvatarGroup
          v-if="task.users?.length"
          :users="task.users"
          :max="3"
          :size="22"
          class="shrink-0"
        />
      </div>

      <p v-if="openTasks.length > previewTasks.length" class="px-2 pt-1 text-xs text-muted-foreground">
        {{ $t('ir dar :count', { count: openTasks.length - previewTasks.length }) }}
      </p>
    </div>

    <template #empty>
      <div class="py-8 text-center">
        <ListTodo class="mx-auto mb-2 h-8 w-8 text-muted-foreground" />
        <p class="text-sm text-muted-foreground">
          {{ $t('Nėra aktyvių užduočių') }}
        </p>
      </div>
    </template>
  </SectionCard>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';
import { AlertCircle, Circle, ListTodo } from 'lucide-vue-next';

import { SectionCard } from '@/Components/ui/section-card';
import { Badge } from '@/Components/ui/badge';
import UsersAvatarGroup from '@/Components/Avatars/UsersAvatarGroup.vue';

interface PreviewTask {
  id: string | number;
  name: string;
  due_date?: string | null;
  completed_at?: string | null;
  is_overdue?: boolean;
  users?: App.Entities.User[];
}

const props = defineProps<{
  tasks?: PreviewTask[];
}>();

defineEmits<{
  'view-all': [];
}>();

const DUE_SOON_DAYS = 7;

const openTasks = computed(() => {
  return (props.tasks ?? [])
    .filter((task) => !task.completed_at)
    .sort((a, b) => {
      // Overdue first, then by soonest due date, then undated last.
      if (!!a.is_overdue !== !!b.is_overdue) {
        return a.is_overdue ? -1 : 1;
      }
      const aDue = a.due_date ? new Date(a.due_date).getTime() : Infinity;
      const bDue = b.due_date ? new Date(b.due_date).getTime() : Infinity;
      return aDue - bDue;
    });
});

const previewTasks = computed(() => openTasks.value.slice(0, 3));

const isDueSoon = (task: PreviewTask) => {
  if (task.is_overdue || !task.due_date) {
    return false;
  }
  const diff = new Date(task.due_date).getTime() - Date.now();
  return diff >= 0 && diff <= DUE_SOON_DAYS * 24 * 60 * 60 * 1000;
};

const urgencyBadge = (task: PreviewTask): { label: string; variant: 'destructive' | 'secondary' } | null => {
  if (task.is_overdue) {
    return { label: $t('Vėluoja'), variant: 'destructive' };
  }
  if (isDueSoon(task)) {
    return { label: $t('Greitai'), variant: 'secondary' };
  }
  return null;
};
</script>
