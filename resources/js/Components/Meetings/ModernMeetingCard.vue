<template>
  <Card
    class="hover:shadow-md transition-all duration-200 cursor-pointer border border-zinc-200 dark:border-zinc-800 hover:border-zinc-300 dark:hover:border-zinc-700"
    @click="$emit('click')">
    <CardContent class="p-4">
      <div class="flex items-start justify-between mb-3">
        <div class="flex-1">
          <h3 class="font-bold text-zinc-900 dark:text-zinc-100 mb-1 line-clamp-2">
            {{ meetingTitle }}
          </h3>
          <div class="flex items-center gap-2 text-sm text-zinc-500 dark:text-zinc-400">
            <Calendar class="h-4 w-4" />
            <time>{{ formatMeetingDate(meeting.start_time) }}</time>
          </div>
          <div class="flex items-center gap-2 text-sm text-zinc-500 dark:text-zinc-400 mt-1">
            <Clock class="h-4 w-4" />
            <time>{{ formatMeetingTime(meeting.start_time) }}</time>
          </div>
        </div>

        <div class="flex items-center gap-1">
          <Button v-if="canDelete" variant="ghost" size="sm"
            class="h-8 w-8 p-0 opacity-0 group-hover:opacity-100 transition-opacity" @click.stop="$emit('delete')">
            <Trash2 class="h-4 w-4" />
          </Button>
          <ChevronRight class="h-4 w-4 text-zinc-400" />
        </div>
      </div>

      <!-- Meeting Stats -->
      <div class="flex items-center gap-4 text-xs text-zinc-500 dark:text-zinc-400">
        <div class="flex items-center gap-1">
          <FileText class="h-3 w-3" />
          <span>{{ meeting.agenda_items?.length || 0 }} {{ $t('klausimai') }}</span>
        </div>
        <div v-if="meeting.tasks?.length" class="flex items-center gap-1">
          <CheckSquare class="h-3 w-3" />
          <span>{{ completedTasks }}/{{ meeting.tasks.length }} {{ $t('užduotys') }}</span>
        </div>
      </div>

      <!-- File Status Badges -->
      <div class="flex items-center gap-2 mt-3">
        <Badge
          v-if="meeting.has_protocol"
          variant="outline"
          class="text-xs gap-1 text-green-600 border-green-300 dark:text-green-400 dark:border-green-700"
        >
          <FileCheck class="h-3 w-3" />
          {{ $t('Protokolas') }}
        </Badge>
        <Badge
          v-else
          variant="outline"
          class="text-xs gap-1 text-zinc-400 border-zinc-200 dark:text-zinc-500 dark:border-zinc-700"
        >
          <File class="h-3 w-3" />
          {{ $t('Nėra protokolo') }}
        </Badge>
        <Badge
          v-if="meeting.has_report"
          variant="outline"
          class="text-xs gap-1 text-blue-600 border-blue-300 dark:text-blue-400 dark:border-blue-700"
        >
          <ClipboardCheck class="h-3 w-3" />
          {{ $t('Ataskaita') }}
        </Badge>
      </div>

      <!-- Institution Badge (if provided) -->
      <div v-if="showInstitution && institution" class="mt-3">
        <Badge variant="secondary" class="text-xs">
          {{ institution.short_name || institution.name }}
        </Badge>
      </div>

      <!-- Public Status Badge -->
      <div v-if="meeting.is_public" class="mt-3">
        <Badge variant="outline" class="text-xs gap-1 text-green-600 border-green-300 dark:text-green-400 dark:border-green-700">
          <Globe class="h-3 w-3" />
          {{ $t('Rodomas viešai') }}
        </Badge>
      </div>
    </CardContent>
  </Card>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';
import {
  Calendar,
  Clock,
  ChevronRight,
  FileText,
  File,
  FileCheck,
  ClipboardCheck,
  CheckSquare,
  Trash2,
  Globe,
} from 'lucide-vue-next';

import { Card, CardContent } from '@/Components/ui/card';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';

const props = defineProps<{
  meeting: App.Entities.Meeting;
  institution?: App.Entities.Institution;
  showInstitution?: boolean;
  canDelete?: boolean;
}>();

const emit = defineEmits<{
  click: [];
  delete: [];
}>();

const meetingTitle = computed(() => {
  if (props.meeting.title && props.meeting.title.trim() !== '') {
    return props.meeting.title;
  }

  const institutionName = props.institution?.name || 'Institucijos';
  return `${formatMeetingDate(props.meeting.start_time)} ${institutionName} posėdis`;
});

const completedTasks = computed(() => {
  if (!props.meeting.tasks?.length) return 0;
  return props.meeting.tasks.filter(task => task.completed_at).length;
});

const formatMeetingDate = (dateString: string) => {
  const date = new Date(dateString);
  return date.toLocaleDateString('lt-LT', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
  });
};

const formatMeetingTime = (dateString: string) => {
  const date = new Date(dateString);
  return date.toLocaleTimeString('lt-LT', {
    hour: '2-digit',
    minute: '2-digit',
  });
};
</script>
