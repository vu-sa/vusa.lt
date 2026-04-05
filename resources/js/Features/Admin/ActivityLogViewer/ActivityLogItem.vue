<template>
  <div class="space-y-2">
    <div class="flex items-center justify-between gap-2">
      <div class="flex items-center gap-2">
        <div
          :class="[
            'flex items-center justify-center h-6 w-6 rounded-full shrink-0',
            activity.description === 'created'
              ? 'bg-green-100 dark:bg-green-900/30'
              : 'bg-blue-100 dark:bg-blue-900/30',
          ]"
        >
          <Plus v-if="activity.description === 'created'" class="h-3.5 w-3.5 text-green-600 dark:text-green-400" />
          <Pencil v-else class="h-3.5 w-3.5 text-blue-600 dark:text-blue-400" />
        </div>
        <span class="text-sm font-medium">
          {{ activity.description === 'created' ? $t('Sukurta') : $t('Atnaujinta') }}
        </span>
      </div>
      <div v-if="activity.causer" class="shrink-0">
        <UserPopover :size="18" show-name :user="activity.causer" />
      </div>
    </div>

    <div v-if="activity.description === 'updated' && changedFields.length > 0" class="ml-8 space-y-1.5">
      <div
        v-for="field in changedFields"
        :key="field.key"
        class="rounded-md bg-zinc-50 dark:bg-zinc-800/50 px-3 py-2 text-sm"
      >
        <span class="font-medium text-zinc-700 dark:text-zinc-300">{{ field.label }}</span>
        <div class="mt-0.5 text-zinc-500 dark:text-zinc-400">
          <template v-if="field.isStatus">
            <Badge :variant="getStatusVariant(field.oldValue)" class="mr-1">
              {{ formatStatus(field.oldValue) }}
            </Badge>
            <ArrowRight class="inline h-3.5 w-3.5 mx-1 text-zinc-400" />
            <Badge :variant="getStatusVariant(field.newValue)" class="ml-1">
              {{ formatStatus(field.newValue) }}
            </Badge>
          </template>
          <template v-else-if="field.isDate">
            {{ formatDateValue(field.oldValue) || '—' }}
            <ArrowRight class="inline h-3.5 w-3.5 mx-1 text-zinc-400" />
            {{ formatDateValue(field.newValue) || '—' }}
          </template>
          <template v-else-if="field.isRichText">
            <span class="italic">{{ $t('Turinys atnaujintas') }}</span>
          </template>
          <template v-else>
            {{ truncateText(field.oldValue) || '—' }}
            <ArrowRight class="inline h-3.5 w-3.5 mx-1 text-zinc-400" />
            {{ truncateText(field.newValue) || '—' }}
          </template>
        </div>
      </div>
    </div>

    <p :title="activity.created_at" class="ml-8 text-xs text-zinc-400 dark:text-zinc-500">
      {{ formatRelativeTime(activity.created_at) }}
    </p>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';
import { Plus, Pencil, ArrowRight } from 'lucide-vue-next';

import { formatRelativeTime } from '@/Utils/IntlTime';
import UserPopover from '@/Components/Avatars/UserPopover.vue';
import { Badge } from '@/Components/ui/badge';

const props = defineProps<{
  activity: Record<string, any>;
}>();

const HIDDEN_KEYS = ['updated_at', 'created_at', 'deleted_at', 'id', 'created_by', 'tenant_id'];

const DATE_KEYS = ['occurred_at', 'resolved_at'];

const RICH_TEXT_KEYS = ['description', 'solution', 'steps_taken'];

const fieldLabels: Record<string, () => string> = {
  title: () => $t('entities.problem.title'),
  description: () => $t('entities.problem.description'),
  solution: () => $t('entities.problem.solution'),
  steps_taken: () => $t('entities.problem.steps_taken'),
  occurred_at: () => $t('entities.problem.occurred_at'),
  resolved_at: () => $t('entities.problem.resolved_at'),
  status: () => $t('entities.problem.status'),
  responsible_user_id: () => $t('entities.problem.responsible_user'),
  name: () => $t('Pavadinimas'),
};

const getFieldLabel = (key: string): string => {
  return fieldLabels[key]?.() ?? key.replace(/_/g, ' ');
};

interface ChangedField {
  key: string;
  label: string;
  oldValue: string;
  newValue: string;
  isStatus: boolean;
  isDate: boolean;
  isRichText: boolean;
}

const changedFields = computed<ChangedField[]>(() => {
  if (!props.activity.properties?.attributes) return [];

  return Object.keys(props.activity.properties.attributes)
    .filter(key => !HIDDEN_KEYS.includes(key))
    .map(key => ({
      key,
      label: getFieldLabel(key),
      oldValue: props.activity.properties.old?.[key] ?? '',
      newValue: props.activity.properties.attributes[key] ?? '',
      isStatus: key === 'status',
      isDate: DATE_KEYS.includes(key),
      isRichText: RICH_TEXT_KEYS.includes(key),
    }));
});

const formatStatus = (status: string): string => {
  const map: Record<string, () => string> = {
    open: () => $t('Atvira'),
    in_progress: () => $t('Vykdoma'),
    resolved: () => $t('Išspręsta'),
  };
  return map[status]?.() ?? status;
};

const getStatusVariant = (status: string): 'destructive' | 'default' | 'secondary' | 'outline' => {
  const map: Record<string, 'destructive' | 'default' | 'secondary' | 'outline'> = {
    open: 'destructive',
    in_progress: 'secondary',
    resolved: 'default',
  };
  return map[status] ?? 'outline';
};

const formatDateValue = (value: string | null): string => {
  if (!value) return '';
  try {
    return new Date(value).toLocaleDateString('lt-LT');
  }
  catch {
    return value;
  }
};

const stripHtml = (html: string): string => html.replace(/<[^>]*>/g, '').trim();

const truncateText = (value: string | null): string => {
  if (!value) return '';
  const text = stripHtml(String(value));
  return text.length > 80 ? `${text.substring(0, 80)}…` : text;
};
</script>
