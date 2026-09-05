<template>
  <section>
    <div v-if="showHeading" class="mb-4 flex items-baseline gap-3 border-l-2 border-brand pl-3">
      <h2 class="u-display text-lg font-bold tracking-tight text-foreground sm:text-xl">
        {{ $t('Darbotvarkė') }}
      </h2>
      <span v-if="items.length" class="font-mono text-xs text-muted-foreground">
        ({{ items.length }})
      </span>
    </div>

    <p v-if="items.length === 0" class="py-6 text-center text-sm italic text-muted-foreground">
      {{ isUpcoming ? $t('Darbotvarkė dar nepaskelbta') : $t('Darbotvarkė dar neįvesta') }}
    </p>

    <!-- Flat divided list rather than a card: it sits on the page, it does not float above it -->
    <div
      v-else
      class="divide-y divide-border border-y border-border"
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
          <div class="h-3.5 w-2/3 bg-muted" />
          <div class="mt-2 h-2.5 w-1/3 bg-muted/60" />
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
