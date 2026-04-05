<template>
  <CommandItem
    :value="itemValue"
    class="group cursor-pointer rounded-lg px-3 py-2.5 transition-colors hover:bg-accent data-[highlighted]:bg-accent"
    @select="handleSelect"
  >
    <div class="flex items-center gap-3 w-full min-w-0">
      <!-- Icon container with background -->
      <div class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-rose-500/10 text-rose-600 dark:bg-rose-500/20 dark:text-rose-400 group-hover:bg-rose-500/15 dark:group-hover:bg-rose-500/25 transition-colors">
        <CalendarDays class="size-4" />
      </div>

      <!-- Content -->
      <div class="flex-1 min-w-0">
        <div class="flex items-center gap-2">
          <span class="font-medium truncate text-sm group-hover:text-foreground transition-colors">
            {{ displayTitle }}
          </span>
          <span
            v-if="isUpcoming"
            class="inline-flex items-center rounded-full px-1.5 py-0.5 text-[10px] font-medium ring-1 ring-inset bg-emerald-500/10 text-emerald-600 ring-emerald-500/20 dark:bg-emerald-500/20 dark:text-emerald-400"
          >
            {{ $t('Būsimas') }}
          </span>
        </div>
        <div class="flex items-center gap-1.5 text-xs text-muted-foreground mt-0.5">
          <span v-if="event.tenant_name" class="shrink-0 font-medium">{{ event.tenant_name }}</span>
          <span v-if="event.tenant_name && formattedDate" class="text-muted-foreground/40">•</span>
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
import { trans as $t, getActiveLanguage } from 'laravel-vue-i18n';
import { ChevronRight, CalendarDays } from 'lucide-vue-next';

import { CommandItem } from '@/Components/ui/command';
import { useCommandPalette, type RecentItem } from '@/Composables/useCommandPalette';
import type { CalendarSearchResult } from '@/Composables/useAdminSearch';

const props = defineProps<{
  event: CalendarSearchResult;
}>();

const { close, addRecentItem } = useCommandPalette();

const itemValue = computed(() => `calendar-${props.event.id}`);

const displayTitle = computed(() => {
  const lang = getActiveLanguage();
  if (lang === 'en' && props.event.title_en) return props.event.title_en;
  if (props.event.title_lt) return props.event.title_lt;
  return props.event.title || $t('Be pavadinimo');
});

const formattedDate = computed(() => {
  if (!props.event.date) return null;
  const date = new Date(props.event.date * 1000);
  return date.toLocaleDateString('lt-LT', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  });
});

const isUpcoming = computed(() => {
  if (!props.event.date) return false;
  return props.event.date * 1000 > Date.now();
});

const handleSelect = () => {
  // Add to recent items
  addRecentItem({
    id: props.event.id,
    type: 'calendar',
    title: displayTitle.value,
    href: route('calendar.edit', props.event.id),
  } as Omit<RecentItem, 'timestamp'>);

  // Navigate to edit page
  close();
  router.visit(route('calendar.edit', props.event.id));
};
</script>
