<template>
  <div class="-mx-6 -mt-6 mb-2 border-b border-border bg-card px-6 py-2">
    <div class="flex items-center justify-between gap-4">
      <!-- Back to agenda -->
      <Link
        :href="route('meetings.show', meetingId)"
        class="u-touch inline-flex items-center gap-1.5 text-sm text-muted-foreground transition-colors hover:text-primary"
      >
        <ChevronLeft class="h-4 w-4" />
        {{ $t('Visa darbotvarkė') }}
      </Link>

      <!-- Position popover + prev/next -->
      <div class="flex items-center gap-1">
        <Button
          variant="ghost"
          size="icon"
          class="u-touch"
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
              <span class="font-semibold text-foreground">{{ currentIndex + 1 }}</span>
              <span class="text-muted-foreground">/ {{ siblingAgendaItems.length }}</span>
              <ChevronsUpDown class="h-3.5 w-3.5 text-muted-foreground" />
            </Button>
          </PopoverTrigger>
          <PopoverContent class="w-[26rem] max-w-[calc(100vw-2rem)] p-0" align="end">
            <div class="flex items-center justify-between gap-2 border-b border-border px-4 py-3">
              <h4 class="truncate text-sm font-semibold text-foreground">
                {{ meetingTitle || $t('Darbotvarkė') }}
              </h4>
              <span class="shrink-0 text-xs text-muted-foreground">
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
                  ? 'bg-secondary'
                  : 'hover:bg-secondary/70'"
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
                  <p class="truncate text-sm text-foreground">
                    {{ item.title }}
                  </p>
                  <div class="mt-0.5 flex items-center gap-2">
                    <span class="text-[11px] uppercase tracking-wide text-muted-foreground">
                      {{ typeLabel(item.type) }}
                    </span>
                    <span
                      v-if="item.brought_by_students"
                      class="inline-flex h-3.5 w-3.5 items-center justify-center rounded-sm bg-red-500 text-[9px] font-bold text-white"
                      :title="$t('Studentų klausimas')"
                    >S</span>
                  </div>
                </div>
                <span :class="['mt-1.5 h-2 w-2 shrink-0 rounded-full', getAgendaItemStatusMeta(item as any, props.requiresStudentPerspective).dotClass]" />
              </Link>
            </div>
          </PopoverContent>
        </Popover>

        <Button
          variant="ghost"
          size="icon"
          class="u-touch"
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

const props = withDefaults(defineProps<{
  meetingId: string;
  meetingTitle?: string | null;
  currentId: string;
  siblingAgendaItems: SiblingItem[];
  /** False for VU SA's own bodies — a recorded decision alone means decided. */
  requiresStudentPerspective?: boolean;
}>(), {
  requiresStudentPerspective: true,
});

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
    const status = getAgendaItemStatus(item as any, props.requiresStudentPerspective);
    return status === 'student_aligned' || status === 'consensus' || status === 'decision_positive';
  }).length,
);

const typeLabel = (type?: string | null): string => {
  switch (type) {
    case 'voting': return $t('Balsuojamas');
    case 'informational': return $t('Informacinis');
    case 'deferred': return $t('Atidėtas');
    case 'break': return $t('Pertrauka');
    default: return $t('Nepažymėtas');
  }
};
</script>
