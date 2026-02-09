<template>
  <CommandItem
    :value="itemValue"
    class="group cursor-pointer rounded-lg px-3 py-2.5 transition-colors hover:bg-accent data-[highlighted]:bg-accent"
    @select="handleSelect"
  >
    <div class="flex items-center gap-3 w-full min-w-0">
      <!-- Icon container with background -->
      <div class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-blue-500/10 text-blue-600 dark:bg-blue-500/20 dark:text-blue-400 group-hover:bg-blue-500/15 dark:group-hover:bg-blue-500/25 transition-colors">
        <MeetingIcon class="size-4" />
      </div>

      <!-- Content -->
      <div class="flex-1 min-w-0">
        <div class="flex items-center gap-2">
          <span class="font-medium truncate text-sm group-hover:text-foreground transition-colors">
            {{ meeting.title || $t('Be pavadinimo') }}
          </span>
          <!-- Related institution indicator -->
          <span
            v-if="isRelated"
            class="inline-flex items-center gap-1 rounded-full px-1.5 py-0.5 text-[10px] font-medium bg-purple-500/10 text-purple-600 ring-1 ring-inset ring-purple-500/20 dark:bg-purple-500/20 dark:text-purple-400"
            :title="$t('Iš susijusios institucijos')"
          >
            <LinkIcon class="size-2.5" />
            {{ $t('Susiję') }}
          </span>
          <span
            v-if="meeting.completion_status"
            :class="[
              'inline-flex items-center rounded-full px-1.5 py-0.5 text-[10px] font-medium ring-1 ring-inset',
              meeting.completion_status === 'complete'
                ? 'bg-emerald-500/10 text-emerald-600 ring-emerald-500/20 dark:bg-emerald-500/20 dark:text-emerald-400'
                : 'bg-amber-500/10 text-amber-600 ring-amber-500/20 dark:bg-amber-500/20 dark:text-amber-400'
            ]"
          >
            {{ meeting.completion_status === 'complete' ? $t('Baigtas') : $t('Nebaigtas') }}
          </span>
        </div>
        <div class="flex items-center gap-1.5 text-xs text-muted-foreground mt-0.5">
          <span v-if="meeting.tenant_shortname" class="shrink-0 font-medium">{{ meeting.tenant_shortname }}</span>
          <span v-if="meeting.tenant_shortname && meeting.institution_name_lt" class="text-muted-foreground/40">•</span>
          <span v-if="meeting.institution_name_lt" class="truncate">{{ meeting.institution_name_lt }}</span>
          <span v-if="(meeting.tenant_shortname || meeting.institution_name_lt) && formattedDate" class="text-muted-foreground/40">•</span>
          <span v-if="formattedDate" class="shrink-0 tabular-nums">{{ formattedDate }}</span>
        </div>
      </div>

      <!-- Arrow indicator -->
      <ChevronRight class="size-4 text-muted-foreground/40 opacity-0 group-hover:opacity-100 transition-opacity shrink-0" />
    </div>
  </CommandItem>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { router } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { ChevronRight, Link as LinkIcon } from 'lucide-vue-next';

import { CommandItem } from '@/Components/ui/command';
import { MeetingIcon } from '@/Components/icons';
import { useCommandPalette, type RecentItem } from '@/Composables/useCommandPalette';
import type { MeetingSearchResult } from '@/Composables/useAdminSearch';

const props = defineProps<{
  meeting: MeetingSearchResult;
  isRelated?: boolean;
}>();

const { close, addRecentItem } = useCommandPalette();

const itemValue = computed(() => `meeting-${props.meeting.id}`);

const formattedDate = computed(() => {
  if (!props.meeting.start_time) return null;
  const date = new Date(props.meeting.start_time * 1000);
  return date.toLocaleDateString('lt-LT', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  });
});

const handleSelect = () => {
  // Add to recent items
  addRecentItem({
    id: props.meeting.id,
    type: 'meeting',
    title: props.meeting.title || $t('Be pavadinimo'),
    href: route('meetings.show', props.meeting.id),
  } as Omit<RecentItem, 'timestamp'>);

  // Navigate and close
  close();
  router.visit(route('meetings.show', props.meeting.id));
};
</script>
