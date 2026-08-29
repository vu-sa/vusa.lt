<template>
  <section>
    <div v-if="showHeading" class="mb-4 flex items-center gap-4">
      <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">
        {{ $t('Darbotvarkė') }}
      </h2>
      <div class="h-px flex-1 bg-gradient-to-r from-zinc-200 to-transparent dark:from-zinc-700" />
      <span v-if="items.length" class="shrink-0 text-xs text-zinc-400 dark:text-zinc-500">
        {{ items.length }}
      </span>
    </div>

    <p v-if="items.length === 0" class="py-6 text-center text-sm italic text-zinc-500 dark:text-zinc-400">
      {{ isUpcoming ? $t('Darbotvarkė dar nepaskelbta') : $t('Darbotvarkė dar neįvesta') }}
    </p>

    <!-- Flat divided list rather than a card: it sits on the page, it does not float above it -->
    <div
      v-else
      class="divide-y divide-zinc-200/70 border-y border-zinc-200/70 dark:divide-zinc-800 dark:border-zinc-800"
    >
      <template v-if="ready || items.length <= 8">
        <PublicAgendaItemRow
          v-for="item in items"
          :key="item.id"
          :item
          :requires-student-perspective
          :is-upcoming
          :disclosure-group
        />
      </template>

      <!-- Long agendas paint a skeleton on the first frame so the page is interactive sooner -->
      <template v-else>
        <div v-for="i in 8" :key="i" class="animate-pulse px-1 py-3.5 sm:px-2">
          <div class="h-3.5 w-2/3 rounded bg-zinc-200 dark:bg-zinc-800" />
          <div class="mt-2 h-2.5 w-1/3 rounded bg-zinc-100 dark:bg-zinc-800/60" />
        </div>
      </template>
    </div>
  </section>
</template>

<script setup lang="ts">
import { onMounted, ref, useId } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';

import PublicAgendaItemRow from '@/Components/Public/PublicAgendaItemRow.vue';

const {
  requiresStudentPerspective = true,
  isUpcoming = false,
  showHeading = true,
} = defineProps<{
  items: App.Entities.AgendaItem[];
  /** Defaults to true: dropping the student view must be a deliberate choice, never a fallthrough. */
  requiresStudentPerspective?: boolean;
  isUpcoming?: boolean;
  showHeading?: boolean;
}>();

// Unique per list, so two agendas on one page do not close each other's rows.
const disclosureGroup = `agenda-${useId()}`;

const ready = ref(false);
onMounted(() => {
  requestAnimationFrame(() => setTimeout(() => (ready.value = true), 100));
});
</script>
