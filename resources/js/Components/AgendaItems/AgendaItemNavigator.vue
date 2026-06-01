<template>
  <div class="sticky top-0 z-20 -mx-6 -mt-6 mb-2 border-b border-zinc-200 dark:border-zinc-800 bg-white/85 dark:bg-zinc-950/85 px-6 py-2 backdrop-blur">
    <div class="flex items-center justify-between gap-4">
      <!-- Back to agenda -->
      <Link
        :href="route('meetings.show', meetingId)"
        class="inline-flex items-center gap-1.5 text-sm text-zinc-500 hover:text-primary transition-colors"
      >
        <ChevronLeft class="h-4 w-4" />
        {{ $t('Visa darbotvarkė') }}
      </Link>

      <!-- Position popover + prev/next -->
      <div class="flex items-center gap-1">
        <Button
          variant="ghost"
          size="icon"
          class="h-8 w-8"
          :disabled="!previousItem"
          :title="$t('Ankstesnis punktas')"
          @click="previousItem && emit('navigate', previousItem.id)"
        >
          <ChevronLeft class="h-4 w-4" />
        </Button>

        <Popover v-model:open="isOpen">
          <PopoverTrigger as-child>
            <Button variant="ghost" size="sm" class="h-8 gap-1.5">
              {{ $t('Punktas') }}
              <span class="font-semibold text-zinc-900 dark:text-zinc-100">{{ currentIndex + 1 }}</span>
              <span class="text-zinc-400">/ {{ siblingAgendaItems.length }}</span>
              <ChevronsUpDown class="h-3.5 w-3.5 text-zinc-400" />
            </Button>
          </PopoverTrigger>
          <PopoverContent class="w-[26rem] max-w-[calc(100vw-2rem)] p-0" align="end">
            <div class="flex items-center justify-between gap-2 border-b border-zinc-200 dark:border-zinc-800 px-4 py-3">
              <h4 class="truncate text-sm font-semibold text-zinc-900 dark:text-zinc-100">
                {{ meetingTitle || $t('Darbotvarkė') }}
              </h4>
              <span class="shrink-0 text-xs text-zinc-400 dark:text-zinc-500">
                {{ acceptedCount }} / {{ siblingAgendaItems.length }} {{ $t('priimti') }}
              </span>
            </div>

            <div class="max-h-[60vh] overflow-y-auto py-1">
              <Link
                v-for="(item, index) in siblingAgendaItems"
                :key="item.id"
                :href="route('agendaItems.edit', item.id)"
                class="flex items-start gap-3 px-3 py-2 transition-colors"
                :class="item.id === currentId
                  ? 'bg-primary/5 dark:bg-primary/10'
                  : 'hover:bg-zinc-50 dark:hover:bg-zinc-800/50'"
                @click="isOpen = false"
              >
                <span
                  :class="[
                    'mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-md text-xs font-semibold',
                    getNumberBadgeClass(item as any),
                  ]"
                >
                  {{ String(index + 1).padStart(2, '0') }}
                </span>
                <div class="min-w-0 flex-1">
                  <p class="truncate text-sm text-zinc-900 dark:text-zinc-100">
                    {{ item.title }}
                  </p>
                  <div class="mt-0.5 flex items-center gap-2">
                    <span class="text-[11px] uppercase tracking-wide text-zinc-400 dark:text-zinc-500">
                      {{ typeLabel(item.type) }}
                    </span>
                    <span
                      v-if="item.brought_by_students"
                      class="inline-flex h-3.5 w-3.5 items-center justify-center rounded-sm bg-red-500 text-[9px] font-bold text-white"
                      :title="$t('Studentų klausimas')"
                    >S</span>
                  </div>
                </div>
                <span :class="['mt-1.5 h-2 w-2 shrink-0 rounded-full', getAgendaItemStatusMeta(item as any).dotClass]" />
              </Link>
            </div>
          </PopoverContent>
        </Popover>

        <Button
          variant="ghost"
          size="icon"
          class="h-8 w-8"
          :disabled="!nextItem"
          :title="$t('Kitas punktas')"
          @click="nextItem && emit('navigate', nextItem.id)"
        >
          <ChevronRight class="h-4 w-4" />
        </Button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { ChevronLeft, ChevronRight, ChevronsUpDown } from 'lucide-vue-next';

import { Button } from '@/Components/ui/button';
import { Popover, PopoverContent, PopoverTrigger } from '@/Components/ui/popover';
import {
  getAgendaItemStatus,
  getAgendaItemStatusMeta,
  getNumberBadgeClass,
} from '@/Composables/useAgendaItemStyling';

interface SiblingItem {
  id: string;
  title: string;
  type?: string | null;
  order: number;
  brought_by_students: boolean;
  main_vote?: unknown;
}

const props = defineProps<{
  meetingId: string;
  meetingTitle?: string | null;
  currentId: string;
  siblingAgendaItems: SiblingItem[];
}>();

const emit = defineEmits<{
  navigate: [id: string];
}>();

const isOpen = ref(false);

const currentIndex = computed(() =>
  props.siblingAgendaItems.findIndex(item => item.id === props.currentId),
);

const previousItem = computed(() =>
  currentIndex.value > 0 ? props.siblingAgendaItems[currentIndex.value - 1] : null,
);

const nextItem = computed(() =>
  currentIndex.value >= 0 && currentIndex.value < props.siblingAgendaItems.length - 1
    ? props.siblingAgendaItems[currentIndex.value + 1]
    : null,
);

const acceptedCount = computed(() =>
  props.siblingAgendaItems.filter((item) => {
    const status = getAgendaItemStatus(item as any);
    return status === 'student_aligned' || status === 'consensus';
  }).length,
);

const typeLabel = (type?: string | null): string => {
  switch (type) {
    case 'voting': return $t('Balsuojamas');
    case 'informational': return $t('Informacinis');
    case 'deferred': return $t('Atidėtas');
    default: return $t('Nepažymėtas');
  }
};
</script>
